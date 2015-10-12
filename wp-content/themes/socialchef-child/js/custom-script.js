(function($) {

	

	$(document).ready(function(){


	// function headerShow() {
	// 	if  ($(".intro").length > 0)) {
	// 		$("#headID").addClass("headHide");
	//  	    $("#headID").removeClass("headShow");
	// 	}else{
	// 		$("#headID").addClass("headShow");
	// 		$("#headID").removeClass("headHide");
	// 	}
	// }
	// headerShow();
		
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
		    }else if (scroll <= 0){
		    	$("#headID").addClass("headHide");
		    	$("#headID").removeClass("headShow");
			};
		});


	});//doc ready
})(jQuery);



// if intro is showing hide header
// if intro is not showing show header