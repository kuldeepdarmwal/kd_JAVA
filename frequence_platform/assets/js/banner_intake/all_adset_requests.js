var adset_requests_table;

$(document).ready(function(){
        var timezone_offset = new Date().getTimezoneOffset()/60;
	$('#adset_requests_table').show();
	adset_requests_table = $('#adset_requests_table').dataTable({
                "ajax": "/banner_intake/ajax_data_for_datatable/"+timezone_offset,
                "language": {
                    "loadingRecords": "<img id='c_main_loading_img' src='/images/loadingImage.gif'/>"
                },
		"ordering": true,
		"order": [[0, "desc"]],
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
		},
		"columns": [
			
			{
				data: "email_errors",
				title: "<span class='mailgun-header'>EMAIL STATUS</span>",
				"class": "adset_request_auto_column",
				"data-toggle": "tooltip",
				visible : eval("(function(){if (($.inArray('email_errors',invisible_columns)) != -1){ return false;}else{return true;}})();"),
				"render": function ( data, type, row ) {
				if (data !== null )
				{
					data = format_creative_table_data(data);
				}
				return data;
				}
			},
			{
				data: "id",
				title: "REQ#",
                                visible : eval("(function(){if (($.inArray('id',invisible_columns)) != -1){ return false;}else{return true;}})();"),
				"class": "adset_request_auto_column"
			},
			{
				data: "creative_name",
				title: "ADSET NAME",
                                "render": function ( data, type, row ) {
                                        if (row.latest_version !== 'N/A')
                                        {
                                                if (user_role !== 'sales')
                                                {
                                                        data = '<a href="/creative_uploader/'+row.latest_version+'" target="_blank" title="Version - '+row.version_name+'">'+data+'</a>';
                                                }
                                        }
                                        return data;
                                },
				"class": "adset_request_auto_column"
			},
			{
				data: "partner",
				title: "PARTNER",
				"class": "adset_request_auto_column"
			},
			{
				data: "creative_request_owner_id",
				title: "OWNER",
				"class": "adset_request_auto_column"
			},
			{
				data: "advertiser_name",
				title: "ADVERTISER",
				"class": "adset_request_auto_column"
			},
                        {
				data: "product",
				title: "PRODUCT",
				"class": "adset_request_auto_column"
			},
			{
				data: "request_type",
				title: "TYPE",
				"class": "adset_request_auto_column"
			},
                        {
				data: "landing_page",
				title: "LANDING PAGE",
				orderable: false,
				searchable: false,
                                "render": function ( data, type, row ) {
                                        if (data !== 'N/A')
                                        {
						var short_url = data.length>70 ? '...'+data.slice(-70) : data;
                                                if (data.indexOf('http') == -1)
                                                {
                                                        data = '<a href="http://'+data+'" data-toggle="tooltip" data-placement="top" title="'+data+'" target="_blank">'+short_url+'</a>';
                                                }
                                                else
                                                {
                                                        data = '<a href="'+data+'" data-toggle="tooltip" data-placement="top" title="'+data+'" target="_blank">'+short_url+'</a>';
                                                }
                                        }
                                        return data;
                                },
				"class": "adset_request_auto_column"
			},
			{
				data: "requested",
				title: "REQUESTED",
				orderable: false,
				searchable: false,
                                "render": function ( data, type, row ) {
                                        if (data !== '' && data !== 'N/A' )
                                        {
                                                data = '<a href="javascript:modal_open('+row.id+');" id="'+row.id+'" class="request_id">'+formated_date(data)+'</a>';
                                        }
                                        return data;
                                },
				"class": "adset_request_auto_column"
			},
                        {
				data: "created",
				title: "CREATED",
				orderable: false,
				searchable: false,
                                visible : eval("(function(){if ($.inArray('created',invisible_columns) != -1){ return false;}else{return true;}})();"),
                                "render": function ( data, type, row ) {
                                        if (data !== '' && data !== 'N/A' )
                                        {
                                                data = formated_date(data);
                                        }
                                        return data;
                                },
				"class": "adset_request_auto_column"
			},
			{
				data: "updated",
				title: "UPDATED",
				orderable: false,
				searchable: false,
                                visible : eval("(function(){if ($.inArray('updated',invisible_columns) != -1){ return false;}else{return true;}})();"),
                                "render": function ( data, type, row ) {
                                        if (data !== '' && data !== 'N/A' )
                                        {
                                                data = formated_date(data);
                                        }
                                        return data;
                                },
				"class": "adset_request_auto_column"
			},
                        {
				data: "updater",
				title: "UPDATER",
				orderable: false,
				searchable: false,
                                visible : eval("(function(){if ($.inArray('updater',invisible_columns) != -1){ return false;}else{return true;}})();"),
				"class": "adset_request_auto_column"
			},
			{
				data: "latest_version",
				title: "VERSIONS",
				orderable: false,
				searchable: false,
                                "render": function ( data, type, row ) {
                                    if (data !== 'N/A')
                                    {
										if(row.versions.length > 0)
										{
											var dropdown_string = '<div class="dropdown creative_versions_dropdown"><a class="btn btn-link dropdown-toggle" data-toggle="dropdown" href="#">'+row.version_name+' <span class="caret creative_request_large_caret"></span></a> <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu">';
											
											$.each(row.versions, function(key, value){
												var version_class = "creative_version_bottom_border";
												if(key == row.versions.length -1)
												{
													version_class = "";
												}
												dropdown_string += '<li class="'+version_class+'"><div>'+value.version_name+' <a href="'+value.version_url+'" target="_blank">'+value.version_identifier+'</a></div></li>';
											});
											dropdown_string += '</ul></div>';
											data = dropdown_string;
										}
										else
										{
											data = '-';
										}
                                        
										
                                    }
									else
									{
										data = '-';
									}
                                        return data;
                                },
				"class": "adset_request_latest_version"
			},
			{
				data: "internally_approved_updated_user",
				title: "INTERNAL APPROVED BY",
				visible : eval("(function(){if ($.inArray('updater',invisible_columns) != -1){ return false;}else{return true;}})();"),
                                "class": "adset_request_auto_column"
			},
			{
				data: "internally_approved_updated_timestamp",
				title: "INTERNAL APPROVED TIME",
				orderable: false,
				searchable: false,
                                visible : eval("(function(){if ($.inArray('internally_approved_updated_timestamp',invisible_columns) != -1){ return false;}else{return true;}})();"),
				"render": function ( data, type, row ) {
                                        if (data !== null )
                                        {
                                                data = formated_date(data);
                                        }
					return data;
				},	
                                "class": "adset_request_auto_column"
			},
			{
				data: "demo",
				searchable: true,
				visible : false
			},
			{
			data: function(data)
			{
				if (data.versions.length > 0)
				{
					var search_string = "";
					$.each(data.versions, function(i, val){
						search_string += val.version_identifier + " ";
					});
					return search_string;
				}
				else
				{
					return data.base_64_encoded_id;
				}
			},
			searchable: true,
			visible: false,
			defaultContent: ''
			}
		]
	});
	function format_creative_table_data(data)
	{
		var not_done_icon = '<i style="color: #faa732;" class="material-icons" title="Mailgun email error">&#xE002;</i>';
		var mailgun_error_html = not_done_icon;
		var mailgun_hidden  = '<span class="hide">mailgun error</span>';
		if (data == 1)
		{
			mailgun_error_html = '';
		}
		else
		{
			
			mailgun_error_html = not_done_icon + mailgun_hidden;
		}
	
		return mailgun_error_html;
	}

	$('.DTS_Loading').hide();

	$( 'span.mailgun-header' ).hover( function() {
		$(this).tooltip({ 'placement': 'top' , 'title' : 'Status of Zendesk Confirmation Email'}); 
	});
	
	$("#adset_request_link_button").click(function(){
		var result = true;
		$.ajax({
			type: "POST",
			url: '/banner_intake/unset_preload_io',
			async: false,
			data:{},
			dataType: 'json',
			success: function(data, textStatus, jqXHR){
				return true;

			},
			error: function(jqXHR, textStatus, error){
				result = false;
			}
		});
		return result;
	});
        
    if(open_modal_id != null)
    {
    	modal_open(open_modal_id);
    }	
});

