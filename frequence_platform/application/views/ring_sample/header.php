<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
		<meta name="description" content=""/>
		<meta name="keywords" content=""/>
		<title>Planner</title>

		<link rel="shortcut icon" href="/images/favicon.png">

		<!--	<style type="text/css">
				@font-face { font-family: 
				Neue; src: url('https://s3.amazonaws.com/brandcdn-assets/fonts/BebasNeue.otf'); } 
			</style>
	          -->

		<!-- Link css-->
		<link rel="stylesheet" type="text/css" href="/ring_files/css/ringfonts.css"/>
		<link rel="stylesheet" type="text/css" href="/ring_files/css/creative_demo/creative_demo_style.css"/>

		<link rel="stylesheet" type="text/css" href="/ring_files/css/temp.css"/>
		<link rel="stylesheet" type="text/css" href="/ring_files/css/zice.style.css"/>
		<link rel="stylesheet" type="text/css" href="/ring_files/css/icon.css"/>
		<link rel="stylesheet" type="text/css" href="/libraries/external/font-awesome/css/font-awesome.min.css"/>
		<link rel="stylesheet" type="text/css" href="/libraries/external/font-awesome/css/font-awesome-ie7.min.css"/>
		<link rel="stylesheet" type="text/css" href="/ring_files/css/ui-custom.css"/>
		<link rel="stylesheet" type="text/css" href="/ring_files/css/timepicker.css"/>
		<link rel="stylesheet" type="text/css" href="/ring_files/components/colorpicker/css/colorpicker.css"/>
		<link rel="stylesheet" type="text/css" href="/ring_files/components/elfinder/css/elfinder.css"/>
		<link rel="stylesheet" type="text/css" href="/ring_files/components/datatables/dataTables.css"/>
		<link rel="stylesheet" type="text/css" href="/ring_files/components/validationEngine/validationEngine.jquery.css"/>     
		<link rel="stylesheet" type="text/css" href="/ring_files/components/jscrollpane/jscrollpane.css"/>
		<link rel="stylesheet" type="text/css" href="/ring_files/components/fancybox/jquery.fancybox.css"/>
		<link rel="stylesheet" type="text/css" href="/ring_files/components/tipsy/tipsy.css"/>
		<link rel="stylesheet" type="text/css" href="/ring_files/components/editor/jquery.cleditor.css"/>
		<link rel="stylesheet" type="text/css" href="/ring_files/components/chosen/chosen.css"/>
		<link rel="stylesheet" type="text/css" href="/ring_files/components/confirm/jquery.confirm.css"/>
		<link rel="stylesheet" type="text/css" href="/ring_files/components/sourcerer/sourcerer.css"/>
		<link rel="stylesheet" type="text/css" href="/ring_files/components/fullcalendar/fullcalendar.css"/>
		<link rel="stylesheet" type="text/css" href="/ring_files/components/Jcrop/jquery.Jcrop.css"/>   
			
		<link rel="stylesheet" type="text/css" href="/ring_files/css/slider_utils.css"/>

		<link rel="stylesheet" type="text/css" href="/css/ui.dropdownchecklist.standalone.css"/>

		<link rel="stylesheet" type="text/css" href="/css/lap.css">
		<link rel="stylesheet" type="text/css" href="/css/planner.css">

		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
		<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?libraries=places"></script>
		<script type="text/javascript" src="//www.google.com/jsapi"></script>

		<?php $this->load->view('vl_platform_2/ui_core/js_error_handling'); write_vl_platform_error_handlers_js(); ?>

		<script type="text/javascript" src="/ring_files/js/jquery.min.js"></script>
		<script type="text/javascript" src="/ring_files/components/ui/jquery.ui.min.js"></script> 
		<script type="text/javascript" src="/ring_files/components/ui/jquery.autotab.js"></script>
		<script type="text/javascript" src="/ring_files/components/ui/timepicker.js"></script>
		<script type="text/javascript" src="/ring_files/components/colorpicker/js/colorpicker.js"></script>
		<script type="text/javascript" src="/ring_files/components/checkboxes/iphone.check.js"></script>
		<script type="text/javascript" src="/ring_files/components/elfinder/js/elfinder.full.js"></script>
		<script type="text/javascript" src="/ring_files/components/datatables/dataTables.min.js"></script>
		<script type="text/javascript" src="/ring_files/components/datatables/ColVis.js"></script>
		<script type="text/javascript" src="/ring_files/components/scrolltop/scrolltopcontrol.js"></script>
		<script type="text/javascript" src="/ring_files/components/fancybox/jquery.fancybox.js"></script>
		<script type="text/javascript" src="/ring_files/components/jscrollpane/mousewheel.js"></script>
		<script type="text/javascript" src="/ring_files/components/jscrollpane/mwheelIntent.js"></script>
		<script type="text/javascript" src="/ring_files/components/jscrollpane/jscrollpane.min.js"></script>
		<script type="text/javascript" src="/ring_files/components/spinner/ui.spinner.js"></script>
		<script type="text/javascript" src="/ring_files/components/tipsy/jquery.tipsy.js"></script>
		<script type="text/javascript" src="/ring_files/components/editor/jquery.cleditor.js"></script>
		<script type="text/javascript" src="/ring_files/components/chosen/chosen.js"></script>
		<script type="text/javascript" src="/ring_files/components/confirm/jquery.confirm.js"></script>
		<script type="text/javascript" src="/ring_files/components/validationEngine/jquery.validationEngine.js"></script>
		<script type="text/javascript" src="/ring_files/components/validationEngine/jquery.validationEngine-en.js"></script>
		<script type="text/javascript" src="/ring_files/components/vticker/jquery.vticker-min.js"></script>
		<script type="text/javascript" src="/ring_files/components/sourcerer/sourcerer.js"></script>
		<script type="text/javascript" src="/ring_files/components/fullcalendar/fullcalendar.js"></script>
		<script type="text/javascript" src="/ring_files/components/flot/flot.js"></script>
		<script type="text/javascript" src="/ring_files/components/flot/flot.pie.min.js"></script>
		<script type="text/javascript" src="/ring_files/components/flot/flot.resize.min.js"></script>
		<script type="text/javascript" src="/ring_files/components/flot/graphtable.js"></script>
		<script type="text/javascript" src="/ring_files/components/uploadify/swfobject.js"></script>
		<script type="text/javascript" src="/ring_files/components/uploadify/uploadify.js"></script>
		<script type="text/javascript" src="/ring_files/components/checkboxes/customInput.jquery.js"></script>
		<script type="text/javascript" src="/ring_files/components/effect/jquery-jrumble.js"></script>
		<script type="text/javascript" src="/ring_files/components/filestyle/jquery.filestyle.js"></script>
		<script type="text/javascript" src="/ring_files/components/placeholder/jquery.placeholder.js"></script>
		<script type="text/javascript" src="/ring_files/components/Jcrop/jquery.Jcrop.js"></script>
		<script type="text/javascript" src="/ring_files/components/imgTransform/jquery.transform.js"></script>
		<script type="text/javascript" src="/ring_files/components/webcam/webcam.js"></script>
		<script type="text/javascript" src="/ring_files/components/rating_star/rating_star.js"></script>
		<script type="text/javascript" src="/ring_files/components/dualListBox/dualListBox.js"></script>
		<script type="text/javascript" src="/ring_files/components/smartWizard/jquery.smartWizard.min.js"></script>
		<script type="text/javascript" src="/ring_files/components/maskedinput/jquery.maskedinput.js"></script>
		<script type="text/javascript" src="/ring_files/components/highlightText/highlightText.js"></script>
		<script type="text/javascript" src="/ring_files/components/elastic/jquery.elastic.source.js"></script>
		<script type="text/javascript" src="/ring_files/js/jquery.cookie.js"></script>
		<script type="text/javascript" src="/ring_files/js/zice.custom.js"></script>
		<script type="text/javascript" src="/js/jquery.sparkline.js"></script>
		<script type="text/javascript" src="/js/ui.dropdownchecklist.js"></script>
		<script type="text/javascript" src="/js/highcharts.js"></script>

		<!-- JQuery Scripts -->
		<script type="text/javascript" src="/ring_files/js/pullout.js" ></script>
		<script type="text/javascript" src="/ring_files/js/icon.js" ></script> 

		<script type="text/javascript" src="/ring_files/js/creative_demo/jquery.preloadify.js" ></script> 
		<script type="text/javascript" src="/ring_files/js/slider_utils.js" ></script>
		<script type="text/javascript" src="/ring_files/js/jquery.easing.1.3.js" ></script> 
		<script type="text/javascript" src="/ring_files/js/jquery.quicksand.js" ></script> 

		<?php
			include $_SERVER['DOCUMENT_ROOT'].'/application/views/lap/lap_header.php';
			include $_SERVER['DOCUMENT_ROOT'].'/application/views/smb/media_targeting_header.php';
			include $_SERVER['DOCUMENT_ROOT'].'/application/views/rf/rf_header.php';
		?>

		<link rel="stylesheet" type="text/css" href="/ring_files/css/zice.style.css"/>
		<link rel="stylesheet" type="text/css" href="/ring_files/css/ringfonts.css"/>
	</head>

	<body class="dashborad">

		<div id="header">
			<div id="ghost_title" style="font-family:'Bebas Neue', sans-serif;height: 40px;font-size: 36px;color:#ffffff;position:absolute;padding-left:15px;left:143px;top:5px;margin-top:50px;">Replace Application Header</div>

			<div id="ntdlogo">
				<a href="director"> <img src="/ring_files/images/vantage-local-white-logo.png" width="125" height="14" /> </a>
			</div>
			<div id="businessname"></div>
			<div id="account_info"> 

				<div class="setting" title="Profile Setting"> 
					<a href ="#" class="setting"><class="red"><?php echo $firstname.' '.$lastname;?></a>
					<a href ="#" class="setting" id="collapse" style="display:none;"><class="red"><?php echo $firstname.' '.$lastname;?></a>
					<img src="<?php echo base_url('ring_files/images/gear.png');?>" class="gear"  alt="Profile Setting" >
				</div>
			</div>
		</div>