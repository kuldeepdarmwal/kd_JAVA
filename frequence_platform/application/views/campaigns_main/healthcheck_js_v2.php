<script type="text/javascript" src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.js"></script>
<script src="/js/campaign_health/jquery.notification.js" type="text/javascript"></script>
<script src="/js/campaign_health/xdate.js" type="text/javascript"></script>
<script src="/js/campaign_health/campaign_health_highcharts.js" type="text/javascript"></script>
<script src="/libraries/external/select2-3.5.2/select2.js"></script>	
<script src="/js/highchart/js/highcharts.js"></script>
<script src="/js/highchart/js/modules/exporting.js"></script>
<script src="/bootstrap/assets/js/bootstrap-tooltip.js"></script>
<script type="text/javascript" src="/js/campaign_health/bootstrap-datepicker.js"></script>
<script src="/libraries/external/DataTables-1.10.2/extensions/FixedHeader/js/dataTables.fixedHeader.js"></script>
<script src="/js/campaign_health/notes.js"></script>
<style>
    .reminder_check{
	display:block;
	cursor: pointer;
    }
    .star-enable{
	background: url("<?php echo '/assets/img/star-enable.png'?>");
	height:20px;
	width:20px;
	display: inline-block;
	text-indent:-1000px;
    }
    .star-disable{
	background: url("<?php echo '/assets/img/star-disable.png'?>");
	height:20px;
	width:20px;
	display: inline-block;
	text-indent:-1000px;
    }
</style>
<script type="text/javascript">
var c_timeout_id;
var bulk_date = new Date();
bulk_date.setDate(bulk_date.getDate()-1);
var bulk_day = bulk_date.getDate();
var bulk_month = bulk_date.getMonth()+1;
var bulk_enddate = ((''+bulk_month).length<2 ? '0' : '') + (bulk_month) + '/' + ((''+bulk_day).length<2 ? '0' : '') + bulk_day + '/' +  bulk_date.getFullYear();

bulk_date = new Date(new Date(bulk_date).setMonth(bulk_date.getMonth()-1));
var bulk_day = bulk_date.getDate();
var bulk_month = bulk_date.getMonth()+1;
var bulk_startdate = ((''+bulk_month).length<2 ? '0' : '') + (bulk_month) + '/' + ((''+bulk_day).length<2 ? '0' : '') + bulk_day + '/' +  bulk_date.getFullYear();

var campaign_table;
var fixed_header;
var last_campaign_checked = false;

var campaign_table_new;
var selected_row;
var row_data;

$(function() {
	$("#bulk_download_start_date").val(bulk_startdate);
	$("#bulk_download_end_date").val(bulk_enddate);

	$(".bulk_download_datepicker").datepicker().on('changeDate', function(event) {
		var temp_selected_sales_person = $("#bd_sales_person_select").val();
		$("#bd_partner_select").trigger("change", [temp_selected_sales_person]);
	});
	
	$("#bd_sales_person_select").select2();
	$("#bd_partner_select").select2();

	$(".bulk_download_datepicker").change(function(){
		var temp_selected_sales_person = $("#bd_sales_person_select").val();
		$("#bd_partner_select").trigger("change", [temp_selected_sales_person]);
	});

	$("#bd_ae_tooltip").tooltip({
		placement: 'top',
		title: '<strong>Account Executives are only visible if they have campaigns within the selected date range</strong>',
		html: true,
		trigger: 'hover'
	});
});

$("#bulk_download_button").click(function(){
	$('.bulk_download_content_div').hide();
	$('#bulk_download_loader_content').show();
	check_pending_and_submit_bulk_campaign_request(false);
	$("#bd_partner_select").trigger("change");
});

