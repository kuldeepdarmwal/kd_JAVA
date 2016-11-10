<?php
namespace AcceptanceTester;

/*
 * Common user actions.
 * Should be used for series of steps that
 * can be repeated among different tests.
 *
 * If, for some reason you need common tests which 
 * do not belong here, generate another step
 * class and use it to extend AcceptanceTester\UserSteps.
 * Then, use the @actor annotation before your
 * test to use the new Actor.
 */

class UserSteps extends \AcceptanceTester
{
	// Login
	/*
	 * Pass the username/email of the user you
	 * want to log in as. The password is defined in 
	 * /tests/_bootstrap.env.php
	 */
	public function login($username)
	{
		$I = $this;

		$I->amOnPage(\LoginPage::$URL);
		$I->fillField(\LoginPage::$usernameField, $username);
		$I->fillField(\LoginPage::$passwordField, TEST_PASSWORD);
		$I->click(\LoginPage::$loginButton);
	}

    public function logout()
    {
        $I = $this;

        $I->amOnPage('/logout');
    }

	public function createLap($fields = array())
	{
		$I = $this;

		$defaults = array(
			'geographic_targeting' => array(
				'search_type' => 'radius',
				'search_radius' => 5,
				'search_center' => '320 Mountain View Avenue, Mountain View, California',
				'zips' => ''
			),
			'media_targeting' => array(
				// To be filled out when more detailed planner tests are created
			),
			'reach_frequency' => array(
				// To be filled out when more detailed planner tests are created
			),
			'save_lap' => array(
				'advertiser' => 'ZZ_TEST_CODECEPTION ADVERTISER_' . date('Y_m_d'),
				'ad_plan_name' => 'ZZ_TEST_CODECEPTION ADPLAN_' . date('Y_m_d') . '_' . round(microtime(true)),
				'notes' => 'ZZ_TEST_CODECEPTION'
			)
		);

		$fields = (!empty($fields)) ? array_replace_recursive($fields, $defaults) : $defaults;

		$I->amOnPage('/planner');
		$I->waitForElementVisible('iframe#iframe-demo', 10);
		

		// Wait for google maps to load then populate geo info if needed
		$I->waitForJS('return (typeof google === "object" && typeof google.maps === "object")', 200);
		// Fill in geo info from $fields

		// Go to media targeting tab and fill in fields info
		$I->click('div#MediaTargetingMenuItem + a');
		$I->waitForJS('return $("#body_header").text() == "Media Targeting +"', 5);
		// Do stuff on media targeting tab

		// Go to reach frequency tab and fill in fields info
		$I->click('div#ReachFrequencyMenuItem + a');
		$I->waitForJS('return $("#body_header").text() == "Campaign Performance Estimate  +"', 10);
		// Do stuff on reach frequency tab

		// Go back to geo targeting tag in order to use _geo inputs
		$I->click('div#GeoTargetingMenuItem + a');
		$I->waitForElement('#body_header_geo', 10);
		
		$I->click('div#SaveLapMenuItem + a');
		$I->waitForElementVisible('div#notes_header_geo', 5);

		$I->fillField('input#advertiser_geo', $fields['save_lap']['advertiser']);
		$I->fillField('input#plan_name_geo', $fields['save_lap']['ad_plan_name']);
		$I->fillField('textarea#notes_geo', $fields['save_lap']['notes']);

		$I->click('#submitButton_geo');
		return $fields;
    }

