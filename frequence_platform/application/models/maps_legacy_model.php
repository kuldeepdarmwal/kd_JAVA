<?php

class Maps_legacy_model extends CI_Model 
{

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	public function get_comma_separated_county_and_state_list_for_regions($region_type, $region_ids)
	{
		$in_string = implode(',', array_fill(0, count($region_ids), '?'));

		$set_query = "SET group_concat_max_len = 4096;";
		$select_query =
		"	SELECT 
				GROUP_CONCAT(DISTINCT gcm.county SEPARATOR ', ') AS county_list, 
				COUNT(DISTINCT gcm.county) AS num_counties,
				GROUP_CONCAT(DISTINCT gsm.NAME10 SEPARATOR ', ') AS state_list,
				COUNT(DISTINCT gsm.`NAME10`) AS num_states
			FROM
				geo_zcta_to_county AS gztoc
			LEFT OUTER JOIN 
				(
					SELECT DISTINCT 
						num_id,
						IF(
							`NAMELSAD10` LIKE '% Census Area', # Check if county name ends with ' Census Area'
							REPLACE(`NAMELSAD10`, ' Census Area', ''), # Remove if name contains it
							LEFT(`NAMELSAD10`, LENGTH(`NAMELSAD10`) - LOCATE(' ', REVERSE(`NAMELSAD10`))) # Otherwise, remove the region type
						) AS county
					FROM geo_county_map
					GROUP BY 
						county
					HAVING 
						county IS NOT NULL
				) AS gcm
				ON gztoc.`geoid_int` = gcm.num_id 
			LEFT OUTER JOIN 
				geo_state_map AS gsm 
				ON gztoc.`state_int` = gsm.`num_id`
			WHERE 
				gcm.county IS NOT NULL AND
				gsm.NAME10 IS NOT NULL AND 
				gztoc.`zcta_int` IN ({$in_string});
		";
		$this->db->trans_start();
		$this->db->query($set_query);
		$response = $this->db->query($select_query, $region_ids);
		$this->db->trans_complete();

		return ($response->num_rows() > 0) ? $response->row_array() : false;
	}

	public function get_city_of_highest_populated_zipcode($zips)
	{
		$insert_string = implode(',', array_fill(0, count($zips), '?'));
		$bindings = $zips;
		$sql =
		"	SELECT 
				CONCAT(
					IF(
						`NAMELSAD10` LIKE '% city and borough', # Check if place name ends with ' city and borough'
						REPLACE(`NAMELSAD10`, ' city and borough', ''), # Remove if name contains it
						LEFT(`NAMELSAD10`, LENGTH(`NAMELSAD10`) - LOCATE(' ', REVERSE(`NAMELSAD10`))) # Otherwise, remove the region type
					), 
					', ', 
					gsm.NAME10
				) AS custom_name,
				gpm.DP0010001 AS population 
			FROM 
				(
					SELECT 
						DP0010001, 
						num_id AS id 
					FROM 
						geo_zcta_map 
					WHERE 
						num_id IN ({$insert_string}) 
					ORDER BY 
						DP0010001 DESC 
					LIMIT 1
				) AS max_zip
			INNER JOIN 
				geo_zcta_to_place AS gztp 
				ON gztp.zcta_int = max_zip.id
			INNER JOIN 
				geo_place_map AS gpm 
				ON gpm.num_id = gztp.geoid_int 
			INNER JOIN 
				geo_state_map AS gsm 
				ON gsm.num_id = gztp.state_int 
			ORDER BY 
				gpm.DP0010001 DESC 
			LIMIT 1;
		";
		$result = $this->db->query($sql, $bindings);
		if($result->num_rows() > 0)
		{
			$result_array = $result->row_array();
			return $result_array['custom_name'];
		}
		return '';
	}

	public function get_custom_regions_for_location_name($session_id, $location_id)
	{
		$bindings = array($session_id, $location_id);
		$sql =
		"	SELECT 
				GROUP_CONCAT(grc.name SEPARATOR ', ') AS custom_regions 
			FROM 
				mpq_sessions_and_submissions AS mss 
			INNER JOIN 
				mpq_custom_geos_join AS mcgj 
				ON mcgj.mpq_id = mss.id 
			INNER JOIN 
				geo_regions_collection AS grc 
				ON grc.id = mcgj.geo_regions_collection_id 
			WHERE 
				mss.session_id = ? AND 
				mcgj.location_id = ?;
		";
		$query = $this->db->query($sql, $bindings);
		if($query->num_rows() > 0)
		{
			$strings_to_remove = array(' city', ' state', ' Census Area');

			$result = $query->row_array();
			$new_name = $result['custom_regions'];
			foreach($strings_to_remove as $remove_this)
			{
				$new_name = str_replace($remove_this, '', $new_name);
			}
			return $new_name;
		}
		return '';
	}

