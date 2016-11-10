/*global window, require, EventDispatcher, Widget, ImageWidget, ButtonWidget, VideoWidget, overload, deserialize, makeTemplate, emptyNode, setAttribute, applyStyles, hasClass, setClass*/

window.frqVidAd = (function(){

/*global window, document, Element*/

/*
 * Utility functions
 */
function deserialize(string)
{
	var chunks, i, len, pair, output;
	output = {};
	chunks = string.split('&');

	for(i = 0, len = chunks.length; i < len; i++)
	{
		pair = chunks[i].split('=');
		output[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1]);
	}

	return output;
}

function overload(source, more)
{
	var key;
	if(source && more)
	{
		for(key in source)
		{
			if(more[key] === undefined)
			{
				more[key] = source[key];
			}
		}
	}
	return more || source;
}

/*
 * templating a la mustache
 */
function makeTemplate(templateMarkup)
{
	return function(data)
	{
		var key, output = '' + templateMarkup;

		for(key in data)
		{
			if(typeof data[key] === 'string' || typeof data[key] === 'number')
			{
				output = output.replace(new RegExp('{{' + key + '}}', 'g'), data[key]);
			}
		}

		output = output.replace(/{{[^}]+}}/g, ''); // remove unmatched variables

		return output;
	};
}

/*
 * DOM and browser functions
 */
function applyStyles(element, styles)
{
	var prop;
	for(prop in styles)
	{
		if(typeof styles[prop] === 'string')
		{
			element.style[prop] = styles[prop];
		}
	}
}

/*
 * @param Element element
 * @param string className
 * (optional) @param boolean returnDetails - return detailed object, instead of boolean
 * @returns boolean/object hasClass or details
 */
function hasClass(element, className, returnDetails)
{
	var details = {};

	details.className = element.className.trim();
	details.classList = (details.className ? element.className.trim().split(/\s+/) : []);
	details.classIndex = details.classList.indexOf(className);
	details.hasClass = details.classIndex !== -1;

	return returnDetails ? details : details.hasClass;
}

/*
 * @param Element element
 * @param string classes space-separated CSS class names
 * (optional) @param boolean/null add - true = add; false = remove; null = toggle. Default: true
 */
function setClass(element, classes, add)
{
	var i = 0, len, className, details;

	classes = classes.trim().split(/\s+/);

	for(i = 0, len = classes.length; i < len; i++)
	{
		className = classes[i];

		details = hasClass(element, className, true);

		if(add === undefined)
		{
			add = true;
		}
		else if(add === null)
		{
			add = !details.hasClass;
		}

		if(add && !details.hasClass)
		{
			details.classList.push(className);
		}
		else if(!add && details.hasClass)
		{
			details.classList.splice(details.classIndex, 1);
		}
	}
	element.className = details.classList.join(' ');
}

/*
 * listen to several events; preserves handler scope
 * @param Element target space-separated
 * @param string eventNames space-separated
 * @param Function handler
 */
function addSeveralEventListeners(target, eventNames, handler)
{
	var i, len;

	eventNames = eventNames.trim().split(/\s+/);

	for(i = 0, len = eventNames.length; i < len; i++)
	{
		target.addEventListener(eventNames[i], handler);
	}
}

/*
 * remove the listeners from several events
 * @param Element target space-separated
 * @param string eventNames space-separated
 * @param Function handler
 */
function removeSeveralEventListeners(target, eventNames, handler)
{
	var i, len;

	eventNames = eventNames.trim().split(/\s+/);

	for(i = 0, len = eventNames.length; i < len; i++)
	{
		target.removeEventListener(eventNames[i], handler);
	}
}

/*
 * @param DOMElement target
 * @param Function overHandler
 * @param Function outHandler
 */