    // MPQ
    public function submitMPQ($fields = array())
    {
        $I = $this;

        $defaults = array(
            "public" => true,
            "set_zips" => "90001",
            "demographic" => array(),
            "contextual" => array(),
            "mpq_type" => array(
                "type" => "proposal",
                "values" => array(
                    "proposal_type" => "dollar_budget",
                    "dollar_budget_range_div select" => "1"
                )
            ),
            "creative_request" =>  "existing",
            "submit_info" => array(
            	'advertiser_website_url_input' => 'brandcdn.com',
	            'requester_name_input' => 'Codeception Tester',
	            'agency_website_input' => 'brandcdn.com',
	            'requester_email_address_input' => 'codeception@frequence.com',
	            'requester_phone_number_input' => '1234567890'
            )
        );

        // Merge the passed-in options with the defaults if they exist
        $fields = !empty($fields) ? array_replace_recursive($defaults, $fields) : $defaults;

        if ($fields["public"])
        {
            $I->amOnPage(\MPQPage::route('/ref/tech@vantagelocal.com'));
            $I->seeInTitle('MPQ');

            // Get rid of welcome modal if it's visible
            $I->waitForElementVisible('#welcome_modal', 5);
            $I->click('#welcome_modal .modal-footer button');
            $I->waitForElementNotVisible('#welcome_modal', 5);
        }
        else
        {
            $I->amOnPage(\MPQPage::$URL);
            $I->seeInTitle('MPQ');
        }

        $I->click('#known_zips_tab_anchor');
        $I->fillField('#set_zips', $fields['set_zips']);
        $I->click('#zip_list a.btn-success');
        $I->waitForElementNotVisible('#map_loading_image', 30);

        $I->fillField('#s2id_autogen2', 'Accounting');
        $I->waitForElement('span.select2-match');
        $I->click('#select2-drop ul.select2-results li:first-child');
        
        switch ($fields['mpq_type']['type'])
        {
            case "proposal":
                if (!$fields['public'])
                {
                    $I->click('#form_toggle_button');
                    $I->waitForElementVisible("#options_form", 5);
                }
                if ($fields['mpq_type']['values']['proposal_type'] == "dollar_budget")
                {
                    $I->selectOption('#dollar_budget_range_div select', "1");
                }
                else
                {
                    $I->click('#impression_budget_pill a');
                }
                $I->click('#proposal_submit_button');
                break;

            case "insertion_order":
                $I->fillField('#impressions_box', '100000');
                $I->fillField('#landing_page_input', 'http://brandcdn.com');

                $I->selectOption('handle_creative_request', 'creative_new_request');
                $I->waitForElementVisible('#creative_new_request');

                $I->waitForElementVisible('#step1');

                /*
                 * TODO:
                 * Attaching a file currently returns "Invalid Command Method".
                 * Not sure how to get this to work with webdriver.
                 *
                 * $I->attachFile('#file_upload', 'sample_upload.png');
                 * $I->waitForElementVisible('.uploaded_file', 30);
                */

                $I->click('.actions button.btn-next');
                $I->waitForElementVisible('#step2', 5);
                $I->fillField('#scene_1_input', 'This is a sample scene...');
                $I->click('#add_scene_cg a.btn');
                $I->waitForElementVisible('#scene_2_input', 5);
                $I->click('#remove_scene_2_button');
                $I->waitForElementNotVisible('#scene_2_input');

                $I->click('.actions button.btn-next');
                $I->waitForElementVisible('#step3', 5);

                $I->click('.actions button.btn-next');
                $I->waitForElementVisible('#step4', 5);

                $I->click('#insertion_order_submit_button');
                break;
        }

        $I->waitForElementVisible('#submit_modal', 10);
        $I->wait(5);

        foreach($fields['submit_info'] as $field => $value)
        {
            $I->fillField('#'.$field, $value);
        }
        
        if ($fields['mpq_type']['type'] == "proposal")
        {
        	$I->fillField('#s2id_autogen3', "B");
        	$I->waitForElement('span.select2-match');
        	$I->click('ul.select2-results li.select2-results-dept-0');
        }
        
        $I->click('#submit_proposal_request_button');
        $I->waitForText('Hello '. $fields['submit_info']['requester_name_input'] .'!', 30);
    }
    
    /*
     * Populates the database with an MPQ session
     * for testing.
     *
     * Returns the ID of the created session.
     */
    public function populateOneMpqSession($mpq_session_data)
    {
    	$I = $this;    
    	$I->haveInDatabase('mpq_sessions_and_submissions', $mpq_session_data);
    	return $I->grabFromDatabase('mpq_sessions_and_submissions', 'id', array('notes' => $mpq_session_data['notes']));
    }
    
    /*
     * Populates the database with an insertion order
     * for testing.
     */
    public function populateInsertionOrder($insertion_order_data)
    {
    	$I = $this;
    	$I->haveInDatabase('mpq_insertion_orders', $insertion_order_data);
    	return $I->grabFromDatabase('mpq_insertion_orders', 'id', array('landing_page' => $insertion_order_data['landing_page']));
    }
}