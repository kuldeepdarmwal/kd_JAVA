<?php
use \AcceptanceTester;

class MPQCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
    }

    /**
     * @group public
     * @group mpq
     * @actor AcceptanceTester\UserSteps
     */
    public function load_geo_regions_on_mpq(AcceptanceTester $I)
    {
        $I->am('Public User');
        $I->wantTo('test the region, radius, and zip functionality for /mpq');

        $I->amOnSubdomain('secure' . SUBDOMAIN);
        $I->amOnPage(MPQPage::route('/ref/tech@vantagelocal.com'));
        $I->seeInTitle('MPQ');

        // Get rid of welcome modal if it's visible
        $I->waitForElementVisible('#welcome_modal', 5);
        $I->click('#welcome_modal .modal-footer button');
        $I->waitForElementNotVisible('#welcome_modal', 5);

        // check that there's no zips loaded
        $I->seeFieldIsEmpty($I->grabValueFrom('#set_zips'));

        //Check custom regions
        $I->seeElement('#custom_region_list');
        $I->fillField('#s2id_autogen4', 'Mountain View city, California');
        $I->waitForElement('span.select2-match');
        $I->click('ul.select2-results li:first-child');
        $I->see('Mountain View city, California', 'li.select2-search-choice div');

        $I->click('#custom_region_list a.btn-success');
        $I->waitForElementNotVisible('#map_loading_image', 30);
        $I->wait(5);

        $I->click('#known_zips_tab_anchor');
        $I->seeElement('#zip_list');
        $region_zips = $I->grabValueFrom('#set_zips');
        $I->dontSeeFieldIsEmpty($region_zips);
        $I->makeScreenshot('mpq_custom_region');

        // Check radius
        $I->click('#radius_search_pill a');
        $I->seeElement('#radius_search');
        $I->fillField('#radius', '5');
        $I->fillField('#address', '94041');

        $I->click('#radius_search a.btn-success');
        $I->waitForElementNotVisible('#map_loading_image', 30);
        $I->wait(5);

        $I->click('#known_zips_tab_anchor');
        $I->seeElement('#zip_list');
        $radius_zips = $I->grabValueFrom('#set_zips');
        $I->dontSeeFieldIsEmpty($radius_zips);
        $I->dontSeeInField('#set_zips', $region_zips);
        $I->makeScreenshot('mpq_radius');

    }
    
    /**
     * @group non_public
     * @group mpq
     * @actor AcceptanceTester\UserSteps
     */
    public function mpq_proposal_budget(AcceptanceTester $I)
    {

        $I->am('Whitelabel Sales User');
        $I->wantTo('test the budget functionaity for MPQ');

        $I->amOnSubdomain('wl-test' . SUBDOMAIN);
        $I->login('test_p_sales');

        $I->amOnPage(MPQPage::$URL);
        $I->seeInTitle('MPQ');

        $I->click('#form_toggle_button');
        $I->waitForElementVisible('#options_form');

        // Test dollar budget
        $I->selectOption('#dollar_budget_range_div select', "1");
        $I->seeNumberOfElements('#dollar_budget_options_list form', 3);
        $I->click('#dollar_budget_options_list form:last-child div.pull-right button');
        $I->seeNumberOfElements('#dollar_budget_options_list form', 2);
        $I->click('#dollar_budget_options_div button#option_add_button');
        $I->seeNumberOfElements('#dollar_budget_options_list form', 3);
        $I->fillField('#dollar_budget_options_list form:last-child .mpq_option_amount', '3500');
        $I->pressKey('body', 'tab');
        $I->waitForElement('#dollar_budget_options_list form:last-child .mpq_option_result', 5);
        $summary = $I->grabTextFrom('#dollar_budget_options_list form:last-child .mpq_option_result');
        $I->fillField('#dollar_budget_options_list form:last-child .mpq_option_cpm', '6');
        $I->pressKey('body', 'tab');
        $I->waitForElementChange('#dollar_budget_options_list form:last-child .mpq_option_result', function(\WebDriverElement $el) use ($summary)
        {
            return $el->getText() !== $summary;
        }, 5);

        // Test impressions budget
        $I->click('#impression_budget_pill a');
        $I->waitForElementVisible('#impressions_tab');

        $I->seeNumberOfElements('#dollar_budget_options_list form', 3);
        $I->click('#impression_budget_options_list form:last-child div.pull-right button');
        $I->seeNumberOfElements('#impression_budget_options_list form', 2);
        $I->click('#impression_budget_options_div div.row button');
        $I->seeNumberOfElements('#impression_budget_options_list form', 3);
        $I->fillField('#impression_budget_options_list form:last-child .mpq_option_amount', '350000');
        $I->pressKey('body', 'tab');
        $I->waitForElement('#impression_budget_options_list form:last-child .mpq_option_result', 5);
        $summary = $I->grabTextFrom('#impression_budget_options_list form:last-child .mpq_option_result');
        $I->fillField('#impression_budget_options_list form:last-child .mpq_option_cpm', '6');
        $I->pressKey('body', 'tab');
        $I->waitForElementChange('#impression_budget_options_list form:last-child .mpq_option_result', function(\WebDriverElement $el) use ($summary)
        {
            return $el->getText() !== $summary;
        }, 5);

        $I->logout();
    }

    /**
     * @group non_public
     * @group mpq
     * @actor AcceptanceTester\UserSteps
     */
    public function submit_mpq_insertion_order(AcceptanceTester $I)
    {
        $I->am('Whitelabel Sales User');
        $I->wantTo('submit an MPQ insertion order');

        $I->amOnSubdomain('wl-test' . SUBDOMAIN);
        $I->login('test_p_sales');

        $timestamp = time();
        $fields = array(
            "public" => false,
            "set_zips" => "90001",
            "mpq_type" => array(
                "type" => "insertion_order"
            ),
            "submit_info" => array(
                'advertiser_business_name_input' => TEST_PREFIX . $timestamp
            )
        );

        $I->submitMPQ($fields);
        $I->logout();

        $I->seeInDatabase('mpq_sessions_and_submissions', array('is_submitted' => 1, 'advertiser_name' => TEST_PREFIX . $timestamp));
    }

    /**
     * @group public
     * @group mpq
     * @actor AcceptanceTester\UserSteps
    */
    public function submit_public_mpq(AcceptanceTester $I)
    {
        $I->am('Public User');
        $I->wantTo('submit a public-facing MPQ');

        $I->amOnSubdomain('secure' . SUBDOMAIN);

        $timestamp = time();
        $fields = array();
        $fields['submit_info'] = array(
            'advertiser_business_name_input' => TEST_PREFIX . $timestamp
        );

        $I->submitMPQ($fields);

        $I->seeInDatabase('mpq_sessions_and_submissions', array('is_submitted' => 1, 'advertiser_name' => TEST_PREFIX . $timestamp));
    }

}