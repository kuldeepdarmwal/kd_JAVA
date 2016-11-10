/*
 * Adapted from /js/campaign_health/notes.js:create_excel_report
 *
 * This version ignores columns with `visible: false`, and maps the data and
 * columns in the same way dataTable does, referencing data by each column's
 * data attribute, instead of assuming data are named in the format
 * `column_<index>`.
 *
 * @param array col_data same data passed to dataTable `columns` option
 * @param array table_data same data passed to dataTable `data` option
 * @param string table_display name to give the downloaded file
 */
function create_excel_indexed(col_data, table_data, table_display)
{

	/*
	 * Adapted from /js/campaign_health/notes.js:format_data
	 *
	 * This version forces the input to a string before calling `replace` on it,
	 * and trims outer whitespace off the output.
	 *
	 * @param string input
	 */
	var format_inline_column_data = function(input)
	{
		input = String(input);

		if (input == undefined)
		{
			return ""; 
		}

		var output = input.replace(/"/g, '""'); // escape quotes
		output = output.replace(/\<[^\>]+\>/g, ""); // HTML
		output = output.replace(/&nbsp;/g, " "); // non-breaking space character => space
		output = output.trim();

		if(output == "")
		{
			return '';
		}
		return '"' + output + '"';
	}
	var csv = "";
	var exported_column_index = 0;
	for(var i=0; i < col_data.length; i++)
	{
		if(col_data[i]['export'] !== true && (col_data[i]['export'] === false || col_data[i]['visible'] === false))
		{
			continue;
		}

		if (exported_column_index > 0)
		{
			csv += ":;:";
		}

		csv += format_inline_column_data(col_data[i].title);

		exported_column_index++;
	}

	for(var i=0; i < table_data.length; i++)
	{
		csv += ":^:";
		exported_column_index = 0;
		for(var j=0; j < col_data.length; j++)
		{
			if(col_data[j]['export'] !== true && (col_data[j]['export'] === false || col_data[j]['visible'] === false))
			{
				continue;
			}

			if (exported_column_index > 0)
			{
				csv += ":;:";
			}

			var col_name = col_data[j].data;
			var input_data = table_data[i][col_name]
			if(typeof col_data[j] !== 'undefined' && typeof col_data[j].render !== 'undefined' && typeof col_data[j].render._ !== 'undefined')
			{ 
				input_data = table_data[i][col_name][col_data[j].render._];
			}

			csv += format_inline_column_data(input_data);

			exported_column_index++;
		}
	}

	var file_name = table_display;
	if(file_name && csv)
	{
		$("#file_name").val(file_name);
		$("#csv_data").val(csv);
		$("#download_inline_csv_form").submit();
	}
}
