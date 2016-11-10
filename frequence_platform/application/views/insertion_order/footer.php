<script src="/libraries/external/jquery-1.10.2/jquery-1.10.2.min.js"></script>
<script src="/bootstrap/assets/js/bootstrap.min.js"></script>

<script type="text/javascript" src="/bootstrap/assets/js/bootstrap-datetimepicker.min.js"></script>
<!-- mpq_component_functions.php -->
<script src="/libraries/external/js/jquery-file-upload-9.5.7/js/vendor/jquery.ui.widget.js"></script>
<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
<script src="/libraries/external/js/jquery-file-upload-9.5.7/js/jquery.iframe-transport.js"></script>
<!-- The basic File Upload plugin -->
<script src="/libraries/external/js/jquery-file-upload-9.5.7/js/jquery.fileupload.js"></script>
<!-- The File Upload processing plugin -->
<script src="/libraries/external/js/jquery-file-upload-9.5.7/js/jquery.fileupload-process.js"></script>
<script type="text/javascript" src="/libraries/external/js/jquery-placeholder/jquery.placeholder.js"></script>
<script src="/libraries/external/json/json2.js"></script>
<!-- WARNING! This is a modified version of the fuelux library, to prevent it from re-loading bootstrap. Please do not replace. -->
<script src="/libraries/external/fuelux/fuelux.js" type="text/javascript"></script>
<script src="/libraries/external/chardin/chardinjs.min.js"></script>

<!-- geo_component_functions.php -->
<script type="text/javascript" src="/libraries/external/json/json2.js"></script>
<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?libraries=places"></script>
<script type="text/javascript" src="//www.google.com/jsapi"></script>
<script src="/libraries/external/select2/select2.js"></script>
<script src="/assets/js/mpq/insertion_order.min.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>

<script type="text/javascript">
		
	// displays the error at the bottom of the page
	function vl_show_error_html(html_string)
	{
		var error_div = $("<div />").html(html_string);
		vl_append_error(error_div);
	}

	var vl_error_background_color_index = 0;
	var vl_error_background_colors = new Array(
		"#ccf",
		"#fcc",
		"#cfc"
	);

	function vl_color_error_background(error)
	{
		error.css('background-color', vl_error_background_colors[vl_error_background_color_index]);
		vl_error_background_color_index++;
		vl_error_background_color_index %= vl_error_background_colors.length;
	}

	function vl_get_error_header(header_extras)
	{
		var d = new Date();
		var date_time = d.toLocaleString("en-US");

		var header_addition = $("<span />");
		if(typeof header_extras === 'string')
		{
			header_addition.
				text(header_extras).
				css({
					paddingLeft: "32px"
				});
		}

		var error_header = $("<div />").
			html("Error logged at: ("+date_time+")").
			append(header_addition).
			css({
				backgroundColor: "#000",
				color: "#fff"
			});
		return error_header;
	}

	function vl_hide_errors()
	{
		$("#vl_errors_section").hide();
	}

	function vl_show_errors()
	{
		$("#vl_errors_section").show();
		window.location.hash = "#";
		window.location.hash = "#vl_errors_section_bottom";
	}

	function vl_append_error(error, header_extras)
	{
		
				vl_color_error_background(error);
				error.prepend(vl_get_error_header(header_extras));
						$("#vl_display_errors_div").append(error);
		vl_show_errors();
	}

	function vl_prepend_error(error, header_extras)
	{
		
				vl_color_error_background(error);
				error.prepend(vl_get_error_header(header_extras));
						$("#vl_display_errors_div").prepend(error);
		vl_show_errors();
	}

	function vl_clear_errors()
	{
		$("#vl_display_errors_div").empty();
		vl_hide_errors();
	}
		</script>

	<script type="text/javascript">

	// evaluates whether the ajax json call succeded 
	function vl_is_ajax_call_success(data)
	{
		var is_success = false;
		if(typeof data === 'object' && 
			data !== null && // not null
			typeof data['is_success'] !== 'undefined' && 
			data.is_success == 1
		)
		{
			is_success = true;
		}

		return is_success;
	}


	//	show error for ajax call failure
	//		use in the ajax.error() function
	function vl_show_jquery_ajax_error(jqXHR, textStatus, error)
	{
		var html_string = '';
		if('responseText' in jqXHR)
		{
			html_string = jqXHR.responseText;
		}
		else if('responseXML' in jqXHR)
		{
			html_string = jqXHR.responseXML;
		}
		else
		{
			html_string = "unhandled error type (#547894)";
		}
		
		var error_div = $("<div />").html(html_string);
		vl_append_error(error_div, "ajax jquery function failure");
	}

	//	show errors when ajax call succeeds but returned data signals an error
	//		use in the ajax.success() function
	function vl_show_ajax_response_data_errors(data, additional_error_string)
	{
		var error_div = $("<div />");
		if(typeof additional_error_string === 'string')
		{
			error_div.append($("<div />").html(additional_error_string));
		}
		else if(typeof additional_error_string === 'undefined')
		{
			// additional_error_string is optional
		}
		else
		{
				error_div.append($("<div />").html("ajax error handler error:  unexpected additional error type: '"+ typeof additional_error_string +"' only strings are handled"));
		}

		if(typeof data === 'object' &&
			data !== null
		)
		{
			var is_success_type = typeof data.is_success;
			if(is_success_type === 'number' ||
				is_success_type === 'boolean')
			{
				// this is the expected situation
			}
			else if(is_success_type === 'undefined')
			{
				error_div.append($("<div />").html("ajax error handler error:  \"is_success\" property undefined, expected 'number' or 'boolean'"));
			}
			else
			{
				error_div.append($("<div />").html("ajax error handler error:  \"is_success\" property unexpected type: '"+is_success_type+"' expected 'number' or 'boolean'"));
			}

			var errors_type = typeof data.errors;
			
			if(errors_type === 'undefined')
			{
				error_div.append($("<div />").html("ajax error handler error:  \"errors\" array undefined, expected an array of strings"));
			}
			else if(errors_type === 'string')
			{
				error_div.append($("<div />").html(data.errors));
			}
			else if(Array.isArray(data.errors))
			{
				data.errors.forEach(function (element, index, array) {
					if(typeof element === 'string')
					{
						error_div.append($("<div />").html(element));
					}
					else
					{
						var element_type = typeof element;
						error_div.append($("<div />").html("ajax error handler error:  unexpected \"errors\" array element ["+index+"] type: '"+element_type+"' must be string"));

					}
				});
			}
			else
			{
				error_div.append($("<div />").html("ajax error handler error:  unexpected errors type: '"+errors_type+"' expected an array of strings"));
			}
		}
		else
		{
			error_div.append($("<div />").html("ajax error handler error:  json response must be an object with at least an \"is_success\" property and an \"errors\" property"));
		}

		
		vl_append_error(error_div, 'ajax response data errors');
	}

</script>


</body>
</html>
