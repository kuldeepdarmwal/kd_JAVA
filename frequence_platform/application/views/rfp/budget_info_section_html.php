<!-- budget section -->

<?php
$term_array = array('monthly' => true, 'weekly' => true, 'daily' => true);
foreach($products as $product)
{
	$definition = json_decode($product['definition'], true);
	if(array_key_exists('term', $definition))
	{
		if(!in_array('monthly', $definition['term']))
		{
			$term_array['monthly'] = false;
		}
		if(!in_array('weekly', $definition['term']))
		{
			$term_array['weekly'] = false;
		}
		if(!in_array('daily', $definition['term']))
		{
			$term_array['daily'] = false;
		}
	}
}
if(!in_array('true', $term_array))
{
	$term_array = array('monthly' => true, 'weekly' => true, 'daily' => true);
}

?>

<div id="mpq_budget_info_section">
	<form>
		<ul class="collapsible popout collapsible-accordion" data-collapsible="accordion" id="budget_content_collection">
			<li>
				<div class="card-content row" style="margin-bottom: 0px;">
					<h4 class="col s6 card-title grey-text text-darken-1">Budget</h4>
				</div>
				<div id="slider_container">
					<div class="row" style="margin-bottom:0px;">
						<div id="rfp_budget_slider_container_col" class="col s10 offset-s1">
							<form>
								<label for="budget_slider">Budget</label>
								<input id="budget_slider" min="25" max="100" value="50">
								<label for="budget_slider" style="float:left;padding-top:5px;">Lower</label><label for="budget_slider" style="float:right;padding-top:5px;">Higher</label>
							</form>
						</div>
					</div>
				</div>
				
				<a id="add_new_option_button" class="text-bold btn-floating btn-large waves-effect waves-light grey darken-1 tooltipped" data-tooltip="Add new option"><i style="font-size:2rem;" class="mdi-content-add"></i></a>
				<div id="budget_option_row" class="row">
					<div class="col s10 offset-s2">
						<div class="row">
							<div id="option_0_indicator" class="col s4 option_0_item">
								<div id="option_0_text_container" class="budget_option_text_container tooltipped" data-position="top" data-delay="50" data-tooltip="double-click to rename">
									<span id="option_0_text" class="budget_option_text">Budget 1</span>
								</div>
								<input id="option_0_title" type="text" class="budget_option_title" value="Budget 1">
								<a class="proposal_option_close_button btn-flat" data-option-id="0">
									<i class="grey-text text-darken-1 mdi-action-highlight-remove"></i>
								</a>
							</div>
							<div id="option_1_indicator" class="col s4 option_1_item">
								<div id="option_1_text_container" class="budget_option_text_container tooltipped" data-position="top" data-delay="50" data-tooltip="double-click to rename">
									<span id="option_1_text" class="budget_option_text">Budget 2</span>
								</div>
								<input id="option_1_title" type="text" class="budget_option_title" value="Budget 2">
								<a class="proposal_option_close_button btn-flat" data-option-id="1">
									<i class="grey-text text-darken-1 mdi-action-highlight-remove"></i>
								</a>
							</div>
							<div id="option_2_indicator" class="col s4 option_2_item">
								<div id="option_2_text_container" class="budget_option_text_container tooltipped" data-position="top" data-delay="50" data-tooltip="double-click to rename">
									<span id="option_2_text" class="budget_option_text">Budget 3</span>
								</div>
								<input id="option_2_title" type="text" class="budget_option_title" value="Budget 3">
								<a class="proposal_option_close_button btn-flat" data-option-id="2">
									<i class="grey-text text-darken-1 mdi-action-highlight-remove"></i>
								</a>
							</div>
						</div>
					</div>
				</div>
			</li>
			
			<li id="option_collection_item">
				<div class="row" style="margin:0px;">
					<div class="col s2 term_col text-bold grey-text text-darken-1">
						<div style="padding-top:1rem;padding-left:1rem;">
							Term
						</div>
					</div>
					<div class="col s10">
						<div class="row">
							<div class="col s4 option_0_item">
								<select id="option_0_duration" style="height: 400px;" class="duration_dropdown" data-option-id="0">
									<option value="1">1</option>
									<option value="2">2</option>
									<option value="3">3</option>
									<option value="4">4</option>
									<option value="5">5</option>
									<option selected="selected" value="6">6</option>
									<option value="7">7</option>
									<option value="8">8</option>
									<option value="9">9</option>
									<option value="10">10</option>
									<option value="11">11</option>
									<option value="12">12</option>
								</select>
								<select id="option_0_term" data-option-id="0" class="duration_term_dropdown">
