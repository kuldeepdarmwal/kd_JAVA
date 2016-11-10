<div id="user-pop-up">
   <!-- role/superuser pop up indicator that is hidden but fades in/out when the name is clicked -->
   <?php echo '<span style="font-weight: bold;"> '.$username. '</span><br>';?> 
   <?php if ($isGroupSuper == 1){echo $role.' '.'(superuser)'. '<br>'.$business_name;}?><br/>
   <a href="/change_password">change password</a><br>
   <a href="/auth/logout">logout</a> 
  </div>
 
   <div id="left_menu">
   <ul id="nav">
   <li>
   <?php 

     echo '<a href="#"><span id=span>Application</span> <img id="arrow" src="'.base_url("ring_files/images/arr2.png").'"  class="arrmenu"/>  </a>';

?>
<ul id="sublist">
   <?php 
   switch($role) {
   case "business":
   echo  '<li><a name="drop" href="'.site_url("report").'" id="campaigns">Campaigns</a></li>';
   break;
   case "creative":
   echo '<li><a name="drop" href="'.site_url("dashboard").'" id="tickets">Tickets</a></li>';
   break;
   case "sales":
   echo '<!--<li><a name="drop" href="#" id="marketing">Marketing</a></li>-->';
   if ($isGroupSuper == 1){
     echo '<!--<li><a name="drop" href="#" id="partners">Partners</a></li>-->
     <!--<li><a name="drop" href="#" id="smb">SMB</a></li>-->';
   }
   echo '<!--<li><a name="drop" href="#" id="proposals">Proposals </a></li>-->
   <!--<li><a name="drop" href="#" id="lap">Local Ad Plans</a></li>-->
   <li><a name="drop"  href="'.site_url("dashboard").'" id="tickets">Tickets</a></li>
   <li><a name="drop" href="'.site_url("report").'" id="campaigns">Campaigns</a></li>';
   
   if ($isGroupSuper == 1){
     echo '<!--<li><a name="drop" href="#" id="admin">Admin</a></li>-->';
   }		
   break;
   case "design":
   redirect('dashboard/design');
   break;
   case "ops":
   echo '<li><a name="drop" href="#" id="adops">Ad Ops</a></li>
   <!--<li><a name="drop" href="#" id="marketing">Marketing</a></li>-->
   <li><a name="drop" href="#" id="partners">Partners</a></li>
   <!--<li><a name="drop" href="#" id="proposals">Proposals </a></li>-->
   <li><a name="drop" href="#" id="lap">Local Ad Plans</a></li>
   <li><a name="drop" href="'.site_url("dashboard").'" id="tickets">Tickets</a></li>
   <li><a name="drop" href="'.site_url("report").'" id="campaigns">Campaigns</a></li>
   <!--<li><a name="drop" href="#" id="admin">Admin</a></li>-->
   <li><a name="drop" href="#" id="smb">SMB</a></li>';
   break;
   case "admin":
   echo '<li><a name="drop" href="#" id="techops">Tech Ops</a></li>
   <li><a name="drop" href="#" id="adops">Ad Ops</a></li>
   <!--<li><a name="drop" href="#" id="marketing">Marketing</a></li>-->
   <li><a name="drop" href="#" id="partners">Partners</a></li>
   <li><a name="drop" href="generator">Generator</a></li>
   <!--<li><a name="drop" href="#" id="proposals">Proposals </a></li>-->
   <li><a name="drop" href="#" id="lap">Local Ad Plans</a></li>
   <li><a name="drop" href="'.site_url("dashboard").'" id="tickets">Tickets</a></li>
   <li><a name="drop" href="'.site_url("report").'" id="campaigns" >Campaigns</a></li>
   <!--<li><a name="drop" href="#" id="admin">Admin</a></li>-->
   <li><a name="drop" href="#" id="smb">SMB</a></li>';
   break;
   default:
   die("Unknown role: ".$role);
   }
   ?>
   </ul>
   </li>
   </ul>
   <ul id="main_menu" class="main_menu">
   <div id="partners" name="sub" >
   <?php 
     echo '<li class="limenu" ><a href="'.site_url("register").'">Create Partner Login</a></li>';
     echo '<!--<li class="limenu"><a href="'.site_url("dashboard").'">Dashboard</a></li>
      <li class="limenu" ><a href="'.site_url("dashboard").'">New Partner Organization</a></li>-->';
