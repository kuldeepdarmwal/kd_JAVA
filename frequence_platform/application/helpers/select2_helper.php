<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

function select2_helper($model, $method, $post_array = array(), $add_args = NULL)
{
	$response = array(
		'errors' => NULL,
		'results' => array()
	);

	$raw_term = $post_array['q'] ?: '%';
	$page_limit =  $post_array['page_limit'] ? intval($post_array['page_limit'], 10) : 10;
	$page_number = $post_array['page'] ? intval($post_array['page'], 10) : 1;

	$raw_term = ($raw_term == "%") ? "%" : '%'.$raw_term.'%';

	$mysql_page_number = ($page_number - 1) * $page_limit;
	$add_args = (!empty($add_args) && gettype($add_args) == 'array') ? $add_args : array();
	$args = array_merge(array($raw_term, $mysql_page_number, ($page_limit + 1)), $add_args);

	if (method_exists($model, $method))
	{
		$result = call_user_func_array(array($model, $method), $args);
		if ($result)
		{
			$response['results'] = $result;

			if (count($result) == $page_limit + 1)
			{
				$response['real_count'] = $page_limit;
				$response['more'] = true;
			}
			else
			{
				$response['real_count'] = count($result);
				$response['more'] = false;
			}
		}
		else
		{
			$response['errors'] = 'Errno 70002: The query did not return any results';
		}
	}
	else
	{
		$response['errors'] = 'Errno 70001: Method \''. $method .'\' does not exist on model \''. get_class($model) .'\'';
	}

	return $response;
}

?>