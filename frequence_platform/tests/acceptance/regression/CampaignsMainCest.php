<?php
namespace regression;
use \AcceptanceTester;

class CampaignsMainCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
    }

	/**
     * @group non_public
     * @group campaigns_main
	 * @group campaigns_main_ops
     * @actor AcceptanceTester\UserSteps
    */

	public function load_campaigns_main_ops_user(AcceptanceTester $I)
	{
		$is_ops_admin = true;

		$I->am('Test Ops/Admin User');
		$I->wantTo('test campaigns main loads correctly');
		
		$I->amOnSubdomain('secure' . SUBDOMAIN);
		$I->login('test_admin');
		$this->campaigns_main_loads($I, $is_ops_admin);
		$this->campaigns_main_bap($I);
		$I->logout();
	}

	/**
     * @group non_public
     * @group campaigns_main
	 * @group campaigns_main_sales
     * @actor AcceptanceTester\UserSteps
    */

	public function load_campaigns_main_sales_user(AcceptanceTester $I)
	{
		$is_ops_admin = false;

		$I->am('Test Sales User');
		$I->wantTo('test campaigns main loads correctly');
		
		$I->amOnSubdomain('frequence' . SUBDOMAIN);
		$I->login('test_owner_sales_s');
		$this->campaigns_main_loads($I, $is_ops_admin);
		$I->logout();
	}

	protected function get_wait_for_change_closure($starting_value)
	{
		return function(\WebDriverElement $el) use ($starting_value) {
			$new_value = $el->getText();

			if($new_value != $starting_value)
			{
				return true;
			}

			return false;
		};
	}
	
	protected function campaigns_main_loads(AcceptanceTester $I, $is_ops_admin)
	{				
		$I->amOnPage(\CampaignsMainPage::$URL);
		
		$I->waitForElement('#c_main_loading_img', 30);
		$I->waitForElement('#campaign_table_wrapper', 30);
		//datatables elements exist
		$I->seeElement('#campaign_table_filter');
		$I->seeElement('#campaign_table_paginate');
		$I->seeElement('#campaign_table');
		//other stuff exists
		$I->seeElement('#campaign_html_header');
		if($is_ops_admin)
		{
			$I->seeElement('#c_bulk_action_html');
		}

		$I->fillField('#campaign_table_filter input', 'a8b7d89caad849do40d0l503dfg8b7fd0a0argd');
		$I->waitForText('No matching records found', 30, '#campaign_table td.dataTables_empty');

		$I->fillField('#campaign_table_filter input', 'zz_test');
		$I->waitForElementVisible('#campaign_table > tbody > tr:first-of-type > td.c_checkbox', 30);
		$I->waitForElementChange('#campaign_table > tbody > tr:first-of-type > td.c_campaign_column > div',
			$this->get_wait_for_change_closure(''),
			30
		);

		$this->campaigns_main_can_sort($I);
		$this->campaigns_main_action_buttons($I, $is_ops_admin);
		$this->campaigns_main_bulk_download($I, $is_ops_admin);
	}

	protected function campaigns_main_action_buttons(AcceptanceTester $I, $is_ops_admin)
	{
		//can see charts
		$I->executeJS("
			$('#campaign_table > tbody a.dropdown-toggle.aic_charts:first').click();
			$('#campaign_table > tbody a.ai_charts:first').click();");
		$I->waitForElementVisible('#time_series_chart', 30);
		$I->click('#detail_modal > div.modal-header > button.close');
		
		if($is_ops_admin)
		{
			//can archive
			$I->executeJS("
				$('#campaign_table > tbody a.dropdown-toggle.aic_archive:first').click();
				$('#campaign_table > tbody a.ai_archive:first').click();");
			$I->waitForText('Are you sure you want to graveyard', 15, '#confirm_modal_detail_body');
			$I->click('#confirm_modal > div.modal-header > button.close');

			//can go to dsp campaign (unable to verify the results of this)
			//$I->executeJS("
			//	$('#campaign_table > tbody a.dropdown-toggle.aic_dsp_campaign:first').click();
			//	$('#campaign_table > tbody a.ai_dsp_campaign:first').click();");
		}

		//can go to landing page
		$I->click('#campaign_table > tbody > tr:first-of-type a.dropdown-toggle.aic_landing_page');
		$I->click('#campaign_table > tbody > tr:first-of-type a.ai_landing_page');

		//can view creatives
		$I->fillField('#campaign_table_filter input', 'zz_test_scott_prod_2015_01_30_regress_adlinker');
		$I->wait(5);

		$I->executeJS("
				$('#campaign_table > tbody a.dropdown-toggle.aic_creative:first').click();
				$('#campaign_table > tbody a.ai_creative:first').click();");
		$I->waitForElementVisible('#adset_modal_detail_body > a', 30);
		$I->click('#adset_modal > div.modal-header > button.close');
		
		//can download tracking tags (unable to verify the results of this)
		//$I->executeJS("
		//	$('#campaign_table > tbody a.dropdown-toggle.aic_rtg_tags:first').click();
		//	$('#campaign_table > tbody a.ai_rtg_tags:first').click();");
	}

	protected function campaigns_main_can_sort(AcceptanceTester $I)
	{
		$I->click('div.FixedHeader_Cloned.fixedHeader.FixedHeader_Header thead > tr > th.c_campaign_column');
		$I->wait(5);
		$first_advertiser = $I->grabTextFrom('#campaign_table > tbody > tr:first-of-type > td.c_campaign_column > div');

		$I->click('div.FixedHeader_Cloned.fixedHeader.FixedHeader_Header thead > tr > th.c_campaign_column');
		$I->waitForElementChange('#campaign_table > tbody > tr:first-of-type > td.c_campaign_column > div',
			$this->get_wait_for_change_closure($first_advertiser),
			30
		);
	}
	
	protected function campaigns_main_bulk_download(AcceptanceTester $I, $is_ops_admin)
	{
		$bulk_download_disabled = $I->grabAttributeFrom('#bulk_download_init_button', 'disabled');
		$I->click('#bulk_download_button');
		$I->waitForElementVisible('#bulk_download_modal', 30);
		$I->waitForElementNotVisible('#bulk_download_loader_content', 30);
		$warning_hidden = $I->executeJS("return ($('#bulk_download_warning_content').css('display') == 'none')");
		if($warning_hidden == true)
		{
			$I->waitForElementVisible('#bulk_download_start_date', 30);
			$I->click('#bulk_download_init_button');
			$I->waitForText('An email has been sent to your address', 30, '#c_message_box_content');
		}
		else
		{
			$I->see('It looks like you already have a bulk data request in progress.', '#bulk_download_warning_content');
		}
	}

	protected function campaigns_main_bap(AcceptanceTester $I)
	{
		$I->click('#campaign_table > tbody > tr:first-of-type td.c_checkbox input');
		
		$I->click('#c_bulk_action_html div.dropdown-toggle');
		$I->click('#bap_submit_bap_campaigns');

		$I->click('#c_bulk_action_html div.dropdown-toggle');
		$I->click('#bap_bulk_archive_campaigns');
		$I->waitForElementVisible('#bulk_archive_modal_campaigns', 15);
		$I->click('#bulk_archive_modal > div.modal-header > button.close');
	}

}
