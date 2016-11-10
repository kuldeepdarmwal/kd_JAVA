var launch_io_timeout_id;

$(document).ready(function(){

	$(".launch_io_campaign").on('click', function(e)
	{
		if(selected_campaign_id != $(this).attr("data-campaign"))
		{		
			var selected = $('.selected_campaign');

			if(selected)
			{
				$(selected).removeClass('selected_campaign');
			}

			$(this).addClass('selected_campaign');
			selected_campaign_id = $(this).attr("data-campaign");
			load_campaign_setup_page(selected_campaign_id);
		}
	});

	$(".trash_campaign_button").on('click', function(e)
	{
		e.stopPropagation();
		trash_campaign_row = $(this).parent();
		var campaign_name = $(".launch_io_campaign_name", trash_campaign_row).html();
		$("#confirm_trash_campaign_modal_body").html("Are you sure you want to trash <strong>"+campaign_name+"</strong>?");
		$("#confirm_trash_campaign_modal").modal("show");
	});
	if(!has_notes)
	{
		$(".launch_io_content_container").css('bottom', '3px');
	}
});

function load_campaign_setup_page(campaign_id)
{
	$("#campaign_setup_iframe").attr("src", "/campaign_setup/"+campaign_id+"/1");
	$("#no_campaign_selected_div").hide();
	$("#campaign_setup_iframe").show();
	if(has_notes)
	{	
		$("#notes_section").show();
	}

}

function trash_campaign(campaign_element)
{
	var trash_campaign_id = $(campaign_element).attr('data-campaign');

	$.ajax({
	type: "POST",
	url: '/launch_io/trash_campaign',
	async: true,
	dataType: 'json',		
	data: 
	{
		c_id: trash_campaign_id
	},
	success: function(data, textStatus, jqXHR){
		if(data.success == true)
		{
			if(trash_campaign_id == selected_campaign_id)
			{
				$("#campaign_setup_iframe").attr("src", "/campaign_setup");
				$("#no_campaign_selected_div").show();
				$("#campaign_setup_iframe").hide();
				$("#notes_section").hide();
			}
			num_campaigns -= 1;
			$(campaign_element).remove();
		}
		else
		{
			launch_io_set_message_timeout_and_show("Error 457211: Failed to trash campaign", 'alert alert-error', 16000);
		}
	},
	error: function(jqXHR, textStatus, error){ 
		launch_io_set_message_timeout_and_show("Error 457210: Unknown error occured while trashing campaign", 'alert alert-error', 16000);
	}
	});	
}

function launch_io_set_message_timeout_and_show(message, selected_class, timeout)
{
	window.clearTimeout(launch_io_timeout_id);
	$('#launch_io_message_box_content').append(message+"<br>");
	$('#launch_io_message_box').prop('class', selected_class);
	$('#launch_io_message_box').show();
	launch_io_timeout_id = window.setTimeout(function(){
		$('#launch_io_message_box').fadeOut("slow", function(){
			$('#launch_io_message_box_content').html('');
		});
	}, timeout);
}

$("#launch_io_message_box > button").click(function(){
	window.clearTimeout(launch_io_timeout_id);
	$('#launch_io_message_box').fadeOut("fast", function(){
		$('#launch_io_message_box_content').html('');
	});
});
