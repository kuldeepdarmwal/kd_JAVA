
<!-- campaigns_main/subfeature_html_header -->
		<link href='//fonts.googleapis.com/css?family=Oxygen' rel='stylesheet' type='text/css'>
        <link href='//fonts.googleapis.com/css?family=Homenaje' rel='stylesheet' type='text/css'>
        <link rel="shortcut icon" href="/images/campaign_health/hospital.png">
		<link rel="stylesheet" media="all" href="/css/campaign_health/campaign_health_index_style_mod.css" type="text/css">
        <link rel="stylesheet" href="/css/campaign_health/notification.css">
		<link rel="stylesheet" href="/css/campaign_health/datepicker.css">
		<link href="/libraries/external/select2-3.5.2/select2.css" rel="stylesheet">
		<link rel="stylesheet" type="text/css" href="/libraries/external/DataTables-1.10.2/media/css/jquery.dataTables.css">
		<link rel="stylesheet" type="text/css" href="/libraries/external/DataTables-1.10.2/extensions/FixedHeader/css/dataTables.fixedHeader.min.css">
	
<style type="text/css">
	.overlay 
	{
	  position: fixed;
	  top: 20px;
	  bottom: 0;
	  left: 0;
	  right: 0;
	  visibility: hidden;
	  opacity: 0;
	  z-index: 105;
	}
	.overlay:target 
	{
	  visibility: visible;
	  opacity: 1;
	}
	.popup_notes_class 
	{
	  z-index: 105;
	  margin: 70px auto;
	  padding: 6px;
	  background: #fff;
	  border-radius: 5px;
	  width: 95%;
	  position: relative;
	  transition: all 0.3s ease-in-out;	  
	  border:1px solid;
	  border-color:#848484;
	  line-height: 15px;
	}
	.popup_notes_class .close_note
	{
	  position: absolute;
	  top: 10px;
	  right: 15px;
	  transition: all 200ms;
	  font-size: 35px;
	  font-weight: bold;
	  text-decoration: none;
	  color: #333;
	}
	.popup_notes_class .close_note:hover 
	{
	  color: orange;
	}
	.popup_notes_class .content 
	{  
	  overflow: auto;
	  max-height: calc(100vh - 150px);
	}
	.notes_text
	{   
	    cursor: pointer;
	    transition: all 0.3s ease-out;
	    display: inline-block;
	    padding: 2px 4px;
	    font-size: 10px;
	    font-weight: bold;
	    line-height: 14px;
	    color: #ffffff;
	    text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
	    white-space: nowrap;
	    vertical-align: baseline;
	    background-color: #999999;
	}
</style>
<style type="text/css">
	.form-horizontal .control-group
	{
		margin-bottom: 8px; 
	}
	
	#campaigns_main_page_container
	{
		margin-left: 1%;
		margin-right: 1%;
	}

	.c_campaign_column
	{
		word-wrap: break-word;
	}

