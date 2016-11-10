<script type="text/javascript" src="http://code.jquery.com/jquery-2.1.1.min.js"></script>
<script src="/libraries/external/js/jquery-file-upload-9.5.7/js/vendor/jquery.ui.widget.js"></script>
<script src="/bootstrap/assets/js/bootstrap.min.js"></script>
<script src="/assets/js/materialize_freq.min.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>

<!-- The basic File Upload plugin -->
<script src="/libraries/external/js/jquery-file-upload-9.5.7/js/jquery.fileupload.js"></script>
<script src="/libraries/external/select2/select2.js"></script>

<script type="text/javascript">
	var partner_create_update = '<?php echo $source; ?>';
	var parent_partner_data = <?php echo $parent_partner_name_option_array; ?>;
        var partner_form_error_message = '<?php echo $partner_form_error_message; ?>';
        var partner_status = '<?php echo $partner_status; ?>';
        var favicon_image_path = '<?php echo !empty($form_data['favicon_path']) ? $form_data['favicon_path'] : $form_data['default_favicon_path']; ?>';
        var header_image_path = '<?php echo !empty($form_data['header_img']) ? $form_data['header_img'] : $form_data['default_header_img']; ?>';
        var logo_image_path = '<?php echo !empty($form_data['partner_report_logo_filepath']) ? $form_data['partner_report_logo_filepath'] : $form_data['default_partner_report_logo_filepath']; ?>';
	var logo_image_path = '<?php echo !empty($form_data['partner_report_logo_filepath']) ? $form_data['partner_report_logo_filepath'] : $form_data['default_partner_report_logo_filepath']; ?>';        
	var default_partner_user_email = '<?php echo $form_data['default_partner_user_email']; ?>';
	var default_advertiser_email = '<?php echo $form_data['default_advertiser_email']; ?>';
 	var partner_palette_data = <?php echo $partner_palette_info; ?>;
</script>
<script src="/assets/js/partners/partner.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>

</body>
</html>