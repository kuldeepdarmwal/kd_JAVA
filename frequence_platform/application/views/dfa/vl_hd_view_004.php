<!-- Vantage Local HTML/JS Tag version 4.0.3, authors: MM, SH, MR, Shaggy 2013.07.10 - Interactions etc -->
<SCRIPT LANGUAGE=JavaScript>
<!--
//============================================================
//============================================================
//============================================================
var dcgif = "<?php echo $backup_image;?>";

var vl_html_target = "<?php echo isset($no_engage) ? '' : '_blank';?>";

var isHD = "<?php echo $is_hd ? 'true' : 'false';?>";

var dccreativewidth = "<?php echo $creative_width;?>";
var dccreativeheight = "<?php echo $creative_height;?>";

var parentURL = "<?php echo $open_swf_file;?>";
var parentURLSSL = "<?php echo $ssl_swf_file;?>";

var xmlURL = "<?php echo $open_xml_file;?>";
var xmlURLSSL = "<?php echo $ssl_xml_file;?>";

var isGrandpaImage = "<?php echo $is_gpa ? 'true' : 'false';?>";
var grandpaImg = "<?php echo $open_gpa_image_file;?>";
var grandpaImgSSL = "<?php echo $ssl_gpa_image_file;?>";
var playButtonImage = "";
var fullscreenURL = "<?php echo $open_fullscreen_file;?>"
var fullscreenURLSSL = "<?php echo $ssl_fullscreen_file;?>";

var grandpaURL = "http://ad.vantagelocal.com/grandpa/richgrandpa_200_<?php echo $creative_width;?>.swf";
var grandpaURLSSL = "https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/grandpa/richgrandpa_200_<?php echo $creative_width;?>.swf";

var rnd = Math.floor(Math.random()*10000000000);
var clientID = "VL_<?php echo $vl_creative_id;?>" + "_" + rnd;

var isTrackingOff = "<?php echo $tracking_off ? 'true' : 'false';?>";

var vlid = "engvlx4ie<?php echo sprintf('%07d', $vl_campaign_id);?>rlp";
//============================================================
//============================================================
//============================================================
var swiffyJS = "<?php echo (isset($open_js_file)) ? $open_js_file : ''; ?>";
var swiffyJSSSL = "<?php echo (isset($ssl_js_file)) ? $ssl_js_file : ''; ?>";

var flashvars = {};
var params = {};
var forceHTML5 = "<?php echo (isset($force_html5)) ? $force_html5 : 'false'; ?>";
var isDebugMode = "false";

params.wmode = "window";
params.bgcolor = "";
flashvars.isLoader = "true";
<?php //print '<pre>'; print_r($variables_js); print '</pre>'; ?>;
<?php echo (isset($variables_js)) ? json_decode($variables_js) : "//variables_js not found"; ?>
/*
*****************************************
*****************************************
INSERT VARIABLES HERE FROM "variables.js"
*****************************************
*****************************************
*/
//============================================================
//============================================================
//============================================================
var isHTML5;
if (swiffyJS != "") {
	isHTML5 = true;
}
if (dccreativewidth == "320" && parentURL == "") {
	forceHTML5 = "true";	
}
var swiffyobj;
var noFlash;
var thisURL = window.location.protocol + "://" + window.location.host + "/" + window.location.pathname;
var is_uiwebview = /(iPhone|iPod|iPad).*AppleWebKit(?!.*Safari)/i.test(navigator.userAgent);
var is_safari_or_uiwebview = /(iPhone|iPod|iPad).*AppleWebKit/i.test(navigator.userAgent);
var ua = navigator.userAgent.toLowerCase();
var isAndroid = ua.indexOf("android") > -1;
var youTubeID;
var margintop = 0;
var marginleft = 0;
//============================================================
//============================================================
//============================================================
var fullscreenImageURL;
var isImageOnlyAd;		

var dcswf;
var advurl = "<?php echo isset($no_engage) ? '#' : '%c%u';?>";
var dcadvurl = escape("<?php echo isset($no_engage) ? '' : '%c%u';?>");

var dcminversion = 9;
var dcmaxversion = 9;
var plugin = false;

var isSSL;
var grandpaImage;			
var parentAddress;
var publisherURL = document.location;
var adSize = dccreativewidth + "x" + dccreativeheight;
var urlXML;
var video_x;
var video_y;
var loadRichImageAd;
var youtubeURL;
//============================================================
//============================================================
//============================================================
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
if (isDebugMode == "true"){
   console.log("JavaScript: DART HTML/JS Version 4.0.3");
}
//============================================================
//============================================================
//============================================================
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

