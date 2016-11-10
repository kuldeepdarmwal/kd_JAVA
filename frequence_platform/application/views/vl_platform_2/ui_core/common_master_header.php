<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title><?php echo $title;?></title>
		<meta name="viewport" content="width=device-width">
		<meta name="description" content="">
		<meta name="author" content="">

		<link rel="shortcut icon" href="<?php echo $master_header_data['partner_favicon']; ?>"/>
<?php		if (isset($master_header_data['only_include_nav_css']) && $master_header_data['only_include_nav_css'])
		{
?>			<link rel="stylesheet" href="/bootstrap/assets/css/bootstrap-nav.css"/>
<?php		}
		else
		{
?>			<link rel="stylesheet" href="/bootstrap/assets/css/bootstrap.css"/>
<?php		}
?>		<link rel="stylesheet" href="/bootstrap/assets/css/bootstrap-responsive.css"/>
		<link rel="stylesheet" href="/libraries/external/font-awesome/css/font-awesome.css"/>
		<link rel="stylesheet" href="/css/whitelabel/base_ui.css?v=<?php echo CACHE_BUSTER_VERSION; ?>"/>
		<?php 
			if (!empty($master_header_data['partner_ui_css_path']))
			{
				echo '<link rel="stylesheet" href="'.S3_ASSETS_PATH.$master_header_data['partner_ui_css_path'].'?v='.CACHE_BUSTER_VERSION.'"/>';
			}
		?>

		<script type="text/javascript">
			// console.log fix for IE
			if ( !window.console ) window.console = { log:function(){} };
		</script>

<?php

if(isset($feature_html_head_view_path) && !empty($feature_html_head_view_path))
{
	$this->load->view($feature_html_head_view_path);
}
if(isset($subfeature_html_head_view_path) && !empty($subfeature_html_head_view_path))
{
	$this->load->view($subfeature_html_head_view_path);
}

