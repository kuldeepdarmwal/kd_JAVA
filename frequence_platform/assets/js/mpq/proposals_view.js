var proposals_table;
var pv_timeout_id;
var pv_io_data;

// add checkbox filter to datatable
if(user_role == 'admin')
{
	$.fn.dataTableExt.afnFiltering.push(function(oSettings, aData, iDataIndex) {
		var checked = $('#demo_prop_checkbox').is(':checked');
		if (checked && aData[6] == 'demo' || aData[6] == 'nodemo') {
			return true;
		}
		if (!checked && aData[6]  == 'nodemo') {
			return true;
		}
		return false;
	});
}

$(document).ready(function(){

	$('#pv_proposals_table').show();

	var proposals_table_data = format_proposals_table_data(pv_table_data);
	var proposals_table_columns = [
		{
			data: "opportunity",
			title: "Opportunity",
			render: {
				_: "formatted",
				sort: "sort",
				filter: "filter"
			},
			type: "string",
			"class": "pv_custom_column pv_column_padding"
		},
		{
			data: "updated",
			title: "Updated",
			render: {
				_: "formatted",
				sort: "sort"
			},
			type: "string",
			"class": "pv_auto_column"
		},
		{
			data: "max_value",
			title: "Value",
			render: {
				_: "formatted",
				sort: "sort"
			},
			type: "num",
			"class": "pv_auto_column pv_column_padding"
		},
		{
			data: "owner",
			title: "Opportunity Owner",
			render: {
				_: "formatted",
				sort: "sort"
			},
			type: "string",
			"class": "pv_custom_column pv_column_padding"
		},
		{
			data: "author",
			title: "Proposal Author",
			render: {
				_: "formatted",
				sort: "sort"
			},
			type: "string",
			"class": "pv_elastic_column pv_column_padding"
		},
		{
			data: "actions",
			title: "Actions",
			orderable: false,
			searchable: false,
			"class": "pv_auto_column",
			export: false
		},
		{
			data: "demo",
			searchable: true,
			visible : false
		}
	];

	proposals_table = $('#pv_proposals_table').dataTable({
		"data": proposals_table_data,
		"ordering": true,
		"order": [[1, "desc"]],
		"lengthMenu": [
			[25, 50, 100],
			[25, 50, 100]
		],
		"columnDefs": [{}],
		"rowCallback": function(row,data) {
			if (data.demo == "demo" && data.is_demo_login != 1){
				var $row = $(row);
				$row.css({"background-color":"#FBEFF2"})
			}
		},
		"initComplete": function(settings) {
		},
		"drawCallback": function(settings) {
			$('.pv_tooltip').tooltip();
		},
		"columns": proposals_table_columns
	});

	$('#csv_download_button').click(function() {
		create_excel_indexed(proposals_table_columns, proposals_table_data, 'Proposals');
	});
});

//adding checkbox filter
	$(document).ready(function() {
		$('#demo_prop_checkbox').on("click", function(e) {
			console.log('click');
			proposals_table.fnDraw();
		});
	});

