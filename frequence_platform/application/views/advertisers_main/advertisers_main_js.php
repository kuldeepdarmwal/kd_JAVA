<!-- ADVERTISERS MAIN JS -->
<script src="/js/campaign_health/xdate.js" type="text/javascript"></script>
<script src="/js/highchart/js/highcharts.js"></script>
<script src="/libraries/external/select2-3.5.2/select2.js"></script>	
<script src="/bootstrap/assets/js/bootstrap-tooltip.js"></script>
<script type="text/javascript" src="/js/campaign_health/bootstrap-datepicker.js"></script>

<script type="text/javascript">
var am_static_card_width = 340;
var am_timeout_id;
var am_data_offset;
var am_data_limit;
var am_total_loaded = 0;
var am_ajax_more = true;
var am_ajax_pending = false;

$(document).ready(function(){
	var am_selected_user = JSON.parse('<?php echo json_encode($selected_user); ?>');

	var report_date = '<?php echo $report_date; ?>';
	var am_date = new Date(report_date);
	am_date.setDate(am_date.getDate()+1);
	var am_day = am_date.getDate();
	var am_month = am_date.getMonth()+1;
	var am_enddate = ((''+am_month).length<2 ? '0' : '') + (am_month) + '/' + ((''+am_day).length<2 ? '0' : '') + am_day + '/' +  am_date.getFullYear();

	am_date = new Date(new Date(am_date).setDate(am_date.getDate()-14));
	var am_day = am_date.getDate();
	var am_month = am_date.getMonth()+1;
	var am_startdate = ((''+am_month).length<2 ? '0' : '') + (am_month) + '/' + ((''+am_day).length<2 ? '0' : '') + am_day + '/' +  am_date.getFullYear();

	$("#am_start_date").val(am_startdate);
	$("#am_end_date").val(am_enddate);
	
	$(".am_datepicker").datepicker();

	$('#am_date_control_button').click(function(){
		create_advertiser_card_view();
	});

	$('#am_advertiser_owner_select').select2({
		allowClear: true,
		minimumInputLength: 0,
		ajax:{
			type: 'POST',
			url: '/advertisers_main/select2_get_advertiser_owners_with_advertisers/',
			dataType: 'json',
			data: function (term, page){
				term = (typeof term === "undefined" || term == "") ? "%" : term;
				return{
					q: term,
					page_limit: 20,
					page: page
				};
			},
			results: function(data){
				return {results: data.result, more: data.more};
			}
		},
		initSelection: function(element, callback) {
			callback(am_selected_user);
		}
	}).on("change", create_advertiser_card_view);
	
	create_advertiser_card_view();
});

function create_advertiser_card_view()
{
	am_data_offset = 0;
	am_total_loaded = 0;
	am_data_limit = Math.max(Math.floor(($(window).width() - 10)/am_static_card_width), 2);
	$('#am_loading_img').show();
	$('#am_body_content').html('').hide();
	
	get_advertiser_card_data();
}

function get_advertiser_card_data()
{
	var advertiser_owner = $('#am_advertiser_owner_select').select2('data').id;
	var start_date = $('#am_start_date').val();
	var end_date = $('#am_end_date').val();
	ajax_pending = true;
	$.ajax({
		type: "POST",
		url: '/advertisers_main/ajax_get_advertiser_card_data',
		async: true,
		dataType: 'json',
		data: 
		{
			user_id: advertiser_owner,
			start_date: start_date,
			end_date: end_date,
			offset: am_data_offset,
			limit: am_data_limit
		},
		success: function(data, textStatus, jqXHR){
			ajax_pending = false;
			$('#am_loading_img').hide();
			$('#am_body_content').show();
			if(typeof data.is_success !== 'undefined' && typeof data.errors !== 'undefined' && typeof data.data !== 'undefined')
			{
				if(data.is_success === true)
				{
					data.data.sort(sort_by_impressions);
					$.each(data.data, function(key, value){
						create_new_advertiser_card(value, start_date, end_date);
					});
					$('i.am_red_icon_display').tooltip({
						placement: 'top',
						trigger: 'hover',
						title: 'All campaigns are archived for this advertiser'
					});
					am_ajax_more = data.more;
				}
				else
				{
					set_message_timeout_and_show(data.errors, 'alert alert-error', 16000);
				}
			}
		},
		error: function(jqXHR, textStatus, error){
			ajax_pending = false;
			if(xhr.getAllResponseHeaders())
			{
				set_message_timeout_and_show("Error 754825: Server Error", 'alert alert-error', 16000);
			}
		}
	});
}

