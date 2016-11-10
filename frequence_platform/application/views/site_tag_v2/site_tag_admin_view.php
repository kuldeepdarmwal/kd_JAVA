
<html>
<head>
<script type="text/javascript" src="/bootstrap/assets/js/bootstrap.min.js"></script>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title>Site Tagging Admin</title>

<style>
	#codiv 
	{
		font-size: 10px;
	}
	
	.thdr 
	{
		font-size: 12px;
		background-image: -webkit-linear-gradient(top, #fff 20%, #f0f0f0 50%, #e8e8e8 52%, #BCBEF5 100%);
	}
	
	#tabsContent 
	{
		font-size: 10px;
	}
	
	input[type="text"]
	{
		font-size: 10px;
	}

	.select2-result-label 
	{
		font-size: 10px;
	}
	
	.context .select2-container-multi .select2-choices .select2-search-choice 
	{
		background-image: -webkit-linear-gradient(top, #f4f4f4 20%, #f0f0f0 50%, #e8e8e8 52%, #F8D9FA 100%);
	}
	
	.geo .select2-container-multi .select2-choices .select2-search-choice 
	{
		background-image: -webkit-linear-gradient(top, #f4f4f4 20%, #f0f0f0 50%, #e8e8e8 52%, #CBEAF5 100%);
	}
	
	.demo .select2-container-multi .select2-choices .select2-search-choice 
	{
		background-image: -webkit-linear-gradient(top, #f4f4f4 20%, #f0f0f0 50%, #e8e8e8 52%, #CDF5CB 100%);
	}
	
	.site .select2-container-multi .select2-choices .select2-search-choice 
	{
		background-image: -webkit-linear-gradient(top, #f4f4f4 20%, #f0f0f0 50%, #e8e8e8 52%, #EBE702 100%);
	}
	
	.alert, .alert h4 
	{
		color: #757877;
	}
	
	#site_div .nav-tabs>li>a 
	{
		color: #555555;
		background-color: #D2D6D2;
		border: 1px solid #dddddd;
		border-bottom-color: transparent;
	}
	
	#site_div .nav-tabs>li.active>a 
	{
		color: #555555;
		background-color: #D2F5CE;
		border: 1px solid #dddddd;
		border-bottom-color: transparent;
	}
	
	.highlight 
	{
		background-image: linear-gradient(90deg, #4F364C, #FFFFFF);
	}
</style>

<script type="text/javascript">
var g_selected_sites;
var g_timeout_id;
var i;

$(document).ready(function() 
{
 	$('#search_tags_select').select2({
		multiple: true,
		width: '1200px',
		placeholder: "Select tags",
		allowClear: true,
		minimumInputLength: 0,
		ajax:{
			type: 'POST',
			url: '/site_tag_admin_controller/ajax_media_targeting_tags/',
			dataType: 'json',
			data: function (term, page){
				term = (typeof term === "undefined" || term == "") ? "%" : term;
				return{
					q: term,
					page_limit: 10,
					page: page,
					source: 'all'
				};
			},
			results: function(data){
				return {results: data.result, more: data.more};	
			}
		}
	}) ;
	$("#sites-selected-button").click(sites_selected_go);
	$("#sites-clear-button").click(sites_clear_go);
});

function sites_clear_go()
{
	if($("#search_tags_select").val() != '')
	{
		$("#search_tags_select").select2("data", "");
	}
}

function sites_selected_go()
{
	if($("#search_tags_select").val() != '')
	{
		get_sites_ui($("#search_tags_select").select2("val"));
	}
	else
	{
		show_tags_error("");
	}
}

function get_sites_ui(str_array) //the input is the multiselect sites
{
	g_selected_sites = str_array;
	paint_site_forms();
}

function get_comscore_data(url) 
{
	showoverlay();
	$.ajax({
		type: "POST",
		url: '/site_tag_admin_controller/get_comscore_site_demo_data/',
		async: true,
		data: { url: url },
		dataType: 'json',
		error: function(xhr, textStatus, error)
		{
			show_tags_error('Error 4929356: failed getting sitepack select');
			hideoverlay();
		},
		success: function(msg)
		{
			if(vl_is_ajax_call_success(msg))
			{
				var comscore_html="";
				if (msg == undefined || msg.data == undefined  || msg.data[0] == undefined || msg.data[0].length == 0) 
				{
					comscore_html="No data found";
				}
				else 
				{	
					comscore_html="Comscore Demo Data: ";
					for (var key in msg.data[0])
					{
						var val=msg.data[0][key];
						if (val!="" && val != "0")
							comscore_html+=key + ": "+val+"; " 
					}
				}	
				show_tags_error(comscore_html);
			}
			else
			{
				show_tags_error('Failed to get the Comscore data');
			}
			hideoverlay();
		}
	});
}

function prepare_site_view(msg)
{
	var html_string="<table border=0 class='table table-striped' width='100%'><tr><td width='10%' class='thdr'>Site</td><td width='30%' class='thdr'>Snag Tags</td><td width='30%' class='thdr'>Cities</td><td width='30%' class='thdr'>Stereotypes</td></tr>";
	var site_data_array=new Array();
	$("#sites-controls").html("");
	for (var i=0; i < msg.data.length; i++)
	{
		var site_id=msg.data[i]['site_id'];
		var url=msg.data[i]['url'];
		var tag=msg.data[i]['tag'];
		var type=msg.data[i]['type'];
		var id=msg.data[i]['tag_id']; 
		
 		if (site_data_array[url] == undefined || site_data_array[url].length == 0) 
		{
			site_data_array[url]=new Array();
			site_data_array[url]['url']=url;
			site_data_array[url]['site_id']=site_id;
		}
		if (site_data_array[url][type] == undefined || site_data_array[url][type].length == 0) 
		{
			site_data_array[url][type]=new Array();
		}
		if (id != undefined && id > 0)
			site_data_array[url][type].push({id:id, text:tag});
	}
 
	for (var key in site_data_array)
	{
		var url1=site_data_array[key]['url'];
		var site_id=site_data_array[key]['site_id'];
		var contextual_array=site_data_array[key][1];
		var geo_array=site_data_array[key][2];
		var stereo_array=site_data_array[key][4];//chkk
		var context_select_name = 'co'+site_id;
		var geo_select_name = 'ge'+site_id;
		var stereo_select_name = 'st'+site_id;
		html_string += '<tr valign=top><td><a href="http://'+url1+'" target="_blank">'+url1+'</a>&nbsp;<span onclick="get_comscore_data(\''+url1+'\')"><i class="icon-eye-open"></i></span></td><td class=context>'
		+build_tag_string("co"+site_id )+'</td><td class=geo>'
		+build_tag_string("ge"+site_id )+'</td><td class=demo>'
		+build_tag_string("st"+site_id )+'</td></tr>';
 	}
	html_string += "</table>";
	$("#sites-controls").html(html_string);

	for (var key in site_data_array)
	{
		var url1=site_data_array[key]['url'];
		var site_id=site_data_array[key]['site_id'];
		
		var contextual_array=site_data_array[key][1];
		var geo_array=site_data_array[key][2];
		var stereo_array=site_data_array[key][4];
		
		var context_select_name = 'co'+site_id;
		var geo_select_name = 'ge'+site_id;
		var stereo_select_name = 'st'+site_id;
		$('#'+context_select_name).select2({
			multiple: true,
			width: '300px',
			placeholder: "Select tags",
			allowClear: true,
			minimumInputLength:0,
			ajax:{
				type: 'POST',
				url: '/site_tag_admin_controller/ajax_media_targeting_tags/',
				dataType: 'json',
				data: function (term,page){
					term = (typeof term === "undefined" || term == "") ? "%" : term;
					return{
						q: term,
						page_limit: 10,
						page: page,
						source : 1
					};
				},
				results: function(data)
				{
					return {results: data.result, more: data.more};
				}
			}
		})
		.on("change",function(e)
		{
			var this_element = this;
			show_tags_error("Saving...");
			showoverlay();
			$.ajax({
				type: "POST",
				url: '/site_tag_admin_controller/save_site_data_grid/',
				async: true,
				data: {tag_data: JSON.stringify($(this).select2('data')), site_id: this.id, tag_type : 1},
				dataType: 'json',
				error: function(xhr, textStatus, error)
				{
					show_tags_error('Error 8329583: failed updating tags for site: '+$(this_element).data("site"));
					vl_show_jquery_ajax_error(xhr, textStatus, error);
					hideoverlay();
				},
				success: function(msg)
				{
					if(vl_is_ajax_call_success(msg))
					{
						var message="Saved";
						if (msg["message"] != undefined && msg["message"] != "")
							message=msg["message"];
						show_tags_error(message);
					}
					else
					{
						show_tags_error(msg["errors"]);
					}
					hideoverlay();
				}
			});
			
		}); 
		if(contextual_array.length > 0 ) 
		{
			$('#'+context_select_name).select2('data', contextual_array);
		}
		
		// geo section
		$('#'+geo_select_name).select2({
			multiple: true,
			width: '300px',
			placeholder: "Select tags",
			allowClear: true,
			minimumInputLength: 0,
			ajax:{
				type: 'POST',
				url: '/site_tag_admin_controller/ajax_media_targeting_tags/',
				dataType: 'json',
				data: function (term,page)
				{
					term = (typeof term === "undefined" || term == "") ? "%" : term;
					return {
						q: term,
						page_limit: 10,
						page: page,
						source : 2
					};
				},
				results: function(data)
				{
					return {results: data.result, more: data.more};
				}
			}
		})
		.on("change",function(e)
		{
			var this_element = this;
			show_tags_error("Saving...");
			showoverlay();
			$.ajax({
				type: "POST",
				url: '/site_tag_admin_controller/save_site_data_grid/',
				async: true,
				data: {tag_data: JSON.stringify($(this).select2('data')), site_id: this.id, tag_type : 2},
				dataType: 'json',
				error: function(xhr, textStatus, error)
				{
					show_tags_error('Error 8329583: failed updating tags for site: '+$(this_element).data("site"));
					vl_show_jquery_ajax_error(xhr, textStatus, error);
					hideoverlay();
				},
				success: function(msg)
				{
					if(vl_is_ajax_call_success(msg))
					{
						var message="Saved";
						if (msg["message"] != undefined)
							message+=" - "+ msg["message"];
						show_tags_error(message);
					}
					else
					{
						show_tags_error(msg["errors"]);
						//vl_show_ajax_response_data_errors(msg, 'error returned from server when updating tags for site: '+$(this_element).data("site"));
					}
					hideoverlay();
				}
			});
		}); 
		
		if(geo_array.length > 0 ) 
		{
			$('#'+geo_select_name).select2('data', geo_array);
		}
	 
		// stereo section
		$('#'+stereo_select_name).select2({
			multiple: true,
			width: '300px',
			placeholder: "Select tags",
			allowClear: true,
			minimumInputLength: 0,
			ajax:{
				type: 'POST',
				url: '/site_tag_admin_controller/ajax_media_targeting_tags/',
				dataType: 'json',
				data: function (term,page)
				{
					term = (typeof term === "undefined" || term == "") ? "%" : term;
					return{
						q: term,
						page_limit: 10,
						page: page,
						source : 4
					};
				},
				results: function(data){
					return {results: data.result, more: data.more};
				}
			}
		})
		.on("change",function(e)
		{
			 show_tags_error("Saving...");
			 var var_element = this;
			 showoverlay();
			$.ajax({
				type: "POST",
				url: '/site_tag_admin_controller/save_site_data_grid/',
				async: true,
				data: {tag_data: JSON.stringify($(this).select2('data')), site_id: this.id, tag_type : 4},
				dataType: 'json',
				error: function(xhr, textStatus, error)
				{
					show_tags_error('Error 8329583: failed updating tags for site: '+$(this_element).data("site"));
					vl_show_jquery_ajax_error(xhr, textStatus, error);
					hideoverlay();
				},
				success: function(msg)
				{
					if(vl_is_ajax_call_success(msg))
					{
						var message="Saved";
						if (msg["message"] != undefined)
							message+=" - "+ msg["message"];
						show_tags_error(message);
					}
					else
					{
						show_tags_error(msg["errors"]);
					}
					hideoverlay();
				}
			});
			
		}); 
		if(stereo_array.length > 0) 
		{
			$('#'+stereo_select_name).select2('data', stereo_array);
		}
	}
}

function prepare_tag_view(msg)
{
	var html_string="<table border=0 class='table table-striped' width='100%'><tr><td width='30%' class='thdr'>Tag</td><td width='35%' class='thdr'>Premium Sites</td><td width='35%' class='thdr'>Bidder Sites</td></tr>";
	
	var site_data_combo_array=new Array();
	$("#sites-controls").html("");
	for (var i=0; i < msg.data.length; i++)
	{
		var site_id=msg.data[i]['site_id'];
		var url=msg.data[i]['url'];
		var tag=msg.data[i]['tag'];
		var type=msg.data[i]['type'];
		var tag_id=msg.data[i]['tag_id']; 
		var site_bidder_flag=msg.data[i]['is_bidder_only_flag']; 
		
		if (site_data_combo_array[tag] == undefined) 
		{
			site_data_combo_array[tag]=new Array();
			site_data_combo_array[tag]['data_premium']=new Array();
			site_data_combo_array[tag]['data_bidder']=new Array();
		}

 		site_data_combo_array[tag]['tag']=tag;
		site_data_combo_array[tag]['tag_id']=tag_id;
		site_data_combo_array[tag]['type']=type;
		if (site_id != undefined && site_id > 0 && site_bidder_flag == 1)
		{
			site_data_combo_array[tag]['data_bidder'].push({id:site_id, text:url});
		}
		else if (site_id != undefined && site_id > 0)
		{
			site_data_combo_array[tag]['data_premium'].push({id:site_id, text:url});
		}
	}
 
	for (var key in site_data_combo_array)
	{
		var tag_name=site_data_combo_array[key]['tag'];
		var tag_id=site_data_combo_array[key]['tag_id'];
		var tag_type=site_data_combo_array[key]['type'];
		var site_select_name_premium = tag_type+'-'+tag_id+'_prem';
		var site_select_name_bidder = tag_type+'-'+tag_id+'_bid';
	 	html_string += '<tr valign=top><td>'+tag_name+'</td><td class=site>'+build_tag_string(site_select_name_premium)+'</td><td class=site>'+build_tag_string(site_select_name_bidder)+'</td></tr>';
	}
	html_string += "</table>";
	$("#sites-controls").html(html_string);
	
	for (var key in site_data_combo_array)
	{
		var tag_name=site_data_combo_array[key]['tag'];
		var tag_id=site_data_combo_array[key]['tag_id'];
		var site_array=site_data_combo_array[key]['data_premium'];
		var tag_type=site_data_combo_array[key]['type'];
		var site_select_name_premium = tag_type+'-'+tag_id+'_prem';
		
	 	$('#'+site_select_name_premium).select2({
			tags: true,
			tokenSeparators: [',', ' '],
			tokenizer: function(input, selection, callback) 
			{
				if (input.indexOf(',') < 0 && input.indexOf(' ') < 0)
					return;
				var parts = input.split(/,| /);
				var data = [];
				for (var i = 0; i < parts.length; i++) 
				{
					var part = parts[i];
					part = part.trim();
					data.push({id: i-10000, text: part});
				}
				callback(data);
			},
			width: '900px',
			placeholder: "Select Sites",
			allowClear: true,
			minimumInputLength:0,
			ajax:{
				type: 'POST',
				url: '/site_tag_admin_controller/ajax_media_targeting_tags/',
				dataType: 'json',
				data: function (term,page){
					term = (typeof term === "undefined" || term == "") ? "%" : term;
					return {
						q: term,
						page_limit: 10,
						page: page,
						source : 0,
						bidder_flag: 0
					};
				},
				results: function(data)
				{
					return {results: data.result, more: data.more};
				}
			}
		})
		.on("change",function(e)
		{
		 	var this_element = this;
		 	var select2_data = JSON.stringify($(this).select2('data'));
		 	$(this).select2('data',"");
		 	showoverlay();
			$.ajax({
				type: "POST",
				url: '/site_tag_admin_controller/save_tag_data_grid/',
				async: true,
				data: {tag_data: select2_data, tag_id: this.id, bidder_flag: 0},
				dataType: 'json',
				error: function(xhr, textStatus, error)
				{
					show_tags_error('Error 8329583: failed updating tags for site: '+$(this_element).data("site"));
					vl_show_jquery_ajax_error(xhr, textStatus, error);
					hideoverlay();
				},
				success: function(msg)
				{
					if(vl_is_ajax_call_success(msg))
					{
						var message="Saved";
						if (msg.data["message"] != undefined)
							message= msg.data["message"];
						show_tags_error(message);
						
						var data_for_select2_refresh=new Array(); 
						var data_for_select2 = msg.data['options'];
						
						var j=0;
						if ( msg.data['options'] != undefined &&  msg.data['options'].length > 0) 
						{	
							$("#"+msg.tag_id).select2("data", msg.data['options']);
						}
						if (msg.data['not_found'] != undefined)
						{		 		
								var sitenames=""; 
								for (var keya in msg.data['not_found']) 
								{
									sitenames += msg.data['not_found'][keya]+ "\n";
								}
								if (sitenames.length > 1) 
								{
									var r = confirm("Do you want to add the following sites in our Sites list?\n Please be careful as these sites are not part of our list today.\n\n"+sitenames);
									if (r == true) 
									{// if user clicks confirm, make another call to create these sites in the freq_sites_main table and also link them to the id
									   $.ajax({
											type: "POST",
											url: '/site_tag_admin_controller/save_new_sites_bulk/',
											async: true,
											data: {sitenames: sitenames, tag_id: msg.tag_id},
											dataType: 'json',
											error: function(xhr, textStatus, error)
											{
												show_tags_error('Error 8329583: failed updating tags for site:');
												vl_show_jquery_ajax_error(xhr, textStatus, error);
											},
											success: function(msg)
											{
												if(vl_is_ajax_call_success(msg))
												{
													show_tags_error("New sites created and tagged");
													if ( msg.data != undefined &&  msg.data.length > 0) 
													{	
														$("#"+msg.tag_id).select2("data", msg.data);
													}
												}
												else
												{
													show_tags_error(msg["errors"]);
												}
											}
										});							
									}
								}
							}
					}
					else
					{
						show_tags_error(msg["errors"]);
					}
					hideoverlay();
				}
			});
		}); 
		if(site_array.length > 0 ) 
		{
			$('#'+site_select_name_premium).select2('data', site_array);
		}
	}


	//bidder section
	for (var key in site_data_combo_array)
	{
		var tag_name=site_data_combo_array[key]['tag'];
		var tag_id=site_data_combo_array[key]['tag_id'];
		var site_array=site_data_combo_array[key]['data_bidder'];
		var tag_type=site_data_combo_array[key]['type'];
		var site_select_name_bidder = tag_type+'-'+tag_id+'_bid';
		
	 	$('#'+site_select_name_bidder).select2({
			tags: true,
			tokenSeparators: [',', ' '],
			tokenizer: function(input, selection, callback) 
			{
				if (input.indexOf(',') < 0 && input.indexOf(' ') < 0)
					return;
				var parts = input.split(/,| /);
				var data = [];
				for (var i = 0; i < parts.length; i++) 
				{
					var part = parts[i];
					part = part.trim();
					data.push({id: i-10000, text: part});
				}
				callback(data);
			},
			width: '900px',
			placeholder: "Select Sites",
			allowClear: true,
			minimumInputLength:0,
			ajax:{
				type: 'POST',
				url: '/site_tag_admin_controller/ajax_media_targeting_tags/',
				dataType: 'json',
				data: function (term,page){
					term = (typeof term === "undefined" || term == "") ? "%" : term;
					return {
						q: term,
						page_limit: 10,
						page: page,
						source : 0,
						bidder_flag: 1
					};
				},
				results: function(data)
				{
					return {results: data.result, more: data.more};
				}
			}
		})
		.on("change",function(e)
		{
		 	var this_element = this;
		 	var select2_data = JSON.stringify($(this).select2('data'));
		 	$(this).select2('data',"");
		 	showoverlay();
			$.ajax({
				type: "POST",
				url: '/site_tag_admin_controller/save_tag_data_grid/',
				async: true,
				data: {tag_data: select2_data, tag_id: this.id, bidder_flag: 1},
				dataType: 'json',
				error: function(xhr, textStatus, error)
				{
					show_tags_error('Error 8329583: failed updating tags for site: '+$(this_element).data("site"));
					vl_show_jquery_ajax_error(xhr, textStatus, error);
					hideoverlay();
				},
				success: function(msg)
				{
					if(vl_is_ajax_call_success(msg))
					{
						var message="Saved";
						if (msg.data["message"] != undefined)
							message= msg.data["message"];
						show_tags_error(message);
						
						var data_for_select2_refresh=new Array(); 
						var data_for_select2 = msg.data['options'];
						
						var j=0;
						if ( msg.data['options'] != undefined &&  msg.data['options'].length > 0) 
						{	
							$("#"+msg.tag_id).select2("data", msg.data['options']);
						}
						if (msg.data['not_found'] != undefined)
						{		 		
								var sitenames=""; 
								for (var keya in msg.data['not_found']) 
								{
									sitenames += msg.data['not_found'][keya]+ "\n";
								}
								if (sitenames.length > 1) 
								{
									var r = confirm("Do you want to add the following sites in our Sites list?\n Please be careful as these sites are not part of our list today.\n\n"+sitenames);
									if (r == true) 
									{// if user clicks confirm, make another call to create these sites in the freq_sites_main table and also link them to the id
									   $.ajax({
											type: "POST",
											url: '/site_tag_admin_controller/save_new_sites_bulk/',
											async: true,
											data: {sitenames: sitenames, tag_id: msg.tag_id},
											dataType: 'json',
											error: function(xhr, textStatus, error)
											{
												show_tags_error('Error 8329583: failed updating tags for site:');
												vl_show_jquery_ajax_error(xhr, textStatus, error);
											},
											success: function(msg)
											{
												if(vl_is_ajax_call_success(msg))
												{
													show_tags_error("New sites created and tagged");
													if ( msg.data != undefined &&  msg.data.length > 0) 
													{	
														$("#"+msg.tag_id).select2("data", msg.data);
													}
												}
												else
												{
													show_tags_error(msg["errors"]);
												}
											}
										});							
									}
								}
							}
					}
					else
					{
						show_tags_error(msg["errors"]);
					}
					hideoverlay();
				}
			});
		}); 
		if(site_array.length > 0 ) 
		{
			$('#'+site_select_name_bidder).select2('data', site_array);
		}
	}
}

function paint_site_forms()
{
	var site_form;
	show_tags_error("Fetching data...");
	showoverlay();
	$.ajax({
		type: "POST",
		url: '/site_tag_admin_controller/get_data_grid/',
		async: true,
		data: {id_all: g_selected_sites},
		dataType: 'json',
		error: function(xhr, textStatus, error)
		{
			show_tags_error('Error 93220306: failed getting sitename and tags for site id: '+g_selected_sites[i]);
			vl_show_jquery_ajax_error(xhr, textStatus, error);
			hideoverlay();
		},
		success: function(msg)
		{
			if(vl_is_ajax_call_success(msg))
			{
				if (msg.mode == 'sites_only')
				{
					show_tags_error("Mode A - Site mode: Showing one row per Site");
					$("#sites-controls").append(prepare_site_view(msg));
				}
				else if (msg.mode == 'sites_and_stereos')
				{
					show_tags_error("Mode A - Site mode: Showing one row per Site for the sites entered above");
					$("#sites-controls").append(prepare_site_view(msg));
				} 
				else if (msg.mode == 'same_stereos')
				{
					show_tags_error("Mode B - Tag mode: Showing one row per tag");
					$("#sites-controls").append(prepare_tag_view(msg));
				}
				else if (msg.mode == 'diff_stereos')
				{
					show_tags_error("Mode C - Research mode: Showing one row per Site. These sites contain all the tags entered above");
					$("#sites-controls").append(prepare_site_view(msg));
				}
			}
			else
			{
				show_tags_error(msg["errors"]);
			}
			hideoverlay();
		}
	});
}

function build_tag_string(id)
{
	return '<input type="hidden" id="'+id+'">';
}

function show_tags_error(error_msg)
{
	error_msg="<b>"+error_msg+"</b>";
	window.clearTimeout(g_timeout_id);
	$("#tags_alert_div").html(error_msg);
	$("#tags_alert_div").show();
}

function loadtododata(queryname) 
{	
	showoverlay();
	$.ajax({
		type: "POST",
		url: '/site_tag_admin_controller/pull_todo_sitelist/',
		async: true,
		data: { name: queryname },
		dataType: 'json',
		error: function(xhr, textStatus, error)
		{
			show_tags_error('Error 4929356: failed getting sitepack select');
			hideoverlay();
		},
		success: function(msg)
		{
			if(vl_is_ajax_call_success(msg))
			{
				$("#search_tags_select").select2("data", msg.data);
				show_tags_error("Showing "+ msg.data.length + " results");
				sites_selected_go();
			}
			else
			{
				show_tags_error('Failed to get the TODO list');
			}
			hideoverlay();
		}
	});
	
}

//industry section start
function load_industries(industry_id) 
{
	if (industry_id == undefined)
	{
		industry_id=0;
	}
	showoverlay() ;
	$.ajax({
		type: "POST",
		url: '/site_tag_admin_controller/pull_industry_data/',
		async: true,
		data: {industry_id: industry_id},
		dataType: 'json',
		error: function(xhr, textStatus, error)
		{
			show_tags_error('Error 4929356: Failed to get industries');
			hideoverlay() ;
		},
		success: function(msg)
		{
			if(vl_is_ajax_call_success(msg))
			{
				if (msg == null || msg.data == null) 
				{
					$('#industry-table').html("No Industries found !!!");
				} 
				else 
				{
					write_in_industries_table(msg.data, industry_id);
				}
			}
			else
			{
				show_tags_error('Failed to get Industries');
			}
			hideoverlay() ;
		}
	});
}

function save_industry(id)
{
	var confirm_reply=true;
	var ind_name='industry_name_'+id;
	var industry_name = document.getElementById(ind_name).value;
	if (industry_name == "")
	{
		alert('Industry name cannot be blank');
		return;
	}
	if (id < 0)
	{
		confirm_reply = confirm("Please confirm that you want to create a new Industry. Industries can't be deleted after creation and will show up on RFP page!!");
	}
	if (confirm_reply == true) 
	{ 
		showoverlay() ;
			
	var ind_custom_name_f='industry_custom_name_f_'+id;
	var industry_custom_name_f = document.getElementById(ind_custom_name_f).value;	
	var iab_tags_name='iab_tags_'+id;
	var iab_tags = document.getElementById(iab_tags_name).value;	
	$.ajax({
		type: "POST",
		url: '/site_tag_admin_controller/save_industry_data/',
		async: true,
		data: {
			id: id,
			industry_name: industry_name,
			industry_custom_name_f: industry_custom_name_f,
			iab_tags: iab_tags
		},
		dataType: 'json',
		error: function(xhr, textStatus, error)
		{
			show_tags_error('Error 4929356: Failed to save industries');
			hideoverlay() ;
		},
		success: function(msg)
		{
			if(vl_is_ajax_call_success(msg))
			{
				if (msg != undefined && msg.data != undefined && msg.data.indexOf("Error#") == -1)
				{		if (id < 0)
						{
							load_industries();
							show_tags_error("Industry created. Now refreshing complete page");
						}
						else
						{
							load_industries(msg.data);
							show_tags_error("Industry data updated");
						}
						
				}
				else if (msg != undefined && msg.data != undefined && msg.data.indexOf("Error#") != -1)
				{
					alert(msg.data);
					show_tags_error(msg.data);
				}
			}
			else
			{
				show_tags_error('Failed to get Industries');
			}
			hideoverlay() ;
		}
	});
	}
}

function write_in_industries_table(data, industry_id_original) 
{
		var html_table= "<marquee>**WARNING: TO BE USED BY SUPER ADMINS ONLY **</marquee><br>"+
		"a: Industries can be Added or Updated. (For Industry deletions contact tech). b: Industry-Context tags can be linked."+
		"<br><table border=0 class='table table-striped' width='100%'><tr>"+
		"<td class='thdr'>ID</td><td class='thdr'><b>Industry Name</b></td>"+
		"<td class='thdr'>Custom Name:F</td><td class='thdr'>Created</td>"+
		"<td class='thdr'>Updated</td><td class='thdr'>Updated By</td><td class='thdr'>RFPs</td><td class='thdr'>Sites</td>"+
		"<td class='thdr'>Context tags</td><td class='thdr'>Save</td></tr>";
		var html_sub_table=0;
		for (var key=0; key < data.length; key++)
		{	
			var industry_custom_name_f = "";
			if (data[key]['industry_custom_name_f'] != null)
			{
				industry_custom_name_f = data[key]['industry_custom_name_f'];
			}

			var updated_date = "";
			if (data[key]['updated_date'] != null)
			{
				updated_date = data[key]['updated_date'];
			}

			var industry_id = data[key]['industry_id'];
			var save_button_text = "Update";
			if (industry_id < 0)
			{
				save_button_text = "Create";
			}
			var indu_id=data[key]['industry_id'];
			if (indu_id == '-9')
			{
				indu_id = "*New*";
			}
			html_table += "<tr id='tr_ind"+data[key]['industry_id']+"'>";
			html_sub_table = "<td>"+indu_id+"</td><td><input class='small-font' type='text' value='"+data[key]['industry_name']+"' id='industry_name_"+data[key]['industry_id']+"'></td>"+
			"<td><input class='small-font' type='text' value='"+industry_custom_name_f+"' id='industry_custom_name_f_"+data[key]['industry_id']+"'>"+
			"</td><td>"+data[key]['created_date']+"</td><td>"+updated_date+"</td><td>"+data[key]['updated_username']+"</td>"+
			"<td>"+data[key]['rfp_tag_count']+"</td><td>"+data[key]['sites_count']+"</td><td><input type='hidden' id='iab_tags_"+data[key]['industry_id']+"'></td>"+
			"<td><button type=button onclick=\"save_industry('"+data[key]['industry_id']+"')\" class=\"btn btn-success btn-mini\">"+save_button_text+"</button></td>";
			html_table += html_sub_table+ "</tr>";
			if (industry_id_original > 0)
			{
				var tr_name = '#tr_ind'+data[key]['industry_id'];
				$(tr_name).html(html_sub_table);
				break;
			}
		}
		html_table +="</table>";
		if (industry_id_original <= 0)
		{
			$('#industries-table').html(html_table);
		}	

		for (var key=0; key < data.length; key++)
		{
			var industry_iab_array=new Array();
			if (data[key]['tags'] != undefined && data[key]['tags'] != "")
			{
				var data_array = data[key]['tags'].split(",");
				
				for (var j=0; j < data_array.length; j++)
				{
					data_row_array=data_array[j].split("--");
					if (data_row_array[1] != "")
					{
						var sub_array=new Array();
						sub_array['id']=data_row_array[1];
						sub_array['text']=data_row_array[0];
						industry_iab_array[industry_iab_array.length]=sub_array;
					}
				}
			}
			var select2_name = '#iab_tags_'+data[key]['industry_id'];
		 	$(select2_name).select2({
			multiple: true,
			width: '300px',
			placeholder: "Select Contextuals",
			allowClear: true,
			minimumInputLength: 0,
				ajax:{
					type: 'POST',
					url: '/site_tag_admin_controller/ajax_media_targeting_tags/',
					dataType: 'json',
					data: function (term, page){
						term = (typeof term === "undefined" || term == "") ? "%" : term;
						return{
							q: term,
							page_limit: 10,
							page: page,
							source: '1'
						};
					},
					results: function(data){
						return {results: data.result, more: data.more};	
					}
					}
			}) ;
			if(industry_iab_array.length > 0) 
			{
				$(select2_name).select2('data', industry_iab_array);
			}
		}
}
//industry end

//headers_in_rfps_load
var highchart_data_rfp_count;
function headers_in_rfps_load()
{
	var headers_in_rfps_days_back=document.getElementById('headers_in_rfps_days_back').value;
	//var headers_in_rfps_days_back=30;
	showoverlay() ;
	$.ajax({
		type: "POST",
		url: '/site_tag_admin_controller/headers_in_rfps_load/',
		async: true,
		data: {headers_in_rfps_days_back: headers_in_rfps_days_back},
		dataType: 'json',
		error: function(xhr, textStatus, error)
		{
			show_tags_error('Error 4929356: Failed to get industries');
			hideoverlay() ;
		},
		success: function(msg)
		{
			if(vl_is_ajax_call_success(msg))
			{
				if (msg == null || msg.data == null) 
				{
					$('#industry-table').html("No Industries found !!!");
				} 
				else 
				{
					highchart_data_rfp_count=msg.data.data;
					var rfp_count=msg.data.total_count;
					highchart_for_rfp_headers(0);
					document.getElementById('container_headers_in_rfps_searchbox').innerHTML="Filter counts: <input style='width:50px;' onblur='highchart_for_rfp_headers(this.value)' type='text' id='headers_in_rfps_days_filter' value='0'>&nbsp;&nbsp;Total RFPs: "+ rfp_count;
				}
			}
			else
			{
				show_tags_error('Failed to get Industries');
			}
			hideoverlay() ;
		}
	});
}

function highchart_for_rfp_headers(filter) 
{
	var header_array=new Array();
	var data_array=new Array();
	var i=0;
	for (var key in highchart_data_rfp_count)
	{
		if (highchart_data_rfp_count[key] >= filter) 
		{
			header_array[i]=key;
			data_array[i]=highchart_data_rfp_count[key];
			i++;
		}
	}

    $('#container_headers_in_rfps_div').highcharts({
        chart: {
            type: 'column'
        },
        title: {
            text: 'Header count in RFPs'
        },
        subtitle: {
            text: ''
        },
        xAxis: {
            categories: header_array,
            crosshair: true
        },
        yAxis: {
            min: 0,
            title: {
                text: ''
            }
        },
        tooltip: {
            headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                '<td style="padding:0"><b>{point.y}</b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
        },
        plotOptions: {
            column: {
                pointPadding: 0,
                borderWidth: 0
            }
        },
        series: [{
            name: 'Headers',
            data: data_array

        }]
    });
};

//////////////// site section
var pendingptr=0;
var approvedptr=0;
var rejectedptr=0;
var ttdptr=0;
var limit=50;
var type1=0;
function load_sites(type, start) 
{
	showoverlay() ;
	 if (type == '-1') 
	 {
		 type=type1;
	 }
	 if (type == '-2') 
	 {
	 	show_tags_error('Please select a tab below to get started');
	 	$('#sites-table').html("Please select a tab to get started");
	 	hideoverlay() ;
	 	return;
	 }
	type1=type;

	if (start == undefined)
		 start = 0;

	search_text=document.getElementById('search_sites_text').value;
		 
	$.ajax({
		type: "POST",
		url: '/site_tag_admin_controller/pull_site_data/',
		async: true,
		data: { type: type, start: start, limit : limit, search_text: search_text},
		dataType: 'json',
		error: function(xhr, textStatus, error)
		{
			show_tags_error('Error 4929356: Failed to get sites');
			hideoverlay() ;
		},
		success: function(msg)
		{
			if(vl_is_ajax_call_success(msg))
			{
				if (msg == null || msg.data == null) 
				{
					pendingptr=0;
					approvedptr=0;
					rejectedptr=0;
					ttdptr=0;
					start=0;
					$('#sites-table').html("No Sites found !!!");
				} 
				else 
				{
					write_in_sites_table(msg.data, type);
				}
				show_tags_error("Showing sites from "+start + " to " + (start+ limit));
			}
			else
			{
				show_tags_error('Failed to get sites');
			}
			hideoverlay() ;
		}
	});
}

function fetch_next_batch() 
{
	var start = 50;
	if (type1 == 0) 
	{
		pendingptr+=limit;
		start=pendingptr;
	}
	else if (type1 == 1) 
	{
		approvedptr+=limit;
		start=approvedptr;
	}
	else if (type1 == 2) 
	{
		rejectedptr+=limit;
		start=rejectedptr;
	}
	else if (type1 == 3) 
	{
		ttdptr+=limit;
		start=ttdptr;
	}
	load_sites(type1, start);
}

$("#overlay").click(function() 
{
    return false;
});

function showoverlay() 
{
	if (document.getElementById('overlay') == undefined) 
	{
		$("body").append("<div id='overlay' style='background-color:#F2F0F2; opacity: 0.8;position:absolute;top:0;left:0;height:400%;width:100%;z-index:999'>Processing...</div>");
	}
}

function hideoverlay() 
{
	if (document.getElementById('overlay') != undefined) 
	{
		$("#overlay").remove();
	}
}

function site_status_change(tagname, url, status)
{
	showoverlay();
	if (status == 5)
		url= document.getElementById('bulk_ttd_update_flag').value;
	$.ajax({
		type: "POST",
		url: '/site_tag_admin_controller/site_status_change/',
		async: true,
		data: { url: url, status: status},
		dataType: 'json',
		error: function(xhr, textStatus, error)
		{
			show_tags_error('Error 4929356: failed to change the site status');
			hideoverlay() ;
		},
		success: function(msg)
		{
			if(vl_is_ajax_call_success(msg))
			{
				show_tags_error("Change saved");
				if (status != 5)
					document.getElementById(tagname+url).style.display="none";		
				else
				{
					load_sites(3, 0);
					show_tags_error('Sites tagged to TTD Only Successfully');		
				}	
			}
			else
			{
				show_tags_error('failed to change the site status');
			}
			hideoverlay() ;
		}
	});	
}

function write_in_sites_table(data, type) 
{
	if (type == 0) 
	{// pending
		var html_table="<table border=0 class='table table-striped' width='100%'><tr><td class='thdr'></td><td class='thdr'>Site</td><td class='thdr'>RTG only</td><td class='thdr'>Source</td><td class='thdr'><b>Impressions</b></td><td class='thdr'>Clicks</td><td class='thdr'>CTR%</td><td class='thdr'></td></tr>";
		for (var key in data)
		{
			html_table += "<tr id='td_p_"+data[key]['url']+"'><td></td><td><a href='http://"+data[key]['url']+"' target='_blank'>"+data[key]['url']+"</a></td><td>"+
			data[key]['is_retargeting_flag']+"</td><td>"+
			data[key]['source']+"</td><td>"+
			data[key]['f_impressions']+"</td><td>"+
			data[key]['clicks']+"</td><td>"+
			data[key]['ctr']+"</td><td >"+
			"<button type=button onclick=\"site_status_change('td_p_', '"+data[key]['url']+"', 1)\" class=\"btn btn-success btn-mini\">Approve</button>&nbsp;&nbsp;"+
			"<button type=button onclick=\"site_status_change('td_p_', '"+data[key]['url']+"', 2)\" class=\"btn btn-warning btn-mini\">Reject</button></td></tr>";
		}
		html_table +="</table>";
		$('#sites-table').html(html_table);
	} else if (type == 1) 
	{//approved
		var html_table="<table border=0 class='table table-striped' width='100%'><tr><td class='thdr'></td><td class='thdr'>Site</td><td class='thdr'>Manually Added</td><td class='thdr'></td></tr>";
		for (var key in data)
		{
			html_table += "<tr id='td_a_"+data[key]['url']+"'><td></td><td><a href='http://"+data[key]['url']+"' target='_blank'>"+data[key]['url']+"</a></td><td>"+
			data[key]['is_manually_added_flag']+"</td><td>"+
			"<button type=button onclick=\"site_status_change('td_a_','"+data[key]['url']+"', 3)\" class=\"btn btn-warning btn-mini\">Bidder Only</button>&nbsp;&nbsp;&nbsp;"+
			"<button type=button onclick=\"site_status_change('td_a_','"+data[key]['url']+"', 2)\" class=\"btn btn-danger btn-mini\">Reject</button></td></tr>";
		}
		html_table +="</table>";
		$('#sites-table').html(html_table);
	}  
	else if (type == 2) 
	{//rejected
		var html_table="<table border=0 class='table table-striped' width='100%'><tr><td class='thdr'></td><td class='thdr'>Site</td><td class='thdr'>Grey</td><td class='thdr'>Black</td><td class='thdr'></td></tr>";
		for (var key in data)
		{
			html_table += "<tr id='td_r_"+data[key]['url']+"'><td></td><td><a href='http://"+data[key]['url']+"' target='_blank'>"+data[key]['url']+"</a></td><td>"+
			data[key]['grey']+"</td><td>"+
			data[key]['black']+"</td><td>";
			if (data[key]['grey'] == '' && data[key]['black'] == '')
				html_table += "<button type=button onclick=\"site_status_change('td_r_', '"+data[key]['url']+"', 1)\" class=\"btn btn-success btn-mini\">Approve</button></td></tr>";
		}
		html_table +="</table>";
		$('#sites-table').html(html_table);
	} else if (type == 3) 
	{//ttd only
		var html_table="<table border=0 class='table table-striped' width='100%'><tr><td class='thdr'>Site</td><td class='thdr'></td></tr>";
		for (var key in data)
		{
			html_table += "<tr id='td_t_"+data[key]['url']+"'><td><a href='http://"+data[key]['url']+"' target='_blank'>"+data[key]['url']+"</a></td><td>";
			html_table += "<button type=button onclick=\"site_status_change('td_t_', '"+data[key]['url']+"', 4)\" class=\"btn btn-success btn-mini\">Remove Bidder flag</button></td></tr>";
		}
		html_table +="</table><br><br>"+
		"Update existing Approved sites with Bidder-Only Flag. One url per line<br><textarea rows=10 id='bulk_ttd_update_flag'></textarea>&nbsp;"+
		"<button type=button onclick=\"site_status_change(null, null, 5)\" class=\"btn btn-success btn-mini\">Add Bidder flag in bulk</button>";
		$('#sites-table').html(html_table);
	} 
}

</script>
</head>
<body>
	<div class="container-fluid" id="codiv">
		<div id="tags_alert_div" class="alert"></div>
		<!--1. Nav bar section - start  -->
		<ul id="tabs" class="nav nav-tabs">
			<li class="active"><a href="#todo_div" data-toggle="tab">To-Do's</a></li>
			<li><a href="#industry_div" data-toggle="tab" onclick="load_industries()">Industries</a></li>
			<li><a href="#headers_in_rfps_div" data-toggle="tab">Headers in RFPs</a></li>
			
			<li><a href="#site_div" data-toggle="tab" onclick="load_sites(-2, 0)">Sites</a></li>
			<li><a href="/siterank_controller" target='_blank'>SiteGen Algorithm</a></li>
			<li><a href="/siterank_controller?page_type=ttd_list" target='_blank'>SiteGen Algorithm for Bidder</a></li>

		</ul>
		<!--1. Nav bar section - end  -->
		<div id="dialog"></div>
		<div id="tabsContent" class="tab-content">
			<!--2. todos section - start  -->
			<div class="tab-pane fade in active" id="todo_div">
				<div class="row">
					<div class="span12">
	
	<?php
	$style2="background-color:#E6CE83;";
	$style1="background-color:#F1FCF0;";
	$todo_html="<table class='table table-condensed' cellspacing=2 cellpadding=2 ><tr><td style='".$style2."'><b>TODOs:</b></td>";
	$ctrr=0;
	foreach ($todo_array as $row)
	{
		if ($ctrr%2==0)
			$style=$style1;
		else
			$style=$style2;
		
		$todo_html.="<td style='".$style."'><a href='#' onclick='loadtododata(\"$row[0]\")'>".$row[0]."</a>";
		
		if (($row[2]+$row[1])>0)
			$todo_html.="<br> (Completed ".$row[1]." of ".($row[2]+$row[1]).") &nbsp;&nbsp;</td>";
		
		$ctrr++;
	}
	$todo_html.="</tr></table><br>";
	echo $todo_html;
	?>
		</div>
				</div>

				<table width='100%'>
					<tr>
						<td class="highlight"><input type="hidden" id="search_tags_select"
							class="my-select2-container span6 site-tag-multi-class js-example-basic-multiple">
							&nbsp;&nbsp;&nbsp;
							<button id="sites-selected-button"
								class="btn btn-success btn-mini" data-loading-text="Loading">
								Load</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<button id="sites-clear-button" class="btn btn-warning btn-mini"
								data-loading-text="Clear">Clear</button></td>
					</tr>
				</table>
				<br>

				<div id="sites-controls"></div>
			</div>
			<!--2. todos section - end  -->

			<!--2. site edit section - start  -->
			<div class="tab-pane fade" id="site_div">

				<ul id="tabs_site" class="nav nav-tabs">
					<li class="active"><a href="#pending_sites" data-toggle="tab" onclick="load_sites(0, 0)">Pending</a></li>
					<li><a href="#approved_sites" data-toggle="tab" onclick="load_sites(1, 0)">Approved</a></li>
					<li><a href="#rejected_sites" data-toggle="tab" onclick="load_sites(2, 0)">Rejected</a></li>
					<li><a href="#ttd_sites" data-toggle="tab" onclick="load_sites(3, 0)">Bidder Only</a></li>
				</ul>
				<div class="row pull-right">
					<div class="input-append">
						<div id="sites-find">
							<input class='input' type="text" id="search_sites_text"
								placeholder="Find sites..."> &nbsp;
							<button id="sites-src-button" class="btn"
								onclick="load_sites('-1', 0)" data-loading-text="Search">
								<i class="icon-search"></i>
							</button>
							&nbsp;
							<button id="sites-clear-button" class="btn btn-info"
								onclick="document.getElementById('search_sites_text').value='';load_sites('-1', 0)"
								data-loading-text="Clear">Clear</button>
						</div>
					</div>
				</div>

				<div id="sites-table"></div>
				<div class="row pull-right">
					<button onclick="fetch_next_batch()" class="btn btn-info btn-mini">Next
						>></button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<br> <br>
				</div>

			</div>
			<!--2. site edit section - end  -->


			<!--3. industry edit section - start  -->
			<div class="tab-pane fade" id="industry_div">

				<div id="industries-table"></div>

			</div>
			<!--3. industry edit section - end  -->

			<!--4. industry edit section - start  -->
			<div class="tab-pane fade" id="headers_in_rfps_div">
				Analyze RFP headers: Number of days back: <input type='text' id='headers_in_rfps_days_back' value='30'  style='width:50px;' >&nbsp;
				<button onclick="headers_in_rfps_load()" class="btn btn-info btn-mini">Run Report</button><br>
				<div id="container_headers_in_rfps_searchbox"></div>
				<div id="container_headers_in_rfps_div" style="min-width: 310px; height: 400px; margin: 0 auto"></div>

			</div>
			<!--4. industry edit section - end  -->



		</div>
	</div>
	<!-- main container fluid div end -->
 <?php
	$this->load->view('vl_platform_2/ui_core/js_error_handling');
	write_vl_platform_error_handlers_js();
	write_vl_platform_error_handlers_html_section();
	?>
</body>
</html>
<script src="http://code.highcharts.com/highcharts.js"></script>
<script src="http://code.highcharts.com/modules/exporting.js"></script>