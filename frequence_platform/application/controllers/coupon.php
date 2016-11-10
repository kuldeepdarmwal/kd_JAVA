<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Coupon Controller class
 * 
 * Deals with user interaction for Coupon claiming page
 *  
 */

class Coupon extends CI_Controller {

	public function __construct(){
		parent::__construct() ;
		$this->load->database();
	}
        
        
/**
 *
 * @param type $id Camapign's id for which a claim is being asked
 */        

	public function index($id=87,$mode='live')
	{
        
                 $this->load->helper('url') ;
                 $this->load->helper('coupon') ;
                 
                 // determine coupon claim mode 
                 $do_real = ($mode == 'live') ? true : false ;
                 $data['text'] = $do_real ? 'LIVE' : 'TEST' ; // test indicator
              
                //check if campaign is running or not 
                // load Campaign model class
                $this->load->model('Campaign_model','campaign',TRUE);
                $campaign = $this->campaign->get_coupon_by_id($id) ;
                
                // determine campaign coupon type
                if($campaign->coupon_type==1){
                    //QPQ
                }else{
                    //FreePON
                    // Only avaii
                }
                
                // prepare data that is to be displayed statically across all coupons 
                // of a campaign .
                $data['title'] = $campaign->title ;
                $data['description'] = $campaign->description ;
                $data['discount'] = $campaign->discount ;

                // show error page if campaign is not running
                if(!$campaign->in_run){
                   show_404() ;
                }
              
                if($do_real){
                    // additional logic for DB updates  
                 }
              
                // load Campaign model class
                $this->load->view('coupon/coupon_header') ;
                $this->load->view('coupon/coupon_body',$data) ;
                $this->load->view('coupon/coupon_footer') ;
            
            
        }
        
     
        
}