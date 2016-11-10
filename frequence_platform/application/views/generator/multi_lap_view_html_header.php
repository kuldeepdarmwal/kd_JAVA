<?php
	
	$this->load->view('mpq_v2/geo_component_functions');
	$this->load->view('mpq_v2/mpq_component_functions');

?>
<link href="/libraries/external/select2/select2.css" rel="stylesheet"/>
<style>

	<?php echo $demo_css; ?>

	iframe {
		margin-bottom: 30px;
	}

	.outer_toggle {
		position: absolute;
		top: -28px;
		left: 70px;
	}

	.togglers {
		font-size: 25px;
		text-align: right;
		padding-right: 1px;
		padding-top: 3px;
		-webkit-touch-callout: none;
		-webkit-user-select: none;
		-khtml-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
		user-select: none;
	}

	.togglers > i {
		cursor: pointer;

	}

	.toggle_containers {
		margin-bottom: 10px;
		position: relative;
	}

	.zips_containers {
		padding: 0px 3px;
		border-radius: 3px;
	}

	.background_color_grey {
		background-color: #D8D8D8;
	}

	#zips_text {
		margin-bottom: 0;
		text-align: justify;
	}

	.no_height {
		height: 0px;
	}

	.lead {
		margin-bottom: 0px;
	}
</style>