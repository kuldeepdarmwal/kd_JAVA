<?php

function should_show_public_errors()
{
	return ENVIRONMENT == 'production' || ENVIRONMENT == 'staging';
}

function write_vl_platform_error_handlers_html_section()
{
	$should_show_public_errors = should_show_public_errors();

	if($should_show_public_errors == true)
	{
		echo '
			<div id="vl_errors_section" class="container container-body alert alert-error" style="display:none;">
			<h4 style="">Errors Occured</h4>
		';
	}
	else
	{
		echo '
			<div id="vl_errors_section" style="display:none;">
			<h2 style="background-color:red;">Showing errors</h2>
		';
	}
	?>
		<div id="vl_display_errors_div">
		</div>
		<a name="vl_errors_section_bottom"></a>
	</div>
	<?php
}

function write_vl_platform_error_handlers_js()
{
	$should_show_public_errors = should_show_public_errors();

	?>
	<script type="text/javascript">
		<?php
			//	Standardized way of displaying errors
		?>

		<?php
		$is_error_notification_on = true;
		if($is_error_notification_on)
		{
			// WARNNING: if you add new debuggin functions:
			//		add a stub in the PHP else clause.  Otherwise there may be errors in a release build.
		?>
		
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
			<?php
			if($should_show_public_errors == true)
			{
			}
			else
			{
				echo '
					vl_color_error_background(error);
					error.prepend(vl_get_error_header(header_extras));
				';
			}
			?>
			$("#vl_display_errors_div").append(error);
			vl_show_errors();
		}

		function vl_prepend_error(error, header_extras)
		{
			<?php
			if($should_show_public_errors == true)
			{
			}
			else
			{
				echo '
					vl_color_error_background(error);
					error.prepend(vl_get_error_header(header_extras));
				';
			}
			?>
			$("#vl_display_errors_div").prepend(error);
			vl_show_errors();
		}

		function vl_clear_errors()
		{
			$("#vl_display_errors_div").empty();
			vl_hide_errors();
		}
		<?php
		}
		else
		{
		?>
		// debugging functions stubbed out
		function vl_show_error_html(html_string) {}
		function vl_color_error_background(error) {}
		function vl_get_error_header(header_extras) {}
		function vl_hide_errors() {}
		function vl_show_errors() {}
		function vl_append_error(error) {}
		function vl_prepend_error(error) {}
		function vl_clear_errors() {}
		<?php
		}
		?>
	</script>

	<script type="text/javascript">
		<?php
			//	Standardized way of handling ajax responses
			//	
			//	ajax responses should be in the json format
			//		with the top level being an object with two properties
			//		is_success : which is 1 on success or 0 on failure
			//		errors : an array of strings detailing why the ajax call was a failure
			//
			//	if the response is not an object or doesn't have the is_success property it is considered a failed call
			//
			//	(5/16/2013)
		?>

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

		<?php
		if($is_error_notification_on)
		{
			//	WARNNING: if you add new debuggin functions:
			//		add a stub in the PHP else clause.  Otherwise there may be errors in a release build.
		?>

			//	show error for ajax call failure
			//		use in the ajax.error() function
			function vl_show_jquery_ajax_error(jqXHR, textStatus, error)
			{
				var html_string = '';
				<?php
				if($should_show_public_errors == true)
				{
				?>
					// in production don't show PHP and MYSQL errors
					html_string = 'Server Error';
				<?php
				}
				else
				{
				?>
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
				<?php
				}
				?>

				var error_div = $("<div />").html(html_string);
				vl_append_error(error_div, "ajax jquery function failure");
			}

			//	show errors when ajax call succeeds but returned data signals an error
			//		use in the ajax.success() function
			function vl_show_ajax_response_data_errors(data, additional_error_string)
			{
				var error_div = $("<div />");
				<?php
				if($should_show_public_errors == true)
				{
				?>
					// in production don't display errors about invalid error data

					var is_invalid_error = false;

					if(typeof additional_error_string === 'string')
					{
						error_div.append($("<div />").html(additional_error_string));
					}

					if(typeof data === 'object' &&
						data !== null
					)
					{
						var errors_type = typeof data.errors;
						
						if(errors_type === 'string')
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
									is_invalid_error = true;
								}
							});
						}
						else
						{
							is_invalid_error = true;
						}
					}
					else
					{
						is_invalid_error = true;
					}

					if(is_invalid_error == true)
					{
						var html_string = 'Error Occured on Server';
						error_div.append($("<div />").html(html_string));
					}
				<?php 
				}
				else
				{
				?>
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

				<?php 
				}
				?>

				vl_append_error(error_div, 'ajax response data errors');
			}
		
		<?php
		}
		else
		{
		?>
			// ajax debugging functions stubbed out
			function vl_show_jquery_ajax_error(jqXHR, textStatus, error) {} 
			function vl_show_ajax_response_data_errors(data, additional_error_string) {}
		<?php
		}
		?>
	</script>
	<?php
}

?>
