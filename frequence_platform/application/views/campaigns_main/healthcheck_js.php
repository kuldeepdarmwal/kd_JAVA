<!--CAMPAIGN HEALTHCHECK JAVASCRIPT VIEW -->

<script src="/js/campaign_health/jquery.stickytableheader.js" type="text/javascript"></script> 
<script src="/js/campaign_health/jquery.tablesorter.js" type="text/javascript"></script>
<script src="/js/campaign_health/jquery.notification.js" type="text/javascript"></script>
<script src="/js/campaign_health/xdate.js" type="text/javascript"></script> <!--        http://arshaw.com/xdate/-->
<script src="/js/campaign_health/campaign_health_highcharts.js" type="text/javascript"></script>
<script src="/libraries/external/select2/select2.js"></script>
<script src="/js/highchart/js/highcharts.js"></script>
<script src="/js/highchart/js/modules/exporting.js"></script>

<script src="/bootstrap/assets/js/bootstrap-tooltip.js"></script>
<script type="text/javascript" src="/js/campaign_health/bootstrap-datepicker.js"></script>
<script type="text/javascript">

var bulk_date = new Date();
bulk_date.setDate(bulk_date.getDate()-1);
var bulk_day = bulk_date.getDate();
var bulk_month = bulk_date.getMonth()+1;
var bulk_enddate = ((''+bulk_month).length<2 ? '0' : '') + (bulk_month) + '/' + ((''+bulk_day).length<2 ? '0' : '') + bulk_day + '/' +  bulk_date.getFullYear();

bulk_date = new Date(new Date(bulk_date).setDate(bulk_date.getDate()-30));
var bulk_day = bulk_date.getDate();
var bulk_month = bulk_date.getMonth()+1;
var bulk_startdate = ((''+bulk_month).length<2 ? '0' : '') + (bulk_month) + '/' + ((''+bulk_day).length<2 ? '0' : '') + bulk_day + '/' +  bulk_date.getFullYear();

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
	$("#bd_partner_select").trigger("change");
});

var campaigns = new Array();
var num_rows;
var report_date = '<?php echo $report_date;?>';
var partner_vl = ('<?php echo $partner_id; ?>' == '1' && '<?php echo $role; ?>' != 'sales');
var x_report_date = new XDate(report_date);
var one_month_ago = x_report_date.clone().addMonths(-1, true).toString("yyyy-MM-dd");

$(document).ready(function () {
    campaigns = jQuery.parseJSON('<?php echo addslashes($campaigns);?>');//alert('hello');
    num_rows = campaigns.length;
    //console.debug(campaigns);
    if(num_rows != 0 ){
        document.getElementById('progress_span').innerHTML = 'loaded 0 of '+campaigns.length+'  campaigns ';
        document.getElementById("master_loading_bar").style.width = Math.round(100/num_rows)+'%';
        document.getElementById("master_loading_bar").style.visibility = 'visible';

        append_row_asynch(0);

    }else{
		$("#campaigns_main_filter").hide();
        document.getElementById('progress_span').innerHTML = 'no campaigns found';
		document.getElementById('the_campaign_health_table').outerHTML = "<div><h3>You don't have any campaigns yet. " <?php if ($can_view_mpq) { ?> + "<a href='/mpq'>Request one.</a></h3></div>"<?php } ?>;
    }
});

function format_imprs(imprs,dps){
    //.toFixed(2)
    var scale = Math.pow(10,dps);
    var result = Math.round(scale*imprs/1000)/scale;
    
    return add_commas(result.toFixed(dps))+'k';
}

function format_percent(flt){
    return Math.round((100*flt))+'%'
}

function style_rtg_cell(is_rtg, rtg_w){
    if(null_check(is_rtg)==1 && rtg_w != null){
        if(rtg_w < 0.01 ){
            return '<span class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Retargeting Alert" data-content="Retargeting is enabled but minimal impressions are firing. Potential problem: small cookie audience. Please check advertiser page for cookie."><span class="label label-important"><i class="icon-exclamation-sign icon-white"></i> </span></span>';
        }else{
            return '<span class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Retargeting OK" data-content="Retargeting is enabled and impressions are firing."><span class="label label-success"><i class="icon-thumbs-up icon-white"></i> </span></span>';
        }
    }else{
        return '<p class="muted">-</p>';
    }
}

function format_single_site_weight(w){
    //set alert styling for too heavy top site
    if(w > 0.35){
        //site_weight_style = 'class="red_alert"';
        return '<span class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Site Weight Alert" data-content="Too many impressions on one domain"><span class="label label-important">'+format_percent(w)+' <i class="icon-exclamation-sign icon-white"></i></span></span>';
    }else{
        return format_percent(w);
    }
}

