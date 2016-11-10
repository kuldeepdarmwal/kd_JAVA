<?php

class Cup_model extends CI_Model {

	public function __construct()
	{
		$this->load->database();
		$this->load->model('variables_model');
		$this->load->model('geo_in_ads_model');
		$this->load->library('vl_aws_services');
	}
	//get all assets with size(dimensions) from creatives table
	public function get_assets_by_adset($version, $creative_size = "%")
	{
		if($version == '0' or $version == 'new')
		{
			return false;
		}
		else
		{
			$sql = 'SELECT * FROM cup_versions WHERE id = ?';
			$query = $this->db->query($sql, $version);
			$adset_result = $query->row_array();

			$sql = '
				SELECT
					b.type as type,
					b.open_uri as open_uri,
					b.ssl_uri as ssl_uri,
					b.weight as weight,
					b.is_archived as is_archived,
					b.extension as extension,
					a.size as size,
					b.updated_timestamp > c.published_timestamp AS modified_since_publish
				FROM cup_creatives a
				RIGHT JOIN cup_ad_assets b
					ON a.id = b.creative_id
				JOIN cup_versions c
					ON a.version_id = c.id
				WHERE (a.version_id = ? AND a.size LIKE ?) OR b.id IN (?, ?, ?)';
			$query = $this->db->query($sql, array($adset_result['id'], $creative_size, $adset_result['variables_js'], $adset_result['variables_xml'], $adset_result['fullscreen']));
			$asset_result = $query->result_array();

			// ///append builder version and variables obj to this
			if(!is_null($adset_result['builder_version'])){
				array_push($asset_result, array('type'=>'variables_data',
												'open_uri'=>'',
												'ssl_uri'=>'',
												'weight'=>'',
												'is_archived'=>'',
												'extension'=>'',
												'size'=>''));
			}
			return $asset_result;
		}
	}

	public function get_builder_version_by_version_id($version_id)
	{
		$sql = 'SELECT builder_version FROM cup_versions AS cv WHERE cv.id = ?';
		$query = $this->db->query($sql, $version_id);
		if($query->num_rows() > 0)
		{
			$row = $query->row_array();
			return $row['builder_version'];
		}
		return false;
	}

	public function list_adsets()
	{
		$sql = 'SELECT * from cup_adsets WHERE 1 ORDER BY id DESC';
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}

	public function insert_adset($name, $campaign_id, $user_id, $show_for_io=0)
	{
		$sql = 'INSERT IGNORE INTO cup_adsets (name, campaign_id) VALUES (?, ?)';
		$query = $this->db->query($sql, $this->db->escape(array($name, $campaign_id)));
		if($this->db->affected_rows() > 0)
		{
			$adset_id = mysql_insert_id();
			$first_variation_id = null;
			$first_variation_data = null;

			$binding_array = array($adset_id, $user_id, $user_id, $show_for_io, $first_variation_id, '0', $campaign_id);
			$sql = '
				INSERT INTO
					cup_versions
					(
						adset_id,
						version,
						created_user,
						created_timestamp,
						updated_user,
						show_for_io,
						parent_variation_id,
						variation_name,
						campaign_id
					)
					VALUES
					(
						?,
						1,
						?,
						CURRENT_TIMESTAMP,
						?,
						?,
						?,
						?,
						?
					)';
			$query = $this->db->query($sql, $binding_array);

			$version_id = mysql_insert_id();
			if($first_variation_id == null)
			{
				$first_variation_id = $version_id;
			}
			$base_64_id = base64_encode(base64_encode(base64_encode($version_id)));

			$adset_sql = '
					SELECT
						id,
						name
					FROM
						cup_adsets
					WHERE
						id = ?';
			$adset_response = $this->db->query($adset_sql, $adset_id);

			if($adset_response->num_rows() > 0)
			{
				$adset_data = $adset_response->row_array();

				$vanity_string = url_title($adset_data['name']).'v1-0';

				$update_sql = '
					UPDATE
						cup_versions
					SET
						base_64_encoded_id = ?,
						vanity_string = ?
					WHERE id = ?';
				$update_query = $this->db->query($update_sql, array($base_64_id, $vanity_string, $version_id));
				if($update_query)
				{
					return array("adset"=>$adset_data, "version"=>$version_id);
				}
			}
		}
		return false;
	}

	//make some creatives records if they dont exist, however we do not want to overwrite or create new creatives if old ones exist because the ids matter
	public function find_replace_creatives($adset, $version)
	{
		// TODO: create 468x60 only if files were uploaded?
		$size_array = array(
			"size" => array('160x600', '300x250', '336x280', '728x90', '320x50', '468x60'),
			"missing" => array('160x600' => 1, '300x250' => 1, '336x280' => 1, '728x90' => 1, '320x50' => 1, '468x60' => 1)
			);
		$sql = "SELECT * FROM cup_creatives WHERE version_id = ?";
		$query = $this->db->query($sql, $version);
		if($query->num_rows() > 0)
		{ //if we have at least 1 creatives record
			$creative_result = $query->result_array();
			foreach($creative_result as $v)
			{
				if(in_array($v['size'], $size_array['size']))
				{
					$size_array["missing"][$v['size']] = 0;
				}
			}
			foreach($size_array["missing"] as $k => $v)
			{
				if($v == 1)
				{
					$sql = "INSERT INTO cup_creatives (size, version_id) VALUES (?, ?)";
					$query = $this->db->query($sql, array($k, $version));
				}
			}
		}
		else
		{ //we have no creatives, create a new set of them (one for each size)
			foreach($size_array["size"] as $v)
			{
				$sql = "INSERT INTO cup_creatives (size, version_id) VALUES (?, ?)";
				$query = $this->db->query($sql, array($v, $version));
			}
		}
	}

	//takes array of new assets to create and replaces them into the database
	public function update_assets($asset_array, $adset, $version, $user_id)
	{
		$sql = "SELECT id, size FROM cup_creatives WHERE version_id = ?";
		$query = $this->db->query($sql, $version);
		$creatives = $query->result_array();
		$ids = array();
		foreach($creatives as $v)
		{
			$ids[$v['size']] = $v['id'];
		}

		foreach($asset_array as $k => $v)
		{
			if($k == 'variables_xml' or $k == 'variables_js' or $k == 'fullscreen')
			{
				$sql = 'REPLACE INTO cup_ad_assets (type, open_uri, ssl_uri, weight, extension) VALUES (?,?,?,?,?)';
				$query = $this->db->query($sql, array($v['type'], $v['open_uri'], $v['ssl_uri'], $v['weight'], $v['extension']));
				$insert_id = $this->db->insert_id();
				$binding_array = array($insert_id, $user_id, $version);
				$sql = 'UPDATE cup_versions SET '.$k.' = ?, updated_user = ? WHERE id = ?';  //set variables and fullscreen id in version record
				$query = $this->db->query($sql, $binding_array);
			}
			else if(array_key_exists($v['size'], $ids))
			{
				$sql = 'REPLACE INTO cup_ad_assets (type, open_uri, ssl_uri, creative_id, weight, extension) VALUES (?,?,?,?,?,?)';
				$query = $this->db->query($sql, array($v['type'], $v['open_uri'], $v['ssl_uri'],$ids[$v['size']], $v['weight'], $v['extension']));
			}
		}
	}

	public function get_adset_by_version_id($version)
	{
		$sql = 'SELECT b.id as id FROM cup_versions a JOIN cup_adsets b ON a.adset_id = b.id WHERE a.id = ?';
		$query = $this->db->query($sql, $version);
		if($query->num_rows() > 0)
		{
			$row = $query->row_array();
			return $row['id'];
		}
		return false;
	}

	public function mark_version_published_time($version_id)
	{
		$sql = '
			UPDATE
				`cup_versions`
			SET
				`published_timestamp` = NOW()
			WHERE
				`id` = ?';
		$query = $this->db->query($sql, $version_id);
		if($this->db->affected_rows() > 0)
		{
			return true;
		}
		return false;
	}

	public function get_adsets_from_c_id($c_id)
	{
		$where_clause = ($c_id=="none")? '1' : 'campaign_id = '.$c_id;
		$sql = 'SELECT * from cup_adsets WHERE '.$where_clause.' ORDER BY id DESC';
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}

	public function get_tag_strings_for_adset($adset_id)
	{
		$query =
			"SELECT
				tc.tag_code
			FROM
				tag_files_to_campaigns tftc
			JOIN
				cup_adsets ca ON tftc.campaign_id = ca.campaign_id
			JOIN
				tag_codes tc ON tftc.tag_file_id = tc.tag_file_id
			WHERE
				tc.isActive = 1
			AND
				tc.tag_type != 1
			AND
				ca.id = ?
			";
		$result = $this->db->query($query, $adset_id);
		$query_response_array = $result->result_array();
		return $query_response_array;
	}

	public function get_creatives_by_adset($version_id)
	{
		$query = "SELECT DISTINCT size, id FROM cup_creatives WHERE version_id = ?";
		$result = $this->db->query($query, $version_id);
		$query_response_array = $result->result_array();
		return $query_response_array;
	}

	public function files_ok($assets, $size = null, $builder_version = null, $variables_tab = false, $force_html5 = false, $is_gallery = false)
	{
		if(!$is_gallery)
		{
			return true;
		}

		if(html5_creative_set::is_html5_builder_version($builder_version))
		{
			$html5_creative_set = new html5_creative_set($this, $assets, $size, $builder_version);
			// if it's not a compatible HTML5 creative, let it be evaluated below
			if($html5_creative_set->can_publish_at_least_one_size())
			{
				return true;
			}
		}
		//$required_files = array('variables_data'=>false, 'swf' => false, 'backup' => false, 'loader' => false, 'js' => false);
		$required_files = array('variables_data'=>false,'variables_xml' => false, 'variables_js' => false, 'swf' => false, 'backup' => false, 'loader' => false, 'js' => false);

		foreach($assets as $asset)
		{
			switch($asset['type'])
			{
			case 'variables':
				$required_files['variables_xml'] = true;
				break;
			case 'variables_xml':
				$required_files['variables_xml'] = true;
				break;
			case 'variables_js':
				$required_files['variables_js'] = true;
				break;
			case 'variables_data':
				$required_files['variables_data'] = true;
				break;
			case 'js':
				$required_files['js'] = true;
				break;
			case 'swf':
				$required_files['swf'] = true;
				break;
			case 'backup':
				$required_files['backup'] = true;
				break;
			case 'loader':
				$required_files['loader'] = true;
			}
		}
		if($size == '320x50')
		{
			return (($required_files['variables_data'] OR $required_files['variables_xml'] OR $required_files['variables_js']) AND (($variables_tab) ? true : $required_files['backup']) AND ($required_files['js'] OR $required_files['loader']));
		}
		else
		{
			return (($required_files['variables_data'] OR $required_files['variables_xml'] OR $required_files['variables_js']) AND (($variables_tab) ? true : $required_files['backup']) AND ($required_files['js'] OR $required_files['swf'] OR $required_files['loader']));
		}
	}

	public function prep_file_links($assets,$creative_size,$vl_campaign_id = 0)
	{
		$dimensions = explode('x',$creative_size);
		$data['creative_width'] = $dimensions[0];
		$data['creative_height'] = $dimensions[1];
		$data['is_gpa'] = false;
		$data['open_xml_file'] = '';
		$data['ssl_xml_file'] = '';
		$data['open_fullscreen_file'] = '';
		$data['ssl_fullscreen_file'] = '';
		$data['open_swf_file'] = '';
		$data['ssl_swf_file'] = '';
		$data['backup_image']= '';
		$data['open_backup_image'] = '';
		$data['ssl_backup_image'] = '';
		$data['open_gpa_image_file'] = '';
		$data['ssl_gpa_image_file'] = '';
		$data['variables_js'] = '';
		$data['open_js_file'] = '';
		$data['ssl_js_file'] = '';


		foreach($assets as $asset)
		{
			switch ($asset['type'])
			{
			case 'variables':
				$data['open_xml_file'] = $asset['open_uri'];
				$data['ssl_xml_file'] = $asset['ssl_uri'];
				break;
			case 'variables_xml':
				$data['open_xml_file'] = $asset['open_uri'];
				$data['ssl_xml_file'] = $asset['ssl_uri'];
				break;
			case 'variables_js':
				$data['variables_js'] = $asset['open_uri'];
				break;
			case 'fullscreen':
				$data['open_fullscreen_file'] = $asset['open_uri'];
				$data['ssl_fullscreen_file'] = $asset['ssl_uri'];
				break;
			case 'swf':
				$data['open_swf_file'] = $asset['open_uri'];
				$data['ssl_swf_file'] = $asset['ssl_uri'];
				break;
			case 'js':
				$data['open_js_file'] = $asset['open_uri'];
				$data['ssl_js_file'] = $asset['ssl_uri'];
				break;
			case 'backup':
				$data['backup_image']= $asset['open_uri']; // deprecated; remove with care -CL
				$data['open_backup_image']= $asset['open_uri'];
				$data['ssl_backup_image']= $asset['ssl_uri'];
				break;
			case 'loader':
				$data['open_gpa_image_file'] = $asset['open_uri'];
				$data['ssl_gpa_image_file'] = $asset['ssl_uri'];
				$data['is_gpa'] = true;
				break;
			}
		}
		$data['is_hd']= true;
		$data['vl_campaign_id'] = $vl_campaign_id;
		return $data;
	}

