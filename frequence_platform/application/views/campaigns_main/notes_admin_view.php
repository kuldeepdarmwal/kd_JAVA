<html>
<head>
<script type="text/javascript" src="/bootstrap/assets/js/bootstrap.min.js"></script>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title>notes Admin</title>
<style>
#rcorners3 {
    border-radius: 25px;
    border: 2px solid #8AC007;
    padding: 20px; 
    width: 1200px;
    height: 30px; 
}
	.alert, .alert h4 
	{
		color: #757877;
	}
</style>
<?php 
		
		echo "<div style='font-size:10px;color:#848484'>";
		$date = new DateTime();
		$date_hdr = new DateTime();
		$date_interval='P1D';
		 echo "<h3>&nbsp;Notes Admin<br></h3><h4>&nbsp;Count of Notes added per user in last 2 weeks</h4><br>";
		 echo "<table class='table table-bordered table-hover table-condensed'><thead><tr><th>User</th>";
		 for($ctr=1; $ctr<=14;$ctr++)
			 {
			 	$date_hdr->format('Y-m-d');
			 	echo "<th>".$date_hdr->format('Y-m-d')."</th>";
			 	
			 	$date_hdr->sub(new DateInterval($date_interval));

			 }
			 echo "</tr></thead>";
		 $notes_main_array=array();
		 foreach($notes_data['report_notes'] as $row)
		 {
		 	if (array_key_exists($row['username'], $notes_main_array) != 'true')
		 	{
		 		$notes_main_array[$row['username']]=array();
		 	}
		 	$notes_main_array[$row['username']][$row['created_date']]=$row['count'];
		 	//echo "<tr><td>".$row['created_date']."</td><td>".$row['username']."</td><td>".$row['count']."</td></tr>";
		 }

		 foreach ($notes_data['report_notes_total'] as $value) 
		 {
		 	echo "<tr><td>".$value["username"]."</td>";
		 	$username = $value["username"];
		 	$date = new DateTime();
		 	for($ctr=1; $ctr<=14;$ctr++)
			 {			 	
			 	echo "<td>";
			 	if (array_key_exists($username, $notes_main_array)  == 'true' && array_key_exists($date->format('Y-m-d'), $notes_main_array[$username])  == 'true')
			 	{
			 		echo $notes_main_array[$username][$date->format('Y-m-d')];
			 	}
			 	echo "</td>";
			 	$date->sub(new DateInterval($date_interval));

			 }
			 echo "</tr>";
		 }
		 
		 echo "</tbody></table><br>";

		 echo "<h4>&nbsp;Note Details from last 2 weeks</h4><br>";

		 echo "<div id='rcorners3' class='span12'>Advertiser: <input type=text class='input-small' id='ad_name'>&nbsp;";
		 echo "Campaign: <input type=text class='input-small' id='cpn_name'>&nbsp;&nbsp;";
		 echo "Campaign minus: <input class='input-small'  type=text id='cpn_minus'>";
		 echo "&nbsp;Notes: <input class='input-small' type=text id='notes_text'>";
		 echo "&nbsp;User: <input type=text class='input-small'  id='user_name'>";
		 echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button class='btn btn-primary btn-mini' onclick='notes_search(0)'>Advertiser Search</button>";
		 echo "&nbsp;&nbsp;&nbsp;<button class='btn btn-success btn-mini' onclick='notes_search(1)'>Campaign Search</button>";
		 echo "&nbsp;&nbsp;&nbsp;<button class='btn btn-info btn-mini' onclick='notes_search(2)'>Notes Search</button></div><br>";
		 echo "<br><br><div id='search_info'></div>";

		 

		 echo "<br><div id='tags_alert_div' class='alert'></div><br><div id='search_result_notes'><table class='table table-bordered  table-hover table-condensed'><thead><tr><th>Advertiser</th><th>Campaign</th><th>CreatedDate</th><th>username</th><th>Code</th><th>Flight</th><th>Opt</th><th>IO</th><th>BI</th><th>MPQ</th><th>URL</th><th>Ad ID</th><th>Geo</th><th>Pop</th><th>Demo</th><th>Context</th><th>Bdgt</th><th>Cal</th><th>Note</th></tr></thead><tbody>";
		 

		 foreach($notes_data['table_notes'] as $row)
		 {
		 	$cid = $row['Campaign ID'];
		 	$sub_array=explode("^^\n" , $row['Notes']);
					$campaign_string="";
					$sub_ctr=0;
					foreach ($sub_array as $sub_row) 
					{
						if ($sub_ctr > 14)
							break;
						$sub_internal_array=explode("::" , $sub_row);
						$cell_value="";
						if (count($sub_internal_array) > 1)
						{
							$cell_value=$sub_internal_array[1];
							/*
							if (strlen($sub_internal_array[1]) > 50)
								$cell_value=substr($sub_internal_array[1], 0, 50) . "...";
							else
								$cell_value=$sub_internal_array[1];*/
						}	
						$campaign_string.="<td style='white-space:pre-wrap;'>".$cell_value."</td>";

						$sub_ctr++;
					}

		 	echo "<tr><td>".$row['Advertiser']."</td><td><a target='_blank' href='/campaign_setup/$cid'>".$row['Campaign Name']."</a></td><td>".$row['CreatedDate']."</td><td>".$row['username']."</td>".$campaign_string."</tr>";
		 }
		 echo "</tbody></table>";
		 echo "</div>";

 
	$this->load->view('vl_platform_2/ui_core/js_error_handling');
	write_vl_platform_error_handlers_js();
	write_vl_platform_error_handlers_html_section();
	?>

