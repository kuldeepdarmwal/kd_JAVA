<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Ad_machina extends CI_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->load->helper(array(
			'mixpanel',
			'mailgun',
		));
		$this->load->model(array('cdn_model',
			'video_ad_model',
			'report_v2_model',
		));
		$this->load->library(array(
			'session',
			'tank_auth',
			'vl_aws_services',
			'vl_rackspace_cdn',
			'vl_platform',
			'csv',
		));
	}

	public function index()
	{
		$this->vl_platform->has_permission_to_view_page_otherwise_redirect('ad_machina_manager', 'vab');

		$user_id = $this->tank_auth->get_user_id();
		$user = $this->video_ad_model->get_user_info($user_id);
		$edit_redirect = $this->vl_platform->get_access_permission_redirect('ad_machina');
		$user_has_edit_permission = ($edit_redirect ? false : true);

		$advertisers = $this->video_ad_model->get_sample_ad_manager_table_data(
			$user->id,
			$user->role,
			$user->isGroupSuper,
			$user_has_edit_permission
		);

		// TODO: remove once `builder()` is accessed with video_ad_id
		foreach($advertisers as &$advertiser)
		{
			if(!empty($advertiser['spec_ads']))
			{
				foreach($advertiser['spec_ads'] as &$spec_ad)
				{
					$preview_key_parts = $this->video_ad_model->parse_video_ad_key($spec_ad['preview_key']);
					$spec_ad['preview_key_timestamp'] = $preview_key_parts['time'];
				}
			}
		}

		$data = array(
			'title' => 'Video Ad Manager',
			'has_edit_permission' => $user_has_edit_permission,
			'has_advertisers' => !empty($advertisers),
			'advertisers_json' => json_encode($advertisers),
			'mixpanel_info' => get_mixpanel_data_array($this),
		);

		$this->vl_platform->show_views(
			$this,
			$data,
			'ad_machina', // ID
			'ad_machina/index', // body
			'ad_machina/index_head', // head
			null, // sub head
			'ad_machina/index_js', // JS
			null // sub JS
		);

	}

	public function videos($frq_third_party_account_id = null, $segment = 0)
	{
		$this->vl_platform->has_permission_to_view_page_otherwise_redirect('ad_machina', 'vab/videos');

		// change empty string or '0' to null
		if(empty($frq_third_party_account_id) || $frq_third_party_account_id == 'all')
		{
			$frq_third_party_account_id = null;
		}

		$accounts = $this->get_accounts_by_current_user_access();
		$accessible_frq_third_party_account_ids = array_column($accounts, 'frq_third_party_account_id');
		if($frq_third_party_account_id !== null && !in_array($frq_third_party_account_id, $accessible_frq_third_party_account_ids))
		{
			redirect('vab');
		}
		$filter_frq_third_party_account_ids = $frq_third_party_account_id ? [$frq_third_party_account_id] : $accessible_frq_third_party_account_ids;
		$segment_size = 80;
		$offset = $segment * $segment_size;
		$videos = $this->video_ad_model->get_creatives_by_accounts($filter_frq_third_party_account_ids, $offset, $segment_size);

		$data = array(
			'title' => 'Video Ad: Videos',
			'frq_third_party_account_id' => $frq_third_party_account_id,
			'accounts' => $accounts,
			'videos' => $videos,
			'segment' => $segment,
			'segment_size' => $segment_size,
			'mixpanel_info' => get_mixpanel_data_array($this),
		);

		if($segment > 0)
		{
			$this->load->view('ad_machina/videos_list', $data);
		}
		else
		{
			$this->vl_platform->show_views(
				$this,
				$data,
				'ad_machina', // ID
				'ad_machina/videos', // body
				'ad_machina/videos_head', // head
				null, // sub head
				'ad_machina/videos_js', // JS
				null // sub JS
			);
		}

	}

	public function builder($video_source_id = null, $time = null)
	{
		$should_reload_with_new_time = false;

		if(empty($video_source_id))
		{
			$redirect = 'vab';
		}
		else
		{
			if(empty($time))
			{
				$time = time();
				$should_reload_with_new_time = true;
			}

			$referer = "vab/builder/$video_source_id/$time";
			if($redirect = $this->vl_platform->get_access_permission_redirect('ad_machina'))
			{
				if($redirect !== 'login')
				{
					$redirect = 'vab';
				}
			}
			else if($should_reload_with_new_time)
			{
				$redirect = $referer;
			}
		}

		if(!empty($redirect))
		{
			if($redirect === 'login')
			{
				$this->session->set_userdata('referer', $referer);
			}

			redirect(site_url($redirect));
		}

		$user_id = $this->tank_auth->get_user_id();
		$user = $this->video_ad_model->get_user_info($user_id);

		$creative = $this->video_ad_model->get_creative(
			$video_source_id,
			$user->id,
			$user->role,
			$user->isGroupSuper
		);
		if(empty($creative))
		{
			redirect('vab');
		}

		header('Cache-Control: no-cache, max-age=0, must-revalidate, no-store'); // always get this page fresh, even on back-button navigation

		$video_ad_key = $this->video_ad_model->create_video_ad_key($video_source_id, $time);

		$video_ad_data = $this->video_ad_model->get_video_ad_data(null, $video_ad_key);
		$video_ad_config_json = json_encode(null);
		$video_ad_template_id = null;
		if(!empty($video_ad_data))
		{
			$video_ad_config_json = $video_ad_data['config_json'];
			$video_ad_template_id = $video_ad_data['template_id'];
		}

		$builder_config = [
			"version" => "v0.3.0",
			"dur" => "0.3",
			"videos" => [
				[
					"thumbnail" => $creative['link_thumb'],
					"title" => 'video',
					"media" => [
						"mp4" => $creative['link_mp4'],
						"webm" => $creative['link_webm'],
						"poster" => $creative['link_thumb'],
					]
				]
			]
		];

		$presets_with_templates = $this->video_ad_model->get_active_presets_with_templates();

		// prepare templates for digestion by media-select format on builder.php
		$layouts = [];
		foreach($presets_with_templates as $preset)
		{
			$layouts[$preset['name']] = [
				'id' => $preset['template_id'],
				'thumbnail' => $preset['thumbnail_url'],
				'title' => $preset['name'],
				'media' => $preset['template'],
				'config_json' => $preset['config_json'],
			];
		}

		$button_objects = $this->vl_rackspace_cdn->get_file_list($this->config->config['spec_ad_rackspace_container'], $this->config->config['spec_ad_rackspace_path'].'/buttons/');
		$background_objects = $this->vl_rackspace_cdn->get_file_list($this->config->config['spec_ad_rackspace_container'], $this->config->config['spec_ad_rackspace_path'].'/backgrounds/');
		$logo_objects = $this->vl_rackspace_cdn->get_file_list($this->config->config['spec_ad_rackspace_container'], $this->config->config['spec_ad_rackspace_path'].'/logos/'.$video_ad_key);

		$button_urls = $this->vl_rackspace_cdn->get_cdn_urls($button_objects, true);
		$background_urls = $this->vl_rackspace_cdn->get_cdn_urls($background_objects, true);
		$logo_urls = $this->vl_rackspace_cdn->get_cdn_urls($logo_objects, true);

		$assets = array(
			'play_buttons' => array(),
			'backgrounds' => $background_urls,
			'logos' => $logo_urls,
		);

		foreach($button_urls as $url)
		{
			$assets['play_buttons'][] = $url;
		}

		$button_states = array('idle', 'hover');

		$this->sort_into_compound_assets($assets['play_buttons'], $button_states);
		$this->sort_into_compound_assets($assets['backgrounds'], null, true);
		$this->sort_into_compound_assets($assets['logos'], null);

		$mixpanel_info = get_mixpanel_data_array($this);

		$demo_title = !empty($video_ad_data['demo_title']) ? $video_ad_data['demo_title'] : $creative['advertiser_name'];

		$data = compact('builder_config', 'layouts', 'assets', 'video_ad_key', 'creative', 'mixpanel_info', 'video_ad_template_id', 'video_ad_config_json', 'demo_title');
		$data['title'] = 'Video Ad Builder';

		$this->vl_platform->show_views(
			$this,
			$data,
			'ad_machina', // ID
			'ad_machina/builder', // body
			'ad_machina/builder_head', // head
			null, // sub head
			'ad_machina/builder_js', // JS
			null // sub JS
		);
	}

	private function resolve_view_count_increment_and_email_sales($ad_data)
	{
		if($this->tank_auth->is_logged_in())
		{
			$roles_to_count = ['business', 'client'];
			$role = strtolower($this->tank_auth->get_role($this->tank_auth->get_username()));
			if(!in_array($role, $roles_to_count))
			{
				return false;
			}
		}

		$this->video_ad_model->increment_view_count($ad_data['id']);
		if($ad_data['view_count'] == 0) // $ad_data retains the former view_count
		{
			$sales_user = $this->video_ad_model->get_advertiser_and_sales_info_for_email($ad_data['advertiser_id']);
			if($sales_user)
			{
				$full_preview_url = 'http://' . $sales_user['sales_partner_cname'] . '.' . g_second_level_domain . $ad_data['preview_url'];

				$from = "noreply@{$sales_user['sales_partner_cname']}.brandcdn.com";
				$to = $sales_user['email'];
				$subject = "Video Ad Preview {$ad_data['demo_title']} for Advertiser {$sales_user['advertiser_name']} was viewed";
				$message = "Hello {$sales_user['firstname']} {$sales_user['lastname']},";
				$message .=	"\n\nYour Video Ad Preview \"{$ad_data['demo_title']}\" for Advertiser {$sales_user['advertiser_name']} was just viewed for the first time.";
				$message .=	"\n\nView the Sample Ad Demo here:\n{$full_preview_url}";
				$email_result = mailgun(
					$from,
					$to,
					$subject,
					$message,
					'text'
				);
			}
		}

		return true;
	}

	public function demo($video_ad_key = null)
	{
		$mixpanel_info = $this->tank_auth->is_logged_in() ? get_mixpanel_data_array($this) : null;
		$data = array(
			'video_ad_key' => $video_ad_key,
			'title' => 'Video Ad Preview',
			'demo_title' => null,
			'mixpanel_info' => $mixpanel_info,
		);

		if($video_ad_key)
		{
			if($ad_data = $this->video_ad_model->get_video_ad_data(null, $video_ad_key))
			{
				$data['video_ad_id'] = $ad_data['id'];
				$data['config_json'] = $ad_data['config_json'];
				$data['config_data'] = json_decode($ad_data['config_json'], true);
				$data['demo_title'] = $ad_data['demo_title'];
				if(!empty($ad_data['demo_title']))
				{
					$data['title'] = 'Video Ad: ' . $ad_data['demo_title'];
				}

				$this->resolve_view_count_increment_and_email_sales($ad_data);
			}
		}

		$this->vl_platform->show_views(
			$this, // controller
			$data, // template data
			'ad_machina', // ID
			'ad_machina/demo', // body
			'ad_machina/demo_head', // head
			null, // sub head
			'ad_machina/demo_js', // JS
			null // sub JS
		);
	}

	public function render($video_ad_id)
	{
		if($this->input->get_post('render_as_text') !== false)
		{
			header('Content-type: text/plain'); // show the source
		}
		if($ad_data = $this->video_ad_model->get_video_ad_data($video_ad_id))
		{
			$config_data = json_decode($ad_data['config_json'], true);
			if($this->input->get_post('preview') !== false)
			{
				unset($config_data['advertiser_url']);
			}
			$template_data = $this->video_ad_model->get_template($ad_data['template_id']);
			$data = [
				'config_json' => json_encode($config_data),
				'config_data' => $config_data,
				'template_json' => json_encode($template_data['template']),
			];

			$this->load->view('ad_machina/render', $data);
		}
		else
		{
			echo 'No ad data found.';
		}

	}

	public function ajax_upload_video_ad_logo($video_ad_key)
	{
		$response = [
			'is_success' => false,
		];

		$redirect = $this->vl_platform->get_access_permission_redirect('ad_machina');
		if(!empty($redirect))
		{
			$response['message'] = 'Not authorized';
		}
		else if(empty($_FILES['logo']) || filesize($_FILES['logo']['tmp_name']) == 0)
		{
			$response['message'] = 'No file or empty file';
		}
		else
		{
			$file_extension = str_replace('image/', '', $_FILES['logo']['type']);
			$time = time();
			$file_prefix = $this->config->config['spec_ad_rackspace_path']."/logos/$video_ad_key"; // prefix without file extension
			$file = [
				'local_file_path' => $_FILES['logo']['tmp_name'],
				'name' => "$file_prefix$time.$file_extension",
			];

			$this->delete_video_ad_logo_from_cdn($video_ad_key);

			$upload_response = $this->vl_rackspace_cdn->upload_file($this->config->config['spec_ad_rackspace_container'], $file);
			if($upload_response)
			{
				$logo_objects = $this->vl_rackspace_cdn->get_file_list($this->config->config['spec_ad_rackspace_container'], $this->config->config['spec_ad_rackspace_path'].'/logos/'.$video_ad_key);
				$response['cdn_urls'] = $this->vl_rackspace_cdn->get_cdn_urls($logo_objects, true);
				$response['is_success'] = !empty($response['cdn_urls']);
			}
		}

		echo json_encode($response);
	}

	public function ajax_delete_video_ad_logo($video_ad_key)
	{
		$response = [
			'is_success' => false,
		];

		$redirect = $this->vl_platform->get_access_permission_redirect('ad_machina');
		if(!empty($redirect))
		{
			$response['message'] = 'Not authorized';
		}
		else if($this->delete_video_ad_logo_from_cdn($video_ad_key))
		{
			$response['is_success'] = true;
			$response['message'] = 'file deleted';
		}

		echo json_encode($response);
	}

	public function ajax_publish_ad($video_source_id, $time)
	{
		$response = [
			'is_success' => false,
		];

		$redirect = $this->vl_platform->get_access_permission_redirect('ad_machina');

		$is_user_authorized = empty($redirect);

		if($is_user_authorized)
		{
			$user_id = $this->tank_auth->get_user_id();
			$user = $this->video_ad_model->get_user_info($user_id);

			$creative = $this->video_ad_model->get_creative(
				$video_source_id,
				$user->id,
				$user->role,
				$user->isGroupSuper
			);
			if(empty($creative))
			{
				$is_user_authorized = false;
			}
		}

		if(!$is_user_authorized)
		{
			$response['message'] = 'Not authorized';
		}
		else
		{
			$user_id = $this->tank_auth->get_user_id();
			$video_ad_key = $this->video_ad_model->create_video_ad_key($video_source_id, $time);
			$response['video_ad_key'] = $video_ad_key;

			$config_json = $this->input->post('config');
			$template_id = $this->input->post('template_id');
			$advertiser_id = $this->video_ad_model->get_advertiser_id_from_video_id($video_source_id);
			$demo_title = $this->input->post('demo_title');

			if($config_json)
			{

				$config_data = json_decode($config_json, true);
				$s3_urls = $config_data['video'];
				$cdn_assets_result = $this->copy_video_and_thumbnail_from_s3_to_cdn($s3_urls);
				if($cdn_assets_result['is_success'])
				{
					$config_data['video'] = $cdn_assets_result['files'];
					$config_data['advertiser_url'] = '%c%u'; // replaced by Doubleclick
					$config_json = json_encode($config_data);

					$url_demo_title = preg_replace('/[\']/', '', $demo_title);
					$url_demo_title = preg_replace('/[^A-Za-z0-9]+/', '-', $url_demo_title);
					$preview_url = "/vab/preview/$url_demo_title/$video_ad_key";

					$video_ad_data_to_save = [
						'user_id'         => $user_id,
						'config_json'     => $config_json,
						'template_id'     => $template_id,
						'advertiser_id'   => $advertiser_id,
						'demo_title'      => $demo_title,
						'video_source_id' => $video_source_id,
						'preview_key'     => $video_ad_key,
						'preview_url'     => $preview_url
					];

					$video_ad_id = $this->video_ad_model->save_video_ad_data($video_ad_data_to_save);

					if($video_ad_id)
					{
						$response['is_success'] = true;
						$response['preview_url'] = $preview_url . '/';
						$response['video_ad_key'] = $video_ad_key;
						$response['video_ad_id'] = $video_ad_id;
					}
					else
					{
						$response['message'] = 'Failed to save ad data to database.';
					}

				}
				else
				{
					$response['message'] = 'Failed to put S3 assets on CDN.';
				}

			}
		}

		echo json_encode($response);
	}

	/*
	 * @param string $video_ad_key
	 * @returns boolean $success
	 */
	private function delete_video_ad_logo_from_cdn($video_ad_key)
	{
		$success = true;

		// only one logo per ad: delete any existing logos for this ad (should be only 1)
		// if no logos found on CDN, return success: true
		$file_prefix = $this->config->config['spec_ad_rackspace_path']."/logos/$video_ad_key"; // prefix without file extension
		$file_matches = $this->vl_rackspace_cdn->get_file_list($this->config->config['spec_ad_rackspace_container'], $file_prefix);
		if(count($file_matches))
		{
			foreach($file_matches as $file_object)
			{
				$delete_response = $this->vl_rackspace_cdn->delete_file($this->config->config['spec_ad_rackspace_container'], $file_object->getName());
				$success = $success && $delete_response;
			}
		}

		return $success;
	}

	/*
	 * sort a list of URLs into sets organized by type
	 * @param array $collection
	 * @param array $media_types (optional) contains string keys to filter URLs into. If omitted, the list is assumed to have sets of only one file.
	 */
	private function sort_into_compound_assets(&$collection, $media_types = null, $use_file_names_as_titles = false)
	{
		sort($collection);

		$compound_collection = array();

		if($media_types !== null)
		{
			// cluster similar URLs, while disregarding portions by which similar URLs will commonly differ
			$meaningful_urls = array();
			$distances = array();

			$types_pattern = '/(' . implode('|', $media_types) . ')/i';

			foreach($collection as $collection_index => $url)
			{
				$meaningful_urls[] = preg_replace($types_pattern, '', $url);
				if($collection_index > 0)
				{
					$distances[$collection_index-1] = levenshtein($meaningful_urls[$collection_index - 1], $meaningful_urls[$collection_index]);
				}
			}

			$distance_threshold = 0;
			if(count($distances) > 0)
			{
				$distance_threshold = array_sum($distances) / count($distances);
			}
			if($distance_threshold > 3)
			{
				$distance_threshold = 3;
			}
			$cluster = array();
			foreach($collection as $index => $url)
			{
				$cluster[] = $url;
				if(!isset($distances[$index]) || $distances[$index] > $distance_threshold)
				{
					$compound_collection[] = $cluster;
					$cluster = array();
				}
			}
		}
		else
		{
			foreach($collection as $url)
			{
				$compound_collection[] = array($url);
			}
		}

		foreach($compound_collection as &$set)
		{
			if($media_types)
			{
				$thumbnail_url = null;
				$named_set = array();
				$unused_names = $media_types;
				foreach($set as $url)
				{
					if(!count($unused_names))
					{
						break;
					}

					$chosen_name = null;
					foreach($unused_names as $index => $name)
					{
						if(strpos($url, $name) !== false)
						{
							$chosen_name = $name;
							array_splice($unused_names, $index, 1);
						}
					}

					if($chosen_name === null)
					{
						$chosen_name = array_shift($unused_names);
					}

					if($chosen_name == $media_types[0])
					{
						$thumbnail_url = $url;
					}

					$named_set[$chosen_name] = $url;
				}
			}
			else
			{
				$thumbnail_url = $set[0];
				$named_set = $set[0];
			}

			$set = array(
				'thumbnail' => $thumbnail_url,
				'media' => $named_set,
			);

			if($use_file_names_as_titles)
			{
				$title = basename($set['thumbnail']); // use thumbnail file name (without path) as title
				$title = preg_replace('/\.[^\.]*$/', '', $title); // remove file extension
				$title = preg_replace('/_/', ' ', $title); // change underscores to spaces
				$set['title'] = $title;
			}
		}

		$collection = $compound_collection;

	}

	private function copy_video_and_thumbnail_from_s3_to_cdn($s3_urls)
	{
		$result = [
			'is_success' => false,
			'files' => [],
		];

		$file_type_settings = [
			'mp4' => [
				's3_path' => $this->config->config['video_output_path'] . '/videos/',
				'spec_ad_rackspace_path' => $this->config->config['spec_ad_rackspace_path'] . '/videos/',
			],
			'webm' => [
				's3_path' => $this->config->config['video_output_path'] . '/videos/',
				'spec_ad_rackspace_path' => $this->config->config['spec_ad_rackspace_path'] . '/videos/',
			],
			'poster' => [
				's3_path' => $this->config->config['video_output_path'] . '/thumbnails/',
				'spec_ad_rackspace_path' => $this->config->config['spec_ad_rackspace_path'] . '/thumbnails/',
			],
		];

		if(!empty($s3_urls))
		{
			$valid_media_type_count = 0;
			foreach($s3_urls as $type => $s3_url)
			{
				if(!isset($file_type_settings[$type])) { // other video options
					continue;
				}
				$valid_media_type_count++; // for comparison
				if(strpos($s3_url, 's3.amazonaws.com') === false) { // not an S3 URL, presuming CDN URL loaded from saved ad
					$result['files'][$type] = $s3_url;
					continue;
				}
				$s3_basename = preg_replace('/\?.*$/', '', basename($s3_url)); // remove query string, too
				$s3_file_extension = preg_replace('/^.*\./', '', $s3_basename);
				$s3_basename_without_extension = str_replace('.' . $s3_file_extension, '', $s3_basename);
				$s3_path = $file_type_settings[$type]['s3_path'] . $s3_basename;
				$spec_ad_rackspace_path = $file_type_settings[$type]['spec_ad_rackspace_path'] . $this->video_ad_model->obfuscate($s3_basename_without_extension) . '.' . $s3_file_extension;

				try
				{
					$cdn_url = $this->get_cdn_url_and_copy_from_s3_if_necessary($s3_path, $spec_ad_rackspace_path);
				}
				catch(Exception $exception)
				{
					$result['message'] = $exception->getMessage();
				}

				if(!$cdn_url)
				{
					break;
				}

				$result['files'][$type] = $cdn_url;
			}
		}

		if(count($result['files']) == $valid_media_type_count)
		{
			$result['is_success'] = true;
		}

		return $result;
	}

	private function get_cdn_url_and_copy_from_s3_if_necessary($s3_path, $spec_ad_rackspace_path)
	{
		$cdn_url = $this->get_cdn_url_from_path($spec_ad_rackspace_path);

		if(!$cdn_url)
		{
			$cdn_url = $this->copy_file_from_s3_to_cdn($s3_path, $spec_ad_rackspace_path);
		}

		return $cdn_url;
	}

	private function copy_file_from_s3_to_cdn($s3_path, $spec_ad_rackspace_path)
	{
		// download file from S3 to temp file
		$use_https_cdn_urls = true;
		$file_basename = basename($s3_path);
		$file = [
			'local_file_path' => tempnam(sys_get_temp_dir(), $file_basename),
			'name' => $spec_ad_rackspace_path,
		];

		$download_response = $this->vl_aws_services->get_file($this->config->config['video_output_bucket'], $s3_path);
		if(empty($download_response['ContentLength']))
		{
			return false;
		}
		if(file_put_contents($file['local_file_path'], $download_response['Body']) === false)
		{
			return false;
		}

		// upload file to CDN
		$cdn_file_object = $this->vl_rackspace_cdn->upload_file($this->config->config['spec_ad_rackspace_container'], $file);
		unlink($file['local_file_path']);

		return $this->vl_rackspace_cdn->get_cdn_url($cdn_file_object, $use_https_cdn_urls);
	}

	private function get_cdn_url_from_path($spec_ad_rackspace_path)
	{
		$url = false;
		$use_https_cdn_urls = true;

		if($file_object = $this->vl_rackspace_cdn->get_file_info($this->config->config['spec_ad_rackspace_container'], $spec_ad_rackspace_path))
		{
			$url = $this->vl_rackspace_cdn->get_cdn_url($file_object, $use_https_cdn_urls);
		}

		return $url;
	}

	private function get_accounts_by_current_user_access()
	{
		$user_id = $this->tank_auth->get_user_id();
		$user = $this->tank_auth->get_user_by_id($user_id);
		$user->role = strtolower($user->role);
		$accounts = [];

		$search_term = null;
		$mysql_page_number = 0;
		$page_limit = 0;
		switch($user->role)
		{
			case 'sales':
				$advertisers = $this->tank_auth->get_advertisers_by_sales_person_partner_hierarchy($user_id, $user->isGroupSuper, $search_term, $mysql_page_number, $page_limit);
				break;
			case 'business':
				$advertiser_name = $this->report_v2_model->get_advertiser_name($user->advertiser_id);
				$advertisers = array(array('Name' => $advertiser_name, 'id' => $user->advertiser_id));
				break;
			case 'admin':
			case 'creative':
			case 'ops':
				$advertisers = $this->tank_auth->get_businesses($search_term, $mysql_page_number, $page_limit);
				break;
			case 'agency':
			case 'client':
				$advertisers = $this->tank_auth->get_advertisers_for_client_or_agency($user_id, $search_term, $mysql_page_number, $page_limit);
				break;
			default:
				throw new Exception("Unknown user role: ".$user->role." (#7863801)");
				break;
		}

		if(!is_null($advertisers))
		{
			$advertiser_frq_ids = array_column($advertisers, 'id');
			$accounts = $this->video_ad_model->filter_accounts_with_spots_from_advertisers_frq_id($advertiser_frq_ids);
		}

		return $accounts;
	}

	public function initial_sample_ad_data_fix()
	{
		if($this->input->is_cli_request())
		{
			$this->video_ad_model->initial_sample_ad_data_fix();
			echo "Done\n\n";
		}
		else
		{
			show_404();
		}
	}

	public function download_csv()
	{
		if($this->tank_auth->is_logged_in())
		{
			$this->csv->download_posted_inline_csv_data();
		}
		else
		{
			echo 'not logged in ';
		}
	}

}
