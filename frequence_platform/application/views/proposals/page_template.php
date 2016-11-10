<!doctype html>
<html>
	<head>
		<link rel="stylesheet" href="/assets/css/proposal_pdf.css">
		<?php 
			foreach ($includes as $file)
			{
				echo $file['file_type'] === 'js' ?
					'<script type="text/javascript" src="'.$file['file_url'].'"></script>' :
					'<link rel="stylesheet" href="'.$file['file_url'].'"/>';
			}
		?>
	</head>
	<body class="landscape"></body>
</html>