<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
   <head>
   <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
   <title><?php echo $partner_name; ?> - Web Report</title>
   <link rel="shortcut icon" href="<?php echo $favicon_path; ?>">
   <link href='https://fonts.googleapis.com/css?family=Lato:400,700,900' rel='stylesheet' type='text/css'>
   <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
   <link rel="stylesheet" href="/js/js_calendar/development-bundle/themes/ui-darkness/jquery.ui.all.css">
   <link href="<?php echo $css; ?>" rel="stylesheet" type="text/css" />
   <script src="/js/js_calendar/development-bundle/jquery-1.6.2.js"></script>
   <script src="/js/js_calendar/development-bundle/ui/jquery.ui.core.js"></script>
   <script src="/js/js_calendar/development-bundle/ui/jquery.ui.widget.js"></script>
   <script src="/js/js_calendar/development-bundle/ui/jquery.ui.datepicker.js"></script>

   <script>
   $(function() {
       $( "#datepicker1" ).datepicker({dateFormat: 'yy-mm-dd'});
     });
$(function() {
    $( "#datepicker2" ).datepicker({dateFormat: 'yy-mm-dd'});
  });
$(function() {
    $( "#datepicker3" ).datepicker({dateFormat: 'yy-mm-dd'});
  });

</script>

<script type="text/javascript">



  function today_month(){
  d = new Date();
  mo = d.getMonth();
  month_name = new Array();
  month_name[0]= "JAN";
  month_name[1]= "FEB";
  month_name[2]= "MAR";
  month_name[3]= "APR";
  month_name[4]= "MAY";
  month_name[5]= "JUN";
  month_name[6]= "JUL";
  month_name[7]= "AUG";
  month_name[8]= "SEP";
  month_name[9]= "OCT";
  month_name[10]= "NOV";
  month_name[11]= "DEC";
  return month_name[mo];
}
function today_day(){
  d = new Date();
  return d.getDate();
}

</script>
<script>

function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
							  if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
  }

function MM_swapImgRestore() { //v3.0
  var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
}

function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_swapImage() { //v3.0
  var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
								    if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}
</script>


<script type="text/javascript">
  $(document).ready(function(){
      $(".pagetitle1").click(function(){
	  $(".panel1").slideToggle("fast");
	});
      $(".pagetitle2").click(function(){
	  $(".panel2").slideToggle("fast");
	});
      $(".pagetitle3").click(function(){
	  $(".panel3").slideToggle("fast");
	});
      $(".pagetitle4").click(function(){
	  $(".panel4").slideToggle("fast");
	});
    });
function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
							  if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}

function MM_swapImgRestore() { //v3.0
  var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
}

function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_swapImage() { //v3.0
  var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
								    if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}
function set_campaign(str)
{
  if (str=="")
    {
      document.getElementById("selectdisp").innerHTML="No Campaigns Available";
      return;
    }
  else
    {
      //alert("1");
      xmlhttp = new XMLHttpRequest();
      //alert("2");
      xmlhttp.open("GET", "webr/get_campaign/"+str, false);
      //alert("3");
      xmlhttp.send();
      //alert(str);
      document.getElementById("selectdisp").innerHTML=xmlhttp.responseText;
      //alert("5");
    }
}
</script>
</head>
<body onload="MM_preloadImages('/images/HEAD_GEO-on.gif','/images/HEAD_DEMO-on.gif','/images/HEAD_PLACEMENT-on.gif','/images/HEAD_SUMMARY-on.gif')">
  <!-- Start Open Web Analytics Tracker -->
  <script type="text/javascript">
  //<![CDATA[
  var owa_baseUrl = 'http://www.vantagelocal.com/owa/';
var owa_cmds = owa_cmds || [];
owa_cmds.push(['setSiteId', 'bb2ea80045ed33d12517f7ded0b46ce6']);
owa_cmds.push(['trackPageView']);
owa_cmds.push(['trackClicks']);
owa_cmds.push(['trackDomStream']);

