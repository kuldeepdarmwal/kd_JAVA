<?php
use \AcceptanceTester;

class CampaignSetupCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
    }

    // tests

    /**
     * @group non_public
     * @group campaign_setup
     * @actor AcceptanceTester\UserSteps
    */
    public function createAdvertiserCampaign(AcceptanceTester $I)
    {
        $I->am('Test Admin User');
        $I->wantTo('test campaign_setup, checking advertiser and campaign creation');

        $I->amOnSubdomain('secure' . SUBDOMAIN);
        $I->login('test_admin');

        $I->amOnPage(CampaignSetupPage::$URL);
        $I->seeInTitle('AL4k [Campaigns]');

        $timestamp = time();
        $advertiserName = TEST_PREFIX."_ADV_".$timestamp;
        $this->createAdvertiser($I, $advertiserName);
        $this->pushAdvertiserToBidder($I, $advertiserName);

        $campaignName = TEST_PREFIX. "CAMPAIGN 01".$timestamp;
        $this->createCampaignForNewAdvertiser($I, $campaignName);
        $this->pushCampaignToBidder($I, $campaignName);
        $this->checkBidderImpressionValues($I);
    }

    protected function createAdvertiser(AcceptanceTester $I, $advertiserName)
    {
        $I->waitForElement('#s2id_advertiser_select');
        $I->click('#s2id_advertiser_select .select2-choice .select2-arrow');
        $I->seeElement('#select2-drop');    
        $I->fillField('#select2-drop .select2-input', "*New*");
        $I->waitForElement('.select2-results li.select2-result-selectable > div > div > strong', 30);
        $I->wait(1);
        $I->click('#select2-drop ul.select2-results li:first-child');
        $I->seeElement('#new_advertiser_name');
        $I->fillField('#new_advertiser_name', $advertiserName);


        $I->click('#s2id_sales_select  .select2-choice .select2-arrow');
        $I->fillField('#select2-drop .select2-input', ":: WL Sales");
        $I->waitForElement('span.select2-match');
        $I->click('#select2-drop ul.select2-results li:first-child');

        $I->waitForText("created" ,30,'#load_advertiser_status');
        $I->seeInDatabase('Advertisers', array('Name' => $advertiserName));
    }

    protected function pushAdvertiserToBidder(AcceptanceTester $I, $advertiserName)
    {
        $I->seeElement('#add_to_td_button');
        $I->click('#add_to_td_button');
        $I->waitForText("added to bidder" ,30,'#load_advertiser_status');

        $ttd_adv_id = $I->grabFromDatabase('Advertisers', 'ttd_adv_id', array('Name' => $advertiserName));
        $I->assertNotEmpty($ttd_adv_id, "ttd_adv_id for advertiser is populated in database");
    }

    protected function createCampaignForNewAdvertiser(AcceptanceTester $I, $campaignName)
    {
        $I->waitForElementVisible('#campaign_section',30);
        $I->see("*New*", '#s2id_campaign_select .select2-choice .select2-chosen');
        $I->seeElement('#new_campaign_name');

        $I->fillField('#new_campaign_name', $campaignName);
        $I->fillField('#campaign_landing_page_url', "http://www.frequence.com/");
      //  $I->fillField('#campaign_target_impressions', "15");
      //  $I->click("#in_total_radio");
      //  $I->fillField('#invoice_budget', "3.50");
      //  $I->click('#campaign_target_start_date');
      //  $I->wantTo("Fill out the start and end dates");
      //  $I->executeJS('$(\'#campaign_target_start_date\').val(\''.date('Y-m-d', strtotime('+1 day')).'\')');
      //  $I->executeJS('$(\'#campaign_target_end_date\').val(\''.date('Y-m-d', strtotime('+15 days')).'\')');
        $I->click('#initial_timeseries_btn');
        $I->click("#cat_50");
        $I->click("#cat_55");
        $I->makeScreenshot('timeseries_btn1');
        $I->click('#campaign_load_button');
        $I->wait(3);

        $I->waitForText("Campaign" ,30,'#load_campaign_status .label-success');
        $I->seeInDatabase('Campaigns', array('Name' => $campaignName));
    }

    protected function pushCampaignToBidder(AcceptanceTester $I, $campaignName)
    {
        
        $I->click('#is_display_campaign');
        $I->seeElement('#add_campaign_to_td_button');

        $I->click("#s2id_tag_file_select .select2-choice .select2-arrow");
        $I->seeElement('#select2-drop');    
        $I->fillField('#select2-drop .select2-input', "*New*");
        $I->click('#select2-drop ul.select2-results li:first-child');
        $I->seeElement("#new_tag_file_name");
        $I->fillField("#new_tag_file_name", $campaignName);
        $I->click("#add_new_tag_file_button");

        $I->click('#add_campaign_to_td_button');

        $I->waitForElementVisible('#modify_adgroups_button', 75);

        $ttd_campaign_id = $I->grabFromDatabase('Campaigns', 'ttd_campaign_id', array('Name' => $campaignName));
        $I->assertNotEmpty($ttd_campaign_id, "ttd_campaign_id for campaign is populated in database");

        $I->seeElement('#add_av_campaign_to_td_button');
        $I->click('#add_av_campaign_to_td_button');
        $I->waitForElementNotVisible('#add_av_campaign_to_td_button', 45);

        $ttd_av_campaign_id = $I->grabFromDatabase('Campaigns', 'ttd_av_id', array('Name' => $campaignName));
        $I->assertNotEmpty($ttd_av_campaign_id, "ttd_av_id for campaign is populated in database");
    }

    protected function checkBidderImpressionValues(AcceptanceTester $I)
    {
        //$expectedTotalImpressions = (int)$I->grabFromDatabase('ttd_budget_operands', "(1+budget_buffer)*15*(1+impression_leakage_buffer)*(ttd_daily_target_weight_pc+ttd_daily_target_weight_mobile_320+ttd_daily_target_weight_mobile_no_320+ttd_daily_target_weight_tablet+ttd_daily_target_weight_rtg)*1000");
        $expectedTotalImpressions_four_months = 543456;
        $expectedTotalImpressions_three_months = 411264;
        $I->seeElement('#modify_adgroups_button');
        $I->click('#modify_adgroups_button');
        $I->waitForElementVisible('#impression_budget_box', 30);
        $actualTotalImpressions = (int)$I->grabValueFrom('#impression_budget_box');
        if ($actualTotalImpressions != $expectedTotalImpressions_four_months && $actualTotalImpressions != $expectedTotalImpressions_three_months)
        {
            $I->assertEquals($expectedTotalImpressions_four_months, $actualTotalImpressions, " Expected bidder campaign impression budget matches actual campaign impression budget");
        }
    }
}