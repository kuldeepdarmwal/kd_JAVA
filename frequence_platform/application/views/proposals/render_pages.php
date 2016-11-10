<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	 	<link rel="stylesheet" href="https://s3.amazonaws.com/brandcdn-assets/partners/frequence/proposal_pdf.css">
		 <?php 
		 foreach ($css_includes as $css_include)
		 {
			 echo '<link rel="stylesheet" href="'.$css_include.'" />';
		 }
		 ?>

		<style><?php echo $custom_css; ?></style>
	</head>
	<body class="landscape">
		<?php
		foreach($pages as $page)
		{
			echo $page['raw_html'];
		}
		?>

		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js"></script>		
		<script type="text/javascript" src="/libraries/external/jquery-1.10.2/jquery-1.10.2.min.js"></script>

		<?php 
		foreach($js_includes as $js_include)
		{
			echo '<script type="text/javascript" src="'.$js_include.'"></script>';
		}
		?>
		<script type="text/javascript"><?php echo $custom_js; ?></script>
	</body>
</html>
