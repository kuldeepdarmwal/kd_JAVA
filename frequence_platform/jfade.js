$(function($) {
	$.fn.jfade = function(settings) {
   
	var defaults = {
		start_opacity: "1",
		high_opacity: "1",
		low_opacity: ".5",
		timing: "500",
		border: "false",
		margin: 0,
		borderwidth: 1
	};
	var settings = $.extend(defaults, settings);
	settings.element = $(this);
	settings.marginadjust = settings.margin-settings.borderwidth
			
	//set opacity to start
	$(settings.element).css("opacity",settings.start_opacity);
	//mouse over
	$(settings.element).hover(
	
		//mouse in
		function () {												  
			$(this).stop().animate({opacity: settings.high_opacity}, settings.timing); //100% opacity for hovered object
			$(this).siblings().stop().animate({opacity: settings.low_opacity}, settings.timing); //dimmed opacity for other objects
			if(settings.border == 'true') {$(this).css("border", "solid 1px #CCC"); $(this).css("margin", settings.marginadjust);}
			$(this).find('.pictext').slideToggle();	
		},
		
		//mouse out
		function () {
			$(this).stop().animate({opacity: settings.start_opacity}, settings.timing); //return hovered object to start opacity
			$(this).siblings().stop().animate({opacity: settings.start_opacity}, settings.timing); // return other objects to start opacity
			if(settings.border == 'true') {$(this).css("border", "0px"); $(this).css("margin", settings.margin);}
			$(this).find('.pictext').slideToggle();
		}
	);
	return this;
	}
	
})(jQuery);