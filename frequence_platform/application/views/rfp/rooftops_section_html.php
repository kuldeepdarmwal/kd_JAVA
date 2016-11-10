	<!-- rooftops section -->
	<div id="mpq_rooftops_section" class="mpq_section_card card scrollspy">
		<h4 class="card-title grey-text text-darken-1 bold">Rooftops<?php
		 if($is_rfp == true)
		 {
			 $rooftops_product_text = "";
			 foreach($products as $product)
			 {
				 if($product['is_rooftops_dependent'] == 1)
				 {
					 $rooftops_product_definition = json_decode($product['definition'], true);
					 if($rooftops_product_text !== "")
					 {
						 $rooftops_product_text .= ", ";
					 }
					 $rooftops_product_text .= ($rooftops_product_definition['first_name'] !== false) ? $rooftops_product_definition['first_name']." " : "";
					 $rooftops_product_text .= $rooftops_product_definition['last_name'];
				 }
			 }
			 echo '<div style="display:inline-block;font-weight:normal;font-size:1rem;margin-left:5%;">' . $rooftops_product_text . "</div>";
		 }
?></h4>
		<div class="card-content">
			<input type="hidden" style="width:100%;" id="rooftops_multiselect">
		</div>
	</div>