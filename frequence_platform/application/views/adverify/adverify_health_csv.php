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

	header('Content-Disposition: attachment; filename='.$filename); 
	$headers = array_keys($csv_data[0]);
	echo '"'.implode('","', $headers).'"'."\r\n";
	
	foreach($csv_data as $row)
	{
		echo '"'.implode('","', $row).'"'."\r\n";
	}

	exit();
?>
