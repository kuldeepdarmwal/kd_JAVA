<?php
if ( ! defined('BASEPATH')) define('BASEPATH', 1);
require './application/helpers/encrypt_decrypt_helper.php';

class encrypt_decrypt_Test extends PHPUnit_Framework_TestCase
{        
        public function test_decrypt_id()
        {
                $encrypt_id = "1639400jmk852700";
                $actual_id = 100;

                $res = decrypt_id($encrypt_id);

                $this->assertEquals($actual_id, $res);
        }	
}

?>