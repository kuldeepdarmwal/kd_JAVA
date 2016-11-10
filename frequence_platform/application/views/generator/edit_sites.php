<!DOCTYPE html>
<html>
  <head>
    <title>Vantage Local | Edit Site List</title>
    
<!--<link rel="stylesheet" href="/bootstrap/assets/css/bootstrap-responsive.css">-->
	<link href="/libraries/external/select2/select2.css" rel="stylesheet">
    <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/smoothness/jquery-ui.css" type="text/css" media="screen" />  
    <link rel="stylesheet" href="/js/multi_select/css/ui.multiselect.css" type="text/css" media="screen" />
    <link rel="stylesheet" href="/bootstrap/assets/css/bootstrap.css">
	<link rel="stylesheet" type="text/css" href="/css/edit_sitelist.css">
	
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script src="/bootstrap/assets/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="http://code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
	<script type="text/javascript" src="/js/multi_select/js/ui.multiselect.js"></script>
    <script src="/libraries/external/select2/select2.js"></script>
	<script type="text/javascript" src="/js/edit_sites.js"></script>

    <!-- Included a bunch of js for the multiselect jquery plugin.  This is so in the final list you can move elements around -->

<script type="text/javascript">
    $(document).ready(function(){
    	init_industry_data();
		$('.toggle_head').dblclick(function(){
			var index = $(this).index('th');
			$(this).toggle("fast");
			$('.togl').each(function(){
				$(this).children().eq(index).toggle("fast");
			});
		});
	});
	$('#site_source_tabs > li > a').click(function(event){
		event.preventDefault();
	});
	function initialize_media_targeting_tags_selection()
	{
		$("#media_targeting_tags_select2").select2('data', <?php echo ($media_targeting_tags == "") ? "null" : $media_targeting_tags; ?>);
		$("#media_targeting_tags_select2").select2("container").find("ul.select2-choices").sortable({
			containment: 'parent',
			start: function() { $("#media_targeting_tags_select2").select2("onSortStart"); },
			update: function() { $("#media_targeting_tags_select2").select2("onSortEnd"); }
		});
		
	}

	function save_industry_regenerate_site_list()
	{
		var industry_id=$('#industry_select').select2('val');
		if (industry_id == '')
		{
			$( "#industry_select_div_message" ).text("Please select a Product");
			return;
		}
		$.ajax({
			type: "POST",
			url: '/proposal_builder/regenerate_site_list/',
			async: true,
			data: { prop_id: <?php echo $prop_id; ?>,
					industry_id: industry_id  },
			dataType: 'json',
			error: function(xhr, textStatus, error)
			{
				show_tags_error('Error 4929356: failed getting sitepack select');
				hideoverlay();
			},
			success: function(msg)
			{
				$( "#industry_select_div_message" ).text("Saved");
				location.reload();
			}
		});
	}

	function init_industry_data()
	{
		$('#industry_select').select2({ width: '100%' ,
			placeholder: "Advertiser Product",
			minimumInputLength: 0,
			multiple: false,
			ajax: {
				url: "/mpq_v2/get_industries/",
				type: 'POST',
				dataType: 'json',
				data: function (term, page) {
					term = (typeof term === "undefined" || term == "") ? "%" : term;
					return {
						q: term,
						page_limit: 20,
						page: page
					};
				},
				results: function (data) {
					return {results: data.results, more: data.more};
				}
			}
		});
		<?php if ($industry_data_id != null) { ?>
		var industry_data_array=new Array();
		industry_data_array["id"] = <?php echo $industry_data_id; ?>;
		industry_data_array["text"] = '<?php echo $industry_data_text; ?>';
		$("#industry_select").select2("data", industry_data_array);
		<?php } ?>
	}
