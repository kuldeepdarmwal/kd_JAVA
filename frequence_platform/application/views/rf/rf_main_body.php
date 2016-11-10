<?php

$frequencyColor = "'#4795D1'";
$reachColor = "'#168C6B'";

$reach = $reach_frequency_result['time_series'];

$numElementsInChart = 6; // corresponds to var of same name in rf.php
for ($i=0;$i<$numElementsInChart ;$i++){
    $impressions_labels[$i] = $i + 1;
    $geo_reach_uv[$i] = $reach['geo_reach'][$i]*$geo_pop;
    $demo_reach_uv[$i] = $reach['demo_reach'][$i]*$geo_pop*$demo_pop;
    $demo_size_time_series[$i] = round($demo_pop*$geo_pop,0);
}

$monthTitles = array();
for ($ii=0;$ii<$numElementsInChart ;$ii++){
	$monthTitles[$ii] = date("M", strtotime("+ ".($ii+1)." month", time()));
}

$rf_inputs = explode("|",urldecode($rf_parameters));
$nIMPRESSIONS = 0;
$nDEMO_COV = 1;
$nGAMMA = 2;
$nIP_ACCURACY = 3;
$nDEMO_ONLINE = 4;

$finalGeoPop = $geo_pop;
$finalDemoPop = $demo_pop*$geo_pop;
if($finalDemoPop > $finalGeoPop)
{
	$finalDemoPop = $finalGeoPop;
}

?>

function UpdateMonthlyPriceEstimate()
{
	$("#RfPriceEstimateValue").html("<?php echo '$'.number_format($monthlyPriceEstimate, 2).' / mo'; ?>");
	$("#RfPriceEstimateRawValue").html("<?php echo $monthlyPriceEstimate; ?>");
}

function UpdateMediaComparison()
{
	$("#RfMediaComparisonReachValue").html("<?php echo $mediaComparisonReach; ?>");
	$("#RfMediaComparisonFrequencyValue").html("<?php echo $mediaComparisonFrequency; ?>");
	$("#RfMediaComparisonOverallValue").html("<?php echo $mediaComparisonOverall; ?>");
}

