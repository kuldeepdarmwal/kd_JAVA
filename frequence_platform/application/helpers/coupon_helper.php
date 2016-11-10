<?php  

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Coupon Helper
 * 
 * Very site-specefic helper to help in generation of random coupon codes
 * at the time of campaign creation
 *  
 */

if ( !function_exists('coupon_code')){
    
    // this function takes id of a coupon code to
    // determine it's start offset
    //pattern xxxx.x(.)xxx
    // four characters random alphanumeric  / 1 character random alphabet 
    // generate a single coupon code depending upons coupons already being alotted
    function coupon_code($id,$fixed_suffix='',$sep = ''){
        // id will be used to determine coupon code based on max value
     
        // get a four character alphanumeric set
        // get one random alphabet
        // determine first available from all alotted coupon codes 
        $CI =& get_instance(); //first get instance
        // load Campaign model class
        $CI->load->model('Campaign_model','campaign',TRUE);
        $max_coupon_count = $CI->campaign->get_coupons_count($id)  ;
        // if $fixed_suffix = 'next_available' then we could do smthin else
        return coupon_randomizer(4).strtoupper(coupon_randomizer(1,'alpha')).$sep.$fixed_suffix ;
    }
    
   /**
    * Generates a list of coupon for a Campaign
    * @param type $id campaign's id
    * @param type $sep optional separator 
    * @return array 
    */
   function coupon_codes($id,$sep = ''){

        $CI =& get_instance(); //first get instance
        // load Campaign model class
        $CI->load->model('Campaign_model','campaign',TRUE);
        $max_coupon_count = $CI->campaign->get_coupons_count($id)  ;
        $coupons = array();
        for($i=1;$i<=$max_coupon_count;$i++){
           array_push($coupons,
                   coupon_randomizer(4).strtoupper(coupon_randomizer(1,'alpha')).$sep.coupon_number_formatter($i) 
                   );
        }
        return $coupons ;
    }
    
    // prepares an associative array of coupon codes
    function coupon_codes_prepare($id,$coupon_codes){
        $campaign_coupons = array() ;
        foreach($coupon_codes as $coupon_code){
           array_push($campaign_coupons,
                   array(
                            'coupon_id' => $id ,
                            'coupon_use_code' => $coupon_code,
                       ) 
                   );
        } 
        return $campaign_coupons ;
    }
    
    
    /**
     *
     * @param int $val value to be left padded
     * @param str $pad_char character to be used for padding
     * @param int $pad_width actual boundary width
     * 
     * @return string formatted number string 
     */
    function coupon_number_formatter($val,$pad_char='0',$pad_width=3){
        $pad_length = $pad_width - strlen($val) ;
        $pad_txt = '';
        for($i=0;$i<$pad_length;$i++){
            $pad_txt .= $pad_char ;
        }
        return $pad_txt.$val ;
    }
    
    
    /**
     * Generates a random string of specified length .
     * 
     * @param int $str_length length of expected random string 
     * @param string $str_type type of expected random string 
     * 
     * @return string 
     */
    
    function coupon_randomizer($str_length,$str_type='alphanum'){
        
        $str_set = array(
            'num'=>'0123456789',
            'alpha'=>'abcdefghijklmnopqrstuvwxyz',
            'alphanum'=>'abcdefghijklmnopqrstuvwxyz0123456789'
        );
        if(!in_array($str_type, array_keys($str_set))){
            $str_type = 'alphanum' ;
        }
        
        // generate our cc pattern
        $string = '';
        for ($i = 0; $i < $str_length; $i++) {
            $string .= $str_set[$str_type][rand(0, strlen($str_set[$str_type]) - 1)];
        }

        return $string ;  
    }
}