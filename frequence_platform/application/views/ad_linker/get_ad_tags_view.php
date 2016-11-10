<div class="span6">

	
<?php

function write_creative_size_ad_tag_button(
	$id,
	$size_string,
	$ad_server
)
{
	echo '
		<span class="btn btn-mini btn-inverse" onclick="get_ad_tag_for_creative('.$id.', \''.$ad_server.'\');">
			<i class="icon-chevron-left icon-white"></i>
		'.$size_string.'
			<i class="icon-chevron-right icon-white"></i>
		</span>
	';
}

function write_add_tags_button(
	$partner_name,
	$ttd_tags,
	$campaign_id,
	$version_id,
	$ttd_exists,
	$target_ad_server
)
{
	$ad_server_string = 'dfa';
	switch($target_ad_server)
	{
		case 'dfa':
			$ad_server_string = 'dfa';
			break;
		case 'adtech':
			$ad_server_string = 'adtech';
			break;
		case 'fas':
			$ad_server_string = 'fas';
			break;
		default:
			// TODO: do something other than die? -scott
			die("unknown target ad server: ".$target_ad_server." Error: #2190873");
	}

	if($ttd_tags)
	{
	    	if($ttd_exists)
		{
			echo '<span class="label label-important">Creatives already exist for this adset!</span>
			    <br>';
		}

		echo '
			<select id="creative_push_option" style="margin-top:5px;margin-bottom:0px;width:220px;" onchange="reset_push_button(\''.$ad_server_string.'\')">
				<option value="all" selected="selected">All</option>
				<option value="standard">Standard</option>
				<option value="rtg">RTG</option>
			</select>
		';
		echo '
			<span id="ttd_'.$ad_server_string.'_tooltip" class="ad_tag_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Adgroup Selection" data-content="Select which adgroup(s) will have creatives pushed to them: All: all applicable adgroups; Standard: All non-retargeting adgroups; RTG: Only RTG">
				<i class="icon-info-sign "></i>
			</span><br>
		';
		echo '
			<a id="add_'.$ad_server_string.'_tags_to_tradedesk_button" class="btn btn btn-info" style="margin-top:5px;width:194px;text-align:left;" data-pushdisabled="false" onclick="add_creatives_to_tradedesk(this, '.$campaign_id.','.$version_id.',\''.$ad_server_string.'\');" target="_blank">
				<i class="icon-retweet icon-white"></i> 
				Add '.$target_ad_server.' tags to TTD
			</a>
		';
		echo '
			<span id="ttd_'.$ad_server_string.'_tooltip" class="ad_tag_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="TTD Creatives" data-content="Add creatives to TTD and link them to the selected adgroups in the selected set owned by its campaign using '.$ad_server_string.' tags.">
				<i class="icon-info-sign "></i>
			</span>
		';		

		echo '<br><div style="margin-top:5px;"> <input id="ttd_'.$ad_server_string.'_prefix_textbox"  type="text" placeholder="my_prefix"> <span id="ttd_prefix_'.$ad_server_string.'_tooltip" class="ad_tag_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="TTD Creative Prefix" data-content="Optional custom prefix to be put in front of creative names for the '.$ad_server_string.' tags being pushed to TradeDesk."><i class="icon-info-sign "></i></span></div>';
		echo '<br><div style="position: absolute;top: 30%;text-align: center;right: 10px;width: 65px;"><input id="overwrite_creatives" type="checkbox" value="" checked="true" onclick="handle_overwrite_change(this.checked)"> <span id="push_description" class="label label-important" style="width:58px;">REPLACE</span></div>';
	}
}

function have_ad_tags($creatives, $ad_server)
{
	$ad_server_type = k_ad_server_type::unknown;
	
	if($ad_server == "dfa")
	{
	    $tag_to_check = 'ad_tag';
	    $ad_server_type = k_ad_server_type::dfa_id;
	}
	else if($ad_server == "fas")
	{
		$tag_to_check = 'ad_tag';
		$ad_server_type = k_ad_server_type::fas_id;
 	}
	else
	{
	    $tag_to_check = 'adtech_ad_tag';
	}
	foreach($creatives as $row)
	{	
	    if($row[$tag_to_check] != NULL && $row['published_ad_server'] == $ad_server_type)
	    {
		return TRUE;
	    }
	}
	return FALSE;
}

