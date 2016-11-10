<?php

class dbm_uploader_model extends CI_Model 
{
	const trafficing_system_dfp = 'DFP';
	const trafficing_system_dbm = 'DBM';
	public function translate_report_data_directory_to_data_arrays($file_path)
	{
		$downloaded_files = array_diff(scandir($file_path), array('.','..'));
		$csv_data = array();
				
		foreach($downloaded_files as $file_name)
		{
			$full_file_path = $file_path."/".$file_name;
			$file = fopen($full_file_path, 'r');
			if($file == false)
			{
				return false;
			}
			$header = null;
			while(($row = fgetcsv($file, 0, ",")) != false && count($row) > 1)
			{
				if($header == null)
				{
					$header = $row;
				}
				else
				{
					$csv_data_row = array_combine($header, $row);
					if($csv_data_row['Line Item'] != "")
					{
						$csv_data[] = $csv_data_row;
					} 
				}
			}
				fclose($file);
			}
	    return $csv_data;
	}

	public function add_csv_rows_for_report_type_to_raw_for_date($csv_data_rows, $csv_type, $report_date, $raw_table)
	{
		$table_cols = "adgroup_id, date";
		$type_specific_fields = "";
		$csv_type_new = (strpos($csv_type, 'dfp') !== FALSE) ? strtok($csv_type, '-') : $csv_type; //Checked type for DFP & DBM	 
		switch($csv_type_new)
		{
			case "geo":
				$type_specific_fields = ", impressions, clicks, city, region, post_click_conversions, post_impression_conversions";
				$query_entry = "(?,?,?,?,?,?,?,?)";
				break;
			case "site":
				$type_specific_fields = ", impressions, clicks, site, post_click_conversions, post_impression_conversions";
				$query_entry = "(?,?,?,?,?,?,?)";
				break;
			case "size":
				$type_specific_fields = ", impressions, clicks, size";
				$query_entry = "(?,?,?,?,?)";
				break;
			case "creative":
				$type_specific_fields = ", impressions, clicks, dfa_placement_id";
				$query_entry = "(?,?,?,?,?)";
				break;
			case "video":
				$type_specific_fields = ", video_starts, video_25_percent_viewed, video_50_percent_viewed, video_75_percent_viewed, video_100_percent_viewed";
				$query_entry = "(?,?,?,?,?,?,?)";
				break;
			case "trueview":
				$query_entry = "(?, ?)";
				break;
			default:
				//Unrecognized csv_type
				return false;
		}

		$query_values = array();
		$insert_bindings = array();
		if($csv_type == 'site-dfp')
		{
			$map_url = array();
			$map_url = $this->map_url_site_data();
		}
		foreach($csv_data_rows as $data_row)
		{
			$query_values[] = $query_entry;
			$insert_bindings[] = $data_row['Line Item ID']; //Checked type for DFP & DBM	 
			$insert_bindings[] = $report_date;

			switch($csv_type)
			{
				case "geo":
					$insert_bindings[] = $data_row['Impressions'];
					$insert_bindings[] = $data_row['Clicks'];
					$insert_bindings[] = $data_row['City'];
					$insert_bindings[] = $data_row['Region'];
					$insert_bindings[] = $data_row['Post-Click Conversions'];
					$insert_bindings[] = $data_row['Post-View Conversions'];
					break;
				case "geo-dfp":
					$insert_bindings[] = $data_row['AD_SERVER_IMPRESSIONS'];
					$insert_bindings[] = $data_row['AD_SERVER_CLICKS'];
					$insert_bindings[] = $data_row['CITY_NAME'];
					$insert_bindings[] = $data_row['REGION_NAME'];
					$insert_bindings[] = 0;
					$insert_bindings[] = 0;
					break;    
				case "site":
					$insert_bindings[] = $data_row['Impressions'];
					$insert_bindings[] = $data_row['Clicks'];				
					$insert_bindings[] = $data_row['App/URL'];
					$insert_bindings[] = $data_row['Post-Click Conversions'];
					$insert_bindings[] = $data_row['Post-View Conversions'];
					break;
				case "site-dfp":
					$insert_bindings[] = $data_row['AD_SERVER_IMPRESSIONS'];
					$insert_bindings[] = $data_row['AD_SERVER_CLICKS'];				
					$insert_bindings[] = array_key_exists($data_row['AD_UNIT_NAME'],$map_url) ? $map_url[$data_row['AD_UNIT_NAME']] : '';
					$insert_bindings[] = $data_row['CLICK_THROUGH_CONVERSIONS'];
					$insert_bindings[] = $data_row['VIEW_THROUGH_CONVERSIONS'];
					break;    
				case "size":
					$insert_bindings[] = $data_row['Impressions'];
					$insert_bindings[] = $data_row['Clicks'];				
					$insert_bindings[] = $data_row['Creative Width']."x".$data_row['Creative Height'];
					break;
				case "size-dfp":
					$insert_bindings[] = $data_row['AD_SERVER_IMPRESSIONS'];
					$insert_bindings[] = $data_row['AD_SERVER_CLICKS'];				
					$insert_bindings[] = $data_row['CREATIVE_SIZE'];
					break;    
				case "creative":
					$insert_bindings[] = $data_row['Impressions'];
					$insert_bindings[] = $data_row['Clicks'];				
					$insert_bindings[] = $data_row['DCM Placement ID'];
					break;
				case "creative-dfp":
					$insert_bindings[] = $data_row['AD_SERVER_IMPRESSIONS'];
					$insert_bindings[] = $data_row['AD_SERVER_CLICKS'];				
					$insert_bindings[] = $data_row['CREATIVE_ID'];
					break;    
				case "video":
					$insert_bindings[] = $data_row['Starts (Video)'];
					$insert_bindings[] = $data_row['First-Quartile Views (Video)'];
					$insert_bindings[] = $data_row['Midpoint Views (Video)'];
					$insert_bindings[] = $data_row['Third-Quartile Views (Video)'];
					$insert_bindings[] = $data_row['Complete Views (Video)'];
					break;
				case "trueview":
					//Don't gotta do nothin'
					break;
				default:
					//Unrecognized csv_type
					return false;
			}
		}
		$query_values = implode(',', $query_values);

		$this->raw_db = $this->load->database('td_intermediate', true);   
		
		$insert_raw_dbm_data_query = "INSERT INTO {$raw_table} (".$table_cols.$type_specific_fields.") VALUES ".$query_values;
		$insert_raw_data_result = $this->raw_db->query($insert_raw_dbm_data_query, $insert_bindings);
		if($insert_raw_data_result == false)
		{
			return false;
		}
		return $this->raw_db->affected_rows();
	}
	
