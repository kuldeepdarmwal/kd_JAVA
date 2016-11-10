<?php  

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

function convert_demo_category_if_all_unset($demographics)
{
	$demo_array = explode('_', $demographics);

	if($demo_array[0] == '0' AND $demo_array[1] == '0')
	{
		$demo_array[0] = '1'; //male
		$demo_array[1] = '1'; //female
	}
	if($demo_array[2] == '0' AND $demo_array[3] == '0' AND $demo_array[4] == '0' AND $demo_array[5] == '0' AND $demo_array[6] == '0' AND $demo_array[7] == '0' AND $demo_array[8] == '0')
	{
		$demo_array[2] = '1'; //under 18
		$demo_array[3] = '1'; //18-24
		$demo_array[4] = '1'; //25-34
		$demo_array[5] = '1'; //35-44
		$demo_array[6] = '1'; //45-54
		$demo_array[7] = '1'; //55-64
		$demo_array[8] = '1'; //over 65
	}
	if($demo_array[9] == '0' AND $demo_array[10] == '0' AND $demo_array[11] == '0' AND $demo_array[12] == '0')
	{
		$demo_array[9]  = '1'; //under 50k
		$demo_array[10] = '1'; //50k-100k
		$demo_array[11] = '1'; //100k-150k
		$demo_array[12] = '1'; //over 150k
	}
	if($demo_array[13] == '0' AND $demo_array[14] == '0' AND $demo_array[15] == '0')
	{
		$demo_array[13] = '1'; //no college
		$demo_array[14] = '1'; //college
		$demo_array[15] = '1'; //grad school
	}
	if($demo_array[16] == '0' AND $demo_array[17] == '0')
	{
		$demo_array[16] = '1'; //no kids
		$demo_array[17] = '1'; //has kids
	}

	$demographics = implode('_', $demo_array);
	return $demographics;
}