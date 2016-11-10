<?php  

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Export Helper
 * 
 * Helps exporting a array or result set of values
 *  
 */

if ( !function_exists('export_to_csv')){
    
   function export_to_csv( $data=array(),$cols=10,$file_name,$backup = false){
        $CI =& get_instance(); 
        $CI->load->helper('download') ;
        $i = 0 ;
        $out = '';

        for($i=0;$i<count($data);$i++){
            $out .='"'. $data[$i] . '",' ; 
            if(($i+1) % $cols == 0){
               $out .= "\n" ; 
            }
            
        }
        if($backup){
            $CI->load->helper('file') ;
            // check if directory exists / create / and save file
            $file_path = "./assets/exports/$file_name" ;
            if (!write_file($file_path, $out))
            {
               //
            }else{
               //
            }
        }
        //force_download($file_name,$out) ;   
   }
}