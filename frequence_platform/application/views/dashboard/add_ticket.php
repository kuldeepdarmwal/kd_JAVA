 <body onLoad="startTimer();" onMouseOver="stopTimer();" onMouseOut="startTimer();">
 <div class="wrapper">
	<div class="header">
		<div class="headwrap">
			<a href="/dashboard">
				<img src="/images/vl_logo.gif" width="393" height="86" />
			</a>
			<div class="login">
				<?php include $_SERVER['DOCUMENT_ROOT'].'/dashboard_functions/member-index.php';?>
			</div>
		</div>
	</div>
	<div class="indexflags">
		<div class="container">
			<?php include $_SERVER['DOCUMENT_ROOT'].'/dashboard_functions/index_menu.php'; ?>
			<div class="content" id="ajaxContent">
	

<?php
/*
ehco '
<h2>Add Ticket</h2>
/application/views/dashboard/add_ticket.php <br />
';
 */
//echo 'id: '.$id.'<br />';
?>

<?php 
if($isError || $ticketAlreadyExists)
{
	echo "<h1 class='err'>Error Adding To DB</h1>";

	if(isset($ticketData))
	{
		echo "<h3 class='err'>Ticket Name Already Exists</h3>";
		echo '<table>';
		echo '<tr><th>Ticket Number</th><td>'.$ticketData->TicketID.'</td></tr>';
		echo '<tr><th>Ticket Name</th><td>'.$VLFORM_ticketName.'</td></tr>';
		echo '</table>';
	}

	if($isError == true) 
	{
		foreach($errorMessages as $errorMessage)
		{
			echo $errorMessage.'<br />';
		}
	}
}
else
{
	if(isset($ticketData))
	{
		echo '<table>';
		echo '<tr><th>Ticket ID</th><td>'.$ticketData->TicketID.'</td></tr>';
		echo '<tr><th>Status</th><td>'.$ticketData->Status.'</td></tr>';
		echo '<tr><th>Assigned Business</th><td>'.$ticketData->Business.'</td></tr>';
		echo '<tr><th>Campaign</th><td>'.$ticketData->Campaign.'</td></tr>';
		echo '<tr><th>Ticket Name</th><td>'.$ticketData->TicketName.'</td></tr>';
		echo '<tr><th>Ops Owner</th><td>'.$opsOwner.'</td></tr>';
		echo '<tr><th>Creative Owner</th><td>'.$creativeOwner.'</td></tr>';
		echo '<tr><th>Sales Owner</th><td>'.$salesOwner.'</td></tr>';
		echo '<tr><th>Date Created</th><td>'.date('Y-m-d g:i A',strtotime($ticketData->Date_Created)).'</td></tr>';
		echo '<tr><th>Ticket Type</th><td>'.$VLFORM_ticketName.'</td></tr>';
		echo '<tr><th>Comments</th><td><pre>'.$VLFORM_ticket_comments.'</pre></td></tr>';
		echo '</table>';

		echo "<button onclick='popitup(\"ajax_viewTicket/TicketID/".$ticketData->TicketID."\"); return false;' style='padding:10px;'>View This Ticket</button>";

	}
}

//echo $message;

?>

			</div>
			<div class="clearBoth"></div>
		</div><div class="clearBoth"></div>
	</div>
	<img src="/images/vl_logo.gif" style="width:1px;height:1px;">
	<div class="push"></div>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/dashboard_functions/footer.php'; ?>
</body>
</html>


