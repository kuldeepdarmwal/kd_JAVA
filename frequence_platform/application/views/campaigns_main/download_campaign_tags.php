<?php
	header('Content-type: text/plain');
	header('Content-Disposition: attachment; filename="'.$file_name.'"');
	if (!defined('PHP_EOL')) {
		switch (strtoupper(substr(PHP_OS, 0, 3))) {
			// Windows
			case 'WIN':
				define('PHP_EOL', "\r\n");
				break;
			// Mac
			case 'DAR':
				define('PHP_EOL', "\r");
				break;
			// Unix
			default:
				define('PHP_EOL', "\n");
			}
		}
			
	$base_url = base_url();
	$file_printed = array();
	foreach($tags as $row)
	{
		if(array_key_exists($row['name'], $file_printed))
		{
			continue;
		}
		
		$file_printed[$row['name']] = true;
		$file_extension = "";
		if (strpos($row['name'],".js") <= 0)
		{
			$file_extension = ".js";
		}
		
		$htmlStringMsg1 = 
		'<!-- Tracking tag. Place in the <body> section of webpage -->';
		$htmlStringMsg2 = 
		'<!-- Privacy policy at http://tag.brandcdn.com/privacy -->';
		$htmlStringMsg3 = 
		'<script type="text/javascript" src="//tag.brandcdn.com/autoscript/'.$row['name'].$file_extension.'"></script>'."\r\n"."\r\n";
		echo $htmlStringMsg1."\r\n";
		echo $htmlStringMsg2."\r\n";
		echo $htmlStringMsg3;
	}

?>
