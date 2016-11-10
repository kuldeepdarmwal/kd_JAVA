<?php
/*
echo '
<h2>Dashboard</h2>
/application/views/dashboard/tool_viewDashboard.php <br />
';
 */
//echo $tool;
?>
 <body>
 <div class="wrapper">
	<div class="header">
		<div class="headwrap">
			<a href="/toolkit">
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
			<div style='float:right;margin-right:20px;'>
						<table>
							<tr >
								<td width='57' class='helpHedOps' >Ops</td>
								<td width='57' class='helpHedCreative'>Creative</td>
								<td width='57' class='helpHedSales'>Sales</td>
							</tr>
						</table>
					</div>
				<h2>Ticket Dashboard</h2>
				<div class='status-TicketType'>
					<form action='' method='post' style="float:left;">
						<?php
							
							$TheNumberOfTicketTypes = $result_TicketType->num_rows();
							//echo 'NumTicketTypes: '.$TheNumberOfTicketTypes.'<br />';
						?>
						<div class='status-TicketControls'>
							<select id="TicketType" name="TicketType" onchange=populateDashboard(this.options[this.selectedIndex].value)>

								<?php
									for($i=0;$i<$TheNumberOfTicketTypes;$i++)
									{
										$theRow = $result_TicketType->row($i);
										$ticketTypeName = $theRow->TicketType;
										echo "<option value='".$ticketTypeName."'>".$ticketTypeName."</option>";
									}
								?>

							<!--<option value='LANDING PAGE'>Landing Pages</option>-->
							</select>
							<img src='/images/refresh.png' alt='Refresh Page' title='Refresh Page' width='50px' onclick='populateDashboard(getElementById("TicketType").value)'/>
							<!--<input type='text' value='Search By Ticket Name'/>-->
						</div>
					</form>
				</div><div class='clearBoth'></div>
				<div id="DashboardDiv">
					<?php 
						$this->load->view('dashboard/get_dashboard');
						//include $_SERVER['DOCUMENT_ROOT'].'/dashboard_functions/get_dashboard.php';
					?>
				</div><div class='clearBoth'></div>
			</div>
			<div class="clearBoth"></div>
		</div><div class="clearBoth"></div>
	</div>
	<div class="push"></div>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/dashboard_functions/footer.php'; ?>
</body>
</html>



