<script src="/libraries/external/select2/select2.js"></script>
<link rel="stylesheet" href="/libraries/external/font-awesome/css/font-awesome.css"/>
<script type="text/javascript" src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.js"></script>
<script type="text/javascript" src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.rowGrouping.js"></script>
<script type="text/javascript" src="/js/datatable_csv_export.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<style>
#advertisers_table thead th
{
	background-color: #f9f9f9;
	color: #0088cc;
	border-bottom-color: #ddd;
}

#advertisers_table select
{
	width: auto;
}

#advertisers_table .pv_action_container, #advertisers_table .pv_creation_time_container
{
	white-space: nowrap;
	overflow: hidden;
	display: inline-block;
	width: 100%;
	text-align: center;
}

#advertisers_table .pv_elastic_column
{
	/*width: 40%;*/
        width: 5%;
}

#advertisers_table .pv_elastic_column_one
{
        width: 15%;
}


#advertisers_table .pv_auto_column
{
	width: auto;
}

#advertisers_table
{
	min-width: 100%;
	border-bottom-color: #ddd;
}

#advertisers_table a.btn, #advertisers_table button.btn
{
	color: grey;
	text-decoration: none;
}

#disabled_btn
{
	color: grey;
	text-decoration: none;
}

#advertisers_table a.btn.disabled
{
	color: #ddd;
	background-color: transparent;
}

#advertisers_table a.btn:not(.disabled):hover, #advertisers_table button.btn:hover
{
	color: #0088cc;
	text-decoration: none;
}

#advertisers_table a.btn, #advertisers_table button.btn {
    color: grey;
    text-decoration: none;
}
.wrap_word
{
        word-wrap: break-word;
}
.btn
{
        padding: 4px 5px;
}
a.adv_group_name
{
        color: #0088cc;
        text-decoration: none;
}
a.adv_group_name:hover
{
        color: #005580;
        text-decoration: none;
}
.column_adv_name
{
        padding-left: 10px;
}
.admin_key
{
        font-size: 16px;
        text-decoration: none;
        padding-left: 5px;
}
.add_user_session_error
{
	color: red;
	font-size: 14px;
	margin-left: 20px;
}
</style>
<script type="text/javascript">
var table_data = {};
var table;
var selected_row;
var row_data;
var g_the_table;
var am_table_data = <?php echo json_encode($access_manager_view_data); ?>;
showoverlay();
$(document).ready(function () {
    
        var access_manager_table_data = format_access_manager_table_data(am_table_data);
        var access_manager_table_columns = [
                {
                        data: "data_adv_name",
                        title: "Advertiser",
                        render: {
                                _: "formatted_adv_name",
                                sort: "sort"
                        },
                        type: "string",
                        "class": "pv_elastic_column_one pv_column_padding"
                },
                {
                        data: "data_partner_name",
                        title: "Partner",
                        render: {
                                _: "formatted_partner_name",
                                sort: "sort"
                        },
                        type: "string",
                        "class": "pv_elastic_column pv_column_padding"
                },
                {
                        data: "data_adv_group",
                        title: "Advertiser Group",
                        render: {
                                _: "formatted_adv_group",
                                sort: "sort"
                        },
                        type: "string",
                        "class": "pv_elastic_column pv_column_padding"
                },                
                {
                        data: "data_adv_created_date",
                        title: "Advertiser Created Date",
                        render: {
                                _: "formatted_adv_created_date",
                                sort: "sort"
                        },
                        type: "string",
                        "class": "pv_elastic_column"
                },
                {
                        data: "data_agency_name",
                        title: "Agency Name",
                        render: {
                                _: "formatted_agency_name",
                                sort: "sort"
                        },
                        type: "string",
                        "class": "pv_elastic_column pv_column_padding"
                },
                {
                        data: "data_ul_id",
                        title: "ULID / Client ID",
                        render: {
                                _: "formatted_ul_id",
                                sort: "sort"
                        },
                        type: "string",
                        "class": "pv_elastic_column pv_column_padding"
                },
                {
                        data: "data_adv_id",
                        //title: "Linked Advertiser & Agency Users",
                        render: {
                                _: "formatted_adv_id"
                        },
                        orderable: false,
                        type: "string",
                        "class": "pv_auto_column"
                }
        ];
    
 	g_the_table = $("#advertisers_table").dataTable({		
                "data": access_manager_table_data,
                "ordering": true,
                "order": [[0, "asc"]],
                "lengthMenu": [
                        [25, 50, 100],
                        [25, 50, 100]
                ],
                "columnDefs": [{width: '10%', targets: 1}],
		"drawCallback": function( settings ) {
                        $('[data-toggle="tooltip"]').tooltip();
                        document.getElementById('toggle_all_check').checked=false;
                },
		
		rowCallback: function(row, data, index) {},
                "columns": access_manager_table_columns		
	});

	$("#loading_img").hide();
	$("#main_content").show();
	hideoverlay();
        
        table = $('#advertisers_table').DataTable();
        $('#advertisers_table tbody').on( 'click', 'tr', function () {                
                selected_row = this;
                row_data = table.row(selected_row).data();
        } );
});

