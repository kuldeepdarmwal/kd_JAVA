<?php
//echo '<h2>Add Campaign</h2>';
//echo 'application/views/dashboard/tool_add_campaign.php';
echo "<h3>Success - <span class='err'><i>".$Name."</i></span> for <span class='err'><i>".$Business."</i></span> has been added to VL</h3>
<table>
	<tr>
		<td style='padding:5px;'><strong>Business Name:</strong></td>
		<td>".$Business."</td>
	</tr>
	<tr>
		<td style='padding:5px;'><strong>Campaign Name:</strong></td>
		<td>".$Name."</td>
	</tr>
	<tr>
		<td style='padding:5px;'><strong>Landing Page:</strong></td>
		<td>".$LandingPage."</td>
	</tr>
</table>";
/*
echo "<h3>Success - <span class='err'><i>".stripslashes($Name)."</i></span> for <span class='err'><i>".stripslashes($Business)."</i></span> has been added to VL</h3>
<table>
	<tr>
		<td style='padding:5px;'><strong>Business Name:</strong></td>
		<td>".stripslashes($Business)."</td>
		<td><button onclick=\"alert('This Does Not Work Yet')\" style='padding:10px;' >Modify</button></td>
	</tr>
	<tr>
		<td style='padding:5px;'><strong>Campaign Name:</strong></td>
		<td>".stripslashes($Name)."</td>
		<td><button onclick=\"alert('This Does Not Work Yet')\" style='padding:10px;' >Modify</button></td>
	</tr>
	<tr>
		<td style='padding:5px;'><strong>Landing Page:</strong></td>
		<td>".$LandingPage."</td>
		<td><button onclick=\"alert('This Does Not Work Yet')\" style='padding:10px;' >Modify</button></td>
	</tr>
</table>";
 */
?>
