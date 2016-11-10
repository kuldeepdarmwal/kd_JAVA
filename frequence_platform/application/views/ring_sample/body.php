 
<script type="text/javascript">
function anyClickScript(){
                //alert("inside anyclick");

                var xmlhttp = new XMLHttpRequest();
                var site_rank_url = "/ring/hchk";
                ///alert(site_rank_url);
                xmlhttp.open("GET", site_rank_url, false);
                xmlhttp.send();
                document.getElementById("body_content_geo").innerHTML=xmlhttp.responseText;
                document.getElementById("body_header_geo").innerHTML="Campaign Healthcheck";
                document.getElementById("ghost_title").innerHTML="Campaign Healthcheck";
				document.getElementById("sliderHeaderContent").innerHTML="No Data";
				document.getElementById("notgeo").innerHTML="";              
}

function detailstable(){
                  var date1 = document.getElementById("date1").value;
                 var date2 = document.getElementById("date2").value;
                 var camp = document.getElementById("campaignname").innerHTML;
                 var str = date1+"|"+date2+"|"+camp;
                 document.getElementById('ajax_loading_image').style.display = 'block';
                 document.getElementById("detailstable").innerHTML='';
                 document.getElementById("detailsgraph").innerHTML = '';
                 document.getElementById("top5").innerHTML = '';
                 document.getElementById("top5cities").innerHTML = '';
                  var xmlhttp = new XMLHttpRequest();
                var site_rank_url = "ring/details/"+str;
                alert(site_rank_url);
                xmlhttp.open("GET", site_rank_url, false);
                xmlhttp.send();
                if(xmlhttp.responseText == 0) {
                var getInnerText = '<h2 style="text-align:center;color:black;">No Data</h2>';
                document.getElementById("detailstable").innerHTML = getInnerText;
                } else {
                document.getElementById("detailstable").innerHTML=xmlhttp.responseText;
                }
                if(xmlhttp.responseText != 0) {
                 var xmlhttp = new XMLHttpRequest();
                 var site_rank_url = "ring/highchart/"+str;
                alert(site_rank_url);
                xmlhttp.open("GET", site_rank_url, false);
                xmlhttp.send();
                jQuery('#detailsgraph').html(xmlhttp.responseText);
                if(xmlhttp.responseText) {
                 var xmlhttp = new XMLHttpRequest();
                 var site_rank_url = "ring/top5/"+str;
                alert(site_rank_url);
                xmlhttp.open("GET", site_rank_url, false);
                xmlhttp.send();
                jQuery('#top5').html(xmlhttp.responseText);
                }
                if(xmlhttp.responseText) {
                 var xmlhttp = new XMLHttpRequest();
                 var site_rank_url = "ring/top5cities/"+str;
                alert(site_rank_url);
                xmlhttp.open("GET", site_rank_url, false);
                xmlhttp.send();
              jQuery('#top5cities').html(xmlhttp.responseText);
                }
             }
                document.getElementById('ajax_loading_image').style.display = 'none';
}
function campaign(c){
                 document.getElementById("campaignname").innerHTML=c;
                 var date1 = document.getElementById("date1").value;
                 var date2 = document.getElementById("date2").value;
                 var camp = document.getElementById("campaignname").innerHTML;
                 var str = date1+"|"+date2+"|"+camp;
		 		 document.getElementById('ajax_loading_image').style.display = 'block';
		 		 document.getElementById("detailstable").innerHTML='';
		 		 document.getElementById("detailsgraph").innerHTML = '';
		 		 document.getElementById("top5").innerHTML = '';
		 		 document.getElementById("top5cities").innerHTML = '';
                 var xmlhttp = new XMLHttpRequest();
                var site_rank_url = "/ring/details/"+str;
                //alert(site_rank_url);
                xmlhttp.open("GET", site_rank_url, false);
                xmlhttp.send();
		if(xmlhttp.responseText == 0) {
		var getInnerText = '<h2 style="text-align:center;color:black;">No Data</h2>';	
		document.getElementById("detailstable").innerHTML = getInnerText;
		} else {
                document.getElementById("detailstable").innerHTML=xmlhttp.responseText;
		}
		if(xmlhttp.responseText != 0) {
                 var xmlhttp = new XMLHttpRequest();
                 var site_rank_url = "/ring/highchart/"+str;
                ///alert(site_rank_url);
                xmlhttp.open("GET", site_rank_url, false);
                xmlhttp.send();
                //document.getElementById("detailsgraph").innerHTML=xmlhttp.responseText;
                 //document.getElementById("detailsgraph").innerHTML="";
                jQuery('#detailsgraph').html(xmlhttp.responseText);
		if(xmlhttp.responseText) {
                 var xmlhttp = new XMLHttpRequest();
                 var site_rank_url = "/ring/top5/"+str;
                ///alert(site_rank_url);
                xmlhttp.open("GET", site_rank_url, false);
                xmlhttp.send();
                //document.getElementById("detailsgraph").innerHTML=xmlhttp.responseText;
                 //document.getElementById("detailsgraph").innerHTML="";
                jQuery('#top5').html(xmlhttp.responseText);
		}	
		if(xmlhttp.responseText) {
                 var xmlhttp = new XMLHttpRequest();
                 var site_rank_url = "/ring/top5cities/"+str;
                ///alert(site_rank_url);
                xmlhttp.open("GET", site_rank_url, false);
                xmlhttp.send();
                //document.getElementById("detailsgraph").innerHTML=xmlhttp.responseText;
                 //document.getElementById("detailsgraph").innerHTML="";
              jQuery('#top5cities').html(xmlhttp.responseText);
		}
	     }
		document.getElementById('ajax_loading_image').style.display = 'none';

}               
</script>


