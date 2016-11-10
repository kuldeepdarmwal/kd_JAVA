<?php

class Advertisers extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library(array('session', 'tank_auth', 'vl_platform'));
		$this->load->model(array('advertisers_model', 'mpq_v2_model', 'vl_auth_model'));
		$this->load->helper(array('vl_ajax', 'select2_helper'));
	}

	public function index()
	{
		if(!$this->vl_platform->has_permission_to_view_page_otherwise_redirect('edit_advertisers', '/advertisers'))
		{
			return;
		}

		$data['title'] = 'Advertisers';
		$active_feature_button_id = "";
		$user_id = $this->tank_auth->get_user_id();
		$data['advertisers'] = $this->advertisers_model->get_allowed_advertisers_by_user_id($user_id);

		$this->vl_platform->show_views(
			$this,
			$data,
			$active_feature_button_id,
			'advertisers/all_advertisers_view_html.php',
			'advertisers/all_advertisers_view_header.php',
			NULL,
			'advertisers/all_advertisers_view_footer.php',
			NULL
		);
	}

	public function edit($advertiser_id = null)
	{
		if(!$this->vl_platform->has_permission_to_view_page_otherwise_redirect('edit_advertisers', '/advertisers'))
		{
			return;
		}

		$data = array('title' => 'Edit Advertiser');
		
		$post_data = $this->input->post(null, true);
		if ($post_data)
		{
			$validate = $this->validate_edit_advertisers_post($post_data);

			if ($validate)
			{
				$edit_result = $this->advertisers_model->save_advertiser($post_data['advertiser_id'], $post_data['advertiser_name'], $post_data['external_id'], $post_data['sales_person']);
				redirect('/advertisers');
			}
		}

		$active_feature_button_id = "";
		$user_id = $this->tank_auth->get_user_id();
		$advertisers = $this->advertisers_model->get_allowed_advertisers_by_user_id($user_id, base64_decode($advertiser_id));

		if (empty($advertisers))
		{
			show_404();
		}

		$data['advertiser'] = $advertisers[0];

		$this->vl_platform->show_views(
			$this,
			$data,
			$active_feature_button_id,
			'advertisers/edit_body',
			'advertisers/edit_header',
			NULL,
			'advertisers/edit_footer',
			NULL
		);
	}

	public function validate_edit_advertisers_post($post_array)
	{
		if ($post_array === false || $post_array['advertiser_name'] === false || $post_array['sales_person'] === false)
		{
			return false;
		}
		return true;
	}

	public function get_allowed_advertiser_owners()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array();
			$allowed_roles = array('sales', 'admin', 'ops');
			$post_variables = array('q', 'page_limit', 'page');
			$response = vl_verify_ajax_call($allowed_roles, $post_variables);

			if($response['is_success'])
			{
				$parameters = array();
				$add_args = array();
				$add_args['user_id'] = $this->tank_auth->get_user_id();
				$parameters['q'] = $this->input->post('q');
				$parameters['page_limit'] = $this->input->post('page_limit');
				$parameters['page'] = $this->input->post('page');
				$response = select2_helper($this->advertisers_model, 'get_select2_sales_users_for_edit_advertiser', $parameters, $add_args);
				
				$this->output->set_output(json_encode($response));
				$this->output->set_status_header(200);
			}
			else
			{
				echo json_encode(array('errors' => "Not authorized - #903813"));
			}
		}
		else
		{
			show_404();
		}
	}

	
	
}

?>
