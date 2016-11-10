<body style="">
	<div class="container" style="padding-right:20px;">
		<div class="row page_header_control" style="margin-right:-30px;padding-top: 5px;">
			<div class="span12" style="margin-right:30px;">
				<div class="row">
					<div class="span3" style="">
						<input id="businesses_select_box">
					</div>
					<div class="span3" style="">
						<input id="campaigns_select_box">
					</div>
					<div class="span2">
						<div id="start_date_time_div" class="input-append date">
							<input id="start_date_time_input" data-format="MM/dd/yyyy hh:mm:ss" type=text style="width:80%;" placeholder="Start Date">
							</input>
							<span class="add-on">
								<i data-time-icon="icon-time" data-date-icon="icon-calendar">
								</i>
							</span>
						</div>
					</div>
					<div class="span2">
						<div id="end_date_time_div" class="input-append date">
							<input id="end_date_time_input" data-format="MM/dd/yyyy hh:mm:ss" type=text style="width:80%;" placeholder="End Date">
							</input>
							<span class="add-on">
								<i data-time-icon="icon-time" data-date-icon="icon-calendar">
								</i>
							</span>
						</div>
					</div>
					<div class="span2">
						<select id="status_select_box" style="width:100%;">
							<option value="unreviewed">Show Unreviewed</option>
							<option value="all">Show All</option>
							<option value="approved">Show Approved</option>
							<option value="disapproved">Show Rejected</option>
						</select>
					</div>
				</div>
				<div class="row">
					<div class="span2">
						<input id="screen_shots_per_page_input" type="text" maxlength="3" value="25" style="width:30px;position:relative;top:4px;"></input>
						per page
					</div>
					<div class="span2" style="margin-top: 10px;">
						<div id="total_screen_shots_value" style="display:inline;">0</div> total screen shots
					</div>
					<div class="span5" style="">
						<a id="previous_page_button" class="btn" style="width:100px;">Previous Page</a>
						<a href="#" id="first_page_number">1</a>...
						<input id="current_page_input" type="text" maxlength="3" value="1" style="width:30px;position:relative;top:4px;"></input>
						...<a href="#" id="last_page_number">1</a>
						<a id="next_page_button" class="btn" style="width:100px;">Next Page</a>
					</div>
					<div class="span3" style="position:relative;">
						<a id="refresh_page" class="btn" style="position:relative;top:6px;">Refresh Page</a>
						<select id="sort_method_select_box" style="width:50%;float:right;position:relative;top:6px;">
							<option value="url" selected>Sort by Url</option>
							<option value="business_name">Sort by Business Name</option>
							<option value="campaign_name">Sort by Campaign Name</option>
							<option value="date">Sort by Date</option>
							<option value="status">Sort by Status</option>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div style="clear:both;">
	</div>
	<div>
	</div>
	<div style="clear:both;">
	</div>
	<div id="screen_shots_list" class="row center_div" style="min-height:430px;float:left;margin-left:2%;maring-right:2%;background-color:#ffffff;width:96%;">
		<div style="clear:both;">
		</div>
	</div>
	<div style="clear:both;">
	</div>
	<div class="container">
		<div class="row page_header_control" style="margin-right:-30px;">
			<div class="span12" style="margin-right:30px;">
				<div class="row">
					<div class="span6 offset4">
						<a id="previous_page_button_bottom" class="btn" style="width:100px;">Previous Page</a>
						<a href="#" id="first_page_number_bottom">1</a>...
						<input id="current_page_input_bottom" type="text" maxlength="3" value="1" style="width:30px;position:relative;top:4px;"></input>
						...<a href="#" id="last_page_number_bottom">1</a>
						<a id="next_page_button_bottom" class="btn" style="width:100px;">Next Page</a>
					</div>
					<div class="span2">
						<a id="refresh_page_bottom" class="btn" style="position:relative;top:6px;">Refresh Page</a>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="span12">
				<div id="error_info" style="text-align:center;">
				</div>
			</div>
		</div>
	</div>
	<div id="screen_shot_light_box_modal" style="overflow:visible;" class="modal hide" style="">
		<div style="min-height:108px;height:18%;position:relative;background-color:#eeeeee;border-bottom:1px black solid;">
			<div style="text-align:center;">
				<span id="light_box_item_number">
					1
				</span>
				of
				<span id="light_box_total">
					3
				</span>
			</div>
			<div style="text-align:center;">
				<span id="light_box_url">
					blog.mercurynews.com
				</span>
				, 
				<span id="light_box_date">
					1/15/2012 00:00:00
				</span>
			</div>
			<div style="text-align:center;">
				<span id="light_box_business_name">
					Words Worth Inc	
				</span>
				, 
				<span id="light_box_campaign_name">
					Tree Trimming
				</span>
			</div>
			<div id="light_box_previous_action" style="text-align:center;">
			</div>
			<div style="position:absolute;bottom:0px;width:100%;">
				<div style="width:20%;float:left;">
					<a id="light_box_previous_screen_shot_button" class="btn" style="width:80%;">
						Previous Screen Shot
					</a>
				</div>
				<div style="width:60%;float:left;" class="">
					<div style="width:60%;float:left;margin-left:20%;maring-right:20%;" class="btn-group">
						<a id="light_box_approve_screen_shot_button" class="btn" style="width:25%;">
							Approve
						</a>
						<a id="light_box_unset_screen_shot_button" class="btn" style="width:25%;">
							Unset
						</a>
						<a id="light_box_disapprove_screen_shot_button" class="btn" style="width:25%;">
							Reject
						</a>
					</div>
				</div>
				<div style="width:20%;float:left;">
					<a id="light_box_next_screen_shot_button" class="btn" style="width:80%;float:right;">
						Next Screen Shot
					</a>
				</div>
			</div>
			<div style="clear:both;">
			</div>
		</div>
		<div style="height:82%;background-color:purple;overflow:auto;">
			<div id="screen_shot_light_box_space" style="width:100%;display:block;">
			</div>
		</div>
	</div>
	<div id="loading_image">
		<img src="/images/report_v2_loader.gif" width="127" height="126" />
	</div>
	<div id="loading_image_bottom">
		<img src="/images/report_v2_loader.gif" width="127" height="126" />
	</div>
