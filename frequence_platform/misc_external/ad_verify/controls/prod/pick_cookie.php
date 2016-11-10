<script>

function run_script()
{
    var dropdown = document.getElementById("campaigns");
    var campaign_id = dropdown.options[dropdown.selectedIndex].value;
    window.location = "http://adverify.vantagelocalstage.com/cookie_monster.php?id="+campaign_id;
 
}
</script>


<td>Campaign: </td>
<td><select id="campaigns" name="campaigns">
    <option>---SELECT A CAMPAIGN---</option>
    <?php
     //$db = mysql_connect('10.179.2.54', 'vlproduser', 'L0cal1s1n!');
	 //mysql_select_db('vantagelocal_prod');
	$db = mysql_connect('brandcdn-prod.cgy3zqnvq7mi.us-west-1.rds.amazonaws.com', 'frequence_admin', '!Ds6AC0SnulgHe$4');
    mysql_select_db('brandcdn');	
    $active_campaign_query = "                                                                                                                                                             SELECT c.Name as Name, a.Name as Business, c.LandingPage as Page, c.id as id                                                                                                         
      FROM Campaigns c LEFT JOIN Advertisers a ON (c.business_id = a.id) JOIN AdGroups ad ON (c.id = ad.campaign_id)                                                                       
                                                                                                                                                                                            
                                                                                                                                                                                           
      WHERE c.ignore_for_healthcheck = 0 AND c.id IN (SELECT campaign_id FROM tags where tag_type = 1)                                                                                                                                                   
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
