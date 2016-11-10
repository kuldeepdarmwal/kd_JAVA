var all_ios_table;
var all_ios_timeout_id;

//Push checkbox filter to the datatable
if(user_role == 'admin')
{
	$.fn.dataTableExt.afnFiltering.push(function(oSettings, aData, iDataIndex) {
		var checked = $('#demo_io_checkbox').is(':checked');
		var index = 13;
		
		if(io_submit_allowed && show_forecast_column)
		{
			index = 14;
		}
		
		if (checked && aData[index] == 'demo' || aData[index] == 'nodemo') {
			return true;
		}
		if (!checked && aData[index]  == 'nodemo') {
			return true;
		}
		return false;
	  });
}		   

$(document).ready(function(){
	$('#all_ios_table').show();

	var columns = [
			{
				data: "opportunity",
				title: "ORDER",
				render: {
					_: "formatted",
					sort: "sort",
					filter: "filter"
				},
				type: "string",
				"class": "all_ios_elastic_column"
			},
			{
				data: "created",
				title: "CREATED",
				render: {
					_: "formatted",
					sort: "sort",
					filter: "filter"
				},
				type: "string",
				"class": "all_ios_auto_column"
			},
			{
				data: "owner",
				title: "OPPORTUNITY OWNER",
				render: {
					_: "formatted",
					sort: "sort",
					filter: "filter"
				},
				type: "string",
				"class": "all_ios_elastic_column"
			},
			{
				data: "start_date",
				title: '<div style="padding: 0px 20px;">START</div>',
				type: "string",
				"class": "all_ios_auto_column"
			},
			{
				data: "impressions",
				title: "IMPRS",
				render: {
					_: "formatted",
					sort: "sort",
					filter: "filter"
				},
				type: "num",
				"class": "all_ios_auto_column"				
			},
			{
				data: "opportunity_status",
				title: "<img src='/images/opportunity.png' data-trigger='hover' data-placement='top' data-title='OPPORTUNITY' class='all_ios_tooltip'/>",
				render: {
					_: "formatted",
					sort: "sort"
				},
				type: "string",
				searchable: false,
				"class": "all_ios_flag_column"
			},
			{
				data: "geo_status",
				title: "<img src='/images/geo.png' data-trigger='hover' data-placement='top' data-title='GEO' class='all_ios_tooltip'/>",
				render: {
					_: "formatted",
					sort: "sort"
				},
				type: "string",
				searchable: false,
				"class": "all_ios_flag_column"
			},
			{
				data: "audience_status",
				title: "<img src='/images/audience.png' data-trigger='hover' data-placement='top' data-title='AUDIENCE' class='all_ios_tooltip'/>",
				render: {
					_: "formatted",
					sort: "sort"
				},
				type: "string",
				searchable: false,
				"class": "all_ios_flag_column"
			},
			{
				data: "tracking_status",
				title: "<img src='/images/tracking.png' data-trigger='hover' data-placement='top' data-title='TRACKING' class='all_ios_tooltip'/>",
				render: {
					_: "formatted",
					sort: "sort"
				},
				type: "string",
				searchable: false,
				"class": "all_ios_flag_column"
			},
			{
				data: "flights_status",
				title: "<img src='/images/flights.png' data-trigger='hover' data-placement='top' data-title='FLIGHTS' class='all_ios_tooltip'/>",
				render: {
					_: "formatted",
					sort: "sort"
				},
				type: "string",
				searchable: false,
				"class": "all_ios_flag_column"
			}
		];
		
	if(io_submit_allowed && show_forecast_column)
	{
		columns.push({
				data: "forecast_status",
				title: "<img src='/images/forecast.png' data-trigger='hover' data-placement='top' data-title='O&O FORECAST' class='all_ios_tooltip'/>",
				render: {
					_: "formatted",
					sort: "sort"
				},
				type: "string",
				searchable: false,
				"class": "all_ios_flag_column"
			});
	}
	
	columns.push({
				data: "creative_status",
				title: "<img src='/images/creative.png' data-trigger='hover' data-placement='top' data-title='CREATIVES' class='all_ios_tooltip'/>",
				render: {
					_: "formatted",
					sort: "sort"
				},
				type: "string",
				searchable: false,
				"class": "all_ios_flag_column"
			},{
				data: "status",
				title: "STATUS",
				render: {
					_: "formatted",
					sort: "sort",
					filter: "filter"
				},
				type: "string",
				"class": "all_ios_status_column"
			},
			{
				data: "actions",
				title: "ACTIONS",
				orderable: false,
				searchable: false,
				"class": "all_ios_flag_column"
			},
			{
				data: "demo",                        
				searchable: true,
				visible : false	
			},
			{
				data: "mailgun_error_text",
				title:"<span class='mailgun-header'>EMAIL STATUS</span>",
				type: "string",
				render: {
					_: "formatted",
					sort: "sort"
				},
				"class": "all_ios_flag_column"
			});
	
	all_ios_table = $('#all_ios_table').dataTable({
		"data": format_ios_table_data(all_ios_table_data),
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
                            $row.css({"background-color":"#FBEFF2"});
                    }
		},
		"initComplete": function(settings) {
		},
		"drawCallback": function(settings) {
			$('.all_ios_tooltip').tooltip();
		},
		"columns": columns
	});
	
	if(submission_message !== "")
	{
		all_ios_set_message_timeout_and_show(submission_message, 'alert alert-success', 32000);
	}
	$( 'span.mailgun-header' ).hover( function()
	{
		$(this).tooltip({ 'placement': 'top' , 'title' : 'Status of Zendesk Confirmation Email'}); 
	});
	if( user_role != 'admin' ) {
		$("#all_ios_table th:last-child, #all_ios_table td:last-child").remove();
	}
});

