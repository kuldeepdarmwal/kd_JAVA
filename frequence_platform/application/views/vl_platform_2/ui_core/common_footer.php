		<?php
			// See common_header.php for instructions on how to use ui_core/*.php

			// feature specific html in previous view
		?>
		<?php
			$this->load->view('vl_platform_2/ui_core/js_error_handling.php');
		?>

		<div style="width=100%;text-align:center;">
		<?php
				//copyright removed
		?>
		</div>

		<?php
			write_vl_platform_error_handlers_html_section();
		?>

		<?php
			// NOTE: jquery and bootstrap js files are included here.
			// Don't inlucde them again in feature specific code.
		?>
		<script src="/libraries/external/jquery-1.10.2/jquery-1.10.2.min.js"></script>
		<script src="/bootstrap/assets/js/bootstrap_v2_3_1.min.js"></script>
		<script src="/libraries/external/underscorejs/underscore-min.1.8.3.js"></script>

		<?php
			write_vl_platform_error_handlers_js();
		?>

		<script type="text/javascript">
		// This script section has crontrol for master navigation bar.
		// Only the pieces the user has permission to use are output.


		$(document).ready(function() {
			<?php
				if(isset($master_header_data))
				{
					echo '$("#'.$master_header_data['active_feature_id'].'_feature_link").addClass("active");';
					echo '$("#pull_'.$master_header_data['active_feature_id'].'_feature_link").addClass("active");';
				}
			?>

			$(document).on('click', '.pull_nav_toggle', function(e){
				e.preventDefault();

				$('.pull_nav').toggleClass('open');
				var open = $('.pull_nav').hasClass('open') ? 0 : -300;

				$('.pull_nav').animate(
					{
						'right': open
					},
					200
				);
			});
		});

		$('.helpdesk-popover').popover({delay: { hide: 2000 }});

		</script>


		<?php
			// this script section has control of feature nav bar
			if(!isset($skip_common_feature_header) ||
				!$skip_common_feature_header
			)
			{
		?>
		<script type="text/javascript">

		// Bind click function to each feature nav button.
		$(document).ready(function() {

			<?php

				if(isset($feature_header_data))
				{
					echo '$("#feature_nav_item_'.$feature_header_data['active_subfeature_id'].'").addClass("active");';
				}
			?>

			$("#nothing_lfdhs").addClass("active");
		});

		</script>
		<?php
			} // close skip_common_feature_header
		?>

		<?php
			// Javascript for active feature
			if(isset($feature_js_view_path) &&
				!empty($feature_js_view_path)
			)
			{
				$this->load->view($feature_js_view_path);
			}

			// Javascript for active subfeature
			if(isset($subfeature_js_view_path) &&
				!empty($subfeature_js_view_path)
			)
			{
				$this->load->view($subfeature_js_view_path);
			}
		?>

	</body>
</html>
