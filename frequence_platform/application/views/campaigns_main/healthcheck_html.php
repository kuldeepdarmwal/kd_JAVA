<!--CAMPAIGN HEALTHCHECK VIEW -->
<style type="text/css">
   .form-horizontal .control-group { margin-bottom: 8px; }
</style>
<div style="margin-left: 50px; margin-right: 50px;">
	<h3 style="padding-right: 10px;">Report Date: 	<?php echo $report_date; ?><small> <span id="progress_span">initializing </span></small>
	</h3>
	<?php //echo $partner_id.'<br>'; ?>
	<?php //echo $role.'<br>'; ?>
	<div id="master_loading_bar" class="progress progress-striped" style="visibility: hidden">
  		<div id="master_loader" class="bar bar-success" style="width: 100%;"></div>
	</div>
	
	<div id="campaigns_main_filter" width="100%">
		<i class="icon-filter"></i> Filter
		<div style="float: right; padding: 10px">
		    	<?php if($role == 'sales' OR $role == 'admin' OR $role == 'ops')
			      {
				echo '<button data-toggle="modal" data-target="#bulk_download_modal" class="btn btn-inverse" type="button" id="bulk_download_button" ><i class="icon-download icon-white"></i> Bulk Download</button>';
			      }
			      
			if($partner_id == 1 && $role!='sales'){
				echo ' <a class="btn btn-danger" href="/campaign_health/graveyard" target="_blank"><i class="icon-trash icon-white"></i> Archived</a>';
			} ?>
			<a class="btn btn-primary" href="/report" target="_blank"><i class="icon-signal icon-white"></i> Reports</a>


        </div>
   	</div>
	<table id="the_campaign_health_table" class="tableWithFloatingHeader table table-bordered table-condensed table-hover table-striped">
		<thead>  
				<tr>
				    <th>
					Partner
				    </th>

                    <th >
						Advertiser - Campaign
					</th>

					<th>
						RTG?
					</th>
<?php if($partner_id == 1 && $role!='sales'){echo 
                    '<th>
				Shotz
					</th>'
;} ?>
					<th>
						Target <span class="tool_poppers"  data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Target Impressions ['000']" data-content="Target per cycle."> <i class="icon-info-sign "></i></span>
					</th>
                    <th>
						Campaign Type 
					</th>
<?php if($partner_id == 1 && $role!='sales'){echo 
                    '<th>
						Rem.
					</th>'
;} ?>					
					
					<th>
						Cycles Live <span class="tool_poppers"  data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Cycles Live" data-content="The number of cycles that the campaign has impressions."> <i class="icon-info-sign "></i></span>
					</th>
					<th>
						Start Date <span class="tool_poppers"  data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Start Date" data-content="The first day that impressions were run for this campaign."> <i class="icon-info-sign "></i></span>
					</th>
				
					<th>
						Cycle End Date <span class="tool_poppers"  data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Cycle End Date" data-content="This is the date that the current cycle will be done."> <i class="icon-info-sign "></i></span>
					</th>
<?php if($partner_id == 1 && $role!='sales'){echo 
                    '<th>
						Site Weight
					</th>'
;} ?>
 
                    <th>
                    	Total Imprs
			<br>
			OTI <span class="tool_poppers"  data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Lifetime On Target Impressions Ratio" data-content="If = 100%, the campaign is right on target for it's lifetime. If under/over 100% the campaign is under/over it's lifetime target impressions."> <i class="icon-info-sign "></i></span>
					</th>

                                       
<?php if($partner_id == 1 && $role!='sales'){echo 
                    '<th>
						Cycle Imprs
						<br>
						OTI  <span class="tool_poppers"  data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Cycle On Target Impressions Ratio" data-content="If = 100%, the campaign is right on target for this cycle. If under/over 100% the campaign is under/over it\'s cycle target impressions."> <i class="icon-info-sign "></i></span>
					</th>'
;} ?>

                                     
                                       