function format_lifetime_oti(lifetime_oti,remaining_imprs,target,reset_date){
    formatted_oti_value = format_percent(lifetime_oti);
    if(partner_vl == 1)
	{
        if(lifetime_oti <1)
		{
            lifetime_delivery_ratio_alert = formatted_oti_value+' of lifetime target';
            lifetime_delivery_ratio_error = 'Need '+format_imprs(remaining_imprs,0)+' impressions by '+reset_date+' ';
            lifetime_delivery_ratio_error += '( '+format_imprs(target,2)+' impressions per day).';
            return '<span class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Lifetime OTI Alert" data-content="'+lifetime_delivery_ratio_error+'"><span class="label label-important">'+formatted_oti_value+'</span></span>';
            
        }
		else if(lifetime_oti > 1.1)
		{
            lifetime_delivery_ratio_alert = formatted_oti_value+' of lifetime target';
            lifetime_delivery_ratio_error = ' Impressions over target';
            return '<span class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Lifetime OTI Alert" data-content="'+lifetime_delivery_ratio_error+'"><span class="label label-info">'+formatted_oti_value+' <i class="icon-fire icon-white"></i></span></span>';
        }
    }
	else
	{
        if(lifetime_oti <0.8)
		{
            return '<span class="label label-warning"> '+formatted_oti_value+' </span>';
        }
		else if(lifetime_oti >= 1.0)
		{
            return '<span class="label label-success"> >100% </span>';
        }

    }
    return formatted_oti_value;
}

function format_cycle_oti(cycle_oti,remaining_imprs,target,reset_date)
{
    formatted_oti_value = format_percent(cycle_oti);
    if(cycle_oti <1){
        cycle_delivery_ratio_alert = formatted_oti_value+' of cycle target';
        cycle_delivery_ratio_error = 'Need '+format_imprs(remaining_imprs,0)+' impressions by '+reset_date+' ';
        cycle_delivery_ratio_error += '( '+format_imprs(target,2)+' impressions per day).';
        return '<span class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Cycle OTI Alert" data-content="'+cycle_delivery_ratio_error+'"><span class="label label-important">'+formatted_oti_value+'</span></span>';
        
    }else if(cycle_oti > 1.1){
        cycle_delivery_ratio_alert = formatted_oti_value+' of lifetime target';
        cycle_delivery_ratio_error = ' Impressions over target';
        return '<span class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Cycle OTI Alert" data-content="'+cycle_delivery_ratio_error+'"><span class="label label-info">'+formatted_oti_value+' <i class="icon-fire icon-white"></i></span></span>';
    }
    return formatted_oti_value;
}

function append_row_asynch(row_num){
    //console.debug(campaigns);
    //get_dates_impressions_async(row_num);
    var data_url = "/campaign_health/get_campaign_results/"+campaigns[row_num].id;

    $.ajax({
        type: "GET",
        url: data_url,
        async: true,
        data: { r_date: report_date , m_date: one_month_ago},
        dataType: 'html',
        error: function(){
            return 'error';
        },
        success: function(msg){ 
            var campaign_results = jQuery.parseJSON(msg);
            var displ_partner = null_check(campaigns[row_num].partner);
			var displ_sales = campaign_results.partner_name;
            var displ_advertiser = null_check(campaigns[row_num].advertiserElem);
            var displ_campaign_name = null_check(campaigns[row_num].c_name);
            var displ_rtg = style_rtg_cell(campaigns[row_num].retargeting, campaign_results.rtg_weight);//null_check(campaigns[row_num].retargeting);
            var displ_target = null_check(campaigns[row_num].target);
            var displ_kill_date = campaign_type_stringify(campaign_results.campaign_type, null_check(campaigns[row_num].end_date));
	    var displ_date_mismatch = campaign_results.ttd_date_mismatch;
	    var displ_days_left = campaigns[row_num].days_left != null? campaigns[row_num].days_left : '-';
            var displ_cycles_live = campaign_results.cycles_live != undefined? (Math.round(100*campaign_results.cycles_live)/100).toFixed(2) : '-';
            var displ_start_date = campaign_results.start_date;
            var displ_cycle_end_date = campaign_results.next_reset != undefined? campaign_results.next_reset : '-';
            var displ_site_weight = campaign_results.site_weight != undefined? format_single_site_weight(campaign_results.site_weight) : '-';
            var displ_total_impressions = campaign_results.lifetime_impressions!=undefined? format_imprs(campaign_results.lifetime_impressions,0) : '-';
	    var displ_total_clicks = campaign_results.lifetime_clicks;
	    var displ_engagement_rate = campaign_results.lifetime_impressions != undefined? Math.round((Number(campaign_results.lifetime_engagements) + Number(campaign_results.lifetime_clicks)) / Number(campaign_results.lifetime_impressions)*100*100)/100 + '%' : '-';
	    var displ_ctr = campaign_results.lifetime_impressions != undefined? Math.round(Number(campaign_results.lifetime_clicks)/Number(campaign_results.lifetime_impressions)*100*100)/100 + '%' : '-' ;    
            var displ_total_OTI = campaign_results.lifetime_OTI != undefined? format_lifetime_oti(campaign_results.lifetime_OTI,campaign_results.lifetime_remaining_impressions,campaign_results.lifetime_target_to_next_bill_date,campaign_results.next_reset) : '-';
            var displ_cycle_impressions = campaign_results.cycle_impressions != undefined? format_imprs(campaign_results.cycle_impressions,1) : '-';
            var displ_cycle_OTI = campaign_results.cycle_OTI != undefined? format_cycle_oti(campaign_results.cycle_OTI,campaign_results.cycle_remaining_impressions,campaign_results.cycle_target_to_next_bill_date,campaign_results.next_reset) : '-';
            var displ_cycle_daily_target = campaign_results.cycle_target_to_next_bill_date != undefined? format_imprs(campaign_results.cycle_target_to_next_bill_date,1) : '-';
            var displ_yday_impressions = campaign_results.yesterday_impressions != undefined? format_imprs(campaign_results.yesterday_impressions,1) : '-';;
            var displ_lt_daily_target = campaign_results.long_term_target != undefined? format_imprs(campaign_results.long_term_target,1) : '-';;
	    var displ_screenshots_ok = style_screenshot_cell(campaign_results.screenshots);
			var displ_landing_page = campaign_results.landing_page;
			var displ_has_tags = campaign_results.has_tags;
			var displ_has_adsets = campaign_results.has_adsets;
			var displ_has_m_adgroup = campaign_results.has_m_adgroup;
			var displ_day_old_adgroup = campaign_results.day_old_adgroup;
			var displ_has_adgroups = campaign_results.has_adgroups;
			var displ_ttd_campaign = campaign_results.ttd_campaign;
            display_row(row_num,campaigns[row_num].id,displ_partner,displ_sales, displ_advertiser,displ_campaign_name,displ_rtg,displ_cycles_live,displ_target,displ_kill_date,displ_start_date,displ_cycle_end_date,displ_site_weight,displ_total_impressions,displ_total_OTI,displ_cycle_impressions,displ_cycle_OTI,displ_cycle_daily_target,displ_yday_impressions,displ_lt_daily_target, displ_landing_page, displ_has_tags, displ_has_adsets, displ_has_m_adgroup, displ_day_old_adgroup, displ_has_adgroups, displ_ttd_campaign, displ_ctr, displ_engagement_rate, displ_screenshots_ok, displ_days_left, displ_date_mismatch);
            $('.tool_poppers').popover();

            document.getElementById('progress_span').innerHTML = 'loaded '+row_num+' of '+campaigns.length+'  campaigns';
            document.getElementById("master_loading_bar").style.width = Math.round(100*(row_num+1)/campaigns.length)+'%';

            //document.getElementById('test_div').innerHTML += '<br><br>'+msg;
            //if(row_num+1 < 10){
            if(row_num+1 < campaigns.length){
                append_row_asynch(row_num+1);
            }else{
                //document.getElementById('test_div').innerHTML += '<br><br>ALL DONE';
                all_rows_loaded();
                document.getElementById("master_loading_bar").style.visibility = 'hidden';
                document.getElementById('progress_span').innerHTML = 'loaded all <i class = "icon-ok-sign"></i> ';
            }
        }
    });
}

