<!-- Vantage Local HTML/JS Tag version 7.0.1, authors: MM, SH, MR, Shaggy 2013.10.03 - Interactions etc -->
<?php 
	if (empty($return_as_js) || !$return_as_js)
	{
?>		<SCRIPT LANGUAGE=JavaScript>
<?php	}
?>
var dcgif = "<?php echo $open_backup_image;?>"; <?php //URL of the backup image for DFA ?> 
var dcgifSSL = "<?php echo $ssl_backup_image;?>"; <?php //URL of the backup image for DFA ?>

var vl_html_target = "<?php echo isset($no_engage) ? '' : '_blank';?>"; <?php // If approval or preview page, do not open a new window. When publish, open a new window (when clicked). ?>

var isHD = "<?php echo $is_hd ? 'true' : 'false';?>"; <?php // all ads are HD now; use rich grandpa ?>

var dccreativewidth = "<?php echo $creative_width;?>"; <?php // ad size width for DFA and to send grandpa/children/html5 ?>
var dccreativeheight = "<?php echo $creative_height;?>"; <?php // ad size heigth for DFA and to send to grandpa/children/html5 ?>

var parentURL = "<?php echo $open_swf_file;?>"; <?php // URL of animation SWF ?>
var parentURLSSL = "<?php echo $ssl_swf_file;?>"; <?php // secure URL of animation SWF ?>

//var xmlURL = "<?php //echo $open_xml_file;?>"; <?php // XML path for legacy variables ?>
//var xmlURLSSL = "<?php //echo $ssl_xml_file;?>"; <?php // secure XML path for legacy variables ?>

var isGrandpaImage = "<?php echo $is_gpa ? 'true' : 'false';?>"; <?php // boolean, does this ad use a loader_image ?>
var grandpaImg = "<?php echo $open_gpa_image_file;?>"; <?php // also known as loader_image; image displayed while loading and/or still ads ?>
var grandpaImgSSL = "<?php echo $ssl_gpa_image_file;?>"; <?php // secure loader_image; image displayed while loading and/or still ads ?>
var coreJSURL = "http://ad.vantagelocal.com/core/Core.3.1.2.min.js"; <?php // URL of Core JavaScript on CDN ?>
var coreJSURLSSL = "https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/core/Core.3.1.2.min.js"; <?php // secure URL of Core JavaScript on CDN ?>
var childrenJSURL = "http://ad.vantagelocal.com/children/Children.3.1.2.min.js"; <?php // URL of combined Children JavaScript on CDN ?>
var childrenJSURLSSL = "https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/children/Children.3.1.2.min.js"; <?php // secure URL of combined Children JavaScript on CDN ?>
var shareButtonsURL = "http://ad.vantagelocal.com/test/share-buttons/share-buttons.png"; <?php // URL of SocialChild JavaScript on CDN ?>
var shareButtonsURLSSL = "https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/test/share-buttons/share-buttons.png"; <?php // secure URL of SocialChild JavaScript on CDN ?>
var playButtonImage = "";
var fullscreenURL = "<?php echo $open_fullscreen_file;?>";  <?php // URL of video fullscreen background image ?>
var fullscreenURLSSL = "<?php echo $ssl_fullscreen_file;?>"; <?php // secure URL of video fullscreen background image ?>

var grandpaURL = "http://ad.vantagelocal.com/grandpa/richgrandpa_<?php echo $gpa_version;?>_<?php echo $creative_width;?>.swf"; <?php // URL or rich grandpa on CDN ?>
var grandpaURLSSL = "https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/grandpa/richgrandpa_<?php echo $gpa_version;?>_<?php echo $creative_width;?>.swf"; <?php // secure URL or rich grandpa on CDN ?>

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
var swiffyJS = "<?php echo (isset($open_js_file)) ? $open_js_file : ''; ?>"; <?php // URL of json file for html5 animation using google swiffy ?>
var swiffyJSSSL = "<?php echo (isset($ssl_js_file)) ? $ssl_js_file : ''; ?>"; <?php // secure URL of json file for html5 animation using google swiffy ?>