function HighchartReachFrequencyGraph()
{
	var chart = new Highcharts.Chart({
		chart: {
				renderTo: 'RfBodyGraph',
				zoomType: 'xy',
				backgroundColor: '#fbfbfb'
		},
		title: {
				text: ''
		},
		subtitle: {
				text: ''
		},
		xAxis: [{
				categories: [
					<?php
						for($ii=0; $ii<$numElementsInChart;$ii+=1)
						{
							if($ii==($numElementsInChart-1))
							{
								echo "'".$monthTitles[$ii]."'";
							}
							else
							{
								echo "'".$monthTitles[$ii]."', ";
							}
						}
					?>
						]
		}],
		yAxis: [
		{ // Secondary yAxis
				title: {
						text: 'Reach',
						style: {
								color: <?php echo $reachColor; ?>
						}
				},
				labels: {
						formatter: function() {
								return (this.value * 100) + '%';
						},
						style: {
								color: <?php echo $reachColor; ?>
						}
				},
				max: 1.0,
				maxPadding: 0.0,
				min: 0.0,
				tickInterval: 0.25
		}
		, 
		{ // Primary yAxis
				labels: {
						formatter: function() {
								return this.value+'';
						},
						style: {
								color: <?php echo $frequencyColor; ?>
						}
				},
				title: {
						text: 'Average Frequency',
						style: {
								color: <?php echo $frequencyColor; ?>
						}
				},
				opposite: true,
				max: 25.0,
				maxPadding: 0.0,
				min: 0
		}
		],
		tooltip: {
				formatter: function() {
						if(this.series.name == 'Reach')
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
				layout: 'vertical',
				align: 'left',
				x: 50,
				verticalAlign: 'top',
				y: 0,
				floating: true,
				backgroundColor: '#fbfbfb'
		},
		series: [
		{
				name: 'Average Frequency',
				color: <?php echo $frequencyColor; ?>,
				type: 'column',
				yAxis: 1,
				data: [
				<?php
				for($ii=0; $ii<$numElementsInChart;$ii+=1)
				{
					if($ii != ($numElementsInChart - 1))
					{
						echo $reach['demo_frequency'][$ii].', ';
					}
					else
					{
						echo $reach['demo_frequency'][$ii].'';
					}
				}
				?>
				]
		}
		, 
		{
				name: 'Reach',
				color: <?php echo $reachColor; ?>,
				type: 'spline',
				yAxis: 0,
				data: [
				<?php
				for($ii=0; $ii<$numElementsInChart;$ii+=1)
				{
					if($ii != ($numElementsInChart - 1))
					{
						echo $reach['demo_reach'][$ii].', ';
					}
					else
					{
						echo $reach['demo_reach'][$ii].'';
					}
				}
				?>
				]
		}
		]
	});
}

$("#body_content").html(
'<div id="RfMainBody" class="RfMainBody" >'+
'	<div id="RfBodyExtraData" class="RfBodyExtraData">'+
'		<div id="RfPopulationText" class="RfPopulationText">'+
'			<div id="RfTargetedRegionText">'+
'				<?php echo $targeted_region_summary; ?>'+
'			</div>'+ 
'			<div class="RfGeoPopulationText">'+
'				<div id="RfGeoPopulationValue" style="font-size:32px"><?php echo number_format($finalGeoPop); ?>'+
'				</div>'+
'				<div id="RfGeoPopulationTitle" style="font-size:22px">Geo Population'+
'				</div> '+
'			</div>'+
'			<div class="RfDemoPopulationText">'+
'				<div id="RfDemoPopulationValue" style="font-size:32px"><?php echo number_format($finalDemoPop); ?>'+
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
'				<tr style="background-color: #666666; color: white;">'+
'					<td style="font-weight: bold;background-color:#fbfbfb;"><br>'+
'					</td>'+
					<?php
						for($ii=0; $ii<$numElementsInChart;$ii+=1) 
						{
							echo '\'<td style="font-weight: bold; text-align: center;">'.$monthTitles[$ii].'<br></td>\'+';
						}
					?>
'				</tr>'+
'			</thead>'+
'			<tbody>'+
'				<tr>'+
'					<td>IMPRESSIONS<br>'+
'					</td>'+
					<?php
						for($ii=0; $ii<$numElementsInChart;$ii+=1) 
						{
							echo '\'<td style="text-align: center;">'.number_format(($ii+1)*$rf_inputs[$nIMPRESSIONS]/1000).'k'.'<br></td>\'+';
						}
					?>
'				</tr>'+
'				<tr>'+
'					<td>REACH %<br>'+
'					</td>'+
					<?php
						for($ii=0; $ii<$numElementsInChart;$ii+=1) 
						{
							echo '\'<td style="text-align: center;">'.round($reach['demo_reach'][$ii]*100,1)."%".'<br></td>\'+';
						}
					?>
'				</tr>'+
'				<tr>'+
'					<td>REACH<br>'+
'					</td>'+
					<?php
						for($ii=0; $ii<$numElementsInChart;$ii+=1) 
						{
							echo '\'<td style="text-align: center;">'.number_format($demo_reach_uv[$ii]).'<br></td>\'+';
						}
					?>
'				</tr>'+
'				<tr>'+
'					<td>FREQUENCY<br>'+
'					</td>'+
					<?php
						for($ii=0; $ii<$numElementsInChart;$ii+=1) 
						{
							echo '\'<td style="text-align: center;">'.round($reach['demo_frequency'][$ii],2).'<br></td>\'+';
						}
					?>
'				</tr>'+
'			</tbody>'+
'		</table>'+
'	</div>'+
'</div>'
);

HighchartReachFrequencyGraph();
UpdateMonthlyPriceEstimate();
//UpdateMediaComparison();
