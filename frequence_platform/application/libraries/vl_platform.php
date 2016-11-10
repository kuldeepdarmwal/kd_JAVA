<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

// --------------------------------------------------------------------
// ------------------------ Begin Overview ----------------------------
// --------------------------------------------------------------------
//
//	Here are some instructions on how to use the VL Platform.
//
//	Currently the main piece of the VL Platform is the UI Core which is a common way of creating the navigation among the various features and functionality we use internally and give to clients through secure.vantagelocal.com (or the whitelabeled version)
//
//	first some terminology:
//		=	feature
//			-	a broad category of functionality such as Prop Gen, Campaigns, MPQ.  Represents a group of web pages.  Can correspond to a CI controller, but may use multiple
//		=	subfeature
//			-	specific pieces of functionality that correspond to a particular feature.  Is an individual web page.  Such as Prop Gen => Option Engine, Prop Gen => Display Proposal, Campaigns => Campaign Data
//		=	master nav bar
//			-	the top most list of buttons with company logo and user name and setting
//		=	feature button
//			-	a button on the master nav bar that links to the main url for a set of feature pages
//		=	feature nav bar
//			-	the secondary navbar containing links to the various web pages corresponding to the subfeatures for the selected feature on the master nav bar
//		=	subfeature button
//			-	a button on the feature nav bar which links to a web page for a subfeature for the selected feature on the master nav bar
//
//	vl_platform files:
//		=	application/libraries/vl_platform.php
//			-	used to compose and show the web_page for the feature/subfeature
//			-	creates the visuals for the master nav bar, feature nav bar, and footer
//			-	only shows the features the user is allowed to see
//			-	this file contains a bunch of comments with further instructions on how to use it
//		=	application/models/vl_platform_model.php
//			-	get access permissions for user
//			-	get data for master nav bar buttons
//			-	this file contains a bunch of sql queries for testing/debugging the user permissions for the features
//		=	application/views/vl_platform/ui_core/common_master_header.php
//			-	shows the master nav bar
//			-	uses data populated from database
//			-	composes the <head></head> section of the html
//			-	this file contains a bunch of comments detailing how to use the views/ui_core/* files
//		=	application/views/vl_platform/ui_core/common_feature_header.php
//			-	shows the feature nav bar
//			-	uses data populated by feature/subfeature controller in
//		=	application/views/vl_platform/ui_core/common_footer.php
//			-	shows the page footer
//			-	puts javascript into the web page
//
//	vl_platform database tables
//		=	pl_permission_groups
//			-	specifies groups of users.  Which are then related to specific features for determining access permissions for a user
//		=	pl_features
//			-	the data for the feature buttons
//		=	pl_feature_permissions
//			-	determines user's access to each feature based upon the permission groups they belong to
//
//	pass paths for your feature and subfeature CI view files to the vl_platform->show_views() function to show your webpage
//		=	$sub_feature_body
//			-	either a string path to a view which is the body of the feature/subfeature
//			-	or an array of string paths used to compose the body of the feature/subfeature
//		=	$feature_html_header
//			-	Used for data that is used across the subfeatures, so you don't duplicate code in each $subfeature_html_header file.
//			-	data that goes in the <head></head> html section
//		=	$subfeature_html_header
//			-	Used for data unique to the subfeature.
//			-	data that goes in the <head></head> html section.
//		=	$feature_js
//			-	Used for javascript that is used across the subfeatures, so you don't duplicate code in each $subfeature_js file.
//			-	javascript <script src="file.js"> tags and put custom javascript in <script type="text/javascript">your code here</script>
//		=	$subfeature_js
//			-	Used for data unique to the subfeature.
//			-	javascript <script src="file.js"> tags and put custom javascript in <script type="text/javascript">your code here</script>
//
//	For an example of how to use this see the mpq_v2 files or campaigns_main files.
//
//
// --------------------------------------------------------------------
// ------------------------ End Overview ------------------------------
// --------------------------------------------------------------------