	public function get_points_from_region_string($region_string)
	{
		$points_to_return = array();
		$target_region_ids = $this->get_target_region_ids($region_string); //assoc array of region_type => region_ids
		foreach($target_region_ids as $region_type => $region_ids)
		{
			$in_string = implode(',', array_fill(0, count($region_ids), '?'));
			$query = 
			"	SELECT 
					gp.local_id as {$region_type}, 
					Y(gp.center_point) as latitude, 
					X(gp.center_point) as longitude 
				FROM 
					geo_polygons AS gp 
				WHERE 
					gp.local_id IN ({$in_string});
			";
			$response = $this->db->query($query, $region_ids);
			$response_array = $response->result_array();

			$points_to_return[$region_type] = array();
			foreach($response_array as $region_info)
			{
				$lat = floatval($region_info['latitude']);
				$lng = floatval($region_info['longitude']);
				$points_to_return[$region_type][$region_info[$region_type]] = array('latitude' => $lat, 'longitude' => $lng);
			}
		}

		return $points_to_return;
	}

	function get_geojson_points_from_region_array($region_array)
	{
		$points_to_return = array();

		foreach($region_array as $region_type => $region_ids)
		{
			$in_string = implode(',', array_fill(0, count($region_ids), '?'));
			$query = 
			"	SELECT 
					local_id as {$region_type}, 
					CONCAT('{\"type\":\"Point\",\"coordinates\":[', X(center_point), ',', Y(center_point), ']}') as geojson_point_line 
				FROM 
					geo_polygons 
				WHERE 
					local_id IN ({$in_string});
			";

			$response = $this->db->query($query, $region_ids);
			$response_array = $response->result_array();

			$points_to_return[$region_type] = array_combine(array_column($response_array, $region_type), array_column($response_array, 'geojson_point_line'));
		}

		return $points_to_return;
	}

	function get_geojson_blobs_from_region_array($region_array)
	{
		$blobs_to_return = array();

		foreach($region_array as $region_type => $region_ids)
		{
			if(empty($region_ids)) continue;

			$in_string = implode(',', array_fill(0, count($region_ids), '?'));
			$query = 
			"	SELECT 
					local_id as {$region_type}, 
					geojson_blob as geojson_blob 
				FROM 
					geo_polygons 
				WHERE 
					local_id IN ({$in_string});
			";
			$response = $this->db->query($query, $region_ids);
			$response_array = $response->result_array();

			$blobs_to_return[$region_type] = array();
			foreach($response_array as $region_info)
			{
				$blobs_to_return[$region_type][$region_info[$region_type]] = $region_info['geojson_blob'];
			}
		}

		return $blobs_to_return;
	}

	public function get_target_region_ids($region_string)
	{
		$return_array = array();
		$region_array = array_filter(explode('|', trim($region_string)));

		foreach($region_array as $region)
		{
			$region_type = $this->get_region_type(substr($region, 0, 1));
			if(!isset($return_array[$region_type]))
			{
				$return_array[$region_type] = array();
			}
			$return_array[$region_type][] = substr($region, 1);
		}
		return $return_array;
	}

	private function get_region_type($char)
	{
		switch($char)
		{
			case 'Z':	return 'zcta';
			case 'P':	return 'place';
			case 'C':	return 'county';
			case 'S':	return 'state';
		}
	}

	public function get_list_of_distinct_counties_from_array_of_zips($zip_array, $in_one_array = false)
	{
		$zip_array[] = -1;
		$in_string = implode(',', array_fill(0, count($zip_array), '?'));
		$sql = 
		"	SELECT DISTINCT
				IF(
					gcm.`NAMELSAD10` LIKE '% Census Area', # Check if county name ends with ' Census Area'
					REPLACE(gcm.NAMELSAD10, ' Census Area', ''), # Remove if name contains it
					LEFT(gcm.NAMELSAD10, LENGTH(gcm.NAMELSAD10) - LOCATE(' ', REVERSE(gcm.NAMELSAD10))) # Otherwise, remove the region type
				) AS county 
			FROM 
				geo_county_map AS gcm
			INNER JOIN 
				geo_zcta_to_county AS ztc 
				ON ztc.geoid_int = gcm.num_id
			WHERE 
				ztc.zcta_int IN ({$in_string})
		";
		$response = $this->db->query($sql, $zip_array);
		$response = $response->result_array();

		if ($in_one_array)
		{
			$response['county'] = array_column($response, 'county');
			return (gettype($response['county']) == 'array') ? $response['county'] : array($response['county']);
		}
		return $response;
	}