function format_table_data(data, is_same_sales, is_same_partner)
{
	var report_date = "<?php echo $report_date; ?>";
	var one_month_ago = "<?php echo $one_month_ago; ?>";
	$.each(data, function(key, value){
		this.formatted_checkbox = '<input id="highlight_row_checkbox_'+this.id+'" class="c_campaign_row_checkbox" type="checkbox" data-c-campaign-id="'+this.id+'">';
		this.cycle_end = this.next_reset;
		this.cycle_target = this.cycle_target_to_next_bill_date;
		this.advertiserElem = this.advertiser;
		var rtg_str = "";
		var campaign_class = "";
		if(this.is_retargeting == 1 && this.rtg_weight < 0.01) //rtg alert
		{
			rtg_str = ' <span class="popover_'+this.id+' tool_poppers label label-warning" data-toggle="popover" data-trigger="hover" data-placement="right" data-original-title="Retargeting Alert" data-content="Retargeting is enabled but minimal impressions are firing. Potential problem: small cookie audience. Please check advertiser page for cookie."><i class="icon-warning-sign icon-white"></i></span>';
			campaign_class = 'class="c_campaign_text pull-left"';
		}

		var partner_sales_name = "";
		if(is_same_partner !== 1)
		{
			partner_sales_name += this.partner;
		}

		if(is_same_sales !== 1)
		{
			if(partner_sales_name !== "")
			{
				partner_sales_name += " - ";
			}
			partner_sales_name += this.sales_person;
		}

		if(partner_sales_name !== "")
		{
			partner_sales_name = "<small>" + partner_sales_name + "</small><br>";
		}
<?php		
		if ($role == 'sales')
		{
?>			this.order_id_obj = {
				formatted_order_id: this.order_id_obj,
				order_id: this.order_id_obj
			};
<?php		}
?>
<?php if($role == 'admin' or $role == 'ops') { ?>

                // For third party ids
                third_party_ids = "fid: " + this.f_id;
                if (this.ttd_adv_id !== null)
                {
                    third_party_ids += " | ttdid: " + this.ttd_adv_id;
                }
                if (this.ul_id !== null)
                {
                    third_party_ids += " | ulid: " + this.ul_id;
                }
                if (this.eclipse_id !== null)
                {
                    third_party_ids += " | eclipseid: " + this.eclipse_id;
                }
                if (this.tmpi_ids !== null)
                {
                    third_party_ids += " | tmpi: " + this.tmpi_ids;
                }
		if (this.order_id_obj !== null && this.order_id_obj != "-")
                {
                    third_party_ids += " | partnerorderid: " + this.order_id_obj;
                }
		if (this.unique_display_id_obj !== null && this.unique_display_id_obj != "")
                {
                    third_party_ids += " | externalorderid: " + this.unique_display_id_obj;
                }
                
		var adv_campaign_name = "<a href=\"/campaign_setup/"+this.id+"\" target=\"_blank\" class=\"popover_"+this.id+" tool_poppers\" data-toggle=\"popover\" data-trigger=\"hover\" data-placement=\"right\" data-original-title=\"Third Party IDs\" data-content=\""+third_party_ids+"\">" + this.advertiserElem + " - " + this.campaign + "</a>";

<?php }else{ ?>

		var adv_campaign_name = this.advertiserElem + " - " + this.campaign;

<?php } ?>	
		if (this.is_reminder === null || this.is_reminder == 0)
		{
			reminder_status_html = "<div class='reminder_check' data-cid='"+this.id+"' data-status = '"+0+"'><span class='star-disable' title='Not Starred'>notstarred</span></div>";
			reminder_flag = 'notstarred';
		}
		else
		{
			reminder_status_html = "<div class='reminder_check' data-cid='"+this.id+"' data-status = '"+this.is_reminder+"'><span class='star-enable' title='Starred'>starred</span></div>";
			reminder_flag = 'starred';
		}
		this.is_reminder = {
			formatted_reminder:reminder_status_html,
			reminder: reminder_flag
		};
		this.campaign_obj = {
			formatted_campaign:"<div id=\"campaign_"+this.id+"\" "+campaign_class+">"+partner_sales_name+"<strong>"+adv_campaign_name+"</strong>"+rtg_str+"</div>",
			campaign: this.advertiserElem + this.campaign
		};
		this.target_obj = {
			formatted_target: format_target_impressions(this.id, this.target_impressions*1000, this.ttd_pending_flag), //format_large_data_num(this.target_impressions*1000, 1),
			target_impressions: this.target_impressions*1000
		};
//NO_TGT_IM YES_TGT_IM
		this.total_impressions_obj = {
			formatted_total_impressions: format_large_data_num(this.total_impressions, 1),
			total_impressions: this.total_impressions
		};

		this.timeseries_string_obj = {
			formatted_timeseries_string: format_timeseries_string(this.timeseries_string, this.campaign_end_date, this.id),
			timeseries_string: this.timeseries_string,
			campaign_end_date: this.campaign_end_date
		};

		this.cycle_end_date_obj = {
			formatted_cycle_end_date: format_cycle_end_date(this.cycle_end_date, this.id),
			cycle_end_date: this.cycle_end_date
		};

<?php if($role == 'sales') { ?>
							 
		this.total_oti_obj = {
			formatted_total_oti: format_total_oti(this.lifetime_oti),
			total_oti: this.lifetime_oti
		};

<?php }else if($role == 'ops' || $role == 'admin') { ?>

		this.total_oti_obj = {
			formatted_total_oti: format_oti(this.lifetime_oti, this.lifetime_remaining_impressions, this.lifetime_target_to_next_bill_date, this.campaign_end_date, "total", this.id),
			total_oti: this.lifetime_oti
		};

		this.cycle_impressions_obj = {
			formatted_cycle_impressions: format_large_data_num(this.cycle_impressions, 2),
			cycle_impressions: this.cycle_impressions
		};
		this.cycle_oti_obj = {
			formatted_cycle_oti: format_oti(this.cycle_oti, this.cycle_remaining_impressions, this.cycle_target_to_next_bill_date, this.cycle_end_date, "cycle", this.id),
			cycle_oti: this.cycle_oti
		};
		this.cycle_target_obj = {
			formatted_cycle_target: format_cycle_target(this.cycle_target, 1),
			cycle_target: this.cycle_target
		};
		this.yesterday_impressions_obj = {
			formatted_yesterday_impressions: format_yday_impressions(this.id, this.yday_impressions),
			yday_impressions: this.yday_impressions
		};
		this.days_remaining_obj = {
			formatted_days_remaining: format_days_remaining(this.days_left),
			cycle_days_remaining: this.days_left
		};				
		this.lt_target_obj = {
			formatted_lt_target: format_large_data_num(this.long_term_target, 2),
			long_term_target: this.long_term_target
		};

		var daily_oti = 0;
		var formatted_daily_oti = '<span class="label label-important">0%</span>';

		this.action_date_obj = {
			formatted_action_date: format_action_date(this.id, this.action_date),
			action_date: this.action_date
		};

		var adv_campaign_name = this.advertiserElem + " - " + this.campaign;
		this.alerts_string_obj = { 		   
			formatted_alerts_string: format_alerts_string(this.id, this.alerts_string, adv_campaign_name, this.campaign_notes_string),
			alerts_string: this.alerts_string
		};

		this.nf_oti_obj = {
			formatted_nf_oti: format_nf_oti(this.nf_oti, this.nf_oti_hover, this.id),
			nf_oti: this.nf_oti
		};

		if(this.yday_impressions == 0 && this.cycle_target <= 0)
		{
			if (this.target_impressions == undefined || this.target_impressions == "0" || this.target_impressions == "0.0000")
			{
				daily_oti = 100;
				formatted_daily_oti = '<span> 100% </span>';
			}
			else 
			{
				daily_oti = -1;
				formatted_daily_oti = '<span> - </span>';
			}
			
		}
		else if(this.cycle_target <= 0)
		{
			daily_oti = 99999999;
			formatted_daily_oti = '<span class="popover_'+this.id+' tool_poppers label label-warning" data-toggle="popover" data-trigger="hover" data-placement="right" data-original-title="Daily OTI" data-content="Cycle target is 0 or less."><strong>&infin;</strong></span>';
		}
		else 
		{
			daily_oti = this.yday_impressions/this.cycle_target;
			formatted_daily_oti = format_daily_oti(daily_oti, this.yday_impressions, this.cycle_target, this.id );
		}
		
		this.daily_oti_obj = {
			formatted_daily_oti: formatted_daily_oti,
			daily_oti: daily_oti + 1 //datatables was failing to filter on -1.  Adding 1 to every sorted value solved this.
		};

		this.ctr_obj = {
			formatted_ctr: format_percent_data_num(this.ctr, 2),
			ctr: this.ctr
		};
		this.engagement_rate_obj = {
			formatted_engagement_rate: format_percent_data_num(this.engagement_rate, 2),
			engagement_rate: this.engagement_rate
		};

		this.mini_form_value = 0;
		var non_pc_yday_impressions = 0;
		var cycle_target_multiplier = <?php echo $mini_form_multiplier; ?>;

		if(this.cycle_target > 0)
		{
			non_pc_yday_impressions = this.yday_impressions - this.pc_yday_impressions;
			this.mini_form_value = (this.cycle_target * cycle_target_multiplier).toFixed();
		}
		
		var ttd_warning_display = '';
		var ttd_pending_display_string = "true";
		if(this.ttd_pending_flag == false)
		{
			ttd_warning_display = 'style="display:none;" ';
			ttd_pending_display_string = "false";
		}
		var arrow_up_tooltip = 'data-toggle="popover" data-trigger="hover" data-placement="left" data-original-title="Inventory alert" data-content="Increasing the PC adgroup will not achieve desired effect due to lack of inventory"';
		var arrow_down_tooltip = 'data-toggle="popover" data-trigger="hover" data-placement="left" data-original-title="PC floor alert" data-content="Target will attempt to drop the PC adgroup below the foor"';

		this.formatted_ttd_warning = '<span class="popover_'+this.id+' icon-stack tool_poppers c_bid_warning_icon c_bid_warning_icon_up_'+this.id+'" '+arrow_up_tooltip+' >'+
									 '<i class="icon-minus icon-stack-base c_minus_icon_up"></i><i class="icon-arrow-up"></i></span><br />'+
									 '<span class="popover_'+this.id+' icon-stack tool_poppers c_bid_warning_icon c_bid_warning_icon_down_'+this.id+'" '+arrow_down_tooltip+' >'+
									 '<i class="icon-minus icon-stack-base c_minus_icon_down"></i><i class="icon-arrow-down"></i></span>';

		this.formatted_ttd_data = '<div '+ttd_warning_display+'id="c_pending_div_'+this.id+'" class="popover_'+this.id+' c_pending_div tool_poppers label label-warning" data-toggle="popover" data-trigger="hover" data-placement="left" data-original-title="Pending changes alert" data-content="This campaign does not have <br> sufficient data to make <br> automated buying decisions" data-html="true" data-fq-pending-flag="'+ttd_pending_display_string+'">pending</div>';
		var no_bidder_icon_display = '';

		if(this.pc_adgroup_id !== null)
		{
			this.ttd_loaded = false;
			this.formatted_ttd_data += ''+
				'<div class="input-append input-prepend c_miniform_element c_miniform_div c_miniform_hide">' +
					'<input id="managed_target_box_'+this.id+'" type="text" value="'+this.mini_form_value+'" data-original-cycle-target="'+this.mini_form_value+'" data-original-non-pc-realized="'+non_pc_yday_impressions+'">' +
					'<button onclick="update_pc_adgroup_impression_target(\''+this.pc_adgroup_id+'\', '+this.id+', this);" class="btn btn-mini btn-info">' +
						'<i class="icon-retweet icon-white"></i>' +
					'</button>' +
				'</div>'+
				'<div id="miniform_loader_'+this.id+'" class="c_miniform_element c_miniform_loader_icon">'+
					'<i class="icon-spinner icon-spin c_icon_spin"></i>'+
				'</div>';
			no_bidder_icon_display = ' c_miniform_hide';
		}

			var no_bidder_icon_tooltip = 'data-toggle="popover" data-trigger="hover" data-placement="top" data-original-title="No Bidder Data" data-content="No bidder data found for campaign" data-html="true"';
			this.formatted_ttd_data += ''+
				'<div id="no_bidder_div_'+this.id+'" class="c_miniform_element c_no_bidder_icon'+no_bidder_icon_display+'">'+
					'<i '+no_bidder_icon_tooltip+' class="popover_'+this.id+' tool_poppers icon-info-sign icon-2x"></i>'+
				'</div>';
<?php } ?>

		
		var formatted_action_items = "";
		var action_dropdown_classes = "";
<?php if($role == 'ops' || $role == 'admin') { ?>		

		if(this.ttd_campaign_id !== null)
		{
			action_dropdown_classes += " aic_dsp_campaign";
			formatted_action_items += '<li><a class="ai_dsp_campaign ai_link" href="https://desk.thetradedesk.com/campaigns/detail/'+this.ttd_campaign_id+'" target="_blank"><i class="icon-shopping-cart"></i> Go to Bidder Campaign</a></li>';
		}

<?php } ?>
		action_dropdown_classes += " aic_charts";
		formatted_action_items += '<li><a class="ai_charts ai_modal" href="#detail_modal" data-toggle="modal" onclick="show_detail_modal('+this.id+',\''+one_month_ago+'\',\''+report_date+'\',\''+escape(this.advertiserElem+'-'+this.campaign)+'\');"><i class="icon-bar-chart"></i> Show Charts</a></li>';
		
		action_dropdown_classes += " aic_landing_page";
		formatted_action_items += '<li><a class="ai_landing_page ai_link" href="'+this.landing_page+'" target="_blank"><i class="icon-home"></i> Go to Landing Page</a></li>';
		
		if(this.has_adsets == true)
		{
			action_dropdown_classes += " aic_creative";
			formatted_action_items += '<li><a class="ai_creative ai_modal" href="#adset_modal" data-toggle="modal" onclick ="show_adset_modal(\''+escape(this.advertiserElem)+'\', \''+escape(this.campaign)+'\', '+this.id+');"><i class="icon-picture"></i> View Creative</a></li>';
		}

		<?php if ($can_create_ticket) { ?>
			formatted_action_items += '<li><a class="create_ticket_button ai_modal" href="#" data-campaign-id="'+this.id+'"><i class="icon-ticket"></i> Create Helpdesk Ticket</a></li>';
		<?php } ?>
		
		if(this.has_tags == true)
		{
			action_dropdown_classes += " aic_rtg_tags";
			formatted_action_items += '<li><a class="ai_rtg_tags ai_link" href="/campaign_health/download_tags/'+this.id+'" target="_blank"><i class="icon-code"></i> Advertiser Tracking Tags</a></li>';
		}

<?php if($role == 'ops' || $role == 'admin') { ?>

		if(this.has_adgroups == true)
		{
			action_dropdown_classes += " aic_adgroups";
			formatted_action_items += '<li><a class="ai_adgroups ai_modal" href="#" onclick="show_adgroup_modal(\''+escape(this.advertiserElem)+'\', \''+escape(this.campaign)+'\', '+this.id+');"><i class="icon-edit"></i> AdGroups Form</a></li>';
		}

		action_dropdown_classes += " aic_archive";
		formatted_action_items += '<li><a class="ai_archive ai_modal" href="#confirm_modal" data-toggle="modal" onclick="show_removal_confirm_modal('+this.id+', \''+escape(this.advertiserElem)+'\', \''+escape(this.campaign)+'\', $(this));"><i class="icon-trash"></i> Archive</a></li>';

<?php } ?>

		this.formatted_actions = '<div class="btn-group pull-right"><a class="btn btn-link dropdown-toggle'+action_dropdown_classes+'" data-toggle="dropdown" href="#"><i class="icon-gear"></i><span class="caret"></span></a><ul class="dropdown-menu">';
		this.formatted_actions += formatted_action_items;
		this.formatted_actions += '</ul></div>';

<?php if($role == 'ops' || $role == 'admin') { ?>
		this.formatted_update_button = '<button onclick="force_campaign_pending('+this.id+', this);" class="popover_'+this.id+' tool_poppers c_pending_button pull-right btn btn-link" data-toggle="popover" data-trigger="hover" data-placement="left" title data-original-title="Force Pending" data-content="Click here to mark the campaign with pending changes" type="button"><i class="icon-hand-up icon-white"></i></button> ';
											   
<?php } ?>
 
	});
	return data;
}

