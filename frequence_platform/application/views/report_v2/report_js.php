<script type="text/javascript" src="/js/highcharts.js"></script>

<script type="text/javascript" src="/libraries/external/bootstrap-daterangepicker-1.3.16/moment.js"></script>
<script type="text/javascript" src="/libraries/external/bootstrap-daterangepicker-1.3.16/daterangepicker.js"></script>

<script type="text/javascript" src="/libraries/external/select2/select2.js"></script>
<script type="text/javascript" src="/libraries/external/bootstrap-multiselect/js/bootstrap-multiselect.js"></script>

<script type="text/javascript" src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/plug-ins/9dcbecd42ad/integration/bootstrap/2/dataTables.bootstrap.js"></script>
<script type="text/javascript" src="/js/jquery.sparkline-2.1.2.js"></script>
<script type="text/javascript" src="/assets/js/videojs/videojs-ie8.min.js"></script>
<script src="/assets/js/videojs/video.js"></script>


<?php if($mixpanel_info['user_unique_id']) : ?>
<script type="text/javascript">

	var timezone_offset = (new Date().getTimezoneOffset() / 60) * -1; // getTimezoneOffset returns offset in minutes, so divide by 60

	mixpanel.identify("<?php echo $mixpanel_info['user_unique_id']; ?>");
	mixpanel.people.set({
		"$first_name": "<?php echo $mixpanel_info['user_firstname']; ?>",
		"$last_name": "<?php echo $mixpanel_info['user_lastname']; ?>",
		"$email": "<?php echo $mixpanel_info['user_email']; ?>",
		"user_type": "<?php echo $mixpanel_info['user_role']; ?>",
		"is_super": "<?php echo $mixpanel_info['user_is_super']; ?>",
		"partner_id": "<?php echo $mixpanel_info['user_partner_id']; ?>",
		"partner_name": "<?php echo $mixpanel_info['user_partner']; ?>",
		"cname": "<?php echo $mixpanel_info['user_cname']; ?>",
		"advertiser_name": "<?php echo $mixpanel_info['user_advertiser_name']; ?>",
		"advertiser_id": "<?php echo $mixpanel_info['user_advertiser_id']; ?>",
		"timezone_offset": timezone_offset,
		"last_page_visit": "<?php echo $mixpanel_info['page_access_time']; ?>",
		"last_page_visited": "/report"
	});
	mixpanel.people.set_once({
		"$created": "<?php echo $mixpanel_info['page_access_time']; ?>",
		"page_views": 0
	});
	mixpanel.people.increment("page_views");