function format_access_manager_table_data(data)
{
        $.each(data, function(key, value)
        {
                var data_adv_cell = "<div class='row-fluid hover_row'><div class='span1'>"+
                                "<input type='checkbox' name='bulk_aid' id='bulk_aid_"+this.adv_id+"' value='"+this.adv_id+"' >"+
                                "</div><div class='span8 column_adv_name'>";
                			
                data_adv_cell += this.adv_name;
                
                data_adv_cell += "</div></div>";

                this.data_adv_name = {
                        formatted_adv_name: data_adv_cell,
                        sort: this.adv_name
                };
                
                var ag_name = "-";
                if (this.ag_name != null && this.ag_name != "")
                {
                        ag_name = "<span data-toggle='tooltip' title='Edit Adv Group'><a href='#' class='adv_group_name' data-trigger='hover' data-placement='top' data-title='Edit Adv Group' class='btn btn-link btn-large' onclick=\"add_edit_advertiser_group_popup('', '"+this.ag_id+"');\" ><strong>"+this.ag_name+"</strong></a></span>";                        
                }
                this.data_adv_group = {
                        formatted_adv_group: ag_name,
                        sort: ag_name
                };
                
                this.data_partner_name = {
                        formatted_partner_name: this.partner_name,
                        sort: this.partner_name
                };
                
                this.data_adv_created_date = {
                        formatted_adv_created_date: '<div class="pv_modal_column_container">'+ this.adv_created_date + '</div>',
                        sort: this.adv_created_date
                };
                
                this.data_agency_name = {
                        formatted_agency_name: this.agency_name,
                        sort: this.agency_name
                };
                
                this.data_ul_id = {
                        formatted_ul_id: this.ul_id,
                        sort: this.ul_id
                };

                var users_array = this.users;
                var users_cell = "";
                var power_users_count = 0;
                for (var ctr = 0; ctr < users_array.length; ctr++)
                {
                        var user_row = users_array[ctr];
                        if (user_row['is_power_user'] == '1')
                        {
                                power_users_count++;
                        }
                }
                for (var ctr=0; ctr < users_array.length; ctr++)
                {
                        var user_row = users_array[ctr];
                        if (user_row['user_id'] != '' && user_row['user_id'] != undefined)
                        {
                                users_cell += get_user_cell_html(user_row['first_name'], user_row['last_name'], user_row['email'], user_row['is_power_user'], 
                                        user_row['resend_link_flag'], user_row['role'], this.adv_id, this.adv_name, user_row['user_id'], "add", power_users_count, user_row['activation_email_date'], user_row['user_created_date']) ;                        
                        }
                }
                
                users_cell += "<div>"+
                            " <a href='#' data-trigger='hover' data-toggle='tooltip' data-placement='top' data-title='Add user' class='btn btn-link pv_new_rfp_button pv_tooltip pv_edit_rfp_button' onclick=\"edit_user('','','' , '', '', '"+
                            this.adv_id+"', '"+this.adv_name.replace("'" , "\\'")+"', '')\" ><span>+<i class='icon-user'></i></span></a></div>";
                
                this.data_adv_id = {
                        formatted_adv_id: users_cell,
                        sort: this.adv_id
                };
                
        });

        return data;
}