<script type="text/javascript">	

function show_tags_error(error_msg)
{
	error_msg="<b>"+error_msg+"</b>";
	$("#tags_alert_div").html(error_msg);
	$("#tags_alert_div").show();
}

function notes_search(mode)
{

		var ad_name = document.getElementById("ad_name").value;
		var cpn_name = document.getElementById("cpn_name").value;
		var cpn_minus = document.getElementById("cpn_minus").value;
		var notes_text = document.getElementById("notes_text").value;
		var user_name = document.getElementById("user_name").value;
		
		 var data_url = "/campaigns_main/notes_search_advanced";
			$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: {ad_name: ad_name, cpn_name: cpn_name, cpn_minus: cpn_minus, notes_text: notes_text, user_name: user_name, mode: mode},
			dataType: 'html',
			error: function(){
				document.getElementById('adgroup_modal_detail_body').innerHTML = temp;
				return 'error';
			},
			success: function(msg){ 
				var returned_data = jQuery.parseJSON(msg);
				var search_result = returned_data['search_result'];
				var output_str="<br><br><table class='table table-border table-hover table-condensed'><thead><tr><th>Advertiser</th><th>Campaign</th><th>CreatedDate</th><th>username</th><th>Code</th><th>Flight</th><th>Opt</th><th>IO</th><th>BI</th><th>MPQ</th><th>URL</th><th>Ad ID</th><th>Geo</th><th>Pop</th><th>Demo</th><th>Context</th><th>Bdgt</th><th>Cal</th><th>Note</th></tr></thead><tbody>";
				if (search_result != undefined) 
				{
					for (var i=0; i < search_result.length; i++)
					{
						var sub_array=search_result[i];
						 output_str+= "<tr><td>"+sub_array['advertiserElem'];
						 output_str+=  "</td><td><a target='_blank' href='/campaign_setup/";
						 output_str+=  sub_array['cid']+"'>"+ sub_array['cname'];
						 output_str+=  "</a></td><td>"+ sub_array['created_date']+"</td><td>"+ sub_array['username']+"</td>";
						 if (sub_array['notes'].indexOf("^^") != -1)
						 {
							 var sub_array_notes=sub_array['notes'].split ("^^\n");
							 for (var j=0; j < 15; j++)
							{
								if (sub_array_notes[j] != undefined)
								{
									var sub_internal_array=sub_array_notes[j].split("::"); 
									if (sub_internal_array.length > 1)
									output_str+=  "<td style='white-space:pre-wrap;'>"+sub_internal_array[1]+"</td>";
								}
								else
								{
									output_str+=  "<td></td>";
								}
							}
						}
						else
						{
							for (var j=0; j < 15; j++)
							{
								output_str+=  "<td></td>";
							}
						}

						output_str+= "</tr>";
					}
				}
				output_str+= "</tbody></table>";
				if (mode == 0)
		 			show_tags_error("Advertiser results");
		 		if (mode == 1)
		 			show_tags_error("Campaign results");
		 		if (mode == 2)
		 			show_tags_error("Notes results");
		 		
				document.getElementById("search_result_notes").innerHTML=output_str;
				 
			}
		});
}

</script>
</body>
</html>

 