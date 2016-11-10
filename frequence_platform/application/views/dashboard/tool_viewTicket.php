<?php
/*
echo '
<h2>Tool View Ticket</h2>
/application/views/dashboard/tool_viewTicket.php <br />
';
 */
?>
<?php
//echo '$result_stateOwnerCount: '.$result_stateOwnerCount.'<br />';
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

<?php
	echo "<div class='ticket-wrapper ".$background."'>";
		//echo "<div class='ticket-header' >Business: ".$BusinessName." Campaign: ".$CampaignName." Ticket Type: ".$TicketState."</div>";
		echo "<div class='ticket-infoBox $border'>";
			echo "<div class='ticket-cols'>";
				echo "<h1>Summary</h1>";

				echo "<span class='ticket-name'>Ticket Name</span> 
					<span class='ticket-object' id='TicketName'>".$TicketName."</span><br />";	
				echo "<span class='ticket-name'>State</span> 
					<span class='ticket-object'>$TicketState</span><br />";
				echo "<span class='ticket-name'>Responsible</span>
					<span class='ticket-object'>".$result_stateOwnerFirstName." ".$result_stateOwnerLastName."</span><br />";
				echo "<span id='current_user' style='visibility:hidden'>".$id."</span>";
				echo "<span class='ticket-name' style='visibility:hidden'>Ticket ID</span> 
					<span class='ticket-object' id='TicketID' style='visibility:hidden'>".$TicketID."</span><br />";				
			echo "</div>";
//TIMERS//TIMERS//TIMERS//TIMERS//TIMERS//TIMERS//TIMERS//TIMERS//TIMERS//TIMERS//TIMERS
			echo "<div class='ticket-cols'>";
				echo "<h1>Timers</h1>";
				echo "<span class='ticket-name'>Created</span>
					<span class='ticket-object'>".date('Y-m-d g:i A', strtotime($Date_Created))."</span><br />";
					
				echo "<span class='ticket-name'>Modified</span>
					<span id='time_modified' class='ticket-object'>".date('Y-m-d g:i A', strtotime($Date_Modified))."</span><br />";
					
				echo "<span class='ticket-name'>Global</span>
					<span class='ticket-object'>".round($global_master_dif,1)." days</span><br />";
					
				echo "<span class='ticket-name'>State</span>
					<span class='ticket-object'>".round($state_master_dif,1)." days</span><br />";
				echo "<span class='ticket-name' style='visibility:hidden'>Today</span>
					<span id='time_now' class='ticket-object' style='visibility:hidden'>".$today."</span><br />";
			echo "</div>";

			echo "<div class='ticket-cols'>";
				echo "<h1>Owners</h1>";
				echo "<div class='ticket-dropdown'>";
					echo "<span class='ticket-dropdown-team ";
						if($result_stateOwnerRole == 'OPS'){echo 'green';}
						echo "'>OPS</span>";
						echo "<select  id='owner_ops' class='ticket-dropdown-select'>";
							$result_currentOwner = $result_opsCurrentOwner->row();
							echo "<option value='".$result_currentOwner->id."'>".$result_currentOwner->firstname." ".$result_currentOwner->lastname."</option>";
					
							
							$rows_opsMemberList = $result_opsMemberList->num_rows();
							for($i=0;$i<$rows_opsMemberList;$i++){
								$row_opsMemberList = $result_opsMemberList->row($i);
								echo "<option value='".$row_opsMemberList->id."'>".$row_opsMemberList->firstname." ".$row_opsMemberList->lastname."</option>";
							}
							
					echo "</select>";
				echo "</div>";

				echo "<div class='ticket-dropdown'>";
					echo "<span class='ticket-dropdown-team ";
						if($result_stateOwnerRole == 'CREATIVE'){echo 'green';}
						echo "'>CREATIVE</span>";
						echo "<select id='owner_creative' class='ticket-dropdown-select'>";
							//$sql_currentOwner = "SELECT firstname, lastname, id FROM members WHERE role IN ('CREATIVE') AND member_id = ".$owner_creative."";
							$result_currentOwner = $result_creativeCurrentOwner->row();
							echo "<option value='".$result_currentOwner->id."'>".$result_currentOwner->firstname." ".$result_currentOwner->lastname."</option>";
					
							//$sql_opsMemberList = "SELECT firstname, lastname, member_id FROM members WHERE role IN ('CREATIVE') AND member_id != $owner_creative";
							//$result_opsMemberList = mysql_query($sql_opsMemberList);
							$rows_creativeMemberList = $result_creativeMemberList->num_rows();
							for($i=0;$i<$rows_creativeMemberList;$i++)
							{
								$row_creativeMemberList = $result_creativeMemberList->row($i);
								echo "<option value='".$row_creativeMemberList->id."'>".$row_creativeMemberList->firstname." ".$row_creativeMemberList->lastname."</option>";
							}
					echo "</select>";
				echo "</div>";

				echo "<div class='ticket-dropdown'>";
					echo "<span class='ticket-dropdown-team ";
						if($result_stateOwnerRole == 'SALES'){echo 'green';}
						echo "'>SALES</span>";
						echo "<select id='owner_sales' class='ticket-dropdown-select'>";
							//$sql_currentOwner = "SELECT firstname, lastname, member_id FROM members WHERE role IN ('SALES') AND member_id = ".$owner_sales."";
							//$result_currentOwner = mysql_query($sql_currentOwner);
							
							$result_currentOwner = $result_salesCurrentOwner->row();
							echo "<option value='".$result_currentOwner->id."'>".$result_currentOwner->firstname." ".$result_currentOwner->lastname."</option>";
							
							//$sql_salesMemberList = "SELECT firstname, lastname, member_id FROM members WHERE role IN ('SALES') AND member_id != $owner_sales";
							//$result_salesMemberList = mysql_query($sql_salesMemberList);
							$rows_salesMemberList = $result_salesMemberList->num_rows();
							for($i=0;$i<$rows_salesMemberList;$i++)
							{
								$row_salesMemberList = $result_salesMemberList->row($i);
								echo "<option value='".$row_salesMemberList->id."'>".$row_salesMemberList->firstname." ".$row_salesMemberList->lastname."</option>";
							}
					echo "</select>";
				echo "</div>";
