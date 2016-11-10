<?php

require_once(FCPATH . '/vendor/phayes/geophp/geoPHP.inc');
class Maps_model extends CI_Model
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

	public function get_canadian_provinces_by_fsa(array $ids)
	{
		$canadian_fsas = array_filter($ids, function($fsa){
			return (preg_match('/[a-zA-Z][0-9][a-zA-Z]/', $fsa));
		});
		$fsa_int_bindings = $this->make_canadian_fsa_variables_for_sql($canadian_fsas);

		$in_string = implode(',', array_fill(0, count($canadian_fsas), '?'));

		$sql =
		"	SELECT
				gfm.CFSAUID AS fsa,
				gpm.province_name_english AS p_name
			FROM
				geo_province_map gpm
			INNER JOIN
				geo_fsa_map gfm
				ON
					gfm.pruid_int = gpm.id
			WHERE
				gfm.numeric_fsa IN ({$in_string})
			GROUP BY
				fsa
		";
		$response = $this->db->query($sql, $fsa_int_bindings);
		if($response->num_rows())
		{
			return array_combine(
				array_column($response->result_array(), 'fsa'),
				array_column($response->result_array(), 'p_name')
			);
		}
		return array();
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
						gcd.population_total,
						gcd.local_id AS id
					FROM
						geo_zcta_map gzm
					INNER JOIN
						geo_cumulative_demographics gcd
						ON
							gcd.id = gzm.gcd_id
					WHERE
						gzm.num_id IN ({$insert_string})
					ORDER BY
						gcd.population_total DESC
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

	public function get_custom_regions_for_location_name($session_id, $location_id, $mpq_id = false)
	{
		$id_where_sql = "mss.session_id = ?";
		if($mpq_id !== false)
		{
			$id_where_sql = "mss.id = ?";
			$bindings = array($mpq_id, $location_id);
		}
		else
		{
			$bindings = array($session_id, $location_id);
		}
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
				".$id_where_sql." AND
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

	function get_geojson_points_from_region_array(array $region_array)
	{
		$points_to_return = array();

		foreach($region_array as $region_type => $region_ids)
		{
			$canada_us_sql_and_bindings = $this->get_zcta_fsa_sql_and_bindings_from_region_array($region_ids);
			$bindings = array_merge($canada_us_sql_and_bindings['us_zcta_ints'], $canada_us_sql_and_bindings['canadian_fsa_ints']);
			$query =
			"	SELECT
					gp.local_id AS {$region_type},
					CONCAT('{\"type\":\"Point\",\"coordinates\":[', X(gp.center_point), ',', Y(gp.center_point), ']}') AS geojson_point_line
				FROM
					geo_polygons AS gp
				LEFT JOIN
					geo_zcta_map AS gzm
					ON gzm.gcd_id = gp.id
				WHERE
					gzm.num_id IN ({$canada_us_sql_and_bindings['us_zcta_insert_string']}) AND
					gp.local_id IS NOT NULL
				GROUP BY
					gp.local_id

				UNION ALL

				SELECT
					gp.local_id AS {$region_type},
					CONCAT('{\"type\":\"Point\",\"coordinates\":[', X(gp.center_point), ',', Y(gp.center_point), ']}') AS geojson_point_line
				FROM
					geo_polygons AS gp
				LEFT JOIN
					geo_fsa_map AS gfm
					ON gfm.gcd_id = gp.id
				WHERE
					gfm.numeric_fsa IN ({$canada_us_sql_and_bindings['canadian_fsa_insert_string']}) AND
					gp.local_id IS NOT NULL
				GROUP BY
					gp.local_id
			";

			$response = $this->db->query($query, $bindings);
			$response_array = $response->result_array();

			$points_to_return[$region_type] = array_combine(array_column($response_array, $region_type), array_column($response_array, 'geojson_point_line'));
		}

		return $points_to_return;
	}

	function get_geojson_blobs_from_region_array($region_array, $complexity_level = 'max')
	{
		$blobs_to_return = array();

		foreach($region_array as $region_type => $region_ids)
		{
			if(empty($region_ids)) continue;

			$polygon_precision_sql = $this->get_complexity_sql_piece($complexity_level);

			$canada_us_sql_and_bindings = $this->get_zcta_fsa_sql_and_bindings_from_region_array($region_ids);
			$bindings = array_merge($canada_us_sql_and_bindings['us_zcta_ints'], $canada_us_sql_and_bindings['canadian_fsa_ints']);

			$query =
			"	SELECT
					{$region_type},
					geo
				FROM (
					SELECT
						local_id as {$region_type},
						{$polygon_precision_sql} AS geo
					FROM
						geo_polygons AS gp
					LEFT JOIN
						geo_zcta_map AS gzm
						ON
						gzm.gcd_id = gp.id
					WHERE
						gzm.num_id IN ({$canada_us_sql_and_bindings['us_zcta_insert_string']})

					UNION ALL

					SELECT
						local_id as {$region_type},
						{$polygon_precision_sql} AS geo
					FROM
						geo_polygons AS gp
					LEFT JOIN
						geo_fsa_map AS gfm
						ON
							gfm.gcd_id = gp.id
					WHERE
						gfm.numeric_fsa IN ({$canada_us_sql_and_bindings['canadian_fsa_insert_string']})
				) AS combined_geos;
			";
			$response = $this->db->query($query, $bindings);
			$response_array = $response->result_array();
			$response->free_result();

			$blobs_to_return[$region_type] = array();
			$count_regions_returned = count($response_array);
			for($i = 0; $i < $count_regions_returned; $i++)
			{
				$geom = geoPHP::load($response_array[$i]['geo'], 'wkb');
				$blobs_to_return[$region_type][$response_array[$i]['zcta']] = $geom->out('json');

				unset($response_array[$i]);
			}
		}

		return $blobs_to_return;
	}

	private function get_zcta_fsa_sql_and_bindings_from_region_array(array $region_array)
	{
		$sql_and_bindings = [
			'canadian_fsa_ints' => [-1],
			'canadian_fsa_insert_string' => '?',
			'us_zcta_ints' => [-1],
			'us_zcta_insert_string' => '?'
		];

		if($region_array)
		{
			$canadian_fsa_ints = $this->make_canadian_fsa_variables_for_sql(array_filter($region_array, function($value){
				return (preg_match('/[a-zA-Z][0-9][a-zA-Z]/', $value));
			}));
			$canadian_fsa_insert_string = implode(',', array_fill(0, count($canadian_fsa_ints), '?'));
			$us_zctas = array_filter($region_array, 'ctype_digit');

			$us_zctas[] = -1;
			$us_zcta_ints = array_map('intval', $us_zctas);
			$us_zcta_insert_string = implode(',', array_fill(0, count($us_zcta_ints), '?'));

			$sql_and_bindings['canadian_fsa_ints'] = $canadian_fsa_ints;
			$sql_and_bindings['canadian_fsa_insert_string'] = $canadian_fsa_insert_string;
			$sql_and_bindings['us_zcta_ints'] = $us_zcta_ints;
			$sql_and_bindings['us_zcta_insert_string'] = $us_zcta_insert_string;
		}

		return $sql_and_bindings;
	}

	private function get_complexity_sql_piece($complexity_level)
	{
		$sql_piece = 'ST_AsBinary(';
		if($complexity_level == 'max')
		{
			$sql_piece .= 'polygon_precision_max';
		}
		else
		{
			$num_complexity_levels = 3;
			$start_complexity_level = min(max(1, (int)$complexity_level), $num_complexity_levels); // Accounts for invalid inputs

			$sql_piece .= 'COALESCE(';
			for($i = $start_complexity_level; $i <= $num_complexity_levels; $i++)
			{
				$sql_piece .= "polygon_precision_{$i},";
			}
			$sql_piece .= 'polygon_precision_max)';
		}
		$sql_piece .= ')';
		return $sql_piece;
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
		$canada_us_sql_and_bindings = $this->get_zcta_fsa_sql_and_bindings_from_region_array($zip_array);
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
				ztc.zcta_int IN ({$canada_us_sql_and_bindings['us_zcta_insert_string']})
		";
		$response = $this->db->query($sql, $canada_us_sql_and_bindings['us_zcta_ints']);
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
		"	SELECT
				NULL AS zips,
				NULL AS distance,
				NULL AS total_pop
			FROM dual
			WHERE (@total := 0)

			UNION

			SELECT
				zip.local_id,
				(((acos(sin((? * pi() / 180)) * sin((Y(center_point) * pi() / 180)) + cos((? * pi() / 180)) * cos((Y(center_point) * pi() / 180)) * cos(((? - X(center_point)) * pi() / 180)))) * 180 / pi()) * 60 * 1.1515) AS distance,
				@total := @total + zip.population_total AS total
			FROM (
				SELECT
					id,
					local_id
				FROM
					geo_polygons
				ORDER BY (((acos(sin((? * pi() / 180)) * sin((Y(center_point) * pi() / 180)) + cos((? * pi() / 180)) * cos((Y(center_point) * pi() / 180)) * cos(((? - X(center_point)) * pi() / 180)))) * 180 / pi()) * 60 * 1.1515) ASC
			) AS ids
			INNER JOIN
				geo_cumulative_demographics AS zip
				ON zip.id = ids.id
			INNER JOIN
				geo_polygons AS coords
				ON ids.local_id = coords.local_id
			WHERE
				@total <= ?;
		";
		$response = $this->db->query($sql, $bindings);
		return $response->result_array();
	}

	public function get_ratio_between_selected_regions_and_total_map_area($regions, $map_area_in_km)
	{
		if(count($regions))
		{
			$zcta_insert_string = implode(',', array_fill(0, count($regions), '?'));
			if(is_numeric($map_area_in_km))
			{
				$sql =
				"	SELECT
						SUM(land_area_sq_km) AS zcta_land_area
					FROM
						geo_polygons
					WHERE
						local_id IN ({$zcta_insert_string})
				";
			}
			else
			{
				$sql =
				"	SELECT
						POWER(6371, 2) * ABS(SIN((PI() / 180) * lat1) - SIN((PI() / 180) * lat2)) * ABS(((PI() / 180) * lon1) - ((PI() / 180) * lon2)) AS bounding_area,
						land_area AS zcta_land_area,
						(((acos(sin((coords.lat1 * pi() / 180)) * sin((coords.lat2 * pi() / 180)) + cos((coords.lat1 * pi() / 180)) * cos((coords.lat2 * pi() / 180)) * cos(((coords.lon2 - coords.lon1) * pi() / 180)))) * 180 / pi()) * 60 * 1.853159616) AS bounding_area_diagonal
					FROM (
						SELECT
							SUBSTRING_INDEX(SUBSTRING_INDEX(poly.geo, ' ', -5), ',', 1) AS lat1,
							SUBSTRING_INDEX(poly.geo, ' ', 1) AS lon1,
							SUBSTRING_INDEX(SUBSTRING_INDEX(poly.geo, ' ', -3), ',', 1) AS lat2,
							SUBSTRING_INDEX(SUBSTRING_INDEX(poly.geo, ',', -3), ' ', 1) AS lon2,
							poly.land_area_in_km AS land_area
						FROM (
							SELECT
								REPLACE(REPLACE(AsText(ST_Envelope(
									GeomFromText(
										CONCAT('LINESTRING(', GROUP_CONCAT(REPLACE(REPLACE(AsText(`center_point`), 'POINT(', ''), ')', '') SEPARATOR ', '), ')')
									)
								)), 'POLYGON((', ''), '))', '') AS geo,
								SUM(land_area_sq_km) AS land_area_in_km
							FROM
								geo_polygons
							WHERE
								local_id IN ({$zcta_insert_string})
						) AS poly
					) AS coords;
				";
			}
			$bindings = array_map('intval', $regions);
			$result = $this->db->query($sql, $bindings);
			if($result->num_rows() == 1)
			{
				$ratio = 0.0;
				if($map_area_in_km == null)
				{
					if($result->row()->bounding_area && $result->row()->bounding_area_diagonal < 1000) // measured in km
					{
						$ratio = $result->row()->zcta_land_area / $result->row()->bounding_area;
					}
				}
				else
				{
					if($map_area_in_km < 2500000) // Land area in km2
					{
						$ratio = $result->row()->zcta_land_area / $map_area_in_km;
					}
				}
				return $ratio;
			}
		}
		return 0.0;
	}

	public function get_geojson_and_geo_data_from_zctas_and_data_type($zctas, $data_type)
	{
		$polygon_precision_sql = $this->get_complexity_sql_piece(1);

		$canada_us_sql_and_bindings = $this->get_zcta_fsa_sql_and_bindings_from_region_array($zctas);
		$bindings = array_merge(array($data_type), $canada_us_sql_and_bindings['us_zcta_ints'], array($data_type), $canada_us_sql_and_bindings['canadian_fsa_ints']);
		$sql =
		"	SELECT
				gcd.local_id AS zcta,
				IF(
					? = 'polygon',
					{$polygon_precision_sql},
					CONCAT('{\"type\":\"Point\",\"coordinates\":[', X(gp.center_point), ',', Y(gp.center_point), ']}')
				) AS geo,
				gcd.population_total AS population,
				GROUP_CONCAT(DISTINCT(COALESCE(gpm.NAMELSAD10, gcm.NAMELSAD10)) SEPARATOR ', ') AS city,
				GROUP_CONCAT(DISTINCT(COALESCE(gsm_p.NAME10, gsm_c.NAME10)) SEPARATOR ', ') AS region
			FROM
				geo_cumulative_demographics AS gcd
			INNER JOIN
				geo_polygons AS gp
				ON
					gp.id = gcd.id
			LEFT JOIN
				geo_zcta_map AS gzm
				ON
					gzm.gcd_id = gp.id
			LEFT JOIN
				geo_zcta_to_place AS gztp
				ON
					gztp.zcta_int = gzm.num_id
			LEFT JOIN
				geo_zcta_to_county AS gztc
				ON
					gztc.zcta_int = gzm.num_id
			LEFT JOIN
				geo_county_map AS gcm
				ON
					gztc.geoid_int = gcm.num_id
			LEFT JOIN
				geo_place_map AS gpm
				ON
					gpm.num_id = gztp.geoid_int
			LEFT JOIN
				geo_state_map AS gsm_p
				ON
					gsm_p.num_id = gztp.state_int
			LEFT JOIN
				geo_state_map AS gsm_c
				ON
					gsm_c.num_id = gztc.state_int
			WHERE
				gzm.num_id IN ({$canada_us_sql_and_bindings['us_zcta_insert_string']})
			GROUP BY
				gcd.id

			UNION ALL

			SELECT
				gcd.local_id AS zcta,
				IF(
					? = 'polygon',
					{$polygon_precision_sql},
					CONCAT('{\"type\":\"Point\",\"coordinates\":[', X(gp.center_point), ',', Y(gp.center_point), ']}')
				) AS geo,
				gcd.population_total AS population,
				GROUP_CONCAT(DISTINCT(gpm_ca.province_name_english) SEPARATOR ', ') AS city,
				'Canada' AS region
			FROM
				geo_cumulative_demographics AS gcd
			INNER JOIN
				geo_polygons AS gp
				ON
					gp.id = gcd.id
			LEFT JOIN
				geo_fsa_map AS gfm
				ON
					gfm.gcd_id = gcd.id
			LEFT JOIN
				geo_province_map AS gpm_ca
				ON
					gpm_ca.id = gfm.pruid_int
			WHERE
				gfm.numeric_fsa IN ({$canada_us_sql_and_bindings['canadian_fsa_insert_string']})
			GROUP BY
				gcd.id
		";
		$result = $this->db->query($sql, $bindings);
		if($result->num_rows() > 0)
		{
			$result_array = $result->result_array();
			$result->free_result();

			$result_array_length = count($result_array);
			$return_array = [];

			for($i = 0; $i < $result_array_length; $i++)
			{
				$values = $result_array[$i];
				$result_array[$i] = null;
				unset($result_array[$i]);

				$zcta = (is_numeric($values['zcta'])) ? intval($values['zcta']) : $values['zcta'];
				$return_array[$zcta] = $values;
				if($data_type == 'polygon')
				{
					$polygon = geoPHP::load($return_array[$zcta]['geo'], 'wkb');
					$return_array[$zcta]['geo'] = $polygon;
					$polygon = null;
					unset($polygon);
				}
			}
			return $return_array;
		}
		return array();
	}

	private function make_canadian_fsa_variables_for_sql($canadian_fsas)
	{
		$fsa_ints = [-1];
		foreach($canadian_fsas AS $fsa)
		{
			if(strlen((string)$fsa) == 3)
			{
				$proper_fsa = strtoupper($fsa);
				$fsa_ints[] = (int)(ord(substr($proper_fsa, 0, 1)) . substr($proper_fsa, 1, 1) . ord(substr($proper_fsa, 2, 1)));
			}
		}
		return $fsa_ints;
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
				gp.local_id AS region_id,
				Y(gp.center_point) AS lat,
				X(gp.center_point) AS lng
			FROM
				geo_polygons AS gp
			WHERE
				MBRContains(
					GeomFromText(CONCAT('Polygon((', ?, ' ', ?, ', ', ?, ' ', ?, ', ', ?, ' ', ?, ', ', ?, ' ', ?, ', ', ?, ' ', ?, '))')),
					gp.center_point
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
		$latitude = floatval($center['latitude']);
		$longitude = floatval($center['longitude']);
		$radius = intval($radius);
		$bindings = array_merge(
			[$latitude, $longitude, $latitude, $radius],
			[$longitude, $latitude, $radius],
			[$longitude, $latitude, $radius],
			[$longitude, $latitude, $radius],
			[$longitude, $latitude, $radius]
		);

		$sql =
		"	SELECT
				local_id AS zips
			FROM(
				SELECT
					gp.*,
					(6371 * acos(cos(radians(?)) * cos(radians(Y(gp.center_point))) * cos(radians(X(gp.center_point)) - radians(?)) + sin(radians(?)) * sin(radians(Y(gp.center_point))))) AS distance
					# Based on (CONVERSION_MULTIPLIER * acos(cos(radians(LATITUDE_1)) * cos(radians(LATITUDE_2)) * cos(radians(LONGITUDE_2) - radians(LONGITUDE_1)) + sin(radians(LATITUDE_1)) * sin(radians(LATITUDE_2)))) - https://en.wikipedia.org/wiki/Haversine_formula
				FROM geo_polygons AS gp
				HAVING distance < (SQRT(2) * ?) # Trick to speed up query by limiting points initially
			) AS t1
			WHERE
				ST_Intersects(ST_Buffer(POINT(?, ?), ? / (111)), COALESCE(polygon_precision_1, polygon_precision_max)) AND # 111 is a rough estimate of degrees to km
				(
					ST_Within(ST_Buffer(POINT(?, ?), ? / (111)), COALESCE(polygon_precision_1, polygon_precision_3, polygon_precision_3, polygon_precision_max)) OR
					ST_Contains(ST_Buffer(POINT(?, ?), ? / (111)), COALESCE(polygon_precision_1, polygon_precision_3, polygon_precision_3, polygon_precision_max)) OR
					ST_Overlaps(ST_Buffer(POINT(?, ?), ? / (111)), COALESCE(polygon_precision_1, polygon_precision_3, polygon_precision_3, polygon_precision_max))
				);
		";
		$response = $this->db->query($sql, $bindings);
		return $response->result_array();
	}

	public function get_all_state_and_zctas_given_zcta_or_state_ids(array $regions)
	{
		if(!isset($regions['state']) && !isset($regions['zcta']))
		{
			throw new Exception('$regions must have at least one index "state" or "zcta".');
		}

		$zcta_regions_array = (isset($regions['zcta'])) ? $regions['zcta'] : [];
		$canada_us_sql_and_bindings = $this->get_zcta_fsa_sql_and_bindings_from_region_array($zcta_regions_array);

		$state_int_ids = (isset($regions['state'])) ? array_map('intval', $regions['state']) : [];
		$state_id_array = array_merge([-1], $state_int_ids);
		$state_id_array_insert_string = implode(',', array_fill(0, count($state_id_array), '?'));

		$bindings = array_merge(
			$canada_us_sql_and_bindings['us_zcta_ints'],
			$canada_us_sql_and_bindings['us_zcta_ints'],
			$state_id_array,
			$canada_us_sql_and_bindings['canadian_fsa_ints']
		);

		$set_query = "SET group_concat_max_len = 2097152;";
		$select_query =
		"	SELECT
				GROUP_CONCAT(DISTINCT a.zcta SEPARATOR ',') AS zcta_list,
				a.state_name AS state_name
			FROM
			(
				SELECT
					gztp_1.ZCTA5 AS zcta,
					state_ids.state_id AS state_id,
					state_ids.state_name AS state_name
				FROM
					geo_zcta_to_place AS gztp_1,
				(
					SELECT DISTINCT
						gztp_2.state_int AS state_id,
						gsm.NAME10 AS state_name
					FROM
						geo_zcta_to_place AS gztp_2
					INNER JOIN
						geo_state_map AS gsm
						ON
							gsm.num_id = gztp_2.state_int
					WHERE
						gztp_2.zcta_int IN ({$canada_us_sql_and_bindings['us_zcta_insert_string']})
				) AS state_ids
				WHERE
					gztp_1.state_int = state_ids.state_id

				UNION ALL

				SELECT
					gztc_1.ZCTA5 AS zcta,
					state_ids.state_id AS state_id,
					state_ids.state_name AS state_name
				FROM
					geo_zcta_to_county AS gztc_1,
				(
					SELECT DISTINCT
						gztc_2.state_int AS state_id,
						gsm.NAME10 AS state_name
					FROM
						geo_zcta_to_county AS gztc_2
					INNER JOIN
						geo_state_map AS gsm
						ON
							gsm.num_id = gztc_2.state_int
					WHERE
						gztc_2.zcta_int IN ({$canada_us_sql_and_bindings['us_zcta_insert_string']})
				) AS state_ids
				WHERE
					gztc_1.state_int = state_ids.state_id
				GROUP BY zcta

				UNION ALL

				SELECT
					gztp_3.ZCTA5 AS zcta,
					gztp_3.state_int AS state_id,
					gsm.NAME10 AS state_name
				FROM
					geo_zcta_to_place AS gztp_3
				INNER JOIN
					geo_state_map AS gsm
					ON
						gsm.num_id = gztp_3.state_int
				WHERE
					gztp_3.state_int IN ({$state_id_array_insert_string})
				GROUP BY zcta

				UNION ALL

				SELECT
					gfm.CFSAUID AS zcta,
					province_ids.province_id AS state_id,
					province_ids.province_name AS state_name
				FROM
					geo_fsa_map gfm,
				(
					SELECT DISTINCT
						gpm1.id AS province_id,
						gpm1.province_name_english AS province_name
					FROM
						geo_province_map AS gpm1
					INNER JOIN
						geo_fsa_map AS gfm1
						ON
							gfm1.pruid_int = gpm1.id
					WHERE
						gfm1.numeric_fsa IN ({$canada_us_sql_and_bindings['canadian_fsa_insert_string']})
				) AS province_ids
				WHERE
					gfm.pruid_int = province_ids.province_id
				GROUP BY zcta
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
		$in_array = implode(',', array_fill(0, count($regions), '?'));
		$sql =
		"	SELECT
				SUM(gcd.population_total) AS region_population,
				SUM(gcd.population_female) AS female_population,
				SUM(gcd.population_male) AS male_population,
				SUM(gcd.population_white) AS white_population,
				SUM(gcd.population_black) AS black_population,
				SUM(gcd.population_asian) AS asian_population,
				SUM(gcd.population_hispanic) AS hispanic_population,
				SUM(gcd.population_other_race) AS other_race_population,
				SUM(gcd.population_white +
				gcd.population_black +
				gcd.population_asian +
				gcd.population_hispanic +
				gcd.population_other_race) AS normalized_race_population,
				AVG(gcd.population_median_age) AS median_age,
				SUM(gcd.population_age_under_18) AS age_under_18,
				SUM(gcd.population_age_18_24) AS age_18_24,
				SUM(gcd.population_age_25_34) AS age_25_34,
				SUM(gcd.population_age_35_44) AS age_35_44,
				SUM(gcd.population_age_45_54) AS age_45_54,
				SUM(gcd.population_age_55_64) AS age_55_64,
				SUM(gcd.population_age_65_and_over) AS age_65_and_over,
				ROUND(SUM(gcd.population_total) * (SUM(gcd.population_college_none) / (SUM(gcd.population_college_none) + SUM(gcd.population_college_under) + SUM(gcd.population_college_grad)))) AS college_no,
				ROUND(SUM(gcd.population_total) * (SUM(gcd.population_college_under) / (SUM(gcd.population_college_none) + SUM(gcd.population_college_under) + SUM(gcd.population_college_grad)))) AS college_under,
				ROUND(SUM(gcd.population_total) * (SUM(gcd.population_college_grad) / (SUM(gcd.population_college_none) + SUM(gcd.population_college_under) + SUM(gcd.population_college_grad)))) AS college_grad,

				SUM(gcd.households_total) AS total_households,
				SUM(gcd.households_with_kids) AS kids_yes,
				SUM((gcd.households_total -
				gcd.households_with_kids)) AS kids_no,
				AVG(gcd.households_average_occupancy) AS persons_household,
				AVG(gcd.households_median_value) AS average_home_value,
				AVG(gcd.households_average_income) AS household_income,
				SUM(gcd.households_income_0_50) AS income_0_50,
				SUM(gcd.households_income_50_100) AS income_50_100,
				SUM(gcd.households_income_100_150) AS income_100_150,
				SUM(gcd.households_income_150_and_up) AS income_150,

				SUM(gcd.businesses_total) AS num_establishments
			FROM
				geo_cumulative_demographics AS gcd
			WHERE
				gcd.local_id IN ({$in_array})
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
				X(gp.center_point) AS region_center_longitude,
				Y(gp.center_point) AS region_center_latitude,
				gcd.local_id AS region_name,
				gcd.population_total AS region_population,
				gcd.population_female AS female_population,
				gcd.population_male AS male_population,
				gcd.population_white AS white_population,
				gcd.population_black AS black_population,
				gcd.population_asian AS asian_population,
				gcd.population_hispanic AS hispanic_population,
				gcd.population_other_race AS other_race_population,
				(gcd.population_white +
				gcd.population_black +
				gcd.population_asian +
				gcd.population_hispanic +
				gcd.population_other_race) AS normalized_race_population,
				gcd.population_median_age AS median_age,
				gcd.population_age_under_18 AS age_under_18,
				gcd.population_age_18_24 AS age_18_24,
				gcd.population_age_25_34 AS age_25_34,
				gcd.population_age_35_44 AS age_35_44,
				gcd.population_age_45_54 AS age_45_54,
				gcd.population_age_55_64 AS age_55_64,
				gcd.population_age_65_and_over AS age_65_and_over,
				ROUND(gcd.population_total * (gcd.population_college_none / (gcd.population_college_none + gcd.population_college_under + gcd.population_college_grad))) AS college_no,
				ROUND(gcd.population_total * (gcd.population_college_under / (gcd.population_college_none + gcd.population_college_under + gcd.population_college_grad))) AS college_under,
				ROUND(gcd.population_total * (gcd.population_college_grad / (gcd.population_college_none + gcd.population_college_under + gcd.population_college_grad))) AS college_grad,

				gcd.households_total AS total_households,
				gcd.households_with_kids AS kids_yes,
				(gcd.households_total -
				gcd.households_with_kids) AS kids_no,
				gcd.households_average_occupancy AS persons_household,
				gcd.households_median_value AS average_home_value,
				gcd.households_average_income AS household_income,
				gcd.households_income_0_50 AS income_0_50,
				gcd.households_income_50_100 AS income_50_100,
				gcd.households_income_100_150 AS income_100_150,
				gcd.households_income_150_and_up AS income_150,

				gcd.businesses_total AS num_establishments,

				gcd.population_republican AS republican,
				gcd.population_democrat AS democrat,
				gcd.population_independent AS independent,
				gcd.population_total - gcd.population_age_under_18 - gcd.population_republican - gcd.population_democrat - gcd.population_independent AS unregistered
			FROM
				geo_cumulative_demographics AS gcd
			LEFT OUTER JOIN
				geo_polygons AS gp
				ON
					gp.id = gcd.id
			WHERE
				gcd.local_id IN ({$in_string})
		";
		$response = $this->db->query($sql, $regions);
		return $response->result_array();
	}

	public function get_regions_that_exist_in_db_from_region_array($regions)
	{
		$regions[] = -1;
		$insert_string = implode(',', array_fill(0, count($regions), '?'));
		$sql =
		"	SELECT
				local_id
			FROM
				geo_cumulative_demographics
			WHERE
				local_id IN ({$insert_string})
			GROUP BY
				local_id
		";
		$result = $this->db->query($sql, $regions);
		return array_column($result->result_array(), 'local_id');
	}

	public function get_zips_from_session_id_and_feature_table($session_id, $feature_table, $as_string = false, $location_id = null, $id = false)
	{
		$zips = array();
		if($id !== false)
		{
			$bindings = array($id);
			$query = "
				SELECT 
					`region_data`
				FROM 
					`{$feature_table}`
				WHERE 
				`id` = ?";
		}
		else
		{
			$bindings = array($session_id);
			$query = "
				SELECT 
					`region_data`
				FROM 
					`{$feature_table}`
				WHERE 
				`session_id` = ?";
		}
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

	public function get_applicable_regions_from_tv_region_ids($tv_regions)
	{
		if(!is_array($tv_regions) || count($tv_regions) < 1)
		{
			return array();
		}
		$total_regions = call_user_func_array('array_merge', $tv_regions);
		$zcta_insert_string = implode(',', array_fill(0, count($total_regions), '?'));
		$sql =
		"	SELECT
				gzm.ZCTA5CE10 AS zcta,
				1 AS tv
			FROM
				geo_zcta_map AS gzm
			WHERE
				gzm.num_id IN ({$zcta_insert_string})
			GROUP BY zcta
		";
		$result = $this->db->query($sql, $total_regions);
		if($result->num_rows() > 0)
		{
			$result_array = $result->result_array();
			$result_array = array_combine(array_column($result_array, 'zcta'), $result_array);
			array_walk_recursive($result_array, function (&$value, $key) {
				if($key == 'tv')
				{
					$value = true;
				}
			});
			foreach($result_array as $zcta => &$data)
			{
				foreach($tv_regions as $campaign_id => $regions)
				{
					if(in_array($zcta, $regions))
					{
						if(!isset($data['tv_campaigns']))
						{
							$data['tv_campaigns'] = array($campaign_id);
						}
						if(!in_array($campaign_id, $data['tv_campaigns']))
						{
							$data['tv_campaigns'][] = $campaign_id;
						}
					}
				}
			}
			return $result_array;
		}
		return array();
	}

	public function get_zone_regions(array $syscodes, $geojson_region_type)
	{
		if($syscodes)
		{
			$syscode_insert_string = implode(',', array_fill(0, count($syscodes), '?'));
			$bindings = array_map('intval', $syscodes);
			$json_query =
			"	SELECT
					tssrj.syscode_int,
					grc.json_regions
				FROM
					geo_regions_collection AS grc
				JOIN
					tp_spectrum_syscode_region_join AS tssrj
					ON
						tssrj.geo_regions_collection_id = grc.id
				WHERE
					tssrj.syscode_int IN ({$syscode_insert_string})
				GROUP BY
					tssrj.syscode_int
			";
			$json_result = $this->db->query($json_query, $bindings);
			if($json_result->num_rows() > 0)
			{
				$json_result_array = $json_result->result_array();
				$all_regions = array_combine(
					array_column($json_result_array, 'syscode_int'),
					array_map(function($value){
						return json_decode($value, true);
					}, array_column($json_result_array, 'json_regions'))
				);
				$region_ids_for_region_geojson = call_user_func_array('array_merge', $all_regions);

				$regions = $this->get_geojson_points_from_region_array(array('zcta' => $region_ids_for_region_geojson));
				if($regions)
				{
					$regions = $regions['zcta'];
					$regions_to_return = array_fill_keys(array_keys($regions), []);
					foreach ($regions_to_return as $zcta => &$region_data)
					{
						$syscodes = [];
						foreach ($all_regions as $syscode => $affected_zips)
						{
							if(in_array($zcta, $affected_zips))
							{
								$syscodes[] = $syscode;
							}
						}

						$region_data = [
							'geo' => $regions[$zcta],
							'syscodes' => $syscodes
						];

						// $regions_to_return[$region_data['syscode_int']] = $this->get_cumulative_polygon_from_id_array($regions);
					}
					return $regions_to_return;
				}
			}
		}
		return array();
	}

	private function get_cumulative_polygon_from_id_array(array $zcta_ids)
	{
		ini_set('memory_limit', '-1');
		if($zcta_ids)
		{
			$bindings = array_merge($zcta_ids, $zcta_ids);
			$id_insert_string = implode(',', array_fill(0, count($zcta_ids), '?'));
			$query =
			"	SELECT
					gp.local_id,
					-- gp.geojson_blob
					CONCAT('{\"type\":\"Point\",\"coordinates\":[', X(gp.center_point), ',', Y(gp.center_point), ']}') as geojson_point_line
				FROM
					geo_polygons AS gp
				LEFT JOIN
					geo_zcta_map AS gzm
					ON gzm.gcd_id = gp.id
				LEFT JOIN
					geo_fsa_map AS gfm
					ON gfm.gcd_id = gp.id
				WHERE
					gzm.num_id IN ({$id_insert_string}) OR
					gfm.CFSAUID IN ({$id_insert_string}) AND
					gp.local_id IS NOT NULL
			";
			$result = $this->db->query($query, $bindings);
			if($result->num_rows() > 0)
			{
				geophp_load();
				$geometry_collection = '{"type":"GeometryCollection","geometries": [';
				$polygons = $result->result_array();
				foreach($polygons as $index => $polygon)
				{
					if($index) $geometry_collection .= ',';
					// $geometry = geoPHP::load($polygon['geojson_blob'], 'json');
					$geometry_collection .= $polygon['geojson_blob'];
				}
				$geometry_collection .= ']}';
				$combined = geoPHP::load($geometry_collection, 'json');
				$geometry_collection = null;
				// $combined
			}
		}
		return array();
	}

	public function get_multi_points_for_geofencing(array $advertiser_ids, array $campaign_ids, $start_date, $end_date, $number_of_points_to_return = 10000)
	{
		$advertiser_insert_string = implode(',', array_fill(0, count($advertiser_ids), '?'));
		$campaign_insert_string = implode(',', array_fill(0, count($campaign_ids), '?'));
		$limit_points_to_return = abs(intval($number_of_points_to_return));

		$points_bindings = array_merge(
			array_map('intval', $advertiser_ids),
			array_map('intval', $campaign_ids),
			[$start_date . ' 00:00:00', $end_date . ' 23:59:59'],
			[$limit_points_to_return]
		);
		$stats_bindings = array_merge(
			array_map('intval', $advertiser_ids),
			array_map('intval', $campaign_ids),
			[$start_date, $end_date]
		);

		$set_sql = "SET group_concat_max_len = 2097152";
		$points_sql =
		"	SELECT
				gsp.geofence_adgroup_centers_id AS id,
				gac.name AS name,
				X(gac.center_point) AS center_point_longitude,
				Y(gac.center_point) AS center_point_latitude,
				gac.radius AS radius_in_meters,
				CONCAT('{\"type\":\"MultiPoint\",\"coordinates\":[', GROUP_CONCAT(DISTINCT CONCAT('[', X(gsp.location_point), ',', Y(gsp.location_point), ']') SEPARATOR ','), ']}') AS multi_point
			FROM (
				SELECT
					@num := IF(@gacid = location_points.geofence_adgroup_centers_id, @num := @num + 1, 1) AS point_number,
					@gacid := location_points.geofence_adgroup_centers_id,
					location_points.geofence_adgroup_centers_id,
					POINT(TRUNCATE(X(location_points.location_point), 5), TRUNCATE(Y(location_points.location_point), 5)) AS location_point
				FROM
				(
					SELECT
						gsp.geofence_adgroup_centers_id,
						gsp.date_time,
						gsp.location_point
					FROM
						geofence_saved_points AS gsp
					JOIN
						geofence_adgroup_centers AS gac
						ON
							gac.id = gsp.geofence_adgroup_centers_id
					JOIN
						AdGroups AS a
						ON
							a.vl_id = gac.adgroup_vl_id
					JOIN
						Campaigns AS c
						ON
							c.id = a.campaign_id
					JOIN
						Advertisers AS adv
						ON
							adv.id = c.business_id
					WHERE
						adv.id IN ({$advertiser_insert_string}) AND
						c.id IN ({$campaign_insert_string}) AND
						a.Source = 'TDGF' AND
						(gsp.date_time BETWEEN ? AND ?)
					GROUP BY gsp.geofence_adgroup_centers_id, gsp.location_point
					ORDER BY gsp.geofence_adgroup_centers_id, gsp.date_time DESC
				) AS location_points
				JOIN (SELECT @gacid := NULL, @point_number := 0) AS vars
				ORDER BY point_number
				LIMIT ?
			) AS gsp
			JOIN
				geofence_adgroup_centers AS gac
				ON
					gac.id = gsp.geofence_adgroup_centers_id
			GROUP BY
				gsp.geofence_adgroup_centers_id
		";
		$stats_sql =
		"	SELECT
				gac.id,
				gac.name,
				gac.address,
				SUM(gdt.impressions_total_android) AS android_impression_sum,
				SUM(gdt.impressions_total_ios) AS ios_impression_sum,
				SUM(gdt.impressions_total_other) AS other_impression_sum,
				SUM(gdt.clicks_total_android) AS android_click_sum,
				SUM(gdt.clicks_total_ios) AS ios_click_sum,
				SUM(gdt.clicks_total_other) AS other_click_sum
			FROM
				geofence_daily_totals AS gdt
			JOIN
				geofence_adgroup_centers AS gac
				ON
					gac.id = gdt.geofence_adgroup_centers_id
			JOIN
				AdGroups AS a
				ON
					a.vl_id = gac.adgroup_vl_id
			JOIN
				Campaigns AS c
				ON
					c.id = a.campaign_id
			JOIN
				Advertisers AS adv
				ON
					adv.id = c.business_id
			WHERE
				adv.id IN ({$advertiser_insert_string}) AND
				c.id IN ({$campaign_insert_string}) AND
				a.Source = 'TDGF' AND
				gdt.date BETWEEN ? AND ?
			GROUP BY
				gdt.geofence_adgroup_centers_id
		";

		$this->db->trans_start();
			$this->db->query($set_sql);
			$points_result = $this->db->query($points_sql, $points_bindings);
			$stats_result = $this->db->query($stats_sql, $stats_bindings);
		$this->db->trans_complete();

		if($points_result->num_rows > 0 && $stats_result->num_rows > 0)
		{
			$combined_result = array_replace_recursive($points_result->result_array(), $stats_result->result_array());
			return $combined_result;
		}
		return array();
	}

	public function zcta_and_type_from_center(array $latlng)
	{
		if($latlng)
		{
			$latitude = floatval($latlng['latitude']);
			$longitude = floatval($latlng['longitude']);

			$bindings = [
				$longitude, $latitude,
				$longitude, $latitude,
				$longitude, $latitude
			];

			$sql =
			"	SELECT
					gp.local_id AS zcta,
					gcd.density_by_population_type AS zcta_type
				FROM geo_polygons AS gp
				JOIN geo_cumulative_demographics AS gcd
					ON gcd.id = gp.id
				JOIN geo_polygons_point_search AS glltg
					ON glltg.gcd_id = gp.id
				WHERE
					((? * 10000) BETWEEN glltg.min_lng_scaled AND glltg.max_lng_scaled) AND
					((? * 10000) BETWEEN glltg.min_lat_scaled AND glltg.max_lat_scaled) AND
					ST_Contains(gp.polygon_precision_max, POINT(?, ?));
			";
			$result = $this->db->query($sql, $bindings);
			if($result->num_rows() > 0)
			{
				return $result->row_array();
			}
		}
		return [];
	}

	public function get_zips_affected_by_geofencing($geofencing_data)
	{
		$results = [];
		$this->db->trans_start();
		foreach($geofencing_data as $index => $geofence_data)
		{
			$latitude = floatval($geofence_data['latlng'][0]);
			$longitude = floatval($geofence_data['latlng'][1]);
			$radius = floatval($geofence_data['radius']) / 1000; // Convert to km

			$bindings = array_merge(
				[$longitude, $latitude],
				[$latitude, $longitude, $latitude],
				[$longitude, $latitude, $radius],
				[$longitude, $latitude, $radius],
				[$longitude, $latitude, $radius],
				[$longitude, $latitude, $radius]
			);
			$location_sql =
			"	SELECT
					GROUP_CONCAT(DISTINCT local_id ORDER BY t1.distance ASC SEPARATOR ',') AS zips
				FROM(
					SELECT
						gp.*,
						0 AS distance
					FROM geo_polygons AS gp
					JOIN geo_polygons_point_search AS glltg
						ON glltg.gcd_id = gp.id
					WHERE
						((? * 10000) BETWEEN glltg.min_lng_scaled AND glltg.max_lng_scaled) AND
						((? * 10000) BETWEEN glltg.min_lat_scaled AND glltg.max_lat_scaled)

					UNION ALL

					SELECT
						gp.*,
						(6371 * acos(cos(radians(?)) * cos(radians(Y(gp.center_point))) * cos(radians(X(gp.center_point)) - radians(?)) + sin(radians(?)) * sin(radians(Y(gp.center_point))))) AS distance
						# Based on (CONVERSION_MULTIPLIER * acos(cos(radians(LATITUDE_1)) * cos(radians(LATITUDE_2)) * cos(radians(LONGITUDE_2) - radians(LONGITUDE_1)) + sin(radians(LATITUDE_1)) * sin(radians(LATITUDE_2)))) - https://en.wikipedia.org/wiki/Haversine_formula
					FROM geo_polygons AS gp
					HAVING distance < (10) # Trick to speed up query by limiting points initially
					ORDER BY distance ASC
					LIMIT 10
				) AS t1
				WHERE
					ST_Intersects(ST_Buffer(POINT(?, ?), ? / (111)), COALESCE(polygon_precision_1, polygon_precision_max)) AND # 111 is a rough estimate of degrees to km
					(
						ST_Within(ST_Buffer(POINT(?, ?), ? / (111)), COALESCE(polygon_precision_1, polygon_precision_3, polygon_precision_3, polygon_precision_max)) OR
						ST_Contains(ST_Buffer(POINT(?, ?), ? / (111)), COALESCE(polygon_precision_1, polygon_precision_3, polygon_precision_3, polygon_precision_max)) OR
						ST_Overlaps(ST_Buffer(POINT(?, ?), ? / (111)), COALESCE(polygon_precision_1, polygon_precision_3, polygon_precision_3, polygon_precision_max))
					);
			";
			$results[$index] = $this->db->query($location_sql, $bindings);
		}
		$this->db->trans_complete();

		$affected_regions = [];
		foreach($results as $index => $result)
		{
			if($result->num_rows() > 0)
			{
				$row = $result->row_array();
				$affected_regions[$index] = explode(',', $row['zips']);
			}
		}
		return $affected_regions;
	}

	public function get_regions_and_data_from_campaign_ids_and_date_range($advertiser_ids, $campaign_ids, $start_date, $end_date, $use_impression_cutoff = true)
	{
		if(gettype($advertiser_ids) != 'array' || count($advertiser_ids) < 1)
		{
			return array();
		}
		if(gettype($campaign_ids) != 'array' || count($campaign_ids) < 1)
		{
			return array();
		}
		$advertiser_insert_string = implode(',', array_fill(0, count($advertiser_ids), '?'));
		$campaign_insert_string = implode(',', array_fill(0, count($campaign_ids), '?'));
		$bindings = array_merge($campaign_ids, array($start_date, $end_date), $advertiser_ids);
		$sql =
		"	SELECT
				gcd.local_id AS zcta,
				SUM(zr.`impressions`) AS impressions,
				SUM(COALESCE(zr.clicks, 0)) AS clicks,
				GROUP_CONCAT(DISTINCT(ag.campaign_id) SEPARATOR ',') AS relavant_campaigns
			FROM
				`AdGroups` AS ag
			INNER JOIN
				Campaigns AS c
				ON
					c.id = ag.campaign_id
			INNER JOIN
				Advertisers AS a
				ON
					a.id = c.business_id
			INNER JOIN
				zcta_records AS zr
				ON
					zr.`ad_group_id` = ag.`ID`
			INNER JOIN
				geo_cumulative_demographics AS gcd
				ON
					gcd.id = zr.gcd_id
			WHERE
				ag.`campaign_id` IN ({$campaign_insert_string}) AND
				(zr.date BETWEEN ? AND ?) AND
				a.id IN ({$advertiser_insert_string}) AND
				a.are_heatmaps_enabled > 0
			GROUP BY gcd.local_id
		";
		$result = $this->db->query($sql, $bindings);
		if($result->num_rows() > 0)
		{
			$return_array = array_combine(array_column($result->result_array(), 'zcta'), $result->result_array());
			if($use_impression_cutoff)
			{
				$impression_cutoff = 10000;
				$total_impressions = array_sum(array_column($return_array, 'impressions'));
				$return_array = ($total_impressions >= $impression_cutoff) ? $return_array : array();
			}
			return $return_array;
		}
		return array();
	}

	public function get_mapbox_styles_by_partner_id($partner_id)
	{
		$sql =
		"	SELECT
				*
			FROM
				wl_partner_snapshot_preferences
			WHERE
				partner_id = ?
		";
		$result = $this->db->query($sql, $partner_id);
		if($result->num_rows > 0)
		{
			return $result->row_array();
		}
		return [];
	}

	public function get_mapbox_styles_by_proposal_id($proposal_id)
	{
		$query = "
			SELECT
				prt.map_style,
				prt.use_inverted_map,
				prt.polygon_style
			FROM
				prop_gen_prop_data pgp
				JOIN mpq_sessions_and_submissions mss
					ON pgp.source_mpq = mss.id
				JOIN cp_strategies_join_proposal_templates sjp
					ON mss.strategy_id = sjp.cp_strategies_id
				JOIN proposal_templates prt
					ON sjp.proposal_templates_id = prt.id
			WHERE
				pgp.prop_id = ?";
		$response = $this->db->query($query, $proposal_id);
		if($response->num_rows() > 0)
		{
			return $response->row_array();
		}
		return array();
	}

}

?>
