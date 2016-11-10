<script type="text/javascript">
var g_selected_sites;
var g_timeout_id;
function get_sites_ui(str_array) //the input is the multiselect sites
{
	g_selected_sites = str_array;
	paint_site_forms(0);
}

function paint_site_forms(i)
{
	disable_site_buttons();
	var site_form;
	$.ajax({
		type: "POST",
		url: '/media_targeting_tags/get_media_targeting_tags_for_site/',
		async: true,
		data: {id: g_selected_sites[i]},
		dataType: 'json',
		error: function(xhr, textStatus, error){
			show_tags_error('Error 93220306: failed getting sitename and tags for site id: '+g_selected_sites[i]);
			vl_show_jquery_ajax_error(xhr, textStatus, error);
		},
		success: function(msg){
			if(vl_is_ajax_call_success(msg))
			{
				site_form = '<b>'+msg.data.url+' </b><a href="//'+msg.data.url+'" target="_blank"><i class="icon-external-link"></i></a> <br>'+build_tag_string(g_selected_sites[i])+'<br>';
				if(i==0)
				{
					$("#sites-controls").html('<div class="row"><div class="span12"><div class="row site-edit-header-row" ><div class="span9 "><h3><i class="icon-edit"></i>Add and remove tags from the sites below</h3></div></div></div></div>'+site_form);
				}
				else
				{
					$("#sites-controls").append(site_form);
				}
				
				//load the existing tags
				var preselected_tags = new Array();
				if(msg.data.media_targeting_tags != false)
				{
					for(var i_tag = 0; i_tag < msg.data.media_targeting_tags.length; i_tag++)
					{
						preselected_tags.push({id: msg.data.media_targeting_tags[i_tag].id, text: msg.data.media_targeting_tags[i_tag].tag_copy, tag_type: msg.data.media_targeting_tags[i_tag].tag_type});
					}
				}
				///after the form is written and we've grabbed all the preselected tags for this site, we should turn the input field into a select2
				$('#multi-tag-'+g_selected_sites[i]).select2({
					multiple: true,
					placeholder: "select tags",
					allowClear: true,
					minimumInputLength: 0,
					ajax:{
						type: 'POST',
						url: '/media_targeting_tags/ajax_media_targeting_tags/',
						dataType: 'json',
						data: function (term,page){
							term = (typeof term === "undefined" || term == "") ? "%" : term;
							return{
								q: term,
								page_limit: 10,
								page: page
							};
						},
						results: function(data){
							return {results: data.result, more: data.more};
						}
					}
				}).on("change",function(e){
					$( "#tags-go-button" + $(this).data("site")).button('reset');
				});
				
				if(preselected_tags.length > 0 )
				{
				  	$('#multi-tag-'+g_selected_sites[i]).select2('data', preselected_tags);
				}
				
				//initially have the button in the complete mode
				$("#tags-go-button"+g_selected_sites[i]).button('loading'); //require change to input to make button usable
				
				//then we need to turn on the save button
				$("#tags-go-button"+g_selected_sites[i]).click(function() {
					var this_element = this;
					$(this_element).button('pending');
					$.ajax({
						type: "POST",
						url: '/media_targeting_tags/save_tags_to_site/',
						async: true,
						data: {tag_data: JSON.stringify($('#multi-tag-'+$(this_element).data("site")).select2('data')), site: $(this_element).data("site")},
						dataType: 'json',
						error: function(xhr, textStatus, error){
							show_tags_error('Error 8329583: failed updating tags for site: '+$(this_element).data("site"));
							vl_show_jquery_ajax_error(xhr, textStatus, error);
							
						},
						success: function(msg){
							if(vl_is_ajax_call_success(msg))
							{
								$(this_element).button('loading');
							}
							else
							{
								vl_show_ajax_response_data_errors(msg, 'error returned from server when updating tags for site: '+$(this_element).data("site"));
							}
						}
					});
				});
				if(i < g_selected_sites.length-1)
				{
					i++;
					paint_site_forms(i); //recursive - do the next one
				}
				else
				{
					enable_site_buttons();
				}
			}
			else
			{
				vl_show_ajax_response_data_errors(msg, 'error returned from server when getting media targeting tags for site');
			}
		}
	});
}