	public function store_regions_for_geojson_for_later_use($region_array, $radius = NULL)
	{
		// Store in db with unique id and return id
		$blobs = (!empty($region_array['blobs']) && count($region_array['blobs']) > 0) ? $region_array['blobs'] : NULL;
		$points = (!empty($region_array['points']) && gettype($region_array['points']) == 'array') ? $region_array['points'] : NULL;
		$uris = (!empty($region_array['uris']) && $region_array['uris'] != '') ? explode(',', $region_array['uris']) : NULL;
		$center = (!empty($region_array['center'])) ? $region_array['center'] : NULL;

		$unique_id = uniqid();

		$bindings = array($unique_id);
		$sql = 
		'	INSERT INTO 
				geo_maps_available 
				(url_code, blobs_to_be_returned, points_to_be_returned, center, affected_uris, radius) 
			VALUES 
				(?, ?, ?, ?, ?, ?) 
		';
		$bindings[] = ($blobs) ? json_encode($blobs) : NULL;
		$bindings[] = ($points) ? json_encode($points) : NULL;
		$bindings[] = ($center) ? json_encode($center) : json_encode($this->calculate_center_from_points($blobs, $points));
		$bindings[] = ($uris) ? json_encode($uris) : NULL;
		$bindings[] = ($radius) ? $radius : NULL;

		$this->db->query($sql, $bindings);
		return $unique_id;
	}

	//not weighted, calculates center point on spheroid
	private function calculate_center_from_points($blobs, $points)
	{
		$point_array = array();
		if (!empty($points)) 
		{
			if (isset($points['lat_long_points'])) 
			{
				$point_array = array_merge($point_array, $points['lat_long_points']);
				unset($points['lat_long_points']);
			}
			foreach ($points as $type => $array)
			{
				$point_array = array_merge($point_array, $this->get_centers_from_blobs($array, $type));
			}
		}
		if (!empty($blobs) && gettype($blobs) == 'array') 
		{
			foreach ($blobs as $type => $array)
			{
				$point_array = array_merge($point_array, $this->get_centers_from_blobs($array, $type));
			}
		}

		$total_weight = count($point_array);
		$x = $y = $z = 0;
		foreach ($point_array as $point)
		{
			$lat_radian = $point['latitude'] * pi() / 180;
			$long_radian = $point['longitude'] * pi() / 180;

			$x += (cos($lat_radian) * cos($long_radian));
			$y += (cos($lat_radian) * sin($long_radian));
			$z += (sin($lat_radian));
		}

		$x /= $total_weight;
		$y /= $total_weight;
		$z /= $total_weight;

		$new_longitude = atan2($y, $x);
		$new_latitude = atan2($z, sqrt($x * $x + $y * $y));

		$return_point = array();
		$return_point['latitude'] = $new_latitude * 180 / pi();
		$return_point['longitude'] = $new_longitude * 180 / pi();

		return $return_point;
	}

	public function get_radii_from_unique_ids($unique_id_array)
	{
		$centers_radii = array();
		$in_array = array_fill(0, count($unique_id_array), '?');
		$sql = 
		'	SELECT 
				center AS center, 
				radius AS radius 
			FROM 
				geo_maps_available  
			WHERE 
				url_code IN (' . implode(',', $in_array) . ')
		';
		$response = $this->db->query($sql, $unique_id_array);
		return $response->result_array();
	}

	public function get_centers_from_regions($regions)
	{
		$centers = array();
		foreach ($regions as $region => $region_array)
		{
			$centers = array_merge($centers, $this->get_centers_from_blobs($region_array, $region));
		}
		return $centers;
	}

	private function get_centers_from_blobs($blobs, $map_type)
	{
		$in_array = array_fill(0, count($blobs), '?');
		$sql = 
		'	SELECT 
				INTPTLAT10 AS latitude, 
				INTPTLON10 AS longitude 
			FROM 
				geo_' . $map_type . '_map 
			WHERE 
				num_id IN (' . implode(',', $in_array) . ')
		';
		$response = $this->db->query($sql, $blobs);
		return $response->result_array();
	}

	public function get_region_array_from_db_with_unique_id($unique_id)
	{
		$sql = 
		'	SELECT 
				blobs_to_be_returned AS blobs,  
				points_to_be_returned AS points,  
				center AS center, 
				radius AS radius, 
				affected_uris AS uris 
			FROM 
				geo_maps_available 
			WHERE 
				url_code = ?
		';
		$response = $this->db->query($sql, $unique_id);
		$response = $response->result_array();
		return $response;
	}

	public function get_geojson_blobs_from_regions_from_db($regions, $map_type)
	{
		$in_array_string = implode(',', array_fill(0, count($regions), '?'));
		$sql = 
		"	SELECT 
				geojson_blob 
			FROM 
				geo_polygons 
			WHERE 
				local_id IN ({$in_array_string})
		";
		$response = $this->db->query($sql, $regions);
		return array_column($response->result_array(), 'geojson_blob');
	}