function display_row(row_num,c_id,displ_partner, displ_sales, displ_advertiser,display_campaign_name,displ_is_retargeting,displ_cycles_live,displ_target,displ_kill_date,displ_start_date,displ_cycle_end_date,displ_site_weight,displ_total_impressions,displ_total_OTI,displ_cycle_impressions,displ_cycle_OTI,displ_cycle_daily_target,displ_yday_impressions,displ_lt_daily_target, landing_page, has_tags, has_adsets, has_m_adgroup, day_old_adgroup, has_adgroups, has_ttd_campaign, ctr, displ_engagement_rate, displ_screenshots_ok, displ_days_left, displ_date_mismatch){
    var row;
    var campaign = campaigns[row_num];

    //row id is needed for remove_from_healthcheck function
    row =  '<tr id = "'+(row_num+1)+'" >';
    //this is the button to remove from healthcheck
    //row += '<td style="" ><div class="" onclick="remove_from_healthcheck(\''+campaign.id+'\','+(row_num+1)+');"><img src="/images/campaign_health/rubbish.png" alt="Remove" ></div></td>';
	//Salesperson
	row += '<td>'+displ_sales;
    //Partner
    row += '<br>'+displ_partner;

    row +="</td>";
    //Advertiser - Campaign
    if(partner_vl)
	{
        row += '<td><div style="max-width:100%;">'+displ_advertiser+' <br><a target="_blank" style="word-break:break-word;" href="/campaign_setup/'+c_id+'"> <strong>'+display_campaign_name+'<strong></a></div></td>';
    }
	else
	{
        row += '<td>'+displ_advertiser+' <br><strong>'+display_campaign_name+'</strong></td>';
    }
    //RTG?
    row += '<td>'+displ_is_retargeting+'</td>';
    
    //Screenshots
    if(partner_vl)
	{
		row += '<td>'+displ_screenshots_ok+'</td>';
    }
    //Target Impressions ('000)
    row += '<td>'+displ_target+'k </td>';
    //End Date (ie: hard end date for defined period campaigns)
    row += '<td id="end_date_'+c_id+'">';
    if(displ_days_left < 5 && partner_vl)
    {
		row += style_past_end_date_cell(displ_kill_date, displ_days_left);
    }
    else
    {
		row+= displ_kill_date;
    }
    if(partner_vl) 
    {
		if( !displ_date_mismatch.start_match  ||  !displ_date_mismatch.end_match)
		{
			row+= '<span class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Dates Mismatch" data-content="This start/end dates do not match the dates specified in bidder."><span class="label label-info"><i class="icon-exclamation-sign icon-white"></i></span></span>';
		} 
		else if(displ_date_mismatch.error)
		{
			row+= '<span class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Bidder Warning" data-content="Could not retrieve start/end date data from bidder."><span class="label label-info"><i class="icon-warning-sign icon-white"></i></span></span>';
		}
    }
    row += '</td>';
    
    //Days Remaining
    if(partner_vl)
    {
		row += '<td id="days_left_'+c_id+'">'+displ_days_left+'</td>';
    }
    ///11111111
    //Months Live (float)
    row += '<td>'+displ_cycles_live+'</td>';////
    //Start Date (the first day an impression showed up for this campaign)
    row += '<td>'+displ_start_date+'</td>';
    //Bill Date (the next monthly anniversary for the campaign or if a defined period campaign, it's the hard end date)
    row += '<td>'+displ_cycle_end_date+'</td>';
    
    if(partner_vl){
        //Site Weight (the % that the heaviest site makes up in the recent month's impressions)'
        row += '<td>'+displ_site_weight+'</td>';
    }
    //Total Impressions (Lifetime all impressions)
    row += '<td>'+displ_total_impressions+'<br>'+displ_total_OTI+'</td>';
    
    //lifetime OTI
    //row += '<td>'+displ_total_OTI+'</td>';
    if(partner_vl){
        //Cycle Impressions (total impressions between last reset date and report date (if defined period campaign, equal to Total Impressions)
        row += '<td>'+displ_cycle_impressions+'<br>'+displ_cycle_OTI+'</td>';
        
        //cycle OTI
       //row += '<td>'+displ_cycle_OTI+'</td>';
        
        //Cycle Target (daily kImpressions to get to target for this billing cycle)
        row += '<td>'+displ_cycle_daily_target+'</td>';
        //Y'day Realized (Total campaign impressions for report_date)
        row += '<td >'+displ_yday_impressions+'</td>';
        //LT Target (for normal recurring campaign, this is target impressions / 28, for defined period campaigns it's target/expected period
        row += '<td>'+displ_lt_daily_target+'</td>';
	    	//CTR (Clickthrough Rate)
	row += '<td>'+ctr+'</td>';
    
    row += '<td>'+displ_engagement_rate+'</td>';
	

    }
    

    //this is the button to show the charts
    row += '<td style="white-space:nowrap;line-height:2px;"><div class="btn-group">'; 
    if(partner_vl)
    {
	//row +=  '<a class="btn btn-mini btn-inverse" onclick=\'show_removal_confirm_modal('+c_id+',\"'+displ_advertiser+'\", \"'+display_campaign_name+'\",'+(row_num+1)+'); \' title="Remove"><i class="icon-trash icon-white"></i></a>';
	row += "<a class='btn btn-mini btn-inverse' onclick=\' show_removal_confirm_modal("+c_id+", \""+escape(displ_advertiser)+"\", \""+escape(display_campaign_name)+"\", "+(row_num+1)+"); \' title='Remove'><i class='icon-trash icon-white'></i></a>";
    }
    //row +=  '<a class="btn btn-mini btn-success"  title="view reports" onclick="show_detail_id(\''+campaign.id+'\',\''+one_month_ago+'\',\''+report_date+'\',\''+displ_advertiser+'-'+displ_campaign_name+'\');"><i class="icon-signal icon-white"></i></a>';
    row +=  '<a class="btn btn-mini btn-success"  title="Show Details" href="#myModal" data-toggle="modal" onclick="show_detail_modal(\''+c_id+'\',\''+one_month_ago+'\',\''+report_date+'\',\''+displ_advertiser+'-'+display_campaign_name+'\');"><i class="icon-signal icon-white"></i></a>';

	//Landing Page
	if(landing_page.indexOf('http') == -1)
	{
		row += '<a class="btn btn-warning btn-mini" title="Go to landing page - '+landing_page+'" href="http://'+landing_page+'" target="_blank"><i class="icon-home icon-white"></i></a>';
	}
	else
	{
		row += '<a class="btn btn-warning btn-mini" title="Go to landing page - '+landing_page+'"  href="'+landing_page+'" target="_blank"><i class="icon-home icon-white"></i></a>';
	}
	
	if(has_adsets)
	{
		row += '<a class="btn btn-success btn-mini" href="#adset_modal" data-toggle="modal" onclick="show_adset_modal(\''+displ_advertiser+'\', \''+display_campaign_name+'\', '+c_id+')" title="View adsets"><i class="icon-picture icon-white"></i></a>';
	}
	else
	{
		
		row += '<a class="btn btn-mini disabled" title="No adsets available"><div style="width:14px;">&nbsp;</div></a>';
	}
	
	if(has_tags)
	{
		row += '<a class="btn btn-primary btn-mini" title="Download tracking tags"  href="/campaign_health/download_tags/'+c_id+'" target="_blank"><i class="icon-chevron-left icon-white"></i><i class="icon-chevron-right icon-white"></i></a>';
	}
	else
	{
		row += '<a class="btn btn-mini disabled" title="No RTG tags found" ><div style="width:14px;">&nbsp;</div></a>';
	}
	row += '</div><br><br>';
	
    if(partner_vl){
	var daily_target_number = displ_cycle_daily_target.replace('-', '');
	daily_target_number = daily_target_number.replace('k', '');
	daily_target_number = daily_target_number *1000;
	
	row += '<div class="input-prepend input-append" style="float:left;">'
	 if(has_adgroups)
	    {
		row += '<a class="btn btn-mini btn-info"  data-toggle="modal" onclick="show_adgroup_modal(\''+displ_advertiser+'\', \''+display_campaign_name+'\', '+c_id+')" title="Edit TTD Campaign/Adgroups"><i class="icon-edit icon-white"></i></a>';
	    }
	    else
	    {
		if(has_ttd_campaign)
		{
			row += '<a class="btn btn-mini btn-info "  data-toggle="modal" onclick="show_campaign_modal(\''+displ_advertiser+'\', \''+display_campaign_name+'\', '+c_id+')" title="Edit TTD Campaign"><i class="icon-edit icon-white"></i></a>';
		}
		else
		{
		    row += '<a class="btn btn-mini btn-info disabled" title="No TTD Adgroups or campaign found." ><i class="icon-remove icon-white"></i></a>';
		}
	    }
	
	if(has_m_adgroup)
	{
	    
	    
	    row += '<input style="width:60px;padding:0;" onclick="this.focus();this.select();" id="managed_target_box_'+c_id+'" type="text" value="'+daily_target_number*1.02+'"/><button class="btn btn-info btn-mini" title="Update Daily Impression Target" onclick="update_impression_target('+c_id+', this);"><i class="icon-retweet icon-white"></i></button></div>';
	    if(day_old_adgroup)
	    {
		row += '<div id="date_stamp_'+c_id+'" class="label label-warning" title="Adgroup/Campaign modified within the last 24 hours." style="float:left;position:relative;top:5px; left:2px;"><i class="icon-warning-sign icon-white"></i></div>';
	    }
	    else
	    {		
		row += '<div id="date_stamp_'+c_id+'" class="label label-success" title="Adgroup/Campaign not recently modified" style="float:left;position:relative;top:2px; left:2px;"><i class="icon-hand-left icon-white"></i></div>';
	    }
	    
	    
	    

	}
	else
	{
	    row += '<input style="width:60px;padding:0;" id="managed_target_box_'+c_id+'" type="text" value="---" disabled /><button class="btn btn-mini btn-info disabled" title="No TTD Adgroups found for this campaign." ><i class="icon-remove icon-white"></i></button></div>'
	    if(day_old_adgroup)
	    {
		row += '<div id="date_stamp_'+c_id+'" class="label label-warning" title="Campaign modified within the last 24 hours." style="float:left;position:relative;top:2px; left:2px;"><i class="icon-warning-sign icon-white"></i></div>';
	    }
	    else
	    {		
		row += '<div id="date_stamp_'+c_id+'" class="label label-success" title="Campaign not recently modified" style="float:left;position:relative;top:2px; left:2px;"><i class="icon-hand-left icon-white"></i></div>';
	    }
	    row += "</div>";
	    }
	    row += "</td>";
	row += "<td style='text-align:center;vertical-align:middle;'><span class='label' id='marker_"+c_id+"' onclick='ok_toggle(this);'><i class='icon-thumbs-down icon-white'></i></span></td>";
	
    }
    //row += '<td><div class="" onclick="show_detail_id(\''+campaign.id+'\',\''+one_month_ago+'\',\''+report_date+'\',\''+displ_advertiser+'-'+displ_campaign_name+'\');"><img class="resize" src="/images/campaign_health/chart.png" alt="Detail" ></div></td>';
	//Retargeting Script
    row += '</tr>';
    $("#table_body").append(row);
}

