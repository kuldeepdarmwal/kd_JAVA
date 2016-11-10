<?php

class Maps extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->load->helper('multi_lap');
		$this->load->helper('vl_ajax_helper');

		$this->load->library('map');
		$this->load->library('tank_auth');

		$this->load->model('mpq_v2_model');
	}

	public function ajax_get_data_for_maps_and_demos_with_unique_id()
	{
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer', 'proposal_builder/multi_geo_grabber');
			redirect("login");
		}
		$username = $this->tank_auth->get_username();
		$role = strtolower($this->tank_auth->get_role($username));

		if($role != 'admin' && $role != 'ops') redirect("director");

		if (isset($_POST['unique_id']))
		{
			$allowed_roles = array('admin', 'ops');
			$post_variables = array('unique_id');

			$response = vl_verify_ajax_call($allowed_roles, $post_variables);
			if (!$response['is_success'])
			{
				echo json_encode($response);
				exit(0);
			}
			else
			{
				$return_array = array('is_success' => true);
				$return_array['regions'] = $this->map->get_region_array_from_db_with_unique_id($response['post']['unique_id']);
				$return_array['demos'] = $this->map->get_demo_array_for_page_with_unique_id($response['post']['unique_id']);
				$return_array['maps'] = $this->map->get_map_array_for_page_with_unique_id($response['post']['unique_id']);

				echo json_encode($return_array);
			}
		}
		else show_404();
	}

	public function ajax_add_bulk_locations_to_session()
	{
		$allowed_roles = array('admin', 'ops', 'sales', 'client', 'agency');
		$post_variables = array(
			'locations',
			'starting_location_id',
			'submission_type',
			'line_number'
		);

		$response = vl_verify_ajax_call($allowed_roles, $post_variables);
		if (!$response['is_success'])
		{
			echo json_encode($response);
			exit(0);
		}
		else
		{
			$return_array = array(
				'is_success' => true,
				'errors' => array(),
				'successful_locations' => array(),
				'line_number' => $response['post']['line_number']
			);
			$next_location_id_to_use = $response['post']['starting_location_id'];

			if($location_array = json_decode($response['post']['locations'], true))
			{
				foreach($location_array as $line_number => $location_info)
				{
					$individual_location_array = array();

					$formatted_location = ($response['post']['submission_type'] == 'both') ?
						$location_info :
						$this->get_formatted_line_based_on_submission_type($location_info, $response['post']['submission_type']);
					$map_parameters = verify_and_format_input_line($formatted_location, $return_array, $line_number);
					$lat_long = get_geocoded_address_center_from_google($map_parameters['address']);
					if (isset($lat_long['errors']))
					{
						$return_array['is_success'] = true;
						$return_array['errors'][] = $lat_long['errors'];
					}
					else
					{
						$containing_zip = null;
						if (isset($lat_long['containing_zip']))
						{
							$containing_zip = $lat_long['containing_zip'];
							unset($lat_long['containing_zip']);
						}

						$return_array['center_coordinates'][] = $lat_long;
						$affected = $this->get_affected_zips($lat_long, $map_parameters['radius'], $map_parameters['min_population']);
						$radius = (isset($affected[0]['distance']) ? $affected[count($affected) - 1]['distance'] : $map_parameters['radius']);
						if (!is_int($radius))
						{
							$individual_location_array['geo_dropdown_options'] = array();

							$radius = ceil($radius);
							$search_criteria = "ZIP_{$radius}_{$lat_long['latitude']}_{$lat_long['longitude']}";
							$session_id = $this->session->userdata('session_id');
							$location_name = "{$map_parameters['address']} - {$radius} mile radius";
							$affected_zips = $this->mpq_v2_model->do_geo_search_and_save_result($search_criteria, $session_id, $next_location_id_to_use++, $location_name, $map_parameters['address']);
							$individual_location_array['geo_dropdown_options']['radius'] = $radius;
							$individual_location_array['geo_dropdown_options']['address'] = $map_parameters['address'];
						}
						if (count($affected_zips) == 0)
						{
							if ($containing_zip == null)
							{
								$return_array['errors'][] = "Address: {$map_parameters['address']}. No geos available for that region/radius.";
							}
							$affected_zips = array($containing_zip);
						}

						if(!empty($affected_zips))
						{
							$individual_location_array['location_name'] = $location_name;
							$individual_location_array['has_been_renamed'] = false;
							$individual_location_array['is_radius_search'] = true;
							$individual_location_array['geo_dropdown_selection'] = 'radius_search';
							$individual_location_array['regions'] = implode(', ', $affected_zips);

							$demographics = $this->map->get_demographics_from_region_array($affected_zips);
							$individual_location_array['location_population'] = $demographics['region_population'];
							$return_array['successful_locations'][] = $individual_location_array;
						}
					}
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'][] = 'Data sent is not json serializable. Check sent input.';
			}

			echo json_encode($return_array);
		}
	}

	public function get_polygons_for_visible_area()
	{
		$allowed_roles = array('admin', 'ops', 'sales', 'client', 'agency', 'business');
		$post_variables = array(
			'area_ids',
			'map_area_km'
		);

		$response = vl_verify_ajax_call($allowed_roles, $post_variables);
		if (!$response['is_success'])
		{
			echo json_encode($response);
			exit(0);
		}
		else
		{
			$area_ids = (is_string($response['post']['area_ids'])) ? explode(',', $response['post']['area_ids']) : $response['post']['area_ids'];
			$map_area_km = $response['post']['map_area_km'];

			$ratio = $this->map->get_ratio_between_selected_regions_and_total_map_area($area_ids, $map_area_km);

			$use_polygons = false;
			if($ratio > 0.01)
			{
				$use_polygons = true;
			}
			echo json_encode(array('is_success' => true, 'use_polygons' => $use_polygons));
		}
	}

	private function get_formatted_line_based_on_submission_type($location_info, $submission_type)
	{
		if(count($location_info) == 2)
		{
			switch ($submission_type)
			{
				case 'radius':
					$population_placeholder = array('');
					array_splice($location_info, 2, 0, $population_placeholder);
					return $location_info;
				case 'population':
					$radius_placeholder = array('');
					array_splice($location_info, 1, 0, $radius_placeholder);
					return $location_info;
				default:
					echo json_encode(array('is_success' => false, 'errors' => array("Improper upload type variable detected: {$submission_type}")));
					exit(0);
			}
		}
		else
		{
			echo json_encode(array('is_success' => true, 'errors' => array('Check that your line is formatted correctly.')));
			exit(0);
		}
	}

	private function get_affected_zips($lat_long, $radius, $min_population)
	{
		if (isset($lat_long['latitude']) && isset($lat_long['longitude']))
		{
			return (!empty($radius) ? $this->map->get_zips_from_radius_and_center($lat_long, $radius) : $this->map->get_zips_from_min_population_and_center($lat_long, $min_population));
		}
		return NULL;
	}

	public function get_geo_json_uri_for_maps_through_ajax()
	{
		$allowed_roles = array('admin', 'ops');
		$post_variables = array('blobs', 'points', 'center', 'affected_uris', 'radius');

		$response = vl_verify_ajax_call($allowed_roles, $post_variables);
		if (!$response['is_success'])
		{
			echo json_encode($response);
			exit(0);
		}
		else
		{
			$uri = $this->map->get_geo_json_map_uri_for_google_maps(
				array(
					'blobs' => ($response['post']['blobs']) ? array('zcta' => $response['post']['blobs']) : NULL,
					'points' => ($response['post']['points']) ? $response['post']['points'] : NULL,
					'center' => ($response['post']['center']) ? $response['post']['center'] : NULL,
					'uris' => ($response['post']['affected_uris']) ? $response['post']['affected_uris'] : NULL
				),
				$response['post']['radius']
			);
			echo json_encode(array('is_success' => true, 'geojson_uri' => $uri));
		}
	}

	public function get_region_geojson_to_draw()
	{
		$allowed_roles = array('admin', 'ops', 'sales', 'creative', 'business', 'public', 'client', 'agency');
		$post_variables = array('region_id', 'region_type');

		$response = vl_verify_ajax_call($allowed_roles, $post_variables);
		if (!$response['is_success'])
		{
			echo json_encode($response);
			exit(0);
		}
		else
		{
			if($response['post']['region_type'] == 'zcta')
			{
				$region_ids = $response['post']['region_id'];
				if(is_string($region_ids))
				{
					$region_ids = explode(',', $region_ids);
				}
				$region_ids = array_map(function($value){
					return (is_numeric($value)) ? intval($value) : $value;
				}, $region_ids);

				$complexity_level = 'max';
				if($this->input->post('complexity_level') !== false)
				{
					$complexity_level = $this->input->post('complexity_level');
				}

				$memory_limit_in_bytes = 0;
				if($this->input->post('memory_limit') !== false)
				{
					$memory_limit = $this->input->post('memory_limit');
					$memory_limit_in_bytes = $this->map->get_memory_limit_in_bytes_from_string($memory_limit);
				}

				if($this->input->post('use_points') == 'true')
				{
					$geojson_blob = $this->map->get_geojson_points_from_region_array(array($response['post']['region_type'] => $region_ids));
				}
				else
				{
					$geojson_blob = $this->map->get_geojson_blobs_from_region_array(array($response['post']['region_type'] => $region_ids), $complexity_level);
				}

				$num_regions_with_geos = count($geojson_blob[$response['post']['region_type']]);
				$num_regions_without_geos = count($region_ids) - $num_regions_with_geos;
				$feature_blob = $this->map->get_geojson_for_mpq($geojson_blob);
				echo json_encode(array(
					'is_success' => true,
					'region_blob' => ($memory_limit_in_bytes == 0 || strlen($feature_blob) <= $memory_limit_in_bytes) ? $feature_blob : false,
					'regions_without_geos' => $num_regions_without_geos,
					'complexity_level' => $complexity_level
				));
			}
			else
			{
				echo json_encode(array('is_success' => false, 'errors' => array("Unknown region type: {$response['post']['region_type']}")));
			}
		}
	}

	public function get_all_region_centers_in_given_area()
	{
		$allowed_roles = array('admin', 'ops', 'sales', 'creative', 'business', 'public', 'client', 'agency');
		$post_variables = array('region_type', 'ne_lat', 'ne_lng', 'sw_lat', 'sw_lng');

		$response = vl_verify_ajax_call($allowed_roles, $post_variables);
		if (!$response['is_success'])
		{
			echo json_encode($response);
			exit(0);
		}
		else
		{
			$north_east_corner = array('latitude' => $response['post']['ne_lat'], 'longitude' => $response['post']['ne_lng']);
			$south_west_corner = array('latitude' => $response['post']['sw_lat'], 'longitude' => $response['post']['sw_lng']);

			$centers = $this->map->get_region_centers_contained_in_bounded_area($north_east_corner, $south_west_corner, $response['post']['region_type']);
			echo json_encode(array('is_success' => true, 'centers' => $centers));
		}
	}

	public function get_marker_cluster_info()
	{
		$allowed_roles = array('admin', 'ops', 'sales', 'creative', 'business', 'public', 'client', 'agency');
		$post_variables = array('region_type', 'region_ids');

		$response = vl_verify_ajax_call($allowed_roles, $post_variables);
		if (!$response['is_success'])
		{
			echo json_encode($response);
			exit(0);
		}
		else
		{
			$lists = $this->map->get_county_and_state_list_for_regions($response['post']['region_type'], explode(',', $response['post']['region_ids']));
			echo json_encode(array('is_success' => (bool)$lists, 'lists' => $lists));
		}
	}

	public function get_marker_for_map($hex_color, $radius = 6, $opacity = 1, $border_color = '%23646464', $border_opacity = 1)
	{
		$hex_color = rawurldecode($hex_color);
		$hex_border_color = rawurldecode($border_color);

		// Convert to alpha from percent opacity
		$alpha = 127 * (1 - $opacity);
		$border_alpha = 127 * (1 - $border_opacity);

		$border_width = 1;
		$position_center = $radius + $border_width;
		$diameter = 2 * $radius;
		$filled_diameter = 2 * $position_center;

		$img = imagecreatetruecolor($filled_diameter, $filled_diameter);
		imagesavealpha($img, true);

		$transparent_color = imagecolorallocatealpha($img, 0, 0, 0, 127);
		imagefill($img, 0, 0, $transparent_color);

		// allocate some colors
		$image_color = $this->hex2rgb($img, $hex_color, $alpha);
		$image_border_color = $this->hex2rgb($img, $hex_border_color, $border_alpha);

		imagefilledellipse($img, $position_center, $position_center, $diameter, $diameter, $image_color);

		imagearc($img, $position_center, $position_center, $diameter, $diameter, 0, 360, $image_border_color);

		// // output image in the browser
		header('Content-Type: image/png');
		imagepng($img);

		// free memory
		imagedestroy($img);
	}

	public function get_svg_marker_for_map($hex_color, $radius = 6, $opacity = 1, $border_color = '%23646464', $border_opacity = 1, $border_width = 1)
	{
		$decoded_color = rawurldecode($hex_color);
		$decoded_border_color = rawurldecode($border_color);
		$position_center = $radius + $border_width;
		$diameter = 2 * ($position_center);

		header('Content-Type: image/svg+xml');

		echo
		"<?xml version=\"1.0\"?>
		<svg xmlns=\"http://www.w3.org/2000/svg\" version=\"1.1\" viewBox=\"0 0 {$diameter} {$diameter}\" height=\"{$diameter}\" width=\"{$diameter}\">
			<circle
				cx=\"{$position_center}\"
				cy=\"{$position_center}\"
				r=\"{$radius}\"
				stroke=\"{$decoded_border_color}\"
				stroke-opacity=\"{$border_opacity}\"
				stroke-width=\"{$border_width}\"
				fill=\"{$decoded_color}\"
				fill-opacity=\"{$opacity}\"
			/>
		</svg>";
	}

	function hex2rgb($im, $hex, $alpha)
	{
		return imagecolorallocatealpha($im,
			hexdec(substr($hex, 1, 2)),
			hexdec(substr($hex, 3, 2)),
			hexdec(substr($hex, 5, 2)),
			intval($alpha)
		);
	}

}
?>