</script>
  </head>
  <body>
    <div id="left_area">
      
	  <ul style="margin:10px 0; width:100%; margin-left:auto; margin-right:auto;" class="nav nav-pills" id="site_source_tabs">
		<li class="active"><a href="#tags_pane" data-toggle="tab">Tags</a></li>
		<li><a href="#pack_pane" data-toggle="tab">Site Packs</a></li>
		<li><a href="#custom_pane" data-toggle="tab">Custom</a></li>
		<!--<li><a href="#channel_pane" data-toggle="tab">Channel</a></li>-->
		<li><a href="#targeted_pane" data-toggle="tab">Targeted</a></li>
	  </ul>
	  
	  <div class="tab-content">
		<div class="tab-pane active" id="tags_pane">
		  <div id="tags_pane_top">
			<div id="tags_box_group">
			  <h2>
			    Tags&nbsp;
				<div id="tags_bottom_pane_arrows">
				  <button onclick="move_pack_sites('tags_sites', 0)" class="arrow btn btn-primary"><i class="icon-chevron-right icon-white"></i></button>&nbsp;
				  <button onclick="move_pack_sites('tags_sites', 1)" class="arrow btn btn-primary"><i class="icon-chevron-right icon-white"></i><i class="icon-chevron-right icon-white"></i></button>
				</div>
			  </h2>
			  <select multiple="multiple" id="tags_sites"></select>
			</div>
		  </div>
		  <div id="tags_pane_bottom">
			<button onclick="populate_site_multiselect_from_tags()" id="tags_sites_button" class="btn btn-success"><i class="icon-search icon-white"></i> Sites</button>
			<!--<button id="iab_select2_expand" class="btn btn-info"><i class="icon-plus icon-white"></i></button>-->
			<div id="iab_select2_container">
			  <input type="hidden" id="media_targeting_tags_select2"> 
			</div>
			<div id="tags_below_select2_buttons">
			  <a href="/media_targeting_tags/edit_tags" target="_blank">Edit Tags</a> <button onclick="initialize_media_targeting_tags_selection();" id="tags_original_tags_button" class="btn btn-danger"><i class="icon-refresh icon-white"></i> Original Tags</button>
			</div>
			<!--  Industry Tag  -->
			<br>
			<div id="industry_select_div">
				<input type="hidden" style=" width:90%;" id="industry_select"><br><br>
				<button onclick="save_industry_regenerate_site_list();" id="regenerate_site_list_button" class="btn btn-danger"><i class="icon-refresh icon-white"></i> Save Industry & Regenerate Site List</button>
			</div>
			<div id="industry_select_div_message" style="background-color: #F5D95F">
			</div>
		  </div>
		</div>
		
		<div class="tab-pane" id="targeted_pane">
		  <h2>
			Targeted Sites
			<div style="position:relative; float:right;">
			  <button class="arrow btn btn-primary" onclick="move_sites('site_list', 0);"><i class="icon-chevron-right icon-white"></i></button>&nbsp;
			  <button class="arrow btn btn-primary" onclick="move_sites('site_list', 1);"><i class="icon-chevron-right icon-white"></i><i class="icon-chevron-right icon-white"></i></button>
			</div>
		  </h2>
		  <select multiple="multiple" id="site_list"><?php echo $site_list; ?></select>
		</div>
		
		<div class="tab-pane" id="channel_pane">
		  <h2>
			Channel Sites
			<button class="arrow btn btn-primary" onclick="move_sites('channel_site_list', 0);"><i class="icon-chevron-right icon-white"></i></button>&nbsp;
			<button class="arrow btn btn-primary" onclick="move_sites('channel_site_list', 1);"><i class="icon-chevron-right icon-white"></i><i class="icon-chevron-right icon-white"></i></button>
		  </h2>
		  <select multiple="multiple" id="channel_site_list"><?php echo $channel_site_list; ?></select>
		</div>
		
		<div class="tab-pane" id="custom_pane">
		  <h2>
			Custom Sites
			<button style="top:4px;position:relative;float:right;" class="arrow btn btn-primary" onclick="move_sites('custom_site_list', 2);"><i class="icon-chevron-right icon-white"></i><i class="icon-chevron-right icon-white"></i></button>
		  </h2>
		  <textarea wrap="off" placeholder="Paste Demographic Scraper text here." id="custom_site_list"></textarea><br>
		  <a href="<?php echo base_url('q_scrape');?>" target="_blank">Demographic Scraper</a>
		</div>
		
		<div class="tab-pane" id="pack_pane">
		  <h2>
			Site Packs
			<div id="site_pack_arrows">
			  <select onchange="build_pack_list(this.value)" id="site_pack_drop" name="pack_select">
				<option value="none">Select Pack</option>
				<?php echo $pack_options; ?>
			  </select>
			  <div id="site_pack_arrow_buttons">
				<button class="arrow btn btn-primary" onclick="move_pack_sites('site_pack', 0);"><i class="icon-chevron-right icon-white"></i></button>&nbsp;
				<button class="arrow btn btn-primary" onclick="move_pack_sites('site_pack', 1);"><i class="icon-chevron-right icon-white"></i><i class="icon-chevron-right icon-white"></i></button>
			  </div>
			</div>
		  </h2>
		  <select multiple="multiple" id="site_pack"></select>
		</div>
	  </div>
    </div>
	
    <div id="right_area">
      <!--<form enctype="multipart/form-data" id="myform" method="post" action="">-->
	  <div id="sitelist_msg_box" class="alert">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		<div id="sitelist_msg_box_content">
		</div>
	  </div>
      <h2 id="top_menu">
		<div id="save_section">
		  Final List&nbsp;<button id="save_final_list" class="btn btn-success" onclick="save_sites(<?php echo $prop_id; ?>);">Save</button>
		</div>
		<div id="pack_control">
		  <button id="pack_button_1" class="pack_save btn btn-primary" onclick="get_pack_buttons();">Save as Site Pack</button>
		  <button id="pack_button_replace" class="pack_save btn" onclick="pack_button_replace();">Replace Existing</button>
		  <button id="pack_button_new" class="pack_save btn" onclick="pack_button_new();">Create New</button>
		  <button id="pack_button_back" class="pack_save btn" onclick="pack_button_back();"><i class="icon-remove"></i></button>
		  <select id="site_pack_drop_replace" name="pack_select"><?php echo $pack_options; ?></select>
		  <input id="site_pack_text_new" type="text" placeholder="Pack Name..." />
		  <button id="pack_button_replace_save" class="pack_save btn btn-success" onclick="save_pack_list('replace');">Replace</button>
		  <button id="pack_button_new_save" class="pack_save btn btn-success" onclick="save_pack_list('new');">&nbsp;Create&nbsp;</button>
		  
		</div>
		<div id="insert_control">
		  <button id="page_break" class="btn" onclick="insert_page_break();">Insert Page Break</button>
		  <input placeholder="Header Name..." id="header_text" type="text" />
		  <button id="insert_header" class="btn" onclick="insert_header();">Insert Header</button>
		</div>
		
		<div id="header_duplicates_section">
		  Duplicates Not Allowed <input checked="checked" id="header_checkbox" type="checkbox" />
		</div>
      </h2>
      <div>
	

	<table style="width:98%;margin-top:102px;" border-collapse=:collapse; id="uber">
	  <thead>
            <tr id="headers" class="ui-widget-header even">
              <th style="width:38px;">&nbsp;</th>
              <th style="width:200px">Site</th>
	      <th class="toggle_head" style="width:40px;">Reach</th>
	      <th class="toggle_head" style="width:40px;">Male</th>
	      <th class="toggle_head" style="width:40px;">Female</th>
	      <th class="toggle_head" style="width:40px;">< 18</th>
	      <th class="toggle_head" style="width:40px;">18-24</th>
	      <th class="toggle_head" style="width:40px;">25-34</th>
	      <th class="toggle_head" style="width:40px;">35-44</th>
	      <th class="toggle_head" style="width:40px;">45-54</th>
	      <th class="toggle_head" style="width:40px;">55-64</th>
	      <th class="toggle_head" style="width:40px;">65+</th>
	      <th class="toggle_head" style="width:40px;">cauc</th>
	      <th class="toggle_head" style="width:40px;">afr am</th>
	      <th class="toggle_head" style="width:40px;">asian</th>
	      <th class="toggle_head" style="width:40px;">hisp</th>
	      <th class="toggle_head" style="width:40px;">other</th>
	      <th class="toggle_head" style="width:40px;">kids</th>
	      <th class="toggle_head" style="width:40px;">no kids</th>
	      <th class="toggle_head" style="width:40px;">$0-50</th>
	      <th class="toggle_head" style="width:40px;">$50-100</th>
	      <th class="toggle_head" style="width:40px;">$100-150</th>
	      <th class="toggle_head" style="width:40px;">$150+</th>
	      <th class="toggle_head" style="width:40px;">none</th>
	      <th class="toggle_head" style="width:40px;">under</th>
	      <th class="toggle_head" style="width:40px;">graduate</th>
            </tr>              
	  </thead>
	  <tbody class="ui-widget-content ui-selectable ui-sortable">
	    <?php echo $existing_string; ?>
	  </tbody>
	</table>
      </div>
    <button id="remove_sites" class="btn btn-danger" onclick="delete_selected();">Remove Selected Sites</button>

    </div>
  </body>
</html>