	public function scoop_blacklist_sites($raw_site_table)
	{
		$get_bad_sites_query = "
				SELECT
					bad_site
				FROM
					td_uploader_blocklist";
		$blocklist_result = $this->db->query($get_bad_sites_query);
		if($blocklist_result === false)
		{
			return false;
		}
		$blocklist = $blocklist_result->result_array();
				
		if(count($blocklist) == 0)
		{
			return true;
		}
		
		$raw_table_clauses = array();
		foreach($blocklist as $blocklist_site)
		{
			$raw_table_clauses[] = "site LIKE \"". $blocklist_site['bad_site']."\"";
		}
		$blocklist_clause = implode(" OR ", $raw_table_clauses);
		
		$scoop_blocklist_query = "
			UPDATE
				{$raw_site_table}
			SET
				Site = \"All other sites\"
			WHERE ".$blocklist_clause;
				
		$this->raw_db = $this->load->database('td_intermediate', true);   
		$scoop_blocklist_result = $this->raw_db->query($scoop_blocklist_query);
		if($scoop_blocklist_result == false)
		{
			return false;
		}
		return true;
	}
	
	public function convert_trueview_sites($raw_site_table, $raw_trueview_table)
	{
		$this->raw_db = $this->load->database('td_intermediate', true);
		$fix_trueview_query = 
		"UPDATE 
			{$raw_site_table}
		SET 
			site = 'youtube.com'
		WHERE
			adgroup_id IN (SELECT DISTINCT adgroup_id FROM {$raw_trueview_table})
		";

		return $this->raw_db->query($fix_trueview_query);
	}