//	library of functions used for the ui_core structure of the VL Platform
//	See mpq_v2 for an example of how to use this
//
//  main functions:
//		has_permission_to_view_page_otherwise_redirect()
//			returns false if role is not allowed to visit page and sets CI redirect
//			returns true if role can view page
//		show_views()
//			fuction for showing all the specified views, plus the UI Core views
//			see comments for function definition on how to use
//
//	other functions for situations requiring more customization.
//		get_master_header_data()
//			populates the $data array for the master header view
//		get_access_permission_redirect()
//			used by has_permission_to_view_page_otherwise_redirect() but is public for custom behavior
//			returns string with redirect location if role isn't allowed to visit page
//			returns empty string if role has permission
//
//	how to add a new feature (5 steps in this file)
//		1) add button for feature on master nav bar
//			add record to database table `pl_features`
//				`id`:  is auto populated
//				`html_id`:
//				`name`:  is the text on the button
//				`url`:  the url link followed upon clicking button
//				`icon`:  the icon on the button
//				`order`:  determins placement of button relative to other feature buttons. left to right.
//
//		2) add permissions detemining which users have access to the new feature
//			Debugging: There are sql queries in vl_platform_model.php for checking what features users have access to
//			check/add records in database table `pl_permission_groups`
//				this table represents a group of users based upon values in ther `users` table
//				1st) see if a record already exists for the permission group you want to give/restrict access for your feature
//				2nd) add any permission groups that are missing.
//					A non-NULL value, restricts the set of users in the permission_group.
//					A NULL field doesn't add any filtering based on that field
//					`id`:  auto populated
//					`role`:  corresponds to 'role' field in `users` table
//					`is_group_super`:  corresponds to 'isGroupSuper' field in `users` table
//					`is_global_super`:  corresponds to 'isGlobalSuper' field in `users` table
//					`partner_id`:  corresponds to 'partner_id' field in `users` table
//						EXCEPT if the user's `role` is BUSINESS, in which case it uses the sales_person's partner_id
//								derived from: user => business => sales_person => `partner_id`
//					`user_id`:  corresponds to 'id' field in `users` table
//			relate your record in `pl_features` table to the relevant records in `pl_permission_groups` using table `pl_feature_permissions`
//				`permission_group_id`: corresponds to `id` field in `pl_permission_groups` table
//				`feature_id`: corresponds to `id` field in `pl_features` table
//				`has_access`: determines whether that feature is accessible to that group
//			Note: the most specific permission for a specific feature that is matched to a user takes precedence
//			      in the order from most specific to most general:  user_id, partner_id, is_global_super, is_group_super, role
//					1st) user #70 can access prop_gen
//					2nd) sales with partner_id = 3 cannot access prop_gen
//					3rd) all sales can access prop_gen
//
//			examples:
//				`pl_features` table
//						`id`	`html_id`	`name`	`url`             `icon`                `order`
//						1   	mpq      	MPQ   	/mpq/hello_world	icon-globe icon-white	0
//						2   	tickets  	Tickets /tickets        	icon-globe icon-white	3
//						3   	prop_gen 	Props 	/prop_gen/list   	icon-globe icon-white	2
//
//				`pl_permission_groups` table
//					`id`	`role`	`is_group_super`	`is_global_super`	`partner_id`	`user_id`
//					 1   	admin		NULL	          	NULL	           	NULL        	NULL     	: means - "all admins"
//					 2  	admin		NULL	          	NULL	           	3           	NULL     	: means - "all admins with partner id = 3"
//					 3  	NULL		NULL	          	NULL	           	NULL        	70       	: means - "user with id = 70"
//
//				`pl_feature_permissions` table
//					`permission_group_id`	`feature_id`	`has_access`
//						1                    	1           	1         	: means - all admins can access mpq
//						3                    	2           	1         	: means - user #70 can access tickets
//						2                    	3           	1         	: means - admins with partner_id = 3 can access prop_gen
//						3                    	3           	1         	: means - user #70 can access prop_gen
//						1                    	2           	1         	: means - all admins can access tickets
//						3                    	1           	0         	: means - user #70 can NOT access mpq
//
//				so: user #70 (who is an admin) sees - prop_gen and tickets
//				so: admins with partner_id = 3 see - mpq, prop_gen, and tickets
//				so: all other admins see - mpq and tickets
//
//		3) redirect url when user is not logged in (i.e. redirected to login and then redirected back after login)
//			pass the feature id to function has_permission_to_view_page_otherwise_redirect()
//			or to function get_access_permission_redirect() if you are using that function instead
//
//		4) update starting page if appropriate
//			verify function get_start_page_by_role() and change as necessary
//
//		5) add validation for feature_id
//			in function is_valid_feature_button_id() in variable $feature_id_map add the new feature's id
//
//		6) in your controller
//			a) load this library: $this->load->library('vl_platform');
//			a) check permissions: $this->vl_platform->has_permission_to_view_page_otherwise_redirect()
//			b) show the ui_core+feature+subfeature views: $this->vl_platform->show_views()
//
//
//	comment last edited (3/28/2013 by Scott Huber)
class vl_platform
{

