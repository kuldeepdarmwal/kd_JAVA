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
var g_loading_pills_first_time;
var g_products_clicked=false;
var g_nav_pill_selected_obj;

var g_chart_resources = new Array();
var g_overview_cards_chart_resources = new Array();
var g_product_detail_cards_chart_resources = new Array();

var maps_on_page = {};
var maps_product_sums = {};

var video_players = [];

var g_airings_content_jqxhr = null;
var g_overview_lift_triggered = false;
function unbind_chart_resize_callbacks(resources)
{
	var chart_resource;
	while((chart_resource = resources.pop()) !== undefined)
	{
		$(window).off('resize', chart_resource.resize_function);
	}
}

window.overview_calls = [];
function abort_overview_ajax()
{
	abort_map_loading_lqxhrs();
	$.each(window.overview_calls, function(i, call) {
		call.abort();
	});
	window.overview_calls = [];
}

window.onbeforeunload = function(e)
{
	abort_overview_ajax();
};

window.onload = function(){
	mixpanel.track("Page load");
	$(".navigation_container i.icon-caret-right").show();
};

window.product_card_row_calls = [];
function abort_product_card_row_ajax()
{
	abort_map_loading_lqxhrs();
	$.each(window.product_card_row_calls, function(i, call) {
		call.abort();
	});
	window.product_card_row_calls = [];
}

function abort_map_loading_lqxhrs()
{
	if(maps_on_page)
	{
		for(var map in maps_on_page)
		{
			maps_on_page[map].stop_and_clear_load_regions_queue();
		}
	}
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
		abort_airings_jqxhr();
}

