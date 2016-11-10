<?php
	
	function verify_address($address)
	{
		return ($address != '');
	}

	function string_to_num(&$s)
	{
		$s = floatval(str_replace(',', '', $s));
	}

	function get_geocoded_address_center_from_google($address)
	{
		$return_array = array();
		$url = 'https://maps.googleapis.com/maps/api/geocode/json?address=';
		$uri_address = urlencode($address);
		$api_key = '&key=' . 'AIzaSyAVqHsbdM1Thk9WK5JlfbQIfor-2CKlq2g';
		$call = file_get_contents($url . $uri_address . $api_key);
		$call = json_decode($call, true);
		if ($call['status'] == 'OK')
		{
			foreach ($call['results'] as $key => $val)
			{
				foreach ($val['address_components'] as $comp)
				{
					if(in_array('United States', $comp) || in_array('US', $comp))
					{
						$return_array = array('latitude' => $val['geometry']['location']['lat'], 'longitude' => $val['geometry']['location']['lng']);
						if (in_array('postal_code', $comp['types']))
						{
							$return_array['containing_zip'] = $comp['short_name'];
						}
						return $return_array;
					}
				}
				return array('errors' => 'Location not found in the United States. Try being more specific.');
			}
		}
		if ($call['status'] == 'ZERO_RESULTS')
		{
			return array('errors' => 'Invalid address selected.');
		}
	}

	function get_array_of_values_with_same_key_from_assoc_array($array_of_arrays, $key_to_look_for)
	{
		$new_arr = array();
		array_walk_recursive(
			$array_of_arrays, 
			function ($value, $key) use (&$new_arr, $key_to_look_for) 
			{
				if ($key == $key_to_look_for) $new_arr[] = $value;
			}
		);
		
		return $new_arr;
	}

	function trim_value(&$value)
	{
		$value = trim($value);
	}

	function verify_and_format_input_line(&$lap_line_array, &$return_array, $line_number)
	{
		$address = '';
		$radius = '';
		$min_population = '';

		if(count($lap_line_array) != 3)
		{
			$return_array['is_success'] = FALSE;
			$return_array['errors'][] = 'Input line needs to be formated correctly: ' . (implode(' - ', $lap_line_array));
			return;
		}

		array_walk($lap_line_array, 'trim_value');
		$address = $lap_line_array[0];
		$radius = $lap_line_array[1];
		$min_population = $lap_line_array[2];

		if(!verify_address($address))
		{
			$return_array['is_success'] = FALSE;
			$return_array['errors'][] = 'A valid address is needed -> line: ' . (implode(' - ', $lap_line_array));
		}

		if(!($radius != '' XOR $min_population != ''))
		{
			$return_array['is_success'] = FALSE;
			$return_array['errors'] = 'Either a radius or a min population is needed, but not both -> line: ' . (implode(' - ', $lap_line_array));
		}

		if(strlen($min_population) > 0)
		{
			if (!is_numeric(str_replace(',', '', $min_population)))
			{
				$return_array['is_success'] = FALSE;
				$return_array['errors'][] = 'The value for Min Population is not formatted correctly -> line: ' . (implode(' - ', $lap_line_array));
			}
			else
			{
				string_to_num($min_population);
			}
		}

		if(strlen($radius) > 0)
		{
			if (!is_numeric(str_replace(',', '', $radius)))
			{
				$return_array['is_success'] = FALSE;
				$return_array['errors'][] = 'The value for Radius is not formatted correctly -> line: ' . (implode(' - ', $lap_line_array));
			}
			else
			{
				string_to_num($radius);
			}
		}

		return array('address' => $address, 'radius' => $radius, 'min_population' => $min_population);
	}

?>