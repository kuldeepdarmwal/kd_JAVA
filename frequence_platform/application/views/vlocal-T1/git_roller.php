<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>VANTAGE LOCAL | Rollout</title>
	<link rel="shortcut icon" href="<?php echo base_url('images/favicon.png');?>">
	</head>
	<body>
	  <?php
   if(array_key_exists('rollit', $_POST) AND array_key_exists('branch_name', $_POST))
   {
       $branch = 'git@github.com:VantageLocal123/ToyTrunk.git -b ' . trim($_POST['branch_name']);
       $filename = $_SERVER['DOCUMENT_ROOT'] . '/rollit/rollout.flag';
       $fp = fopen($filename, "w");
       if($fp){
		   fwrite($fp, $branch);
		   echo "creating " . $filename . " <br>";
		   fclose($fp);
		   echo "<h2>GITHUB branch: ".$branch." rollout request has been submitted!</h2><br>";
		   echo '<a href="'.site_url("auth/logout").'"><img src="'.base_url("images/LOGOUT.gif").'" width="85" height="28" style="border:none" /></a><br>';
		   echo "<img src='".site_url('images/waiting.gif')."' />";
		   echo "<iframe src='".base_url('rollit/waiting.mp3')."'></iframe>";
       }
       else
	   {
		   echo "Error writing file: " . $filename . " <br>";
	   }
   }
   else
   {
       echo "<iframe src='".base_url('rollit/roll_out.mp3')."'></iframe>";
       echo "<form action='' method='post'>";
       echo "<h2>ROLLOUT GITHUB BRANCH</h2>";
       echo "<strong>Branch Name</strong>(brand_cdnify, xml_video_fix, develop, etc)<br>";
       echo "<input type='text' size='40' name='branch_name' /><br>";
       echo "<input type='submit' name='rollit' value='ROLL OUT' />";
       echo '<span style="margin-left:140px"><a href="'.site_url("auth/logout").'"><img src="'.base_url("images/LOGOUT.gif").'" width="85" height="28" style="border:none" /></a></span><br>';
       echo "</form>";
       // echo "<span style='float:right'>";
       //echo '<a href="'.site_url("auth/logout").'"><img src="'.base_url("images/LOGOUT.gif").'" width="85" height="28" style="border:none" /></a><br>';

   }
?>
<br>This rollout brought to you by:<br>
<?php
$fun_display = array("Her Majesty the Queen of England", 
					 "Ludacris Inc.", 
					 "The People's Republic of China", 
					 "The Green Party of the United States", 
					 "Mothers Aganist Drunk Driving",
					 "American Federation of Television and Radio Artists",
					 "United States Department of Labor",
					 "The National Labor Relations Act",
					 "Mustache Aficionado Magazine",
					 "The Peacock Lounge",
					 "The Parents Against Predators Act",
					 "Mothers Against Dog Chaining",
					 "Parents Against Bad Books In Schools",
					 "The Chicago Teacher's Union",
					 "Students and Teachers Against Racism",
					 "People Against Gangsterism and Drugs",
					 "The National Rifle Association",
					 "American Civil Liberties Union",
					 "The National Association of Barbershop Quartets",
					 "The City of Mountain View",
					 "The President of the United States",
					 "Van Halen",
					 "You",
					 "Toddlers in Tiaras",
					 "Women in Computer Science",
					 "The Evil Emperor Zurg",
					 "People Who Wear Socks With Sandals",
					 "The American Association of Independent Professional Baseball",
					 "The San Francisco Giants",
					 "The San Francisco 49ers",
					 "A Birch Tree",
					 "Those Squirrels from Outside",
					 "Tom Cruise",
					 "Russia, With Love",
					 "Harry Potter and the Chamber of Secrets",
					 "American Pickers",
					 "Hastily Written Code",
					 "Asian Box",
					 "John Madden",
					 "Dubstep",
					 "Joe the Plumber",
					 "Hockey Moms",
					 "Emacs",
					 "VI",
					 "The Council on Foreign Relations",
					 "The Letter 'B' and the Number 3",
					 "sleep inc."
				 );
$temp = rand(0, count($fun_display) - 1);
?>
<small><?php echo $fun_display[$temp]; ?></small>
</body>
</html>
