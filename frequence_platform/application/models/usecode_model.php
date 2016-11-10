<?php

class Usecode_model extends CI_Model{
    
    
	// call parent model's constructor
	function __construct(){
	
		parent::__construct() ;
                
	}
        
        function get_all(){
            $query = $this->db->get('usecodes') ;
            return $query->result() ;
        }
        
        function get_by_id($id){
             $query = $this->db->select('coupon_use_code as coupon_code')
                            ->from('usecodes')
                            ->where(array('coupon_id'=>$id)) ;
            $usecodes = $query->get()->result() ;
            return $usecodes ;
        }
        
        function get_next_available($id){
            
        }
        
        function create($with){
            $this->db->insert_batch('usecodes', $with); 
        }
    
    
}
?>
