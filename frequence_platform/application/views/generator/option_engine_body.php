<?php
	$this->load->view('vl_platform_2/ui_core/js_error_handling.php');
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>VantageLocal Option Engine</title>
	<style>
	#tt{
		background-color:#f6f6f6;
	}
	</style>
	<link rel="stylesheet" type="text/css" href="/js/jquery-easyui-1.3/themes/default/easyui.css">
	<link rel="stylesheet" type="text/css" href="/js/jquery-easyui-1.3/themes/icon.css">
	<link href="/libraries/external/select2/select2.css" rel="stylesheet"/>
	<!--<script type="text/javascript" src="//code.jquery.com/jquery-1.4.4.min.js"></script>-->
	<!--script type="text/javascript" src="/js/jquery-easyui-1.3/jquery-1.7.2.min.js"></script-->
	<script src="/js/jquery-1.8.3.min.js"></script>
	<!--script src="/bootstrap/assets/js/bootstrap.min.js"></script-->

	<?php
		write_vl_platform_error_handlers_js();
	?>

	<script type="text/javascript">
	// This script section has crontrol for master navigation bar.
	// Only the pieces the user has permission to use are output.


	$(document).ready(function() {
		<?php
			//echo '$("#'.$master_header_data['active_feature_id'].'_feature_link").addClass("active");';
		?>
	});

	</script>

	<script type="text/javascript" src="/js/jquery-easyui-1.3/jquery.easyui.min.js"></script>
	<script src="/libraries/external/select2/select2.js"></script>

	<script>
	var currentOption = 1;
var planIndices = [];
var data_json = '<?php echo $saved_json;?>';
/**
 * function to add an option tab to the option engine
 *
 */

//	handle change that only affects reach frequency data
//	updates the reach/frequency ui with data from server calculations
//
//	return: nothing
function handle_reach_frequency_change(option_id, ad_plan_id)
{
	if (parseInt($('input#gamma_'+option_id+'_'+ad_plan_id).val(), 10) <= -1)
	{
		alert('Your Gamma must be above -1 on Option '+option_id+', Adplan '+ad_plan_id+'.');
		$('input#gamma_'+option_id+'_'+ad_plan_id).val(0);
		handle_reach_frequency_change(option_id, ad_plan_id);
		return false;
	}

	var ad_plan = get_ad_plan_data(option_id, ad_plan_id);
	if(ad_plan != false)
	{
		var ad_plan_json = JSON.stringify(ad_plan);
		$.ajax({
			url: '/mpq/recalculate_reach_frequency_for_option_engine',
			dataType: 'json',
			success: function(data, textStatus, xhr) 
			{
				if(vl_is_ajax_call_success(data))
				{
					var reach_frequency_data = data.reach_frequency;
					apply_reach_frequency_data(option_id, ad_plan_id, reach_frequency_data);
				}
				else
				{
					vl_show_ajax_response_data_errors(data, 'reach frequency change failed');
				}
			},
			error: function(xhr, textStatus, error) 
			{
				vl_show_jquery_ajax_error(xhr, textStatus, error);
			},
			async: false,  // TODO: make async: true - scott (2013/05/20)
			type: "POST", 
			data: {
				ad_plan_json : ad_plan_json
			}
		});
	}
}

//	handle change that affects a single option
//
//	return: nothing
function handle_option_change(change_type, option_id)
{
	common_handle_option_change(change_type, option_id, null, 'option');
}

//	get the index of the ad plan associated with the id
//	if an intermediate tab in the UI is deleted, then the id becomes different than the index
//
//	return: false if there is no tab for the option_id or ad_plan_id
//		the index number otherwise
function get_ad_plan_index_from_id(option_id, ad_plan_id)
{
	var tempIndex = $('#tt').tabs('getTabIndex', $('#Option'+option_id));
	if(tempIndex == -1)
	{
		return false;
	}

	ad_plan_index = 0;
	
	var num_ad_plans = planIndices[option_id];

	for(var ad_plan_id_iterator = 1; ad_plan_id_iterator < num_ad_plans + 1; ad_plan_id_iterator++)
	{
		var tempLapIndex = $('#subtabs' + option_id).tabs('getTabIndex', $('#lap_' + option_id + "_" +ad_plan_id_iterator));

		if(ad_plan_id_iterator == ad_plan_id)
		{
			if(tempLapIndex != -1)
			{
				return ad_plan_index;
			}
			else
			{
				return false;
			}
		}

		if(tempLapIndex != -1)
		{
			ad_plan_index++;
		}
	}

	return false;
}

//	apply data response from server to the particular option tab and all associated ad_plan tabs
//
//	return: nothing
function apply_option_response(option_id, option_data)
{
	apply_option_data(option_id, option_data);
	var ad_plans = option_data.laps;
	var num_ad_plans = ad_plans.length;
	for(var ii = 0; ii < num_ad_plans; ii++)
	{
		var ad_plan_data = ad_plans[ii];
		var ad_plan_id = ad_plan_data.html_id;
		apply_ad_plan_data(option_id, ad_plan_id, ad_plan_data);
	}
}

