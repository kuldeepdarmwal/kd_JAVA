<?php

class Al_model extends CI_Model {

	public function __construct()
	{
		$this->load->database();
		$this->load->model('td_uploader_model');
	}

	public function get_adv_details($adv_id)
	{
		$sql = "SELECT * FROM Advertisers WHERE id = ?";
		$query = $this->db->query($sql, $adv_id);
		if($query->num_rows() > 0)
		{
			return $query->row_array();
		}
		return null;
	}

	public function get_advertisers_for_select2($search_term, $start, $limit)
	{
		$bindings = array();
		$bindings[] = $search_term;
		$bindings[] = $search_term;
		$bindings[] = $search_term;
		$bindings[] = $search_term;
		$bindings[] = $search_term;
		$bindings[] = $start;
		$bindings[] = $limit;

		$query =
		"SELECT
			adv.id AS id,
			adv.Name AS text,
			adv.ttd_adv_id AS ttd_adv_id,
			tsa.ul_id AS ul_id,
			tsa.eclipse_id AS eclipse_id,
			GROUP_CONCAT(tmpi_join.account_id SEPARATOR ', ') AS tmpi_ids
		FROM
			Advertisers AS adv
			LEFT JOIN tp_spectrum_accounts AS tsa
				ON adv.id = tsa.advertiser_id
			LEFT JOIN
			(SELECT
				tajtpa.frq_advertiser_id AS frq_advertiser_id,
				tta.account_id AS account_id
			FROM
				tp_advertisers_join_third_party_account AS tajtpa
				JOIN tp_tmpi_accounts AS tta
					ON tajtpa.frq_third_party_account_id = tta.id
			WHERE
				tajtpa.third_party_source = 1
			)AS tmpi_join
				ON adv.id = tmpi_join.frq_advertiser_id
		WHERE
			adv.name LIKE ?
			OR adv.ttd_adv_id LIKE ?
			OR tsa.ul_id LIKE ?
			OR tsa.eclipse_id LIKE ?
			OR tmpi_join.account_id LIKE ?
		GROUP BY
			adv.id
		ORDER BY
			adv.Name ASC
		LIMIT ?, ?";
		$response = $this->db->query($query, $bindings);
		if($response->num_rows() > 0)
		{
			$advertiser_rows = $response->result_array();
			$result = array();
			foreach($advertiser_rows as $row)
			{
				$id_text = "fid: ".$row['id'];
				if(!empty($row['ttd_adv_id']))
				{
					$id_text .= " | ttdid: ".$row['ttd_adv_id'];
				}
				if(!empty($row['ul_id']))
				{
					$id_text .= " | ulid: ".$row['ul_id'];
				}
				if(!empty($row['eclipse_id']))
				{
					$id_text .= " | eclipseid: ".$row['eclipse_id'];
				}
				if(!empty($row['tmpi_ids']))
				{
					$id_text .= " | tmpi: ".$row['tmpi_ids'];
				}
				$result[] = array("id" => $row['id'], "text" => $row['text'], "id_list" => $id_text);
			}
			return $result;
		}
		return array();
	}


	public function get_tag_files_for_select2($search_term, $start, $limit, $campaign_id)
	{
		$bindings = array();
		$bindings[] = $search_term;
		$bindings[] = $campaign_id;
		$bindings[] = $start;
		$bindings[] = $limit;

		$query = "
				SELECT
					tf.id AS id,
					tf.name AS text
				FROM
					Campaigns AS cmp
					JOIN Advertisers AS adv
						ON (cmp.business_id = adv.id)
					JOIN tag_files AS tf
						ON (adv.id = tf.io_advertiser_id)
				WHERE
					tf.name LIKE ?
					AND cmp.id = ?
					AND tf.source_table = \"Advertisers\"
					AND tf.id NOT IN (
						SELECT
							DISTINCT tf.id
						FROM
							tag_files AS tf
							JOIN tag_codes AS tc
								ON (tf.id = tc.tag_file_id)
						WHERE
							tc.tag_type = 1
					)
				ORDER BY
					tf.name ASC
				LIMIT ?, ?";
		$response = $this->db->query($query, $bindings);
		if($response->num_rows() > 0)
		{
			return $response->result_array();
		}
		return array();
	}

	function get_campaign_details($c_id)
	{
			$sql = "SELECT
						 *
					FROM
						Campaigns
					WHERE
						id = ?";
			$query = $this->db->query($sql, $c_id);
			if($query->num_rows() > 0)
			{
				return $query->result_array();
			}
			return null;
	}
	
	function get_campaign_advertiser_details($c_id)
	{
		$sql = "
				SELECT
					adv.Name AS advertiser_name,
					cpm.*
				FROM
					Campaigns AS cpm
					JOIN Advertisers AS adv
						ON cpm.business_id = adv.id		
				WHERE
					cpm.id = ?
			";
		$query = $this->db->query($sql, $c_id);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return null;
	}

	function get_campaign_timeseries_details($c_id)
	{
			$sql = "
				SELECT
					id,
					campaigns_id,
					impressions ,
					DATE_FORMAT(series_date, '%m/%d/%Y') AS series_date
				FROM
					campaigns_time_series
				WHERE
					campaigns_id = ?
					AND object_type_id=0";
			$query = $this->db->query($sql, $c_id);
			if($query->num_rows() > 0)
			{
				return $query->result_array();
			}
			return null;
	}

