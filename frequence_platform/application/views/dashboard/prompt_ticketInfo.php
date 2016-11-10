<?php
/*
echo '
<h2>Promt Ticket Info</h2>
/application/views/dashboard/prompt_ticketInfo.php <br />
';

echo 'source: '.$source.'<br />';
echo 'owner_ops: '.$owner_ops.'<br />';
echo 'owner_creative: '.$owner_creative.'<br />';
echo 'owner_sales: '.$owner_sales.'<br />';
echo 'TicketID: '.$TicketID.'<br />';
echo 'time_now: '.$time_now.'<br />';
echo 'current_user: '.$current_user.'<br />';
 */
?>

 <div class="wrapper">
	<div class="header">
		<div class="headwrap">
			<a href="/dashboard">
				<img src="/images/vl_logo.gif" width="393" height="86" />
			</a>
		</div>
	</div>
	<div class="indexflags">
		<div class="container">
			<div class="content" id="ajaxContent">

	<?php
	//<form action="/dashboard/modify_ticket" method="post" enctype="multipart/form-data" >
?>
	<?php echo form_open_multipart('dashboard/modify_ticket'); ?>

		<div class='ticket-wrapper'>
			<h1>Additional Info:</h1>
			<div class='ticket-infoBox'>
				<div class='ticket-cols'>
					<h1>Ticket Info:</h1>
					<?php
						echo "
							<span class='ticket-name'>Ticket Name:</span>
								<span class='ticket-object'>$TicketName</span>
								<input type='hidden' name='TicketName' value='$TicketName'>
								<input type='hidden' name='TicketID' value='$TicketID'>
							<span class='ticket-name'>Time Now:</span>
								<span class='ticket-object'>$time_now</span>
								<input type='hidden' name='time_now' value='$time_now'>
							<span class='ticket-name'>You:</span>
								<span class='ticket-object'>".$session_username."</span>
								<input type='hidden' name='personUpdating' value='".$session_username."'>

							";
							if(isset($ChangeOfTransition) && $ChangeOfTransition==1)
							{
								echo "
									<input type='hidden' name='ChangeOfTransition' value='$ChangeOfTransition'>
									<span class='ticket-name '>Old Status:</span>
										<span class='ticket-object red'>$Status</span>
										<input type='hidden' name='Status' value='$Status'>
									<span class='ticket-name'>New Status:</span>
										<span class='ticket-object red'>$newStatus</span>
										<input type='hidden' name='newStatus' value='$newStatus'>
									";
							}
							if(isset($ChangeOfOwner) && $ChangeOfOwner==1)
							{
								echo "<input type='hidden' name='ChangeOfOwner' value='$ChangeOfOwner'>";

								if($new_owner_sales == $owner_sales){$sales_color="777777";}else{$sales_color="red";}
								if($new_owner_ops == $owner_ops){$ops_color="777777";}else{$ops_color="red";}
								if($new_owner_creative == $owner_creative){$creative_color="777777";}else{$creative_color="red";}
								
								///This will track the number of owners that were changed.  We use this specifically for the history/comment string generation of proper sentance structure.
								$numberOfOwnerChanges = 0;
								
								if($ops_color=="red")
								{
									echo "
										
										<span class='ticket-name'>Old Ops:</span>
											<span class='ticket-object $ops_color'>".$owner_ops_name."</span>
											<input type='hidden' name='owner_ops' value='$owner_ops'>
										<span class='ticket-name'>New Ops:</span>
											<span class='ticket-object $ops_color'>".$new_owner_ops_name."</span>
											<input type='hidden' name='new_owner_ops' value='$new_owner_ops'>
										";
									$numberOfOwnerChanges++;
								}
								else
								{
									echo "<input type='hidden' name='owner_ops' value='$owner_ops'>";
									echo "<input type='hidden' name='new_owner_ops' value='$owner_ops'>";
								}
								
								if($sales_color=="red")
								{
									echo "
										<span class='ticket-name'>Old Sales:</span>
											<span class='ticket-object $sales_color'>".$owner_sales_name."</span>
											<input type='hidden' name='owner_sales' value='$owner_sales'>
										<span class='ticket-name'>New Sales:</span>
											<span class='ticket-object $sales_color'>".$new_owner_sales_name."</span>
											<input type='hidden' name='new_owner_sales' value='$new_owner_sales'>
										";
									$numberOfOwnerChanges++;
								}
								else
								{
									echo"<input type='hidden' name='owner_sales' value='$owner_sales'>";
									echo"<input type='hidden' name='new_owner_sales' value='$owner_sales'>";
								}
								
								if($creative_color=="red")
								{
									echo "
										<span class='ticket-name'>Old Creative:</span>
											<span class='ticket-object $creative_color'>".$owner_creative_name."</span>
											<input type='hidden' name='owner_creative' value='$owner_creative'>
										<span class='ticket-name '>New Creative:</span>
											<span class='ticket-object $creative_color'>".$new_owner_creative_name."</span>
											<input type='hidden' name='new_owner_creative' value='$new_owner_creative'>
										";
									$numberOfOwnerChanges++;
								}
								else
								{
									echo"<input type='hidden' name='owner_creative' value='$owner_creative'>";
									echo"<input type='hidden' name='new_owner_creative' value='$owner_creative'>";
								}
								echo"<input type='hidden' name='numberOfOwnerChanges' value='$numberOfOwnerChanges'>";
							}
					?>
				</div>
				<div class='ticket-cols'>
				<h1>Leave Comments:</h1>
					<textarea style="width:100%;height:100px;" onkeyup="checkForComments(this.value);" name="comments" id="comments" ></textarea>
				</div>
				<div class='ticket-cols'>
					<h1>Add More Files:
					<select onchange="addMoreFiles(this.value)" name="numberOfFilesBeingUploaded">
						<option value="0">0</option>
						<option value="1">1</option>
						<option value="2">2</option>
						<option value="3">3</option>
						<option value="4">4</option>
						<option value="5">5</option>
						<option value="6">6</option>
						<option value="7">7</option>
						<option value="8">8</option>
						<option value="9">9</option>
						<option value="10">10</option>
					</select>
					</h1>
					<br />
					<div id="howManyUploads">
					</div>
				</div><div class='clearBoth'></div>
			</div><div class='clearBoth'></div>
			<div class='ticket-infoBox'>
				<div class='ticket-cols'>
					<input type="submit" value="UPDATE: '<?php echo $TicketName; ?>'">
				</div>
				<div class='ticket-cols' >
					<ul id='commentChecker'>
						<li><span class='red'>Please Leave A Comment</span></li>
					</ul>
					<ul id='UploadChecker' >
						<li ><span class='green'>No Uploads Selected</span></li>
					</ul>
				</div>
			</div><div class='clearBoth'></div>
		</div><div class='clearBoth'></div>
	</form>
</div>
</div>

