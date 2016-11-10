var all_advertisers_table;

$(document).ready(function(){
	$('#all_advertisers_table').show();
	all_advertisers_table = $('#all_advertisers_table').dataTable({
		"data": format_advertisers_table_data(advertisers_table_data),
		"ordering": true,
		"order": [[0, "asc"]],
		"lengthMenu" : [
			[25, 50, 100],
			[25, 50, 100]
		],
		"columnDefs": [{}],
		"rowCallback": function(row,data) {
		},
		"initComplete": function(settings) {
		},
		"drawCallback": function(settings) {
			$('.all_advertisers_tooltip').tooltip();
		},
		"columns": [
			{
				data: "advertiser",
				title: "ADVERTISER NAME",
				render: {
					_: "formatted",
					sort: "sort",
					filter: "filter"
				},
				type: "string",
				"class": "all_advertisers_elastic_column"
			},
			{
				data: "user",
				title: "USER",
				render: {
					_: "formatted",
					sort: "sort",
					filter: "filter"
				},
				type: "string",
				"class": "all_advertisers_elastic_column"
			},
			{
				data: "partner",
				title: "PARTNER",
				render: {
					_: "formatted",
					sort: "sort",
					filter: "filter"
				},
				type: "string",
				"class": "all_advertisers_elastic_column"
			},
			{
				data: "external_id",
				title: "EXTERNAL ID",
				render: {
					_: "formatted",
					sort: "sort",
					filter: "filter"
				},
				type: "string",
				"class": "all_advertisers_elastic_column"
			},
			{
				data: "actions",
				title: "ACTIONS",
				orderable: false,
				searchable: false,
				"class": "all_advertisers_flag_column"
			},
		]
	});
});

function format_advertisers_table_data(data)
{
	$.each(data, function(key, value){
		var advertiser_name = this.advertiser_name;
		var formatted_advertiser_name = advertiser_name;
		if(advertiser_name == null)
		{
			formatted_advertiser_name = '<span class="muted">[Not Available]</span>';
		}
		this.advertiser = {
			formatted: formatted_advertiser_name,
			sort: advertiser_name,
			filter: advertiser_name
		};

		var user_name = this.user_name;
		var formatted_user_name = user_name;
		if($.trim(user_name) == "")
		{
			user_name = null;
		}
		if(user_name == null)
		{
			formatted_user_name = '<span class="muted">[Not Available]</span>';
		}
		this.user = {
			formatted: formatted_user_name,
			sort: user_name,
			filter: user_name
		};

		var partner_name = this.partner_name;
		var formatted_partner_name = partner_name;
		if(partner_name == null)
		{
			formatted_partner_name = '<span class="muted">[Not Available]</span>';
		}
		this.partner = {
			formatted: formatted_partner_name,
			sort: partner_name,
			filter: partner_name
		};

		var external_id = this.externalId;
		var formatted_external_id = external_id;
		if(external_id == null)
		{
			formatted_external_id = '<span class="muted">[Not Available]</span>';
		}
		this.external_id = {
			formatted: formatted_external_id,
			sort: external_id,
			filter: external_id
		};
		this.actions = '<a id="edit_advertiser_'+this.advertiser_id+'" href="\/advertisers\/edit\/'+this.encoded_advertiser_id+'" class="btn btn-link all_advertisers_tooltip" data-trigger="hover" data-placement="top" data-title="Edit Advertiser"><i class="icon-pencil"></i></a>';
	});
	return data;
}