//adding checkbox filter        
	$(document).ready(function() {
	$('#demo_io_checkbox').on("click", function(e) {
		console.log('click');
		all_ios_table.fnDraw();
		});
	$('#all_ios_header_popover').popover({
		trigger: "hover"
		});
	});

function format_ios_table_data(data)
{
	$.each(data, function(key, value){
		var formatted_unique_display_id = this.unique_display_id;
		if(this.unique_display_id === null)
		{
			formatted_unique_display_id = "None Available";
		}
		
		var order_name = "";		
		if(this.order_name !== null)
		{
			order_name = this.order_name;
		}
		
		var order_id = "";
		if(this.order_id !== null)
		{
			order_id = this.order_id;
		}
				
		var advertiser_name = this.advertiser_name;
                var io_advertiser_name = this.io_advertiser_name;
		var advertiser_name_appender = '';
                if (typeof io_advertiser_name !== 'undefined' && io_advertiser_name !== '' && io_advertiser_name !== null)
                {
			advertiser_name = io_advertiser_name;
                }
		
		if (this.source_table !== "Advertisers")
		{
			advertiser_name_appender = '<i style="color: #faa732;position:relative;top:4px;margin-left:10px;" class="material-icons" title="Not Verified">&#xE002;</i>';
		}
		
		if (order_name != "" && order_id != "")
		{
			advertiser_name_appender = advertiser_name_appender + "<div style='margin-top:5px;line-height:13px;'><small>" + order_name + "</small></div><div><small>" + order_id + "</small></div>";
		}
		else if (order_name != "")
		{
			advertiser_name_appender = advertiser_name_appender + "<div style='margin-top:5px;line-height:13px;'><small>" + order_name + "</small></div>";
		}
		else if (order_id != "")
		{
			advertiser_name_appender = advertiser_name_appender + "<div style='margin-top:5px;line-height:13px;'><small>" + order_id + "</small></div>";
		}
	
                this.opportunity = {
			formatted: '<div class="all_ios_modal_column_container"><button id="preview_modal_'+this.id+'" type="button" class="btn btn-link all_ios_preview_modal_button all_ios_tooltip" data-trigger="hover" data-placement="top" data-title="quick view" data-mpq-id="'+this.id+'"><i class="icon-resize-full"></i></button><div class="all_ios_opportunity_name all_ios_tooltip" style="display:inline-block;" data-trigger="hover" data-placement="top" data-title="ID: '+formatted_unique_display_id+'">' + advertiser_name + advertiser_name_appender + '</div></div>',
			sort: advertiser_name,
			filter: advertiser_name + formatted_unique_display_id + order_name + order_id
		};

		var local_creation_time = new Date(this.creation_time);
		var creation_timestamp = local_creation_time.getTime();
		local_creation_time = local_creation_time.getFullYear() + "-" + ('0' + (local_creation_time.getMonth() + 1)).slice(-2) + "-" + ('0' + local_creation_time.getDate()).slice(-2);
		this.created = {
			formatted: '<div class="all_ios_creation_time_container">'+local_creation_time+'</div>',
			sort: creation_timestamp,
			filter: local_creation_time
		};

		var local_last_updated = new Date(this.last_updated);
		var updated_timestamp = local_last_updated.getTime();
		local_last_updated = local_last_updated.getFullYear() + "-" + ('0' + (local_last_updated.getMonth() + 1)).slice(-2) + "-" + ('0' + local_last_updated.getDate()).slice(-2);
		this.updated = {
			formatted: '<div class="all_ios_creation_time_container">'+local_last_updated+'</div>',
			sort: updated_timestamp,
			filter: local_last_updated
		};

		var owner_formatted = '<small>' + this.partner_name + "</small><br />" + this.owner_name;
		var owner_sort = this.partner_name + ": " + this.owner_name;
		var owner_filter = this.partner_name + ": " + this.owner_name;
		if(this.owner_name === null)
		{
			owner_formatted = "none";
			owner_sort = "";
			owner_filter = "none";
		}
		this.owner = {
			formatted: owner_formatted,
			sort: owner_sort,
			filter: owner_filter
		};
		
		var timeseries_months = this.timeseries_months;
		if(this.timeseries_months == 0 || this.timeseries_months == null)
		{
			timeseries_months = 1;
		}
		timeseries_months += " Month";
		if(this.timeseries_months > 1)
		{
			timeseries_months += "s";
		}

		var timeseries_impressions = 0;
		var formatted_timeseries_impressions = "";
		if(this.timeseries_sum_impressions !== null)
		{
			timeseries_impressions = this.timeseries_sum_impressions;
			formatted_timeseries_impressions = '<div>'+number_with_commas(this.timeseries_sum_impressions)+'</div><div style="font-size: 12px;">'+timeseries_months+'</div>';
		}

		this.impressions = {
			formatted: formatted_timeseries_impressions,
			sort: timeseries_impressions,
			filter: timeseries_impressions
			
		};

		var done_icon = '<i style="color: #009900;" class="material-icons" title="Saved">&#xE8E8;</i>';
		var not_done_icon = '<i style="color: #faa732;" class="material-icons" title="Not Saved">&#xE002;</i>';
		var pending_icon = '<i style="color: #FF9100;" class="material-icons" title="Pending">&#xE924;</i>';

		var opportunity_status_html = not_done_icon;
		if (this.opportunity_status == 1 )
		{
			opportunity_status_html = done_icon;
		}else if (this.opportunity_status == 2)
		{
			opportunity_status_html = pending_icon;
		}       
		this.opportunity_status = {
		    formatted: opportunity_status_html,
				sort: this.opportunity_status
		};

		var geo_status_html = not_done_icon;
		if (this.geo_status == 1)
		{
		    geo_status_html = done_icon;
		}else if (this.geo_status == 2)
		{
		    geo_status_html = pending_icon;
		}
		this.geo_status = {
		    formatted: geo_status_html,
				sort: this.geo_status
		};

		var audience_status_html = not_done_icon;
		if (this.audience_status == 1)
		{
		    audience_status_html = done_icon;
		}else if (this.audience_status == 2)
		{
		    audience_status_html = pending_icon;
		}
		this.audience_status = {
		    formatted: audience_status_html,
				sort: this.audience_status
		};

		var flights_status_html = not_done_icon;
		if (this.flights_status == 1)
		{
		    flights_status_html = done_icon;
		}else if (this.flights_status == 2)
		{
		    flights_status_html = pending_icon;
		}
		this.flights_status = {
		    formatted: flights_status_html,
				sort: this.flights_status
		};

		var creative_status_html = not_done_icon;
		if (this.creative_status == 1)
		{
		    creative_status_html = done_icon;
		}else if (this.creative_status == 2)
		{
		    creative_status_html = pending_icon;
		}
		this.creative_status = {
		    formatted: creative_status_html,
				sort: this.creative_status
		};

		var tracking_status_html = not_done_icon;
		if (this.tracking_status == 1)
		{
		    tracking_status_html = done_icon;
		}else if (this.tracking_status == 2)
		{
		    tracking_status_html = pending_icon;
		}
		this.tracking_status = {
			formatted: tracking_status_html,
			sort: this.tracking_status
		};
		
		var forecast_status_html = "";		
		if (this.dfp_enable != null && this.dfp_enable == "1")
		{
			forecast_status_html = not_done_icon;
			if (this.forecast_status == 1)
			{
				forecast_status_html = done_icon;
			}			
		}
		else
		{
			this.forecast_status = "";
		}
		
		this.forecast_status = {
			formatted: forecast_status_html,
			sort: this.forecast_status
		};
		
		var mailgun_error_html = not_done_icon;
		var mailgun_hidden = '<span class="hide">mailgun error</span>';
		if (this.mailgun_error_text === null)
		{
			mailgun_error_html = '';
		}
		else
		{
			var not_done_icon = '<i style="color: #faa732;" class="material-icons" title="Mailgun Email Error">&#xE002;</i>';
			mailgun_error_html = not_done_icon + mailgun_hidden;
		}
		this.mailgun_error_text = {
			formatted: mailgun_error_html,
			sort: this.mailgun_error_text
		};

		var status_html = "<div class='all_ios_status' style='background-color:#666666;'>Saved</div>";
		var status_filter = "Saved";
		var edit_disable = "";
		var disabled_prop = "";
		
		if (this.mpq_type === 'io-submitted')
		{
			status_html = "<div class='all_ios_status' style='background-color:#51b11d;'>Submitted</div>";
			status_filter = "Submitted";
			edit_disable =  user_edit ? "" : " disabled";
			disabled_prop = user_edit ? "" : ' disabled="disabled"';

			if(this.associated_campaigns > 0 && (user_role == 'admin' || user_role == 'ops' || user_role == 'creative'))
			{
				status_html = "<div class='all_ios_status' style='background-color:#2F6904;'>Processed</div>";
				status_filter = "Processed";
			}
		}
		else if (this.mpq_type === 'io-in-review')
		{
			status_html = "<div class='all_ios_status' style='background-color:#DC0800;'>Review</div>";
			status_filter = "Review";
		}
		else if(this.is_locked == true)
		{
			status_html = '<div class="all_ios_status" style="background-color:#faa732">Being Edited</div>';
			status_filter = "Being Edited";
			edit_disable = " disabled";
			disabled_prop = ' disabled="disabled"';
		}
		
		if(user_role == 'sales' && this.mpq_type === 'io-submitted') 
		{
			if(!io_submit_allowed || this.associated_campaigns > 0 )
			{
				edit_disable = " disabled";
				disabled_prop = ' disabled="disabled"';
			}
			else
			{
				edit_disable = "";
				disabled_prop = "";
			}
		}

		this.status = {
		    formatted: status_html,
				sort: status_filter,
				filter: status_filter
		};

		launch_button = build_launch_button(this);

		this.actions = '<div class="all_ios_action_container">'+
		'<a id="all_ios_edit_'+this.id+'" href="#" class="btn btn-link'+edit_disable+' all_ios_tooltip edit_io_button" data-trigger="hover" data-unique-display-id="'+this.unique_display_id+'" data-placement="top" data-title="edit" data-io-type="edit" data-mpq-id="'+this.id+'" '+disabled_prop+'><i class="icon-pencil"></i></a>' +
		launch_button+build_download_tag_button(this,advertiser_name)+build_download_ad_tags_button(this);
		this.actions += '</div>';
	});
        
	return data;
}

