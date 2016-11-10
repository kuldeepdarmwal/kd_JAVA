<?php
echo '
<h2>Send Email</h2>
/application/views/dashboard/sendMail.php <br />
';
?>

<?php
	if($emailSent == true)
	{
		echo "<h3>Message successfully sent</h3>";
		echo '<button style="padding:10px;" onclick="javascript:window.close();"><font size="2" style="color:#000;">Close Window</font></button>';
		echo "
			<table border='0' cellpadding='5'>
				<tr><td>To:</td><td>$to</td></tr>
				<tr><td>Subject:</td><td>$subject</td></tr>
				<tr><td valign='top'>Message:</td><td>$message</td></tr>
			</table>";

	} 
	else 
	{
		echo "<h3>Message delivery failed.</h3>";
	}

?>
