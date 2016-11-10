<script type="text/javascript">

var campaign_tags_table;
var c_items_to_load = []; //copy (by value) of datatables data elements
var c_display_order = []; //subset of indexes of datatables data in order of current display
var c_display_master = []; //all indexes of datatables data
var c_bidder_loaded_flag = false; //all campaigns loaded flag
var c_is_new_display_flag = false; //page view recently changed flag
var c_raw_to_load_settings = {}; //object for holding datatables settings until bidder ajax has returned
var c_datatables_data = {}; //reference to datatables data elements for modification
var c_bulk_adgroup_campaign_ids = []; //array of highlighted campaign_ids
var c_bulk_archive_campaign_data = {}; //object of campaigns to archive index on campaign_id
var ct_modified_tags = JSON.parse('<?php echo $campaign_tag_ids; ?>');

function format_oti(raw_oti, remaining_imprs, target, reset_date, oti_type, campaign_id)
{
	var oti = Math.round(raw_oti*100);
	var alert_title = "OTI Alert";
	if(oti_type == "total")
	{
		alert_title = "Lifetime OTI Alert";
	}
	else if(oti_type == "cycle")
	{
		alert_title = "Cycle OTI Alert";
	}
	
	var formatted_remaining = format_large_data_num(remaining_imprs, 1);
	var formatted_target = format_large_data_num(target, 1);

	if(oti > 110)
	{
		return '<span class="popover_'+campaign_id+' tool_poppers label label-info" data-toggle="popover" data-trigger="hover" data-placement="right" data-original-title="'+alert_title+'" data-content="Impressions over target">'+oti+'%</span>';
	}
	else if(oti < 100)
	{
		return '<span class="popover_'+campaign_id+' tool_poppers label label-important" data-toggle="popover" data-trigger="hover" data-placement="right" data-original-title="'+alert_title+'" data-content="Need '+formatted_remaining+' impressions by '+reset_date+' ('+formatted_target+' impressions per day).">'+oti+'%</span>';	
	}
	else
	{
		return '<span>'+oti+'%</span>';
	}
}
 
function format_action_date(campaign_id, series_date)
{
	if (series_date != undefined && series_date != "2050-01-01")
	{
		series_date_display=series_date.substring(5, 10);
		return "<button  type=button onclick=\"change_action_date_flag(this, "+campaign_id+", '"+series_date+"')\" class='btn btn-warning btn-mini'  id='action_date_button_"+campaign_id+"'>"+series_date_display+"</button>";
	}
	else
		return "";
}

function format_alerts_string(campaign_id, alerts_string, campaign_adv_name, campaign_notes_string)
{
	var campaign_notes_append = "..";
	if (campaign_notes_string == undefined || campaign_notes_string == "")
	{
		campaign_notes_string = "No notes found";
		campaign_notes_append = "";
	} else if (campaign_notes_string.indexOf("background-color:#C2F7B0") != -1)
	{
		campaign_notes_append = " *";
	}
	if (alerts_string != undefined && alerts_string != "NOTES")
	{
 		alerts_string_subs=alerts_string.substring(0, 6);
 		var label_color = "important";
 		if (alerts_string_subs == 'PAUS_0')
 		{
 			label_color = "success";
 		}
 		campaign_notes_string=alerts_string+campaign_notes_string;
		return '<div class="box"><a class="notes_text" href="#popup_notes'+campaign_id+'">'+alerts_string_subs+campaign_notes_append+'</a></div><div id="popup_notes'+campaign_id+'" class="overlay"><div class="popup_notes_class"><b style="line-height: 30px;">'+campaign_adv_name+'</b><a class="close_note" href="#!">×</a><div class="content">'+campaign_notes_string+'</div></div></div>';
	}
	else 
	{
		return '<div class="box"><a class="notes_text" href="#popup_notes'+campaign_id+'">NOTES'+campaign_notes_append+'</a></div><div id="popup_notes'+campaign_id+'" class="overlay"><div class="popup_notes_class"><b style="line-height:30px;">'+campaign_adv_name+'</b><a class="close_note" href="#!">×</a><div class="content">'+campaign_notes_string+'</div></div></div>';
	}
	return "";
}

function format_daily_oti(raw_oti, yday_impressions, cycle_target, campaign_id)
{
	var oti = Math.round(raw_oti*100);

	if(oti < 100)
	{
		return '<span class="popover_'+campaign_id+' tool_poppers label label-important" data-toggle="popover" data-trigger="hover" data-placement="right" data-original-title="Daily OTI" data-content="adjust by '+format_large_data_num(cycle_target-yday_impressions, 1)+' impressions">'+oti+'%</span>';
	}
	else if(oti > 110)
	{
		return '<span class="popover_'+campaign_id+' tool_poppers label label-info" data-toggle="popover" data-trigger="hover" data-placement="right" data-original-title="Daily OTI" data-content="adjust by '+format_large_data_num(cycle_target-yday_impressions, 1)+' impressions">'+oti+'%</span>';
	}
	else
	{
		return '<span>'+oti+'%</span>';
	}
}

function format_nf_oti(nf_oti, nf_oti_hover, campaign_id)
{
	var nf_oti = Math.round(nf_oti*100);
	if(nf_oti == 0)
	{
		return '<span>'+nf_oti+'%</span>';
	}
	if(nf_oti < 75 || nf_oti > 125)
	{
		return '<span class="popover_'+campaign_id+' tool_poppers label label-important" data-toggle="popover" data-trigger="hover" data-placement="right" data-original-title="NF OTI" data-content="'+nf_oti_hover+'">'+nf_oti+'%</span>';
	}
	else 
	{
		return '<span class="popover_'+campaign_id+' tool_poppers label label-info" data-toggle="popover" data-trigger="hover" data-placement="right" data-original-title="NF OTI"  data-content="'+nf_oti_hover+'">'+nf_oti+'%</span>';
	}
}

function show_removal_confirm_modal(c_id, raw_advertiser_name, raw_campaign_name, target_element)
{
	var advertiser_name = unescape(raw_advertiser_name);
	var campaign_name = unescape(raw_campaign_name);

    $("#confirm_modal_detail_body").html("Are you sure you want to graveyard <strong>"+advertiser_name+" - "+campaign_name+"</strong>?");
    $("#confirm_delete_button").click(function(){
		remove_from_healthcheck(c_id, target_element);
		$("#confirm_delete_button").unbind("click");
	});
}