<?php if($role == 'admin' || $role == 'ops') { ?>

	td.c_campaign_column
	{
		width:275px;
		max-width:275px;
	}
	
	.c_campaign_text
	{
		width:270px;
	}
	
	td.c_ttd_column div.popover
	{
		width: auto;
	}

	.c_miniform_loader_icon > i
	{
		color:gray;
		font-size:1.2em;
	}
	
	.c_no_bidder_icon > i
	{
		color:gray;
		font-size:1.2em;
	}

	.c_ttd_column > div.c_miniform_div
	{
		margin-bottom: 0px;
	}
	
	.c_ttd_column > button.c_bidder_button
	{
		display: none;
		width: 87px;
		height: 22px;
		padding: 0px; 
		font-size: 12px;
		
	}

	table#campaign_table tr > td.c_ttd_warning_column
	{
		padding: 5px 0px;
		width: 30px;
	}

	table#campaign_table tr > td.c_ttd_column
	{
		padding: 2px 0px 2px 0px;
	}

	.c_ttd_warning_column > div.label
	{
		padding: 8px 4px;
		display: none;
	}

	table#campaign_table td.c_ttd_column input
	{
		-webkit-border-top-left-radius: 4px;
		-webkit-border-bottom-left-radius: 4px;
		-moz-border-radius-topleft: 4px;
		-moz-border-radius-bottomleft: 4px;
		border-top-left-radius: 4px;
		border-bottom-left-radius: 4px;
		padding: 0px 0px 0px 4px;
		width: 57px;
	}

	div.c_miniform_loader_icon, div.c_no_bidder_icon
	{
		width: 87px;
	}

	div.popover table.c_popover_miniform_table td
	{
		border-top: none;
	}

	.c_miniform_hide
	{
		display: none;
	}

	#c_bulk_action_html
	{
		position: absolute;
		margin-top: 16px;
		margin-left: 105px;
	}
	#c_bulk_action_html > div > .dropdown-toggle
	{
		display: inline-block;
		padding: 4px 8px;
		margin-bottom: 0px;
		background-color: #62c462;
		border-radius: 4px;
		font-size: 1.2em;
		color: white;
	}
	#c_bulk_action_html > div > .dropdown-toggle > i.icon-list-alt
	{
		padding-right: 12px;
	}

	div.fixedHeader table.dataTable thead th.c_checkbox
	{
		max-width: 23px;
		width: 23px;
	}

	input.c_control_checkbox
	{
		margin-left: -12px;
		float: left;
	}
	
	#bulk_archive_modal_campaigns
	{
		overflow-y: auto;
		max-height: 350px;
	}

	#ct_tag_utility_button_1
	{
		display: none;
		position: absolute;
		margin-top: 0px;
		margin-left: 160px;
		z-index: 1;
		font-weight: bold;
	}

	#tag_utility_body_content
	{
		display: none;
	}

	#tag_utility_loader_content
	{
		text-align: center;
	}

	#tag_utility_modal
	{
		width: 1200px;
		margin-left: -600px;
	}

	#campaign_tags_table th
	{
		text-align: left;
	}
	
	#tag_utility_modal > div.modal-body
	{
		max-height: 500px;
		padding: 7px;
	}

	#campaign_tags_table > tbody td
	{
		border-bottom: 1px solid #ddd;
	}

	#campaign_tags_table > tbody td.ct_td_left
	{
		border-left: 1px solid #ddd;
	}

	#campaign_tags_table > tbody td.ct_td_right
	{
		border-right: 1px solid #ddd;
	}
	
	div#campaign_tags_table_length select
	{
		width: auto;
		margin-bottom: 0px;
	}

	#bulk_hidden_icon_container
	{
		position: absolute;
		z-index: 1;
		padding: 2px;
		margin-top: -10px;
		margin-left: -10px;
		width: 20px;
		height: 20px;
		text-align: center;
		background-color: #3a87ad;
		color: #fff;
		-webkit-border-radius: 20px;
		-moz-border-radius: 20px;
		border-radius: 20px;
	}

	#bulk_hidden_icon_container.bulk_hidden_alert
	{
		background-color: #b94a48;
	}

	table#campaign_table
	{
		font-size:12px;
	}

	table.dataTable thead>tr>th
	{
		font-size:12px;
	}

<?php } else { ?>

	td.c_campaign_column
	{
		width: 355px;
		max-width: 355px;
	}

	.c_campaign_text
	{
		width: 350px;
	}
	
<?php } ?>

	td.c_checkbox
	{
		max-width: 10px;
		width: 10px;
	}
	
	
	table#campaign_table thead th, table.dataTable thead td
	{
		padding: 10px 16px;
	}

	div.fixedHeader table.dataTable thead th
	{
		padding: 10px 16px;
	}
	
	.c_center
	{
		text-align: center;
	}

	.c_timeseries_column .popover {
		font-size: 11px;	
		line-height: 100%;
	  	width:300px;
	  	height:'100%';
	}

	th.c_alerts_string_column .popover {
		font-size: 10px;	
		line-height: 100%;
	  	width:350px;
	  	height:'100%';
	}

	td.c_alerts_string_column .popover {
		font-size: 10px;	
		line-height: 100%;
	  	width:1360px;
	  	height:700px;
	  	overflow: scroll;
	}
	
	#campaign_table tr.row_highlighted > td
	{	
<?php if($role == 'admin' || $role == 'ops') { ?>
		background-color: #fff8c4; /*yellow*/
<?php } else { ?>
		background-color: #d9edf7; /*blue*/
<?php } ?>
	}

	#campaign_table tr.row_highlighted_click > td
	{
		background-color: #d9edf7; /*blue*/
	}

	#campaign_table tr.row_highlighted_click.row_highlighted > td
	{
		background-color: #fff8c4; /*yellow*/
		/*background-color: #d1f1c5; /*green*/
	}
	
	.c_start_column, .c_cycle_start_column .c_cycle_end_column
	{
		min-width: 60px;
	}

	.c_type_column
	{
		min-width: 100px;
	}

	th.c_action_column
	{
		width:0px;
	}
	
	td.c_update_button_column button.c_pending_button
	{
		text-decoration: none;
		padding: 4px 8px;
		margin: 5px 0px;
	}

	.c_action_column .btn-group > a.dropdown-toggle
	{
		padding: 4px 8px;
	}
	
	div.btn-group > a.btn.btn-link.dropdown-toggle
	{
		color: #333333;
	}

	div.btn-group ul
	{
		left: -130px;
		text-align: left;
	}

	div.btn-group ul i.icon-trash
	{
		padding: 0px 2px;
	}

	div.btn-group ul i.icon-shopping-cart, div.btn-group ul i.icon-home
	{
		padding: 0px 1px;
	}
	
	div.btn-group ul i.icon-edit
	{
		padding-left: 2px;
	}

	#campaign_table tr span.alert
	{
		display: block;
		padding: 5px 0px;
		margin-bottom: 0px;
	}

	div.btn-group.open > a.btn.btn-link.dropdown-toggle
	{
		background-color: #ffffff;
		box-shadow: none;
		-webkit-box-shadow: none;
		-moz-box-shadow: none;
	}

	div.btn-group > a.btn.btn-link.dropdown-toggle:hover
	{
		text-decoration: none;
	}

	body
	{
		padding-bottom: 125px;
	}

	div#campaign_table_length select
	{
		width: auto;
		margin-bottom: 0px;
	}
	
	div#campaign_table_filter label
	{
		margin-bottom: 0px;
	}

	table#campaign_table tr > td
	{
		padding: 5px 3px;
	}

	div#campaign_html_header
	{
		float: right;
		height: 60px;
		display: none;
	}
	
	div#report_date_header
	{
		display: none;
	}

	div#campaign_html_header .btn
	{
		float: right;
		margin-top: 15px;
		margin-left: 2px;
	}

	table td.c_total_oti_column span.label, table td.c_cycle_oti_column span.label, table td.c_daily_oti_column span.label
	{
		padding: 6px 0px;
		width: 50px;
	}

	#campaign_table_paginate a.paginate_button
	{
		-webkit-border-radius: 4px;
		-moz-border-radius: 4px;
		border-radius: 4px;
	}

	#c_main_loading_img
	{
		position: absolute;
		left: 0;
		right: 0;
		top: 200px;
		margin-left: auto;
		margin-right: auto;
		width: 144px;
	}
	
	.c_campaign_column span.label.label-important
	{
		margin-left: 3px;
	}

	div.c_pending_div
	{
		font-size: 12px;
		margin-bottom: 2px;
		padding: 3px 6px;
		
	}

	#c_message_box
	{
		width:40%;
		position: fixed;
		margin-left: auto;
		margin-right: auto;
		margin-top: 0px;
		margin-bottom: 0px;
		z-index: 107;
		left: 0px;
		right: 0px;
		display: none;
	}

	#campaigns_main_page_container > .row-fluid > div
	{
		margin-left:0px;
	}

	.c_bid_warning_icon
	{
		color: #f89406;
		font-size: 0.9em;
		visibility: hidden;
	}
	.c_bid_warning_icon i.c_minus_icon_up
	{
		margin-top: -6px;
		font-size: 1em;
	}
	.c_bid_warning_icon i.c_minus_icon_down
	{
		margin-top: 8px;
		font-size: 1em;
	}

	#s2id_bd_sales_person_select > a.select2-choice, #s2id_bd_partner_select > a.select2-choice
	{
		overflow:visible;
	}

	#bulk_download_modal_body
	{
		min-height:60px;
	}

	#bulk_download_body_content
	{
		display:none;
	}
	
	#bulk_download_warning_content
	{
		font-size:17.5px;
		display:none;
	}

	#campaign_tags_page_container
	{
		display:none;
	}

	#campaign_tags_page_container
	{
		padding: 20px 10px 0px 10px;
		min-width: 650px;
	}
	#ct_header_content
	{
		height: 60px;
		padding: 0px 15px;
	}
	#ct_header_left
	{
		float: left;
	}
	#ct_header_right
	{
		float: right;
	}
	#ct_bulk_add_tags_button
	{
		margin-right: 10px;
	}
	div#ct_header_content .btn.ct_header_button
	{
		width: 175px;
	}
	.ct_bulk_tags_button_icons
	{
		float: left;
	}

	td.ct_checkbox_td
	{
		width: 23px;
	}
	td.ct_campaign_name_td
	{
		width: 270px;
	}
	td.ct_tag_list_td > input
	{
		width: 95%;
		min-width: 300px;
	}

	#ct_body_content > table tr.row_highlighted > td
	{
		background-color:#d9edf7;
	}

	#ct_campaign_tags_table
	{
		border-bottom: 1px solid #dddddd;
	}
	
	#s2id_ct_bulk_edit_campaign_tags_select2 > ul.select2-choices
	{
		min-height: 90px !important;
	}

	div#bulk_download_modal
	{
		width: 720px;
		margin-left: -360px;
	}
	#bulk_download_modal_body
	{
		text-align:center;
	}
	.bd_inline_block
	{
		display:inline-block;
	}
	.bd_form_element
	{
		float:left;
		padding:10px;
	}
	#bulk_download_form
	{
		margin-bottom:0px;
	}
	div.bd_form_element > div.select2-container
	{
		width:310px;
		text-align: left;
	}
	div.bd_form_element > input.bulk_download_datepicker
	{
		width:296px;
	}
	#bd_ae_tooltip_div
	{
		font-size:12px;
		float:right;
		margin-right: 25px;
	}


	</style>