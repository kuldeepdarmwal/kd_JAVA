/*global window, document, $, segment_size, showFeedback */

$(function(){

	var body = $('.video_gallery');

	function createAutoPlayVideo(element)
	{
		var mp4Source, webmSource, video;

		video = document.createElement('video');
		video.setAttribute('controls', 'true');
		video.setAttribute('autoplay', 'true');

		webmSource = document.createElement('source');
		webmSource.src = element.attr('data-webm');
		mp4Source = document.createElement('source');
		mp4Source.src = element.attr('data-mp4');

		video.appendChild(mp4Source);
		video.appendChild(webmSource);

		element.prepend(video);

		return video;
	}

	function stopOtherVideos(activeContainer)
	{
		$('li', body).not(activeContainer).each(function() {
			var video = $('video', this);
			if(video.length)
			{
				$(this).removeClass('active');
				video.remove();
				$('img', this).show();
			}
		});
	}

	function playVideo(activeContainer)
	{
		var videoMatches, video_element;

		videoMatches = $('video', activeContainer);
		if(videoMatches.length)
		{
			video_element = videoMatches[0];
			video_element.currentTime = 0;
			video_element.play();
		}
		else
		{
			activeContainer.addClass('active');
			video_element = createAutoPlayVideo(activeContainer);
		}
		video_element.volume = 0.1;
		$('img', activeContainer).hide();
	}

	body
		.on('mouseover', 'li.spot_video:not(.active)', function(event) {
			var
				container = $(event.target).closest('li'),
				timeout, hoverPlayTime = 500; // TODO: touch-enabled interaction (no hover required)

			timeout = setTimeout(function() {
				stopOtherVideos(container);
				playVideo(container);
			}, hoverPlayTime);

			$(this).on('mouseout', function() {
				clearTimeout(timeout);
			});
		})
		.on('click', 'li', function(event) {
			var element = $(event.target).closest('li')[0];
			var video_id = element.attributes['data-video-id'].value;
			window.location.href = '/vab/builder/' + video_id;
		});

	$('.video_gallery select').on('change', function() {
		window.location.href = '/vab/videos/' + $(this).val();
	});

	var path_parts = window.location.pathname.split('/');
	var account_id = path_parts[3] || 'all'; // account id, or "all" if none
	infiniteScroll({
		pageSize: segment_size, // global on views/creative_upload/videos.php
		url: '/vab/videos/'+account_id+'/{{page}}',
		container: 'ul.video_list',
		scrollBottomBuffer: 1000,
		container: 'ul.video_list'
	});

	setupSticky({
		forceStickyWidth: true
	});

});
