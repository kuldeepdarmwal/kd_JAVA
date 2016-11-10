<div id="user-pop-up">
  <!-- role/superuser pop up indicator that is hidden but fades in/out when the name is clicked -->
  <?php echo '<span style="font-weight: bold;"> '.$username. '</span><br>';?> 
  <?php if ($isGroupSuper == 1){echo $role.' '.'(superuser)'. '<br>'.$business_name;}?></br>
  <a href="/change_password">change password</a><br>
  <a href="/auth/logout">logout</a> 
  </div>
 
	<div id="left_menu">
		<ul id="nav" style="display:none;">
			<li>
				<?php 
				echo'<a href="#"><span id=span>SMB</span> <img id="arrow" src="'.base_url("ring_files/images/arr2.png").'"  class="arrmenu"/>  </a>';
				?>

				<ul id="sublist">
					<?php 
					switch($role) 
					{
						case "sales":
							echo '<li><a name="drop" href="#" id="smb">SMB</a></li>';
						break;
						default:
							echo '<li><a name="drop" href="#" id="smb">SMB</a></li>';
							//die("Not Sales User: ".$role);
					}
					?>
				</ul>
			</li>
		</ul>

		<ul id="main_menu" class="main_menu">
			<div id="smb" name="sub">
				<li class="limenu">
					<div class="ColorSwatch" id="GeoTargetingMenuItem">
					</div>
					<a href="#" id="geo" onClick="paint_geo();">
						Geo Targeting
					</a>
				</li>

				<li class="limenu">
					<div class="ColorSwatch" id="MediaTargetingMenuItem">
					</div>
					<a href="#" onClick=" $('span#geospan').css('display', 'none'); $('span#notgeo').css('display', 'inline'); showMediaTargeting();document.getElementById('ghost_title').innerHTML='Media Targeting'; slideIn(function(src){},'dummy'); ">
						Media Targeting
					</a>
				</li>

				<li class="limenu">
					<div class="ColorSwatch" id="ReachFrequencyMenuItem">
					</div>
					<a href="#" onClick="$('span#geospan').css('display', 'none'); $('span#notgeo').css('display', 'inline');ShowReachFrequency();">
						Reach Frequency
					</a>
				</li>
                                <!--
				<li class="limenu">
					<div class="ColorSwatch" id="CreativeLibraryMenuItem">
					</div>
					<a href="#" onClick="$('span#geospan').css('display', 'none'); $('span#notgeo').css('display', 'inline');deploy_creative_demo();document.getElementById('ghost_title').innerHTML='Ad Library';">
						Creative Library
					</a>
				</li>
                                -->
				<li class="limenu">
					<div class="ColorSwatch" id="SaveLapMenuItem">
					</div>
					<a href="#" onClick="deploy_lap_summary();">
						Save LAP
					</a>
				</li>
			</div>
		</ul>		       			       
	</div>

  <script type="text/javascript" src="/ring_files/js/navbar.js" ></script>    
	<script type="text/javascript">
		function NavbarShowGeo() 
		{
                        document.getElementById("notes_header_geo").innerHTML = '<span></span>';
                        document.getElementById('notes_body_geo').innerHTML = '<div style="width:400px;margin-left:auto;margin-right:auto;"><h3>Advertiser</h3>'+
			'<input type="text" id="advertiser_geo" name="advertiser" style="margin-left:10px;" />' +
			'<h3>Ad Plan Name</h3>' +
			'<input type="text" id="plan_name_geo" name="plan_name" style="margin-left:10px;" />' + 
			'<h3>Notes</h3>' +
			'<textarea id="notes_geo" name="notes_geo" style="height:50px;width:80%;margin-left:10px;"></textarea><br><br>'+
			'<button style="" id="submitButton_geo" onClick="save_lap_geo();" style="width:80px;height:30px;margin-left:40px;">Submit</button>'+
			'<button style="" id="backButton_geo" onClick="lap_back_geo();" style="width:80px;height:30px;margin-left:40px;">Cancel</button>'+
			'<button style="" id="saveasButton_geo" onclick="saveas_lap_geo();" style="width:80px;height:30px;margin-left:40px;">Save As New</button>'+
			'</div><span stlye="color:#FF0000;" id=\'errorspan_geo\'></span>'+
			'<p id="notes_notif_geo" style="color:#808080"></p>'; 
			ShowGeo();
			flexigrid('true');
			paint_geo(); 
		}

	 	window.onload=NavbarShowGeo;
	</script>
