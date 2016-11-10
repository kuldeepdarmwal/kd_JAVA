<?php
?>
<div id="pv_body_container">
		<div id="pv_message_box" class="alert">
			<button type="button" class="close">×</button>
			<span id="pv_message_box_content"></span>
		</div>
	<div id="pv_header_section">
		<h3 style="display:inline-block;">
			Proposals
			<a id="csv_download_button" title="Download CSV" class="btn btn-link"><i class="icon-download"></i></a>
		</h3>
		<a href="/rfp/gate" id="pv_rfp_link_button" class="btn btn-link btn-large"><i class="icon-paste"></i> <strong>New Proposal</strong></a>
	</div>
	<div id="pv_body_section">
		<table id="pv_proposals_table" cellspacing="0" class="order-column hover row-border" style="display:none;"></table>
		<?php if($user_role == 'admin') { ?>
		<label class="checkbox" style="background-color: #FBEFF2; float:left"><input type="checkbox"  name="demo_prop_checkbox" id="demo_prop_checkbox" value="show" />Show Demo Data</label>
		<?php } ?>
	</div>
</div>

<form id="pv_create_io_form" action="/insertion_orders/create" method="post">
	<input id="pv_create_io_form_mpq_id" type="hidden" name="mpq_id" value="" />
	<input id="pv_create_io_form_option_id" type="hidden" name="option_id" value="" />
	<input id="pv_creatve_io_form_source" type="hidden" name="source" value="rfp" />
</form>

<form id="download_inline_csv_form" action="/proposals/download_csv" method="POST" style="display:none;" target="_blank">
	<input type="hidden" id="file_name" name="file_name" value="" />
	<input type="hidden" id="csv_data" name="csv_data" value="" />
</form>

<div id="pv_preview_modal" class="modal hide fade" tabindex="-1">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">×</button>
		<h3>Proposal Preview</h3>
	</div>
	<div class="modal-body">

	</div>
	<div class="modal-footer">
		<a class="btn" data-dismiss="modal">Close</a>
	</div>
</div>

<div id="pv_io_modal" class="modal hide fade" tabindex="-1">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">×</button>
		<h3>Start Insertion Order</h3>
	</div>
	<div class="modal-body">
		<div id="pv_io_modal_body_content"></div>
		<img id="pv_io_modal_loading_img" src="/images/loadingImage.gif" alt="">
	</div>

</div>
