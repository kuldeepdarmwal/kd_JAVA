	<!-- Keywords section -->
	<div id="mpq_keywords_section" class="mpq_section_card card scrollspy">
		<h4 class="card-title grey-text text-darken-1 bold">Keywords<?php
		 if($is_rfp == true)
		 {
			 $keywords_product_text = "";
			 foreach($products as $product)
			 {
				 if($product['is_keywords_dependent'] == 1)
				 {
					 $keywords_product_definition = json_decode($product['definition'], true);
					 if($keywords_product_text !== "")
					 {
						 $keywords_product_text .= ", ";
					 }
					 $keywords_product_text .= ($keywords_product_definition['first_name'] !== false) ? $keywords_product_definition['first_name']." " : "";
					 $keywords_product_text .= $keywords_product_definition['last_name'];
				 }
			 }
			 echo '<div style="display:inline-block;font-weight:normal;font-size:1rem;margin-left:5%;">' . $keywords_product_text . "</div>";
		 }
?></h4>
		<div class="card-content">
			<div class="row">
				<div class="col s10">
					<div id="keywords_tags" contentEditable="true"><div class="keywords_placeholder_text" contentEditable="false">Paste Keywords Here...</div><div class="keywords_last_element" contentEditable="true"> </div></div>
				</div>
			</div>
			<div id="keywords_clicks_amount_content" class="row">
				<div class="col s10 input-field">
					<input id="keywords_clicks_amount" class="grey-text" type="text">
					<label for="keywords_clicks_amount" class="active">Click Inventory</label>
					<span class="grey-text text-darken-1">clicks available / month</span>
				</div>
			</div>
		</div>
	</div>
