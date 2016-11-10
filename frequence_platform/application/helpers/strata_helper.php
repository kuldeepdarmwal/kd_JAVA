<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

function format_tv_schedule($xml, $selected_networks = false, $demographics = false)
{
	if (is_file($xml))
	{
		$strata_schedule = simplexml_load_file($xml);
	}
	else
	{
		$strata_schedule = new SimpleXMLElement($xml);
	}

	$schedule = array(
		'geos' => array(),
		'title' => (string) $strata_schedule->document->name,
		'networks' => array(),
		'daily_inventory' => array(),
		'start_date' => (string) $strata_schedule->campaign->dateRange->startDate,
		'end_date' => (string) $strata_schedule->campaign->dateRange->endDate,
		'weeks' => array()
	);
	foreach($strata_schedule->campaign->order->systemOrder->weeks->week as $week)
	{
		$schedule['weeks'][] = (string) $week->attributes()->startDate;
	}

	$days_of_week = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');

	$hours_of_day = array();
	for ($i = 0; $i < 24; $i++)
	{
		$hours_of_day[$i] = 0;
	}

	foreach ($days_of_week as $day)
	{
		$schedule['daily_inventory'][$day] = $hours_of_day;
	}
	$schedule['hourly_inventory'] = $hours_of_day;

	$max_duration = 0;
	$total_weeks_array = array();
	foreach($strata_schedule->campaign->order->systemOrder as $i => $raw_geo)
	{
		$geo_totals = get_object_vars($raw_geo->totals);
		foreach($raw_geo->weeks->week as $week_object)
		{
			if(!in_array((string)$week_object->attributes()->startDate, $total_weeks_array))
			{
				$total_weeks_array[] = (string)$week_object->attributes()->startDate;
			}
		}
		$geo = array(
			'name' => (string) $raw_geo->system->name,
			'syscode' => (string) $raw_geo->system->syscode,
			'weeks' => count($raw_geo->weeks->week),
			'dollars' => floatval($geo_totals['cost']),
			'spots' => $geo_totals['spots'],
			'schedule' => array()
		);

		foreach($raw_geo->detailLine as $buy)
		{
			$network = (string) $buy->network->ID[0]->code;

			$network_index = array_search($network, array_column($schedule['networks'], 'name'));
			if ($network_index === false)
			{
				$network_index = count($schedule['networks']);
				$schedule['networks'][$network_index] = empty_network_array($network);
				$schedule['networks'][$network_index]['zones'][] = $geo['name'];
			} else {
				if (array_search($geo['name'], $schedule['networks'][$network_index]['zones']) === false)
				{
					$schedule['networks'][$network_index]['zones'][] = $geo['name'];
				}
			}

			// Daily/Hourly schedule
			$start = idate('H', strtotime($buy->startTime));
			$end = idate('H', strtotime($buy->endTime));
			if ($start === $end) // schedule is only for 1 hour
			{
				$end++;
			}

			$num_hours = $end === 0 ? 24 - $start : $end - $start;
			$avg_spots = $buy->totals->spots / $num_hours;

			$daily_schedule = get_object_vars($buy->dayOfWeek);

			$num_days = array_reduce($daily_schedule, function($total, $day) {
				return $day == 'Y' ? ++$total : $total;
			}, 0);

			if ($num_days > 0)
			{
				$avg_spots /= $num_days;
			}

			for ($i = $start; $i < $end || $i < 24; $i++)
			{
				$schedule['hourly_inventory'][$i] += $avg_spots;
			}

			$dayRange = array(false, false);

			foreach($days_of_week as $day)
			{
				if ($daily_schedule[$day] == 'Y')
				{
					for ($i = $start; $i < $end || $i < 24; $i++)
					{
						$schedule['daily_inventory'][$day][$i] += $avg_spots;
					}

					if (!$dayRange[0])
					{
						$dayRange[0] = $day;
					}
					$dayRange[1] = $day;
				}
			}


			$network_schedule = array(
				'spots' => 0,
				'weeks' => count($buy->spot),
				'start' => date('ga', strtotime($buy->startTime)),
				'end' => date('ga', strtotime($buy->endTime)),
				'days' => implode($dayRange, '-'),
				'network' => $network,
				'logo' => "https://s3.amazonaws.com/brandcdn-assets/images/tv_network_logos/$network.png"
			);

			foreach ($buy->spot as $spot)
			{
				$schedule['networks'][$network_index]['spots'] += floatval($spot->quantity);
				$schedule['networks'][$network_index]['cost'] += floatval($buy->spotCost) * floatval($spot->quantity);
				$network_schedule['spots'] += floatval($spot->quantity);
			}

			$network_schedule['avg_spots'] = round($network_schedule['spots'] / $network_schedule['weeks']) ?: 1;

			$geo['schedule'][] = $network_schedule;
		}

		$schedule['geos'][] = $geo;
	}

	$max_duration = count($total_weeks_array);
	usort($schedule['networks'], function($a, $b) {
		return $a['spots'] == $b['spots'] ? 0 : ( $a['spots'] > $b['spots'] ) ? -1 : 1;
	});

	$schedule['budget'] = array(
		'weeks' => $max_duration,
		'dollars' => (int) $strata_schedule->campaign->order->totals->cost,
		'spots' => (int) $strata_schedule->campaign->order->totals->spots
	);

	$schedule['errors'] = validate_tv_schedule($schedule);
	return $schedule;
}

function validate_tv_schedule($schedule)
{
	$errors = [];
	return $errors;
}

function empty_network_array($network) {
	return array(
		'spots' => 0,
		'cost' => 0,
		'zones' => array(),
		'name' => $network,
		'logo' => "https://s3.amazonaws.com/brandcdn-assets/images/tv_network_logos/$network.png"
	);
}