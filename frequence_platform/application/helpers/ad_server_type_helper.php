<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if(!class_exists('k_ad_server_type'))
{
	abstract class k_ad_server_type
	{
		const all_string = 'all';
		const dfa_string = 'dfa';
		const adtech_string = 'adtech';
		const fas_string = 'fas';
		
		const all_id = -1;
		const dfa_id = 1; 		// corresponds to database table `cup_creatives` field `published_ad_server`
		const adtech_id = 2;	// corresponds to database table `cup_creatives` field `published_ad_server`
		const fas_id = 3;
		
		const unknown = -2;

		const database_min_value = self::dfa_id;
		const database_max_value = self::fas_id;

		public static function is_valid_for_database($id)
		{
			$is_valid = false;
			if($id >= self::database_min_value && $id <= self::database_max_value)
			{
				$is_valid = true;
			}

			return $is_valid;
		}

		public static function resolve_string_to_id($string, $allow_all = false)
		{
			$result = self::unknown;
			switch($string)
			{
				case self::dfa_string:
					$result = self::dfa_id;
					break;
				case self::fas_string:
					$result = self::fas_id;
					break;
				case self::adtech_string:
					$result = self::adtech_id;
					break;
				case self::all_string:
					if($allow_all == true)
					{
						$result = self::all_id;
					}
					else
					{
						$result = self::unknown;
					}
					break;
				default:
					$result = self::unknown;
			}

			return $result;
		}
	}
}

