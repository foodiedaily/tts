<?php
defined( 'WPINC' ) or die;
$domain = parse_url( home_url( '/' ), PHP_URL_HOST );
$token = wp_hash( 'sev-token' );
?>
<div class="wrap">
<?php if ( ! $this->get_reseller_id() ) : ?>
	<p><?php _e( '<code>GD_RESELLER</code> is not defined. Please contact support.', 'sev' ); ?></p>
<?php else : ?>
	<iframe id="sev" width="100%" height="910" src="<?php echo $this->get_integration_base(); ?>?domain=<?php echo esc_attr( $domain ); ?>&integration=WORDPRESS&token=<?php echo esc_attr( $token ); ?>&reseller_id=<?php echo $this->get_reseller_id(); ?>&language=<?php echo $this->get_locale(); ?>"></iframe>

	</div>

	<script>
	jQuery(window).focus(function(){
		jQuery('#sev').attr( 'src', function ( i, val ) { return val; });
	});
	</script>
<?php endif; ?>