	public function get_creative_details($size, $version)
	{
		$sql = "SELECT * FROM cup_creatives WHERE size = ? AND version_id = ?";
		$query = $this->db->query($sql, array($size, $version));
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return null;
	}

	public function get_data_for_ad_tag($creative_id, $ad_server)
	{
		$ad_tag_field_sql = 'ad_tag';
		switch($ad_server)
		{
		case 'dfa':
			$ad_tag_field_sql = 'ad_tag';
			break;
		case 'fas':
			$ad_tag_field_sql = 'ad_tag';
			break;
		case 'adtech':
			$ad_tag_field_sql = 'adtech_ad_tag';
			break;
		default:
			die("unkown ad_server: $ad_server");
			break;
		}

		$sql = "
			SELECT
				pd.ad_choices_tag AS ad_choices_tag,
				cr.size AS ad_size,
				cr.".$ad_tag_field_sql." AS ad_tag
			FROM
				cup_creatives AS cr
				JOIN cup_versions ver
					ON cr.version_id = ver.id
				JOIN Campaigns c
					ON ver.campaign_id = c.id
				JOIN `Advertisers` a
					ON a.id = c.business_id
				JOIN users u
					ON a.sales_person = u.id
				JOIN wl_partner_details pd
					ON u.partner_id = pd.id
			WHERE
				cr.id = ?
		";

		$response = $this->db->query($sql, $creative_id);
		if($response->num_rows() > 0)
		{
			return $response->row_array();
		}
		return false;
	}

	public function get_creatives_for_version($version, $ad_server_type_id)
	{
		if(gettype($ad_server_type_id) != "integer")
		{
			die('get_creatives_for_version() expected $ad_server to be of type integer, but it\'s: '.gettype($ad_server_type_id));
		}

		$ad_server_sql = '';
		switch($ad_server_type_id)
		{
		case k_ad_server_type::all_id:
			$ad_server_sql = '
					((ad_tag IS NOT NULL) OR
					(adtech_ad_tag IS NOT NULL))
				';
			break;
		case k_ad_server_type::dfa_id:
			$ad_server_sql = '
					(ad_tag IS NOT NULL)
				';
			break;
		case k_ad_server_type::fas_id:
			$ad_server_sql = '
					(ad_tag IS NOT NULL)
				';
			break;
		case k_ad_server_type::adtech_id:
			$ad_server_sql = '
					(adtech_ad_tag IS NOT NULL)
				';
			break;
		default:
			die("unhandled ad_server_type_id: ".$ad_server_type_id);
			$ad_server_sql = '
					((ad_tag IS NOT NULL) OR
					(adtech_ad_tag IS NOT NULL))
				';
		}

		$sql = '
			SELECT * from cup_creatives
			WHERE
				version_id = ? AND
				'.$ad_server_sql.'
			';
		$query = $this->db->query($sql, $version);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}

	public function get_partner_from_campaign($c_id)
	{
		$sql = "SELECT u.partner_id, w.partner_name FROM
			Campaigns c
			LEFT JOIN `Advertisers` a
			ON a.id = c.business_id
			LEFT JOIN users u
			ON a.sales_person = u.id
			LEFT JOIN wl_partner_details w
			ON u.partner_id = w.id
			WHERE c.id = ".$c_id." ";
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return null;
	}

	public function get_partner_from_version($v_id)
	{
		$sql = "
			SELECT
				u.partner_id,
				w.partner_name
			FROM
				cup_versions ver
				LEFT JOIN Campaigns c
					ON ver.campaign_id = c.id
				LEFT JOIN `Advertisers` a
					ON a.id = c.business_id
				LEFT JOIN users u
					ON a.sales_person = u.id
				LEFT JOIN wl_partner_details w
					ON u.partner_id = w.id
			WHERE ver.id = ?";
		$query = $this->db->query($sql, $v_id);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return null;
	}

	public function get_campaign_from_version($v_id)
    {
		$sql =
		"SELECT
			 campaign_id as id
		FROM
			cup_versions
		WHERE
			id = ?";
		$query = $this->db->query($sql, $v_id);
		if($query->num_rows() > 0)
		{
			return $query->row()->id;
		}
		return null;
    }

	public function get_ad_tag_from_db($creative_id)
	{
		$sql = "SELECT ad_tag FROM cup_creatives WHERE id = ".$creative_id." ";
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return null;
	}

	public function get_adtech_ad_tag_from_db($creative_id)
	{
		$sql = "SELECT adtech_ad_tag FROM cup_creatives WHERE id = ?";
		$query = $this->db->query($sql, $creative_id);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return null;
	}

	public function get_adtech_ids_from_db($insert_array)
	{
		$sql = "SELECT adtech_flight_id, adtech_campaign_id FROM cup_creatives
			WHERE size = ? AND version_id = ?";
		$query = $this->db->query($sql, $insert_array);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return null;
	}

