<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Adgroup_setup extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->helper('form');
		$this->load->helper('url');
		$this->load->library('tank_auth');
		$this->load->library('session');
		$this->load->library('form_validation');
		$this->load->library('csv');
		$this->load->model('al_model');
		$this->load->model('publisher_model');
		$this->load->model('campaign_health_model');
		$this->load->model('tradedesk_model');
		$this->load->model('tag_model');
		$this->load->helper('vl_ajax_helper');
		$this->load->helper('select2_helper');
	}

	public function index()
	{
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer','adgroup_setup');
			redirect('login');
			return;
		}
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		if ($role == 'ops' or $role == 'admin')
		{
			$data['username']   = $username;
			$data['firstname']  = $this->tank_auth->get_firstname($data['username']);
			$data['lastname']   = $this->tank_auth->get_lastname($data['username']);
			$data['user_id']    = $this->tank_auth->get_user_id();
			//for dropdown
			//$data['advertisers'] = $this->tank_auth->get_businesses();

			//$data['vl_campaigns'] = $this->publisher_model->get_all_active_campaigns();
			//$data['advertisers'] = $this->tank_auth->get_businesses();
			$data['vl_campaigns'] = $this->publisher_model->get_all_active_campaigns();

			$data['sales_people'] = $this->tank_auth->get_sales_users();

			$data['active_menu_item'] = "adgroups_menu";//the view will make this menu item active
			$data['title'] = "AL4k [Adgroups]";
			$this->load->view('ad_linker/header',$data);
			$this->load->view('ad_linker/adgroups_form',$data);
		}
		else
		{
			redirect('director');
		}
	}

	public function get_advertiser_details()
	{
		$adv_id = $_GET["adv_id"];
		if(!$this->tank_auth->is_logged_in())
		{
			$return['success'] = false;
			$return['info'] = 'you need to login again';
		}
		else
		{
			$username = $this->tank_auth->get_username();
			$role = $this->tank_auth->get_role($username);
			if ($role == 'ops' or $role == 'admin'){
				$advertiser_row = $this->al_model->get_adv_details($adv_id);
				$return['data'] = $advertiser_row;
				$return['success'] = true;
			}
			else
			{
				$return['success'] = false;
				$return['info'] = 'you are not authorized for some reason';
			}
		}
		echo json_encode($return);
	}

	public function get_campaign_details()
	{
		$c_id = $_POST["c_id"];
		if(!$this->tank_auth->is_logged_in())
		{
			$return['success'] = false;
			$return['info'] = 'you need to login again';
		}
		else
		{
			$username = $this->tank_auth->get_username();
			$role = $this->tank_auth->get_role($username);
			if ($role == 'ops' or $role == 'admin')
			{
				$return['data'] = $this->al_model->get_campaign_details($c_id);
				$return['data_timeseries'] = $this->al_model->get_campaign_timeseries_details($c_id);
				$return['campaign_ttd_data'] = array('sitelist'=>false, 'zip_list'=>false);
				$return['tag_data'] = null;
				$campaign_tag_data = $this->tag_model->retrieve_tag_file_info_for_campaign($c_id);
				$campaign_ttd_data = $this->tradedesk_model->grab_ttd_details_for_campaign($c_id);
				$campaign_zips_data = $this->tradedesk_model->grab_newest_zips_for_campaign($c_id);
				if($campaign_tag_data)
				{
					$return['tag_data'] = $campaign_tag_data;
				}
				if($campaign_ttd_data)
				{
					if($campaign_ttd_data['site_list_contents'])
					{
						$sitelist_data = json_decode($campaign_ttd_data['site_list_contents']);
						$return['campaign_ttd_data']['sitelist']['sitelist_name'] = $sitelist_data->name;
						$return['campaign_ttd_data']['sitelist']['sitelist_data'] = $sitelist_data->data;
					}
				}
				if($campaign_zips_data)
				{
					if($campaign_zips_data['zip_list'])
					{
						$return['campaign_ttd_data']['zip_list']['zips'] = $campaign_zips_data['zip_list'];
						$return['campaign_ttd_data']['zip_list']['bad_zips'] = $campaign_zips_data['bad_zips'];
						$timestamp = $campaign_zips_data['time_created'];
						if(date('I')) //Pacific time for now.
						{
							$timestamp = date("Y-m-d h:i A", strtotime('-7 hours', strtotime($timestamp)));
						}
						else
						{
							$timestamp = date("Y-m-d h:i A", strtotime('-8 hours', strtotime($timestamp)));

						}
						$return['campaign_ttd_data']['zip_list']['timestamp'] = $timestamp;

					}
				}

				$return['success'] = true;
			}
			else
			{
				$return['success'] = false;
				$return['info'] = "how'd you do that?";
			}
		}
		echo json_encode($return);
	}

	public function get_campaign_categories()
	{
		$c_id = $_POST["c_id"];
		if(!$this->tank_auth->is_logged_in())
		{
			$return['success'] = false;
			$return['info'] = 'you need to login again';
		}
		else
		{
			$username = $this->tank_auth->get_username();
			$role = $this->tank_auth->get_role($username);
			if ($role == 'ops' or $role == 'admin')
			{
				$return['data'] = $this->al_model->get_campaign_categories($c_id);
				$return['success'] = true;
			}
			else
			{
				$return['success'] = false;
				$return['info'] = "how'd you do that?";
			}
		}
		echo json_encode($return);
	}

	public function get_adgroup_details()
	{
		$allowed_user_types = array('ops', 'admin');
		$required_post_variables = array('ag_id');
		$ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables);
		if(!$ajax_verify['is_success'])
		{
			echo json_encode($ajax_verify);
			exit;
		}
		else
		{
			$adgroup_id = $ajax_verify['post']['ag_id'];
			$return_array = [
				'is_success' => true,
				'data' => [
					'adgroup_details' => $this->al_model->get_adgroup_details($adgroup_id),
					'geofencing_details' => $this->al_model->get_adgroup_geofencing_data($adgroup_id, true)
				],
				'errors' => []
			];
		}
		echo json_encode($return_array);
	}

	private function get_prefilled_geofencing_data($geofencing_result_array)
	{
		$prefilled_input = '';
		foreach ($geofencing_result_array as $row)
		{
			$prefilled_input .= $this->csv->array_to_csv($row, "\t") . "\n";
		}
		return $prefilled_input;
	}

	public function create_new_advertiser()
	{
		$errors = array();
		if(!array_key_exists('adv', $_POST) || $_POST['adv'] == "")
		{
			array_push($errors, "advertiser name is missing");
		}
		if(!array_key_exists('s_id', $_POST) || $_POST['s_id'] == "")
		{
			array_push($errors, "salesperson id is missing");
		}
		if(!$this->tank_auth->is_logged_in())
		{
			array_push($errors, "advertiser name is missing");
		}
		if(count($errors) > 0)
		{
			$return['success'] = false;
			$return['info'] = implode(", ", $errors);
		}
		else
		{
			$username = $this->tank_auth->get_username();
			$role = $this->tank_auth->get_role($username);
			if ($role == 'ops' or $role == 'admin')
			{
				$insert_array = array(
					"Name" => trim($_POST["adv"]),
					"sales_person" =>  $_POST["s_id"]
				);
				//$return['effected_rows'] = $this->al_model->create_advertiser($insert_array);
				$return['data'] = $this->al_model->create_advertiser($insert_array);
				$return['success'] = ($return['data'] !== 0)? true : false;
			}
			else
			{
				$return['success'] = false;
				$return['info'] = "not authorized: how'd you get here?";
			}
		}
		echo json_encode($return);
	}


	public function create_new_campaign()
	{
		if(!array_key_exists('cats', $_POST))
		{
			$_POST['cats'] = array();
		}
		$errors = array();
		if(!array_key_exists('lp', $_POST) || trim($_POST['lp']) == "")
		{
			array_push($errors, "landing page is missing");
		}
		if(!array_key_exists('c_name', $_POST) || $_POST['c_name'] == "")
		{
			array_push($errors, "campaign name is missing");
		}
		if(!array_key_exists('a_id', $_POST) || $_POST['a_id'] == "")
		{
			array_push($errors, "advertiser_id is missing");
		}
		/*if(!array_key_exists('imprs', $_POST) || $_POST['imprs'] == "")
		{
			array_push($errors, "impression target is missing");
		}
		if(!array_key_exists('s_date', $_POST) || $_POST['s_date'] == "")
		{
			array_push($errors, "start date is missing");
		}
		if((!array_key_exists('e_date', $_POST) || $_POST['e_date'] == "") && (array_key_exists('term_type', $_POST) && $_POST['term_type'] == 'FIXED_TERM'))
		{
			array_push($errors, "campaign end date is missing");
		}
		if(!array_key_exists('term_type', $_POST))
		{
			array_push($errors, "monthly reset type is missing");
		}*/
		if(!array_key_exists('is_archived', $_POST))
		{
			array_push($errors, "archived status is missing");
		}
		/*if(!array_key_exists('invoice_budget', $_POST))
		{
			array_push($errors, "invoice budget is missing");
		}*/
		if(!array_key_exists('clone_from_id', $_POST))
		{
			array_push($errors, "clone from id is missing");
		}
		if(!$this->tank_auth->is_logged_in())
		{
			array_push($errors, "Not logged in");
		}
		if(count($errors) > 0)
		{
			$return['success'] = false;
			$return['info'] = implode(", ", $errors);
		}
		else
		{
			$username = $this->tank_auth->get_username();
			$role = $this->tank_auth->get_role($username);
			$pause_date = $_POST["pause_date"];
			if ($pause_date == "")
				$pause_date=null;
			if ($role == 'ops' or $role == 'admin')
			{
				//a_id, c_name,lp,imprs,e_date
				$insert_array = array(
					"Name" => trim($_POST["c_name"]),
					"business_id" => $_POST["a_id"],
					"TargetImpressions" => $_POST["imprs"],
					"hard_end_date" => $_POST["e_date"] == "" ? NULL : $_POST["e_date"] ,
					"start_date" =>  $_POST["s_date"] ,
					"term_type" =>  $_POST["term_type"],
					"LandingPage" =>  trim($_POST["lp"]),
					"categories" => $_POST["cats"],
					"ignore_for_healthcheck" => $_POST["is_archived"],
					"invoice_budget"=> $_POST["invoice_budget"],
					"pause_date" => $pause_date,
					"clone_from_id" => $_POST["clone_from_id"]
				);

				$return['id'] = $this->al_model->create_campaign($insert_array);
				//$this->delete_create_time_series_for_campaign($return['id'], $_POST["time_series_data"]);

				$return['success'] = ($return['id']>0)? true : false;
			}
			else
			{
				$return['success'] = false;
				$return['info'] = "not authorized: how'd you get here?";
			}
		}
		echo json_encode($return);
	}

	private function delete_create_time_series_for_campaign($campaign_id, $time_series_data)
	{
		$timeseries_insert_array_new = $this->al_model->format_time_series_array_from_string($time_series_data);
		if($timeseries_insert_array_new == 0)
		{
			return 0;
		}
		return $this->al_model->delete_create_time_series_for_campaign($campaign_id, $timeseries_insert_array_new, "EXECUTE");
	}

	public function create_new_adgroup()
	{
		$allowed_user_types = array('ops', 'admin');
		$required_post_variables = array(
			'c_id',
			'ag_ext_id',
			'ag_city',
			'ag_region',
			'ag_src',
			'ag_is_rtg',
			'ag_type',
			'geofence_client_data',
			'ag_type_new',
			'geofence_centers'
		);
		$ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables);
		if(!$ajax_verify['is_success'])
		{
			echo json_encode($ajax_verify);
			exit;
		}
		else
		{
			$return_array = [
				'is_success' => true,
				'errors' => []
			];
			$geofencing_input_from_user = json_decode($ajax_verify['post']['geofence_client_data'], true);
			$has_geofencing_data =
				is_array($geofencing_input_from_user) &&
				(isset($geofencing_input_from_user['bulk_geofence_data']) || isset($geofencing_input_from_user['geofence_form_data']));
			if($ajax_verify['post']['ag_src'] == 'TDGF' && !$has_geofencing_data)
			{
				$return_array['is_success'] = false;
				$return_array['errors'][] = 'Geofencing input is required for adgroup source TDGF';
				echo json_encode($return_array);
				exit;
			}

			$geofencing_data_from_csv = [];
			$geofencing_data_from_form = [];
			if($has_geofencing_data)
			{
				if($geofencing_input_from_user['bulk_geofence_data'])
				{
					$geofencing_data_from_csv_result = $this->get_geofencing_data_from_csv_input($geofencing_input_from_user['bulk_geofence_data']);
					$csv_success = $geofencing_data_from_csv_result['is_success'];
					$geofencing_data_from_csv = $geofencing_data_from_csv_result['data'];
				}
				if($geofencing_input_from_user['geofence_form_data'])
				{
					$geofencing_data_from_form = $geofencing_input_from_user['geofence_form_data'];
				}

				if($geofencing_data_from_csv && !$csv_success)
				{
					echo json_encode($geofencing_data_from_csv_result);
					exit;
				}
			}

			$insert_array = array(
				$ajax_verify['post']['c_id'],
				$ajax_verify['post']['ag_ext_id'],
				$ajax_verify['post']['ag_city'],
				$ajax_verify['post']['ag_region'],
				$ajax_verify['post']['ag_src'],
				$ajax_verify['post']['ag_is_rtg'] == 'true' ? 1 : 0,
				$ajax_verify['post']['ag_type'],
				$ajax_verify['post']['ag_type_new']
			);
			$geofencing_data = array_merge($geofencing_data_from_csv, $geofencing_data_from_form);
			$return_array = $this->al_model->create_adgroup($insert_array, $geofencing_data);
			if($return_array['is_success'] && ($ajax_verify['post']['ag_src'] == 'TDGF'))
			{
				$geofencing_details = $this->al_model->get_adgroup_geofencing_data($return_array['vl_id'], true);
				if($geofencing_details)
				{
					$return_array['data'] = $geofencing_details;
				}
			}
		}
		echo json_encode($return_array);
	}

	public function update_adgroup()
	{
		$allowed_user_types = array('ops', 'admin');
		$required_post_variables = array(
			'ag_ext_id',
			'ag_city',
			'ag_region',
			'ag_src',
			'ag_is_rtg',
			'ag_type',
			'ag_id',
			'geofence_client_data',
			'geofence_centers',
			'ag_type_new'
		);
		$ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables);

		if(!$ajax_verify['is_success'])
		{
			echo json_encode($ajax_verify);
			exit;
		}
		else
		{
			$return_array = [
				'is_success' => true,
				'errors' => []
			];
			$geofencing_input_from_user = json_decode($ajax_verify['post']['geofence_client_data'], true);
			$has_geofencing_data =
				is_array($geofencing_input_from_user) &&
				(isset($geofencing_input_from_user['bulk_geofence_data']) || isset($geofencing_input_from_user['geofence_form_data']));
			if($ajax_verify['post']['ag_src'] == 'TDGF' && !$has_geofencing_data)
			{
				$return_array['is_success'] = false;
				$return_array['errors'][] = 'Geofencing input is required for adgroup source TDGF';
				echo json_encode($return_array);
				exit;
			}

			$geofencing_data_from_csv = [];
			$geofencing_data_from_form = [];
			if($has_geofencing_data)
			{
				$geofencing_data_from_csv_result = null;
				if($geofencing_input_from_user['bulk_geofence_data'])
				{
					$geofencing_data_from_csv_result = $this->get_geofencing_data_from_csv_input($geofencing_input_from_user['bulk_geofence_data']);
					$csv_success = $geofencing_data_from_csv_result['is_success'];
					$geofencing_data_from_csv = $geofencing_data_from_csv_result['data'];
				}
				if($geofencing_input_from_user['geofence_form_data'])
				{
					$geofencing_data_from_form = $geofencing_input_from_user['geofence_form_data'];
				}

				if($geofencing_data_from_csv_result && !$csv_success)
				{
					echo json_encode($geofencing_data_from_csv_result);
					exit;
				}
			}

			$insert_array = array(
				$ajax_verify['post']['ag_ext_id'],
				$ajax_verify['post']['ag_city'],
				$ajax_verify['post']['ag_region'],
				$ajax_verify['post']['ag_src'],
				$ajax_verify['post']['ag_is_rtg'] == 'true' ? 1 : 0,
				$ajax_verify['post']['ag_type'],
				$ajax_verify['post']['ag_type_new'],
				(int)$ajax_verify['post']['ag_id'],
			);

			$geofencing_data = array_merge($geofencing_data_from_csv, $geofencing_data_from_form);
			$was_updated = $this->al_model->update_adgroup($insert_array, $geofencing_data);
			if($was_updated)
			{
				$geofencing_details = $this->al_model->get_adgroup_geofencing_data((int)$ajax_verify['post']['ag_id'], true);
				if($geofencing_details)
				{
					$return_array['data'] = $geofencing_details;
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'][] = 'Updating failed; please try again';
			}
		}
		echo json_encode($return_array);
	}

	private function get_geofencing_data_from_csv_input($geofencing_input_from_user)
	{
		$return_array = [
			'is_success' => true,
			'errors' => [],
			'data' => []
		];

		$headers_and_types = [
			'latitude' => 'double',
			'longitude' => 'double',
			'radius' => 'int',
			'name' => 'string',
			'address' => 'string'
		];
		$expected_column_count = count($headers_and_types);
		$lines = explode("\n", $geofencing_input_from_user);
		$delimiter = $this->guess_csv_delimiter(array_slice($lines, 0, 1), $expected_column_count); // Check the first line
		if($delimiter)
		{
			foreach($lines as $line_number => $line)
			{
				if($line !== '')
				{
					$line_with_headers = array_combine(array_keys($headers_and_types), str_getcsv($line, $delimiter, '"'));

					if($this->are_line_variables_formatted_correctly($headers_and_types, $line_with_headers))
					{
						$return_array['data'][] = $line_with_headers;
					}
					else
					{
						$return_array['is_success'] = false;
						$human_readable_line_number = $line_number + 1;
						$return_array['errors'][] = "Check formatting of text at line {$human_readable_line_number}";
						break;
					}
				}
			}
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['errors'][] = "Check that the number of columns in the file is {$expected_column_count} and comma/tab delimited";
		}
		return $return_array;
	}

	private function are_line_variables_formatted_correctly($headers_and_types, &$line_to_check)
	{
		foreach($headers_and_types as $header => $type)
		{
			if($type == 'double' && !is_numeric($line_to_check[$header]))
			{
				return false;
			}
			else if($type == 'int' && !ctype_digit($line_to_check[$header]))
			{
				return false;
			}
			settype($line_to_check[$header], $type);
		}
		return true;
	}

	private function guess_csv_delimiter(array $first_lines, $column_count)
	{
		$count_tsv_fields = [];
		$count_csv_fields = [];
		foreach ($first_lines as $line)
		{
			$count_tsv_fields[] = count(str_getcsv($line, "\t", '"'));
			$count_csv_fields[] = count(str_getcsv($line, ',', '"'));
		}
		if(count(array_unique($count_tsv_fields)) === 1 && $count_tsv_fields[0] === $column_count)
		{
			return "\t";
		}
		else if(count(array_unique($count_csv_fields)) === 1 && $count_csv_fields[0] === $column_count)
		{
			return ',';
		}
		return false;
	}

	public function get_vl_advertisers_dropdown()
	{
		$advertisers = $this->tank_auth->get_businesses();
		echo '<option></option>';
		echo '<option value="none">Select Advertiser</option><option value="new">*New*</option>';
		foreach($advertisers as $advertiser)
		{
			echo '<option value="' .$advertiser['id']. '">' .$advertiser['Name']. '</option>';
		}
	}

	public function  get_vl_campaigns_dropdown()
	{
		$advertiser_id = $_POST["adv_id"];
		$campaigns = $this->tank_auth->get_campaigns_by_id($advertiser_id);
		if($campaigns != null)
		{
			foreach($campaigns as $campaign)
			{
				echo '<option value="' .$campaign['id']. '">' .$campaign['Name']. '</option>';
			}
		}
		echo '<option value="new">*New*</option>';
	}

	public function get_vl_adgroups_dropdown()
	{
		$c_id = $_POST["c_id"];
		$adgroups = $this->tank_auth->get_adgroups_by_c_id($c_id);
		echo '<option value="new">*New*</option>';
		foreach($adgroups as $adgroup)
		{
		echo '<option value="' .$adgroup['vl_id']. '">' .$adgroup['ID']. '</option>';

		}
	}
	
	public function get_subproduct_type_dropdown()
	{
		$subproduct_types = $this->al_model->get_all_subproduct_types();
		echo '<option value="0">Select Type</option>';
		foreach($subproduct_types as $subproduct_type)
		{
			echo '<option value="' .$subproduct_type['Id']. '">' .$subproduct_type['name']. '</option>';
		}
	}

	public function update_advertiser()
	{

		$errors = array();
		if(!array_key_exists('s_id', $_POST))
		{
			array_push($errors, "salesperson id is missing");
		}
		if(!array_key_exists('adv_id', $_POST))
		{
			array_push($errors, "advertiser id is missing");
		}
		if(!$this->tank_auth->is_logged_in())
		{
			array_push($errors, "Not logged in");
		}
		if(count($errors) > 0)
		{
			$return['success'] = false;
			$return['info'] = implode(", ", $errors);
		}
		else
		{
			$username = $this->tank_auth->get_username();
			$role = $this->tank_auth->get_role($username);
			if ($role == 'ops' or $role == 'admin')
			{
				$insert_array = array(
					"sales_person" =>  $_POST["s_id"],
					"id" => trim($_POST["adv_id"])
				);
				//$return['effected_rows'] = $this->al_model->create_advertiser($insert_array);
				$return['success'] = ($this->al_model->update_advertiser($insert_array)>0)? true : false;
			}
			else
			{
				$return['success'] = false;
				$return['info'] = "not authorized: how'd you get here?";
			}
		}
		echo json_encode($return);
	}

	public function rename_campaign()
	{
		$is_success = false;
		$errors = array();

		$allowed_user_types = array('ops', 'admin');
		$required_post_variables = array('c_id', 'campaign_name');
		$ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables); //post variables saved to ['post']['name']
		if($ajax_verify['is_success'])
		{
			$campaign_id = $ajax_verify['post']['c_id'];
			$campaign_name = $ajax_verify['post']['campaign_name'];
			$campaign_rename_response = $this->al_model->update_campaign_name($campaign_id, $campaign_name);
			$is_success = $campaign_rename_response['is_success'];
			$errors[] = $campaign_rename_response['errors'];
		}
		else
		{
			foreach($ajax_verify['errors'] as $err)
			{
				$errors[] = $err;
			}
		}

		echo json_encode(
			array('is_success'=>$is_success,
				'errors'=>$errors
				)
			);
	}

	public function rename_advertiser()
	{
		$is_success = false;
		$errors = array();

		$allowed_user_types = array('ops', 'admin');
		$required_post_variables = array('a_id', 'adv_name');
		$ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables); //post variables saved to ['post']['name']
		if($ajax_verify['is_success'])
		{
			$advertiser_id = $ajax_verify['post']['a_id'];
			$advertiser_name = $ajax_verify['post']['adv_name'];
			$advertiser_rename_response = $this->al_model->update_advertiser_name($advertiser_id, $advertiser_name);
			$is_success = $advertiser_rename_response['is_success'];
			$errors[] = $advertiser_rename_response['errors'];
		}
		else
		{
			foreach($ajax_verify['errors'] as $err)
			{
				$errors[] = $err;
			}
		}

		echo json_encode(
			array('is_success'=>$is_success,
				'errors'=>$errors
				)
			);
	}

	public function update_campaign()
	{
		$errors = array();
		if(!array_key_exists('lp', $_POST) || trim($_POST['lp']) == "")
		{
			array_push($errors, "landing page is missing");
		}
		/*if(!array_key_exists('imprs', $_POST) || $_POST['imprs'] == "")
		{
			array_push($errors, "impression target is missing");
		}
		if(!array_key_exists('s_date', $_POST) || $_POST['s_date'] == "")
		{
			array_push($errors, "start date is missing");
		}
		if((!array_key_exists('e_date', $_POST) || $_POST['e_date'] == "") && (!array_key_exists('term_type', $_POST) AND $_POST['term_type'] != 'FIXED_TERM'))
		{
			array_push($errors, "campaign end date is missing");
		}
		if(!array_key_exists('term_type', $_POST))
		{
			array_push($errors, "monthly reset type is missing");
		}*/
		if(!array_key_exists('is_archived', $_POST))
		{
			array_push($errors, "archive status is missing");
		}
		/*if(!array_key_exists('invoice_budget', $_POST))
		{
			array_push($errors, "invoice budget is missing");
		}*/
		if(!$this->tank_auth->is_logged_in())
		{
			array_push($errors, "Not logged in");
		}
		if(count($errors) > 0)
		{
			$return['success'] = false;
			$return['info'] = implode(", ", $errors);
		}
		else
		{
			$username = $this->tank_auth->get_username();
			$role = $this->tank_auth->get_role($username);

			$pause_date = $_POST["pause_date"];
			if ($pause_date == "")
				$pause_date=null;

			if ($role == 'ops' or $role == 'admin'){
			//c_id: c_id,lp: lp,imprs: imprs, e_date: e_date
				$insert_array = array(
					"LandingPage" =>  trim($_POST["lp"]),
					"TargetImpressions" =>  $_POST["imprs"],
					"hard_end_date" =>  $_POST["e_date"] == "" ? NULL : $_POST["e_date"],
					"start_date" =>  $_POST["s_date"],
					"term_type" =>  $_POST["term_type"],
					"ignore_for_healthcheck"=>$_POST["is_archived"],
					"invoice_budget"=>$_POST["invoice_budget"],
					"pause_date" => $pause_date,
					"id" => $_POST["c_id"]
				);
				if(array_key_exists('cats', $_POST))
				{
					$insert_array['categories'] = $_POST['cats'];
				}
				$return['success'] = ($this->al_model->update_campaign($insert_array) > 0) ? true : false;
				//$this->delete_create_time_series_for_campaign($_POST["c_id"], $_POST["time_series_data"]);
				//$return['effected_rows'] = $this->al_model->create_advertiser($insert_array);
			}else{
				$return['success'] = false;
				$return['info'] = "not authorized: how'd you get here?";
			}
		}
		echo json_encode($return);
	}

	public function refresh_cache_campaign_report() {
		$campaign_id = $this->input->post('campaign_id');
		$this->al_model->refresh_cache_campaign_report($campaign_id);
		$return['success'] = true;
		echo json_encode($return);
	}

	public function get_active_campaigns() {
		if($_SERVER['REQUEST_METHOD'] === 'POST' && $this->tank_auth->is_logged_in())
		{
			$username = $this->tank_auth->get_username();
			$role = $this->tank_auth->get_role($username);

			if (in_array(strtolower($role), array('admin', 'ops')))
			{
				$post_array = $this->input->post();
				$post_array['q'] = str_replace(" ", "%", $post_array['q']);

				$response = select2_helper($this->publisher_model, 'get_all_active_campaigns_select2', $post_array);

				if (empty($response['results']) || $response['errors'])
				{
					$response['results'] = array();
				}

				echo json_encode($response);
			}
			else
			{
				echo json_encode(array('errors' => "Not authorized"));
			}
		}
		else
		{
			show_404();
		}
	}
	public function get_advertisers()
	{
		$advertiser_array = array('results' => array(), 'more' => false);
		if($_SERVER['REQUEST_METHOD'] === 'POST' && $this->tank_auth->is_logged_in())
		{
			$post_array = $this->input->post();
			if(array_key_exists('q', $post_array) AND array_key_exists('page', $post_array) AND array_key_exists('page_limit', $post_array))
			{
				if($post_array['page'] == 1)
				{
					$advertiser_array['results'][] = array(
						'id' => 'new',
						'text' => '*New*',
						'id_list' => "N/A"
						);
				}
				$advertiser_response = select2_helper($this->al_model, 'get_advertisers_for_select2', $post_array);

				if (!empty($advertiser_response['results']) && !$advertiser_response['errors'])
				{
					$advertiser_array['more'] = $advertiser_response['more'];
					for($i = 0; $i < $advertiser_response['real_count']; $i++)
					{
						$advertiser_array['results'][] = array(
							'id' => $advertiser_response['results'][$i]['id'],
							'text' => $advertiser_response['results'][$i]['text'],
							'id_list' => $advertiser_response['results'][$i]['id_list']
						);
					}
				}
			}
		echo json_encode($advertiser_array);
		}
		else
		{
			show_404();
		}
	}

	public function get_tag_files_for_campaign()
	{
		$tags_array = array('results' => array(), 'more' => false);
		if($_SERVER['REQUEST_METHOD'] === 'POST' && $this->tank_auth->is_logged_in())
		{
			$post_array = $this->input->post();
			if(array_key_exists('q', $post_array) AND array_key_exists('page', $post_array) AND array_key_exists('page_limit', $post_array) AND array_key_exists('c_id', $post_array))
			{
				$tags_response = select2_helper($this->al_model, 'get_tag_files_for_select2', $post_array, array($post_array['c_id']));

				if (!empty($tags_response['results']) && !$tags_response['errors'])
				{
					$tags_array['more'] = $tags_response['more'];
					for($i = 0; $i < $tags_response['real_count']; $i++)
					{
						$tags_array['results'][] = array(
							'id' => $tags_response['results'][$i]['id'],
							'text' => $tags_response['results'][$i]['text']
						);
					}
				}
			}
			echo json_encode($tags_array);
		}
		else
		{
			show_404();
		}
	}

	public function trash_campaign()
	{
		$result = array();
		$result['is_success'] = true;

		$allowed_user_types = array('ops', 'admin');
		$required_post_variables = array('c_id');
		$ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables); //post variables saved to ['post']['name']
		if($ajax_verify['is_success'])
		{
			$campaign_id = $ajax_verify['post']['c_id'];
			$did_trash_campaign = $this->al_model->trash_campaign($campaign_id);
			if(!$did_trash_campaign)
			{
				$result['is_success'] = false;
			}
		}
		else
		{
			$result['is_success'] = false;
		}

		echo json_encode($result);
	}

}//class
