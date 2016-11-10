/*global window,$*/
(function() {
	var
		setupSticky,
		options,
		scrollHandler,
		stickyTopOffset,
		resizeHandler;

	setupSticky = function(newOptions) {
		options = $.extend({
			forceStickyWidth: false,
			forceOffset: 0
		}, newOptions);
		$(window).on('resize', resizeHandler);
		$(window).on('scroll', scrollHandler);
		resizeHandler(false);
		scrollHandler();
	};

	resizeHandler = function(updatePosition) {
		var navbarOffset = $(".navbar-fixed-top").css('position') === 'fixed' && $('.navbar-fixed-top').height() || 0;
		stickyTopOffset = navbarOffset + options.forceOffset;
		if(updatePosition !== false)
		{
			setStickyPosition();
		}
	};

	setStickyPosition = function() {
		$('.sticky').each(function() {
			var element, pinnedCSS;

			element = $(this);

			pinnedCSS = {
				top: stickyTopOffset + 'px'
			};
			if(options.forceStickyWidth) {
				pinnedCSS.width = element.parent().width() + 'px';
			}
			element.css(pinnedCSS);
		});
	};

	scrollHandler = function() {
		$('.sticky').each(function() {
			var element, offset, stickyData;

			element = $(this);
			stickyData = element.data('stickyData');

			if (typeof stickyData === 'undefined') {
				offset = element.offset();
				stickyData = {
					offset: offset,
					width: element.width(),
					height: element.height(),
					top: element.css('top'),
					position: element.css('position'),
					placeholder: null
				};
				element.data('stickyData', stickyData);
			}

			if ($(document).scrollTop() > stickyData.offset.top - stickyTopOffset) {
				if (stickyData.placeholder === null) {
					stickyData.placeholder = element.clone()
						.removeAttr('id')
						.addClass('stickyPlaceholder')
						.removeClass('sticky')
						.insertBefore(element);

					element.addClass('stickyPinned');
					setStickyPosition();
					element.data('stickyData', stickyData);
				}
			} else {
				if (stickyData.placeholder !== null) {
					element.removeClass('stickyPinned').css({
						top: stickyData.top
					});
					stickyData.placeholder.remove();
					stickyData.placeholder = null;
					element.data('stickyData', stickyData);
				}
			}
		});
	};

	window.setupSticky = setupSticky;

}());
