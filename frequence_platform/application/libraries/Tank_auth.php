<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once('phpass-0.1/PasswordHash.php');

define('STATUS_ACTIVATED', '1');
define('STATUS_NOT_ACTIVATED', '0');

/**
 * Tank_auth
 *
 * Authentication library for Code Igniter.
 *
 * @package		Tank_auth
 * @author		Ilya Konyukhov (http://konyukhov.com/soft/)
 * @version		1.0.9
 * @based on	DX Auth by Dexcell (http://dexcell.shinsengumiteam.com/dx_auth)
 * @license		MIT License Copyright (c) 2008 Erick Hartanto
 */
class Tank_auth
{
	private $error = array();

	function __construct()
	{
		$this->ci =& get_instance();

		$this->ci->load->config('tank_auth', TRUE);

		$this->ci->load->library('session');
		$this->ci->load->database();
		$this->ci->load->model('tank_auth/users');

		// Try to autologin
		$this->autologin();
	}

	/**
	 * Login user on the site. Return TRUE if login is successful
	 * (user exists and activated, password is correct), otherwise FALSE.
	 *
	 * @param	string	(username or email or both depending on settings in config file)
	 * @param	string
	 * @param	bool
	 * @return	bool
	 */
	function login($login, $password, $remember, $login_by_username, $login_by_email)
	{
		if ((strlen($login) > 0) AND (strlen($password) > 0)) {

			// Which function to use to login (based on config)
			if ($login_by_username AND $login_by_email) {
				$get_user_func = 'get_user_by_login';
			} else if ($login_by_username) {
				$get_user_func = 'get_user_by_username';
			} else {
				$get_user_func = 'get_user_by_email';
			}

			if (!is_null($user = $this->ci->users->$get_user_func($login))) {	// login ok
				$demo_partner = $this->ci->users->get_partner_details_by_id($user->partner_id);
				// Does password match hash in database?
				$hasher = new PasswordHash(
						$this->ci->config->item('phpass_hash_strength', 'tank_auth'),
						$this->ci->config->item('phpass_hash_portable', 'tank_auth'));
				if ($hasher->CheckPassword($password, $user->password)) {		// password ok
					//Check Archived User
					if(!empty($demo_partner))
					{
						$is_demo_partner = $demo_partner->is_demo_partner;					    
					}
					else
					{
						$is_demo_partner = '';
					}   
					if($is_demo_partner)
					{
					    $current_date = date_create(date('Y-m-d H:i:s'));
					    $archive_date = date_create($user->archived);
					    $diff_archived_date = date_diff($current_date,$archive_date);
					    $diff_archived_date = intval($diff_archived_date->format("%r%a"));
					    if($diff_archived_date <= 0)
					    {						
						$this->error = array('archived' => 'Current User is archived and cannot be logged in');
						return FALSE;
					    }
					}
					if ($user->banned == 1) {									// fail - banned
						$this->error = array('banned' => $user->ban_reason);

					} else {
						$this->ci->session->set_userdata(array(
									'user_id'	=> $user->id,
									'username'	=> $user->username,
									'is_demo_partner'=> $is_demo_partner,
									'status'	=> ($user->activated == 1) ? STATUS_ACTIVATED : STATUS_NOT_ACTIVATED,
									));

						if ($user->activated == 0) {							// fail - not activated
							$this->error = array('not_activated' => '');

						} else {												// success
							if ($remember) {
								$this->create_autologin($user->id);
							}

							$this->clear_login_attempts($login);

							$this->ci->users->update_login_info(
								$user->id,
								$this->ci->config->item('login_record_ip', 'tank_auth'),
								$this->ci->config->item('login_record_time', 'tank_auth'));

							if($user->role == "ADMIN" || $user->role == "OPS" || $user->role == "CREATIVE")
							{
								//Cookie drop
								setcookie(constant('ENVIRONMENT')."-gp-approve", constant('ENVIRONMENT')."_".$user->role, time()+$this->ci->config->item('sess_expiration'), $this->ci->config->item('cookie_path'), ".".g_second_level_domain);
							}
							return TRUE;
						}
					}
				} else {														// fail - wrong password
					$this->increase_login_attempt($login);
					$this->error = array('password' => 'auth_incorrect_password');
				}
			} else {															// fail - wrong login
				$this->increase_login_attempt($login);
				$this->error = array('login' => 'auth_incorrect_login');
			}
		}
		return FALSE;
	}


