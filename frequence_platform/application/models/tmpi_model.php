<?php

class Tmpi_model extends CI_Model
{
	private $raw_db;

	public function __construct()
	{
		parent::__construct();

		$this->load->database();
		$this->load->helper('spectrum_traffic_system_helper');
		$this->raw_db = $this->load->database('td_intermediate', true);
	}

	private function create_sql_values_order_and_duplicate_key_update_values(
		&$values_list_sql,
		&$duplicate_key_update_list_sql,
		$map_csv_column_name_to_database_field_name,
		$csv_first_row,
		$date = null,
		$columns_to_increment_for_duplicate_key_update = null
	)
	{
		$map_csv_column_index_to_database_field_name = array();

		foreach($map_csv_column_name_to_database_field_name as $csv_column => $database_field)
		{
			$csv_index = array_search($csv_column, $csv_first_row);
			if($csv_index !== false)
			{
				$map_csv_column_index_to_database_field_name[$csv_index] = $database_field;
			}
			else
			{
				$column_names = implode(",", $csv_first_row);
				throw new Exception("Failed to find csv_column '$csv_column' in csv first row. column_names: $column_names (#4798170)");
			}
		}

		ksort($map_csv_column_index_to_database_field_name);

		$num_csv_columns = count($csv_first_row);
		$num_map_items = count($map_csv_column_index_to_database_field_name);
		end($map_csv_column_index_to_database_field_name);
		$last_map_index = key($map_csv_column_index_to_database_field_name);
		if($num_map_items - 1 !== $last_map_index || $num_map_items !== $num_csv_columns)
		{
			$column_names = implode(",", $csv_first_row);
			throw new Exception("Unhandled or mismatched columns num_map_items: $num_map_items, last_index: $last_map_index, num_csv_columns: $num_csv_columns, column_names: $column_names (#9074307)");
		}

		$values_list_sql = "(";
		$duplicate_key_update_list_sql = "";
		foreach($map_csv_column_index_to_database_field_name as $index => $field_name)
		{
			if($index != 0)
			{
				$values_list_sql .= ",";
				$duplicate_key_update_list_sql .= ",";
			}

			$values_list_sql .= "$field_name";

			$should_increment_column_value =
				is_array($columns_to_increment_for_duplicate_key_update) &&
				array_key_exists($field_name, $columns_to_increment_for_duplicate_key_update) &&
				$columns_to_increment_for_duplicate_key_update[$field_name];
			$duplicate_key_update_list_sql .= ($should_increment_column_value) ? "$field_name = $field_name + VALUES($field_name)" : "$field_name = VALUES($field_name)";
		}

		if(!empty($date))
		{
			$values_list_sql .= ",date";
			$duplicate_key_update_list_sql .= ",date = VALUES(date)";
		}
		$values_list_sql .= ")";
	}