(function() {
  var _owa = document.createElement('script'); _owa.type = 'text/javascript'; _owa.async = true;
  owa_baseUrl = ('https:' == document.location.protocol ? window.owa_baseSecUrl || owa_baseUrl.replace(/http:/, 'https:') : owa_baseUrl );
  _owa.src = owa_baseUrl + 'modules/base/js/owa.tracker-combined-min.js';
  var _owa_s = document.getElementsByTagName('script')[0]; _owa_s.parentNode.insertBefore(_owa, _owa_s);
}());
//]]>
</script>
<!-- End Open Web Analytics Code -->

<div class="header">
  <div class="headerwrap">
  <div class="headlogobar">
  <div class="logobar_left"><img class="logo_img" src="<?php echo $logo_path; ?>" /></div>
  <div class="logobar_right" align="right">
  <div class="logobar_custinfo">Welcome<strong> <span class="logobar_name"><?php echo $username; ?><br />
  </span></strong><span class="head_name"><?php echo $display_name; ?></span><strong><span class="logobar_name"><br />
  <a href="/change_password" style="font-size: 11px; color:#555;font-weight: normal;">change password</a><br>
			
  </span></strong><a href="auth/logout"><img src="/images/LOGOUT.gif" width="85" height="28" style="border:none"/></a></div>
  </div>
  </div><!--END LOGO BAR -->
  
  
  <?php
  /*
    If the submit button was hit, this code will be executed.  
    It checks to make sure that a campaign, startdate and enddate variable made it into the POST array. Then it has two functions for either business users or other type of user.
    We don't need to check if they selected a business name if the user is a business users since we just use their business for that
    If admin, sales, etc it checks for the business selected
    
    In both cases it sets session data for use in the graphs.
    
  */
  if(array_key_exists('generate', $_POST))
    {
      if(array_key_exists('campaigns', $_POST) and array_key_exists('startdate', $_POST) and array_key_exists('enddate', $_POST))
	{
	  if($role == 'business')
	    {
	      $this->session->set_userdata('businessName', $business_name);
	      $this->session->set_userdata('campaignName',$_POST['campaigns']);
	      $this->session->set_userdata('endDate',date("Y-m-d", strtotime($_POST['enddate'])));
	      $this->session->set_userdata('startDate',date("Y-m-d", strtotime($_POST['startdate'])));
	    }
	  else if(array_key_exists('businesses', $_POST))
	    {
	      $this->session->set_userdata('businessName',$_POST['businesses']);
	      $this->session->set_userdata('campaignName',$_POST['campaigns']);
	      $this->session->set_userdata('endDate',date("Y-m-d", strtotime($_POST['enddate'])));
	      $this->session->set_userdata('startDate',date("Y-m-d", strtotime($_POST['startdate']))); 
	    }
	}
      //echo "GOT HERE";
    }
  else
    {
      
    }
?>
<div class="headforms">
  <?php echo form_open($this->uri->uri_string()); ?>
  <div class="headform1">
  <div class="head_name"><strong>Advertiser Name</strong><br />
  <?php //if ($role == 'BUSINESS') { echo $business_name;}
  if ($role == 'sales')
    {
      echo '<select name="businesses" id="select" onchange=set_campaign(this.value)>';
      foreach ($businesses as $bprinter)
	{
	  if ($bprinter['Name'] == $this->session->userdata('businessName'))
	    {
	      echo '<option selected="selected" value="' .$bprinter['Name']. '">' .$bprinter['Name']. '</option>';
	    }
	  else{echo '<option value="' .$bprinter['Name']. '">' .$bprinter['Name']. '</option>';}
	}
      echo '</select>';
    }
  else if($role == 'admin' or $role == 'ops' or $role == 'creative') {
    //$selected_name = 'City Ventures';//$this->session->userdata('businessName');
    /*$bis_js = 'id="select" onchange=set_campaign(this.value)';
      echo form_dropdown('businesses',$all_business, 'Almaden Valley Athletic Club', $bis_js);*/
    echo '<select name="businesses" id="select" onchange=set_campaign(this.value)>';
    foreach ($businesses as $bprinter)
      {
	if ($bprinter['Name'] == $this->session->userdata('businessName'))
	  {
	    echo '<option selected="selected" value="' .$bprinter['Name']. '">' .$bprinter['Name']. '</option>';
	  }
	else{	echo '<option value="' .$bprinter['Name']. '">' .$bprinter['Name']. '</option>';}
      }
    echo '</select>';
  }
  else {echo $business_name;}
