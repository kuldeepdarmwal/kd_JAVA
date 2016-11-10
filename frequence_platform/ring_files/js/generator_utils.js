function deploy_dashboard() {
	document.getElementById('body_header').innerHTML = "Local Ad Planner Dashboard";
	document.getElementById('ghost_title').innerHTML = "Local Ad Planner Dashboard";
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.open("GET", "/proposal_builder/get_lap_summary/", false);
	xmlhttp.send();
	document.getElementById("body_content").innerHTML=xmlhttp.responseText;
	
}
