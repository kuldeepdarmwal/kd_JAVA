<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

final class k_impressions_clicks_data_set
{
	const pre_roll_only = "pre_roll_only";
	const non_pre_roll = "non_pre_roll";
	const all = "all";

	private function __construct() { } // disable class instantiation
}

class report_campaign_drop_down_item
{
	public $name;
	public $value;

	public function __construct(
		$name,
		$vale
	)
	{
		$this->name = $name;
		$this->value = $value;
	}
}

abstract class report_product_tab_html_id
{
	const overview_product = 'overview_product';
	const display_product = 'display_product';
	const pre_roll_product = 'pre_roll_product';
	const targeted_clicks_product = 'targeted_clicks_product';
	const targeted_inventory_product = 'targeted_inventory_product';
	const targeted_directories_product = 'targeted_directories_product';
	const targeted_content_product = 'targeted_content_product';
	const targeted_tv_product = 'targeted_tv_product';
	const overview_lift = 'overview_lift';
}

abstract class frequence_product_names
{
	const display = 'Display';
	const pre_roll = 'Pre-Roll';

	const visual_spectrum_display = 'Display';
	const visual_frequence_display = 'Display';
}

abstract class tmpi_product_names
{
	const clicks = 'Visits';
	const inventory = 'Leads';
	const directories = 'Directories';
}

abstract class spectrum_product_names
{
	const tv = 'Television';
}

abstract class carmercial_product_names
{
	const content = 'Content';
}

abstract class report_organization_names
{
	const frequence = 'brandcdn';
	const tmpi = 'tmpi';
	const carmercial = 'carmercial';
	const spectrum = 'spectrum';
}

// Values also used in Database `tp_advertisers_join_third_party_account`.`third_party_source` field.
abstract class report_campaign_organization
{
	const frequence = 0;
	const tmpi = 1;
	const carmercial = 2;
	const spectrum = 3;
}

abstract class report_digital_overview_type
{
	const targeted_display = 1;
	const targeted_pre_roll = 2;
	const targeted_clicks = 3;
	const targeted_inventory = 4;
	const targeted_directories = 5;
	const targeted_content = 6;

	public static function get_account_type_friendly_name($overview_type_id, $is_tmpi_accessible)
	{
		switch($overview_type_id)
		{
			case 1:
				if($is_tmpi_accessible)
				{
					return "Display";
				}
				else
				{
					return "Display";
				}
			case 2:
				if($is_tmpi_accessible)
				{
					return "Preroll";
				}
				else
				{
					return "Preroll";
				}
			case 3:
				if($is_tmpi_accessible)
				{
					return "Visits";
				}
				else
				{
					return "Visits";
				}
			case 4:
				return "Leads";
			case 5:
				return "Directories";
			case 6:
				return "Content";
			default:
				//Someone messed up
				return "Misc";
		}
	}
}

abstract class report_campaign_type
{
	const frequence_display = 0;
	const frequence_pre_roll = 1;

	const tmpi_clicks = 0;
	const tmpi_inventory = 1;
	const tmpi_directories = 2;

	const carmercial_content = 0;

	const spectrum_tv = 0;
}

abstract class tmpi_clicks_data_set_type
{
	const summary = 0;
	const geo = 1;
	const date_raw_data = 2;
}

abstract class tmpi_clicks_campaign_type
{
	const clicks = 0;
	const smart_ads = 1;
}

class report_campaign
{
	public $organization;
	public $type;
	public $id;
	public $name;

	public function __construct(
		$organization_or_value,
		$type = null,
		$id = null,
		$name = null
	)
	{
		if($id === null && $type === null)
		{
			$campaign_content = $this->decode_report_campaign_value($organization_or_value);

			$this->id = (int)$campaign_content['id'];
			$this->organization = $campaign_content['organization'];
			$this->type = $campaign_content['type'];
			$this->name = null;
		}
		else
		{
			$this->id = (int)$id;
			$this->organization = self::$map_campaign_group_name_to_number[$organization_or_value];
			$this->type = self::$map_campaign_group_and_type_name_to_number[$this->organization][$type];
			$this->name = $name;
		}
	}

	public function get_html_content($is_spectrum)
	{
		$type_name = self::$map_campaign_group_and_type_number_to_name[$this->organization][$this->type];
		if($is_spectrum && $type_name === frequence_product_names::display)
		{
			$type_name = frequence_product_names::visual_spectrum_display;
		}
		return $type_name. ": " . $this->name;
	}

	public function get_html_value()
	{
		return $this->encode_report_campaign_value(
			$this->organization,
			$this->type,
			$this->id
		);
	}

	private function encode_report_campaign_value(
		$campaign_group,
		$campaign_type,
		$campaign_id
	)
	{
		$value =
			$campaign_group . self::$separator .
			$campaign_type . self::$separator .
			$campaign_id;

		return $value;
	}

	private function decode_report_campaign_value(
		$campaign_value
	)
	{
		$content = explode(self::$separator, $campaign_value);
		$num_found = count($content);
		$num_looking_for = 3;
		if($num_found != $num_looking_for)
		{
			throw new Exception("Unexpected number of items in campaign_value. Found: $num_found, looking for $num_looking_for.  Value: $campaign_value");
		}

		return array(
			'organization' => $content[0],
			'type' => $content[1],
			'id' => $content[2]
		);
	}

