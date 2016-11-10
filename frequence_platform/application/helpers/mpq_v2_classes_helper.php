<?php

// used for making a single demographic ui element
class mpq_demographic_element_data
{
	public $visible_name = '';
	public $id_core_name = '';
	public $is_checked = false;
	
	public function __construct($name, $id, $is_checked = false)
	{
		$this->visible_name = $name;
		$this->id_core_name = $id;
		$this->is_checked = $is_checked;
	}

	private function get_element_id()
	{
		return $this->id_core_name.'_demographic';
	}

	public function write_element_id_name()
	{
		$element_id = $this->get_element_id();
		echo '\''.$element_id.'\'';
	}

	public function write_checkbox_element()
	{
		$element_id = $this->get_element_id();
		$checked_attribute = '';
		if($this->is_checked)
		{
			$checked_attribute = 'checked="checked"';
		}

		echo '
			<label class="checkbox">
				<input type="checkbox" id="'.$element_id.'" '.$checked_attribute.'>'.//' onclick="handle_demographics_change();">'.
				$this->visible_name.'
			</label>
		';
	}
}

// used for making a section of demographics for the ui
class mpq_demographic_section
{
	public $section_title = '';
	public $demographic_elements = array();

	public function __construct($section_title, array $demographic_elements)
	{
		$this->demographic_elements = $demographic_elements;
		$this->section_title = $section_title;
	}

	public function write_demographic_section()
	{
		switch ($this->section_title) {
			case "Household Annual Income":
				$div_id_string = "id=\"hhi_checkboxes\"";
				break;
			case "Education":
				$div_id_string = "id=\"ed_checkboxes\"";
				break;
			default:
				$div_id_string ="";
				break;
		}


		echo '
			<div class="span2 intro-chardin" '.$div_id_string.'>
				<h4 class="muted" >
					'.$this->section_title.'
				</h4>
		';
		foreach($this->demographic_elements as $demographic_element)
		{
			$demographic_element->write_checkbox_element();
		}
		echo '
			</div>
		';
	}
}

// used for making a single channel ui element
class mpq_channel_data
{
	public function __construct($visible_name, $id_core_name, $is_checked = false)
	{
		$this->visible_name = $visible_name;
		$this->id_core_name = $id_core_name;
		$this->is_checked = $is_checked;
	}
	public function write_channel_option()
	{
		if($this->visible_name != '')
		{
			//no longer using options to populate contextual select2
			//echo '<option value="'.$this->id_core_name.'">'.$this->visible_name.'</option>'. "\n";
		}
	}
	public function write_channel_checkbox()
	{
		$checked_attribute = '';
		if($this->is_checked)
		{
			$checked_attribute = 'checked="checked"';
		}

		echo '
			<label class="checkbox">
				<input type="checkbox" id="'.$this->id_core_name.'_channel" '.
					$checked_attribute.
					' class="channel_checkbox"'.
					//' onclick="handle_mpq_channel_change();"'.
					'>
				'.$this->visible_name.'
			</label>
		'."\n";
	}
	
	public $visible_name = '';
	public $id_core_name = '';
	public $is_checked = false;
}

class mpq_industry_data
{
	public function __construct($visible_name, $id_core_name, $is_checked = false)
	{
		$this->visible_name = $visible_name;
		$this->id_core_name = $id_core_name;
		$this->is_checked = $is_checked;
	}
	public function write_industry_option()
	{
		if($this->visible_name != '')
		{
			//no longer using options to populate contextual select2
			//echo '<option value="'.$this->id_core_name.'">'.$this->visible_name.'</option>'. "\n";
		}
	}
	public function write_industry_checkbox()
	{
		$checked_attribute = '';
		if($this->is_checked)
		{
			$checked_attribute = 'checked="checked"';
		}

		echo '
			<label class="checkbox">
				<input type="checkbox" id="'.$this->id_core_name.'_industry" '.
					$checked_attribute.
					' class="industry_checkbox"'.
					//' onclick="handle_mpq_channel_change();"'.
					'>
				'.$this->visible_name.'
			</label>
		'."\n";
	}
	
	public $visible_name = '';
	public $id_core_name = '';
	public $is_checked = false;
}

// used for making a section of channels for the ui
class mpq_channel_section
{
	public function __construct($channels)
	{
		$this->channels = $channels;
	}
	public function write_checkbox_channel_section()
 	{
		echo '<div class="span3">'."\n";
 		foreach($this->channels as $channel)
 		{
			$channel->write_channel_checkbox();
 		}
 		echo '</div>'."\n";
 	}
	public function write_select_channel_section()
	{
		echo '<div class="span12">'."\n";
		//echo '<select style="display:none; width:90%" multiple name="iab_contextual_multiselect" id="iab_contextual_multiselect">'."\n";
		echo '<input type="hidden" style=" width:90%;" id="iab_contextual_multiselect">'."\n";
		foreach($this->channels as $channel)
		{
			$channel->write_channel_option();
		}
		//echo '</select>'."\n";
		echo '</div>'."\n";
	}
	
	public $channels;
}