	function logout_without_redirect()
	{
		setcookie(constant('ENVIRONMENT')."-gp-approve","", time()-3600, $this->ci->config->item('cookie_path'), ".".g_second_level_domain);

		$this->delete_autologin();
		// See http://codeigniter.com/forums/viewreply/662369/ as the reason for the next line
		$this->ci->session->set_userdata(array('user_id' => '', 'username' => '', 'status' => '' ,'is_demo_partner' => ''));
		
		$this->ci->session->sess_destroy();
	}

	/**
	 * Logout user from the site
	 *
	 * @return	void
	 */
	function logout()
	{
		$login = 'login';
		$this->logout_without_redirect();
		redirect($login);
	}

	/**
	 * Check if user logged in. Also test if user is activated or not.
	 *
	 * @param	bool
	 * @return	bool
	 */
	function is_logged_in($activated = TRUE)
	{
		return $this->ci->session->userdata('status') === ($activated ? STATUS_ACTIVATED : STATUS_NOT_ACTIVATED);
	}

	/**
	 * Get user_id
	 *
	 * @return	string
	 */
	function get_user_id()
	{
		return $this->ci->session->userdata('user_id');
	}
	/**
	  * Get user_id by email
	  *
	  * @return  string
	  */
	function get_user_id_by_email($email)
	{
		return $this->ci->users->get_user_by_email($email);
	}
	/**
	  * Get user by id
	  *
	  * @return  bool
	  */
	function get_user_by_id($id)
	{
		return $this->ci->users->get_user_by_id($id, TRUE);
	}
	/**
	  * Get user session id by mpq_sessions_and_submissions id
	  *
	  * @return  bool
	  */
	function get_session_by_mpq_session_id($mpq_session_id, $session_id)
	{
		return $this->ci->users->get_session_by_mpq_session_id($mpq_session_id, $session_id);
	}

	/**
	 * Get username
	 *
	 * @return	string
	 */
	function get_username()
	{
		return $this->ci->session->userdata('username');
	}
	function can_register()
	{
		$temprole = $this->get_role($this->get_username());
		return ($temprole == 'admin' OR $temprole == 'ops' OR (
					$temprole == 'sales' AND (
						$this->get_is_partner_owner_by_user_id($this->get_user_id()) OR 
						$this->get_isGroupSuper($this->get_username())
					)
				));
	}
	function get_partner_array()
	{
		return $this->ci->users->get_all_partners();
	}
	function get_business_array()
	{
		return $this->ci->users->get_business_option_array();
	}
	function get_business_and_campaign_by_id($campaign_id)
	{
		return $this->ci->users->get_business_and_campaign_by_id($campaign_id);
	}
	function get_business_by_campaign($campaign_name)
	{
		return $this->ci->users->get_business_by_campaign_name($campaign_name);
	}

	function get_partner_info($partner_id)
	{
		return $this->ci->users->get_partner_details($partner_id);
	}
	function get_partner_info_by_role_and_ids($role, $user_id, $advertiser_id = null)
	{
		return $this->ci->users->get_partner_info_by_role_and_ids($role, $user_id, $advertiser_id);
	}

	function get_partner_id($user_id)
	{
		return $this->ci->users->get_partner_id_by_user_id($user_id);
	}
	function get_partner_by_business($business_name)
	{
		return $this->ci->users->get_partner_id_by_business_name($business_name);
	}
	function get_biz_name($username)
	{
		return $this->ci->users->get_business_name_by_username($username);
	}
	function get_business_id($username)
	{
		return $this->ci->users->get_business_id_by_username($username);
	}
	function get_business_id_from_name($business_name)
	{
		return $this->ci->users->get_business_id_from_name($business_name);
	}
	function get_business_name_from_id($business_id)
	{
		return $this->ci->users->get_business_name_from_id($business_id);
	}
	function get_role($username)
	{
		return $this->ci->users->get_role_by_username($username);
	}
	function get_role_by_user_id($user_id)
	{
		return $this->ci->users->get_role_by_user_id($user_id);
	}
	function get_sales_users()
	{
		return $this->ci->users->get_all_sales_users();
	}
	function get_business_salesperson_by_id($business_id)
	{
		return $this->ci->users->get_salesperson_by_business_id($business_id);
	}
	function get_business_salesperson($business)
	{
		return $this->ci->users->get_salesperson_by_business_name($business);
	}
	function get_email($username)
	{
		return $this->ci->users->get_email_by_username($username);
	}
	function get_email_by_user_id($user_id)
	{
		return $this->ci->users->get_email_by_user_id($user_id);
	}
	function get_firstname($username)
	{
		return $this->ci->users->get_firstname_by_username($username);
	}
	
