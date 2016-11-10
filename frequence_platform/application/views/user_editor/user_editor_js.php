<script type="text/javascript" src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.js"></script>
<script src="/libraries/external/DataTables-1.10.2/extensions/FixedHeader/js/dataTables.fixedHeader.js"></script>
<script type="text/javascript" src="/assets/js/jquery.highlight.js"></script>
<script src="/libraries/external/select2/select2.js"></script>
<script src="/bootstrap/assets/js/bootstrap-tooltip.js"></script>
<script src="/libraries/external/bootstrap-switch-3.3.2/js/bootstrap-switch.min.js"></script>
<script src="/libraries/external/momentjs/moment.min.js"></script>
<script src="/libraries/external/momentjs/moment-timezone-with-data.min.js"></script>
<script src="/libraries/external/momentjs/jstz.min.js"></script>

<script type="text/javascript">

var u_timeout_id;
var single_u_timeout_id
var selected_user_id;
var previous_role;

var owner_partner_add_list = [];
var owner_partner_remove_list = [];
var is_super = false;

var table_data = {};
var table;
var usr_partner_id;
var partner_matches;
var advertisers;

//For lack of a better ideaf
var selected_row;
var row_data;

function change_permission_property(user_id, which_permission, enable)
{
	var user_name = $('#user_name_'+user_id).text();
	$.ajax({
			type: "POST",
			url: '/user_editor/update_permission_for_user',
			async: true,
			data: {user_id: user_id, permission: which_permission, is_enabled: enable},
			dataType: 'json',
			success: function(data, textStatus, jqXHR){
				if(data.is_success)
				{
					if(which_permission == "ban")
					{
						if(!enable)
						{
							set_message_timeout_and_show('Reactivated '+user_name, 'alert alert-success', 3000);
						}
						else
						{
							set_message_timeout_and_show('Disabled '+user_name, 'alert alert-success', 3000);
						}
					}
					else
					{
						set_message_timeout_and_show('Successfully updated '+user_name, 'alert alert-success', 3000);
					}
				}
				else
				{
					set_message_timeout_and_show('Error: Failed to set '+which_permission+' for  '+user_name, 'alert alert-error', 16000);
					err_set_permission_checkbox(user_id, which_permission, !enable);
				}
			},
			error: function(xhr, textStatus, error){
				set_message_timeout_and_show('Error 48192: Unable to alter permission for user '+user_name, 'alert alert-error', 16000);
				err_set_permission_checkbox(user_id, which_permission, !enable);
			}
		});
}

function err_set_permission_checkbox(user_id, which_permission, enable)
{
	$("permission_"+which_permission+"_"+user_id).prop('checked', enable);
}


function reset_password(reset_button, user_id, user_name)
{
	$(reset_button).popover('hide');
	reset_button.disabled = true;
	$.ajax({
			type: "POST",
			url: '/auth/forgot_password',
			async: true,
			data: {reset_user_id: user_id, reset_user_name: user_name},
			dataType: 'json',
			success: function(data, textStatus, jqXHR){
				if(data.is_success)
				{
					var display_email = $("#user_"+user_id+" span small").html();
					set_message_timeout_and_show('Password reset email sent to '+display_email, 'alert alert-success', 3000);
					window.setTimeout( function(){reset_button.disabled = false;}, 5000);
				}
				else
				{
					set_message_timeout_and_show('Error: Failed to reset password for  '+user_name, 'alert alert-error', 16000);
					reset_button.disabled = false;
				}
			},
			error: function(xhr, textStatus, error){
				set_message_timeout_and_show('Error 48192: Unable to reset password for user '+user_name, 'alert alert-error', 16000);
				reset_button.disabled = false;
			}
		});

}

function format_report_setting_checkbox(is_enabled, user_id, which)
{
	var checkbox = "<input type=\"checkbox\" id=\"permission_"+which+"_"+user_id+"\" ";
	if(is_enabled)
	{
		checkbox += "checked=\"true\" ";
	}
	<?php if($this->session->userdata('is_demo_partner') != 1) { ?>
	if(which == "ban")
	{
		checkbox += "name=\"switch_me\" onclick=\"alert("+user_id+"a"+")\" data-uid=\""+user_id+"\"";
	}
	else
	{
		checkbox += "onchange=\"change_permission_property("+user_id+", \'"+which+"\', this.checked);\"";
	}
	<?php } else { ?>
	if(which == "ban")
	{
		checkbox += "name=\"switch_me\" disabled=\"disabled\" ";
	}
	else
	{
		checkbox += "disabled=\"disabled\"";
	}
	<?php } ?>
	checkbox += " />";
	return checkbox;

}


function get_boolean_from_db_flag_value(flag)
{
	if(flag == "1")
	{
		return true;
	}
	return false;
}

