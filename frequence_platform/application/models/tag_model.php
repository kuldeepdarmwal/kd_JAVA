<?php
class Tag_model extends CI_Model 
{
	public function __construct()
	{
		$this->load->database();
	}
	
	public function get_active_inactive_tags()
	{
		$sql = "
			SELECT  
				t.id AS id, 
				tf.name AS name,
				t.tag_type AS tag_type, 
				t.isActive AS is_active,
				t.username AS username,
				supr.advertiser_id AS advertiser_id,
				supr.advertiser_name AS advertiser_name,
				supr.full_campaign AS full_campaign,
				supr.campaign_id AS campaign_id
			FROM 
				tag_codes t
			JOIN 
				tag_files tf 
			ON 
				t.tag_file_id = tf.id
			JOIN (
				SELECT 
					tag_file_id,
					a.id AS advertiser_id,
					a.Name AS advertiser_name,
					GROUP_CONCAT(c.Name SEPARATOR ',') AS full_campaign, 
					GROUP_CONCAT(c.id SEPARATOR ',') AS campaign_id
				FROM 
					tag_files_to_campaigns ctf
				JOIN
					Campaigns c
				ON
					ctf.campaign_id = c.id
				JOIN 
					Advertisers a
				ON 
					a.id = c.business_id
				GROUP BY
					tag_file_id, a.id, a.Name
			) AS supr
			ON 
				supr.tag_file_id = t.tag_file_id
			GROUP BY
				t.id, tf.name, t.tag_type, t.isActive, t.username, supr.advertiser_id, supr.advertiser_name
			";
		$query = $this->db->query($sql);

		if ($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		
		return NULL;
	}

	public function get_all_active_tag_files_excluding_ad_verify()
	{
		$sql = "SELECT DISTINCT 
				tf.* 
			FROM 
				tag_codes t 
			LEFT JOIN
				tag_files tf
			ON
				t.tag_file_id = tf.id
			WHERE 
				t.isActive = 1 
			AND 
				t.tag_type != 1 
			ORDER BY 
				tf.name ASC";
		
		$query = $this->db->query($sql);
		
		if ($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return NULL;
	}
	
	public function get_active_filenames()
	{
		$sql = "
			SELECT DISTINCT 
				tf.name 
			FROM 
				tag_codes t 
			LEFT JOIN
				tag_files tf
			ON
				t.tag_file_id = tf.id	
			WHERE 
				t.isActive = 1 
			ORDER BY 
				tf.name ASC";
		$query = $this->db->query($sql);
		
		if ($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return NULL;
	}
	
	public function get_inactive_filenames()
	{
		$sql = "
			SELECT DISTINCT 
				tf.name 
			FROM 
				tag_codes t
			LEFT JOIN
				tag_files tf
			ON
				t.tag_file_id = tf.id	
			WHERE 
				t.isActive = 0 
			ORDER BY 
				tf.name ASC";
		$query = $this->db->query($sql);
		
		if ($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return NULL;
	}
	
	public function get_tag_by_tag_id($tag_id, $adv_id)
	{
		$binding_array = array($adv_id, $tag_id);
		$sql = "SELECT 
				t.id AS id, 
				tf.name AS name,
				t.tag_type AS tag_type, 
				t.isActive AS is_active,
				t.username AS username,
				t.tag_code AS tag_code,
				a.id AS advertiser_id,
				a.Name AS advertiser_name,
				tf.id AS tag_file_id,
				GROUP_CONCAT(c.Name SEPARATOR ',') AS full_campaign, 
				GROUP_CONCAT(c.id SEPARATOR ',') AS campaign_id
			FROM 
				tag_codes t
			LEFT JOIN
				tag_files tf
			ON
				t.tag_file_id = tf.id
			LEFT JOIN 
				tag_files_to_campaigns ctf
			ON 
				tf.id = ctf.tag_file_id
			LEFT JOIN
				Campaigns c
			ON
				ctf.campaign_id = c.id
			LEFT JOIN 
				Advertisers a
			ON 
				a.id = c.business_id
			WHERE
				a.id = ?
			AND
				t.id = ?		
			GROUP BY
				t.id, tf.name, t.tag_type, t.isActive, t.username, t.tag_code, a.id, a.Name, tf.id";

		$query = $this->db->query($sql,$binding_array);

		if ($query->num_rows() > 0)
		{
			return $query->result_array();
		}

		return NULL;
	}

	public function get_tags_by_file_id($file_id)
	{
		$binding_array = array($file_id);
		$sql = "
			SELECT 
				t.tag_code AS tag, 
				t.*,
				tf.io_advertiser_id AS advertiser_id
			FROM 
				tag_codes t
			LEFT JOIN
				tag_files tf
			ON
				t.tag_file_id = tf.id	
			WHERE 
				t.tag_type != 1 
			AND 
				tf.id = ?";
		$query = $this->db->query($sql, $binding_array);
		
		if ($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return NULL;
	}
	
	public function insert_tag($insert_array)
	{
		if ($insert_array[3] == 0 || $insert_array[3] == 2)
		{
			$result = $this->check_if_tag_file_contains_rtg_or_conversion_tag(-1,$insert_array[3],$insert_array[2]);

			if ($result)
			{
				$result_arr["status"] = "fail";
				$result_arr["error_message"] = "File already contains ".(($insert_array[3] == 0) ? "RTG tag" : "conversion tag");
				return $result_arr;
			}
		}

		//$insert_array[1] = url_title($insert_array[1]);
		$sql = "INSERT INTO tag_codes (tag_code, username, tag_file_id, tag_type) VALUES (?, ?, ?, ?)";
		$query = $this->db->query($sql, $this->db->escape($insert_array));
		
		if ($this->db->insert_id() > 0)
		{
			$result_arr["status"] = "success";
			$result_arr["id"] = $this->db->insert_id();
						
			//If insert is successful and tag_tye is RTG/CONV then check and insert custom tag for lift pixel.
			$this->check_and_insert_tag_for_lift_pixel($insert_array);
		}
		else
		{
			$result_arr["status"] = "fail";
			$result_arr["error_message"] = "Error while staging tag";
		}
		
		return $result_arr;
	}
	
	public function update_tag($update_array)
	{
		if ($update_array[3] == 0 || $update_array[3] == 2)
		{
			$result = $this->check_if_tag_file_contains_rtg_or_conversion_tag(null, $update_array[3], $update_array[2]);

			if ($result)
			{
				$result_arr["status"] = "fail";
				$result_arr["error_message"] = "File already contains ".(($update_array[3] == 0) ? "RTG tag" : "conversion tag");
				return $result_arr;
			}
		}
		
		$sql = "UPDATE 
				tag_codes
			SET 
				tag_code= ?,
				username = ?,
				tag_file_id = ?,
				tag_type = ?
			WHERE
				id = ?";
		$query = $this->db->query($sql, $this->db->escape($update_array));
		
		if ($this->db->affected_rows() > 0)
		{
			$result_arr["status"] = "success";
		}
		else
		{
			$result_arr["status"] = "fail";
			$result_arr["error_message"] = "Error while updating tag";
		}
		
		return $result_arr;
	}
	
	public function update_tag_type_and_tag_code($tag_type, $old_tag_type, $tag_code, $tag_id)
	{
		$binding_array = array($tag_type, $tag_id);
		$result_arr = array();
		
		if ( ($tag_type == 0 || $tag_type == 2) && ($old_tag_type !== $tag_type))
		{
			$result = $this->check_if_tag_file_contains_rtg_or_conversion_tag($tag_id, $tag_type);

			if ($result)
			{
				$result_arr["status"] = "fail";
				$result_arr["error_message"] = "File already contains ".(($tag_type == 0) ? "RTG tag" : "conversion tag");
				return $result_arr;
			}
		}
		
		$binding_array = array($tag_code, $tag_type, $tag_id);
		
		$sql = "UPDATE 
				tag_codes
			SET 
				tag_code = ?, tag_type = ?
			WHERE
				id = ?";
		
		$query = $this->db->query($sql,$binding_array);
		
		if ($this->db->affected_rows() > 0)
		{
			$result_arr["status"] = "success";
		}
		else
		{
			$result_arr["status"] = "fail";
			$result_arr["error_message"] = "Update failed";
		}
			
		return $result_arr;
	}
	
	public function check_if_tag_file_contains_rtg_or_conversion_tag($tag_id, $tag_type, $tag_file_id = -1)
	{
		$binding_array = array($tag_type, $tag_id);
		if ($tag_type == 0 || $tag_type == 2)
		{
			if ($tag_file_id != -1)
			{
				$binding_array = array($tag_type, $tag_file_id);
				$query = "
					SELECT
						tc.id
					FROM
						tag_codes tc
					WHERE
						tc.tag_type = ?
					AND
						tc.isActive = 1
					AND
						tc.tag_file_id = ?
					";
			}
			else
			{
				$query = "
					SELECT
						tc.id
					FROM
						tag_codes tc
					WHERE
						tc.tag_type = ?
					AND
						tc.isActive = 1	
					AND
						tc.tag_file_id
					IN
						(
							SELECT 
								tag_file_id
							FROM
								tag_codes tc_inner
							WHERE
								id = ?
						)
					";
			}
			
			$result =  $this->db->query($query, $binding_array);

			if ($result->num_rows() > 0)
			{
				return true;
			}
		}
		return false;
	}
	
	public function activate_tag($insert_array, $adv_id)
	{
		$result_arr["status"] = "success";
		$tag = $this->get_tag_by_tag_id($insert_array['id'],$adv_id);
		if ($tag[0]['tag_type'] == 0 || $tag[0]['tag_type'] == 2)
		{
			$result = $this->check_if_tag_file_contains_rtg_or_conversion_tag(null, $tag[0]['tag_type'], $tag[0]['tag_file_id']);
			if ($result)
			{
				$result_arr["status"] = "fail";
				$result_arr["error_message"] = "File already contains ".(($tag[0]['tag_type'] == 0) ? "RTG tag" : "conversion tag");
				return $result_arr;
			}
		}
		
		$sql =    "UPDATE tag_codes SET isActive = 1 WHERE id = ?";
		$query = $this->db->query($sql, $this->db->escape($insert_array));
		return $result_arr;
	}
	
	public function deactivate_tag($insert_array)
	{
		$sql =    "UPDATE tag_codes SET isActive = 0 WHERE id = ?";
		$query = $this->db->query($sql, $this->db->escape($insert_array));
		return $this->db->affected_rows();
	}
	
	public function get_search_tags($search_term)
	{
		$s='%'.$search_term.'%';
		$bindings = array($s,$s,$s);
		$sql = 	"
			SELECT 
				t.id AS id, 
				tf.name AS name,
				t.tag_type AS tag_type, 
				t.isActive AS is_active,
				t.username AS username,
				t.tag_code AS tag_code,
				a.id AS advertiser_id,
				a.Name AS advertiser_name,
				GROUP_CONCAT(c.Name SEPARATOR ',') AS full_campaign, 
				GROUP_CONCAT(c.id SEPARATOR ',') AS campaign_id
			FROM 
				tag_codes t
			LEFT JOIN
				tag_files tf
			ON
				t.tag_file_id = tf.id
			LEFT JOIN 
				tag_files_to_campaigns ctf
			ON 
				tf.id = ctf.tag_file_id
			LEFT JOIN
				Campaigns c
			ON
				ctf.campaign_id = c.id
			LEFT JOIN 
				Advertisers a
			ON 
				a.id = c.business_id
			WHERE 
				tf.name LIKE  ? OR a.Name LIKE ? OR c.Name LIKE ?
			GROUP BY
				t.id, tf.name, t.tag_type, t.isActive, t.username, t.tag_code, a.id, a.Name	
			";	
		
		$result = $this->db->query($sql,$bindings);
		if($result->num_rows() > 0)
		{
			return $result->result_array();
		}
		else
		{
			return NULL;
		}
	}

	public function get_all_tag_files_for_advertiser($adv_id,$tag_file_id=-1,$tag_id=-1,$tag_type=-1)
	{
		$binding = array();
		$binding[] = $adv_id;
		$tag_file_sql = "";
		$no_adverify_sql = "";
		
		if ($tag_file_id != -1)
		{
			$tag_file_sql = " AND tf.id = ?";
			$binding[] = $tag_file_id;
		}
		elseif ($tag_id != -1)
		{
			$tag_file_sql = 
				" 
				AND 
					tf.id IN (SELECT tag_file_id  
				FROM 
					tag_codes WHERE id = ?) 
				";
			$binding[] = $tag_id;
		}
		
		if ($tag_type != 1)
		{
			$no_adverify_sql =
				" 
				AND 
					lower(tf.name) NOT LIKE '%ad_verify%' 
				AND 
					lower(tf.name) NOT LIKE '%adverify%' 
				";				                                                                                                                                       
		}
		
		$query = "
			SELECT 
				tf.name
			FROM
				tag_files AS tf
			WHERE
				tf.io_advertiser_id = ?
				$tag_file_sql
				$no_adverify_sql
			";
		$result =  $this->db->query($query, $binding);
		
		if ($result->num_rows() > 0)
		{
			return $result->result_array();
		}
		return FALSE;
	}
	
	public function get_all_tag_files_for_campaign($cmp_id,$tag_file_id=-1,$tag_id=-1,$tag_type=-1)
	{
		$binding = array();
		$binding[] = $cmp_id;
		$tag_file_sql = "";
		$no_adverify_sql = "";
		
		if ($tag_file_id != -1)
		{
			$tag_file_sql = " AND tf.id = ?";
			$binding[] = $tag_file_id;
		}
		elseif ($tag_id != -1)
		{
			$tag_file_sql = 
				" AND tf.id IN (SELECT tag_file_id FROM tag_codes WHERE id = ?) ";
			$binding[] = $tag_id;
		}
		
		if ($tag_type != 1)
		{
			$no_adverify_sql =
				" AND
					tf.id NOT IN	
					(
						SELECT 
							tc_in.tag_file_id 
						FROM 
							tag_codes AS tc_in
						JOIN
							tag_files_to_campaigns AS tftc_in
						ON
							tc_in.tag_file_id = tftc_in.tag_file_id									       
						WHERE 
							tc_in.tag_type = 1
						AND
							tftc_in.campaign_id = ?
					) 
				";
			$binding[] = $cmp_id;
		}
		
		$query = "
			SELECT 
				tf.name
			FROM
				tag_files AS tf
			JOIN
				tag_files_to_campaigns AS tftc ON (tf.id = tftc.tag_file_id)
			WHERE
				tftc.campaign_id = ?
				$tag_file_sql
				$no_adverify_sql
			";
		$result =  $this->db->query($query, $binding);
		
		if ($result->num_rows() > 0)
		{
			return $result->result_array();
		}
		return FALSE;
	}
	
	public function get_all_tracking_tag_files_for_advertiser_for_search_term($search_term, $start, $limit, $advertiser_id, $source_table, $td_tag_type = 0)
	{
		$binding_array = array();
		$binding_array[] = $source_table;
		$binding_array[] = $advertiser_id;
		$binding_array[] = $search_term;
		$binding_array[] = $advertiser_id;
		$binding_array[] = $start;
		$binding_array[] = $limit;
		
		$tag_type_sql = "t.tag_type = 1";
		
		if ($td_tag_type == 1)
		{
			
			$tag_type_sql = "t.tag_type != 1";
		}
		
		
		
		$sql = 
			"
			SELECT DISTINCT
				tfile.id AS id,
				tfile.name AS tag_file_name 
			FROM 
				tag_files tfile
			WHERE 
				tfile.name IS NOT NULL
			AND
				tfile.source_table = ?
			AND
				tfile.io_advertiser_id = ? 
			AND
				tfile.name LIKE ?
			AND 
				tfile.id 
			NOT IN 
				(
					SELECT DISTINCT
						tfile.id
					FROM 
						tag_codes t
					LEFT JOIN
						tag_files tfile
					ON
						t.tag_file_id = tfile.id	
					WHERE 
						tfile.io_advertiser_id = ? 						
					AND 
						$tag_type_sql
				)		
			ORDER BY 
				tfile.name ASC
			LIMIT ?,?	
			";

		$query = $this->db->query($sql, $binding_array);
		
		if ($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		
		return false;
	}
	
	public function get_tracking_tag_file_name_by_id($tracking_tag_file_id)
	{
		$binding_array = array();
		$binding_array[] = $tracking_tag_file_id;
		
		$sql = 
			"
			SELECT 
				tfile.name AS tracking_tag_file_name
			FROM 
				tag_files tfile
			WHERE
				tfile.id = ?
			";
		
		$query = $this->db->query($sql, $binding_array);
		
		if ($query->num_rows() > 0)
		{
			$result = $query->result_array();
			return $result[0]['tracking_tag_file_name'];
		}
		
		return null;
	}
	
	public function save_tracking_tag_file($tracking)
	{
		if (!isset($tracking) || !isset($tracking->tracking_tag_file))
		{
			return null;
		}
		
		$binding_array = array();
		$binding_array[] = $tracking->tracking_tag_file;
		$binding_array[] = $tracking->source_table;		
		$binding_array[] = $tracking->io_advertiser_id;
		
		$sql = 
			"
			SELECT 
				tfile.id AS id
			FROM 
				tag_files tfile
			WHERE
				tfile.name = ?
			AND
				tfile.source_table = ?
			AND	
				tfile.io_advertiser_id = ?			
			";
		
		$query = $this->db->query($sql, $binding_array);
		
		if ($query->num_rows() > 0)
		{
			$result = $query->result_array();
			return $result[0]['id'];
		}
		else
		{
			//Create new file.
			$sql = "INSERT INTO tag_files (name, source_table, io_advertiser_id) VALUES(?, ?, ?)";
			$query = $this->db->query($sql,$binding_array);

			if ($this->db->affected_rows() > 0)
			{
				return $this->db->insert_id();
			}
			
		}
		return null;
	}
	
	public function create_tag_files_to_campaigns_entry($file_id, $campaign_id, $existing_tags_assigned = null)
	{
		if (!isset($campaign_id) || !isset($file_id))
		{
			return false;
		}
		
		$binding_array = array($file_id, $campaign_id);
		
		$sql = 
			"
			SELECT 
				ctf.tag_file_id AS tag_file_id
			FROM 
				tag_files_to_campaigns ctf
			WHERE
				ctf.tag_file_id = ?
			AND
				ctf.campaign_id = ?
			";
		
		$query = $this->db->query($sql, $binding_array);
		
		if ($query->num_rows() > 0)
		{
			if ($existing_tags_assigned === 0 || $existing_tags_assigned === 1)
			{
				$update_result = $this->update_tag_files_to_campaigns_with_tags_assigned_status($existing_tags_assigned, $file_id, $campaign_id);
				
				if (!$update_result)
				{
					return false;
				}
			}
			
			$result = $query->result_array();
			return $result[0]['tag_file_id'];
		}
		else
		{
			if ($existing_tags_assigned === null)
			{
				$existing_tags_assigned = 0;				
			}
			$binding_array[] = $existing_tags_assigned;
			
			$sql = "INSERT INTO tag_files_to_campaigns (tag_file_id, campaign_id, existing_tags_assigned) VALUES (?, ?, ?)";
			$query = $this->db->query($sql, $binding_array);
			return $this->db->affected_rows();		
		}
	}
	
	public function update_tag_files_to_campaigns_with_tags_assigned_status($existing_tags_assigned, $file_id, $campaign_id)
	{
		$bindings = array($existing_tags_assigned, $file_id, $campaign_id);		
		$query = "
				UPDATE
					tag_files_to_campaigns AS tftc
				SET
					existing_tags_assigned = ?
				WHERE
					tag_file_id = ?
				AND
					campaign_id = ?
			";
		$result = $this->db->query($query,$bindings);
		
		if ($this->db->affected_rows() > 0)
		{
			return true;
		}
		
		return false;
	}
	
	public function update_tag_files_to_campaigns_with_new_tag_file_id($new_tag_file_id, $old_tag_file_id, $campaign_ids)
	{
		$bindings = array($new_tag_file_id, $old_tag_file_id);		
		$query = "
				UPDATE
					tag_files_to_campaigns AS tftc
				SET
					tag_file_id = ?
				WHERE
					tag_file_id = ?
				AND
					campaign_id IN (".implode(",",$campaign_ids).")";
		$result = $this->db->query($query,$bindings);
		
		if ($this->db->affected_rows() > 0)
		{
			return true;
		}
		
		return false;
	}
	
	public function get_tag_files_to_campaigns_entry($tag_file_id, $campaign_id)
	{
		$bindings = array($tag_file_id, $campaign_id);		
		$query = "
				SELECT
					tftc.*
				FROM	
					tag_files_to_campaigns AS tftc
				WHERE
					tag_file_id = ?
				AND
					campaign_id = ?
			";
		$result = $this->db->query($query,$bindings);
		
		if ($result->num_rows() > 0)
		{
			return $result->row_array();
		}
		
		return false;
	}
	
	public function get_advertiser_by_campaign_id($campaign_id)
	{
		$query = "
				SELECT
					ad.*
				FROM
					Campaigns AS cmpgn
				LEFT JOIN 
					Advertisers AS ad
				ON 
					cmpgn.business_id = ad.id
				WHERE
					cmpgn.id = ?";
		$result = $this->db->query($query, $campaign_id);
		
		if ($result->num_rows() > 0)
		{
			return $result->row_array();
		}

		
		return false;	    
	}
	
	public function get_advertiser_by_advertiser_id($advertiser_id, $source_table = null)
	{
		if ($source_table != null && $source_table == 'advertisers_unverified')
		{
			$query = "
				SELECT
					ad_unverified.*,
					ad_unverified.name AS Name
				FROM
					advertisers_unverified AS ad_unverified
				WHERE
					ad_unverified.id = ?";
		}
		else
		{
			$query = "
				SELECT
					ad.*
				FROM
					Advertisers AS ad
				WHERE
					ad.id = ?";
		}
		
		$result = $this->db->query($query, $advertiser_id);
		
		if ($result->num_rows() > 0)
		{
			return $result->row_array();
		}

		
		return false;	    
	}

	public function save_tag_code_to_file($tag_html, $tag_file_id, $tag_type)
	{
		$username = $this->session->userdata('username');
		$bindings = array(
			$tag_html,
			$username,
			$tag_file_id,
			$tag_type
		);
		$save_tag_code_query = "INSERT INTO tag_codes (tag_code, username, tag_file_id, tag_type) VALUES (?, ?, ?, ?)";
		$save_tag_code_result = $this->db->query($save_tag_code_query, $bindings);

		if ($save_tag_code_result == false)
		{
			return false;
		}
		else
		{
			$this->check_and_insert_tag_for_lift_pixel($bindings);
		}
		
		return true;
	}

	public function retrieve_tag_file_info_for_campaign($campaign_id)
	{
		$get_tag_files_info_query =
		"SELECT
			tf.id AS id,
			tf.name AS name
		FROM
			tag_files_to_campaigns AS tftc
			JOIN tag_files AS tf
				ON (tftc.tag_file_id = tf.id)
		WHERE
			tftc.campaign_id = ?
		";

		$get_tag_files_info_results = $this->db->query($get_tag_files_info_query, $campaign_id);
		if($get_tag_files_info_results == false)
		{
			return false;
		}
		if($get_tag_files_info_results->num_rows() == 1)
		{
			return $get_tag_files_info_results->row_array();
		}
		return false;
	}
	
	public function update_tracking_tag_file_info($source_table, $io_advertiser_id, $tracking_tag_file_id)
	{		
		$bindings = array(			
			$source_table,
			$io_advertiser_id,
			$tracking_tag_file_id
		);
		
		$update_tag_file_query = "UPDATE
						tag_files
					SET
						source_table = ?, io_advertiser_id = ?
					WHERE
						id = ?
					";
		
		return $this->db->query($update_tag_file_query, $bindings);		
	}
	
	public function check_and_insert_tag_for_lift_pixel($insert_array)
	{
		$result_arr = array("status" => "fail");
		
		if ($insert_array[3] == 0 || $insert_array[3] == 2)
		{
			//Check if lift pixel is already present for the file.
			$binding_array = array($insert_array[2]);
			$query = "
				SELECT
					tc.id
				FROM
					tag_codes tc
				WHERE
					tc.tag_type = 3
				AND
					lower(tc.tag_code) like '%adservices.brandcdn%'
				AND
					tc.tag_file_id = ?
				";
			$result =  $this->db->query($query, $binding_array);
			
			//If lift pixel not found, create new one.
			if ($result->num_rows() == 0)
			{
				$advertiser_id = $this->get_io_advertiser_by_tag_file_id($insert_array[2]);
				$tag_code = '<script type="text/javascript">var adv_id='
							.$advertiser_id.',s=document.createElement("script");s.type="text/javascript",'
							.'s.src="//adservices.brandcdn.com/pixel/cv_pixel.js",'
							.'s.style.display="none",document.head.appendChild(s);</script>';
				$binding_array = array();
				$binding_array[] = $tag_code;
				$binding_array[] = $insert_array[1];
				$binding_array[] = $insert_array[2];
				
				$sql = "INSERT INTO tag_codes (tag_code, username, tag_file_id, tag_type) VALUES (?, ?, ?, 3)";
				$query = $this->db->query($sql, $this->db->escape($binding_array));

				if ($this->db->insert_id() > 0)
				{
					$result_arr["status"] = "success";					
				}				
			}
		}
		
		return $result_arr;
	}
	
	public function get_io_advertiser_by_tag_file_id($tag_file_id)
	{
		$binding = array();
		$binding[]=$tag_file_id;
		
		$query = "
				SELECT
					tf.io_advertiser_id
				FROM
					tag_files AS tf
				WHERE
					tf.id = ?";
		$result = $this->db->query($query, $binding);
		
		if ($result->num_rows() > 0)
		{
			$adv_result = $result->row_array(); 
			return $adv_result['io_advertiser_id'];
		}
		
		return false;	    
	}
}
