<!-- Vantage Local HTML5/JS Tag version 6.0.5, authors: MM, SH, MR, Shaggy 2013.10.10 - Interactions etc -->
<SCRIPT LANGUAGE=JavaScript>
<?php // control the scope ?>
function vl() {
	//============================================================
	//==================PUBLISHING VARIABLES======================
	//============================================================
	<?php // random for unique impression ID ?>
	var rnd = Math.floor(Math.random()*10000000000);
	<?php // operation variables ?>
	var ad = {
		clientID :"VL_30303" + "_" + rnd,	 //"VL_<?php echo $vl_creative_id;?>" + "_" + rnd,
		backup : "<?php echo $backup_image;?>",
		vlid : "engvlx4ie<?php echo sprintf('%07d', $vl_campaign_id);?>rlp",
		clickTag : ["<?php echo isset($no_engage) ? '#' : '%c%u';?>"],
		blankTarget : "<?php echo isset($no_engage) ? '' : '_blank';?>",
		isTrackingOff : "<?php echo $tracking_off ? 'true' : 'false';?>",
		<?php // vantage local amazon container URL, TODO: "MATT" change this value based on operation conditions ?>
		fileContainer : "http://s3.amazonaws.com/adnifty0/"
	};
	//============================================================
	//=====================OPTIONS VARIABLES=======================
	//============================================================
	<?php // array for creative option variables ?>
	var opt = {};
	<?php // count of important files/scripts to load for load managing ?>
	var numLoaded=0;
	<?php // requires minimum of two important scripts to load ?>
	var numToLoad=2;
	<?php // insert variables from creative_uploader as json and assign to option array ?>
	<?php //echo (isset($variables_js)) ? json_decode($variables_js) : "//variables_js not found"; ?>
	var variables = eval('('+<?php echo $variables_data_obj; ?>+')');
	opt = variables.flashvars;
	<?php // array of Amazon image files for the slides from image picker ?>
	opt.files = variables.files;
	<?php // get the widths of the images ?>
	opt.widths = [opt.files.slideImage0.width,opt.files.slideImage1.width,opt.files.slideImage2.width,opt.files.slideImage3.width];
	for(var i=0; i<opt.widths.length;i++) opt.widths[i] = parseInt(opt.widths[i], 10);
	<?php // get width of the expandable slide ?>
	opt.expandableSlideWidth = opt.widths[parseInt(opt.expandSlide)];
	<?php // get the image heights ?>
	opt.heights = [opt.files.slideImage0.height,opt.files.slideImage1.height,opt.files.slideImage2.height,opt.files.slideImage3.height];
	for(var i=0; i<opt.heights.length;i++) opt.heights[i] = parseInt(opt.heights[i], 10);
	opt.expandableSlideHeight = opt.heights[parseInt(opt.expandSlide)];
	<?php // set the ad size based off of the first slide image, TODO: change this to startSlide ?>
	ad.width = parseInt(opt.files.slideImage0.width);
	ad.height = parseInt(opt.files.slideImage0.height);
	<?php // set the optional landing page links, and using DFA campaign tracking %c ?>
	ad.clickTag[1] = '<?php echo isset($no_engage) ? "#" : "'+'%c'+opt.secondClick+'";?>';
	ad.clickTag[2] = '<?php echo isset($no_engage) ? "#" : "'+'%c'+opt.thirdClick+'";?>';
	ad.clickTag[3] = '<?php echo isset($no_engage) ? "#" : "'+'%c'+opt.fourthClick+'";?>';
	<?php // if this ad is a click to call ad, change the main landing page/clickTag to call phone number - only works after published to DFA ?>
	opt.isClickToCall = getBoolean( opt.isClickToCall );
	if ( opt.isClickToCall && opt.clickToCallNumber != "" ) {
		ad.clickTag[0] = '<?php echo isset($no_engage) ? "#" : "'+'%c'+'tel:'+ opt.clickToCallNumber+'";?>';
	}
	//console.log( opt.files );
	//============================================================
	//=========================IE SUCKS===========================
	//============================================================
	<?php // get IE version and document mode ?>
	var ua = navigator.userAgent;
	var isIE = ua.indexOf("MSIE") != -1;
	var ieVersion;
	var mode = document.documentMode ||
	((/back/i).test(document.compatMode || "") ? 5 : ieVersion) ||
	((/MSIE\s*(\d+\.?\d*)/i).test(navigator.userAgent || "") ? parseFloat(RegExp.$1, 10) : null);
	function getIEVersion(dataString) {
		var index = dataString.indexOf("MSIE");
		if (index == -1) return;
		return parseFloat(dataString.substring(index+5));
	}
	if(isIE) {
		ieVersion = getIEVersion(navigator.userAgent);
		//alert('version '+ ieVersion);
		if(ieVersion < 8) { // TODO: evaluate what versions of IE we'll support and what to do if we encounter an older version
			//alert('document.documentMode ' + document.documentMode);
			<?php // if IE 9 or less and document mode is under 7, load backup image ?>
			<?php //if(mode < 7 || !mode) { ?>
				if(ad.backup)
				{
					document.write( '<div id="'+ad.clientID+'" class="'+ad.vlid+'"><a href="'+ad.clickTag[0]+'" target="_blank" style="border:none;"><img style="border:none;" src="'+ad.backup+'"/></a></div>' );
				}
				else
				{
					document.write( '<div id="'+ad.clientID+'" class="'+ad.vlid+'"><img style="border:none;" src="https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/spec_ads/unsupported_browser.jpg"/></div>' );
				}
				return;
			<?php //} ?>
		}
	}
	<?php // determine if using Firefox browser ?>
	var isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
	opt.isFirefox = isFirefox;
	opt.ieVersion = ieVersion;
	opt.isIE = isIE;
	//console.log(opt.isDesktopSwipe);
	//============================================================
	//============================================================
	//============================================================
	<?php // convert json strings to booleans ?>
	opt.isDesktopSwipe = getBoolean( opt.isDesktopSwipe );
	opt.isCountdown = getBoolean( opt.isCountdown );
	opt.isCustomButtonCSS = getBoolean( opt.isCustomButtonCSS );
	opt.isVideo = getBoolean( opt.isVideo );
	opt.isAutoLoadVideo = getBoolean( opt.isAutoLoadVideo );
	opt.isExpandable = getBoolean(opt.isExpandable);
	opt.isRichMediaMap = getBoolean(opt.isRichMediaMap);
	opt.isDebugMode = getBoolean(opt.isDebugMode);
	opt.isHideAdUntilLoadedBugPatch = getBoolean(opt.isHideAdUntilLoadedBugPatch);
	<?php // CSS, customize the mouse cursor ?>
	if ( opt.isDesktopSwipe == true ) {
		opt.customCSS += '.vl-preload-image, .vl-slide-image{cursor:url('+ ad.fileContainer + opt.files.hoverCursor.key +'),url('+ ad.fileContainer + opt.files.hoverCursor.key +'),auto;}.vl-preload-image:active, .vl-slide-image:active {cursor:url('+ ad.fileContainer + opt.files.draggingCursor.key +'),url('+ ad.fileContainer + opt.files.draggingCursor.key +'),auto;}';
	} else {
		opt.customCSS += '.vl-preload-image, .vl-slide-image{cursor:pointer;}.vl-preload-image:active, .vl-slide-image:active{cursor:pointer;}';
	}
	<?php // if using youtube video with auto-load on an expandable ad, hide the ad until the youtube is done loading to prevent user from clicking - TODO: prevent slider/video fail if clicked while loading in Vantage/swipe_mod ?>
	<?php // ad will be returned to display:block in vantage.js on youtube setup complete ?> 
	if ( opt.isVideo == true && opt.videoPlayer == "youtube" && opt.isExpandable && opt.isAutoLoadVideo && !isIE && opt.isHideAdUntilLoadedBugPatch ) {
		opt.customCSS += '.adbox{display:none;}';
	}
	<?php // CSS, setup custom button colors (optional setting) ?>
	if ( opt.isCustomButtonCSS == true ) {
		var buttonColor = [opt.buttonColor1,opt.buttonColor2,opt.buttonColor3,opt.buttonColor4];
		var hoverButtonColor = [opt.hoverButtonColor1,opt.hoverButtonColor2,opt.hoverButtonColor3,opt.hoverButtonColor4];
		for( var i=0; i <= parseInt(opt.numButtons)-1; i++ ){
			opt.customCSS += '.vl-button.clickTag-'+i+'{background-color:'+buttonColor[i]+';}.vl-button.clickTag-'+i+':hover {background-color:'+hoverButtonColor[i]+';-webkit-transition: background-color 500ms linear; -moz-transition: background-color 500ms linear; -o-transition: background-color 500ms linear; -ms-transition: background-color 500ms linear; transition: background-color 500ms linear;}';
		}
	}
	<?php // default CSS for slider ?>
	var styleCSS = '.adbox{-moz-user-select: none !important; -khtml-user-select: none !important; user-select: none !important;}.vl-slide-image, .vl-preload-image{user-drag: none !important; -webkit-user-drag: none !important;-webkit-touch-callout: none !important;}.vl-button{cursor:pointer}.vl-countdown{-webkit-user-drag: none;}.swipe {overflow: hidden;visibility: hidden;position: relative} .swipe-wrap {overflow: hidden;position: relative;}.swipe-wrap > div {float:left;position: relative;}@-webkit-keyframes rotation { from {-webkit-transform: rotate(0deg);} to {-webkit-transform: rotate(359deg);}} @-moz-keyframes rotation { from {transform: rotate(0deg);} to {transform: rotate(359deg);}} @-o-keyframes rotation { from {transform: rotate(0deg);} to {transform: rotate(359deg);}} @keyframes rotation { from {transform: rotate(0deg);} to {transform: rotate(359deg);}}.vl-button img {display:block;margin-top:2px;}.vl-facebook,.vl-linkedin,.vl-twitter,.vl-google,.vl-mail {width:21px;height:21px;float:left;margin:2px;background-color:'+opt.socialColor+';-webkit-border-radius: 2px;-moz-border-radius: 2px;border-radius: 2px;}.vl-twitter:hover {background-color:#00abf0;-webkit-transition: background-color 500ms linear; -moz-transition: background-color 500ms linear; -o-transition: background-color 500ms linear; -ms-transition: background-color 500ms linear; transition: background-color 500ms linear;}.vl-facebook:hover {background-color:#3b559f;-webkit-transition: background-color 500ms linear; -moz-transition: background-color 500ms linear; -o-transition: background-color 500ms linear; -ms-transition: background-color 500ms linear; transition: background-color 500ms linear;}.vl-linkedin:hover {background-color:#0177b5;-webkit-transition: background-color 500ms linear; -moz-transition: background-color 500ms linear; -o-transition: background-color 500ms linear; -ms-transition: background-color 500ms linear; transition: background-color 500ms linear;}.vl-google:hover {background-color:#b13021;-webkit-transition: background-color 500ms linear; -moz-transition: background-color 500ms linear; -o-transition: background-color 500ms linear; -ms-transition: background-color 500ms linear; transition: background-color 500ms linear;}.vl-mail:hover {background-color:#007236;-webkit-transition: background-color 500ms linear; -moz-transition: background-color 500ms linear; -o-transition: background-color 500ms linear; -ms-transition: background-color 500ms linear; transition: background-color 500ms linear;}.vl-slidenavbtn{width:70px;height:23px;float:left;margin:2px;background-color:'+opt.slidenavbtncolor+';-webkit-border-radius: 2px;-moz-border-radius: 2px;border-radius: 2px;}.vl-slidenavbtn:hover {background-color:'+opt.slidenavbtnhovercolor+';-webkit-transition: background-color 500ms linear; -moz-transition: background-color 500ms linear; -o-transition: background-color 500ms linear; -ms-transition: background-color 500ms linear; transition: background-color 500ms linear;}.vl-activeslidenavbtn{width:70px;height:23px;float:left;margin:2px;background-color:'+opt.slidenavbtnselectedcolor+';-webkit-border-radius: 2px;-moz-border-radius: 2px;border-radius: 2px;}.vl-ctaslidenavbtn{width:70px;height:23px;float:left;margin:2px;background-color:'+opt.slidenavbtnctacolor+';-webkit-border-radius: 2px;-moz-border-radius: 2px;border-radius:2px;}'+ opt.customCSS;
	<?php // html string to contain the slides ?>
	var slides="";
	//var slideImages = [opt.files.slideImage0.key,opt.files.slideImage1.key,opt.files.slideImage2.key,opt.files.slideImage3.key];
	var slideImages =[null, null, null, null];
	if(opt.files.slideImage0)
	{
	    slideImages[0] = opt.files.slideImage0.key;
	}
	if(opt.files.slideImage1)
	{
	    slideImages[1] = opt.files.slideImage1.key;
	}
	if(opt.files.slideImage2)
	{
	    slideImages[2] = opt.files.slideImage2.key;
	}
	if(opt.files.slideImage3)
	{
	    slideImages[3] = opt.files.slideImage3.key;
	}
	var buttons = [opt.files.buttonImage0.key,opt.files.buttonImage1.key,opt.files.buttonImage2.key,opt.files.buttonImage3.key];
	var specadbackgroundcolor = "#BBBBBB";
	if(opt.specadbackgroundcolor){
	    specadbackgroundcolor = opt.specadbackgroundcolor;
	} 
	<?php // total number of slides ?>
	opt.numSlides = parseInt(opt.numSlides);
	<?php // creative UI starts at "1" instead of "0" for UX ?>
	opt.expandSlide = parseInt(opt.expandSlide)-1;
	//console.log("numSlides: "+opt.numSlides);
	<?php // loop through the number of slides and create the HTML based on options ?>
	for( var num=0; num < opt.numSlides; num++ ) {
		//console.log( "num: "+ num );
		if( num == 0 && opt.isExpandable == true && num == opt.expandSlide-1 ) {
			<?php // create a clear slide that user can click to expand the ad, if this ad expands to slide 2, for a cooler slide transition effect ?>
			slides += '<div id="'+ad.clientID+'_slide_'+num+'" class="vl-slide-image" style="width:'+ad.width+'px;height:'+ad.height+'px;" onclick="'+ad.clientID+'_adbox.'+'clickExpand();"></div>';
		} else {
			<?php // create each slide div and divs for potential rich media options ?>
			slides	+=	'<div id="'+ad.clientID+'_slide_'+num+'" style="width:'+ad.width+'px;height:'+ad.height+'px;background-color:'+specadbackgroundcolor+';" onclick="'+ad.clientID+'_adbox.'+'clickHandler(0); '+ad.clientID+'_adbox.'+'onEnrollButtonClick();">'
							+'<div id="'+ad.clientID+'_video_'+num+'"></div>'
							+'<div id="'+ad.clientID+'_map_'+num+'"></div>'
							+'<div id="'+ad.clientID+'_social_'+num+'" class="vl-social"></div>';
			if( num == parseInt(opt.expandSlide) && opt.isExpandable == true ) {
				<?php // create multiple landing page buttons for first expandable slide ?>
				slides	+=	'<div id="'+ad.clientID+'_button_wrapper" style="position:absolute;left:'+opt.buttonWrapperLeft+'px;top:'+opt.buttonWrapperTop+'px;">';
				for( var i=parseInt(opt.numButtons)-1; i >= 0; i-- ){
					slides += '<div class="vl-button clickTag-'+i+'" onclick="'+ad.clientID+'_adbox.'+'clickHandler('+i+');"><img src="'+ ad.fileContainer + buttons[i] +'" /></div>';
				}
				slides += '</div>';
			}
			if ( num == opt.expandSlide-1 && opt.isExpandable == true ) {
				<?php // create a slide that user can click to expand the ad, if this ad expands on a slide other than slide 2 ?> 
				slides += '<img id="'+ad.clientID+'_slide_image_'+num+'" class="vl-slide-image" src="'+ ad.fileContainer + slideImages[num] +'?v='+rnd+'" onclick="'+ad.clientID+'_adbox.'+'clickExpand();"/></div>';
			} else {
				<?php // create the image tags and make them clickable to the main landing page ?>
				if(slideImages[num])
				{
				    slides += '<img id="'+ad.clientID+'_slide_image_'+num+'" class="vl-slide-image" src="'+ ad.fileContainer + slideImages[num] +'?v='+rnd+'" onclick="'+ad.clientID+'_adbox.'+'clickHandler(0); '+ad.clientID+'_adbox.'+'onEnrollButtonClick();"/>'
				}
				//Image Div?
				//console.log(opt.useSpecLogo);
				if(opt.useSpecLogo == 'true'){
				    var speclogo = ad.fileContainer + opt.files.specadimage.key;
				    //console.log(speclogo);
						slides += '<div id="'+ad.clientID+'_specimage_'+num+'" onclick="'+ad.clientID+'_adbox.'+'onEnrollButtonClick();" style="width:'+(ad.width-20)+'px;position:absolute;left:10px;top:0px;height:'+opt.speclogoheight+'px;text-align:'+opt.speclogoalign+';padding-'+opt.speclogalalign+':10px;">';
				    slides += '<img style="height:'+opt.speclogoheight+'px;" src="'+speclogo+'">';

				    slides += "</div>";
				//End Image Div
				}
				//NAV BAR
				
				if(opt.useNavbar == 'true'){
				slides += '<div id="'+ad.clientID+'_nav_'+num+'" style="width:'+ad.width+'px;height:28px;position:absolute;top:'+(ad.height-28)+'px;" >'
				 var cta_url = "https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/spec_ads/cta_visit_us.png";
				 
				 if(opt.files.specadctabtn)
				 {
				    if (opt.files.specadctabtn.key){
							var cta_url = ad.fileContainer + opt.files.specadctabtn.key;
				    }
				 }

				 
			        slides += '<div id="VL_30303_7516434590_navbar-holder" class="vl-social-holder" style="width:'+ad.width+'px;position:absolute;left:2px;z-index:1; cursor: pointer;">\n\
			    <img id="'+ad.clientID+'_nav_'+num+'_0" class="vl-slidenavbtn" src="https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/spec_ads/video.png" onclick="'+ad.clientID+'_adbox.'+'slider.slide(0, 300)">\n\
			    <img id="'+ad.clientID+'_nav_'+num+'_1" class="vl-slidenavbtn" src="https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/spec_ads/map.png" onclick="'+ad.clientID+'_adbox.'+'slider.slide(1, 300)">\n\
			    <img id="'+ad.clientID+'_nav_'+num+'_2" class="vl-slidenavbtn" src="https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/spec_ads/social.png" onclick="'+ad.clientID+'_adbox.'+'slider.slide(2, 300)">\n\
			    <img id="'+ad.clientID+'_nav_'+(num+1)+'_CTA" class="vl-ctaslidenavbtn" src="'+cta_url+'" onclick="'+ad.clientID+'_adbox.'+'onEnrollButtonClick();"></div>';
				
				
				slides += '</div>';
				} 

				slides+= '</div>';
			}
		}
	}
	<?php // main ad div ?>
	var adBox = '<div id="'+ad.clientID+'_adbox" class="adbox '+ad.vlid+'" style="position:relative;z-index:444444;">'
				+	'<div id="'+ad.clientID+'_countdown"></div>';
	if ( opt.isExpandable == true && opt.expandSlide == 1 ) {
		<?php // if expandable on slide 2, make a fake first image for a cooler slide transition effect ?>
		adBox +=	'<div id="'+ad.clientID+'_preload" onclick="'+ad.clientID+'_adbox.'+'clickExpand();" class="vl-preload-image"><img src="'+ ad.fileContainer + slideImages[0] +'"/></div>';
	}
	//console.log(slides);
	<?php // create swipe slider html ?>
	adBox +=	'<div id="'+ad.clientID+'_slider" style="position:absolute;top:0px;left:0px;" class="swipe">'
			+		'<div id="'+ad.clientID+'_swipe" class="swipe-wrap">'
			+			slides
			+		'</div>'
			+	'</div>'  
			+'</div>';
	<?php // if debugmode, create next/previous buttons for fast navigation/testing ?>
	if( opt.isDebugMode == true ){
		adBox +='<div class="slider-nav" style="position:relative;z-index:444445;"><button onclick="'+ad.clientID+'_adbox.'+'slider.prev()">prev</button><button onclick="'+ad.clientID+'_adbox.'+'slider.next();">next</button></div>';
	}
	<?php // append the CSS to the page header ?>
	var header = document.getElementsByTagName('head')[0],
		style = document.createElement('style');
	style.type = 'text/css';
	if (style.styleSheet){
	  style.styleSheet.cssText = styleCSS;
	} else {
	  style.appendChild(document.createTextNode(styleCSS));
	}
	header.appendChild(style);			
	//document.write( styleCSS );
	<?php // write the ad to the page ?>
	document.write( adBox );
	
	<?php // script and CSS loader for the load manager ?>
	function loadScript( file, type, title ) {
		//console.log( "blob: " + "loadScript: " + file );
		if ( type == 'js' ){
			var script = document.createElement( 'script' );
			script.type = 'text/javascript';
			script.src = file;
			if (script.readyState){
				script.onreadystatechange = function(){
					if (script.readyState == "loaded" ||
							script.readyState == "complete"){
						script.onreadystatechange = null;
						loadHandler( title );
					}
				};
			} else {
				script.onload = function(){
					loadHandler( title );
				};
			}
			var firstScriptTag = document.getElementsByTagName( 'script' )[0];
			firstScriptTag.parentNode.insertBefore( script, firstScriptTag );
		} else if ( type == 'css' ){
			var script = document.createElement( 'link' );
			script.rel = 'stylesheet';
			script.type = 'text/css';
			script.title = title;
			//script.media = "screen";
			//script.charset = "UTF-8";
			script.href = file;
			document.getElementsByTagName("head")[0].appendChild(script);
		}
	}
	<?php // determine which scripts to load based on the ad options ?>
	if ( opt.videoPlayer == "videojs" ) {
		numToLoad++;
		loadScript ( 'https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/js/videojs/vlvideo_1.5s.css', 'css', "Video JS" );
		loadScript ( 'https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/js/videojs/video.js', 'js', "videojs" );
	} else if ( opt.videoPlayer == "jwplayer" || opt.videoPlayer == "jwplayerpro" ) {
		numToLoad++;
		loadScript( 'http://jwpsrv.com/library/UQS8Tv7GEeKCjyIACusDuQ.js', 'js', "jwplayer" ); 
	}
	<?php // load the two required scripts ?>
	loadScript ( 'https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/js/swipejs/swipe_mod_5.4.js', 'js', "swipejs" );
	loadScript ( opt.vantageURL, 'js', "vantage" );
	//loadScript ( '/misc_external/cdn/swipe_ad/vantage.js', 'js', "swipejs" );
	//loadScript ( '/misc_external/cdn/swipe_ad/swipe_mod.js', 'js', "swipejs" );
	//loadScript ( 'http://localhost:8888/assets/swipe_mod.js?='+rnd, 'js', "swipejs" );
	
	<?php // load manager waits for required scripts to be loaded before launching the ad ?>
	function loadHandler( name ) {
		numLoaded++;
		//console.log( "blob: loadHandler: numToLoad: " + numToLoad + " numLoaded: " + numLoaded + " name: " + name );
		if ( numLoaded == numToLoad ) {
			powerUp();
		}
	}
	<?php // starts the ad ?>
	function powerUp () {
		//console.log( "blob: powerUp: " );
		window[ad.clientID + "_adbox"] = new Vantage( ad, opt );
	}
	<?php // callback function that the youtube script calls once it is loaded ?>
	this.onYouTubeIframeAPIReady = function() {
		//console.log("blob: onYouTubeIframeAPIReady:");
		window[ad.clientID + "_adbox"].launchYouTube();	
	}
	function getBoolean(value) {
		if (value == false) {
			return false;
		}
		if (value == true) {
			return true;
		}
		if (value == "true") {
			return true;
		} else {
			return false;
		}
	}

	window[ad.clientID + "_vl_map_ready"] = function()
	{
		window[ad.clientID + "_adbox"].setupMap();
	}

}
<?php // required callback function for google map, in order to load the google map script using our loader ?>
function mapReady(){
	//console.log( "blob: mapReady: " );
}

vl();
</script>