	public function convert_trueview_cities($raw_city_table, $raw_trueview_table)
	{
		$this->raw_db = $this->load->database('td_intermediate', true);
		$fix_trueview_query = 
		"UPDATE 
			{$raw_city_table}
		SET 
			city = 'YouTube (City Not Set)',
			region = 'YouTube (Region Not Set)'
		WHERE
			adgroup_id IN (SELECT DISTINCT adgroup_id FROM {$raw_trueview_table})
		";

		return $this->raw_db->query($fix_trueview_query);
	}	

	public function post_process_raw_sites($raw_site_table)
	{
		$fix_queries = array();
		$fix_responses = array();

		$fix_queries['tail_site_aggregates'] = "UPDATE ".$raw_site_table." SET site = \"All other sites\" WHERE impressions < 10 AND clicks = 0";
		
		$fix_queries['youtube'] = "UPDATE ".$raw_site_table." SET site = \"youtube.com\" WHERE site LIKE \"%Youtube Channel >>\"";

		$fix_queries['android_apps'] = "UPDATE ".$raw_site_table." SET site = \"Android Apps\" WHERE site LIKE \"% - Android %\"";
		$fix_queries['ios_apps'] = "UPDATE ".$raw_site_table." SET site = \"iOS Apps\" WHERE site LIKE \"% - iOS %\"";
		$fix_queries['other_apps'] = "UPDATE ".$raw_site_table." SET site = \"Other Apps\" WHERE site LIKE \"% app%\" AND site != \"Android Apps\" AND site != \"iOS Apps\"";
		
		$fix_queries['misc_channels'] = "UPDATE ".$raw_site_table." SET site = \"All other sites\" WHERE site LIKE \"%Channel >>%\"";
		
		$fix_queries['paren_channels'] = "UPDATE ".$raw_site_table." SET site = \"All other sites\" WHERE site LIKE \"%(%)%\"";
		
		$fix_queries['slashes'] = "UPDATE ".$raw_site_table." SET site = SUBSTRING(site,1, POSITION('/' IN site)-1) WHERE site LIKE '%/%' ";
		
		$fix_queries['not_url'] = "UPDATE ".$raw_site_table." SET site = 'All other sites' WHERE (site NOT LIKE '%.%' AND (site != \"All other sites\" AND site != \"Android Apps\" AND site != \"iOS Apps\" AND site != \"Other Apps\")) OR site = '' ";
		
		return $this->run_post_processing_queries($fix_queries);
	}	
	
	public function post_process_raw_cities($raw_cities_table)
       {
                $fix_queries = array();
                $fix_responses = array();

                $fix_queries['stuff_with_parenthesis'] = "UPDATE ".$raw_cities_table." SET region = SUBSTRING(region,1, POSITION(' (' IN region)-1) WHERE region LIKE '% (%)%' ";

                $fix_queries['all_other_cities'] = "UPDATE ".$raw_cities_table." SET city = \"All other cities\", region = \"All other regions\"  WHERE region = \"Unknown\" OR region = \"N/A\"";
		$fix_queries['all_other_region_dfp'] = "UPDATE ".$raw_cities_table." SET region = \"All other regions\"  WHERE region = \"N/A\"";
		$fix_queries['all_other_region_dfp'] = "UPDATE ".$raw_cities_table." SET city = \"All other cities\" WHERE city = \"N/A\"";
		$fix_queries['all_other_cities_with_region'] = "UPDATE ".$raw_cities_table." SET city = \"All other cities\" WHERE city = \"Unknown\" AND region != \"Unknown\"";

                return $this->run_post_processing_queries($fix_queries);
       }		
	
	private function run_post_processing_queries($post_processing_queries)
	{
		$this->raw_db = $this->load->database('td_intermediate', true);  
		foreach($post_processing_queries as $name => $query)
		{
			$fix_responses[$name] = $this->raw_db->query($query);
			if($fix_responses[$name] == false)
			{
				return false;
			}
		}
		return true;
	}

