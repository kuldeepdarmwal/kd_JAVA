var g_campaigns_jqxhr = null;
var g_tabs_visibility_jqxhr = null;
var g_headline_jqxhr = null;
var g_chart_jqxhr = null;
var g_subproduct_content_jqxhr = null;
var g_secondary_subproduct_content_jqxhr = null;

// Each ajax request for get_report_data() gets a unique id, only the current id's response is handled.
var g_get_report_campaigns_data_request_number = 0;
var g_get_report_tabs_visibility_data_request_number = 0;
var g_get_report_headline_data_request_number = 0;
var g_get_report_chart_data_request_number = 0;

var g_tabs_visibility = null;

var g_current_start_date;
var g_current_end_date;

var g_are_campaigns_loading = true;

var g_current_product_tab = 'overview_product';
var g_current_product_tab_set = null;
var g_current_subproduct_nav_pill_id = '';
var g_current_subproduct_nav_pills = null;

var g_chart_resources = new Array();

var heatmaps_on_page = {};

window.overview_calls = [];
window.onbeforeunload = function(e)
{
	$.each(window.overview_calls, function(i, call){
		call.abort();
	});
}

function abort_tabs_visibility_jqxhr()
{
	if(g_tabs_visibility_jqxhr != null)
	{
		g_tabs_visibility_jqxhr.abort();
		g_tabs_visibility_jqxhr = null;
	}
}

function abort_campaigns_jqxhr()
{
	if(g_campaigns_jqxhr != null)
	{
		g_campaigns_jqxhr.abort();
		g_campaigns_jqxhr = null;
	}
}

function abort_headline_jqxhr()
{
	if(g_headline_jqxhr != null)
	{
		g_headline_jqxhr.abort();
		g_headline_jqxhr = null;
	}
}

function abort_chart_jqxhr()
{
	if(g_chart_jqxhr != null)
	{
		g_chart_jqxhr.abort();
		g_chart_jqxhr = null;
	}
}

function abort_subproduct_jqxhr()
{
	if(g_subproduct_content_jqxhr != null)
	{
		g_subproduct_content_jqxhr.abort();
		g_subproduct_content_jqxhr = null;
	}
	abort_secondary_subproduct_jqxhr();
}

function abort_secondary_subproduct_jqxhr()
{
	if(g_secondary_subproduct_content_jqxhr != null)
	{
		g_secondary_subproduct_content_jqxhr.abort();
		g_secondary_subproduct_content_jqxhr = null;
	}
}

// central function that gets the data according to the change_type and applies it to the page
function update_page(change_type)
{
	var advertiser_id = $("#report_v2_advertiser_options").val();
	var campaign_values = $("#report_v2_campaign_options").val();
	var start_date = g_current_start_date;
	var end_date = g_current_end_date;

	get_report_data(
		advertiser_id,
		campaign_values,
		start_date,
		end_date,
		change_type,
		g_current_product_tab,
		g_current_subproduct_nav_pill_id
	);
}

// A campaign was selected from the Campaign dropdown select list.
function select_campaign()
{
	update_page('change_campaign');
}

// An advertiser was selected from the Advertiser dropdown select list.
function select_advertiser()
{
	update_page('change_advertiser');
}

// The start or end date was changed
function select_date(start, end, label)
{
	g_current_start_date = start.format('YYYY-MM-DD');
	g_current_end_date = end.format('YYYY-MM-DD');

	update_page('change_date');
}

function apply_campaigns_data(campaigns, value_changed)
{
	if(value_changed == 'change_advertiser')
	{
		$("#report_v2_campaign_options").html('');
		$.each(campaigns, function(i, data) {
			var display_name = data.content;

			$('#report_v2_campaign_options').append($("<option></option>").attr("value", data.value).text(display_name));
		});
		
		$("#report_v2_campaign_options").multiselect('rebuild');
		$("#report_v2_campaign_options").multiselect('selectAll', false);
		$("#report_v2_campaign_options").multiselect('updateButtonText');
		var enabled = campaigns.length > 0 ? 'enable' : 'disable';

		enable_campaigns_select();
	}
}

function apply_summary_data(summary_data, value_changed)
{
	$(".summary_section div.impressions-count").html(summary_data.impressions);
	$(".summary_section div.leads-count").html(summary_data.leads);
	$(".summary_section div.engagements-count").html(summary_data.total_engagements);
	$(".summary_section div.engagements-rate").html(summary_data.total_engagement_rate);
	$(".summary_section div.interactions-count").html(summary_data.ad_interactions);
	$(".summary_section div.interactions-rate").html(summary_data.ad_interaction_rate);
	$(".summary_section div.visits-count").html(summary_data.visits);
	$(".summary_section div.visits-rate").html(summary_data.visit_rate);
	$(".summary_section div.view-throughs-count").html(summary_data.view_throughs);
	$(".summary_section div.view-throughs-rate").html(summary_data.view_through_rate);
	$(".summary_section div.clicks-count").html(summary_data.clicks);
	$(".summary_section div.clicks-rate").html(summary_data.click_rate);
	$(".summary_section div.retargeting-count").html(summary_data.retargeting_impressions);
	$(".summary_section div.retargeting-rate").html(summary_data.retargeting_click_rate);

	if(summary_data.are_leads_visible)
	{
		$("#summary_leads_cell").css('visibility', 'visible');
	}
	else
	{
		$("#summary_leads_cell").css('visibility', 'hidden');
	}

	if(summary_data.are_visits_visible)
	{
		$(".visits_column").css('display', 'inline-block');
	}
	else
	{
		$(".visits_column").css('display', 'none');
	}
}

function start_subproduct_loading_gif()
{
	$('#subproduct_content').fadeTo(200, 0.2);
	$("#subproduct_loading_image").show("fast");
}

function stop_subproduct_loading_gif()
{
	$("#subproduct_loading_image").hide("fast");
	$('#subproduct_content').fadeTo(200, 1);
}

function start_overview_loading_gif()
{
	$('#overview_loading_image').fadeIn(100);
	$('#overview_tab_inner_content').fadeOut(100);
	$('#no_data_overview_overlay').fadeOut(100);
	$("#no_graph_data_overlay").hide(200);
}
function stop_overview_loading_gif(selector)
{
	$('#overview_loading_image').fadeOut(100, function(){
		$(selector).fadeIn(100, function(){
			$(window).resize();
		});
	});
}

function switch_tab_and_reload_content_for_inactive_tab()
{
	var is_switching = false;

	if(g_tabs_visibility[g_current_product_tab] !== true)
	{
		var new_tab = null;

		var num_possible_tabs = g_tabs_order_to_html_id.length;
		for(var ii = 0; ii < num_possible_tabs; ii++)
		{
			var look_up = g_tabs_order_to_html_id[ii];
			if(g_tabs_visibility[look_up] === true)
			{
				new_tab = look_up;
				break;
			}
		}

		if(new_tab !== null)
		{
			g_current_product_tab = new_tab;
			g_current_subproduct_nav_pill_id = null;
			update_page('change_product');

			is_switching = true;
		}
	}

	return is_switching;
}

function handle_ajax_controlled_error(data)
{
	if(data.hasOwnProperty('is_logged_in') && data.is_logged_in === false)
	{
		window.location.replace('.'); // reload current page because they've been logged out
	}
	else
	{
		vl_show_ajax_response_data_errors(data, message);
	}
}

