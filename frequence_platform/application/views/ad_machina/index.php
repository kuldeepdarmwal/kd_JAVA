		<div class="container sample_ad_manager">
			<div class="vab_common_header">
				<h3>
					Video Ad Manager
	<?php if($has_advertisers){ ?>
					<a id="csv_download_button" title="Download CSV" class="btn btn-link"><i class="icon-download"></i></a>
	<?php } ?>
				</h3>
	<?php if($has_edit_permission){ ?>
				<a href="/vab/videos" id="new_sample_ad_link_button" class="right-link btn btn-link btn-large"><i class="icon-play-circle"></i> <strong>New Video Ad Preview</strong></a>
	<?php } ?>
			</div>
<?php if($has_advertisers){ ?>
			<table id="spec_ad_table" class="order-column hover row-border"></table>
<?php }else{ ?>
			<p class="no_content_message">No Video Ads have been prepared for your clients. Contact your manager to request a Video Ad Preview.</p>
<?php } ?>
		</div>
		<form id="download_inline_csv_form" action="/vab/download_csv" method="POST" style="display:none;" target="_blank">
			<input type="hidden" id="file_name" name="file_name" value="" />
			<input type="hidden" id="csv_data" name="csv_data" value="" />
		</form>

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
		"last_page_visited": "/vab"
	});
	mixpanel.people.set_once({
		"$created": "<?php echo $mixpanel_info['page_access_time']; ?>",
		"page_views": 0
	});
	mixpanel.people.increment("page_views");

</script>
<?php endif; ?>