?>
</div>
</div>
<div class="headform2">
  <div class="head_select"><strong>Campaign Name</strong><br />
  <div id="selectdisp">
  <?php
  if ($role == 'business')
    {
      $selected_campaign = '';
      if(array_key_exists('campaigns', $_POST))
	{
	  $selected_campaign = $this->session->userdata('campaignName');
	}
      echo '<label for="campaigns"></label>';
      echo '<select name="campaigns" id="select">';
      $countb = 0;

      foreach($campaigns as $printer)
	{
          $countb++;
	  if ($printer['Name'] == $selected_campaign)
	    {
	      echo '<option selected="selected" value="' .$printer['Name']. '">' .$printer['Name']. '</option>';
	    }
	  else
	    {
	      echo '<option value="' .$printer['Name']. '">' .$printer['Name']. '</option>';
	    }
	}
      if($countb > 1)
      {
          if($selected_campaign == ".*")
            {
                echo '<option selected="selected" value=".*">All Campaigns</option>';
            }
            else
            {
            echo '<option value=".*">All Campaigns</option>';
            }
            
      }
    }
  else if (array_key_exists('campaigns', $_POST))
    {
      $checkdem = 0;
      $count = 0;
      $campaign_list = $this->session->userdata('campaign_list');
      echo '<label for="campaigns"></label>';
      echo '<select name="campaigns" id="select">';
      if($this->session->userdata('campaignName') == '.*')
	{
	  echo '<option selected="selected" value="'.$this->session->userdata('campaignName'). '">All Campaigns</option>';
	  $checkdem = 1;
	}
      foreach ($campaign_list as $printer)
	{
	  $countd++;
	  if ($printer['Name'] == $this->session->userdata('campaignName'))
	    {
	      echo '<option selected="selected" value="' .$printer['Name']. '">' .$printer['Name']. '</option>';
	    }
	  else
	    {
	      echo '<option value="' .$printer['Name']. '">' .$printer['Name']. '</option>';
	    }
	}
      if($countd > 1 and $checkdem == 0)
	{
	  echo '<option value=".*">All Campaigns</option>';
	}
      //echo '<option value="' .$this->session->userdata('campaignName'). '">' .$this->session->userdata('campaignName'). '</option>';
    }
  else
    {
      echo '<label for="campaigns"></label>';
      echo '<select name="campaigns" id="select">';
      $countl = 0;
      foreach ($campaigns as $printer)
	{
          $countl++;
	  echo '<option value="' .$printer['Name']. '">' .$printer['Name']. '</option>';
	}
      if($countl > 1)
	{
	  echo '<option value=".*">All Campaigns</option>';
	}
    }
?></select>
</div>
</div>
</div>



