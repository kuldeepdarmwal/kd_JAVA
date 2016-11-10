<?php
	//THIS FILE IS DEPRECIATED. USE index.php td_uploader td_upload INSTEAD 
	//DELETE THIS SOMEDAY MAYBE
	$num_clicks = 0;
	$num_impressions = 0;
	$num_siterecords = 0;
	$num_cityrecords = 0;
	$num_adsizerecords = 0;
	$new_imp_table = "td_raw_impressions_" . date("Y_m_d",strtotime('-1 day'));
	$new_clk_table = "td_raw_clicks_" . date("Y_m_d", strtotime('-1 day'));
	$missing_imps = array();
	$missing_clks = array();

	$num_errors = 0;
	$num_warnings = 0;

	// hack so that we can use the codeigniter database.php config file
	if(!defined('BASEPATH'))
	{
		define('BASEPATH', ' ');
	}
	include('database.php');

	if(isset($db))
	{
		$g_main_database_host_name = $db[$active_group]['hostname'];
		$g_main_database_user = $db[$active_group]['username'];
		$g_main_database_password = $db[$active_group]['password'];
		$g_main_database_name = $db[$active_group]['database'];
		$g_intermediate_database_host_name = $db['td_intermediate']['hostname'];
		$g_intermediate_database_user = $db['td_intermediate']['username'];
		$g_intermediate_database_password = $db['td_intermediate']['password'];
		$g_intermediate_database_name = $db['td_intermediate']['database'];
	}
	else
	{
		die("Failed to load database configuration\n");
	}

	define('database_location', $g_main_database_host_name);
	define('db_username', $g_main_database_user);
	define('db_password', $g_main_database_password);
	define('db_main_database', $g_main_database_name);
	define('db_raw_database', $g_intermediate_database_name);


	//Loads data from Trade desk into an amazon s3 object to be utilized in transfer to raw tables
	function load_data($start_time, $end_time) //$date)
	{
		$start_datetime_post = $start_time;
		$end_datetime_post = $end_time;

		echo "load_data: {$start_datetime_post} - {$end_datetime_post}\n";
		//Create tables
		global $new_imp_table, $new_clk_table;

		$table_gen_impressions = 
		"	CREATE TABLE `{$new_imp_table}` (
				`LogEntryTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
				`ImpressionId` varchar(36) COLLATE utf8_unicode_ci NOT NULL, 
				`WinningPriceCPMInDollars` decimal(6, 4) NOT NULL, 
				`SupplyVendor` varchar(32) COLLATE utf8_unicode_ci NOT NULL, 
				`AdvertiserId` varchar(32) COLLATE utf8_unicode_ci NOT NULL, 
				`CampaignId` varchar(32) COLLATE utf8_unicode_ci NOT NULL, 
				`AdGroupId` varchar(32) COLLATE utf8_unicode_ci NOT NULL, 
				`CreativeId` varchar(32) COLLATE utf8_unicode_ci NOT NULL, 
				`AdWidthInPixels` int(11) NOT NULL, 
				`AdHeightInPixels` int(11) NOT NULL, 
				`Frequency` int(11) NOT NULL, 
				`Site` varchar(512) COLLATE utf8_unicode_ci NOT NULL, 
				`TDID` varchar(36) COLLATE utf8_unicode_ci NOT NULL, 
				`ReferrerCategoriesList` int(11) NOT NULL, 
				`FoldPosition` int(11) NOT NULL, 
				`UserHourOfWeek` int(11) NOT NULL, 
				`CountryLog` varchar(80) COLLATE utf8_unicode_ci NOT NULL, 
				`Region` varchar(80) COLLATE utf8_unicode_ci NOT NULL, 
				`Metro` varchar(80) COLLATE utf8_unicode_ci NOT NULL, 
				`City` varchar(80) COLLATE utf8_unicode_ci NOT NULL, 
				`IPAddress` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
				`VantageLocalId` bigint(10) NOT NULL, 
				KEY `indx_time_vlid` (`LogEntryTime`, `VantageLocalId`),
				KEY `indx_vlid` (`VantageLocalId`)
			) ENGINE = MyISAM DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;
		";

		$table_gen_clicks = 
		"	CREATE TABLE `{$new_clk_table}` (
				`LogEntryTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
				`ClickId` varchar(36) COLLATE utf8_unicode_ci NOT NULL, 
				`IPAddress` varchar(40) COLLATE utf8_unicode_ci NOT NULL, 
				`ReferrerUrl` text COLLATE utf8_unicode_ci NOT NULL, 
				`RedirectUrl` text COLLATE utf8_unicode_ci NOT NULL, 
				`CampaignId` varchar(32) COLLATE utf8_unicode_ci NOT NULL, 
				`ChannelId` text COLLATE utf8_unicode_ci NOT NULL, 
				`AdvertiserId` varchar(32) COLLATE utf8_unicode_ci NOT NULL, 
				`DisplayImpressionId` varchar(36) COLLATE utf8_unicode_ci NOT NULL, 
				`Keyword` text COLLATE utf8_unicode_ci NOT NULL, 
				`KeywordId` text COLLATE utf8_unicode_ci NOT NULL, 
				`MatchType` text COLLATE utf8_unicode_ci NOT NULL, 
				`DistributionNetwork` text COLLATE utf8_unicode_ci NOT NULL, 
				`TDID` varchar(36) COLLATE utf8_unicode_ci NOT NULL, 
				`RawUrl` text COLLATE utf8_unicode_ci NOT NULL, 
				`VantageLocalId` bigint(10) NOT NULL, 
				KEY `indx_time_vlid` (`LogEntryTime`, `VantageLocalId`), 
				KEY `indx_vlid` (`VantageLocalId`)  
			) ENGINE = MyISAM DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;
		";

		$table_gen_db = mysqli_connect(database_location, db_username, db_password, db_raw_database) or die('Connection error: ' . mysqli_error($table_gen_db) . "\n");
		$table_gen_db->query($table_gen_impressions);
		$table_gen_db->query($table_gen_clicks);
		$table_gen_db->close();

		if($start_datetime_post && $end_datetime_post)
		{
			$start_datetime = new DateTime($start_datetime_post); //('2012-06-04 00:00:00');
			$start_date_hour = new DateTime($start_datetime->format("Y-m-d H:00:00"));
			$current_date_hour = clone $start_date_hour;
			$time_interval = new DateInterval('PT1H0M0S');
			$end_datetime = new DateTime($end_datetime_post);
	
			$s3 = new AmazonS3();
	
			while($current_date_hour->getTimestamp() < $end_datetime->getTimestamp())
			{
				global $missing_imps, $missing_clks;
				$bucket_by_date_and_hour = $current_date_hour->format('Y/m/d/H/');
				echo "Loop: {$current_date_hour->format('d-m-Y H:i:s')} {$bucket_by_date_and_hour}\n";

				$impression_buckets_by_hour = $s3->get_object_list(
					"thetradedesk-uswest-partners-vantagelocal",
					array(
						"prefix" => $bucket_by_date_and_hour,
						"pcre" => "/impressions/"
					)
				);

				if(!load_raw_data_feed($s3, "impressions", $impression_buckets_by_hour))
				{
					array_push($missing_imps, $current_date_hour->format("d/m-H"));
				}

				$click_buckets_by_hour = $s3->get_object_list(
					"thetradedesk-uswest-partners-vantagelocal",
					array(
						"prefix" => $bucket_by_date_and_hour,
						"pcre" => "/clicks/"
					)
				);

				if(!load_raw_data_feed($s3, "clicks", $click_buckets_by_hour))
				{
					array_push($missing_clks, $current_date_hour->format("d/m-H"));
				}
				$current_date_hour->add($time_interval);
			}
		}

		$imp_optimize = "OPTIMIZE TABLE {$new_imp_table}";
		$clk_optimize = "OPTIMIZE TABLE {$new_clk_table}";
		$table_opt_db = mysqli_connect(database_location, db_username, db_password, db_raw_database) or die('Connection error: ' . mysqli_error($table_opt_db) . "\n");
		$table_opt_db->query($imp_optimize);
		$table_opt_db->query($clk_optimize);
		$table_opt_db->close();
	}

	//Function takes in an s3 object full of trade desk data
	//And which table we're going into and dumps the raw data into that table
	function load_raw_data_feed($s3, $destination_table_type, $source_files_list)
	{
		foreach($source_files_list as $hour_bucket)
		{
			$temp_file_name = '/tmp/ttdFileToDecompress.log.gz';
			$object_response = $s3->get_object(
				'thetradedesk-uswest-partners-vantagelocal', 
				$hour_bucket, 
				array('fileDownload' => $temp_file_name)
			);

			if($object_response->isOK())
			{
				$fileData = gzfile($temp_file_name);
				if($fileData)
				{
					if($destination_table_type == "impressions")
					{
						foreach($fileData as $fileLine)
						{
							$cells = str_getcsv($fileLine, "\t");
							upload_impression_row_to_raw_data_feed($cells);
						}
						echo "Imps: OK\n";
						return true;
					}
					elseif($destination_table_type == "clicks")
					{
						foreach($fileData as $fileLine)
						{
							$cells = str_getcsv($fileLine, "\t");
							upload_click_row_to_raw_data_feed($cells);
						}
						echo "Clks: OK\n";
						return true;
					}
					else
					{
						die("Unknown destination_table_type: {$destination_table_type}\n");
					}
				}
				else
				{
					die("Failed to open or gunzip for bucket: {$hour_bucket}\n");
				}
			}
			else
			{
				die("Failed to get response from Amazon s3 object.\n");
			}
		}
		return false;
	}

	//Inserts a row into td_raw_impressions
	function upload_impression_row_to_raw_data_feed($impression_row_array)
	{
		$impression_id_index = 1;
		$temp = $impression_row_array[$impression_id_index];
		$vl_id = substr($temp, 0, 4).substr($temp, 32, 4);
		$vl_id_int = base_convert($vl_id, 16, 10);
		$cleaned = clean_url($impression_row_array[11]);
		global $num_impressions, $new_imp_table, $process_date;
		
		if(substr($impression_row_array[0], 0, 10) == $process_date)
		{
			$query = 
			"	INSERT IGNORE INTO {$new_imp_table}
				VALUES (
					'{$impression_row_array[0]}',
					'{$impression_row_array[1]}',
					'{$impression_row_array[2]}',
					'{$impression_row_array[3]}',
					'{$impression_row_array[4]}',
					'{$impression_row_array[5]}',
					'{$impression_row_array[6]}',
					'{$impression_row_array[7]}',
					'{$impression_row_array[8]}',
					'{$impression_row_array[9]}',
					'{$impression_row_array[10]}',
					'{$cleaned}',
					'{$impression_row_array[12]}',
					'{$impression_row_array[13]}',
					'{$impression_row_array[14]}',
					'{$impression_row_array[15]}',
					'{$impression_row_array[16]}',
					'{$impression_row_array[17]}',
					'{$impression_row_array[18]}',
					'{$impression_row_array[19]}',
					'{$impression_row_array[20]}',
					'{$vl_id_int}'
				)
			";
			$data_feed_db = mysqli_connect(database_location, db_username, db_password, db_raw_database) or die('Connection error: ' . mysqli_error($data_feed_db) . "\n");
			$data_feed_db->query($query);
			if($data_feed_db->affected_rows > 0)
			{
				$num_impressions++;
			}
		}
	}

	//Inserts a row into td_raw_clicks
	function upload_click_row_to_raw_data_feed($click_row_array)
	{
		$display_impression_id_index = 8;
		$temp = $click_row_array[$display_impression_id_index];
		$vl_id = substr($temp, 0, 4) . substr($temp, 32, 4);
		$vl_id_int = base_convert($vl_id, 16, 10);
		global $num_clicks, $new_clk_table, $process_date;
		
		if(substr($click_row_array[0], 0, 10) == $process_date)
		{
			$query = 
			"	INSERT IGNORE INTO {$new_clk_table}
				VALUES (
					'{$click_row_array[0]}',
					'{$click_row_array[1]}',
					'{$click_row_array[2]}',
			'" .	mysql_real_escape_string($click_row_array[3]) . "',
			'" .	mysql_real_escape_string($click_row_array[4]) . "',
					'{$click_row_array[5]}',
					'{$click_row_array[6]}',
					'{$click_row_array[7]}',
					'{$click_row_array[8]}',
					'{$click_row_array[9]}',
					'{$click_row_array[10]}',
					'{$click_row_array[11]}',
					'{$click_row_array[12]}',
					'{$click_row_array[13]}', 
					'" . mysql_real_escape_string($click_row_array[14]) . "',
					'{$vl_id_int}'
				)
			";

			$data_feed_db = mysqli_connect(database_location, db_username, db_password, db_raw_database) or die('Connection error: ' . mysqli_error($data_feed_db) . "\n");
			$data_feed_db->query($query);
			if($data_feed_db->affected_rows > 0)
			{
				$num_clicks++;
			}
		}
	}

	//Cleans urls to match criteria for a Base_Site
	//Moved all regex to this giant if-chain because I clearly have no idea what I'm doing
	function clean_url($test_string)
	{
		if(preg_match("*google.site-not-provided*", $test_string))
		{
			return "All other sites";
		} 
		if(preg_match("*casale.site-not-provided*", $test_string))
		{
			return "All other sites";
		} 
		if(preg_match("*rubicon.site-not-provided*", $test_string))
		{
			return "All other sites";
		}
		if(preg_match("*appnexus.site-not-provided*", $test_string))
		{
			return "All other sites";
		}
		if(preg_match("*bmp.gunbroker.com*", $test_string))
		{
			return "All other sites";
		}
		if(preg_match("*nym1.ib.adnxs.com*", $test_string))
		{
			return "All other sites";
		}
		if(preg_match("*dakinemedia.net*", $test_string))
		{
			return "All other sites";
		}
		if(preg_match("*mail.yahoo.com*", $test_string))
		{
			return "All other sites";
		}
		if(preg_match("*ad.doubleclick.net*", $test_string))
		{
			return "All other sites";
		}
		if(preg_match("*site9264.com*", $test_string))
		{
			return "All other sites";
		}
		if(preg_match("*yahoonetplus.com*", $test_string))
		{
			return "All other sites";
		}
		if(preg_match("*optimized-by.rubiconproject.com*", $test_string))
		{
			return "All other sites";
		}
		if(preg_match("*i.vemba.com*", $test_string))
		{
			return "All other sites";
		}
		if(preg_match("*funnie.st*", $test_string))
		{
			return "All other sites";
		}
		if(preg_match("*m.datehookup.com*", $test_string))
		{
			return "All other sites";
		}
		if(preg_match("*meetme.com*", $test_string))
		{
			return "All other sites";
		}
		if(preg_match("*animefreak.tv*", $test_string))
		{
			return "All other sites";
		}
		if(preg_match("*coed.com*", $test_string))
		{
			return "All other sites";
		}
		if(preg_match("*failblog.org*", $test_string))
		{
			return "All other sites";
		}
		if(preg_match("*rantgirls.com*", $test_string))
		{
			return "All other sites";
		}
		if(preg_match("*cdn.lovedgames.com*", $test_string))
		{
			return "All other sites";
		}
		if($test_string == "{techno.page_url}")
		{
			return "All other sites";
		}
		if(!preg_match('*.[A-Za-z0-9\-]{1,}[\.][A-Za-z\-]{2,3}*', $test_string))
		{
			return "All other sites";
		} 
		if(preg_match("/^(:)/", $test_string))
		{
			return "All other sites";
		} 

		$out = $test_string;
		if(preg_match("/^(www.*)/", $test_string))
		{
			$out = substr($out, strrpos($out, "www.")+4);
		}
		if(preg_match("*\?*", $out))
		{	
			$out = substr($out, 0, strrpos($out, '?'));
		}
		if(preg_match("*\@*", $out))
		{
			$out = substr($out,strrpos($out, '@')+1);
		}
		if(preg_match("(\{.*?\})", $out))
		{
			$out = substr($out, strrpos($out, '{')+1, strrpos($out, '}')-1);
		}
		if(preg_match("*\/*", $out))
		{
			$out = substr($out, 0, strrpos($out, '/'));
		} 
		if(!preg_match("*\.*", $out))
		{
			return "All other sites";
		}
		if($out == "")
		{
			return "All other sites";
		} 
		return $out;	
		}

	//Grabs and aggregates all clicks and impressions
	//for a given date, and aggregates and passes rows
	//generated to be put into CityRecords/SiteRecords
	function process_and_transfer_data($date_to_process) //$date)
	{
		if($date_to_process)
		{
			same_day_clean($date_to_process);
			$sites_aggregate_response = aggregate_impression_and_click_data("sites", $date_to_process);
			echo "Sites: " . mysql_num_rows($sites_aggregate_response) . "\n";
			upload_impression_and_click_data("sites", $sites_aggregate_response, $date_to_process);

			$cities_aggregate_response = aggregate_impression_and_click_data("cities", $date_to_process);
			echo "Cities: " . mysql_num_rows($cities_aggregate_response) . "\n";
			upload_impression_and_click_data("cities", $cities_aggregate_response, $date_to_process);

			$ad_sizes_aggregate_response = aggregate_impression_and_click_data("sizes", $date_to_process);
			echo "Sizes: " . mysql_num_rows($ad_sizes_aggregate_response) . "\n";
			upload_impression_and_click_data("sizes", $ad_sizes_aggregate_response, $date_to_process);
		}
	}

	function same_day_clean($today)
	{
		global $new_clk_table, $new_imp_table;
		$id_get_query = "SELECT DISTINCT AdGroupId FROM {$new_imp_table}"; 

		$records_db = mysql_connect(database_location, db_username, db_password);
		mysql_select_db(db_raw_database);
		$result = mysql_query($id_get_query, $records_db);
		mysql_select_db(db_main_database);

		while ($row = mysql_fetch_array($result, MYSQL_NUM))
		{
			$delete_query = "DELETE FROM SiteRecords WHERE AdGroupId = '{$row[0]}' AND Site = 'All other sites' AND Date = '{$today}'";
			mysql_query($delete_query);
		}
	}

	//Aggregation function that generates the data which will
	//wind up in the Site/CityRecords tables
	function aggregate_impression_and_click_data($data_type, $start_date_and_time, $end_date_and_time = NULL)
	{
		$start = $start_date_and_time;
		global $new_clk_table, $new_imp_table;
		if(isset($end_date_and_time))
		{
			$end = $end_date_and_time;
		}
		else
		{
			$rounded_to_date = date("Y-m-d", strtotime($start_date_and_time));
			$start = date("Y-m-d H:i:s", strtotime($rounded_to_date));
			$end = date("Y-m-d H:i:s", strtotime("+ 1 day", strtotime($rounded_to_date)));
		}
		
		$bindings = array($start, $end);
		if($data_type == 'sites')
		{
			$query = 
			"	SELECT 
					ci.AdGroupId as aid,
					ci.Site as ss,
					ci.Date as date,
					ci.Impressions as imp,
					COALESCE(cc.Clicks, 0) as clk,
					ci.Cost as tot
				FROM
				(	SELECT 
						a.AdGroupId as AdGroupID,
						a.Site as Site,
						DATE(a.LogEntryTime) as Date,
						count(a.ImpressionId) as Impressions,
						SUM(0) as Clicks,
						SUM(a.WinningPriceCPMInDollars)/1000 as Cost
					FROM {$new_imp_table} a 
					GROUP BY 
						a.AdGroupId,
						a.Site,
						DATE(a.LogEntryTime)
				) as ci 
				LEFT JOIN 
				(	SELECT 
						a.AdGroupId as AdGroupID,
						a.Site as Site,
						DATE(a.LogEntryTime) as Date,
						SUM(0) as Impressions,
						count(DISTINCT b.ClickId) as Clicks,
						sum(a.WinningPriceCPMInDollars)/1000 as Cost
					FROM {$new_clk_table} b
					INNER JOIN {$new_imp_table} a
					ON a.VantageLocalId = b.VantageLocalId
					GROUP BY 
						a.AdGroupId,
						a.Site,
						DATE(a.LogEntryTime)
				) as cc
				ON
					ci.AdGroupId = cc.AdgroupId AND
					ci.Site = cc.Site AND
					ci.Date = cc.Date 
				WHERE 1
				ORDER BY
					ci.Impressions DESC, 
					ci.Site ASC, 
					ci.AdGroupId DESC 
			";
		}
		elseif($data_type == 'cities')
		{
			$query = 
			"	SELECT 
					ci.AdGroupId as aid,
					ci.City as cty,
					ci.Region as reg,
					ci.Date as date,
					ci.Impressions as Impressions,
					COALESCE(cc.Clicks, 0) as Clicks,
					ci.Cost as Cost
				FROM
				(	SELECT 
						a.AdGroupId as AdGroupID,
						a.City as City,
						a.Region as Region,
						DATE(a.LogEntryTime) as Date,
						count(a.ImpressionId) as Impressions,
						SUM(0) as Clicks,
						SUM(a.WinningPriceCPMInDollars)/1000 as Cost
					FROM {$new_imp_table} a 
					GROUP BY 
						a.AdGroupId,
						a.City,
						a.Region,
						DATE(a.LogEntryTime)
				) as ci 
				LEFT JOIN 
				(	SELECT 
						a.AdGroupId as AdGroupID,
						a.City as City,
						a.Region as Region,
						DATE(a.LogEntryTime) as Date,
						SUM(0) as Impressions,
						count(DISTINCT b.ClickId) as Clicks,
						sum(a.WinningPriceCPMInDollars)/1000 as Cost
					FROM {$new_clk_table} b
					INNER JOIN {$new_imp_table} a
					ON a.VantageLocalId = b.VantageLocalId
					GROUP BY 
						a.AdGroupId,
						a.City,
						a.Region,
						DATE(a.LogEntryTime)
				) as cc
				ON
					ci.AdGroupId = cc.AdgroupId AND
					ci.City = cc.City AND
					ci.Region = cc.Region AND
					ci.Date = cc.Date 
				WHERE 1
				ORDER BY
					ci.Impressions DESC, 
					ci.City ASC, 
					ci.Region ASC, 
					ci.AdGroupId DESC
			";	
		}
		elseif($data_type = "sizes")
		{
			$query = 
			"	SELECT 
					ci.AdGroupId as aid,
					CONCAT(ci.Width, CONCAT('x', ci.Height)) as size,
					ci.Date as date,
					ci.Impressions as imp,
					COALESCE(cc.Clicks, 0) as clk,
					ci.Cost as tot
				FROM
				(	SELECT 
						a.AdGroupId as AdGroupID,
						a.AdWidthInPixels as Width,
						a.AdHeightInPixels as Height,
						DATE(a.LogEntryTime) as Date,
						count(a.ImpressionId) as Impressions,
						SUM(0) as Clicks,
						SUM(a.WinningPriceCPMInDollars)/1000 as Cost
					FROM {$new_imp_table} a 
					GROUP BY 
						a.AdGroupId,
						a.AdWidthInPixels,
						DATE(a.LogEntryTime)
				) as ci 
				LEFT JOIN 
				(	SELECT 
						a.AdGroupId as AdGroupID,
						a.AdWidthInPixels as Width,
						a.AdHeightInPixels as Height,
						DATE(a.LogEntryTime) as Date,
						SUM(0) as Impressions,
						count(DISTINCT b.ClickId) as Clicks,
						sum(a.WinningPriceCPMInDollars)/1000 as Cost
					FROM {$new_clk_table} b
					INNER JOIN {$new_imp_table} a
					ON a.VantageLocalId = b.VantageLocalId
					GROUP BY 
						a.AdGroupId,
						a.AdWidthInPixels,
						DATE(a.LogEntryTime)
				) as cc
				ON
					ci.AdGroupId = cc.AdgroupId AND
					ci.Width = cc.Width AND
					ci.Date = cc.Date 
				WHERE 1
				ORDER BY
					ci.AdGroupId DESC, 
					ci.Width ASC, 
					ci.Impressions DESC
			";
		}
		else
		{
			die("Unknown data_type: {$data_type}\n");
		}
		echo "{$query}\n";
		$db_raw = mysql_connect(database_location, db_username, db_password);
		mysql_select_db(db_raw_database);
		$response = mysql_query($query, $db_raw);
		return $response;
	}

	//Inserts aggregate rows into CityRecords/SiteRecords.
	function upload_impression_and_click_data($data_type, $aggregate_response, $date)
	{
		if(sizeOf($aggregate_response) > 0)
		{
			$db_main = mysqli_connect(database_location, db_username, db_password, db_main_database) or die('Connection error: ' . mysqli_error($db_main) . "\n");
			if($data_type == 'sites')
			{
				global $num_siterecords;
				while($aggregate_row = mysql_fetch_array($aggregate_response))
				{
					$cost = $aggregate_row['tot'] / $aggregate_row['imp'];
					$query = 
					"	INSERT INTO SiteRecords
						(AdGroupID, Site, Date, Impressions, Clicks, Cost, Base_Site)	
						VALUES 
						(
							'{$aggregate_row['aid']}',
							'{$aggregate_row['ss']}',
							'{$aggregate_row['date']}',
							'{$aggregate_row['imp']}',
							'{$aggregate_row['clk']}',
							'{$cost}',
							'{$aggregate_row['ss']}'
						)
						ON DUPLICATE KEY UPDATE
							Impressions = '{$aggregate_row['imp']}', 
							Clicks = '{$aggregate_row['clk']}', 
							Cost = '{$cost}', 
							Base_Site = '{$aggregate_row['ss']}';
					";
					
					$db_main->query($query);
					if($db_main->affected_rows > 0)
					{
						$num_siterecords++;
					}
				}
				$db_main->close();
			}
			elseif($data_type == 'cities')
			{
				global $num_cityrecords;
				while($aggregate_row = mysql_fetch_array($aggregate_response))
				{
					$cost = $aggregate_row['Cost'] / $aggregate_row['Impressions'];
					
					$query = 
					"	INSERT INTO CityRecords
						(AdGroupID, City, Region, Date, Impressions, Clicks, Cost)	
						VALUES 
						(
							'{$aggregate_row['aid']}', 
							'{$aggregate_row['cty']}', 
							'{$aggregate_row['reg']}', 
							'{$aggregate_row['date']}', 
							'{$aggregate_row['Impressions']}', 
							'{$aggregate_row['Clicks']}', 
							'{$cost}'
						)
						ON DUPLICATE KEY UPDATE 
							Impressions = '{$aggregate_row['Impressions']}', 
							Clicks = '{$aggregate_row['Clicks']}', 
							Cost = '{$cost}';
					";
					
					$db_main->query($query);
					if($db_main->affected_rows > 0)
					{
						$num_cityrecords++;
					}
				}
				$db_main->close();
			}
			elseif($data_type == 'sizes')
			{
				global $num_adsizerecords;
				while($aggregate_row = mysql_fetch_array($aggregate_response))
				{
					$cost = $aggregate_row['tot'] / $aggregate_row['imp'];
					$query = 
					"	INSERT INTO report_ad_size_records
						(AdGroupID, Size, Date, Impressions, Clicks, Cost)		
						VALUES
						(
							'{$aggregate_row['aid']}', 
							'{$aggregate_row['size']}', 
							'{$aggregate_row['date']}', 
							'{$aggregate_row['imp']}', 
							'{$aggregate_row['clk']}', 
							'{$cost}'
						)
						ON DUPLICATE KEY UPDATE
							Impressions = '{$aggregate_row['imp']}', 
							Clicks = '{$aggregate_row['clk']}', 
							Cost = '{$cost}';
					";

					$db_main->query($query);
					if($db_main->affected_rows > 0)
					{
						$num_adsizerecords++;
					}
				}
				$db_main->close();
			}
		}
	}

	//Scoops up tail aggregate rows in the SiteRecords table
	//Bunches up rows with Less than 10 impressions and no clicks
	//and merges them with the "other" row for that AdGroupID/Date
	function collate_loose_impressions($date, $data_type) 
	{
		$db_collate = mysqli_connect(database_location, db_username, db_password, db_main_database) or die('Connection error: ' . mysqli_error($db_collate) . "\n");
 		
 		if ($data_type == 'sites')
 		{
 			$scoop_query = 
			"	INSERT INTO SiteRecords 
				(AdGroupID, Site, Date, Impressions, Clicks, Cost, Base_Site)
				(	SELECT 
						AdGroupID, 
						'OTHER SITES', 
						DATE, 
						SUM(Impressions), 
						SUM(Clicks), 
						Cost, 
						'All other sites' AS Imp
					FROM SiteRecords
					WHERE 
						Date = '{$date}' AND 
						(
							(
								Impressions < 10 AND 
								Clicks = 0
							) OR 
							Site = 'All other sites'
						)
					GROUP BY AdGroupID, DATE
				)
			";

			$clear_query = 
			"	DELETE FROM SiteRecords 
				WHERE 
					Date = '{$date}' AND 
					(
						(
							Impressions < 10 AND 
							Clicks = 0 AND 
							Site != 'OTHER SITES'
						) OR 
						Site = 'All other sites'
					)
			";

			$replace_query = 
			"	UPDATE SiteRecords 
				SET Site = 'All other sites' 
				WHERE 
					Date = '{$date}' AND 
					Base_Site = 'All other sites'
			";
 		}
 		else if ($data_type == 'cities')
 		{
 			$scoop_query = 
			"	INSERT INTO CityRecords 
				(AdGroupID, City, Region, Date, Impressions, Clicks, Cost)
				(	SELECT 
						AdGroupID, 
						'All other cities', 
						'All other regions', 
						DATE, 
						SUM(Impressions), 
						SUM(Clicks), 
						Cost
					FROM CityRecords
					WHERE 
						Date = '{$date}' AND 
						(
							(
								Impressions < 10 AND 
								Clicks = 0
							) OR 
							Region = 'All other regions'
						)
					GROUP BY AdGroupID, DATE
				)
			";

			$clear_query = 
			"	DELETE FROM CityRecords 
				WHERE 
					Date = '{$date}' AND 
					(
						(
							Impressions < 10 AND 
							Clicks = 0 AND 
							Site != 'OTHER SITES'
						) OR 
						Site = 'All other sites'
					)
			";

			$replace_query = 
			"	UPDATE CityRecords 
				SET Site = 'All other sites' 
				WHERE 
					Date = '{$date}' AND 
					Base_Site = 'All other sites'
			";
 		}
		

		$db_collate->query($scoop_query);
		$db_collate->query($clear_query);
		$db_collate->query($replace_query);
	}

	function verify_raw_data($destination_table_type, $source_files_list)
	{
		if(count($source_files_list) > 0)
		{
			echo "{$destination_table_type}: OKAY!\n";
			return true;
		} 
		else
		{
			echo "{$destination_table_type}: FAIL!\n";
			return false;
		}
	}

	function verify_date($start_date, $end_date)
	{
		$start_datetime = new DateTime($start_date); //('2012-06-04 00:00:00');
		$start_date_hour = new DateTime($start_datetime->format("Y-m-d H:00:00"));
		$current_date_hour = clone $start_date_hour;
		$time_interval = new DateInterval('PT1H0M0S');
		$end_datetime = new DateTime($end_date);
		$s3 = new AmazonS3();

		while($current_date_hour->getTimestamp() < $end_datetime->getTimestamp())
		{
			$bucket_by_date_and_hour = $current_date_hour->format('Y/m/d/H/');
			echo "Check-Loop: {$current_date_hour->format('d-m-Y H:i:s')} {$bucket_by_date_and_hour}\n";

			$impression_buckets_by_hour = $s3->get_object_list(
				"thetradedesk-uswest-partners-vantagelocal",
				array(
					"prefix" => $bucket_by_date_and_hour,
					"pcre" => "/impressions/"
				)
			);
			
			if(!verify_raw_data("impressions", $impression_buckets_by_hour))
			{
				return false;
			}

			$click_buckets_by_hour = $s3->get_object_list(
				"thetradedesk-uswest-partners-vantagelocal",
				array(
					"prefix" => $bucket_by_date_and_hour,
					"pcre" => "/clicks/"
				)
			);
			
			if(!verify_raw_data("clicks", $click_buckets_by_hour))
			{
				// return FALSE;
			}
			$current_date_hour->add($time_interval);
		}
		return true;
	}

	function execute_all_impression_caching()
	{
		$caching_response = shell_exec("php ../www/index.php campaigns_main cache_all_impression_amounts");
		
		$cache_from = "Frequence Daily Caching <noreply@frequence.com>";
		$cache_to = "Tech Logs <tech.logs@frequence.com>";
		$cache_subject = "Nightly Impression Caching (" . date("m/d", strtotime('-1 day')) . ")";
		$cache_message = $caching_response;
		
		send_email($cache_from, $cache_to, $cache_subject, $cache_message, 'text');
	}

	function send_email($from, $to, $subject, $message, $body_type = 'html')
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, 'api:key-1bsoo8wav8mfihe11j30qj602snztfe4');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v2/mg.brandcdn.com/messages');
		curl_setopt(
			$ch, 
			CURLOPT_POSTFIELDS,
			array('from' => $from,
				'to' => $to,
				'subject' => $subject,
				$body_type => $message
			)
		);
		$result = curl_exec($ch);
		curl_close($ch);
		echo $result;
	}

	function check_and_report_accuracy_of_site_and_city_data(&$message_sub_array, $data_type, $site_value, $city_value, $total_value)
	{
		global $num_errors, $num_warnings;
		$error = (max($site_value, $city_value, $total_value) - min($site_value, $city_value, $total_value)) / min($site_value, $city_value, $total_value);
		$site_value_string = number_format($site_value);
		$city_value_string = number_format($city_value);
		$total_value_string = number_format($total_value);
		$imps_string = 'City/Site ' . ucfirst($data_type) . ': ';
		$imps_string .= (!($city_value == $site_value)) ? "NOT OK ({$city_value_string}/{$site_value_string})" : "OK ({$city_value_string}/{$site_value_string})";
		$message_sub_array[] = $imps_string;
		if ($error > 0 && $error <= 0.01)
		{
			$message_sub_array[] = '[WARNING] Small Sites/Cities records mismatch';
			$num_warnings++;
		}
		else if ($error > 0.01)
		{
			$message_sub_array[] = '[ERROR] Sites/Cities records mismatch';
			$num_errors++;
		}
	}