	public function get_geojson_and_zips_from_region_list($regions)
	{
		$in_string = implode(',', array_fill(0, count($regions), '?'));
		$sql = 
		"	SELECT 
				local_id as zip,
				geojson_blob 
			FROM 
				geo_polygons 
			WHERE 
				local_id IN ({$in_string})
		";
		$response = $this->db->query($sql, $regions);
		return $response->result_array();
	}

	public function get_zips_from_min_population_and_center($center, $min_pop)
	{
		$bindings = array($center['latitude'], $center['latitude'], $center['longitude'], $center['latitude'], $center['latitude'], $center['longitude'], $min_pop);
		$sql = 
		'	SELECT 
				NULL AS zips, 
				NULL AS distance,
				NULL AS total_pop 
			FROM dual 
			WHERE (@total := 0) 
			
			UNION 

			SELECT 
				zip.id2,
				(((acos(sin((? * pi() / 180)) * sin((coords.INTPTLAT10 * pi() / 180)) + cos((? * pi() / 180)) * cos((coords.INTPTLAT10 * pi() / 180)) * cos(((? - coords.INTPTLON10) * pi() / 180)))) * 180 / pi()) * 60 * 1.1515) AS distance, 
				@total := @total + zip.HD01_VD01 AS total
			FROM (
				SELECT 
					coords.num_id 
				FROM 
					`geo_zcta_map` AS coords 
				ORDER BY (((acos(sin((? * pi() / 180)) * sin((coords.INTPTLAT10 * pi() / 180)) + cos((? * pi() / 180)) * cos((coords.INTPTLAT10 * pi() / 180)) * cos(((? - coords.INTPTLON10) * pi() / 180)))) * 180 / pi()) * 60 * 1.1515) ASC
			) AS ids 
			INNER JOIN 
				`geo_ACS_12_5YR_B01001_zcta` AS zip 
				ON zip.num_id = ids.num_id
			INNER JOIN 
				`geo_zcta_map` AS coords  
				ON ids.num_id = coords.num_id
			WHERE 
				@total <= ?;
		';
		$response = $this->db->query($sql, $bindings);
		return $response->result_array();
	}

	public function get_region_centers_contained_in_bounded_area($north_east_corner, $south_west_corner, $region_type)
	{
		$bindings = array(
			$north_east_corner['longitude'], 
			$north_east_corner['latitude'], // point 1 of mbr
			
			$north_east_corner['longitude'], 
			$south_west_corner['latitude'], // point 2 of mbr
			
			$south_west_corner['longitude'],
			$south_west_corner['latitude'], // point 3 of mbr
			
			$south_west_corner['longitude'],
			$north_east_corner['latitude'], // point 4 of mbr
			
			$north_east_corner['longitude'],
			$north_east_corner['latitude'], // point 1 of mbr
		);

		// Have to use concat because ? puts value in single quotes
		$query = 
		"	SELECT 
				grm.GEOID10 AS region_id,
				grm.INTPTLAT10 AS lat,
				grm.INTPTLON10 AS lng
			FROM 
				geo_{$region_type}_map AS grm 
			WHERE 
				MBRContains(
					GeomFromText(CONCAT('Polygon((', ?, ' ', ?, ', ', ?, ' ', ?, ', ', ?, ' ', ?, ', ', ?, ' ', ?, ', ', ?, ' ', ?, '))')),
					grm.center_point
				)
		";
		$response = $this->db->query($query, $bindings);

		$return_array = array();
		foreach($response->result_array() as $centers)
		{
			$return_array[$centers['region_id']] = array('latitude' => floatval($centers['lat']), 'longitude' => floatval($centers['lng']));
		}

		return $return_array;
	}

	public function get_zips_from_radius_and_center($center, $radius)
	{
		$bindings = array($center['latitude'], $center['latitude'], $center['longitude'], $center['latitude'], $center['latitude'], $center['longitude'], $radius);
		$sql = 
		'	SELECT 
				ZCTA5CE10 AS zips, 
				(((acos(sin((? * pi() / 180)) * sin((INTPTLAT10 * pi() / 180)) + cos((? * pi() / 180)) * cos((INTPTLAT10 * pi() / 180)) * cos(((? - INTPTLON10) * pi() / 180)))) * 180 / pi()) * 60 * 1.1515) AS length 
			FROM 
				geo_zcta_map 
			WHERE 
				(((acos(sin((? * pi() / 180)) * sin((`INTPTLAT10` * pi() / 180)) + cos((? * pi() / 180)) * cos((`INTPTLAT10` * pi() / 180)) * cos(((? - `INTPTLON10`) * pi() / 180)))) * 180 / pi()) * 60 * 1.1515) <= ? 
			ORDER BY 
				length ASC
		';
		$response = $this->db->query($sql, $bindings);
		return $response->result_array();
	}

