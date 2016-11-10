<?php
class Spectrum_tv_model extends CI_Model
{
	static $PROCESSING = 'processing';
	static $COMPLETE = 'complete';
	static $INCOMPLETE = 'incomplete';
	static $NO_SOURCE_VIDEO = 'no_source_video';
	static $THIRD_PARTY_SOURCE_ID = '3';
	static $INCOMPATIBLE_SOURCE_VIDEO = 'incompatible_source_video';

	public function __construct()
	{
		$this->load->database();
		$this->load->library('vl_aws_services');
		$this->load->helper(array(
			'spectrum_traffic_system_helper'
		));

		require_once('misc_external/uploader_constants.php');
	}

	public function upload_tv_creatives_for_date($report_date = '-1 day')
	{
		$report_date_string = (new DateTime($report_date))->format('Ymd');
		$report_date_time_string = $report_date_string . "_" . (new DateTime())->format('His');
		$return_array = array();
		$return_array['is_success'] = true;
		$return_array['err_msg'] = "";
		$return_array['spectrum_tv_schedule_rows_inserted'] = 0;
		$return_array['spectrum_tv_creatives_rows_inserted'] = 0;
		$return_array['spectrum_historic_zones_inserted'] = 0;
		$return_array['bad_syscode_strings'] = array();
		$live_tv_schedule_table_name_string = "tp_spectrum_tv_schedule";
		$new_tv_schedule_table_name_string = $live_tv_schedule_table_name_string."_new_$report_date_time_string";
		$archive_tv_schedule_table_name_string = $live_tv_schedule_table_name_string."_archive_$report_date_time_string";

		$video_creatives_table_name_string = "tp_video_creatives";
		$live_tv_creatives_table_name_string = "tp_spectrum_tv_creatives";
		$new_tv_creatives_table_name_string = $live_tv_creatives_table_name_string."_new_$report_date_time_string";
		$archive_tv_creatives_table_name_string = $live_tv_creatives_table_name_string."_archive_$report_date_time_string";
		$previous_tv_schedules_table_name_string = "tp_spectrum_tv_previous_airing_data";

		// PART 1: Create new tables like old tables
		$create_new_tv_schedule_table_sql = "
			CREATE TABLE IF NOT EXISTS $new_tv_schedule_table_name_string
			LIKE $live_tv_schedule_table_name_string
		";
		$create_new_tv_schedule_table_result = $this->db->query($create_new_tv_schedule_table_sql);

		$create_new_tv_creatvies_table_sql = "
			CREATE TABLE IF NOT EXISTS $new_tv_creatives_table_name_string
			LIKE $live_tv_creatives_table_name_string
		";
		$create_new_tv_creatvies_table_result = $this->db->query($create_new_tv_creatvies_table_sql);

		//PART 2: POPULATE spectrum_tv_schedule table from CSV file from AWS. TODO** authentication check.
		$grab_timezone_query =
		"	SELECT
				id,
				utc_offset,
				utc_dst_offset
			FROM
				tp_spectrum_tv_zone_data";

		$zone_timezones_result = $this->db->query($grab_timezone_query);
		$zone_timezones = $zone_timezones_result->result_array();

		$date_for_timezone =  new DateTime(null, new DateTimeZone('America/New_York'));
		$dst_flag = $date_for_timezone->format('I');

		if($dst_flag)
		{
			$zone_timezones = array_column($zone_timezones, 'utc_dst_offset', 'id');
		}
		else
		{
			$zone_timezones = array_column($zone_timezones, 'utc_offset', 'id');
		}

		$ctr = 0;
		$upload_bindings = array();
		$insert_values = array();
		try
		{
			$url = 'spectrumreach-data/schedule/AirTimeData.'.$report_date_string.'.CSV';
			if($this->vl_aws_services->file_exists($url))
			{
				$file = fopen("s3://".$url, 'r');
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['err_msg'] = "File at ".$url." not found";
				return $return_array;
			}
		}
		catch(Exception $e)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = $e->getMessage();
			return $return_array;
		}
		if($file == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Failed to open file at ".$url;
			return $return_array;
		}
		$header = null;
/*
		CustomerNumber
		CustomerULID
		CopyId
		BookendTopID
		BookendBottomID
		CustomerName
		CopyTitle
		BookendTopTitle
		BookendBottomTitle
		CopyTitle2
		CopyLength
		Zone
		Syscode
		Network
		SpotDateTime
		Program
		RefreshTimeDate
		TrafficSystem
*/
		while(($row = fgetcsv($file, 0, "|")) != false)
		{
			if($header == null)
			{
				$header = $row;
			}
			else
			{
				foreach($row as &$value)
				{
					$value = trim($value);
				}

				$csv_data_row = array_combine($header, $row);
				//Adjustments

				$csv_data_row['CustomerULID'] = $this->append_traffic_system_to_ulid($csv_data_row['TrafficSystem'], $csv_data_row['CustomerULID']);

				if($csv_data_row['CopyID'] == "")
				{
					$csv_data_row['CopyID'] = null;
				}
				else if($csv_data_row['CopyID'] == "BOOKEND")
				{
					$csv_data_row['CopyID'] = null;
				}
				else
				{
					$csv_data_row['BookendTopID'] = null;
					$csv_data_row['BookendBottomID'] = null;
				}

				$csv_data_row['Syscode'] = ltrim($csv_data_row['Syscode'], '0');

				if(!empty($zone_timezones[$csv_data_row['Syscode']]))
				{
					$offset = $zone_timezones[$csv_data_row['Syscode']];
				}
				else
				{
					if($dst_flag)
					{
						$offset = "-04:00";
					}
					else
					{
						$offset = "-05:00";
					}
					if(!isset($return_array['bad_syscode_strings'][$csv_data_row['Syscode']]))
					{
						$return_array['bad_syscode_strings'][$csv_data_row['Syscode']] = $csv_data_row['Zone']."(".$csv_data_row['Syscode']." - ".$csv_data_row['TrafficSystem'].")";
					}
				}

				//Gotta reverse the - or + to get the time to match GMT
				if(strpos($offset, "-") !== false)
				{
					$offset = str_replace("-", "+", $offset);
				}
				else
				{
					$offset = "-".$offset;
				}

				$offset = substr($offset, 0, strpos($offset, ":"));

				$csv_data_row['SpotDateTime'] = date("Y-m-d H:i:s", strtotime($offset.' hours', strtotime($csv_data_row['SpotDateTime'])));
				$csv_data_row['Network'] = trim($csv_data_row['Network']);

				$csv_data_row['RefreshTimeDate'] = date("Y-m-d H:i:s", strtotime(substr($csv_data_row['RefreshTimeDate'], 0, strpos($csv_data_row['RefreshTimeDate'], '.'))));

				foreach($csv_data_row as $csv_data_element)
				{
					$upload_bindings[] = $csv_data_element;
				}
				$insert_values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
				$ctr++;
			}
		}

