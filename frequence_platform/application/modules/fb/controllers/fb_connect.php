<?php
class Fb_connect extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('Facebook_model');
                $this->load->helper('url');
	}
	
	function index($id)
	{ 
		$fb_data = $this->session->userdata('fb_data');
                $fb_data['loginUrl'] = str_replace('&state=', "%2Findex%2F{$id}&state=" ,$fb_data['loginUrl']);
                //$fb_data['loginUrl'] = str_replace('%2Ffb%2Ffb_connect&state=', "%2Ffrontend%2Fcoupon%2F{$id}&state=" ,$fb_data['loginUrl']);
                if(!$fb_data['me']) redirect($fb_data['loginUrl']);
                
                if($this->session->userdata('mode') == 'test')
		  redirect('frontend/coupon/'.$id.'?mode=test&');
                else
		  redirect('frontend/coupon/'.$id);
	}
}
?>