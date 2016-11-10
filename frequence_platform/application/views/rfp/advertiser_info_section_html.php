<!-- advertiser section -->
<div id="advertiser_info_section">
	<div class="pull-left">
		<a href="/rfp/create/<?php echo $unique_display_id; ?>" class="tooltipped" data-tooltip="Opportunity Details"><i class="icon-arrow-left"></i></a>
        <?php echo $advertiser_org_name . (empty($proposal_name) ? "" : " : ".$proposal_name); ?>
	</div>
	<div class="pull-right">
		Presented on behalf of <?php echo $account_executive_data['text']; ?>
		<input type="checkbox" id="cc_owner" class="filled-in" <?php echo $owner_is_submitter ? '' : 'checked'; ?> />
		<label for="cc_owner" style="display: <?php echo $owner_is_submitter ? 'none' : 'inline-block'; ?>">Email Opportunity Owner?</label>
	</div>
</div>
