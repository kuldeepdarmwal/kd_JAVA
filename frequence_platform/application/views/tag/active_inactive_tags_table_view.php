<?php
	if ($tags != NULL)
	{
		foreach($tags as $row)
		{
			
			try
			{
				//Don't display tags which won't have any advertiser association.
				if(!isset($row["advertiser_id"]) || $row["advertiser_id"] <= 0)
				{
					continue;
				}

				$is_active = ($row["is_active"] == 1) ? TRUE : FALSE;
				$class_html = $is_active ? '' : 'class="warning"';

				echo ' <tr id="row_id_'.$row["id"].'" '.$class_html.' onclick="">';
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

				if (isset($row["full_campaign"]))
				{
					echo '<strong>'.$row["advertiser_name"]." : </strong>".$row["full_campaign"];
				}
				else
				{
					echo '<span style="color:#cccccc;">No Campaign Selected</span>';
				}

				echo ' 	</td>';
				echo ' 	<td class="tag_list_data">';
				echo 		$row["name"].'' ;
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

				if ($row["advertiser_id"] != 0)
				{
					echo '<a title="download script" class="btn btn-mini btn-primary" target="_blank" href="/tag/download_tags/'.$row["advertiser_id"].'"><i class="icon-chevron-left icon-white"></i>all JS for advertiser<i class="icon-chevron-right icon-white"></i></a>';
				}
				else
				{
					echo '<span title="no tags" class="btn btn-mini  disabled" ><i class="icon-chevron-left icon"></i>all js for file<i class="icon-chevron-right icon"></i></span>';
				}

				if ($is_active)
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
			}catch (Exception $e){
				echo 'Caught exception: ',  $e->getMessage(), "\n";
			}
		} 
	}
	else 
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
?>