//WARNING: Spliced function in from td_uploader_pre_roll_google_scoop_ryan - Don't test this.
 function unify_pre_roll_google_sites($raw_table_name)
 {
	 $google_unify_query = "
				UPDATE 
					".$raw_table_name." as raw_imps
				SET
					Site = \"Google Ad Network\"
				WHERE
					(Site LIKE \"%.youtube.co%\" OR
					Site LIKE \"%.google.co%\" OR
					Site LIKE \"youtube.co%\" OR
					Site LIKE \"youtu.be%\" OR
					Site LIKE \"google.co%\")
					AND 
					AdGroupId IN
					(
					SELECT 
						adg.ID
					FROM
						".db_main_database.".AdGroups adg
					WHERE
						adg.target_type LIKE \"%Pre-Roll%\"
					)";
	 
	echo $google_unify_query."\n";
	$db_raw = mysql_connect(database_location, db_username, db_password);
	mysql_select_db(db_raw_database); 
	$response = mysql_query($google_unify_query, $db_raw);
	if(!$response)
	{
		return false;
	}
	return true;
	 
 }

?>
<?php
	
	$start_timestamp = new DateTime("now");
	$is_successful = true;
	$is_rerunning = false;
	require_once('aws-sdk-1.5.6.2/sdk.class.php');
	
	if($argc > 1)
	{
		if($argc != 2)
		{
			echo "USAGE: new_td_upload.php YYYY-MM-DD\n";
			return;
		}
		if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $argv[1]))
		{
			echo "USAGE: new_td_upload.php YYYY-MM-DD <-- Yo, format your date correctly, please.\n";
			return;
		}
		if(!strtotime($argv[1]))
		{
			echo "USAGE: new_td_upload.php YYYY-MM-DD <-- Yeah. Really funny. Use a valid date.\n";
			return;
		}
		$start_time = date("m/d/Y 00:00:00", strtotime($argv[1]));
		$early_time = date('m/d/Y 23:00:00', strtotime('-1 day', strtotime($start_time)));
		$end_time = date('m/d/Y 00:00:00', strtotime('+1 day', strtotime($start_time)));
		echo "Ah, so we're going to run {$start_time}\n";
		$is_rerunning = true;
	}
	else 
	{
		$early_time = date("m/d/Y 23:00:00", strtotime('-2 day'));
		$start_time = date("m/d/Y 00:00:00", strtotime('-1 day'));
		$end_time   = date("m/d/Y 00:00:00", strtotime('-0 day'));

		$date_verify = false;
		$datecheck_db = mysqli_connect(database_location, db_username, db_password, db_raw_database) or die('Connection error: ' . mysqli_error($datecheck_db) . "\n");
		while(!$date_verify)
		{
			echo "Hold up. Verifying {$start_time}\n";
			//Check if this day is already done
			$imp_table_name = "td_raw_impressions_" . date("Y_m_d",strtotime($start_time));
			
			if($datecheck_db->query("DESCRIBE {$imp_table_name}"))
			{
				echo "Date {$start_time} already has data.\n";
				$date_verify = true;
				$early_time = date('m/d/Y 23:00:00', strtotime('+1 day', strtotime($early_time)));
				$start_time = date('m/d/Y 00:00:00', strtotime('+1 day', strtotime($start_time)));
				$end_time = date('m/d/Y 00:00:00', strtotime('+1 day', strtotime($end_time)));

				echo "{$start_time}\n";
			}
			else
			{
				//There's no data for this day, so let's step back
				echo "$start_time Doesn't have any data yet, let's check yesterday.\n";
				$early_time = date('m/d/Y 23:00:00', strtotime('-1 day', strtotime($early_time)));
				$start_time = date('m/d/Y 00:00:00', strtotime('-1 day', strtotime($start_time)));
				$end_time = date('m/d/Y 00:00:00', strtotime('-1 day', strtotime($end_time)));
			}
		}

		if(!verify_date($start_time, $end_time))
		{
			//Oldest non-loaded day is not in there yet
			echo "NOPE!\n";
			return;
		}
		echo "Cool.\n";
	}

	$process_date = date("Y-m-d", strtotime($start_time));
	global $new_imp_table, $new_clk_table, $num_errors, $num_warnings;
	$new_imp_table = "td_raw_impressions_" . date("Y_m_d",strtotime($start_time));
	$new_clk_table = "td_raw_clicks_" . date("Y_m_d", strtotime($start_time));
	
	$new_imp_table .= ($is_rerunning) ? '_REDO' : '';
	$new_clk_table .= ($is_rerunning) ? '_REDO' : '';
	

	echo "{$start_time}\n{$end_time}\n{$process_date}\n";
	$message = array();
	$message['tradedesk_data_upload'] = array();
	$message['tradedesk_data_upload'][] = '<u>TRADEDESK DATA UPLOAD</u>'; 
	$message['tradedesk_data_upload'][] = 'DATE PROCESSED: ' . date("M d,Y", strtotime($start_time));
	$message['tradedesk_data_upload'][] = 'PROCESS STARTED: ' . date("F j, Y, H:i:s") . ' GMT';

	load_data($early_time, $start_time);
	load_data($start_time, $end_time);

	$message['s3_buckets'] = array();
	$message['s3_buckets'][] = '<u>S3 BUCKETS</u>';
	$message['s3_buckets'][] = 'Impressions: ' . (25 - count($missing_imps)) . '/25 found';
	$uh_oh = false;
	if(count($missing_imps) > 0)
	{
		$uh_oh = true;
		$message['s3_buckets'][] = '(MISSING: ' . implode(', ', $missing_imps) . ')';
		$num_errors++;
	}
	
	$message['s3_buckets'][] = 'Clicks: ' . (25 - count($missing_clks)) . '/25 found';
	if(count($missing_clks) > 0)
	{
		$uh_oh = true;
		$message['s3_buckets'][] = '(MISSING: ' . implode(', ', $missing_clks) . ')';
		$num_errors++;
	}

	$raw_timestamp = new DateTime("now");
	$message['raw_data_loaded'] = array();
	$message['raw_data_loaded'][] = '<u>RAW DATA LOADED</u> (' . date_diff($start_timestamp, $raw_timestamp)->format("%imin, %ss") . ')';
	$message['raw_data_loaded'][] = 'Raw Clicks: ' . number_format($num_clicks);
	$message['raw_data_loaded'][] = 'Raw Impressions: ' . number_format($num_impressions);

	process_and_transfer_data($process_date);

	$agg_timestamp = new DateTime("now");
	$message['raw_binned_data_loaded'] = array();
	$message['raw_binned_data_loaded'][] = '<u>RAW BINNED DATA LOADED</u> (' . date_diff($raw_timestamp, $agg_timestamp)->format("%imin, %ss") . ')';

	$site_imps = "SELECT SUM(Impressions) FROM SiteRecords WHERE Date = '{$process_date}'";

	$site_clicks = "SELECT SUM(Clicks) FROM SiteRecords WHERE Date = '{$process_date}'";

	$city_imps = "SELECT SUM(Impressions) FROM CityRecords WHERE Date = '{$process_date}'";

	$city_clicks = "SELECT SUM(Clicks) FROM CityRecords WHERE Date = '{$process_date}'";

	$adsize_imps = "SELECT SUM(Impressions) FROM report_ad_size_records WHERE Date = '{$process_date}'";

	$adsize_clicks = "SELECT SUM(Clicks) FROM report_ad_size_records WHERE Date = '{$process_date}'";

	$loose_site_imps = "SELECT SUM(Impressions) FROM SiteRecords WHERE Date = '{$process_date}' AND Impressions < 10 AND Clicks = 0 AND Site != 'All other sites'";

	$loose_site_rows = "SELECT COUNT(*) FROM SiteRecords WHERE Date = '{$process_date}' AND Impressions < 10 AND Clicks = 0 AND Site != 'All other sites'";

	$loose_city_imps = "SELECT SUM(Impressions) FROM SiteRecords WHERE Date = '{$process_date}' AND Impressions < 10 AND Clicks = 0 AND Site != 'All other sites'";

	$loose_city_rows = "SELECT COUNT(*) FROM SiteRecords WHERE Date = '{$process_date}' AND Impressions < 10 AND Clicks = 0 AND Site != 'All other sites'";

	$num_site_rows = "SELECT COUNT(*) FROM SiteRecords WHERE Date = '{$process_date}'";

	$num_city_rows = "SELECT COUNT(*) FROM CityRecords WHERE Date = '{$process_date}'";

	$num_ad_size_rows = "SELECT COUNT(*) FROM report_ad_size_records WHERE Date = '{$process_date}'";

	$total_s_rows = "SELECT COUNT(*) FROM SiteRecords";

	$db_raw = mysql_connect(database_location, db_username, db_password);
	mysql_select_db(db_main_database);

	$response = mysql_query($site_imps, $db_raw);
	$s_impressions = mysql_result($response, 0);

	$response = mysql_query($site_clicks, $db_raw);
	$s_clicks = mysql_result($response, 0);

	$response = mysql_query($city_imps, $db_raw);
	$c_impressions = mysql_result($response, 0);

	$response = mysql_query($city_clicks, $db_raw);
	$c_clicks = mysql_result($response, 0);

	$data_types = array('impressions', 'clicks');

	$message['raw_binned_data_loaded'][] = 'New Site Records: ' . number_format($num_siterecords);
	$message['raw_binned_data_loaded'][] = 'New City Records: ' . number_format($num_cityrecords);

	check_and_report_accuracy_of_site_and_city_data($message['raw_binned_data_loaded'], 'impressions', $s_impressions, $c_impressions, $num_impressions);
	check_and_report_accuracy_of_site_and_city_data($message['raw_binned_data_loaded'], 'clicks', $s_clicks, $c_clicks, $num_clicks);

	$response = mysql_query($loose_site_imps, $db_raw);
	$l_site_imps = mysql_result($response, 0);

	$response = mysql_query($loose_site_rows, $db_raw);
	$l_site_rows = mysql_result($response, 0);

	$response = mysql_query($num_site_rows, $db_raw);
	$s_rows = mysql_result($response, 0);

	$response = mysql_query($num_city_rows, $db_raw);
	$c_rows = mysql_result($response, 0);

	$response = mysql_query($num_ad_size_rows, $db_raw);
	$a_s_rows = mysql_result($response, 0);

	$response = mysql_query($site_imps, $db_raw);
	$num_s_imps = mysql_result($response, 0);

	$response = mysql_query($site_clicks, $db_raw);
	$num_s_clks = mysql_result($response, 0);

	collate_loose_impressions($process_date);

	$tail_timestamp = new DateTime("now");
	$message['tail_aggregation'] = array();
	$message['tail_aggregation'][] = '<u>TAIL AGGREGATION</u> (' . date_diff($agg_timestamp, $tail_timestamp)->format("%imin, %ss") . ')';
	$message['tail_aggregation'][] = 'Aggregate Tail Site Records: ' . number_format($l_site_rows) . ' (' . number_format(intval(100 * ($l_site_rows / $num_siterecords))) . '%)';
	$message['tail_aggregation'][] = 'Aggregate Tail Site Impressions: ' . number_format($l_site_imps) . ' (' . number_format(intval(100 * ($l_site_imps / $s_impressions))) . '%)';
	$message['tail_aggregation'][] = 'Aggregate Tail City Records: ' . number_format($l_site_rows) . ' (' . number_format(intval(100 * ($l_site_rows / $num_siterecords))) . '%)';
	$message['tail_aggregation'][] = 'Aggregate Tail City Impressions: ' . number_format($l_site_imps) . ' (' . number_format(intval(100 * ($l_site_imps / $s_impressions))) . '%)';

	$site_check = 'SiteRecords Check: ';
	if (!($num_impressions == $num_s_imps))
	{
		$site_check .= '[ERROR] Site Impression Mismatch following aggregation';
		$num_errors++;
	}
	else
	{
		$site_check .= 'OK';
	}
	$message['tail_aggregation'][] = $site_check;

	$optimize_start_time = microtime(true);

	$optimize_sites = "OPTIMIZE TABLE SiteRecords";
	$optimize_cities = "OPTIMIZE TABLE CityRecords";
	$optimize_ad_sizes = "OPTIMIZE TABLE report_ad_size_records";
	mysql_query($optimize_sites, $db_raw);
	mysql_query($optimize_cities, $db_raw);
	mysql_query($optimize_ad_sizes, $db_raw);

	$total_optimize_time = microtime(true) - $optimize_start_time;

	$final_timestamp = new DateTime("now");

	$subject = ($uh_oh) ? '[WARNING!] ' : '';
	$subject = 'Nightly TD Upload (' . date('m/d', strtotime('-1 day')) . ') - Completed ';


	$from = "TTD Daily <noreply@frequence.com>";
	$to = "Tech Logs <tech.logs@frequence.com>";

	$message['summary'] = array();
	$message['summary'][] = '<u>SUMMARY</u>';
	$message['summary'][] = 'Total Site Records: ' . number_format($s_rows);
	$message['summary'][] = 'Total City Records: ' . number_format($c_rows);
	$message['summary'][] = 'Total Ad Size Records: ' . number_format($a_s_rows);
	$message['summary'][] = 'Total New Impressions: ' . number_format($num_s_imps);
	$message['summary'][] = 'Total New Clicks: ' . number_format($num_s_clks);
	$message['summary'][] = 'Total Table Optimization Time: ' . gmdate('G\h\r\s i\m\i\n s\s', $total_optimize_time);
	$message['summary'][] = 'Total Process Time: ' . date_diff($start_timestamp, $final_timestamp)->format("%imin, %ss");

	$message['tradedesk_data_upload'] = implode("\n", $message['tradedesk_data_upload']);
	$message['s3_buckets'] = implode("\n", $message['s3_buckets']);
	$message['raw_data_loaded'] = implode("\n", $message['raw_data_loaded']);
	$message['raw_binned_data_loaded'] = implode("\n", $message['raw_binned_data_loaded']);
	$message['tail_aggregation'] = implode("\n", $message['tail_aggregation']);
	$message['summary'] = implode("\n", $message['summary']);

	$message = nl2br(implode("\n\n", $message));
	
	send_email($from, $to, $subject, $message, 'html');

	$av_clean_cities = "DELETE FROM CityRecords WHERE AdGroupID IN (SELECT ID FROM AdGroups WHERE Source = 'TDAV')";
	$av_clean_sites = "DELETE FROM SiteRecords WHERE AdGroupID IN (SELECT ID FROM AdGroups WHERE Source = 'TDAV')";
	$av_clean_ad_sizes = "DELETE FROM report_ad_size_records WHERE AdGroupID IN (SELECT ID FROM AdGroups WHERE Source = 'TDAV')";
	mysql_query($av_clean_cities, $db_raw);
	mysql_query($av_clean_sites, $db_raw);
	mysql_query($av_clean_ad_sizes, $db_raw);

	execute_all_impression_caching();

?>
