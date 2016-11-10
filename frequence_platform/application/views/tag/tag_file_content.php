<?php

if ($tags != NULL){
	$cnt = 0;
	
	foreach($tags as $row)
	{	
		//Don"t display tags which won"t have any advertiser association.
		if(!isset($row["tag_file_id"]) || $row["tag_file_id"] <= 0)
		{
			continue;
		}
		
		$tag_id = $row["id"];
		$tag_code = $row["tag_code"];
		$tag_class = "tag_file_content";
		$advertiser_id = $row["advertiser_id"];
		$is_active = $row["isActive"];
		
		if ($cnt === 0 && (count($tags)-1) === 0)
		{
			$tag_class = $tag_class." first_tag last_tag";
		}
		elseif ($cnt === 0)
		{
			$tag_class = $tag_class." first_tag";
		}
		elseif ($cnt === (count($tags) - 1))
		{
			$tag_class = $tag_class." last_tag";
		}
?>
		<div class="control-group <?php echo $tag_class; ?>">
			<div class="controls">
				<textarea id="<?php echo $tag_id; ?>_content_file_tag" name="<?php echo $tag_id; ?>_tag_code" rows="6" class="span7" style="resize: none;"><?php echo $tag_code; ?></textarea>
				<div class="tag_file_content_controls">
					<input class="rtg_radio" type="radio" id="<?php echo $tag_id; ?>_0_tag_type" name="<?php echo $tag_id; ?>_tag_type" value="0" <?php if($tags[$cnt]["tag_type"] == 0){ echo "checked=\"true\""; }?>> RTG
					&nbsp;<input class="conversion_radio" type="radio" id="<?php echo $tag_id; ?>_2_tag_type" name="<?php echo $tag_id; ?>_tag_type" value="2" <?php if($tags[$cnt]["tag_type"] == 2){ echo "checked=\"true\""; }?>>  Conversion
					&nbsp;<input class="custom_radio" type="radio" id="<?php echo $tag_id; ?>_3_tag_type" name="<?php echo $tag_id; ?>_tag_type" value="3" <?php if($tags[$cnt]["tag_type"] == 3){ echo "checked=\"true\""; }?>> Custom&nbsp;
					<span class="tag_id_display">Tag ID : <?php echo $tag_id; ?></span><br/>
					<button id="<?php echo $tag_id; ?>_save_btn" class="tag_file_content_tag_button_save_btn btn btn-primary" type="button" onclick="javascript:update_tag_type(<?php echo $tag_id;?>,<?php echo $advertiser_id; ?>)"><i class="icon-thumbs-up icon-white"></i> Save</button>
					<span id="<?php echo $tag_id; ?>_status" class="update_status label"></span>
					<span id="<?php echo $tag_id; ?>_deactivate_button" class="activate_deactivate <?php echo ( ($is_active == 1) ? "" : "hide_class" );?>"><a id="action_<?php echo $tag_id; ?>" class="btn btn-mini btn-danger" onclick="deactivate_tag(<?php echo $tag_id; ?>,<?php echo $advertiser_id; ?>,'tag_file_content');" title="Deactivate tag"><i class="icon-trash icon-white"></i></a></span>
					<span id="<?php echo $tag_id; ?>_activate_button" class="activate_deactivate <?php echo ( ($is_active == 1) ? "hide_class" : "" );?>"><a id="action_<?php echo $tag_id; ?>" class="btn btn-mini btn-success" onclick="activate_tag(<?php echo $tag_id; ?>,<?php echo $advertiser_id; ?>,'tag_file_content');" title="Activate tag"><i class="icon-play-circle icon-white"></i></a></span>
				</div>
			</div>
		</div>	
<?php
		$cnt++;
	} 
}