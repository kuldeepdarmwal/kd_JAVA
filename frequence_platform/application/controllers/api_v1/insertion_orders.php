<?php defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH.'/libraries/REST_Controller.php';
require FCPATH.'/vendor/autoload.php';

class Insertion_orders extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('mpq_v2_model');
		$this->load->model('insertion_order_model');
		$this->load->helper('url');
		$this->load->library('map');
	}

	public function index_get($insertion_order_id = null)
	{
		$after_id = $this->input->get('after_id', true) ?: null;
		$since_id = $this->input->get('since_id', true) ?: null;
		$count = $this->input->get('count', true) && $this->input->get('count', true) <= 100 ? $this->input->get('count', true) : 100; // limit to 100 results
		$start_date = $this->input->get('start_date', true) ?: null;
		$end_date = $this->input->get('end_date', true) ?: null;

		$errors = array();
		if (!empty($start_date) && !$this->validate_date($start_date))
		{
			$errors[] = "'start_date' must be a valid date string";
		}
		if (!empty($end_date) && !$this->validate_date($end_date))
		{
			$errors[] = "'end_date' must be a valid date string";
		}
		if (!empty($errors))
		{
			$this->response(
				array('errors' => $errors),
				400
			);
		}

		$user_id = $this->_allow->id;

		try
		{
			$insertion_orders = $this->insertion_order_model->get_submitted_insertion_orders(
				$user_id,
				$after_id,
				$since_id,
				$count,
				$start_date,
				$end_date,
				$insertion_order_id
			);
		}
		catch (Exception $e)
		{
			$this->response(
				array('errors' => array($e->getMessage())),
				$e->getCode()
			);
		}

		/* TODO: check if this stuff exists */
		foreach ($insertion_orders as &$io)
		{
			if (!empty($io->region_data))
			{
				$io->region_data = json_decode($io->region_data);
				if(is_array($io->region_data))
				{
					$io->region_data = $io->region_data[0];
				}
				$this->map->convert_old_flexigrid_format_object($io->region_data);
				$io->region_data = $io->region_data->ids->zcta;
			}

			if (!empty($io->target_demographics))
			{
				$io->target_demographics = $this->parse_demo_string($io->target_demographics);
			}

			if (!empty($io->creative_files))
			{
				$io->creative_files = empty($io->creative_files) ? array() : json_decode($io->creative_files);
				$io->creative_files = array_map(function($file)
				{
					return substr($file->name, 7, strlen($file->name) - 7);
				}, $io->creative_files);
			}
		}
		
		$this->response($insertion_orders);
	}

	public function index_post()
	{
		$this->return_405();
	}

	public function index_put()
	{
		$this->return_405();
	}

	public function index_delete()
	{
		$this->return_405();
	}

	public function authorize_post()
	{
		try
		{
			$mpq_session_id = $this->mpq_v2_model->initialize_mpq_session($this->session->userdata('session_id'), $this->_allow->id);
		}
		catch (Exception $e)
		{
			$this->response(
				array('error' => $e->getMessage()),
				$e->getCode()
			);
		}

		$token_content = array(
			"sub" => "mpq_session",
			"aud" => $this->_allow->id,
			"iat" => time(),
			"mpq_session_id" => $mpq_session_id
		);
		$token = JWT::encode($token_content, NULL); // TODO: encode with secret

		$this->response(base_url()."embed/insertion_order/".$token);
	}

	public function base_url_get()
	{
		$this->response(base_url());
	}

	public function results_get($insertion_order_id = null)
	{
		if (empty($insertion_order_id))
		{
			$this->response("insertion order ID is required", 400);
		}

		$start_date = $this->input->get('start_date', true) ?: null;
		$end_date = $this->input->get('end_date', true) ?: null;

		$errors = array();
		if (!empty($start_date) && !$this->validate_date($start_date))
		{
			$errors[] = "'start_date' must be a valid date string";
		}
		if (!empty($end_date) && !$this->validate_date($end_date))
		{
			$errors[] = "'end_date' must be a valid date string";
		}

		try
		{
			$results = $this->insertion_order_model->get_campaign_results($insertion_order_id, $start_date, $end_date);
		}
		catch (Exception $e)
		{
			$this->response(
				array('error' => $e->getMessage()),
				$e->getCode()
			);
		}

		$response = array(
			'preroll_performance' => array(
				'impressions' => $results[0]->preroll_impressions,
				'clicks' => $results[0]->preroll_clicks,
				'25_percent_count' => $results[0]->preroll_25_percent_count,
				'50_percent_count' => $results[0]->preroll_50_percent_count,
				'75_percent_count' => $results[0]->preroll_75_percent_count,
				'100_percent_count' => $results[0]->preroll_100_percent_count
			)
		);
		
		$this->response($response);
	}

	public function results_post()
	{
		$this->return_405();
	}

	public function results_put()
	{
		$this->return_405();
	}

	public function results_delete()
	{
		$this->return_405();
	}

	private function parse_demo_string($demo_string)
	{
		$demo_bools = explode("_", $demo_string);

		$demos = array(
			'Gender' => array(
				'Male' => false,
				'Female' => false
			),
			'Age' => array(
				'Under 18' => false,
				'18-24' => false,
				'25-34' => false,
				'35-44' => false,
				'45-54' => false,
				'55-64' => false,
				'65+' => false
			),
			'Income' => array(
				'0-50k' => false,
				'50-100k' => false,
				'100-150k' => false,
				'150k +' => false
			),
			'Education' => array(
				'No College' => false,
				'Undergrad' => false,
				'Grad School' => false
			),
			'Parenting' => array(
				'No Kids' => false,
				'Has Kids' => false
			)
		);

		$i = 0;

		foreach($demos as $group_key => &$demo_group)
		{
			$demo_all_switch = true;
			$activated_demos = array();

			foreach($demo_group as $demo_key => &$demo)
			{
				$demo = (bool) $demo_bools[$i];
				$demo_prev = isset($demo_prev) ? $demo_prev : $demo;

				if ($demo != $demo_prev)
				{
					$demo_all_switch = false;
				}

				if ($demo)
				{
					$activated_demos[] = $demo_key;
				}

				$demo_prev = $demo;
				$i++;
			}

			unset($demo_prev);

			if ($demo_all_switch)
			{
				$demo_group = 'All';
			}
			else
			{
				$demo_group = $activated_demos;
			}
		}

		return $demos;
	}

	private function validate_date($date)
	{
	    $d = DateTime::createFromFormat('Y-m-d', $date);
	    return $d && $d->format('Y-m-d') == $date;
	}

	private function return_405()
	{
		$this->response(
			array('error' => 'Method Not Allowed'),
			405
		);
	}
}
?>