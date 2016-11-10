
<link href='//fonts.googleapis.com/css?family=Oxygen' rel='stylesheet' type='text/css'>
<link rel="stylesheet" type="text/css" href="<?php echo base_url('css/smb/media_targeting.css'); ?>"/> 
<link rel="stylesheet" type="text/css" href="<?php echo base_url('css/smb/smartslider.css'); ?>"/>   
<script type="text/javascript" src="<?php echo base_url('js/smb/media_targeting.js');?>" ></script>
<script type="text/javascript" src="<?php echo base_url('js/smb/smartslider.js');?>" ></script>
<link rel="stylesheet" type="text/css" href="/css/smb/reach_frequency.css"/>

<script type="text/javascript">
      function getSlider(site) {
            var demo_string =  jQuery.trim(getDemoFromTable());
                if (demo_string.charAt(47) == "_") {
                    var slider_value = demo_string.substring(46,47);
                } else if (demo_string.charAt(48) == "0") {
                    var slider_value = demo_string.substring(46,49);
                } else {
                    var slider_value = demo_string.substring(46,48);
                }
          if (site =="smb") {      
          	$('#smart-slider').strackbar({ 
							callback: onTick, 
							defaultValue: slider_value, 
							sliderHeight: 7, 
							style: 'style3', 
							animate: true, 
							ticks: false, 
							labels: false, 
							trackerHeight: 34, 
							trackerWidth: 36, 
							sliderWidth: 235,
							triggerCallbackOnMove: true 
							});
          } else {
          	$('#smart-slider_rf').strackbar({ 
							callback: onTick, 
							defaultValue: slider_value, 
							sliderHeight: 7, 
							style: 'style3_rf', 
							animate: false, 
							ticks: false, 
							labels: false, 
							trackerHeight: 34, 
							trackerWidth: 36, 
							sliderWidth: 180,
							triggerCallbackOnMove: false
							});
          }
     }  
      function onTick(value) {
          $('#text').html(value);
          SendDataToBody();
      }
	function slide() {
		$('div.gridRight').animate({ marginLeft:  '-600px ' }, {complete: function() { $( 'div.gridRight ').css( 'display ',  'none ');}});
				
		}
	function slide2() {
		 $('div.gridRight ').css( 'display ',  'inline ');
		 $('div.gridRight ').animate({ marginLeft:  '0px ' }, {complete: function() { $( 'div.gridRight ').css( 'display ',  'inline ');}});
	
	}
         
	function showMediaTargeting()
	{

            
                if(is_planner_showing_geo)
                {
                    save_notes_geo();

                    init_note_toggle_geo();
                }
                if(is_planner_showing)
                {
                    save_notes();
                    
                    
                }
                init_note_toggle();
               menu_index = 1;
		document.getElementById("body_header").innerHTML="Media Targeting<a id='notes_toggle_link' style='float:right;' href='javascript:toggle_notes()'><img src='/images/notes_PNG.png' /><div align='right' style='float:right;width:25px;'> +</div></a>";
		document.getElementById("sliderBodyContent").innerHTML="";
		
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.open("GET", "/smb/get_media_targeting_body", false);
		xmlhttp.send();
		document.getElementById("body_content").innerHTML=xmlhttp.responseText;
		document.getElementById("sliderHeaderContent").innerHTML="<span id='siteSpan' style='color: #423b3b'>NO SITE SELECTED</span>";

		$("#channel_choices").chosen();
		$("#iab_category_choices").chosen();

		ShowCheckboxes();
                		//update_site_pack("");
		UpdateSitesList();
		//tracker();
	}
      
	function ShowCheckboxes()
	{
		var rfData = GetReachFrequencyData();
		var impression = Number(rfData.impressions);
		if(impression == 0)
		{
			impression = getPopulation();
		}
                var demo_string = getDemoFromTable();
                //alert(demo_string);
                document.getElementById("rfSlider").innerHTML=''+ 
     //'<div class="RfSettingsRow" id="RfCampaignFocusRow">'+
			'<div style="position:relative;">'+
				'<span id="RfSliderReach">REACH</span>'+
				'<span id="RfSliderFrequency">FREQUENCY</span>'+
				 '<div id="smart-slider" style="position:relative; display:block; margin-left:60px;"></div>' +
                           ' <div id="text" style="display:none; clear:both; text-align:center;width:200px;"></div>'+
		//'</div>'+
		'<div class="RfSettingsRow" id="RfCampaignFocusValueRow" style="display:none;">'+
		'<div class="RfSettingsRow" id="RfRetargetingRow" style="visibility:hidden">'+
			'<span class="RfSelectorTitle">Retargeting</span>'+
			'<input type="checkbox" name="RetargetingCheckbox" id="RetargetingCheckbox" value="Retargeting" checked/>'+
		'</div>'+
		'<div class="RfSettingsRow" id="RfCampaignFocusValueRow" style="visibility:hidden;">'+
			'<span id="RfCampaignFocusValue">50</span>'+
		'</div>';
		document.getElementById("demoCheckboxes").innerHTML=''+
	'<div class="RfCampaignSettings">'+
		'<div class="RfSettingsRow" id="RfGenderRow">'+
			'<span class="RfSelectorTitle">Gender</span>'+
			'<select class="RfSelector" id="GenderSelector" multiple="multiple">'+
                        '<option id="gender_all" value="all">All</option>'+
				'<option id="gender_male" value="gm">Male</option>'+
				'<option id="gender_female" value="gf">Female</option>'+

		
			'</select>'+
		'</div>'+
		'<div class="RfSettingsRow" id="RfIncomeRow">'+
			'<span class="RfSelectorTitle">INCOME</span>'+
			'<select class="RfSelector" id="IncomeSelector" multiple="multiple">'+
				'<option id="income_all" value="all">All</option>'+
				'<option id="income_1"value="i050">0-50k</option>'+
				'<option id="income_2"value="i50100">50-100k</option>'+
				'<option id="income_3"value="i100150">100-150k</option>'+
				'<option id="income_4"value="i150">150k +</option>'+
			'</select>'+
		'</div>'+
		'<div class="RfSettingsRow" id="RfAgeRow">'+
			'<span class="RfSelectorTitle">AGE</span>'+
			'<select class="RfSelector" id="AgeSelector" multiple="multiple">'+
				'<option id="age_all" value="all">All</option>'+
				'<option id="age_1" value="au18">Under 18</option>'+
				'<option id="age_2" value="a1824">18 to 24</option>'+
				'<option id="age_3" value="a2534">25 to 34</option>'+
				'<option id="age_4" value="a3544">35 to 44</option>'+
				'<option id="age_5" value="a4554">45 to 54</option>'+
				'<option id="age_6" value="a5564">55 to 64</option>'+
				'<option id="age_7" value="a65">65 +</option>'+
			'</select>'+
		'</div>'+
		'<div class="RfSettingsRow" id="RfEthnicRow" style="display:none;">'+
			'<span class="RfSelectorTitle">ETHNIC</span>'+
			'<select class="RfSelector" id="EthnicSelector" multiple="multiple">'+
				'<option id="race_all" value="all">All</option>'+
				'<option id="race_1" value="rc">Cauc</option>'+
				'<option id="race_2" value="raa">Afr Am</option>'+
				'<option id="race_3" value="ra">Asian</option>'+
				'<option id="race_4" value="rh">Hisp</option>'+
				'<option id="race_5" value="ro">Other</option>'+
			'</select>'+
		'</div>'+
		'<div class="RfSettingsRow" id="RfParentingRow">'+
			'<span class="RfSelectorTitle">PARENTING</span>'+
			'<select class="RfSelector" id="ParentingSelector" multiple="multiple">'+
				'<option id="kids_all" value="all">All</option>'+
				'<option id="kids_1" value="kn">No Kids</option>'+
				'<option id="kids_2" value="ky">Has Kids</option>'+
			'</select>'+
		'</div>'+
		'<div class="RfSettingsRow" id="RfEducationRow">'+
			'<span class="RfSelectorTitle">EDUCATION</span>'+
			'<select class="RfSelector" id="EducationSelector" multiple="multiple">'+
				'<option id="education_all" value="all">All</option>'+
				'<option id="education_1" value="cn">No College</option>'+
				'<option id="education_2" value="cu">Under Grad</option>'+
				'<option id="education_3" value="cg">Grad School</option>'+
			'</select>'+
		'</div>'+
		'<div class="RfSettingsRow" id="RfEducationRow" style="display:none;">'+
			'<span class="RfSelectorTitle">Impressions</span>'+
			'<select class="RfSelector" id="ImpressionsSelector">'+
				'<option id="imp1" value="100000">100,000</option>'+
				'<option id="imp2" value="200000">200,000</option>'+
				'<option id="imp3" value="300000">300,000</option>'+
				'<option id="imp4" value="400000">400,000</option>'+
				'<option id="imp5" value="500000">500,000</option>'+
				'<option id="imp6" value="600000">600,000</option>'+
				'<option id="imp7" value="700000">700,000</option>'+
				'<option id="imp8" value="800000">800,000</option>'+
				'<option id="imp9" value="900000">900,000</option>'+
				'<option id="imp10" value="1000000">1,000,000</option>'+
				'<option id="imp11" value="2000000">2,000,000</option>'+
				'<option id="imp12" value="3000000">3,000,000</option>'+
				'<option id="imp13" value="4000000">4,000,000</option>'+
				'<option id="imp14" value="5000000">5,000,000</option>'+
				'<option id="imp15" value="6000000">6,000,000</option>'+
				'<option id="imp16" value="7000000">7,000,000</option>'+
				'<option id="imp17" value="8000000">8,000,000</option>'+
				'<option id="imp18" value="9000000">9,000,000</option>'+
				'<option id="imp19" value="10000000">10,000,000</option>'+
			'</select>'+
			'<span> per month </span>'+
		'</div>'+
		
		//'<div class="RfSettingsRow" id="RfRetargetingRow">'+
		//	'<span class="RfSelectorTitle">Retargeting</span>'+
		//	'<input type="checkbox" name="RetargetingCheckbox" id="RetargetingCheckbox" value="Retargeting" checked/>'+
		//'</div>'+
	'</div>'+

	'<div id="RfExtras" style="width:100%; height:100%; color:black; border: black 1px solid; font-size:12px; display:none;">'+
		'<div id="RfDebugText" style="height:50px; width:100%; overflow:auto; border: black 1px solid;">'+
			'Debug Text	'+
		'</div>'+
		'<div id="RfHiddenInputs" style="height:70px; width:100%; border: black 1px solid;">'+
			'<div style="float:left;">'+
				'RON Geo Coverage: <input id="RfRonGeoCoverage" type="text" size="3" style="" height="14px" value="0.87"/>'+
			'</div>'+
			'<div style="float:left;">'+
				'Gamma: <input id="RfGamma" type="text" size="3" style="" value="1"/>'+
			'</div>'+
			'<div style="float:left;">'+
				'IP Accuracy: <input id="RfIpAccuracy" type="text" size="3" style="" value="0.99"/>'+
			'</div>'+
			'<div style="float:left;">'+
				'% of Demo Online: <input id="RfPercentDemoOnline" type="text" size="3" style="" value="0.87"/>'+
			'</div>'+
		'</div>'+
		'<div id="RfHiddenSites" style="height:240px; width:100%; overflow:auto; border: black 1px solid;">'+
			'Sites Table'+
		'</div>'+
	'</div>';
                
		if($("div.gridRight").css('marginLeft') != '0px'){
			slideOut();
		}
                
                
                
                if (checkDemo(demo_string, 0) == true && checkDemo(demo_string, 2) == true) {
                    document.getElementById("gender_all").selected=true;
                }
                
                if (checkDemo(demo_string, 0) == true) {
                    document.getElementById("gender_male").selected=true;
                } 
                
                if (checkDemo(demo_string, 2) == true) {
                    document.getElementById("gender_female").selected=true;
                }
                
                if (checkDemo(demo_string, 4) == true && checkDemo(demo_string, 6) == true
                     && checkDemo(demo_string, 8) == true && checkDemo(demo_string, 10) == true
                     && checkDemo(demo_string, 12) == true && checkDemo(demo_string, 14) == true
                     && checkDemo(demo_string, 16) == true) {
                    document.getElementById("age_all").selected=true;
                } 
                if (checkDemo(demo_string, 4) == true) {
                    document.getElementById("age_1").selected=true;
                } 
                if (checkDemo(demo_string, 6) == true) {
                    document.getElementById("age_2").selected=true;
                } 
                if (checkDemo(demo_string, 8) == true) {
                    document.getElementById("age_3").selected=true;
                } 
                if (checkDemo(demo_string, 10) == true) {
                    document.getElementById("age_4").selected=true;
                } 
                if (checkDemo(demo_string, 12) == true) {
                    document.getElementById("age_5").selected=true;
                } 
                if (checkDemo(demo_string, 14) == true) {
                    document.getElementById("age_6").selected=true;
                } 
                if (checkDemo(demo_string, 16) == true) {
                    document.getElementById("age_7").selected=true;
                } 

                if (checkDemo(demo_string, 18) == true && checkDemo(demo_string, 20) == true
                     && checkDemo(demo_string, 22) == true && checkDemo(demo_string, 24) == true) {
                    document.getElementById("income_all").selected=true;
                } 
                if (checkDemo(demo_string, 18) == true) {
                    document.getElementById("income_1").selected=true;
                } 
                if (checkDemo(demo_string, 20) == true) {
                    document.getElementById("income_2").selected=true;
                } 
                if (checkDemo(demo_string, 22) == true) {
                    document.getElementById("income_3").selected=true;
                } 
                if (checkDemo(demo_string, 24) == true) {
                    document.getElementById("income_4").selected=true;
                } 
                
                if (checkDemo(demo_string, 26) == true && checkDemo(demo_string, 28) == true
                     && checkDemo(demo_string, 30)) {
                    document.getElementById("education_all").selected=true;
                } 
                if (checkDemo(demo_string, 26) == true) {
                    document.getElementById("education_1").selected=true;
                } 
                if (checkDemo(demo_string, 28) == true) {
                    document.getElementById("education_2").selected=true;
                } 
                if (checkDemo(demo_string, 30) == true) {
                    document.getElementById("education_3").selected=true;
                } 

                if (checkDemo(demo_string, 32) == true && checkDemo(demo_string, 34) == true) {
                    document.getElementById("kids_all").selected=true;
                } 
                if (checkDemo(demo_string, 32) == true) {
                    document.getElementById("kids_1").selected=true;
                } 
                if (checkDemo(demo_string, 34) == true) {
                    document.getElementById("kids_2").selected=true;
                } 
                
                if (checkDemo(demo_string, 36) == true && checkDemo(demo_string, 38) == true
                     && checkDemo(demo_string, 40) == true && checkDemo(demo_string, 42) == true
                     && checkDemo(demo_string, 44) == true) {
                    document.getElementById("race_all").selected=true;
                } 
                if (checkDemo(demo_string, 36) == true) {
                    document.getElementById("race_1").selected=true;
                } 
                if (checkDemo(demo_string, 38) == true) {
                    document.getElementById("race_2").selected=true;
                } 
                if (checkDemo(demo_string, 40) == true) {
                    document.getElementById("race_3").selected=true;
                } 
                if (checkDemo(demo_string, 42) == true) {
                    document.getElementById("race_4").selected=true;
                } 
                if (checkDemo(demo_string, 44) == true) {
                    document.getElementById("race_5").selected=true;
                } 
                
                switch(impression) {
                    case 100000:
                        document.getElementById("imp1").selected=true;
                        break;
                    case 200000:
                        document.getElementById("imp2").selected=true;
                        break;
                    case 300000:
                        document.getElementById("imp3").selected=true;
                        break;
                    case 400000:
                        document.getElementById("imp4").selected=true;
                        break;
                    case 500000:
                        document.getElementById("imp5").selected=true;
                        break;
                     case 600000:
                        document.getElementById("imp6").selected=true;
                        break;                       
                    case 700000:
                        document.getElementById("imp7").selected=true;
                        break;
                    case 800000:
                        document.getElementById("imp8").selected=true;
                        break;
                     case 900000:
                        document.getElementById("imp9").selected=true;
                        break;                       
                     case 1000000:
                        document.getElementById("imp10").selected=true;
                        break;                       
                     case 2000000:
                        document.getElementById("imp11").selected=true;
                        break;                       
                     case 3000000:
                        document.getElementById("imp12").selected=true;
                        break;                       
                    case 4000000:
                        document.getElementById("imp13").selected=true;
                        break;                        
                    case 5000000:
                        document.getElementById("imp14").selected=true;
                        break;                        
                     case 6000000:
                        document.getElementById("imp15").selected=true;
                        break;                       
                     case 7000000:
                        document.getElementById("imp16").selected=true;
                        break;                       
                      case 8000000:
                        document.getElementById("imp17").selected=true;
                        break;                      
                    case 9000000:
                        document.getElementById("imp18").selected=true;
                        break;                        
                     case 10000000:
                        document.getElementById("imp19").selected=true;
                        break;   
                  }
                                     
    
                
                getSlider('smb');               
		SetupSlider();
		//SendDataToBody();
	}

	function toggle_iab_categories()
	{
		$("#iab_categories_section").toggle();
	}

	function toggle_channels()
	{
		$("#channels_section").toggle();
	}
</script>
