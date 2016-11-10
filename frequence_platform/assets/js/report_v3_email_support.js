$(document).on('ready', function(){

	$("div.support_tab").on("click", function(){

		if($("div.support_form_container").hasClass("hidden"))
		{
			$("div.support_backdrop").css({display: 'block', opacity: 0}).animate({opacity: 0.5});
			$("div.support_form_container").toggleClass("hidden");
			$("div.support_container").css({bottom:'-' + $("div.support_form_container").height() + 'px'}).animate({bottom: '0px'});
		}
		else
		{
			$("div.support_backdrop").fadeOut();
			$("div.support_container").animate({bottom: '-' + $("div.support_form_container").height() + 'px'}, function(){
				$(this).css({bottom: '0px'});
				$("div.support_form_container").toggleClass("hidden");
			});
		}
	});

	$("div.support_form_container form button.btn").on("click", function(){
		send_support_email();
	});

	function send_support_email()
	{
		deactivate_form();

		var textarea_message = $("div.support_form_container form textarea").val();
		if(textarea_message.trim())
		{
			$.ajax({
				async: true,
				type: "POST",
				data: { 
					message: textarea_message
				},
				url: "/report_v2/ajax_send_support_email",
				success: function(response_data, textStatus, jqXHR) {
					if(vl_is_ajax_call_success(response_data))
					{
						$(".message_container").html(get_message_html('<strong>Success!</strong> Your message has been sent successfully.', 'alert alert-success'));
						$("div.support_form_container form textarea").val("");

						setTimeout(function(){
							$(".message_container .alert").alert('close');
							$("div.support_tab").click();
						}, 2000);
					}
					else
					{
						handle_ajax_controlled_error(response_data, "Failed to send support email ");
					}

					reactivate_form();
				},
				error: function(jqXHR, textStatus, errorThrown) {
					vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
				},
				dataType: "json"
			});
		}
		else
		{
			$(".message_container").html(get_message_html('<strong>Warning!</strong> The message field can\'t be left blank.', 'alert'));

			reactivate_form();
		}
	}

	function deactivate_form()
	{
		$("div.support_form_container form button.btn").off("click");
		$("div.support_form_container form button.btn").prop('disabled', true);
		$("div.support_form_container form button.btn").text('Sending');
	}

	function reactivate_form()
	{
		$("div.support_form_container form button.btn").on("click", function(){
			send_support_email();
		});
		$("div.support_form_container form button.btn").prop('disabled', false);
		$("div.support_form_container form button.btn").text('Submit');
	}

	function get_message_html(message, type)
	{
		var html = '<div class="' + type + '">' + 
			'<button type="button" class="close" data-dismiss="alert">&times;</button>' + 
			message + 
		'</div>';

		return html;
	}

	if(typeof String.prototype.trim !== 'function')
	{
		String.prototype.trim = function(){
			return this.replace(/^\s+|\s+$/g, '');
		}
	}

});