function build_download_ad_tags_button(row_data)
{
	if (row_data.o_o_enabled != null && row_data.o_o_enabled == "1")
	{
		var download_ad_tag_disable = " all_ios_tooltip";
		var download_ad_tag_disabled_prop = '';	
		var download_ad_tag_href = '/insertion_orders/download_ad_tags/'+row_data.unique_display_id;
		return '<a id="all_ios_download_ad_tags_'+row_data.id+'" href="'+download_ad_tag_href+'" target="_blank" class="btn btn-link '+download_ad_tag_disable+'" data-trigger="hover" data-placement="top" data-title="ad tags" data-io-type="edit" data-mpq-id="'+row_data.id+'" '+download_ad_tag_disabled_prop+'><i class="icon-tags"></i></a>';
	}
	else
	{
		return "";
	}	
}

function build_download_tag_button(row_data,advertiser_name)
{
        var download_tag_disable = "disabled";
        var download_tag_disabled_prop = ' disabled="disabled"';
        var download_tag_href = 'javascript:void(0);';
        
        if (row_data.tracking_tag_file_id != null && row_data.tracking_tag_file_id != -1)
        {
                download_tag_disable = " all_ios_tooltip";
                download_tag_disabled_prop = '';	
                download_tag_href = '/tag/download_tags/'+row_data.io_advertiser_id+'/'+row_data.tracking_tag_file_id+'/-1/-1/'+row_data.source_table;

        }
        
        return '<a id="all_ios_download_'+row_data.id+'" href="'+download_tag_href+'" target="_blank" class="btn btn-link '+download_tag_disable+'" data-trigger="hover" data-placement="top" data-title="tags" data-io-type="edit" data-mpq-id="'+row_data.id+'" '+download_tag_disabled_prop+'><i class="icon-code"></i></a>';
}

