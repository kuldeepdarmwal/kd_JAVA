<br />
<ul class="nav nav-tabs">
  <li id="dfa_tab" class="active"><a href="#dfa" onclick="switch_upload_tabs('dfa');" data-toggle="tab">DFA</a></li>
  <li id="adtech_tab"><a href="#adtech" onclick="switch_upload_tabs('adtech');" data-toggle="tab">AdTech</a></li>
  <li id="fas_tab"><a href="#fas" onclick="switch_upload_tabs('fas');" data-toggle="tab">Frequence Ad Server</a></li>
</ul>
<div id="upload_dfa" class="span12 form-horizontal upload_tab_content">
  <h4>DFA Ad Publisher <small> load adset to DFA and get ad tags</small></h4>
  <div class="control-group">
    <label class="control-label" for="adv_id">DFA Advertiser</label>
    <div class="controls" id="">
      <div class="form-inline">
        <select id="advertiser_dropdown" onChange="advertiser_select_script();">
          <option value="nothing">--</option>
        </select>
        <span>
          <span id="new_advertiser_input"> </span> <span id="load_advertiser_status"> </span>
        </span>
      </div>
    </div>
  </div>
  <div class="control-group">
    <label class="control-label" for="adv_id">DFA Campaign</label>
    <div class="controls" id="">
      <div class="form-inline">
        <select id="campaign_dropdown" onChange="campaign_select_script();">
          <option value="nothing">--</option>
        </select>
        <span>
          <span id="new_campaign_input"> </span> <span id="load_campaign_status"> </span>
        </span>
      </div>
    </div>
  </div>
  <div class="control-group">
    <div class="controls">
      <div class="form-inline">
		<button type="button" class="btn btn-primary" onclick="load_adset_to_dfa();"><i class="icon-book icon-white"></i> Publish</button>
		<?php
		   if(!$has_campaign)
		   {
			   echo '<span id="no_campaign_warning"  class="label label-important">This adset is not linked to a campaign! Consider fixing this.</span>';
		   }
		   ?>
      </div>
    </div>
    <div class="control-group">
      <div class="controls">
        <span id="insert_creative_status"></span>
      </div>
    </div>
  </div>
</div>



<div id="upload_adtech" class="span12 form-horizontal upload_tab_content">
  <h4>AdTech Ad Publisher <small> load adset to ADTECH and get ad tags</small></h4>
  <div class="control-group">
    <label class="control-label" for="adv_id">ADTECH Advertiser</label>
    <div class="controls" id="">
      <div class="form-inline">
        <select id="adtech_advertiser_dropdown" onChange="adtech_advertiser_select_script();">
          <option value="nothing">--</option>
        </select>
        <span>
          <span id="new_adtech_advertiser_input"> </span> <span id="load_adtech_advertiser_status"> </span>
        </span>
      </div>
    </div>
  </div>
  <div class="control-group">
    <label class="control-label" for="adv_id">ADTECH Campaign</label>
    <div class="controls" id="">
      <div class="form-inline">
        <select id="adtech_campaign_dropdown" onChange="adtech_campaign_select_script(this.value);">
          <option value="nothing">--</option>
        </select>
        <span>
          <span id="new_adtech_campaign_input"> </span> <span id="load_adtech_campaign_status"> </span>
        </span>
      </div>
    </div>
  </div>
  <div class="control-group">
    <div class="controls">
      <div class="form-inline">
		<button type="button" class="btn btn-primary" onclick="load_adset_to_adtech();"><i class="icon-book icon-white"></i> Publish</button>
		<?php
		   if(!$has_campaign)
		   {
			   echo '<span id="no_campaign_warning"  class="label label-important">This version is not linked to a campaign! Consider fixing this.</span>';
		   }
		   ?>
      </div>
    </div>
    <div class="control-group">
      <div class="controls">
        <span id="insert_adtech_creative_status"></span>
      </div>
    </div>
  </div>
</div>

<div id="upload_fas" class="span12 form-horizontal upload_tab_content">
	<h4>FAS Ad Publisher <small> load adset to Frequence Ad Server and get ad tags</small></h4>
	<div class="control-group">
		<label class="control-label" for="adv_id">Landing Page</label>
		<div class="controls" id="">
			<div class="form-inline">        
				<input  id="new_landing_page" type="url" value="http://www." >
			</div>
		</div>
	</div>
	<div class="control-group">
		<div class="controls">
			<div class="form-inline">
				<button type="button" class="btn btn-primary" onclick="load_adset_to_fas();"><i class="icon-book icon-white"></i> Publish</button>
<?php				if (!$has_campaign)
				{
					echo '<span id="no_campaign_warning"  class="label label-important">This adset is not linked to a campaign! Consider fixing this.</span>';
				}
?>			</div>
		</div>
		<div class="control-group">
			<div class="controls">
				<span id="insert_fas_creative_status"></span>
			</div>
		</div>
	</div>
</div>