//adding checkbox filter
$(document).ready(function() {
	if(user_role == 'admin')
	{
		$.fn.dataTableExt.afnFiltering.push(function(oSettings, aData, iDataIndex) {
			var checked = $('#demo_checkbox').is(':checked');
			if (checked && aData[16] == 'demo' || aData[16] == 'nodemo') {
				return true;
			}
			if (!checked && aData[16]  == 'nodemo') {
				return true;
			}
			return false;
		});
		$('#demo_checkbox').on("click", function(e) {
			console.log('click');
			adset_requests_table.fnDraw();
		});
	}
});

function formated_date(date_to_format)
{
        if (date_to_format !== '' && date_to_format !== 'N/A' )
        {
                var testDateUtc = moment(date_to_format,"YYYY-MM-DD hh:mm:ss A");
                var time_from_date = moment(testDateUtc).get('hour') +":"+ moment(testDateUtc).get('minute')+":"+moment(testDateUtc).get('second');
                date_to_format = moment(testDateUtc).format('YYYY-MM-DD')+"<br/>"+time_from_date;
        }
        return date_to_format;
}

function modal_open(form_id){
        $.ajax({
                url: '/banner_intake/ajax_review_single/'+form_id,
                type: 'POST',
                dataType: 'html',
                success: function(data) {
                        $("#adset_requests_preview_modal .modal-body").html(data);
                        $("#adset_requests_preview_modal").modal("show");
                }
        });

}