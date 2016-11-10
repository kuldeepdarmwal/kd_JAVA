<style type-"text/css">


#submittal_view_modal
{
	margin-left:0px;
	top:5%;
	width:80%;
	left:10%;
	right:10%;
}
select.campaign_select {
	width: 75px;
}
#campaign_submit {
  margin: 0;
}
#campaign_submit .modal-body {
  overflow-y: visible;
}
input[name="campaign_name"],
#advertiser_select,
#s2id_advertiser_select {
  width: 300px;
}
#submittal_view_modal_body
{
	max-height:550px;
}
#load_more_form_div
{
	padding-top:20px;
}
#load_more_button
{
	margin-bottom:10px;
}
#load_more_form_div input
{
	width:50px;
}
.action_column
{
	width:130px;
}
.tooltip.in
{
	opacity: 1;
	font-size: 13px;
}
.tooltip-inner
{
	max-width: 100%;
}
.geos_button
{
	position: relative;
}
.geos_rfp_button
{
	position: relative;
}
.geos_download
{
	display: none;
	position: absolute;
	left: 28px;
	top: -2px;
	width: 250px;
	background: white;
	padding: 10px;
	border-radius: 3px;
	box-shadow: 3px 3px 5px rgba(0,0,0,0.2);
	text-align: left;
}
.geos_rfp_download
{
	display: none;
	position: absolute;
	left: 28px;
	top: -2px;
	width: 120px;
	background: white;
	padding: 10px;
	border-radius: 3px;
	box-shadow: 3px 3px 5px rgba(0,0,0,0.2);
	text-align: left;
}
.geos_download assets{
	display:block;
}
</style>
<script src="/libraries/external/js/jquery.hoverintent.js"></script>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/json2/20140204/json2.min.js"></script>
<script type="text/javascript">
	
	function open_submit_notes_box(mpq_id)
	{
		$.ajax({
			url: '/mpq_v2/get_notes_view_for_get_all_mpqs',
			dataType: 'json',
			async: false,
			type: 'POST',
			data:{
				mpq_id: mpq_id
			},
			success: function(data)
			{
				if(data.is_success = true)
				{
					$('#submittal_view_modal_body').html(data.view_data);
					$('#submittal_view_modal').modal('show');
				}
			},
			error: function(data)
			{
				alert("Error retrieving notes data for mpq: "+String(mpq_id));
			},
		});
	}
</script>