	public function __construct()
	{
		$this->ci =& get_instance();

		$this->ci->load->helper('url');
		$this->ci->load->library('session');
		$this->ci->load->library('tank_auth');
		$this->ci->load->model('vl_platform_model');
		$this->ci->load->model('spectrum_account_model');
	}

	private function is_valid_feature_button_id($feature_id)
	{
		$is_valid = false;
		$feature_id_map = array(
			'mpq'                => true,
			'prop_gen'           => true,
			'ad_ops'             => true,
			'campaigns_main'     => true,
			'gallery'            => true,
			'banner_intake'      => true,
			'custom_gallery'     => true,
			'register'           => true,
			'all_mpqs'           => true,
			'reports'            => true,
			'third_party_linker' => true,
			'multi_geos'         => true,
			'report_l'           => true,
			'user_editor'        => true,
			'access_manager'     => true,
			'proposals'          => true,
			'ad_machina'         => true,
			'ad_machina_manager' => true,
			'io'                 => true
		);

		if(array_key_exists($feature_id, $feature_id_map))
		{
			//$is_found = $feature_id_map[$feature_id];
			$is_valid = true;
		}
		else
		{
			$is_valid = false;
		}

		return $is_valid;
	}

	private function get_start_page_by_role($role)
	{
		$start_page = '';
		switch($role)
		{
			case 'admin':
				$start_page = '/campaign_setup';
				break;
			case 'agency':
				$start_page = '/report';
				break;
			case 'business':
				$start_page = '/report';
				break;
			case 'client':
				$start_page = '/report';
				break;
			case 'creative':
				$start_page = '/report';
				break;
			case 'ops':
				$start_page = '/campaign_setup';
				break;
			case 'sales':
				$start_page = '/report';
				break;
			default:
				// public user?
				$start_page = '/report';
				break;
		}

		return $start_page;
	}

	// check feature access of user
	// return empty string if user has permission to access feature
	// return start page url if user does NOT have permission to access feature
	//
	// $role parameter is no longer used, kept it for backwards compatibility with previous version (3/25/2013)
	private function get_feature_permission_redirect($feature_html_id, $role = '')
	{
		$redirect = '';

		$user_id = $this->ci->tank_auth->get_user_id();
		$is_accessible = $this->ci->vl_platform_model->is_feature_accessible($user_id, $feature_html_id);

		if(!$is_accessible)
		{
			$redirect = $this->get_start_page_by_role($role);
		}

		return $redirect;
	}

	//	returns true if user has priveledges to visit feature_id page
	//	returns false if user is not logged in and redirects to $referer_url after user logs in
	//	returns false if user does not have priveledges to view and redirects to default page for user
	public function has_permission_to_view_page_otherwise_redirect($feature_html_id, $referer_url)
	{
		$has_permission = true;

		$redirect = $this->get_access_permission_redirect($feature_html_id);
		if($redirect != '')
		{
			$has_permission = false;
			if($redirect == 'login')
			{
				$this->ci->session->set_userdata('referer', $referer_url);
			}

			redirect(site_url($redirect));
		}

		return $has_permission;
	}

	/**
	 * Returns array of html ids as keys/values for specified user id or
	 * uses the id of the current user
	 * @param int|null $user_id
	 * @return array
	 */
	public function get_list_of_user_permission_html_ids_for_user($user_id = null)
	{
		if(!$user_id)
		{
			$user_id = $this->ci->tank_auth->get_user_id();
		}
		$permissions_array = $this->ci->vl_platform_model->get_accessable_features((int)$user_id);
		if(!empty($permissions_array))
		{
			return array_combine(array_column($permissions_array, 'html_id'), array_column($permissions_array, 'html_id'));
		}
		return array();
	}

	// check permission and login of user
	// return redirect location or empty string if access allowed for user
	// set referer if relevant (user is not logged in)
	public function get_access_permission_redirect($feature_html_id) //, $subfeature_id, $user_id)
	{
		$redirect = '';

		if(!$this->ci->tank_auth->is_logged_in())
		{
			$redirect = 'login';
		}
		else
		{
			$username = $this->ci->tank_auth->get_username();
			$role = $this->ci->tank_auth->get_role($username);
			$redirect = $this->get_feature_permission_redirect($feature_html_id, $role);
		}

		return $redirect;
	}

	// get the data for the feature buttons on the master nav bar
	private function get_master_nav_buttons_data(Array &$buttons_data)
	{
		$user_id = $this->ci->tank_auth->get_user_id();
		if($user_id != 0)
		{
			$response = $this->ci->vl_platform_model->get_features_for_user($user_id);
			if($response)
			{
				$features_data = $response->result();
				foreach($features_data AS $feature_data)
				{
					if ($feature_data->in_header == '1')
					{
						$button_data = array();
						$button_data['button_id'] = $feature_data->html_id;
						$button_data['link_url'] = $feature_data->url;
						$button_data['button_text'] = $feature_data->name;
						$button_data['icon_class'] = $feature_data->icon;

						$buttons_data[] = $button_data;
					}

				}
			}
		}
	}

