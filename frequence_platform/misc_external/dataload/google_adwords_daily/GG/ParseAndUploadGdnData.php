<?php
	echo "Loading GDN site data<br />";
  echo 'Loading file: "'.$fileName.'"...<br />';

  $fileResource = fopen($fileName, "rb");
  if($fileResource == false) {
    die('Couldn\'t open file: '.$fileName);
  }

	include 'dbconfig.php';

  $dblink = mysql_connect($dbhost, $dbuser, $dbpassword);
  if(!$dblink) {
    fclose($fileResource);
    die ( 'Not connected :' . mysql_error());
  }
  mysql_select_db($dbname);

	while(($fields = fgetcsv($fileResource)) != false) {
		//echo '1st While: '.$fields[0].'<br />';
		if($fields[0] == "Account") {
			break; // Skip headers.
		}
	}

	/*
	// Print AdGroups row IDs
	$testAdGroupsQuery = 'SELECT ID
												FROM AdGroups
												WHERE 1';
	$testAdGroupsResult = mysql_query($testAdGroupsQuery) or die(mysql_error());
	$numRows = mysql_num_rows($testAdGroupsResult);
	for($ii=0; $ii<$numRows; $ii += 1) {
		$ID = mysql_result($testAdGroupsResult, $ii, 0);
		echo 'ID: \''.$ID.'\'<br />';
	}
	*/
	$todayTime = strtotime(date("Y-m-d"));
	$twoDaysAgoTime = strtotime("- 2 days", $todayTime);
	//echo '$todayTime: '.date("Y-m-d", $todayTime).'<br />';
	//echo '$twoDaysAgoTime: '.date("Y-m-d", $twoDaysAgoTime).'<br />';

	$missingItemsMapByAdGroupID = array();
	while(($fields = fgetcsv($fileResource)) != false) {
		//echo '2nd While: <pre>'; print_r($fields); echo '</pre><br />';
		if($fields[0] != "Total") {

			$adGroupID = $fields[4];
			$findBusinessAndCampaignFromAdGroups_Query = '
      SELECT BusinessName, CampaignName 
				FROM AdGroups 
				WHERE ID = \''.$adGroupID.'\''; 
			//echo 'Query: '.$findBusinessAndCampaignFromAdGroups_Query.'<br />';
			$findBusinessAndCampaignFromAdGroups_Result = 
					mysql_query($findBusinessAndCampaignFromAdGroups_Query)
					or die(mysql_error());

			if(mysql_num_rows($findBusinessAndCampaignFromAdGroups_Result) > 0) {
				foreach($fields as &$field) {
					$field = mysql_real_escape_string($field);
				}

				$myTime = strtotime($fields[1]);
				if($myTime <= $twoDaysAgoTime) {
					$formattedDate = date("Y-m-d", $myTime);

					if($insertDataType == 'City') {
						$insertCityQuery = 'INSERT IGNORE INTO CityRecords
																	VALUES (\''.$fields[4].'\', \''.
																							$fields[5].'\', \''.
																							$fields[6].'\', \''.
																							$formattedDate.'\', '.
																							$fields[7].', '.
																							$fields[8].', '.
																							$fields[9].')';
						//echo 'Query: '.$insertCityQuery.'<br />';
						$insertCityQueryResult = mysql_query($insertCityQuery) or die(mysql_error());
					}
					else if($insertDataType == 'Site') {
						$insertSiteQuery = 'INSERT IGNORE INTO SiteRecords
																	VALUES (\''.$fields[4].'\', \''.
																							$fields[3].'\', \''.
																							$formattedDate.'\', '.
																							$fields[6].', '.
																							$fields[7].', '.
																							$fields[8].', \''.
																							$fields[3].'\')';
						//echo 'Query: '.$insertSiteQuery.'<br />';
						$insertSiteQueryResult = mysql_query($insertSiteQuery) or die(mysql_error());
					}
					else if($insertDataType == 'Rtg Site') {
						$insertRtgSiteQuery = 'INSERT IGNORE INTO SiteRecords
																VALUES (\''.$fields[4].'\', \''.
																						$fields[3].'\', \''.
																						$formattedDate.'\', '.
																						$fields[6].', '.
																						$fields[7].', '.
																						$fields[8].', \''.
																						$fields[3].'\')';
						//echo 'Query: '.$insertRtgSiteQuery.'<br />';
						$insertRtgSiteQueryResult = mysql_query($insertRtgSiteQuery) or die(mysql_error());
					}
					else {
						die('Unknown $insertDataType: '.$insertDataType.'<br />');
					}
				}
				else {
					// Time of entry is yesterday or today so don't add it.
					$formattedDate = date("Y-m-d", $myTime);
					echo 'AdGroupID: \''.$adGroupID.'\' ignoring item due to it being too new ('.$formattedDate.')<br />';
				}
			}
			else {
				if($missingItemsMapByAdGroupID[$adGroupID] != 1) {
					$missingItemsMapByAdGroupID[$adGroupID] = 1;
					echo 'AdGroupID: \''.$adGroupID.'\' is not in AdGroups table. ('.$fields[0].', '.$fields[5].')<br />';
				}
			}
		}
		else {
			break;
		}
	}

  mysql_close($dblink);
	fclose($fileResource);
	echo 'Upload site data complete.';

?>
