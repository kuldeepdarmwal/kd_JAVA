<?php
/*
 * @param string $name
 * @param string $selection - index of selection among $options
 * @param array $options - indexed array of options, themselves associative arrays of values
 *	e.g.
 *	[
 *		[
 *			'thumbnail' => 'http://cdn.example.com/thumbnail.jpg',
 *			... other optional metadata
 *			'media' => [
 *				'default' => 'http://cdn.example.com/button_default.jpg',
 *				'hover' => 'http://cdn.example.com/button_hover.jpg'
 *			]
 *		]
 *	]
 * @param array $settings - indexed array of settings, see defaults below
 */
function put_media_select($name, $options = [], $selection = NULL, $settings = [])
{
	$settings = array_merge([
		'tile' => false,
		'empty_option' => false,
		'empty_option_title' => null,
		'attributes' => [],
		'media_attributes' => [],
		'suppress_titles' => false,
		'selection_by_id' => false, // false: use selection as value, true: use selection as ID
	], $settings);

	if($settings['empty_option'])
	{
		$empty_option = ['media'=>null];
		if($settings['empty_option_title'])
		{
			$empty_option['title'] = $settings['empty_option_title'];
		}
		array_unshift($options, $empty_option);
	}

	$wrap_classes = array('media-select-wrap');
	$detailed = false;
	$has_id = false;
	$output = '';

	$selection_found = false;
	$selected_id = NULL;
	foreach($options as $option) {
		$classes = ['media-option'];
		$attributes = $settings['attributes'];
		$media_attributes = $settings['media_attributes'];

		$media_json = json_encode($option['media']);
		$media_id = NULL;
		$config_json = null;
		if(!empty($option['config_json']))
		{
			$attributes[] = 'data-config="' . htmlentities($option['config_json']) . '"';
		}

		if(isset($option['id']))
		{
			$attributes[] = 'data-id="' . $option['id'] . '"';
			$has_id = true;
		}
		if($selection === NULL)
		{
			$selection = $media_json;
		}
		if(!$selection_found && ($selection == $media_json || ($settings['selection_by_id'] && $selection == $option['id'])))
		{
			if($settings['selection_by_id'])
			{
				$selection = $media_json;
			}
			$selection_found = true;
			$classes[] = 'selected';
			if($has_id)
			{
				$selected_id = $option['id'];
			}
		}
		if(!empty($option['title']))
		{
			$detailed = true;
		}

		$attributes[] = 'data-media="' . htmlentities($media_json) . '"';

		$image = '';
		if($settings['tile'])
		{
			$classes[] = 'tile-thumbnail';
			$option_style = '';
			if(isset($option['thumbnail']))
			{
				$option_style = 'style="background-image:url(\'' . $option['thumbnail'] . '\')"';
			}
			$image = '<div class="media-option-pattern" '.$option_style.' ' . implode(' ', $media_attributes) . '></div>';
		}
		else if(isset($option['thumbnail']))
		{
			$image = '<img src="' . $option['thumbnail'] . '" ' . implode(' ', $media_attributes) . '>';
		}
		$output .= '<div class="' . implode(' ', $classes) . '" ' . implode(' ', $attributes) . '>';
		$output .= $image;
		if(isset($option['title']) && !$settings['suppress_titles'])
		{
			$output .= '<div class="media-label">' . $option['title'] . '</div>';
		}
		$output .= '</div>';
	}
	if($detailed)
	{
		$wrap_classes[] = 'detailed';
	}
	if($has_id)
	{
		$output .= '<input type="hidden" class="id-input" name="' . $name . '_id" value="' . htmlentities($selected_id) . '">';
	}

	$output .= '<input type="hidden" class="config-input" name="' . $name . '" value="' . htmlentities($selection) . '">';

	// wrap with opening and ending tags
	$output_start = '<div class="' . implode(' ', $wrap_classes) . '">'
		. '<div class="media-select">';
	$output_end = '</div>'
		. '</div>';
	$output = $output_start . $output . $output_end;

	echo $output;

	return count($options);
}
?>
		<div class="ad_builder">
			<div class="ad_machina_nav clear">
				<div class="left">
					<a class="link-back" href="/vab/videos/<?php echo $creative['frq_third_party_account_id']; ?>" title="Pick another video"><i class="fa fa-caret-left"></i></a>
					<span contenteditable class="editable_demo_title"><?php echo $demo_title; ?></span>
				</div>
			    <?php if($this->session->userdata('is_demo_partner') != 1) { ?>
				<div class="right">
					<span id="save_status">auto-save not yet implemented...</span>
					<button id="preview_button">Save &amp; Preview</button>
				</div>
			    <?php } ?>
			</div>
			<div class="ad_builder_body">
				<div class="context-switcher">
					<!--div class="context-button" data-context="video">Video</div-->
					<div class="context-button selected" data-context="layout">Layout</div>
					<div class="context-button" data-context="bg">Background</div>
					<div class="context-button" data-context="cta">Call to Action</div>
				</div>
				<div class="context-trough">
					<div class="context-pane" data-context="video">
						<?php put_media_select('video', $builder_config['videos']); ?>

						<h4>play button</h4>
						<?php put_media_select('play_button', $assets['play_buttons']); ?>
					</div>
					<div class="context-pane selected" data-context="layout">
						<?php put_media_select('layout', $layouts, $video_ad_template_id, ['suppress_titles'=>true, 'selection_by_id'=>true]); // TODO: select by data-id ?>
					</div>
					<div class="context-pane" data-context="bg">
						<div class="clear">
							<div class="left">
								<label>Background Color</label>
							</div>
							<input type="color" class="config-input right" name="bg_color" value="#000000">
						</div>
						<hr>
						<?php put_media_select('bg_img', $assets['backgrounds'], NULL, ['tile'=>TRUE, 'empty_option'=>true, 'empty_option_title'=>'solid color', 'media_attributes'=>['data-get-config="bg_color"', 'data-get-config-style="background-color"'], 'suppress_titles'=>true]); ?>
					</div>
					<div class="context-pane" data-context="cta">
						<div class="clear">
							<div class="left">
								<label>CTA Copy</label>
							</div>
							<div class="right">
								<input type="text" class="config-input config-listen-input" name="cta_text" value="Learn More!">
							</div>
						</div>
						<div class="clear">
							<label for="cta_bg_color" class="left">CTA Background Color</label>
							<div class="right">
								<label class="checkbox-label">
									none
									<input type="checkbox" class="config-input-override" name="cta_bg_transparent" data-override-key="cta_bg_color" value="transparent">
								</label>
								<input type="color" class="config-input right" name="cta_bg_color" value="#666666">
								<input type="hidden" class="config-input" name="cta_color" value="#FFFFFF" data-color-to-complement="cta_bg_color" data-override-color-to-complement="bg_color">
							</div>
						</div>
						<div class="clear">
							<label>
								<div class="left">Rounded Corners</div>
								<div class="right">
									<input type="checkbox" class="config-input" name="cta_radius" value="0.3em" data-off-value="0">
								</div>
							</label>
						</div>
						<div class="clear">
							<label>
								<div class="left">Border</div>
								<div class="right">
									<input type="checkbox" class="config-input" name="cta_border" value="1px solid #FFFFFF" data-off-value="0">
								</div>
							</label>
						</div>
					</div>
				</div>
				<div class="workspace">
					<div class="ad_preview_wrap">
						<div class="ad_controls">
							<div class="ad_zoom_controls left">
								<i class="fa fa-search-plus" title="Zoom (not yet implemented)"></i>
								<span class="ad-view-size-status">100%</span>
								<i class="fa fa-search-minus" title="Zoom (not yet implemented)"></i>
							</div>
							<div class="right">
								<span id="refresh_button" title="Click to restart ads">
									Update <i class="fa fa-refresh"></i>
								</span>
							</div>
						</div>
						<div id="f1234567890" class="preview requires-video requires-csstransforms" data-init="w=300&h=250&bw=1&bc=666"></div>
						<div class="no-support-message">
							<h2>Ooops!</h2>
							<p>This preview requires a browser that supports HTML Video (e.g. Internet Explorer 9 or newer).</p>
						</div>
					</div>
					<div class="global-trough">
						<div class="logo-pane">
							<label>logo</label>
							<div class="logo-text">
								<input type="text" class="config-input config-listen-input with-color" name="logo_text" value="<?php echo $creative['advertiser_name']; ?>">
								<input type="color" class="config-input" name="logo_color" value="#FFFFFF">
							</div>
							<form class="logo-file" action="/ad_machina/ajax_upload_video_ad_logo/<?php echo $video_ad_key; ?>" method="POST" enctype="multipart/form-data" target="upload_iframe">
								<input type="file" name="logo">
								<div class="clear">
									<div class="left">
									<?php $logo_count = put_media_select('logo_img', $assets['logos']); ?>
									</div>
									<div class="right">
									<i class="delete_button<?php echo ($logo_count > 0 ? '' : ' disabled'); ?> fa fa-trash"></i>
									</div>
								</div>
								<input type="hidden" class="config-input file_exists_flag" name="logo_is_file" value="<?php echo ($logo_count > 0 ? 'yes' : 'no'); ?>">
							</form>
						</div>
						<div class="header-pane">
							<div class="header-text">
								<label>header</label>
								<input type="text" class="config-input config-listen-input with-color" name="header_text" value="Main Message">
								<input type="color" class="config-input" name="header_color" value="#FFFFFF">
							</div>
						</div>
						<div class="footer-pane">
							<div class="footer-text">
								<label>footer</label>
								<input type="text" class="config-input config-listen-input with-color" name="footer_text" value="One last thing to say before you click that Call to Action button.">
								<input type="color" class="config-input" name="footer_color" value="#FFFFFF">
							</div>
						</div>
						<input type="hidden" class="config-input" name="dur" value="<?php echo $builder_config['dur']; ?>">
					</div>
				</div>
			</div>
		</div>

		<input type="hidden" name="builder_script_initialized" value="0">

		<iframe name="upload_iframe" src="about:blank" style="display:none"></iframe>

		<script type="x-template" id="msg-saving"><h4>Preparing your ad for demonstration.</h4><i class="fa fa-refresh fa-spin"></i></script>
		<script type="x-template" id="msg-loading"><h4>Loading Preview.</h4><i class="fa fa-refresh fa-spin"></i></script>
		<script type="x-template" id="msg-error"><h4>There was an error. Please try again.</h4></script>

