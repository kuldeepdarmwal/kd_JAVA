<style>
#advertisers_table_filter {
    float: 'right';
}
.hover_row{
	padding:'3px';
}
.hover_row:hover{
background: #D7D7D7;
}

.tooltip.in
{
	opacity: '1';
	font-size: '13px';
}
.tooltip-inner
{
	max-width: '100%';
}
table.dataTable thead th, table.dataTable thead tfooter td {
    padding: '10px 18px';
    border-bottom: '1px solid #ddd';
}
table.dataTable.no-footer {
    border-bottom: '1px solid #ddd';
}
.access_manager_container
{
        min-width: 980px;
        margin-left: auto;
        margin-right: auto;
        width: 95%;
}
.access_manager_main_content
{        
        width: 100%;
}
#csv_download_button
{
        text-decoration: none;
}
</style>
<script type="text/javascript">
var advertiser_group_access_flag=false;
var user_role = '<?php echo $role; ?>';
<?php
if ($this->vl_platform_model->is_feature_accessible($user_id, 'advertiser_group')) {
?>
	advertiser_group_access_flag=true;
<?php 
} 
?>
</script>


<div class="access_manager_container">	
	<div class="row-fluid">
                <div class='span12'>
                        <h4> 
                                <i class="icon-group"> </i>
                                Access Manager
                                <a id="csv_download_button" title="Download CSV" href="/access_manager/download_csv" target="_blank" class="btn btn-link"><i class="icon-download"></i></a>
                        </h4>
                </div>
        </div>
	<div>
		<div id="access_manager_main_content">
			<div class="row-fluid">
		 		<div  class="span12">
				<span data-toggle='tooltip' title='Check/Uncheck all'>
					<input style='display: inline' type='checkbox' id='toggle_all_check' onclick='toggle_all_add_users_to_adv(this)'>&nbsp;
				</span>
				<span class="dropdown" data-toggle='tooltip' title='Bulk Add and Remove users'>
				  <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"><i class="icon-list-ul"> </i> &nbsp;&nbsp;
				    <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
				    <li><a href="#" onclick='add_user_to_adv()'>Add user to selected Advertisers</a></li>
				    <li><a href="#" onclick='remove_user_from_adv()'>Remove user from selected Advertisers</a></li>
				  </ul>
				</span>
				<script type="text/javascript">
				if (advertiser_group_access_flag) 
				{
					document.write("<span data-toggle='tooltip' title='Advertiser Group'> "+
						"<a href='#' id='adv_group' style='text-decoration: none;' class='btn btn-link btn-large' onclick='add_edit_advertiser_groups(1)'><i class='icon-plus-sign'> </i> <strong><small>New Group</small></strong></a></span>");
				}
				</script>
			 </div> 
			 <div  class="span9" id="tags_alert_div"><br><br>
 			</div> 
			<div class="row-fluid">
				<div class="span12">
					<table id="advertisers_table" class="table table-bordered" style='font-size:11px' cellspacing="0" >
		 <thead>
                        <tr>
                                <th>Advertiser</th>
                                <th>Partner</th>
                                <th>Advertiser Group</th>                
                                <th>Advertiser Created Date</th>
                                <th>Agency Name</th>
                                <th>UL ID / Client ID</th>                                
                                <th>
                                        <div class="span12">
                                                <div class="span4">Linked Users Name and Email</div>                                                
                                                <div class="span2">Login</div>
                                                <div class="span2">Created</div>
                                                <div class="span2">Type</div>
                                                <div class="span2">Actions</div>
                                        </div>
                                </th>
                        </tr>
                </thead>
                <tbody></tbody>
		</table>
                                        <table id="advertisers_export_table" class="table table-bordered" style='display:none' cellspacing="0" ></table>
				</div>
			</div>
			<div class="row">
				<div class="span12">
				</div>
			</div>
		</div>
		<img id="loading_img" src="/images/mpq_v2_loader.gif" style="display:none"/>
	</div>
</div>
<div id="edit_accounts_modal" class="modal hide fade" style="overflow:hidden;" role="dialog" aria-labelledby="accounts_modal_label" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h4 id="accounts_modal_label"><i class="icon-edit "></i> <span id='edit_accounts_modal_title'>Advertiser Name </span> </h3>
  </div>
  <div  class="modal-body">
	<fieldset id="edit_accounts_form_modal_body">
		<label>Linked Accounts: </label>
		<input type="hidden" id="accounts_select" class="span4" />
	</fieldset>
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
    <button id="update_accounts_button" class="btn btn-primary">Update</button>
  </div>
</div>
<form id="edit_user_form" name='edit_user_form'>
<div id="edit_modal" class="modal fade" role="dialog"  style="width:600px" aria-labelledby="edit_modal_label" >
	<div class="modal-dialog modal-lg">
	<div class="modal-content">
    <div class="modal-header" >
        <button type="button" onclick="close_modal()" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h5 id="myModalLabel"><div id='modal_window_title'>User Details</div></h5>
    </div>
    <div class="modal-body">
        <p></p>
    </div>   
    </div>
    </div>
</div>
</form>
