<?php
/**
 * CampaignModel
 * Holds method which help while Campaign's creation
 *
 */
class Campaign_model extends CI_Model{

	// call parent model's constructor
	function __construct(){                
		$this->load->database();
		parent::__construct() ;
                
	}
        
        //-----Campaign's Data retrieval methods ------
	
	// get all available coupon types for a Campaign
	function get_coupon_types_available(){
		
		$query = $this->db->get('coupon_type') ;
		return $query->result() ;
	}
	
	// get all available actions for a Campaign
	function get_actions_available(){
	
		$query = $this->db->get('actions') ;
		return $query->result() ;
	}
	
	// get all available actions for a Campaign
	function get_business_available(){
	
		$query = $this->db->get('Advertisers') ;
		return $query->result() ;
	}
	
	function create($data){
		$str = $this->db->insert('coupon', $data); 
                $ins_id = $this->db->insert_id() ;
		return $ins_id ;
	}
        
        function set_actions($data){
                $str = $this->db->insert_batch('coupon_actions', $data);
        }
        
        function get_actions($id){

             $action = $this->db->get_where('coupon_actions',array('coupon_id'=>$id))->result_array() ;
             return $action;
            
        }
        function have_action($id,$aid){
            $action = $this->db->get_where('coupon_actions',array('coupon_id'=>$id,'action_id'=>$aid))->row() ;
            return $action;
            
        }
        
        function get_coupon_available_by($limit,$offset,$col='created',$order='desc'){
            
                 // row set 
                 $query = $this->db->select('coupon.id,in_run,campaign_name,created,Advertisers.Name as business_name')
                            ->from('coupon')
                            ->where(array('visible'=>1))
                            ->join('Advertisers','coupon.business_name=Advertisers.Name','inner')
                            ->limit($limit,$offset)
                            ->order_by($col,$order);
                 
                 $ret['rows'] =  $query->get()->result() ;
                  
                 // count query
                 $ret['count'] = $this->db->count_all('coupon') ;
                                
		 return $ret ; 
        }
        
        function get_coupon_available(){
            
            $query = $this->db->select('coupon.id,campaign_name,created,Advertisers.Name as business_name')
                            ->from('coupon')
                            ->join('Advertisers','coupon.business_name=Advertisers.Name','inner') ;
            $ret =  $query->get()->result() ;
            return $ret ;
        }
        
        function get_coupon_available_count(){
            //$ret = $this->db->count_all('coupon') ;
	      $ret = $this->db->get_where('coupon','visible = 1')->num_rows() ;
            return $ret ;
        }
        
        function get_coupon_by_id($id){
            $coupon = $this->db->get_where('coupon',array('id'=>$id))->row() ;
            return $coupon;
        }
        
        function get_matric_by_coupon_id($coupon_id){
           return $this->db->get_where('metrics',array('coupon_id' => $coupon_id));           
        }
        
        
        // no. of coupons in a campaign
        function get_coupons_count($id){
             $coupon = $this->db->get_where('coupon',array('id'=>$id),1)->result() ;
             return $coupon[0]->num_of_coupon ;
        }
        function get_coupons_count_claimed($id){
            // fetch number of coupons that is already being claimed for a coupon id 
        }
        
        //---- Campaign's Status Change methods ----
        // toggle method
        function pause($id){
            // pauses a running campaign
            $ret = $this->db->update('coupon',array('in_run'=>0),array('id' => $id)) ;
            return $ret ;
        }
        function resume($id){
            // resumes a paused campaign
            $ret = $this->db->update('coupon',array('in_run'=>1),array('id' => $id)) ;
            return $ret ;
        }
        
        function delete($id){
            $ret = $this->db->update('coupon',array('visible'=>0),array('id' => $id)) ;
            return $ret ;
        }
        
        function claimed($id){
            $data = array('claimed',1) ;
            $this->db->where('id',$id) ;
            $this->db->update('coupon',$data) ;
        }
	
	public function get_tags_campaign($c_id)
	{	      
	    $sql = "SELECT 
			tf.id,
			tf.name
		    FROM 
			tag_files_to_campaigns tc 
		    LEFT JOIN 
			tag_files tf  
		    ON 
			(tf.id =  tc.tag_file_id )
		    JOIN 
			tag_codes tco  
		    ON (tc.tag_file_id = tco.tag_file_id AND tco.tag_type != 1 )
		    WHERE 
			tc.campaign_id = ".$c_id."			   
		    GROUP BY 
			tf.name
		    ORDER BY 
			tf.id 
		    DESC" ;

	    $query = $this->db->query($sql);			
		
		if ($query->num_rows() > 0)
		{
			return $query->result_array();
		}		
		return NULL;
	}
        
}