	public function match_raw_creatives($raw_creatives_table)
	{
		$this->raw_db = $this->load->database('td_intermediate', true);
		$match_creatives_query = 
		"UPDATE 
			{$raw_creatives_table} AS r_cre
			LEFT JOIN {$this->db->database}.cup_creatives AS cre
				ON (r_cre.dfa_placement_id = cre.dfa_placement_id)
		SET
			r_cre.freq_creative_id = cre.id";

		return $this->raw_db->query($match_creatives_query);
	}
	
	public function gather_impression_and_click_counts_for_tables($raw_site_table, $raw_city_table, $raw_size_table, $raw_creative_table, $raw_video_table)
	{
		$this->raw_db = $this->load->database('td_intermediate', true);
		$return_array = array();
		$return_array['site_data'] = array();
		$return_array['city_data'] = array();
		
		$raw_count_query = "
			SELECT
				SUM(impressions) AS imps,
				SUM(clicks) as clks,
				SUM(post_click_conversions) + SUM(post_impression_conversions) AS viewthroughs
			FROM {$raw_site_table}";
		
		$raw_site_count_result = $this->raw_db->query($raw_count_query);
		if($raw_site_count_result == false)
		{
			return false;	
		}
		
		$raw_count_query = "
			SELECT
				SUM(impressions) AS imps,
				SUM(clicks) AS clks,
				SUM(post_click_conversions) + SUM(post_impression_conversions) AS viewthroughs
			FROM {$raw_city_table}";
		$raw_city_count_result = $this->raw_db->query($raw_count_query);
		if($raw_city_count_result == false)
		{
			return false;
		}

		$raw_count_query = "
			SELECT
				SUM(impressions) AS imps,
				SUM(clicks) AS clks
			FROM {$raw_size_table}";
		$raw_size_count_result = $this->raw_db->query($raw_count_query);
		if($raw_size_count_result == false)
		{
			return false;
		}
		
		$raw_count_query = "
			SELECT
				SUM(impressions) AS imps,
				SUM(clicks) AS clks
			FROM {$raw_creative_table}";
		$raw_creative_count_result = $this->raw_db->query($raw_count_query);
		if($raw_creative_count_result == false)
		{
			return false;
		}

		if($raw_video_table != NULL)
		{
			$raw_count_query = "
				SELECT
					SUM(video_starts) AS starts
				FROM {$raw_video_table}";
			$raw_video_count_result = $this->raw_db->query($raw_count_query);
			if($raw_video_count_result == false)
			{
				return false;
			}
		}

		$return_array['site_data'] = $raw_site_count_result->row_array();
		$return_array['city_data'] = $raw_city_count_result->row_array();
		$return_array['size_data'] = $raw_size_count_result->row_array();
		$return_array['creative_data'] = $raw_creative_count_result->row_array();
		
		if($raw_video_table != NULL)
		{
			$return_array['video_data'] = $raw_video_count_result->row_array();
		}	

		
		return $return_array;
	}
	