function edit_user(first_name, last_name, email, is_power_user, role, adv_id, adv_name, user_id)
{	
	var modal_window_title = '';
	if (user_id == '')
	{
		modal_window_title = "Add user for  '" + adv_name + "'";
		if (adv_name == '')
		{
			modal_window_title = 'Add user for selected Advertiser(s)';
		}
	}
	else
	{
		modal_window_title = 'Edit user';
	}
 	document.getElementById('modal_window_title').innerHTML = modal_window_title;
    var edit_content = '<div class="form-group" style="font-size: 11px"><div id="tags_alert_div_modal"><div><br></div></div>'; 
    if (user_id == '')
    {
    	edit_content += '<div class="row-fluid"><div class="span4">Email: </div><div class="span8"><input type="hidden" id="email_sel" name="email_sel" value="'+email+'"></div></div>';
    }
    edit_content += '<br><div id="fn_div" class="row-fluid"><div class="span4">First Name</div><div class="span8"><input type=text name="first_name" id="first_name" value="'+first_name+'"></div></div>'+
     '<div id="ln_div" class="row-fluid"><div class="span4">Last Name</div><div class="span8"><input type=text class="form-control" name="last_name" id="last_name" value="'+last_name+'"></div></div>'+
     '<div id="email_div" class="row-fluid"><div class="span4">Email</div><div class="span8"><input type="email" class="form-control" size="400" name="email" id="email" value="'+email+'"></div></div>';

    if (user_role != 'client') 
    {
		if (role == "CLIENT")
			edit_content += '<div id="client_div" class="row-fluid" style="height:40px"><div class="span4">Type</div><div class="span1">Client </div>'+
			'<div class="span1"><input type=radio name="client_type" id="client_type_client" value="CLIENT" checked> &nbsp;&nbsp;&nbsp;&nbsp;</div>'+
			'<div class="span1">Agency </div><div class="span1"><input type=radio name="client_type" id="client_type_agency" value="AGENCY"></div></div>';
		else
			edit_content += '<div id="client_div" class="row-fluid" style="height:40px"><div class="span4">Type</div><div class="span1">Client </div>'+
			'<div class="span1"><input type=radio name="client_type" id="client_type_client" value="CLIENT" > &nbsp;&nbsp;&nbsp;&nbsp;</div>'+
			'<div class="span1">Agency </div><div class="span1"><input type=radio name="client_type" id="client_type_agency" value="AGENCY" checked></div></div>';
	}
	else
	{
		edit_content += '<div id="client_div" class="row-fluid" style="height:40px"><input type="hidden" name="client_type" id="client_type_client" value="CLIENT" checked></div>';
	}	
	var checked_flag="";	
	if (is_power_user == "1")
		checked_flag="checked";

		edit_content +='<div id="is_power_user_div" class="row-fluid" style="height:20px"><div class="span4"></div><div class="span1">Admin </div>'+
		'<div class="span1"><input type="checkbox" name="client_admin" id="client_admin_admin" value="1" '+checked_flag+'> &nbsp;&nbsp;&nbsp;&nbsp;</div>'+
		'<div class="span2"></div><div class="span1"></div></div> ';

		edit_content +='<div> <br><button onclick="close_modal()" class="btn btn-small" data-dismiss="modal" aria-hidden="true">Cancel</button>&nbsp;'+  
		'<button data-dismiss="modal" aria-hidden="true" class="btn btn-success btn-small" id="edit_submit_btn" onclick="edit_user_save()">';

		edit_content +='Save';

	edit_content +='</button><span class="add_user_session_error hide">Session expired. Please login again.</span></div>';

	edit_content +='<input type=hidden id="adv_id" value="'+adv_id+'"><input type=hidden id="adv_name" value="'+adv_name+'"><input type=hidden id="user_id" value="'+user_id+'">';	
	
	$('#edit_modal .modal-body').html(edit_content);
	$('#edit_modal').modal();
	$('#edit_modal').removeAttr('tabindex');
	$('#email_sel').select2({
		multiple: false,
		width: '300px',
		placeholder: "Enter email",
		allowClear: true,
		minimumInputLength: 0,
		ajax:{
			type: 'POST',
			url: '/spectrum_account_data_loader/fetch_user_info_by_email_select2',
			dataType: 'json',
			data: function (term, page){
				term = (typeof term === "undefined" || term == "") ? "%" : term;

				return{
					q: term,
					page_limit: 10,
					page: page
				};
			},
			results: function(data)
			{
				if(data.result.length > 0)
				{
					$(".add_user_session_error").addClass("hide");
					return {results: data.result, more: data.more}
				}
				else
				{
					$(".add_user_session_error").removeClass("hide");
					$("#email_sel").select2("close");
					return false;
				}
			}
		}
	})
	.on("change",function(e)
	{
		var email_value = document.getElementById('email_sel').value;
		if (email_value == "*New*")
		{
			document.getElementById('fn_div').style.display='none';
			document.getElementById('ln_div').style.display='none';
			document.getElementById('email_div').style.display='block';
			//document.getElementById('is_power_user_div').style.display='block';
			document.getElementById('client_div').style.display='block';
		}
		else 
		{
			document.getElementById('fn_div').style.display='none';
			document.getElementById('ln_div').style.display='none';
			document.getElementById('email_div').style.display='none';
			//document.getElementById('is_power_user_div').style.display='none';
			document.getElementById('client_div').style.display='none';
			document.getElementById('email').value=document.getElementById('email_sel').value;
		}
	});

	if (email == null || email == '')
	{
		document.getElementById('fn_div').style.display='none';
		document.getElementById('ln_div').style.display='none';
		document.getElementById('email_div').style.display='none';
		//document.getElementById('is_power_user_div').style.display='none';
		document.getElementById('client_div').style.display='none';
	}
	else
	{
		document.getElementById('fn_div').style.display='block';
		document.getElementById('ln_div').style.display='block';
		document.getElementById('email_div').style.display='block';
		//document.getElementById('is_power_user_div').style.display='block';
		document.getElementById('client_div').style.display='block';
	}
}

