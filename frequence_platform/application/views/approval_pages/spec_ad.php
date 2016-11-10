
<style>

#specad_wrapper {
	background-image:url(https://s3.amazonaws.com/brandcdn-assets/spec_ad/placement.jpg);
	height:884px;
	width:1236px;
	position:relative;
}
#specad_holder{
	position: absolute;
	width: 300px;
	height: 250px;
	left: 879px;
	top:277px;
	background-color:#f5f5f5;
}




</style>

<div class="container">
	<div id="specad_wrapper">
		<div id="specad_holder">
			<iframe  id="the_ad" src="/crtv/get_ad/<?php echo $version_id; ?>/300x250" style="overflow:visible;  width: 300px; height: 250px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>
		</div>
	</div>
	</div>

<?php
if(isset($tags) && $tags)
{
	foreach($tags as $tag_to_display)
	{
		echo $tag_to_display." "; 
	}
}
?>


<script>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
     m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
     })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', 'UA-22820832-3', 'brandcdn.com');
ga('send', 'pageview');

</script>