// used for making a section of channels for the ui
class mpq_industry_section
{
	public function __construct($industries)
	{
		$this->industries = $industries;
	}
	public function write_checkbox_industry_section()
 	{
		echo '<div class="span3">'."\n";
 		foreach($this->industries as $industry)
 		{
			$industry->write_industry_checkbox();
 		}
 		echo '</div>'."\n";
 	}
	public function write_select_industry_section()
	{
		echo '<div class="span12">'."\n";
		echo '<input type="hidden" style=" width:90%;" id="industry_select">'."\n";
		foreach($this->industries as $industry)
		{
			$industry->write_industry_option();
		}
		//echo '</select>'."\n";
		echo '</div>'."\n";
	}
	
	public $industries;
}

// used for getting option data from database and population default option data
class mpq_budget_option_get
{
	public $type;
	public $amount;
	public $term;
	public $duration;
	public $cpm;
	public $discount;
	public $list_order;

	public function __construct()
	{
	}
}

// used for saving mpq option to database
class mpq_budget_option_save
{
	//public $lap_id = 0;
	public $type;
	public $amount;
	public $term;
	public $duration;
	public $cpm;
	public $discount;
	public $order;

	private function __construct()
	{
	}

	private static function handle_possible_null($value)
	{
		if($value !== null && $value === '')
		{
			$value = null;
		}

		return $value;
	}

	public static function make_new_with_std_class(stdClass $option)
	{
		$is_success = false;
		$result = null;

		if(property_exists($option, 'type') &&
			property_exists($option, 'amount') &&
			property_exists($option, 'term') &&
			property_exists($option, 'duration') &&
			property_exists($option, 'order')
		)
		{
			$is_success = true;

			$cpm = property_exists($option, 'cpm') ? $option->cpm : null;
			$cpm = mpq_budget_option_save::handle_possible_null($cpm);
			$discount = property_exists($option, 'discount') ? $option->discount : null;
			$discount = mpq_budget_option_save::handle_possible_null($discount);

			$new_option = new mpq_budget_option_save();
			$new_option->type = $option->type;
			$new_option->amount = $option->amount;
			$new_option->term = $option->term;
			$new_option->duration = $option->duration;
			$new_option->cpm = $cpm;
			$new_option->discount = $discount;
			$new_option->order = $option->order;

			$result = $new_option;
		}
		else
		{
			$is_success = false;
			$error_message = 'Failed to create new option.  Incomplete option data. (#58741)';
			$result = $error_message;
		}

		return $result;
	}
}

// used for making option ui
class mpq_budget_option_row
{
	public $default_amount = 0;
	public $default_amount_term = '';
	public $default_duration = 0;
	public $default_cpm = null;
	public $default_discount = null;
	public $option_type = 'dollar';

	public $placeholder_cpm = null;
	public $placeholder_discount = null;
	public $summary_string = null;

	public function __construct(
		$default_amount,
		$default_amount_term,
		$default_duration,
		$option_type,
		$default_cpm = null,
		$default_discount = null,
		$placeholder_cpm = null,
		$placeholder_discount = null,
		$summary_string = ''
	)
	{
		$this->default_amount = $default_amount;
		$this->default_amount_term = $default_amount_term;
		$this->default_duration = $default_duration;
		$this->option_type = $option_type;

		$this->default_cpm = $default_cpm;
		$this->default_discount = $default_discount;
		$this->placeholder_cpm = $placeholder_cpm;
		$this->placeholder_discount = $placeholder_discount;
		$this->summary_string = $summary_string;
	}

	public function write_option_row_javascript_data()
	{
		global $g_mpq_user_role;
		global $g_mpq_user_is_logged_in;

		$row_data = '
			{
				option_amount: "'.$this->default_amount.'", 
				frequency_term: "'.$this->default_amount_term.'",
				term_duration: "'.$this->default_duration.'",
		';

		// TODO: fix - scott (2013-05-31)
		if($g_mpq_user_is_logged_in == true && $g_mpq_user_role == 'media_planner')
		{
			$row_data .= '
				term_cpm: "'.$this->default_cpm.'",
				placeholder_cpm: "'.$this->placeholder_cpm.'",
				option_discount: "'.$this->default_discount.'",
				placeholder_discount: "'.$this->placeholder_discount.'",
				summary_string: "'.$this->summary_string.'",
			';
		}

		$row_data .= '
				option_type: "'.$this->option_type.'"
			}
		';

		echo $row_data;
	}
}

// used for making ui for a set of options, the range
class mpq_options_budget_range
{
	public $range_string = '';
	public $default_options = array();

	public function __construct($range_string, $default_options)
	{
		$this->range_string = $range_string;
		$this->default_options = $default_options;
	}

	public function write_range_option_html($value)
	{
			echo '<option value="'.$value.'">'.
				$this->range_string.
				'</option>';
	}

	public function write_option_rows_javascript_data()
	{
		$last_option_index = count($this->default_options) - 1;
		
		echo 'new Array(';
		foreach($this->default_options as $ii=>$default_option)
		{
			$default_option->write_option_row_javascript_data();
			if($ii != $last_option_index)
			{
				echo ',';
			}
		}
		echo ')';
	}
}

?>