function refresh_linked_advertisers_and_users(adv_id)
{
        //showoverlay();
        $.ajax({
                url: '/spectrum_account_data_loader/get_advertiser_data_access_manager_grid',
                type: 'POST',
                async: true,
                dataType: 'json',
                data: {
                        adv_id: adv_id
                },
                success: function(data, status) {
                        if(data.is_success)
                        {
                                var users_array = data.adv_users.users;                                
                                var users_cell = "";                                                        
                                var power_users_count = 0;
                                for (var ctr = 0; ctr < users_array.length; ctr++)
                                {
                                        var user_row = users_array[ctr];
                                        if (user_row['is_power_user'] == '1')
                                        {
                                                power_users_count++;
                                        }
                                }
                                for (var ctr=0; ctr < users_array.length; ctr++)
                                {
                                        var user_row = users_array[ctr];
                                        if (user_row['user_id'] != '' && user_row['user_id'] != undefined)
                                        {
                                                users_cell += get_user_cell_html(user_row['first_name'], user_row['last_name'], user_row['email'], user_row['is_power_user'], 
                                                                        user_row['resend_link_flag'], user_row['role'], data.adv_users.adv_id, data.adv_users.adv_name, user_row['user_id'], "add", power_users_count, user_row['activation_email_date'], user_row['user_created_date']) ;
                                        }
                                }
                                
                                users_cell += "<div>"+
                                                " <a href='#' data-trigger='hover' data-toggle='tooltip' data-placement='top' data-title='Add user' class='btn btn-link pv_new_rfp_button pv_tooltip pv_edit_rfp_button' onclick=\"edit_user('','','' , '', '', '"+
                                                data.adv_users.adv_id+"', '"+data.adv_users.adv_name.replace("'" , "\\'")+"', '')\" ><span>+<i class='icon-user'></i></span></a></div>";
                                
                                hideoverlay();

                                row_data.data_adv_id.formatted_adv_id = users_cell;
                                table.row(selected_row).data(row_data);
                                table.draw(false); //Redraw table
                        }
                }
        });
}
 
function edit_user_save()
{
	close_modal_flag=false;
	var email_sel = "";
	if ( document.forms['edit_user_form'].email_sel != undefined )
		 email_sel = document.forms['edit_user_form'].email_sel.value;
	var error_flag = '';
	var first_name = document.forms['edit_user_form'].first_name.value;
	var last_name = document.forms['edit_user_form'].last_name.value;
	var email = document.forms['edit_user_form'].email.value;
	if (document.forms['edit_user_form'].email_sel == undefined || email_sel == '*New*')
	{
		/*if (first_name == '')
		{
			error_flag += 'First Name required; ';
		}
		if (last_name == '')
		{
			error_flag += 'Last Name required; ';
		}*/
		if (email.indexOf('@') == '-1' || email.indexOf('.') == '-1')
		{
			error_flag += 'Email required';
		}
	}
	else
	{
		if (email_sel == '' && document.forms['edit_user_form'].email_sel != undefined )
		{
			error_flag = 'Please select an email';
		}
	}
	if (error_flag != '')
	{
		show_modal_error(error_flag);
		return false;
	}
	var adv_id = document.forms['edit_user_form'].adv_id.value;
	var adv_name = document.forms['edit_user_form'].adv_name.value;
	var user_id = document.forms['edit_user_form'].user_id.value;
	var is_power_user = "";
	for (i = 0; i < document.getElementsByName('client_admin').length; i++) 
	{
        if (document.getElementsByName('client_admin')[i].checked) 
        {
            is_power_user = document.getElementsByName('client_admin')[i].value;
        }
    }
	var role = "";
	for (i = 0; i < document.getElementsByName('client_type').length; i++) 
	{
        if (document.getElementsByName('client_type')[i].checked) 
        {
            role = document.getElementsByName('client_type')[i].value;
        }
    }
	showoverlay();
	$.ajax({
		url: '/spectrum_account_data_loader/add_edit_advertiser_user',
		type: 'POST',
		async: true,
		dataType: 'json',
		data: {
			first_name: first_name, last_name: last_name, email: email, is_power_user: is_power_user, role: role, adv_id: adv_id, adv_name: adv_name, user_id: user_id
		},
		success: function(data, status) {
			if(data.is_success)
			{                               
				//g_the_table.api().ajax.reload(null, false);                                
				show_tags_error(data.data['return_flag'], 'info');
				document.getElementById('toggle_all_check').checked=false;
                                // To check for bulk advertiser
                                if (adv_id.indexOf(',') == -1)
                                {
                                        refresh_linked_advertisers_and_users(adv_id);
                                }
                                else
                                {
                                        window.location.reload();
                                }
			}
			else
			{
				show_tags_error('Unable to process request at this time. Some error occurred. 908dg9uibjl5rt', 'info'); 
                                hideoverlay();
			}
			//hideoverlay();

		},
		error: function(e, data) {
			console.log(e);
			show_tags_error('Unable to process request at this time. Some error occurred. 908dg9uibjl5ry', 'info');
                        hideoverlay();
		}
	});
}

function show_modal_error(error_flag, type)
{
	if (type=="info")
		document.getElementById('tags_alert_div_modal').innerHTML = '<div class="alert">'+error_flag+'</div>';
	else	
		document.getElementById('tags_alert_div_modal').innerHTML = '<div class="alert alert-danger">'+error_flag+'</div>';
}

var close_modal_flag=false;
function close_modal()
{
	close_modal_flag=true;
}
 