	function get_lastname($username)
	{
		return $this->ci->users->get_lastname_by_username($username);
	}
	
	function get_placements_viewable($username)
	{
		return $this->ci->users->get_placements_viewable_by_username($username);
	}
	
	function get_ad_sizes_viewable($user_id)
	{
		return $this->ci->users->get_ad_sizes_viewable_by_id($user_id);	    
	}

	function get_beta_report_engagements_viewable($username)
	{
		return $this->ci->users->get_beta_report_engagements_viewable_by_username($username);
	}
	
	function get_beta($username)
	{
		return $this->ci->users->get_screenshots_viewable_by_username($username);
	}
	
	function get_bgroup($username)
	{
		return $this->ci->users->get_bgroup_by_username($username);
	}
	
	function get_isGroupSuper($username)
	{
		return $this->ci->users->get_isGroupSuper_by_username($username);
	}
	
	function get_isGlobalSuper($username)
	{
		return $this->ci->users->get_isGlobalSuper_by_username($username);
	}
	function get_planner_viewable($user_id)
	{
		return $this->ci->users->get_planner_viewable($user_id);
	}
	function get_campaigns_by_id($business_id)
	{
		return $this->ci->users->get_campaigns_by_business_id($business_id);
	}
	function get_adgroups_by_c_id($c_id)
	{
		return $this->ci->users->get_campaigns_by_campaign_id($c_id);
	}
	function get_campaigns($business_name)
	{
		return $this->ci->users->get_campaigns_by_business_name($business_name);
	}
	function get_campaign_details_by_id($campaign_id)
	{
		return $this->ci->users->get_campaign_details_by_id($campaign_id);
	}
	function get_campaign_details($campaign_name, $business_id)
	{
		return $this->ci->users->get_campaign_details($campaign_name, $business_id);
	}
	function get_campaign_name_from_id($campaign_id)
	{
		return $this->ci->users->get_campaign_name_from_id($campaign_id);
	}
	function insert_advertiser($insert_array)
	{
		return $this->ci->users->insert_advertiser($insert_array);
	}
	function insert_campaign($insert_array)
	{
		return $this->ci->users->insert_campaign($insert_array);
	}
	function insert_adgroup($insert_array)
	{
		return $this->ci->users->insert_adgroup($insert_array);
	}
	function get_partner_businesses($partner_id)
	{
		return $this->ci->users->get_businesses_by_partner($partner_id);
	}
	function get_partner_by_unique($partner_unique_id)
	{
		return $this->ci->users->get_partner_id_by_unique_id($partner_unique_id);
	}
	function get_partner_by_cname($partner_cname)
	{
		return $this->ci->users->get_partner_id_by_cname($partner_cname);
	}
	function get_businesses($search_term = '', $start = 0, $limit = 0)
	{
		return $this->ci->users->get_all_business_names($search_term, $start, $limit);
	}
	function get_sales_businesses($id)
	{
		return $this->ci->users->get_businesses_by_salesperson($id);
	}
	function insert_missing_domains($array)
	{
		return $this->ci->users->insert_demos_from_sites($array);
	}
	function get_partner_id_by_advertiser_id($id)
	{
		return $this->ci->users->get_partner_id_by_business_id($id);
	}
	function get_advertisers_by_user_id_for_admin_and_ops($user_id)
	{
		return $this->ci->users->get_advertisers_by_user_id_for_admin_and_ops($user_id);
	}
	function get_advertisers_by_user_id_and_is_super($user_id, $is_super)
	{
		return $this->ci->users->get_advertisers_by_user_id_and_is_super($user_id, $is_super);
	}

