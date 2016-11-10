<?php
// $oneforthnightAgo = date("Y-m-d",strtotime ( '-14 day' , strtotime ( $date ) )) ; 
$id_arr="";
$min=0;
$c=0;
$cc=500;
$array_id=array();
$dataexplode=explode("$$",$encoded_data);
if($dataexplode[2]!=""){
$targetline=explode("|",$dataexplode[1]);
$cc=$targetline[0];
$campaign=$targetline[1];
$dates=explode("|",$dataexplode[0]);
$d1=$dates[0];
$d2=$dates[1];
//$d1="2011-09-20";
//$d2="2011-09-27";
$arr =explode("||",$dataexplode[2]);
foreach($arr as $val){
$ar = explode("|",$val);
foreach ($ar as $val1){
if($c==0){
$id=$val1;
}
if($c!=0){
$date1=explode(",",$val1);
$array_id[$id][$date1[1]]=$date1[0];
}
$c++;
}
$val_array['name']="'".$id."'";
$val_array['type']="'column'";
$str="";
while($d1!=$d2){
if(array_key_exists($d1, $array_id[$id])){
$str.= $array_id[$id][$d1].",";
$id_arr.="'".$d1."',";
}
//else{
//$str.="'0',";
//}
$d1 = date("Y-m-d",strtotime ( '+1 day' , strtotime ( $d1 ) )) ;
//$val_array['data']=trim($str,",");
}
$coun=substr_count($str, ',');
if($coun>$min){
$min=$coun;
}
$val_array['data']= "[".trim($str,",")."]";
$d1=$dates[0];
//$d1="2011-09-20";
$arri1[] = $val_array;
$c=0;
}
//print json_encode($arri1);
/*while($d1!=$d2){
$id_arr.="'".$d1."',";
$d1 = date("Y-m-d",strtotime ( '+1 day' , strtotime ( $d1 ) )) ;
}*/
$splinedata="";
$id_arr=trim($id_arr,",");
$coun=substr_count($id_arr, ',');
while($c!=$min){
$splinedata.=$cc.",";
$c++;
}
$val_array['name']="'Daily Target Line'";
$val_array['data']="[".trim($splinedata,",")."]";
$val_array['type']="'spline'";
$arri1[] = $val_array;
?>

<html>
<head>
<!--<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
<script src="/js/highchart/js/highcharts.js" type="text/javascript"></script>
<script src="http://code.highcharts.com/modules/exporting.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/prototype/1.7.0.0/prototype.js" type="text/javascript"></script>
<script src="/js/highchart/js/adapters/prototype-adapter.js" type="text/javascript"></script>-->
<script src="/js/jquery-1.7.1.min.js" type="text/javascript"></script>
<script src="/js/highchart/js/highcharts.js" type="text/javascript"></script>
<script src="/js/highchart/js/modules/exporting.js"></script>
<script src="/js/highchart/prototype.js" type="text/javascript"></script>
<script src="/js/highchart/js/adapters/prototype-adapter.js" type="text/javascript"></script>

<script type="text/javascript">

(function($){ // encapsulate jQuery



var chart;
$(document).ready(function() {
        var abc=new Array(<?php print $id_arr;?>);
        var val_array= <?php print str_replace("\"","",json_encode($arri1));?>;
	var camp1 = document.getElementById("campaignname").innerHTML;
        var camp = camp1.replace(":","->");
	chart = new Highcharts.Chart({
		chart: {
			renderTo: 'container',
		},
		title: {
			text: 'Impressions ['+camp+']'
		},
		xAxis: {
                       labels: {
                    rotation: -60,
                    align: 'right',
                    style: {
                        fontSize: '11px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                },
			categories: abc
		},
		yAxis: {
			min: 0,
			title: {
				text: ''
			},
			stackLabels: {
				enabled: false,
				style: {
					fontWeight: 'bold',
					color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
				}
			}
		},
		/*legend: {
			align: 'right',
			x: -100,
			verticalAlign: 'bottom',
			y: 20,
			floating: true,
			backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColorSolid) || 'gray',
			borderColor: '#000',
			borderWidth: 1,
			shadow: false
		},*/
		tooltip: {
			formatter: function() {
			if(this.y !== 0) {
			return '<b>'+ this.x +'</b><br/><br/>'+this.y;
			}
//	return '<b>'+ this.x +'</b><br/>'+
			//		this.series.name +': '+ this.y +'<br/>'+
			//		'Total: '+ this.point.stackTotal;
			}
		},
		plotOptions: {
			series: {
				stacking: 'normal',
                                dataLabels: {
                    enabled: false,
                    rotation: -90,
                    color: '#FFFFFF',
                    align: 'right',
                    x: -3,
                    y: 10,
                    formatter: function() {
                    return this.y    
                    },
                    style: {
                        fontSize: '13px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                } 
			}
		},
		series: val_array
	});
});


})(jQuery);

</script>



</head>
<body>
<div id="container" style=" margin: 0 auto"></div>
</body>
</html>
<?php } ?>