	private function upload_tmpi_csv_to_raw_table(
		$working_directory,
		$directory_listing,
		$file_name_match,
		$destination_table_name,
		$map_csv_column_to_database_field,
		$date = null,
		$special_data_format_function = null,
		$columns_to_increment_for_duplicate_key_update = null
	)
	{
		$result = array(
			'is_success' => false,
			'info' => array(),
			'debug_info' => array(),
			'errors' => array()
		);

		$matches = preg_grep($file_name_match, $directory_listing);
		if(!empty($matches))
		{
			$base_file_name = reset($matches);
			$full_file_name = $working_directory.'/'.$base_file_name;
			$file_lines = file($full_file_name, FILE_IGNORE_NEW_LINES);

			if($file_lines !== false)
			{
				$csv_data = array_map(function($line) {
					return str_getcsv($line, "\t", "'");
				}, $file_lines);

				$this->remove_unwanted_columns_from_csv_array($csv_data, $map_csv_column_to_database_field);

				if(!empty($csv_data))
				{
					$num_csv_lines = count($csv_data);
					$num_csv_header_columns = count($csv_data[0]);
					$result['debug_info'][] = "num_csv_header_columns: $num_csv_header_columns\n";
					$are_all_columns_present = true;

					$values_to_insert_sql = "";
					$values_to_insert_bindings = array();

					$first_row = $csv_data[0];

					$values_order_sql = "";
					$duplicate_key_update_list_sql = "";

					try
					{
						$this->create_sql_values_order_and_duplicate_key_update_values(
							$values_order_sql,
							$duplicate_key_update_list_sql,
							$map_csv_column_to_database_field,
							$first_row,
							$date,
							$columns_to_increment_for_duplicate_key_update
						);
					}
					catch(Exception $except)
					{
						throw new Exception($except->getMessage()." file: $base_file_name");
					}

					$account_id_index = false;
					$listing_id_index = false;
					$captured_index = false;
					$report_month_index = false;
					foreach($first_row as $index => $value)
					{
						switch($value)
						{
							case 'acc_ID':
								$account_id_index = $index;
								break;
							case 'lst_id':
								$listing_id_index = $index;
								break;
							case 'Captured':
								$captured_index = $index;
								break;
							case 'rptMonth':
								$report_month_index = $index;
								break;
							default:
								break;
						}
					}

					if($account_id_index === false)
					{
						$result['errors'][] = "Failed to find 'acc_ID' column in $base_file_name";
						return $result;
					}

					$num_columns_to_insert = count($map_csv_column_to_database_field);
					if($num_columns_to_insert !== $num_csv_header_columns)
					{
						$result['errors'][] = "Num header columns doesn't match num items in columns map for $base_file_name. (#87176190)";
					}

					$values_list_sql = "(";
					for($ii = 0; $ii < $num_columns_to_insert; $ii++)
					{
						if($ii == 0)
						{
							$values_list_sql .= "?";
						}
						else
						{
							$values_list_sql .= ",?";
						}
					}

					if(!empty($date))
					{
						$values_list_sql .= ",?";
					}

					$values_list_sql .= ")";

					$k_num_rows_per_insert = 30000;
					$csv_row_index = 1;
					$is_inserts_success = true;

					$initial_insert_database =& $this->raw_db;

					$clear_raw_table_sql = "
						TRUNCATE TABLE $destination_table_name
					";
					if(!$initial_insert_database->query($clear_raw_table_sql, $values_to_insert_bindings))
					{
						$result['errors'][] = "Failed to clear raw table '$destination_table_name'.";
						$is_inserts_success = false;
						break;
					}

					while($csv_row_index < $num_csv_lines && $is_inserts_success === true)
					{
						$values_to_insert_sql = "";
						$values_to_insert_bindings = array();

						while($csv_row_index < $num_csv_lines)
						{
							$csv_row = $csv_data[$csv_row_index];

							if(count($csv_row) == $num_csv_header_columns)
							{
								if($csv_row_index == 1 || $csv_row_index % $k_num_rows_per_insert == 0)
								{
									$values_to_insert_sql .= $values_list_sql;
								}
								else
								{
									$values_to_insert_sql .= ",".$values_list_sql;
								}

								foreach($csv_row as $csv_column_index => $value)
								{
									if($csv_column_index === $account_id_index)
									{
										$value = ltrim($value, "-");
									}
									elseif($csv_column_index === $captured_index)
									{
										$value = date("Y-m-d", strtotime($value));
									}
									elseif($csv_column_index === $report_month_index)
									{
										$value = date("Y-m-d", strtotime($special_data_format_function($value)));
									}
									elseif($csv_column_index === $listing_id_index)
									{
										$value = ltrim($value, "-");
									}

									$values_to_insert_bindings[] = $value;
								}

								if(!empty($date))
								{
									$values_to_insert_bindings[] = $date;
								}

							}
							else
							{
								$are_all_columns_present = false;
								$result['errors'][] = "bad num_columns: ".count($csv_row)." at index: $csv_row_index of $base_file_name";
								$is_inserts_success = false;
								break;
							}

							$csv_row_index++;
							if($csv_row_index % $k_num_rows_per_insert == 0)
							{
								break;
							}
						}

						if($are_all_columns_present && !empty($values_to_insert_sql))
						{
							$insert_sql = "
								INSERT INTO $destination_table_name
									$values_order_sql
								VALUES
									$values_to_insert_sql
								ON DUPLICATE KEY UPDATE
									$duplicate_key_update_list_sql
							";

							if(!$initial_insert_database->query($insert_sql, $values_to_insert_bindings))
							{
								$result['errors'][] = "Failed to insert into '$destination_table_name' from $base_file_name";
								$is_inserts_success = false;
								break;
							}
						}
					}

					if(!$are_all_columns_present)
					{
						$result['errors'][] = "One of the csv rows in $base_file_name has a wrong number of columns for '$destination_table_name'";
					}
					elseif(!$is_inserts_success)
					{
						// error logged previously
					}
					else
					{
						$result['is_success'] = true;
					}
				}
			}
			else
			{
				$result['errors'][] = "failed to load data from file: $full_file_name";
			}

		}
		else
		{
			$result['errors'][] = "Failed to find extracted file matching: '$file_name_match'";
		}

		return $result;
	}