<?php if($mixpanel_info['user_unique_id']) : ?>
<script type="text/javascript">

	var timezone_offset = (new Date().getTimezoneOffset() / 60) * -1; // getTimezoneOffset returns offset in minutes, so divide by 60

	mixpanel.identify("<?php echo $mixpanel_info['user_unique_id']; ?>");
	mixpanel.people.set({
		"$first_name": "<?php echo $mixpanel_info['user_firstname']; ?>",
		"$last_name": "<?php echo $mixpanel_info['user_lastname']; ?>",
		"$email": "<?php echo $mixpanel_info['user_email']; ?>",
		"user_type": "<?php echo $mixpanel_info['user_role']; ?>",
		"is_super": "<?php echo $mixpanel_info['user_is_super']; ?>",
		"partner_id": "<?php echo $mixpanel_info['user_partner_id']; ?>",
		"partner_name": "<?php echo $mixpanel_info['user_partner']; ?>",
		"cname": "<?php echo $mixpanel_info['user_cname']; ?>",
		"advertiser_name": "<?php echo $mixpanel_info['user_advertiser_name']; ?>",
		"advertiser_id": "<?php echo $mixpanel_info['user_advertiser_id']; ?>",
		"timezone_offset": timezone_offset,
		"last_page_visit": "<?php echo $mixpanel_info['page_access_time']; ?>",
		"last_page_visited": "/vab/builder"
	});
	mixpanel.people.set_once({
		"$created": "<?php echo $mixpanel_info['page_access_time']; ?>",
		"page_views": 0
	});
	mixpanel.people.increment("page_views");

</script>
<?php endif; ?>