function format_percent_data_num(raw_percent, decimal)
{
	var mult_num = raw_percent * 100;
	if(Math.round(mult_num) !== mult_num)
	{
		if(decimal === 0)
		{
			return Math.round(mult_num) + "%";
		}
		else
		{
			return mult_num.toFixed(decimal) + "%";
		}
	}
	else
	{
		return mult_num + "%";
	}
}

function format_timeseries_string(timeseries_string, campaign_end_date, campaign_id)
{
	timeseries_string.replace(",", "" );
	return '<span data-html="true" style="width:70px" class="popover_'+campaign_id+'  tool_poppers label" data-toggle="popover" data-trigger="hover" data-placement="right" data-original-title="Schedule" data-content="'+timeseries_string+'">'+campaign_end_date+'</span>';
}

function format_cycle_end_date(cycle_end_date, campaign_id)
{
	if (cycle_end_date == undefined)
		return '';

	var ce_cycle_end_date="ce:"+cycle_end_date	;
	return '<span data-html="true" style="width:70px" data-trigger="hover1" data-placement="right" data-original-title="Schedule" data-content="'+ce_cycle_end_date+'">'+cycle_end_date+'</span>';
}


function format_cycle_target(raw_number)
{
	if(raw_number < 0)
	{
		return '-';
	}
	else
	{
		return format_large_data_num(raw_number, 2);
	}
}

function format_yday_impressions(id, yday_impressions)
{
	var data_content="NO_YDAY_IM";
	if (yday_impressions > 5)
	{
		data_content="YES_YDAY_IM";
	}
	return '<span id="c_yday_impressions_text_'+id+'" data-trigger="hover1" data-placement="right" data-content="'+data_content+'">'+format_large_data_num(yday_impressions, 2)+'</span>';
}

function format_target_impressions(id, target_impressions, ttd_pending_flag)
{ 
	var data_content="NO_TGT_IM";
	if (target_impressions > 0)
	{
		data_content="YES_TGT_IM";
	}
	 
	var ttd_no_pending_string = '';
	if(ttd_pending_flag == false)
	{
		ttd_no_pending_string=';TTD_NO_PEND';
	}
	data_content += ttd_no_pending_string;

	return '<span id="target_impressions_text_'+id+'" data-trigger="hover1" data-placement="right" data-content="'+data_content+'">'+format_large_data_num(target_impressions, 2)+'</span>';
}

function format_large_data_num(raw_number, decimal)
{
	var return_num = "0";
	var suffix_str = "k"; //thousand
	var divided_num = raw_number/1000;

	//un-comment this to convert very large numbers to million
	//	if(divided_num >= 1000)
	//	{
	//		suffix_str = "m"; //million
	//		divided_num = divided_num/100;
	//	}
	
	if(Math.round(divided_num) !== divided_num)
	{
		if(decimal === 0 || Math.abs(divided_num.toFixed(decimal)) % 1 == 0)
		{
			return_num = Math.round(divided_num);
			if(return_num === -0)
			{
				return_num = 0;
			}
		}
		else
		{
			return_num = divided_num.toFixed(decimal);
		}
	}
	else
	{
		return_num = divided_num;
	}
	return numberWithCommas(return_num) + suffix_str;
}

function format_total_oti(raw_oti)
{
	var oti = Math.round(raw_oti*100);
	if(oti > 100)
	{
		return '<span class="label label-success">&gt;100%</span>';
	}
	else if(oti == 100)
	{
		return '<span class="label label-success">100%</span>';
	}
	else if(oti < 80)
	{
		return '<span class="label label-warning">'+oti+'%</span>';
	}
	else	
	{
		return '<span>'+oti+'%</span>';
	}
}