		$return_array['spectrum_tv_schedule_rows_inserted'] += $ctr;
		$insert_values = implode(', ', $insert_values);

		$insert_spectrum_new_tv_schedule = "
			INSERT INTO
				$new_tv_schedule_table_name_string
				(`client_traffic_id`, `account_ul_id`, `spot_id`, `bookend_top_id`, `bookend_bottom_id`, `client_name`, `spot_name`, `bookend_top_name`, `bookend_bottom_name`, `30s_sec_spot_name`, `duration`, `zone`, `zone_sys_code`, `network`, `air_time_date`, `program`, `refresh_time_date`, `traffic_system`)
			VALUES ".$insert_values."
			ON DUPLICATE KEY UPDATE
				client_traffic_id = VALUES(client_traffic_id),
				account_ul_id = VALUES(account_ul_id),
				spot_id = VALUES(spot_id),
				bookend_top_id = VALUES(bookend_top_id),
				bookend_bottom_id = VALUES(bookend_bottom_id),
				client_name = VALUES(client_name),
				spot_name = VALUES(spot_name),
				bookend_top_name = VALUES(bookend_top_name),
				bookend_bottom_name = VALUES(bookend_bottom_name),
				30s_sec_spot_name = VALUES(30s_sec_spot_name),
				duration = VALUES(duration),
				zone = VALUES(zone),
				zone_sys_code = VALUES(zone_sys_code),
				network = VALUES(network),
				air_time_date = VALUES(air_time_date),
				program = VALUES(program),
				refresh_time_date = VALUES(refresh_time_date),
				traffic_system = VALUES(traffic_system)
			";
		$insert_spectrum_new_tv_schedule_result = $this->db->query($insert_spectrum_new_tv_schedule, $upload_bindings);
		if($insert_spectrum_new_tv_schedule_result == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Failed to insert insert_spectrum_tv_schedule data into database";
			return $return_array;
		}

		$insert_spectrum_archive_tv_schedule = "
			INSERT IGNORE INTO $previous_tv_schedules_table_name_string
				(account_ul_id, zone_sys_code, date)
			(SELECT
				account_ul_id,
				zone_sys_code,
				DATE(air_time_date)
			FROM
				$new_tv_schedule_table_name_string
			GROUP BY
				account_ul_id, zone_sys_code, date(air_time_date))
			";
		$insert_spectrum_archive_tv_schedule_result = $this->db->query($insert_spectrum_archive_tv_schedule, $upload_bindings);
		if($insert_spectrum_archive_tv_schedule_result == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Failed to aggregate historic tv airing data";
			return $return_array;
		}

		$return_array['spectrum_historic_zones_inserted'] = $this->db->affected_rows();

		//PART 3: POPULATE spectrum_tv_creatives from spectrum_tv_schedule table
		$insert_spectrum_tv_creatives = "
			INSERT INTO $new_tv_creatives_table_name_string (id)
			SELECT DISTINCT
				main.spot_id
			FROM
				(
					SELECT
						spot_id
					FROM
						$new_tv_schedule_table_name_string
					UNION
					SELECT
						bookend_top_id
					FROM
						$new_tv_schedule_table_name_string
					UNION
					SELECT
						bookend_bottom_id
					FROM
						$new_tv_schedule_table_name_string) main
			WHERE
				spot_id != 'BOOKEND'
					AND spot_id IS NOT NULL
					AND spot_id != ''
		";

		$insert_spectrum_tv_creatives_result = $this->db->query($insert_spectrum_tv_creatives);

		if($insert_spectrum_tv_creatives_result == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Failed to insert spectrum_tv_creatives data into database";
			return $return_array;
		}

		//PART 4: Get count of records inserted in spectrum_tv_creatives
		$select_spectrum_tv_creatives = "
			SELECT COUNT(*) AS count
			FROM
				$new_tv_creatives_table_name_string
		";

		$select_spectrum_tv_creatives_result = $this->db->query($select_spectrum_tv_creatives);

		if ($select_spectrum_tv_creatives_result->num_rows()>0)
		{
			$data=$select_spectrum_tv_creatives_result->result_array();
			foreach ($data as $rows)
			{
				$return_array['spectrum_tv_creatives_rows_inserted']=$rows['count'];
				break;
			}
		}

		// PART 5: Populate new creatives with already processed videos
		$populate_new_tv_creatives_table_with_existing_video_assets_sql = "
			UPDATE $new_tv_creatives_table_name_string AS new
				JOIN $live_tv_creatives_table_name_string AS live
					ON (new.id = live.id)
			SET
				new.link_mp4 = live.link_mp4,
				new.link_webm = live.link_webm,
				new.link_thumb = live.link_thumb,
				new.status = live.status,
				new.date_modified = live.date_modified
		";