	public function are_creatives_in_ttd($version_id)
	{
		$sql = "SELECT ttd_creative_id FROM cup_creatives WHERE ttd_creative_id IS NOT NULL AND version_id = ".$version_id;
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			return TRUE;
		}
		return false;

	}

	public function get_adset_details($adset_id)
	{
		$sql = "SELECT  concat(adv.Name ,\": \", left_side.c_name,\" : \",left_side.as_name) as campaign, left_side.LP as LP FROM
			(SELECT adsets.name as as_name, c.business_id as advertiser_id, c.Name as c_name, c.LandingPage as LP
			FROM cup_adsets adsets
			LEFT JOIN Campaigns c
			ON adsets.campaign_id=c.id
			WHERE adsets.id = ".$adset_id.") left_side
			LEFT JOIN Advertisers adv
			ON adv.id = left_side.advertiser_id";
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return null;
	}

	public function update_creative($insert_array)
	{
		// 'ad_tag'=>$ad_tag,
		//       'dfa_advertiser_id'=>$dfa_adv_id,
		//       'dfa_campaign_id'=>$dfa_camp_id,
		//       'dfa_placement_id'=>$dfa_placement_id,
		//       'dfa_creative_id'=>$dfa_creative_id,
		//       'dfa_ad_id'=>$dfa_ad_id,
		//       'creative_size'=>$creative_size,
		//       'adset_id'=>$adset_id
		$sql =  "UPDATE cup_creatives
			SET  	ad_tag= ?,
			dfa_advertiser_id = ?,
			dfa_campaign_id = ?,
			dfa_placement_id = ?,
			dfa_creative_id=?,
			dfa_ad_id=?,
			published_ad_server=? 
			WHERE size = ? AND version_id = ?";
		$query = $this->db->query($sql, $insert_array);
		return $this->db->affected_rows();
	}

	public function update_creative_adtech_tag($insert_array)
	{
		$sql =
			"UPDATE
			cup_creatives
			SET
			adtech_ad_tag = ?
			WHERE
			size = ?
			AND version_id = ?";
		$query = $this->db->query($sql, $insert_array);
		return $this->db->affected_rows();
	}
	
	public function update_creative_fas_tag($insert_array)
	{
		$sql =
			"
			UPDATE
				cup_creatives
			SET
				ad_tag = ?,
				published_ad_server = ?
			WHERE
				size = ?
			AND
				version_id = ?";
		$query = $this->db->query($sql, $insert_array);
		return $this->db->affected_rows();
	}

	public function update_creative_adtech_details($insert_array)
	{
		$sql =
			"UPDATE
			cup_creatives
			SET
			adtech_flight_id = ?,
			adtech_campaign_id = ?
			WHERE
			size = ?
			AND version_id = ?";
		$query = $this->db->query($sql, $insert_array);
		return $this->db->affected_rows();
	}


	public function get_versions_by_adset_for_campaign($adset, $campaign)
	{
		$where_campaign_sql = "";
		if($campaign != "none" && !empty($campaign))
		{
			$where_campaign_sql = "ver.campaign_id = ? AND";
			$bindings[] = $campaign_id;
		}
		$sql =
		"SELECT
			*
		FROM
			cup_versions
		WHERE
			".$where_campaign_sql."
			adset_id = ?
		GROUP BY
			version
		ORDER BY
			version DESC";
		$query = $this->db->query($sql, $adset);
		return $query->result_array();
	}


	public function insert_version($adset, $user_id, $campaign_id)
	{
		$sql =
			"SELECT
			MAX(cpv.version) as max,
			cpa.name as adset_name
			FROM
			cup_versions cpv
			JOIN cup_adsets cpa
			ON cpv.adset_id = cpa.id
			WHERE
			cpa.id = ?";
		$query = $this->db->query($sql, $adset);
		$temp_result = $query->row_array();
		$max = $temp_result['max'];
		$adset_name = $temp_result['adset_name'];
		$vanity_string = url_title($adset_name).'v'.($max+1);
		$binding_array = array($adset, $max+1, $user_id, $user_id, $vanity_string, $campaign_id);
		$sql = "
			INSERT INTO
				cup_versions
				(
					adset_id,
					version,
					created_user,
					created_timestamp,
					updated_user,
					vanity_string,
					variation_name,
					campaign_id
				)
				VALUES
				(
					?,
					?,
					?,
					CURRENT_TIMESTAMP,
					?,
					?,
					'0',
					?
				)";
		$query = $this->db->query($sql, $binding_array);
		if($this->db->affected_rows() > 0)
		{
			$insert_id = $this->db->insert_id();
			$base_64_id = base64_encode(base64_encode(base64_encode($insert_id)));

			$update_sql = "
				UPDATE
					cup_versions
				SET
					base_64_encoded_id = ?
				WHERE
					id = ?";
			$this->db->query($update_sql, array($base_64_id, $insert_id));

			return $insert_id;
		}
		return false;

	}
	public function get_partner_header_img($version)
	{
		$sql = "
			SELECT
				a.*
			FROM
				wl_partner_details a
				LEFT JOIN users b ON a.id = b.partner_id
				LEFT JOIN Advertisers c ON b.id = c.sales_person
				LEFT JOIN Campaigns d on c.id = d.business_id
				LEFT JOIN cup_adsets e ON d.id = e.campaign_id
				LEFT JOIN cup_versions f ON e.id = f.adset_id
			WHERE
				f.id = ?
		";

		$query = $this->db->query($sql, $version);
		if($query->num_rows() > 0)
		{
			$temp = $query->row_array();
			if(is_null($temp['header_img']))
			{
				return false;
			}
			return $temp;
		}
		return false;
	}
	public function can_publish_version($version)
	{
		$sql = "SELECT variables_xml, variables_js FROM cup_versions WHERE id = ?";
		$query = $this->db->query($sql, $version);
		if($query->num_rows() > 0)
		{
			$variables_array = $query->row_array();
			$sql = "SELECT a.*, b.size as size FROM cup_ad_assets a LEFT JOIN cup_creatives b ON a.creative_id = b.id WHERE b.version_id = ? OR a.id IN (?, ?)";
			$query = $this->db->query($sql, array($version, $variables_array['variables_xml'], $variables_array['variables_js']));
			if($query->num_rows() > 0)
			{
				return $query->result_array();
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	public function get_campaign_for_adset($adset_id)
	{
		$sql = "
			SELECT
				ca.id AS id,
				CONCAT(ad.Name, ' : ', ca.Name) AS text,
				ca.Name AS campaign,
				ad.Name AS advertiser
			FROM
				cup_adsets AS cad
				JOIN Campaigns AS ca
					ON cad.campaign_id = ca.id
				JOIN Advertisers AS ad
					ON ca.business_id = ad.id
			WHERE
				cad.id = ?";
		$query = $this->db->query($sql, $adset_id);
		if($query->num_rows() > 0)
		{
			return $query->row_array();
		}
		return array();
	}

	public function get_campaign_for_variation($variation_id)
	{
		$sql = "
			SELECT
				ca.id AS id,
				CONCAT(ad.Name, ' : ', ca.Name) AS text,
				ca.Name AS campaign,
				ad.Name AS advertiser
			FROM
				cup_versions AS ver
				JOIN Campaigns AS ca
					ON ver.campaign_id = ca.id
				JOIN Advertisers AS ad
					ON ca.business_id = ad.id
			WHERE
				ver.id = ?";
		$query = $this->db->query($sql, $variation_id);
		if($query->num_rows() > 0)
		{
			return $query->row_array();
		}
		return array();
	}

	public function link_version_to_campaign($version_id, $campaign_id)
	{
		$update_version_campaign_sql =
			"UPDATE
			cup_versions
			SET
			campaign_id = ?
			WHERE
			id = ?";
		$update_version_campaign_result = $this->db->query($update_version_campaign_sql, array($campaign_id, $version_id));
		if($this->db->affected_rows() > 0)
		{
			return true;
		}
		return false;
	}

	public function does_adset_have_campaign($adset)
	{
		$sql = "SELECT campaign_id FROM cup_adsets WHERE campaign_id IS NOT NULL AND id = ".$adset;
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			return true;
		}
		return false;
	}

	public function does_version_have_campaign($version)
	{
		$sql =
		"SELECT
			campaign_id
		FROM
			cup_versions
		WHERE
			campaign_id IS NOT NULL
			AND id = ?";
		$query = $this->db->query($sql, $version);
		if($query->num_rows() > 0)
		{
			return true;
		}
		return false;
	}

	private function recursive_dashes($string)
	{
		if(!strpos($string, "--"))
		{
			return $string;
		}
		else
		{
			return $this->recursive_dashes(str_replace("--", "-", $string));
		}
	}



	public function get_adset_ids_from_adset_version_id($adset_version_id){
		$sql = "SELECT c_a.campaign_id, c_a.id
				FROM cup_versions as c_v
				JOIN cup_adsets as c_a
				ON c_v.adset_id = c_a.id
				WHERE c_v.id =  ".$adset_version_id;
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			$result = $query->result_array();
			return $result;
		}
		return null;

	}

	public function get_ad_html( $version, $creative_size, $is_publish = false, $ad_server_type = k_ad_server_type::dfa_id, $return_as_js = FALSE)
	{
		//copy of the ad function, private to creative_uploader
		if(($creative_size != '') && ($version != ''))
		{
			$assets = $this->cup_model->get_assets_by_adset($version, $creative_size);
			$data = $this->cup_model->prep_file_links($assets,$creative_size);

			//we need the vl creative id to inject into the html for interactions
			$vl_creative_record = $this->cup_model->get_creative_details($creative_size, $version);
			$data['vl_creative_id'] = $vl_creative_record[0]['id'];
			$data['tracking_off'] = $is_publish? false : true;
			$data['ad_server_type'] = $ad_server_type;
			$data['return_as_js'] = $return_as_js;

			$campaign_and_advertiser_details = $this->cup_model->get_campaign_and_advertiser_by_version_id($version);
			if ($campaign_and_advertiser_details != null)
			{
				$data['campaign_id'] = $campaign_and_advertiser_details['campaign_id'];
				$data['advertiser_id'] = $campaign_and_advertiser_details['advertiser_id'];
			}

			if(!$is_publish){
				$data['no_engage'] = true;///for some reason the blob is looking if this is set?
			}
			if(constant('ENVIRONMENT') !== 'production')
			{
				$this->cup_model->get_dev_tracking_pixel_addresses($data);
			}
			$adset_array = $this->variables_model->get_adset_by_id($version);
			$data['variables_data_obj'] = json_encode($adset_array[0]['variables_data']);

			if(html5_creative_set::is_html5_builder_version($adset_array[0]['builder_version']))
			{
				$html5_creative_set = new html5_creative_set($this, $assets, $creative_size, $adset_array[0]['builder_version']);
				$creative_markup = $html5_creative_set->get_creative_markup_for_size($creative_size);
				$data['html5_initialization'] = $creative_markup['initialization'];
				$data['html5_setup'] = $creative_markup['setup'];
			}

			if(!is_null($adset_array[0]['builder_version'])){//if this adset doesn't have a builder version - don't set the variables data type
				$versions_array = $this->variables_model->get_versions_from_builder_version($adset_array[0]['builder_version']);
				$blob_version = $versions_array[0]['blob_version'];
				$data['gpa_version'] = $versions_array[0]['rich_grandpa_version'];
			}else{
				$blob_version = '004';
			}


			if($this->files_ok($assets, $creative_size, $adset_array[0]['builder_version']))
			{
				if(isset($version) && $version != '' && $version != '0' && strpos($data['variables_data_obj'],"dynamicGeoDefault"))
				{
					$data['dynamic_geo_default'] = TRUE;
					$messages_data = $this->geo_in_ads_model->get_messages_data_for_adset_version($version);

					if ($messages_data)
					{
						$data['messages_data'] = $messages_data;
					}
				}

				$ad_html = $this->load->view('dfa/vl_hd_view_'.$blob_version,$data, true);
				return $ad_html;
			}
			return false;
		}
		return false;
	}

	public function set_adchoices_status_for_version($v_id, $is_using_adchoices, $adserver)
	{
		if($adserver == "dfa")
		{
			$chosen_adserver = "dfa";
		} elseif($adserver == "adtech")
		{
			$chosen_adserver = "adtech";
		}
		else
		{
			return false;
		}
		$sql = "UPDATE cup_creatives SET ".$chosen_adserver."_used_adchoices = ? WHERE version_id = ?";
		$insert_array = array("used_adchoices"=>$is_using_adchoices, "version_id"=>$v_id);
		$query = $this->db->query($sql,$insert_array);
		if($this->db->affected_rows() > 0)
		{
			return TRUE;
		}
		return false;

	}

	public function does_version_exist($v_id)
	{
	    $sql = "SELECT id FROM cup_versions WHERE id = ?";
	    $query = $this->db->query($sql, $v_id);
	    if($query->num_rows() == 1)
	    {
		return $query->row_array();
	    }
	    return false;
	}
	public function get_adsets_for_select2($search_term, $start, $limit, $campaign_id)
	{
		$bindings = array();
		$where_campaign_sql = "";
		if($campaign_id != "none" && !empty($campaign_id))
		{
			$where_campaign_sql = "ver.campaign_id = ? AND";
			$bindings[] = $campaign_id;
		}

		$bindings[] = $search_term;
		$bindings[] = $start;
		$bindings[] = $limit;

		$query = "
				SELECT
					cad.id AS id,
					cad.name AS text
				FROM
					cup_adsets AS cad
					JOIN cup_versions AS ver
						ON (cad.id = ver.adset_id)
				WHERE
					".$where_campaign_sql."
					cad.name LIKE ?
				GROUP BY
					cad.id
				ORDER BY
					cad.id DESC
				LIMIT ?, ?";
		$response = $this->db->query($query, $bindings);
		if($response->num_rows() > 0)
		{
			return $response->result_array();
		}
		return array();

	}

	public function get_campaigns_for_select2($search_term, $start, $limit)
	{
		$query = "
				SELECT
					ca.id AS id,
					CONCAT(ad.Name, ' : ', ca.Name) AS text,
					ca.Name AS campaign_name,
					ad.Name AS advertiser_name
				FROM
					Campaigns AS ca
					JOIN Advertisers AS ad
						ON ca.business_id = ad.id
				WHERE
					ca.Name LIKE ? OR
					ad.Name LIKE ?
				ORDER BY
					ca.id DESC
				LIMIT ?, ?";
		$response = $this->db->query($query, array($search_term, $search_term, $start, $limit));
		if($response->num_rows() > 0)
		{
			return $response->result_array();
		}
		return array();
	}

	public function get_campaign_adset_version_data_by_version_id($version_id)
	{
		$query = "
				SELECT
					cvn.id AS version_id,
					cvn.parent_variation_id AS variation_parent_id,
					cvn.version AS version_number,
					cad.id AS adset_id,
					cad.name AS adset_name
				FROM
					cup_versions AS cvn
					JOIN cup_adsets AS cad
						ON cvn.adset_id = cad.id
				WHERE
					cvn.id = ?";
		$result = $this->db->query($query, $version_id);
		if($result->num_rows() > 0)
		{
			return $result->row_array();
		}
		return array();
	}

	public function get_dev_tracking_pixel_addresses(&$data)
	{
		$data['dev_tracking_pixel_address'] = 'http://61c349ad500e1375f385-f209909a213f941a7b3901f342f0afdc.r4.cf1.rackcdn.com/px.gif';
		$data['dev_tracking_pixel_address_ssl'] = 'https://cbd0aba6abd314510b90-f209909a213f941a7b3901f342f0afdc.ssl.cf1.rackcdn.com/px.gif';
		return $data;
	}

	public function get_advertiser_by_campaign_id($campaign_id)
	{
		$query = "
				SELECT
					ad.sales_person AS sales_person
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
			$row_result = $result->row_array();
			return $row_result['sales_person'];
		}

		return false;

	}

	public function save_show_for_io_value_for_adset_version($adset_version_id, $show_for_io)
	{
		if($show_for_io == 1)
		{
			$user_name = $this->tank_auth->get_username();
			$current_date_time = date('Y-m-d H:i:s');
			$binding_array = array($user_name, $current_date_time, $show_for_io, $adset_version_id);
			$query = "
					UPDATE
						cup_versions AS cupv
					SET
						cupv.internally_approved_updated_user = ?,
						cupv.internally_approved_updated_timestamp = ?,
						cupv.show_for_io = ?
					WHERE
						cupv.id = ?";
			$this->db->query($query, $binding_array);
		}
		else
		{
			$binding_array = array($show_for_io, $adset_version_id);
			$query = "
					UPDATE
						cup_versions AS cupv
					SET
						cupv.show_for_io = ?
					WHERE
						cupv.id = ?";

			$this->db->query($query, $binding_array);
		}
		if ($this->db->affected_rows() > 0)
		{
			return true;
		}

		return false;
	}


	public function get_show_for_io_value_for_adset_version($adset_version_id)
	{
		$query = "
				SELECT
					cupv.show_for_io,
					usr.username,
					DATE_FORMAT(cupv.updated_timestamp,'%Y-%m-%d') AS updated_date,
					cupv.internally_approved_updated_user as internally_approved_updated_user,
					cupv.internally_approved_updated_timestamp as internally_approved_updated_timestamp
				FROM
					cup_versions AS cupv
				JOIN
					users AS usr
				ON
					cupv.updated_user = usr.id
				WHERE
					cupv.id = ?";

		$result = $this->db->query($query, $adset_version_id);

		if ($result->num_rows() > 0)
		{
			return $result->row_array();
		}

		return false;
	}

	public function get_gallery_link_vanity_info($version_id)
	{
		$sql = "
			SELECT
				cpv.id,
				cpv.version,
				cpa.name
			FROM
				cup_versions cpv
				JOIN cup_adsets cpa
					ON cpv.adset_id = cpa.id
			WHERE
				cpv.id = ?";
		$query = $this->db->query($sql, $version_id);
		if($query->num_rows() > 0)
		{
			$result = $query->row_array();
			return $result;
		}
		return false;
	}

	public function get_campaign_and_advertiser_by_version_id($v_id)
	{
		$sql = "SELECT
				cmp.id AS campaign_id,
				cmp.business_id AS advertiser_id
			FROM
				cup_versions AS cv
			JOIN
				cup_adsets AS ca
			ON
				cv.adset_id = ca.id
			JOIN
				Campaigns AS cmp
			ON
				ca.campaign_id = cmp.id
			WHERE
				cv.id = ?";

		$query = $this->db->query($sql,$v_id);

		if ($query->num_rows() > 0)
		{
			$result = array();
			$result['campaign_id'] = $query->row()->campaign_id;
			$result['advertiser_id'] = $query->row()->advertiser_id;
			return $result;
		}

		return null;
	}

	public function get_version_banner_intake_cname($version_id)
	{
		$get_banner_intake_cname_query =
		"SELECT
			wlp.cname AS cname
		FROM
			cup_versions AS ver
			JOIN cup_adsets AS ads
				ON ver.adset_id = ads.id
			JOIN adset_requests AS ar
				ON ads.id = ar.adset_id
			JOIN users AS u
				ON ar.creative_request_owner_id = u.id
			JOIN wl_partner_details AS wlp
				ON u.partner_id = wlp.id
		WHERE
			ver.id = ?
		";

		$get_banner_intake_cname_result = $this->db->query($get_banner_intake_cname_query, $version_id);
		if($get_banner_intake_cname_result == false || $get_banner_intake_cname_result->num_rows() < 1)
		{
			return false;
		}
		$row = $get_banner_intake_cname_result->row_array();
		return $row['cname'];
	}

	public function get_variations_by_version_for_campaign($version_id, $campaign_id)
	{
		$where_campaign_sql = "";
		$bindings = array();
		if($campaign_id != "none" && !empty($campaign_id))
		{
			$where_campaign_sql = "campaign_id = ? AND";
			$bindings[] = $campaign_id;
		}
		$sql =
		"SELECT
			*
		FROM
			cup_versions
		WHERE
			".$where_campaign_sql."
			(id = ?
			OR parent_variation_id = ?)
		ORDER BY
			version DESC";
		$bindings[] = $version_id;
		$bindings[] = $version_id;
		$query = $this->db->query($sql, $bindings);
		return $query->result_array();
	}

	public function insert_new_variation($user_id, $adset_id, $parent_version, $variation_name, $campaign_id)
	{
		$return_array = array('success' => true, 'data' => null, 'err_msg' => null);

		if(!$this->is_variation_name_unique_for_version($variation_name, $parent_version))
		{
			$return_array['success'] = false;
			$return_array['err_msg'] = "Error #427280: Duplicate variation name.";
			return $return_array;
		}

		$bindings = array($user_id, $parent_version, $variation_name, $campaign_id, $parent_version);
		$insert_variation_query =
		"INSERT INTO
			cup_versions
			(
				adset_id,
				version,
				created_user,
				created_timestamp,
				show_for_io,
				parent_variation_id,
				variation_name,
				campaign_id
			)
			(SELECT
				adset_id,
				version,
				?,
				CURRENT_TIMESTAMP,
				0,
				?,
				?,
				?
			FROM
				cup_versions
			WHERE
				id = ?)
		";

		$insert_variation_result = $this->db->query($insert_variation_query, $bindings);
		if(!$insert_variation_result)
		{
			$return_array['success'] = false;
			$return_array['err_msg'] = "Error #432119: Failed to create variation.";
			return $return_array;
		}

		$variation_id = mysql_insert_id();
		$base64_variation_id = base64_encode(base64_encode(base64_encode($variation_id)));

		$adset_sql =
		"SELECT
			ads.id AS id,
			ads.name AS name
		FROM
			cup_adsets AS ads
			JOIN cup_versions AS ver
				ON ads.id = ver.adset_id
		WHERE
			ver.id = ?";
		$adset_response = $this->db->query($adset_sql, $variation_id);
		if($adset_response->num_rows() > 0)
		{
			$adset_data = $adset_response->row_array();

			$vanity_string = url_title($adset_data['name']).'v1-'.$variation_name;

			$update_sql = "
				UPDATE
					cup_versions
				SET
					base_64_encoded_id = ?,
					vanity_string = ?
				WHERE id = ?";
			$update_query = $this->db->query($update_sql, array($base64_variation_id, $vanity_string, $variation_id));
		}
		$return_array['data'] = $variation_id;
		return $return_array;
	}

	public function rename_variation($variation_id, $new_name, $parent_version)
	{
		$return_array = array('success' => true, 'err_msg' => null);

		if(!$this->is_variation_name_unique_for_version($new_name, $parent_version))
		{
			$return_array['success'] = false;
			$return_array['err_msg'] = "Error #427280: Duplicate variation name.";
			return $return_array;
		}

		$rename_variation_query =
		"UPDATE
			cup_versions
		SET
			variation_name = ?
		WHERE
			id = ?
		";

		$bindings = array($new_name, $variation_id);
		$rename_variation_result = $this->db->query($rename_variation_query, $bindings);
		if($rename_variation_result == false)
		{
			$return_array['success'] = false;
			$return_array['err_msg'] = "Error #427280: Failed to update variation name.";
			return $return_array;
		}
		return $return_array;
	}

	private function is_variation_name_unique_for_version($variation_name, $parent_version_id)
	{
		$verify_duplicate_name_query =
		"SELECT
			*
		FROM
			cup_versions
		WHERE
			parent_variation_id = ?
			AND variation_name LIKE ?
		";
		$bindings = array($parent_version_id, $variation_name);
		$verify_duplicate_name_result = $this->db->query($verify_duplicate_name_query, $bindings);
		if($verify_duplicate_name_result == false || $verify_duplicate_name_result->num_rows() > 0)
		{
			return false;
		}
		return true;

	}

	public function generate_moat_tag_for_campaign_with_ad_size($f_campaign_id, $ad_size)
	{
		$moat_tag_html = "";
		$get_moat_tag_details_query =
		"SELECT
			cmp.Name AS campaign_name,
			tsa.ul_id AS ul_id
		FROM
			Campaigns AS cmp
			JOIN Advertisers AS adv
				ON (cmp.business_id = adv.id)
 			JOIN users AS u
 				ON (adv.sales_person = u.id)
 			JOIN wl_partner_details AS wlp
 				ON (u.partner_id = wlp.id)
 			JOIN wl_partner_hierarchy AS wlh
 				ON (wlp.id = wlh.descendant_id)
			LEFT JOIN tp_spectrum_accounts AS tsa
				ON (adv.id = tsa.advertiser_id)
		WHERE
			cmp.id = ?
			AND wlh.ancestor_id = 3";

		$get_moat_tag_details_result = $this->db->query($get_moat_tag_details_query, $f_campaign_id);
		if($get_moat_tag_details_result == false)
		{
			return false;
		}
		else
		{
			if($get_moat_tag_details_result->num_rows() > 0)
			{
				$row = $get_moat_tag_details_result->row_array();
				$ul_id = empty($row['ul_id']) ? "NO_UL_ID" : $row['ul_id'];
				$campaign_name = urlencode($row['campaign_name']);
				$moat_tag_html = '<noscript class="MOAT-spectrumreachdisplay809387421460?moatClientLevel1=FREQ&amp;moatClientLevel2='.$ul_id.'&amp;moatClientLevel3='.$campaign_name.'&amp;moatClientLevel4='.$ad_size.'"></noscript><script src="https://z.moatads.com/spectrumreachdisplay809387421460/moatad.js#moatClientLevel1=FREQ&moatClientLevel2='.$ul_id.'&moatClientLevel3='.$campaign_name.'&moatClientLevel4='.$ad_size.'" type="text/javascript"></script>';
			}
		}
		return $moat_tag_html;
	}
}

