<?php
	header('Content-Type: text/csv'); 
	header('Pragma: no-cache');
	header('Expires: 0');
	header('Content-Disposition: attachment; filename=ad_tags.xls');

	echo '"Name",';
	echo '"Description",';
	echo '"Width",';
	echo '"Height",';
	echo '"AdTag",';
	echo '"Landing Page Urls",';
	echo '"Google Vendor Declarations"';
	echo "\r\n";

	foreach($creatives as $index=>$row)
	{
		$creative_data = $creatives_data[$index];

		echo '"'.$adset[0]['campaign'].'::'.$row['size'].'",';
		echo '" -- ",';
		echo '"'.$creative_data['width'].'",';
		echo '"'.$creative_data['height'].'",';
		switch($ad_server_type_id)
		{
			case k_ad_server_type::dfa_id:
				echo '"'.$row['ad_tag'].$creative_data['trust_tag'].'",';
				break;
			case k_ad_server_type::fas_id:
				echo '"'.$row['ad_tag'].$creative_data['trust_tag'].'",';
				break;
			case k_ad_server_type::adtech_id:
				echo '"'.$row['adtech_ad_tag'].$creative_data['trust_tag'].'",';
				break;
			default:
				// TODO: better error handling -scott
		}
		echo '"'.$adset[0]['LP'].'",';
		echo '""';
		echo "\r\n";
	}

	exit();
?>
