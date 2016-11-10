<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title><?php echo $title;?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
	<style>
			body {padding-top: 90px;}
			.navbar .brand{padding-top: 10px; }
			input[type="date"]::-webkit-calendar-picker-indicator{
				display:inline-block;
				margin-top:2%;
				float:right;
			}
			input[type="date"]::-webkit-inner-spin-button {
				/* display: none; <- Crashes Chrome on hover */
				-webkit-appearance: none;
				margin: 0;
			}
			th.active_tag_set,
			td.active_tag_set,
			.table-striped tbody > tr:nth-child(even) > td.active_tag_set {
				background-color:#E6FEE6;
			}
			.table-striped tbody > tr:nth-child(odd) > td.active_tag_set {
				background-color:#D2FBD2;
			}

			.vl-clickable-icon
			{
				text-decoration: none;
				color:grey;
			}
			.vl-clickable-icon:hover
			{
				text-decoration: none;
				color:green;
			}

			div.btn-group > a.btn.btn-link.dropdown-toggle:hover
			{
				text-decoration: none;
			}

			#refresh_cache_data_cog_icon {
				color: grey;
			}

			#refresh_cache_data_cog_icon:hover {
				color: green;
			}

			#refresh_cache_data_retweet_icon {
				color: grey;
			}

			#geofencing_data_table th {
				font-size: 12px;
				text-align: center;
			}

			#geofencing_data_table tr td:nth-child(1),
			#geofencing_data_table tr td:nth-child(2) {
				width: 15%;
			}

			#geofencing_data_table tr td:nth-child(1) input,
			#geofencing_data_table tr td:nth-child(2) input {
				width: 90%;
			}

			#geofencing_data_table tr td:nth-child(3) {
				width: 10%;
			}

			#geofencing_data_table tr td:nth-child(3) input {
				width: 80%;
				padding-left: 1em;
			}

			#geofencing_data_table tr td:nth-child(4),
			#geofencing_data_table tr td:nth-child(5) {
				width: 30%;
			}

			#geofencing_data_table tr td:nth-child(4) input,
			#geofencing_data_table tr td:nth-child(5) input {
				width: 90%;
			}

			#geofencing_data_table tr td:nth-child(6) {
				text-align: center;
				vertical-align: middle;
			}

			#geofencing_data_table tr td:nth-child(6) i {
				cursor: pointer;
			}

			#geofencing_data_table input {
				margin: 0;
				border: 0;
				font-size: 11px;
			}

			.geofence_single_add_button {
				float: right;
				position: relative;
				bottom: 3.6em;
				left: 2em;
			}

			#am_ops_owner_div {
				padding-top: 50px;
				margin-left: 0px;
			}
			#am_ops_owner_select2 {
				width:100%;
			}
			#s2id_am_ops_owner_select2 > ul.select2-choices
			{
				-webkit-border-radius: 4px;
				-moz-border-radius: 4px;
				border-radius: 4px;
			}
	</style>
		<link href="/bootstrap/assets/css/bootstrap.css" rel="stylesheet">
		<link href="/bootstrap/assets/css/bootstrap-responsive.css" rel="stylesheet">
		<link rel="stylesheet" type="text/css" href="/libraries/external/DataTables-1.10.2/media/css/jquery.dataTables.css">
		<link rel="stylesheet" href="/libraries/external/font-awesome/css/font-awesome.css">

		<!-- Fav and touch icons -->
		<link rel="apple-touch-icon-precomposed" sizes="144x144" href="../assets/ico/apple-touch-icon-144-precomposed.png">
		<link rel="apple-touch-icon-precomposed" sizes="114x114" href="../assets/ico/apple-touch-icon-114-precomposed.png">
		<link rel="apple-touch-icon-precomposed" sizes="72x72" href="../assets/ico/apple-touch-icon-72-precomposed.png">
		<link rel="apple-touch-icon-precomposed" href="../assets/ico/apple-touch-icon-57-precomposed.png">
		<link rel="shortcut icon" href="/images/flyer_favicon.png">
		<link rel="stylesheet" href="/bootstrap/Bootstrap-Image-Gallery/css/bootstrap-image-gallery.min.css">
		<!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
		<link rel="stylesheet" href="/blueimp/css/jquery.fileupload-ui.css">
		<!--<script type="text/javascript" src="/assets/ad_link_3000/js/jquery.js"></script>-->

		<script type="text/javascript">
			// console.log polyfill for IE
			if ( !window.console ) window.console = { log:function(){} };
		</script>

		<script src="//code.jquery.com/jquery-1.9.1.js"></script>
		<script type="text/javascript" src="/assets/ad_link_3000/js/ajaxfileupload.js"></script>

		<link href="/libraries/external/select2/select2.css" rel="stylesheet"/>
		<script src="/libraries/external/select2/select2.js"></script>
	</head>

	<body>
		<div class="navbar navbar-inverse navbar-fixed-top">
			<div class="navbar-inner">
				<div style="margin-left:50px;margin-right:50px;">
					<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
						<i class="icon-list icon-white"></i>
					</a>
					<a class="brand" style="width:38px;height:30px;padding:0px;margin:0px;padding-top:8px;padding-right:7px;" href="/director"><img src="/images/flyer.png"></a>

					<?php if (!isset($shaved_labels) || !$shaved_labels): ?>
						<div class="nav-collapse collapse">
							<div class="navbar-text pull-right">
								<div class="btn-group">
									<a class="btn btn-inverse" href="#" onclick=""> <?php echo "{$firstname} {$lastname}";?></a>
									<button class="btn btn-inverse dropdown-toggle" data-toggle="dropdown">
										<span class="caret"></span>
									</button>
									<ul class="dropdown-menu">
										<li><a href="/auth/logout" ><i class="icon-share"></i> Log Out</a></li>
										<li><a href="/register" ><i class="icon-user"></i> Register</a></li>
										<li><a href="/change_password"><i class="icon-lock"></i> Change Password</a></li>
										<li><a href="https://brandcdn.wufoo.com/forms/zujobdp0db47wb/" onclick="window.open(this.href,  null, 'height=550, width=680, toolbar=0, location=0, status=1, scrollbars=1, resizable=1'); return false"><i class="icon-edit"></i> Bug/Tech Request</a></li>
										<!-- dropdown menu links -->
									</ul>
								</div>
							</div>
							<ul class="nav">
								<li id="util_dropdown" class="dropdown">
									<a class="dropdown-toggle" id="the_drop" role="button" data-toggle="dropdown" href="#">
										<i class="icon-briefcase icon-white"></i> <i class="icon-chevron-down icon-white"></i>
									</a>
									<ul id="the_dd_menu" class="dropdown-menu" role="menu" aria-labelledby="util_dropdown">
										<!-- <li><a tabindex="-1" target="_blank" href="/register"><i class="icon-user"></i> Register</a></li> -->
										<li><a tabindex="-1" target="_blank" href="/report"><i class="icon-signal"></i> Reports</a></li>
										<li><a tabindex="-1" target="_blank" href="/campaigns_main"><i class="icon-heart"></i> Campaigns Main</a></li>
										<li><a tabindex="-1" target="_blank" href="/advertisers_main"><i class="icon-table"></i> Advertisers Main</a></li>
										<li><a tabindex="-1" target="_blank" href="//vantagelocal.com/upload/"><i class="icon-download-alt"></i> "FTP"</a></li>
										<li><a tabindex="-1" target="_blank" href="/image_scraper"><i class="icon-picture"></i> Image Scraper</a></li>
										<li id='syscode_uploader'><a tabindex="-1" target="_blank" href="/syscode_upload"><i class="icon-refresh"></i> Syscodes</a></li>
										<li class="divider"></li>
										<li><a tabindex="-1" target="_blank" href="/gallery/broadcaster"><i class="icon-bullhorn"></i> Adset Broadcaster</a></li>
										<li><a tabindex="-1" target="_blank" href="/review_screen_shots"><i class="icon-camera"></i> Screenshots</a></li>
									</ul>
								</li>
								<li id="planner_dropdown" class="dropdown">
									<a class="dropdown-toggle" id="the_drop" role="button" data-toggle="dropdown" href="#">
										<i class="icon-globe icon-white"></i> <i class="icon-chevron-down icon-white"></i>
									</a>
									<ul id="the_dd_menu" class="dropdown-menu" role="menu" aria-labelledby="planner_dropdown">
										<li><a tabindex="-1" target="_blank" href="/planner"><i class="icon-leaf"></i> LAP Light</a></li>
										<li><a tabindex="-1" target="_blank" href="/proposal_builder/option_engine"><i class="icon-road"></i> Option Engine</a></li>
										<li><a tabindex="-1" target="_blank" href="/proposal_builder/multi_geo_grabber"><i class="icon-map-marker"></i> Multi Geos</a></li>
										<li><a tabindex="-1" target="_blank" href="/proposal_builder/lap_image_gen"><i class="icon-camera"></i> Image Generator</a></li>
										<li><a tabindex="-1" target="_blank" href="/proposal_builder/edit_sitelist"><i class="icon-pencil"></i> Edit Sitelist</a></li>
										<li><a tabindex="-1" target="_blank" href="/proposal_builder/control_panel"><i class="icon-picture"></i> Display Proposal</a></li>
										<li class="divider"></li>
										<li><a tabindex="-1" target="_blank" href="/proposal_builder/get_all_laps"><i class="icon-list"></i> All LAPs</a></li>
										<li><a tabindex="-1" target="_blank" href="/proposal_builder/get_all_proposals"><i class="icon-inbox"></i> All Proposals</a></li>
										<li class="divider"></li>
										<li><a tabindex="-1" target="_blank" href="/site_tag_admin_controller"><i class="icon-glass"></i> Site Admin</a></li>
										<li><a tabindex="-1" target="_blank" href="/campaigns_main/notes_admin"><i class="icon-list-alt"></i> Notes Admin</a></li>
										<li><a tabindex="-1" target="_blank" href="/campaigns_main/revenue"><i class="icon-plane"></i> Revenue Admin</a></li>
									</ul>
								</li>
								<li id="all_mpqs_menu"><a href="/proposal_builder/get_all_mpqs/proposals"><i class="icon-flag icon-white"></i><span> All MPQs</span></a></li>
								<li id="campaigns_menu"><a href="/campaign_setup"><i class="icon-list-alt icon-white"></i><span> Campaign Setup</span></a></li>
								<li id="tags_menu"><a href="/tag"><i class="icon-bookmark icon-white"></i><span> Tags</span></a></li>
								<li id="adsets_menu" ><a href="/creative_uploader"><i class="icon-edit icon-white"></i><span> Ad Sets</span></a></li>
								<!-- <li id="approvals_menu" ><a href="#"><i class="icon-ok-sign icon-white"></i><span style="text-decoration: line-through;"> Approvals</span></a></li> -->
								<!--  <li id="publish_menu" ><a href="/publisher"><i class="icon-book icon-white"></i><span style="text-decoration: line-through;"> Publisher</span></a></li> -->
								<li id="adgroups_menu" ><a href="/adgroup_setup"><i class="icon-plane icon-white"></i><span> Adgroups</span></a></li>
								<li id="bulk_campaign_upload_menu" ><a href="/bulk_campaign_upload"><i class="icon-upload icon-white"></i><span> Bulk Campaigns</span></a></li>
								<li id="bulk_adset_refresh_menu" ><a href="/bulk_adset_refresh"><i class="icon-retweet icon-white"></i><span> Bulk Adsets</span></a></li>
								<li id="bulk_adgroup_putter_menu" ><a href="/bulk_adgroup_putter"><i class="icon-upload-alt icon-white"></i><span> Bulk Adgroups</span></a></li>
							</ul>
						</div><!--/.nav-collapse -->
					<?php endif; ?>
				</div>
			</div>
		</div>


<?php if (isset($active_menu_item) && (!isset($shaved_labels) || !$shaved_labels)): ?>
	<script>
		document.getElementById("<?php echo $active_menu_item;?>").className = "active";
	</script>
<?php endif; ?>