function build_download_ad_tags_button(row_data)
{
        var download_ad_tag_disable = "disabled";
        var download_ad_tag_disabled_prop = ' disabled="disabled"';
        var download_ad_tag_href = 'javascript:void(0);';
        
        if (row_data.o_o_enabled != null && row_data.o_o_enabled == "1")
        {
                download_ad_tag_disable = " all_ios_tooltip";
                download_ad_tag_disabled_prop = '';	
                download_ad_tag_href = '/insertion_orders/download_ad_tags/'+row_data.unique_display_id;

        }

        return '<a id="all_ios_download_ad_tags_'+row_data.id+'" href="'+download_ad_tag_href+'" target="_blank" class="btn btn-link '+download_ad_tag_disable+'" data-trigger="hover" data-placement="top" data-title="ad tags" data-io-type="edit" data-mpq-id="'+row_data.id+'" '+download_ad_tag_disabled_prop+'><i class="icon-tags"></i></a>';
}

function build_launch_button(row_data)
{
	var launch = "";
	if(user_edit)
	{
		var launch_disable = " disabled";
		var launch_disabled_prop = ' disabled="disabled"';
		var launch_href = 'javascript:void(0);';
		var launch_content = '<i class="icon-plane"></i>';
		var tooltip_text = "launch";
		if(row_data.mpq_type === 'io-submitted' && row_data.source_table == "Advertisers" && row_data.demo == 'nodemo')
		{
			launch_disable = " all_ios_tooltip";
			launch_disabled_prop = '';	
			launch_href = '/launch_io/'+row_data.id;

		}
		if(row_data.associated_campaigns > 0)
		{
			launch_disable = " all_ios_tooltip";			
			launch_disabled_prop = '';
			tooltip_text = "edit";	
			launch_content = row_data.associated_campaigns; //Count
		}
		launch = '<a id="all_ios_launch_'+row_data.id+'" href="'+launch_href+'" target="_blank" class="btn btn-link'+launch_disable+' launch_io_button" data-trigger="hover" data-placement="top" data-title="'+tooltip_text+'" data-io-type="edit" data-mpq-id="'+row_data.id+'" '+launch_disabled_prop+'>'+launch_content+'</a>';

	}
	return launch;
}

