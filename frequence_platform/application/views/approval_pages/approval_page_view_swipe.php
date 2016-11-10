<style>
.leaderboard
{
	top:60px;
}
.landing_page
{
	top:700px;
}
</style>
<div class="container">
	<iframe class="leaderboard approval_iframe" id="iframe1" src="/crtv/get_ad/<?php echo $version_id; ?>/728x90" style="overflow:visible;  width: 728px; height: 600px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>
	<div class="landing_page">
	  
		<?php
		   if ($is_show_landing_page && isset($landing_page))
		   {
			   echo "<h4>Landing Page: <a target=\"_blank\" href=".$landing_page.">".$landing_page."</a> </h4>";
		   }
		   ?>
	  
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
<script type="text/javascript">
var iframe1 = document.getElementById('iframe1');

iframe1.onload = function (){
	if(this.contentDocument) iframe1.doc = iframe1.contentDocument;
	else if(iframe1.contentWindow) iframe1.doc = iframe1.contentWindow.document;
	
	var css = '.adbox{float:left;}',
		header = iframe1.doc.getElementsByTagName('head')[0],
		style = document.createElement('style');
		
	style.type = 'text/css';
	if (style.styleSheet){
	  style.styleSheet.cssText = css;
	} else {
	  style.appendChild(document.createTextNode(css));
	}

	header.appendChild(style);
}
</script>