function sort_by_impressions(a, b)
{
	return b.average_impressions - a.average_impressions;
}

function create_new_advertiser_card(obj, start, end)
{
	am_total_loaded++;
	var red_icon_class = "am_red_icon_display ";
	if(obj.num_active_campaigns > 0)
	{
		red_icon_class = "am_red_icon_hide ";
	}

	var html = ''+
		'<div id="am_card_'+obj.advertiser_id+'" class="am_advertiser_card">'+
			'<div class="am_card_header">'+
				'<i class="am_red_icon '+red_icon_class+'icon-warning-sign"></i>'+
				'<div class="am_card_text_header">'+
					'<div style="display:inline-block;">'+
						'<span class="am_card_small_text">'+obj.partner_name+' - '+obj.user_name+'</span>'+
 						'<br>'+
						'<span class="am_card_header_text"><a class="am_card_header_link" href="/campaign_setup/'+obj.first_campaign_id+'" target="_blank">'+obj.advertiser_name+'</a></span>'+
					'</div>'+
				'</div>'+
			'</div>'+
			'<div id="am_card_body_'+obj.advertiser_id+'">'+
				'<div id="am_card_body_ctr_'+obj.advertiser_id+'" class="am_card_body"></div>'+
				'<div id="am_card_body_adr_'+obj.advertiser_id+'" class="am_card_body"></div>'+
				'<div id="am_card_body_vtr_'+obj.advertiser_id+'" class="am_card_body"></div>'+
				'<div id="am_card_body_pcr_'+obj.advertiser_id+'" class="am_card_body am_card_body_bottom"></div>'+
			'</div>'+
		'</div>';
	$(html).appendTo('#am_body_content').hide().fadeIn(300);

	var end_date = new Date(end);
	var current_date = new Date(start);
	var all_dates = [];
	var impression_data = [];
	var non_pr_impression_data = [];
	var pr_impression_data = [];
	var ctr_data = [];
	var pr_ctr_data = [];
	var vtr_data = [];
	var pr_vtr_data = [];
	var prcr_data = [];
	var adir_data = [];
	
	while(current_date <= end_date)
	{
		var temp_day = current_date.getDate();
		var temp_month = current_date.getMonth()+1;
		var temp_date = ((''+temp_month).length<2 ? '0' : '') + (temp_month) + '/' + ((''+temp_day).length<2 ? '0' : '') + temp_day + '/' +  current_date.getFullYear();
		all_dates.push(temp_date);
		current_date.setDate(current_date.getDate() + 1);
		if(typeof obj['graph_data'][temp_date] === "undefined")
		{
			impression_data.push(0);
			non_pr_impression_data.push(0);
			pr_impression_data.push(0);
			ctr_data.push(0);
			pr_ctr_data.push(0);
			prcr_data.push(0);
			adir_data.push(0);
			vtr_data.push(0);
			pr_vtr_data.push(0);
		}
		else
		{
			impression_data.push(parseInt(obj['graph_data'][temp_date]['impressions']));
			non_pr_impression_data.push(parseInt(obj['graph_data'][temp_date]['non_pr_impressions']));
			pr_impression_data.push(parseInt(obj['graph_data'][temp_date]['pr_impressions']));
			if(parseInt(obj['graph_data'][temp_date]['impressions']) == 0)
			{
				ctr_data.push(0);
				pr_ctr_data.push(0);
				prcr_data.push(0);
				adir_data.push(0);
			}
			else
			{
				var ctr = parseInt(obj['graph_data'][temp_date]['non_pr_clicks'])/parseInt(obj['graph_data'][temp_date]['impressions']) * 100;
				ctr_data.push(Math.round(ctr * 100)/100);
				
				var pr_ctr = parseInt(obj['graph_data'][temp_date]['pr_clicks'])/parseInt(obj['graph_data'][temp_date]['impressions']) * 100;
				pr_ctr_data.push(Math.round(pr_ctr * 100)/100);				

				if(obj['graph_data'][temp_date]['pr_impressions'] > 0)
				{
					var prcr = parseInt(obj['graph_data'][temp_date]['pr_100_percent_completions'])/parseInt(obj['graph_data'][temp_date]['pr_impressions']) * 100;
					prcr_data.push(Math.round(prcr * 100)/100);
				}
				else
				{
					prcr_data.push(0);
				}
				var adir = parseInt(obj['graph_data'][temp_date]['ad_interactions'])/parseInt(obj['graph_data'][temp_date]['impressions']) * 100;
				adir_data.push(Math.round(adir * 100)/100);
			}
			if(obj['average_impressions'] > 0)
			{
				var vtr = parseInt(obj['graph_data'][temp_date]['non_pr_view_throughs'])/parseInt(obj['average_impressions']) * 100;
				vtr_data.push(Math.round(vtr * 100)/100);
				var pr_vtr = parseInt(obj['graph_data'][temp_date]['pr_view_throughs'])/parseInt(obj['average_impressions']) * 100;
				pr_vtr_data.push(Math.round(pr_vtr * 100)/100);
			}
			else
			{
				vtr_data.push(0);
				pr_vtr_data.push(0);
			}
		}

		var non_pr_impression_color = "244, 204, 164";
		var non_pr_line_color = "237, 155, 35";
		var pr_impression_color = "164, 204, 244";
		var pr_line_color = "35, 155, 237";

		var impression_graph_data = {
				name: 'Display Impressions',
				type: 'area',
				zIndex: 0,
				yAxis: 0,
				data: non_pr_impression_data,
				color: "rgba("+non_pr_impression_color+", 1)",
				marker: {enabled: false},
				fillColor: {
					linearGradient: {x1: 0, y1: 0, x2: 0, y2: 1},
					stops: [
						[0, "rgba("+non_pr_impression_color+", 0.9)"],
						[1, "rgba("+non_pr_impression_color+", 0.6)"]
					]
				}
			}; 
		
		
		var pr_impression_graph_data = {
				name: 'Pre-Roll Impressions',
				type: 'area',
				zIndex: 0,
				yAxis: 0,
				data: pr_impression_data,
				color: "rgba("+pr_impression_color+", 1)",
				marker: {enabled: false},
				fillColor: {
					linearGradient: {x1: 0, y1: 0, x2: 0, y2: 1},
					stops: [
						[0, "rgba("+pr_impression_color+", 0.9)"],
						[1, "rgba("+pr_impression_color+", 0.6)"]
					]
				}
			}; 

		var ctr_graph_data = [
			impression_graph_data,
			pr_impression_graph_data,
			{
				name: 'Display',
				type: 'line',
				zIndex: 1,
				yAxis: 1,
				data: ctr_data,
				color: "rgba("+non_pr_line_color+", 1)",
				marker: {
					enabled: false
				}
			},
			{
				name: 'Pre-roll',
				type: 'line',
				dashStyle: 'ShortDash',
				zIndex: 2,
				yAxis: 1,
				data: pr_ctr_data,
				color: "rgba("+pr_line_color+", 1)",
				marker: {
					enabled: false
				}
			}];
		var vtr_graph_data = [
			impression_graph_data,
			pr_impression_graph_data,
			{
				name: 'Display View Through Rate',
				type: 'line',
				zIndex: 1,
				yAxis: 1,
				data: vtr_data,
				color: "rgba("+non_pr_line_color+", 1)",
				marker: {
					enabled: false
				}
			},
			{
				name: 'Pre-Roll View Through Rate',
				type: 'line',
				zIndex: 1,
				yAxis: 1,
				data: pr_vtr_data,
				dashStyle: 'ShortDash',
				color: "rgba("+pr_line_color+", 1)",
				marker: {
					enabled: false
				}
			}];

		var prcr_graph_data = [
			pr_impression_graph_data,
			{
				name: 'Pre Roll Completion Rate',
				type: 'line',
				dashStyle: 'ShortDash',
				zIndex: 1,
				yAxis: 1,
				data: prcr_data,
				color: "rgba("+pr_line_color+", 1)",
				marker: {
					enabled: false
				}
			}];

		var adir_graph_data = [
			impression_graph_data,
			pr_impression_graph_data,
			{
				name: 'Ad Interaction Rate',
				type: 'line',
				zIndex: 1,
				yAxis: 1,
				data: adir_data,
				color: "rgba("+non_pr_line_color+", 1)",
				marker: {
					enabled: false
				}
			}];
		create_highchart_for_advertiser_data('am_card_body_ctr_'+obj.advertiser_id, 'CTR', all_dates, ctr_graph_data, false);
		create_highchart_for_advertiser_data('am_card_body_adr_'+obj.advertiser_id, 'Ad Interactions', all_dates, adir_graph_data, false);
		create_highchart_for_advertiser_data('am_card_body_vtr_'+obj.advertiser_id, 'View Throughs', all_dates, vtr_graph_data, false);
		create_highchart_for_advertiser_data('am_card_body_pcr_'+obj.advertiser_id, 'Pre-Roll Completions', all_dates, prcr_graph_data, true);
	}

}