function format_user_super_cell(owned_partners)
{
	var return_html = "<div class=\"org\">";
	if(owned_partners != null && owned_partners != "" && owned_partners.length > 0)
	{
		for(i = 0; i < owned_partners.length; i++)
		{
			if(owned_partners[i] in partner_matches)
			{
				if(partner_matches[owned_partners[i]]['descendants'] != null)
				{
					return_html += "<span class=\"tt_org\" title=\""+partner_matches[owned_partners[i]]['partner_name']+"\" data-content=\"<ul>"+partner_matches[owned_partners[i]]['descendants']+"</ul>\">"+partner_matches[owned_partners[i]]['partner_name']+"</span><br>";
				}
				else
				{
					return_html += "<span>"+partner_matches[owned_partners[i]]['partner_name']+"</span><br>";
				}
			}
		}
	}
	else
	{
		return_html += "<div class=\"org blank_org\">--</div>";
	}
	return_html += "</div>";
	return return_html;
}

function format_org_cell(partner_id)
{
	var return_html = "<div class=\"blank_org\"><span>--</span></div>";

	if(partner_id in partner_matches)
	{
		return_html = "<div><small>"+(partner_matches[partner_id]['login_url'] == null ? "--" : partner_matches[partner_id]['login_url'])+"</small><br><span class=\"long_name_wrap\">"+partner_matches[partner_id]['partner_name']+"</span><br>";
	}
	return return_html;
}

function format_business_org_cell(business_id)
{
	var return_html = "<div class=\"blank_org\"><span>--</span></div>";

	if(business_id in advertisers)
	{
		return_html = "<div><small>"+(partner_matches[advertisers[business_id]['adv_partner']]['login_url'] == null ? "--" : partner_matches[advertisers[business_id]['adv_partner']]['login_url'])+"</small><br><span class=\"long_name_wrap\">"+advertisers[business_id]['name']+"</span><br>";
	}
	return return_html;
}

function format_user_name_cell(user_id, email,first_name, last_name)
{
    <?php  if($this->session->userdata('is_demo_partner') != 1) { ?>
	return "<div id=\"user_"+user_id+"\" class=\"user_table_user_name\" ><span style=\"word-break:break-all\"><small>"+email+"</small></span><br><a href=\"javascript:handle_user_name_click("+user_id+");\"><i class=\"icon-pencil\"></i> <span id=\"user_name_"+user_id+"\"><strong>"+first_name+" "+last_name+"</strong></a></span></div>";
    <?php } if($this->session->userdata('is_demo_partner') == 1){   ?>
	return "<div id=\"user_"+user_id+"\" class=\"user_table_user_name\" ><span style=\"word-break:break-all\"><small>"+email+"</small></span><br><i class=\"icon-pencil\"></i> <span id=\"user_name_"+user_id+"\"><strong>"+first_name+" "+last_name+"</strong></span></div>";
    <?php } ?>
}

function format_role(role)
{
	//Sales = Sales/Partner. Special case
	if(role == "SALES")
	{
		return "Partner";
	}
	if(role == "BUSINESS")
	{
		return "Advertiser";
	}
	return (role).toLowerCase().charAt(0).toUpperCase() + (role).toLowerCase().slice(1);
}

function get_partner_names(partners)
{
	return_string = "--";
	for(i = 0; i < partners.length; i++)
	{
		if(typeof partner_matches[i] !== "undefined")
		{
			return_string += (partner_matches[partners[i]]['partner_name']) + " ";
		}
	}
	return return_string;
}