function remove_from_healthcheck(campaign, target_element)
{
	$.ajax({
		type: "POST",
		url: '/campaigns_main/ajax_remove_from_campaigns_main',
		async: true,
		data: {c_id: campaign},
		dataType: 'json',
		success: function(data, textStatus, jqXHR){
			if(data.is_success === true)
			{
				remove_campaign_id_from_array_if_exist(campaign);
				delete_row(target_element);
				set_message_timeout_and_show('deleted campaign ID:' + campaign, 'alert alert-info', 16000);
			}
			else
			{
				set_message_timeout_and_show(data.errors, 'alert alert-error', 16000);
			}
		},
		error: function(jqXHR, textStatus, error){
			set_message_timeout_and_show('Error 500049: Server Error', 'alert alert-error', 16000);
		}
	});
}

function delete_row(target_element)
{
	var deleted_row = target_element.parents("tr");
	if(typeof deleted_row !== "undefined")
	{
		campaign_table.fnDeleteRow(deleted_row);
	}
	else
	{
		set_message_timeout_and_show("Warning 487140: Campaign archived but failed to remove from view.", 'alert alert-warning', 16000);
	}
}

function show_adgroup_modal(raw_advertiser_name, raw_campaign_name, campaign_id)
{
	var advertiser_name = unescape(raw_advertiser_name);
	var campaign_name = unescape(raw_campaign_name);

 	$('#adgroup_modal_detail_header').html(advertiser_name + ' - ' + campaign_name);
	$('#adgroup_modal_detail_body').html('<div class="progress progress-striped active"> <div class="bar" style="width: 100%;"></div></div>');	
	
	$('#ttd_modify_button').prop('disabled', 'disabled');
	$('#ttd_modify_button').unbind('click');
	$("#adgroup_modal").modal("show");
	var data_url = "/tradedesk/get_adgroup_data/"+campaign_id;
	$.ajax({
        type: "POST",
        url: data_url,
        async: true,
        data: {},
        dataType: 'json',
        success: function(data, textStatus, jqXHR){ 
            if(data.success === true && data.adgroup_ids !== undefined && data.form_html !== undefined)
			{	
				$('#adgroup_modal_detail_body').html(data.form_html);
				$('#ttd_modify_button').click(function(){
					big_form_update(campaign_id, data.adgroup_ids);
				});
				$('#ttd_modify_button').prop('disabled', false);
			}
			else
			{
				$("#adgroup_modal").modal("hide");
				if(data.err_msg !== undefined)
				{
					set_message_timeout_and_show(data.err_msg, 'alert alert-error', 16000);
				}
				else
				{
					set_message_timeout_and_show('Error 509371: Unknown server error.', 'alert alert-error', 16000);
				}
			}
        },
		error: function(jqXHR, textStatus, error){
			set_message_timeout_and_show('Error 500071: Server Error', 'alert alert-error', 16000);
		}
    });
}

function big_form_update(campaign_id, adgroups)
{
	var c_impression_budget = $('#impression_budget_box').val();
	var c_dollar_budget = $('#dollar_budget_box').val();
	var c_start_date = $('#start_date_box').val();
	var c_end_date = $('#end_date_box').val();
	var nan_msg = "The following are invalid, non-number inputs:\n";
	var nan_flag = false;
	var zero_msg = "The following must be non-blank, non-zero inputs:\n";
	var zero_flag = false;
	if(isNaN(c_impression_budget))
	{
		nan_msg += "Campaign Impressions\n";
		nan_flag = true;
	}
	if(isNaN(c_dollar_budget))
	{
		nan_msg += "Campaign $ Budget\n";
		nan_flag = true;
	}
	var adgroup_data = {};
	for(var i = 0; i < adgroups.length; i++)
	{
	    adgroup_data[adgroups[i]] = {};
	    adgroup_data[adgroups[i]]['is_enabled'] = $('#'+adgroups[i]+'_is_enabled_checkbox').prop('checked');
	    adgroup_data[adgroups[i]]['impression_budget'] = $('#'+adgroups[i]+'_impression_budget_box').val();
	    adgroup_data[adgroups[i]]['dollar_budget'] = $('#'+adgroups[i]+'_dollar_budget_box').val();
	    adgroup_data[adgroups[i]]['daily_impressions'] = $('#'+adgroups[i]+'_daily_impression_box').val();
	    adgroup_data[adgroups[i]]['daily_dollars'] = $('#'+adgroups[i]+'_daily_dollar_box').val();
	
	    if(isNaN(adgroup_data[adgroups[i]]['impression_budget']))
	    {
			nan_msg += "Adgroup "+adgroups[i]+" Impression Budget\n";
			nan_flag = true;
	    }
	    if(isNaN(adgroup_data[adgroups[i]]['dollar_budget']))
	    {
			nan_msg += "Adgroup "+adgroups[i]+" $ Budget\n";
			nan_flag = true;
	    }
	    if(isNaN(adgroup_data[adgroups[i]]['daily_impressions']))
	    {
			nan_msg += "Adgroup "+adgroups[i]+" Daily Impressions\n";
			nan_flag = true;
	    }
	    if(isNaN(adgroup_data[adgroups[i]]['daily_dollars']))
	    {
			nan_msg += "Adgroup "+adgroups[i]+" Daily $ Budget\n";
			nan_flag = true;
	    }
	    
	    if(is_zero(adgroup_data[adgroups[i]]['impression_budget']))
	    {
			zero_msg += "Adgroup "+adgroups[i]+" Impression Budget\n";
			zero_flag = true;
	    }
	    if(is_zero(adgroup_data[adgroups[i]]['dollar_budget']))
	    {
			zero_msg += "Adgroup "+adgroups[i]+" $ Budget\n";
			zero_flag = true;
	    }
	    if(is_zero(adgroup_data[adgroups[i]]['daily_impressions']))
	    {
			zero_msg += "Adgroup "+adgroups[i]+" Daily Impressions\n";
			zero_flag = true;
	    }
	    if(is_zero(adgroup_data[adgroups[i]]['daily_dollars']))
	    {
			zero_msg += "Adgroup "+adgroups[i]+" Daily $ Budget\n";
			zero_flag = true;
	    } 
	}
	if(nan_flag)
	{
	    alert(nan_msg);
	    return;
	}
	if(zero_flag)
	{
	    alert(zero_msg);
	    return; 
	}
	
	if(c_impression_budget == "")
	{
		$('#impression_budget_box').val("0");
	}
	if(c_dollar_budget == "")
	{
		$('#dollar_budget_box').val("0");
	}
	$('#ttd_modify_button').prop('disabled', 'disabled');
	$('#ttd_modify_button').html('Updating...');
    
	var data_url = "/tradedesk/update_campaign_from_big_form/";
	$.ajax({
		type: "POST",
		url: "/tradedesk/update_campaign_from_big_form/",
		async: true,
		data: {
			c_id: campaign_id, 
			c_imps: c_impression_budget, 
			c_bux: c_dollar_budget, 
			c_start: c_start_date, 
			c_end: c_end_date, 
			adgroup_data: JSON.stringify(adgroup_data)
		},
		dataType: 'json',
		success: function(returned_data, textStatus, jqXHR){
			$('#ttd_modify_button').prop('disabled', false);
			$('#ttd_modify_button').html('Update');
			if(returned_data.success)
			{
				$('#c_timestamp_box').prop('class', 'label label-warning');
				$('#c_timestamp_box').html('Changes Pending: Just updated');
				
				for(var i = 0; i < adgroups.length; i++)
				{
				    $('#'+adgroups[i]+'_timestamp_box').prop('class', 'label label-warning');
				    $('#'+adgroups[i]+'_timestamp_box').html('Changes Pending: Just updated');
				}
				
                var timebox = $("#date_stamp_"+campaign_id);
                timebox.prop('class', 'label label-warning');
                timebox.html('<i class="icon-warning-sign icon-white"></i>');
                timebox.prop('title', 'You just modified this adgroup.');
				
                if(returned_data.date_check != '-')
                {
                    if(returned_data.date_check < 5)
                    {
                        $('#end_date_'+campaign_id).html(style_past_end_date_cell(campaign_type_stringify(returned_data.campaign_type, returned_data.end_date),returned_data.date_check));
                    }
                    else
                    {
                        $('#end_date_'+campaign_id).html(campaign_type_stringify(returned_data.campaign_type, returned_data.end_date));
                    }
                }
                else
                {
                    $('#end_date_'+campaign_id).html(campaign_type_stringify(returned_data.campaign_type, returned_data.end_date));
                }
				$('#days_left_'+campaign_id).html(returned_data.date_check);
			}
			else
			{
				if(returned_data.err_msg !== undefined)
				{
					set_message_timeout_and_show(returned_data.err_msg, 'alert alert-error', 16000);
				}
				else
				{
					set_message_timeout_and_show("Error 501983: Unknown server error", 'alert alert-error', 16000);
				}
			}
			
		},
		error: function(jqXHR, textStatus, error){ 
			set_message_timeout_and_show("Error 500083: Server Error", 'alert alert-error', 16000);
		}
	});
}