function create_highchart_for_advertiser_data(id, title, all_dates, graph_data_array, enable_dates)
{
	var tick_interval = Math.ceil(all_dates.length/8);
		var chart = new Highcharts.Chart({
		chart: {
			renderTo: id,
			type: 'area',
			spacingLeft: 0,
			spacingRight: 0,
			plotBackgroundColor: 'rgba(0,0,0,0)',
            backgroundColor: 'rgba(0,0,0,0)'
		},
		title: {
			text: title,
		},
		credits: {enabled: false},
		yAxis: [
			{
				offset: -10,
				maxPadding: 0,
				minPadding: 0,
				title: {text: null},
				opposite: true,
				min: 0
			},
			{
				offset: -10,
				title: {text: null},
				opposite: false,
				labels: {
					formatter: function(){
						return (+(this.value).toFixed(2) + "%").replace(/^[0]+\./g,".");
					},
				},
				min: 0
			}
		],
		xAxis: {
			labels: {
				enabled: enable_dates,
				formatter: function(){
					var split_string = this.value.split('/');
					return split_string[0] + '-' + split_string[1];
				},
				rotation: -45,
				align: 'right',
				style: {fontSize: '12px'}
			},
			categories: all_dates,
			tickInterval: tick_interval
		},
		plotOptions: {
			column: {grouping: false},
			series: {animation: false}
		},
		legend: {enabled: false},
		series: graph_data_array
	});
}

$(window).scroll(function(){
	if($(window).scrollTop() >= ($(document).height() - $(window).height()) && am_ajax_pending == false && am_ajax_more == true)
	{
		var temp_limit = Math.max(Math.floor(($(window).width() - 10)/am_static_card_width), 2);
		var remaining_spaces = am_total_loaded % temp_limit;

		am_data_limit = temp_limit + remaining_spaces;
		am_data_offset += am_data_limit;
		$('#am_loading_img').show(200, get_advertiser_card_data);
	}
});

function set_message_timeout_and_show(message, selected_class, timeout)
{
	window.clearTimeout(am_timeout_id);
	$('#am_message_box_content').append(message+"<br>");
	$('#am_message_box').prop('class', selected_class);
	$('#am_message_box').show();
	am_timeout_id = window.setTimeout(function(){
		$('#am_message_box').fadeOut("slow", function(){
			$('#am_message_box_content').html('');
		});
	}, timeout);
}

</script>