	private function remove_unwanted_columns_from_csv_array(&$csv_array, &$map_csv_column_to_database_field)
	{
		$csv_first_row = array_flip($csv_array[0]);
		$map_csv_column_to_database_field = array_filter($map_csv_column_to_database_field);
		$columns_to_remove = array_flip(array_diff_key($csv_first_row, $map_csv_column_to_database_field));

		array_walk($csv_array, function(&$value, $key) use ($columns_to_remove) {
			$value = array_diff_key($value, $columns_to_remove);
			ksort($value);
		});
	}

	private function delete_data_to_be_replaced(
		$destination_table_name,
		$date_field_name,
		$source_table_name_param = null
	)
	{
		$source_table_name = $source_table_name_param;
		if($source_table_name_param === null)
		{
			$source_table_name = $destination_table_name;
		}

		$raw_database_name = $this->raw_db->database;

		$delete_records_which_have_new_data_sql = "
			DELETE $destination_table_name
			FROM
				$destination_table_name
			WHERE
				$destination_table_name.$date_field_name IN (
					SELECT
						raw.$date_field_name
					FROM $raw_database_name.$source_table_name AS raw
					GROUP BY raw.$date_field_name
				)
		";

		return $this->db->query($delete_records_which_have_new_data_sql);
	}

	private function upload_tmpi_csv_to_database(
		$working_directory,
		$directory_listing,
		$file_name_match,
		$destination_table_name,
		$map_csv_column_to_database_field,
		$date_field_name,
		$date = null,
		$special_data_format_function = null,
		$columns_to_increment_for_duplicate_key_update = null
	)
	{
		$result = $this->upload_tmpi_csv_to_raw_table(
			$working_directory,
			$directory_listing,
			$file_name_match,
			$destination_table_name,
			$map_csv_column_to_database_field,
			$date,
			$special_data_format_function,
			$columns_to_increment_for_duplicate_key_update
		);

		if($result['is_success'])
		{
			$result['is_success'] = false;

			$raw_database_name = $this->raw_db->database;

			if(!$this->delete_data_to_be_replaced(
				$destination_table_name,
				$date_field_name
			))
			{
				$result['errors'][] = "Failed to delete old data for dates in '$destination_table_name'.";
			}

			$copy_new_records_sql = "
				INSERT $destination_table_name
				SELECT * FROM $raw_database_name.$destination_table_name
			";

			if(!$this->db->query($copy_new_records_sql))
			{
				$result['errors'][] = "Failed to insert new data for dates in '$destination_table_name'.";
			}
			else
			{
				$num_inserted_lines = $this->db->affected_rows();
				$result['is_success'] = true;
				$result['info'][] = "Successfully loaded ".($num_inserted_lines)." rows into `$destination_table_name` table";
			}
		}

		return $result;
	}

