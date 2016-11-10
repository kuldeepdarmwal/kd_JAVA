<?php
//build the dropdown string
$dropdown_string = '<select id="template_select" class="select_two" style="width:250px">';

if(!is_null($all_templates)){
	foreach($all_templates as $key => $val){
		$dropdown_string .= '<option value="'.$val['id'].'">'.$val['friendly_name'].'</option>';
	}
}

$dropdown_string .= '</select>';

?>






<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
	<h3 id="myModalLabel">Import variables from template</h3>
</div>
<div class="modal-body">
	Open:
	<?php echo $dropdown_string; ?>
</div>
<div class="modal-footer">
	<button class="btn" data-dismiss="modal" aria-hidden="true">Forget It</button>
	<button class="btn btn-primary" id="template_open_button_modal">Import</button>
</div>

<script>



$("#template_open_button_modal").click(function() {
	open_template_variables($("#template_select").val());
});


</script>