function format_table_data(data_array, partner_matching_array)
{
	$.each(data_array, function(key, value)
	{
		this.user_name_email_obj = {
			formatted_user_name: format_user_name_cell(this.id, this.email, this.firstname, this.lastname),
			user_name: this.firstname + " " + this.lastname + " " + this.email
		};

		this.last_login_obj = {
			formatted_last_login: this.last_login,
			time_sort: this.time_sort
		};

		this.role_obj = {
			formatted_role: format_role(this.role),
			role: this.role
		};

		this.org_obj = {
			formatted_org: this.role == "BUSINESS" ? format_business_org_cell(this.org_id) : format_org_cell(this.org_id),
			org: this.org_name
		};

		var supers = [];
		if(this.role == "SALES")
		{
			if(this.owned_partners)
			{
				supers = supers.concat(this.owned_partners);
			}
			if(this.isGroupSuper == "1" && this.org_id != null)
			{
				var remove_index = supers.indexOf(this.org_id);
				if(remove_index != -1)
				{
					supers.splice(remove_index, 1);
				}
					supers.unshift(this.org_id);
			}
		}
		this.super_org_obj = {
			formatted_super_org: format_user_super_cell(supers),
			super_org: get_partner_names(supers)
		};
		<?php if($report_permissions['are_placements_accessible']){ ?>
		this.placements_setting_obj = {
			formatted_placements_checkbox: format_report_setting_checkbox(get_boolean_from_db_flag_value(this.placements_viewable), this.id, "placement"),
		};
		<?php } ?>
		<?php if($report_permissions['are_screenshots_accessible']){ ?>
		this.screenshots_setting_obj = {
			formatted_screenshots_checkbox: format_report_setting_checkbox(get_boolean_from_db_flag_value(this.screenshots_viewable), this.id, "screenshot"),
		};
		<?php } ?>
		<?php if($report_permissions['are_ad_interactions_accessible']){ ?>
		this.engagements_setting_obj = {
			formatted_engagements_checkbox: format_report_setting_checkbox(get_boolean_from_db_flag_value(this.beta_report_engagements_viewable), this.id, "engagement"),
		};
		<?php } ?>
		<?php if($this->session->userdata('is_demo_partner') != 1) { ?>
		this.reset_password_button_obj = {
			formatted_password_reset_button: "<div><span><button class=\"btn btn-mini btn-primary tt_reset_button\" onclick=\"reset_password(this, "+this.id+", \'"+this.username+"\');\">Reset</button></span></div>"
		};
		<?php } if($this->session->userdata('is_demo_partner') == 1) { ?>
		this.reset_password_button_obj = {
			formatted_password_reset_button: "<div><span><button style=\"cursor:default;outline:none;color: #A4A4A4\" class=\"btn btn-mini tt_reset_button\");\">Reset</button></span></div>"
		};
		<?php } ?>		
		this.ban_obj = {
			formatted_ban_switch: "<span class=\"tt_ban_switch\">"+format_report_setting_checkbox(!(get_boolean_from_db_flag_value(this.banned)),this.id, "ban")+"</span>"
		};	
		
		
	});
	return data_array;
}

function initialize_ban_switches()
{
	$("[name='switch_me']").bootstrapSwitch({
		size: 'mini',
		onText: " <i class=\"icon-ok\"/> "
,		offText: " <i class=\"icon-remove\"/> ",
		onColor: 'success',
		offColor: 'danger',
		onSwitchChange: function(event, state) {handle_toggle_ban_switch(this, state);}
	});
}

var advertiser_id="";
var advertiser_name="";
function fill_user_edit_form(user_id)
{
	clear_error_fields();
	hide_user_edit_message();
	selected_user_id = user_id;
	$("#user_edit_modal_body").hide(0);
	$("#user_field_role_dropdown_other_than_client_agency").hide();
	$("#user_edit_modal_body_loading_img").show(0);
	var user_name = $('#user_name_'+user_id).text();
	advertiser_id="";
	advertiser_name="";
	$.ajax({
			type: "POST",
			url: '/user_editor/get_user_form_details',
			async: true,
			data: {user_id: user_id},
			dataType: 'json',
			success: function(data, textStatus, jqXHR){
				if(data.is_success)
				{
					var user_data = data.user_data;
					//set data
					var owned_partners = [];
					$("#user_edit_first_name").val(user_data.first_name);
					$("#user_edit_last_name").val(user_data.last_name);
					$("#user_edit_email").val(user_data.email);
					if(user_data.role == 'CLIENT' || user_data.role == 'AGENCY')
					{
						$('#user_edit_role').empty().append('<option value=""></option><option value="CLIENT">Client</option><option value="AGENCY">Agency</option>');
					}
					else
					{
						$('#user_edit_role').empty().append($('#user_edit_role_except_client_agency').html())
					}
					$("#user_edit_role").val(user_data.role);
					var partner = -1;
					if(user_data.partner_id != null)
					{
						partner = user_data.partner_id;
					}
					$("#user_edit_partner").val(partner).trigger("change");
					previous_role = user_data.role;

					<?php if($report_permissions['are_placements_accessible']){ ?>
					$("#user_edit_check_placements").prop('checked', parseInt(user_data.placements_viewable));
					<?php } ?>

					<?php if($report_permissions['are_screenshots_accessible']){ ?>
					$("#user_edit_check_screenshots").prop('checked', parseInt(user_data.screenshots_viewable));
					<?php } ?>

					<?php if($report_permissions['are_ad_interactions_accessible']){ ?>
					$("#user_edit_check_engagements").prop('checked', parseInt(user_data.engagements_viewable));
					<?php } ?>
					$("#user_edit_check_creative").prop('checked', parseInt(user_data.creative_viewable));
					$("#user_edit_check_proposals").prop('checked', parseInt(user_data.proposals_viewable));
					var sample_ad_select_value = '';
					if(parseInt(user_data.sample_ad_builder))
					{
						sample_ad_select_value = 'builder';
					}
					else if(parseInt(user_data.sample_ad_manager))
					{
						sample_ad_select_value = 'manager';
					}
					$("#user_edit_select_sample_ads").val(sample_ad_select_value);
					$("#user_edit_contact_addr").val(user_data.address_1);
					$("#user_edit_contact_addr_line_two").val(user_data.address_2);
					$("#user_edit_contact_city").val(user_data.city);
					$("#user_edit_contact_state").val(user_data.state);
					$("#user_edit_contact_zip").val(user_data.zip);
					$("#user_edit_contact_phone").val(user_data.phone_number);
					var advertiser = -1;
					if(user_data.advertiser_id != null)
					{
						advertiser = user_data.advertiser_id;
						advertiser_id=user_data.advertiser_id;
						advertiser_name=user_data.advertiser_name;
					}
					$("#user_edit_advertiser").val(advertiser).trigger("change");
					handle_user_edit_role_change(user_data.role);
					$("#user_edit_modal_body").show(0);
					$("#user_edit_modal_body_loading_img").hide(0);
					if(user_data.is_group_super == "1")
					{
						owned_partners.push(user_data.partner_id);
					}
					if(user_data.owned_partners != null)
					{
						$.each(user_data.owned_partners, function(key, value)
						{
							owned_partners.push(this);
						});

					}
					$("#user_edit_owned_partners").val(owned_partners).trigger("change");
					return true;
				}
				else
				{
					set_message_timeout_and_show("Error: "+data.err_msg, 'alert alert-error', 16000);
					return false;
				}
			},
			error: function(xhr, textStatus, error){
				set_message_timeout_and_show('Error 71228: Unable to fetch user data for '+user_name, 'alert alert-error', 16000);
				return false;
			}
		});

}

