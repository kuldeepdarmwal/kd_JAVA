<?php
use \AcceptanceTester;

class BannerIntakeCest
{
	public function _before(AcceptanceTester $I)
	{
	}

	public function _after(AcceptanceTester $I)
	{
	}

	/**
	 * @group public
	 * @group banner_intake
	 * @actor AcceptanceTester\UserSteps
	 * COMMENTED until this is fixed. URL no longer works.
	 */
	public function submit_public_banner_intake(AcceptanceTester $I)
	{
		$I->amOnSubdomain('secure' . SUBDOMAIN);
		$I->login('test_admin');
		$advertiser_name = $this->submitBannerIntake($I);

		$I->seeInDatabase('adset_requests', array('scenes' => '["First scene for '.$advertiser_name.'"]'));
	}

	protected function submitBannerIntake(AcceptanceTester $I)
	{
		$I->amOnPage(BannerIntakePage::$URL);

		$advertiser = TEST_PREFIX . time();

		$I->fillField('#creative_name',$advertiser);	
		$I->fillField('#landing_page', 'http://www.frequence.com');	
		$I->fillField('#advertiser_website', 'http://www.frequence.com');
		$I->waitForElement('#s2id_advertiser');
		$I->click('#s2id_advertiser .select2-choice .select2-arrow');
		$I->seeElement('#select2-drop'); 
		$I->fillField('#select2-drop .select2-input', "01_zz_test_ignore_me");
		$I->waitForElement('#select2-drop ul.select2-results li:nth-child(2)', 10);
		$I->click('#select2-drop ul.select2-results li:nth-child(2)');
		$I->fillField('#scene_1_input', 'First scene for '.$advertiser);
		$I->click('#submit_request');
		$I->waitForElementVisible('.banner_intake_review_body');

		return $advertiser;
	}
}