$('#edit_modal').on('hide', function (evt) {
	if (close_modal_flag) 	
		return true;
	else
	{
		var isValid = true;
		if ((document.forms['edit_user_form'].email_sel == undefined || document.forms['edit_user_form'].email_sel.value == '*New*' ) 
			&& document.forms['edit_user_form'].first_name != undefined)
		{	
			var email = document.forms['edit_user_form'].email.value;

			if (email.indexOf('@') == '-1' || email.indexOf('.') == '-1')
			{
				isValid = false;
			}
		}
		else if (document.forms['edit_user_form'].email_sel != undefined)//add existing email
		{
			if (document.forms['edit_user_form'].email_sel.value == '')
			{
				isValid = false;
			}
		}
		else if (document.forms['edit_user_form'].email_remove != undefined)
		{
			if (document.forms['edit_user_form'].email_remove.value == '')
			{
				isValid = false;
			}
		}	
		else if (document.forms['edit_user_form'].advertiser_group_name != undefined)
		{
			if (document.forms['edit_user_form'].advertiser_group_name.value == '')
			{
				isValid = false;
			}
			if (document.forms['edit_user_form'].advertiser_group.value == '')
			{
				isValid = false;
			}
		}	

		if (!isValid) 
		{
			event.preventDefault();
			event.stopImmediatePropagation();
			return false;
		}
		else
		{
			return true;
		}
	}
});

function resend_link_flag(user_id, adv_name, adv_id)
{
	showoverlay();
	$.ajax({
		url: '/spectrum_account_data_loader/resend_link_flag',
		type: 'POST',
		async: true,
		dataType: 'json',
		data: {
			adv_name: adv_name, user_id: user_id, adv_id: adv_id
		},
		success: function(data, status) {
			if(data.is_success)
			{
				//hideoverlay();
				//g_the_table.api().ajax.reload(null, false);
                                refresh_linked_advertisers_and_users(adv_id);
				show_tags_error('Activation link sent to user','info');                                
			}
			else
			{
				show_tags_error('Unable to process request at this time. Some error occurred. 908dg45hyuibjl5rt', 'info'); 
			}
		},
		error: function(e, data) {
			console.log(e);
			show_tags_error('Unable to process request at this time. Some error occurred. 908dg9dfghibjl5rt', 'info'); 
		}
	});
}

function remove_user_save(adv_id, user_id)
{
	showoverlay();
	$.ajax({
		url: '/spectrum_account_data_loader/remove_advertiser_user',
		type: 'POST',
		async: true,
		dataType: 'json',
		data: {
			adv_id: adv_id, user_id: user_id
		},
		success: function(data, status) {
			if(data.is_success)
			{
				//g_the_table.api().ajax.reload(null, false);
                                refresh_linked_advertisers_and_users(adv_id);
				show_tags_error(data.data,'info');
				//hideoverlay();                                
			}
			else
			{
				console.log(data);
				show_tags_error('Unable to process request at this time. Some error occurred. 908deeasdasd9uibjl5rt', 'info'); 
			}
		},
		error: function(e, data) {
			console.log(e);
			show_tags_error('Unable to process request at this time. Some error occurred. 908dg9uigsfsfl5rt', 'info'); 
		}
	});
}

function remove_user_save_bulk()
{
	close_modal_flag=false;
	var email = document.forms['edit_user_form'].email_remove.value;
	var adv_id = document.forms['edit_user_form'].adv_id.value;
	if (document.forms['edit_user_form'].email_remove.value == '')
	{
		show_modal_error('Please select an email');
		return false;
	}
	showoverlay();
	$.ajax({
		url: '/spectrum_account_data_loader/remove_advertiser_user_bulk',
		type: 'POST',
		async: true,
		dataType: 'json',
		data: {
			adv_id: adv_id, email: email
		},
		success: function(data, status) {
			if(data.is_success)
			{
				//g_the_table.api().ajax.reload(null, false);
				document.getElementById('toggle_all_check').checked=false;
				show_tags_error(data.data,'info');				
                                window.location.reload();                                
			}
			else
			{
				show_tags_error('Unable to process request at this time. Some error occurred. 908dg9dasdjl5rt', 'info');
                                hideoverlay();
			}
		},
		error: function(e, data) {
			show_tags_error('Unable to process request at this time. Some error occurred. 90dasduf9uibjl5rt', 'info');
                        hideoverlay();
		}
	});
}

function remove_users_bulk(adv_id)
{
	document.getElementById('modal_window_title').innerHTML = 'Select one or more user(s) to remove from selected Advertiser(s)';
    var edit_content = '<div class="form-group" style="font-size: 11px"><div id="tags_alert_div_modal"><div> </div></div><div class="row"><div class="span2">Select User(s) to remove</div><div class="span2"><input type=hidden class="form-control" name="email_remove" id="email_remove"></div></div>';
    edit_content +='<div><br> <button class="btn btn-small" onclick="close_modal()" data-dismiss="modal" aria-hidden="true">Cancel</button>  <button data-dismiss="modal" aria-hidden="true" class="btn btn-small btn-danger" id="edit_submit_btn" onclick="remove_user_save_bulk()">Remove</button> </div>';
    edit_content +='<input type=hidden id="adv_id" value="'+adv_id+'">';	
	$('#edit_modal .modal-body').html(edit_content);
	$('#edit_modal').modal();
	$('#email_remove').select2({
		multiple: true,
		width: '300px',
		placeholder: "Select users to remove",
		allowClear: true,
		minimumInputLength: 0,
		ajax:{
			type: 'POST',
			url: '/spectrum_account_data_loader/fetch_user_info_by_email_select2',
			dataType: 'json',
			data: function (term, page){
				term = (typeof term === "undefined" || term == "") ? "%" : term; 
				return{
					q: term,
					page_limit: 10,
					page: page,
					adv_id : adv_id
				};
			},
			results: function(data){
				return {results: data.result, more: data.more};	
			}
		},
   
		initSelection: function(element, callback) {
        return $.getJSON("/spectrum_account_data_loader/fetch_user_info_by_email_select2", null, function(data) {
            return callback(data[0]);
        });
    }
	});
}

