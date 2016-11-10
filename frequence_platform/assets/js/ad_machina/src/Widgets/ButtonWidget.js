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
