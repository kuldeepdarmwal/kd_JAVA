<?php

class Media_targeting_tags_model extends CI_Model {

	public function __construct()
	{
		$this->load->database();
	}

	public function get_media_targeting_sites_by_term($search_term, $start, $limit)
	{
		$sql = "SELECT * FROM media_targeting_sites	WHERE url LIKE ? LIMIT ?, ?";
		$query = $this->db->query($sql, array($search_term, $start, $limit));
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return false;
	}

	public function get_site_url($id)
	{
		$sql = "SELECT url FROM media_targeting_sites WHERE id = ?";
		$query = $this->db->query($sql, $id);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return false;
	}

	public function get_media_targeting_tags($site_id)
	{
		$sql = 
		"	SELECT * FROM 
			(
				SELECT 
					t.id as id, 
					CONCAT('IAB: ', t.tag_copy) as tag_copy, 
					s2t.table_source as tag_type 
				FROM 
					media_targeting_sites_to_tags AS s2t 
					INNER JOIN 
					iab_categories AS t 
						ON t.id = s2t.tag_id 
				WHERE 
					s2t.table_source = 1 AND 
					s2t.site_id  = ?

				UNION

				SELECT 
					gcm.GEOID10 AS id, 
					CONCAT('GEO: ', gcm.NAMELSAD10) AS tag_copy, 
					s2t.table_source AS tag_type 
				FROM 
					geo_cbsa_map AS gcm 
					INNER JOIN 
					media_targeting_sites_to_tags AS s2t 
						ON gcm.num_id = s2t.tag_id 
				WHERE 
					s2t.site_id = ? AND 
					s2t.table_source = 2 

				UNION

				SELECT 
					dc.id as id, 
					CONCAT('DEMO: ', dc.tag_copy) as tag_copy, 
					s2t.table_source as tag_type
				FROM
					demographic_categories_legacy AS dc
					INNER JOIN
					media_targeting_sites_to_tags AS s2t
						ON dc.id = s2t.tag_id
				WHERE
					s2t.site_id = ? AND
					s2t.table_source = 3
			) AS alias 
			ORDER BY tag_copy;
		";
		$query = $this->db->query($sql, array($site_id, $site_id, $site_id));
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return false;
	}

	public function get_media_targeting_tags_by_term($search_term, $start, $limit)
	{
		$sql = 
		"	SELECT * FROM 
			(
				SELECT 
					id, 
					CONCAT('IAB: ', tag_copy) as tag_copy, 
					1 AS tag_type 
				FROM 
					iab_categories 
				WHERE 
					CONCAT('IAB: ', tag_copy) LIKE ?

				UNION

				SELECT 
					GEOID10 AS id, 
					CONCAT('GEO: ', NAMELSAD10) AS tag_copy, 
					2 AS tag_type 
				FROM 
					geo_cbsa_map 
				WHERE 
					CONCAT('GEO: ', NAMELSAD10) LIKE ? 

				UNION

				SELECT
					id,
					CONCAT('DEMO: ', tag_copy) tag_copy,
					3 AS tag_type
				FROM
					demographic_categories_legacy
				WHERE
					CONCAT('DEMO: ', tag_copy) LIKE ?
			) AS alias
			ORDER BY tag_copy
			LIMIT ?, ?;
		";
		$query = $this->db->query($sql, array($search_term, $search_term, $search_term, $start, $limit));
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return false;
	}

	public function save_tags_to_site($media_targeting_tags, $site, &$response)
	{
		$sql = "DELETE FROM media_targeting_sites_to_tags WHERE site_id = ?";
		$query = $this->db->query($sql, $site);
		if($media_targeting_tags)
		{
			$this->add_tags_to_site($media_targeting_tags, $site, $response);
		}
	}

	public function get_all_sitepacks()
	{
		$sql = 'SELECT id, concat(id,") ",Name) as pack	FROM prop_gen_site_packs';
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return false;
	}

	public function get_json_sites_from_sitepack($sitepack_id)
	{
		$sql = "SELECT * FROM prop_gen_site_packs WHERE ID = ?";
		$query = $this->db->query($sql, $sitepack_id);
		if($query->num_rows() > 0)
		{
			return $query->row_array();
		}
		return false;
	}

	public function get_site_id_from_url($url)
	{
		$sql = "SELECT id FROM media_targeting_sites WHERE url = ?";
		$query = $this->db->query($sql, $url);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return false;
	}

