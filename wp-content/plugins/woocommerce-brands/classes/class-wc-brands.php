<?php

/**
 * WC_Brands class.
 */
class WC_Brands {

	const E_WC_COUPON_EXCLUDED_BRANDS = 115;

	var $template_url;
	var $plugin_path;

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->template_url = apply_filters( 'woocommerce_template_url', 'woocommerce/' );

		add_action( 'woocommerce_register_taxonomy', array( __CLASS__, 'init_taxonomy' ) );
		add_action( 'widgets_init', array( $this, 'init_widgets' ) );
		add_filter( 'template_include', array( $this, 'template_loader' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'styles' ) );
		add_action( 'wp', array( $this, 'body_class' ) );

		add_action( 'woocommerce_product_meta_end', array( $this, 'show_brand' ) );

		// Coupon validation and error handling.
		add_filter( 'woocommerce_coupon_is_valid_for_cart', array( $this, 'validate_coupon_is_valid_for_cart' ), null, 4 );
		add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'validate_coupon_is_valid_for_products' ), null, 4 );
		add_filter( 'woocommerce_coupon_error', array( $this, 'add_coupon_error_message' ), null, 3 );
		add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'maybe_apply_discount' ), null, 5 );

		add_filter( 'post_type_link', array( $this, 'post_type_link' ), 11, 2 );

		if ( function_exists( 'add_image_size' ) ) {
			add_image_size( 'brand-thumb', 300, 9999 );
		}

		if ( get_option( 'wc_brands_show_description' ) == 'yes' )
			add_action( 'woocommerce_archive_description', array( $this, 'brand_description' ) );

		$this->register_shortcodes();
    }

    /**
	 * Filter to allow product_brand in the permalinks for products.
	 *
	 * @access public
	 * @param string $permalink The existing permalink URL.
	 * @param WP_Post $post
	 * @return string
	 */
	public function post_type_link ( $permalink, $post ) {
		// Abort if post is not a product
		if ( $post->post_type !== 'product' )
			return $permalink;

		// Abort early if the placeholder rewrite tag isn't in the generated URL
		if ( false === strpos( $permalink, '%' ) )
			return $permalink;

		// Get the custom taxonomy terms in use by this post
		$terms = get_the_terms( $post->ID, 'product_brand' );

		if ( empty( $terms ) ) {
			// If no terms are assigned to this post, use a string instead (can't leave the placeholder there)
			$product_brand = _x( 'uncategorized', 'slug', 'wc_brands' );
		} else {
			// Replace the placeholder rewrite tag with the first term's slug
			$first_term = array_shift( $terms );
			$product_brand = $first_term->slug;
		}

		$find = array(
			'%product_brand%'
		);

		$replace = array(
			$product_brand
		);

		$replace = array_map( 'sanitize_title', $replace );

		$permalink = str_replace( $find, $replace, $permalink );

		return $permalink;
	} // End post_type_link()

    /**
     * Display a specific error message if the coupon doesn't validate because of a brands-related element.
     * @access public
     * @since  1.3.0
     * @param  string $err The error message.
     * @param  string $err_code The error code.
     * @param  object  $this_obj Cart object.
     * @return string
     */
    public function add_coupon_error_message ( $err, $err_code, $this_obj ) {
    	if ( self::E_WC_COUPON_EXCLUDED_BRANDS == $err_code ) {
    		$excluded_product_brands = (array)get_post_meta( $this_obj->id, 'exclude_product_brands', true );
    		// Store excluded brands that are in cart in $brands
			$brands = array();
			if ( sizeof( WC()->cart->get_cart() ) > 0 ) {
				foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

					$product_brands = wp_get_post_terms( $cart_item['product_id'], 'product_brand', array( "fields" => "ids" ) );
					if ( sizeof( $intersect = array_intersect( $product_brands, $excluded_product_brands ) ) > 0 ) {
						foreach( $intersect as $cat_id) {
							$cat = get_term( $cat_id, 'product_brand' );
							$brands[] = $cat->name;
						}
					}
				}
			}

			$err = sprintf( __( 'Sorry, this coupon is not applicable to the brands: %s.', 'wc_brands' ), implode( ', ', array_unique( $brands ) ) );
    	}
    	return $err;
    } // End add_coupon_error_message()

   	/**
     * Conditionally apply brands discounts.
     * @access private
     * @since  1.3.1
     * @return  void
     */
    public function maybe_apply_discount( $discount, $discounting_amount, $cart_item, $single, $this_obj ) {
    	// Deal only with product-centric coupons.
    	if ( ! is_a( $this_obj, 'WC_Coupon' ) || ! $this_obj->is_type( array( 'fixed_product', 'percent_product' ) ) ) {
    		return $discount;
    	}

    	// By default, store the discount value as our response.
    	$response = $discount;
    	$product_brands = wp_get_post_terms( $cart_item['product_id'], 'product_brand', array( "fields" => "ids" ) );

    	// If our coupon brands aren't present in the products in our cart, don't assign the discount.
    	if ( ! $this->_product_has_brands( $product_brands, $this_obj->included_brands ) ) {
    		$response = 0;
    	}

    	// If our excluded coupon brands are present in the products in our cart, don't assign the discount.
    	if ( $this->_product_has_brands( $product_brands, $this_obj->excluded_brands ) ) {
    		$response = 0;
    	}

    	return $response;
    } // End maybe_apply_discount()

    /**
     * Check whether given product brands are assigned to the current coupon being inspected.
     * @access private
     * @since  1.3.1
     * @return  void
     */
    private function _product_has_brands ( $product_brands, $coupon_brands ) {
    	$response = false;

    	if ( sizeof( array_intersect( $product_brands, $coupon_brands ) ) > 0 ) {
			$response = true;
		}

    	return $response;
    } // End _product_has_brands()

  	/**
     * Check if the coupon is valid for the given product.
     * @access public
     * @since  1.3.0
     * @return  void
     */
    public function validate_coupon_is_valid_for_cart ( $valid, $product ) {
    	$product_brands = wp_get_post_terms( $product->id, 'product_brand', array( "fields" => "ids" ) );
    	$stored_product_brands = (array)get_post_meta( $product->id, 'product_brands', true );
    	$excluded_product_brands = (array)get_post_meta( $product->id, 'exclude_product_brands', true );

		// Brand discounts
		if ( sizeof( $stored_product_brands ) > 0 ) {
			if ( sizeof( array_intersect( $product_brands, $stored_product_brands ) ) > 0 ) {
				$valid = true;
			}
		}

		// Specific brands excluded from the discount
		if ( sizeof( $excluded_product_brands ) > 0 ) {
			if ( sizeof( array_intersect( $product_brands, $excluded_product_brands ) ) > 0 ) {
				$valid = false;
			}
		}

    	return $valid;
    } // End validate_coupon_is_valid_for_cart()

    /**
     * Check if the coupon is valid for the given product.
     * @access public
     * @since  1.3.0
     * @return  void
     */
    public function validate_coupon_is_valid_for_products ( $valid, $product, $this_obj, $values ) {
    	if ( ! is_a( $this_obj, 'WC_Coupon' ) || ! $this_obj->is_type( array( 'fixed_product', 'percent_product' ) ) ) {
    		return $valid;
    	}

    	$product_brands = wp_get_post_terms( $product->id, 'product_brand', array( "fields" => "ids" ) );
    	$stored_product_brands = (array)get_post_meta( $this_obj->id, 'product_brands', true );
    	$excluded_product_brands = (array)get_post_meta( $this_obj->id, 'exclude_product_brands', true );

		// Brand discounts
		if ( sizeof( $stored_product_brands ) > 0 ) {
			if ( sizeof( array_intersect( $product_brands, $stored_product_brands ) ) > 0 ) {
				$valid = true;
			}
		}

		// Specific brands excluded from the discount
		if ( sizeof( $excluded_product_brands ) > 0 ) {
			if ( sizeof( array_intersect( $product_brands, $excluded_product_brands ) ) > 0 ) {
				$valid = false;
			}
		}

		// Store these for later, to avoid multiple look-ups when we filter on the discount.
		$this_obj->included_brands = $stored_product_brands;
		$this_obj->excluded_brands = $excluded_product_brands;

    	return $valid;
    } // End validate_coupon_is_valid_for_products()

    function body_class() {
	    if ( is_tax( 'product_brand' ) ) {
			add_filter( 'body_class', array( $this, 'add_body_class' ) );
		}
    }

    function add_body_class( $classes ) {
    	$classes[] = 'woocommerce';
    	$classes[] = 'woocommerce-page';
    	return $classes;
    }

    function styles() {
	    wp_enqueue_style( 'brands-styles', plugins_url( '/assets/css/style.css', dirname( __FILE__ ) ) );
    }

	/**
	 * init_taxonomy function.
	 *
	 * @access public
	 */
	public static function init_taxonomy() {
		global $woocommerce;

		$shop_page_id = woocommerce_get_page_id( 'shop' );

		$base_slug = $shop_page_id > 0 && get_page( $shop_page_id ) ? get_page_uri( $shop_page_id ) : 'shop';

		$category_base = get_option('woocommerce_prepend_shop_page_to_urls') == "yes" ? trailingslashit( $base_slug ) : '';

		register_taxonomy( 'product_brand',
	        array('product'),
	        apply_filters( 'register_taxonomy_product_brand', array(
	            'hierarchical' 			=> true,
	            'update_count_callback' => '_update_post_term_count',
	            'label' 				=> __( 'Brands', 'wc_brands'),
	            'labels' => array(
	                    'name' 				=> __( 'Brands', 'wc_brands' ),
	                    'singular_name' 	=> __( 'Brand', 'wc_brands' ),
	                    'search_items' 		=> __( 'Search Brands', 'wc_brands' ),
	                    'all_items' 		=> __( 'All Brands', 'wc_brands' ),
	                    'parent_item' 		=> __( 'Parent Brand', 'wc_brands' ),
	                    'parent_item_colon' => __( 'Parent Brand:', 'wc_brands' ),
	                    'edit_item' 		=> __( 'Edit Brand', 'wc_brands' ),
	                    'update_item' 		=> __( 'Update Brand', 'wc_brands' ),
	                    'add_new_item' 		=> __( 'Add New Brand', 'wc_brands' ),
	                    'new_item_name' 	=> __( 'New Brand Name', 'wc_brands' )
	            	),
	            'show_ui' 				=> true,
	            'show_in_nav_menus' 	=> true,
				'capabilities'			=> array(
					'manage_terms' 		=> 'manage_product_terms',
					'edit_terms' 		=> 'edit_product_terms',
					'delete_terms' 		=> 'delete_product_terms',
					'assign_terms' 		=> 'assign_product_terms'
				),
	            'rewrite' 				=> array( 'slug' => $category_base . __( 'brand', 'wc_brands' ), 'with_front' => false, 'hierarchical' => true )
	        ) )
	    );
	}

	/**
	 * init_widgets function.
	 *
	 * @access public
	 */
	function init_widgets() {

		// Inc
		require_once( 'widgets/class-wc-widget-brand-description.php' );
		require_once( 'widgets/class-wc-widget-brand-nav.php' );
		require_once( 'widgets/class-wc-widget-brand-thumbnails.php' );

		// Register
		register_widget( 'WC_Widget_Brand_Description' );
		register_widget( 'WC_Widget_Brand_Nav' );
		register_widget( 'WC_Widget_Brand_Thumbnails' );
	}

	/**
	 * Get the plugin path
	 */
	function plugin_path() {
		if ( $this->plugin_path ) return $this->plugin_path;

		return $this->plugin_path = untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) );
	}

	/**
	 * template_loader
	 *
	 * Handles template usage so that we can use our own templates instead of the themes.
	 *
	 * Templates are in the 'templates' folder. woocommerce looks for theme
	 * overides in /theme/woocommerce/ by default
	 *
	 * For beginners, it also looks for a woocommerce.php template first. If the user adds
	 * this to the theme (containing a woocommerce() inside) this will be used for all
	 * woocommerce templates.
	 */
	function template_loader( $template ) {

		$find = array( 'woocommerce.php' );
		$file = '';

		if ( is_tax( 'product_brand' ) ) {

			$term = get_queried_object();

			$file 		= 'taxonomy-' . $term->taxonomy . '.php';
			$find[] 	= 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
			$find[] 	= $this->template_url . 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
			$find[] 	= $file;
			$find[] 	= $this->template_url . $file;

		}

		if ( $file ) {
			$template = locate_template( $find );
			if ( ! $template ) $template = $this->plugin_path() . '/templates/' . $file;
		}

		return $template;
	}

	/**
	 * brand_image function.
	 *
	 * @access public
	 */
	function brand_description() {

		if ( ! is_tax( 'product_brand' ) )
			return;

		if ( ! get_query_var( 'term' ) )
			return;

		$thumbnail = '';

		$term = get_term_by( 'slug', get_query_var( 'term' ), 'product_brand' );
		$thumbnail = get_brand_thumbnail_url( $term->term_id, 'full' );

		woocommerce_get_template( 'brand-description.php', array(
			'thumbnail'	=> $thumbnail
		), 'woocommerce-brands', $this->plugin_path() . '/templates/' );
	}

	/**
	 * show_brand function.
	 *
	 * @access public
	 * @return void
	 */
	function show_brand() {
		global $post;

		if ( is_singular( 'product' ) ) {

			$taxonomy = get_taxonomy( 'product_brand' );
			$labels   = $taxonomy->labels;

			echo get_brands( $post->ID, ', ', ' <span class="posted_in">' . $labels->name . ': ', '.</span>' );
		}
	}

	/**
	 * register_shortcodes function.
	 *
	 * @access public
	 */
	function register_shortcodes() {

		add_shortcode( 'product_brand', array( $this, 'output_product_brand' ) );
		add_shortcode( 'product_brand_thumbnails', array( $this, 'output_product_brand_thumbnails' ) );
		add_shortcode( 'product_brand_thumbnails_description', array( $this, 'output_product_brand_thumbnails_description' ) );
		add_shortcode( 'product_brand_list', array( $this, 'output_product_brand_list' ) );

	}

	/**
	 * output_product_brand function.
	 *
	 * @access public
	 */
	function output_product_brand( $atts ) {
		global $post;

		extract( shortcode_atts( array(
		      'width'   => '',
		      'height'  => '',
		      'class'   => 'aligncenter',
		      'post_id' => ''
	    ), $atts ) );

	    if ( ! $post_id && ! $post )
	    	return;

		if ( ! $post_id )
			$post_id = $post->ID;

		$brands = wp_get_post_terms( $post_id, 'product_brand', array( "fields" => "ids" ) );

		$output = null;

		if ( count( $brands ) > 0 ) {

			ob_start();

			foreach( $brands as $brand ) {

				$thumbnail = get_brand_thumbnail_url( $brand );

				if ( $thumbnail ) {

					$term = get_term_by( 'id', $brand, 'product_brand' );

					if ( $width || $height ) {
						$width = $width ? $width : 'auto';
						$height = $height ? $height : 'auto';
					}


					woocommerce_get_template( 'shortcodes/single-brand.php', array(
						'term'		=> $term,
						'width'		=> $width,
						'height'	=> $height,
						'thumbnail'	=> $thumbnail,
						'class'		=> $class
					), 'woocommerce-brands', untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) ) . '/templates/' );

				}
			}
			$output = ob_get_clean();
		}

		return $output;
	}

	/**
	 * output_product_brand_list function.
	 *
	 * @access public
	 * @return void
	 */
	function output_product_brand_list( $atts ) {

		extract( shortcode_atts( array(
			'show_top_links'    => true,
			'show_empty'        => true,
			'show_empty_brands' => false
		), $atts ) );

		if ( $show_top_links === "false" )
			$show_top_links = false;

		if ( $show_empty === "false" )
			$show_empty = false;

		if ( $show_empty_brands === "false" )
			$show_empty_brands = false;

		$product_brands = array();
		$terms          = get_terms( 'product_brand', array( 'hide_empty' => ( $show_empty_brands ? false : true ) ) );

		foreach ( $terms as $term ) {

			$term_letter = substr( $term->slug, 0, 1 );

			if ( ctype_alpha( $term_letter ) ) {

				foreach ( range( 'a', 'z' ) as $i )
					if ( $i == $term_letter ) {
						$product_brands[ $i ][] = $term;
						break;
					}

			} else {
				$product_brands[ '0-9' ][] = $term;
			}

		}

		ob_start();

		woocommerce_get_template( 'shortcodes/brands-a-z.php', array(
			'terms'				=> $terms,
			'index'				=> array_merge( range( 'a', 'z' ), array( '0-9' ) ),
			'product_brands'	=> $product_brands,
			'show_empty'		=> $show_empty,
			'show_top_links'	=> $show_top_links
		), 'woocommerce-brands', untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) ) . '/templates/' );

		return ob_get_clean();
	}

	/**
	 * output_product_brand_thumbnails function.
	 *
	 * @access public
	 * @param mixed $atts
	 * @return void
	 */
	function output_product_brand_thumbnails( $atts ) {

		extract( shortcode_atts( array(
		      'show_empty' 		=> true,
		      'columns'			=> 4,
		      'hide_empty'		=> 0,
		      'orderby'			=> 'name',
		      'exclude'			=> '',
		      'number'			=> '',
		      'fluid_columns'   => false
	     ), $atts ) );

	    $exclude = array_map( 'intval', explode(',', $exclude) );
	    $order = $orderby == 'name' ? 'asc' : 'desc';

	    if ( 'true' == $show_empty ) {
	    	$hide_empty = false;
	    } else {
	    	$hide_empty = true;
	    }

		$brands = get_terms( 'product_brand', array( 'hide_empty' => $hide_empty, 'orderby' => $orderby, 'exclude' => $exclude, 'number' => $number, 'order' => $order ) );

		if ( ! $brands )
			return;

		ob_start();

		woocommerce_get_template( 'widgets/brand-thumbnails.php', array(
			'brands'	=> $brands,
			'columns'	=> $columns,
			'fluid_columns' => $fluid_columns
		), 'woocommerce-brands', untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) ) . '/templates/' );

		return ob_get_clean();
	}

	/**
	 * output_product_brand_thumbnails_description function.
	 *
	 * @access public
	 * @param mixed $atts
	 * @return void
	 */
	function output_product_brand_thumbnails_description( $atts ) {

		extract( shortcode_atts( array(
		      'show_empty' 		=> true,
		      'columns'			=> 1,
		      'hide_empty'		=> 0,
		      'orderby'			=> 'name',
		      'exclude'			=> '',
		      'number'			=> ''
	     ), $atts ) );

	    $exclude = array_map( 'intval', explode(',', $exclude) );
	    $order = $orderby == 'name' ? 'asc' : 'desc';

		$brands = get_terms( 'product_brand', array( 'hide_empty' => $hide_empty, 'orderby' => $orderby, 'exclude' => $exclude, 'number' => $number, 'order' => $order ) );

		if ( ! $brands )
			return;

		ob_start();

		woocommerce_get_template( 'widgets/brand-thumbnails-description.php', array(
			'brands'	=> $brands,
			'columns'	=> $columns
		), 'woocommerce-brands', untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) ) . '/templates/' );

		return ob_get_clean();
	}
}

$GLOBALS['WC_Brands'] = new WC_Brands();
