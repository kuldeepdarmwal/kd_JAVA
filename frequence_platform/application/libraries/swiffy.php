<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Methods for moving and altering Swiffy creative files.
 *
 * @package swiffy
 */
class swiffy
{

	function __construct()
	{
	}

	/**
	 * Get a list of files from the specified S3 bucket / prefix.
	 *
	 * @param string $bucket - name of the S3 bucket
	 * @param string $prefix - name of the prefix/subfolder
	 * @return object - ResourceIteratorInterface (AWS)
	 */
	public function get_js_from_swiffy_html($html, $id_prefix = 'swiffycontainer-', $options = [])
	{
		$swiffyobject_pattern = '/swiffyobject\s*=/';
		$swiffyobject_line = preg_grep($swiffyobject_pattern, explode("\n", $html));

		if(!empty($swiffyobject_line))
		{
			// The first element in the array may not be at index 0. It might be at 8.
			$swiffyobject_js = html_entity_decode(trim(array_shift($swiffyobject_line)));
			$swiffyobject_js .= "\nwindow['swiffy_{$id_prefix}'+clientID] = new swiffy.Stage(document.getElementById('{$id_prefix}'+clientID),swiffyobject);";
			if(isset($options['transparent']) && $options['transparent'])
			{
				$swiffyobject_js .= "\nwindow['swiffy_{$id_prefix}'+clientID].setBackground(null);";
			}
			$swiffyobject_js .= "\nwindow['swiffy_{$id_prefix}'+clientID].start();";
			return $swiffyobject_js;
		}

		return FALSE;
	}

}