function load_campaigns(
	advertiser_id, 
	campaign_values, 
	start_date, 
	end_date, 
	value_changed, 
	active_product,
	active_subproduct
)
{
	g_get_report_campaigns_data_request_number++;

	abort_campaigns_jqxhr();

	disable_actions_during_campaigns_load();

	g_campaigns_jqxhr = jQuery.ajax({
		async: true,
		type: "POST",
		data: { 
			request_id: ""+g_get_report_campaigns_data_request_number,
			advertiser_id: advertiser_id,
			campaign_values: campaign_values,
			start_date: start_date,
			end_date: end_date,
			action: value_changed,
			active_product: active_product,
			active_subproduct: active_subproduct
		},
		url: "/report_legacy/get_campaigns",
		success: function(data, textStatus, jqXHR) {
			g_campaigns_jqxhr = null;
			if(vl_is_ajax_call_success(data))
			{
				if(data.real_data.request_id == g_get_report_campaigns_data_request_number)
				{
					apply_campaigns_data(
						data.real_data.campaigns,
						value_changed
					);

					if(g_tabs_visibility_jqxhr === null)
					{
						switch_tab_and_reload_content_for_inactive_tab();
					}
				}
			}
			else
			{
				stop_summary_loading_gif();

				handle_ajax_controlled_error(data, "Failed in /report_legacy/get_campaigns");
			}

			enable_actions_after_campaigns_load();
		},
		error: function(jqXHR, textStatus, errorThrown) {
			g_campaigns_jqxhr = null;

			if(errorThrown !== "abort")
			{
				vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
				stop_summary_loading_gif();
				enable_actions_after_campaigns_load();
			}
		},
		dataType: "json"
	});
}

