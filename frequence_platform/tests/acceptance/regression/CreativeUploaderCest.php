<?php
namespace regression;
use \AcceptanceTester;

class CreativeUploaderCest
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
	 * @group creative_uploader
	 * @actor AcceptanceTester\UserSteps
	 */
	public function create_adset_and_depencies(AcceptanceTester $I)
	{
		$I->am('Test Admin User');
		$I->wantTo('create a new adset');

		$adset_name = TEST_PREFIX.'ADS '.time();

		$I->amOnSubdomain('secure' . SUBDOMAIN);
		$I->login('test_admin');

		$I->amOnPage(\CreativeUploaderPage::$URL);

		$this->create_adset($I, $adset_name);
		$I->see($adset_name, '#output_adset_name');

		// populate variables
		$this->switch_to_variables_tab($I);
		$variables = $this->populate_variables($I);
		$I->click('#super_save_button');
		$I->waitForElement('#output_creative_summary', 60);

		// test variables
		$I->reloadPage();
		$this->select_adset($I, $adset_name);
		$this->switch_to_variables_tab($I);
		$this->see_variables($I, $variables);

		// preview
		$I->makeScreenshot('creative_uploader_preview');
	}

	/**
	 * @param AcceptanceTester $I
	 * @param string $adset_name
	 */
	protected function create_adset(AcceptanceTester $I, $adset_name)
	{
		$I->wantTo("create adset called $adset_name");
		$this->select_from_select2_element($I, '#s2id_adset_select', '*New*');
		$I->seeElement('#new_adset_box');
		$I->fillField('#new_adset_box', $adset_name);
		$I->click('#adset_load_button');
		$I->waitForElement('#output_creative_summary', 60);
	}

	/**
	 * @param AcceptanceTester $I
	 * @param string $adset_name
	 */
	protected function select_adset(AcceptanceTester $I, $adset_name)
	{
		$I->wantTo("select adset $adset_name");
		$this->select_from_select2_element($I, '#s2id_adset_select', $adset_name);
		$I->waitForElement('#output_creative_summary', 60);
	}

	/**
	 * @param AcceptanceTester $I
	 */
	protected function switch_to_variables_tab(AcceptanceTester $I)
	{
		$I->wantTo('switch to Variables UI tab');
		$I->click('#variables_ui_tab a');
		$I->waitForElement('#ui_control_form dl dt', 60);
	}

	/**
	 * @param Acceptance
	 * FIXME: fields are "not currently interactable and may not be manipulated"Tester $I
	 */
	protected function populate_variables(AcceptanceTester $I)
	{
		$I->wantTo('populate builder variables');
		$I->waitForElement('#builder_version_select option', 60); // wait for the select list to be populated
		$I->appendField('#builder_version_select', '705');
		$I->waitForElement('#ui_control_form dl dt', 60);

		$variables = array(
			// VIDEO
			'videoURL' => 'https://www.youtube.com/watch?v=Rk_sAHh9s08',
			//-- Play Button Positions
			'playButton300_x' => '0',
			//-- Video Settings
			'videoWidth300' => '150',
			// MAP
			'isRichMediaMap' => 'true',
			// SOCIAL
			'useShareButtons' => 'true',
		);

		foreach($variables as $name => $value)
		{
			$css_selector = 'input[name="flashvars.' . $name . '"]';
			$this->expand_section_around_variable_field($I, $css_selector);
			if($value === 'true' || $value === 'false') // assume radio button input
			{
				$I->selectOption($css_selector, $value);
			}
			else // assume text input
			{
				$I->fillField($css_selector, $value);
			}
		}

		return $variables;
	}

	protected function expand_section_around_variable_field(AcceptanceTester $I, $css_selector)
	{
		// only expand collapsed parents (parent <dd> gets class "in" when expanded)
		$javascript_to_expand_variable_section = '(function(){'
				. 'var digUp = function(from)'
				. '{'
					. 'var header = $(from).closest("dd:not(.in)").prev("dt").find("a.accordion-toggle");'
					. 'if(header.length)'
					. '{'
						. 'digUp(header);' // depth-first traversal: click top-most header first
						. 'header.click();' // then click the closest header
					. '}'
				. '};'
				. 'digUp("' . str_replace('"', '\"', $css_selector) . '");'
			. '})();';
		$I->executeJS($javascript_to_expand_variable_section);
		$I->waitForElementVisible($css_selector);
	}

	/**
	 * @param AcceptanceTester $I
	 * @param array $variables
	 */
	protected function see_variables(AcceptanceTester $I, $variables)
	{
		foreach($variables as $name => $value)
		{
			$css_selector = 'input[name="flashvars.' . $name . '"]';
			$this->expand_section_around_variable_field($I, $css_selector);
			$I->seeInField($css_selector, $value);
		}
	}

	/*
	 * @param string $selector CSS selector of the Select2 wrapping div element
	 * @param string $value to select
	 * TODO: this should be a helper
	 */
	protected function select_from_select2_element(AcceptanceTester $I, $selector, $value)
	{
		$I->waitForElementVisible($selector, 30);
		$I->click($selector . ' .select2-choice .select2-arrow'); // expand select2
		$I->waitForElementVisible('#select2-drop', 10); // wait for it to be populated
		$I->fillField('#select2-drop .select2-input', $value); // begin searching for the value
		$I->waitForElementVisible('span.select2-match', 30); // wait for matches
		$I->click('span.select2-match'); // select the first match
	}
}
