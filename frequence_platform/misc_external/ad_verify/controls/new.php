<script>

function run_script()
{
    var dropdown = document.getElementById("campaigns");
    var campaign_id = dropdown.options[dropdown.selectedIndex].value;
//    alert(campaign_id);
    
    var request = new XMLHttpRequest;
    request.open("POST", "execute_script.php", true);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    var params = "campaign_id="+campaign_id;
    request.send(params);
    
    document.getElementById("start_button").style.visibility = "hidden";
    document.getElementById("submitted").style.visibility = "visible";

}

function stop_script()
{
    var rand_id = document.getElementById("kill_id").value;
  

    var kill_request = new XMLHttpRequest;

    kill_request.open("POST", "kill_script.php", true);
    kill_request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    var params = "kill_id="+rand_id;
//   alert(params);                                                                                      
    kill_request.send(params);

   alert("Terminating job, please wait...");
    document.getElementById("kill_button").style.visibility = "hidden";
    window.setTimeout(function(){document.getElementById("kill_button").style.visibility = "visible";} , 8000);


}

</script>

 
<html>
<head><title>SCREENSHOT BOT v.9</title></head>

<body>
<h2>Screenshot Bot</h2>

<table>
<tr>
<td>Campaign: </td>
<td><select id="campaigns" name="campaigns">
    <option>---SELECT A CAMPAIGN---</option>
    <?php
    $db = mysql_connect('db.vantagelocaldev.com', 'vldevuser', 'L0cal1s1n!');
    mysql_select_db('vantagelocal_dev');
    $active_campaign_query = "                                                                           
       SELECT c.Name as Name, a.Name as Business, c.LandingPage as Page, c.id as id                      
      FROM Campaigns c LEFT JOIN Advertisers a ON (c.business_id = a.id) JOIN AdGroups ad ON (c.id = ad.campaign_id)                                                                                             
                                                                                                         
      WHERE c.ignore_for_healthcheck = 0                                                                 
      GROUP BY c.id ORDER BY a.Name ASC";
    $response = mysql_query($active_campaign_query);
while($row = mysql_fetch_assoc($response))
{
    echo "<option value='".$row['id']."'>".$row['Business']." - ".$row['Name']."</option>";
}
?>
</select>
<button name="go" value="Run!" onClick="run_script();" id="start_button">Run!</button>
</table>
<br>
<div id="submitted" style="visibility: hidden;"><strong>REQUEST SUBMITTED!</strong> - You can find logs detailing bot jobs <a href="/screenshots/logs/">here</a> - <a href="/screenshots/">Gallery</a></div>

<br>
<table>
<td>Terminate a job: </td>
<td><input type="text" size="5" id="kill_id"></td>
<td><button name="stop" value="STOP!" onClick="stop_script();" id="kill_button">STOP!</button></td>
</table>