	public function upload_customer_list_to_database($working_directory, $directory_listing, $date)
	{
		$raw_database_name = $this->raw_db->database;

		$date_time = new DateTime($date);
		$comparison_date = $date;

		$file_name_match = "/Charter-CustomerList-/";
		$raw_csv_table_name = "tp_tmpi_accounts_and_products";
		$map_csv_column_to_database_field = array(
			'acc_ID' => 'account_id',
			'Customer' => 'customer',
			'Phone' => 'phone'
		);

		$v2_start_date = new DateTime('2015-09-03');
		$v2_delta = $v2_start_date->diff($date_time);
		$has_v2_data = !$v2_delta->invert;
		$v2_fields = array(
			'leads' => 'leads',
			'directories' => 'directories',
			'display' => 'visits',
			'smartads' => 'smart_ads'
		);
		if($has_v2_data)
		{
			$map_csv_column_to_database_field = array_merge($map_csv_column_to_database_field, $v2_fields);
		}

		$v2_1_start_date = new DateTime('2015-09-25');
		$v2_1_delta = $v2_1_start_date->diff($date_time);
		$has_v2_1_data = !$v2_1_delta->invert;
		$v2_1_fields = array(
			'charter_Name' => 'spectrum_name',
			'charter_ID' => 'spectrum_eclipse_id_raw',
			'charter_Region' => 'spectrum_traffic_system_raw'
		);
		if($has_v2_1_data)
		{
			$map_csv_column_to_database_field = array_merge($map_csv_column_to_database_field, $v2_1_fields);
		}

		$v2_2_start_date = new DateTime('2016-02-25');
		$v2_2_delta = $v2_2_start_date->diff($date_time);
		$has_v2_2_data = !$v2_2_delta->invert;
		$v2_2_fields = array(
			'exported' => 'date'
		);
		if($has_v2_2_data)
		{
			$map_csv_column_to_database_field = array_merge($map_csv_column_to_database_field, $v2_2_fields);
			$date = null;
		}

		$v4_start_date = new DateTime('2016-04-13');
		$v4_delta = $v4_start_date->diff($date_time);
		$has_v4_data = !$v4_delta->invert;
		$v4_fields = array(
			'charter_Region_ID' => 'spectrum_traffic_system',
			'package' => false,
			'price' => false,
			'targetactions' => false
		);
		if($has_v4_data)
		{
			$map_csv_column_to_database_field = array_merge($map_csv_column_to_database_field, $v4_fields);
			unset($map_csv_column_to_database_field['charter_Region']);
		}

		$result = $this->upload_tmpi_csv_to_raw_table(
			$working_directory,
			$directory_listing,
			$file_name_match,
			$raw_csv_table_name,
			$map_csv_column_to_database_field,
			$date
		);

		if($has_v2_1_data)
		{
			$this->map_traffic_system_and_ul_id($raw_database_name, $comparison_date);
		}

		if($result['is_success'])
		{
			$result['is_success'] = false;

			// Insert/Update tp_tmpi_accounts
			$insert_new_tmpi_accounts_sql = "
				INSERT IGNORE INTO tp_tmpi_accounts
					(account_id, customer, phone)
				SELECT
					account_id,
					customer,
					phone
				FROM
					$raw_database_name.$raw_csv_table_name
				GROUP BY
					account_id
			";
			$insert_new_tmpi_accounts_result = $this->db->query($insert_new_tmpi_accounts_sql);
			$num_new_tmpi_accounts = $this->db->affected_rows();

			$update_existing_tmpi_accounts_sql = "
				UPDATE tp_tmpi_accounts AS ta
					JOIN $raw_database_name.$raw_csv_table_name AS raw
						ON (ta.account_id = raw.account_id)
				SET
					ta.customer = raw.customer,
					ta.phone = raw.phone
			";
			$update_existing_tmpi_accounts_result = $this->db->query($update_existing_tmpi_accounts_sql);
			$num_updated_tmpi_accounts = $this->db->affected_rows();

			if($insert_new_tmpi_accounts_result && $update_existing_tmpi_accounts_result && $has_v2_data)
			{
				// Delete tp_tmpi_accounts_products
				if($this->delete_data_to_be_replaced(
					'tp_tmpi_account_products',
					'date',
					$raw_csv_table_name
				))
				{
					// Insert tp_tmpi_accounts_products
					$update_tmpi_account_products_sql = "
						INSERT INTO tp_tmpi_account_products
							(account_id, date, visits, leads, directories, smart_ads)
						SELECT
							account_id,
							date,
							visits,
							leads,
							directories,
							smart_ads
						FROM
							$raw_database_name.$raw_csv_table_name
					";

					if($this->db->query($update_tmpi_account_products_sql))
					{
						$num_tmpi_account_products_rows = $this->db->affected_rows();

						$result['is_success'] = true;
						$result['info'][] = "Successfully loaded ".($num_new_tmpi_accounts)." new rows into `tp_tmpi_accounts` table";
						$result['info'][] = "Successfully updated ".($num_updated_tmpi_accounts)." rows in `tp_tmpi_accounts` table";
						$result['info'][] = "Successfully loaded ".($num_tmpi_account_products_rows)." rows into `tp_tmpi_account_products` table";
					}
					else
					{
						$result['errors'][] = "Failed to insert new data for dates in 'tp_tmpi_accounts_and_products'.";
					}
				}
				else
				{
					$result['errors'][] = "Failed to insert new data for dates in 'tp_tmpi_accounts_and_products'.";
				}
			}
			else
			{
				if($has_v2_data)
				{
					$result['errors'][] = "Failed to insert new data for dates in 'tp_tmpi_accounts'.";
				}
				else
				{
					$result['is_success'] = true;
					$result['info'][] = "Successfully loaded ".($num_new_tmpi_accounts)." new rows into `tp_tmpi_accounts` table";
					$result['info'][] = "Successfully updated ".($num_updated_tmpi_accounts)." rows in `tp_tmpi_accounts` table";
				}
			}
		}

		return $result;
	}