<?php
if($term_array['monthly'] == true){ echo '<option value="monthly">Months</option>'; }
if($term_array['weekly'] == true){ echo '<option value="weekly">Weeks</option>'; }
if($term_array['daily'] == true){ echo '<option value="daily">Days</option>'; }
?>
								</select>
							</div>
							<div class="col s4 option_1_item">
								<select id="option_1_duration" class="duration_dropdown" data-option-id="1">
									<option value="1">1</option>
									<option value="2">2</option>
									<option value="3">3</option>
									<option value="4">4</option>
									<option value="5">5</option>
									<option selected="selected" value="6">6</option>
									<option value="7">7</option>
									<option value="8">8</option>
									<option value="9">9</option>
									<option value="10">10</option>
									<option value="11">11</option>
									<option value="12">12</option>
								</select>
								<select id="option_1_term" data-option-id="1" class="duration_term_dropdown">
<?php
if($term_array['monthly'] == true){ echo '<option value="monthly">Months</option>'; }
if($term_array['weekly'] == true){ echo '<option value="weekly">Weeks</option>'; }
if($term_array['daily'] == true){ echo '<option value="daily">Days</option>'; }
?>
								</select>
							</div>
							<div class="col s4 option_2_item">
								<select id="option_2_duration" class="duration_dropdown" data-option-id="2">
									<option value="1">1</option>
									<option value="2">2</option>
									<option value="3">3</option>
									<option value="4">4</option>
									<option value="5">5</option>
									<option selected="selected" value="6">6</option>
									<option value="7">7</option>
									<option value="8">8</option>
									<option value="9">9</option>
									<option value="10">10</option>
									<option value="11">11</option>
									<option value="12">12</option>
								</select>
								<select id="option_2_term" data-option-id="2" class="duration_term_dropdown">