	/**
	 * Create new user on the site and return some data about it:
	 * user_id, username, password, email, new_email_key (if any).
	 *
	 * @param	string
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @return	array
	 */
	function create_user($username, 
						 $email, 
						 $password, 
						 $role, 
						 $globalsuper, 
						 $bgroup, 
						 $groupsuper, 
						 $advertiser_id, 
						 $firstname, 
						 $lastname, 
						 $partner, 
						 $planner_viewable,
						 $placements_viewable, 
						 $screenshots_viewable,
						 $engagements_viewable,
						 $ad_sizes_viewable,
						 $email_activation,
						 $additional_fields = NULL) //create_user gets variables from auth->register
	{
		if ((strlen($username) > 0) AND !$this->ci->users->is_username_available($username)) {
			$this->error = array('username' => 'auth_username_in_use');

		} elseif (!$this->ci->users->is_email_available($email)) {
			$this->error = array('email' => 'auth_email_in_use');

		} else {
			// Hash password using phpass
			$hasher = new PasswordHash(
				 $this->ci->config->item('phpass_hash_strength', 'tank_auth'),
				 $this->ci->config->item('phpass_hash_portable', 'tank_auth'));
			$hashed_password = $hasher->HashPassword($password);
			
			if ($role == 'ADMIN' || $role == 'CREATIVE' || $role == 'OPS') // #1193. for admin, creative and ops, hardcode partnerid to 1
				$partner = 1;
			elseif ($role == 'BUSINESS') // for advertiser, hardcode partner id to null. removing typecase to int from below to support nullable column
				$partner = NULL; // if the role is sales, let user select a partner id from the dropdown

			$data = array(
				'username'		=> $username,
				'password'		=> $hashed_password,
				'email'			=> $email,
				'last_ip'		=> $this->ci->input->ip_address(),
				'role'			=> $role,
				'isGlobalSuper' => $globalsuper,
				'bgroup'		=> $bgroup,
				'isGroupSuper'	=> $groupsuper,
				'advertiser_id'	=> $advertiser_id,
				'firstname'		=> $firstname,
				'lastname'		=> $lastname,
				'partner_id' 	=> $partner,
				'planner_viewable'  => $planner_viewable,
				'placements_viewable'=>$placements_viewable,
				'screenshots_viewable'=>$screenshots_viewable,
				'beta_report_engagements_viewable'=>$engagements_viewable,
				'ad_sizes_viewable'=>$ad_sizes_viewable
				);

			if ($email_activation) {
				$data['new_email_key'] = md5(rand().microtime());
			}

			if (gettype($additional_fields) == "array")
			{
				$data = array_merge($data, $additional_fields);
			}

			if (!is_null($res = $this->ci->users->create_user($data, !$email_activation))) {  //call to users model->create_user with the data array
				$data['user_id'] = $res['user_id'];
				$data['password'] = $password;
				unset($data['last_ip']);
				return $data;
			}
		}
		return NULL;
	}

	/**
	 * Check if username available for registering.
	 * Can be called for instant form validation.
	 *
	 * @param	string
	 * @return	bool
	 */
	function is_username_available($username)
	{
		return ((strlen($username) > 0) AND $this->ci->users->is_username_available($username));
	}

	/**
	 * Check if email available for registering.
	 * Can be called for instant form validation.
	 *
	 * @param	string
	 * @return	bool
	 */
	function is_email_available($email)
	{
		return ((strlen($email) > 0) AND $this->ci->users->is_email_available($email));
	}

	/**
	 * Change email for activation and return some data about user:
	 * user_id, username, email, new_email_key.
	 * Can be called for not activated users only.
	 *
	 * @param	string
	 * @return	array
	 */
	function change_email($email)
	{
		$user_id = $this->ci->session->userdata('user_id');

		if (!is_null($user = $this->ci->users->get_user_by_id($user_id, FALSE))) {

			$data = array(
				'user_id'	=> $user_id,
				'username'	=> $user->username,
				'email'		=> $email,
				);
			if (strtolower($user->email) == strtolower($email)) {		// leave activation key as is
				$data['new_email_key'] = $user->new_email_key;
				return $data;

			} elseif ($this->ci->users->is_email_available($email)) {
				$data['new_email_key'] = md5(rand().microtime());
				$this->ci->users->set_new_email($user_id, $email, $data['new_email_key'], FALSE);
				return $data;

			} else {
				$this->error = array('email' => 'auth_email_in_use');
			}
		}
		return NULL;
	}