function show_removal_confirm_modal(c_id, advertiser_name,campaign_name, row_num)
{
    $("#confirm_modal_detail_body").html("Are you sure you want to graveyard <strong>"+unescape(advertiser_name)+" - "+unescape(campaign_name)+"</strong>?");
    $("#confirm_modal").modal("show");
    $("#confirm_delete_button").click(function() {remove_from_healthcheck(c_id, row_num);});
    var thing = 'remove_from_healthcheck(\''+c_id+'\','+(row_num)+')';
}

function show_campaign_modal(advertiser_name, campaign_name, campaign_id)
{
 	document.getElementById('adgroup_modal_detail_body').innerHTML = temp;
 	document.getElementById('adgroup_modal_detail_header').innerHTML = advertiser_name+' - '+campaign_name;
	//var temp = document.getElementById('adgroup_modal_detail_body').innerHTML;
	
	document.getElementById('adgroup_modal_detail_body').innerHTML = '<div class="progress progress-striped active"> <div class="bar" style="width: 100%;"></div></div>';
	document.getElementById('ttd_modal_footer').innerHTML = '<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a><button id="modify_button" type="submit" class="btn btn-info disabled"><i class="icon-retweet icon-white"></i> Update</button>';
	$("#adgroup_modal").modal("show");
	var data_url = "/tradedesk/get_ttd_campaign_data/"+campaign_id;
	    $.ajax({
        type: "GET",
        url: data_url,
        async: true,
        data: {},
        dataType: 'html',
        error: function(){
			document.getElementById('adgroup_modal_detail_body').innerHTML = temp;
            return 'error';
        },
        success: function(msg){ 

            var returned_data = jQuery.parseJSON(msg)
			if(returned_data.success)
			{
				
				
				document.getElementById('adgroup_modal_detail_body').innerHTML = temp;
				
				$("#adgroups_options").hide();
				
				if(returned_data.day_old_modify)
				{
				    document.getElementById('c_timestamp_box').className = "label label-warning";
				    document.getElementById('c_timestamp_box').innerHTML = "Changes pending: Last updated " + returned_data.modify_timestamp + " GMT";
				}
			    	else 
				{
				    document.getElementById('c_timestamp_box').className = "label label-success";
				    document.getElementById('c_timestamp_box').innerHTML = "Reliable Data: Last updated " + returned_data.modify_timestamp + " GMT";
				}
				
				
				document.getElementById('impression_budget_box').value = returned_data.c_impressions;
				document.getElementById('dollar_budget_box').value = returned_data.c_dollars;
				document.getElementById('start_date_box').value = returned_data.c_start_date;
				document.getElementById('end_date_box').value = returned_data.c_end_date;
				
				document.getElementById('ttd_modal_footer').innerHTML = '<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a><button id="c_modify_button" onclick="campaign_form_update('+campaign_id+')" type="submit" class="btn btn-info"><i class="icon-retweet icon-white"></i> Update</button>';
				
				
			}
			else
			{
				
				alert(returned_data.err_msg);
				$("#adgroup_modal").modal("hide");
				window.setTimeout(function(){}, 1500);
			}
        }
    });
}



