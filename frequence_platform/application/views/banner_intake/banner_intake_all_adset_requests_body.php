<?php
?>
<div id="adset_requests_body_container">
		<div id="adset_requests_message_box" class="alert">
			<button type="button" class="close">×</button>
			<span id="adset_requests_message_box_content"></span>
		</div>
	<div id="adset_requests_header_section">
		<h3 style="display:inline-block;">Creative Requests</h3>
		<a href="/creative_requests/new" id="adset_request_link_button" class="btn btn-link btn-large"><i class="icon-picture icon-white"></i> <strong>New Creative Request</strong></a>
	</div>
	<div id="adset_requests_body_section">
	    <table id="adset_requests_table" cellspacing="0" class="order-column hover row-border" style="display:none;"></table>
	    <?php if($user_role == 'admin') { ?>
	    <label class="checkbox" style="background-color: #FBEFF2; float:left"><input type="checkbox"  name="demo_show" id="demo_checkbox" value="show"/>Show Demo Data</label>
	    <?php } ?>
	</div>
</div>
<form id="existing_adset_requests_form" action="/banner_intake" target="_blank" method="post">
	<input id="adset_requests_existing_form_method" type="hidden" name="submission_method" value="" />
	<input id="adset_requests_form_adset_request_id" type="hidden" name="adset_request_id" value="" />
</form>

<div id="adset_requests_preview_modal" class="modal hide fade" tabindex="-1">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">×</button>
		<h3>Adset Request Preview</h3>
	</div>
	<div class="modal-body">
		
	</div>
	<div class="modal-footer">
		<a class="btn" data-dismiss="modal">Close</a>
	</div>
</div>
