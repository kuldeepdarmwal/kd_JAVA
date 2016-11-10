<?php
	if($isError == true)
	{
		echo '
			<h2> Errors </h2>
		';
		foreach($errorMessages as $message)
		{
			echo $message."<br />";
		}
	}
?>

