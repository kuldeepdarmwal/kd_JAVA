<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * The constants acts as encryption keys
 */
define("PREFIX_KEY", 16394);
define("SUFFIX_KEY", 8527);

/**
* Encodes the given id into query string format
* @param int $id  
* @return string encrypted string 
*/
function encrypt_id($id)
{
        // Generate random chars of length 3 by default
        $random_chars = generate_random_string();

        // Create encrypted string
        $encrypted_id = (PREFIX_KEY * $id) .$random_chars. (SUFFIX_KEY * $id);

        return $encrypted_id;
}

/**
* Decodes the given query string format into id
* @param string $encrypted_string 
* @return int $id 
*/
function decrypt_id($encrypted_string)
{
        // Remove random chars
        $numbers = remove_random_string($encrypted_string);
        $id1 = $numbers[0]/PREFIX_KEY;
        $id2 = $numbers[1]/SUFFIX_KEY;   

        if ($id1 == $id2)
        {
                return $id1;
        }

        return false;
}

function generate_random_string($length = 3) 
{
        $str = "";
        $characters = range('a', 'z');
        $max = count($characters) - 1;
        for ($i = 0; $i < $length; $i++) 
        {
                $rand = mt_rand(0, $max);
                $str .= $characters[$rand];
        }
        return $str;
}

function remove_random_string($string, $length = 3) 
{
        $string_without_chars = preg_replace("/[a-z]/i", '$', $string);

        $common_string = '';
        for ($i = 0; $i < $length; $i++) 
        {        
                $common_string .= '$';
        }

        $numbers_array = explode($common_string, $string_without_chars);

        return $numbers_array;
}

?>