	private function tag_display_clicks_smart_ads(&$result, $table_name, $date)
	{
		if($result['is_success'] === true)
		{
			$sql = "
				UPDATE
					$table_name
				SET
					campaign_type = 1
				WHERE
					record_campaign_name LIKE \"%Smart%Ad%\" AND
					captured BETWEEN ? AND ?
			";

			// Need a past day, because actual data is from 2 or more days ago
			$past_date = date("Y-m-d", strtotime("-5 days", strtotime($date)));

			$bindings = array($past_date, $date);

			$is_tag_success = $this->db->query($sql, $bindings);

			if($is_tag_success === true)
			{
				$result['info'][] = $this->db->affected_rows()." rows tagged as Smart Ad in $table_name for $past_date to $date";
			}
			else
			{
				$result['is_success'] = false;
				$result['errors'][] = "Failed to tag smart ads for $table_name";
			}
		}
	}

	public function upload_display_clicks_to_database($working_directory, $directory_listing, $date)
	{

		$file_name_match = "/Charter-DisplayClicks-/";
		$destination_table_name = "tp_tmpi_display_clicks";
		$map_csv_column_to_database_field = array(
			'acc_ID' => 'account_id',
			'rcd_Master_ID' => 'record_master_id',
			'rcd_Campaign_Name' => 'record_campaign_name',
			'Captured' => 'captured',
			'Clicks' => 'clicks',
			'Impressions' => 'impressions'
		);

		$has_smart_ad_column = strtotime($date) >= strtotime("2015-04-08");

		if($has_smart_ad_column)
		{
			$map_csv_column_to_database_field['isDynamic'] = 'campaign_type';
		}

		$result = $this->upload_tmpi_csv_to_database(
			$working_directory,
			$directory_listing,
			$file_name_match,
			$destination_table_name,
			$map_csv_column_to_database_field,
			'captured'
		);

		if(!$has_smart_ad_column && $result['is_success'] === true)
		{
			$this->tag_display_clicks_smart_ads($result, $destination_table_name, $date);
		}

		return $result;
	}

	public function upload_display_geo_clicks_to_database($working_directory, $directory_listing, $date)
	{

		$file_name_match = "/Charter-DisplayGeoClicks-/";
		$destination_table_name = "tp_tmpi_display_geo_clicks";
		$map_csv_column_to_database_field = array(
				'acc_ID' => 'account_id',
				'rcd_Master_ID' => 'record_master_id',
				'rcd_Campaign_Name' => 'record_campaign_name',
				'disgeo_city' => 'city',
				'disgeo_region' => 'region',
				'Captured' => 'captured',
				'Clicks' => 'clicks',
				'Impressions' => 'impressions'
		);

		$has_smart_ad_column = strtotime($date) >= strtotime("2015-04-08");

		if($has_smart_ad_column)
		{
			$map_csv_column_to_database_field['isDynamic'] = 'campaign_type';
		}

		$columns_to_increment_for_duplicate_key_update = [
			'clicks' => true,
			'impressions' => true
		];

		$result = $this->upload_tmpi_csv_to_database(
				$working_directory,
				$directory_listing,
				$file_name_match,
				$destination_table_name,
				$map_csv_column_to_database_field,
				'captured',
				null,
				null,
				$columns_to_increment_for_duplicate_key_update
		);

		if(!$has_smart_ad_column && $result['is_success'] === true)
		{
			$this->tag_display_clicks_smart_ads($result, $destination_table_name, $date);
		}

		return $result;
	}

