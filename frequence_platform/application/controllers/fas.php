<?php
class Fas  extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('form', 'url','multi_upload'));
		$this->load->helper('ad_server_type');
		$this->load->helper('url');
		$this->load->helper('vl_ajax_helper');
		$this->load->library('tank_auth');
		$this->load->model('fas_model');
		$this->load->model('cup_model');
	}
	
	public function publish_creative()
	{
		if ($this->tank_auth->is_logged_in())
		{
			$allowed_user_types = array('admin','ops','creative');
			$required_post_variables = array('cr_size','version','vl_c_id', 'landing_page');
			$ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables);
			if ($ajax_verify['is_success'])
			{
				$creative_size = $ajax_verify['post']['cr_size'];
				$version_id = $ajax_verify['post']['version'];
				$vl_campaign_id = $ajax_verify['post']['vl_c_id'];
				$landing_page = $ajax_verify['post']['landing_page'];
				$assets = $this->cup_model->get_assets_by_adset($version_id, $creative_size);
				$builder_version = $this->cup_model->get_builder_version_by_version_id($version_id);
				if ($this->cup_model->files_ok($assets, $creative_size, $builder_version, FALSE, FALSE, TRUE))
				{
					$creative = $this->cup_model->prep_file_links($assets,$creative_size, $vl_campaign_id);
					$fas_push_success = $this->fas_model->push_fas_creatives($creative, $creative_size, $version_id, FALSE, $landing_page);
					if ($fas_push_success['is_success'])
					{
						$this->cup_model->mark_version_published_time($version_id);
						echo '<span class="label label-success"><i class="icon-thumbs-up icon-white"></i>SUCCESS</span>';
					}
					else
					{
						echo '<span class="label-warning" title="'.htmlentities($fas_push_success['err_msg']).'"><i class="icon-thumbs-down icon-white"></i>FAIL</span>';
						return;
					}
				}
				else
				{
					echo '<span class="label-warning" title="File check failed for size ' . $creative_size . '"><i class="icon-thumbs-down icon-white"></i>FAIL</span>';
				}
			}
		}
	}	
}