function format_proposals_table_data(data)
{
	$.each(data, function(key, value){

		var formatted_unique_display_id = this.unique_display_id;
		if(this.unique_display_id === null)
		{
			formatted_unique_display_id = "None Available";
		}

		this.proposal_name = (this.proposal_name ==  "" || this.proposal_name == null)? "" : " : "+ this.proposal_name;
		this.opportunity = {
			formatted: '<div class="pv_modal_column_container">' +
				'<div class="pv_opportunity_name pv_tooltip" style="display:inline-block; font-size: 16px; font-weight: 400;" data-trigger="hover" data-placement="top" ' +
				'data-title="ID: '+formatted_unique_display_id+'">' + this.advertiser_name + this.proposal_name+ '</div>' +
				'<div class="pv_opportunity_name" style="display:inline-block; font-size: 12px;">' + this.industry_name + '</div></div>',
			sort: this.advertiser_name,
			filter: this.advertiser_name + formatted_unique_display_id
		};
		var local_date = new Date(this.creation_time);
		var timestamp = local_date.getTime();

		var local_creation_time = local_date.getFullYear() + "-" + ('0' + (local_date.getMonth() + 1)).slice(-2) + "-" + ('0' + local_date.getDate()).slice(-2);

		this.updated = {
			formatted: '<div class="pv_creation_time_container">'+local_creation_time+'</div>',
			sort: timestamp
		};

		var term_text = "";
		if(term_text == "weekly")
		{
			term_text = "week";
		}
		else if(term_text == "daily")
		{
			term_text = "day";
		}
		else
		{
			term_text = "month";
		}

		if(this.duration > 1)
		{
			term_text += "s";
		}

		var grand_total_dollars_formatted = '$' + number_with_commas(this.grand_total_dollars);

		this.max_value = {
			formatted: '<div>'+grand_total_dollars_formatted+'</div><div>'+this.duration+' '+term_text+'</div>',
			sort: this.grand_total_dollars
		};

		var owner_formatted = '<div><div style="font-size: 12px;">'+this.partner_name+'</div><div style="font-size: 16px;">'+this.owner_name+'</div></div>';
		var owner_sort = this.owner_user_id;
		if(this.owner_user_id == null)
		{
			owner_formatted = "none";
			owner_sort = 0;
		}
		this.owner = {
			formatted: owner_formatted,
			sort: owner_sort
		};

		var author_formatted = this.creator_name;
		var author_sort = this.creator_user_id;
		if(this.creator_user_id == null)
		{
			author_formatted = "none";
			author_sort = 0;
		}
		this.author = {
			formatted: author_formatted,
			sort: author_sort
		};

		var download_class = "btn btn-link";
		var download_href = "";
		var download_title = "download";
		if(this.unique_display_id == null || this.process_status === "queued-auto" || this.process_status === "snapshots-processing")
		{
			download_class = "btn btn-link disabled";
			download_title = "pending";
		}
		else if(this.is_gate_complete !== null && this.is_targeting_complete !== null && this.is_budget_complete !== null && this.is_builder_complete !== null && this.is_budget_complete == 1)
		{
			download_href = "/proposals/generate_builder_pdf/"+this.unique_display_id;			
		}
		else
		{
			if(this.pdf_location == null)
			{
				download_class = "btn btn-link disabled";
				download_title = "pending";
			}
			else
			{
				download_href = "/proposals/"+this.proposal_id+"/download/"+this.pdf_title;
			}
		}

		this.actions = '<div class="pv_action_container"> \
		<a id="pv_edit_'+this.id+'" href="/rfp/targeting/'+this.unique_display_id+'" class="btn btn-link pv_new_rfp_button pv_tooltip pv_edit_rfp_button" data-trigger="hover" data-placement="top" data-title="edit" data-rfp-type="edit" data-rfp-id="'+this.proposal_id+'" data-mpq-id="'+this.id+'"><i class="icon-pencil"></i></a> \
		<a id="pv_copy_'+this.id+'" href="/rfp/copy/'+this.unique_display_id+'" class="btn btn-link pv_new_rfp_button pv_tooltip pv_copy_rfp_button" data-trigger="hover" data-placement="top" data-title="copy" data-rfp-type="copy" data-rfp-id="'+this.proposal_id+'"><i class="icon-copy"></i></a> \
		<button id="preview_modal_'+this.id+'" type="button" class="btn btn-link pv_preview_modal_button pv_tooltip" data-trigger="hover" data-placement="top" data-title="quick view" data-mpq-id="'+this.id+'"><i class="icon-resize-full"></i></button> \
		<a id="pv_download_'+this.id+'" href="'+download_href+'" target="_blank" class="'+download_class+' pv_download_button pv_tooltip" data-trigger="hover" data-placement="top" data-title="'+download_title+'" data-rfp-id="'+this.proposal_id+'"><i class="icon-download-alt"></i></a>';
		if(is_io_permitted)
		{
			var io_title = 'create io';
			var create_io_class = 'pv_create_io_button';

			if(this.can_submit_io == '0')
			{
				io_title = 'no io products';
				create_io_class = 'disabled';
			}

			this.actions += '<a id="pv_create_io_'+this.id+'" href="#" class="btn btn-link '+create_io_class+' pv_tooltip" data-trigger="hover" data-placement="top" data-title="'+io_title+'" data-mpq-id="'+this.id+'"><i class="icon-indent-right"></i></a>';
		}
		this.actions += '</div>';
	});
	return data;
}

$(document).on('click', '.pv_download_button', function(e){
	if($(this).hasClass('disabled'))
	{
		e.preventDefault();
	}

});

$(document).on('click', 'a.disabled', function(e){
	e.preventDefault();
});

