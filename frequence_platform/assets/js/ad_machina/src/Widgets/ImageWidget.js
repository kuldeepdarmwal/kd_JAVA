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