/**
 * A set of several sizes of HTML5 creatives
 */
class html5_creative_set
{

	protected $creatives = [];
	protected $other_assets = [];
	protected $data_for_multiple_replace = null;
	protected $selected_builder_version = null;

	/**
	 * @param $assets_or_directory mixed: array of assets, or string absolute path to directory in which to search
	 * @param $size string WIDTHxHEIGHT, if provided, only assets of this size will be returned
	 * @param $builder_version string key of self::get_builder_version_creative_type_hash()
	 * @return array of assets and metadata
	 */
	public function __construct($ci, $assets_or_directory, $size = null, $builder_version = null)
	{
		$this->selected_builder_version = $builder_version;

		$this->creatives = []; // array of HTML5 creatives, indexed by WIDTHxHEIGHT

		$creative_types = self::get_creative_types_per_builder_version($builder_version);

		if(is_array($assets_or_directory))
		{
			$assets_by_size = [];

			foreach($assets_or_directory as $asset)
			{
				// Some "assets" don't have size, like "variables_data". Ignore them.
				if($asset['size'])
				{
					$assets_by_size[$asset['size']][] = $asset;
				}
			}

			foreach($assets_by_size as $asset_size => $assets)
			{
				if(!$size || $asset_size == $size)
				{
					foreach($assets as $index => $asset)
					{
						if($asset['type'] === 'backup' || $asset['type'] === 'loader')
						{
							$this->other_assets[$asset_size][$asset['type']] = $asset;
							unset($assets[$index]);
						}
					}

					foreach($creative_types as $creative_type)
					{
						$creative = new $creative_type($ci, $asset_size);
						$creative->build_from_assets($assets);
						if($creative->is_compatible && $creative->is_correct_size && $creative->is_complete)
						{
							$this->creatives[$creative->size] = $creative;
							break;
						}
					}
				}
			}
		}
		else
		{
			$files_to_scan = $this->scandir_recursive($assets_or_directory);

			foreach($files_to_scan as $file)
			{
				$is_html_file = !$file['is_dir'] && preg_match('/\.html$/', $file['path']);
				$backup_image_match = [];
				$is_backup_image = !$file['is_dir'] && preg_match('/backup_([0-9]+x[0-9]+)/', basename($file['path']), $backup_image_match);

				if($is_html_file)
				{
					$html_contents = file_get_contents($file['path']);

					$children_files = $this->get_children_files_in_array($files_to_scan, dirname($file['path']));

					foreach($creative_types as $creative_type)
					{
						if(strpos($html_contents, $creative_type::MODIFIED_LIVE_HTML_IDENTIFYING_COMMENT) === false)
						{
							$creative = new $creative_type($ci);
							$creative->build_from_html($file['path'], $html_contents, $children_files);
							if($creative->is_compatible && $creative->is_correct_size)
							{
								$this->creatives[$creative->size] = $creative;
								break;
							}
						}
					}
				}
				// find backup any staged images
				elseif($is_backup_image)
				{
					$this->other_assets[$backup_image_match[1]]['backup'] = [
						'type' => 'backup',
						'full_path' => $file['path']
					];
				}
			}
		}
	}

	static function get_builder_version_creative_type_hash()
	{
		return [
			'80' => ['html5_createjs_rich_media_creative'],
			'402' => ['html5_createjs_2015_2_creative', 'html5_createjs_creative'],
			'302' => ['html5_generic_creative'],
			'401' => ['html5_createjs_2015_2_creative', 'html5_createjs_creative'],
			'301' => ['html5_generic_creative'],
			'400' => ['html5_createjs_creative'],
			'300' => ['html5_generic_creative'],
		];
	}

	static function get_creative_types_per_builder_version($selected_builder_version = null)
	{
		$creative_types_by_builder_version = self::get_builder_version_creative_type_hash();
		$selected_creative_types = [];
		if($selected_builder_version && !self::is_html5_builder_version($selected_builder_version))
		{
			$selected_builder_version = null;
		}
		foreach($creative_types_by_builder_version as $builder_version => $creative_types)
		{
			if(!$selected_builder_version || $builder_version == $selected_builder_version)
			{
				$selected_creative_types = array_merge($selected_creative_types, $creative_types);
			}
		}
		return $selected_creative_types;
	}

	static function get_html5_builder_versions()
	{
		return array_keys(self::get_builder_version_creative_type_hash());
	}

	static function get_internal_rich_media_builder_versions()
	{
		return array_keys(array_filter(self::get_creative_types_per_builder_version(), function($creative_type) {
			$is_internal_rich_media = defined("$creative_type::IS_INTERNAL_RICH_MEDIA") ? $creative_type::IS_INTERNAL_RICH_MEDIA : false;
			return $is_internal_rich_media;
		}));
	}

	static function is_html5_builder_version($builder_version)
	{
		$html5_builder_versions = self::get_html5_builder_versions();
		return array_search($builder_version, $html5_builder_versions) !== false;
	}

	static function is_internal_rich_media_builder_version($builder_version)
	{
		$internal_rich_media_builder_versions = self::get_internal_rich_media_builder_versions();
		return array_search($builder_version, $internal_rich_media_builder_versions) !== false;
	}

	public function get_recommended_builder_version()
	{
		$recommended_builder_version = false;
		$creative_types = $this->get_creative_types();
		if(count($creative_types))
		{
			foreach($creative_types as $creative_type)
			{
				foreach(self::get_builder_version_creative_type_hash() as $builder_version_for_this_creative_type => $creative_types_for_this_builder_version)
				{
					foreach($creative_types_for_this_builder_version as $one_creative_type_for_this_builder_version)
					if($one_creative_type_for_this_builder_version == $creative_type)
					{
						$recommended_builder_version = max($recommended_builder_version, $builder_version_for_this_creative_type);
					}
				}
			}
		}

		return $recommended_builder_version;
	}

	/**
	 * @param $size string WIDTHxHEIGHT
	 * @return string markup, or false if no creative of that size exists
	 */
	public function get_creative_markup_for_size($size)
	{
		if(isset($this->creatives[$size]))
		{
			return $this->creatives[$size]->get_markup();
		}
		else
		{
			return false;
		}
	}

	/**
	 * @param $version_id string
	 * @param $callable callable (see html5_creative:load_assets_to_cdn);
	 * @return array assets
	 */
	public function load_all_assets_to_cdn($version_id, $callable)
	{
		$assets = [];
		$size_directory_suffix = '_' . uniqid();
		foreach($this->creatives as $creative)
		{
			$assets = array_merge($assets, $creative->load_assets_to_cdn($version_id, $size_directory_suffix, $callable));
		}

		return $assets;
	}

	/**
	 * @return boolean
	 */
	public function can_publish_at_least_one_size()
	{
		$result = false;

		if(!empty($this->creatives))
		{
			foreach($this->creatives as $size => $creative)
			{
				if($creative->can_publish() && isset($this->other_assets[$creative->size]['backup']))
				{
					// at least one size can publish
					$result = true;
					break;
				}
			}
		}
		else if($this->selected_builder_version && !empty($this->other_assets))
		{
			$creative_types_for_this_builder_version = self::get_creative_types_per_builder_version($this->selected_builder_version);
			foreach($creative_types_for_this_builder_version as $creative_type)
			{
				if($creative_type::SUPPORTS_STATIC_IMAGE)
				{
					foreach($this->other_assets as $size => $assets)
					{
						// both backup and loader are required: the blob uses the loader as the static image, and the ad server requires a backup
						if(array_key_exists('backup', $assets) && array_key_exists('loader', $assets))
						{
							$result = true;
						}
					}
				}
			}
		}

		return $result;
	}

	/*
	 * @return array missing files indexed by size
	 */
	public function list_all_missing_files()
	{
		// a starting point, in case of missing/incomplete creatives
		$missing_files = [
			'160x600' => [
				'html' => 1,
				'backup' => isset($this->other_assets['160x600']['backup'])
			],
			'300x250' => [
				'html' => 1,
				'backup' => isset($this->other_assets['300x250']['backup'])
			],
			'336x280' => [
				'html' => 1,
				'backup' => isset($this->other_assets['336x280']['backup'])
			],
			'728x90' => [
				'html' => 1,
				'backup' => isset($this->other_assets['728x90']['backup'])
			]
		];

		if(!empty($this->creatives))
		{
			foreach($this->creatives as $creative)
			{
				$missing_files[$creative->size] = $creative->list_missing_files();
				$missing_files[$creative->size]['backup'] = !isset($this->other_assets[$creative->size]['backup']);
			}
		}

		return $missing_files;
	}

	/**
	 * @return array creative types
	 */
	protected function get_creative_types()
	{
		$types = [];
		foreach($this->creatives as $creative)
		{
			$types[$creative->size] = get_class($creative);
		}
		return $types;
	}

	/**
	 * @param $directory_path string
	 * @return array of files, keys 'path' (string), 'is_dir' (boolean)
	 */
	protected function scandir_recursive($directory_path)
	{
		$file_names = scandir($directory_path);

		$files = [];

		foreach($file_names as $file_name)
		{
			if($file_name !== '.' && $file_name !== '..')
			{
				$file_path = "$directory_path/$file_name";
				$file = [
					'path' => $file_path,
					'is_dir' => is_dir($file_path)
				];

				$files[] = $file;

				if($file['is_dir'])
				{
					$more_files = $this->scandir_recursive($file_path);
					$files = array_merge($files, $more_files);
				}
			}
		}

		return $files;
	}

