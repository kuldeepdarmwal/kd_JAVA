<div class="container container-body" style=""> 
	<h3>Multi-location Planner<hr></h3>
	<div class="row-fluid">
		<div class="span12" style='padding-bottom:50px;'>
			<b>Address;Radius;Min population</b> <small>(if you use Min population, leave Radius blank and vice versa)</small>
			<form style="text-align:right;">
				<textarea id='laps' class='span12' rows=3 style='resize:none' placeholder='<?php echo "Disneyland;20;\nThe Statue of Liberty;;1,000,000"; ?>'></textarea>
				<button id='submit_laps' class='btn btn-success' title='(shift + enter)' style='width:80px' type='button'>go</button>
			</form>
			<small><span id='progress_span' class='muted' style='display:none'>Preparing to load regions</span></small>
			<div id='loading_bar' class='progress progress-striped active' style='display:none;'>
				<div class='bar' style='width: 0%'></div>
			</div>
			<div id='select2_div'>
				<form><select id='sel_id'><option></option></select></form>
			</div>
			<div id='title_container'></div>
			<div id='maps' class='row-fluid' style='position:relative;margin-bottom:50px;'>
				<?php echo $map_html; ?>
			</div>
			<div id='demos' class='row-fluid' style='position:relative;'>
				<?php echo $demo_html; ?>
			</div>
		</div>
	</div>
</div>