	public function get_first_flight_campaign_timeseries_details($c_id)
	{
			$sql = "
				SELECT
					COALESCE(ifsd.audience_ext_impressions,0) + COALESCE(ifsd.geofencing_impressions, 0) AS impressions,
					cts.series_date AS start_date,
					DATE_SUB(cts.series_date, INTERVAL 1 DAY) end_date
				FROM
					campaigns_time_series AS cts
					JOIN io_flight_subproduct_details AS ifsd
						ON (cts.id = ifsd.io_timeseries_id)
				WHERE
					campaigns_id = ?
					AND object_type_id=0
				ORDER BY
					series_date
				LIMIT 2";
			$query = $this->db->query($sql, $c_id);
			$return_arr=array();
			if($query->num_rows() > 0)
			{   $ctr=0;
				foreach ($query->result_array() as $row) {
					 if ($ctr == 0)
					 {
					 	$return_arr['first_flight_start_date']=$row['start_date'];
					 	$return_arr['first_flight_impressions']=$row['impressions'];
					 }
					 else
					 	$return_arr['first_flight_end_date']=$row['end_date'];

					$ctr++;
				}
				return $return_arr;
			}
			return null;
	}

	public function get_managed_impression_totals_for_campaign($campaign_id)
	{
		$get_audience_ext_total_query =
		"SELECT
			COALESCE(SUM(ifsd.audience_ext_impressions), 0) + COALESCE(SUM(ifsd.geofencing_impressions), 0) AS total_impressions
		FROM
			campaigns_time_series AS cts
			JOIN io_flight_subproduct_details AS ifsd
				ON (cts.id = ifsd.io_timeseries_id)
		WHERE
			cts.campaigns_id = ?
			AND object_type_id = 0
		GROUP BY 
			cts.campaigns_id
		";

		$get_audience_ext_total_result = $this->db->query($get_audience_ext_total_query, $campaign_id);
		if($get_audience_ext_total_result == false || $get_audience_ext_total_result->num_rows() < 1)
		{
			return false;
		}

		$row = $get_audience_ext_total_result->row_array();
		return $row['total_impressions'];

	}

	public function get_adgroup_details($ag_id)
	{
		$sql = "SELECT * FROM AdGroups WHERE vl_id = ?";
		$query = $this->db->query($sql, $ag_id);
		if($query->num_rows() > 0)
		{
			return $query->result_array()[0];
		}
		return null;
	}

	public function get_adgroup_geofencing_data($adgroup_id, $with_adgroup_center_id = false)
	{
		$adgroup_center_sql = '';
		if($with_adgroup_center_id)
		{
			$adgroup_center_sql = ', id AS id';
		}

		$sql =
		"	SELECT
				Y(center_point) AS latitude,
				X(center_point) AS longitude,
				radius AS radius,
				name AS name,
				address AS address
				{$adgroup_center_sql}
			FROM
				geofence_adgroup_centers
			WHERE
				adgroup_vl_id = $adgroup_id
		";
		$result = $this->db->query($sql, $adgroup_id);
		if($result->num_rows() > 0)
		{
			return $result->result_array();
		}
		return array();
	}

	function create_advertiser($insert_array)
	{
		$sql = 	"INSERT IGNORE INTO Advertisers (Name, sales_person) VALUES (?, ?)";
		$query = $this->db->query($sql, $this->db->escape($insert_array));
		$rows = $this->db->affected_rows();
		if($rows>0)
		{
			$advertiser_id = mysql_insert_id();
			$advertiser_sql = "
					SELECT
						id as id,
						Name as name
					FROM
						Advertisers
					WHERE
						id = ?";
			$advertiser_response = $this->db->query($advertiser_sql, $advertiser_id);
			if($advertiser_response->num_rows() > 0)
			{
				return $advertiser_response->row_array();
			}
			else
			{
				return 0;
			}
		}
		else
		{
			return 0;
		}

	}

	function delete_create_time_series_for_campaign($campaign_id, $insert_array, $execute_flag, $object_type_id = 0)
	{
		if ($campaign_id == null || count($insert_array)==0)
			return 0;

		$value_array=array();
		foreach($insert_array as $row)
		{
			if ($row[0] != null && $row[0] != "")
				$value_array[]="(".$campaign_id.",STR_TO_DATE('".$row[0]."', '%m/%d/%Y'),".$row[1].", ".$object_type_id.")";
		}
		$value_string=implode(",", $value_array);
		$sql = 	"
			INSERT IGNORE INTO
				campaigns_time_series
					(
						campaigns_id,
						series_date,
						impressions,
						object_type_id
					)
			VALUES
					".$value_string;

		if ($execute_flag=="EXECUTE")
		{
			$sql_delete = "
				DELETE FROM
					campaigns_time_series
				WHERE
					campaigns_id = ?
					AND object_type_id = ?";

			$query = $this->db->query($sql_delete, array($campaign_id, $object_type_id));

			$query = $this->db->query($sql);
			$rows = $this->db->affected_rows();
			return count($rows);
		}
		else
		{
			return $sql;
		}
	}

