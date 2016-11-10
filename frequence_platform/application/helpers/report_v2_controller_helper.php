<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class report_ajax_input
{
	public $error_info = array();
	public $is_success = false;

	public $action = null;
	public $advertiser_id = null;
	public $campaign_set = null;
	public $request_id = null;

	public $start_date = null;
	public $end_date = null;

	public $active_product = null;
	public $active_subproduct = null;
}

class report_table_starting_column_sort
{
	public $column_index = null; // Zeor based index. Converted to ones based for MySQL.
	public $column_order = null; // Values of 'asc' or 'desc'.  Used in MySQL and DataTables.js .

	public function __construct(
		$column_index,
		$column_order
	)
	{
		$this->column_index = $column_index;
		$this->column_order = $column_order;
	}
}

class report_table_data
{
	public $header = null;
	public $rows = null;
	public $starting_sorts = null;
	public $row_javascript_click_function_name = null;
	public $caption = null;
	public $table_options = null;
}

class report_table_header
{
	public $cells = array();
}

class report_table_header_cell
{
	public $html_content;
	public $css_classes;
	public $table_colmun_type;
}

class report_table_column_format
{
	public $css_class;
	public $initial_sort_order;
	public $format_function;
	public $orderable;
	public $searchable;
	public $visible;
	public $csv_exportable;

	public function __construct(
		$css_class,
		$initial_sort_order,
		$format_function,
		$orderable = true,
		$searchable = true,
		$visible = true,
		$csv_exportable = null
	)
	{
		$this->css_class = $css_class;
		$this->initial_sort_order = $initial_sort_order;
		$this->format_function = $format_function;
		$this->orderable = $orderable;
		$this->searchable = $searchable;
		$this->visible = $visible;
		$this->csv_exportable = $csv_exportable;
	}
}

class report_table_row
{
	public $cells = array();
	public $hidden_data = null;
}

class report_table_cell
{
	public $html_content = "";
	public $css_classes = "";
	public $link = null;
}

abstract class k_build_table_type
{
	const html = 0;
	const csv = 1;
}

