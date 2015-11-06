(function($) {

	

	$(document).ready(function(){
		var i =0;
		$("#custom-search-1 #recipe_search_submit").on('click', function() {
			event.preventDefault();
			var search = $(".added-ingredient").map(function() {
				return $(this).val();
			}).get();
			$(".entries").empty();
		for(var l = 0; l < search.length; l++) {
			console.log(search);
			var apiKey = "Et3G368R2cTiwcH59XH9GnqK9NtjiqG7";
			var titleKeyword = search[l];
			var url = "http://api.bigoven.com/recipes?pg=1&rpp=25&title_kw="
				+ titleKeyword
				+ "&api_key=" + apiKey;
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



