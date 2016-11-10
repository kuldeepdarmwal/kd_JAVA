<!DOCTYPE html>
<html lang="en">
<head>
<title>Report</title>

<script type="text/javascript" src="/js/jquery-1.8.2.js"></script>
<script type="text/javascript" src="/js/jquery-ui-1.9.1-full.custom/js/jquery-ui-1.9.1.custom.js"></script>
<script type="text/javascript" src="/bootstrap/assets/js/bootstrap.js"></script>
<script type="text/javascript" src="/bootstrap/assets/js/bootstrap-datetimepicker.min.js"></script>

<link ref="stylesheet" href="/js/jquery-ui-1.9.1-full.custom/css/test_custom_theme/jquery-ui-1.9.1.custom.css"></link>
<link rel="stylesheet" href="/bootstrap/assets/css/bootstrap.css"></link>
<link rel="stylesheet" href="/bootstrap/assets/css/bootstrap-responsive.css"></link>
<link rel="stylesheet" href="/bootstrap/assets/css/bootstrap-datetimepicker.min.css"></link>
<link href="/libraries/external/select2/select2.css" rel="stylesheet"/>
<script src="/libraries/external/select2/select2.js"></script>
<style>

html {
	background: #a1dbff; /* Old browsers */
}

body {
	position:relative;
	background: #a1dbff; /* Old browsers */
	background: -moz-linear-gradient(top, #f0f9ff 0%, #cbebff 47%, #a1dbff 100%); /* FF3.6+ */
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#f0f9ff), color-stop(47%,#cbebff), color-stop(100%,#a1dbff)); /* Chrome,Safari4+ */
	background: -webkit-linear-gradient(top, #f0f9ff 0%,#cbebff 47%,#a1dbff 100%); /* Chrome10+,Safari5.1+ */
	background: -o-linear-gradient(top, #f0f9ff 0%,#cbebff 47%,#a1dbff 100%); /* Opera 11.10+ */
	background: -ms-linear-gradient(top, #f0f9ff 0%,#cbebff 47%,#a1dbff 100%); /* IE10+ */
	background: linear-gradient(to bottom, #f0f9ff 0%,#cbebff 47%,#a1dbff 100%); /* W3C */
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#f0f9ff', endColorstr='#a1dbff',GradientType=0 ); /* IE6-9 */
}

.center_div {
	margin-left:auto;
	margin-right:auto;
	background-color:yellow; 
}

.sort_method_radio {
	width:12px;
}

.center_text {
	text-align:center;
}

.control_buttons button{
	width:130px;
}

.buttons_row {
	margin-top:10px;
	margin-bottom:10px;
}

.screen_shot_padding {
	padding:30px;
	background-color:#888888;
  border: 1px solid #000;
	-webkit-border-radius: 6px;
	-moz-border-radius: 6px;
	border-radius: 6px;
}

.screen_shot_border {
	padding:0px;
	background-color:#888888;
  border: 1px solid #000;
}

.main_page {
	background-color:#fff;
}

.status_button_approve_selected.btn,
.status_button_approve_selected.btn:link,
.status_button_approve_selected.btn:visited
{
	background: #9dd53a; /* Old browsers */
	/* IE9 SVG, needs conditional override of 'filter' to 'none' */
	background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iIzlkZDUzYSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjUwJSIgc3RvcC1jb2xvcj0iI2ExZDU0ZiIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjUxJSIgc3RvcC1jb2xvcj0iIzgwYzIxNyIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiM3Y2JjMGEiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
	background: -moz-linear-gradient(top,  #9dd53a 0%, #a1d54f 50%, #80c217 51%, #7cbc0a 100%); /* FF3.6+ */
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#9dd53a), color-stop(50%,#a1d54f), color-stop(51%,#80c217), color-stop(100%,#7cbc0a)); /* Chrome,Safari4+ */
	background: -webkit-linear-gradient(top,  #9dd53a 0%,#a1d54f 50%,#80c217 51%,#7cbc0a 100%); /* Chrome10+,Safari5.1+ */
	background: -o-linear-gradient(top,  #9dd53a 0%,#a1d54f 50%,#80c217 51%,#7cbc0a 100%); /* Opera 11.10+ */
	background: -ms-linear-gradient(top,  #9dd53a 0%,#a1d54f 50%,#80c217 51%,#7cbc0a 100%); /* IE10+ */
	background: linear-gradient(to bottom,  #9dd53a 0%,#a1d54f 50%,#80c217 51%,#7cbc0a 100%); /* W3C */
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#9dd53a', endColorstr='#7cbc0a',GradientType=0 ); /* IE6-8 */
}

.status_button_unset_selected.btn,
.status_button_unset_selected.btn:link,
.status_button_unset_selected.btn:visited
{
	color:#fff;
	background: #4c4c4c; /* Old browsers */
	/* IE9 SVG, needs conditional override of 'filter' to 'none' */
	background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iIzRjNGM0YyIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEyJSIgc3RvcC1jb2xvcj0iIzU5NTk1OSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjI1JSIgc3RvcC1jb2xvcj0iIzY2NjY2NiIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjM5JSIgc3RvcC1jb2xvcj0iIzQ3NDc0NyIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjUwJSIgc3RvcC1jb2xvcj0iIzJjMmMyYyIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjUxJSIgc3RvcC1jb2xvcj0iIzAwMDAwMCIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjYwJSIgc3RvcC1jb2xvcj0iIzExMTExMSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9Ijc2JSIgc3RvcC1jb2xvcj0iIzJiMmIyYiIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjkxJSIgc3RvcC1jb2xvcj0iIzFjMWMxYyIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiMxMzEzMTMiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
	background: -moz-linear-gradient(top,  #4c4c4c 0%, #595959 12%, #666666 25%, #474747 39%, #2c2c2c 50%, #000000 51%, #111111 60%, #2b2b2b 76%, #1c1c1c 91%, #131313 100%); /* FF3.6+ */
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#4c4c4c), color-stop(12%,#595959), color-stop(25%,#666666), color-stop(39%,#474747), color-stop(50%,#2c2c2c), color-stop(51%,#000000), color-stop(60%,#111111), color-stop(76%,#2b2b2b), color-stop(91%,#1c1c1c), color-stop(100%,#131313)); /* Chrome,Safari4+ */
	background: -webkit-linear-gradient(top,  #4c4c4c 0%,#595959 12%,#666666 25%,#474747 39%,#2c2c2c 50%,#000000 51%,#111111 60%,#2b2b2b 76%,#1c1c1c 91%,#131313 100%); /* Chrome10+,Safari5.1+ */
	background: -o-linear-gradient(top,  #4c4c4c 0%,#595959 12%,#666666 25%,#474747 39%,#2c2c2c 50%,#000000 51%,#111111 60%,#2b2b2b 76%,#1c1c1c 91%,#131313 100%); /* Opera 11.10+ */
	background: -ms-linear-gradient(top,  #4c4c4c 0%,#595959 12%,#666666 25%,#474747 39%,#2c2c2c 50%,#000000 51%,#111111 60%,#2b2b2b 76%,#1c1c1c 91%,#131313 100%); /* IE10+ */
	background: linear-gradient(to bottom,  #4c4c4c 0%,#595959 12%,#666666 25%,#474747 39%,#2c2c2c 50%,#000000 51%,#111111 60%,#2b2b2b 76%,#1c1c1c 91%,#131313 100%); /* W3C */
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#4c4c4c', endColorstr='#131313',GradientType=0 ); /* IE6-8 */
}

.status_button_reject_selected.btn,
.status_button_reject_selected.btn:link,
.status_button_reject_selected.btn:visited
{
	background: #ff474a; /* Old browsers */
	/* IE9 SVG, needs conditional override of 'filter' to 'none' */
	background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2ZmNDc0YSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjUwJSIgc3RvcC1jb2xvcj0iI2ZmN2E3YSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjUyJSIgc3RvcC1jb2xvcj0iI2ZmMWUyMiIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNmZjFlMjIiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
	background: -moz-linear-gradient(top,  #ff474a 0%, #ff7a7a 50%, #ff1e22 52%, #ff1e22 100%); /* FF3.6+ */
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#ff474a), color-stop(50%,#ff7a7a), color-stop(52%,#ff1e22), color-stop(100%,#ff1e22)); /* Chrome,Safari4+ */
	background: -webkit-linear-gradient(top,  #ff474a 0%,#ff7a7a 50%,#ff1e22 52%,#ff1e22 100%); /* Chrome10+,Safari5.1+ */
	background: -o-linear-gradient(top,  #ff474a 0%,#ff7a7a 50%,#ff1e22 52%,#ff1e22 100%); /* Opera 11.10+ */
	background: -ms-linear-gradient(top,  #ff474a 0%,#ff7a7a 50%,#ff1e22 52%,#ff1e22 100%); /* IE10+ */
	background: linear-gradient(to bottom,  #ff474a 0%,#ff7a7a 50%,#ff1e22 52%,#ff1e22 100%); /* W3C */
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ff474a', endColorstr='#ff1e22',GradientType=0 ); /* IE6-8 */
}

.page_header_control {
	background-color:#eeeeee;
	border:1px black solid;
	border-top:0px black solid;
	border-bottom:0px black solid;
}

#screen_shots_list {
	border:1px black solid;
  -webkit-border-radius: 6px;
     -moz-border-radius: 6px;
          border-radius: 6px;
}

#screen_shot_light_box_modal {
	position:fixed;
	top:0%;
	left:0%;
	margin-top:5%;
	margin-left:10%;
	margin-right:10%;
	width:80%;
	max-width:1000px;
	max-height:90%;
	height:90%;
}

#loading_image_bottom {
	position:absolute;
	bottom:300px;
	left:45%;
	display:none;
}

#loading_image {
	position:absolute;
	top:300px;
	left:45%;
	display:none;
}

#first_page_number, 
#first_page_number:link, 
#first_page_number:visited, 
#first_page_number:hover, 
#first_page_number:active, 
#last_page_number, 
#last_page_number:link, 
#last_page_number:visited, 
#last_page_number:hover, 
#last_page_number:active,
#first_page_number_bottom, 
#first_page_number_bottom:link, 
#first_page_number_bottom:visited, 
#first_page_number_bottom:hover, 
#first_page_number_bottom:active, 
#last_page_number_bottom, 
#last_page_number_bottom:link, 
#last_page_number_bottom:visited, 
#last_page_number_bottom:hover, 
#last_page_number_bottom:active {
	color:black;
}

#s2id_businesses_select_box {
    width: 285px;
}
#s2id_campaigns_select_box {
    width: 270px;
}
.empty_section {
	color: #ccc;
	display: block;
	text-align: center;
	margin-top: 166px;
}
</style>

<script type="text/javascript">
	var g_screen_shots_data = new Array();
	var g_screen_shots_list = new Array();
	var g_screen_shot_index = 0;
	var g_get_report_data_request_number = 0;
	var advertiser_id = 0;

	function start_loading_gif()
	{
		$("#loading_image").show();
	}

	function stop_loading_gif()
	{
		$("#loading_image").hide();
		$("#loading_image_bottom").hide();
	}

	// central function that gets the data according to the change_type and applies it to the page
	function update_page(change_type)
	{
		var advertiser_id = $("#businesses_select_box").val();
		var campaign_id = $("#campaigns_select_box").val();
		var start_date = $("#start_date_time_input").val();
		var end_date = $("#end_date_time_input").val();
		var status_filter = $("#status_select_box").val();
		var sort_method = $("#sort_method_select_box").val();
		var page_index = $("#current_page_input").val();
		var screen_shots_per_page = $("#screen_shots_per_page_input").val();

		refresh_screen_shots_data(advertiser_id, campaign_id, start_date, end_date, change_type, status_filter, sort_method, page_index, screen_shots_per_page);
	}

	// Retrieve the screen shots data from the server.  Apply the data to the page as necessary.
	function refresh_screen_shots_data(
		advertiser_id, 
		campaign_id, 
		start_date, 
		end_date, 
		value_changed,
		status_filter,
		sort_method, 
		page_index, 
		num_items_per_page
		)
	{
		g_get_report_data_request_number++;

		start_loading_gif();

		jQuery.ajax({
			asyn: true,
			type: "POST",
		data: { 
				request_id: ""+g_get_report_data_request_number,
				advertiser_id: advertiser_id,
				campaign_id: campaign_id,
				start_date: start_date,
				end_date: end_date,
				action: value_changed,
				status_filter: status_filter,
				sort_method: sort_method, 
				page_index: page_index, 
				num_items_per_page: num_items_per_page
			},
			url: "/screen_shot_approval/get_screen_shots_data",
			success: function(data, textStatus, jqXHR) {
				if(data.is_success == "true")
				{
					if(data.real_data.request_id == g_get_report_data_request_number)
					{
						apply_controls_data(
							value_changed, 
							data.real_data.total_num_screen_shots,
							data.real_data.total_num_screen_shot_pages
						);

						apply_screen_shots_data(
							data.real_data.screen_shots_data
						);
						stop_loading_gif();
					}
					else
					{
						//alert("Skip response request #: "+data.data.request_id+" awaiting request #: "+g_get_report_data_request_number);
						// Don't handle old requests
					}
				}
				else
				{
					stop_loading_gif();
					//alert("Error get_screen_shots_data(): 507232");
					if(typeof(data.errors) == "object")
					{
						$("#error_info").html("ERROR...<br />");
						for(var ii=0; ii<data.errors.length; ii++)
						{
							$("#error_info").append(data.errors[ii]);
							$("#error_info").append('<br />');
						}
					}
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
					$("#error_info").html(
						errorThrown+
						"<br />"+
						jqXHR.responseText
					);
					stop_loading_gif();
					//alert("Fatal error get_report_data(): 908437, "+errorThrown);
			},
			dataType: "json"
		});
	}

	// set the values of the header set of controls
	function apply_controls_data(value_changed, num_screen_shots, num_pages)
	{
		$("#total_screen_shots_value").html(num_screen_shots);
		$("#last_page_number").html(num_pages);
		$("#last_page_number_bottom").html(num_pages);
	}
	
	// show the screen shots in a tiled set
	function apply_screen_shots_data(screen_shots_data)
	{
		g_screen_shots_list = screen_shots_data;

		$("#screen_shots_list").html("");
		var count = screen_shots_data.length;
		if(!count)
		{
			var html = '<h2 class="empty_section">No screen shots available.</h3>';
			$("#screen_shots_list").html(html);
		}
		$.each(screen_shots_data, function(i, data)
		{
			var is_approved_select_string = '';
			var is_disapproved_select_string = '';
			var is_unreviewed_select_string = '';
			switch(data.is_approved)
			{
				case '0':
					is_disapproved_select_string = " status_button_reject_selected";
					break;
				case null:
					is_unreviewed_select_string = " status_button_unset_selected";
					break;
				case '1':
					is_approved_select_string = " status_button_approve_selected";
					break;
				default:
					alert("Error: Unrecognized status: "+data.is_approved);
			}

			$("#screen_shots_list").
			append(
				$(document.createElement("div"))
					.attr({
						"style": "width:300px;float:left;margin:10px;border:1px black solid;background-color:#f0f9ff;"
					})
					.append(
						$(document.createElement("div"))
							.attr({
								"class": "btn-group",
								"style": "width:100%;"
							})
							.append(
								$(document.createElement("a"))
									.attr({
										"id": "approve_button_"+i,
										"class": "btn"+is_approved_select_string,
										"style": "width:33%;padding:4px 0px;"
									})
									.text("Approve")
									.click(function(){
										approve_screen_shot(i);
									})
							)
							.append(
								$(document.createElement("a"))
									.attr({
										"id": "unset_button_"+i,
										"class": "btn"+is_unreviewed_select_string,
										"style": "width:33%;padding:4px 0px;"
									})
									.text("Unset")
									.click(function(){
										unset_screen_shot(i);
									})
							)
							.append(
								$(document.createElement("a"))
									.attr({
										"id": "disapprove_button_"+i,
										"class": "btn"+is_disapproved_select_string,
										"style": "width:33%;padding:4px 0px;"
									})
									.text("Reject")
									.click(function() {
										disapprove_screen_shot(i);
									})
							)
					)
					.append(
						$(document.createElement("div"))
							.attr({
								"class": "center_div",
								"style": "width:100%;height:300px;background-color:#dddddd;overflow:auto;border-bottom:1px black solid;"
							})
							.append(
								$(document.createElement("a"))
									.attr({
										"href" : "#",
										"id" : "screen_shot_button_"+i,
										"style": "width:100%;height:100%;"
									})
									.click(function() {
										update_light_box_data(i);
										$("#light_box_previous_action").text("");
										$("#screen_shot_light_box_modal").modal();
										return false;
									})
									.append(
										$(document.createElement("img"))
										.attr({
											"id" : "screen_shot_image_"+i,
											"src" : data.file_name,
											"style": "width:100%;"
										})
									)
							)
					)
					.append(
						$(document.createElement("div"))
							.attr({
								"style": "width:100%;"
							})
							.append(
								$(document.createElement("div"))
									.attr({
										"id": "url_text",
										"class": "center_text"
									})
									.text(data.base_url)
							)
							.append(
								$(document.createElement("div"))
									.attr({
										"id": "date_text",
										"class": "center_text"
									})
									.text(data.creation_date)
							)
							.append(
								$(document.createElement("div"))
									.attr({
										"id": "business_name_text",
										"class": "center_text"
									})
									.text(data.business_name)
							)
							.append(
								$(document.createElement("div"))
									.attr({
										"id": "campaign_name_text",
										"class": "center_text"
									})
									.text(data.campaign_name)
							)
					)
			)

		});
	}

	function update_screen_shot_status_on_server(screen_shot_id, is_approved)
	{
		var advertiser_id = $("#businesses_select_box").val();

		$.ajax(
			"/screen_shot_approval/set_screen_shot_approval", 
		{
			data: {
				id: screen_shot_id,
				is_approved: is_approved,
				advertiser_id: advertiser_id
			},
			success: function() {
				// alert("Success for updating screen shot status: "+is_approved);
			},
			error:function(jqXHR, textStatus, errorThrown) {
				// alert("Failed to update screen shot status");
			},
			type: "POST"
		});
	}

	function approve_screen_shot(index)
	{
		var screen_shot_object = g_screen_shots_list[index];
		screen_shot_object.is_approved = 1;

		$("#disapprove_button"+"_"+index).removeClass("status_button_reject_selected");
		$("#approve_button"+"_"+index).addClass("status_button_approve_selected");
		$("#unset_button"+"_"+index).removeClass("status_button_unset_selected");

		update_screen_shot_status_on_server(screen_shot_object.id, screen_shot_object.is_approved);
	}

	function disapprove_screen_shot(index)
	{
		var screen_shot_object = g_screen_shots_list[index];
		screen_shot_object.is_approved = 0;

		$("#disapprove_button"+"_"+index).addClass("status_button_reject_selected");
		$("#approve_button"+"_"+index).removeClass("status_button_approve_selected");
		$("#unset_button"+"_"+index).removeClass("status_button_unset_selected");

		update_screen_shot_status_on_server(screen_shot_object.id, screen_shot_object.is_approved);
	}

	function unset_screen_shot(index)
	{
		var screen_shot_object = g_screen_shots_list[index];
		screen_shot_object.is_approved = null;

		$("#disapprove_button"+"_"+index).removeClass("status_button_reject_selected");
		$("#approve_button"+"_"+index).removeClass("status_button_approve_selected");
		$("#unset_button"+"_"+index).addClass("status_button_unset_selected");

		update_screen_shot_status_on_server(screen_shot_object.id, screen_shot_object.is_approved);
	}

	function highlight_status_button(is_approved)
	{
		if(is_approved == 0)
		{
			$("#disapprove_button").addClass("status_button_reject_selected");
			$("#approve_button").removeClass("status_button_approve_selected");
			$("#unset_button").removeClass("status_button_unset_selected");
		}
		else if(is_approved == 1)
		{
			$("#disapprove_button").removeClass("status_button_reject_selected");
			$("#approve_button").addClass("status_button_approve_selected");
			$("#unset_button").removeClass("status_button_unset_selected");
		}
		else if(is_approved == null)
		{
			$("#disapprove_button").removeClass("status_button_reject_selected");
			$("#approve_button").removeClass("status_button_approve_selected");
			$("#unset_button").addClass("status_button_unset_selected");
		}
		else
		{
			alert("Unknown approval status: "+is_approved);
		}
	}

	function set_page_number(value)
	{
		$("#current_page_input").val(value);
		$("#current_page_input_bottom").val(value);
	}

	function change_businesses_selection()
	{
		set_page_number("1");
		update_page('change_advertiser');
	}
	function change_campaigns_selection()
	{
		set_page_number("1");
		update_page('change_campaign');
	}
	function change_start_date_input()
	{
		set_page_number("1");
		update_page('date');
	}
	function change_end_date_input()
	{
		set_page_number("1");
		update_page('date');
	}
	function change_status_select_box()
	{
		set_page_number("1");
		update_page('approval_status');
	}
	function change_screen_shots_per_page_input()
	{
		set_page_number("1");
		update_page('screen_shots_per_page');
	}
	function change_sort_method_select_box()
	{
		set_page_number("1");
		update_page('sort_method');
	}
	function change_current_page_input()
	{
		var new_page = $("#current_page_input").val();
		$("#current_page_input_bottom").val(new_page);
		update_page('current_page');
	}
	function change_current_page_input_bottom()
	{
		var new_page = $("#current_page_input_bottom").val();
		$("#current_page_input").val(new_page);
		update_page('current_page');
	}
	function change_previous_page_button()
	{
		var page_index = $("#current_page_input").val();
		page_index = parseInt(page_index);
		page_index	-= 1;
		if(page_index <= 0)
		{
			var last_page = $("#last_page_number").text();
			last_page = parseInt(last_page);
			page_index = last_page;
		}
		set_page_number(""+page_index);
		update_page('previous_page');
	}
	function change_next_page_button()
	{
		var page_index = $("#current_page_input").val();
		page_index = parseInt(page_index);
		page_index += 1;
		var last_page = $("#last_page_number").text();
		last_page = parseInt(last_page);
		if(page_index > last_page)
		{
			page_index = 1;
		}
		set_page_number(""+page_index);
		update_page('next_page');
	}
	function change_first_page_link()
	{
			var first_page = 1;
			var new_page = first_page;
			$("#current_page_input").val(new_page);
			update_page('current_page');
			$("#current_page_input_bottom").val(new_page);
			update_page('current_page');
			return false;
	}
	function change_last_page_link()
	{
			var last_page = $("#last_page_number").text();
			last_page = parseInt(last_page);
			var new_page = last_page;
			$("#current_page_input").val(new_page);
			update_page('current_page');
			$("#current_page_input_bottom").val(new_page);
			update_page('current_page');
			return false;
	}

	function update_light_box_data(index)
	{
		var screen_shot_data = g_screen_shots_list[index];

		$("#screen_shot_light_box_space").html("");
		$("#screen_shot_light_box_space").append(
			$(document.createElement("img"))
			.attr({
				"id" : "none",
				"src" : screen_shot_data.file_name,
				"style": "width:100%;"
			})
		);

		$("#light_box_business_name").text(screen_shot_data.business_name);
		$("#light_box_campaign_name").text(screen_shot_data.campaign_name);
		$("#light_box_date").text(screen_shot_data.creation_date);
		$("#light_box_url").text(screen_shot_data.base_url);
		$("#light_box_item_number").text(index+1);
		// var items_per_page = parseInt($("#screen_shots_per_page_input").val());
		$("#light_box_total").text(g_screen_shots_list.length);
		
		highlight_light_box_status_button(screen_shot_data.is_approved);
	}

	function highlight_light_box_status_button(is_approved)
	{
		$("#light_box_disapprove_screen_shot_button").removeClass("status_button_reject_selected");
		$("#light_box_approve_screen_shot_button").removeClass("status_button_approve_selected");
		$("#light_box_unset_screen_shot_button").removeClass("status_button_unset_selected");

		if(is_approved == 0)
		{
			$("#light_box_disapprove_screen_shot_button").addClass("status_button_reject_selected");
		}
		else if(is_approved == 1)
		{
			$("#light_box_approve_screen_shot_button").addClass("status_button_approve_selected");
		}
		else if(is_approved == null)
		{
			$("#light_box_unset_screen_shot_button").addClass("status_button_unset_selected");
		}
		else
		{
			alert("Unknown approval status: "+is_approved);
		}
	}

	function light_box_previous_screen_shot()
	{
		$("#light_box_previous_action").text("");

		var item_index = $("#light_box_item_number").text();
		item_index = parseInt(item_index);
		item_index	-= 1;
		if(item_index <= 0)
		{
			var last_item = $("#light_box_total").text();
			last_item = parseInt(last_item);
			item_index = last_item;
		}
		$("#light_box_item_number").text(item_index);

		item_index--; // make zero based
		update_light_box_data(item_index);
	}
	function light_box_next_screen_shot()
	{
		$("#light_box_previous_action").text("");

		var item_index = $("#light_box_item_number").text();
		item_index = parseInt(item_index);
		item_index += 1;
		var last_item = $("#light_box_total").text();
		last_item = parseInt(last_item);
		if(item_index > last_item)
		{
			item_index = 1;
		}
		$("#light_box_item_number").text(item_index);

		item_index--; // make zero based
		update_light_box_data(item_index);
	}

	function light_box_approve_screen_shot()
	{
		$("#light_box_disapprove_screen_shot_button").removeClass("status_button_reject_selected");
		$("#light_box_approve_screen_shot_button").addClass("status_button_approve_selected");
		$("#light_box_unset_screen_shot_button").removeClass("status_button_unset_selected");

		var item_index = $("#light_box_item_number").text();
		item_index = parseInt(item_index);
		item_index--; // make zero based
		approve_screen_shot(item_index);

		light_box_next_screen_shot();

		$("#light_box_previous_action").text("approved previous screenshot");
	}
	function light_box_unset_screen_shot()
	{
		$("#light_box_disapprove_screen_shot_button").removeClass("status_button_reject_selected");
		$("#light_box_approve_screen_shot_button").removeClass("status_button_approve_selected");
		$("#light_box_unset_screen_shot_button").addClass("status_button_unset_selected");

		var item_index = $("#light_box_item_number").text();
		item_index = parseInt(item_index);
		item_index--; // make zero based
		unset_screen_shot(item_index);

		light_box_next_screen_shot();

		$("#light_box_previous_action").text("unset previous screenshot");
	}
	function light_box_disapprove_screen_shot()
	{
		$("#light_box_disapprove_screen_shot_button").addClass("status_button_reject_selected");
		$("#light_box_approve_screen_shot_button").removeClass("status_button_approve_selected");
		$("#light_box_unset_screen_shot_button").removeClass("status_button_unset_selected");

		var item_index = $("#light_box_item_number").text();
		item_index = parseInt(item_index);
		item_index--; // make zero based
		disapprove_screen_shot(item_index);

		light_box_next_screen_shot();

		$("#light_box_previous_action").text("rejected previous screenshot");
	}

	function refresh_screen_shots()
	{
		update_page('refresh_screeh_shots');
	}
	$(function(){
		$("#status_select_box").on("change", change_status_select_box);
		$("#screen_shots_per_page_input").change(change_screen_shots_per_page_input);
		$("#refresh_page").click(refresh_screen_shots);
		$("#refresh_page_bottom").click(refresh_screen_shots);
		$("#sort_method_select_box").on("change", change_sort_method_select_box);
		$("#current_page_input").change(change_current_page_input);
		$("#previous_page_button").click(change_previous_page_button);
		$("#next_page_button").click(change_next_page_button);
		$("#first_page_number").click(change_first_page_link);
		$("#last_page_number").click(change_last_page_link);
		$("#current_page_input_bottom").change(change_current_page_input_bottom);
		$("#previous_page_button_bottom").click(change_previous_page_button);
		$("#next_page_button_bottom").click(change_next_page_button);
		$("#first_page_number_bottom").click(change_first_page_link);
		$("#last_page_number_bottom").click(change_last_page_link);
		$("#light_box_previous_screen_shot_button").click(light_box_previous_screen_shot);
		$("#light_box_approve_screen_shot_button").click(light_box_approve_screen_shot);
		$("#light_box_unset_screen_shot_button").click(light_box_unset_screen_shot);
		$("#light_box_disapprove_screen_shot_button").click(light_box_disapprove_screen_shot);
		$("#light_box_next_screen_shot_button").click(light_box_next_screen_shot);
		$("#start_date_time_div").datetimepicker({
			language: 'en',
			pick24HourFormat: true
		});
		$("#start_date_time_div").on('changeDate', function(e) {
			change_start_date_input();
		});
		$("#start_date_time_div").datetimepicker('setDate', new Date("<?php echo $start_date;?>")); // must be in the format: 'yyyy mm dd,hh:mm:ss'
		$("#end_date_time_div").datetimepicker({
			language: 'en',
			pick24HourFormat: true
		});
		$("#end_date_time_div").on('changeDate', function(e) {
			change_end_date_input();
		});
		$("#end_date_time_div").datetimepicker('setDate', new Date("<?php echo $end_date;?>")); // must be in the format: 'yyyy mm dd,hh:mm:ss'
	});
</script>
</head>

