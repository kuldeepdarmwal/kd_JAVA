<!-- v3.0.1 - 2016.07.25 -->
<?php 
	if (empty($return_as_js) || !$return_as_js)
	{
?>		<script type="text/javascript">
<?php	}
?>
var dcgif = "<?php echo $open_backup_image;?>"; <?php //URL of the backup image for DFA ?> 
var dcgifSSL = "<?php echo $ssl_backup_image;?>"; <?php //URL of the backup image for DFA ?>
var vl_html_target = "<?php echo isset($no_engage) && empty($landing_page) ? '' : '_blank';?>"; <?php // If approval or preview page, and no landing page is set, do not open a new window. When landing page is set, or publishing, open a new window (when clicked). ?>
var isHD = "<?php echo $is_hd ? 'true' : 'false';?>"; <?php // all ads are HD now; use rich grandpa ?>
var dccreativewidth = "<?php echo $creative_width;?>"; <?php // ad size width for DFA and to send grandpa/children/html5 ?>
var dccreativeheight = "<?php echo $creative_height;?>"; <?php // ad size heigth for DFA and to send to grandpa/children/html5 ?>
var adSize = dccreativewidth + "x" + dccreativeheight;
var parentURL = ""; <?php // URL of animation SWF ?>
var parentURLSSL = ""; <?php // secure URL of animation SWF ?>
var isGrandpaImage = "<?php echo $is_gpa ? 'true' : 'false';?>"; <?php // boolean, does this ad use a loader_image ?>
var grandpaImg = "<?php echo $open_gpa_image_file;?>"; <?php // also known as loader_image; image displayed while loading and/or still ads ?>
var grandpaImgSSL = "<?php echo $ssl_gpa_image_file;?>"; <?php // secure loader_image; image displayed while loading and/or still ads ?>
var playButtonImage = "";
var fullscreenURL = "<?php echo $open_fullscreen_file;?>";  <?php // URL of video fullscreen background image ?>
var fullscreenURLSSL = "<?php echo $ssl_fullscreen_file;?>"; <?php // secure URL of video fullscreen background image ?>
var grandpaURL = ""; <?php // URL or rich grandpa on CDN ?>
var grandpaURLSSL = ""; <?php // secure URL or rich grandpa on CDN ?>
var coreJSURL = "http://ad.vantagelocal.com/core/Core.3.1.2.min.js"; <?php // URL of Core JavaScript on CDN ?>
var coreJSURLSSL = "https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/core/Core.3.1.2.min.js"; <?php // secure URL of Core JavaScript on CDN ?>
var childrenJSURL = "http://ad.vantagelocal.com/children/Children.3.1.2.min.js"; <?php // URL of combined Children JavaScript on CDN ?>		
var childrenJSURLSSL = "https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/children/Children.3.1.2.min.js"; <?php // secure URL of combined Children JavaScript on CDN ?>
var shareButtonsURL = "http://ad.vantagelocal.com/test/share-buttons/share-buttons.png"; <?php // URL of SocialChild JavaScript on CDN ?>
var shareButtonsURLSSL = "https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/test/share-buttons/share-buttons.png"; <?php // secure URL of SocialChild JavaScript on CDN ?>
var rnd = Math.floor(Math.random()*10000000000); <?php // random number per ad impression for unique ad ID, ex. multiple impressions on the same page ?>
var clientID = "VL_<?php echo $vl_creative_id;?>" + "_" + rnd; <?php // append random number to creative ID from DF, in order to associate tracking with campaigns ?>
var isTrackingOff = "<?php echo $tracking_off ? 'true' : 'false';?>"; <?php //disables engagement tracking for preview/approval/gallery/wordpress/press case uses ?>
var vlid = "engvlx4ie<?php echo sprintf('%07d', $vl_campaign_id);?>rlp"; <?php // ID for screenshot bot and ad verify ?>
var imp_p = true;
var cmid = "<?php echo (isset($campaign_id)) ? $campaign_id : ''; ?>";
var aid = "<?php echo (isset($advertiser_id)) ? $advertiser_id : ''; ?>";
var pixel_ping_domain = "<?php echo PIXEL_PING_DOMAIN; ?>";
var creative_id = "<?php echo (isset($vl_creative_id)) ? $vl_creative_id : ''; ?>";
var ad_server_type = "<?php echo (isset($ad_server_type)) ? $ad_server_type : '1'; ?>"; <?php // Ad server type. Defaults to DFA. ?>

