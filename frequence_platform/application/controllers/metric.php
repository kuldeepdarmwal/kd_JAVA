<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Metric extends CI_controller{
    
    public function __construct(){
		parent::__construct() ;
		$this->load->database();
		$this->load->library('tank_auth');
   }
   
   public function index(){
       $this->load->helper('url') ;
       if (!$this->tank_auth->is_logged_in()) {
		redirect('/auth/login/');
       }else{
            $this->load->view('vlc_header') ;
            $this->load->view('coupon_metrics') ;
            $this->load->view('vlc_footer') ;
      }
   }
   
   public function view($id){
       $this->load->helper('url') ;
       if (!$this->tank_auth->is_logged_in()) {
		redirect('/auth/login/');
       }else{
            $this->load->library('table') ;
            $this->load->model('Metric_model','metric',false) ;
            $this->load->model('Campaign_model','campaign',false);
            $m_id = $this->metric->get_id($id) ; // metric id 
            if($m_id){
            // Campaign name
            $coupon = $this->campaign->get_coupon_by_id($id) ;
            $data['campaign_name'] = $coupon->campaign_name ;
            //---- campaign summary 
            $data['stat_set'] = array(
                array(
                $this->metric->stat_impression($id) ,
                $this->metric->stat_codes_created($id) ,
                $this->metric->stat_claims_count($m_id) ,
                $this->metric->stat_claims_shared_by_fb($m_id) ,
                $this->metric->stat_claims_shared_to_mails($m_id) ,
                $this->metric->stat_claims_shared_to_self($m_id) 
                    )
            ); 

            // coupons claimed
            //shared contacts
            
            $actions = $this->campaign->get_actions_available() ;
            foreach($actions as $action){
                $action_map[$action->id] = $action->name ;
            }
            
            //---- Coupons Claimed
            $data['claims'] = array() ;
            foreach($this->metric->get_claims($m_id) as $claim){ 
                array_push($data['claims'],array(
                                $claim->email ,
                                date('j M , Y',$claim->date),
                                $claim->coupon_code ,
                                $action_map[$claim->method_id] 
                                
                    
                )) ;
            }
            
            // shares
            $data['shares'] = array() ;
            foreach($this->metric->get_shares($m_id) as $share){ 
                array_push($data['shares'],array(
                                $share->clm_mail ,
                                $share->ref_mail ,
                                date('j M , Y',$share->date),
                    
                )) ;
            }
                $data['metric_status'] = 1 ;
            }else{
                
                $data['metric_status'] = 0 ;
            }
            
            
            
            $this->load->view('vlc_header') ;
            $this->load->view('coupon_metrics',$data) ;
            $this->load->view('vlc_footer') ;
       }
   }
   
    
}
?>
