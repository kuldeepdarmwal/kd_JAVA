<?php
	$endash = html_entity_decode('&#x2013;', ENT_COMPAT, 'UTF-8');
	$emdash = html_entity_decode('&#x2014;', ENT_COMPAT, 'UTF-8');

	header('Content-type: text/csv');
	header('Content-Disposition: attachment; filename="users.csv"');

	// create a file pointer connected to the output stream
	$output = fopen('php://output', 'w');

	// output the column headings
	fputcsv($output, array('First Name','Last Name','Email','Role','Last Login ('.$timezone_code.')','Organization','Super Of','Is Active'));

	// loop over the rows, outputting them
	foreach ($users as $user)
	{
		$user_data = array();
		$user_data['firstname'] = $user['firstname'];
		$user_data['lastname'] = $user['lastname'];
		$user_data['email'] = $user['email'];
		$user_data['role'] = $user['role'];
		$super_of = '';
		$org_name = str_replace([$endash,$emdash], '-', $user['org_name']);
		$is_active = 'Yes';
		
		if($user['banned'] == 1)
		{
			$is_active = 'No';
		}
		if ($user_data['role'] == 'SALES')
		{
			$user_data['role'] = 'PARTNER';
			
			if ($user['isGroupSuper'] == '1')
			{
				$super_of = $org_name;
			}
			
			if (isset($user['owned_partners']))
			{
				$owned = str_replace([$endash,$emdash], '-', $user['owned_partners']);
				
				if ($super_of != '')
				{
					$super_of = $super_of.'||'.$owned;
				}
				else
				{
					$super_of = $owned;
				}
			}
		}
		
		$user_data['last_login'] = $user['last_login'];
		$user_data['org_name'] = $org_name;
		$user_data['super_of'] = $super_of;
		$user_data['is_active'] = $is_active;
		
		fputcsv($output, $user_data);
	}
?>