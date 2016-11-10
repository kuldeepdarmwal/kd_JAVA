<?php
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

	$file_printed = array();
	foreach($tags as $row)
	{
		if(array_key_exists($row['name'], $file_printed))
		{
			continue;
		}
		
		$file_printed[$row['name']] = true;
		$extension = ".js";
		
		if (strpos($row['name'],".js") > 0)
		{
			$extension = "";
		}
		
		$htmlStringMsg1 = '
		<!-- Place in the <body> section of webpage -->';
		$htmlStringMsg2 = 
		'<!-- Privacy policy at tag.brandcdn.com/privacy -->';
		$htmlStringMsg3 = 
		'<script type="text/javascript" src="//tag.brandcdn.com/autoscript/'.$row['name'].$extension.'"></script>';
		//'<script type="text/javascript" src="'.normal_url().'autoscript/'.$row['name'].'.js"></script>'; 

		echo htmlentities($htmlStringMsg1).'<br/>';
		echo htmlentities($htmlStringMsg2).'<br/>';
		echo htmlentities($htmlStringMsg3);
                                         
        }

?> 