//	apply data response from server to the specified option tab and ad_plan tab
//
//	return: nothing
function apply_ad_plan_response(option_id, ad_plan_id, option_data, ad_plan_index)
{
	apply_option_data(option_id, option_data);
	var ad_plan_data = option_data.laps[ad_plan_index];
	apply_ad_plan_data(option_id, ad_plan_id, ad_plan_data);
}

//	handles data change to an option
//	gets relevant data for recalculation from the ui, sends it to the server, and applies the resulting data to the ui
//
//	param: change_type: 'string' identifying the type of change which kicked off the handler.
//	param: option_id:	'integer' identifying the option tab which has the change.
//	param: ad_plan_id:	'integer' identifying the ad_plan tab which has the change. this is not used if 'scope' == 'option'
//	param: scope:	'string' describes whether the change is for the entire option or a specific ad_plan
//
//	return: nothing
function common_handle_option_change(change_type, option_id, ad_plan_id, scope)
{

	var option = get_option_data(option_id);
	if(option !== false)
	{
		var ad_plans = get_ad_plans_data(option_id);
		option['laps'] = ad_plans;

		var option_json = JSON.stringify(option);

		var ad_plan_index = get_ad_plan_index_from_id(option_id, ad_plan_id);
		if(scope != 'ad_plan' || ad_plan_index !== false)
		{
			$.ajax({
				url: '/mpq/recalculate_option_for_option_engine',
				dataType: 'json',
				success: function(data, textStatus, xhr) 
				{
					if(vl_is_ajax_call_success(data))
					{
						var option_data = data.option;
						switch(scope)
						{
							case 'option':
								apply_option_response(option_id, option_data);
								break;
							case 'ad_plan':
								apply_ad_plan_response(option_id, ad_plan_id, option_data, ad_plan_index);
								break;
							default:
								vl_show_error_html('unknown scope: '+scope+' ("32871976")');		
								break;
						}
					}
					else
					{
						vl_show_ajax_response_data_errors(data, 'ad plan change failed');
					}
				},
				error: function(xhr, textStatus, error) 
				{
					vl_show_jquery_ajax_error(xhr, textStatus, error);
				},
				async: false,  // TODO: make async: true - scott (2013/05/20)
				type: "POST", 
				data: {
					option_json : option_json,
					ad_plan_index : ad_plan_index,
					change_type : change_type,
					scope : scope
				}
			});
		}
		else
		{
			alert('failed to find lap #'+ad_plan_id+', for option #'+option_id);
		}
	}
}

//	handle change made to a specific ad_plan tab
//
//	return: nothing
function handle_ad_plan_change(change_type, option_id, ad_plan_id)
{
	build_to_planner_button(option_id, ad_plan_id);
	common_handle_option_change(change_type, option_id, ad_plan_id, 'ad_plan');
}

//	adds a new option tab to the ui with a single ad_plan tab
//
//	return: nothing
function addNewOptionTab(){
    var title = 'Option ' + currentOption;
    var myId = 'Option'+ currentOption;
    var myOption = currentOption++;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", "/proposal_builder/edit_option/" + myOption, false);
    xmlhttp.send();
    var content = xmlhttp.responseText;


    $('#tt').tabs('add',
    {
		id:myId,
		title:title,
		content:content,
		closable:true,
    });
    
    $("#subtabs"+myOption).tabs({
	onClose: function(title, index){
		common_handle_option_change('ad_plan_removed', myOption, null, 'option');
	},
	tools: [
		{
			iconCls: 'icon-add',
			handler: function()
			{
				addInnerTab(myOption);
			}
		}
	],
	scrollIncrement: 500,
	scrollDuration: 200
    });
    addInnerTab(myOption);
}

//	adds a blank option tab without an ad_plan tab.  Used when loading a saved option.
function addBlankTab(){
    var title = 'Option ' + currentOption;
    var myId = 'Option'+ currentOption;
    var myOption = currentOption++;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", "/proposal_builder/edit_option/" + myOption, false);
    xmlhttp.send();
    var content = xmlhttp.responseText;


    $('#tt').tabs('add',{
	id:myId,
	title:title,
	content:content,
	closable:true,
    });
    
    $("#subtabs"+myOption).tabs({
	onClose: function(title, index){
		common_handle_option_change('ad_plan_removed', myOption, null, 'option');
	},
	tools: [
		{
			iconCls: 'icon-add',
			handler: function()
			{
				addInnerTab(myOption);
			}
		}
	],
	scrollIncrement: 500,
	scrollDuration: 200
    });
}

//	disables a ui element
function disable_box(enable_id, disable_id)
{
    document.getElementById(enable_id).disabled = false;
    document.getElementById(disable_id).disabled = "disabled";
}

//	handle toggling cpm from default calculation to custom input values
//	enables/disables ui elements and recalculates option and ad_plan accordingly
function handle_cpm_toggle(option_id, ad_plan_id)
{
	var suffix = option_id+'_'+ad_plan_id;
	if($('#cpm_impressions_'+suffix).prop('disabled') == true)
	{
		$('#cpm_impressions_'+suffix).prop('disabled', false);
		$('#cpm_retargeting_'+suffix).prop('disabled', false);
	}
	else
	{
		$('#cpm_impressions_'+suffix).prop('disabled', true);
		$('#cpm_retargeting_'+suffix).prop('disabled', true);
	}

	common_handle_option_change('custom_cpm', option_id, ad_plan_id, 'ad_plan');
}

