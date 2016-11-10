<html><head><style>body{margin:0;padding:0}</style></head><body>
<div id="f1234567890" data-init="<?php echo 'w='.$config_data['w'].'&h='.$config_data['h'].'&bw='.$config_data['bw'].'&bc='.$config_data['bc']; ?>">
<script>
<?php echo file_get_contents(FCPATH . '/assets/js/ad_machina/ad_platform.min.js'); ?>
<?php //* DEBUG */ echo file_get_contents(FCPATH . '/assets/js/ad_machina/ad_platform.js'); ?>
;config=<?php echo $config_json; ?>;
;config.layout=<?php echo $template_json; ?>;
;window.ad=new frqVidAd(document.getElementById('f1234567890'), config); <?php // TODO: use dynamic ID ?>
</script>
<?php /* TODO: set backup below by individual ad configuration -CL ?>
<noscript><a target="_blank" href="%c%u"><img src="https://0267a94cab6d385b9024-b73241aae133d24e20527933c2ff6c10.ssl.cf1.rackcdn.com/CDN_API_565f72402a7e1_300x250_backup.jpg" border="0"></a></noscript>
<?php */ ?>
</body></html>