	public function get_master_header_data(Array &$data, $active_feature_id)
	{
		$master_header_data = array();

		$user_id = $this->ci->tank_auth->get_user_id();
		$is_logged_in = false;

		$master_header_data['partner_mpq_can_submit_proposal'] = true;
		$master_header_data['partner_mpq_can_see_rate_card'] = false;
		$master_header_data['can_register'] = FALSE;
		$master_header_data['user_editor'] = FALSE;
		$master_header_data['third_party_linker'] = FALSE;
		$master_header_data['access_manager'] = FALSE;
		$master_header_data['ad_machina'] = FALSE;
		$master_header_data['ad_machina_manager'] = FALSE;

		if($this->ci->tank_auth->is_logged_in())
		{
			$is_logged_in = true;

			$user_id = $this->ci->tank_auth->get_user_id();
			$username = $this->ci->tank_auth->get_username();
			$user = $this->ci->tank_auth->get_user_by_id($user_id);

			$partner_info = $this->ci->tank_auth->get_partner_info_by_role_and_ids($user->role, $user_id, $user->advertiser_id);
			$master_header_data['is_partner'] = ($partner_info['id'] > 1) ? true : false;
			$master_header_data['partner_header_image'] = ($partner_info['header_img'] == NULL) ? false : $partner_info['header_img'];
			$master_header_data['partner_name'] = $partner_info['cname'];

			$master_header_data['can_register'] = ($this->get_access_permission_redirect('register') == '' ? TRUE : FALSE);
			$master_header_data['user_editor'] = ($this->get_access_permission_redirect('user_editor') == '' ? TRUE : FALSE);
			$master_header_data['third_party_linker'] = ($this->get_access_permission_redirect('third_party_linker') == '' ? TRUE : FALSE);
                        $master_header_data['partners'] = ($this->get_access_permission_redirect('partners') == '' ? TRUE : FALSE);
			$master_header_data['edit_advertisers'] = ($this->get_access_permission_redirect('edit_advertisers') == '' ? TRUE : FALSE);
			$master_header_data['access_manager'] = ($this->get_access_permission_redirect('access_manager') == '' ? TRUE : FALSE) &&
				($this->get_access_manager_permission($user_id));
			$master_header_data['ad_machina'] = ($this->get_access_permission_redirect('ad_machina') == '' ? TRUE : FALSE);
			$master_header_data['ad_machina_manager'] = ($this->get_access_permission_redirect('ad_machina_manager') == '' ? TRUE : FALSE);
			$master_header_data['can_create_helpdesk_ticket'] = $this->get_access_permission_redirect('create_helpdesk_ticket') === '';

			$master_header_data['partner_mpq_can_see_rate_card'] = ($partner_info['id'] == 3 OR $partner_info['id'] == 11 OR $partner_info['id'] == 13 OR $partner_info['id'] == 14) ? false : true;
			//$master_header_data['partner_cname'] = ($partner_info['cname'] == NULL) ? false : $partner_info['cname'];
			$master_header_data['firstname'] = $this->ci->tank_auth->get_firstname($username);
			$master_header_data['lastname'] = $this->ci->tank_auth->get_lastname($username);
			$master_header_data['user_id'] = $user_id;
			$data['user_profile_data'] = $this->get_user_profile_data($user_id);
		}
		else
		{
			$is_logged_in = false;

			$master_header_data['is_partner'] = false;
			$master_header_data['firstname'] = '';
			$master_header_data['lastname'] = '';
			$master_header_data['user_id'] = 0;
			$data['user_profile_data'] = array();
		}

		$partner_cname_response = $this->ci->tank_auth->get_partner_info_by_sub_domain();
		if($partner_cname_response)
		{
			$master_header_data['is_partner'] = $partner_cname_response['id'] > 1 ? true : false;
			$master_header_data['partner_header_image'] = ($partner_cname_response['header_img'] == NULL) ? false : $partner_cname_response['header_img'];

			$master_header_data['partner_favicon'] = ($partner_cname_response['favicon_path'] == NULL) ? false : $partner_cname_response['favicon_path'];
			$master_header_data['partner_ui_css_path'] = $partner_cname_response['ui_css_path'];
			$master_header_data['partner_name'] = $partner_cname_response['cname'];
		}
		$master_header_data['is_logged_in'] = $is_logged_in;
		$master_header_data['active_feature_id'] = $active_feature_id;

		$master_header_data['has_seen_browser_warning'] = $this->ci->session->userdata('has_seen_browser_warning');
		$this->ci->session->set_userdata('has_seen_browser_warning', true);

		$feature_buttons_data = array();
		$this->get_master_nav_buttons_data($feature_buttons_data);
		$master_header_data['buttons_data'] = $feature_buttons_data;
		$data['master_header_data'] = $master_header_data;
	}