if ("https:" == document.location.protocol) {
   isSSL = "true";
   parentAddress = parentURLSSL;
   grandpaImage = grandpaImgSSL;
   urlXML = xmlURLSSL;
   fullscreenImageURL = fullscreenURLSSL;
   swiffyJS = swiffyJSSSL;
   if (isHD == "true" && dccreativewidth != "320"){
       dcswf = grandpaURLSSL;
   } else {
       dcswf = parentAddress;
			 isHD = "false";
   }
} else {
   isSSL = "false";
   parentAddress = parentURL;
   grandpaImage = grandpaImg;
   urlXML = xmlURL;
   fullscreenImageURL = fullscreenURL;
   if (isHD == "true" && dccreativewidth != "320"){
       dcswf = grandpaURL;
   } else {
       dcswf = parentAddress;
			 isHD = "false";
   }
}

if (isHD != true) {
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
flashvars.urlXML = urlXML;
flashvars.isHD = isHD;
flashvars.isGrandpaImage = isGrandpaImage;
flashvars.isDebugMode = isDebugMode;
flashvars.fullscreenImageURL = fullscreenImageURL;
flashvars.isTrackingOff = isTrackingOff;
flashvars.isImageOnlyAd = isImageOnlyAd;

params.movie = dcswf +'?clickTag='+dcadvurl;
params.quality = "autohigh";
params.allowScriptAccess = "always";
params.allowFullScreen = "true";
params.allowNetworking = "all";

var attributes = {};
attributes.id = clientID;
attributes.vlid = vlid;
//============================================================
//============================================================
//============================================================
var divID = "div-"+clientID;

if (isDebugMode == "true"){
    console.log("JavaScript: dcswf: " + dcswf);
}

swfobject.embedSWF(params.movie, divID, dccreativewidth, dccreativeheight, escape(dcmaxversion), false, flashvars, params, attributes);

var dclkjstag = (('%eenv!'!="j")&&(typeof dclkFlashWrite!="undefined")) ? false : true;

var dcflashtagstring = '<div class="vantagelocal" style="position:relative;width:' + dccreativewidth + 'px;height:' + dccreativeheight + 'px;"><div class="buttons" style="z-index:3; position:absolute;"><div id="playbutton-'+clientID+'"></div></div><span id="clicktag-'+clientID+'"></span><div id='+divID+'></div></div>';

//============================================================
//============================================================
//============================================================
function loadTheParent(id) {   
        if (isDebugMode == "true"){
            console.log("JavaScript: loadTheParent Called in javascript!");
        }
        var flashObj = document.getElementById(id);
        if (isDebugMode == "true"){
            console.log("JavaScript: " + flashObj);
        }
        if (isImageOnlyAd != "true" && noFlash != true) {
			flashObj.loadParentFromJS();
		}
}

if (isHD == "true"){
    if (isDebugMode == "true"){
        console.log("JavaScript: setTimeout: 3sec in javascript!");
    }
    window.setTimeout("rudeLoad()", 3000);
}

function rudeLoad() {
	if (noFlash != true && isHD == "true") {
    	if (isDebugMode == "true"){
        	console.log("JavaScript: rudeLoad Called in javascript!");
    	}
    	loadTheParent(clientID);
    }
}

function receiveReadyFromAS3(id) {
    window.onload=loadTheParent(id);
}

function receiveTextFromAS3(Txt) {
    if (isDebugMode == "true"){
        console.log(Txt);
    }
}

function getFlashVer() {
    var i,a,o,p,s="Shockwave",f="Flash",t=" 2.0",u=s+" "+f,v=s+f+".",rSW=RegExp("^"+u+" (\\d+)");
    if((o=navigator.plugins)&&(p=o[u]||o[u+t])&&(a=p.description.match(rSW)))return a[1];
    else if(!!(window.ActiveXObject))for(i=12;i>0;i--)try{if(!!(new ActiveXObject(v+v+i)))return i}catch(e){}
    return 0;
}

if ((dcminversion<=getFlashVer()) && !dclkjstag && forceHTML5 != "true"){
   dclkFlashWrite(dcflashtagstring);
   if (dccreativewidth == "320") {
	   noFlash = false;
   		setupHTML5();	
   }
} else if ((dcminversion<=getFlashVer()) && dclkjstag && forceHTML5 != "true") {
    document.write(dcflashtagstring);
	noFlash = false;
    if (dccreativewidth == "320") {
   		setupHTML5();	
	}
} else if (isHTML5 == true) {
	noFlash = true;
	setupHTML5();
} else if (isHTML5 != true && grandpaImage != ""){
	noFlash = true;
	loadRichImageAd = true;
	setupHTML5();
} else {
	noFlash = true;
	document.write('<a target="'+vl_html_target+'" href="'+advurl+'"><img src="' + dcgif + '" border=0></a>');
}
//============================================================
//============================================================
//============================================================
function setupHTML5() {
	if (isDebugMode == "true") {
        console.log("JavaScript: setupHTML5: adSize: " + adSize);
    }

	if(isAndroid || is_uiwebview || is_safari_or_uiwebview) {
		flashvars.autoPlay = "false";
		//flashvars.isAutoLoadVideo = "false";
	}
	
	if (isSSL == "true") {
		playButtonImage = flashvars.playButtonImageURLSSL;
	} else {
		playButtonImage = flashvars.playButtonImageURL;
	}

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
			flashvars.autoPlay = "false";
			flashvars.isAutoLoadVideo = "false";
			//swiffyobj = flashvars.swiffyobject320;
  			playButton_x = flashvars.playButton320_x;
  			playButton_y = flashvars.playButton320_y;
  			//videoWidth = "320";
  			//videoHeight = "50";
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
			if (isDebugMode == "true") {
        		console.log("JavaScript: setupHTML5: * * * ERROR * * * : Default Case Loaded: " );
			}
			video_x = "1";
  			video_y = "1";
  			playButton_x = dccreativewidth / 2;
  			playButton_y = dccreativeheight / 2;
  			videoWidth = "158";
  			videoHeight = "88";
	}
	if (noFlash == true && loadRichImageAd != true) {
		loadHTML5();
	}
	else if (noFlash != true && dccreativewidth == "320") {
		
		document.getElementById('clicktag-'+clientID).innerHTML = '<a href="' + advurl + '" target="'+vl_html_target+'"><span id="clicktag" style="width:100%;height:100%;position:absolute;left:0px;top:0px;z-index:1;"></span></a>';
	}
	else if (loadRichImageAd == true) {
		document.write('<div id="'+ vlid +'-'+ clientID +'"class="vantagelocal" style="position:relative;width:' + dccreativewidth + 'px;height:' + dccreativeheight + 'px;"><div class="buttons" style="z-index:3; position:absolute;"><div id="playbutton-'+clientID+'"></div></div><div style="position:absolute;left:' + video_x + 'px;top:' + video_y + 'px;z-index:2;" class="video-holder" id="holder"><div id="player-'+ clientID +'"></div></div><a target="'+vl_html_target+'" href="'+ advurl +'"><img src="' + grandpaImage + '" border=0></a></div>');
		animationComplete();
	}
}

