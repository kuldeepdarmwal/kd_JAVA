<style type="text/css">
	#adset_request_link_button
	{
		color: #0088cc;
	}

	#adset_request_link_button:hover
	{
		color: #0099ff;
		text-decoration: none;
		cursor: pointer;
	}
</style>
<div class="container-fluid banner_intake_review_body">
	<h3>
		<span style="float:left;">
		<?php if(!$is_review){ ?>
			<small><a href="<?php echo $base_url."banner_intake/ticket_preview/".base64_encode($form_data['insert_id']); ?>">Adset request</a> for: </small>
		<?php } else { ?>
			<small>Adset request for: </small>
		<?php }?>
<?php			echo $form_data['advertiser_name'] 
?>			<br/>
<?php			
			if (isset($requested_time))
			{
?>				<small>
<?php					echo 'Requested Time: '.$requested_time;
?>				</small>
<?php			}
?>		</span>
<?php if(isset($version_ids) && $version_ids != false) { ?>
		<span style="float:right;"><small>
		<?php foreach($version_ids as $version) { ?>
			&nbsp;<a target="_blank" href="/creative_uploader/<?php echo $version['version_id'];?>">V<?php echo $version['version_number']; ?></a>&nbsp;
		<?php } ?>
		</small></span>
<?php } ?>
<?php		if (!(isset($request_source)) || $request_source == "new_adset_request")
		{
?>			<span style="float:right;"><a href="/creative_requests/new" id="adset_request_link_button" class="btn btn-link btn-large"><i class="icon-picture icon-white"></i> <strong>New Creative Request</strong></a></span>
<?php		}
?>	</h3>
	<table id="review_fields_table" class="table table-hover">
		<tr>
			<td style="border-top: 1px solid #dddddd">Product:</td>
			<td style="border-top: 1px solid #dddddd">
<?php				echo $form_data['product']; 
?>			</td>
		</tr>
		<tr>
			<td style="border-top: 1px solid #dddddd">Creative Name:</td>
			<td style="border-top: 1px solid #dddddd">
<?php				echo $form_data['creative_name']; 
?>			</td>
		</tr>
		<tr>
			<td style="border-top: 1px solid #dddddd">Advertiser:</td>
			<td style="border-top: 1px solid #dddddd">
<?php				echo $form_data['advertiser_name']; 
?>			</td>
		</tr>
