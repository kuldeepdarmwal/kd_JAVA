<?php
?>
<div id="launch_io_message_box" class="alert">
	<button type="button" class="close">×</button>
	<span id="launch_io_message_box_content"></span>
</div>

<div class="launch_io_campaign_list_container">
	<?php foreach($campaigns as $campaign) { ?>
		<div class="launch_io_campaign" data-campaign="<?php echo $campaign['id'];?>">
			<span class="launch_io_advertiser_name">
				<?php echo $campaign['adv_name']; ?>
			</span>
			<br>
			<span class="launch_io_campaign_name">
				<?php echo $campaign['Name']; ?>
			</span>
			<br>
			<span class="btn btn-danger btn-small trash_campaign_button"><i class="icon-trash"></i></span>
		</div>
	<?php } ?>
</div>
<div class="launch_io_content_container">
	<div id="no_campaign_selected_div">
		No Campaign Selected
	</div>
	<iframe id="campaign_setup_iframe" style="display:none;"></iframe>
	<div id="notes_section" class="launch_io_notes_container" style="display:none;">
		<h5>IO Notes <?php if($include_rtg){ echo '<span class="label label-success">Include Retargeting</span>';} else {echo '<span class="label label-important">Do Not Include Retargeting</span>';} ?></h5>
		<textarea id="notes_text" readonly><?php if($has_notes) { echo $notes; } ?></textarea>
	</div>
</div>


<div id="confirm_trash_campaign_modal" class="modal hide fade" tabindex="-1">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">×</button>
		<h3>Confirm Action</h3>
	</div>
	<div id="confirm_trash_campaign_modal_body" class="modal-body">
		
	</div>
	<div class="modal-footer">
		<a class="btn" data-dismiss="modal">No</a>
		<button class="btn btn-success" data-dismiss="modal" onclick="trash_campaign(trash_campaign_row);">Yes</button>
	</div>
</div>