<div class="headform3">
  <input type="submit" name="generate" id="generate" style="font-size:23px;display:block;width:62px;height:57px;position:absolute;margin-left:230px;margin-top:5px" value="GO" />
  <!--  <input type="image" src="/images/button-oon.png" name="generate" width="62" height="57" id="generate" class="go" alt="Submit Form" />
  --><?php $d_format = 'Y-m-d'; ?>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
  <td width="22%" align="left" class="datetext"><strong class="head_select">Start </strong></td>
  <td width="78%"><label for="startdate"></label>
  <input name="startdate" type="text" id="datepicker1" value="<?php echo $this->session->userdata('startDate');//date($d_format, strtotime("-30 days")); ?>" /></td>
  </tr>
  <tr>
  <td align="left" class="datetext"><strong class="head_select">End</strong></td>
  <td><label for="enddate"></label>
  <input name="enddate" type="text" id="datepicker2" value="<?php echo $this->session->userdata('endDate');//date($d_format, strtotime("-2days")); ?>" /></td>
  </tr>
  </table>
  <!-- <div class="head_date"><strong>Date</strong> (Year-Month-Day)<br />
  <label for="date"></label>
  <?php $d_format = 'Y-m-d'; ?>
  <input name="date" type="text" id="datepicker" value="" />
  </div>-->
  </div>
  <div class="clr"></div>
  <div class="orangebar">
  <div id="bizname1" class="chart-title"><?php //echo $this->session->userdata('businessName'); ?></div>
  
  <!--  <input type="image" src="/images/button-oon.png" name="generate" width="62" height="57" id="generate" onmouseover="MM_swapImage('go','','images/button.png',1)" onmouseout="MM_swapImgRestore()" /> -->
  </div>
  <?php echo form_close(); ?>
  </div>
  </div></div>
  <div class="contentwrap">
  <div class="pagetitle1"><img src="images/HEAD_SUMMARY.gif" name="sum" width="970" height="74" id="sum" onmouseover="MM_swapImage('sum','','images/HEAD_SUMMARY-on.gif',1)" onmouseout="MM_swapImgRestore()" /></div>
  <div class="panel1">
  <div class="pageright">
  <div class="chart_topright">
  <div class="chart-title">AD VIEWS / DAY </div>
  <div class="chartBorder">
  <div id="viewsGraphPage1"></div>
  <iframe id='iframe-graph2' src='graphs/graph_102' height='288' width='810' frameborder='0' allowtransparency='true'></iframe>
  </div>
  </div>
  <div class="chart_bottomright">
  <div class="chart-title" style="top:23px">VISITS / DAY </div>
  <div class="chartBorder">
  <div id="visitsGraphPage1"></div>
  <iframe id='iframe-graph3' src='graphs/graph_103' height='268' width='810' frameborder='0' allowtransparency='true'></iframe>
  </div>
  
  </div>
  </div>
  <div class="pageleft">
  
  <div class="table_date"></div>
  <div class="table_views"></div>
  <div class="table_visits"></div>
  
  <div class="chartBorder">
  <iframe id='iframe-graph1' src='graphs/graph_101' height='700' width='234' frameborder='0' allowtransparency='true'></iframe>
  </div>
  </div>
  <div class="clr"></div>
  
  </div>
  <div class="pagetitle2" ><img src="images/HEAD_PLACEMENT.gif" name="placement" width="970" height="74" id="placement" onmouseover="MM_swapImage('placement','','images/HEAD_PLACEMENT-on.gif',1)" onmouseout="MM_swapImgRestore()" /></div>
  <div class="panel2" >
  <div class="pageleft_page2" style="padding-top:120px;">
  
  <div class="table_url">URL</div>
  <div class="table_viewsR">VIEWS</div>
  <div class="chartBorder">
  <div id="viewsTablePage2"></div>
  <iframe id='iframe-graph4' src='graphs/graph_104' height='604' width='200' frameborder='0' allowtransparency='true'></iframe>
  </div>
  </div>
  <div class="pageright_page2">
  <div class="chart-title" style="top:60px;left:140px">AD VIEWS PER AD PLACEMENT</div>
  <div class="chartBorder">
  <div id="viewsPage2" style="margin-top:-30px;"></div>
  <iframe id='iframe-graph5' src='graphs/graph_105' height='604' width='760' frameborder='0' allowtransparency='true'></iframe>
  </div>
  </div>
  </div>
  <div class="pagetitle3"><img src="images/HEAD_GEO.gif" name="geo" width="970" height="74" id="geo" onmouseover="MM_swapImage('geo','','images/HEAD_GEO-on.gif',1)" onmouseout="MM_swapImgRestore()" /></div>
  <div class="panel3">
  <div class="page3_city">
  
  <div class="chart-title" style="top:30px;left:370px">AD VIEWS PER CITY</div>
   
  
  <div class="chartBorder">
      
  <div id="cityBarChartPage3" style="width:970px; height:198px;"></div>
  <iframe id='iframe-graph6' src='graphs/graph_106' height='198' width='1000' frameborder='0' allowtransparency='true'></iframe>
  </div>
  <div class="clear"></div>
  </div>
      
  <div class="pageright_page3">
  <div class="chart-title" style="top:39px;left:150px">AD VIEWS % PER CITY</div>
  <div class="chartBorder">
  <div id="cityPieChartPage3" style="width:760px; height:338px;"></div>
  <iframe id='iframe-graph8' src='graphs/graph_108' height='338' width='760' frameborder='0' allowtransparency='true'></iframe>
  </div>
  <a href="city_data" target="_blank" style="text-align:right;font-size:7pt;font-family:Lato, sans-serif;float:right">ALL CITY DATA</a>
  
  </div>
  <div class="pageleft_page3">
  <!-- <div class="table_url" style="top:37px;">CITY</div>
  <div class="table_viewsR" style="top:37px;">AD VIEWS</div>-->
  <div class="chartBorder">
  <div id="cityTablePage3" style="width:200px; height:338px;"></div>
  <iframe id='iframe-graph7' src='graphs/graph_107' height='338' width='200' frameborder='0' allowtransparency='true'></iframe>
  </div>
  
  </div>
      
  </div>
  
  <div class="pagetitle4"><img src="images/HEAD_DEMO.gif" name="demo" width="970" height="74" id="demo" onmouseover="MM_swapImage('demo','','images/HEAD_DEMO-on.gif',1)" onmouseout="MM_swapImgRestore()" /></div>
  <div class="panel4" style="display=none"> <div class="pg4title">AD VIEWS PER DEMOGRAPHIC</div>
  <div class="page4_holder">
  <div class="demo_title" style="top:150px;">AGE</div>
  <div class="chartBorder">
  <div id="agePage4" ></div>
  <iframe id='iframe-graph9' src='graphs/graph_109' height='300' width='700' frameborder='0' allowtransparency='true'></iframe>
  </div>
  </div>
  <div class="page4_holder">
  <div class="demo_title" style="top:150px;">INCOME</div>
  <div class="chartBorder">
  <div id="incomePage4"></div>
  <iframe id='iframe-graph10' src='graphs/graph_110' height='340' width='700' frameborder='0' allowtransparency='true'></iframe>
  </div>
  </div>
  <div class="page4_holder">
  <div class="demo_title" style="top:70px;">GENDER</div>
  <div class="chartBorder">
  <div id="genderPage4"></div>
  <iframe id='iframe-graph11' src='graphs/graph_111' height='300' width='700' frameborder='0' allowtransparency='true'></iframe>
  </div>
  </div>
  <div class="page4_holder">
  <div class="demo_title" style="top:100px;">EDUCATION</div>
  <div class="chartBorder">
  <div id="educationPage4"></div>
  <iframe id='iframe-graph12' src='graphs/graph_112' height='300' width='700' frameborder='0' allowtransparency='true'></iframe>
  </div>
  </div>
  </div> <div class="push" align="center"><img src="/images/1_block.gif" height="80" width="100" /></div>
  <div class="clear"></div>
  </div>
  <?php 
  //Display tags generated by SALES and BUSINESS users.
  if(isset($tags) && $tags)
  {
    foreach($tags as $tag_to_display)
    {
       echo $tag_to_display." "; 
    }
  }
  ?>
  


  </body>
  </html>
