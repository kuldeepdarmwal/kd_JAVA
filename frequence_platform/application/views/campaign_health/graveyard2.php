<?php
//Campaigns.Business as advertiser, 
//Campaigns.Name as campaign_name,
//max(AdGroups.isRetargeting) as RTG,
//Campaigns.TargetImpressions as target_monthly_impressions,
//Campaigns.hard_end_date as hard_end_date,
//min(CityRecords.Date) as start_date,
//max(CityRecords.Date) as end_date,
//timestampdiff(month,min(CityRecords.Date),max(CityRecords.Date)) as months	

$table_body_string = '';
$row_id = 1; //this will serve as the row id so the js function knows to remove this row from the table (start at 1 so we don't delete the headings)
foreach ($graveyard_summary->result_array() as $row)
{   



    $table_body_string .= '<tr id = "'.$row_id.'" class="expand">
                                        <td style="width: 100px;" >
						<div class="clickable_icon" onclick="add_to_healthcheck(\''.rawurlencode($row['advertiser']).'\',\''.$row['c_id'].'\','.$row_id.');"><img src="/images/campaign_health/eye_open.png" alt="Add" ></div>
                                                
                                                
                                                    
					</td>
                                        <td>
						'.$row['partner'].'
					</td>
					<td>
						'.$row['advertiser'].'
					</td>
					<td>
						'.$row['campaign_name'].'
					</td>
					<td>
						'.$row['RTG'].'
					</td>
					<td>
						'.number_format($row['target_monthly_impressions']).'
					</td>
                                        <td>
						'.$row['hard_end_date'].'
					</td>
					
				</tr>';
    
    $row_id++;
    
}

?>



<!DOCTYPE HTML>
<html>
<head>
	<title>GRAVEYARD</title>
        <link href='http://fonts.googleapis.com/css?family=Oxygen' rel='stylesheet' type='text/css'>
        <link rel="shortcut icon" href="/images/campaign_health/spade.png">
	<link rel="stylesheet" media="all" href="../css/campaign_health/campaign_health_index_style.css" type="text/css">
        <link rel="stylesheet" href="../css/campaign_health/notification.css">
<!--	<link rel="stylesheet" media="all" href="../css/tablesorter.css" type="text/css">-->
</head>
<body onload="">
          <img src="/images/campaign_health/FD_poison1.png" alt="for my homies"  />      
    <h2>Welcome to the GRAVEYARD - Where campaigns go to die</h2>
    <div width="1000px">
        <div style="float: right; padding: 5px 0px 0px 0px" onclick="show_ticker()"><img src="/images/campaign_health/espn-red_50.png" alt="Show Ticker" ></div>
        <div style="float: right"><a href="/campaign_health/" target="_blank"><img src="/images/campaign_health/stethoscope.png" alt="Dead Campaigns" width="41" height="29"></a></div></div>

	<table id="the_campaign_health_table" class="tableWithFloatingHeader dead tablesorter">
            <thead >

				<tr >
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
						Monthly<br> Target<br> Impressions
					</th>
                                        <th>
						Hard<br> End<br> Date
					</th>
					
				
				</tr>
		</thead>
                <?php echo $table_body_string;?>
		
	</table>
	<div style="height: 50px">
           something goes here
	</div>    
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js" type="text/javascript"></script>
        <script src="../js/campaign_health/jquery.stickytableheader.js" type="text/javascript"></script> 
	<script src="../js/campaign_health/jquery.tablesorter.js" type="text/javascript"></script>
        <script src="../js/campaign_health/jquery.notification.js"></script>
        <script type="text/javascript">

		$(document).ready(function () {
			// initialize stickyTableHeaders _after_ tablesorter
			$(".tablesorter").tablesorter();
                        
                         tables = document.getElementsByTagName('table');
                        for (var t = 0; t < tables.length; t++) {
                                element = tables[t];

//                                if (element.attributes['class']
//                                        && element.attributes['class'].value == 'filterable') {

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
                                //}
                        }
                        
                        
                        
                        
			$("table").stickyTableHeaders();
                        //jQuery(".expand").click(function() { jQuery(this).next(".content").slideToggle(500);} );
		});
                
                
                
                
                function add_to_healthcheck(raw_advertiser,campaign,id){
                            //alert(campaign);
							var advertiser = unescape(raw_advertiser);
                            $.ajax({
                                type: "GET",
                                url: "/campaign_health/add_to_healthcheck/",
                                data: {campaign: campaign },
                                dataType: 'html'
                                }).done(function( msg ) {
                                    var response = jQuery.parseJSON(msg);
									var ui_message;
                                    if(response){
                                        delete_row(id);
                                        ui_message = "added " + advertiser + " " + campaign;
                                    }else{
                                        ui_message = "there was an error with the database";
                                    }
                                    

                                    $.createNotification({
                                            content: ui_message,
                                            duration: null,
                                            horizontal: 'left',
                                            vertical: 'top',
                                            duration: 1000,
                                            click: function() {
                                                    this.hide();
                                            }
                                    });
                                    
                            });
                  
                    
                    
                    
                }
                
                
                
                
                function show_detail(advertiser,campaign){
                    $.createNotification({
                            content: "please show detail for " + advertiser + " " + campaign,
                            duration: null,
                            horizontal: 'left',
                            vertical: 'top',
                            duration: 1000,
                            click: function() {
                                    this.hide();
                            }
                            
                    });
                }
                
                function show_ticker(){
                    $.createBigNotification({
                            content: '<iframe src ="http://espn.go.com/bottomline/espnewsbottomlinebasic.html" height="44" width="765"></iframe>',
                            duration: null,
                            vertical: 'bottom'
                    });
                    
//                     $.createBigNotification({
//                            content: '<iframe src ="http://espn.go.com/bottomline/espnewsbottomlinebasic.html" height="44" width="765"></iframe>',
//                            duration: null,
//                            horizontal: 'left',
//                            vertical: 'top'
//                            
//                    });
                }
                
                function delete_row(rowid)  
                {   
                    var row = document.getElementById(rowid);
                    row.parentNode.removeChild(row);
                }

	</script>
        <script type="text/javascript" src="/js/campaign_health/filter_table.js"></script>
</body>
</html>
