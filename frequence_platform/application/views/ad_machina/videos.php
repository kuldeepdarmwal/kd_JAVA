		<div class="container">
			<div class="video_gallery ad_machina_body">
				<div class="vab_common_header">
					<h3>Video Gallery</h3>
				</div>
				<form action="builder" class="sticky">
					<label for="video_account">Account:
						<select id="video_account">
							<option value="">All</option>
							<?php
							foreach($accounts as $account)
							{
								$selected = $account['frq_third_party_account_id'] == $frq_third_party_account_id ? ' selected' : '';
								echo "<option value=\"{$account['frq_third_party_account_id']}\"{$selected}>{$account['name']}</option>";
							}
							?>
						</select>
					</label>
				</form>
				<p>Hover a video to preview. Click to create an ad.</p>
				<ul class="video_list">
					<?php
					// match videos_list.php exactly
					foreach($videos as $video)
					{
						echo "<li class=\"spot_video\""
							. "data-video-id=\"{$video['video_creative_id']}\""
							. "data-mp4=\"{$video['link_mp4']}\""
							. "data-webm=\"{$video['link_webm']}\">"
								. "<img src=\"{$video['link_thumb']}\">"
								. "<label class=\"\">{$video['name']}</label>"
							. "</li>\n";
					}
					?>
				</ul>
			</div>
		</div>

		<script type="x-template" id="msg-loading"><h4>Loading your video ad.</h4><i class="fa fa-refresh fa-spin"></i></script>
		<script type="x-template" id="msg-filtering"><h4>Loading video list.</h4><i class="fa fa-refresh fa-spin"></i></script>

<?php if($mixpanel_info['user_unique_id']) : ?>
<script type="text/javascript">

	var timezone_offset = (new Date().getTimezoneOffset() / 60) * -1; // getTimezoneOffset returns offset in minutes, so divide by 60

	mixpanel.identify("<?php echo $mixpanel_info['user_unique_id']; ?>");
	mixpanel.people.set({
		"$first_name": "<?php echo $mixpanel_info['user_firstname']; ?>",
		"$last_name": "<?php echo $mixpanel_info['user_lastname']; ?>",
		"$email": "<?php echo $mixpanel_info['user_email']; ?>",
		"user_type": "<?php echo $mixpanel_info['user_role']; ?>",
		"is_super": "<?php echo $mixpanel_info['user_is_super']; ?>",
		"partner_id": "<?php echo $mixpanel_info['user_partner_id']; ?>",
		"partner_name": "<?php echo $mixpanel_info['user_partner']; ?>",
		"cname": "<?php echo $mixpanel_info['user_cname']; ?>",
		"advertiser_name": "<?php echo $mixpanel_info['user_advertiser_name']; ?>",
		"advertiser_id": "<?php echo $mixpanel_info['user_advertiser_id']; ?>",
		"timezone_offset": timezone_offset,
		"last_page_visit": "<?php echo $mixpanel_info['page_access_time']; ?>",
		"last_page_visited": "/vab/videos"
	});
	mixpanel.people.set_once({
		"$created": "<?php echo $mixpanel_info['page_access_time']; ?>",
		"page_views": 0
	});
	mixpanel.people.increment("page_views");

</script>
<?php endif; ?>