function show_adgroup_modal(advertiser_name, campaign_name, campaign_id)
{
 	document.getElementById('adgroup_modal_detail_header').innerHTML = advertiser_name+' - '+campaign_name;
	
	document.getElementById('adgroup_modal_detail_body').innerHTML = '<div class="progress progress-striped active"> <div class="bar" style="width: 100%;"></div></div>';
	document.getElementById('ttd_modal_footer').innerHTML = '<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a><button id="modify_button" type="submit" class="btn btn-info disabled"><i class="icon-retweet icon-white"></i> Update</button>';
	$("#adgroup_modal").modal("show");
	var data_url = "/tradedesk/get_adgroup_data/"+campaign_id;
	    $.ajax({
        type: "GET",
        url: data_url,
        async: true,
        data: {},
        dataType: 'html',
        error: function(){
			document.getElementById('adgroup_modal_detail_body').innerHTML = temp;
            return 'error';
        },
        success: function(msg){ 

            var returned_data = jQuery.parseJSON(msg)
			if(returned_data.success)
			{	
				adgroups = returned_data.adgroup_ids;
				document.getElementById('adgroup_modal_detail_body').innerHTML = returned_data.form_html;
				document.getElementById('ttd_modal_footer').innerHTML = '<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a><button id="modify_button" onclick="big_form_update('+campaign_id+')" type="submit" class="btn btn-info"><i class="icon-retweet icon-white"></i> Update</button>';
				
			}
			else
			{
				$("#adgroup_modal").modal("hide");
				alert(returned_data.err_msg);
			}
        }
    });
}

