<?php

?>
<script src="/libraries/external/select2/select2.js"></script>
<script type="text/javascript" src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.js"></script>
<script>

var g_the_table;


function create_advertiser_row(advertiser, tmpi_accounts)
{
	var advertiser_cell = format_advertiser_cell(advertiser.partner_name, advertiser.ae_name, advertiser.f_advertiser_name, advertiser.f_advertiser_name, advertiser.id);
	var cell_markup_object = format_accounts_cell_string(advertiser.id, advertiser.f_advertiser_name, tmpi_accounts);
	g_the_table.fnAddData( {
	 	DT_RowId:  "advertiser_row_"+advertiser.id,
	 	f_advertiser_id: advertiser.id,
	 	f_advertiser_name: advertiser.f_advertiser_name,
	 	f_advertiser_string: advertiser_cell,
	 	accounts_cell_string: cell_markup_object.cell_string
     }, true );
}

function destroy_advertiser_row(advertiser_id)
{
	g_the_table.api().row("#advertiser_row_"+advertiser_id).remove().draw();
}

function destroy_rows(accounts)
{
	for ( var i = 0; i < accounts.length; i++ ) {
		g_the_table.api().row("#account_row_"+accounts[i].id).remove().draw();
	}
}

function create_account_only_rows(accounts)
{
	var cell_markup_object;
	for ( var i = 0; i < accounts.length; i++ ) {
		cell_markup_object = format_accounts_cell_string(null, null, [accounts[i]]);
		g_the_table.fnAddData( {
			DT_RowId:  "account_row_"+accounts[i].id,
			f_advertiser_id: null,
			f_advertiser_name: null,
			f_advertiser_string: format_advertiser_cell(null, null, null,  accounts[i].id),
			accounts_cell_string: cell_markup_object.cell_string
        }, true );
	}
}


function do_id_comparison(old_elements, new_elements) {
    var matches = [];
    var old_only = [];
    var new_only = [];
    var in_old_only = false;
    var in_new_only_array = [];
    if(old_elements == null && new_elements == null)
    {
    	//do nothing and return empty arrays for old, new and match
    }
    else if(old_elements == null)
    {
    	new_only = new_elements;
    }
    else if (new_elements == null)
    {
    	old_only = old_elements;
    }
    else{
    	for ( var i = 0; i < old_elements.length; i++ ) {
	    	in_old_only = true;
	        for ( var e = 0; e < new_elements.length; e++ ) {
	        	if(i==0)
	        	{
	        		in_new_only_array[e] = true; 
	        	}
	            if ( old_elements[i].id === new_elements[e].id )
	            {
	            	in_old_only = false;
	            	in_new_only_array[e] = false; 
	            	matches.push( old_elements[i] );
	            } 
	            if(i === old_elements.length-1) //if last old, then we can see how the new ones look look
	            {
	            	if(in_new_only_array[e]) new_only.push( new_elements[e] );
	            }
	        }
	        if(in_old_only)
	        {
	        	old_only.push( old_elements[i] );
	        }
	    }
    }
    return {'matches' : matches, 'old_only' : old_only, 'new_only': new_only};
}


function handle_table_row_changes(f_advertiser_id, f_advertiser_name, new_linked_accounts, previous_linked_accounts)
{
	//first we have to figure out which accounts are old only, matched and new only 
	var compare_array = do_id_comparison( previous_linked_accounts, new_linked_accounts);
	//the old only ones need new account-only rows
	if(compare_array.old_only.length > 0)
	{
		create_account_only_rows(compare_array.old_only);
	}
	//the new only accounts need their previous account-only rows removed
	if(compare_array.new_only.length > 0)
	{
		destroy_rows(compare_array.new_only);
	}
	///then we can populate the advertiser row with all the new
	//update the advertiser row with new links
	var formatted_new_accounts = format_accounts_cell_string(f_advertiser_id, f_advertiser_name, new_linked_accounts);
	g_the_table.fnUpdate( formatted_new_accounts.cell_string, $('#advertiser_row_'+f_advertiser_id), 1 , 0 , 0);
}