function loadHTML5() {
	if (isDebugMode == "true") {
        console.log("JavaScript: loadHTML5: swiffyobj: " + swiffyJS);
    }
    if(!("isSwiffyLoaded" in window) || window.isSwiffyLoaded != true ) {
        window.isSwiffyLoaded = true;
        document.write('<script src="https://www.gstatic.com/swiffy/v7.3/runtime.js"><\/script>');
    }
	document.write('<div id="'+ vlid +'-'+ clientID +'"class="vantagelocal" style="position:relative;width:' + dccreativewidth + 'px;height:' + dccreativeheight + 'px;"><div class="buttons" style="z-index:3; position:absolute;"><div id="playbutton-'+clientID+'"></div></div><div style="position:absolute;left:' + video_x + 'px;top:' + video_y + 'px;z-index:2;" class="video-holder" id="holder"><div id="player-'+ clientID +'"></div></div><a href="' + advurl + '" target="'+vl_html_target+'"><span id="clicktag" style="width:100%;height:100%;position:absolute;left:0px;top:0px;z-index:1;"></span></a><div id="swiffycontainer-'+clientID+'" style="width: ' + dccreativewidth + 'px; height: ' + dccreativeheight + 'px" style="position:absolute;left:0px;top:0px;z-index:0;"></div></div><script type="text/javascript" src="' + swiffyJS + '"><\/script>');
}

