<?php

?>

<html>
<head>
	<script type="text/javascript">
		var timer;

		var nextDateToTransfer;
		var nextDateToProcess;
		var endDateToProcess;

		var numRawUploadStepsCompleted = 0;
		var numAggregationStepsCompleted = 0;

		var needsToOptimize = false;
		var shouldDoUploadOnly = false;

		function run() 
		{
			var divElem = document.createElement("div");
			divElem.innerHTML = "run... ";
			var elem = document.getElementById("myTable");
			elem.insertBefore(divElem, elem.firstChild);

			function myFunc() {
				timer = setTimeout(myFunc, 0);

				if(nextDateToProcess >= endDateToProcess)
				{
					shouldDoUploadOnly = document.getElementById("isUploadOnly").checked == true;
					if(nextDateToTransfer >= endDateToProcess || shouldDoUploadOnly == true)
					{
						stop();
						isRunning = false;
						var el = document.getElementById("submitButton");
						el.value = 'Start upload';

						var divElem = document.createElement("div");
						divElem.innerHTML = "<h2>Finished.</h2>";
						var elem = document.getElementById("myTable");
						elem.insertBefore(divElem, elem.firstChild);
					}
					else
					{
						if(needsToOptimize)
						{
							needsToOptimize = false;
							optimizeTables();
						}

						processAndTransfer(nextDateToTransfer);
						var nextTime = nextDateToTransfer.getTime()+(24*60*60*1000);
						nextDateToTransfer.setTime(nextTime);

						numAggregationStepsCompleted++; 
						document.getElementById("numAggregatedSteps").innerHTML = numAggregationStepsCompleted;
					}
				}
				else
				{
					var nextEndDateToProcess = new Date();
					nextEndDateToProcess.setTime(nextDateToProcess.getTime()+60*60*1000);
					addSiteStatisticsForRow(nextDateToProcess, nextEndDateToProcess);
					nextDateToProcess = nextEndDateToProcess;

					numRawUploadStepsCompleted++; 
					document.getElementById("numRawUploadSteps").innerHTML = numRawUploadStepsCompleted;

					needsToOptimize = true;
				}
			}

			timer = setTimeout(myFunc, 0);
		}

		function stop() {
			clearInterval(timer);
		}

		function optimizeTables()
		{
			xmlhttp = new XMLHttpRequest();
			xmlhttp.open("POST", "/uploadtradedeskdata/OptimizeIntermediateTables", false); 
			xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			xmlhttp.send("");
			var responseData = xmlhttp.responseText;		
			var divElem = document.createElement("div");
			divElem.innerHTML = responseData;
			var elem = document.getElementById("myTable");
			elem.insertBefore(divElem, elem.firstChild);
		}

		function processAndTransfer(myDate) 
		{
			xmlhttp = new XMLHttpRequest();
			xmlhttp.open("POST", "/uploadtradedeskdata/ProcessAndTransferData", false); 
			xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			var myDateString = 
				""+myDate.getFullYear()+"-"+
				(myDate.getMonth()+1)+"-"+
				myDate.getDate();
			var sendString = "date="+myDateString;

			xmlhttp.send(sendString);
			var siteData = xmlhttp.responseText;		
			var divElem = document.createElement("div");
			divElem.innerHTML = siteData;
			var elem = document.getElementById("myTable");
			elem.insertBefore(divElem, elem.firstChild);
		}

		function addSiteStatisticsForRow(startDate, endDate) 
		{
			xmlhttp = new XMLHttpRequest();
			xmlhttp.open("POST", "/uploadtradedeskdata/LoadData", false); 
			xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

			var startDateString = 
				""+startDate.getFullYear()+"-"+
				(startDate.getMonth()+1)+"-"+
				startDate.getDate()+" "+
				startDate.getHours()+":00:00";
			var endDateString = 
				""+endDate.getFullYear()+"-"+
				(endDate.getMonth()+1)+"-"+
				endDate.getDate()+" "+
				endDate.getHours()+":00:00";

			var sendString = "startDateTime="+startDateString+"&endDateTime="+endDateString;
			xmlhttp.send(sendString);
			var siteData = xmlhttp.responseText;		

			var divElem = document.createElement("div");
			divElem.innerHTML = siteData;
			var elem = document.getElementById("myTable");
			elem.insertBefore(divElem, elem.firstChild);
			//document.getElementById("myTable").appendChild(divElem);
		}

		function testTableLoad() 
		{
			alert("table loaded.");
		}

		var isRunning = false;
		function startOrStopOrContinueUploading()
		{
			if(isRunning == true)
			{
				var el = document.getElementById("submitButton");
				el.value = 'Start upload';
				//alert("stop running.");
				stop();
				isRunning = false;

				var divElem = document.createElement("div");
				divElem.innerHTML = "Stop.";
				document.getElementById("myTable").appendChild(divElem);
			}
			else
			{
				var el = document.getElementById("submitButton");
				el.value = 'Stop upload';

				var beginDateValue = document.getElementById("beginDate").value;
				var endDateValue = document.getElementById("endDate").value;
				
				var startDate = new Date(beginDateValue);//+" PST");
				nextDateToTransfer = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate());
				nextDateToProcess = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate(), startDate.getHours(), 0, 0, 0);
				endDateToProcess = new Date(endDateValue);//+" PST");

				//var dateDeltaMilliseconds = endDate - beginDate;
				//var dateDeltaHours = dateDeltaMilliseconds / (1000 * 60 * 60);

				run();
				isRunning = true;

				var divElem = document.createElement("div");
				divElem.innerHTML = "Start."+startDate+" : "+endDateToProcess;
				var elem = document.getElementById("myTable");
				elem.insertBefore(divElem, elem.firstChild);
			}
		}

	</script>
</head>
<body>
<?php
echo 'Trade Desk upload '.date("Y-m-d H:i:s").'<br />';

$formAttributes = array(
'onsubmit' => 'startOrStopOrContinueUploading(); return false;'//'return false;'
);
echo form_open('upload_td_data', $formAttributes);

	//'value' => date("Y-m-d 00:00:00", strtotime('-2 day')),
$inputBeginDateData = array(
	'id' => 'beginDate',
	'value' => date("m/d/Y 00:00:00", strtotime('-2 day')),
	'maxlength' => '100',
	'size' => '50'
);
echo form_input($inputBeginDateData);
echo '<br />';

	//'value' => date("Y-m-d 00:00:00", strtotime('-1 day')),
$inputEndDateData = array(
	'id' => 'endDate',
	'value' => date("m/d/Y 00:00:00", strtotime('-1 day')),
	'maxlength' => '100',
	'size' => '50'
);
echo form_input($inputEndDateData);
echo '<br />';

$checkboxData = array(
    'name'        => 'isUploadOnly',
    'id'          => 'isUploadOnly',
    'value'       => 'onlyUpload',
    'checked'     => FALSE
    );
echo form_checkbox($checkboxData);
echo 'do upload only<br />';

$submitAttributes = array(
	'name' => 'submitTradeDeskUpload',
	'id' => 'submitButton',
	'value' => 'Start upload'
);
echo form_submit($submitAttributes, 'Start upload');
echo form_close();

?>
<div>Upload steps completed: <strong><span id="numRawUploadSteps">0</span></strong></div>
<div>Aggreagtion steps completed: <strong><span id="numAggregatedSteps">0</span></strong></div>
<br />
<div id="myTable">
</div>
</body>
</html>

