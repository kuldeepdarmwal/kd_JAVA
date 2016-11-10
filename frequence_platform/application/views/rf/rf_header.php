<?php
	$numElementsInChart = 6; // corresponds to var of same name in rf.php & rf_json_data.php

	$frequencyColor = "'#E07979'";
	$reachColor = "'#7D2D39'";
?>

<link rel="stylesheet" type="text/css" href="/css/smb/reach_frequency.css"/>

<script type="text/javascript">

	var gIsDiscountShowing=false;
	var gIsEstimateShowing=false;

  function ToggleMediaComparisonVisibility()
	{
		$("#RfMediaComparisonDataVisuals").toggle();
	}

	function ToggleEstimateVisibility()
	{
		if(gIsEstimateShowing == true)
		{
			gIsEstimateShowing = false;
			HideEstimate();
		}
		else
		{
			gIsEstimateShowing = true;
			ShowEstimate();
		}
	}

	function ShowEstimate()
	{
		$("#RfPriceEstimateButtonValue").html("Remove Estimate");
		$("#RfPriceEstimate").show();
	}

	function HideEstimate()
	{
		$("#RfPriceEstimateButtonValue").html("Get Estimate");
		$("#RfPriceEstimate").hide();
	}
        
	function ToggleDiscountVisibility()
	{
		if(gIsDiscountShowing == true)
		{
			gIsDiscountShowing = false;
			HideDiscount();
		}
		else
		{
			gIsDiscountShowing = true;
			ShowDiscount();
		}
	}

	function ShowDiscount()
	{
		$("#RfPriceEstimateEnterCodeRow").show();
		$("#RfPriceEstimateDiscountPercentRow").show();
		$("#RfPriceEstimateDiscountRateRow").show();
	}

	function HideDiscount()
	{
		$("#RfPriceEstimateEnterCodeRow").hide();
		$("#RfPriceEstimateDiscountPercentRow").hide();
		$("#RfPriceEstimateDiscountRateRow").hide();
	}

	function formatNumber(num) {
			return num.toLocaleString().split(".")[0] + "."
					+ num.toFixed(2).split(".")[1];
	}

	// From http://stackoverflow.com/questions/149055/how-can-i-format-numbers-as-money-in-javascript
	Number.prototype.formatMoney = function(c, d, t){
		var n = this, c = isNaN(c = Math.abs(c)) ? 2 : c, d = d == undefined ? "." : d, t = t == undefined ? "," : t, s = n < 0 ? "-" : "", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
		return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
	};

	function GetDiscountPercent()
	{
		var discountCode = $("#RfPriceEstimateCodeName").val();
		var discountValue = discountCode.match(/\d+/);
		var actualDiscount = 0;
		if(discountValue != null)
		{
			actualDiscount = discountValue[0];
		}

		return actualDiscount;
	}

	function ProcessDiscountCode()
	{
		var actualDiscount = GetDiscountPercent();

		var baseEstimate = $("#RfPriceEstimateRawValue").html();
		var amountDiscounted = baseEstimate * actualDiscount / 100;
		var discountedRate = baseEstimate - amountDiscounted;
		$("#RfPriceEstimateDiscountPercent").html("Discount ("+actualDiscount+"%)");
		$("#RfPriceEstimateDiscountAmount").html("-$"+amountDiscounted.formatMoney(2)+" / mo");
		$("#RfPriceEstimateDiscountRate").html("$"+discountedRate.formatMoney(2)+" / mo");

		var hasRetargeting = $("#RetargetingCheckbox").is(':checked')?"with Retargeting":"without Retargeting";

		var discountCode = $("#RfPriceEstimateCodeName").val();

		var note = "Base price "+baseEstimate+" with discount code "+discountCode+" gives final price "+discountedRate+" "+hasRetargeting;
		
		//if(actualDiscount != 0)
		{
			SavePriceNotes(note);
		}
	}

	function SavePriceNotes(note)
	{
		var savePriceUrl = "/rf/save_price";
		$.ajax({
			url: savePriceUrl,
			type: "POST", 
			data: {priceNotes: note},
			success: function(data, textStatus, xhr) 
			{
				//alert('SavePriceNotes returned: '+data);
			},
			error: function(xhr, textStatus, error) 
			{
				$("#body_content").html("SavePriceNotes() ajax error. "+savePriceUrl+"<br /> Error code: "+error);
			}
		});

	}

	function GetReachFrequencyData()
	{
		var rfJsonData = null;
		$.ajax({
			url: '/rf/get_settings_data',
			type: "GET", 
			success: function(data, textStatus, xhr) 
			{
				rfJsonData = data;
				//alert("get settings data success: "+rfJsonData);
			},
			error: function(xhr, textStatus, error) 
			{
				//alert("get settings data failed: "+error);
			},
			async: false
		});

		var rfData = null;
		if(rfJsonData != null)
		{
			rfData = jQuery.parseJSON(rfJsonData);
			//alert("hasRetargeting after parse: "+rfData.hasRetargeting);
		}
		else
		{
			alert("ERROR: Reach Frequency: Received no json data.");
			rfData = new Object();
			rfData.hasRetargeting = 0;
			rfData.impressions = 0;
		}
	
		return rfData;
	}

	function SetReachFrequencyData()
	{
		alert("SetReachFrequencyData() unimplemented");
	}

	function getDemoFromTable() {
			var xmlhttp = new XMLHttpRequest(); 
			xmlhttp.open('GET', '/rf/getDemo/', false); 
			xmlhttp.send();
			var demo = new String(xmlhttp.responseText);
			var demo2 = jQuery.trim(demo);
			return demo2;
	}
	
	function getPopulation() {
		var impressionList=new Array(100000, 200000, 300000, 400000, 500000, 600000, 700000, 800000, 900000, 
			1000000, 2000000, 3000000, 4000000, 5000000, 6000000, 7000000, 8000000, 9000000, 10000000);
		var xmlhttp = new XMLHttpRequest(); 
		xmlhttp.open('GET', '/rf/getPopulation/', false); 
		xmlhttp.send();
		var population =  xmlhttp.responseText; 
		var diff1 = 0;
		var diff2 = 10000000000;
		var impression = 0;
		for (i=0; i<impressionList.length; i++) {
			diff1 = Math.abs(impressionList[i]-population);
			if (diff1 < diff2) {
				diff2 = diff1;
				impression = impressionList[i];
			}
		}
	 return impression;
	}
	

	
	function checkDemo(demo, index) 
	{
		//  alert('demo string: "' + demo + '"               index;' + index + '              charAt:' + demo.charCodeAt(0));
		if (demo.charAt(index) == 1) {
			return true;
		} else {
			return false;
		}
	}

	var highChart;
	function SetupReachFrequencyBodyStructure()
	{
		//alert("SetupReachFrequencyBodyStructure");

		$("#body_content").html(
		'<div id="RfMainBody" class="RfMainBody" >'+
		'	<div id="RfBodyExtraData" class="RfBodyExtraData">'+
		'		<div id="RfPopulationText" class="RfPopulationText">'+
		'			<div id="RfTargetedRegionText">'+
		'			</div>'+ 
		'			<div class="RfGeoPopulationText">'+
		'				<div id="RfGeoPopulationValue" style="font-size:32px">'+
		'				</div>'+
		'				<div id="RfGeoPopulationTitle" style="font-size:22px">Geo Population'+
		'				</div> '+
		'			</div>'+
		'			<div class="RfDemoPopulationText">'+
		'				<div id="RfDemoPopulationValue" style="font-size:32px">'+
		'				</div>'+
		'				<div id="RfDemoPopulationTitle" style="font-size:22px">Target Population'+
		'				</div> '+
		'			</div>'+
		'		</div>'+
		'	</div>'+
		'	<div id="RfBodyGraph" class="RfBodyGraph" style="min-width:400px; min-height: 300px; height:100%; margin: 0 auto;">'+
		'	</div>'+
		'	<div id="RfBodyTable" class="RfBodyTable">'+
		'		<table style="min-width: 400px; width:100%; height: 121px; padding: 40px 0px 0px 0px;"; border="0">'+
		'			<thead>'+
		'				<tr style="background-color: #C0C1C2; color: #414142;">'+
		'					<td style="font-weight: bold;background-color:#fbfbfb;"><br>'+
		'					</td>'+
							<?php
								for($ii=0; $ii<$numElementsInChart;$ii+=1) 
								{
									echo '\'<td id="RfMonthTableElement_'.$ii.'" style="color: #414142; font-family: Oxygen, sans-serif; text-transform:uppercase; text-align: center;"><br></td>\'+';
								}
							?>
		'				</tr>'+
		'			</thead>'+
		'			<tbody>'+

		'				<tr>'+
		'					<td style="padding-left:14px; color: #414142; background-color: #EAEAEA; font-family: Oxygen, sans-serif; text-transform:uppercase;">IMPRESSIONS<br>'+
		'					</td>'+
							<?php
								for($ii=0; $ii<$numElementsInChart;$ii+=1) 
								{
									echo '\'<td id="RfImpressionsTableElement_'.$ii.'" style="color: #414142; background-color:#F6F6F6; text-align: center;"><br></td>\'+';
								}
							?>
		'				</tr>'+
		'				<tr>'+
		'					<td style="padding-left:14px; color: #414142; background-color: #EAEAEA; font-family: Oxygen, sans-serif; text-transform:uppercase;">REACH %<br>'+
		'					</td>'+
							<?php
								for($ii=0; $ii<$numElementsInChart;$ii+=1) 
								{
									echo '\'<td id="RfReachPercentTableElement_'.$ii.'" style="color: #414142; background-color:#F6F6F6; text-align: center;"><br></td>\'+';
								}
							?>
		'				</tr>'+
		'				<tr>'+
		'					<td style="padding-left:14px; color: #414142; background-color: #EAEAEA; font-family: Oxygen, sans-serif; text-transform:uppercase;">REACH<br>'+
		'					</td>'+
							<?php
								for($ii=0; $ii<$numElementsInChart;$ii+=1) 
								{
									echo '\'<td id="RfReachValueTableElement_'.$ii.'" style="color: #414142; background-color:#F6F6F6; text-align: center;"><br></td>\'+';
								}
							?>
		'				</tr>'+
		'				<tr>'+
		'					<td style="padding-left:14px; color: #414142; background-color: #EAEAEA; font-family: Oxygen, sans-serif; text-transform:uppercase;">FREQUENCY<br>'+
		'					</td>'+
							<?php
								for($ii=0; $ii<$numElementsInChart;$ii+=1) 
								{
									echo '\'<td id="RfFrequencyTableElement_'.$ii.'" style="color: #414142; background-color:#F6F6F6; text-align: center;"><br></td>\'+';
								}
							?>
		'				</tr>'+
		'			</tbody>'+
		'		</table>'+
		'	</div>'+
		'</div>'
		);

	highChart = new Highcharts.Chart({
		chart: {
				renderTo: 'RfBodyGraph',
				zoomType: 'xy',
				backgroundColor: '#fbfbfb',
				alignTicks: false
		},
		title: {
				text: ''
		},
		subtitle: {
				text: ''
		},
		xAxis: [{
				categories: [
						],
                                                    labels: {
         style: {
            color: '#414142',
            font: '12px Oxygen, sans-serif',
            textTransform: 'uppercase'
         }
      }
		}],
		yAxis: [
		{ // Secondary yAxis
				title: {
						text: 'Reach',
						style: {
								color: <?php echo $reachColor; ?>,
                                                                font: 'bold 12px "Oxygen", sans-serif'
						}
				},
				labels: {
						formatter: function() {
								return (this.value * 100) + '%';
						},
						style: {
								color: <?php echo $reachColor; ?>,
                                                                font: 'bold 12px "Oxygen", sans-serif'
						}
				},
				max: 1.0,
				maxPadding: 0.0,
				min: 0.0,
				tickInterval: 0.25,
				endOnTick: false,
				gridLineWidth: 0
		}
		, 
		{ // Primary yAxis
				labels: {
						formatter: function() {
								return this.value+'';
						},
						style: {
								color: <?php echo $reachColor; ?>,
                                                                font: 'bold 12px "Oxygen", sans-serif'
						}
				},
				title: {
						text: 'Average Frequency',
						style: {
								color: <?php echo $reachColor; ?>,
                                                                font: 'bold 12px "Oxygen", sans-serif'
						}
				},
				opposite: true,
				max: 25.0,
				maxPadding: 0.0,
				min: 0,
				endOnTick: false
		}
		],
		tooltip: {
				formatter: function() {
						if(this.series.name == 'Display Reach' ||
							this.series.name == 'Other Reach')
						{
							var num = this.y*100 + 0;
							return ''+this.x+': '+num.toFixed(1)+'%';
						}
						else
						{
							var num = this.y + 0;
							return ''+this.x+': '+num.toFixed(1)+'';
						}
				}
		},
		legend: {
				layout: 'horizontal',
				align: 'left',
				x: 60,
				verticalAlign: 'top',
				y: -10,
				floating: true,
				backgroundColor: '#fbfbfb'
		},
		series: [
		{
				name: 'Display Average Frequency',
				color: <?php echo $frequencyColor; ?>,
				type: 'column',
				yAxis: 1,
				data: [
				],
				stack: 'frequency'
		}
		, 
		{
				name: 'Display Reach',
                                visible: false,
				color: <?php echo $reachColor; ?>,
				type: 'spline',
				yAxis: 0,
				data: [
				]
		}
		,
		{
				name: 'Other Reach',
                                visible: false,
				color: '#97989a',
				type: 'spline',
				yAxis: 0,
				data: [
				]
		}
		,
		{
				name: 'Other Average Frequency',
                                visible: false,
				color: '#97989a',
				type: 'column',
				yAxis: 1,
				data: [
				],
				stack: 'frequency'
		}
		]
	});


	}

	function UpdateReachFrequencyPage()
	{
		//alert("UpdateReachFrequencyPage begin");
		var encodedParameters = EncodeDemographicAndReachFrequencyParameters();
		var mediaType = $("#RfMediaComparisonSelection").val();
		var hasRetargeting = $("#RetargetingCheckbox").is(':checked')?"1":"0";
		var discountPercent = GetDiscountPercent();

		var campaignFocusValue = $("#RfCampaignFocusSlider").slider("option", "value");
		$("#RfCampaignFocusValue").html(""+campaignFocusValue);

		var rfJsonData=null;
		var post_data = {
			mediaType: mediaType,
			hasRetargeting: hasRetargeting,
			encodedData: encodedParameters,
			discountPercent: discountPercent
		};

		$.ajax({
			url: '/rf/GetPageData',
			dataType: 'json',
			success: function(data, textStatus, xhr) 
			{
				rfJsonData = data;
				//alert("get settings data success: "+rfJsonData);
			},
			error: function(xhr, textStatus, error) 
			{
				alert("get settings data failed: "+error);
			},
			async: false,
			type: "POST", 
			data: post_data
		});

		var rfData = null;
		if(rfJsonData != null)
		{
			ApplyReachFrequencyData(rfJsonData, mediaType)
			ProcessDiscountCode();
		}
		else
		{
			alert("no json data.");
		}
	}


	function GetMaxValue(valueArray)
	{
		maxValue = 0;
		for(ii=0; ii<valueArray.length; ii++)
		{
			if(valueArray[ii] > maxValue)
			{
				maxValue = valueArray[ii];
			}
		}
		return maxValue;
	}

	var scaleFactor = 1.75;
	var upperTrigger = null;
	var lowerTrigger = null;

	function ResetReachFrequencyHysteresis()
	{
		upperTrigger = null;
		lowerTrigger = null;
	}

	function ApplyReachFrequencyData(myData, mediaType)
	{
		var averageFrequencySeriesIndex = 0;
		var reachSeriesIndex = 1;
		var mediaReachSeriesIndex = 2;
		var mediaFrequencySeriesIndex = 3;

		var frequencyAxisIndex = 1;

		/*
		// Constants also found in rf_json_data.php
		var kPrintMediaIndex = 0;
		var kDirectMediaIndex = 1;
		var kRadioMediaIndex = 2;
		var kNumOtherMedia = 3;
		*/

		var numMediaCategories = myData.mediaCategories.length;
		$("#RfMediaComparisonSelection").empty();
		for(ii=0; ii<numMediaCategories; ii++)
		{
			var category = myData.mediaCategories[ii];
			var newOption = new Option(category, category);
			$("#RfMediaComparisonSelection").append(newOption);
		}

		if(mediaType=="Default")
		{
			$("#RfMediaComparisonSelection").val(myData.mediaCategories[0]);
		}
		else
		{
			$("#RfMediaComparisonSelection").val(mediaType);
		}

		$("#RfTargetedRegionText").html(myData.targeted_region_summary);

		$("#RfGeoPopulationValue").html(myData.geoPopulation);
		$("#RfDemoPopulationValue").html(myData.demoPopulation);

		$("#RfPriceEstimateValue").html(myData.priceEstimateString);
		$("#RfPriceEstimateRawValue").html(myData.priceEstimate);

		var maxFrequency = GetMaxValue(myData.frequency);

		if((upperTrigger == null) || (maxFrequency > upperTrigger)||(maxFrequency < lowerTrigger)) 
		{
			var upperLimit = maxFrequency*scaleFactor;
			upperTrigger = maxFrequency*scaleFactor;
			lowerTrigger = maxFrequency/scaleFactor;    
			highChart.yAxis[frequencyAxisIndex].setExtremes(0, upperLimit, true, false);
		}

		for(ii=0; ii < <?php echo $numElementsInChart; ?> ;ii++)
		{
			$("#RfMonthTableElement_"+ii).html(myData.monthTitles[ii]);
			$("#RfImpressionsTableElement_"+ii).html(myData.impressions[ii]);
			$("#RfReachPercentTableElement_"+ii).html(myData.reachPercent[ii]);
			$("#RfReachValueTableElement_"+ii).html(myData.reachValue[ii]);
			$("#RfFrequencyTableElement_"+ii).html(myData.frequency[ii]);
		}

		$("#RfMediaComparisonOverallValue").html(myData.displayTimesBetter);

		highChart.xAxis[0].setCategories(myData.monthTitles, false);
		highChart.series[reachSeriesIndex].setData(myData.demoReach, false);
		highChart.series[mediaReachSeriesIndex].setData(myData.otherMediaReachPercent, false);
		highChart.series[averageFrequencySeriesIndex].setData(myData.demoFrequency, false);
		highChart.series[mediaFrequencySeriesIndex].setData(myData.otherMediaFrequency, false);

		highChart.redraw();
	}

    
	function ShowReachFrequency()
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
		menu_index = 2;
		ResetReachFrequencyHysteresis();

		//alert("ShowReachFrequency begin");
		var demo_string = getDemoFromTable();
		var rfData = GetReachFrequencyData();
		var impression = Number(rfData.impressions);
		if(impression == 0)
		{
			impression = getPopulation();
		}
		//  alert(demo_string);

		document.getElementById("body_header").innerHTML='Campaign Performance Estimate <a id="notes_toggle_link" style="float:right;" href="javascript:toggle_notes()"><img src="/images/notes_PNG.png"  /><div align="right" style="float:right;width:25px;"> +</div></a>';
		document.getElementById("ghost_title").innerHTML='Campaign Performance Estimate';
                dismiss_notes();

		document.getElementById("body_content").innerHTML='';
		SetupReachFrequencyBodyStructure();

		document.getElementById("sliderHeaderContent").innerHTML='Campaign Settings';
		document.getElementById("sliderBodyContent").innerHTML='';

		document.getElementById("sliderBodyContent").innerHTML=''+
	'<div class="RfCampaignSettings">'+
		'<div class="RfSettingsRow" id="RfGenderRow" style="display:none;">'+
			'<span class="RfSelectorTitle">Gender</span>'+
			'<select class="RfSelector" id="GenderSelector" multiple="multiple">'+
                        '<option id="gender_all" value="all">All</option>'+
				'<option id="gender_male" value="gm">Male</option>'+
				'<option id="gender_female" value="gf">Female</option>'+

		
			'</select>'+
		'</div>'+
		'<div class="RfSettingsRow" id="RfIncomeRow" style="display:none;">'+
			'<span class="RfSelectorTitle">INCOME</span>'+
			'<select class="RfSelector" id="IncomeSelector" multiple="multiple">'+
				'<option id="income_all" value="all">All</option>'+
				'<option id="income_1"value="i050">0-50k</option>'+
				'<option id="income_2"value="i50100">50-100k</option>'+
				'<option id="income_3"value="i100150">100-150k</option>'+
				'<option id="income_4"value="i150">150k +</option>'+
			'</select>'+
		'</div>'+
		'<div class="RfSettingsRow" id="RfAgeRow" style="display:none;">'+
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
		'<div class="RfSettingsRow" id="RfParentingRow" style="display:none;">'+
			'<span class="RfSelectorTitle">PARENTING</span>'+
			'<select class="RfSelector" id="ParentingSelector" multiple="multiple">'+
				'<option id="kids_all" value="all">All</option>'+
				'<option id="kids_1" value="kn">No Kids</option>'+
				'<option id="kids_2" value="ky">Has Kids</option>'+
			'</select>'+
		'</div>'+
		'<div class="RfSettingsRow" id="RfEducationRow" style="display:none;">'+
			'<span class="RfSelectorTitle">EDUCATION</span>'+
			'<select class="RfSelector" id="EducationSelector" multiple="multiple">'+
				'<option id="education_all" value="all">All</option>'+
				'<option id="education_1" value="cn">No College</option>'+
				'<option id="education_2" value="cu">Under Grad</option>'+
				'<option id="education_3" value="cg">Grad School</option>'+
			'</select>'+
		'</div>'+
		'<div class="RfSettingsRow" id="RfEducationRow">'+
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
		'<div class="RfSettingsRow" id="RfRetargetingRow">'+
			'<span class="RfSelectorTitle">Retargeting</span>'+
			'<input type="checkbox" name="RetargetingCheckbox" id="RetargetingCheckbox" value="Retargeting" checked/>'+
		'</div>'+
		'<div class="RfSettingsRow" id="RfCampaignFocusRow">'+
			'<span class="RfSelectorTitle" style="float:left;">Campaign Focus</span>'+
			'<span id="RfSliderReach_rf">Reach</span>'+
			'<span id="RfSliderFrequency_rf">Frequency</span>'+
			'<div id="smart-slider_rf" style="position:relative; display:block; margin-left:160px;">'+
			'</div>' +
			'<div id="text" style="display:none; clear:both; text-align:center;width:200px;">'+
			'</div>'+		
		'</div>'+
		'<div class="RfSettingsRow" id="RfCampaignFocusValueRow" style="display:none;">'+
			'<span id="RfCampaignFocusValue">50</span>'+
		'</div>'+

	'</div>'+

	'<div id="RfCampaignSettingsDivider">'+
	'</div>'+

	'<div id="RfMediaComparisonDiv">'+
		'<div id="RfMediaComparisonTitle">'+
			'Media Comparison'+
		'</div>'+
		'<div id="RfMediaComparisonDataVisuals">'+
			'<div class="RfMediaComparisonRow">'+
				'<div class="RfMediaComparisonRowName">'+
					'Media Type'+
				'</div>'+
				'<div class="RfMediaComparisonRowValue">'+
					'<select id="RfMediaComparisonSelection">'+
						'<option value="Default">Default</option>'+
					'</select>'+
				'</div>'+
			'</div>'+
			'<div class="RfMediaComparisonRow">'+
				'<div class="RfMediaComparisonRowName">'+
					'Display Is'+
				'</div>'+
				'<div class="RfMediaComparisonRowValue">'+
					'<span id="RfMediaComparisonOverallValue" class="RfMediaComparisonValue">'+
						'45X'+
					'</span>'+
					' better*'+
				'</div>'+
			'</div>'+
			'<div class="RfMediaComparisonRow">'+
				'<div class="RfMediaComparisonRowName">'+
				'</div>'+
				'<div class="RfMediaComparisonRowValue" id="RfMediaComparisonValueExplanation">'+
					'*Cost Per Gross Rating Point'+
				'</div>'+
			'</div>'+
		'</div>'+
	'</div>'+

	'<div id="RfCampaignSettingsDivider">'+
	'</div>'+

	'<div id="RfPriceEstimateButton">'+
		'<button type="button" id="RfPriceEstimateButtonValue">'+
			'Get Estimate'+
		'</button>'+
	'</div>'+

	'<div id="RfPriceEstimate" style="display:none;">'+
		'<div id="RfPriceEstimateTitle">'+
			'<span id="RfPriceEstimateDiscountLink">Pricing</span>'+
		'</div>'+
		'<div class="RfPriceEstimateRow">'+
			'<div class="RfPriceEstimateRowName">'+
				'Rate'+
			'</div>'+
			'<div class="RfPriceEstimateRowValue">'+
				'<div id="RfPriceEstimateValue">'+
					'$ / mo'+
				'</div>'+
				'<div id="RfPriceEstimateRawValue" style="display: none;">'+
					'0'+
				'</div>'+
			'</div>'+
		'</div>'+
		'<div id="RfPriceEstimateEnterCodeRow" class="RfPriceEstimateRow">'+
			'<div class="RfPriceEstimateRowName">'+
				'Code'+
			'</div>'+
			'<div class="RfPriceEstimateRowValue">'+
				'<input type="text" name="RfPriceEstimateCodeName" id="RfPriceEstimateCodeName" size="12" />'+
			'</div>'+
		'</div>'+
		'<div id="RfPriceEstimateDiscountPercentRow" class="RfPriceEstimateRow">'+
			'<div class="RfPriceEstimateRowName">'+
				'<div id="RfPriceEstimateDiscountPercent">'+
					'Discount (%)'+
				'</div>'+
			'</div>'+
			'<div id="RfPriceEstimateDiscountAmount" class="RfPriceEstimateRowValue">'+
				'-$ / mon'+
			'</div>'+
		'</div>'+
		'<div id="RfPriceEstimateDiscountRateRow" class="RfPriceEstimateRow">'+
			'<div class="RfPriceEstimateRowName">'+
				'Discounted Rate'+
			'</div>'+
			'<div id="RfPriceEstimateDiscountRate" class="RfPriceEstimateRowValue">'+
				'$ / mo'+
			'</div>'+
		'</div>'+
	'</div>'+

	'<div id="RfExtras" style="width:100%; height:100%; color:black; border: black 1px solid; font-size:12px; visibility:hidden;">'+
		'<div id="RfDebugText" style="height:50px; width:100%; overflow:auto; border: black 1px solid;">'+
			'Debug Text	'+
		'</div>'+
		'<div id="RfHiddenInputs" style="height:70px; width:100%; border: black 1px solid;">'+
			'<div style="float:left;">'+
				'RON Geo Coverage: <input id="RfRonGeoCoverage" type="text" size="3" style="" height="14px" value="0.87"/>'+
			'</div>'+
			'<div style="float:left;">'+
				'Gamma: <input id="RfGamma" type="text" size="3" style="" value="0"/>'+
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

		checkAndSlideOut();              
                
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
		switch(impression) 
		{
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

		if(rfData.hasRetargeting == 1)
		{
			$("#RetargetingCheckbox").attr('checked', true);
		}
		else if(rfData.hasRetargeting == 0)
		{
			$("#RetargetingCheckbox").attr('checked', false);
		}
		else
		{
			alert("Unhandled retargeting value: "+rfData.hasRetargeting);
		}
                                     
		SetupSlider();
    getSlider("rf");               

		SendDataToBody();
	}

	function GetValueFromArray(arr, val)
	{
		//var result = "."
		for(var ii=0; ii<arr.length; ii++)
		{
			var item = arr[ii];
			if(item == val)
			{
				return 1;
			}
			//result += item;
		}
		return 0; //arr.toString();
	}

	function EncodeDemographicsInString()
	{
		var genderValues = $("#GenderSelector").val() || [];
		var incomeValues = $("#IncomeSelector").val() || [];
		var ageValues = $("#AgeSelector").val() || [];
		var ethnicValues = $("#EthnicSelector").val() || [];
		var parentingValues = $("#ParentingSelector").val() || [];
		var educationValues = $("#EducationSelector").val() || [];

		var impressionsValue = $("#ImpressionsSelector").val();
		var campaignFocusValue = $("#text").html();
		var retargetingValue = $("#RetargetingCheckbox").is(':checked');

		var encoded = "";
		// gender
		encoded += GetValueFromArray(genderValues, "gm")+"_";
		encoded += GetValueFromArray(genderValues, "gf")+"_";
		// age
		encoded += GetValueFromArray(ageValues, "au18")+"_";
		encoded += GetValueFromArray(ageValues, "a1824")+"_";
		encoded += GetValueFromArray(ageValues, "a2534")+"_";
		encoded += GetValueFromArray(ageValues, "a3544")+"_";
		encoded += GetValueFromArray(ageValues, "a4554")+"_";
		encoded += GetValueFromArray(ageValues, "a5564")+"_";
		encoded += GetValueFromArray(ageValues, "a65")+"_";
		// income
		encoded += GetValueFromArray(incomeValues, "i050")+"_";
		encoded += GetValueFromArray(incomeValues, "i50100")+"_";
		encoded += GetValueFromArray(incomeValues, "i100150")+"_";
		encoded += GetValueFromArray(incomeValues, "i150")+"_";
		// education
		encoded += GetValueFromArray(educationValues, "cn")+"_";
		encoded += GetValueFromArray(educationValues, "cu")+"_";
		encoded += GetValueFromArray(educationValues, "cg")+"_";
		// parents
		encoded += GetValueFromArray(parentingValues, "kn")+"_";
		encoded += GetValueFromArray(parentingValues, "ky")+"_";
		// ethnicity
		encoded += GetValueFromArray(ethnicValues, "rc")+"_";
		encoded += GetValueFromArray(ethnicValues, "raa")+"_";
		encoded += GetValueFromArray(ethnicValues, "ra")+"_";
		encoded += GetValueFromArray(ethnicValues, "rh")+"_";
		encoded += GetValueFromArray(ethnicValues, "ro")+"_";
		// reach & frequency
		encoded += campaignFocusValue + "_";

		encoded += "All_"; // category
		encoded += "Force include sites here..."; // extra sites

		return encoded;
	}

	function EncodeReachFrequencyParameters()
	{
            $nIMPRESSIONS = 0;
            $nGEO_COV = 1;
            $nGAMMA = 2;
            $nIP_ACC = 3;
            $nDEMO_COV_OVERRIDE = 4;
            $nRETARGETING = 5;
		impressions = $("#ImpressionsSelector").val();
		var geoCoverage = $("#RfRonGeoCoverage").val();
		var gamma = $("#RfGamma").val();
		var ipAccuracy = $("#RfIpAccuracy").val();
		var demoCoverageOverride = $("#RfPercentDemoOnline").val();
		var retargeting = $("#RetargetingCheckbox").is(':checked')?"1":"0";

		var encoded = ""+
			impressions+"|"+
			geoCoverage+"|"+
			gamma+"|"+
			ipAccuracy+"|"+
			demoCoverageOverride+"|"+
			retargeting;

		return encoded;
	}

	function EncodeDemographicAndReachFrequencyParameters()
	{
		var encodedReachFrequency = EncodeReachFrequencyParameters();
		var encodedDemographics = EncodeDemographicsInString();
		var encodedForcedSites = " ";
		var separatorToken = "||";
		var encodedParameters = 
			encodedDemographics + separatorToken + 
			encodedForcedSites + separatorToken + 
			encodedReachFrequency;
		return encodedParameters;
	}

	function SendDataToBody()
	{
    if ($("div#body_header").text()=="Media Targeting +" || $("div#body_header").text()=="Media Targeting -") 
		{
			UpdateSitesList();
     //update_site_pack("");
    }
		else 
		{
			UpdateReachFrequencyPage();
		}

		/*
		var encodedParameters = EncodeDemographicAndReachFrequencyParameters();
		$.ajax({
			url: "/siterank/"+encodedParameters,
			success: function(data, textStatus, xhr) 
			{
				$("#RfHiddenSites").html(data);
			},
			error: function(xhr, textStatus, error) 
			{
				$("#RfHiddenSites").html("Error: "+error);
			}
		});
		*/
	}

	function SetupSlider()
	{
                var demo_string =  jQuery.trim(getDemoFromTable());
                if (demo_string.charAt(47) == "_") {
                    var slider_value = demo_string.substring(46,47);
                } else if (demo_string.charAt(48) == "0") {
                    var slider_value = demo_string.substring(46,49);
                } else {
                    var slider_value = demo_string.substring(46,48);
                }
    
		$("#GenderSelector").dropdownchecklist({
			firstItemChecksAll: true,
			width: 250,
			onComplete: SendDataToBody
		});
		$("#AgeSelector").dropdownchecklist({
			firstItemChecksAll: true,
			width: 250,
			onComplete: SendDataToBody
		});
		$("#IncomeSelector").dropdownchecklist({
			firstItemChecksAll: true,
			width: 250,
			onComplete: SendDataToBody
		});
		$("#EthnicSelector").dropdownchecklist({
			firstItemChecksAll: true,
			width: 250,
			onComplete: SendDataToBody
		});
		$("#ParentingSelector").dropdownchecklist({
			firstItemChecksAll: true,
			width: 250,
			onComplete: SendDataToBody
		});
		$("#EducationSelector").dropdownchecklist({
			firstItemChecksAll: true,
			width: 250,
			onComplete: SendDataToBody
		});

		/*$("#RfCampaignFocusSlider").slider({ 
			max: 100, 
			min: 0,
			value: slider_value,
			width: 250,
			stop:SendDataToBody
		});*/
                
              


		$("#RetargetingCheckbox").click(SendDataToBody);
		$("#RfMediaComparisonSelection").change(SendDataToBody);

		$("#ImpressionsSelector").change(SendDataToBody);
		$("#RfRonGeoCoverage").change(SendDataToBody);
		$("#RfGamma").change(SendDataToBody);
		$("#RfIpAccuracy").change(SendDataToBody);
		$("#RfPercentDemoOnline").change(SendDataToBody);

		$("#RfMediaComparisonTitle").click(ToggleMediaComparisonVisibility);

		$("#RfPriceEstimateButtonValue").click(ToggleEstimateVisibility);
		$("#RfPriceEstimateDiscountLink").click(ToggleDiscountVisibility);
		$("#RfPriceEstimateCodeName").change(SendDataToBody);
	}

</script>