function mouseoutIntent(target, overHandler, outHandler, initialEventType)
{
	var mouseoutIntentTimeout, initialEventType;

	initialEventType = initialEventType || 'mouseover';

	target.addEventListener(initialEventType, function(event) {
		if(mouseoutIntentTimeout)
		{
			clearTimeout(mouseoutIntentTimeout);
			mouseoutIntentTimeout = null;
		}
		else
		{
			overHandler.apply(event.target, [event]);
		}
	});

	target.addEventListener('mouseout', function(event) {
		mouseoutIntentTimeout = setTimeout(function() {
			outHandler.apply(event.target, [event]);
		}, 0);
	});

	// TODO: enable removing these listeners, too
}

/*
 * helper to set, add, or remove attributes
 */
function setAttribute(element, name, value)
{
	if(typeof value === 'boolean' && !value)
	{
		element.hasAttribute(name) && element.removeAttribute(name);
	}
	else
	{
		element.setAttribute(name, value);
	}
}

function emptyNode(element)
{
	while(element.firstChild)
	{
		element.removeChild(element.firstChild);
	}
}

function toggleFullScreen(element)
{
	if (!element) {
		element = document.documentElement;
	}
	if (!document.fullscreenElement &&    // alternative standard method
			!document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement ) {  // current working methods
		if (element.requestFullscreen) {
			element.requestFullscreen();
		} else if (element.msRequestFullscreen) {
			element.msRequestFullscreen();
		} else if (element.mozRequestFullScreen) {
			element.mozRequestFullScreen();
		} else if (element.webkitRequestFullscreen) {
			element.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
		}
	} else {
		if (document.exitFullscreen) {
			document.exitFullscreen();
		} else if (document.msExitFullscreen) {
			document.msExitFullscreen();
		} else if (document.mozCancelFullScreen) {
			document.mozCancelFullScreen();
		} else if (document.webkitExitFullscreen) {
			document.webkitExitFullscreen();
		}
	}
}

/*
 * @param String url sprite sheet image URL
 * @param Number w width, pixels
 * @param Number h height, pixels
 * @param Number x sprite left position, pixels
 * @param Number y sprite top position, pixels
 * @param Number wPadding left/right padding, pixels (optional)
 * @param Number hPadding top/bottom padding, pixels (optional)
 *
 * use with `new Sprite(...)`
 */
function Sprite(url, w, h, x, y, wPadding, hPadding)
{
	this.element = document.createElement('DIV');
	this.imageElement = document.createElement('DIV');

	this.element.appendChild(this.imageElement);

	wPadding = wPadding || 0;
	hPadding = hPadding || 0;

	// assuming CSS 'box-sizing' has initial value of 'content-box', so no bootstrap '* { }' junk!
	applyStyles(this.element, {
		width: w + 'px',
		height: h + 'px',
		padding: hPadding + 'px ' + wPadding + 'px'
	});
	applyStyles(this.imageElement, {
		'background-image': 'url(' + url + ')',
		'background-repeat': 'no-repeat',
		'background-position': '-' + x + 'px -' + y + 'px',
		width: w + 'px',
		height: h + 'px'
	});

	this.offsetStates = {};

	this.setOffset = function(offsetX, offsetY) {
		applyStyles(this.imageElement, {
			'background-position': '-' + (x + offsetX) + 'px -' + (y + offsetY) + 'px',
		});
	};

	this.defineState = function(stateName, offsetX, offsetY) {
		this.offsetStates[stateName] = {x: offsetX, y: offsetY};
	};

	this.setState = function(stateName) {
		if(this.offsetStates[stateName])
		{
			this.setOffset(this.offsetStates[stateName].x, this.offsetStates[stateName].y);
		}
	};
}

/*
 * Event Dispatcher
 */
function EventDispatcher()
{
	this.listeners = {};
}

/*
 * @param string eventName
 * @param function listener
 */
EventDispatcher.prototype.addEventListener = function(eventName, listener)
{
	if(!this.listeners.hasOwnProperty(eventName))
	{
		this.listeners[eventName] = [];
	}
	this.listeners[eventName].push(listener);
};

/*
 * @param string eventName
 * @param mixed data optional
 */