	/**
	 * Returns files only (no directories) in $directory
	 * @param $files array as output by scandir_recursive
	 * @param $directory string
	 * @return array files in the same format as scandir_recursive
	 */
	protected function get_children_files_in_array($files, $directory)
	{
		$children_files = [];

		foreach($files as $file)
		{
			if(!$file['is_dir'] && strpos($file['path'], $directory) === 0)
			{
				$children_files[] = $file['path'];
			}
		}

		return $children_files;
	}

}

/**
 * An HTML5 creative, of one size, and its assets
 */
abstract class html5_creative
{

	public $directory;
	public $width;
	public $height;
	public $size;
	public $is_compatible;
	public $is_correct_size;
	public $is_complete;
	public $is_rigid_internal_paths = false;

	protected $html_patterns = [];
	protected $html_matches = [];
	protected $html_matches_indexed = [];
	protected $assets = [];
	protected $indexed_assets = [];

	const ABSOLUTE_URL_PATTERN = '/^https?:/';
	const MODIFIED_LIVE_HTML_BASENAME = 'live_html.html';
	const MODIFIED_LIVE_HTML_IDENTIFYING_COMMENT = '<!--live8x8296-->';
	const SUPPORTS_STATIC_IMAGE = false;

	public function __construct($ci, $size = null)
	{
		$this->size = $size;
		$this->ci = $ci;
	}

	public function build_from_html($html_path, &$html_contents = null, $files = [])
	{
		$this->directory = dirname($html_path);

		if(!$html_contents)
		{
			$html_contents = file_get_contents($html_path);
		}
		$this->add_asset([
			'type' => 'html',
			'full_path' => $html_path,
			'contents' => $html_contents
		]);

		$original_size = $this->size;

		if($this->scrape_html())
		{
			$this->is_compatible = true;
			if($this->is_correct_size = ($original_size == null || $original_size == $this->size))
			{
				// if a size has been specified, and this is not it, don't bother with any more prep
				$this->find_staged_assets($files);
			}
		}
	}

	/**
	 * @param $assets array
	 */
	public function build_from_assets($assets)
	{
		// add HTML asset first
		foreach($assets as $asset)
		{
			if($asset['type'] == 'html')
			{
				$this->add_asset($asset);
			}
		}

		if(isset($this->indexed_assets['html']))
		{
			// now add the rest
			foreach($assets as $asset)
			{
				if($asset['size'] = $this->size)
				{
					if($asset['type'] != 'html')
					{
						$this->add_asset($asset);
					}
				}
			}

			// the ad is only given assets for its size from the database, so these checks are bypassed
			$this->is_compatible = true;
			$this->is_correct_size = true;
			$this->is_complete = true;
		}

		return $this->is_compatible;
	}

	/**
	 * @param $version_id string
	 * @param $size_directory_suffix string
	 * @param $callable callable: takes the following params
	 * - @param $version_id string
	 * - @param $asset array: associative with keys:
	 * - - path string full system path to file
	 * - - path_in_creative file path relative to creative HTML file
	 * - - size string WIDTHxHEIGHT
	 * - - type string unique name per size
	 * - @return array of assets, each of which is an array has keys 'ssl_uri' and 'open_uri'
	 * @return array of values returned by $callable
	 */
	public function load_assets_to_cdn($version_id, $size_directory_suffix = '', $callable)
	{
		$loaded_assets = [];

		if($this->is_rigid_internal_paths)
		{
			$size_directory_suffix = '';
		}

		foreach($this->assets as $asset)
		{
			$loaded_assets[] = call_user_func($callable, $version_id, [
				'path' => $asset['full_path'],
				'size' => $this->size,
				'type' => $asset['type'],
				'path_in_creative' => $asset['path_in_creative'],
				'cdn_path' => "creatives/v{$version_id}/{$this->size}{$size_directory_suffix}/{$asset['path_in_creative']}"
			]);
		}

		return $loaded_assets;
	}

	/**
	 * @param $markup array of markup to be extended (with keys: 'initialization', 'setup')
	 * @return array of markup with keys: 'initialization', 'setup'
	 */
	public function get_markup($markup = [])
	{
		if(!isset($markup['initialization']))
		{
			$markup['initialization'] = '';
		}
		if(!isset($markup['setup']))
		{
			$markup['setup'] = '';
		}

		$markup['initialization'] .= "isHTML5 = !!window.CanvasRenderingContext2D;\n";

		return $markup;
	}

	/**
	 * @return boolean true if it can be published (has necessary files)
	 */
	public function can_publish()
	{
		return isset($this->indexed_assets['html']);
	}

	/**
	 * @return array of missing files indexed by type, values of (int) 1
	 */
	public function list_missing_files()
	{
		return [
			'html' => !isset($this->indexed_assets['html'])
		];
	}

	/**
	 * Search the HTML file with html_patterns
	 * @return boolean compatible HTML file has been scraped
	 */
	protected function scrape_html()
	{
		$this->html_matches = [];

		foreach($this->html_patterns as $key => $pattern_info)
		{
			if(empty($pattern_info['multiple']))
			{
				$is_matching_pattern = preg_match($pattern_info['pattern'], $this->indexed_assets['html']['contents'], $this->html_matches[$key]);
			}
			else
			{
				$is_matching_pattern = preg_match_all($pattern_info['pattern'], $this->indexed_assets['html']['contents'], $this->html_matches[$key]);
			}

			if($is_matching_pattern)
			{
				foreach($pattern_info['values'] as $name => $index)
				{
					$this->html_matches_indexed[$key][$name] = isset($this->html_matches[$key][$index]) ? $this->html_matches[$key][$index] : null;
				}
			}
			elseif(isset($pattern_info['required']) && $pattern_info['required'])
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Build indexed_assets array, keyed by the asset type. Structure will be different per implementation of this abstract class, but should always have an 'html' key.
	 */
	protected function index_assets()
	{
		$this->indexed_assets = [];

		foreach($this->assets as $asset)
		{
			$this->indexed_assets[$asset['type']] = $asset;
		}
	}

	protected function find_staged_assets(&$files = [])
	{
		// exclude dynamically generated files in staged assets
		$is_file_removed = false;
		foreach($files as $index => $file_path)
		{
			if(preg_match('/\.html$/', $file_path))
			{
				// all HTML files
				$file_contents = file_get_contents($file_path);
				if(basename($file_path) == self::MODIFIED_LIVE_HTML_BASENAME && strpos($file_contents, self::MODIFIED_LIVE_HTML_IDENTIFYING_COMMENT) !== false)
				{
					// remove live_html.html from files to use for assets
					array_splice($files, $index, 1); // remove the dynamically generated HTML file
					// reset keys
					$files = array_values($files);
					break;
				}
			}
		}

		// in overrides of this function: populate $this->assets from $files
		$this->is_complete = true;
	}

	protected function get_url($full_local_file_path)
	{

		$url = $full_local_file_path;
		if(!preg_match(self::ABSOLUTE_URL_PATTERN, $full_local_file_path))
		{
			$url = base_url($this->get_relative_path($full_local_file_path, $_SERVER['DOCUMENT_ROOT']));
		}

		return $url;
	}

	/**
	 * @param $full_path string
	 * @param $from_directory string
	 */
	protected function get_relative_path($full_path, $from_directory)
	{
		if(preg_match(self::ABSOLUTE_URL_PATTERN, $full_path))
		{
			$from_directory_http_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $from_directory);
			$path_to_remove = base_url($from_directory_http_path);
		}
		else
		{
			$path_to_remove = $from_directory;
		}

		$path_to_remove = rtrim($path_to_remove, '/') . '/'; // exactly one slash at the end
		$relative_path = str_replace($path_to_remove, '', $full_path);
		$relative_path = preg_replace('/\?.*$/', '', $relative_path); // remove cache buster from URL, if it's there

		return $relative_path;
	}

	/**
	 * @param $asset array ['type' => string, 'full_path' OR 'path_in_creative' => string]
	 *	the first asset must be of 'type' 'html', and it must have a 'full_path'
	 * @param $should_get_contents boolean
	 * @return $asset array
	 */
	protected function add_asset($asset, $should_get_contents = false)
	{
		if(isset($asset['ssl_uri']) && empty($asset['full_path']))
		{
			$asset['full_path'] = $asset['ssl_uri'];
		}

		if(empty($this->directory))
		{
			if($asset['type'] === 'html')
			{
				$this->directory = dirname($asset['full_path']);
			}
			else
			{
				return false; // you must add the html asset first
			}
		}

		if(empty($asset['path_in_creative']))
		{
			$directory_without_unique_id = $this->remove_size_directory_suffix($this->directory);
			$full_path_without_unique_id = $this->remove_size_directory_suffix($asset['full_path']);
			$asset['path_in_creative'] = str_replace($directory_without_unique_id . '/', '', $full_path_without_unique_id);
		}
		elseif(empty($asset['full_path']) && !empty($this->directory))
		{
			$asset['full_path'] = $this->directory . '/' . $asset['path_in_creative'];
		}

		if(empty($asset['ssl_uri'])) // local asset
		{
			$asset['ssl_uri'] = $this->get_url($asset['full_path']);
			$asset['open_uri'] = $asset['ssl_uri'];
		}

		if($should_get_contents)
		{
			$this->get_asset_contents($asset);
		}

		$this->assets[] = $asset;

		$this->index_assets();

		return $asset;
	}

	/**
	 * @param $asset array
	 */
	protected function get_asset_contents(&$asset)
	{
		if(!isset($asset['contents']) && isset($asset['full_path']))
		{
			$is_local_file = (substr($asset['full_path'], 0, 1) === '/');
			if(!$is_local_file || file_exists($asset['full_path']))
			{
				$asset['contents'] = file_get_contents($asset['full_path']);
			}
		}
	}

	/**
	 * Remove the uniqid() added to the size segment of the CDN path.
	 *
	 * @param $path string
	 */
	protected function remove_size_directory_suffix($path)
	{
		$cdn_path_pattern = '/v([0-9]+)\/([0-9x]+)(_[0-9a-f]{13})?(\/.*)?/';
		//                     1         2        3               4
		// 1: version ID
		// 2. size
		// 3. uniqid suffix (13 hex characters)
		// 4. remainder
		return preg_replace($cdn_path_pattern, 'v$1/$2$4', $path);
	}

}

/**
 * An HTML5 creative, of an undetermined type, to be loaded in an iframe
 */
class html5_generic_creative extends html5_creative
{

	public $is_rigid_internal_paths = true;
	const SUPPORTS_STATIC_IMAGE = true;

	protected $html_patterns = [
		'html' => [
			'pattern' => '/(width[^0-9A-Za-z]+)(160|728|300|336|320)(.*height[^0-9A-Za-z]+)(600|90|250|280|50)/s',
			//             1                   2                    3                      4
			'values' => [
				'width' => 2,
				'height' => 4
			],
			'required' => true
		]
	];

	public function get_markup($markup = [])
	{
		$markup = parent::get_markup($markup);

		/**
		 * generate initialization markup
		 */
		if(empty($this->indexed_assets['html']))
		{
			$markup['initialization'] .= 'isHTML5 = false;';
		}
		$markup['initialization'] .= "var htmlURL = \"{$this->indexed_assets['html']['open_uri']}\";\n";
		$markup['initialization'] .= "var htmlSSLURL = \"{$this->indexed_assets['html']['ssl_uri']}\";\n";

		/**
		 * generate setup markup
		 */
		$markup['setup'] .= "	if (flashvars.isDebugMode == true) {\n";
		$markup['setup'] .= "		console.log(\"JavaScript: loadHTML5: generic\");\n";
		$markup['setup'] .= "	}\n";
		$markup['setup'] .= "	document.write('<div id=\"'+ vlid +'-'+ clientID +'\"class=\"vantagelocal\" style=\"position:relative;width:' + dccreativewidth + 'px;height:' + dccreativeheight + 'px;background-color:#FFFFFF\" onmouseover=\"onHoverMaster();\" onmouseout=\"onOutMaster();\"><div class=\"buttons\" style=\"z-index:3; position:absolute;\"><div id=\"playbutton-'+clientID+'\"></div></div><div style=\"position:absolute;left:' + video_x + 'px;top:' + video_y + 'px;z-index:2;\" class=\"video-holder\" id=\"holder\"><div id=\"player-'+ clientID +'\"></div></div><a href=\"' + advurl + '\" target=\"'+vl_html_target+'\"><span id=\"clicktag\" style=\"width:100%;height:100%;position:absolute;left:0px;top:0px;z-index:1;background-color:rgba(0,0,0,0)\"></span></a><iframe src=\"' + (isSSL == 'true' ? htmlSSLURL : htmlURL) + '\" width=\"' + dccreativewidth + '\" height=\"' + dccreativeheight + '\" frameborder=\"0\" scrolling=\"no\" marginwidth=\"0\" marginheight=\"0\" allowfullscreen></iframe></div>');\n";
		$markup['setup'] .= "	animationComplete();";

		return $markup;
	}

	protected function index_assets()
	{
		// Backups only appear when building from already-uploaded assets. Their upload is not handled in html5_creative and its descendants.
		$this->indexed_assets = [
			'html' => null,
			'files' => []
		];

		foreach($this->assets as $asset)
		{
			if($asset['type'] == 'html')
			{
				$this->indexed_assets[$asset['type']] = $asset;
			}
			else
			{
				$this->indexed_assets['files'][] = $asset;
			}
		}
	}

