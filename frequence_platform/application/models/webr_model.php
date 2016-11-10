<?php

class webr_model extends CI_Model {

  public function __construct(){
    $this->load->database();                 
  }
  
  public function get_tags($business_name)
  {
    
    $query = 
		"SELECT 
			c.id,
			tc.*
		FROM 
			tag_files_to_campaigns tftc
		JOIN
			tag_codes tc ON (tftc.tag_file_id = tc.tag_file_id)		
		JOIN 
			Campaigns c ON (tftc.campaign_id = c.id)
		JOIN 
			Advertisers a ON (a.id = c.business_id)
		WHERE 
			a.Name = ? 
		AND 
			tc.isActive = 1
		AND 
			tc.tag_type = 0";
    $result = $this->db->query($query, $business_name);
            
    $query_response_array = $result->result_array();
    return $query_response_array;
  
  }
}
?>
