	<!-- audience section -->
	<div id="mpq_audience_info_section" class="mpq_section_card card scrollspy">
		<h4 class="card-title grey-text text-darken-1 bold">Audience
<?php
		 if($is_rfp == true)
		 {
			 $audience_product_text = "";
			 foreach($products as $product)
			 {
				 if($product['is_audience_dependent'] == 1 && !$product['is_political'])
				 {
					 $audience_product_definition = json_decode($product['definition'], true);
					 if($audience_product_text !== "")
					 {
						 $audience_product_text .= ", ";
					 }
					 $audience_product_text .= ($audience_product_definition['first_name'] !== false) ? $audience_product_definition['first_name']." " : "";
					 $audience_product_text .= $audience_product_definition['last_name'];
				 }
			 }
			 echo '<div style="display:inline-block;font-weight:normal;font-size:1rem;margin-left:5%;">' . $audience_product_text . "</div>";
		 }
?>
		</h4>
		<div class="card-content">
		<?php
		if ($is_rfp) 
		{
			if($has_political && !empty($political_segment_data))
			{
		?>
			<div class="row rfp_political_display" id="political_segments" style="margin-bottom: 30px;">
			<?php
			foreach($political_segment_data as $category => $segments)
			{
				echo '
					<div class="row">
						<div class="col s1">&nbsp;</div>
						<div class="grey-text text-darken-1 col s2" style="font-weight:bold;">'.$category.'</div>
					</div>
					<div class="row">
						<div class="col s1">&nbsp;</div>';
				foreach($segments as $segment)
				{
					$is_checked = (bool) $segment['value'] ? "checked" : "";

					echo '
						<div class="col s2">
							<input type="checkbox" class="filled-in" id="political_segment_'.$segment['id'].'" '. $is_checked .'/>
							<label for="political_segment_'.$segment['id'].'">'.$segment['name'].'</label>
						</div>';
				}
				echo '</div><hr />';
			}
			?>
			</div>
		<?php
			}
		}
		?>
			<div class="row" id="demographic_inputs">
				<div class="col s1">&nbsp;</div>
				<?php
					foreach($demographic_sections as $demographic_section)
					{
						echo '
					 <div class="col s2">
						 <div class="grey-text text-darken-1" style="font-weight:bold;">'.$demographic_section->section_title.'</div>';
						foreach($demographic_section->demographic_elements as $checkbox)
						{
							$checked_attr = "";
							if($checkbox->is_checked)
							{
								$checked_attr = 'checked="checked"';
							}
							
							echo '
								<div>
									<input type="checkbox" class="filled-in" id="'.$checkbox->id_core_name.'_channel" '.$checked_attr.'/>
									<label for="'.$checkbox->id_core_name.'_channel">'.$checkbox->visible_name.'</label>
								</div>';
						}
						echo '</div>';
					 }
				?>
			</div>
			<div class="grey-text text-darken-1" style="font-weight:bold;">
                Audience Interests
			</div>
			<input type="hidden" style="width:100%;" id="iab_contextual_multiselect">
		</div>
		<div class="row">
			<div class="col s1">&nbsp;</div>
		</div>
	</div>
