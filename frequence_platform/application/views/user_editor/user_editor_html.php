<?php
if($user_role == "admin")
{
	$role_options = array(
			'ADMIN' => 'Admin',
			'OPS' => 'Ops',
			'BUSINESS' => 'Advertiser',
			'SALES' => 'Partner',
			'CREATIVE' => 'Creative'
			);
}
else if($user_role == "ops")
{
	$role_options = array(
			'OPS' => 'Ops',
			'BUSINESS' => 'Advertiser',
			'SALES' => 'Partner',
			'CREATIVE' => 'Creative'
			);
}
else
{
	$role_options = array(
			'BUSINESS' => 'Advertiser',
			'SALES' => 'Partner',
			);
}
?>


<link href="/libraries/external/select2/select2.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="/libraries/external/DataTables-1.10.2/media/css/jquery.dataTables.css">
<link rel="stylesheet" type="text/css" href="/libraries/external/DataTables-1.10.2/extensions/FixedHeader/css/dataTables.fixedHeader.min.css">
<link rel="stylesheet" type="text/css" href="/libraries/external/DataTables-1.10.2/extensions/FixedHeader/css/dataTables.fixedHeader.min.css">
<link rel="stylesheet" type="text/css" href="/libraries/external/bootstrap-switch-3.3.2/css/bootstrap2/bootstrap-switch.min.css">
<link rel="stylesheet" type="text/css" href="/assets/css/searchHighlight.css">

<style type="text/css">
	table.dataTable thead th.sorting,
	table.dataTable thead th.sorting_asc,
	table.dataTable thead th.sorting_desc
	{
		text-align: left;
		padding-left: 12px;
	}
	#user_editor_table .blank_org
	{
		text-align: left;
	}
	#user_editor_header
	{
		float:left;
		margin-bottom:42px;
		padding:0px;
	}
	#user_editor_header h3
	{
		line-height:0px;
	}

	.user_table_checkbox
	{
		text-align:center;
	}

	.user_edit_error_box
	{
		width:40%;
		left: 0px;
		right: 0px;
		display: none;
		position: fixed;
		margin-left: auto;
		margin-right: auto;
		margin-top: 0px;
		margin-bottom: 0px;
		z-index: 200;
	}
	.user_table_ban_switch
	{
		text-align:center;
		min-width: 0px;
	}

	.bootstrap-switch.bootstrap-switch-mini
	{
		min-width: 0px;
	}

	.user_table_user_name > a:hover
	{
		color:#0088cc;
		text-decoration: none
	}
	.user_table_user_name > a
	{
		color: inherit;
	}
	.modal.large
	{
		width: 70%;
		margin-left:-35%;
	}
	.modal-body
	{
		max-height: 475px;
	}
	.org > div.popover > .popover-inner > .popover-content
	{
		font-size: 9px;
		width:110%;
	}
	.loading_img
	{
		padding-top: 10px;
		padding-bottom: 10px;
		display:block;
		margin-left: auto;
		margin-right: auto;
	}
	.user_edit_message_box
	{
		position: fixed;
		top:75px;
		left:32%;
		width:600px;
		margin-left:auto;
		margin-right:auto;
		z-index:1049;
	}
	.long_name_wrap
	{
		word-break:break-all;
	}
	small
	{
		color:#777777;
	}
	#user_editor_download_bulk_users{
		float:right;
		margin-left: 20px;
	}
	#user_editor_download_bulk_users a{
		color:#ffffff;
		text-decoration:none;
	}
	#user_edit_modal_footer .btn{
		text-align:right;
	}
	#user_edit_modal_footer .btn{
		padding:8px 35px;
		margin-left: 15px;
	}
	.row{
		margin-left:0px;
	}
	.permission_toggle{
		display: block;
		float:left;
		margin-left: 15px;
		width: 235px;
	}
	.control-group-ext{
		margin-left:83px;
		width:510px;
	}
	.control-ext{
		margin-left:0px;
	}
	.control-group{
		clear:both;
		width: 600px;
	}
	.span5, .span6{
		width:100%;
		margin-left: 0px;
	}
	.form-horizontal .control-label {
		width:219px;
		margin-right: 15px;
	}
	.form-horizontal .controls {
		margin-left: 0px;
		width: 500px;
		text-align: left;
	}
	.modal.large {
		width: 50%;
		margin-left: -25%;
	}
	.select2-container a{
		text-align: left;
	}
</style>

<div class="container" style="width:1400px;">
	<div class="user_edit_message_box">
		<div id="u_message_box" class="alert alert-error" style="display:none;">
			<button type="button" class="close">&times;</button>
			<div id="u_message_box_content"></div>
		</div>
	</div>
	<div width="100%">
		<div id="user_editor_header">
			<h3>User Management</h3>
		</div>
		<div id="user_editor_download_bulk_users">
			<a id="bulk_download_users" class="btn btn-inverse" type="button" href="/user_editor/download_users" target="_blank"><i class="icon-download-alt icon-white"></i>&nbsp;Bulk Users Data</a>
		</div>
		<?php if($can_use_register){ ?>
		<div style="float: right;">
			<a class="btn btn-success" type="button" id="new_user_button" href="/register" target="_blank" ><i class="icon-plus icon-white"></i> New User</a>
		</div>
		<?php } ?>
		<br>
		<div id="user_editor_body"></div>
		<table id="user_editor_table" class="display" width="100%">
		</table>
	</div>
	<br>
	<img id="loading_img" class="loading_img" src="/images/loadingImage.gif" alt="">