function create_io_from_existing(object)
{
	var io_type = $(object).attr('data-io-type');
	var mpq_id = $(object).attr('data-mpq-id');
	if(typeof io_type !== "undefined" && typeof mpq_id !== "undefined")
	{
		$('#all_ios_existing_form_method').val(io_type);
		$('#all_ios_existing_form_mpq_id').val(mpq_id);
		$('#all_ios_existing_form').submit();
	}
	else
	{
		all_ios_set_message_timeout_and_show("Error 693003: unable to read data for selected proposals" , 'alert alert-error', 16000);
	}
}

$('#pv_io_link_button').on('click', function(e){
	e.preventDefault();
	$('#all_ios_new_form').submit();
});

$(document).on('click', '.edit_io_button', function(e){
	e.preventDefault();
	var button_object = this;
	if($(button_object).attr('disabled') == 'disabled')
	{
		return;
	}
	var mpq_id = $(button_object).attr('data-mpq-id');
		$.ajax({
		type: "POST",
		url: '/insertion_orders/is_insertion_order_editable',
		async: true,
		dataType: 'json',		
		data: 
		{
			mpq_id: mpq_id
		},
		success: function(data, textStatus, jqXHR){
			if(data.is_success == true)
			{
				if(data.is_editable_success == "success")
				{
					//create_io_from_existing(button_object);
					window.location = "/io/"+$(button_object).data('unique-display-id');
				}
				else if(data.is_editable_success == "submitted")
				{
					all_ios_set_message_timeout_and_show("The insertion order you are trying to edit is no longer available.  Refresh the page to get the newest list of submitted insertion orders.", 'alert alert-warning', 20000);
				}
				else if(data.is_editable_success == "locked")
				{
					all_ios_set_message_timeout_and_show("The insertion order you are trying to edit is currently being edited by someone else.  Refresh the page to get the newest list of submitted insertion orders.", 'alert alert-warning', 20000);
				}
				else
				{
					all_ios_set_message_timeout_and_show("The insertion order you are trying to edit is currently unavailable.  Refresh the page to get the newest list of submitted insertion orders.", 'alert alert-warning', 20000);
				}
			}
			else
			{
				if(typeof data.errors !== "undefined")
				{
					all_ios_set_message_timeout_and_show(data.errors, 'alert alert-error', 16000);
				}
				else
				{
					all_ios_set_message_timeout_and_show("Error 702500: An unknown error occurred", 'alert alert-error', 16000);
				}
			}
		},
		error: function(jqXHR, textStatus, error){ 
			all_ios_set_message_timeout_and_show("Error 701500: Server Error", 'alert alert-error', 16000);
		}
	});
});