function animationComplete() {	
  if ((noFlash == true || dccreativewidth == "320") && typeof flashvars.videoURL !== 'undefined') {
		youtubeURL = flashvars.videoURL;
		if (isDebugMode == "true") {
        	console.log("JavaScript: animationComplete: isAutoLoadVideo: " + flashvars.isAutoLoadVideo + " isYoutubeFile: " + isYoutubeFile(flashvars.videoURL));
    	}
		if (isYoutubeFile(flashvars.videoURL) == true && flashvars.isAutoLoadVideo == "true") {
			if (isDebugMode == "true") {
        		console.log("JavaScript: animationComplete: Call to Load Video: ");
    		}
     	 	loadVideo();
		} else if (isYoutubeFile(flashvars.videoURL) == true && flashvars.isAutoLoadVideo == "false") {
     		if (isDebugMode == "true") {
        		console.log("JavaScript: animationComplete: Loading Play Button: margintop: " + margintop + " marginleft: " + marginleft);
    		}
    		var buttonImage = new Image();
			buttonImage.name = buttonImage;
			buttonImage.onload = adjustImagePosition;
			buttonImage.src = playButtonImage;
   	 	}
   	 }
}
function adjustImagePosition() {
	margintop = -this.height/2;
	marginleft = -this.width/2;
	if (isDebugMode == "true") {
		console.log("JavaScript: adjustImagePosition: margintop: " + margintop + " marginleft: " + marginleft);
	}
	document.getElementById('playbutton-'+clientID).innerHTML = '<div id="playbtn" style="position:absolute;left:' + playButton_x + 'px;top:' + playButton_y + 'px;margin-top:'+ margintop +'px;margin-left:'+ marginleft +'px;"><a href="' + youtubeURL + '" target="_blank"><img src="' + playButtonImage + '"></a></div>';
}

//============================================================
//============================================================
//============================================================
function loadVideo() {
	if (isDebugMode == "true") {
        console.log("JavaScript: loadVideo: ");
    }
	var tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    var firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
}

function isYoutubeFile(vidURL) {
	if (isDebugMode == "true") {
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
function onYouTubeIframeAPIReady() {
	if (isDebugMode == "true") {
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
			'autoplay': flashvars.autoPlay,
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

function onPlayerReady(event) {
	if (isDebugMode == "true") {
        console.log("JavaScript: onPlayerReady: ");
    }
	if (flashvars.autoPlay == "true") {
		event.target.playVideo();
	}
}

isPaused = false;
function onPlayerStateChange(event) {
	if (isDebugMode == "true") {
        console.log("JavaScript: onPlayerStateChange: ");
    }
	if (event.data == YT.PlayerState.PLAYING && !isPaused && flashvars.autoPlay == "true") {
		var sec = parseInt(flashvars.numAutoPlaySeconds) * 1000;
		if (isDebugMode == "true") {
        	console.log("JavaScript: onPlayerStateChange: autoPlay: " + flashvars.autoPlay + " sec: " + sec);
    	}
		setTimeout(videoStop, sec);
		videoMute();
		isPaused = true;
	}
}
function videoStop() {
	if (isDebugMode == "true") {
        console.log("JavaScript: videoStop: ");
    }
    player.seekTo(0);
    videoUnMute();
	player.stopVideo();
}
function videoPause() {
	if (isDebugMode == "true") {
        console.log("JavaScript: videoPause: ");
    }
	player.pauseVideo();
}
function videoMute() {
	if (isDebugMode == "true") {
        console.log("JavaScript: videoMute: ");
    }
	player.mute();
}
function videoUnMute() {
	if (isDebugMode == "true") {
        console.log("JavaScript: videoUnMute: ");
    }
	player.unMute();
}

//XXX if ad is clicked while playing video, pause video

//============================================================
//============================================================
//============================================================
function hasBooleanValue(fvar) {
	if (fvar == undefined) {
		return false;
	} else {
		try {
			var strVar = fvar.toLowerCase();
			if ((strVar == "true") || (strVar == "false")) {
				return true;
			} else {
				return false;
			}
		} catch (err) {
			return false;
		}
	}
	return false;
}

function getBoolean(fvar) {
	if (fvar.toLowerCase() == "true") {
		return true;
	} else {
		return false;
	}
}

function getColorInt(fvar) {
	try {
		return uint(getColorStr(fvar));
	}
	catch (err) {
	}
	return 0x000000;
}

function getColorStr(fvar) {
	if (fvar == undefined) {
		return "";
	}
	var strVar = fvar;
	if (strVar.indexOf("0x") == 0) {
		strVar = strVar.substr(2);
	}
	if (strVar.indexOf("#") == 0) {
		strVar = strVar.substr(1);
	}
	if (strVar.length == 3) {
		strVar = strVar.charAt(0) + strVar.charAt(0) + strVar.charAt(1) + strVar.charAt(1) + strVar.charAt(2) + strVar.charAt(2);
	}
	if (strVar.length != 6) {
		return "";
	}
	else {
		return "0x" + strVar;
	}
}
//============================================================
//============================================================
//============================================================
//-->

</SCRIPT>
<NOSCRIPT><A TARGET="_blank" HREF="%c%u"><IMG SRC="<?php echo $backup_image;?>" BORDER=0></A></NOSCRIPT>
