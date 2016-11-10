
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
	<h3 id="myModalLabel">Save variables to adset</h3>
</div>
<div class="modal-body" style="height:55px;">
<span class="span1">Adset: </span>
<input id="modal_adset_select" onchange="adset_save_is_adset(this.value);" class="span4">
</div>
<div class="modal-footer">
	<button class="btn" data-dismiss="modal" aria-hidden="true">Forget It</button>
	<button class="btn btn-primary" disabled="disabled" id="adset_save_button_modal">Save</button>
</div>

<script>
$("#adset_save_button_modal").click(function() {
 	save_adset_variables($("#modal_adset_select").val(),false);
});

function adset_save_is_adset(value)
{
	if(value !== undefined && value !== "")
	{
		$("#adset_save_button_modal").prop('disabled', false);
	}
	else
	{
		$("#adset_save_button_modal").prop('disabled', 'disabled');		
	}
}

</script>