function write_all_to_xls_button($adset, $version_id, $ad_server)
{
	echo '<span class="btn btn-mini btn-primary" onclick="download_spreadsheet('.$adset.', '.$version_id.', \''.$ad_server.'\')" ><i class="icon-download-alt icon-white"></i> <i class="icon-chevron-left icon-white"></i> ALL '.$ad_server.' to XLS<i class="icon-chevron-right icon-white"></i></span>';
}

function write_all_to_txt_button($version_id, $ad_server)
{
	echo '<span class="btn btn-mini btn-info" onclick="text_tags('.$version_id.', \''.$ad_server.'\')"><i class="icon-share icon-white"></i> <i class="icon-chevron-left icon-white"></i> ALL '.$ad_server.' to TXT<i class="icon-chevron-right icon-white"></i></span>';
}

function write_render_all_button($version_id, $ad_server)
{
	echo '<span class="btn btn-mini btn-success" onclick="render_tags('.$version_id.', \''.$ad_server.'\');"> <i class="icon-picture icon-white"></i> RENDER ALL '.$ad_server.'</span>';
}

$adtech_column_class = "adtech_tags_column";
$dfa_column_class = "dfa_tags_column";
$fas_column_class = "fas_tags_column";

//switch($published_ad_server)
//{
//	case k_ad_server_type::adtech_id:
//		$adtech_column_class .= " active_tag_set";
//		break;
//	case k_ad_server_type::dfa_id:
//		$dfa_column_class .= " active_tag_set";
//		break;
//	case k_ad_server_type::fas_id:
//		$fas_column_class .= " active_tag_set";
//		break;
//	default:
//		break;
//}

$partner_name = isset($partner[0]['partner_name'])? $partner[0]['partner_name'] : "VL";

$num_dfa_ad_tags = 0;
$num_adtech_ad_tags = 0;
$num_fas_ad_tags = 0;