var c_window_width = 0;
var c_navbar_height = 0;

$(window).load(function(){
	c_window_width = $(window).width();
	var navbar_object = $('body > div.navbar.navbar-fixed-top');
	c_navbar_height = navbar_object.height();
	
	if(navbar_object.css('position') == 'fixed')
	{
		$('body').css('padding-top', c_navbar_height+'px');
	}
	else
	{
		$('body').css('padding-top', '0px');
	}
});


$(window).resize(function(){
	var temp_window_width = $(window).width();
	if(c_window_width != temp_window_width)
	{
		var navbar_object = $('body > div.navbar.navbar-fixed-top');
		var temp_navbar_height = navbar_object.height();
		
		if(c_navbar_height != temp_navbar_height)
		{
			var css_padding_top = 0;
			if(navbar_object.css('position') == 'fixed')
			{
				css_padding_top = 11 + temp_navbar_height;
			}
			else
			{
				temp_navbar_height = 0;
			}
			var fixed_header_object = $('div.FixedHeader_Cloned.fixedHeader.FixedHeader_Header');
			if(fixed_header_object.css('position') == 'fixed')
			{
				fixed_header_object.css('top', temp_navbar_height);
			}
			$('body').css('padding-top', css_padding_top);
			c_navbar_height = temp_navbar_height;
		}
		c_window_width = temp_window_width;
	}
});

function custom_table_search()
{
	var keywords = document.getElementById('custom_search').value.split(' '), filter ='';
	
	for (var i=0; i<keywords.length; i++) 
	{
	   filter = (filter!=='') ? filter+'|'+keywords[i] : keywords[i];
	}            
	campaign_table.fnFilter("", null, true, false, true, true);
}