</script>
<?php endif; ?>
<script type="text/javascript">
	var role = '<?php echo $role ?>';
	var g_tabs_order_to_html_id = [
		<?php
			echo "
			'".report_product_tab_html_id::overview_product."',
			'".report_product_tab_html_id::display_product."',
			'".report_product_tab_html_id::pre_roll_product."',
			'".report_product_tab_html_id::targeted_clicks_product."',
			'".report_product_tab_html_id::targeted_inventory_product."',
			'".report_product_tab_html_id::targeted_directories_product."',
			'".report_product_tab_html_id::targeted_tv_product."'
			";
		?>
	];

	$(function()
	{
	<?php if($are_ad_interactions_accessible != 1) { ?>
		$(".ad_interactions_visibility").hide();
	<?php } if($are_screenshots_accessible != 1) { ?>
		$(".screenshots_visibility").hide();
	<?php } ?>

		g_current_start_date = '<?php echo ''.$start_date.''; ?>';
		g_current_end_date = '<?php echo ''.$end_date.''; ?>';
	});

	var g_overview_chart;
	var g_overview_chart_function;
	var g_are_ad_interactions_visible = <?php echo $are_ad_interactions_accessible == 1 ? 'true' : 'false'; ?>;

	$('#overview_tab_content .card.ajax-load').hide(0);

	function apply_graph_data(
		graph_data,
		value_changed,
		are_leads_visible
	)
	{
		var categories_data = new Array();
		var all_impressions_data = new Array();
		var retargeting_impressions_data = new Array();
		var all_clicks_data = new Array();
		var retargeting_clicks_data = new Array();
		var engagements_data = new Array();
		var leads_data = new Array();
		var visits_data = new Array();

		for(var ii=0; ii<graph_data.length; ii++)
		{
			var date_data = graph_data[ii];
			categories_data[ii] = date_data['date'];
			all_impressions_data[ii] = +date_data['total_impressions'];
			all_clicks_data[ii] = +date_data['total_clicks'];
			retargeting_impressions_data[ii] = +date_data['rtg_impressions'];
			engagements_data[ii] = +date_data['engagements'];
			leads_data[ii] = +date_data['leads'];
			visits_data[ii] = +date_data['total_visits'];
		}

		var is_all_impressions_visible = true;
		var is_retargeting_impressions_visible = true;
		var is_all_clicks_visible = true;
		var is_engagements_visible = true;
		var is_visits_visible = true;
		var is_leads_visible = true;
		if(typeof(g_overview_chart) !== "undefined" &&
			typeof(g_overview_chart.series) !== "undefined"
		)
		{
			if(typeof(g_overview_chart.series[0]) !== "undefined" &&
				typeof(g_overview_chart.series[0].visible) !== "undefined")
			{
				is_all_impressions_visible = g_overview_chart.series[0].visible;
			}
			if(typeof(g_overview_chart.series[1]) !== "undefined" &&
				typeof(g_overview_chart.series[1].visible) !== "undefined")
			{
				is_retargeting_impressions_visible = g_overview_chart.series[1].visible;
			}
			<?php
			if($are_view_throughs_accessible == 1)
			{
				echo '
					if(typeof(g_overview_chart.series[2]) !== "undefined" &&
						typeof(g_overview_chart.series[2].visible) !== "undefined")
					{
						is_visits_visible = g_overview_chart.series[2].visible;
					}
					';
			}
			?>
			if(typeof(g_overview_chart.series[3]) !== "undefined" &&
				typeof(g_overview_chart.series[3].visible) !== "undefined")
			{
				is_all_clicks_visible = g_overview_chart.series[3].visible;
			}
			<?php
			if($are_engagements_accessible == 1)
			{
				echo '
				if(typeof(g_overview_chart.series[4]) !== "undefined" &&
					typeof(g_overview_chart.series[4].visible) !== "undefined")
				{
					is_engagements_visible = g_overview_chart.series[4].visible;
				}
					';
			}
			?>
		}

		$("#graph_container")
			.html('')
			.show(0);

		if(graph_data.length > 0)
		{
			// We want to modify the X axis interval based upon both the width of the graph
			// and the number of days in the time period specified. This calculation basically
			// cuts the graph's width into 4 breakpoints, then multiplies the interval between
			// days by the number of the breakpoint.
			var widthModifier = 5 - (Math.floor($('.graphs_section').width() / 225));
			graphInterval = Math.ceil(graph_data.length / 16) * widthModifier;

<?php if($are_view_throughs_accessible == 1) { ?>
			var initial_click_stack = 3;
			var initial_click_z_index = 3;
<?php } else { ?>
			var initial_click_stack = 1;
			var initial_click_z_index = 2;
<?php } ?>

			var series_array = [
				{
					name: 'Impressions',
					visible: is_all_impressions_visible,
					lineWidth: 1,
					type:'area',
					zIndex: 0,
					yAxis: 0,
					data: all_impressions_data,
					color: '#919194',
					marker: {
						enabled: false
					},
					legend_index: 0
				},
				{
					name: 'Retargeting Impressions',
					visible: is_retargeting_impressions_visible,
					type: 'area',
					zIndex: 1,
					yAxis: 0,
					lineWidth: 1,
					data: retargeting_impressions_data,
					color: '#00629B',
					marker: {
						enabled: false
					},
					legendIndex: 2
				},
	<?php if($are_view_throughs_accessible == 1) { ?>
					{
						name: 'Visits',
						visible: is_visits_visible,
						color: '#DCDCE0',
						type: 'column',
						stack: 1,
						index: 3,
						zIndex: 2,
						yAxis: 1,
						borderColor: "white",
						borderWidth: 1,
						data: visits_data,
						legendIndex: 3
					},
	<?php } ?>
				{
					name: 'Clicks',
					visible: is_all_clicks_visible,
					color: '#0092e7',
					borderColor: 'white',
					borderWidth: 1,
					type: 'column',
					stack: initial_click_stack,
					index: 4,
					zIndex: initial_click_z_index,
					yAxis: 1,
					data: all_clicks_data,
					legendIndex: 2
				}
	<?php if($are_engagements_accessible == 1) { ?>
				,
				{
					name: 'Engagements',
					visible: is_engagements_visible,
					color: '#4D4F53',
					type: 'column',
					stack: 2,
					index: 4,
					zIndex: 1,
					yAxis: 1,
					borderColor: 'white',
					borderWidth: 1,
					data: engagements_data,
					legendIndex: 4
				}
	<?php } ?>
			];

			if(are_leads_visible)
			{
				var leads_series_element = {
					name: 'Leads',
					color: '#09A0B2',
					type: 'column',
					stack: 1,
					index: 1,
					zIndex: 2,
					yAxis: 1,
					borderColor: 'white',
					borderWidth: 1,
					data: leads_data,
					legendIndex: 5
				};

				series_array.push(leads_series_element);
			}

			g_overview_chart = new Highcharts.Chart({
				chart: {
					renderTo: 'graph_container',
					type:'area',
					height:240,
					zoomType:'x'
				},
				title: {
					text: ''
				},
				tooltip: {
						pointFormat: '{series.name}: <b>{point.y}</b><br/>'
				},
				credits: {
						enabled: false
				},
				yAxis: [{
					title: {
						text: 'Impressions',
						style: {
							fontSize: '10px'
						}
					},
					labels:{
						style: {
							fontSize: "9px",
							fontWeight: 'bold'
						}
					}
				},
				{
					title: {
						text: 'Engagements',
						style: {
							fontSize: '10px'
						}
					},
					labels: {
						style: {
							fontSize: "9px"
						}
					},
					opposite: true
				}],
				xAxis: {
					labels:{
						rotation: -45,
						align: 'right',
						style: {
							fontSize: '9px'
						}
					},
					categories: categories_data,
					tickInterval: graphInterval
				},
				plotOptions: {
					column: {
						grouping: false,
						stacking: 'normal'
					},
					series: {
							events: {
							legendItemClick: function() {
	<?php if($are_view_throughs_accessible == 1) { ?>
								if(this.name == "Visits")
								{
									var clicks_index = null;
									for(var ii = 0; ii < this.chart.series.length; ii++)
									{
										if(g_overview_chart.series[ii].name == "Clicks")
										{
											clicks_index = ii;
											break;
										}
									}

									if(clicks_index == null)
									{
										return;
									}

									var clicks_series = g_overview_chart.series[clicks_index];
									var will_visits_be_visible = !this.visible; // visibility of clicked item changes after this function returns
									var original_chart_animation = g_overview_chart.animation;
									g_overview_chart.animation = false;

									if(will_visits_be_visible && clicks_series.visible)
									{
										clicks_series.update({stack: 3, zIndex: 3});
									}
									else
									{
										clicks_series.update({stack: 1, zIndex: 2});
									}

									this.setVisible(will_visits_be_visible);
									g_overview_chart.animation = original_chart_animation;
									return false;
								}
								else if(this.name == "Clicks")
								{
									var visits_index = null;
									for(var ii = 0; ii < this.chart.series.length; ii++)
									{
										if(g_overview_chart.series[ii].name == "Visits")
										{
											visits_index = ii;
											break;
										}
									}

									if(visits_index == null)
									{
										return;
									}

									var visits_series = g_overview_chart.series[visits_index];
									var will_clicks_be_visible = !this.visible; // visibility of clicked item changes after this function returns
									if(will_clicks_be_visible && visits_series.visible)
									{
										this.update({stack: 3, zIndex: 3});
									}
									else
									{
										//this.update({stack: 1, zIndex: 2});
									}
								}
	<?php } ?>
							}
						}
					}
				},
				legend: {
					align: 'center',
					verticalAlign: 'top',
					floating: false,
					borderWidth: 0,
					backgroundColor: 'rgba(0,0,0,0)',
					borderRadius: 2,
					symbolWidth: 11,
					itemStyle: {
						fontSize: "9px",
						fontWeight: "normal"
					},
					itemHoverStyle: {
						color: '#000'
					},
					itemHiddenStyle: {
						color: '#ccc'
					}
				},
				series: series_array
			});

			if(typeof g_overview_chart_function !== 'undefined')
			{
				$(window).off('resize', g_overview_chart_function);
			}

			g_overview_chart_function = function() {
				var widthModifier = 5 - (Math.floor($('.graphs_section').width() / 225));
				graphInterval = Math.ceil(graph_data.length / 16) * widthModifier;
				g_overview_chart.xAxis[0].options.tickInterval = graphInterval;
			};

			$(window).resize(g_overview_chart_function);
		}
		else
		{
			$('#graph_container').hide(0);
			$("#no_graph_data_overlay").show(200);
			$("#graph_info_hover_over").css('visibility', 'hidden');
		}
	}