<!-- ban user modal -->
	<div id="ban_user_modal" class="modal hide fade" aria-hidden="true">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			<h3 id="ban_user_modal_header">Ban User</h3>
		</div>
		<div id="ban_user_modal_body" class="modal-body">
			<div id="ban_user_body_content" class="bulk_download_content_div">
			</div>

		</div>
		<div id="ban_user_modal_footer" class="modal-footer">
		</div>
	</div>

<!-- single user edit modal -->
	<div id="user_edit_modal" class="modal hide fade large" aria-hidden="true">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			<h3 id="user_edit_modal_header">Edit User</h3>
		</div>
		<img id="user_edit_modal_body_loading_img" class="loading_img" src="/images/loadingImage.gif" alt="">
		<div id="user_edit_modal_body" class="modal-body">
			<div id="user_edit_message_box" class="alert alert-error user_edit_error_box">
				<button type="button" class="close">&times;</button>
				<div id="user_edit_message_box_content"></div>
			</div>
			<div class='form-horizontal'>
				<div class="row" align="middle" style="position:relative;">
					<div class="span5">
						<div id="user_edit_field_first_name" class="control-group">
							<label class="control-label" for="user_edit_first_name">First</label>
							<div class="controls">
								<input type="text" id="user_edit_first_name">
							</div>
						</div>
						<div id="user_edit_field_last_name" class="control-group" >
							<label class="control-label" for="user_edit_last_name">Last</label>
							<div class="controls">
								<input type="text" id="user_edit_last_name">
							</div>
						</div>
						<div id="user_edit_field_email" class="control-group">
							<label class="control-label" for="user_edit_email">Email</label>
							<div class="controls">
								<input type="text" id="user_edit_email">
							</div>
						</div>
						<div id="user_edit_field_role" class="control-group">
							<label class="control-label" for="user_edit_role"><i class="icon-user"></i> User Role</label>
							<div class="controls">
								<?php echo form_dropdown("user_edit_role", $role_options, 'admin', "id=\"user_edit_role\" onchange=\"handle_user_edit_role_change(this.value);\"");?>
							</div>
						</div>
						<div id="user_field_role_dropdown_other_than_client_agency">
							<?php echo form_dropdown("user_edit_role_except_client_agency", $role_options, 'admin', "id=\"user_edit_role_except_client_agency\" onchange=\"handle_user_edit_role_change(this.value);\"");?>
						</div>
						<div id="user_edit_field_advertiser" class="control-group">
							<label class="control-label" for="user_edit_partner"><i class="icon-building"></i> Advertiser</label>
							<div class="controls">
								<input type="text" id="user_edit_advertiser">
							</div>
						</div>
						<div id="user_edit_field_partner" class="control-group">
							<label class="control-label" for="user_edit_partner"><i class="icon-group"></i> Partner</label>
							<div class="controls">
								<input type="text" id="user_edit_partner">
							</div>
						</div>
						<div class="control-group control-group-ext" >
							<div class="controls control-ext" >
								<?php if($report_permissions['are_placements_accessible']){ ?>
								<label class="permission_toggle">
									<input type="checkbox" id="user_edit_check_placements"/> can view placements in reports?
								</label>
								<?php } ?>
								<?php if($report_permissions['are_screenshots_accessible']){ ?>
								<label class="permission_toggle">
									<input type="checkbox" id="user_edit_check_screenshots"/> can view screenshots in reports?
								</label>
								<?php } ?>
								<?php if($report_permissions['are_ad_interactions_accessible']){ ?>
								<label class="permission_toggle" >
									<input type="checkbox" id="user_edit_check_engagements"/> can view engagements in reports?
								</label>
								<?php } ?>
								<label class="permission_toggle" >
									<input type="checkbox" id="user_edit_check_creative"/> access to creative request?
								</label>
								<label class="permission_toggle" >
									<input type="checkbox" id="user_edit_check_proposals"/> can view proposals?
								</label>
								<label class="permission_toggle" id="user_edit_sample_ads">
									<select id="user_edit_select_sample_ads"/>
										<option value="">no access to sample ads</option>
										<option value="manager">can manage sample ads</option>
										<option value="builder">can build &amp; manage sample ads</option>
									</select>
								</label>
							</div>
						</div>
						<div id="user_edit_owned_partners_div" class="control-group">
							<label class="control-label" for="user_edit_owned_partner">Partner owner for</label>
							<div class="controls">
								<input type="text" id="user_edit_owned_partners">
							</div>
						</div>
					</div>
					<div id="user_edit_contact_info" class="span6">
						<legend>Proposal Contact Info<small> optional</small></legend>
						<div class="control-group">
							<label class="control-label" for="user_edit_contact_addr">Address</label>
							<div class="controls">
								<input type="text" id="user_edit_contact_addr">
							</div>
						</div>
						<div class="control-group">
							<label class="control-label" for="user_edit_contact_addr_line_two">Address (Line 2)</label>
							<div class="controls">
								<input type="text" id="user_edit_contact_addr_line_two">
							</div>
						</div>
						<div class="control-group">
							<label class="control-label" for="user_edit_contact_city">City</label>
							<div class="controls">
								<input type="text" id="user_edit_contact_city">
							</div>
						</div>
						<div class="control-group">
							<label class="control-label" for="user_edit_contact_state">State/Province</label>
							<div class="controls">
								<input type="text" id="user_edit_contact_state">
							</div>
						</div>
						<div class="control-group">
							<label class="control-label" for="user_edit_contact_zip">Zip/Postal Code</label>
							<div class="controls">
								<input type="text" id="user_edit_contact_zip">
							</div>
						</div>
						<div class="control-group">
							<label class="control-label" for="user_edit_contact_phone">Phone Number</label>
							<div class="controls">
								<input type="text" id="user_edit_contact_phone">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div id="user_edit_modal_footer" class="modal-footer"></div>
	</div>
