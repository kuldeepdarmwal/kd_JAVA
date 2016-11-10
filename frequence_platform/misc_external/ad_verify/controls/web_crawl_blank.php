<html>
<head><title>Web Crawler</title>
<script>

function run_script()
{
    var domains = document.getElementById("domain_box").value;    
    var request = new XMLHttpRequest;
    request.open("POST", "execute_web_crawler.php", true);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    var params = "domain="+domains;
    request.send(params);
    var butan = document.getElementById("go_button");
    butan.disabled = true;
}

function kill_script()
{
    var kill_id = document.getElementById("kill_box").value;
    var request = new XMLHttpRequest;
    request.open("POST", "crawl_kill.php", true);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    var params = "kill_id="+kill_id;
    request.send(params);
    var butan = document.getElementById("kill_button");
    butan.disabled = true;
    window.setTimeout(function(){butan.disabled = false;}, 5000);

}

</script>
</head>

<body>
<h2>Web Crawler</h2>
<strong>Domains to visit:</strong>
<table>
<tr>
<td>http://</td><td><textarea rows="3" cols="30" id="domain_box"></textarea></td>
</tr>
</table>
<button name="stop" value="STOP!" onClick="run_script();" id="go_button">GO!</button>
<br><br><a href="/screenshots/logs/crawler/">Logs of crawler jobs</a>

<br><br>
<table>
    <tr><td>Kill ID:</td><td><input type="text" size="8" id="kill_box"></td><td><button name="stop" value="STOP!" onClick="kill_script();" id="kill_button">STOP!</button>
</body>

</html>
