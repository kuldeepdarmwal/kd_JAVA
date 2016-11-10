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