function prefill_linker_form(f_advertiser_id, f_advertiser_name)
{
	//get elements for select2
	$.ajax({
		url: '/third_party_linker/get_third_party_account_details_for_advertiser',
		type: 'POST',
		async: true,
		dataType: 'json',
		data: {
			f_advertiser_id: f_advertiser_id
		},
		success: function(data, status) {
			if(data.is_success)
			{
				var previous_linked_accounts = jQuery.parseJSON(data.assigned_accounts);
				var formatted_accounts = format_accounts_cell_string(f_advertiser_id, f_advertiser_name, previous_linked_accounts);
				$("#accounts_select").select2({
					placeholder:"Type to find accounts to link",
					multiple: true,
					data: data.potential_accounts,
					matcher: function(term, text) {
					      var has = true;
					      var words = term.toUpperCase().split(" ");
					      for (var i =0; i < words.length; i++){
					        var word = words[i];
					        has = has && (text.toUpperCase().indexOf(word) >= 0); 
					      }
					      return has;
					},
					initSelection: function(element, callback) {               
            		}
				}).select2('data',formatted_accounts.select_object);
				//attach advertiser id to update method
				$( "#update_accounts_button").unbind( "click" );//remove previous click handler m@ redo this later
				$( "#update_accounts_button" ).click(function() {
					g_the_table.fnUpdate( '<i class="icon-rotate-right icon-spin icon-2x f_spinner_icon"></i>', $('#advertiser_row_'+f_advertiser_id), 1 , 0 , 0);
				  	$.ajax({
						url: '/third_party_linker/link_accounts_to_advertiser',
						type: 'POST',
						async: true,
						dataType: 'json',
						data: {
							f_advertiser_id: f_advertiser_id,
							tp_accounts_string: $("#accounts_select").val()
						},
						success: function(data, status) {
							if(data.is_success)
							{
								handle_table_row_changes(f_advertiser_id, f_advertiser_name, jQuery.parseJSON(data.new_linked_accounts), previous_linked_accounts);
							}	
							else
							{
								alert('some error occurred 987ibo');
							}

						},
						error: function(e, data) {
							console.log('error 087g9ub:',e);
							alert('an error occurred: 9u80guvyf');
						}
					});
				});
				$("#edit_accounts_form_modal_body").css("display","block");
				$("#modal_spinner").css("display","none");
			}
			else
			{
				console.log(data);
				alert('some error occurred 908dg9uibjl');
			}
		},
		error: function(e, data) {
			console.log(e);
			alert('some error occurred 08e97gyb');
		}
	});
}

function format_edit_button(f_advertiser_id,f_advertiser_name)
{
	return '<div   class="btn btn-link edit_button pull-right" data-f-advertiser-id = "'+f_advertiser_id+'" data-f-advertiser-name="'+f_advertiser_name+'" ><i class="icon-edit  f_clickable_icon"> edit</i></div>';
}

function format_accounts_cell_string(f_advertiser_id, f_advertiser_name, tmpi_accounts_object)
{
	var edit_icon ;
	if(f_advertiser_id != null)
	{
		edit_icon  = format_edit_button(f_advertiser_id, f_advertiser_name);
	}
	else
	{
		edit_icon = '';
	}
	var account_names_string = '<div class="accounts_cell_container"><div class="span12"><div class="span9">';
	var accounts_select2 = [];
	if(tmpi_accounts_object != null)
	{
		$.each(tmpi_accounts_object, function(key, acct){
			account_names_string += '<h5>'+acct.acct_name+' <small>[id: '+acct.acct_id+', '+stringify_third_party_data_source_id(acct.tp_source)+']</small></h5>';
			accounts_select2[key] = {id: acct.id, text:acct.acct_name + ' [id: '+acct.acct_id+', '+stringify_third_party_data_source_id(acct.tp_source)+']'};

		});
	}
	account_names_string += '</div><div class="span3 pull-right">'+edit_icon+'</div></div></div>';
	return {cell_string: account_names_string, select_object: accounts_select2};
}

function format_advertiser_cell(partner_name, ae_name, f_advertiser_name, this_row_id)
{
	var f_advertiser_string;
	if(f_advertiser_name != null)
	{
		f_advertiser_string = '<small class="muted">'+partner_name+" - "+ae_name+"</small><br><h5>"+f_advertiser_name+"<h5>";	
	}
	else
	{
		f_advertiser_string = '<div class="advertiser_cell_wrapper "><div class=" btn btn-link advertiser_cell_for_account" id = "advertiser_cell_for_account_'+this_row_id+'" data-account-id="'+this_row_id+'" ><i class="icon-plus  f_clickable_icon"> add advertiser to account</i><div></div>';
	}
	return f_advertiser_string;
}

function format_table_data(data)
{
	$.each(data, function(key, value){
		var tmpi_accounts_object = jQuery.parseJSON(value.tmpi_accounts_object);
		var formatted_accounts ;
		formatted_accounts = format_accounts_cell_string(value.f_advertiser_id, value.f_advertiser_name, tmpi_accounts_object);
		this.accounts_cell_string = formatted_accounts.cell_string;
		this.f_advertiser_string = format_advertiser_cell(this.partner_name, this.ae_name, this.f_advertiser_name, this.account_only_id);
	});
	return data;
}

