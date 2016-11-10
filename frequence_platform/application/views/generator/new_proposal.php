
<link href='https://fonts.googleapis.com/css?family=Oxygen' rel='stylesheet' type='text/css'>
<link rel="stylesheet" type="text/css" href="<?php echo base_url('css/generator/generator.css'); ?>"/>   
<script type="text/javascript" src="<?php echo base_url('js/generator/generator.js');?>" ></script>

<script type="text/javascript">
	function showGenerator(){
		document.getElementById("body_header").innerHTML="Create/Edit Proposal";
		
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.open("GET", "/smb/get_media_targeting_body", false);
		xmlhttp.send();
		document.getElementById("body_content").innerHTML=xmlhttp.responseText;
		document.getElementById("sliderHeaderContent").innerHTML="Site Details";

		update_site_pack("");
		
	tracker();
	}
</script>