$(document).ready(function(){
		 

<?php if(($role == 'ops' || $role == 'admin') && !empty($campaign_tags_error)){ ?> 
	$("#c_main_loading_img").hide();
	set_message_timeout_and_show('<?php echo $campaign_tags_error; ?>', 'alert alert-error', 100000);

	return;

<?php } ?>

	$.ajax({
		type: "POST",
		url: '/campaigns_main/ajax_get_campaigns_data',
		async: true,
		data: 
		{
			report_date: '<?php echo $report_date; ?>',
			campaign_tags: '<?php echo $campaign_tags_input; ?>'
		},
		dataType: 'json',
		success: function(data, textStatus, jqXHR){
			setTimeout(function(){
				$("#c_main_loading_img").hide();
				if(data.is_same_sales === undefined || data.is_same_partner === undefined || data.campaign_array === undefined)
				{
					set_message_timeout_and_show('Error 500322: Server returned invalid campaign data', 'alert alert-error', 16000);	
				}
				else if(data.campaign_array.length == 0)
				{
					var body_html = '<h3> Looks like you don\'t have any active campaigns. ';
<?php if($can_view_mpq) { ?>
					body_html += '<a href="/mpq" target="_blank">Click here to request one</a> or ';
<?php } ?>
				body_html += '<a href="/report" target="_blank">Go to reports</a>' +
					 ' to see older archived campaigns.</h3>';
				$("#campaign_body_section").html(body_html);
				$("#campaign_body_section").show();
			}
			else
			{
<?php if($role == 'ops' || $role == 'admin') { ?>
				$("#ct_tag_utility_button_1").show();
<?php } ?>
				$("#report_date_header").show();
				$("#campaign_html_header").show();
				$("#campaign_table").show();

				campaign_table = $("#campaign_table").dataTable({
					"data": format_table_data(data.campaign_array, data.is_same_sales, data.is_same_partner),
					"ordering": true,
<?php if($role == 'ops' || $role == 'admin') { ?>					
					"order": [[ 4, "asc"]],
<?php } else { ?>		
					"order": [[ 2, "asc"]],
<?php } ?>				
					"lengthMenu": [
						[100, 200, -1],
						[100, 200, 'All']
					],
					"columnDefs": [
						{}
					],
					"rowCallback": function(row, data) {
						$(row).find('.tool_poppers').popover();
					},
					"initComplete": function(settings){
						initialize_tooltips();
<?php if($role == 'admin' || $role == 'ops') { ?>
							update_items_to_load_with_current_view(settings, true);
<?php } ?>						
						},
						"drawCallback": function(settings){
<?php if($role == 'admin' || $role == 'ops') { ?>
							update_items_to_load_with_current_view(settings, false);
<?php } ?>
						},
						"columns": [
							{
								data: "formatted_checkbox",
<?php if($role == 'admin' || $role == 'ops') { ?>
								title: "<input class=\"c_control_checkbox\" onclick=\"highlight_all_campaign_rows($(this));\" type=\"checkbox\">",
<?php } else { ?>
								title: "",
<?php } ?>
								"class": "c_checkbox",
								orderable: false,
								searchable: false
							},
							{
								data: "is_reminder",
								orderDataType: "is_reminder",
								title: "<span class='star-disable'></span>",
								render: {
									_: "formatted_reminder",
									sort: "reminder"
								},
								"class": "c_center",
								searchable: false,
								type: "string"
							},	
							{
								data: "campaign_obj",
								render: {
									_: "formatted_campaign",
									sort: "campaign"
								},
								title: "<div>Campaign</div>",
								"class": "c_campaign_column",
								type: "string"
							},
							{
								data: "target_obj",
								render: {
									_: "formatted_target",
									sort: "target_impressions"
								},
								title: "<div>Target</div>",
								"class": "c_target_column c_center",
								type: "num"
							},
<?php if($role == 'admin' || $role == 'ops') { ?>
							{
								data: "alerts_string_obj",
								render: {
									_: "formatted_alerts_string",
									sort: "alerts_string"
								},
								title: "<div>Alerts</div>",
								"class": "c_alerts_string_column"     
							},
							{
								data: "action_date_obj",
								render: {
									_: "formatted_action_date",
									sort: "action_date"
								},
								title: "<div>Action</div>",
								"class": "c_target_column",
								type: "string"
							},
							{
								data: "days_remaining_obj",
								render: {
									_: "formatted_days_remaining",
									sort: "cycle_days_remaining"
							},
								title: "<div>Rem.</div>",
								"class": "c_days_remaining_column c_center",
								type: "num"
							},
<?php	} 
							if ($role == 'sales') 
							{
?>								{
									data: "order_id_obj",
									render: {
										_: "formatted_order_id",
										sort: "order_id"
									},
									title: "<div>Order ID</div>",
									"class": "order_id_column c_center",
									type: "string"
								},
<?php							}
?>								
							{
								data: "start_date",
								title: "<div>Start</div>",
								"class": "c_start_column c_center"     
							},
							{
								data: "timeseries_string_obj",
								render: {
									_: "formatted_timeseries_string",
									sort: "campaign_end_date"
								},
								title: "<div>End</div>",
								"class": "c_timeseries_column c_center"
							},	
							{
								data: "cycle_start_date",
								title: "<div>Cycle Start</div>",
								"class": "c_cycle_start_column c_center"
							},						
							{
								data: "cycle_end_date_obj",
								render: {
									_: "formatted_cycle_end_date",
									sort: "cycle_end_date"
								},
								title: "<div>Cycle End</div>",
								"class": "c_cycle_end_column c_center",
								type: "string"
							},
							{
								data: "total_impressions_obj",
								render: {
									_: "formatted_total_impressions",
									sort: "total_impressions"
								},
								title: "<div>Total Imprs</div>",
								"class": "c_total_imprs_column c_center",
								type: "num"
							},
							{
								data: "total_oti_obj",
								render: {
									_: "formatted_total_oti",
									sort: "total_oti"
								},
								title: "<div>Total OTI</div>",
								"class": "c_total_oti_column c_center",
								type: "num"
							},
<?php if($role == 'ops' || $role == 'admin') { ?>
							{
								data: "cycle_impressions_obj",
								render: {
									_: "formatted_cycle_impressions",
									sort: "cycle_impressions"
								},
								title: "<div>Cycle Imprs</div>",
								"class": "c_cycle_imprs_column c_center",
								type: "num"
							},
							{
								data: "cycle_oti_obj",
								render: {
									_: "formatted_cycle_oti",
									sort: "cycle_oti"
								},
								title: "<div>Cycle OTI</div>",
								"class": "c_cycle_oti_column c_center",
								type: "num"
							},
							{
								data: "yesterday_impressions_obj",
								render: {
									_: "formatted_yesterday_impressions",
									sort: "yday_impressions"
								},
								title: "<div>Y'day Imprs</div>",
								"class": "c_yd_imprs_column c_center",
								type: "num"
							},
							{
								data: "cycle_target_obj",
								render: {
									_: "formatted_cycle_target",
									sort: "cycle_target"
								},
								title: "<div>Cycle Target</div>",
								"class": "c_cycle_target_column c_center",
								type: "num"
							},
							{
								data: "lt_target_obj",
								render: {
									_: "formatted_lt_target",
									sort: "long_term_target"
								},
								title: "<div>Ideal Pace</div>",
								"class": "c_lt_target_column c_center",
								type: "num"
							},
							{
								data: "daily_oti_obj",
								render: {
									_: "formatted_daily_oti",
									sort: "daily_oti"
								},
								title: "<div>Daily OTI</div>",
								"class": "c_daily_oti_column c_center",
								type: "num"
							},
							{
								data: "nf_oti_obj",
								render: {
									_: "formatted_nf_oti",
									sort: "nf_oti"
								},
								title: "<div>NF OTI</div>",
								"class": "c_nf_oti_column c_center",
								type: "num"
							},
							{
								data: "ctr_obj",
								render: {
									_: "formatted_ctr",
									sort: "ctr"
								},
								title: "<div>CTR</div>",
								"class": "c_ctr_column c_center",
								type: "num"
							},
							{
								data: "engagement_rate_obj",
								render: {
									_: "formatted_engagement_rate",
									sort: "engagement_rate"
								},
								title: "<div>Engagement Rate</div>",
								"class": "c_engagement_column c_center",
								type: "num"
							},
							{
								data: "formatted_ttd_data",
								title: "",
								"class": "c_ttd_column c_center",
								orderable: false,
								searchable: false
							},
							{
								data: "formatted_ttd_warning",
								title: "",
								"class": "c_ttd_warning_column c_center",
								orderable: false,
								searchable: false
							},
							{
								data: "formatted_update_button",
								title: "",
								"class": "c_update_button_column c_center",
								orderable: false,
								searchable: false
							},
<?php } ?>
						{
							data: "formatted_actions",
							title: "",
							"class": "c_action_column c_center",
							orderable: false,
							searchable: false
						}
					]
				});
<?php if($role == 'ops' || $role == 'admin' || $role = 'sales'){ ?>
	campaign_table_new = campaign_table.api();
	$('#campaign_table tbody').on( 'click', '.reminder_check', function () {  
		selected_row = $(this).closest('tr');
		row_data = campaign_table_new.row($(this).closest('tr')).data();
		change_reminder_flag();
	});
<?php } ?>

// this method makes sure atleast 3 characters need to be typed in datatable searchbox on campaigns main page			
 
$('.dataTables_filter input')
    .unbind('')
    .bind('input', function(e){
		var item = $(this);
    	searchWait = 0;
    	if(!searchWaitInterval) searchWaitInterval = setInterval(function(){
        if(searchWait>=3){
            clearInterval(searchWaitInterval);
            searchWaitInterval = '';
     // if ($(this).val().length < 3 && $(this).val().length > 0 && e.keyCode != 13) return;
			searchTerm = $(item).val();
            
      campaign_table.fnFilter(searchTerm);

      searchWait = 0;
        }
        searchWait++;
    },200);

});

$(document).on('click', '.create_ticket_button', function(e){
	e.preventDefault();
	$('#create_ticket_modal input#ticket_campaign_id').val($(this).data('campaign-id'));
	$('#create_ticket_modal input#ticket_subject').val('');
	$('#create_ticket_modal #ticket_body').val('');
	$('#create_ticket_modal .modal-body div.alert-error').hide();
	$('#create_ticket_modal').modal();
});

$('#create_ticket_button').on('click', function(e){
	e.preventDefault();
	var data = {};
	data.subject = $('#ticket_subject').val();
	data.body = $('#ticket_body').val().replace(/\n/g, '<br/>');
	data.campaign_id = $('#ticket_campaign_id').val();

	$('#create_ticket_modal .modal-body .control-group').removeClass('error');

	var validate = true;
	if (data.subject === '')
	{
		$('#ticket_subject').parent().addClass('error');
		validate = false;
	}

	if (data.body === '')
	{
		$('#ticket_body').parent().addClass('error');
		validate = false;
	}

	if (validate === false)
	{
		$('#create_ticket_modal .modal-body div.alert-error').fadeIn(200);
		return false;
	}

	// validation

	$.ajax({
		url: '/campaigns_main/ajax_create_ticket',
		method: 'POST',
		data: data,
		dataType: 'json',
		success: function(data){
			$('#create_ticket_modal').modal('hide');
			set_message_timeout_and_show('Your ticket has been created.', 'alert alert-primary', 10000);
		},
		error: function(jqXHR){
			$('#create_ticket_modal').modal('hide');
			set_message_timeout_and_show(jqXHR.responseText, 'alert alert-error', 10000);
		}
	});
});
 
<?php if($role == 'admin' || $role == 'ops') { ?>
								 
String.prototype.indexOfInsensitive = function (s, b) {
    return this.toLowerCase().indexOf(s.toLowerCase(), b);
}

campaign_table.dataTableExt.afnFiltering.push(
    function( oSettings, aData, iDataIndex ) {

    	var keywords = document.getElementById('custom_search').value.split(','), filter ='';
		
		var return_flag=true;
		for (var i=0; i<keywords.length; i++) 
		{
			var return_flag_keyword=false;
			var return_flag_keyword_neg=true;
			for ( j=0; j<aData.length; j++ )
			{	
				var keyword=keywords[i].trim();
				if (keyword.indexOf('^') == -1 &&  aData[j].indexOfInsensitive(keyword) != -1 )
				{
					 return_flag_keyword = true;
					 break;
				} 
				else if (keyword.indexOf('^') != -1 &&  aData[j].indexOfInsensitive(keyword.substring(1)) != -1 )
				{

					 return_flag_keyword_neg = false;
					 break;
				}                  
			}
		    if ((keyword.indexOf('^') == -1 && !return_flag_keyword) || (keyword.indexOf('^') != -1 && !return_flag_keyword_neg))
            {
            	return_flag=false;
            	break;
            }            	
		}      	
        return return_flag;
    }
);

<?php }   ?>

var searchWait = 0;
var searchWaitInterval;
 



				$('a.ai_modal').click(function(event){
					event.preventDefault();
				});
				var fh_offset_top = 0;
				var navbar_object = $('body > div.navbar.navbar-fixed-top');
				if(navbar_object.css('position') == 'fixed')
				{
					fh_offset_top = navbar_object.height();
				}
				fixed_header = new $.fn.dataTable.FixedHeader(campaign_table, {'offsetTop': fh_offset_top});

				$(window).scroll(function(){
					fix_datatables_fixed_header_offset();
				});
				
				$(window).resize(function(){
					fixed_header._fnUpdateClones(true);
					fixed_header._fnUpdatePositions();
					$('th.c_checkbox').css('width', '0px');
				});

<?php if($role == 'ops' || $role == 'admin') { ?>
				var bulk_action_html = '' +
					'<div id="c_bulk_action_html"><div id="bulk_hidden_icon_container">0</div><div class="dropdown">' +
						'<div class="dropdown-toggle" data-toggle="dropdown"><i class="icon-list-alt"></i><i class="icon-caret-down"></i></div>' +
						'<ul class="dropdown-menu">' +
							'<li><a id="bap_submit_bap_campaigns" class="c_prevent_default" href="#" onclick="submit_bap_campaigns();"><i class="icon-download-alt"></i> BAP</a></li>' +
							'<li><a id="bap_submit_bap_timeseries" class="c_prevent_default" href="#" onclick="submit_bap_timeseries();"><i class="icon-download-alt"></i> B TimeSeries</a></li>' +
							'<li><a class="c_prevent_default" id="c_campaign_tag_link" href="#"><i class="icon-tags"></i> Tag Campaigns</a></li>' +
							'<li><a id="bap_bulk_archive_campaigns" class="c_prevent_default" href="#bulk_archive_modal" data-toggle="modal" onclick="set_bulk_archive_modal_content();"><i class="icon-trash"></i> Archive</a></li>' +
						'</ul>' +
					'</div></div>';
				$('div.FixedHeader_Cloned.fixedHeader.FixedHeader_Header').prepend(bulk_action_html);

				$('#c_bulk_action_html a.c_prevent_default').click(function(event){
					event.preventDefault();
				});

				$('#confirm_bulk_archive_button').click(archive_all_bap_campaigns);
				
				$('#c_campaign_tag_link').click(function(){
					if(c_bulk_adgroup_campaign_ids.length > 0)
					{
						 get_campaign_tag_data_and_display();
					}
					else
					{
						set_message_timeout_and_show('No campaigns selected for tag view', 'alert alert-warning', 16000);
					}

				});
<?php } ?>
				}
<?php if($role == 'ops' || $role == 'admin') { ?>
			}, 1000);
<?php } else { ?>
			}, 100);
<?php } ?>
		},
		error: function(jqXHR, textStatus, error){ 
			set_message_timeout_and_show('Error 500028: Server Error', 'alert alert-error', 16000);		
		}
	});	

