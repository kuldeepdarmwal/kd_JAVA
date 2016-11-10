<?php
	$tags_cnt = 0;
	
	foreach ($ad_tags_result as $row)
	{
		$comment_for_ad_tag = "<!-- ";
		$name_found = false;
		$ad_tag = $row['ad_tag'];
		
		
		if ($tags_cnt == 0)
		{
			if (isset($row['name']) && $row['name'] != null && $row['name'] != "")
			{
				$file_name = "ad_tags-".$row['name']."-".$row['version_id'].".txt";
			}
			
			header('Content-type: text/plain');
			header('Content-Disposition: attachment; filename="'.$file_name.'"');
			
			if (!defined('PHP_EOL'))
			{
				switch (strtoupper(substr(PHP_OS, 0, 3)))
				{
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
		}
		
		$tags_cnt++;
		
		if (!empty($row['published_ad_server']) && $row['published_ad_server'] == 3)
		{
			$ad_tag = str_replace("fas_candu=", "fas_candu=%%CLICK_URL_ESC%%", $ad_tag);
			$ad_tag = str_replace("fas_c=", "fas_c=%%CLICK_URL_ESC%%", $ad_tag);
			$ad_tag = str_replace("fas_candu_for_js=\"", "fas_candu_for_js=\"%%CLICK_URL_ESC%%", $ad_tag);
			$ad_tag = str_replace("fas_c_for_js=\"", "fas_c_for_js=\"%%CLICK_URL_ESC%%", $ad_tag);
			$ad_tag = str_replace("<NOSCRIPT><A HREF=\"", "<NOSCRIPT><A HREF=\"%%CLICK_URL_ESC%%", $ad_tag);
		}
		
		if (isset($row['name']) && $row['name'] != null && $row['name'] != "")
		{
			$comment_for_ad_tag = $comment_for_ad_tag.$row['name'];
			$name_found = true;
		}
		
		if (isset($row['version_id']) && $row['version_id'] != null)
		{
			if (!$name_found)
			{
				$comment_for_ad_tag = $comment_for_ad_tag.$row['version_id'];
			}
			else
			{
				$comment_for_ad_tag = $comment_for_ad_tag." : ".$row['version_id'];
			}
		}
		
		if (isset($row['size']) && $row['size'] != null)
		{
			$comment_for_ad_tag = $comment_for_ad_tag." : ".$row['size'];
		}
		
		$comment_for_ad_tag = $comment_for_ad_tag." -->";		
		echo $comment_for_ad_tag."\r\n".$ad_tag."\r\n\n";
	}