	function delete_create_time_series_for_campaign_bulk($delete_campaign_id_array, $insert_campaign_array)
	{
		$value_array=array();
		$delete_string = implode(",",$delete_campaign_id_array);
		$sql_delete = "
			DELETE FROM
				campaigns_time_series
			WHERE
				object_type_id=0 AND
				campaigns_id IN (" . $delete_string . ");";
		$query = $this->db->query($sql_delete);

		$insert_campaign_string = implode(",", $insert_campaign_array);
		$sql = 	"
			INSERT IGNORE INTO
				campaigns_time_series
					(
						campaigns_id,
						series_date,
						impressions,
						object_type_id
					)
			VALUES
					".$insert_campaign_string;
		$query = $this->db->query($sql);

		$rows = $this->db->affected_rows();
		return count($rows);
	}

	//Deprecated - 3 Oct 16 
	function delete_create_time_series_for_io($campaigns_id, $time_series)
	{
		$invalidate_dfp_details_query = 
		"UPDATE 
			io_flight_subproduct_details
		SET
			archived_flag = 1
		WHERE
			io_timeseries_id IN
			(SELECT
				id
			FROM
				campaigns_time_series
			WHERE 
				campaigns_id = ?
				AND object_type_id = 1
			)";
		$invalidate_dfp_details_result = $this->db->query($invalidate_dfp_details_query, $campaigns_id);


		$delete_product_cpm_query =
		"DELETE FROM
			io_campaign_product_cpm
		WHERE
			cp_submitted_products_id = ?
		";
		$delete_product_cpm_result = $this->db->query($delete_product_cpm_query, $campaigns_id);

		$sql_delete =
		'	DELETE FROM campaigns_time_series
			WHERE
				campaigns_id = ? AND
				object_type_id = 1
		';
		$delete_query = $this->db->query($sql_delete, array($campaigns_id));

		$value_array = array();
		foreach($time_series as $flight)
		{
			$value_array[] = "($campaigns_id,STR_TO_DATE('{$flight['start_date']}', '%m/%d/%Y'),{$flight['impressions']},1)";
		}
		$values_string = implode(",", $value_array);

		$sql_insert =
		'	INSERT IGNORE INTO
				campaigns_time_series
				(campaigns_id, series_date, impressions, object_type_id)
				VALUES '. $values_string;
		$query = $this->db->query($sql_insert, array());
	}

	function create_campaign($insert_array, $leave_landing_page_alone = false)
	{
		$categories = $insert_array['categories'];
		unset($insert_array['categories']);
		if(!$leave_landing_page_alone)
		{
			$insert_array['LandingPage'] = $this->format_url($insert_array['LandingPage']);
		}

		$sql = 	"INSERT IGNORE INTO
				Campaigns (Name,  business_id, TargetImpressions, hard_end_date, start_date, term_type, LandingPage, ignore_for_healthcheck, invoice_budget, pause_date, cloned_from_campaign_id)
				VALUES (?,?,?,?,?,?,?,?,?,?,?)";

		$query = $this->db->query($sql, $this->db->escape($insert_array));
		$rows = $this->db->affected_rows();
		if($rows>0)
		{
			$inserted_row = mysql_insert_id();
			$this->update_categories($categories, $inserted_row);
			return $inserted_row;
		}
		else
		{
			return 0;
		}
	}

	function create_campaign_from_insertion_order($campaign_name, $business_id, $io_id)
	{
		$sql =
		'	INSERT IGNORE INTO Campaigns
				(
				Name,
				business_id,
				TargetImpressions,
				start_date,
				hard_end_date,
				term_type,
				LandingPage,
				ignore_for_healthcheck,
				invoice_budget,
				insertion_order_id
				)
			SELECT
				?,
				?,
				ROUND((io.impressions / 1000)),
				io.start_date,
				io.end_date,
				io.term_type,
				io.landing_page,
				0,
				0,
				io.id
			FROM
				mpq_insertion_orders as io
			WHERE
				io.id = ?
			LIMIT 1
		';

		$query = $this->db->query($sql, array($campaign_name, $business_id, $io_id));
		$rows = $this->db->affected_rows();
		if($rows>0)
		{
			return array(
				'id' => mysql_insert_id(),
				'name' => $campaign_name
			);
		}
		else
		{
			throw new Exception('This campaign name already exists for this advertiser', 409);
		}
	}

	public function create_adgroup($insert_array, $geofence_centers = null)
	{
		$return_array = [
			'is_success' => true,
			'vl_id' => 0,
			'errors' => []
		];
		$sql =
		"	INSERT IGNORE INTO
				AdGroups (campaign_id, ID, City, Region, Source, IsRetargeting, target_type, subproduct_type_id)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?)
		";

