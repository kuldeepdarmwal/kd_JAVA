    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html>
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="shortcut icon" href="/images/favicon_blue.png">
    <title>VANTAGE LOCAL | 	Adgroups</title>
    <link href="/css/login_style.css" rel="stylesheet" type="text/css" />
    <link href='https://fonts.googleapis.com/css?family=Lato:100,100italic,300,300italic,400,400italic,700,700italic,900italic,900' rel='stylesheet' type='text/css'>


    <script type="text/javascript">
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


    <!-- LOAD jQuery --><script type="text/javascript" src="/js/jquery-1.7.1.min.js"></script>
    </head>
    <script type="text/javascript" src="/js/jquery-ui-1.8.17.custom.min.js"></script>
    <script type="text/javascript" src="/js/dropdowns.js"></script>

    <script type="text/javascript">
    function setSelectedIndex(s, v)
{
    for( var i = 0; i < s.options.length; i++) {
	if( s.options[i].value == v) {
	    s.options[i].selected = true;
	    return;
	}
    }
}
function handle_adv_change(str)
{
	document.getElementById("cgn_input").value = "";
	document.getElementById("imp_input").value = "";
	document.getElementById("end_date_input").value = "";
	document.getElementById("lp_input").value = "";
	document.getElementById("after_campaign").disabled = "disabled";
	document.getElementById("after_adgroup").disabled = "disabled";

	if (str=="none")
	{
		document.getElementById("adgroups").innerHTML="Select Campaign First";
		document.getElementById("adv_input").disabled = "disabled";
		document.getElementById("sales_select").disabled = "disabled";
		document.getElementById("adv_input").value = "";
		setSelectedIndex(document.getElementById("sales_select"), "none");
		setSelectedIndex(document.getElementById("business_cgn_select"), "none");
		document.getElementById("campaigns").innerHTML="Select Advertiser First";
		document.getElementById("adgroups").innerHTML="Select Campaign First";
	}
	else if(str=="new")
	{
		document.getElementById("adgroups").innerHTML="Select Campaign First";
		document.getElementById("adv_input").disabled = false;
		document.getElementById("sales_select").disabled = false;
		document.getElementById("adv_input").value = "";
		setSelectedIndex(document.getElementById("business_cgn_select"), "new");
		document.getElementById("campaigns").innerHTML="Select Advertiser First";
		document.getElementById("adgroups").innerHTML="Select Campaign First";
		document.getElementById("after_advertiser").disabled= false;
	}
	else
	{
		document.getElementById("adgroups").innerHTML="Select Campaign First";
		setSelectedIndex(document.getElementById("business_cgn_select"), str);
		document.getElementById("after_advertiser").disabled= "disabled";
		document.getElementById("adv_input").disabled = "disabled";
		document.getElementById("sales_select").disabled = "disabled";
		var xmlhttp3 = new XMLHttpRequest();
		xmlhttp3.open("POST", "adgroups/get_advertiser_name/"+str, false);
		xmlhttp3.send(); 
		document.getElementById("adv_input").value = $.trim(xmlhttp3.responseText);
		var xmlhttp1 = new XMLHttpRequest();
		xmlhttp1.open("POST", "adgroups/sales_user/"+str, false);
		xmlhttp1.send(); 
		setSelectedIndex(document.getElementById("sales_select"), $.trim(xmlhttp1.responseText));
		var xmlhttp2 = new XMLHttpRequest();
		xmlhttp2.open("POST", "adgroups/get_campaign/"+str, false);
		xmlhttp2.send();
		document.getElementById("campaigns").innerHTML=$.trim(xmlhttp2.responseText);
	}
}
function handle_campaign_change(str)
{
	document.getElementById("cgn_input").value = "";
	document.getElementById("imp_input").value = "";
	document.getElementById("end_date_input").value = "";
	document.getElementById("lp_input").value = "";
	if(str=="none")
	{
		document.getElementById("cgn_input").disabled = "disabled";
		document.getElementById("imp_input").disabled = "disabled";
		document.getElementById("end_date_input").disabled = "disabled";
		document.getElementById("lp_input").disabled = "disabled";
		document.getElementById("adgroups").innerHTML="Select Campaign First";
		document.getElementById("after_adgroup").disabled = "disabled";
		document.getElementById("after_campaign").disabled = "disabled";
	}
	else if (str=="new")
	{
		document.getElementById("cgn_input").disabled = false;
		document.getElementById("lp_input").disabled = false;
		document.getElementById("imp_input").disabled = false;
		document.getElementById("end_date_input").disabled = false;
		document.getElementById("adgroups").innerHTML="Select Campaign First";
		document.getElementById("after_campaign").disabled = false;
		document.getElementById("after_adgroup").disabled = "disabled";
	}
	else
	{
		var xmlhttp3 = new XMLHttpRequest();
		xmlhttp3.open("POST", "adgroups/get_campaign_name/"+str, false);
		xmlhttp3.send(); 
		document.getElementById("cgn_input").value = $.trim(xmlhttp3.responseText);
		document.getElementById("cgn_input").disabled = "disabled";
		document.getElementById("imp_input").disabled = "disabled";
		document.getElementById("end_date_input").disabled = "disabled";
		document.getElementById("lp_input").disabled = "disabled";
		document.getElementById("after_campaign").disabled = "disabled";
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.open("POST", "adgroups/generate_adgroup/"+str, false);
		xmlhttp.send();
		document.getElementById("adgroups").innerHTML=$.trim(xmlhttp.responseText);
		document.getElementById("after_adgroup").disabled= false;
		
		var xmlhttp3 = new XMLHttpRequest();
		xmlhttp3.open("POST", "adgroups/campaign_details/"+str, false);
		xmlhttp3.send();
		var cgnarray = $.trim(xmlhttp3.responseText).split('|');
		document.getElementById("lp_input").value = cgnarray[2];
		document.getElementById("imp_input").value = cgnarray[3];
		document.getElementById("end_date_input").value = cgnarray[5];
	}
}
function handle_adv_submit()
{
	error = "";
	var params = "";
	var adv_array = new Array(2);
	adv_array[0] = document.getElementById("adv_input").value;
	adv_array[1] = document.getElementById("sales_select").value;
	for(var i = 0; i < adv_array.length; i++)
	{
		if(adv_array[i] === "" || adv_array[i] === "none")
		{
			error = "all fields required";
		}
	}
	if(error == "")
	{
		params="Name="+adv_array[0]+"&sales_person="+adv_array[1];
		xmlhttp = new XMLHttpRequest();
		xmlhttp.open("POST", "adgroups/submit_advertiser", false);
		xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xmlhttp.setRequestHeader("Content-length", params.length);
		xmlhttp.setRequestHeader("Connection", "close");
		xmlhttp.send(params);
		var advertiser_id = $.trim(xmlhttp.responseText);
		if(advertiser_id != "0")
		{
			$("#business_select").append('<option selected="selected" value="'+advertiser_id+'">'+adv_array[0]+'</option>');
			$("#business_cgn_select").append('<option selected="selected" value="'+advertiser_id+'">'+adv_array[0]+'</option>');
			handle_adv_change(advertiser_id);
		}
		else
		{
			document.getElementById("adv_error").innerHTML="Couldn't create new advertiser";
		}
	}
	else
	{
		document.getElementById("adv_error").innerHTML=error;
	}
	
}
function handle_cgn_submit()
{
	var error = "";
	var params = "";
	var cgn_array = new Array(4);
	cgn_array[0] = document.getElementById("cgn_input").value;
	cgn_array[1] = document.getElementById("business_cgn_select").value;
	cgn_array[2] = document.getElementById("lp_input").value;
	cgn_array[3] = document.getElementById("imp_input").value;
	for(var i = 0; i < cgn_array.length; i++)
	{
		if (cgn_array[i] == "")
		{
				error = "All fields required";
		}
	}

	if(error == "")
	{
		var end_date = document.getElementById("end_date_input").value;
		var params = "Name="+cgn_array[0]+"&business_id="+cgn_array[1]+"&LandingPage="+cgn_array[2]+"&TargetImpressions="+cgn_array[3]+"&EndDate="+end_date;
		xmlhttp = new XMLHttpRequest();
		xmlhttp.open("POST", "adgroups/submit_campaign", false);
		xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xmlhttp.setRequestHeader("Content-length", params.length);
		xmlhttp.setRequestHeader("Connection", "close");
		xmlhttp.send(params);
		var response = $.trim(xmlhttp.responseText);
		if(response != "0")
		{
			$("#cgn_select").append('<option selected="selected" value="'+response+'">'+cgn_array[0]+'</option>');
			handle_campaign_change(response);
		}
		else
		{
			document.getElementById("cgn_error").innerHTML="Failed to add Campaign";
		}
	}
	else
	{
		document.getElementById("cgn_error").innerHTML=cgn_error;
	}
}
function handle_adg_submit()
{
	var params = "";
	var error = "";
	var adg_array = new Array(7);
	adg_array[0] = document.getElementById("adg_id").value;
	adg_array[1] = document.getElementById("adg_adv").value;
	adg_array[2] = document.getElementById("adg_cgn").value;
	adg_array[3] = document.getElementById("adg_cty").value;
	adg_array[4] = document.getElementById("adg_rgn").value;
	adg_array[5] = document.getElementById("adg_src").value;
	adg_array[6] = document.getElementById("adg_rtg").checked;
	adg_array[7] = document.getElementById("adg_adv_id").value;
	adg_array[8] = document.getElementById("adg_cgn_id").value;

	if(adg_array[0] == "" || adg_array[1] == "" || adg_array[2] == "" || adg_array[5] == "" || adg_array[8] == "" || adg_array[8] == "0")
	{
		error = "non-optional fields required";
		document.getElementById("adg_error").innerHTML = error;
	}
	else
	{
		if(adg_array[6] == false)
		{
				adg_array[6] = 0;
		}
		else
		{
				adg_array[6] = 1;
		}
		params = 
			"ID="+adg_array[0]+
			"&BusinessName="+adg_array[1]+
			"&CampaignName="+adg_array[2]+
			"&IsRetargeting="+adg_array[6]+
			"&IsDerivedSiteDateRequired=0&City="+adg_array[3]+
			"&Region="+adg_array[4]+
			"&Source="+adg_array[5]+
			"&business_id="+adg_array[7]+
			"&campaign_id="+adg_array[8]
			;
		xmlhttp = new XMLHttpRequest();
		xmlhttp.open("POST", "adgroups/submit_adgroup", false);
		xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xmlhttp.setRequestHeader("Content-length", params.length);
		xmlhttp.setRequestHeader("Connection", "close");
		xmlhttp.send(params);
		$("#adg_error").append("<br>"+$.trim(xmlhttp.responseText));
	}
}
</script>
    
    <body onload="MM_preloadImages('/images/topicon03on.png','/images/topicon02on.png')">
    <div class="wrapper">
    <div class="header">
    <div class="header960">
    <div class="navbar"><a href="http://www.vantagelocal.com"><img src="/images/logo.gif" width="960" height="105" /></a></div> 
    <!-- stuff starts here -->
    <form>
    <table cellspacing="0">
    <tr><td>
    <?php
