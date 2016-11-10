<?php

class RegisterPage
{
    // include url of current page
	public static $URL = '/register';

	/**
	 * Declare UI map for this page here. CSS or XPath allowed.
	 * public static $usernameField = '#username';
	 * public static $formSubmitButton = "#mainForm input[type=submit]";
	 */

	public static $codeception_advertiser_name_secure = 'ZZ_TEST_CODECEPTION_ADVERTISER';
	public static $codeception_advertiser_name_wltest = 'ZZ_TEST_CODECEPTION_WL_ADVERTISER';

	public static $test_login_admin = 'TEST_admin';
	public static $test_login_wl_sales = 'TEST_p_sales_s';

	/**
	 * Basic route example for your current URL
	 * You can append any additional parameter to URL
	 * and use it in tests like: EditPage::route('/123-post');
	 */
	public static function route($param)
	{
		return static::$URL.$param;
	}

	/**
	 * @var AcceptanceTester;
	 */
	protected $acceptanceTester;

	public function __construct(AcceptanceTester $I)
	{
		$this->acceptanceTester = $I;
	}

	/**
	 * @return RegisterPage
	 */
	public static function of(AcceptanceTester $I)
	{
		return new static($I);
	}
}