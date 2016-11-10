$(window).load(function(){	
/* SPECIAL NOTES:
YOU CAN CHOOSE EITHER JQUERY UI (WITH EFFECTS CORE) FOR EASING OR THE EASING PLUGIN. IF YOU TRY TO USE 
ANY EASING OTHERWISE, THE PLUGIN WILL BREAK. YOU CAN USE THE VARIABLES BELOW TO MAKE ADJUSTMENTS, EACH
ONE IS COMMENTED. YOU CAN MOVE THIS INTO A DIFFERENT JS FILE IF YOU LIKE. THE SETTINGS THAT SAY
(ONLY ON PAGE LOAD) WILL ONLY AFFECT THE 3RD BANNER ON INITIAL LOAD. THE OTHER SETTINGS WILL TAKE OVER 
WHEN YOU HOVER ICON #3. JQUERY UI CHANGES THE BEHAVIOUR OF SHOW/HIDE (YOU'LL NOTICE MORE OF AN ANIMATION 
FOR THE BIO BOXES. IF YOU USE JQUERY THEY'LL JUST FADE IN/FADE OUT.
*/
	// VARIABLES YOU CAN USE TO ADJUST THE ANIMATIONS
	var speed_in=700, // SPEED ON HOVER IN
		speed_out=700, // SPEED ON HOVER OUT
		start_opacity=1, // OPACITY ON HOVER OUT
		end_opacity=1, // OPACITY ON HOVER IN
		ease_in="swing", // SPECIAL EASING TO APPLY TO HOVER IN
		ease_out="swing", // SPECIAL EASING TO APPLY TO HOVER OUT
		pageload_delay=0, // INITIAL PAGE LOAD DELAY
		pageload_speed_in=1000, // SPEED IN ON #3 (ONLY ON PAGE LOAD)
		pageload_end_opacity=1, // FADE IN TO X ON #3 (ONLY ON PAGE LOAD)
		pageload_ease_in="easeOutBounce"; // SPECIAL EASING ON #3 (ONLY ON PAGE LOAD)
		// SECONDARY PAGES
		sec_speed_in=700,
		sec_end_opacity=1,
		sec_ease_in="swing",
		sec_delay=100,
		// INNER FLAG TEXT
		inner_speed=1200, // ADJUST THE SPEED OF INSIDE TEXT
		inner_effect="swing";
		
	// ****************************************************
	// FIRST USE JQUERY TO SET OPACITY TO 0 (QUICK CROSS BROWSER)
	$('.flagholder1, .flagholder2, .flagholder3, .flag_left').animate({'opacity':start_opacity},0);
	// SETUP INTITIAL NATIVE JS TIMER TO WAIT 2SECONDS BEFORE SLIDING IN #3
	setTimeout("$('.flagholder2').animate({'top':'0px','opacity':'"+pageload_end_opacity+"'},{duration:"+pageload_speed_in+", queue:false, specialEasing:{'top':'"+pageload_ease_in+"'}})", pageload_delay);
	// ****************************************************
	// BIND MOUSE EVENTS TO ICONS
	$('.icon01').hover(function(){
		$('.flagholder1').animate({'top':'0px','opacity':end_opacity},
		{duration:speed_in, queue:false, specialEasing:{'top':ease_in}});
		$('.flagholder2, .flagholder3').animate({'top':'-383px','opacity':start_opacity},
		{duration:speed_out, queue:false, specialEasing:{'top':ease_out}});});
	$('.icon02').hover(function(){
		$('.flagholder2').animate({'top':'0px','opacity':end_opacity},
		{duration:speed_in, queue:false, specialEasing:{'top':ease_in}});
		$('.flagholder1, .flagholder3').animate({'top':'-383px','opacity':start_opacity},
		{duration:speed_out, queue:false, specialEasing:{'top':ease_out}});});
	$('.icon03').hover(function(){
		$('.flagholder3').animate({'top':'0px','opacity':end_opacity},
		{duration:speed_in, queue:false, specialEasing:{'top':ease_in}});
		$('.flagholder1, .flagholder2').animate({'top':'-383px','opacity':start_opacity},
		{duration:speed_out, queue:false, specialEasing:{'top':ease_out}});});
	// ****************************************************
	// SECONDARY PAGES BANNER
	setTimeout("$('.flag_left').animate({'top':'0px','opacity':'"+sec_end_opacity+"'},{duration:"+sec_speed_in+", queue:false, specialEasing:{'top':'"+sec_ease_in+"'}}).find('.innerflag').animate({'top':'50px'},{duration:"+inner_speed+", queue:false, specialEasing:{'top':'"+inner_effect+"'}})", sec_delay);
	// ****************************************************
	// SECONDARY PAGES BIOS
	$('.content_right').find('.odholder img').each(function(){
		var sec_obj=$(this), // CACHE OBJECT FOR PERFORMANCE
		image_text=sec_obj.attr("alt"); // SAVE THE IMAGE ALT AS TEXT
		// CREATE THE BIO BOX AND POSITION/PLACE TEXT INSIDE
		sec_obj.parent('.odholder').prepend('<div class="biobox" style="margin-left:-70px;margin-top:95px;"><p class="biotext">'+image_text+'</div>');
	});
	// SETUP HOVER EVENT
	var old_obj
	
	$('.odholder').hover(function(){
		var sec_obj=$(this);
		sec_obj.find('.biobox').show(100);
		
	},function(){
		var sec_obj=$(this);
		sec_obj.find('.biobox').hide(0);
	});
});