function load_tabs_visibility_and_nav_pills(
	advertiser_id, 
	campaign_values, 
	start_date, 
	end_date, 
	value_changed, 
	active_product,
	active_subproduct
)
{
	g_get_report_tabs_visibility_data_request_number++;

	abort_tabs_visibility_jqxhr();

	g_tabs_visibility_jqxhr = jQuery.ajax({
		async: true,
		type: "POST",
		data: { 
			request_id: ""+g_get_report_tabs_visibility_data_request_number,
			advertiser_id: advertiser_id,
			campaign_values: campaign_values,
			start_date: start_date,
			end_date: end_date,
			action: value_changed,
			active_product: active_product,
			active_subproduct: active_subproduct
		},
		url: "/report_legacy/get_products_tabs_and_views_nav_pills",
		success: function(data, textStatus, jqXHR) {
			g_tabs_visibility_jqxhr = null;
			if(vl_is_ajax_call_success(data))
			{
				if(data.real_data.request_id == g_get_report_tabs_visibility_data_request_number)
				{
					var is_force_tab_content_refresh = false;

					if(value_changed != 'change_product' && value_changed != 'change_subproduct')
					{
						g_tabs_visibility = data.real_data.tabs_data.tabs_visibility;
						g_current_product_tab_set = data.real_data.tabs_data.new_tabs;

						var new_active_product = data.real_data.tabs_data.new_active_product;
						if(
							(
								new_active_product === 'overview_product' &&
								g_current_product_tab !== 'overview_product'
							) ||
							(
								new_active_product !== 'overview_product' &&
								g_current_product_tab === 'overview_product'
							)
						)
						{
							is_force_tab_content_refresh = true;
						}

						if(g_campaigns_jqxhr === null)
						{
							g_current_product_tab = data.real_data.tabs_data.new_active_product;
						}

						build_tab_set(g_current_product_tab_set, g_tabs_visibility, data.real_data.tabs_data.new_active_product);
					}

					if(value_changed != 'change_subproduct')
					{
						if(data.real_data.subproducts_data.new_subproduct)
						{
							build_subproduct_nav_pills(
								data.real_data.subproducts_data.new_subproduct,
								data.real_data.subproducts_data.new_subproduct.client_subproduct_items,
								data.real_data.subproducts_data.new_active_subproduct
							);
						}
						else
						{
							build_subproduct_nav_pills(null, null, null);
						}
					}

					update_csv_access();

					// Needs to be done after campaign set has been updated
					if(g_campaigns_jqxhr === null && 
						!switch_tab_and_reload_content_for_inactive_tab() &&
						is_force_tab_content_refresh === true
					)
					{
						update_page('change_subproduct');
					}
				}
			}
			else
			{
				stop_summary_loading_gif();

				handle_ajax_controlled_error(data, "Failed in /report_legacy/get_products_tabs_and_views_nav_pills");
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			g_tabs_visibility_jqxhr = null;

			if(errorThrown !== "abort")
			{
				vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
				stop_summary_loading_gif();
			}
		},
		dataType: "json"
	});
}

function get_report_data(
	advertiser_id, 
	campaign_values, 
	start_date, 
	end_date, 
	value_changed, 
	active_product,
	active_subproduct
)
{
	if(value_changed == 'initialization' ||
		value_changed == 'change_advertiser')
	{
		load_campaigns(
			advertiser_id, 
			campaign_values, 
			start_date, 
			end_date, 
			value_changed, 
			active_product,
			active_subproduct
		);
	}

	if(value_changed == 'initialization' ||
		value_changed == 'change_advertiser' ||
		value_changed == 'change_campaign' ||
		value_changed == 'change_date' ||
		value_changed == 'change_product'
	)
	{
		load_tabs_visibility_and_nav_pills(
			advertiser_id, 
			campaign_values, 
			start_date, 
			end_date, 
			value_changed, 
			active_product,
			active_subproduct
		);
	}

	load_tab_content(
		advertiser_id, 
		campaign_values, 
		start_date, 
		end_date, 
		value_changed, 
		active_product,
		active_subproduct
	);
}

function load_tab_content(
	advertiser_id, 
	campaign_values, 
	start_date, 
	end_date, 
	value_changed, 
	active_product,
	active_subproduct
)
{
	abort_headline_jqxhr();
	abort_chart_jqxhr();
	abort_subproduct_jqxhr();

	var chart_resource;
	while((chart_resource = g_chart_resources.pop()) !== undefined)
	{
		$(window).off('resize', chart_resource.resize_function);
	}

	if($('#overview_tab_content').is(':visible') || active_product === 'overview_product')
	{
		start_overview_loading_gif();
	}

	if($('#subproduct_loading_enclosure').is(':visible') && active_product !== 'overview_product')
	{
		start_subproduct_loading_gif();
	}

	if(active_product === 'overview_product')
	{
		$('#overview_tab_content').show(0);

		g_get_report_headline_data_request_number++;

		g_headline_jqxhr = jQuery.ajax({
			async: true,
			type: "POST",
			data: { 
				request_id: ""+g_get_report_headline_data_request_number,
				advertiser_id: advertiser_id,
				campaign_values: campaign_values,
				start_date: start_date,
				end_date: end_date,
				action: value_changed,
				active_product: active_product,
				active_subproduct: active_subproduct
			},
			url: "/report_legacy/get_headline_data",
			success: function(data, textStatus, jqXHR) {
				g_headline_jqxhr = null;
				if(vl_is_ajax_call_success(data))
				{
					if(data.real_data.request_id == g_get_report_headline_data_request_number)
					{
						// switching for inactive tab handled on campaign & tab_visibility loads

						$('#subproduct_loading_enclosure').hide(0);

						apply_summary_data(
							data.real_data.summary_data,
							value_changed
						);

						update_csv_access();

						stop_subproduct_loading_gif();

						$(window).resize();
					}
				}
				else
				{
					stop_summary_loading_gif();
					handle_ajax_controlled_error(data, "Failed in /report_legacy/get_headline_data");
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				g_headline_jqxhr = null;

				if(errorThrown !== "abort")
				{
					vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
					stop_summary_loading_gif();
				}
			},
			dataType: "json"
		});

		g_get_report_chart_data_request_number++;

		g_chart_jqxhr = jQuery.ajax({
			url: "/report_legacy/get_graph_data",
			async: true,
			type: "POST",
			dataType: "json",
			data: { 
				request_id: ""+g_get_report_chart_data_request_number,
				advertiser_id: advertiser_id,
				campaign_values: campaign_values,
				start_date: start_date,
				end_date: end_date,
				action: value_changed,
				active_product: active_product,
				active_subproduct: active_subproduct
			},
			success: function(data, textStatus, jqXHR) {
				g_chart_jqxhr = null;
				if(vl_is_ajax_call_success(data))
				{
					if(data.real_data.request_id == g_get_report_chart_data_request_number)
					{
						// switching for inactive tab handled on campaign & tab_visibility loads

						$('#subproduct_loading_enclosure').hide(0);

						apply_graph_data(
							data.real_data.graph_data,
							value_changed,
							data.real_data.are_leads_visible
						);

						update_csv_access();

						stop_subproduct_loading_gif();
					}
				}
				else
				{

					handle_ajax_controlled_error(data, "Failed in /report_legacy/get_graph_data");
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				g_chart_jqxhr = null;

				if(errorThrown !== "abort")
				{
					vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
				}
			}
		});

		window.overview_calls = [];

		$('#overview_tab_content div.card.ajax-load').each(function() {
			var card = $(this);
			window.overview_calls.push($.ajax({
				url: "/report_legacy/get_overview_card_data",
				async: true,
				type: "POST",
				dataType: "json",
				data: {
					advertiser_id: advertiser_id,
					campaign_values: campaign_values,
					start_date: start_date,
					end_date: end_date,
					action: value_changed,
					source: card.data('source'),
				},
				success: function(data) {
					if (data.length === 0) {
						$(card).fadeOut(0);
					} else {
						populate_overview_card(data, card);
						$(card).fadeIn(0);

						// Initialize the tooltips in the overview card
						$(card).find(".report_tooltip").popover();
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					if (textStatus !== 'abort')
					{
						vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
					}
				}
			}));
		});

		$.when.apply($, window.overview_calls).then(function(){
			var args = Array.prototype.slice.call(arguments);
			var has_data = false;
			$.each(args, function(i, val){
				if (val[0].length > 0){
					has_data = true;
				}
			});

			stop_overview_loading_gif(has_data ? '#overview_tab_inner_content' : '#no_data_overview_overlay');
		});
	}
	else
	{
		g_subproduct_content_jqxhr = jQuery.ajax({
			url: "/report_legacy/get_subproduct_content_data",
			async: true,
			type: "POST",
			dataType: "json",
			data: { 
				request_id: 0,
				advertiser_id: advertiser_id,
				campaign_values: campaign_values,
				start_date: start_date,
				end_date: end_date,
				action: value_changed,
				active_product: active_product,
				active_subproduct: active_subproduct
			},
			success: function(data, textStatus, jqXHR) {
				g_subproduct_content_jqxhr = null;
				if(vl_is_ajax_call_success(data))
				{
					if(data.real_data.is_subproduct_data_response === true)
					{
						$('#overview_tab_content').hide(0);
						$('#subproduct_loading_enclosure').show();

						$('#subproduct_content').html(data.real_data.subproduct_data.subproduct_html_scaffolding);
						stop_subproduct_loading_gif();
						stop_overview_loading_gif();
						build_subproduct_visuals(data.real_data.subproduct_data.subproduct_data_sets);

						update_csv_access();


					}
					else
					{
						// Not subproduct data response due to switching data set and tab change.  New data loaded elsewhere.
					}
				}
				else
				{
					handle_ajax_controlled_error(data, "Failed in /report_legacy/get_subproduct_content_data");

					stop_subproduct_loading_gif();
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				g_subproduct_content_jqxhr = null;

				if(errorThrown !== "abort")
				{
					vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
					stop_subproduct_loading_gif();
				}
			}
		});
	}
}

function populate_overview_card(product_data, card) {
	switch (card.data('chart-type')) {
		case 'creatives':
			var chart = $(card).find('.chart');
			if (product_data.length > 1)
			{
				$(chart).append('<div class="button prev"><img src="/assets/img/btn-prev.png"/></div><div class="button next"><img src="/assets/img/btn-next.png"/></div>');
			}
			$(chart).data('creatives', product_data);
			$(chart).data('creativesIndex', 0);

			$(chart).find('.button.prev').on('click', function(){
				var index = $(chart).data('creativesIndex') == 0 ? $(chart).data('creatives').length - 1 : $(chart).data('creativesIndex') - 1;
				$(chart).data('creativesIndex', index);
				populate_creatives_overview_card(product_data[index].data.thumbnail, product_data[index].data.impressions, product_data[index].data.interaction_rate, card)
			});

			$(chart).find('.button.next').on('click', function(){
				var index = $(chart).data('creativesIndex') == $(chart).data('creatives').length - 1 ? 0 : $(chart).data('creativesIndex') + 1;
				$(chart).data('creativesIndex', index);
				populate_creatives_overview_card(product_data[index].data.thumbnail, product_data[index].data.impressions, product_data[index].data.interaction_rate, card)
			});
			
			populate_creatives_overview_card(product_data[0].data.thumbnail, product_data[0].data.impressions, product_data[0].data.interaction_rate, card);
			break;
		case 'bar':
			$(card).find('.chart').highcharts({
				chart: {
					type: 'bar',
					height: 230,
					marginTop: 10,
					marginBottom: 10,
					marginRight: 15
				},
				series: [{
					name: "Impressions",
					data: $.map(product_data, function(curr){
						return {
							'name': curr.name,
							'y': curr.data
						};
					}),
					dataLabels: {
						enabled: true,
						crop: false,
						overflow: 'none',
						style: {
							y: 0,
							x: 0,
							fontSize: 9
						},
						formatter: function(){
							if (this.y >= 2000){
								return Math.round(this.y / 1000) + 'k';
							}
							return Highcharts.numberFormat(this.y, 0);
						}
					},
					pointPadding: 0.2
				}],
				xAxis: {
					lineWidth: 0,
					minorGridLineWidth: 0,
					gridLineColor: 'transparent',
					lineColor: 'transparent',
					labels: {
						enabled: true,
						distance: 0,
						x: -10,
						y: 2,
						style: {
							fontSize: 9
						}
					},
					categories: $.map(product_data, function(curr){
						if (curr.name.length > 16) {
							return curr.name.substring(0, 14) + '...';
						}
						return curr.name;
					}),
					minorTickLength: 0,
					tickLength: 0
				},
				yAxis: {
					lineWidth: 0,
					minorGridLineWidth: 0,
					gridLineWidth: 0,
					gridLineColor: 'transparent',
					lineColor: 'transparent',
					labels: {
					   enabled: false
					},
					minorTickLength: 0,
					tickLength: 0,
					type: 'logarithmic',
					title: {
						style: {
							display: 'none'
						}
					}
				},
				tooltip: {
					formatter: function () {
		                return this.key + ': ' + Highcharts.numberFormat(this.y, 0);
		            },
		            borderWidth: 0,
		            style: {
		            	padding: '3px',
		            	fontSize: '10px'
		            }
				},
				legend: {
					enabled: false
				},
				colors: ['#006ca3'],
				title: {
					style: {
						display: 'none'
					}
				},
				credits: {
					enabled: false
				}
			});
			break;
		case 'bar_completions':
			$(card).find('.chart').highcharts({
				chart: {
					type: 'bar',
					height: 220,
					marginTop: 10,
					marginBottom: 0,
					marginLeft: 70,
					marginRight: -40
				},
				series: [{
					name: "Completions",
					data: $.map(product_data, function(curr){
						return {
							'y': curr.data
						};
					}),
					dataLabels: {
						enabled: true,
						inside: true,
						align: 'left',
						x: 5,
						style: {
							fontSize: '18px',
							fontWeight: 'bold',
							fontFamily: 'Arial',
							color: 'white'
						}
					},
					pointPadding: -0.15
				}],
				xAxis: {
					lineWidth: 0,
					minorGridLineWidth: 0,
					gridLineColor: 'transparent',
					lineColor: 'transparent',
					labels: {
						enabled: true,
						align: 'right',
						distance: 0,
						x: -15,
						style: {
							fontSize: '9px',
							textAlign: 'center'
						},
						useHTML: true,
						formatter: function(){
							return {
								'Started': '<div style="width:50px;text-align:center;"><img src="/assets/img/completion-0.png" style="display:block;height:20px;margin:0 auto;"/> Started</div>',
								'25% Viewed': '<div style="width:50px;text-align:center;"><img src="/assets/img/completion-25.png" style="display:block;height:20px;margin:0 auto;"/> 25% Viewed</div>',
								'50% Viewed': '<div style="width:50px;text-align:center;"><img src="/assets/img/completion-50.png" style="display:block;height:20px;margin:0 auto;"/> 50% Viewed</div>',
								'75% Viewed': '<div style="width:50px;text-align:center;"><img src="/assets/img/completion-75.png" style="display:block;height:20px;margin:0 auto;"/> 75% Viewed</div>',
								'Completed': '<div style="width:50px;text-align:center;"><img src="/assets/img/completion-100.png" style="display:block;height:20px;margin:0 auto;"/> Completed</div>'
							}[this.value];
						}
					},
					categories: ['Started', '25% Viewed', '50% Viewed', '75% Viewed', 'Completed'],
					minorTickLength: 0,
					tickLength: 0
				},
				yAxis: {
					lineWidth: 0,
					minorGridLineWidth: 0,
					gridLineWidth: 0,
					gridLineColor: 'transparent',
					lineColor: 'transparent',
					labels: {
					   enabled: false
					},
					minorTickLength: 0,
					tickLength: 0,
					title: {
						style: {
							display: 'none'
						}
					}
				},
				tooltip: {
					enabled: true,
					useHTML: true,
					backgroundColor: 'white',
					borderWidth: 0,
					hideDelay: 200,
					formatter: function(){
						return {
							'Started': 'Number of impressions where the video was played. Event is logged once per view. If a user stops play and restarts it, the restart isn\'t counted.',
							'25% Viewed': 'Number of times the video played to 25% of its length.',
							'50% Viewed': 'Number of times the video reached its midpoint during play. Event is logged once per view.',
							'75% Viewed': 'Number of times the video played to 75% of its length.',
							'Completed': 'Number of times the video played to completion. Event is logged once per view. If a user restarts the clip, it\'s not counted again.'
						}[this.key];
					},
					positioner: function(labelWidth, labelHeight, point){
						var y = (point.plotY + 100) > 220 ? point.plotY - 10 - labelHeight : point.plotY + 33;
						return {
							x: 70,
							y: y
						}
					}
				},
				legend: {
					enabled: false
				},
				colors: ['#006ca3'],
				title: {
					style: {
						display: 'none'
					}
				},
				credits: {
					enabled: false
				}
			});
			break;
		case 'column':
			var total = 0
			$.each(product_data, function(i, val){
				total += val.data;
			});

			var is_clicks = $(card).data('source') == 'clicks';
			var is_targeted_content = $(card).data('source') == 'content';

			var marginLeft = is_clicks ? 90 : 50;

			$(card).find('.chart').highcharts({
				chart: {
					type: 'column',
					height: 240,
					marginTop: 20,
					marginLeft: marginLeft
				},
				series: [{
					data: $.map(product_data, function(curr){
						return {
							'name': curr.name,
							'y': curr.data
						};
					}),
					dataLabels: {
						enabled: false
					},
					pointPadding: 0.1
				}],
				xAxis: {
					labels: {
						enabled: true,
						rotation: is_clicks ? -45 : 0,
						x: is_clicks ? 5 : 0,
						y: 15,
		                style: {
		                    fontSize: '9px',
		                    fontFamily: 'Arial, sans-serif'
		                },
		                formatter: function() {
		                	return this.value.length > 16 ? this.value.substring(0, 13) + '...' : this.value;
		                }
					},
					categories: $.map(product_data, function(curr){
						return curr.name
					}),
				},
				yAxis: {
					title: {
						text: "Views",
						style: {
							display: is_targeted_content ? 'block' : 'none'
						}
					}
				},
				tooltip: {
					formatter: function () {
		                return this.key + ': ' + Highcharts.numberFormat(this.y, 0);
		            },
		            borderWidth: 0,
		            style: {
		            	padding: '3px',
		            	fontSize: '10px'
		            }
				},
				legend: {
					enabled: false
				},
				colors: ['#006ca3'],
				title: {
					style: {
						display: 'none'
					}
				},
				credits: {
					enabled: false
				}
			});

			if (is_clicks)
			{
				var font_size = 30;
				if (total > 999)
				{
					total = Highcharts.numberFormat(total / 1000, 1) + 'k';
					font_size = 24;
				}
				$(card).find('h4').text(product_data[0].term + " Clicks");
				$(card).find('.chart').append('<div class="total" style="position:absolute;left:0px;top:75px;text-align:center;"><h3 style="color:#006ca3;margin: 0;line-height: 1em;font-size: '+font_size+'px;">'+total+'</h3><span style="font-size:15px;">Clicks</span></div>');
			}

			break;
		case 'pie':
			$(card).find('.chart').highcharts({
				chart: {
					type: 'pie',
					height: '230'
				},
				series: [{
					name: "Price",
					data: $.map(product_data, function(curr){
						return {
							'name': curr.name,
							'y': curr.data
						};
					}),
					dataLabels: {
						enabled: false
					},
					showInLegend: true
				}],
				colors: ['#005B89', '#006ca3', '#0074B0', '#ccc'],
				legend: {            
					verticalAlign: 'top',             
					floating: false,             
					borderWidth: 0,             
					margin: 0,
					backgroundColor: 'rgba(0,0,0,0)',    
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
				title: {
					style: {
						display: 'none'
					}
				},
				credits: {
					enabled: false
				}
			});
			break;
		case 'list':
			$(card).find('.chart').empty();
			$.each(product_data, function(i, item){
				$(card).find('.chart').append('<div class="search-product"><span class="name">'+item.name+'</span><span class="value">'+Highcharts.numberFormat(item.data, 0)+'</span></div>');
			});
			break;
		default:
			break;
	}
}

function populate_creatives_overview_card(thumbnail, impressions, interaction_rate, card){
	var chart = $(card).find('.chart');
	
	chart.fadeOut(200, function(){
		chart.find('.impressions .value').text(Highcharts.numberFormat(impressions, 0));
		chart.find('.interaction_rate .value').text(Highcharts.numberFormat(interaction_rate * 100, 2) + '%');
		chart.find('.thumbnail').attr('src', thumbnail);
		chart.find('.thumbnail').on('load', function(){
			chart.fadeIn(200);
		});
	});
}

function show_empty_data_response(card) {
	var chart = $(card).find('.chart').highcharts();
    if (chart) chart.destroy();
}

function download_csv_by_category()
{
	download_csv(g_current_product_tab, g_current_subproduct_nav_pill_id);

	return false;
}

function stop_date_range_picker_event_if_disabled(event)
{
	if(g_are_campaigns_loading)
	{
		event.stopImmediatePropagation();
		return false;
	}
	else
	{
		return true;
	}
}

function disable_actions_during_campaigns_load()
{
	g_are_campaigns_loading = true;

	disable_campaigns_select();
	disable_tab_clicking();
	disable_nav_pill_clicking();
	disable_date_range_picker();
}

function enable_actions_after_campaigns_load()
{
	g_are_campaigns_loading = false;

	enable_tab_clicking();
	enable_nav_pill_clicking();
	enable_date_range_picker();
}

function enable_tab_clicking()
{
	$("#products_nav_section > a:not(.disabled)").off("click", disable_click);
	$("#products_nav_section > a:not(.disabled)").on("click", handle_tab_click);
	$("#products_nav_section > a:not(.disabled)").removeClass('dim_during_campaigns_load_disable');
}

function disable_tab_clicking()
{
	$("#products_nav_section > a:not(.disabled)").off("click", handle_tab_click);
	$("#products_nav_section > a:not(.disabled)").on("click", disable_click);
	$("#products_nav_section > a:not(.disabled)").addClass('dim_during_campaigns_load_disable');
}

function enable_nav_pill_clicking()
{
	$("#subproduct_nav_pills_holder").find('a').off('click', disable_click);
	$("#subproduct_nav_pills_holder").find('a').on('click', handle_nav_pill_select);
	$("#subproduct_nav_pills_holder").find('a').removeClass('dim_during_campaigns_load_disable');
}

function disable_nav_pill_clicking()
{
	$("#subproduct_nav_pills_holder").find('a').off('click', handle_nav_pill_select);
	$("#subproduct_nav_pills_holder").find('a').on('click', disable_click);
	$("#subproduct_nav_pills_holder").find('a').addClass('dim_during_campaigns_load_disable');
}

function enable_date_range_picker()
{
	$('#report_v2_date_range_input').removeClass('dim_during_campaigns_load_disable');
}

function disable_date_range_picker()
{
	$('#report_v2_date_range_input').addClass('dim_during_campaigns_load_disable');
}

function enable_campaigns_select()
{
	$("#report_v2_campaign_options").multiselect('enable');
}

function disable_campaigns_select()
{
	$("#report_v2_campaign_options").multiselect('disable');
}


// Download the csv according to the currently selected table.
function download_csv(tab, subproduct_nav_pill)
{
	var advertiser = $("#report_v2_advertiser_options").val();
	var campaign = $("#report_v2_campaign_options").val();
	var start_date = g_current_start_date;
	var end_date = g_current_end_date;

	if(advertiser && campaign && start_date && end_date)
	{
		$("#download_csv_advertiser").val(advertiser);
		$("#download_csv_campaign").val(campaign.toString());
		$("#download_csv_start_date").val(start_date);
		$("#download_csv_end_date").val(end_date);
		$("#download_csv_tab").val(tab);
		$("#download_csv_nav_pill").val(subproduct_nav_pill);
		$("#download_csv_form").submit();
	}
}

// Setup and initialize the environment 
$(function()
{
	$('.controller_section').show(100);
	// begin: fix for firefox refresh not applying to select element until after it has been created.
	$("#report_v2_advertiser_options").prop("selectedIndex", 0);
	$("#report_v2_campaign_options").prop("selectedIndex", 0);
	// end: fix for firefox refresh not applying to select element until after it has been created.

	if($('#report_v2_advertiser_options').prop("tagName") == "INPUT")
	{
		$('#report_v2_advertiser_options').select2({
					placeholder: "Select an advertiser",
					minimumInputLength: 0,
					formatSelection: function(obj)
					{
						return obj.text + ' <b class="caret"></b>';
					},
					initSelection: function(element, callback)
					{

						return callback({id:g_first_advertiser.id , text:g_first_advertiser.Name});
					},
					ajax: {
						url: "/report_legacy/ajax_get_advertisers",
						type: 'POST',
						dataType: 'json',
						data: function (term, page) {
							term = (typeof term === "undefined" || term == "") ? "%" : term;
							return {
								q: term,
								page_limit: 100,
								page: page
							};
						},
						results: function (data) {
							return {results: data.results, more: data.more};
						}
					},
					allowClear: false
				}).select2('val',[]);	
	}
	else
	{
		$("#report_v2_advertiser_options").select2({
			formatSelection: function(obj)
			{
				return obj.text + ' <b class="caret"></b>';
			}
		});
	}
	var g_campaign_select_interval_id = null;

	$("#report_v2_campaign_options").multiselect({ 
			includeSelectAllOption: true,
			nonSelectedText: 'Select a campaign',
			enableCaseInsensitiveFiltering: true,
			numberDisplayed: 1,
			filterPlaceholder: '',
			onChange: function(option, checked)
			{
				if(g_campaign_select_interval_id !== null)
				{
					window.clearInterval(g_campaign_select_interval_id);
					g_campaign_select_interval_id = null;
				}
				g_campaign_select_interval_id = window.setInterval(check_if_dropdown_closed, 300);

				function check_if_dropdown_closed()
				{
					if (!$('#report_v2_campaign_dropdown div.btn-group').hasClass('open'))
					{
						window.clearInterval(g_campaign_select_interval_id);
						g_campaign_select_interval_id = null;
						select_campaign();
					}
				}
			},
			templates: {
				button: '<span type="button" class="multiselect dropdown-toggle btn btn-default" data-toggle="dropdown"></span>',
				filter: '<div class="select2-search"><input type="text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" class="select2-input form-control multiselect-search"></div>',
				filterClearBtn: '<button class="btn btn-default multiselect-clear-filter" type="button">X</button></div></li>'
			}
		});

	// Pre-empt campaign multi select when loading campaigns.  Need to add these handlers before the modules handlers are added.
	$('#report_v2_date_range_input').on({
		'click': stop_date_range_picker_event_if_disabled,
		'focus': stop_date_range_picker_event_if_disabled,
		'keyup': stop_date_range_picker_event_if_disabled
	});

	var $date_picker = $('#report_v2_date_range_input').daterangepicker(
		{ 
			format: 'MM/DD/YYYY',
			startDate: g_current_start_date,
			endDate: g_current_end_date,
			opens: 'left'
		},
		function(start, end, label) {
			this.updateInputText();
			select_date(start, end, label);
		}
	);

	$date_picker.data('daterangepicker').updateInputText();

	$("#table_download_link").click(download_csv_by_category);
	$("#table_download_link").attr('title', 'Download .csv file of geography data');

	$("#table_download_link").hide();

	update_page('change_advertiser');

	$(".report_tooltip").popover();
});


function disable_click(event)
{
	event.stopImmediatePropagation();
	return false;
}

function build_tab_set(tabs_data, tabs_visibility, current_tab)
{
	if(tabs_data != null)
	{
		var num_tabs = tabs_data.length;

		var tabs_html = '';
		var tabs_nav_html = '';

		for(var ii = 0; ii < num_tabs; ii++)
		{
			var tab = tabs_data[ii];

			var is_tab_visible = tabs_visibility !== null && tabs_visibility[tab.html_element_id] === true;

			tabs_nav_html += '<a href="#' + tab.html_element_id + '" id="'+tab.html_element_id + '"';

			if(tab.html_element_id === current_tab)
			{
				if(is_tab_visible)
				{
					tabs_nav_html += ' class="active"';
				}
				else
				{
					tabs_nav_html += ' class="active disabled"';
				}
			}
			else
			{
				if(is_tab_visible)
				{
					tabs_nav_html += '';
				}
				else
				{
					tabs_nav_html += ' class="disabled"';
				}
			}

			if(is_tab_visible)
			{
				tabs_nav_html += '>';
			}
			else
			{
				tabs_nav_html += '>';
			}

			var image_html = '';
			if(tab.image_path)
			{
				image_html = '<img src="/assets/img/' + tab.image_path + '" />';
			}

			var brand_html = '';
			if(tab.product_brand)
			{
				brand_html = '<span class="product_brand">' + tab.product_brand + '</span>';
			}

			var product_type_html = '';
			if(tab.product_type)
			{
				product_type_html = '<span class="product_type">' + tab.product_type + '</span>';
			}

			// strcture in starting html too
			tabs_nav_html += image_html +
				brand_html +
				product_type_html +
				'</a>';
		}


		if(num_tabs > 1)
		{
			tabs_html += tabs_nav_html;
		}

		$("#products_nav_section").html(tabs_html);

		if(g_are_campaigns_loading)
		{
			disable_tab_clicking();
		}
		else
		{
			enable_tab_clicking();
		}

		$("#products_nav_section > a.disabled").on("click", disable_click);
	}
}

function get_id_from_href(href)
{
	if(typeof href === 'string')
	{
		var hash_index = href.indexOf("#");
		var id = href.substring(hash_index + 1);
		return id;
	}

	return null;
}

function handle_tab_click(event)
{
	var activated_tab = event.currentTarget;
	var activated_id = get_id_from_href(activated_tab.href);

	var $active_tabs = $("#products_nav_section > a.active");
	$active_tabs.removeClass("active");
	$(activated_tab).addClass("active");

	g_current_product_tab = activated_id;
	g_current_subproduct_nav_pill_id = null;
	update_page('change_product');

	return false;
}

function update_csv_access()
{
	var should_enable_download = false;
	var download_type = "";

	if(g_current_product_tab == 'overview_product')
	{
		should_enable_download = true;
		download_type = "Chart";
	}
	else if(g_current_subproduct_nav_pills !== null)
	{
		var num_nav_pills = g_current_subproduct_nav_pills.length;
		for(var ii=0; ii<num_nav_pills; ii++)
		{
			var subproduct_nav_pill = g_current_subproduct_nav_pills[ii];
			if(subproduct_nav_pill.html_element_id == g_current_subproduct_nav_pill_id)
			{
				should_enable_download = subproduct_nav_pill.can_download_csv;
				download_type = subproduct_nav_pill.title;

				break;
			}
		}
	}

	if(should_enable_download)
	{
		$("#table_download_link").show(300);
		$("#table_download_link").attr('title', 'Download .csv file of '+download_type+' data');
	}
	else
	{
		$("#table_download_link").hide(300);
	}
}

function build_subproduct_nav_pills(subproducts_config, subproduct_nav_pills, new_active_subproduct)
{
	g_current_subproduct_nav_pills = subproduct_nav_pills;
	g_current_subproduct_nav_pill_id = new_active_subproduct;

	if(subproducts_config != null && subproduct_nav_pills != null)
	{
		if(g_current_subproduct_nav_pill_id === '')
		{
			g_current_subproduct_nav_pill_id = subproduct_nav_pills[0].html_element_id;
		}

		var num_nav_pills = subproduct_nav_pills.length;
		var subproduct_nav_pills_html = '<ul id="subproduct_nav_pills" class="nav nav-pills ' + subproducts_config.data_view_class + '">';

		for(var ii = 0; ii < num_nav_pills; ii++)
		{
			var subproduct_nav_pill = subproduct_nav_pills[ii];

			subproduct_nav_pills_html += '<li id='+subproduct_nav_pill.html_element_id;

			if(g_current_subproduct_nav_pill_id == subproduct_nav_pill.html_element_id)
			{
				subproduct_nav_pills_html += ' class="active">';
			}
			else
			{
				subproduct_nav_pills_html += '>';
			}

			subproduct_nav_pills_html += '<a href="#'+subproduct_nav_pill.html_element_id+'">'+subproduct_nav_pill.title+'</a></li>';
		}
		subproduct_nav_pills_html += '</ul>';

		if(num_nav_pills > 1)
		{
			$("#subproduct_nav_pills_holder").html(subproduct_nav_pills_html);

			if(g_are_campaigns_loading)
			{
				disable_nav_pill_clicking();
			}
			else
			{
				enable_nav_pill_clicking();
			}
		}
		else
		{
			$("#subproduct_nav_pills_holder").html("");
		}
	}
	else
	{
		$("#subproduct_nav_pills_holder").html("");
	}
}

function handle_nav_pill_select(event)
{
	var activated_nav_pill = event.target;
	var activated_id = get_id_from_href(activated_nav_pill.href);

	g_current_subproduct_nav_pill_id = activated_id;
	change_active_nav_pill(activated_id);

	update_page('change_subproduct');

	event.preventDefault();
}

function build_subproduct_visuals(subproduct_data_sets)
{
	var num_data_sets = subproduct_data_sets.length;
	for(var ii = 0; ii < num_data_sets; ii++)
	{
		var data_set = subproduct_data_sets[ii];
		var is_success = window[data_set.display_function](data_set.display_selector, data_set.display_data);
	}
}

function build_subproduct_table(selector, data)
{
	build_table(data, selector);

	return true;
}

function build_subproduct_chart(selector, data)
{
	window[data.javascript_builder_function](data);

	return true;
}

function build_subproduct_summary(selector, data)
{
	$(selector).html(data.html_scaffolding);

	var num_values = data.values.length;
	for(var ii = 0; ii < num_values ; ii++)
	{
		var summary_value_config = data.values[ii];
		var specific_selector = selector + " " + summary_value_config.css_selector;
		$(specific_selector).html(summary_value_config.value);
	}

	return true;
}

function build_content_chart(chart_data)
{
	var content_chart_id = 'content_chart_visuals';
	$(chart_data.container_css_selector).html('<div id="' + content_chart_id + '"></div>');

	var chart_series_data = chart_data.series;

	var x_axis_tick_width = 40;
	var num_ticks_to_show = (Math.floor($(chart_data.container_css_selector).width() / x_axis_tick_width));
	var tick_interval = Math.ceil(chart_data.x_axis_data.length / num_ticks_to_show);
	
	var highcharts_series = [
		{
			name: 'Views',             
			visible: true,  
			lineWidth: 2,                       
			type: 'line',             
			zIndex: 0,             
			yAxis: 0,
			data: chart_series_data[0],
			color: '#005F9B',             
			marker: {                 
				enabled: true
			},
			legendIndex: 0
		}
	];

	var highcharts_options = {
		chart: {             
			renderTo: content_chart_id,             
			type:'line',                     
			height:250,
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
				text: 'Views'          
			},             
			labels:{                  
				style: {                                         
					fontWeight: 'bold'                  
				}             
			},
			min: 0
		}],         
		xAxis: {             
			labels:{
				rotation: -45,                     
				align: 'right'
			},             
			categories: chart_data.x_axis_data,
			tickInterval: tick_interval 
		},
		plotOptions: {
		},
		legend: {             
			enabled: false,
			align: 'center',             
			x: 0,
			verticalAlign: 'top',             
			y: 0,
			floating: false,             
			borderWidth: 0,             
			backgroundColor: 'rgba(0,0,0,0)',             
			borderRadius: 2,             
			itemHoverStyle: {                 
				color: '#f00'             
			},             
			itemHiddenStyle: {                 
				color: '#ccc'
			}
		},         
		series: highcharts_series
	};

	build_chart(
		highcharts_options, 
		'#'+content_chart_id, 
		chart_data.container_css_selector,
		x_axis_tick_width,
		chart_data.x_axis_data.length
	);
}

function build_chart(
	highcharts_options,
	chart_content_css_selector,
	chart_container_css_selector,
	x_axis_tick_width,
	num_graph_items
)
{

	var chart = new Highcharts.Chart(highcharts_options);
	var chart_resize_function = function() {
		var num_ticks_to_show = (Math.floor($(chart_container_css_selector).width() / x_axis_tick_width));
		var tick_interval = Math.ceil(num_graph_items / num_ticks_to_show);
		chart.xAxis[0].options.tickInterval = tick_interval;
	};
	var chart_resource = {
		'chart': chart,
		'resize_function': chart_resize_function
	};

	g_chart_resources.push(chart_resource);

	$(window).resize(chart_resize_function);
}

function build_table(table_data, table_css_selector)
{
	if(table_data != null)
	{
		var header_cells = table_data.header.cells;
		var num_columns = header_cells.length;
		var rows = table_data.rows;
		var num_rows = rows.length;

		if(num_rows > 0)
		{
			var data_table_columns_objects = new Array();
			for(var column_ii = 0; column_ii < num_columns; column_ii++)
			{
				var header_cell = header_cells[column_ii];
				var column_object = {
					title : header_cell.html_content,
					data : 'column_' + column_ii,
					'class' : header_cell.css_classes,
					orderSequence : header_cell.initial_sort_order === 'asc' ? ['asc', 'desc'] : ['desc', 'asc']
				};
				
				data_table_columns_objects.push(column_object);
			}
			
			var data_table_rows_objects = new Array();
			for(var row_ii = 0; row_ii < num_rows; row_ii++)
			{
				var row_object = {};

				var row = rows[row_ii];
				var cells = row.cells;
				var num_cells = cells.length;
				for(var cell_ii = 0; cell_ii < num_cells; cell_ii++)
				{
					var cell = cells[cell_ii];

					row_object['column_' + cell_ii] = cell.html_content;
				}

				if(row.hidden_data != null)
				{
					row_object['hidden_data'] = row.hidden_data;
				}

				data_table_rows_objects.push(row_object);
			}

			var starting_sorts = table_data.starting_sorts;
			var sorts = new Array();
			var table_ordering = false;
			if(starting_sorts != null)
			{
				var num_sorts = starting_sorts.length;
				for(var ii = 0; ii < num_sorts; ii++)
				{
					var sort = starting_sorts[ii];
					sorts.push(new Array(sort.column_index, sort.column_order));
				}
				table_ordering = true;
			}

			var caption = '';
			if(table_data.caption)
			{
				caption = '<caption>' + table_data.caption + '</caption>';
			}

			$(table_css_selector).html(
				'<table class="tab_table" cellspacing="0" class="order-column row-border" style="display:none;">'+
				caption +
				'</table>'
			);
			$(table_css_selector + " .tab_table").show();
			var table_options = {
				"data": data_table_rows_objects,
				"ordering": table_ordering,
				"searching": false,
				"order": sorts,
				"lengthChange": false,
				"pageLength" : 20,
				"paging": "full_numbers",
				"columns": data_table_columns_objects,
				"initComplete" : function(settings) {
					$("table .report_tooltip").popover();
				}
			};

			if(table_data.table_options)
			{
				var option_overrides = table_data.table_options;
				for(var attribute_name in option_overrides) 
				{
					table_options[attribute_name] = option_overrides[attribute_name];
				}
			}

			var data_table = $(table_css_selector + " .tab_table").dataTable(table_options);

			if(table_data.row_javascript_click_function_name !== null)
			{
				var data_row_click_handler = window[table_data.row_javascript_click_function_name];
				$(table_css_selector + ' .tab_table tbody').on( 'click', 'tr', function() {
					data_row_click_handler(data_table, this);
				});
				$(table_css_selector + ' .tab_table tbody')
					.on('mouseover', 'tr', function() {
						$(data_table.api().rows().nodes()).removeClass('row_highlight');
						$(data_table.api().row(this).node()).addClass('row_highlight');
					})
					.on('mouseleave', 'tr', function() {
						$(data_table.api().row(this).node()).addClass('row_highlight');
					}
				);
			}
		}
		else
		{
			var start_date = new Date(g_current_start_date);
			var formatted_start_date = (start_date.getMonth() + 1) + '/' + start_date.getDate() + '/' + start_date.getFullYear();
			var end_date = new Date(g_current_end_date);
			var formatted_end_date = (end_date.getMonth() + 1) + '/' + end_date.getDate() + '/' + end_date.getFullYear();
			$(table_css_selector).html('<div style="width:100%; font-size: 16px; text-align:center; color:grey; margin-top: 70px;">No data available for ' + formatted_start_date + " - " + formatted_end_date + '</div>');
		}
	}
	else
	{
		$(table_css_selector).html('');
	}
}

function build_display_creatives_table(selector, data)
{
	$(selector).html('<div id="display_creatives_table"></div>');

	var table_css_selector = '#display_creatives_table';

	$(table_css_selector).html(
		'<table class="tab_table" cellspacing="0" class="order-column row-border" width="100%" style="display:none;"></table>'
	);
	$(table_css_selector + " .tab_table").show();

	var m_data_source = data;

	var creative_table_page_length = 4;
	var table_options = {
		//"data": data_table_rows_objects,
		"serverSide": true,
		"ordering": false,
		"searching": false,
		"order": new Array(new Array(0,'asc')),
		"lengthChange": false,
		"pageLength" : creative_table_page_length,
		"paging": "full_numbers",
		"columns": new Array({ title: "", data: 'creative_data', 'class': 'column_class', orderSequence: ['asc', 'desc']}),
		"ajax": function(data_tables_ajax_data, callback, settings) {
			var creative_ids = m_data_source.slice(data_tables_ajax_data.start, data_tables_ajax_data.start+data_tables_ajax_data.length);
			if(creative_ids.length > 0)
			{
				abort_secondary_subproduct_jqxhr();
				start_subproduct_loading_gif();
				g_secondary_subproduct_content_jqxhr = jQuery.ajax({
					async: true,
					type: "POST",
					data: { 
						advertiser_id: $("#report_v2_advertiser_options").val(),
						campaign_ids: $("#report_v2_campaign_options").val(),
						start_date: g_current_start_date,
						end_date: g_current_end_date,
						creative_ids: creative_ids
					},
					url: "/report_legacy/get_creative_messages",
					success: function(response_data, textStatus, jqXHR) {
						g_secondary_subproduct_content_jqxhr = null;
						if(vl_is_ajax_call_success(response_data))
						{
							var data_tables_data = {
								draw: data_tables_ajax_data.draw,
								recordsTotal: m_data_source.length,
								recordsFiltered: m_data_source.length,
								data: response_data.creative_data_sets
							};
							callback(data_tables_data);
							stop_subproduct_loading_gif();
						}
						else
						{
							stop_subproduct_loading_gif();

							handle_ajax_controlled_error(response_data, "Failed in /report_legacy/get_creative_messages");
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						g_secondary_subproduct_content_jqxhr = null;

						if(errorThrown !== "abort")
						{
							vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
							stop_subproduct_loading_gif();
						}
					},
					dataType: "json"
				});
			}
			else
			{
				var start_date = new Date(g_current_start_date);
				var formatted_start_date = (start_date.getMonth() + 1) + '/' + start_date.getDate() + '/' + start_date.getFullYear();
				var end_date = new Date(g_current_end_date);
				var formatted_end_date = (end_date.getMonth() + 1) + '/' + end_date.getDate() + '/' + end_date.getFullYear();
				$(table_css_selector).html('<div style="width:100%; font-size: 16px; text-align:center; color:grey; margin-top: 70px;">Insufficient data for selected campaigns in dates ' + formatted_start_date + " - " + formatted_end_date + '</div>');

			}

		},
		"drawCallback": function(settings) {
			$(".chart_content_div").trigger('create_chart');
			$("table.tab_table thead").remove();
			
		},
		"rowCallback": function(row, data, index) {
			var summary_id = 'creative_summary_'+index;
			var chart_container_id = 'creative_container_chart_'+index;
			var content_chart_id = 'creative_chart_'+index;

			var summary_titles = new Array(
				'Impressions',
				'Ad Interactions',
				'Ad Interaction Rate',
				'Clicks',
				'Click Rate'
			);

			var summary_values = data.creative_data.summary_values;

			var summary_html = '<div id="' + summary_id + '" class="summary_data">';

			var num_columns = summary_titles.length;
			for(var ii = 0; ii < num_columns; ii++)
			{
				if(!g_are_ad_interactions_visible && ii === 1)
				{
					ii += 2; // Skip Ad Interactions
				}
				summary_html += 
				'<div class="summary_data_column">' +
					'<div class="data_value">' +
					summary_values[ii] +
					'</div>' + 
					'<div class="data_label">' +
					summary_titles[ii] +
					'</div>' + 
				'</div> ';
			}
			summary_html += '</div> ';

			var cup_version_id = data.creative_data.cup_version_id;
			var $row = $(row);
			var creative_styles = "";
			if(data.creative_data.creative_width > 200)
			{
				creative_styles += "width:200px;";
			}
			else 
			{
				creative_styles += "height:167px;";
			}
			$row.html(
				'<td><div class="main_row" style="position:relative;width:100%;">'+
					'<table class="creative_table_row" width="100%"><tbody>' +
						'<tr>' +
							'<td style="width:200px;"><div class="creative_thumbnail"><div class="creative_thumbnail_overlay" /><div class="creative_thumbnail_container"><img src="'+data.creative_data.creative_thumbnail+'" style="'+creative_styles+'"></div></td>' +
							'<td>' +
								summary_html +
								'<div id="'+chart_container_id+'" style="width:100%;"><div id="'+content_chart_id+'" class="chart_content_div" style="width:100%;"></div></div>' +
							'</td>' +
						'</tr>' +
						'<tr><td colspan=2>' +
							'<div class="creative_preview collapse" data-parent="#display_creatives_table" ></div>' +
						'</td></tr>' +
					'</tbody></table>' +
				'</div></td>'
			);

			$('.creative_preview', $row).collapse({ toggle: false });

			$('.creative_thumbnail', $row).click(function(event) {
				var $open_accordion = $('.creative_preview.in');
				var $associated_accordion = $('.creative_preview', $(this).parents('table')[0]);

				if(!$associated_accordion.hasClass('in'))
				{
					$associated_accordion.html('<style type="text/css"> .wide_skyscraper { position:absolute; z-index:1028; top:20px; left:0px; } .large_rectangle { position: absolute; z-index: 1028; top: 140px; left: 527px; } .medium_rectangle { position:absolute; z-index:1028; top:140px; left:189px; } .leaderboard { position:absolute; z-index:1028; top:20px; left:189px; } .mobile { position:absolute; z-index:1028; top:124px; left:34px; height:50px; width:320px; } .standard_ads_preview { height:650px; } #phone_img, .phone_frame { height:778px; width:387px; } .mobile_ads_preview { height:930px; } .nav-tabs .tab_colored a { border-bottom-color: #ddd;} .nav-tabs .active a { border-bottom-color:transparent; } </style>' +
						'<div class="tabbable">' +
						'<ul class="nav nav-tabs" id="app_tab_ul">' +
						  '<li class="tab_colored active"><a class="preview_pane_tab" href="#standard_ads_'+cup_version_id+'" data-toggle="tab">Standard Ad Units</a></li>' +
						  '<li class="tab_colored"><a class="preview_pane_tab" href="#mobile_ads_'+cup_version_id+'" data-toggle="tab">Mobile Ad Units</a></li>' +
						  '<li class="pull-right">&nbsp;&nbsp;<button type="button" class="btn btn-success btn-small approval_page_refresh" id="approval_page_refresh"><i class="icon-refresh icon-white"></i></button></li>' +
						'</ul>' +
						'<div class="tab-content" style="position:relative;overflow:hidden;">' +
						  '<div class="tab-pane active standard_ads_preview" id="standard_ads_'+cup_version_id+'">' +
							'<iframe class="wide_skyscraper approval_iframe" src="/crtv/get_ad/'+cup_version_id+'/160x600" style="overflow:hidden;  width: 160px; height: 600px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0"><p>Your browser does not support iframes.</p></iframe>' +
							'<iframe class="leaderboard approval_iframe" src="/crtv/get_ad/'+cup_version_id+'/728x90" style="overflow:hidden;  width: 728px; height: 90px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0"><p>Your browser does not support iframes.</p></iframe>' +
							'<iframe class="large_rectangle approval_iframe" src="/crtv/get_ad/'+cup_version_id+'/336x280" style="overflow:hidden;  width: 336px; height: 280px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0"><p>Your browser does not support iframes.</p></iframe>' +
							'<iframe class="medium_rectangle approval_iframe" src="/crtv/get_ad/'+cup_version_id+'/300x250" style="overflow:hidden;  width: 300px; height: 250px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0"><p>Your browser does not support iframes.</p></iframe>' +
						  '</div>' +
						  '<div class="tab-pane mobile_ads_preview" id="mobile_ads_'+cup_version_id+'" style="position:relative;">' +
							'<div class="phone_frame">' +
								'<div class="phone_frame" style="position:absolute;top:0px;left:0px;z-index:1029"></div>' +
								'<img id="phone_img" src="https://s3.amazonaws.com/brandcdn-assets/images/frq_iphone6.png" alt="" />' +
								'<iframe class="mobile approval_iframe" src="/crtv/get_ad/'+cup_version_id+'/320x50" style="overflow:hidden;" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0"><p>Your browser does not support iframes.</p></iframe>' +
						  '</div>' +
						  '</div>' +
						'</div>' +
					  '</div>'
					);
					$associated_accordion.on('hidden', function() {
						$associated_accordion.off('hidden');
						$($associated_accordion, '.approval_page_refresh').off('click');
						$associated_accordion.html('');
					});
				}

				$associated_accordion.collapse('toggle');
				if($open_accordion !== $associated_accordion)
				{
					$open_accordion.collapse('hide');
				}

				$('.approval_page_refresh', $associated_accordion).click(function() {
					$(".approval_iframe").each(function(index) {
						$(this).attr("src", $(this).attr("src"));
					});
				});
			});

			$('#'+content_chart_id, $row).one('create_chart', data.creative_data.chart, function(event) {
				var chart_data = event.data;

				var num_graph_x_axis_items = chart_data.dates.length;

				var x_axis_tick_width = 100;
				var num_x_axis_ticks_to_show = (Math.floor($('#'+chart_container_id).width()/ x_axis_tick_width));
				var tick_interval = Math.ceil(num_graph_x_axis_items / num_x_axis_ticks_to_show);

				var is_engagements_visible = true;

				var highcharts_series = [
					{
						name: 'Impressions',
						visible: true,
						lineWidth: 1,
						type: 'area',
						zIndex: 0,
						yAxis: 0,
						data: chart_data.impressions,
						color: '#919194',
						marker: {
							enabled: false
						},
						legendIndex: 0
					},
					{
						name: 'Clicks',
						visible: true,
						color: '#0092e7',
						borderColor: 'white',
						borderWidth: 1,
						type: 'column',
						stack: 3,
						index: 4,
						zIndex: 3,
						yAxis: 1,
						data: chart_data.clicks,
						legendIndex: 1
					},
					{
						name: 'Ad Interactions',
						visible: g_are_ad_interactions_visible,
						color: '#4D4F53',
						borderColor: 'white',
						borderWidth: 1,
						type: 'column',
						stack: 2,
						index: 4,
						zIndex: 1,
						yAxis: 1,
						data: chart_data.engagements,
						legendIndex: 2
					}
				];

				var highcharts_options = {
					chart: {
						renderTo: content_chart_id,
						type:'line',
						height:140,
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
							text: 'Impressions'
						},
						labels:{
							style: {
								fontWeight: 'bold'
							}
						},
						min: 0
					},
					{
						title: {
							text: g_are_ad_interactions_visible == true ? 'Ad Interactions & Clicks' : 'Clicks',
							style: {
								"font-size": g_are_ad_interactions_visible == true ? "9px" : "10px"
							}
						},
						opposite: true
					}],
					xAxis: {
						labels:{
							rotation: 0,
							align: 'center',
							style: {
								'color': '#999999',
								'font-size': '10px'
							}
						},
						categories: chart_data.dates,
						tickInterval: tick_interval 
					},
					plotOptions: {
						column: {
							grouping: false,
							stacking: 'normal'
						}
					},
					legend: {
						enabled: false,
						align: 'center',
						x: 0,
						verticalAlign: 'top',
						y: 0,
						floating: false,
						borderWidth: 0,
						backgroundColor: 'rgba(0,0,0,0)',
						borderRadius: 2,
						itemHoverStyle: {
							color: '#f00'
						},
						itemHiddenStyle: {
							color: '#ccc'
						}
					},
					series: highcharts_series
				};

				build_chart(
					highcharts_options, 
					'#'+content_chart_id, 
					'#'+chart_container_id,
					x_axis_tick_width,
					num_graph_x_axis_items
				);

			});
		}
	};
	var data_table = $(table_css_selector + " .tab_table").dataTable(table_options);

	if(m_data_source.length <= creative_table_page_length)
	{
		$(".dataTables_paginate.paging_simple_numbers").hide()
	}
}

function build_heatmap_from_data(selector, data)
{
	if(heatmaps_on_page.hasOwnProperty(selector) && heatmaps_on_page[selector] != null)
	{
		heatmaps_on_page[selector].initialize_new_map();
		heatmaps_on_page[selector].build_heatmap_from_data(data);
	}
	else
	{
		heatmaps_on_page[selector] = new ReportHeatmap(selector);
		heatmaps_on_page[selector].build_heatmap_from_data(data);
	}
}

function inventory_lead_details_click_function(data_table, event_this)
{
	var row_data = data_table.api().row(event_this).data();
	var modal_data = row_data.hidden_data;
	
	var modal_header = "";
	if(modal_data.email === null)
	{
		modal_header = "Phone Lead: ";
	}
	else
	{
		modal_header = "Email Lead: ";
	}
	modal_header += modal_data.name;

	var modal_body = "<div> <b>Name:</b> "+modal_data.name+"</div>";
	modal_body += "<div> <b>Date:</b> "+modal_data.date+"</div>";
	if(modal_data.email !== null)
	{
		modal_body += "<div> <b>Email:</b> "+modal_data.email+"</div>";
	}
	modal_body += "<div> <b>Phone:</b> "+modal_data.phone+"</div>";
	modal_body += "<div> <b>Zip Code:</b> "+modal_data.zip_code+"</div>";
	if(modal_data.email !== null)
	{
		modal_body += "<div> <b>Details:</b> "+modal_data.comment+"</div>";
	}

	$("#inventory_leads_modal_header_content").html(modal_header);
	$("#inventory_leads_modal_body_content").html(modal_body);
	$("#inventory_leads_modal").modal('show');
}

function change_active_nav_pill(nav_pill_id)
{
	$('#subproduct_nav_pills > li.active').removeClass('active');
	$('#' + nav_pill_id).addClass('active');
}

function change_active_tab(tab_item_id)
{
	$('#new_tabs_radio > div.tabbable > ul > li.active').removeClass('active');
	$('#' + tab_item_id + '_nav').addClass('active');
}
