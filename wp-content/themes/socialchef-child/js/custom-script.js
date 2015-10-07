
jQuery(document).ready(function(){

console.log ('working');

jQuery(window).scroll(function(){
    if (jQuery(window).scrollTop() > 10){
    	console.log ('scroll');
        jQuery('.head').addClass('head-drop-in');
    }else{
    	jQuery('.head').removeClass('head-drop-in');
    }
 });

jQuery(body).click(function(){
	jQuery('.head').addClass('head-drop-in');
});

});