	public function upload_email_leads_to_database($working_directory, $directory_listing, $unused_date)
	{
		$file_name_match = "/Charter-EmailLeads-/";
		$destination_table_name = "tp_tmpi_email_leads";
		$map_csv_column_to_database_field = array(
			'acc_ID' => 'account_id',
			'VIN' => 'vin',
			'LeadID' => 'lead_id',
			'Captured' => 'captured',
			'FromMail' => 'from_mail',
			'FirstName' => 'first_name',
			'LastName' => 'last_name',
			'Phone' => 'phone',
			'Zip' => 'zip_code',
			'Comment' => 'comment'
		);

		$result = $this->upload_tmpi_csv_to_database(
			$working_directory,
			$directory_listing,
			$file_name_match,
			$destination_table_name,
			$map_csv_column_to_database_field,
			'captured'
		);

		return $result;
	}

	public function upload_hi_lo_performance_to_database($working_directory, $directory_listing, $date)
	{
		$date_time = new DateTime($date);

		$file_name_match = "/Charter-HiLoPerformance-/";
		$destination_table_name = "tp_tmpi_hi_lo_performance";
		$map_csv_column_to_database_field = array(
			'rank' => 'rank',
			'acc_ID' => 'account_id',
			'lst_id' => 'listing_id',
			'lst_Text' => 'listing_text',
			'lst_Search' => 'listing_search',
			'lst_Details' => 'listing_details',
			'lst_Price' => 'listing_price',
			'lst_PriceRange' => 'listing_price_range'
		);

		$v2_2_start_date = new DateTime('2016-02-25');
		$v2_2_delta = $v2_2_start_date->diff($date_time);
		$has_v2_2_data = !$v2_2_delta->invert;
		$v2_2_fields = array(
			'exported' => 'date'
		);
		if($has_v2_2_data)
		{
			$map_csv_column_to_database_field = array_merge($map_csv_column_to_database_field, $v2_2_fields);
			$date = null;
		}

		$result = $this->upload_tmpi_csv_to_database(
			$working_directory,
			$directory_listing,
			$file_name_match,
			$destination_table_name,
			$map_csv_column_to_database_field,
			'date',
			$date
		);

		return $result;
	}

	public function upload_local_performance_to_database($working_directory, $directory_listing, $unused_date)
	{
		$file_name_match = "/Charter-LocalPerformance-/";
		$destination_table_name = "tp_tmpi_local_performance";
		$map_csv_column_to_database_field = array(
			'acc_ID' => 'account_id',
			'Captured' => 'captured',
			'ShortName' => 'short_name',
			'Total' => 'total'
		);

		$result = $this->upload_tmpi_csv_to_database(
			$working_directory,
			$directory_listing,
			$file_name_match,
			$destination_table_name,
			$map_csv_column_to_database_field,
			'captured'
		);

		return $result;
	}

	public function upload_phone_leads_to_database($working_directory, $directory_listing, $unused_date)
	{
		$file_name_match = "/Charter-PhoneLeads-/";
		$destination_table_name = "tp_tmpi_phone_leads";
		$map_csv_column_to_database_field = array(
			'acc_ID' => 'account_id',
			'LeadID' => 'lead_id',
			'Captured' => 'captured',
			'FirstName' => 'first_name',
			'LastName' => 'last_name',
			'Phone' => 'phone',
			'Zip' => 'zip_code'
		);

		$result = $this->upload_tmpi_csv_to_database(
			$working_directory,
			$directory_listing,
			$file_name_match,
			$destination_table_name,
			$map_csv_column_to_database_field,
			'captured'
		);

		return $result;
	}

