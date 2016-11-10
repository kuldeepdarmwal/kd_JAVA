<div class="video_spec_ad_preview">
	<div class="headline">
		Don’t miss the impact of online video!
	</div>
	<div class="full-ad-row">
		<div class="full-ad">
			<!--h4 class="advertiser_name">Sample Preview<?php echo $demo_title ? " for $demo_title" : ''; ?>:</h4--><?php // OOPS!? TODO: This is not on the new layout. I think it needs to stick around ?>
			<div class="claim">
				Extend the reach of your television campaign by combining TV and online advertising.
			</div>
			<div class="specad">
				<?php if(!empty($video_ad_id)) { ?>
				<iframe src="/vab/render/<?php echo $video_ad_id ?>?preview=1" id="d1234567890" class="requires-video requires-csstransforms" width="300" height="250" margin="0" border="0" align="middle"></iframe>
				<div class="no-support-message">
					<h2>Ooops!</h2>
					<p>This preview requires a browser that supports HTML Video (e.g. Internet Explorer 9 or newer).</p>
					<p>If this were your live ad, your backup image would be shown here.</p>
				</div>
				<?php } else { ?>
				<div class="headline">
					<h2>Ooops!</h2>
					<hr>
					<p>This link doesn't match an ad we have on record.</p>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="contextual-preview">
			<?php if(!empty($video_ad_id)) { ?>
			<div class="preview-container">
				<h3>Example of your video Ad on devices:</h3>
				<div class="laptop">
					<iframe src="/vab/render/<?php echo $video_ad_id ?>?preview=1" id="a1234567890" class="preview-ad requires-video requires-csstransforms" width="300" height="250" margin="0" border"0"></iframe>
				</div>
				<div class="tablet">
					<iframe src="/vab/render/<?php echo $video_ad_id ?>?preview=1" id="b1234567890" class="preview-ad requires-video requires-csstransforms" width="300" height="250" margin="0" border"0"></iframe>
				</div>
				<div class="phone">
					<iframe src="/vab/render/<?php echo $video_ad_id ?>?preview=1" id="c1234567890" class="preview-ad requires-video requires-csstransforms" width="300" height="250" margin="0" border"0"></iframe>
				</div>
			</div>
			<?php } ?>
		</div>
	</div>
	<div class="assertions">
		<div class="assertion">
			Ad recall jumps when TV and Online are combined.
			<small>Based on Nielsen 2014 IAB Online Video Study.</small>
		</div>
		<div class="recall-graph bar-small">
			<div class="bar">46<sup>%</sup></div>
			<div class="bar-label">TV ad recall</div>
		</div>
		<div class="recall-graph bar-tall">
			<div class="bar">55<sup>%</sup></div>
			<div class="bar-label">TV + Online<br>ad recall</div>
		</div>
	</div>

<?php if($mixpanel_info && $mixpanel_info['user_unique_id']) : ?>
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
		"last_page_visited": "/vab/preview"
	});
	mixpanel.people.set_once({
		"$created": "<?php echo $mixpanel_info['page_access_time']; ?>",
		"adbuilder_preview": 0
	});
	mixpanel.people.increment("adbuilder_preview");

</script>
<?php endif; ?>
</div>
