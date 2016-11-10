<style type="text/css">
	#{{id}}
	{
		background-color: {{bg_color}};
		background-image: url({{bg_img}});
		background-position: center;
		position: relative;
		overflow: hidden;
		cursor: pointer;
	}
	#{{id}} *
	{
		-ms-transition-property: top, left, right, bottom, transform, opacity;
		-ms-transition-duration: {{dur}}s;
		transition-property: top, left, right, bottom, transform, opacity;
		transition-duration: {{dur}}s;
	}

	/* all buttons */
	.{{id}}_hover_wrap
	{
		position: relative;
	}
	.{{id}}_hover_wrap:hover > img:nth-child(2n+1)
	{
		visibility: hidden;
	}
	.{{id}}_hover_wrap > img:nth-child(2n+2)
	{
		visibility: hidden;
		position: absolute;
		left: 0;
		top: 0;
	}
	.{{id}}_hover_wrap:hover > img:nth-child(2n+2)
	{
		visibility: visible;
	}

	/* video */
	#{{id}}_video_wrap
	{
		position: absolute;
		top: 40px;
		left: 0;
		width: {{w}}px;
		height: 169px;
	}
	#{{id}}_video_wrap video
	{
		position: relative;
		width: 100%;
		height: 100%;
	}
	#{{id}}_video_wrap.loading #{{id}}_play_btn,
	#{{id}}_video_wrap.anim-done:not(.paused) #{{id}}_play_btn
	{
		z-index: -1;
	}
	#{{id}}_play_btn
	{
		position: absolute;
		top: 50%;
		left: 50%;
		opacity: 0;
		-ms-transform: scaleX(2) scaleY(2);
		transform: scaleX(2) scaleY(2);
	}
	#{{id}}_video_wrap.paused #{{id}}_play_btn
	{
		opacity: 1;
		-ms-transform: scaleX(1) scaleY(1);
		transform: scaleX(1) scaleY(1);
	}
	#{{id}}_video_wrap.paused #{{id}}_play_btn:hover
	{
		-ms-transform: scaleX(1.1) scaleY(1.1);
		transform: scaleX(1.1) scaleY(1.1);
	}
	#{{id}}_video_wrap > img /* video poster */
	{
		position: absolute;
		top: 0;
		left: 0;
		max-width: 100%;
		max-height: 100%;
		display: block;
		margin: 0 auto;
	}
	#{{id}}_video_wrap.open > img
	{
		opacity: 0;
	}
	#{{id}}_video_wrap.open.anim-done > img /* video poster */
	{
		display: none;
	}
</style>
<div id="{{id}}_video_wrap"></div>
