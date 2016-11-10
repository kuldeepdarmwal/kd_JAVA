<?php

class CreativeUploaderPage
{
    // include url of current page
    public static $URL = '/creative_uploader';

    /**
     * Declare UI map for this page here. CSS or XPath allowed.
     * public static $usernameField = '#username';
     * public static $formSubmitButton = "#mainForm input[type=submit]";
     */

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
    protected $acceptance_tester;

    public function __construct(AcceptanceTester $I)
    {
        $this->acceptance_tester = $I;
    }

    /**
     * @return CreativeUploaderPage
     */
    public static function of(AcceptanceTester $I)
    {
        return new static($I);
    }
}
