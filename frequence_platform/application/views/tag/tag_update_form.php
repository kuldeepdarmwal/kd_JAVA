<style type="text/css">
	#update_form_tracking_tag_file_container
	{
		display:none;
		margin-top:10px;
		margin-bottom: 20px;
	}
	#new_update_form_tracking_file_status
	{
		display: none;
		font-weight:bold;
	}
</style>
<div class="row-fluid">
	<form name="update_form" action="/tag/update" method="post">
		<fieldset>
			<label>Tag <span class="label">last updated by: <?php  echo $tags[0]["username"]; ?></span><span style="color:#BD362F;font-size:11px;position: relative;float: right;top: -12px;">Tag ID : <?php echo $tags[0]["id"]; ?></span></label>
			<textarea name="newTag" id="new_tag"  rows="5" class="span12" ><?php echo htmlentities($tags[0]["tag_code"]); ?></textarea>
			<br/><br/>
			<span>
				<input style="position:relative;top:-4px;" type="radio" name="edit_td_tag_type" <?php if($tags[0]['tag_type'] == 0){ echo "checked=\"true\""; }?> value ="0" /> RTG Tag  
				&nbsp;&nbsp;<input style="position:relative;top:-4px;" type="radio" name="edit_td_tag_type" <?php if($tags[0]['tag_type'] == 2){ echo "checked=\"true\""; }?> value ="2" /> Conversion Tag
				&nbsp;&nbsp;<input style="position:relative;top:-4px;" type="radio" name="edit_td_tag_type" <?php if($tags[0]['tag_type'] == 3){ echo "checked=\"true\""; }?> value ="3" onclick="$('#update_form_tracking_tag_file').select2('val','');"> Custom Tag
				&nbsp;&nbsp; &nbsp; || &nbsp; &nbsp;<input style="position:relative;top:-4px;" type="radio" name="edit_td_tag_type" <?php if($tags[0]['tag_type'] == 1){ echo "checked=\"true\""; }?> value ="1" onclick="$('#update_form_tracking_tag_file').select2('val','');"> AdVerify Tag  				
			</span>
			<br/><br/>
			<div class="control-group">
				<label class="control-label" for="update_form_tracking_tag_file">Tracking Tag Filename</label>
				<div class="controls">
					<input type="hidden" name="update_form_tracking_tag_file" id="update_form_tracking_tag_file" class="span12"/>
				</div>
			</div>
			<br/><br/>
			<label>Updated by </label>
			<input type="text" name="username" value="<?php echo $username; ?>" /><br/>
			<input type="hidden" name="update_form_adv_id" id="update_form_adv_id" value="<?php  echo $tags[0]["advertiser_id"]; ?>"/>
		</fieldset>
		<?php echo form_hidden('tagID',$tags[0]["id"]); ?>
	</form>
</div>
<script>
	$('#update_form_tracking_tag_file').select2({
		placeholder: "Select Tracking Tag File",
		minimumInputLength: 0,
		multiple: false,
		ajax: {
			url: "/tag/get_select2_tracking_tag_file_names/",
			type: 'POST',
			dataType: 'json',
			data: function (term, page) {
				var td_tag_type = $('input[name=edit_td_tag_type]:checked').val();
				var adv_id = $("#update_form_adv_id").val();
				var source_table = "Advertisers";
				term = (typeof term === "undefined" || term == "") ? "%" : term;
				return {
					q: term,
					page_limit: 100,
					page: page,
					advertiser_id:adv_id,
					source_table:source_table,
					td_tag_type:td_tag_type,
					new_option:'no'
				};
			},results: function (data) {
				return {results: data.results, more: data.more};
			},error: function(jqXHR, textStatus, error){
				console.log(error);
			}                        
		}
	});

	var update_form_tracking_tag_file_select2_option = {'id':<?php  echo $tags[0]["tag_file_id"]; ?>,'text':'<?php  echo trim($tags[0]["name"]); ?>'};
	$('#update_form_tracking_tag_file').select2('data', update_form_tracking_tag_file_select2_option);

</script>