	public function get_all_state_and_zctas_given_zcta_or_state_ids($regions)
	{
		if(gettype($regions) != 'array')
		{
			throw new Exception('$regions must be an array.');
		}
		if(!isset($regions['state']) && !isset($regions['zcta']))
		{
			throw new Exception('$regions must have at least one index "state" or "zcta".');
		}

		$zcta_id_array = (isset($regions['zcta'])) ? $regions['zcta'] : array(-1);
		$state_id_array = (isset($regions['state'])) ? $regions['state'] : array(-1);

		$zcta_id_array_insert_string = implode(',', array_fill(0, count($zcta_id_array), '?'));
		$state_id_array_insert_string = implode(',', array_fill(0, count($state_id_array), '?'));

		$bindings = array_merge($zcta_id_array, $state_id_array);

		$set_query = "SET group_concat_max_len = 2097152;";
		$select_query = 
		"	SELECT 
				GROUP_CONCAT(a.zcta SEPARATOR ',') AS zcta_list,
				a.state_name AS state_name
			FROM
			(
				SELECT 
					DISTINCT gztp_1.zcta_int AS zcta,
					state_ids.state_id AS state_id,
					state_ids.state_name AS state_name
				FROM 
					geo_zcta_to_place AS gztp_1,
				(
					SELECT 
						gztp_2.`state_int` AS state_id,
						gsm.`NAME10` AS state_name
					FROM 
						geo_zcta_to_place AS gztp_2 
					INNER JOIN 
						geo_state_map AS gsm 
						ON 
							gsm.num_id = gztp_2.`state_int`
					WHERE 
						gztp_2.zcta_int IN({$zcta_id_array_insert_string}) 
				) AS state_ids
				WHERE 
					gztp_1.state_int = state_ids.state_id

				UNION

				SELECT 
					gztp_3.zcta_int AS zcta,
					gztp_3.state_int AS state_id,
					gsm.NAME10 AS state_name
				FROM 
					geo_zcta_to_place AS gztp_3
				INNER JOIN
					geo_state_map AS gsm 
					ON 
						gsm.num_id = gztp_3.`state_int`
				WHERE 
					gztp_3.state_int IN ({$state_id_array_insert_string})
			) AS a
			GROUP BY 
				a.state_name;
		";

		$this->db->trans_start();
		$this->db->query($set_query);
		$response = $this->db->query($select_query, $bindings);
		$this->db->trans_complete();

		if($response->num_rows() > 0)
		{
			$response_array = $response->result_array();
			$return_array = array_combine(array_column($response_array, 'state_name'), array_column($response_array, 'zcta_list'));
			foreach($return_array AS &$zcta_list)
			{
				$zcta_list = explode(',', $zcta_list);
			}
			return $return_array;
		}
		else
		{
			return array();
		}
	}