$(document).on('click', '.pv_edit_rfp_button', function(e){
	e.preventDefault();
	var button_object = this;
	var mpq_id = $(button_object).attr('data-mpq-id');
		$.ajax({
		type: "POST",
		url: '/mpq_v2/is_rfp_editable',
		async: true,
		dataType: 'json',
		data:
		{
			mpq_id: mpq_id
		},
		success: function(data, textStatus, jqXHR){
			if(data.is_success == true)
			{
				if(data.is_editable_success == true)
				{
					window.location = $(button_object).attr('href');
				}
				else
				{
					pv_set_message_timeout_and_show("The proposal you are trying to edit is no longer available.  Refresh the page to get the newest list of submitted proposals.", 'alert alert-warning', 20000);
				}
			}
			else
			{
				if(typeof data.errors !== "undefined")
				{
					pv_set_message_timeout_and_show(data.errors, 'alert alert-error', 16000);
				}
				else
				{
					pv_set_message_timeout_and_show("Error 702500: An unknown error occurred", 'alert alert-error', 16000);
				}
			}
		},
		error: function(jqXHR, textStatus, error){
			pv_set_message_timeout_and_show("Error 701500: Server Error", 'alert alert-error', 16000);
		}
	});
});

$(document).on('click', '.pv_preview_modal_button', function(e){
	e.preventDefault();
	var rfp_id = $(this).attr('data-mpq-id');
	$.ajax({
		type: "POST",
		url: '/mpq_v2/get_rfp_summary_html',
		async: true,
		dataType: 'json',
		data:
		{
			rfp_id: rfp_id
		},
		success: function(data, textStatus, jqXHR){
			if(data.is_success == true)
			{
				$('#pv_preview_modal .modal-body').html(data.html_data);
				$('#pv_preview_modal').modal('show');
			}
			else
			{
				if(typeof data.errors !== "undefined")
				{
					pv_set_message_timeout_and_show(data.errors, 'alert alert-error', 16000);
				}
				else
				{
					pv_set_message_timeout_and_show("Error 692500: An unknown error occurred", 'alert alert-error', 16000);
				}
			}
		},
		error: function(jqXHR, textStatus, error){
			pv_set_message_timeout_and_show("Error 691500: Server Error", 'alert alert-error', 16000);
		}
	});
});

function pv_set_message_timeout_and_show(message, selected_class, timeout)
{
	window.clearTimeout(pv_timeout_id);
	$('#pv_message_box_content').append(message+"<br>");
	$('#pv_message_box').prop('class', selected_class);
	$('#pv_message_box').show();
	pv_timeout_id = window.setTimeout(function(){
		$('#pv_message_box').fadeOut("slow", function(){
			$('#pv_message_box_content').html('');
		});
	}, timeout);
}

$("#pv_message_box > button").click(function(){
	window.clearTimeout(pv_timeout_id);
	$('#pv_message_box').fadeOut("fast", function(){
		$('#pv_message_box_content').html('');
	});
});

$(document).on('click', '.pv_create_io_button', function(e){
	e.preventDefault();
	$('#pv_io_modal').modal('show');
	$('#pv_io_modal_body_content').hide();
	$('#pv_io_modal_loading_img').css('display', 'block');
	var mpq_id = $(this).attr('data-mpq-id');
	$.ajax({
		type: "POST",
		url: '/proposals/get_budget_options_for_create_io',
		async: true,
		dataType: 'json',
		data:
		{
			mpq_id: mpq_id
		},
		success: function(data, textStatus, jqXHR){
			$('#pv_io_modal_loading_img').css('display', 'none');
			if(data.is_success == true)
			{
				populate_io_modal_window(data.data);
				$('#pv_io_modal_body_content').show();
			}
			else
			{
				if(typeof data.errors !== "undefined")
				{
					pv_set_message_timeout_and_show(data.errors, 'alert alert-error', 16000);
				}
				else
				{
					pv_set_message_timeout_and_show("Error 824500: An unknown error occurred", 'alert alert-error', 16000);
				}
			}
		},
		error: function(jqXHR, textStatus, error){
			pv_set_message_timeout_and_show("Error 823500: Server Error", 'alert alert-error', 16000);
		}
	});
});