	public function upload_search_detail_performance_to_database($working_directory, $directory_listing, $unused_date)
	{
		$file_name_match = "/Charter-SearchDetailPerformance-.*\.txt/";
		$destination_table_name = "tp_tmpi_search_detail_performance";
		$map_csv_column_to_database_field = array(
			'acc_ID' => 'account_id',
			'rptMonth' => 'report_month',
			'ShortName' => 'short_name',
			'MonthTotal' => 'month_total'
		);

		$matches = preg_grep($file_name_match, $directory_listing);

		$is_v2_search_detail_data = strpos(reset($matches), '@') !== false; // New TMPi data delivered every 4 hours does not have the yearly total - Scott 2015-07-31

		$has_year_total = !$is_v2_search_detail_data;
		if($has_year_total)
		{
			$map_csv_column_to_database_field['YearTotal'] = 'year_total';
		}

		$special_data_function = function($special_data) {
			return $special_data;
		};
		if(!$is_v2_search_detail_data)
		{
			$special_data_function = function($special_data) {
				return $special_data.'01';
			};
		}

		$result = $this->upload_tmpi_csv_to_database(
			$working_directory,
			$directory_listing,
			$file_name_match,
			$destination_table_name,
			$map_csv_column_to_database_field,
			'report_month',
			null,
			$special_data_function
		);

		return $result;
	}

	public function upload_vehicle_summaries_to_database($working_directory, $directory_listing, $date)
	{
		$date_time = new DateTime($date);

		$file_name_match = "/Charter-VehicleSummary-/";
		$destination_table_name = "tp_tmpi_vehicle_summaries";
		$map_csv_column_to_database_field = array(
			'acc_ID' => 'account_id',
			'pac_ID' => 'performance_report_id',
			'pac_Name' => 'performance_report_name',
			'pac_ShortName' => 'performance_report_short_name',
			'pac_Sort' => 'performance_report_sort',
			'total' => 'total',
			'count' => 'count'
		);

		$v2_2_start_date = new DateTime('2016-02-25');
		$v2_2_delta = $v2_2_start_date->diff($date_time);
		$has_v2_2_data = !$v2_2_delta->invert;
		$v2_2_fields = array(
			'exported' => 'date'
		);
		if($has_v2_2_data)
		{
			$map_csv_column_to_database_field = array_merge($map_csv_column_to_database_field, $v2_2_fields);
			$date = null;
		}

		$result = $this->upload_tmpi_csv_to_database(
			$working_directory,
			$directory_listing,
			$file_name_match,
			$destination_table_name,
			$map_csv_column_to_database_field,
			'date',
			$date
		);

		return $result;
	}

	public function example_usage()
	{
		$account_sql = "
			acc.customer AS customer,
		";

		$this->get_tmpi_account_data_presence(
			121,
			$account_sql,
			'2014-11-11',
			'2015-03-03'
		);
	}

	public function do_campaigns_have_tmpi_data($advertiser, $campaigns)
	{
		$num_campaigns = count($campaigns);
		$has_tmpi_data = false;

		if($num_campaigns > 0)
		{
			if($num_campaigns === 1 && $campaigns[0] == 0)
			{
				$sql = "
					SELECT
						*
					FROM
						Campaigns AS cmp
						JOIN tp_campaigns_tmpi_accounts AS cmp_acc
							ON (cmp.id = cmp_acc.campaign_id)
					WHERE
						cmp.business_id = ?
					LIMIT 1
				";

				$response = $this->db->query($sql, $advertiser);
				if($response->num_rows() > 0)
				{
					$has_tmpi_data = true;
				}
			}
			else
			{
				$campaigns_sql = "?".str_repeat(",?", $num_campaigns - 1);

				$sql = "
					SELECT
						*
					FROM
						tp_campaigns_tmpi_accounts AS cmp_acc
					WHERE
						cmp_acc.campaign_id IN ($campaigns_sql)
					LIMIT 1
				";

				$response = $this->db->query($sql, $campaigns);
				if($response->num_rows() > 0)
				{
					$has_tmpi_data = true;
				}
			}
		}

		return $has_tmpi_data;
	}