EventDispatcher.prototype.createEvent = function(eventName, data)
{
	return {
		type: eventName,
		target: this,
		data: data
	};
};

/*
 * @param string/object event string, or object created by EventDispatcher.prototype.createEvent
 * @param mixed data optional: if event is a string, used as second arg in EventDispatcher.prototype.createEvent
 */
EventDispatcher.prototype.dispatchEvent = function(event, data)
{
	var i, len;

	if(typeof event === 'string')
	{
		event = this.createEvent(event, data);
	}
	if(typeof this.listeners[event.type] === 'object')
	{
		for(i = 0, len = this.listeners[event.type].length; i < len; i++)
		{
			this.listeners[event.type][i].apply(this, [event]);
		}
	}
};

/*
 * @param string eventName
 * @param function listener
 */
EventDispatcher.prototype.removeEventListener = function(eventName, listener)
{
	var i;
	for(i = this.listeners[eventName].length - 1; i > -1; i--)
	{
		if(listener === this.listeners[eventName][i])
		{
			this.listeners[eventName].splice(i, 1);
		}
	}
};

/*
 * @param Element/EventDispatcher target
 * @param array eventNames
 */
EventDispatcher.prototype.bubbleFrom = function(target, eventNames)
{
	var self = this, propagateEvent, i, len;
	propagateEvent = function(event)
	{
		self.dispatchEvent(event);
	};
	for(i = 0, len = eventNames.length; i < len; i++)
	{
		target.addEventListener(eventNames[i], propagateEvent);
	}
};

/*global EventDispatcher, overload, document*/

/*
 * @param Ad ad
 * @param object options
 */
function Widget(ad, options)
{
	EventDispatcher.call(this);
	var eventName;

	// let defaults on descendants of Widget shine through
	this.defaults = overload({
		propagatedEvents: []
	}, this.defaults);
	this.ad = ad;

	// overload defaults with instance options
	this.options = overload(this.defaults, options);

	this.element = this.build();
	if(this.options.id)
	{
		this.element.id = this.options.id;
	}

	if(this.options.events)
	{
		for(eventName in this.options.events)
		{
			this.addEventListener(eventName, this.options.events[eventName]);
		}
	}

	this.bubbleFrom(this.element, this.options.propagatedEvents);

	if(this.options.parent)
	{
		this.options.parent.appendChild(this.element);
	}
}
Widget.prototype = new EventDispatcher();
Widget.prototype.constructor = Widget;
/*
 * overload .build() to create special element
 */
Widget.prototype.build = function()
{
	return document.createElement('DIV');
};

/*global document, Image, Widget, emptyNode, setAttribute, applyStyles, hasClass, setClass*/

function ImageWidget()
{
	this.defaults = {
		explicitNaturalWidth: true,
	};
	Widget.apply(this, arguments);
}

ImageWidget.prototype = new Widget();
ImageWidget.prototype.constructor = ImageWidget;

ImageWidget.prototype.build = function()
{
	var self = this, image, unassignedImage;

	// This assumes 2 images with the same SRC will load simultaneously.
	// One is needed outside the DOM, to measure, but one also must be returned
	// immediately for insertion into the DOM.
	unassignedImage = new Image();
	unassignedImage.addEventListener('load', function() {
		self.width = this.width;
		self.height = this.height;
		if(self.options.explicitNaturalWidth)
		{
			self.element.width = self.width;
			self.element.height = self.height;
		}
		self.dispatchEvent('load');
	});
	unassignedImage.src = this.options.src;

	image = new Image();
	image.src = this.options.src;

	// FIXME: do variables scoepd to this closure not get garbage collected because I'm returning image here?
	// I'm thinking specifically of unassignedImage.
	return image;
};

/*global document, Widget, ImageWidget, emptyNode, setAttribute, applyStyles, hasClass, setClass*/

function ButtonWidget()
{
	this.defaults = {
		explicitNaturalWidth: true,
		propagatedEvents: [
			'click',
			'mouseover',
			'mouseout'
		]
	};
	Widget.apply(this, arguments);
}

