<?php

class Syscode_upload_model extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	private $errors = [];
	private $messages = [];

	private $names_inserted = [];

	public function update_syscodes($data_array)
	{
		$return_array = ['is_success' => true];

		$query_array = $this->populate_query_array($data_array);
		$this->run_query_transactions($query_array);

		if($this->errors)
		{
			$return_array['is_success'] = false;
			$return_array['errors'] = $this->errors;
		}
		$return_array['messages'] = $this->messages;

		return $return_array;
	}

	private function populate_query_array($data_array)
	{
		$query_array = [];
		$query_array[] = $this->get_insert_query($data_array);
		$query_array[] = $this->get_spectrum_zones_syscode_insert_query();
		$query_array[] = $this->get_spectrum_zones_assignment_query();

		return $query_array;
	}

	private function get_insert_query($array_from_file)
	{
		$processed_data = $this->process_data($array_from_file);

		$sql =
		"	INSERT INTO
				geo_regions_collection (`name`, `region_type`, `json_regions`)
			VALUES
		";
		$insert_string_array = [];
		$bindings = [];
		foreach($processed_data as $zone_name => $zone_zips)
		{
			$insert_string = implode(',', array_fill(0, 3, '?'));
			$insert_string_array[] = "({$insert_string})";
			$this->names_inserted[] = $zone_name;
			$bindings[] = $zone_name;
			$bindings[] = 'ZIP';
			$bindings[] = json_encode(array_values(array_unique(array_filter($zone_zips))));
		}
		$sql .= implode(',', $insert_string_array);
		$sql .=
		"	ON DUPLICATE KEY UPDATE
				json_regions = VALUES(json_regions)
		";

		return array('text' => $sql, 'bindings' => $bindings, 'desc' => 'Update existing or insert new zones into geo_regions_collection');
	}

	private function get_spectrum_zones_assignment_query()
	{
		$name_insert_bindings_string = implode(',', array_fill(0, count($this->names_inserted), '?'));
		$sql =
		"	INSERT IGNORE INTO `geo_regions_collections_partner`
			(partner_id, geo_regions_collection_id)
			SELECT
				wpd.id,
				grc.`id`
			FROM
			(	SELECT
					id
				FROM
					`wl_partner_details`
				WHERE cname = 'spectrumreach'
			) AS wpd,
			(	SELECT
					*
				FROM
					`geo_regions_collection`
				WHERE
					`name` IN ({$name_insert_bindings_string})
			) AS grc;
		";
		$bindings = $this->names_inserted;

		return array('text' => $sql, 'bindings' => $bindings, 'desc' => 'Update zone relationships with new zone names that have been inserted');
	}

	private function get_spectrum_zones_syscode_insert_query()
	{
		$name_insert_bindings_string = implode(',', array_fill(0, count($this->names_inserted), '?'));
		$sql =
		"	INSERT IGNORE INTO
				`tp_spectrum_syscode_region_join` (`syscode_int`, `geo_regions_collection_id`)
			SELECT
				SUBSTRING(`name`, POSITION('[' IN `name`) + 1, 4) AS syscode,
				id AS id
			FROM
				geo_regions_collection
			WHERE
				`name` IN ({$name_insert_bindings_string});
		";
		$bindings = $this->names_inserted;

		return array('text' => $sql, 'bindings' => $bindings, 'desc' => 'Add rows to syscode join table');
	}

	private function process_data($data_array)
	{
		$processed_data = [];
		foreach($data_array as $row)
		{
			$zone_name = 'Zone: ' . $row['Region'] . ' ' . str_replace('Spectrum/', '', $row['Sysname']) . ' [' . trim(str_pad($row['Syscode'], 4, '0', STR_PAD_LEFT)) . ']';
			if(!array_key_exists($zone_name, $processed_data))
			{
				$processed_data[$zone_name] = array();
			}
			$processed_data[$zone_name][] = $row['Zip Code'];
		}
		return $processed_data;
	}

	private function run_query_transactions($query_array)
	{
		$this->db->trans_begin();
		foreach($query_array as $query)
		{
			if(isset($query['bindings']))
			{
				$result = $this->db->query($query['text'], $query['bindings']);
			}
			else
			{
				$result = $this->db->query($query['text']);
			}

			if($this->db->trans_status() === true)
			{
				if(isset($query['desc']))
				{
					$num_rows_affected = $this->db->affected_rows();
					$message = "Query: {$query['desc']} -> Successful\n";
					if($num_rows_affected > 0)
					{
						$message .= strval($num_rows_affected);
						$message .= ($num_rows_affected == 1) ? ' row affected' : ' rows affected';
					}

					$this->messages[] = $message;
				}
			}
			else
			{
				$this->db->trans_rollback();
				if(isset($query['desc']))
				{
					$this->errors[] = "Query: {$query['desc']} -> Failed\nAny previous queries have been rolled back";
				}
				return;
			}
		}
		$this->db->trans_commit();
	}
}