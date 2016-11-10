<?php

$data = array();
if($notes_response->num_rows() > 0)
{
	$notes_data = $notes_response->row();

	if($notes_data->is_saved_lap)
	{
		$data = array(
			'advertiser' => $notes_data->advertiser,
			'plan' => $notes_data->plan_name,
			'notes' => $notes_data->notes,
			'zips' => $zips_string
		);
	}
	else
	{
		$data = array(
			'advertiser' => '',
			'plan' => '',
			'notes' => '', 
			'zips' => $zips_string
		);
	}
}
else
{
	$data = array(
		'advertiser' => "",
		'plan' => "",
		'notes' => "",
		'zips' => ""
	);
}

$json = json_encode($data);
echo $json;
return;
?>
