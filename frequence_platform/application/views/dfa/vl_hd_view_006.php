<!-- Vantage Local HTML5/JS Tag version 5.0.5, authors: MM, SH, MR, Shaggy 2013.09.10 - Interactions etc -->
<SCRIPT LANGUAGE=JavaScript>
function vl() {
	//============================================================
	//==================PUBLISHING VARIABLES======================
	//============================================================
	var rnd = Math.floor(Math.random()*10000000000);
	var ad = {
		clientID :"VL_30303" + "_" + rnd,	 //"VL_<?php echo $vl_creative_id;?>" + "_" + rnd,
		backup : "<?php echo $backup_image;?>",
		vlid : "engvlx4ie<?php echo sprintf('%07d', $vl_campaign_id);?>rlp",
		clickTag : ["<?php echo isset($no_engage) ? '#' : '%c%u';?>","http://www.ramtrucks.com/en/lineup/","http://www.ramtrucks.com/en/buyers_tools/","http://www.ramtrucks.com/en/towing_guide/"],
		blankTarget : "<?php echo isset($no_engage) ? '' : '_blank';?>",
		isTrackingOff : "<?php echo $tracking_off ? 'true' : 'false';?>",
		fileContainer : "http://s3.amazonaws.com/adnifty0/"
	};
	//============================================================
	//=====================OPTIONS VARIABLES=======================
	//============================================================
	var opt = {};
	var numLoaded=0;
	var numToLoad=2;
	<?php //echo (isset($variables_js)) ? json_decode($variables_js) : "//variables_js not found"; ?>
	var variables = eval('('+<?php echo $variables_data_obj; ?>+')');
	opt = variables.flashvars;
	opt.files = variables.files;
	opt.widths = [opt.files.slideImage0.width,opt.files.slideImage1.width,opt.files.slideImage2.width,opt.files.slideImage3.width];
	opt.expandableSlideWidth = opt.widths[parseInt(opt.expandSlide)];
	opt.heights = [opt.files.slideImage0.height,opt.files.slideImage1.height,opt.files.slideImage2.height,opt.files.slideImage3.height];
	opt.expandableSlideHeight = opt.heights[parseInt(opt.expandSlide)];
	ad.width = opt.files.slideImage0.width;
	ad.height = opt.files.slideImage0.height;
	//console.log( "opt.expandableSlideHeight: " + opt.expandableSlideHeight );
	//============================================================
	//=========================IE SUCKS===========================
	//============================================================
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
		if(ieVersion < 10) {
			//alert('document.documentMode ' + document.documentMode);
			if(mode < 7 || !mode) {
				document.write( '<div id="'+ad.clientID+'" class="'+ad.vlid+'"><a href="'+ad.clickTag[0]+'" target="_blank" style="border:none;"><img style="border:none;" src="'+ad.backup+'"/></a></div>' );
				return;
			}
		}
	}
	opt.ieVersion = ieVersion;
	opt.isIE = isIE;
	//============================================================
	//============================================================
	//============================================================
	var styleCSS = '<style>.swipe {overflow: hidden;visibility: hidden;position: relative} .swipe-wrap {overflow: hidden;position: relative;}.swipe-wrap > div {float:left;position: relative;}@-webkit-keyframes rotation { from {-webkit-transform: rotate(0deg);} to {-webkit-transform: rotate(359deg);}} @-moz-keyframes rotation { from {transform: rotate(0deg);} to {transform: rotate(359deg);}} @-o-keyframes rotation { from {transform: rotate(0deg);} to {transform: rotate(359deg);}} @keyframes rotation { from {transform: rotate(0deg);} to {transform: rotate(359deg);}}.vl-button img {display:block;margin-top:2px;}.vl-facebook,.vl-linkedin,.vl-twitter,.vl-google,.vl-mail {width:21px;height:21px;float:left;margin:2px;background-color:'+opt.socialColor+';-webkit-border-radius: 2px;-moz-border-radius: 2px;border-radius: 2px;}.vl-twitter:hover {background-color:#00abf0;-webkit-transition: background-color 500ms linear; -moz-transition: background-color 500ms linear; -o-transition: background-color 500ms linear; -ms-transition: background-color 500ms linear; transition: background-color 500ms linear;}.vl-facebook:hover {background-color:#3b559f;-webkit-transition: background-color 500ms linear; -moz-transition: background-color 500ms linear; -o-transition: background-color 500ms linear; -ms-transition: background-color 500ms linear; transition: background-color 500ms linear;}.vl-linkedin:hover {background-color:#0177b5;-webkit-transition: background-color 500ms linear; -moz-transition: background-color 500ms linear; -o-transition: background-color 500ms linear; -ms-transition: background-color 500ms linear; transition: background-color 500ms linear;}.vl-google:hover {background-color:#b13021;-webkit-transition: background-color 500ms linear; -moz-transition: background-color 500ms linear; -o-transition: background-color 500ms linear; -ms-transition: background-color 500ms linear; transition: background-color 500ms linear;}.vl-mail:hover {background-color:#007236;-webkit-transition: background-color 500ms linear; -moz-transition: background-color 500ms linear; -o-transition: background-color 500ms linear; -ms-transition: background-color 500ms linear; transition: background-color 500ms linear;}'+ opt.customCSS +'</style>';
	var slides;
	var slideImages = [opt.files.slideImage0.key,opt.files.slideImage1.key,opt.files.slideImage2.key,opt.files.slideImage3.key];
	var buttons = [opt.files.buttonImage0.key,opt.files.buttonImage1.key,opt.files.buttonImage2.key,opt.files.buttonImage3.key]
	opt.numSlides = parseInt(opt.numSlides);
	opt.expandSlide = parseInt(opt.expandSlide)-1;
	//console.log("numSlides: "+opt.numSlides);
	for( var num=0; num < opt.numSlides; num++ ) {
		//console.log( "num: "+ num );
		if( num == 0 && opt.isExpandable == "true" && num == opt.expandSlide-1 ) {
			slides += '<div id="'+ad.clientID+'_slide_'+num+'" style="width:'+ad.width+'px;height:'+ad.height+'px;cursor:pointer;" onclick="'+ad.clientID+'_adbox.'+'clickExpand();"></div>';
		} else {
			slides	+=	'<div id="'+ad.clientID+'_slide_'+num+'">'
							+'<div id="'+ad.clientID+'_video_'+num+'"></div>'
							+'<div id="'+ad.clientID+'_map_'+num+'"></div>'
							+'<div id="'+ad.clientID+'_social_'+num+'" class="vl-social"></div>';
			if( num == parseInt(opt.expandSlide) && opt.isExpandable == "true" ) {
				slides	+=	'<div id="'+ad.clientID+'_button_wrapper" style="position:absolute;left:'+opt.buttonWrapperLeft+'px;top:'+opt.buttonWrapperTop+'px;cursor: pointer;">';
				for( var i=parseInt(opt.numButtons)-1; i >= 0; i-- ){
					slides += '<div class="vl-button" onclick="'+ad.clientID+'_adbox.'+'clickHandler('+i+');"><img src="'+ ad.fileContainer + buttons[i] +'" /></div>';
				}
				slides += '</div>';
			}
			if ( num == opt.expandSlide-1 && opt.isExpandable == "true" ) {
				slides += '<img id="'+ad.clientID+'_slide_image_'+num+'" src="'+ ad.fileContainer + slideImages[num] +'" style="cursor:pointer;" onclick="'+ad.clientID+'_adbox.'+'clickExpand();"/></div>';
			} else {
				slides += '<img id="'+ad.clientID+'_slide_image_'+num+'" src="'+ ad.fileContainer + slideImages[num] +'" style="cursor:pointer;" onclick="'+ad.clientID+'_adbox.'+'clickHandler(0);"/></div>';
			}
		}
	}
	var adBox = '<div id="'+ad.clientID+'_adbox" class="adbox '+ad.vlid+'" style="position:relative;z-index:444444;">'
				+	'<div id="'+ad.clientID+'_countdown"></div>';
	if ( opt.isExpandable == "true" ) {
		adBox +=	'<div id="'+ad.clientID+'_preload" onclick="'+ad.clientID+'_adbox.'+'clickExpand();" style="cursor:pointer;"><img src="'+ ad.fileContainer + opt.files.slideImage0.key +'"/></div>';
	}
	adBox +=	'<div id="'+ad.clientID+'_slider" style="position:absolute;top:0px;left:0px;" class="swipe">'
			+		'<div id="'+ad.clientID+'_swipe" class="swipe-wrap">'
			+			slides
			+		'</div>'
			+	'</div>'  
			+'</div>';
	if( opt.isDebugMode = "true" ){
		adBox +='<div><button onclick="'+ad.clientID+'_adbox.'+'slider.prev()">prev</button><button onclick="'+ad.clientID+'_adbox.'+'slider.next();">next</button></div>';
	}
				
	document.write( styleCSS );
	document.write( adBox );

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
	if ( opt.isRichMediaMap == "true" ) {
		numToLoad++;
		loadScript ( 'https://maps.googleapis.com/maps/api/js?key=AIzaSyBQmpszQKjBkCeXSD46tZ34c0dDQ2vI9qk&sensor=true&callback=mapReady', 'js', "googlemaps" );
	}
	if ( opt.videoPlayer == "videojs" ) {
		numToLoad++;
		loadScript ( 'https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/js/videojs/vlvideo_1.5s.css?v='+rnd, 'css', "Video JS" );
		loadScript ( 'https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/js/videojs/video.js?v='+rnd, 'js', "videojs" );
	} else if ( opt.videoPlayer == "jwplayer" || opt.videoPlayer == "jwplayerpro" ) {
		numToLoad++;
		loadScript( 'http://jwpsrv.com/library/UQS8Tv7GEeKCjyIACusDuQ.js?v='+rnd, 'js', "jwplayer" ); 
	}
	loadScript ( 'https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/js/swipejs/swipe_mod_5.1.js?v='+rnd, 'js', "swipejs" );
	//loadScript ( 'http://localhost:8888/assets/vantage.js?v='+rnd, 'js', "vantage" );
	loadScript ( 'https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/js/vantage/vantage.062.js?v='+rnd, 'js', "vantage" );
	

	function loadHandler( name ) {
		numLoaded++;
		//console.log( "blob: loadHandler: numToLoad: " + numToLoad + " numLoaded: " + numLoaded + " name: " + name );
		if ( numLoaded == numToLoad ) {
			powerUp();
		}
	}
	function powerUp () {
		//console.log( "blob: powerUp: " );
		window[ad.clientID + "_adbox"] = new Vantage( ad, opt );
	}
	this.onYouTubeIframeAPIReady = function() {
		//console.log("blob: onYouTubeIframeAPIReady:");
		window[ad.clientID + "_adbox"].launchYouTube();	
	}
}
function mapReady(){
	//console.log( "blob: mapReady: " );
}
vl();
</script>