(function($) {

	

	$(document).ready(function(){

		
		function headerShow(){
			if (!$("body").hasClass("home-page")) {
				$(this).children("#headID").removeClass("headHide");
				$(this).children("#headID").addClass("headShow");
			}
		};
		headerShow();	

		$(window).scroll(function() {		    
	    var scroll = $(".home-page").scrollTop();    
		    if (scroll > 0) {
		       	$("#headID").addClass("headShow");
		    	$("#headID").removeClass("headHide");
		    }else if (scroll <= 0) {
		    	$("#headID").addClass("headHide");
		    	$("#headID").removeClass("headShow");
			};
		});
	});
})(jQuery);

// if height of <body> is equal to height of window, show header

// var bodyHeight = $("body").height();
// if (bodyHeight) = 100


// || (window.location.pathname != '/tts.dev')