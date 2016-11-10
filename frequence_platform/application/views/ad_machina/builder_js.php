		<script type="text/javascript" src="/assets/js/modernizr.3.2.0.video-transform.min.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
		<script type="text/javascript" src="/assets/color-input-polyfill/color-input-polyfill.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>

		<script type="text/javascript" src="/assets/js/ad_machina/ad_machina_common.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
		<script type="text/javascript" src="/assets/js/ad_machina/ad_platform.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
		<script type="text/javascript">
			window.video_ad_key = <?php echo json_encode($video_ad_key); ?>;
			window.loaded_config = <?php echo $video_ad_config_json; ?>;
			window.loaded_template_id = <?php echo json_encode($video_ad_template_id); ?>;
		</script>
		<script type="text/javascript" src="/assets/js/ad_machina/ad_builder.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
