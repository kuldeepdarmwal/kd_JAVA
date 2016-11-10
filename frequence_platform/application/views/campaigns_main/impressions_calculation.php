<html>
<head>
<script type="text/javascript" src="/bootstrap/assets/js/bootstrap.min.js"></script>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title>Site Tagging Admin</title>

<style>
#rcorners3 {
    border-radius: 25px;
    border: 2px solid #8AC007;
    padding: 20px; 
    width: 1300px;
}
 
</style>
<script src="http://code.highcharts.com/highcharts.js"></script>
<script src="http://code.highcharts.com/modules/exporting.js"></script>

<div class="container container-fluid">

<div class="row">

    <div class="span12">
        <ul class="nav nav-pills">
          <li class="active"><a href="#">Revenue</a></li>
          <li><a href="/campaigns_main/revenue_cpm">CPM</a></li>
        </ul>
    </div>

<div id='rcorners3' style='display:flex; flex-direction:row;'>
&nbsp;&nbsp;Start Date:&nbsp;&nbsp;
<div id="initial_start_date_div" class="input-append date datepicker_recurring_start">
     <input type="text" data-format="yyyy-MM-dd" value="" 
    class="input-small" id="initial_start_date">
    <span class="add-on"><i data-time-icon="icon-time" data-date-icon="icon-calendar" class="icon-calendar"></i></span>
</div>
 &nbsp;&nbsp;End Date: &nbsp;&nbsp;
<div id="initial_end_date_div" class="input-append date datepicker_recurring_start">
   <input type="text" data-format="yyyy-MM-dd" value="" 
    class="input-small" id="initial_end_date">
    <span class="add-on"><i data-time-icon="icon-time" data-date-icon="icon-calendar" class="icon-calendar"></i></span>
</div>

&nbsp;&nbsp;Ref (QBO): &nbsp;&nbsp;<input type="text" value="" class="input-small" id="ref"/><input type="hidden" value="3.35" class="input-small" id="cpm"/> &nbsp;&nbsp;&nbsp;&nbsp; 

<a id="fetch_campaign_flights" onclick="fetch_campaign_flights(2)" class="btn btn-primary btn-small">Billing (MTD > 0)</a>&nbsp;
<a id="fetch_campaign_flights" onclick="fetch_campaign_flights(4)" class="btn btn-success btn-small">QBO Billing data</a>&nbsp;
<a id="fetch_campaign_flights" onclick="fetch_campaign_flights(3)" class="btn btn-warning btn-small">Forecasting tool</a> 
</div> 

<div class='span12' style='font-size:10px'><br>&nbsp;Page to be used by one person at a time. To be used by Frequence Execs only.
<br>
</div>

<div class='span12' id="container" style="min-width: 310px; height: 400px; margin: 0 auto"></div>

<div id ="ajax_data" style='padding: 5px; font-size:10px'></div> 

</div>
</div> 
</body>
</html>

<script type="text/javascript">

$(document).ready(function () {
	$('#initial_start_date_div').datetimepicker({pickTime: false, maskInput: true});
    $('#initial_end_date_div').datetimepicker({pickTime: false, maskInput: true});
    $("#c_main_loading_img").hide();
})

function fetch_impressions()
{
	var start_date=document.getElementById("initial_start_date").value; 
	var end_date=document.getElementById("initial_end_date").value; 

	document.getElementById('container').style.display='none';
	document.getElementById('ajax_data').style.display='block';

	$.ajax({
        type: "POST",
        url: "/campaigns_main/get_impressions_by_range/",
        async: true,
        data: {start_date: start_date, end_date: end_date},
        dataType: 'json',
		success: function(data, textStatus, jqXHR){
			var body_content = "<table border=1><tr><td>#</td><td>Campaign Name</td><td>Targeted Impressions</td></tr>";
			console.log(data);
			var data_array = data['new_campaigns_array'];
			for (var i=0; i < data_array.length; i++)
			{
				var cid = data_array[i]['id'];
				cid=cid.replace("c-","");
				var target_impressions=data_array[i]['prorated_target_impressions'].toLocaleString('en-US', {minimumFractionDigits: 0});;
				 
				body_content += "<tr><td>"+(i+1)+"</td><td><a target='_blank' href='/campaign_setup/"+cid+"'>"+
                data_array[i]['name'] + "</a></td><td>" + target_impressions+"</td></tr>";
				
			}
			body_content += "</table>";
			$("#ajax_data").html(body_content);
        },
		error: function(jqXHR, textStatus, error){ 
			set_message_timeout_and_show('Error 500013: Server Error', 'alert alert-error', 16000);
		}
    });
}

