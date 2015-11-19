(function($) {


	function loadMore(alreadyloading, search, number_of_fails) {
		if (alreadyloading == false) {
			alreadyloading = true;
			nextpage++;
			for(var l = 0; l < search.length; l++) {
				var apiKey = "Et3G368R2cTiwcH59XH9GnqK9NtjiqG7";
				var titleKeyword = search[l];
				console.log(nextpage);
				var url = "http://api.bigoven.com/recipes?pg=" + nextpage + "&rpp=100&title_kw="
					+ titleKeyword
					+ "&api_key=" + apiKey;
				//console.log(titleKeyword);
				$.ajax({
					type: "GET",
					dataType: 'json',
					cache: false,
					url: url,
					success: function (data) {
						//data.Results.each(function() {
						//	console.log("heelo");
						//});
						var recipe_number = 0;
						for (var i = 0; i < data.Results.length; i++) {
							if (data.Results[i].ImageURL != "http://redirect.bigoven.com/pics/recipe-no-image.jpg") {
								recipe_number++;
							}
						}
						console.log(number_of_fails);
						if(number_of_fails == 5 ) {
							console.log("failed too many times");
						}
						else if(recipe_number == 0) {
							number_of_fails++;
							alreadyloading = false;
							nextpage++;
							loadMore(alreadyloading, search, number_of_fails);
						} else {
							number_of_fails = 0;
							for (var i = 0; i < data.Results.length; i++) {
								if (data.Results[i].ImageURL != "http://redirect.bigoven.com/pics/recipe-no-image.jpg") {
									var html = '<div class="entry one-fourth recipe-item">' +
										'<figure><img src="' + data.Results[i].ImageURL + '" alt="' + data.Results[i].Title + '">' +
										'<figcaption><a href="http://tts.dev/recipe/?recipe_id=' + data.Results[i].RecipeID + '" target="_blank"><i class="ico eldorado_eyelash"></i> <span>View recipe</span></a></figcaption>' +
										'</figure><div class="container" style="height: 131px;"><h2> ' +
										'<a href="http://tts.dev/recipe/?recipe_id=' + data.Results[i].RecipeID + '" target="_blank">' + data.Results[i].Title + '</a></h2> ' +
										'<div class="actions"><div> ' +
										'<div class="difficulty"><i class="ico i-moderate"></i> moderate</div>' +
										'<!-- <div class="likes"><i class="ico i-likes"></i><a href="#">10</a></div>-->' +
										'</div></div></div></div>';
									$('.entries').append(html);
								}
							}
						}
					},
					error: function  (xhr, ajaxOptions, thrownError) {
						console.log(xhr.status);
						console.log(thrownError);
					}
				});
			}

		}
	}
	var nextpage = 2;

	$(window).scroll(function() {
		var number_of_fails = 0;
		if ($('body').height() <= ($(window).height() + $(window).scrollTop())) {
			var alreadyloading = false;
			var search = $(".added-ingredient").map(function() {
				return $(this).val();
			}).get();
			loadMore(alreadyloading, search, number_of_fails);

		}
	});



	$(document).ready(function(){
		$('#fes-upload-form-recipe').submit(function(){
			$(".ingredient_quantity").each(function() {
				var val = $(this).val();
				if(val.indexOf('/') === -1)
				{
					console.log("no dash found.");
				} else {
					val = val.split('/');
					val = val[0]/val[1];
					val = val.toFixed(2);
					$(this).val(val);
				}
			});
			return true;
		});

		$("#delete_recipe").on('click', function() {
			event.preventDefault();
			var id = $("#fesid").val();
			console.log(id);
			var url_to_delete = "http://tts.dev/wp-content/themes/socialchef-child/delete_recipe.php?id=" + id;
			$.ajax({
				type: "POST",
				url: url_to_delete,
				success: function (data) {
					//var post = JSON.parse(data);
					//console.log(post);
					window.location.replace("http://tts.dev/");
				},
				error: function (xhr, ajaxOptions, thrownError) {
					console.log(xhr.status);
					console.log(thrownError);
				}
			});
		});


		var i =0;

		$("#custom-search-1 #recipe_search_submit").on('click', function() {
			event.preventDefault();
			var nextpage = 2;
			var search = $(".added-ingredient").map(function() {
				return $(this).val();
			}).get();
			$(".entries").empty();
			console.log(search);
			var url2 = "http://tts.dev/wp-content/themes/socialchef-child/ingredient-search.php?";
			for(var l = 0; l < search.length; l++) {
				url2 += "ingredients=" + search[l] + "&";
				}
				console.log(url2);
				$.ajax({
					type: "GET",
					url: url2,
					success: function (data) {
						var post = JSON.parse(data);
						console.log(post);
						for (var i = 0; i < post.length; i++) {
							var html = '<div class="entry one-fourth recipe-item">' +
								'<figure>' + post[i].image +
								'<figcaption><a href="' + post[i].guid + '" target="_blank"><i class="ico eldorado_eyelash"></i> <span>View recipe</span></a></figcaption>' +
								'</figure><div class="container" style="height: 131px;"><h2> ' +
								'<a href="' + post[i].guid + '" target="_blank">' + post[i].post_title + '</a></h2> ' +
								'<div class="actions"><div> ' +
								'<div class="difficulty"><i class="ico i-moderate"></i> moderate</div>' +
									'<div class="comments"><i class="ico eldorado_comment_baloon"></i><a href="' + post[i].guid + '#comments">' + post[i].comment_count + '</a></div>' +
								'<!-- <div class="likes"><i class="ico i-likes"></i><a href="#">10</a></div>-->' +
								'</div></div></div></div>';
							$('.entries').prepend(html);
						}
					},
					error: function (xhr, ajaxOptions, thrownError) {
						console.log(xhr.status);
						console.log(thrownError);
					}
				});

		for(var l = 0; l < search.length; l++) {
			var apiKey = "Et3G368R2cTiwcH59XH9GnqK9NtjiqG7";
			var titleKeyword = search[l];
			var url = "http://api.bigoven.com/recipes?pg=1&rpp=25&title_kw="
				+ titleKeyword
				+ "&api_key=" + apiKey;
			//console.log(titleKeyword);
			$.ajax({
				type: "GET",
				dataType: 'json',
				cache: false,
				url: url,
				success: function (data) {
					//data.Results.each(function() {
					//	console.log("heelo");
					//});
					console.log(data.Results[2]);

					for (var i = 0; i < data.Results.length; i++) {
						if(data.Results[i].ImageURL != "http://redirect.bigoven.com/pics/recipe-no-image.jpg") {
							var html = '<div class="entry one-fourth recipe-item">' +
								'<figure><img src="' + data.Results[i].ImageURL + '" alt="' + data.Results[i].Title + '">' +
								'<figcaption><a href="http://tts.dev/recipe/?recipe_id=' + data.Results[i].RecipeID + '" target="_blank"><i class="ico eldorado_eyelash"></i> <span>View recipe</span></a></figcaption>' +
								'</figure><div class="container" style="height: 131px;"><h2> ' +
								'<a href="http://tts.dev/recipe/?recipe_id=' + data.Results[i].RecipeID + '" target="_blank">' + data.Results[i].Title + '</a></h2> ' +
								'<div class="actions"><div> ' +
								'<div class="difficulty"><i class="ico i-moderate"></i> moderate</div>' +
								'<!-- <div class="likes"><i class="ico i-likes"></i><a href="#">10</a></div>-->' +
								'</div></div></div></div>';
							$('.entries').append(html);
						}
					}
				},
				error: function  (xhr, ajaxOptions, thrownError) {
					console.log(xhr.status);
					console.log(thrownError);
				}
			});
		}
		});

		console.log("IT works");

	function headerShow() {
		var url = window.location.href;
		if (url.split("/").length >= 5) {
			$("#headID").addClass("headShowNoTransitionShow");
			$("#headID").removeClass("headHide");
		}
	}
	headerShow();
		
	// function headerShow(){
	// 	if (!$("body").hasClass("home-page")) {
	// 		$(this).children("#headID").removeClass("headHide");
	// 		$(this).children("#headID").addClass("headShow");
	// 	}
	// };
	// headerShow();
	

		$(window).scroll(function() {		    
			var url = window.location.href;
	    var scroll = $(".home-page").scrollTop();    
		    if (scroll > 0) {
		       	$("#headID").addClass("headShow");
		    	$("#headID").removeClass("headHide");
		     } else if ((scroll <= 0) && (url.split("/").length < 5)){
		    	$("#headID").addClass("headHide");
		    	$("#headID").removeClass("headShow");
		  
			}
		});


	});//doc ready
})(jQuery);