function handle_user_name_click(user_id)
{
		owner_partner_add_list = [];
		owner_partner_remove_list = [];
		$("#user_edit_owned_partners").val([]).trigger("change");
		fill_user_edit_form(user_id);
		$("#user_edit_modal_footer").html('<a class="btn" data-dismiss="modal" aria-hidden="true">Cancel</a><button id="modify_user_button" onclick="handle_save_user(this, '+user_id+')" class="btn btn-success">Save</button>');
		$("#user_edit_modal").modal("show");
}

function handle_toggle_ban_switch(ban_switch, is_active)
{
	var banning_user_id = $(ban_switch).attr('data-uid');
	set_user_switch(banning_user_id, !is_active);
	var user_name = $('#user_name_'+banning_user_id).text();
	if(is_active == true)
	{
		//Unban user
		change_permission_property(banning_user_id, "ban", !is_active)
		set_user_switch(banning_user_id, is_active);
	}
	else
	{
		//setup modal and display
		$("#ban_user_body_content").html("Are you sure you want to deactivate "+user_name+"?");
		$("#ban_user_modal_footer").html('<a class="btn" data-dismiss="modal" aria-hidden="true">No</a><button id="modify_button" data-dismiss="modal" aria-hidden="true" type="submit" onclick="handle_ban_user(this, '+banning_user_id+')" class="btn btn-success">Yes, disable this user</button>');
		$("#ban_user_modal").modal("show");
	}
}
function handle_ban_user(ban_button, user_id)
{
	ban_button.disabled=true;
	change_permission_property(user_id, "ban" , true);
	set_user_switch(user_id, false);
	$("#ban_user_modal").modal("hide");
}

function set_user_switch(user_id, is_active)
{
	$("#permission_ban_"+user_id).bootstrapSwitch('state', is_active, 'true');
}

function initialize_tooltips()
{
	$('.tt_placement_header').popover({
		placement: 'left',
		title: "Placements",
		content: "If a user has \"Placements\" enabled, they can see campaign performace by placement when they navigate to their reports page.",
		trigger: "hover"
	});

	$('.tt_screenshot_header').popover({
		placement: 'left',
		title: "Screenshots",
		content: "If a user has \"Screenshots\" enabled, they can see the Screenshots tab when they navigate to their reports page.",
		trigger: "hover"
	});

	$('.tt_engagement_header').popover({
		placement: 'left',
		title: "Engagements",
		content: "If a user has \"engagements\" enabled, they can see engagements when they navigate to their reports page.",
		trigger: "hover"
	});

	$('.tt_reset_button').popover({
		placement: 'left',
		title: "Reset Password",
		content: "Reseting a user's password will email the user a password reset link - If the user does not change their password using the link, their original password will remain valid.",
		trigger: "hover"
	});

	$('.tt_ban_switch').popover({
		placement: 'left',
		title: "Activate/Deactivate User",
		content: "Inactive users are rejected when logging in.",
		trigger: "hover"
	});

	$('.tt_org').popover({
		placement: 'right',
		title: function(){return this.attr('title')},
		content: function(){this.attr('data-content')},
		trigger: "hover",
		html: true
	});
}

