<?php
	$this->load->view('vl_platform_2/ui_core/js_error_handling.php');
	write_vl_platform_error_handlers_html_section();
	write_vl_platform_error_handlers_js();
?>
<script src="/libraries/external/jquery-1.10.2/jquery-1.10.2.min.js"></script>
<script src="/bootstrap/assets/js/bootstrap.min.js"></script>
<script src="/assets/js/materialize_freq.min.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<script src="/assets/js/mui.min.js"></script>
<script src="/libraries/external/js/jquery-file-upload-9.5.7/js/vendor/jquery.ui.widget.js"></script>
<script src="/libraries/external/js/jquery-file-upload-9.5.7/js/jquery.iframe-transport.js"></script>
<script src="/libraries/external/js/jquery-file-upload-9.5.7/js/jquery.fileupload.js"></script>
<script src="/libraries/external/js/jquery-file-upload-9.5.7/js/jquery.fileupload-process.js"></script>
<script src="/libraries/external/js/jquery-placeholder/jquery.placeholder.js"></script>
<script src="/libraries/external/json/json2.js"></script>
<script type="text/javascript">
	// This script section has crontrol for master navigation bar.
	// Only the pieces the user has permission to use are output.
	$(document).ready(function() {
<?php		
		if (isset($master_header_data))
		{
			echo '$("#'.$master_header_data['active_feature_id'].'_feature_link").addClass("active");';
			echo '$("#pull_'.$master_header_data['active_feature_id'].'_feature_link").addClass("active");';
		}
?>		
		$(document).on('click', '.pull_nav_toggle', function(e){
			e.preventDefault();

			$('.pull_nav').toggleClass('open');
			var open = $('.pull_nav').hasClass('open') ? 0 : -300;

			$('.pull_nav').animate(
				{
					'right': open	
				},
				200
			);
		});
	});
