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
