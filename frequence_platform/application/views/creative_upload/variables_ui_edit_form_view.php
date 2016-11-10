<div class="container">
  <?php if($all_files == 0)
	{ ?>
  <div class="alert alert-block fade in">
    <button type="button" class="close" data-dismiss="alert">x</button>
    <strong>Missing Files:</strong>
    <?php
	  foreach($missing_array as $k => $v)
	  {
	      $has_items = 0;
	      $echo_str = "";
	      if($k != 'static')
	      {
		  $echo_str .= "<br>".$k.": ";
		  foreach ($v as $l => $w)
		  {
		      if($w == 1)
		      {
			  $has_items = 1;
			  $echo_str .= $l."&nbsp;&nbsp;";
		      }
		  }
	      }
	      else
	      {
		  if($v['fullscreen'] == 1)
		  {
		      $has_items = 1;
		      $echo_str .= "<br>fullscreen";
		  }
	      }
	      if($has_items == 0)
	      {
		  $echo_str = "";
	      }
	      echo $echo_str;
	  }
	  ?>
  </div> 
<?php } ?>

<div class="top_nav" style="margin-bottom:20px;">
	<h4 style="color: green">variables<small> alpha</small></h4>
	<select id="builder_version_select"></select>
<?php
	if(isset($recommended_builder_version))
	{
		echo '<div class="alert alert-error">Based on your uploaded assets, it is recommended to use <strong>Builder Version <span class="recommended_builder_version">' . $recommended_builder_version . '</span></strong>.</div>';
	}
?>
	<div class="row">
		<div class="span12">
			<div class="btn-group">
				<a class="btn btn-mini dropdown-toggle" data-toggle="dropdown" href="#">
						Import
					<span class="caret"></span>
				</a>
				<ul class="dropdown-menu">
				<!-- dropdown menu links -->
				<li><a href="#variables_modal" role="button" data-toggle="modal" id="template_open_button"><i class="icon-folder-open" ></i> Variables From Template</a></li>
				<li><a href="#variables_modal" role="button" data-toggle="modal" id="adset_open_button"><i class="icon-picture"></i> Variables From Adset</a><li>
				<li><a  role="button" id="pull_cdn_assets" ><i class="icon-download"></i> Full Adset </a></li>
				</ul>
			</div>
			<div class="btn-group">
				<a class="btn btn-mini dropdown-toggle" data-toggle="dropdown" href="#">
						Save
					<span class="caret"></span>
				</a>
				<ul class="dropdown-menu">
					<!-- dropdown menu links -->
					<li><a href="#variables_modal" role="button" data-toggle="modal" id="adset_save_button"><i class="icon-picture"></i> Variables To Adset</a></li>
					<li><a href="#variables_modal" role="button" data-toggle="modal" id="template_save_button"><i class="icon-folder-open"></i> Variables To Template</a><li>
				</ul>
			</div>
		</div>
	</div>
	<div class="row" style="margin-top:10px;">
		<div class="span3">
			<div class="btn-group">
				<button class="btn  btn-danger" type="button" id="super_save_button"><i class="icon-hdd icon-white"></i> Super Save!</button>
			</div>
			<a id="super_save_tooltip" data-toggle="tooltip" title="SUPER SAVE: saves variables to the adset selected above and loads staged assets to the CDN - all at once!"><i class=" icon-info-sign"></i> </a>
			<div class="btn-group pull-right">
				<button class="btn btn-success" type="button" id="refresh_button">  <i class="icon-refresh icon-white"></i></button>
			</div>
			
		</div>
	</div>
</div>


 <div class="sideNav">
	<div>
		<form id="ui_control_form" >
		</form>
		<div >
			<div class="span12">

			</div>
		</div>
	</div>
</div>

		<div id="testArea"></div>
		<div class=" controlscontrols-row">
	<div class="tabbable pull-right" style="width:728px; height: 1600px;">
		<div>
		<div style="height:775px;" class="variables_tab" id="variables_ad_all"></div>
		<div style="height:775px;" class="variables_tab" id="variables_ad_all_h5"></div>
		</div>
	</div>
	</div>

</div>


