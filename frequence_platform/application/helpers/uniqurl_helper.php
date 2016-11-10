<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Uniqueurl Helper
 * 
 * Very site-specefic helper to help in generation of unique url as per
 * coupon id
 */

if(!function_exists('uniq_url')){
    
    function  uniq_url($id){      
        
        return urlencode(base64_encode(urlencode(base64_encode($id))));
    }
    
    function parse_uniq_url($encoded_id){
        return base64_decode(urldecode(base64_decode(urldecode($encoded_id))));
    }
    

    
}
?>
