<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Crtv extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->helper('form');
		$this->load->helper('url');
		$this->load->library('tank_auth');
		$this->load->library('session');
		$this->load->library('form_validation');
		$this->load->library('vl_platform');
		$this->load->model('al_model');
		$this->load->model('cup_model');
		$this->load->model('variables_model');
		$this->load->model('spec_ad_model');
		$this->load->model('geo_in_ads_model');
	}

	function get_spec_ad($encoded_data)
	{
		$cup_version = false;

		$is_spec_ad = true;
		$decoded_data = $this->spec_ad_model->decode_ad_set_data_from_url($encoded_data);
		$data_version = $decoded_data['ver'];
		if($data_version == 1)
		{
			$cup_version = $decoded_data['id'];
		}

		$this->get_adset_decoded($cup_version, $is_spec_ad);
	}

	function get_adset($version_encoded)
	{
		$version = base64_decode(base64_decode(base64_decode($version_encoded)));
		$is_spec_ad = false;
		$this->get_adset_decoded($version, $is_spec_ad);
	}

	function get_gallery_adset($version_encoded)
	{
		$version = base64_decode(base64_decode(base64_decode($version_encoded)));
		$is_spec_ad = false;
		$is_show_landing_page = FALSE;
		$cname_trumps_adset_partner_id = true;
		$this->get_adset_decoded($version, $is_spec_ad, $is_show_landing_page, $cname_trumps_adset_partner_id);
	}

	private function get_adset_decoded($version, $is_spec_ad = false, $is_show_landing_page = true, $cname_trumps_adset_partner_id = false)
	{
		//echo $version;
		$data['cname_trumps_adset_partner_id'] = $cname_trumps_adset_partner_id;
		$data['is_show_landing_page'] = $is_show_landing_page;
		if($version != '0' and $version != 'new' and $version !== false)
		{
			if ($this->tank_auth->is_logged_in())
			{
				$username = $this->tank_auth->get_username();
				$role = $this->tank_auth->get_role($username);
				if ($role == 'ops' or $role == 'admin')
				{
					$data['show_for_io_checkbox'] = true;
					$data['modified_base_url'] = 'frequence.'.$this->tank_auth->get_domain_without_cname().'/';
				}
				elseif($role == 'creative')
				{
					$data['show_adset_versions'] = true;
					$data['modified_base_url'] = 'frequence.'.$this->tank_auth->get_domain_without_cname().'/';
				}
				else
				{
					$data['modified_base_url'] = base_url();
				}
			}
			else
			{
				if(isset($_COOKIE[constant('ENVIRONMENT')."-gp-approve"]))
				{
					$approval_cookie_value = $_COOKIE[constant('ENVIRONMENT')."-gp-approve"];

					if($approval_cookie_value == constant('ENVIRONMENT')."_ADMIN" || $approval_cookie_value == constant('ENVIRONMENT')."_OPS")
					{
						$data['show_for_io_checkbox'] = true;
						$data['modified_base_url'] = 'frequence.'.$this->tank_auth->get_domain_without_cname().'/';
					}
					elseif($approval_cookie_value == constant('ENVIRONMENT')."_CREATIVE")
					{
						$data['show_adset_versions'] = true;
						$data['modified_base_url'] = 'frequence.'.$this->tank_auth->get_domain_without_cname().'/';
					}
					else
					{
						$data['modified_base_url'] = base_url();
					}
				}
			}
			
			$data['version_id'] = $version;
			$data['adset_id'] = $this->cup_model->get_adset_by_version_id($version);
			$adset_array = $this->variables_model->get_adset_by_id($version);
			$assets_320x50 = $this->cup_model->get_assets_by_adset($data['version_id'], '320x50');
			$builder_version = $this->cup_model->get_builder_version_by_version_id($version);
			$data['show_mobile'] = $this->cup_model->files_ok($assets_320x50, '320x50', $builder_version, true, false, true);
			if(is_numeric($data['adset_id']))
			{
				$tags = array();
				foreach($this->cup_model->get_tag_strings_for_adset($data['adset_id']) as $temp)
				{
					array_push($tags, $temp['tag_code']);
				}
				$data['campaign'] = $this->al_model->get_campaign_details_by_version_id($data['version_id']);
				$data['landing_page'] = $this->al_model->get_adset_landing_page_by_version_id($data['version_id']);
				
				$data['tags'] = $tags;
				$data['shaved_labels'] = true;
				$data['title'] = 'Adset Preview';
				$data['is_partner'] = false;
				$data['is_gallery_page'] = true;
				$partner_header_response = $this->cup_model->get_partner_header_img($version);
				if($partner_header_response)
				{
					$data['partner_header_img'] = $partner_header_response['header_img'];
					if($partner_header_response['id'] != '1')
					{
						$data['is_partner'] = true;
					}
				}

				if($is_spec_ad)
				{
					$data['partner_header_img'] = $partner_header_response['header_img'];
				}

				// Partner info for spectrumdemo
				$partner_data = $this->tank_auth->get_partner_info_by_sub_domain();
				$data['partner_name'] = $partner_data ? $partner_data['cname'] : false;
				
				$data['feature_html_head_view_path'] = 'approval_pages/approval_page_header';
				$data['ignore_footer_copyright'] = true;
				$data['feature_js_view_path'] = 'approval_pages/approval_page_feature_js';
				$this->vl_platform->get_master_header_data($data, "gallery");
				$this->load->view('vl_platform_2/ui_core/common_master_header', $data);
				$is_html5_ad = html5_creative_set::is_html5_builder_version($adset_array[0]['builder_version']);
				if($is_spec_ad)
				{
					$this->load->view('approval_pages/spec_ad',$data);
				}
				elseif ( $adset_array[0]['builder_version'] > 699 || $adset_array[0]['builder_version'] < 206 || $is_html5_ad) {
					$this->load->view('approval_pages/approval_page_view',$data);					
				} else {
					$this->load->view('approval_pages/approval_page_view_swipe',$data);
				}
				$this->load->view('vl_platform_2/ui_core/common_footer', $data);
				$this->load->view('approval_pages/approval_page_footer',$data);
			}
		}
	}

	public function get_ad($version, $creative_size)
	{
		//copy of the ad function, private to creative_uploader
		if(($creative_size !='') && ($version !=''))
		{
			$assets = $this->cup_model->get_assets_by_adset($version, $creative_size);
			$data = $this->cup_model->prep_file_links($assets,$creative_size);
			$adset_array = $this->variables_model->get_adset_by_id($version);
			$data['variables_data_obj'] = json_encode($adset_array[0]['variables_data']);
			if(!is_null($adset_array[0]['builder_version'])){//if this adset doesn't have a builder version - don't set the variables data type
				$versions_array = $this->variables_model->get_versions_from_builder_version($adset_array[0]['builder_version']);
				$blob_version = $versions_array[0]['blob_version'];
				$data['gpa_version'] = $versions_array[0]['rich_grandpa_version'];
			}else{
				$blob_version = '004';
			}
			$vl_creative_record = $this->cup_model->get_creative_details($creative_size, $version);
			$data['vl_creative_id'] = $vl_creative_record[0]['id'];
			$data['tracking_off'] = true;
			$data['no_engage'] = true;

			$data['landing_page'] = $this->al_model->get_adset_landing_page_by_version_id($version);

			if(html5_creative_set::is_html5_builder_version($adset_array[0]['builder_version']))
			{
				$html5_creative_set = new html5_creative_set($this, $assets, $creative_size, $adset_array[0]['builder_version']);
				$creative_markup = $html5_creative_set->get_creative_markup_for_size($creative_size);
				$data['html5_initialization'] = $creative_markup['initialization'];
				$data['html5_setup'] = $creative_markup['setup'];
			}
			
			$campaign_and_advertiser_details = $this->cup_model->get_campaign_and_advertiser_by_version_id($version);
			if ($campaign_and_advertiser_details != NULL)
			{
				$data['campaign_id'] = $campaign_and_advertiser_details['campaign_id'];
				$data['advertiser_id'] = $campaign_and_advertiser_details['advertiser_id'];
			}
			
			if(constant('ENVIRONMENT') !== 'production')
			{
				$this->cup_model->get_dev_tracking_pixel_addresses($data);
			}
			if(isset($data['variables_js']) and $data['variables_js'] != '')
			{
				$data['variables_js'] = json_encode(file_get_contents($data['variables_js']));
			}
			$builder_version = $this->cup_model->get_builder_version_by_version_id($version);
			if($this->cup_model->files_ok($assets, NULL, $builder_version, true))
			{
				if(isset($version) && $version != '' && $version != '0' && strpos($data['variables_data_obj'],"dynamicGeoDefault"))
				{
					$data['dynamic_geo_default'] = TRUE;
					$messages_data = $this->geo_in_ads_model->get_messages_data_for_adset_version($version);
					
					if ($messages_data)
					{
						$data['messages_data'] = $messages_data;
					}
				}
				
				echo '
					<!DOCTYPE html>
					<html>
					<head>
				';
				$this->load->view('dfa/vl_hd_view_'.$blob_version,$data);
				echo '
					</head>
					<body>
					</body>
					</html>
				';
			}
		}
	}


  


}
