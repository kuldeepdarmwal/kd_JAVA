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
    echo $row['id'];
    $get_base_site_query = "SELECT b.Base_Site as base_url, SUM(b.Impressions) FROM (AdGroups a JOIN SiteRecords b ON (a.ID = b.AdGroupID)) JOIN Campaigns c ON (a.campaign_id = c.id) WHERE b.Base_Site !='youtube.com' AND b.Base_Site != 'facebook.com' And b.Base_Site != 'All other sites' AND  c.id = ".$row['id']." AND b.Date > '".$one_month_ago."' GROUP BY base_url ORDER BY SUM(b.Impressions) DESC LIMIT 20";
     echo $get_base_site_query."\n";
     $sites_response = mysql_query($get_base_site_query);
     while($site = mysql_fetch_assoc($sites_response))
     {
	 array_push($base_sites, $site['base_url']);
     }
}
$data = array();
$base_sites = array_count_values($base_sites);
print_r($base_sites);
foreach ($base_sites as $site => $count)
{
$get_last_run_date_query = "SELECT start_time FROM ad_verify_web_crawl_records WHERE base_url = '".$site."' ORDER BY start_time DESC LIMIT 1";
                                                                      
 $last_run_date_result = mysql_query($get_last_run_date_query);                                                               
 if(mysql_num_rows($last_run_date_result) > 0)                                                                                
 {                                                                                                                            
     $date_row = mysql_fetch_assoc($last_run_date_result);                                                                    
     $last_run_date = $date_row['start_time'];                                                                                
 }                                                                                                                            
 else                                                                                                                        
 {
     $last_run_date = "NEVER";                                            
 }                                                                         
 $data[$site] = array($count, $last_run_date);
 //array_push($data, array($site, $count, $last_run_date));
}

//$data = array_unique($data);
ksort($data);
print_r($data);
/*
$base_sites = array_unique($base_sites);
foreach ($base_sites as $base_site)
{
    $get_campaign_count_query = "SELECT * FROM SiteRecords WHERE Base_Site = '".$base_site."' AND Date > '".$one_month_ago."' GROUP BY AdGroupID";
    $campaign_count_result = mysql_query($get_campaign_count_query);
    $campaign_count = mysql_num_rows($campaign_count_result);
    echo "CAMPAIGN_COUNT: ".$campaign_count;

    $get_last_run_date_query = "SELECT start_time FROM ad_verify_web_crawl_records WHERE base_url = '".$base_site."' ORDER BY start_time DESC LIMIT 1";
    $last_run_date_result = mysql_query($get_last_run_date_query);
    if(mysql_num_rows($last_run_date_result) > 0)
    {
	$date_row = mysql_fetch_assoc($last_run_date_result);
	$last_run_date = $date_row['start_time'];
    } 
    else 
    {
	$last_run_date = "NEVER";
    }

	echo "START DATE: ".$last_run_date;
	$site_data = array($base_site, $campaign_count, $last_run_date);
	array_push($data, $site_data);       
}

print_r($data);

?>
*/