function abort_airings_jqxhr()
{
	if(g_airings_content_jqxhr != null)
	{
		g_airings_content_jqxhr.abort();
		g_airings_content_jqxhr = null;
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
	if (g_current_product_tab == 'lift')
	{
		g_current_product_tab = "overview_product";
		$(".sidebar-nav .topparentnav").removeClass("topparentnavclicked");
		$("#overview_product > a").addClass("topparentnavclicked");
	}
	$("#overview_lift > a").addClass("disabled");
	update_page('change_campaign');
}

// An advertiser was selected from the Advertiser dropdown select list.
function select_advertiser()
{
	var advertiser_id = $("#report_v2_advertiser_options").val();
	if(advertiser_id && advertiser_id.length > 5)
	{
		document.getElementById('adv_search_span').innerHTML='Please reduce the number of selected Advertisers';
		return false;
	}
	$('#report_v2_advertiser_dropdown div.btn-group').removeClass('open');
	last_valid_selection = advertiser_id;

	if (g_current_product_tab == 'lift')
	{
		g_current_product_tab = "overview_product";
		$(".sidebar-nav .topparentnav").removeClass("topparentnavclicked");
		$("#overview_product > a").addClass("topparentnavclicked");
	}
	$("#overview_lift > a").addClass("disabled");

	update_page('change_advertiser');
}

// An advertiser was selected from the Advertiser dropdown select list.
function close_advertiser_popup()
{
	//$('#report_v2_advertiser_dropdown').val(last_valid_selection);
	//$('#report_v2_advertiser_dropdown').multiselect('refresh');

	//$('#report_v2_advertiser_dropdown').val(last_valid_selection);
	//$("#report_v2_advertiser_dropdown").multiselect('rebuild');
	$('#report_v2_advertiser_dropdown div.btn-group').removeClass('open');
}

// The start or end date was changed
function select_date(start, end, label)
{
	g_current_start_date = start.format('YYYY-MM-DD');
	g_current_end_date = end.format('YYYY-MM-DD');

	if (g_current_product_tab == 'lift')
	{
		g_current_product_tab = "overview_product";
		$(".sidebar-nav .topparentnav").removeClass("topparentnavclicked");
		$("#overview_product > a").addClass("topparentnavclicked");
	}
	$("#overview_lift > a").addClass("disabled");
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
		if (typeof campaigns !== 'undefined' && campaigns.length > 0)
		{
			if (!$("#report_v2_campaign_dropdown ul.multiselect-container").hasClass("dropdown-menu-active"))
			{
				$("#report_v2_campaign_dropdown ul.multiselect-container").addClass("dropdown-menu-active");
			}
			if (!$("#report_v2_campaign_dropdown .btn-group span.multiselect").hasClass("dropdown-toggle-active"))
			{
				$("#report_v2_campaign_dropdown .btn-group span.multiselect").addClass("dropdown-toggle-active");
			}

			$("#report_v2_campaign_options").multiselect('rebuild');
			$("#report_v2_campaign_options").multiselect('selectAll', false);
			$("#report_v2_campaign_options").multiselect('updateButtonText');


			enable_campaigns_select();
		}
		else
		{
			campaign_dropdown_multiselect();
		}
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
	$('#lift_analysis_overview').fadeOut(100);
	$("#lift_no_data_overview_overlay").fadeOut(100);
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
	$('#lift_analysis_overview').fadeOut(100);
	$('#lift_analysis_overview').fadeOut(100);
	$("#lift_no_data_overview_overlay").fadeOut(100);
}
function stop_overview_loading_gif(selector, callback)
{
	$('#overview_loading_image').fadeOut(100, function(){
		$(selector).fadeIn(100, function(){
			$(window).resize();
		});
		if(callback && typeof callback == 'function')
		{
			callback();
		}
	});
}

function start_campaign_lift_loading(lift_gif_img)
{
	$('#campaigns_lift_table').fadeTo(200, 0.2);
	if(lift_gif_img)
	{
		$(".campaigns_lift_loading_image").show();
	}
}

function stop_campaign_lift_loading()
{
	$('#campaigns_lift_table').fadeTo(200, 1);
	$(".campaigns_lift_loading_image").hide();
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
		vl_show_ajax_response_data_errors(data, 'Error, please try again');
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
		url: "/report_v2/get_campaigns",
		success: function(data, textStatus, jqXHR) {
			g_campaigns_jqxhr = null;
			if(vl_is_ajax_call_success(data))
			{
				if(data.real_data.request_id == g_get_report_campaigns_data_request_number)
				{
					if(data.real_data.campaigns)
					{
						apply_campaigns_data(
							data.real_data.campaigns,
							value_changed
						);
					}
					else
					{
						campaign_dropdown_multiselect();
					}

					if(g_tabs_visibility_jqxhr === null)
					{
						switch_tab_and_reload_content_for_inactive_tab();
					}
				}
			}
			else
			{
				stop_summary_loading_gif();

				handle_ajax_controlled_error(data, "Failed in /report_v2/get_campaigns");
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


function stop_summary_loading_gif()
{
	// todo place holder, missing function
}

function fix_product_details_chart_width()
{
	$chart = $('#product_details_card_row').find('.middle_card .card .chart');
	if(!$chart.is(':visible'))
	{
		$chart.fadeIn(200);
	}

	var highchart = $('#product_details_card_row .middle_card .chart').highcharts();
	if(highchart)
	{
		highchart.reflow();
		highchart.render();
		// Hacky way of removing empty legends that cover the true legend - MC
		$(".highcharts-legend g:empty").parents(".highcharts-legend").remove();
	}

	// The reflow fail to redraw chart in IE8, but makes it smooth for all other browsers.
	$(window).resize();
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
	handle_map_products_ajax_call_race_condition(false);
	g_get_report_tabs_visibility_data_request_number++;

	abort_tabs_visibility_jqxhr();
	abort_product_card_row_ajax();

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
		url: "/report_v2/get_products_tabs_and_views_nav_pills",
		success: function(data, textStatus, jqXHR) {
			g_tabs_visibility_jqxhr = null;
			if(vl_is_ajax_call_success(data))
			{
				var update_digital_overview = false;
				if(data.real_data.request_id == g_get_report_tabs_visibility_data_request_number)
				{
					var is_force_tab_content_refresh = false;

					if(value_changed != 'change_product' && value_changed != 'change_subproduct')
					{
						g_tabs_visibility = data.real_data.tabs_data.tabs_visibility;
						update_digital_overview = true;
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
						unbind_chart_resize_callbacks(g_product_detail_cards_chart_resources);

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

						if(new_active_product !== 'overview_product')
						{
							var $target = $('#product_details_card_row').empty();

							var new_active_product = data.real_data.tabs_data.new_active_product;
							var $cloned_card_row = $('#overview_tab_content .card_row.' + new_active_product).clone();

							$cloned_card_row.find('.chart').empty();
							$cloned_card_row.find('.card').fadeIn(0);
							$cloned_card_row.find('.middle_card .card .chart').hide(); // hide the chart until it is sized correctly
							$target.append($cloned_card_row);

							var product_detail_cards_ajax_load_selector = '#product_details_card_row div.card.ajax-load';
							if(update_digital_overview)
							{
								product_detail_cards_ajax_load_selector += ', #digital_overview_modal .card.ajax-load';
								$('#digital_overview_button').hide();
							}
							var product_detail_cards_all_loads_complete_callback = function(is_any_data) {
								show_lift_overview($("#report_v2_campaign_options").val());
								if($('.subproduct_loading_enclosure').is(':visible'))
								{
									// reflow highchart incase it was hidden when it was instantiated.
									// but don't do it if it isn't visible yet (subproduct data is still loading)
									// companion code to #reflow_highcharts
									fix_product_details_chart_width();
								}
							};

							load_cards(
								advertiser_id,
								campaign_values,
								start_date,
								end_date,
								value_changed,
								product_detail_cards_ajax_load_selector,
								window.product_card_row_calls,
								product_detail_cards_all_loads_complete_callback,
								g_product_detail_cards_chart_resources
							);
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

				handle_ajax_controlled_error(data, "Failed in /report_v2/get_products_tabs_and_views_nav_pills");
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			g_tabs_visibility_jqxhr = null;

			if(errorThrown !== "abort")
			{
				vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
				stop_summary_loading_gif();
			}
			else
			{
				update_digital_overview = true;
			}
		},
		dataType: "json",
		complete: function()
		{
			handle_map_products_ajax_call_race_condition(true);
			if(maps_on_page['#overview_map'])
			{
				maps_on_page['#overview_map'].add_product_html_to_map_form();
			}
		}
	});
}

function advertiser_group_conversion_for_id(advertiser_id)
{
	if (advertiser_id != undefined && advertiser_id != null)
	{
		for (var i=0; i < advertiser_id.length; i++)
		{
			if (advertiser_id[i].indexOf('ag-') != -1)
			{
				sub_adv_id_array=advertiser_id[i].split('-');
				for (var j=1; j < sub_adv_id_array.length; j++)
				{
					advertiser_id[advertiser_id.length]=sub_adv_id_array[j];
				}
				advertiser_id[i]=advertiser_id[advertiser_id.length-1];
			}
		}
	}
	return advertiser_id;
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
	advertiser_id= advertiser_group_conversion_for_id(advertiser_id);

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

function load_cards(
	advertiser_id,
	campaign_values,
	start_date,
	end_date,
	value_changed,
	card_loads_selector,
	card_load_calls,
	card_loads_complete_callback,
	resize_resources
)
{
	$.each(card_load_calls, function(i, call){
		call.abort();
	});
	card_load_calls.length = 0;

	var timezone_offset = new Date().getTimezoneOffset()/60;
	$(card_loads_selector).each(function() {
		var card = $(this);
		card_load_calls.push($.ajax({
			url: "/report_v2/get_overview_card_data",
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
				timezone_offset: timezone_offset
			},
			success: function(data) {
				$(card).fadeIn(0)
				if (data.length === 0) {
					$(".chart", card).css('visibility', 'hidden');
					$(card).addClass('no_data');
				} else {
					populate_overview_card(data, card, resize_resources);
					$(".chart", card).css('visibility', 'visible');
					$(card).removeClass('no_data');

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

	$.when.apply($, card_load_calls).then(function(){
		var args = Array.prototype.slice.call(arguments);

		var has_data = false;
		$.each(args, function(i, val){
			if (val[0].length > 0 || !$.isEmptyObject(val[0])){
				has_data = true;
			}
		});
		var any_card_has_data = false;
		$.each($("div.card_row"), function (index, card_row) {
			if($(card_row).find('div.card').length === $(card_row).find('div.card.no_data').length)
			{
				$(card_row).fadeOut(0);
			}
			else
			{
				$(card_row).fadeIn(0);
				any_card_has_data = true;
			}
		});
		var is_any_data = has_data && any_card_has_data;
		card_loads_complete_callback(is_any_data);
		card_load_calls.length = 0;
	});
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
	abort_overview_ajax();

	unbind_chart_resize_callbacks(g_chart_resources);
	unbind_chart_resize_callbacks(g_overview_cards_chart_resources);

	if($('#overview_tab_content').is(':visible') || active_product === 'overview_product')
	{
		start_overview_loading_gif();
	}

	if($('.subproduct_loading_enclosure').is(':visible') && active_product !== 'overview_product')
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
			url: "/report_v2/get_headline_data",
			success: function(data, textStatus, jqXHR) {
				g_headline_jqxhr = null;
				if(vl_is_ajax_call_success(data))
				{
					if(data.real_data.request_id == g_get_report_headline_data_request_number)
					{
						// switching for inactive tab handled on campaign & tab_visibility loads
						$('.subproduct_loading_enclosure').hide(0);

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
					handle_ajax_controlled_error(data, "Failed in /report_v2/get_headline_data");
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

		/*
		g_get_report_chart_data_request_number++;

		g_chart_jqxhr = jQuery.ajax({
			url: "/report_v2/get_graph_data",
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

						$('.subproduct_loading_enclosure').hide(0);

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

					handle_ajax_controlled_error(data, "Failed in /report_v2/get_graph_data");
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
		*/

		var update_digital_overview = true;
		if(value_changed != "change_product")
		{
			$('#digital_overview_button').hide();
			update_digital_overview = true;
		}

		var overview_cards_ajax_load_selector = '#overview_tab_content div.card.ajax-load';
		if(update_digital_overview)
		{
			overview_cards_ajax_load_selector += ', #digital_overview_modal .card.ajax-load';
		}

		var overview_cards_all_loads_complete_callback = function(is_any_data) {
			show_lift_overview($("#report_v2_campaign_options").val());
			stop_overview_loading_gif(is_any_data ? '#overview_tab_inner_content' : '#no_data_overview_overlay', function(){
				redraw_map_if_exists('#overview_map');
				$("#campaigns_overview_title #campaigns_live_date").html($("#report_v2_date_range_input").val());
				$("#digital_overview_date").html($("#report_v2_date_range_input").val());
				$("#campaigns_overview_title").show();
			});
		};

		load_cards(
			advertiser_id,
			campaign_values,
			start_date,
			end_date,
			value_changed,
			overview_cards_ajax_load_selector,
			window.overview_calls,
			overview_cards_all_loads_complete_callback,
			g_overview_cards_chart_resources
		);
	}
	else
	{
		g_subproduct_content_jqxhr = jQuery.ajax({
			url: "/report_v2/get_subproduct_content_data",
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
				active_subproduct: active_subproduct,
				timezone_offset: new Date().getTimezoneOffset()/60
			},
			success: function(data, textStatus, jqXHR) {
				g_subproduct_content_jqxhr = null;
				if(vl_is_ajax_call_success(data))
				{
					if(data.real_data.is_subproduct_data_response === true)
					{
						$('#overview_tab_content').hide(0);
						$('.subproduct_loading_enclosure').show();

						for (var i=0;i<data.real_data.subproduct_data.length;i++)
						{
							if (i === 0)
							{
								$('#subproduct_content').html(data.real_data.subproduct_data[i].subproduct_html_scaffolding);
							}
							else
							{
								$('#subproduct_content').append(data.real_data.subproduct_data[i].subproduct_html_scaffolding);
							}
							build_subproduct_visuals(data.real_data.subproduct_data[i].subproduct_data_sets);
						}

						nav_pill_click(null);
						stop_subproduct_loading_gif();
						stop_overview_loading_gif();
						update_csv_access();

						if(window.product_card_row_calls.length == 0)
						{
							// reflow highchart incase it was hidden when it was instantiated.
							// but don't do it if it isn't visible yet (product cards are still loading)
							// companion code to #reflow_highcharts
							fix_product_details_chart_width();
						}
					}
					else
					{
						// Not subproduct data response due to switching data set and tab change.  New data loaded elsewhere.
					}
				}
				else
				{
					handle_ajax_controlled_error(data, "Failed in /report_v2/get_subproduct_content_data");

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

function format_overview_tv_airing_row(row_data)
{
	var html =
		'<tr style="height:61px;">' +
			'<td style="padding:0;padding-left:5px;height:30px;width:58px;text-align:left;"><div><div class="tv_creative_thumbnail_container" style="width:47px;margin-left:10px;height:30px;display:inline-block;position:relative;"><div class="tv_overview_thumbnail_overlay"></div><img class="tv_overview_creative_thumbnail"" src="'+row_data.creative.creative_thumbnail+'"></div></div></td>' +
			'<td style="width:40%;"><div style="padding-left:15px;text-align:left;word-break:break-word;">'+row_data.creative.creative_name+'</div></td>' +
			'<td style="width:8%;padding:0;"><img src="'+row_data.network_icon+'" style="height:40px;"/></td>' +
			'<td style="width:20%;word-break:break-word"><b><i>'+row_data.show+'</i></b></td>' +
			'<td style="width:25%;word-break:break-word">'+row_data.zone_friendly_name+'<br>'+row_data.airing_time+'</td>' +
		'</tr>';
	return html;
}

function populate_overview_card(product_data, card, resize_resources) {
	var chart_type = card.data('chart-type');
	var data_source = card.data('source');

	switch (chart_type) {
		case 'tv':
			var chart = $(card).find('.chart');
			$(chart).html('<table class="tab_table primary_table" cellspacing="0" class="order-column row-border" width="100%" ></table>');
			$.each(product_data, function(key, value)
			{
				//$('table', chart).append(format_overview_tv_airing_row(this));
				var row_data = this;
				var new_row = $(format_overview_tv_airing_row(this)).appendTo($('table', chart));
				$('.tv_creative_thumbnail_container', new_row).click(function(event) {
					var modal_header = row_data.creative.creative_name;
					var modal_body = build_video_preview_modal_body(row_data.creative);
					$("#tv_creative_video_modal_header_content").html(modal_header);
					$("#tv_creative_video_modal_body_content").html(modal_body);
					$("#tv_creative_video_modal").modal('show');
					$('a[data-toggle="tab"]').on('shown', function(e) {
						$(e.relatedTarget.hash + " video").get(0).pause();
						$(e.target.hash + " video").get(0).play();
					});
					event.stopPropagation();
				});


			});
			$("#tv_creative_video_modal").on('hidden', function(){
				$("#tv_creative_video_modal_body_content").html('');
			});
			break;
		case 'creatives':
			var chart = $(card).find('.chart');
			chart.html('<img class="thumbnail switchable disabled" src="" /><div class="stats"><div class="impressions report_tooltip" data-content="Ads or listings shown to audiences" data-trigger="hover" data-placement="top" data-delay="160"><span class="value"></span><span>Impressions</span></div><div class="interaction_rate report_tooltip" data-content="Ad Interactions / Impressions" data-trigger="hover" data-placement="top" data-delay="160"><span class="value"></span><span>Interaction Rate</span></div></div>');
			if (product_data.length > 1)
			{
				$(chart).append('<div class="button prev"><img src="/assets/img/btn-prev.png"/></div><div class="button next"><img src="/assets/img/btn-next.png"/></div>');
			}
			$(chart).data('creatives', product_data);
			$(chart).data('creativesIndex', 0);

			$(chart).find('.button.prev').on('click', function(){
				var index = $(chart).data('creativesIndex') == 0 ? $(chart).data('creatives').length - 1 : $(chart).data('creativesIndex') - 1;
				$(chart).data('creativesIndex', index);
				populate_creatives_overview_card(product_data[index].data.thumbnail, product_data[index].data.impressions, product_data[index].data.interaction_rate, card);
			});

			$(chart).find('.button.next').on('click', function(){
				var index = $(chart).data('creativesIndex') == $(chart).data('creatives').length - 1 ? 0 : $(chart).data('creativesIndex') + 1;
				$(chart).data('creativesIndex', index);
				populate_creatives_overview_card(product_data[index].data.thumbnail, product_data[index].data.impressions, product_data[index].data.interaction_rate, card);
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
		case 'bar_networks':
			$(card).find('.chart').highcharts({
				chart: {
					type: 'bar',
					height: 230,
					marginTop: 10,
					marginBottom: 10,
					marginRight: 15
				},
				series: [{
					name: this.units,
					pointWidth: 20,
					data: $.map(product_data.graph_data, function(curr){
						return {
							'name': curr.friendly_name,
							'y': curr.data
						};
					}),
					dataLabels: {
						enabled: true,
						inside: true,
						align: 'left',
						crop: false,
						overflow: 'none',
						style: {
							y: 0,
							x: 0,
							fontSize: 12,
							color: 'white'
						},
						formatter: function() {
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
						},
						useHTML: true,
						formatter: function(){
							return '<div style="width:40px;text-align:center;margin-top:-2px;"><img src="'+product_data.network_icons[this.value]+'" style="display:block;width:40px;height:30px;margin:0 auto;"/></div>';
						}
					},
					categories: $.map(product_data.graph_data, function(curr){
						if (curr.name.length > 16) {
							return curr.name.substring(0, 14) + '...';
						}
						return curr.name;
					}),
					minorTickLength: 0,
					tickLength: 0
				},
				yAxis: {
					min: 0.9,
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
					type: 'linear',
					title: {
						style: {
							display: 'none'
						}
					}
				},
				tooltip: {
					formatter: function () {
						return this.key + ': ' + Highcharts.numberFormat(this.y, 0) + " " + product_data.units;
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
			var impressions = product_data.completion_data[0].data;
			if(product_data.max_completion_date != null)
			{
				$('.completions_date_warning').html("* data available up to " + product_data.max_completion_date);
				$('.completions_date_warning').show();
			}
			else
			{
				$('.completions_date_warning').html('');
				$('.completions_date_warning').hide();
			}
			$(card).find('.chart').highcharts({
				chart: {
					type: 'bar',
					height: 220,
					marginTop: 10,
					marginBottom: 0,
					marginLeft: 70,
					marginRight: -40,
					events : {
						redraw: function()
						{
							$(".report_tooltip").popover();
						}
					}

				},
				series: [{
					name: "Completions",
					data: $.map(product_data.completion_data, function(curr){
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
						},
						formatter:function() {
							var rate = (this.y / impressions) * 100;
							if(rate != 100)
							{
								return Highcharts.numberFormat(rate, 1) + '%';
							}
							else
							{
								return Highcharts.numberFormat(rate, 0) + '%';
							}
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
								'Started': '<div class="report_tooltip" title="Starts" data-placement="top" data-trigger="hover" data-content="Number of impressions where the video was played. Event is logged once per view. If a user stops play and restarts it, the restart isn\'t counted." style="width:50px;text-align:center;"><img src="/assets/img/completion-0.png" style="display:block;height:20px;margin:0 auto;"/> Started</div>',
								'25% Viewed': '<div class="report_tooltip" title="25% Viewed" data-placement="top" data-trigger="hover" data-content="Number of times the video played to 25% of its length." style="width:50px;text-align:center;"><img src="/assets/img/completion-25.png" style="display:block;height:20px;margin:0 auto;"/> 25% Viewed</div>',
								'50% Viewed': '<div class="report_tooltip" title="50% Viewed" data-placement="top" data-trigger="hover" data-content="Number of times the video reached its midpoint during play. Event is logged once per view." style="width:50px;text-align:center;"><img src="/assets/img/completion-50.png" style="display:block;height:20px;margin:0 auto;"/> 50% Viewed</div>',
								'75% Viewed': '<div class="report_tooltip" title="75% Viewed" data-placement="top" data-trigger="hover" data-content="Number of times the video played to 75% of its length." style="width:50px;text-align:center;"><img src="/assets/img/completion-75.png" style="display:block;height:20px;margin:0 auto;"/> 75% Viewed</div>',
								'Completed': '<div class="report_tooltip" title="Completions" data-placement="top" data-trigger="hover" data-content="Number of times the video played to completion. Event is logged once per view. If a user restarts the clip, it\'s not counted again." style="width:50px;text-align:center;"><img src="/assets/img/completion-100.png" style="display:block;height:20px;margin:0 auto;"/> Completed</div>'
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
						return Highcharts.numberFormat(this.y, 0) + " views";
						/*
						return {
							'Started': 'Number of impressions where the video was played. Event is logged once per view. If a user stops play and restarts it, the restart isn\'t counted.',
							'25% Viewed': 'Number of times the video played to 25% of its length.',
							'50% Viewed': 'Number of times the video reached its midpoint during play. Event is logged once per view.',
							'75% Viewed': 'Number of times the video played to 75% of its length.',
							'Completed': 'Number of times the video played to completion. Event is logged once per view. If a user restarts the clip, it\'s not counted again.'
						}[this.key];
						*/
					},
					positioner: function(labelWidth, labelHeight, point){
						var y = (point.plotY + 100) > 220 ? point.plotY - 10 - labelHeight : point.plotY + 33;
						return {
							x: 70,
							y: y
						};
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
			var total = 0;
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
					marginLeft: marginLeft,
					events : {
						redraw: function()
						{
							// Fixes problem where chart displayed
							fix_product_details_chart_width();
						}
					}
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
						return curr.name;
					})
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
			var pie_colors = ['#005B89', '#006ca3', '#0074B0', '#ccc'];

			if(data_source === 'inventory_price')
			{
				pie_colors = ['#09a0b2', '#5389d6', '#004465', '#ccc'];
			}

			$(card).find('.chart').highcharts({
				chart: {
					type: 'pie',
					height: '230',
					width:'250'
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
				colors: pie_colors,
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
				},
				tooltip: {
					formatter: function() {
						return 'Price Breakdown : <br/><b>'+Math.round(this.percentage)+'%</b> listed near '+this.key;
					}
				}
			});
			break;
		case 'list':
			$(card).find('.chart').empty();
			$.each(product_data, function(i, item){
				$(card).find('.chart').append('<div class="search-product"><span class="name">'+item.name+'</span><span class="value">'+Highcharts.numberFormat(item.data, 0)+'</span></div>');
			});
			break;
		case 'list2':
			$(card).find('.chart').empty();
			$.each(product_data, function(i, item) {
				var formatted_number;
				if(item.number_type && item.number_type == 'percent')
				{
					formatted_number = Highcharts.numberFormat(item.data, 1) + '%';
				}
				else if(item.number_type && item.number_type == 'non-number')
				{
					formatted_number = item.data;
				}
				else
				{
					formatted_number = Highcharts.numberFormat(item.data, 0);
				}

				$(card).find('.chart').append('<div class="product-total"><table><tr><td><div class="value">'+formatted_number+'</span></td></tr><tr><td><div class="name">'+item.name+'</span></td></tr></table></div>');
			});
			break;
		case 'timeseries-shared-chart':
			var series_config_items_map = {
				'total_impressions': {
					'type': 'area',
					'name': 'Impressions',
					'z_index': 0,
					'y_axis': 0,
					'color': '#919194',
					'legend_index': 0
				},
				'retargeting_impressions': {
					'type': 'area',
					'name': 'Retargeting Impressions',
					'z_index': 1,
					'y_axis': 0,
					'color': '#00629b',
					'legend_index': 1
				},
				'visits': {
					'type': 'column',
					'name': 'Visits',
					'z_index': 3,
					'y_axis': 1,
					'color': '#dcdce0',
					'legend_index': 3,
					'stack': 1,
					'index': 3
				},
				'clicks': {
					'type': 'column',
					'name': 'Clicks',
					'z_index': 4, // TODO: Use initial_click_z_index
					'y_axis': 1,
					'color': '#0092e7',
					'legend_index': 2,
					'stack': 3, // TODO: initial_click_stack
					'index': 4
				},
				'engagements': {
					'type': 'column',
					'name': 'Engagements',
					'z_index': 2,
					'y_axis': 1,
					'color': '#4d4f53',
					'legend_index': 4,
					'stack': 2,
					'index': 4
				},
				'leads': {
					'type': 'column',
					'name': 'Leads',
					'z_index': 2,
					'y_axis': 1,
					'color': '#09a0b2',
					'legend_index': 5,
					'stack': 3,
					'index': 1
				},
				'airings': {
					'type': 'column',
					'name': 'Airings',
					'z_index': 2,
					'y_axis': 1,
					'color': '#0092e7',
					'legend_index': 5,
					'stack': 3,
					'index': 1
				}
			};

			var chart_config_items = [];
			$.each(product_data.series_data, function (index, series)
			{
				var series_config = series_config_items_map[series.data_purpose];
				var series_item = {
					'series_config': series_config,
					'data': series.data
				};

				chart_config_items.push(series_item);
			});

			var chart_config = {
				'items': chart_config_items,
				'height': 200,
				'y_axis': [{
						showEmpty: false,
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
						showEmpty: false,
						title: {
							text: 'Engagements',
							style: {
								"font-size": "10px"
							}
						},
						opposite: true
					}
				],
				'x_axis_data': product_data.x_axis_data,
				'is_legend_enabled': true
			};

			var x_axis_tick_width = 20;
			var $chart_container_element = $(card).find('.chart');
			var chart_options = setup_chart_options(
				chart_config,
				$chart_container_element,
				x_axis_tick_width
			);

			var highchart = $chart_container_element.highcharts(chart_options).highcharts();
			window.nugget_highchart = highchart;

			attach_chart_function_for_resizing_ticks(
				resize_resources,
				highchart,
				$chart_container_element,
				x_axis_tick_width,
				product_data.x_axis_data.length
			);
			break;

		case 'timeseries-1-column':
			var chart_config_items = [];

			var y_axis_title = 'Impressions';
			var map_data_source_to_y_axis_title = {
				'timeseries_tv_verified': "Airings",
				'timeseries_content_product': "Views"
			};

			if(map_data_source_to_y_axis_title.hasOwnProperty(data_source))
			{
				y_axis_title = map_data_source_to_y_axis_title[data_source];
			}

			$.each(product_data.series_data, function(index, item) {
				var series_config;
				if(typeof item.push == 'function') // Hacky ie8 fix
				{
					series_config = {
						'type': 'column',
						'name': y_axis_title,
						'z_index': 2,
						'y_axis': 0,
						'color': '#0092e7'
					};
				}
				else
				{
					// TODO: throw error?
				}

				chart_config_item = {
					'series_config': series_config,
					'data': (typeof item.push == 'function') ? item : item.data // Hacky ie8 fix
				};
				chart_config_items.push(chart_config_item);
			});

			if(map_data_source_to_y_axis_title.hasOwnProperty(data_source))
			{
				y_axis_title = map_data_source_to_y_axis_title[data_source];
			}

			var chart_config = {
				'items': chart_config_items,
				'height': 200,
				'y_axis_title': y_axis_title,
				'x_axis_data': product_data.x_axis_data
			};

			var x_axis_tick_width = 20;
			var $chart_container_element = $(card).find('.chart');
			var chart_options = setup_chart_options(
				chart_config,
				$chart_container_element,
				x_axis_tick_width
			);

			var highchart = $chart_container_element.highcharts(chart_options).highcharts();

			attach_chart_function_for_resizing_ticks(
				resize_resources,
				highchart,
				$chart_container_element,
				x_axis_tick_width,
				product_data.x_axis_data.length
			);
			break;
		case 'map':
			build_overview_map_from_data('#overview_map', product_data.overview_geojson);
			break;
		case 'digital_overview':
			if(product_data.overview_data.length > 0)
			{
				var totals = null;
				$("#overview_digital_overview").html('');
				$("#overview_digital_overview").append('<table id="digital_overview_table" class="digital_overview_card_table"></table>');
				$("#digital_overview_table").append('<thead class="digital_overview_header_row">' +
					'<tr>' +
					'<th style="position:static;"></th>' +
					'<th class="tooltip_triangle"><div class="report_tooltip" data-trigger="hover" data-placement="top" data-content="Ads or listings shown to audiences">Impressions</div></th>' +
					'<th class="tooltip_triangle"><div class="report_tooltip" data-trigger="hover" data-placement="top" data-content="Visits + Ad Hovers + Video Plays + Other Audience Initiated Interactions With Campaigns">Total Engagements</div></th>' +
					'<th class="tooltip_triangle"><div class="report_tooltip" data-trigger="hover" data-placement="top" data-content="Single Hovers + Full Screen Video Clicks + Video Plays">Ad Interactions</div></th>' +
					'<th class="tooltip_triangle"><div class="report_tooltip" data-trigger="hover" data-placement="top" data-content="Click-Throughs + View-Throughs">Visits</div></th>' +
					'<th class="tooltip_triangle"><div class="report_tooltip" data-trigger="hover" data-placement="top" data-content="Visits to conversion page by impressioned users (who did not click)">View Throughs</div></th>' +
					'<th class="tooltip_triangle"><div class="report_tooltip" data-trigger="hover" data-placement="top" data-content="Clicks on Ads or Listings (aka Click-Throughs)">Clicks</div></th>' +
					'<th class="tooltip_triangle"><div class="report_tooltip" data-trigger="hover" data-placement="top" data-content="Impressions from retargeting program where site visitors are shown follow-on ads or listings">Retargeting</div></th>' +
					(product_data.is_tmpi_accessible ?
						'<th class="tooltip_triangle"><div class="report_tooltip" data-trigger="hover" data-placement="top" data-content="Phone or email requests">Leads</div></th>' +
						'<th class="tooltip_triangle"><div class="report_tooltip" data-trigger="hover" data-placement="top" data-content="Times users viewed or interacted With your Local Search profile">Profile Activity</div></th>' +
						'<th class="tooltip_triangle"><div class="report_tooltip" data-trigger="hover" data-placement="top" data-content="Times Targeted Content video clips were viewed">Content Views</div></th>' : '') +
					'</tr></thead>');

				maps_product_sums = {};
				$.each(product_data.overview_data, function(key, value)
				{
					var has_impressions = this.impressions > 0;
					if(has_impressions)
					{
						maps_product_sums[this.friendly_name] = Highcharts.numberFormat(this.impressions, 0);
					}

					$("#digital_overview_table").append('<tr class="digital_overview_row enabled">' +
						'<td class="digital_overview_type_cell"><table><tr style="border:none;"><td style="border:none;"><input type="checkbox" checked="true"></td><td style="border:none;text-align:left;padding-left:15px;padding-right:5px;">'+this.type+'</td></tr></table></td>' +
						'<td class="digital_overview_cell impressions_cell">'+
							'<div>' +
								'<div class="digital_overview_value">'+(this.impressions > 0 ? Highcharts.numberFormat(this.impressions,0) : '&nbsp;')+'</div>' +
							'</div>'+
						'</td>' +
						'<td class="digital_overview_cell total_engagements_cell' +(this.total_engagements > 0 && has_impressions ? ' tooltip_triangle' : '')+'">' +
							(this.total_engagements > 0 && has_impressions ? '<div class="report_tooltip" data-trigger="hover" data-placement="top" title="Engagement Rate" data-content="Engagement Rate: '+Highcharts.numberFormat((this.total_engagements/this.impressions)*100, 2)+'%">' : '<div>') +
								'<div class="digital_overview_value">'+(this.total_engagements > 0 ? Highcharts.numberFormat(this.total_engagements,0) : '&nbsp;')+'</div>' +
							'</div>'+
						'</td>' +
						'<td class="digital_overview_cell ad_interactions_cell' +(this.ad_interactions > 0 && has_impressions ? ' tooltip_triangle' : '')+'">' +
							 (this.ad_interactions > 0 && has_impressions ? '<div class="report_tooltip" data-trigger="hover" data-placement="top" title="Interaction Rate" data-content="Interaction Rate: '+Highcharts.numberFormat((this.ad_interactions/this.impressions)*100, 2)+'%">' : '<div>') +
								'<div class="digital_overview_value">'+(this.ad_interactions > 0 ? Highcharts.numberFormat(this.ad_interactions,0) : '&nbsp;')+'</div>' +
							'</div>'+
						'</td>' +
						'<td class="digital_overview_cell visits_cell' +(this.visits > 0 && has_impressions ? ' tooltip_triangle' : '')+'">' +
							(this.visits > 0 && has_impressions ? '<div class="report_tooltip" data-trigger="hover" data-placement="top" title="Visit Rate" data-content="Visit Rate: '+Highcharts.numberFormat((this.visits/this.impressions)*100, 2)+'%">' : '<div>') +
								'<div class="digital_overview_value">'+(this.visits > 0 ? Highcharts.numberFormat(this.visits,0) : '&nbsp;')+'</div>' +
							'</div>' +
						'</td>' +
						'<td class="digital_overview_cell view_throughs_cell' +(this.view_throughs > 0 && has_impressions ? ' tooltip_triangle' : '')+'">' +
							(this.view_throughs > 0 && has_impressions ? '<div class="report_tooltip" data-trigger="hover" data-placement="top" title="View-Through Rate" data-content="View-Through Rate: '+Highcharts.numberFormat((this.view_throughs/this.impressions)*100, 2)+'%">' : '<div>') +
								'<div class="digital_overview_value">'+(this.view_throughs > 0 ? Highcharts.numberFormat(this.view_throughs,0) : '&nbsp;')+'</div>' +
							'</div>' +
						'</td>' +
						'<td class="digital_overview_cell clicks_cell' +(this.clicks > 0 && has_impressions ? ' tooltip_triangle' : '')+'">' +
							(this.clicks > 0 && has_impressions ? '<div class="report_tooltip" data-trigger="hover" data-placement="top" title="Click-Through Rate" data-content="Click-Through Rate: '+Highcharts.numberFormat((this.clicks/this.impressions)*100, 2)+'%">' : '<div>') +
								'<div class="digital_overview_value">'+(this.clicks > 0 ? Highcharts.numberFormat(this.clicks,0) : '&nbsp;')+'</div>' +
							'</div>' +
						'</td>' +
						'<td class="digital_overview_cell retargeting_impression_cell' +(this.retargeting_impressions > 0 && this.retargeting_clicks > 0 ? ' tooltip_triangle' : '')+'">' +
							(this.retargeting_impressions > 0 && this.retargeting_clicks > 0 ? '<div class="report_tooltip" data-trigger="hover" data-placement="top" title="Retargeting Clickthrough Rate" data-content="Retargeting Clickthrough Rate: '+Highcharts.numberFormat((this.retargeting_clicks/this.retargeting_impressions)*100, 2)+'%">' : '<div>') +
								'<div class="digital_overview_value">'+(this.retargeting_impressions > 0 ? Highcharts.numberFormat(this.retargeting_impressions,0) : '&nbsp;')+'</div>' +
							'</div>' +
						'</td>' +
						(product_data.is_tmpi_accessible ?
							'<td class="digital_overview_cell leads_cell">' +
								'<div>' +
									'<div class="digital_overview_value">'+(this.leads > 0 ? Highcharts.numberFormat(this.leads,0) : '&nbsp;')+'</div>' +
								'</div>' +
							'</td>' +
							'<td class="digital_overview_cell profile_activity_cell">' +
								'<div>' +
									'<div class="digital_overview_value">'+(this.profile_activity > 0? Highcharts.numberFormat(this.profile_activity,0) : '&nbsp;')+'</div>' +
								'</div>' +
							'</td>' +
							'<td class="digital_overview_cell content_views_cell">' +
								'<div>' +
									'<div class="digital_overview_value">'+(this.content_views > 0 ? Highcharts.numberFormat(this.content_views,0) : '&nbsp;')+'</div>' +
								'</div>' +
							'</td>' : '') +
						'</tr>');

					if(totals == null)
					{
						totals = this;
						totals.type = "totals:";
					}
					else
					{
						totals.impressions += this.impressions;
						totals.total_engagements += this.total_engagements;
						totals.ad_interactions += this.ad_interactions;
						totals.visits += this.visits;
						totals.view_throughs += this.view_throughs;
						totals.clicks += this.clicks;
						totals.retargeting_impressions += this.retargeting_impressions;
						if(product_data.is_tmpi_accessible)
						{
							totals.leads += this.leads;
							totals.profile_activity += this.profile_activity;
							totals.content_views += this.content_views;
						}
					}
				});
				if(product_data.overview_data.length > 1)
				{
					$("#digital_overview_table").append('<tr>' +
						'<td class="digital_overview_type_cell" style="text-align:center;">'+totals.type+'</td>' +
						'<td class="digital_overview_total_cell" total-source="impressions_cell">'+Highcharts.numberFormat(totals.impressions,0)+'</td>' +
						'<td class="digital_overview_total_cell" total-source="total_engagements_cell">'+Highcharts.numberFormat(totals.total_engagements,0)+'</td>' +
						'<td class="digital_overview_total_cell" total-source="ad_interactions_cell">'+Highcharts.numberFormat(totals.ad_interactions,0)+'</td>' +
						'<td class="digital_overview_total_cell" total-source="visits_cell">'+Highcharts.numberFormat(totals.visits,0)+'</td>' +
						'<td class="digital_overview_total_cell" total-source="view_throughs_cell">'+Highcharts.numberFormat(totals.view_throughs,0)+'</td>' +
						'<td class="digital_overview_total_cell" total-source="clicks_cell">'+Highcharts.numberFormat(totals.clicks,0)+'</td>' +
						'<td class="digital_overview_total_cell" total-source="retargeting_impression_cell">'+Highcharts.numberFormat(totals.retargeting_impressions,0)+'</td>' +
						(product_data.is_tmpi_accessible ?
							'<td class="digital_overview_total_cell" total-source="leads_cell">'+Highcharts.numberFormat(totals.leads,0)+'</td>' +
							'<td class="digital_overview_total_cell" total-source="profile_activity_cell">'+Highcharts.numberFormat(totals.profile_activity,0)+'</td>' +
							'<td class="digital_overview_total_cell" total-source="content_views_cell">'+Highcharts.numberFormat(totals.content_views,0)+'</td>' : '') +
					'	</tr>');

					$(".digital_overview_type_cell input").click(function(){
						var toggle_row = $(this).parents('.digital_overview_row');
						if(this.checked)
						{
							//enable the row
							$(this).parents('.digital_overview_row').addClass('enabled');
							$('td.digital_overview_cell',$(this).parents('.digital_overview_row')).css('color', '');
							retabulate_digital_overview_totals();
						}
						else
						{
							//disable the row
							$(this).parents('.digital_overview_row').removeClass('enabled');
							$('td.digital_overview_cell',$(this).parents('.digital_overview_row')).css('color', '#e5e5e5');
							retabulate_digital_overview_totals();
						}
					});
				}
				else
				{
					$(".digital_overview_type_cell input").attr("disabled", true);
				}
				$('#digital_overview_button').show();
				$('#digital_overview_button i').popover();
				$('#digital_overview_button i').hover(function(e){
					e.preventDefault();
					e.stopPropagation();
					$(this).removeClass('digital_overview_button_no_hover');
					$(this).addClass('digital_overview_button_hover');
				},
				function(e){
					e.preventDefault();
					e.stopPropagation();
					$(this).removeClass('digital_overview_button_hover');
					$(this).addClass('digital_overview_button_no_hover');
				});

				$('#digital_overview_button i').on('click', function(e){
					e.preventDefault();
					e.stopPropagation();
					$('#digital_overview_modal').modal('show');
				});

				if(maps_on_page['#overview_map'])
				{
					maps_on_page['#overview_map'].populate_impression_totals();
				}
			}
			break;
		default:
			break;
	}
}

function retabulate_digital_overview_totals()
{
	var totals_cells = $(".digital_overview_total_cell");
	$.each(totals_cells, function(key, value)
	{
		var sum_cell_class = $(this).attr('total-source');
		var cells_to_sum = $('.enabled .'+sum_cell_class);
		var total = 0;
		$.each(cells_to_sum, function(key, value)
		{
			var number = $('div.digital_overview_value', this).html();
			number = parseInt(number.replace(/,/g, ''), 10);
			if(!isNaN(number))
			{
				total += number;
			}
		});
		$(this).html(Highcharts.numberFormat(total,0));
	});
}

function populate_creatives_overview_card(thumbnail, impressions, interaction_rate, card){
	var chart = $(card).find('.chart');
	$(function(){
		chart.fadeOut(200, function(){
			chart.find('.impressions .value').text(Highcharts.numberFormat(impressions, 0));
			chart.find('.interaction_rate .value').text(Highcharts.numberFormat(interaction_rate * 100, 2) + '%');
			chart.find('.thumbnail').attr('src', thumbnail);
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
	$("#products_nav_section a:not(.disabled)").off("click", disable_click);
	$("#products_nav_section a:not(.disabled)").on("click", handle_tab_click);
	$("#products_nav_section a:not(.disabled)").removeClass('dim_during_campaigns_load_disable');
	$("#overview_product a:not(.disabled)").off("click", disable_click);
	$("#overview_product a:not(.disabled)").on("click", handle_tab_click);
	$("#overview_product a:not(.disabled)").removeClass('dim_during_campaigns_load_disable');

	$(document).off("click","#overview_lift > a:not(.disabled)", disable_click);
	$(document).on("click","#overview_lift > a:not(.disabled)", handle_lift_tab_click);
	$("#overview_lift a:not(.disabled)").removeClass('dim_during_campaigns_load_disable');
}

function disable_tab_clicking()
{
	$("#products_nav_section > a:not(.disabled)").off("click", handle_tab_click);
	$("#products_nav_section > a:not(.disabled)").on("click", disable_click);
	$("#products_nav_section > a:not(.disabled)").addClass('dim_during_campaigns_load_disable');
	$("#overview_product > a:not(.disabled)").off("click", handle_tab_click);
	$("#overview_product > a:not(.disabled)").on("click", disable_click);
	$("#overview_product > a:not(.disabled)").addClass('dim_during_campaigns_load_disable');

	$(document).off("click","#overview_lift > a:not(.disabled)", handle_lift_tab_click);
	$(document).on("click","#overview_lift > a:not(.disabled)", disable_click);
	$("#overview_lift a:not(.disabled)").addClass('dim_during_campaigns_load_disable');
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
	$('#report_v2_date_range_input').removeAttr('disabled','disabled');
}

function disable_date_range_picker()
{
	$('#report_v2_date_range_input').addClass('dim_during_campaigns_load_disable');
	$('#report_v2_date_range_input').attr('disabled','disabled');
}

function enable_campaigns_select()
{
	$("#report_v2_campaign_options").multiselect('enable');
}

function disable_campaigns_select()
{
	$("#report_v2_campaign_options").multiselect('disable');
}

var seach_adv_new="";
var search_adv="";
function adv_server_search(me)
{
	seach_adv_new = me.value;
	searchWait = 0;
	if(!searchWaitInterval) searchWaitInterval = setInterval(function(){
		if(searchWait >= 3){
			clearInterval(searchWaitInterval);
			searchWaitInterval = '';
			adv_server_search_fn();
			searchWait = 0;
		}
		searchWait++;
	}, 300);
}
var searchWait = 0;
var searchWaitInterval;


//var adv_results=new Array();
function adv_server_search_fn()
{
	search_adv=document.getElementById('adv_search_text').value;
	//document.getElementById('adv_search_text').disabled=true;
	//document.getElementById('adv_search_text').value="Searching for '" + search_adv+"'";
	document.getElementById("adv_search_span").innerHTML="Searching Advertisers...";
	var advertiser_id = $("#report_v2_advertiser_options").val();
	var selected_adv_rows=new Array();
	if (advertiser_id != null)
	{
		for (var j=0; j < advertiser_id.length; j++ )
		{
			var key = advertiser_id[j];
			if (key == "" || key == "0" || key == "-1")
				continue;
			var value=adv_results['a-'+key];
			selected_adv_rows[j]=new Array();
			selected_adv_rows[j][0]=key;
			selected_adv_rows[j][1]=value;
		}
	}

	g_campaigns_jqxhr = jQuery.ajax({
		async: true,
		type: "POST",
		data: {
			q: search_adv,
			page_limit: 100,
			page: 1,
			source: 'all'
		},
		url: "/report_v2/get_advertisers_ajax",
		success: function(data, textStatus, jqXHR) {
			if (seach_adv_new !== search_adv && seach_adv_new !== '')
			{
				document.getElementById('adv_search_text').value = seach_adv_new;
				document.getElementById('adv_search_text').focus();
				return;
			}

			var message = "Please select up to 5 Advertisers";
			g_campaigns_jqxhr = null;
			$("#report_v2_advertiser_options").html('');
			var i=0;
			adv_results=new Array();

			for (i=0; i < selected_adv_rows.length; i++)
			{
				$('#report_v2_advertiser_options').append($("<option></option>").attr("value", selected_adv_rows[i][0]).text(selected_adv_rows[i][1]).attr("selected", "true"));
				adv_results['a-'+selected_adv_rows[i][0]]=selected_adv_rows[i][1];
			}
			for (i=0; i < data.result.length; i++)
			{
				if ($.inArray(data.result[i]['id'], advertiser_id) >= 0)
					continue;

				$('#report_v2_advertiser_options').append($("<option></option>").attr("value", data.result[i]['id']).text(data.result[i]['text']));
				adv_results['a-'+data.result[i]['id']]=data.result[i]['text'];
				$('#report_v2_advertiser_dropdown div.btn-group').addClass('open');
				if (data.result.length > 49 && i == 48)
				{
					message='Please narrow your search. Showing first 50 matches';
					break;
				}
			}
			if (i == 0)
			{
				$('#report_v2_advertiser_dropdown div.btn-group').addClass('open');
				//$('#report_v2_advertiser_options').append($("<option></option>"));
				message='No Advertisers found';
				disable_campaigns_select();
			}

			if (i > 0)
			{
				fix_advertiser_select_layout(false)
				$("#report_v2_advertiser_options").multiselect('rebuild');
				//$("#report_v2_advertiser_options").multiselect('selectAll', false);
				$("#report_v2_advertiser_options").multiselect('updateButtonText');
				//update_page('change_advertiser');
				fix_advertiser_select_layout(true)
			}
			document.getElementById("adv_search_span").innerHTML=message;
			if (document.getElementById('adv_search_text') != undefined)
			{
				var ad_search_text = $("#adv_search_text");
				ad_search_text.blur();
				ad_search_text.focus().focus();
				document.getElementById('adv_search_text').value = search_adv;

				if ( typeof ad_search_text[0].setSelectionRange != "undefined" )
				{
					ad_search_text.val(ad_search_text.val());
					var strLength= ad_search_text.val().length;
					ad_search_text.focus();
					ad_search_text[0].setSelectionRange(strLength, strLength);
				}
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			g_campaigns_jqxhr = null;
			if(errorThrown !== "abort")
			{
				vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
				stop_summary_loading_gif();
			}
		},
		dataType: "json"
	});
}

// Download the csv according to the currently selected table.
function download_csv(tab, subproduct_nav_pill)
{
	var advertiser = $("#report_v2_advertiser_options").val();
	var campaign_array = $("#report_v2_campaign_options").val();
	var start_date = g_current_start_date;
	var end_date = g_current_end_date;

	if(advertiser && campaign_array && start_date && end_date)
	{
		$("#download_csv_advertiser").html("");
		$("#download_csv_campaign").html("");

		var advertiser_array = advertiser_group_conversion_for_id(advertiser);
		for(var advertiser in advertiser_array)
		{
			$('<input type="hidden" name="advertiser_id[]" value="' + advertiser_array[advertiser] + '">').appendTo($("#download_csv_advertiser"));
		}

		for(var campaign in campaign_array)
		{
			$('<input type="hidden" name="campaign_values[]" value="' + campaign_array[campaign] + '">').appendTo($("#download_csv_campaign"));
		}

		$("#download_csv_start_date").val(start_date);
		$("#download_csv_end_date").val(end_date);
		$("#download_csv_tab").val(tab);
		$("#download_csv_nav_pill").val(subproduct_nav_pill);
		$("#download_csv_timezone_offset").val(new Date().getTimezoneOffset()/60);
		$("#download_csv_form").submit();
	}
}

function fix_advertiser_select_layout(should_fix)
{
	var dropdown_container = $("#report_v2_advertiser_dropdown .btn-group");
	var filter_element = dropdown_container.find('.advertiser_search_container');
	var options_list_element = dropdown_container.find('ul');

	if(should_fix)
	{
		filter_element.prependTo(dropdown_container);
	}
	else
	{
		filter_element.prependTo(options_list_element);
	}
}

var last_valid_selection = null;
// Setup and initialize the environment
$(function()
{
	$(document).on("click","#overview_lift_container_view_more", function(event){
		$("#overview_lift > a").trigger("click");
	});

	if (typeof Waves !== 'undefined')
	{
		Waves.attach('.topparentnav');
		Waves.init();
	}

	$(window).resize(function(){
		if (typeof g_nav_pill_selected_obj !== 'undefined')
		{
			nav_pill_click(g_nav_pill_selected_obj);
		}

		var highchart = $('#lift_analysis_conversion_baseline_container').highcharts();
		if(highchart)
		{
			add_custom_renderer(highchart);
			highchart.reflow();
			highchart.render();
			// Hacky way of removing empty legends that cover the true legend - MC
			$(".highcharts-legend g:empty").parents(".highcharts-legend").remove();
		}
	});

	$(".sidebar-nav .topparentnav").click(function(event,triggeredBy){
		event.preventDefault();
		if ($(this).hasClass('productnav'))
		{
			//Don't toggle the class if the click event is triggered by some other JS function.
			//This may unnecessary closes the products section.
			if (typeof triggeredBy === 'undefined' || triggeredBy !== 'js_triggered')
			{
				$("#products_nav_section").toggleClass("products_nav_section_on");
			}
			g_products_clicked = true;
		}
		else if ($(this).hasClass('lift'))
		{
			g_products_clicked = false;
			g_loading_pills_first_time= false;
			$("#products_nav_section .product_child").removeClass("product_child_clicked");
			$(".subproduct_loading_enclosure").hide();
		}
		else
		{
			g_products_clicked = false;
			g_loading_pills_first_time= true;
			$(".subproduct_loading_enclosure").hide();
			$("#products_nav_section .product_child").removeClass("product_child_clicked");

			if (!$("#products_nav_section").hasClass("products_nav_section_on"))
			{
				$("#products_nav_section").addClass("products_nav_section_on");
			}
		}

		if (!$(this).hasClass('topparentnavclicked'))
		{
			$(".sidebar-nav .topparentnav").removeClass("topparentnavclicked");
			$(this).addClass("topparentnavclicked");
		}
	});

	$("#left_nav_toggle").click(function(e){
		e.preventDefault();
		$('.navigation_container').toggleClass('openleftnav');
	});

	$( window ).resize(function() {
		$('.navigation_container').removeClass('openleftnav');
	});

	$(".overview_page_view_more_link a").click(function(event){
		event.preventDefault();
		$($(this).attr("href")).trigger("click");
		g_products_clicked = true;
		$(".sidebar-nav .productnav").trigger("click",['js_triggered']);
	});

	$('.controller_section').show(100);
	if ($('#report_v2_advertiser_options option').size() <= 5 )
	{
		$("#report_v2_advertiser_options option").prop("selected", true);
	}
	$("#report_v2_campaign_options").prop("selectedIndex", 0);

	var g_campaign_select_interval_id = null;

	var select_text = $("#report_v2_advertiser_options").children().length ? 'Select Advertiser(s)' : 'No Advertisers Available';
	$("#report_v2_advertiser_options").multiselect({
		includeSelectAllOption: false,
		nonSelectedText: select_text,
		enableCaseInsensitiveFiltering: true,
		numberDisplayed: 1,
		filterPlaceholder: '',
		max_selected_options: 5,
		disableIfEmpty: true,
		onChange: function(option, checked)
		{
			var advertiser_id = $("#report_v2_advertiser_options").val();
			if(g_campaign_select_interval_id !== null)
			{
				window.clearInterval(g_campaign_select_interval_id);
				g_campaign_select_interval_id = null;
			}
			g_campaign_select_interval_id = window.setInterval(check_if_dropdown_closed, 300);

			function check_if_dropdown_closed()
			{
				if (!$('#report_v2_advertiser_dropdown div.btn-group').hasClass('open'))
				{
					window.clearInterval(g_campaign_select_interval_id);
					g_campaign_select_interval_id = null;
					select_advertiser();
				}
			}

			var me = option, // is an <option/>
			parent = me.parent(),// is a <select/>
			max = parent.data('max'), // defined on <select/>
			options, // will contain all <option/> within <select/>
			selected, // will contain all <option(::selected)/> within <select/>
			multiselect; // will contain the generated ".multiselect-container"

			if (!max)
			{ // don't have a max setting so ignore the rest
				return;
			}

			// get all options
			options = me.parent().find('option');

			// get selected options
			selected = options.filter(function () {
				return $(this).is(':selected');
			});

			// get the generated multiselect container
			multiselect = parent.siblings('.btn-group').find('.multiselect-container');

			// check if max amount of selected options has been met
			if (selected.length >= max) {
				// max is met so disable all other checkboxes.

				options.filter(function () {
					return !$(this).is(':selected');
				}).each(function () {
					multiselect.find('input[value="' + $(this).val() + '"]')
						.prop('disabled', true)
						.parents('li').addClass('disabled');
				});

			} else {
				// max is not yet met so enable all disabled checkboxes.

				options.each(function () {
					multiselect.find('input[value="' + $(this).val() + '"]')
						.prop('disabled', false)
						.parents('li').removeClass('disabled');
				});
			}
		},
		templates: {
			button: '<span type="button" class="multiselect dropdown-toggle btn btn-default" data-toggle="dropdown"></span>',
			filter: '<div class="advertiser_search_container">' +
						'<div class="select2-search"><input type="text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" id="adv_search_text" onkeyup="adv_server_search(this)" class="select2-input form-control multiselect-search"></div>'+
						'<div class="range_inputs" style="padding:15px;font-size:12px"><div style="float:left;font-weight:bold;width:100%;margin-bottom:20px;"><div style="float:left"><span id="adv_search_span">Please select upto 5 Advertisers</span></div><div style="float:right "><button onclick="close_advertiser_popup()" class="applyBtn btn btn-small btn-sm btn-success">Apply</button></div></div></div>' +
					'</div>'
		},buttonText: function(options, select) {
			if (options.length === 0)
			{
				return this.nonSelectedText + ' <b class="caret"></b>';
			}
			else if (options.length > this.numberDisplayed)
			{
				return options.length + ' ' + this.nSelectedText + ' <b class="caret"></b>';
			}
			else
			{
				var selected = '';
				options.each(function() {
					var label = ($(this).attr('label') !== undefined) ? $(this).attr('label') : $(this).html();
					selected += label + ', ';
				});
				return selected.substr(0, selected.length - 2) + ' <b class="caret"></b>';
			}
		}
	});
	fix_advertiser_select_layout(true);

	$('div#report_v2_advertiser_dropdown.target_dropdown div.btn-group span.multiselect.dropdown-toggle.btn.btn-default').click(function (e) {
		setTimeout(function() {
			if (document.getElementById('adv_search_text') !== undefined)
			{
				document.getElementById('adv_search_text').focus();
			}
		}, 10);
		e.preventDefault();
	});

	$(".navigation_container").scroll(function(){
		$("div.btn-group .multiselect-container").each(function(){
			var parent_height = $(this).parent().height();
			var parent_top = $(this).parent().offset().top;
			var toppos = ((parent_top + parent_height) - 2)+"px";
			$(this).css("top",toppos);
		});

		var date_field_height = $('#report_v2_date_range_input').height();
		var date_field_top = $('#report_v2_date_range_input').offset().top;
		var toppos = ((date_field_height + date_field_top + 10))+"px";
		$(".daterangepicker").css("top",toppos);
	});

	function campaign_dropdown_multiselect()
	{
		$("#report_v2_campaign_options").multiselect('destroy');

		var campaign_select_text = $("#report_v2_campaign_options").children().length ? 'Select a campaign' : 'No campaigns available';
		$("#report_v2_campaign_options").multiselect({
			includeSelectAllOption: true,
			nonSelectedText: campaign_select_text,
			enableCaseInsensitiveFiltering: true,
			numberDisplayed: 1,
			filterPlaceholder: '',
			disableIfEmpty: true,
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
	}
	window.campaign_dropdown_multiselect = campaign_dropdown_multiselect;
	campaign_dropdown_multiselect();

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
	$(document.body).on('click', "a.overview_product_table_download_link", function(){
		download_csv(g_current_product_tab, $(this).data('product'));
		return false;
	});

	$("#table_download_link").attr('title', 'Download .csv file of geography data');

	$("#table_download_link").hide();

	update_page('change_advertiser');
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

			if (tab.product_type === 'Overview' || tab.product_brand === 'Overview')
			{
				continue;
			}

			var is_tab_visible = tabs_visibility !== null && tabs_visibility[tab.html_element_id] === true;

			tabs_nav_html += '<li><a onclick="javascript:product_child_clicked(this,event);" data-event-name="' + tab.event_friendly_name + '" href="#' + tab.html_element_id + '" id="'+tab.html_element_id + '"';

			if(tab.html_element_id === current_tab)
			{
				if(is_tab_visible)
				{
					tabs_nav_html += ' class="active product_child product_child_clicked"';
				}
				else
				{
					tabs_nav_html += ' class="active disabled product_child"';
				}
			}
			else
			{
				if(is_tab_visible)
				{
					tabs_nav_html += ' class="product_child"';
				}
				else
				{
					tabs_nav_html += ' class="disabled product_child"';
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
				image_html = '<img src="/assets/img/' + tab.image_path + '" width="10px;" height="10px;" />';
			}
			var brand_html = '';
			if(tab.product_brand)
			{
				brand_html = '<span class="product_brand">' + tab.product_brand + '</span>';
			}

			// strcture in starting html too
			tabs_nav_html += brand_html + "&nbsp;&nbsp;" + tab.product_type + "</a></li>";
		}


		if(num_tabs > 1)
		{
			tabs_html += tabs_nav_html;
		}

		$("#products_nav_section").html(tabs_html);
		if (typeof Waves !== 'undefined')
		{
			Waves.attach('.product_child');
			Waves.init();
		}

		if(g_are_campaigns_loading)
		{
			disable_tab_clicking();
		}
		else
		{
			enable_tab_clicking();
		}
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

	if($(activated_tab).data("event-name"))
	{
		mixpanel.track($(activated_tab).data("event-name") + " sidebar product clicked");
	}

	var $active_tabs = $("#products_nav_section > a.active");
	$active_tabs.removeClass("active");
	$(activated_tab).addClass("active");

	g_current_product_tab = activated_id;

	if (g_current_product_tab == "overview_product"){
		$("#overview_lift > a").addClass("disabled");
	}


	g_current_subproduct_nav_pill_id = null;
	update_page('change_product');

	return false;
}

function handle_lift_tab_click()
{
	g_current_product_tab = "lift";
	start_overview_loading_gif();
	show_lift_detail();
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

	if(subproducts_config !== null && subproduct_nav_pills !== null)
	{
		if(g_current_subproduct_nav_pill_id === '')
		{
			g_current_subproduct_nav_pill_id = subproduct_nav_pills[0].html_element_id;
		}

		var num_nav_pills = subproduct_nav_pills.length;
		var subproduct_nav_pills_html = '<ul id="subproduct_nav_pills" class="nav nav-pills ' +
			subproducts_config.data_view_class + '">' +
			'<div id="nav_pill_underline"></div>';

		for(var ii = 0; ii < num_nav_pills; ii++)
		{
			var subproduct_nav_pill = subproduct_nav_pills[ii];

			subproduct_nav_pills_html += '<li id="'+subproduct_nav_pill.html_element_id;

			if(g_current_subproduct_nav_pill_id == subproduct_nav_pill.html_element_id)
			{
				subproduct_nav_pills_html += '">';
			}
			else
			{
				subproduct_nav_pills_html += '">';
			}
			if (typeof g_loading_pills_first_time === 'undefined')
			{
				g_loading_pills_first_time = true;
			}
			subproduct_nav_pills_html += '<a class="nav_pill" href="#'+subproduct_nav_pill.html_element_id+'" data-event-name="' + subproduct_nav_pill.event_friendly_name + '" onclick="javascript:nav_pill_click(this)">'+subproduct_nav_pill.title+'</a></li>';
		}
		subproduct_nav_pills_html += '</ul>';

		if(num_nav_pills > 0)
		{
			$("#subproduct_nav_pills_holder").html(subproduct_nav_pills_html);
			var li_selector_to_show = (g_current_subproduct_nav_pill_id !== "") ? "#" + g_current_subproduct_nav_pill_id : "#subproduct_nav_pills > li:first";
			nav_pill_click($(li_selector_to_show + " > a"));
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

	if($(activated_nav_pill).data("event-name"))
	{
		mixpanel.track($(activated_nav_pill).data("event-name") + ": " + $(activated_nav_pill).text() + " clicked");
	}

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

	var x_axis_tick_width = 100;
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

function setup_chart_options(
	chart_config,
	chart_container,
	x_axis_tick_width
)
{
	var largest_legend_index = 0;
	var largest_z_index = 0;
	var stack_index = 0;
	var default_color_index = 0;

	var default_colors = [
		'#00629b',
		'#0092e7',
		'#919194',
		'#4d4f53',
		'#09A0B2'
	];

	var chart_series_configs = [];
	$.each(chart_config.items, function(index, chart_item) {
		var chart_series;

		if(chart_item.series_config)
		{
			var series_config = chart_item.series_config;
			switch(series_config.type)
			{
				case 'area':
					chart_series = {
						name: series_config.name || '',
						visible: series_config.visible || true,
						color: series_config.color || default_colors[default_color_index++],
						lineWidth: series_config.line_width || 1,
						type: 'area',
						zIndex: series_config.z_index || (largest_z_index++),
						yAxis: series_config.y_axis || 0,
						data: chart_item.data,
						marker: {
							enabled: series_config.is_marker_enabled || false
						},
						legendIndex: series_config.legend_index || (largest_legend_index++)
					};
					break;
				case 'column':
					chart_series = {
						name: series_config.name || '',
						visible: series_config.visible || true,
						color: series_config.color || default_colors[default_color_index++],
						borderColor: series_config.border_color || 'white',
						borderWidth: series_config.border_width || 1,
						lineWidth: series_config.line_width || 1,
						type: 'column',
						stack: series_config.stack || stack_index++,
						/*
						index: series_config.index || undefined,
						*/
						zIndex: series_config.z_index || (largest_z_index++),
						yAxis: series_config.y_axis || 0,
						data: chart_item.data,
						marker: {
							enabled: series_config.is_marker_enabled || false
						},
						legendIndex: series_config.legend_index || (largest_legend_index++)

					};
					break;
				default:
					// TODO: report error unhandled series type
					break;
			}
		}
		else
		{
			// TODO: default? // chart_series = [];
		}

		chart_series_configs.push(chart_series);
	});

	var tick_interval = calculate_chart_tick_interval(
		chart_container,
		x_axis_tick_width,
		chart_config.x_axis_data.length
	);

	var y_axis_config;
	if(chart_config.y_axis)
	{
		y_axis_config = chart_config.y_axis;
	}
	else
	{
		y_axis_config = [{
				showEmpty: false,
				title: {
					text: chart_config.y_axis_title || ''
				},
				labels:{
					style: {
						fontWeight: 'bold'
					}
				},
				min: 0
			},
			{
				showEmpty: false,
				title: {
					text: chart_config.y_axis_2_title || '',
					style: {
						"font-size": "10px"
					}
				},
				opposite: true
			}
		];
	}

	var highcharts_options = {
		chart: {
		/*
			renderTo: chart_config.container_id || undefined,
			*/
			type:'area',
			height: chart_config.height || 140,
			zoomType:'x',
			marginRight: 35,
			events : {
				redraw: function()
				{
					// Fixes problem where chart displayed incorrectly
					fix_product_details_chart_width();
				}
			}
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
		yAxis: y_axis_config,
		xAxis: {
			showLastLabel: true,
			endOnTick: true,
			labels:{
				step:5,
				align:'center',
				rotation:360,
				y:25,
				style: {
					'color': '#999999',
					'font-size': '10px'
				}
			},
			categories: chart_config.x_axis_data,
			tickInterval: tick_interval
		},
		plotOptions: {
			column: {
				grouping: false,
				stacking: 'normal'
			}
		},
		legend: {
			enabled: chart_config.is_legend_enabled || false,
			align: 'center',
			x: chart_config.legend_x || 0,
			verticalAlign: 'top',
			y: chart_config.legend_y || 0,
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
		series: chart_series_configs
	};

	return highcharts_options;
}

function calculate_chart_tick_interval(
	chart_container_or_css_selector,
	x_axis_tick_width,
	num_graph_x_axis_items
)
{
	var num_ticks_to_show = (Math.floor($(chart_container_or_css_selector).width() / x_axis_tick_width));
	var tick_interval = Math.ceil(num_graph_x_axis_items / num_ticks_to_show);
	return tick_interval;
}

function attach_chart_function_for_resizing_ticks(
	chart_resize_resource_tracker,
	chart,
	chart_container_css_selector,
	x_axis_tick_width,
	num_graph_x_axis_items
)
{
	var chart_resize_function = function() {
		var tick_interval = calculate_chart_tick_interval(
			chart_container_css_selector,
			x_axis_tick_width,
			num_graph_x_axis_items
		);
		chart.xAxis[0].options.tickInterval = tick_interval;
	};
	var chart_resource = {
		'chart': chart,
		'resize_function': chart_resize_function
	};

	chart_resize_resource_tracker.push(chart_resource);

	$(window).resize(chart_resize_function);
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
	attach_chart_function_for_resizing_ticks(
		g_chart_resources,
		chart,
		chart_container_css_selector,
		x_axis_tick_width,
		num_graph_items
	);
}
var data_table_columns_objects_global=new Array();
var data_table_rows_objects_global=new Array();
function build_table(table_data, table_css_selector)
{
	if(table_data != null)
	{
		var header_cells = table_data.header.cells;
		var num_columns = header_cells.length;
		var rows = table_data.rows;
		var num_rows = rows.length;
		var all_other_sites_row = [];
		var all_other_sites_small_rows = [];

		if(num_rows > 0)
		{
			var data_table_columns_objects = new Array();
			var header_array=new Array();
			for(var column_ii = 0; column_ii < num_columns; column_ii++)
			{
				var header_cell = header_cells[column_ii];
				var column_object = {
					title : header_cell.html_content,
					data : 'column_' + column_ii,
					'class' : header_cell.css_classes,
					orderSequence : header_cell.initial_sort_order === 'asc' ? ['asc', 'desc'] : ['desc', 'asc'],
					orderable : header_cell.orderable === false ? false : true,
					searchable : header_cell.searchable === false ? false : true,
					visible : header_cell.visible === false ? false : true
				};

				if(header_cell.csv_exportable !== undefined)
				{
					column_object["export"] = header_cell.csv_exportable;
				}

				data_table_columns_objects.push(column_object);
				header_array.push(header_cell.html_content);
			}
			if (table_data.caption)
			{
				data_table_columns_objects_global[table_data.caption]=data_table_columns_objects;
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
				if(row_object.column_0 === 'All other sites')
				{
					all_other_sites_row.push(row_object);
					continue;
				}
				if(row_object.column_0 === 'All other sites - small impressions')
				{
					continue;
				}
				if(row_object.column_4 === "0")
				{
					all_other_sites_small_rows.push(row_object);
					continue;
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

			if (table_data.caption)
			{
				data_table_rows_objects_global[table_data.caption]=data_table_rows_objects;
			}

			var download_click_handler = function(event)
			{
				var data_table_rows_for_download = data_table_rows_objects_global[table_data.caption]
					.concat(all_other_sites_small_rows)
					.concat(all_other_sites_row);

				event.preventDefault();
				create_excel_indexed(
					data_table_columns_objects_global[table_data.caption],
					data_table_rows_for_download,
					table_data.caption
				);
			};
			if(table_data.caption)
			{
				var csv_link = '&nbsp;&nbsp;<span style="float:right;position:relative;top:-10px">' +
					'<a href="#" title="Download" class="btn btn-link pv_new_rfp_button pv_tooltip pv_edit_rfp_button download_table_button">' +
				'<span><i class="icon-remove icon-download"></i></span></a></span>';
				caption = '<caption>' + table_data.caption + csv_link + '</caption>';
			}

			$(table_css_selector).html(
				'<table id="report_sub_products_id" class="tab_table" cellspacing="0" class="order-column row-border" style="display:none;">'+
				caption +
				'</table>'
			);
			$(table_css_selector + ' .download_table_button').on('click', download_click_handler);
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
				},
				"drawCallback" : function(){
					var all_lis = $(table_css_selector+" .pagination ul li");
					if (all_lis.length < 4)
					{
						$(table_css_selector+" .pagination").closest(".row-fluid").hide();
					}
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

function build_tv_creatives_table(selector, data)
{
	$(selector).html('<div id="tv_creatives_table"></div>' +
		'<div data-content="Creatives may be excluded from this section if they only have a few impressions for the selected time period or if the creatives are custom 3rd party creatives." data-trigger="hover" data-placement="top" class="report_tooltip_display_creatives" style="float:right;color:#888888;cursor:default;">' +
			'Why can\'t I see my creative? '+
		'</div>');

	var table_css_selector = '#tv_creatives_table';

	$(table_css_selector).html(
		'<table class="tab_table" cellspacing="0" class="order-column row-border" width="100%" style="display:none;"></table>'
	);
	$(table_css_selector + " .tab_table").show();

	var m_data_source = data.creative_ids;
	var creative_type = data.creative_type;
	var advertiser_id = $("#report_v2_advertiser_options").val();
	advertiser_id = advertiser_group_conversion_for_id(advertiser_id);

	var creative_table_page_length = 10;
	var table_options = {
		"serverSide": true,
		"ordering": false,
		"searching": false,
		"order": new Array(new Array(0,'asc')),
		"lengthChange": false,
		"pageLength" : creative_table_page_length,
		"paging": "full_numbers",
		"columns": new Array({ title: "", data: 'creative_data', 'class': 'column_class', orderSequence: ['asc', 'desc']}),
		"ajax": function(data_tables_ajax_data, callback, settings) {
			var creative_ids = m_data_source.slice(data_tables_ajax_data.start, data_tables_ajax_data.start + data_tables_ajax_data.length);
			if(creative_ids.length > 0)
			{
				abort_secondary_subproduct_jqxhr();
				start_subproduct_loading_gif();
				g_secondary_subproduct_content_jqxhr = jQuery.ajax({
					async: true,
					type: "POST",
					data: {
						advertiser_id: advertiser_id,
						campaign_ids: $("#report_v2_campaign_options").val(),
						start_date: g_current_start_date,
						end_date: g_current_end_date,
						creative_ids: creative_ids,
						creative_type: creative_type
					},
					url: "/report_v2/get_creative_messages",
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

							handle_ajax_controlled_error(response_data, "Failed in /report_v2/get_creative_messages");
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

			// reflow because the creatives data displays in a second load
			// and the scrollbar causes numbers on the chart to be cut off
			fix_product_details_chart_width();
		},
		"rowCallback": function(row, data, index) {
			var summary_id = 'creative_summary_'+index;
			var chart_container_id = 'creative_container_chart_'+index;
			var content_chart_id = 'creative_chart_'+index;
			var width_handler = (typeof is_ie8 == 'undefined') ? 'max-width' : 'width';

			var $row = $(row);
			var creative_styles = "height:167px;";
			$row.html(
				'<td><div class="main_row" style="position:relative;width:100%;">'+
					'<table class="creative_table_row" width="100%"><tbody>' +
						'<tr>' +
							'<td style="width:200px;border-top-left-radius:2px;"><div class="creative_thumbnail"><div class="tv_impressions_thumbnail_overlay" /><div class="creative_thumbnail_container"><img src="'+(data.creative_data.tv_creative_spot_thumbnail || data.creative_data.tv_creative_bookend_top_thumbnail || data.creative_data.tv_creative_bookend_bottom_thumbnail)+'" style="' + width_handler + ':200px;"></div></td>' +
							'<td style="vertical-align:top;border-top-right-radius:2px;" valign="top">' +
								'<div id="'+chart_container_id+'" style="width:99%;position:relative;"><div id="'+content_chart_id+'" class="chart_content_div" style="width:99%;position:absolute;"></div></div>' +
							'</td>' +
						'</tr>' +
					'</tbody></table>' +
				'</div></td>'
			);

			var modal_data = {
				creative_thumbnail: data.creative_data.tv_creative_spot_thumbnail || data.creative_data.tv_creative_bookend_top_thumbnail,
				creative_thumbnail_2: data.creative_data.tv_creative_bookend_bottom_thumbnail,
				creative_video: data.creative_data.tv_creative_spot_video_url || data.creative_data.tv_creative_bookend_top_video_url || '#',
				creative_video_webm: data.creative_data.tv_creative_spot_video_url_webm || data.creative_data.tv_creative_bookend_top_video_url_webm || '#',
				creative_video_bookend_2: data.creative_data.tv_creative_bookend_bottom_video_url || '#',
				creative_video_bookend_2_webm: data.creative_data.tv_creative_bookend_bottom_video_url_webm || '#'
			};

			$('.creative_thumbnail_container, .tv_impressions_thumbnail_overlay', $row).click(function(event) {
				var modal_header = data.creative_data.tv_creative_spot_name || (data.creative_data.tv_creative_bookend_top_name + " & " + data.creative_data.tv_creative_bookend_bottom_name);
				var modal_body = build_video_preview_modal_body(modal_data);
				$("#tv_creative_video_modal_header_content").html(modal_header);
				$("#tv_creative_video_modal_body_content").html(modal_body);
				$("#tv_creative_video_modal").modal('show');
				build_ie8_video();
				$('a[data-toggle="tab"]').on('shown', function(e) {
					if(typeof is_ie8 == 'undefined')
					{
						$(e.relatedTarget.hash + " video").get(0).pause();
						$(e.target.hash + " video").get(0).play();
					}
					else
					{
						videojs($(e.relatedTarget.hash + " .video-js").attr('id')).pause();
						videojs($(e.target.hash + " .video-js").attr('id')).play();
					}
				});

				event.stopPropagation();
			});

			$("#tv_creative_video_modal").on('hidden', function(){
				destroy_ie8_video();
				$("#tv_creative_video_modal_body_content").html('');
			});

			$('#'+content_chart_id, $row).one('create_chart', data.creative_data.chart, function(event) {
				var chart_data = event.data;
				var has_impressions = !!chart_data.impressions;

				var num_graph_x_axis_items = chart_data.dates.length;

				var x_axis_tick_width = 100;
				var num_x_axis_ticks_to_show = (Math.floor($('#'+chart_container_id).width()/ x_axis_tick_width));
				var tick_interval = Math.ceil(num_graph_x_axis_items / num_x_axis_ticks_to_show);

				var is_engagements_visible = true;
				var highcharts_series = [];

				// These indexes are only incremented when they are used so the next one will always follow the first - MC
				var legend_index = 0;
				var axis_index = 0;

				if(has_impressions)
				{
					highcharts_series.push({
						name: 'Impressions',
						visible: true,
						lineWidth: 1,
						type: 'area',
						zIndex: 0,
						yAxis: axis_index++,
						data: chart_data.impressions,
						color: '#919194',
						marker: {
							enabled: false
						},
						legendIndex: legend_index++
					});
				}

				highcharts_series.push({
					name: 'Airings',
					visible: true,
					color: '#0092e7',
					borderColor: 'white',
					borderWidth: 1,
					type: 'column',
					stack: 3,
					index: 4,
					zIndex: 3,
					yAxis: axis_index++,
					data: chart_data.airings,
					legendIndex: legend_index++
				});

				var y_axis_array = [];
				if(has_impressions)
				{
					y_axis_array.push({
						title: {
							text: 'Impressions'
						},
						labels:{
							style: {
								fontWeight: 'bold'
							}
						},
						min: 0
					});
				}
				y_axis_array.push({
					title: {
						text: 'Airings',
						style: {
							fontWeight: 'bold'
						}
					},
					opposite: true
				});

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
					yAxis: y_axis_array,
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
						enabled: true,
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

function build_tv_network_table(selector, data)
{
	// TODO:TV custom javascript function for handling your own data
	// Can use build_chart() to make a chart, see how it's used other places
	// Can use build_table() to make a table, see how it's used other places
	$(selector).html('<div id="tv_network_table"></div>');

	var table_css_selector = "#tv_network_table";

	$(table_css_selector).html(
		'<table class="tab_table primary_table" cellspacing="0" class="order-column row-border" width="100%" style="display:none;"></table>'
	);

	$(table_css_selector + " .tab_table").show();
	if(data.length > 0)
	{
		var table_data = data;
		var tv_network_page_length = 10;

		var tv_network_table = $(table_css_selector + " .tab_table").dataTable({
			"data": format_tv_network_data(table_data),
				"autoWidth" : false,
				"searching" : true,
				"bLengthChange": false,
				"bFilter" : true,
				"ordering" : false,
				"createdRow": function(row, data) {
					var $row = $(row);
					var zone_holder_selector = '.tv_network_airings';
					$(zone_holder_selector, $row).collapse({ toggle: false }).on('shown', function(){
						var this_accordion = this;
						setTimeout(function(){
							$(this_accordion).css({height:'auto'});
						}, 0);
					});
					$('table > tbody > tr:first-child', $row).click(function(event) {
						var $open_accordion = $(zone_holder_selector+'.in');
						$('tr:first-child', $open_accordion.parents('table')[0]).removeClass('tv_selected_row');
						var $associated_accordion = $(zone_holder_selector, $row);

						if(!$associated_accordion.hasClass('in'))
						{
							$('tr:first-child', $row).addClass('tv_selected_row');
							//Establish table/accordions for secondary table

							// get airings data with another AJAX call
							get_airings_data_for_zone_network($associated_accordion, zone_holder_selector, $open_accordion, data.advertiser_ids, data.account_ids, data.zone, data.network);
						}

						if($open_accordion !== $associated_accordion && $associated_accordion.hasClass('in'))
						{
							$open_accordion.collapse('hide');
						}
					});

				$('tr > .td_left > div > .tv_creative_thumbnail_container', $row).click(function(event) {
						var modal_header = data.creative_name;
						var modal_body = build_video_preview_modal_body(data);
						$("#tv_creative_video_modal_header_content").html(modal_header);
						$("#tv_creative_video_modal_body_content").html(modal_body);
						$("#tv_creative_video_modal").modal('show');
						$('a[data-toggle="tab"]').on('shown', function(e) {
							$(e.relatedTarget.hash + " video").get(0).pause();
							$(e.target.hash + " video").get(0).play();
						});

							event.stopPropagation();
					});

				$('tr > td.network_schedule_link a', $row).click(function(event) {
					var modal_header = '<img src="'+data.network_icon+'" style="height:40px;"> '+data.network_friendly_name+" Demographic Profile <small>"+data.network+"</small>";
					if($.isEmptyObject(data.demographic_data) == false)
					{
						display_demographic_data_modal(data.demographic_data);
						$("#tv_airing_schedule_modal_body_content_no_data").hide();
						$("#tv_airing_schedule_modal_body_content").show();

					}
					else
					{
						$("#tv_airing_schedule_modal_body_content_no_data").html("Demographic data for "+data.network_friendly_name+" unavailable");
						$("#tv_airing_schedule_modal_body_content_no_data").show();
						$("#tv_airing_schedule_modal_body_content").hide();
					}

					$("#tv_airing_schedule_modal_header_content").html(modal_header);
					$("#tv_airing_schedule_modal").modal('show');
					$.sparkline_display_visible();
					event.stopPropagation();
				});

				$('table > tbody > tr:first-child', $row).hover(function(event) {
					$(this).addClass('tv_hover_row');
					$(".network_schedule_clickable", this).show();
				},
				function(event) {
					$(this).removeClass('tv_hover_row');
					$(".network_schedule_clickable", this).hide();
				});

			},
			"initComplete": function(settings){
			},
			"drawCallback": function(settings){
				var all_lis = $(table_css_selector+" .pagination ul li");
				if (all_lis.length < 4)
				{
					$(table_css_selector+" .pagination").closest(".row-fluid").hide();
				}
			},
			"columns": [
				{
					data: "network_row",
					render: {
						_: "network_cell_html",
						sort: "search_text"
					},
					searchable: true
				}
			]
		});
		if(table_data.length <= tv_network_page_length)
		{
			$(".dataTables_paginate.paging_simple_numbers").hide()
		}
	}
	else
	{
		$(table_css_selector).html('<div style="width:100%; font-size: 16px; text-align:center; color:grey; margin-top: 70px;">No airings data available for selected accounts.</div>');
	}
}


function format_tv_network_data(data)
{
	$.each(data, function(key, value)
	{
		this.network_row = {
			network_cell_html: format_tv_network_row(this),
			search_text: this.network+" "+this.network_friendly_name+" "+this.zone_friendly_name
		};
	});
	return data;
}


function format_tv_network_row(row_data)
{
	var html =
		'<td style="padding:0;"><div class="main_row" style="position:relative;width:100%;height:auto;">'+
			'<table class="tv_network_table" style="height:100%; width:100%;table-layout:fixed;">' +
				'<tr>' +
					'<td class="td_left" style="padding-left:10px;height:65px;width:105px;"><div style="width:105px;"><img src="'+row_data.network_icon+'" style="height:55px;padding-top:5px;padding-bottom:5px;"/></div></td>' +
					'<td class="network_schedule_link" style="width:120px;text-align:center;"><a class="network_schedule_clickable network_schedule_icon" style="font-size:24px;display:none;"><i class="network_schedule_clickable icon-share" style="display:none"></i></a></td>' +
					'<td style="padding:0;height:100%;width:35%;text-align:left;"><div>' +
					'<span><strong>'+row_data.network_friendly_name+'</strong></span></div></td>' +
					'<td style="padding:0;text-align:left;">'+row_data.zone_friendly_name+'</td>' +
					'<td style="padding:0;text-align:right;">'+row_data.airings_count+' airings</td>' +
					'<td class="td_right" style="padding:0;width:50px;"></td>' +
				'</tr>' +
				'<tr><td colspan=6 style="padding:0;height:0px;">' +
					'<div class="tv_network_airings collapse" data-parent="#tv_network_table" style="height:0px;"></div>' +
				'</td></tr>' +
			'</table>' +
		'</td>';
	return html;
}


function build_ie8_video()
{
	if(typeof is_ie8 != 'undefined')
	{
		$('.tv_creative_video_player').each(function(i, obj) {
			video_players.push(videojs(obj.id));
		});
	}
}

function destroy_ie8_video()
{
	if(typeof is_ie8 != 'undefined')
	{
		$.each(video_players, function(idx, value)
		{
			value.dispose();
		});
		video_players = [];
	}
}

function build_network_details_table(network_table_element, parent_element_selector, airings_array)
{
		//insert table
	network_table_element.html(
		'<table class="tab_table secondary_table" cellspacing="0" class="order-column row-border" width="100%" style="display:none;margin:0;"></table>'
	);
	$(".tab_table", network_table_element).show();

	var initial_page_length = 10;
	var network_details_table = $(".tab_table", network_table_element).dataTable({
				"data": format_network_airings(airings_array),
				"autoWidth" : false,
				"searching" : true,
				"ordering" : false,
				"paging": true,
				"pageLength": initial_page_length,
				"bInfo": false,
				"createdRow": function(row, data) {
					var $row = $(row);
					var airing_holder_selector = '.tv_zone_airings';

					$('.video_clickable', $row).click(function(event) {
							var modal_header = data.creative.creative_name;
							var modal_body = build_video_preview_modal_body(data.creative);
							$("#tv_creative_video_modal_header_content").html(modal_header);
							$("#tv_creative_video_modal_body_content").html(modal_body);
							build_ie8_video();
							$("#tv_creative_video_modal").modal('show');
							$('a[data-toggle="tab"]').on('shown', function(e) {
								if(typeof is_ie8 == 'undefined')
								{
									$(e.relatedTarget.hash + " video").get(0).pause();
									$(e.target.hash + " video").get(0).play();
								}
								else
								{
									videojs($(e.relatedTarget.hash + " .video-js").attr('id')).pause();
									videojs($(e.target.hash + " .video-js").attr('id')).play();
								}
							});

							event.stopPropagation();
					});

				},
				"initComplete": function(settings){
				},
				"drawCallback": function(settings){
					$("thead", network_table_element).hide();
					$("#tv_creative_video_modal").on('hidden', function(){
						destroy_ie8_video();
						$("#tv_creative_video_modal_body_content").html('');
					});
				},
				"columns": [
					{
						data: "airing_row",
						render: {
							_: "airing_cell_html"
						}
					}
				]
			});

	$(".tv_network_airings .dataTables_paginate.paging_simple_numbers").hide()
	$(".tv_network_airings  > div > div:nth-child(3)").hide()

	$(".tab_table", network_table_element).show();


	/*
	var network_details_html = '<table style="width:100%">';
	$.each(airings_array, function(key, value)
	{
		var network_details_row =
			'<tr>' +
				'<td class="td_left" style="padding:0;height:95px;float:left;width:100%;text-align:left;"><div><div class="tv_creative_thumbnail_container" style="width:150px;margin-left:5px;height:95px;display:inline-block;position:relative;"><div class="tv_thumbnail_overlay"></div><img class="tv_creative_thumbnail"" src="'+this.creative.creative_thumbnail+'"></div><span style="padding-left:30px;">'+this.creative.creative_id+'</span></div></td>' +
				'<td>'+this.airing_time+'</td>' +
				'<td>'+this.show+'</td>' +
			'</tr>';

		network_details_html += network_details_row;
	});

	network_details_html += "</table>";
	$(network_table_element).html(network_details_html);
*/


}

function format_network_airings(data)
{
	$.each(data, function(key, value)
	{
		this.airing_row = {
			airing_cell_html: format_tv_network_airing(this)
		};
	});
	return data;
}

function format_tv_network_airing(row_data)
{

		var html =
				'<td style="padding:0;"><div class="main_row" style="position:relative;width:100%;height:auto;">'+
						'<table class="tv_network_details_table" style="height:100%; width:100%;">' +
								'<tr>' +
								'<td class="td_left" style="padding:0;padding-left:55px;height:49px;width:61px;text-align:left;"><div><div class="video_clickable tv_creative_thumbnail_container" style="width:61px;margin-left:10px;height:39px;display:inline-block;cursor:pointer;"><div class="tv_thumbnail_overlay"></div><img class="tv_creative_thumbnail"" src="'+row_data.creative.creative_thumbnail+'"></div></div></td>' +
								'<td><div class="video_clickable" style="cursor:pointer;display:inline-block;float:left;padding-left:15px;text-align:left;word-break:break-word;width:auto;">'+row_data.creative.creative_name+'</div></td>' +
								'<td style="width:37%;word-break:break-word;text-align:right;padding-right:110px;"><b><i>'+row_data.show+'</i></b><br>'+row_data.airing_time+'</td>' +
								'</tr>' +
						'</table>' +
				'</td>';
		return html;
}

function build_video_preview_modal_body(row_data)
{
	var has_bookends = row_data.creative_video_bookend_2 != null && row_data.creative_video_bookend_2 != '#';
	var modal_body_html = "";
	if(has_bookends)
	{
		modal_body_html += '<div class="tabbable">' +
								'<ul class="nav nav-tabs">' +
									'<li class="active"><a href="#video_tab_1" data-toggle="tab">Bookend 1</a></li>' +
									'<li><a href="#video_tab_2" data-toggle="tab">Bookend 2</a></li>'+
								'</ul>' +
								'<div class="tab-content">';
	}
	modal_body_html += '<div style="text-align:center;" class="tab-pane active flowplayer" id="video_tab_1">' +
							'<video class="tv_creative_video_player '+((typeof is_ie8 != 'undefined') ? 'video-js' : '')+'" id="modal_video_player_1" controls autoplay width="530" height="287" poster="'+row_data.creative_thumbnail+'" data-setup="{}">' +
								'<source src="'+row_data.creative_video+'" type="video/mp4"/> ' +
								'<source src="'+row_data.creative_video_webm+'" type="video/webm"/> ' +
							'</video>' +
						'</div>';
	if(has_bookends)
	{
		modal_body_html +=	'<div style="text-align:center;" class="tab-pane flowplayer" id="video_tab_2">' +
								'<video class="tv_creative_video_player '+((typeof is_ie8 != 'undefined') ? 'video-js' : '')+'" id="modal_video_player_2" controls width="530" height="287" poster="'+row_data.creative_thumbnail_2+'" data-setup="{}">' +
								'<source src="'+row_data.creative_video_bookend_2+'" type="video/mp4"/> ' +
								'<source src="'+row_data.creative_video_bookend_2_webm+'" type="video/webm"/> ' +
							'</video>' +
							'</div>' +
						'</div>' +
					'</div>';
	}

	return modal_body_html;
}

function display_demographic_data_modal(demographic_data)
{

	//TODO Replace with data out of database
	var averages =
	{
		"male_population":			0.492,
		"female_population":		0.508,
		"age_18_24":				0.100,
		"age_25_34":				0.133,
		"age_35_44":				0.133,
		"age_45_54":				0.144,
		"age_55_64":				0.118,
		"age_65":					0.132,
		"white_population":			0.642,
		"black_population":			0.123,
		"asian_population":			0.048,
		"hispanic_population":		0.177,
		"race_other":				0.010,
		"kids_no":					0.667,
		"kids_yes":					0.333,
		"income_0_50":				0.477,
		"income_50_100":			0.302,
		"income_100_150":			0.127,
		"income_150":				0.093,
		"college_no":				0.642,
		"college_under":			0.254,
		"college_grad":				0.104
	};

	$(".income").html(demographic_data["avg_household_income"].Value);
	$(".median_age").html(demographic_data["median_age"].Value);
	$(".persons_household").html(demographic_data["avg_household_size"].Value);
	$(".home_value").html(demographic_data["avg_hosehold_value"].Value);

	$.each(averages, function(key, value)
	{
		if(key == "male_population")
		{
		}
		sparkline(
			((demographic_data[key].Value * 100) / ((demographic_data[key].Value * 100)/demographic_data[key].Index)),
			(Math.round(demographic_data[key].Value * 1000) / 10),
			"#sparkline_" + key,
			null,
			demographic_data[key].Index
		);

		/*sparkline(
			97.2984,
			49.4,
			"#sparkline_" + key
		);*/
	});


}

function build_display_creatives_table(selector, data)
{
	$(selector).html('<div id="display_creatives_table"></div>' +
		'<div data-content="Creatives may be excluded from this section if they only have a few impressions for the selected time period or if the creatives are custom 3rd party creatives." data-trigger="hover" data-placement="top" class="report_tooltip_display_creatives" style="float:right;color:#888888;cursor:default;">' +
			'Why can\'t I see my creative? '+
		'</div>');

	$('.report_tooltip_display_creatives').popover();
	var table_css_selector = '#display_creatives_table';

	$(table_css_selector).html(
		'<table class="tab_table" cellspacing="0" class="order-column row-border" width="100%" style="display:none;"></table>'
	);
	$(table_css_selector + " .tab_table").show();

	var m_data_source = data.creative_ids;
	var creative_type = data.creative_type;
	var advertiser_id = $("#report_v2_advertiser_options").val();
	advertiser_id = advertiser_group_conversion_for_id(advertiser_id);

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
						advertiser_id: advertiser_id,
						campaign_ids: $("#report_v2_campaign_options").val(),
						start_date: g_current_start_date,
						end_date: g_current_end_date,
						creative_ids: creative_ids,
						creative_type: creative_type
					},
					url: "/report_v2/get_creative_messages",
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

							handle_ajax_controlled_error(response_data, "Failed in /report_v2/get_creative_messages");
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

			// reflow because the creatives data displays in a second load
			// and the scrollbar causes numbers on the chart to be cut off
			fix_product_details_chart_width();
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
							'<td style="width:200px;border-top-left-radius:2px;"><div class="creative_thumbnail"><div class="creative_thumbnail_overlay" /><div class="creative_thumbnail_container"><img src="'+data.creative_data.creative_thumbnail+'" style="max-width:initial;'+creative_styles+'"></div></td>' +
							'<td style="vertical-align:top;border-top-right-radius:2px;" valign="top">' +
								summary_html +
								'<div id="'+chart_container_id+'" style="width:99%;position:relative;"><div id="'+content_chart_id+'" class="chart_content_div" style="width:99%;position:absolute;"></div></div>' +
							'</td>' +
						'</tr>' +
						'<tr><td colspan=2 style="border-bottom-left-radius:2px;border-bottom-right-radius:2px;">' +
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
	if(maps_on_page.hasOwnProperty(selector) && maps_on_page[selector] != null)
	{
		maps_on_page[selector].initialize_new_map();
		maps_on_page[selector].build_map_from_data(data.heatmap, maps_on_page[selector].load_geofencing_data(data.geofence_multipoints));
	}
	else
	{
		maps_on_page[selector] = new ReportMap(selector, 'heatmap');
		maps_on_page[selector].build_map_from_data(data.heatmap, maps_on_page[selector].load_geofencing_data(data.geofence_multipoints));
	}
}

function build_overview_map_from_data(selector, data)
{
	setTimeout(function() {
		if(maps_on_page.hasOwnProperty(selector) && maps_on_page[selector] != null)
		{
			maps_on_page[selector].initialize_new_map();
			maps_on_page[selector].build_map_from_data(data);
		}
		else
		{
			maps_on_page[selector] = new ReportMap(selector, 'overview');
			maps_on_page[selector].build_map_from_data(data);
		}
	}, 10);
}

function build_tv_zones_map(selector, data)
{
	if(data.initial_geojson !== '')
	{
		setTimeout(function() {
			var map_selector = '#' + data.map_id;
			if(maps_on_page.hasOwnProperty(map_selector) && maps_on_page[map_selector] != null)
			{
				maps_on_page[map_selector].initialize_new_map();
				maps_on_page[map_selector].build_map_from_data(data.initial_geojson, function(){
					maps_on_page[map_selector].set_up_zone_form(data.zone_data);
				});
			}
			else
			{
				maps_on_page[map_selector] = new ReportMap(map_selector, 'zone');
				maps_on_page[map_selector].build_map_from_data(data.initial_geojson, function(){
					maps_on_page[map_selector].set_up_zone_form(data.zone_data);
				});
			}
		}, 10);
	}
	else
	{
		$(selector).html('<div style="width:100%; font-size: 16px; text-align:center; color:grey; margin-top: 70px;">No zone data available for selected accounts.</div>');
	}
}

function build_lift_map_from_data(selector, geojson_blob)
{
	if (geojson_blob)
	{
		if (maps_on_page.hasOwnProperty(selector) && maps_on_page[selector] != null)
		{
			maps_on_page[selector].initialize_new_map();
			maps_on_page[selector].build_map_from_data(geojson_blob);
		}
		else
		{
			maps_on_page[selector] = new ReportMap(selector, 'lift');
			maps_on_page[selector].build_map_from_data(geojson_blob);
		}
		// Malaika TODO: form styling in it's own object, handles zoom to form object for forms on either side of map
		// maps_on_page[selector].set_up_lift_form(data.lift_form_data);
	}
}

function redraw_map_if_exists(selector)
{
	if(maps_on_page[selector])
	{
		maps_on_page[selector].redraw_map();
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
//	$('#subproduct_nav_pills > li.active').removeClass('active');
//	$('#' + nav_pill_id).addClass('active');
}

function change_active_tab(tab_item_id)
{
	$('#new_tabs_radio > div.tabbable > ul > li.active').removeClass('active');
	$('#' + tab_item_id + '_nav').addClass('active');
}

function sparkline(length, percent, id, final_length, demo_index) {
	var maxInputLength = 200;
	var finalLength = final_length || 106;
	var indexValue = length*finalLength / maxInputLength;
	var fullBar = finalLength;
	if(indexValue > fullBar)
	{
		indexValue = fullBar;
	}

	var halfBar = fullBar / 2;
	var backgroundColor = '#cacaca';
	var barColor = '#414142';
	if(indexValue > halfBar)
	{
		barColor = '#5bcae9';
	}
	var targetValue = halfBar;

	$(id).sparkline(
		[targetValue,indexValue,fullBar,indexValue,indexValue],
		{
			type: 'bullet',
			width: finalLength,
			targetWidth: 1,
			height: '12',
			targetColor: '#f6f6f6',
			performanceColor: '#',
			disableHiddenCheck: true,
			rangeColors: [backgroundColor, barColor, barColor],
			tooltipFormatter: function(sparkline, options, fields){
				if(demo_index)
				{
					return percent + "% - Index: "+demo_index;
				}
				else
				{
					return percent + "%";
				}
			}
		}
	);
}

function product_child_clicked(element, event)
{
	if (!$(element).hasClass('disabled'))
	{
		$(".navigation_container .product_child").removeClass("product_child_clicked");
		$(element).addClass("product_child_clicked");
		$(".navigation_container .topparentnav").removeClass("topparentnavclicked");
		$(".navigation_container .productnav").addClass("topparentnavclicked");
	}
	else
	{
		if (typeof window.event !== 'undefined')
		{
			window.event.preventDefault ? window.event.preventDefault() : window.event.returnValue = false;
		}
		else if (typeof event !== 'undefined')
		{
			event.preventDefault();
		}
	}
}

function nav_pill_click(element)
{
	if (element === null)
	{
		element = $("#nav_pill_underline").next("li").children("a");

		if (typeof g_loading_pills_first_time === 'undefined' || !g_loading_pills_first_time)
		{
			return;
		}
		else
		{
			g_loading_pills_first_time = false;
		}
	}

	if ($(element).position())
	{
		var nav_pill_width = $(element).width();
		var nav_pill_height = $(element).height();
		var nav_pill_left = $(element).position().left;
		var nav_pill_top = $(element).position().top;
		$("#nav_pill_underline").css({"left":nav_pill_left+"px","width":(Number(nav_pill_width)+24)+"px","top":(Number(nav_pill_top)+Number(nav_pill_height)+30)+"px"});
		$("#nav_pill_underline").show();
		g_nav_pill_selected_obj = element;
	}
}

function get_airings_data_for_zone_network(associated_accordion, zone_holder_selector, open_accordion, advertiser_ids, account_ids, zone, network)
{
	abort_secondary_subproduct_jqxhr();
	start_overview_loading_gif();

	g_airings_content_jqxhr = jQuery.ajax({
		url: "/report_v2/get_airings_data_for_zone_network",
		async: true,
		type: "POST",
		dataType: "json",
		data: {
			advertiser_ids: advertiser_ids,
			account_ids: account_ids,
			zone: zone,
			network: network
		},

		success: function(data, textStatus, jqXHR) {
			g_airings_content_jqxhr = null;

			if(vl_is_ajax_call_success(data))
			{
				build_network_details_table(associated_accordion, zone_holder_selector, data[0].airings);

				if(!associated_accordion.hasClass('in'))
				{
					associated_accordion.on('hidden', function() {
						associated_accordion.off('hidden');
						associated_accordion.html('');
					});
				}

				associated_accordion.collapse('toggle');
				if(open_accordion !== associated_accordion)
				{
					open_accordion.collapse('hide');
				}
			}
			else
			{
				stop_overview_loading_gif();
				handle_ajax_controlled_error(data, "Failed in /report_v2/get_airings_data_for_zone_network");
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			g_airings_content_jqxhr = null;

			if(errorThrown !== "abort")
			{
				vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
				stop_overview_loading_gif();
			}
		},
		complete: function() {
			stop_overview_loading_gif();
		}
	});
}


function show_lift_overview(campaign_values)
{
	if (typeof campaign_values == 'undefined' || campaign_values == null || campaign_values == "")
	{
		return false;
	}

	var start_date = get_one_year_back_date(convert_date_to_sql_format(g_current_end_date));
	var end_date = convert_date_to_sql_format(g_current_end_date);
	var campaign_ids = get_campaign_ids_for_lift(campaign_values);

	if (campaign_ids == "")
	{
		return false;
	}

	$.ajax({
		url: "/report_v2/get_lift_data_for_report_overview",
		type: "POST",
		dataType: "json",
		data:
		{
			campaign_values:campaign_ids,
			start_date: start_date,
			end_date: end_date
		},
		success: function(result)
		{
			if (result.is_success && typeof result.lift_data_for_report_overview != 'undefined')
			{

				if (result.lift_data_for_report_overview.average_lift != null && result.lift_data_for_report_overview.average_lift != "")
				{
					var avg_lift = result.lift_data_for_report_overview.average_lift;
					var average_conversion_rate = result.lift_data_for_report_overview.average_conversion_rate;
					var average_baseline = result.lift_data_for_report_overview.average_baseline;

					if(avg_lift > 99)
					{
						$("#overview_lift_container .avg_lift_text").html("over 99");
						$("#overview_lift_container .avg_lift").html("> 99");
						$("#overview_lift_container .positive_lift").show();
						$("#overview_lift_container .overview_lift_left_card_inner_avg_lift_text .fa").hide();
					}else
					{
						$("#overview_lift_container .avg_lift_text").html(avg_lift);
						$("#overview_lift_container .avg_lift").html(avg_lift);

						if (avg_lift >= 1)
						{
							$("#overview_lift_container .negative_lift").hide();
							$("#overview_lift_container .positive_lift").show();
							$("#overview_lift_container .overview_lift_left_card_inner_avg_lift_text .fa").show();
						}
						else
						{
							$("#overview_lift_container .positive_lift").hide();
							$("#overview_lift_container .negative_lift").show();
							$("#overview_lift_container .overview_lift_left_card_inner_avg_lift_text .fa").hide();
						}
					}


					if (avg_lift < 1 || average_conversion_rate < 0.01)
					{
						$("#overview_lift_container .exposed_rate_container .fa").hide();
						$("#overview_lift_container .exposed_rate_container .avg_conversion_rate").addClass("left-margin-20");
					}
					else
					{
						$("#overview_lift_container .exposed_rate_container .avg_conversion_rate").removeClass("left-margin-20");
						$("#overview_lift_container .exposed_rate_container .fa").show();
					}


					if (average_conversion_rate < 0.01)
					{
						average_conversion_rate = "< 0.01";
					}
					$("#overview_lift_container .avg_conversion_rate").html(average_conversion_rate);

					if (average_baseline < 0.01)
					{
						average_baseline = "< 0.01";
					}
					$("#overview_lift_container .avg_baseline").html(average_baseline);


					$("#overview_lift_container").show();
					var t= setInterval(
						function(){
							if($("#campaigns_overview_title").is(":visible")
								|| $("#product_details_card_row").is(":visible")){
								$("#overview_lift > a").removeClass("disabled");
								clearInterval(t);
							}
						},500);
				}
				else
				{
					$("#overview_lift_container").hide();
					$("#overview_lift > a").addClass("disabled");
				}

			}
		},
		error: function(jqXHR, textStatus, errorThrown)
		{
			if (textStatus !== 'abort')
			{
				vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
			}
		}
	});
}

show_lift_map();


function show_lift_map()
{
	$("#lift_map").empty();
	$(".lift_map_loading_image").show();
	var campaign_values = $("#report_v2_campaign_options").val();
	var start_date = get_one_year_back_date(convert_date_to_sql_format(g_current_end_date));
	var end_date = convert_date_to_sql_format(g_current_end_date);

	if (typeof campaign_values == 'undefined' || campaign_values == null || campaign_values == ""
		|| typeof start_date == 'undefined' || start_date == null || start_date == ""
		|| typeof end_date == 'undefined' || end_date == null || end_date == "")
	{
		return false;
	}

	var campaign_ids = get_campaign_ids_for_lift(campaign_values);

	if (campaign_ids == "")
	{
		return false;
	}

	// place your code here to run when the element becomes visible
	jQuery.ajax({
		url: "/report_v2/get_lift_data_per_zip_for_campaigns",
		type: "POST",
		dataType: "json",
		data:{
			campaign_values:campaign_ids,
			start_date:start_date,
			end_date:end_date
		},
		success: function(data, textStatus, jqXHR) {
			if (data.is_success)
			{
				$(".lift_map_loading_image").hide();
				build_lift_map_from_data("#lift_map", data.geojson_blob);
				$(window).resize();
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {

			if(errorThrown !== "abort")
			{
				vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
				stop_subproduct_loading_gif();
			}
		}
	});
}

function show_lift_detail()
{
	if(g_overview_lift_triggered)
	{
		return;
	}
	else
	{
		g_overview_lift_triggered = true;
	}

	var advertiser_id = $("#report_v2_advertiser_options").val();
	advertiser_id = advertiser_group_conversion_for_id(advertiser_id);
	var campaign_values = $("#report_v2_campaign_options").val();
	var start_date = get_one_year_back_date(convert_date_to_sql_format(g_current_end_date));
	var end_date = convert_date_to_sql_format(g_current_end_date);

	$("#lift_no_data_overview_overlay").fadeOut(100);
	$("#lift_analysis_card_row .card_row").fadeOut(100);
	$("#lift_analysis_card_row").fadeOut(100);
	$("#lift_analysis_overview").fadeOut(100);

	if (typeof campaign_values == 'undefined' || campaign_values == null || campaign_values == ""
		|| typeof start_date == 'undefined' || start_date == null || start_date == ""
		|| typeof end_date == 'undefined' || end_date == null || end_date == "")
	{
		displayLiftMessage("Please select advertiser and dates for viewing the lift data.");
		stop_overview_loading_gif();
		return false;
	}

	var campaign_ids = get_campaign_ids_for_lift(campaign_values);
	if (campaign_ids == "")
	{
		displayLiftMessage("Cannot calculate lift for selected campaigns.");
		stop_overview_loading_gif();
		return false;
	}

	show_lift_map();
	build_campaigns_lift_table();

	jQuery.ajax({
		url: "/report_v2/get_lift_data_for_report_detail",
		async: true,
		type: "POST",
		dataType: "json",
		data:
		{
			advertiser_id:advertiser_id,
			campaign_values:campaign_ids,
			campaign_values_set:campaign_values,
			start_date:start_date,
			end_date:end_date
		},
		success: function(data)
		{
			g_overview_lift_triggered = false;
			if (data.is_success)
			{
				var lift_data = data.lift_data_for_report_overview.average_lift;
				if (typeof lift_data != 'undefined' && lift_data != '' )
				{
					$("#overview_lift a:not(.disabled)").off("click", disable_click);
					$("#overview_lift a:not(.disabled)").on("click", handle_lift_tab_click);
					$("#overview_lift a:not(.disabled)").removeClass('dim_during_campaigns_load_disable');

					var avg_lift = data.lift_data_for_report_overview.average_lift;
					var average_conversion_rate = data.lift_data_for_report_overview.average_conversion_rate;
					var average_baseline = data.lift_data_for_report_overview.average_baseline;

					if(avg_lift > 99)
					{
						$("#lift_analysis_overview .overview_lift_left_card_inner_avg_lift_text .fa").hide();
						$("#lift_analysis_overview .avg_lift").html("> 99");
						$("#lift_analysis_overview .positive_lift").show();
					}
					else
					{
						$("#lift_analysis_overview .avg_lift").html(avg_lift);

						if (avg_lift >= 1)
						{
							$("#lift_analysis_overview .overview_lift_left_card_inner_avg_lift_text .fa").show();
							$("#lift_analysis_overview .negative_lift").hide();
							$("#lift_analysis_overview .positive_lift").show();
						}
						else
						{
							$("#lift_analysis_overview .overview_lift_left_card_inner_avg_lift_text .fa").hide();
							$("#lift_analysis_overview .positive_lift").hide();
							$("#lift_analysis_overview .negative_lift").show();
						}
					}

					if (avg_lift < 1 || average_conversion_rate < 0.01)
					{
						$("#lift_analysis_overview .exposed_rate_info_container .fa").hide();
					}
					else
					{
						$("#lift_analysis_overview .exposed_rate_info_container .fa").show();
					}

					if (average_baseline < 0.01)
					{
						average_baseline = "< 0.01";
					}
					$("#lift_analysis_overview .avg_baseline_rate").html(average_baseline);

					if (average_conversion_rate < 0.01)
					{
						average_conversion_rate = "< 0.01";
					}
					$("#lift_analysis_overview .avg_conversion_rate").html(average_conversion_rate);

					var daily_lifts = [];
					var baseline_rates = [];
					var conversion_rates = [];
					var dates = [];

					for(var i=0;i<data.lift_data_for_report_overview_per_day.length;i++)
					{
						var conversion = 0;
						if (data.lift_data_for_report_overview_per_day[i]['average_conversion_rate'] != null && data.lift_data_for_report_overview_per_day[i]['average_conversion_rate'] != '')
						{
							conversion = parseFloat(data.lift_data_for_report_overview_per_day[i]['average_conversion_rate']);
						}

						var baseline = 0;
						if (data.lift_data_for_report_overview_per_day[i]['average_baseline'] != null && data.lift_data_for_report_overview_per_day[i]['average_baseline'] != '')
						{
							baseline = parseFloat(data.lift_data_for_report_overview_per_day[i]['average_baseline']);
						}

						var daily_lift = 0;
						if (data.lift_data_for_report_overview_per_day[i]['average_lift'] != null && data.lift_data_for_report_overview_per_day[i]['average_lift'] != '')
						{
							daily_lift = parseFloat(data.lift_data_for_report_overview_per_day[i]['average_lift']);
						}

						dates.push(data.lift_data_for_report_overview_per_day[i]['impression_date']);
						conversion_rates.push(conversion);
						baseline_rates.push(baseline);
						daily_lifts.push(daily_lift);
					}

					stop_overview_loading_gif();
					show_date_and_disclaimer_in_header(start_date, end_date);

					$("#lift_analysis_card_row .card_row").fadeIn(100);
					$("#lift_analysis_card_row").fadeIn(100);
					$("#lift_analysis_overview").fadeIn(100);

					draw_daily_lift_highchart("lift_analysis_daily_lift_container_graph", dates, conversion_rates, baseline_rates, daily_lifts, false, 0);
					draw_conversion_and_baseline_highchart(avg_lift, data.lift_data_for_report_overview.average_conversion_rate, data.lift_data_for_report_overview.average_baseline);
					redraw_map_if_exists('#lift_map');
				}
				else
				{
					$("#overview_lift > a:not(.disabled)").off("click", handle_lift_tab_click);
					$("#overview_lift > a:not(.disabled)").on("click", disable_click);
					$("#overview_lift > a:not(.disabled)").addClass('dim_during_campaigns_load_disable');
					displayLiftMessage("Cannot calculate Lift. Data below thresold.");
					stop_overview_loading_gif();
				}
			}
			else
			{
				displayLiftMessage("Cannot calculate Lift for the given date range.");
				stop_overview_loading_gif();
			}
		},
		error: function(jqXHR, textStatus, errorThrown)
		{
			if(errorThrown !== "abort")
			{
				vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
				stop_subproduct_loading_gif();
			}
		}
	});
}

function displayLiftMessage(message)
{
	$("#lift_no_data_overview_overlay h3").html(message);
	$("#lift_no_data_overview_overlay").fadeIn(100);
}

function draw_conversion_and_baseline_highchart(average_lift, average_conversion, average_baseline)
{
	var original_baseline_value = null;
	if (parseFloat(average_baseline) < 0.02 && parseFloat(average_conversion) > 0.5)
	{
		original_baseline_value = parseFloat(average_baseline);
		average_baseline = "0.02";

		var w_conversion = ((parseFloat(average_conversion))/20);

		if (w_conversion > 0.02)
		{
			average_baseline = w_conversion + "";
		}
	}
	else if (parseFloat(average_baseline) == 0 && parseFloat(average_conversion) <= 0.5)
	{
		original_baseline_value = parseFloat(average_baseline);
		average_baseline = ((parseFloat(average_conversion))/20);
	}

	var original_conversion_value = null;
	if (parseFloat(average_conversion) < 0.02 && parseFloat(average_baseline) > 0.5)
	{
		original_conversion_value = parseFloat(average_conversion);
		average_conversion = "0.02";

		var w_baseline = ((parseFloat(average_baseline))/20);

		if (w_baseline > 0.02)
		{
			average_conversion = w_baseline + "";
		}
	}
	else if (parseFloat(average_conversion) == 0 && parseFloat(average_baseline) <= 0.5)
	{
		original_conversion_value = parseFloat(average_conversion);
		average_baseline = ((parseFloat(average_baseline))/20);
	}

	var chart = new Highcharts.Chart({
		chart:
		{
			type: 'column',
			renderTo: 'lift_analysis_conversion_baseline_container'
		},
		title:
		{
			text: ''
		},
		tooltip:
		{
			formatter: function ()
			{
				return '<b>' + this.y + '</b>';
			}
		},
		xAxis:
		{
			categories: ['', 'BASELINE', 'EXPOSED AUDIENCE', ''],
			crosshair: false,
			labels:
			{
				formatter: function ()
				{
					return "<b>" + this.value + "</b>";
				}
			},
			minorTickLength: 0,
			tickLength: 0
		},
		yAxis:
		{
			min: 0,
			title:{text: ''},
			labels: {enabled: false}
		},
		legend:
		{
			enabled: false
		},
		credits:
		{
			enabled: false
		},
		plotOptions:
		{
			column:
			{
				borderWidth: 0,
				dataLabels:
				{
					enabled: false
				},
				enableMouseTracking: false
			},
			series:
			{
				colorByPoint: true,
				colors: ['',"#636268", "#D3D2D5",''],
				pointPadding: 0,
				groupPadding: 0.05,
				zIndex: 0
			}
		},
		series:
		[
			{
				name: '',
				average_lift:average_lift,
				original_baseline_value:original_baseline_value,
				original_conversion_value:original_conversion_value,
				data: [null, parseFloat(average_baseline), parseFloat(average_conversion), null]
			}
		]
	},
	function (chart) {
		add_custom_renderer(chart);
	});

	$(window).resize();
}

var lift_custom_renderers = {};

function add_custom_renderer(chart)
{
	for (var prop in lift_custom_renderers) {
		$(lift_custom_renderers[prop].element).remove();
	}

	var point1 = chart.series[0].points[1];
	var point2 = chart.series[0].points[2];

	if (point1.y < point2.y)
	{
		lift_custom_renderers['v_line'] = chart.renderer
								.rect(((point1.shapeArgs.x + point1.shapeArgs.width)) , (point2.plotY +  chart.plotTop), 2, ((point1.plotY-point2.plotY)-2), 0)
								.attr({fill: '#717171',zIndex: 3});
		lift_custom_renderers['v_line'].add();

		lift_custom_renderers['bottom_h_line'] = chart.renderer
								.rect(((point1.shapeArgs.x + point1.shapeArgs.width) - 10) , ((point1.plotY + chart.plotTop)-2),20, 2, 0)
								.attr({fill: '#717171',zIndex: 3});
		lift_custom_renderers['bottom_h_line'].add();

		lift_custom_renderers['top_h_line'] = chart.renderer
								.rect(((point1.shapeArgs.x + point1.shapeArgs.width) - 10) , ((point2.plotY + chart.plotTop)),20, 2, 0)
								.attr({fill: '#717171',zIndex: 3});
		lift_custom_renderers['top_h_line'].add();

		var avg_lift = chart.series[0].options.average_lift;
		if (avg_lift > 99)
		{
			avg_lift = ">99";
		}

		lift_custom_renderers['lift_text'] = chart.renderer
								.text(avg_lift+"x LIFT", ((point1.shapeArgs.x+point1.shapeArgs.width)-35), ((point2.plotY +  chart.plotTop) - 5))
								.css({color: '#24B19B',fontSize: '16px', fontWeight:'bold'});
		lift_custom_renderers['lift_text'].add();
	}

	var points = chart.series[0].points;
	for (var i = 0; i < points.length; i++)
	{
		if ((i == 1 || i == 2))
		{
			var x_pos = points[i].shapeArgs.x + 20;
			var y_pos = (points[i].shapeArgs.y + points[i].shapeArgs.height) - 10;
			var text_color = "#ffffff";

			if ((points[i].shapeArgs.height <= 35))
			{
				y_pos = points[i].shapeArgs.y + 5;
				text_color = "#575958";
			}

			var y_val = points[i].y;

			if (points[i].category == 'BASELINE' && chart.series[0].options.original_baseline_value != null)
			{
				y_val = chart.series[0].options.original_baseline_value;
			}

			if (points[i].category == 'EXPOSED AUDIENCE' && chart.series[0].options.original_conversion_value != null)
			{
				y_val = chart.series[0].options.original_conversion_value;
			}

			if (y_val < 0.01)
			{
				y_val = '<0.01%';
			}
			else
			{
				y_val = parseFloat(y_val).toFixed(2) + '%';
			}

			lift_custom_renderers[points[i].category] = chart.renderer
							.text(y_val, x_pos, y_pos)
							.css({color: text_color,fontSize: '20px', fontWeight:'bold'})
							.attr({zIndex: 5});
			lift_custom_renderers[points[i].category].add();
		}
	}
}

function draw_daily_lift_highchart(container_selector, dates, conversion_rates, baseline_rates, monthly_lifts, user_short_dates, rotate)
{
	var newArray = $.merge([], conversion_rates);
	$.merge(newArray, baseline_rates);
	var max_value = (Math.max.apply(Math,newArray));
	var max_value_lift = (Math.max.apply(Math,monthly_lifts));
	var monthly_lift_chart_data = get_monthly_lift_chart_data(dates, conversion_rates, baseline_rates, monthly_lifts, user_short_dates);

	var chart = new Highcharts.Chart({
		chart:
		{
			zoomType: 'xy',
			renderTo: container_selector
		},
		title:
		{
			text: ''
		},
		xAxis:
		[
			{
				categories: monthly_lift_chart_data['dates'],
				minorTickLength: 0,
				tickLength: 0,
				useHTML:true,
				labels:
				{
					formatter: function ()
					{
						var dateIndex = monthly_lift_chart_data['dates'].indexOf(this.value);

						if (monthly_lift_chart_data['lift'][dateIndex] != null)
						{
							return '<span style="font-weight:bold;color:#2D2D2D;">' + this.value + '</span>';
						}
						else
						{
							return this.value;
						}
					},
					style:
					{
						color:'#C1C1C1'
					},
					rotation:rotate
				}
			}
		],
		yAxis:
		[
//			{
//				min: 0,
//				max:max_value_lift,
//				gridLineWidth: 0,
//				title:
//				{
//					text: 'Lift'
//				},
//				labels:
//				{
//					format: '{value}x',
//					style:
//					{
//						color: '#2D2D2D'
//					}
//				}
//			},
			{
				min: 0,
				max:max_value,
				labels:
				{
					format: '{value}%',
					style:
					{
						color: '#2D2D2D'
					}
				},
				title:
				{
					text: 'Site Visit Rate'
				}
//				,
//				opposite:true
			},
			{
				min: 0,
				max:max_value,
				title:
				{
					text: ''
				},
				labels:
				{
					enabled: false
				}
			}
		],
		credits:
		{
			enabled: false
		},
		tooltip: {
			shared: true,
			reversed: true,
			formatter: function() {
				var s = '';
				var original_value_obj = monthly_lift_chart_data['original_values'][this.x];

				if (typeof original_value_obj == 'undefined' && this.x.length > 3)
				{
					var index = this.x.substring(0,3).toUpperCase();
					original_value_obj = monthly_lift_chart_data['original_values'][index];
				}

				if (typeof original_value_obj != 'undefined')
				{
					s = this.x;
					var conversion = original_value_obj.conversion_rate;

					if (conversion < 0.01)
					{
						conversion = '&lt;0.01%';
					}
					else
					{
						conversion = conversion+'%';
					}

					var baseline = original_value_obj.baseline_rate;

					if (baseline < 0.01)
					{
						baseline = '&lt;0.01%';
					}
					else
					{
						baseline = baseline+'%';
					}

					var lift = original_value_obj.lift;

					if (lift > 99)
					{
						lift = "&gt; 99x";
					}
					else
					{
						lift = lift+'x';
					}

					$.each(this.points, function(i, point) {
						if (point.series.name == "BASELINE")
						{
							s += '<br/><span style="color:#24B19B">\u25CF</span> LIFT: <b>' + lift;
						}

						s += '<br/><span style="color:'+ point.series.color +'">\u25CF</span> ' + point.series.name + ': <b>';
//						if (point.series.name == "LIFT")
//						{
//							s += lift;
//						}
//						else

						if (point.series.name == "BASELINE")
						{
							s += baseline;
						}
						else if (point.series.name == "EXPOSED AUDIENCE")
						{
							s += conversion;
						}

						s += '</b>';
					});
				}

				return s;

			}
		},
		plotOptions:
		{
			column:
			{
				borderWidth: 0
			},
			series:
			{
				pointPadding: 0,
				groupPadding: 0.1
			}
		},
		series:
		[
//			{
//				name: 'LIFT',
//				type: 'spline',
//				color: '#24B19B',
//				data: monthly_lift_chart_data['lift'],
//				lineWidth:2,
//				marker: {
//					radius: 5
//				},
//				tooltip:
//				{
//					valueSuffix: 'x'
//				},
//				legendIndex:0,
//				zIndex: 10
//			},
			{
				name: 'BASELINE',
				type: 'column',
				data: monthly_lift_chart_data['baseline'],
				color: "#636268",
				yAxis: 1,
				tooltip: {
					valueSuffix: '%'
				},
				legendIndex:1,
				zIndex: 0
			},
			{
				name: 'EXPOSED AUDIENCE',
				type: 'column',
				//yAxis: 1,
				color: "#D3D2D5",
				data: monthly_lift_chart_data['conversion'],
				tooltip: {
					valueSuffix: '%'
				},
				legendIndex:2,
				zIndex: 0
			}
		]
	});
	$(window).resize();
}

function convert_date_to_sql_format(date_to_convert)
{
	var converted_date = date_to_convert;
	if (typeof date_to_convert !== 'undefined' && date_to_convert.indexOf("/") !== -1)
	{
		converted_date = date_to_convert.split("/");
		converted_date = converted_date[2] + "-" + converted_date[0] + "-" + converted_date[1];
	}
	return converted_date;
}

function get_one_year_back_date(date_passed)
{
	if( typeof date_passed != "undefined")
	{
		var parts = date_passed.split('-');
		var date = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
		date.setFullYear(date.getFullYear() - 1);
		return (isNaN(date.getFullYear()) ? '' : formatDate(date));
	}
}

function formatDate(date)
{
    var day = date.getDate();
    var month = date.getMonth() + 1;
    var year = date.getFullYear();
    return year + '-' + (month < 10 ? '0' : '') + month + '-' + (day < 10 ? '0' : '') + day;
}

function get_campaign_ids_for_lift(campaign_values)
{
	var campaign_ids = "";
	for (var i=0;i<campaign_values.length;i++)
	{
		var campaign = campaign_values[i];
		if (campaign.indexOf(";") >= -1)
		{
			campaign = campaign.split(";");
			//If organization is "Frequence" and product type is "Display" or "Preroll"
			if (campaign[0] == '0:' && (campaign[1] == '0:' || campaign[1] == '1:'))
			{
				campaign_ids += campaign[campaign.length - 1] + ",";
			}
		}
		else
		{
			campaign_ids += campaign + ",";
		}
	}

	if (campaign_ids != "")
	{
		campaign_ids = campaign_ids.substring(0,campaign_ids.length-1);
	}

	return campaign_ids;
}

function build_campaigns_lift_table()
{
	start_campaign_lift_loading(true);
	var campaign_values = $("#report_v2_campaign_options").val();
	var start_date = get_one_year_back_date(convert_date_to_sql_format(g_current_end_date));
	var end_date = convert_date_to_sql_format(g_current_end_date);

	if (typeof campaign_values == 'undefined' || campaign_values == null || campaign_values == ""
		|| typeof start_date == 'undefined' || start_date == null || start_date == ""
		|| typeof end_date == 'undefined' || end_date == null || end_date == "")
	{
		return false;
	}

	var campaign_ids = get_campaign_ids_for_lift(campaign_values);
	var campaign_ids_array = campaign_ids.split(",");

	if (campaign_ids == "")
	{
		return false;
	}

	var advertiser_id = $("#report_v2_advertiser_options").val();
	advertiser_id = advertiser_group_conversion_for_id(advertiser_id);

	var table_css_selector = '#campaigns_lift_table';

	$(table_css_selector).html(
		'<table class="tab_table lift_data_table" cellspacing="0" class="order-column row-border" width="100%" style="display:none;border-collapse: separate" ></table>'
	);
	$(table_css_selector + " .tab_table").show();

	//var cids = campaign_ids_array.slice(data_tables_ajax_data.start, data_tables_ajax_data.start+data_tables_ajax_data.length);
	if (campaign_ids_array.length > 0)
	{
		start_campaign_lift_loading(false);
		abort_secondary_subproduct_jqxhr();
		g_secondary_subproduct_content_jqxhr = jQuery.ajax({
			async: true,
			dataType: "json",
			type: "POST",
			data: {
				advertiser_id: advertiser_id,
				campaign_values: campaign_ids,
				campaign_values_set : campaign_values,
				start_date: start_date,
				end_date: end_date
			},
			url: "/report_v2/get_campaigns_lift_data_for_report_detail",
			success: function(response_data, textStatus, jqXHR) {
				g_secondary_subproduct_content_jqxhr = null;
				if(vl_is_ajax_call_success(response_data))
				{
					var campaigns_lift_table_page_length = 2;
					var table_options = {
						"data": response_data.campaigns_lift_data_set,
						"serverSide": false,
						"ordering": false,
						"searching": false,
						"order": new Array(new Array(0,'asc')),
						"lengthChange": false,
						"pageLength" : campaigns_lift_table_page_length,
						"paging": "full_numbers",
						"columns": new Array({ title: "", data: 'campaigns_lift', 'class': 'column_class', orderSequence: ['asc', 'desc']}),
						"drawCallback": function(settings) {
							var all_lis = $(table_css_selector+" .pagination ul li");
							if (all_lis.length < 4)
							{
								$(table_css_selector+" .pagination").closest(".row-fluid").hide();
							}
							$(".campaign_lift_creatives").trigger('display_creatives');
							$(".campaign_lift_chart_content_div").trigger('create_chart');
							$("table.tab_table thead").remove();
						},
						"rowCallback": function(row, data, index) {
							var chart_container_id = "campaign_lift_chart_container_"+index;
							var chart_content_id = "campaign_lift_chart_content_"+index;
							var creatives_container_id = "campaign_lift_creatives_container_"+index;
							var creatives_left_arrow_id = "campaign_lift_creatives_la_container_"+index;
							var creatives_right_arrow_id = "campaign_lift_creatives_rt_container_"+index;

							var $row = $(row);
							var creatives_html = '<div id="'+creatives_container_id+'" class="campaign_lift_creatives"></div>';
							if (typeof (data.campaigns_lift.creatives ) != 'undefined' && data.campaigns_lift.creatives.length > 1)
							{
								creatives_html =
									'<div id="'+creatives_left_arrow_id+'" class="campaign_lift_creatives_left_arrow">'+
										'<i class="fa fa-angle-left prev_creative" aria-hidden="true"></i>'+
									'</div>'+
									'<div style="float:left;width:220px;height:1px;">'+
										'<div id="'+creatives_container_id+'" class="campaign_lift_creatives"></div>'+
									'</div>'+
									'<div id="'+creatives_right_arrow_id+'" class="campaign_lift_creatives_right_arrow">'+
										'<i class="fa fa-angle-right next_creative" aria-hidden="true"></i>'+
									'</div>';
							}

							var avg_lift = data.campaigns_lift.overview.average_lift;
							if (avg_lift > 99)
							{
								avg_lift = "> 99";
							}

							var avg_baseline = data.campaigns_lift.overview.average_baseline;
							if (avg_baseline < 0.01)
							{
								avg_baseline = "< 0.01";
							}

							var average_conversion_rate = data.campaigns_lift.overview.average_conversion_rate;
							if (average_conversion_rate < 0.01)
							{
								average_conversion_rate = "< 0.01";
							}

							$row.html(
								'<td>'+
									'<div class="lift_analysis_middle_full campaign_lift_card">'+
										'<div class="lift_analysis_card lift_analysis_campaign_lift_card">'+
											'<h3><span>Campaign: '+data.campaigns_lift.campaign_name+'</span></h3>'+
											'<div class="lift_analysis_card_inner">'+
												'<div class="lift_analysis_card_inner_left">'+
													'<div class="lift_analysis_average_row">'+
														'<span class="lift_analysis_average_row_number"><span class="avg_lift">'+avg_lift+'</span></span>'+
														'<span class="lift_analysis_average_row_appender" style="margin-left:0px;">X</span>'+
														'<div class="lift_analysis_average_row_text">LIFT</div>'+
													'</div>'+
													'<div class="lift_analysis_average_row">'+
														'<span class="lift_analysis_average_row_number"><span class="avg_conversion_rate">'+average_conversion_rate+'</span></span>'+
														'<span class="lift_analysis_average_row_appender" style="margin-left:0px;">%</span>'+
														'<div class="lift_analysis_average_row_text">EXPOSED RATE</div>'+
													'</div>'+
													'<div class="lift_analysis_average_row">'+
														'<span class="lift_analysis_average_row_number"><span class="avg_baseline_rate">'+avg_baseline+'</span></span>'+
														'<span class="lift_analysis_average_row_appender" style="margin-left:0px;">%</span>'+
														'<div class="lift_analysis_average_row_text">BASELINE RATE</div>'+
													'</div>'+
												'</div>'+
												'<div class="lift_analysis_card_inner_middle">'+creatives_html+'</div>'+
												'<div class="lift_analysis_card_inner_right">'+
													'<div id="'+chart_container_id+'" style="width:99%;position:relative;"><div id="'+chart_content_id+'" class="campaign_lift_chart_content_div" style="width:99%;position:absolute;height:295px;"></div></div>' +
												'</div>'+
											'</div>'+
										'</div>'+
									'</div>'+
								'</td>'
							);

							$('#'+chart_content_id, $row).one('create_chart', data.campaigns_lift.overview_per_month, function(event) {
								var daily_lifts = [];
								var baseline_rates = [];
								var conversion_rates = [];
								var dates = [];
								var overview_per_month = event.data;
								if (typeof overview_per_month !== 'undefined')
								{
									for(var i=0;i<overview_per_month.length;i++)
									{
										var conversion = 0;
										if (overview_per_month[i]['average_conversion_rate'] != null && overview_per_month[i]['average_conversion_rate'] != '')
										{
											conversion = parseFloat(overview_per_month[i]['average_conversion_rate']);
										}

										var baseline = 0;
										if (overview_per_month[i]['average_baseline'] != null && overview_per_month[i]['average_baseline'] != '')
										{
											baseline = parseFloat(overview_per_month[i]['average_baseline']);
										}

										var daily_lift = 0;
										if (overview_per_month[i]['average_lift'] != null && overview_per_month[i]['average_lift'] != '')
										{
											daily_lift = parseFloat(overview_per_month[i]['average_lift']);
										}

										dates.push(overview_per_month[i]['impression_date']);
										conversion_rates.push(conversion);
										baseline_rates.push(baseline);
										daily_lifts.push(daily_lift);
									}
									draw_daily_lift_highchart(chart_content_id, dates, conversion_rates, baseline_rates, daily_lifts, true, -45);
								}
							});


							$('#'+creatives_container_id, $row).one('display_creatives', data.campaigns_lift.creatives, function(event) {
								var creatives = event.data;
								var creative_selector = $('#'+creatives_container_id);
								var thumbnail = "";

								$(creative_selector).html('<img class="campaign_lift_thumbnail switchable disabled" src="" style="border:2px solid #BAB8B9;"/>');

								if (typeof (creatives ) != 'undefined' && creatives.length > 0)
								{
									$(creative_selector).data('creatives', creatives);
									$(creative_selector).data('creativesIndex', 0);

									$("#"+creatives_left_arrow_id).find('.prev_creative').on('click', function(){
										var index = $(creative_selector).data('creativesIndex') == 0 ? $(creative_selector).data('creatives').length - 1 : $(creative_selector).data('creativesIndex') - 1;
										$(creative_selector).data('creativesIndex', index);
										var thumbnail = creatives[index].creative_data.creative_thumbnail;
										populate_creatives_campaign_lift_card(thumbnail, creative_selector);
									});

									$("#"+creatives_right_arrow_id).find('.next_creative').on('click', function(){
										var index = $(creative_selector).data('creativesIndex') == $(creative_selector).data('creatives').length - 1 ? 0 : $(creative_selector).data('creativesIndex') + 1;
										$(creative_selector).data('creativesIndex', index);
										var thumbnail = creatives[index].creative_data.creative_thumbnail;
										populate_creatives_campaign_lift_card(thumbnail, creative_selector);
									});

									thumbnail = creatives[0].creative_data.creative_thumbnail;
								}
								else
								{
									thumbnail = "/images/creative_placeholder.jpg";
								}

								populate_creatives_campaign_lift_card(thumbnail, creative_selector);
							});
						}
					};

					$(table_css_selector + " .tab_table").dataTable(table_options);
					stop_campaign_lift_loading();
				}
				else
				{
					stop_campaign_lift_loading();
					handle_ajax_controlled_error(response_data, "Failed in /report_v2/get_campaign_lift_data_for_report_detail");
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				g_secondary_subproduct_content_jqxhr = null;

				if(errorThrown !== "abort")
				{
					vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
					stop_campaign_lift_loading();
				}
			}
		});
	}
}

function populate_creatives_campaign_lift_card(thumbnail, card){
	$(function(){
		$(card).fadeOut(200, function(){
			$(card).find('.campaign_lift_thumbnail').attr('src', thumbnail);
			$(card).fadeIn(200);
		});
	});
}

function get_monthly_lift_chart_data(dates, conversion_rates, baseline_rates, monthly_lifts, use_short_dates)
{
	var dates_obj = {
		"January" : {
			"conversion_rate" : null,
			"baseline_rate" : null,
			"lift" : null
		},
		"February" : {
			"conversion_rate" : null,
			"baseline_rate" : null,
			"lift" : null
		},
		"March" : {
			"conversion_rate" : null,
			"baseline_rate" : null,
			"lift" : null
		},
		"April" : {
			"conversion_rate" : null,
			"baseline_rate" : null,
			"lift" : null
		},
		"May" : {
			"conversion_rate" : null,
			"baseline_rate" : null,
			"lift" : null
		},
		"June" : {
			"conversion_rate" : null,
			"baseline_rate" : null,
			"lift" : null
		},
		"July" : {
			"conversion_rate" : null,
			"baseline_rate" : null,
			"lift" : null
		},
		"August" : {
			"conversion_rate" : null,
			"baseline_rate" : null,
			"lift" : null
		},
		"September" : {
			"conversion_rate" : null,
			"baseline_rate" : null,
			"lift" : null
		},
		"October" : {
			"conversion_rate" : null,
			"baseline_rate" : null,
			"lift" : null
		},
		"November" : {
			"conversion_rate" : null,
			"baseline_rate" : null,
			"lift" : null
		},
		"December" : {
			"conversion_rate" : null,
			"baseline_rate" : null,
			"lift" : null
		}
	};

	var dates_short = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEPT','OCT','NOV','DEC'];
	var dates_final = ['January','February','March','April','May','June','July','August','September','October','November','December'];
	var conversions_final = [];
	var baselines_final = [];
	var lift_final = [];
	var original_values = {};

	for (var dcount = 0; dcount < dates.length; dcount++)
	{
		var conversion = conversion_rates[dcount];
		var baseline = baseline_rates[dcount];
		var lift = monthly_lifts[dcount];
		var index = dates[dcount].substring(0,3).toUpperCase();
		var changed_baseline = false;
		var changed_conversion = false;

		original_values[index] = {
			"conversion_rate" : conversion,
			"baseline_rate" : baseline,
			"lift" : lift
		};

		//Below two if conditions are for displaying small bar line on chart if data is too small.
		if (conversion < 0.02 && baseline > 0.5)
		{
			conversion = 0.02;
			var w_baseline = (baseline/20);

			if (w_baseline > 0.02)
			{
				conversion = w_baseline;
				changed_conversion = true;
			}
		}
		else if (baseline == 0 && conversion <= 0.5)
		{
			baseline = (conversion/20);
			changed_baseline = true;
		}

		if (baseline < 0.02 && conversion > 0.5)
		{
			baseline = 0.02;
			var w_conversion = (conversion/20);

			if (w_conversion > 0.02)
			{
				baseline = w_conversion;
				changed_baseline = true;
			}
		}
		else if (conversion == 0 && baseline <= 0.5)
		{
			conversion = (baseline/20);
			changed_conversion = true;
		}

		//The code written in below 2 if conditions will check if the changed conversion/baseline value is greater than the original conversion/baseline value for any entry.
		//If it is, then it will use that minimum value instead of using changed value.
		//Ex - If there are 2 entries in baseline array - 0.01, 0.02. Then above code will change the value 0.01 to 0.04 to display the bar line,
		//But this will look bigger than the bar line for value 0.02, which is wrong. So instead of changing 0.01 to 0.04, we will change it to 0.02.
		if (changed_conversion)
		{
			var temp_conversion_rates = conversion_rates.slice(0);
			temp_conversion_rates.splice(dcount, 1);
			var minimum_conversion_value = Math.min.apply(Math,temp_conversion_rates);
			if (conversion > minimum_conversion_value && minimum_conversion_value > 0.01)
			{
				conversion = minimum_conversion_value;
			}
		}
		if (changed_baseline)
		{
			var temp_baseline_rates = baseline_rates.slice(0);
			temp_baseline_rates.splice(dcount, 1);
			var minimum_baseline_value = Math.min.apply(Math,temp_baseline_rates);
			if (baseline > minimum_baseline_value && minimum_baseline_value > 0.01)
			{
				baseline = minimum_baseline_value;
			}
		}

		dates_obj[dates[dcount]].conversion_rate = conversion;
		dates_obj[dates[dcount]].baseline_rate = baseline;
		dates_obj[dates[dcount]].lift = lift;
	}

	for (var prop in dates_obj)
	{
		conversions_final.push(dates_obj[prop].conversion_rate);
		baselines_final.push(dates_obj[prop].baseline_rate);
		lift_final.push(dates_obj[prop].lift);
	}

	var monthly_lift_chart_data = {};

	if (use_short_dates)
	{
		monthly_lift_chart_data['dates'] = dates_short;
	}
	else
	{
		monthly_lift_chart_data['dates'] = dates_short;//dates_final;
	}

	monthly_lift_chart_data['conversion'] = conversions_final;
	monthly_lift_chart_data['baseline'] = baselines_final;
	monthly_lift_chart_data['lift'] = lift_final;
	monthly_lift_chart_data['original_values'] = original_values;

	return monthly_lift_chart_data;
}

function show_date_and_disclaimer_in_header(start_date, end_date)
{
	var formatted_start_date = start_date.split("-");
	formatted_start_date = formatted_start_date[1] + "/" + formatted_start_date[2] + "/" + (formatted_start_date[0].substring(2));
	var formatted_end_date = end_date.split("-");
	formatted_end_date = formatted_end_date[1] + "/" + formatted_end_date[2] + "/" + (formatted_end_date[0].substring(2));
	$("#lift_analysis_overview .date_start").html(formatted_start_date);
	$("#lift_analysis_overview .date_end").html(formatted_end_date);
	$("#lift_analysis_overview .date_in_header").show();
}
