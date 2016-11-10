<?php defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH.'/vendor/autoload.php';

class Launch_io extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();

		$this->load->model('all_ios_model');
		$this->load->model('proposals_model');
		$this->load->model('campaign_health_model');
		$this->load->model('mpq_v2_model');
		$this->load->library('session');
		$this->load->library('tank_auth');
		$this->load->library('vl_platform');
		$this->load->helper('vl_ajax_helper');

	}

	public function index($insertion_order_id = false)
	{
		//Navigation security stuff
		$redirect_string = $this->vl_platform->get_access_permission_redirect('launch_io');

		if ($redirect_string == 'login')
		{
			$this->session->set_userdata('referer', '/insertion_orders');
			redirect($redirect_string);
			return;
		}
		else if($redirect_string !== '')
		{
			redirect($redirect_string);
		}

		//If no insertion order/invalid insertion order, kick back to /insertion_orders
		if(!$insertion_order_id || !$this->all_ios_model->is_insertion_order_launchable($insertion_order_id))
		{
			redirect("insertion_orders");
		}

		//Determine if campaigns exist that belong to the insertion order and have matching advertiser ids with the insertion order
		$campaigns = $this->all_ios_model->get_insertion_order_campaigns($insertion_order_id);
		if($campaigns == -1)
		{
			echo "FAILED TO CHECK IF INSERTION ORDER HAD CAMPAIGNS";
			return;
		}
		if($campaigns === false)
		{
			$built_campaigns = $this->all_ios_model->build_insertion_order_campaigns($insertion_order_id);
			if($built_campaigns == false)
			{
				//Failures in the model function will output error statements about failed step.
				echo "FAILED TO BUILD CAMPAIGNS";
				return;
			}
			$campaigns = $this->all_ios_model->get_insertion_order_campaigns($insertion_order_id);
		}

		$io_notes = $this->all_ios_model->get_notes_from_insertion_order($insertion_order_id);
		$data = array();
		$data['campaigns'] = $campaigns;
		$data['has_notes'] = $io_notes != false ? 1 : 0;
		$data['notes'] = $io_notes['notes'];
		$data['include_rtg'] = $io_notes['rtg'];
		$data['title'] = 'Launch Insertion Order';
		$active_feature_button_id = 'io';
		$this->vl_platform->show_views(
			$this,
			$data,
			$active_feature_button_id,
			'insertion_order/launch_io_html.php',
			'insertion_order/launch_io_header.php',
			NULL,
			'insertion_order/launch_io_footer.php',
			NULL
		);
	}

	public function trash_campaign()
	{
		$return_array = array();
		$return_array['success'] = true;

		$campaign_id = $this->input->post('c_id');
		if(!$campaign_id)
		{
			$return_array['success'] = false;
		}
		else
		{
			$return_array['success'] = $this->al_model->trash_campaign($campaign_id);
		}

		echo json_encode($return_array);
	}
}
