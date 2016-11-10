<?php
require ('gCharts/gChart.php');


$rf_inputs = explode("|",urldecode($rf_parameters));
$nIMPRESSIONS = 0;
$nDEMO_COV = 1;
$nGAMMA = 2;
$nIP_ACCURACY = 3;
$nDEMO_ONLINE = 4;

$reach = $reach_frequency_result['time_series'];

//echo "impressions: ".$rf_inputs[$nIMPRESSIONS]."<br>";
//echo "demo online: ".$rf_inputs[$nDEMO_ONLINE]."<br>";
//echo "geo pop: ".$rf_inputs[$nGEO_POP]."<br>";
//echo "demo pop: ".$rf_inputs[$nDEMO_POP]."<br>";
//echo "gamma: ".$rf_inputs[$nGAMMA]."<br>";
//echo "geo coverage: ".$rf_inputs[$nGEO_COV]."<br>";
//echo "demo coverage: ".$rf_inputs[$nDEMO_COV]."<br>";

for ($i=0;$i<6;$i++){
    $impressions_labels[$i] = $i + 1;
    $geo_reach_uv[$i] = $reach['geo_reach'][$i]*$geo_pop;
    $demo_reach_uv[$i] = $reach['demo_reach'][$i]*$geo_pop*$demo_pop;
    $demo_size_time_series[$i] = round($demo_pop*$geo_pop,0);
}


///VENN DIAGRAMS
$scaleBy = 1;
$population = $geo_pop;
$demographicPopulation = $demo_pop*$population;
$geo_reach = $reach['geo_reach'][5]*$population;
$demo_reach = $reach['demo_reach'][5]*$demographicPopulation;
$impressions = 6*$rf_inputs[$nIMPRESSIONS];

$venn_diagram_string =  '<div style="float:right; margin:0px 0px 0px 0px;"><img src="http://chart.apis.google.com/chart?
		chs=300x300&
		cht=v&chco=d5d5d5,d95b44,80b5c5&
		chds=0,'.($population*$scaleBy).'&
		chd=t:'.($population*$scaleBy).',
				'.(($demographicPopulation*0.98)*$scaleBy).',
				'.($geo_reach*$scaleBy).',
				'.(($demographicPopulation*0.98)*$scaleBy).',
				'.($geo_reach*$scaleBy)*$rf_inputs[$nIP_ACCURACY].',
				'.($demo_reach*$scaleBy).',0&
				chdl=GEO+POPULATION|DEMOGRAPHIC+POPULATION|GEOGRAPHIC+REACH
				&chts=676767,9" width="300" height="300" alt="" /></div>';

echo $venn_diagram_string."<br>";