	public function get_demographics_from_array_of_regions($regions)
	{
		$regions = (empty($regions['zcta'])) ? array(null) : $regions['zcta'];
		$in_array = array_fill(0, count($regions), '?');
		$in_array = implode(',', $in_array);
		$sql = 
		"	SELECT 
				AVG(med_age.HD01_VD02)							AS median_age, 
				SUM(demos.HD01_VD01)							AS region_population, 
				SUM(demos.HD01_VD02) 							AS male_population, 
				SUM(demos.HD01_VD26) 							AS female_population, 
				SUM(race.HC01_VC88)								AS white_population, 
				SUM(race.HC01_VC89)								AS black_population, 
				SUM(race.HC01_VC91)								AS asian_population, 
				SUM(race.HC01_VC82)								AS hispanic_population, 
				SUM(((race.HC01_VC90 + race.HC01_VC92 + 
				race.HC01_VC93))) 								AS other_race_population, 
				SUM((race.HC01_VC88 + race.HC01_VC89 + 
				race.HC01_VC91 + race.HC01_VC82 + 
				race.HC01_VC90 + race.HC01_VC92 + 
				race.HC01_VC93))								AS normalized_race_population, 
				SUM((kids.HC01_EST_VC02 * kids.HC01_EST_VC18 
					/ 100))										AS kids_yes, 
				
				SUM((kids.HC01_EST_VC02 - (kids.HC01_EST_VC02 *
				kids.HC01_EST_VC18 / 100)))						AS kids_no, 
				SUM(kids.HC01_EST_VC02)							AS total_households, 
				AVG(kids.HC01_EST_VC03)							AS persons_household, 
				SUM((demos.HD01_VD03 + demos.HD01_VD04 + 
				demos.HD01_VD05 + demos.HD01_VD06 + 
				demos.HD01_VD27 + demos.HD01_VD28 + 
				demos.HD01_VD29 + demos.HD01_VD30))				AS age_under_18, 
				SUM((demos.HD01_VD07 + demos.HD01_VD08 + 
				demos.HD01_VD09 + demos.HD01_VD10 + 
				demos.HD01_VD31 + demos.HD01_VD32 + 
				demos.HD01_VD33 + demos.HD01_VD34))				AS age_18_24, 
				SUM((demos.HD01_VD11 + demos.HD01_VD12 + 
				demos.HD01_VD35 + demos.HD01_VD36))				AS age_25_34, 
				SUM((demos.HD01_VD13 + demos.HD01_VD14 + 
				demos.HD01_VD37 + demos.HD01_VD38))				AS age_35_44, 
				SUM((demos.HD01_VD15 + demos.HD01_VD16 + 
				demos.HD01_VD39 + demos.HD01_VD40))				AS age_45_54, 
				SUM((demos.HD01_VD17 + demos.HD01_VD18 + 
				demos.HD01_VD19 + demos.HD01_VD41 + 
				demos.HD01_VD42 + demos.HD01_VD43))				AS age_55_64, 
				SUM((demos.HD01_VD20 + demos.HD01_VD21 + 
				demos.HD01_VD22 + demos.HD01_VD23 + 
				demos.HD01_VD24 + demos.HD01_VD25 + 
				demos.HD01_VD44 + demos.HD01_VD45 + 
				demos.HD01_VD46 + demos.HD01_VD47 + 
				demos.HD01_VD48 + demos.HD01_VD49))				AS age_65_and_over, 
				
				SUM(business.ESTAB)								AS num_establishments, 
				SUM(house_income.HC01_EST_VC02)					AS num_households, 
				AVG(house_val.HC01_VC125_new)					AS average_home_value, 
				SUM((demos.HD01_VD01 * 
				(school.HC01_EST_VC08 + school.HC01_EST_VC09 + 
				school.HC01_EST_VC10 + school.HC01_EST_VC11)
				/100)) 											AS college_no, 
				
				SUM((demos.HD01_VD01 * 
				(school.HC01_EST_VC12 + school.HC01_EST_VC13)
				/100)) 											AS college_under, 
				
				SUM((demos.HD01_VD01* (school.HC01_EST_VC14)
				/100))											AS college_grad, 
				
				AVG(house_income.HC02_EST_VC02)					AS household_income, 
				SUM((income.HC01_VC75 + income.HC01_VC76 + 
				income.HC01_VC77 + income.HC01_VC78 + 
				income.HC01_VC79)) 								AS income_0_50, 
				SUM((income.HC01_VC80 + income.HC01_VC81))		AS income_50_100, 
				SUM(income.HC01_VC82)							AS income_100_150, 
				SUM((income.HC01_VC83 + income.HC01_VC84))		AS income_150 
				
			FROM 
				 geo_zcta_map AS zcta 
			INNER JOIN 
				geo_ACS_12_5YR_B01001_zcta AS demos 
				ON 
					demos.num_id = zcta.num_id 
			INNER JOIN 
				geo_ACS_12_5YR_B01002_zcta AS med_age 
				ON 
					med_age.num_id = zcta.num_id 
			INNER JOIN 
				geo_ACS_12_5YR_DP03_zcta AS income 
			 	ON 
					income.num_id = zcta.num_id 
			INNER JOIN 
				geo_ACS_12_5YR_DP04_zcta AS house_val 
				ON 
					house_val.num_id = zcta.num_id 
			INNER JOIN 
				geo_ACS_12_5YR_DP05_zcta AS race 
				ON 
					race.num_id = zcta.num_id 
			INNER JOIN 
				geo_ACS_12_5YR_S1101_zcta AS kids 
				ON 
					kids.num_id = zcta.num_id 
			INNER JOIN 
				geo_ACS_12_5YR_S1501_zcta AS school 
				ON 
					school.num_id = zcta.num_id 
			INNER JOIN 
				geo_ACS_12_5YR_S1903_zcta AS house_income 
				ON 
					house_income.num_id = zcta.num_id 
			LEFT OUTER JOIN 
				geo_BP_2011_00CZ1_zipcode AS business 
				ON 
					business.num_id = zcta.num_id 
			WHERE 
				zcta.num_id IN ({$in_array})
		";

		$response = $this->db->query($sql, $regions);
		return $response->row_array();
	}

