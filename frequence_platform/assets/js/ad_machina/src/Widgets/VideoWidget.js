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
