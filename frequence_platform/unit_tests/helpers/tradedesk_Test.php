<?php
if ( ! defined('BASEPATH')) define('BASEPATH', 1);
require './application/helpers/tradedesk_helper.php';

class tradedesk_Test extends PHPUnit_Framework_TestCase
{        
        public function test_get_todays_date()
        {
                $res = get_todays_date();

                $t = time();
                $x = $t+date("Z", $t);
                $todays_date = strftime("%Y-%m-%dT00:00:00.0000+00:00", $x);

                $this->assertEquals($todays_date, $res);
        }
        
        public function test_get_num_broadcast_months()
        {
                $start_date = '2016-09-21';
                $end_date = '2016-10-21';

                $res = get_num_broadcast_months($start_date, $end_date);

                $this->assertEquals(2, $res);
        }	
}

?>