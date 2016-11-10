<?php
error_reporting(E_ALL);
extract($_GET);
$endDate = date('Y-m-d', strtotime($reportDate));
$startDate1 = new DateTime($endDate);
$startDate1->modify('-1 month');
$startDate1 = $startDate1->format('Y-m-d');
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" media="all" href="/css/campaign_health/campaign_health_index_style_mod.css" type="text/css">
<script type="text/javascript" src="/libraries/external/jquery-1.10.2/jquery-1.10.2.min.js"></script>
<script src="/js/campaign_health/xdate.js" type="text/javascript"></script>
<script src="/js/campaign_health/campaign_health_highcharts.js" type="text/javascript"></script>
<script src="/js/highchart/js/highcharts.js"></script>
<script src="/js/highchart/js/modules/exporting.js"></script>
<link href="//fonts.googleapis.com/css?family=Oxygen" rel="stylesheet" type="text/css">
<style>
#circle{
    left: 100px;
}
</style>
</head>
<body onload="show_detail_modal('<?php echo $cid;?>', '<?= $startDate1?>', '<?php echo $endDate;?>', '')" style="padding: 0;">
	<div id="detail_modal" class="" tabindex="-1" role="dialog" aria-labelledby="detail_modalLabel" aria-hidden="true">
	    <div id="modal_detail_body">
		    <p>&nbsp;</p>
	    </div>
	</div>
</body>

<script>
function show_detail_modal(campaign_id, start, end, raw_title){   
    var title = unescape(raw_title);
    document.getElementById('modal_detail_body').innerHTML = '<div class="progress progress-striped active"> <div class="bar" style="width: 100%;"></div></div>';
    $.ajax({
        url: "/campaign_health/get_campaign_details_id/",
        type: 'POST',
        data: {campaign_id: campaign_id, start: start, end: end},
        dataType: 'json',
        success: (function (msg) {
            if (msg.is_success)
            {
                //toggle_loading_gif('hidden');
                document.getElementById('modal_detail_body').innerHTML = '<div id="circle"></div><div id="time_series_chart" style=" height: 400px; margin: 0 auto"></div><div id="second_chart" style=" height: 0px; clear:both;"></div><div id="cities_chart" style=" height: 400px; "></div>';
                console.log(msg);
		console.log(msg.time_series);
                render_time_series_chart_with_retargeting(msg.time_series, title);
                if(msg.second_block)
                {
                    document.getElementById('second_chart').style.height = '400px';
                    render_second_chart(msg.second_block, msg.second_block_title);
                }
                render_cities_chart(msg.city_block,'Top 10 Cities');
            }
            else
            {
                set_chart_status('important', "error 11242, something went wrong: " + msg.errors.join(", "));
            }
        }),
        error: (function (jqXHR, textStatus, errorThrown) {
            set_chart_status('important', "error 5335, something went wrong: " + errorThrown);
        })
    });
}

function set_chart_status(label, copy)
{
 if(label === null)
 {
  document.getElementById("modal_detail_body").innerHTML ='';
 }
 else
 {
  document.getElementById("modal_detail_body").innerHTML = '<span class="label label-'+label+'">'+copy+'</span>';
 }
}

function render_cities_chart(data_array,title){
    var city_names = new Array();
    var impressions = new Array();


    for (var i=0;i<data_array.city_results.length;i++){
        city_names[i] = data_array.city_results[i].city + '-'+data_array.city_results[i].region;
        impressions[i] = parseInt(data_array.city_results[i].impressions);
    }

    Highcharts.setOptions({
        chart: {
            style: {
                fontFamily: 'Oxygen',
                fontColor: 'rgba(0,0,0,1)'
            }
        }
    });



    var chart;
    $(document).ready(function() {
        chart = new Highcharts.Chart({
            chart: {
                renderTo: 'cities_chart',
                type: 'bar',
                plotBackgroundColor: 'rgba(0,0,0,0)',
                backgroundColor: 'rgba(0,0,0,0)'
            },
            title: {
                text: title,
                style:{
                    //color: 'rgba(255,255,255,1)'
                }
            },
	    credits: {
		enabled: false
	    },

            xAxis: {
                categories: city_names,
                labels:{
                    style:{
                        //color: 'rgba(255,255,255,1)'
                    }
                },
                title: {
                    text: null
                }
            },
            yAxis: {
                title: {
                    text: '',
                    align: 'high'
                },
                labels: {
                    overflow: 'justify',
                    style:{
                        //color: 'rgba(255,255,255,1)'
                    }
                }
            },
            tooltip: {
                formatter: function() {
                    return ''+
                        this.series.name +': '+ this.y/1000 +'k';
                }
            },
            plotOptions: {
                bar: {
                    dataLabels: {
                        enabled: true,
                        style:{
							//color: 'rgba(255,255,255,1)'
                        }
                    }
                }
            },
            legend: {
                align: 'left',
                verticalAlign: 'top',
                y: 0,
                floating: true,
                borderWidth: 0,
                itemHiddenStyle: {
                    //color: '#000'
                },
                itemStyle: {
                    //color: '#fff'
                }
            },
            credits: {
                enabled: false
            },
			exporting: {
				buttons: {
					printButton: {
						enabled: false
					}
				}
			},
            series: [{
                name: 'Impressions',
                data: impressions,
                color: 'rgba(244, 217, 103, 0.6)'
            }]
        });
    });
}

    function numberWithCommas(x) {
	var parts = x.toString().split(".");
	parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
	return parts.join(".");
    }
