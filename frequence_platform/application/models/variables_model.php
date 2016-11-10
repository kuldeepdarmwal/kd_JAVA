<?php

class Variables_model extends CI_Model {

	public function __construct()
	{
		$this->load->database();
	}

	public function get_latest_variables_config()
	{
		$sql = "SELECT * FROM variables_config ORDER BY builder_version DESC LIMIT 1";
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			$result = $query->result_array();
			return $result[0]['configuration'];
		}
		return NULL;
	}

	public function get_all_variables_versions()
	{
		$sql = "SELECT DISTINCT builder_version, name FROM variables_config ORDER BY builder_version DESC";
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			$result = $query->result_array();
			return $result;
		}
		return NULL;
	}

	public function get_variables_config_by_version($builder_version)
	{
		$sql = "SELECT * FROM variables_config WHERE builder_version =".$builder_version."";
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			$result = $query->result_array();
			return $result[0]['configuration'];
		}
		return NULL;
	}

	public function  get_all_templates($builder_version){
		$sql = "SELECT id, friendly_name FROM adset_templates WHERE builder_version =".$builder_version." AND archived=0";
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			$result = $query->result_array();
			return $result;
		}
		return NULL;
	}

	public function  get_all_adsets(){
		$sql = "SELECT *
				FROM cup_adsets as a_s
				JOIN cup_versions as a_v
				ON a_s.id = a_v.adset_id
				ORDER BY a_v.id DESC ";
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			$result = $query->result_array();
			return $result;
		}
		return NULL;
	}

	public function  get_all_adsets_with_variables(){
		$sql = "SELECT *
				FROM cup_adsets as a_s
				JOIN cup_versions as a_v
				ON a_s.id = a_v.adset_id
				WHERE a_v.variables_data IS NOT NULL
				ORDER BY a_v.id DESC ";
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			$result = $query->result_array();
			return $result;
		}
		return NULL;
	}

	public function  get_all_adsets_by_builder_version($builder_version){
		$sql = "SELECT *
				FROM cup_adsets as a_s
				JOIN cup_versions as a_v
				ON a_s.id = a_v.adset_id
				WHERE 
				a_v.builder_version = ".$builder_version." 
				AND
				a_v.variables_data IS NOT NULL
				ORDER BY a_v.id DESC ";
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			$result = $query->result_array();
			return $result;
		}
		return NULL;
	}

	public function  insert_template($template_friendly_name,$template_data,$builder_version){
		$sql = 	"INSERT INTO adset_templates (`friendly_name`,`data`,`builder_version`) VALUES (  '".$template_friendly_name."', '".$template_data."', ".$builder_version.")";
		$query = $this->db->query($sql);
		echo $this->db->affected_rows();
	}

	public function update_template($template_id,$variables_data,$builder_version){
		$sql = 	"UPDATE adset_templates SET data='".$variables_data."' , builder_version=".$builder_version." WHERE id=".$template_id;
		$query = $this->db->query($sql);
		echo $this->db->affected_rows();
	}

	public function update_adset($adset_version_id, $variables_data, $builder_version, $user_id){
		//$variables_data = 'ivjebejobd';
		$binding_array = array($variables_data, $builder_version, $user_id, $adset_version_id);
		$sql = 	"UPDATE cup_versions SET variables_data = ?, builder_version = ?, updated_user = ? WHERE id = ?";
		$query = $this->db->query($sql, $binding_array);
		echo $this->db->affected_rows();
	}

	public function get_template_by_id($t_id){
		$sql = "SELECT builder_version, data FROM adset_templates WHERE id =".$t_id."";
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			$result = $query->result_array();
			return $result;
		}
		return NULL;
	}

	public function get_adset_by_id($adset_version_id){
		$sql = '
			SELECT
				builder_version,
				variables_data,
				updated_timestamp > published_timestamp AS modified_since_publish
			FROM
				cup_versions
			WHERE
				id = ?';
		$query = $this->db->query($sql, $adset_version_id);
		if($query->num_rows() > 0)
		{
			$result = $query->result_array();
			return $result;
		}
		return NULL;
	}

	public function  insert_variables_config($builder_version,$config_blob, $name = ''){
		$sql = 	"INSERT INTO variables_config (`builder_version`,`configuration`,`name`) VALUES (?, ?, ?)";
		$bindings = [$builder_version, $config_blob, $name];
		$query = $this->db->query($sql, $bindings);
		echo $this->db->affected_rows();
	}

	public function  insert_new_blob_to_builder_version($builder_version,$blob_version,$rich_grandpa_version){
		$sql = 	"INSERT INTO builder_to_blob_versions (`builder_version`,`blob_version`, `rich_grandpa_version`) VALUES (  '".$builder_version."', '".$blob_version."', '".$rich_grandpa_version."')";
		$query = $this->db->query($sql);
		echo $this->db->affected_rows();
	}

	public function get_versions_from_builder_version($builder_version){
		$sql = "SELECT * FROM builder_to_blob_versions WHERE builder_version =".$builder_version." ";
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			$result = $query->result_array();
			return $result;
		}
		return NULL;
	}

	public function get_js_variable_file_contents($adset_id){
		$sql = "SELECT assets.open_uri
				FROM cup_versions as versions
				JOIN cup_ad_assets as assets
				ON versions.variables_js = assets.id
				WHERE versions.id = ".$adset_id;
		//return $sql;
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			$result = $query->result_array();
			return file_get_contents($result[0]['open_uri']);
		}
		return NULL;
	}
	public function get_variables_for_select2($search_term, $start, $limit)
	{
		$bindings = array($search_term, $start, $limit);
		$query = "
			SELECT 
				a_v.id AS id,
				CONCAT(a_s.name, ' v', a_v.version) AS text
			FROM 
				cup_adsets AS a_s
				JOIN cup_versions AS a_v
					ON a_s.id = a_v.adset_id
			WHERE
				a_s.name LIKE ?
				AND a_v.parent_variation_id IS NULL
			ORDER BY
				a_v.id DESC
			LIMIT ?, ?";
		$result = $this->db->query($query, $bindings);
		if($result->num_rows() > 0)
		{
			return $result->result_array();
		}
		return array();
	}
}
