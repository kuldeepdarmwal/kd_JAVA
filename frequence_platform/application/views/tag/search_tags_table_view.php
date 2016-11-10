<?php

echo '<table class="table table-striped table-hover table-condensed">';
echo '<thead>';
echo ' <tr >';
echo ' 	<th >';
echo ' 		ID';
echo ' 	</th>';
echo ' 	<th >';
echo ' 		Tag Type';
echo ' 	</th>';
echo ' 	<th >';
echo ' 		Advertiser : Campaigns';
echo ' 	</th>';
echo ' 	<th >';
echo ' 		Filename';
echo ' 	</th>';
echo ' 	<th >';
echo ' 		Actions';
echo ' 	</th>';
echo ' </tr>';
echo '</thead>';
echo '<tbody>';


if ($tags != NULL){
	foreach($tags as $row)
	{
		//Don't display tags which won't have any advertiser association.
		if(!isset($row["advertiser_id"]) || $row["advertiser_id"] <= 0)
		{
			continue;
		}
		
		if($row["is_active"])
		{
			echo ' <tr  id="tr_'.$row["id"].'" onclick="">';
		}
		else
		{
			echo ' <tr id="tr_'.$row["id"].'" class="warning" onclick="">';
		}
		echo ' 	<td >';
		echo 		$row["id"];
		echo ' 	</td>';
		echo ' 	<td >';
		switch($row['tag_type'])
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
		echo '  <strong>'.$row["advertiser_name"]." : </strong>".$row["full_campaign"];
		echo ' 	</td>';
		echo ' 	<td class="tag_list_data">';
		echo 		$row["name"].'' ;
		echo ' 	</td>';
		echo ' 	<td >';
		echo '<div class="btn-group">';
		
		if ($row["advertiser_id"] != 0)
		{
			echo '<a title="download script" class="btn btn-mini btn-primary" target="_blank" href="/tag/download_tags/'.$row["advertiser_id"].'"><i class="icon-chevron-left icon-white"></i>all JS for campaign<i class="icon-chevron-right icon-white"></i></a>';
		}
		else{
			echo '<span title="no tags" class="btn btn-mini  disabled" ><i class="icon-chevron-left icon"></i>all js for campaign<i class="icon-chevron-right icon"></i></span>';
		}

		if($row["is_active"])
		{
			echo '<a  class="btn btn-mini btn-success"  title="View advertiser script" onclick="pop_up_tag('.$row["id"].','.$row["advertiser_id"].','.$row['tag_type'].');" ><i class="icon-chevron-left icon-white"></i>JS for this tag<i class="icon-chevron-right icon-white"></i></a><a class="btn btn-mini btn-inverse"  title="Edit tag" onclick="pop_up_tag_update('.$row["id"].','.$row["advertiser_id"].');"><i class="icon-edit icon-white"></i> Edit</a><a id="action_'.$row["id"].'" class="btn btn-mini btn-danger" onclick="deactivate_tag('.$row["id"].','.$row["advertiser_id"].');" title="Deactivate tag"><i class="icon-trash icon-white"></i></a>';
		}
		else
		{
			echo ' <a  class="btn btn-mini btn-success"  title="View advertiser script" onclick="pop_up_tag('.$row["id"].','.$row["advertiser_id"].','.$row['tag_type'].');" ><i class="icon-chevron-left icon-white"></i>JS for this tag<i class="icon-chevron-right icon-white"></i></a><a class="btn btn-mini btn-inverse"  title="Edit tag" onclick="pop_up_tag_update('.$row["id"].','.$row["advertiser_id"].');"><i class="icon-edit icon-white"></i> Edit</a><a id="action_'.$row["id"].'" class="btn btn-mini btn-success" onclick="activate_tag('.$row["id"].','.$row["advertiser_id"].');" title="Activate tag"><i class="icon-play-circle icon-white"></i></a>';
		}
		echo '</div>';
		echo ' 	</td>';
		echo ' </tr>';
	} 
} else 
{
	echo ' <tr class="error" onclick="">';
	echo ' 	<td >';
	echo ' 	-- ';
	echo ' 	</td>';
	echo ' 	<td >';
	echo ' 	-- ';
	echo ' 	</td>';
	echo ' 	<td >';
	echo ' 	-- ';
	echo ' 	</td>';
	echo ' 	<td >';
	echo ' 	-- ';
	echo ' 	</td>';
	echo ' 	<td >';
	echo ' 	none ';
	echo ' 	</td>';
	echo ' 	<td >';
	echo ' 	-- ';
	echo ' 	</td>';
	echo ' </tr>';			
   
}
echo ' </tbody></table><br />';
echo '<div>';//end of active tag table
?>