	//	function for showing all the views
	//	$controller is the controller object for the feature/subfeature
	//	$data is the array of data passed to controller->load->view() for all views
	//	$feature_button_id the id for the feature button to highlight
	//	$subfeature_body can be a string or an array
	//		if string it shows that view
	//		if array shows each view in array
	//	All the following are paths to a view to load
	//		$subfeature_body,
	//		$feature_html_header,
	//		$subfeature_html_header,
	//		$feature_js,
	//		$subfeature_js,
	//	$show_feature_header will hide the feature header if set to false
	//		used if you want to use a custom feature header
	//	Specific purpose of each file path:
	//		$subfeature_body, - the html body of the subfeature
	//		$feature_html_header, - the css and other <head></head> data which is used for all sub-features
	//		$subfeature_html_header, - the css and other <head></head> data for visible subfeature
	//		$feature_js, - the javascript and inlucdes common to all subfeatures in the feature
	//		$subfeature_js, - the javascript and includes for specific visible subfeature
	//
	//
	//	comment last edited (3/28/2013 by Scott Huber)
	public function show_views(
		$controller,
		&$data,
		$feature_button_id,
		$subfeature_body,
		$feature_html_header,
		$subfeature_html_header,
		$feature_js,
		$subfeature_js,
		$show_feature_header = true,
		$only_include_nav_css = false
	)
	{

		if(!array_key_exists('master_header_data', $data))
		{
			$this->get_master_header_data($data, $feature_button_id);
		}

		if(!$show_feature_header ||
			!array_key_exists('feature_header_data', $data)
		)
		{
			$data['skip_common_feature_header'] = true;
		}

		if (!array_key_exists('partner_favicon', $data['master_header_data']))
		{
			$data['master_header_data']['partner_favicon'] = '/images/favicon-1.ico';
			$data['master_header_data']['partner_header_image'] = '//s3.amazonaws.com/brandcdn-assets/partners/frequence/frequence-logo.png';
		}

		if(trim($data['master_header_data']['partner_favicon']) == '')
		{
			$data['master_header_data']['partner_favicon'] = '//s3.amazonaws.com/brandcdn-assets/partners/default/images/favicon.ico';
		}

		$data['master_header_data']['only_include_nav_css'] = $only_include_nav_css;
		$data['feature_html_head_view_path'] = $feature_html_header;
		$data['subfeature_html_head_view_path'] = $subfeature_html_header;
		$controller->load->view('vl_platform_2/ui_core/common_master_header', $data);

		if($show_feature_header)
		{
			$controller->load->view('vl_platform_2/ui_core/common_feature_header', $data);
		}

		if(gettype($subfeature_body) == 'array')
		{
			foreach($subfeature_body as $view_body)
			{
				$controller->load->view($view_body, $data);
			}
		}
		else
		{
			$controller->load->view($subfeature_body, $data);
		}

		$data['feature_js_view_path'] = $feature_js;
		$data['subfeature_js_view_path'] = $subfeature_js;
		$controller->load->view('vl_platform_2/ui_core/common_footer', $data);
	}

	public function get_user_profile_data($user_id){
		$return = $this->ci->vl_platform_model->get_user_profile_details($user_id);
		// $return['user'] = $this->ci->vl_platform_model->get_user_level_details($user_id);
		// if($return['user']['role'] == 'BUSINESS'){///if this user is an advertiser we need their salesperson's partner id and the advertiser biz name
		// 	$partner_id = $this->ci->users->get_partner_id_by_business_id($return['user']['advertiser_id']);
		// 	$return['advertiser_name'] = $this->ci->users->get_business_name_from_id($return['user']['advertiser_id']);
		// }else{ //creatives and admins and most importantly, SALES
		// 	$partner_id = $this->ci->tank_auth->get_partner_id($user_id);
		// }
  //       $return['partner'] = $this->ci->tank_auth->get_partner_info($partner_id);
		return $return;
	}

	public function get_access_manager_permission($user_id)
	{
		return $this->ci->spectrum_account_model->get_user_access_for_access_manager($user_id);
	}
}