	public function transfer_tables_to_aggregate_table($raw_site_table, $raw_city_table, $raw_size_table, $raw_creative_table, $raw_video_table)
	{
		$this->raw_db = $this->load->database('td_intermediate', true);
		$return_array = array();

		$insert_dbm_site_records_query = "INSERT INTO 
									SiteRecords (AdGroupID, Date, Impressions, Clicks, Cost, Site, Base_Site, post_click_conversion_1, post_impression_conversion_1)
									(SELECT 
										adgroup_id,
										date,
										SUM(impressions) AS imprs,
										SUM(clicks) AS clks,
										0,
										site,
										site,
										SUM(post_click_conversions),
										SUM(post_impression_conversions)
									FROM ".$this->raw_db->database.".".$raw_site_table."
									GROUP BY
										adgroup_id, date, site) 
									ON DUPLICATE KEY UPDATE 
										Impressions = VALUES(Impressions),
										Clicks = VALUES(Clicks)";
		$insert_dbm_site_records_result = $this->db->query($insert_dbm_site_records_query);
		if($insert_dbm_site_records_result == false)
		{
			return false; 
		}
		$return_array['site_row_count'] = $this->raw_db->affected_rows();

		$insert_dbm_city_records_query = "INSERT INTO 
									CityRecords (AdGroupID, Date, Impressions, Clicks, Cost, City, Region, post_click_conversion_1, post_impression_conversion_1)
									(SELECT 
										adgroup_id,
										date,
										SUM(impressions) AS imprs,
										SUM(clicks) AS clks,
										0,
										city,
										region,
										SUM(post_click_conversions),
										SUM(post_impression_conversions)
									FROM ".$this->raw_db->database.".".$raw_city_table."
									GROUP BY
										adgroup_id, date, city, region) 
									ON DUPLICATE KEY UPDATE 
										Impressions = VALUES(Impressions),
										Clicks = VALUES(Clicks)";
		$insert_dbm_city_records_result = $this->db->query($insert_dbm_city_records_query);
		if($insert_dbm_city_records_result == false)
		{
			return false; 
		}
		$return_array['city_row_count'] = $this->raw_db->affected_rows();

		$insert_dbm_size_records_query = "INSERT INTO 
									report_ad_size_records (AdGroupID, Date, Impressions, Clicks, Cost, Size)
									(SELECT 
										adgroup_id,
										date,
										SUM(impressions) AS imprs,
										SUM(clicks) AS clks,
										0,
										size
									FROM ".$this->raw_db->database.".".$raw_size_table."
									GROUP BY
										adgroup_id, date, size) 
									ON DUPLICATE KEY UPDATE 
										Impressions = VALUES(Impressions),
										Clicks = VALUES(Clicks)";
		$insert_dbm_size_records_result = $this->db->query($insert_dbm_size_records_query);
		if($insert_dbm_size_records_result == false)
		{
			return false; 
		}
		$return_array['size_row_count'] = $this->raw_db->affected_rows();

		$insert_dbm_creative_records_query = "INSERT INTO 
									report_creative_records (adgroup_id, date, impressions, clicks, cost, creative_id, tp_creative_id, tp_creative_source)
									(SELECT 
										adgroup_id,
										date,
										SUM(impressions) AS imprs,
										SUM(clicks) AS clks,
										0,
										freq_creative_id,
										dfa_placement_id,
										1
									FROM ".$this->raw_db->database.".".$raw_creative_table."
									GROUP BY
										adgroup_id, date, dfa_placement_id) 
									ON DUPLICATE KEY UPDATE 
										impressions = VALUES(impressions),
										clicks = VALUES(clicks),
										creative_id = VALUES(creative_id)";
		$insert_dbm_creative_records_result = $this->db->query($insert_dbm_creative_records_query);
		if($insert_dbm_creative_records_result == false)
		{
			return false; 
		}
		$return_array['creative_row_count'] = $this->raw_db->affected_rows();

		if($raw_video_table != NULL)
		{
			$insert_dbm_video_records_query = "INSERT INTO 
										report_video_play_records (AdGroupID, date, start_count, 25_percent_count, 50_percent_count, 75_percent_count, 100_percent_count)
										(SELECT 
											adgroup_id,
											date,
											SUM(video_starts) AS starts,
											SUM(video_25_percent_viewed) AS 25_pcnt,
											SUM(video_50_percent_viewed) AS 50_pcnt,
											SUM(video_75_percent_viewed) AS 75_pcnt,
											SUM(video_100_percent_viewed) AS 100_pcnt
										FROM ".$this->raw_db->database.".".$raw_video_table."
										WHERE 
											video_starts > 0
										GROUP BY
											adgroup_id, date) 
										ON DUPLICATE KEY UPDATE 
											start_count = VALUES(start_count),
											25_percent_count = VALUES(25_percent_count),
											50_percent_count = VALUES(50_percent_count),
											75_percent_count = VALUES(75_percent_count),
											100_percent_count = VALUES(100_percent_count)";
			$insert_dbm_video_records_result = $this->db->query($insert_dbm_video_records_query);
			if($insert_dbm_video_records_result == false)
			{
				return false; 
			}
			$return_array['video_row_count'] = $this->raw_db->affected_rows();
		}
		return $return_array;
	}
	