</script>

<?php // report_v2.js has to be loaded after g_current_start_date is set in onload. ?>
<!--[if gt IE 9 | !IE ]><!-->
	<script type="text/javascript" src="/assets/js/waves.min.js"></script>
<![endif]-->
<script type="text/javascript" src="/assets/js/report_v2.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<script src="/js/campaign_health/notes.js"></script>
<script src="/js/datatable_csv_export.js"></script>

<!-- TODO Update Mapbox regular library when new version of Leaflet supported 20160601 - MC -->
<!-- <script src='https://api.mapbox.com/mapbox.js/v2.4.0/mapbox.js'></script> -->
<script src='/assets/leaflet/leaflet-src.js?v=<?php echo CACHE_BUSTER_VERSION; ?>'></script>
<script src='https://api.mapbox.com/mapbox.js/v2.4.0/mapbox.standalone.js'></script>

<script type="text/javascript" src="/js/ajax_queue.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<script type="text/javascript" src="/assets/js/report_v2_maps.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<script src='https://api.mapbox.com/mapbox.js/plugins/leaflet-geodesy/v0.1.0/leaflet-geodesy.js'></script>

<?php if($has_support_email) : ?>
	<script type="text/javascript" src="/assets/js/report_v3_email_support.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<?php endif; ?>

<!--[if lt IE 9 ]>
	<script>
		$(function(){
			$(window).resize(function(){
				var window_width = $(window).width();
				if(window_width<=1024)
				{
					$(".navigation_container").css("left","-240px");
					$(".content_container").css("left","0px");
					$("#left_nav_toggle").css("display","block");
					$("#leftsection").css("width","2% !important");
					$("#middlesection").css("width","96% !important");
					$("#rightsection").css("width","2% !important");
					$(".container-body").css("top","74px");
				}else if (window_width > 1024 && window_width<=1366)
				{
					$(".navigation_container").css("left","0px");
					$("#leftsection").css("width","5% !important");
					$("#middlesection").css("width","90% !important");
					$("#rightsection").css("width","5% !important");
				}else
				{
					$(".navigation_container").css("left","0px");
					$(".content_container").css("left","240px");
					$("#left_nav_toggle").css("display","none");
					$("#leftsection").css("width","10% !important");
					$("#middlesection").css("width","80% !important");
					$("#rightsection").css("width","10% !important");
					$(".container-body").css("top","74px");
				}
			});
		});
	</script>
<![endif]-->
