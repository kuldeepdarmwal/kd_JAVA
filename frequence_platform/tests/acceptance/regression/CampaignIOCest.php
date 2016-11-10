<?php
use \AcceptanceTester;

class CampaignIOCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
    }

    /**
     * @group non_public
     * @group campaignio
     * @actor AcceptanceTester\UserSteps
     */
    public function submit_io_campaign(AcceptanceTester $I)
    {
    	$TEST_NAME = "SUBMIT_MPQ_IO";
        $I->am('Admin user');
        $I->wantTo('1. Submit an MPQ insertion order and 2. Create a Campaign');

        $I->amOnSubdomain('secure' . SUBDOMAIN);
        $I->login('test_admin');
        $I->username = 'test_admin';
        $I->user_id = $I->grabFromDatabase('users', 'id', array('username' => $I->username));

        $timestamp = time();
        $MY_TEST_PREFIX=TEST_PREFIX . $timestamp;
        $fields = array(
            "public" => false,
            "set_zips" => "90001",
            "mpq_type" => array(
                "type" => "insertion_order"
            ),
            "submit_info" => array(
                'advertiser_business_name_input' => $MY_TEST_PREFIX
            )
        );
       
        $mpq_session_id = $I->populateOneMpqSession(
            array(
                'creation_time' => date("Y-m-d H:i:s"),
                'creator_user_id' => $I->user_id, 
                'is_submitted' => 1,
            	'notes' => 	$MY_TEST_PREFIX,
                'region_data' => '{"page":1,"total":1,"rows":[{"id":"94041","cell":["94041","12929",7015.3284468165,0.50947482403898,0.49052517596102,0.18802691623482,0.063964730450924,0.2075179828293,0.17603836336917,0.14904478304587,0.11671436305979,0.098692861010132,0.49661920292737,0.036671704717206,0.22758730411264,0.23291703126243,0.0062047569803516,0.756,0.244,0.30790520671164,0.23611831862999,0.18284033904169,0.27313613561668,0.361,0.326,0.312]}]}',
                'demographic_data' => '1_1_1_1_1_1_1_1_1_1_1_1_1_1_1_1_1_1_1_1_1_1_1__All_Force include sites here...',
                'advertiser_name' => $MY_TEST_PREFIX
            ),
            $I->user_id
        );
      
        $io_id = $I->populateInsertionOrder(array(
        		'mpq_id' => $mpq_session_id,
        		'impressions' => 9989,
        		'start_date' => date("Y-m-d"),
        		'term_type' => 'monthly',
        		'landing_page' => 'http://frequence.com?testing='.$MY_TEST_PREFIX,
        		'include_rtg' => 1
        ));
        
        $I->amOnPage("/proposal_builder/get_all_mpqs/io?limit=5");
        $I->seeInTitle('All MPQs');
        $I->click("//table[@id='mpq_list_table']//a[@data-io-id='".$io_id ."']");
        $I->waitForElementVisible('#campaign_modal_label');
        $I->fillField('input[name=campaign_name]', $MY_TEST_PREFIX);
        $I->click('.select2-container .select2-choice .select2-arrow');
        $I->fillField('#select2-drop .select2-search .select2-input', "zz_test_ignore_me");
        $I->waitForElement('span.select2-match');
        $I->click('ul.select2-results li:first-child');
        $I->click('#campaign_submit .modal-footer .btn-primary');
        $I->waitForElementNotVisible('#campaign_modal');
        $I->logout();
        $I->seeInDatabase('Campaigns', array('name' => $MY_TEST_PREFIX));
    }   

}