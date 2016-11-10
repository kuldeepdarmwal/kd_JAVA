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