function style_past_end_date_cell(end_date, days_left)
{
	//changed from 0 to 1 but not able to test because it deals with ttd stuff
	if(days_left < 1) 
    {
		return '<span class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="End Date Alert" data-content="This end date has already passed."><span class="label label-important"><i class="icon-exclamation-sign icon-white"></i> '+end_date+'</span></span>';
    }
    if(days_left == 1)
    {
		return '<span class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="End Date Alert" data-content="Today is the last day for this campaign."><span class="label label-warning"><i class="icon-warning-sign icon-white"></i> '+end_date+'</span></span>';
    }
    else
    {
		return '<span class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="End Date Alert" data-content="This campaign is ending in '+days_left+' day(s)."><span class="label"><i class="icon-warning-sign icon-white"></i> '+end_date+'</span></span>';
    }
}

function force_campaign_pending(campaign_id, element)
{
	$(element).prop('disabled', true);
	$(element).popover('hide');
	
	$.ajax({
		type: "POST",
		url: '/campaigns_main/ajax_update_ttd_timestamp',
		async: true,
		data: {c_id: campaign_id},
		dataType: 'json',
		success: function(data, textStatus, jqXHR){
			$(element).prop('disabled', false);
			if(data.is_success === true)
			{
				show_pending_warning_for_campaign(campaign_id);
			}
			else
			{
				set_message_timeout_and_show(data.errors, 'alert alert-error', 16000);
			}
		},
		error: function(xhr, textStatus, error){
			$(element).prop('disabled', false);
			set_message_timeout_and_show('Unable to force pending', 'alert alert-error', 16000);
		}
	});
}


function update_pc_adgroup_impression_target(adgroup_id, campaign_id, element)
{
	$(element).prop('disabled', 'disabled');
	var raw_managed_target_object = $('#managed_target_box_'+campaign_id);
	var original_managed_target_val = raw_managed_target_object.attr('data-original-cycle-target');
	var raw_managed_target = raw_managed_target_object.val();
	var managed_target = 0;
	var miniform = $(element).parent('.c_miniform_div');
	var non_pc_realized = raw_managed_target_object.attr('data-original-non-pc-realized');
	miniform.popover('destroy');
	if($.isNumeric(raw_managed_target))
	{
		managed_target = parseInt(raw_managed_target, 10);
		if(managed_target >= 0)
		{
			$.ajax({
				type: "POST",
				url: "/tradedesk/update_pc_adgroup_impression_target",
				async: true,
				data: {adgroup_id: adgroup_id, campaign_id: campaign_id, new_target: managed_target, non_pc_realized: non_pc_realized},
				dataType: 'json',
				success: function(data, textStatus, jqXHR){ 
					if(data.is_success)
					{
						set_message_timeout_and_show("Bidder data updated successfully", 'alert alert-success', 10000);
						
						$(element).prop('disabled', false);
						
						if(typeof original_managed_target_val !== 'undefined')
						{
							//we want to reset the suggested target back to what it was originally in case the pc adgroup is edited again
							$('#managed_target_box_'+campaign_id).val(original_managed_target_val);
						}
						
						//show pending flag and add campaign back to list of "to load" campaigns
						show_pending_warning_for_campaign(campaign_id);
					}
					else
					{
						set_message_timeout_and_show(data.errors, 'alert alert-error', 16000);
					}
				},
				error: function(xhr, textStatus, error){
					$(element).prop('disabled', false);
					set_message_timeout_and_show('Error 510957: unable to update pc adgroup data for campaign', 'alert alert-error', 16000);
				}
			});
			return;
		}
	}
	set_message_timeout_and_show('Warning 422945: invalid managed target value: '+raw_managed_target, 16000);
}