<?php
if($term_array['monthly'] == true){ echo '<option value="monthly">Months</option>'; }
if($term_array['weekly'] == true){ echo '<option value="weekly">Weeks</option>'; }
if($term_array['daily'] == true){ echo '<option value="daily">Days</option>'; }
?>
								</select>
							</div>
						</div>
					</div>
				</div>
			</li>
	<?php
	$after_total = array();
	$subtotal = array('0' => 0, '1' => 0, '2' => 0);
	foreach($products as $product)
	{
		$definition = json_decode($product['definition'], true);
		if(array_key_exists('after_discount', $definition) and $definition['after_discount'] == true)
		{
			$after_total[$product['id']] = array('product' => $product, 'definition' => json_decode($product['definition'], true));
		}
		else if($product['product_type'] !== 'discount')
		{

			write_product_html($product, $definition, $subtotal, true, false, $is_preload);
			
		}
	}
	$discount_percent = $raw_discount/100;
	$discount_array = array('0' => $subtotal['0'] * $discount_percent, '1' => $subtotal['1'] * $discount_percent, '2' => $subtotal['2'] * $discount_percent);
	
	$total_per_location_array = array('0' => ($subtotal['0'] * (1-$discount_percent)), '1' => ($subtotal['1'] * (1-$discount_percent)), '2' => ($subtotal['2'] * (1-$discount_percent)));
	
	$total_all_location_array = array('0' => ($subtotal['0'] * (1-$discount_percent)), '1' => ($subtotal['1'] * (1-$discount_percent)), '2' => ($subtotal['2'] * (1-$discount_percent)));
	
	 ?>
			<li id="subtotal_collection_item" class="require-multi-product">
				<div class="fake_collection_item">
					<div class="row">
						<div class="col s2 term_col text-bold grey-text text-darken-1">
							<div style="padding-top:1rem;">
								Subtotal
							</div>
						</div>
						<div class="col s10">
							<div class="row" style="line-height: 3rem;">
								<div class="col s4 option_0_item">
									<span id="subtotal_option_0" class="product_subtotal grey-text text-darken-1">$<?php echo number_format($subtotal['0']); ?></span> <span class="grey-text subtotal_small_text"><span class="grey-text text-lighten-1 cp_term_text_0">monthly</span></span>
								</div>
								<div class="col s4 option_1_item">
									<span id="subtotal_option_1" class="product_subtotal grey-text text-darken-1">$<?php echo number_format($subtotal['1']); ?></span> <span class="grey-text subtotal_small_text"><span class="grey-text text-lighten-1 cp_term_text_1">monthly</span></span>
								</div>
								<div class="col s4 option_2_item">
									<span id="subtotal_option_2" class="product_subtotal grey-text text-darken-1">$<?php echo number_format($subtotal['2']); ?></span> <span class="grey-text subtotal_small_text"><span class="grey-text text-lighten-1 cp_term_text_2">monthly</span></span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</li>

			<li id="discount_collection_item" class="display_collection_item">
				<div class="collapsible-header">
					<div class="row">
						<div class="col s2 term_col text-bold grey-text">
							<div style="padding-top:1rem; line-height:20px;">
								<span id="discount_display_text" class="product_display_text"><?php echo $raw_discount_name; ?></span>
							</div>
						</div>
						<div class="col s10">
							<div class="row" style="line-height: 3rem;">
								<div class="col s4 option_0_item">
									<span id="discount_option_0" class="product_subtotal green-text text-lighten-1">-$<?php echo number_format($discount_array['0']); ?></span> <span class="grey-text text-lighten-1 subtotal_small_text"><span class="grey-text text-lighten-1 cp_term_text_0">monthly</span></span>
								</div>
								<div class="col s4 option_1_item">
									<span id="discount_option_1" class="product_subtotal green-text text-lighten-1">-$<?php echo number_format($discount_array['1']); ?></span> <span class="grey-text text-lighten-1 subtotal_small_text"><span class="grey-text text-lighten-1 cp_term_text_1">monthly</span></span>
								</div>
								<div class="col s4 option_2_item">
									<span id="discount_option_2" class="product_subtotal green-text text-lighten-1">-$<?php echo number_format($discount_array['2']); ?></span> <span class="grey-text text-lighten-1 subtotal_small_text"><span class="grey-text text-lighten-1 cp_term_text_2">monthly</span></span>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="collapsible-body">
					<div class="row">
						<div class="input-field col s2">
							<input id="discount_display_input" class="display_input" type="text" value="<?php echo $raw_discount_name; ?>"/>
							<label style="left: 1.75rem;" for="discount_display_input">Discount name</label>
						</div>
						<div class="col s10">
							<div class="input-field col s4 option_0_item">
								<input id="discount_percent_option_0" onchange="format_discount_and_calc(this);" type="text" class="grey-text discount_input" value="<?php echo $raw_discount; ?>">%
								<label for="discount_percent_option_0"></label>
								
							</div>
							<div class="input-field col s4 option_1_item">	
								<input id="discount_percent_option_1" onchange="format_discount_and_calc(this);" type="text" class="grey-text discount_input" value="<?php echo $raw_discount; ?>">%
								<label for="discount_percent_option_1"></label>
							</div>
							<div class="input-field col s4 option_2_item">	
								<input id="discount_percent_option_2" onchange="format_discount_and_calc(this);" type="text" class="grey-text discount_input" value="<?php echo $raw_discount; ?>">%
								<label for="discount_percent_option_2"></label>
							</div>
						</div>
					</div>
				</div>
			</li>
				
			<li id="total_all_locations_collection_item" class="require_multi_location">
				<div class="fake_collection_item">
					<div class="row">
						<div class="col s2 term_col text-bold grey-text text-darken-1">
							<div style="padding-top:1rem;">
								Total Monthly
							</div>
						</div>
						<div class="col s10">
							<div class="row" style="line-height: 3rem;">
								<div class="col s4 option_0_item">
									<span id="total_all_location_option_0" class="product_subtotal grey-text text-darken-1">$<?php echo number_format($total_all_location_array['0']); ?></span> <span class="grey-text text-lighten-1 subtotal_small_text cp_term_text_0">monthly</span>
								</div>
								<div class="col s4 option_1_item">
									<span id="total_all_location_option_1" class="product_subtotal grey-text text-darken-1">$<?php echo number_format($total_all_location_array['1']); ?></span> <span class="grey-text text-lighten-1 subtotal_small_text cp_term_text_1">monthly</span>
								</div>
								<div class="col s4 option_2_item">
									<span id="total_all_location_option_2" class="product_subtotal grey-text text-darken-1">$<?php echo number_format($total_all_location_array['2']); ?></span> <span class="grey-text text-lighten-1 subtotal_small_text cp_term_text_2">monthly</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</li>

			<!-- after discount goes here -->
			
			<?php
				$setup_fee_array = array('0' => 0, '1' => 0, '2' => 0);
				foreach($after_total as $product_id => $product_instance)
				{
					$temp_setup_fee_array = array('0' => 0, '1' => 0, '2' => 0);
					write_product_html($product_instance['product'], $product_instance['definition'], $temp_setup_fee_array, false, '<span class="grey-text text-lighten-1">one time</span>', $is_preload);
					foreach($temp_setup_fee_array as $key => $value)
					{
						$setup_fee_array[$key] += $temp_setup_fee_array[$key];
					}
				}
				$grand_total_array = array('0' => ($subtotal['0'] * (1-$discount_percent) * $term_duration)+$setup_fee_array['0'], '1' => ($subtotal['1'] * (1-$discount_percent) * $term_duration)+$setup_fee_array['1'], '2' => ($subtotal['2'] * (1-$discount_percent) * $term_duration)+$setup_fee_array['2']);

			?>

			<li id="grand_total_collection_item">
				<div class="fake_collection_item">
					<div class="row">
						<div class="col s2 term_col text-bold grey-text text-darken-1">
							<div style="padding-top:1rem;">
								Grand Total
							</div>
						</div>
						<div class="col s10">
							<div class="row" style="line-height: 3rem;">
								<div class="col s4 option_0_item">
									<span id="grand_total_option_0" class="product_subtotal grey-text text-darken-1">$<?php echo number_format($grand_total_array['0']); ?></span>
								</div>
								<div class="col s4 option_1_item">
									<span id="grand_total_option_1" class="product_subtotal grey-text text-darken-1">$<?php echo number_format($grand_total_array['1']); ?></span>
								</div>
								<div class="col s4 option_2_item">
									<span id="grand_total_option_2" class="product_subtotal grey-text text-darken-1">$<?php echo number_format($grand_total_array['2']); ?></span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</li>

			<li id="download_collection_item">
				<div class="card-content" style="padding:1rem;">
					<div class="row" style="margin:0px;">

						<a id="cp_submit_button" class="col s2 offset-s10 waves-effect waves-light btn">Email Proposal</a>
					</div>
				</div>
			</li>
		</ul>
	</form>
</div>