/////SET UP AXES AND TICK MARKS FOR TIME SERIES
	$pixelsWideLineChart=900;
	$pixelsTall=300;
	$numLeftAxisLabels = 4;//ceil(max($contactedValues)/$leftAxisIncrement);
	$leftAxisIncrement = 2000*ceil((max($demo_size_time_series)/$numLeftAxisLabels)/2000);
	$leftChartMax = $leftAxisIncrement*$numLeftAxisLabels;
	
	$numRightAxisLabels = 4;//ceil(max($contactedValues)/$leftAxisIncrement);
	$rightAxisIncrement = 0.05*ceil((max($reach['demo_frequency'])/$numRightAxisLabels)/0.05);
	$rightChartMax = $rightAxisIncrement*$numRightAxisLabels;
	for($i=0;$i<$numRightAxisLabels+1;$i++){
		$rightAxisLabels[$i] = $rightAxisIncrement*$i;
	}
	
	$scaleFactor = ($leftChartMax/$rightChartMax);
	for($i=0;$i<count($reach['demo_frequency']);$i++){
		$scaledFrequency[$i] = $scaleFactor*$reach['demo_frequency'][$i];
	}
	
	///////NEW LINE CHART - BOOKING FUNNEL
	$lineChart = new gLineChart($pixelsWideLineChart,$pixelsTall);
	$lineChart->addDataSet($demo_reach_uv);
	$lineChart->addDataSet($demo_size_time_series);
	$lineChart->addDataSet($scaledFrequency);
	$lineChart->setLegend(array("Reach(LHS)","Target Audience(LHS)","Frequency(RHS)"));
	$lineChart->setColors(array("003366","0099FFcc","d95b44"));
	$lineChart->setVisibleAxes(array('x','r','y'));
	$lineChart->setDataRange(0,$leftChartMax);
	$lineChart->addAxisRange(2, 0,$leftChartMax,$leftAxisIncrement);//Axis#,StartVal,EndVal,Increment
	$lineChart->addAxisLabel(1, $rightAxisLabels);
	$lineChart->addAxisLabel(0, $impressions_labels);
	$lineChart->addLineFill('B','00336622',0,0);
	$lineChart->addLineFill('B','80b5c522',1,0);
	$lineChart->setProperty('chxs', '1,FF0000');//chxs=0,00AA00,10,0.5,l,673838
	$lineChart->setProperty('chls', '3|3|3');//linetype and thickness
	$lineChart->setLegendPosition('b');
	$lineChart->setGridLines(20, 10);
	
	$imgURL = $lineChart->getUrl();
	$rf_chart_string =  '<div style="float:left;padding:50px 0px 0px 30px;"><img src='.$imgURL.' /></div>';




?>


