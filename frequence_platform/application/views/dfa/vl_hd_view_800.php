<!-- v8.0.0 - 2016.06.28 -->
<?php
	if (empty($return_as_js) || !$return_as_js)
	{
?>		<script type="text/javascript">
<?php	}
?>
var dcgif = "<?php echo $open_backup_image;?>"; <?php //URL of the backup image for DFA ?> 
var dcgifSSL = "<?php echo $ssl_backup_image;?>"; <?php //URL of the backup image for DFA ?>
var vl_html_target = "<?php echo isset($no_engage) && empty($landing_page) ? '' : '_blank';?>"; <?php // If approval or preview page, do not open a new window. When publish, open a new window (when clicked). ?>
var isHD = "<?php echo $is_hd ? 'true' : 'false';?>"; <?php // all ads are HD now; use rich grandpa ?>
var dccreativewidth = "<?php echo $creative_width;?>"; <?php // ad size width for DFA and to send grandpa/children/html5 ?>
var dccreativeheight = "<?php echo $creative_height;?>"; <?php // ad size heigth for DFA and to send to grandpa/children/html5 ?>
var adSize = dccreativewidth + "x" + dccreativeheight;
var parentURL = ""; <?php // URL of animation SWF ?>
var parentURLSSL = ""; <?php // secure URL of animation SWF ?>
var isGrandpaImage = "<?php echo $is_gpa ? 'true' : 'false';?>"; <?php // boolean, does this ad use a loader_image ?>
var grandpaImg = "<?php echo $open_gpa_image_file;?>"; <?php // also known as loader_image; image displayed while loading and/or still ads ?>
var grandpaImgSSL = "<?php echo $ssl_gpa_image_file;?>"; <?php // secure loader_image; image displayed while loading and/or still ads ?>
var fullscreenURL = "<?php echo $open_fullscreen_file;?>";  <?php // URL of video fullscreen background image ?>
var fullscreenURLSSL = "<?php echo $ssl_fullscreen_file;?>"; <?php // secure URL of video fullscreen background image ?>
var grandpaURL = ""; <?php // URL or rich grandpa on CDN ?>
var grandpaURLSSL = ""; <?php // secure URL or rich grandpa on CDN ?>
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
var oldvars = {}; <?php // object for variables for grandpa/children/html5 ?>
//============================================================
//====================INSERT VARIABLES========================
//============================================================
var variables = eval('('+<?php echo $variables_data_obj; ?>+')'); <?php // json variables are inserted here from creaitve_uploader variables config ?>
oldvars.isLoader = "true"; <?php // option to display the loading bar/spinner while ad is loading TODO: move to variables config ?>
<?php //echo (isset($variables_js)) ? json_decode($variables_js) : "//variables_js not found"; ?>
//============================================================
//============================================================
oldvars.forceHTML5 = 'true';
if (getBoolean(oldvars.forceHTML5)) {
	forceHTML5 = oldvars.forceHTML5; <?php // get the forceHTML5 value from the json, TODO: simplify ?>
}
//============================================================
var isHTML5; <?php // varible to know if the ad has an html5 version ?>
<?php echo $html5_initialization; // in the case of builder 800, this will include the JavaScript `configuration` variable. ?>

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
var advurl = '<?php echo isset($landing_page) ? $landing_page : '#'; ?>'; <?php // Landing page URL without tracking, or '#' if not set ?>
var dcadvurl = "";

configuration = window.configuration || {};
configuration.click_through_base_url = '';
configuration.click_through_landing_url = '<?php echo isset($landing_page) ? $landing_page : '#'; ?>';

<?php
	if (!isset($no_engage) || !$no_engage)
	{
?>		switch (ad_server_type)
		{
			case "3":
				advurl = getcOrcanduURL("fas_candu"); <?php // Read adv url from click parameter if Ad server is Frequence ?>
				configuration.click_through_base_url = getcOrcanduURL("fas_c");
				configuration.click_through_landing_url = getcOrcanduURL('fas_candu').replace(getcOrcanduURL('fas_c'), '');
				break;
			default:
				advurl = '%c%u'; <?php // DFA default landing page ?>
				configuration.click_through_base_url = '%c';
				configuration.click_through_landing_url = '%u';
				break;
		}
		dcadvurl = escape(advurl);
<?php	}
?>

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
	if (getBoolean(isHD) && dccreativewidth != "320"){ <?php // determine which flash file to load, case: parent without grandpa ?>
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
	if (getBoolean(isHD) && dccreativewidth != "320"){ <?php // determine which flash file to load, case: parent without grandpa ?>
		dcnope = grandpaURL;
	} else {
		dcnope = parentAddress;
		isHD = "false";
	}
}
<?php // if grandpa is loading, tell grandpa what SWF animation he needs to load and append the clickTag ?>
if (isHD != true) { <?php // TODO: change to string "true" ?>
	oldvars.parentURL = parentAddress+'?clickTag='+dcadvurl;	
} else {
	oldvars.parentURL = parentAddress;
}
oldvars.isSSL = isSSL;
oldvars.adSize = adSize;
oldvars.grandpaImage = grandpaImage;
oldvars.publisherURL = publisherURL;
oldvars.clientID = clientID;
oldvars.isHD = isHD;
oldvars.isGrandpaImage = isGrandpaImage;
oldvars.fullscreenImageURL = fullscreenImageURL;
oldvars.isTrackingOff = isTrackingOff;
oldvars.isImageOnlyAd = isImageOnlyAd;