function get_user_cell_html(first_name, last_name, email, is_power_user, resend_link_flag, role, adv_id, adv_name, user_id, mode, power_users_count, activation_email_date, user_creation_date)
{
	var first_name_display=first_name;
	if (first_name_display == "")
		first_name_display = "-";
	var last_name_display=last_name;
	if (last_name_display == "")
		last_name_display = "-";

	if (first_name != undefined)			
		first_name = first_name.replace("'" , "\\'");
	
	if (last_name != undefined)			
		last_name = last_name.replace("'" , "\\'");

	if (adv_name != undefined)			
		adv_name = adv_name.replace("'" , "\\'");


	var users_cell = "";
	if (mode == 'add')
	{
	 	users_cell = "<div class='row-fluid hover_row wrap_word' id='user_cell_"+adv_id+"_"+user_id+"'>";
	}
	var login_date_string = "";
        if (activation_email_date != "" && activation_email_date != "0000-00-00" && activation_email_date != null)
	{
            login_date_string += "<a href='javascript:void(0);' class='btn btn-link pv_new_rfp_button pv_tooltip pv_edit_rfp_button' data-toggle='tooltip' data-trigger='hover' data-placement='top'";
            if (resend_link_flag || resend_link_flag == "1")
            {
                    login_date_string += " data-title='Invite Date'>";
                    login_date_string += "<small>"+activation_email_date+"</small>&nbsp;";
                    login_date_string += "<i class='icon-gift'></i>";
                    
            }
            else if (resend_link_flag != "1")
            {
                    login_date_string += " data-title='Last Login'>";
                    login_date_string += "<small>"+activation_email_date+"</small>";
            }
            login_date_string += "</a>";
        }
        else
        {
                login_date_string = "-";
        }
        var user_creation_date_string="";
	if (user_creation_date != "" && user_creation_date != "0000-00-00" && user_creation_date != null)
	{
		user_creation_date_string="<small>"+user_creation_date+"</small>";
	}
        else
        {
                user_creation_date_string="-";
        }
	users_cell += "<div class='span12'>";        
        users_cell += "<div class='span4'><b>"+first_name_display+" "+last_name_display+"</b>";
        users_cell += "<br />"+email+"</div>";        
        users_cell += "<div class='span2'>"+login_date_string+"</div>";
        users_cell += "<div class='span2'>"+user_creation_date_string+"</div>";
        users_cell += "<div class='span2'>";
        var power_user = '';
	if (is_power_user == "1")
        {
		power_user = "<a href='javascript:void(0);' data-toggle='tooltip' data-trigger='hover' data-placement='top' data-title='Admin'><span class='admin_key'><i class='icon-key'></i></span></a>";
        }
        
        var role_img = '';
        if (role == 'CLIENT')
        {
		role_img = "<a href='javascript:void(0);' data-toggle='tooltip' data-trigger='hover' data-placement='top' data-title='Client'><span class='badge badge-warning'>C</span></a>";
        }
        else if (role == 'AGENCY')
        {
		role_img = "<a href='javascript:void(0);' data-toggle='tooltip' data-trigger='hover' data-placement='top' data-title='Agency'><span class='badge badge-success'>A</span></a>";
        }
	
	users_cell += role_img + ' ' + power_user;	
	
        users_cell += "</div>";
        users_cell += "<div class='span2'>";
	if (resend_link_flag || resend_link_flag == "1")
		users_cell += "<a href='#' onclick=\"resend_link_flag("+user_id+", \'"+adv_name+"\', \'"+adv_id+"\')\" class='btn btn-link pv_new_rfp_button pv_tooltip pv_edit_rfp_button' data-toggle='tooltip'  data-trigger='hover' data-placement='top' data-title='Send Invite Email'><span><i class='icon-envelope icon-white'></i></span></a>";
	else
		users_cell += "&nbsp;";

	users_cell += "<a href='#' onclick=\"edit_user('"+first_name+"','"+last_name+"','"+
	email+"' , '"+is_power_user+"', '"+role+"', '"+adv_id+"', '"+adv_name+"','"+user_id+"')\" class='btn btn-link pv_new_rfp_button pv_tooltip pv_edit_rfp_button' data-toggle='tooltip'  data-trigger='hover' data-placement='top' data-title='Edit'><span><i class='icon-edit icon-white'></i></span></a>";

	if (power_users_count > 1 || is_power_user == '0')
	{
		users_cell +="<a href='#' onclick='remove_user_save("+adv_id+", "+user_id+")' class='btn btn-link pv_new_rfp_button pv_tooltip pv_edit_rfp_button' data-toggle='tooltip'  data-trigger='hover' data-placement='top' data-title='Remove'><span><i class='icon-remove icon-white'></i></span></a>";
	}
	else
	{
		users_cell +="<span   class='btn btn-link pv_new_rfp_button pv_tooltip pv_edit_rfp_button'  id='disabled_btn'  data-toggle='tooltip'  data-trigger='hover' data-placement='top' data-title='1 Admin'><span><i class='icon-lock icon-white'></i></span></span>";
	}
	users_cell +="</div>";
        users_cell += "</div>";
	if (mode == 'add')
		users_cell +="</div>";
	return users_cell;
}

