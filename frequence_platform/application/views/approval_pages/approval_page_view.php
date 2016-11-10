<div class="container" style="margin-top:30px;">
	<div class="tabbable">
<?php		if ($show_mobile)
		{
?>			<ul class="nav nav-tabs" id="app_tab_ul">
				<li class="tab_colored active"><a class="preview_pane_tab" href="#standard_ads" data-toggle="tab" id="standard_ad_units_tab">Standard Ad Units</a></li>
				<li class="tab_colored"><a class="preview_pane_tab" href="#mobile_ads" data-toggle="tab" id="mobile_ad_units_tab">Mobile Ad Units</a></li>
				<li class="pull-right">&nbsp;&nbsp;<button type="button" class="btn btn-success btn-small" id="approval_page_refresh"><i class="icon-refresh icon-white"></i></button></li>
<?php				if (isset($show_for_io_checkbox))
				{				
?>					<li id="show_for_io_li" class="pull-right" style="margin-right: 50px;margin-top: -15px;">
						<div class="checkbox show-for-io">
							<div style="font-size: 12px;">
								<input type="checkbox" name="show_for_io"> Internally Approved?<span id="show_for_io_updated_user_name_date" style="margin-left:5px;"></span>
								<br/>(Makes adset available for IOs)
								<br/>Adset version: <a target="_blank" href="//<?php echo $modified_base_url; ?>creative_uploader/<?php echo $version_id; ?>"><?php echo $modified_base_url."creative_uploader/".$version_id; ?></a>
							</div>
						</div>
					</li>
<?php				}
				elseif(isset($show_adset_versions))
				{
?>					<li id="show_for_io_li" class="pull-right" style="margin-right: 50px;margin-top: -15px;">
						<div class="checkbox show-for-io">
							<div style="font-size: 12px;">
								<br/>Adset version: <a target="_blank" href="//<?php echo $modified_base_url; ?>creative_uploader/<?php echo $version_id; ?>"><?php echo $modified_base_url."creative_uploader/".$version_id; ?></a>
							</div>
						</div>
					</li>
<?php				}
?>			</ul>
<?php		}
		elseif(isset($show_for_io_checkbox))
		{
?>			<ul class="nav nav-tabs" id="app_tab_ul">
				<li id="show_for_io_li" class="pull-right" style="margin-right: 50px;margin-top: -15px;">
					<div class="checkbox show-for-io">
						<div style="font-size: 12px;"><input type="checkbox" name="show_for_io"> Internally Approved?<span id="show_for_io_updated_user_name_date"  style="margin-left:5px;"></span>
						<br/>(Makes adset available for IOs)
						<br/>Adset version: <a target="_blank" href="//<?php echo $modified_base_url; ?>creative_uploader/<?php echo $version_id; ?>"><?php echo $modified_base_url."creative_uploader/".$version_id; ?></a>
						</div>
					</div>
				</li>
			</ul>
<?php		}
		elseif(isset($show_adset_versions))
		{
?>			<ul class="nav nav-tabs" id="app_tab_ul">
				<li id="show_for_io_li" class="pull-right" style="margin-right: 50px;margin-top: -15px;">
					<div class="checkbox show-for-io">
						<div style="font-size: 12px;">
						<br/>Adset version: <a target="_blank" href="//<?php echo $modified_base_url; ?>creative_uploader/<?php echo $version_id; ?>"><?php echo $modified_base_url."creative_uploader/".$version_id; ?></a>
						</div>
					</div>
				</li>			
			</ul>			
<?php		}
?>		<div class="tab-content" style="position:relative;overflow:hidden;z-index:1;">
			<div class="tab-pane active" id="standard_ads">
				<iframe class="wide_skyscraper approval_iframe standard_ad_iframe" src="/crtv/get_ad/<?php echo $version_id; ?>/160x600" style="overflow:hidden;  width: 160px; height: 600px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>
				<iframe class="leaderboard approval_iframe standard_ad_iframe" src="/crtv/get_ad/<?php echo $version_id; ?>/728x90" style="overflow:hidden;  width: 728px; height: 90px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>
				<iframe class="large_rectangle approval_iframe standard_ad_iframe" src="/crtv/get_ad/<?php echo $version_id; ?>/336x280" style="overflow:hidden;  width: 336px; height: 280px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>
				<iframe class="medium_rectangle approval_iframe standard_ad_iframe" src="/crtv/get_ad/<?php echo $version_id; ?>/300x250" style="overflow:hidden;  width: 300px; height: 250px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>
				<iframe class="small_leaderboard approval_iframe standard_ad_iframe" src="/crtv/get_ad/<?php echo $version_id; ?>/468x60" style="overflow:hidden;  width: 468px; height: 60px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>
				<div class="landing_page">
<?php					if ($is_show_landing_page && isset($landing_page))
					{
						echo "<h4>Landing Page: <a target=\"_blank\" href=".$landing_page.">".$landing_page."</a> </h4>";
					}
?>				</div>
			</div>
<?php			if ($show_mobile)
			{
?>				<div class="tab-pane" id="mobile_ads">
					<img id="phone_img" src="https://s3.amazonaws.com/brandcdn-assets/images/frq_iphone6.png" alt="" />
					<iframe class="mobile approval_iframe mobile_ad_iframe" src="/crtv/get_ad/<?php echo $version_id; ?>/320x50" style="overflow:hidden;" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0"><p>Your browser does not support iframes.</p></iframe>
				</div>
<?php			}
?>		</div>
	</div>
</div>
<?php
	if (isset($tags) && $tags)
	{
		foreach ($tags as $tag_to_display)
		{
			echo $tag_to_display." "; 
		}
	}
?>
