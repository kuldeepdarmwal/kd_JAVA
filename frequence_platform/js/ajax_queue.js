/*global window, $ */
(function() {

	function AjaxQueue(settings) {
		settings = $.extend({
			concurrency: 1 // warning: concurrency greater than 1 allows requests to return out of order
		}, settings);

		var stopped = false, next, queue = [], pending = [];

		next = function() {
			while(queue.length && pending.length < settings.concurrency)
			{
				pending.push($.ajax(queue.shift()));
			}
		};

		// add an ajax operation to the queue: options are identical to those passed to jQuery.ajax()
		this.push = function(options) {
			var given_complete = options.complete;

			options.complete = function() {
				pending.splice(pending.indexOf(this), 1);
				if(typeof given_complete === 'function')
				{
					given_complete.apply(this, arguments);
				}
				if(!stopped)
				{
					next();
				}
			};

			queue.push(options);

			next();

			return this;
		};

		// get the queue array, for counting or other purposes
		this.get = function() {
			return this.getQueued();
		};

		this.getQueued = function() {
			return queue;
		};

		this.getPending = function() {
			return pending;
		};

		this.stop = function(shouldAbortPending) {
			stopped = true;

			if(shouldAbortPending)
			{
				for(var i = 0; i < pending.length; i++)
				{
					pending[i].abort();
				}
			}

			return this;
		};

		this.start = function() {
			if(stopped)
			{
				stopped = false;
				next();
			}
			return this;
		};

		this.clear = function() {
			this.stop(true);
			queue = [];

			return this;
		};

		// expose scope variables
		this.container = null; // deprecated

	}

	window.AjaxQueue = AjaxQueue;

}());

// usage: var queue = new AjaxQueue(); queue.push({ url:'http://example.com/', ... }).push({ url:'/stuff', ... });
// reqeusts will run immediately, in order
