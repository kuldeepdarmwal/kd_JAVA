<html>
<head><title>Add a site</title>
<body>
<?php
//$db = mysql_connect('10.179.2.54', 'vlproduser', 'L0cal1s1n!');
//mysql_select_db('vantagelocal_prod');
$db = mysql_connect('brandcdn-prod.cgy3zqnvq7mi.us-west-1.rds.amazonaws.com', 'frequence_admin', '!Ds6AC0SnulgHe$4');
mysql_select_db('brandcdn');	

?>

<script>
function blank_checkboxes()
{

    var checkboxes = document.getElementsByName('category_checkbox');
    var cats = [];
    for(i = 0; i < checkboxes.length;i++)
    {
        checkboxes[i].checked = false;
    }
    


}

function check_boxes(box){
    var campaign_id = box.options[box.selectedIndex].value;
   
    var request = new XMLHttpRequest;
    request.open("GET", "get_categories.php?c_id="+campaign_id, false);
    var params = "campaign_id="+campaign_id;
    request.send();
   
    var boxes_to_check = JSON.parse(request.responseText);
    blank_checkboxes();
    console.log(boxes_to_check);
    for (box in boxes_to_check)
    {
	document.getElementById(''+boxes_to_check[box]).checked = true;
    }
    
}

function save_to_database(){

    var dropdown = document.getElementById('campaigns');
    var campaign_id = dropdown.options[dropdown.selectedIndex].value;



    var checkboxes = document.getElementsByName('category_checkbox');
    var cats = [];
    for(i = 0; i < checkboxes.length;i++)
    {
	if(checkboxes[i].checked){
            cats.push(checkboxes[i].id)
	}
    }
    console.log(cats);
    if(campaign_id != '-1')
    {
	var request = new XMLHttpRequest;
	request.open("POST", "save_categories.php", false);
	var json_data = { };
	json_data.campaign_id = campaign_id;
	json_data.categories = cats;
	params = JSON.stringify(json_data);
	request.send(params);
	var butan = document.getElementById('save_button');
	butan.disabled = true;
	butan.innerHTML = "One second...";
	window.setTimeout(function(){butan.innerHTML = "Save Changes"; butan.disabled = false;}, 2000);
	
	

    }

    
}

</script>

<h2>Campaign Categories</h2>


<table>
<tr>
<td>Campaign: </td>
<td><select id="campaigns" name="campaigns" onchange='check_boxes(this);'>
    <option value='-1' >---SELECT A CAMPAIGN---</option>
    <?php
    
    $active_campaign_query = "                                                                        
                                                                                                       
       SELECT c.Name as Name, a.Name as Business, c.id as id                   
                                                                                                       
      FROM Campaigns c LEFT JOIN Advertisers a ON (c.business_id = a.id) WHERE ignore_for_healthcheck = 0                                                                                                                                                                                               
                                                                                                      
                                                                                                       
    
                                                                                                       
      GROUP BY c.id ORDER BY a.Name ASC";
$response = mysql_query($active_campaign_query);
echo "ASD: ".mysql_num_rows($response);
while($row = mysql_fetch_assoc($response))
{
    echo "<option value='".$row['id']."'>".$row['Business']." - ".$row['Name']."</option>";
}
?>
</select>
</td>
</table>
</br>
<?php
    $get_categories_query = "SELECT ID, Name FROM ad_verify_content_categories";
$category_response = mysql_query($get_categories_query);

while($row = mysql_fetch_assoc($category_response))
{
    echo "<input type='checkbox' name='category_checkbox' id='".$row['ID']."'>".$row['Name']."<br>";
}

?>
<br>
<button name="stop" value="Save Changes" onClick="save_to_database();" id="save_button">Save Changes</button>



</body>

</html>