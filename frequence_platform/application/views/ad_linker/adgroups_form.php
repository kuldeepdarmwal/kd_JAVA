<div class="container">
	<h2>Setup <small> Adgroups</small></h2>
	<div id="loader_bar" class="row-fluid" style="height: 15px;"></div>
	<div class="row-fluid">


<!-- ADVERTISER::CAMPAIGN -->
<!-- ADVERTISER::CAMPAIGN -->
<!-- ADVERTISER::CAMPAIGN -->
		<div class = "span5">
			<form>
				<fieldset>
					<legend >Campaign </legend>
	<!-- ADVERTISER NAME -->
					<label>Advertiser :: Campaign</label>
					<input id="campaign_select" class="span10" style="margin-left: 0px;"/>
				</fieldset>
			</form>
		</div><!-- span3 -->

<!-- ADGROUP -->
<!-- ADGROUP -->
<!-- ADGROUP -->
		<div id="adgroup_section" class="span7" style="visibility:hidden">
			<form>
				<fieldset>
					<legend >Ad Group <span class="help-inline pull-right" style="padding-right:20px"><button type="button"  id="adgroup_load_button" class="btn btn-success btn-mini" onclick="update_create_adgroup();" data-loading-text="Loading..."><i class="icon-thumbs-up icon-white"></i> <span>Update</span></button></span><span class="help-inline pull-right" id="load_adgroup_status"></span></legend>
	<!-- ADGROUP ID -->
					<label for="adgroup_select">Ext. Ad Group ID</label>
					<select id="adgroup_select" onchange=handle_ui_adgroup_change(this.value) >
					</select>
	<!-- NEW ADGROUP NAME -->
					<span id="new_adgroup_input" style="visibility:hidden">
						< type="text" placeholder="Name new adgroup" id="new_adgroup_ext_id" />
					</span>
	<!-- CITY -->
					<label for="adgroup_city">City <small class="help-inline">check spelling!</small></label>
					<input type="text" placeholder="Name city" id="adgroup_city" />
	<!-- REGION -->
					<label for="adgroup_region">Region <small class="help-inline">please spell full state name</small></label>
					<input type="text" placeholder="Name region" id="adgroup_region" />
	<!-- SOURCE -->
					<label for="dsp_source">Source <small class="help-inline">TD, FB, DBM, etc</small></label>
					<select type="text" id="dsp_source" required>
						<option value="">Please Select</option>
						<option value="TD">TD - The Trade Desk: display, pre-roll, custom</option>
						<option value="TDAV">TDAV - The Trade Desk: Ad Verify</option>
						<option value="TDGF">TDGF - The Trade Desk: Geo Fence</option>
						<option value="DBM">DBM - Google Double Click Bid Manager</option>
						<option value="DEMO">DEMO - Custom Demo Ad Group</option>
						<option value="FB">FB - Facebook</option>
						<option value="DFPBH">DFPBH - Bright House - Google Double Click for Publishers</option>
						<option value="NA">NA - Not applicable/Other</option>
					</select>
	<!-- TYPE -->
					<label for="adgroup_type">Target Type <small class="help-inline">PC, Mobile 320, Mobile No 320, Tablet, RTG, Custom</small></label>
					<select type="text" placeholder="" id="adgroup_type" >
						<option value="none">Please Select</option>
						<option value="PC">PC</option>
						<option value="Mobile 320">Mobile 320</option>
						<option value="Mobile No 320">Mobile No 320</option>
						<option value="Tablet">Tablet</option>
						<option value="RTG">RTG</option>
						<option value="Pre-Roll">Pre-Roll</option>
						<option value="RTG Pre-Roll">RTG Pre-Roll</option>
						<option value="Custom Averaged">Custom Averaged</option>
						<option value="Custom Ignored">Custom Ignored</option>
					</select>
	<!-- IS RTG? -->
					<br>
					<input type="checkbox" id="rtg_check"> Retargeting?
	<!-- ADGROUP TYPE -->				
					<label for="adgroup_select">Sub Product Type</label>
					<select id="subproduct_type_select" required>
					</select>
	<!-- UPDATE BUTTON -->
					<div id="geofencing_section" style="display: none; margin-top: 2em;">
						<span>Geo Fence Targeted Centers:</span>
						<span class="btn btn-danger btn-mini remove_all_centers_button" style="float: right;">Trash All <i class='icon-trash'></i></span>
						<span class="btn btn-success btn-mini bulk_add_centers_button" style="float: right; margin-right: 1em;">Bulk Add Centers <i class='icon-upload'></i></span>
						<div id="geofencing_bulk_upload_section" style="display: none;">
							<label for="geofencing_data">Format: <small class="help-inline">Latitude, Longitude, Radius (meters), Name, Address</small></label>
							<textarea id="geofencing_data" rows="10" style="width: 100%;font-size: 11px;" required></textarea>
						</div>
						<table class="table table-condensed" id="geofencing_data_table">
							<thead>
								<tr>
									<th>Latitude</th>
									<th>Longitude</th>
									<th>Radius<small>(m)</small></th>
									<th>Name</th>
									<th>Address</th>
									<th>Trash</th>
								</tr>
							</thead>
							<tbody></tbody>
						</table>
						<div class="geofence_single_add_button"><button class="btn btn-mini btn-success"><i class="icon-plus"></i></button></div>
					</div>

				</fieldset><!-- adgroups -->
			</form>
		</div>

	</div><!-- row -->
