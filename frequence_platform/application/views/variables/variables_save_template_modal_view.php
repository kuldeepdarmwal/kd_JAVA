<?php
//build the dropdown string
$dropdown_string = '<select id="template_select" class="select_two" style="width:250px">
						<option value="save_as_new"> as new</option>';

if(!is_null($all_templates)){
	foreach($all_templates as $key => $val){
		$dropdown_string .= '<option value="'.$val['id'].'"> to: '.$val['friendly_name'].'</option>';
	}
}

$dropdown_string .= '</select>';

//print '<pre>'; print_r($dropdown_string); print '</pre>';

?>



<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
	<h3 id="myModalLabel">Save variables to template</h3>
</div>
<div class="modal-body">
	Save:
	<?php echo $dropdown_string; ?>
	<span id="new_template_name_span"><input type="text" id="new_template_name" placeholder="new template name here"></span>
</div>
<div class="modal-footer">
	<button class="btn" data-dismiss="modal" aria-hidden="true">Forget It</button>
	<button class="btn btn-primary" id="template_save_button_modal">Save changes</button>
</div>

<script>
$("#template_select").change(function() {
	if($(this).val()=="save_as_new"){
		$("#new_template_name_span").show();
	}else{
		$("#new_template_name_span").hide();
	}
});


$("#template_save_button_modal").click(function() {
	var formData = form2js('ui_control_form', '.', true, function(node){});
	$.ajax({
		type: "POST",
		url: '/variables/save_template',
		async: true,
		data: { t_id: $("#template_select").val() , t_name: $("#new_template_name").val(), variables_data: JSON.stringify(formData, null, '\t') , b_vers: $("#builder_version_select").val() },
		dataType: 'html',
		error: function(){
			alert('error');
			return 'error';
		},
		success: function(msg){ 
			$('#variables_modal').modal('hide');
			// m@@ might want to update some title on the main view to show the name of the 
		}
	});

});


</script>




