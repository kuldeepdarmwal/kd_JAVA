<?php
	$endash = html_entity_decode('&#x2013;', ENT_COMPAT, 'UTF-8');
	$emdash = html_entity_decode('&#x2014;', ENT_COMPAT, 'UTF-8');

	header('Content-type: text/csv');
	header('Content-Disposition: attachment; filename="access_manager.csv"');

	// create a file pointer connected to the output stream
	$output = fopen('php://output', 'w');

	// output the column headings
	fputcsv($output, array('Advertiser Name',
                                'Agency Name',
                                'Advertiser Group',
                                'UL ID / Client ID',
                                'Traffic System',
                                'Partner Name',
                                'Registered',
                                'Access',
                                'Last Date of Welcome Email',
                                'Registration Date',
                                'Last Login Date',
                                'User Email',
                                'User First Name',
                                'User Last Name',
                                'Creation Date',
                                'Sales Person'
                        ));

	// loop over the rows, outputting them
	foreach ($view_data as $export)
	{
                $data = array();
                $data['adv_name'] = $export['adv_name'];
                $data['agency_name'] = $export['agency_name'];
                $data['ag_name'] = $export['ag_name'];
                $data['ul_id'] = $export['ul_id'];
                $data['traffic_system'] = $export['traffic_system'];
                $data['partner_name'] = $export['partner_name'];
                
                $user_count = 0;
                foreach ($export['users'] as $user_export)
                {
                        if (!empty($user_export['email']))
                        {
                                $data['is_registered'] = '';
                                if (isset($user_export['resend_link_flag']) && $user_export['resend_link_flag'] == "1")
                                {
                                        $data['is_registered'] = 'No';
                                }
                                else if (isset($user_export['email']))
                                {
                                        $data['is_registered'] = 'Yes';
                                }

                                $data['has_access'] = '';
                                if ($user_export['is_power_user'] == '1')
                                {
                                        $data['has_access'] = 'Admin';
                                }
                                else if ($user_export['role'] == 'CLIENT' || $user_export['role'] == 'AGENCY')
                                {
                                        $data['has_access'] = $user_export['role'];
                                }

                                $data['last_welcome_email_date'] = '';
                                if ($user_export['resend_link_flag'] == "1" && $user_export['activation_email_date'] != "" && $user_export['activation_email_date'] != "0000-00-00")
                                {
                                        $data['last_welcome_email_date'] = $user_export['activation_email_date'];
                                }

                                $data['registration_date'] = '';
                                if ($user_export['registration_date'] != "" && $user_export['registration_date'] != "0000-00-00")
                                {
                                        $data['registration_date'] = $user_export['registration_date'];
                                }

                                $data['last_login_date'] = '';
                                if ($user_export['resend_link_flag'] != "1" && $user_export['activation_email_date'] != "" && $user_export['activation_email_date'] != "0000-00-00")
                                {
                                        $data['last_login_date'] = $user_export['activation_email_date'];
                                }

                                $data['user_email'] = $user_export['email'];
                                $data['user_first_name'] = $user_export['first_name'];
                                $data['user_last_name'] = $user_export['last_name'];

                                $data['user_created_date'] = '';
                                if ($user_export['user_created_date'] != "" && $user_export['user_created_date'] != "0000-00-00")
                                {
                                        $data['user_created_date'] = $user_export['user_created_date'];
                                }

                                $data['sales_person'] = $user_export['sales_first_name'] . ' ' . $user_export['sales_last_name'];

                                $user_count++;
                                fputcsv($output, $data);
                        }
                }

                if ($user_count == 0)
                {
                        fputcsv($output, $data);
                }
	}
?>