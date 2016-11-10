<div class="container container-body" style=""> 
	<h3>Geographic Targeting <small>Please use this section to specify the geography you would like to target</small><hr></h3>
	<div class="row-fluid">
		<div class="span12">
			<?php
				$zips_string = '';
				$zips = $geographics_section_data['zips'];
				$last_zip_index = count($zips) - 1;
				foreach($zips as $index=>$zip)
				{
					if($index != $last_zip_index)
					{
						$zips_string .= $zip.', ';
					}
					else
					{
						$zips_string .= $zip;
					}
				}
			?>
			<div style="display:none;" id="geo_error_div">
				<span class="span12 alert alert-error" id="geo_error_text"></span>
			</div>
			
			<div class="tabbable " >
				<ul class="nav nav-pills " id="geo_tabs">
				<?php if($custom_geos_enabled){ ?>
					<li id="custom_regions_pill" class=" active intro-chardin">
						<a href="#custom_region_list" data-toggle="tab" >Regions</a>
					</li>
				<?php } ?>
					<li id="radius_search_pill" class="<?php if(!$custom_geos_enabled){ echo 'active';  }?> intro-chardin" >
						<a href="#radius_search" data-toggle="tab" >Radius Search</a>
					</li>
					<li id="known_zips_pill" class="intro-chardin">
						<a href="#zip_list" data-toggle="tab" id="known_zips_tab_anchor">Known Zips</a>
					</li>

					<a href="#geo" id="geo_tab_help_button"><i class="icon-question-sign icon-large"></i></a>
				</ul>
			    
				<div class="tab-content">
				  <div class="tab-pane <?php if($custom_geos_enabled){ echo 'active';  }?>" id="custom_region_list">
					<div style="height:50px;" class="row-fluid">
					  <form class="form-inline">
						<div style="padding-right:4px;" class="span10"> <input class="span12" type="hidden" id="custom_regions_multiselect"></div>
						<a id="custom_regions_multiselect_load_button" class="btn btn-success"><i class="icon-map-marker icon-white"></i> Load Regions</a>
					  </form>
					</div>
				  </div>
				    
					<div class="tab-pane <?php if(!$custom_geos_enabled){ echo 'active';  }?>" id="radius_search">
						<div class="row-fluid">
							<div class="span12">

								<form class="form-inline">
									<input type="hidden" id="region_type" value="ZIP">
									Zips
									<!--select class="span1" id="region_type">
										<option value="ZIP" selected="selected">
											Zips
										</option>
										<option value="PLACE">
											Cities
										</option>
									</select-->
									&nbsp;within&nbsp;
									<div class="input-append">
										<div id="radius_span" class="intro-chardin"><input type="text" class="input-mini " placeholder="" id="radius" value="<?php echo $geo_radius; ?>" onclick="this.select();" />
										<span class="add-on"> miles</span></div>
									</div>
									&nbsp;of&nbsp;
									<input type="text" class="span7 intro-chardin" placeholder="" value="<?php echo $geo_center; ?>" id="address" onClick="this.select();" />
									<a class="btn btn-success" id="searchbut"><i class="icon-map-marker icon-white"></i> Search</a> <a href="#geo_search" id="geo_search_help_button"><i class="icon-question-sign icon-large"></i></a>
								</form>
							</div>
						</div>
					</div>
					<div class="tab-pane" id="zip_list">
						<form class="form-inline">
							<textarea id="set_zips" class="span10" rows="1" placeholder="type zips here (For Example: 94303, 94100, 93456...)" onClick="this.select();"><?php echo $zips_string; ?></textarea>
							<a class="btn btn-success" id="known_zips_load_button"><i class="icon-map-marker icon-white"></i> Load Zips</a>
						</form>
					</div>
				</div>
			    
			</div>
			<div style="width:100%;">
				<div style="position:relative;">
					<div id="map_loading_image">
						<img src="/images/mpq_v2_loader.gif" />
					</div>
					<div id="region-links" style="width:100%;height:750px;">
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row-fluid">
		<div class="span12">
			<div>
				<h3>
					Demographic Targeting <small>Please use this section to specify the demographics of the audience you would like to target <a href="#demo" id="demo_targeting_help_button"> <i class="icon-question-sign icon-large"></i></a></small>
					<hr>
				</h3>
			</div>
		<?php
			foreach($demographic_sections as $demographic_section)
			{
				$demographic_section->write_demographic_section();
			}
		?>
		</div>
	</div>

	<div class="row-fluid">
		<div class="span12">
			<div id="media_targeting_section" class="intro-chardin">
				<div class="row-fluid">
					<h3>
						Media Targeting <small>Please use this section to describe your offering and the interests of your audience <a href="#media" id="media_targeting_help_button"><i class="icon-question-sign icon-large"></i></a></small>
						<hr>
					</h3>
		
				</div>
				<div >
					<h4 class="muted">
						Contextual Channels
					</h4>
				</div>
				<div class="row-fluid">
					<?php
						foreach($channel_sections as $channel_section)
						{
							$channel_section->write_select_channel_section();
						}
					?>
				</div>
			</div>
		</div>
	</div>

	<div class="row-fluid" name="options">
		<div class="span12">
		</div>
	</div>

	<div class="row-fluid">
		<div class="span12">
			<div>
					<h3 id="options_header">
						<span id="header_title">Insertion Order <small>Please use this section to specify the final campaign parameters</small></span>
					<hr>
				</h3>
			</div>


			<div id="insertion_order_form">
				<div style="display:none;" class="alert alert-error mpq_error_box" id="insertion_order_form_error">
					<span id="insertion_order_form_error_text">&nbsp;</span>
				</div> 
				<div style="display:none;" class="alert alert-success" id="insertion_order_form_success">
					<button type="button" class="close" data-dismiss="alert">x</button>
					Insertion Order Submitted
				</div>
				<form class="form-horizontal">
					<div class="control-group">
						<div class="input-append">
							<input class="span2" id="impressions_box" style="width:200px;" type="text">
							<span class="add-on">impressions</span>
						</div> &nbsp;
						<select style="width:200px;" class="input" id="period_dropdown" onchange="period_change(this.value)" >
							<option value="MONTH_END">Monthly (Month End)</option>
							<option value="BROADCAST_MONTHLY">Monthly (Broadcast)</option>
							<option value="FIXED_TERM">In Total</option>
						</select>
					</div>
					<div class="control-group">
						<label class="control-label" for="start_date_input">Preferred Start Date</label>
						<div class="controls">
							<div id="datetimepicker3" class="input-append date">
								<input id="start_date_input" data-format="MM/dd/yyyy" data-type="text" type="text" value="<?php echo date('m/d/Y', mktime(0, 0, 0, date('m'), date('d')+3, date('Y'))); ?>"></input>
								<span class="add-on"><i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span>
							</div>
						</div>
					</div>
					<div id="flight_term_picker" class="control-group">
						<label class="control-label" for="flight_term">Flight Term</label>
						<div class="controls">
							<select id="flight_term" onchange="flight_term_change(this.value)">
								<option value="-1">Ongoing</option>
								<option value="1">1</option>
								<option value="2">2</option>
								<option value="3">3</option>
								<option value="4">4</option>
								<option value="5">5</option>
								<option value="6">6</option>
								<option value="7">7</option>
								<option value="8">8</option>
								<option value="9">9</option>
								<option value="10">10</option>
								<option value="11">11</option>
								<option value="12">12</option>
								<option value="0">Specify end date</option>
							</select>
							<span id="end_date_field"> Preferred End Date 
								<div id="datetimepicker2" class="input-append date">
									<input id="specify_end_date_box" data-format="MM/dd/yyyy" type="text" value="<?php echo date('m/d/Y', mktime(0, 0, 0, date('m')+1, date('d')+3, date('Y'))); ?>"></input>
									<span class="add-on"><i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span>
								</div>
							</span>
							<span id="literally_just_the_word_months"> months</span>
						</div>
					</div>
					<div id="end_date_picker" class="control-group" style="visibility:visible;">
						<label class="control-label" for="datetimepicker1">Preferred End Date</label>
						<div class="controls">
							<div id="datetimepicker1" class="input-append date">
								<input id="in_total_end_date_box" data-format="MM/dd/yyyy" type="text" value="<?php echo date('m/d/Y', mktime(0, 0, 0, date('m')+1, date('d')+3, date('Y'))); ?>"></input>
								<span class="add-on"><i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span>
							</div>
						</div>
					</div>
					<div style="display:none;" class="alert alert-error mpq_error_box" id="landing_page_form_error">
						<span id="landing_page_form_error_text">&nbsp;</span>
					</div> 
					<div class="control-group">
						<label class="control-label" for="landing_page_input">Campaign Landing Page</label>
						<div class="controls">
							<input type="text" id="landing_page_input">
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="retargeting_input">Includes Retargeting?</label>
						<div class="controls">
							<input type="checkbox" id="retargeting_input" checked="checked">
						</div>
					</div>
				</form>

				<div id="creative_new_request">
					<legend class="header_title">Creative</legend>

				  	<div class="handle_creative active">
				  		<p>Please upload any creative files needed for your campaign.</p>
				  	</div>

					<div class="control-group">
						<span class="btn btn-primary fileinput-button">
					        <i class="icon-file icon-white"></i>
					        <span>Add files...</span>
					        <!-- The file input field used as target for the file upload widget -->
					        <input id="file_upload" type="file" name="files[]" multiple />
					    </span>
					    <span style="position:relative;top:-8px;left:10px;">or drag-and-drop files here</span>
					    <br />
					    <!-- The container for the uploaded files -->
					    <div id="files" class="files">
					    </div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row-fluid">
		<div class="span12">
		</div>
	</div>

	<div style="padding-bottom:50px;" class="row-fluid">
	  <div class="span12">
		<h3>Notes <small>Use this section to add any custom instructions for your request</small></h3><hr>
		<textarea id="submission_notes_textarea" style="height:60px;width:100%;box-sizing:border-box;-moz-box-sizing:border-box;" placeholder="type anything here..." ></textarea>
	  </div>
	</div>

	<input id="advertiser_business_name_input" class="input-block-level" type="hidden" placeholder="Advertiser Business Name" value="<?php echo $user['business_name'];?>">
	<input id="advertiser_website_url_input" class="input-block-level" type="hidden" placeholder="www.advertiser.website.com" value="<?php echo $user['partner']['home_url'];?>">
	<input id="requester_name_input" class="input-block-level" type="hidden" placeholder="Firstname Lastname" value="<?php echo $user['firstname'] . " " . $user['lastname'];?>"/>
	<input id="role_dropdown" value="<?php echo $role;?>" type="hidden" />
	<input id="agency_website_input" class="input-block-level" type="hidden" placeholder="www.agency.website.com" value="<?php echo $user['partner']['home_url'];?>">
	<input id="requester_email_address_input" class="input-block-level" type="hidden" placeholder="your@email.here" value="<?php echo $user['email'];?>">
	<input id="requester_phone_number_input" class="input-block-level" type="hidden" placeholder="(YOUR)-PHONE-NUMBER" value="<?php echo $user['phone_number'];?>">
	<input type="hidden" name="requester_email" value="<?php echo $user['email']; ?>"/>
	<input type="hidden" name="requester_id" value="<?php echo $user['id']; ?>"/>

	<div class="row-fluid">
		<div class="span12 well well-large">
			<a id="insertion_order_submit_button" role="button" class="btn btn-large btn-block btn-primary" data-toggle="modal">
			  <i class="icon-thumbs-up icon-white"></i> Kick off campaign!</a>
		</div>
	</div>
</div> <!-- container -->
