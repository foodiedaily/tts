/*global wc_single_product_params */
jQuery( function( $ ) {
	$(document).ready(function() {
		// wc_single_product_params is required to continue, ensure the object exists
		//if (typeof wc_single_product_params === 'undefined') {
		//	return false;
		//}
        //
		//// Tabs
		//$('.wc-tabs-wrapper, .woocommerce-tabs')
		//	.on('init', function () {
		//		$('.wc-tab, .woocommerce-tabs .panel:not(.panel .panel)').hide();
        //
		//		var hash = window.location.hash;
		//		var url = window.location.href;
		//		var $tabs = $(this).find('.wc-tabs, ul.tabs').first();
        //
		//		if (hash.toLowerCase().indexOf('comment-') >= 0 || hash === '#reviews') {
		//			$tabs.find('li.reviews_tab a').click();
		//		} else if (url.indexOf('comment-page-') > 0 || url.indexOf('cpage=') > 0) {
		//			$tabs.find('li.reviews_tab a').click();
		//		} else {
		//			$tabs.find('li:first a').click();
		//		}
		//	})
		//	.on('click', '.wc-tabs li a, ul.tabs li a', function () {
		//		var $tab = $(this);
		//		var $tabs_wrapper = $tab.closest('.wc-tabs-wrapper, .woocommerce-tabs');
		//		var $tabs = $tabs_wrapper.find('.wc-tabs, ul.tabs');
        //
		//		$tabs.find('li').removeClass('active');
		//		$tabs_wrapper.find('.wc-tab, .panel:not(.panel .panel)').hide();
        //
		//		$tab.closest('li').addClass('active');
		//		$tabs_wrapper.find($tab.attr('href')).show();
        //
		//		return false;
		//	})
		//	.trigger('init');

		//$('a.woocommerce-review-link').click(function () {
		//	$('.reviews_tab a').click();
		//	return true;
		//});

		// Star ratings for comments
		$('#uniform-recipe_rating').hide().before('<p class="stars"><span><a class="star-1" href="#">1</a><a class="star-2" href="#">2</a><a class="star-3" href="#">3</a><a class="star-4" href="#">4</a><a class="star-5" href="#">5</a></span></p>');
		var rating_value = 0;
		$('body')
			.on('click', '#respond p.stars a', function () {
				var $star = $(this),
					$rating = $(this).closest('#respond').find('#rating');
				$rating.val($star.text());
				rating_value = $star.text();
				$star.siblings('a').removeClass('active');
				$star.addClass('active');
				console.log(rating_value);
				return false;
			})
		$("#recipe_comments #commentform").submit(function() {

				if(rating_value > 0 && rating_value <= 5) {
					$.ajax({
						//url: baseurl + "/rating.php?rating=" + rating_value,
						url: "http://tts.dev/wp-content/themes/socialchef-child/rating.php?rating=" + rating_value,
						type: "POST",
						success: function (results) {
							//var posts = JSON.parse(results);
							//console.log(posts);
							console.log('Work.');

						},
						error: function () {
							console.log('Cannot retrieve data.');
						}
					});
				}
				return true;
				//if ($rating.size() > 0 && !rating && wc_single_product_params.review_rating_required === 'yes') {
				//	window.alert(wc_single_product_params.i18n_required_rating_text);
                //
				//	return false;
				//}
			});
	});
});