	/*
	private function get_csv_column_index_to_database_values_index_map(
		$map_csv_column_to_database_field,
		$csv_first_row
	)
	{
		$map_csv_column_index_to_database_values_index = array();

		$database_index = 0;
		foreach($map_csv_column_to_database_field as $csv_column => $database_field)
		{
			$csv_index = array_search($csv_column, $csv_first_row);
			if($index !== false)
			{
				$map_csv_column_index_to_database_values_index[$csv_index] = $database_index;
			}
			else
			{
				throw new Exception("Failed to find csv_column '$csv_column' in csv first row.");
			}

			$database_index++;
		}

		return $map_csv_column_index_to_database_values_index;
	}
	*/

	private function map_traffic_system_and_ul_id($raw_database_name, $date)
	{
		$date_time = new DateTime($date);

		$v4_start_date = new DateTime('2016-04-13');
		$v4_delta = $v4_start_date->diff($date_time);
		$has_v4_data = !$v4_delta->invert;

		if(!$has_v4_data)
		{
			$traffic_system_case_sql = get_sql_case_mapping_spectrum_traffic_system_name_to_id('spectrum_traffic_system_raw');
			$update_traffic_system_query =
			"UPDATE
				{$raw_database_name}.tp_tmpi_accounts_and_products
			SET
				spectrum_traffic_system = $traffic_system_case_sql;
			";
			$update_traffic_system_result = $this->db->query($update_traffic_system_query);
			if($update_traffic_system_result == false)
			{
				return false;
			}
		}

		$update_eclipse_id_query =
		"UPDATE
			{$raw_database_name}.tp_tmpi_accounts_and_products
		SET
			spectrum_eclipse_id = CONCAT(spectrum_traffic_system, spectrum_eclipse_id_raw)
		";
		$update_eclipse_id_result = $this->db->query($update_eclipse_id_query);
		if($update_eclipse_id_result == false)
		{
			return false;
		}
		return true;
	}

	public function link_tmpi_accounts_to_advertisers($date)
	{

		$result['is_success'] = true;
		$result['info'] = "";

		$date_time = new DateTime($date);
		$v2_1_start_date = new DateTime('2015-09-29');
		$v2_1_delta = $v2_1_start_date->diff($date_time);
		$has_v2_1_data = !$v2_1_delta->invert;

		if($has_v2_1_data)
		{
			$raw_database_name = $this->raw_db->database;
			$delete_old_links_query =
			"DELETE FROM
				tp_advertisers_join_third_party_account
			WHERE
				third_party_source = 1
				AND frq_third_party_account_id IN
			(
				SELECT
					tta.id AS frq_id
				FROM
					$raw_database_name.tp_tmpi_accounts_and_products AS rttaap
					JOIN tp_spectrum_accounts AS tsa
						ON (rttaap.spectrum_eclipse_id = tsa.eclipse_id)
					JOIN tp_tmpi_accounts AS tta
						ON (rttaap.account_id = tta.account_id)
				GROUP BY
					tta.account_id
			)
			";

			$delete_old_links_result = $this->db->query($delete_old_links_query);
			if($delete_old_links_result == false)
			{
				$result['is_success'] = false;
				return $result;
			}
			$result['info'][] = "Successfully unlinked {$this->db->affected_rows()} advertiser-account pairings";

			$add_new_links_query =
			"INSERT INTO
				tp_advertisers_join_third_party_account
			(frq_advertiser_id, frq_third_party_account_id, third_party_source)
			(
				SELECT
					tsa.advertiser_id AS advertiser_id,
					tta.id AS frq_third_party_account_id,
					1
				FROM
					$raw_database_name.tp_tmpi_accounts_and_products AS rttaap
					JOIN tp_spectrum_accounts AS tsa
						ON (rttaap.spectrum_eclipse_id = tsa.eclipse_id)
					JOIN tp_tmpi_accounts AS tta
						ON (rttaap.account_id = tta.account_id)
				GROUP BY
					tta.account_id
			)
			";
			$add_new_links_result = $this->db->query($add_new_links_query);
			if($add_new_links_result == false)
			{
				$result['is_success'] = false;
				return $result;
			}
			$result['info'][] = "Successfully linked {$this->db->affected_rows()} advertiser-account pairings";
			return $result;
		}

	}
}