$("#overlay").click(function() 
{
    return false;
});

function showoverlay() 
{
	if (document.getElementById('overlay') == undefined) 
	{	
		$("body").append("<div id='overlay' style='background-color:#F2F0F2; opacity: 0.8;position:absolute;top:0;left:0;height:400%;width:100%;z-index:999'>Calculating billing. This can take few minutes....</div>");
	}
}

function hideoverlay() 
{
	if (document.getElementById('overlay') != undefined) 
	{
		$("#overlay").remove();
	}
}

function fetch_campaign_flights(mode)
{
	showoverlay();
	var start_date=document.getElementById("initial_start_date").value; 
	var end_date=document.getElementById("initial_end_date").value; 
    var cpm=document.getElementById("cpm").value; 
    var ref=document.getElementById("ref").value; 
    if (ref == undefined || ref == "")
        ref ="9876";

	$.ajax({
        type: "POST",
        url: "/campaigns_main/get_all_campaign_flights/",
        async: true,
        data: {start_date: start_date, end_date: end_date, mode: mode, cpm: cpm},
        dataType: 'json',
		success: function(data, textStatus, jqXHR){

			var data_array = data['search_result']['table_data'];

			if (mode == 1 || mode == 2)
			{
				var body_content = "<br><br><table border=1><tr><td></td><td>Partner Name</td><td>Partner ID</td><td>Advertiser Name</td<><td>Advertiser ID</td>"+
				"<td>Campaign Name</td><td>Campaign ID</td><td>Flight Start</td><td>Flight End</td><td>Flight target Impressions</td><td>Flight Status</td>"+
				"<td>LTD Start Date</td><td>LTD End Date</td><td>LTD Actual Impressions</td><td>MTD Start Date</td><td>MTD End Date</td><td>MTD Actual Impressions</td>"+
                "<td>Prev LTD Billed</td><td>MTD Billing</td><td>Performance</td><td>CPM</td><td>Type</td><td>Notes</td><td>Final Amount</td></tr>";
				 
                 var total_completed_flights=0;
                 var total_underdelivered_flights=0;
                 var total_overdelivered_flights=0;
                 var total_sum_impressions_performance=0;
                 var total_flight_target_impressions=0;

				for (var i=0; i < data_array.length; i++)
				{
					body_content += "<tr><td>"+(i+1)+"</td>"+data_array[i]['disp_str']+ 
					"<td>"+ data_array[i]['cid']+"</td><td>"+ data_array[i]['flight_start']+"</td><td>"+ data_array[i]['flight_end']+
					"</td><td>"+ data_array[i]['flight_target_impr']+"</td><td>"+ data_array[i]['flight_status']+
					"</td><td>"+ data_array[i]['ltd_start']+"</td><td>"+data_array[i]['ltd_end']+"</td><td>"+
					data_array[i]['ltd_impr']+"</td><td>"+data_array[i]['mtd_start']+"</td><td>"+data_array[i]['mtd_end']+
					"</td><td>"+data_array[i]['mtd_impr']+"</td><td>"+data_array[i]['prev_ltd_billing']+ "</td><td>"+data_array[i]['mtd_billing']+ "</td><td>"+data_array[i]['performance_flag']+ "</td>"+
                    "<td>"+data_array[i]['cpm']+ "</td><td>"+data_array[i]['campaign_target_type']+
                     "</td><td>"+data_array[i]['notes']+ "</td><td>"+data_array[i]['final_amount']+ "</td>"+
                    "</tr>";
				    if (data_array[i]['flight_status'] == 'Complete')
                    {
                        total_completed_flights++;
                        if (data_array[i]['performance_flag'] == 'underdelivery')
                        {
                            total_underdelivered_flights++;
                        } 
                        else
                        {
                            total_overdelivered_flights++;    
                            total_sum_impressions_performance+=parseInt(data_array[i]['performance_flag']);
                            total_flight_target_impressions+=parseInt(data_array[i]['flight_target_impr']);
                        }
                    }

                }
				body_content += "</table>";

                header=" <br><br><br><b>Overdelivery: "+total_sum_impressions_performance.toLocaleString() + " / " + total_flight_target_impressions.toLocaleString() + " (" + Math.round(100*total_sum_impressions_performance/total_flight_target_impressions ) +"%)" +
                " &nbsp;&nbsp;&nbsp;&nbsp;Underdelivery: " +total_underdelivered_flights.toLocaleString()+ " / "+ total_completed_flights.toLocaleString() + " (" + Math.round(100* total_underdelivered_flights/total_completed_flights)+"%)</b>";

				$("#ajax_data").html(header + " " + body_content);
				$("#container").html("");
				document.getElementById('container').style.display='none';
				document.getElementById('ajax_data').style.display='block';
			}
            if (mode == 4)
            {
                var customer_name=data_array[0]['customer_name'];
                var date = new Date();
                var first_day = new Date(date.getFullYear(), date.getMonth(), 1);
                var first_day =first_day.getFullYear().toString()+ "-" + (first_day.getMonth() + 1) + "-" + first_day.getDate();
                body_content_header_csv="<a id='fetch_campaign_flights' onclick='create_excel(\"0_csv_table\", \""+customer_name+"\")' class='btn btn-small' title='"+customer_name+"'>"+customer_name+" <i class='icon-download-alt icon-white'></i></a><br><br>";
                
                var body_content_header = "<br><br><a id='fetch_campaign_flights' onclick='create_excel(\"0_csv_table\", \""+customer_name+"\")' class='btn btn-small' title='"+customer_name+"'>"+customer_name+" <i class='icon-download-alt icon-white'></i></a><br>"+
                "<br><br><table id='0_csv_table' style1='visibility:hidden' border=1><tr><td>Advertiser</td><td>CampaignName</td><td>1</td<><td>2</td>"+
                "<td>LineDesc</td><td>Impressions</td><td>LineItem</td><td>LineUnitPrice</td><td>RefNumber</td><td>Customer</td>"+
                "<td>TxnDate</td><td>DueDate</td><td>SalesTerm</td><td>BillAddrLine1</td><td>BillAddrLine2</td><td>BillAddrLineCity</td>"+
                "<td>BillAddrLineState</td><td>BillAddrLinePostalCode</td><td>LineQty</td><td>LineAmount</td><td>InternalNotes</td></tr>";
                var prev_customer_name="-1";
                body_content=body_content_header;
                var ctr=1;
                for (var i=0; i < data_array.length; i++)
                {
                    customer_name=data_array[i]['customer_name'];
                    if (prev_customer_name != "-1" && customer_name != prev_customer_name)
                    {
                        body_content_header_csv+="<a id='fetch_campaign_flights' onclick='create_excel(\""+ctr+"_csv_table\", \""+customer_name+"\")' class='btn btn-small' title='"+customer_name+"'>"+customer_name+" <i class='icon-download-alt icon-white'></i></a><br><br>";
                        body_content_header = "<br><br><a id='fetch_campaign_flights' onclick='create_excel(\""+ctr+"_csv_table\", \""+customer_name+"\")' class='btn btn-small' title='"+customer_name+"'>"+customer_name+" <i class='icon-download-alt icon-white'></i></a><br>"+
                            "<br><br><table  id='" +ctr+ "_csv_table' style1='visibility:hidden' border=1><tr><td>Advertiser</td><td>CampaignName</td><td>1</td<><td>2</td>"+
                            "<td>LineDesc</td><td>Impressions</td><td>LineItem</td><td>LineUnitPrice</td><td>RefNumber</td><td>Customer</td>"+
                            "<td>TxnDate</td><td>DueDate</td><td>SalesTerm</td><td>BillAddrLine1</td><td>BillAddrLine2</td><td>BillAddrLineCity</td>"+
                            "<td>BillAddrLineState</td><td>BillAddrLinePostalCode</td><td>LineQty</td><td>LineAmount</td><td>InternalNotes</td></tr>";
                        body_content += "</table>" + body_content_header;
                        ctr++;
                        ref=parseInt(ref)+1;
                    }
                    
                    body_content += "<tr><td>"+ data_array[i]['adv_name']+"</td><td>"+ data_array[i]['campaign_name']+"</td><td>"+ 
                    data_array[i]['mtd_billing']+"</td<><td>"+ data_array[i]['mtd_impr']+"</td>"+
                    "<td>"+ data_array[i]['line_desc']+"</td><td>"+ data_array[i]['mtd_billing_final']+"</td><td>Custom</td><td>"+ data_array[i]['cpm']+"</td><td>"+ref+"</td><td>"+ data_array[i]['display_name']+"</td>"+
                    "<td>"+first_day+"</td><td>"+data_array[i]['due_date']+"</td><td>"+data_array[i]['sales_term']+"</td><td>"+data_array[i]['billing_address_1']+
                    "</td><td>"+data_array[i]['billing_address_2']+"</td><td>"+data_array[i]['billing_city']+"</td>"+
                    "<td>"+data_array[i]['billing_state']+"</td><td>"+data_array[i]['billing_zip']+"</td><td>"+ data_array[i]['line_qty']+"</td><td>"+ data_array[i]['final_amount']+"</td><td>"+ data_array[i]['notes']+"</td></tr>";
                    
                    prev_customer_name=customer_name;
                
                }
                body_content += "</table>";

                $("#ajax_data").html(body_content_header_csv+"<hr>"+body_content);
                $("#container").html("");
                document.getElementById('container').style.display='none';
                document.getElementById('ajax_data').style.display='block';
            }
			//graph
			else if (mode == 3)
			{
				document.getElementById('container').style.display='block';
				document.getElementById('ajax_data').style.display='none';
				var data_array_int=new Array();
				var alert_flag=false;
				for(var i=0; i < data['search_result']['data_array'].length; i++)
				{
					if (alert_flag==false && data['search_result']['data_array'][i] == null)
					{
						alert('Campaigns not found for all dates selected');
						alert_flag=true;
					}
					data_array_int[i]=parseInt(data['search_result']['data_array'][i]);
				}
				display_graph(data['search_result']['days_array'], data_array_int, data['search_result']['graph_title']);
			}

			hideoverlay();
        },
		error: function(jqXHR, textStatus, error){ 
			alert('Error 500013: Server Error', 'alert alert-error', 16000);
		}
    });
}

// graph goes here
function display_graph(days_array, data_array, title_calc) {

    $('#container').highcharts({
        title: {
            text: 'Forecasting: Timeseries Impressions (For Nonpaused campaigns or Pause Date in future. Name not starting with zz_. Includes archived Campaigns)' 
        },
        subtitle: {
            text: title_calc
        },
        xAxis: {
            categories: days_array
        },
        yAxis: {
            title: {
                text: "Timeseries impressions per day"
            },
            plotLines: [{
                value: 0,
                width: 1,
                color: '#808080'
            }]
        },
        tooltip: {
            valueSuffix: ''
        },
        legend: {
            layout: 'vertical',
            align: 'right',
            verticalAlign: 'middle',
            borderWidth: 0
        },
        series: [{
            name: 'Impressions',
            data: data_array
        }]
    });
}


</script>
<script type="text/javascript" src="/bootstrap/assets/js/bootstrap-datetimepicker.min.js"></script>
<script type="text/javascript" src="/js/csv_table_create.js"></script>


 