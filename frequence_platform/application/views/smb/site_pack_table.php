<?php
echo '<!DOCTYPE html>';
?>
<html>
<head>
	
	<title>Media Targeting</title>
	<link rel="stylesheet" type="text/css" href="<?php echo base_url('css/smb/media_targeting.css'); ?>"/>   
	
<script type="text/javascript">
   
</script>

</head>
<body>
<table id="site_pack_table" name="site_pack_table" class="mt_site_pack_table" onLoad="slideIn(null);">
	<tr>
		<td class="mt_site_pack_header" colspan="4">
		SITE LIST
		</td>
	</tr>
	<tr class="mt_column_headers">
		<td class="mt_other_headers">
			DOMAIN
		</td>
		<td class="mt_col1 mt_other_headers">
			LOCAL REACH
		</td>
		<td class="mt_other_headers mt_col2">
			CHANNEL
		</td>
	</tr>
<?php
$numDemoRows = $demoResponse->num_rows();
$siteLimit = 0;
if ($numDemoRows > 14) {
    $siteLimit = 14;
} else {
    $siteLimit = $numDemoRows;
}
/*
echo '<tr>
<td colspan="4">
'.$numDemoRows.'
</td>
</tr>';
*/

for($ii=0; $ii<$siteLimit; $ii++)
{
	$demoRow = $demoResponse->row($ii);
	$reachPopulation = $demoRow->local_reach;
	if($population > 0)
	{
		$reachPopulation = 100 * ceil($demoRow->local_reach * $population / 100.0);
		if($reachPopulation == 0)
		{
			$reachPopulation = 100;
		}
	}

	echo '
	<tr class="mt_site_pack_row" onClick="
        parent.document.getElementById(\'notes_header\').innerHTML=\'\';
        parent.document.getElementById(\'sliderBodyContent\').innerHTML= \'\';
        	if(parent.$(\'div.gridRight\').css(\'marginLeft\') == \'-600px\'){
                    parent.$(\'div.gridRight\').css(\'display\', \'inline\');
		    parent.$(\'div.gridRight\').animate({ marginLeft: \'0px\' });
                } else {
		slideIn(slideOut);	
                }
		if(is_planner_showing)
                {
                toggle_notes();
                }
		$(\'tr.mt_site_pack_row\').attr(\'style\', \'background-color:;\');
			$(\'tr.mt_site_pack_row\').attr(\'style\', \'color: #594f4f;\');
		$(this).css(\'background-color\', \'#ED7523\');
                
                $(\'td.mt_col0\').css(\'background-color\', \'#EAEAEA\');
                $(\'td.mt_col1\').css(\'background-color\', \'#F6F6F6\');
                $(\'td.mt_col2\').css(\'background-color\', \'#F6F6F6\');
               

               this.childNodes[1].style.backgroundColor=\'#ED7523\';
                this.childNodes[3].style.backgroundColor=\'#ED7523\';
                this.childNodes[5].style.backgroundColor=\'#ED7523\';
                
                
		$(this).css(\'color\', \'#fbfbfb\');
		
						
if(parent.document.getElementById(\'website\') !== null) {
parent.document.getElementById(\'website\').parentNode.removeChild(parent.document.getElementById(\'website\'));
}
							
		var webpageDiv = parent.document.createElement(\'div\');
		webpageDiv.setAttribute(\'id\', \'website\');
		webpageDiv.setAttribute(\'class\', \'adPageDiv\');

		var webpageWrapper = parent.document.createElement(\'div\');
		webpageWrapper.setAttribute(\'class\', \'adPageLinkWrapper\');

		var webpageCrop = parent.document.createElement(\'div\');
		webpageCrop.setAttribute(\'class\', \'adPageLinkCrop\');

		var webLink = parent.document.createElement(\'a\');
		webLink.setAttribute(\'href\', \'http://\'+this.childNodes[1].textContent);
		webLink.setAttribute(\'target\', \'_blank\');
		webLink.setAttribute(\'style\', \'width:100%\');
				
		var webpage = parent.document.createElement(\'img\');
		webpage.setAttribute(\'id\', \'webpage\');
		webpage.setAttribute(\'src\', \'http://creative.vantagelocal.com/adlibrary/webpage_thumbnails/\'+this.childNodes[1].textContent+\'.png\');
    webpage.setAttribute(\'onerror\', \'this.src=\\\'/images/webpage_thumbnails/loading.gif\\\'\');
		webpage.setAttribute(\'class\', \'adPageLinkImage\');
				
		var dropShadow = parent.document.createElement(\'img\');
		dropShadow.setAttribute(\'id\', \'dropShadow\');
		dropShadow.setAttribute(\'src\', \'/images/site_placement_shadow.png\');
		dropShadow.setAttribute(\'class\', \'adPageLinkShadow\');

		webLink.appendChild(webpage);
		webpageCrop.appendChild(webLink);
		webpageWrapper.appendChild(webpageCrop);
		webpageWrapper.appendChild(dropShadow);
		webpageDiv.appendChild(webpageWrapper);
				
		var webpageInfoWrapper = parent.document.createElement(\'div\');
		webpageInfoWrapper.setAttribute(\'class\', \'adPageInfoWrapper\');

		var webpageInfo = parent.document.createElement(\'div\');
		webpageInfo.setAttribute(\'class\', \'adPageInfo\');
				
		var webpageSite = parent.document.createElement(\'div\');	
		webpageSite.setAttribute(\'style\', \'display:none; float:left; font-family:BebasNeue; color:#414142; font-size:24px; margin-left:30px; margin-top:0px; margin-right:20px;\');
		webpageSite.innerHTML = this.childNodes[1].textContent+\'<br><span>Site Name</span>\';
		webpageSite.childNodes[2].setAttribute(\'style\', \'font-size:14px; color:#414142;\');
				
				var webpageReach = parent.document.createElement(\'div\');	
		webpageReach.setAttribute(\'style\', \'display:inline; float:left; font-family:BebasNeue; color:#414142; font-size:23px; margin-top:0px; margin-left:10px; margin-right:60px;\');
		webpageReach.setAttribute(\'id\', \'local_reach\'); 
                webpageReach.innerHTML = this.childNodes[3].textContent+\'<br><span>Local Reach</span>\';
		webpageReach.childNodes[2].setAttribute(\'style\', \'font-size:14px; color:#414142;\');
				
				var webpageChannel = parent.document.createElement(\'div\');	
                webpageChannel.setAttribute(\'id\', \'channel_header\'); 
		webpageChannel.setAttribute(\'style\', \'display:inline; float:left; font-family:BebasNeue; color:#414142; font-size:23px;margin-top:0px; margin-right:20px;\');
		webpageChannel.innerHTML = this.childNodes[5].textContent.split(\'>\')[0]+\'<br><span>Channel</span>\';
		webpageChannel.childNodes[2].setAttribute(\'style\', \'font-size:14px; color:#414142;\');

		var webpageBorder = parent.document.createElement(\'div\');
		webpageBorder.setAttribute(\'class\', \'adPageDivider\');
				
		webpageInfo.appendChild(webpageSite);
		webpageInfo.appendChild(webpageReach);
		webpageInfo.appendChild(webpageChannel);
		webpageInfoWrapper.appendChild(webpageInfo);
		webpageInfoWrapper.appendChild(webpageBorder);
				
		webpageDiv.appendChild(webpageInfoWrapper);
		
		
		parent.document.getElementById(\'sliderBodyContent\').appendChild(webpageDiv);
				parent.document.getElementById(\'siteSpan\').innerHTML= this.childNodes[1].textContent;

		var demoDiv = parent.document.createElement(\'div\');
		demoDiv.setAttribute(\'id\', \'site_pack_demographics_iframe\');
		demoDiv.setAttribute(\'class\', \'site_pack_demographics_iframe\');
		var demoIFrame = parent.document.createElement(\'iframe\');
		demoIFrame.setAttribute(\'src\', \'/smb/get_media_targeting_demographics_by_site/\'+ this.childNodes[1].textContent);
		demoIFrame.setAttribute(\'width\', \'90%\');
		demoIFrame.setAttribute(\'height\', \'350px\');
		demoIFrame.setAttribute(\'id\', \'demographics_iframe\');
		demoIFrame.setAttribute(\'class\', \'demographics_iframe\');
		demoIFrame.setAttribute(\'frameborder\', \'0\');
		demoIFrame.setAttribute(\'scrolling\', \'no\');

		demoDiv.appendChild(demoIFrame);
		parent.document.getElementById(\'sliderBodyContent\').appendChild(demoDiv);
						
		">
		<td class="mt_col0">
			<a target="_blank" href="http://'.$demoRow->domain.'">'.$demoRow->domain.'</a>
		</td>
		<td class="mt_col1">
			'.number_format($reachPopulation).'
		</td>
		<td class="mt_col2">
			'.$demoRow->channel.'
		</td>
	</tr>
	';
}
?>
	

<?php
$numChannelRows = $channelResponse->num_rows();
$channelLimit = 0;
if ($numChannelRows > 14) {
    $channelLimit = 14;
} else {
    $channelLimit = $numChannelRows;
}
for($ii=0; $ii<$channelLimit; $ii++)
{
	$channelRow = $channelResponse->row($ii);
	$reachPopulation = $channelRow->Reach;
	if($population > 0)
	{
		$reachPopulation = 100 * round($channelRow->Reach * $population / 100.0);
		if($reachPopulation == 0)
		{
			$reachPopulation = 100;
		}
	}

	echo '
	<tr class="mt_site_pack_row" onClick="
        	if(parent.$(\'div.gridRight\').css(\'marginLeft\') == \'-600px\'){
                    parent.$(\'div.gridRight\').css(\'display\', \'inline\');
		    parent.$(\'div.gridRight\').animate({ marginLeft: \'0px\' });
                } else {
		slideIn(slideOut);	
                }
		dismiss_notes();
		$(\'tr.mt_site_pack_row\').attr(\'style\', \'background-color:;\');
			$(\'tr.mt_site_pack_row\').attr(\'style\', \'color: #594f4f;\');
		$(this).css(\'background-color\', \'#ED7523\');
                
                $(\'td.mt_col0\').css(\'background-color\', \'#EAEAEA\');
                $(\'td.mt_col1\').css(\'background-color\', \'#F6F6F6\');
                $(\'td.mt_col2\').css(\'background-color\', \'#F6F6F6\');
               

               this.childNodes[1].style.backgroundColor=\'#ED7523\';
                this.childNodes[3].style.backgroundColor=\'#ED7523\';
                this.childNodes[5].style.backgroundColor=\'#ED7523\';
                
                
		$(this).css(\'color\', \'#fbfbfb\');
		
				
if(parent.document.getElementById(\'website\') !== null) {
parent.document.getElementById(\'website\').parentNode.removeChild(parent.document.getElementById(\'website\'));
}
							
		var webpageDiv = parent.document.createElement(\'div\');
		webpageDiv.setAttribute(\'id\', \'website\');
		webpageDiv.setAttribute(\'class\', \'adPageDiv\');

		var webpageWrapper = parent.document.createElement(\'div\');
		webpageWrapper.setAttribute(\'class\', \'adPageLinkWrapper\');

		var webpageCrop = parent.document.createElement(\'div\');
		webpageCrop.setAttribute(\'class\', \'adPageLinkCrop\');

		var webLink = parent.document.createElement(\'a\');
		webLink.setAttribute(\'href\', \'http://\'+this.childNodes[1].textContent);
		webLink.setAttribute(\'target\', \'_blank\');
		webLink.setAttribute(\'style\', \'width:100%\');
				
		var webpage = parent.document.createElement(\'img\');
		webpage.setAttribute(\'id\', \'webpage\');
		webpage.setAttribute(\'src\', \'http://creative.vantagelocal.com/adlibrary/webpage_thumbnails/\'+this.childNodes[1].textContent+\'.png\');
    webpage.setAttribute(\'onerror\', \'this.src=\\\'/images/webpage_thumbnails/loading.gif\\\'\');
		webpage.setAttribute(\'class\', \'adPageLinkImage\');
				
		var dropShadow = parent.document.createElement(\'img\');
		dropShadow.setAttribute(\'id\', \'dropShadow\');
		dropShadow.setAttribute(\'src\', \'/images/site_placement_shadow.png\');
		dropShadow.setAttribute(\'class\', \'adPageLinkShadow\');

		webLink.appendChild(webpage);
		webpageCrop.appendChild(webLink);
		webpageWrapper.appendChild(webpageCrop);
		webpageWrapper.appendChild(dropShadow);
		webpageDiv.appendChild(webpageWrapper);
				
		var webpageInfoWrapper = parent.document.createElement(\'div\');
		webpageInfoWrapper.setAttribute(\'class\', \'adPageInfoWrapper\');

		var webpageInfo = parent.document.createElement(\'div\');
		webpageInfo.setAttribute(\'class\', \'adPageInfo\');
				
		var webpageSite = parent.document.createElement(\'div\');	
		webpageSite.setAttribute(\'style\', \'display:none; float:left; font-family:BebasNeue; color:#414142; font-size:24px; margin-left:30px; margin-top:0px; margin-right:20px;\');
		webpageSite.innerHTML = this.childNodes[1].textContent+\'<br><span>Site Name</span>\';
		webpageSite.childNodes[2].setAttribute(\'style\', \'font-size:14px; color:#414142;\');
				
				var webpageReach = parent.document.createElement(\'div\');	
		webpageReach.setAttribute(\'style\', \'display:inline; float:left; font-family:BebasNeue; color:#414142; font-size:23px; margin-top:0px; margin-left:10px; margin-right:60px;\');
		webpageReach.setAttribute(\'id\', \'local_reach\'); 
                webpageReach.innerHTML = this.childNodes[3].textContent+\'<br><span>Local Reach</span>\';
		webpageReach.childNodes[2].setAttribute(\'style\', \'font-size:14px; color:#414142;\');
				
				var webpageChannel = parent.document.createElement(\'div\');	
                webpageChannel.setAttribute(\'id\', \'channel_header\'); 
		webpageChannel.setAttribute(\'style\', \'display:inline; float:left; font-family:BebasNeue; color:#414142; font-size:23px;margin-top:0px; margin-right:20px;\');
		webpageChannel.innerHTML = this.childNodes[5].textContent.split(\'>\')[0]+\'<br><span>Channel</span>\';
		webpageChannel.childNodes[2].setAttribute(\'style\', \'font-size:14px; color:#414142;\');

		var webpageBorder = parent.document.createElement(\'div\');
		webpageBorder.setAttribute(\'class\', \'adPageDivider\');
				
		webpageInfo.appendChild(webpageSite);
		webpageInfo.appendChild(webpageReach);
		webpageInfo.appendChild(webpageChannel);
		webpageInfoWrapper.appendChild(webpageInfo);
		webpageInfoWrapper.appendChild(webpageBorder);
				
		webpageDiv.appendChild(webpageInfoWrapper);
		
		
		parent.document.getElementById(\'sliderBodyContent\').appendChild(webpageDiv);
				parent.document.getElementById(\'siteSpan\').innerHTML= this.childNodes[1].textContent;

		var demoDiv = parent.document.createElement(\'div\');
		demoDiv.setAttribute(\'id\', \'site_pack_demographics_iframe\');
		demoDiv.setAttribute(\'class\', \'site_pack_demographics_iframe\');
		var demoIFrame = parent.document.createElement(\'iframe\');
		demoIFrame.setAttribute(\'src\', \'/smb/get_media_targeting_demographics_by_site/\'+ this.childNodes[1].textContent);
		demoIFrame.setAttribute(\'width\', \'90%\');
		demoIFrame.setAttribute(\'height\', \'350px\');
		demoIFrame.setAttribute(\'id\', \'demographics_iframe\');
		demoIFrame.setAttribute(\'class\', \'demographics_iframe\');
		demoIFrame.setAttribute(\'frameborder\', \'0\');
		demoIFrame.setAttribute(\'scrolling\', \'no\');

		demoDiv.appendChild(demoIFrame);
		parent.document.getElementById(\'sliderBodyContent\').appendChild(demoDiv);

						
		">
		<td class="mt_col0">
			<a target="_blank" href="http://'.$channelRow->Domain.'">'.$channelRow->Domain.'</a>
		</td>
		<td class="mt_col1">
			'.number_format($reachPopulation).'
		</td>
		<td class="mt_col2">
			'.$channelRow->Category.'
		</td>
	</tr>
	';
}
?>

</table>
</body>