	public static function init_maps()
	{
		self::$map_campaign_group_number_to_name = array_flip(self::$map_campaign_group_name_to_number);
		self::$map_campaign_group_and_type_number_to_name = array();
		foreach(self::$map_campaign_group_and_type_name_to_number as $index => &$type_name_to_number)
		{
			self::$map_campaign_group_and_type_number_to_name[$index] = array_flip($type_name_to_number);
		}
	}

	private static $map_campaign_group_name_to_number = array(
		report_organization_names::frequence => report_campaign_organization::frequence,
		report_organization_names::tmpi => report_campaign_organization::tmpi,
		report_organization_names::carmercial => report_campaign_organization::carmercial,
		report_organization_names::spectrum => report_campaign_organization::spectrum
	);
	private static $map_campaign_group_number_to_name = null;

	private static $map_campaign_group_and_type_name_to_number = array(
		report_campaign_organization::frequence => array(
			frequence_product_names::display => report_campaign_type::frequence_display,
			frequence_product_names::pre_roll => report_campaign_type::frequence_pre_roll
		),
		report_campaign_organization::tmpi => array(
			tmpi_product_names::clicks => report_campaign_type::tmpi_clicks,
			tmpi_product_names::inventory => report_campaign_type::tmpi_inventory,
			tmpi_product_names::directories => report_campaign_type::tmpi_directories
		),
		report_campaign_organization::carmercial => array(
			carmercial_product_names::content => report_campaign_type::carmercial_content
		),
		report_campaign_organization::spectrum => array(
			spectrum_product_names::tv => report_campaign_type::spectrum_tv
		)
	);
	private static $map_campaign_group_and_type_number_to_name = null;

	private static $separator = ':;';
}

class report_campaign_set
{
	private $campaign_id_tree = array(
		report_campaign_organization::frequence => array(
			report_campaign_type::frequence_display => array(),
			report_campaign_type::frequence_pre_roll => array()
		),
		report_campaign_organization::tmpi => array(
			report_campaign_type::tmpi_clicks => array(),
			report_campaign_type::tmpi_inventory => array(),
			report_campaign_type::tmpi_directories => array()
		),
		report_campaign_organization::carmercial => array(
			report_campaign_type::carmercial_content => array()
		),
		report_campaign_organization::spectrum => array(
			report_campaign_type::spectrum_tv => array()
		)
	);

	private $campaigns = array();

	public function __construct(
		array $report_campaigns
	)
	{
		if(!empty($report_campaigns))
		{
			if(gettype($report_campaigns[0]) === "string")
			{
				$created_report_campaigns = array_map(
					function($campaign_string)
					{
						return new report_campaign($campaign_string);
					},
					$report_campaigns
				);

				$this->campaigns = $created_report_campaigns;
			}
			else
			{
				$this->campaigns = $report_campaigns;
			}

			foreach($this->campaigns as $campaign)
			{
				$this->campaign_id_tree[$campaign->organization][$campaign->type][] = $campaign->id;
			}
		}
	}

	public function clear_ids_not_in_organization_and_product($keep_organization, $keep_product)
	{
		foreach($this->campaign_id_tree as $organization => &$products)
		{
			if($keep_organization === $organization)
			{
				if($keep_product !== null)
				{
					foreach($products as $product => &$campaign_ids)
					{
						if($keep_product !== $product)
						{
							$campaign_ids = array();
						}
					}
				}
			}
			else
			{
				foreach($products as $product => &$campaign_ids)
				{
					$campaign_ids = array();
				}
			}
		}
	}

	public function get_campaigns_for_html($subfeature_access_permissions)
	{
		$html_campaigns = array();

		$is_tmpi_accessible = $subfeature_access_permissions['is_tmpi_accessible'];
		// error_log(print_r($this->campaigns, true)); // Commented out because not sure why it was added - MC 20160506
		foreach($this->campaigns as $campaign)
		{
			if($is_tmpi_accessible ||
				($campaign->organization != report_campaign_organization::tmpi &&
					$campaign->organization != report_campaign_organization::carmercial
				)
			)
			{
				$html_campaigns[] = array(
					'value' => $campaign->get_html_value(),
					'content'=> $campaign->get_html_content($is_tmpi_accessible)
				);
			}
		}

		return $html_campaigns;
	}

	public function get_organziation_campaign_id_sets(
		$organization
	)
	{
		return $this->campaign_id_tree[$organization];
	}

	public function get_campaign_ids(
		$organization = null,
		$type = null
	)
	{
		if($organization === null)
		{
			$campaign_ids = array();

			foreach($this->campaign_id_tree as &$organization_ids)
			{
				foreach($organization_ids as &$type_ids)
				{
					$campaign_ids = array_merge($campaign_ids, $type_ids);
				}
			}

			return $campaign_ids;
		}
		else
		{
			if($type === null)
			{
				$organization_ids =& $this->campaign_id_tree[$organization];
				$campaign_ids = array();
				foreach($organization_ids as &$type_ids)
				{
					$campaign_ids = array_merge($campaign_ids, $type_ids);
				}

				return $campaign_ids;
			}
			else
			{
				return $this->campaign_id_tree[$organization][$type];
			}
		}
	}
}