echo '<label for="businesses">Advertiser Name</label><br>';
echo '<select name="businesses" id="business_select" style="width:200px;" onchange=handle_adv_change(this.value)>';
echo '<option value="none">Select Advertiser</option>';
echo '<option value="none">--------------------</option>';
echo '<option value="new">New Advertiser</option>';
echo '<option value="none">--------------------</option>';
foreach($advertisers as $aprinter)
{
    echo '<option value="' .$aprinter['id']. '">' .$aprinter['Name']. '</option>';
}

echo '</select>';
    ?>
    </td>
    <td style="width: 100px;"></td>
    <td>
    <?php
echo'  Advertiser: (no apos or special characters)<input type="text" id="adv_input" value="" disabled="disabled" style="width:200px;"/>';
echo '<label for="salesperson">SalesPerson:</label><br>';
echo '<select name="salesperson" id="sales_select" disabled="disabled" style="width:200px;">';
echo '<option value="none"></option>';
echo '<option value="none">--------------------</option>';
foreach($sales_people as $sprinter)
{
    echo '<option value="' .$sprinter['id']. '">' .$sprinter['username']. '</option>';
}
echo '</select>';
    ?>
    </td>
    <td style="width:100px;"></td>
    <td><button type="button" id="after_advertiser" style="width:120px;" disabled="disabled" onclick="handle_adv_submit()">Add Advertiser</button></td>
    <td id="adv_error"></td>
    </tr>
  <tr><td><br><br></td></tr>
    <tr style="padding-top:10px;"><td>
    <div id="campaigns">Select Advertiser First</div>
    </td><td></td>
    <td>
    <?php
