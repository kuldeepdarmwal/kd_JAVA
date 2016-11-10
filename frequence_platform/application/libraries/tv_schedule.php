<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

function format_tv_schedule($xml)
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
		'hourly_inventory' => array()
	);

	for ($i = 0; $i < 24; $i++)
	{
		$schedule['hourly_inventory'][$i] = 0;
	}

	$max_duration = 0;

	foreach($strata_schedule->campaign->order->systemOrder as $i => $raw_geo)
	{
		$geo = array(
			'name' => (string) $raw_geo->system->name,
			'syscode' => (string) $raw_geo->system->syscode,
			'weeks' => count($raw_geo->weeks->week),
			'dollars' => round($raw_geo->totals->cost / count($raw_geo->weeks->week)),
			'spots' => round($raw_geo->totals->spots / count($raw_geo->weeks->week)),
			'schedule' => array()
		);

		foreach($raw_geo->weeks->week as $week)
		{
			$week_number = (string) $week['number'];
			if (!array_search($week_number, array_column($geo['schedule'], 'week_number')))
			{
				$geo['schedule'][] = array(
					'week_number' => $week_number,
					'start_date' => (string) $week['startDate'],
					'end_date' => date('Y-m-d', strtotime("+6 day", strtotime((string) $week['startDate']))),
					'networks' => array()
				);
			}
		}

		foreach($raw_geo->detailLine as $buy)
		{
			$network = (string) $buy->network->ID[0]->code;

			$network_index = array_search($network, array_column($schedule['networks'], 'name'));
			if ($network_index === false)
			{
				$network_index = count($schedule['networks']);
				$schedule['networks'][$network_index] = empty_network_array($network);
			}

			$schedule['networks'][$network_index]['zones']++;

			foreach ($buy->spot as $spot)
			{

				$schedule['networks'][$network_index]['spots'] += floatval($spot->quantity);
				$schedule['networks'][$network_index]['cost'] += floatval($buy->spotCost) * floatval($spot->quantity);

				$week_number = (int) $spot->weekNumber;
				$week_key = array_search($week_number, array_column($geo['schedule'], 'week_number'));
				$geo['schedule'][$week_key]['networks'][] = array(
					'name' => $network,
					'start_date' => $geo['schedule'][$week_key]['start_date'],
					'end_date' => $geo['schedule'][$week_key]['end_date'],
					'spots' => floatval($spot->quantity),
					'rate' => floatval($buy->spotCost),
					'cost' => floatval($buy->spotCost) * floatval($spot->quantity)
				);
			}
		}

		if (count($geo['schedule']) > $max_duration)
		{
			$max_duration = count($geo['schedule']);
		}

		$schedule['geos'][] = $geo;
	}

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
		'zones' => 0,
		'name' => $network,
		'logo' => "//s3-us-west-1.amazonaws.com/reports-tv-data/network_icons/$network.png"
	);
}