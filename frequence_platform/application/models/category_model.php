<?php

class Category_model extends CI_Model {
	public function __construct(){
		$this->load->database();                
	}

	public function get_all_iab_channels()
	{
		$sql = "SELECT id, tag_copy FROM iab_categories WHERE 1 ORDER BY tag_copy";
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return false;
	}
        
	public function get_all_categories($the_demo_database){
		$the_query = "SELECT DISTINCT `Category` FROM ".$the_demo_database." ORDER BY `Category`";
		//echo $the_query."<br>";
		$query = $this->db->query($the_query);
            
		$query_response_array = $query->result_array();
		return $query_response_array;
	}
        
	public function get_sites_query($categories,$the_category_database){
		$bindings = array();
		$which_categories_query_fragment = $this->get_which_categories_query_fragment($bindings, $categories);
		$sql = "SELECT Site as Domain,   `Reach`, `Category` FROM ".$the_category_database." ".$which_categories_query_fragment." ORDER BY `Category Rank`, Category";
		return $this->db->query($sql, $bindings);    
	}
        
	function get_which_categories_query_fragment(&$bindings, $categories){
		$string_to_return = "WHERE ";
		for ($i = 0; $i<count($categories);$i++){
			$bindings[] = $categories[$i];
			$string_to_return = $string_to_return."`Category` = ? OR ";
		}
		$string_to_return = $string_to_return." 'BLAH'";
		return $string_to_return;
	}
     
        
}
?>