//	handle changes to cpm values.  Recalculates ui if editing is enabled
function handle_cpm_change(suffix)
{
	if($('#cpm_impressions_'+suffix).prop('disabled') == true)
	{
	}
	else
	{
		var ids = suffix.split('_');
		common_handle_option_change('cpm', ids[0], ids[1], 'ad_plan');
	}
}

function set_period_type_strings(option_id, ad_plan_id, period_type)
{
	switch(period_type)
	{
		case 'months':
		default:
			document.getElementById('retargeting_period_'+option_id+"_"+ad_plan_id).innerHTML = 'month';
			var period_type_title = 'Monthly';
			document.getElementById('option_pricing_base_period_type_'+option_id).innerHTML = period_type_title;
			document.getElementById('option_pricing_total_period_type_'+option_id).innerHTML = period_type_title;
			document.getElementById('ad_plan_budget_period_type_'+option_id).innerHTML = period_type_title;
			document.getElementById('ad_plan_impressions_period_type_'+option_id).innerHTML = period_type_title;
			break;
		case 'weeks':
			document.getElementById('retargeting_period_'+option_id+"_"+ad_plan_id).innerHTML = 'week';
			var period_type_title = 'Weekly';
			document.getElementById('option_pricing_base_period_type_'+option_id).innerHTML = period_type_title;
			document.getElementById('option_pricing_total_period_type_'+option_id).innerHTML = period_type_title;
			document.getElementById('ad_plan_budget_period_type_'+option_id).innerHTML = period_type_title;
			document.getElementById('ad_plan_impressions_period_type_'+option_id).innerHTML = period_type_title;
			break;
		case 'days':
			document.getElementById('retargeting_period_'+option_id+"_"+ad_plan_id).innerHTML = 'day';
			var period_type_title = 'Daily';
			document.getElementById('option_pricing_base_period_type_'+option_id).innerHTML = period_type_title;
			document.getElementById('option_pricing_total_period_type_'+option_id).innerHTML = period_type_title;
			document.getElementById('ad_plan_budget_period_type_'+option_id).innerHTML = period_type_title;
			document.getElementById('ad_plan_impressions_period_type_'+option_id).innerHTML = period_type_title;
			break;
	}
}

//	when the period_type changes, recalculate ui and update pertiod_type terms throughout
//
//	return: nothing
function handle_period_type_change(option_id, ad_plan_id)
{
	handle_ad_plan_change('period_type', option_id, ad_plan_id);

	var period_type = document.getElementById('period_type_'+option_id+"_"+ad_plan_id).value;
	set_period_type_strings(option_id, ad_plan_id, period_type);
}

