<?php
require 'Calculator.php';

class CalculatorTests extends PHPUnit_Framework_TestCase
{
    private $calculator;

    protected function setUp()
    {
        $this->calculator = new Calculator();
    }

    protected function tearDown()
    {
        $this->calculator = NULL;
    }

    public function testAdd()
    {
        $result = $this->calculator->add(1, 2);
        $this->assertEquals(3, $result);
    }

    public function testSub()
    {
        $result = $this->calculator->sub(8, 2);
        $this->assertEquals(6, $result);
    }

    public function testMulti()
    {
        $result = $this->calculator->multi(10, 5);
        $this->assertEquals(50, $result);
    }


}





