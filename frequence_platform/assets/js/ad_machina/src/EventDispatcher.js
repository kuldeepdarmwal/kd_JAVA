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
