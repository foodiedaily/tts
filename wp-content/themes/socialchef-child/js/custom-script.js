(function($) {

	

	$(document).ready(function(){


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



