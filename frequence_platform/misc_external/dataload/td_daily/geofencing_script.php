<?php
	require_once __DIR__ . '/../../../config.php';

	// Parameters include the raw table name
	if(!isset($argv[1]))
	{
		die("Raw table name needed to run geofence script\n");
	}
	$raw_table = $argv[1];

	$links = connect_to_dbs();

	$adgroup_ids = get_adgroup_ids_for_geofencing($links['main']);
	if($adgroup_ids)
	{
		$affected_impressions = get_impressions_for_geofencing($links['raw'], $raw_table, $adgroup_ids);
		if($affected_impressions)
		{
			$num_successful_impressions = fix_affected_impressions($links['raw'], $raw_table, $affected_impressions);
			if($num_successful_impressions)
			{
				$message = "{$num_successful_impressions} impressions geofenced";
				$stragglers_left = get_straggling_impressions($links['raw'], $raw_table, $adgroup_ids);
				if($stragglers_left)
				{
					$num_stragglers = count($stragglers_left);
					$fixed_stragglers = fix_straggling_impressions($links['raw'], $raw_table, $stragglers_left);
					$message .= ($fixed_stragglers) ? ", including {$fixed_stragglers} fixed stragglers" : ", {$num_stragglers} impressions couldn't be fixed";
				}
				echo "{$message}\n";
			}
			else
			{
				echo "Region mapping has erred; not all regions were fixed\n";
			}
		}
		else
		{
			echo "No impressions needed geofencing\n";
		}
	}
	else
	{
		echo "No adgroups needed geofencing\n";
	}

	close_db_connections($links);

	function fix_affected_impressions(&$raw_db, $raw_table, $affected_impressions)
	{
		$fix_query = '';
		$main_db = DB_DATABASE;

		foreach($affected_impressions as $impression)
		{
			$log = $impression['LogEntryTime'];
			$imp = $impression['VantageLocalId'];
			$lat = floatval($impression['Latitude']);
			$lng = floatval($impression['Longitude']);

			$fix_query .=
			"	UPDATE {$raw_table} SET
					geofence_gcd_id = COALESCE((SELECT gcd_id FROM geo_lat_lngs_to_gcd WHERE ST_Contains(polygon, POINT({$lng}, {$lat}))), 0),
					geofence_place_id = (
						SELECT
							IFNULL
							(
								(
									SELECT geo_id
									FROM geo_lat_lngs_to_city_state
									WHERE ST_Contains(`polygon`, POINT({$lng}, {$lat}))
								),
								IFNULL(
									(
										SELECT gllcs.geo_id
										FROM geo_lat_lngs_to_gcd gllg
										LEFT JOIN {$main_db}.geo_zcta_map gzm
											ON gzm.gcd_id = gllg.gcd_id
										LEFT JOIN {$main_db}.`geo_zcta_to_place` AS gztp
											ON gztp.`zcta_int` = gzm.num_id
										LEFT JOIN geo_lat_lngs_to_city_state gllcs
											ON gllcs.geo_id = gztp.`geoid_int`
										WHERE
											ST_Contains(gllg.`polygon`, POINT({$lng}, {$lat}))
										ORDER BY
											ST_Distance(POINT({$lng}, {$lat}), gllcs.`polygon`)
										LIMIT 1
									),
									(
										SELECT geo_id
										FROM geo_lat_lngs_to_city_state
										ORDER BY
											ST_Distance(POINT({$lng}, {$lat}), `polygon`)
										LIMIT 1
									)
								)
							) AS city_id
					)
				WHERE
					LogEntryTime = '{$log}' AND
					VantageLocalId = '{$imp}';
			";
		}

		if($raw_db->multi_query($fix_query))
		{
			$num_rows_affected = 0;
			do
			{
				$num_rows_affected += $raw_db->affected_rows;
			}
			while ($raw_db->more_results() && $raw_db->next_result());

			if(count($affected_impressions) === $num_rows_affected)
			{
				return $num_rows_affected;
			}
		}
		else
		{
			die("Multi query failed: ({$raw_db->errno}) {$raw_db->error}\n");
		}
		return false;
	}

	function fix_straggling_impressions(&$raw_db, $raw_table, $straggling_impressions)
	{
		$fix_query = '';

		foreach($straggling_impressions as $impression)
		{
			$log = $impression['LogEntryTime'];
			$imp = $impression['VantageLocalId'];
			$lat = floatval($impression['Latitude']);
			$lng = floatval($impression['Longitude']);

			$fix_query .=
			"	UPDATE {$raw_table} SET
					geofence_gcd_id = (SELECT gcd_id FROM geo_lat_lngs_to_gcd ORDER BY ST_Distance(POINT({$lng}, {$lat}), polygon) LIMIT 1)
				WHERE
					LogEntryTime = '{$log}' AND
					VantageLocalId = '{$imp}';
			";
		}

		if($raw_db->multi_query($fix_query))
		{
			$num_rows_affected = 0;
			do
			{
				$num_rows_affected += $raw_db->affected_rows;
			}
			while ($raw_db->more_results() && $raw_db->next_result());

			if(count($straggling_impressions) === $num_rows_affected)
			{
				return $num_rows_affected;
			}
		}
		else
		{
			die("Multi query failed: ({$raw_db->errno}) {$raw_db->error}\n");
		}
		return false;
	}

	function get_impressions_for_geofencing(&$raw_db, $raw_table, $adgroups)
	{
		$adgroups_string = implode(',', array_map(function($adgroup){
			return "'{$adgroup}'";
		}, $adgroups));

		$impression_sql =
		"	SELECT
				LogEntryTime,
				VantageLocalId,
				Latitude,
				Longitude
			FROM
				{$raw_table}
			WHERE
				AdGroupId IN ({$adgroups_string}) AND
				Latitude IS NOT NULL AND
				Longitude IS NOT NULL AND
				geofence_gcd_id = 0 AND
				geofence_place_id = 0
		";
		$result = $raw_db->query($impression_sql);
		if($result && $result->num_rows > 0)
		{
			$affected = [];
			while($row = $result->fetch_assoc())
			{
				$affected[] = $row;
			}
			$result->close();
			return $affected;
		}
		return false;
	}

	function get_straggling_impressions(&$raw_db, $raw_table, $adgroups)
	{
		$adgroups_string = implode(',', array_map(function($adgroup){
			return "'{$adgroup}'";
		}, $adgroups));

		$impression_sql =
		"	SELECT
				LogEntryTime,
				VantageLocalId,
				Latitude,
				Longitude
			FROM
				{$raw_table}
			WHERE
				AdGroupId IN ({$adgroups_string}) AND
				Latitude IS NOT NULL AND
				Longitude IS NOT NULL AND
				geofence_gcd_id = 0
		";
		$result = $raw_db->query($impression_sql);
		if($result && $result->num_rows > 0)
		{
			$affected = [];
			while($row = $result->fetch_assoc())
			{
				$affected[] = $row;
			}
			$result->close();
			return $affected;
		}
		return false;
	}

	function get_adgroup_ids_for_geofencing(&$main_db)
	{
		//Get adgroups that have the type TDGF
		$adgroup_sql =
		"	SELECT
				ID AS adgroup_id
			FROM
				AdGroups
			WHERE
				Source = 'TDGF'
		";
		$result = $main_db->query($adgroup_sql);
		if($result && $result->num_rows > 0)
		{
			$ids = [];
			while($row = $result->fetch_assoc())
			{
				$ids[] = $row['adgroup_id'];
			}
			$result->close();
			return $ids;
		}
		return false;
	}

	function close_db_connections(&$links)
	{
		foreach($links as &$link)
		{
			$link->close();
		}
	}

	function connect_to_dbs()
	{
		$links = [];
		$links['main'] = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
		if($links['main']->connect_errno)
		{
			die("Connect Error ({$links['main']->connect_errno}) {$links['main']->connect_error}\n");
		}

		$links['raw'] = new mysqli(TD_DB_HOSTNAME, TD_DB_USERNAME, TD_DB_PASSWORD, TD_DB_DATABASE);
		if($links['raw']->connect_errno)
		{
			die("Connect Error ({$links['raw']->connect_errno}) {$links['raw']->connect_error}\n");
		}
		return $links;
	}
?>