$(document).on('click', '.all_ios_preview_modal_button', function(e){
	e.preventDefault();
	var mpq_id = $(this).attr('data-mpq-id');
	$.ajax({
		type: "POST",
		url: '/insertion_orders/get_insertion_order_summary_html',
		async: true,
		dataType: 'json',		
		data: 
		{
			mpq_id: mpq_id
		},
		success: function(data, textStatus, jqXHR){
			if(data.is_success == true)
			{
				$('#all_ios_preview_modal .modal-body').html(data.html_data);
				$('#all_ios_preview_modal').modal('show');
			}
			else
			{
				if(typeof data.errors !== "undefined")
				{
					all_ios_set_message_timeout_and_show(data.errors, 'alert alert-error', 16000);
				}
				else
				{
					all_ios_set_message_timeout_and_show("Error 692500: An unknown error occurred", 'alert alert-error', 16000);
				}
			}
		},
		error: function(jqXHR, textStatus, error){ 
			all_ios_set_message_timeout_and_show("Error 691500: Server Error", 'alert alert-error', 16000);
		}
	});
});

function all_ios_set_message_timeout_and_show(message, selected_class, timeout)
{
	window.clearTimeout(all_ios_timeout_id);
	$('#all_ios_message_box_content').append(message+"<br>");
	$('#all_ios_message_box').prop('class', selected_class);
	$('#all_ios_message_box').show();
	all_ios_timeout_id = window.setTimeout(function(){
		$('#all_ios_message_box').fadeOut("slow", function(){
			$('#all_ios_message_box_content').html('');
		});
	}, timeout);
}

$("#all_ios_message_box > button").click(function(){
	window.clearTimeout(all_ios_timeout_id);
	$('#all_ios_message_box').fadeOut("fast", function(){
		$('#all_ios_message_box_content').html('');
	});
});

function number_with_commas(number)
{
	return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
