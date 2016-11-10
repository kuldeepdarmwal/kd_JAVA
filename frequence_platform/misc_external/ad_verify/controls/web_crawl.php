<html>
<head>
    <link rel='stylesheet' href='http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/smoothness/jquery-ui.css' type='text/css' media='screen' />  
    <link rel='stylesheet' href='http://secure.vantagelocal.com/js/multi_select/css/ui.multiselect.css' type='text/css' media='screen' />
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
    <script type="text/javascript" src="http://code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
<style type="text/css">
     .ui-selecting { background: #FECA40; }
     .ui-selected { background: #F39814; color: white; }
</style>
<script>

function run_script()
{
    var domains = document.getElementById("select-result").innerHTML;
    if (domains == "none"){
	alert("NOPE");
	return;
    }
    alert(domains);
    var request = new XMLHttpRequest;
    request.open("POST", "execute_web_crawler.php", true);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    var params = "domain="+domains;
    request.send(params);
    var butan = document.getElementById("go_button");
    butan.disabled = true;
}
     
     function kill_script()
     {
	 var kill_id = document.getElementById("kill_box").value;
	 var request = new XMLHttpRequest;
	 request.open("POST", "crawl_kill.php", true);
	 request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	 var params = "kill_id="+kill_id;
	 request.send(params);
	 var butan = document.getElementById("kill_button");
	 butan.disabled = true;
	 window.setTimeout(function(){butan.disabled = false;}, 5000);

     }



  $(window).load(function(){
	  var to_crawl = "";
  	$("#selectable").selectable({
	    filter: "tr",
  	    stop: function(){
		    var result = $("#select-result").empty();
		    $(".ui-selected").each(function() {
			    //alert($(this).children().eq(0).text());
			var index = $(this).children().eq(0).text();
			if(index.length > 0) {
			    result.append(index+'\n');
			    //$('#go_button').attr("disabled", "");
			}
			if (result.length < 2){
			    //$('#go_button').attr("disabled", "disabled");
			}
			to_crawl = result;
			});
		    
		}
	    });       
      });


</script>
<title>Legitimate Spider Bot v1</title>
</head>

<body>
<h2>Ad-Verify Web Crawler Control Panel</h2>
<p id="feedback">
<span>Selected:</span> <span id="select-result">none</span>.
</p>

<div id='scrollbox' style="width:550px;height:400px;overflow:auto;">

	 <table id="list_table">
	 <thead>
	 <tr id="headers" class="ui-widget-header even">
	 <th class="toggle_head">Base Site</th>
	 <th class="toggle_head"># Campaigns</th>
	 <th class="toggle_head">Last Run</th>
	 </tr>
	 </thead>
     
	 <tbody id='selectable' class="ui-widget-content ui-selectable">

<?php
     $base_sites = array();
$one_month_ago = date("Y-m-d", strtotime('-1 month'));
//$db = mysql_connect('10.179.2.54', 'vlproduser', 'L0cal1s1n!');
//mysql_select_db('vantagelocal_prod');
$db = mysql_connect('brandcdn-prod.cgy3zqnvq7mi.us-west-1.rds.amazonaws.com', 'frequence_admin', '!Ds6AC0SnulgHe$4');
mysql_select_db('brandcdn');	
    $active_campaign_query = "SELECT c.Name as Name, a.Name as Business, c.LandingPage as Page, c.id as id FROM Campaigns c LEFT JOIN Advertisers a ON (c.business_id = a.id) WHERE c.ignore_for_healthcheck = 0 AND c.id IN (SELECT campaign_id FROM tags WHERE tag_type = 1 and isActive = 1) GROUP BY c.id ORDER BY a.Name ASC";
$response = mysql_query($active_campaign_query);

while($row = mysql_fetch_assoc($response))
{
    //echo $row['id'];
    $get_base_site_query = "SELECT b.Base_Site as base_url, SUM(b.Impressions) FROM (AdGroups a JOIN SiteRecords b ON (a.ID = b.AdGroupID)) JOIN Campaigns c ON (a.campaign_id = c.id) WHERE b.Base_Site  NOT LIKE '%youtube.com%' AND b.Base_Site NOT LIKE '%okcupid.com%' AND b.Base_Site NOT LIKE '%facebook.com%' AND b.Base_Site NOT LIKE '%site-not-provided%' AND b.Base_Site != 'All other sites' AND  c.id = ".$row['id']." AND b.Date > '".$one_month_ago."' GROUP BY base_url ORDER BY SUM(b.Impressions) DESC LIMIT 20";
    // echo $get_base_site_query."\n";
    $sites_response = mysql_query($get_base_site_query);
    while($site = mysql_fetch_assoc($sites_response))
    {
	array_push($base_sites, $site['base_url']);
    }
}
$data = array();
$base_sites = array_count_values($base_sites);
//print_r($base_sites);
$run_sites = array();
$not_run_sites = array();
foreach ($base_sites as $site => $count)
{
    
    $get_last_run_date_query = "SELECT start_time FROM ad_verify_web_crawl_records WHERE base_url = '".$site."' ORDER BY start_time DESC LIMIT 1";

    $last_run_date_result = mysql_query($get_last_run_date_query);
    if(mysql_num_rows($last_run_date_result) > 0)
    {
	$date_row = mysql_fetch_assoc($last_run_date_result);
	$last_run_date = $date_row['start_time'];
	$run_sites[$site] = array($site, $count, $last_run_date);
	
    }
 else
 {
     $last_run_date = "NEVER";
     $not_run_sites[$site] = array($site, $count, $last_run_date);
 }
//    $data[$site] = array($count, $last_run_date);
    //array_push($data, array($site, $count, $last_run_date));                                                                                                                                
}
//$data = array_unique($data);
//ksort($run_sites);
usort($run_sites, function($a1, $a2){
	$v1 = strtotime($a1[2]);
	$v2 = strtotime($a2[2]);
	return $v1 - $v2;
    });
//ksort($not_run_sites);
usort($not_run_sites, function($a1, $a2){
	$v1 = $a1[1];
	$v2 = $a2[1];
	return $v2 - $v1;
    });
$data = array_merge($not_run_sites, $run_sites);

foreach($data as $site => $site_data)
{
    echo "<tr class='ui-widget-content'><td>".$site_data[0]."</td><td>".$site_data[1]."</td><td>".$site_data[2]."</td><tr>";

}
?>



 </tbody>
	 </table>
</div>
<br>
<button value="STOP!" onClick="run_script();" id="go_button">GO!</button>
<br><br><a href="/screenshots/logs/crawler/">Logs of crawler jobs</a>


</body>
</html>