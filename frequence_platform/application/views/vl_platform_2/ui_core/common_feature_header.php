	<?php
		// See common_header.php for instructions on how to use ui_core/*.php
	?>
	
			<?php
				if(isset($feature_header_data['nav_buttons_data'])){
					echo '<div class="container">
							<ul class="nav nav-tabs">';
					$nav_buttons_data = $feature_header_data['nav_buttons_data'];
					foreach($nav_buttons_data as $ii=>$button_data)
					{
						$list_item_html = '<li id="feature_nav_item_'.
							$button_data['button_id'].'"><a href="'.
							$button_data['link_url'].'"><i class="'.
							$button_data['icon_class'].'"></i><span> '.
							$button_data['button_text'].'</span></a>'.
							'</li>';
						echo $list_item_html."\n";
					}
					echo '</ul>
						</div>';
				}
				// TODO: Make it handle arrays of arrays so a feature header item can have a drop down lists.
				//			 Fill in more data (title, optional image)
				
			?>
		