ButtonWidget.prototype = new Widget();
ButtonWidget.prototype.constructor = ButtonWidget;

ButtonWidget.prototype.build = function()
{
	var self = this, buttonWrap, numLoaded = 0, incrementLoaded, sharedImageEvents;

	buttonWrap = document.createElement('DIV');
	setClass(buttonWrap, this.ad.config.id + '_hover_wrap');
	applyStyles(buttonWrap, {visibility: 'hidden'});

	incrementLoaded = function()
	{
		numLoaded++;
		if(numLoaded === self.images.length)
		{
			self.dispatchEvent('load');
		}
	};

	this.addEventListener('load', function() {
		applyStyles(this.element, {visibility: ''});
		if(this.images[0].options.explicitNaturalWidth)
		{
			this.width = this.images[0].width;
			this.height = this.images[0].height;
			applyStyles(this.element, {
				width: this.width + 'px',
				height: this.height + 'px'
			});
		}
	});

	sharedImageEvents = {
		load: incrementLoaded
	};

	this.images = [
		new ImageWidget(this.ad, {
			src: this.options.idle,
			parent: buttonWrap,
			explicitNaturalWidth: this.options.explicitNaturalWidth,
			events: sharedImageEvents
		}),
		new ImageWidget(this.ad, {
			src: this.options.hover,
			parent: buttonWrap,
			explicitNaturalWidth: this.options.explicitNaturalWidth,
			events: sharedImageEvents
		})
	];

	return buttonWrap;
};

/*global document, Widget, emptyNode, setAttribute, applyStyles, hasClass, setClass*/

function TextWidget()
{
	this.defaults = {
		text: '',
		styles: {},
	};
	Widget.apply(this, arguments);
	this.setText(this.options.text);
	this.setStyles(this.options.styles);
}

TextWidget.prototype = new Widget();
TextWidget.prototype.constructor = TextWidget;

TextWidget.prototype.setText = function(message)
{
	this.element.innerHTML = message;
};

TextWidget.prototype.setStyles = function(styles)
{
	applyStyles(this.element, styles);
};

/*global document, Widget, TextWidget, ImageWidget, ButtonWidget, emptyNode, setAttribute, applyStyles, hasClass, setClass, addSeveralEventListeners, removeSeveralEventListeners*/

function VideoWidget()
{
	this.defaults = {
		controls: true,
		autoplay: true,
		autoMute: true,
		unmuteMessage: 'click for audio',
		autoplayDuration: 15,
		volume: 1,
		preload: true,
		mp4: null,
		webm: null,
		poster: null,
		propagatedEvents: [
			'pause',
			'canplay'
		]
	};
	Widget.apply(this, arguments);
	this.volume = 1;
	if(this.options.autoMute)
	{
		this.toggleMute(true);
	}
	if(this.options.autoplay)
	{
		this.toggleOpen(true);
	}
}

VideoWidget.prototype = new Widget();
VideoWidget.prototype.constructor = VideoWidget;

VideoWidget.statusEvents = 'progress pause playing timeupdate ended volumechange';
VideoWidget.intentionalUserEvents = 'mousedown mouseup click touchstart touchend';
VideoWidget.otherUserEvents = 'mousemove keydown keypress keyup';

