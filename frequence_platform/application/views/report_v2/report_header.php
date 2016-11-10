<?php
	// TODO: what to do with this?
	/*
	if($favicon_path != NULL &&
		$favicon_path != '')
	{
		$path_info = pathinfo($favicon_path);
		$extension = $path_info['extension'];
		echo '<link rel="icon"
      type="image/'.$extension.'"
      href="'.$favicon_path.'">';
	}
	*/
?>
<link rel="stylesheet" href="/libraries/external/bootstrap-daterangepicker-1.3.16/daterangepicker-bs2.css"></link>
<link rel="stylesheet" href="/libraries/external/DataTables-1.10.2/media/css/jquery.dataTables.css"></link>
<link href="/libraries/external/select2/select2.css" rel="stylesheet"/>
<link href="/css/report_select2.css?v=<?php echo CACHE_BUSTER_VERSION; ?>" rel="stylesheet"/>
<link href="/libraries/external/bootstrap-multiselect/css/bootstrap-multiselect.css" rel="stylesheet"/>
<link rel="stylesheet" href="/css/whitelabel/report_v2/default/waves.min.css"></link>
<link rel="stylesheet" href="/css/whitelabel/report_v2/default/base.css?v=<?php echo CACHE_BUSTER_VERSION; ?>"></link>

<!-- TODO Remove when Mapbox library upgraded to new version of Leaflet 20160601 - MC -->
<link rel="stylesheet" href="/assets/leaflet/leaflet.css?v=<?php echo CACHE_BUSTER_VERSION; ?>"></link>

<link rel="stylesheet" href="/assets/css/reports_map.css?v=<?php echo CACHE_BUSTER_VERSION; ?>"></link>
<link rel="stylesheet" href="/assets/css/videojs/video-js.css"></script>
<!--[if lte IE 9]>
	<link rel="stylesheet" href="/assets/css/reports_map_ie.css"></link>
<![endif]-->
<!--[if lte IE 8]>
	<script type="text/javascript"> var is_ie8 = true; </script>
<![endif]-->
<link href='https://api.mapbox.com/mapbox.js/v2.4.0/mapbox.css' rel='stylesheet' />

<style>
	<?php
		if(!$are_ad_interactions_accessible)
		{
			echo '
				.ad_interactions_visibility,
				.summary_section td.ad-interactions {
					display:none!important;
				}
			';
		}

		if(!$are_screenshots_accessible)
		{
			echo '
				.screenshots_visibility {
					display:none!important;
				}
			';
		}

		if(!$are_engagements_accessible)
		{
			echo '
				.summary_section .data_column.engagements_column,
				.summary_section td.engagements {
					display: none!important;
				}
			';
		}

		if(!$are_view_throughs_accessible)
		{
			echo '
				.summary_section .data_column.view_throughs_column,
				.summary_section td.view-throughs {
					display: none!important;
				}
				.summary_section .data_column.visits_column,
				.summary_section td.visits {
					display: none!important;
				}
				.summary_section {
					padding: 19px 80px;
				}
			';
		}

		if(!$is_tmpi_accessible)
		{
			echo '
				#summary_leads_cell,
				.summary_section td.leads {
					visibility:hidden;
				}
				#products_nav_section
				{
					text-align: left;
				}
			';
		}

		if($are_tv_impressions_accessible)
		{
			echo '
				.map_tools {
					width:25%;
				}
			';
		}
	?>

	.row_highlight:hover {
		background-color:rgb(209,240,255);
	}

	table.dataTable.order-column tbody tr.row_highlight:hover > .sorting_1,
	table.dataTable.order-column tbody tr.row_highlight:hover > .sorting_2,
	table.dataTable.order-column tbody tr.row_highlight:hover > .sorting_3,
	table.dataTable.display tbody tr.row_highlight:hover > .sorting_1,
	table.dataTable.display tbody tr.row_highlight:hover > .sorting_2,
	table.dataTable.display tbody tr.row_highlight:hover > .sorting_3 {
		background-color:rgb(184,231,255);
	}

	.popover-title{
		display: none;
	}

	.dim_during_campaigns_load_disable {
		opacity: 0.6
	}

	</style>
<!-- // TODO: Scott figure out: #subproduct_table_holder { max-width: 770px; } -->
<!--[if IE 8]>
	<style>
		#subproduct_table_holder { max-width: 770px; }
		#products_nav_section  a.disabled, #overview_lift > a.disabled {
			-ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=20)";
		}

		.dim_during_campaigns_load_disable {
			-ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=60)";
		}
	</style>
<![endif]-->
<!--[if IE 7]>
<style>
		#products_nav_section  a.disabled, #overview_lift > a.disabled {
			filter: alpha(opacity=20);
		}

		.dim_during_campaigns_load_disable {
			filter: alpha(opacity=60);
		}
</style>
<![endif]-->