function is_zero(input)
{
    return (input == 0)||(input == "");
}

function big_form_update(campaign_id)
{
	var c_impression_budget = document.getElementById('impression_budget_box').value;
	var c_dollar_budget = document.getElementById('dollar_budget_box').value;
	var c_start_date = document.getElementById('start_date_box').value;
	var c_end_date = document.getElementById('end_date_box').value;
	
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
	    adgroup_data[adgroups[i]]['is_enabled'] = document.getElementById(adgroups[i]+'_is_enabled_checkbox').checked;
	    adgroup_data[adgroups[i]]['impression_budget'] = document.getElementById(adgroups[i]+'_impression_budget_box').value;
	    adgroup_data[adgroups[i]]['dollar_budget'] = document.getElementById(adgroups[i]+'_dollar_budget_box').value;
	    adgroup_data[adgroups[i]]['daily_impressions'] = document.getElementById(adgroups[i]+'_daily_impression_box').value;
	    adgroup_data[adgroups[i]]['daily_dollars'] = document.getElementById(adgroups[i]+'_daily_dollar_box').value;
	    
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
	   document.getElementById('impression_budget_box').value = "0";
   }
   if(c_dollar_budget == "")
   {
	   document.getElementById('dollar_budget_box').value = "0";
   }
   
 
    document.getElementById('ttd_modal_footer').innerHTML = '<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a><button id="modify_button" type="submit" class="btn btn-info disabled">Updating...</button>';

	var data_url = "/tradedesk/update_campaign_from_big_form/";
		$.ajax({
		type: "POST",
		url: data_url,
		async: true,
		data: {c_id: campaign_id, c_imps: c_impression_budget, c_bux: c_dollar_budget, c_start: c_start_date, c_end: c_end_date, adgroup_data: JSON.stringify(adgroup_data)},
		dataType: 'html',
		error: function(){
			
			return 'error';
		},
		success: function(msg){
		//console.log(msg);
			var returned_data = jQuery.parseJSON(msg)
			if(returned_data.success)
			{
				document.getElementById('ttd_modal_footer').innerHTML = '<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a><button id="modify_button" onclick="big_form_update('+campaign_id+')" type="submit" class="btn btn-info"><i class="icon-retweet icon-white"></i> Update</button>';
				
				document.getElementById('c_timestamp_box').className = "label label-warning";
				document.getElementById('c_timestamp_box').innerHTML = "Changes Pending: Just updated";
				
				for(var i = 0; i < adgroups.length; i++)
				{
				    document.getElementById(adgroups[i]+'_timestamp_box').className = "label label-warning";
				    document.getElementById(adgroups[i]+'_timestamp_box').innerHTML = "Changes Pending: Just updated";
				}

				
                            var timebox = document.getElementById("date_stamp_"+campaign_id);
                            timebox.className = "label label-warning";
                            timebox.innerHTML = '<i class="icon-warning-sign icon-white"></i>';
                            timebox.title = "You just modified this adgroup.";
                            if(returned_data.date_check != '-')
                            {
                                if(returned_data.date_check < 5)
                                {
                                    document.getElementById('end_date_'+campaign_id).innerHTML = style_past_end_date_cell(campaign_type_stringify(returned_data.campaign_type, returned_data.end_date),returned_data.date_check);
                                }
                                else
                                {
                                    document.getElementById('end_date_'+campaign_id).innerHTML = campaign_type_stringify(returned_data.campaign_type, returned_data.end_date);
                                }
                            }
                            else
                            {
                                document.getElementById('end_date_'+campaign_id).innerHTML = campaign_type_stringify(returned_data.campaign_type, returned_data.end_date);
                            }
                            document.getElementById('days_left_'+campaign_id).innerHTML = returned_data.date_check;

				
			}
			else
			{
				alert(returned_data.err_msg);
				document.getElementById('ttd_modal_footer').innerHTML = '<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a><button id="modify_button" onclick="big_form_update('+campaign_id+')" type="submit" class="btn btn-info"><i class="icon-retweet icon-white"></i> Update</button>';

			}
		}
	});
   
}

