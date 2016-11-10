<!DOCTYPE html>
<html>
	<body>
		<h2>Hello <?php echo $firstname." ".$lastname; ?></h2>
		<?php if($is_success) { ?>
			Your request for campaign data has been processed.  Please see the attached file for your Bulk Campaign Summary.
		<?php } else { ?>
			An error was encountered while processing your bulk campaign request.
		<?php } ?>
		<br />
	</body>
	
</html>