		$this->db->query($sql, $insert_array);
		if($this->db->affected_rows() > 0)
		{
			$adgroup_vl_id = $this->db->insert_id();
			$return_array['vl_id'] = $adgroup_vl_id;
			if($geofence_centers)
			{
				$values_insert_string = implode(',', array_fill(0, count($geofence_centers), '(?, POINT(?, ?), ?, ?, ?)'));
				$geofence_sql =
				"	INSERT IGNORE INTO
						geofence_adgroup_centers (`adgroup_vl_id`, `center_point`, `radius`, `name`, `address`)
					VALUES
						{$values_insert_string}
				";
				$geofence_bindings = [];
				array_walk($geofence_centers, function($geofence_center_group) use ($adgroup_vl_id, &$geofence_bindings) {
					$geofence_bindings[] = $adgroup_vl_id;
					$geofence_bindings[] = $geofence_center_group['longitude'];
					$geofence_bindings[] = $geofence_center_group['latitude'];
					$geofence_bindings[] = $geofence_center_group['radius'];
					$geofence_bindings[] = $geofence_center_group['name'];
					$geofence_bindings[] = $geofence_center_group['address'];
				});
				$this->db->query($geofence_sql, $geofence_bindings);
			}
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['errors'][] = 'AdGroup unable to be created';
		}