	/**
	 * Activate user using given key
	 *
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @return	bool
	 */
	//function deactivate($v){
	function activate_user($user_id, $activation_key, $activate_by_email = TRUE)
	{
		$this->ci->users->purge_na($this->ci->config->item('email_activation_expire', 'tank_auth'));

		if ((strlen($user_id) > 0) AND (strlen($activation_key) > 0)) {
			return $this->ci->users->activate_user($user_id, $activation_key, $activate_by_email);
		}
		return FALSE;
	}

	/**
	 * Set new password key for user and return some data about user:
	 * user_id, username, email, new_pass_key.
	 * The password key can be used to verify user when resetting his/her password.
	 *
	 * @param	string
	 * @return	array
	 */
	function forgot_password($login)
	{
		if (strlen($login) > 0) {
			if (!is_null($user = $this->ci->users->get_user_by_login($login))) {

				$data = array(
					'user_id'		=> $user->id,
					'username'		=> $user->username,
					'email'			=> $user->email,
					'role'          => $user->role,
					'firstname'     => $user->firstname,
					'lastname'      => $user->lastname,
					'new_pass_key'	=> md5(rand().microtime()),
					);

				$this->ci->users->set_password_key($user->id, $data['new_pass_key']);
				return $data;

			} else {
				$this->error = array('login' => 'auth_incorrect_email_or_username');
			}
		}
		return NULL;
	}

	/**
	 * Check if given password key is valid and user is authenticated.
	 *
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	function can_reset_password($user_id, $new_pass_key)
	{
		if ((strlen($user_id) > 0) AND (strlen($new_pass_key) > 0)) {
			return $this->ci->users->can_reset_password(
				$user_id,
				$new_pass_key,
				$this->ci->config->item('forgot_password_expire', 'tank_auth'));
		}
		return FALSE;
	}

	/**
	 * Replace user password (forgotten) with a new one (set by user)
	 * and return some data about it: user_id, username, new_password, email.
	 *
	 * @param	string
	 * @param	string
         * @param	string
         * @param	int
	 * @return	bool
	 */
	function reset_password($user_id, $new_pass_key, $new_password, $is_registration = 0)
	{
		if ((strlen($user_id) > 0) AND (strlen($new_pass_key) > 0) AND (strlen($new_password) > 0)) {

			if (!is_null($user = $this->ci->users->get_user_by_id($user_id, TRUE))) {

				// Hash password using phpass
				$hasher = new PasswordHash(
					$this->ci->config->item('phpass_hash_strength', 'tank_auth'),
					$this->ci->config->item('phpass_hash_portable', 'tank_auth'));
				$hashed_password = $hasher->HashPassword($new_password);

				if ($this->ci->users->reset_password(
						$user_id,
						$hashed_password,
						$new_pass_key,
						$this->ci->config->item('forgot_password_expire', 'tank_auth'),
                                                $is_registration))
                                {	// success

					// Clear all user's autologins
					$this->ci->load->model('tank_auth/user_autologin');
					$this->ci->user_autologin->clear($user->id);

					return array(
						'user_id'		=> $user_id,
						'username'		=> $user->username,
						'email'			=> $user->email,
						'firstname'     => $user->firstname,
						'lastname'      => $user->lastname,
						'role'			=> $user->role,
						'new_password'	=> $new_password,
						);
				}
			}
		}
		return NULL;
	}