?>
</div>
<div id="proposals" name="sub">
   <li class="limenu"><a href="#">Dashboard</a></li>
   <li class="limenu" ><a href="#">New Proposal</a></li>
   <li class="limenu" ><a href="#">New Page</a></li> 
   </div>
   <div id="lap" name="sub">
   <li class="limenu"><a href="lap">Dashboard</a></li>
   <li class="limenu" ><a href="lap">New Local Ad Plan</a></li>
   </div>
   <div id="tickets" name="sub">
   <?php 
   if ($role == 'creative') {
     echo ' <li class="limenu"><a href="'.site_url("dashboard").'">Dashboard</a></li>';
   } else {
     echo ' <li class="limenu"><a href="'.site_url("dashboard").'">Dashboard</a></li>
      <!--<li class="limenu" ><a href="#">New Tickets</a></li>-->';}
?>
</div>
<div id="generator" name="sub">
  <li class="limenu"><a href="#" onClick="deploy_dashboard();">Dashboard</a></li>
  <li class="limenu"><a href="#" onClick="deploy_new_proposal();">New Proposal</a></li>
  <li class="limenu"><a href="#" onClick="alert('myman');">New Page</a></li>
</div>
<div id="campaigns" name="sub">
   <?php 
   if ($role == 'business') {
     echo ' <li class="limenu"><a href="'.site_url("report").'">Reports</a></li>';

   } else if($role !='sales'){
     echo ' <!--<li class="limenu"><a href="'.site_url("dashboard").'">Dashboard</a></li>-->
      <li class="limenu" ><a href="'.site_url("report").'">Reports</a></li>';}
   else{ echo '<li class="limenu" ><a href="'.site_url("report").'">Reports</a></li> ';}
?>
</div>
<div id="admin" name="sub">
   <?php 
   if ($role == 'admin' || ($role == 'sales' && $isGroupSuper == 1)){
     echo '<li class="limenu" ><a href="#">Pricing</a></li>';
   }
   ?>
   <li class="limenu"><a href="#">Health check</a></li>
   <li class="limenu" ><a href="#">Forms TBD</a></li>
   <li class="limenu" ><a href="#">Forms TBD</a></li>
</div>
<div id="smb" name="sub">
   <li class="limenu"><a href="#" id="geo" onClick="paint_geo();slideOut();">Geo Targeting</a></li>
   <li class="limenu"><a href="#" onClick="$('span#geospan').css('display', 'none');
   $('span#notgeo').css('display', 'inline');$('div.gridRight').css('display', 'none');
showMediaTargeting();document.getElementById('ghost_title').innerHTML='Media Targeting';">Media Targeting</a></li>
   <li class="limenu"><a href="#" onClick="$('span#geospan').css('display', 'none');$('div.gridRight').css('display', 'inline');
   $('span#notgeo').css('display', 'inline');
ShowReachFrequency();document.getElementById('ghost_title').innerHTML='Reach Frequency';">Reach Frequency</a></li>
   <!--<li class="limenu"><a href="#">Reach Frequency & ROI</a></li>
   <li class="limenu"><a href="#">Retargeting</a></li>
   <li class="limenu"><a href="#">Optimization</a></li>-->
   <li class="limenu"><a href="#" onClick="deploy_creative_demo();document.getElementById('ghost_title').innerHTML='Ad Library';">Creative Library</a></li>
   <li class="limenu"><a href="#" onclick="deploy_campaign();document.getElementById('ghost_title').innerHTML='Campaign Setup';">Deploy Campaign</a></li>
</div>
   <div id="adops" name="sub">
   <li class="limenu" ><a href="<?php echo site_url('q_scrape'); ?>">Demographic Scraper</a></li>
   <li class="limenu" ><a href="#">Data Feed</a></li>
   <li class="limenu" ><a href="#">Cookie Management</a></li>
   <li class="limenu" ><a href="#">Search Retargeting Management</a></li>
   <li class="limenu" ><a href="<?php echo site_url('couponadmin'); ?>">Coupon</a></li>
</div>
<div id="techops" name="sub">
   <li class="limenu" ><a href="<?php echo site_url('rollout'); ?>">Rollout</a></li>
   <li class="limenu" ><a href="#">System Health</a></li>
   </div>
   <div id="marketing" name="sub">
   <li class="limenu" ><a href="#">Seo/Sem Analytics</a></li>
   <li class="limenu" ><a href="#">Funnel Metrics</a></li>
   </div>
   </ul>		       			       
</div>

   <script type="text/javascript" src="/ring_files/js/navbar.js" ></script>   
   <script type="text/javascript" src="/ring_files/js/generator_utils.js"> </script>
