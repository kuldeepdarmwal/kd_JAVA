<?php

function collate_loose_impressions($date)
{

  $db_collate = mysql_connect('localhost', 'vldevuser', 'L0cal1s1n!');
  mysql_select_db('vantagelocal_dev');

 $scoop_query =
 "INSERT INTO SiteRecords(                                                                                                         
 SELECT AdGroupID,  'OTHER SITES', DATE, SUM( Impressions ) , SUM( Clicks ) , Cost,  'All other sites' AS Imp                      
 FROM SiteRecords                                                                                                                  
 WHERE Date = '".$date."' AND ((                                                                                                   
 Impressions <10                                                                                                                   
 AND Clicks =0)                                                                                                                                 
 OR Site =  'All other sites')                                                                                                     
 GROUP BY AdGroupID, DATE )";

$clear_query =
  "DELETE FROM SiteRecords WHERE Date = '".$date."' AND ((Impressions < 10 AND Clicks = 0 AND Site != 'OTHER SITES') OR Site = 'All other sites')";



$replace_query =
  "UPDATE SiteRecords SET Site='All other sites' WHERE Date = '".$date."' AND Base_Site='All other sites'";


$imp_count_query = "SELECT SUM(Impressions) FROM SiteRecords WHERE Date = '".$date."'";

$result = mysql_query($imp_count_query, $db_collate);
$initial_impressions = mysql_result($result, 0);

echo "Initial: ".$initial_impressions;

mysql_query($scoop_query, $db_collate);
mysql_query($clear_query, $db_collate);
mysql_query($replace_query, $db_collate);

$result = mysql_query($imp_count_query, $db_collate);
$fixed_impressions = mysql_result($result, 0);

echo " - After: ".$fixed_impressions."\n";

}



$dates = array(
	       "2012-10-31"
	      );

foreach ($dates as $process_date)
{
    echo "Collating ".$process_date." ".date("m/d/Y H:i:s")."\n";
    collate_loose_impressions($process_date);
    
}

?>