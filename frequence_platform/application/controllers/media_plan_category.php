<?php

class Media_plan_category extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('category_model');
	}
	
	function form($prepopulated_category_string = '')
	{
            $data['unique_site_categories'] = $this->category_model->get_all_categories("media_plan_channel_sites");

            $data['prepopulated_category'] = urldecode($prepopulated_category_string);
            
            $this->load->view('categories/all_categories_view',$data);
	}
	function get_sites($categories_string)
	{   
            
            $the_category_database = "media_plan_channel_sites";
            $categories = explode('|',urldecode($categories_string));
            $sitelist_sql_result = $this->category_model->get_sites_query($categories,$the_category_database);
            
            $this->load->library('table');
            $this->table->set_heading('Domain', 'Reach','Channel');
            
            $data['sites'] = $this->table->generate($sitelist_sql_result);
            $this->load->view('categories/sites_view',$data);
        }
        function mtv(){
            $data['php_variable_name'] = '';
            $this->load->view('categories/media_targeting_UI_view',$data);
        }
	
}
?>
