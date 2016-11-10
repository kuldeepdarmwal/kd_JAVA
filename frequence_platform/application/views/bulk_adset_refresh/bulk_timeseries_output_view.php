<div class="container">

<?php 
		if ($timeseries_error_string != "")
		{
			echo '<div class="alert alert-error"><h3>Invalid Inputs Detected <small>please edit csv and <a href="/bulk_adset_refresh" class="link">retry</a></small></h3></div>';
			echo '<div class="row-fluid">';
			echo $timeseries_error_string;
			echo '</div>';
		}
		if ($timeseries_success_array != "")
		{
			echo '<div class="success"><h3>Time series refreshed successfully</h3></div>';
			echo "<table class='table'><tr><td>Advertiser Name</td><td>Campaign Name</td><td>Campaign ID</td><td>Time Series Schedule</td></tr>";
			foreach ($timeseries_success_array as $key => $value) {
				echo "<tr><td>".$value['Advertiser Name']."</td><td>".$value['Campaign Name']."</td><td>".$value['CampaignID']."</td><td>".$value['timeseries_string']."</td></tr>";
			}
			echo "</table>";
		}
?>

</div>

</body>
</html>