function add_user_to_adv()
{	
	var selected_adv_ids = "";
	for (i = 0; i < document.getElementsByName('bulk_aid').length; i++) 
	{
        if (document.getElementsByName('bulk_aid')[i].checked) 
        {
            selected_adv_ids += document.getElementsByName('bulk_aid')[i].value+",";
        }
    }
	if (selected_adv_ids.length < 2)
	{
	  show_tags_error('Please select at least one Advertiser','error');
	  return;
	}
	edit_user('','','' , '', '', selected_adv_ids, '', '');
}

function remove_user_from_adv()
{
	var selected_adv_ids = "";
	for (i = 0; i < document.getElementsByName('bulk_aid').length; i++) 
	{
        if (document.getElementsByName('bulk_aid')[i].checked) 
        {
            selected_adv_ids += document.getElementsByName('bulk_aid')[i].value+",";
        }
    }
	selected_adv_ids = selected_adv_ids.substring(0,selected_adv_ids.length-1);
	if (selected_adv_ids.length < 2)
	{
	  show_tags_error('Please select atleast one Advertiser','error');
	  return;
	}
	remove_users_bulk(selected_adv_ids);
}

function add_edit_advertiser_groups(mode)
{	
	var selected_adv_ids = "";
	for (i = 0; i < document.getElementsByName('bulk_aid').length; i++) 
	{
        if (document.getElementsByName('bulk_aid')[i].checked) 
        {
            selected_adv_ids += document.getElementsByName('bulk_aid')[i].value+",";
        }
    }
	if (selected_adv_ids.length < 2)
	{
	  show_tags_error('Please select atleast one Advertiser','error');
	  return;
	}
	add_edit_advertiser_group_popup(selected_adv_ids, "");
}

function add_edit_advertiser_group_popup(selected_adv_ids, advertiser_group_id)
{

	document.getElementById('modal_window_title').innerHTML = 'Advertiser Group';
    var edit_content = '<div class="form-group" style="font-size: 11px"><div id="tags_alert_div_modal"><div> </div></div>';
	edit_content +='<div class="row"><div class="span2">Advertiser Group Name:</div><div class="span2">'+
	'<input type=hidden class="form-control" name="advertiser_group_id" id="advertiser_group_id" value="'+advertiser_group_id+
	'"><input type=text class="form-control" name="advertiser_group_name" id="advertiser_group_name"></div></div>';
    edit_content +='<div class="row"><div class="span2">Advertisers</div><div class="span2"><input type=hidden class="form-control" name="advertiser_group" id="advertiser_group"></div></div>';
    edit_content +='<div><br> <button class="btn btn-small" id="close_btn" onclick="close_modal()" data-dismiss="modal" aria-hidden="true">Close</button>  ';
    edit_content +='<button class="btn btn-small btn-success" id="edit_submit_btn" onclick="advertiser_group_submit(event)">Save</button> ';
    if (advertiser_group_id != undefined && advertiser_group_id != "")
    {
	    edit_content +='<button class="btn btn-small btn-danger" id="delete_submit_btn" onclick="advertiser_group_delete(event)">Delete Group</button>';

  	}
  	edit_content +='</div>';

	$('#edit_modal .modal-body').html(edit_content);
	$('#edit_modal').modal();
	$('#advertiser_group').select2({
		multiple: true,
		width: '300px',
		placeholder: "Select Advertisers",
		allowClear: true,
		minimumInputLength: 0,
		ajax:{
			type: 'POST',
			url: '/spectrum_account_data_loader/fetch_advertisers_for_adgroup',
			dataType: 'json',
			data: function (term, page){
				term = (typeof term === "undefined" || term == "") ? "%" : term; 
				return{
					q: term,
					page_limit: 10,
					page: page
				};
			},
			results: function(data){
				return {results: data.result, more: data.more};	
			}
		} 
	
	}); 
		$.ajax({
		url: '/spectrum_account_data_loader/fetch_advertisers_for_adgroup',
		type: 'POST',
		async: true,
		dataType: 'json',
		data: {
			selected_adv_ids: selected_adv_ids, 
			advertiser_group_id: advertiser_group_id
		},
		success: function(data, status) {
			if (data.result != undefined && data.result.length > 0)
			{
				$('#advertiser_group').select2('data', data.result);
				if (data.ag_name != undefined)
					document.getElementById('advertiser_group_name').value=data.ag_name;
			}			
		},
		error: function(e, data) {
			show_tags_error('Unable to process request at this time. Some error occurred. 90dasduf9uibjl5rt', 'info'); 
		}
	});
}

