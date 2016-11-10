<script type="text/javascript" src="/bootstrap/assets/js/bootstrap-datetimepicker.min.js"></script>

<?php
	write_commmon_mpq_javascript();
	write_misc_extras_javascript();
	write_geo_component_javascript($has_session_data, $mpq_id);
	write_demographic_component_javascript($demographic_elements);
	write_media_component_javascript();
	write_budget_options_component_javascript(
		$options_dollar_budget_defaults,
		$options_impression_budget_defaults,
		$has_session_data,
		$options_existing_data,
		$master_header_data['partner_mpq_can_submit_proposal'],
		$master_header_data['partner_mpq_can_see_rate_card']
	);
	write_insertion_order_component_javascript();
	write_submit_modal_box_javascript();
?>
