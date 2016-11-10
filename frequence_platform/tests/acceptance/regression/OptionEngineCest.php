<?php
use \AcceptanceTester;

class OptionEngineCest
{
	public function _before(AcceptanceTester $I)
	{
	}

	public function _after(AcceptanceTester $I)
	{
	}

	/**
     * @group non_public
     * @group option_engine
     * @actor AcceptanceTester\UserSteps
     */
	public function createOptionEngineProposalFromScratch(AcceptanceTester $I)
	{
		$I->am('Ops User');
		$I->wantTo('test the functionality of /proposal_builder/option_engine');
		
		$I->amOnSubdomain('secure' . SUBDOMAIN);
		$I->login('TEST_ops');

		$fields = $I->createLap(); // Fields variable saved in order to compare values to what was input

		$I->amOnPage(OptionEnginePage::$URL);
		$I->seeInTitle('Option Engine');

		// Make sure values on page are initialized correctly
		$I->see('N/A', 'td.geo_population');
		$I->see('N/A', 'td.target_population');
		$I->seeCheckboxIsChecked('#show_pricing');
		$I->seeCheckboxIsChecked('#creative_design_1');
		$I->dontSeeCheckboxIsChecked('#campaign_or_month_1');

		// Enter into select2 value used as name from LAP creation to find the test LAP to use and load it
		$I->waitForElement('#s2id_selected_lap_1_1');
		$I->click('#s2id_selected_lap_1_1 .select2-choice .select2-arrow');
		$I->waitForJS('return $("#select2-drop div.select2-search input.select2-focused").hasClass("select2-active") === false;', 5);
		$I->seeElement('#select2-drop');
		$I->fillField('#select2-drop .select2-input', $fields['save_lap']['ad_plan_name']);
		$I->waitForJS('return $("#select2-drop div.select2-search input.select2-focused").hasClass("select2-active") === false;', 5);
		$I->click('ul.select2-results li:first-child div');
		$I->click('#lap_1_1'); // To change focus and start js field-filling
		$I->waitForJS('return $("#subtabs1 div.tabs-header div.tabs-wrap ul.tabs li.tabs-selected a.tabs-inner span.tabs-title").text() !== "Ad Plan 1";', 15);

		// After LAP loaded, make sure the link to the LAP exists and is correct based on text of link
		$lap_id = $I->grabValueFrom('#selected_lap_1_1');
		$I->dontSeeFieldIsEmpty($lap_id);
		$I->seeLink("Go to LAP {$lap_id}", "/proposal_builder/force_session/{$lap_id}");

		// Enter into select2 'test' to find a test rep to use and load them
		$I->waitForElement('#s2id_rep_id');
		$I->click('#s2id_rep_id .select2-choice .select2-arrow');
		$I->waitForJS('return $("#select2-drop div.select2-search input.select2-focused").hasClass("select2-active") === false;', 5);
		$I->seeElement('#select2-drop');
		$I->fillField('#select2-drop .select2-input', 'test');
		$I->waitForJS('return $("#select2-drop div.select2-search input.select2-focused").hasClass("select2-active") === false;', 5);
		$I->click('ul.select2-results li:nth-child(4) div');

		// After rep chosen, make sure the link to the rep_id exists and has correct id based on chosen rep from dropdown
		$rep_id = $I->grabValueFrom('#rep_id');
		$I->dontSeeFieldIsEmpty($rep_id);

		// Make sure values in population fields are not empty and are no longer N/A
		$total_population = $I->grabTextFrom('td.geo_population');
		$target_population = $I->grabTextFrom('td.target_population');
		$I->dontSeeFieldIsEmpty($total_population);
		$I->dontSeeFieldIsEmpty($target_population);
		$I->cantSeeInField('td.geo_population', 'N/A');
		$I->cantSeeInField('td.target_population', 'N/A');

		// Fill in textboxes
		$prop_name = 'ZZ_TEST_CODECEPTION PROPOSAL_' . date('Y_m_d');
		$option_name = 'ZZ_TEST_CODECEPTION OPTION_' . date('Y_m_d');
		$monthly_budget = 1001;
		$I->fillField('#proposal_name', $prop_name);
		$I->fillField('#option_name_1', $option_name);
		$I->fillField('#budget_1_1', strval($monthly_budget));
		$I->click('#lap_1_1'); // To change focus and start js field-filling

		// Verify js filled fields appropriately
		$I->waitForElement('td.rf_td_1_1', 5);
		$I->see(strval($monthly_budget), '#monthly_total_cost_1');
		$I->see(strval($monthly_budget), '#monthly_raw_cost_1');

		// Click to save the proposal
		$I->click('#save_button');

		// Check that the page now has signs of successful proposal creation
		$regex_string = '~' . OptionEnginePage::$URL . '/(\d+)$~';
		$prop_id = $I->grabFromCurrentUrl($regex_string);
		$I->dontSeeFieldIsEmpty($prop_id);

		$I->seeInField('#proposal_name', $prop_name);
		$I->seeInField('#option_name_1', $option_name);
		$I->seeInField('#rep_id', $rep_id);

		$I->seeLink("Go to LAP {$lap_id}", "/proposal_builder/force_session/{$lap_id}");
		$I->seeLink("Generate images for this lap", "/proposal_builder/lap_image_gen/{$lap_id}/{$prop_id}");
		$I->seeLink("Generate overview image", "/proposal_builder/lap_image_gen/overview/{$prop_id}");
		$I->seeLink("Edit Sitelist", "/proposal_builder/edit_sitelist/{$prop_id}");
		$I->seeLink("Edit Sitelist", "/proposal_builder/control_panel/{$prop_id}");

		// Check that proposal is saved correctly in db
		$db_values_to_check = array(
			'prop_id' => $prop_id,
			'prop_name' => $prop_name,
			'show_pricing' => 1,
			'rep_id' => $rep_id
		);
		$I->seeInDatabase('prop_gen_prop_data', $db_values_to_check);

		$num_impressions = $I->executeJS('return $("#impressions_1_1").val()');
		$retargeting_cost = $I->grabTextFrom('#retargeting_cost_1_1');
		$term = $I->grabValueFrom('#term_1_1 option[selected=selected]');
		$term_type = $I->grabValueFrom('#period_type_1_1 option[selected=selected]');
		$max_target_reach = $I->grabValueFrom('#demo_coverage_1_1');
		$gamma = $I->grabValueFrom('#gamma_1_1');
		$ip_accuracy = $I->grabValueFrom('#ip_accuracy_1_1');

		$db_values_to_check = array(
			'lap_id' => $lap_id,
			'prop_id' => $prop_id,
			'budget' => $monthly_budget,
			'impressions' => $num_impressions,
			'retargeting' => 1,
			'retargeting_price' => $retargeting_cost,
			'term' => $term, 
			'period_type' => $term_type,
			'geo_coverage' => $max_target_reach,
			'gamma' => $gamma,
			'ip_accuracy' => $ip_accuracy,
			'demo_coverage' => $max_target_reach,
			'custom_impression_cpm' => null,
			'custom_retargeting_cpm' => null
		);
		$I->seeInDatabase('prop_gen_adplan_option_join', $db_values_to_check);

		$db_values_to_check = array(
			'prop_id' => $prop_id,
			'option_name' => $option_name,
			'monthly_cost_raw' => $monthly_budget,
			'monthly_percent_discount' => 0,
			'monthly_cost' => $monthly_budget,
			'cost_by_campaign' => 0
		);
		$I->seeInDatabase('prop_gen_option_prop_join', $db_values_to_check);
	}
}