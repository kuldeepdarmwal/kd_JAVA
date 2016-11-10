<?php
	if ($tag_data != NULL && count($tag_data) == 1)
	{
		//Don't display tags which won't have any advertiser association.
		if(!isset($tag_data[0]["advertiser_id"]) || $tag_data[0]["advertiser_id"] <= 0)
		{
			return;
		}
		
		$is_active = ($tag_data[0]["is_active"] == 1) ? TRUE : FALSE;
		$class_html = $is_active ? '' : 'class="warning"';
		
		echo ' <tr id="row_id_'.$tag_data[0]["id"].'" '.$class_html.' onclick="">';
		echo ' 	<td >';
		echo $tag_data[0]["id"];
		echo ' 	</td>';
		echo ' 	<td >';
		
		switch ($tag_data[0]['tag_type'])
		{
			case "0":
				echo "Retargeting";
				break;
			case "1":
				echo "Ad Verify";
				break;
			case "2":
				echo "Conversion";
				break;
			case "3":
				echo "Custom";
				break;
		}
		
		echo ' 	</td>';
		echo ' 	<td class="tag_list_data">';
		
		if (isset($tag_data[0]["full_campaign"]))
		{
			echo '<strong>'.$tag_data[0]["advertiser_name"]." : </strong>".$tag_data[0]["full_campaign"];
		}
		else
		{
			echo '<span style="color:#cccccc;">No Campaign Selected</span>';
		}

		echo ' 	</td>';
		echo ' 	<td class="tag_list_data">';
		echo $tag_data[0]["name"].'' ;
		echo ' 	</td>';
		echo ' 	<td>';
		
		if ($is_active)
		{
			echo 'Yes';
		}
		else
		{
			echo 'No';
		}
		
		echo ' 	</td>';
		echo ' 	<td >';
		echo '<div class="btn-group">';
		
		if ($tag_data[0]["advertiser_id"] != 0)
		{
			echo '<a title="download script" class="btn btn-mini btn-primary" target="_blank" href="/tag/download_tags/'.$tag_data[0]["advertiser_id"].'"><i class="icon-chevron-left icon-white"></i>all JS for campaign<i class="icon-chevron-right icon-white"></i></a>';
		}
		else
		{
			echo '<span title="no tags" class="btn btn-mini  disabled" ><i class="icon-chevron-left icon"></i>all js for campaign<i class="icon-chevron-right icon"></i></span>';
		}

		if ($is_active)
		{
			echo '<a  class="btn btn-mini btn-success"  title="View advertiser script" onclick="pop_up_tag('.$tag_data[0]["id"].','.$tag_data[0]["advertiser_id"].','.$tag_data[0]['tag_type'].');" ><i class="icon-chevron-left icon-white"></i>JS for this tag<i class="icon-chevron-right icon-white"></i></a><a class="btn btn-mini btn-inverse"  title="Edit tag" onclick="pop_up_tag_update('.$tag_data[0]["id"].','.$tag_data[0]["advertiser_id"].');"><i class="icon-edit icon-white"></i> Edit</a><a id="action_'.$tag_data[0]["id"].'" class="btn btn-mini btn-danger" onclick="deactivate_tag('.$tag_data[0]["id"].','.$tag_data[0]["advertiser_id"].');" title="Deactivate tag"><i class="icon-trash icon-white"></i></a>';
		}
		else
		{
			echo ' <a  class="btn btn-mini btn-success"  title="View advertiser script" onclick="pop_up_tag('.$tag_data[0]["id"].','.$tag_data[0]["advertiser_id"].','.$tag_data[0]['tag_type'].');" ><i class="icon-chevron-left icon-white"></i>JS for this tag<i class="icon-chevron-right icon-white"></i></a><a class="btn btn-mini btn-inverse"  title="Edit tag" onclick="pop_up_tag_update('.$tag_data[0]["id"].','.$tag_data[0]["advertiser_id"].');"><i class="icon-edit icon-white"></i> Edit</a><a id="action_'.$tag_data[0]["id"].'" class="btn btn-mini btn-success" onclick="activate_tag('.$tag_data[0]["id"].','.$tag_data[0]["advertiser_id"].');" title="Activate tag"><i class="icon-play-circle icon-white"></i></a>';
		}
		
		echo '</div>';
		echo ' 	</td>';
		echo ' </tr>';
	}
?>