</div>

<script src="/bootstrap/assets/js/bootstrap-transition.js"></script>
<script src="/bootstrap/assets/js/bootstrap-alert.js"></script>
<script src="/bootstrap/assets/js/bootstrap-modal.js"></script>
<script src="/bootstrap/assets/js/bootstrap-dropdown.js"></script>
<script src="/bootstrap/assets/js/bootstrap-scrollspy.js"></script>
<script src="/bootstrap/assets/js/bootstrap-tab.js"></script>
<script src="/bootstrap/assets/js/bootstrap-tooltip.js"></script>
<script src="/bootstrap/assets/js/bootstrap-popover.js"></script>
<script src="/bootstrap/assets/js/bootstrap-button.js"></script>
<script src="/bootstrap/assets/js/bootstrap-collapse.js"></script>
<script src="/bootstrap/assets/js/bootstrap-carousel.js"></script>
<script src="/bootstrap/assets/js/bootstrap-typeahead.js"></script>

<script type="text/javascript">

	var timer;//this is used for the async loading bar
	var loader_off_secret;

	function handle_ui_campaign_change(c_id)
	{
		set_adgroup_status(null);
		handle_campaign_change(c_id);
	}

	function handle_ui_adgroup_change(ag_id)
	{
		set_adgroup_status(null);
		toggle_loader_bar(true, 2, 'prefill_adgroup_inputs');
		handle_ag_change(ag_id);
	}

	function handle_ag_change(ag_id)
	{
		//alert(ag_id);
		show_new_adgroup_input_box(false);
		if (ag_id == "new")
		{
			show_new_adgroup_input_box(true);
			clear_adgroup_inputs();
			handle_adgroup_source_change(null);
			toggle_loader_bar(false, null, 'prefill_adgroup_inputs');//handles the case of a new advertiser (i think?)
		}
		else
		{
			//build_campaign_select_dropdown(G_advertiser_id);
			toggle_loader_bar(true,0.1,'prefill_adgroup_inputs');
			clear_adgroup_inputs();
			prefill_adgroup_inputs(ag_id);
		}
		show_adgroup_section("visible");
	}

	function handle_adgroup_source_change(selected_adgroup_source)
	{
		if(selected_adgroup_source == 'TDGF')
		{
			$("#geofencing_section").show();
		}
		else
		{
			$("#geofencing_data").val("");
			$("#geofencing_data_table tbody").html("");
			$("#geofencing_section").hide();
		}
	}

	function prefill_adgroup_inputs(ag_id)
	{
		var data_url = "/adgroup_setup/get_adgroup_details/";
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: { ag_id: ag_id},
			dataType: 'json',
			error: function(jqXHR, textStatus, errorThrown)
			{
				set_adgroup_status('important', "error 834297, something went wrong" + errorThrown);
			},
			success: function(returned_data_array)
			{
				if(returned_data_array.is_success)
				{
					//preload fields
					document.getElementById("adgroup_city").value = returned_data_array.data.adgroup_details.City;
					document.getElementById("adgroup_region").value = returned_data_array.data.adgroup_details.Region;

					$('#dsp_source option[value=' + returned_data_array.data.adgroup_details.Source + ']').prop('selected', 'selected');
					if(returned_data_array.data.adgroup_details.Source === 'TDGF')
					{
						$("#geofencing_section").show();
						$("#geofencing_data_table tbody").html("");
						for(var i = 0; i < returned_data_array.data.geofencing_details.length; i++)
						{
							var geofence_data_row = returned_data_array.data.geofencing_details[i];
							var row = $("<tr></tr>");
							row.append("<td><input required type='number' data-geofence-center-id='" + geofence_data_row.id + "' data-geofence-center-type='latitude' class='existing_geofence_center' value='" + geofence_data_row.latitude + "' placeholder='Latitude' ></td>");
							row.append("<td><input required type='number' data-geofence-center-id='" + geofence_data_row.id + "' data-geofence-center-type='longitude' class='existing_geofence_center' value='" + geofence_data_row.longitude + "' placeholder='Longitude' ></td>");
							row.append("<td><input required type='number' data-geofence-center-id='" + geofence_data_row.id + "' data-geofence-center-type='radius' class='existing_geofence_center' value='" + geofence_data_row.radius + "' placeholder='Radius' ></td>");
							row.append("<td><input required type='text' data-geofence-center-id='" + geofence_data_row.id + "' data-geofence-center-type='name' class='existing_geofence_center' value='" + geofence_data_row.name + "' placeholder='Name' ></td>");
							row.append("<td><input required type='text' data-geofence-center-id='" + geofence_data_row.id + "' data-geofence-center-type='address' class='existing_geofence_center' value='" + geofence_data_row.address + "' placeholder='Address' ></td>");
							row.append("<td><i class='icon-remove-sign' data-geofence-center-id='" + geofence_data_row.id + "' ></i></td>");
							$("#geofencing_data_table tbody").append(row);
						}
					}
					else
					{
						$("#geofencing_section").hide();
						$("#geofencing_data_table tbody").html("");
					}
					$('#subproduct_type_select option[value=' + returned_data_array.data.adgroup_details.subproduct_type_id + ']').prop('selected', 'selected');
					document.getElementById("rtg_check").checked = returned_data_array.data.adgroup_details.IsRetargeting == 1 ? true : false;
					if(returned_data_array.data.adgroup_details.target_type !== null)
					{
						document.getElementById("adgroup_type").value = returned_data_array.data.adgroup_details.target_type;
					}
				}
				else
				{
					set_adgroup_status('important', "error 454489, something went wrong" + returned_data_array.errors.join(', '));
				}
			},
			complete: function()
			{
				toggle_loader_bar(false, null, 'prefill_adgroup_inputs');
			}
		});
	}

	function clear_adgroup_inputs()
	{
		document.getElementById("adgroup_city").value = '';
		document.getElementById("adgroup_region").value = '';
		$('#dsp_source option:eq(0)').prop('selected', 'selected');
		handle_adgroup_source_change(null);
		document.getElementById("rtg_check").checked = false;
		document.getElementById("adgroup_type").value = "none";
		document.getElementById("subproduct_type_select").value = "0";
	}

	function handle_campaign_change(c_id)
	{
		refresh_adgroup_selection(c_id);
		add_subproduct_type_dropdown();
		handle_ag_change("new");
	}

	function refresh_adgroup_selection(c_id, ag_id)
	{
		var data_url = "/adgroup_setup/get_vl_adgroups_dropdown/";
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: {c_id: c_id},
			dataType: 'html',
			error: function(){
				set_adgroup_status('important', "error 122584, error getting adgroups");
			},
			success: function(msg){
				document.getElementById("adgroup_select").innerHTML=msg;
				if(ag_id!=null)
				{
					document.getElementById("adgroup_select").value = ag_id;
				}
				handle_ag_change(document.getElementById("adgroup_select").value);
				toggle_loader_bar(false,null,'refresh_adgroup_dropdown');
			}
		});
	}
	
	function add_subproduct_type_dropdown()
	{
		var data_url = "/adgroup_setup/get_subproduct_type_dropdown/";
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			dataType: 'html',
			error: function(){
				set_adgroup_status('important', "error 122584, error getting adgroups");
			},
			success: function(data){
				document.getElementById("subproduct_type_select").innerHTML=data;
				if(data!=null)
				{
					document.getElementById("subproduct_type_select").innerHTML=data;
				}
				else
				{
					return false;
				}
			}
		});
	}

	function update_create_adgroup()
	{
		set_adgroup_status(null);

		switch(document.getElementById("adgroup_select").value)
		{
			case "new":
				if(document.getElementById("new_adgroup_ext_id").value === "")
				{
					alert('please specify ext adgroup id');
					document.getElementById("adgroup_select").value = "new";
				}
				else
				{
					if(document.getElementById("adgroup_type").options[document.getElementById("adgroup_type").selectedIndex].value == "none")
					{
						alert('Please select a target type for this adgroup');
						return;
					}
					if($('#dsp_source option:selected').val() === "")
					{
						alert('Please select a source for this adgroup');
						return;
					}
					if($('#subproduct_type_select option:selected').val() == 0)
					{
						alert('Please select a sub product type for this adgroup');
						return;
					}
					else if($('#dsp_source option:selected').val() === "TDGF" && !is_geofencing_form_data_valid())
					{
						alert('Please ensure that the geofence form is filled out properly and non-empty or that the bulk upload adgroup section is filled.');
						return;
					}
					toggle_loader_bar(true,0.7,'create_new_adgroup');
					create_new_adgroup(
						document.getElementById("campaign_select").value,
						document.getElementById("new_adgroup_ext_id").value,
						document.getElementById("adgroup_city").value,
						document.getElementById("adgroup_region").value,
						$('#dsp_source option:selected').val(),
						document.getElementById("rtg_check").checked,
						document.getElementById("adgroup_type").options[document.getElementById("adgroup_type").selectedIndex].value,
						$("#geofencing_data").val(),
						$('#subproduct_type_select option:selected').val(),
						get_geofencing_data_from_form()
					);
				}
				break;
			default:
				if(document.getElementById("adgroup_type").options[document.getElementById("adgroup_type").selectedIndex].value == "none")
				{
					alert('Please select a target type for this adgroup');
					return;
				}
				if($('#dsp_source option:selected').val() === "")
				{
					alert('Please select a source for this adgroup');
					return;
				}
				else if($('#dsp_source option:selected').val() === "TDGF" && $("#geofencing_data").val() === "")
				{
					alert('Please ensure that the geofence form is filled out properly and non-empty or that the bulk upload adgroup section is filled.');
					return;
				}
				toggle_loader_bar(true,0.5,'update_adgroup');
				update_adgroup(
					document.getElementById("adgroup_select").value,
					document.getElementById("adgroup_select").options[document.getElementById("adgroup_select").selectedIndex].text,
					document.getElementById("adgroup_city").value,
					document.getElementById("adgroup_region").value,
					$('#dsp_source option:selected').val(),
					document.getElementById("rtg_check").checked,
					document.getElementById("adgroup_type").options[document.getElementById("adgroup_type").selectedIndex].value,
					$("#geofencing_data").val(),
					$('#subproduct_type_select option:selected').val(),
					get_geofencing_data_from_form()
				);
		}
	}

	function update_adgroup(ag_id, ag_ext_id, ag_city, ag_region, ag_src, ag_is_rtg, ag_type, geofence_centers, ag_type_new, geofence_client_data)
	{
		var c_id = $('#campaign_select').val();
		var data_url = "/adgroup_setup/update_adgroup/";
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: {
				ag_id: ag_id,
				ag_ext_id: ag_ext_id,
				ag_city: ag_city,
				ag_region: ag_region,
				ag_src: ag_src,
				ag_is_rtg: ag_is_rtg,
				ag_type: ag_type,
				geofence_centers: geofence_centers,
				ag_type_new: ag_type_new,
				geofence_client_data: geofence_client_data
			},
			dataType: 'json',
			error: function(jqXHR, textStatus, errorThrown)
			{
				set_adgroup_status('important', "error 64209, something went wrong: " + errorThrown);
			},
			success: function(returned_data_array)
			{
				if(returned_data_array.is_success)
				{
					// refresh_adgroup_selection(c_id,returned_data_array.vl_id);
					$("#geofencing_data").val("");
					$("#geofencing_bulk_upload_section").hide();

					update_cycle_impression_cache_for_campaign(c_id);
					show_new_adgroup_input_box(false);
					set_adgroup_status('success', "adgroup updated");
					if(returned_data_array.data)
					{
						$("#geofencing_data_table tbody").html("");
						for(var i = 0; i < returned_data_array.data.length; i++)
						{
							var geofence_data_row = returned_data_array.data[i];
							var row = $("<tr></tr>");
							row.append("<td><input required type='number' data-geofence-center-id='" + geofence_data_row.id + "' data-geofence-center-type='latitude' class='existing_geofence_center' value='" + geofence_data_row.latitude + "' placeholder='Latitude' ></td>");
							row.append("<td><input required type='number' data-geofence-center-id='" + geofence_data_row.id + "' data-geofence-center-type='longitude' class='existing_geofence_center' value='" + geofence_data_row.longitude + "' placeholder='Longitude' ></td>");
							row.append("<td><input required type='number' data-geofence-center-id='" + geofence_data_row.id + "' data-geofence-center-type='radius' class='existing_geofence_center' value='" + geofence_data_row.radius + "' placeholder='Radius' ></td>");
							row.append("<td><input required type='text' data-geofence-center-id='" + geofence_data_row.id + "' data-geofence-center-type='name' class='existing_geofence_center' value='" + geofence_data_row.name + "' placeholder='Name' ></td>");
							row.append("<td><input required type='text' data-geofence-center-id='" + geofence_data_row.id + "' data-geofence-center-type='address' class='existing_geofence_center' value='" + geofence_data_row.address + "' placeholder='Address' ></td>");
							row.append("<td><i class='icon-remove-sign' data-geofence-center-id='" + geofence_data_row.id + "' ></i></td>");
							$("#geofencing_data_table tbody").append(row);
						}
					}
				}
				else
				{
					set_adgroup_status('important', "error 109879, something went wrong: " + returned_data_array.errors.join(", "));
				}
			},
			complete: function()
			{
				toggle_loader_bar(false, null, 'update_adgroup');
			}
		});
	}

	function create_new_adgroup(c_id, ag_ext_id, ag_city, ag_region, ag_src, ag_is_rtg, ag_type, geofence_centers, ag_type_new, geofence_client_data)
	{
		var data_url = "/adgroup_setup/create_new_adgroup/";
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: {
				c_id: c_id,
				ag_ext_id: ag_ext_id,
				ag_city: ag_city,
				ag_region: ag_region,
				ag_src: ag_src,
				ag_is_rtg:ag_is_rtg,
				ag_type: ag_type,
				ag_type_new: ag_type_new,
				geofence_centers: geofence_centers,
				geofence_client_data: geofence_client_data
			},
			dataType: 'json',
			error: function(jqXHR, textStatus, errorThrown)
			{
				set_adgroup_status('important', "error 5335, something went wrong: " + errorThrown);
			},
			success: function(returned_data_array)
			{
				if(returned_data_array.is_success)
				{
					refresh_adgroup_selection(c_id, returned_data_array.vl_id);
					update_all_impression_cache_for_campaign(c_id);
					show_new_adgroup_input_box(false);
					set_adgroup_status('success', "adgroup: " + returned_data_array.vl_id + " created");
					if(returned_data_array.data)
					{
						$("#geofencing_data_table tbody").html("");
						for(var i = 0; i < returned_data_array.data.length; i++)
						{
							var geofence_data_row = returned_data_array.data[i];
							var row = $("<tr></tr>");
							row.append("<td><input required type='number' data-geofence-center-id='" + geofence_data_row.id + "' data-geofence-center-type='latitude' class='existing_geofence_center' value='" + geofence_data_row.latitude + "' placeholder='Latitude' ></td>");
							row.append("<td><input required type='number' data-geofence-center-id='" + geofence_data_row.id + "' data-geofence-center-type='longitude' class='existing_geofence_center' value='" + geofence_data_row.longitude + "' placeholder='Longitude' ></td>");
							row.append("<td><input required type='number' data-geofence-center-id='" + geofence_data_row.id + "' data-geofence-center-type='radius' class='existing_geofence_center' value='" + geofence_data_row.radius + "' placeholder='Radius' ></td>");
							row.append("<td><input required type='text' data-geofence-center-id='" + geofence_data_row.id + "' data-geofence-center-type='name' class='existing_geofence_center' value='" + geofence_data_row.name + "' placeholder='Name' ></td>");
							row.append("<td><input required type='text' data-geofence-center-id='" + geofence_data_row.id + "' data-geofence-center-type='address' class='existing_geofence_center' value='" + geofence_data_row.address + "' placeholder='Address' ></td>");
							row.append("<td><i class='icon-remove-sign' data-geofence-center-id='" + geofence_data_row.id + "' ></i></td>");
							$("#geofencing_data_table tbody").append(row);
						}
					}
				}
				else
				{
					set_adgroup_status('important', "error 11242, something went wrong: " + returned_data_array.errors.join(", "));
				}
			},
			complete: function()
			{
				toggle_loader_bar(false, null, 'create_new_adgroup');
			}
		});
	}


	function set_adgroup_status(label, copy)
	{
		if(label === null)
		{
			document.getElementById("load_adgroup_status").innerHTML ='';
		}
		else
		{
			document.getElementById("load_adgroup_status").innerHTML = '<span class="label label-'+label+'">'+copy+'</span>';
		}
	}

	function toggle_loader_bar(is_on, expected_seconds_to_completion,this_off_secret)
	{
		if(is_on)
		{
			//clear_all_status();
			loader_off_secret = this_off_secret;
			$("#loader_bar").html('<div class="progress progress-striped active"><div class="bar" style="width: 100%;"></div></div>');
			run(expected_seconds_to_completion);
		}
		else if(this_off_secret == loader_off_secret)
		{
			$("#loader_bar").html('');
			stop_progress_timer();
		}
	}

	function run(expected_seconds_to_completion)
	{
		var time_increment_ms = 10; //mS between ticks
		var target_threshold = 0.9; //at the expected seconds to completion the width should be here
		var time_factor = (target_threshold*target_threshold)/expected_seconds_to_completion;
		var bar_width;

		var ii = 1;
		function myFunc() {
			timer = setTimeout(myFunc, time_increment_ms);
			ii++;
			bar_width = Math.min(Math.round(100*Math.sqrt(time_factor*time_increment_ms*ii/1000)-0.5),100)+'%';
			document.getElementById('loader_bar').style.width = bar_width;
			//document.getElementById("debug_spot").innerHTML = bar_width;
		}
		timer = setTimeout(myFunc, time_increment_ms);
	}

	function stop_progress_timer()
	{
		clearInterval(timer);
	}

	function show_new_adgroup_input_box(is_new)
	{
		if(is_new)
		{
			document.getElementById("new_adgroup_ext_id").value = '';
			document.getElementById("new_adgroup_input").style.visibility = "visible";
		}
		else
		{
			document.getElementById("new_adgroup_input").style.visibility = "hidden";
		}
	}

	function show_adgroup_section(vis)
	{
		document.getElementById("adgroup_section").style.visibility = vis;
	}

	function init()
	{
		//toggle_loader_bar(true,0.2,'refresh_advertiser_dropdown');
		//set_adgroup_status('warning', 'alert goes here')
	}

	function update_cycle_impression_cache_for_campaign(c_id)
	{
		if(c_id !== 'none')
		{
			$.ajax({
				type: "POST",
				url: "/campaigns_main/ajax_cache_single_campaign_cycle_impressions",
				async: true,
				data: {c_id: c_id},
				dataType: 'json',
				error: function(jqXHR, textStatus, errorThrown){
					set_adgroup_status('important', "error 58415.1, failed to cache cycle_impressions for selected adgroup's campaign");
				},
				success: function(data, textStatus, jqXHR){
					if(data.is_success !== undefined && data.is_success == true)
					{
						//return successfully
					}
					else
					{
						set_adgroup_status('warning', "Error caching impressions: " + data.errors);
					}
				}
			});
		}
	}

	function update_all_impression_cache_for_campaign(c_id)
	{
		if(c_id !== 'none')
		{
			$.ajax({
				type: "POST",
				url: "/campaigns_main/ajax_cache_single_campaign_impression_amounts",
				async: true,
				data: {c_id: c_id},
				dataType: 'json',
				error: function(jqXHR, textStatus, errorThrown){
					set_adgroup_status('important', "error 58415.2, failed to cache all impressions for selected adgroup's campaign");
				},
				success: function(data, textStatus, jqXHR){
					if(typeof data.is_success !== "undefined" && data.is_success == true)
					{
						//return successfully
					}
					else
					{
						set_adgroup_status('warning', "Error caching impressions: " + data.errors);
					}
				}
			});
		}
	}

	function is_geofencing_form_data_valid()
	{
		var form_is_valid = $("#geofencing_data_table tbody tr input").parents("tr").length > 0 || ($("#geofencing_bulk_upload_section").is(":visible") && $("#geofencing_data").val() !== "");

		$("#geofencing_data_table tbody tr input").parents("tr").each(function(){
			var latitude = $(this).find("input[data-geofence-center-type=\"latitude\"]").val();
			var longitude = $(this).find("input[data-geofence-center-type=\"longitude\"]").val();
			var radius = $(this).find("input[data-geofence-center-type=\"radius\"]").val();
			var name = $(this).find("input[data-geofence-center-type=\"name\"]").val();
			var address = $(this).find("input[data-geofence-center-type=\"address\"]").val();

			var row_is_valid = (validate_numbers(latitude) && validate_numbers(longitude) && validate_numbers(radius) && Boolean(name) && Boolean(address));
			form_is_valid = form_is_valid && row_is_valid;

			if(!row_is_valid) $(this).addClass("error");
			else $(this).removeClass("error");
		});

		return form_is_valid;
	}

	function validate_numbers(possible_number)
	{
		return !isNaN(possible_number - 0) && possible_number !== null && possible_number !== "" && possible_number !== false;
	}

	function get_geofencing_data_from_form()
	{
		var geofencing_data_object = {
			bulk_geofence_data: ($("#geofencing_bulk_upload_section").is(":visible")) ? $("#geofencing_data").val().trim() : "",
			geofence_form_data: []
		};

		$("#geofencing_data_table tbody tr input.new_geofence_center").parents("tr").each(function(){
			var geofence_point_object = {};
			geofence_point_object.id = null;
			geofence_point_object.latitude = parseFloat($(this).find("input[data-geofence-center-type=\"latitude\"]").val());
			geofence_point_object.longitude = parseFloat($(this).find("input[data-geofence-center-type=\"longitude\"]").val());
			geofence_point_object.radius = parseInt($(this).find("input[data-geofence-center-type=\"radius\"]").val());
			geofence_point_object.name = $(this).find("input[data-geofence-center-type=\"name\"]").val();
			geofence_point_object.address = $(this).find("input[data-geofence-center-type=\"address\"]").val();

			geofencing_data_object.geofence_form_data.push(geofence_point_object);
		});
		$("#geofencing_data_table tbody tr input.existing_geofence_center").parents("tr").each(function(){
			var geofence_point_object = {};
			geofence_point_object.id = $(this).find("input[data-geofence-center-type=\"latitude\"]").data("geofence-center-id");
			geofence_point_object.latitude = parseFloat($(this).find("input[data-geofence-center-type=\"latitude\"]").val());
			geofence_point_object.longitude = parseFloat($(this).find("input[data-geofence-center-type=\"longitude\"]").val());
			geofence_point_object.radius = parseInt($(this).find("input[data-geofence-center-type=\"radius\"]").val());
			geofence_point_object.name = $(this).find("input[data-geofence-center-type=\"name\"]").val();
			geofence_point_object.address = $(this).find("input[data-geofence-center-type=\"address\"]").val();

			geofencing_data_object.geofence_form_data.push(geofence_point_object);
		});

		return JSON.stringify(geofencing_data_object);
	}

	$(document).ready(function(){
		$('#campaign_select').select2({
			minimumInputLength: 0,
			placeholder: "Start typing to find active campaigns...",
			ajax: {
				url: "/adgroup_setup/get_active_campaigns/",
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
					return {results: data.results, more: data.more};
				}
			}
		});

		$('#campaign_select').on('change', function(){
			handle_ui_campaign_change($('#campaign_select').val());
		});

		$('#dsp_source').on('change', function(){
			handle_adgroup_source_change($('#dsp_source option:selected').val());
		});
	});

	$(document.body).on("click", "#geofencing_data_table td:last-child i", function(event){
		var replacement_html = "";
		if($(this).data("geofence-center-id"))
		{
			replacement_html = '<td colspan="6">' +
				'<div class="alert" style="margin:0; font-size: 12px; padding: 5px 35px 5px 14px;">' +
					'<strong>Warning!</strong> Saving will permanently delete any data associated with this center.' +
				'</div>'
			'</td>';
		}
		$(this).parents("tr").html(replacement_html);
	});

	$(".remove_all_centers_button").on("click", function(){
		$("#geofencing_data_table tbody td:last-child i").click();
	});

	$(".bulk_add_centers_button").on("click", function(){
		$("#geofencing_bulk_upload_section").toggle();
	});

	$(".geofence_single_add_button").on("click", function(event){
		event.preventDefault();
		var row = $("<tr></tr>");
		row.append("<td><input required type='number' data-geofence-center-id='' data-geofence-center-type='latitude' class='new_geofence_center' value='' placeholder='Latitude' ></td>");
		row.append("<td><input required type='number' data-geofence-center-id='' data-geofence-center-type='longitude' class='new_geofence_center' value='' placeholder='Longitude' ></td>");
		row.append("<td><input required type='number' data-geofence-center-id='' data-geofence-center-type='radius' class='new_geofence_center' value='' placeholder='Radius' ></td>");
		row.append("<td><input required type='text' data-geofence-center-id='' data-geofence-center-type='name' class='new_geofence_center' value='' placeholder='Name' ></td>");
		row.append("<td><input required type='text' data-geofence-center-id='' data-geofence-center-type='address' class='new_geofence_center' value='' placeholder='Address' ></td>");
		row.append("<td><i class='icon-remove-sign'></i></td>");
		$("#geofencing_data_table tbody").append(row);

		$("body").scrollTop($("#geofencing_data_table tbody tr:last").offset().top);
	})

	</script>
</body>

<script>
	window.onload = init();
</script>
</html>
