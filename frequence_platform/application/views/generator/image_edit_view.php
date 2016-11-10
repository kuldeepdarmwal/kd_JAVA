<!DOCTYPE html>
<html>
  <head>
    <title>Image Editor|Lap <?php echo $lap_id; ?></title>
  </head>
  <body>
 <?php
	echo $response."<br>";
	//var_dump($_POST);
echo '<a href="/proposal_builder/lap_image_gen/'.$lap_id.'/'.$prop_id.'">Generate Images For this Proposal and LAP</a>';
      ?>
    <form action="" method="post">
      <table>
	<?php
  if(!isset($images))
    {
      //echo "<br>Generate some images to edit first<br>";
    }
  else
    {
      $i=0;
      foreach($images as $v)
    	{
    	  echo '<tr><td>Number:<br><input type="hidden" value="'.$v["snapshot_num"].'" name="hidden'.$i.'" /><input type="text" name="num'.$i.'" value="'.$v["snapshot_num"].'"/><br>Title:<br><input type="text" name="title'.$i.'" value="'.$v["snapshot_title"].'" /></td><td><img src="'.$v["snapshot_data"].'"></td><td><input type="submit" name="delete'.$v["snapshot_num"].'" style="height:30px;width:120px;background: #D04646;color: #EAEAEA;" value="Delete Image" /></td></tr>';
    	  $i++;
    	}
      echo '<tr><td></td><td><input type="submit" name="submit" style="height:30px;width:120px;" value="Finish Editing" /><tr><td>';
    }
?>
      </table>
    </form>
  </body>
</html>
