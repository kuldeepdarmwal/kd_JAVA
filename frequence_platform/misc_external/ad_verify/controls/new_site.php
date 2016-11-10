<html>
<head><title>Add a site</title>
<body>
<?php
$db = mysql_connect('db.vantagelocaldev.com', 'vldevuser', 'L0cal1s1n!');
mysql_select_db('vantagelocal_dev');

if($_POST['full_url'] != "" && $_POST['base_url'] != "")
{
   
    echo "Adding ".$_POST['full_url']." to database";
   
    $add_to_site_table_query = "INSERT INTO ad_verify_page_data (base_url, url) VALUES ('".$_POST['base_url']."','".$_POST['full_url']."')";
    
    //echo $add_to_site_table_query;
    
    mysql_query($add_to_site_table_query);
    
    $get_id_query = "SELECT id FROM ad_verify_page_data WHERE base_url = '".$_POST['base_url']."' AND url = '".$_POST['full_url']."'";
    
    $result = mysql_query($get_id_query);
    $data = mysql_fetch_assoc($result);
    $id = $data['id'];
    
//	echo $get_id_query;
    foreach ($_POST as $column_name => $column_data) {
        if($column_name != 'full_url' and $column_name != 'base_url')
	{
	   $match_query = "INSERT INTO ad_verify_page_categories(page_id, category_id) VALUES (".$id.",".$column_name.")";
//	   echo $match_query;
	   mysql_query($match_query);
	}
    }

}



?>

<h2>Add a site to the database</h2>

<form action="/new_site.php" method="post">
    
<input type="text" name="full_url"> Full URL of Site
<br>
    <input type="text" name="base_url"> Base URL of Site
<br>
<?php
    $get_categories_query = "SELECT ID, Name FROM ad_verify_content_categories";
$category_response = mysql_query($get_categories_query);

while($row = mysql_fetch_assoc($category_response))
{
    echo "<input type='checkbox' name='".$row['ID']."'>".$row['Name']."<br>";
}

?>
<br>
<input type="submit" value="Add Site">
</form>



</body>

</html>