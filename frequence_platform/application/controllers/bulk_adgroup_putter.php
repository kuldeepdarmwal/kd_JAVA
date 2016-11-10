<?php

class Bulk_adgroup_putter extends CI_Controller
{
	function __construct()
	{
		parent::__construct();

		$this->load->helper(array('form', 'url', 'vl_ajax', 'tradedesk', 'tradedesk_api_v2_validation', 'tradedesk_api_v3_validation', 'ad_server_type'));
		$this->load->library(array('form_validation', 'tank_auth'));
		$this->load->model('tradedesk_model');
	}

	public $g_csv_array = array();

	public function index()
	{
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer', 'bulk_adgroup_putter');
			redirect('login');
			return;
		}
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		if ($role == 'ops' || $role == 'admin')
		{
			///setup common header
			$data['username'] = $username;
			$data['firstname'] = $this->tank_auth->get_firstname($data['username']);
			$data['lastname'] = $this->tank_auth->get_lastname($data['username']);
			$data['user_id'] = $this->tank_auth->get_user_id();

			$data['active_menu_item'] = "bulk_adgroup_putter_menu";
			$data['title'] = "Bulk Adgroup Putter";

			$this->form_validation->set_rules('userfile', 'File', 'callback_check_file');
			if ($this->form_validation->run() == FALSE)
			{
				$this->load->view('ad_linker/header', $data);
				$this->load->view('bulk_adgroup_putter/bulk_adgroup_form_body_view', $data);
			}
			else
			{
				$file_contents = $this->g_csv_array;
				$inputs_valid = true;

				$validation_result_array = array();

				$cleaned_file_contents = array();
				foreach($file_contents['array_data'] as $ii => $row)
				{
					$test = array_filter($row);
					if (!empty($test))
					{
						$row = array_map('trim', $row);

						$this->remove_non_nullable_empty_columns($row);

						$row_validation = $this->validate_file_line($row);
						$validation_result_array[$row['AdGroupId']] = $row_validation;
						if ($row_validation['is_success'])
						{
							$is_json_parsed = $this->expand_json($row);
							if ($is_json_parsed)
							{
								$cleaned_file_contents[] = $row;
							}
							else
							{
								$inputs_valid = false;
							}
						}
						else
						{
							$inputs_valid = false;
						}
					}
				}

				$file_contents['array_data'] = $cleaned_file_contents;
				
				$data['validation_array'] = $validation_result_array;
				$data['file_data'] = $_FILES;
				$data['data_array'] = $file_contents;

				$data['inputs_valid'] = $inputs_valid;
				if(!$inputs_valid)
				{
					$problem_fields = array();
					$this->load->library('table');
					$this->table->set_heading('Adgroup ID', 'Error');
					$tmpl = array ('table_open'=> '<table class="table table-hover table-condensed">');
					$this->table->set_template($tmpl);
					//process the validation array to come up with meaningful messages
					foreach($validation_result_array as $row_id => $row_results)
					{
						$problem_fields[$row_id] = implode('</span><span class="label label-important">', array('AdGroupId', 'Error'));
						foreach ($row_results['errors'] as $error)
						{
							$this->table->add_row("{$row_id}", '<span class="label label-important">' . $error . '</span>');
						}
						
					}
					$data['error_table'] = $this->table->generate();;
					$data['problem_fields'] = $problem_fields;

					$this->load->view('ad_linker/header', $data);
					$this->load->view('bulk_adgroup_putter/bulk_adgroup_invalid_inputs_view', $data);
				}
				else
				{
					$this->load->view('ad_linker/header', $data);
					$this->load->view('bulk_adgroup_putter/bulk_adgroup_complete_view', $data);
				}
			}
		}
		else
		{ 
			redirect('director'); 
		}
	}

	private function remove_non_nullable_empty_columns(&$row)
	{
		if (array_key_exists('RTBAttributes::AudienceTargeting::AudienceId', $row) && array_key_exists('RTBAttributes::AudienceTargeting::RecencyAdjustments', $row))
		{
			if (!$row['RTBAttributes::AudienceTargeting::AudienceId'] && !$row['RTBAttributes::AudienceTargeting::RecencyAdjustments'])
			{
				unset($row['RTBAttributes::AudienceTargeting::AudienceId']);
				unset($row['RTBAttributes::AudienceTargeting::RecencyAdjustments']);
			}
		}

		if (array_key_exists('RTBAttributes::GeoSegmentAdjustments', $row))
		{
			if (!$row['RTBAttributes::GeoSegmentAdjustments'])
			{
				unset($row['RTBAttributes::GeoSegmentAdjustments']);
			}
		}

		if (array_key_exists('RTBAttributes::SiteListIds', $row))
		{
			if (!$row['RTBAttributes::SiteListIds'])
			{
				unset($row['RTBAttributes::SiteListIds']);
			}
		}
	}

	private function validate_file_line($line)
	{
		$return_array = array(
			'is_success' => true,
			'errors' => array()
		);
		
		if (is_array($line))
		{
			if (array_key_exists('AdGroupId', $line))
			{
				foreach ($line as $column_string => $value)
				{
					$column_hierarchy = explode('::', $column_string);
					switch ($column_hierarchy[0])
					{
						case 'AdGroupId':
							break;
						case 'IsEnabled':
							if (($value === null || $value == '') || (strtolower($value) != 'true' && strtolower($value) != 'false'))
							{
								$return_array['is_success'] = false;
								$return_array['errors'][] = 'The IsEnabled field must not be null and must either be "true" or "false"';
							}
							break;
						case 'IndustryCategoryId':
							if (is_numeric($value))
							{
								if ((int)$value != $value)
								{
									$return_array['is_success'] = false;
									$return_array['errors'][] = 'IndustryCategoryId must be an integer';
								}
							}
							else if (!($value === null || $value == ''))
							{
								$return_array['is_success'] = false;
								$return_array['errors'][] = 'IndustryCategoryId must be an integer or null';
							}
							break;
						case 'RTBAttributes':
							if ($column_hierarchy[1] == 'AutoOptimizationSettings')
							{
								$adgroup_data = $this->tradedesk_model->get_adgroup($line['AdGroupId']);
								$is_success = validate_rtb_optimization_attributes(array_slice($column_hierarchy, 1), $adgroup_data, $value, $return_array['errors']);
								$return_array['is_success'] = (!$is_success ? false : $return_array['is_success']);
							}
							else 
							{
								$is_success = validate_rtb_attributes(array_slice($column_hierarchy, 1), $value, $return_array['errors']);
								$return_array['is_success'] = (!$is_success ? false : $return_array['is_success']);
							}
							
							break;
						default:
							$return_array['errors'][] = "Not a valid column type: {$column_hierarchy[0]}";
							$is_success = false;
							break;
					}
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'][] = 'AdGroupId required for update.';
			}
		}
		return $return_array;
	}

	private function expand_json(&$line)
	{
		if (array_key_exists('RTBAttributes::AudienceTargeting::RecencyAdjustments', $line))
		{
			$line['RTBAttributes::AudienceTargeting::RecencyAdjustments'] = json_decode($line['RTBAttributes::AudienceTargeting::RecencyAdjustments'], true);
			return (bool)$line['RTBAttributes::AudienceTargeting::RecencyAdjustments'];
		}

		if (array_key_exists('RTBAttributes::GeoSegmentAdjustments', $line))
		{
			$line['RTBAttributes::GeoSegmentAdjustments'] = json_decode($line['RTBAttributes::GeoSegmentAdjustments'], true);
			return (bool)$line['RTBAttributes::GeoSegmentAdjustments'];
		}

		return true;
	}

	public function check_file($file)
	{
		if($_FILES['userfile']['error'] > 0)
		{
			$this->form_validation->set_message('check_file', "No file found ");
			return false;
		}
		elseif(end(explode('.', $_FILES['userfile']['name'])) != 'csv')
		{
			$this->form_validation->set_message('check_file', "CSV file required ");
			return false;
		}
		else
		{
			$this->g_csv_array = $this->csv_to_array();
			$this->form_validation->set_message('check_file', "Unusual CSV format detected ");
			return $this->g_csv_array['is_success'];
		}
		
	}

	private function csv_to_array()
	{
		$array = $fields = array(); 
		$i = 0;
		$handle = @fopen($_FILES['userfile']['tmp_name'], "r");
		if ($handle) {
			while (($row = fgetcsv($handle)) !== false) 
			{
				if (empty($fields)) 
				{
					$fields = $row;
					if(count($fields)<=1)
					{
						return array('is_success' => false);
					}
					continue;
				}

				foreach ($row as $k => $value) 
				{
					$array[$i][trim($fields[$k])] = $value;
				}
				$i++;
			}
			if (!feof($handle)) 
			{
				echo "Error: unexpected fgets() fail\n";
				return array('is_success'=>false);
			}
			fclose($handle);
		}
		return array('is_success'=>true,'array_data'=>$array);
	}

	private function get_hierarchy_array($hierarchy_string, $content)
	{
		$hierarchy_array = explode('::', $hierarchy_string);
		$arr = array();
		$ref = &$arr;
		foreach ($hierarchy_array as $key)
		{
			$ref[$key] = array();
			$ref = &$ref[$key];
		}

		if ($content === 'null')
		{
			$ref = null;
		}
		else 
		{
			if (is_array($content))
			{
				$ref = $content;
			}
			else 
			{
				$json_slashes = json_decode($content, true);
				$json_no_slashes = json_decode(stripslashes($content), true);

				if (!empty($json_slashes))
				{
					$ref = $json_slashes;
				}
				else if (!empty($json_no_slashes))
				{
					$ref = $json_no_slashes;
				}
				else
				{
					$ref = $content;
				}
			}
		}

		return $arr;
	}

	public function load_line_item()
	{
		$allowed_user_types = array('admin', 'ops');
		$required_post_variables = array('line_item');

		$ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables);
		if($ajax_verify['is_success'])
		{
			$ttd_blob = array();
			$ttd_blob_opt = array();
			$is_success = 1;
			$error_message = '';
			$results['v3']['is_success'] = 1;
			$results['v2']['is_success'] = 1;
			$results['v2']['ttd_response'] = ''; 
			$results['v2']['url'] = '';
			$results['v3']['ttd_response'] = '';
			$results['v3']['url'] = '';
			
			
			foreach ($ajax_verify['post']['line_item'] as $col_name => $col_val)
			{
				if (strpos($col_name,'AutoOptimizationSettings'))
				{
					$hierarchy1 = $this->get_hierarchy_array($col_name, $col_val);
					$ttd_blob_opt = array_merge_recursive($ttd_blob_opt, $hierarchy1);
				}
				else 
				{
					$hierarchy = $this->get_hierarchy_array($col_name, $col_val);
					$ttd_blob = array_merge_recursive($ttd_blob, $hierarchy);
				}
			}

			if(sizeof($ttd_blob_opt) > 0)
			{
				$ttd_blob_opt['AdGroupId'] = $ajax_verify['post']['line_item']['AdGroupId'];
				$ttd_json_opt = json_encode($ttd_blob_opt);
				$results['v3'] = $this->tradedesk_model->put_adgroup($ttd_json_opt, 'v3');
			}
			if(sizeof($ttd_blob) > 1)
			{
				$ttd_json = json_encode($ttd_blob);
				$results['v2'] = $this->tradedesk_model->put_adgroup($ttd_json, 'v2');
			}
			
			if ($results['v3']['is_success'] == '')
			{
				$is_success = '';
				$error_message = $results['v3']['errors'];
				
			}
			elseif($results['v2']['is_success'] == '')
			{
				$is_success = '';
				$error_message = $results['v2']['errors'];
			}
			
			echo json_encode(array(
				'is_success' => $is_success,
				'adgroup_id' => ((!empty($ttd_blob['AdGroupId'])) ? $ttd_blob['AdGroupId'] : null),
				'errors' => $error_message,
				'ttd_request' => $ttd_blob,
				'ttd_response' => $results['v2']['ttd_response'],
				'ttd_url_hit' => $results['v2']['url'],
				'ttd_v3_request' => $ttd_blob_opt,
				'ttd_v3_response' => $results['v3']['ttd_response'],
				'ttd_v3_url_hit' => $results['v3']['url']
			));
		}
	}
}

