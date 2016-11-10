<?php
	header('Content-Type: text/csv'); 
	header('CacheControl: no-cache');
	header('Expires: 0');

	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	$should_skip_cache = strpos($user_agent, 'MSIE 7.0') !== false;
	$should_skip_cache = $should_skip_cache || (strpos($user_agent, 'MSIE 8.0') !== false);
	$should_skip_cache = $should_skip_cache || (strpos($user_agent, 'MSIE 9.0') !== false);
	if(!$should_skip_cache)
	{
		header('Pragma: no-cache');
	}

	header('Content-Disposition: attachment; filename=ad_performance_by_'.$filename_stub.'.csv'); 

	foreach($data_response as $row)
	{
		echo '"'.implode('","', $row).'"'."\r\n";
	}

	exit();
?>
