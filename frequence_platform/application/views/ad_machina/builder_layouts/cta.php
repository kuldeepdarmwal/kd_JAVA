<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" type="text/css">
<link href='https://fonts.googleapis.com/css?family=Open+Sans+Condensed:700' rel='stylesheet' type='text/css'>
<style type="text/css">
	#{{id}}
	{
		font-family: 'Open Sans', sans-serif;
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

	#{{id}} img
	{
		max-width: 100%;
		max-height: 100%;
	}

	/* all buttons */
	.{{id}}_hover_wrap
	{
		position: relative;
	}
	.{{id}}_hover_wrap > img
	{
		display: block;
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
		left: 30px;
		top: 40px;
		width: 240px;
		height: 135px;
	}
	#{{id}}_video_wrap video
	{
		width: 100%;
		height: 100%;
	}
	#{{id}}_video_wrap > img
	{
		position: absolute;
		top: 0;
		left: 0;
		width: {{w}}px;
		height: 169px;
	}
	#{{id}}_video_wrap.open > img
	{
		display: none;
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

	/* layout */
	#{{id}}_header
	{
		font-family: 'Open Sans Condensed', sans-serif;
		font-weight: 700;
		position: absolute;
		top: 0px;
		left: 0px;
		right: 0px;
		overflow: hidden;
		padding: 4px;
		height: 40px;
		box-sizing: border-box;
	}
	#{{id}}_logo,
	#{{id}}_header_text
	{
		float: left;
		height: 100%;
		width: 50%;
	}
	#{{id}}_logo_text,
	#{{id}}_header_text
	{
		box-sizing: border-box;
	}
	#{{id}}_logo_text
	{
		height: 100%;
		color: {{logo_color}};
		font-size: {{logo_text_size}};
		line-height: {{logo_line_height}};
	}
	.{{id}}_logo_file_yes #{{id}}_logo_text
	{
		display: none;
	}
	.{{id}}_logo_file_no #{{id}}_logo_img
	{
		display: none;
	}
	#{{id}}_header_text
	{
		float: right;
		text-align: right;
		color: {{header_color}};
		font-size: {{header_text_size}};
		line-height: {{header_line_height}};
	}
	#{{id}}_footer
	{
		position: absolute;
		bottom: 1px;
		left: 0;
		width: 100%;
	}
	#{{id}}_cta_btn
	{
		font-family: 'Open Sans Condensed', sans-serif;
		font-weight: 700;
		position: absolute;
		bottom: 4px;
		height: 30px;
		left: 50%;
		padding: 0 0.6em;
		-ms-transform: translateX(-50%);
		transform: translateX(-50%);
		white-space: pre-wrap;
		color: {{cta_color}};
		background-color: {{cta_bg_color}};
		border-radius: {{cta_radius}};
		border: {{cta_border}};
		font-size: {{cta_text_size}};
		line-height: {{cta_line_height}};
		box-shadow: 0px 5px 10px rgba(0,0,0,0.5);
	}
	#{{id}}_cta_btn:hover
	{
		-ms-transform: translateX(-50%) scaleX(1.1) scaleY(1.1);
		transform: translateX(-50%) scaleX(1.1) scaleY(1.1);
	}
	#{{id}}_footer_text
	{
		color: {{footer_color}};
		font-size: {{footer_text_size}};
		line-height: {{footer_line_height}};
		white-space: pre-wrap;
		text-align: center;
		position: absolute;
		width: 100%;
		height: 30px;
		bottom: 40px;
		left: 0;
		box-sizing: border-box;
		padding: 0 4px 4px;
	}
</style>
<div id="{{id}}_header">
	<div id="{{id}}_header_text">{{header_text}}</div>
	<div id="{{id}}_logo" class="{{id}}_logo_file_{{logo_is_file}}">
		<img id="{{id}}_logo_img" src="{{logo_img}}" alt="{{logo_text}}">
		<div id="{{id}}_logo_text">{{logo_text}}</div>
	</div>
</div>
<div id="{{id}}_video_wrap"></div>
<div id="{{id}}_footer">
	<div id="{{id}}_footer_text">{{footer_text}}</div>
	<div id="{{id}}_cta_btn">{{cta_text}}</div>
</div>
