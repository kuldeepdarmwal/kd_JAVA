<html>
	<head>
	<?php $sites = explode("\n",$sites);  ?>
	<script type="text/javascript">
	var timer;
	var sites = new Array();
	var numSites;

	var numSitesWithCompleteDemographics = 0;
	var numSitesWithIncompleteDemographics = 0;
	var numSitesSkipped = 0;

	function run() {
		var ii=0;
		function myFunc() {
			timer = setTimeout(myFunc, 0);

			addSiteStatisticsForRow(sites[ii]);
			ii++;

			document.getElementById("currentCount").innerHTML = "# Sites Processed: "+ii+"/"+numSites;
			document.getElementById("currentCompleteCount").innerHTML = "# Sites with complete data: "+numSitesWithCompleteDemographics;
			document.getElementById("currentIncompleteCount").innerHTML = "# Sites with incomplete data: "+numSitesWithIncompleteDemographics;
			document.getElementById("currentSkippedCount").innerHTML = "# Sites skipped: "+numSitesSkipped;

			if(ii >= numSites) {
				stop();
			}
		}
		timer = setTimeout(myFunc, 0);
	}

	function stop() {
		clearInterval(timer);
	}

	function addSiteStatisticsForRow(siteName) {
		xmlhttp = new XMLHttpRequest();
		xmlhttp.open("GET", "q_scrape/get_row_stats?siteName="+siteName+"&display=<?php echo $disp ;?>", false);
		xmlhttp.send();
		var siteData = xmlhttp.responseText;		

		if(siteData != "") {
			var newTr = document.createElement("tr");
			var isDemographicDataComplete = true;

	var split = siteData.split(',');
	for(var ii=0; ii<split.length -1; ii++) {
	    var newTd = document.createElement("td");
	    newTd.style.backgroundColor = is_this_average(ii,split[25])? 'red' : 'white';
	    newTr.appendChild(newTd);

				var newText = document.createTextNode(split[ii]);
				newTd.appendChild(newText);

				if(split[ii]=="NA") {
					isDemographicDataComplete = false;
				}
			}

			document.getElementById("myTable").appendChild(newTr);

			if(isDemographicDataComplete == true) {
				numSitesWithCompleteDemographics++;
			}
			else {
				numSitesWithIncompleteDemographics++;
			}
		}
		else {
			numSitesSkipped++;
		}
	}

function is_this_average(which_column, average_flags){
     //first turn average flags to binary
     //see if the bit is set
     var which_bit = 25-which_column;
     var bin_string = Number(average_flags).toString(2);
     //alert('ave flags: '+average_flags+' - bin string: '+bin_string +" - total bits: "+bin_string.length +" - which bit: "+which_bit);
     if(which_bit <= bin_string.length){
       return (bin_string[bin_string.length - which_bit] == '1');
     }else{
       return false;
     }
     
   }

window.onload = function() {
    var nextSite = 0;
    numSites = <?php echo count($sites) ?>;
	<?php
    $numSites = count($sites);
    for ($ii=0;$ii<$numSites;$ii += 1) {
	$strToFix = 'sites['.$ii.'] = "'.$sites[$ii].'";';
	echo preg_replace('/[\x00-\x1F\x7F]/', "", $strToFix);
    }
	?>

    run();
}

	function testTableLoad() {
		alert("table loaded.");
	}

	function init() {
		alert("init begin");
		var el = document.getElementById("myTable");
		el.addEventListener("load", function() {testTableLoad();}, false);
		alert("init end");
	}

	</script>
	</head>
	<body>


	<?php
		if($disp == 'upload') echo '<h2 style="color:green">Uploading statistics to Demographics Database.</h2>';
		else echo '<h2 style="color:green">Displaying Demographics Only.</h2>';

		echo '<p><div id="currentCount"># Sites Processed: 0/'.count($sites).'</div><br /></p>';
		echo '<p><div id="currentIncompleteCount"># Sites with incomplete data: 0'.'</div><br /></p>';
		echo '<p><div id="currentCompleteCount"># Sites with complete data: 0/'.'</div><br /></p>';
		echo '<p><div id="currentSkippedCount"># Sites skipped: 0/'.'</div><br /></p>';



		$siteDemographics = array();

echo '<table id="myTable" border="1">';
echo '<tr>';
echo '<td>URL</td>';
echo '<td>Reach</td>';
echo '<td>Male</td>';
echo '<td>Female</td>';
echo '<td>Under 18</td>';
echo '<td>18-24</td>';
echo '<td>25-34</td>';
echo '<td>35-44</td>';
echo '<td>45-54</td>';
echo '<td>55-64</td>';
echo '<td>65+</td>';
echo '<td>Cauc.</td>';
echo '<td>Afr. Am.</td>';
echo '<td>Asian</td>';
echo '<td>Hisp.</td>';
echo '<td>Other</td>';
echo '<td>No Kids</td>';
echo '<td>Has Kids</td>';
echo '<td>$0-50k</td>';
echo '<td>$50-100k</td>';
echo '<td>$100-150k</td>';
echo '<td>$150k+</td>';
echo '<td>No College</td>';
echo '<td>College</td>';
echo '<td>Grad. Sch.</td>';
echo '</tr>';
    ?>

</body>
</html>
