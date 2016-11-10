<?php
class adverify extends CI_Controller
{

	function __construct()
	{
		parent::__construct();

		$this->load->helper(array('form', 'url'));
		$this->load->library('form_validation');
		$this->load->library('tank_auth');
		$this->load->model('adverify_model');
	}

	public function queue_jumper()
	{
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer','adverify');
			redirect('login');
			return;
		}
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		if ($role == 'ops' or $role == 'admin')
		{
			///setup common header
			$data['username']   = $username;
			$data['firstname']  = $this->tank_auth->get_firstname($data['username']);
			$data['lastname']   = $this->tank_auth->get_lastname($data['username']);
			$data['user_id']    = $this->tank_auth->get_user_id();

			$data['title'] = "AdVerify Queue Jumper";

			$this->load->view('ad_linker/header',$data);
			$this->load->view('adverify/queue_jumper',$data);
			
		}
	}
	
	public function add_campaign_list_to_queue()
	{
		$return['success'] = false;
		$return['err_msg'] = "";
		
		if($this->input->post('raw_queue_input') === false)
		{
			$return['err_msg'] = "Missing request parameters";
			echo json_encode($return);
			return;
		}
		
		$campaign_list = explode(',', $this->input->post('raw_queue_input'));
		$campaign_list = array_map('trim', $campaign_list);
		
		if(!$this->adverify_model->validate_campaign_list($campaign_list))
		{
			$return['err_msg'] = "Invalid input detected.";
			echo json_encode($return);
			return;
		}		
		$insert_queue_result = $this->adverify_model->add_campaign_list_to_priority_queue($campaign_list);
		if($insert_queue_result == false)
		{
			$return['err_msg'] = "Failed to add campaigns to queue";
		}
		else
		{
			$return['success'] = true;
		}
		echo json_encode($return);
		
	}
	
	public function adverify_health()
	{
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer','adverify');
			redirect('login');
			return;
		}
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		if ($role == 'ops' or $role == 'admin')
		{
			///setup common header
			$data['username']   = $username;
			$data['firstname']  = $this->tank_auth->get_firstname($data['username']);
			$data['lastname']   = $this->tank_auth->get_lastname($data['username']);
			$data['user_id']    = $this->tank_auth->get_user_id();

			$data['title'] = "AdVerify Health CSV";

			$this->load->view('ad_linker/header',$data);
			$this->load->view('adverify/adverify_health',$data);
			
		}
	}
	
	public function adverify_health_download($start_date, $end_date)
	{
		$now = new DateTime();
		$start_datetime = new DateTime($start_date);
		$end_datetime = new DateTime($end_date);
		if($start_datetime > $now)
		{
			echo "FAILURE: Starting date is in the future!";
			return;
		}
		if($start_datetime > $end_datetime)
		{
			echo "FAILURE: End date is before start date!";
			return;
		}
		
		$health_result = $this->adverify_model->get_screenshot_health_between_dates_($start_date, $end_date);
		if($health_result == false)
		{
			echo "FAILURE: Failed to retrieve screenshot health";
			return;
		}
		if(count($health_result) < 1)
		{
			echo "FAILURE: No data found for date range ".$start_date. " - ".$end_date;
			return;
		}
		
		$data['filename'] = "adverify_health_".$start_date."_".$end_date.".csv";
		$data['csv_data'] = $health_result;
		$this->load->view('adverify/adverify_health_csv', $data); 
	}
}

?>