</script>
<script type="text/javascript" charset="UTF-8">
	var g_scene_counter = <?php echo $g_scene_counter; ?>;
	function delete_asset(file_counter)
	{
		$('#asset_row_'+file_counter).remove();
	}

	function add_scene()
	{
		g_scene_counter++;
		var scene_string = '<div class="control-group" id="scene_'+g_scene_counter+'_control_group"> \
									<label class="control-label" for="scene_'+g_scene_counter+'_input">Scene '+g_scene_counter+'</label> \
									<div class="controls"> \
										<textarea class=\"span4\" id="scene_'+g_scene_counter+'_input" placeholder="the story continues..." name="scenes[]"></textarea>  \
										<a id="remove_scene_'+g_scene_counter+'_button" onclick="delete_scene()" class="btn btn-danger btn-mini"><span class="icon icon-remove"></span></a> \
									</div> \
								</div>';

		$( "#remove_scene_"+(g_scene_counter-1)+"_button" ).remove();
		$( "#add_scene_cg" ).before( scene_string );
		$('#scene_'+g_scene_counter+'_input').placeholder();
	}

	function delete_scene()
	{
		$('#scene_'+g_scene_counter+'_control_group').hide(200,function(){
				$('#scene_'+g_scene_counter+'_control_group').remove(); 
				g_scene_counter--;
				if(g_scene_counter>1)
				{
					var the_delete_button_string = ' <a id="remove_scene_'+g_scene_counter+'_button" onclick="delete_scene()" class="btn btn-danger btn-mini"><span class="icon icon-remove"></span></a>';
					$( '#scene_'+g_scene_counter+'_input' ).after(the_delete_button_string);
				}

			});
	}

	function is_form_valid(form_data)
	{
		var return_ok = true;
		//remove all errors
		$('.control-group').removeClass('error');
		//check advertiser name
		if(form_data.flight_details.advertiser_name == undefined || form_data.flight_details.advertiser_name == '')
		{
			$('#advertiser_name_control_group').addClass('error');
			$('#advertiser_help').html('Advertiser name is a required field');
			return_ok = false;
		}

		return return_ok;
	}

	$(document).ready( function () {
		$(this).scrollTop(0);

		$('input, textarea').placeholder();

		var g_file_counter = <?php echo $g_file_counter; ?>;

		/* File upload */
		 var url = '/banner_intake/creative_files_upload';
		$('#file_upload').fileupload({
		url: url,
		type: 'POST',
			dataType: 'json',
			add: function(e, data) {
				data.el = $('<div class="file row" data-file="'+ data.files[0].name +'"/>');
				$('#files').append(data.el);
				$(data.el).html('<label class="control-label" style="font-size:0.85em;">' + data.files[0].name.substr(0,20) + '...</label><div class="progress progress-success progress-striped active span6"><div class="bar"></div></div>');
				data.submit();

				$('#main_files_alert')
					.removeClass('alert-success')
					.addClass('alert-info')
					.html('<b>Heads Up!</b> Your files are currently in the process of uploading. Please do not refresh or close your browser window until the uploads are complete.');
			},
			progress: function (e, data) {
				var progress = parseInt(data.loaded / data.total * 100, 10);
				$(data.el).find('.progress .bar').css(
				    'width',
				    progress + '%'
				)
				.text('Uploading ' + progress + '% of ' + Math.floor(data.total / 1024) + 'kb...');
				if (progress == 100) {
				    $(data.el).find('.progress .bar').text('Processing ' + data.files[0].name + '...');
				}
			},
			fail: function(e, data) {
				$(data.el).html('<div class="alert alert-danger">'+ data.files[0].name +' failed to upload!</div>');
			},
			done: function(e, data) {
				var result = data.result;
				g_file_counter++;
				var html = '<div style="margin-left: 20px;"><b>'+ result.data.Key.substr(7) +'</b> <button class="btn btn-danger btn-mini delete_file" data-bucket="'+ result.data.Bucket +'" data-name="'+ result.data.Key +'"><span class="icon icon-remove"></span></button></div>';
				html += '<input type="hidden" name="creative_files['+ g_file_counter +'][url]" value="'+ result.response.ObjectURL +'"/>';
				html += '<input type="hidden" name="creative_files['+ g_file_counter +'][name]" value="'+ result.data.Key +'"/>';
				html += '<input type="hidden" name="creative_files['+ g_file_counter +'][bucket]" value="'+ result.data.Bucket +'"/>';
				html += '<input type="hidden" name="creative_files['+ g_file_counter +'][type]" value="'+ result.data.ContentType +'"/>';
				$(data.el).html(html);

				$('#main_files_alert')
				.removeClass('alert-info')
				.addClass('alert-success')
				.html('<b>Your files have finished uploading!</b> You still need to hit \'Submit\' at the bottom of this page to save your files.');
		    }
		}).prop('disabled', !$.support.fileInput).parent().addClass($.support.fileInput ? undefined : 'disabled');

		// Delete buttons for files
		$(document).on('click', 'button.delete_file', function(e){
		       e.preventDefault();
		       var $el = $(this).closest('div.file');
		       $el.animate({opacity: 0.5}, 200);
		       $.ajax({
			       url: '/banner_intake/creative_files_delete',
			       type: 'POST',
			       data: {
				       'name': $(this).data('name'),
				       'bucket': $(this).data('bucket')
			       },
			       success: function(data, status) {
				       $el.fadeOut(200);
				       $el.remove();
			       },
			       error: function(e, data) {
				       $el.fadeOut(200);
				       $el.remove();
			       }
		       });
		       return false;
	       });

	       // prevent browser's default action for drag+drop
	       $(document).bind('drop dragover', function(e){
		       e.preventDefault();
	       });

	       // If the option changes, show the specified element
	       function check_option(check, el)
	       {
			if (check)
			{
				$(el).show(200);
			}
			else
			{
				$(el).hide(200);
			}
	       }

	       $( "#cta_select" ).on('change', function() {
		       check_option($(this).val() == 'other', '#other_cta_text');
	       });
	       check_option($('#cta_select').val() == 'other', '#other_cta_text');

	       $( "#is_video" ).on('click', function() {
		       check_option($(this).is(':checked'), '#video_details');
	       });

	       $( "#is_map" ).on('click', function() {
		       check_option($(this).is(':checked'), '#map_details');
	       });

	       $( "#is_social" ).on('click', function() {
		       check_option($(this).is(':checked'), '#social_details');
	       });
	});
</script>
</body>
</html>