//============================================================
//============================================================
//============================================================
var flashvars = {}; <?php // object for variables for grandpa/children/html5 ?>
var params = {}; <?php // Flash parameters/variables including clickTag ?>
var forceHTML5 = "true"; <?php // option to force html5 only ad (no flash ) ?>
params.wmode = "window"; <?php // flash background setting, "window", "opaque", or "transparent", TODO: move to variables config ?>
params.bgcolor = ""; <?php // optional to set a custom background color for the Flash file, TODO: move to variables config ?>
//============================================================
//====================INSERT VARIABLES========================
//============================================================
var variables = eval('('+<?php echo $variables_data_obj; ?>+')'); <?php // json variables are inserted here from creaitve_uploader variables config ?>
flashvars = variables.flashvars; <?php // assign json values to the object array for Flash/HTML5 ?>
flashvars.isLoader = "true"; <?php // option to display the loading bar/spinner while ad is loading TODO: move to variables config ?>
<?php //echo (isset($variables_js)) ? json_decode($variables_js) : "//variables_js not found"; ?>
//============================================================
//============================================================
if ( flashvars.forceHTML5 == "true" ) {
	forceHTML5 = flashvars.forceHTML5; <?php // get the forceHTML5 value from the json, TODO: simplify ?>
}
//============================================================
var isHTML5; <?php // varible to know if the ad has an html5 version ?>
<?php echo $html5_initialization; ?>
if (dccreativewidth == "320" && parentURL == "") {
	forceHTML5 = "true"; <?php // if the ad size width is 320px, this must be a mobile ad so force it ?>
}
var noFlash;
var thisURL = window.location.protocol + "://" + window.location.host + "/" + window.location.pathname;
<?php // determine if this is a smart phone ?>
var is_uiwebview = /(iPhone|iPod|iPad).*AppleWebKit(?!.*Safari)/i.test(navigator.userAgent);
var is_safari_or_uiwebview = /(iPhone|iPod|iPad).*AppleWebKit/i.test(navigator.userAgent);
var ua = navigator.userAgent.toLowerCase();
var isAndroid = ua.indexOf("android") > -1;
var isChrome = ua.indexOf('chrome') > -1;
if(isChrome && (isHTML5 || parentURL == "")) { <?php // force HTML if this is chrome and either: we have an HTML creative, or we don't have a flash creative ?>
	forceHTML5 = "true";
}
var youTubeID;
var margintop = 0; <?php // margin to position play video button ?>
var marginleft = 0; <?php // margin to position play video button ?>
//============================================================
//============================================================
//============================================================
var fullscreenImageURL;
var isImageOnlyAd;  <?php // for Flash and HTML5 (no animation), rich media still optional ?>		

var dcnope;  <?php // DFA variable for grandpa ?>
var advurl = "<?php echo isset($landing_page) ? $landing_page : '#'; ?>"; <?php // Landing page URL without tracking, or '#' if not set ?>
var dcadvurl = "";
<?php
	if (!isset($no_engage) || !$no_engage)
	{
?>		switch (ad_server_type)
		{
			case "3":
				advurl = getcOrcanduURL("fas_candu"); <?php // Read adv url from click parameter if Ad server is Frequence ?>
				dcadvurl = escape(advurl);
				break;
			default:
				advurl = '%c%u'; <?php // DFA default landing page ?>
				dcadvurl = escape('%c%u'); <?php // Flash uses escaped URL strings ?>
				break;
		}
<?php	}
?>

var dcminversion = 9;  <?php // minimum version of Flash Player, TODO: move to variables config ?>
var dcmaxversion = 9; <?php // maximum version of Flash Player, TODO: move to variables config ?>
var plugin = false; <?php // TODO: remove if not used ?>

var isSSL;
var grandpaImage;			
var parentAddress; <?php // URL of parent animation SWF ?>
var publisherURL = document.location;
var video_x;
var video_y;
var loadRichImageAd; <?php // for HTML5 static image ads (no animation) ?>
var youtubeURL;
var isMobile;
<?php // engagement variables ?>
var clickCounter=0;
var hoverCounter=0;
var hover2Counter=0;
var videoPlayCounter=0;
var playTimeSeconds=0;
var delayTime=1000;
var buttonTimeout;
var expandCounter=0;
var isVideoPlaying=false;
var engagementTimer;
var videoDuration;
var isVideo;
//============================================================
//============================================================
//============================================================
<?php // fallback function for no console in IE 9 and below, converts colsole.log to alert ?>
var alertFallback = true;
if (typeof console === "undefined" || typeof console.log === "undefined") {
	console = {};
	if (alertFallback) {
		console.log = function(msg) {
			alert(msg);
		};
	} else {
		console.log = function() {};
	}
}
//============================================================
//============================================================
//============================================================
//============================================================
//============================================================
if(parentURL == "") {
	isImageOnlyAd = "true";
} else {
	isImageOnlyAd = "false";
}
if(parentURL == "" && grandpaImg == "") {
	forceHTML5 = "true";
}
<?php // set up secure or non-secure URL paths ?>
if ("https:" == document.location.protocol) {
	isSSL = "true";
	parentAddress = parentURLSSL;
	grandpaImage = grandpaImgSSL;
	fullscreenImageURL = fullscreenURLSSL;
	if (isHD == "true" && dccreativewidth != "320"){ <?php // determine which flash file to load, case: parent without grandpa ?>
		dcnope = grandpaURLSSL;
	} else {
		dcnope = parentAddress;
		isHD = "false";
	}
} else {
	isSSL = "false";
	parentAddress = parentURL;
	grandpaImage = grandpaImg;
	fullscreenImageURL = fullscreenURL;
	if (isHD == "true" && dccreativewidth != "320"){ <?php // determine which flash file to load, case: parent without grandpa ?>
		dcnope = grandpaURL;
	} else {
		dcnope = parentAddress;
		isHD = "false";
	}
}
<?php // if grandpa is loading, tell grandpa what SWF animation he needs to load and append the clickTag ?>
if (isHD != true) { <?php // TODO: change to string "true" ?>
	flashvars.parentURL = parentAddress+'?clickTag='+dcadvurl;	
} else {
	flashvars.parentURL = parentAddress;
}
flashvars.isSSL = isSSL;
flashvars.adSize = adSize;
flashvars.grandpaImage = grandpaImage;
flashvars.publisherURL = publisherURL;
flashvars.clientID = clientID;
flashvars.isHD = isHD;
flashvars.isGrandpaImage = isGrandpaImage;
flashvars.fullscreenImageURL = fullscreenImageURL;
flashvars.isTrackingOff = isTrackingOff;
flashvars.isImageOnlyAd = isImageOnlyAd;

