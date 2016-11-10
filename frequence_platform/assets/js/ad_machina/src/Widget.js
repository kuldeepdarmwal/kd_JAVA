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