<?php		if (isset($form_data['ticket_owner']) && $form_data['ticket_owner'] !== '')
		{
?>			<tr>
				<td style="border-top: 1px solid #dddddd">Ticket Owner:</td>
				<td style="border-top: 1px solid #dddddd">
<?php					echo $form_data['ticket_owner'];
?>				</td>
			</tr>
<?php		}
		if (isset($form_data['cc_on_ticket']) && $form_data['cc_on_ticket'] !== '0')
		{
?>			<tr>
				<td style="border-top: 1px solid #dddddd">CC On Ticket:</td>
				<td style="border-top: 1px solid #dddddd">
<?php					echo $form_data['cc_on_ticket'];
?>				</td>
			</tr>
<?php		}
		if (isset($form_data['adset_version_url']) && $form_data['adset_version_url'] != '' && isset($user_role) && $user_role !== 'sales')
		{
?>			<tr>
				<td style="border-top: 1px solid #dddddd">Adset Version:</td>
				<td style="border-top: 1px solid #dddddd">
<?php					echo '<a href="'.$form_data['adset_version_url'].'" target="_blank">'.$form_data['adset_version_url'].'</a>' 
?>				</td>
			</tr>
<?php		}

		if ($form_data['product'] === 'Display')
		{
?>			<tr>
				<td style="border-top: 1px solid #dddddd">Request Type:</td>
				<td style="border-top: 1px solid #dddddd">
<?php					echo $form_data['request_type']; 
?>				</td>
			</tr>
<?php			
			if ($form_data['request_type'] === 'Custom Banner Design')
			{
				if (isset($form_data['advertiser_email']) && $form_data['advertiser_email'] !== '')
				{
?>					<tr>
						<td style="border-top: 1px solid #dddddd">Advertiser Email:</td>
						<td style="border-top: 1px solid #dddddd">
<?php							echo $form_data['advertiser_email']; 
?>						</td>
					</tr>
<?php				} 
?>				<tr>
					<td style="border-top: 1px solid #dddddd">Advertiser Website:</td>
					<td style="border-top: 1px solid #dddddd">
<?php						echo '<a href="'.$form_data['advertiser_website'].'" target="_blank">'.$form_data['advertiser_website'].'</a>' 
?>					</td>
				</tr>
				<tr>
					<td style="border-top: 1px solid #dddddd">Landing Page:</td>
					<td style="border-top: 1px solid #dddddd">
<?php						echo '<a href="'.$form_data['landing_page'].'" target="_blank">'.$form_data['landing_page'].'</a>' 
?>					</td>
				</tr>
<?php				
				if (isset($form_data['scenes']) && count($form_data['scenes']) > 0 )
				{
?>					<tr>
						<td style="border-top: 1px solid #dddddd">Scenes:</td>
						<td style="border-top: 1px solid #dddddd">
<?php							foreach($form_data['scenes'] as $scene_num=>$scene)
							{
								echo '<span class="label" style="font-size:x-small"><small >'.($scene_num+1).'</small></span> '.nl2br($scene).'<br>';
							}
?>						</td>
					</tr>
<?php				}

				if (isset($form_data['cta']) && $form_data['cta'] != '' && $form_data['product'] === 'Display')
				{
?>					<tr>
						<td style="border-top: 1px solid #dddddd">CTA:</td>
						<td style="border-top: 1px solid #dddddd">
<?php							echo ($form_data['cta'] == "other" ? ' "'.$form_data['cta_other'].'"' : '"'.$form_data['cta'].'"') 
?>						</td>
					</tr>
<?php				}

				if (isset($form_data['is_video']) && $form_data['is_video'] = "on")
				{
?>					<tr>
						<td style="border-top: 1px solid #dddddd">video:</td>
						<td style="border-top: 1px solid #dddddd">
<?php							echo ($form_data['features_video_youtube_url'] ? '<span class="muted" ><i class="icon-youtube-play"></i>  </span><a href="'.$form_data['features_video_youtube_url'].'" target="_blank">'.$form_data['features_video_youtube_url'].'</a><br>' : '');
							echo ($form_data['features_video_video_play'] ? '<span class="muted" style="color:#999999">video play: </span>'.str_replace('_',' ',$form_data['features_video_video_play']).'<br>' : '');
							echo ($form_data['features_video_mobile_clickthrough_to'] ? '<span class="muted" style="color:#999999">mobile click-through: </span>'.str_replace('_',' ',$form_data['features_video_mobile_clickthrough_to']).'<br>' : '');
?>						</td>
					</tr>
<?php				}

				if (isset($form_data['is_map']) && $form_data['is_map'] = "on")
				{
?>					<tr>
						<td style="border-top: 1px solid #dddddd">map locations:</td>
						<td style="border-top: 1px solid #dddddd">
<?php							echo ($form_data['features_map_locations'] ? nl2br($form_data['features_map_locations']) : '');
?>						</td>
					</tr>
<?php				}

				if (isset($form_data['is_social']) && $form_data['is_social'] = "on")
				{
?>					<tr>
						<td style="border-top: 1px solid #dddddd">social:</td>
						<td style="border-top: 1px solid #dddddd">
<?php							echo ($form_data['features_social_twitter_text'] ? '<span class="muted"><i class="icon-twitter"></i> <span style="font-size:x-small;color:#999999">twitter</span> </span>'.$form_data['features_social_twitter_text'].'<br><hr style="border-top: 1px solid #eeeeee">' : '');
							echo ($form_data['features_social_email_subject'] ? '<span class="muted"><i class="icon-envelope"></i> <span style="font-size:x-small;color:#999999">email subject </span> </span>'.$form_data['features_social_email_subject'].'<br>' : '');
							echo ($form_data['features_social_email_message'] ? '<span class="muted"><i class="icon-envelope"></i> <span style="font-size:x-small;color:#999999">email message </span></span>'.nl2br($form_data['features_social_email_message']).'<br><hr style="border-top: 1px solid #eeeeee">' : '');
							echo ($form_data['features_social_linkedin_subject'] ? '<span class="muted"><i class="icon-linkedin-sign"></i> <span style="font-size:x-small;color:#999999">linkedin subject </span></span>'.$form_data['features_social_linkedin_subject'].'<br>' : '');
							echo ($form_data['features_social_linkedin_message'] ? '<span class="muted"><i class="icon-linkedin-sign"></i> <span style="font-size:x-small;color:#999999">linkedin message </span></span>'.nl2br($form_data['features_social_linkedin_message']).'<br><hr style="border-top: 1px solid #eeeeee">' : '');
?>						</td>
					</tr>
<?php				}
			}
			elseif (isset($form_data['request_type']) && $form_data['request_type'] === 'Ad Tags')
			{
				if (isset($form_data['tag_320x50']) && $form_data['tag_320x50'] !== '')
				{
?>					<tr>
						<td style="border-top: 1px solid #dddddd">320x50 Tag:</td>
						<td style="border-top: 1px solid #dddddd">
<?php							echo '<xmp>'.$form_data['tag_320x50'].'</xmp>';
?>						</td>
					</tr>
<?php				}

				if (isset($form_data['tag_728x90']) && $form_data['tag_728x90'] !== '')
				{
?>					<tr>
						<td style="border-top: 1px solid #dddddd">728x90 Tag:</td>
						<td style="border-top: 1px solid #dddddd">
<?php							echo '<xmp>'.$form_data['tag_728x90'].'</xmp>';
?>						</td>
					</tr>
<?php				} 
				
				if (isset($form_data['tag_160x600']) && $form_data['tag_160x600'] !== '')
				{
?>					<tr>
						<td style="border-top: 1px solid #dddddd">160x600 Tag:</td>
						<td style="border-top: 1px solid #dddddd">
<?php							echo '<xmp>'.$form_data['tag_160x600'].'</xmp>';
?>						</td>
					</tr>
<?php				}
				
				if (isset($form_data['tag_336x280']) && $form_data['tag_336x280'] !== '')
				{
?>					<tr>
						<td style="border-top: 1px solid #dddddd">336x280 Tag:</td>
						<td style="border-top: 1px solid #dddddd">
<?php							echo '<xmp>'.$form_data['tag_336x280'].'</xmp>';
?>						</td>
					</tr>
<?php				}
				
				if (isset($form_data['tag_300x250']) && $form_data['tag_300x250'] !== '')
				{
?>					<tr>
						<td style="border-top: 1px solid #dddddd">300x250 Tag:</td>
						<td style="border-top: 1px solid #dddddd">
<?php							echo '<xmp>'.$form_data['tag_300x250'].'</xmp>';
?>						</td>
					</tr>
<?php				}
				
				if (isset($form_data['tag_custom']) && $form_data['tag_custom'] !== '')
				{
?>					<tr>
						<td style="border-top: 1px solid #dddddd">Custom Tag:</td>
						<td style="border-top: 1px solid #dddddd">
<?php							echo '<xmp>'.$form_data['tag_custom'].'</xmp>';
?>						</td>
					</tr>
<?php				} 
			}
			elseif (isset($form_data['request_type']))
			{
?>				<tr>
					<td style="border-top: 1px solid #dddddd">Landing Page:</td>
					<td style="border-top: 1px solid #dddddd">
<?php						echo '<a href="'.$form_data['landing_page'].'" target="_blank">'.$form_data['landing_page'].'</a>' 
?>					</td>
				</tr>	
<?php			}
		}
		elseif ($form_data['product'] === 'Preroll' && $form_data['preroll_video_url'] !== '')
		{
?>			<tr>
				<td style="border-top: 1px solid #dddddd">Preroll Video URL:</td>
				<td style="border-top: 1px solid #dddddd">
<?php					echo '<a href="'.$form_data['preroll_video_url'].'" target="_blank">'.$form_data['preroll_video_url'].'</a>' 
?>				</td>
			</tr> 
<?php		}
		if ($form_data['product'] === 'Preroll')
		{
?>			<tr>
				<td style="border-top: 1px solid #dddddd">Landing Page:</td>
				<td style="border-top: 1px solid #dddddd">
<?php					echo '<a href="'.$form_data['landing_page'].'" target="_blank">'.$form_data['landing_page'].'</a>' 
?>				</td>
			</tr>	
<?php		}

		if (isset($form_data['creative_files']) && count($form_data['creative_files']) > 0 )
		{
?>			<tr>
				<td style="border-top: 1px solid #dddddd">Creative Files:</td>
				<td style="border-top: 1px solid #dddddd">
<?php					foreach($form_data['creative_files'] as $creative_file)
					{
						echo '<a href="'. $creative_file['url'] .'" target="_blank" title="Right click and select \'Save As...\'">'. $creative_file['name'] .' <i class="icon icon-share"></i></a><br>';
					}
?>				</td>
			</tr>
<?php 		}
		if ($form_data['product'] === 'Display' && $form_data['request_type'] == "Upload Own")
		{	
?>
			<tr>
				<td style="border-top: 1px solid #dddddd">Requester Email:</td>
				<td style="border-top: 1px solid #dddddd">
<?php					echo $form_data['requester_email']; 
?>				</td>
			</tr>
<?php		}

		if (isset($form_data['has_variations']) && $form_data['has_variations'] == "on")
		{
?>			<tr>
				<td style="border-top: 1px solid #dddddd">Location Variations: </td>
				<td style="border-top: 1px solid #dddddd"><?php
					echo $form_data['variation_spec']."<br>"; 
					if(isset($form_data['has_variations']) && $form_data['has_variations'] == "on"){
						foreach($form_data['variation_names'] as $idx => $variation_name)
						{
							if($variation_name != "" && $form_data['variation_details'][$idx] != "")
							{
								echo '<span class="label" style="font-size:x-small"><small >'.($idx+1).'</small></span> '.nl2br($variation_name.' - '.$form_data['variation_details'][$idx]).'<br>';
							}
						}
					} 
					else
					{
						echo '<span class="label label-warning"> None specified</span>';
					}
?>				</td>
			</tr>
<?php		}
?>
<?php		if (isset($form_data['other_comments']) && $form_data['other_comments'] !== '')
		{
?>			<tr>
				<td style="border-top: 1px solid #dddddd">Other Comments:</td>
				<td style="border-top: 1px solid #dddddd">
<?php					echo nl2br($form_data['other_comments']);
?>				</td>
			</tr>
<?php		}
?>	</table>
</div>