function populate_io_modal_window(data)
{
	var modal_html = '<table id="pv_io_modal_table"><tr><td class="pv_io_modal_left_column"></td>';
	var button_html = '<tr><td></td>';
	var after_discount_html = '';
	var totals_array = [];
	var after_discount_array = [];
	var term_array = [];
	pv_io_data = {};
	$.each(data, function(key, value){
		totals_array.push(0);
		after_discount_array.push(0);
		term_array.push(value.term);
		modal_html += '<td class="pv_io_modal_budget_column"><strong>'+value.option_name+'</strong></td>';
		button_html += '<td class="pv_io_modal_budget_column"><button type="button" class="btn btn-inverse pv_io_modal_create_button" data-option-id="'+value.option_id+'">Make IO</button></td>';
		pv_io_data[value.option_id] = value.mpq_id;
	});

	button_html += '</tr>';
	modal_html += '</tr>';

	$.each(data[0].products, function(product_key, product_value){
		var product_html = '<tr>';
		product_html += '<td class="pv_io_modal_left_column">'+product_value.name+'</td>';
		$.each(data, function(option_key, option_value){
			var io_term = "";
			if(product_value.after_discount === false)
			{
				io_term = option_value.term;
				totals_array[option_key] += option_value.products[product_key].cost;
			}
			else
			{
				io_term = "one time";
				after_discount_array[option_key] += option_value.products[product_key].cost;
			}
			product_html += '<td class="pv_io_modal_budget_column"><strong>$'+number_with_commas(option_value.products[product_key].cost)+'</strong> '+io_term+'</td>';
		});
		product_html += '</tr>';
		if(product_value.after_discount === false)
		{
			modal_html += product_html;
		}
		else
		{
			after_discount_html += product_html;
		}
	});

	var sub_total_html = '<tr><td class="pv_io_modal_left_column">Subtotal</td>';
	var discount_html = '<tr><td class="pv_io_modal_left_column">'+data[0].discount_name+'</td>';
	var total_html = '<tr><td class="pv_io_modal_left_column">Total</td>';
	var grand_total_html = '<tr><td class="pv_io_modal_left_column">Grand Total</td>';

	var should_show_discount_row = Boolean(data[0].discount_name);

	$.each(data, function(key, value){
		var discount_val = (value.discount*totals_array[key]/100);
		sub_total_html += '<td class="pv_io_modal_budget_column"><strong>$'+number_with_commas(totals_array[key])+'</strong> '+term_array[key]+'</td>';
		discount_html += '<td class="pv_io_modal_budget_column"><strong>-$'+number_with_commas(discount_val)+'</strong> '+term_array[key]+'</td>';
		total_html += '<td class="pv_io_modal_budget_column"><strong>$'+number_with_commas(totals_array[key] - discount_val)+'</strong> '+term_array[key]+'</td>';
		var total_before_discount = (totals_array[key] - discount_val)*value.duration;
		var after_discount = after_discount_array[key];
		total_before_discount = Math.round(total_before_discount) != total_before_discount ? parseFloat(total_before_discount.toFixed(2)) : total_before_discount;
		after_discount = Math.round(after_discount) != after_discount ? parseFloat(after_discount.toFixed(2)) : after_discount;
		grand_total_html += '<td class="pv_io_modal_budget_column"><strong>$'+number_with_commas(total_before_discount + after_discount)+'</strong> </td>';
	});

	sub_total_html += '</tr>';
	discount_html += '</tr>';
	total_html += '</tr>';
	grand_total_html += '</tr>';
	modal_html += sub_total_html + ((should_show_discount_row) ? discount_html : '') + total_html + after_discount_html + grand_total_html + button_html;
	modal_html += '<tr>';
	modal_html += '</table>';
	$('#pv_io_modal_body_content').html(modal_html);
}

$(document).on('click', '.pv_io_modal_create_button', function(e){
	e.preventDefault();
	var temp_io_data = $(this).data();
	if(temp_io_data.hasOwnProperty('optionId') && pv_io_data.hasOwnProperty(temp_io_data.optionId))
	{
		$('#pv_create_io_form_mpq_id').val(pv_io_data[temp_io_data.optionId]);
		$('#pv_create_io_form_option_id').val(temp_io_data.optionId);
		$('#pv_create_io_form').submit();
	}
	else
	{
		pv_set_message_timeout_and_show("Error 4167318: Unable to create insertion order from the selected proposal", 'alert alert-error', 16000);
	}
});

function number_with_commas(number)
{
	return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
