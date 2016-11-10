<html>
<head>
	<meta charset="utf-8"> 
	
	<title>VANTAGE LOCAL | Online Display Advertising</title>
	<link rel="shortcut icon" href="/images/favicon.png">

<?php
echo "\n";
echo '<script type="text/javascript">'."\n";
echo '//file: index_header.php<br />'."\n";
echo '</script>'."\n";
?>

	<link rel="stylesheet" href="/css/accordion-a.css" />
	<link rel="stylesheet" href="/css/accordion-a.minimal.css" />
	<link rel="stylesheet" href="/css/dashboard_sticky.css" type="text/css" />
	<link rel="stylesheet" href="/css/dashboard_vl.css" type="text/css" />
	
	<link rel="stylesheet" type="text/css" href="/css/dashboard_style.css" />
	<link rel="stylesheet" type="text/css" href="/js/js_calendar/development-bundle/themes/ui-darkness/jquery.ui.all.css"> 
	<script type="text/javascript">
		<?php include_once $_SERVER['DOCUMENT_ROOT'].'/dashboard_functions/functions_js.php'; ?>
	</script>
	
	<link rel="stylesheet" href="/js/js_calendar/development-bundle/themes/ui-darkness/jquery.ui.all.css"> 
	<script src="/js/js_calendar/development-bundle/jquery-1.6.2.js"></script> 
	<script src="/js/js_calendar/development-bundle/ui/jquery.ui.core.js"></script> 
	<script src="/js/js_calendar/development-bundle/ui/jquery.ui.widget.js"></script> 
	<script src="/js/js_calendar/development-bundle/ui/jquery.ui.datepicker.js"></script> 

	<script> 
	$(function() {
		$( "#datepicker1" ).datepicker({dateFormat: 'yy-mm-dd'});
	});
	$(function() {
		$( "#datepicker2" ).datepicker({dateFormat: 'yy-mm-dd'});
	});
	$(function() {
		$( "#datepicker3" ).datepicker({dateFormat: 'yy-mm-dd'});
	});
	</script> 
	
	<script type="text/javascript">
	function today_month(){
		d = new Date();
		mo = d.getMonth();
		month_name = new Array();
		month_name[0]= "JAN";
		month_name[1]= "FEB";
		month_name[2]= "MAR";
		month_name[3]= "APR";
		month_name[4]= "MAY";
		month_name[5]= "JUN";
		month_name[6]= "JUL";
		month_name[7]= "AUG";
		month_name[8]= "SEP";
		month_name[9]= "OCT";
		month_name[10]= "NOV";
		month_name[11]= "DEC";
		return month_name[mo];
	}
	function today_day(){
		d = new Date();
		return d.getDate();
	}
	</script>
	<script>
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

	<style type="text/css">
	body {
		text-decoration:none;}
	a:link {
		text-decoration: none;
	}
	a:visited {
		text-decoration: none;
	}
	a:hover {
		text-decoration: none;
	}
	a:active {
		text-decoration: none;
	}
	</style>
</head>