	protected function scrape_html()
	{
		if($successful_scrape = parent::scrape_html())
		{
			$this->width = $this->html_matches_indexed['html']['width'];
			$this->height = $this->html_matches_indexed['html']['height'];

			$this->size = "{$this->width}x{$this->height}";
		}

		return $successful_scrape;
	}

	protected function find_staged_assets(&$files = [])
	{
		parent::find_staged_assets($files);
		// add all the other files to the asssets
		$html_file_index = array_search($this->indexed_assets['html']['full_path'], $files);
		array_splice($files, $html_file_index, 1);
		foreach($files as $index => $file_path)
		{
			$this->add_asset([
				'type' => "file",
				'full_path' => $file_path
			]);
		}
	}

}

/**
 * An HTML5 creative, from Adobe Animate, to be integrated to the blob
 */
class html5_createjs_creative extends html5_creative
{

	protected $should_offload_js_file_to_cdn = null;
	protected $html_patterns = [
		'canvas' => [
			'pattern' => '/<canvas.*width="(\d+)" height="(\d+)" style="background-color:([^";]+)/',
			//                             1              2                              3
			'values' => [
				'width' => 1,
				'height' => 2,
				'background_color' => 3
			],
			'required' => true
		],
		'js_source_path' => [
			'pattern' => '/(<script src="[^"]*createjs[^"]*\.js[^"]*"><\/script>[\r\n\s]*)(<script src=")([^"]+\.js)(\?[^"]+)?/m',
			//             1                                                              2              3          4
			'values' => [
				'src' => 3,
				'cache_buster' => 4
			],
			'required' => true
		],
		'js_source_id' => [
			'pattern' => '/(exportRoot = new lib\.)([^\(]+)/',
			//             1                       2
			'values' => [
				'id' => 2
			],
			'required' => true
		],
		'spritesheet_load' => [
			'pattern' => '/(loadFile\({src:")([^"?]+)(\?[^"]+)?(.*type:"spritesheet", id:")([^"]+)(")(, callback:"([^"}]+)("))?/',
			//             1                 2       3         4                           5      6  7            8       9
			'values' => [
				'src' => 2,
				'cache_buster' => 3,
				'id' => 5,
				'callback' => 8
			],
			'multiple' => true,
			'required' => false
		],
		'spritesheet_result' => [
			'pattern' => '/(ss\[")([^"]+)("\] = queue.getResult\(")([^"]+)("\);)/',
			//             1      2      3                         4      5
			'values' => [
				'id' => 2
			],
			'multiple' => true,
			'required' => false
		]
	];

	/**
	 * @param $assets array
	 */
	public function build_from_assets($assets)
	{
		if(parent::build_from_assets($assets))
		{
			$this->get_asset_contents($this->indexed_assets['html']);

			$original_size = $this->size;

			if(isset($this->indexed_assets['html']['contents']) && $this->scrape_html())
			{
				$this->is_compatible = true;
				if($this->is_correct_size = ($original_size == null || $original_size == $this->size))
				{
					$this->find_staged_assets();
				}
			}
		}

		return $this->is_compatible;
	}

	public function get_markup($markup = [])
	{
		if(!isset($markup['initialization']))
		{
			$markup['initialization'] = '';
		}

		$markup['initialization'] = "\nvar canvas_id = vlid +'_'+ clientID +'_canvas';\n" . $markup['initialization'];

		$markup = parent::get_markup($markup);

		array_walk($this->indexed_assets['atlases'], function(&$atlas_asset){
			$this->get_asset_contents($atlas_asset['contents']);
		});

		if(isset($this->indexed_assets['live_js']))
		{
			$javascript_after_createjs_loaded = "document.write('<script src=\"{$this->indexed_assets['live_js']['ssl_uri']}\"><\/script>');";
			$javascript_in_init =	'';
		}
		else
		{
			$live_creative_js = $this->get_live_js();
			// js contents is not called in the global context, so we're bringing variables into and out of global scope.
			$javascript_after_createjs_loaded = "
				function creative() {
					var createjs = window.createjs;
					{$live_creative_js}
					window.images = images;
					window.lib = lib;
					window.ss = ss;
				}
				";
			$javascript_in_init =	'creative();';
		}

		/*
		 * generate setup markup
		 */
		ob_start();
		?>
		if (flashvars.isDebugMode == true) {
			console.log("JavaScript: loadHTML5: CreateJS (Animate)");
		}
		if(typeof createjs === 'undefined')
		{
			document.write('<script src="https://code.createjs.com/createjs-2015.11.26.min.js"><\/script>');
		}

		document.write('<div id="'+ vlid +'-'+ clientID +'"class="vantagelocal" style="position:relative;width:' + dccreativewidth + 'px;height:' + dccreativeheight + 'px;background-color:#FFFFFF" onmouseover="onHoverMaster();" onmouseout="onOutMaster();"><div class="buttons" style="z-index:3; position:absolute;"><div id="playbutton-'+clientID+'"></div></div><div style="position:absolute;left:' + video_x + 'px;top:' + video_y + 'px;z-index:2;" class="video-holder" id="holder"><div id="player-'+ clientID +'"></div></div><a href="' + advurl + '" target="'+vl_html_target+'"><span id="clicktag" style="width:100%;height:100%;position:absolute;left:0px;top:0px;z-index:1;background-color:rgba(0,0,0,0)"></span></a><canvas id="'+ canvas_id +'" width="' + dccreativewidth + '" height="' + dccreativeheight + '"></canvas></div>');

		var canvas, stage, exportRoot;

		<?php echo $javascript_after_createjs_loaded; ?>

		function init() {
			<?php echo $javascript_in_init; ?>

			canvas = document.getElementById(canvas_id);
			images = images||{};
			ss = ss||{};

			var loader = new createjs.LoadQueue(false);
			loader.addEventListener("fileload", handleFileLoad);
			loader.addEventListener("complete", handleComplete);

			<?php
			foreach($this->indexed_assets['spritesheets'] as $index => $spritesheet_asset)
			{
				$atlas_name = $this->html_matches_indexed['spritesheet_load']['id'][$index];
				echo "			loader.loadFile({src:isSSL == 'true' ? '{$spritesheet_asset['ssl_uri']}' : '{$spritesheet_asset['open_uri']}', type:'image', id:'{$atlas_name}'}, true);\n";
			}
			?>
			loader.loadManifest(lib.properties.manifest);
		}

		window.addEventListener('load', function() {
			init();
		});

		function handleFileLoad(evt) {
			if (evt.item.type == "image") { images[evt.item.id] = evt.result; }
		}

		function handleComplete(evt) {
			var queue = evt.target;
			<?php
			foreach($this->indexed_assets['atlases'] as $index => $atlas_asset)
			{
				$atlas_name = $this->html_matches_indexed['spritesheet_load']['id'][$index];
				$image_path = $this->indexed_assets['spritesheets'][$index]['path_in_creative'];
				$image_javascript = "queue.getResult('{$atlas_name}')";
				$updated_atlas_json = preg_replace("|\"{$image_path}(\?\d+)?\"|", $image_javascript, $atlas_asset['contents']);
				echo "ss[\"{$atlas_name}\"] = new createjs.SpriteSheet({$updated_atlas_json});\n";
			}
			?>

			exportRoot = new lib.<?php echo $this->html_matches_indexed['js_source_id']['id'] ?>();

			stage = new createjs.Stage(canvas);
			stage.addChild(exportRoot);
			stage.update();

			createjs.Ticker.setFPS(lib.properties.fps);
			createjs.Ticker.addEventListener("tick", stage);
		}
		<?php
		$markup['setup'] .= ob_get_clean();

		return $markup;
	}

	public function load_assets_to_cdn($version_id, $size_directory_suffix = '', $callable)
	{
		$loaded_assets = parent::load_assets_to_cdn($version_id, $size_directory_suffix, $callable);

		//update local asset URIs with those from the CDN, for use in live_js below
		foreach($loaded_assets as $loaded_asset)
		{
			$loaded_asset_full_local_path = $loaded_asset['dirname'] . '/' . $loaded_asset['basename'];

			foreach($this->assets as &$asset)
			{
				if($loaded_asset_full_local_path == $asset['full_path'])
				{
					$asset['ssl_uri'] = $loaded_asset['ssl_uri'];
					$asset['open_uri'] = $loaded_asset['open_uri'];
				}
			}
		}
		$this->index_assets();

		$markup = $this->get_markup();

		$markup_weight = array_reduce($markup, function($carry, $markup_segment) {
			return $carry + strlen($markup_segment);
		}, 0);
		$dcm_html_code_weight_limit = 131072; // 128KB
		$approximate_blob_size = 46080; // 45KB (about 10KB larger than the actual blob, for safety)
		if($this->should_offload_js_file_to_cdn === null)
		{
			$this->should_offload_js_file_to_cdn = ($markup_weight + $approximate_blob_size > $dcm_html_code_weight_limit);
		}

		if($this->should_offload_js_file_to_cdn)
		{
			$live_js_asset = $this->add_asset([
				'type' => 'live_js',
				'full_path' => str_replace('.js', '.1.js', $this->indexed_assets['js']['full_path']),
				'contents' => $this->get_live_js()
			]);
			file_put_contents($live_js_asset['full_path'], $live_js_asset['contents']);

			$loaded_assets[] = call_user_func($callable, $version_id, [
				'path' => $live_js_asset['full_path'],
				'size' => $this->size,
				'type' => $live_js_asset['type'],
				'path_in_creative' => $live_js_asset['path_in_creative'],
				'cdn_path' => "creatives/v{$version_id}/{$this->size}{$size_directory_suffix}/{$live_js_asset['path_in_creative']}"
			]);
		}

		return $loaded_assets;
	}

	public function can_publish()
	{
		$result = parent::can_publish();

		return $result && isset($this->indexed_assets['js']);
	}

	/**
	 * @return array of missing files indexed by type, values of (int) 1
	 */
	public function list_missing_files()
	{
		$missing_files = parent::list_missing_files();

		if(!isset($this->indexed_assets['js']))
		{
			$missing_files['js'] = 1;
		}

		return $missing_files;
	}

	protected function get_live_js()
	{
		$this->get_asset_contents($this->indexed_assets['js']);
		$live_js = $this->indexed_assets['js']['contents'];
		foreach($this->indexed_assets['images'] as $image_asset)
		{
			$image_url_switch = "isSSL == 'true' ? '{$image_asset['ssl_uri']}' : '{$image_asset['open_uri']}'";
			$live_js = preg_replace('{"' . $image_asset['path_in_creative'] . '(\?[^"]+)?"}', $image_url_switch, $this->indexed_assets['js']['contents']);
		}

		return $live_js;
	}

	protected function index_assets()
	{
		$this->indexed_assets = [
			'html' => null,
			'js' => null,
			'atlases' => [],
			'spritesheets' => [],
			'images' => []
		];

		foreach($this->assets as $asset)
		{
			if(strpos($asset['type'], 'atlas') === 0)
			{
				$atlas_index = $this->get_spritesheet_index_by_string_containing_id($asset['path_in_creative']);
				if($atlas_index !== false)
				{
					$this->indexed_assets['atlases'][$atlas_index] = $asset;
				}
			}
			else if(strpos($asset['type'], 'spritesheet') === 0)
			{
				$spritesheet_index = $this->get_spritesheet_index_by_string_containing_id($asset['path_in_creative']);
				if($spritesheet_index !== false)
				{
					$this->indexed_assets['spritesheets'][$spritesheet_index] = $asset;
				}
			}
			else if(strpos($asset['type'], 'image') === 0)
			{
				$this->indexed_assets['images'][] = $asset;
			}
			else
			{
				$this->indexed_assets[$asset['type']] = $asset;
			}
		}

		if($this->indexed_assets['atlases'])
		{
			ksort($this->indexed_assets['atlases']);
		}
		if($this->indexed_assets['spritesheets'])
		{
			ksort($this->indexed_assets['spritesheets']);
		}
	}

	protected function get_spritesheet_index_by_string_containing_id($string_containing_id)
	{
		if(isset($this->html_matches_indexed['spritesheet_load']['id']))
		{
			foreach($this->html_matches_indexed['spritesheet_load']['id'] as $index => $id)
			{
				if(strpos($string_containing_id, $id) !== false)
				{
					return $index;
				}
			}
		}
		return false;
	}

	public function find_staged_assets(&$files = [])
	{
		parent::find_staged_assets($files);
		if($this->html_matches_indexed['js_source_path']['src'] && $this->html_matches_indexed['js_source_id']['id'])
		{
			$this->add_asset([
				'type' => 'js',
				'path_in_creative' => $this->html_matches_indexed['js_source_path']['src']
			]);
			$this->get_asset_contents($this->indexed_assets['js']);
			if(isset($this->indexed_assets['js']['contents']))
			{
				preg_match_all('/src:"([^"]+)", id:/', $this->indexed_assets['js']['contents'], $manifest_image_matches);
				if(!empty($manifest_image_matches))
				{
					foreach($manifest_image_matches[1] as $manifest_image_source)
					{
						$this->add_asset([
							'type' => 'image',
							'path_in_creative' => preg_replace('/\?.*$/', '', $manifest_image_source) // remove query string (cache-buster)
						]);
					}
				}
			}
			else
			{
				$this->is_complete = false;
			}

			if(isset($this->html_matches_indexed['spritesheet_load']['src']))
			{
				foreach($this->html_matches_indexed['spritesheet_load']['src'] as $index => $spritesheet_relative_path)
				{
					$spritesheet_asset = $this->add_asset([
						'type' => "atlas{$index}",
						'path_in_creative' => $spritesheet_relative_path
					], true);

					if(isset($spritesheet_asset['contents']))
					{
						$spritesheet_data = json_decode($spritesheet_asset['contents'], true);
						foreach($spritesheet_data['images'] as $image_relative_path)
						{
							$this->add_asset([
								'type' => "spritesheet{$index}",
								'path_in_creative' => preg_replace('/\?.*$/', '', $image_relative_path) // remove query string (cache-buster)
							]);
						}
					}
					else
					{
						$this->is_complete = false;
						break;
					}
				}
			}
		}
		else
		{
			$this->is_compatible = false;
		}
	}