<?php if (isset($dev_tracking_pixel_address) && isset($dev_tracking_pixel_address_ssl)) : ?>
	flashvars.trackingPixelAddress = '<?php echo $dev_tracking_pixel_address; ?>';
	flashvars.trackingPixelAddressSSL = '<?php echo $dev_tracking_pixel_address_ssl; ?>';
<?php endif // dev_tracking_pixel_address ?>

params.movie = dcnope +'?clickTag='+dcadvurl;
params.quality = "autohigh"; <?php // flash quality ?>
params.allowScriptAccess = "always"; <?php // allows javascript/actionscript communication ?>
params.allowFullScreen = "true"; <?php // allows flash to go fullscreen ?>
params.allowNetworking = "all"; <?php // allows cross-network file loading ?>

var attributes = {};
attributes.id = clientID;
attributes.vlid = vlid;

var divID = "div-"+clientID; <?php // unique div ID to load ad into ?>
flashvars.isDebugMode = getBoolean(flashvars.isDebugMode);
if (flashvars.isDebugMode == true){
	console.log("JavaScript: dcnope: " + dcnope);
}

<?php // doubleclick stage variable ?>
var dclkjstag = (('%eenv!'!="j")&&(typeof dclkFlashWrite!="undefined")) ? false : true;
<?php // define the html for the div to write to the document, and then load the ad into ?>
var dcflashtagstring = '<div class="vantagelocal" style="position:relative;width:' + dccreativewidth + 'px;height:' + dccreativeheight + 'px;"><div class="buttons" style="z-index:3; position:absolute;"><div id="playbutton-'+clientID+'"></div></div><span id="clicktag-'+clientID+'"></span><div id='+divID+'></div></div>';