function build_tag_string(site_id){
	var tags_string = '';
	tags_string += '<div class="row"><div class="span12">';
	tags_string += '<input type="hidden" id="multi-tag-'+site_id+'" class="span6 site-tag-multi-class" data-site="'+site_id+'">'
	tags_string += '<button class="span2 btn btn-info" data-pending-text="loading..." data-loading-text="saved" id="tags-go-button'+site_id+'" data-site="'+site_id+'" >save tags</button>';
	tags_string += '<br>';
	tags_string += '</div></div>';
	return tags_string;
}

function sites_selected_go(){
	$("#sites-controls").html( '<h3><i class="icon-edit"></i>Add and remove tags from the sites below</h3><img src="/images/media_targeting_tags_loader.gif">');
	if($("#site-tag-multi-id").val() != '')
	{
		get_sites_ui($("#site-tag-multi-id").select2("val"));
	}
	else
	{
		$("#sites-controls").html( '<div class="alert alert-info">No Sites Selected</div>');
	}
}

function enable_site_buttons(){
	$("#sites-selected-button").button('reset');
	$("#sites-selected-button").prop('disabled', false);
	$("#bulk_add_tags_modal_open_button").button('reset');
	$("#bulk_add_tags_modal_open_button").prop('disabled', false);
}

function disable_site_buttons(){
	$("#sites-selected-button").button('loading');
	$("#sites-selected-button").prop('disabled', true);
	$("#bulk_add_tags_modal_open_button").button('loading');
	$("#bulk_add_tags_modal_open_button").prop('disabled', true);
}