VideoWidget.prototype.build = function()
{
	var self, videoWrap, sources;

	self = this;
	videoWrap = document.getElementById(this.ad.config.id + '_video_wrap');
	this.element = videoWrap; // Widget makes it so after returning, but VideoControlWidget needs it sooner

	this.videoElement = document.createElement('VIDEO');
	this.videoElement.id = this.ad.config.id + '_video';
	videoWrap.appendChild(this.videoElement);
	setClass(videoWrap, 'loading');

	setAttribute(this.videoElement, 'controls', false);
	setAttribute(this.videoElement, 'preload', this.options.preload);
	setAttribute(this.videoElement, 'autoplay', this.options.autoplay);

	addSeveralEventListeners(this.videoElement, VideoWidget.statusEvents, function(event){ self.onStatusChange(event); return true; });
	this.videoElement.addEventListener('canplay', function(event){ self.firstReady(event); }); // TODO: consider if this should be added here. How to have default listeners to events which are accessible from outside the VideoWidget?
	this.firstUserInteractionHandler = function(event){ self.firstUserInteraction(event); }; // preserve scope
	addSeveralEventListeners(videoWrap, VideoWidget.intentionalUserEvents, this.firstUserInteractionHandler);

	// let user interact with video without clicking through
	addSeveralEventListeners(videoWrap, this.ad.clickThroughEvents, function(event) {
		if(event.target !== self.videoElement)
		{
			self.ad.cancelClickThrough();
		}
	});

	sources = [];
	if(this.options.webm)
	{
		sources.push({mimeType:'video/webm', src: this.options.webm});
	}
	if(this.options.mp4)
	{
		sources.push({mimeType:'video/mp4', src: this.options.mp4});
	}

	this.load(sources);

	if(this.ad.config.video.show_poster && this.options.poster)
	{
		this.poster = new ImageWidget(this.ad, {
			id: this.ad.config.id + '_video_poster',
			src: this.options.poster,
			parent: videoWrap
		});
	}

	if(this.ad.config.play_button)
	{
		this.playButton = new ButtonWidget(this.ad, {
			parent: videoWrap,
			id: this.ad.config.id + '_play_btn',
			idle: this.ad.config.play_button.idle,
			hover: this.ad.config.play_button.hover,
			events: {
				click: function()
				{
					self.toggleOpen(true);
					self.play();
				},
				load: function()
				{
					applyStyles(this.element, {
						'marginLeft': '-' + ( this.width / 2 ) + 'px',
						'marginTop': '-' + ( this.height / 2 ) + 'px'
					});
				}
			}
		});
	}

	if(this.options.autoMute && this.options.unmuteMessage)
	{
		this.unmuteText = new TextWidget(this.ad, {
			parent: videoWrap,
			text: this.options.unmuteMessage,
			styles: {
				'font-family': 'sans-serif',
				color: 'white',
				position: 'absolute',
				'font-size': '20px',
				'text-shadow': '0 0.1em 0.2em black',
				top: '60%',
				width: '100%',
				'text-align': 'center'
			}
		});
	}
	else
	{
		this.unmuteText = null;
	}

	this.controls = new VideoControlWidget(this.ad, {
		video: this
	});
	videoWrap.appendChild(this.controls.element);

	return videoWrap;
};

/*
 * @param array sources
 */
VideoWidget.prototype.load = function(sources)
{
	var sourceElement, i;
	emptyNode(this.videoElement);
	for(i = 0; i < sources.length; i++)
	{
		if(typeof sources[i].mimeType === 'string')
		{
			sourceElement = document.createElement('SOURCE');
			sourceElement.setAttribute('type', sources[i].mimeType);
			sourceElement.setAttribute('src', sources[i].src);
			this.videoElement.appendChild(sourceElement);
		}
	}
};

/*
 * @param boolean forceValue
 * @returns boolean open
 */
VideoWidget.prototype.toggleOpen = function(forceValue)
{
	var willOpen;

	willOpen = (forceValue !== undefined ? forceValue : !this.isOpen());

	this.noteAnim(this.element, this.ad.config.dur);
	setClass(this.element, 'open', willOpen);
};

VideoWidget.prototype.noteAnim = function(element, duration)
{
	setClass(element, 'anim-done', false);
	clearTimeout(this.noteAnimTimeout);
	this.noteAnimTimeout = setTimeout(function(){
		setClass(element, 'anim-done');
	}, duration * 1000);
}

VideoWidget.prototype.isOpen = function()
{
	return hasClass(this.element, 'open');
};

