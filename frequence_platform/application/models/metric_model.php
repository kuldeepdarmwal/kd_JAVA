<?php

/**
 * Metric Model
 * DML assisting methods for metric part of site
 *  
 */

class Metric_model extends CI_Model{
    function __construct(){
                $this->load->database() ;
		parent::__construct() ;
                
    }
    
    /**
     * getter method for accessing metric id for a coupun
     * 
     * @param type $id coupon/campaign id
     * @return metric_id ( uniquely identifies a coupon in metric table ) 
     */
    
    function get_id($id){
         $metric = $this->db->get_where('metrics',array('coupon_id'=>$id))->row() ;
         return $metric ? $metric->m_id : 0 ;
    }
    
    /**
     * get all claims for a campaign
     * 
     * @param type $m_id
     * @return type 
     */
    
    function get_claims($m_id){
        //$claims = $this->db->get_where('claimer',array('metric_id'=>$m_id))->result();  
        $claims = $this->db->select('email,method_id,date,uc.coupon_use_code as coupon_code')
                       ->from('claimer')
                       ->where(array('metric_id'=>$m_id))
                       ->join('usecodes as uc','claimer.used_code_id=uc.id','inner')
                       ->get()->result() ;
        return $claims ;
    }
    
    /**
     * returns total shares for a specefic campaign
     * @param type $m_id
     * @return type 
     */
    
    function get_shares($m_id){

         $query = $this->db->select('c.email as clm_mail,c.date,r.email as ref_mail')
                            ->from('claimer as c')
                            ->where(array('c.metric_id'=>$m_id))
                            ->join('claimer_referrer as cr','c.id=cr.claimer_id','inner')
                            ->join('referrer as r','r.id=cr.referrer_id','inner') ;
                 
         return  $query->get()->result() ;
    }
    
    /**
     * gets total number of claims for a campaign 
     * 
     * @param type $m_id
     * @return type 
     */
    
    function stat_claims_count($m_id){
          $claims = $this->db->where(array('metric_id'=>$m_id))->from('claimer')->count_all_results() ;
          return $claims ;
    }
    
    /**
     * Number of times a campaign page is being visited
     * 
     * @param type $id
     * @return type 
     */
    function stat_impression($id){
          $coupon = $this->db->get_where('metrics',array('coupon_id'=>$id))->row() ;
          return $coupon->impression ;
    }
    
    /**
     * Subset of share count : Facebook 
     * @param type $m_id
     * @return type 
     */
    function stat_claims_shared_by_fb($m_id){
             $claims = $this->db->where(array('metric_id'=>$m_id,'method_id'=>1))->from('claimer')->count_all_results() ;
          return $claims ;
    }
    /**
     *  Subset of share count : mail
     * @param type $m_id
     * @return type 
     */
    function stat_claims_shared_to_mails($m_id){
         $claims = $this->db->where(array('metric_id'=>$m_id,'method_id'=>2))->from('claimer')->count_all_results() ;
          return $claims ;
    }
    
    /**
     * Subset of share count : slef mails
     * @param type $m_id
     * @return type 
     */
    function stat_claims_shared_to_self($m_id){
         $claims = $this->db->where(array('metric_id'=>$m_id,'method_id'=>3))->from('claimer')->count_all_results() ;
          return $claims ;
    }
    /**
     * Generalised method for accessing number of shares for a specific claim method type
     * @param type $m_id metric_id
     * @param type $a_id action_id 1(fb)/2(mail)/3(self)
     * @return type 
     */
    function stat_claims_shared_by($m_id,$a_id){
        $claims = $this->db->where(array('metric_id'=>$m_id,'method_id'=>$a_id))->from('claimer')->count_all_results() ;
        return $claims ;  
    }
    /**
     * Total number of usecodes created for a campaign
     * @param type $id
     * @return type 
     */
    function stat_codes_created($id){
        $cc = $this->db->select('c.num_of_coupon as codes_created')
                       ->from('coupon as c')
                       ->where(array('id'=>$id))
                       ->get()->row() ;
        return $cc->codes_created ;
    }
    /**
     * returns date upto which a campaign is treated valid 
     * @param type $id
     * @return type 
     */
    
    function stat_valid_upto($id){
        $cc = $this->db->select('c.created,c.validity')
                       ->from('coupon as c')
                       ->where(array('id'=>$id))
                       ->get()->row() ;
        return strtotime('+'. $cc->validity .' days',$cc->created) ;
    }
    
    /**
     * check if exceeded number of claims
     * if stat_claims_count >= stat_codes_created
     * / else there ud b no more unclaimed rows in usecodes for that coupon  
     */
}
?>
