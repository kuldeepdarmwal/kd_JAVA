<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

function get_mixpanel_data_array($instance, $user = null)
{
	$info_array = array();

	if($user === null)
	{
		$user_id = $instance->tank_auth->get_user_id();
		$user = $instance->tank_auth->get_user_by_id($user_id);
	}

	// User Data
	$info_array['user_unique_id'] = $user->id;
	$info_array['user_firstname'] = $user->firstname;
	$info_array['user_lastname'] = $user->lastname;
	$info_array['user_role'] = strtolower($user->role);
	$info_array['user_email'] = $user->email;
	$info_array['user_is_super'] = ($user->isGroupSuper == 1) ? 'true' : 'false';

	$partner_id = $user->partner_id;
	if($user->role == 'business')
	{
		$partner_id = $instance->tank_auth->get_partner_id_by_advertiser_id($user->advertiser_id);
	}
	$partner_info = $instance->tank_auth->get_partner_info($partner_id);

	// Partner Data
	$info_array['user_partner_id'] = $partner_id;
	$info_array['user_partner'] = $partner_info['partner_name'];
	$info_array['user_cname'] = $partner_info['cname'];

	// Advertiser Data
	$info_array['user_advertiser_id'] = ($user->role == 'business') ? $user->advertiser_id : 'null';
	$info_array['user_advertiser_name'] = ($user->role == 'business') ? $instance->tank_auth->get_business_name_from_id($user->advertiser_id) : 'null';

	// Other data
	$d = new DateTime();
	$info_array['page_access_time'] = $d->format("Y-m-d\TH:i:s");

	return $info_array;
}