function build_edit_user_dropdowns(partner_matching_array, advertiser_data_array)
{
	partner_dropdown_data_array = [];
	partner_dropdown_data_array.push({id: -1, text: "Please Select"});

	owned_partner_dropdown_data_array = [];

	$.each(partner_matching_array, function(key, value)
	{
		partner_dropdown_data_array.push({id: key, text: this['partner_name']});
		if(key != usr_partner_id)
		{
			owned_partner_dropdown_data_array.push({id: key, text: this['partner_name']});
		}
	});

	$("#user_edit_partner").select2({
		data: partner_dropdown_data_array,
		width: "element"
	}).on("select2-open", function(e){
		this.lastvalue = this.value;
	})
	.on("select2-selecting", function(e){
		if($.inArray(this.lastvalue, $('#user_edit_owned_partners').val().split(',')) != -1)
		{
			if($.inArray(this.lastvalue, owner_partner_add_list) == -1)
			{
				owner_partner_add_list.push(this.lastvalue);
			}
			if($.inArray(this.lastvalue, owner_partner_remove_list) != -1)
			{
				owner_partner_remove_list = $.grep(owner_partner_remove_list, function(val) { return val != this.value});
			}
		}
		if($.inArray(e.val, $('#user_edit_owned_partners').val().split(',')) != -1)
		{
			if($.inArray(e.val, owner_partner_add_list) != -1)
			{
				owner_partner_add_list = $.grep(owner_partner_add_list, function(val) { return val != e.val});
			}
		}
	});

	$('#user_edit_advertiser').select2({
		placeholder: "Select an advertiser",
		minimumInputLength: 0,
		width: "element",
		formatSelection: function(obj)
		{
			return obj.text;
		},
		initSelection: function(element, callback)
		{
			return callback({id:advertiser_id , text:advertiser_name});
		},
		ajax: {
			url: "/report_legacy/ajax_get_advertisers",
			type: 'POST',
			dataType: 'json',
			data: function (term, page) {
				term = (typeof term === "undefined" || term == "") ? "%" : term;
				return {
					q: term,
					page_limit: 100,
					page: page
				};
			},
			results: function (data) {
				return {results: data.results, more: data.more};
			}
		},
		allowClear: false
	});



	$("#user_edit_owned_partners").select2({
		data: owned_partner_dropdown_data_array,
		tags: true,
		width: "element"
	}).on("change", function(e){
		if(e.added != undefined)
		{
			//Add to added
			if($.inArray(e.added.id, owner_partner_add_list) == -1)
			{
				owner_partner_add_list.push(e.added.id);
			}
			//If in removed, remove it from removed
			if($.inArray(e.added.id, owner_partner_remove_list) != -1)
			{
				//remove
				owner_partner_remove_list = $.grep(owner_partner_remove_list, function(val) { return val != e.added.id});
			}
		}
		else if(e.removed != undefined)
		{
			//Add to removed
			if($.inArray(e.removed.id, owner_partner_remove_list) == -1)
			{
				owner_partner_remove_list.push(e.removed.id);
			}
			//If in added, remove it from added
			if($.inArray(e.removed.id, owner_partner_add_list) != -1)
			{
				//remove
				owner_partner_add_list = $.grep(owner_partner_add_list, function(val) { return val != e.removed.id});
			}
		}
	});
}