<?php if($role == 'ops' || $role == 'admin') { ?>
	$(document).on("dblclick", '#campaign_table tr > td', function(){
		$(this).parent('tr').toggleClass('row_highlighted_click');
	});
<?php } ?>
});

function initialize_tooltips()
{
	$('th.c_target_column > div').popover({
		placement: 'left',
		title: "Target Impressions",
		content: "Target Impressions for current cycle.",
		trigger: "hover"
	});
	$('th.c_start_column > div').popover({
		placement: 'left',
		title: "Start Date",
		content: "First impression date",
		trigger: "hover"
	});
	$('th.c_cycle_start_column > div').popover({
		placement: 'left',
		title: "Cycle Start Date",
		content: "The first impression date for the current cycle",
		trigger: "hover"
	});
	$('th.c_cycle_end_column > div').popover({
		placement: 'left',
		title: "Cycle End Date",
		content: "The last impression date for the current cycle",
		trigger: "hover"
	});
	$('th.c_total_imprs_column > div').popover({
		placement: 'left',
		title: "Total Impressions",
		content: "Sum of campaign's lifetime impressions",
		trigger: "hover"
	});
	$('th.c_total_oti_column > div').popover({
		placement: 'left',
		title: "Lifetime On Target Indicator",
		content: "If = 100%, the campaign is right on target for its lifetime. If under/over 100% the campaign is under/over its lifetime target impressions",
		trigger: "hover"
	});

<?php if($role == 'admin' || $role == 'ops') { ?>

	$('th.c_cycle_imprs_column > div').popover({
		placement: 'left',
		title: "Cycle Impressions",
		content: "Sum of campaign's impressions for this cycle",
		trigger: "hover"
	});

	$('th.c_cycle_oti_column > div').popover({
		placement: 'left',
		title: "Cycle On Target Indicator",
		content: "If = 100%, the campaign is right on target for its cycle. If under/over 100% the campaign is under/over its cycle target impressions",
		trigger: "hover"
	});

	$('th.c_cycle_target_column > div').popover({
		placement: 'left',
		title: "Cycle Target Impressions",
		content: "The daily target required to hit the cycle OTI by the cycle end date",
		trigger: "hover"
	});

	$('th.c_yd_imprs_column > div').popover({
		placement: 'left',
		title: "Yesterday Impressions",
		content: "Sum of campaign's impressions for the last known impression date",
		trigger: "hover"
	});

	$('th.c_lt_target_column > div').popover({
		placement: 'left',
		title: "Long Term Target",
		content: "The average daily impression target required to satisfy targets in the long run",
		trigger: "hover"
	});

	$('th.c_daily_oti_column > div').popover({
		placement: 'left',
		title: "Daily OTI",
		content: "yesterday's impressions divided by the cycle target.  If cycle target is 0 or less, the percentage will be infinite.",
		trigger: "hover"
	});

	$('th.c_nf_oti_column > div').popover({
		placement: 'left',
		title: "NF OTI",
		content: "Yesterday's impressions divided by the Ideal pace of next flight. If Yesterday impressions =0 or Days in next flight =0 or Target impressions in next flight =0, the NF OTI will be 0",
		trigger: "hover"
	});

	$('th.c_days_remaining_column > div').popover({
		placement: 'left',
		title: "Cycle Days Remaining",
		content: "The number of days left in the current cycle",
		trigger: "hover"
	});
	
	$('th.order_id_column > div').popover({
		placement: 'left',
		title: "Order ID",
		content: "Order ID from Insertion Order",
		trigger: "hover"
	});	

	$('th.c_ctr_column > div').popover({
		placement: 'left',
		title: "Click Through Rate",
		content: "Clicks/Impressions",
		trigger: "hover"
	});

	$('th.c_engagement_column > div').popover({
		placement: 'left',
		title: "Engagement Rate",
		content: "Engagements/Impressions",
		trigger: "hover"
	});

	$('th.c_action_date_column > div').popover({
		placement: 'left',
		title: "Action Date",
		content: "Shows Action Date. Impressions of current Series != Impressions of Next Series AND Impressions of Next Series > 0",
		trigger: "hover"
	});

	$('th.c_alerts_string_column > div').popover({
		placement: 'right',
		title: "Alerts",
		html: "true",
		content: "List of Alerts: 1) PAUS_2 : Paused Campaign with End Date in past    2) PAUS_1 : Paused Campaign with impressions y'day        3) PAUS_0 : Paused Campaign          4) LIVE_0 : Live Campaign with 0 impressions y'day          5) DEAD_1 : Not live Campaign with impressions y'day         6) ARCH_0 : Please archive. Campaign End Date in past with 0 Impressions yesterday        7) ARCH_1 : No Campaign End Date    ",
		trigger: "hover"
	});

	$('th.c_timeseries_column > div').popover({
		placement: 'left',
		title: "Schedule",
		content: "Start Date of the Campaign. On hover of the Start Date below, the tooltip shows the Start Date, End Date and Target Impressions for each cycle setup for this Campaign.",
		trigger: "hover"
	});

<?php } ?>

}


function highlight_campaign_row(obj, event)
{
	if(obj.prop("checked") == true)
	{	
		if(event !== false)
		{
			if(event.shiftKey)
			{
				var all_visible_campaigns = $(".c_campaign_row_checkbox").map(function(){return this}).get();
				var highlight_start = false;
				if(last_campaign_checked !== false && $.inArray(last_campaign_checked, all_visible_campaigns) >= 0)
				{
					$.each(all_visible_campaigns, function(key, value) {
						if(highlight_start === true)
						{
							if(value === obj[0] || value === last_campaign_checked)
							{
								return false;
							}
							$(value).prop('checked', 'checked');
							highlight_campaign_row($(value), false);
						}
						else if(value === obj[0] || value === last_campaign_checked)
						{
							highlight_start = true;
						}
						
					});
				}
			}
			last_campaign_checked = obj[0];
		}
		
		obj.parent().parent().addClass("row_highlighted");

<?php if($role == 'admin' || $role == 'ops') { ?>
		var temp_campaign_id = obj.attr('data-c-campaign-id');
		add_campaign_id_to_array_if_not_exist(temp_campaign_id);
		if(update_icon_and_check_all_visible_checkboxes_checked() === true)
		{
			$('.c_control_checkbox').prop('checked', true);
		}

<?php } ?>

	}
	else
	{
		last_campaign_checked = false;

<?php if($role == 'admin' || $role == 'ops') { ?>
		var temp_campaign_id = obj.attr('data-c-campaign-id');
		remove_campaign_id_from_array_if_exist(temp_campaign_id);
		$('.c_control_checkbox').prop('checked', false);
<?php } ?>

		obj.parent().parent().removeClass("row_highlighted");
	}
}

