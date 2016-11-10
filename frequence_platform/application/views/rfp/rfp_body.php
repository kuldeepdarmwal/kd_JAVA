<?php

function write_product_html($product, $definition, &$subtotal_array, $include_in_subtotal = true, $one_time = false, $is_preload = false)
{
	if($include_in_subtotal)
	{
		$after_discount_class = "";
		$product_calc_class = "product_budget_calc";
	}
	else
	{
	 	$after_discount_class = "after_discount";
		$product_calc_class = "product_budget_no_calc";
	}

	if($product['is_geo_dependent'])
	{
		$product_calc_class .= " product_budget_geo_dependent";
	}

	if($product['is_rooftops_dependent'])
	{
		$product_calc_class .= " product_budget_rooftops_dependent";
	}

	if($product['is_keywords_dependent'])
	{
		$product_calc_class .= " product_budget_keywords_dependent";
	}

	$product_display_text = "";
	if((array_key_exists('has_match', $product) && $product['has_match'] == false))
	{
		$product_display_text = 'style="display:none;"';
	}

	$budget_allocation_fixed_checked = '';
	$budget_allocation_per_pop_checked = 'checked="checked"';

	if(array_key_exists('allocation_method', $definition) && $definition['allocation_method'] == 'fixed')
	{
		$budget_allocation_fixed_checked = 'checked="checked"';
		$budget_allocation_per_pop_checked = '';
	}

	if($product['product_type'] == "cost_per_unit")
	{
	?>
	<li id="product_budget_<?php echo $product['id'];?>" class="display_collection_item" <?php echo $product_display_text; ?>>
		<div class="collapsible-header">
			<div class="row">
				<div class="col s2 targeted_image_col text-bold grey-text">
					<div class="img_title_section_container">
					<?php
					if($definition['product_enabled_img'] !== false)
					{
						echo '<img class="targeted_image" src="'.$definition['product_enabled_img'].'">';
					}
					else if($definition['product_disabled_img'] !== false)
					{
						echo '<img class="targeted_image" src="'.$definition['product_disabled_img'].'">';
					}
					echo '<div class="audience_img_title_section">';
					echo ($definition['first_name'] !== false) ? $definition['first_name']."<br>" : "";
					echo $definition['last_name'];
					echo "</div>";
					?>
					</div>
				</div>
				<div class="col s10">
					<div class="row">
						<?php
						foreach($definition['options'] as $option_id => $option)
						{
							$option_total = 0;
							$option_unit = 0;
							$option_cpm = 0;
							if($option['unit']['default'] !== false)
							{
								$option_unit = $option['unit']['default'];
							}
							if($option['cpm']['default'] !== false)
							{
								$option_cpm = $option['cpm']['default'];
							}

							if($option['type']['default'] == 'dollars')
							{
								$option_total = $option_unit;
							}
							else
							{
								$option_total = $option_cpm * $option_unit / 1000;
							}
							$subtotal_array[$option_id] += $option_total;
							$term_text = $one_time ? $one_time : '<span class="grey-text text-lighten-1 cp_term_text_' . $option_id . '">per month</span>';
						?>

						<div class="col s4 option_<?php echo $option_id; ?>_item">
							<span id="product_subtotal_option_<?php echo $option_id.'_'.$product['id']; ?>" class="grey-text product_subtotal <?php echo $product_calc_class; ?> product_impressions_dollar_budget" data-base-price="<?php echo $option_total;?>" data-option-id="<?php echo $option_id; ?>">$<?php echo number_format($option_total); ?></span> <span class="grey-text subtotal_small_text"><?php echo $term_text; ?></span>
						</div>

						<?php
						}
						?>
					</div>
				</div>
			</div>
		</div>

		<div class="collapsible-body">
			<div class="row">
				<div class="col s2">
					<div class="require_multi_location_placeholder">
					&nbsp;
					</div>
					<div class="require_multi_location">
						<div class="row" style="margin-bottom:10px;">
							<div class="col s11 offset-s1 grey-text text-darken-1 text-bold require_multi_location product_multi_location_text">
								Multi-Geo Budget Allocation
							</div>
						</div>
						<div class="row">
							<div class="col s11 offset-s1 require_multi_location">
								<input class="product_budget_allocation with-gap" name="budget_allocation_<?php echo $product['id']; ?>" type="radio" data-product-id="<?php echo $product['id']; ?>" value="per_pop" id="allocation_per_pop_<?php echo $product['id']; ?>" <?php echo $budget_allocation_per_pop_checked; ?>/>
								<label for="allocation_per_pop_<?php echo $product['id']; ?>">allocate budget per population</label>
							</div>
						</div>
						<div class="row">
							<div class="col s11 offset-s1 require_multi_location" style="margin-top:-1rem;">
								<input class="product_budget_allocation with-gap" name="budget_allocation_<?php echo $product['id']; ?>" type="radio"  data-product-id="<?php echo $product['id']; ?>" value="fixed" id="allocation_fixed_<?php echo $product['id']; ?>" <?php echo $budget_allocation_fixed_checked; ?>/>
								<label for="allocation_fixed_<?php echo $product['id']; ?>">fixed budget per geography</label>
							</div>
						</div>
					</div>
				</div>
				<div class="col s10">
				<?php
				foreach($definition['options'] as $option_id => $option)
				{
				?>

				<div class="col s4 option_<?php echo $option_id; ?>_item">
					<input id="unit_multiplier_<?php echo $option_id.'_'.$product['id'];?>" type="hidden" value="<?php echo (array_key_exists('unit_multiplier', $option) ? $option['unit_multiplier'] : '1000');?>">
					<div class="row">
						<div class="input-field col s4">
						<?php
						 $unit_value = $option['unit']['default'];

						echo '<input onchange="cost_per_unit_calc(\''.$option_id.'\',\''.$product['id'].'\', this);" id="unit_option_'.$option_id.'_'.$product['id'].'" type="text" class="grey-text product_input budget_slider_multiplier budget_impressions_dependent" data-input-type="unit" data-option-id="'.$option_id.'" data-cpm-switch="true" data-previous-calc-value="dollars" data-product-id="'.$product['id'].'" data-default-value="'.$unit_value.'" value="'.number_format($unit_value).'">';
						if($option['unit']['label'] !== false)
						{
							echo '<label for="unit_option_'.$option_id.'_'.$product['id'].'">'.$option['unit']['label'].'</label>';
						}
						?>
						</div>
						<div class="input-field col s7">
							<select onchange="cost_per_unit_calc('<?php echo $option_id; ?>', '<?php echo $product['id']; ?>', false);" id="type_option_<?php echo $option_id.'_'.$product['id']; ?>" class="io_period_dropdown grey-text" data-input-type="type" data-option-id="<?php echo $option_id; ?>" data-product-id="<?php echo $product['id']; ?>">
								<?php
								foreach($option['type']['dropdown_options'] as $dropdown_option)
								{
									$selected_string = "";
									if($dropdown_option['value'] == $option['type']['default'])
									{
										$selected_string = ' selected="selected"';
									}
									echo '<option'.$selected_string.' value="'.$dropdown_option["value"].'">'.$dropdown_option["text"].'</option>';
								}
							?>
						</select>
							</select>
						</div>
					</div>
					<div class="row">
						<div class="input-field col s4">
						<?php
				$cpm_disabled_text = "";
				$cpm_disabled_class= "";
				if(array_key_exists('cpm_editable', $definition) && $definition['cpm_editable'] == false)
				{
					$cpm_disabled_class = 'cpm_disabled';
					$cpm_disabled_text = 'disabled="disabled" ';
					if(array_key_exists('cpm_periods', $definition))
					{
						$cpm_disabled_text .= 'data-cpm-periods="'.htmlentities(json_encode($definition['cpm_periods'])).'" ';
					}
				}
				$temp_cpm = trim(trim(number_format($option['cpm']['default'], 4), '0'), '.');
				if($temp_cpm == "")
				{
					$temp_cpm = "0";
				}
				echo '<span class="discrete_dollar_prepend">$</span><input '.$cpm_disabled_text.'onchange="cost_per_unit_calc(\''.$option_id.'\',\''.$product['id'].'\', this);" id="cpm_option_'.$option_id.'_'.$product['id'].'" type="text" class="grey-text input_cpm product_input '.$cpm_disabled_class.'" data-option-id="'.$option_id.'" data-product-id="'.$product['id'].'" data-input-type="cpm" value="'.$temp_cpm.'">';
						if($option['cpm']['label'] !== false)
						{
							echo '<label for="cpm_option_'.$option_id.'_'.$product['id'].'">'.$option['cpm']['label'].'</label>';
						}
						?>
						</div>
					</div>
				</div>

				<?php
				}
				?>
					<?php if(array_key_exists('alert_text', $definition)) { ?>
					<div class="row">
						<div class="col s12 grey-text">
							<?php echo $definition['alert_text']; ?>
						</div>
					</div>
					<?php } ?>
				</div>

			</div>
		</div>
	</li>
	<?php
	}
	else if($product['product_type'] == "cost_per_inventory_unit")
	{
	?>
	<li id="product_budget_<?php echo $product['id'];?>" class="display_collection_item" <?php echo $product_display_text; ?>>
		<div class="collapsible-header">
			<div class="row">
				<div class="col s2 targeted_image_col text-bold grey-text">
					<div class="img_title_section_container">
					<?php
					if($definition['product_enabled_img'] !== false)
					{
						echo '<img class="targeted_image" src="'.$definition['product_enabled_img'].'">';
					}
					else if($definition['product_disabled_img'] !== false)
					{
						echo '<img class="targeted_image" src="'.$definition['product_disabled_img'].'">';
					}
					echo '<div class="audience_img_title_section">';
					echo ($definition['first_name'] !== false) ? $definition['first_name']."<br>" : "";
					echo $definition['last_name'];
					echo "</div>";

					?>
					</div>
				</div>
				<div class="col s10">
					<div class="row">
						<?php
						foreach($definition['options'] as $option_id => $option)
						{
							$option_total = 0;
							$option_unit = 0;
							$option_cpm = 0;
							$option_inventory = $definition['inventory']['dropdown_options'][0]['value'];
							if($option['unit']['default'] !== false)
							{
								$option_unit = $option['unit']['default'];
							}
							if($definition['inventory']['default'] !== false)
							{
								$option_inventory = $definition['inventory']['default'];
							}
							if($option['cpm']['default'] !== false)
							{
								$option_cpm = $option['cpm']['default'];
							}
							$option_total = $option_cpm * $option_unit / 1000;
							$option_total += $option_inventory;
							$subtotal_array[$option_id] += $option_total;
							$term_text = $one_time ? $one_time : '<span class="grey-text text-lighten-1 cp_term_text_' . $option_id . '">per month</span>';
						?>
						<div class="col s4 option_<?php echo $option_id; ?>_item">
							<span id="product_subtotal_option_<?php echo $option_id.'_'.$product['id']; ?>" class="grey-text product_subtotal <?php echo $product_calc_class; ?>" data-base-price="<?php echo round($option_total);?>" data-option-id="<?php echo $option_id; ?>">$<?php echo number_format($option_total)?></span> <span class="grey-text subtotal_small_text"><?php echo $term_text; ?></span>
						</div>
						<?php
						}
						?>
					</div>
				</div>
			</div>
		</div>
		<div class="collapsible-body">
			<div class="row">
				<div class="col s2">
					<div class="row" style="margin-left:0rem;margin-right:0rem;">
						<div class="input-field s12" style="width:90%; padding-left:5%; padding-right:5%;">
							<select onchange="cost_per_inventory_unit_calc(false, '<?php echo $product['id']; ?>', false, false);" id="inventory_option_<?php echo $product['id']; ?>" class="io_period_dropdown grey-text no_option_product_input" data-input-type="raw_inventory" data-product-id="<?php echo $product['id']; ?>">
							<?php
								foreach($definition['inventory']['dropdown_options'] as $dropdown_option)
								{
									$selected_string = "";
									$inventory_default = $definition['inventory']['default'];
									if(array_key_exists('raw_default', $definition['inventory']))
									{
										$inventory_default = $definition['inventory']['raw_default'];
									}
									if($dropdown_option['value'] == $inventory_default)
									{
										$selected_string = ' selected="selected"';
									}
									echo '<option'.$selected_string.' value="'.$dropdown_option["value"].'">'.$dropdown_option["text"].'</option>';
								}
							?>
							</select>
							<?php
							if($definition['inventory']['label'] !== false)
							{
								echo '<label for="inventory_option_'.$product['id'].'">'.$definition['inventory']['label'].'</label>';
							}
						?>
						</div>
					</div>
					<div class="row" style="margin-left:0rem;margin-right:0rem;">
						<div class="input-field s12" style="width:90%; padding-left:5%; padding-right: 5%;">
							<?php
								$inventory_base_input = $definition['inventory']['default'];
								if(array_key_exists('inventory_base_input_default', $definition['inventory']))
								{
									$inventory_base_input = $definition['inventory']['inventory_base_input_default'];
						   		}
								$inventory_base_input_label = 'Base Price';
								if(array_key_exists('inventory_base_input_label', $definition['inventory']))
								{
									$inventory_base_input_label = $definition['inventory']['inventory_base_input_label'];
								}
								echo '<span class="discrete_dollar_prepend">$</span><input onchange="cost_per_inventory_unit_calc(false, \''.$product['id'].'\', this, true);" id="base_dollars_option_'.$product['id'].'" type="text" class="input_cpm grey-text no_option_product_input" data-input-type="inventory" data-product-id="'.$product['id'].'" value="'.number_format($inventory_base_input).'">';
								echo '<label for="base_dollars_'.$product['id'].'">'.$inventory_base_input_label.'</label>';
							 ?>
						</div>
					</div>
				</div>
				<div class="col s10">
				<?php
				foreach($definition['options'] as $option_id => $option)
				{
				?>
				<div class="col s4 option_<?php echo $option_id; ?>_item">
					<div class="row">
						<div class="col s6 input-field">
							<select onchange="cost_per_inventory_unit_calc('<?php echo $option_id; ?>', '<?php echo $product['id']; ?>', false, false);" id="unit_option_<?php echo $option_id.'_'.$product['id']; ?>" class="io_period_dropdown grey-text product_input" data-input-type="unit" data-option-id="<?php echo $option_id; ?>" data-product-id="<?php echo $product['id']; ?>">
							<?php
								foreach($option['unit']['dropdown_options'] as $dropdown_option)
								{
									$selected_string = "";
									if($dropdown_option['value'] == $option['unit']['default'])
									{
										$selected_string = ' selected="selected"';
									}
									echo '<option'.$selected_string.' value="'.$dropdown_option["value"].'">'.$dropdown_option["text"].'</option>';
								}
							?>
						</select>
						<?php
							if($option['unit']['label'] !== false)
							{
								echo '<label for="unit_option_'.$option_id.'_'.$product['id'].'">'.$option['unit']['label'].'</label>';
							}
						?>

						</div>
						<div class="col s6" style="margin-top:2rem;">
						<?php
						if(array_key_exists('description_html', $option['unit']))
						{
							echo $option['unit']['description_html'];
						}
						else
						{
						?>
							Smart ad imprs<span class="cp_term_text_<?php echo $option_id;?>"> per month</span> per rooftop
						<?php
						}
						?>
						</div>
					</div>
					<div class="row">
						<div class="col s4 input-field">
							<?php
							$temp_cpm = trim(trim(number_format($option['cpm']['default'], 4), '0'), '.');
							if($temp_cpm == "")
							{
								$temp_cpm = "0";
							}
							echo '<span class="discrete_dollar_prepend">$</span><input onchange="cost_per_inventory_unit_calc(\''.$option_id.'\', \''.$product['id'].'\', this, false);" id="cpm_option_'.$option_id.'_'.$product['id'].'" type="text" class="input_cpm grey-text product_input" data-input-type="cpm" data-option-id="'.$option_id.'" data-product-id="'.$product['id'].'" value="'.$temp_cpm.'">';
							if($option['cpm']['label'] !== false)
							{
								echo '<label for="cpm_option_'.$option_id.'_'.$product['id'].'">'.$option['cpm']['label'].'</label>';
							}
							?>
						</div>
					</div>
				</div>
				<?php
				}
				?>
				</div>
			</div>
		</div>
	</li>
	<?php
	}
	else if($product['product_type'] == "cost_per_discrete_unit")
	{
	?>

	<li id="product_budget_<?php echo $product['id'];?>" class="display_collection_item" <?php echo $product_display_text; ?>>
		<div class="collapsible-header">
			<div class="row">
				<div class="col s2 targeted_image_col text-bold grey-text">
					<div class="img_title_section_container">
					<?php
					if($definition['product_enabled_img'] !== false)
					{
						echo '<img class="targeted_image" src="'.$definition['product_enabled_img'].'">';
					}
					else if($definition['product_disabled_img'] !== false)
					{
						echo '<img class="targeted_image" src="'.$definition['product_disabled_img'].'">';
					}
					echo '<div class="audience_img_title_section">';
					echo ($definition['first_name'] !== false) ? $definition['first_name']."<br>" : "";
					echo $definition['last_name'];
					echo "</div>";

					?>
					</div>
				</div>
				<div class="col s10">
					<div class="row">
						<?php
						foreach($definition['options'] as $option_id => $option)
						{
							$option_total = 0;
							$option_unit = $option['unit']['dropdown_options'][0]['value'];
							$option_cpc = 0;

							if($option['unit']['default'] !== false)
							{
								$option_unit = $option['unit']['default'];
							}
							if(array_key_exists('custom_default', $option['cpc']))
							{
								$option_cpc = $option['cpc']['custom_default'];
							}
							else if($option['cpc']['default'] !== false)
							{
								$option_cpc = $option['cpc']['default'];
							}
							$option_total = $option_unit*$option_cpc;
							$subtotal_array[$option_id] += $option_total;
							$term_text = $one_time ? $one_time : '<span class="grey-text text-lighten-1 cp_term_text_' . $option_id . '">per month</span>';
						?>
						<div class="col s4 option_<?php echo $option_id; ?>_item">
							<span id="product_subtotal_option_<?php echo $option_id.'_'.$product['id']; ?>" class="grey-text product_subtotal <?php echo $product_calc_class; ?>" data-base-price="<?php echo $option_total;?>" data-option-id="<?php echo $option_id; ?>">$<?php echo number_format($option_total); ?></span> <span class="grey-text subtotal_small_text"><?php echo $term_text; ?></span>
						</div>
						<?php
						}
						?>
					</div>
				</div>
			</div>
		</div>

		<div class="collapsible-body">
			<div class="row">
				<div class="col s10 offset-s2">
				<?php
				foreach($definition['options'] as $option_id => $option)
				{
					$option_default = $option['unit']['default'];
					if(array_key_exists('raw_unit', $option))
					{
						$option_default = $option['raw_unit']['default'];
					}
					$option_cpc = $option['cpc']['default'];
					if(array_key_exists('custom_default', $option['cpc']))
					{
						$option_cpc = $option['cpc']['custom_default'];
					}
				?>
				<div class="col s4 option_<?php echo $option_id; ?>_item">
					<div class="row">

						<div class="input-field col s6">
							<select onchange="cost_per_discrete_unit_calc('<?php echo $option_id; ?>','<?php echo $product['id']; ?>', false, false);" id="unit_option_<?php echo $option_id.'_'.$product['id']; ?>" class="io_period_dropdown grey-text product_input budget_multiplier_discrete_unit_dropdown" data-input-type="raw_unit" data-option-id="<?php echo $option_id; ?>" data-product-id="<?php echo $product['id']; ?>">
							<?php
							foreach($option['unit']['dropdown_options'] as $dropdown_option)
							{
								$selected_string = "";

								if($dropdown_option['value'] == $option_default)
								{
									$selected_string = ' selected="selected"';
								}
								echo '<option'.$selected_string.' value="'.$dropdown_option["value"].'">'.$dropdown_option["text"].'</option>';
							}
							?>
							</select>
							<?php
							if($option['unit']['label'] !== false)
							{
								echo '<label for="unit_option_'.$option_id.'_'.$product['id'].'">'.$option['unit']['label'].'</label>';
							}
							?>
						</div>
						<div class="col s6" style="margin-top:2rem;">
						<?php
						if(array_key_exists('description_html', $option['unit']))
						{
							echo $option['unit']['description_html'];
						}
						else
						{
						?>
							visits<span class="cp_term_text_<?php echo $option_id;?>"> per month</span> per region
						<?php
						}
						?>
						</div>
					</div>
					<div class="row">
					<div class="input-field col s6">
						<?php
					$unit_input_value = $option['unit']['default'];
					if(array_key_exists('unit_input_default', $option['unit']))
					{
						$unit_input_value = $option['unit']['unit_input_default'];
					}
					$unit_input_value_label = 'Total<span class="require_multi_location"> per region</span>';
					if(array_key_exists('unit_input_label', $option['unit']))
					{
						$unit_input_value_label = $option['unit']['unit_input_label'];
					}
						echo '<span class="discrete_dollar_prepend">$</span><input onchange="cost_per_discrete_unit_calc(\''.$option_id,'\', \''.$product['id'].'\', this, true);" id="dollar_option_'.$option_id.'_'.$product['id'].'" type="text" class="input_dollars grey-text product_input" data-cpc-reverse-calc="true" data-input-type="unit" data-option-id="'.$option_id.'" data-product-id="'.$product['id'].'" value="'.number_format($unit_input_value * $option_cpc).'">';
						echo '<label for="dollar_option_'.$option_id.'_'.$product['id'].'">'.$unit_input_value_label.'</label>';
						?>
						</div>
						<div class="input-field col s4" style="display:none;">
							<?php
							$temp_cpc = trim(trim(number_format($option_cpc, 4), '0'), '.');
							if($temp_cpc == "")
							{
								$temp_cpc = "0";
							}
						echo '<span class="discrete_dollar_prepend">$</span><input  onchange="cost_per_discrete_unit_calc(\''.$option_id.'\', \''.$product['id'].'\', this, false);" id="cpm_option_'.$option_id.'_'.$product['id'].'" type="text" class="input_cpm grey-text product_input" data-input-type="cpc" data-default-cpc="'.$option['cpc']['default'].'" data-option-id="'.$option_id.'" data-product-id="'.$product['id'].'" value="'.$temp_cpc.'">';
						if($option['cpc']['label'] !== false)
						{
							echo '<label for="cpm_option_'.$option_id.'_'.$product['id'].'">'.$option['cpc']['label'].'</label>';
						}
						?>
						</div>
					</div>
				</div>
				<?php
				}
				?>
				</div>
			</div>
		</div>
	</li>

	<?php
	}
	else if($product['product_type'] == "input_box")
	{
	?>

	<li id="product_budget_<?php echo $product['id'];?>" class="display_collection_item" <?php echo $product_display_text; ?>>
		<div class="collapsible-header">
			<div class="row">
				<div class="col s2 targeted_image_col text-bold grey-text">
					<div class="img_title_section_container">
					<?php
					if($definition['product_enabled_img'] !== false)
					{
						echo '<img class="targeted_image" src="'.$definition['product_enabled_img'].'">';
					}
					else if($definition['product_disabled_img'] !== false)
					{
						echo '<img class="targeted_image" src="'.$definition['product_disabled_img'].'">';
					}
					echo '<div class="audience_img_title_section">';

					if(array_key_exists('name_editable', $definition) and $definition['name_editable'] == true)
					{
						echo '<span id="product_display_text_'.$product['id'].'" class="product_display_text">'.$definition['last_name'].'</span>';
					}
					else
					{
						echo ($definition['first_name'] !== false) ? $definition['first_name']."<br>" : "";
						echo $definition['last_name'];
					}
					echo "</div>";

					?>
					</div>
				</div>
				<div class="col s10">
					<div class="row">
					<?php
					foreach($definition['options'] as $option_id => $option)
					{
						$option_total = 0;
						if($option['unit']['default'] !== false)
						{
							$option_total = intval($option['unit']['default']);
						}
						$subtotal_array[$option_id] += $option_total;
						$term_text = $one_time ? $one_time : '<span class="grey-text text-lighten-1 cp_term_text_' . $option_id . '">per month</span>';
					?>
						<div class="col s4 option_<?php echo $option_id; ?>_item">
						<span id="product_subtotal_option_<?php echo $option_id.'_'.$product['id']; ?>" class="grey-text product_subtotal <?php echo $product_calc_class; ?>" data-base-price="<?php echo $option_total;?>" data-option-id="<?php echo $option_id; ?>">$<?php echo number_format($option_total);?></span> <span class="grey-text subtotal_small_text"><?php echo $term_text; ?></span>
						</div>
					<?php
					}
					?>
					</div>
				</div>
			</div>
		</div>

		<div class="collapsible-body">
			<div class="row">
				<?php
				if(array_key_exists('name_editable', $definition) and $definition['name_editable'] == true)
				{
					echo '<div class="input-field col s2">';
					echo '<input id="product_display_input_'.$product['id'].'" class="product_display_input" data-original-value="'.$definition['last_name'].'" data-product-id="'.$product['id'].'" type="text" value="'.$definition['last_name'].'" />';
					if(array_key_exists('name_editable_label', $definition))
					{
						echo '<label style="left: 1.75rem;" for="produt_display_input_'.$product['id'].'">'.$definition['name_editable_label'].'</label>';
					}
					echo '</div>';
				 	echo '<div class="col s10">';
				}
				else
				{
					echo '<div class="col s10 offset-s2">';
				}
				foreach($definition['options'] as $option_id => $option)
				{
				?>
				<div class="col s4 option_<?php echo $option_id; ?>_item">
					<div class="row">
						<div class="col s6 input-field">

				<?php
				echo '<span class="discrete_dollar_prepend">$</span><input  onchange="input_box_calc(\''.$option_id.'\', \''.$product['id'].'\', this);" id="unit_option_'.$option_id.'_'.$product['id'].'" type="text" class="input_box_product grey-text product_input '.$after_discount_class.'" data-input-type="unit" data-option-id="'.$option_id.'" data-default-value="'.$option['unit']['default'].'" data-product-id="'.$product['id'].'" value="'.number_format(intval($option['unit']['default'])).'">';
				if($option['unit']['label'] !== false)
				{
					echo '<label for="unit_option_'.$option_id.'_'.$product['id'].'">'.$option['unit']['label'].'</label>';
				}
				?>
						</div>
					</div>
				</div>

				<?php
				}
				?>
				</div>
			</div>
		</div>
	</li>

	<?php
	}
	else if($product['product_type'] == "cost_per_static_unit")
	{
	?>

	<li id="product_budget_<?php echo $product['id'];?>" class="display_collection_item" <?php echo $product_display_text; ?>>
		<div class="collapsible-header">
			<div class="row">
				<div class="col s2 targeted_image_col text-bold grey-text">
					<div class="img_title_section_container">
					<?php
					if($definition['product_enabled_img'] !== false)
					{
						echo '<img class="targeted_image" src="'.$definition['product_enabled_img'].'">';
					}
					else if($definition['product_disabled_img'] !== false)
					{
						echo '<img class="targeted_image" src="'.$definition['product_disabled_img'].'">';
					}
					echo '<div class="audience_img_title_section">';
					echo ($definition['first_name'] !== false) ? $definition['first_name']."<br>" : "";
					echo $definition['last_name'];
					echo "</div>";

					?>
					</div>
				</div>
				<div class="col s10">
					<div class="row">
						<?php
						foreach($definition['options'] as $option_id => $option)
						{
							$option_content = 0;
							$option_price = 0;
							$option_total = 0;
							if($definition['content']['default'] !== false)
							{
								$option_content = $definition['content']['default'];
							}
							if($option['price']['default'] !== false)
							{
								$option_price = $option['price']['default'];
							}
							$option_total = $option_content*$option_price;
							$subtotal_array[$option_id] += $option_total;
							$term_text = $one_time ? $one_time : '<span class="grey-text text-lighten-1 cp_term_text_' . $option_id . '">per month</span>';
						?>
						<div class="col s4 option_<?php echo $option_id; ?>_item">
							<span id="product_subtotal_option_<?php echo $option_id.'_'.$product['id']; ?>" class="grey-text product_subtotal <?php echo $product_calc_class; ?>" data-base-price="<?php echo $option_total;?>" data-option-id="<?php echo $option_id; ?>">$<?php echo number_format($option_total); ?></span> <span class="grey-text subtotal_small_text"><?php echo $term_text; ?></span>
						</div>
						<?php
						}
						?>
					</div>
				</div>
			</div>
		</div>

		<div class="collapsible-body">
			<div class="row">
				<div class="col s2">
					<div class="input-field" style="width:90%; padding-left:5%; padding-right:5%;">
					<?php
				echo '<input  onchange="cost_per_static_unit_calc(false, \''.$product['id'].'\', this);" id="content_option_'.$product['id'].'" type="text" class="grey-text no_option_product_input" data-input-type="content" data-product-id="'.$product['id'].'" value="'.number_format($definition['content']['default']).'">';
				if($definition['content']['label'] !== false)
				{
					echo '<label for="content_option_'.$product['id'].'">'.$definition['content']['label'].'</label>';
				}
				?>
					</div>
				</div>
				<div class="col s10">
				<?php
				foreach($definition['options'] as $option_id => $option)
				{
				?>
				<div class="col s4 option_<?php echo $option_id; ?>_item">
					<div class="row">
						<div class="col s6 input-field">

				<?php
				echo '<span class="discrete_dollar_prepend">$</span><input onchange="cost_per_static_unit_calc(\''.$option_id.'\', \''.$product['id'].'\', this);" id="price_option_'.$option_id.'_'.$product['id'].'" type="text" class="input_price grey-text product_input '.$after_discount_class.'" data-input-type="price" data-option-id="'.$option_id.'" data-product-id="'.$product['id'].'" data-default-value="'.$option['price']['default'].'" value="'.number_format($option['price']['default']).'">';
				if($option['price']['label'] !== false)
				{
					echo '<label for="price_option_'.$option_id.'_'.$product['id'].'">'.$option['price']['label'].'</label>';
				}
				?>

						</div>
					</div>
				</div>

				<?php
				}
				?>
			</div>
			</div>
		</div>
	</li>
	<?php
	}
	else if($product['product_type'] == "cost_per_tv_package")
	{
?>
		<li id="product_budget_<?php echo $product['id'];?>" class="display_collection_item" <?php echo $product_display_text; ?>>
		<div class="collapsible-header">
			<div class="row">
				<div class="col s2 targeted_image_col text-bold grey-text">
					<div class="img_title_section_container">
					<?php
					if($definition['product_enabled_img'] !== false)
					{
						echo '<img class="targeted_image" src="'.$definition['product_enabled_img'].'">';
					}
					else if($definition['product_disabled_img'] !== false)
					{
						echo '<img class="targeted_image" src="'.$definition['product_disabled_img'].'">';
					}
					echo '<div class="audience_img_title_section">';
					echo ($definition['first_name'] !== false) ? $definition['first_name']."<br>" : "";
					echo $definition['last_name'];
					echo "</div>";
					?>
					</div>
				</div>
				<div class="col s10">
					<div class="row">
						<?php
						foreach($definition['options'] as $option_id => $option)
						{
							$option_total = 0;
							$subtotal_array[$option_id] += $option_total;
							$term_text = $one_time ? $one_time : '<span class="grey-text text-lighten-1 cp_term_text_' . $option_id . '">per month</span>';
						?>

						<div class="col s4 option_<?php echo $option_id; ?>_item">
							<span id="product_subtotal_option_<?php echo $option_id.'_'.$product['id']; ?>" class="grey-text product_subtotal product_budget_tv_zone_dependent <?php echo $product_calc_class; ?>" data-base-price="<?php echo $option_total;?>" data-option-id="<?php echo $option_id; ?>" data-product-id="<?php echo $product['id']; ?>">$<?php echo number_format($option_total); ?></span> <span class="grey-text subtotal_small_text"><?php echo $term_text; ?></span>
						</div>

						<?php
						}
						?>
					</div>
				</div>
			</div>
		</div>

		<div class="collapsible-body">
			<div class="row">
				<div class="col s10 offset-s2">
					<input type="hidden" name="custom_pack_type" value="<?php echo $definition['custom_pack_mapping'] ?: '16 Network'; ?>"/>
				<?php
				foreach($definition['options'] as $option_id => $option)
				{
					$option_default = $option['unit']['default'];
					$custom_selected = $option_default === 0;
					$custom_spots = isset($option['spots']) ? $option['spots']['default'] : 0;
					$custom_price = isset($option['price']) ? $option['price']['default'] : 0;
				?>
					<div class="col s4 option_<?php echo $option_id; ?>_item tv_budget_item" data-option-id="<?php echo $option_id; ?>" data-product-id="<?php echo $product['id']; ?>">
						<div class="row">
							<div class="input-field col s12">
								<select onload="cost_per_tv_package_calc('<?php echo $option_id; ?>', '<?php echo $product['id']; ?>', this);" onchange="cost_per_tv_package_calc('<?php echo $option_id; ?>', '<?php echo $product['id']; ?>', this);" id="unit_option_<?php echo $option_id.'_'.$product['id']; ?>" class="col s6 io_period_dropdown tv_zone_package_select grey-text product_input" data-input-type="tv" data-option-id="<?php echo $option_id; ?>" data-product-id="<?php echo $product['id']; ?>">
								<?php
								foreach($option['unit']['dropdown_options'] as $dropdown_option)
								{
									$selected_string = "";
									if($dropdown_option['value'] == $option_default)
									{
										$selected_string = ' selected="selected"';
									}
									echo '<option'.$selected_string.' value="'.$dropdown_option["value"].'">'.$dropdown_option["text"].'</option>';
								}
								?>
									<option <?php echo $custom_selected ? "selected" : ""; ?> value="custom">Custom</option>
								</select>
								<div class="col s6" style="line-height:61px;">
								<?php
								if(array_key_exists('description_html', $option['unit']))
								{
									echo $option['unit']['description_html'];
								}
								?>
								</div>
							</div>
						</div>
						<div class="row" id="custom_tv_package_<?php echo $option_id; ?>" style="<?php echo $custom_selected ? "" : "display: none;"; ?>">
							<div class="input-field col s4" style="margin-left: 0.75rem;">
								<input name="custom_tv_package_<?php echo $option_id; ?>_spots" onchange="custom_tv_spots_calc('<?php echo $option_id; ?>', '<?php echo $product['id']; ?>', this);" type="text" class="grey-text product_input custom_spots_input" value="<?php echo $custom_spots; ?>" data-original-value="<?php echo $custom_spots; ?>">
								<label for="custom_tv_package_<?php echo $option_id; ?>_spots">Spots</label>
							</div>
							<div class="input-field col s4">
								<span class="discrete_dollar_prepend">$</span>
								<input name="custom_tv_package_<?php echo $option_id; ?>_price" onchange="custom_tv_package_calc('<?php echo $option_id; ?>', '<?php echo $product['id']; ?>', this);" type="text" class="grey-text product_input custom_price_input" value="<?php echo $custom_price; ?>" data-original-value="<?php echo $custom_price; ?>">
								<label for="custom_tv_package_<?php echo $option_id; ?>_price">Price</label>
							</div>
						</div>
					</div>
				<?php }?>
				</div>
			</div>
		</div>
	</li>

<?php
	}
	else if($product['product_type'] == "cost_per_sem_unit")
	{
	?>
	<li id="product_budget_<?php echo $product['id'];?>" class="display_collection_item" <?php echo $product_display_text; ?>>
		<div class="collapsible-header">
			<div class="row">
				<div class="col s2 targeted_image_col text-bold grey-text">
					<div class="img_title_section_container">
					<?php
					if($definition['product_enabled_img'] !== false)
					{
						echo '<img class="targeted_image" src="'.$definition['product_enabled_img'].'">';
					}
					else if($definition['product_disabled_img'] !== false)
					{
						echo '<img class="targeted_image" src="'.$definition['product_disabled_img'].'">';
					}
					echo '<div class="audience_img_title_section">';
					echo ($definition['first_name'] !== false) ? $definition['first_name']."<br>" : "";
					echo $definition['last_name'];
					echo "</div>";
					?>
					</div>
				</div>
				<div class="col s10">
					<div class="row">
						<?php
						foreach($definition['options'] as $option_id => $option)
						{
							$option_total = 0;
							$option_unit = 0;
							if($option['unit']['default'] !== false)
							{
								$option_unit = $option['unit']['default'];
							}
							$option_total = $option_unit;

							$term_text = $one_time ? $one_time : '<span class="grey-text text-lighten-1 cp_term_text_' . $option_id . '">per month</span>';
						?>

						<div class="col s4 option_<?php echo $option_id; ?>_item">
							<span id="product_subtotal_option_<?php echo $option_id.'_'.$product['id']; ?>" class="grey-text product_subtotal <?php echo $product_calc_class; ?>" data-base-price="<?php echo $option_total;?>" data-option-id="<?php echo $option_id; ?>">$<?php echo number_format($option_total); ?></span> <span class="grey-text subtotal_small_text"><?php echo $term_text; ?></span>
						</div>

						<?php
						}
						?>
					</div>
				</div>
			</div>
		</div>

		<div class="collapsible-body">
			<div class="row">
				<div class="col s10 offset-s2">
				<?php
				foreach($definition['options'] as $option_id => $option)
				{
				?>
					<div class="col s4 option_<?php echo $option_id; ?>_item sem_budget_item" data-option-id="<?php echo $option_id; ?>" data-product-id="<?php echo $product['id']; ?>">
						<div class="row">
							<div class="input-field col s12">
								<?php
								$cpm_value = $option['cpm']['default'];

								echo '<input onchange="cost_per_sem_unit_calc(\''.$option_id.'\',\''.$product['id'].'\');" id="cpm_option_'.$option_id.'_'.$product['id'].'" type="text" class="grey-text product_input sem_clicks_input sem_clicks_amount keywords_clicks_dependent" data-input-type="cpm" data-option-id="'.$option_id.'" data-product-id="'.$product['id'].'" value="'.number_format($cpm_value).'">';
								if($option['cpm']['label'] !== false)
								{
									echo '<label for="cpm_option_'.$option_id.'_'.$product['id'].'">'.$option['unit']['label'].'</label>';
								}
								?>
								<span class="grey-text text-darken-1">Clicks per month</span>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s12">
								<span class="sem_dollar_prepend">$</span>
								<?php
								$unit_value = $option['unit']['default'];

								echo '<input onchange="cost_per_sem_unit_calc(\''.$option_id.'\',\''.$product['id'].'\');" id="unit_option_'.$option_id.'_'.$product['id'].'" type="text" class="grey-text product_input sem_clicks_input sem_price_amount" data-input-type="unit" data-option-id="'.$option_id.'" data-product-id="'.$product['id'].'" value="'.number_format($unit_value).'">';
								if($option['unit']['label'] !== false)
								{
									echo '<label for="unit_option_'.$option_id.'_'.$product['id'].'">'.$option['unit']['label'].'</label>';
								}
								?>
								<span class="grey-text text-darken-1">Budget per month</span>
							</div>
						</div>
					</div>

				<?php
				}
				?>
				</div>

			</div>
		</div>
	</li>
	<?php
	}
}