function render_second_chart(data_array,title){
    var site_names = new Array();
    var impressions = new Array();

	var plot_background_color = ''; // transparent with IE7
	var background_color = ''; // transparent with IE7
	var plot_options_color = 'rgb(255,255,255)';
	var series_color = 'rgb(244, 172, 123)';

	function contains(str, substr)
	{
		return !!~('' + str).indexOf(substr);
	}
	var test_rgba_element = document.createElement('campaign_health_test_rgba');
	var test_rgba_style = test_rgba_element.style;
	test_rgba_style.cssText = 'background-color:rgba(150, 255, 150, 0.5)';
	// Colors with Alpah channel
	if(contains(test_rgba_style.backgroundColor, 'rgba'))
	{
		plot_background_color = 'rgba(0,0,0,0)';
		background_color = 'rgba(0,0,0,0)';
		plot_options_color = 'rgba(255,255,255,1)';
		series_color = 'rgba(237, 117, 35, 0.6)';
	}
    


    for (var i=0;i<data_array.site_results.length;i++){
        site_names[i] = data_array.site_results[i].site;
        impressions[i] = parseInt(data_array.site_results[i].impressions);
    }

    Highcharts.setOptions({
        chart: {
            style: {
                fontFamily: 'Oxygen'
                //,fontColor: 'rgba(0,0,0,1)'
            }
        }
    });

    var chart;
    $(document).ready(function() {
        chart = new Highcharts.Chart({
            chart: {
                renderTo: 'second_chart',
                type: 'bar',
                plotBackgroundColor: plot_background_color,
                backgroundColor: background_color
            },
            title: {
                text: title,
                style:{
                    //color: 'rgb(255,255,255)'
                }
            },
	    credits: {
		enabled: false
	    },
            xAxis: {
                categories: site_names,
                labels:{
                    style:{
                        //color: 'rgb(255,255,255)'
                    }
                },
                title: {
                    text: null
                }
            },
            yAxis: {
                type: 'logarithmic',
                title: {
                    text: '',
                    align: 'high'
                },
                labels: {
                    overflow: 'justify',
                    style:{
                        //color: 'rgb(255,255,255)'
                    }
                }
            },
            tooltip: {
                formatter: function() {
                    return ''+
                        this.series.name +': '+ this.y/1000 +'k';
                }
            },
            plotOptions: {
                bar: {
                    dataLabels: {
                        enabled: true,
                        style:{
							color: plot_options_color
                        }
                    }
                }
            },
            legend: {
                align: 'left',
                verticalAlign: 'top',
                y: 0,
                floating: true,
                borderWidth: 0,
                itemHiddenStyle: {
                    color: '#000'
                },
                itemStyle: {
                    //color: '#fff'
                }
            },
            credits: {
                enabled: false
            },
			exporting: {
				buttons: {
					printButton: {
						enabled: false
					}
				}
			},
            series: [{
                name: 'Impressions',
                data: impressions,
                color: series_color
            }]
        });
    });
}