	public function create_raw_dbm_tables($raw_site_table, $raw_city_table, $raw_size_table, $raw_creative_table, $raw_video_table, $raw_trueview_table)
	{
		$this->raw_db = $this->load->database('td_intermediate', true);
		$create_raw_sites_query = "CREATE TABLE `{$raw_site_table}` (
									`adgroup_id` varchar(31) NOT NULL,
									`date` date NOT NULL,
									`site` varchar(127) NOT NULL,
									`impressions` int(11) NOT NULL,
									`clicks` int(11) NOT NULL,
									`post_click_conversions` int(11) NOT NULL,
									`post_impression_conversions` int(11) NOT NULL
								  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
		
		$create_raw_cities_query = "CREATE TABLE `{$raw_city_table}` (
									`adgroup_id` varchar(31) NOT NULL,
									`date` date NOT NULL,
									`city` varchar(31) NOT NULL,
									`region` varchar(31) NOT NULL,
									`impressions` int(11) NOT NULL,
									`clicks` int(11) NOT NULL,
									`post_click_conversions` int(11) NOT NULL,
									`post_impression_conversions` int(11) NOT NULL
								  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
		
		$create_raw_sizes_query = "CREATE TABLE `{$raw_size_table}` (
									`adgroup_id` varchar(31) NOT NULL,
									`date` date NOT NULL,
									`size` varchar(10) NOT NULL,
									`impressions` int(11) NOT NULL,
									`clicks` int(11) NOT NULL
								  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

		$create_raw_creatives_query = "CREATE TABLE `{$raw_creative_table}` (
									`adgroup_id` varchar(31) NOT NULL,
									`date` date NOT NULL,
									`dfa_placement_id` bigint(20) NOT NULL,
									`freq_creative_id` int(11), 
									`impressions` int(11) NOT NULL,
									`clicks` int(11) NOT NULL
								  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

		if($raw_video_table != NULL)
		{    
			$create_raw_video_query = "CREATE TABLE `{$raw_video_table}` (
										`adgroup_id` varchar(31) NOT NULL,
										`date` date NOT NULL,
										`video_starts` int(11) NOT NULL,
										`video_25_percent_viewed` int(11) NOT NULL,
										`video_50_percent_viewed` int(11) NOT NULL,
										`video_75_percent_viewed` int(11) NOT NULL,
										`video_100_percent_viewed` int(11) NOT NULL
									  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";	
		}
		
		if($raw_trueview_table != NULL)
		{
			$create_raw_trueview_query = "CREATE TABLE `{$raw_trueview_table}` (
										`adgroup_id` varchar(31) NOT NULL,
										`date` date NOT NULL
									  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
		}

		$create_sites = $this->raw_db->query($create_raw_sites_query);
		$create_geos = $this->raw_db->query($create_raw_cities_query);
		$create_sizes = $this->raw_db->query($create_raw_sizes_query);
		$create_creatives = $this->raw_db->query($create_raw_creatives_query);
		$create_video = ($raw_video_table == NULL) ? true : $this->raw_db->query($create_raw_video_query);
		$create_trueview = ($raw_trueview_table == NULL) ? true : $this->raw_db->query($create_raw_trueview_query);
		if($create_sites == false || $create_geos == false || $create_sizes == false || $create_creatives == false || $create_video == false || $create_trueview == false)
		{
			return false;
		}
		$this->raw_db->close();
		return true;
	}
	
	public function clear_raw_dbm_tables($raw_table_array)
	{
		$this->raw_db = $this->load->database('td_intermediate', true);
		foreach($raw_table_array as $raw_table)
		{
			$delete_query =  "TRUNCATE TABLE {$raw_table}";
			$delete_result = $this->raw_db->query($delete_query);
			if($delete_result == false)
			{
				return false;
			}
		}
		$this->raw_db->close();
		return true;
	}
	