//UPDATE OWNER//UPDATE OWNER//UPDATE OWNER//UPDATE OWNER//UPDATE OWNER//UPDATE OWNER
				echo "<input class='cool_button_$result_stateOwnerRole' type='submit' value='UPDATE / ADD NOTES' onclick=\"ticket_updateOwners(); return false;\"><span id='owner_update_status' class='ticket-update'></span>";
			echo "</div><div class='clearBoth'></div>";
		echo "</div><div class='clearBoth'></div>";

		// Action
		echo "<div class='ticket-infoBox $border'>";
			echo "<div class='ticket-cols'>";
				echo "<h1>Action:</h1>";
				//$sql_nextSteps = "SELECT final_state, button_copy, history_copy  FROM D_TRANSITIONS WHERE initial_state = '".$TicketState."'";
				//echo $sql_nextSteps;
				//$result_nextSteps = mysql_query($sql_nextSteps);
				$rows_nextSteps = $result_nextSteps->num_rows();
				
				for($i=0;$i<$rows_nextSteps;$i++)
				{
					$row_nextSteps = $result_nextSteps->row($i);
					echo "
						<div class='height-50px'>";
					if($canModifyTicketState == true)
					{
					echo "
							<input class='cool_button_$result_stateOwnerRole' type='submit' value='".$row_nextSteps->button_copy."' title='".$row_nextSteps->button_copy."'onclick='changeTicketStatus(\"".$row_nextSteps->final_state."\")'/>
							";
					}
					else
					{
						echo "".$row_nextSteps->button_copy;
					}
					echo "
						</div>";
				}
				
			echo "<div id='ticketStatusModified' class='ticket-update'></div>
				</div>";
		
			echo "<div class='ticket-cols'>";
				echo "<h1>Next State:</h1>";
				for($i=0;$i<$rows_nextSteps;$i++)
				{
					$row_nextSteps = $result_nextSteps->row($i);
					echo '<div class="ticket-nextState">';
						$nextState[$i] = $row_nextSteps->final_state;
						echo $nextState[$i];
					echo '</div>';
					
				}
				//echo "<input type='text' id='actionComments' onclick=\"this.value='';\" value='Enter Comments Here' >";
			echo "</div>";
		
			echo "<div class='ticket-cols'>";
				echo "<h1>Role:</h1>";
				for($i=0;$i<$rows_nextSteps;$i++)
				{	
					echo '<div class="ticket-nextState">';
						//$sql = "SELECT Role FROM D_TICKETSTATUSES WHERE Status = '".$nextState[$i]."'";
						$result_nextRoleName = $result_nextRoles[$i];
						$row_nextRoleName = $result_nextRoleName->row();
						echo $row_nextRoleName->Role;
					echo "</div>";	
				}
				
			echo "</div><div class='clearBoth'></div>";
		echo "</div><div class='clearBoth'></div>";

		echo "<div class='ticket-infoBox $border'>";
			echo "<div class='ticket-cols'>";
				echo "<h1>Uploads:</h1>";
					echo "<div class='ticket-cols-uploads-list'>";
						$baseDirectory = $_SERVER['DOCUMENT_ROOT'];
						$theTicketDirectory = $baseDirectory."/tickets/Ticket_".$TicketID."/";
						$urlDirectory = "/tickets/Ticket_".$TicketID."/";
							//echo $theTicketDirectory."<br />";
							if ($handle = @opendir($theTicketDirectory)) 
							{
								$thelist = "";
								while (false !== ($file = readdir($handle)))
								{
								  if ($file != "." && $file != "..")
									{
										$file_ext = substr($file, strripos($file, '.'));
										$thelist .= '<a target="_blank" href="'.$urlDirectory.$file.'" onmouseover="imagePreview(\''.$urlDirectory.$file.'\',\''.$file_ext.'\',\''.$baseDirectory.'\')" onclick="imagePreview(\''.$urlDirectory.$file.'\',\''.$file_ext.'\',\''.$baseDirectory.'\')">'.$file.'</a><br />';
										
									}
								}
								closedir($handle);
							}