<?php if($partner_id == 1 && $role!='sales'){echo 
                    '<th>
						Cycle Target
					</th>
                    <th>
						Y\'day Realized
					</th>
					<th>
					LT Target
					</th>
					<th>
				CTR
				</th>
					<th>
					    Engagement Rate
					</th>

		    '
;} ?>
				
                    <th>
                    	Action
					</th>
					
					
  <?php if($partner_id == 1 && $role!='sales'){echo                                    
		    '<th>
				Marker	    
		    </th>'
  ;} ?>
				</tr>
		</thead>
                <tbody class="the_body" id="table_body">
                    <img id="loader_image" class="the_image"   src="/images/campaign_health/preloader.gif" >
                  <?php //echo $table_body_string;?>
				</tbody>
	</table>

	<div id="bulk_download_modal" class="modal hide fade" aria-hidden="true">
	  <form id="bulk_download_form" action="/campaigns_main/get_bulk_download/" method="POST" target="_blank">
		<div class="modal-header">
		  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		  <h3 id="bulk_download_modal_header">Bulk Campaign Data Download</h3>
		</div>
		<div id="bulk_download_modal_body" class="modal-body">
		  <div class="bd_inline_block">
			<div class="bd_form_element">
			  <div><h4>Start Date</h4></div> 
			  <input type="text" name="start_date" id="bulk_download_start_date" class="bulk_download_datepicker" data-date-format="mm/dd/yyyy">
			</div>
			<div class="bd_form_element" >
			  <div><h4>End Date</h4></div> 
			  <input type="text" name="end_date" id="bulk_download_end_date" class="bulk_download_datepicker" data-date-format="mm/dd/yyyy">

			</div>
		  </div>

		  <div>
			<hr style="margin:0px 0px 0px 0px;" />
			<div class="bd_inline_block">
			  <div class="bd_form_element">
				<div>
				  <h4>Partner</h4>
				</div>
				<select name="bd_partner_select"  id="bd_partner_select">
				  <?php echo $bd_partner_options; ?>
				</select>
			  </div>
			  <div class="bd_form_element">
				<div>
				  <h4>Account Executive</h4> 
				</div>
				<select name="bd_sales_person_select" id="bd_sales_person_select">
				</select>
			  </div>
			</div>
			<div id="bd_ae_tooltip_div">
			  <a href="#" id="bd_ae_tooltip" onclick="event.preventDefault();"> Why can't I find an AE? <i class="icon-question-sign"></i></a>
			</div>
		  </div>
		</div>
		<div id="bulk_download_modal_footer" class="modal-footer">
		  <button type="button" data-dismiss="modal" class="btn">Close</button>
		  <button disabled="disabled" id="bulk_download_init_button" type="submit" class="btn btn-inverse"><i class="icon-download icon-white"></i> Download</button>
		</div>
	  </form>
	</div>

	<div id="myModal" class="modal hide fade big_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	  <div class="modal-header">
	    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
	    <h3 id="modal_detail_header">Modal header</h3>
	  </div>
	  <div id="modal_detail_body" class="modal-body">
	    <p></p>
	  </div>
	</div>
	
	<div id="adset_modal" class="modal hide fade big_modal" tabindex="-1" role="dialog" aria-labelledby="adset_modalLabel" aria-hidden="true">
	  <div class="modal-header">
	    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
	    <h3 id="adset_modal_detail_header">Modal header</h3>
	  </div>
	  <div id="adset_modal_detail_body" class="modal-body">
	    <p></p>
	  </div>
	    <div class="modal-footer">
		<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a>
	    </div>
	</div> 
	
	
    	<div id="adgroup_modal" class="modal hide fade big_modal" tabindex="-1" role="dialog" aria-labelledby="adgroup_modalLabel" aria-hidden="true">
	  <div class="modal-header">
	    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
	    <h3 id="adgroup_modal_detail_header">Modal header</h3>
	  </div>
	  <div id="adgroup_modal_detail_body" class="modal-body" style="max-height:450px;">
	      <h4>Campaign Details</h4> <span class="label label-success" style="position:relative;top:-10px;" id="c_timestamp_box">ASDASDASD</span>
	      <div class="divider"></div>
	      <form class="form-horizontal">
		  <div class="control-group">
		      <label class="control-label" for="impression_budget_box">Impression Budget</label>
		      <div class="controls">
			  <input type="text" id="impression_budget_box">
		      </div>
		  </div>
		  <div class="control-group">
		      <label class="control-label" for="dollar_budget_box">$ Budget</label>
		      <div class="controls">
			  <input type="text" id="dollar_budget_box">
		      </div>
		  </div>
		  <div class="control-group">
		      <label class="control-label" for="start_date_box">Start Date</label>
		      <div class="controls">
			  <input type="date" id="start_date_box">
		      </div>
		  </div>
		  <div class="control-group">
		      <label class="control-label" for="end_date_box">End Date <span class="tool_poppers"  data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="End Date Adjustment" data-content="This is going to adjust both the TTD and the VL End Date."> <i class="icon-info-sign "></i></span></label>
		      <div class="controls">
			  <input type="date" id="end_date_box">
		      </div>
		  </div>
	      </form>
		  <hr/>
		  <div id="adgroups_options">
	    <h4 id='m_header'>Managed Adgroup</h4> <span class="label label-success" id="m_timestamp_box">ASDASDASD</span>
	      <form class="form-horizontal">
		  <div class="control-group">
		      <label class="control-label" for="m_is_enabled_checkbox">Enabled?</label>
		      <div class="controls">
			  <input type="checkbox" id="m_is_enabled_checkbox"/>
		      </div>
		  </div>
		  <div class="control-group">
		      <label class="control-label" for="m_impression_budget_box">Impression Budget</label>
		      <div class="controls">
			  <input type="text" id="m_impression_budget_box">
		      </div>
		  </div>
		  <div class="control-group">
		      <label class="control-label" for="m_dollar_budget_box">$ Budget</label>
		      <div class="controls">
			  <input type="text" id="m_dollar_budget_box">
		      </div>
		  </div>
		  <div class="control-group">
		      <label class="control-label" for="m_daily_impression_box">Daily Impression Budget</label>
		      <div class="controls">
			  <input type="text" id="m_daily_impression_box">
		      </div>
		  </div>
		  <div class="control-group">
		      <label class="control-label" for="m_daily_dollar_box">Daily $ Budget</label>
		      <div class="controls">
			  <input type="text" id="m_daily_dollar_box">
		      </div>
		  </div>
	      </form>
	    <h4 id='r_header'>Retargeting Adgroup</h4> <span class="label label-success" id="r_timestamp_box">ASDASDASD</span>
	      <div class="divider"></div>
	      <form class="form-horizontal">
		  <div class="control-group">
		      <label class="control-label" for="r_is_enabled_checkbox">Enabled?</label>
		      <div class="controls">
			  <input type="checkbox" id="r_is_enabled_checkbox"/>
		      </div>
		  </div>
		  <div class="control-group">
		      <label class="control-label" for="r_impression_budget_box">Impression Budget</label>
		      <div class="controls">
			  <input type="text" id="r_impression_budget_box">
		      </div>
		  </div>
		  <div class="control-group">
		      <label class="control-label" for="r_dollar_budget_box">$ Budget</label>
		      <div class="controls">
			  <input type="text" id="r_dollar_budget_box">
		      </div>
		  </div>
		  <div class="control-group">
		      <label class="control-label" for="r_daily_impression_box">Daily Impression Budget</label>
		      <div class="controls">
			  <input type="text" id="r_daily_impression_box">
		      </div>
		  </div>
		  <div class="control-group">
		      <label class="control-label" for="r_daily_dollar_box">Daily $ Budget</label>
		      <div class="controls">
			  <input type="text" id="r_daily_dollar_box">
		      </div>
		  </div>
		  </div>
	      </form>
	  </div>
	    <div class="modal-footer" id="ttd_modal_footer">
		<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a>
		<button id="modify_button" type="submit" class="btn btn-info "><i class="icon-retweet icon-white"></i> Update</button>
	    </div>
	</div>
    
    	<div id="confirm_modal" class="modal hide fade big_modal" tabindex="-1" role="dialog" aria-labelledby="confirm_modalLabel" aria-hidden="true">
	  <div class="modal-header">
	    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
	    <h3 id="confirm_modal_detail_header">Confirm Action</h3>
	  </div>
	  <div id="confirm_modal_detail_body" class="modal-body">
	    
	  </div>
	    <div class="modal-footer">
		<a class="btn btn-danger" data-dismiss="modal" aria-hidden="true">No</a>
		<a class="btn btn-success" id="confirm_delete_button" onclick="" data-dismiss="modal" aria-hidden="true">Yes</a>
	    </div>
	</div> 
    

    
	<div id="test_div"></div>
	
	<div id="feature_test_div">
	</div>
	<div id="subfeature_test_div">
	</div>
</div>
