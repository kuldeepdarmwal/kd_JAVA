<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Creative_uploader extends CI_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->load->helper(array(
			'form',
			'url',
			'multi_upload',
			'file',
			'ad_server_type',
			'select2_helper',
			'vl_ajax'
		));
		$this->load->model(array('cdn_model', 'cup_model','publisher_model', 'tradedesk_model'));
		$this->load->model('variables_model');
		$this->load->model('spec_ad_model');
		$this->load->model('geo_in_ads_model');
		$this->load->model('vl_auth_model');
		$this->load->model('al_model');
		$this->load->library(array('session', 'ftp', 'tank_auth', 'swiffy'));
	}

	public function index($adset_version_id = "0")
	{
		if(!$this->tank_auth->is_logged_in())
		{
			$referer = 'creative_uploader';
			if($adset_version_id != '0')
			{
				$referer .= "/$adset_version_id";
			}
			$this->session->set_userdata('referer', $referer);
			redirect('login');
		}
		else if ($_SERVER['SERVER_PORT'] == 443) // mimicking unforce_ssl() from hooks/ssl.php, will be unnecessary when entire platform is SSL
		{
			$this->config->config['base_url'] = str_replace('https://', 'http://', $this->config->config['base_url']);
			redirect($this->uri->uri_string());
		}
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		if ($role == 'ops' or $role == 'admin' or $role == 'creative')
		{
			$data['username'] = $username;
			$data['firstname'] = $this->tank_auth->get_firstname($data['username']);
			$data['lastname'] = $this->tank_auth->get_lastname($data['username']);
			$data['user_id'] = $this->tank_auth->get_user_id();
			
			$existing_version_response = $this->cup_model->get_campaign_adset_version_data_by_version_id($adset_version_id);
			$data['existing_adset_object'] = json_encode($existing_version_response);
			$data['adset_version_id'] = $adset_version_id;
	
			$data['active_menu_item'] = "adsets_menu";//the view will make this menu item active
			$data['title'] = "Adcenter";
			$this->load->view('ad_linker/header', $data);

			$this->load->view('vl_platform_2/ui_core/js_error_handling',$data);

			$this->load->view('creative_upload/adsets_body',$data);
		}
		else
		{	
			redirect('director'); 
		}
	}


	function get_ids_from_adset_version(){
		echo json_encode($this->cup_model->get_adset_ids_from_adset_version_id($_POST["av_id"]));
	}

	//Unused Function? : Will
	function gallery($adset)
	{
		$data['link_array'] = $this->cup_model->get_links($adset);
		$this->load->view('creative_upload/gallery', $data);
	}

	//Build Adsets called at body load, reads all current adsets in database and returns html for a drop down
	function build_adsets()
	{
		$adset_result = $this->cup_model->list_adsets();
		$select_string = '<option value="none">Please select</option>';
		$select_string .= '<option value="none">---------------------</option>';
		$select_string .= '<option value="select_new_dropdown_adset_insert">Add New Adset</option>';
		if(is_array($adset_result))
		{
			$select_string .= '<option value="none">---------------------</option>';
			foreach($adset_result as $v)
			{
				$select_string .= '<option value="'.$v["id"].'">'.$v["name"].'</option>';
			}
		}
		else
		{
			//dont populate because we have no adsets
		}
		$key = $this->session->userdata('key');
		if($key)  //checking if we have a key created already
		{
			$directory = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/';
			if(!is_dir($directory)) //if the key directory does not exist create it
			{
				$is_success = true;
				$is_success = $is_success && mkdir($directory);
				$is_success = $is_success && chmod($directory, 0777);
				$is_success = $is_success && mkdir($directory."files/");
				$is_success = $is_success && chmod($directory."files/", 0777);
				$is_success = $is_success && mkdir($directory."structured_files/");
				$is_success = $is_success && chmod($directory."structured_files/", 0777);
				$is_success = $is_success && mkdir($directory."thumbnails/");
				$is_success = $is_success && chmod($directory."thumbnails/", 0777);
				if(!$is_success)
				{
					die("build_adsets() permissions failure");
				}
			}
		}
		else  //otherwise create a new key and create the directory.
		{
			$key = time();
			$this->session->set_userdata(array('key' => $key));
			$directory = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/';
			if(is_dir($directory))
			{
				$this->rmdir_recursive($directory);
			}
			$is_success = true;
			$is_success = $is_success && mkdir($directory);
			$is_success = $is_success && chmod($directory, 0777);
			$is_success = $is_success && mkdir($directory."files/");
			$is_success = $is_success && chmod($directory."files/", 0777);
			$is_success = $is_success && mkdir($directory."structured_files/");
			$is_success = $is_success && chmod($directory."structured_files/", 0777);
			$is_success = $is_success && mkdir($directory."thumbnails/");
			$is_success = $is_success && chmod($directory."thumbnails/", 0777);
			if(!$is_success)
			{
				die("build_adsets() permissions failure");
			}
		}
		echo $select_string;
	}
	
	//insert a new adset into the database and return the id of the inserted element so we can set it to be selected in the dropdown
	function insert_new_adset()
	{
		$response_array = array('is_success' => false, 'data' => array());
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			if($this->tank_auth->is_logged_in())
			{
				$c_id = $this->input->post('c_id');
				$string = $this->input->post('text');
				$text = preg_replace('/[;~\/<>|^\r\n]|[;]$/s', '_', $string);
				$user_id = $this->tank_auth->get_user_id();
				if($c_id !== false && $text !== false)
				{
					$response = $this->cup_model->insert_adset($text,(($c_id  == "none" OR $c_id == "") ? null : $c_id), $user_id);
					if($response)
					{
						$response_array['data'] = $response;
						$response_array['is_success'] = true;
					}
				}
			}
			echo json_encode($response_array);
		}
		else
		{
			show_404();
		}
	}
	
	public function is_file_there($file_string){//* are wildcards
		$it = iterator_to_array(new GlobIterator($file_string, GlobIterator::CURRENT_AS_PATHNAME) );
		return  count($it) == 0 ? false : true ;
	}
	
	public function get_first_file_match($file_string){
		$it = iterator_to_array(new GlobIterator($file_string, GlobIterator::CURRENT_AS_PATHNAME) );
		return reset($it);
	}

	//remove the key from the session and call the recursive rmdir function on the associated directory
	public function unlink_key()
	{
		$key = $this->session->userdata('key');
		if($key)
		{
			$directory = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/';
			if(is_dir($directory))
			{
				$this->rmdir_recursive($directory);
			}
			$this->session->unset_userdata('key');
		}
	}
	//Called either when dropdown is selected or after an insert/replace is called.
	public function populate_from_existing_adset($version="0")
	{
		if($version != "0" AND $version != 'new')
		{
			$builder_version = $this->cup_model->get_builder_version_by_version_id($version);
			$assets = $this->cup_model->get_assets_by_adset($version, '%'); //get all current assets
			$assets_modified_since_publish = false;
			if(html5_creative_set::is_html5_builder_version($builder_version))
			{
				$html5_creative_set = new html5_creative_set($this, $assets, null, $builder_version);
				$missing_array = $html5_creative_set->list_all_missing_files();
				foreach($assets as $asset)  //determine which files we already have
				{
					if(isset($asset['modified_since_publish']) && $asset['modified_since_publish'] == '1')
					{
						$assets_modified_since_publish = true;
					}
				}
			}
			else
			{
				$missing_array = array(
					'static' => array('variables_js' => 1, 'variables_xml' => 1, 'fullscreen' => 1),
					'160x600' => array('swf' => 1, 'js' => 1, 'backup' => 1, 'loader' => 1),
					'300x250' => array('swf' => 1, 'js' => 1, 'backup' => 1, 'loader' => 1),
					'336x280' => array('swf' => 1, 'js' => 1, 'backup' => 1, 'loader' => 1),
					'728x90'  => array('swf' => 1, 'js' => 1, 'backup' => 1, 'loader' => 1),
					'320x50'  => array('swf' => 1, 'js' => 1, 'backup' => 1, 'loader' => 1)
				);
				foreach($assets as $asset)  //determine which files we already have
				{
					if($asset["size"] == "")
					{
						$missing_array['static'][$asset["type"]] = 0;
					}
					else
					{
						$missing_array[$asset["size"]][$asset["type"]] = 0;
					}
					if(isset($asset['modified_since_publish']) && $asset['modified_since_publish'] == '1')
					{
						$assets_modified_since_publish = true;
					}
				}
			}
			$missing_string = '';
			foreach($missing_array as $size => $file_types)
			{ //create the html for the missing assets
		
				if(in_array(1, $file_types))
				{ //something is missing
					$missing_string .= '<tr class="warning" ><td style="width:20%;">'.$size.': </td>';
					$missing_string .= '<td style="width:15%;">Missing: </td>';
					$missing_string .= '<td>';
					$tempy = "";
					foreach($file_types as $file_type => $is_missing)
					{
						if($is_missing == 1)
						{
							$missing_string .= $tempy."&nbsp;".$file_type;
							$tempy = ",";
						}
					}
					$missing_string .= '</td>';
				}
				else 
				{ //all files exist for that category of file
					$missing_string .= '<tr><td >'.$size.': </td>';
					$missing_string .= '<td>OK</td><td ></td>';
				}
				$missing_string .= '</tr>';
			}
			$output = "";

			//get some info on variables data and builder version
			$adset_array = $this->variables_model->get_adset_by_id($version);
			if(!is_null($adset_array[0]['variables_data'])){
				$output .= '<div class="alert alert-success">Variables Data Found Builder Version: '.$adset_array[0]['builder_version'].'</div>';
			}
			$data['variables_data_obj'] = json_encode($adset_array[0]['variables_data']);

			$unpublished_changes = [];
			if($assets_modified_since_publish)
			{
				$unpublished_changes[] = 'assets';
			}
			if($adset_array[0]['modified_since_publish'] == '1')
			{
				$unpublished_changes[] = 'variables';
			}
			if(!empty($unpublished_changes))
			{
				$output .= '<div class="alert alert-warning">This adset version has changed <strong>' . implode('</strong> and <strong>', $unpublished_changes) . '</strong> since it was last published.</div>';
			}


			$output .= '<div class="output_div" id="output_creative_summary"><div id="output_creative_left"><h4>File Check</h4>';
			$output .= '<table id="output_creative_summary_table" class="table table-bordered table-hover table-condensed">'.$missing_string.'</table></div></div>';
			$output .= '<br>';
	
			//output html to be returned
			$output .= '<br><br><div class="output_div" id="output_assets_content"><h4>Registered Assets</h4><table id="output_asset_table" class="table table-bordered table-hover table-condensed table-striped">';
			$output .= '<thead><th>Type</th><th>Extension</th><th>Weight</th><th>URI</th></thead>';
			foreach($assets as $asset)
			{ //create the html for existing assets
				if($asset["size"] == "")
				{
					$output .= '<tr><td>'.$asset["type"].'</td><td>'.$asset["extension"].'</td><td>'.$asset["weight"].'</td>';
				}
				else
				{
					$output .= '<tr><td>'.$asset["type"]."_".$asset["size"].'</td><td>'.$asset["extension"].'</td><td>'.$asset["weight"].'</td>';
				}
				$output .= '<td><a target="_blank" href="'.$asset["open_uri"].'">Open</a>&nbsp;&nbsp;<a target="_blank" href="'.$asset["ssl_uri"].'">Secure</a></td></tr>';
			}
			$output .= '</table></div>';
	
			echo $output;
		}
		else
		{
			echo 'false';
		}
	}
	//check for conflicts between submitted files and existing assets.
	public function check_exists($version)
	{
		if($version != '0' AND $version != 'new')
		{
			$adset = $this->cup_model->get_adset_by_version_id($version);
			$file_array = array('static' => array('variables_js' => '*variables.js', 'variables_xml' => '*variables.xml', 'fullscreen' => '*fullscreen.*'),
								'160x600' => array('swf' => '*160x600.swf', 'js' => '*160x600.js', 'backup' => '*backup_160x600.*', 'loader' => '*loader_image_160x600.*'),
								'300x250' => array('swf' => '*300x250.swf', 'js' => '*300x250.js', 'backup' => '*backup_300x250.*', 'loader' => '*loader_image_300x250.*'),
								'336x280' => array('swf' => '*336x280.swf', 'js' => '*336x280.js', 'backup' => '*backup_336x280.*', 'loader' => '*loader_image_336x280.*'),
								'728x90'  => array('swf' => '*728x90.swf',  'js' => '*728x90.js',  'backup' => '*backup_728x90.*',  'loader' => '*loader_image_728x90.*'),
								'320x50'  => array('swf' => '*320x50.swf',  'js' => '*320x50.js',  'backup' => '*backup_320x50.*', 'loader' => '*loader_image_320x50.*'),
								'468x60'  => array('backup' => '*backup_468x60.*',  'loader' => '*loader_image_468x60.*'));
			$missing_array = array('static' => array('variables_js' => 1, 'variables_xml' => 1, 'fullscreen' => 1),
								   '160x600' => array('swf' => 1, 'js' => 1, 'backup' => 1, 'loader' => 1),
								   '300x250' => array('swf' => 1, 'js' => 1, 'backup' => 1, 'loader' => 1),
								   '336x280' => array('swf' => 1, 'js' => 1, 'backup' => 1, 'loader' => 1),
								   '728x90'  => array('swf' => 1, 'js' => 1, 'backup' => 1, 'loader' => 1),
								   '320x50'  => array('swf' => 1, 'js' => 1, 'backup' => 1, 'loader' => 1),
								   '468x60'  => array('backup' => 1, 'loader' => 1));
			$key = $this->session->userdata('key');
			if($key)
			{

				$value = $this->cup_model->get_assets_by_adset($version);
				foreach($value as $v)
				{
					if($v["size"] == "")
					{
						$missing_array['static'][$v["type"]] = 0;
					}
					else
					{
						$missing_array[$v["size"]][$v["type"]] = 0;
					}
				}
				$conflict_string = "";
				$link = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/files';
				foreach($file_array as $k => $v)
				{
					foreach($v as $j => $u)
					{
						if($missing_array[$k][$j] == 0)
						{
							if($this->is_file_there($link."/".$u))
							{
								if($k == 'static')
								{
									$conflict_string .= 'Asset already registered: '.$j.'<br />';
								}
								else
								{
									$conflict_string .= 'Asset already registered: '.$j.'_'.$k.'<br />';
								}
							}
						}
					}
				}
				if($conflict_string != '')
				{  //we have conflicts, return a conflict string to be displayed in the modal box popup
					echo $conflict_string;
				}
				else
				{
					echo "0";
				}
			} //if key end
			else
			{
				echo "Warning 2291: Directory key lost, please re-upload files";
			}
		}
	}

	//after confirmation from modal box or if there are no conflicts between submitted and existing assets replace into the database with the new files.
	public function find_variables($version)
	{
		if($version == '0' || $version == 'new')
		{
			return;
		}

		$adset = $this->cup_model->get_adset_by_version_id($version);
		$file_array = array(
			'static' => array('variables_js' => '*variables.js', 'variables_xml' => '*variables.xml', 'fullscreen' => '*fullscreen.*'),
			'160x600' => array('swf' => '*160x600.swf', 'js' => '*160x600.js', 'backup' => '*backup_160x600.*', 'loader' => '*loader_image_160x600.*'),
			'300x250' => array('swf' => '*300x250.swf', 'js' => '*300x250.js', 'backup' => '*backup_300x250.*', 'loader' => '*loader_image_300x250.*'),
			'336x280' => array('swf' => '*336x280.swf', 'js' => '*336x280.js', 'backup' => '*backup_336x280.*', 'loader' => '*loader_image_336x280.*'),
			'728x90'  => array('swf' => '*728x90.swf',  'js' => '*728x90.js',  'backup' => '*backup_728x90.*',  'loader' => '*loader_image_728x90.*'),
			'320x50'  => array('swf' => '*320x50.swf',  'js' => '*320x50.js',  'backup' => '*backup_320x50.*', 'loader' => '*loader_image_320x50.*'),
			'468x60'  => array('backup' => '*backup_468x60.*',  'loader' => '*loader_image_468x60.*')
		);

		$existing_array = array();

		$output = '';
		$entries = '';
		
		$user_id = $this->tank_auth->get_user_id();
		$key = $this->session->userdata('key');
		if($key)
		{ //if we find a key in the session, we know we have files to look for.
			$link = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/files';
			//populate existing_array, which will be the array we use to insert into the database
			foreach($file_array as $file_group => $file_types)
			{
				foreach($file_types as $file_type => $file_glob)
				{
					$file_index = ($file_group !== 'static' ? "{$file_group}_{$file_type}" : $file_type);
					$full_file_glob = "$link/$file_glob";

					if($this->is_file_there($full_file_glob))
					{
						$file_path = $this->get_first_file_match($full_file_glob);
						$existing_array[$file_index] = pathinfo($file_path);
						$existing_array[$file_index]['type'] = $file_type;
						$existing_array[$file_index]['weight'] = $this->format_bytes(filesize($file_path));

						if($file_group !== 'static')
						{
							$existing_array[$file_index]['size'] = $file_group;
						}
					}
				}
			}

			foreach($existing_array as $file_index => $file_info)
			{ //Load new assets to CDN first
				$temp = $this->cdn_model->load_asset(
					"{$file_info["dirname"]}/{$file_info["basename"]}",
					"{$file_index}.{$file_info['extension']}"
				);
				$existing_array[$file_index]['open_uri'] =  $temp['open_uri'];
				$existing_array[$file_index]['ssl_uri'] = $temp['ssl_uri'];
			}

			$builder_version = $this->cup_model->get_builder_version_by_version_id($version);
			if(html5_creative_set::is_html5_builder_version($builder_version))
			{
				$structured_link = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/structured_files';
				if($html5_creative_set = new html5_creative_set($this, $structured_link, null, $builder_version))
				{
					$this->load_html5_creative_assets($version, $html5_creative_set, $existing_array);
				}
			}

			//create appropriate creatives records if we need them and the update assets Then call the populate function to return output html
			$this->cup_model->find_replace_creatives($adset, $version);
			$this->cup_model->update_assets($existing_array, $adset, $version, $user_id);
			$this->populate_from_existing_adset($version);
		}
	}

	//upload files
	public function multi_upload()
	{
		$file_name = isset($_GET['file_stub']) ? basename(stripslashes($_GET['file_stub'])) : null;
		$file_relative_path = isset($_GET['file_relative_path']) ? stripslashes($_GET['file_relative_path']) : null;

		if($this->session->userdata('key'))
		{
			$key = $this->session->userdata('key');
			$directory = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/';
		}
		else
		{
			$key = time();
			$this->session->set_userdata(array('key' => $key));
			$directory = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/';

			if(!mkdir($directory))
			{
				die("multi_upload() failed to mkdir");
			}
			if(!chmod($directory, 0777))
			{
				die("multi_upload() failed to chmod");
			}
			if(!mkdir($directory."files/"))
			{
				die("multi_upload() failed to mkdir");
			}
			if(!chmod($directory."files/", 0777))
			{
				die("multi_upload() failed to chmod");
			}
			if(!mkdir($directory."structured_files/"))
			{
				die("multi_upload() failed to mkdir");
			}
			if(!chmod($directory."structured_files/", 0777))
			{
				die("multi_upload() failed to chmod");
			}
			if(!mkdir($directory."thumbnails/"))
			{
				die("multi_upload() failed to mkdir");
			}
			if(!chmod($directory."thumbnails/", 0777))
			{
				die("multi_upload() failed to chmod");
			}
		}
		error_reporting(E_ALL | E_STRICT);
		//custom option array to be sent to the upload handler
		$options = array(
			'script_url' => base_url('creative_uploader/multi_upload/'),
			'upload_dir' =>  $directory.'files/',
			'upload_url' => base_url('assets/creative_upload/'.$key.'/files/')."/",
			'structured_upload_dir' =>  $directory.'structured_files/',
			'structured_upload_url' => base_url('assets/creative_upload/'.$key.'/structured_files/')."/",
			'param_name' => 'files',
			'delete_type' => 'DELETE',
			'max_file_size' => null,
			'min_file_size' => 1,
			'accept_file_types' => '/.+$/i',
			'max_number_of_files' => null,
			'max_width' => null,
			'max_height' => null,
			'min_width' => 1,
			'min_height' => 1,
			'discard_aborted_uploads' => true,
			'orient_image' => false,
			'image_versions' => array(
				'thumbnail' => array(
					'upload_dir' =>  $directory."thumbnails/",
					'upload_url' => base_url('assets/creative_upload/'.$key.'/thumbnails/')."/",
					'max_width' => 80,
					'max_height' => 80
					)
				),
			'skip_dot_files' => true,
			'mirror_directory_structure' => true
			);
		$builder_version = $this->input->post('builder_version');
		if(!$builder_version || !html5_creative_set::is_html5_builder_version($builder_version))
		{
			$options['file_uploaded_hook'] = [$this, 'check_uploaded_file'];
		}
		$upload_handler = new UploadHandler($options);
		header('Pragma: no-cache');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Content-Disposition: inline; filename="files.json"');
		header('X-Content-Type-Options: nosniff');
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, PUT, DELETE');
		header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');
		switch ($_SERVER['REQUEST_METHOD']) {
		case 'OPTIONS':
			break;
		case 'HEAD':
		case 'GET':
			$upload_handler->get();
			break;
		case 'POST':
			if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE')
			{
				$upload_handler->delete($file_name, $file_relative_path);
			}
			else
			{
				$upload_handler->post();
			}
			break;
		case 'DELETE':
			$upload_handler->delete($file_name, $file_relative_path);
			break;
		default:
			header('HTTP/1.1 405 Method Not Allowed');
		}
	}

	public function get_adsets()
	{
		$adset_array = array('results' => array(), 'more' => false);
		if($_SERVER['REQUEST_METHOD'] === 'POST' && $this->tank_auth->is_logged_in())
		{
			$post_array = $this->input->post();
			if(array_key_exists('q', $post_array) AND array_key_exists('page', $post_array) AND array_key_exists('page_limit', $post_array) AND array_key_exists('campaign', $post_array))
			{
				if($post_array['page'] == 1)
				{
					$adset_array['results'][] = array(
						'id' => 'select_new_dropdown_adset_insert',
						'text' => '*New*'
						);
				}
				$adset_response = select2_helper($this->cup_model, 'get_adsets_for_select2', $post_array, array($post_array['campaign']));
								
				if (!empty($adset_response['results']) && !$adset_response['errors'])
				{
					$adset_array['more'] = $adset_response['more'];
					for($i = 0; $i < $adset_response['real_count']; $i++)
					{
						$adset_array['results'][] = array(
							'id' => $adset_response['results'][$i]['id'],
							'text' => $adset_response['results'][$i]['text']
							);
					}
				}
				
			}
			echo json_encode($adset_array);
		}
		else
		{
			show_404();
		}
	}

	public function ajax_get_sample_ad_display_url()
	{
		$response = array();

		$is_success = false;
		$errors = array();

		if($this->tank_auth->is_logged_in())
		{
			$cup_version_id = $this->input->post('version_id');
			if($cup_version_id !== false)
			{
				$spec_ad_url = $this->spec_ad_model->get_spec_ad_url($cup_version_id);
				$response['url'] = $spec_ad_url;
				$is_success = true;
			}
		}
		else
		{
			$is_success = false;
			$errors[] = 'not logged in ; access denied to creative_uploader';
		}

		$response['is_success'] = $is_success;
		$response['errors'] = $errors;

		$json_response = json_encode($response);
		echo $json_response;
	}
	
	public function encode_ad_tag_id()
	{
		$ad_tag_id = $_GET['tag_id'];
		$return['data'] = base64_encode(base64_encode(base64_encode($ad_tag_id)));
		$return['success'] = true;
		echo json_encode($return);
	}
	
	public function get_dfa_form($version = "")
	{
		$data['version'] = $version;
		$data['has_campaign'] = $this->cup_model->does_version_have_campaign($version);
		$this->load->view('ad_linker/dfa_form',$data);
	}

	//Links an adset to a campaign in the VL DB. 
	public function link_version_to_campaign()
	{
		$return = array('is_success' => false);
		$version_id = $this->input->post('v_id');
		$campaign_id = $this->input->post('c_id');
		if($campaign_id && $version_id)
		{
			$return['is_success'] = $this->cup_model->link_version_to_campaign($version_id, $campaign_id);	
		}
		echo json_encode($return);
	}

	public function get_creatives()
	{
		$version_id = $_POST["version"];
		$all_creatives = $this->cup_model->get_creatives_by_adset($version_id);
		echo json_encode($all_creatives);
	}

	//Get tags for the tag view. 
	//Now only gets tags if they have a tag
	//Displays a warning if the creatives already are in TTD.
	public function get_tags_view()//adset id is input
	{
		$version = $_POST['version'];
		//$campaign_id = $_POST['c_id'];
		if($version != '0' and $version != 'new')
		{
			$a_id = $this->cup_model->get_adset_by_version_id($version);
			$data['adset'] = $a_id;
			$data['partner'] = $this->cup_model->get_partner_from_version($version);
			$creatives = $this->cup_model->get_creatives_for_version($version, k_ad_server_type::all_id);
			$data['creatives'] = $creatives;
			$campaign_id = $this->cup_model->get_campaign_from_version($version);
			$data['ttd_tags'] = $this->tradedesk_model->does_campaign_have_ttd_advertiser($campaign_id);
			$data['ttd_exists'] = $this->cup_model->are_creatives_in_ttd($version);
			$data['campaign_id'] = $campaign_id;
			$data['version_id'] = $version;
			
			
			$published_ad_server = k_ad_server_type::unknown;
			if(is_array($creatives))
			{
				foreach($creatives as $creative)
				{
					$creative_ad_server = $creative['published_ad_server'];
					if($creative_ad_server === null)
					{
						$published_ad_server = k_ad_server_type::unknown;
						break;
					}

					$should_break_loop = false;
					switch($published_ad_server)
					{
						case k_ad_server_type::unknown:
							$published_ad_server = $creative_ad_server;
							break;
						case k_ad_server_type::dfa_id:
							if($creative_ad_server != k_ad_server_type::dfa_id)
							{
								// ad server type is inconsistent
								$published_ad_server = k_ad_server_type::unknown;
								$should_break_loop = true;
							}
							break;
						case k_ad_server_type::adtech_id:
							if($creative_ad_server != k_ad_server_type::adtech_id)
							{
								// ad server type is inconsistent
								$published_ad_server = k_ad_server_type::unknown;
								$should_break_loop = true;
							}
							break;
						case k_ad_server_type::fas_id:
							if($creative_ad_server != k_ad_server_type::fas_id)
							{
								// ad server type is inconsistent
								$published_ad_server = k_ad_server_type::unknown;
								$should_break_loop = true;
							}
							break;	
						default:
							die("unknown ad server type: ".$creative_ad_server." (Error: #408734)");
							// TODO: better error handling -scott
					}

					if($should_break_loop)
					{
						break;
					}
				}
				$dfa_adchoices_break = false;
				$adtech_adchoices_break = false;
				$fas_used_adchoices = false;
				foreach($creatives as $creative)
				{
					if(!$dfa_adchoices_break)
					{
					    $dfa_used_adchoices = $creative['dfa_used_adchoices'];
					    if($dfa_adchoices_break == '0')
					    {
						$dfa_adchoices_break = true;
					    }
					}
					if(!$adtech_adchoices_break)
					{
					    $adtech_used_adchoices = $creative['adtech_used_adchoices'];
					    if($adtech_adchoices_break == '0')
					    {
						$adtech_adchoices_break = true;
					    }
					}
				}
			    $data['dfa_used_adchoices'] = $dfa_used_adchoices;
			    $data['adtech_used_adchoices'] = $adtech_used_adchoices;
			    $data['fas_used_adchoices'] = $fas_used_adchoices;
			}

			$data['published_ad_server'] = $published_ad_server;
			//PUT STUFF HERE
			$this->load->view('ad_linker/get_ad_tags_view', $data);
		}
	}

	public function get_versions($adset)
	{
		$ret_str = "<option value='new'>*New*</option>";
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$allowed_roles = array('admin', 'ops', 'creative');
			$post_variables = array('adset_id', 'campaign_id');

			$response = vl_verify_ajax_call($allowed_roles, $post_variables);
			if($response['is_success']) 
			{
				$adset_id = $this->input->post('adset_id');
				$campaign_id = $this->input->post('campaign_id');

				$versions = $this->cup_model->get_versions_by_adset_for_campaign($adset_id);
				$first_one_selected = false;
				foreach($versions as $v)
				{
					if(!$first_one_selected)
					{
						$ret_str .= '<option selected="selected" value="'.$v['id'].'">v'.$v['version'].'</option>';
						$first_one_selected = true;
					}
					else
					{
						$ret_str .= '<option value="'.$v['id'].'">v'.$v['version'].'</option>';
					}
				}
				echo $ret_str;							
			}
		}
	}

	public function get_variations()
	{

		$ret_str = "<option value='new'>*New*</option>";
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$allowed_roles = array('admin', 'ops', 'creative');
			$post_variables = array('version_id', 'campaign_id');

			$response = vl_verify_ajax_call($allowed_roles, $post_variables);
			if($response['is_success']) 
			{
				$version_id = $this->input->post('version_id');
				$campaign_id = $this->input->post('campaign_id');

				$variations = $this->cup_model->get_variations_by_version_for_campaign($version_id, $campaign_id);
				$first_one_selected = false;
				foreach($variations as $v)
				{
					if(!$first_one_selected)
					{
						$ret_str .= '<option selected="selected" value="'.$v['id'].'">'.$v['variation_name'].'</option>';
						$first_one_selected = true;
					}
					else
					{
						$ret_str .= '<option value="'.$v['id'].'">'.$v['variation_name'].'</option>';
					}
				}
				echo $ret_str;							
			}
		}		
	}

	public function get_campaign_for_adset()
	{
		$return = array('is_success' => false, 'campaign' => array('id' => 'none', 'text' => 'All Campaigns', 'campaign' => null, 'advertiser' => null));
		$adset_id = $this->input->post('ad_id');
		if($adset_id)
		{
			$return['is_success'] = true;
			$campaign_result = $this->cup_model->get_campaign_for_adset($adset_id);
			if(!empty($campaign_result))
			{
				$return['campaign'] = $campaign_result;
			}
		}
		echo json_encode($return);
	}

	public function get_campaign_for_variation()
	{
		$return = array('is_success' => false, 'campaign' => array('id' => 'none', 'text' => 'All Campaigns', 'campaign' => null, 'advertiser' => null));
		$variation_id = $this->input->post('variation_id');
		if($variation_id)
		{
			$return['is_success'] = true;
			$campaign_result = $this->cup_model->get_campaign_for_variation($variation_id);
			if(!empty($campaign_result))
			{
				$return['campaign'] = $campaign_result;
			}
		}
		echo json_encode($return);
	}	
	
	public function insert_new_version()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$allowed_roles = array('admin', 'ops', 'creative');
			$post_variables = array('adset_id', 'campaign_id');

			$response = vl_verify_ajax_call($allowed_roles, $post_variables);
			if($response['is_success']) 
			{	
				$adset = $this->input->post('adset_id');
				$campaign_id = $this->input->post('campaign_id');
				if($campaign_id == "none" || empty($campaign_id))
				{
					$campaign_id = null;
				}
				$user_id = $this->tank_auth->get_user_id();
				$id = $this->cup_model->insert_version($adset, $user_id, $campaign_id);
				if($id)
				{
					echo $id;
					return;
				}
			}
		}
		echo "false";
	}

	// FIXME: It seems get_local_files is never called -CL 2016-07-25
	public function get_local_files()
	{
		if($key)
		{
			$file_array = array('160x600' => array('swf' => '*160x600.swf', 'backup' => '*backup_160x600.*', 'loader' => '*loader_image_160x600.*'),
								'300x250' => array('swf' => '*300x250.swf', 'backup' => '*backup_300x250.*', 'loader' => '*loader_image_300x250.*'),
								'336x280' => array('swf' => '*336x280.swf', 'backup' => '*backup_336x280.*', 'loader' => '*loader_image_336x280.*'),
								'728x90'  => array('swf' => '*728x90.swf',  'backup' => '*backup_728x90.*',  'loader' => '*loader_image_728x90.*'),
								'320x50'  => array('swf' => '*320x50.swf',  'backup' => '*backup_320x50.*', 'loader' => '*loader_image_320x50.*'));
			foreach($file_array as $k => $v)
			{
				$print .= $this->get_local_ad($k);
			}
			echo $print;
		}
		else
		{
			echo "Key required";
		}
	}

	public function get_local_ad($size, $is_html5 = "0")
	{
		$file_array = array(
			'160x600' => array('swf' => '*160x600.swf', 'js' => '*160x600.js', 'backup' => '*backup_160x600.*', 'loader' => '*loader_image_160x600.*'),
			'300x250' => array('swf' => '*300x250.swf', 'js' => '*300x250.js', 'backup' => '*backup_300x250.*', 'loader' => '*loader_image_300x250.*'),
			'336x280' => array('swf' => '*336x280.swf', 'js' => '*336x280.js', 'backup' => '*backup_336x280.*', 'loader' => '*loader_image_336x280.*'),
			'728x90'  => array('swf' => '*728x90.swf',  'js' => '*728x90.js',  'backup' => '*backup_728x90.*',  'loader' => '*loader_image_728x90.*'),
			'320x50'  => array('swf' => '*320x50.swf',  'js' => '*320x50.js',  'backup' => '*backup_320x50.*', 'loader' => '*loader_image_320x50.*'),
			'468x60'  => array('backup' => '*backup_468x60.*',  'loader' => '*loader_image_468x60.*')
		);
		if(array_key_exists($size, $file_array))
		{
			$data['force_html5'] = ($is_html5 == '1') ? "true" : "false";
			$data['open_xml_file'] = '';
			$data['ssl_xml_file'] = '';
			$data['open_fullscreen_file'] = '';
			$data['ssl_fullscreen_file'] = '';
			$data['open_js_file'] = '';
			$data['ssl_js_file'] = '';
			$assets = array();
			$key = $this->session->userdata('key');
			$print = '';
			if($key)
			{
				$builder_version = null; // TODO: $this->cup_model->get_builder_version_by_version_id($version_id);
				$file_index = $file_array[$size];
				$link = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/files';
				$structured_link = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/structured_files';
				if($html5_creative_set = new html5_creative_set($this, $structured_link, $size, $builder_version))
				{
					$creative_markup = $html5_creative_set->get_creative_markup_for_size($size);
					$data['html5_initialization'] = $creative_markup['initialization'];
					$data['html5_setup'] = $creative_markup['setup'];
				}
				$path = base_url('assets/creative_upload/'.$key.'/files');
				if($this->is_file_there($link."/*variables*"))
				{
					$data['open_js_variables_file'] = "";
					$data['ssl_js_variables_file'] = "";
					$data['variables_js'] = "";
					$data['open_xml_file'] = "";
					$data['ssl_xml_file'] = "";
					if($this->is_file_there($link."/*variables.js"))
					{
						array_push($assets, array('type' => 'variables_js'));
						$data['open_js_variables_file'] = $this->get_first_file_match($link."/*variables.js");
						$temp = explode('assets/', $data['open_js_variables_file']);
						$data['open_js_variables_file'] = base_url('assets/'.$temp[1]);
						$data['ssl_js_variables_file'] = $data['open_js_variables_file'];
						$data['variables_js'] = json_encode(file_get_contents($data['open_js_variables_file']));
					}
					if($this->is_file_there($link."/*variables.xml"))
					{
						array_push($assets, array('type' => 'variables_xml'));
						$data['open_xml_file'] = $this->get_first_file_match($link."/*variables.xml");
						$temp = explode('assets/', $data['open_xml_file']);
						$data['open_xml_file'] = base_url('assets/'.$temp[1]);
						$data['ssl_xml_file'] = $data['open_xml_file'];
					}
				}
				if($this->is_file_there($link."/*fullscreen*"))
				{
					$data['open_fullscreen_file'] = $this->get_first_file_match($link."/*fullscreen*");
					$temp = explode('assets/', $data['open_fullscreen_file']);
					$data['open_fullscreen_file'] = base_url('assets/'.$temp[1]);
					$data['ssl_fullscreen_file'] = $data['open_fullscreen_file'];
				}
		
				$data['open_swf_file'] = '';
				$data['ssl_swf_file'] = '';
				$data['backup_image']= '';
				$data['open_backup_image']= '';
				$data['ssl_backup_image']= '';
				$data['open_gpa_image_file'] = '';
				$data['ssl_gpa_image_file'] = '';
				$data['is_gpa'] = false;
				if($this->is_file_there($link."/".$file_index['js']))
				{
					$data['open_js_file'] = $this->get_first_file_match($link."/".$file_index['js']);
					$temp = explode('assets/', $data['open_js_file']);
					$data['open_js_file'] = base_url('assets/'.$temp[1]);
					$data['ssl_js_file'] = $data['open_js_file'];
					array_push($assets, array('type' => 'js'));
				}
				if($this->is_file_there($link."/".$file_index['swf']))
				{
					$data['open_swf_file'] = $this->get_first_file_match($link."/".$file_index['swf']);
					$temp = explode('assets/', $data['open_swf_file']);
					$data['open_swf_file'] = base_url('assets/'.$temp[1]);
					$data['ssl_swf_file'] = $data['open_swf_file'];
					array_push($assets, array('type' => 'swf'));
				}
				if($this->is_file_there($link."/".$file_index['backup']))
				{
					$data['open_backup_image'] = $this->get_first_file_match($link."/".$file_index['backup']);
					$temp = explode('assets/', $data['open_backup_image']);
					$data['open_backup_image'] = base_url('assets/'.$temp[1]);
					$data['ssl_backup_image'] = $data['open_backup_image'];
					$data['backup_image'] = $data['open_backup_image']; // deprecated; remove with care -CL
					array_push($assets, array('type' => 'backup'));
				}
					if($this->is_file_there($link."/".$file_index['loader']))
					{
						$data['open_gpa_image_file'] = $this->get_first_file_match($link."/".$file_index['loader']);
						$temp = explode('assets/', $data['open_gpa_image_file']);
						$data['open_gpa_image_file'] = base_url('assets/'.$temp[1]);
						$data['ssl_gpa_image_file'] = $data['open_gpa_image_file'];
						$data['is_gpa'] = true;
						array_push($assets, array('type' => 'loader'));
					}
				if($this->cup_model->files_ok($assets, $size, $builder_version, true, $data['force_html5']))
				{
					$data['tracking_off'] = true;
					$dimensions = explode('x', $size);
					$data['creative_width'] = $dimensions[0];
					$data['creative_height'] = $dimensions[1];
					$data['vl_creative_id'] = 0;
					$data['vl_campaign_id'] = 0;
					$data['is_hd'] = true;
					$data['no_engage'] = true;
					// TODO: no reference to version_id or campaign here. Where is this used, and does it need to link to the landing page?
					
					$html = $this->load->view('dfa/vl_hd_view_04',$data,true);
					//print_r(json_decode($data['variables_js']));
					echo $html;
				}
			}
		}
	}

	public function get_new_local_ad($builder_version, $size, $is_html5 = "0", $adset_version_id = null)
	{
		$assets=array();
		$key = $this->session->userdata('key');
		if($key){
			$path = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/files/variables.json';
			$data['variables_data_obj'] = json_encode(file_get_contents($path));
			$versions_array = $this->variables_model->get_versions_from_builder_version($builder_version);
			$blob_version = $versions_array[0]['blob_version'];
			$data['gpa_version'] = $versions_array[0]['rich_grandpa_version'];
		}
		//$data['open_xml_file'] = '';
		//$data['ssl_xml_file'] = '';
		//print '<pre>'; print_r($data); print '</pre>';
		if(is_null($data['variables_data_obj'])){
			echo "couldn't find variables data";
		}else{
			$file_array = array('160x600' => array('swf' => '*160x600.swf', 'js' => '*160x600.js', 'backup' => '*backup_160x600.*', 'loader' => '*loader_image_160x600.*'),
							'300x250' => array('swf' => '*300x250.swf', 'js' => '*300x250.js', 'backup' => '*backup_300x250.*', 'loader' => '*loader_image_300x250.*'),
							'336x280' => array('swf' => '*336x280.swf', 'js' => '*336x280.js', 'backup' => '*backup_336x280.*', 'loader' => '*loader_image_336x280.*'),
							'728x90'  => array('swf' => '*728x90.swf',  'js' => '*728x90.js',  'backup' => '*backup_728x90.*',  'loader' => '*loader_image_728x90.*'),
							'320x50'  => array('swf' => '*320x50.swf',  'js' => '*320x50.js',  'backup' => '*backup_320x50.*', 'loader' => '*loader_image_320x50.*'),
							'468x60'  => array('backup' => '*backup_468x60.*',  'loader' => '*loader_image_468x60.*'));
			if(array_key_exists($size, $file_array))
			{

				$data['force_html5'] = ($is_html5 == '1') ? "true" : "false";
				$data['open_fullscreen_file'] = '';
				$data['ssl_fullscreen_file'] = '';
				$assets = array();
				array_push($assets, array('type' => 'variables_data'));///if we're in here, we have variables
				$key = $this->session->userdata('key');
				$print = '';
				if($key)
				{
					$campaign_and_advertiser_details = $this->cup_model->get_campaign_and_advertiser_by_version_id($adset_version_id);
					if ($campaign_and_advertiser_details != NULL)
					{
						$data['campaign_id'] = $campaign_and_advertiser_details['campaign_id'];
						$data['advertiser_id'] = $campaign_and_advertiser_details['advertiser_id'];
					}
					
					$file_index = $file_array[$size];
					$link = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/files';
					$structured_link = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/structured_files';
					if($html5_creative_set = new html5_creative_set($this, $structured_link, $size, $builder_version))
					{
						$creative_markup = $html5_creative_set->get_creative_markup_for_size($size);
						$data['html5_initialization'] = $creative_markup['initialization'];
						$data['html5_setup'] = $creative_markup['setup'];
					}
					$path = base_url('assets/creative_upload/'.$key.'/files');
					if($this->is_file_there($link."/*fullscreen*"))
					{
						$data['open_fullscreen_file'] = $this->get_first_file_match($link."/*fullscreen*");
						$temp = explode('assets/', $data['open_fullscreen_file']);
						$data['open_fullscreen_file'] = base_url('assets/'.$temp[1]);
						$data['ssl_fullscreen_file'] = $data['open_fullscreen_file'];
					}
					$data['open_swf_file'] = '';
					$data['ssl_swf_file'] = '';
					$data['open_backup_image']= '';
					$data['ssl_backup_image']= '';
					$data['backup_image']= '';
					$data['open_gpa_image_file'] = '';
					$data['ssl_gpa_image_file'] = '';
					$data['is_gpa'] = false;
					if(constant('ENVIRONMENT') !== 'production')
					{
						$this->cup_model->get_dev_tracking_pixel_addresses($data);
					}
					if(isset($file_index['js']) && $this->is_file_there($link."/".$file_index['js']))
					{
						$data['open_js_file'] = $this->get_first_file_match($link."/".$file_index['js']);
						$temp = explode('assets/', $data['open_js_file']);
						$data['open_js_file'] = base_url('assets/'.$temp[1]);
						$data['ssl_js_file'] = $data['open_js_file'];
						array_push($assets, array('type' => 'js'));
					}
					if(isset($file_index['swf']) && $this->is_file_there($link."/".$file_index['swf']))
					{
						$data['open_swf_file'] = $this->get_first_file_match($link."/".$file_index['swf']);
						$temp = explode('assets/', $data['open_swf_file']);
						$data['open_swf_file'] = base_url('assets/'.$temp[1]);
						$data['ssl_swf_file'] = $data['open_swf_file'];
						array_push($assets, array('type' => 'swf'));
					}
					if(isset($file_index['backup']) && $this->is_file_there($link."/".$file_index['backup']))
					{
						$data['open_backup_image'] = $this->get_first_file_match($link."/".$file_index['backup']);
						$temp = explode('assets/', $data['open_backup_image']);
						$data['open_backup_image'] = base_url('assets/'.$temp[1]);
						$data['ssl_backup_image'] = $data['open_backup_image'];
						$data['backup_image'] = $data['open_backup_image']; // deprecated; remove with care -CL
						array_push($assets, array('type' => 'backup'));
					}
					if(isset($file_index['loader']) && $this->is_file_there($link."/".$file_index['loader']))
					{
						$data['open_gpa_image_file'] = $this->get_first_file_match($link."/".$file_index['loader']);
						$temp = explode('assets/', $data['open_gpa_image_file']);
						$data['open_gpa_image_file'] = base_url('assets/'.$temp[1]);
						$data['ssl_gpa_image_file'] = $data['open_gpa_image_file'];
						$data['is_gpa'] = true;
						array_push($assets, array('type' => 'loader'));
					}
					if($this->cup_model->files_ok($assets, $size, $builder_version, true, $data['force_html5']))
					{
						$data['tracking_off'] = true;
						$dimensions = explode('x', $size);
						$data['creative_width'] = $dimensions[0];
						$data['creative_height'] = $dimensions[1];
						$data['vl_creative_id'] = 0;
						$data['vl_campaign_id'] = 0;
						$data['is_hd'] = true;
						$data['no_engage'] = true;
						if(isset($adset_version_id) && $adset_version_id != '' && $adset_version_id != '0')
						{
							$data['landing_page'] = $this->al_model->get_adset_landing_page_by_version_id($adset_version_id);
						}
						
						if(isset($adset_version_id) && $adset_version_id != '' && $adset_version_id != '0' && strpos($data['variables_data_obj'],"dynamicGeoDefault"))
						{
							$data['dynamic_geo_default'] = TRUE;
							$messages_data = $this->geo_in_ads_model->get_messages_data_for_adset_version($adset_version_id);
							
							if ($messages_data)
							{
								$data['messages_data'] = $messages_data;
							}
						}
						
						$html = $this->load->view('dfa/vl_hd_view_'.$blob_version,$data,true);
						echo $html;
					}
				}
			}
		}
	}
	
	public function load_variables_ui()
	{
		$key = $this->session->userdata('key');
		$builder_version = $this->input->post('builder_version');
		$adset_version = $this->input->post('adset_version');
		$all_builder_versions = $this->variables_model->get_all_variables_versions();
		$is_html5_builder_version = html5_creative_set::is_html5_builder_version($builder_version);
		$is_internal_rich_media_builder_version = html5_creative_set::is_internal_rich_media_builder_version($builder_version);
		if(empty($builder_version))
		{
			if(!empty($adset_version))
			{
				$builder_version = $this->cup_model->get_builder_version_by_version_id($adset_version);
			}
			else
			{
				// use default builder version (highest version number)
				$builder_version = $all_builder_versions[0]['builder_version'];
			}
		}
		if($key)
		{ //if we find a key in the session, we know we have files to look for.
			$link = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/files';
			$structured_link = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/structured_files';
			$file_array = array('static' => array('fullscreen' => '*fullscreen*'),
								'160x600' => array('swf' => '*160x600.swf', 'backup' => '*backup_160x600.*', 'loader_image' => '*loader_image_160x600.*'),
								'300x250' => array('swf' => '*300x250.swf', 'backup' => '*backup_300x250.*', 'loader_image' => '*loader_image_300x250.*'),
								'336x280' => array('swf' => '*336x280.swf', 'backup' => '*backup_336x280.*', 'loader_image' => '*loader_image_336x280.*'),
								'728x90'  => array('swf' => '*728x90.swf',  'backup' => '*backup_728x90.*',  'loader_image' => '*loader_image_728x90.*'));
			$data['missing_array'] = array('static' => array('fullscreen' => 1),
										   '160x600' => array('swf' => 1, 'backup' => 1, 'loader_image' => 1),
										   '300x250' => array('swf' => 1, 'backup' => 1, 'loader_image' => 1),
										   '336x280' => array('swf' => 1, 'backup' => 1, 'loader_image' => 1),
										   '728x90'  => array('swf' => 1, 'backup' => 1, 'loader_image' => 1));
			$has_swf = false;
			$is_missing_all_files = true;
			foreach($file_array as $ad_size => $file_types)
			{
				foreach($file_types as $file_type => $glob_pattern)
				{
					if($this->is_file_there($link."/".$glob_pattern))
					{
						$is_missing_all_files = false;
						$data['missing_array'][$ad_size][$file_type] = 0;
						if($file_type === 'swf')
						{
							$has_swf = true;
						}
					}
				}
			}
			$data['all_files'] = 1;
			foreach($data['missing_array'] as $ad_size => $file_types)
			{
				foreach($file_types as $file_type => $is_missing)
				{
					if($is_missing == 1)
					{
						$data['all_files'] = 0;
					}
				}
			}

			$is_html5_creative = false;
			$html5_creative_set = new html5_creative_set($this, $structured_link, null, $builder_version);
			if(!$has_swf)
			{
				$recommended_html5_builder_version = $html5_creative_set->get_recommended_builder_version();
				$is_html5_creative = (bool)$recommended_html5_builder_version;
			}

			if($is_html5_builder_version)
			{
				$data['missing_array'] = $html5_creative_set->list_all_missing_files();
			}

			$data['recommended_builder_version'] = null;
			if($is_html5_creative)
			{
				$data['recommended_builder_version'] = $recommended_html5_builder_version;
			}
			elseif(!$is_missing_all_files)
			{
				// recommend newest non-html5 builder version
				foreach($all_builder_versions as $builder_version_info)
				{
					if(!html5_creative_set::is_html5_builder_version($builder_version_info['builder_version']))
					{
						$data['recommended_builder_version'] = $builder_version_info['builder_version'];
						break;
					}
				}
			}

			$data['hide_variables_ui'] = $is_internal_rich_media_builder_version;

			$form_ui = $this->load->view('creative_upload/variables_ui_edit_form_view', $data, true);
			echo $form_ui;
		}
		else
		{
			echo 'Error 4421: Session key expired.';
		}
	}

	public function update_variables_file()
	{
		$key = $this->session->userdata('key');
		if($key)
		{
			$contents = $_POST['variables'];
			if($contents)
			{
				$link = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/files';
				if($this->is_file_there($link."/*variables.*"))
				{
					$path = $this->get_first_file_match($link."/*variables*");
					$success = file_put_contents($path, $contents);
					if($success)
					{
						echo "Success";
					}
					else
					{
						echo "Failed to write to variables file";
					}
				}
				else
				{
					echo "Variables file not found";
				}
			}
			else
			{
				echo "Invalid variables contents";
			}
		}
		else
		{
			echo "Session key not found";
		}
	}

	public function can_publish_version()
	{
		$ok = "false";

		if(array_key_exists('version_id', $_POST))
		{
			$version_id = $_POST['version_id'];
			$adsets =  $this->variables_model->get_adset_by_id($version_id) ;

			$variables_data = $adsets[0]['variables_data'];
			$builder_version = $adsets[0]['builder_version'];
			$asset_array = $this->cup_model->can_publish_version($version_id);

			if($asset_array)
			{
				if(html5_creative_set::is_html5_builder_version($builder_version))
				{
					$html5_creative_set = new html5_creative_set($this, $asset_array, null, $builder_version);
					if($html5_creative_set->can_publish_at_least_one_size())
					{
						$ok = 'true';
					}
				}
				else if($variables_data) // Flash builder versions
				{
					// plot out all the assets by size and type
					$file_array = array(
						'160x600' => array('js' => 0, 'swf' => 0, 'backup' => 0, 'loader' => 0),
						'300x250' => array('js' => 0, 'swf' => 0, 'backup' => 0, 'loader' => 0),
						'336x280' => array('js' => 0, 'swf' => 0, 'backup' => 0, 'loader' => 0),
						'728x90'  => array('js' => 0, 'swf' => 0, 'backup' => 0, 'loader' => 0),
						'320x50'  => array('js' => 0, 'swf' => 0, 'backup' => 0, 'loader' => 0),
						'468x60'  => array('backup' => 0, 'loader' => 0)
					);

					foreach($asset_array as $asset)
					{
						if($asset['type'] == 'js' OR $asset['type'] == 'swf' OR $asset['type'] == 'backup' OR $asset['type'] == 'loader')
						{
							$file_array[$asset['size']][$asset['type']] = 1;
						}
					}

					// find at lease one size that is ready to publish
					foreach($file_array as $size => $types)
					{
						if($types['backup'] == 1)
						{
							if($size == '320x50' AND ($types['js'] == 1 OR $types['loader'] == 1))
							{
								$ok = "true";
							}
							else if($size != '320x50' AND ($types['swf'] == 1 OR $types['loader'] == 1 OR $types['js'] == 1))
							{
								$ok = "true";
							}
						}
					}
				}
			}
		}

		echo $ok;
	}

	public function save_cdn_assets_to_local_dir($version="0")
	{
		$key = $this->session->userdata('key');
		$path = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/files/';
		$structured_path = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/structured_files/';
		//should clear all files in there first
		$files = glob($path.'*'); // get all file names
		foreach($files as $file){ // iterate files
			if(is_file($file))
			unlink($file); // delete file
		}

		$file_type_name_map = array('swf'=>'size',
									'js'=>'size',
									'loader'=>'loader_image_size',
									'backup'=>'backup_size',
									'fullscreen'=>'');
		


		$assets = $this->cup_model->get_assets_by_adset($version, '%');
		//echo "before<br>";
		//print '<pre>'; print_r($assets); print '</pre>';
		
		
		function make_unique_filename($file_path)
		{
			$original_file_info = pathinfo($file_path);
			$file_suffix_number = 0;
			while(file_exists($file_path))
			{
				$file_suffix_number++;
				$file_path = "{$original_file_info['dirname']}/{$original_file_info['filename']}_{$file_suffix_number}.{$original_file_info['extension']}";
			}

			return $file_path;
		}

		if($key){
			foreach($assets as $index => &$asset){
				if(empty($asset['open_uri']))
				{
					// ignore "assets" that are not files on the CDN
					continue;
				}
				$asset['get_contents_success'] = file_get_contents($asset['open_uri']);
				$html5_local_path_pattern = "/^.*creatives\/v{$version}\//";

				if(preg_match($html5_local_path_pattern, $asset['open_uri']))
				{
					$asset['nickname'] = basename($asset['open_uri']);
					$local_file_path = make_unique_filename($path . $asset['nickname']);

					$html5_local_path = preg_replace($html5_local_path_pattern, '', $asset['open_uri']);
					$symlink_path = $structured_path . '/' . $html5_local_path;
					$file_directory = dirname($symlink_path);
					if(!is_dir($file_directory))
					{
						mkdir($file_directory, 0777, true); // make the directory recursively
					}
					system("ln -s \"$local_file_path\" \"$symlink_path\"");
				}
				else
				{
					$asset['nickname'] = preg_replace('/size/', $asset['size'], $file_type_name_map[$asset['type']]) . '.' . $asset['extension'];
					$local_file_path = make_unique_filename($path . $asset['nickname']);
				}
				$success = file_put_contents($local_file_path, $asset['get_contents_success']);
			}
			echo "ok";
			//print '<pre>'; print_r($assets); print '</pre>';
		}else{
			echo 'session no existie';
		}
		

		
		//echo json_encode($assets);
	}

	public function write_local_variables_json(){
		$key = $this->session->userdata('key');
		if($key){
			$contents = $_POST['vars'];
			$path = $_SERVER['DOCUMENT_ROOT'].'/assets/creative_upload/'.$key.'/files/variables.json';
			$success = file_put_contents($path, $contents);
			echo($key);
			print '<pre>'; print_r($success); print '</pre>';

		}
	}
	public function ajax_get_select2_campaigns()
	{
		$campaign_array = array('results' => array(), 'more' => false);
		if($_SERVER['REQUEST_METHOD'] === 'POST' && $this->tank_auth->is_logged_in())
		{
			$post_array = $this->input->post();
			if(array_key_exists('q', $post_array) AND array_key_exists('page', $post_array) AND array_key_exists('page_limit', $post_array) AND array_key_exists('type', $post_array))
			{
				if($post_array['page'] == 1 && $post_array['type'] !== "campaign_link_select" && $post_array['q'] === '%')
				{
					$campaign_array['results'][] = array(
						'id' => 'none',
						'text' => 'All Campaigns',
						'campaign' => null,
						'advertiser' => null
						);
				}
				$campaign_response = select2_helper($this->cup_model, 'get_campaigns_for_select2', $post_array);
				if (!empty($campaign_response['results']) && !$campaign_response['errors'])
				{
					$campaign_array['more'] = $campaign_response['more'];
					for($i = 0; $i < $campaign_response['real_count']; $i++)
					{
						$campaign_array['results'][] = array(
							'id' => $campaign_response['results'][$i]['id'],
							'text' => $campaign_response['results'][$i]['text'],
							'campaign' => $campaign_response['results'][$i]['campaign_name'],
							'advertiser' => $campaign_response['results'][$i]['advertiser_name']
							);
					}
				}
				
			}
			echo json_encode($campaign_array);
		}
		else
		{
			show_404();
		}
	}

	public function check_uploaded_file($file)
	{
		if($file->type == 'text/html')
		{
			$this->process_swiffy_html_files($file);
		}

		return $file;
	}

	/**
	 * @param $html5_creative_set html5_creative_set
	 * @param $existing_arran array of the type created in find_variables, updated by reference
	 * @return array asset info, or boolean false
	 */
	private function load_html5_creative_assets($version_id, $html5_creative_set, &$existing_array)
	{
		$loaded_assets = $html5_creative_set->load_all_assets_to_cdn($version_id, function($version_id, $file_info) {
			$loaded_asset = pathinfo($file_info['path']);

			if(empty($loaded_asset['extension']))
			{
				// unwanted file, like DS_Store
				return false;
			}

			$uploaded_result = $this->cdn_model->load_asset_with_directory_structure($file_info['path'], $file_info['cdn_path']);

			$loaded_asset['type'] = $file_info['type'];
			$loaded_asset['weight'] = $this->format_bytes(filesize($file_info['path']));
			$loaded_asset['size'] = $file_info['size'];
			$loaded_asset['open_uri'] = $uploaded_result['open_uri'];
			$loaded_asset['ssl_uri'] = $uploaded_result['ssl_uri'];

			return $loaded_asset;
		});

		foreach($loaded_assets as $asset)
		{
			if($asset) // skip assets that returned false
			{
				$file_index = "{$asset['size']}_{$asset['type']}";
				$existing_array[$file_index] = $asset;
			}
		}
	}

	private function process_swiffy_hover_html_files(&$file)
	{
		$swiffy_options = [
			'transparent' => TRUE,
		];
		if($swiffyobject_js = $this->swiffy->get_js_from_swiffy_html(file_get_contents($file->path), 'hover_', $swiffy_options))
		{
			$js_file_name = str_replace('.html', '.js', $file->name);
			$js_file_path = str_replace($file->name, $js_file_name, $file->path);
			if(file_put_contents($js_file_path, $swiffyobject_js))
			{
				$file->type = 'application/javascript';
				$file->size = strlen($swiffyobject_js);
				$file->url = str_replace(rawurlencode($file->name), rawurlencode($js_file_name), $file->url);
				$file->delete_url = str_replace(rawurlencode($file->name), rawurlencode($js_file_name), $file->delete_url);
				$file->name = $js_file_name;
				$file->path = $js_file_path;
			}
		}
	}

	private function process_swiffy_html_files(&$file)
	{
		if($swiffyobject_js = $this->swiffy->get_js_from_swiffy_html(file_get_contents($file->path)))
		{
			$js_file_name = str_replace('.html', '.js', $file->name);
			$js_file_path = str_replace($file->name, $js_file_name, $file->path);
			if(file_put_contents($js_file_path, $swiffyobject_js))
			{
				$file->type = 'application/javascript';
				$file->size = strlen($swiffyobject_js);
				$file->url = str_replace(rawurlencode($file->name), rawurlencode($js_file_name), $file->url);
				$file->delete_url = str_replace(rawurlencode($file->name), rawurlencode($js_file_name), $file->delete_url);
				$file->name = $js_file_name;
				$file->path = $js_file_path;
			}
		}
	}

	private function format_bytes($a_bytes)
	{
		if ($a_bytes < 1024)
		{
			return $a_bytes .' B';
		}
		elseif ($a_bytes < 1048576)
		{
			return round($a_bytes / 1024, 2) .' KB';
		}
		elseif ($a_bytes < 1073741824)
		{
			return round($a_bytes / 1048576, 2) . ' MB';
		}
		elseif ($a_bytes < 1099511627776)
		{
			return round($a_bytes / 1073741824, 2) . ' GB';
		}
		elseif ($a_bytes < 1125899906842624)
		{
			return round($a_bytes / 1099511627776, 2) .' TB';
		}
		elseif ($a_bytes < 1152921504606846976)
		{
			return round($a_bytes / 1125899906842624, 2) .' PB';
		}
		elseif ($a_bytes < 1180591620717411303424)
		{
			return round($a_bytes / 1152921504606846976, 2) .' EB';
		}
		elseif ($a_bytes < 1208925819614629174706176)
		{
			return round($a_bytes / 1180591620717411303424, 2) .' ZB';
		}
		else
		{
			return round($a_bytes / 1208925819614629174706176, 2) .' YB';
		}
	}
	
	public function get_gallery_url()
	{
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->tank_auth->is_logged_in())
		{
			$return = array('is_success' => true, 'errors' => "", 'vanity_string' => "", 'base_url' => "");
			$version_id = $this->input->post('version_id');
			$campaign_id = $this->input->post('campaign_id');
			
			if($version_id !== false && $version_id !== "")
			{
				$vanity_info = $this->cup_model->get_gallery_link_vanity_info($version_id);
				if($vanity_info !== false)
				{
					$return['vanity_string'] = url_title($vanity_info['name']).'v'.$vanity_info['version'];
					$return['encoded_adset_version_id']  = base64_encode(base64_encode(base64_encode($version_id)));
					if($campaign_id !== false && $campaign_id !== 'none' && $campaign_id !== '')
					{
						$sales_person_id = $this->cup_model->get_advertiser_by_campaign_id($campaign_id);
						if ($sales_person_id)
						{
							$cname_to_use = $this->cup_model->get_version_banner_intake_cname($version_id);
							if($cname_to_use == false)
							{
								$cname_to_use = $this->vl_auth_model->get_cname_for_user($sales_person_id);
							}
							$domain = '.'.$this->tank_auth->get_domain_without_cname();

							if (!empty($cname_to_use) && !empty($domain))
							{
								$protocol = ENABLE_HOOKS ? 'https' : 'http';
								$return['base_url'] = $protocol.'://'.$cname_to_use.$domain.'/';
							}
						}
					}
					else
					{
						$cname_to_use = $this->cup_model->get_version_banner_intake_cname($version_id);
						if($cname_to_use != false)
						{
							$domain = '.'.$this->tank_auth->get_domain_without_cname();
							$protocol = ENABLE_HOOKS ? 'https' : 'http';
							$return['base_url'] = $protocol.'://'.$cname_to_use.$domain.'/';							
						}
					}
				}
				else
				{
					$return['is_success'] = false;
					$return['errors'] = "Error 012528: unable to get vanity url info";
				}
			}
			else
			{
				$return['is_success'] = false;
				$return['errors'] = "Error 012529: invalid post data recieved";
			}
			echo json_encode($return);
		}
		else
		{
			show_404();
		}
	}

	public function save_show_for_io()
	{
		if ($this->tank_auth->is_logged_in() || $this->does_approval_page_cookie_approve())
		{
			$show_for_io = $_GET['show_for_io'];
			$adset_version_id = $_GET['adset_version_id'];
			
			if ($adset_version_id)
			{
				$result = $this->cup_model->save_show_for_io_value_for_adset_version($adset_version_id, $show_for_io);
				
				if ($result)
				{
					$return['status'] = 'success';
				}
				else
				{
					$return['status'] = 'fail';
				}
				
				echo json_encode($return);
			}
		}
	}
	
	public function get_show_for_io_value()
	{
		if ($this->tank_auth->is_logged_in() || $this->does_approval_page_cookie_approve())
		{
			$adset_version_id = $_GET['adset_version_id'];
			
			if ($adset_version_id)
			{
				$result = $this->cup_model->get_show_for_io_value_for_adset_version($adset_version_id);
				
				if ($result)
				{
					$return['data'] = $result['show_for_io'];
					if($result['internally_approved_updated_user'] != '')
					{
						$return['username'] = $result['internally_approved_updated_user'];
						$return['updated_date'] = $result['internally_approved_updated_timestamp'];
					}
					else
					{
						$return['username'] = $result['username'];
						$return['updated_date'] = $result['updated_date'];
					}	
				}
				else
				{
					$return['data'] = '';
				}
				
				echo json_encode($return);
			}
		}
	}

	private function does_approval_page_cookie_approve()
	{
		if(isset($_COOKIE[constant('ENVIRONMENT')."-gp-approve"]))
		{
			$approval_value = $_COOKIE[constant('ENVIRONMENT')."-gp-approve"];
			if($approval_value == constant('ENVIRONMENT')."_ADMIN" || $approval_value == constant('ENVIRONMENT')."_OPS")
			{
				return true;
			}
		}
		return false;
	}

	//removing unwanted directories and files after we are done with them
	private function rmdir_recursive($dir)
	{
		$files = scandir($dir);
		array_shift($files);    // remove '.' from array
		array_shift($files);    // remove '..' from array

		foreach ($files as $file) {
			$file = $dir . '/' . $file;
			if (is_dir($file)) {
				$this->rmdir_recursive($file);
			} else {
				unlink($file);
			}
		}
		rmdir($dir);
	}

	public function insert_new_variation()
	{
		$response_array = array('is_success' => true, 'data' => array());
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$allowed_roles = array('admin', 'ops', 'creative');
			$post_variables = array('variation_name', 'adset', 'parent_version', 'campaign_id');

			$response = vl_verify_ajax_call($allowed_roles, $post_variables);
			if($response['is_success']) 
			{
				$variation_name = $this->input->post('variation_name', true);
				$adset_id = $this->input->post('adset', true);
				$parent_version = $this->input->post('parent_version', true);
				$campaign_id = $this->input->post('campaign_id', true);
				$campaign_id = ($campaign_id  == "none" OR $campaign_id == "") ? null : $campaign_id;
				$user_id = $this->tank_auth->get_user_id();


				$insert_variation = $this->cup_model->insert_new_variation($user_id, $adset_id, $parent_version, $variation_name, $campaign_id);
				if($insert_variation['success'] == false)
				{
					$response_array['is_success'] = false;
					$response_array['data'] = $insert_variation['err_msg'];
				}
				$response_array['data'] = $insert_variation['data'];
			}
			else
			{
				$response_array['is_success'] = false;
				$response_array['data'] = "Error #682812: Failed to validate new variation request.";

			}
		}
		else
		{
			$response_array['is_success'] = false;
			$response_array['data'] = "Error #682813: Failed to validate new variation request.";
		}
		echo json_encode($response_array);
		return;
	}

	public function rename_variation()
	{
		$response_array = array('is_success' => true, 'data' => array());
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$allowed_roles = array('admin', 'ops', 'creative');
			$post_variables = array('variation', 'new_variation_name', 'parent_version');

			$response = vl_verify_ajax_call($allowed_roles, $post_variables);
			if($response['is_success']) 
			{
				$variation_name = $this->input->post('new_variation_name', true);
				$variation_id = $this->input->post('variation', true);
				$parent_version = $this->input->post('parent_version', true);


				$edit_variation_name = $this->cup_model->rename_variation($variation_id, $variation_name, $parent_version);
				if($edit_variation_name['success'] == false)
				{
					$response_array['is_success'] = false;
					$response_array['data'] = $edit_variation_name['err_msg'];
				}
			}
			else
			{
				$response_array['is_success'] = false;
				$response_array['data'] = "Error #611222: Failed to validate variation rename request.";

			}
		}
		else
		{
			$response_array['is_success'] = false;
			$response_array['data'] = "Error #611223: Failed to validate variation rename request.";
		}
		echo json_encode($response_array);
		return;		
	}
}
