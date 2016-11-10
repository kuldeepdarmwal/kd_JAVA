<?php

class Proposal_Builder extends CI_Controller
{
	private $snapshot_timeout_in_minutes = 10;

	static $EMAIL_NOREPLY = 'no-reply@brandcdn.com';
	static $EMAIL_HELPDESK = 'helpdesk@brandcdn.com';
	static $EMAIL_CC = array();
	static $EMAIL_BCC = array();

	function __construct()
	{
		parent::__construct();

		$this->load->model('proposal_gen_model');
		$this->load->model('lap_lite_model');
		$this->load->model('mpq_v2_model');
		$this->load->model('rf_model');
		$this->load->model('siterankpoc_model');
		$this->load->model('users');
		$this->load->model('tag_model');
		$this->load->model('proposals_model');

		$this->load->helper('url');
		$this->load->helper('form');
		$this->load->helper('html_dom');
		$this->load->helper('vl_ajax_helper');
		$this->load->helper('multi_lap_helper');
		$this->load->helper('select2_helper');
		$this->load->helper('ttd_excel_upload_helper');
		$this->load->helper('mailgun');
		$this->load->helper('tag_helper');

		$this->load->library('excel');
		$this->load->library('session');
		$this->load->library('tank_auth');
		$this->load->library('ftp');
		$this->load->library('vl_platform');
		$this->load->library('map');
		$this->load->library('proposal_generator');
		$this->load->library('cli_data_processor_common');
	}