function update_bulk_hidden_icon_number(campaign_count)
{
	if(campaign_count > 99)
	{
		$('#bulk_hidden_icon_container').html('99+');
	}
	else
	{
		$('#bulk_hidden_icon_container').html(campaign_count);
	}
}

function show_adset_modal(raw_advertiser_name, raw_campaign_name, campaign_id)
{
	var advertiser_name = unescape(raw_advertiser_name);
	var campaign_name = unescape(raw_campaign_name);
	
	$("#adset_modal_detail_header").html(advertiser_name + ' - ' + campaign_name + ' Adsets');
	$("#adset_modal_detail_body").html('<div class="progress progress-striped active"> <div class="bar" style="width: 100%;"></div></div>');
	$.ajax({
        type: "POST",
        url: "/campaigns_main/ajax_get_adsets_for_campaign/",
        async: true,
        data: {c_id: campaign_id},
        dataType: 'json',
		success: function(data, textStatus, jqXHR){
			var body_content = "";
			if(data.is_success === true && $.isArray(data.versions) && data.versions.length > 0)
			{
				$.each(data.versions, function(index, value){
					body_content += value;
				});
			}
			else
			{
				body_content = "<h4>No adsets available.</h4>";
			}
			$("#adset_modal_detail_body").html(body_content);
        },
		error: function(jqXHR, textStatus, error){ 
			set_message_timeout_and_show('Error 500013: Server Error', 'alert alert-error', 16000);
		}
    });
}

function is_zero(input)
{
    return (input == 0)||(input == "");
}

function campaign_type_stringify(end_type, kill_date)
{
    var output = "";
    switch(end_type)
    {
	case "FIXED_TERM":
	    output = "Fixed term until <br>" + kill_date;
	    break;
	case "MONTH_END_WITH_END_DATE":
	    output = "Month-end until <br> " + kill_date;
	    break;
	case "MONTHLY_WITH_END_DATE":
	    output = "Monthly until <br>" + kill_date;
	    break;
	case "MONTH_END_RECURRING":
	    output = "Month-end recurring";
	    break;
	case "MONTHLY_RECURRING":
	    output = "Monthly recurring";
	    break;
    case "BROADCAST_MONTHLY_RECURRING":
        output = "Broadcast month recurring"
        break;
    case "BROADCAST_MONTHLY_WITH_END_DATE":
        output = "Broadcast monthly until <br> " + kill_date;
        break;
	case "ERROR":
	    output = '<span class="label label-inverse">Campaign setup error</span>';
    }
    return output;
}

$("#bd_partner_select").change(function(event, optional_sales_val){
	$("#bd_sales_person_select").select2("readonly", true);
	$("#bd_sales_person_select").html("");
	var start_date = $("#bulk_download_start_date").val();
	var end_date = $("#bulk_download_end_date").val();
	var selected_partner = this.value;
	if(selected_partner !== 'select' && typeof start_date !== undefined && typeof end_date !== undefined && is_valid_date(start_date) && is_valid_date(end_date))
	{
	    $.ajax({
			type: "POST",
			url: '/campaigns_main/ajax_get_bulk_download_sales_people',
			dataType: 'json',
			async: true,
			data: 
			{
				selected_partner: selected_partner,
				start_date: start_date,
				end_date: end_date
			},
			success: function(data, textStatus, xhr){
				var data_data_length = 0;
				for(var data_data_index in data.data)
				{
					if(data.data.hasOwnProperty(data_data_index))
					{
						data_data_length++;  //count number of objects stored in data.data
					}
				}
				if(data.is_success == true && typeof data.data !== "undefined" && data_data_length > 0)
				{
					$("#bd_sales_person_select").html("");
					var partner = "";
					$.each(data.data, function(index, value){
						if(value.partner === undefined)
						{
							partner = "";
						}
						else
						{
							partner = value.partner+' :: ';
						}

						$('<option value="'+value.id+'">' + partner + value.username + '</option>').appendTo("#bd_sales_person_select");
					});
					$('#bd_sales_person_select').select2({
						formatResult: select2_ae_dropdown_format,
						formatSelection: select2_ae_selection_format
					});
					if(optional_sales_val !== undefined && $.isNumeric(optional_sales_val)) //if the function was triggered by a date change we want to preserve the value that was selected previously
					{
						$("#bd_sales_person_select").select2("val", optional_sales_val);
					}

				}
				else
				{
					$('<option value="none">None Available</option>').appendTo("#bd_sales_person_select");
					$('#bd_sales_person_select').select2({
						formatResult: select2_ae_dropdown_format,
						formatSelection: select2_ae_selection_format
					});			
				}
				$("#bd_sales_person_select").select2("readonly", false);
			},
			error: function(xhr, textStatus, error){
				$('<option value="none">Temporarily Unavailable</option>').appendTo("#bd_sales_person_select");
				$('#bd_sales_person_select').select2({
					formatResult: select2_ae_dropdown_format,
					formatSelection: select2_ae_selection_format
				});
				$("#bd_sales_person_select").select2("readonly", false);
			}
		});
	}
	else
	{
		$('<option value="none">None Available</option>').appendTo("#bd_sales_person_select");
		$('#bd_sales_person_select').select2({
			formatResult: select2_ae_dropdown_format,
			formatSelection: select2_ae_selection_format
		});	
		$("#bd_sales_person_select").select2("readonly", false);
	}
});

function select2_ae_dropdown_format(item)
{
	var result = item.text;
	var item_split_arr = item.text.split(" :: ");
	if(item_split_arr.length == 2)
	{
		item_split_arr[0] = '<small class="muted">'+item_split_arr[0]+'</small>';
		item_split_arr[1] = '<strong>'+item_split_arr[1]+'</strong>';
		result = item_split_arr.join('<br />');
	}
	return result;
}
function select2_ae_selection_format(item)
{
	var result = item.text;
	var item_split_arr = item.text.split(" :: ");
	if(item_split_arr.length == 2)
	{
		result = item_split_arr[1];
	}
	return result;
}

function is_valid_date(date_string)
{
    if(!/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(date_string))
	{
        return false;
	}

    // Parse the date parts to integers
    var parts = date_string.split("/");
    var day = parseInt(parts[1], 10);
    var month = parseInt(parts[0], 10);
    var year = parseInt(parts[2], 10);

    // Check the ranges of month and year
    if(year < 1000 || year > 3000 || month == 0 || month > 12)
	{
        return false;
	}

    var month_length = [ 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 ];

    // Adjust for leap years
    if(year % 400 == 0 || (year % 100 != 0 && year % 4 == 0))
	{
        month_length[1] = 29;
	}

    // Check the range of the day
    return day > 0 && day <= month_length[month - 1];
}

function set_message_timeout_and_show(message, selected_class, timeout)
{
	window.clearTimeout(c_timeout_id);
	$('#c_message_box_content').append(message+"<br>");
	$('#c_message_box').prop('class', selected_class);
	$('#c_message_box').show();
	c_timeout_id = window.setTimeout(function(){
		$('#c_message_box').fadeOut("slow", function(){
			$('#c_message_box_content').html('');
		});
	}, timeout);
}