VideoWidget.prototype.firstReady = function()
{
	var self = this;

	if(this.ready)
	{
		return;
	}
	this.ready = true;

	setClass(this.element, 'loading', false);

	if(this.options.autoplay && this.options.autoplayDuration)
	{
		this.autoPauseTimeout = setTimeout(function(){ self.autoPause(); }, this.options.autoplayDuration * 1000);
	}

};

VideoWidget.prototype.firstUserInteraction = function(event)
{
	var self = this;

	if(this.userHasInteracted)
	{
		return;
	}
	this.userHasInteracted = true;
	removeSeveralEventListeners(this.element, VideoWidget.intentionalUserEvents, this.firstUserInteractionHandler);
	event.stopImmediatePropagation();

	if(this.options.autoplay && this.options.autoplayDuration) {
		this.seek(0);
	}
	if(this.autoPauseTimeout) {
		clearTimeout(this.autoPauseTimeout);
	}
	if(this.options.autoMute) {
		this.disablingAutoMute = true;
		// if clicking the mousedown on the mute button is the first interaction, don't remute if they mouseup/click within 500ms
		setTimeout(function() {
			self.disablingAutoMute = false;
		}, 500);
		this.toggleMute(false);
	}
	this.dispatchEvent('first-user-interaction', event);
};

VideoWidget.prototype.autoPause = function()
{
	this.pause();
	this.toggleMute(false); // unmute for when the user clicks play
};
VideoWidget.prototype.onStatusChange = function(event)
{
	if(this.paused !== this.videoElement.paused)
	{
		this.paused = this.videoElement.paused;
		this.noteAnim(this.element, this.ad.config.dur);
		setClass(this.element, 'paused', this.paused);
	}
	if(event.type === 'ended')
	{
		this.toggleOpen(false);
	}
	if(event.type === 'volumechange' && this.unmuteText)
	{
		this.unmuteText.setStyles({display: this.isMuted() ? 'block' : 'none'});
		// TODO: remove unmuteText after first hidden
		if(!this.isMuted())
		{
			this.unmuteText = null;
		}
	}
	this.currentTime = this.videoElement.currentTime;
};

VideoWidget.prototype.play = function()
{
	this.videoElement.play();
};

VideoWidget.prototype.pause = function()
{
	this.videoElement.pause();
};

/*
 * @param number time
 */
VideoWidget.prototype.seek = function(time)
{
	this.videoElement.currentTime = time;
	return this.videoElement.currentTime;
};

/*
 * @param boolean forceValue
 * @returns boolean videoElement.muted
 */
VideoWidget.prototype.toggleMute = function(forceValue)
{
	this.videoElement.muted = (forceValue !== undefined ? forceValue : !this.isMuted());
	return this.isMuted();
};

/*
 * @returns boolean videoElement.muted
 */
VideoWidget.prototype.isMuted = function()
{
	return this.videoElement.muted;
};

//////////
// helpers
//////////

function formatSeconds(allSeconds)
{
	var minutes, seconds;

	minutes = Math.floor(allSeconds / 60).toString();
	seconds = Math.floor(allSeconds % 60).toString();
	if(seconds.length < 2)
	{
		seconds = '0' + seconds;
	}

	return minutes + ':' + seconds;
}

///////////////////////////////
// widget for all user controls
///////////////////////////////

function VideoControlWidget()
{
	var self = this, showHandler, hideHandler;

	this.defaults = {
		video: null,
		showReplay: true,
		showVolume: true,
		showTime: true,
		visibleAtStart: false,
		alwaysVisible: false,
		timeout: 2000,
		spritesUrl: 'https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/automated_video_ads/assets/video_control_sprites.png',
		spriteSize: 30
	};
	Widget.apply(this, arguments);

	this.options.video.element.appendChild(this.element);

	this.isVisible = true;

	if(this.options.visibleAtStart || this.options.alwaysVisible)
	{
		this.show(this.options.alwaysVisible);
	}
	else
	{
		this.hide();
	}

	if(!this.options.alwaysVisible)
	{
		showHandler = function() {
			self.show();
		};
		hideHandler = function() {
			self.hide();
		};
		mouseoutIntent(
			this.options.video.element,
			showHandler,
			hideHandler,
			'mousemove'
		);
		// also prolong show timeout if clicking buttons
		addSeveralEventListeners(this.options.video.element, 'mousedown click touchstart touchend', showHandler);
	}
}
VideoControlWidget.prototype = new Widget();
VideoControlWidget.prototype.constructor = VideoControlWidget;

