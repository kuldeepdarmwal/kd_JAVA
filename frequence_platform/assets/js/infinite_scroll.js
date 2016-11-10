/*global window,document,$*/
(function() {
	window.infiniteScroll = function(newOptions) {
		var options, container, load_next, scrollStep, segment_index, segment_loading_spinner, loading_segment;

		options = $.extend({
			container: null,
			url: null,
			pageSize: 120,
			itemSelector: 'li',
			scrollBottomBuffer: 500,
			scrollContainer: window // setting this to anything else is untested -CL
		}, newOptions);

		container = $(options.container);

		segment_index = 0;
		segment_loading_spinner = $('<div class="segment_loading_spinner"><i class="uploading-file-status fa fa-refresh fa-spin"></i></div>');

		load_next = function() {
			var url, finished;

			segment_index++;
			loading_segment = true;

			url = options.url.replace('{{page}}', segment_index);

			$.ajax({
				url: url,
				success: function(data) {
					var items;
					finished = true;
					if(data) {
						items = $(data);
						if(items.filter(options.itemSelector).length == options.pageSize)
						{
							finished = false;
						}
						container.append(items);
					}
				},
				error: function() {
					finished = true;
				},
				complete: function() {
					if(finished) {
						$(options.scrollContainer).off('scroll', scrollStep);
					}
					loading_segment = false;
					segment_loading_spinner.remove();
				}
			});
			container.append(segment_loading_spinner);
		};

		scrollStep = function() {
			if(!loading_segment && $(document).scrollTop() + options.scrollContainer.innerHeight > document.body.scrollHeight - options.scrollBottomBuffer) {
				load_next();
			}
		};

		$(options.scrollContainer).on('scroll', scrollStep);
		scrollStep();
	};
}());