$(document).ready(function() {
	$('#site-tag-multi-id').select2({
		multiple: true,
		placeholder: "start typing to find sites...",
		allowClear: true,
		minimumInputLength: 0,
		ajax:{
			url: '/media_targeting_tags/ajax_sites/',
			type: 'POST',
			dataType: 'json',
			data: function (term,page){
				term = (typeof term === "undefined" || term == "") ? "%" : term;
				return{
					q: term,
					page_limit: 10,
					page: page
				};
			},
			results: function(data){
				return {results: data.result, more: data.more};
			}
		},
		initSelection: function(element, callback){
			var data=[];
			$(element.val().split(",")).each(function(i){
				var item = this.split('||');
				data.push({
					id: item[0],
					text: item[1]
				});
			});
			callback(data);
		}
	}).on("change",function(e){
		if($(this).select2("val")=='')
		{
			disable_site_buttons();
		}
		else
		{
			enable_site_buttons();
		}
	});

	$("#sites-selected-button").click(sites_selected_go);

	//attach a click function to the bulk add tags modal thingie
	$( "#bulk_add_tags_modal_open_button").click(function(event) {
		event.preventDefault();
		$("#bulk_add_tags_modal_body").html('<div class="row"><div class="span5"><input type="hidden" id="bulk-tag-input" class="span5"></div></div>');
		//make the bulk add a select 2 input
		$('#bulk-tag-input').select2({
			multiple: true,
			placeholder: "select tags",
			allowClear: true,
			minimumInputLength: 0,
			ajax:{
				type: 'POST',
				url: '/media_targeting_tags/ajax_media_targeting_tags/',
				dataType: 'json',
				data: function (term,page){
					term = (typeof term === "undefined" || term == "") ? "%" : term;
					return{
						q: term,
						page_limit: 10,
						page: page
					};
				},
				results: function(data){
					return {results: data.result, more: data.more};
				}
			}
		});
	});//bulk tags button click

	//attach a click function to the search sites by tags modal thingie
	$( "#search_by_tags_modal_open_button").click(function() {
		$("#search_by_tags_modal_body").html('<div class="row"><div class="span4"><input type="hidden" id="search-by-tags-input" class="span5"></div></div>');
		//make the bulk add a select 2 input
		$('#search-by-tags-input').select2({
			multiple: true,
			placeholder: "select tags",
			allowClear: true,
			minimumInputLength: 0,
			ajax:{
				type: 'POST',
				url: '/media_targeting_tags/ajax_media_targeting_tags/',
				dataType: 'json',
				data: function (term,page){
					term = (typeof term === "undefined" || term == "") ? "%" : term;
					return{
						q: term,
						page_limit: 10,
						page: page
					};
				},
				results: function(data){
					return {results: data.result, more: data.more};
				}
			}
		});
	});//search by tags button click
	
	$('#search_by_tags_go_button').click(function(){
		if($('#search-by-tags-input').val() != '')
		{
			$("#sites-controls").html('');
			$.ajax({
				type: "POST",
				url: '/media_targeting_tags/get_sites_by_tags/',
				async: true,
				data: { tags: JSON.stringify($('#search-by-tags-input').select2('data'))},
				dataType: 'json',
				error: function(xhr, textStatus, error){
					vl_show_jquery_ajax_error(xhr, textStatus, error);
				},
				success: function(msg){ 
					if(vl_is_ajax_call_success(msg))
					{
						var sites_to_load = new Array();
						if(msg.is_warning == true)
						{
							show_tags_error(msg.warning);
						}
						else
						{
							$.each(msg.data, function(site_index, site_data){
								sites_to_load.push(site_data.site_id+"||"+site_data.url);
							});
							if(sites_to_load.length > 0)
							{
								$('#site-tag-multi-id').select2('val', sites_to_load);
								enable_site_buttons();
							}
							else
							{
								$('#site-tag-multi-id').select2('val', null);
								disable_site_buttons();
							}
							
						}
						$('#search_by_tag_modal').modal('hide');
					}
					else
					{
						vl_show_ajax_response_data_errors(msg, 'failed to get sites by tags');
					}
				}
			});
		}
		else
		{
			show_tags_error("Warning 336735: No tags selected");
		}
	});

	$( "#bulk-sites-button").click(function() {
		$.ajax({
			type: "POST",
			url: '/media_targeting_tags/get_site_packs_select/',
			async: true,
			data: { },
			dataType: 'json',
			error: function(xhr, textStatus, error){
				show_tags_error('Error 4929356: failed getting sitepack select');
				vl_show_jquery_ajax_error(xhr, textStatus, error);
			},
			success: function(msg){
				if(vl_is_ajax_call_success(msg))
				{
					var select_elements = '<select id="sitepacks_select" class="span5"><option></option>';
					$.each(msg.data, function(index, value){
						select_elements += '<option value="'+value.id+'">'+value.pack+'</option>';
					});
					select_elements += '</select>';
					$("#sitepack_select_modal_body").html(select_elements);
					$('#sitepacks_select').select2({
						placeholder: "select site pack",
						allowClear: true,
						minimumInputLength: 0
					});
				}
				else
				{
					vl_show_ajax_response_data_errors(data, 'getting site packs from server failed');
				}
			}
		});
	});//bulk sites button click
	
	$("#load_sitepack_button").click(function(){
		$('#sitepack_select_modal').modal('hide');
		$("#sites-controls").html('');
		$.ajax({
			type: "POST",
			url: '/media_targeting_tags/get_sites_from_sitepack/',
			async: true,
			data: {pack_id: $('#sitepacks_select').val()},
			dataType: 'json',
			error: function(xhr, textStatus, error){
				show_tags_error('error retreiving sites for selected sitepack');
				vl_show_jquery_ajax_error(xhr, textStatus, error);
			},
			success: function(msg){
				if(vl_is_ajax_call_success(msg))
				{
					if(!msg.data.is_warning)
					{
						var sites = msg.data.sites_to_push;
						var sites_failed = msg.data.sites_failed;
						if(sites.length > 0 )
						{
							$('#site-tag-multi-id').select2('val', sites);
							enable_site_buttons();
						}
						else
						{
							$('#site-tag-multi-id').select2('val', null);
							disable_site_buttons();
						}
						if(sites_failed.length > 0)
						{
							var error = "Failed to insert some sites: ";
							$.each(sites_failed, function(index, value){
								if(index > 0)
								{
									error += ', ';
								}
								error += value;
							});
							show_tags_error(error);
						}
					}
					else
					{
						show_tags_error(msg.data.warning);
					}
				}
				else
				{
					vl_show_ajax_response_data_errors(data, 'getting sites for selected sitepack failed');
				}
			}
		});
	});
	///make the bulk tag save button work
	$( "#bulk_add_tags_save_button").click(function(){
		g_selected_sites = $("#site-tag-multi-id").select2("val");
		$('#bulk_add_tags_modal').modal('hide');
		//clear out all the mini forms
		$("#sites-controls").html('<img src="/images/media_targeting_tags_loader.gif">');

		var ajax_complete_count = 0;
		//loop through the selected sites saving tags
		$.each(g_selected_sites,function(site_index, site_id){
			$.ajax({
				type: "POST",
				url: '/media_targeting_tags/add_tags_to_site/',
				async: true,
				data: {
					tags: JSON.stringify($("#bulk-tag-input").select2('data')),
					site: site_id
				},
				dataType: 'json',
				error: function(xhr, textStatus, error){
					ajax_complete_count++;
					if(ajax_complete_count == g_selected_sites.length)
					{
						paint_site_forms(0);
					}
					vl_show_jquery_ajax_error(xhr, textStatus, error);
				},
				success: function(msg){
					ajax_complete_count++;
					if(vl_is_ajax_call_success(msg))
					{
						//do nothing
					}
					else
					{
						vl_show_ajax_response_data_errors(msg, 'adding tags to site failed');
					}
					if(ajax_complete_count == g_selected_sites.length)
					{
						//repaint the mini forms
						paint_site_forms(0);
					}
				}
			});
		});
	});

	$( "#add_raw_sites_button").click(function() {
		var i;
		var site_rows;
		var demo_array;
		var sites_to_load = new Array();
		var errors = "";
		var num_response = 0;
		site_rows = $.trim($('#raw_sites_text').val()).split("\n");
		for(i = 0; i < site_rows.length; i++)
		{
			demo_array = site_rows[i].split(/\s+/);
			if(demo_array.length == 26)
			{
				demo_array.pop();		
			}
			if(demo_array.length == 25)
			{
				$.ajax({
					type: "POST",
					url: '/media_targeting_tags/save_site/',
					async: true,
					data: {demo_array: demo_array},
					dataType: 'json',
					error: function(xhr, textStatus, error){
						num_response++;
						if(num_response == site_rows.length)
						{
							if(sites_to_load.length > 0)
							{
								enable_site_buttons();
							}
						}
						vl_show_jquery_ajax_error(xhr, textStatus, error);
					},
					success: function(msg){
						num_response++;
						if(vl_is_ajax_call_success(msg))
						{
							if(msg.is_warning)
							{
								show_tags_error(msg.warnings);
							}
							else
							{
								sites_to_load.push(msg.data.site_id+"||"+msg.data.site_url);
								$('#site-tag-multi-id').select2('val', sites_to_load);
							}
						}
						else
						{
							vl_show_ajax_response_data_errors(msg, 'saving site failed');
						}
						if(num_response == site_rows.length)
						{
							if(sites_to_load.length > 0)
							{
								enable_site_buttons();
							}
						}
					}
				});
			}
			else
			{
				errors += "Invalid length for custom site: "+site_rows[i]+"<br/>";
			}
		}
		if(errors != "")
		{
			show_tags_error(errors);
		}
		$('#add_raw_sites_modal').modal('hide');
	});

	disable_site_buttons();
});///document ready

function show_tags_error(error_msg)
{
	window.clearTimeout(g_timeout_id);
	$("#tags_alert_div").append(error_msg+"<br>");
	$(".alert.alert-error").show();
	g_timeout_id = window.setTimeout(function(){
		$('.alert.alert-error').hide();
		$("#tags_alert_div").html('');
		
	}, 18000);
}
</script>