var flashvars = {}; <?php // object for variables for grandpa/children/html5 ?>
var params = {}; <?php // Flash parameters/variables including clickTag ?>
var forceHTML5 = "<?php echo (isset($force_html5)) ? $force_html5 : 'false'; ?>"; <?php // option to force html5 only ad (no flash ) ?>

params.wmode = "window"; <?php // flash background setting, "window", "opaque", or "transparent", TODO: move to variables config ?>
params.bgcolor = ""; <?php // optional to set a custom background color for the Flash file, TODO: move to variables config ?>
//============================================================
//====================INSERT VARIABLES========================
//============================================================
var variables = eval('('+<?php echo $variables_data_obj; ?>+')'); <?php // json variables are inserted here from creaitve_uploader variables config ?>
flashvars = variables.flashvars; <?php // assign json values to the object array for Flash/HTML5 ?>
flashvars.isLoader = "true"; <?php // option to display the loading bar/spinner while ad is loading TODO: move to variables config ?>
//console.log(flashvars);
<?php //echo (isset($variables_js)) ? json_decode($variables_js) : "//variables_js not found"; ?>
//============================================================
//============================================================
if ( flashvars.forceHTML5 == "true" ) {
	forceHTML5 = flashvars.forceHTML5; <?php // get the forceHTML5 value from the json, TODO: simplify ?>
}
//============================================================
var isHTML5; <?php // varible to know if the ad has an html5 version ?>
if (swiffyJS != "") {
	isHTML5 = true; <?php // if there is a swiffy json file, this ad is html5 ?>
}
if (dccreativewidth == "320" && parentURL == "") {
	forceHTML5 = "true"; <?php // if the ad size width is 320px, this must be a mobile ad so force it ?>
}
var swiffyobj;
var noFlash;
var thisURL = window.location.protocol + "://" + window.location.host + "/" + window.location.pathname;
<?php // determine if this is a smart phone ?>
var is_uiwebview = /(iPhone|iPod|iPad).*AppleWebKit(?!.*Safari)/i.test(navigator.userAgent);
var is_safari_or_uiwebview = /(iPhone|iPod|iPad).*AppleWebKit/i.test(navigator.userAgent);
var ua = navigator.userAgent.toLowerCase();
var isAndroid = ua.indexOf("android") > -1;
var isChrome = ua.indexOf('chrome') > -1;
if(isChrome && isHTML5) {
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

var dcswf;  <?php // DFA variable for grandpa ?>
var advurl = "#";
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
var adSize = dccreativewidth + "x" + dccreativeheight;
//var urlXML;
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
<?php // the SWFObject embed class, TODO: remote/remove for less file weight ?>
var swfobject=function(){var D="undefined",r="object",S="Shockwave Flash",W="ShockwaveFlash.ShockwaveFlash",q="application/x-shockwave-flash",R="SWFObjectExprInst",x="onreadystatechange",O=window,j=document,t=navigator,T=false,U=[h],o=[],N=[],I=[],l,Q,E,B,J=false,a=false,n,G,m=true,M=function(){var aa=typeof j.getElementById!=D&&typeof j.getElementsByTagName!=D&&typeof j.createElement!=D,ah=t.userAgent.toLowerCase(),Y=t.platform.toLowerCase(),ae=Y?/win/.test(Y):/win/.test(ah),ac=Y?/mac/.test(Y):/mac/.test(ah),af=/webkit/.test(ah)?parseFloat(ah.replace(/^.*webkit\/(\d+(\.\d+)?).*$/,"$1")):false,X=!+"\v1",ag=[0,0,0],ab=null;if(typeof t.plugins!=D&&typeof t.plugins[S]==r){ab=t.plugins[S].description;if(ab&&!(typeof t.mimeTypes!=D&&t.mimeTypes[q]&&!t.mimeTypes[q].enabledPlugin)){T=true;X=false;ab=ab.replace(/^.*\s+(\S+\s+\S+$)/,"$1");ag[0]=parseInt(ab.replace(/^(.*)\..*$/,"$1"),10);ag[1]=parseInt(ab.replace(/^.*\.(.*)\s.*$/,"$1"),10);ag[2]=/[a-zA-Z]/.test(ab)?parseInt(ab.replace(/^.*[a-zA-Z]+(.*)$/,"$1"),10):0}}else{if(typeof O.ActiveXObject!=D){try{var ad=new ActiveXObject(W);if(ad){ab=ad.GetVariable("$version");if(ab){X=true;ab=ab.split(" ")[1].split(",");ag=[parseInt(ab[0],10),parseInt(ab[1],10),parseInt(ab[2],10)]}}}catch(Z){}}}return{w3:aa,pv:ag,wk:af,ie:X,win:ae,mac:ac}}(),k=function(){if(!M.w3){return}if((typeof j.readyState!=D&&j.readyState=="complete")||(typeof j.readyState==D&&(j.getElementsByTagName("body")[0]||j.body))){f()}if(!J){if(typeof j.addEventListener!=D){j.addEventListener("DOMContentLoaded",f,false)}if(M.ie&&M.win){j.attachEvent(x,function(){if(j.readyState=="complete"){j.detachEvent(x,arguments.callee);f()}});if(O==top){(function(){if(J){return}try{j.documentElement.doScroll("left")}catch(X){setTimeout(arguments.callee,0);return}f()})()}}if(M.wk){(function(){if(J){return}if(!/loaded|complete/.test(j.readyState)){setTimeout(arguments.callee,0);return}f()})()}s(f)}}();function f(){if(J){return}try{var Z=j.getElementsByTagName("body")[0].appendChild(C("span"));Z.parentNode.removeChild(Z)}catch(aa){return}J=true;var X=U.length;for(var Y=0;Y<X;Y++){U[Y]()}}function K(X){if(J){X()}else{U[U.length]=X}}function s(Y){if(typeof O.addEventListener!=D){O.addEventListener("load",Y,false)}else{if(typeof j.addEventListener!=D){j.addEventListener("load",Y,false)}else{if(typeof O.attachEvent!=D){i(O,"onload",Y)}else{if(typeof O.onload=="function"){var X=O.onload;O.onload=function(){X();Y()}}else{O.onload=Y}}}}}function h(){if(T){V()}else{H()}}function V(){var X=j.getElementsByTagName("body")[0];var aa=C(r);aa.setAttribute("type",q);var Z=X.appendChild(aa);if(Z){var Y=0;(function(){if(typeof Z.GetVariable!=D){var ab=Z.GetVariable("$version");if(ab){ab=ab.split(" ")[1].split(",");M.pv=[parseInt(ab[0],10),parseInt(ab[1],10),parseInt(ab[2],10)]}}else{if(Y<10){Y++;setTimeout(arguments.callee,10);return}}X.removeChild(aa);Z=null;H()})()}else{H()}}function H(){var ag=o.length;if(ag>0){for(var af=0;af<ag;af++){var Y=o[af].id;var ab=o[af].callbackFn;var aa={success:false,id:Y};if(M.pv[0]>0){var ae=c(Y);if(ae){if(F(o[af].swfVersion)&&!(M.wk&&M.wk<312)){w(Y,true);if(ab){aa.success=true;aa.ref=z(Y);ab(aa)}}else{if(o[af].expressInstall&&A()){var ai={};ai.data=o[af].expressInstall;ai.width=ae.getAttribute("width")||"0";ai.height=ae.getAttribute("height")||"0";if(ae.getAttribute("class")){ai.styleclass=ae.getAttribute("class")}if(ae.getAttribute("align")){ai.align=ae.getAttribute("align")}var ah={};var X=ae.getElementsByTagName("param");var ac=X.length;for(var ad=0;ad<ac;ad++){if(X[ad].getAttribute("name").toLowerCase()!="movie"){ah[X[ad].getAttribute("name")]=X[ad].getAttribute("value")}}P(ai,ah,Y,ab)}else{p(ae);if(ab){ab(aa)}}}}}else{w(Y,true);if(ab){var Z=z(Y);if(Z&&typeof Z.SetVariable!=D){aa.success=true;aa.ref=Z}ab(aa)}}}}}function z(aa){var X=null;var Y=c(aa);if(Y&&Y.nodeName=="OBJECT"){if(typeof Y.SetVariable!=D){X=Y}else{var Z=Y.getElementsByTagName(r)[0];if(Z){X=Z}}}return X}function A(){return !a&&F("6.0.65")&&(M.win||M.mac)&&!(M.wk&&M.wk<312)}function P(aa,ab,X,Z){a=true;E=Z||null;B={success:false,id:X};var ae=c(X);if(ae){if(ae.nodeName=="OBJECT"){l=g(ae);Q=null}else{l=ae;Q=X}aa.id=R;if(typeof aa.width==D||(!/%$/.test(aa.width)&&parseInt(aa.width,10)<310)){aa.width="310"}if(typeof aa.height==D||(!/%$/.test(aa.height)&&parseInt(aa.height,10)<137)){aa.height="137"}j.title=j.title.slice(0,47)+" - Flash Player Installation";var ad=M.ie&&M.win?"ActiveX":"PlugIn",ac="MMredirectURL="+O.location.toString().replace(/&/g,"%26")+"&MMplayerType="+ad+"&MMdoctitle="+j.title;if(typeof ab.flashvars!=D){ab.flashvars+="&"+ac}else{ab.flashvars=ac}if(M.ie&&M.win&&ae.readyState!=4){var Y=C("div");X+="SWFObjectNew";Y.setAttribute("id",X);ae.parentNode.insertBefore(Y,ae);ae.style.display="none";(function(){if(ae.readyState==4){ae.parentNode.removeChild(ae)}else{setTimeout(arguments.callee,10)}})()}u(aa,ab,X)}}function p(Y){if(M.ie&&M.win&&Y.readyState!=4){var X=C("div");Y.parentNode.insertBefore(X,Y);X.parentNode.replaceChild(g(Y),X);Y.style.display="none";(function(){if(Y.readyState==4){Y.parentNode.removeChild(Y)}else{setTimeout(arguments.callee,10)}})()}else{Y.parentNode.replaceChild(g(Y),Y)}}function g(ab){var aa=C("div");if(M.win&&M.ie){aa.innerHTML=ab.innerHTML}else{var Y=ab.getElementsByTagName(r)[0];if(Y){var ad=Y.childNodes;if(ad){var X=ad.length;for(var Z=0;Z<X;Z++){if(!(ad[Z].nodeType==1&&ad[Z].nodeName=="PARAM")&&!(ad[Z].nodeType==8)){aa.appendChild(ad[Z].cloneNode(true))}}}}}return aa}function u(ai,ag,Y){var X,aa=c(Y);if(M.wk&&M.wk<312){return X}if(aa){if(typeof ai.id==D){ai.id=Y}if(M.ie&&M.win){var ah="";for(var ae in ai){if(ai[ae]!=Object.prototype[ae]){if(ae.toLowerCase()=="data"){ag.movie=ai[ae]}else{if(ae.toLowerCase()=="styleclass"){ah+=' class="'+ai[ae]+'"'}else{if(ae.toLowerCase()!="classid"){ah+=" "+ae+'="'+ai[ae]+'"'}}}}}var af="";for(var ad in ag){if(ag[ad]!=Object.prototype[ad]){af+='<param name="'+ad+'" value="'+ag[ad]+'" />'}}aa.outerHTML='<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"'+ah+">"+af+"</object>";N[N.length]=ai.id;X=c(ai.id)}else{var Z=C(r);Z.setAttribute("type",q);for(var ac in ai){if(ai[ac]!=Object.prototype[ac]){if(ac.toLowerCase()=="styleclass"){Z.setAttribute("class",ai[ac])}else{if(ac.toLowerCase()!="classid"){Z.setAttribute(ac,ai[ac])}}}}for(var ab in ag){if(ag[ab]!=Object.prototype[ab]&&ab.toLowerCase()!="movie"){e(Z,ab,ag[ab])}}aa.parentNode.replaceChild(Z,aa);X=Z}}return X}function e(Z,X,Y){var aa=C("param");aa.setAttribute("name",X);aa.setAttribute("value",Y);Z.appendChild(aa)}function y(Y){var X=c(Y);if(X&&X.nodeName=="OBJECT"){if(M.ie&&M.win){X.style.display="none";(function(){if(X.readyState==4){b(Y)}else{setTimeout(arguments.callee,10)}})()}else{X.parentNode.removeChild(X)}}}function b(Z){var Y=c(Z);if(Y){for(var X in Y){if(typeof Y[X]=="function"){Y[X]=null}}Y.parentNode.removeChild(Y)}}function c(Z){var X=null;try{X=j.getElementById(Z)}catch(Y){}return X}function C(X){return j.createElement(X)}function i(Z,X,Y){Z.attachEvent(X,Y);I[I.length]=[Z,X,Y]}function F(Z){var Y=M.pv,X=Z.split(".");X[0]=parseInt(X[0],10);X[1]=parseInt(X[1],10)||0;X[2]=parseInt(X[2],10)||0;return(Y[0]>X[0]||(Y[0]==X[0]&&Y[1]>X[1])||(Y[0]==X[0]&&Y[1]==X[1]&&Y[2]>=X[2]))?true:false}function v(ac,Y,ad,ab){if(M.ie&&M.mac){return}var aa=j.getElementsByTagName("head")[0];if(!aa){return}var X=(ad&&typeof ad=="string")?ad:"screen";if(ab){n=null;G=null}if(!n||G!=X){var Z=C("style");Z.setAttribute("type","text/css");Z.setAttribute("media",X);n=aa.appendChild(Z);if(M.ie&&M.win&&typeof j.styleSheets!=D&&j.styleSheets.length>0){n=j.styleSheets[j.styleSheets.length-1]}G=X}if(M.ie&&M.win){if(n&&typeof n.addRule==r){n.addRule(ac,Y)}}else{if(n&&typeof j.createTextNode!=D){n.appendChild(j.createTextNode(ac+" {"+Y+"}"))}}}function w(Z,X){if(!m){return}var Y=X?"visible":"hidden";if(J&&c(Z)){c(Z).style.visibility=Y}else{v("#"+Z,"visibility:"+Y)}}function L(Y){var Z=/[\\\"<>\.;]/;var X=Z.exec(Y)!=null;return X&&typeof encodeURIComponent!=D?encodeURIComponent(Y):Y}var d=function(){if(M.ie&&M.win){window.attachEvent("onunload",function(){var ac=I.length;for(var ab=0;ab<ac;ab++){I[ab][0].detachEvent(I[ab][1],I[ab][2])}var Z=N.length;for(var aa=0;aa<Z;aa++){y(N[aa])}for(var Y in M){M[Y]=null}M=null;for(var X in swfobject){swfobject[X]=null}swfobject=null})}}();return{registerObject:function(ab,X,aa,Z){if(M.w3&&ab&&X){var Y={};Y.id=ab;Y.swfVersion=X;Y.expressInstall=aa;Y.callbackFn=Z;o[o.length]=Y;w(ab,false)}else{if(Z){Z({success:false,id:ab})}}},getObjectById:function(X){if(M.w3){return z(X)}},embedSWF:function(ab,ah,ae,ag,Y,aa,Z,ad,af,ac){var X={success:false,id:ah};if(M.w3&&!(M.wk&&M.wk<312)&&ab&&ah&&ae&&ag&&Y){w(ah,false);K(function(){ae+="";ag+="";var aj={};if(af&&typeof af===r){for(var al in af){aj[al]=af[al]}}aj.data=ab;aj.width=ae;aj.height=ag;var am={};if(ad&&typeof ad===r){for(var ak in ad){am[ak]=ad[ak]}}if(Z&&typeof Z===r){for(var ai in Z){if(typeof am.flashvars!=D){am.flashvars+="&"+ai+"="+Z[ai]}else{am.flashvars=ai+"="+Z[ai]}}}if(F(Y)){var an=u(aj,am,ah);if(aj.id==ah){w(ah,true)}X.success=true;X.ref=an}else{if(aa&&A()){aj.data=aa;P(aj,am,ah,ac);return}else{w(ah,true)}}if(ac){ac(X)}})}else{if(ac){ac(X)}}},switchOffAutoHideShow:function(){m=false},ua:M,getFlashPlayerVersion:function(){return{major:M.pv[0],minor:M.pv[1],release:M.pv[2]}},hasFlashPlayerVersion:F,createSWF:function(Z,Y,X){if(M.w3){return u(Z,Y,X)}else{return undefined}},showExpressInstall:function(Z,aa,X,Y){if(M.w3&&A()){P(Z,aa,X,Y)}},removeSWF:function(X){if(M.w3){y(X)}},createCSS:function(aa,Z,Y,X){if(M.w3){v(aa,Z,Y,X)}},addDomLoadEvent:K,addLoadEvent:s,getQueryParamValue:function(aa){var Z=j.location.search||j.location.hash;if(Z){if(/\?/.test(Z)){Z=Z.split("?")[1]}if(aa==null){return L(Z)}var Y=Z.split("&");for(var X=0;X<Y.length;X++){if(Y[X].substring(0,Y[X].indexOf("="))==aa){return L(Y[X].substring((Y[X].indexOf("=")+1)))}}}return""},expressInstallCallback:function(){if(a){var X=c(R);if(X&&l){X.parentNode.replaceChild(l,X);if(Q){w(Q,true);if(M.ie&&M.win){l.style.display="block"}}if(E){E(B)}}a=false}}}}();
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
   //urlXML = xmlURLSSL;
   fullscreenImageURL = fullscreenURLSSL;
   swiffyJS = swiffyJSSSL;
   if (isHD == "true" && dccreativewidth != "320"){ <?php // determine which flash file to load, case: parent without grandpa ?>
       dcswf = grandpaURLSSL;
   } else {
       dcswf = parentAddress;
       isHD = "false";
   }
} else {
   isSSL = "false";
   parentAddress = parentURL;
   grandpaImage = grandpaImg;
   //urlXML = xmlURL;
   fullscreenImageURL = fullscreenURL;
   if (isHD == "true" && dccreativewidth != "320"){ <?php // determine which flash file to load, case: parent without grandpa ?>
       dcswf = grandpaURL;
   } else {
       dcswf = parentAddress;
       isHD = "false";
   }
}
<?php // if grandpa is loading, tell grandpa what SWF animation he needs to load and append the clickTag ?>
if (isHD != true) { <?php // TODO: change to string "true" ?>
	flashvars.parentURL = parentAddress+'?clickTag='+dcadvurl;	
} else {
	flashvars.parentURL = parentAddress;
}
//============================================================
//============================================================
//============================================================
flashvars.isSSL = isSSL;
flashvars.adSize = adSize;
flashvars.grandpaImage = grandpaImage;
flashvars.publisherURL = publisherURL;
flashvars.clientID = clientID;
//flashvars.urlXML = urlXML;
flashvars.isHD = isHD;
flashvars.isGrandpaImage = isGrandpaImage;
flashvars.isDebugMode = flashvars.isDebugMode;
flashvars.fullscreenImageURL = fullscreenImageURL;
flashvars.isTrackingOff = isTrackingOff;
flashvars.isImageOnlyAd = isImageOnlyAd;
<?php if (isset($dev_tracking_pixel_address) && isset($dev_tracking_pixel_address_ssl)) : ?>
flashvars.trackingPixelAddress = '<?php echo $dev_tracking_pixel_address; ?>';
flashvars.trackingPixelAddressSSL = '<?php echo $dev_tracking_pixel_address_ssl; ?>';
<?php endif // dev_tracking_pixel_address ?>

params.movie = dcswf +'?clickTag='+dcadvurl;
params.quality = "autohigh"; <?php // flash quality ?>
params.allowScriptAccess = "always"; <?php // allows javascript/actionscript communication ?>
params.allowFullScreen = "true"; <?php // allows flash to go fullscreen ?>
params.allowNetworking = "all"; <?php // allows cross-network file loading ?>

var attributes = {};
attributes.id = clientID;
attributes.vlid = vlid;
//============================================================
//============================================================
//============================================================
var divID = "div-"+clientID; <?php // unique div ID to load ad into ?>
flashvars.isDebugMode = getBoolean(flashvars.isDebugMode);
if (flashvars.isDebugMode == true){
    console.log("JavaScript: dcswf: " + dcswf);
}
if (flashvars.isDebugMode == true){
    console.log("JavaScript: dcswf: " + dcswf);
}
<?php // embed Flash using swfObject ?>
swfobject.embedSWF(params.movie, divID, dccreativewidth, dccreativeheight, escape(dcmaxversion), false, flashvars, params, attributes);
<?php // doubleclick stage variable ?>
var dclkjstag = (('%eenv!'!="j")&&(typeof dclkFlashWrite!="undefined")) ? false : true;
<?php // define the html for the div to write to the document, and then load the ad into ?>
var dcflashtagstring = '<div class="vantagelocal" style="position:relative;width:' + dccreativewidth + 'px;height:' + dccreativeheight + 'px;"><div class="buttons" style="z-index:3; position:absolute;"><div id="playbutton-'+clientID+'"></div></div><span id="clicktag-'+clientID+'"></span><div id='+divID+'></div></div>';
//============================================================
//============================================================
//============================================================
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
<?php // is user does not have flash, there is not swiffy HTML5, but there is a loader_image ?>
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
			//swiffyobj = flashvars.swiffyobject320;
  			playButton_x = flashvars.playButton320_x;
  			playButton_y = flashvars.playButton320_y;
  			//videoWidth = "320";
  			//videoHeight = "50";
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
		document.getElementById('clicktag-'+clientID).innerHTML = '<a href="' + advurl + '" target="'+vl_html_target+'" onmouseover="onHoverMaster();" onmouseout="onOutMaster();"><span id="clicktag" style="width:100%;height:100%;position:absolute;left:0px;top:0px;z-index:1;"></span></a>';
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
	if (flashvars.isDebugMode == true) {
        console.log("JavaScript: loadHTML5: swiffyobj: " + swiffyJS);
    }
    if(!("isSwiffyLoaded" in window) || window.isSwiffyLoaded != true ) {
        window.isSwiffyLoaded = true;
        document.write('<script src="https://www.gstatic.com/swiffy/v7.3/runtime.js"><\/script>');
    }
	document.write('<div id="'+ vlid +'-'+ clientID +'"class="vantagelocal" style="position:relative;width:' + dccreativewidth + 'px;height:' + dccreativeheight + 'px;" onmouseover="onHoverMaster();" onmouseout="onOutMaster();"><div class="buttons" style="z-index:3; position:absolute;"><div id="playbutton-'+clientID+'"></div></div><div style="position:absolute;left:' + video_x + 'px;top:' + video_y + 'px;z-index:2;" class="video-holder" id="holder"><div id="player-'+ clientID +'"></div></div><a href="' + advurl + '" target="'+vl_html_target+'"><span id="clicktag" style="width:100%;height:100%;position:absolute;left:0px;top:0px;z-index:1;"></span></a><div id="swiffycontainer-'+clientID+'" style="width: ' + dccreativewidth + 'px; height: ' + dccreativeheight + 'px" style="position:absolute;left:0px;top:0px;z-index:0;"></div></div><script type="text/javascript" src="' + swiffyJS + '"><\/script>');
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
	//window.open(flashvars.clickTag[tag],ad.blankTarget);	//XXX
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
	//console.log("videoDuration: " + videoDuration + " Math.round(currentTime): " + Math.round(currentTime));
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
?>		</SCRIPT>
		<NOSCRIPT><A TARGET="_blank" HREF="%c%u"><IMG SRC="<?php echo $ssl_backup_image;?>" BORDER=0></A></NOSCRIPT>
<?php	}
?>