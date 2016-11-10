/*global window, document, $ */

$(function(){

	var panes, initMediaSelect, rebuildMediaSelect, placeholder, refreshButton, previewButton, previousConfig, body;

	panes = $('form.pane');
	placeholder = $('<div class="media-select-placeholder"><div>');
	refreshButton = $('#refresh_button');
	previewButton = $('#preview_button');

	body = $('.ad_builder_body');

	function resizeBuilder() {
		body.height($(window).height() - body.offset().top);
	}

	resizeBuilder();

	$(window).on('pageshow', function(event) {
		// if page session is cached, hide "loading preview" message
		hideFeedback();
	});

	$(window).on('resize', resizeBuilder);

	$('input[type="file"]').each(function(){
		var input = $(this);
		input.on('change', function(){
			if(input.val())
			{
				input.after('<i class="uploading-file-status fa fa-refresh fa-spin" title="Uploading..."></i>');
				input.closest('form').submit();
				disableWrappedFileInput(input);
			}
		});
	});

	function selectContext(contextName)
	{
		var button, pane;

		button = $('.context-button[data-context="' + contextName + '"]');
		button.addClass('selected').siblings('.selected').removeClass('selected');

		pane = $('.context-pane[data-context="' + contextName + '"]');
		pane.addClass('selected').siblings('.selected').removeClass('selected');
	}

	$('.context-button').click(function() {
		var button = $(this);
		if(!button.is('.disabled'))
		{
			selectContext(button.data('context'));
		}
	});

	// set select behavior
	initMediaSelect = function(){
		var wrap, select, options, input;
		wrap = $(this);
		select = wrap.find('.media-select');
		options = select.find('.media-option');
		input = select.find('.config-input');
		id_input = select.find('.id-input');

		function makeSelection(optionElement)
		{
			var selection, selectionData, selectionConfig, id;

			selection = $(optionElement);
			if(!selection.length)
			{
				select = options.first();
			}
			selectionData = selection.attr('data-media');
			selectionConfigJSON = selection.attr('data-config');
			if(selectionConfigJSON)
			{
				selectionConfig = JSON.parse(selectionConfigJSON);
				if(selectionConfig)
				{
					updateConfigFields(selectionConfig);
				}
			}
			else
			{
				selectionConfig = null;
			}
			selection.addClass('selected').siblings('.selected').removeClass('selected');
			input.val(selectionData);
			if(id_input.length)
			{
				id_input.val(selection.attr('data-id'));
			}

			requestPreviewGeneration();
		}

		options.on('click', function(){
			makeSelection(this);
		});
	};

	rebuildMediaSelect = function(element, url)
	{
		var markup;

		element.find('input').val(url || '');
		element.find('.media-option').remove();

		if(url)
		{
			markup = '<div class="media-option selected" data-media="' + url + '">';
			markup += '<img src="' + url + '">';
			markup += '</div>';
			element.find('.media-select').append(markup);
		}
	};

	$('.media-select-wrap').each(initMediaSelect);

	function detectFeaturesFromLayout(layout)
	{
		var hasLogo = layout.indexOf('id="{{id}}_logo"') > -1;
		var hasHeader = layout.indexOf('id="{{id}}_header_text"') > -1;
		var hasFooter = layout.indexOf('id="{{id}}_footer_text"') > -1;
		var hasCTA = layout.indexOf('id="{{id}}_cta_btn"') > -1;

		config.video.show_poster = true;

		$('.logo-pane').toggle(hasLogo);
		$('.header-pane').toggle(hasHeader);
		$('.footer-pane').toggle(hasFooter);
		$('[data-context="cta"]').toggleClass('disabled', !hasCTA);
		ctaButonTooltip = hasCTA ? "" : "This layout has no Call to Action";
		$('[data-context="cta"]').attr('title', ctaButonTooltip);
		$('.global-trough').toggle(hasLogo || hasHeader || hasFooter);
	}

	function fitText(selector, config, fontSizeVarName, lineHeightVarName, oneline)
	{
		var deferred = $.Deferred();

		var fontSizeInterval, element, height, fontSize, lineHeight;

		element = $(selector);
		if(!element || !element.length) { return; }
		height = element.innerHeight();
		lineHeight = height + 'px';
		fontSize = height;

		element.css({'font-size': fontSize + 'px'});

		while(element[0].scrollHeight > height)
		{
			fontSize -= 0.5;
			element.css({'font-size': fontSize + 'px'});
			if(!oneline && fontSize < height / 2)
			{
				lineHeight = 'initial';
			}
			element.css({'line-height': lineHeight});
		}

		element.css({
			'font-size': fontSize + 'px',
			'line-height': lineHeight
		});

		config[fontSizeVarName] = fontSize + 'px';
		config[lineHeightVarName] = lineHeight;

		deferred.resolve(config);

		return deferred;
	}

	/*
	 * @param string hex_color 6-digit hex color with/without leading '#'
	 */
	function isColorDark(hex_color)
	{
		var i, dec_channel, dec_average;
		hex_color = hex_color.replace('#', '');
		dec_average = 0;
		for(i = 0; i < hex_color.length; i += 2)
		{
			dec_channel = parseInt(hex_color.substr(i, 2), 16);
			if(i == 2) // green seems brighter
			{
				dec_channel *= 1.8;
			}
			else if(i == 4) // blue seems darker
			{
				dec_channel *= 0.7;
			}
			dec_average += dec_channel;
		}
		dec_average /= 3;

		return dec_average < 255 / 1.8;
	}

	function updateConfigFields(config_override, trigger_change)
	{
		var
			key,
			input,
			input_string_value,
			input_changing,
			override_input,
			selectedInput,
			media_select,
			json_value,
			media_options,
			selected_media_option;

		if(trigger_change !== false)
		{
			trigger_change = true;
		}

		for(key in config_override)
		{
			config[key] = config_override[key];
			input = $('[name="'+key+'"]');
			input_changing = input;
			if(input.length)
			{
				input_string_value = config_override[key];
				if(typeof input_string_value !== 'string' && typeof input_string_value !== 'number')
				{
					input_string_value = JSON.stringify(input_string_value);
				}
				media_select = input.closest('.media-select');
				override_input = $('[data-override-key="'+key+'"]');
				if(override_input.length && override_input.val() == config_override[key]) // assuming override is a checkbox, as in its only implementation currently
				{
					input_changing = override_input;
					override_input.prop('checked', true);
				}
				else
				{
					if(override_input.length)
					{
						override_input.prop('checked', false);
					}

					if(input.is('[type=radio], [type=checkbox]'))
					{
						selectedInput = input.filter('[value="'+input_string_value+'"]')
						input_changing = selectedInput;
						selectedInput.prop('checked', true);
						input.not(selectedInput).prop('checked', false);
					}
					else if(input.is('[type=color]') && input.spectrum('get') !== input)
					{
						// color input is overridden with a polyfill
						input.spectrum('set', input_string_value);
					}
					else if(input.is('[contenteditable]'))
					{
						input.text(input_string_value);
					}
					else
					{
						input.val(input_string_value);
					}

					if(media_select.length)
					{
						media_options = media_select.find('.media-option');
						selected_media_option = media_options.first();
						media_options.each(function() {
							var option, option_media_value;

							option = $(this);
							option_media_value = option.attr('data-media');
							if(option_media_value)
							{
								option_media_value = option_media_value.replace(/\\\//g, '/'); // unescaping forward slashes which PHP escapes

								if(option_media_value == input_string_value)
								{
									selected_media_option = option;
								}
							}
						});
						selected_media_option.addClass('selected').siblings('.media-option').removeClass('selected');
					}
				}

				if(trigger_change !== false)
				{
					input_changing.trigger('change');
				}
			}
		}
	}

	var
		longPreviewTimeout,
		previewQueued;

	function requestPreviewGeneration()
	{
		if(!longPreviewTimeout)
		{
			// delay 30ms to handle (nearly) simultaneous requests
			setTimeout(function() {
				previewQueued = false;
				generatePreview();
			}, 30);

			// delay 1s between other requests
			longPreviewTimeout = setTimeout(function() {
				longPreviewTimeout = null;
				if(previewQueued)
				{
					previewQueued = false;
					generatePreview();
				}
			}, 1000);
		}
		else if(!previewQueued)
		{
			previewQueued = true;
		}
	}

	var config = {id:'f1234567890'};
	function generatePreview()
	{
		var deferred = $.Deferred();

		var previewElement;

		refreshButton.addClass('disabled');
		refreshButton.find('i').addClass('fa-spin');

		// config = {id:'f1234567890'};
		$('.config-input').each(function(){
			var input, key, override_input, complement_color_key, color_to_complement, value, json_value;
			input = $(this);
			key = input.attr('name');
			if(input.is('[type="checkbox"], [type="radio"]') && input.is(':not(:checked)') && input.is(':not([data-off-value])'))
			{
				return true; // continue to the next input
			}

			override_input = $('[data-override-key="'+key+'"]');
			if(override_input.length && override_input.is(':checked')) // assuming override is a checkbox, as in its only implementation currently
			{
				value = override_input.val();
			}
			else
			{
				if(input.is('[data-off-value]:not(:checked)')) // an unchecked box, but with a specified "off" value
				{
					value = input.attr('data-off-value');
				}
				else
				{
					value = input[input.is('[contenteditable]') ? 'text' : 'val']();
				}
			}

			complement_color_key = input.data('colorToComplement');
			if($('[data-override-key="'+complement_color_key+'"]:checked').length)
			{
				// if that color is being overridden, get the color field specified in data-override-color-to-complement
				complement_color_key = input.data('overrideColorToComplement');
			}
			if(complement_color_key)
			{
				color_to_complement = $('[name="' + complement_color_key + '"]').val();
				value = isColorDark(color_to_complement) ? '#FFFFFF' : '#000000';
			}
			try {
				json_value = JSON.parse(value);
				value = json_value;
			} catch(ignore) {}
			config[key] = value;
		});

		detectFeaturesFromLayout(config.layout);

		previewElement = document.getElementById(config.id);
		window.ad = new window.frqVidAd(previewElement, config);

		waitForAdStyle()
			.then(fitLogoText)
			.then(fitHeaderText)
			.then(fitCtaText)
			.then(fitFooterText)
			.then(function() {
				refreshButton.removeClass('disabled');
				refreshButton.find('i').removeClass('fa-spin');
				window.config = config; // for debugging
				deferred.resolve(config);
			});

		return deferred;
	}

	function waitForAdStyle()
	{
		var deferred = $.Deferred();

		var isAdStyleApplied, adStyleCheckInterval;

		isAdStyleApplied = false;

		adStyleCheckInterval = setInterval(function() {
			var containerCursorStyle = $("#" + config.id).css('cursor');
			if(containerCursorStyle == 'pointer')
			{
				clearInterval(adStyleCheckInterval);
				deferred.resolve();
			}
		}, 10);

		return deferred;
	}

	function fitLogoText() {
		return fitText('#'+config.id+'_logo_text', config, 'logo_text_size', 'logo_line_height', true)
	}
	function fitHeaderText() {
		return fitText('#'+config.id+'_header_text', config, 'header_text_size', 'header_line_height');
	}
	function fitCtaText() {
		return fitText('#'+config.id+'_cta_btn', config, 'cta_text_size', 'cta_line_height', true);
	}
	function fitFooterText() {
		return fitText('#'+config.id+'_footer_text', config, 'footer_text_size', 'footer_line_height');
	}

	function savePreview()
	{
		var markup, config, template_id;

		showFeedback($('#msg-saving').html());
		generatePreview().then(function(config) {
			template_id = $('.context-pane[data-context="layout"] .media-option.selected').attr('data-id');
			advertiser_id = $('[name="advertiser_id"]').val();
			demo_title = $('.editable_demo_title').text(); // getting text from contenteditable <span>
			delete config.layout; // this will be restored with the layout (a.k.a. template) from the database
			$.ajax({
				url: window.location.pathname.replace('builder', 'ajax_publish_ad'),
				data: {
					config: JSON.stringify(config),
					template_id: template_id,
					advertiser_id: advertiser_id,
					demo_title: demo_title
				},
				type: 'POST',
				success: function(data){
					handleAjaxResponse(data, function(response) {
						hideFeedback();
						window.location = response.preview_url;
					}, function() {
						showFeedback($('#msg-error').html());
						setTimeout(hideFeedback, 5000);
					});
				},
				error: function() {
					showFeedback($('#msg-error').html());
					setTimeout(hideFeedback, 5000);
				}
			});
		});
	}

	function updateDependentElements(key, value)
	{
		affected_ui = $('[data-get-config="'+key+'"]').each(function() {
			var receving_element, css_property;
			receving_element = $(this);
			css_property = receving_element.attr('data-get-config-style');
			receving_element.css(css_property, value);
		});
	}

	// update dependent elements with initial input values
	var keys_depended_upon = [];
	$('[data-get-config]').each(function() {
		var dependent_element = $(this);
		var key_listened_for = dependent_element.data('getConfig');
		if(keys_depended_upon.indexOf(key_listened_for) === -1)
		{
			keys_depended_upon.push(key_listened_for);
			var value = $('[name="'+key_listened_for+'"]').val();
			updateDependentElements(key_listened_for, value);
		}
	});

	// enable manual changes
	// expects `this` to be an input element, as if bound on a `change` or `input` event
	// if calling independently, use `apply`
	function reactToUserChanges()
	{
		var key, value, changed_input, is_overriding, input_to_override;
		changed_input = $(this);
		key = changed_input.attr('name');
		value = changed_input.val();
		if(changed_input.is('.config-input-override'))
		{
			key = changed_input.attr('data-override-key');
			input_to_override = $('[name="'+key+'"]');
			is_overriding = changed_input.is(':checked');
			input_to_override.prop('disabled', is_overriding);
			if(!is_overriding)
			{
				if(input_to_override.is('[type="checkbox"], [type="radio"]'))
				{
					input_to_override = input_to_override.filter(':checked');
				}
				value = input_to_override[input_to_override.is('[contenteditable]') ? 'text' : 'val']();
			}
		}
		updateDependentElements(key, value);
		requestPreviewGeneration();
	}

	// detect color input changes
	// (polyfill, e.g. in IE9, doesn't trigger 'change' on inputs correctly)
	var color_input_polyfill_values = {};
	var color_input_polyfill_change_interval = setInterval(function() {
		$('input[type="color"]').each(function() {
			var input = $(this);
			var key = input.attr('name');
			var value = input.val();
			if(typeof color_input_polyfill_values[key] === 'undefined')
			{
				color_input_polyfill_values[key] = value;
			}
			if(color_input_polyfill_values[key] !== value)
			{
				color_input_polyfill_values[key] = value;
				reactToUserChanges.call(this);
			}
		});
	}, 33); // ~30x per second
	$('.config-input, .config-input-override').on('change', reactToUserChanges);
	$('.config-listen-input').on('input', function(){
		requestPreviewGeneration();
	});
	// disable/alter default form behavior
	$('form').each(function() {
		var form, target, loadHandler;
		form = $(this);
		form.on('submit', function(event){
			target = form.attr('target');
			if(!target)
			{
				event.preventDefault();
			}
			else
			{
				loadHandler = function()
				{
					$(this).off('load', loadHandler);
					form.find('.uploading-file-status').remove();
					enableWrappedFileInput(form.find('input[type=file]'));

					var iframeDocument = this.contentDocument || this.contentWindow.document;
					handleAjaxResponse(iframeDocument.body.innerHTML, function(response) {
						rebuildMediaSelect(form.find('.media-select-wrap'), response['cdn_urls'][0]);
						form.find('input.file_exists_flag').val('yes');
						form.find('.delete_button').removeClass('disabled');
						requestPreviewGeneration();
					});
				};
				$('iframe[name="'+target+'"]').on('load', loadHandler);
			}
		});
		form.find('.delete_button').on('click', function(){
			var button = $(this);
			button.addClass('disabled');
			button.after('<i class="deleting-file-status fa fa-refresh fa-spin"></i>');
			$.ajax({
				url: '/vab/ajax_delete_video_ad_logo/' + window.video_ad_key,
				type: 'POST',
				success: function(data){
					button.siblings('.deleting-file-status').remove();
					handleAjaxResponse(data, function(response) {
						form.find('input[type="file"]').val('');
						form.find('input.file_exists_flag').val('no');
						rebuildMediaSelect(form.find('.media-select-wrap'), ''); // empty the media select
						requestPreviewGeneration();
					}, function() {
						button.removeClass('disabled');
						showFeedback($('#msg-error').html());
						setTimeout(hideFeedback, 5000);
					});
				},
				error: function() {
					button.removeClass('disabled');
					showFeedback($('#msg-error').html());
					setTimeout(hideFeedback, 5000);
				}
			});
		});
	});

	// replace file input with a button
	$('input[type="file"]').each(function() {
		var input = $(this);
		var wrapper = $('<label class="file_input_wrapper"></label>');

		// place the wrapper after the input in the DOM,
		// and then put the input in the wrapper
		wrapper.insertAfter(input);
		wrapper.append(input);
	});

	var disableWrappedFileInput = function(input)
	{
		var label = $(input).closest('label.file_input_wrapper');
		input = label.find('input[type=file]');
		label.addClass('disabled');
		input.prop('disabled', true);
	};

	var enableWrappedFileInput = function(input)
	{
		var label = $(input).closest('label.file_input_wrapper');
		input = label.find('input[type=file]');
		label.removeClass('disabled');
		input.prop('disabled', false);
	};

	if(window.loaded_config)
	{
		var clone_config = $.extend({}, window.loaded_config);
		if(typeof clone_config.advertiser_url !== 'undefined')
		{
			delete clone_config.advertiser_url;
		}
		updateConfigFields(clone_config);
	}
	else
	{
		$('[data-context="layout"] .media-option.selected').click();
		$(window).on('load', function() {
			requestPreviewGeneration();
		});
	}

	// initialize
	generatePreview();

	refreshButton.click(generatePreview);

	previewButton.click(savePreview);

});
