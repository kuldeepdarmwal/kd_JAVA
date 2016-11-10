<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Auth extends CI_Controller
{
	function __construct()
	{
		parent::__construct();

		$this->load->helper(array('form', 'html_dom', 'mailgun', 'url', 'vl_ajax'));
		$this->load->library('form_validation');
		//		$this->load->library('security');
		$this->load->library('tank_auth');
		$this->load->library('vl_platform');
		$this->lang->load('tank_auth');
		$this->load->model('vl_auth_model');
	}

	function index()
	{
		if ($message = $this->session->flashdata('message')) {
			$this->load->view('auth/general_message', array('message' => $message));
		} else {
			redirect('login');
		}
	}


	private function get_dev_server_subdomain_segment($segment)
	{
		$subdomain_segment = false;

		$review_matches = array();
		$is_review = preg_match('/^review-[\d]+$/', $segment, $review_matches);
		if($is_review === 1)
		{
			$subdomain_segment = $review_matches[0];
		}

		$dev_matches = array();
		$is_dev = preg_match('/^dev_[\w]+$/', $segment, $dev_matches);
		if($is_dev === 1)
		{
			$subdomain_segment = $dev_matches[0];
		}

		return $subdomain_segment;
	}

	private function get_redirect_if_wrong_domain()
	{
		$redirect_location = false;
		
		$user_id = $this->tank_auth->get_user_id();
		$host = $_SERVER['HTTP_HOST'];
		$is_host_correct = $this->vl_auth_model->does_host_match_partner($host, $user_id);
		if($is_host_correct == false)
		{
			$user_name = $this->tank_auth->get_username();
			$role = $this->tank_auth->get_role($user_name);

			$domain = '.'.g_second_level_domain;

			$should_override_host_access = false;
			switch(strtolower($role))
			{
				case 'admin':
				case 'creative':
				case 'ops':
					$should_override_host_access = true;
					break;

				case 'agency':
				case 'business':
				case 'client':
				case 'sales':
					if(ENVIRONMENT == 'localhost-development')
					{
						// localhost
						$should_override_host_access = true;
					}
					else
					{
						$domain = '.'.$this->tank_auth->get_domain_without_cname();
					}
					break;

				default:
					die('unknown user role: '.$role.', login rejected.  Error (#5408745)');
					break;
			}

			if($should_override_host_access == false)
			{
				$cname = $this->vl_auth_model->get_cname_for_user($user_id);
				if(empty($cname))
				{
					$cname = 'vantagelocal';
				}
				$protocol = ENABLE_HOOKS ? 'https' : 'http';
				$accessible_login_url = $protocol.'://'.$cname.$domain;
				$redirect_location = $accessible_login_url;
			}
		}

		return $redirect_location;
	}

	/**
	 * Login user on the site
	 *
	 * @return void
	 */
	function login($partner_unique_id = '0')
	{	
		$this->redirect_if_partner_unique_id($partner_unique_id);
               $data['error_message'] = "";
		$raw_goto = $this->session->userdata('referer');
		$goto = $raw_goto;
		if(empty($goto))
		{
			$goto = 'director';
		}
		else
		{
			$this->session->set_userdata('referer', '');
		}

		if ($this->tank_auth->is_logged_in())								// logged in
		{
			redirect($goto);
		}
		elseif ($this->tank_auth->is_logged_in(FALSE))						// logged in, not activated
		{
			redirect('/auth/send_again/');
			
		}
		else
		{
			$data['login_by_username'] = ($this->config->item('login_by_username', 'tank_auth') AND
					$this->config->item('use_username', 'tank_auth'));
			$data['login_by_email'] = $this->config->item('login_by_email', 'tank_auth');

			$this->form_validation->set_rules('login', 'Login', 'trim|required|xss_clean');
			$this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean');
			$this->form_validation->set_rules('referer', 'Referer', 'trim|xss_clean');
			//$this->form_validation->set_rules('remember', 'Remember me', 'integer');

			// Get login for counting attempts to login
			if ($this->config->item('login_count_attempts', 'tank_auth') AND
					($login = $this->input->post('login'))) {
				$login = $this->security->xss_clean($login);
			} else {
				$login = '';
			}

			$data['use_recaptcha'] = $this->config->item('use_recaptcha', 'tank_auth');
			if ($this->tank_auth->is_max_login_attempts_exceeded($login)) {
				if ($data['use_recaptcha'])
					$this->form_validation->set_rules('recaptcha_response_field', 'Confirmation Code', 'trim|xss_clean|required|callback__check_recaptcha');
				else
					$this->form_validation->set_rules('captcha', 'Confirmation Code', 'trim|xss_clean|required|callback__check_captcha');
			}
			$data['errors'] = array();

			if ($this->form_validation->run()) {								// validation ok
				if ($this->tank_auth->login(
							$this->form_validation->set_value('login'),
							$this->form_validation->set_value('password'),
							$this->form_validation->set_value('remember'),
							$data['login_by_username'],
							$data['login_by_email'])) {								// success
					
					$wrong_domain_redirect = $this->get_redirect_if_wrong_domain();
					$temp_goto = $this->form_validation->set_value('referer');
					if($temp_goto AND !strpos($temp_goto, 'http'))
					{
						$goto = $temp_goto;
					}
					if($goto == "")
					{
						$goto = 'director';
					}
					if($wrong_domain_redirect !== false)
					{
						$this->tank_auth->logout_without_redirect();
                                                $data['error_message'] = "Email or password is invalid";						
					}
					else
					{
						redirect($goto);
					}
				} else {
					$errors = $this->tank_auth->get_error_message();
					if (isset($errors['banned'])) {								// banned user
						$this->_show_message($this->lang->line('auth_message_banned').' '.$errors['banned']);

					} elseif (isset($errors['not_activated'])) {				// not activated user
						redirect('/auth/send_again/');

					} elseif (isset($errors['archived'])) {                // not archived user
						redirect('/auth/send_again/');					    
					}
					else {													// fail
						foreach ($errors as $k => $v)	$data['errors'][$k] = $this->lang->line($v);
					}
				}
			}
			$data['show_captcha'] = FALSE;
			if ($this->tank_auth->is_max_login_attempts_exceeded($login)) {
				$data['show_captcha'] = TRUE;
				if ($data['use_recaptcha']) {
					$data['recaptcha_html'] = $this->_create_recaptcha();
				} else {
					$data['captcha_html'] = $this->_create_captcha();
				}
			}


			$data['referer'] = $goto;
			$data['partner_unique_id'] = $partner_unique_id;

		/*      
		if($data['partner_unique_id'] > 0)
		{
		$data['partner_id'] = $this->tank_auth->get_partner_by_unique($partner_unique_id);
		$partner_info = $this->tank_auth->get_partner_info($data['partner_id']);
		$data['logo_path'] = $partner_info['partner_report_logo_filepath'];
		$data['favicon_path'] = $partner_info['favicon_path'];
		$data['css_path'] = $partner_info['css_filepath'];
		$data['home_url'] = $partner_info['home_url'];
		$data['contact_number'] = $partner_info['contact_number'];
		$data['partner_name'] = $partner_info['partner_name'];
		$data['contact_number'] = $partner_info['contact_number'];
		$this->load->view('auth/partner_login', $data);
		}*/

			$partner_details = $this->tank_auth->if_partner_return_details();
			if($partner_details)
			{
				$data['logo_path'] = $partner_details['partner_report_logo_filepath'];
				$data['favicon_path'] = $partner_details['favicon_path'] ?: '/images/favicon-1.ico';
				$data['css_path'] = $partner_details['css_filepath'] ?: '//s3.amazonaws.com/brandcdn-assets/partners/base/base_login_style.css';
				$data['home_url'] = $partner_details['home_url'];
				$data['contact_number'] = $partner_details['contact_number'];
				$data['partner_name'] = $partner_details['partner_name'];
				$data['contact_number'] = $partner_details['contact_number'];
				$data['username'] = $login;
				$data['title'] = $data['partner_name'] . ' | Login';
				$this->load->view('auth/header', $data);
				$this->load->view('auth/login_form', $data);
				$this->load->view('auth/footer', $data);
			}
			else
			{
				show_404();
			}
		}
	}
  
	/**
	 * Logout user
	 *
	 * @return void
	 */
	function logout()
	{
		$this->tank_auth->logout();

		//$this->_show_message($this->lang->line('auth_message_logged_out'));
		//redirect('login');
	}

	/**
	 * Register user on the site
	 *
	 * @return void
	 */
	function register()
	{
		if (!$this->tank_auth->is_logged_in()) {		// if not logged in redirect
			$this->session->set_userdata('referer','register');
			redirect('login');
		}
		$temprole = $this->tank_auth->get_role($this->tank_auth->get_username());
		$can_register = ($this->vl_platform->get_access_permission_redirect('register') == '' ? TRUE : FALSE);
		$is_sales_account = FALSE;
		$data['check_email_status'] = FALSE;  // Check for Duplicate Email
		if (!$can_register)
		{
			redirect('login');
		}
		$data['can_register'] = $can_register;
		if ($this->tank_auth->is_logged_in(FALSE)) {						// logged in, not activated
			redirect('/auth/send_again/');

		} elseif (!$this->config->item('allow_registration', 'tank_auth')) {	// registration is off
			$this->_show_message($this->lang->line('auth_message_registration_disabled'));

		} else {
			
			if ($temprole == 'sales') { 
				$use_username = FALSE;
				$is_sales_account = TRUE;
			} else {
				$use_username = $this->config->item('use_username', 'tank_auth');
			}
			$captcha_registration	= $this->config->item('captcha_registration', 'tank_auth');
			$use_recaptcha			= $this->config->item('use_recaptcha', 'tank_auth');
			
			if (!$is_sales_account) {
				$this->form_validation->set_rules('role', 'Role', 'trim|required|xss_clean');
			} else {
				$this->form_validation->set_rules('advertiser_name', 'Advertiser Name', 'trim|required|xss_clean');
			}
			$this->form_validation->set_rules('email', 'Email', 'trim|required|xss_clean|valid_email');
			$this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean|min_length['.$this->config->item('password_min_length', 'tank_auth').']|max_length['.$this->config->item('password_max_length', 'tank_auth').']');//|alpha_dash');
			$this->form_validation->set_rules('confirm_password', 'Confirm Password', 'trim|required|xss_clean|matches[password]');
      		$this->form_validation->set_rules('firstname', 'First Name', 'trim|required|xss_clean');
			$this->form_validation->set_rules('lastname', 'Last Name', 'trim|required|xss_clean');
			$this->form_validation->set_rules('address_1', 'Address 1', '');
			$this->form_validation->set_rules('address_2', 'Address 2', '');
			$this->form_validation->set_rules('city', 'City', '');
			$this->form_validation->set_rules('state', 'State / Province', '');
			$this->form_validation->set_rules('zip', 'Zip / Postal Code', '');
			$this->form_validation->set_rules('phone_number', 'Phone Number', '');
			
			//$this->form_validation->set_rules('bgroup', 'Business Group', 'trim|required|xss_clean');
			if(array_key_exists('role', $_POST) && $_POST['role'] == "BUSINESS" && (!$is_sales_account))
			{
				$this->form_validation->set_rules('advertiser_name', 'Advertiser Name', 'trim|required|xss_clean');
			}
			else if (!$is_sales_account)
			{
				$this->form_validation->set_rules('advertiser_name', 'Advertiser Name', 'trim|xss_clean');
			}

			if(array_key_exists('role', $_POST) && $_POST['role'] == "SALES" && (!$is_sales_account))
			{
				$this->form_validation->set_rules('partner', 'Partner', 'required');
			}

			if ($captcha_registration) {
				if ($use_recaptcha) {
					$this->form_validation->set_rules('recaptcha_response_field', 'Confirmation Code', 'trim|xss_clean|required|callback__check_recaptcha');
				} else {
					$this->form_validation->set_rules('captcha', 'Confirmation Code', 'trim|xss_clean|required|callback__check_captcha');
				}
			}

			$data['errors'] = array();

			$data['user_permissions'] = array(); 
			$data['user_permissions']['can_view_planner'] = (($temprole == 'admin') OR ($this->tank_auth->get_planner_viewable($this->tank_auth->get_user_id()) == '1'));
			$data['user_permissions']['can_view_placements'] = (($temprole == 'admin') OR ($this->tank_auth->get_placements_viewable($this->tank_auth->get_username()) == '1'));
			$data['user_permissions']['can_view_screenshots'] = (($temprole == 'admin') OR ($this->tank_auth->get_beta($this->tank_auth->get_username()) == '1'));
			$data['user_permissions']['can_view_engagements'] = (($temprole == 'admin') OR ($this->tank_auth->get_beta_report_engagements_viewable($this->tank_auth->get_username()) == '1'));
			$data['user_permissions']['can_view_ad_sizes'] = (($temprole == 'admin') OR ($this->tank_auth->get_ad_sizes_viewable($this->tank_auth->get_user_id()) == '1'));
			
			$email_activation = $this->config->item('email_activation', 'tank_auth');
			$isGroupSuper = 0;
			$data['is_group_super_checked'] = false;
			$isGlobalSuper = 0;
			$data['is_global_super_checked'] = false;
			$placements_viewable = 0;
			$data['is_placements_viewable_checked'] = false;
			$planner_viewable = 0;
			$data['is_planner_viewable_checked'] = false;
			$screenshots_viewable = 0;
			$data['is_screenshots_viewable_checked'] = false;
			$engagements_viewable = 0;
			$data['is_engagements_viewable_checked'] = false;
			$ad_sizes_viewable = 0;
			$data['is_ad_sizes_viewable_checked'] = false;

			if(array_key_exists('isGroupSuper', $_POST))
			{
				$isGroupSuper = 1;
				$data['is_group_super_checked'] = true;
			}
			if(array_key_exists('isGlobalSuper', $_POST))
			{
				$isGlobalSuper = 1;
				$data['is_global_super_checked'] = true;
			}
			if(array_key_exists('planner_viewable', $_POST))
			{
				$planner_viewable = 1;
				$data['is_planner_viewable_checked'] = true;
			}
			if(array_key_exists('placements_viewable', $_POST))
			{
				$placements_viewable = 1;
				$data['is_placements_viewable_checked'] = true;
			} 
			if(array_key_exists('screenshots_viewable', $_POST))
			{
				$screenshots_viewable = 1;
				$data['is_screenshots_viewable_checked'] = true;
			} 
			if(array_key_exists('engagements_viewable', $_POST))
			{
				$engagements_viewable = 1;
				$data['is_engagements_viewable_checked'] = true;
			}
			if(array_key_exists('send_registration_welcome_email', $_POST))
			{
				$data['is_send_registration_welcome_email_checked'] = $this->input->post('send_registration_welcome_email') === 'true';
			} 
			if(array_key_exists('ad_sizes_viewable', $_POST))
			{
				$ad_sizes_viewable = 1;
				$data['is_ad_sizes_viewable_checked'] = true;
			}
			if(array_key_exists('advertiser_name', $_POST))
			{
				$data['selected_advertiser'] = $_POST['advertiser_name'];
			}
			if(array_key_exists('partner', $_POST))
			{
				$data['selected_partner'] = $_POST['partner'];
			}
			if(array_key_exists('role', $_POST))
			{
				$data['selected_role'] = $_POST['role'];
			}
			else
			{
				$data['selected_role'] = 'BUSINESS'; // Default selected role
			}
			
			// Default checked values here
			if (!isset($_POST) OR count($_POST) == 0) {
				$data['is_screenshots_viewable_checked'] = true; 
				$data['is_engagements_viewable_checked'] = true;
				$data['is_placements_viewable_checked'] = true;
				$data['is_send_registration_welcome_email_checked'] = false;
			}

			if ($this->form_validation->run())
			{								// validation ok
				if (!$this->tank_auth->is_email_available($_POST['email'])) 
				{							// check for duplicate email
					$data['check_email_status'] = TRUE;
				}
				if ($is_sales_account)
				{
					$created_username = $this->form_validation->set_value('firstname') . '_' . $this->form_validation->set_value('lastname') . '_' . strval(rand()) . strval(microtime(true) * 10000);
				}
				
				$data_copy = $data;

				$additional_fields = array(
					'address_1' => $this->input->post('address_1', true),
					'address_2' => $this->input->post('address_2', true),
					'city' => $this->input->post('city', true),
					'state' => $this->input->post('state', true),
					'zip' => $this->input->post('zip', true),
					'phone_number' => $this->input->post('phone_number', true)
				);

				$send_email_welcome = $this->input->post('send_registration_welcome_email') === 'true';

				$data = $this->tank_auth->create_user(
					$this->form_validation->set_value('email'),
					$this->form_validation->set_value('email'),
					$this->form_validation->set_value('password'),
					$is_sales_account ? 'BUSINESS' : $this->form_validation->set_value('role'),
					$isGlobalSuper,
					'none',
					$isGroupSuper,
					$this->form_validation->set_value('advertiser_name'),
					$this->form_validation->set_value('firstname'),
					$this->form_validation->set_value('lastname'),
					$is_sales_account ? '' : $this->form_validation->set_value('partner'),
					($is_sales_account OR !$data['user_permissions']['can_view_planner']) ? '' : $planner_viewable,
					$data['user_permissions']['can_view_placements'] ? $placements_viewable : '',
					$data['user_permissions']['can_view_screenshots'] ? $screenshots_viewable : '',
					$data['user_permissions']['can_view_engagements'] ? $engagements_viewable : '',
					0, //$data['user_permissions']['can_view_ad_sizes'] ? $ad_sizes_viewable : '',
					$email_activation,
					$additional_fields
				);

				if (!is_null($data)) // success
				{
					$data['site_name'] = $this->config->item('website_name', 'tank_auth');
					/*if ($email_activation)
					{									// send "activate" email
						$data['activation_period'] = $this->config->item('email_activation_expire', 'tank_auth') / 3600;

						$this->_send_email('activate', $data['email'], $data);

						unset($data['password']); // Clear password (just for any case)

						$this->_show_message($this->lang->line('auth_message_registration_completed_1'));

					}
					else
					{
						if ($this->config->item('email_account_details', 'tank_auth')) {	// send "welcome" email

							$this->_send_email('welcome', $data['email'], $data);
						}*/
						
					$message = 'New user successfully created!';
					if($send_email_welcome)
					{
						$cname = $this->vl_auth_model->get_cname_for_user($data['user_id']);
						if(empty($cname))
						{
							$cname = 'frequence';
						}

						$partner_details = $this->vl_platform_model->get_user_profile_details($data['user_id']);
						$result = false;
						if($partner_details)
						{
							$email_data = array(
								'first_name' => $data['firstname'],
								'last_name' => $data['lastname'],
								'cname' => $cname,
								'email' => $data['email'],
								'password' => $data['password']
							);

							$email_link = $this->vl_auth_model->get_email_html_for_partner($partner_details['partner_id']);
							$email_html = file_get_html($email_link);
							if($this->configure_registration_email($email_html, $email_data))
							{
								$result = mailgun(
									"noreply@{$cname}.brandcdn.com",
									$email_data['email'],
									"Welcome to {$cname}.brandcdn.com",
									$email_html->save(),
									'html',
									array( 
										'Reply-To' => "noreply@{$cname}.brandcdn.com"
									)
								);
							}
						}
						$message .= ($result) ? '<br>A message containing the new credentials has been sent to the email address provided.' : "<br>Error configuring/sending email";
					}
					unset($data['password']); // Clear password (just for any case)
					$this->session->set_flashdata('message', $message);
    				redirect(current_url(), 'refresh');
					//}
				} 
				else
				{
					$data = $data_copy;
					$errors = $this->tank_auth->get_error_message();
					foreach ($errors as $k => $v)	$data['errors'][$k] = $this->lang->line($v);
				}
			}

			if ($captcha_registration)
			{
				if ($use_recaptcha)
				{
					$data['recaptcha_html'] = $this->_create_recaptcha();
				}
				else
				{
					$data['captcha_html'] = $this->_create_captcha();
				}
			}
			$data['use_username'] = $use_username;
			$data['is_sales_account'] = $is_sales_account;
			$data['captcha_registration'] = $captcha_registration;
			$data['use_recaptcha'] = $use_recaptcha;
			$data['business_name_options'] = array();

			if (!$is_sales_account)
			{
				$data['business_name_options'] = $this->tank_auth->get_advertisers_by_user_id_for_admin_and_ops($this->tank_auth->get_user_id());
			}
			else
			{
				$data['business_name_options'] = $this->tank_auth->get_advertisers_by_user_id_and_is_super($this->tank_auth->get_user_id(), $this->tank_auth->get_isGroupSuper($this->tank_auth->get_username()));
			}
			
			$data['business_name_option_array'] = array();
			if ($data['business_name_options'])
			{
				foreach($data['business_name_options'] as $value)
				{
					settype($value['id'], "int");
					$data['business_name_option_array'][] = array(
						'id' => $value['id'],
						'partner' => $value['partner_name'],
						'advertiser' => $value['Name'],
						'text' => $value['partner_name'] . ' ' . $value["Name"]
					);
				}
			}
			
			$data['business_name_option_array'] = json_encode($data['business_name_option_array']);
			$data['width'] = '452px';

			$data['partner_name_options'] = $this->tank_auth->get_partner_array(); 
			$data['partner_name_option_array'] = array();
			if ($data['partner_name_options'])
			{
				foreach($data['partner_name_options'] as $value)
				{
					settype($value['id'], "int");
					$data['partner_name_option_array'][] = array(
						'id' => $value['id'],
						'text' => $value['partner_name'] 
					);
				}
			}
			$data['partner_name_option_array'] = json_encode($data['partner_name_option_array']);      

			$data['is_logged_in'] = true;

			$session_id = $this->session->userdata('session_id');
			$data['session_id'] = $session_id;

			$data['title'] = 'Register';
			
			$email_firstname = array_key_exists('firstname', $_POST) ? $this->input->post('firstname') : '{First Name}';
			$email_lastname = array_key_exists('lastname', $_POST) ? $this->input->post('lastname') : '{Last Name}';
			$email_email = array_key_exists('email', $_POST) ? $this->input->post('email') : '{Email}';
			$email_password = array_key_exists('password', $_POST) ? $this->input->post('password') : '{Password}';

			$role_id = null;
			if($data['selected_role'] == 'BUSINESS')
			{
				$role_id = (array_key_exists('advertiser_name', $_POST)) ? $this->input->post('advertiser_name') : null;
			}
			else if($data['selected_role'] == 'SALES')
			{
				$role_id = (array_key_exists('partner', $_POST)) ? $this->input->post('partner') : null;
			}

			$email_cname = $this->get_cname_for_register_page($data['selected_role'], $role_id);
			$data['email_contents'] = 
			"	Hello <span id='email_first_name'>{$email_firstname}</span> <span id='email_last_name'>{$email_lastname}</span>,
				Welcome to the <span class='email_cname'>{$email_cname}</span>.brandcdn.com platform.

				Your login credentials are as follows:
				url: <span class='email_cname'>{$email_cname}</span>.brandcdn.com
				username: <span id='email_username'>{$email_email}</span>
				password: <span id='email_password'>{$email_password}</span>
			";

			$this->vl_platform->show_views(
				$this,
				$data,
				'register',
				'auth/register_form',
				NULL,
				'auth/register_page_css',
				'auth/register_page_js',
				NULL
				// , false
			);
		}
	}

	private function configure_registration_email(&$email_html, $email_data)
	{
		if(!$email_html) return false;

		$replacements = array(
			'innertext' => array(
				'.user_fullname' => $email_data['first_name'] . ' ' . $email_data['last_name'],
				'.user_cname' => $email_data['cname'],
				'.user_username' => $email_data['email'],
				'.user_email' => $email_data['email'],
				'.user_password' => $email_data['password'],
				'.user_login_url' => $email_data['cname'] . '.brandcdn.com'
			),
			'href' => array(
				'.user_email_href' => 'mailto:' . $email_data['email'],
				'.user_login_url' => $email_data['cname'] . '.brandcdn.com'
			)
		);

		foreach($replacements as $replacement_type => $replacement_type_array)
		{
			foreach($replacement_type_array as $html_id => $user_data)
			{
				foreach($email_html->find($html_id) as $to_be_replaced)
				{
					$to_be_replaced->{$replacement_type} = $user_data;
				}
			}
			
		}
		return true;
	}

	private function get_cname_for_register_page($role, $role_id)
	{
		if($role == 'BUSINESS')
		{
			if(is_numeric($role_id))
			{
				return $this->vl_platform_model->get_cname_from_advertiser_id($role_id);
			}
			return '{Partner Name}';
		}
		else if($role == 'SALES')
		{
			if(is_numeric($role_id))
			{
				return $this->vl_platform_model->get_cname_from_sales_partner_id($role_id);
			}
			return '{Partner Name}';
		}
		else
		{
			return 'secure';
		}
	}

	public function get_cname_for_user_by_ajax()
	{
		$allowed_roles = array('admin', 'ops', 'sales');
		$post_variables = array('role', 'role_id');

		$response = vl_verify_ajax_call($allowed_roles, $post_variables);
		$is_success = $response['is_success'];
		$cname = '{Partner Name}';
		if ($is_success) 
		{
			$role = $response['post']['role'];
			$role_id = $response['post']['role_id'];

			$cname = $this->get_cname_for_register_page($role, $role_id);
			$is_success = $is_success && ($cname ? true : false);
		}
		echo json_encode(array('is_success' => $is_success, 'cname' => $cname));
	}

	/**
	 * Send activation email again, to the same or new email address
	 *
	 * @return void
	 */
	function send_again()
	{
		if (!$this->tank_auth->is_logged_in(FALSE)) {							// not logged in or activated
			redirect('/auth/login/');

		} else {
			$this->form_validation->set_rules('email', 'Email', 'trim|required|xss_clean|valid_email');

			$data['errors'] = array();

			if ($this->form_validation->run()) {								// validation ok
				if (!is_null($data = $this->tank_auth->change_email(
								 $this->form_validation->set_value('email')))) {			// success

					$data['site_name']	= $this->config->item('website_name', 'tank_auth');
					$data['activation_period'] = $this->config->item('email_activation_expire', 'tank_auth') / 3600;

					$this->_send_email('activate', $data['email'], $data);

					$this->_show_message(sprintf($this->lang->line('auth_message_activation_email_sent'), $data['email']));

				} else {
					$errors = $this->tank_auth->get_error_message();
					foreach ($errors as $k => $v)	$data['errors'][$k] = $this->lang->line($v);
				}
			}
			$this->load->view('auth/send_again_form', $data);
		}
	}

	/**
	 * Activate user account.
	 * User is verified by user_id and authentication code in the URL.
	 * Can be called by clicking on link in mail.
	 *
	 * @return void
	 */
	function activate()
	{
		$user_id		= $this->uri->segment(3);
		$new_email_key	= $this->uri->segment(4);

		// Activate user
		if ($this->tank_auth->activate_user($user_id, $new_email_key)) {		// success
			$this->tank_auth->logout();
			$this->_show_message($this->lang->line('auth_message_activation_completed').' '.anchor('/auth/login/', 'Login'));

		} else {																// fail
			$this->_show_message($this->lang->line('auth_message_activation_failed'));
		}
	}

	/**
	 * Generate reset code (to change password) and send it to user
	 *
	 * @return void
	 */
	function forgot_password()
	{
		$reset_other_user_id = false;
		$reset_other_return_data = array();
		$reset_other_return_data['is_success'] = true;
		if(array_key_exists('reset_user_id', $_POST))
		{
			$reset_other_user_id = $this->input->post('reset_user_id');
			$reset_user_name = $this->input->post('reset_user_name');
			if($reset_other_user_id === false || $reset_user_name === false)
			{
				$reset_other_return_data['is_success'] = false;
				echo json_encode($reset_other_return_data);
				return;
			}
		}
		if($this->tank_auth->is_logged_in() && $reset_other_user_id == false) // logged in
		{									
			redirect('/auth/business/');
		}
		elseif($this->tank_auth->is_logged_in(FALSE) && $reset_other_user_id == false) // logged in, not activated
		{						
			redirect('/auth/send_again/');
		}
		else
		{
			$this->form_validation->set_rules('login', 'Login Name', 'trim|required|xss_clean');

			$data['errors'] = array();

			$partner_details = $this->tank_auth->if_partner_return_details();
			if($partner_details)
			{
				$data['logo_path'] = $partner_details['partner_report_logo_filepath'];
				$data['favicon_path'] = $partner_details['favicon_path'];
				$data['css_path'] = $partner_details['css_filepath'] ?: '//s3.amazonaws.com/brandcdn-assets/partners/base/base_login_style.css';
				$data['home_url'] = $partner_details['home_url'];
				$data['contact_number'] = $partner_details['contact_number'];
				$data['partner_name'] = $partner_details['partner_name'];
				$data['contact_number'] = $partner_details['contact_number'];
				$data['title'] = $data['partner_name'] . ' | Reset Password';
				if($reset_other_user_id == false)
				{
					$this->load->view('auth/header', $data);
				}
				
				if ($reset_other_user_id != false || $this->form_validation->run())
				{								// validation ok
					if ($reset_other_user_id != false || !is_null($data = $this->tank_auth->forgot_password($this->form_validation->set_value('login'))))
					{
						//Commented out to allow for partner URL to show up on returned e-mails
						//$data['site_name'] = $this->config->item('website_name', 'tank_auth');
	 
						// Send email with password activation link - 
	          
						//BEGIN NOT TANK_AUTH CODE - Run reset procedure, and reset with a new password that's randomly generated
						//and pass information to the e-mail to be sent out
						//---------------------------------------------------------------------------------------------------------------

						if($reset_other_user_id != false)
						{
							$data['user_id'] = $reset_other_user_id;
							$data['username'] = $reset_user_name;
							$data['role'] = $this->tank_auth->get_role($data['username']);
							$data['email'] = $this->tank_auth->get_email($data['username']);
							$data['firstname'] = $this->tank_auth->get_firstname($data['username']);
							$data['lastname'] = $this->tank_auth->get_lastname($data['username']);
						}						

						$upper_role = strtoupper($data['role']);
						if($upper_role == 'SALES' ||
							$upper_role == 'AGENCY' ||
							$upper_role == 'CLIENT'
						)
						{
							$partner_data = $this->tank_auth->get_partner_info($this->tank_auth->get_partner_id($data['user_id']));
						}
						else if($upper_role == 'BUSINESS')
						{
							//SALES TO BPARTNERETEHTAFAH
							$partner_data = $this->tank_auth->get_partner_info($this->tank_auth->get_partner_by_business($this->tank_auth->get_biz_name($data['username'])));
	              
						}
						else 
						{
							$partner_data = $this->tank_auth->get_partner_info(1);
						}
	          
						$data['partner_name'] = $partner_data['partner_name'];
						$data['partner_url'] = $partner_data['cname'] . '.' . $this->tank_auth->get_domain_without_cname();

						$rand = substr(md5(microtime()),rand(0,26),10); // Create random string to avoid dupes
						if ($this->users->set_password_key($data['user_id'], $rand))
						{
							$data['new_pass_key'] = $rand;

							$type = 'forgot_password';
							$result = mailgun(
								'noreply@'.$data['partner_url'],
								$data['email'],
								sprintf($this->lang->line('auth_subject_'.$type), $data['partner_url']),
								$this->load->view('email/'.$type.'-html', $data, TRUE),
								"html",
								array( 
									'bcc' => 'matt.robles@vantagelocal.com',
									'Reply-To' => 'noreply@'.$data['partner_url']
								)
							);
							if($reset_other_user_id != false)
							{
								echo json_encode($reset_other_return_data);
								return;
							}
							$data['message'] = sprintf($this->lang->line('auth_message_reset_password_email_sent'), $data['email']);
						}
						else
						{
							$data['message'] = $this->lang->line('auth_message_reset_password_request_failed');
						}

						$this->load->view('auth/reset_password_confirmation', $data);

					} 
					else // username / email did not exist
					{
						$errors = $this->tank_auth->get_error_message();
						foreach ($errors as $k => $v)	$data['errors'][$k] = $this->lang->line($v);
						$this->load->view('auth/forgot_password_form', $data);
					}
				}
				else // nothing was submitted
				{
					$this->load->view('auth/forgot_password_form', $data);	
				}

				$this->load->view('auth/footer', $data);
			}
			else
			{
				show_404();
			}
		}
	}

	/**
	 * Replace user password (forgotten) with a new one (set by user).
	 * User is verified by user_id and authentication code in the URL.
	 * Can be called by clicking on link in mail.
	 *
	 * @return void
	 */
	function reset_password()
	{
      
		$user_id		= $this->uri->segment(2);
		$new_pass_key	= $this->uri->segment(3);

		$this->form_validation->set_rules('new_password', 'New Password', 'trim|required|xss_clean|min_length['.$this->config->item('password_min_length', 'tank_auth').']|max_length['.$this->config->item('password_max_length', 'tank_auth').']');//|alpha_dash');
		$this->form_validation->set_rules('confirm_new_password', 'Confirm new Password', 'trim|required|xss_clean|matches[new_password]');

		$data['errors'] = array();

		$partner_details = $this->tank_auth->if_partner_return_details();
		if($partner_details)
		{
			$data['logo_path'] = $partner_details['partner_report_logo_filepath'];
			$data['favicon_path'] = $partner_details['favicon_path'];
			$data['css_path'] = $partner_details['css_filepath'] ?: '//s3.amazonaws.com/brandcdn-assets/partners/base/base_login_style.css';
			$data['home_url'] = $partner_details['home_url'];
			$data['contact_number'] = $partner_details['contact_number'];
			$data['partner_name'] = $partner_details['partner_name'];
			$data['contact_number'] = $partner_details['contact_number'];
			$data['title'] = $data['partner_name'] . ' | Reset Password';
			$this->load->view('auth/header', $data);

			if ($this->form_validation->run()) {								// validation ok
				if (!is_null($data = $this->tank_auth->reset_password(
								 $user_id, $new_pass_key,
								 $this->form_validation->set_value('new_password')))) {	// success

					$upper_role = strtoupper($data['role']);
					if($upper_role == 'SALES' ||
						$upper_role == 'AGENCY' ||
						$upper_role == 'CLIENT'
					)
					{
						$partner_data = $this->tank_auth->get_partner_info($this->tank_auth->get_partner_id($data['user_id']));
					}
					else if($upper_role == 'BUSINESS')
					{
						//SALES TO BPARTNERETEHTAFAH
						$partner_data = $this->tank_auth->get_partner_info($this->tank_auth->get_partner_by_business($this->tank_auth->get_biz_name($data['username'])));
	          
					}
					else 
					{
						$partner_data = $this->tank_auth->get_partner_info(1);
					}
	      
					$data['partner_name'] = $partner_data['partner_name'];
					$data['partner_url'] = $partner_data['cname'] . '.' . $this->tank_auth->get_domain_without_cname();

					$type = 'reset_password';
					$result = mailgun(
						'noreply@'.$data['partner_url'],
						$data['email'],
						sprintf($this->lang->line('auth_subject_'.$type), $data['partner_url']),
						$this->load->view('email/'.$type.'-html', $data, TRUE),
						"html",
						array(
							'Reply-To' => 'noreply@'.$data['partner_url']
						)
					);

					$data['message'] = $this->lang->line('auth_message_new_password_activated').' '.anchor('login', 'Login');

				} else {														// fail
					$data['message'] = $this->lang->line('auth_message_new_password_failed');
				}
				
				$this->load->view('auth/reset_password_confirmation', $data);

			} else {
				// Try to activate user by password key (if not activated yet)
				if ($this->config->item('email_activation', 'tank_auth')) {
					$this->tank_auth->activate_user($user_id, $new_pass_key, FALSE);
				}

				$expire_period = (60 * 60 * 6); // 6-hour expire period
				if (!$this->tank_auth->can_reset_password($user_id, $new_pass_key, $expire_period)) {
					$data['message'] = $this->lang->line('auth_message_new_password_failed');
					$this->load->view('auth/reset_password_confirmation', $data);
				}
				else
				{
					//$this->load->view('auth/header', $data);
					$this->load->view('auth/reset_password_form', $data);
				}
			}
			
			$this->load->view('auth/footer', $data);
		}
		else
		{
			show_404();
		}
	}

	/**
	 * Adds first name, last name and password to a new user.
	 * User is verified by authentication code in the URL. this method to be used only for new users with role in client or agency
	 * Can be called by clicking on link in mail.
	 *
	 * @return void
	 */
	public function client_invite()
	{
      	
		$this->form_validation->set_rules('new_password', 'New Password', 'trim|required|xss_clean|min_length['.$this->config->item('password_min_length', 'tank_auth').']|max_length['.$this->config->item('password_max_length', 'tank_auth').']|alpha_dash');
		$this->form_validation->set_rules('confirm_new_password', 'Confirm new Password', 'trim|required|xss_clean|matches[new_password]');
		$new_pass_key = $this->uri->segment(2);
		$user_id=null;
		$error_flag=false;
		$data['message']="";
		$data['success_flag']=FALSE;
		$data['errors'] = array();
		$email="";
		if ($new_pass_key != "")
		{
			$user_data=$this->users->get_user_by_new_password_key_client_agency($new_pass_key);
			if (!$user_data)
			{
				$data['message']="Link has expired. Unable to signup using this expired link.";
				$error_flag=true;
			}
			else
			{
				$user_id=$user_data['id'];
				$email=$user_data['email'];
				$first_name = $user_data['firstname'];
 				$last_name = $user_data['lastname'];
				$data['user_id']=$user_id;
				$data['email']=$email;
				$data['first_name'] = $first_name;
				$data['last_name'] = $last_name;
			}
		}
		if ($this->input->post('first_name') != null && $this->form_validation->run()) 
 		{
			$this->users->update_first_name_last_name($user_id, $this->input->post('first_name'), $this->input->post('last_name'));
                        // Pass 1 for registration
			$data = $this->tank_auth->reset_password($user_id, $new_pass_key, $this->form_validation->set_value('new_password'), 1);
			$data['message'] = $this->lang->line('auth_message_new_password_activated').' '.anchor('login', 'Login');
			$data['success_flag']=TRUE;
		}
		$partner_details = $this->tank_auth->if_partner_return_details();
		$data['logo_path'] = $partner_details['partner_report_logo_filepath'];
		$data['favicon_path'] = $partner_details['favicon_path'];
		$data['css_path'] = $partner_details['css_filepath'] ?: '//s3.amazonaws.com/brandcdn-assets/partners/base/base_login_style.css';
		$data['home_url'] = $partner_details['home_url'];
		$data['contact_number'] = $partner_details['contact_number'];
		$data['title'] =  ' | Welcome ';
		$data['email']=$email;
		$partner_data = $this->tank_auth->get_partner_info($this->tank_auth->get_partner_id($user_id));
		$data['partner_name'] = $partner_data['partner_name'];
		$data['partner_url'] = $partner_data['cname'] . '.' . $this->tank_auth->get_domain_without_cname();
		$this->load->view('auth/header', $data);
		$this->load->view('auth/new_spectrum_client_welcome_form', $data);		
		$this->load->view('auth/footer', $data);
	}	


	/**
	 * Change user password
	 *
	 * @return void
	 */
	function change_password()
	{
		if (!$this->tank_auth->is_logged_in()) {								// not logged in or not activated
			redirect('login');

		} else {
			$this->form_validation->set_rules('old_password', 'Old Password', 'trim|required|xss_clean');
			$this->form_validation->set_rules('new_password', 'New Password', 'trim|required|xss_clean|min_length['.$this->config->item('password_min_length', 'tank_auth').']|max_length['.$this->config->item('password_max_length', 'tank_auth').']');//|alpha_dash');
			$this->form_validation->set_rules('confirm_new_password', 'Confirm new Password', 'trim|required|xss_clean|matches[new_password]');

			$data['errors'] = array();

			if ($this->form_validation->run()) {								// validation ok
				if ($this->tank_auth->change_password(
						$this->form_validation->set_value('old_password'),
						$this->form_validation->set_value('new_password'))) {	// success
					$this->_show_message($this->lang->line('auth_message_password_changed').' '.anchor('login', 'Home'));

				} else {														// fail
					$errors = $this->tank_auth->get_error_message();
					foreach ($errors as $k => $v)	$data['errors'][$k] = $this->lang->line($v);
				}
			}

			$this->load->view('auth/change_password_form', $data);
		}
	}

	/**
	 * Change user email
	 *
	 * @return void
	 */
	function change_email()
	{
		if (!$this->tank_auth->is_logged_in()) {								// not logged in or not activated
			redirect('login');

		} else {
			$this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean');
			$this->form_validation->set_rules('email', 'Email', 'trim|required|xss_clean|valid_email');

			$data['errors'] = array();

			if ($this->form_validation->run()) {								// validation ok
				if (!is_null($data = $this->tank_auth->set_new_email(
							     $this->form_validation->set_value('email'),
							     $this->form_validation->set_value('password')))) {			// success

					$data['site_name'] = $this->config->item('website_name', 'tank_auth');

					// Send email with new email address and its activation link
					$this->_send_email('change_email', $data['new_email'], $data);

					$this->_show_message(sprintf($this->lang->line('auth_message_new_email_sent'), $data['new_email']));

				} else {
					$errors = $this->tank_auth->get_error_message();
					foreach ($errors as $k => $v)	$data['errors'][$k] = $this->lang->line($v);
				}
			}
			$this->load->view('auth/change_email_form', $data);
		}
	}

	/**
	 * Replace user email with a new one.
	 * User is verified by user_id and authentication code in the URL.
	 * Can be called by clicking on link in mail.
	 *
	 * @return void
	 */
	function reset_email()
	{
		$user_id		= $this->uri->segment(3);
		$new_email_key	= $this->uri->segment(4);

		// Reset email
		if ($this->tank_auth->activate_new_email($user_id, $new_email_key)) {	// success
			$this->tank_auth->logout();
			$this->_show_message($this->lang->line('auth_message_new_email_activated').' '.anchor('login', 'Login'));

		} else {																// fail
			$this->_show_message($this->lang->line('auth_message_new_email_failed'));
		}
	}

	/**
	 * Delete user from the site (only when user is logged in)
	 *
	 * @return void
	 */
	function unregister()
	{
		if (!$this->tank_auth->is_logged_in()) {								// not logged in or not activated
			redirect('login');

		} else {
			$this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean');

			$data['errors'] = array();

			if ($this->form_validation->run()) {								// validation ok
				if ($this->tank_auth->delete_user(
						$this->form_validation->set_value('password'))) {		// success
					$this->_show_message($this->lang->line('auth_message_unregistered'));

				} else {														// fail
					$errors = $this->tank_auth->get_error_message();
					foreach ($errors as $k => $v)	$data['errors'][$k] = $this->lang->line($v);
				}
			}
			$this->load->view('auth/unregister_form', $data);
		}
	}

	/**
	 * Show info message
	 *
	 * @param	string
	 * @return	void
	 */
	function _show_message($message)
	{
		$this->session->set_flashdata('message', $message);
		redirect('/auth/');
	}

	/**
	 * Send email message of given type (activate, forgot_password, etc.)
	 *
	 * @param	string
	 * @param	string
	 * @param	array
	 * @return	void
	 */
	function _send_email($type, $email, &$data)
	{
      
		//Generate 'from' address based on $email
		/*
		  $this->load->library('email');
		  $this->email->from($this->config->item('webmaster_email', 'tank_auth'), $this->config->item('website_name', 'tank_auth'));
		  //$this->email->from(("noreply@" + $data['email_source']), ($data['site_name']."_password_recovery"));
		  $this->email->reply_to(("noreply@".$data['email_source']), ($data['site_name']."_password_recovery"));
		  //$this->email->reply_to($this->config->item('webmaster_email', 'tank_auth'), $this->config->item('website_name', 'tank_auth'));
		  $this->email->to($email);
		  $this->email->subject(sprintf($this->lang->line('auth_subject_'.$type), $data['partner_name']));
		  $this->email->message($this->load->view('email/'.$type.'-html', $data, TRUE));
		  $this->email->set_alt_message($this->load->view('email/'.$type.'-txt', $data, TRUE));
		  $this->email->send();
		*/
    
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'From: noreply@'.$data['email_source']. "\r\n" .
			'Bcc: matt.robles@vantagelocal.com'. "\r\n" .
			'Reply-To: noreply@'.$data['email_source']. "\r\n" .
			'X-Mailer: PHP/' . phpversion() . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

		$subject = sprintf($this->lang->line('auth_subject_'.$type), $data['partner_name']);
		$message = $this->load->view('email/'.$type.'-html', $data, TRUE);
		$success = mail($email, $subject, $message, $headers);
      
	}

	/**
	 * Create CAPTCHA image to verify user as a human
	 *
	 * @return	string
	 */
	function _create_captcha()
	{
		$this->load->helper('captcha');

		$cap = create_captcha(array(
								  'img_path'		=> './'.$this->config->item('captcha_path', 'tank_auth'),
								  'img_url'		=> base_url().$this->config->item('captcha_path', 'tank_auth'),
								  'font_path'		=> './'.$this->config->item('captcha_fonts_path', 'tank_auth'),
								  'font_size'		=> $this->config->item('captcha_font_size', 'tank_auth'),
								  'img_width'		=> $this->config->item('captcha_width', 'tank_auth'),
								  'img_height'	=> $this->config->item('captcha_height', 'tank_auth'),
								  'show_grid'		=> $this->config->item('captcha_grid', 'tank_auth'),
								  'expiration'	=> $this->config->item('captcha_expire', 'tank_auth'),
								  ));

		// Save captcha params in session
		$this->session->set_flashdata(array(
										  'captcha_word' => $cap['word'],
										  'captcha_time' => $cap['time'],
										  ));

		return $cap['image'];
	}

	/**
	 * Callback function. Check if CAPTCHA test is passed.
	 *
	 * @param	string
	 * @return	bool
	 */
	function _check_captcha($code)
	{
		$time = $this->session->flashdata('captcha_time');
		$word = $this->session->flashdata('captcha_word');

		list($usec, $sec) = explode(" ", microtime());
		$now = ((float)$usec + (float)$sec);

		if ($now - $time > $this->config->item('captcha_expire', 'tank_auth')) {
			$this->form_validation->set_message('_check_captcha', $this->lang->line('auth_captcha_expired'));
			return FALSE;

		} elseif (($this->config->item('captcha_case_sensitive', 'tank_auth') AND
				   $code != $word) OR
				  strtolower($code) != strtolower($word)) {
			$this->form_validation->set_message('_check_captcha', $this->lang->line('auth_incorrect_captcha'));
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Create reCAPTCHA JS and non-JS HTML to verify user as a human
	 *
	 * @return	string
	 */
	function _create_recaptcha()
	{
		$this->load->helper('recaptcha');

		// Add custom theme so we can get only image
		$options = "<script>var RecaptchaOptions = {theme: 'custom', custom_theme_widget: 'recaptcha_widget'};</script>\n";

		// Get reCAPTCHA JS and non-JS HTML
		$html = recaptcha_get_html($this->config->item('recaptcha_public_key', 'tank_auth'));

		return $options.$html;
	}

	/**
	 * Callback function. Check if reCAPTCHA test is passed.
	 *
	 * @return	bool
	 */
	function _check_recaptcha()
	{
		$this->load->helper('recaptcha');

		$resp = recaptcha_check_answer($this->config->item('recaptcha_private_key', 'tank_auth'),
									   $_SERVER['REMOTE_ADDR'],
									   $_POST['recaptcha_challenge_field'],
									   $_POST['recaptcha_response_field']);

		if (!$resp->is_valid) {
			$this->form_validation->set_message('_check_recaptcha', $this->lang->line('auth_incorrect_captcha'));
			return FALSE;
		}
		return TRUE;
	}

	private function redirect_if_partner_unique_id($unique_id)
	{
		if($unique_id == '0')
		{
			return;
		}
		else
		{
			$this->tank_auth->redirect_to_partner_unique_id($unique_id);
		}
	}
}

/* End of file auth.php */
/* Location: ./application/controllers/auth.php */