$(document).ready(function(){
	var timezone_offset = new Date().getTimezoneOffset()/60;
	$.ajax({
		type: "POST",
		url: '/user_editor/ajax_get_user_editor_users',
		async: true,
		data: {timezone_offset: timezone_offset},
		dataType: 'json',
		success: function(data, textStatus, jqXHR){
			$("#loading_img").hide();
			if(data.is_success)
			{
				if(data.user_array === undefined)
				{
					set_message_timeout_and_show('Error 500322: Server returned invalid user data', 'alert alert-error', 16000);
				}
				else if(data.user_array.length == 0)
				{

					var body_html = '<h3> Looks like you don\'t have any users. ';
					body_html += '<a href="/register" target="_blank">Click here to request one</a> ';
					$("#user_editor_body").html(body_html);
					$("#user_editor_body").show();
				}
				else
				{
				usr_partner_id = data.cur_user_partner_id
				advertisers = data.available_advertisers;
				var timezone = jstz.determine();
				var timezone_code = moment.tz.zone(timezone.name()).abbr(new Date().getTime());
				//Set the timezone code and offset in bulk download users link.
				var bulk_download_link=$("#bulk_download_users");
				$(bulk_download_link).attr("href",$(bulk_download_link).attr("href")+"/"+timezone_offset+"/"+timezone_code);
				build_edit_user_dropdowns(data.partner_descendants);
				if(data.can_edit_role == false)
				{
					$("#user_edit_role").prop("disabled", true);
				}

				table_data = data.user_array;
				partner_matches = data.partner_descendants
				users_table = $("#user_editor_table").dataTable({
								"data": format_table_data(table_data, partner_matches),
								"autoWidth" : false,
								"oLanguage": {
									"sLengthMenu": "Display _MENU_ users"
								},
								"ordering": true,
								"order": [[ 0, "asc"]],
								"lengthMenu": [
									[10, 25, 50, 100],
									[10, 25, 50, 100]
								],
								"columnDefs": [
									{}
								],
								"rowCallback": function(row, data) {
								},
								"initComplete": function(settings){
									initialize_ban_switches();
									initialize_tooltips();
								},
								"drawCallback": function(settings){
									initialize_ban_switches();
									initialize_tooltips();
								},
								"columns": [
									{
										data: "user_name_email_obj",
										render: {
											_: "formatted_user_name",
											sort: "user_name"
										},
										title: "<div>User</div>",
										type: "string",
										"width": "25%"
									},
									{
										data: "last_login_obj",
										render: {
											_: "formatted_last_login",
											sort: "time_sort"
										},
										title: "Last Login ("+timezone_code+")",
										type: "string",
										"width": "15%",
										"asSorting": ["desc", "asc"]
									},
									{
										data: "role_obj",
										render: {
											_: "formatted_role",
										},
										title: "<div>Role</div>",
										type: "string",
										"width": "15%",
									},
									{
										data: "org_obj",
										render: {
											_: "formatted_org",
											sort: "org"
										},
										title: "<div>Organization</div>",
										type: "string",
										"width": "20%"
									},
									{
										data: "super_org_obj",
										render: {
											_: "formatted_super_org",
											sort: "super_org",
											filter: "super_org"
										},
										title: "<div>Super of</div>",
										type: "string",
										"width": "15%"

									},
                                                                        <?php /*if($report_permissions['are_placements_accessible']){ ?>
									{
										data: "placements_setting_obj",
										render: {
											_: "formatted_placements_checkbox"
										},
										title: "<div class=\"tt_placement_header\">Placements</div>",
										"class": "user_table_checkbox",
										orderable: false,
										searchable: false
									},
									<?php } ?>
									<?php if($report_permissions['are_screenshots_accessible']){ ?>
									{
										data: "screenshots_setting_obj",
										render: {
											_: "formatted_screenshots_checkbox"
										},
										title: "<div class=\"tt_screenshot_header\">Screenshots</div>",
										"class": "user_table_checkbox",
										orderable: false,
										searchable: false
									},
									<?php } ?>
									<?php if($report_permissions['are_ad_interactions_accessible']){ ?>
									{
										data: "engagements_setting_obj",
										render: {
											_: "formatted_engagements_checkbox"
										},
										title: "<div class=\"tt_engagement_header\">Engagements</div>",
										"class": "user_table_checkbox",
										orderable: false,
										searchable: false
									},
									<?php } */ ?>
									{
										data: "reset_password_button_obj",
										render: {
											_: "formatted_password_reset_button"
										},
										title: "Password",
										width:"5%",
										class:"dt-body-center",
										orderable: false,
										searchable: false
									},
									{
										data: "ban_obj",
										render: {
											_: "formatted_ban_switch"
										},
										title: "Active?",
										width:"5%",
										class:"dt-body-center",
										orderable: false,
										searchable: false
									}

								]
							});
					table = $('#user_editor_table').DataTable();
					table.on('draw', function() {
						var body = $(table.table().body());
						body.unhighlight();
						body.highlight(table.search());
					});
					var fh_offset_top = 0;
					var navbar_object = $('body > div.navbar.navbar-fixed-top');
					if(navbar_object.css('position') == 'fixed')
					{
						fh_offset_top = navbar_object.height();
					}
					var fixed_header = new $.fn.dataTable.FixedHeader(users_table, {'offsetTop': fh_offset_top});
					$('#user_editor_table tbody').on( 'click', 'tr', function () {
					    selected_row = this;
					    row_data = table.row(selected_row).data();
					} );
					$("#user_editor_header").show();
					$("#user_editor_table").show();
				}
			}
			else
			{
				set_message_timeout_and_show(data.err_msg, 'alert alert-error', 16000);
			}
		}
	})
});

function set_message_timeout_and_show(message, selected_class, timeout)
{
	window.clearTimeout(u_timeout_id);
	$('#u_message_box_content').append(message+"<br>");
	$('#u_message_box').prop('class', selected_class);
	$('#u_message_box').show();
	u_timeout_id = window.setTimeout(function(){
		$('#u_message_box').fadeOut("slow", function(){
			$('#u_message_box_content').html('');
		});
	}, timeout);
}

function set_user_edit_message_timeout_and_show(message, selected_class, timeout)
{
	window.clearTimeout(u_timeout_id);
	$('#user_edit_message_box_content').append(message+"<br>");
	$('#user_edit_message_box').prop('class', selected_class);
	$('#user_edit_message_box').show();
	single_u_timeout_id = window.setTimeout(function(){
		$('#user_edit_message_box').fadeOut("slow", function(){
			$('#user_edit_message_box_content').html('');
		});
	}, timeout);

}
function hide_user_edit_message()
{
	$('#user_edit_message_box_content').html("");
	$('#user_message_message_box').hide();
}

