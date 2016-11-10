<!DOCTYPE html>
<html>
<head>
	<title>Media Targeting</title>
<script language="JavaScript"> 
  
  <!--
  function calcHeight()
  {
//find the height of the internal page
var the_height=
document.getElementById('resize').contentWindow.
document.body.scrollHeight;

//change the height of the iframe
document.getElementById('resize').height=
the_height;
}
//-->
</script>
</head>
<body >

	<script type="text/javascript" src="/js/jquery-1.7.1.min.js"></script>
	<script type="text/javascript" src="/js/jquery.sparkline.js"></script>
        <a><div class ="squeezeBox" id="squeezeBoxExpand" style="color: #423b3b; font-family: 'Oxygen', sans-serif; font-size:10px;position:absolute; left:424px; top:59px; display:none;z-index:1000;" onClick="$('#squeezeBoxExpand').css('display', 'none'); $('#squeezeBoxContract').css('display', 'inline'); $('div.squeezeBody').css('display','inline');ShowCheckboxes();  SetupSlider();"><img height="21px" width="23px" src="/images/options_gear.png" > show options</div></a>
        <a><div class ="squeezeBox" id="squeezeBoxContract" style="color: #423b3b; font-family: 'Oxygen', sans-serif; font-size:10px; position:absolute; left:424px; top:59px; display:inline; z-index:1000;" onClick="$('#squeezeBoxContract').css('display', 'none'); $('#squeezeBoxExpand').css('display', 'inline'); $('div.squeezeBody').css('display','none');"><img height="21px" width="23px" src="/images/options_gear.png" > hide options</div></a>
        <div class="MtReachFrequencyOptions" id="rfSlider" >
			Reach / Frequency
		</div>    
        <div class="squeezeBody" style="display:inline">
           <div class="MtOptionsDiv" style="font-size:11px">
		<div class="MtDemographicOptions" id="demoCheckboxes">
			Demographic Options
		</div>
		<?php
		$iab_categories_style = 'style="display:block;"';
		$channels_style = 'style="display:none;"';
		if($selected_iab_categories != false || count($channels) == 0)
		{
			// show when there are iabs or no channels
			$iab_categories_style = 'style="display:block;"';
			$channels_style = 'style="display:none;"';
		}
		else
		{
			// only show when there are no iabs and there are channels
			$iab_categories_style = 'style="display:none;"';
			$channels_style = 'style="display:block;"';
		}
		?>

		<?php
		$lower_role = strtolower($role);
		if($lower_role == 'ops' || $lower_role == 'admin')
		{
			?>
			<div>
				<input type="button" value="Toggle IAB Categories" onclick="toggle_iab_categories();" />
				<input type="button" value="Toggle Channels" onclick="toggle_channels();" />
			</div>
			<?php
		}
		?>

		<div id="iab_categories_section" class="MtIabCategoryOptions" <?php echo $iab_categories_style; ?> >
			<div style="font-family:'BebasNeue', sans-serif; color:#414142; font-size:14px;">
				IAB Category Options
			</div>
			<div id="MtIabCategoryOptionsSelector">
			<select class="chzn-select-iab" multiple tabindex="5" size="5" name="iab_category_choices" onChange="save_iab_categories();" id="iab_category_choices">
			<?php    
			$selected_items = array();
			foreach($selected_iab_categories as $v)
			{
				foreach($unique_iab_categories as $w)
				{
					if($w['tag_copy'] == $v['tag_copy'])
					{
						$selected_items[$w['tag_copy']] = true;
						break;
					}
				}
			}
			for ($i=0; $i < count($unique_iab_categories); $i++)
			{
				$iab_channel = $unique_iab_categories[$i];
				$selected_string = "";
				if(array_key_exists($iab_channel['tag_copy'], $selected_items))
				{
					$selected_string = 'selected="selected"';
				}
				echo '<option value="'.$iab_channel['id'].'"'.$selected_string.'" >'.$iab_channel['tag_copy'].'</option>';
			} 
			?>
			</select>  
			</div>
		</div>
		<div id="channels_section" class="MtChannelOptions" <?php echo $channels_style; ?> >
			<div style="font-family:'BebasNeue', sans-serif; color:#414142; font-size:14px;">
				Channel Options
			</div>
			<div id="MtChannelOptionsSelector">
			<select class="chzn-select " multiple tabindex="4" size="5" name="channel_choices" onChange="UpdateSitesList();" id="channel_choices">
			<?php 
				for ($j=0;$j<count($channels);$j++) {
					$channels[$j] = trim($channels[$j]);
				}
                                
				for ($i=0;$i<count($unique_site_categories);$i++)
				{
					$unique_channel = $unique_site_categories[$i]['Category'];
					if (in_array(trim($unique_channel), $channels))
					{
						$selected_string = 'selected="selected"';
					}
					else
					{
						$selected_string ='';
					}

					echo '<option value="'.$unique_channel.'"'.$selected_string.'" >'.$unique_channel.'</option>'; 
				} 
                                
			?>
			</select>  
			</div>
		</div>
		

	</div>
            </div>

<div id="site_pack_category">
</div> 
<div id="site_pack" class="mt_site_pack_position" style="margin-top:0px">
<?php
	//$this->load->view('smb/site_pack_table');
?>
</div>

</body>
</html>
