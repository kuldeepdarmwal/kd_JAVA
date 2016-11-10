	  <h4>Campaign Details</h4> <span class="label <?php if(!$c_day_old_modify){echo 'label-success';}else{echo 'label-warning';}?>" style="position:relative;top:-10px;" id="c_timestamp_box">Last Updated: <?php echo $c_modify_timestamp; ?></span>
	  <div class="divider"></div>
	  <form class="form-horizontal">
	  <div class="control-group">
		  <label class="control-label" for="impression_budget_box">Impression Budget</label>
		  <div class="controls">
		  <input type="text" id="impression_budget_box" value="<?php echo $c_impressions; ?>">
		  </div>
	  </div>
	  <div class="control-group">
		  <label class="control-label" for="dollar_budget_box">$ Budget</label>
		  <div class="controls">
		  <input type="text" id="dollar_budget_box" value="<?php echo $c_dollars; ?>">
		  </div>
	  </div>
	  <div class="control-group">
		  <label class="control-label" for="start_date_box">Start Date</label>
		  <div class="controls">
		  <input type="date" id="start_date_box" value="<?php echo $c_start_date; ?>">
		  </div>
	  </div>
	  <div class="control-group">
		  <label class="control-label" for="end_date_box">End Date</label>
		  <div class="controls">
		  <input type="date" id="end_date_box" value="<?php echo $c_end_date; ?>">
		  </div>
	  </div>
	  </form>
	  <hr/>
<?php
foreach($adgroups_to_display as $adgroup)
{
    if(!$adgroup['day_old_modify'])
    {
	$time_warning_class = 'label-success';
    }
    else
    {
	$time_warning_class = 'label-warning';
    }
    if($adgroup['is_enabled'])
    {
	$checked = "checked=true";
    }
    else
    {
	$checked = "";
    }
echo
	'<h4>'.$adgroup['name'].' ('.$adgroup['ttd_id'].')</h4> <span class="label '.$time_warning_class.'" id="'.$adgroup['ttd_id'].'_timestamp_box">Last Updated: '.$adgroup['modify_timestamp'].'</span>
	  <form class="form-horizontal">
	  <div class="control-group">
		  <label class="control-label" for="'.$adgroup['ttd_id'].'_is_enabled_checkbox">Enabled?</label>
		  <div class="controls">
		  <input type="checkbox" '.$checked.' id="'.$adgroup['ttd_id'].'_is_enabled_checkbox"/>
		  </div>
	  </div>
	  <div class="control-group">
		  <label class="control-label" for="'.$adgroup['ttd_id'].'_impression_budget_box">Impression Budget</label>
		  <div class="controls">
		  <input type="text" id="'.$adgroup['ttd_id'].'_impression_budget_box" value="'.$adgroup['impression_budget'].'">
		  </div>
	  </div>
	  <div class="control-group">
		  <label class="control-label" for="'.$adgroup['ttd_id'].'_dollar_budget_box">$ Budget</label>
		  <div class="controls">
		  <input type="text" id="'.$adgroup['ttd_id'].'_dollar_budget_box" value="'.$adgroup['dollar_budget'].'">
		  </div>
	  </div>
	  <div class="control-group">
		  <label class="control-label" for="'.$adgroup['ttd_id'].'_daily_impression_box">Daily Impression Budget</label>
		  <div class="controls">
		  <input type="text" id="'.$adgroup['ttd_id'].'_daily_impression_box" value="'.$adgroup['daily_impression_budget'].'">
		  </div>
	  </div>
	  <div class="control-group">
		  <label class="control-label" for="'.$adgroup['ttd_id'].'_daily_dollar_box">Daily $ Budget</label>
		  <div class="controls">
		  <input type="text" id="'.$adgroup['ttd_id'].'_daily_dollar_box" value="'.$adgroup['daily_dollar_budget'].'">
		  </div>
	  </div>
	  </form>
	  <hr>';
}
?>
