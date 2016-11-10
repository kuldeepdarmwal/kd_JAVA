<?php
/*
echo '
<h2>Modify Ticket</h2>
/application/views/dashboard/modify_ticket.php <br />
';
 */
?>

<body>
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

				<h2 class="green">The ticket has been updated</h2>
				<p>

				</p>
				<button style="padding:10px;" onclick="refreshParent();window.close();"><font size="2" style="color:#000;">Close This Window</font></button>

				<hr />
			
					<div class="modifyTicket-statusUpdate-chooser">
						<?php 
						
							echo "<div class='regConfirmation'>";
							echo "<form action='/dashboard/sendMail' method='post' target='_blank' >";
							echo "<input type='hidden' 		name='email_TicketName' 			id='email_TicketName' 			value='$TicketName' 			/>";
							echo "<input type='hidden' 		name='email_TicketID' 				id='email_TicketID' 			value='$TicketID' 				/>";
							echo "<input type='hidden'	 	name='email_time_now' 				id='email_time_now' 			value='$time_now' 				/>";
							echo "<input type='hidden'		name='email_personUpdating'	 		id='email_personUpdating'	  	value='$personUpdating' 		/>";
							
							
							$sendEmailTo = '';
							if(isset($ChangeOfTransition) && $ChangeOfTransition==1)
							{
								echo "<input type='hidden' 	name='email_ChangeOfTransition' 	id='email_ChangeOfTransition'  	value='$ChangeOfTransition' 	/>";
								echo "<input type='hidden' 	name='email_Status' 				id='email_Status'  				value='$Status' 				/>";
								echo "<input type='hidden' 	name='email_newStatus' 				id='email_newStatus'  			value='$newStatus' 				/>";
								$dynamicContent = $theHistorySentence;
								echo "<input type='hidden' 	name='email_dynamicContent' 		id='email_dynamicContent'  		value='$dynamicContent' 		/>";
								

								
								echo "<span style='float:left;width:100%'>
									<h3>This email will be delivered to:</h3>
									<table>
										<tr><th>".$owner_ops_name."|".$owner_ops."</th><td>Ops</td><td>".$owner_ops_email."</td></tr>
										<tr><th>".$owner_creative_name."|".$owner_creative."</th><td>Creative</td><td>".$owner_creative_email."</td></tr>
										<tr><th>".$owner_sales_name."|".$owner_sales."</th><td>Sales</td><td>".$owner_sales_email."</td></tr>
									</table>
									</span>";	
								$sendEmailTo = $owner_ops_email.", ".$owner_creative_email.", ".$owner_sales_email;
							}
							if(isset($ChangeOfOwner) && $ChangeOfOwner==1)
							{
								echo "<input type='hidden' 	name='email_ChangeOfOwner' 			id='email_ChangeOfOwner'  		value='$ChangeOfOwner' 			/>";
								echo "<input type='hidden' 	name='email_owner_ops' 				id='email_owner_ops'  			value='$owner_ops' 				/>";
								echo "<input type='hidden' 	name='email_new_owner_ops' 			id='email_new_owner_ops'  		value='$new_owner_ops' 			/>";
								echo "<input type='hidden' 	name='email_owner_sales' 			id='email_owner_sales'  		value='$owner_sales' 			/>";
								echo "<input type='hidden' 	name='email_new_owner_sales' 		id='email_new_owner_sales'  	value='$new_owner_sales' 		/>";
								echo "<input type='hidden' 	name='email_owner_creative' 		id='email_owner_creative'  		value='$owner_creative' 		/>";
								echo "<input type='hidden' 	name='email_new_owner_creative' 	id='email_new_owner_creative'  	value='$new_owner_creative' 	/>";
								echo "<input type='hidden' 	name='email_numberOfOwnerChanges'	id='email_numberOfOwnerChanges' value='$numberOfOwnerChanges' 	/>";
								
								$dynamicContent = $theHistorySentence;
								echo "<input type='hidden' 	name='email_dynamicContent' 		id='email_dynamicContent'  		value='$dynamicContent' 		/>";
								
								echo "<span style='float:left;width:100%'>
									<h3>This email will be delivered to:</h3>
									<table>
										<tr><th>".$new_owner_ops_name."|".$new_owner_ops."</th><td>Ops</td><td>".$new_owner_ops_email."</td></tr>
										<tr><th>".$new_owner_creative_name."|".$new_owner_creative."</th><td>Creative</td><td>".$new_owner_creative_email."</td></tr>
										<tr><th>".$new_owner_sales_name."|".$new_owner_sales."</th><td>Sales</td><td>".$new_owner_sales_email."</td></tr>
									</table>
									</span>";	
								$sendEmailTo = $new_owner_ops_email.", ".$new_owner_creative_email.", ".$new_owner_sales_email;
							}
							
							echo "<input type='hidden' 		name='email_comments' 				id='email_comments'				value='$comments' 				/>";
							echo "<input type='hidden' 		name='email_theHistorySentance' 	id='email_theHistorySentance'	value='$theHistorySentence' 	/>";
							
							echo "<input type='hidden' 		name='SESS_EMAIL_ADDR' 				id='SESS_EMAIL_ADDR'  			value='".$session_email."' 	/>";
							echo "<input type='hidden' 		name='email_messageID' 				id='email_messageID'  			value='2' 	/>";
						
							echo "<input type='submit' value='Notify The Owners Of Updates' style='float:left' onclick='refreshParent();'/>";
							
							echo "<input type='hidden' 		name='sendEmailTo' 				id='sendEmailTo'  			value='$sendEmailTo' 	/>";
							//echo "<label><input type='checkbox' name='sendEmailTo' class='modifyTicket-checkbox' value='".mysql_result($query_ShowOwners,0,0)."'>".GetMemberNameByMemberID(mysql_result($query_ShowOwners,0,0),2)."</label><br />";
							//echo "<label><input type='checkbox' name='sendEmailTo' class='modifyTicket-checkbox' value='".mysql_result($query_ShowOwners,0,1)."'>".GetMemberNameByMemberID(mysql_result($query_ShowOwners,0,1),2)."</label><br />";
							//echo "<label><input type='checkbox' name='sendEmailTo' class='modifyTicket-checkbox' value='".mysql_result($query_ShowOwners,0,2)."'>".GetMemberNameByMemberID(mysql_result($query_ShowOwners,0,2),2)."</label><br />";
							//echo "<label><input type='checkbox' class='modifyTicket-checkbox' onClick='checkall(this,\"sendEmailTo\");'>All Assigned To Ticket</label><br />";
						echo '</form>';

						for($i=0;$i<$numberOfFilesBeingUploaded;$i++)
						{
							$theFile = "uploadedfile$i";
							
							echo "
								<div class='ticket-modify-confirmation-upload'>
									<center>
										<h2>Upload ".($i+1)."</h2>";


							if($uploadFileData[$i] != false)
							{
								$genericFileName = $genericFileNames[$i];
								$uploadData = $uploadFileData[$i];
								$file_ext = $uploadData['file_ext'];
								$fileName = $uploadData['file_name'];
								echo "Uploaded \"$fileName\" as: $genericFileName";
								echo "
									</center>
									<div id='confirmation_upload$i'></div>
									";
								/*
								echo "
										<input type='text' id='upload$i' value='$genericFileName' onclick='this.select();'/>
										<button onclick='changeFileName(\"$rootTicketDirectory\",\"$genericFileName\",document.getElementById(\"upload$i\").value,\"$file_ext\",\"confirmation_upload$i\")' >Rename This File</button>
									</center>
									<div id='confirmation_upload$i'></div>
									";
									*/
							}
							else
							{
								$uploadData = $uploadFileData[$i];
								$fileName = $uploadData['file_name'];
								echo "
									Failed to upload: $fileName
									";
								echo "
									</center>
									<div id='confirmation_upload$i'></div>
									";
							}
							echo "
								</div>";
						}

					?>
					</div>
					<div>
						<?php
							if($isError)
							{
								foreach($errorMessages as $errorMessage)
								{
									echo $errorMessage.'<br />';
								}
							}
						?>
					</div>
			</div>
			<div class="clearBoth"></div>
		</div><div class="clearBoth"></div>

</div>
</body>
</html>