<?php if (isset($dev_tracking_pixel_address) && isset($dev_tracking_pixel_address_ssl)) : ?>
	oldvars.trackingPixelAddress = '<?php echo $dev_tracking_pixel_address; ?>';
	oldvars.trackingPixelAddressSSL = '<?php echo $dev_tracking_pixel_address_ssl; ?>';
<?php else : // default tracking pixel address (since variables object is empty for builders versions using this view) ?>
	oldvars.trackingPixelAddress = 'http://3100e55ffe038d736447-4c272e1e114489a00a7c5f34206ea8d1.r73.cf1.rackcdn.com/px.gif';
	oldvars.trackingPixelAddressSSL = 'https://81e2563e7a7102c7a523-4c272e1e114489a00a7c5f34206ea8d1.ssl.cf1.rackcdn.com/px.gif';
<?php endif // dev_tracking_pixel_address ?>
configuration.engagement_pixel_url = <?php echo $tracking_off == 'true' ? "''" : 'oldvars.trackingPixelAddressSSL'; ?>;
configuration.client_id = clientID;

var attributes = {};
attributes.id = clientID;
attributes.vlid = vlid;

var divID = "div-"+clientID; <?php // unique div ID to load ad into ?>
oldvars.isDebugMode = getBoolean(oldvars.isDebugMode);
if (oldvars.isDebugMode == true){
	console.log("JavaScript: dcnope: " + dcnope);
}

<?php // parse the dynamic geo data and put it in the variables for the ad ?>
oldvars.dynamic_geo = '%g';
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
				if (typeof variables.oldvars.dynamicGeoDefault !== 'undefined' && variables.oldvars.dynamicGeoDefault !== '' )
				{
					var geoMacro;

					geoMacro = deserialize(variables.oldvars.dynamic_geo);

					if (geoMacro && typeof geoMacro.zp !== 'undefined' && typeof zips_to_message_id !== 'undefined' && typeof message_id_to_message !== 'undefined') {
						lookupZip(geoMacro.zp, function(message) {

							if (typeof message === "undefined" || message === '') {
								message = variables.oldvars.dynamicGeoDefault;
							}

							setDynamicGeo(message);
						});

					} else {
						setDynamicGeo(variables.oldvars.dynamicGeoDefault);
					}
				}
			}
			init();
		})();
<?php	}
?> 	
if (isHTML5 == true) {
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
	if ( getBoolean( oldvars.isClickToCall ) && oldvars.clickToCallNumber != "" && adSize == "320x50" ) {
		advurl = '<?php echo isset($no_engage) ? "#" : "'+'%c'+'tel:'+ oldvars.clickToCallNumber+'";?>';
	}
	document.write('<a onmouseover="onHoverMaster();" target="'+vl_html_target+'" href="'+advurl+'"><img src="' + (getBoolean(isSSL) ? dcgifSSL : dcgif) + '" border=0></a>');
}
//============================================================
//============================================================
//============================================================
function setupHTML5() {
	if (oldvars.isDebugMode == true) {
	    console.log("JavaScript: setupHTML5: adSize: " + adSize);
	}
	<?php // check if mobile ?>
	if(isAndroid || is_uiwebview || is_safari_or_uiwebview) {
		oldvars.autoPlay = false;
		isMobile = true;
	}
	<?php // if user does not have flash or is mobile, and not image-only ad, ex. swiffy version ?>
	if (noFlash == true && loadRichImageAd != true) {
		//HTML5 anime all
		loadHTML5();
	}
	<?php // static loader_image / image-only ad ?>
	else if (loadRichImageAd == true) {
		//rich loader image
		document.write('<div id="'+ vlid +'-'+ clientID +'" class="vantagelocal" style="position:relative;width:' + dccreativewidth + 'px;height:' + dccreativeheight + 'px;" onmouseover="onHoverMaster();" onmouseout="onOutMaster();"><a target="'+vl_html_target+'" href="'+ advurl +'"><img src="' + grandpaImage + '" border=0></a></div>');
	}
}
<?php // load google swiffy runtime and animation ?>
function loadHTML5() {
<?php echo $html5_setup; ?>
}
//============================================================
//============================================================
//============================================================
function loadTrackingPixel(t) {
	if (oldvars.isDebugMode == true) {
		console.log( "Vantage: loadTrackingPixel: type: " + t );
	}
	if ( !isHTML5 && isTrackingOff != "true" ) {
		var pixelURL = new Image();
		if (getBoolean(isSSL)) {
			pixelURL.src = oldvars.trackingPixelAddressSSL + "?clientid=" + oldvars.clientID + "&e=" + t;
		} else {
			pixelURL.src = oldvars.trackingPixelAddress + "?clientid=" + oldvars.clientID + "&e=" + t;
		}
	}
}
<?php // on mouse hover, do this, ex. engagement tracking ?>
function onHoverMaster() {
	if(!isHTML5)
	{
		hoverCounter++;
		loadTrackingPixel("hov" + "&cnt=" + hoverCounter);
		is2secHover = true; <?php // TODO: possibly unnecessary variable, it does work ?>
		if(is2secHover == true) {
			clearTimeout( buttonTimeout );
			buttonTimeout = setTimeout( hover2Seconds, 2000 );
		}
	}
}
function onOutMaster() {
	if(!isHTML5)
	{
		clearTimeout( buttonTimeout );
		is2secHover = false;
	}
}
<?php // if user hovers over ad for 2-seconds or more ?>
function hover2Seconds() {
	if(!isHTML5)
	{
		hover2Counter++;
		loadTrackingPixel( "hv2" + "&cnt=" + hover2Counter );
	}
}
<?php //loadTrackingPixel( "imp" ); // removed (2013-11-21) ?>
<?php // fire impression tracking ?>
//============================================================
//============================================================
//============================================================
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