	public function get_array_of_demographics_from_array_of_regions($regions)
	{
		$regions = (empty($regions)) ? array(null) : $regions;
		$in_string = implode(',', array_fill(0, count($regions), '?'));

		$sql = 
		"	SELECT 
				zcta.ZCTA5CE10									AS region_name, 
				X(zcta.center_point)							AS region_center_longitude, 
				Y(zcta.center_point)							AS region_center_latitude, 
				med_age.HD01_VD02								AS median_age, 
				demos.HD01_VD01									AS region_population, 
				demos.HD01_VD02									AS male_population, 
				demos.HD01_VD26									AS female_population, 
				race.HC01_VC88									AS white_population, 
				race.HC01_VC89									AS black_population, 
				race.HC01_VC91									AS asian_population, 
				race.HC01_VC82									AS hispanic_population, 
				(race.HC01_VC90 + race.HC01_VC92 + 
				race.HC01_VC93) 								AS other_race_population, 
				(race.HC01_VC88 + race.HC01_VC89 + 
				race.HC01_VC91 + race.HC01_VC82 + 
				race.HC01_VC90 + race.HC01_VC92 + 
				race.HC01_VC93)									AS normalized_race_population, 
				(kids.HC01_EST_VC02 * kids.HC01_EST_VC18 
					/ 100)										AS kids_yes, 
				
				(kids.HC01_EST_VC02 - (kids.HC01_EST_VC02 *
				kids.HC01_EST_VC18 / 100))						AS kids_no, 
				kids.HC01_EST_VC02								AS total_households, 
				kids.HC01_EST_VC03								AS persons_household, 
				(demos.HD01_VD03 + demos.HD01_VD04 + 
				demos.HD01_VD05 + demos.HD01_VD06 + 
				demos.HD01_VD27 + demos.HD01_VD28 + 
				demos.HD01_VD29 + demos.HD01_VD30)				AS age_under_18, 
				(demos.HD01_VD07 + demos.HD01_VD08 + 
				demos.HD01_VD09 + demos.HD01_VD10 + 
				demos.HD01_VD31 + demos.HD01_VD32 + 
				demos.HD01_VD33 + demos.HD01_VD34)				AS age_18_24, 
				(demos.HD01_VD11 + demos.HD01_VD12 + 
				demos.HD01_VD35 + demos.HD01_VD36)				AS age_25_34, 
				(demos.HD01_VD13 + demos.HD01_VD14 + 
				demos.HD01_VD37 + demos.HD01_VD38)				AS age_35_44, 
				(demos.HD01_VD15 + demos.HD01_VD16 + 
				demos.HD01_VD39 + demos.HD01_VD40)				AS age_45_54, 
				(demos.HD01_VD17 + demos.HD01_VD18 + 
				demos.HD01_VD19 + demos.HD01_VD41 + 
				demos.HD01_VD42 + demos.HD01_VD43)				AS age_55_64, 
				(demos.HD01_VD20 + demos.HD01_VD21 + 
				demos.HD01_VD22 + demos.HD01_VD23 + 
				demos.HD01_VD24 + demos.HD01_VD25 + 
				demos.HD01_VD44 + demos.HD01_VD45 + 
				demos.HD01_VD46 + demos.HD01_VD47 + 
				demos.HD01_VD48 + demos.HD01_VD49)				AS age_65_and_over, 
				
				business.ESTAB									AS num_establishments, 
				house_income.HC01_EST_VC02						AS num_households, 
				house_val.HC01_VC125_new						AS average_home_value, 
				(demos.HD01_VD01 * 
				(school.HC01_EST_VC08 + school.HC01_EST_VC09 + 
				school.HC01_EST_VC10 + school.HC01_EST_VC11)
				/100) 											AS college_no, 
				
				(demos.HD01_VD01 * 
				(school.HC01_EST_VC12 + school.HC01_EST_VC13)
				/100) 											AS college_under, 
				
				(demos.HD01_VD01* (school.HC01_EST_VC14)
				/100)											AS college_grad, 
				
				house_income.HC02_EST_VC02						AS household_income, 
				(income.HC01_VC75 + income.HC01_VC76 + 
				income.HC01_VC77 + income.HC01_VC78 + 
				income.HC01_VC79) 								AS income_0_50, 
				(income.HC01_VC80 + income.HC01_VC81)			AS income_50_100, 
				income.HC01_VC82								AS income_100_150, 
				(income.HC01_VC83 + income.HC01_VC84)			AS income_150 
				
			FROM 
				 geo_zcta_map AS zcta 
			INNER JOIN 
				geo_ACS_12_5YR_B01001_zcta AS demos 
				ON 
					demos.num_id = zcta.num_id 
			INNER JOIN 
				geo_ACS_12_5YR_B01002_zcta AS med_age 
				ON 
					med_age.num_id = zcta.num_id 
			INNER JOIN 
				geo_ACS_12_5YR_DP03_zcta AS income 
			 	ON 
					income.num_id = zcta.num_id 
			INNER JOIN 
				geo_ACS_12_5YR_DP04_zcta AS house_val 
				ON 
					house_val.num_id = zcta.num_id 
			INNER JOIN 
				geo_ACS_12_5YR_DP05_zcta AS race 
				ON 
					race.num_id = zcta.num_id 
			INNER JOIN 
				geo_ACS_12_5YR_S1101_zcta AS kids 
				ON 
					kids.num_id = zcta.num_id 
			INNER JOIN 
				geo_ACS_12_5YR_S1501_zcta AS school 
				ON 
					school.num_id = zcta.num_id 
			INNER JOIN 
				geo_ACS_12_5YR_S1903_zcta AS house_income 
				ON 
					house_income.num_id = zcta.num_id 
			LEFT OUTER JOIN 
				geo_BP_2011_00CZ1_zipcode AS business 
				ON 
					business.num_id = zcta.num_id 
			WHERE 
				zcta.num_id IN ({$in_string})
		";

		$response = $this->db->query($sql, $regions);
		return $response->result_array();
	}

