/*
 * Vantage 0.7.8
 * Vantage Local Inc
 * By Michael McConnell
 * Copyright 2013, MIT License
 *
*/

function Vantage( ad, options ) {
	// quit if no root element
	if ( !document.getElementById( ad.clientID+'_adbox' ) ) return;
	//var adbox = document.getElementById( ad.clientID+'_adbox' );

	var vl = options || {};
	
	var thisURL;
	var isMobile = false;
	var video_x;
	var video_y;
	var videoWidth;
	var videoHeight;
	var map_x;
	var map_y;
	var mapWidth;
	var mapHeight;
	var youtubeURL;
	var youTubeID;
	var margintop = 0;
	var marginleft = 0;
	var isSSL;
	var adSize;
	var player;
	var isPlayedOnce;
	var isVideoSetup;
	var isVideoSettingUp;
	var slider_index;
	this.slider; 
	var numSecCountdown;
	var counter;
	var countdownSpinner;
	var shareVariables;
	var social_x;
	var social_y;
	var facebookBtn;
	var googleBtn;
	var linkedinBtn;
	var mailBtn;
	var twitterBtn;
	var messageBoxText;
	this.hoverMask = false;
	var swipeCounter=0;
	var clickCounter=0;
	var hoverCounter=0;
	var hover2Counter=0;
	var videoPlayCounter=0;
	var playTimeSeconds=0;
	var delayTime=1000;
	var buttonTimeout;
	var expandCounter=0;
	var currentSlide=0;
	var youtubeScriptLoaded=false;

	var is_map_loaded = false;

	//setup the ad options
	this.setup = function() {
		//console.log( "Vantage: setup: " );
		//track ad impression // loadTrackingPixel( "imp" ); // removed (2013-11-21)
		//get the current publisher URL
		thisURL = window.location.protocol + "://" + window.location.host + "/" + window.location.pathname;
		//ad size
		adSize = ad.width + "x" + ad.height;
		//determine if mobile smart phone
		var is_uiwebview = /(iPhone|iPod|iPad).*AppleWebKit(?!.*Safari)/i.test(navigator.userAgent);
		var is_safari_or_uiwebview = /(iPhone|iPod|iPad).*AppleWebKit/i.test(navigator.userAgent);
		var ua = navigator.userAgent.toLowerCase();
		var isAndroid = ua.indexOf( "android") > -1;
		//message to display on expandable
		messageBoxText = vl.messageBoxText;
		//change the message if mobile
		if ( isAndroid || is_uiwebview || is_safari_or_uiwebview ) {
			isMobile = true;
			messageBoxText = vl.messageMobileText;
		}
		//get the security of the current publisher page
		if ( "https:" == document.location.protocol ) {
	   		isSSL = true;
		} else {
	   		isSSL = false;
		}
		//convert the json strings to numbers and booleans
		video_x = parseInt( vl.video_x );
		video_y = parseInt( vl.video_y );
		videoWidth = parseInt( vl.videoWidth );
		videoHeight = parseInt( vl.videoHeight );
		social_x = parseInt( vl.social_x );
		social_y = parseInt( vl.social_y );
		mapWidth = parseInt( vl.mapWidth );
		mapHeight = parseInt( vl.mapHeight );
		map_x = parseInt( vl.map_x );
		map_y = parseInt( vl.map_y );
		vl.startSlide = parseInt( vl.startSlide );
		vl.autoPlay = getBoolean( vl.autoPlay );
		vl.continuous = getBoolean( vl.continuous );
		vl.disableScroll = getBoolean( vl.disableScroll );
		vl.stopPropagation = getBoolean( vl.stopPropagation );
		vl.isRichMediaMap = getBoolean( vl.isRichMediaMap );
		vl.continuous = getBoolean( vl.continuous );
		vl.disableScroll = getBoolean( vl.disableScroll );
		vl.stopPropagation = getBoolean( vl.stopPropagation );
		vl.isExpandable = getBoolean( vl.isExpandable );
		vl.useShareButtons = getBoolean( vl.useShareButtons );
		ad.isTrackingOff = getBoolean( ad.isTrackingOff );
		vl.useFacebook = getBoolean( vl.useFacebook );
		vl.useTwitter = getBoolean( vl.useTwitter );
		vl.useGPlus = getBoolean( vl.useGPlus );
		vl.useLinkedIn = getBoolean( vl.useLinkedIn );
		vl.useEmail = getBoolean( vl.useEmail );
		vl.isHoverToClick = getBoolean( vl.isHoverToClick );
		vl.isHoverToPlay = getBoolean( vl.isHoverToPlay );
		vl.isVideo = getBoolean( vl.isVideo );
		for(var i=0; i<options.widths.length;i++) options.widths[i] = parseInt(options.widths[i], 10);
		vl.isDesktopSwipe = getBoolean( vl.isDesktopSwipe );
		vl.isCountdown = getBoolean( vl.isCountdown );
		vl.isPlayMuted = getBoolean( vl.isPlayMuted );
		vl.numAutoPlaySeconds = parseInt( vl.numAutoPlaySeconds );
		vl.isAutoLoadVideo = getBoolean( vl.isAutoLoadVideo );
		vl.isFirefox = getBoolean( vl.isFirefox );
		vl.forceHTML5 = getBoolean( vl.forceHTML5 );
		vl.showCloseButton = getBoolean( vl.showCloseButton );
		vl.isHideAdUntilLoadedBugPatch = getBoolean( vl.isHideAdUntilLoadedBugPatch );
		//creative UI starts at "1" instead of "0" for UX
		vl.videoSlideNum = parseInt( vl.videoSlideNum )-1;
		vl.mapSlideNum = parseInt(vl.mapSlideNum)-1;
		vl.socialSlideNum = parseInt( vl.socialSlideNum ) - 1;

		//for(var i=0; i<options.heights.length;i++) options.heights[i] = parseInt(options.heights[i], 10);
		//vl.isDebugMode = getBoolean( vl.isDebugMode );
		//vl.isSSL = getBoolean( vl.isSSL );
		
		
		currentSlide = vl.startSlide;
		if ( vl.isVideo ) {
			isVideoSetup = false;
			isVideoSettingUp = false;
		}

		//determine if auto-play slider, and how many seconds per slide
		if ( vl.auto != "false" ) {
			vl.auto = parseInt( vl.auto ) * 1000;
		} else {
			vl.auto = getBoolean( vl.auto );
		}
		//if auto-play, should it stop on a slide other than the last slide, //TODO: make this feature work (scope issue when calling swipe_mod to stop)
		if ( vl.stopSlide != "false" ) {
			vl.stopSlide = parseInt( vl.stopSlide );
		} else {
			vl.stopSlide = getBoolean( vl.stopSlide );
		}
		
		// Initiate swipeJS (swipe_mod)
		var elem = document.getElementById( ad.clientID + '_' + 'slider' );
		this.slider = Swipe(elem, {
			startSlide: vl.startSlide,
			auto: vl.auto,
			continuous: vl.continuous,
			disableScroll: vl.disableScroll,
			stopPropagation: vl.stopPropagation,
			isDesktopSwipe: vl.isDesktopSwipe,
			isExpandable: vl.isExpandable,
			callback: function( index, elem ) {
				sliderEventListener( index, elem );
			},
			widths: vl.widths,
			transitionEnd: function(index, element) {
				sliderTransitionEndListener( index, elem );
			}
		});
		// Adjust slider box size
		document.getElementById( ad.clientID + '_' + 'slider' ).style.width = ad.width+"px";
		document.getElementById( ad.clientID + '_' + 'slider' ).style.height = ad.height+"px";

		document.getElementById( ad.clientID + '_' + 'adbox' ).style.width = ad.width+"px";
		document.getElementById( ad.clientID + '_' + 'adbox' ).style.height = ad.height+"px";
		
		//console.log(vl.useNavbar);
		if (vl.useNavbar == 'true') {
		    setupNavbar();
		} else {
		   // console.log("UHOH");
		}
		
		
		if ( vl.isExpandable == true ) {
			this.setupExpandable();
		}
		if ( vl.useShareButtons == true ) {
			setupSocial();
		}
		countdownSpinner = new Image();
		//IE does not support CSS rotation, so must load an animated gif for loader bar/spinner
		if ( vl.isIE ) {
			countdownSpinner.src = vl.countdownSpinnerGIF;
		} else {
			countdownSpinner.src = vl.countdownSpinnerURL;
		}
		countdownSpinner.name = countdownSpinner;
		
		if ( isVideoSetup != true && vl.isVideo == true && vl.isAutoLoadVideo == true ) {
			this.setupVideo( vl.videoSlideNum );
		}
	}
	//setup expandable ad feature
	this.setupExpandable = function() {
		//console.log( "Vantage: setupExpandable: adSize: " + adSize );

		// Add message box (instructions)
		showRolloverMessage();
		
		//add the close button to the expandable slide //TODO: add close button on all expanded sizes (sizes bigger than startSlide)
	    if ( vl.showCloseButton ) {
	    	getExpandibleSlideDiv( vl.expandSlide ).innerHTML += '<div id="vl_close" class="vl-close" style="text-align:center;vertical-align:middle;font-size: 20px; font-weight: bold; font-family: Helvetica,Arial; color: #cccccc;height:26px;width:30px;position:absolute;top:10px;left:'+(options.widths[vl.expandSlide]-vl.closeButtonPadding)+'px;" onclick="'+ad.clientID+'_adbox.closeExpandable();"><img style="border:none;"src ="'+ ad.fileContainer + vl.files.closeButton.key +'"></div>';
		}
		//create a div for the countdown animation
		if ( vl.expandSlide-1 > 0  && vl.isCountdown == true ) {
			document.getElementById( ad.clientID + '_' + 'countdown' ).innerHTML = '<span style="position:absolute; z-index:2;"></span>';
		}
	}
	//show the expandable message
	function showRolloverMessage() {
		//console.log( "Vantage: showRolloverMessage: vl.isCountdown: " + vl.isCountdown );
		if ( vl.isCountdown == true ) {
			document.getElementById( ad.clientID + '_' + 'countdown' ).outerHTML = '<div id="'+ ad.clientID + '_' + 'countdown" class="vl-countdown" onclick="'+ ad.clientID + '_adbox.' +'clickExpand();" style="user-drag: none; -webkit-user-drag: none;cursor:pointer;width:'+ vl.messageBoxWidth +'px; height:'+ vl.messageBoxHeight +'px; position:absolute; z-index:1; top:'+ vl.messageBoxTop +'px; left:'+ vl.messageBoxLeft +'px; font-size: '+ vl.messageBoxTextSize +'px; font-weight: bold; font-family: Helvetica,Arial; color: '+ vl.messageBoxTextColor +';text-align: left; line-height:100%; -webkit-touch-callout: none;"><span style="position:absolute; z-index:2;text-align: left; line-height:100%; -webkit-touch-callout: none;">'+ messageBoxText +'</span></div>';
		}
	}
	//close expandable button
	this.closeExpandable = function( e ) {
		//XXX
		this.slider.slide( vl.expandSlide-1, 300 );
		loadTrackingPixel( "exc" );
	}
	//determine which video player to use, and set it up
	this.setupVideo = function( slideNum ) {
		//console.log( "Vantage: setupVideo: slideNum: " + slideNum );
		//make sure we only setup once
		if ( isVideoSetup == false && vl.isVideo == true  && !isVideoSettingUp) {
			isVideoSettingUp = true;
			//auto-play is not supported on mobile
			if ( isMobile == true ) {
				vl.autoPlay = false;
			}
		
			if ( vl.videoPlayer == "youtube" ) {
				//console.log( "Vantage: setupVideo: setupYouTube: " );
				if ( isYoutubeFile( vl.videoURL ) == true ) {
					document.getElementById( ad.clientID + '_video_' + slideNum ).innerHTML = '<div style="position:absolute;left:' + video_x + 'px;top:' + video_y + 'px;z-index:2;" class="vl-video-holder" id="'+ ad.clientID +'_video_holder"><div class="vl-player" id="player-'+ ad.clientID +'"></div></div>';
					setupYouTube();
				}
			} 
			if ( vl.videoPlayer == "jwplayer" ) {
				//console.log( "Vantage: setupVideo: setupJWPlayer: " );
				document.getElementById( ad.clientID + '_video_' + slideNum ).innerHTML = '<div style="position:absolute;left:' + video_x + 'px;top:' + video_y + 'px;z-index:2;" class="vl-video-holder" id="'+ ad.clientID +'_video_holder"><div class="vl-player" id="player-'+ ad.clientID +'"></div></div>';
				setupJWPlayer();
			}
			if ( vl.videoPlayer == "jwplayerpro" ) {
				//console.log( "Vantage: setupVideo: setupJWPlayerPro: " );
				document.getElementById( ad.clientID + '_video_' + slideNum ).innerHTML = '<div style="position:absolute;left:' + video_x + 'px;top:' + video_y + 'px;z-index:2;" class="vl-video-holder" id="'+ ad.clientID +'_video_holder"><div class="vl-player" id="player-'+ ad.clientID +'"></div></div>';
				setupJWPlayerPro();
			}
			if ( vl.videoPlayer == "videojs" ) {
				//console.log( "Vantage: setupVideo: setupVideoJS: " );
				document.getElementById( ad.clientID + '_video_' + slideNum ).innerHTML = '<div style="position:absolute;left:' + video_x + 'px;top:' + video_y + 'px;z-index:2;" class="vl-video-holder" id="'+ ad.clientID +'_video_holder"><video class="video-js vjs-default-skin" id="player-'+ ad.clientID +'"></video></div>';
				setupVideoJS();
			}
		}
	}
	
	function event_check(thing_happened){
	    if(thing_happened.stopPropagation){
		thing_happened.stopPropagation();
	    } else {
		thing_happened.cancelBubble = true; 
		thing_happened.returnValue = false;
	    }
	    
	}
	
	//google map setup
	this.setupMap = function() {
		//console.log( "Vantage: setupMap: " );
		var allLatitudes = vl.mapPinLatitudes.split(' ').join('').split(';');
		var allLongitudes = vl.mapPinLongitudes.split(' ').join('').split(';');
		var allContents = vl.mapPinDescriptions.split(';');
		var allTitles = vl.markerTitles.split(';');
		var totalMarkers = Math.min(allLatitudes.length, allLongitudes.length, allContents.length, allTitles.length);
		
		var mapOptions = {
			center: new google.maps.LatLng( allLatitudes[0], allLongitudes[0] ),
			zoom: parseInt( vl.mapDefaultZoomLevel ),
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			mapTypeControl : false
		};
		document.getElementById( ad.clientID + '_map_' + vl.mapSlideNum ).outerHTML = '<div id="'+ ad.clientID +'_map_' + vl.mapSlideNum + '" style="position:absolute; left:' + map_x + 'px; top:' + map_y + 'px; width: '+ mapWidth +'px; height:'+ mapHeight +'px;" class="vl-map-holder" ></div>';
		var map = new google.maps.Map( document.getElementById( ad.clientID + '_map_' + vl.mapSlideNum ), mapOptions );
		var mapbox = document.getElementById( ad.clientID + '_map_' + vl.mapSlideNum );
		if(mapbox.addEventListener)
		{
		    mapbox.addEventListener("click", function(event){event_check(event)}, false);
		    mapbox.addEventListener("mousedown", function(event){event_check(event)}, false);
		    mapbox.addEventListener("touchstart", function(event){event_check(event)}, false);
		} 
		else if(mapbox.attachEvent)
		{
		    mapbox.attachEvent("onclick", function(event){event_check(event)});
		    mapbox.attachEvent("mousedown", function(event){event_check(event)});
		    mapbox.attachEvent("touchstart", function(event){event_check(event)});
		}    
		var infowindow = new google.maps.InfoWindow();
		var marker, i;
		for (i = 0; i < totalMarkers; i++) {
			var latLong = new google.maps.LatLng( allLatitudes[i], allLongitudes[i] );		
			marker = new google.maps.Marker({
				position: latLong,
				map: map,
				title: allTitles[i]
			});
			
			google.maps.event.addListener(marker, 'click', (function(marker, i) {
				return function() {
					infowindow.setContent( "<strong>" + allTitles[i] + "</strong><br />" + allContents[i] );
					infowindow.open(map, marker);
				}
			})(marker, i));
		}
	}
	//call setup of vantage.js
	this.setup();
	//this function loads all the landing pages
	this.clickHandler = function( tag ) {
		//console.log( "Vantage: clickHandler: this.slider.isDragging(): " + this.slider.isDragging() + " vl.isDesktopSwipe: " + vl.isDesktopSwipe );
		//check if user is dragging the slider to swipe
		if ( vl.isDesktopSwipe == true && this.slider.isDragging() != true && ad.blankTarget != '' ) {
			window.open(ad.clickTag[tag],ad.blankTarget);
			if ( vl.isVideo == true ) {
				videoPause();
			}
			clickCounter++;
			loadTrackingPixel( "mcl" + "&cnt=" + clickCounter + "&tag=" + tag );
		} else if ( vl.isDesktopSwipe != true && ad.blankTarget != '' ) {
			window.open(ad.clickTag[tag],ad.blankTarget);
			if ( vl.isVideo == true ) {
				videoPause();
			}
			clickCounter++;
			loadTrackingPixel( "mcl" + "&cnt=" + clickCounter + "&tag=" + tag );
		}
	}
	//go to next slide, to expand slide
	this.clickExpand = function() {
		//console.log( "Vantage: clickExpand: this.slider.isDragging(): " + this.slider.isDragging() + " vl.isDesktopSwipe: " + vl.isDesktopSwipe );
		//temp method to be integrated in clickHandler
		if ( vl.isDesktopSwipe == true && this.slider.isDragging() != true ) {
			if ( isVideoSetup == true && vl.isVideo == true && vl.videoSlideNum == vl.expandSlide ) {
				this.slider.slide( vl.expandSlide, 300 );
				clearInterval(counter);
			} else if ( vl.isVideo != true ) {
				this.slider.slide( vl.expandSlide, 300 );
				clearInterval(counter);
			}
		} else if ( vl.isDesktopSwipe != true  ) {
			if ( isVideoSetup == true && vl.isVideo == true && vl.videoSlideNum == vl.expandSlide ) {
				this.slider.slide( vl.expandSlide, 300 );
				clearInterval(counter);
			} else if ( vl.isVideo != true ) {
				this.slider.slide( vl.expandSlide, 300 );
				clearInterval(counter);
			}
		}
	}
	//engagement tracking
	function loadTrackingPixel(t) {
		//console.log( "Vantage: loadTrackingPixel: type: " + t );
		if ( ad.isTrackingOff != true ) {
			var pixelURL = new Image();
			if ( isSSL == true ) {
				pixelURL.src = vl.trackingPixelAddressSSL + "?clientid=" + ad.clientID + "&e=" + t;
			} else {
				pixelURL.src = vl.trackingPixelAddress + "?clientid=" + ad.clientID + "&e=" + t;
			}
		}
	}
	
	function setupNavbar(){
	   // console.log("SETUPNAVBAR")
	   // console.log(vl.videoSlideNum)
	    if(vl.isVideo) {
		 //console.log("SETUPNAVBARVIDEO")
		document.getElementById(ad.clientID+'_nav_'+vl.videoSlideNum+'_'+vl.videoSlideNum).className = "vl-activeslidenavbtn";
	    } 
	    if(vl.isRichMediaMap) {
		// console.log("SETUPNAVBARMAP")
		document.getElementById(ad.clientID+'_nav_'+vl.mapSlideNum+'_'+vl.mapSlideNum).className = "vl-activeslidenavbtn";
	    }
	    if(vl.useShareButtons) {
		 //console.log("SETUPNAVBARSOCIAL")
		//Anyone want to explain why I had to +1 this slide number if video=1, map=2, social=3 in variables?
		document.getElementById(ad.clientID+'_nav_'+vl.socialSlideNum+'_'+vl.socialSlideNum).className = "vl-activeslidenavbtn";
	    }
	    for(var i = 0; i < 3; i++)
	    {
		for(var j = 0; j < 3; j++)
		{
		    var current_button = document.getElementById(ad.clientID+'_nav_'+i+'_'+j);
		    if(current_button.addEventListener){
		    current_button.addEventListener("click", function(event){event_check(event)}, false);
		    } 
		    else if(current_button.attachEvent)
		    {
		     current_button.attachEvent("onclick", function(event){event_check(event)});
		    } 
		}
	    }
	    
	}
	
	this.onEnrollButtonClick = function() {
		//console.log( "Vantage: onEnrollButtonClick: " );
		
		//loadTrackingPixel("sht");
		
		//var windowFeatures = "height=520,width=660,toolbar=no,scrollbars=yes";
		window.open( vl.specURL, "win");
	    
	}
	
	//social sharing
	function setupSocial() {
	    
		//console.log( "Vantage: setupSocial: " );
	    document.getElementById( ad.clientID + '_social_' + vl.socialSlideNum ).innerHTML = '<div id="'+ ad.clientID + '_' +'social-holder" class="vl-social-holder" style="width:'+ parseInt( vl.socialWidth ) +'px;position:absolute;left:' + social_x + 'px;top:' + social_y + 'px;z-index:1; cursor: pointer;"></div>';

		if ( vl.useFacebook ) {
			document.getElementById( ad.clientID + '_' +'social-holder' ).innerHTML += '<img id="' + ad.clientID + '_' + 'facebookBtn" class="vl-facebook" src="' + ad.fileContainer + vl.files.facebookButtonImage.key +'" onclick="'+ ad.clientID + '_adbox.' +'onFacebookClick();"/>';
		}
		if ( vl.useTwitter ) {
			document.getElementById( ad.clientID + '_' +'social-holder' ).innerHTML += '<img id="' + ad.clientID + '_' + 'twitterBtn" class="vl-twitter" src="' + ad.fileContainer + vl.files.twitterButtonImage.key +'" onclick="'+ ad.clientID + '_adbox.' +'onTwitterClick();"/>';
		}
		if ( vl.useGPlus ) {
			document.getElementById( ad.clientID + '_' +'social-holder' ).innerHTML += '<img id="' + ad.clientID + '_' + 'googleBtn" class="vl-google" src="' + ad.fileContainer + vl.files.googleButtonImage.key +'" onclick="'+ ad.clientID + '_adbox.' +'onGoogleClick();"/>';
		}
		if ( vl.useLinkedIn ) {
			document.getElementById( ad.clientID + '_' +'social-holder' ).innerHTML += '<img id="' + ad.clientID + '_' + 'linkedinBtn" class="vl-linkedin" src="' + ad.fileContainer + vl.files.linkedinButtonImage.key +'" onclick="'+ ad.clientID + '_adbox.' +'onLinkedinClick();"/>';
		}
		if ( vl.useEmail ) {
			document.getElementById( ad.clientID + '_' +'social-holder' ).innerHTML += '<img id="' + ad.clientID + '_' + 'mailBtn" class="vl-mail" src="' + ad.fileContainer + vl.files.emailButtonImage.key +'" onclick="'+ ad.clientID + '_adbox.' +'onMailClick();"/>';
		}
		
		if(document.getElementById( ad.clientID + '_' +'social-holder' ).addEventListener)
		{
		    document.getElementById( ad.clientID + '_' +'social-holder' ).addEventListener("click", function(event){event_check(event)}, false);
		} else if(document.getElementById( ad.clientID + '_' +'social-holder' ).attachEvent)
		{
		  document.getElementById( ad.clientID + '_' +'social-holder' ).attachEvent("onclick", function(event){event_check(event)});  
		}
	}
	// Share on Facebook
	this.onFacebookClick = function() {
		//console.log( "Vantage: onFacebookClick: " );
		loadTrackingPixel("shf");
		
		var sharerURL = "https://www.facebook.com/sharer.php?u=" + escape( vl.urlToShare );
		var windowFeatures = "height=520,width=660,toolbar=no,scrollbars=yes";
		window.open( sharerURL, "win", windowFeatures );
	}
	// Share on Twitter
	this.onTwitterClick = function() {
		//console.log( "Vantage: onTwitterClick: " );
		loadTrackingPixel("sht");
		
		var sharerURL = 'https://twitter.com/share?url=' + escape( vl.urlToShare ) + '&text=' + vl.twitterText +' @' + escape( vl.urlToShare );
		var windowFeatures = "height=520,width=660,toolbar=no,scrollbars=yes";
		window.open( sharerURL, "win", windowFeatures );
	}
	// Google share
	this.onGoogleClick = function() {	
		//console.log( "Vantage: onGoogleClick: " );
		loadTrackingPixel("shg");
		
		//plus one g+
		var sharerURL = "https://plus.google.com/share?url=" + escape( vl.urlToShare );
		var windowFeatures = "menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=600";
		window.open( sharerURL, "win", windowFeatures );
		
	}
	// Share on LinkedIn
	this.onLinkedinClick = function() {
		//console.log( "Vantage: onLinkedinClick: " );
		loadTrackingPixel("shl");
		
		var sharerURL = "https://www.linkedin.com/shareArticle?url=" + escape( vl.urlToShare ) + '&title=' + vl.shareTitle + '&summary=' + vl.shareNotes + '&source=' + vl.shareSource;
		var windowFeatures = "menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=600";
		window.open( sharerURL, "win", windowFeatures );
	}	
	// Share via e-mail
	this.onMailClick = function() {
		//console.log( "Vantage: onMailClick: " );
		loadTrackingPixel("she");
		
		var sharerURL = 'mailto:?' + 'subject=' + encodeURIComponent( vl.mailSubject ) + '&body=' + encodeURIComponent( vl.mailBody ) + '    ' + encodeURIComponent( vl.urlToShare );
		var windowFeatures = "menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=600";
		window.open( sharerURL, "win", windowFeatures );
	}
	//on hover over ad
	this.onHoverMaster = function() {
		//console.log( "Vantage: onHoverMaster: " );
		//if expandable, current slide is the one before expandable slide, there is a hover action "expand", not mobile, and there is a countdown feature
		if ( vl.isExpandable == true && this.slider.getPos() == vl.expandSlide-1 && ( vl.hoverAction != "" && vl.hoverAction != null ) && !isMobile && vl.isCountdown == true ) {
			if( this.hoverMask == false ) {
				this.hoverMask = true;
				this.onExpandableHover();
			}
		}
		if ( isVideoSetup != true && vl.isVideo == true ) {
			this.setupVideo( vl.videoSlideNum );
			
		}
		hoverCounter++;
		loadTrackingPixel("hov" + "&cnt=" + hoverCounter);
		is2secHover = true;	//XXX //TODO: variable may not be necessary
		//engagement tracking countdown for 2-second hover
		if(is2secHover == true) {
			clearTimeout( buttonTimeout );
			buttonTimeout = setTimeout( hover2Seconds, 2000 );
		}
	}
	//on hover out of ad
	this.onOutMaster = function() {
		//console.log( "Vantage: onOutMaster: " );
		//if expandable, current slide is the one before expandable slide, there is a hover action "expand", not mobile, and there is a countdown feature
		if ( vl.isExpandable == true && this.slider.getPos() == vl.expandSlide-1 && ( vl.hoverAction != "" && vl.hoverAction != null ) && !isMobile && vl.isCountdown == true ) {
			if( this.hoverMask == true ){
				this.hoverMask = false;
				this.onExpandableRollout();
			}
		}
		clearTimeout( buttonTimeout );
		is2secHover = false;
	}
	//fire engagement tracking for 2-second hover
	function hover2Seconds() {
		//console.log( "Vantage: hover2Seconds: " );
		hover2Counter++;
		loadTrackingPixel( "hv2" + "&cnt=" + hover2Counter );
	}
	//3-second countdown feature to expand the ad, vl.hoverAction "expand"
	this.onExpandableHover = function() {
		//console.log( "Vantage: onExpandableHover: vl.hoverAction: " + vl.hoverAction );
		this.startCountdown( 3, vl.hoverAction );
	}
	//show expand message instead of countdown, on rollout
	this.onExpandableRollout = function() {
		//console.log( "Vantage: onExpandableRollout: " );
		clearInterval( counter );
		showRolloverMessage();
		//document.getElementById( "spinner" ).innerHTML = "";
	}
	//countdown timer
	this.timer = function( dothis ) {
		//console.log( "Vantage: timer: numSecCountdown: " + numSecCountdown );
		numSecCountdown = numSecCountdown -1;
		if ( numSecCountdown <= 0) {
			clearInterval( counter );
			//document.getElementById( ad.clientID + '_' + 'countdown' ).innerHTML = "";
			this.onTimerComplete( dothis );
			return;
		}
		document.getElementById( ad.clientID + '_timer' ).innerHTML = numSecCountdown;
	}
	//countdown display
	this.startCountdown = function( num, dothis ) {
		//console.log( "Vantage: startCountdown: " );
		numSecCountdown = num;
		//show second countdown numbers
		document.getElementById( ad.clientID + '_' + 'countdown' ).outerHTML = '<div id="'+ ad.clientID + '_' + 'countdown" class="vl-countdown" style="-webkit-user-drag: none;cursor: pointer; width:'+ countdownSpinner.width +'px; height:'+ countdownSpinner.height +'px; position:absolute; z-index:1; top:'+ ( ad.height/2 -countdownSpinner.height/2 ) +'px; left:'+ ( ad.width/2 -countdownSpinner.width/2 ) +'px; font-size: 40px; font-weight: bold; font-family: Helvetica,Arial; color: #ffffff;text-align: left; line-height:100%" onclick="'+ ad.clientID + '_adbox.' +'clickExpand();" ><span id="' + ad.clientID + '_timer" style="cursor: pointer;position:absolute; left: 23px; top: 15px; z-index:2; -webkit-touch-callout: none;">'+ numSecCountdown +'</span><img id="spinner" style="cursor: pointer; -webkit-animation: rotation .9s infinite linear;-moz-animation: rotation .9s infinite linear; -webkit-touch-callout: none; -webkit-user-drag: none;" src="' + countdownSpinner.src +'" ></div>';
		obj = this;
		counter = setInterval( function() {obj.timer ( dothis );}, 600 );
	}
	//on countdown complete
	this.onTimerComplete = function( dothis ) {
		//console.log( "Vantage: onTimerComplete: dothis: " + dothis );
		if ( numSecCountdown < 0) {
			clearInterval( counter );
			return;
		}
		//if the countdown option is to "expand" ==> vl.hoverAction
		if ( dothis == "expand" ) {
			this.slider.slide( vl.expandSlide, 300 );
			clearInterval( counter );
		}
	}
	
	function load_javascript(file)
	{
		var script = document.createElement( 'script' );
		script.type = 'text/javascript';
		script.src = file;
		var firstScriptTag = document.getElementsByTagName( 'script' )[0];
		firstScriptTag.parentNode.insertBefore( script, firstScriptTag );
	}

	function load_map_libraries()
	{
		// Note: assuming that powerUp() has been called
		load_javascript('https://maps.googleapis.com/maps/api/js?key=AIzaSyBQmpszQKjBkCeXSD46tZ34c0dDQ2vI9qk&sensor=true&callback='+ad.clientID+'_vl_map_ready');
	}

	//listener on every slide enter
	function sliderEventListener( index, elem ) {
		//console.log( "Vantage: sliderEventListener: index: " + index + " div-id: " + elem.id + " vl.isExpandable : " + vl.isExpandable );
		currentSlide = index;
		morph(index);

		if(vl.isRichMediaMap == true && index == vl.mapSlideNum)
		{
			if(is_map_loaded == false)
			{
				is_map_loaded = true;
				load_map_libraries();
			}
		}

		if ( index == vl.expandSlide && vl.isExpandable == true ) {
			clearInterval( counter );
			document.getElementById( ad.clientID + '_' + 'countdown' ).innerHTML = '<span style="position:absolute; z-index:2; -webkit-touch-callout: none;"></span>';
		    
			expandCounter++
			loadTrackingPixel( "exp" + "&cnt=" + expandCounter );
		}
		if ( vl.auto != false && index == vl.stopSlide ) {
			//console.log("sliderEventListener: adbox: " + adbox + " this: " + this);
			//console.log( "Sorry, auto stop is not complete" );
			//this.slider.stop();
		}
		swipeCounter++;
		loadTrackingPixel( "swp" + "&cnt=" + swipeCounter );
	}
	//listener on every slide transition complete
	function sliderTransitionEndListener( index, elem ) {
		//console.log( "Vantage: sliderTransitionEndListener: index: " + index + " div-id: " + elem.id + " vl.isExpandable : " + vl.isExpandable );
		if ( index == vl.expandSlide - 1 && vl.isExpandable == true ) {
			clearInterval( counter );
			showRolloverMessage();
		} else {
			document.getElementById( ad.clientID + '_' + 'countdown' ).innerHTML = '<span style="position:absolute; z-index:2; -webkit-touch-callout: none;"></span>';
		}
		if ( index == vl.videoSlideNum && vl.isVideo == true && ( index == vl.expandSlide || vl.autoPlay == true ) && isMobile != true ) {
			if ( isVideoSetup == true ) {
				videoPlay();
			}
		} else if ( vl.isVideo == true && isVideoSetup == true ){ 
			videoPause();
		}
	}
	//setup videoJS
	function setupVideoJS() {
		//console.log( "Vantage: setupVideoJS: videoID: " + vl.videoMP4 + " vl.autoPlay: " + vl.autoPlay );
		//prioritize player type flash vs. html5
		var first;
		var second;
		if ( isMobile ) {
			first = "html5";
			second = "flash";
		} else {
			first = "flash";
			second = "html5";
		}
		player = videojs( 'player-'+ ad.clientID, {
        	"width" : videoWidth,
        	"height" : videoHeight,
        	"autoplay" : vl.autoPlay,
        	"controls" : true,
        	"techOrder" : [first, second],
        	"poster": vl.videoPoster,
        	"preload" : "none"
		});
		player.src([
			//{ type: "video/youtube", src: vl.videoURL },
  			{ type: "video/mp4", src: vl.videoMP4 },
  			{ type: "video/webm", src: vl.videoWEBM },
  			{ type: "video/ogg", src: vl.videoOGV }
		]);
		player.on( "ended", videoComplete );
		//player.requestFullScreen();
		isVideoSetup = true;
    }
    //setup JWplayer
	function setupJWPlayer() {
		//console.log( "Vantage: setupJWPlayer: videoID: " + vl.videoID );
		player = jwplayer( 'player-'+ ad.clientID ).setup({
        	playlist: 'http://jwpsrv.com/feed/' + vl.videoID + '.rss',
        	width: videoWidth,
        	height: videoHeight,
        	//aspectratio: '16:9',
        	autostart: vl.autoPlay
        	//controls: 'false',
        	//fallback: 'false',
        	//mute: 'true',
		});
		isVideoSetup = true;
    }
    //jwplayer pro version, using bitsontherun to stream
    function setupJWPlayerPro() {
		//console.log( "Vantage: setupJWPlayerPro: videoID: " + vl.videoID );
		var protocol = "http://";
	    if( isSSL == true) {
			 protocol = "https://";
		}

		var plugin_path = protocol+"a.jwpcdn.com/player/6/653609/ping.js";
		player = jwplayer( 'player-'+ ad.clientID ).setup({
			autostart: vl.autoPlay,
			controls: true,
			displaytitle: false,
			fallback: false,
			flashplayer: protocol+"a.jwpcdn.com/player/6/653609/jwplayer.flash.swf",
			html5player: protocol+"a.jwpcdn.com/player/6/653609/jwplayer.html5.js",
			plugins: { plugin_path: {"pixel": protocol+"content.bitsontherun.com/ping.gif"}},
			//primary: "flash",
			repeat: false,
			image: protocol+'content.bitsontherun.com/thumbs/'+ vl.videoID +'-480.jpg',
			playlist: protocol+'content.bitsontherun.com/jw6/'+ vl.videoID +'.xml',
			stretching: "uniform",
			width: videoWidth,
			height: videoHeight
		});
		isVideoSetup = true;
    }
	//load the youtube script
	function setupYouTube() {
		//console.log( "Vantage: setupYouTube: " );
		if ( !youtubeScriptLoaded ) {
			var tag = document.createElement( 'script' );
			tag.src = "https://www.youtube.com/iframe_api";
			var firstScriptTag = document.getElementsByTagName( 'script' )[0];
			firstScriptTag.parentNode.insertBefore( tag, firstScriptTag );
		
			youtubeScriptLoaded = true;
		}
	}
	//callback function called from js blob once youtube script is loaded
	this.launchYouTube = function() {
		//console.log( "Vantage: onYouTubeIframeAPIReady: youTubeID: " + youTubeID );
		//use html5 player for mobile, Firefox or when forced
		if ( isMobile || vl.isFirefox || vl.forceHTML5 ) {
			player = new YT.Player('player-'+ ad.clientID, {
				width: videoWidth,
				height: videoHeight,
				videoId: youTubeID,
				playerVars: { 
					'enablejsapi': 1, 
					'modestbranding': 1,
					'rel':0,
					'autoplay': vl.autoPlay,
					'autohide': 1,
					'iv_load_policy': 3,
					'origin': thisURL,
					'showinfo': 0,
					'showsearch': 0,
					'iv_load_policy': 3,
					'fs': 1,
					'controls':1,
					'html5':1
				},
				events: {
					'onReady': onYouTubeReady,
					'onStateChange': onYouTubeStateChange
				}
			});
		//use flash player
		} else {
			 player = new YT.Player('player-'+ ad.clientID, {
				width: videoWidth,
				height: videoHeight,
				videoId: youTubeID,
				playerVars: { 
					'enablejsapi': 1, 
					'modestbranding': 1,
					'rel':0,
					'autoplay': vl.autoPlay,
					'autohide': 1,
					'iv_load_policy': 3,
					'origin': thisURL,
					'showinfo': 0,
					'showsearch': 0,
					'iv_load_policy': 3,
					'fs': 1,
					'controls':1
				},
				events: {
					'onReady': onYouTubeReady,
					'onStateChange': onYouTubeStateChange
				}
			});
		}
	}
	//when the youtube video is setup and loaded, ready to go
	function onYouTubeReady(event) {
		//console.log( "Vantage: onYouTubeReady: " );
		isVideoSetup = true;
		//if video is auto-play and the current slide is the slide with the video, play it
		if ( vl.autoPlay == true && currentSlide == vl.videoSlideNum ) {
			event.target.playVideo();
		}
		//show the ad (adbox) if hidden
		//if using youtube video with auto-load on an expandable ad, the ad is hidden until the youtube is done loading to prevent user from clicking
		//TODO: prevent slider/video fail if clicked while loading in Vantage/swipe_mod
		if ( vl.isExpandable && vl.isAutoLoadVideo && vl.isHideAdUntilLoadedBugPatch ) {
			var css = '.adbox{display:block !important;}',
				header = document.getElementsByTagName('head')[0],
				style = document.createElement('style');
		
			style.type = 'text/css';
			if (style.styleSheet){
			  style.styleSheet.cssText = css;
			} else {
			  style.appendChild(document.createTextNode(css));
			}
			header.appendChild(style);
		}
		//hide the fullscreen button on firefox or forcedHTML5, using html5 player
		//BUG patch attempt: when user exits fullscreen, the viewport breaks on expandables
		//TODO: this does not work, fix this patch
		if ( vl.isFirefox || vl.forceHTML5 && !isMobile && vl.isExpandable ) {
			var youtubeiFrame = document.getElementById('player-'+ ad.clientID);
			if(this.contentDocument) youtubeiFrame.doc = youtubeiFrame.contentDocument;
			else if(iframe.contentWindow) youtubeiFrame.doc = youtubeiFrame.contentWindow.document;

			var fullscreenCSS = '.ytp-button-fullscreen-enter{display:none !important;}',
				youtubeHead = youtubeiFrame.doc.getElementsByTagName('head')[0],
				youtubeStyle = document.createElement('style');

			youtubeStyle.type = 'text/css';
			if (youtubeStyle.styleSheet){
			  youtubeStyle.styleSheet.cssText = fullscreenCSS;
			} else {
			  youtubeStyle.appendChild(document.createTextNode(fullscreenCSS));
			}
			youtubeHead.appendChild(youtubeStyle);
		}
	}
	//listener for youtube events
	function onYouTubeStateChange( event ) {
		//console.log( "Vantage: onYouTubeStateChange: " + " playersstate: " + YT.PlayerState.PLAYING + " isPlayedOnce: " + isPlayedOnce + " vl.autoPlay: " + vl.autoPlay + " vl.isPlayMuted: " + vl.isPlayMuted + " vl.numAutoPlaySeconds: " + vl.numAutoPlaySeconds );
		//auto-play muted only once
		if ( event.data == YT.PlayerState.PLAYING && isPlayedOnce != true ) {
			if ( vl.autoPlay == true ) {
				var sec = vl.numAutoPlaySeconds * 1000;
				setTimeout (videoStop, sec );
			}
			if ( vl.isPlayMuted == true ) {
				videoMute();
			}
			isPlayedOnce = true;
		}
		if ( event.data == YT.PlayerState.PLAYING ) {
			videoPlayCount();
		}
		if ( event.data == YT.PlayerState.PAUSED ) {
			//TODO: set variable to know when paused
		}
	}
	//============================================VIDEO BUTTON====================================
	//TODO: options if creating a play button that loads the video on click or hover
	/*
	function hoverToPlayOver( e ) {
		//XXX
		isHoverToPlay = true;
		if( vl.isHoverToClick == true ) { //XXX
			clearTimeout( buttonTimeout );
			buttonTimeout = setTimeout( playDelay, delayTime );
		}
	}

	function hoverToPlayOut( e ) {
		//XXX
		isHoverToPlay = false;
		clearTimeout( buttonTimeout );
	}

	function playDelay() {
		//XXX
		if ( vl.isHoverToPlay == true && isVideoLoaded == false ){
			//loadChild("video");	//XXX
			loadTrackingPixel("hvp");
		}
	}

	function clickToLoadVideo( e ) {
		//XXX
		if( isVideoLoaded == false ){
			//loadChild("video");	//XXX
			loadTrackingPixel("cpv");
		}
	}
	//TODO: engagement tracking for fullscreen event
	function enterFullscreen( e ) {
		//console.log( "Vantage: enterFullscreen: " );
		loadTrackingPixel("ful");
	}
	*/
	//============================================/VIDEO BUTTON====================================
	//if video is complete
	//TODO: only setup for youtube, hook into other player APIs
	function videoComplete( e ) {
		//console.log( "Vantage: videoComplete: " );
		loadTrackingPixel("vdc");
		videoStop();
	}
	//TODO: engagement tracking for fullscreen event
	function exitFullscreen( e ) {
		//console.log( "Vantage: exitFullscreen: " );
		loadTrackingPixel("exf");
	}
	//counts and tracks every second of video play time
	//TODO: only setup for youtube, hook into other player APIs
	function videoPlayTime( e ) {
		//console.log( "Vantage: videoPlayTime: " );
		playTimeSeconds++;
		loadTrackingPixel("pts" + "&cnt=" + playTimeSeconds);
	}
	//TODO: only setup for youtube, hook into other player APIs
	function videoMidpoint( e ){
		//console.log( "Vantage: videoMidpoint: " );
		loadTrackingPixel("mid");
	}
	//TODO: only setup for youtube, hook into other player APIs
	function videoPlayCount( e ) {
		//console.log( "Vantage: videoPlayCount: " );
		videoPlayCounter++;
		loadTrackingPixel("vpc" + "&cnt=" + videoPlayCounter);
	}
	//stop any of the video players
	function videoStop() {
		//console.log( "Vantage: videoStop: " );
		if ( vl.videoPlayer == "youtube" ) {
			player.seekTo(0);
			videoUnMute();
			videoPause();
		}
		if ( vl.videoPlayer == "jwplayer"  || vl.videoPlayer == "jwplayerpro" ) {
			player.seek(0);
			videoUnMute();
			videoPause();
		}
		
	}
	//pause any of the video players
	function videoPause() {
		//console.log( "Vantage: videoPause: " );
		if ( vl.videoPlayer == "youtube" ) {	//TODO: check if playing, see onYouTubeStateChange above
			player.pauseVideo();
		}
		if ( ( vl.videoPlayer == "jwplayer" || vl.videoPlayer == "jwplayerpro" ) && player.getState() == "PLAYING" ) {
			player.pause();
		}
		if ( vl.videoPlayer == "videojs" && player.paused() == false ) {
			player.pause();
		}
	}
	//mute any of the video players
	function videoMute() {
		//console.log( "Vantage: videoMute: " );
		if ( vl.videoPlayer == "youtube" ) {
			player.mute();
		}
		if ( vl.videoPlayer == "jwplayer" || vl.videoPlayer == "jwplayerpro" ) {
			player.setMute();
		}
	}
	//unmute any of the video players
	function videoUnMute() {
		//console.log( "Vantage: videoUnMute: " );
		if ( vl.videoPlayer == "youtube" ) {
			player.unMute();
		}
		if ( vl.videoPlayer == "jwplayer" || vl.videoPlayer == "jwplayerpro" ) {
			player.setMute();
		}
	}
	//play any of the players
	function videoPlay() {
		//console.log( "Vantage: videoPlay: isMobile: " + isMobile );
		//auto-play is not allowed on mobile, must be user initiated, many cases require device player (ex. iphone)
		if ( isMobile != true ) {
			if ( vl.videoPlayer == "youtube" ) {
				player.playVideo();
			}
			if ( vl.videoPlayer == "jwplayer" || vl.videoPlayer == "jwplayerpro" || vl.videoPlayer == "videojs" ) {
				player.play();
			}
		}
	}
	//adjusts the slider size for expandable ads
	var lastSlide = vl.startSlide;
	function morph( index ) {
		//console.log("Vantage: Morph: vl.isExpandable: " + vl.isExpandable + " vl.widths[index]: " + vl.widths[index] + " vl.widths[lastSlide]: " + vl.widths[lastSlide] + " lastSlide: " + lastSlide + " vl.startSlide: " + vl.startSlide + " index: " + index );
		//console.log( vl.widths );
		//console.log( "Vantage: morph: document.getElementById( ad.clientID + '_' + 'slider' ).style.left: " + document.getElementById( ad.clientID + '_' + 'slider' ).style.left );
		if( vl.isExpandable == true ) {		//TODO: fire morph on every slide event, remove the true/false, and morph based on if width change
			document.getElementById( ad.clientID + '_' + 'slider' ).style.width = vl.widths[index]+"px";
			document.getElementById( ad.clientID + '_' + 'slider' ).style.height = vl.heights[index]+"px";
			if( index == vl.startSlide ) {
				document.getElementById( ad.clientID + '_' + 'slider' ).style.left = 0 +"px";
			}	
			else if( vl.widths[index] != vl.widths[lastSlide] ) {
				document.getElementById( ad.clientID + '_' + 'slider' ).style.left = ( -1*( vl.widths[index]-vl.widths[lastSlide] ) )+"px";
				//console.log( "Vantage: morph: document.getElementById( ad.clientID + '_' + 'slider' ).style.left: " + document.getElementById( ad.clientID + '_' + 'slider' ).style.left );
			} /*else {
				getExpandibleSlideDiv(vl.expandSlide).style.webkitTransform = "translate("+vl.widths[index]+"px, 0)";
				setTimeout(function(){
				document.getElementById( ad.clientID + '_' + 'slider' ).style.width = ad.width+"px";
				//console.log("width = "+ad.width);
				document.getElementById( ad.clientID + '_' + 'slider' ).style.height = ad.height+"px";
				//console.log("height = "+ad.height);
				document.getElementById( ad.clientID + '_' + 'slider' ).style.left = "0px";
				}, 150);
			}*/
		}
		lastSlide = index;
	}
    
	function getExpandibleSlideDiv( slideNum ) {
		allSwipeRelatedDivs = document.getElementById( ad.clientID + '_' + 'slider' ).getElementsByTagName("div");
		for(var i = 0; i < allSwipeRelatedDivs.length;i++) {
			if(allSwipeRelatedDivs[i].getAttribute('data-index') == slideNum ) {
			//console.log("Vantage: getExpandibleSlideDiv: " + "Divs FOUND: ");
			//console.log(allSwipeRelatedDivs[i]);
			return allSwipeRelatedDivs[i];
			}
		}
	}

	//--------------------------TOOLS----------------------------
	//custom getElementsByTagName because this does not exist for all browsers
	function getElementsByClassName(node, classname) {
		var a = [];
		var re = new RegExp('(^| )'+classname+'( |$)');
		var els = node.getElementsByTagName("*");
		for(var i=0,j=els.length; i<j; i++)
				if(re.test(els[i].className))a.push(els[i]);
		return a;
	}
    //validate youtube URL
	function isYoutubeFile(vidURL) {
		//console.log( "Vantage: isYoutubeFile: vidURL: " +vidURL );
		var videoURL;
		if ( vidURL.toLowerCase().indexOf( "http://www.youtube.com/v/" ) == 0 ) {
			youTubeID = vidURL.substr( 25, 11 );
			if (isSSL == true) {
				videoURL = "https://www.youtube.com/v/" + youTubeID;
			} else {
				videoURL = "http://www.youtube.com/v/" + youTubeID;
			}
			return true;
		}
		if ( vidURL.toLowerCase().indexOf( "http://www.youtube.com/watch?v=" ) == 0 ) {
			youTubeID = vidURL.substr( 31, 11 );
			if ( isSSL == true ) {
				videoURL = "https://www.youtube.com/v/" + youTubeID;
			} else {
				videoURL = "http://www.youtube.com/v/" + youTubeID;
			}
			return true;
		}
		if ( vidURL.toLowerCase().indexOf( "https://www.youtube.com/v/" ) == 0 ) {
			youTubeID = vidURL.substr( 26, 11 );
			if ( isSSL == true ) {
				videoURL = "https://www.youtube.com/v/" + youTubeID;
			} else {
				videoURL = "http://www.youtube.com/v/" + youTubeID;
			}
			return true;
		}
		if ( vidURL.toLowerCase().indexOf( "https://www.youtube.com/watch?v=" ) == 0 ) {
			youTubeID = vidURL.substr(32, 11);
			if ( isSSL == true ) {
				videoURL = "https://www.youtube.com/v/" + youTubeID;
			} else {
				videoURL = "http://www.youtube.com/v/" + youTubeID;
			}
			return true;
		}
		return false;
	}
	//strict boolean, ex string conversion
	function getBoolean(value) {
		if (value == false) {
			return false;
		}
		if ( value == true ) {
			return true;
		}
		if ( value == "true" ) {
			return true;
		} else {
			return false;
		}
	}
	//--------------------------/TOOLS----------------------------
	//custom hover listener for the ad and all elements within it
	var addEvent = window.addEventListener ? function (elem, type, method) {
        elem.addEventListener(type, method, false);
    } : function (elem, type, method) {
        elem.attachEvent('on' + type, method);
    };

	var removeEvent = window.removeEventListener ? function (elem, type, method) {
        elem.removeEventListener(type, method, false);
    } : function (elem, type, method) {
        elem.detachEvent('on' + type, method);
    };

	function contains(container, maybe) {
		return container.contains ? container.contains(maybe) :
        !!(container.compareDocumentPosition(maybe) & 16);
	}
	function mouseEnterLeave(elem, type, method) {
		var mouseEnter = type === 'mouseenter',
			ie = mouseEnter ? 'fromElement' : 'toElement',
			method2 = function (e) {
				e = e || window.event;
				var target = e.target || e.srcElement,
					related = e.relatedTarget || e[ie];
				if ((elem === target || contains(elem, target)) &&
					!contains(elem, related)) {
						method();
				}
			};
		type = mouseEnter ? 'mouseover' : 'mouseout';
		addEvent(elem, type, method2);
		return method2;
	}
	var div = document.getElementById(ad.clientID+'_adbox');
	var obj = this;
	var listener = function(){
		obj.onHoverMaster();
		//console.log('- over()');
	};
	var listenerTwo = function () { 
		obj.onOutMaster(); 
		//console.log('- out()');
	};
	mouseEnterLeave(div, 'mouseenter', listener);
	mouseEnterLeave(div, 'mouseleave', listenerTwo);

	var newListener = mouseEnterLeave(div, 'mouseenter', listener);

	// removing...
	removeEvent(div, 'mouseover', newListener);

}
