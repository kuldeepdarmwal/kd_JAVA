<div class="container">

	<h3>Spectrum Syscode Uploader <small>choose file -> upload -> done</small></h3>

	<form action="/syscode_upload" id="syscode_upload_form" method="post" enctype="multipart/form-data">
		<div style="position:relative;">
			<a class='btn btn-mini' href='javascript:;'>
				Choose File...
				<input type="file" style='position:absolute;z-index:2;top:0;left:0;filter: alpha(opacity=0);-ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=0)";opacity:0;background-color:transparent;color:transparent;' name="userfile" size="40"  onchange='var pieces=$(this).val().split("\\");$("#upload-file-info").html(pieces[pieces.length-1]);$("#err_div").html("");'>
			</a>
			&nbsp;
			<span class='label label-success' id="upload-file-info"></span>
		</div>
		<hr>
		<div class="row-fluid">
			<button type="submit" id="upload_button" class="btn btn-info" name="upload">Upload</button>
		<div>
	</form>

	<div>
		<p>
			<small>
				*CSV/TSV column headers aren't required, but if they are omitted, then the file must have <?php echo count($file_fields); ?> columns with each column appearing in this order:<br>
				<?php echo implode(', ', $file_fields); ?><br>
				If the header is included in the file, the fields can be in any order.
			</small>
		</p>
	</div>

	<div id="err_div"></div>

</div>
<script type="text/javascript">

$(document).on("ready", function(){
	var fileSelect = $("form input[type='file']");
	var uploadButton = $("form#syscode_upload_form button#upload_button");

	$(uploadButton).on("click", function(event)
	{
		event.preventDefault();
		if(fileSelect.val() !== "")
		{
			// Update button text.
			uploadButton.text('Uploading...');
			uploadButton.prop("disabled", true);

			// The rest of the code will go here...
			var file_data = fileSelect.prop("files")[0];
			var form_data = new FormData();
			form_data.append("userfile", file_data);
			form_data.append("should_refresh_syscodes", $("#refresh_syscodes").is(":checked"));

			$.ajax({
				url: '/syscode_upload/ajax_submit_file',
				type: 'POST',
				dataType: 'JSON',
				success: function(response_data, textStatus, jqXHR) {
					if(response_data.is_success)
					{
						display_messages(["File uploaded successfully."].concat(response_data.messages), "success");
					}
					else
					{
						display_messages(response_data.errors, "error");
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					display_messages([errorThrown], "error");
				},
				complete: function()
				{
					// Update button text.
					uploadButton.text('Upload');
					uploadButton.prop("disabled", false);

					$("form#syscode_upload_form")[0].reset();
					$('#upload-file-info').text(""); // To clear the custom span for the file upload
				},
				// Form data
				data: form_data,
				//Options to tell jQuery not to process data or worry about content-type.
				cache: false,
				contentType: false,
				processData: false
			});
		}
		else
		{
			display_messages(["Please choose a file to upload"], "warning");
		}
	});

	function clear_messages()
	{
		$("#err_div").text("");
	}

	function display_messages(messages, message_class)
	{
		clear_messages();
		for(var message in messages)
		{
			$("#err_div").append('<p class="text-' + message_class + '">' + messages[message] + '</p>');
		}
	}

	$('#refresh_syscodes_tooltip').tooltip({placement: 'right'});
});

</script>

<script src="/bootstrap/assets/js/bootstrap-dropdown.js"></script>
<script src="/bootstrap/assets/js/bootstrap-tooltip.js"></script>
</body>
</html>