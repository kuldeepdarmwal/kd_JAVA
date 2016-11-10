<?php
use \AcceptanceTester;

class RegisterCest
{
	public function _before(AcceptanceTester $I)
	{
	}

	public function _after(AcceptanceTester $I)
	{
	}

	/**
     * @group non_public
     * @group register
     * @actor AcceptanceTester\UserSteps
     */
	public function happy_path_for_admin(AcceptanceTester $I)
	{
		$I->am('Admin User');
		$I->wantTo('test the functionality of /register by creating one user of each type');
		
		$I->amOnSubdomain('secure' . SUBDOMAIN);
		$I->login(RegisterPage::$test_login_admin);

		// Go to page and check page set up
		$I->amOnPage(RegisterPage::$URL);
		$I->seeInTitle('Register');
		$I->see('Register User');

		// Check default values are set
		$this->check_register_page_defaults($I, 'admin');

		// List of possible users on our platform
		$users = array(
			'admin',
			'business',
			'creative',
			'ops',
			'sales',
			'super_sales'
		);
		
		// Get relevant partner ids for users to check against db stuff
		$frequence_partner_id = $I->grabFromDatabase('wl_partner_details', 'id', array('partner_name' => 'Frequence'));
		$brand_cdn_partner_id = $I->grabFromDatabase('wl_partner_details', 'id', array('partner_name' => 'Brand CDN'));

		$frequence_partner_cname = $I->grabFromDatabase('wl_partner_details', 'cname', array('partner_name' => 'Frequence'));
		$brand_cdn_partner_cname = $I->grabFromDatabase('wl_partner_details', 'cname', array('partner_name' => 'Brand CDN'));

		// Go through each user and input data
		foreach($users as $user_type)
		{
			$user_details = array(
				'username' => "ZZ_TEST_CODECEPTION_{$user_type}_" . date('Y_m_d_') . strval(intval(microtime(true))),
				'email' => "ZZ_TEST_CODECEPTION_{$user_type}_" . date('Y_m_d_') . strval(intval(microtime(true))) . '@frequence.com',
				'password' => TEST_PASSWORD,
				'confirm_password' => TEST_PASSWORD,
				'firstname' => 'Codeception',
				'lastname' => 'Tester'
			);

			$check_boxes = array(
				'is_planner' => mt_rand(0,1),
				'is_placements' => mt_rand(0,1),
				'is_screenshots' => mt_rand(0,1),
				'is_engagements' => mt_rand(0,1),
			);

			$partner_id = $brand_cdn_partner_id;
			$partner_cname = $brand_cdn_partner_cname;
			if(strpos($user_type, 'sales') === false)
			{
				// About to create a non-sales user
				$I->selectOption('form select[name=role]', strtoupper($user_type));
				if($user_type == 'business')
				{
					$I->waitForElementVisible('#advertiser_select_container', 30);
					$this->get_select2_option($I, '#s2id_advertiser_select', RegisterPage::$codeception_advertiser_name_secure);
					$I->waitForElementChange('.email_cname', function(\WebDriverElement $el) {
						return $el->getText() !== '{Partner Name}';
					}, 30);

					$partner_id = null;
					$partner_cname = $frequence_partner_cname;
				}
			}
			else
			{
				// About to create a sales user
				$I->selectOption('form select[name=role]', 'SALES');
				$I->waitForElementVisible('#partner_container', 30);
				$this->get_select2_option($I, '#s2id_partner_select', 'Frequence');
				$I->waitForElementChange('.email_cname', function(\WebDriverElement $el) {
					return $el->getText() !== '{Partner Name}';
				}, 30);
				
				$partner_id = $frequence_partner_id;
				$partner_cname = $frequence_partner_cname;

				// Additional info for sales users added to user_details array
				$user_details['address 1'] = '320 Mountain View Ave';
				$user_details['city'] = 'Mountain View';
				$user_details['state'] = 'CA';
				$user_details['zip'] = '94041';
				$user_details['phone_number'] = '1234567890';
					
				if($user_type == 'super_sales')
				{
					$I->checkOption('#isGroupSuper');
				}
			}

			// Fill fields on page with data from user_details array
			$this->fill_fields_on_page_from_array($I, $user_details);
			$this->check_email_snippet_populated_correctly($I, $user_details, $partner_cname);

			// Check/uncheck checkboxes based on check_boxes array
			$this->check_or_uncheck_checkboxes_from_array($I, $check_boxes);

			// Submit the form and verify success messages
			$this->submit_register_form($I);

			// Check default values are still set after page reloads
			$this->check_register_page_defaults($I, 'admin');
			
			// Check that user info successfully put in db
			$user_details['role'] = ($user_type == 'super_sales') ? 'SALES' : strtoupper($user_type);
			$user_details['isGroupSuper'] = ($user_type == 'super_sales') ? 1 : 0;
			$user_details['placements_viewable'] = $check_boxes['is_placements'];
			$user_details['planner_viewable'] = $check_boxes['is_planner'];
			$user_details['screenshots_viewable'] = $check_boxes['is_screenshots'];
			$user_details['beta_report_engagements_viewable'] = $check_boxes['is_engagements'];
			$user_details['ad_sizes_viewable'] = 0; //Default of 0 for all new users
			$user_details['partner_id'] = $partner_id;

			// Unset password fields from db check
			unset($user_details['password']);
			unset($user_details['confirm_password']);
			if(isset($user_details['address 1']))
			{
				// Address is stored under different name in db than on the page
				$user_details['address_1'] = $user_details['address 1'];
				unset($user_details['address 1']);
			}

			$I->seeInDatabase('users', $user_details);
			$this->test_new_user_can_login($I, $user_type, $user_details, 'secure', RegisterPage::$test_login_admin);
		}
	}