	protected function scrape_html()
	{
		if($successful_scrape = parent::scrape_html())
		{
			$this->width = $this->html_matches_indexed['canvas']['width'];
			$this->height = $this->html_matches_indexed['canvas']['height'];

			$this->size = "{$this->width}x{$this->height}";
		}

		return $successful_scrape;
	}

}

/**
 * An HTML5 creative, from Adobe Animate v2015.2 (released 2016-06-20), to be integrated to the blob
 */
class html5_createjs_2015_2_creative extends html5_createjs_creative
{

	protected $html_patterns = [
		'canvas' => [
			'pattern' => '/<canvas.*width="(\d+)" height="(\d+)" style="[^"]*background-color:([^";]+)/',
			//                             1              2                                   3
			'values' => [
				'width' => 1,
				'height' => 2,
				'background_color' => 3
			],
			'required' => true
		],
		'js_source_path' => [
			'pattern' => '/(<script src="([^"]*createjs[^"]*\.js[^"]*)"><\/script>[\r\n\s]*)((<script src=")([^"]+\.js)(\?[^"]+)?)?/m',
			//             1             2                                                  34              5          6
			'values' => [
				'createjs_engine_src' => 2,
				'src' => 5,
				'cache_buster' => 6
			],
			'required' => true
		],
		'bundled_creative_js' => [
			'pattern' => '/(<script[^>]*>)[\r\n\s]*(\(function \(lib, img, cjs, ss\).*var lib, images, createjs, ss;)[\r\n\s]*(<\/script>)/s',
			//             1                       2                                                                          3
			'values' => [
				'code' => 2
			],
			'required' => false
		],
		'js_source_id' => [
			'pattern' => '/(exportRoot = new lib\.)([^\(]+)/',
			//             1                       2
			'values' => [
				'id' => 2
			],
			'required' => true
		],
		'spritesheet_load' => [
			'pattern' => '/(loadFile\({src:")([^"?]+)(\?[^"]+)?(.*type:"spritesheet", id:")([^"]+)(")(, callback:"([^"}]+)("))?/',
			//             1                 2       3         4                           5      6  7            8       9
			'values' => [
				'src' => 2,
				'cache_buster' => 3,
				'id' => 5,
				'callback' => 8
			],
			'multiple' => true,
			'required' => false
		],
		'spritesheet_result' => [
			'pattern' => '/(ss\[")([^"]+)("\] = queue.getResult\(")([^"]+)("\);)/',
			//             1      2      3                         4      5
			'values' => [
				'id' => 2
			],
			'multiple' => true,
			'required' => false
		]
	];

	public function find_staged_assets(&$files = [])
	{
		if(!empty($this->html_matches_indexed['js_source_id']['id']))
		{
			if(!empty($this->html_matches_indexed['js_source_path']['src']))
			{
				$this->add_asset([
					'type' => 'js',
					'path_in_creative' => $this->html_matches_indexed['js_source_path']['src']
				]);
				$this->get_asset_contents($this->indexed_assets['js']);
				if(isset($this->indexed_assets['js']['contents']))
				{
					preg_match_all('/src:"([^"]+)", id:/', $this->indexed_assets['js']['contents'], $manifest_image_matches);
					if(!empty($manifest_image_matches))
					{
						foreach($manifest_image_matches[1] as $manifest_image_source)
						{
							$this->add_asset([
								'type' => 'image',
								'path_in_creative' => preg_replace('/\?.*$/', '', $manifest_image_source) // remove query string (cache-buster)
							]);
						}
					}
				}
				else
				{
					$this->is_complete = false;
				}

				if(isset($this->html_matches_indexed['spritesheet_load']['src']))
				{
					foreach($this->html_matches_indexed['spritesheet_load']['src'] as $index => $spritesheet_relative_path)
					{
						$spritesheet_asset = $this->add_asset([
							'type' => "atlas{$index}",
							'path_in_creative' => $spritesheet_relative_path
						], true);

						if(isset($spritesheet_asset['contents']))
						{
							$spritesheet_data = json_decode($spritesheet_asset['contents'], true);
							foreach($spritesheet_data['images'] as $image_relative_path)
							{
								$this->add_asset([
									'type' => "spritesheet{$index}",
									'path_in_creative' => preg_replace('/\?.*$/', '', $image_relative_path) // remove query string (cache-buster)
								]);
							}
						}
						else
						{
							$this->is_complete = false;
							break;
						}
					}
				}
			}
			if(!$this->is_complete && !empty($this->html_matches_indexed['bundled_creative_js']))
			{
				preg_match_all('/src:"([^"]+)", id:/', $this->html_matches_indexed['bundled_creative_js']['code'], $manifest_image_matches);
				if(!empty($manifest_image_matches))
				{
					foreach($manifest_image_matches[1] as $manifest_image_source)
					{
						$this->add_asset([
							'type' => 'image',
							'path_in_creative' => preg_replace('/\?.*$/', '', $manifest_image_source) // remove query string (cache-buster)
						]);
					}
				}

				if(isset($this->html_matches_indexed['spritesheet_load']['src']))
				{
					foreach($this->html_matches_indexed['spritesheet_load']['src'] as $index => $spritesheet_relative_path)
					{
						$spritesheet_asset = $this->add_asset([
							'type' => "atlas{$index}",
							'path_in_creative' => $spritesheet_relative_path
						], true);

						if(isset($spritesheet_asset['contents']))
						{
							$spritesheet_data = json_decode($spritesheet_asset['contents'], true);
							foreach($spritesheet_data['images'] as $image_relative_path)
							{
								$this->add_asset([
									'type' => "spritesheet{$index}",
									'path_in_creative' => preg_replace('/\?.*$/', '', $image_relative_path) // remove query string (cache-buster)
								]);
							}
						}
						else
						{
							$this->is_compatible = false;
							$this->is_complete = false;
							break;
						}
					}
				}
			}
		}
		else
		{
			$this->is_compatible = false;
		}
	}

	public function get_markup($markup = [])
	{
		$markup = parent::get_markup($markup);

		array_walk($this->indexed_assets['atlases'], function(&$atlas_asset){
			$this->get_asset_contents($atlas_asset['contents']);
		});

		$live_creative_js = $this->get_live_js();
		if(isset($this->indexed_assets['live_js']))
		{
			$javascript_after_createjs_loaded = "document.write('<script src=\"{$this->indexed_assets['live_js']['ssl_uri']}\"><\/script>');";
			$javascript_in_init =	'';
		}
		else
		{
			// js contents is not called in the global context, so we're bringing variables into and out of global scope.
			$javascript_after_createjs_loaded = "
				function creative() {
					var createjs = window.createjs;
					{$live_creative_js}
					window.images = images;
					window.lib = lib;
					window.ss = ss;
				}
				";
			$javascript_in_init =	'creative();';
		}

		/*
		 * generate setup markup
		 */
		ob_start();
		?>
		if (flashvars.isDebugMode == true) {
			console.log("JavaScript: loadHTML5: CreateJS (Animate)");
		}
		if(typeof createjs === 'undefined')
		{
			document.write('<script src="<?php echo $this->html_matches_indexed['js_source_path']['createjs_engine_src']; ?>"><\/script>');
		}

		var canvas_id = vlid +'_'+ clientID +'_canvas';

		document.write('<div id="'+ vlid +'-'+ clientID +'"class="vantagelocal" style="position:relative;width:' + dccreativewidth + 'px;height:' + dccreativeheight + 'px;background-color:#FFFFFF" onmouseover="onHoverMaster();" onmouseout="onOutMaster();"><div class="buttons" style="z-index:3; position:absolute;"><div id="playbutton-'+clientID+'"></div></div><div style="position:absolute;left:' + video_x + 'px;top:' + video_y + 'px;z-index:2;" class="video-holder" id="holder"><div id="player-'+ clientID +'"></div></div><a href="' + advurl + '" target="'+vl_html_target+'"><span id="clicktag" style="width:100%;height:100%;position:absolute;left:0px;top:0px;z-index:1;background-color:rgba(0,0,0,0)"></span></a><canvas id="'+ canvas_id +'" width="' + dccreativewidth + '" height="' + dccreativeheight + '"></canvas></div>');

		var canvas, stage, exportRoot;

		<?php echo $javascript_after_createjs_loaded; ?>

		function init() {
			<?php echo $javascript_in_init; ?>

			canvas = document.getElementById(canvas_id);
			images = images||{};
			ss = ss||{};

			var loader = new createjs.LoadQueue(false);
			loader.addEventListener("fileload", handleFileLoad);
			loader.addEventListener("complete", handleComplete);

			<?php
			foreach($this->indexed_assets['spritesheets'] as $index => $spritesheet_asset)
			{
				$atlas_name = $this->html_matches_indexed['spritesheet_load']['id'][$index];
				echo "			loader.loadFile({src:isSSL == 'true' ? '{$spritesheet_asset['ssl_uri']}' : '{$spritesheet_asset['open_uri']}', type:'image', id:'{$atlas_name}'}, true);\n";
			}
			?>
			loader.loadManifest(lib.properties.manifest);
		}

		window.addEventListener('load', function() {
			init();
		});

		function handleFileLoad(evt) {
			if (evt.item.type == "image") { images[evt.item.id] = evt.result; }
		}

		function handleComplete(evt) {
			var queue = evt.target;
			<?php
			foreach($this->indexed_assets['atlases'] as $index => $atlas_asset)
			{
				$atlas_name = $this->html_matches_indexed['spritesheet_load']['id'][$index];
				$image_path = $this->indexed_assets['spritesheets'][$index]['path_in_creative'];
				$image_javascript = "queue.getResult('{$atlas_name}')";
				$updated_atlas_json = preg_replace("|\"{$image_path}(\?\d+)?\"|", $image_javascript, $atlas_asset['contents']);
				echo "ss[\"{$atlas_name}\"] = new createjs.SpriteSheet({$updated_atlas_json});\n";
			}
			?>

			<?php
			if(strpos($live_creative_js, 'ssMetadata'))
			{
			?>
			var ssMetadata = lib.ssMetadata;
			for(i=0; i<ssMetadata.length; i++) {
				ss[ssMetadata[i].name] = new createjs.SpriteSheet( {"images": [queue.getResult(ssMetadata[i].name)], "frames": ssMetadata[i].frames} )
			}
			<?php
			}
			?>

			exportRoot = new lib.<?php echo $this->html_matches_indexed['js_source_id']['id']; ?>();

			stage = new createjs.Stage(canvas);
			stage.addChild(exportRoot);

			/*responsive*/(function(i,e,t,a){var n,s,h=1;window.addEventListener("resize",d);d();function d(){var d=lib.properties.width,l=lib.properties.height;var r=window.innerWidth,w=window.innerHeight;var f=window.devicePixelRatio,o=r/d,v=w/l,c=1;if(i){if(e=="width"&&n==r||e=="height"&&s==w){c=h}else if(!t){if(r<d||w<l)c=Math.min(o,v)}else if(a==1){c=Math.min(o,v)}else if(a==2){c=Math.max(o,v)}}canvas.width=d*f*c;canvas.height=l*f*c;canvas.style.width=d*c+"px";canvas.style.height=l*c+"px";stage.scaleX=f*c;stage.scaleY=f*c;n=r;s=w;h=c}})(false,"both",false,1);

			createjs.Ticker.setFPS(lib.properties.fps);
			createjs.Ticker.addEventListener("tick", stage);
		}

		<?php
		$markup['setup'] = ob_get_clean();

		return $markup;
	}

	/**
	 * @return array of missing files indexed by type, values of (int) 1
	 */
	public function list_missing_files()
	{
		$missing_files = parent::list_missing_files();

		$missing_files['js'] = 0;

		return $missing_files;
	}

	public function can_publish()
	{
		return isset($this->indexed_assets['html']);
	}

	protected function get_live_js()
	{
		if($this->indexed_assets['js'])
		{
			$this->get_asset_contents($this->indexed_assets['js']);
			$live_js = $this->indexed_assets['js']['contents'];
		}
		else if(!empty($this->html_matches_indexed['bundled_creative_js']))
		{
			$live_js = $this->html_matches_indexed['bundled_creative_js']['code'];
		}

		foreach($this->indexed_assets['images'] as $image_asset)
		{
			$image_url_switch = "isSSL == 'true' ? '{$image_asset['ssl_uri']}' : '{$image_asset['open_uri']}'";
			$live_js = preg_replace('{"' . $image_asset['path_in_creative'] . '(\?[^"]+)?"}', $image_url_switch, $live_js);
		}

		return $live_js;
	}

}

/**
 * An HTML5 creative, from Adobe Animate, containing deeply integrated rich media features
 */
class html5_createjs_rich_media_creative extends html5_generic_creative
{