	public function get_zips_from_session_id_and_feature_table($session_id, $feature_table, $as_string = false, $location_id = null)
	{
		$zips = array();
		$bindings = array($session_id);
		$query = 
		"	SELECT `region_data`
			FROM `{$feature_table}`
			WHERE `session_id` = ?
		";
		$result = $this->db->query($query, $bindings);

		if($result->num_rows() > 0)
		{
			$location_id = is_numeric($location_id) ? (int)$location_id : null;
			$region_data_array = json_decode($result->row()->region_data, true);
			if($region_data_array)
			{
				$this->map->convert_old_flexigrid_format($region_data_array);
				if(isset($region_data_array[$location_id]) && is_array($region_data_array[$location_id]))
				{
					// Mpq region
					$region_data = $region_data_array[$location_id];
					if(array_key_exists('ids', $region_data))
					{
						$zips = array_key_exists('zcta', $region_data['ids']) ? $region_data['ids']['zcta'] : array();
					}
				}
				else if($location_id === null && count($region_data_array) > 0)
				{
					// Planner region
					if(array_key_exists('ids', $region_data_array))
					{
						$zips = array_key_exists('zcta', $region_data_array['ids']) ? $region_data_array['ids']['zcta'] : array();
					}
				}
			}
		}

		if ($as_string)
		{
			return (empty($zips) ? '' : 'Z') . implode('|Z', $zips);
		}
		return array('zcta' => $zips);
	}

	public function get_regions_and_data_from_campaign_ids_and_date_range($campaign_ids, $start_date, $end_date)
	{
		if(gettype($campaign_ids) != 'array' || count($campaign_ids) < 1)
		{
			throw new Exception('$campaign_ids must be an array', 1);
		}

		$campaign_insert_string = implode(',', array_fill(0, count($campaign_ids), '?'));
		$bindings = array_merge($campaign_ids, array($start_date, $end_date));
		$sql = 
		"	SELECT 
				gcd.local_id AS zcta,
				gcd.population_total AS population,
				SUM(zr.`impressions`) AS impressions,
				SUM(COALESCE(zr.clicks, 0)) AS clicks,
				GROUP_CONCAT(DISTINCT(COALESCE(COALESCE(gpm.`NAMELSAD10`, gcm.`NAMELSAD10`), gpm_ca.province_name_english)) SEPARATOR ', ') AS city,
				GROUP_CONCAT(DISTINCT(COALESCE(gsm.`NAME10`, 'Canada')) SEPARATOR ', ') AS region,
				gp.`geojson_blob` AS `polygon`,
				X(gp.center_point) AS polygon_center_longitude, 
				Y(gp.center_point) AS polygon_center_latitude
			FROM 
				`AdGroups` AS ag
			INNER JOIN 
				zcta_records AS zr
				ON 
					zr.`ad_group_id` = ag.`ID`
			INNER JOIN 
				geo_cumulative_demographics AS gcd 
				ON 
					gcd.id = zr.gcd_id 
			INNER JOIN 
				geo_polygons AS gp 
				ON 
					gp.id = gcd.id 
			LEFT JOIN 
				geo_zcta_to_place AS gztp 
				ON 
					gztp.`zcta_int` = gcd.local_id 
			LEFT JOIN
				geo_zcta_to_county AS gztc 
				ON 
					gztc.`zcta_int` = gcd.local_id 
			LEFT JOIN 
				geo_county_map AS gcm 
				ON 
					gztc.geoid_int = gcm.num_id 
			LEFT JOIN 
				geo_place_map AS gpm 
				ON 
					gpm.num_id = gztp.geoid_int
			LEFT JOIN 
				geo_state_map AS gsm 
				ON 
					gsm.num_id = gztp.`state_int` OR 
					gsm.num_id = gztc.`state_int`
			LEFT JOIN 
				geo_fsa_map AS gfm 
				ON 
					gfm.gcd_id = gcd.id 
			LEFT JOIN 
				geo_province_map AS gpm_ca 
				ON 
					gpm_ca.id = gfm.pruid_int
			WHERE 
				ag.`campaign_id` IN ({$campaign_insert_string}) AND
				(zr.date BETWEEN ? AND ?)
			GROUP BY gcd.local_id
		";
		$result = $this->db->query($sql, $bindings);
		if($result->num_rows() > 0)
		{
			$impression_cutoff = 10000;
			$total_impressions = array_sum(array_column($result->result_array(), 'impressions'));

			return ($total_impressions >= $impression_cutoff) ? $result->result_array() : array();
		}
		return array();
	}

}

?>