	<?php
	$totalReach = $realizedValueResponse->row()->totalReach;
	$realizedGm = $realizedValueResponse->row()->gm / $totalReach;
	$realizedGf = $realizedValueResponse->row()->gf / $totalReach;

	//echo '$totalReach: '.$totalReach.' ';
	//echo '$realizedGm: '.$realizedGm.' ';
	//echo '$realizedGf: '.$realizedGf.' ';
	$sdGm = $internetStandardDeviation->row()->gm;

	$meanTotalReach = $getInternetMean->row()->totalReach;
	$meanGm = $getInternetMean->row()->gm / $meanTotalReach;
	//echo '<div id="mt_gm_graph" style="position:absolute; top:21px; left:101px;"></div>';
	?>


document.getElementById("sliderBodyContent").innerHTML="<div id=\"sparkline_male\" class=\"sparkline_male\"> Replace me <div>";
//document.getElementById("shuber_test").innerHTML="<?php echo $realizedGm.', '.$meanGm.', '.$sdGm.', shuber_test'?>;";
//scaled_bullet_graph(<?php echo $realizedGm.', '.$meanGm.', '.$sdGm.', "shuber_test"'?>);
//scaled_bullet_graph(<?php echo $realizedGm.', '.$sdGm.', '.$meanGm.', "shuber_test"'?>);

function sparkline(length, id) {
	var index_value = length;
	var id = id;
    var target_color = '#aaaaaa';
    var target_value = 100;
    var lower = Math.min(index_value,100);
    var upper_color = '#fbfbfb';
    if (index_value > 190){
      index_value = 190;
      upper_color = '#d95b44';//alert color
    }
 
      
    $(id).sparkline([target_value,lower,200,index_value,lower], {
    type: 'bullet',
    targetWidth: 1,
    height: '10',
    targetColor: target_color,
    performanceColor: '#',
    rangeColors: [upper_color,'#45ADA8','#AAC2C1']});
}
sparkline(300, "#sparkline_male");

/*
function test_sparkline()
{
    $("#shuber_test").sparkline([0,0,60,30,30], {
    type: 'bullet',
    height: '8',
    targetWidth: 1,
    targetColor: '#ffffff',
    performanceColor: '#ffffff',
    rangeColors: ['red','green','orange']});

		document.getElementById("shuber_test").innerHTML="<?php echo $realizedGm.', '.$meanGm.', '.$sdGm.', shuber_test'?>;";
}
test_sparkline();
*/
