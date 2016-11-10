<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

abstract class spectrum_traffic_system_names_from_carmercial
{
	const central_pacific = 'central_pacific';
	const mid_north = 'mid_north';
	const southeast = 'southeast';
}

abstract class spectrum_traffic_system_names
{
	const central_pacific = 'Central Pacific';
	const mid_north = 'Mid North';
	const southeast = 'Southeast';
	const bright_house = 'BH';

	const unknown = 'Unknown';
}

abstract class spectrum_traffic_system_ids
{
	const central_pacific = 101;
	const mid_north = 102;
	const southeast = 103;
	const bright_house = 104;

	const unknown = 100;
}

function get_sql_case_mapping_spectrum_traffic_system_name_to_id($case_field_parameter_name)
{
	return " CASE $case_field_parameter_name
			WHEN '".spectrum_traffic_system_names::bright_house."' THEN ".spectrum_traffic_system_ids::bright_house."
			WHEN '".spectrum_traffic_system_names::southeast."' THEN ".spectrum_traffic_system_ids::southeast."
			WHEN '".spectrum_traffic_system_names::mid_north."' THEN ".spectrum_traffic_system_ids::mid_north."
			WHEN '".spectrum_traffic_system_names::central_pacific."' THEN ".spectrum_traffic_system_ids::central_pacific."
			ELSE ".spectrum_traffic_system_ids::unknown."
		END
	";
}