function stringify_third_party_data_source_id(third_party_source_id)
{
	switch(third_party_source_id)
	{
		case '1':
			return "<?php echo tmpi_product_names::clicks."/".tmpi_product_names::inventory."/".tmpi_product_names::directories;?>";
			break;
		case '2':
			return "<?php echo carmercial_product_names::content;?>";
			break;
		default:
			return "Unknown Source";
			break;
	}
}

$(document).ready(function () {
	$("#loading_img").show();
	$.ajax({
		url: '/third_party_linker/get_all_linked_advertisers_and_accounts',
		type: 'POST',
		async: true,
		dataType: 'json',
		data: {},
		success: function(data, status) {
			if(data.is_success)
			{
				g_the_table = $("#advertisers_table").dataTable({
					destroy: true,
					paging:   true,
					searching:   true,
					ordering: true,
					info:     false,
					order: [[0,'asc']],
					lengthMenu: false,
					data: format_table_data(data.data),
					columns: [
						{
							data: null,
							title: '<span class="muted">Advertiser</span>',
							width: '550px',
							render:{
								_: "f_advertiser_name",
								display: "f_advertiser_string"
							},
						},
						{
							data: "accounts_cell_string",
							title: '<span class="muted">Accounts</span>',
							width: '550px',
							searchable: true
						},
						{
							data: 'f_advertiser_name',
							visible: false,
							searchable: true
						}
					]
				});
				$("#loading_img").hide();
				$("#main_content").show();
			}
			else
			{
				alert('some error occurred 90ipdn');
			}
		},
		error: function(e, data) {
			console.log('error 09897f:',e);
		}
	});
	$(document.body).on('click' ,'.edit_button',function(){
		$('#edit_accounts_modal_title').html( $(this).attr('data-f-advertiser-name'));
		$("#edit_accounts_form_modal_body").css("display","none");
		$("#modal_spinner").css("display","block");
		$('#edit_accounts_modal').modal('show');
		prefill_linker_form($(this).attr('data-f-advertiser-id'), $(this).attr('data-f-advertiser-name'));
	});
	$(document.body).on('click' ,'#update_accounts_button',function(){
		$('#edit_accounts_modal').modal('hide');		
	});


	function format_s2(obj){
			var markup = '<small style="font-size:8pt;">' + obj.partner_name + ': ' + obj.ae_name + '</small> <br>&nbsp;<b>' + obj.f_advertiser_name + '</b>';
			return markup;
	};

	function format_s2_selected(obj){
			var markup = '<small style="font-size:8pt;">' + obj.partner_name + ': ' + obj.ae_name + '</small> &nbsp;<b>' + obj.f_advertiser_name + '</b>';
			return markup;
	};

	$(document.body).on('click' ,'.advertiser_cell_for_account',function(){
		var account_id =  $(this).attr('data-account-id');
		$(this).parent().html('<input type="hidden" style="min-width: 400px; " id="advertiser_select_for_'+account_id+'" />');

		$('#advertiser_select_for_'+account_id+'').select2({
	    	minimumInputLength: 0,
	    	placeholder: "Select advertiser",
	    	ajax: {
				url: "/third_party_linker/get_advertisers",
				type: 'POST',
				dataType: 'json',
				data: function (term, page) {
					term = (typeof term === "undefined" || term == "") ? "%" : term;
					return {
						q: term,
						page_limit: 20,
						page: page
					};
				},
				results: function (data) {
					return {results: data.results, more: data.more};
				}
			},
			formatResult: format_s2,
			formatSelection: format_s2_selected,
			allowClear: true
	    }).select2("open");

		$('#advertiser_select_for_'+account_id+'').on("change", function(e) { 
			///add this account to selected advertiser
			g_the_table.fnUpdate( '<i class="icon-rotate-right icon-spin icon-2x f_spinner_icon"></i>', $('#account_row_'+account_id), 0 , 0 , 0);
			$.ajax({
				url: '/third_party_linker/add_single_account_to_advertiser',
				type: 'POST',
				async: true,
				dataType: 'json',
				data: {
					f_advertiser_id: e.val,
					account_id: account_id
				},
				success: function(data, status) {
					if(data.is_success)
					{
						///destroy this account only row
						destroy_rows([{ id: account_id }]);
						///destroy other advertiser/account rows with this advertiser
						destroy_advertiser_row(e.val);
						///create new advertiser/account row
						create_advertiser_row(e.added, jQuery.parseJSON(data.new_linked_accounts));
					}
					else
					{
						alert('some error occurred 654357');
					}
				},
				error: function(e, data) {
					console.log('error 09897f:',e);
				}
			});
			

		});



	});
});
</script>
