<div class="container container-body" style=""> 
	<h3>Geographic Targeting <small>Please use this section to specify the geography you would like to target</small><hr></h3>
	<div class="row-fluid">
		<div class="span12">
			<?php
				/*
				echo '
				<div>
					Debug code session_id: '.$session_id.'
				</div>
				';
				*/
			?>
			<?php
				//write_geo_component_geo_stats_summary_html();
				//write_geo_component_geo_region_summary_html();
				write_geo_component_search_html($geo_radius, $geo_center, $geographics_section_data,$custom_geos_enabled);
			?>
			<div style="width:100%;">
				<?php
					write_geo_component_map_html();
				?>
			</div>
		</div>
	</div>
	<?php
	write_demographic_component_html($demographic_sections);
	write_media_component_html($channel_sections);
	write_budget_options_component_html(
		$options_dollar_budget_defaults,
		$has_session_data,
		$master_header_data['partner_mpq_can_submit_proposal']
	);
	write_notes_component_html();
	write_submission_component_html();
	echo '</div> <!-- container -->';

	//set up variables so the modal box is set
	write_submit_modal_box_html($user_profile_data, $creative_requester, $industry_sections);
	 
	?>


<?php
	write_misc_extras_html();
?>


