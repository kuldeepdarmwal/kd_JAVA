		<!--link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css"-->
		<link rel="stylesheet" type="text/css" href="/assets/sticky/sticky.css?v=<?php echo CACHE_BUSTER_VERSION; ?>">
		<link rel="stylesheet" type="text/css" href="/css/image_scraper.css?v=<?php echo CACHE_BUSTER_VERSION; ?>">
		<script type="text/javascript" src="/assets/sticky/sticky.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
		<script type="text/javascript" src="/js/image_scraper.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
		<script type="text/template" id="template_each_image">
			<div class="found-image" title="FROM {{page}}">
				<img src="{{url}}">
				<div class="info">{{width}}x{{height}}</div>
			</div>
		</script>
		<div class="container">
			<h2>Image Scraper <small>quickly get images from (almost) any web site</small></h2>
			<form id="scrape_request" class="scrape_request container sticky" action="/image_scraper/crawl">
				<label>URL: <input type="text" name="url"></label>
				<label>linked pages: <input type="checkbox" name="depth" value="1"></label>
				<label>
					<div class="transparency-option light-transparency selected"></div>
					<div class="transparency-option medium-transparency"></div>
					<div class="transparency-option dark-transparency"></div>
				</label>
				<input type="submit" value="Get Images">
			</form>
			<content>
				<p id="feedback_container" class="feedback"></p>
				<div id="image_results" class="results light-transparency"></div>
			</content>
		</div>
<!-- bootstrap is needed for the main navigation dropdown menus -->
<script src="/blueimp/js/bootstrap.min.js"></script>
	</body>
</html>