function advertiser_group_submit(ev)
{	
	ev.preventDefault();
	var advertiser_group_id = document.forms['edit_user_form'].advertiser_group_id.value;
	var advertiser_group_name = document.forms['edit_user_form'].advertiser_group_name.value;
	var advertiser_group = document.forms['edit_user_form'].advertiser_group.value;
	document.forms['edit_user_form'].advertiser_group_name.style.backgroundColor="#fff";
	if (document.forms['edit_user_form'].advertiser_group_name.value == '')
	{
		show_modal_error('Please enter an Advertiser Group Name');
		document.forms['edit_user_form'].advertiser_group_name.style.backgroundColor="#F5BCA9";
		return false;
	}
	if (document.forms['edit_user_form'].advertiser_group.value == '')
	{
		show_modal_error('Please select some Advertisers to Add');
		return false;
	}
	showoverlay();
	
	$.ajax({
		url: '/spectrum_account_data_loader/advertiser_group_submit',
		type: 'POST',
		async: true,
		dataType: 'json',
		data: {
			advertiser_group_name: advertiser_group_name, advertiser_group: advertiser_group, advertiser_group_id: advertiser_group_id
		},
		success: function(data, status) {
			if(data.is_success == "true")
			{
				//g_the_table.api().ajax.reload(null, false);
				document.getElementById('toggle_all_check').checked=false;
				show_tags_error(data.data,'info');
				show_modal_error('SUCCESS: Advertiser Group Saved','info');
				show_tags_error('SUCCESS: Advertiser Group Saved','info');				
				document.getElementById('close_btn').click();
                                window.location.reload();                                
			}
			else if(data.is_validation_errors == "true")
			{
				show_modal_error(data.error_message);
				if (data.is_duplicate_adgroup_name)
				{
					document.forms['edit_user_form'].advertiser_group_name.style.backgroundColor="#F5BCA9";
				}
				hideoverlay();
				return false;
			}
			else
			{
				show_modal_error('Some error happened, please refresh page and try again' );
				show_tags_error('Unable to process request at this time. Some error occurred. 908dg9dasdjl5rt', 'info');
                                hideoverlay();				 
			}
		},
		error: function(e, data) {
			show_modal_error('Some error happened, please refresh page and try again' );
			show_tags_error('Unable to process request at this time. Some error occurred. 90dasduf9uibjl5rt', 'info');
                        hideoverlay();			 
		}
	});
}

function advertiser_group_delete(ev)
{	
	ev.preventDefault();
	var advertiser_group_id = document.forms['edit_user_form'].advertiser_group_id.value;
	
	showoverlay();
	
	$.ajax({
		url: '/spectrum_account_data_loader/advertiser_group_delete',
		type: 'POST',
		async: true,
		dataType: 'json',
		data: {
			advertiser_group_id: advertiser_group_id
		},
		success: function(data, status) {
			if(data.is_success == "true")
			{
				//g_the_table.api().ajax.reload(null, false);
				document.getElementById('toggle_all_check').checked=false;
				show_tags_error(data.data,'info');
				show_modal_error('SUCCESS: Advertiser Group Deleted','info');
				show_tags_error('SUCCESS: Advertiser Group Deleted','info');				
				document.getElementById('close_btn').click();
                                window.location.reload();                                
			}
			else
			{
				show_modal_error('Some error happened, please refresh page and try again' );
				show_tags_error('Unable to process request at this time. Some error occurred. 908dg9dasdjl5rt', 'info'); 
				hideoverlay();
			}
		},
		error: function(e, data) {
			show_modal_error('Some error happened, please refresh page and try again' );
			show_tags_error('Unable to process request at this time. Some error occurred. 90dasduf9uibjl5rt', 'info'); 
			hideoverlay();
		}
	});
}

function toggle_all_add_users_to_adv(me)
{
	for (i = 0; i < document.getElementsByName('bulk_aid').length; i++) 
	{
		document.getElementsByName('bulk_aid')[i].checked = document.getElementById('toggle_all_check').checked;
    }
}

function showoverlay() 
{
	if (document.getElementById('overlay') == undefined) 
	{
		$("body").append("<div id='overlay' style='background-color:#F2F0F2; opacity: 0.8;position:absolute;top:0;left:0;height:400%;width:100%;z-index:999'>Processing...</div>");
	}
}

function hideoverlay() 
{
	if (document.getElementById('overlay') != undefined) 
	{
		$("#overlay").remove();
	}
}

var g_timeout_id;
function show_tags_error(error_msg, type)
{
	if (error_msg == undefined)
		return;
	window.clearTimeout(g_timeout_id);
	if (type=='error')
		$("#tags_alert_div").html("<div class='alert alert-danger'>"+error_msg+"</div>");
	else
		$("#tags_alert_div").html("<div class='alert'>"+error_msg+"</div>");
	$("#tags_alert_div").show();
}

$.fn.modal.Constructor.prototype.enforceFocus = function() {};
</script> 