	public function get_aggregate_counts_for_adgroups_for_date($date,$uploader_type)
	{
		$ad_source = ($uploader_type == self::trafficing_system_dfp) ? 'DFPBH' : self::trafficing_system_dbm;
		$return_array = array();
		$get_dbm_count_query = "
			SELECT
				COALESCE(SUM(r.Impressions), 0) AS imps,
				COALESCE(SUM(r.Clicks), 0 ) AS clks,
				COALESCE((SUM(r.post_click_conversion_1) + SUM(r.post_impression_conversion_1)), 0 ) AS viewthroughs
			FROM SiteRecords AS r
			JOIN AdGroups AS adg
				ON (r.AdGroupID = adg.ID)
			WHERE r.Date = ? AND adg.Source = \"".$ad_source."\"";
		$agg_dbm_site_count_result = $this->db->query($get_dbm_count_query, $date);
		if($agg_dbm_site_count_result == false)
		{
			return false; 
		}
		
		$get_dbm_count_query = "
			SELECT
				COALESCE(SUM(r.Impressions), 0) AS imps,
				COALESCE(SUM(r.Clicks), 0) AS clks,
				COALESCE((SUM(r.post_click_conversion_1) + SUM(r.post_impression_conversion_1)), 0 ) AS viewthroughs
			FROM CityRecords AS r
			JOIN AdGroups AS adg
				ON (r.AdGroupID = adg.ID)
			WHERE r.Date = ? AND adg.Source = \"".$ad_source."\"";
		$agg_dbm_city_count_result = $this->db->query($get_dbm_count_query, $date);
		if($agg_dbm_city_count_result == false)
		{
			return false; 
		}		

		$get_dbm_count_query = "
			SELECT
				COALESCE(SUM(r.Impressions), 0) AS imps,
				COALESCE(SUM(r.Clicks), 0) AS clks
			FROM report_ad_size_records AS r
			JOIN AdGroups AS adg
				ON (r.AdGroupID = adg.ID)
			WHERE r.Date = ? AND adg.Source = \"".$ad_source."\"";
		$agg_dbm_size_count_result = $this->db->query($get_dbm_count_query, $date);
		if($agg_dbm_size_count_result == false)
		{
			return false; 
		}		

		$get_dbm_count_query = "
			SELECT
				COALESCE(SUM(r.Impressions), 0) AS imps,
				COALESCE(SUM(r.Clicks), 0) AS clks
			FROM report_creative_records AS r
			JOIN AdGroups AS adg
				ON (r.adgroup_id = adg.ID)
			WHERE r.Date = ? AND adg.Source = \"".$ad_source."\"";
		$agg_dbm_creative_count_result = $this->db->query($get_dbm_count_query, $date);
		if($agg_dbm_creative_count_result == false)
		{
			return false; 
		}

		if($uploader_type != self::trafficing_system_dfp)
		{
			$get_dbm_count_query = "
				SELECT
					COALESCE(SUM(r.start_count), 0) AS starts
				FROM report_video_play_records AS r
				JOIN AdGroups AS adg
					ON (r.AdGroupID = adg.ID)
				WHERE r.Date = ? AND adg.Source = \"DBM\"";
			$agg_dbm_video_count_result = $this->db->query($get_dbm_count_query, $date);
			if($agg_dbm_video_count_result == false)
			{
				return false; 
			}
		}
		$return_array['site_data'] = $agg_dbm_site_count_result->row_array();
		$return_array['city_data'] = $agg_dbm_city_count_result->row_array();
		$return_array['size_data'] = $agg_dbm_size_count_result->row_array();
		$return_array['creative_data'] = $agg_dbm_creative_count_result->row_array();
		if($uploader_type != self::trafficing_system_dfp)
		{
			$return_array['video_data'] = $agg_dbm_video_count_result->row_array();
		}	
		return $return_array;
	}
	
	/*
	 * Get Brighthuose data from the Adgroup 
	 */
	public function get_frequence_bright_house_adgroups_data()
	{
		$query = "
			SELECT
				DISTINCT(ID) AS adgroup_id
			FROM 
				AdGroups AS r
			WHERE 
				Source = \"DFPBH\"";
		$result = $this->db->query($query);
		$bh_adgroup_ids = $result->result_array();
		$bh_adgroup_ids = array_column($bh_adgroup_ids, 'adgroup_id');
		return implode(',',$bh_adgroup_ids);
	}
	
	public function map_url_site_data()
	{
		$query = "SELECT * FROM report_dfp_ad_unit_name_to_url";
		$result = $this->db->query($query);
		$result = $result->result_array($result);
		$map_url_array = array();
		foreach($result as $row)
		{
			$map_url_array[$row['ad_unit_name']] = $row['site_url'];
		}
		return $map_url_array;
	}
	
	
}

?>
