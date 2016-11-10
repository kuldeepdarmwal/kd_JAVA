<?
require_once("config/config.php");
if (!empty($_FILES)) {
	$albumid=$_REQUEST['albumid'];
	$imageInfo = getimagesize($_FILES['Filedata']['tmp_name']);
	
	$memoryNeeded = round(( ($imageInfo[0] * $imageInfo[1] + $config['bigImageX']*$config['bigImageY']) * $imageInfo['bits'] * $imageInfo['channels'] / 8 + pow(2, 16)) * 1.8);
	
	if(function_exists('memory_get_usage') && memory_get_usage() + $memoryNeeded > let_to_num(ini_get('memory_limit')))
	{
		ini_set('memory_limit',ceil( (memory_get_usage() + $memoryNeeded)/pow(1024,2) ).'M');
	}
	
	$tempFile = $_FILES['Filedata']['tmp_name'];
	$name =  $_FILES['Filedata']['name'];
	$text= "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ1234567890";

$array_last=explode(".",$_FILES['Filedata']['name']);
$c=count($array_last)-1;
$ext=strtolower($array_last[$c]);
$fileupload_name=substr(str_shuffle($text),0,32)."-".substr(str_shuffle($text),0,32).".".$ext;

	imageResize(array(
		'sourceFile'		=> $_FILES['Filedata']['tmp_name'],
		'imageInfo'			=> $imageInfo,
		'destinationFile'	=> $config['imageDir'].'/b/'.$fileupload_name,
		'width'				=> $config['bigImageX'],
		'height'			=> $config['bigImageY']
	));
	
	imageResize(array(
		'sourceFile'		=> $_FILES['Filedata']['tmp_name'],
		'imageInfo'			=> $imageInfo,
		'destinationFile'	=> $config['imageDir'].'/m/'.$fileupload_name,
		'width'				=> $config['midImageX'],
		'height'			=> $config['midImageY']
	));
	
	imageResize(array(
		'sourceFile'		=> $_FILES['Filedata']['tmp_name'],
		'imageInfo'			=> $imageInfo,
		'destinationFile'	=> $config['imageDir'].'/s/'.$fileupload_name,
		'width'				=> $config['smallImageX'],
		'height'			=> $config['smallImageY'],
		'method'			=> 'crop'
	));

	$dt=date("Y-m-d H:i:s");
	$sql="insert into  ".$prefix_table."pics  (albumid,filename,title,dt) values('$albumid','$fileupload_name','$name','$dt') ";
	$result=mysql_query($sql); 
	if($result){
		$row=mysql_fetch_row(mysql_query("select max(id) from ".$prefix_table."pics"));
		 $sql= q("UPDATE  ".$prefix_table."albums SET cnt =cnt +1,thumb = IF(thumb<>0,thumb,".$row[0].")  WHERE  id = '$albumid' ");
		 		echo "complete";
		}
}
?>