//parameters:
//	campaign_object - the current campaign row to update
//	current_index - the index of the campaign within the datatables object
//	single_update_flag - flag for a single update from the force pending button (not part of load queue)
function bidder_ajax_for_campaign(campaign_object, current_index, single_update_flag)
{
	if(single_update_flag === false && c_bidder_loaded_flag === true)
	{
		//bidder ajax called while all campaigns are loaded
		return;
	}
	var campaign_id = campaign_object._aData.id;
	var managed_target_box = $("#managed_target_box_"+campaign_id);
	var miniform = managed_target_box.parent('div.c_miniform_div');
	$.ajax({
		type: "POST",
		url: "/campaigns_main/get_tradedesk_data_for_campaign",
		async: true,
		data: {campaign: campaign_id},
		dataType: 'json',
		success: function(data, textStatus, jqXHR){ 
			var highlight_checkbox_checked = $('#highlight_row_checkbox_'+campaign_id).prop('checked');
			var bidder_cell_data = campaign_table.fnGetData(current_index, 22);

			if(data.is_success === true)
			{
				bidder_cell_data = change_miniform_cell_data_display(bidder_cell_data, 'show_miniform');
				campaign_table.fnUpdate(bidder_cell_data, current_index, 22, 0, 0);
				if(single_update_flag === true)
				{
					$("#c_pending_div_"+campaign_id+"").show();
					$("#c_pending_div_"+campaign_id+"").attr('data-fq-pending-flag', 'true');
				}
				if(data.data.check_action_date)
				{
					$("#action_date_button_"+campaign_id+"").hide();
					$("#action_date_button_"+campaign_id+"").prop('disabled', true);
				}
				display_pc_adgroup_warnings(campaign_id, data.data);
				var managed_target_box = $("#managed_target_box_"+campaign_id);
				var miniform = managed_target_box.parent('div.c_miniform_div');
				miniform.children().prop('disabled', false);
			}
			else
			{
				bidder_cell_data = change_miniform_cell_data_display(bidder_cell_data, 'show_no_bidder');
				campaign_table.fnUpdate(bidder_cell_data, current_index, 22, 0, 0);
				if(single_update_flag === true)
				{
					$("#c_pending_div_"+campaign_id+"").show();
					$("#c_pending_div_"+campaign_id+"").attr('data-fq-pending-flag', 'true');
				}
				if(data.data.check_action_date)
				{
					$("#action_date_button_"+campaign_id+"").hide();
					$("#action_date_button_"+campaign_id+"").prop('disabled', true);
				}
			}

			if(highlight_checkbox_checked !== undefined)
			{
				$('#highlight_row_checkbox_'+campaign_id).prop('checked', highlight_checkbox_checked);
			}

			$('.popover_'+campaign_id).popover();

			c_datatables_data[current_index]._aData.ttd_loaded = true;
			if(single_update_flag === false)
			{
				get_next_campaign_to_load();
			}
		},
		error: function(xhr, textStatus, error){
			set_message_timeout_and_show('Error 510658: unable to retrieve bidder campaign data', 'alert alert-error', 16000);
		}
	});
}