VideoControlWidget.prototype.build = function()
{
	// assign this.element early, so that controls can append themselves
	this.element = document.createElement('DIV');

	var controlHeight = '30px';

	applyStyles(this.element, {
		position: 'absolute',
		left: '0',
		bottom: '0',
		color: 'white',
		width: '100%',
		height: controlHeight,
		"line-height": controlHeight,
		"background-color": 'rgba(0,0,0,0.5)',
		"text-align": 'center',
		"font-size": '12px',
	});

	this.play = this.createPlayButton(this);
	this.time = this.createTimeDisplay(this);
	this.volume = this.createVolumeControl(this);

	// TODO: move these styles into an inline style block on the ad
	applyStyles(this.play.element, {
		position: 'absolute',
		width: controlHeight,
		height: controlHeight,
		left: '4px',
		bottom: '0'
	});
	applyStyles(this.time.element, {
		position: 'absolute',
		height: controlHeight,
		right: '40px',
		bottom: '0',
		"text-align": 'right'
	});
	applyStyles(this.volume.element, {
		position: 'absolute',
		width: controlHeight,
		height: controlHeight,
		right: '4px',
		bottom: '0'
	});

	return this.element;
};

VideoControlWidget.prototype.createGenericControl = function(controlWidget, sprite)
{
	var control = {};

	if(sprite)
	{
		control.sprite = sprite;
		control.element = sprite.element;
	}
	else
	{
		control.element = document.createElement('DIV');
	}

	controlWidget.element.appendChild(control.element);

	return control;
};

//////////////////////
// individual controls
//////////////////////

VideoControlWidget.prototype.createPlayButton = function(controlWidget)
{
	var self, sprite, control;

	self = this;
	sprite = new Sprite(
		this.options.spritesUrl,
		this.options.spriteSize,
		this.options.spriteSize,
		0,
		0
	);
	control = this.createGenericControl(controlWidget, sprite);

	control.sprite.defineState('paused', 0, 0);
	control.sprite.defineState('playing', this.options.spriteSize * 1, 0);
	control.sprite.defineState('ended', this.options.spriteSize * 2, 0);

	control.setState = function(state) {
		if(state === control.state)
		{
			return;
		}
		control.state = state;
		control.sprite.setState(state);
	};

	control.setState('paused');

	this.options.video.videoElement.addEventListener('pause', function() {
		control.setState('paused');
	});
	this.options.video.videoElement.addEventListener('playing', function() {
		control.setState('playing');
	});
	this.options.video.videoElement.addEventListener('ended', function() {
		control.setState('ended');
	});

	control.element.addEventListener('click', function() {
		switch(control.state)
		{
			case 'paused':
				self.options.video.play();
				break;
			case 'ended':
				self.options.video.toggleOpen(true);
				setTimeout(function() {
					self.options.video.play();
				}, 10);
			default: // 'playing', etc.
				self.options.video.pause();
		}
	});

	return control;
};

VideoControlWidget.prototype.createTimeDisplay = function(controlWidget)
{
	var control = this.createGenericControl(controlWidget);

	control.duration = 0;

	// methods
	control.update = function(time, duration)
	{
		var text = formatSeconds(time);

		if(duration !== undefined)
		{
			this.duration = duration;
		}
		text += ' / ' + formatSeconds(this.duration);

		control.element.innerHTML = text;
	};

	control.update(0);

	this.options.video.videoElement.addEventListener('timeupdate', function(event) {
		control.update(event.target.currentTime, event.target.duration);
	});

	return control;
};

