
  <script src="/bootstrap/assets/js/bootstrap-tab.js"></script>
 <script src="/libraries/external/select2/select2.js"></script>
 <script type="text/javascript" src="//code.jquery.com/ui/1.10.3/jquery-ui.js"></script>


<script>
		
		var g_can_open =  <?php echo $do_galleries_exist; ?>;
		var g_can_save = false;
		var g_can_share = false;
		var g_the_active_gallery = new Object();

		g_the_active_gallery['id'] = '';
		g_the_active_gallery['text'] = '';
		g_the_active_gallery['slug'] = '';
		g_the_active_gallery['u'] = '';
		g_the_active_gallery['is_tracked'] = '';
		g_the_active_gallery['u_name']= '';
		g_default_editor = <?php echo $def_editor; ?>;

		function update_pills_from_global_status()
		{
			if(g_can_open)
			{
				$( "#open_existing_li" ).removeClass( "disabled" );
				$( "#open_existing_menu_option" ).removeClass( "disabled" );
				$("#save_as_existing_modal_pill").css("visibility", "visible");
				$("#save_as_existing_pane").css("visibility", "visible");
			}
			else
			{
				$( "#open_existing_li" ).addClass( "disabled" );
				$( "#open_existing_menu_option" ).addClass( "disabled" );
				$("#save_as_existing_modal_pill").css("visibility", "hidden");
				$("#save_as_existing_pane").css("visibility", "hidden");
			}

			if(g_can_save)
			{
				$('#save_as_gallery_button').removeClass("disabled");
				$( "#save_pill_li" ).removeClass( "disabled" );
				if(g_can_share)
				{
					show_share_box(g_the_active_gallery['u'], g_the_active_gallery['slug'], g_the_active_gallery['is_tracked']=='1');
				}
				else
				{
					$("#share_alert_box").css("visibility", "hidden");
				}
			}
			else //if we can't save - we can't share either
			{
				$( "#save_pill_li" ).addClass( "disabled" );
				$( "#save_as_gallery_button" ).addClass( "disabled" );
				$( "#share_gallery_button" ).addClass( "disabled" );
				$("#share_alert_box").css("visibility", "hidden");
			}
		}

		function adset_dropdown_format(adset){
			if (adset.features == null)
			{
				adset.features = '';
			}
			else
			{
				adset.features = '[ '+adset.features+' ]'
			}
			var markup = '<table><tr><td><img src="'+adset.thumb+'" style="height:50px;"></td><td><h4>'+adset.saved_adset_name+' <small>'+adset.features+'</small></h4></td></tr></table>';
			return markup;
		}

		function adset_selected_format(adset){

			var markup = '<table style="cursor:move">\
							<tbody>\
								<tr>\
									<td>\
										<img src="'+adset.thumb+'" style="height:100px; width:120px;">\
									</td>\
								</tr>\
								<tr>\
									<td><a href="'+adset.preview_link+'" target="_blank">'+adset.saved_adset_name+'</a>\
									</td>\
								</tr>\
							</tbody>\
						</table>';
			return markup;
		}

		function getBaseURL () //this function is used to generate a sharable link
		{
			 return "http://" + location.hostname + "/";
		}

		function handle_delete_button()
		{
			$.ajax({
				type: "POST",
				url: '/custom_gallery/delete_gallery/',
				async: true,
				data: { id: g_the_active_gallery['id'] },
				dataType: 'json',
				error: function(xhr, textStatus, error){
					vl_show_jquery_ajax_error(xhr, textStatus, error);
					$('#delete_gallery_modal').modal('hide');
				},
				success: function(data, textStatus, xhr){ 
					if(vl_is_ajax_call_success(data))
					{
						reset_page_with_new_gallery();
						$('#delete_gallery_modal').modal('hide');
					}
					else
					{
						$('#delete_gallery_modal').modal('hide');
						vl_show_ajax_response_data_errors(data, 'UI err 8975');
					}
				}
			});
		}

		function reset_page_with_new_gallery()
		{
			$('#adset_select').select2('data', [] );
			g_the_active_gallery['id'] = '';
			g_the_active_gallery['text'] = '';
			g_the_active_gallery['slug'] = '';
			g_the_active_gallery['u'] = '';
			g_the_active_gallery['is_tracked'] = '';
			$("#gallery_title").html('New Gallery');
			$("#delete_button_span").css("visibility", "hidden");
			$("#share_alert_box").css("visibility", "hidden");
			g_can_save = false;
			update_pills_from_global_status();
			$('#open_gallery_select').select2('data',null);
		}
		
		function show_share_box(u, slug, trackable)
		{
			$("#share_alert_box").css("visibility", "visible");
			$("#share_link_span").html(getBaseURL()+'share_gallery/'+u+'/'+slug);
			$('#is_tracked').prop('checked', trackable);

		}

		function update_gallery_tracking(is_tracked)
		{
			$.ajax({
				type: "POST",
				url: '/custom_gallery/update_tracking/',
				async: true,
				data: { id: g_the_active_gallery['id'], is_tracked: is_tracked },
				dataType: 'json',
				error: function(xhr, textStatus, error){
					vl_show_jquery_ajax_error(xhr, textStatus, error);
				},
				success: function(data, textStatus, xhr){ 
					if(vl_is_ajax_call_success(data))
					{
						$("#refresh_icon").css("visibility", "hidden");
						//g_the_active_gallery = $('#open_gallery_select').select2('data');
						g_the_active_gallery['is_tracked'] = is_tracked;
						$('#open_gallery_select').select2('data',g_the_active_gallery);
					}
					else
					{
						vl_show_ajax_response_data_errors(data, 'error ee514ed5');
					}
				}
			});
		}

		function open_gallery_to_edit()
		{
			$('#open_gallery_modal').modal('hide');
			$("#refresh_icon").css("visibility", "visible");
			$.ajax({
				type: "POST",
				url: '/custom_gallery/get_gallery/',
				async: true,
				data: { id: $('#open_gallery_select').select2('data').id },
				dataType: 'json',
				error: function(xhr, textStatus, error){
					vl_show_jquery_ajax_error(xhr, textStatus, error);
				},
				success: function(data, textStatus, xhr){ 
					if(vl_is_ajax_call_success(data))
					{
						var adset_data = $.parseJSON(data.json_adset_string);
						g_the_active_gallery = $('#open_gallery_select').select2('data');
						$('#the_selected_save_as_gallery').select2('data',g_the_active_gallery);
						$("#gallery_title").html($('#open_gallery_select').select2('data').text);
						$("#delete_button_span").css("visibility", "visible");
						$("#adset_select").select2('data', adset_data );

						$('#the_gallery_owner').select2('data',[{id:g_the_active_gallery['u'],text:g_the_active_gallery['u_name']}]);
						g_can_save = true;
						g_can_share = true;
						update_pills_from_global_status();
						
						if(data.alert_type == "NO_ADSETS")
						{
							alert('It looks like this gallery has no adsets. Feel free to add valid adsets to the gallery or delete the gallery to remove it altogether.');
						}
						else if(data.alert_type == "NO_GALLERY")
						{
							alert('It looks like this gallery has expired.');
						}
						$("#refresh_icon").css("visibility", "hidden");
					}
					else
					{
						vl_show_ajax_response_data_errors(data, 'UI err 95689');
					}
				}
			});
		}
	
		$(document).ready( function () {
			update_pills_from_global_status();
			$('#adset_select').select2({
				placeholder: "...start typing to select adsets by name or category...",
				minimumInputLength: 0,
				multiple: true,
				ajax: {
					url: "/custom_gallery/broadcasted_adset_feed/",
					type: 'POST',
					dataType: 'json',
					data: function (term, page) {
						term = (typeof term === "undefined" || term == "") ? "%" : term;
						return {
							q: term,
							page_limit: 20,
							page: page,
						};
					},
					results: function (data) {
						return {results: data.result, more: data.more};
					}
				},
				escapeMarkup: function (m) { return m; },
				formatResult: adset_dropdown_format,
				formatSelection: adset_selected_format, 
			}).on("change",function(e){
				g_can_save = (!jQuery.isEmptyObject(e.val));
				g_can_share = false;
				update_pills_from_global_status();
			});


			$("#adset_select").select2("container").find("ul.select2-choices").sortable({
				containment: 'parent',
				start: function() { $("#adset_select").select2("onSortStart"); },
				update: function() { $("#adset_select").select2("onSortEnd"); }
			});


			
			$("#save_as_gallery_button").on('click',function(e){
				if ($(this).hasClass('disabled'))
				{
					return;
				}
				else{
					if(jQuery.isEmptyObject($('#adset_select').select2('data')))
					{
						alert('please select some ads to go in your awesome gallery');
					}
					else
					{
						$('#save_as_modal').modal('show'); //pop up the modal
						if(g_the_active_gallery['id'] == '')//if we don't have an active gallery we're going to default to save as new
						{
							$('#save_as_new_modal_anchor').tab('show'); 
							$('#the_gallery_owner').select2('data', g_default_editor );
						}
						else //otherwise prefill the select2 with the active gallery
						{
							$('#save_as_existing_modal_anchor').tab('show'); 
							$('#the_selected_save_as_gallery').select2('data', g_the_active_gallery );
							//if this gallery has been saved in the past  it has an owner - prefill the owner accordingly
							var this_gallery_owner = new Object();
							this_gallery_owner.id = g_the_active_gallery.u;
							this_gallery_owner.text = g_the_active_gallery.u_name;
							$('#the_gallery_owner').select2('data', this_gallery_owner );
						}
					}
				} 
			});

			$("#save_as_new_modal_go").on('click',function(e){
				if($('#new_gallery_name').val() !== '')
				{
					$('#save_as_modal').modal('hide');
					$("#refresh_icon").css("visibility", "visible");
					$.ajax({
							type: "POST",
							url: '/custom_gallery/save_new_gallery/',
							async: true,
							data: { adsets: $('#adset_select').select2('data') , g_name: $('#new_gallery_name').val(), is_tracked: 0, u_id: $('#the_gallery_owner').select2('data').id},
							dataType: 'json',
							error: function(xhr, textStatus, error){
								vl_show_jquery_ajax_error(xhr, textStatus, error);
							},
							success: function(data, textStatus, xhr){ 
								if(vl_is_ajax_call_success(data))
								{
									if(data.alert_type == "DUPLICATE")
									{
										alert('This gallery name is already taken for this user. Please try another name for your new gallery.');
									}
									else
									{
										g_the_active_gallery['id'] = data.saved_g_id;
										g_the_active_gallery['text'] = data.saved_g_name;
										g_the_active_gallery['slug'] = data.slug;
										g_the_active_gallery['u'] = data.u;
										g_the_active_gallery['u_name'] = data.u_name;
										g_the_active_gallery['is_tracked'] = data.is_tracked;
										$("#gallery_title").html(data.saved_g_name);
										$("#delete_button_span").css("visibility", "visible");
										
										$('#new_gallery_name').val('');
										$('#the_selected_save_as_gallery').select2('data',g_the_active_gallery);
										$('#open_gallery_select').select2('data',g_the_active_gallery);
										g_can_open = true;
										g_can_share = true;
										update_pills_from_global_status();
									}
									$("#refresh_icon").css("visibility", "hidden");
								}
								else
								{
									vl_show_ajax_response_data_errors(data, 'UI err 8975');
								}
							}
						});
				}
				else
				{
					alert('please name this gallery before trying to save as new');
				}

			});

			$('#the_selected_save_as_gallery').select2({
				placeholder: "Select gallery",
				minimumInputLength: 0,
				multiple: false,
				ajax: {
					url: "/custom_gallery/existing_galleries_feed/",
					type: 'POST',
					dataType: 'json',
					data: function (term, page) {
						term = (typeof term === "undefined" || term == "") ? "%" : term;
						return {
							q: term,
							page_limit: 10,
							page: page
						};
					},
					results: function (data) {
						return {results: data.result, more: data.more};
					}
				}
			}).on("change",function(e){
				var existing_editor = new Object();
				existing_editor.id = $(this).select2('data').u
				existing_editor.text = $(this).select2('data').u_name;
				$('#the_gallery_owner').select2('data',existing_editor);
			});

			$("#the_gallery_owner").select2({
				data: <?php echo $user_list;?>,
			});
			<?php if(!$can_user_see_editor_dropdown){ ?>
			$("#the_gallery_owner_container").css( "display", "none" );
			<?php } ?>


			$("#save_as_existing_modal_go").on('click',function(e){
				if(jQuery.isEmptyObject($('#the_selected_save_as_gallery').select2('data')))
				{
					alert('please select the gallery you\'d like to save as');
				}
				else
				{
					$('#save_as_modal').modal('hide');
					$("#refresh_icon").css("visibility", "visible");
					$.ajax({
							type: "POST",
							url: '/custom_gallery/save_existing_gallery/',
							async: true,
							data: { adsets: $('#adset_select').select2('data') , g_id: $('#the_selected_save_as_gallery').select2('data').id, is_tracked: $('#the_selected_save_as_gallery').select2('data').is_tracked, u_id: $('#the_gallery_owner').select2('data').id},
							dataType: 'json',
							error: function(xhr, textStatus, error){
								vl_show_jquery_ajax_error(xhr, textStatus, error);
							},
							success: function(data, textStatus, xhr){ 
								if(vl_is_ajax_call_success(data))
								{
									g_the_active_gallery = $('#the_selected_save_as_gallery').select2('data');
									$('#open_gallery_select').select2('data',null);
									$('#the_selected_save_as_gallery').select2('data',g_the_active_gallery);
									$("#gallery_title").html($('#the_selected_save_as_gallery').select2('data').text);
									$("#delete_button_span").css("visibility", "visible");
									$('#new_gallery_name').val('');
									g_can_share = true;

									g_the_active_gallery.u  = $('#the_gallery_owner').select2('data').id;
									g_the_active_gallery.u_name  = $('#the_gallery_owner').select2('data').text;
									update_pills_from_global_status();
						 			$("#refresh_icon").css("visibility", "hidden");
								}
								else
								{
									vl_show_ajax_response_data_errors(data, 'UI err d4c156');
								}
							}
						});
				}
			});

			$("#open_new_menu_option").on('click',function(e){
				reset_page_with_new_gallery();
			});

			$('#open_gallery_select').select2({
					placeholder: "Select gallery",
					minimumInputLength: 0,
					multiple: false,
					ajax: {
						url: "/custom_gallery/existing_galleries_feed/",
						type: 'POST',
						dataType: 'json',
						data: function (term, page) {
							term = (typeof term === "undefined" || term == "") ? "%" : term;
							return {
								q: term,
								page_limit: 10,
								page: page
							};
						},
						results: function (data) {
							return {results: data.result, more: data.more};
						}
					}
				});

			$("#open_existing_menu_option").on('click',function(e){
				if ($(this).hasClass('disabled'))
				{
					return;
				}
				$('#open_gallery_modal').modal('show');
				
			});
			//make the open button work on the open existing gallery
			$('#open_gallery_modal_go').on('click',function(e){

				//go get the adsets associated with the selected gallery
				if(!jQuery.isEmptyObject($('#open_gallery_select').select2('data')))
				{
					open_gallery_to_edit();
				}
				else
				{
					alert('please select a gallery to open');
				}
			});

			
			$('#is_tracked').change(function() {
				$("#refresh_icon").css("visibility", "visible");
				update_gallery_tracking($(this).is(":checked") ? 1 : 0);
			});

			$('#delete_gallery').on('click',function(e){
				handle_delete_button();
			});

			
			<?php if(!is_null($preset)){?>
			//preset the form here
			var is_gallery_valid = <?php echo($live_gallery_entry_exists );?>;
			if(is_gallery_valid)
			{
				g_the_active_gallery = eval(<?php echo $the_active_gallery; ?>);
				$('#open_gallery_select').select2('data',g_the_active_gallery);
				open_gallery_to_edit();
			}
			else
			{
				alert('the gallery that you are trying to preload may have expired.');
			}

			<?php } ?>

 		});//doc ready


</script>
