<!DOCTYPE HTML>
<html>
<head>
	<title>STAY HEALTHY</title>
        <link href='http://fonts.googleapis.com/css?family=Oxygen' rel='stylesheet' type='text/css'>
        <link href='http://fonts.googleapis.com/css?family=Homenaje' rel='stylesheet' type='text/css'>
        <link rel="shortcut icon" href="/images/campaign_health/hospital.png">
	<link rel="stylesheet" media="all" href="../css/campaign_health/campaign_health_index_style.css" type="text/css">
        <link rel="stylesheet" href="../css/campaign_health/notification.css">
        
        <script src="/js/campaign_health/jquery.min.js" type="text/javascript"></script>
        
        <script src="../js/campaign_health/jquery.stickytableheader.js" type="text/javascript"></script> 
	<script src="../js/campaign_health/jquery.tablesorter.js" type="text/javascript"></script>
        <script src="../js/campaign_health/jquery.notification.js" type="text/javascript"></script>
        <script src="../js/campaign_health/xdate.js" type="text/javascript"></script> <!--        http://arshaw.com/xdate/-->
        <script src="../js/campaign_health/campaign_health_highcharts.js" type="text/javascript"></script>
   
        <script src="/js/highchart/js/highcharts.js"></script>
        <script src="/js/highchart/js/modules/exporting.js"></script>

        <script type="text/javascript">
                var timer;
                var campaigns = new Array();
                var num_rows;
                var report_date = '<?php echo $report_date;?>';
                var x_report_date = new XDate(report_date);
                var one_month_ago = x_report_date.clone().addMonths(-1, true).toString("yyyy-MM-dd");
                
                $(document).ready(function () {
                 campaigns = jQuery.parseJSON('<?php echo addslashes($campaigns);?>');//alert('hello');
                 num_rows = campaigns.length;
                 document.getElementById('progress_span').innerHTML = '[loaded 0 / '+num_rows+']';
                 run();
                });
                
                function run(){
                    var ii=0;
                    function myFunc() {
                        timer = setTimeout(myFunc, 0);
                        append_row(campaigns[ii],ii);
                        ii++;
                        document.getElementById('progress_span').innerHTML = '[loaded '+ii+'/'+num_rows+']';
                        if(ii >= num_rows) {
                        //if(ii >= 5) {
                            document.getElementById('progress_span').innerHTML = '[loaded all]';
                            stop();
                        }
                    }
                    timer = setTimeout(myFunc, 0);
                }
                

                function stop() {
                    clearInterval(timer);
                    all_rows_loaded();
                }
                
                
                function append_row(campaign,row_num){

                    var row;
                    var long_term_target;
                    var projected_campaign_days;
                    var reset_date;
                    var next_bill_date;
                    var cycle_impressions
                    var x_bill_date;
                    var cycle;
                    var yesterdays;
                    var yesterdays_impressions;
                    var cycle_days_left;
                    var cycle_impressions_left;
                    var cycle_target;
 
                    var dates_impressions; 
                    
                    //initialize cell values for this row
                    //Partner
                    var displ_partner = null_check(campaign.partner);
                    //Advertiser
                    var displ_advertiser = null_check(campaign.advertiserElem);
                    //Campaign
                    var displ_campaign_name = null_check(campaign.c_name);
                    //RTG?
                    var displ_is_retargeting = null_check(campaign.retargeting);
                    //Target Impressions ('000)
                    var displ_target_imprs = add_commas(null_check(campaign.target));
                    //End Date (ie: hard end date for defined period campaigns)
                    var displ_hard_end = null_check(campaign.end_date);
                    //Months Live (float)
                    var displ_months_live = '-';
                    //Start Date (the first day an impression showed up for this campaign)
                    var displ_start_date = '-';
                    //Bill Date (the next monthly anniversary for the campaign or if a defined period campaign, it's the hard end date)
                    var displ_bill_date = '-';
                    //Site Weight (the % that the heaviest site makes up in the recent month's impressions)'
                    var displ_single_site_weight;
                    //Total Impressions (Lifetime all impressions)
                    var displ_delivered_imprs = 0;
                    //Cycle Impressions (total impressions between last reset date and report date (if defined period campaign, equal to Total Impressions)
                    var displ_cycle_imprs = 0;
                    //Cycle Target (daily kImpressions to get to target for this billing cycle)
                    var displ_cycle_target = '-';
                    //Y'day Realized (Total campaign impressions for report_date)
                    var displ_yday_realized = 0;
                    //LT Target (for normal recurring campaign, this is target impressions / 28, for defined period campaigns it's target/expected period
                    var displ_long_term_target = '-';
                    
                    var d0;
                    var d1;
                    var months_live;
                    
                    ///formatting variables
                    var days_live;
                    var projected_campaign_life_days;
                    var lifetime_delivery_ratio;
                    var lifetime_delivery_ratio_alert;
                    var lifetime_delivery_ratio_error;
                    var lifetime_remaining_impressions ;
                    var lifetime_target_till_next_bill_date;
                    
                    var cycle_delivery_ratio;
                    var cycle_delivery_ratio_alert;
                    var cycle_delivery_ratio_error;
                    var cycle_remaining_impressions ;
                    var cycle_target_till_next_bill_date;
                    var cycle_days;
                    var cycle_days_left;
                    
                    var yesterday_style;
                    var hard_end_date_style = " ";
                    
                    var weights;
                    var rtg_style = " ";
                    var site_weight_style = " ";
                    
                    dates_impressions = get_dates_impressions(campaign.id);//returns start_date(1st observed impression date), end_date(latest observed impression date) and all impressions for campaign
                 
                    if(dates_impressions[0].start_date != null && dates_impressions[0].end_date != null ){//if no impression records yet, this is probably a new campaign or perhaps an errored entry and we shouldn't be doing any algebra on the campaign'

                        d0 = new XDate(dates_impressions[0].start_date);//date format for first impressions seen date
                        d1 = new XDate(dates_impressions[0].end_date);//date format for last impressions seen date

                     
                        months_live = d0.diffMonths(d1);//returns month float
                        days_live = d0.diffDays(d1);//
                        
                        if(campaigns[row_num].end_date === null){///normal recurring campaign
                            projected_campaign_days = 28;//use 28 so we slightly overdeliver
                            
                            if(campaign.first_reset_date!=null){///if there is a value set for first rest date we need calculate "next bill date"
                                x_first_reset = new XDate(campaign.first_reset_date);
                                var months_since_first_reset = x_first_reset.diffMonths(report_date);
                                x_bill_date = x_first_reset.clone().addMonths(Math.round(months_since_first_reset+0.5), true);
                            }else{
                                x_bill_date = d0.clone().addMonths(Math.round(months_live+0.5), true);
                            }
                            next_bill_date = x_bill_date.toString("yyyy-MM-dd");
                            projected_campaign_life_days = d0.diffDays(x_bill_date);
                            reset_date = x_bill_date.clone().addMonths(-1, false).toString("yyyy-MM-dd");//previous reset date
                            
                            cycle_days = -x_bill_date.diffDays(reset_date);
                            cycle_impressions = get_total_impressions(campaign.id, reset_date, report_date)[0].total_impressions;//get total campaign impressions between two dates
                            
                            lifetime_remaining_impressions = campaign.target*Math.round(months_live+0.5) - dates_impressions[0].total_impressions/1000;//how many impressions do we need to get square by next bill date
                            lifetime_target_till_next_bill_date = (campaign.target*Math.round(months_live+0.5) - dates_impressions[0].total_impressions/1000)/(projected_campaign_life_days-days_live);//what's the daily target kImpressions level we need to get square by next bill date'
                            lifetime_delivery_ratio = (Math.round(dates_impressions[0].total_impressions/1000))/((days_live/projected_campaign_life_days)*campaign.target*Math.round(months_live+0.5));//impressions delivered / impressions we should have delivered by now
                           
                        }else{///defined period campaigns
                            var x_hard_end = new XDate(campaign.end_date);
                            projected_campaign_days = d0.diffDays(x_hard_end);
                            x_bill_date = new XDate(campaign.end_date);
                            next_bill_date = x_bill_date.toString("yyyy-MM-dd");
                            projected_campaign_life_days = projected_campaign_days;
                            reset_date = dates_impressions[0].start_date;
                            
                            cycle_days = -x_bill_date.diffDays(reset_date);
                            cycle_impressions = dates_impressions[0].total_impressions;
                            
                            lifetime_remaining_impressions = campaign.target - (cycle_impressions/1000);
                            lifetime_target_till_next_bill_date = lifetime_remaining_impressions/(projected_campaign_life_days-days_live);
                            lifetime_delivery_ratio = (cycle_impressions/1000)/((days_live/projected_campaign_life_days)*campaign.target);
                            //alert('imprs: '+(cycle_impressions/1000)+' days live: '+days_live+' projected days: '+projected_campaign_life_days+' should be: '+((days_live/projected_campaign_life_days)*campaign.target));
                            hard_end_date_style = (lifetime_delivery_ratio<1) ? 'class="red_alert"' : " ";
                        }

                        cycle_days_left = x_report_date.diffDays(next_bill_date);
                        cycle_impressions_left = campaign.target - (cycle_impressions/1000);
                        cycle_target = cycle_impressions_left/(cycle_days_left);
                        cycle_delivery_ratio = (cycle_impressions/1000)/(((cycle_days-cycle_days_left)/cycle_days)*campaign.target);
                       
                        yesterdays = get_total_impressions(campaign.id, report_date, report_date);
                        yesterdays_impressions = null_check(yesterdays[0].total_impressions);
                        
                        long_term_target = campaign.target/projected_campaign_days;
                        
                        if((yesterdays_impressions/1000) > long_term_target + 1){
                            //over
                            yesterday_style = 'class="blue_alert"';
                        }else if((yesterdays_impressions/1000) < long_term_target ){
                            //under
                            yesterday_style = 'class="red_alert"';
                        }else{
                            //ok
                            yesterday_style = " ";
                        }
                        
                        displ_months_live = (Math.round(10*months_live)/10).toFixed(1);
                        displ_start_date = dates_impressions[0].start_date;
                        displ_bill_date = next_bill_date;
                        displ_delivered_imprs = add_commas(Math.round(dates_impressions[0].total_impressions/1000));
                        displ_cycle_imprs = add_commas(Math.round(cycle_impressions/1000));
                        displ_cycle_target = cycle_target.toFixed(1);
                        displ_yday_realized = add_commas((yesterdays_impressions/1000).toFixed(1));
                        displ_long_term_target = add_commas(long_term_target.toFixed(1));
                        
                        ///build tooltip for Total Impressions column
                        if(lifetime_delivery_ratio <1){
                            lifetime_delivery_ratio_alert = 'ALERT: '+Math.round((100*lifetime_delivery_ratio))+'% of lifetime target<br>';
                            lifetime_delivery_ratio_error = 'Need '+lifetime_remaining_impressions.toFixed(0)+'k impressions by '+next_bill_date+'<br>';
                            lifetime_delivery_ratio_error += '( '+lifetime_target_till_next_bill_date.toFixed(1)+'k impressions per day)<br>';
                            displ_delivered_imprs = '<a class="tooltip" href="#">'+displ_delivered_imprs+'<span class="custom info"><img src="/images/campaign_health/alert_warning.png" alt="Information" height="48" width="48" /><em>'+lifetime_delivery_ratio_alert+'</em>'+lifetime_delivery_ratio_error+'</span></a><img src="/images/campaign_health/warning.png" height=10 width=10>';
                        }
                        
                        ///build tooltip for Cyclel Impressions column
                        if(cycle_delivery_ratio <1){
                            cycle_delivery_ratio_alert = 'ALERT: '+Math.round((100*cycle_delivery_ratio))+'% of cycle target<br>';
                            cycle_delivery_ratio_error = 'Need '+cycle_impressions_left.toFixed(0)+'k impressions by '+next_bill_date+'<br>';
                            cycle_delivery_ratio_error += '( '+cycle_target.toFixed(1)+'k impressions per day)<br>';
                            displ_cycle_imprs = '<a class="tooltip" href="#">'+displ_cycle_imprs+'<span class="custom info"><img src="/images/campaign_health/alert_warning.png" alt="Information" height="48" width="48" /><em>'+cycle_delivery_ratio_alert+'</em>'+cycle_delivery_ratio_error+'</span></a><img src="/images/campaign_health/warning.png" height=10 width=10>';
                        }
                        
                        
                        //returns top site impressions / total impressions and rtg impressions / total impressions for the last month
                        weights = get_impression_weights(campaign.id,one_month_ago, report_date);
                        //alert(weights.single_site_weight) ;
                        
                        displ_single_site_weight = Math.round(100*weights.single_site_weight)+'%';
                        
                        
                        //set alert styling for too heavy top site
                        if(weights.single_site_weight > 0.20){
                            site_weight_style = 'class="red_alert"';
                        }
                        
                        //set alert for low impressions for retargting campaigns
                        if(weights.rtg_weight < 0.01  && (displ_is_retargeting==1)){//if rtg impressions are low
                            rtg_style = 'class="red_alert"';
                        }
                        //set alert for found rtg impressions for non-rtg camapigns
                        if(weights.rtg_weight > 0  && (displ_is_retargeting==0)){//or if db has rtg off
                            rtg_style = 'class="red_alert"';
                        }
                       
                        
                        
               
                    }else{//if no impressions found yet, there are some calcs we can do
                        if(campaigns[row_num].end_date === null){
                            projected_campaign_days = 28;
                        }else{
                            projected_campaign_days = x_report_date.diffDays(x_hard_end);
                        }
                        long_term_target = campaign.target/projected_campaign_days;
                        displ_long_term_target = add_commas(long_term_target.toFixed(1));
                        displ_cycle_target = displ_long_term_target;
                    }
                 
                 
                 
                   
                 
                 
                    //row id is needed for remove_from_healthcheck function
                    row =  '<tr id = "'+(row_num+1)+'" class="expand">';
                    //this is the button to remove from healthcheck
                    row += '<td style="" ><div class="clickable_icon" onclick="remove_from_healthcheck(\''+campaign.id+'\','+(row_num+1)+');"><img class="resize" src="/images/campaign_health/rubbish.png" alt="Remove" ></div></td>';
                    //Partner
                    row += '<td>'+displ_partner+'</td>';
                    //Advertiser
                    row += '<td>'+displ_advertiser+'</td>';
                    //Campaign
                    row += '<td><a <a target="_blank" href="/campaign_setup/'+campaign.id+'">'+displ_campaign_name+'</a></td>';
                    //RTG?
                    row += '<td '+rtg_style+' >'+displ_is_retargeting+'</td>';
                    //Target Impressions ('000)
                    row += '<td>'+displ_target_imprs+'</td>';
                    //End Date (ie: hard end date for defined period campaigns)
                    row += '<td '+hard_end_date_style+' >'+displ_hard_end+'</td>';
                    //Months Live (float)
                    row += '<td>'+displ_months_live+'</td>';
                    //Start Date (the first day an impression showed up for this campaign)
                    row += '<td>'+displ_start_date+'</td>';
                    //Bill Date (the next monthly anniversary for the campaign or if a defined period campaign, it's the hard end date)
                    row += '<td>'+displ_bill_date+'</td>';
                    //Site Weight (the % that the heaviest site makes up in the recent month's impressions)'
                    row += '<td '+site_weight_style+' >'+displ_single_site_weight+'</td>';
                    //Total Impressions (Lifetime all impressions)
                    row += '<td>'+displ_delivered_imprs+'</td>';
                    //Cycle Impressions (total impressions between last reset date and report date (if defined period campaign, equal to Total Impressions)
                    row += '<td>'+displ_cycle_imprs+'</td>';
                    //Cycle Target (daily kImpressions to get to target for this billing cycle)
                    row += '<td>'+displ_cycle_target+'</td>';
                    //Y'day Realized (Total campaign impressions for report_date)
                    row += '<td '+yesterday_style+'>'+displ_yday_realized+'</td>';
                    //LT Target (for normal recurring campaign, this is target impressions / 28, for defined period campaigns it's target/expected period
                    row += '<td>'+displ_long_term_target+'</td>';
                    //this is the button to show the charts
                    row += '<td><div class="clickable_icon" onclick="show_detail_id(\''+campaign.id+'\',\''+one_month_ago+'\',\''+report_date+'\',\''+displ_advertiser+'-'+displ_campaign_name+'\');"><img class="resize" src="/images/campaign_health/chart.png" alt="Detail" ></div></td>';
                    row += '</tr>';
                    $("#table_body").append(row);
                }
                
                
                function null_check(value){
                    return ((value===null)? '-' : value);
                }
                
                function get_total_impressions(c_id,start_date, end_date){
                     var data_url = "/campaign_health/impression_total/";
                    //alert('localhost'+data_url+'?c_id='+c_id+'&st='+start_date+'&end='+end_date);
                    var return_data;
                    $.ajax({
                                type: "GET",
                                url: data_url,
                                async: false,
                                data: { c_id: c_id,st:start_date , end:end_date },
                                dataType: 'html',
                                error: function(){
                                    return 'error';
                                },
                                success: function(msg){ 
                                    return_data = msg;
                                }
                            });
                            return jQuery.parseJSON(return_data);
                            //return (return_data);
                }
                
                function get_impression_weights(c_id,start_date, end_date){
                     var data_url = "/campaign_health/get_overweight_detail/";
                    //alert('localhost'+data_url+'?c_id='+c_id+'&st='+start_date+'&end='+end_date);
                    var return_data;
                    $.ajax({
                                type: "GET",
                                url: data_url,
                                async: false,
                                data: { c_id: c_id,st:start_date , end:end_date },
                                dataType: 'html',
                                error: function(){
                                    return 'error';
                                },
                                success: function(msg){ 
                                    return_data = msg;
                                }
                            });
                            return jQuery.parseJSON(return_data);
                            //return (return_data);
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

                ///gets lifetime impressions, first impression date and last impression date for campaign id
                function get_dates_impressions(c_id){
                    var data_url = "/campaign_health/lifetime_dates_impressions/"+c_id;
                    //alert('localhost'+data_url);
                    var return_data;
                    $.ajax({
                                type: "GET",
                                url: data_url,
                                async: false,
                                data: {  },
                                dataType: 'html',
                                error: function(){
                                    return 'error';
                                },
                                success: function(msg){ 
                                    return_data = msg;
                                }
                            });
                            return jQuery.parseJSON(return_data);
                }
                
                function all_rows_loaded(){
                    $(".tablesorter").tablesorter();
			
                        tables = document.getElementsByTagName('table');
                        for (var t = 0; t < tables.length; t++) {
                                element = tables[t];

                                        /* Here is dynamically created a form */
                                        var form = document.createElement('form');
                                        form.setAttribute('class', 'filter');
                                        // For ie...
                                        form.attributes['class'].value = 'filter';
                                        var input = document.createElement('input');
                                        input.onkeyup = function() {
                                                filterTable(input, element);
                                        }
                                        form.appendChild(input);
                                        element.parentNode.insertBefore(form, element);

                        }
  
                        $("table").stickyTableHeaders();
                }
                

                function toggle_loading_gif(visibility){
                    document.getElementById("loader_image").style.visibility = visibility;
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
                                    if(response.success){
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
                
                function show_detail(advertiser,campaign,start,end){
                    toggle_loading_gif('visible')
                    $.ajax({
                                type: "GET",
                                url: "/campaign_health/get_campaign_details/",
                                data: { advertiser: advertiser, campaign: campaign, start: start, end: end },
                                dataType: 'html'
                                }).done(function( msg ) {
                                    var response_array = jQuery.parseJSON(msg);  
                                        $.createNotification({
                                            content: '<div id="circle">text</div><div id="time_series_chart" style="min-width: 400px; height: 275px; margin: 0 auto"></div><div id="second_chart" style="width: 475px; height: 350px; float: left; clear:both;"></div><div id="cities_chart" style="width: 475px; height: 350px; float: right"></div>',
                                            duration: null,
                                            horizontal: 'left',
                                            vertical: 'top',
                                            limit: 1,
                                            queue: false,
                                            click: function() {
                                                    this.hide();
                                                }
                                        });
                                        toggle_loading_gif('hidden');
                                        render_time_series_chart(response_array.time_series, advertiser + ' - '+campaign);
                                        render_second_chart(response_array.second_block,response_array.second_block_title);
                                        render_cities_chart(response_array.city_block,'Top 10 Cities');
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
                
                

	</script>
        
<!--	<link rel="stylesheet" media="all" href="../css/tablesorter.css" type="text/css">-->
</head>
<body onload="">
    <div id="test_div"></div>
   
   <div width="100%">
       CAMPAIGN HEALTH (<?php echo $report_date ?>) <span id="progress_span">hello</span><br>
       <a href="/director">HOME</a>       <a  href="/auth/logout">LOGOUT</a><br><br>
       SEARCH
        
        <div style="float: right; padding: 10px"><a href="/campaign_health/graveyard" target="_blank"><img src="/images/campaign_health/FD_poison1.png" alt="Dead Campaigns" width="40" height="40" ></a></div>
   </div>

	<table id="the_campaign_health_table" class="tableWithFloatingHeader stock tablesorter">
		<thead>
                   
				<tr>
					<th class="no_sort">
					</th>
                                        <th >
						Partner
					</th>
                                        <th >
						Advertiser
					</th>
					<th>
						Campaign
					</th>
					<th>
						RTG?
					</th>
					<th>
						Target<br>Impressions
					</th>
                                        <th>
						End<br> Date
					</th>
					<th>
						Months<br>Live
					</th>
					<th>
						Start<br>Date
					</th>
				
					<th>
						Bill<br>Date
					</th>
                                        <th>
						Site<br>Weight
					</th>
					<th>
                                                Total<br>Impressions
					</th>
                                       
                                        <th>
						Cycle<br>Impressions
					</th>
                                     
                                       
                                        <th>
						Cycle<br>Target
					</th>
                                        <th>
						Y'day<br>Realized
					</th>
                                        <th>
						LT<br>Target
					</th>
                                        <th class="no_sort">
					</th>
                                        
                                        
				</tr>
		</thead>
                <tbody class="the_body" id="table_body">
                    <img id="loader_image" class="the_image"   src="/images/campaign_health/preloader.gif" >
                  <?php //echo $table_body_string;?>
		</tbody>
	</table>
  <script type="text/javascript" src="/js/campaign_health/filter_table.js"></script>
        
        
        
        
  
       
        
</body>
</html>