//	function to add an ad plan tab to the currently selected option tab
function addInnerTab(optionId, lap_data){
    if(planIndices[optionId] == undefined){
	planIndices[optionId] = 1;
    }
    var plan_index = planIndices[optionId];
    var term = $('#term_'+optionId+'_'+planIndices[optionId]).val();
    var title = 'Ad Plan ' + planIndices[optionId];
    if (lap_data == undefined)
    {
    	var lap_id = "";
    	lap_data = {
    		pretty_population: "N/A",
    		pretty_demo_population: "N/A"
    	};
    	var checked = 'checked';
    }
    else
    {
    	var lap_id = lap_data.lap_id;
    	title = lap_data.plan_name;
    	var checked = lap_data.retargeting == "1" ? 'checked' : '';
    }

    var content = 
    '<div style="font-size:1.25em;margin-bottom:20px;">'+
    'Select a Local Ad Plan:&nbsp;&nbsp;&nbsp;&nbsp;'+
    '<div style="width:285px;display:inline-block;"><input type="hidden" id="selected_lap_'+optionId+'_'+planIndices[optionId]+'" name="selected_lap_'+optionId+'_'+planIndices[optionId]+'" onchange="handle_ad_plan_change(\'select_lap\', '+optionId+', '+planIndices[optionId]+');" style="width:100%" value="'+lap_id+'"/></div>'+
	'<br /><br /><span id="planner_link_'+optionId+'_'+planIndices[optionId]+'">LINK</span>'+
    '</div>'+
	'<table style="padding:2px;border-collapse:collapse;position:relative;">'+
	'<tr>'+
	'<td style="padding-bottom:20px;">'+
	'<table>'+
	'<tr>'+
	'<td style="text-align:right;margin-right:10px;">Term</td>'+
	'<td>'+
	'<select onchange="handle_ad_plan_change(\'term_duration\', '+optionId+', '+planIndices[optionId]+');" id="term_'+optionId+'_'+planIndices[optionId]+'" name="term_'+optionId+"_"+planIndices[optionId]+'">'+
	'<option value="1">1</option>'+
	'<option value="2">2</option>'+
	'<option value="3">3</option>'+
	'<option value="4">4</option>'+
	'<option value="5">5</option>'+
	'<option value="6" selected="selected">6</option>'+
	'<option value="7">7</option>'+
	'<option value="8">8</option>'+
	'<option value="9">9</option>'+
	'<option value="10">10</option>'+
	'<option value="11">11</option>'+
	'<option value="12">12</option>'+
	'</select>'+
	'<select onchange="handle_period_type_change(\''+optionId+'\', \''+planIndices[optionId]+'\');" id="period_type_'+optionId+'_'+planIndices[optionId]+'" name="period_type_'+optionId+'_'+planIndices[optionId]+'">'+
	'<option value="days">days</option>'+
	'<option value="weeks">weeks</option>'+
	'<option value="months" selected="selected">months</option>'+
	'</select>'+
	'</td>'+
	'</tr>'+
	'<tr>'+
	'<td style="text-align:right;margin-right:10px;"><span id="ad_plan_budget_period_type_'+optionId+'">Monthly</span> Budget</td>'+
	'<td><input type="text" value="0" name="budget_'+optionId+'_'+planIndices[optionId]+'" id="budget_'+optionId+'_'+planIndices[optionId]+'" onChange="handle_ad_plan_change(\'budget\', \''+optionId+'\', \''+planIndices[optionId]+'\')"/>'+
	'<input type="radio" name="group_'+optionId+'_'+planIndices[optionId]+'" value="budget_'+optionId+'_'+planIndices[optionId]+'" id="group_'+optionId+'_'+planIndices[optionId]+'_budget" checked="checked" onclick="disable_box(this.value, \'impressions_'+optionId+'_'+planIndices[optionId]+'\')" ></td>'+
	'</tr>'+
	'<tr>'+
	'<td style="text-align:right;margin-right:10px;"><span id="ad_plan_impressions_period_type_'+optionId+'">Monthly</span> Impressions</td>'+
	'<td>'+
	'<input type="text" value="0" name="impressions_'+optionId+'_'+planIndices[optionId]+'" id="impressions_'+optionId+'_'+planIndices[optionId]+'" disabled="disabled" onChange="handle_ad_plan_change(\'impressions\', \''+optionId+'\', \''+planIndices[optionId]+'\');"/>'+
        '<input type="radio" name="group_'+optionId+'_'+planIndices[optionId]+'" value="impressions_'+optionId+'_'+planIndices[optionId]+'" id="group_'+optionId+'_'+planIndices[optionId]+'_impressions" onclick="disable_box(this.value, \'budget_'+optionId+'_'+planIndices[optionId]+'\')" ></td>'+
	'<td></td>'+
	'</tr>'+
	'<tr>'+
	'<td style="text-align:right;margin-right:10px;">Include Retargeting ($<span id="retargeting_cost_'+optionId + '_' + planIndices[optionId]+'">0</span>/<span id="retargeting_period_'+optionId + '_' + planIndices[optionId]+'">month</span>)</td>'+
	'<td><input type="checkbox" name="retargeting_'+optionId+'_'+planIndices[optionId]+'" id="retargeting_'+optionId+'_'+planIndices[optionId]+'" '+ checked +' onChange="handle_ad_plan_change(\'retargeting\', \''+optionId+'\', \''+planIndices[optionId]+'\')"/></td>'+
	'</tr>'+
	'</table></div>'+
	'</td>'+
	'<td style="vertical-align:top;">'+
	'<div style="border:solid 2px #0969A4;padding:15px 10px;text-align:center;line-height:1.75em;">'+
	'<button onclick="handle_cpm_toggle('+optionId+', '+planIndices[optionId]+');" style="background-color: #f6f6f6;-moz-border-radius: 7px;-webkit-border-radius: 7px;width:100px;height:30px;">Custom CPM</button><br />'+
	'Impressions &nbsp;<input disabled=true onchange="handle_cpm_change(\''+optionId+'_'+planIndices[optionId]+'\');" id="cpm_impressions_'+optionId+'_'+planIndices[optionId]+'" size="4" type="text" /><br />'+
	'Retargeting &nbsp;<input onchange="handle_cpm_change(\''+optionId+'_'+planIndices[optionId]+'\');" id="cpm_retargeting_'+optionId+'_'+planIndices[optionId]+'" disabled=true size="4" type="text" />'+
	'</div>'+
	'</td>'+
	'<table>'+
	'<tr>'+
	'<td>'+
	'<table style="width:220px;margin-right:20px;">'+
	'<tr><td>Max Target Reach</td><td><input type="number" min="0" max="1" step="0.1" onchange="handle_reach_frequency_change('+optionId+', '+planIndices[optionId]+');" id="demo_coverage_'+optionId+'_'+planIndices[optionId]+'" type="text" style="width:50" value="0.87" /></td></tr>'+
	'<tr><td>Ip Accuracy</td><td><input type="number" min="0" max="1" step="0.1"  onchange="handle_reach_frequency_change('+optionId+', '+planIndices[optionId]+');" id="ip_accuracy_'+optionId+'_'+planIndices[optionId]+'" type="text" style="width:50" value="0.99" /></td></tr>'+
	'<tr><td>Gamma</td><td><input type="number" min="-0.9" max="1" step="0.1"  onchange="handle_reach_frequency_change('+optionId+', '+planIndices[optionId]+');" id="gamma_'+optionId+'_'+planIndices[optionId]+'" type="text" style="width:50" value="0.3" /></td></tr>'+
	'<tr><td>Population:</td><td class="geo_population">'+ lap_data.pretty_population +'</td></tr>'+
	'<tr><td>Target Population:</td><td class="target_population">'+ lap_data.pretty_demo_population +'</td></tr>'+
	'</table>'+
	'</td>'+
	'<td>'+
	'<table class="rf_table" border="2" id="reach_freq_'+optionId+'_'+planIndices[optionId]+'">'+
	'<tr id="period_row_'+optionId+'_'+planIndices[optionId]+'"><th>Period</th></tr>'+
	'<tr id="impression_row_'+optionId+'_'+planIndices[optionId]+'"><th>Impressions</th></tr>'+
	'<tr id="percent_row_'+optionId+'_'+planIndices[optionId]+'"><th>Reach[%]</th></tr>'+
	'<tr id="reach_row_'+optionId+'_'+planIndices[optionId]+'"><th>Reach</th></tr>'+
	'<tr id="frequency_row_'+optionId+'_'+planIndices[optionId]+'"><th>Frequency</th></tr>'+
	'</table>'+
	'</td>'+
	'</tr>'+
	'</table>';

    var myId = "lap_"+optionId + "_" +planIndices[optionId];
    $('#subtabs' + optionId).tabs('add',{
		id:myId,
		title:title,
		content:content,
		closable:true
    });

    $('#selected_lap_'+optionId+'_'+planIndices[optionId]+'').select2({
    	minimumInputLength: 0,
    	placeholder: "Start typing to find a LAP...",
    	ajax: {
			url: "/proposal_builder/get_laps_select2",
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
		formatResult: format_s2,
		formatSelection: format_s2,
		allowClear: false
    });
    if (lap_id !== "")
    {
	    $('#selected_lap_'+optionId+'_'+planIndices[optionId]+'').select2('data', lap_data);
	    $('#selected_lap_'+optionId+'_'+planIndices[optionId]+'').val(lap_id);
	}

    $('#selected_lap_'+optionId+'_'+planIndices[optionId]+'').on('change', function(data, x, y, z) {
    	var tab_header = $('#subtabs'+optionId+'').find('ul.tabs li')[plan_index - 1];
    	$(tab_header).find('span.tabs-title').html(data.added.plan_name);

    	$('#lap_'+optionId+'_'+plan_index+' td.geo_population').html(data.added.population);
    	$('#lap_'+optionId+'_'+plan_index+' td.target_population').html(data.added.demo_population);
    });

    build_to_planner_button(optionId, planIndices[optionId]);

    planIndices[optionId]++;
}

function format_s2(obj)
{
	return obj.plan_name+': <span style="font-size:0.8em;color:#aaa;">'+obj.advertiserElem+'</span>';
}

//	get the data for the option, excluding its ad_plans
//
//	return: the data for the option
function get_option_data(option_id)
{
	var tempIndex = $('#tt').tabs('getTabIndex', $('#Option'+option_id));
	if(tempIndex == -1){
		return false;
	}

	option_data = {};
	
	if (document.getElementById('creative_design_'+option_id).checked) {
	    option_data['creative_design'] = 1;
	} else {
	    option_data['creative_design'] = 0; 
	}	
	
	option_data['option_name'] = document.getElementById('option_name_'+option_id).value;

	option_data['monthly_raw_cost'] = document.getElementById('monthly_raw_cost_'+option_id).innerHTML;
	option_data['monthly_total_cost'] = document.getElementById('monthly_total_cost_'+option_id).innerHTML;
	option_data['discount_monthly_percent'] = document.getElementById('discount_monthly_percent_'+option_id).value;
	if(document.getElementById('campaign_or_month_'+option_id).checked)
	{
	    option_data['cost_by_campaign'] = 1;
	}
	else
	{
	    option_data['cost_by_campaign'] = 0;
	}

	return option_data;
}

//	apply the data to the optoin ui, excluding its ad_plans
//
//	return false if can't find option tab, true otherwise
function apply_option_data(option_id, option_data)
{
	var tempIndex = $('#tt').tabs('getTabIndex', $('#Option'+option_id));
	if(tempIndex == -1){
		return false;
	}

	$('#monthly_raw_cost_'+option_id).html(option_data['monthly_raw_cost']);
	$('#monthly_total_cost_'+option_id).html(option_data['monthly_total_cost']);
	$('#total_impressions_'+option_id).html(option_data['total_impressions']);
	$('#total_cpm_'+option_id).html(option_data['total_cpm']);

	return true;
}

//	get the data for the ad_plans for a particular option
//
//	return: the data for the ad_plans
function get_ad_plans_data(option_id)
{
	var tempIndex = $('#tt').tabs('getTabIndex', $('#Option'+option_id));
	if(tempIndex == -1){
		return false;
	}

	var num_ad_plans = planIndices[option_id];
	var ad_plans = [];

	for(var ad_plan_id = 1; ad_plan_id < num_ad_plans + 1; ad_plan_id++)
	{
		var ad_plan_data = get_ad_plan_data(option_id, ad_plan_id);
		if(ad_plan_data !== false)
		{
			ad_plans.push(ad_plan_data);
		}
	}

	return ad_plans;
}

//	get the data for an ad_plan for a particular option
//
//	return: the data for the ad_plan
function get_ad_plan_data(option_id, ad_plan_id)
{
	var tempLapIndex = $('#subtabs' + option_id).tabs('getTabIndex', $('#lap_' + option_id + "_" +ad_plan_id));
	if(tempLapIndex == -1)
	{
		return false;
	}

	ad_plan_data = {};
	ad_plan_data['html_id'] = ad_plan_id;
	var mySelectBox = document.getElementById('selected_lap_'+option_id+'_'+ad_plan_id);
	ad_plan_data['lap_id'] = mySelectBox.value;
	ad_plan_data['impressions'] = document.getElementById('impressions_'+option_id+"_"+ad_plan_id).value.replace(/\,/g, "");
	ad_plan_data['budget'] = document.getElementById('budget_'+option_id+"_"+ad_plan_id).value;
	ad_plan_data['term'] = document.getElementById('term_'+option_id+"_"+ad_plan_id).value;
	ad_plan_data['period_type'] = document.getElementById('period_type_'+option_id+"_"+ad_plan_id).value;
	ad_plan_data['geo_coverage'] = $('#geo_coverage_'+option_id+"_"+ad_plan_id).val();
	ad_plan_data['gamma'] = $('#gamma_'+option_id+"_"+ad_plan_id).val();
	ad_plan_data['ip_accuracy'] = $('#ip_accuracy_'+option_id+"_"+ad_plan_id).val();
	ad_plan_data['demo_coverage'] = $('#demo_coverage_'+option_id+"_"+ad_plan_id).val();

	var option_type_radio_name = 'group_'+option_id+'_'+ad_plan_id;
	var option_type = $('input[name="'+option_type_radio_name+'"]:checked').val();
	ad_plan_data['option_type'] = option_type;

	if($('#cpm_impressions_'+option_id+"_"+ad_plan_id).prop('disabled') != true)
	{
		ad_plan_data['custom_impression_cpm'] = $('#cpm_impressions_'+option_id+"_"+ad_plan_id).val();
		ad_plan_data['custom_retargeting_cpm'] = $('#cpm_retargeting_'+option_id+"_"+ad_plan_id).val();
	}
	else
	{
		ad_plan_data['custom_impression_cpm'] = 'null';
		ad_plan_data['custom_retargeting_cpm'] = 'null';
	}

	if (document.getElementById('retargeting_'+option_id+"_"+ad_plan_id).checked) {
		ad_plan_data['retargeting'] = 1;
		ad_plan_data['retargeting_price'] = document.getElementById('retargeting_cost_'+option_id+"_"+ad_plan_id).innerHTML;
	} else {
		ad_plan_data['retargeting'] = 0;
		ad_plan_data['retargeting_price'] = 0;
	}

	return ad_plan_data;
}

//	update the reach/frequency ui
//
//	return: nothing
function apply_reach_frequency_data(option_id, ad_plan_id, reach_frequency_data)
{
	$('.rf_table').find('.rf_td_'+option_id+'_'+ad_plan_id).remove();
	var num_rows = reach_frequency_data.length;
	for(var ii = 1; ii <= num_rows; ii++) // r/f calc
	{
		var row = reach_frequency_data[ii - 1];
		$('#period_row_'+option_id+'_'+ad_plan_id).append("<td class='rf_td_"+option_id+'_'+ad_plan_id+"'>&nbsp;"+ii+"&nbsp;</td>");
		$('#impression_row_'+option_id+'_'+ad_plan_id).append("<td class='rf_td_"+option_id+'_'+ad_plan_id+"'>"+row['impressions']+"</td>");
		$('#percent_row_'+option_id+'_'+ad_plan_id).append("<td class='rf_td_"+option_id+'_'+ad_plan_id+"'>"+row['reach_percent']+"</td>");
		$('#reach_row_'+option_id+'_'+ad_plan_id).append("<td class='rf_td_"+option_id+'_'+ad_plan_id+"'>"+row['reach']+"</td>");
		$('#frequency_row_'+option_id+'_'+ad_plan_id).append("<td class='rf_td_"+option_id+'_'+ad_plan_id+"'>"+row['frequency']+"</td>");
	}
}

//	update the ad_plan ui
//
//	return: false if can't find the option or ad_plan tab, true otherwise
function apply_ad_plan_data(option_id, ad_plan_id, ad_plan_data)
{
	var id_suffix = option_id + "_" +ad_plan_id;

	var tempLapIndex = $('#subtabs' + option_id).tabs('getTabIndex', $('#lap_' + id_suffix));
	if(tempLapIndex == -1)
	{
		return false;
	}

	document.getElementById('impressions_'+id_suffix).value =	ad_plan_data['impressions'] ;
	//.replace(/\,/g, "")
	document.getElementById('budget_'+id_suffix).value =	ad_plan_data['budget'] ;
	if($('#cpm_impressions_'+id_suffix).prop('disabled') == true)
	{
		$('#cpm_impressions_'+id_suffix).val(ad_plan_data['custom_impression_cpm']);
		$('#cpm_retargeting_'+id_suffix).val(ad_plan_data['custom_retargeting_cpm']);
	}

	document.getElementById('retargeting_cost_'+id_suffix).innerHTML = ad_plan_data['retargeting_price'];

	apply_reach_frequency_data(option_id, ad_plan_id, ad_plan_data['reach_frequency_table']);

	return true;
}

//	get all option and ad_plan data from page and save it to database on server
//	
//	return: nothing
function save_proposal()
{
    var proposalName = document.getElementById('proposal_name').value;
    var show_pricing = (document.getElementById('show_pricing').checked == true ? 1 : 0);
    var rep_id = $('#rep_id').val();
    var options = [];
    var error_string = "";
    for(var i = 1; i < currentOption + 1; i++)
	{
		var option = get_option_data(i);
		if(option !== false)
		{
			var ad_plans = get_ad_plans_data(i);
			var unique_laps = [];
			option['laps'] = ad_plans;
			options.push(option);
			for(var x = 0; x <= ad_plans.length - 1; x++)
			{
				if (unique_laps.indexOf(ad_plans[x].lap_id) >= 0)
				{
					alert("You can not use the same LAP twice in one option. Please select a different LAP or create a new one.");
					return false;
				}
				else 
				{
					if (ad_plans[x].lap_id == "")
					{
						alert("Some of your adplans do not have a LAP selected. Please fill out all adplans.");
						return false;
					}
					unique_laps.push(ad_plans[x].lap_id);
				}
			}
		}
	}

	$.ajax({
		type: "POST",
		url: "/proposal_builder/save_proposal/<?php echo ($prop_id == null) ? '' : $prop_id; ?>",
		async: false,
		data: {name: proposalName, data: options, show_pricing: show_pricing, rep_id: rep_id},
		dataType: 'json',
		success: function(data, textStatus, xhr){
			alert("proposal save request sent");
			window.location='/proposal_builder/option_engine/'+data+'';
		},
		error: function(xhr, textStatus, error){
			alert("Error 0392911: Unable to save proposal.");
		}
	});

}

function roundNumber(num, dec) {
    var result = Math.round(num*Math.pow(10,dec))/Math.pow(10,dec);
    return result;
}	

// TODO: remove this -scott (2013_07_24)
function updateCost(optionId)
{
    alert("Error: updateCost no longer exists.");
}

//	programmatically preselects a value from a dropdown list after searching by value.
//	used to load proposals from database back into view.
function setSelectedIndex(s, valsearch)
{
    // Loop through all the items in drop down list
    for (i = 0; i< s.options.length; i++)
    {
	if (s.options[i].value == valsearch)
	{
	    // Item is found. Set its property and exit
	    s.options[i].selected = true;
	    break;
	}
    }
    return;
}

function isNumber(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}

function build_to_planner_button (option_id, plan_id)
{
	var prop_id = '<?php echo $prop_id; ?>';
	var lap_id = $('input#selected_lap_'+option_id+'_'+plan_id).val();
	var html = '<a target="_blank" href="/proposal_builder/force_session/'+lap_id+'">Go to LAP '+lap_id+'</a>';
    html += prop_id && lap_id ? '<br /><a target="_blank" href="/proposal_builder/lap_image_gen/'+lap_id+'/'+prop_id+'">Generate images for this lap</a>' : '';
    $("#planner_link_"+option_id+"_"+plan_id).html(html);

}

$(document).ready(function(e) {
	$('#tt').tabs();
	if('<?php echo $is_saved;?>' == "saved")
	{
		// load functionality is implemented here within this little logic bit.
		var data = jQuery.parseJSON(data_json);
		var show_pricing = data['show_pricing'];
		document.getElementById("show_pricing").checked = (show_pricing == 1 ? true : false);

		var lap_id = data['options'][0]['laps'][0]['lap_id'];
		document.getElementById('ids_go_here').innerHTML = "&nbsp;&nbsp;&nbsp;<a target='_blank' href='/proposal_builder/edit_sitelist/<?php echo $prop_id;?>'>Edit Sitelist</a>&nbsp;&nbsp;&nbsp;<a target='_blank' href='/proposal_builder/control_panel/<?php echo $prop_id;?>'>Display Proposal</a>"
		
		////////////////////////// START DATA INJECTION ////////////////////////////
		document.getElementById('proposal_name').value = data['prop_name'];
		for(option_num = 1; option_num < data['options'].length + 1; option_num++)
		{
			addBlankTab();	

			var option_data = data['options'][option_num - 1];
			document.getElementById('option_name_'+option_num).value = option_data['option_name'];

			document.getElementById('discount_monthly_percent_'+option_num).value = option_data['monthly_percent_discount'];	
			document.getElementById('monthly_raw_cost_'+option_num).innerHTML =	option_data['monthly_raw_cost'];
			document.getElementById('monthly_total_cost_'+option_num).innerHTML =	option_data['monthly_total_cost'];
			$('#total_impressions_'+option_num).html(option_data['total_impressions']);
			$('#total_cpm_'+option_num).html(option_data['total_cpm']);

			if(option_data['creative_design'] == '1')
			{
				document.getElementById('creative_design_'+option_num).checked = true;
			}
			if(option_data['cost_by_campaign'] == '1')
			{
				document.getElementById('campaign_or_month_'+option_num).checked = true;
			}

			for(lap_num = 1; lap_num < option_data['laps'].length + 1; lap_num++)
			{
				var lap_data = option_data['laps'][lap_num - 1];
				addInnerTab(option_num, lap_data);

				var id_suffix = option_num+"_"+lap_num;

				build_to_planner_button(option_num, lap_num);
				document.getElementById('impressions_'+id_suffix).value = lap_data['impressions'];
				document.getElementById('budget_'+id_suffix).value = lap_data['budget'];
				setSelectedIndex(document.getElementById('term_'+id_suffix), lap_data['term']+"");
				setSelectedIndex(document.getElementById('period_type_'+id_suffix), lap_data['period_type']+"");
				//document.getElementById("group_"+id_suffix+"_impressions").checked = true;
				//document.getElementById("group_"+id_suffix+"_cost").checked = false;

				$('#geo_coverage_'+id_suffix).val(lap_data['geo_coverage']);
				$('#gamma_'+id_suffix).val(lap_data['gamma']);
				$('#ip_accuracy_'+id_suffix).val(lap_data['ip_accuracy']);
				$('#demo_coverage_'+id_suffix).val(lap_data['demo_coverage']);
	    	$('#retargeting_cost_'+id_suffix).text(lap_data['retargeting_price']);

				if(lap_data['custom_impression_cpm'] == null || lap_data['custom_retargeting_cpm'] == null)
				{
					$('#cpm_impressions_'+id_suffix).prop('disabled', true);
					$('#cpm_retargeting_'+id_suffix).prop('disabled', true);
					$('#cpm_impressions_'+id_suffix).val(parseFloat(lap_data['default_impression_cpm']));
					$('#cpm_retargeting_'+id_suffix).val(parseFloat(lap_data['default_retargeting_cpm']));
				}
				else
				{
					$('#cpm_impressions_'+id_suffix).prop('disabled', false);
					$('#cpm_retargeting_'+id_suffix).prop('disabled', false);
					$('#cpm_impressions_'+id_suffix).val(parseFloat(lap_data['custom_impression_cpm']));
					$('#cpm_retargeting_'+id_suffix).val(parseFloat(lap_data['custom_retargeting_cpm']));
				}
				apply_reach_frequency_data(option_num, lap_num, lap_data['reach_frequency_table']);

				set_period_type_strings(option_num, lap_num, lap_data['period_type']);
			}
		}
	}
	else
	{
		addNewOptionTab();
	}

	$('#rep_id').select2({
    	minimumInputLength: 0,
    	placeholder: "Start typing to find a rep...",
    	ajax: {
			url: "/proposal_builder/get_reps_select2",
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
		width: '400px',
		formatResult: format_rep_s2,
		formatSelection: format_rep_s2,
		allowClear: true
    });

	if (data && data['rep_id'] !== null)
	{
	    $('#rep_id').select2('data', data);
	    $('#rep_id').val(data['rep_id']);
	}
});

function format_rep_s2(obj)
{
	return obj.first_name+' ' + obj.last_name + ': <span style="font-size:0.8em;color:#aaa;">'+obj.partner_name+'</span>';
}

</script>
</head>
<body style="background-color:#4795d1;">
	<div style="margin-bottom:10px">
		<button onclick="window.location.href = '/proposal_builder/get_all_mpqs';">  <<-- to All MPQs</button> 
		<a href="#" class="easyui-linkbutton" onclick="addNewOptionTab();">Add Option</a>
		Proposal Name:  <input type="textfield" id="proposal_name" name="proposal_name"/><span style="padding-left:15px;">Show Pricing?</span><input id="show_pricing" type="checkbox" checked="checked" />
		<?php 
			if (isset($prop_id))
			{
				echo '&nbsp;&nbsp;&nbsp;<a target="_blank" href="/proposal_builder/lap_image_gen/overview/'.$prop_id.'">Generate overview image</a>';
			}
		?>
		<span id="ids_go_here"></span>
		<span style="display:inline-block;margin-left:20px;">Rep: <div style="width:400px;margin-left:5px;display:inline-block;"><input type="hidden" id="rep_id" name="rep_id"/></div></span>
	</div>
	<div id="tt" class="easyui-tabs">

	</div>
	<br/>
	<a href="#" id ="save_button" class="easyui-linkbutton" onclick="save_proposal();">Save Proposal</a>

	<?php
	write_vl_platform_error_handlers_html_section();
	?>
</body>
</html>