</body>
<script>	
	$('#businesses_select_box').select2({
		placeholder: "Select an advertiser",
		minimumInputLength: 0,
		ajax: {
			url: "/screen_shot_approval/get_advertisers_details",
			type: 'POST',
			dataType: 'json',
			data: function (term, page) {
			term = (typeof term === "undefined" || term == "") ? "%" : term;
			return {
					q: term,
					page_limit: 100,
					page: page
				};
			},
			results: function (data) {
				return {results: data.results, more: data.more};
			}
		},
		allowClear: false,
		formatSelection: format_advertiser_cell,
		formatResult: format_advertiser_cell
	}).on("change", function(e){
		var data = $('#businesses_select_box').select2('data');
		var id = "";
		var text = "All campaign "+data.id_list;
		$("#campaigns_select_box").select2('data', {id: id, text: text, id_list: ""}); 
		change_businesses_selection();
	}); 
	
	$('#campaigns_select_box').select2({
		placeholder: "Select campaign",
		minimumInputLength: 0,
		ajax: {
			url: "/screen_shot_approval/get_campaign_details",
			type: 'POST',
			dataType: 'json',
			data: function (term, page) {
				term = (typeof term === "undefined" || term == "") ? "%" : term;
				return {
					q: term,
					page_limit: 100,
					page: page,
					advertiser_id: $("#businesses_select_box").val(),
				};
			},
			results: function (data) {
				return {results: data.results, more: data.more};
			}
		},
		allowClear: false,
		formatSelection: format_advertiser_cell,
		formatResult: format_advertiser_cell
	}).on("change", function(e){
		change_campaigns_selection()
	});

	function format_advertiser_cell(data)
	{
		var adv_html = '<div style="'+(data.id == "" ? "" : "")+'white-space:normal;">'+data.text+'<span>'+data.id_list+"</span>";                                        "</div>";
		return adv_html;
	}
</script>
</html>