<div id="content">
    
<span id="geospan" style="display">
   <div class="inner" style="height: 923px;">

              <div class="gridLeft" style="z-index:2;">
			<div class="widget" style="height:auto">
				<div class="header" id="body_header_geo"> Replace Application Header 
				</div>
				<div class="content" id="body_content_geo" style="background:#fbfbfb; height:auto;">
					<p>Replaced by application body</p>
				</div>
				<div id="ajax_loading_image" style="display:none;position:absolute;top:353px;left:230px;"><img src="<?php echo base_url('images/ajax-loader.gif');?>"></div> 
			</div>
	  </div>

	  <div class="gridRight" id="gridRight_geo" style="z-index:1;"> <!-- this is initially hidden, but slides out when slide is clicked -->
		<div class="floatingGridRight"id="slideOut_geo">
			<div class="widget">
                              <style>
                                    
                                    #notes_toggle_link_geo:link {color:#C4C8C6;}      /* unvisited link */
                                    #notes_toggle_link_geo:visited {color:#C4C8C6;}  /* visited link */
                                    #notes_toggle_link_geo:hover {color:red;}  /* mouse over link */
                                    #notes_toggle_link_geo:active {color:#C4C8C6;}  /* selected link */
                                    
                                </style>
				<div class="header" id="sliderHeaderContent_geo"><span> Replace Slider Header </span></div>
				<div class="content" id="sliderBodyContent_geo">
				</div>
        <div class="header" id="notes_header_geo" style="position:absolute; top:0px; left:0px; visibility: hidden;"><span></span></div>
				<div class="content" id="notes_body_geo" style="position:absolute; top:0px; left:0px; visibility: hidden;">
				</div>                                
			</div>
		</div>
	  </div>

   </div> <!--// End inner -->
</span>

<span id="notgeo" style="display">
   <div class="inner">

              <div class="gridLeft" style="z-index:2;">
			<div class="widget" style="height:auto">
				<div class="header" id="body_header"> Replace Application Header 
				</div>
				<div class="content" id="body_content" style="background:#fbfbfb; height:auto;">
					<p>Replaced by application body</p>
				</div>
			</div>
	  </div>

	  <div class="gridRight" id="gridRight" style="z-index:1;"> <!-- this is initially hidden, but slides out when slide is clicked -->
		<div class="floatingGridRight"id="slideOut">
			<div class="widget" style="position:absolute;width:100%;">
                                <style>
                                    
                                    #notes_toggle_link:link {color:#C4C8C6;}      /* unvisited link */
                                    #notes_toggle_link:visited {color:#C4C8C6;}  /* visited link */
                                    #notes_toggle_link:hover {color:red;}  /* mouse over link */
                                    #notes_toggle_link:active {color:#C4C8C6;}  /* selected link */
                                    
                                </style>
				<div class="header" id="sliderHeaderContent" ><span> Replace Slider Header </span></div>
				<div class="content" id="sliderBodyContent" >
				</div>

                                <div class="header" id="notes_header" style="position:absolute; top:0px; left:0px; visibility: hidden;"><span></span></div>
				<div class="content" id="notes_body" style="position:absolute; top:0px; left:0px; visibility: hidden;">
				</div>
                                
			</div>
		</div>
	  </div>

   </div> <!--// End inner -->
</span>
</div> <!--// End content --> 


   <!-- JQuery Scripts -->  





</body>
</html>



