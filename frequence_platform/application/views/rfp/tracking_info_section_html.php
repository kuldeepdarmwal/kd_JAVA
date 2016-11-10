<!-- Tracking section -->
<input type="hidden" id="old_source_table" name="old_source_table" value="<?php echo $old_source_table; ?>"/>
<input type="hidden" id="old_tracking_tag_file_id" name="old_tracking_tag_file_id" value="<?php echo $old_tracking_tag_file_id; ?>"/>
<input type="hidden" id="old_io_advertiser_id" name="old_io_advertiser_id" value="<?php echo $old_io_advertiser_id; ?>"/>
<div id="mpq_tracking_info_section" class="mpq_section_card card scrollspy">
	<h4 class="card-title grey-text text-darken-1 bold">Tracking</h4>
	<div class="card-content">
		<div id="tracking_tag_advertiser_warning">Please select an advertiser in the <a href="#mpq_advertiser_info_section">Opportunity section</a>.</div>
		<div id="tracking_tag" class="row" style="display: none;">
			<div class="input-field col s5">
				<input type="hidden" id="mpq_tracking_tag_file" name="mpq_tracking_tag_file" class="grey-text text-darken-1" length="255"/>
				<label for="s2id_mpq_tracking_tag_file" class="mpq_tracking_tag_label">Tracking Tag</label>
			</div>
			<div id="mpq_tracking_tag_new_container" class="input-field col s7">
				<div id="tracking_file_prepend_string" class="tracking_tag_file_appender_text" style="float: left;"></div>
				<div style="display: block;float: left;padding:0px;" class="col s4">
				    <input type="text" id="mpq_tracking_tag_new" name="mpq_tracking_tag_new" class="grey-text text-darken-1 input-field" length="255">
				    <label for="mpq_tracking_tag_new" class="" style="left: inherit;">New Tracking Tag</label>
				</div>    
				<div id="tracking_file_append_string" class="tracking_tag_file_appender_text" style="float: left;">.js</div>
			</div>
		</div>
		<div class="row">
			<div class="input-field col s9" style="padding-left: 0px;">
				<input type="checkbox" class="filled-in" id="include_retargeting" name="include_retargeting" <?php echo ($include_retargeting ? "checked" : ""); ?>/>
				<label for="include_retargeting">Include Retargeting</label>
			</div>
		</div>
	</div>
</div>