<?php

//$db = mysql_connect('10.179.2.54', 'vlproduser', 'L0cal1s1n!');
//mysql_select_db('vantagelocal_prod');
$db = mysql_connect('brandcdn-prod.cgy3zqnvq7mi.us-west-1.rds.amazonaws.com', 'frequence_admin', '!Ds6AC0SnulgHe$4');
mysql_select_db('brandcdn');	

$query = "SELECT tag_id FROM ad_verify_campaign_categories WHERE campaign_id = ".$_GET['c_id'];

$response = mysql_query($query);
$list = array();

while ($row = mysql_fetch_assoc($response))
{
    array_push($list, $row['tag_id']);
}



echo json_encode($list);
?>