function handle_user_edit_role_change(role)
{
	if(role == "SALES")
	{
		$("#user_edit_field_advertiser").hide();
		$("#user_edit_field_partner").show();
		$("#user_edit_contact_info").show();
		$("#user_edit_check_super_div").show();
		$("#user_edit_owned_partners_div").show();
		$("#user_edit_sample_ads").show();
	}
	else
	{
		if(role == "BUSINESS")
		{
			$("#user_edit_field_advertiser").show();
		}
		else
		{
			$("#user_edit_field_advertiser").hide();
		}
		$("#user_edit_owned_partners_div").hide();
		$("#user_edit_field_partner").hide();
		$("#user_edit_contact_info").hide();
		$("#user_edit_check_super_div").hide();
		$("#user_edit_sample_ads").hide();
	}
	if(role == 'CLIENT' || role == 'AGENCY')
	{
		$(".control-group-ext").hide();
		
	}
}

function display_error_fields(fields)
{
	$.each(fields, function(key, value)
	{
		$("#user_edit_field_"+value).addClass("error");
	});
}

function clear_error_fields()
{
	$(".control-group").removeClass("error");
	$("#user_edit_message_box").hide();
}

function handle_save_user(save_button, user_id)
{
	save_button.disabled = true;
	clear_error_fields();
	hide_user_edit_message();
	var user_name = $('#user_name_'+user_id).text();
	var supers = [];
	var user_save_obj = {};
	user_save_obj['first_name'] = $("#user_edit_first_name").val();
	user_save_obj['last_name'] = $("#user_edit_last_name").val();
	user_save_obj['email'] = $("#user_edit_email").val();
	user_save_obj['role'] = $("#user_edit_role").val();
	user_save_obj['is_super'] = 0;
	user_save_obj['advertiserElem'] = null;
	

	<?php if($report_permissions['are_placements_accessible']){ ?>
	user_save_obj['placements'] = $("#user_edit_check_placements").prop('checked') ? 1 : 0;
	<?php } ?>

	<?php if($report_permissions['are_screenshots_accessible']){ ?>
	user_save_obj['screenshots'] = $("#user_edit_check_screenshots").prop('checked')? 1 : 0;
	<?php } ?>

	<?php if($report_permissions['are_ad_interactions_accessible']){ ?>
	user_save_obj['engagements'] = $("#user_edit_check_engagements").prop('checked')? 1 : 0;
	<?php } ?>
	user_save_obj['banner_intake'] = $("#user_edit_check_creative").prop('checked')? 1 : 0;
	user_save_obj['proposals'] = $("#user_edit_check_proposals").prop('checked')? 1 : 0;
	user_save_obj['sample_ad_builder'] = $("#user_edit_select_sample_ads").val() == 'builder'? 1 : 0;
	user_save_obj['sample_ad_manager'] = $("#user_edit_select_sample_ads").val() !== ''? 1 : 0;
	user_save_obj['partner'] = null; //placeholder
	user_save_obj['advertiserElem'] = null //placeholder

	if(user_save_obj['role'] == "SALES")
	{
		user_save_obj['partner'] = $("#user_edit_partner").val();

		new_partners = $('#user_edit_owned_partners').val();
		if(new_partners != "")
		{
			var supers = $('#user_edit_owned_partners').val().split(',');
		}

		if(supers.indexOf(user_save_obj['partner']) != -1)
		{
			user_save_obj['is_super'] = 1;
		}
		else
		{
			user_save_obj['is_super'] = 0
		}

		var owner_remove_index = owner_partner_remove_list.indexOf(user_save_obj['partner']);
		if(owner_remove_index == -1)
		{
			owner_partner_remove_list.push(user_save_obj['partner']);
		}
		owner_remove_index = owner_partner_add_list.indexOf(user_save_obj['partner']);
		if(owner_remove_index != -1)
		{
			owner_partner_add_list.splice(owner_remove_index, 1);
		}

		user_save_obj['address_1'] = $("#user_edit_contact_addr").val();
		user_save_obj['address_2'] = $("#user_edit_contact_addr_line_two").val();
		user_save_obj['city'] = $("#user_edit_contact_city").val();
		user_save_obj['state'] = $("#user_edit_contact_state").val();
		user_save_obj['zip'] = $("#user_edit_contact_zip").val();
		user_save_obj['phone'] = $("#user_edit_contact_phone").val();
		user_save_obj['owned_partners_to_remove'] = owner_partner_remove_list;
		user_save_obj['owned_partners_to_add'] = owner_partner_add_list;
	}
	else if(user_save_obj['role'] == "BUSINESS")
	{
		user_save_obj['advertiserElem'] = $("#user_edit_advertiser").val();
	}
	
	var user_role_change_check = true;
	if(previous_role == "CLIENT" || previous_role == "AGENCY")
	{
		user_role_change_check = false;
	}

	var role_change_okay = false;
	//First check if changes should be made
	if(user_role_change_check)
	{
		if(user_save_obj['role'] != previous_role)
		{
			$.ajax({
					type: "POST",
					url: '/user_editor/verify_role_change',
					async: false,
					data: {user_id: user_id, old_role: previous_role, new_role: user_save_obj['role']},
					dataType: 'json',
					success: function(data, textStatus, jqXHR){
						if(data.is_success)
						{
							if(data.user_dependencies == "")
							{
								role_change_okay = true;
							}
							else
							{
								//display error messages
								set_user_edit_message_timeout_and_show("User Role Issues found: Resolve before changing user role:<br>"+data.user_dependencies, 'alert alert-error', 16000);
								save_button.disabled = false;
							}
						}
						else
						{
							set_user_edit_message_timeout_and_show("Error: "+data.err_msg, 'alert alert-error', 16000);
							save_button.disabled = false;
						}
					},
					error: function(xhr, textStatus, error){
						set_user_edit_message_timeout_and_show('Error 71228: Unable to validate role change for '+user_name, 'alert alert-error', 16000);
						save_button.disabled = false;
					}
				});
		}
		else
		{
			role_change_okay = true;
		}
	}
	else
	{
		role_change_okay = true;
	}

	if(role_change_okay == true)
	{
		$.ajax({
					type: "POST",
					url: '/user_editor/save_user_details',
					async: true,
					data: {user_id: user_id, user_data:user_save_obj},
					dataType: 'json',
					success: function(data, textStatus, jqXHR){
						if(data.is_success)
						{
							save_button.disabled = false;
							set_message_timeout_and_show('Updated '+user_name, 'alert alert-success', 3000);
							$("#user_edit_modal").modal("hide");
							//Update row
							row_data.user_name_email_obj.formatted_user_name = format_user_name_cell(user_id, user_save_obj['email'], user_save_obj['first_name'], user_save_obj['last_name'])
							row_data.role_obj.formatted_role = format_role(user_save_obj['role']);
							if(user_save_obj['role'] != "ADMIN" && user_save_obj['role'] != "OPS" && user_save_obj['role'] != "CREATIVE")
							{
								if(user_save_obj['role'] == "BUSINESS")
								{
									row_data.org_obj.formatted_org = format_business_org_cell(user_save_obj['advertiserElem']);
									row_data.org_obj.org = advertisers[user_save_obj['advertiserElem']]['name'];
								}
								else
								{
									row_data.org_obj.formatted_org = format_org_cell(user_save_obj['partner']);
									row_data.org_obj.org = get_partner_names([user_save_obj['partner']]);
									row_data.super_org_obj.formatted_super_org = format_user_super_cell(supers);
									row_data.super_org_obj.super_org = get_partner_names(supers);
								}
							}


							<?php if($report_permissions['are_placements_accessible']){ ?>
							row_data.placements_setting_obj.formatted_placements_checkbox = format_report_setting_checkbox(user_save_obj['placements'], user_id, "placement");
							<?php } ?>

							<?php if($report_permissions['are_screenshots_accessible']){ ?>
							row_data.screenshots_setting_obj.formatted_screenshots_checkbox = format_report_setting_checkbox(user_save_obj['screenshots'], user_id, "screenshot");
							<?php } ?>

							<?php if($report_permissions['are_ad_interactions_accessible']){ ?>
							row_data.engagements_setting_obj.formatted_engagements_checkbox = format_report_setting_checkbox(user_save_obj['engagements'], user_id, "engagement")
							<?php } ?>
							table.row(selected_row).data(row_data);
							table.draw(false); //Redraw table
							initialize_ban_switches();
							initialize_tooltips();
						}
						else
						{
							set_user_edit_message_timeout_and_show("Error: "+data.err_msg, 'alert alert-error', 16000);
							if(data.bad_fields != null)
							{
								display_error_fields(data.bad_fields);
							}
							save_button.disabled = false;
						}
					},
					error: function(xhr, textStatus, error){

						set_user_edit_message_timeout_and_show('Error 71228: Unable to update user '+user_name, 'alert alert-error', 16000);
						save_button.disabled = false;
					}
				});
	}

}

$("#u_message_box > button").click(function(){
	window.clearTimeout(u_timeout_id);
	$('#u_message_box').fadeOut("fast", function(){
		$('#u_message_box_content').html('');
	});
});

$("#user_edit_message_box > button").click(function(){
	window.clearTimeout(single_u_timeout_id);
	$('#user_edit_message_box').fadeOut("fast", function(){
		$('#user_edit_message_box_content').html('');
	});
});
</script>