$("#c_message_box > button").click(function(){
	window.clearTimeout(c_timeout_id);
	$('#c_message_box').fadeOut("fast", function(){
		$('#c_message_box_content').html('');
	});
});

function numberWithCommas(x) {
    var parts = x.toString().split(".");
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    return parts.join(".");
}

function format_days_remaining(days_left)
{
	if(days_left === null)
	{
		return '-';
	}
	return days_left;
}

function check_pending_and_submit_bulk_campaign_request(is_submit_flag)
{
	$('#bulk_download_init_button').prop('disabled', true);
	$.ajax({
		type: "POST",
		url: '/campaigns_main/ajax_check_user_bulk_pending_flag',
		dataType: 'json',
		async: true,
		success: function(data, textStatus, xhr) {
			if(data.is_success === true)
			{
				$('#bulk_download_init_button').prop('disabled', true);
				if(data.is_pending === true)
				{
					display_bulk_campaign_warning_message(true);
				}
				else
				{
					if(is_submit_flag == true)
					{
						submit_bulk_campaign_data_request(data.user_email);
					}
					else
					{
						display_bulk_campaign_warning_message(false);
						$('#bulk_download_init_button').prop('disabled', false);
					}
				}
			}
			else
			{
				$('#bulk_download_modal').modal('hide');
				set_message_timeout_and_show(data.errors, 'alert alert-error', 16000);
			}
		},
		error: function(xhr, textStatus, error) {
			if(xhr.getAllResponseHeaders())
			{
				$('#bulk_download_modal').modal('hide');
				set_message_timeout_and_show("Error 397016: Bulk campaign data request failed with Server Error", 'alert alert-error', 16000);
			}
		}
	});
}

function submit_bulk_campaign_data_request(user_email)
{
	var start_date = $('#bulk_download_start_date').val();
	var end_date = $('#bulk_download_end_date').val();
	var selected_partner = $('#bd_partner_select').val();
	var selected_sales_person = $('#bd_sales_person_select').val();
	if(typeof start_date !== "undefined" &&
	   typeof end_date !== "undefined" &&
	   typeof selected_partner !== "undefined" &&
	   typeof selected_sales_person !== "undefined")
	{
		$('#bulk_download_modal').modal('hide');
		var alert_message = 'An email has been sent to your address with the bulk campaign data you requested';
		if(typeof user_email !== "undefined" && user_email !== false)
		{
			alert_message = 'An email has been sent to your address ('+user_email+') with the bulk campaign data you requested'
		}
		set_message_timeout_and_show(alert_message, 'alert alert-info', 16000);

		$.ajax({
			type: "POST",
			url: '/campaigns_main/get_bulk_download',
			dataType: 'json',
			async: true,
			data:{
				start_date: start_date,
				end_date: end_date,
				selected_partner: selected_partner,
				selected_sales_person: selected_sales_person
			},
			success: function(data, textStatus, xhr) {
				if(data.is_success === true)
				{
					display_bulk_campaign_warning_message(false);
					$('#bulk_download_init_button').prop('disabled', false);
				}
				else
				{
					var error_message = "";
					$.each(data.errors, function(index, value){
						if(error_message !== "")
						{
							error_message += "<br>";
						}
						error_message += value;
					});
					$('#bulk_download_modal').modal('hide');
					set_message_timeout_and_show(error_message, 'alert alert-error', 16000);
				}
				
				if(data.is_pending === true)
				{
					display_bulk_campaign_warning_message(true);
				}
			},
			error: function(xhr, textStatus, error) {
				if(xhr.getAllResponseHeaders())
				{
					$('#bulk_download_modal').modal('hide');
					set_message_timeout_and_show("Error 397017: Bulk campaign data request failed with Server Error", 'alert alert-error', 16000);
				}
			}
		});
	}
	else
	{
		set_message_timeout_and_show('Error 397377: Unable to process bulk data request', 'alert alert-warning', 16000);
	}
}

function display_bulk_campaign_warning_message(show_message)
{
	var body_content = $('#bulk_download_body_content');
	var warning_content = $('#bulk_download_warning_content');
	
	$('.bulk_download_content_div').hide();
	
	if(show_message === true)
	{
		warning_content.show();
	}
	else
	{
		body_content.show();
	}
}

function fix_datatables_fixed_header_offset()
{
	var scroll_navbar_object = $('body > div.navbar.navbar-fixed-top');
	if($('div.FixedHeader_Cloned.fixedHeader.FixedHeader_Header').css('position') == 'fixed')
	{
		var fixed_header_settings = fixed_header.fnGetSettings();
		if(scroll_navbar_object.css('position') == 'fixed')
		{
			fixed_header_settings.oOffset.top = scroll_navbar_object.height();
		}
		else
		{
			fixed_header_settings.oOffset.top = 0;
		}
	}
}

$("#bulk_download_init_button").click(function(){
	check_pending_and_submit_bulk_campaign_request(true);
});

$(document).on("click", '.c_campaign_row_checkbox', function(e){
	highlight_campaign_row($(this), e);
});

function change_action_date_flag(me, campaign_id, series_date)
{
	 if(confirm("Please confirm that this task is completed in Biddr and we can disable the Action Date?")) 
	 {

	 	$.ajax({
		type: "POST",
		url: '/campaigns_main/update_action_date_flag/',
		async: true,
		data: { campaign_id: campaign_id, series_date: series_date },
		dataType: 'json',
		error: function(xhr, textStatus, error)
		{
			set_message_timeout_and_show('Error 49293577: failed to update', 'alert alert-error', 16000);
		},
		success: function(msg)
		{
			if(vl_is_ajax_call_success(msg))
			{
				$( me ).hide(  );
				$(me).prop('disabled', true);
				 
			}
		}
	});
  	}
}

function change_reminder_flag()
{
	var campaign_id = row_data.id;
	var reminder_status = row_data.is_reminder.reminder == 'starred' ? 0 : 1;
	// Update the flag in DB
	$.ajax({
		type: "POST",
		url: '/campaigns_main/update_reminder_flag/',
		async: true,
		data: { campaign_id: campaign_id, reminder_status: reminder_status, report_date: <?php echo $report_date; ?> },
		dataType: 'json',
		error: function(xhr, textStatus, error)
		{
			set_message_timeout_and_show('Error 49293577: failed to update', 'alert alert-error', 16000);
		},
		success: function(msg)
		{
			if(vl_is_ajax_call_success(msg))
			{
				if(row_data.is_reminder.reminder == "starred")
				{
					var inner_html = row_data.is_reminder.formatted_reminder;
					inner_html = inner_html.replace('star-enable','star-disable');
					inner_html = inner_html.replace('Starred','Not Starred');
					inner_html = inner_html.replace('starred','notstarred');
					row_data.is_reminder.formatted_reminder = inner_html;
					row_data.is_reminder.reminder = 'notstarred';
				}
				else
				{
					var inner_html = row_data.is_reminder.formatted_reminder;
					inner_html = inner_html.replace('star-disable','star-enable');
					inner_html = inner_html.replace('Not Starred','Starred');
					inner_html = inner_html.replace('notstarred','starred');
					row_data.is_reminder.formatted_reminder = inner_html;
					row_data.is_reminder.reminder = 'starred';
				}
				campaign_table_new.row(selected_row).data(row_data);
				campaign_table_new.draw(false);
				if(msg.check_action_date)
				{
					$("#action_date_button_"+campaign_id+"").hide();
					$("#action_date_button_"+campaign_id+"").prop('disabled', true);
				}
				if(msg.check_pending)
				{
					$("#c_pending_div_"+campaign_id+"").show();
					$("#c_pending_div_"+campaign_id+"").attr('data-fq-pending-flag', 'true');
				}
			}
		}
	});
}

</script>

<?php 
if($role == 'ops' || $role == 'admin')
{
	$this->load->view('/campaigns_main/healthcheck_js_v2_admin_functions.php');
}
?>
<input type='hidden'  id="new_notes" />
