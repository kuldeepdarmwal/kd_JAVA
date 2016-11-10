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

    //$db = mysql_connect('10.179.2.54', 'vlproduser', 'L0cal1s1n!');
    //mysql_select_db('vantagelocal_prod');
	$db = mysql_connect('brandcdn-prod.cgy3zqnvq7mi.us-west-1.rds.amazonaws.com', 'frequence_admin', '!Ds6AC0SnulgHe$4');
    mysql_select_db('brandcdn');	

//Get cookies
$cookie_query = "
      SELECT tag 
      FROM tags
      WHERE campaign_id = ".$_GET['id']." AND tag_type = 1";

echo $cookie_query.' ';
$cookie_result = mysql_query($cookie_query);

while($row = mysql_fetch_assoc($cookie_result))
{
    echo $row['tag']."<br><br>";
    $tag = $row['tag'];
    $tag = str_replace("<", "&lt;", $tag);
    $tag = str_replace(">", "&gt;", $tag);
    echo $tag."<br><br>";
    

}

}

?>
</body>
</html>