function campaign_form_update(campaign_id)
{
    var c_impression_budget = document.getElementById('impression_budget_box').value;
    var c_dollar_budget = document.getElementById('dollar_budget_box').value;
    var c_start_date = document.getElementById('start_date_box').value;
    var c_end_date = document.getElementById('end_date_box').value;
    

    
    var nan_msg = "The following are invalid, non-number inputs:\n";
    var nan_flag = false;
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
    if(nan_flag)
    {
	alert(nan_msg);
	return;
    }

    
    document.getElementById('ttd_modal_footer').innerHTML = '<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a><button id="modify_button" type="submit" class="btn btn-info disabled">Updating...</button>';
    
    var data_url = "/tradedesk/update_campaign_from_campaign_form/";
	    $.ajax({
        type: "POST",
        url: data_url,
        async: true,
        data: {c_id: campaign_id, c_imps: c_impression_budget, c_bux: c_dollar_budget, c_start: c_start_date, c_end: c_end_date},
        dataType: 'html',
        error: function(){
			
            return 'error';
        },
        success: function(msg){
	    //console.log(msg);
            var returned_data = jQuery.parseJSON(msg)
			if(returned_data.success)
			{
			    document.getElementById('ttd_modal_footer').innerHTML = '<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a><button id="c_modify_button" onclick="campaign_form_update('+campaign_id+')" type="submit" class="btn btn-info"><i class="icon-retweet icon-white"></i> Update</button>';
			    document.getElementById('c_timestamp_box').className = "label label-warning";
			    document.getElementById('c_timestamp_box').innerHTML = "Changes Pending: Just updated"
			    var timebox = document.getElementById("date_stamp_"+campaign_id);
			    timebox.className = "label label-warning";
			    timebox.innerHTML = '<i class="icon-warning-sign icon-white"></i>';
			    timebox.title = "You just modified this adgroup.";
			    
			    
			}
			else
			{
			    alert(returned_data.err_msg);
				document.getElementById('ttd_modal_footer').innerHTML = '<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a><button id="c_modify_button" onclick="campaign_form_update('+campaign_id+')" type="submit" class="btn btn-info"><i class="icon-retweet icon-white"></i> Update</button>';

			}
        }
    });
   
}


function show_adset_modal(advertiser_name, campaign_name, campaign_id)
{
	document.getElementById('adset_modal_detail_header').innerHTML = advertiser_name+' - '+campaign_name+" Adsets";
	document.getElementById('adset_modal_detail_body').innerHTML = '<div class="progress progress-striped active"> <div class="bar" style="width: 100%;"></div></div>';
	var data_url = "/campaign_health/get_adsets/"+campaign_id;
    $.ajax({
        type: "GET",
        url: data_url,
        async: true,
        data: {},
        dataType: 'html',
        error: function(){
			document.getElementById('adset_modal_detail_body').innerHTML = "";
            return 'error';
        },
        success: function(msg){ 
            //console.debug(msg);
            var returned_data = jQuery.parseJSON(msg)
			if(returned_data.success)
			{
				document.getElementById('adset_modal_detail_body').innerHTML = returned_data.output;
			}
			else
			{
				document.getElementById('adset_modal_detail_body').innerHTML = "";
			}
        }
    });
}

function null_check(value){
    return ((value===null)? '-' : value);
}

function ok_toggle(box)
{
  
    if(box.className == 'label')
    {
	box.className = "label label-info";
	box.innerHTML = "<i class='icon-thumbs-up icon-white'></i>";
    }
    else
    {
	box.className = "label";
	box.innerHTML = "<i class='icon-thumbs-down icon-white'></i>";
    }
}