?>
							<p>
								<?php 
									if(isset($thelist))
									{
										echo $thelist; 
									}
									else
									{
										echo "<font class='err'>Either the directory '$theTicketDirectory' does not exist or the directory is empty</font>";
									}
								?>
							</p>
					
	<?php
					echo "</div>";
				echo "</div>";
				echo "<div class='ticket-2cols'>";
					echo "<h1>Image Preview</h1>";
					echo "<div id='uploadImagePreview'>";
					echo "</div>";
			echo "</div>";
			echo "<div class='ticket-cols'>";

			echo "</div>";
			echo "<div class='clearBoth'></div>";
		
		echo "</div><div class='clearBoth'></div>";

		echo "<div class='ticket-infoBox $border'>";
				echo "<div class='ticket-cols-wide'>";
					echo "<h1>History</h1>";
				echo "</div><div class='clearBoth'></div>";
				echo "<div class='ticket-cols-wide'>";
				/*
					$sql_ticketComments = "
						SELECT 
							thist.Date_Created, 
							mem.firstname, 
							mem.lastname, 
							thist.Comments, 
							thist.member_id 
						FROM F_TICKETHISTORY thist 
							JOIN members mem ON mem.member_id = thist.member_id 
						WHERE thist.TicketID = '".$TicketID."' 
						ORDER BY 1 DESC";
				 */
					$rows_ticketComments = $result_ticketComments->num_rows();
					for($i=0;$i<$rows_ticketComments;$i++)
					{
						$ticketCommentName = $ticketCommentNames[$i];
						$row_ticketComments = $result_ticketComments->row($i);

						//$commenterAvatarURL = $_SERVER['DOCUMENT_ROOT']."/dashboard/uploads/mem_".$row_ticketComments->member_id."/avatar_".$row_ticketComments->member_id.".png";
						$commenterAvatarURL = 'https://s3.amazonaws.com/brandcdn-assets/avatars/avatar_'.$row_ticketComments->member_id.'.png';
                                                
                                                echo "<div class='ticket-comment'>
								<div class='ticket-comment-avatar'>
									<img src='$commenterAvatarURL' alt=' ' title='".$ticketCommentName."' width='60'/>									
									<!--<br />
									<img src='img/logo-google-gmail.png' alt='gMail' width='20'/>
									<img src='img/logo-google-talk.png' alt='gChat' width='20'/>
									<img src='img/logo-google-voice.png' alt='gVoice' width='20'/>-->
									<br />
									<a href='' title='Send Email To: ".$ticketCommentName."'>".$ticketCommentName."</a> 
								</div>
								<div class='ticket-comment-text'>
									<pre>".$row_ticketComments->Comments."</pre>
								</div>
							</div>";
					}
				echo "</div><div class='clearBoth'></div>";
			echo "</div><div class='clearBoth'></div>";
		echo "</div><div class='clearBoth'></div>";
?>

</div>
</div>
</body>
</html>

