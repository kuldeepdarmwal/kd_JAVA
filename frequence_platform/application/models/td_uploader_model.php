<?php

class Td_uploader_model extends CI_Model
{
	public function __construct()
	{
		require_once('misc_external/uploader_constants.php');
	}

	const all_other_sites_placeholder = 'All other sites';
	const all_other_sites_small_impressions_placeholder = 'All other sites - small impressions';

	public function create_impressions_and_clicks_tables($new_imp_table, $new_clk_table, $new_bl_imp_table, $new_bl_clk_table)
	{
		$db_raw = $this->load->database(raw_db_groupname, true);

		switch($this->config->item("ttd_feed_ver"))
		{
			case "2":
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
						KEY `indx_vlid` (`VantageLocalId`),
						KEY `indx_creative_id` (`CreativeId`),
						KEY `indx_adgroup_id` (`AdGroupId`)
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
				$table_gen_bl_impressions =
				"	CREATE TABLE `{$new_bl_imp_table}` (
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
				$table_gen_bl_clicks =
				"	CREATE TABLE `{$new_bl_clk_table}` (
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
				break;
			case "4":
				$table_gen_impressions =
				"	CREATE TABLE `{$new_imp_table}` (
						`ImpressionId` varchar(36) COLLATE utf8_unicode_ci NOT NULL,
						`LogEntryTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
						`PartnerId` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`AdvertiserId` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`CampaignId` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`AdGroupId` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`PrivateContractId` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`AudienceId` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`CreativeId` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`AdFormat` varchar(16) NOT NULL,
						`Frequency` int(11) NOT NULL,
						`SupplyVendor` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`SupplyVendorPublisherId` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`DealId` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`Site` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
						`ReferrerCategoriesList` int(11) NOT NULL,
						`FoldPosition` int(11) NOT NULL,
						`UserHourOfWeek` int(11) NOT NULL,
						`UserAgent` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
						`IPAddress` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
						`TDID` varchar(36) COLLATE utf8_unicode_ci NOT NULL,
						`CountryLong` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`Region` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`Metro` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`City` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`DeviceType` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`OSFamily` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`OS` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`Browser` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`Recency` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`LanguageCode` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`MediaCost` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`FeeFeatureCost` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`DataUsageTotalCost` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`TTDCostInUSD` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`PartnerCostInUSD` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`AdvertiserCostInUSD` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`Latitude` float(9,6) DEFAULT NULL,
						`Longitude` float(9,6) DEFAULT NULL,
						`DeviceID` varchar(63) COLLATE utf8_unicode_ci NOT NULL,
						`ZipCode` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
						`geofence_gcd_id` int(10) NOT NULL,
						`geofence_place_id` int(10) NOT NULL,
						`VantageLocalId` bigint(10) NOT NULL,
						KEY `indx_time_vlid` (`LogEntryTime`, `VantageLocalId`),
						KEY `indx_vlid` (`VantageLocalId`),
						KEY `indx_creative_id` (`CreativeId`),
						KEY `indx_adgroup_id` (`AdGroupId`),
						KEY `geofence_gcd_id` (`geofence_gcd_id`),
						KEY `geofence_place_id` (`geofence_place_id`)
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

				$table_gen_bl_impressions =
				"	CREATE TABLE `{$new_bl_imp_table}` (
						`ImpressionId` varchar(36) COLLATE utf8_unicode_ci NOT NULL,
						`LogEntryTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
						`PartnerId` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`AdvertiserId` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`CampaignId` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`AdGroupId` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`PrivateContractId` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`AudienceId` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`CreativeId` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`AdFormat` varchar(16) NOT NULL,
						`Frequency` int(11) NOT NULL,
						`SupplyVendor` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`SupplyVendorPublisherId` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`DealId` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
						`Site` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
						`ReferrerCategoriesList` int(11) NOT NULL,
						`FoldPosition` int(11) NOT NULL,
						`UserHourOfWeek` int(11) NOT NULL,
						`UserAgent` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
						`IPAddress` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
						`TDID` varchar(36) COLLATE utf8_unicode_ci NOT NULL,
						`CountryLong` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`Region` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`Metro` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`City` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`DeviceType` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`OSFamily` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`OS` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`Browser` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`Recency` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`LanguageCode` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`MediaCost` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`FeeFeatureCost` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`DataUsageTotalCost` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`TTDCostInUSD` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`PartnerCostInUSD` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`AdvertiserCostInUSD` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
						`Latitude` float(9,6) DEFAULT NULL,
						`Longitude` float(9,6) DEFAULT NULL,
						`DeviceID` varchar(63) COLLATE utf8_unicode_ci NOT NULL,
						`ZipCode` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
						`geofence_gcd_id` int(10) NOT NULL,
						`geofence_place_id` int(10) NOT NULL,
						`VantageLocalId` bigint(10) NOT NULL,
						KEY `indx_time_vlid` (`LogEntryTime`, `VantageLocalId`),
						KEY `indx_vlid` (`VantageLocalId`),
						KEY `indx_creative_id` (`CreativeId`),
						KEY `indx_adgroup_id` (`AdGroupId`),
						KEY `geofence_gcd_id` (`geofence_gcd_id`),
						KEY `geofence_place_id` (`geofence_place_id`)
					) ENGINE = MyISAM DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;
				";

				$table_gen_bl_clicks =
				"	CREATE TABLE `{$new_bl_clk_table}` (
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
				break;
			default:
				echo "Invalid TTD feed version in config";
				exit();
		}
		if ((!$db_raw->table_exists($new_imp_table)) &&
			(!$db_raw->table_exists($new_clk_table)) &&
			(!$db_raw->table_exists($new_bl_imp_table)) &&
			(!$db_raw->table_exists($new_bl_clk_table)))
		{
			return ($db_raw->query($table_gen_impressions) && $db_raw->query($table_gen_clicks) && $db_raw->query($table_gen_bl_impressions) && $db_raw->query($table_gen_bl_clicks));
		}
		else
		{
			return false;
		}
	}

	public function check_if_table_exists($table_name, $group_name)
	{
		$db = $this->load->database($group_name, true);
		return ($db->table_exists($table_name));
	}

	public function optimize_table($table, $group_name)
	{
		$db = $this->load->database($group_name, true);
		$query = "OPTIMIZE TABLE {$table}";
		$db->query($query);
	}

	public function remove_records_with_incorrect_date($table_name, $group_name, $date_to_match, $date_column)
	{
		$db = $this->load->database($group_name, true);
		$bindings = array($date_to_match, date('Y-m-d', strtotime('+1 day', strtotime($date_to_match))));
		$query =
		"	DELETE FROM {$table_name}
			WHERE
				{$date_column} < ? OR
				{$date_column} >= ?;
		";
		$db->query($query, $bindings);
	}

	public function load_data_infile($file_path, $table, $data_type, $date)
	{
		$db_raw = $this->load->database(raw_db_groupname, true);
		$query =
		"	LOAD DATA LOCAL INFILE '{$file_path}'
			INTO TABLE {$table}
			FIELDS TERMINATED BY '\\t' ESCAPED BY '\\b'
			LINES TERMINATED BY '\\r\\n'
		";

		$sites_placeholder = sites_placeholder;

		switch($this->config->item('ttd_feed_ver'))
		{
			case "2":
				$impression_field_list =
				"LogEntryTime,
				@ImpressionId,
				WinningPriceCPMInDollars,
				SupplyVendor,
				AdvertiserId,
				CampaignId,
				AdGroupId,
				CreativeId,
				AdWidthInPixels,
				AdHeightInPixels,
				Frequency,
				@Site,
				TDID,
				ReferrerCategoriesList,
				FoldPosition,
				UserHourOfWeek,
				CountryLog,
				Region,
				Metro,
				City,
				IPAddress,
				VantageLocalId";

				$click_field_list =
				"LogEntryTime,
				ClickId,
				IPAddress,
				ReferrerUrl,
				RedirectUrl,
				CampaignId,
				ChannelId,
				AdvertiserId,
				@DisplayImpressionId,
				Keyword,
				KeywordId,
				MatchType,
				DistributionNetwork,
				TDID,
				RawUrl,
				VantageLocalId";

				break;
			case "3":
				echo "Invalid TTD feed version found during infile load (3)";
				exit();
				break;
			case "4":
				$impression_field_list =
				"@ImpressionId,
				LogEntryTime,
				PartnerId,
				AdvertiserId,
				CampaignId,
				AdGroupId,
				PrivateContractId,
				AudienceId,
				CreativeId,
				AdFormat,
				Frequency,
				SupplyVendor,
				SupplyVendorPublisherId,
				DealId,
				@Site,
				ReferrerCategoriesList,
				FoldPosition,
				UserHourOfWeek,
				UserAgent,
				IPAddress,
				TDID,
				CountryLong,
				Region,
				Metro,
				City,
				DeviceType,
				OSFamily,
				OS,
				Browser,
				Recency,
				LanguageCode,
				MediaCost,
				FeeFeatureCost,
				DataUsageTotalCost,
				TTDCostInUSD,
				PartnerCostInUSD,
				AdvertiserCostInUSD,
				Latitude,
				Longitude,
				DeviceID,
				ZipCode,
				@geofence_gcd_id,
				@geofence_place_id,
				VantageLocalId";

				$click_field_list =
				"LogEntryTime,
				ClickId,
				IPAddress,
				ReferrerUrl,
				RedirectUrl,
				CampaignId,
				ChannelId,
				AdvertiserId,
				@DisplayImpressionId,
				Keyword,
				KeywordId,
				MatchType,
				DistributionNetwork,
				TDID,
				RawUrl,
				VantageLocalId";

				break;
			default:
				echo "Invalid TTD feed version found during infile load";
				exit();
		}

		if ($data_type == 'impressions')
		{
			$blocklist_result = $this->get_uploader_blocklist();
			if($blocklist_result === false)
			{
				die("Failed to get site blocklists for raw data upload");
			}
			$blocklist_query_string = "";
			foreach($blocklist_result as $blocklist_row)
			{
				$blocklist_query_string .= "@Site LIKE '".$blocklist_row['bad_site']."' OR ";
			}

			$query .=
			"(	{$impression_field_list}	)
			SET
				ImpressionId = @ImpressionId,
				Site = IF(
					(
						".$blocklist_query_string."
						@Site LIKE '%@%' OR
						@Site NOT LIKE '%.%' OR
						@Site = '{techno.page_url}' OR
						@Site = 'javascript:window.contents' OR
						@Site REGEXP '/^(:)/' OR
						@Site NOT REGEXP '.[A-Za-z0-9\-]{1,}[\.][A-Za-z\-]{2,3}'
					),
					'{$sites_placeholder}',
					IF (
						@Site LIKE '%www.%',
						SUBSTRING(@Site, POSITION('www.' IN @Site) + 4),
						@Site
					)
				),
				VantageLocalId = CONV(CONCAT(SUBSTR(@ImpressionId, 1, 4), SUBSTR(@ImpressionId, 33, 4)), 16, 10)
			";
		}
		else if ($data_type == 'clicks')
		{
			$query .=
			"(	{$click_field_list} )
			SET
				DisplayImpressionId = @DisplayImpressionId,
				VantageLocalId = CONV(CONCAT(SUBSTR(@DisplayImpressionId, 1, 4), SUBSTR(@DisplayImpressionId, 33, 4)), 16, 10)
			";
		}
		$return_var = "";
		$system_result = system("mysql -u ".TD_DB_USERNAME." --password='".TD_DB_PASSWORD."' -h ".TD_DB_HOSTNAME." --local_infile=1 -e \"$query\" ".TD_DB_DATABASE, $return_var);
		if($return_var != 0 || $system_result === false)
		{
			return false;
		}
		return true;
	}

	public function get_num_records($table_name, $group_name)
	{
		$db = $this->load->database($group_name, true);
		return $db->count_all($table_name);
	}

	public function same_day_clean($today, $table)
	{
		$db_raw = $this->load->database(raw_db_groupname, true);
		$id_get_query = "SELECT DISTINCT AdGroupId FROM {$table}";
		$result = $db_raw->query($id_get_query);
		$result = $result->result_array();

		$adgroup_array = call_user_func_array('array_merge_recursive', $result);
		$adgroup_array = is_array($adgroup_array['AdGroupId']) ? $adgroup_array['AdGroupId'] : array($adgroup_array['AdGroupId']);

		if (count($adgroup_array) > 0)
		{
			$db_main = $this->load->database('main', true);
			$in_array = implode(',', array_fill(0, count($adgroup_array), '?'));
			$bindings = array_merge($adgroup_array, array($today));

			$sites_placeholder = self::all_other_sites_placeholder;
			$sites_placeholder_small_impressions = self::all_other_sites_small_impressions_placeholder;
			$delete_query =
			"	DELETE FROM SiteRecords
				WHERE
					AdGroupId IN ({$in_array}) AND
					(Site = '{$sites_placeholder}' OR Site = '{$sites_placeholder_small_impressions}') AND
					Date = ?
			";
			$db_main->query($delete_query, $bindings);
		}
	}

	//Inserts aggregate rows into CityRecords/SiteRecords/zcta_records.
	public function upload_impression_and_click_data($data_type, $aggregate_response, $date)
	{
		$db_main = $this->load->database('main', true);
		$num_records = 0;
		$query_size = 10000;

		echo "Each . represents {$query_size} rows inserted into {$data_type} table: ";

		if(count($aggregate_response) > 0)
		{
			if($data_type == 'sites')
			{
				$query_start =
				"	INSERT INTO SiteRecords
					(AdGroupID, Site, Date, Impressions, Clicks, Cost, Base_Site)
					VALUES
				";
				$query_end =
				"	ON DUPLICATE KEY UPDATE
						Impressions = VALUES(Impressions),
						Clicks = VALUES(Clicks),
						Cost = VALUES(Cost),
						Base_Site = VALUES(Base_Site);
				";
				$query_middle = array();
				$bindings = array();

				foreach($aggregate_response as $aggregate_row)
				{
					$cost = $aggregate_row['tot'] / $aggregate_row['imp'];

					$bindings[] = $aggregate_row['aid'];
					$bindings[] = $aggregate_row['ss'];
					$bindings[] = $aggregate_row['date'];
					$bindings[] = $aggregate_row['imp'];
					$bindings[] = $aggregate_row['clk'];
					$bindings[] = $cost;
					$bindings[] = $aggregate_row['ss'];

					$query_middle[] = '(?,?,?,?,?,?,?)';

					if (count($query_middle) == $query_size)
					{
						$query = $query_start . implode(',', $query_middle) . $query_end;
						if ($db_main->query($query, $bindings))
						{
							echo '.';
							$num_records += $query_size;
						}
						else
						{
							var_dump($db_main);
						}
						$query_middle = array();
						$bindings = array();
					}
				}
				if (count($query_middle) > 0)
				{
					$query = $query_start . implode(',', $query_middle) . $query_end;
					if ($db_main->query($query, $bindings))
					{
						echo '.';
						$num_records += count($query_middle);
					}
					else
					{
						var_dump($db_main);
					}
					$query_middle = array();
					$bindings = array();
				}
			}
			elseif($data_type == 'cities')
			{
				$query_start =
				"	INSERT INTO CityRecords
					(AdGroupID, City, Region, Date, Impressions, Clicks, Cost)
					VALUES
				";
				$query_end =
				"	ON DUPLICATE KEY UPDATE
						Impressions = VALUES(Impressions),
						Clicks = VALUES(Clicks),
						Cost = VALUES(Cost);
				";
				$query_middle = array();
				$bindings = array();
				foreach($aggregate_response as $aggregate_row)
				{
					$cost = $aggregate_row['Cost'] / $aggregate_row['Impressions'];

					$bindings[] = $aggregate_row['aid'];
					$bindings[] = $aggregate_row['cty'];
					$bindings[] = $aggregate_row['reg'];
					$bindings[] = $aggregate_row['date'];
					$bindings[] = $aggregate_row['Impressions'];
					$bindings[] = $aggregate_row['Clicks'];
					$bindings[] = $cost;
					$query_middle[] = '(?,?,?,?,?,?,?)';

					if (count($query_middle) == $query_size)
					{
						$query = $query_start . implode(',', $query_middle) . $query_end;
						if ($db_main->query($query, $bindings))
						{
							echo '.';
							$num_records += $query_size;
						}
						else
						{
							var_dump($db_main);
						}
						$query_middle = array();
						$bindings = array();
					}
				}
				if (count($query_middle) > 0)
				{
					$query = $query_start . implode(',', $query_middle) . $query_end;
					if ($db_main->query($query, $bindings))
					{
						echo '.';
						$num_records += count($query_middle);
					}
					else
					{
						var_dump($db_main);
					}
					$query_middle = array();
					$bindings = array();
				}
			}
			elseif($data_type == 'sizes')
			{
				$query_start =
				"	INSERT INTO report_ad_size_records
					(AdGroupID, Size, Date, Impressions, Clicks, Cost)
					VALUES
				";
				$query_end =
				"	ON DUPLICATE KEY UPDATE
						Impressions = VALUES(Impressions),
						Clicks = VALUES(Clicks),
						Cost = VALUES(Cost);
				";
				$query_middle = array();
				$bindings = array();

				foreach($aggregate_response as $aggregate_row)
				{
					$cost = $aggregate_row['tot'] / $aggregate_row['imp'];

					$bindings[] = $aggregate_row['aid'];
					$bindings[] = $aggregate_row['size'];
					$bindings[] = $aggregate_row['date'];
					$bindings[] = $aggregate_row['imp'];
					$bindings[] = $aggregate_row['clk'];
					$bindings[] = $cost;
					$query_middle[] = '(?,?,?,?,?,?)';
					if (count($query_middle) == $query_size)
					{
						$query = $query_start . implode(',', $query_middle) . $query_end;
						if ($db_main->query($query, $bindings))
						{
							echo '.';
							$num_records += $query_size;
						}
						else
						{
							var_dump($db_main);
						}
						$query_middle = array();
						$bindings = array();
					}
				}

				if (count($query_middle) > 0)
				{
					$query = $query_start . implode(',', $query_middle) . $query_end;
					if ($db_main->query($query, $bindings))
					{
						echo '.';
						$num_records += count($query_middle);
					}
					else
					{
						var_dump($db_main);
					}
					$query_middle = array();
					$bindings = array();
				}
			}
			elseif ($data_type == 'creatives')
			{
				$query_start =
				"	INSERT INTO report_creative_records
					(adgroup_id, creative_id, tp_creative_id, date, impressions, clicks, cost, tp_creative_source)
					VALUES
				";
				$query_end =
				"	ON DUPLICATE KEY UPDATE
						impressions = VALUES(impressions),
						clicks = VALUES(clicks),
						cost = VALUES(cost)
				";
				$query_middle = array();
				$bindings = array();

				foreach($aggregate_response as $aggregate_row)
				{
					$cost = $aggregate_row['cost'] / $aggregate_row['impressions'];

					$bindings[] = $aggregate_row['adgroup_id'];
					$bindings[] = $aggregate_row['frq_creative_id'];
					$bindings[] = $aggregate_row['ttd_creative_id'];
					$bindings[] = $aggregate_row['date'];
					$bindings[] = $aggregate_row['impressions'];
					$bindings[] = $aggregate_row['clicks'];
					$bindings[] = $cost;
					$bindings[] = 0;

					$query_middle[] = '(?,?,?,?,?,?,?,?)';

					if (count($query_middle) == $query_size)
					{
						$query = $query_start . implode(',', $query_middle) . $query_end;
						if ($db_main->query($query, $bindings))
						{
							echo '.';
							$num_records += $query_size;
						}
						else
						{
							var_dump($db_main);
						}
						$query_middle = array();
						$bindings = array();
					}
				}
				if (count($query_middle) > 0)
				{
					$query = $query_start . implode(',', $query_middle) . $query_end;
					if ($db_main->query($query, $bindings))
					{
						echo '.';
						$num_records += count($query_middle);
					}
					else
					{
						var_dump($db_main);
					}
					$query_middle = array();
					$bindings = array();
				}
			}
			else if ($data_type == 'zctas')
			{
				$query_start =
				"	INSERT INTO zcta_records
					(ad_group_id, gcd_id, date, impressions, clicks, cost)
					VALUES
				";
				$query_end =
				"	ON DUPLICATE KEY UPDATE
						impressions = VALUES(impressions),
						clicks = VALUES(clicks),
						cost = VALUES(cost);
				";
				$query_middle = array();
				$bindings = array();
				foreach($aggregate_response as $aggregate_row)
				{
					$cost = $aggregate_row['cost'] / $aggregate_row['impressions'];

					$bindings[] = $aggregate_row['aid'];
					$bindings[] = $aggregate_row['gcd_id'];
					$bindings[] = $aggregate_row['date'];
					$bindings[] = $aggregate_row['impressions'];
					$bindings[] = $aggregate_row['clicks'];
					$bindings[] = $cost;
					$query_middle[] = '(?,?,?,?,?,?)';

					if (count($query_middle) == $query_size)
					{
						$query = $query_start . implode(',', $query_middle) . $query_end;
						if ($db_main->query($query, $bindings))
						{
							echo '.';
							$num_records += $query_size;
						}
						else
						{
							var_dump($db_main);
						}
						$query_middle = array();
						$bindings = array();
					}
				}
				if (count($query_middle) > 0)
				{
					$query = $query_start . implode(',', $query_middle) . $query_end;
					if ($db_main->query($query, $bindings))
					{
						echo '.';
						$num_records += count($query_middle);
					}
					else
					{
						var_dump($db_main);
					}
					$query_middle = array();
					$bindings = array();
				}
			}
			else
			{
				die("Unknown data_type: {$data_type}\n");
			}
		}
		echo "\r\033[K";
		return $num_records;
	}

	//Scoops up tail aggregate rows in the SiteRecords table
	//Bunches up rows with Less than 10 impressions and no clicks
	//and merges them with the "other" row for that AdGroupID/Date
	public function collate_loose_impressions($date, $data_type)
	{
		$db_main = $this->load->database(main_db_groupname, true);
		if ($data_type == 'sites')
		{
			$sites_placeholder = self::all_other_sites_placeholder;
			$sites_placeholder_small_impressions = self::all_other_sites_small_impressions_placeholder;

			$small_sites_expanded_query =
			"	INSERT INTO report_all_other_sites_small_impressions
				(AdGroupID, Site, Date, Impressions, Clicks, Cost, Base_Site)
				(	SELECT
						sr.AdGroupID,
						sr.Site,
						sr.Date,
						sr.Impressions,
						sr.Clicks,
						sr.Cost,
						sr.Base_Site
					FROM
						SiteRecords AS sr
					WHERE
						sr.Date = ? AND
						sr.Impressions < 5 AND
						sr.Clicks = 0 AND
						sr.Site != '{$sites_placeholder}' AND
						sr.Site != '{$sites_placeholder_small_impressions}'
				)
				ON DUPLICATE KEY UPDATE
					Impressions = VALUES(Impressions),
					Clicks = VALUES(Clicks),
					Cost = VALUES(Cost),
					Base_Site = VALUES(Base_Site);
			";

			$small_sites_query =
			"	INSERT INTO SiteRecords
				(AdGroupID, Site, Date, Impressions, Clicks, Cost, Base_Site)
				(	SELECT
						raos.AdGroupID,
						'{$sites_placeholder_small_impressions}' AS placeholder,
						raos.Date,
						SUM(raos.Impressions),
						SUM(raos.Clicks),
						raos.Cost,
						'{$sites_placeholder_small_impressions}' AS Imp
					FROM
						report_all_other_sites_small_impressions AS raos
					WHERE
						raos.Date = ?
					GROUP BY
						raos.AdGroupId,
						raos.Date
				)
				ON DUPLICATE KEY UPDATE
					Impressions = VALUES(Impressions),
					Clicks = VALUES(Clicks),
					Cost = VALUES(Cost),
					Base_Site = VALUES(Base_Site)
			";

			$clear_query =
			"	DELETE FROM SiteRecords
				WHERE
					Date = ? AND
					Impressions < 5 AND
					Clicks = 0 AND
					Site != '{$sites_placeholder}' AND
					Site != '{$sites_placeholder_small_impressions}'
			";
		}
		else if ($data_type == 'cities')
		{
			return; // TODO Finish city aggregation
		}

		$db_main->trans_begin();

		$small_expanded_result = $db_main->query($small_sites_expanded_query, $date);
		$small_result = $db_main->query($small_sites_query, $date);
		$clear_result = $db_main->query($clear_query, $date);

		if($db_main->trans_status() === false && !($small_result && $small_expanded_result && $clear_result))
		{
			$db_main->trans_rollback();
			return false;
		}
		else
		{
			$db_main->trans_commit();
			return true;
		}
	}

	//Aggregation function that generates the data which will
	//wind up in the Site/CityRecords tables
	public function aggregate_impression_and_click_data($new_imp_table, $new_clk_table, $data_type)
	{
		$db_raw = $this->load->database(raw_db_groupname, true);
		if($this->config->item('ttd_feed_ver') == "2")
		{
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
							SUM(a.WinningPriceCPMInDollars) / 1000 as Cost
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
							sum(a.WinningPriceCPMInDollars) / 1000 as Cost
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
							SUM(a.WinningPriceCPMInDollars) / 1000 as Cost
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
							sum(a.WinningPriceCPMInDollars) / 1000 as Cost
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
			elseif($data_type == 'sizes')
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
							SUM(a.WinningPriceCPMInDollars) / 1000 as Cost
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
							sum(a.WinningPriceCPMInDollars) / 1000 as Cost
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
			elseif ($data_type == 'creatives')
			{
				$query =
				"	SELECT
						creative_imprs.adgroup_id AS adgroup_id,
						creative_imprs.ttd_creative_id AS ttd_creative_id,
						frq_creative_ids.creative_id AS frq_creative_id,
						creative_imprs.date AS date,
						creative_imprs.impressions AS impressions,
						COALESCE(creative_clks.clicks, 0) AS clicks,
						creative_imprs.cost AS cost
					FROM
						(SELECT
							a.AdGroupId AS adgroup_id,
							a.CreativeId AS ttd_creative_id,
							DATE(a.LogEntryTime) AS date,
							COUNT(a.ImpressionId) AS impressions,
							SUM(0) AS clicks,
							SUM(a.WinningPriceCPMInDollars)/1000 AS cost
						FROM
							{$new_imp_table} AS a
						GROUP BY
							a.AdGroupId,
							a.CreativeId,
							DATE(a.LogEntryTime)
						) AS creative_imprs
						LEFT JOIN
						(SELECT
							a.AdGroupId as adgroup_id,
							a.CreativeId AS ttd_creative_id,
							DATE(a.LogEntryTime) as date,
							SUM(0) as impressions,
							count(DISTINCT b.ClickId) as clicks,
							SUM(a.WinningPriceCPMInDollars) / 1000 as cost
						FROM
							{$new_clk_table} AS b
						INNER JOIN {$new_imp_table} AS a
							ON a.VantageLocalId = b.VantageLocalId
						GROUP BY
							a.AdGroupId,
							a.CreativeId,
							DATE(a.LogEntryTime)
						) AS creative_clks
							ON (creative_imprs.adgroup_id = creative_clks.adgroup_id AND
								creative_imprs.ttd_creative_id = creative_clks.ttd_creative_id AND
								creative_imprs.date = creative_clks.date)
						LEFT JOIN (
							SELECT
								frq_creatives.id AS creative_id,
								frq_creatives.ttd_creative_id AS ttd_creative_id,
								frq_adgroups_cre.ID AS adgroup_id
							FROM
								{$this->db->database}.cup_creatives AS frq_creatives
								JOIN {$this->db->database}.cup_versions AS frq_versions
									ON (frq_creatives.version_id = frq_versions.id)
								JOIN {$this->db->database}.AdGroups AS frq_adgroups_cre
									ON (frq_versions.campaign_id = frq_adgroups_cre.campaign_id)
							WHERE
								frq_creatives.ttd_creative_id IS NOT NULL
							) frq_creative_ids
							 ON (creative_imprs.ttd_creative_id = frq_creative_ids.ttd_creative_id AND creative_imprs.adgroup_id = frq_creative_ids.adgroup_id)
					GROUP BY
						creative_imprs.adgroup_id,
						creative_imprs.ttd_creative_id,
						creative_imprs.date";
			}
			else
			{
				die("Unknown data_type: {$data_type}\n");
			}
		}
		else
		{
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
							SUM(a.MediaCost) as Cost
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
							sum(a.MediaCost) as Cost
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
				$db_main = $this->load->database('main', true);
				$db_main_name = $db_main->database;
				$cities_placeholder = cities_placeholder;
				$regions_placeholder = regions_placeholder;

				$query =
				"	SELECT
						ci.AdGroupId as aid,
						ci.CityName as cty,
						ci.RegionName as reg,
						ci.Date as date,
						ci.Impressions as Impressions,
						COALESCE(cc.Clicks, 0) as Clicks,
						ci.Cost as Cost
					FROM
					(	SELECT
							a.AdGroupId as AdGroupID,
							(	CASE a.geofence_place_id
									WHEN -1 THEN '{$cities_placeholder}'
									WHEN 0 THEN a.City
									ELSE gllcs.city_name
								END
							) AS CityName,
							(	CASE a.geofence_place_id
									WHEN -1 THEN '{$regions_placeholder}'
									WHEN 0 THEN a.Region
									ELSE gsm.NAME10
								END
							) AS RegionName,
							DATE(a.LogEntryTime) as Date,
							count(a.ImpressionId) as Impressions,
							SUM(0) as Clicks,
							SUM(a.MediaCost) as Cost
						FROM {$new_imp_table} a
						LEFT JOIN geo_lat_lngs_to_city_state gllcs
						ON gllcs.geo_id = a.geofence_place_id
						LEFT JOIN {$db_main_name}.geo_state_map gsm
						ON gsm.num_id = gllcs.state_id
						GROUP BY
							a.AdGroupId,
							CityName,
							RegionName,
							DATE(a.LogEntryTime)
					) as ci
					LEFT JOIN
					(	SELECT
							a.AdGroupId as AdGroupID,
							(	CASE a.geofence_place_id
									WHEN -1 THEN '{$cities_placeholder}'
									WHEN 0 THEN a.City
									ELSE gllcs.city_name
								END
							) AS CityName,
							(	CASE a.geofence_place_id
									WHEN -1 THEN '{$regions_placeholder}'
									WHEN 0 THEN a.Region
									ELSE gsm.NAME10
								END
							) AS RegionName,
							DATE(a.LogEntryTime) as Date,
							SUM(0) as Impressions,
							COUNT(DISTINCT b.ClickId) as Clicks,
							SUM(a.MediaCost) as Cost
						FROM {$new_clk_table} b
						INNER JOIN {$new_imp_table} a
						ON a.VantageLocalId = b.VantageLocalId
						LEFT JOIN geo_lat_lngs_to_city_state gllcs
						ON gllcs.geo_id = a.geofence_place_id
						LEFT JOIN {$db_main_name}.geo_state_map gsm
						ON gsm.num_id = gllcs.state_id
						GROUP BY
							a.AdGroupId,
							CityName,
							RegionName,
							DATE(a.LogEntryTime)
					) as cc
					ON
						ci.AdGroupId = cc.AdgroupId AND
						ci.CityName = cc.CityName AND
						ci.RegionName = cc.RegionName AND
						ci.Date = cc.Date
					WHERE 1
					ORDER BY
						ci.Impressions DESC,
						ci.CityName ASC,
						ci.RegionName ASC,
						ci.AdGroupId DESC
				";
			}
			elseif($data_type == 'sizes')
			{
				$query =
				"	SELECT
						ci.AdGroupId AS aid,
						ci.AdFormat AS size,
						ci.Date AS date,
						ci.Impressions AS imp,
						COALESCE(cc.Clicks, 0) AS clk,
						ci.Cost AS tot
					FROM
					(	SELECT
							a.AdGroupId as AdGroupID,
							a.AdFormat AS AdFormat,
							DATE(a.LogEntryTime) as Date,
							count(a.ImpressionId) as Impressions,
							SUM(0) as Clicks,
							SUM(a.MediaCost) as Cost
						FROM {$new_imp_table} a
						GROUP BY
							a.AdGroupId,
							a.AdFormat,
							DATE(a.LogEntryTime)
					) as ci
					LEFT JOIN
					(	SELECT
							a.AdGroupId as AdGroupID,
							a.AdFormat AS AdFormat,
							DATE(a.LogEntryTime) as Date,
							SUM(0) as Impressions,
							count(DISTINCT b.ClickId) as Clicks,
							sum(a.MediaCost) as Cost
						FROM {$new_clk_table} b
						INNER JOIN {$new_imp_table} a
						ON a.VantageLocalId = b.VantageLocalId
						GROUP BY
							a.AdGroupId,
							a.AdFormat,
							DATE(a.LogEntryTime)
					) as cc
					ON
						ci.AdGroupId = cc.AdgroupId AND
						ci.AdFormat = cc.Adformat AND
						ci.Date = cc.Date
					WHERE 1
					ORDER BY
						ci.AdGroupId DESC,
						ci.AdFormat ASC,
						ci.Impressions DESC
				";
			}
			elseif ($data_type == 'creatives')
			{
				$query =
				"	SELECT
						creative_imprs.adgroup_id AS adgroup_id,
						creative_imprs.ttd_creative_id AS ttd_creative_id,
						frq_creative_ids.creative_id AS frq_creative_id,
						creative_imprs.date AS date,
						creative_imprs.impressions AS impressions,
						COALESCE(creative_clks.clicks, 0) AS clicks,
						creative_imprs.cost AS cost
					FROM
						(SELECT
							a.AdGroupId AS adgroup_id,
							a.CreativeId AS ttd_creative_id,
							DATE(a.LogEntryTime) AS date,
							COUNT(a.ImpressionId) AS impressions,
							SUM(0) AS clicks,
							SUM(a.MediaCost) AS cost
						FROM
							{$new_imp_table} AS a
						GROUP BY
							a.AdGroupId,
							a.CreativeId,
							DATE(a.LogEntryTime)
						) AS creative_imprs
						LEFT JOIN
						(SELECT
							a.AdGroupId as adgroup_id,
							a.CreativeId AS ttd_creative_id,
							DATE(a.LogEntryTime) as date,
							SUM(0) as impressions,
							count(DISTINCT b.ClickId) as clicks,
							SUM(a.MediaCost) as cost
						FROM
							{$new_clk_table} AS b
						INNER JOIN {$new_imp_table} AS a
							ON a.VantageLocalId = b.VantageLocalId
						GROUP BY
							a.AdGroupId,
							a.CreativeId,
							DATE(a.LogEntryTime)
						) AS creative_clks
							ON (creative_imprs.adgroup_id = creative_clks.adgroup_id AND
								creative_imprs.ttd_creative_id = creative_clks.ttd_creative_id AND
								creative_imprs.date = creative_clks.date)
						LEFT JOIN (
							SELECT
								frq_creatives.id AS creative_id,
								frq_creatives.ttd_creative_id AS ttd_creative_id,
								frq_adgroups_cre.ID AS adgroup_id
							FROM
								{$this->db->database}.cup_creatives AS frq_creatives
								JOIN {$this->db->database}.cup_versions AS frq_versions
									ON (frq_creatives.version_id = frq_versions.id)
								JOIN {$this->db->database}.AdGroups AS frq_adgroups_cre
									ON (frq_versions.campaign_id = frq_adgroups_cre.campaign_id)
							WHERE
								frq_creatives.ttd_creative_id IS NOT NULL
							) frq_creative_ids
							 ON (creative_imprs.ttd_creative_id = frq_creative_ids.ttd_creative_id AND creative_imprs.adgroup_id = frq_creative_ids.adgroup_id)
					GROUP BY
						creative_imprs.adgroup_id,
						creative_imprs.ttd_creative_id,
						creative_imprs.date";
			}
			else if($data_type == 'zctas')
			{
				$db_main = $this->load->database('main', true);
				$db_main_name = $db_main->database;
				$query =
				"	SELECT
						zi.ad_group_id as aid,
						zi.gcd_id as gcd_id,
						zi.date as date,
						zi.impressions as impressions,
						COALESCE(zc.clicks, 0) as clicks,
						zi.cost as cost
					FROM
					(	SELECT
							a.AdGroupId as ad_group_id,
							(	CASE a.geofence_gcd_id
									WHEN -1 THEN 0
									WHEN 0 THEN COALESCE(gzm.gcd_id, gzm2.gcd_id, gfm.gcd_id, 0)
									ELSE a.geofence_gcd_id
								END
							) AS gcd_id,
							DATE(a.LogEntryTime) AS date,
							COUNT(a.ImpressionId) AS impressions,
							SUM(0) as clicks,
							SUM(a.MediaCost) AS cost
						FROM {$new_imp_table} a
						LEFT JOIN
							{$db_main_name}.geo_zipcode_to_zcta gzz
							ON
								gzz.zip_int = a.ZipCode
						LEFT JOIN
							{$db_main_name}.geo_zcta_map gzm
							ON
								gzm.num_id = a.ZipCode
						LEFT JOIN
							{$db_main_name}.geo_zcta_map gzm2
							ON
								gzm2.num_id = gzz.zcta_int
						LEFT JOIN
							{$db_main_name}.geo_fsa_map gfm
							ON (
								gfm.char_1 = ORD(SUBSTRING(a.ZipCode, 1, 1)) AND
								gfm.digit_2 = SUBSTRING(a.ZipCode, 2, 1) AND
								gfm.char_3 = ORD(SUBSTRING(a.ZipCode, 3, 1))
							)
						GROUP BY
							a.AdGroupId,
							gcd_id,
							DATE(a.LogEntryTime)
					) as zi
					LEFT JOIN
					(	SELECT
							a.AdGroupId as ad_group_id,
							(	CASE a.geofence_gcd_id
									WHEN -1 THEN 0
									WHEN 0 THEN COALESCE(gzm.gcd_id, gzm2.gcd_id, gfm.gcd_id, 0)
									ELSE a.geofence_gcd_id
								END
							) AS gcd_id,
							DATE(a.LogEntryTime) as date,
							SUM(0) as impressions,
							count(DISTINCT b.ClickId) as clicks,
							sum(a.MediaCost) as cost
						FROM {$new_clk_table} b
						INNER JOIN {$new_imp_table} a
						ON a.VantageLocalId = b.VantageLocalId
						LEFT JOIN
							{$db_main_name}.geo_zipcode_to_zcta gzz
							ON
								gzz.zip_int = a.ZipCode
						LEFT JOIN
							{$db_main_name}.geo_zcta_map gzm
							ON
								gzm.num_id = a.ZipCode
						LEFT JOIN
							{$db_main_name}.geo_zcta_map gzm2
							ON
								gzm2.num_id = gzz.zcta_int
						LEFT JOIN
							{$db_main_name}.geo_fsa_map gfm
							ON (
								gfm.char_1 = ORD(SUBSTRING(a.ZipCode, 1, 1)) AND
								gfm.digit_2 = SUBSTRING(a.ZipCode, 2, 1) AND
								gfm.char_3 = ORD(SUBSTRING(a.ZipCode, 3, 1))
							)
						GROUP BY
							a.AdGroupId,
							gcd_id,
							DATE(a.LogEntryTime)
					) as zc
					ON
						zi.ad_group_id = zc.ad_group_id AND
						zi.gcd_id = zc.gcd_id AND
						zi.date = zc.date
					WHERE 1
					ORDER BY
						zi.impressions DESC,
						zi.gcd_id ASC,
						zi.ad_group_id DESC
				";
			}
			else
			{
				die("Unknown data_type: {$data_type}\n");
			}
		}
		$response = $db_raw->query($query);
		return $response->result_array();
	}

	public function geofencing_assign_geos_to_points_if_they_exist($imp_table)
	{
		$db_raw = $this->load->database(raw_db_groupname, true);
		$db_main = $this->load->database(main_db_groupname, true);
		$db_main_name = $db_main->database;

		$return_array = [
			'affected_rows' => 0,
			'tdgf_adgroups' => []
		];

		$get_geofencing_adgrous_sql =
		"	SELECT ag.ID AS id
			FROM AdGroups AS ag
			JOIN geofence_adgroup_centers AS gcd
				ON gcd.adgroup_vl_id = ag.vl_id
			WHERE ag.Source = 'TDGF';
		";
		$adgroup_result = $db_main->query($get_geofencing_adgrous_sql);
		if($adgroup_result->num_rows() > 0)
		{
			$adgroup_ids = array_column($adgroup_result->result_array(), 'id');
			$return_array['tdgf_adgroups'] = array_unique($adgroup_ids);
			$adgroup_ids_insert_string = implode(',', array_fill(0, count($adgroup_ids), '?'));
			$sql =
			"	UPDATE {$imp_table} AS raw
				SET
					raw.geofence_gcd_id = IFNULL(
						(	SELECT gp.id
							FROM {$db_main_name}.geo_polygons AS gp
							LEFT JOIN {$db_main_name}.geo_zcta_map AS gzm
								ON gzm.gcd_id = gp.id
							WHERE
								gzm.num_id = raw.ZipCode AND
								ST_Contains(gp.polygon_precision_max, POINT(raw.Longitude, raw.Latitude))
							LIMIT 1

							UNION ALL

							SELECT gp.id
							FROM {$db_main_name}.geo_polygons AS gp
							LEFT JOIN {$db_main_name}.geo_fsa_map AS gfm
								ON gfm.gcd_id = gp.id
							WHERE
								gfm.numeric_fsa = CONCAT(ORD(SUBSTRING(raw.ZipCode, 1, 1)), SUBSTRING(raw.ZipCode, 2, 1), ORD(SUBSTRING(raw.ZipCode, 3, 1))) AND
								ST_Contains(gp.polygon_precision_max, POINT(raw.Longitude, raw.Latitude))
							LIMIT 1
						),
					-1),
					raw.geofence_place_id = IFNULL(
						(	SELECT gllcs.geo_id AS place_id
							FROM geo_lat_lngs_to_city_state AS gllcs
							JOIN {$db_main_name}.geo_zcta_to_place AS gztp
								ON gztp.geoid_int = gllcs.geo_id
							WHERE
								gztp.zcta_int = raw.ZipCode AND
								LOWER(gllcs.city_name) = LOWER(raw.City) AND
								ST_Contains(gllcs.polygon, POINT(raw.Longitude, raw.Latitude))
							LIMIT 1
						),
					-1)
				WHERE raw.AdGroupId IN ({$adgroup_ids_insert_string});
			";
			$result = $db_raw->query($sql, $adgroup_ids);
			if($result)
			{
				$return_array['affected_rows'] = $db_raw->affected_rows();
			}
		}
		return $return_array;
	}

	public function geofencing_aggregation_for_date($process_date, $imp_table, $clk_table, $tdgf_adgroups)
	{
		$db_raw = $this->load->database(raw_db_groupname, true);
		$db_main = $this->load->database(main_db_groupname, true);
		$db_main_name = $db_main->database;

		$adgroup_ids_insert_string = implode(',', array_fill(0, count($tdgf_adgroups), '?'));
		$bindings = array_merge($tdgf_adgroups, $tdgf_adgroups);

		$this->db->trans_begin();

		$clear_sql =
		"	DELETE FROM {$db_main_name}.geofence_saved_points
			WHERE DATE(date_time) = ?
		";
		$delete_success = $this->db->query($clear_sql, $process_date);

		$sql =
		"	INSERT INTO {$db_main_name}.geofence_saved_points
			(`geofence_adgroup_centers_id`, `date_time`, `location_point`, `os`, `was_clicked`)
			SELECT
				(
					SELECT
						gac.id
					FROM
						{$db_main_name}.geofence_adgroup_centers AS gac
					WHERE
						adgroup_vl_id = ag.vl_id
					ORDER BY
						(((acos(sin((td_i.latitude * pi() / 180)) * sin((Y(gac.center_point) * pi() / 180)) + cos((td_i.latitude * pi() / 180)) * cos((Y(gac.center_point) * pi() / 180)) * cos(((td_i.longitude - X(gac.center_point)) * pi() / 180)))) * 180 / pi()) * 60 * 1.1515) ASC
					LIMIT 1
				) AS geofence_center_id,
				CONCAT(DATE(td_i.LogEntryTime), ' 00:00:00') AS imp_datetime,
				POINT(TRUNCATE(td_i.Longitude, 6), TRUNCATE(td_i.Latitude, 6)) AS p,
				td_i.OSFamily AS os,
				IF(COUNT(td_c.ClickId) > 0, 1, 0) AS was_clicked
			FROM
				{$imp_table} AS td_i
			JOIN
				{$db_main_name}.AdGroups AS ag
				ON
					ag.ID = td_i.AdGroupId
			LEFT JOIN
				{$clk_table} AS td_c
				ON
					td_c.VantageLocalId = td_i.VantageLocalId
			WHERE
				td_i.AdGroupId IN ({$adgroup_ids_insert_string}) AND
				ag.ID IN ({$adgroup_ids_insert_string}) AND
				td_i.Latitude IS NOT NULL AND
				td_i.Longitude IS NOT NULL AND
				POINT(td_i.Longitude, td_i.Latitude) != POINT(0, 0)
			GROUP BY
				geofence_center_id, imp_datetime, p
			HAVING
				geofence_center_id IS NOT NULL;
		";
		$saved_points_result = $db_raw->query($sql, $bindings);
		if($saved_points_result)
		{
			$bindings = array_merge([$process_date], $tdgf_adgroups, $tdgf_adgroups);
			$sql =
			"	INSERT INTO {$db_main_name}.geofence_daily_totals
				(date, `geofence_adgroup_centers_id`, `impressions_total_android`, `impressions_total_ios`, `impressions_total_other`, `clicks_total_android`, `clicks_total_ios`, `clicks_total_other`)
				SELECT
					DATE(td_i.LogEntryTime) AS point_date,
					(
						SELECT
							gac.id
						FROM
							{$db_main_name}.geofence_adgroup_centers AS gac
						WHERE
							adgroup_vl_id = ag.vl_id
						ORDER BY
							(((acos(sin((td_i.latitude * pi() / 180)) * sin((Y(gac.center_point) * pi() / 180)) + cos((td_i.latitude * pi() / 180)) * cos((Y(gac.center_point) * pi() / 180)) * cos(((td_i.longitude - X(gac.center_point)) * pi() / 180)))) * 180 / pi()) * 60 * 1.1515) ASC
						LIMIT 1
					) AS point_id,
					SUM(IF(td_i.OSFamily = 'Android', 1, 0)) AS android_imp_total,
					SUM(IF(td_i.OSFamily = 'iOS', 1, 0)) AS ios_imp_total,
					SUM(IF(td_i.OSFamily != 'iOS' && td_i.OSFamily != 'Android', 1, 0)) AS other_imp_total,
					SUM(IF(td_i.OSFamily = 'Android' AND td_c.VantageLocalId IS NOT NULL, 1, 0)) AS android_clk_total,
					SUM(IF(td_i.OSFamily = 'iOS' AND td_c.VantageLocalId IS NOT NULL, 1, 0)) AS ios_clk_total,
					SUM(IF(td_i.OSFamily != 'iOS' && td_i.OSFamily != 'Android' AND td_c.VantageLocalId IS NOT NULL, 1, 0)) AS other_clk_total
				FROM
					{$imp_table} AS td_i
				LEFT JOIN
					{$clk_table} AS td_c
					ON
						td_c.VantageLocalId = td_i.VantageLocalId
				JOIN
					{$db_main_name}.AdGroups AS ag
					ON
						ag.ID = td_i.AdGroupId
				WHERE
					DATE(td_i.LogEntryTime) = ? AND
					td_i.AdGroupId IN ({$adgroup_ids_insert_string}) AND
					ag.ID IN ({$adgroup_ids_insert_string})
				GROUP BY
					point_date,
					point_id
				ON DUPLICATE KEY UPDATE
					impressions_total_android = VALUES(impressions_total_android),
					impressions_total_ios = VALUES(impressions_total_ios),
					impressions_total_other = VALUES(impressions_total_other),
					clicks_total_android = VALUES(clicks_total_android),
					clicks_total_ios = VALUES(clicks_total_ios),
					clicks_total_other = VALUES(clicks_total_other)
			";
			$daily_totals_result = $db_raw->query($sql, $bindings);
		}

		if ($this->db->trans_status() === false || boolval($saved_points_result && $daily_totals_result) === false)
		{
			$this->db->trans_rollback();
			$result = "Failed geofence impression/click aggregation for {$process_date}";
		}
		else
		{
			$this->db->trans_commit();
			$result = 'Success!';
		}

		return $result;
	}

	public function get_num_clicks_and_impressions_by_date($date)
	{
		$db_main = $this->load->database('main', true);
		$data = array();

		$sites_placeholder = self::all_other_sites_placeholder;
		$sites_placeholder_small_impressions = self::all_other_sites_small_impressions_placeholder;
		$site_query =
		"	SELECT
				SUM(Impressions) AS num_imps,
				SUM(Clicks) AS num_clicks,
				COUNT(*) AS num_rows
			FROM
				SiteRecords
			WHERE
				Date = ?;
		";

		$city_query =
		"	SELECT
				SUM(Impressions) AS num_imps,
				SUM(Clicks) AS num_clicks,
				COUNT(*) AS num_rows
			FROM
				CityRecords
			WHERE
				Date = ?;
		";

		$size_query =
		"	SELECT
				SUM(Impressions) AS num_imps,
				SUM(Clicks) AS num_clicks,
				COUNT(*) AS num_rows
			FROM
				report_ad_size_records
			WHERE
				Date = ?;
		";

		$creative_query =
		"	SELECT
				SUM(impressions) AS num_imps,
				SUM(clicks) AS num_clicks,
				COUNT(*) AS num_rows
			FROM
				report_creative_records
			WHERE
				date = ?;
		";

		$creative_nulls_query =
		"	SELECT
				COUNT(DISTINCT tp_creative_id) AS unknown_creatives
			FROM
				report_creative_records
			WHERE
				creative_id IS NULL
				AND date = ?
				AND tp_creative_source = 0
		";

		$loose_site_query =
		"	SELECT
				SUM(Impressions) AS num_imps,
				COUNT(*) AS num_rows
			FROM
				SiteRecords
			WHERE
				(Date = ?) AND
				(Impressions < 5) AND
				(Clicks = 0) AND
				(Site != '{$sites_placeholder}') AND
				(Site != '{$sites_placeholder_small_impressions}');
		";

		$zip_query =
		"	SELECT
				SUM(impressions) AS num_imps,
				SUM(clicks) AS num_clicks,
				COUNT(*) AS num_rows
			FROM
				zcta_records
			WHERE
				Date = ?;
		";

		$sites = $db_main->query($site_query, $date) OR die("Failure to get SiteRecords data\n");
		$loose_sites = $db_main->query($loose_site_query, $date) OR die("Failure to get loose SiteRecords data\n");
		$cities = $db_main->query($city_query, $date) OR die("Failure to get CityRecords data\n");
		$sizes = $db_main->query($size_query, $date) OR die("Failure to get AdSize data\n");
		$creatives = $db_main->query($creative_query, $date) OR die("Failure to get Creative data\n");
		$creative_nulls = $db_main->query($creative_nulls_query, $date) OR die("Failure to get Unidentified Creatives data\n");
		$zctas = $db_main->query($zip_query, $date) OR die("Failure to get zcta_records data\n");

		$data['sites'] = $sites->row_array();
		$data['loose_sites'] = $loose_sites->row_array();
		$data['cities'] = $cities->row_array();
		$data['sizes'] = $sizes->row_array();
		$data['creatives'] = array_merge($creatives->row_array(), $creative_nulls->row_array());
		$data['zctas'] = $zctas->row_array();

		return $data;
	}

	public function delete_adverify_records($date)
	{
		$db_main = $this->load->database('main', true);

		$db_main->query("DELETE FROM CityRecords WHERE AdGroupID IN (SELECT ID FROM AdGroups WHERE Source = 'TDAV') AND Date = ?", $date);
		$db_main->query("DELETE FROM SiteRecords WHERE AdGroupID IN (SELECT ID FROM AdGroups WHERE Source = 'TDAV') AND Date = ?", $date);
		$db_main->query("DELETE FROM report_ad_size_records WHERE AdGroupID IN (SELECT ID FROM AdGroups WHERE Source = 'TDAV') AND Date = ?", $date);
		$db_main->query("DELETE FROM report_creative_records WHERE adgroup_id IN (SELECT ID FROM AdGroups WHERE Source = 'TDAV') AND date = ?", $date);
		$db_main->query("DELETE FROM zcta_records WHERE ad_group_id IN (SELECT ID FROM AdGroups WHERE Source = 'TDAV') AND date = ?", $date);
	}
	/* REMOVED 29 Jun 2015. Function to take Google sites belonging
	* to pre-roll adgroups and make them all have site "Google Ad Network"
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
						   ".$this->db->database.".AdGroups adg
					   WHERE
						   adg.target_type LIKE \"%Pre-Roll%\"
					   )";
	   $db_raw = $this->load->database(raw_db_groupname, true);
	   $response = $db_raw->query($google_unify_query);
	   if(!$response)
	   {
		   return false;
	   }
	   return true;
	}
	*/
	//Collection of queries to run on the raw impressions sites
	public function post_process_raw_sites($raw_imp_table)
	{
		$fix_queries = array();
		$fix_responses = array();
		$db_raw = $this->load->database(raw_db_groupname, true);
		//Queries can be added to $fix_queries array modify the raw table below. Feel free to add them/comment them appropriately
		//Will strip http:// from URLs
		$fix_queries['http'] = "UPDATE ".$raw_imp_table." SET Site = SUBSTRING(Site, POSITION('http://' IN Site)+7) WHERE Site LIKE \"http://%\" ";

		$fix_queries['encodedhttp'] = "UPDATE ".$raw_imp_table." SET Site = SUBSTRING(Site, POSITION('http\%3a\%2f\%2f' IN Site)+12) WHERE Site LIKE 'http\%3a\%2f\%2f%'";

		//Will strip port numbers from URLs
		$fix_queries['remote_ports'] = "UPDATE ".$raw_imp_table." SET Site = SUBSTRING(Site, 1, POSITION(':' IN Site)-1) WHERE Site LIKE '%:%' ";

		$fix_queries['slashes'] = "UPDATE ".$raw_imp_table." SET Site = SUBSTRING(Site,1, POSITION('/' IN Site)-1) WHERE Site LIKE '%/%' ";

		$fix_queries['not_url'] = "UPDATE ".$raw_imp_table." SET Site = 'All other sites' WHERE (Site NOT LIKE '%.%' AND Site != 'All other sites') OR Site = '' ";

		foreach($fix_queries as $name => $query)
		{
			$fix_responses[$name] = $db_raw->query($query);
		}

		foreach($fix_responses as $response)
		{
			if($response == false)
			{
				return false;
			}
		}
		return true;
	}

	public function fix_geofenced_impressions_with_no_locations($process_date, $tdgf_adgroups)
	{
		$db_main = $this->load->database(main_db_groupname, true);
		$db_main_name = $db_main->database;

		$city_records_geofence_status = $this->fix_cityrecords_geofenced_data($db_main, $process_date, $tdgf_adgroups);
		$zcta_records_geofence_status = $this->fix_zctarecords_geofenced_data($db_main, $process_date, $tdgf_adgroups);

		return [
			'cities' => $city_records_geofence_status,
			'zctas' => $zcta_records_geofence_status
		];
	}

	private function fix_cityrecords_geofenced_data($db_main, $process_date, $tdgf_adgroups)
	{
		$return_array = [
			'rows_updated' => 0,
			'rows_deleted' => 0
		];

		$adgroup_ids_insert_string = implode(',', array_fill(0, count($tdgf_adgroups), '?'));
		$bindings = array_merge(
			[$process_date],
			$tdgf_adgroups,
			[$process_date],
			$tdgf_adgroups,
			[cities_placeholder]
		);
		$select_city_sql = "
			SELECT cr.*
			FROM CityRecords AS cr
			WHERE
				cr.Date = ? AND
				cr.AdGroupId IN ({$adgroup_ids_insert_string}) AND
				cr.AdGroupID IN (
					SELECT DISTINCT cr.AdGroupId
					FROM CityRecords AS cr
					WHERE
						cr.Date = ? AND
						cr.AdGroupId IN ({$adgroup_ids_insert_string}) AND
						cr.City = ?
				)
			ORDER BY cr.AdGroupId
		";
		$city_result = $db_main->query($select_city_sql, $bindings);
		if($city_result->num_rows() > 0)
		{
			$city_rows = $city_result->result_array();
			$adgroups = array_unique(array_column($city_rows, 'AdGroupID'));
			$column_list = $city_result->list_fields();
			$column_list_string = implode(',', $column_list);
			foreach($adgroups as $adgroup)
			{
				$affected_rows = array_filter($city_rows, function($city_row) use ($adgroup){
					return $city_row['AdGroupID'] == $adgroup;
				});

				$all_other_cities_index = array_search(cities_placeholder, array_column($affected_rows, 'City'));
				if(count($affected_rows) > 1 && $all_other_cities_index !== false)
				{
					$row_to_distribute = array_splice($affected_rows, $all_other_cities_index, 1)[0];
					$impressions_to_distribute = intval($row_to_distribute['Impressions']);
					$clicks_to_distribute = intval($row_to_distribute['Clicks']);
					$cost_to_distribute = floatval($row_to_distribute['Cost']);

					$value_rows = '(' . implode(',', array_fill(0, count($row_to_distribute), '?')) . ')';
					$city_records_modifications = implode(",\n", array_fill(0, count($affected_rows), $value_rows));

					// Easier to do int division / mod on it this way and avoids rounding problems
					$cost_to_distribute_int = 100 * $cost_to_distribute;

					$bindings = [];
					for($i = 0; $i < count($affected_rows); $i++)
					{
						$affected_rows[$i]['Impressions'] += $this->integer_division($impressions_to_distribute, count($affected_rows));
						$affected_rows[$i]['Clicks'] += $this->integer_division($clicks_to_distribute, count($affected_rows));
						$affected_rows[$i]['Cost'] += ($this->integer_division($cost_to_distribute_int, count($affected_rows))) / 100;

						$affected_rows[$i]['Impressions'] += ($i === 0) ? ($impressions_to_distribute % count($affected_rows)) : 0;
						$affected_rows[$i]['Clicks'] += ($i === 0) ? ($clicks_to_distribute % count($affected_rows)) : 0;
						$affected_rows[$i]['Cost'] += ($i === 0) ? ($cost_to_distribute_int % count($affected_rows)) / 100 : 0;

						$bindings = array_merge($bindings, array_values($affected_rows[$i]));
					}

					$update_query =
					"	INSERT INTO CityRecords ({$column_list_string})
						VALUES {$city_records_modifications}
						ON DUPLICATE KEY UPDATE
							Impressions = VALUES(Impressions),
							Clicks = VALUES(Clicks),
							Cost = VALUES(Cost)
					";
					$db_main->query($update_query, $bindings);
					// UPDATE from ON DUPLICATE KEY UPDATE returns 2 (http://dev.mysql.com/doc/refman/5.6/en/insert-on-duplicate.html)
					$return_array['rows_updated'] += ($db_main->affected_rows() / 2);
					$db_main->query("DELETE FROM CityRecords WHERE AdGroupID = ? AND Date = ? AND City = ?", [$adgroup, $process_date, cities_placeholder]);
					$return_array['rows_deleted'] += $db_main->affected_rows();
				}
			}
		}
		return $return_array;
	}

	private function fix_zctarecords_geofenced_data($db_main, $process_date, $tdgf_adgroups)
	{
		$return_array = [
			'rows_updated' => 0,
			'rows_deleted' => 0
		];

		$adgroup_ids_insert_string = implode(',', array_fill(0, count($tdgf_adgroups), '?'));
		$bindings = array_merge(
			[$process_date],
			$tdgf_adgroups,
			[$process_date],
			$tdgf_adgroups
		);

		$select_zcta_sql = "
			SELECT zr.*
			FROM zcta_records AS zr
			WHERE
				zr.date = ? AND
				zr.ad_group_id IN ({$adgroup_ids_insert_string}) AND
				zr.ad_group_id IN (
					SELECT DISTINCT zr.ad_group_id
					FROM zcta_records AS zr
					WHERE
						zr.date = ? AND
						zr.ad_group_id IN ({$adgroup_ids_insert_string}) AND
						zr.gcd_id = 0
				)
			ORDER BY zr.ad_group_id
		";
		$zcta_result = $db_main->query($select_zcta_sql, $bindings);
		if($zcta_result->num_rows() > 0)
		{
			$zcta_rows = $zcta_result->result_array();
			$adgroups = array_unique(array_column($zcta_rows, 'ad_group_id'));
			$column_list = $zcta_result->list_fields();
			$column_list_string = implode(',', $column_list);
			foreach($adgroups as $adgroup)
			{
				$affected_rows = array_filter($zcta_rows, function($zcta_row) use ($adgroup){
					return $zcta_row['ad_group_id'] == $adgroup;
				});

				$all_other_zctas_index = array_search('0', array_column($affected_rows, 'gcd_id'), true);
				if(count($affected_rows) > 1 && $all_other_zctas_index !== false)
				{
					$row_to_distribute = array_splice($affected_rows, $all_other_zctas_index, 1)[0];
					$impressions_to_distribute = intval($row_to_distribute['impressions']);
					$clicks_to_distribute = intval($row_to_distribute['clicks']);
					$cost_to_distribute = floatval($row_to_distribute['cost']);

					$value_rows = '(' . implode(',', array_fill(0, count($row_to_distribute), '?')) . ')';
					$zcta_records_modifications = implode(",\n", array_fill(0, count($affected_rows), $value_rows));

					// Easier to do int division / mod on it this way and avoids rounding problems
					$cost_to_distribute_int = 100 * $cost_to_distribute;

					$bindings = [];
					for($i = 0; $i < count($affected_rows); $i++)
					{
						$affected_rows[$i]['impressions'] += $this->integer_division($impressions_to_distribute, count($affected_rows));
						$affected_rows[$i]['clicks'] += $this->integer_division($clicks_to_distribute, count($affected_rows));
						$affected_rows[$i]['cost'] += ($this->integer_division($cost_to_distribute_int, count($affected_rows))) / 100;

						$affected_rows[$i]['impressions'] += ($i === 0) ? ($impressions_to_distribute % count($affected_rows)) : 0;
						$affected_rows[$i]['clicks'] += ($i === 0) ? ($clicks_to_distribute % count($affected_rows)) : 0;
						$affected_rows[$i]['cost'] += ($i === 0) ? ($cost_to_distribute_int % count($affected_rows)) / 100 : 0;

						$bindings = array_merge($bindings, array_values($affected_rows[$i]));
					}

					$update_query =
					"	INSERT INTO zcta_records ({$column_list_string})
						VALUES {$zcta_records_modifications}
						ON DUPLICATE KEY UPDATE
							impressions = VALUES(impressions),
							clicks = VALUES(clicks),
							cost = VALUES(cost)
					";
					$db_main->query($update_query, $bindings);
					// UPDATE from ON DUPLICATE KEY UPDATE returns 2 (http://dev.mysql.com/doc/refman/5.6/en/insert-on-duplicate.html)
					$return_array['rows_updated'] += ($db_main->affected_rows() / 2);
					$db_main->query("DELETE FROM zcta_records WHERE ad_group_id = ? AND date = ? AND gcd_id = 0", [$adgroup, $process_date]);
					$return_array['rows_deleted'] += $db_main->affected_rows();
				}
			}
		}
		return $return_array;
	}

	private function integer_division($dividend, $divisor)
	{
		return ($dividend - $dividend % $divisor) / $divisor;
	}

	public function get_unknown_zips_from_raw_table($imp_table)
	{
		$db_raw = $this->load->database(raw_db_groupname, true);
		$db_main = $this->load->database(main_db_groupname, true);
		$db_main_name = $db_main->database;
		$query =
		"	SELECT DISTINCT
				ZipCode AS zip
			FROM
				{$imp_table} AS a
			LEFT JOIN
				{$db_main_name}.geo_zipcode_to_zcta gzz
				ON
					gzz.zip_int = a.ZipCode
			LEFT JOIN
				{$db_main_name}.geo_zcta_map gzm_from_zip
				ON
					gzm_from_zip.num_id = gzz.zcta_int
			LEFT JOIN
				{$db_main_name}.geo_zcta_map gzm
				ON
					gzm.num_id = a.ZipCode
			LEFT JOIN
				{$db_main_name}.geo_fsa_map gfm
				ON (
					gfm.char_1 = ORD(SUBSTRING(a.ZipCode, 1, 1)) AND
					gfm.digit_2 = SUBSTRING(a.ZipCode, 2, 1) AND
					gfm.char_3 = ORD(SUBSTRING(a.ZipCode, 3, 1))
				)
			WHERE
				gzz.zip_int IS NULL AND
				gfm.CFSAUID IS NULL AND
				gzm_from_zip.num_id IS NULL AND
				gzm.num_id IS NULL AND
				a.geofence_gcd_id < 1;
		";

		$result = $db_raw->query($query);
		if($result->num_rows() > 0)
		{
			return array_filter(array_column($result->result_array(), 'zip'));
		}
		return array();
	}

	public function get_percentage_of_incorrect_geofenced_regions($imp_table, $tdgf_adgroups)
	{
		$db_raw = $this->load->database(raw_db_groupname, true);
		$db_main = $this->load->database(main_db_groupname, true);
		$db_main_name = $db_main->database;

		$adgroup_ids_insert_string = implode(',', array_fill(0, count($tdgf_adgroups), '?'));
		$sql =
		"	SELECT
				raw.AdGroupId AS adgroup_id,
				COUNT(*) AS total_points,
				SUM(IF(raw.geofence_gcd_id < 1, 1, 0)) AS count_bad_zctas,
				SUM(IF(raw.geofence_place_id < 1, 1, 0)) AS count_bad_cities
			FROM
				{$imp_table} AS raw
			WHERE
				raw.AdGroupId IN ({$adgroup_ids_insert_string})
			GROUP BY
				adgroup_id
		";
		$result = $db_raw->query($sql, $tdgf_adgroups);
		$return_array = [];
		if($result->num_rows() > 0)
		{
			$result_array = $result->result_array();
			foreach($result_array as $adgroup_row)
			{
				$total_points = intval($adgroup_row['total_points']);
				$return_array[$adgroup_row['adgroup_id']] = [
					'total_points' => $total_points,
					'Bad Zctas' => round((intval($adgroup_row['count_bad_zctas']) / $total_points) * 100),
					'Bad Cities' => round((intval($adgroup_row['count_bad_cities']) / $total_points) * 100),
				];
			}
		}
		return $return_array;
	}

	public function get_percentage_of_zipcode_conversions($imp_table)
	{
		$db_raw = $this->load->database(raw_db_groupname, true);
		$db_main = $this->load->database(main_db_groupname, true);
		$db_main_name = $db_main->database;
		$query =
		"	SELECT
				COALESCE((100 * (a.converted_zips / b.total_zips)), 0.0) AS percentage
			FROM
			(
				SELECT
					count(DISTINCT(imps.ZipCode)) AS converted_zips
				FROM
					{$imp_table} AS imps
				LEFT JOIN
					{$db_main_name}.geo_zipcode_to_zcta gzz
					ON
						gzz.zip_int = imps.ZipCode
				WHERE
					gzz.zip_int IS NOT NULL AND
					gzz.zcta_int != gzz.zip_int
			) AS a,
			(
				SELECT
					count(DISTINCT(ZipCode)) AS total_zips
				FROM
					{$imp_table} AS imps
				LEFT JOIN
					{$db_main_name}.geo_zipcode_to_zcta gzz
					ON
						gzz.zip_int = imps.ZipCode
				WHERE
					gzz.zip_int IS NOT NULL
			) AS b;
		";
		$result = $db_raw->query($query);
		if($result->num_rows() == 1)
		{
			$row = $result->row();
			return $row->percentage;
		}
		return 0.0;
	}

	// sums of impressions, clicks, cost, post_click_conversion and
	// 	post_impression_conversion grouped by campaign id for a date.
	// 	This is a cache table used by various reporting screens in the application.
	// 	The cityrecords table is the source for this table.
	// 	This method only accepts one single day as input.
	// this method is called from cronjob td_uploader and also from frontend ui from campaigns page to refresh data for a campaign manually.
	// Jan 29 2015. Amit R Dar

	public function load_report_cached_campaign($caller_source, $date, $campaign_id)
	{
		$db_main = $this->load->database(main_db_groupname, true);

		$sql_where_condition = "";
		$single_campaign_condition = "(
						SELECT
							dd.campaign_id
						FROM
							AdGroups dd
						WHERE
							dd.id = cr.adgroupid
						ORDER BY
							dd.id DESC LIMIT 1
			)";
		if ($caller_source == 'DATE_MODE') {
			$sql_where_condition = " WHERE cr.date = '".$date."'";
		} elseif ($caller_source == 'CAMPAIGN_MODE') {
			$sql_where_condition = ' WHERE ag.campaign_id = '.$campaign_id;
			$single_campaign_condition = $campaign_id;
		} elseif ($caller_source == 'REFRESH_ALL') {
			$delete_query =	"TRUNCATE report_cached_adgroup_date";
			$db_main->query($delete_query);
		}

		$scoop_query =
		"	INSERT INTO report_cached_adgroup_date
			(	adgroup_id,
				date,
				impressions,
				clicks,
				cost,
				post_click_conversion_view_through,
				post_impression_conversion,
				retargeting_impressions,
				retargeting_clicks,
				created_time
			) (
			SELECT
				ag.id,
				cr.date,
				SUM(cr.impressions) ,
				SUM(cr.clicks),
				SUM(cr.cost),
				SUM(cr.post_click_conversion_1 + cr.post_click_conversion_2 + cr.post_click_conversion_3 + cr.post_click_conversion_4 + cr.post_click_conversion_5 + cr.post_click_conversion_6),
				SUM(cr.post_impression_conversion_1 + cr.post_impression_conversion_2 + cr.post_impression_conversion_3 + cr.post_impression_conversion_4 + cr.post_impression_conversion_5 + cr.post_impression_conversion_6),
				SUM( 	CASE
						WHEN ag.isretargeting = '1' THEN cr.impressions
						ELSE 0
						end) ,
				SUM(	CASE
						WHEN ag.isretargeting = '1' THEN cr.clicks
						ELSE 0
						end) ,
				Now()
			FROM
				CityRecords cr
			INNER JOIN
				AdGroups ag
					ON ag.campaign_id = $single_campaign_condition
			AND ag.id = cr.adgroupid $sql_where_condition
			GROUP BY
				ag.id ,
				cr.date )
			ON DUPLICATE KEY UPDATE
				impressions = VALUES ( impressions ) ,
				clicks = VALUES ( clicks ) ,
				cost = VALUES ( cost ) ,
				post_click_conversion_view_through = VALUES ( post_click_conversion_view_through ) ,
				post_impression_conversion = VALUES ( post_impression_conversion ) ,
				retargeting_impressions = VALUES ( retargeting_impressions ) ,
				retargeting_clicks = VALUES ( retargeting_clicks )
		";
		$db_main->query($scoop_query, $date);
		return $db_main->affected_rows();
	}

	public function get_site_rows_post_aggregation_for_date($date)
	{
		$db_main = $this->load->database('main', true);
		$data = array();

		$site_row_query =
		"	SELECT
				COUNT(*) AS num_site_rows
			FROM
				SiteRecords
			WHERE
				Date = ?;
		";
		$site_rows = $db_main->query($site_row_query, $date);
		if($site_rows === false || $site_rows->num_rows() < 1)
		{
			return false;
		}
		$site_result_array = $site_rows->row_array();
		return $site_result_array['num_site_rows'];
	}

	public function check_that_impressions_match_between_sites_tables($date)
	{
		$sites_placeholder_small_impressions = self::all_other_sites_small_impressions_placeholder;

		$db_main = $this->load->database('main', true);
		$bindings = [$date, $date];
		$sql =
		"	SELECT
				COALESCE(sr.total_impressions, -1) = COALESCE(raos.total_impressions, -1) AS impressions_match,
				COALESCE(sr.count_aggregated_adgroups, -1) = COALESCE(raos.count_adgroups, -1) AS adgroup_counts_match,
				sr.total_impressions AS siterecords_impressions,
				sr.count_aggregated_adgroups AS siterecords_adgroups,
				raos.total_impressions AS small_imps_impressions,
				raos.count_adgroups AS small_imps_adgroups
			FROM
			(
				SELECT
					Date AS date,
					SUM(Impressions) AS total_impressions,
					COUNT(DISTINCT AdGroupId) AS count_aggregated_adgroups
				FROM
					SiteRecords
				WHERE
					Date = ? AND
					Site = '{$sites_placeholder_small_impressions}'
			) AS sr
			LEFT JOIN
			(
				SELECT
					Date AS date,
					SUM(Impressions) AS total_impressions,
					COUNT(DISTINCT AdGroupId) AS count_adgroups
				FROM
					report_all_other_sites_small_impressions
				WHERE
					Date = ?
			) AS raos
			ON
				raos.date = sr.date
		";

		$return_array = ['is_success' => true];
		$result = $db_main->query($sql, $bindings);
		if($result->num_rows() > 0)
		{
			$row = $result->row();
			$impressions_match = intval($row->impressions_match);
			$adgroup_counts_match = intval($row->adgroup_counts_match);

			if(!($impressions_match && $adgroup_counts_match))
			{
				$return_array['is_success'] = false;
				$error_string = '';

				$error_string = (!$impressions_match) ? "Impression mismatch (SiteRecords {$row->siterecords_impressions} / report_all_other_sites_small_impressions {$row->small_imps_impressions})" : '';
				$error_string = (!$impressions_match && !$adgroup_counts_match) ? ', ' : '';
				$error_string = (!$adgroup_counts_match) ? "AdGroup mismatch (SiteRecords {$row->siterecords_adgroups} / report_all_other_sites_small_impressions {$row->small_imps_adgroups})" : '';

				$return_array['error'] = $error_string;
			}
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['error'] = 'Unable to check impressions between siterecords tables';
		}
		return $return_array;
	}

	private function get_uploader_blocklist($get_blacklist = false)
	{
		$is_blacklist = 0;
		$get_bad_sites_query = "
				SELECT
					bad_site
				FROM
					td_uploader_blocklist
				WHERE
					is_blacklist = ?
				";
		if($get_blacklist)
		{
			$is_blacklist = 1;
		}
		$blocklist_result = $this->db->query($get_bad_sites_query, $is_blacklist);
		if($blocklist_result === false)
		{
			return false;
		}
		return $blocklist_result->result_array();
	}


	public function remove_raw_blacklist_sites($raw_imp_table, $raw_clk_table, $raw_bl_imp_table, $raw_bl_clk_table)
	{
		$db_raw = $this->load->database(raw_db_groupname, true);

		$return_array = array();
		$return_array['is_success'] = false;
		$return_array['removed_clks'] = 0;
		$return_array['removed_imps'] = 0;
		$return_array['err_msg'] = "";
		$get_blacklist_bad_sites = true;

		$get_black_sites_result = $this->get_uploader_blocklist($get_blacklist_bad_sites);
		if($get_black_sites_result === false)
		{
			$return_array['err_msg'] = "Failed to retrieve blocklist data";
			return $return_array;
		}

		$blacklist_clauses = array();
		$raw_table_clauses = array();
		foreach($get_black_sites_result as $blacklist_site)
		{
			$blacklist_clauses[] = "imps.Site LIKE \"". $blacklist_site['bad_site']."\"";
			$raw_table_clauses[] = "Site LIKE \"". $blacklist_site['bad_site']."\"";
		}

		if(count($blacklist_clauses) == 0)
		{
			$return_array['is_success'] = true;
			return $return_array;
		}

		$blacklist_query_string = implode(' OR ', $blacklist_clauses);
		$raw_table_query_string = implode(' OR ', $raw_table_clauses);

		$copy_blacklist_clicks_query = "
					REPLACE INTO
						{$raw_bl_clk_table}
					(SELECT
						clks.*
					FROM
						{$raw_clk_table} AS clks
						JOIN {$raw_imp_table} AS imps
							ON (clks.VantageLocalId = imps.VantageLocalId)
					WHERE
						{$blacklist_query_string})";
		$copy_bl_clicks_result = $db_raw->query($copy_blacklist_clicks_query);
		if($copy_bl_clicks_result === false)
		{
			$return_array['err_msg'] = "Failed to locate blacklist clicks";
			return $return_array;
		}

		$delete_blacklist_clicks_query = "
			DELETE FROM
				{$raw_clk_table}
			WHERE
				ClickId IN
					(SELECT
						ClickId
					FROM
						{$raw_bl_clk_table})";
		$delete_bl_clicks_result = $db_raw->query($delete_blacklist_clicks_query);
		if($delete_bl_clicks_result === false)
		{
			$return_array['err_msg'] = "Failed to delete blacklist clicks";
			return $return_array;
		}
		$return_array['removed_clks'] = $db_raw->affected_rows();

		$copy_blacklist_imps_query = "
				REPLACE INTO
						{$raw_bl_imp_table}
					(SELECT
						imps.*
					FROM
						{$raw_imp_table} imps
					WHERE
						{$blacklist_query_string})";
		$copy_bl_imps_result = $db_raw->query($copy_blacklist_imps_query);
		if($copy_bl_imps_result === false)
		{
			$return_array['err_msg'] = "Failed to locate blacklist impressions";
			return $return_array;
		}

		$delete_blacklist_imps_query = "
			DELETE FROM
				{$raw_imp_table}
			WHERE
				{$raw_table_query_string}";
		$delete_bl_imps_result = $db_raw->query($delete_blacklist_imps_query);
		if($delete_bl_imps_result == false)
		{
			$return_array['err_msg'] = "Failed to delete blacklist impressions";
			return $return_array;
		}
		$return_array['removed_imps'] = $db_raw->affected_rows();
		$return_array['is_success'] = true;
		return $return_array;
	}

	public function delete_tables_for_date($delete_date)
	{
		$delete_tables_sql = "
			DROP TABLE IF EXISTS
				td_raw_impressions_{$delete_date},
				td_raw_clicks_{$delete_date},
				td_raw_impressions_{$delete_date}_BLACKLIST,
				td_raw_clicks_{$delete_date}_BLACKLIST;
		";
		$db_raw = $this->load->database(raw_db_groupname, true);
		$delete_tables_result = $db_raw->query($delete_tables_sql);
		return $delete_tables_result;
	}

	public function do_td_impressions_exist_for_date($date)
	{
		$find_impressions_query =
		"SELECT
			rcad.*
		FROM
			report_cached_adgroup_date AS rcad
			JOIN AdGroups AS adg
				ON (rcad.adgroup_id = adg.ID)
		WHERE
			rcad.date = ?
			AND adg.source = 'TD'
			AND rcad.impressions > 0
		LIMIT
			1
		";

		$find_impressions_result = $this->db->query($find_impressions_query, $date);

		if($find_impressions_result == false || $find_impressions_result->num_rows() == 0)
		{
			return false;
		}
		return true;
	}
}

?>