		if($this->db->_error_number() > 0)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Failed to update new tv creatives table with existing videos. (#7357)";
			return $return_array;
		}

		// PART 6: Map TV accounts to Advertisers
		if(!$this->map_tv_accounts_to_advertisers($new_tv_schedule_table_name_string, "client_name"))
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Failed to resolve TV accounts to Advertisers (#31238)";
			return $return_array;
		}

		//PART 7: POPULATE video_creatives from spectrum_tv_schedule table
		$insert_video_creatives = "
			INSERT INTO $video_creatives_table_name_string (
				frq_third_party_account_id,
				third_party_video_id,
				third_party_source_id,
				name,
				link_mp4,
				link_webm,
				link_thumb,
				status,
				last_active_date
			)
			SELECT
				tpaj.frq_third_party_account_id,
				stvc.id,
				" . self::$THIRD_PARTY_SOURCE_ID . ",
				stvs.my_spot_name,
				stvc.link_mp4,
				stvc.link_webm,
				stvc.link_thumb,
				stvc.status,
				stvs.air_time_date
			FROM
				$new_tv_creatives_table_name_string AS stvc
			LEFT JOIN (
				SELECT
					tvs_union.my_spot_id,
					tvs_union.my_spot_name,
					MAX(tvs_union.air_time_date) AS air_time_date,
					tvs_union.account_ul_id
				FROM
				(
					SELECT
						COALESCE(spot_id, bookend_top_id) AS my_spot_id,
						IF(spot_id IS NOT NULL, spot_name, IF(bookend_top_id = bookend_bottom_id, spot_name, CONCAT(spot_name, \" bookend top\"))) AS my_spot_name,
						air_time_date,
						account_ul_id
					FROM
						$new_tv_schedule_table_name_string AS tvs1
					UNION ALL
					SELECT
						bookend_bottom_id AS my_spot_id,
						CONCAT(spot_name, \" bookend bottom\") AS my_spot_name,
						air_time_date,
						account_ul_id
					FROM
						$new_tv_schedule_table_name_string AS tvs2
					WHERE bookend_bottom_id IS NOT NULL
				) as tvs_union
				GROUP BY tvs_union.my_spot_id, tvs_union.account_ul_id
			) AS stvs ON (stvc.id = stvs.my_spot_id)
			LEFT JOIN tp_spectrum_tv_accounts AS tva ON (stvs.account_ul_id = tva.account_id)
			LEFT JOIN tp_advertisers_join_third_party_account AS tpaj ON (tva.frq_id = tpaj.frq_third_party_account_id AND tpaj.third_party_source = " . self::$THIRD_PARTY_SOURCE_ID . ")
			ON DUPLICATE KEY UPDATE
				frq_third_party_account_id = VALUES(frq_third_party_account_id),
				last_active_date = VALUES(last_active_date)
		";

		$insert_video_creatives_result = $this->db->query($insert_video_creatives);

		if($insert_video_creatives_result == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Failed to insert video_creatives data into database";
			return $return_array;
		}

		// PART 8: Swap live tables to archive and new tables to be live.
		$populate_new_tv_creatives_table_with_existing_video_assets_result = $this->db->query($populate_new_tv_creatives_table_with_existing_video_assets_sql);

		$rename_current_to_archive_and_new_to_current_tv_tables_sql = "
			RENAME TABLE
				$live_tv_schedule_table_name_string TO $archive_tv_schedule_table_name_string,
				$new_tv_schedule_table_name_string TO $live_tv_schedule_table_name_string,
				$live_tv_creatives_table_name_string TO $archive_tv_creatives_table_name_string,
				$new_tv_creatives_table_name_string TO $live_tv_creatives_table_name_string
		";

		$rename_current_to_archive_and_new_to_current_tv_tables_result = $this->db->query($rename_current_to_archive_and_new_to_current_tv_tables_sql);
		if(!$rename_current_to_archive_and_new_to_current_tv_tables_result)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Failed to swap new tv schedule and tv creatives to be live.";
			return $return_array;
		}

		//PART 9: Delete old table since it's useless now

		$delete_archive_tables_query = "
			DROP TABLE
				$archive_tv_schedule_table_name_string,
				$archive_tv_creatives_table_name_string
		";
		$delete_archive_tables_result = $this->db->query($delete_archive_tables_query);
		if($delete_archive_tables_result == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Failed to remove old schedule/creative data (#57290)";
			return $return_array;
		}

		return $return_array;
	}

	private function map_tv_accounts_to_advertisers($new_tv_schedule_table_name_string, $account_name_field)
	{
		$insert_tv_accounts_sql = "
			INSERT tp_spectrum_tv_accounts
			(account_id, name)
			SELECT
				sa.ul_id,
				sts.client_name
			FROM
				(
					SELECT
						account_ul_id,
						$account_name_field AS client_name
					FROM
						$new_tv_schedule_table_name_string
					GROUP BY
						account_ul_id
				) AS sts
				JOIN tp_spectrum_accounts AS sa
					ON (sts.account_ul_id = sa.ul_id)
			ON DUPLICATE KEY UPDATE
				name = VALUES(name)
		";

		if(!$this->db->query($insert_tv_accounts_sql))
		{
			return false;
		}

		$insert_third_party_advertiser_relationships = "
			INSERT IGNORE tp_advertisers_join_third_party_account
			(frq_advertiser_id, frq_third_party_account_id, third_party_source)
			SELECT
				adv.id,
				sta.frq_id,
				" . self::$THIRD_PARTY_SOURCE_ID . "
			FROM
				Advertisers AS adv
				JOIN tp_spectrum_accounts AS sa
					ON (adv.id = sa.advertiser_id)
				JOIN tp_spectrum_tv_accounts AS sta
					ON (sa.ul_id = sta.account_id)
		";

		if(!$this->db->query($insert_third_party_advertiser_relationships))
		{
			return false;
		}

		return true;
	}

	/*
	 * @param indexed array $rows consisting of
	 *   string $id
	 *   string $third_party_video_id
	 *   string $link_mp4
	 *   string $link_webm
	 *   string $link_thumb
	 *   string $status
	 * @returns boolean success
	 */
	public function update_video_creatives($rows)
	{
		if(empty($rows))
		{
			return FALSE;
		}

		$update_tp_video_creatives_values_sql = '';
		$update_tp_video_creatives_values_bindings = [];
		foreach($rows as $row)
		{
			$video_creative_values = [
				$row['video_creative_id'],
				$row['link_mp4'],
				$row['link_webm'],
				$row['link_thumb'],
				$row['status'],
			];
			$this->add_values_and_bindings(
				$update_tp_video_creatives_values_sql,
				$update_tp_video_creatives_values_bindings,
				$video_creative_values
			);
		}

		$update_tp_video_creatives_sql = "
			INSERT tp_video_creatives (
				id,
				link_mp4,
				link_webm,
				link_thumb,
				status
			)
			VALUES
				$update_tp_video_creatives_values_sql
			ON DUPLICATE KEY UPDATE
				link_mp4 = VALUES(link_mp4),
				link_webm = VALUES(link_webm),
				link_thumb = VALUES(link_thumb),
				status = VALUES(status)
		";

		if(!$this->db->query($update_tp_video_creatives_sql, $update_tp_video_creatives_values_bindings))
		{
			return FALSE;
		}

		$update_tp_spectrum_tv_creatives_values_bindings = array_column($rows, 'third_party_video_id');
		$update_tp_spectrum_tv_creatives_values_sql = '(' . rtrim(str_repeat('?,', count($update_tp_spectrum_tv_creatives_values_bindings)), ',') . ')';

		$update_tp_spectrum_tv_creatives_sql = "
			INSERT tp_spectrum_tv_creatives (
				id,
				link_mp4,
				link_webm,
				link_thumb,
				status
			)
			SELECT tvc.third_party_video_id, tvc.link_mp4, tvc.link_webm, tvc.link_thumb, tvc.status
			FROM tp_video_creatives AS tvc
			JOIN tp_spectrum_tv_creatives AS stvc
				ON (tvc.third_party_video_id = stvc.id)
			WHERE
				tvc.third_party_source_id = " . self::$THIRD_PARTY_SOURCE_ID . "
			AND
				tvc.third_party_video_id IN $update_tp_spectrum_tv_creatives_values_sql
			ON DUPLICATE KEY UPDATE
				link_mp4 = VALUES(link_mp4),
				link_webm = VALUES(link_webm),
				link_thumb = VALUES(link_thumb),
				status = VALUES(status)
		";

		if(!$this->db->query($update_tp_spectrum_tv_creatives_sql, $update_tp_spectrum_tv_creatives_values_bindings))
		{
			return FALSE;
		}

		return TRUE;
	}

	/*
	 * @param boolean $status (optional, default false for ALL statuses)
	 * @returns Array creatives
	 */
	public function get_video_creative_ids_indexed_by_third_party_video_id($status = false)
	{
		$bindings = [self::$THIRD_PARTY_SOURCE_ID];

		$status_condition = "1";
		if($status === NULL)
		{
			$status_condition = "status IS NULL";
		}
		else if($status !== false)
		{
			$status_condition = "status=?";
			$bindings[] = $status;
		}

		$select_video_creatives = "
			SELECT
				id,
				third_party_video_id
			FROM
				tp_video_creatives
			WHERE
				third_party_source_id = ?
			AND
				$status_condition";

		$select_video_creatives_result = $this->db->query($select_video_creatives, $bindings);

		$video_creative_ids_indexed_by_third_party_video_id = [];

		$result = $select_video_creatives_result->result_array();

		if(!empty($result))
		{
			foreach($result as $row)
			{
				$video_creative_ids_indexed_by_third_party_video_id[$row['third_party_video_id']] = $row['id'];
			}
		}

		return $video_creative_ids_indexed_by_third_party_video_id;
	}

	/*
	 * returns the next spot without a status, and sets its status to 'processing'
	 */
	public function get_next_video_creative_to_be_processed($status = NULL)
	{
		$result = false;

		$bindings = [self::$THIRD_PARTY_SOURCE_ID];

		$select_video_creatives_sql_where = 'third_party_source_id = ?';

		if($status === NULL)
		{
			$select_video_creatives_sql_where .= "
					AND status IS NULL";
		}
		else
		{
			$select_video_creatives_sql_where .= "
					AND status=?";
			$bindings[] = $status;
		}

		$select_video_creatives = "
			SELECT
				*
			FROM
				tp_video_creatives
			WHERE
				$select_video_creatives_sql_where
			ORDER BY
				id DESC
			LIMIT 1
		";

		$select_video_creatives_result = $this->db->query($select_video_creatives, $bindings);

		if ($select_video_creatives_result->num_rows()>0)
		{
			$result_rows = $select_video_creatives_result->result_array();
			$next_spot = $result_rows[0];

			if($this->update_creative_status($next_spot['id'], $next_spot['third_party_video_id'], $next_spot['third_party_source_id'], self::$PROCESSING))
			{
				$next_spot['status'] = self::$PROCESSING;
				$result = $next_spot;
			}
		}

		return $result;
	}

	public function update_creative_status($video_creative_id, $third_party_video_id, $third_party_source_id, $status)
	{
		$bindings = [];
		$update_video_creatives_status = "
			UPDATE
				tp_video_creatives
			SET
				status=?
			WHERE
				id=?
			AND
				third_party_source_id=?
		";

		$bindings = array($status, $video_creative_id, $third_party_source_id);

		if(!$this->db->query($update_video_creatives_status, $bindings))
		{
			return FALSE;
		}

		$update_spectrum_tv_creatives_status = "
			UPDATE
				tp_spectrum_tv_creatives
			SET
				status=?
			WHERE
				id=?
		";

		$bindings = array($status, $third_party_video_id);

		return $this->db->query($update_spectrum_tv_creatives_status, $bindings);
	}

	/**
	 * @param $statuses_to_update array
	 * @param $new_status string
	 * @param $third_party_source_id int
	 * @return mixed: number of rows updated in video_creatives, or boolean false on failure
	 */
	public function bulk_update_creative_status($statuses_to_update, $new_status, $third_party_source_id)
	{
		$stvc_bindings = array_merge([$new_status], $statuses_to_update);
		$tvc_bindings = array_merge($stvc_bindings, [$third_party_source_id]);

		$statuses_to_update_sql = '(' . implode(',', array_fill(0, count($statuses_to_update), '?')) . ')';

		$bulk_update_video_creatives_status_sql = "
			UPDATE
				tp_video_creatives
			SET
				status = ?
			WHERE
				status IN $statuses_to_update_sql
			AND
				third_party_source_id = ?
		";

		if($this->db->query($bulk_update_video_creatives_status_sql, $tvc_bindings))
		{
			$number_of_video_creatives_affected = $this->db->affected_rows();
			$statuses_to_update_sql = '(' . implode(',', array_fill(0, count($statuses_to_update), '?')) . ')';

			$bulk_update_spectrum_tv_creatives_status_sql = "
				UPDATE
					tp_spectrum_tv_creatives
				SET
					status = ?
				WHERE
					status IN $statuses_to_update_sql
			";

			if($this->db->query($bulk_update_spectrum_tv_creatives_status_sql, $stvc_bindings))
			{
				return $number_of_video_creatives_affected;
			}
		}

		return false;
	}

	private function add_values_and_bindings(&$values_sql, &$accumulated_bindings, $new_bindings)
	{
		if(!empty($values_sql))
		{
			$values_sql .= ',';
		};
		$values_sql .= '('.rtrim(str_repeat('?,', count($new_bindings)), ',').')';
		// For speed, and since we don't care about the keys, just the order, avoid `array_merge` or `+`
		foreach($new_bindings as $binding)
		{
			$accumulated_bindings[] = $binding;
		}
	}

	public function upload_v2_verified_data_for_date($report_date = '-1 day')
	{
		$report_date_string = (new DateTime($report_date))->format('Ymd');

		$return_array = array();
		$return_array['is_success'] = true;
		$return_array['err_msg'] = "";
		$return_array['spectrum_tv_verified_rows_inserted'] = 0;

		$table_in_which_to_insert = $this->create_temporary_table_for_verify_v2_data($report_date_string);
		if(!$table_in_which_to_insert)
		{
			return false; // Table unable to be created
		}

		try
		{
			$file = array(
				'bucket' => 'spectrumreach-data',
				'name' => "schedule/VerifData.{$report_date_string}V2.CSV"
			);
			$url = "{$file['bucket']}/{$file['name']}";
			if($this->vl_aws_services->file_exists($url))
			{
				$save_path = "/tmp/verif_data_file{$report_date_string}.csv";
				if(file_exists($save_path))
				{
					echo "No need to download file!\n";
				}
				else
				{
					echo "Downloading file from S3...";
					$this->benchmark->mark('download_file');
					$this->vl_aws_services->save_file_to_path($file['bucket'], $file['name'], $save_path);
					$this->benchmark->mark('download_file_end');
					$total_time = round(floatval($this->benchmark->elapsed_time('download_file', 'download_file_end')), 1);
					echo "File downloaded from S3 ({$total_time}s)...\n";
				}

				if(file_exists($save_path))
				{

					echo "Loading file into database...";
					$this->benchmark->mark('load_file');

					if($this->load_data_file_for_v2_data($save_path, $table_in_which_to_insert))
					{
						unlink($save_path);
						$this->benchmark->mark('load_file_end');
						$total_time = round(floatval($this->benchmark->elapsed_time('load_file', 'load_file_end')), 1);
						echo "File Loaded ({$total_time}s)\n";

						echo "Aggregating data... ";
						$this->benchmark->mark('aggregate_data');
						$affected_rows_count = $this->load_v2_data_into_tv_impressions_tables($table_in_which_to_insert);
						if($affected_rows_count !== false)
						{
							$this->benchmark->mark('aggregate_data_end');
							$total_time = round(floatval($this->benchmark->elapsed_time('aggregate_data', 'aggregate_data_end')), 1);
							echo "Data aggregated ({$total_time}s)\n";

							echo "Inserting non-existent creatives if needed...";
							$this->benchmark->mark('add_creatives');
							$this->insert_missing_creative_ids($table_in_which_to_insert);
							$this->benchmark->mark('add_creatives_end');
							$total_time = round(floatval($this->benchmark->elapsed_time('add_creatives', 'add_creatives_end')), 1);
							echo "Creatives handled ({$total_time}s)...\n";

							echo "Populating legacy TV data table... ";
							$this->benchmark->mark('populate_legacy_data');
							$legacy_rows_count = $this->load_v2_data_into_legacy_table($table_in_which_to_insert);
							if($legacy_rows_count !== false)
							{
								$this->benchmark->mark('populate_legacy_data_end');
								$total_time = round(floatval($this->benchmark->elapsed_time('populate_legacy_data', 'populate_legacy_data_end')), 1);
								echo "Data populated ({$total_time}s)\n";

								$return_array['spectrum_tv_verified_temp_rows_inserted'] = $this->db->count_all($table_in_which_to_insert);
								$return_array['spectrum_tv_verified_rows_inserted'] = $affected_rows_count;

								$this->db->query("DROP TABLE $table_in_which_to_insert");
							}
							else
							{
								$return_array['is_success'] = false;
								$return_array['err_msg'] = "Data unable to be aggregated into TV tables";
								return $return_array;
							}
						}
						else
						{
							$return_array['is_success'] = false;
							$return_array['err_msg'] = "Data unable to be aggregated into TV tables";
							return $return_array;
						}
					}
					else
					{
						$return_array['is_success'] = false;
						$return_array['err_msg'] = "File unable to be loaded into the database";
						return $return_array;
					}
				}
				else
				{
						$return_array['is_success'] = false;
						$return_array['err_msg'] = "Failed to download the file at {$url}";
						return $return_array;
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['err_msg'] = "File at {$url} not found";
				return $return_array;
			}
		}
		catch(Exception $e)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = $e->getMessage();
			return $return_array;
		}



		return $return_array;
	}

	private function insert_missing_creative_ids($temp_table)
	{
		$load_creative_tables_sql =
		"	INSERT IGNORE INTO tp_spectrum_tv_creatives
				(`id`)
			SELECT DISTINCT
				copy_id
			FROM
				{$temp_table}
			UNION
			SELECT DISTINCT
				bookend_top_id
			FROM
				{$temp_table}
			UNION
			SELECT DISTINCT
				bookend_bottom_id
			FROM
				{$temp_table}
		";
		$result = $this->db->query($load_creative_tables_sql);
	}

	private function load_v2_data_into_legacy_table($temp_table)
	{
		$traffic_system_case_sql = get_sql_case_mapping_spectrum_traffic_system_name_to_id('traffic_system');
		$load_legacy_table_sql =
		"	INSERT INTO tp_spectrum_tv_verified_data
				(`verified_date`, `account_ul_id`, `client_traffic_id`, `customer_name`, `network`, `spot_count`, `traffic_system`)
			SELECT
				DATE(spot_datetime) AS verified_date,
				CONCAT((
					$traffic_system_case_sql
					), customer_ul_id
				) AS account_ul_id,
				customer_number,
				customer_name,
				network,
				SUM(1) AS spot_count,
				traffic_system
			FROM
				{$temp_table}
			GROUP BY
				DATE(spot_datetime),
				account_ul_id,
				customer_number,
				network
			ON DUPLICATE KEY UPDATE
				spot_count = VALUES(spot_count);
		";
		$result = $this->db->query($load_legacy_table_sql);
		$affected_rows_count = $this->db->affected_rows();

		if($result)
		{
			if($this->map_tv_accounts_to_advertisers("tp_spectrum_tv_verified_data", "customer_name"))
			{
				return $affected_rows_count;
			}
		}
		return false;
	}

	private function load_v2_data_into_tv_impressions_tables($temp_table)
	{
		$creative_table = 'tp_spectrum_tv_impressions_by_creative';
		$network_table = 'tp_spectrum_tv_impressions_by_network_program';
		$syscode_table = 'tp_spectrum_tv_impressions_by_syscode';
		$traffic_system_case_sql = get_sql_case_mapping_spectrum_traffic_system_name_to_id('traffic_system');
		$aggregate_creative_impressions_and_apply_neilson_sql =
		"	INSERT INTO {$creative_table}
				(date, `account_ul_id`, `spot_id`, `bookend_top_id`, `bookend_bottom_id`, `spot_name`, `bookend_top_name`, `bookend_bottom_name`, `estimated_impressions`, spot_count)
			SELECT
				DATE(spot_datetime),
				CONCAT((
					$traffic_system_case_sql
					), customer_ul_id
				) AS account_ul_id,
				copy_id AS spot_id,
				bookend_top_id,
				bookend_bottom_id,
				copy_title,
				bookend_top_title,
				bookend_bottom_title,
				0, #placeholder for impressions
				#CONCAT(DATE(`spot_datetime`), ' ', sec_to_time(time_to_sec(spot_datetime) - time_to_sec(spot_datetime) % (15 * 60))) AS nielson_time_increment,
				SUM(1) AS total_spots
			FROM
				{$temp_table}
			GROUP BY
				DATE(spot_datetime),
				account_ul_id,
				spot_id,
				bookend_top_id,
				bookend_bottom_id
			ON DUPLICATE KEY UPDATE
				estimated_impressions = VALUES(estimated_impressions),
				spot_count = VALUES(spot_count)
		";
		$aggregate_network_impressions_and_apply_neilson_sql =
		"	INSERT INTO {$network_table}
				(date, `account_ul_id`, `network`, `program`, `estimated_impressions`, `spot_count`)
			SELECT
				DATE(spot_datetime),
				CONCAT((
					$traffic_system_case_sql
					), customer_ul_id
				) AS account_ul_id,
				network,
				program,
				0, #placeholder for impressions
				#CONCAT(DATE(`spot_datetime`), ' ', sec_to_time(time_to_sec(spot_datetime) - time_to_sec(spot_datetime) % (15 * 60))) AS nielson_time_increment,
				SUM(1) AS total_spots
			FROM
				{$temp_table}
			GROUP BY
				DATE(spot_datetime),
				account_ul_id,
				network,
				program
			ON DUPLICATE KEY UPDATE
				estimated_impressions = VALUES(estimated_impressions),
				spot_count = VALUES(spot_count)
		";
		$aggregate_syscode_impressions_and_apply_neilson_sql =
		"	INSERT INTO {$syscode_table}
				(date, `account_ul_id`, `zone`, `syscode`, `estimated_impressions`, `spot_count`)
			SELECT
				DATE(spot_datetime),
				CONCAT((
					$traffic_system_case_sql
					), customer_ul_id
				) AS account_ul_id,
				zone,
				syscode,
				0, #placeholder for impressions
				#CONCAT(DATE(`spot_datetime`), ' ', sec_to_time(time_to_sec(spot_datetime) - time_to_sec(spot_datetime) % (15 * 60))) AS nielson_time_increment,
				SUM(1) AS total_spots
			FROM
				{$temp_table}
			GROUP BY
				DATE(spot_datetime),
				account_ul_id,
				zone,
				syscode
			ON DUPLICATE KEY UPDATE
				estimated_impressions = VALUES(estimated_impressions),
				spot_count = VALUES(spot_count)
		";

		$affected_rows_count = 0;
		$this->db->trans_start();
		$creative_result = $this->db->query($aggregate_creative_impressions_and_apply_neilson_sql);
		$affected_rows_count += $this->db->affected_rows();
		$network_result = $this->db->query($aggregate_network_impressions_and_apply_neilson_sql);
		$affected_rows_count += $this->db->affected_rows();
		$syscode_result = $this->db->query($aggregate_syscode_impressions_and_apply_neilson_sql);
		$affected_rows_count += $this->db->affected_rows();
		$this->db->trans_complete();

		if(!$creative_result || !$network_result || !$syscode_result || $this->db->trans_status() === false)
		{
			return false;
		}
		return $affected_rows_count;
	}

	/**
	 * Creates the temporary table used to store version 2 verify
	 * data until it can be inserted into the original table
	 * @param String $date_string The string for the date of table data
	 * @return String The name of the table created
	 */
	private function create_temporary_table_for_verify_v2_data($date_string)
	{
		$db_raw = $this->load->database(raw_db_groupname, true);
		$table_name = "{$db_raw->database}.tp_spectrum_tv_spots_{$date_string}";
		$drop_query = "DROP TABLE IF EXISTS {$table_name};";
		$create_query =
		"	CREATE TABLE {$table_name} (
				`customer_ul_id` int(11) unsigned DEFAULT NULL,
				`customer_number` varchar(12) DEFAULT NULL,
				`copy_id` varchar(12) DEFAULT NULL,
				`bookend_top_id` varchar(12) DEFAULT NULL,
				`bookend_bottom_id` varchar(12) DEFAULT NULL,
				`customer_name` varchar(200) DEFAULT NULL,
				`copy_title` varchar(200) DEFAULT NULL,
				`bookend_top_title` varchar(200) DEFAULT NULL,
				`bookend_bottom_title` varchar(200) DEFAULT NULL,
				`copy_title_2` varchar(200) DEFAULT NULL,
				`copy_length` int(11) DEFAULT NULL,
				`zone` varchar(4) DEFAULT NULL,
				`syscode` varchar(4) DEFAULT NULL,
				`network` varchar(4) DEFAULT NULL,
				`spot_datetime` datetime DEFAULT '0000-00-00 00:00:00',
				`program` varchar(200) DEFAULT NULL,
				`traffic_system` varchar(100) DEFAULT NULL,
				KEY `network` (`network`,`syscode`,`customer_ul_id`,`spot_datetime`),
				KEY `network_2` (`network`,`syscode`,`copy_id`,`bookend_top_id`,`bookend_bottom_id`,`spot_datetime`),
				KEY `copy_id` (`copy_id`,`bookend_top_id`,`bookend_bottom_id`,`customer_ul_id`,`spot_datetime`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;
		";
		$this->db->trans_start();
		$drop_result = $this->db->query($drop_query);
		$create_result = $this->db->query($create_query);
		$this->db->trans_complete();

		if($drop_result && $create_result && $this->db->trans_status() === false)
		{
			return false;
		}
		return $table_name;
	}

	/**
	 * Loads v2 data from supplied file by downloading it to the
	 * environment and using mysql LOAD DATA to quickly load to
	 * supplied table name
	 * @param String $file_path The file to be uploaded to mysql
	 * @param String $table_in_which_to_insert the name of the table
	 * @return Boolean Returns success of the data load
	 */
	private function load_data_file_for_v2_data($file_path, $table_in_which_to_insert)
	{
		$query =
		"	LOAD DATA LOCAL INFILE '{$file_path}'
			INTO TABLE {$table_in_which_to_insert}
			FIELDS TERMINATED BY '|'
			LINES TERMINATED BY '\r\n'
			IGNORE 1 LINES
			(
				@CUSTOMERNUMBER,
				@CUSTOMERULID,
				@COPYID,
				@BOOKENDTOPID,
				@BOOKENDBOTTOMID,
				@CUSTOMERNAME,
				@COPYTITLE,
				@BOOKENDTOPTITLE,
				@BOOKENDBOTTOMTITLE,
				@COPYTITLE2,
				@COPYLENGTH,
				@ZONE,
				@SYSCODE,
				@NETWORK,
				@SPOTDATETIME,
				@PROGRAM,
				@TRAFFICSYSTEM
			)
		SET
			customer_number = TRIM(@CUSTOMERNUMBER),
			customer_ul_id = TRIM(@CUSTOMERULID),
			copy_id = IF(TRIM(@COPYID) = 'BOOKEND', NULL, TRIM(@COPYID)),
			bookend_top_id = IF(TRIM(@BOOKENDTOPID) = '', NULL, TRIM(@BOOKENDTOPID)),
			bookend_bottom_id = IF(TRIM(@BOOKENDBOTTOMID) = '', NULL, TRIM(@BOOKENDBOTTOMID)),
			customer_name = TRIM(@CUSTOMERNAME),
			copy_title = IF(TRIM(@COPYID) = 'BOOKEND', NULL, TRIM(@COPYTITLE)),
			bookend_top_title = IF(TRIM(@BOOKENDTOPTITLE) = '', NULL, TRIM(@BOOKENDTOPTITLE)),
			bookend_bottom_title = IF(TRIM(@BOOKENDBOTTOMTITLE) = '', NULL, TRIM(@BOOKENDBOTTOMTITLE)),
			copy_title_2 = IF(TRIM(@COPYID) = 'BOOKEND', NULL, TRIM(@COPYTITLE2)),
			copy_length = TRIM(@COPYLENGTH),
			zone = TRIM(@ZONE),
			syscode = TRIM(@SYSCODE),
			network = TRIM(@NETWORK),
			spot_datetime = STR_TO_DATE(TRIM(@SPOTDATETIME), '%m/%d/%Y %H:%i:%s'),
			program = TRIM(@PROGRAM),
			traffic_system = TRIM(@TRAFFICSYSTEM)
		";

		$escaped_query = escapeshellarg($query);

		$return_var = "";
		$system_result = system("mysql -u ".TD_DB_USERNAME." --password='".TD_DB_PASSWORD."' -h ".TD_DB_HOSTNAME." --local_infile=1 -e $escaped_query ".TD_DB_DATABASE, $return_var);
		if($return_var != 0 || $system_result === false)
		{
			return false;
		}
		return true;
	}

	public function upload_verified_data_for_date($report_date = '-1 day')
	{
		$report_date_string = (new DateTime($report_date))->format('Ymd');
		$report_date_time_string = $report_date_string . "_" . (new DateTime())->format('His');
		$return_array = array();
		$return_array['is_success'] = true;
		$return_array['err_msg'] = "";
		$return_array['spectrum_tv_verified_rows_inserted'] = 0;
		$return_array['spectrum_tv_verified_spots_inserted'] = 0;

		$ctr = 0;
		try
		{
			$url = 'spectrumreach-data/schedule/VerifData.'.$report_date_string.'.CSV';
			if($this->vl_aws_services->file_exists($url))
			{
				$file = fopen("s3://".$url, 'r');
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['err_msg'] = "File at ".$url." not found";
				return $return_array;
			}
		}
		catch(Exception $e)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = $e->getMessage();
			return $return_array;
		}
		if($file === false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Failed to open file at {$url}";
			return $return_array;
		}
		$header = null;
/*
	Expected Headers:
	VerifiedDate
	CustomerULID
	CustomerNumber
	CustomerName
	Network
	SpotCount
	TrafficSystem
*/
		while(($row = fgetcsv($file, 0, "|")) != false)
		{
			if($header == null)
			{
				$header = $row;
			}
			else
			{
				foreach($row as &$value)
				{
					$value = trim($value);
				}

				$csv_data_row = array_combine($header, $row);
				$csv_data_row['VerifiedDate'] = substr($csv_data_row['VerifiedDate'], 0, strpos($csv_data_row['VerifiedDate'], ' '));

				$csv_data_row['CustomerULID'] = $this->append_traffic_system_to_ulid($csv_data_row['TrafficSystem'], $csv_data_row['CustomerULID']);

				$return_array['spectrum_tv_verified_spots_inserted'] += $csv_data_row['SpotCount'];

				foreach($csv_data_row as $csv_data_element)
				{
					$upload_bindings[] = $csv_data_element;
				}
				$insert_values[] = "(?, ?, ?, ?, ?, ?, ?)";
				$ctr++;
			}
		}

		$return_array['spectrum_tv_verified_rows_inserted'] += $ctr;
		$insert_values = implode(', ', $insert_values);
		if(!empty($insert_values))
		{
			$insert_verified_spots_query = "
				INSERT INTO
					tp_spectrum_tv_verified_data
					(`verified_date`, `account_ul_id`, `client_traffic_id`, `customer_name`, `network`, `spot_count`, `traffic_system`)
				VALUES ".$insert_values."
				ON DUPLICATE KEY UPDATE
					spot_count = VALUES(spot_count)
					";
			$insert_verified_spots_result = $this->db->query($insert_verified_spots_query, $upload_bindings);
			if($insert_verified_spots_result == false)
			{
				$return_array['is_success'] = false;
				$return_array['err_msg'] = "Failed to insert verified data (#11922)";
				return $return_array;
			}

			if(!$this->map_tv_accounts_to_advertisers("tp_spectrum_tv_verified_data", "customer_name"))
			{
				$return_array['is_success'] = false;
				$return_array['err_msg'] = "Failed to resolve TV accounts to Advertisers (#31238)";
				return $return_array;
			}
		}
		return $return_array;
	}

	private function append_traffic_system_to_ulid($traffic_system_string, $ul_id)
	{
		switch($traffic_system_string)
		{
			case spectrum_traffic_system_names::southeast:
				$traffic_system_id = spectrum_traffic_system_ids::southeast;
				break;
			case spectrum_traffic_system_names::mid_north:
				$traffic_system_id = spectrum_traffic_system_ids::mid_north;
				break;
			case spectrum_traffic_system_names::central_pacific:
				$traffic_system_id = spectrum_traffic_system_ids::central_pacific;
				break;
			default:
				$traffic_system_id = spectrum_traffic_system_ids::unknown;
				break;
		}
		return $traffic_system_id.$ul_id;
	}

	public function get_account_names_with_no_account()
	{
		$get_orphaned_tv_data_query =
		"SELECT
			sts.client_name AS account_name,
			sts.account_ul_id AS ul_id,
			sts.traffic_system AS traffic_system
		FROM
			tp_spectrum_tv_schedule AS sts
			LEFT JOIN tp_spectrum_tv_accounts AS sta
				ON (sts.account_ul_id = sta.account_id)
		WHERE
			sta.account_id IS NULL
		GROUP BY
			ul_id, client_name;
		";

		$get_orphaned_tv_data_result = $this->db->query($get_orphaned_tv_data_query);
		if($get_orphaned_tv_data_query == false)
		{
			return false;
		}
		return $get_orphaned_tv_data_result->result_array();
	}

}
