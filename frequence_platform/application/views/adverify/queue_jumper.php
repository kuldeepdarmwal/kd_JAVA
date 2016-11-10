<div class="container">
<h3>AdVerify Queue Jumper <small>paste a list, cut in line.</small></h3>

<div style="position:relative;">
		<textarea id="cid_queue_list" placeholder="Paste comma-separated campaign IDs here." style="width: 300px; height:100px;" ></textarea>
		&nbsp;
		<span class='label label-success' id="upload-file-info"></span>
	</div>
	<hr>
<div class="row-fluid">
<span id="err_span"></span>
<br><br>
<button id="add_queue_button" class="btn btn-info">Add to Queue</button>
<div>


</div>
<script src="/bootstrap/assets/js/bootstrap-dropdown.js"></script>
<script>
	function queue_cid_list()
	{
			clear_errors();
			var queue_box_string = $('#cid_queue_list').val();
			disable_button();
			if(queue_box_string == '')
			{
				set_error_status("Verify the input has something in there.", "important");
				reset_button();
				return;
			}
			
			
			///do ajax call to load line item here
			$.ajax({
				type: "POST",
				url: '/adverify/add_campaign_list_to_queue/',
				async: true,
				data: {raw_queue_input: queue_box_string},
				dataType: 'json',
				error: function(xhr, textStatus, error)
				{
					set_error_status("Failed to update queue", "important")
					reset_button();
				},
				success: function(data, textStatus, xhr)
				{ 
					reset_button();
					if(data.success)
					{
						set_error_status("Success! Campaigns added to queue.", "success")
					}
					else
					{
						set_error_status(data.err_msg, "important");
					}
				}
			});
			
	}
function clear_errors()
{
	$('#err_span').attr("class", "");
	$('#err_span').html("");
}

function set_error_status(msg, bs_color)
{
	$('#err_span').attr("class", "label label-"+bs_color);
    $('#err_span').html(msg);
}

function disable_button()
{
	$("#add_queue_button").prop("disabled", true)
	$("#add_queue_button").html("Adding...");
	$("#add_queue_button").unbind("click");
}

function reset_button()
{
	$("#add_queue_button").prop("disabled", false)
	$("#add_queue_button").html("Add to Queue");
	$("#add_queue_button").click(queue_cid_list);
}

$(document).ready(reset_button());
</script>
</body>
</html>