?>

<div id="mpq_body_container" class="container" style="">

<?php

	$this->load->view('rfp/advertiser_info_section_html');
	$this->load->view('rfp/product_info_section_html');
	$this->load->view('rfp/tvzones_section_html');
	$this->load->view('rfp/keywords_info_section_html');
	$this->load->view('rfp/geography_info_section_html');
	$this->load->view('rfp/audience_info_section_html');
	$this->load->view('rfp/rooftops_section_html');
	$this->load->view('rfp/budget_info_section_html');

?>

	<form id="take_me_to_success" method="POST" action="/rfp/success" class="hidden">
		<input type="hidden" name="id" value="">
	</form>

	<form id="rfp_with_new_account_executive" method="POST" action="/rfp" class="hidden">
		<input type="hidden" name="new_account_executive" value="">
	</form>

	<div id="rfp_account_executive_confirm" class="modal">
		<div class="modal-content">
			<h4>Your RFP settings have changed</h4>
			<p>
				You have selected a user with different RFP settings.  The page needs to refresh to load the new data.  You will lose any changes to your current RFP.
			</p>
		</div>
		<div class="modal-footer">
			<a id="rfp_account_executive_cancel" href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cancel</a>
			<a id="rfp_account_executive_reload" href="#!" class="modal-action waves-effect waves-green btn-flat">Reload Page</a>
		</div>
	</div>

