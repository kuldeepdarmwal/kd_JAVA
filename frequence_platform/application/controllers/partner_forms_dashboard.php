<?php


class Partner_forms_dashboard extends CI_Controller {
        
    
    function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->library('tank_auth');

    }
    
    
    public function index()
	{
            if (!$this->tank_auth->is_logged_in()) {
                $this->session->set_userdata('referer','partner_forms_dashboard');
                redirect(site_url("toolkit"));
            }
            else {
			
                $data['user_id']	= $this->tank_auth->get_user_id();
                $data['username']	= $this->tank_auth->get_username();
                $data['business_name']  = $this->tank_auth->get_biz_name($data['username']);
                $data['role']	        = $this->tank_auth->get_role($data['username']);
               
                if (($data['role']=="SALES")&&($data['business_name'] == "Vantage Local Corporate")){
		  //$this->session->set_userdata('referer','partner_forms_dashboard');
                   redirect(site_url("toolkit"));  
                }
            }
            
            
            $this->load->view('partner_forms/index');
	}
        public function mpq()
	{
                if (!$this->tank_auth->is_logged_in()) {
                    $this->session->set_userdata('referer','partner_forms_dashboard');
                    redirect(site_url("toolkit"));
                   }
                else {
			
                    $data['user_id']	= $this->tank_auth->get_user_id();
                    $data['username']	= $this->tank_auth->get_username();
                    $data['business_name']  = $this->tank_auth->get_biz_name($data['username']);
                    $data['role']	        = $this->tank_auth->get_role($data['username']);
                    
                    if (($data['role']=="SALES")&&($data['business_name'] == "Vantage Local Corporate")){
		      redirect(site_url("toolkit"));  
                    }
                }
		$this->load->view('partner_forms/mpq');
	}
        public function new_campaign_ticket()
	{
            if (!$this->tank_auth->is_logged_in()) {
                $this->session->set_userdata('referer','partner_forms_dashboard');
                redirect(site_url("toolkit"));
            }
            else {
			
                $data['user_id']	= $this->tank_auth->get_user_id();
                $data['username']	= $this->tank_auth->get_username();
                $data['business_name']  = $this->tank_auth->get_biz_name($data['username']);
                $data['role']	        = $this->tank_auth->get_role($data['username']);
               
                if (($data['role']=="SALES")&&($data['business_name'] == "Vantage Local Corporate")){
		  
                   redirect(site_url("toolkit"));  
                }
            }
		$this->load->view('partner_forms/new_campaign_ticket');
	}
}
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