<?php // parse the dynamic geo data and put it in the variables for the ad ?>
flashvars.dynamic_geo = '%g';
<?php
	if (isset($dynamic_geo_default) && $dynamic_geo_default)
	{
		if (isset($messages_data) && isset($messages_data['zips_to_message_id_array']) && isset($messages_data['message_id_to_message_array']))
		{
			$obj_string = "";
			
			foreach ($messages_data['zips_to_message_id_array'] as $zips_to_message_id_array_row)
			{
				$obj_string = $obj_string.'"'.$zips_to_message_id_array_row['zips'].'":'.$zips_to_message_id_array_row['message_id'].',';
			}
			
			$obj_string = rtrim($obj_string,',');
?>			var zips_to_message_id = {<?php echo $obj_string; ?>};
<?php		
			$obj_string = "";
			
			foreach ($messages_data['message_id_to_message_array'] as $message_id_to_message_array_row)
			{
				$obj_string=$obj_string.$message_id_to_message_array_row['message_id'].':"'.$message_id_to_message_array_row['message'].'",';
			}
			
			$obj_string = rtrim($obj_string,',');
?>			var message_id_to_message = {<?php echo $obj_string; ?>};
<?php		}
?>		(function(){
			var deserialize, lookupZip, creativeObject, dynamicGeoMessage, setDynamicGeo, creativeIsReadyForDynamicGeo, init;

			deserialize = function(serial) {
				var chunks, pair, output, i, len, key, value;

				output = null;

				if (typeof serial === 'string' && serial.length) {
					chunks = serial.split('&');
					for (i = 0, len = chunks.length; i < len; i++) {
						pair = chunks[i].split('=');
						if (pair && pair.length === 2) {
							if (output === null) {
								output = {};
							}
							key = decodeURIComponent(pair[0]);
							value = decodeURIComponent(pair[1]);
							output[key] = value;
						}
					}
				}

				return output;
			};

			lookupZip = function(zip, callback) {
				var message_data;

				for (var zips_to_message_id_key in zips_to_message_id)
				{
					if (zips_to_message_id_key.indexOf(","+Number(zip)+",") !== -1)
					{
						var message_id = zips_to_message_id[zips_to_message_id_key];
						message_data = message_id_to_message[message_id];
						break;
					}
				}

				callback(message_data);
			};

			window.setFlashReadyForDynamicGeo = function() {
				creativeIsReadyForDynamicGeo = true;
				return dynamicGeoMessage;
			};

			setDynamicGeo = function(message) {
				var creativeObjectId = '';

				dynamicGeoMessage = message;

				creativeObjectId += window.clientID;
				creativeObject = document.getElementById(creativeObjectId); // declared :29

				if (creativeObject !== null && typeof creativeObject.receiveDynamicGeoData === 'function')
				{
					creativeObject.receiveDynamicGeoData(message);
				}
			};

			init = function() {
				//Execute the dynamic geo logic only if dynamicGeoDefault message is set and adset version id is passed.
				if (typeof variables.flashvars.dynamicGeoDefault !== 'undefined' && variables.flashvars.dynamicGeoDefault !== '' )
				{
					var geoMacro;

					geoMacro = deserialize(variables.flashvars.dynamic_geo);

					if (geoMacro && typeof geoMacro.zp !== 'undefined' && typeof zips_to_message_id !== 'undefined' && typeof message_id_to_message !== 'undefined') {
						lookupZip(geoMacro.zp, function(message) {

							if (typeof message === "undefined" || message === '') {
								message = variables.flashvars.dynamicGeoDefault;
							}

							setDynamicGeo(message);
						});

					} else {
						setDynamicGeo(variables.flashvars.dynamicGeoDefault);
					}
				}
			}
			init();
		})();
<?php	}
?> 	
<?php // calls grandpa via clientID to load the parent when the page has finished loading, polite load ?>
function loadTheParent(id) {   
	if (flashvars.isDebugMode == true){
		console.log("JavaScript: loadTheParent Called in javascript!");
	}
	var flashObj = document.getElementById(id);
	if (flashvars.isDebugMode == true){
		console.log("JavaScript: " + flashObj);
	}
	if (isImageOnlyAd != "true" && noFlash != true) {
		flashObj.loadParentFromJS();
	}
}
<?php // if grandpa, start a countdown for rude loading in case parent did not load ?>
if (isHD == "true"){
	if (flashvars.isDebugMode == true){
		console.log("JavaScript: setTimeout: 3sec in javascript!");
	}
	window.setTimeout("rudeLoad()", 3000);
}
<?php // if grandpa, and the parent has not loaded within 3-seconds, load anyways ?>
function rudeLoad() {
	if (noFlash != true && isHD == "true") {
		if (flashvars.isDebugMode == true){
			console.log("JavaScript: rudeLoad Called in javascript!");
		}
		loadTheParent(clientID);
	}
}
<?php // receive communication from Flash that grandpa is all setup and ready to go, POLITE LOAD ?>
function receiveReadyFromAS3(id) {
	<?php // TODO: handle case if onload is already called, and improve polite loading ?>
	window.onload=loadTheParent(id);
}
<?php // receive communication from Flash for debug and console logs ?>
function receiveTextFromAS3(Txt) {
	if (flashvars.isDebugMode == true){
		console.log(Txt);
	}
}
<?php // determine if Flash and what version ?>
function getFlashVer() {
	var i,a,o,p,s="Shockwave",f="Flash",t=" 2.0",u=s+" "+f,v=s+f+".",rSW=RegExp("^"+u+" (\\d+)");
	if((o=navigator.plugins)&&(p=o[u]||o[u+t])&&(a=p.description.match(rSW)))return a[1];
	else if(!!(window.ActiveXObject))for(i=12;i>0;i--)try{if(!!(new ActiveXObject(v+v+i)))return i}catch(e){}
	return 0;
}
<?php // user has flash and is in the doubleclick stage, best guess ?>
if ((dcminversion<=getFlashVer()) && !dclkjstag && forceHTML5 != "true"){
	dclkFlashWrite(dcflashtagstring);
	if (dccreativewidth == "320") {
		noFlash = false;
   		setupHTML5();	
	}
<?php // user has flash and is not in doubleclick stage, best guess ?>
} else if ((dcminversion<=getFlashVer()) && dclkjstag && forceHTML5 != "true") {
	document.write(dcflashtagstring);
	noFlash = false;
	if (dccreativewidth == "320") {
   		setupHTML5();	
	}
<?php // if user does not have flash and there is an swiffy HTML5 version ?>
} else if (isHTML5 == true) {
	noFlash = true;
	setupHTML5();
<?php // if user does not have flash, there is not swiffy HTML5, but there is a loader_image ?>
} else if (isHTML5 != true && grandpaImage != ""){
	noFlash = true;
	loadRichImageAd = true;
	setupHTML5();
<?php // otherwise, load the backup ?>
} else {
	noFlash = true;
	<?php // set a tag to landing page or click to call phone number for mobile ?>
	if ( getBoolean( flashvars.isClickToCall ) && flashvars.clickToCallNumber != "" && adSize == "320x50" ) {
<?php		if (!isset($no_engage) || !$no_engage)
		{
?>			switch (ad_server_type)
			{
				case "3":
					advurl = getcOrcanduURL("fas_c") + 'tel:'+ flashvars.clickToCallNumber;
					break;
				default:
					advurl = '<?php echo "'+'%c'+'tel:'+ flashvars.clickToCallNumber+'"; ?>';
					break;
			}
<?php		}
?>	}
	document.write('<a onmouseover="onHoverMaster();" target="'+vl_html_target+'" href="'+advurl+'"><img src="' + (isSSL == "true" ? dcgifSSL : dcgif) + '" border=0></a>');
}
//============================================================
//============================================================
//============================================================
function setupHTML5() {
	if (flashvars.isDebugMode == true) {
	    console.log("JavaScript: setupHTML5: adSize: " + adSize);
	}
	<?php // check if mobile ?>
	if(isAndroid || is_uiwebview || is_safari_or_uiwebview) {
		flashvars.autoPlay = false;
		isMobile = true;
	}
	<?php // set URL of play buttons; secure or non-secure ?>
	if (isSSL == "true") {
		playButtonImage = flashvars.playButtonImageURLSSL;
	} else {
		playButtonImage = flashvars.playButtonImageURL;
	}
	<?php // setup ad variables based on ad size, width x height ?>
	switch(adSize) {
		case "300x250":
  			video_x = flashvars.video300_x;
  			video_y = flashvars.video300_y;
  			playButton_x = flashvars.playButton300_x;
  			playButton_y = flashvars.playButton300_y;
  			videoWidth = flashvars.videoWidth300;
  			videoHeight = flashvars.videoHeight300;
			break;
		case "320x50":
			video_x = flashvars.playButton320_x;	//xxx
			video_y = flashvars.playButton320_y;	//xxx
			<?php // if mobile 320x50, do NOT auto-play or auto-load video ?>
			flashvars.autoPlay = false;
			flashvars.isAutoLoadVideo = false;
  			playButton_x = flashvars.playButton320_x;
  			playButton_y = flashvars.playButton320_y;
  			<?php // if mobile ad unit 320x50, set clicktag to landing page or phone number ?>
  			if ( getBoolean( flashvars.isClickToCall ) && flashvars.clickToCallNumber != "" ) {
<?php				if (!isset($no_engage) || !$no_engage)
				{
?>					switch (ad_server_type)
					{
						case "3":
							advurl = getcOrcanduURL("fas_c") + 'tel:'+ flashvars.clickToCallNumber;
							break;
						default:
							advurl = '<?php echo "'+'%c'+'tel:'+ flashvars.clickToCallNumber+'";?>';
							break;
					}
<?php				}
?>			}
			<?php // if mobile 320x50, change play button to the mobile button image ?>
  			if (isSSL == "true") {
  				playButtonImage = flashvars.mobilePlayButtonImageURLSSL;
  			} else {
  				playButtonImage = flashvars.mobilePlayButtonImageURL;
  			}
			break;
		case "336x280":
  			video_x = flashvars.video336_x;
  			video_y = flashvars.video336_y;
  			playButton_x = flashvars.playButton336_x;
  			playButton_y = flashvars.playButton336_y;
  			videoWidth = flashvars.videoWidth336;
  			videoHeight = flashvars.videoHeight336;
			break;
		case "728x90":
  			video_x = flashvars.video728_x;
  			video_y = flashvars.video728_y;
  			playButton_x = flashvars.playButton728_x;
  			playButton_y = flashvars.playButton728_y;
  			videoWidth = flashvars.videoWidth728;
  			videoHeight = flashvars.videoHeight728;
			break;
		case "160x600":
  			video_x = flashvars.video160_x;
  			video_y = flashvars.video160_y;
  			playButton_x = flashvars.playButton160_x;
  			playButton_y = flashvars.playButton160_y;
  			videoWidth = flashvars.videoWidth160;
  			videoHeight = flashvars.videoHeight160;
			break;
		default:
			if (flashvars.isDebugMode == true) {
        		console.log("JavaScript: setupHTML5: * * * ERROR * * * : Default Case Loaded: " );
			}
			video_x = "1";
  			video_y = "1";
  			playButton_x = dccreativewidth / 2;
  			playButton_y = dccreativeheight / 2;
  			videoWidth = "158";
  			videoHeight = "88";
	}
	<?php // if user does not have flash or is mobile, and not image-only ad, ex. swiffy version ?>
	if (noFlash == true && loadRichImageAd != true) {
		//HTML5 anime all
		loadHTML5();
	}
	<?php // if user does have flash and is mobile, TODO: determine this case use ?>
	else if (noFlash != true && dccreativewidth == "320") {
		//HTML5 anime mobile only
		document.getElementById('clicktag-'+clientID).innerHTML = '<a href="' + advurl + '" target="'+vl_html_target+'" onmouseover="onHoverMaster();" onmouseout="onOutMaster();"><span id="clicktag" style="width:100%;height:100%;position:absolute;left:0px;top:0px;z-index:1;background-color:rgba(0,0,0,0)"></span></a>';
	}
	<?php // static loader_image / image-only ad ?>
	else if (loadRichImageAd == true) {
		//rich loader image
		document.write('<div id="'+ vlid +'-'+ clientID +'" class="vantagelocal" style="position:relative;width:' + dccreativewidth + 'px;height:' + dccreativeheight + 'px;" onmouseover="onHoverMaster();" onmouseout="onOutMaster();"><div class="buttons" style="z-index:3; position:absolute;"><div id="playbutton-'+clientID+'"></div></div><div style="position:absolute;left:' + video_x + 'px;top:' + video_y + 'px;z-index:2;" class="video-holder" id="holder"><div id="player-'+ clientID +'"></div></div><a target="'+vl_html_target+'" href="'+ advurl +'"><img src="' + grandpaImage + '" border=0></a></div>');
		<?php // call animation complete function to load rich media, because there is no animation ?>
		animationComplete();
	}
}
<?php // load google swiffy runtime and animation ?>
function loadHTML5() {
<?php echo $html5_setup; ?>
}
<?php // on swiffy animation complete, it will call this function to setup rich media, ie. video ?>
<?php // TODO: add conditional logic to load maps and social here ?>
function animationComplete() {
	if ((noFlash == true || dccreativewidth == "320") && typeof flashvars.videoURL !== 'undefined') {
		youtubeURL = flashvars.videoURL;
		if (flashvars.isDebugMode == true) {
			console.log("JavaScript: animationComplete: isAutoLoadVideo: " + getBoolean(flashvars.isAutoLoadVideo) + " isYoutubeFile: " + isYoutubeFile(flashvars.videoURL));
		}
		<?php // case to load the video automatically, if is video ?>
		if (isYoutubeFile(flashvars.videoURL) == true && getBoolean(flashvars.isAutoLoadVideo) == true) {
			isVideo = true;
			if (flashvars.isDebugMode == true) {
				console.log("JavaScript: animationComplete: Call to Load Video: ");
			}
			loadVideo();
			<?php // case to load a video button, which will load video onclick, if is video ?>
		} else if (isYoutubeFile(flashvars.videoURL) == true && getBoolean(flashvars.isAutoLoadVideo) == false) {
			if (flashvars.isDebugMode == true) {
				console.log("JavaScript: animationComplete: Loading Play Button: margintop: " + margintop + " marginleft: " + marginleft);
			}
			isVideo = true;
			var buttonImage = new Image();
			buttonImage.name = buttonImage;
			buttonImage.onload = adjustImagePosition;
			buttonImage.src = playButtonImage;
		}
	}
}
<?php // base the position of the video play button from image center, and set position ?>
function adjustImagePosition() {
	margintop = -this.height/2;
	marginleft = -this.width/2;
	if (flashvars.isDebugMode == true) {
		console.log("JavaScript: adjustImagePosition: margintop: " + margintop + " marginleft: " + marginleft);
	}
	if ( isMobile != true && adSize != "320x50") {
		document.getElementById('playbutton-'+clientID).innerHTML = '<div id="vl-playbtn" style="position:absolute;cursor:pointer;left:' + playButton_x + 'px;top:' + playButton_y + 'px;margin-top:'+ margintop +'px;margin-left:'+ marginleft +'px;"><img src="' + playButtonImage + '" onclick="clickPlay();"></div>';
	} else {
		document.getElementById('playbutton-'+clientID).innerHTML = '<div id="vl-playbtn" style="position:absolute;left:' + playButton_x + 'px;top:' + playButton_y + 'px;margin-top:'+ margintop +'px;margin-left:'+ marginleft +'px;"><a href="' + youtubeURL + '" target="_blank"><img src="' + playButtonImage + '"></a></div>';
	}
}
//============================================================
//============================================================
//============================================================
function clickHandler( tag ) {
	if ( isVideoPlaying == true ) {
		videoPause();
	}
	clickCounter++;
	loadTrackingPixel( "mcl" + "&cnt=" + clickCounter + "&tag=" + tag );
}
function loadTrackingPixel(t) {
	if (flashvars.isDebugMode == true) {
		console.log( "Vantage: loadTrackingPixel: type: " + t );
	}
	if ( isTrackingOff != "true" ) {
		var pixelURL = new Image();
		if ( isSSL == "true" ) {
			pixelURL.src = flashvars.trackingPixelAddressSSL + "?clientid=" + flashvars.clientID + "&e=" + t;
		} else {
			pixelURL.src = flashvars.trackingPixelAddress + "?clientid=" + flashvars.clientID + "&e=" + t;
		}
	}
}
<?php // on mouse hover, do this, ex. engagement tracking ?>
function onHoverMaster() {
	hoverCounter++;
	loadTrackingPixel("hov" + "&cnt=" + hoverCounter);
	is2secHover = true; <?php // TODO: possibly unnecessary variable, it does work ?>
	if(is2secHover == true) {
		clearTimeout( buttonTimeout );
		buttonTimeout = setTimeout( hover2Seconds, 2000 );
	}
}
function onOutMaster() {
	clearTimeout( buttonTimeout );
	is2secHover = false;
}
<?php // if user hovers over ad for 2-seconds or more ?>
function hover2Seconds() {
	hover2Counter++;
	loadTrackingPixel( "hv2" + "&cnt=" + hover2Counter );
}
<?php // track every second of video play time ?>
function videoPlayTime() {
	playTimeSeconds++;
	loadTrackingPixel("pts" + "&cnt=" + playTimeSeconds);
}
<?php // if user plays video to midpoint of more ?>
function videoMidpoint(){
	loadTrackingPixel("mid");
}
<?php //loadTrackingPixel( "imp" ); // removed (2013-11-21) ?>
<?php // fire impression tracking ?>
//============================================================
//============================================================
//============================================================
<?php // load youtube script ?>
function loadVideo() {
	if (flashvars.isDebugMode == true) {
        console.log("JavaScript: loadVideo: ");
    }
	var tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    var firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
}
<?php // on click of video play button ?>
function clickPlay() {
	if (flashvars.isDebugMode == true) {
		console.log("JavaScript: clickPlay: ");
	}
	loadVideo();
	<?php // option to hide the video play button while video is loaded ?>
	if (getBoolean(flashvars.showRichMenuAlways) != true) {
		document.getElementById('vl-playbtn').innerHTML="";
	}
	videoPlayCounter++;
	loadTrackingPixel("vpc" + "&cnt=" + videoPlayCounter);
	loadTrackingPixel("cpv");
}
<?php // if user replays the video ?>
function replayVideo() {
	if (flashvars.isDebugMode == true) {
	        console.log("JavaScript: replayVideo: ");
	}
	onYouTubeIframeAPIReady();
	<?php // option to hide the video play button while video is loaded ?>
	if (getBoolean(flashvars.showRichMenuAlways) != true) {
		document.getElementById('vl-playbtn').innerHTML="";
	}
	videoPlayCounter++;
	loadTrackingPixel("vpc" + "&cnt=" + videoPlayCounter);
	loadTrackingPixel("cpv");
}
<?php // check if valid youtube URL, match security protocol, and use specific youtube URL base ?>
<?php // TODO: change http/https ?>
function isYoutubeFile(vidURL) {
	if (flashvars.isDebugMode == true) {
		console.log("JavaScript: isYoutubeFile: vidURL: " +vidURL);
	}
	var videoURL;
	if (vidURL.toLowerCase().indexOf("http://www.youtube.com/v/") == 0) {
		youTubeID = vidURL.substr(25, 11);
		if (isSSL == "true") {
			videoURL = "https://www.youtube.com/v/" + youTubeID;
		} else {
			videoURL = "http://www.youtube.com/v/" + youTubeID;
		}
		return true;
	}
	if (vidURL.toLowerCase().indexOf("http://www.youtube.com/watch?v=") == 0) {
		youTubeID = vidURL.substr(31, 11);
		if (isSSL == "true") {
			videoURL = "https://www.youtube.com/v/" + youTubeID;
		} else {
			videoURL = "http://www.youtube.com/v/" + youTubeID;
		}
		return true;
	}
	if (vidURL.toLowerCase().indexOf("https://www.youtube.com/v/") == 0) {
		youTubeID = vidURL.substr(26, 11);
		if (isSSL == "true") {
			videoURL = "https://www.youtube.com/v/" + youTubeID;
		} else {
			videoURL = "http://www.youtube.com/v/" + youTubeID;
		}
		return true;
	}
	if (vidURL.toLowerCase().indexOf("https://www.youtube.com/watch?v=") == 0) {
		youTubeID = vidURL.substr(32, 11);
		if (isSSL == "true") {
			videoURL = "https://www.youtube.com/v/" + youTubeID;
		} else {
			videoURL = "http://www.youtube.com/v/" + youTubeID;
		}
		return true;
	}
	return false;
}
var player;
<?php // youtube script tag, after loaded and ready will fire this function ?>
function onYouTubeIframeAPIReady() {
	if (flashvars.isDebugMode == true) {
		console.log("JavaScript: onYouTubeIframeAPIReady: youTubeID: " + youTubeID);
	}
	player = new YT.Player('player-'+ clientID, {
		height: videoHeight,
		width: videoWidth,
		videoId: youTubeID,
		playerVars: { 
			'enablejsapi': 1, 
			'modestbranding': 0,
			'rel':0,
			'autoplay': getBoolean(flashvars.autoPlay),
			'autohide': 1,
			'iv_load_policy': 3,
			'origin': thisURL,
			'showinfo': 0
		},
		events: {
			'onReady': onPlayerReady,
			'onStateChange': onPlayerStateChange
		}
	});
}
<?php // when youtube is finished setting up the video ?>
function onPlayerReady(event) {
	if (flashvars.isDebugMode == true) {
		console.log("JavaScript: onPlayerReady: ");
	}
	if (getBoolean(flashvars.autoPlay) == true) {
		event.target.playVideo();
	}
}
isPaused = false; <?php // variable for auto-play ?>
<?php // on every state change of youtube, this gets called ?>
function onPlayerStateChange(event) {
	if (flashvars.isDebugMode == true) {
		console.log("JavaScript: onPlayerStateChange: ");
	}
	if (event.data == YT.PlayerState.PLAYING) {
		videoDuration = player.getDuration();
		isVideoPlaying = true;
		engagementTimer = setInterval(secondCounter, 1000);
	}
	if (event.data == YT.PlayerState.PLAYING && !isPaused && getBoolean(flashvars.autoPlay) == true && getBoolean(flashvars.isPlayMuted) == true ) {
		var sec = parseInt(flashvars.numAutoPlaySeconds) * 1000;
		if (flashvars.isDebugMode == true) {
			console.log("JavaScript: onPlayerStateChange: autoPlay: " + getBoolean(flashvars.autoPlay) + " sec: " + sec);
		}
		setTimeout(videoStop, sec);
		videoMute();
		isPaused = true;
	}
	if (event.data == YT.PlayerState.ENDED) {
		if (getBoolean(flashvars.isAutoLoadVideo) == false) {
			document.getElementById('vl-playbtn').innerHTML='<img src="' + playButtonImage + '" onclick="replayVideo();">';
			removeVideo();
		}
		isVideoPlaying = false;
		loadTrackingPixel("vdc");
		clearInterval(engagementTimer);
	}
	if (event.data == YT.PlayerState.PAUSED) {
		clearInterval(engagementTimer);
	}
}
<?php // counts seconds of video play time ?>
function secondCounter() {
	videoPlayTime();
	var currentTime = player.getCurrentTime();
	videoPlayTime();
	if (Math.round(currentTime) == Math.round(videoDuration/2)) {
		videoMidpoint();
	}
}
<?php // stop video and return to begining, example: after auto-load/auto-play complete ?>
function videoStop() {
	if (flashvars.isDebugMode == true) {
		console.log("JavaScript: videoStop: ");
	}
	player.seekTo(0);
	videoUnMute();
	player.stopVideo();
	clearInterval(engagementTimer);
}
function videoPause() {
	if (flashvars.isDebugMode == true) {
		console.log("JavaScript: videoPause: ");
	}
	player.pauseVideo();
	clearInterval(engagementTimer);
}
function videoMute() {
	if (flashvars.isDebugMode == true) {
		console.log("JavaScript: videoMute: ");
	}
	player.mute();
}
function videoUnMute() {
	if (flashvars.isDebugMode == true) {
		console.log("JavaScript: videoUnMute: ");
	}
	player.unMute();
}
function removeVideo() {
	if (flashvars.isDebugMode == true) {
		console.log("JavaScript: removeVideo: ");
	}
	player.destroy();
	clearInterval(engagementTimer);
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

document.write('<script src="' + (isSSL ? coreJSURLSSL : coreJSURL) + '"><\/script>');
if(dccreativewidth != '320' && (forceHTML5 === 'true' || (isHTML5 && noFlash)))		
{		
	if(flashvars.isRichMediaMap === 'true' || flashvars.useShareButtons === 'true')		
	{		
		document.write('<script src="' + (isSSL ? childrenJSURLSSL : childrenJSURL) + '"><\/script>');		
	}		
}
function getParameterByName(name)
{
	if(typeof window.location != "undefined")
	{
		var url = window.location.href;
		name = name.replace(/[\[\]]/g, "\\$&");
		var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
		    results = regex.exec(url);
		if (!results) return '';
		if (!results[2]) return '';
		return decodeURIComponent(results[2].replace(/\+/g, " "));
	}
}
function getcOrcanduURL(param_name)
{
	var corcandu_url = getParameterByName(param_name);	
	if (corcandu_url == "")
	{
		if (param_name == "fas_c" && typeof(fas_c_for_js) != "undefined")
		{
			corcandu_url = unescape(fas_c_for_js);
		}
		else if(param_name == "fas_candu" && typeof(fas_candu_for_js) != "undefined")
		{
			corcandu_url = unescape(fas_candu_for_js);
		}
	}	
	return corcandu_url;
}
function generateUUID()
{
	var d = new Date().getTime();
	
        if (window.performance && typeof window.performance.now === "function"){
		d += performance.now();; //use high-precision timer if available
	}	
	var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
		var r = (d + Math.random()*16)%16 | 0;
		d = Math.floor(d/16);
		return (c=='x' ? r : (r&0x3|0x8)).toString(16);
	});	
	return uuid;
}
function getCookie(cname)
{
	var name = cname + "=";
	var ca = document.cookie.split(';');
	for(var i=0; i<ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1);
		if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
	}
	return "";
}
function setCookie(cname, cvalue, exdays)
{
	var d = new Date();
	d.setTime(d.getTime() + (exdays*24*60*60*1000));
	var expires = "expires="+d.toUTCString();
	document.cookie = cname + "=" + cvalue + "; " + expires +"; path=/";
}
function addLoadEvent(o) {
	var n = window.onload;
	"function" != typeof window.onload ? window.onload = o : window.onload = function() {
	    n && n(), o()
	}
}
addLoadEvent(function(){
	if (document.body != null && aid != '' && cmid != '' && isTrackingOff != "true" && imp_p)
	{
		var brandcdn_uid = "";		
		if(ad_server_type == '3')
		{
			brandcdn_uid = getCookie('brandcdn_uid');	
			if (typeof(brandcdn_uid) == 'undefined' || brandcdn_uid == '')
			{
				brandcdn_uid = generateUUID();
				setCookie('brandcdn_uid',brandcdn_uid,365);
			}
		}
		var m = getParameterByName("fas_m");var r = getParameterByName("fas_r");
		var iframe_element = document.createElement('iframe');
		iframe_element.src = pixel_ping_domain+'/pixel/imp?aid='+aid+'&cid='+cmid+'&imp_ck='+brandcdn_uid+"&m="+m+"&r="+r+"&crid="+creative_id;
		iframe_element.style.display = 'none';
		document.body.appendChild(iframe_element);
	}
});
<?php 
	if (empty($return_as_js) || !$return_as_js)
	{
?>		</script>
		<noscript><a target="_blank" href="%c%u"><img src="<?php echo $ssl_backup_image;?>" border=0></a></noscript>
<?php	}
?>
