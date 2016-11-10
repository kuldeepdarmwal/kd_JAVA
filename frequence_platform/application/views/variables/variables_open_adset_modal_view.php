
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
	<h3 id="myModalLabel">Import variables from adset</h3>
</div>
<div class="modal-body" style="height:55px;">
<span class="span1">Adset: </span>
<input id="modal_adset_select" onchange="adset_open_is_adset(this.value);" class="span4">
</div>
<div class="modal-footer">
	<button class="btn" data-dismiss="modal" aria-hidden="true">Forget It</button>
	<button class="btn btn-primary" disabled="disabled" id="adset_open_button_modal">Import</button>
</div>

<script>
$("#adset_open_button_modal").click(function() {
	open_adset_variables($("#modal_adset_select").val());
});

function adset_open_is_adset(value)
{
	if(value !== undefined && value !== "")
	{
		$("#adset_open_button_modal").prop('disabled', false);
	}
	else
	{
		$("#adset_open_button_modal").prop('disabled', 'disabled');		
	}
}

</script>
