<!DOCTYPE html>
<html>
  <head>
	<title>Media Targeting Tags Editor</title>
	<link rel="shortcut icon" type="image/png" href="http://adage.vantagelocal.com/wp-content/uploads/2013/04/favicon-1.ico">
	<link rel="stylesheet" href="/bootstrap/assets/css/bootstrap.css">
	<link rel="stylesheet" href="/bootstrap/assets/css/bootstrap-responsive.css">
	<link rel="stylesheet" href="/libraries/external/font-awesome/css/font-awesome.css">
	<link href="/libraries/external/select2/select2.css" rel="stylesheet"/>
	<style type="text/css">
	  .site-edit-header-row { position: relative; }
	  .bulk-buttons-div {position: absolute; bottom: 0; right: 0;}
	  #sitepack_select_modal_body {height:70px;}
	  .alert.alert-error{display:none;}
	  .site_button {float:left; width:80px; margin-left:10px; padding:4px;}
	</style>

  </head>
  <body>
	<div class="container">
	  <div class="alert alert-error">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		<div id="tags_alert_div">&nbsp;</div>
	  </div>
	  <h1><i class="icon-tags"></i>Tags Editor</h1>
	  <div class="row">
		<div class="span12">
		  <div>Which sites would you like to edit tags for? 
			&nbsp;&nbsp;&nbsp; <a href="#add_raw_sites_modal" class="" data-toggle="modal"><small><i class="icon-upload"></i> sites</small></a>    
			&nbsp;&nbsp;&nbsp; <a href="#sitepack_select_modal" class="" id="bulk-sites-button" data-toggle="modal"> <small><i class="icon-gift"></i> sitepacks</small></a>
			&nbsp;&nbsp;&nbsp; <a href="#search_by_tag_modal" class="" id="search_by_tags_modal_open_button" data-toggle="modal"> <small><i class="icon-search"></i> tags</small></a>
		  </div>
		</div>
	  </div>
	  <div class="row">
		<div class="span12">
		  <input type="hidden" id="site-tag-multi-id" class="span6 site-tag-multi-class">
		  <button class="btn site_button" id="sites-selected-button" data-loading-text="go">go!</button>
		  <a href="#bulk_add_tags_modal" data-toggle="modal" class="btn site_button" id="bulk_add_tags_modal_open_button" data-loading-text="bulk">bulk</a>
		</div>
	  </div>
	  <hr>
	  
	  <div class="row">
		<div class="span12">
		  <div id="sites-controls"></div>
		  <div>
		  </div>


		  <!-- MODALS-->
		  <div id="add_raw_sites_modal" class="modal hide fade"  role="dialog" aria-labelledby="ad_raw_sites_modal_label" aria-hidden="true">
			<div class="modal-header">
			  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
			  <h3 id="ad_raw_sites_modal_label">Add sites <small>tab delimted with demographics please</small></h3>
			</div>
			<div class="modal-body" id="add_raw_sites_modal_body">
			  <textarea id="raw_sites_text" rows="15" class="span5"></textarea>
			</div>
			<div class="modal-footer">
			  <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
			  <button id="add_raw_sites_button" class="btn btn-primary">Add</button>
			</div>
		  </div>

		  <div id="sitepack_select_modal" class="modal hide fade"  role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-header">
			  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
			  <h3 id="myModalLabel">Sitepack Select</h3>
			</div>
			<div class="modal-body" id="sitepack_select_modal_body">
			</div>
			<div class="modal-footer">
			  <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
			  <button id="load_sitepack_button" class="btn btn-primary">Load</button>
			</div>
		  </div>

		  <div id="search_by_tag_modal" class="modal hide fade"  role="dialog" aria-labelledby="search_by_tags_modal_label" aria-hidden="true">
			<div class="modal-header">
			  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
			  <h3 id="search_by_tags_modal_label">Search for sites by tags</h3>
			</div>
			<div class="modal-body" id="search_by_tags_modal_body">
			</div>
			<div class="modal-footer">
			  <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
			  <button id="search_by_tags_go_button" class="btn btn-primary">Save</button>
			</div>
		  </div>

		  <div id="bulk_add_tags_modal" class="modal hide fade"  role="dialog" aria-labelledby="bulk_add_tags_modal_label" aria-hidden="true">
			<div class="modal-header">
			  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
			  <h3 id="bulk_add_tags_modal_label">Add tags in bulk</h3>
			</div>
			<div class="modal-body" id="bulk_add_tags_modal_body">
			</div>
			<div class="modal-footer">
			  <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
			  <button id="bulk_add_tags_save_button" class="btn btn-primary">Save</button>
			</div>
		  </div>
		</div>
	  </div>
	</div>

	<script src="/libraries/external/jquery-1.10.2/jquery-1.10.2.min.js"></script>
	<script src="/bootstrap/assets/js/bootstrap.min.js"></script>
	<script src="/libraries/external/select2/select2.js"></script>
	<?php 
		$this->load->view('vl_platform_2/ui_core/js_error_handling');
		write_vl_platform_error_handlers_js();
		$this->load->view('media_targeting_tags/media_targeting_tags_js');
		write_vl_platform_error_handlers_html_section(); 
	?>
  </body>
</html>