if ($creatives != NULL){
	echo '<table class="table table-striped table-hover table-condensed">';
	echo '<thead>';
	echo ' <tr >';
	echo ' 	<th >';
	echo ' 		Size';
	echo ' 	</th>';
	echo ' 	<th class="'.$dfa_column_class.'">';
	echo ' 		DFA Ad Tag';
	echo '		<span id="dfa_linked_to_ttd_notice">';
	if($published_ad_server == k_ad_server_type::dfa_id)
	{
		echo ' 	(linked to TTD)';
	}
	echo '		</span>';
	echo ' 	</th>';
	echo ' 	<th class="'.$fas_column_class.'">';
	echo ' 		FAS Ad Tag';
	echo '		<span id="fas_linked_to_ttd_notice">';
	if($published_ad_server == k_ad_server_type::fas_id)
	{
		echo ' 	(linked to TTD)';
	}
	echo '		</span>';
	echo ' 	</th>';
	echo ' </tr>';
	echo '</thead>';
	echo '<tbody>';
		echo '
		<tr onclick="">
		<td></td><td>
		';
	if(have_ad_tags($creatives, 'dfa'))
	{
	    echo'
		    
		    <label class="checkbox">
			    <input id="include_ad_choices_dfa_span" checked="checked" type="checkbox" value="" ';
	    if($dfa_used_adchoices)
	    {
		echo 'checked="true"';
	    }
	    echo'>
			    include <b>'.$partner_name.'</b> AdChoices span?
		    </label>
		    
	    ';
	}
	echo '</td><td>';
	if(have_ad_tags($creatives, 'fas'))
	{
	echo'
		<label class="checkbox">
			<input id="include_ad_choices_fas_span" checked="checked" type="checkbox" value="" ';
	    if($fas_used_adchoices)
	    {
		echo 'checked="true"';
	    }
	    echo '>
			include <b>'.$partner_name.'</b> AdChoices span?
		</label>
	';
	}
	echo '</td></tr>';
	foreach($creatives as $row)
	{	
		echo ' <tr  onclick="">';
		echo ' 	<td >';
		echo 		$row["size"];
		echo ' 	</td>';
		echo ' 	<td class="'.$dfa_column_class.'">';
		if($row['ad_tag'] != NULL && $row['published_ad_server'] == k_ad_server_type::dfa_id)
		{
			$num_dfa_ad_tags++;
			write_creative_size_ad_tag_button($row["id"], $row["size"], 'dfa');
		}
		echo ' 	</td>';
		echo ' 	<td class="'.$fas_column_class.'">';
		if($row['ad_tag'] != NULL && $row['published_ad_server'] == k_ad_server_type::fas_id)
		{
			$num_fas_ad_tags++;
			write_creative_size_ad_tag_button($row["id"], $row["size"], 'fas');
		}
		echo ' 	</td>';
		
		echo ' </tr>';
	} 
	echo '
		<tr>
			<td>
			Add Tags
			</td>
		 	<td style="position:relative;" class="'.$dfa_column_class.'">
	';
	if($num_dfa_ad_tags > 0)
	{
		write_add_tags_button(
			$partner_name,
			$ttd_tags,
			$campaign_id,
			$version_id,
			$ttd_exists,
			'dfa'
		);
	}
	else
	{
		echo 'No dfa tags';
	}
	echo '
			</td>
		 	<td style="position:relative;" class="'.$fas_column_class.'">
	';
	if($num_fas_ad_tags > 0)
	{
		write_add_tags_button(
			$partner_name,
			$ttd_tags,
			$campaign_id,
			$version_id,
			$ttd_exists,
			'fas'
		);
	}
	else
	{
		echo 'No fas tags';
	}
	echo '
			</td>
		</tr>
	';

	echo '
		<tr>
			<td>
			To XLS
			</td>
		 	<td class="'.$dfa_column_class.'">
	';
	// TODO: handle this for different ad servers
	if($num_dfa_ad_tags > 0)
	{
		write_all_to_xls_button($adset, $row['version_id'], 'dfa');
	}
	echo '
			</td>
		 	<td class="'.$fas_column_class.'">
	';
	if($num_fas_ad_tags > 0)
	{
		write_all_to_xls_button($adset, $row['version_id'], 'fas');
	}
	echo '
			</td>
		</tr>
	';

	echo '
		<tr>
			<td>
			To TXT
			</td>
		 	<td class="'.$dfa_column_class.'">
	';
	// TODO: handle this for different ad servers
	if($num_dfa_ad_tags > 0)
	{
		write_all_to_txt_button($row['version_id'], 'dfa');
	}
	echo '
			</td>
		 	<td class="'.$fas_column_class.'">
	';
	if($num_fas_ad_tags > 0)
	{
		write_all_to_txt_button($row['version_id'], 'fas');
	}
	echo '
			</td>
		</tr>
	';

	echo '
		<tr>
			<td>
			RENDER
			</td>
		 	<td class="'.$dfa_column_class.'">
	';
	// TODO: handle this for different ad servers
	if($num_dfa_ad_tags > 0)
	{
		write_render_all_button($row['version_id'], 'dfa');
	}
	echo '
			</td>
		 	<td class="'.$fas_column_class.'">
	';
	if($num_fas_ad_tags > 0)
	{
		write_render_all_button($row['version_id'], 'fas');
	}
	echo '
			</td>
		</tr>
	';
	
	if ($row['published_ad_server'] == k_ad_server_type::fas_id)
	{
		echo '
			<tr>
				<td>
					To TXT
				</td>
				<td class="'.$dfa_column_class.'"></td>
				<td class="'.$fas_column_class.'">
		';
		
		if ($num_fas_ad_tags > 0)
		{
			echo '<span class="btn btn-mini btn-warning" onclick="oando_text_tags('.$version_id.', \'fas\')"><i class="icon-share icon-white"></i> <i class="icon-chevron-left icon-white"></i> ALL fas for O&O<i class="icon-chevron-right icon-white"></i></span>';
		}
		
		echo '
				</td>				
			</tr>
		';
	}
	
echo ' </tbody></table>';

echo '</div>';

} 
else
{
    echo "<h4>No published ad tags available.</h4>";
}


?>




</div>