<div class="yui-t1" id="doc3">
      <div id="hd">
        <h1>R/F PERFORMANCE ESTIMATE<br>
        </h1>
      </div>
      <div id="ft">
        <div id="yui-main">
          <div class="yui-b"></div>
        </div>
        Geographic Target: INSERT TARGET REGIONS HERE<br>
        Geo Population: <?php echo number_format($geo_pop)?> people live in the targeted geography<br>
        Demographic Target: <?php echo "INSERT TARGETED DEMOGRAPHIC STRINGS HERE"?><br>
        Demographic Population: <?php echo number_format($demo_pop*$geo_pop)?> people match the targeted demographics in the targeted geography [<?php echo number_format($demo_pop*100,2)."% of geo]"?><br>
        <?php echo number_format($reach_frequency_result['improvement_ratios']['demo_coverage']*100,2)."% of the targeted demography is online"?><br>
        Impressions:<?php echo number_format($reach_frequency_result['improvement_ratios']['impressions'],0)?><br>
        Landed Impressions:<?php echo number_format($reach_frequency_result['improvement_ratios']['landed_impressions'],0)?> impressions hit the target audience<br>
        Sitelist Efficacy:<?php echo number_format($reach_frequency_result['improvement_ratios']['sitelist_efficacy']*100,2)."% of impressions from this siteplan hit the target audience"?><br>
        RON Efficacy:<?php echo number_format($reach_frequency_result['improvement_ratios']['internet_efficacy']*100,2)."% of impressions would hit the target with a RON campaign"?><br>
        Geo Efficacy:<?php echo number_format($reach_frequency_result['improvement_ratios']['mail_efficacy']*100,2)."% would hit the target using direct mail"?><br>
        RON Outperformance: The sitelist is <?php echo number_format($reach_frequency_result['improvement_ratios']['RON']*100 - 100,2)."% more accurate that RON"?><br>
        Geo Outperformance: The sitelist is <?php echo number_format($reach_frequency_result['improvement_ratios']['mail']*100 - 100,2)."% more accurate than direct mailing the selected regions"?><br>
       
        <p style="width: 999px;">
            <?php echo $rf_chart_string;?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
          <?php //echo $venn_diagram_string; ?></p>
        <p style="width: 999px;"><br>
        </p>
        <p style="width: 999px;"><br>
        </p>
        <p></p>
        <table style="width: 900px; height: 121px;" border="0">
          <thead>
            <tr style="background-color: #97989A; color: 414142; font-family: ">
              <td class="month_td" style="font-weight: bold;background-color:white"><br>
              </td>
              <td class="month_td" style="font-weight: bold; text-align: center;">MONTH 1<br>
              </td>
              <td class="month_td" style="font-weight: bold; text-align: center;">MONTH 2<br>
              </td>
              <td class="month_td" style="font-weight: bold; text-align: center;">MONTH 3<br>
              </td>
              <td class="month_td" style="font-weight: bold; text-align: center;">MONTH 4<br>
              </td>
              <td class="month_td" style="font-weight: bold; text-align: center;">MONTH 5<br>
              </td>
              <td class="month_td" style="font-weight: bold; text-align: center;">MONTH 6<br>
              </td>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>IMPRESSIONS<br>
              </td>
              <td style="text-align: center;"><?php echo number_format(1*$rf_inputs[$nIMPRESSIONS])?><br>
              </td>
              <td style="text-align: center;"><?php echo number_format(2*$rf_inputs[$nIMPRESSIONS])?><br>
              </td>
              <td style="text-align: center;"><?php echo number_format(3*$rf_inputs[$nIMPRESSIONS])?><br>
              </td>
              <td style="text-align: center;"><?php echo number_format(4*$rf_inputs[$nIMPRESSIONS])?><br>
              </td>
              <td style="text-align: center;"><?php echo number_format(5*$rf_inputs[$nIMPRESSIONS])?><br>
              </td>
              <td style="text-align: center;"><?php echo number_format(6*$rf_inputs[$nIMPRESSIONS])?><br>
              </td>
            </tr>
            <tr>
              <td>REACH %<br>
              </td>
              <td style="text-align: center;"><?php echo round($reach['demo_reach'][0]*100,1)."%"?><br>
              </td>
              <td style="text-align: center;"><?php echo round($reach['demo_reach'][1]*100,1)."%"?><br>
              </td>
              <td style="text-align: center;"><?php echo round($reach['demo_reach'][2]*100,1)."%"?><br>
              </td>
              <td style="text-align: center;"><?php echo round($reach['demo_reach'][3]*100,1)."%"?><br>
              </td>
              <td style="text-align: center;"><?php echo round($reach['demo_reach'][4]*100,1)."%"?><br>
              </td>
              <td style="text-align: center;"><?php echo round($reach['demo_reach'][5]*100,1)."%"?><br>
              </td>
            </tr>
            <tr>
              <td>REACH<br>
              </td>
              <td style="text-align: center;"><?php echo number_format($demo_reach_uv[0]) ?><br>
              </td>
              <td style="text-align: center;"><?php echo number_format($demo_reach_uv[1]) ?><br>
              </td>
              <td style="text-align: center;"><?php echo number_format($demo_reach_uv[2]) ?><br>
              </td>
              <td style="text-align: center;"><?php echo number_format($demo_reach_uv[3]) ?><br>
              </td>
              <td style="text-align: center;"><?php echo number_format($demo_reach_uv[4]) ?><br>
              </td>
              <td style="text-align: center;"><?php echo number_format($demo_reach_uv[5]) ?><br>
              </td>
            </tr>
            <tr>
              <td>FREQUENCY<br>
              </td>
              <td style="text-align: center;"><?php echo round($reach['demo_frequency'][0],2)?><br>
              </td>
              <td style="text-align: center;"><?php echo round($reach['demo_frequency'][1],2)?><br>
              </td>
              <td style="text-align: center;"><?php echo round($reach['demo_frequency'][2],2)?><br>
              </td>
              <td style="text-align: center;"><?php echo round($reach['demo_frequency'][3],2)?><br>
              </td>
              <td style="text-align: center;"><?php echo round($reach['demo_frequency'][4],2)?><br>
              </td>
              <td style="text-align: center;"><?php echo round($reach['demo_frequency'][5],2)?><br>
              </td>
            </tr>
          </tbody>
        </table>
        <p><br>
        </p>
      </div>
    </div>
&nbsp;
&nbsp;
&nbsp;
&nbsp;
&nbsp;