	/**
     * @group non_public
     * @group register
     * @actor AcceptanceTester\UserSteps
     */
	public function happy_path_for_wl_super(AcceptanceTester $I)
	{
		$I->am('Test Whitelabel Super Sales User');
		$I->wantTo('test the functionality of /register for partner sales users by creating a business user');
		
		$I->amOnSubdomain('wl-test' . SUBDOMAIN);
		$I->login(RegisterPage::$test_login_wl_sales);

		// Go to page and check page set up
		$I->amOnPage(RegisterPage::$URL);
		$I->seeInTitle('Register');
		$I->see('Register User');

		// Check default values are set
		$this->check_register_page_defaults($I, 'sales');

		// List of possible users that super sales users can create
		$users = array(
			'business'
		);
		
		// Get relevant partner ids for users to check against db stuff
		$wl_sales_partner_id = $I->grabFromDatabase('users', 'partner_id', array('username' => RegisterPage::$test_login_wl_sales));
		$wl_sales_partner_cname = $I->grabFromDatabase('wl_partner_details', 'cname', array('id' => $wl_sales_partner_id));

		// Go through each user and input data
		foreach($users as $user_type)
		{
			$user_details = array(
				'email' => "ZZ_TEST_CODECEPTION_{$user_type}_" . date('Y_m_d_') . strval(intval(microtime(true))) . '@frequence.com',
				'password' => TEST_PASSWORD,
				'confirm_password' => TEST_PASSWORD,
				'firstname' => 'Codeception',
				'lastname' => 'Tester'
			);

			$check_boxes = array(
				'is_placements' => mt_rand(0,1),
				'is_screenshots' => mt_rand(0,1),
				'is_engagements' => mt_rand(0,1),
			);

			// Choose advertiser org
			$this->get_select2_option($I, '#s2id_advertiser_select', RegisterPage::$codeception_advertiser_name_wltest);
			$I->waitForElementChange('.email_cname', function(\WebDriverElement $el) {
				return $el->getText() !== '{Partner Name}';
			}, 30);

			// Fill fields on page with data from user_details array
			$this->fill_fields_on_page_from_array($I, $user_details);
			$this->check_email_snippet_populated_correctly($I, $user_details, $wl_sales_partner_cname);

			// Check/uncheck checkboxes based on check_boxes array
			$this->check_or_uncheck_checkboxes_from_array($I, $check_boxes);

			// Submit the form and verify success messages
			$this->submit_register_form($I);

			// Check default values are still set after page reloads
			$this->check_register_page_defaults($I, 'sales');
			
			// Check that user info successfully put in db
			$user_details['role'] = strtoupper($user_type);
			$user_details['isGroupSuper'] = 0;
			$user_details['planner_viewable'] = 0;
			$user_details['ad_sizes_viewable'] = 0; //Default of 0 for all new users
			$user_details['placements_viewable'] = $check_boxes['is_placements'];
			$user_details['screenshots_viewable'] = $check_boxes['is_screenshots'];
			$user_details['beta_report_engagements_viewable'] = $check_boxes['is_engagements'];
			$user_details['partner_id'] = null;

			// Unset password fields from db check
			unset($user_details['password']);
			unset($user_details['confirm_password']);

			$I->seeInDatabase('users', $user_details);
			
			$generated_username = $I->grabFromDatabase('users', 'username', array('email' => $user_details['email']));
			$I->assertContains("{$user_details['firstname']}_{$user_details['lastname']}", $generated_username, 'Username in db has been correctly generated and saved.');

			$this->test_new_user_can_login($I, $user_type, $user_details, 'wl-test', RegisterPage::$test_login_wl_sales);
		}
	}