	function check_is_logged_in_media_planner_and_redirect($referer)
	{
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer', $referer);
			redirect("login");
		}
		$username = $this->tank_auth->get_username();
		$role = strtolower($this->tank_auth->get_role($username));
		if($role != 'admin' && $role != 'ops' && $role != 'creative')
		{
			redirect("director");
		}
	}

	function multi_geo_grabber()
	{
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer', 'proposal_builder/multi_geo_grabber');
			redirect("login");
		}
		$username = $this->tank_auth->get_username();
		$role = strtolower($this->tank_auth->get_role($username));

		if($role != 'admin' && $role != 'ops') redirect("director");

		$data = array(
			'title' => 'Multi-location Planner',
			'user_role' => $role,
			'is_logged_in' => $this->tank_auth->is_logged_in(),
			'maps_js' => $this->load->view('maps/maps_js.php', array('national_averages_array' => $this->map->get_national_averages_for_demos()), true),
			'map_html' => $this->load->view('maps/maps_view.php', '', true),
			'demo_html' => $this->load->view('maps/demos_view.php', '', true),
			'demo_css' => $this->map->get_demos_css()
		);

		$active_feature_button_id = 'multi_geos';
		$this->vl_platform->show_views(
			$this,
			$data,
			$active_feature_button_id,
			'generator/multi_lap_view',
			'generator/multi_lap_view_html_header.php',
			NULL,
			'generator/multi_lap_view_js.php',
			NULL
			// , false
		);
	}

	public function ajax_multi_geo_grabber_submit()
	{
		if (isset($_POST['laps']))
		{
			$allowed_roles = array('admin', 'ops');
			$post_variables = array('laps', 'id_append');

			$response = vl_verify_ajax_call($allowed_roles, $post_variables);
			if (!$response['is_success'])
			{
				echo json_encode($response);
				exit(0);
			}
			else
			{
				$return_array = array('is_success' => TRUE);
				$return_array['maps'] = array();
				$return_array['center_coordinates'] = array();
				$return_array['errors'] = array();
				$return_array['max_radius'] = array();
				$return_array['center_names'] = array();
				$return_array['id_append'] = array();

				$laps = $response['post']['laps'];
				$lap_array = $laps;
				foreach($lap_array as $line_number => $lap_line)
				{
					$arr1 = explode("\t", $lap_line);
					$arr2 = explode(";", $lap_line);
					$lap_line_array = (count($arr1) == 3) ? $arr1 : $arr2;
					$map_parameters = verify_and_format_input_line($lap_line_array, $return_array, $response['post']['id_append'], $line_number);

					if ($return_array['is_success'])
					{
						$lat_long = get_geocoded_address_center_from_google($map_parameters['address']);
						if (isset($lat_long['errors']))
						{
							echo json_encode(array('is_success' => false, 'errors' => $lat_long['errors']));
							exit(0);
						}

						$containing_zip = NULL;
						if (isset($lat_long['containing_zip']))
						{
							$containing_zip = $lat_long['containing_zip'];
							unset($lat_long['containing_zip']);
						}

						$return_array['center_coordinates'][] = $lat_long;
						$affected = $this->get_affected_zips($lat_long, $map_parameters['radius'], $map_parameters['min_population']);
						$radius = (isset($affected[0]['distance']) ? $affected[count($affected) - 1]['distance'] : $map_parameters['radius']);
						if (!is_int($radius))
						{
							$radius = ceil($radius);
							$affected = $this->get_affected_zips($lat_long, $radius, NULL);
						}
						$affected_zips = array_column($affected, 'zips');
						if (count($affected_zips) == 0)
						{
							if ($containing_zip == NULL)
							{
								echo json_encode(array('is_success' => false, 'errors' => array('No geos available for that region/radius')));
								exit(0);
							}
							$affected_zips = array($containing_zip);
						}

						$return_array['max_radius'][] = $radius;
						$return_array['maps'][] = $affected_zips;
						$return_array['center_names'][] = $map_parameters['address'];
						if (!empty($response['post']['id_append']))
						{
							$return_array['id_append'][] = $response['post']['id_append'] . '_' . $line_number;
						}
						$return_array['map_uris'][] = $this->map->get_geo_json_map_uri_for_google_maps(array('blobs' => array('zcta' => $affected_zips), 'center' => $lat_long), $radius);
					}
				}
				echo json_encode($return_array);
			}
		}
		else
		{
			show_404();
		}
	}

	private function get_affected_zips($lat_long, $radius, $min_population)
	{
		if (isset($lat_long['latitude']))
		{
			return (!empty($radius) ? $this->map->get_zips_from_radius_and_center($lat_long, $radius) : $this->map->get_zips_from_min_population_and_center($lat_long, $min_population));
		}
		return NULL;
	}

	public function switch_media_plan()
	{
		$success = $this->lap_lite_model->old_data_new_lap($this->session->userdata('session_id'));
		if($success)
		{
			echo "win";
		}
		else
		{
			echo "lose";
		}
	}

	public function get_lap_summary($lapId = "")
	{
		echo show_404();
	}

	public function save_proposal_html($prop_id)
	{

		$vldir_path = $this->get_proposal_vldir_path();
		$proposal_file_name = $prop_id.'.html';
		$proposals_domain = $this->config->item('proposal_snapshots_ftp_domain');

		$webpage = "http://$proposals_domain/".$vldir_path.'/'.$proposal_file_name;  //set link to remote html page

		$save_path = $_SERVER['DOCUMENT_ROOT'].'/assets/proposal_pdf/'.$proposal_file_name;
		$string = json_decode($_POST["obj"]);
		//$pattern = '/<script type="text\/javascript">(.+?)if\(document\.getElement(.+?)<\/script><\!\-\-<\/body><\/html>\-\->/s';
		//$replacement = "";
		//$string = preg_replace($pattern, $replacement, $string);


		if($fp = fopen($save_path, 'w')) //open html file at assets/proposal_pdf for writing
		{
			fwrite($fp, $string);
			fclose($fp);
			$this->ftp->connect($this->config->item('proposal_snapshots_ftp_config'));
			$vldir_path_and_file_name = $vldir_path.'/'.$proposal_file_name;

			if($this->ftp->upload($save_path,'/'.$vldir_path_and_file_name))
			{
				echo "File saved at http://$proposals_domain/".$vldir_path_and_file_name;
			}
			else
			{
				echo "Upload failed at http://$proposals_domain/".$vldir_path_and_file_name;
			}
			unlink($save_path);
		}
		else
		{
			echo "Failed to save file: ".$save_path;
		}
	}


	private function process_remote_html($template, $data)
	{
		// simple_html_dom method that gets the html
		// and creates a php object from it
		$html = file_get_html($template);

		$template_variables = array();

		// Sets up an array that can be used to grab the correct form of
		// the period type
		$period_type_forms = array(
			'days' => array(
				'singular'	=> 'Day',
				'adverb'	=> 'Daily'
			),
			'weeks' => array(
				'singular'	=> 'Week',
				'adverb'	=> 'Weekly'
			),
			'months' => array(
				'singular'	=> 'Month',
				'adverb'	=> 'Monthly'
			)
		);

		// Cover page
		$template_variables['advertiser_name'] = $data['advertiser_name'];

		// Executive Overview
		$template_variables['T_NUM_ZIPS'] = number_format($data['num_zips']) . " Zip Code";
		$template_variables['T_NUM_ZIPS'] .= $data['num_zips'] != 1 ? "s" : "";

		$template_variables['T_NUM_COUNTIES'] = number_format(count($data['counties']));
		$county_plural = count($data['counties']) != 1 ? " Counties" : " County";
		$template_variables['T_NUM_COUNTIES'] .= $county_plural;

		$template_variables['T_NUM_CONTENT_SUBJECTS'] = "Contextual: " . number_format(count($data['iab_categories']));
		$template_variables['T_NUM_CONTENT_SUBJECTS'] .= count($data['iab_categories']) != 1 ? " Content Subjects" : " Content Subject";
		$template_variables['T_NUM_CONTENT_SUBJECTS'] .= "<br />\n";

		$demo_all_switch = true;
		foreach($data['demographics'] as $demo_key => $demo_category)
		{
			if ($demo_category != "All")
			{
				if(!isset($template_variables['T_DEMOGRAPHIC_CATEGORIES']))
				{
					$template_variables['T_DEMOGRAPHIC_CATEGORIES'] = "Targeted Demographics: " . $demo_key;
				}
				else
				{
					$template_variables['T_DEMOGRAPHIC_CATEGORIES'] .= ", " . $demo_key;
				}
				$demo_all_switch = false;
			}
		}

		if ($demo_all_switch)
		{
			$template_variables['T_DEMOGRAPHIC_CATEGORIES'] = "Targeted Demographics: All";
		}
		$template_variables['T_DEMOGRAPHIC_CATEGORIES'] .= "<br />\n";

		// TODO: This just grabs the population from the first adplan.
		// It should be finding all the unique geographic regions and
		// calculating the population from those.
		$template_variables['T_GEO_POPULATION'] = number_format($data['options'][0]['total_population']);
		$template_variables['T_RETARGETING'] = "Included";

		$template_variables['T_IMPRESSION_PACKAGES'] = "";
		foreach($data['options'] as $option)
		{
			$template_variables['T_IMPRESSION_PACKAGES'] .= number_format($option['impressions']) . " " . $period_type_forms[$option['period_type']]['adverb'] . " Impressions<br />\n";
		}
		if (count($data['options']) < 2)
		{
			$template_variables['impressions_packages_header'] = "package summary";
		}


		// Geographic Targeting
		$template_variables['T_GEO_1_IMG'] = "<img src=\"". $data["geo_overview_link"] ."\" width=\"100%\" />\n";
		$template_variables['T_GEO_1_TITLE'] = $data['geo_overview_title'] ?: "GEOGRAPHIC SUMMARY";

		$template_variables['T_NUM_ZIPS2'] = number_format($data['num_zips']) . " Zip Code";
		$template_variables['T_NUM_ZIPS2'] .= $data['num_zips'] != 1 ? "s" : "";

		$num_counties = count($data["counties"]);
		if ($num_counties > 1) {
			$data["counties"][$num_counties - 1] = "and " . $data["counties"][$num_counties - 1];
		}
		$county_separator = ($num_counties > 2) ? ", " : " ";
		$template_variables['T_COUNTY_LIST'] = implode($data["counties"], $county_separator) . " " . $county_plural;
		$template_variables['T_GEO_POPULATION2'] = number_format($data['options'][0]['total_population']);

		// Contextual Targeting
		$iab_category_string = ""; //this section will not be displayed on the proposal at all if there is no contextual
		$font_size_multiplier = 0;
		$proposals_domain = $this->config->item('proposal_snapshots_ftp_domain');

		if(count($data['iab_categories']) > 0)
		{
			// This is a little complicated. We need to see if the columns will bleed over,
			// and if so, create a new column. 48 is the height in pixels of a header,
			// and 36 is the height in pixels of a sub item.
			$header_height_count = 0;
			$sub_height_count = 0;

			$iab_category_string .= "<div class=\"iab_column\">";

			foreach($data['iab_categories'] as $primary_channel_name => $primary_channel_set)
			{
				$header_height_count += 48;

				// If the list (header to the next header) will be longer than the alotted space, we want
				// to break it up between two columns.
				if (($header_height_count + (count($primary_channel_set, COUNT_RECURSIVE) * 36)) > 900)
				{

					$iab_category_string .= '<div class="p4_primary_channel head_text">'.$primary_channel_name.'</div>'."\n";
					if(empty($primary_channel_set))
					{
						$iab_category_string .= '<div class="p4_secondary_channel sub_text">'.'General'.'</div>'."\n";
					}
					else
					{
						foreach($primary_channel_set as $secondary_channel_name => $secondary_channel_set)
						{
							$sub_height_count += 36;

							if ( ($header_height_count + $sub_height_count) > 900 )
							{
								$iab_category_string .= "</div><div class=\"iab_column\">";
								$header_height_count = 0;
								$sub_height_count = 0;
								$font_size_multiplier++;
							}

							$iab_category_string .= '<div class="p4_secondary_channel sub_text">'.$secondary_channel_name.'</div>'."\n";
							foreach($secondary_channel_set as $tertiary_channel_name => $tertiary_channel_set)
							{
								$sub_height_count += 36;

								if ( ($header_height_count + $sub_height_count) > 900 )
								{
									$iab_category_string .= "</div><div class=\"iab_column\">";
									$header_height_count = 0;
									$sub_height_count = 0;
									$font_size_multiplier++;
								}
								$iab_category_string .= '<div class="p4_tertiary_channel sub_text">'.$tertiary_channel_name.'</div>'."\n";
							}
						}
					}
				}
				else
				{
					$sub_height_count += count($primary_channel_set, COUNT_RECURSIVE) * 36;

					// If the list will be too long for the current column but is not longer than the total alotted space (900px),
					// we just move it to the next column.
					if ( ($header_height_count + $sub_height_count) > 900 )
					{
						$iab_category_string .= "</div><div class=\"iab_column\">";
						$header_height_count = 0;
						$sub_height_count = count($primary_channel_set, COUNT_RECURSIVE) * 36;
						$font_size_multiplier++;
					}

					$iab_category_string .= '<div class="p4_primary_channel head_text">'.$primary_channel_name.'</div>'."\n";
					if(empty($primary_channel_set))
					{
						$iab_category_string .= '<div class="p4_secondary_channel sub_text">'.'General'.'</div>'."\n";
					}
					else
					{
						foreach($primary_channel_set as $secondary_channel_name => $secondary_channel_set)
						{

							$iab_category_string .= '<div class="p4_secondary_channel sub_text">'.$secondary_channel_name.'</div>'."\n";
							foreach($secondary_channel_set as $tertiary_channel_name => $tertiary_channel_set)
							{
								$iab_category_string .= '<div class="p4_tertiary_channel sub_text">'.$tertiary_channel_name.'</div>'."\n";
							}
						}
					}
				}
			}

			$iab_category_string .= "</div>";
		}

		$template_variables['iab_categories'] = $iab_category_string;


		// Demographic Targeting
		$template_variables['T_DEMOGRAPHICS'] = "";
		foreach($data['demographics'] as $key => $demo)
		{
			$demo_string = $demo == "All" ? "All" : implode($demo, ", ");
			$template_variables['T_DEMOGRAPHICS'] .= $key . ": " . $demo_string . "<br />\n";
		}
		$template_variables['T_DEMO_POPULATION'] = $data['options'][0]['demo_population'] <= $data['options'][0]['total_population'] ? number_format($data['options'][0]['demo_population']) : number_format($data['options'][0]['total_population']);


		// Site List
		$site_list_header = "
			<div class=\"heading_wrapper\">\n
			<div class=\"icon_holder\"><img src=\"http://$proposals_domain/proposal_images/images/icon_strategy.png\">\n
			</div>\n
			<div class=\"heading_textholder\">\n
			  <span class=\"title\">targeting strategy</span>\n
			</div>\n
			<div class=\"clear\">\n
			</div>\n
			</div>\n
			<div class=\"site_list_title\">\n
			<h3>site list</h3>\n
			</div>\n
			<div class=\"p4_sitesubtext\"> \n
			<table class=\"p4_fixed\"> \n
			<tr class=\"head_tr\" id=\"p4_tablehead\"> \n
			<th class=\"p4_th\" style=\"text-align:left;\" width=\"22%\">&nbsp; Placement</th> \n
			<th class=\"th_rotate\">Male</th> \n
			<th class=\"th_rotate\">Female</th> \n
			<th class=\"th_rotate\">Under 18</th> \n
			<th class=\"th_rotate\">18-24</th> \n
			<th class=\"th_rotate\">25-34</th> \n
			<th class=\"th_rotate\">35-44</th> \n
			<th class=\"th_rotate\">45-54</th> \n
			<th class=\"th_rotate\">55-64</th> \n
			<th class=\"th_rotate\">65+</th> \n
			<th class=\"th_rotate\">No Kids</th> \n
			<th class=\"th_rotate\">Has Kids</th> \n
			<th class=\"th_rotate\">$0-50k</th> \n
			<th class=\"th_rotate\">50-100k</th> \n
			<th class=\"th_rotate\">100-150k</th> \n
			<th class=\"th_rotate\">150k</th> \n
			</tr> \n
		";

		$site_list_footer = "
			</table> \n
			</div> \n
			<div class=\"break\"></div> \n
		";

		$site_list_string = "";

		if (!empty($data['site_list']))
		{
			$site_list_string = $site_list_header;

			$site_row_count = 1;
			foreach($data['site_list'] as $site)
			{
				if ($site[0] == 'break_tag')
				{
					$site_list_string .= $site_list_footer . $site_list_header;
					$site_row_count = 1;
				}
				else
				{
					if ($site_row_count >= 33)
					{
						$site_list_string .= $site_list_footer . $site_list_header;
						$site_row_count = 1;
					}

					if ($site[0] == 'header_tag')
					{
						$site_list_string .= '<tr class="p4_siterow">';
						$site_list_string .= '<td class="p4_td" colspan="16" style="text-align:left;padding-left:5px;font-weight:bold;" width="22%">'.$site[1].'</td></tr>'."\n";
					}
					else
					{
						$site_list_string .= "<tr class='p4_siterow'>"."\n";
						$site_list_string .= "<td class='p4_td' style='text-align:left;padding-left:5px;' width='22%'>".$site[0]."</td><td style='text-align:center;' class='4_td'>".($site[2] * 100)."%</td><td style='text-align:center;' class='p4_td'>".($site[3] * 100)."%</td><td style='text-align:center;' class='p4_td'>".($site[4] * 100)."%</td><td style='text-align:center;' class='p4_td'>".($site[5] * 100)."%</td><td style='text-align:center;' class='p4_td'>".($site[6] * 100)."%</td><td style='text-align:center;' class='p4_td'>".($site[7] * 100)."%</td><td style='text-align:center;' class='p4_td'>".($site[8] * 100)."%</td><td style='text-align:center;' class='p4_td'>".($site[9] * 100)."%</td><td style='text-align:center;' class='p4_td'>".($site[10] * 100)."%</td><td style='text-align:center;' class='p4_td'>".($site[16] * 100)."%</td><td style='text-align:center;' class='p4_td'>".($site[17] * 100)."%</td><td style='text-align:center;' class='p4_td'>".($site[18] * 100)."%</td><td style='text-align:center;' class='p4_td'>".($site[19] * 100)."%</td><td style='text-align:center;' class='p4_td'>".($site[20] * 100)."%</td><td style='text-align:center;' class='p4_td'>".($site[21] * 100)."%</td>"."\n";
						$site_list_string .= "</tr>"."\n";
					}

					$site_row_count++;
				}
			}

			$site_list_string .= "</table></div>";
		}
		else
		{
			foreach($html->find('#p9_wrapper') as $el)
			{
				$el->outertext = '';
			}
		}

		$template_variables['site_list'] = $site_list_string;

		// Performance Estimates AND Cost Summary
		$template_variables['display_packages'] = "";
		$template_variables['impressions_packages'] = "";

		$template_length_count = 0;

		foreach ($data['options'] as $i => $option)
		{
			$option_name = $option['option_name'] ?: "Display Package " . $i;
			$period_type = $period_type_forms[$option['period_type']];

			// We count an option header as 3 rows, so if the length of the page will
			// be over 18 rows plus 3 headers (or the equivalent) we break.
			$template_length_count += count($option["display_packages"]) + 3;
			if ($template_length_count > 27)
			{
				$template_variables["display_packages"] .= '
					<div class="break"></div>
					<div class="heading_wrapper">
						<div class="icon_holder"><img height="136" src="http://'.$proposals_domain.'/proposal_images/performance_icon.jpg" width="136"></div>

						<div class="heading_textholder">
							<span class="title">performance ESTIMATES</span>
						</div>

						<div class="clear"></div>
					</div><img alt="" height="300" src="http://'.$proposals_domain.'/proposal_images/your_ad_here.jpg" width="1240">
				';
				$template_length_count = count($option["display_packages"]) + 2;
			}
			else if ($i != 0)
			{
				$template_variables['display_packages'] .= "<img src=\"http://$proposals_domain/proposal_images/images/diagnols_1240px.jpg\" width=\"1000\" height=\"30\" style=\"display:block;margin:0px auto 30px auto;\" alt=\"\">\n";
			}

			$template_variables['display_packages'] .= "<div class=\"p5_headtext head_text\">".$option_name."</div>\n";
			$template_variables['display_packages'] .= '<table class="p5_subtext sub_text" cellspacing="10" width="80%"><tr class="p5_disptr"><th class="p5_trhead" style="text-transform:uppercase;">'.$period_type['singular'].'</th><th class="p5_trhead">IMPRESSIONS</th><th class="p5_trhead">REACH[%]</th><th class="p5_trhead">REACH</th><th class="p5_trhead">FREQUENCY</th></tr>'."\n";

			foreach ($option['display_packages'] as $term => $display_package)
			{
				$template_variables['display_packages'] .= '<tr><td>'.($term + 1).'</td><td>'.number_format($display_package["impressions"]).'</td><td>'.$display_package["reach_percent"].'%</td><td>'.number_format($display_package["reach"]).'</td><td>'.$display_package["frequency"].'</td></tr>'."\n";
			}

			$template_variables['display_packages'] .= "</table>\n";

			if ($option['cost_by_campaign'] == 0)
			{
				$template_variables['impressions_packages'] .= "<div class=\"p6_displayhead\">\n<span class=\"p6_headtext head_text\">\n";
				$template_variables['impressions_packages'] .= number_format($option['impressions'], 0) . " " . $period_type['adverb'] . " Impressions";
				$template_variables['impressions_packages'] .= "</span></div>";

				$retargeting_msg = $option['has_retargeting'] ? " (with Retargeting)" : "";
				$template_variables['impressions_packages'] .= "
					<div class=\"p6_subtext sub_text\">\n
					<div>Ad Creative Design and Production:<span class=\"p6_vars\"> $0</span></div>\n
					<div>Impressions Cost".$retargeting_msg.":<span class=\"p6_vars\">  $". number_format($option['monthly_cost_raw'], 0) ."</span></div>\n
				";

				$discount_msg = "";
				if ($option['monthly_percent_discount'] != "0")
				{
					$discount_msg = " (including discount)";
					$discount_total = $option['monthly_cost_raw'] - $option['monthly_cost'];

					$template_variables['impressions_packages'] .= "
						<div>".$period_type['adverb']." Subtotal: <span class=\"p6_vars\">$".number_format($option['monthly_cost_raw'])."</span></div>
						<div>Less ".$option['monthly_percent_discount']."% Discount: <span class=\"p6_vars\">-$".number_format($discount_total)."</span></div>
					";
				}
				$template_variables['impressions_packages'] .= "<div>Cost Summary".$discount_msg.": <span class=\"p6_vars\">$".number_format($option['total_cost'])."</span></div>";
				$template_variables['impressions_packages'] .= "</div><br /><br />";
			}
			else
			{
				$total_impressions = $option['impressions'] * count($option['display_packages']);
				$total_cost = $option['total_cost'] * count($option['display_packages']);
				$total_cost_raw = $option['monthly_cost_raw'] * count($option['display_packages']);

				$template_variables['impressions_packages'] .= "<div class=\"p6_displayhead\">\n<span class=\"p6_headtext head_text\">\n";
				$template_variables['impressions_packages'] .= number_format($total_impressions, 0) . " Total Impressions";
				$template_variables['impressions_packages'] .= "</span></div>";

				$retargeting_msg = $option['has_retargeting'] ? " (with Retargeting)" : "";
				$template_variables['impressions_packages'] .= "
					<div class=\"p6_subtext sub_text\">\n
					<div>Ad Creative Design and Production:<span class=\"p6_vars\"> $0</span></div>\n
					<div>Impressions Cost".$retargeting_msg.":<span class=\"p6_vars\">  $". number_format($total_cost_raw, 0) ."</span></div>\n
				";

				$discount_msg = "";
				if ($option['monthly_percent_discount'] != "0")
				{
					$discount_msg = " (including discount)";
					$discount_total = $total_cost_raw - $total_cost;

					$template_variables['impressions_packages'] .= "
						<div>".$period_type['adverb']." Subtotal: <span class=\"p6_vars\">$".number_format($total_cost_raw)."</span></div>
						<div>Less ".$option['monthly_percent_discount']."% Discount: <span class=\"p6_vars\">-$".number_format($discount_total)."</span></div>
					";
				}
				$template_variables['impressions_packages'] .= "<div>Cost Summary".$discount_msg.": <span class=\"p6_vars\">$".number_format($total_cost)."</span></div>";
				$template_variables['impressions_packages'] .= "</div><br /><br />";
			}
		}

		$template_variables["T_DATE"] = date("F j, Y",$data['valid_date']);

		if($data['show_pricing'] == 0)
		{
			$template_variables['toc_cost_summary'] = "";

			foreach($html->find('#p12_wrapper') as $el)
			{
				$el->outertext = '';
			}

		}


		// About Us
		if (isset($data['rep_id']))
		{
			if (!empty($data['proposal_logo_filepath']))
			{
				foreach($html->find('.partner_logo') as $logo)
				{
					$logo->src = $data['proposal_logo_filepath'];
				}
			}

			foreach($html->find('.partner_name') as $name)
			{
				$name->innertext = $data['pretty_partner_name'] ?: $data['partner_name'];
			}

			// About Us page
			$template_variables['rep_name'] = $data['firstname'] . " " . $data['lastname'] . "<br />";

			$template_variables['address_1'] = $data['address_1'];

			if (!empty($data['address_2']))
			{
				$template_variables['address_2'] = $data['address_2'] . "<br />";
			}

			$template_variables['city'] = $data['city'];
			$template_variables['state'] = $data['state'];
			$template_variables['zip'] = $data['zip'];
			$template_variables['partner_website'] = $data['home_url'];
			$template_variables['phone_number'] = $data['phone_number'];

			if (!empty($data['fax_number']))
			{
				$template_variables['fax_number'] = $data['fax_number'] . " (f)";
			}

			foreach($html->find('#about_vl_logo') as $logo)
			{
				$logo->src = "http://$proposals_domain/proposal_images/images/icon_proposal.png";
			}
		}


		// Appendix
		$template_variables['adplan_appendix_js'] = '
			var demos = '. json_encode($data['demographic_data']) .';

			var averages =
			{
				"male_population":			0.492,
				"female_population":		0.508,
				"age_under_18":				0.239,
				"age_18_24":				0.100,
				"age_25_34":				0.133,
				"age_35_44":				0.133,
				"age_45_54":				0.144,
				"age_55_64":				0.118,
				"age_65_and_over":			0.132,
				"white_population":			0.642,
				"black_population":			0.123,
				"asian_population":			0.048,
				"hispanic_population":		0.177,
				"other_race_population":	0.010,
				"kids_no":					0.667,
				"kids_yes":					0.333,
				"income_0_50":				0.477,
				"income_50_100":			0.302,
				"income_100_150":			0.127,
				"income_150":				0.093,
				"college_no":				0.642,
				"college_under":			0.254,
				"college_grad":				0.104,
			}

			$.each(demos, function(i, val)
			{
				$("#demos_" + val.lap_id + " span.target_population").text(val.region_population_formatted);
				$("#demos_" + val.lap_id + " span.income").text(val.income);
				$("#demos_" + val.lap_id + " span.MedianAge").text(val["MedianAge"]);
				$("#demos_" + val.lap_id + " span.HouseholdPersons").text(val["HouseholdPersons"]);
				$("#demos_" + val.lap_id + " span.HouseValue").text(val["HouseValue"]);
				$("#demos_" + val.lap_id + " span.BusinessCount").text(val["BusinessCount"]);

				for (var key in averages)
				{
					if (
						key == "income_0_50" ||
						key == "income_50_100" ||
						key == "income_100_150" ||
						key == "income_150" ||
						key == "kids_no" ||
						key == "kids_yes"
					)
					{
						sparkline(
							(((val[key] / val.total_households) * 100) / averages[key]),
							(Math.round((val[key] / val.total_households) * 1000) / 10),
							"#demos_" + i + " .sparkline_" + key,
							150
						);
					}
					else if (
						key == "white_population" ||
						key == "black_population" ||
						key == "asian_population" ||
						key == "hispanic_population" ||
						key == "other_race_population"
					)
					{
						sparkline(
							(((val[key] / val.normalized_race_population) * 100) / averages[key]),
							(Math.round((val[key] / val.normalized_race_population) * 1000) / 10),
							"#demos_" + i + " .sparkline_" + key,
							150
						);
					}
					else
					{
						sparkline(
							(((val[key] / val.region_population) * 100) / averages[key]),
							(Math.round((val[key] / val.region_population) * 1000) / 10),
							"#demos_" + i + " .sparkline_" + key,
							150
						);
					}
				}
			});
		';

		$demo_html = '
			<div class="demos_left">
				<div class="demo">
					<label>Population</label>
					<span class="target_population"></span>
				</div>
				<div class="demo">
					<label>Average Income</label>
					<span class="income"></span>
				</div>
				<div class="demo">
					<label>Median Age</label>
					<span class="MedianAge"></span>
				</div>
				<div class="demo">
					<label>People / Household</label>
					<span class="HouseholdPersons"></span>
				</div>
				<div class="demo">
					<label>Average Home Value</label>
					<span class="HouseValue"></span>
				</div>
				<div class="demo">
					<label>#of Businesses</label>
					<span class="BusinessCount"></span>
				</div>
			</div>

			<div class="demo_column" style="text-align:right; position:relative; border: 0px solid black;font-size: 13.3px;font-family: Oxygen, sans-serif;color: #414142;">

				<div class="DemographicGroup">
					<div class="DemographicGroupTitle" style="">Children In Household</div>
					<div class="InternetAverageCenterLine"></div>
					<div class="figure">
						<div class="sparkline_kids_no DemographicSparkline"></div>
						<figcaption class="DemographicRowName">No Kids:</figcaption>
					</div>
					<div class="figure">
						<div class="sparkline_kids_yes DemographicSparkline"></div>
						<figcaption class="DemographicRowName">Has Kids:</figcaption>
					</div>
				</div>

				<div class="DemographicGroup">
					<div class="DemographicGroupTitle" style="">Ethnicity</div>
					<div class="InternetAverageCenterLine"></div>
					<div class="figure">
						<div class="sparkline_white_population DemographicSparkline"></div>
						<figcaption class="DemographicRowName">Cauc:  </figcaption>
					</div>
					<div class="figure">
						<div class="sparkline_black_population DemographicSparkline"></div>
						<figcaption class="DemographicRowName">Afr Amer: </figcaption>
					</div>
					<div class="figure">
						<div class="sparkline_asian_population DemographicSparkline"></div>
						<figcaption class="DemographicRowName">Asian: </figcaption>
					</div>
					<div class="figure">
						<div class="sparkline_hispanic_population DemographicSparkline"></div>
						<figcaption class="DemographicRowName">Hisp: </figcaption>
					</div>
				</div>

				<div class="InternetAverageSubtext">US Average</div>
			</div>

			<div class="demo_column" style="text-align:right;position:relative; border: 0px solid black;font-size: 13.3px;font-family: Oxygen, sans-serif;color: #414142;">

				<div class="DemographicGroup">
					<div class="DemographicGroupTitle" style="">Household Income</div>
					<div class="InternetAverageCenterLine"></div>
					<div class="figure">
						<div class="sparkline_income_0_50 DemographicSparkline"></div>
						<figcaption class="DemographicRowName">&lt; $50k:</figcaption>
					</div>
					<div class="figure">
						<div class="sparkline_income_50_100 DemographicSparkline"></div>
						<figcaption class="DemographicRowName">$50k-100k:</figcaption>
					</div>
					<div class="figure">
						<div class="sparkline_income_100_150 DemographicSparkline"></div>
						<figcaption class="DemographicRowName">$100k-150k:</figcaption>
					</div>
					<div class="figure">
						<div class="sparkline_income_150 DemographicSparkline"></div>
						<figcaption class="DemographicRowName">$150k +:</figcaption>
					</div>
				</div>

				<div class="DemographicGroup" style="left:0px; top:0px;">
					<div class="DemographicGroupTitle" style="">Education Level</div>
					<div class="InternetAverageCenterLine"></div>
					<div class="figure">
						<div class="sparkline_college_no DemographicSparkline"></div>
						<figcaption class="DemographicRowName">No College:</figcaption>
					</div>
					<div class="figure">
						<div class="sparkline_college_under DemographicSparkline"></div>
						<figcaption class="DemographicRowName">College:</figcaption>
					</div>
					<div class="figure">
						<div class="sparkline_college_grad DemographicSparkline"></div>
						<figcaption class="DemographicRowName">Grad School:</figcaption>
					</div>
				</div>

				<div class="InternetAverageSubtext">US Average</div>
			</div>

			<div class="demo_column" style="text-align:right;position:relative;border: 0px solid black;font-family: Oxygen, sans-serif;	font-size: 13.3px; color: #333333;">

				<div class="DemographicGroup">
					<div class="DemographicGroupTitle" style="">Gender</div>
					<div class="InternetAverageCenterLine"></div>
					<div class="figure">
						<div class="sparkline_male_population DemographicSparkline"></div>
						<figcaption class="DemographicRowName">Male:</figcaption>
					</div>
					<div class="figure">
						<div class="sparkline_female_population DemographicSparkline"></div>
						<figcaption class="DemographicRowName">Female:</figcaption>

					</div>		</div>

				<div class="DemographicGroup">
					<div class="DemographicGroupTitle" style="">Age</div>
					<div class="InternetAverageCenterLine"></div>
					<div class="figure">
						<div class="sparkline_age_under_18 DemographicSparkline"></div>
						<figcaption class="DemographicRowName">&lt; 18:</figcaption>
					</div>
					<div class="figure">
						<div class="sparkline_age_18_24 DemographicSparkline"></div>
						<figcaption class="DemographicRowName">18 - 24:</figcaption>
					</div>
					<div class="figure">
						<div class="sparkline_age_25_34 DemographicSparkline"></div>
						<figcaption class="DemographicRowName">25 - 34:</figcaption>
					</div>
					<div class="figure">
						<div class="sparkline_age_35_44 DemographicSparkline"></div>
						<figcaption class="DemographicRowName">35 - 44:</figcaption>
					</div>
					<div class="figure">
						<div class="sparkline_age_45_54 DemographicSparkline"></div>
						<figcaption class="DemographicRowName">45 - 54:</figcaption>
					</div>
					<div class="figure">
						<div class="sparkline_age_55_64 DemographicSparkline"></div>
						<figcaption class="DemographicRowName">55 - 64:</figcaption>
					</div>
					<div class="figure">
						<div class="sparkline_age_65_and_over DemographicSparkline"></div>
						<figcaption class="DemographicRowName">65 +:</figcaption>
					</div>
				</div>

				<div class="InternetAverageSubtext">US Average</div>
			</div>
		';

		$template_variables['adplan_appendix'] = "";
		$unique_adplans = array();

		if ($data['single_adplan'])
		{
			foreach($html->find('.media_plan') as $el)
			{
				$el->innertext = '';
			}
		}
		else
		{
			foreach($data['options'] as $i => $option)
			{
				$option_name = $option['option_name'] ?: "Display Package " + $i;

				foreach($option['adplans'] as $adplan)
				{
					if (!array_key_exists($adplan['lap_id'], $unique_adplans))
					{
						$snapshot_title = $adplan['geo_snapshot_title'] ?: "GEOGRAPHIC SUMMARY";
						$adplan_string = "
							<div class=\"editable wrapper appendix-geo\" style=\"overflow:hidden\">
								<div class=\"heading_wrapper\">
									<div class=\"icon_holder\"><img src=\"http://$proposals_domain/proposal_images/images/icon_forms.png\"></div>

									<div class=\"heading_textholder\">
										".$adplan['plan_name']."<br />
										Geographic Summary
									</div>

									<div class=\"clear\"></div>
								</div>

								<div class=\"page-top\">
								</div><img src=\"".$adplan['geo_snapshot_link']."\" width=\"100%\">

								<div class=\"p3_geospan\">
									<span class=\"p3_geospantext\" style=\"font-weight: bold\">".$snapshot_title."</span>
								</div>

								<div style=\"margin-top: 100px;\">
									<div class=\"list_large_icons\" style=\"margin:0 15px 0px 62px;\"><img height=\"44\" src=\"http://$proposals_domain/proposal_images/icon_population.png\" width=\"46\"></div>
									<h2 style=\"margin-bottom:60px;padding-top:8px;\">DEMOGRAPHICS</h2>
									<div class=\"map_demos\" id=\"demos_".$adplan['lap_id']."\">$demo_html</div>
									<div class=\"clear\"></div>
								</div>
							</div>

							<div class=\"break\"></div>

							<div class=\"editable wrapper appendix-performance\">
								<div class=\"heading_wrapper\">
									<div class=\"icon_holder\"><img height=\"136\" src=\"http://$proposals_domain/proposal_images/images/icon_forms.png\" width=\"136\"></div>

									<div class=\"heading_textholder\">
										".$adplan['plan_name']."<br />
										Performance Estimates
									</div>

									<div class=\"clear\"></div>
								</div>
						";

						$unique_adplans[$adplan['lap_id']] = $adplan_string;
					}
					else
					{
						$unique_adplans[$adplan['lap_id']] .= "</table><img src=\"http://$proposals_domain/proposal_images/images/diagnols_1240px.jpg\" width=\"1000\" height=\"30\" style=\"display:block;margin:0px auto 30px auto;\">";
					}

					$unique_adplans[$adplan['lap_id']] .= "
						<div class=\"p5_headtext head_text\">
							$option_name
						</div>

						<table cellspacing=\"10\" class=\"p5_subtext sub_text\" width=\"80%\">
							<tr class=\"p5_disptr\">
								<th class=\"p5_trhead\" style=\"text-transform:uppercase;\">". $period_type_forms[$option['period_type']]['singular'] ."</th>

								<th class=\"p5_trhead\">IMPRESSIONS</th>

								<th class=\"p5_trhead\">REACH[%]</th>

								<th class=\"p5_trhead\">REACH</th>

								<th class=\"p5_trhead\">FREQUENCY</th>
							</tr>
						";

					foreach($adplan['rf_data'] as $i => $period)
					{
						$month = $i + 1;
						$unique_adplans[$adplan['lap_id']] .= "
							<tr>
								<td>".$month."</td>
								<td>".number_format($period['impressions'])."</td>
								<td>".number_format($period['reach_percent'] * 100, 1)."%</td>
								<td>".number_format($period['reach'], 0)."</td>
								<td>".number_format($period['frequency'], 1)."</td>
							</tr>
						";
					}
				}
			}

			$template_variables['adplan_appendix'] .= array_reduce($unique_adplans, function($carry, $item)
			{
				return $carry . "<div class=\"break\"></div>" . $item . "</table></div>";
			}, "");
			$template_variables['adplan_appendix'] .= "</div>";
		}

		// Go through all template variables and
		// render template
		foreach($template_variables as $key => $value)
		{
			foreach($html->find('#'.$key) as $span)
			{
				$span->innertext = $value;
			}
		}

		// Hide retargeting areas if necessary
		if (!$data['has_retargeting'])
		{
			foreach($html->find('.R_RETARGETING') as $el)
			{
				$el->outertext = '';
			}
		}

		// Return and unset the simple_html_dom object
		$str = $html->save();

		$html->clear();
		unset($html);

		return $str;
	}

	private function get_proposal_vldir_path()
	{
		$vldir_path = 'proposal';
		switch(ENVIRONMENT)
		{
		case 'production':
			break;
		case 'staging':
			$vldir_path .= '/stage';
			break;
		default:
			$vldir_path .= '/dev';
			break;
		}
		return $vldir_path;
	}

	public function load_proposal_display($prop_id)
	{
		$this->ftp->connect($this->config->item('proposal_snapshots_ftp_config'));  //connect to remote server

		$vldir_path = $this->get_proposal_vldir_path();

		$prop_list = $this->ftp->list_files('/'.$vldir_path.'/');
		if(array_key_exists("template", $_GET)) //if there is a custom template
		{
			$template = $_GET['template'];
			$template = substr($template, strrpos($template, '/') + 1);
		}
		else if(in_array('/'.$vldir_path.'/'.$prop_id.'.html', $prop_list)) //or if the prop id exists in the templates
		{
			$template = $prop_id.'.html';
		}
		else  //otherwise use the base template "000_base_template.html"
		{
			$template = "000_base_template.html";
		}
		$proposals_domain = $this->config->item('proposal_snapshots_ftp_domain');
		$webpage = "http://$proposals_domain/".$vldir_path.'/'.$template."";  //set link to remote html page

		if(in_array('/'.$vldir_path.'/'.$template, $prop_list))
		{
			$proposal_data = $this->proposal_gen_model->load_proposal_render_data($prop_id);  //get proposal data
			if ($proposal_data['is_success'])
			{
				$new_html = $this->process_remote_html($webpage, $proposal_data['results']);  //process the data and send it through to the view which displays the proposal with inserted data
				echo $new_html;
			}
			else
			{
				echo "Proposal data could not be loaded!";
				foreach($proposal_data['errors'] as $err)
				{
					echo $err . "<br />";
				}
			}
		}
		else  //else error
		{
			echo "Templating error: template '".$template."' Not found at '".$webpage."'";
		}
		$this->ftp->close();
	}

	public function control_panel($prop_id)
	{
		// redirect to specific proposal if prop_id is in URL
		$control_panel_url = '/proposal_builder/control_panel/';
		$control_panel_url .= $prop_id ?: '';
		$this->check_is_logged_in_media_planner_and_redirect($control_panel_url);

		//control panel will iframe the proposal, has a bar that has a few options that allow for proposal saving and changing
		$this->ftp->connect($this->config->item('proposal_snapshots_ftp_config'));
		$vldir_path = $this->get_proposal_vldir_path();
		$prop_list = $this->ftp->list_files('/'.$vldir_path.'/');
		$data['prop_list'] = array();
		foreach($prop_list as $v)
		{
			if(strpos($v, '.html'))  //only display files with .html on the end
			{
				array_push($data['prop_list'], $v);
			}
		}
		$data['prop_id'] = $prop_id;
		$this->load->view('generator/control', $data);
	}

	/**
	 * takes in a lap_id and generates a map_save_view context for that lap_id, passing the required data for image save to database.
	 *
	 */
	public function lap_image_gen($lap_id, $prop_id, $mpq_id = false, $location_id = null)
	{
		if(!isset($prop_id))
		{
			echo 'Proposal ID required.<br>Usage: ' . base_url('/edit_lap_images/lap_id/prop_id');
		}
		else
		{
			ini_set('memory_limit', '1024M');
			$data['lap_id'] = $lap_id;
			$data['location_id'] = '';
			$data['is_mpq'] = false;
			$data['is_auto_snapshot'] = false;
			$data['partner_ui_css_path'] = null;
			$data['snapshot_preferences'] = null;
			$data['map_tags'] = null;
			$data['is_overview_snapshot'] = false;

			$data['s3_assets_path'] = $this->config->item('s3_assets_path');

			$user_id = ($prop_id != 'false') ?
				$this->proposal_gen_model->get_proposal_owner_id_from_proposal_id($prop_id) :
				$this->mpq_v2_model->get_mpq_submitter_id_by_mpq_id($mpq_id);
			if($user_id !== false)
			{
				$partner_data = $this->tank_auth->get_partner_info($this->tank_auth->get_partner_id($user_id));
				$data['partner_ui_css_path'] = $partner_data['ui_css_path'];
				$data['snapshot_preferences'] = json_encode(array_filter($this->maps_model->get_mapbox_styles_by_proposal_id($prop_id)));
			}

			if($mpq_id) //We're generating an image for mpq
			{
				$region_details = $this->proposal_gen_model->get_name_and_regions_from_mpq_session($mpq_id, $location_id); //get regions from mpq_sessions_and_submissions
				$data['mpq_id'] = $mpq_id;
				$data['lap_id'] = $lap_id;
				$data['prop_id'] = $prop_id;
				$data['is_mpq'] = ($lap_id === 'false' && $prop_id === 'false');
				$data['is_auto_snapshot'] = true;

				$geofencing_points = $this->mpq_v2_model->get_geofencing_points_from_mpq_id_and_location_id($mpq_id, $location_id);
				$data['geofencing_points'] = ($geofencing_points) ? json_encode($geofencing_points) : null;

				if($location_id === null)
				{
					if(!$data['is_mpq'])
					{
						$data['map_tags'] = json_encode($region_details);
					}

					// Combine all regions into one array
					$region_details = array('zcta' => call_user_func_array('array_merge', array_column($region_details, 'zcta')));
					$data['user_supplied_name'] = 'Geographic Summary';
					$data['is_overview_snapshot'] = true;

					$data['lap_id'] = 'overview';
				}
				else
				{
					// Check that location id exists and region_details is an array
					if(is_array($region_details) && isset($region_details[(int)$location_id]))
					{
						$data['user_supplied_name'] = $region_details[$location_id]['user_supplied_name'];
						unset($region_details[$location_id]['user_supplied_name']);
						$region_details = $region_details[$location_id];
						$data['location_id'] = $location_id;
					}
				}
			}
			else
			{
				$data['mpq_id'] = false;
				$data['response'] = " ";
				if ($lap_id == 'overview')
				{
					$data['images'] = $this->proposal_gen_model->getLapImages(NULL, $prop_id);
					if(isset($data['images']))
					{
						if(array_key_exists("submit", $_POST))
						{
							$data['response'] = $this->proposal_gen_model->editLapImages(NULL, $prop_id, array(array('title' => $this->input->post('title', true))));
							$data['images'] = $this->proposal_gen_model->getLapImages(NULL, $prop_id);
						}
					}
					$region_details = $this->proposal_gen_model->get_zips_from_all_associated_prop_gen_sessions($prop_id);
				}
				else
				{
					$data['images'] = $this->proposal_gen_model->getLapImages($lap_id, $prop_id);
					if(isset($data['images']))
					{
						if(array_key_exists("submit", $_POST))
						{
							$data['response'] = $this->proposal_gen_model->editLapImages($lap_id, $prop_id, array(array('title' => $this->input->post('title', true))));
							$data['images'] = $this->proposal_gen_model->getLapImages($lap_id, $prop_id);
						}
					}
					$region_details = $this->proposal_gen_model->get_zips_from_prop_gen_session($lap_id);
				}
			}

			array_walk($region_details, function(&$region_id_array) {
				$region_id_array = array_values(array_unique($region_id_array));
			});
			$data['regions_to_show'] = json_encode($region_details);
			$shared_js_data = [
				'mapbox_access_token' => $this->config->item('mapbox_access_token'),
				'polygon_dots_ratio_cutoff' => $this->config->item('polygon_dots_ratio_cutoff')
			];
			$data['shared_js'] = $this->load->view('maps/map_shared_functions_js_v2', $shared_js_data, true);
			$data['prop_id'] = $prop_id;

			$this->load->view('generator/map_snapshots_for_mpq_lap_html', $data);
		}
	}

	public function rooftops_image_gen($prop_id, $mpq_id)
	{
		if(!isset($prop_id))
		{
			echo 'Proposal ID required.<br>Usage: ' . base_url('/edit_lap_images/lap_id/prop_id');
		}
		else
		{
			ini_set('memory_limit', '1024M');
			$data['lap_id'] = 'rooftops';
			$data['location_id'] = '';
			$data['is_mpq'] = false;
			$data['is_auto_snapshot'] = true;
			$data['partner_ui_css_path'] = null;
			$data['snapshot_preferences'] = null;
			$data['map_tags'] = null;

			$data['s3_assets_path'] = $this->config->item('s3_assets_path');

			$user_id = ($prop_id != 'false') ?
				$this->proposal_gen_model->get_proposal_owner_id_from_proposal_id($prop_id) :
				$this->mpq_v2_model->get_mpq_submitter_id_by_mpq_id($mpq_id);
			if($user_id !== false)
			{
				$partner_data = $this->tank_auth->get_partner_info($this->tank_auth->get_partner_id($user_id));
				$data['partner_ui_css_path'] = $partner_data['ui_css_path'];
				$data['snapshot_preferences'] = json_encode(array_filter($this->maps_model->get_mapbox_styles_by_proposal_id($prop_id)));
			}

			$data['mpq_id'] = false;
			$data['response'] = " ";

			$data['rooftops_to_show'] = json_encode($this->mpq_v2_model->get_rooftops_data($mpq_id));
			$shared_js_data = [
				'mapbox_access_token' => $this->config->item('mapbox_access_token'),
				'polygon_dots_ratio_cutoff' => $this->config->item('polygon_dots_ratio_cutoff')
			];
			$data['shared_js'] = $this->load->view('maps/map_shared_functions_js_v2', $shared_js_data, true);
			$data['prop_id'] = $prop_id;

			$this->load->view('generator/map_snapshots_for_rooftops_html', $data);
		}
	}

	/**
	 * this function takes in image data and a lap id and writes out the important information to the database so that it may be read later
	 * during the downstream generation of the proposal.
	 *
	 */
	public function save_lap_image($lap_id, $prop_id)
	{
		ini_set('post_max_size', '16M');
		$img_file = imagecreatefromstring(base64_decode(substr($this->input->post('img') ,22)));  //funky stuff here,
		$img_path = "{$lap_id}_{$prop_id}_" . date("Y-m-d:H-i-s") . '.png';
		$local_path = $_SERVER['DOCUMENT_ROOT'] . "/assets/proposal_pdf/{$img_path}";
		$vldir_path = $this->get_proposal_vldir_path();
		$remote_path = "/{$vldir_path}/img/{$img_path}";
		$proposals_domain = $this->config->item('proposal_snapshots_ftp_domain');
		$remote_link = "http://{$proposals_domain}/{$vldir_path}/img/{$img_path}";
		imagepng($img_file, $local_path, 9);
		$this->ftp->connect($this->config->item('proposal_snapshots_ftp_config'));
		if($this->ftp->upload($local_path, $remote_path, '', '775'))
		{
			if ($lap_id == 'overview')
			{
				$this->proposal_gen_model->write_prop_overview_image($prop_id, $remote_link, $this->input->post('title'));
			}
			elseif ($lap_id == 'rooftops')
			{
				$this->proposal_gen_model->write_rooftops_image($prop_id, $remote_link);
				echo "finished at: $remote_link";
			}
			else
			{
				$this->proposal_gen_model->write_lap_image($lap_id, $prop_id, $remote_link, $this->input->post('title'));
			}
			echo json_encode(array('is_success' => true, 'message' => "finished at: {$remote_link}"));
		}
		else
		{
			echo json_encode(array('is_success' => false, 'message' => "save FAILED!"));
		}
		$this->ftp->close();
		unlink($local_path);
	}

	public function edit_lap_images($lap_id, $prop_id) {
		if(!isset($prop_id))
		{
			echo "Proposal ID required.<br>Usage: ".base_url('/edit_lap_images/lap_id/prop_id');
		}
		else{
			$data['response'] = " ";
			$data['images'] = $this->proposal_gen_model->getLapImages($lap_id, $prop_id);
			if(isset($data['images']))
			{
				foreach($data['images'] as $v)
				{
					if(array_key_exists("delete".$v['snapshot_num'], $_POST))
					{
						$data['response'] = $this->proposal_gen_model->deleteLapImage($lap_id, $prop_id, $v['snapshot_num']);
					}
				}
				if(array_key_exists("submit", $_POST))
				{
					$arsubmit = array();
					for($i = 0; $i < count($_POST); $i++)
					{
						if(array_key_exists("num".$i, $_POST))
						{
							$arsubmit[$i] = array(
								"num" => $_POST["num".$i],
								"title" => $_POST["title".$i],
								"old_num" => $_POST["hidden".$i]
								);

						}
					}
					$continue = 1;
					foreach($arsubmit as $v)
					{
						$count = 0;
						if($v['num'] >= 9000)
						{
							$data['response'] = "Selected Number ".$v['num']." invalid";
							$continue = 0;
						}
						else
						{
							foreach($arsubmit as $r)
							{
								if($v['num'] == $r['num'])
								{
									$count++;
								}
							}
							if($count >= 2)
							{
								$data['response'] = 'Duplicate entries not allowed';
								$continue = 0;
							}
						}
					}

					if($continue == 1)
					{
						$data['response'] = $this->proposal_gen_model->editLapImages($lap_id, $prop_id, $arsubmit);
					}
				}
				$data['images'] = $this->proposal_gen_model->getLapImages($lap_id, $prop_id);
			}
			$data['lap_id'] = $lap_id;
			$data['prop_id'] = $prop_id;
			$this->load->view('generator/image_edit_view', $data);
		}
	}

    /**
     * Function to be called by option engine after proposal has been built to save data and finalize it so that it may be
     * loaded by the proposal builder into the final template, after which any final scraping can be done.
     *
     */
    public function save_proposal($prop_id = ""){
		$data['prop_name'] = $this->input->post('name');
		$data['prop_data'] = $this->input->post('data');
		$data['show_pricing'] = $this->input->post('show_pricing');
		$data['rep_id'] = $this->input->post('rep_id') ?: null;
		$id = $this->proposal_gen_model->saveProposal($data, $prop_id);
		echo json_encode($id);
	}

	/// Loads the selected lap into the active media_plan_session.
	/// @param if force_lap_id is an empty string, create a new lap.
	public function force_session($force_lap_id = "")
	{
		$session_id = $this->session->userdata('session_id');
		$lap_title = $force_lap_id;
		if($force_lap_id == "" || $force_lap_id == 0)
		{
			$owner_id = $this->tank_auth->get_user_id($session_id);
			$this->lap_lite_model->create_new_lap($session_id, $owner_id);

			$lap_title = 'new lap';
		}
		else
		{
			$this->proposal_gen_model->load_to_media_sessions($session_id, $force_lap_id);
		}

		echo('<!DOCTYPE html><html><head><meta http-equiv="Refresh" content="0;url=/planner" /></head><body>Loading lap: '.$lap_title.', Loading session: '.$session_id.'</body></html>');
	}

	/**
	 * this is the core function for building the options within the ad framework
	 *
	 */
	public function option_engine($force_prop_id = null, $mpq_id = null, $mpq_action = null)
	{
		// redirect to specific proposal if prop_id is in URL
		$option_engine_url = '/proposal_builder/option_engine/';
		$option_engine_url .= $force_prop_id ?: '';
		$this->check_is_logged_in_media_planner_and_redirect($option_engine_url);

		$data = array();
		if($force_prop_id == null)
		{
			$data['name']= "test";
			$data['is_saved'] = "not_saved";
			$data['saved_json'] = "test";
			$data['prop_id'] = $force_prop_id;

			$this->load->view('generator/option_engine_body', $data);
		}
		else
		{
			// then we have a proposal that we want to force.
			// we are going to ***ASSUME*** that said proposal exists and load its data, then pass to our option_engine_body view.
			if($force_prop_id == 'create_from_mpq')
			{
				if($mpq_id == null)
				{
					die('missing mpq_id for option_engine create from mpq (#074330)');
				}

				$session_id = $this->session->userdata('session_id');
				$new_prop_id = 0;
				$user_id = $this->tank_auth->get_user_id($session_id);
				$create_proposal_response = $this->mpq_v2_model->create_proposal_from_mpq($new_prop_id, $mpq_id, $user_id);

				$force_prop_id = $new_prop_id;
				if($create_proposal_response['is_success'] == true)
				{
					if($create_proposal_response['mpq_type'] == 'proposal')
					{
						//insert site list automatically, TODO
						$auto_sites_array=$this->siterankpoc_model->get_sites_for_mpq($mpq_id, null, null, null, null, null, null, null, false, true, $product_type_flag);
						if ($auto_sites_array != "" && count($auto_sites_array) > 0 && $force_prop_id != "")
						{
							$sites_string=json_encode($auto_sites_array);
							$this->proposal_gen_model->save_proposal_sites($sites_string, $force_prop_id);
						}
						redirect('/proposal_builder/option_engine/'.$force_prop_id);
					}
					else if($create_proposal_response['mpq_type'] == 'insertion order')
					{
						redirect('/proposal_builder/edit_sitelist/'.$force_prop_id);
					}
					else
					{
						echo "Error: Unknown mpq type for mpq ".$force_prop_id;
					}
				}
			}
			else if($force_prop_id == 'create_from_mpq_with_action')
			{
				if($mpq_id == null)
				{
					die('missing mpq_id for option_engine create from mpq (#084430)');
				}
				else if($mpq_action == null)
				{
					die('missing action for option_engine create from mpq (#084440)');
				}

				$session_id = $this->session->userdata('session_id');
				$new_prop_id = 0;
				$user_id = $this->tank_auth->get_user_id($session_id);
				$create_proposal_response = $this->mpq_v2_model->create_proposal_from_mpq($new_prop_id, $mpq_id, $user_id);
				$force_prop_id = $new_prop_id;
				if($create_proposal_response['is_success'] == true)
				{
					if($create_proposal_response['mpq_type'] == 'proposal')
					{
						redirect('/proposal_builder/option_engine/'.$force_prop_id);
					}
					else if($create_proposal_response['mpq_type'] == 'insertion order')
					{
						if($mpq_action == 'download_geo')
						{
							redirect('/proposal_builder/export_geos/'.$force_prop_id.'/false/'.$mpq_id.'/');
						}
						if($mpq_action == 'planner')
						{
							$lap_response = $this->mpq_v2_model->get_lap_id_by_source_mpq($mpq_id);
							if($lap_response)
							{
								$this->force_session($lap_response);
							}
							else
							{
								echo "Error: could not find associated lap for mpq: ".$mpq_id;
								return;
							}
						}
						else
						{
							redirect('/proposal_builder/edit_sitelist/'.$force_prop_id);
						}
					}
					else
					{
						echo "Error: Unknown mpq type for mpq ".$force_prop_id;
						return;
					}
				}
			}
			else
			{
				$proposal_data = $this->mpq_v2_model->get_load_proposal_data($force_prop_id);
				foreach($proposal_data['options'] as &$option)
				{
					$total_impressions = 0;
					foreach($option['laps'] as &$lap)
					{
						$impressions = array();
						$i = 1;
						while($i <= $lap['term'])
						{
							$impressions[] = $lap['impressions'] * $i;
							$i++;
						}

						$lap['pretty_population'] = number_format($lap['population']);
						$lap['pretty_demo_population'] = number_format($lap['demo_population']);

						$lap['reach_frequency_table'] = $this->rf_model->calculate_reach_and_frequency(
							$impressions,
							$lap['population'],
							$lap['demo_population'],
							$lap['ip_accuracy'],
							$lap['demo_coverage'],
							$lap['gamma'],
							$lap['retargeting']
						);

						foreach($lap['reach_frequency_table'] as &$term)
						{
							$term['impressions'] = number_format($term['impressions']);
							$term['reach'] = number_format($term['reach'], 0);
							$term['reach_percent'] = number_format($term['reach_percent'] * 100, 1);
							$term['frequency'] = number_format($term['frequency'], 1);
						}

						$total_impressions += $lap['impressions'];
					}
					$option['total_impressions'] = number_format($total_impressions, 0);
					$option['total_cpm'] = number_format(($option['monthly_raw_cost'] / $total_impressions) * 1000, 4);
				}

				$proposal_json = json_encode($proposal_data);

				$data['saved_json'] = addslashes($proposal_json);
				$data['is_saved'] = "saved";
				$data['name'] = "test";  //DONT CHANGE THIS
				$data['prop_id'] = $force_prop_id;
				// this procedure does return the correct data in JSON form for the proposal generator.

				$this->load->view('generator/option_engine_body', $data);
			}
		}
	}

	public function get_all_proposals()
	{
		$this->check_is_logged_in_media_planner_and_redirect('proposal_builder/get_all_proposals');
		$start = $this->input->get('start');
		$limit = $this->input->get('limit');
		if($start === false OR !is_numeric($start) OR $start < 1)
		{
			$start = 1;
		}
		if($limit === false OR !is_numeric($limit))
		{
			$limit = 1000;
		}
		if($limit < 1)
		{
			$limit = 0;
		}

		$data['start'] = $start;
		$data['limit'] = $limit;
		$data['proposals'] = array();
		$data['options'] = array();

		$proposal_response = $this->proposal_gen_model->get_all_proposals_and_options($start - 1, $limit);
		$data['proposals'] = $proposal_response['prop_list'];
		$data['options'] = $proposal_response['option_list'];

		$this->load->view('generator/all_proposals_body', $data);
	}

	public function get_all_mpqs_insertion_orders()
	{
		$this->check_is_logged_in_media_planner_and_redirect('proposal_builder/get_all_mpqs/io');
		$start = $this->input->get('start');
		$limit = $this->input->get('limit');
		if($start === false OR !is_numeric($start) OR $start < 1)
		{
			$start = 1;
		}
		if($limit === false OR !is_numeric($limit))
		{
			$limit = 1000;
		}
		if($limit < 1)
		{
			$limit = 0;
		}

		$data['start'] = $start;
		$data['limit'] = $limit;

		$data['mpqs_data'] = $this->proposal_gen_model->get_submitted_mpqs_insertion_orders($start - 1, $limit);
		//$this->load->view('generator/all_mpqs_body', $data);

		///setup common header
		$username = $this->tank_auth->get_username();
    	$role = $this->tank_auth->get_role($username);
		$data['username']   = $username;
		$data['firstname']  = $this->tank_auth->get_firstname($data['username']);
		$data['lastname']   = $this->tank_auth->get_lastname($data['username']);
		$data['user_id']    = $this->tank_auth->get_user_id();

		$data['title'] = 'All MPQs';
		$data['active_menu_item'] = "all_mpqs_menu";

		$this->load->view('ad_linker/header',$data);
		$this->load->view('generator/all_mpqs_header',$data);
		$this->load->view('generator/all_mpqs_io_body',$data);
		$this->load->view('generator/all_mpqs_js',$data);
	}

	public function get_all_mpqs_proposals()
	{
		$this->check_is_logged_in_media_planner_and_redirect('proposal_builder/get_all_mpqs/proposals');
		$start = $this->input->get('start');
		$limit = $this->input->get('limit');
		if($start === false OR !is_numeric($start) OR $start < 1)
		{
			$start = 1;
		}
		if($limit === false OR !is_numeric($limit))
		{
			$limit = 1000;
		}
		if($limit < 1)
		{
			$limit = 0;
		}

		$data['start'] = $start;
		$data['limit'] = $limit;

		$data['mpqs_data'] = $this->proposal_gen_model->get_submitted_mpqs_proposals($start - 1, $limit);
		//$this->load->view('generator/all_mpqs_body', $data);

		///setup common header
		$username = $this->tank_auth->get_username();
    	$role = $this->tank_auth->get_role($username);
		$data['username']   = $username;
		$data['firstname']  = $this->tank_auth->get_firstname($data['username']);
		$data['lastname']   = $this->tank_auth->get_lastname($data['username']);
		$data['user_id']    = $this->tank_auth->get_user_id();

		$data['title'] = 'All MPQs';
		$data['active_menu_item'] = "all_mpqs_menu";

		$this->load->view('ad_linker/header',$data);
		$this->load->view('generator/all_mpqs_header',$data);
		$this->load->view('generator/all_mpqs_proposals_body',$data);
		$this->load->view('generator/all_mpqs_js',$data);
	}

	public function get_all_mpqs_rfps()
	{
		$this->check_is_logged_in_media_planner_and_redirect('proposal_builder/get_all_mpqs/rfp');
		$start = $this->input->get('start');
		$limit = $this->input->get('limit');
		if($start === false OR !is_numeric($start) OR $start < 1)
		{
			$start = 1;
		}
		if($limit === false OR !is_numeric($limit))
		{
			$limit = 100;
		}
		if($limit < 1)
		{
			$limit = 0;
		}

		$data['start'] = $start;
		$data['limit'] = $limit;

		$data['mpqs_data'] = $this->proposal_gen_model->get_submitted_mpqs_rfps($start - 1, $limit);

		foreach ($data['mpqs_data'] as &$mpq)
		{
			$mpq['pdf_title'] = $this->proposal_generator->get_proposal_pdf_name($mpq["prop_id"], $mpq["advertiser_name"], $mpq["creation_time"]);
		}

		///setup common header
		$username = $this->tank_auth->get_username();
    	$role = $this->tank_auth->get_role($username);
		$data['username']   = $username;
		$data['firstname']  = $this->tank_auth->get_firstname($data['username']);
		$data['lastname']   = $this->tank_auth->get_lastname($data['username']);
		$data['user_id']    = $this->tank_auth->get_user_id();

		$data['title'] = 'All Instant Proposals';
		$data['active_menu_item'] = "all_mpqs_menu";

		$this->load->view('ad_linker/header',$data);
		$this->load->view('generator/all_mpqs_header',$data);
		$this->load->view('generator/all_mpqs_rfp_body',$data);
		$this->load->view('generator/all_mpqs_js',$data);
	}

	/**
	 * The individual framework for editing a single option.
	 *
	 */
	public function edit_option($option_name){
		$data['option_name'] = $option_name;
		$this->load->view('generator/edit_single_option_body', $data);
	}

	/**
	 * puts out a view of all the laps currently within the prop_gen_sessions table
	 *
	 */
	public function get_all_laps()
	{
		$this->check_is_logged_in_media_planner_and_redirect('proposal_builder/get_all_laps');
		$result_array = $this->proposal_gen_model->loadLaps();
		$data['all_laps_data'] = $result_array;
		$this->load->view('generator/all_laps_body', $data);
	}

	public function get_valid_proposals()
	{
		$sitePackCategories = $this->smb_model->getMediaTargetingSitePackCategories();
		$data['sitePackCategories'] = $sitePackCategories;

		$realSitePackName = $sitePackCategories->row(0)->sitePackName;
		$demoResponse = $this->smb_model->getDemoSitePack($realSitePackName);
		$data['demoResponse'] = $demoResponse;
		$channelResponse = $this->smb_model->getChannelSitePack($realSitePackName);
		$data['channelResponse'] = $channelResponse;
		$sessionId = $this->session->userdata('session_id');
		$population = $this->smb_model->get_population($sessionId);
		$data['population'] = $population;

		$realizedValueResponse = $this->smb_model->getRealizedValue($realSitePackName);
		$data['realizedValueResponse'] = $realizedValueResponse;
		$internetStandardDeviation = $this->smb_model->getInternetStandardDeviation();
		$data['internetStandardDeviation'] = $internetStandardDeviation;
		$getInternetMean = $this->smb_model->getInternetMean();
		$data['getInternetMean'] = $getInternetMean;
		$this->load->view('generator/proposal_gen_body', $data);
	}

	public function create_pdf($id = "", $remote="no")
	{
		if($remote == "yes")
		{
			$vldir_path = $this->get_proposal_vldir_path();
			$proposals_domain = $this->config->item('proposal_snapshots_ftp_domain');
			$url = "http://$proposals_domain/".$vldir_path.'/'.$id.".html";
		}
		else
		{
			$url = base_url('proposal_builder/load_proposal_display/'.$id);
		}
		if (strlen($id) > 0)
		{

			$exec_str = 'wkhtmltopdf -L 0 -R 0 -B 0 -T 0 --javascript-delay 1000 --enable-javascript --no-stop-slow-scripts --page-height 1700 '.escapeshellarg($url).' '.$_SERVER["DOCUMENT_ROOT"].'/assets/proposal_pdf/proposal.pdf 2>&1';
			$output = shell_exec($exec_str);
			$error = strpos($output, "QPainter::");
			if($error)
			{
				echo "ERROR DETECTED: ".substr($output, $error);
			}
			else
			{
				echo '<a target="_blank" href="'.base_url("assets/proposal_pdf/proposal.pdf").'">Click here to view your PDF</a>';
			}
		}
		else if(array_key_exists('load', $_POST))
		{
			$exec_str = 'wkhtmltopdf -L 0 -R 0 -B 0 -T 0 --javascript-delay 1000 --enable-javascript --no-stop-slow-scripts --page-height 1700 '.escapeshellarg($_POST['url']).' '.$_SERVER["DOCUMENT_ROOT"].'/assets/proposal_pdf/proposal.pdf 2>&1';
			$output = shell_exec($exec_str);
			$error = strpos($output, "QPainter::");
			if($error)
			{
				echo "ERROR DETECTED: ".substr($output, $error);
			}
			else
			{
				//echo $output;
				//echo "<br>".$exec_str;
				header('Content-disposition: attachment; filename=proposal.pdf');
				header('Content-type: application/pdf');
				readfile($_SERVER["DOCUMENT_ROOT"].'/assets/proposal_pdf/proposal.pdf');
			}
			//$this->load->view('generator/create_pdf');
		}
		else if(array_key_exists('preview', $_POST))
		{
			redirect($_POST['url']);
		}
		else
		{
			$this->load->view('generator/create_pdf');
		}
	}

	private function proposal_parse($location)
	{
		$ch = curl_init($location);   //initialize curl with the scraped page at '$location'
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; InfoPath.2; .NET CLR 2.0.50727; .NET CLR 3.0.04506.648; .NET CLR 3.5.21022; .NET CLR 1.1.4322)2011-10-16 20:22:33');
		$scraped_page = curl_exec($ch);  //The Page that has been scraped by curl
		curl_close($ch);
		return $scraped_page;
	}
	public function insert_proposal_sites($prop_id)
	{
		if($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			$return = array('is_success' => true, 'errors' => '');
			$raw_site_array = $this->input->post('sites');
			if($raw_site_array)
			{
				if($this->proposal_gen_model->save_proposal_sites($raw_site_array, $prop_id))
				{
					$return['updated'] = true;
				}
				else
				{
					$return['updated'] = false;
				}
			}
			else
			{
				$return['is_success'] = false;
				$return['errors'] = 'Error 34423091: No sites found for saving';
			}
			echo json_encode($return);
		}
		else
		{
			show_404();
		}
	}

	public function get_site_packs()
	{
		$return_string = "";
		$packs = $this->proposal_gen_model->get_pack_names();
		if(is_array($packs))
		{
			foreach($packs as $v)
			{
				$return_string .= '<option value="'.$v["ID"].'">'.$v["Name"].'</option>';
			}
		}
		return $return_string;
	}

	public function get_pack_sites($id)
	{
		$raw_pack_sites = $this->proposal_gen_model->get_sites_by_pack($id);
		if($raw_pack_sites)
		{
			$pack_sites = json_decode($raw_pack_sites);
			$pack_site_list = "";
			foreach($pack_sites as $v)
			{
				if($v[0] == 'break_tag')
				{
					$value_string = "break_tag";
					$pack_site_list .= "<option value='".$value_string."'>".$v[1]."</option>";
				}
				else if($v[0] == 'header_tag')
				{
					$value_string = "header_tag";
					$pack_site_list .= "<option  value='".$value_string."'>".$v[1]."</option>";
				}
				else
				{
					$value_string = $v[0]."|||".$v[1]."|".$v[2]. "|";
					$value_string .= $v[3]."|".$v[4]."|".$v[5]."|";
					$value_string .= $v[6]."|".$v[7]."|".$v[8]."|".$v[9];
					$value_string .= "|".$v[10]."|".$v[11]."|".$v[12];
					$value_string .= "|".$v[13]."|".$v[14]."|".$v[15]."|".$v[16];
					$value_string .= "|".$v[17]."|".$v[18]."|".$v[19];
					$value_string .= "|".$v[20]."|".$v[21]."|".$v[22]."|";
					$value_string .= $v[23]."|".$v[24]."|";

					$pack_site_list .= "<option value='".$value_string."'>".$v[0]."</option>";
				}
			}

			echo $pack_site_list;
			return;
		}
		echo "FAIL";
	}

	public function update_create_site_pack($id="")
	{
		$site_array = $_POST['arrjson'];
		$name = $_POST['packname'];
		$return_id = $this->proposal_gen_model->save_site_pack($name, $site_array, $id);
		if($return_id)
		{
			if(is_numeric($return_id))
			{
				echo $return_id;
			}
			else
			{
				echo "true";
			}
		}
		else
		{
			echo "false";
		}
	}

	public function rf_lap_values($lap_id)
	{
		$ret = $this->proposal_gen_model->get_prop_lap_data($lap_id);
		$return_array = array('ip_accuracy' => (is_numeric($ret['rf_ip_accuracy']) ? $ret['rf_ip_accuracy'] : '0.99'),
							  'gamma' => (is_numeric($ret['rf_gamma']) ? $ret['rf_gamma'] : '0.3'),
							  'geo_coverage' => (is_numeric($ret['rf_geo_coverage']) ? $ret['rf_geo_coverage'] : '0.87'),
							  'demo_coverage' => (is_numeric($ret['rf_demo_coverage']) ? $ret['rf_demo_coverage'] : '0.87'));
		echo json_encode($return_array);
	}

	public function rf_calc($lap_id)
	{
		$term = $_POST['term'];
		$impressions = $_POST['impressions'];
		$gamma = $_POST['gamma'];
		$ip_accuracy = $_POST['ip_accuracy'];
		$geo_coverage = $_POST['geo_coverage'];
		$demo_coverage = $_POST['demo_coverage'];
		$retargeting = $_POST['retargeting'];
		$ret = $this->proposal_gen_model->get_prop_lap_data($lap_id);
		$rf_array = array();

		for($i = 1; $i <= $term; $i++)
		{
			$rf_array[$i] = $this->proposal_gen_model->getReachFrequency($ret['population'], $ret['demo_population'], $i, $impressions, $geo_coverage, $gamma, $ip_accuracy, $demo_coverage, $ret['internet_average'], $lap_id, $retargeting);
		}
		echo json_encode($rf_array);
	}

	public function export_geos($prop_id, $option_id, $insertion_order_id = NULL)
	{
		if($insertion_order_id != NULL)
		{
			$geo_json = $this->proposal_gen_model->get_regions_by_insertion_order($insertion_order_id);
			if(!$geo_json)
			{
				echo "No regions found for mpq ". $insertion_order_id ." (#1130402)";
				return;
			}
		}
		else if($option_id != "false" AND $insertion_order_id == NULL)
		{
			$geo_json = $this->proposal_gen_model->get_regions_by_prop_option($prop_id, $option_id);
		}
		else
		{
			echo "Incorrect Parameters to export_geos (#1203113)";
			return;
		}
		if($geo_json and $geo_json['region_data'] != NULL)
		{
			$geo_array = json_decode($geo_json['region_data'], true);
			$this->map->convert_old_flexigrid_format($geo_array);
			$objPHPExcel = new PHPExcel();

			$objPHPExcel->getProperties()->setCreator("Vantage Local")
				->setLastModifiedBy("Vantage Local")
				->setTitle("Geographic Data for Proposal ". $prop_id ." Option ". $option_id ."")
				->setSubject("Geographic Data for Proposal ". $prop_id ." Option ". $option_id ."")
				->setDescription("Geographic Data");

			$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue('A1', 'Country Code')
				->setCellValue('B1', '5 Digit Zip Code');
			$j = 2;
			foreach($geo_array['ids'] as $region_type => $regions)
			{
				if($region_type == 'zcta')
				{
					foreach($regions as $v)
					{
						$objPHPExcel->setActiveSheetIndex(0)
							->setCellValue('A'.$j.'', 'US')
							->setCellValue('B'.$j.'', $v);
						$j++;
					}
				}
			}
			$objPHPExcel->getActiveSheet()->setTitle("ZipCodes");

			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="Geos_'. url_title($geo_json['advertiser']) .'_'. date('dmy_Hi') .'.xlsx"');
			header('Cache-Control: max-age=0');

			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
			$objWriter->save('php://output');
		}
	}

	public function export_geos_by_region_id($mpq_id, $region_id)
	{
		$advertiser_name = $this->mpq_v2_model->get_advertiser_name_on_mpq($mpq_id);

		if(!$region_data_json = $this->mpq_v2_model->get_region_data_by_mpq_id($mpq_id))
		{
			echo 'Error #872957: no region data on this MPQ';
		}
		if(!$mpq_region_data = json_decode($region_data_json, true))
		{
			echo 'Error #872958: region data is not valid JSON';
		}
		if(!array_key_exists(intval($region_id), $mpq_region_data))
		{
			echo 'Error #872959: region id not present in region data';
		}
		$this->map->convert_old_flexigrid_format($mpq_region_data);
		$region_data = $mpq_region_data[intval($region_id)];

		$objPHPExcel = new PHPExcel();

		$document_title = "Geographic Data for Proposal from MPQ $mpq_id region $region_id";
		$objPHPExcel->getProperties()->setCreator("Frequence")
			->setLastModifiedBy("Frequence")
			->setTitle($document_title)
			->setSubject($document_title)
			->setDescription("Geographic Data");

		$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A1', 'Country Code')
			->setCellValue('B1', '5 Digit Zip Code');
		$j = 2;
		foreach($region_data['ids'] as $region_type => $regions)
		{
			if($region_type == 'zcta')
			{
				foreach($regions as $v)
				{
					$objPHPExcel->setActiveSheetIndex(0)
						->setCellValue('A'.$j.'', 'US')
						->setCellValue('B'.$j.'', $v);
					$j++;
				}
			}
		}
		$objPHPExcel->getActiveSheet()->setTitle("ZipCodes");

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="Geos_'. url_title($advertiser_name) .'_'. date('dmy_Hi') .'.xlsx"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
	}

    public function export_sites($prop_id)
    {
    	$site_json = $this->proposal_gen_model->get_sitelist($prop_id);
    	if($site_json and $site_json['site_list'] != NULL)
    	{
    		$site_array = json_decode($site_json['site_list']);
    		$site_array_urls=array();
    		foreach($site_array as $v)
    		{
    			if($v[0] != 'header_tag' AND $v[0] != 'break_tag')
    			{
    				$site_array_urls[]=$v[0];
    			}
    		}
    		export_sites($site_array_urls, url_title($site_json['prop_name']), $prop_id);
    	}
    	else
    	{
    		echo "No sites found for mpq ". $prop_id ." (#1130404)";
    	}
    }

    public function edit_sitelist($prop_id)
    {
		$this->check_is_logged_in_media_planner_and_redirect('proposal_builder/edit_sitelist/'.$prop_id);
		$data['prop_id'] = $prop_id;
		$data['site_list'] = '';
		$data['channel_site_list'] = '';
		$existing_sites = $this->proposal_gen_model->get_existing_proposal_sites($prop_id);
		$data['pack_options'] = $this->get_site_packs();
		$data['existing_string'] = "";
		$data['media_targeting_tags'] = "";
		if($existing_sites)
		{
			$i = 0;
			$existing_array = json_decode($existing_sites);
			foreach($existing_array as $v)
			{
				if($v[0] == 'header_tag')
				{
					$data['existing_string'] .= '<tr class="ui-state-default ui-selectee">'."\n";
					$data['existing_string'] .='<td class="sort" style="width:37px;"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span></td>'."\n";
					$data['existing_string'] .='<td class="a_header" style="width:200px;font-weight:bold;">'.$v[1].'</td> <td colspan="25" style="width:950px;"></td></tr>'."\n";
				}
				else if ($v[0] == 'break_tag')
				{
					$data['existing_string'] .= '<tr class="ui-state-default ui-selectee">'."\n";
					$data['existing_string'] .='<td class="sort" style="width:37px;"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span></td>'."\n";
					$data['existing_string'] .='<td class="a_break" style="width:200px;">(break tag)</td> <td colspan="25" style="width:950px;"></td></tr>'."\n";
				}
				else //its a site
				{
					$data['existing_string'] .= '<tr class="togl ui-state-default ui-selectee">'."\n";
					$data['existing_string'] .= '<td class="sort" style="width:37px;"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span></td>'."\n";
					$data['existing_string'] .= '<td class="a_site" style="width:200px;word-wrap:break-word;"><div style="width:187px;word-wrap:break-word;">'.$v[0].'</div></td>'."\n";
					for($i = 1; $i < count($v); $i++)
					{
						$data['existing_string'] .= '<td style="width:40px;">'.$v[$i].'</td>'."\n";
					}
					$data['existing_string'] .= '</tr>';
				}
			}
		}
		$channel_sites = $this->proposal_gen_model->get_proposal_channel_sites($prop_id);

		$site_list = $this->proposal_gen_model->get_proposal_targeted_sites($prop_id);
		foreach($site_list as $v)
		{
			$value_string = $v['Site']."|||".number_format($v['Reach'], 4)."|".number_format($v['Gender_Male'], 2). "|";
			$value_string .= number_format($v['Gender_Female'], 2)."|".number_format($v['Age_Under18'], 2)."|".number_format($v['Age_18_24'], 2)."|";
			$value_string .= number_format($v['Age_25_34'], 2)."|".number_format($v['Age_35_44'], 2)."|".number_format($v['Age_45_54'], 2)."|".number_format($v['Age_55_64'], 2);
			$value_string .= "|".number_format($v['Age_65'], 2)."|".number_format($v['Race_Cauc'], 2)."|".number_format($v['Race_Afr_Am'], 2);
			$value_string .= "|".number_format($v['Race_Asian'], 2)."|".number_format($v['Race_Hisp'], 2)."|".number_format($v['Race_Other'], 2)."|".number_format($v['Kids_No'], 2);
			$value_string .= "|".number_format($v['Kids_Yes'], 2)."|".number_format($v['Income_0_50'], 2)."|".number_format($v['Income_50_100'], 2);
			$value_string .= "|".number_format($v['Income_100_150'], 2)."|".number_format($v['Income_150'], 2)."|".number_format($v['College_No'], 2)."|";
			$value_string .= number_format($v['College_Under'], 2)."|".number_format($v['College_Grad'], 2)."|";

			$data['site_list'] .= "<option value='".$value_string."'>".$v['Site']."</option>";

		}
		if(!is_array($channel_sites))
		{
			$channel_sites = array();
		}
		foreach($channel_sites as $v)
		{
			$value_string = $v['Site']."|||".number_format($v['Reach'], 4)."|".number_format($v['Gender_Male'], 2). "|";
			$value_string .= number_format($v['Gender_Female'], 2)."|".number_format($v['Age_Under18'], 2)."|".number_format($v['Age_18_24'], 2)."|";
			$value_string .= number_format($v['Age_25_34'], 2)."|".number_format($v['Age_35_44'], 2)."|".number_format($v['Age_45_54'], 2)."|".number_format($v['Age_55_64'], 2);
			$value_string .= "|".number_format($v['Age_65'], 2)."|".number_format($v['Race_Cauc'], 2)."|".number_format($v['Race_Afr_Am'], 2);
			$value_string .= "|".number_format($v['Race_Asian'], 2)."|".number_format($v['Race_Hisp'], 2)."|".number_format($v['Race_Other'], 2)."|".number_format($v['Kids_No'], 2);
			$value_string .= "|".number_format($v['Kids_Yes'], 2)."|".number_format($v['Income_0_50'], 2)."|".number_format($v['Income_50_100'], 2);
			$value_string .= "|".number_format($v['Income_100_150'], 2)."|".number_format($v['Income_150'], 2)."|".number_format($v['College_No'], 2)."|";
			$value_string .= number_format($v['College_Under'], 2)."|".number_format($v['College_Grad'], 2)."|";

			$data['channel_site_list'] .= "<option value='".$value_string."'>".$v['Site']."</option>";
		}
		$existing_tags = $this->proposal_gen_model->get_existing_media_targeting_tags_by_prop_id($prop_id);
		if($existing_tags)
		{
			$data['media_targeting_tags'] = "[";
			foreach($existing_tags as $v)
			{
				if($data['media_targeting_tags'] != "[")
				{
					$data['media_targeting_tags'] .= ", ";
				}
				$data['media_targeting_tags'] .= "{id: ".$v['id'].", text: \"".$v['tag_copy']."\", tag_type: ".$v['tag_type']."}";
			}
			$data['media_targeting_tags'] .= "]";
		}

		$data['site_list'] = trim($data['site_list']);
		$data['channel_site_list'] = trim($data['channel_site_list']);
		$industry_data=$this->proposal_gen_model->get_industry_from_proposal($prop_id);
		$data["industry_data_id"]=$industry_data["id"];
		$data["industry_data_text"]=$industry_data["text"];
		$this->load->view('generator/edit_sites', $data);
	}

	public function save_insertion_order()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$json_array = array('error' => 0, 'error_text' => "");
			$inputs = array();

			if($this->session->userdata('session_id'))
			{
				$inputs['session_id'] = $this->session->userdata('session_id');
			}
			else
			{
				$json_array['error'] = 1;
				$json_array['error_text'] = "session id expired.  Please refresh the page.";
				echo json_encode($json_array);
				return;
			}

			if(array_key_exists("impressions", $_POST) AND array_key_exists("start_date", $_POST) AND
			   array_key_exists("landing_page", $_POST) AND array_key_exists("is_retargeting", $_POST) AND
			   array_key_exists("end_date", $_POST) AND array_key_exists("term_type", $_POST) AND array_key_exists("term_duration", $_POST) AND
			   array_key_exists("advertiser_info", $_POST) AND array_key_exists("requester_info", $_POST))
			{
				$impressions = $this->input->post('impressions');
				$start_date = $this->input->post('start_date');
				$landing_page = $this->input->post('landing_page');

				$is_retargeting = $this->input->post('is_retargeting');
				$end_date = $this->input->post('end_date');
				$term_duration = $this->input->post('term_duration');

				$advertiser_json = $this->input->post('advertiser_info');
				$requester_json = $this->input->post('requester_info');

				if($advertiser_json)
				{
					$inputs['advertiser_info'] = json_decode($advertiser_json);
				}
				else
				{
					$json_array['error'] = 1;
					$json_array['error_text'] = "advertiser info not found";
					echo json_encode($json_array);
					return;
				}
				if($requester_json)
				{
					$inputs['requester_info'] = json_decode($requester_json);
				}
				else
				{
					$json_array['error'] = 1;
					$json_array['error_text'] = "requester info not found";
					echo json_encode($json_array);
					return;
				}

				if($is_retargeting === "true")
				{
					$inputs['is_retargeting'] = 1;
				}
				else if ($is_retargeting === "false")
				{
					$inputs['is_retargeting'] = 0;
				}
				else
				{
					$json_array['error'] = 1;
					$json_array['error_text'] = "invalid input for retargeting input: ".$is_retargeting;
					echo json_encode($json_array);
					return;
				}

				if(isset($landing_page))
				{
					if($landing_page !== "" AND $landing_page !== false)
					{
						$inputs['landing_page'] = $landing_page;
					}
					else
					{
						$json_array['error'] = 1;
						$json_array['error_text'] = "invalid landing page value";
						echo json_encode($json_array);
						return;
					}
				}
				else
				{
					$json_array['error'] = 1;
					$json_array['error_text'] = "invalid landing page value";
					echo json_encode($json_array);
					return;
				}

				if(isset($impressions))
				{
					if(ctype_digit($impressions))
					{
						if((int)$impressions > 1000)
						{
							$inputs['impressions'] = $impressions;
						}
						else
						{
							$json_array['error'] = 1;
							$json_array['error_text'] = "impressions value must be greater than 1000";
							echo json_encode($json_array);
							return;
						}
					}
					else
					{
						$json_array['error'] = 1;
						$json_array['error_text'] = "invalid impression value: ".$impressions."";
						echo json_encode($json_array);
						return;
					}
				}
				else
				{
					$json_array['error'] = 1;
					$json_array['error_text'] = "impressions field required";
					echo json_encode($json_array);
					return;
				}

				$t_start_date = date_parse($start_date);
				if(checkdate($t_start_date['month'], $t_start_date['day'], $t_start_date['year']))
				{
					$inputs['start_date'] = date("Y-m-d", strtotime($start_date));
				}
				else
				{
					$json_array['error'] = 1;
					$json_array['error_text'] = "invalid start date value: ".$start_date;
					echo json_encode($json_array);
					return;
				}

				if($end_date === "false")
				{
					$inputs['end_date'] = NULL;
				}
				else
				{
					$t_end_date = date_parse($end_date);
					if(checkdate($t_end_date['month'], $t_end_date['day'], $t_end_date['year']))
					{

						$inputs['end_date'] = date("Y-m-d", strtotime($end_date));
					}
					else
					{
						$json_array['error'] = 1;
						$json_array['error_text'] = "invalid end date value: ".$end_date;
						echo json_encode($json_array);
						return;
					}
				}

				switch($this->input->post('term_type'))
				{
				case "1":
					$inputs['term_type'] = "daily";
					break;
				case "7":
					$inputs['term_type'] = "weekly";
					break;
				case "30":
					$inputs['term_type'] = "monthly";
					break;
				case "-1":
					$inputs['term_type'] = "in total";
					break;
				default:
					$term_type = $this->input->post('term_type');
					$json_array['error'] = 1;
					$json_array['error_text'] = "unknown term type: ".$term_type;
					echo json_encode($json_array);
					return;
					break;
				}

				if($term_duration == '-1')
				{
					$inputs['term_duration'] = "on going";
				}
				else if($term_duration == '0')
				{
					$inputs['term_duration'] = 'specified';
				}
				else if(1 <= (int)$term_duration AND (int)$term_duration <= 12)
				{
					$inputs['term_duration'] = $term_duration;
				}
				else
				{
					$json_array['error'] = 1;
					$json_array['error_text'] = 'unknown term duration: '.$term_duration;
					echo json_encode($json_array);
					return;
				}
				$query_return = $this->proposal_gen_model->save_insertion_order($inputs);
				if($query_return['error'] == 0)
				{
					echo json_encode($query_return);
					return;
				}
				else
				{
					$json_array = $query_return;
					echo json_encode($json_array);
					return;
				}
			}
			else
			{
				$json_array['error'] = 1;
				$json_array['error_text'] = "insertion form data not found";
				echo json_encode($json_array);
				return;
			}
		}
		else
		{
			show_404();
			return;
		}
	}
	public function create_insertion_order_from_mpq_with_action($mpq_id, $action = NULL)
	{
		if($action == NULL)
		{
			redirect('/proposal_builder/option_engine/create_from_mpq/'.$mpq_id);
		}
		else
		{
			if($action == 'download_geo' or $action == 'planner')
			{
				$this->option_engine('create_from_mpq_with_action', $mpq_id, $action);
			}
			else
			{
				$this->option_engine('create_from_mpq', $mpq_id);
			}
		}
	}
	public function get_sitelist_by_tags()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => '', 'data' => false);
			$tag_data = $this->input->post('tag_data');
			if($tag_data and $tag_data != "")
			{
				$tag_objects = json_decode($tag_data);
				$site_list = $this->proposal_gen_model->get_sitelist_by_media_targeting_tag_ids($tag_objects);
				if($site_list)
				{
					$site_array = array();
					foreach($site_list as $v)
					{
						if(count($site_array) > 0)
						{
							$site_array[] = array('value' => 'ignore_tag', 'name' => " ");
						}
						$site_array[] = array('value' => 'header_tag', 'name' => strtoupper($v['tag_friendly_name']));
						if(!empty($v['sites']))
						{
							foreach($v['sites'] as $w)
							{
								$value_string = $w['url']."|||".$w['Reach']."|".$w['Gender_Male']. "|";
								$value_string .= $w['Gender_Female']."|".$w['Age_Under18']."|".$w['Age_18_24']."|";
								$value_string .= $w['Age_25_34']."|".$w['Age_35_44']."|".$w['Age_45_54']."|".$w['Age_55_64'];
								$value_string .= "|".$w['Age_65']."|".$w['Race_Cauc']."|".$w['Race_Afr_Am'];
								$value_string .= "|".$w['Race_Asian']."|".$w['Race_Hisp']."|".$w['Race_Other']."|".$w['Kids_No'];
								$value_string .= "|".$w['Kids_Yes']."|".$w['Income_0_50']."|".$w['Income_50_100'];
								$value_string .= "|".$w['Income_100_150']."|".$w['Income_150']."|".$w['College_No']."|";
								$value_string .= $w['College_Under']."|".$w['College_Grad']."|";

								$site_array[] = array('value' => $value_string, 'name' => $w['url']);
							}
						}
					}
					$return_array['data'] = $site_array;
				}
				else //associated tags have no sites
				{
					$return_array['data'] = false;
				}

			}
			else //no tags associated with proposal
			{
				$return_array['data'] = false;
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}

	public function get_laps_select2()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST' && $this->tank_auth->is_logged_in())
		{
			$user_id = $this->tank_auth->get_user_id();
			$username = $this->tank_auth->get_username();
			$role = $this->tank_auth->get_role($username);

			if (in_array(strtolower($role), array('admin', 'ops')))
			{

				$post_array = $this->input->post();
				$post_array['q'] = str_replace(" ", "%", $post_array['q']);

				$laps_response = select2_helper($this->proposal_gen_model, 'load_laps_select2', $post_array);

				echo json_encode($laps_response);
			}
			else
			{
				echo json_encode(array('errors' => "Not authorized - #900020"));
			}
		}
		else
		{
			show_404();
		}
	}

	public function get_reps_select2()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST' && $this->tank_auth->is_logged_in())
		{
			$user_id = $this->tank_auth->get_user_id();
			$username = $this->tank_auth->get_username();
			$role = $this->tank_auth->get_role($username);

			if (in_array(strtolower($role), array('admin', 'ops')))
			{
				$post_array = array();
				$post_array['q'] = str_replace(" ", "%", $this->input->post('q'));
				$post_array['page_limit'] = $this->input->post('page_limit');
				$post_array['page'] = $this->input->post('page');

				$laps_response = select2_helper($this->proposal_gen_model, 'load_reps_select2', $post_array);

				echo json_encode($laps_response);
			}
			else
			{
				echo json_encode(array('errors' => "Not authorized - #900020"));
			}
		}
		else
		{
			show_404();
		}
	}

	public function  get_vl_advertisers_dropdown()
	{
		$advertisers = $this->tank_auth->get_businesses();
		echo '<option></option>';
		foreach($advertisers as $advertiser)
		{
			echo '<option value="' .$advertiser['id']. '">' .$advertiser['Name']. '</option>';
		}
	}

	public function regenerate_site_list()
	{
		$allowed_roles = array('admin', 'ops');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array('is_success' => true, 'errors' => '', 'data' => false);
		if($verify_ajax_response['is_success'] === true)
		{
			$prop_id=$this->input->post('prop_id');
			$industry_id=$this->input->post('industry_id');
			$mpq_id=$this->proposal_gen_model->get_mpq_id_for_proposal($prop_id);

			$this->mpq_v2_model->update_industry_for_mpq($industry_id, $mpq_id);
			$auto_sites_array=$this->siterankpoc_model->get_sites_for_mpq($mpq_id, null, null, null, null, null, null, null, false, true);
			if ($auto_sites_array != "" && count($auto_sites_array) > 0)
			{
				$sites_string=json_encode($auto_sites_array);
				$this->proposal_gen_model->save_proposal_sites($sites_string, $prop_id);
			}
		}
		echo json_encode($return_array);
	}

	// this ajax call is made to create mpq, create proposal, create proposal pdf and send email to user with generated proposal
	public function create_mpq_proposal_v2()
	{
		$allowed_roles = array('admin', 'ops', 'sales');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array('is_success' => true, 'errors' => array(), 'session_expired' => false);

		if($verify_ajax_response['is_success'] === true)
		{
			$session_id = $this->session->userdata('session_id');

			$is_builder = $this->input->post('is_builder');
			$raw_mpq_id = $this->input->post('mpq_id');
			if($is_builder === false && $raw_mpq_id !== false && $session_id !== false)
			{
				$session_result = $this->mpq_v2_model->get_mpq_sessions_session_id_by_mpq_id($raw_mpq_id);
				if($session_result !== $session_id)
				{
					$return_array['session_expired'] = true;
					echo json_encode($return_array);
					return;
				}
			}

			$is_geo_dependent = true;
			$is_audience_dependent = true;
			$rfp_status = $this->input->post('rfp_status');
			if($rfp_status === false)
			{
				//oops
			}
			else
			{
				$is_geo_dependent = $rfp_status['has_geo_products'];
				$is_audience_dependent = $rfp_status['has_audience_products'];
			}
			$mpq_id = $this->save_mpq_v2($return_array, $is_builder);
			$return_array['mpq_id'] = $mpq_id;
			
			$proposal_exists_already = false;
			$temp_proposal_data = $this->mpq_v2_model->get_proposal_data_by_source_mpq($mpq_id);
			if(!empty($temp_proposal_data))
			{
				$return_array['prop_id'] = $temp_proposal_data['prop_id'];
				$proposal_exists_already = true;
			}
			   
			if(!$is_builder || !($is_builder && $proposal_exists_already))
			{
				$return_array = $this->create_proposal_v2($mpq_id, $return_array);
			}
			$proposal_id = $return_array['prop_id'];
			$mpq_id = $return_array['mpq_id'];


			//post method returns STRING true/false instead of boolean
			if($is_geo_dependent === "true" and $is_audience_dependent === "true")
			{
				// Amit. Generates Site list automatically for Local and Nonlocal
				$this->generate_auto_site_list_v2($mpq_id, $proposal_id);
			}
			if(!$is_builder)
			{
				$return_array['html_summary_saved'] = $this->generate_original_summary($proposal_id, $is_geo_dependent, $is_audience_dependent);
			}

		}
        $this->output->set_output(json_encode($return_array));
	}

	// run from a cron to finsh up processing of proposals created by Proposal_Builder::create_mpq_proposal_v2()
	// example cron:
	// * *  *   *   *     cd /var/www/platform.brandcdn.com/public && php index.php proposal_builder process_incomplete_proposals >> /var/www/platform.brandcdn.com/log/proposal_builder_cron.log
	public function process_incomplete_proposals()
	{
		if($active_processes = $this->cli_data_processor_common->get_active_processes(__METHOD__))
		{
			$this->cli_data_processor_common->cron_log("{$active_processes['method']} is already in use in these processes:\n  " . implode("\n  ", $active_processes['processes']));
			exit;
		}

		if($proposals = $this->mpq_v2_model->get_proposals_with_process_status('failed'))
		{
			$basic_info = $this->cli_data_processor_common->get_environment_message();
			$message = $basic_info . '<br><br>Proposal IDs are:<ul>';
			foreach ($proposals as $proposal)
			{
				$message .= "<li>".$proposal['prop_id']."</li>";
				$this->mpq_v2_model->set_proposal_status($proposal['prop_id'], 'failed-sent');
			}
			$message .= "</ul>";
			$from="tech@frequence.com";
			$to="tech@frequence.com";
			$subject="Error: RFP Generation issues, please check...";
			$body_type = 'html';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, 'api:key-1bsoo8wav8mfihe11j30qj602snztfe4');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v2/mg.brandcdn.com/messages');
			curl_setopt(
				$ch,
				CURLOPT_POSTFIELDS,
				array('from' => $from,
					'to' => $to,
					'subject' => $subject,
					$body_type => $message
				)
			);
			$result = curl_exec($ch);
			curl_close($ch);
		}

		if($proposals = $this->mpq_v2_model->get_proposals_with_process_status('queued-auto'))
		{
			echo "Processing queued proposals...\n";
			foreach($proposals as $proposal)
			{
				$this->mpq_v2_model->set_proposal_status($proposal['prop_id'], 'failed-snapshots');
				$mpq_id = $proposal['source_mpq'];
				$proposal_id = $proposal['prop_id'];
				$creator_user = $this->users->get_user_by_id($proposal['owner_user_id'], true);
				$partner_id = $creator_user->partner_id;

				$this->populate_geo_snapshots_v2($proposal_id, $mpq_id);
			}
		}

		if($proposals = $this->mpq_v2_model->get_proposals_with_process_status('snapshots-processing'))
		{
			echo "Processing snapshots-processing...\n";
			foreach($proposals as $proposal)
			{
				if($proposal['snapshots_started'] && ((time() - strtotime($proposal['snapshots_started'])) / 60) > $this->snapshot_timeout_in_minutes)
				{
					$this->mpq_v2_model->set_proposal_status($proposal['prop_id'], 'failed-snapshots');
				}

				$mpq_id = $proposal['source_mpq'];
				$proposal_id = $proposal['prop_id'];
				$this->mpq_v2_model->check_and_set_proposal_snapshot_status($proposal_id, $mpq_id);
			}
		}
	}

	public function process_failed_snapshots_proposals()
	{
		if($proposals = $this->mpq_v2_model->get_proposals_with_process_status('failed-snapshots'))
		{
			echo "Processing queued proposals...\n";
			foreach($proposals as $proposal)
			{
				$this->mpq_v2_model->set_proposal_status($proposal['prop_id'], 'failed');
				$mpq_id = $proposal['source_mpq'];
				$proposal_id = $proposal['prop_id'];
				$creator_user = $this->users->get_user_by_id($proposal['owner_user_id'], true);
				$partner_id = $creator_user->partner_id;

				$this->populate_geo_snapshots_v2($proposal_id, $mpq_id);
			}
		}
	}

	public function process_failed_pdf_proposals()
	{
		if($proposals = $this->mpq_v2_model->get_proposals_with_process_status('failed-pdf'))
		{
			echo "Processing snapshots-complete...\n";

			foreach($proposals as $proposal)
			{
				$this->mpq_v2_model->set_proposal_status($proposal['prop_id'], 'failed');
				$proposal_id = $proposal['prop_id'];
				$owner_user = $this->users->get_user_by_id($proposal['owner_user_id'], true);
				$partner_id = $owner_user->partner_id;

				$pdf_url = $this->proposal_generator->auto_generate_proposal($proposal_id, $partner_id);

				$creator_user = $this->users->get_user_by_id($proposal['creator_user_id'], true);
				if($this->generate_auto_email_pdf_v2($proposal, $creator_user, $pdf_url))
				{
					if(!$this->mpq_v2_model->set_proposal_status($proposal_id, 'sent-auto'))
					{
						$this->cli_data_processor_common->cron_log('Error 789525: Failed to dequeue proposal, ID ' . $proposal_id);
					}
				}
				else
				{
					$this->cli_data_processor_common->cron_log('Error 789523: Failed to send proposal, ID ' . $proposal_id);
					continue;
				}
			}
		}
	}


	// this function creates a new proposal from mpq
	private function create_proposal_v2($mpq_id, $return_array)
	{
		//a. get the mpq and populate dummy data in mpq, remove later
		$session_id = $this->session->userdata('session_id');
		//$mpq_id=$this->mpq_v2_model->save_dummy_data_in_mpq($session_id);

		//b. create a new proposal
		$user_id = $this->tank_auth->get_user_id($session_id);
		$create_proposal_response = $this->mpq_v2_model->create_proposal_from_mpq_v2($new_prop_id, $mpq_id, $user_id);
		$force_prop_id = $new_prop_id;

		$return_array['mpq_id']=$mpq_id;
		$return_array['prop_id']=$force_prop_id;

		return $return_array;
	}

	// this function generates Site list automatically for Local and Nonlocal
	private function generate_auto_site_list_v2($mpq_id, $proposal_id)
	{
			$lap_ids_array=$this->proposal_gen_model->get_lap_ids_from_mpq_id($mpq_id);
			$local_sites_included_flag=true;
			if (count($lap_ids_array) > 1)
			{
				$local_sites_included_flag=false;
			}
			$auto_sites_array=$this->siterankpoc_model->get_sites_for_mpq($mpq_id, null, null, null, null, null, null, null, false, $local_sites_included_flag);
			if ($auto_sites_array != "" && count($auto_sites_array) > 0)
			{
				$sites_string=json_encode($auto_sites_array);
				$this->proposal_gen_model->save_proposal_sites($sites_string, $proposal_id);
				$this->siterankpoc_model->get_site_rankings_proposal($mpq_id);
			}

			//now generate sites for local
			$this->siterankpoc_model->generate_save_local_sites_proposal($mpq_id);
	}

	private function populate_geo_snapshots_v2($proposal_id, $mpq_id)
	{
		$this->mpq_v2_model->set_proposal_status($proposal_id, 'snapshots-processing');
		$this->mpq_v2_model->mark_snapshots_started_time($proposal_id);
		$lap_ids = $this->proposal_gen_model->get_lap_ids_from_mpq_id_for_snapshots($mpq_id);

		$urls = array();

		$this->proposals_model->delete_non_overview_snapshots($proposal_id);

		$timeout_in_ms = $this->snapshot_timeout_in_minutes * 60 * 1000;

		foreach($lap_ids as $location_id => $lap_id)
		{
			if($lap_id)
			{
				$urls[] = base_url("proposal_builder/lap_image_gen/{$lap_id}/{$proposal_id}/{$mpq_id}/{$location_id}") . " $timeout_in_ms";
			}
		}
		if (count($lap_ids) > 1)
		{
			$urls[] = base_url("proposal_builder/lap_image_gen/overview/{$proposal_id}/{$mpq_id}") . " $timeout_in_ms";
		}

		$location = FCPATH.'/assets/js/phantom/snapshot.js';

		$log_string = "\nProposal id: {$proposal_id} -- MPQ id: {$mpq_id}\n"; // Easier debugging
		foreach($urls as $url)
		{
			$response = exec(PHANTOMJS_BIN_LOCATION . " {$location} {$url}");
			$log_string .= "{$url} => {$response}\n";
		}
		echo "{$log_string}\n";

		$rooftops = $this->mpq_v2_model->get_rooftops_data($mpq_id);
		if (!empty($rooftops) && $rooftops !== '[]')
		{
			$url = base_url("proposal_builder/rooftops_image_gen/{$proposal_id}/{$mpq_id}#snug");
			$response = exec(PHANTOMJS_BIN_LOCATION . " {$location} {$url}");
			echo "{$url} => {$response}\n";
		}
	}

	private function generate_auto_email_pdf_v2($proposal_with_mpq, $creator_user, $pdf_url)
	{
		$from = self::$EMAIL_NOREPLY;
		$subject = "Request for Proposal for {$proposal_with_mpq['advertiser_name']}";
		$post_overrides = array();

		// determine to whom this will be sent
		$cc = self::$EMAIL_CC;
		$bcc = self::$EMAIL_BCC;

		$creator_address = "{$creator_user->firstname} {$creator_user->lastname} <{$creator_user->email}>";
		$to = $creator_address;

		if ($proposal_with_mpq['cc_owner'])
		{
			$owner_user = $this->users->get_user_by_id($proposal_with_mpq['owner_user_id'], true);
			if(!empty($proposal_with_mpq['submitter_email']) && $owner_user->email != $creator_user->email)
			{
				$to = "{$proposal_with_mpq['submitter_name']} <{$proposal_with_mpq['submitter_email']}>";
				$cc[] = $creator_address;
				$post_overrides['h:reply-to'] = $creator_address;
			}
		}

		if(!empty($cc))
		{
			$post_overrides['cc'] = implode(', ', array_unique($cc));
		}

		if(!empty($bcc))
		{
			$post_overrides['bcc'] = implode(', ', array_unique($bcc));
		}

		$html_body = '<!DOCTYPE html><html><body>'
			. '<a href="' . $pdf_url . '" style="font-size:2em;">Download this proposal as a PDF</a>'
			. '<br><br>'
			. $proposal_with_mpq['original_submission_summary_html']
			. '</body></html>';

		$result = mailgun(
			$from,
			$to,
			$subject,
			$html_body,
			'html',
			$post_overrides
		);

		return $result;
	}

	private function save_mpq_v2(&$return_array, $is_builder)
	{
		$mpq_id = false;

		$product_object = $this->input->post('product_object');
		$cc_owner = $this->input->post('cc_owner');
		$iab_categories = $this->input->post('iab_categories');
		$rooftops = $this->input->post('rooftops');
		$tv_zones = $this->input->post('tv_zones');
		$tv_selected_networks = $this->input->post('tv_selected_networks');
		$keywords_data = $this->input->post('keywords_data');
		$political_segments = $this->input->post('political_segments');
		$demographics = $this->input->post('demographics');
		$options = $this->input->post('options');
		$discount_text = $this->input->post('discount_text');
		
		$builder_mpq_id = false;
		if($is_builder)
		{
			$builder_mpq_id = $this->input->post('mpq_id');
		}

		$notes = '';
		if(!empty($product_object))
		{
			if($iab_categories !== false and $demographics !== false and $options !== false and $discount_text !== false)
			{
				$session_id = $this->session->userdata('session_id');
				$mpq_session = $this->mpq_v2_model->get_rfp_preload_mpq_session_data($session_id, false, $builder_mpq_id);
				$submitted_user_details = $this->tank_auth->get_user_by_id($mpq_session['owner_user_id']);
				if($is_builder)
				{
					$user_id = $this->tank_auth->get_user_id();
				}
				else
				{
					$user_id = $this->tank_auth->get_user_id($session_id);
				}
				$advertiser = new stdClass();
				$advertiser->business_name = $mpq_session['advertiser_name'];
				$advertiser->website_url = $mpq_session['advertiser_website'];
				$submitter = new stdClass();
				$submitter->id = $submitted_user_details->id;
				$submitter->name = $submitted_user_details->firstname . ' ' . $submitted_user_details->lastname;
				$submitter->role = null;
				$submitter->website = null;
				$submitter->email_address = $submitted_user_details->email;
				$submitter->phone_number = null;
				$submitter->notes = $notes;
				$iab_category_ids = json_decode($iab_categories);
				$option_data = json_decode($options, true);

				$save_response = $this->mpq_v2_model->save_submitted_mpq_and_remove_from_session($demographics, $iab_category_ids, null, $advertiser, $submitter, $session_id, $user_id, $mpq_session['industry_id'], $rooftops, $cc_owner, null, $builder_mpq_id);

				if($save_response['success'] and array_key_exists('id', $save_response['geo_data']))
				{
					$mpq_id = $save_response['geo_data']['id'];
					$product_response = $this->mpq_v2_model->save_submitted_rfp_products($product_object, $mpq_id, $is_builder);
					$option_response = $this->mpq_v2_model->save_submitted_mpq_options($option_data, $mpq_id, $discount_text);

					if($keywords_data !== false && $keywords_data !== "[]" && $keywords_data['clicks'] !== "" && is_numeric($keywords_data['clicks']) && $keywords_data['clicks'] > 0 && count($keywords_data['search_terms']) > 0)
					{
						$keywords_response = $this->mpq_v2_model->save_keywords_data($mpq_id, $keywords_data['clicks'], $keywords_data['search_terms']);
					}

					if($tv_zones !== false && count($tv_zones) > 0)
					{
						$tv_zone_response = $this->mpq_v2_model->save_submitted_tv_zones($mpq_id, $tv_zones);
						if($tv_zone_response === false)
						{
							$return_array['is_success'] = false;
							$return_array['errors'][] = "Error 016704: unable to save tv zone data";
						}
					}
					if($tv_selected_networks !== false)
					{
						$this->mpq_v2_model->save_selected_networks($tv_selected_networks, $mpq_id);
					}
					if ($political_segments)
					{
						foreach ($political_segments as &$segment)
						{
							$segment['name'] = explode('_', $segment['name'])[2];
							$segment['value'] = $segment['value'] === "true" ? '1' : '0';
						}
						$this->mpq_v2_model->save_mpq_political_segments($political_segments, $mpq_id);
					}

					if($option_response === false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'][] = "Error 016704: unable to save mpq option data";
					}
					if($product_response === false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'][] = "Error 016705: unable to save mpq product data";
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'][] = "Error 016703: unable to save mpq data";
				}
			}
			else
			{
				$return_array['response'] = $this->input->post(NULL, true);
				$return_array['is_success'] = false;
				$return_array['errors'][] = "Error 016701: invalid data sent to server";
			}
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['errors'][] = "Error 016700: no product data found";
		}
		return $mpq_id;
	}

    private function generate_original_summary($proposal_id, $is_audience, $is_geo)
	{
		$data = array();

		$data['proposal'] = $this->proposals_model->get_proposal_by_id($proposal_id, $is_audience, $is_geo);
		if(empty($data['proposal']['geos']))
		{
			$data['proposal']['geos'] = $data['proposal']['submitted_geos']; // only one or the other is defined, to conserve memory
		}

		$data['industry'] = $this->mpq_v2_model->get_industry_by_id($data['proposal']['industry_id']);
		if($data['industry'] == false)
		{
			$data['industry'] = array('freq_industry_tags_id' => '0', 'name' => 'Unknown');
		}

		$data['mpq'] = $this->mpq_v2_model->get_proposal_with_mpq($proposal_id);

		$data['creator'] = $this->users->get_user_by_id($data['mpq']['creator_user_id'], true);

		$data['categories'] = $this->mpq_v2_model->get_submitted_mpq_options($data['proposal']['source_mpq']);

		$data['all_location_term_totals'] = array();

		$data['rooftops'] = json_decode($data['mpq']['rooftops_data'], true);

		$data['no_discount'] = $data['proposal']['no_discount'];

		$data['political'] = $this->proposals_model->format_political_data($data['mpq']['id']);

		$data['tv_zones'] = $this->mpq_v2_model->get_rfp_tv_zones_by_mpq_id($data['mpq']['id']);

		$num_locations = count($data['proposal']['geos']);
		$html_summary = $this->load->view('/generator/submission_summary_html.php', $data, true);
		return $this->proposal_gen_model->save_original_summary_html($data['proposal']['source_mpq'], $html_summary);
	}

	public function send_debug_email()
	{
		$allowed_roles = array('admin', 'ops', 'sales');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array('is_success' => true, 'errors' => array());
		if($verify_ajax_response['is_success'] === true)
		{
			$message = $this->input->post('data_string');
			$username = $this->tank_auth->get_username();
			$role = strtolower($this->tank_auth->get_role($username));

			$basic_info = $this->cli_data_processor_common->get_environment_message();
			$message = $basic_info . '<br><br> User: '. $username . ". Role: ". $role . '<br><br>====================<br> Error details for failed RFP submission: <br><br>' .  $message;
			$from="tech@frequence.com";
			$to="tech@frequence.com";
			$subject="Error: RFP submission issues, please check...User is: ". $username;
			$body_type = 'html';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, 'api:key-1bsoo8wav8mfihe11j30qj602snztfe4');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v2/mg.brandcdn.com/messages');
			curl_setopt(
				$ch,
				CURLOPT_POSTFIELDS,
				array('from' => $from,
					'to' => $to,
					'subject' => $subject,
					$body_type => $message
				)
			);
			$result = curl_exec($ch);
			curl_close($ch);
		}
		echo json_encode($return_array);
	}

	public function save_io()
	{
		$allowed_roles = array('admin', 'ops', 'sales');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array('is_success' => true, 'errors' => "");

		if($verify_ajax_response['is_success'] === true)
		{
			$advertiser_id = $this->input->post('advertiser_id');
			$advertiser_name = $this->input->post('advertiser_name');
			$source_table = $this->input->post('source_table');
			$website_name = $this->input->post('website_name');
			$order_name = $this->input->post('order_name');
			$order_id = $this->input->post('order_id');
			$industry = $this->input->post('industry');
			$submitted_user_id = $this->input->post('selected_user_id');
			$iab_categories = $this->input->post('iab_categories');
			$demographics = $this->input->post('demographics');
			$raw_io_status = $this->input->post('io_status');
			$notes = $this->input->post('notes');
			$submission_type = $this->input->post('submission_type');
			$tracking_tag_file_id = $this->input->post('tracking_tag_file_id');
			$include_retargeting = $this->input->post('include_retargeting');
			$old_tracking_tag_file_id = $this->input->post('old_tracking_tag_file_id');
			$old_source_table = $this->input->post('old_source_table');
			$mpq_id = $this->input->post('mpq_id');
			$custom_region_data = $this->input->post('custom_region_data');

			if($order_name !== false and $website_name !== false and $industry !== false and $submitted_user_id !== false and $iab_categories !== false and $demographics !== false and $raw_io_status !== false and $notes !== false and $submission_type !== false)
			{
				if(!is_string($notes))
				{
					$notes = "";
				}
				$submitted_user_details = $this->tank_auth->get_user_by_id($submitted_user_id);
				$session_id = $this->session->userdata('session_id');
				$user_id = $this->tank_auth->get_user_id($session_id);

				$tracking = new stdClass();
				$tracking->tracking_tag_file_id = $tracking_tag_file_id;
				$tracking->include_retargeting = $include_retargeting == "true" ? 1 : 0;

				//If old advertiser was unverified one and the new is verified and if old tracking file is same as new tracking file, update tag file table for this file id
				//to populate new advertiser id, name and source table value
				if ($old_source_table === 'advertisers_unverified' && $source_table === 'Advertisers'
					&& $old_tracking_tag_file_id != '' && $tracking_tag_file_id != '' && $old_tracking_tag_file_id == $tracking_tag_file_id)
				{
					$result = $this->tag_model->update_tracking_tag_file_info($source_table, $advertiser_id, $tracking_tag_file_id);
					if (!$result)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = 'There was a problem updating tracking tag file record';
						echo json_encode($return_array);
						return;
					}
				}
				
				if (isset($custom_region_data) && !empty($custom_region_data))
				{
					$this->save_o_o_ids($mpq_id, $custom_region_data);
				}

				$advertiser = new stdClass();
				$advertiser->advertiser_name = $advertiser_name ?: NULL;
				$advertiser->website_url = $website_name ?: NULL;
				$advertiser->order_name = $order_name ?: NULL;
				$advertiser->order_id = $order_id ?: NULL;
				$advertiser->advertiser_id = $advertiser_id ?: NULL;
				$advertiser->source_table = $source_table;

				$submitter = new stdClass();
				$submitter->id = $submitted_user_details->id;
				$submitter->name = $submitted_user_details->firstname . ' ' . $submitted_user_details->lastname;
				$submitter->role = null;
				$submitter->website = null;
				$submitter->email_address = $submitted_user_details->email;
				$submitter->phone_number = null;
				$submitter->notes = $notes;

				$industry_id = $industry;
				$iab_category_ids = json_decode($iab_categories);

				$mpq_summary = $this->mpq_v2_model->get_io_summary_data($mpq_id);

				$io_status_array = array(
					'opportunity_status' => $this->get_io_status_from_array_by_type($raw_io_status, 'opportunity'),
					'product_status' => $this->get_io_status_from_array_by_type($raw_io_status, 'product'),
					'geo_status' => $this->get_io_status_from_array_by_type($raw_io_status, 'geo'),
					'audience_status' => $this->get_io_status_from_array_by_type($raw_io_status, 'audience'),
					'flights_status' => $this->get_io_status_from_array_by_type($raw_io_status, 'flights'),
					'creative_status' => $this->get_io_status_from_array_by_type($raw_io_status, 'creative'),
					'notes_status' => $this->get_io_status_from_array_by_type($raw_io_status, 'notes'),
					'tracking_status' => $this->get_io_status_from_array_by_type($raw_io_status, 'tracking')
					);

				$status_ok = true;
				foreach ($io_status_array as $key => $status)
				{
					if ($status == 0)
					{
						$status_ok = false;
						break;
					}
				}

				if($submission_type == 'save')
				{
					$save_response = $this->mpq_v2_model->save_submitted_io($demographics, $iab_category_ids, $advertiser, $submitter, $session_id, $user_id, $industry_id, $io_status_array, $submission_type, $tracking, $status_ok, $mpq_summary);
					if($save_response === false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 903500: unable to save insertion order data";
					}
				}
				else if($submission_type == 'submit' || $submission_type == 'submit_for_review')
				{
					if($status_ok)
					{
						$save_response = $this->mpq_v2_model->save_submitted_io($demographics, $iab_category_ids, $advertiser, $submitter, $session_id, $user_id, $industry_id, $io_status_array, $submission_type, $tracking, $status_ok, $mpq_summary);

						if ($save_response === false)
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 903500: unable to save insertion order data";
						}
						else
						{
							if ($submission_type == 'submit')
							{
								if($this->session->userdata('is_demo_partner') != 1)
								{
									$email_result = $this->send_io_submit_email($advertiser->advertiser_name, $submitter, $mpq_id, $mpq_summary);
									if($email_result !== true)
									{
										$this->mpq_v2_model->update_mpq_session_with_mailgun_errors($mpq_id, $email_result);
										$return_array['is_success'] = false;
										$return_array['errors'] = "Your insertion order was submitted successfully but there was a problem creating your email summary.";
									}
								}
							}
							elseif ($submission_type == 'submit_for_review')
							{
								$email_result = $this->send_io_review_email($advertiser->advertiser_name, $submitter, $mpq_id, $mpq_summary);
								if($email_result !== true)
								{
									$this->mpq_v2_model->update_mpq_session_with_mailgun_errors($mpq_id, $email_result);
									$return_array['is_success'] = false;
									$return_array['errors'] = "There was a problem in sending email while submitting your insertion order for review.";
								}
							}
						}
					}
					else
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "It looks like you haven't filled out the IO completely.";
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 902400: invalid submission method";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 902400: invalid data sent to server";
			}
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['errors'] = "Error 901401: user logged out or not permitted";
		}

		echo json_encode($return_array);
	}

	private function get_io_status_from_array_by_type($io_status, $status_type)
	{
		if(!array_key_exists($status_type, $io_status) or !array_key_exists('status', $io_status[$status_type]) or $io_status[$status_type]['status'] == 'not_started')
		{
			return 0;
		}
		else if($io_status[$status_type]['status'] == 'done')
		{
			return 1;
		}
		else if($io_status[$status_type]['status'] == 'on_hold')
		{
			return 2;
		}
		else
		{
			return 0;
		}
	}

	private function send_io_submit_email($advertiser_name, $submitter, $mpq_id, $mpq_summary)
	{
		$partner_id = null;
		$partner_details = false;
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		$partner_id = $this->tank_auth->get_partner_id($submitter->id);
		if(!is_null($partner_id))
		{
			$partner_details = $this->tank_auth->get_partner_info($partner_id);
		}

		if($partner_details === false or $partner_details === NULL)
		{
			$partner_details = $this->tank_auth->if_partner_return_details();
		}

		if($partner_details === false or $partner_details === NULL)
		{
		    $partner_name = 'Brand CDN';
			$partner_id = 1;
		}
		else
		{
			$partner_name = $partner_details['partner_name'];
			$partner_id = $partner_details['id'];
		}

		$cc_emails = array();
		$bcc_emails = array();

		$user_email = $this->tank_auth->get_email($this->tank_auth->get_username());

		$sales_result = $this->tank_auth->get_parent_and_sales_owner_emails_by_partner($partner_id);

		foreach($sales_result as $sales_user)
		{
			$bcc_emails[] = $sales_user['email'];
		}

		if($user_email !== $submitter->email_address)
		{
			$cc_emails[] = $user_email;
		}

		$data = $this->proposals_model->get_insertion_order_summary_html_core($mpq_id, $role, 1);
		$data['io_submit_allowed'] = true;
		$message = $this->load->view('/generator/io_summary_html.php', $data, true);

		$email_subject_appender = "";
		if (isset($data['mpq']['mpq_core']))
		{
			$email_subject_appender = ($data['mpq']['mpq_core']['order_name'] !== null ? (" : ".$data['mpq']['mpq_core']['order_name']) : "");
		}

		$subject = "SUBMITTED : Insertion Order for ".$advertiser_name.$email_subject_appender;
		$from = 'No Reply <no-reply@brandcdn.com>';
		$to = 'helpdesk@brandcdn.com';
		$cc = implode(", ", $cc_emails);
		$bcc = implode(", ", $bcc_emails);
		$post_overrides = array("h:reply-to" => $submitter->email_address);

		if ($mpq_summary['mpq_type'] === 'io-submitted')
		{
			$to = $user_email;
			$message = "<strong>Revised Summary: </strong><br/>".$message;
		}

		if($cc !== "")
		{
			$post_overrides['cc'] = $cc;
		}

		if($bcc !== "")
		{
			$post_overrides['bcc'] = $bcc;
		}

		$result = mailgun(
			$from,
			$to,
			$subject,
			$message,
			"html",
			$post_overrides
			);
		return $result;
	}

	private function send_io_review_email($advertiser_name, $submitter, $mpq_id, $mpq_summary)
	{
		$partner_id = null;
		$partner_details = false;
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		$partner_id = $this->tank_auth->get_partner_id($submitter->id);

		if(!is_null($partner_id))
		{
			$partner_details = $this->tank_auth->get_partner_info($partner_id);
		}

		if($partner_details === false or $partner_details === NULL)
		{
			$partner_details = $this->tank_auth->if_partner_return_details();
		}

		if($partner_details === false or $partner_details === NULL)
		{
			$partner_id = 1;
		}
		else
		{
			$partner_id = $partner_details['id'];
		}

		$to_emails = array();

		$sales_result = $this->tank_auth->get_parent_and_sales_owner_emails_by_partner($partner_id);

		foreach($sales_result as $sales_user)
		{
			$to_emails[] = $sales_user['email'];
		}

		if(count($to_emails) == 0)
		{
			return true;
		}

		$data = $this->proposals_model->get_insertion_order_summary_html_core($mpq_id, $role, 1);
		$message = $this->load->view('/generator/io_review_email_html.php', $data, true);

		$email_subject_appender = "";
		if (isset($data['mpq']['mpq_core']))
		{
			$email_subject_appender = ($data['mpq']['mpq_core']['order_name'] !== null ? (" : ".$data['mpq']['mpq_core']['order_name']) : "");
		}

		$subject = "IN REVIEW : Insertion Order for ".$advertiser_name.$email_subject_appender;
		$from = 'No Reply <no-reply@brandcdn.com>';
		$to = implode(", ", $to_emails);
		$post_overrides = array("h:reply-to" => $submitter->email_address);


		$result = mailgun(
				$from,
				$to,
				$subject,
				$message,
				"html",
				$post_overrides
			);

		return $result;
	}

	public function update_io_status()
	{
		$allowed_roles = array('admin', 'ops', 'sales');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array('is_success' => true, 'errors' => "");

		$session_id = $this->session->userdata('session_id');
		if($verify_ajax_response['is_success'] === true)
		{
			$raw_io_status = $this->input->post('io_status');
			$mpq_id = $this->input->post('mpq_id');
			if($raw_io_status !== false && $session_id !== false && $mpq_id !== false)
			{
				$io_status_array = array(
					'opportunity_status' => $this->get_io_status_from_array_by_type($raw_io_status, 'opportunity'),
					'product_status' => $this->get_io_status_from_array_by_type($raw_io_status, 'product'),
					'geo_status' => $this->get_io_status_from_array_by_type($raw_io_status, 'geo'),
					'audience_status' => $this->get_io_status_from_array_by_type($raw_io_status, 'audience'),
					'flights_status' => $this->get_io_status_from_array_by_type($raw_io_status, 'flights'),
					'creative_status' => $this->get_io_status_from_array_by_type($raw_io_status, 'creative'),
					'notes_status' => $this->get_io_status_from_array_by_type($raw_io_status, 'notes'),
					'tracking_status' => $this->get_io_status_from_array_by_type($raw_io_status, 'tracking')
					);

				$response = $this->mpq_v2_model->save_io_status_values($io_status_array, $mpq_id);
				if($response !== true)
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 278500: Unable to save insertion order status";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = '278400: Unable to read insert order status';
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}

	}

	public function update_tracking_tag_file_data($source_table, $io_advertiser_id, $tracking_tag_file_id)
	{
		$return_array = array();
		$return_array['status'] = false;
		$return_array['errors'] = '';

		$result = get_tag_file_directory_name(null,$io_advertiser_id);

		if ($result['status'] == 'fail'){
			return $return_array;
		}

		$directory_name = $result['directory_name'];
		$tracking_tag_file_name = $this->tag_model->get_tracking_tag_file_name_by_id($tracking_tag_file_id);
		$old_tracking_tag_file_full_path = $tracking_tag_file_name;
		$forward_slash_position = strpos($tracking_tag_file_name,'/');

		//If filename contains forward slash, remove them.
		if ($forward_slash_position > 0)
		{
			$tracking_tag_file_name = substr($tracking_tag_file_name,$forward_slash_position+1);
		}

		if (!create_tracking_tag_file_directory($directory_name))
		{
			$return_array['errors'] = 'Error while creating tracking file on disk';
		}
		else
		{
			move_tracking_tag_file_to_new_directory($old_tracking_tag_file_full_path, $directory_name."/".$tracking_tag_file_name);
			$result = $this->tag_model->update_tracking_tag_file_info($directory_name."/".$tracking_tag_file_name, $source_table, $io_advertiser_id, $tracking_tag_file_id);
			if ($result)
			{
				$return_array['status'] = true;
				$return_array['file_name'] = $directory_name."/".$tracking_tag_file_name;
			}
		}

		return $return_array;
	}

	public function get_tag_file_directory_name($campaign_id=-1, $adv_id=-1)
	{
		if ($adv_id != null && $adv_id != -1)
		{
			$advertiser = $this->tag_model->get_advertiser_by_advertiser_id($adv_id);
		}
		elseif($campaign_id != null && $campaign_id != -1)
		{
			$advertiser = $this->tag_model->get_advertiser_by_campaign_id($campaign_id);
		}

		if (!$advertiser)
		{
			return false;
		}
		else
		{
			$advertiser_name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', strip_tags(html_entity_decode($advertiser['Name']))));
			return $advertiser_name."_".strtolower(base64_encode(base64_encode(base64_encode($advertiser["id"]))));
		}
	}

	public function sample_mail()
	{
		$subject = "Re: Insertion Order Request : IO ID : 1012";
		$from = 'No Reply <no-reply@brandcdn.com>';
		$to = 'support+id512680@vlhelp.zendesk.com';
		$message = "This is second message for sample email. Would it be appended?";
		$post_overrides = array("h:reply-to" => "sagun.tumkar@frequence.com");

		$result = mailgun(
			$from,
			$to,
			$subject,
			$message,
			"html",
			$post_overrides
		);
	}
	
	private function save_o_o_ids($mpq_id, $custom_regions_data)
	{
		if (isset($custom_regions_data) && count($custom_regions_data) > 0)
		{
			foreach ($custom_regions_data AS $product_id => $region_o_o_ids)
			{
				foreach ($region_o_o_ids AS $region_id => $o_o_ids_str)
				{
					$o_o_ids = explode(",",$o_o_ids_str);
					$submitted_product = $this->mpq_v2_model->get_submitted_products_by_mpq_id_product_and_region_index($mpq_id, $product_id, $region_id);
					
					if ($submitted_product)
					{
						$this->mpq_v2_model->save_o_o_ids_for_submitted_product($submitted_product[0]['id'], $o_o_ids);
					}
				}
			}
		}
	}
}
