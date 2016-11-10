<div class="container">


<?php
echo '<h3>'.$title.'</h3>';
echo '<i class="icon-filter"></i> Filter';
if(isset($table_data['rows'][0])){
	$table = '<table class="tableWithFloatingHeader table table-condensed table-hover table-striped table-bordered">
		  <thead>';
    foreach($table_data['rows'][0] as $field_id=>$val){
    	$table .= '<th>'.(isset($table_data['fields'][$field_id]['friendly_name'])? $table_data['fields'][$field_id]['friendly_name'] : $field_id).'</th>';
    }
	$table .=	  '</thead>
		  <tbody>';
	foreach($table_data['rows'] as $key=>$entry){
		$table .= '<tr>';
		foreach($entry as $field_id=>$field_value){
			$val_to_print =  $field_value;
			
			if(isset($table_data['fields'][$field_id]['type']) && $table_data['fields'][$field_id]['type'] == 'file'){
				preg_match('#\((.*?)\)#', $field_value, $match);
				$val_to_print = isset($match[1])? '<a class="btn btn-mini btn-primary" href="'.$match[1].'"><i class="icon-download-alt icon-white"></i> '.pathinfo($match[1], PATHINFO_EXTENSION).'</a>' : $field_value;
			}
			if(isset($table_data['fields'][$field_id]['type']) && $table_data['fields'][$field_id]['type'] == 'url' && $field_value != ''){
				$val_to_print = '<a class="btn btn-mini btn-success" href="'.$field_value.'" target="_blank"><i class="icon-picture icon-white"></i></a>';
			}

			$table .= '<td>'.$val_to_print.'</td>';
		}
		$table .= '</tr>';
	}
	$table .= '</tbody>
				</table>';

			echo $table;

}else{
	echo 'no '.$title.' found';
}





//print '<pre>'; print_r($table_data); print '</pre>';
?>

</div>