<?php  

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

function vl_verify_ajax_call($allowed_roles,$post_variables = array())
{
	$CI =& get_instance();
	$CI->load->library('tank_auth');
	
	//removed $CI->input->is_ajax-request() from this because it does not work with ie9 and below
	if($_SERVER['REQUEST_METHOD'] === 'POST')
	{
		$response = array('is_success' => true, 'errors' => array());

		if($CI->tank_auth->is_logged_in())
		{
			$username = $CI->tank_auth->get_username();
			$role = strtolower($CI->tank_auth->get_role($username));
			if(!in_array($role,$allowed_roles) and !in_array('public',$allowed_roles))
			{
				$response['is_success'] = false;
				$response['errors'][] = $role.' not authorized';
			}
		}
		else
		{
			if(!in_array('public',$allowed_roles))
			{
				$response['is_success'] = false;
				$response['errors'][] = "public not authorized";
			}
		}

		//test each required post variable - if doesn't exist - ajax fail
		foreach($post_variables as $post_variable)
		{
			$response['post'][$post_variable] = $CI->input->post($post_variable);
			if($response['post'][$post_variable] === false)
			{
				$response['errors'][] = 'post variable: `'.$post_variable.'` not found';
				$response['is_success'] = false;
			}
		}
		
		return $response;
	}
	else
	{
		show_404();
	}
}

function vl_verify_ajax_post_variables($post_variables)
{
	$CI =& get_instance();
	
	//removed $CI->input->is_ajax-request() from this because it does not work with ie9 and below
	if($_SERVER['REQUEST_METHOD'] === 'POST')
	{
		$response = array('is_success' => true, 'errors' => array());

		//test each required post variable - if doesn't exist - ajax fail
		foreach($post_variables as $post_variable)
		{
			$response['post'][$post_variable] = $CI->input->post($post_variable);
			if($response['post'][$post_variable] === false)
			{
				$response['errors'][] = 'post variable: `'.$post_variable.'` not found';
				$response['is_success'] = false;
			}
		}
		
		return $response;
	}
	else
	{
		show_404();
	}
}

?>