	/**
	 * Change user password (only when user is logged in)
	 *
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	function change_password($old_pass, $new_pass)
	{
		$user_id = $this->ci->session->userdata('user_id');

		if (!is_null($user = $this->ci->users->get_user_by_id($user_id, TRUE))) {

			// Check if old password correct
			$hasher = new PasswordHash(
				$this->ci->config->item('phpass_hash_strength', 'tank_auth'),
				$this->ci->config->item('phpass_hash_portable', 'tank_auth'));
			if ($hasher->CheckPassword($old_pass, $user->password)) {			// success

				// Hash new password using phpass
				$hashed_password = $hasher->HashPassword($new_pass);

				// Replace old password with new one
				$this->ci->users->change_password($user_id, $hashed_password);
				return TRUE;

			} else {															// fail
				$this->error = array('old_password' => 'auth_incorrect_password');
			}
		}
		return FALSE;
	}

	/**
	 * Change user email (only when user is logged in) and return some data about user:
	 * user_id, username, new_email, new_email_key.
	 * The new email cannot be used for login or notification before it is activated.
	 *
	 * @param	string
	 * @param	string
	 * @return	array
	 */
	function set_new_email($new_email, $password)
	{
		$user_id = $this->ci->session->userdata('user_id');

		if (!is_null($user = $this->ci->users->get_user_by_id($user_id, TRUE))) {

			// Check if password correct
			$hasher = new PasswordHash(
				$this->ci->config->item('phpass_hash_strength', 'tank_auth'),
				$this->ci->config->item('phpass_hash_portable', 'tank_auth'));
			if ($hasher->CheckPassword($password, $user->password)) {			// success

				$data = array(
					'user_id'	=> $user_id,
					'username'	=> $user->username,
					'new_email'	=> $new_email,
					);

				if ($user->email == $new_email) {
					$this->error = array('email' => 'auth_current_email');

				} elseif ($user->new_email == $new_email) {		// leave email key as is
					$data['new_email_key'] = $user->new_email_key;
					return $data;

				} elseif ($this->ci->users->is_email_available($new_email)) {
					$data['new_email_key'] = md5(rand().microtime());
					$this->ci->users->set_new_email($user_id, $new_email, $data['new_email_key'], TRUE);
					return $data;

				} else {
					$this->error = array('email' => 'auth_email_in_use');
				}
			} else {															// fail
				$this->error = array('password' => 'auth_incorrect_password');
			}
		}
		return NULL;
	}

	/**
	 * Activate new email, if email activation key is valid.
	 *
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	function activate_new_email($user_id, $new_email_key)
	{
		if ((strlen($user_id) > 0) AND (strlen($new_email_key) > 0)) {
			return $this->ci->users->activate_new_email(
				$user_id,
				$new_email_key);
		}
		return FALSE;
	}

	/**
	 * Delete user from the site (only when user is logged in)
	 *
	 * @param	string
	 * @return	bool
	 */
	function delete_user($password)
	{
		$user_id = $this->ci->session->userdata('user_id');

		if (!is_null($user = $this->ci->users->get_user_by_id($user_id, TRUE))) {

			// Check if password correct
			$hasher = new PasswordHash(
				$this->ci->config->item('phpass_hash_strength', 'tank_auth'),
				$this->ci->config->item('phpass_hash_portable', 'tank_auth'));
			if ($hasher->CheckPassword($password, $user->password)) {			// success

				$this->ci->users->delete_user($user_id);
				$this->logout();
				return TRUE;

			} else {															// fail
				$this->error = array('password' => 'auth_incorrect_password');
			}
		}
		return FALSE;
	}

	/**
	 * Get error message.
	 * Can be invoked after any failed operation such as login or register.
	 *
	 * @return	string
	 */
	function get_error_message()
	{
		return $this->error;
	}

