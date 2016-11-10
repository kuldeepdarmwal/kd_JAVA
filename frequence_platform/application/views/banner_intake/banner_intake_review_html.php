<div class="container-fluid banner_intake_review_body">
	<?php 
	if($is_valid_banner_id){ ?>
	<h3><small>Adset request for: </small> <?php echo $form_data['advertiser_name'] ?><br/><small>requested by:  <?php echo $form_data['advertiser_email'].'</small><br/><small>requested time: '.time_elapsed_string($form_data['updated'],$cur_time,2); ?></small></h3>
	<table class="table table-hover">
		<tr>
			<td style="border-top: 1px solid #dddddd">Product:</td>
			<td style="border-top: 1px solid #dddddd"><?php echo $form_data['product']; ?></td>
		</tr>
		<tr>
			<td style="border-top: 1px solid #dddddd">Creative Name:</td>
			<td style="border-top: 1px solid #dddddd"><?php echo $form_data['creative_name']; ?></td>
		</tr>
		<tr>
			<td style="border-top: 1px solid #dddddd">Advertiser:</td>
			<td style="border-top: 1px solid #dddddd"><?php echo $form_data['advertiser_name']; ?></td>
		</tr>
		<?php if (isset($form_data['adset_version_url']) && $form_data['adset_version_url'] != '' && isset($form_data['user_role']) && $form_data['user_role'] !== 'sales'){?>
			<tr>
				<td style="border-top: 1px solid #dddddd">Adset Version:</td>
				<td style="border-top: 1px solid #dddddd"><?php echo '<a href="'.$form_data['adset_version_url'].'" target="_blank">'.$form_data['adset_version_url'].'</a>' ?></td>
			</tr>
		<?php } ?>
			
			
		<tr>
			<td style="border-top: 1px solid #dddddd">partner email:</td>
			<td style="border-top: 1px solid #dddddd"><?php echo '<a href="mailto:'.$form_data['requester_email'].'" target="_blank">'.$form_data['requester_email'].'</a>' ?></td>
		</tr>
		<tr>
			<td style="border-top: 1px solid #dddddd">advertiser webpage:</td>
			<td style="border-top: 1px solid #dddddd"><?php echo '<a href="'.$form_data['advertiser_website'].'" target="_blank">'.$form_data['advertiser_website'].'</a>' ?></td>
		</tr>
		<tr>
			<td style="border-top: 1px solid #dddddd">campaign landing page:</td>
			<td style="border-top: 1px solid #dddddd"><?php echo '<a href="'.$form_data['landing_page'].'" target="_blank">'.$form_data['landing_page'].'</a>' ?></td>
		
		</tr>
		<tr>
			<td style="border-top: 1px solid #dddddd">creative files:</td>
			<td style="border-top: 1px solid #dddddd"><?php if(isset($form_data['creative_files'])){
				foreach($form_data['creative_files'] as $creative_file)
				{
					echo '<a href="'. $creative_file['signedUrl'] .'" target="_blank" title="Right click and select \'Save As...\'">'. $creative_file['name'] .' <i class="icon icon-share"></i></a><br>';
				}
			} 
			else{
				echo '<span class="label label-warning">none attached</span>';
			}?></td>
		</tr>
		<tr>
			<td style="border-top: 1px solid #dddddd">scenes:</td>
			<td style="border-top: 1px solid #dddddd"><?php 
				if(isset($form_data['scenes'])){
					foreach($form_data['scenes'] as $scene_num=>$scene)
					{
						echo '<span class="label" style="font-size:x-small"><small >'.($scene_num+1).'</small></span> '.nl2br($scene).'<br>';
					}
				} 
				else{
					echo '<span class="label label-warning"> None specified</span>';
				}?>
			</td>
		</tr>
		<tr>
			<td style="border-top: 1px solid #dddddd">cta:</td>
			<td style="border-top: 1px solid #dddddd"><?php echo ($form_data['cta'] == "other" ? ' "'.$form_data['cta_other'].'"' : '"'.$form_data['cta'].'"') ?></td>
		</tr>
		<tr>
			<td style="border-top: 1px solid #dddddd">video:</td>
			<td style="border-top: 1px solid #dddddd"><?php
				if($form_data['features_video_youtube_url'] || $form_data['features_video_video_play'] || $form_data['features_video_mobile_clickthrough_to'])
				{
					echo ($form_data['features_video_youtube_url'] ? '<span class="muted" ><i class="icon-youtube-play"></i>  </span><a href="'.$form_data['features_video_youtube_url'].'" target="_blank">'.$form_data['features_video_youtube_url'].'</a><br>' : '');
					echo ($form_data['features_video_video_play'] ? '<span class="muted" style="color:#999999">video play: </span>'.str_replace('_',' ',$form_data['features_video_video_play']).'<br>' : '');
					echo ($form_data['features_video_mobile_clickthrough_to'] ? '<span class="muted" style="color:#999999">mobile click-through: </span>'.str_replace('_',' ',$form_data['features_video_mobile_clickthrough_to']).'<br>' : '');
				} 
				else
				{
					echo '<span class="label "> Not Requested </span>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td style="border-top: 1px solid #dddddd">map locations:</td>
			<td style="border-top: 1px solid #dddddd"><?php
				if($form_data['features_map_locations'])
				{
					echo nl2br($form_data['features_map_locations']);
				} 
				else
				{
					echo '<span class="label "> Not Requested </span>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td style="border-top: 1px solid #dddddd">social:</td>
			<td style="border-top: 1px solid #dddddd"><?php
				if($form_data['features_social_twitter_text'] || $form_data['features_social_email_subject'] || $form_data['features_social_email_message'] || $form_data['features_social_linkedin_subject'] || $form_data['features_social_linkedin_message'])
				{
					echo ($form_data['features_social_twitter_text'] ? '<span class="muted"><i class="icon-twitter"></i> <span style="font-size:x-small;color:#999999">twitter</span> </span>'.$form_data['features_social_twitter_text'].'<br><hr style="border-top: 1px solid #eeeeee">' : '');
					echo ($form_data['features_social_email_subject'] ? '<span class="muted"><i class="icon-envelope"></i> <span style="font-size:x-small;color:#999999">email subject </span> </span>'.$form_data['features_social_email_subject'].'<br>' : '');
					echo ($form_data['features_social_email_message'] ? '<span class="muted"><i class="icon-envelope"></i> <span style="font-size:x-small;color:#999999">email message </span></span>'.nl2br($form_data['features_social_email_message']).'<br><hr style="border-top: 1px solid #eeeeee">' : '');
					echo ($form_data['features_social_linkedin_subject'] ? '<span class="muted"><i class="icon-linkedin-sign"></i> <span style="font-size:x-small;color:#999999">linkedin subject </span></span>'.$form_data['features_social_linkedin_subject'].'<br>' : '');
					echo ($form_data['features_social_linkedin_message'] ? '<span class="muted"><i class="icon-linkedin-sign"></i> <span style="font-size:x-small;color:#999999">linkedin message </span></span>'.nl2br($form_data['features_social_linkedin_message']).'<br><hr style="border-top: 1px solid #eeeeee">' : '');
				
				} 
				else
				{
					echo '<span class="label "> Not Requested </span>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td style="border-top: 1px solid #dddddd">variations:</td>
			<td style="border-top: 1px solid #dddddd"><?php 
				if(isset($form_data['has_variations']) && $form_data['has_variations'] == "on"){
					foreach($form_data['variation_names'] as $idx => $variation_name)
					{
						if($variation_name != "" && $form_data['variation_details'][$idx] != "")
							echo '<span class="label" style="font-size:x-small"><small >'.($idx+1).'</small></span> '.nl2br($variation_name.' - '.$form_data['variation_details'][$idx]).'<br>';
					}
				} 
				else{
					echo '<span class="label label-warning"> None specified</span>';
				}?>
			</td>
		</tr>		
		<tr>
			<td style="border-top: 1px solid #dddddd">other comments:</td>
			<td style="border-top: 1px solid #dddddd"><?php
				if(isset($form_data['other_comments']))
				{
					echo nl2br($form_data['other_comments']);
				
				} 
				else
				{
					echo '<span class="label "> Not Requested </span>';
				}
				?>
			</td>
		</tr>
	</table>



<?php }else{ ?>



<h3 class="well"><i class="icon-frown"></i> Oops, can't find that adset request <small></small> </h3>



<?php } ?>

</div>

<?php

function time_elapsed_string($datetime,$cur_time, $level = 7) {
	//$now = new DateTime;
	$now = new DateTime($cur_time);
	$ago = new DateTime($datetime);
	$diff = $now->diff($ago);

	$string = array(
		'y' => 'year',
		'm' => 'month',
		'd' => 'day',
		'h' => 'hour',
		'i' => 'minute',
		's' => 'second',
	);
	foreach ($string as $k => &$v)
	{
		if ($diff->$k)
		{
			$v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
		}
		else
		{
			unset($string[$k]);
		}
	}

	$string = array_slice($string, 0, $level);
	return $string ? implode(', ', $string) . ' ago' : 'just now';
}

?>