//find and replace the classes on specific elements in the cell_data string to update the datatables cell with new data.  
//We can't just interact with the dom here because the specific cell might not be currently in the dom.
function change_miniform_cell_data_display(cell_data, action)
{
	var new_cell_data = cell_data;
	if (new_cell_data != undefined && new_cell_data != '' &&  typeof new_cell_data === "string")
	{
		if(action === "show_miniform")
		{
			new_cell_data = new_cell_data.replace(/c_miniform_div c_miniform_hide"/, 'c_miniform_div"');
			new_cell_data = new_cell_data.replace(/c_miniform_loader_icon"/, 'c_miniform_loader_icon c_miniform_hide"');
			new_cell_data = new_cell_data.replace(/c_no_bidder_icon"/, 'c_no_bidder_icon c_miniform_hide"');
		}
		else if(action === "show_loader")
		{
			new_cell_data = new_cell_data.replace(/c_miniform_div"/, 'c_miniform_div c_miniform_hide"');
			new_cell_data = new_cell_data.replace(/c_miniform_loader_icon c_miniform_hide"/, 'c_miniform_loader_icon"');
			new_cell_data = new_cell_data.replace(/c_no_bidder_icon"/, 'c_no_bidder_icon c_miniform_hide"');
		}
		else if(action === "show_no_bidder")
		{
			new_cell_data = new_cell_data.replace(/c_miniform_div"/, 'c_miniform_div c_miniform_hide"');
			new_cell_data = new_cell_data.replace(/c_miniform_loader_icon"/, 'c_miniform_loader_icon c_miniform_hide"');
			new_cell_data = new_cell_data.replace(/c_no_bidder_icon c_miniform_hide"/, 'c_no_bidder_icon"');
		}
	}
	return new_cell_data;
}

function display_pc_adgroup_warnings(campaign_id, data)
{
	$('.c_bid_warning_icon_down_'+campaign_id+', .c_bid_warning_icon_up_'+campaign_id).css('visibility', 'hidden');
	//for checking if pc adgroup impressions are less than 5% of total
	var pc_weight_threshold = .05;
	//for checking if realized impressions was less than 90% of target impressions
	var pc_target_underdelivery_threshold = .9;
	var managed_target_box = $("#managed_target_box_"+campaign_id);
	var miniform = managed_target_box.parent('div.c_miniform_div');

	var new_cycle_target = managed_target_box.val();
	var non_pc_realized_impressions = managed_target_box.attr('data-original-non-pc-realized');
	if(typeof data.pc_daily_impression_budget !== "undefined" && 
	   typeof data.pc_daily_impression_realized !== "undefined" && 
	   typeof data.non_pc_target_impressions !== "undefined" &&
	   data.pc_daily_impression_budget !== null && 
	   data.pc_daily_impression_realized !== null &&
	   typeof non_pc_realized_impressions !== "undefined")
	{
		var is_pending = false;
		var pending_div = $("#c_pending_div_"+campaign_id);
		if(pending_div.css('display') !== 'none' && pending_div.attr('data-fq-pending-flag') == 'true')
		{
			is_pending = true;
		}
		var new_pc_target = new_cycle_target - non_pc_realized_impressions;
		var pc_adgroup_adjustment = new_pc_target - data.pc_daily_impression_realized;
		
		var old_pc_impressions = Math.round(data.pc_daily_impression_budget);
		var old_total_impressions = Math.round(data.total_target);
		var new_pc_impressions = Math.round(new_pc_target);
		var new_total_impressions = Math.round(new_cycle_target);
	
		if(new_pc_target < (pc_weight_threshold * new_total_impressions))
		{
			$('.c_bid_warning_icon_down_'+campaign_id).css('visibility', 'visible');
		}

		//pc realized is below the underdelivery threshold and campaign is not pending and the suggested is a positive change
		if(parseInt(data.pc_daily_impression_realized, 10) < (old_pc_impressions * pc_target_underdelivery_threshold) && 
		   is_pending === false &&
		   old_pc_impressions < new_pc_impressions)
		{
			$('.c_bid_warning_icon_up_'+campaign_id).css('visibility', 'visible');
		}
		
		if(old_total_impressions > 0)
		{
			//multiply by 100 to one decimal place (if it exists)
			var old_pc_percent = Math.round((old_pc_impressions / old_total_impressions) * 1000) / 10;
		}
		else
		{
			var old_pc_percent = 0;
		}

		if(new_total_impressions > 0)
		{
			var new_pc_percent = Math.round((new_pc_impressions / new_total_impressions) * 1000) / 10;
		}
		else
		{
			var new_pc_percent = 0;
		}

		//old format for content_text
		/*
		var content_text = '<div style="text-decoration:underline;">Currently</div>'+
			'<div>PC: '+old_pc_percent+'% = '+numberWithCommas(old_pc_impressions)+' of '+numberWithCommas(old_total_impressions)+'</div><br />'+
			'<div style="text-decoration:underline;">Suggested</div>'+
			'<div>PC: '+new_pc_percent+'% = '+numberWithCommas(new_pc_impressions)+' of '+numberWithCommas(new_total_impressions)+'</div>';
		*/

		var content_text = '<table class="c_popover_miniform_table" style="text-align:center;"><tr><th>&nbsp;</th><th>Currently</th><th>Suggested</th></tr>'+
			'<tr><td>PC%</td><td>'+old_pc_percent+'</td><td>'+new_pc_percent+'</td></tr>'+
			'<tr><td>PC</td><td>'+numberWithCommas(old_pc_impressions)+'</td><td>'+numberWithCommas(new_pc_impressions)+'</td></tr>'+
			'</table>';
		//add this to the table above for a total
		//'<tr><td>Total</td><td>'+numberWithCommas(old_total_impressions)+'</td><td>'+numberWithCommas(new_total_impressions)+'</td></tr>'+
		miniform.popover({
			placement: 'top',
			title: 'Update PC AdGroup',
			content: content_text,
			trigger: 'hover',
			html: true
		});
		return true;
	}
	else
	{
		return false;
	}
}

function show_pending_warning_for_campaign(campaign_id)
{
	$.each(c_items_to_load, function(index){
		if(this._aData.id == campaign_id)
		{
			if($.inArray(index, c_display_order) < 0)
			{
				c_display_order.unshift(index);
				c_display_master.unshift(index);
				var single_update_flag = true;
				bidder_ajax_for_campaign(this, index, single_update_flag);
			}
			return false; // break loop
		}
	});
}

//parameters:
//	settings - datatables settings object
//	is_first_load - boolean for if it is the initial load or not
function update_items_to_load_with_current_view(settings, is_first_load)
{
	c_raw_to_load_settings = $.extend(true, {}, settings); //copy of settings (by value, not reference)
	c_datatables_data = settings.aoData;
	
	if(is_first_load === true)
	{
		//setting objects that are copies of datatables object
		c_items_to_load = c_raw_to_load_settings.aoData;
		c_display_order = c_raw_to_load_settings.aiDisplay;
		c_display_master = c_raw_to_load_settings.aiDisplayMaster;
		
		c_raw_to_load_settings = {}; //empty so we don't reset global settings variables later
		bidder_ajax_for_campaign(c_items_to_load[c_display_order[0]], c_display_order[0], false);
	}
	else //set global display flag so we update the global settings variables later
	{
		if(update_icon_and_check_all_visible_checkboxes_checked() === true)
		{
			$('.c_control_checkbox').prop('checked', true);
		}
		else
		{
			$('.c_control_checkbox').prop('checked', false);
		}
		c_is_new_display_flag = true;
		
	}
}

function get_next_campaign_to_load()
{
	if(c_is_new_display_flag === false)
	{
		var temp_removed_index = c_display_order[0];
		var in_master_array = $.inArray(temp_removed_index, c_display_master);
		c_display_order.shift();
		
		if(in_master_array > -1)
		{
			//found match in master array of all elements
			c_display_master.splice(in_master_array, 1);
		}
	}
	else if(!$.isEmptyObject(c_raw_to_load_settings))
	{
		//while the last campaign was loading a new view is queued up.
		c_items_to_load = c_raw_to_load_settings.aoData;
		c_display_order = c_raw_to_load_settings.aiDisplay;
	}
	c_is_new_display_flag = false;
	
	if(c_display_order.length === 0)
	{
		if(c_display_master.length > 0)
		{
			//we're done with what is currently displayed, move on to the rest of the campaigns
			c_display_order = c_display_master;
		}
		else
		{
			//all campaigns loaded successfully
			c_bidder_loaded_flag = true;
			set_message_timeout_and_show('Bidder data loaded successfully for all campaigns', 'alert alert-info', 32000);
			return;
		}
	}
	
	var temp_new_index = c_display_order[0];
	if(typeof c_datatables_data[temp_new_index]._aData.ttd_loaded === "undefined" || c_datatables_data[temp_new_index]._aData.ttd_loaded === true)
	{
		get_next_campaign_to_load();
	}
	else
	{
		bidder_ajax_for_campaign(c_items_to_load[temp_new_index], temp_new_index, false);
	}
	
}

function highlight_all_campaign_rows(obj)
{	
	$('.c_campaign_row_checkbox').prop('checked', obj.prop("checked"));
	var all_visible_campaigns = $(".c_campaign_row_checkbox").map(function(){return $(this).attr("data-c-campaign-id");}).get();
	if(obj.prop("checked") === true)
	{
		$.each(all_visible_campaigns, function(key, value) {
			add_campaign_id_to_array_if_not_exist(value);
		});
		$('#campaign_table > tbody > tr').addClass("row_highlighted");
	}
	else
	{
		$.each(all_visible_campaigns, function(key, value) {
			remove_campaign_id_from_array_if_exist(value);
		});
		$('#campaign_table > tbody > tr').removeClass("row_highlighted");
	}
}

function add_campaign_id_to_array_if_not_exist(campaign_id)
{
	if(typeof campaign_id !== "undefined" && !isNaN(parseInt(campaign_id)))
	{
		campaign_id = ""+campaign_id;
		var temp_campaign_index = c_bulk_adgroup_campaign_ids.indexOf(campaign_id);
		if(temp_campaign_index === -1)
		{
			c_bulk_adgroup_campaign_ids.push(campaign_id);
			c_bulk_archive_campaign_data[campaign_id] = {
				campaign_name: $('#campaign_'+campaign_id+' > strong > a').html(),
				yday_impressions: $('#c_yday_impressions_text_'+campaign_id).html()
			};
			var total_count = c_bulk_adgroup_campaign_ids.length;
			update_bulk_hidden_icon_number(total_count);
		}
	}
}

function remove_campaign_id_from_array_if_exist(campaign_id)
{
	if(typeof campaign_id !== "undefined" && !isNaN(parseInt(campaign_id)))
	{
		campaign_id = ""+campaign_id;
		var temp_campaign_index = c_bulk_adgroup_campaign_ids.indexOf(campaign_id);
		if(temp_campaign_index > -1)
		{
			c_bulk_adgroup_campaign_ids.splice(temp_campaign_index, 1);
			c_bulk_archive_campaign_data[campaign_id].campaign_name = undefined;
			c_bulk_archive_campaign_data[campaign_id].yday_impressions = undefined;
			var total_count = c_bulk_adgroup_campaign_ids.length;
			update_bulk_hidden_icon_number(total_count);
		}
	}
}

function submit_bap_campaigns()
{
	$('#bap_campaign_ids_input').val(JSON.stringify(c_bulk_adgroup_campaign_ids));
	$('#bap_submit_form').submit();
}

function submit_bap_timeseries()
{
	$('#bap_ts_campaign_ids_input').val(JSON.stringify(c_bulk_adgroup_campaign_ids));
	$('#bap_ts_submit_form').submit();
}

function archive_all_bap_campaigns()
{
	if(c_bulk_adgroup_campaign_ids.length > 0)
	{
		var campaigns_to_delete = c_bulk_adgroup_campaign_ids.slice(); //copy array by value
		$.ajax({
			type: "POST",
			url: "/campaigns_main/ajax_archive_selected_campaigns",
			async: true,
			data: {campaigns: c_bulk_adgroup_campaign_ids},
			dataType: 'json',
			success: function(data, textStatus, jqXHR){
				if(typeof data.is_success !== "undefined" && typeof data.errors !== "undefined")
				{
					if(data.is_success === true)
					{
						$.each(campaigns_to_delete, function(index, value){
							remove_campaign_id_from_array_if_exist(value);
							delete_row($('#highlight_row_checkbox_'+value));
						});
						set_message_timeout_and_show('Campaigns archived successfully', 'alert alert-success', 10000);
					}
					else
					{
						set_message_timeout_and_show(data.errors, 'alert alert-error', 16000);
					}
				}
				else
				{
					set_message_timeout_and_show('Error 905639: Server Error when archiving campaigns', 'alert alert-error', 32000);
				}
			},
			error: function(xhr, textStatus, error){
				set_message_timeout_and_show('Error 905524: Server Error when archiving campaigns', 'alert alert-error', 32000);
			}
		});
	}
	else
	{
		set_message_timeout_and_show('No campaigns selected for bulk archive', 'alert alert-warning', 16000);
	}
}
function update_icon_and_check_all_visible_checkboxes_checked()
{
	var total_count = c_bulk_adgroup_campaign_ids.length;
	var hidden_count = total_count;
	var all_checked = true;
	$('input.c_campaign_row_checkbox').each(function(index, value){
		if(!$(value).prop('checked'))
		{
			all_checked = false;
		}
		else
		{
			hidden_count--;
		}
	});
	
	if(hidden_count > 0)
	{
		$('#bulk_hidden_icon_container').addClass('bulk_hidden_alert');
	}
	else
	{
		$('#bulk_hidden_icon_container').removeClass('bulk_hidden_alert');
	}
	update_bulk_hidden_icon_number(total_count);
	
	return all_checked;
}

function set_bulk_archive_modal_content()
{
	var content_html = "";
	$.each(c_bulk_adgroup_campaign_ids, function(key, value) {
		var temp_campaign_data = c_bulk_archive_campaign_data[value];
		if(temp_campaign_data.campaign_name !== undefined && temp_campaign_data.campaign_name !== undefined)
		{
			var background_style = "";
			var yday_html = "";
			if(temp_campaign_data.yday_impressions !== "0k")
			{
				background_style = "background-color: #ff9393;";
				yday_html = " ("+temp_campaign_data.yday_impressions+")";
			}
			content_html += '<div style="'+background_style+'">'+temp_campaign_data.campaign_name+''+yday_html+'</div>';
		}
	});
	$('#bulk_archive_modal_campaigns').html(content_html);
}

$("#ct_campaigns_main_button").click(function(event){
	event.preventDefault();
	
	if(!$.isEmptyObject(ct_modified_tags))
	{
		var is_modified = false;
		$.each(ct_modified_tags, function(key, value){
			if(value.is_modified === true)
			{
				is_modified = true;
				return false;
			}
		});
	}
	$('#campaigns_main_page_container').fadeIn(300);
	$('body div.fixedHeader').fadeIn(300);
	$('#campaign_tags_page_container').hide();
	fixed_header._fnUpdateClones(true);
	fixed_header._fnUpdatePositions();
	if(is_modified === true)
	{
		set_message_timeout_and_show('This custom campaigns main view is out of date!', 'alert alert-warning', 128000);
	}
});

function get_campaign_tag_data_and_display()
{
	$('#campaigns_main_page_container').hide();
	$('body div.fixedHeader').hide();
	$('#c_main_loading_img').show();
	$.ajax({
		type: "POST",
		url: '/campaign_tags/ajax_get_campaign_tag_data_for_selected_campaigns',
		async: true,
		data: {selected_campaigns: c_bulk_adgroup_campaign_ids},
		dataType: 'json',
		success: function(data, textStatus, jqXHR){
			$('#c_main_loading_img').hide();
			if(typeof data.is_success !== "undefined" || typeof data.errors !== "undefined" || typeof data.campaigns !== undefined)
			{
				if(data.is_success === true)
				{
					initialize_campaign_tags_table(data.campaigns);
					$('#campaign_tags_page_container').fadeIn(300);
				}
				else
				{
					$('#campaigns_main_page_container').fadeIn(300);
					$('body div.fixedHeader').fadeIn(300);
					set_message_timeout_and_show(data.errors, 'alert alert-error', 16000);
				}
			}
			else
			{
				$('#campaigns_main_page_container').fadeIn(300);
				$('body div.fixedHeader').fadeIn(300);
				set_message_timeout_and_show("Error 791406: invalid response from server", 'alert alert-error', 16000);
			}
		},
		error: function(xhr, textStatus, error){
			$('#c_main_loading_img').hide();
			if(xhr.getAllResponseHeaders())
			{
				$('#campaigns_main_page_container').fadeIn(300);
				$('body div.fixedHeader').fadeIn(300);
				set_message_timeout_and_show("Error 791404: Server Error when getting campaign tags data", 'alert alert-error', 16000);
			}
		}
	});
}

function initialize_campaign_tags_table(campaigns)
{
	var tags_table = $('#ct_campaign_tags_table');
	var tags_html = "";
	tags_table.html(tags_html);
	$.each(campaigns, function(key, value){
		var select2_id = 'ct_table_select2_'+value.campaign_id;
		tags_html = '<tr class="row_highlighted">';
		tags_html += '<td class="ct_checkbox_td"> <input type="checkbox" checked="checked" class="ct_campaign_tags_checkbox" data-ct-campaign-id="'+value.campaign_id+'"> </td>';
		tags_html += '<td class="ct_campaign_name_td"> <small>'+value.partner_name+' - '+value.sales_person+'</small>'+
			'<br>'+
			'<strong><a href="/campaign_setup/'+value.campaign_id+'" target="_blank">'+value.advertiser_name+' - '+value.campaign_name+'</a></strong> </td>';
		tags_html += '<td class="ct_tag_list_td">'+
			'<input type="hidden" id="'+select2_id+'" class="ct_campaign_tags_select" style="width:100%;" data-ct-campaign-id="'+value.campaign_id+'"></td></tr>';
		tags_table.append(tags_html);
		$('#'+select2_id).select2({
			multiple: true,
			tags:true,
			placeholder: "select tags",
			allowClear: true,
			minimumInputLength: 0,
			ajax:{
				type: 'POST',
				url: '/campaign_tags/ajax_get_campaign_tags/',
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
			}
		}).on("change", function(e){
			var data_url = "";
			var data_obj = {};
			var target_campaign = $(e.target).attr('data-ct-campaign-id');
			var selected_campaigns = [target_campaign];
			
			if(typeof e.added !== "undefined")
			{
				if(ct_modified_tags.hasOwnProperty(e.added.id))
				{
					ct_modified_tags[e.added.id]['is_modified'] = true;
				}
				
				data_url = '/campaign_tags/ajax_add_campaign_tag_to_campaign';
				data_obj = {tag_id: e.added.id, selected_campaigns: selected_campaigns};
			}
			if(typeof e.removed !== "undefined")
			{
				if(ct_modified_tags.hasOwnProperty(e.removed.id))
				{
					ct_modified_tags[e.removed.id]['is_modified'] = true;
				}
				
				data_url = '/campaign_tags/ajax_remove_campaign_tag_from_campaign';
				data_obj = {tag_id: e.removed.id, selected_campaigns: selected_campaigns};
			}
			if(data_url !== "")
			{
				$.ajax({
					type: "POST",
					url: data_url,
					async: true,
					data: data_obj,
					dataType: 'json',
					success: function(data, textStatus, jqXHR){
						if(data.is_success === false)
						{
							set_message_timeout_and_show(data.errors, 'alert alert-error', 16000);
						}
					},
					error: function(xhr, textStatus, error){
						if(xhr.getAllResponseHeaders())
						{
							set_message_timeout_and_show("Error 791299: server error adding tag to campaign", 'alert alert-error', 16000);
						}
					}
				});
			}
		}).on("select2-selecting", function(e){
			if(e.choice.id === "ct_placeholder_id")
			{
				var target_campaign = $(e.target).attr('data-ct-campaign-id');
				var selected_campaigns = [target_campaign];
				$.ajax({
					type: "POST",
					url: '/campaign_tags/ajax_create_new_campaign_tag',
					async: false,
					data: {tag_name: e.choice.ct_original_text, selected_campaigns: selected_campaigns},
					dataType: 'json',
					success: function(data, textStatus, jqXHR){
						if(data.tag_id !== false)
						{
							e.choice.id = data.tag_id;
							e.choice.text = e.choice.ct_original_text;
						}
						if(data.is_success === false)
						{
							set_message_timeout_and_show(data.errors, 'alert alert-error', 16000);
						}
					},
					error: function(xhr, textStatus, error){
						if(xhr.getAllResponseHeaders())
						{
							set_message_timeout_and_show("Error 791399: server error creating new tag "+e.choice.ct_original_text, 'alert alert-error', 16000);
						}
					}
				});
			}
		});
		$('#'+select2_id).select2('data', value.campaign_tags);
	});
}

$(document).on("click", 'td.ct_checkbox_td > input', function(){
	
	$(this).parent().parent().toggleClass("row_highlighted");
});

$('#ct_bulk_edit_tags_button').click(function(event){
	var checked_campaigns = $(".ct_campaign_tags_checkbox:checked").map(function(){return $(this).attr("data-ct-campaign-id");}).get();
	if(checked_campaigns.length === 0)
	{
		event.preventDefault();
	}
	else
	{
		$.ajax({
			type: "POST",
			url: '/campaign_tags/ajax_get_common_tags_by_campaign_ids',
			async: true,
			data: {campaign_ids: checked_campaigns},
			dataType: 'json',
			success: function(data, textStatus, jqXHR){
				if(data.is_success === true)
				{
					init_bulk_edit_campaign_tags_select(data.tags, checked_campaigns);
				}
				else
				{
					set_message_timeout_and_show(data.errors, 'alert alert-error', 16000);
				}
			},
			error: function(xhr, textStatus, error){
				if(xhr.getAllResponseHeaders())
				{
					set_message_timeout_and_show("Error 771099: unable to get tag data for campaigns", 'alert alert-error', 16000);
				}
			}
		});
	}
});

function init_bulk_edit_campaign_tags_select(tags)
{
	$('#ct_bulk_edit_campaign_tags_select2').select2('destroy');
	$('#ct_bulk_edit_campaign_tags_select2').off('change');
	$('#ct_bulk_edit_campaign_tags_select2').off('select2-selecting');
	
	$('#ct_bulk_edit_campaign_tags_select2').select2({
		multiple: true,
		tags:true,
		placeholder: "select tags",
		allowClear: true,
		minimumInputLength: 0,
		ajax:{
			type: 'POST',
			url: '/campaign_tags/ajax_get_campaign_tags/',
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
		}
	}).on("change", function(e){
		on_select2_campaign_tag_change(e);
	}).on("select2-selecting", function(e){
		if(e.choice.id === "ct_placeholder_id")
		{
			var target_campaign = $(e.target).attr('data-ct-campaign-id');
			var selected_campaigns = "";
			$.ajax({
				type: "POST",
				url: '/campaign_tags/ajax_create_new_campaign_tag',
				async: false,
				data: {tag_name: e.choice.ct_original_text, selected_campaigns: selected_campaigns},
				dataType: 'json',
				success: function(data, textStatus, jqXHR){
					if(data.tag_id !== false)
					{
						e.choice.id = data.tag_id;
						e.choice.text = e.choice.ct_original_text;
					}
					if(data.is_success === false)
					{
						set_message_timeout_and_show(data.errors, 'alert alert-error', 16000);
					}
				},
				error: function(xhr, textStatus, error){
					if(xhr.getAllResponseHeaders())
					{
						set_message_timeout_and_show("Error 791399: server error creating new tag"+e.choice.ct_original_text, 'alert alert-error', 16000);
					}
				}
			});
		}
	});
	$('#ct_bulk_edit_campaign_tags_select2').select2('data', tags);
}

function on_select2_campaign_tag_change(e)
{
	var selected_campaigns = $(".ct_campaign_tags_checkbox:checked").map(function(){return $(this).attr("data-ct-campaign-id");}).get();
	if(selected_campaigns.length === 0)
	{
		$('#bulk_campaign_tags_modal').modal('hide');
		set_message_timeout_and_show("Warning 799399: No campaigns selected for bulk tags");
		return;
	}
	var data_url = "";
	var data_obj = {};
	var select2_action = "none";
	if(typeof e.added !== "undefined")
	{
		if(ct_modified_tags.hasOwnProperty(e.added.id))
		{
			ct_modified_tags[e.added.id]['is_modified'] = true;
		}
		
		select2_action = "added";
		data_url = '/campaign_tags/ajax_add_campaign_tag_to_campaign';
		data_obj = {tag_id: e.added.id, selected_campaigns: selected_campaigns};
	}
	else if(typeof e.removed !== "undefined")
	{
		if(ct_modified_tags.hasOwnProperty(e.removed.id))
		{
			ct_modified_tags[e.removed.id]['is_modified'] = true;
		}
	
		select2_action = "removed";
		data_url = '/campaign_tags/ajax_remove_campaign_tag_from_campaign';
		data_obj = {tag_id: e.removed.id, selected_campaigns: selected_campaigns};
	}
	
	if(select2_action !== "none")
	{
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: data_obj,
			dataType: 'json',
			success: function(data, textStatus, jqXHR){
				if(data.is_success !== false)
				{
					if(select2_action === "added")
					{
						$.each(selected_campaigns, function(key, value){
							var select2_object = $("#ct_table_select2_"+value);
							var select2_data = select2_object.select2('data');
							select2_data.push(e.added);
							select2_object.select2('data', select2_data);
						});
					}
					else if(select2_action === "removed")
					{
						$.each(selected_campaigns, function(key, value){
							var select2_object = $("#ct_table_select2_"+value);
							var select2_data = $.grep(select2_object.select2('data'), function(value){
								return value['id'] != e.removed.id;
							});
							select2_object.select2('data', select2_data);
							
						});
					}
				}
				else
				{
					$('#bulk_campaign_tags_modal').modal('hide');
					set_message_timeout_and_show(data.errors, 'alert alert-error', 16000);
				}
			},
			error: function(xhr, textStatus, error){
				if(xhr.getAllResponseHeaders())
				{
					$('#bulk_campaign_tags_modal').modal('hide');
					set_message_timeout_and_show("Error 791299: server error adding tag to campaign", 'alert alert-error', 32000);
				}
			}
		});
	}
}

$('.ct_tag_utility_button').click(function(){
	$('#tag_utility_body_content').hide();
	$('#tag_utility_loader_content').show();
	if(typeof campaign_tags_table !== "undefined" &&
	   typeof campaign_tags_table.fnDestroy() !== "undefined" &&
	   $.isFunction(campaign_tags_table.fnDestroy()))
	{
		campaign_tags_table.fnDestroy();
	}

	$.ajax({
			type: "POST",
			url: "/campaign_tags/ajax_get_tag_utility_data",
			async: true,
			data: {},
			dataType: 'json',
			success: function(data, textStatus, jqXHR){
				if(data.is_success === true && typeof data.tag_data !== "undefined")
				{
					build_tag_utility_html_for_tag_utility_modal(data.tag_data);
					
					$('#tag_utility_loader_content').hide();
					$('#tag_utility_body_content').show();
				}
				else if(typeof data.errors !== "undefined")
				{
					$('#tag_utility_modal').modal('hide');
					set_message_timeout_and_show(data.errors, 'alert alert-error', 16000);
				}
				else
				{
					$('#tag_utility_modal').modal('hide');
					set_message_timeout_and_show("Error 462111: server error getting campaign tag data", 'alert alert-error', 16000);
				}
			},
			error: function(xhr, textStatus, error){
				if(xhr.getAllResponseHeaders())
				{
					$('#tag_utility_modal').modal('hide');
					set_message_timeout_and_show("Error 462012: server error getting campaign tag data", 'alert alert-error', 16000);
				}
			}
		});
});

function build_tag_utility_html_for_tag_utility_modal(tag_data)
{
	var tags_table_body = $('#campaign_tags_table > tbody');
	tags_table_body.html('');
	$.each(tag_data, function(index, value){
		tags_table_body.append(create_tag_table_row(value));
	});
	campaign_tags_table = $('#campaign_tags_table').dataTable({
		"ordering": true,
		"order": [[4, "desc"]],
		"paging": true,
		"info": false,
		"lengthMenu": [
			[25, 50, 100, -1],
			[25, 50, 100, 'All']
		]
	});
}
function create_tag_table_row(tag_object)
{
	var tag_string = '<tr>';
	tag_string += '<td>'+tag_object.tag_id+'</td>';
	tag_string += '<td><a href="/campaigns_main/tags/'+tag_object.tag_id+'" target="_blank">'+tag_object.tag_name+'</a></td>';
	tag_string += '<td>'+tag_object.live_campaigns+'</td>';
	tag_string += '<td>'+tag_object.ignored_campaigns+'</td>';
	tag_string += '<td>'+tag_object.last_updated+'</td>';
	tag_string += '<td>'+tag_object.username+'</td>';
	tag_string += '</tr>';
	return tag_string;
}
</script>
