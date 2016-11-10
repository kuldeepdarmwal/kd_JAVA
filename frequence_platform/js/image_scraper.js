(function($) {
	var
		// methods
		setupUI,
		handleFormSubmission,
		preloadImages,
		showImages,
		logFeedback,
		makeTemplate,
		toggleDisabled,
		sortBySizeDesc,
		startTimer,
		stopTimer,

		// variables
		result,
		images,
		disabled,
		form,
		submitButton,
		timerData,
		loadTime,
		imageLoadTimeout;

	imageLoadTimeout = 2000; // how many seconds of no images loading before calling it done?

	setupUI = function() {
		$('.transparency-option').on('click', function() {
			var selectedOptionClassName;

			selectedOptionClassName = this.className.replace(/^.*((light|medium|dark)-transparency).*$/, '$1');
			$(this).addClass('selected').siblings('.transparency-option').removeClass('selected');

			$('.results').removeClass('light-transparency medium-transparency dark-transparency').addClass(selectedOptionClassName);
		});

		form = $("form#scrape_request");
		submitButton = form.find('[type=submit]');
		form.on('submit', handleFormSubmission);
		setupSticky(); // /assets/sticky/sticky.js
	};

	handleFormSubmission = function(event) {
		event.preventDefault();

		toggleDisabled(true);
		startTimer(function(time) {
			logFeedback(['<i class="icon-cog icon-spin"></i> Scraping pages (' + time + 's)...']);
		});

		$.ajax({
			url: form.attr('action'),
			data: form.serialize(),
			type: 'GET'
		}).then(function(json) {
			if (json) {
				try {
					result = JSON.parse(json);
				} catch(e) {
					logFeedback(['API response was not formatted as JSON.'], 'error');
					return false;
				}
				if (!result.error.length) {
					if (result.urls.image.length) {
						preloadImages();
					} else {
						logFeedback(['No images found.'], 'error');
					}
				} else {
					logFeedback(result.error, 'error');
				}
			} else {
				logFeedback(['Empty response from API.'], 'error');
			}
		}).fail(function() {
			logFeedback(['Network error connecting to API.'], 'error');
		}).always(function() {
			stopTimer();
			toggleDisabled(false);
		});
	};

	startTimer = function(step) {
		console.log('startTimer');
		stopTimer();
		timerData = {
			step: function() {
				timerData.time++;
				step(timerData.time);
			},
			time: 0
		};
		timerData.interval = setInterval(timerData.step, 1000);
		timerData.step();
	};

	stopTimer = function() {
		console.log('stopTimer');
		if (typeof timerData === 'object' && timerData.hasOwnProperty('interval')) {
			clearInterval(timerData.interval);
		}
	};

	preloadImages = function() {
		var preloadStartTime, preloadAbandoned, finishPreload, timeoutID, bumpTimeout;

		logFeedback(['<i class="icon-cog"></i> Loading images [1/' + result.urls.image.length + ']']);

		preloadStartTime = (new Date()).getTime();
		loadTime = 0;
		images = [];
		imageLoadIntervals = [];

		finishPreload = function() {
			clearTimeout(timeoutID);
			loadTime = Math.round((new Date()).getTime() - preloadStartTime) / 1000;
			preloadAbandoned = true;
			images = images.sort(sortBySizeDesc);
			showImages();
		};

		$(result.urls.image).each(function(i) {
			var imageData = {
				key: i,
				url: this.url,
				page: this.page,
				image: document.createElement('IMG'),
				width: 0,
				height: 0,
				pixels: 0
			};

			$(imageData.image).on('load', function() {
				if (preloadAbandoned) {
					return;
				}

				logFeedback(['<i class="icon-cog icon-spin"></i> Loading images [' + (images.length + 1) + '/' + result.urls.image.length + ']']);

				bumpTimeout();

				imageData.width = this.naturalWidth;
				imageData.height = this.naturalHeight;
				imageData.pixels = imageData.width * imageData.height;

				images.push(imageData);

				if (images.length === result.urls.image.length) {
					finishPreload();
				}
			});

			bumpTimeout = function() {
				clearTimeout(timeoutID);
				timeoutID = setTimeout(finishPreload, imageLoadTimeout);
			};

			bumpTimeout();

			// load the image
			imageData.image.setAttribute('src', imageData.url);
		});
	};

	showImages = function() {
		var imageTemplate, imageOutput, feedback;

		feedback = images.length + ' images found (' +
			result.time.total + 's to scrape, ' +
			loadTime + 's to load' +
			')';

		logFeedback([feedback]);
		imageTemplate = makeTemplate($("#template_each_image").html());

		imageOutput = '';

		$(images).each(function() {
			imageOutput += imageTemplate(this);
		});

		$("#image_results").html(imageOutput).show();
	};

	makeTemplate = function(src) {
		return function(data) {
			var output, key, pattern;

			output = src.substr(0);

			for (key in data) {
				if (typeof key === 'string') {
					pattern = new RegExp('{{' + key + '}}', 'g');
					output = output.replace(pattern, data[key]);
				}
			}

			return output;
		};
	};

	logFeedback = function(messages, classes) {
		var messagesContainer, messagesMarkup;

		messagesContainer = $("#feedback_container");
		messagesMarkup = messages.join('<br>');
		messagesContainer.html(messagesMarkup).attr('class', 'feedback ' + classes).show();
	};

	toggleDisabled = function(forceValue) {
		if (typeof forceValue !== 'undefined') {
			disabled = forceValue;
		} else {
			disabled = !disabled;
		}
		if (disabled) {
			submitButton.attr('disabled', "true");
		} else {
			submitButton.removeAttr('disabled');
		}
	};

	sortBySizeDesc = function(a, b) {
		if (a.pixels < b.pixels) return 1;
		if (a.pixels > b.pixels) return -1;
		return 0;
	};

	$(function() {
		setupUI();
	});
})(jQuery);