	const IS_INTERNAL_RICH_MEDIA = true;

	protected $html_patterns = [
		'html' => [
			// TODO: match each width to its height, and no other (i.e. 300x600 should not be matched...yet)
			'pattern' => '/(width[^0-9A-Za-z]+)(160|728|300|336|320|468)(.*height[^0-9A-Za-z]+)(600|90|250|280|50|60)/s',
			//             1                   2                        3                      4
			'values' => [
				'width' => 2,
				'height' => 4
			],
			'required' => true
		]
	];

	public $rich_media_configuration = [];

	protected $rich_media_scripts = [
		'ad.core' => [
			'requires' => [],
			'prefix' => ''
		],
		'core' => [
			'requires' => ['ad.core'],
			'prefix' => 'rm'
		],
		'video' => [
			'requires' => ['core'],
			'patterns' => [
				'/[^a-z_]rm_video_placeholder_exists[^a-z_]/'
			],
			'prefix' => 'rm'
		],
		'video_hover_to_play' => [
			'requires' => ['core', 'video'],
			'patterns' => [
				'/[^a-z_]rm_video_hover_to_play_exists[^a-z_]/'
			],
			'prefix' => 'rm'
		],
		'video_creative_play_button' => [
			'requires' => ['core', 'video'],
			'patterns' => [
				'/[^a-z_]rm_video_creative_play_button_exists[^a-z_]/'
			],
			'prefix' => 'rm'
		],
		'video_backdrop' => [
			'requires' => ['core', 'video'],
			'patterns' => [
				'/[^a-z_]rm_video_backdrop_exists[^a-z_]/'
			],
			'prefix' => 'rm'
		],
		'map' => [
			'requires' => ['core'],
			'patterns' => [
				'/[^a-z_]rm_map_placeholder_exists[^a-z_]/'
			],
			'prefix' => 'rm'
		],
		'map_creative_button' => [
			'requires' => ['core', 'map'],
			'patterns' => [
				'/[^a-z_]rm_map_creative_button_exists[^a-z_]/'
			],
			'prefix' => 'rm'
		],
		'map_backdrop' => [
			'requires' => ['core', 'map'],
			'patterns' => [
				'/[^a-z_]rm_map_backdrop_exists[^a-z_]/'
			],
			'prefix' => 'rm'
		],
		'social' => [
			'requires' => ['core'],
			'patterns' => [
				'/[^a-z_]rm_social_exists[^a-z_]/'
			],
			'prefix' => 'rm'
		]
	];

	public function build_from_assets($assets)
	{
		if(parent::build_from_assets($assets))
		{
			if($this->is_compatible && $this->is_complete && isset($this->indexed_assets['rm_config']))
			{
				$this->build_rich_media_configuration();
				$this->is_compatible = true;
				$this->is_complete = true;
			}
			else
			{
				$this->is_compatible = false;
				$this->is_complete = false;
			}
		}

		return $this->is_compatible;
	}

	/**
	 * see html5_creative::get_markup
	 */
	public function get_markup($markup = [])
	{
		// "initialization" section of the ad server blob
		if(!isset($markup['initialization']))
		{
			$markup['initialization'] = '';
		}

		$rich_media_configuration_json = json_encode($this->rich_media_configuration);

		$markup['initialization'] .= "isHTML5 = !!window.CanvasRenderingContext2D;" .
		"var configuration = {$rich_media_configuration_json};" .
		"configuration.click_through_target = vl_html_target;" .
		"if(isHTML5)" .
		"{" .
			"window.addEventListener('message', function(event) {" .
				"if(event.data == 'rm_request_configuration')" .
				"{\n" .
					"event.source.postMessage(JSON.stringify({message:'rm_request_configuration',configuration:configuration}), '*');" .
				"}" .
			"});" .
		"}";

		// "setup" section of the ad server blob
		if(!isset($markup['setup']))
		{
			$markup['setup'] = '';
		}

		// TODO: use SSL or non-SSL according to parent
		$markup['setup'] .= "htmlURL = isSSL == 'true' ? '{$this->indexed_assets['live_html']['ssl_uri']}' : '{$this->indexed_assets['live_html']['open_uri']}';";
		$markup['setup'] .= "document.write('<div id=\"'+ vlid +'-'+ clientID +'\"class=\"vantagelocal\" style=\"position:relative;width:' + dccreativewidth + 'px;height:' + dccreativeheight + 'px;background-color:#FFFFFF\" onmouseover=\"onHoverMaster();\" onmouseout=\"onOutMaster();\"><iframe src=\"' + htmlURL + '\" width=\"' + dccreativewidth + '\" height=\"' + dccreativeheight + '\" frameborder=\"0\" scrolling=\"no\" marginwidth=\"0\" marginheight=\"0\" allowfullscreen></iframe></div>');";

		return $markup;
	}

	protected function build_rich_media_configuration()
	{
		$this->rich_media_configuration = [];
		if(isset($this->indexed_assets['rm_config']))
		{
			$this->get_asset_contents($this->indexed_assets['rm_config']);
			$this->rich_media_configuration = json_decode($this->indexed_assets['rm_config']['contents'], true);
			if(isset($this->rich_media_configuration['sizes']))
			{
				if(isset($this->rich_media_configuration['sizes'][$this->size]))
				{
					$this->rich_media_configuration = array_replace_recursive($this->rich_media_configuration, $this->rich_media_configuration['sizes'][$this->size]);
				}
				unset($this->rich_media_configuration['sizes']);
			}
		}

		// HTML which will contain the creative, and which will call out to the parent window (the ad server blob) to get its configuration.
		if(!isset($this->rich_media_configuration['version']))
		{
			$this->rich_media_configuration['version'] = '';
		}
	}

	/**
	 * see html5_createjs_creative::load_assets_to_cdn
	 */
	public function load_assets_to_cdn($version_id, $size_directory_suffix = '', $callable)
	{
		$this->should_offload_js_file_to_cdn = false;
		$loaded_assets = parent::load_assets_to_cdn($version_id, $size_directory_suffix, $callable);

		return $loaded_assets;
	}

	/**
	 * see html5_createjs_creative::find_staged_assets
	 */
	public function find_staged_assets(&$files = [])
	{
		parent::find_staged_assets($files);

		if($this->is_compatible && $this->is_complete)
		{
			// Prefer the master JSON config file  over that of this creative size.
			$master_json_config_path = "{$this->directory}/../rich_media_configuration.json";
			$creative_json_config_path = "{$this->directory}/rich_media_configuration.json";
			if(file_exists($master_json_config_path))
			{
				$master_config_contents = file_get_contents($master_json_config_path);
				file_put_contents($creative_json_config_path, $master_config_contents);
			}
			else if(file_exists($creative_json_config_path))
			{
				// If the master JSON config file doesn't exist (as when downloading from the CDN), then create it from this size's config.
				$creative_config_contents = file_get_contents($creative_json_config_path);
				file_put_contents($master_json_config_path, $creative_config_contents);
			}
			else
			{
				$this->is_compatible = false;
				$this->is_complete = false;
			}

			if($this->is_compatible && $this->is_complete)
			{
				// Remove any existing config asset, including any added as a plain "file" type by html5_generic_creative.
				foreach($this->assets as $index => $asset)
				{
					if($asset['full_path'] == $creative_json_config_path)
					{
						unset($this->assets[$index]);
					}
				}

				$this->add_asset([
					'type' => 'rm_config',
					'full_path' => $creative_json_config_path
				]);
				$this->build_rich_media_configuration();
				$this->add_asset([
					'type' => 'live_html',
					'full_path' => $this->directory . '/' . self::MODIFIED_LIVE_HTML_BASENAME
				]);
				file_put_contents($this->indexed_assets['live_html']['full_path'], $this->get_live_html());
			}
		}
	}

	protected function index_assets()
	{
		$this->indexed_assets = [
			'html' => null,
			'rm_config' => null,
			'live_html' => null,
			'files' => []
		];

		foreach($this->assets as $asset)
		{
			if($asset['type'] == 'html' || $asset['type'] == 'rm_config' || $asset['type'] == 'live_html')
			{
				$this->indexed_assets[$asset['type']] = $asset;
			}
			else
			{
				$this->indexed_assets['files'][] = $asset;
			}
		}
	}

	/**
	 * Get markup for creative HTML (to be loaded to the CDN)
	 */
	protected function get_live_html()
	{
		$feature_script_names = $this->scrape_creative_for_required_features();
		$feature_script_contents_list = $this->get_feature_script_contents($feature_script_names);
		$encapsulated_feature_script_contents = implode("", $feature_script_contents_list);
		$feature_script_pattern = '/<!-- \[BEGIN RICH MEDIA FEATURE SCRIPT -->.*<!-- END RICH MEDIA FEATURE SCRIPT\] -->/s';
		$live_feature_script = '<script>' . $encapsulated_feature_script_contents . '</script>';

		$live_html = $this->indexed_assets['html']['contents'];
		// swap out full feature script tag for minified specific feature scripts
		$live_html = preg_replace($feature_script_pattern, $live_feature_script, $live_html);

		// swap out AJAX config for static config
		$live_configuration_script = "<script>" .
			"!(function(){" . 
				"function receive_config(event)" .
				"{" .
					"var data = JSON.parse(event.data);" .
					"if(data.configuration)" .
					"{" .
						"window.removeEventListener('message', receive_config);" .
						"set_config_8xx(data.configuration);" . // This line is referenced in find_staged_assets. If it changes, update it there, too.
					"}" .
				"}" .
				"window.addEventListener('message', receive_config);" .
				"window.parent.postMessage('rm_request_configuration', '*');" .
			"}());" . 
			"</script>";
		$live_html = preg_replace('/<!-- \[BEGIN RICH MEDIA CONFIG SCRIPT -->.*<!-- END RICH MEDIA CONFIG SCRIPT\] -->/s', $live_configuration_script, $live_html);

		// strip comments
		$live_html = preg_replace('/(\/\*|<!--) \[REMOVE ON PUBLISH.*?REMOVE ON PUBLISH\] (\*\/|-->)/s', '', $live_html);
		$live_html = preg_replace('/<!--.*?-->/s', '', $live_html);
		$live_html .= self::MODIFIED_LIVE_HTML_IDENTIFYING_COMMENT;

		return $live_html;
	}

	/**
	 * @param @script_name string
	 * @param $scripts_list array
	 * @return array
	 */
	protected function add_script_name_to_list_script_with_dependencies($script_name, $scripts_list = [])
	{
		if(!isset($this->rich_media_scripts[$script_name]))
		{
			throw new Exception("Error 92671: Unknown rich media script named {$script_name}.");
		}

		$scripts_list[] = $script_name;
		if(isset($this->rich_media_scripts[$script_name]['requires']))
		{
			foreach($this->rich_media_scripts[$script_name]['requires'] as $required_script_name)
			{
				$scripts_list = $this->add_script_name_to_list_script_with_dependencies($required_script_name, $scripts_list);
			}
		}
		$scripts_list = array_unique($scripts_list);

		return $scripts_list;
	}

	/**
	 * $script_name string
	 * @return string
	 */
	protected function get_script_path($script_name)
	{
		if(!isset($this->rich_media_scripts[$script_name]))
		{
			throw new Exception("Error 92672: Unknown rich media script named {$script_name}.");
		}

		$directory = 'html5/rich_media/scripts/';
		$prefix = $this->rich_media_scripts[$script_name]['prefix'];
		if(!empty($prefix))
		{
			$prefix .= '.';
		}

		return "{$directory}{$prefix}{$script_name}.min.js";
	}

	/**
	 * @return array (see html5_createjs_rich_media_creative::add_script_name_to_list_script_with_dependencies)
	 */
	protected function scrape_creative_for_required_features()
	{
		$scripts_list = ['ad.core'];

		$this->get_asset_contents($this->indexed_assets['html']);

		foreach($this->rich_media_scripts as $script_name => $script_properties)
		{
			if(isset($script_properties['patterns']))
			{
				foreach($script_properties['patterns'] as $pattern)
				{
					if(preg_match($pattern, $this->indexed_assets['html']['contents']))
					{
						$scripts_list = $this->add_script_name_to_list_script_with_dependencies($script_name, $scripts_list);
					}
				}
			}
		}

		return $this->sort_scripts_by_dependency($scripts_list);
	}

	/**
	 * @param $scripts_list array
	 * @return array
	 */
	protected function get_feature_script_contents($scripts_list)
	{
		$script_contents = [];
		foreach($scripts_list as $script_name)
		{
			$script_path = $this->get_script_path($script_name);
			$script_contents[$script_name] = $this->ci->vl_aws_services->get_file_stream("{$this->ci->config->config['s3_rich_media_script_bucket']}/{$script_path}");
		}

		return $script_contents;
	}

	protected function sort_scripts_by_dependency($scripts_list)
	{
		usort($scripts_list, function($a, $b) {
			// if b requires a, sort a to the front
			if(in_array($a, $this->rich_media_scripts[$b]['requires']))
			{
				return -1;
			}
			// if a requires b, sort a to the back
			else if(in_array($b, $this->rich_media_scripts[$a]['requires']))
			{
				return 1;
			}
			return 0;
		});

		return $scripts_list;
	}

}