?>
		<!-- start Mixpanel --><script type="text/javascript">(function(e,b){if(!b.__SV){var a,f,i,g;window.mixpanel=b;b._i=[];b.init=function(a,e,d){function f(b,h){var a=h.split(".");2==a.length&&(b=b[a[0]],h=a[1]);b[h]=function(){b.push([h].concat(Array.prototype.slice.call(arguments,0)))}}var c=b;"undefined"!==typeof d?c=b[d]=[]:d="mixpanel";c.people=c.people||[];c.toString=function(b){var a="mixpanel";"mixpanel"!==d&&(a+="."+d);b||(a+=" (stub)");return a};c.people.toString=function(){return c.toString(1)+".people (stub)"};i="disable time_event track track_pageview track_links track_forms register register_once alias unregister identify name_tag set_config people.set people.set_once people.increment people.append people.union people.track_charge people.clear_charges people.delete_user".split(" ");
		for(g=0;g<i.length;g++)f(c,i[g]);b._i.push([a,e,d])};b.__SV=1.2;a=e.createElement("script");a.type="text/javascript";a.async=!0;a.src="undefined"!==typeof MIXPANEL_CUSTOM_LIB_URL?MIXPANEL_CUSTOM_LIB_URL:"file:"===e.location.protocol&&"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js".match(/^\/\//)?"https://cdn.mxpnl.com/libs/mixpanel-2-latest.min.js":"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js";f=e.getElementsByTagName("script")[0];f.parentNode.insertBefore(a,f)}})(document,window.mixpanel||[]);
		mixpanel.init("<?php echo $this->config->item('mixpanel_token'); ?>");</script><!-- end Mixpanel -->
	</head>

	<body>
		<div class="navbar navbar-fixed-top">
			<div class="navbar-inner">
				<div id="left_nav_toggle" class="navbar-text pull-left" style="padding: 13px 0px 0px 13px;">
					<div class="btn-group">
						<a class="left_nav_toggle" href="#"></a>
					</div>  
				</div>
				<div style="margin-left:50px;margin-right:50px;">
					<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
						<i class="icon-list icon-white"></i>
					</a>
					<a class="brand" id="vl_header_partner_img" href="/director"><img id="vl_header_partner_img_element" src="<?php echo $master_header_data['partner_header_image']; ?>"></a>
<?php if (!isset($shaved_labels) || !$shaved_labels): ?>
					
					<div class="nav-collapse collapse">
						<?php
						if($master_header_data['is_logged_in'] == true)
						{
						?>

						<div class="navbar-text pull-right">
							<div class="btn-group">
							<a class="pull_nav_toggle" href="#"></a>
						</div>
						</div>
						<div class="pull_nav">
							<ul class="mobile_main_nav">
								<?php
									$buttons_data = $master_header_data['buttons_data'];
									foreach($buttons_data as $button_data)
									{
										$list_item_html = '
										<li id="pull_'.$button_data['button_id'].'_feature_link">'.
										'<a href="'.$button_data['link_url'].'">'.
										'<i class="'.$button_data['icon_class'].'"></i>'.
										'<span> '.$button_data['button_text'].'</span>'.
										'</a></li>';
										echo $list_item_html."\n";
									}
								?>
							</ul>
							<ul class="user_nav">
								<h3><?php echo $master_header_data['firstname']." ".$master_header_data['lastname'];?></h3>
								<li><a href="/auth/logout" ><i class="icon-share"></i> Log Out</a></li>
								<?php if ($master_header_data['can_create_helpdesk_ticket']) { ?>
								<li><a href="mailto:helpdesk@brandcdn.com?subject=HelpDesk request from <?php echo $master_header_data['firstname'].' '.$master_header_data['lastname']; ?>" target="_blank"><i class="icon-question-sign"></i> Help</a></li>
								<?php } ?>
								<?php if ($master_header_data['can_register']) { ?>
								<li><a href="/register" ><i class="icon-user"></i> Register</a></li>
								<?php } ?>
								<li><a href="/change_password"><i class="icon-lock"></i> Change Password</a></li>
								<?php if ($master_header_data['third_party_linker']) { ?>
								<li><a href="/linker" ><i class="icon-cogs"></i> Accounts</a></li>
								<?php } ?>
								<?php if ($master_header_data['edit_advertisers']) { ?>
								<li><a href="/advertisers" ><i class="icon-building"></i> Advertisers</a></li>
								<?php } ?>
                                                                <?php if ($master_header_data['partners']) { ?>
								<li><a href="/partners" ><i class="icon-sitemap"></i> Partners</a></li>
								<?php } ?>
								<?php if ($master_header_data['user_editor']) { ?>
								<li><a href="/user_editor" ><i class="icon-wrench"></i> User Editor</a></li>
								<?php } ?>
								<?php if ($master_header_data['access_manager']) { ?>
								<li><a href="/access_manager" ><i class="icon-group"></i> Access Manager</a></li>
								<?php } ?>
								<?php if ($master_header_data['ad_machina_manager']) { ?>
								<li><a href="/vab/" ><i class="icon-play-circle"></i> Video Ad Builder</a></li>
								<?php } ?>
							</ul>
							<!-- dropdown menu links -->
						</div>

						<?php
						}
						else
						{
						?>
						<div class="navbar-text pull-right login"><a href="/login">Login</a></div>
							
						<?php 
						}
						?>

						<ul class="nav">
							<?php
								$buttons_data = $master_header_data['buttons_data'];
								foreach($buttons_data as $button_data)
								{
									$list_item_html = '
									<li id="'.$button_data['button_id'].'_feature_link">'.
									'<a href="'.$button_data['link_url'].'">'.
									'<i class="'.$button_data['icon_class'].'"></i>'.
									'<span> '.$button_data['button_text'].'</span>'.
									'</a></li>';
									echo $list_item_html."\n";
								}
							?>
						</ul>
					</div>
<?php endif; ?>
				</div>
			</div>
		</div>

<?php if ($master_header_data['has_seen_browser_warning'] === false): ?>
	<!--[if lt IE 9]>
		<script>
			window.alert('Your browser version is no longer supported by our platform, and some features may not work correctly. Please upgrade your browser to the latest version, or download Google Chrome for the best browsing experience.');
		</script>
	<![endif]-->
<?php endif; ?>

<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-46147145-1', 'brandcdn.com');
  ga('send', 'pageview');

</script>
