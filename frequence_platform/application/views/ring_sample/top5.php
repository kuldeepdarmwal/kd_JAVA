<?php
if($encoded_data!=""){
$name="";
$value="";
// $oneforthnightAgo = date("Y-m-d",strtotime ( '-14 day' , strtotime ( $date ) )) ;
$arr=explode("||",$encoded_data);
foreach($arr as $val){
$ar = explode("|",$val);
$name.="'".$ar[0]."',";
$value.=$ar[1].",";
}
$value=trim($value,",");
$name=trim($name,",");

if(count($arr) == 1) {
 $value = $value.",''";
 $name = $name.",''";
}

?>



<html>
<head>
<!--
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
<script src="/js/highchart/js/highcharts.js" type="text/javascript"></script>
<script src="http://code.highcharts.com/modules/exporting.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/prototype/1.7.0.0/prototype.js" type="text/javascript"></script>
<script src="/js/highchart/js/adapters/prototype-adapter.js" type="text/javascript"></script>
-->
<script src="/js/jquery-1.7.1.min.js" type="text/javascript"></script>
<script src="/js/highchart/js/highcharts.js" type="text/javascript"></script>
<script src="/js/highchart/js/modules/exporting.js"></script>
<script src="/js/highchart/prototype.js" type="text/javascript"></script>
<script src="/js/highchart/js/adapters/prototype-adapter.js" type="text/javascript"></script>

<script type="text/javascript">
(function($){ 
    var chart;
    var name = new Array(<?php print $name;?>);
    var val = new Array(<?php print $value;?>);
    $(document).ready(function() {
        chart = new Highcharts.Chart({
            chart: {
                renderTo: 'container1',
                type: 'bar'
            },
            title: {
                text: 'TOP 5 SITES IMPRESSION'
            },
            subtitle: {
                text: ''
            },
            xAxis: {
                categories: name,
                title: {
                    text: null
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: '',
                    align: 'high'
                },
                labels: {
                    overflow: 'justify'
                }
            },
            tooltip: {
                formatter: function() {
                    return ''+
                        this.series.name +': '+ this.y ;
                }
            },
            plotOptions: {
                bar: {
                    dataLabels: {
                        enabled: true
                    }
                }
            },
            legend: {
                layout: 'vertical',
                align: 'right',
                verticalAlign: 'top',
                x: -100,
                y: 100,
                floating: true,
                borderWidth: 1,
                backgroundColor: '#FFFFFF',
                shadow: true
            },
            credits: {
                enabled: false
            },
            series: [{
		showInLegend: false,
                name:"impressions" ,
                data: val
            }]
        });
    });
    
})(jQuery);
</script>





</head>
<body>
<div id="container1" style="min-width: 400px; height: 150px; margin: 0 auto"></div>
</body>
</html>
<?php } ?>