function render_time_series_chart_with_retargeting(data_array, title){
    var chart;
    var categories_data = new Array();
    var all_impressions_data = new Array();
    var all_clicks_data = new Array();
    var retargeting_impressions_data = new Array();
    var retargeting_clicks_data = new Array();
    var total_impressions = 0;
    var total_clicks = 0;
    var CTR;

    for (var i=0;i<data_array.time_series.length;i++){
        categories_data[i] = data_array.time_series[i].date;
        all_impressions_data[i] = parseInt(data_array.time_series[i].total_impressions);
        all_clicks_data[i] = parseInt(data_array.time_series[i].total_clicks);
        retargeting_impressions_data[i] = parseInt(data_array.time_series[i].rtg_impressions);
        retargeting_clicks_data[i] = parseInt(data_array.time_series[i].rtg_clicks);
        total_impressions = total_impressions + all_impressions_data[i];
	total_clicks = total_clicks + all_clicks_data[i];
    }

    var myTickInterval = Math.ceil(data_array.time_series.length / 32);

    CTR = (Math.round(100000*total_clicks/total_impressions)/1000)+'%';
    document.getElementById('circle').innerHTML = '<br><span style="font-size:20px; font-family: \'Homenaje\'">'+CTR+'<span style="font-size:14px"> CTR</span></span><br><span style="font-size:20px; font-family: \'Homenaje\'">'+numberWithCommas(Math.round(total_impressions/100)/10)+'k<span style="font-size:14px"> IMPRS</span></span><br><span style="font-size:20px; font-family: \'Homenaje\'">'+total_clicks+'<span style="font-size:14px"> CLICKS</span></span>'


    Highcharts.setOptions({
        chart: {
            style: {
                fontFamily: 'Oxygen',
                fontColor: 'rgba(0,0,0,1)'
            }
        }
    });


    var impression_color = 'rgba(91, 202, 233, 1)';     
    var impression_fade_color_0 = 'rgba(91, 202, 233, 0.9)';     
    var impression_fade_color_1 = 'rgba(91, 202, 233, 0.5)';

    var retargeting_color = 'rgba(237, 117, 35, 1)';     
    var retargeting_fade_color_0 = 'rgba(237, 117, 35, 0.9)';     
    var retargeting_fade_color_1 = 'rgba(237, 117, 35, 0.5)';

    var clickscolor = 'white';     
    var retargetingclickscolor = 'rgba(198, 151, 192, 1)';     
    var clicksborder_color = 'grey';

    var legendbackgroundcolor = 'white';     
    var legendhiddencolor = 'rgba(165, 165, 165, 1)';



    //var impression_color = 'rgba(91,202,233,1)';
    //var impression_fade_color_0 = 'rgba(91,202,233,0.9)';
    //var impression_fade_color_1 = 'rgba(91,202,233,0)';
    //var click_color = 'rgba(255,255,255,1)';
    //var click_bar_color = 'rgba(255,255,255,0.85)';
    $(document).ready(function() {



        chart = new Highcharts.Chart({
            chart: {
                renderTo: 'time_series_chart',
                type: 'area',
                plotBackgroundColor: 'rgba(0,0,0,0)',
                backgroundColor: 'rgba(0,0,0,0)'

            },
            title: {
                text: 'Recent Daily Performance',
                style:{
                    //color: 'rgba(255,255,255,1)'
                }
            },
	    credits: {
		enabled: false
	    },
	    
            yAxis: [{               
                title: {                 
                    text: 'Impressions',                 
                    style: {                 
                        //color: 'white'             
                    }             
                },             
                labels:{                  
                    style: {                     
                        //color: 'white',                      
                        fontWeight: 'bold'                  
                    }             
                }
            },
                    {               

                        title: {                 
                            text: 'Clicks',
                            style: {                 
                                //color: 'white'             
                            }
                        },
                        opposite: true         
                    }],
            xAxis: {             
                labels:{
                    rotation: -45,                     
                    align: 'right',
                    style: {                     
                        //color: 'white',                      
                        fontWeight: 'bold'                  
                    }
                },             
                categories: categories_data,
                tickInterval: myTickInterval
            },

            plotOptions: {
                column: {                 
                    grouping: false
                }
            },         
            legend: {             
                align: 'center',             
                verticalAlign: 'bottom',             
                floating: false,             
                borderWidth: 1,             
                backgroundColor: legendbackgroundcolor,             
                borderRadius: 2,             
                itemHoverStyle: {                 
                    color: '#000'             
                },             
                itemHiddenStyle: {                 
                    color: legendhiddencolor             
                }
            },
			credits: {
                enabled: false
            },
			exporting: {
				buttons: {
					printButton: {
						enabled: false
					}
				}
			},
            series: [{             
                name: 'All Impressions',             
                type:'area',             
                zIndex: 0,             
                yAxis: 0,             
                data: all_impressions_data,
                color: impression_color,             
                marker: {                 
                    enabled: true,                 
                    symbol: 'circle',                 
                    lineWidth: 1,                 
                    radius: 4,                 
                    states: {                    
                        hover: {                        
                            enabled: true                    
                        }                  
                    }             
                },             
                fillColor: {                         
                    linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1},                         
                    stops: [                             
                        [0, impression_fade_color_0],                             
                        [1, impression_fade_color_1]                         
                    ]                     
                }                            
            },
                     {             
                         name: 'Retargeting Impressions',             
                         visible: true,             
                         type: 'area',             
                         zIndex: 2,             
                         yAxis: 0,             
                         data: retargeting_impressions_data,
                         color: retargeting_color,             
                         marker: {                 
                             enabled: true,                 
                             symbol: 'circle',                 
                             lineWidth: 1,                 
                             radius: 4,                 
                             states: {                    
                                 hover: {                        
                                     enabled: true                    
                                 }                  
                             }             
                         },             
                         fillColor: {                         
                             linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1},                         
                             stops: [                             
                                 [0, retargeting_fade_color_0],                             
                                 [1, retargeting_fade_color_1]                         
                             ]                     
                         }                            
                     },
                     {             
                         name: 'All Clicks',             
                         color: clickscolor,             
                         borderColor: clicksborder_color,             
                         borderWidth: 1,             
                         type: 'column',             
                         zIndex: 2,             
                         yAxis: 1,             
                         data: all_clicks_data
                     },
                     {             
                         name: 'Retargeting Clicks',             
                         visible: true,                         
                         color: retargetingclickscolor,             
                         type: 'column',             
                         zIndex: 3,             
                         yAxis: 1,             
                         borderColor: clicksborder_color,             
                         borderWidth: 1,             
                         data: retargeting_clicks_data
                     }]     
        }); 
    });
    
}
function numberWithCommas(x) {
    var parts = x.toString().split(".");
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    return parts.join(".");
}
</script>
</html>