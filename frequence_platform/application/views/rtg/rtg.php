<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
   <link rel="shortcut icon" href="/images/favicon_blue.png">
   <title>VANTAGE LOCAL | 	Retargeting Demo</title>
   <link href="/css/login_style.css" rel="stylesheet" type="text/css" />
   <link href='https://fonts.googleapis.com/css?family=Lato:100,100italic,300,300italic,400,400italic,700,700italic,900italic,900' rel='stylesheet' type='text/css'>
   <script type="text/javascript" src="/ring_files/rtg_files/rtg.js"></script>

   <script type="text/javascript">
   function MM_preloadImages() { //v3.0
   var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
     var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
							   if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
 }

   function MM_swapImgRestore() { //v3.0
     var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
   }

function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_swapImage() { //v3.0
  var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
								    if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}
</script>


<!-- LOAD jQuery --><script type="text/javascript" src="/js/jquery-1.7.1.min.js"></script>

  </head>

  <!-- LOADING JQUERY UI ONLY TO DEMONSTRATE EASING -->
  <script type="text/javascript" src="/js/jquery-ui-1.8.17.custom.min.js"></script>
  <script type="text/javascript" src="/js/dropdowns.js"></script>


  <body onload="MM_preloadImages('/images/topicon03on.png','/images/topicon02on.png')">
  <div class="wrapper">
  <div class="header">
  <div class="header960">
  <div class="navbar"><a href="http://www.vantagelocal.com"><img src="/images/logo.gif" width="960" height="105" /></a></div>
  <div class="flagbar">
  <div class="flagshadow"></div>
  <div class="flag_left">
  <img src="/images/flag_green.png" width="357" height="167" />
  <div class="innerflag">				
  <h1 class="flagtext">Retargeting Demo <span class="titlelight"></span></h1>
  </div>
  </div>
  <div class="flag_nav"><a href="http://www.vantagelocal.com/ourproduct.html"><img src="/images/topicon01.png" name="icon01" width="66" height="167" id="icon01" onmouseover="MM_swapImage('icon01','','/images/topicon01on.png',1)" onmouseout="MM_swapImgRestore()" /></a><a href="http://www.vantagelocal.com/aboutus.html"><img src="/images/topicon02.png" name="icon02" width="68" height="167" id="icon02" onmouseover="MM_swapImage('icon02','','/images/topicon02on.png',1)" onmouseout="MM_swapImgRestore()" /></a><a href="http://www.vantagelocal.com/case-studies.html"><img src="/images/topicon03.png" name="icon03" width="87" height="167" id="icon03" onmouseover="MM_swapImage('icon03','','/images/topicon03on.png',1)" onmouseout="MM_swapImgRestore()" /></a></div>
  </div>
  </div>
  </div>
  <div class="content" style="height:600px;">
  <div class="content_left" style="height:100%;width:100%;font-size:125%;" align="center">
  <table>
  <tr><div class="rtg-top" align="center">
  <td align="center"><img src="/ring_files/rtg_files/Pictures/arrow_right.png" /></td>
  <td align="center"><img src="/ring_files/rtg_files/Pictures/1.png" /><br><br>Browse The Internet<br>
  <select onchange="site_open()" id="siteselect" name="siteselect" style="width:200px;">
  <?php 
  foreach($sites_response as $v)
{
  echo '<option value="'.str_replace("http://www.","",$v["Site"]).'">'.str_replace("http://www.","",$v["Site"]).'</option>';
}
?>
</select>&nbsp<button type="button" onclick="site_open()">Go</button></td>
  <td align="center"><img src="/ring_files/rtg_files/Pictures/arrow_down.png" /></td>
  </div></tr>
  <tr><div class="rtg-middle" align="center">
  <td align="center"><img src="/ring_files/rtg_files/Pictures/3.png" /><br><br>Go Back to Browse the<br>Internet and See<br>Retargeting Ads</td>
  <td align="center"><iframe id="rtg-frame" width=300 height=250 src="/ring_files/rtg_files/Pictures/not_retargeted.png" frameborder="0" scrolling="no"></iframe></td>
  <td align="center"><img src="/ring_files/rtg_files/Pictures/2.png" /><br><br>Visit Advertiser Site<br>and Pick up<br>Retargeting Cookie</td>
  </div></tr>
  <tr><div class="rtg-bottom" align="center">
  <td align="center"><img src="/ring_files/rtg_files/Pictures/arrow_up.png" /></td>
  <td align="center">Advertiser Sites:<br>
  <select onchange="ad_open()" id="adsiteselect" name="adsiteselect" style="width:200px;">
  <?php
  foreach($ads_response as $v)
{
  echo '<option value="'.$v["Name"]."|".$v["Site"].'">'.$v["friendly_name"].'</option>';
}
?>
</select>&nbsp<button type="button" onclick="ad_open()">Go</button></td>
  <td align="center"><img src="/ring_files/rtg_files/Pictures/arrow_left.png" /></td>
  </div>
  </tr>
  </table>
  <button type="button" onclick="window.location.reload()">Reset Cookies</button>
  </div>
  <div class="clear"></div>
  </div>
  <div class="push"></div>
  </div>
  <div class="footer">
  <div class="footerleft">
  <a href="http://www.vantagelocal.com/index.html"><strong>HOME</strong></a>
  <a href="http://www.vantagelocal.com/case-studies.html"><strong>CASE STUDIES</strong></a>
  <a href="http://www.vantagelocal.com/aboutus.html"><strong>ABOUT US</strong></a>
  <a href="http://www.vantagelocal.com/agencysolutions.html"><strong>AGENCY SOLUTIONS</strong></a> <a href="http://www.vantagelocal.com/ourproduct.html"><strong>OUR PRODUCT</strong></a>
  <a href="http://www.vantagelocal.com/contact.html"><strong>CONTACT US</strong></a>
  <a href="http://www.vantagelocal.com/ourbenefits.html"><strong>OUR BENEFITS</strong></a>
  <a href="http://www.vantagelocal.com/ourteam.html"><strong>OUR TEAM</strong></a>
  <a href="http://www.vantagelocal.com/ourdesigns.html"><strong>OUR DESIGNS</strong></a>
  <a href="http://www.vantagelocal.com/faq.html"><strong>FAQ</strong></a>
  </div>
  <div class="footerright" align="right">
  <div class="footer_casestudies"> 
  <h1><a href="http://www.vantagelocal.com/contact.html">CONTACT <strong>US</strong></a><strong></strong></h1>
  <p> Would you   like to see Vantage Local in action? Sure you would! Let us show you how we can provide an affordable and effective, start-to-finish advertising   solution for your local business.</p>
					
  </div>
  </div>
  </div>
  </body>
  </html>
			      