	/**
	 * Save data for user's autologin
	 *
	 * @param	int
	 * @return	bool
	 */
	private function create_autologin($user_id)
	{
		$this->ci->load->helper('cookie');
		$key = substr(md5(uniqid(rand().get_cookie($this->ci->config->item('sess_cookie_name')))), 0, 16);

		$this->ci->load->model('tank_auth/user_autologin');
		$this->ci->user_autologin->purge($user_id);

		if ($this->ci->user_autologin->set($user_id, md5($key))) {
			set_cookie(array(
						   'name' 		=> $this->ci->config->item('autologin_cookie_name', 'tank_auth'),
						   'value'		=> serialize(array('user_id' => $user_id, 'key' => $key)),
						   'expire'	=> $this->ci->config->item('autologin_cookie_life', 'tank_auth'),
						   ));
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Clear user's autologin data
	 *
	 * @return	void
	 */
	private function delete_autologin()
	{
		$this->ci->load->helper('cookie');
		if ($cookie = get_cookie($this->ci->config->item('autologin_cookie_name', 'tank_auth'), TRUE)) {

			$data = unserialize($cookie);

			$this->ci->load->model('tank_auth/user_autologin');
			$this->ci->user_autologin->delete($data['user_id'], md5($data['key']));

			delete_cookie($this->ci->config->item('autologin_cookie_name', 'tank_auth'));
		}
	}

	/**
	 * Login user automatically if he/she provides correct autologin verification
	 *
	 * @return	void
	 */
	private function autologin()
	{
		if (!$this->is_logged_in() AND !$this->is_logged_in(FALSE)) {			// not logged in (as any user)

			$this->ci->load->helper('cookie');
			if ($cookie = get_cookie($this->ci->config->item('autologin_cookie_name', 'tank_auth'), TRUE)) {

				$data = unserialize($cookie);

				if (isset($data['key']) AND isset($data['user_id'])) {

					$this->ci->load->model('tank_auth/user_autologin');
					if (!is_null($user = $this->ci->user_autologin->get($data['user_id'], md5($data['key'])))) {

						// Login user
						$this->ci->session->set_userdata(array(
															 'user_id'	=> $user->id,
															 'username'	=> $user->username,
															 'status'	=> STATUS_ACTIVATED,
															 ));

						// Renew users cookie to prevent it from expiring
						set_cookie(array(
									   'name' 		=> $this->ci->config->item('autologin_cookie_name', 'tank_auth'),
									   'value'		=> $cookie,
									   'expire'	=> $this->ci->config->item('autologin_cookie_life', 'tank_auth'),
									   ));

						$this->ci->users->update_login_info(
							$user->id,
							$this->ci->config->item('login_record_ip', 'tank_auth'),
							$this->ci->config->item('login_record_time', 'tank_auth'));
						return TRUE;
					}
				}
			}
		}
		return FALSE;
	}

	/**
	 * Check if login attempts exceeded max login attempts (specified in config)
	 *
	 * @param	string
	 * @return	bool
	 */
	function is_max_login_attempts_exceeded($login)
	{
		if ($this->ci->config->item('login_count_attempts', 'tank_auth')) {
			$this->ci->load->model('tank_auth/login_attempts');
			return $this->ci->login_attempts->get_attempts_num($this->ci->input->ip_address(), $login)
				>= $this->ci->config->item('login_max_attempts', 'tank_auth');
		}
		return FALSE;
	}

	/**
	 * Increase number of attempts for given IP-address and login
	 * (if attempts to login is being counted)
	 *
	 * @param	string
	 * @return	void
	 */
	private function increase_login_attempt($login)
	{
		if ($this->ci->config->item('login_count_attempts', 'tank_auth')) {
			if (!$this->is_max_login_attempts_exceeded($login)) {
				$this->ci->load->model('tank_auth/login_attempts');
				$this->ci->login_attempts->increase_attempt($this->ci->input->ip_address(), $login);
			}
		}
	}

	/**
	 * Clear all attempt records for given IP-address and login
	 * (if attempts to login is being counted)
	 *
	 * @param	string
	 * @return	void
	 */
	private function clear_login_attempts($login)
	{
		if ($this->ci->config->item('login_count_attempts', 'tank_auth')) {
			$this->ci->load->model('tank_auth/login_attempts');
			$this->ci->login_attempts->clear_attempts(
				$this->ci->input->ip_address(),
				$login,
				$this->ci->config->item('login_attempt_expire', 'tank_auth'));
		}
	}
	
	/* ----NOT AN ORIGINAL FUNCTION----
	 * @param         int - length of random password
	 * @return     string - generated password
	 * 
	 */
	function make_random_password($length){
		$library = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";	

		$size = strlen( $library );
		$str = "";
		for( $i = 0; $i < $length; $i++ ) {
			$str .= $library[ rand( 0, $size - 1 ) ];
		}

		return $str;
      
	}

	function if_partner_return_details()
	{
		$host = $_SERVER['HTTP_HOST'];
		$url_pieces = explode('.', $host);
		$partner_details = $this->ci->users->get_partner_details_by_cname($url_pieces[0]);
		return $partner_details;
	}

	public function get_domain_without_cname()
	{
		return g_second_level_domain;
	}

	function redirect_to_partner_unique_id($unique_id)
	{
		$partner_details = $this->ci->users->get_partner_details_by_unique_id($unique_id);
		$redirect_url = 'https://'.$_SERVER['HTTP_HOST'];
		if($partner_details) //if the partner exists
		{
			if(ENVIRONMENT == 'localhost-development')
			{
				$redirect_url = 'https://'.$_SERVER['HTTP_HOST'];
			}
			else
			{
				$domain = '.'.$this->get_domain_without_cname();
				$cname = $partner_details['cname'];
				if(empty($cname)) //we have no brandcdn subdomain for the partner.
				{
					$cname = 'vantagelocal';
				}
				$redirect_url = 'https://'.$cname.$domain;
			}

		}
		else
		{
			$redirect_url = 'https://'.$_SERVER['HTTP_HOST'];
		}
		redirect($redirect_url); //redirect to the partner's brandcdn subdomain.
	}

	function get_sales_id_and_name_by_partner_id($p_id)
	{
		return $this->ci->users->get_sales_id_and_name_by_partner_id($p_id);
	}
	function get_partner_info_by_sub_domain()
	{
		$this_domain = $this->get_domain_without_cname();
		$url_fragments = explode(".".$this_domain, BASE_URL);
		if(count($url_fragments) > 1)
		{
			$partner_cname = $url_fragments[0];
			$partner_info = $this->ci->users->get_partner_details_by_cname($partner_cname);
			if($partner_info AND $partner_info > 1)
			{
				return $partner_info;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
	function get_super_sales_by_partner_id($p_id)
	{
		return $this->ci->users->get_super_sales_by_partner_id($p_id);
	}
	function get_advertisers_by_sales_person_partner_hierarchy($user_id, $is_super, $search_term=null,$mysql_page_number=0, $page_limit=0)
	{
		return $this->ci->users->get_advertisers_by_sales_person_partner_hierarchy($user_id, $is_super, $search_term, $mysql_page_number, $page_limit);
	}
	function legacy_get_advertisers_by_sales_person_partner_hierarchy($search_term=null,$mysql_page_number=0, $page_limit=0, $user_id, $is_super)
	{
		return $this->ci->users->legacy_get_advertisers_by_sales_person_partner_hierarchy($search_term, $mysql_page_number, $page_limit, $user_id, $is_super);
	}	
	function get_advertisers_for_client_or_agency($user_id, $search_term=null,$mysql_page_number=0, $page_limit=0)
	{
		return $this->ci->users->get_advertisers_for_client_or_agency($user_id, $search_term, $mysql_page_number, $page_limit);
	}
	function get_sales_users_by_partner_hierarchy($user_id, $is_super)
	{
		return $this->ci->users->get_sales_users_by_partner_hierarchy($user_id, $is_super);
	}
	function get_parent_and_sales_owner_emails_by_partner($partner_id)
	{
		return $this->ci->users->get_parent_and_sales_owner_emails_by_partner($partner_id);
	}
	function get_partner_hierarchy_by_sales_person($user_id, $is_super)
	{
		return $this->ci->users->get_partner_hierarchy_by_sales_person($user_id, $is_super);
	}
	function get_sales_people_by_partners_allowed_by_user($user_id, $is_super, $selected_partner = false)
	{
		return $this->ci->users->get_sales_people_by_partners_allowed_by_user($user_id, $is_super, $selected_partner);
	}
	function get_is_partner_owner_by_user_id($user_id)
	{
		return $this->ci->users->is_partner_owner($user_id);
	}
        
        function is_valid_partner_id($partner_id)
	{
		return $this->ci->users->is_valid_partner_id($partner_id);
	}
        function get_all_partners_except_self($current_partner_id = null)
	{
		return $this->ci->users->get_all_partners_except_self($current_partner_id);
	}
        function get_all_descendant_partner_ids($partner_id)
	{
		return $this->ci->users->get_all_descendant_partner_ids($partner_id);
	}
        function save_partner_owner($user_id, $partner_id)
	{
		return $this->ci->users->save_partner_owner($user_id, $partner_id);
	}	
	function get_demo_users_by_partner_id($p_id)
	{
		return $this->ci->users->get_demo_users_by_partner_id($p_id);
	}
}

/* End of file Tank_auth.php */
/* Location: ./application/libraries/Tank_auth.php */