echo' Campaign:<input type="text" id="cgn_input" value="" disabled="disabled" style="width:200px;"/>';
echo '<label for="businesses">Advertiser Name</label><br>';
echo '<select name="businesses" id="business_cgn_select" disabled="disabled" style="width:200px;">';
echo '<option value="none"></option>';
echo '<option value="none">--------------------</option>';
echo '<option value="new">New Advertiser</option>';
echo '<option value="none">--------------------</option>';
foreach($advertisers as $aprinter)
{
    echo '<option value="' .$aprinter['id']. '">' .$aprinter['Name']. '</option>';
}

echo '</select><br>';
echo 'Landing Page:<input type="text" id="lp_input" value="" disabled="disabled" style="width:200px;"/>';
echo 'Target Impressions:(k)<input type="text" id="imp_input" value="" disabled="disabled" style="width:200px;"/>';
echo 'Hard End Date:<input type="text" id="end_date_input" value="" disabled="disabled" style="width:200px;"/>';
    ?>
    </td>
    <td></td>
    <td><button type="button" id="after_campaign" style="width:120px;" disabled="disabled" onclick="handle_cgn_submit()">Add Campaign</button></td>
    <td id="cgn_error"></td>
    </tr>
  <tr><td><br><br></td></tr>
    <tr><td id="adgroups">
    Select Campaign First
</td>
    <td></td><td></td><td></td>
    <td><button type="button" id="after_adgroup" style="width:120px;" disabled="disabled" onclick="handle_adg_submit()">Add Adgroup</button></td>
    <td id="adg_error"></td>
    </tr>

</table>
    </form>
    <!-- stuff ends here -->
    </div>
    </div>
<!--    <div class="content">
    <div class="content_left">
    </div>
    <div class="clear"></div>
   </div>
    <div class="push"></div>-->
    </div>

</body>
    </html>
