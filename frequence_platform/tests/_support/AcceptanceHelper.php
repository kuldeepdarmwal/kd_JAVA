<?php
namespace Codeception\Module;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class AcceptanceHelper extends \Codeception\Module
{
    function seeFieldIsEmpty($value)
    {
        $this->assertTrue(empty($value));
    }

    function dontSeeFieldIsEmpty($value)
    {
        $this->assertFalse(empty($value));
    }
}