function is_int(str) {
    return /^\d+$/.test(str);
}

function update_impression_target(c_id, butan)
{
 
  
    var box_name = 'managed_target_box_'+c_id;
    var new_target = document.getElementById(box_name).value;
    var data_url = "/tradedesk/update_impression_target";
    if(Number(new_target) == 0)
    {
	alert("Managed Adgroup budget targets can't be 0");
	return;
    }
    if (!is_int(new_target))
	{
	    alert("Daily impression targets can only be integers. Enter an integer and try again.");
	    return;
	}
    butan.className = "btn btn-info btn-mini disabled";
   $.ajax({
        type: "POST",
        url: data_url,
        async: true,
        data: {c_id: c_id, new_target: new_target},
        dataType: 'html',
        error: function(r){
	   // console.dir(r);
	    //console.log(JSON.stringify(r));
	    alert("Uh oh");
        },
        success: function(msg){ 
	    var returned_data = jQuery.parseJSON(msg)
	    butan.className = "btn btn-info btn-mini";
	    if(returned_data.success)
	    {
		 
		  var timebox = document.getElementById("date_stamp_"+c_id);
		  timebox.className = "label label-warning";
		  timebox.innerHTML = '<i class="icon-warning-sign icon-white"></i>';
		  timebox.title = "You just modified this adgroup.";
	    }
	    else
	    {
		alert(returned_data.err_msg);
	    }
        }
    });
	
}

function add_commas(nStr)
{
    nStr += '';
    x = nStr.split('.');
    x1 = x[0];
    x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
    }
    return x1 + x2;
}

function all_rows_loaded(){
    //$(".tablesorter").tablesorter();
    $('.tool_poppers').popover();
	element = document.getElementById("the_campaign_health_table");

    /* Here is dynamically created a form */
    var form = document.createElement('form');
    form.setAttribute('class', 'filter');
    form.setAttribute('action', "javascript:void(0);");
    // For ie...
    form.attributes['class'].value = 'filter';
    form.action = "javascript:void(0);";
    var input = document.createElement('input');
    input.onkeyup = function() {
        filterTable(input, element);
    }
    form.appendChild(input);
    element.parentNode.insertBefore(form, element);

    $("#the_campaign_health_table").stickyTableHeaders();
}

function remove_from_healthcheck(campaign,id){
    $.ajax({
        type: "GET",
        url: "/campaign_health/remove_from_healthcheck/", 
        data: { campaign: campaign },
        dataType: 'html'
    }).done(function( msg ) {
        var response = jQuery.parseJSON(msg);
        var ui_message;
        if(response !== false){
            delete_row(id);
            ui_message = "deleted campaign ID:" + campaign;
        }else{
            ui_message = "there was an error with the database";
        }

        $.createNotification({
            content: ui_message,
            duration: null,
            horizontal: 'left',
            vertical: 'top',
            duration: 3000,
            click: function() {
                this.hide();
            }
        });
        
    });
}

function numberWithCommas(x) {
    var parts = x.toString().split(".");
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    return parts.join(".");
}

function delete_row(rowid)  
{   
    var row = document.getElementById(rowid);
    row.parentNode.removeChild(row);
}

function style_screenshot_cell(displ_screenshots_ok)
{
     if(displ_screenshots_ok)
    {
	return '<span class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Screenshots OK!" data-content="Recent ad placements found and screenshots have been made. They can be found on the report page."><span class="label label-success"><i class="icon-thumbs-up icon-white"></i> </span></span>';

    }
    else
    {
	return '<span class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Screenshot Alert" data-content="No screenshots have been taken for this campaign this cycle."><span class="label label-important"><i class="icon-exclamation-sign icon-white"></i> </span></span>';
    }
}

function style_past_end_date_cell(end_date, days_left)
{
    if(days_left < 0)
    {
     return '<span class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="End Date Alert" data-content="This end date has already passed."><span class="label label-important"><i class="icon-exclamation-sign icon-white"></i> '+end_date+'</span></span>';
    }
    if(days_left == 0)
    {
	return '<span class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="End Date Alert" data-content="Today is the last day for this campaign."><span class="label label-warning"><i class="icon-warning-sign icon-white"></i> '+end_date+'</span></span>';
    }
    else
    {
	return '<span class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="End Date Alert" data-content="This campaign is ending in '+days_left+' day(s)."><span class="label"><i class="icon-warning-sign icon-white"></i> '+end_date+'</span></span>';
    }
}

function campaign_type_stringify(end_type, kill_date)
{
    var output = "";
    switch(end_type)
    {
	case "FIXED_TERM":
	    output = "Fixed term until <br>"+kill_date;
	    break;
	case "MONTH_END_WITH_END_DATE":
	    output = "Month-end until <br> "+kill_date;
	    break;
	case "MONTHLY_WITH_END_DATE":
	    output = "Monthly until <br>"+kill_date;
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
	$('#bulk_download_init_button').prop('disabled', true);
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

					$('#bulk_download_init_button').prop('disabled', false);
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
};


</script>
<script type="text/javascript" src="/js/campaign_health/filter_table.js"></script>

