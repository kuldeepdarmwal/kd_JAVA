<?php

$data = file_get_contents("php://input");
$data = json_decode($data);
$campaign_id = $data->{'campaign_id'};

$categories = $data->{'categories'};

//$db = mysql_connect('10.179.2.54', 'vlproduser', 'L0cal1s1n!');
//mysql_select_db('vantagelocal_prod');
$db = mysql_connect('brandcdn-prod.cgy3zqnvq7mi.us-west-1.rds.amazonaws.com', 'frequence_admin', '!Ds6AC0SnulgHe$4');
mysql_select_db('brandcdn');	

$clear_query = "DELETE FROM ad_verify_campaign_categories WHERE campaign_id = ".$campaign_id;
mysql_query($clear_query);


foreach ($categories as $category)
{
    $query = "INSERT INTO ad_verify_campaign_categories (campaign_id, tag_id) VALUES (".$campaign_id.",".$category.")";
    mysql_query($query);
}

?>