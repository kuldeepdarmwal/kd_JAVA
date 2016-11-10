<html>
<head><title>COOKIE MONSTER</title></head>
<body>
<?php

if(!$_GET["id"])
{
    echo "We need an ID";
}
else
{

    $db = mysql_connect('db.vantagelocaldev.com', 'vldevuser', 'L0cal1s1n!');

    mysql_select_db('vantagelocal_dev');

//Get cookies
$cookie_query = "
      SELECT tag 
      FROM tags
      WHERE campaign_id = ".$_GET['id']." AND tag_type = 1";

echo $cookie_query.' ';
$cookie_result = mysql_query($cookie_query);

while($row = mysql_fetch_assoc($cookie_result))
{
    echo $row['tag'];
}

}

?>
</body>
</html>