	private function submit_register_form($I)
	{
		$I->click('form input[type=submit]');
		$I->waitForElement('div.alert.alert-success', 30);
		$I->see('New user successfully created!', 'div.alert.alert-success');
	}

	private function check_or_uncheck_checkboxes_from_array($I, $check_boxes)
	{
		foreach($check_boxes as $check_box => $is_checked)
		{
			($is_checked) ? $I->checkOption("#{$check_box}") : $I->uncheckOption("#{$check_box}");
		}
	}

	private function fill_fields_on_page_from_array($I, $user_details)
	{
		foreach($user_details as $field_name => $field_entry)
		{
			$I->fillField(['name' => $field_name], $field_entry);
		}
	}

	private function check_email_snippet_populated_correctly($I, $user_details, $partner_cname)
	{
		$I->see($user_details['firstname'], 'span#email_first_name');
		$I->see($user_details['lastname'], 'span#email_last_name');
		$I->see($partner_cname, 'span.email_cname');
		$I->see($user_details['email'], 'span#email_username');
		$I->see($user_details['password'], 'span#email_password');
	}

	private function test_new_user_can_login($I, $user_type, $user_details, $relogin_cname, $relogin_name)
	{
		// Log out and log in as the newly created user to make sure /register worked
		$I->logout();

		$subdomain = ($relogin_name == RegisterPage::$test_login_admin) ? $this->get_subdomain_for_user($user_type) : 'wl-test';
		$default_landing_page = $this->get_default_landing_page_for_user($user_type);

		$I->amOnSubdomain($subdomain . SUBDOMAIN);
		$I->login($user_details['email']);

		$I->dontSeeInTitle('Login');
		$I->seeInCurrentUrl($default_landing_page);
		$I->logout();

		// Log back in as user that called this method
		$I->amOnSubdomain($relogin_cname . SUBDOMAIN);
		$I->login($relogin_name);
		$I->amOnPage(RegisterPage::$URL);
		$I->waitForText('Register', 30);
	}

	private function get_subdomain_for_user($user_type)
	{
		if($user_type == 'admin' || $user_type == 'ops')
		{
			return 'secure';
		}
		return 'frequence';
	}

	private function get_default_landing_page_for_user($user_type)
	{
		switch ($user_type) 
		{
			case 'creative':
				return '/creative_uploader';
			case 'business':
			case 'sales':
			case 'super_sales':
				return '/report';
			default:
				return '/campaign_setup';
		}
	}

	private function check_register_page_defaults($I, $user_type)
	{
		if($user_type == 'admin')
		{
			$I->seeInField('form select[id=role]','BUSINESS');
			$I->dontSeeOptionIsSelected('form input[name=planner_viewable]', 'planner_viewable');
		}
		else
		{
			$I->see('Advertiser', 'form');
		}
		
		$I->see('Hello {First Name} {Last Name},', 'div.well');
		$I->seeOptionIsSelected('form input[name=send_registration_welcome_email]', 'false');

		$I->seeOptionIsSelected('form input[name=placements_viewable]', 'placements_viewable');
		$I->seeOptionIsSelected('form input[name=screenshots_viewable]', 'screenshots_viewable');
		$I->seeOptionIsSelected('form input[name=engagements_viewable]', 'engagements_viewable');

		$I->dontSeeElement('div.sales_contact_info');

		$I->see('{First Name}', 'span#email_first_name');
		$I->see('{Last Name}', 'span#email_last_name');
		$I->see('{Partner Name}', 'span.email_cname');
		$I->see('{Email}', 'span#email_username');
		$I->see('{Password}', 'span#email_password');
	}

	/*
	 * @param string $selector CSS selector of the Select2 wrapping div element
	 * @param string $value to select
	 * TODO: this should be a helper
	 */
	protected function get_select2_option(AcceptanceTester $I, $selector, $value)
	{
		$I->waitForElementVisible($selector, 30);
		$I->click($selector . ' .select2-choice .select2-arrow'); // expand select2
		$I->waitForElementVisible('#select2-drop', 10); // wait for it to be populated
		$I->fillField('#select2-drop .select2-input', $value); // begin searching for the value
		$I->waitForElementVisible('span.select2-match', 30); // wait for matches
		$I->click('span.select2-match'); // select the first match
	}
}