		return $return_array;
	}

	public function update_adgroup($insert_array, $geofence_centers = null)
	{
		$success = true;
		$sql =
		"	UPDATE
				AdGroups
			SET
				ID = ?,
				City = ?,
				Region = ?,
				Source = ?,
				IsRetargeting = ?,
				target_type = ?,
				subproduct_type_id = ?
			WHERE
				vl_id = ?
		";
		$success = $success && $this->db->query($sql, $insert_array);

		if($geofence_centers)
		{
			$adgroup_vl_id = $insert_array[6];
			$ids_to_save = [-1];
			$values_insert_string = implode(',', array_fill(0, count($geofence_centers), '(?, ?, POINT(?, ?), ?, ?, ?)'));
			$geofence_bindings = [];
			array_walk($geofence_centers, function($geofence_center_group) use ($adgroup_vl_id, &$geofence_bindings, &$ids_to_save) {
				$center_id = isset($geofence_center_group['id']) ? $geofence_center_group['id'] : null;
				$ids_to_save[] = $center_id;
				$geofence_bindings[] = $center_id;
				$geofence_bindings[] = $adgroup_vl_id;
				$geofence_bindings[] = $geofence_center_group['longitude'];
				$geofence_bindings[] = $geofence_center_group['latitude'];
				$geofence_bindings[] = $geofence_center_group['radius'];
				$geofence_bindings[] = $geofence_center_group['name'];
				$geofence_bindings[] = $geofence_center_group['address'];
			});

			$this->db->trans_begin();
			if(array_filter($ids_to_save) && count($geofence_bindings))
			{
				$ids_to_save = array_filter($ids_to_save);
				$ids_to_save_insert_string = implode(',', array_fill(0, count($ids_to_save), '?'));
				$delete_centers_bindings = array_merge([$adgroup_vl_id], array_filter($ids_to_save));
				$delete_centers_sql =
				"	DELETE FROM `geofence_adgroup_centers`
					WHERE
						adgroup_vl_id = ? AND
						id NOT IN ({$ids_to_save_insert_string})
				";
				$success = $success && $this->db->query($delete_centers_sql, $delete_centers_bindings);
			}

			$geofence_sql =
			"	INSERT INTO
					`geofence_adgroup_centers` (id, adgroup_vl_id, center_point, radius, name, address)
				VALUES
					{$values_insert_string}
				ON DUPLICATE KEY UPDATE
					center_point = VALUES(center_point),
					radius = VALUES(radius),
					name = VALUES(name),
					address = VALUES(address)
			";
			$success = $success && $this->db->query($geofence_sql, $geofence_bindings);

			if (!$success || $this->db->trans_status() === false)
			{
				$this->db->trans_rollback();
			}
			else
			{
				$this->db->trans_commit();
			}
		}
		return $success;
	}

	function update_advertiser($insert_array)
	{
		$sql =  "UPDATE Advertisers SET  sales_person = ?  WHERE id = ?";
		$query = $this->db->query($sql, $this->db->escape($insert_array));
		return $this->db->affected_rows();
	}

	function update_advertiser_name($advertiser_id, $new_advertiser_name)
	{
		$is_success = false;
		$errors = array();
		$bindings = array($new_advertiser_name, $advertiser_id);
		$sql = "UPDATE Advertisers SET  Name = ? WHERE id = ?";
		if($this->db->query($sql, $bindings))
		{
			$is_success = true;
		}
		else
		{
			$errors[] = 'update advertiser name error';
		}
		return array('is_success'=>$is_success,'errors'=>$errors);
	}

	function update_campaign_name($campaign_id, $new_campaign_name)
	{
		$is_success = false;
		$errors = array();
		$bindings = array($new_campaign_name, $campaign_id);
		$sql = "UPDATE Campaigns SET  Name = ?  WHERE id = ?";
		if($this->db->query($sql, $bindings))
		{
			$is_success = true;
		}
		else
		{
			$errors[] = 'update campaign name error';
		}
		return array('is_success'=>$is_success,'errors'=>$errors);
	}

	function update_campaign($insert_array)
	{
		$insert_array['LandingPage'] = $this->format_url($insert_array['LandingPage']);
		if(array_key_exists('categories', $insert_array))
		{
			$categories = $insert_array['categories'];
			unset($insert_array['categories']);
			$success = $this->update_categories($categories, $insert_array['id']);
		}
		else
		{
			$success = $this->update_categories(array(), $insert_array['id']);
		}

		$sql =  "UPDATE Campaigns SET  LandingPage = ?, TargetImpressions = ?, hard_end_date = ?, start_date = ? , term_type = ?, ignore_for_healthcheck = ?, invoice_budget = ?, pause_date = ?  WHERE id = ?";
		$query = $this->db->query($sql, $this->db->escape($insert_array));
		$rows = $this->db->affected_rows();
		if ($rows > 0)
		{
			$success = $success OR true;
		}
		else
		{
			$success = $success OR FALSE;
		}
		return $success;
	}

	function update_categories($category_list, $c_id)
	{
		$delete_old_entries = "DELETE FROM ad_verify_campaign_categories WHERE campaign_id = ".$c_id;
		$this->db->query($delete_old_entries);
		if (count($category_list) > 0)
		{
			foreach ($category_list as $category)
			{
				$add_new_entry = "INSERT INTO ad_verify_campaign_categories (campaign_id, tag_id) VALUES (".$c_id.",".$category.")";
				$this->db->query($add_new_entry);
			}
		}
		return true;
	}

	function get_campaign_details_by_adset_id($adset_id)
	{
		$sql = "SELECT *
				FROM cup_adsets a
				LEFT JOIN Campaigns c
				ON c.id = a.campaign_id
				WHERE a.id = ?";
		$query = $this->db->query($sql, $adset_id);
		if($query->num_rows() > 0)
		{
			return $query->row_array();
		}
		return null;
	}

	function get_campaign_details_by_version_id($version_id)
	{
		$sql = "SELECT
					*
				FROM
					cup_versions AS ver
				LEFT JOIN Campaigns AS cmp
					ON cmp.id = ver.campaign_id
				WHERE
					ver.id = ?";
		$query = $this->db->query($sql, $version_id);
		if($query->num_rows() > 0)
		{
			return $query->row_array();
		}
		return NULL;
	}

	function get_adset_landing_page_by_version_id($version_id)
	{
		$sql = "SELECT
				COALESCE(cmp.LandingPage, ar.landing_page) as landing_page
			FROM
				cup_versions AS ver
			LEFT JOIN Campaigns AS cmp
				ON cmp.id = ver.campaign_id
			LEFT JOIN adset_requests AS ar
				ON ar.adset_id = ver.adset_id
			WHERE
				ver.id = ?";
		$query = $this->db->query($sql, $version_id);
		if($query->num_rows() > 0)
		{
			$result_row = $query->row_array();
			if(!empty($result_row['landing_page']))
			{
				return $this->al_model->format_url($result_row['landing_page']);
			}
		}
		return NULL;
	}

	function get_campaign_adv_name_by_adset_id($adset_id)
	{
		$sql = "SELECT
				adv.Name
			FROM
				cup_adsets a
				LEFT JOIN Campaigns c
				ON c.id = a.campaign_id
				LEFT JOIN Advertisers adv
				ON adv.id = c.business_id
			WHERE
				a.id = ?";
		$query = $this->db->query($sql, $adset_id);
		if($query->num_rows() > 0)
		{
			return $query->row_array();
		}
		return null;
	}

	function get_campaign_adv_name_by_version_id($version_id)
	{
		$sql =
		"SELECT
			adv.Name
		FROM
			cup_versions AS ver
			LEFT JOIN Campaigns AS cmp
				ON ver.campaign_id = cmp.id
			LEFT JOIN Advertisers AS adv
				ON cmp.business_id = adv.id
			WHERE
				ver.id = ?";
		$query = $this->db->query($sql, $version_id);
		if($query->num_rows() > 0)
		{
			return $query->row_array();
		}
		return NULL;
	}


	function get_all_categories()
	{
	$sql = "SELECT ID, Name FROM ad_verify_content_categories WHERE ID IN (SELECT DISTINCT category_id FROM ad_verify_keywords) ORDER BY Name";
	$query = $this->db->query($sql);
	if($query->num_rows() > 0)
	{
		return $query->result_array();
	}
	return null;
	}

	function get_campaign_categories($c_id)
	{
		$sql = "SELECT c.tag_id FROM ad_verify_campaign_categories c LEFT JOIN ad_verify_content_categories cat ON (c.tag_id = cat.ID) WHERE c.campaign_id = ?";
		$query = $this->db->query($sql, $c_id);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return null;
	}

	public function format_url($dirty_url)
	{
	$the_url = preg_replace('#^https?://#', '', $dirty_url);
	$protocol = "http://";
	if(strpos($dirty_url, 'https://') !== false)
	{
		$protocol = "https://";
	}
	$the_url = $protocol.$the_url;
	$parsed_url = parse_url($the_url);
	if(!isset($parsed_url['path']) && isset($parsed_url['query']))
	{
		$parsed_url['path'] = "/";
		$the_url = $parsed_url['scheme']."://".$parsed_url['host'].$parsed_url['path']."?".$parsed_url['query'];
	}
	return $the_url;
	/*preg_match('@^(?:http://)?([^/]+)@i',   $dirty_url, $matches);
	$url_domain = $matches[1];
	preg_match('@^(?:www.)?([^/]+)@i',   $url_domain, $matches);
	$url_domain = $matches[1];*/
	//return  "http://". $url_domain;
	}

	public function update_campaign_landing_page($c_id, $landing_page)
	{
		$update_array = array($landing_page, $c_id);
		$sql = "SELECT * FROM Campaigns WHERE LandingPage = ? AND id = ?";
		$query = $this->db->query($sql, $update_array);
		if($query->num_rows() > 0)
		{
			return true; // ALREADY EXISTS
		}

		$sql = "UPDATE Campaigns SET LandingPage = ?  WHERE id = ?";
		$query = $this->db->query($sql, $update_array);
		if($this->db->affected_rows() == 1)
		{
			return true;
		}
		return FALSE;
	}

	// this method updates data in report_cached_adgroup_date table by picking data from CityRecords table.
	//	It deletes any rows from report_cached_adgroup_date table if all the Adgroupids and Date combination is removed from City Records
	public function refresh_cache_campaign_report($campaign_id)
	{

		$this->td_uploader_model->load_report_cached_campaign('CAMPAIGN_MODE', '', $campaign_id);

		$delete_query =
		"DELETE FROM
			report_cached_adgroup_date
		WHERE
			(ADGROUP_ID, DATE) IN
				(	SELECT
						C.ADGROUP_ID,
						C.DATE
					FROM
						(
							SELECT
								ADGROUP_ID AS adgroup_id,
								DATE AS Date
							FROM
								report_cached_adgroup_date A
							WHERE
								ADGROUP_ID IN
								(
									SELECT
										ID
									FROM
 										AdGroups
									WHERE
										CAMPAIGN_ID=?
								)
								AND
									(
										A.ADGROUP_ID,
										A.DATE
								)
								NOT IN
								(
									SELECT
										ADGROUPID,
										DATE
										FROM
										CityRecords
									WHERE
										ADGROUPID IN
										(
											SELECT
												ID
												FROM
							 					AdGroups
											WHERE
											CAMPAIGN_ID=?
										)
								)
						) AS C
				)
		";
		$delete_bindings = array($campaign_id, $campaign_id);
		$this->db->query($delete_query, $delete_bindings);
		return true;
	}

	public function get_advertiser_owners_by_advertiser_id($advertiser_id)
	{
		$query = "
			SELECT
				us.id as id,
				CONCAT(us.firstname, ' ', us.lastname) AS text
			FROM
				users us
				JOIN users_join_advertisers_for_advertiser_owners ja
					ON us.id = ja.user_id
			WHERE
				ja.advertiser_id = ?";
		$result = $this->db->query($query, $advertiser_id);
		if(!empty($result))
		{
			return $result->result_array();
		}
		return false;
	}

	public function get_advertiser_owners_by_search_term($term, $start, $limit)
	{
		$query = "
			SELECT
				id,
				CONCAT(firstname, ' ', lastname) AS text
			FROM
				users
			WHERE
				role IN ('ADMIN', 'OPS', 'CREATIVE') AND
				concat_ws(' ', LOWER(firstname), LOWER(lastname)) LIKE LOWER(?)
			LIMIT ?, ?";
		$result = $this->db->query($query, array($term, $start, $limit));
		if($result !== false)
		{
			return $result->result_array();
		}
		return array();
	}

	public function add_advertiser_owner_relationship($user_id, $advertiser_id)
	{
		$query = "INSERT INTO users_join_advertisers_for_advertiser_owners (user_id, advertiser_id) VALUES(?, ?)";
		$result = $this->db->query($query, array($user_id, $advertiser_id));
		if($result !== false && $this->db->affected_rows() > 0)
		{
			return true;
		}
		return false;
	}

	public function remove_advertiser_owner_relationship($user_id, $advertiser_id)
	{
		$query = "
			DELETE FROM
				users_join_advertisers_for_advertiser_owners
			WHERE
				user_id = ? AND
				advertiser_id = ?";
		$result = $this->db->query($query, array($user_id, $advertiser_id));
		if($result !== false && $this->db->affected_rows() > 0)
		{
			return true;
		}
		return false;
	}

	public function add_new_note($object_id, $new_note_text, $username, $object_type_id, $legacy_date)
	{
		$bindings=array($new_note_text, $username, $object_type_id, $object_id);
		if ($legacy_date == null || $legacy_date == "")
		{
			$query = "
				INSERT INTO notes
					(notes_text, username, object_type_id, object_id)
				VALUES (?, ?, ?, ?)";
		}
		else
		{
			$query = "
			INSERT INTO notes
				(notes_text, username, object_type_id, object_id, created_date)
			VALUES (?, ?, ?, ?, STR_TO_DATE(?, '%m/%d/%Y %H:%i:%s'))";
			$bindings[]=$legacy_date;
		}


		$result = $this->db->query($query, $bindings);
		if($result !== false && $this->db->affected_rows() > 0)
		{
			return true;
		}
		return false;
	}

	public function generate_campaign_notes($campaign_id, $campaign_region, $campaign_product, $geo_data = '')
	{
            // Get insertion order id
            $generated_data['io_id'] = '';
            $sql_io = "
                SELECT
                    insertion_order_id
                FROM
                    Campaigns
                WHERE
                    id = ?";
            $query_io = $this->db->query($sql_io, $campaign_id);
            if ($query_io->num_rows() > 0)
            {
               $row_io = $query_io->row_array();
               $generated_data['io_id'] = $row_io['insertion_order_id'];
            }

            $geo_data = str_replace(", ", ',', $geo_data);
            $geo_data = explode(',', $geo_data);
            $in_string = rtrim(str_repeat('?,', count($geo_data)), ',');

            // Get total population
            $generated_data['population'] = '';
            $sql_population = "
                SELECT
                    SUM(population_total) AS population_total
                FROM
                    geo_cumulative_demographics
                WHERE
                    local_id IN ({$in_string})";

            $query_population = $this->db->query($sql_population, $geo_data);
            if ($query_population->num_rows() > 0)
            {
               $row_population = $query_population->row_array();
               $generated_data['population'] = $row_population['population_total'];
            }

            // Get Context
            $generated_data['context'] = '';
            $sql_context = "
                SELECT
                    ic.tag_friendly_name
                FROM
                    iab_categories AS ic
                    JOIN mpq_iab_categories_join AS micj
                        ON (ic.id = micj.iab_category_id)
                WHERE
                    micj.mpq_id = ?";
            $query_context = $this->db->query($sql_context, $generated_data['io_id']);
            if ($query_context->num_rows() > 0)
            {
                $query_result = $query_context->result_array();
                foreach($query_result as $context)
                {
                    $generated_data['context'] .= $context['tag_friendly_name'].', ';
                }

                $generated_data['context'] = rtrim($generated_data['context'], ', ');
            }

            // Get Ad ID
            $generated_data['mpq_id'] = '';
            $generated_data['ad_version_id'] = '';
            $generated_data['demographic_data'] = '';
            $adset_id = '';
            $sql_ad_region = "
                SELECT
                    region_data,
                    demographic_data,
                    parent_mpq_id
                FROM
                    mpq_sessions_and_submissions
                WHERE
                    id = ?";
            $query_ad_region = $this->db->query($sql_ad_region, $generated_data['io_id']);
            if ($query_ad_region->num_rows() > 0)
            {
                $row_ad_region = $query_ad_region->row_array();
                $generated_data['mpq_id'] = $row_ad_region['parent_mpq_id'];
                $generated_data['demographic_data'] = $row_ad_region['demographic_data'];
                $region_data = json_decode($row_ad_region['region_data']);
                foreach ($region_data as $region)
                {
                    if (isset($region->user_supplied_name) && ($region->user_supplied_name == $campaign_region))
                    {
                        $region_data_index = $region->page;
                        $sql_ad_version = "
                            SELECT
                                cv2.id, cv2.adset_id
                            FROM
                                cp_products AS cp
                                JOIN cp_submitted_products AS csp
                                    ON (cp.id = csp.product_id)
                                JOIN cp_io_join_cup_versions AS cijcv
                                    ON (csp.id = cijcv.cp_submitted_products_id)
                                JOIN cup_versions AS cv
                                    ON (cijcv.cup_versions_id = cv.id)
                                JOIN cup_versions AS cv2
                                    ON (cv.adset_id = cv2.adset_id)
                            WHERE
                                cp.friendly_name = ? AND
                                csp.mpq_id = ? AND
                                csp.region_data_index = ?";

                        $query_ad_version = $this->db->query($sql_ad_version, array($campaign_product, $generated_data['io_id'], $region_data_index));
                        if ($query_ad_version->num_rows() > 0)
                        {
                            $query_ad_version_result = $query_ad_version->result_array();
                            foreach($query_ad_version_result as $ad_version_ids)
                            {
                                $generated_data['ad_version_id'] .= $ad_version_ids['id'].'<br />';
                                $adset_id = $ad_version_ids['adset_id'];
                            }

                            $generated_data['ad_version_id'] = rtrim($generated_data['ad_version_id'], '<br />');
                        }
                    }
                }
            }

            $generated_data['bi_id'] = '';
            //Get banner intake id
            if (!empty($adset_id))
            {
                $sql_bi = "
                    SELECT
                        id
                    FROM
                        adset_requests
                    WHERE
                        adset_id = ?";
                $query_bi = $this->db->query($sql_bi, $adset_id);
                if ($query_bi->num_rows() > 0)
                {
                   $row_bi = $query_bi->row_array();
                   $generated_data['bi_id'] = $row_bi['id'];
                }
            }

            return $generated_data;
	}

	public function update_note_bad_flag($note_id)
	{
		$query = "
			DELETE FROM notes
			WHERE
				id=?";

		$result = $this->db->query($query, array($note_id));
		return $result;
	}

	public function update_imp_flag($note_id)
	{
		$query = "
			UPDATE notes
			SET is_important_flag = CASE WHEN is_important_flag = 1 THEN 0 ELSE 1 END
			WHERE
				id=?";

		$result = $this->db->query($query, array($note_id));
		return $result;
	}

	public function get_notes_for_campaign($advertiser_id, $cid)
	{
		$result_array=array();
		$query = "
			SELECT
				id,
				notes_text,
				created_date,
				username,
				is_important_flag
			FROM
				notes
			WHERE
				object_id=? AND
				object_type_id=1
			ORDER BY created_date DESC";

		$result = $this->db->query($query, array($cid));
		if($result !== false)
		{
			$result_array['notes_data_campaign']=$result->result_array();
		}
		else
		{
			$result_array['notes_data_campaign']=array();
		}

		$query = "
			SELECT
				id,
				notes_text,
				created_date,
				username,
				is_important_flag
			FROM
				notes
			WHERE
				object_id=? AND
				object_type_id=2
			ORDER BY created_date DESC";

		$result = $this->db->query($query, array($advertiser_id));
		if($result !== false)
		{
			$result_array['notes_data_adv']=$result->result_array();
		}
		else
		{
			$result_array['notes_data_adv']=array();
		}


		return $result_array;
	}

	public function get_all_notes_for_admin_view()
	{
		$query = "
			SELECT
				SUM.Advertiser 'Advertiser',
				SUM.object_id 'Campaign ID',
				SUM.CampaignName 'Campaign Name',
				SUM.notes_text 'Notes',
				SUM.created_date 'CreatedDate',
				SUM.username 'username'
			FROM
			(
				SELECT
					a.name 'Advertiser',
					notes.object_id  ,
					c.name 'CampaignName',
					notes.notes_text  ,
					notes.created_date  ,
					notes.username
				FROM
					notes,
					Campaigns c,
					Advertisers a
				WHERE
					notes.object_type_id=1 AND
					notes.object_id=c.id AND
					c.business_id=a.id AND
					DATE_SUB(notes.created_date, INTERVAL 7 DAY)
				UNION ALL
					SELECT
					a.name  ,
					'--'  ,
					'--'  ,
					notes.notes_text ,
					notes.created_date ,
					notes.username
				FROM
					notes,
					Advertisers a
				WHERE
					notes.object_type_id=2 AND
					notes.object_id=a.id AND
					DATE_SUB(notes.created_date, INTERVAL 7 DAY)
				) AS SUM
				ORDER BY
					SUM.created_date DESC
			 ";

		$return_array=array();
		$result = $this->db->query($query);
		if($result !== false)
		{
			$return_array['table_notes']= $result->result_array();
		}
		else
		{
			$result_array['table_notes']=array();
		}

		$query = '
			SELECT
				DATE_FORMAT(created_date,"%Y-%m-%d") created_date,
				username,
				count(*) count
	    	FROM
				notes
			WHERE
				object_type_id in (1, 2)  AND
				DATE_SUB(created_date, INTERVAL 14 DAY)
			GROUP BY
				DATE_FORMAT(created_date,"%Y-%m-%d"),
				username
			ORDER BY
				DATE_FORMAT(created_date,"%Y-%m-%d") DESC,
				count(*) DESC
			 ';


		$result = $this->db->query($query);
		if($result !== false)
		{
			$return_array['report_notes']= $result->result_array();
		}
		else
		{
			$result_array['report_notes']=array();
		}

		$query = '
			SELECT
				username,
				count(*) count
			FROM
				notes
			WHERE
				object_type_id in (1,2)  AND
				DATE_SUB(created_date, INTERVAL 14 DAY)
			GROUP BY
				username
			ORDER BY
				count(*) DESC
			 ';


		$result = $this->db->query($query);
		if($result !== false)
		{
			$return_array['report_notes_total']= $result->result_array();
		}
		else
		{
			$result_array['report_notes_total']=array();
		}

		return $return_array;
	}

	public function format_time_series_array_from_string($time_series_data)
	{
		if ($time_series_data == null)
			return 0;
		$timeseries_insert_array=explode("^",$time_series_data);
		$timeseries_insert_array_new=array();
		foreach ($timeseries_insert_array as $data)
		{
			$timeseries_insert_sub_array=explode("*",$data);
			$timeseries_insert_array_new[]=$timeseries_insert_sub_array;
		}
		return $timeseries_insert_array_new;
	}

	public function trash_campaign($campaign_id)
	{
		$trash_campaign_query =
		"UPDATE
			Campaigns
		SET
			business_id = ?,
			Name = CONCAT(Name,' - ', id, ' - ', ?),
			ignore_for_healthcheck = 1
		WHERE
			id = ?
		";

		$random = rand(pow(10, 2), pow(10, 3)-1);
		$trash_campaign_result = $this->db->query($trash_campaign_query, array(1018, $random, $campaign_id));
		if($trash_campaign_result == false)
		{
			return false;
		}
		return true;
	}

	public function generate_budget_and_impressions($campaign_id)
	{
		$generate_budget_and_impressions_query =
		"SELECT 
			SUM(ifsd.geofencing_impressions) AS geofencing_impressions,
			SUM(ifsd.geofencing_budget) AS geofencing_budget,
			SUM(ifsd.audience_ext_impressions) AS audience_ext_impressions,
			SUM(ifsd.audience_ext_budget) AS audience_ext_budget
		FROM
			campaigns_time_series AS cts
			JOIN io_flight_subproduct_details AS ifsd
			    ON cts.id = ifsd.io_timeseries_id
		WHERE
			cts.campaigns_id = ?
		";
		$budget_and_impressions_result = $this->db->query($generate_budget_and_impressions_query, array($campaign_id));
		
		if($budget_and_impressions_result != false)
		{
			$budget_and_impressions_result = $budget_and_impressions_result->result_array();
			//$budget_and_impressions_result = array_map('current', $budget_and_impressions_result);
			return $budget_and_impressions_result[0];
		}
		return false;
	}
	
	public function get_all_subproduct_types()
	{
		$sql = "
			SELECT 
				`Id`, `name` 
			FROM 
				`subproduct_types` 
			ORDER BY Id";
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return NULL;
	}
}
