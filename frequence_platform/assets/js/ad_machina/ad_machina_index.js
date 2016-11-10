/*global window, document, $ */
$(function() {
	/*
	 * @param mixed value
	 * @returns boolean
	 */
	function get_boolean(value)
	{
		if(!isNaN(value))
		{
			return Number(value) != 0;
		}
		else if(value == 'yes' || value == 'true')
		{
			return true;
		}
		return false;
	}
	/*
	 * @param string data
	 * @returns string HTML-encoded versions of special characters (e.g. '>' => '&gt;')
	 */
	function html_escape(data)
	{
		return $('<div>').text(data).html();
	}
	/*
	 * @param string data
	 * @returns string same as html_escape, while also HTML-encoding double-quotes
	 */
	function attr_escape(data)
	{
		return html_escape(data).replace(/"/g, '&quot;');
	}

	/*
	 * @param array source_array with object elements
	 * @param string key to extract into result array
	 * @param array map_functions optional array of functions through which to pass each value
	 * @return array values from objects in source_array
	 */
	function list_field(source_array, key, map_functions)
	{
		map_functions = map_functions || [];
		map_functions.unshift(function(object) {
			return object[key];
		});
		return map_multiple(source_array, map_functions);
	}

	/*
	 * @param array array
	 * @param array map_functions functions to be mapped to the array, in order
	 * @return array result after each element has passed through map_functions
	 */
	function map_multiple(array, map_functions)
	{
		var result_array;

		for(var i = 0; i < map_functions.length; i++)
		{
			result_array = (result_array || array).map(map_functions[i]);
		}

		return result_array;
	}

	/*
	 * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/Reduce
	 * @param mixed previousValue
	 * @param mixed currentValue
	 * @param mixed currentIndex
	 * @param mixed array
	 * @returns mixed largest value
	 */
	function reduce_to_largest(previousValue, currentValue, currentIndex, array)
	{
		if(Number(currentValue) > Number(previousValue))
		{
			return Number(currentValue);
		}
		return Number(previousValue);
	}

	/*
	 * @param string value
	 * @return string wrapped with div.sub_row element
	 */
	function make_sub_row(value)
	{
		return '<div class="sub_row">' + value + '</div>';
	};

	/*
	 * @param array data
	 * @return array data prepared for dataTable
	 */
	function format_spec_ad_data(data)
	{
		return data.map(function(row_data) {
			var has_spec_ads = row_data.spec_ads.length > 0;
			var recommend_sample_ad = (!has_spec_ads && get_boolean(row_data.needs_digital));

			var creation_link_classes = ['creation_link', 'ui_icon', 'icon-plus-sign', 'vab-tooltip'];
			if(recommend_sample_ad)
			{
				creation_link_classes.push('recommended');
			}
			var creation_link_attributes = [
				'href="/vab/videos/' + row_data.frq_third_party_account_id + '"',
				'target="_blank"',
				'title="Create a new Video Ad Preview"',
				'data-trigger="hover"',
				'data-placement="right"'
			];
			creation_link_attributes.push('class="' + creation_link_classes.join(' ') + '"');
			var creation_link = '<a ' + creation_link_attributes.join(' ') + '></a>';

			/*
			 * @returns string 'yes' or 'no'
			 */
			function make_digital_column()
			{
				var boolean_value = get_boolean(row_data.needs_digital);
				var text = boolean_value ? 'yes' : 'no';
				var classes = ['needs_digital'];
				if(boolean_value)
				{
					classes.push('recommended');
				}

				return '<span class="' + classes.join(' ') + '">' + text + '</span>';
			};

			/*
			 * @returns string advertiser name with emails
			 */
			function make_advertiser_emails_button()
			{
				var html = '';
				var button_classes = ['emails-button', 'vab-tooltip'];
				var button_attributes = [
					'data-trigger="hover"',
					'data-placement="top"'
				];

				if(row_data.advertiser_emails)
				{
					var email_count = row_data.advertiser_emails.split(',').length;
					button_attributes.push('data-emails="' + attr_escape(row_data.advertiser_emails) + '"');
					button_attributes.push('data-advertiser-name="' + attr_escape(row_data.advertiser_name) + '"');
					button_attributes.push('title="show email' + (email_count == 1 ? '' : 's') + '"');
				}
				else
				{
					button_classes.push('disabled');
					button_attributes.push('title="no emails to list"');
				}

				button_attributes.push('class="' + button_classes.join(' ') + '"');

				html = '<span ' + button_attributes.join(' ') + '><i class="icon-group"></i></span>';

				return html;
			};

			/*
			 * @param object spec_ad_data one "row" in the "spec_ads" array of the data
			 * @returns string HTML with 3 links to copy the preview URL, edit the sample ad, or preview
			 */
			function make_preview_links(spec_ad_data)
			{
				var html = '<span data-trigger="hover" data-placement="top" title="copy URL" data-title="' + attr_escape(spec_ad_data.title) + '" class="demo_action_copy ui_icon icon-copy vab-tooltip"></span>';

				if(window.has_edit_permission)
				{
					html += ' <a data-trigger="hover" data-placement="top" title="edit ad" target="_blank" href="/vab/builder/' + spec_ad_data.video_id + '/' + spec_ad_data.preview_key_timestamp + '" class="demo_action_edit ui_icon icon-pencil vab-tooltip"></a>';
				}

				html += ' <a data-trigger="hover" data-placement="top" title="view" target="_blank" href="' + spec_ad_data.demo_page_url + '" class="demo_action_view ui_icon icon-share vab-tooltip"></a>';

				return html;
			};

			var formatted_row_data = {
				creation_link                             : creation_link,
				recommend_sample_ad                       : recommend_sample_ad,
				advertiser_emails                         : row_data.advertiser_emails.split(',').join(', '),
				advertiser_emails_link                    : make_advertiser_emails_button(),
				has_advertiser_emails                     : row_data.advertiser_emails.length > 0,
				advertiser_name                           : html_escape(row_data.advertiser_name),
				sales_email                               : html_escape(row_data.sales_email),
				partner_name                              : html_escape(row_data.partner_name),
				client_id                                 : html_escape(row_data.client_id),
				traffic_system                            : html_escape(row_data.traffic_system),
				needs_digital                             : make_digital_column(),
				needs_digital_sort                        : Number(row_data.needs_digital),
				spec_ad_titles                            : list_field(row_data.spec_ads, 'title', [html_escape, make_sub_row]).join(' \n'),
				spec_ad_created_date                      : list_field(row_data.spec_ads, 'created_date', [html_escape, make_sub_row]).join(' \n'),
				spec_ad_latest_created_timestamp          : list_field(row_data.spec_ads, 'created_timestamp').reduce(reduce_to_largest, ''),
				spec_ad_author_emails                     : list_field(row_data.spec_ads, 'author_email', [html_escape, make_sub_row]).join(' \n'),
				spec_ad_preview_links                     : map_multiple(row_data.spec_ads, [make_preview_links, make_sub_row]).join(' \n'),
				spec_ad_preview_urls                      : list_field(row_data.spec_ads, 'demo_page_url').join(' \n'),
				spec_ad_view_count                        : list_field(row_data.spec_ads, 'view_count', [make_sub_row]).join(' \n'),
				spec_ad_most_view_count                   : list_field(row_data.spec_ads, 'view_count').reduce(reduce_to_largest, 0)
			};

			return formatted_row_data;
		});
	}

	/*
	 * @param event mouse event
	 */
	function handle_copy_demo_url_click(event)
	{
		var copy_icon = $(event.target);
		var url = copy_icon.siblings('.demo_action_view').attr('href');
		var preview_page_name = 'Preview Page URL for: <em>' + copy_icon.data('title') + '</em>'; // use data-title attribute

		showTextToCopy(url, preview_page_name, false);
	}

	var spec_ad_table = $('#spec_ad_table');
	var spec_ad_table_data;
	var spec_ad_table_columns;

	/*
	 * @param event mouse event
	 */
	function handle_emails_button_click(event)
	{
		var emails_button = $(event.currentTarget);
		var emails = emails_button.data('emails').split(',');
		var emails_markup = emails.join(', ');
		var advertiser_name = emails_button.data('advertiserName'); // jQuery camelcase-ifies dash-separated attribute names
		var title = 'Advertiser and Client emails for <em>' + html_escape(advertiser_name) + '</em>';
		showTextToCopy(emails_markup, title);
	}

	/*
	 * encapsulate building the table
	 */
	function build_datatable()
	{

		spec_ad_table.on('click', '.demo_action_copy', handle_copy_demo_url_click);
		spec_ad_table.on('click', '.emails-button[data-emails]', handle_emails_button_click);

		spec_ad_table_data = format_spec_ad_data(spec_ad_data);

		spec_ad_table_columns = [];

		/*
		 * @param string message
		 * @param string classes
		 */
		function generate_popover_attributes(message, classes, placement)
		{
			classes = classes || '';
			placement = placement || 'top';
			classes += ' vab-popover';
			return 'class="' + classes + '" data-content="' + attr_escape(message) + '" data-trigger="hover" data-placement="' + placement + '" data-toggle="popover" data-delay="200"';
		}

		if(has_edit_permission)
		{
			spec_ad_table_columns = spec_ad_table_columns.concat([
				{
					data: 'creation_link',
					title: '<div ' + generate_popover_attributes("Green: Recommended to create a Video Ad Preview", null, 'right') + '>&nbsp;</div>',
					searchable: false,
					className: 'center_justify',
					orderData: [1],
					export: false
				},
				{
					data: 'recommend_sample_ad',
					searchable: false,
					visible: false
				}
			]);
		}

		spec_ad_table_columns = spec_ad_table_columns.concat([
			{
				data: 'advertiser_name',
				title: 'Advertiser'
			},
			{
				data: 'advertiser_emails',
				title: 'Client Emails',
				visible: false,
				export: true
			},
			{
				data: 'advertiser_emails_link',
				title: '<div ' + generate_popover_attributes("Advertiser and Sales people email addresses") + '>Client Emails</div>',
				className: 'center_justify',
				orderData: [spec_ad_table_columns.length + 3],
				export: false
			},
			{
				data: 'has_advertiser_emails',
				visible: false
			},
			{
				data: 'sales_email',
				title: 'Sales Email'
			},
			{
				data: 'partner_name',
				title: 'Partner'
			},
			{
				data: 'client_id',
				title: 'Client ID'
			},
			{
				data: 'traffic_system',
				title: 'Traffic System'
			},
			{
				data: 'needs_digital',
				title: '<div ' + generate_popover_attributes("\"YES\" for Advertisers without digital campaigns run in the last 6 months.") + '>Needs Digital</div>',
				className: 'center_justify',
				searchable: false,
				orderData: [spec_ad_table_columns.length + 9]
			},
			{
				data: 'needs_digital_sort',
				visible: false,
				searchable: false
			},
			{
				data: 'spec_ad_titles',
				title: 'Video Ad Name'
			},
			{
				data: 'spec_ad_created_date',
				title: 'Creation Date',
				className: 'center_justify',
				searchable: false,
				orderData: [spec_ad_table_columns.length + 12]
			},
			{
				data: 'spec_ad_latest_created_timestamp',
				visible: false,
				searchable: false
			},
			{
				data: 'spec_ad_author_emails',
				title: 'Author Email'
			},
			{
				data: 'spec_ad_preview_links',
				className: 'url_actions center_justify',
				title: 'Preview page URL',
				searchable: false,
				export: false
			},
			{
				data: 'spec_ad_preview_urls',
				title: 'Preview page URL',
				searchable: false,
				export: true,
				visible: false
			},
			{
				data: 'spec_ad_view_count',
				className: 'right_justify',
				title: 'View Count',
				searchable: false,
				orderData: [spec_ad_table_columns.length + 17]
			},
			{
				data: 'spec_ad_most_view_count',
				visible: false,
				searchable: false
			}
		]);

		var inital_table_sorting = [];

		if(has_edit_permission)
		{
			inital_table_sorting = [
				[0, 'desc'], // creation_link
				[2, 'asc'] // advertiser_name
			];
		}
		else
		{
			inital_table_sorting = [
				[11, 'desc'] // spec_ad_created_date
			];
		}

		spec_ad_table.dataTable({
			data: spec_ad_table_data,
			pageLength: 25,
			order: inital_table_sorting,
			columns: spec_ad_table_columns,
			createdRow: function(row) {
				$(row).find('.vab-tooltip').tooltip();
			}
		});

		spec_ad_table.find('.vab-popover').popover();
	}

	if(has_advertisers)
	{
		build_datatable();
		$('#csv_download_button').click(function() {
			create_excel_indexed(spec_ad_table_columns, spec_ad_table_data, 'Video Ad Manager');
		});
	}
});