	public function add_tags_to_site($media_targeting_tags, $site, &$return)
	{
		$return = array('is_success' => true, 'errors' => array());
		$insert_values_sql = "";
		$bindings_array = array();
		$prop_worthy = TRUE;
		foreach($media_targeting_tags as $tag_obj)
		{
			if($insert_values_sql != "")
			{
				$insert_values_sql .= ", ";
			}
			$insert_values_sql .= "(?, ?, ?, ?)";
			$bindings_array[] = $site;
			$bindings_array[] = $tag_obj->id; 
			$bindings_array[] = $tag_obj->tag_type;
			$bindings_array[] = $prop_worthy;
			
		}
		if($insert_values_sql != "")
		{
			$sql = 	"INSERT IGNORE INTO media_targeting_sites_to_tags (site_id,  tag_id, table_source, prop_worthy) VALUES ".$insert_values_sql;
			$query = $this->db->query($sql, $bindings_array);
			if($query == false)
			{
				$return['is_success'] = false;
				$return['errors'][] = 'Failed to add tags for site: '.$site;
			}
		}
		return $return;
	}

	public function save_site($site_row, $is_sitepack = false)
	{
		if($is_sitepack)
		{
			$sql = "INSERT IGNORE INTO media_targeting_sites 
					(url,
					Reach,
					Gender_Male,
					Gender_Female,
					Age_Under18, 
					Age_18_24,
					Age_25_34,
					Age_35_44,
					Age_45_54,
					Age_55_64,
					Age_65,
					Race_Cauc,
					Race_Afr_Am,
					Race_Asian,
					Race_Hisp,
					Race_Other,
					Kids_No,
					Kids_Yes,
					Income_0_50,
					Income_50_100,
					Income_100_150,
					Income_150,
					College_No,
					College_Under,
					College_Grad) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$orig_site_row = $site_row;
			$query_data = $site_row;
		}
		else
		{
			$sql = 	"INSERT INTO media_targeting_sites 
					(url,
					Reach,
					Gender_Male,
					Gender_Female,
					Age_Under18, 
					Age_18_24,
					Age_25_34,
					Age_35_44,
					Age_45_54,
					Age_55_64,
					Age_65,
					Race_Cauc,
					Race_Afr_Am,
					Race_Asian,
					Race_Hisp,
					Race_Other,
					Kids_No,
					Kids_Yes,
					Income_0_50,
					Income_50_100,
					Income_100_150,
					Income_150,
					College_No,
					College_Under,
					College_Grad, 
					last_updated) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
				ON DUPLICATE KEY 
				UPDATE 
					Reach = ?,
					Gender_Male = ?,
					Gender_Female = ?,
					Age_Under18 = ?, 
					Age_18_24 = ?,
					Age_25_34 = ?,
					Age_35_44 = ?,
					Age_45_54 = ?,
					Age_55_64 = ?,
					Age_65 = ?,
					Race_Cauc = ?,
					Race_Afr_Am = ?,
					Race_Asian = ?,
					Race_Hisp = ?,
					Race_Other = ?,
					Kids_No = ?,
					Kids_Yes = ?,
					Income_0_50 = ?,
					Income_50_100 = ?,
					Income_100_150 = ?,
					Income_150 = ?,
					College_No = ?,
					College_Under = ?,
					College_Grad = ?,
					last_updated = CURRENT_TIMESTAMP";
			$orig_site_row = $site_row;
			$the_url = array_shift($site_row);
			$query_data = array_merge($orig_site_row,$site_row);
		}
		$query = $this->db->query($sql, $query_data);
		if($this->db->affected_rows() > 0)
		{
			return array("site_id" => mysql_insert_id(), "site_url" => $orig_site_row[0]);
		}

		return false;
	}

	public function get_sites_by_media_targeting_tags($media_targeting_tags)
	{
		$search_string = "";
		foreach($media_targeting_tags as $tag)
		{
			if($search_string != "")
			{
				$search_string .= ", ";
			}
			$search_string .= "?";
			$binding_array[] = $tag->id."_".$tag->tag_type;
		}
		$sql = "SELECT 
					mts.id AS site_id, 
					mts.url AS url,
					count(mts.id) AS rank
				FROM media_targeting_sites_to_tags s2t 
					JOIN media_targeting_sites mts 
					ON s2t.site_id = mts.id 
				WHERE CONCAT(s2t.tag_id, '_', s2t.table_source) IN(".$search_string.")
				GROUP BY site_id
				ORDER BY rank DESC";
		$query = $this->db->query($sql, $binding_array);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return false;
	}
}
?>