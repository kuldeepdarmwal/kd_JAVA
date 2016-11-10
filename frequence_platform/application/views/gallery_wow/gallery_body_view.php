

<?php
$pills_string = '<div class="feature_pills tabbable"><ul id="filters" data-option-key="filter" class="nav nav-pills"><li class="active"  ><a data-filter="*" data-toggle="tab" href="#">All</a></li>';
	foreach($gallery_features as $feature){
					$pills_string .= '<li class=""><a data-toggle="tab" data-filter=".feature_'.$feature["feature_id"].'" href="#">'.$feature["feature_name"].'</a></li>';
	}
	$pills_string .= '</ul></div>';
	



	echo $pills_string;


	$tiles_string = '<div id="outer_div"><div id="gallery_div">';

	$tiles_string .= '</div></div>';
	
	echo $tiles_string;
?>