VideoControlWidget.prototype.createVolumeControl = function(controlWidget)
{
	var self = this;
	sprite = new Sprite(
		this.options.spritesUrl,
		this.options.spriteSize,
		this.options.spriteSize,
		this.options.spriteSize*3, // x coordinate
		0 // y coordinate
	);
	control = this.createGenericControl(controlWidget, sprite);

	control.sprite.defineState('mute', 0, 0);
	control.sprite.defineState('sound', this.options.spriteSize * 1, 0);

	// methods
	control.update = function(muted)
	{
		if(muted === undefined)
		{
			muted = self.options.video.isMuted();
		}
		control.sprite.setState(muted ? 'mute' : 'sound');
	};

	control.update(self.options.video.options.autoMute);

	control.element.addEventListener('click', function(event) {
		if(self.options.video.disablingAutoMute)
		{
			return;
		}
		self.options.video.toggleMute();
		control.update();
	});

	self.options.video.videoElement.addEventListener('volumechange', function() {
		control.update();
	});

	return control;
};

///////////////////////////
// methods for all controls
///////////////////////////

VideoControlWidget.prototype.show = function(keepVisible)
{
	var self = this;

	clearTimeout(this.timeoutId);

	if(!this.isVisible)
	{
		// TODO: fade in
		this.element.style.display = 'block';

	}

	if(keepVisible !== true)
	{
		this.timeoutId = setTimeout(function() {
			self.hide();
		}, this.options.timeout);
	}

	this.isVisible = true;
};

VideoControlWidget.prototype.hide = function()
{
	clearTimeout(this.timeoutId);
	if(!this.isVisible)
	{
		return;
	}
	this.isVisible = false;
	// TODO: fade out
	this.element.style.display = 'none';
};

/*global document, EventDispatcher, Widget, ImageWidget, ButtonWidget, VideoWidget, overload, deserialize, makeTemplate, emptyNode, setAttribute, applyStyles, hasClass, setClass, addSeveralEventListeners*/

/**
 * Ad framework
 * @param DOMElement container
 * @param Object config
 */
function Ad(container, config)
{
	var layout, self = this;

	this.container = container;
	this.config = overload(
		deserialize(this.container.getAttribute('data-init')),
		config
	);
	this.config.id = this.container.getAttribute('id');

	layout = makeTemplate(this.config.layout);

	this.container.innerHTML = layout(this.config);

	applyStyles(this.container, { // string keys, to resist mangling in script minification
		'width': (this.config.w - this.config.bw * 2) + 'px',
		'height': (this.config.h - this.config.bw * 2) + 'px',
		'border': this.config.bw + 'px solid #' + this.config.bc
	});

	addSeveralEventListeners(this.container, this.clickThroughEvents, function(event) {
		self.clickThrough();
	});

	this.build();
}

Ad.prototype = new EventDispatcher();
Ad.prototype.constructor = Ad;
Ad.prototype.version = '0.2.2';
Ad.prototype.clickThroughEvents = 'click touchend';

Ad.prototype.build = function()
{
	var self, ctaConfig;

	self = this;

	if(this.config.hasOwnProperty('video'))
	{
		this.video = new VideoWidget(this, {
			poster: this.config.video.poster,
			mp4: this.config.video.mp4,
			webm: this.config.video.webm
		});
	}
};

Ad.prototype.clickThrough = function()
{
	var self = this;
	if(this.config.advertiser_url && !this.cancelNextClickThrough)
	{
		this.clickThroughTimeout = setTimeout(function() {
			window.open(self.config.advertiser_url);
			self.clickThroughTimeout = null;
		}, 0);
	}
};

Ad.prototype.cancelClickThrough = function()
{
	var self = this;
	if(this.clickThroughTimeout)
	{
		clearTimeout(this.clickThroughTimeout);
	}
	else
	{
		this.cancelNextClickThrough = true;
		clearTimeout(this.cancelClickThroughTimeout);
		this.cancelClickThroughTimeout = setTimeout(function() {
			self.cancelNextClickThrough = false;
		}, 1);
	}
};

	return Ad;

}());
