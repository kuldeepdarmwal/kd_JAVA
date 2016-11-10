<?php
class Variables extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->helper(array('url', 'select2_helper'));
		$this->load->model('variables_model');
		$this->load->library(array('session',  'tank_auth'));
	}

	public function index(){
		$data['ui_sections'] = $this->special_json_parse($this->variables_model->get_latest_variables_config());
		$builder_versions = json_encode($this->variables_model->get_all_variables_versions());
		$data['all_versions_json'] = json_encode($this->variables_model->get_all_variables_versions());
		$this->load->view('variables/variables_ui_view_3',$data);
	}

	public function get_builder_versions()
	{
		echo json_encode($this->variables_model->get_all_variables_versions());

	}

	private function special_json_parse($text){
		$parsedText = str_replace(chr(10), "", $text);
		return str_replace(chr(13), "", $parsedText);
	}

	public function get_config($builder_version){
		echo $this->variables_model->get_variables_config_by_version($builder_version);
	}

	public function save_template(){
		if($_POST["t_id"] == "save_as_new"){//new template
			$this->variables_model->insert_template($_POST["t_name"],$_POST["variables_data"],$_POST["b_vers"]);
		}else{//save over existing
			$this->variables_model->update_template($_POST["t_id"],$_POST["variables_data"],$_POST["b_vers"]);
		}
	}

	public function save_adset(){
		$user_id = $this->tank_auth->get_user_id();
		$this->variables_model->update_adset($_POST["a_v_id"], $_POST["variables_data"], $_POST["b_vers"], $user_id);
	}


	public function get_modal(){
		$data = array();
		$builder_version = $_POST["b_vers"];
		switch ($_POST["modal_type"]) {
			case 'save_template':
				$data['all_templates'] = $this->variables_model->get_all_templates($builder_version);
				//print '<pre>'; print_r($data['all_templates']); print '</pre>';
				$this->load->view('variables/variables_save_template_modal_view',$data);
				break;
			case 'open_template':
				$data['all_templates'] = $this->variables_model->get_all_templates($builder_version);
				$this->load->view('variables/variables_open_template_modal_view',$data);
				break;
			case 'save_adset':
				$this->load->view('variables/variables_save_adset_modal_view',$data);
				break;
			case 'open_adset':
				$this->load->view('variables/variables_open_adset_modal_view',$data);
				break;
		}
	}
	
	public function get_select2_versions()
	{
		$variables_array = array('results' => array(), 'more' => false);
		if($_SERVER['REQUEST_METHOD'] === 'POST' && $this->tank_auth->is_logged_in())
		{
			$post_array = $this->input->post();
			if(array_key_exists('q', $post_array) AND array_key_exists('page', $post_array) AND array_key_exists('page_limit', $post_array))
			{
				$variables_response = select2_helper($this->variables_model, 'get_variables_for_select2', $post_array);
								
				if (!empty($variables_response['results']) && !$variables_response['errors'])
				{
					$variables_array['more'] = $variables_response['more'];
					for($i = 0; $i < $variables_response['real_count']; $i++)
					{
						$variables_array['results'][] = array(
							'id' => $variables_response['results'][$i]['id'],
							'text' => $variables_response['results'][$i]['text']
							);
					}
				}
			}
			echo json_encode($variables_array);
		}
		else
		{
			show_404();
		}
	}

	public function fetch_template(){
		$result =  $this->variables_model->get_template_by_id($_POST["t_id"]) ;
		echo json_encode($result);
		//print '<pre>'; print_r($result); print '</pre>';
	}

	public function fetch_adset(){
		$result =  $this->variables_model->get_adset_by_id($_POST["a_v_id"]) ;
		echo json_encode($result);
		//print '<pre>'; print_r($result); print '</pre>';
	}

	public function fetch_js_variables_file_contents($adset_id){
		$adset_array = $this->variables_model->get_adset_by_id($adset_id);
		if(!is_null($adset_array[0]['builder_version'])){
			$result = null ;
		}else{
			$result = $this->variables_model->get_js_variable_file_contents($adset_id) ;
		}
			
		//print '<pre>'; print_r($result); print '</pre>';
		echo $result;
	}

	public function fetch_variables($adset_id){
		$adset_array = $this->variables_model->get_adset_by_id($adset_id);
		if(!is_null($adset_array[0]['builder_version'])){
			$result = $adset_array ;
		}else{
			$result[0] = array('builder_version'=>'js',
								'variables_data'=>$this->variables_model->get_js_variable_file_contents($adset_id)) ;
		}
			
		//print '<pre>'; print_r($result); print '</pre>';
		echo json_encode($result);
	}

	// example:  <domain>/variables/build_config_flash/135/702/200
	public function build_config_flash($version, $blob_version=NULL, $rich_grandpa_version=NULL, $name=''){
		$name = rawurldecode($name);
		echo "$version and $blob_version, named '$name'";
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer','creative_uploader');
			redirect('login');
		}
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		if ($role == 'ops' or $role == 'admin')
		{

			$config = array(
							array(	
								'section_type' => 'block',
								'section_title' => 'GENERAL',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "showRichMenuAlways",
														"field_id"=> "flashvars.showRichMenuAlways",
														"tooltip_copy"=> "here is some tooltip copy for this variable",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "closeButtonPadding",
														"field_id"=> "flashvars.closeButtonPadding",
														"tooltip_copy"=> "Change to 10 if Video or Map covers top right corner (for AdChoices)",
														"default_value"=>"15",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isHoverToClick",
														"field_id"=> "flashvars.isHoverToClick",
														"tooltip_copy"=> "Hovering over a button activates it. (Different from isHoverToPlayVideo.)",
														"default_value"=>"true",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isReplay",
														"field_id"=> "flashvars.isReplay",
														"tooltip_copy"=> "isReplay",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isGrandpaImageAlways",
														"field_id"=> "flashvars.isGrandpaImageAlways",
														"tooltip_copy"=> "Image Only Ad",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "hoverName",
														"field_id"=> "flashvars.hoverName",
														"tooltip_copy"=> "Hover Effect - shine or pagepeel or stroke or none",
														"default_value"=>"shine",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "dynamicGeoDefault",
														"field_id"=> "flashvars.dynamicGeoDefault",
														"tooltip_copy"=> "Show message set for zip code. Set default message only when you want to turn on this feature",
														"default_value"=> "",
														"is_hidden"=>""
													)
												),
							),
							array(
								'section_type' => 'block',
								'section_title' => 'VIDEO',
								'ui_elements' => array(
													array(
															"section_type"=> "field",
															"field_type"=>"normal_text",
															"field_label"=> "videoURL",
															"field_id"=> "flashvars.videoURL",
															"tooltip_copy"=> "videoURL",
															"default_value"=>"",
															"is_hidden"=>""
														),
													array(
														'section_type' => 'block',
														'section_title' => 'Video Settings',
														'ui_elements' => array(
																			array(
																				"section_type"=> "field",
																				"field_type"=>"boolean",
																				"field_label"=> "isHoverToPlayVideo",
																				"field_id"=> "flashvars.isHoverToPlayVideo",
																				"tooltip_copy"=> "Allow hovering anywhere over the ad to play the video after a 3 second countdown animation",
																				"default_value"=>"true",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"boolean",
																				"field_label"=> "isHoverToPlayVideoAllowedDuringFlashAnimation",
																				"field_id"=> "flashvars.isHoverToPlayVideoAllowedDuringFlashAnimation",
																				"tooltip_copy"=> "Is hovering to play the video allowed during the Flash animation.  If false, hovering countdown is only allowed after Flash animation completes.",
																				"default_value"=>"true",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"boolean",
																				"field_label"=> "isAutoLoadVideo",
																				"field_id"=> "flashvars.isAutoLoadVideo",
																				"tooltip_copy"=> "isAutoLoadVideo",
																				"default_value"=>"false",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"boolean",
																				"field_label"=> "autoPlay",
																				"field_id"=> "flashvars.autoPlay",
																				"tooltip_copy"=> "autoPlay",
																				"default_value"=>"true",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"boolean",
																				"field_label"=> "isPlayMuted",
																				"field_id"=> "flashvars.isPlayMuted",
																				"tooltip_copy"=> "isPlayMuted",
																				"default_value"=>"false",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "numAutoPlaySeconds",
																				"field_id"=> "flashvars.numAutoPlaySeconds",
																				"tooltip_copy"=> "numAutoPlaySeconds",
																				"default_value"=>"5",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"boolean",
																				"field_label"=> "scaleToFillFullscreenBG",
																				"field_id"=> "flashvars.scaleToFillFullscreenBG",
																				"tooltip_copy"=> "scaleToFillFullscreenBG",
																				"default_value"=>"false",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "fullscreenImageURL",
																				"field_id"=> "flashvars.fullscreenImageURL",
																				"tooltip_copy"=> "fullscreenImageURL",
																				"default_value"=>"",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "fullscreenImageURLSSL",
																				"field_id"=> "flashvars.fullscreenImageURLSSL",
																				"tooltip_copy"=> "fullscreenImageURLSSL",
																				"default_value"=>"",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "playButtonImageURL",
																				"field_id"=> "flashvars.playButtonImageURL",
																				"tooltip_copy"=> "playButtonImageURL",
																				"default_value"=>"http://ad.vantagelocal.com/buttons/white_20x20_play.png",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "playButtonImageURLSSL",
																				"field_id"=> "flashvars.playButtonImageURLSSL",
																				"tooltip_copy"=> "playButtonImageURLSSL",
																				"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/white_20x20_play.png",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "mobilePlayButtonImageURL",
																				"field_id"=> "flashvars.mobilePlayButtonImageURL",
																				"tooltip_copy"=> "mobilePlayButtonImageURL",
																				"default_value"=>"http://ad.vantagelocal.com/buttons/play_btn_white_mobile.png",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "mobilePlayButtonImageURLSSL",
																				"field_id"=> "flashvars.mobilePlayButtonImageURLSSL",
																				"tooltip_copy"=> "mobilePlayButtonImageURLSSL",
																				"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/play_btn_white_mobile.png",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "nonSslCountdownFlashAnimationBaseUrlForHoverToPlayVideo",
																				"field_id"=> "flashvars.nonSslCountdownFlashAnimationBaseUrlForHoverToPlayVideo",
																				"tooltip_copy"=> "Non-SSL base url to the countdown animation played during hovering for playing video.  The actual file names will have underscore, ad size, and .swf appended to it to make the full url. Example: http://scott.com/hover_animation will refernce file http://scott.com/hover_anmiation_336x280.swf (2.4 second duration will be cusomizable in the future.)",
																				"default_value"=>"http://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.r9.cf1.rackcdn.com/hover_countdown_animations/autoload",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "sslCountdownFlashAnimationBaseUrlForHoverToPlayVideo",
																				"field_id"=> "flashvars.sslCountdownFlashAnimationBaseUrlForHoverToPlayVideo",
																				"tooltip_copy"=> "SSL base url to the countdown animation played during hovering for playing video.  The actual file names will have underscore, ad size, and .swf appended to it to make the full url. Example: https://scott.com/hover_animation will refernce file https://scott.com/hover_anmiation_336x280.swf (2.4 second duration will be cusomizable in the future.)",
																				"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/hover_countdown_animations/autoload",
																				"is_hidden"=>""
																			)
																		)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Play Button Positions',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton300_x",
																				"field_id"=> "flashvars.playButton300_x",
																				'tooltip_copy' => "playButton300_x",
																				"default_value"=>"285",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton300_y",
																				"field_id"=> "flashvars.playButton300_y",
																				'tooltip_copy' => "playButton300_y",
																				"default_value"=>"236",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton320_x",
																				"field_id"=> "flashvars.playButton320_x",
																				'tooltip_copy' => "playButton320_x",
																				"default_value"=>"298",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton320_y",
																				"field_id"=> "flashvars.playButton320_y",
																				'tooltip_copy' => "playButton320_y",
																				"default_value"=>"25",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton336_x",
																				"field_id"=> "flashvars.playButton336_x",
																				'tooltip_copy' => "playButton336_x",
																				"default_value"=>"321",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton336_y",
																				"field_id"=> "flashvars.playButton336_y",
																				'tooltip_copy' => "playButton336_y",
																				"default_value"=>"266",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton728_x",
																				"field_id"=> "flashvars.playButton728_x",
																				'tooltip_copy' => "playButton728_x",
																				"default_value"=>"713",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton728_y",
																				"field_id"=> "flashvars.playButton728_y",
																				'tooltip_copy' => "playButton728_y",
																				"default_value"=>"76",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton160_x",
																				"field_id"=> "flashvars.playButton160_x",
																				'tooltip_copy' => "playButton160_x",
																				"default_value"=>"95",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton160_y",
																				"field_id"=> "flashvars.playButton160_y",
																				'tooltip_copy' => "playButton160_y",
																				"default_value"=>"560",
																				"is_hidden"=>""
																			)
																		)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Video Position',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video300_x",
																				"field_id"=> "flashvars.video300_x",
																				'tooltip_copy' => "video300_x",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video300_y",
																				"field_id"=> "flashvars.video300_y",
																				'tooltip_copy' => "video300_y",
																				"default_value"=>"43",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video336_x",
																				"field_id"=> "flashvars.video336_x",
																				'tooltip_copy' => "video336_x",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video336_y",
																				"field_id"=> "flashvars.video336_y",
																				'tooltip_copy' => "video336_y",
																				"default_value"=>"44",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video728_x",
																				"field_id"=> "flashvars.video728_x",
																				'tooltip_copy' => "video728_x",
																				"default_value"=>"260",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video728_y",
																				"field_id"=> "flashvars.video728_y",
																				'tooltip_copy' => "video728_y",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video160_x",
																				"field_id"=> "flashvars.video160_x",
																				'tooltip_copy' => "video160_x",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video160_y",
																				"field_id"=> "flashvars.video160_y",
																				'tooltip_copy' => "video160_y",
																				"default_value"=>"64",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Video Size',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoWidth300",
																				"field_id"=> "flashvars.videoWidth300",
																				'tooltip_copy' => "videoWidth300",
																				"default_value"=>"298",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoHeight300",
																				"field_id"=> "flashvars.videoHeight300",
																				'tooltip_copy' => "videoHeight300",
																				"default_value"=>"168",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoWidth336",
																				"field_id"=> "flashvars.videoWidth336",
																				'tooltip_copy' => "videoWidth336",
																				"default_value"=>"334",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoHeight336",
																				"field_id"=> "flashvars.videoHeight336",
																				'tooltip_copy' => "videoHeight336",
																				"default_value"=>"188",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoWidth728",
																				"field_id"=> "flashvars.videoWidth728",
																				'tooltip_copy' => "videoWidth728",
																				"default_value"=>"158",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoHeight728",
																				"field_id"=> "flashvars.videoHeight728",
																				'tooltip_copy' => "videoHeight728",
																				"default_value"=>"88",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoWidth160",
																				"field_id"=> "flashvars.videoWidth160",
																				'tooltip_copy' => "videoWidth160",
																				"default_value"=>"158",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoHeight160",
																				"field_id"=> "flashvars.videoHeight160",
																				'tooltip_copy' => "videoHeight160",
																				"default_value"=>"88",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Fullscreen',
														'ui_elements' => array(
															array(
																'section_type' => 'field',
																'field_type' => "normal_text",
																'field_label' => "fullscreenBackgroundColor",
																"field_id"=> "flashvars.fullscreenBackgroundColor",
																'tooltip_copy' => "fullscreenBackgroundColor",
																"default_value"=>"252525",
																"is_hidden"=>""
															),
															array(
																'section_type' => 'field',
																'field_type' => "normal_text",
																'field_label' => "forceFullscreenVideoWidth",
																"field_id"=> "flashvars.forceFullscreenVideoWidth",
																'tooltip_copy' => "forceFullscreenVideoWidth",
																"default_value"=>"800",
																"is_hidden"=>""
															),
															array(
																'section_type' => 'field',
																'field_type' => "normal_text",
																'field_label' => "forceFullscreenVideoHeight",
																"field_id"=> "flashvars.forceFullscreenVideoHeight",
																'tooltip_copy' => "forceFullscreenVideoHeight",
																"default_value"=>"450",
																"is_hidden"=>""
															),
														),
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Full Screen Button',
														'ui_elements' => array(
															array(
																"section_type"=> "field",
																"field_type"=>"boolean",
																"field_label"=> "enableFullscreenClickButton",
																"field_id"=> "flashvars.enableFullscreenClickButton",
																"tooltip_copy"=> "enableFullscreenClickButton",
																"default_value"=>"false",
																"is_hidden"=>""
															),
															array(
																"section_type"=> "field",
																"field_type"=>"boolean",
																"field_label"=> "showFullscreenClickButtonDuringAnimation",
																"field_id"=> "flashvars.showFullscreenClickButtonDuringAnimation",
																"tooltip_copy"=> "showFullscreenClickButtonDuringAnimation",
																"default_value"=>"true",
																"is_hidden"=>""
															),
															array(
																'section_type' => 'block',
																'section_title' => '300x250',
																'ui_elements' => array(
																	array(
																		'section_type' => 'field',
																		'field_type' => "normal_text",
																		'field_label' => "fullscreenClickButton300_x",
																		"field_id"=> "flashvars.fullscreenClickButton300_x",
																		'tooltip_copy' => "fullscreenClickButton300_x",
																		"default_value"=>"150",
																		"is_hidden"=>""
																	),
																	array(
																		'section_type' => 'field',
																		'field_type' => "normal_text",
																		'field_label' => "fullscreenClickButton300_y",
																		"field_id"=> "flashvars.fullscreenClickButton300_y",
																		'tooltip_copy' => "fullscreenClickButton300_y",
																		"default_value"=>"221",
																		"is_hidden"=>""
																	),
																	array(
																		"section_type"=> "field",
																		"field_type"=>"normal_text",
																		"field_label"=> "fullscreenClickButtonImageURL_300",
																		"field_id"=> "flashvars.fullscreenClickButtonImageURL_300",
																		"tooltip_copy"=> "fullscreenClickButtonImageURL_300",
																		"default_value"=>"http://ad.vantagelocal.com/buttons/300_fullscreen_click.png",
																		"is_hidden"=>""
																	),
																	array(
																		"section_type"=> "field",
																		"field_type"=>"normal_text",
																		"field_label"=> "fullscreenClickButtonImageURLSSL_300",
																		"field_id"=> "flashvars.fullscreenClickButtonImageURLSSL_300",
																		"tooltip_copy"=> "fullscreenClickButtonImageURLSSL_300",
																		"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/300_fullscreen_click.png",
																		"is_hidden"=>""
																	),
																	array(
																		"section_type"=> "field",
																		"field_type"=>"normal_text",
																		"field_label"=> "fullscreenClickButtonHoverImageURL_300",
																		"field_id"=> "flashvars.fullscreenClickButtonHoverImageURL_300",
																		"tooltip_copy"=> "fullscreenClickButtonHoverImageURL_300",
																		"default_value"=>"http://ad.vantagelocal.com/buttons/300_fullscreen_click_hover.png",
																		"is_hidden"=>""
																	),
																	array(
																		"section_type"=> "field",
																		"field_type"=>"normal_text",
																		"field_label"=> "fullscreenClickButtonHoverImageURLSSL_300",
																		"field_id"=> "flashvars.fullscreenClickButtonHoverImageURLSSL_300",
																		"tooltip_copy"=> "fullscreenClickButtonHoverImageURLSSL_300",
																		"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/300_fullscreen_click_hover.png",
																		"is_hidden"=>""
																	)
																)
															),
															array(
																'section_type' => 'block',
																'section_title' => '336x280',
																'ui_elements' => array(
																	array(
																		'section_type' => 'field',
																		'field_type' => "normal_text",
																		'field_label' => "fullscreenClickButton336_x",
																		"field_id"=> "flashvars.fullscreenClickButton336_x",
																		'tooltip_copy' => "fullscreenClickButton336_x",
																		"default_value"=>"168",
																		"is_hidden"=>""
																	),
																	array(
																		'section_type' => 'field',
																		'field_type' => "normal_text",
																		'field_label' => "fullscreenClickButton336_y",
																		"field_id"=> "flashvars.fullscreenClickButton336_y",
																		'tooltip_copy' => "fullscreenClickButton336_y",
																		"default_value"=>"251",
																		"is_hidden"=>""
																	),
																	array(
																		"section_type"=> "field",
																		"field_type"=>"normal_text",
																		"field_label"=> "fullscreenClickButtonImageURL_336",
																		"field_id"=> "flashvars.fullscreenClickButtonImageURL_336",
																		"tooltip_copy"=> "fullscreenClickButtonImageURL_336",
																		"default_value"=>"http://ad.vantagelocal.com/buttons/336_fullscreen_click.png",
																		"is_hidden"=>""
																	),
																	array(
																		"section_type"=> "field",
																		"field_type"=>"normal_text",
																		"field_label"=> "fullscreenClickButtonImageURLSSL_336",
																		"field_id"=> "flashvars.fullscreenClickButtonImageURLSSL_336",
																		"tooltip_copy"=> "fullscreenClickButtonImageURLSSL_336",
																		"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/336_fullscreen_click.png",
																		"is_hidden"=>""
																	),
																	array(
																		"section_type"=> "field",
																		"field_type"=>"normal_text",
																		"field_label"=> "fullscreenClickButtonHoverImageURL_336",
																		"field_id"=> "flashvars.fullscreenClickButtonHoverImageURL_336",
																		"tooltip_copy"=> "fullscreenClickButtonHoverImageURL_336",
																		"default_value"=>"http://ad.vantagelocal.com/buttons/336_fullscreen_click_hover.png",
																		"is_hidden"=>""
																	),
																	array(
																		"section_type"=> "field",
																		"field_type"=>"normal_text",
																		"field_label"=> "fullscreenClickButtonHoverImageURLSSL_336",
																		"field_id"=> "flashvars.fullscreenClickButtonHoverImageURLSSL_336",
																		"tooltip_copy"=> "fullscreenClickButtonHoverImageURLSSL_336",
																		"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/336_fullscreen_click_hover.png",
																		"is_hidden"=>""
																	)
																)
															),
															array(
																'section_type' => 'block',
																'section_title' => '728x90',
																'ui_elements' => array(
																	array(
																		'section_type' => 'field',
																		'field_type' => "normal_text",
																		'field_label' => "fullscreenClickButton728_x",
																		"field_id"=> "flashvars.fullscreenClickButton728_x",
																		'tooltip_copy' => "fullscreenClickButton728_x",
																		"default_value"=>"625.5",
																		"is_hidden"=>""
																	),
																	array(
																		'section_type' => 'field',
																		'field_type' => "normal_text",
																		'field_label' => "fullscreenClickButton728_y",
																		"field_id"=> "flashvars.fullscreenClickButton728_y",
																		'tooltip_copy' => "fullscreenClickButton728_y",
																		"default_value"=>"45",
																		"is_hidden"=>""
																	),
																	array(
																		"section_type"=> "field",
																		"field_type"=>"normal_text",
																		"field_label"=> "fullscreenClickButtonImageURL_728",
																		"field_id"=> "flashvars.fullscreenClickButtonImageURL_728",
																		"tooltip_copy"=> "fullscreenClickButtonImageURL_728",
																		"default_value"=>"http://ad.vantagelocal.com/buttons/728_fullscreen_click.png",
																		"is_hidden"=>""
																	),
																	array(
																		"section_type"=> "field",
																		"field_type"=>"normal_text",
																		"field_label"=> "fullscreenClickButtonImageURLSSL_728",
																		"field_id"=> "flashvars.fullscreenClickButtonImageURLSSL_728",
																		"tooltip_copy"=> "fullscreenClickButtonImageURLSSL_728",
																		"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/728_fullscreen_click.png",
																		"is_hidden"=>""
																	),
																	array(
																		"section_type"=> "field",
																		"field_type"=>"normal_text",
																		"field_label"=> "fullscreenClickButtonHoverImageURL_728",
																		"field_id"=> "flashvars.fullscreenClickButtonHoverImageURL_728",
																		"tooltip_copy"=> "fullscreenClickButtonHoverImageURL_728",
																		"default_value"=>"http://ad.vantagelocal.com/buttons/728_fullscreen_click_hover.png",
																		"is_hidden"=>""
																	),
																	array(
																		"section_type"=> "field",
																		"field_type"=>"normal_text",
																		"field_label"=> "fullscreenClickButtonHoverImageURLSSL_728",
																		"field_id"=> "flashvars.fullscreenClickButtonHoverImageURLSSL_728",
																		"tooltip_copy"=> "fullscreenClickButtonHoverImageURLSSL_728",
																		"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/728_fullscreen_click_hover.png",
																		"is_hidden"=>""
																	)
																)
															),
															array(
																'section_type' => 'block',
																'section_title' => '160x600',
																'ui_elements' => array(
																	array(
																		'section_type' => 'field',
																		'field_type' => "normal_text",
																		'field_label' => "fullscreenClickButton160_x",
																		"field_id"=> "flashvars.fullscreenClickButton160_x",
																		'tooltip_copy' => "fullscreenClickButton160_x",
																		"default_value"=>"80",
																		"is_hidden"=>""
																	),
																	array(
																		'section_type' => 'field',
																		'field_type' => "normal_text",
																		'field_label' => "fullscreenClickButton160_y",
																		"field_id"=> "flashvars.fullscreenClickButton160_y",
																		'tooltip_copy' => "fullscreenClickButton160_y",
																		"default_value"=>"571",
																		"is_hidden"=>""
																	),
																	array(
																		"section_type"=> "field",
																		"field_type"=>"normal_text",
																		"field_label"=> "fullscreenClickButtonImageURL_160",
																		"field_id"=> "flashvars.fullscreenClickButtonImageURL_160",
																		"tooltip_copy"=> "fullscreenClickButtonImageURL_160",
																		"default_value"=>"http://ad.vantagelocal.com/buttons/160_fullscreen_click.png",
																		"is_hidden"=>""
																	),
																	array(
																		"section_type"=> "field",
																		"field_type"=>"normal_text",
																		"field_label"=> "fullscreenClickButtonImageURLSSL_160",
																		"field_id"=> "flashvars.fullscreenClickButtonImageURLSSL_160",
																		"tooltip_copy"=> "fullscreenClickButtonImageURLSSL_160",
																		"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/160_fullscreen_click.png",
																		"is_hidden"=>""
																	),
																	array(
																		"section_type"=> "field",
																		"field_type"=>"normal_text",
																		"field_label"=> "fullscreenClickButtonHoverImageURL_160",
																		"field_id"=> "flashvars.fullscreenClickButtonHoverImageURL_160",
																		"tooltip_copy"=> "fullscreenClickButtonHoverImageURL_160",
																		"default_value"=>"http://ad.vantagelocal.com/buttons/160_fullscreen_click_hover.png",
																		"is_hidden"=>""
																	),
																	array(
																		"section_type"=> "field",
																		"field_type"=>"normal_text",
																		"field_label"=> "fullscreenClickButtonHoverImageURLSSL_160",
																		"field_id"=> "flashvars.fullscreenClickButtonHoverImageURLSSL_160",
																		"tooltip_copy"=> "fullscreenClickButtonHoverImageURLSSL_160",
																		"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/160_fullscreen_click_hover.png",
																		"is_hidden"=>""
																	)
																)
															)
														)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Player Colors',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timelineDownloadColor",
																				"field_id"=> "flashvars.timelineDownloadColor",
																				'tooltip_copy' => "timelineDownloadColor",
																				"default_value"=>"168DFF",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timelineBackgroundColor",
																				"field_id"=> "flashvars.timelineBackgroundColor",
																				'tooltip_copy' => "timelineBackgroundColor",
																				"default_value"=>"004993",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsColor",
																				"field_id"=> "flashvars.controlsColor",
																				'tooltip_copy' => "controlsColor",
																				"default_value"=>"888888",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsHighlightColor",
																				"field_id"=> "flashvars.controlsHighlightColor",
																				'tooltip_copy' => "controlsHighlightColor",
																				"default_value"=>"BBBBBB",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playerBackgroundColor",
																				"field_id"=> "flashvars.playerBackgroundColor",
																				'tooltip_copy' => "playerBackgroundColor",
																				"default_value"=>"000000",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Video Controls',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useTimeline",
																				"field_id"=> "flashvars.useTimeline",
																				'tooltip_copy' => "useTimeline",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useTimeDisplay",
																				"field_id"=> "flashvars.useTimeDisplay",
																				'tooltip_copy' => "useTimeDisplay",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useSpeaker",
																				"field_id"=> "flashvars.useSpeaker",
																				'tooltip_copy' => "useSpeaker",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useVolLevel",
																				"field_id"=> "flashvars.useVolLevel",
																				'tooltip_copy' => "useVolLevel",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useFullScreen",
																				"field_id"=> "flashvars.useFullScreen",
																				'tooltip_copy' => "useFullScreen",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useInfoButton",
																				"field_id"=> "flashvars.useInfoButton",
																				'tooltip_copy' => "useInfoButton",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useQualityMenu",
																				"field_id"=> "flashvars.useQualityMenu",
																				'tooltip_copy' => "useQualityMenu",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useTimeTooltip",
																				"field_id"=> "flashvars.useTimeTooltip",
																				'tooltip_copy' => "useTimeTooltip",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "usePlayPause",
																				"field_id"=> "flashvars.usePlayPause",
																				'tooltip_copy' => "usePlayPause",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Custom Video',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "previewImg",
																				"field_id"=> "flashvars.previewImg",
																				'tooltip_copy' => "previewImg",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "initialVol",
																				"field_id"=> "flashvars.initialVol",
																				'tooltip_copy' => "initialVol",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "playPauseOverVideo",
																				"field_id"=> "flashvars.playPauseOverVideo",
																				'tooltip_copy' => "playPauseOverVideo",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "bufferTime",
																				"field_id"=> "flashvars.bufferTime",
																				'tooltip_copy' => "bufferTime",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "belowVideo",
																				"field_id"=> "flashvars.belowVideo",
																				'tooltip_copy' => "belowVideo",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "use3Dstyle",
																				"field_id"=> "flashvars.use3Dstyle",
																				'tooltip_copy' => "use3Dstyle",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsAlpha",
																				"field_id"=> "flashvars.controlsAlpha",
																				'tooltip_copy' => "controlsAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playerBackgroundAlpha",
																				"field_id"=> "flashvars.playerBackgroundAlpha",
																				'tooltip_copy' => "playerBackgroundAlpha",
																				"default_value"=>"80",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useBackgroundGradient",
																				"field_id"=> "flashvars.useBackgroundGradient",
																				'tooltip_copy' => "useBackgroundGradient",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "normalTextColor",
																				"field_id"=> "flashvars.normalTextColor",
																				'tooltip_copy' => "normalTextColor",
																				"default_value"=>"777777",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "highlightTextColor",
																				"field_id"=> "flashvars.highlightTextColor",
																				'tooltip_copy' => "highlightTextColor",
																				"default_value"=>"999999",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "popupBackgroundColor",
																				"field_id"=> "flashvars.popupBackgroundColor",
																				'tooltip_copy' => "popupBackgroundColor",
																				"default_value"=>"777777",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "popupBackgroundColor",
																				"field_id"=> "flashvars.popupBackgroundColor",
																				'tooltip_copy' => "popupBackgroundColor",
																				"default_value"=>"000000",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "popupBackgroundAlpha",
																				"field_id"=> "flashvars.popupBackgroundAlpha",
																				'tooltip_copy' => "popupBackgroundAlpha",
																				"default_value"=>"80",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsSeparation",
																				"field_id"=> "flashvars.controlsSeparation",
																				'tooltip_copy' => "controlsSeparation",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsBarHeight",
																				"field_id"=> "flashvars.controlsBarHeight",
																				'tooltip_copy' => "controlsBarHeight",
																				"default_value"=>"25",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsScale",
																				"field_id"=> "flashvars.controlsScale",
																				'tooltip_copy' => "controlsScale",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "textControlsScale",
																				"field_id"=> "flashvars.textControlsScale",
																				'tooltip_copy' => "textControlsScale",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timeTooltipScale",
																				"field_id"=> "flashvars.timeTooltipScale",
																				'tooltip_copy' => "timeTooltipScale",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useControlsGradient",
																				"field_id"=> "flashvars.useControlsGradient",
																				'tooltip_copy' => "useControlsGradient",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useLoadingRing",
																				"field_id"=> "flashvars.useLoadingRing",
																				'tooltip_copy' => "useLoadingRing",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timelineDownloadAlpha",
																				"field_id"=> "flashvars.timelineDownloadAlpha",
																				'tooltip_copy' => "timelineDownloadAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timelineBackgroundAlpha",
																				"field_id"=> "flashvars.timelineBackgroundAlpha",
																				'tooltip_copy' => "timelineBackgroundAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "volBarColor",
																				"field_id"=> "flashvars.volBarColor",
																				'tooltip_copy' => "volBarColor",
																				"default_value"=>"168DFF",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "volBarAlpha",
																				"field_id"=> "flashvars.volBarAlpha",
																				'tooltip_copy' => "volBarAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "volBackgroundColor",
																				"field_id"=> "flashvars.volBackgroundColor",
																				'tooltip_copy' => "volBackgroundColor",
																				"default_value"=>"004993",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "volBackgroundAlpha",
																				"field_id"=> "flashvars.volBackgroundAlpha",
																				'tooltip_copy' => "volBackgroundAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timeTooltipColor",
																				"field_id"=> "flashvars.timeTooltipColor",
																				'tooltip_copy' => "timeTooltipColor",
																				"default_value"=>"666666",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timeTooltipTextColor",
																				"field_id"=> "flashvars.timeTooltipTextColor",
																				'tooltip_copy' => "timeTooltipTextColor",
																				"default_value"=>"DDDDDD",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "tooltipShadow",
																				"field_id"=> "flashvars.tooltipShadow",
																				'tooltip_copy' => "tooltipShadow",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoPath",
																				"field_id"=> "flashvars.logoPath",
																				'tooltip_copy' => "logoPath",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoURL",
																				"field_id"=> "flashvars.logoURL",
																				'tooltip_copy' => "logoURL",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoWindow",
																				"field_id"=> "flashvars.logoWindow",
																				'tooltip_copy' => "logoWindow",
																				"default_value"=>"_blank",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoPosition",
																				"field_id"=> "flashvars.logoPosition",
																				'tooltip_copy' => "logoPosition",
																				"default_value"=>"top-right",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoXmargin",
																				"field_id"=> "flashvars.logoXmargin",
																				'tooltip_copy' => "logoXmargin",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoYmargin",
																				"field_id"=> "flashvars.logoYmargin",
																				'tooltip_copy' => "logoYmargin",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoAlpha",
																				"field_id"=> "flashvars.logoAlpha",
																				'tooltip_copy' => "logoAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoDisplayTime",
																				"field_id"=> "flashvars.logoDisplayTime",
																				'tooltip_copy' => "logoDisplayTime",
																				"default_value"=>"0",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descWidth",
																				"field_id"=> "flashvars.descWidth",
																				'tooltip_copy' => "descWidth",
																				"default_value"=>"220",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descPosition",
																				"field_id"=> "flashvars.descPosition",
																				'tooltip_copy' => "descPosition",
																				"default_value"=>"top-left",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descTextColor",
																				"field_id"=> "flashvars.descTextColor",
																				'tooltip_copy' => "descTextColor",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descXmargin",
																				"field_id"=> "flashvars.descXmargin",
																				'tooltip_copy' => "descXmargin",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descYmargin",
																				"field_id"=> "flashvars.descYmargin",
																				'tooltip_copy' => "descYmargin",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descTextColor",
																				"field_id"=> "flashvars.descTextColor",
																				'tooltip_copy' => "descTextColor",
																				"default_value"=>"EEEEEE",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descTextSize",
																				"field_id"=> "flashvars.descTextSize",
																				'tooltip_copy' => "descTextSize",
																				"default_value"=>"14",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descBackgroundColor",
																				"field_id"=> "flashvars.descBackgroundColor",
																				'tooltip_copy' => "descBackgroundColor",
																				"default_value"=>"000000",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descBackgroundAlpha",
																				"field_id"=> "flashvars.descBackgroundAlpha",
																				'tooltip_copy' => "descBackgroundAlpha",
																				"default_value"=>"50",
																				"is_hidden"=>""
																				)
																			)
													)
												)
							),
							array(
								'section_type' => 'block',
								'section_title' => 'MAP',
								'ui_elements' => array(
													array(
														'section_type' => 'field',
														'field_type' => "boolean",
														'field_label' => "isRichMediaMap",
														"field_id"=> "flashvars.isRichMediaMap",
														'tooltip_copy' => "Turns map on if true",
														"default_value"=>"false",
														"is_hidden"=>""
														),
													array(
														'section_type' => 'block',
														'section_title' => 'Load Settings',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "isAutoLoadMap",
																				"field_id"=> "flashvars.isAutoLoadMap",
																				'tooltip_copy' => "Load the map automatically",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "markerTitles",
																				"field_id"=> "flashvars.markerTitles",
																				'tooltip_copy' => "Pin Titles (Google Map Only), separated by semicolons",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapPinLatitudes",
																				"field_id"=> "flashvars.mapPinLatitudes",
																				'tooltip_copy' => "Must be in order separated by semicolons",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapPinLongitudes",
																				"field_id"=> "flashvars.mapPinLongitudes",
																				'tooltip_copy' => "Must be in order separated by semicolons",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapPinDescriptions",
																				"field_id"=> "flashvars.mapPinDescriptions",
																				'tooltip_copy' => "Address and Info when click the pin, separated by semicolons",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapDefaultZoomLevel",
																				"field_id"=> "flashvars.mapDefaultZoomLevel",
																				'tooltip_copy' => "Lower number means farther into outerspace",
																				"default_value"=>"6",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Load Map Button',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButtonImageURL",
																				"field_id"=> "flashvars.mapButtonImageURL",
																				'tooltip_copy' => "mapButtonImageURL",
																				"default_value"=>"http://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.r9.cf1.rackcdn.com/buttons/white_20x20_map.png",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButtonImageURLSSL",
																				"field_id"=> "flashvars.mapButtonImageURLSSL",
																				'tooltip_copy' => "mapButtonImageURLSSL",
																				"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/white_20x20_map.png",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton300_x",
																				"field_id"=> "flashvars.mapButton300_x",
																				'tooltip_copy' => "mapButton300_x",
																				"default_value"=>"258",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton300_y",
																				"field_id"=> "flashvars.mapButton300_y",
																				'tooltip_copy' => "mapButton300_y",
																				"default_value"=>"236",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton336_x",
																				"field_id"=> "flashvars.mapButton336_x",
																				'tooltip_copy' => "mapButton336_x",
																				"default_value"=>"294",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton336_y",
																				"field_id"=> "flashvars.mapButton336_y",
																				'tooltip_copy' => "mapButton336_y",
																				"default_value"=>"266",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton728_x",
																				"field_id"=> "flashvars.mapButton728_x",
																				'tooltip_copy' => "mapButton728_x",
																				"default_value"=>"686",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton728_y",
																				"field_id"=> "flashvars.mapButton728_y",
																				'tooltip_copy' => "mapButton728_y",
																				"default_value"=>"76",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton160_x",
																				"field_id"=> "flashvars.mapButton160_x",
																				'tooltip_copy' => "mapButton160_x",
																				"default_value"=>"70",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton160_y",
																				"field_id"=> "flashvars.mapButton160_y",
																				'tooltip_copy' => "mapButton160_y",
																				"default_value"=>"560",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Map Position',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map300_x",
																				"field_id"=> "flashvars.map300_x",
																				'tooltip_copy' => "map300_x",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map300_y",
																				"field_id"=> "flashvars.map300_y",
																				'tooltip_copy' => "map300_y",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map336_x",
																				"field_id"=> "flashvars.map336_x",
																				'tooltip_copy' => "map336_x",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map336_y",
																				"field_id"=> "flashvars.map336_y",
																				'tooltip_copy' => "map336_y",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map728_x",
																				"field_id"=> "flashvars.map728_x",
																				'tooltip_copy' => "map728_x",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map728_y",
																				"field_id"=> "flashvars.map728_y",
																				'tooltip_copy' => "map728_y",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map160_x",
																				"field_id"=> "flashvars.map160_x",
																				'tooltip_copy' => "map160_x",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map160_y",
																				"field_id"=> "flashvars.map160_y",
																				'tooltip_copy' => "map160_y",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Map Size',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapWidth300",
																				"field_id"=> "flashvars.mapWidth300",
																				'tooltip_copy' => "mapWidth300",
																				"default_value"=>"298",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapHeight300",
																				"field_id"=> "flashvars.mapHeight300",
																				'tooltip_copy' => "mapHeight300",
																				"default_value"=>"248",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapWidth336",
																				"field_id"=> "flashvars.mapWidth336",
																				'tooltip_copy' => "mapWidth336",
																				"default_value"=>"334",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapHeight336",
																				"field_id"=> "flashvars.mapHeight336",
																				'tooltip_copy' => "mapHeight336",
																				"default_value"=>"278",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapWidth728",
																				"field_id"=> "flashvars.mapWidth728",
																				'tooltip_copy' => "mapWidth728",
																				"default_value"=>"726",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapHeight728",
																				"field_id"=> "flashvars.mapHeight728",
																				'tooltip_copy' => "mapHeight728",
																				"default_value"=>"88",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapWidth160",
																				"field_id"=> "flashvars.mapWidth160",
																				'tooltip_copy' => "mapWidth160",
																				"default_value"=>"158",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapHeight160",
																				"field_id"=> "flashvars.mapHeight160",
																				'tooltip_copy' => "mapHeight160",
																				"default_value"=>"598",
																				"is_hidden"=>""
																				)
																			)
													)
												),
							),
							array(
								'section_type' => 'block',
								'section_title' => 'SOCIAL',
								'ui_elements' => array(
													array(
														'section_type' => 'field',
														'field_type' => "boolean",
														'field_label' => "useShareButtons",
														"field_id"=> "flashvars.useShareButtons",
														'tooltip_copy' => "Use Social on or off",
														"default_value"=>"false",
														"is_hidden"=>""
														),
													array(
														'section_type' => 'block',
														'section_title' => 'Settings',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "urlToShare",
																				"field_id"=> "flashvars.urlToShare",
																				'tooltip_copy' => "URL of the page to share - use bit.ly",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "shareTitle",
																				"field_id"=> "flashvars.shareTitle",
																				'tooltip_copy' => "Title to share on LinkedIn",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "shareNotes",
																				"field_id"=> "flashvars.shareNotes",
																				'tooltip_copy' => "This is the Summary on LinkedIn",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "twitterText",
																				"field_id"=> "flashvars.twitterText",
																				'tooltip_copy' => "Text to share on Twitter (urlToShare will be added to end)",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "shareLabels",
																				"field_id"=> "flashvars.shareLabels",
																				'tooltip_copy' => "Google Bookmarks (discontinued)",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "shareSource",
																				"field_id"=> "flashvars.shareSource",
																				'tooltip_copy' => "Source for LinkedIn - Client URL",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mailSubject",
																				"field_id"=> "flashvars.mailSubject",
																				'tooltip_copy' => "Subject of the email message",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mailBody",
																				"field_id"=> "flashvars.mailBody",
																				'tooltip_copy' => "Body of the email message (no HTML)",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "isSocialAtStart",
																				"field_id"=> "flashvars.isSocialAtStart",
																				'tooltip_copy' => "Show the buttons at start of animation",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "socialVideoOnly",
																				"field_id"=> "flashvars.socialVideoOnly",
																				'tooltip_copy' => "Show social only in the video",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "hideSocialOnChildLoad",
																				"field_id"=> "flashvars.hideSocialOnChildLoad",
																				'tooltip_copy' => "Hide social buttons when map or video loads",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Social Button',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social300_x",
																				"field_id"=> "flashvars.social300_x",
																				'tooltip_copy' => "social300_x",
																				"default_value"=>"119",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social300_y",
																				"field_id"=> "flashvars.social300_y",
																				'tooltip_copy' => "social300_y",
																				"default_value"=>"225",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social336_x",
																				"field_id"=> "flashvars.social336_x",
																				'tooltip_copy' => "social336_x",
																				"default_value"=>"153",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social336_y",
																				"field_id"=> "flashvars.social336_y",
																				'tooltip_copy' => "social336_y",
																				"default_value"=>"255",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social728_x",
																				"field_id"=> "flashvars.social728_x",
																				'tooltip_copy' => "social728_x",
																				"default_value"=>"546",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social728_y",
																				"field_id"=> "flashvars.social728_y",
																				'tooltip_copy' => "social728_y",
																				"default_value"=>"65",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social160_x",
																				"field_id"=> "flashvars.social160_x",
																				'tooltip_copy' => "social160_x",
																				"default_value"=>"20",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social160_y",
																				"field_id"=> "flashvars.social160_y",
																				'tooltip_copy' => "social160_y",
																				"default_value"=>"576",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "socialAddress",
																				"field_id"=> "flashvars.socialAddress",
																				'tooltip_copy' => "socialAddress",
																				"default_value"=>"http://ad.vantagelocal.com/children/social_child_136.swf",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "socialAddressSSL",
																				"field_id"=> "flashvars.socialAddressSSL",
																				'tooltip_copy' => "socialAddressSSL",
																				"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/children/social_child_136.swf",
																				"is_hidden"=>""
																				)
																			)
													)

												),
							),
							array(
								'section_type' => 'block',
								'section_title' => 'SECOND CLICK BUTTON',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClickURL",
														"field_id"=> "flashvars.secondClickURL",
														"tooltip_copy"=> "secondClickURL",
														"default_value"=>"",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isSecondClickAlways",
														"field_id"=> "flashvars.isSecondClickAlways",
														"tooltip_copy"=> "isSecondClickAlways",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick300_x",
														"field_id"=> "flashvars.secondClick300_x",
														"tooltip_copy"=> "secondClick300_x",
														"default_value"=>"6",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick300_y",
														"field_id"=> "flashvars.secondClick300_y",
														"tooltip_copy"=> "secondClick300_y",
														"default_value"=>"216",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick336_x",
														"field_id"=> "flashvars.secondClick336_x",
														"tooltip_copy"=> "secondClick336_x",
														"default_value"=>"6",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick336_y",
														"field_id"=> "flashvars.secondClick336_y",
														"tooltip_copy"=> "secondClick336_y",
														"default_value"=>"246",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick728_x",
														"field_id"=> "flashvars.secondClick728_x",
														"tooltip_copy"=> "secondClick728_x",
														"default_value"=>"6",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick728_y",
														"field_id"=> "flashvars.secondClick728_y",
														"tooltip_copy"=> "secondClick728_y",
														"default_value"=>"55",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick160_x",
														"field_id"=> "flashvars.secondClick160_x",
														"tooltip_copy"=> "secondClick160_x",
														"default_value"=>"30",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick160_y",
														"field_id"=> "flashvars.secondClick160_y",
														"tooltip_copy"=> "secondClick160_y",
														"default_value"=>"510",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClickAddress",
														"field_id"=> "flashvars.secondClickAddress",
														"tooltip_copy"=> "secondClickAddress",
														"default_value"=>"http://ad.vantagelocal.com/buttons/facebook_dark.png",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClickAddressSSL",
														"field_id"=> "flashvars.secondClickAddressSSL",
														"tooltip_copy"=> "secondClickAddressSSL",
														"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/facebook_dark.png",
														"is_hidden"=>""
													)
												),
							),
							array(
								'section_type' => 'block',
								'section_title' => 'CUSTOM',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isDebugMode",
														"field_id"=> "flashvars.isDebugMode",
														"tooltip_copy"=> "isDebugMode",
														"default_value"=>false,
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "forceHTML5",
														"field_id"=> "flashvars.forceHTML5",
														"tooltip_copy"=> "forceHTML5",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isClickToCall",
														"field_id"=> "flashvars.isClickToCall",
														"tooltip_copy"=> "isClickToCall",
														"default_value"=>false,
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "clickToCallNumber",
														"field_id"=> "flashvars.clickToCallNumber",
														"tooltip_copy"=> "Phone Number without dashes (+ ok)",
														"default_value"=>"",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isKillASMBLclickTag",
														"field_id"=> "flashvars.isKillASMBLclickTag",
														"tooltip_copy"=> "isKillASMBLclickTag",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isKillGrandpaClickTag",
														"field_id"=> "flashvars.isKillGrandpaClickTag",
														"tooltip_copy"=> "isKillGrandpaClickTag",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "animationClip",
														"field_id"=> "flashvars.animationClip",
														"tooltip_copy"=> "animationClip",
														"default_value"=>"holder",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "parentClickTagPath",
														"field_id"=> "flashvars.parentClickTagPath",
														"tooltip_copy"=> "parentClickTagPath",
														"default_value"=>"button_holder",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "hoverFolder",
														"field_id"=> "flashvars.hoverFolder",
														"tooltip_copy"=> "hoverFolder",
														"default_value"=>"http://ad.vantagelocal.com/hovers/hovers_external_html5_images/",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "hoverFolderSSL",
														"field_id"=> "flashvars.hoverFolderSSL",
														"tooltip_copy"=> "hoverFolderSSL",
														"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/hovers_external_html5_images/",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "videoChildAddress",
														"field_id"=> "flashvars.videoChildAddress",
														"tooltip_copy"=> "videoChildAddress",
														"default_value"=>"http://ad.vantagelocal.com/children/video_child_219.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "videoChildAddressSSL",
														"field_id"=> "flashvars.videoChildAddressSSL",
														"tooltip_copy"=> "videoChildAddressSSL",
														"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/children/video_child_219.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "mapChildAddress",
														"field_id"=> "flashvars.mapChildAddress",
														"tooltip_copy"=> "mapChildAddress",
														"default_value"=>"http://ad.vantagelocal.com/children/map_child_101.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "mapChildAddressSSL",
														"field_id"=> "flashvars.mapChildAddressSSL",
														"tooltip_copy"=> "mapChildAddressSSL",
														"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/children/map_child_101.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailChildAddress",
														"field_id"=> "flashvars.emailChildAddress",
														"tooltip_copy"=> "emailChildAddress",
														"default_value"=>"http://ad.vantagelocal.com/children/email_child_001.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailChildAddressSSL",
														"field_id"=> "flashvars.emailChildAddressSSL",
														"tooltip_copy"=> "emailChildAddressSSL",
														"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/children/email_child_001.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "trackingPixelAddressSSL",
														"field_id"=> "flashvars.trackingPixelAddressSSL",
														"tooltip_copy"=> "trackingPixelAddressSSL",
														"default_value"=>"https://81e2563e7a7102c7a523-4c272e1e114489a00a7c5f34206ea8d1.ssl.cf1.rackcdn.com/px.gif",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "trackingPixelAddress",
														"field_id"=> "flashvars.trackingPixelAddress",
														"tooltip_copy"=> "trackingPixelAddress",
														"default_value"=>"http://3100e55ffe038d736447-4c272e1e114489a00a7c5f34206ea8d1.r73.cf1.rackcdn.com/px.gif",
														"is_hidden"=>""
													)
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'EMAIL (beta)',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isRichMediaEmail",
														"field_id"=> "flashvars.isRichMediaEmail",
														"tooltip_copy"=> "isRichMediaEmail",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButtonImageURL",
														"field_id"=> "flashvars.emailButtonImageURL",
														"tooltip_copy"=> "emailButtonImageURL",
														"default_value"=>"http://ad.vantagelocal.com/buttons/coupon_btn_1.png",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButtonImageURLSSL",
														"field_id"=> "flashvars.emailButtonImageURLSSL",
														"tooltip_copy"=> "emailButtonImageURLSSL",
														"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/coupon_btn_1.png",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isAutoLoadEmail",
														"field_id"=> "flashvars.isAutoLoadEmail",
														"tooltip_copy"=> "isAutoLoadEmail",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton300_x",
														"field_id"=> "flashvars.emailButton300_x",
														"tooltip_copy"=> "emailButton300_x",
														"default_value"=>"53",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton300_y",
														"field_id"=> "flashvars.emailButton300_y",
														"tooltip_copy"=> "emailButton300_y",
														"default_value"=>"125",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton336_x",
														"field_id"=> "flashvars.emailButton336_x",
														"tooltip_copy"=> "emailButton336_x",
														"default_value"=>"246",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton336_y",
														"field_id"=> "flashvars.emailButton336_y",
														"tooltip_copy"=> "emailButton336_y",
														"default_value"=>"242",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton728_x",
														"field_id"=> "flashvars.emailButton728_x",
														"tooltip_copy"=> "emailButton728_x",
														"default_value"=>"443",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton728_y",
														"field_id"=> "flashvars.emailButton728_y",
														"tooltip_copy"=> "emailButton728_y",
														"default_value"=>"27",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton160_x",
														"field_id"=> "flashvars.emailButton160_x",
														"tooltip_copy"=> "emailButton160_x",
														"default_value"=>"60",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton160_y",
														"field_id"=> "flashvars.emailButton160_y",
														"tooltip_copy"=> "emailButton160_y",
														"default_value"=>"286",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email300_x",
														"field_id"=> "flashvars.email300_x",
														"tooltip_copy"=> "email300_x",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email300_y",
														"field_id"=> "flashvars.email300_y",
														"tooltip_copy"=> "email300_y",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email336_x",
														"field_id"=> "flashvars.email336_x",
														"tooltip_copy"=> "email336_x",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email336_y",
														"field_id"=> "flashvars.email336_y",
														"tooltip_copy"=> "email336_y",
														"default_value"=>"13",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email728_x",
														"field_id"=> "flashvars.email728_x",
														"tooltip_copy"=> "email728_x",
														"default_value"=>"607",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email728_y",
														"field_id"=> "flashvars.email728_y",
														"tooltip_copy"=> "email728_y",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email160_x",
														"field_id"=> "flashvars.email160_x",
														"tooltip_copy"=> "email160_x",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email160_y",
														"field_id"=> "flashvars.email160_y",
														"tooltip_copy"=> "email160_y",
														"default_value"=>"296",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailWidth300",
														"field_id"=> "flashvars.emailWidth300",
														"tooltip_copy"=> "emailWidth300",
														"default_value"=>"300",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailHeight300",
														"field_id"=> "flashvars.emailHeight300",
														"tooltip_copy"=> "emailHeight300",
														"default_value"=>"250",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailWidth336",
														"field_id"=> "flashvars.emailWidth336",
														"tooltip_copy"=> "emailWidth336",
														"default_value"=>"336",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailHeight336",
														"field_id"=> "flashvars.emailHeight336",
														"tooltip_copy"=> "emailHeight336",
														"default_value"=>"254",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailWidth728",
														"field_id"=> "flashvars.emailWidth728",
														"tooltip_copy"=> "emailWidth728",
														"default_value"=>"119",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailHeight728",
														"field_id"=> "flashvars.emailHeight728",
														"tooltip_copy"=> "emailHeight728",
														"default_value"=>"90",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailWidth160",
														"field_id"=> "flashvars.emailWidth160",
														"tooltip_copy"=> "emailWidth160",
														"default_value"=>"160",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailHeight160",
														"field_id"=> "flashvars.emailHeight160",
														"tooltip_copy"=> "emailHeight160",
														"default_value"=>"121",
														"is_hidden"=>""
													)
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'VERSION',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "xmlVersion",
														"field_id"=> "flashvars.xmlVersion",
														"tooltip_copy"=> "xmlVersion",
														"default_value"=>"200",
														"is_hidden"=>1
													)
												)
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'BUILDER VERSION',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "VL Builder version",
														"field_id"=> "",
														"tooltip_copy"=> "This is the vantage local builder version",
														"default_value"=>$version,
														"is_hidden"=>1
													)
												)
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'FILES',
								'ui_elements' => array(
													array(
														'section_type' => 'field',
														'field_type' => "file_picker",
														'field_label' => "Special File",
														"field_id"=> "files.special_file",
														'tooltip_copy' => "drag drop asset",
														"is_hidden"=>""
													),
													array(
														'section_type' => 'field',
														'field_type' => "file_picker",
														'field_label' => "A different File",
														"field_id"=> "files.different_file",
														'tooltip_copy' => "drag drop asset",
														"is_hidden"=>""
													)
												)
							)
						);

			$json_blob = json_encode($config);
			print '<pre>'; print_r($json_blob); print '</pre>';
			echo "new config rows: ";
			$this->variables_model->insert_variables_config($version,$json_blob, $name);
			echo "<br>new builder 2 blob rows: ";
			$this->variables_model->insert_new_blob_to_builder_version($version, $blob_version, $rich_grandpa_version);
		}else
		{	
			redirect('director'); 
		}
	}

	public function build_config_swipe($version, $blob_version=NULL,$rich_grandpa_version=NULL){
		echo $version." and ".$blob_version;
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer','creative_uploader');
			redirect('login');
		}
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		if ($role == 'ops' or $role == 'admin')
		{

			$config = array(
							array(	
								'section_type' => 'block',
								'section_title' => 'GENERAL',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "showRichMenuAlways",
														"field_id"=> "flashvars.showRichMenuAlways",
														"tooltip_copy"=> "here is some tooltip copy for this variable",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "closeButtonPadding",
														"field_id"=> "flashvars.closeButtonPadding",
														"tooltip_copy"=> "Change to 10 if Video or Map covers top right corner (for AdChoices)",
														"default_value"=>"40",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isHoverToClick",
														"field_id"=> "flashvars.isHoverToClick",
														"tooltip_copy"=> "isHoverToClick",
														"default_value"=>"true",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isReplay",
														"field_id"=> "flashvars.isReplay",
														"tooltip_copy"=> "isReplay",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isGrandpaImageAlways",
														"field_id"=> "flashvars.isGrandpaImageAlways",
														"tooltip_copy"=> "Image Only Ad",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "hoverName",
														"field_id"=> "flashvars.hoverName",
														"tooltip_copy"=> "Hover Effect - shine or pagepeel or stroke or none",
														"default_value"=>"shine",
														"is_hidden"=>""
													)
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'SLIDER',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "numSlides",
														"field_id"=> "flashvars.numSlides",
														"tooltip_copy"=> "Number of Slides",
														"default_value"=>2,
														"is_hidden"=>""
													),
													array(	
														'section_type' => 'block',
														'section_title' => 'Images',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Slide Image 1",
																				"field_id"=> "files.slideImage0",
																				'tooltip_copy' => "drag drop slide image",
																				"is_hidden"=>"",
																				"default_filename"=>"blue_backup_300x250.jpg",
																				"default_is_writable"=>"true",
																				"default_key"=>"nROPFp0Thuga0h5RpmyQ_blue_backup_300x250.jpg",
																				"default_mimetype"=>"image/jpeg",
																				"default_size"=>"53062",
																				"default_url"=>"https://www.filepicker.io/api/file/XfFVDxvxQHyaRI29rU9y",
																				"default_width"=> 300,
																				"default_height"=> 250,
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Slide Image 2",
																				"field_id"=> "files.slideImage1",
																				'tooltip_copy' => "drag drop slide image",
																				"is_hidden"=>"",
																				"default_filename"=>"green_backup_300x250.jpg",
																				"default_is_writable"=>"true",
																				"default_key"=>"J6GHMI7nT4KaxgBdavmN_green_backup_300x250.jpg",
																				"default_mimetype"=>"image/jpeg",
																				"default_size"=>"48695",
																				"default_url"=>"https://www.filepicker.io/api/file/9c4H9i0TVqFbwgHbn2GQ",
																				"default_width"=> 300,
																				"default_height"=> 250,
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Slide Image 3",
																				"field_id"=> "files.slideImage2",
																				'tooltip_copy' => "drag drop slide image",
																				"is_hidden"=>"",
																				"default_filename"=>"purple_backup_300x250.jpg",
																				"default_is_writable"=>"true",
																				"default_key"=>"tlZAdMX4TfqrIKfL3bJ5_purple_backup_300x250.jpg",
																				"default_mimetype"=>"image/jpeg",
																				"default_size"=>"51623",
																				"default_url"=>"https://www.filepicker.io/api/file/dwxsybCRMij1TguKpYcA",
																				"default_width"=> 300,
																				"default_height"=> 250,
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Slide Image 4",
																				"field_id"=> "files.slideImage3",
																				'tooltip_copy' => "drag drop slide image",
																				"is_hidden"=>"",
																				"default_filename"=>"grey_backup_300x250.jpg",
																				"default_is_writable"=>"true",
																				"default_key"=>"VoPAmGOQT1W94qpWlA11_grey_backup_300x250.jpg",
																				"default_mimetype"=>"image/jpeg",
																				"default_size"=>"38610",
																				"default_url"=>"https://www.filepicker.io/api/file/ky0rcUkeRcSjohshow7o",
																				"default_width"=> 300,
																				"default_height"=> 250,
																			)
																		)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Settings',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "continuous",
																				"field_id"=> "flashvars.continuous",
																				'tooltip_copy' => "continuous",
																				"default_value"=>false,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "startSlide",
																				"field_id"=> "flashvars.startSlide",
																				'tooltip_copy' => "start on slide number",
																				"default_value"=>0,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "Auto Play Slider Seconds, or false",
																				"field_id"=> "flashvars.auto",
																				'tooltip_copy' => "auto play slider",
																				"default_value"=>false,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "Stop Autoplay on Slide Number",
																				"field_id"=> "flashvars.stopSlide",
																				'tooltip_copy' => "Stop Auto Play on this Slide Number, or false",
																				"default_value"=>false,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "isDesktopSwipe",
																				"field_id"=> "flashvars.isDesktopSwipe",
																				'tooltip_copy' => "isDesktopSwipe",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "disableScroll",
																				"field_id"=> "flashvars.disableScroll",
																				'tooltip_copy' => "disableScroll",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "stopPropagation",
																				"field_id"=> "flashvars.stopPropagation",
																				'tooltip_copy' => "stopPropagation",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "hoverAction",
																				"field_id"=> "flashvars.hoverAction",
																				'tooltip_copy' => "hoverAction",
																				"default_value"=>"expand",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Expandable',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "isExpandable",
																				"field_id"=> "flashvars.isExpandable",
																				'tooltip_copy' => "isExpandable",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "isCountdown",
																				"field_id"=> "flashvars.isCountdown",
																				'tooltip_copy' => "isCountdown",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "expandSlide",
																				"field_id"=> "flashvars.expandSlide",
																				'tooltip_copy' => "Which slide number expands, starting with 0",
																				"default_value"=>2,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "buttonWrapperLeft",
																				"field_id"=> "flashvars.buttonWrapperLeft",
																				'tooltip_copy' => "buttonWrapperLeft",
																				"default_value"=>10,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "buttonWrapperTop",
																				"field_id"=> "flashvars.buttonWrapperTop",
																				'tooltip_copy' => "buttonWrapperTop",
																				"default_value"=>100,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "showCloseButton",
																				"field_id"=> "flashvars.showCloseButton",
																				'tooltip_copy' => "showCloseButton",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "closeButton",
																				"field_id"=> "files.closeButton",
																				'tooltip_copy' => "drag drop close image",
																				"is_hidden"=>"",
																				"default_filename"=>"close.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"8shg1yQ4T5iGsjiDYNLL_close.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"1522",
																				"default_url"=>"https://www.filepicker.io/api/file/tSrK7lJRAGzWh8fpTeb6"
																				),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "numButtons",
																				"field_id"=> "flashvars.numButtons",
																				"tooltip_copy"=> "Number of Buttons",
																				"default_value"=>4,
																				"is_hidden"=>""
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Button Image 1",
																				"field_id"=> "files.buttonImage0",
																				'tooltip_copy' => "drag drop button image",
																				"is_hidden"=>"",
																				"default_filename"=>"learn.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"Rex456KUSEy4QdWXjDyW_learn.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"2910",
																				"default_url"=>"https://www.filepicker.io/api/file/PQGXb2DtS6bEH9SoGvtQ"
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Button Image 2",
																				"field_id"=> "files.buttonImage1",
																				'tooltip_copy' => "drag drop button image",
																				"is_hidden"=>"",
																				"default_filename"=>"learn.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"Rex456KUSEy4QdWXjDyW_learn.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"2910",
																				"default_url"=>"https://www.filepicker.io/api/file/PQGXb2DtS6bEH9SoGvtQ"
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "secondClick",
																				"field_id"=> "flashvars.secondClick",
																				'tooltip_copy' => "secondClick",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Button Image 3",
																				"field_id"=> "files.buttonImage2",
																				'tooltip_copy' => "drag drop button image",
																				"is_hidden"=>"",
																				"default_filename"=>"learn.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"Rex456KUSEy4QdWXjDyW_learn.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"2910",
																				"default_url"=>"https://www.filepicker.io/api/file/PQGXb2DtS6bEH9SoGvtQ"
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "thirdClick",
																				"field_id"=> "flashvars.thirdClick",
																				'tooltip_copy' => "thirdClick",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Button Image 4",
																				"field_id"=> "files.buttonImage3",
																				'tooltip_copy' => "drag drop slide image",
																				"is_hidden"=>"",
																				"default_filename"=>"learn.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"Rex456KUSEy4QdWXjDyW_learn.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"2910",
																				"default_url"=>"https://www.filepicker.io/api/file/PQGXb2DtS6bEH9SoGvtQ"
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "fourthClick",
																				"field_id"=> "flashvars.fourthClick",
																				'tooltip_copy' => "fourthClick",
																				"default_value"=>"",
																				"is_hidden"=>""
																				)
																		)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Custom Slider Settings',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "text_area",
																				'field_label' => "customCSS",
																				"field_id"=> "flashvars.customCSS",
																				'tooltip_copy' => "Custom CSS",
																				"default_value"=>".vl-button img:hover,.vl-close img:hover{-webkit-box-shadow: 0px 0px 2px 2px rgba(255, 255, 255, .50);box-shadow: 0px 0px 2px 2px rgba(255, 255, 255, .50);}",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "isCustomButtonCSS",
																				"field_id"=> "flashvars.isCustomButtonCSS",
																				'tooltip_copy' => "isCountdown",
																				"default_value"=>false,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "CSS Button Color 1",
																				"field_id"=> "flashvars.buttonColor1",
																				'tooltip_copy' => "buttonColor1",
																				"default_value"=>"#333333",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "CSS Hover Button Color 1",
																				"field_id"=> "flashvars.hoverButtonColor1",
																				'tooltip_copy' => "hoverButtonColor1",
																				"default_value"=>"#333333",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "CSS Button Color 2",
																				"field_id"=> "flashvars.buttonColor2",
																				'tooltip_copy' => "buttonColor2",
																				"default_value"=>"#333333",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "CSS Hover Button Color 2",
																				"field_id"=> "flashvars.hoverButtonColor2",
																				'tooltip_copy' => "hoverButtonColor2",
																				"default_value"=>"#333333",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "CSS Button Color 3",
																				"field_id"=> "flashvars.buttonColor3",
																				'tooltip_copy' => "buttonColor3",
																				"default_value"=>"#333333",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "CSS Hover Button Color 3",
																				"field_id"=> "flashvars.hoverButtonColor3",
																				'tooltip_copy' => "hoverButtonColor3",
																				"default_value"=>"#333333",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "CSS Button Color 4",
																				"field_id"=> "flashvars.buttonColor4",
																				'tooltip_copy' => "buttonColor4",
																				"default_value"=>"#333333",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "CSS Hover Button Color 4",
																				"field_id"=> "flashvars.hoverButtonColor4",
																				'tooltip_copy' => "hoverButtonColor4",
																				"default_value"=>"#333333",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "hover cursor image",
																				"field_id"=> "files.hoverCursor",
																				'tooltip_copy' => "hover cursor image",
																				"is_hidden"=>"",
																				"default_filename"=>"grab.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"gYm55xIWR5i1bpvM2yEr_grab.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"99",
																				"default_url"=>"https://www.filepicker.io/api/file/fSZeqy7FT2ZZESJ7uLHU"
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "dragging cursor image",
																				"field_id"=> "files.draggingCursor",
																				'tooltip_copy' => "dragging cursor image",
																				"is_hidden"=>"",
																				"default_filename"=>"grabbing.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"SGnmgOioRNWMQB8Zz9fW_grabbing.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"2889",
																				"default_url"=>"https://www.filepicker.io/api/file/3yaVyJjSYK8ii7UUnDc8"
																				)
																		)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Message Box',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "messageBoxText",
																				"field_id"=> "flashvars.messageBoxText",
																				'tooltip_copy' => "messageBoxText",
																				"default_value"=>"ROLLOVER TO PLAY",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "messageMobileText",
																				"field_id"=> "flashvars.messageMobileText",
																				'tooltip_copy' => "messageMobileText",
																				"default_value"=>"SWIPE TO EXPAND",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "messageBoxHeight",
																				"field_id"=> "flashvars.messageBoxHeight",
																				'tooltip_copy' => "messageBoxHeight",
																				"default_value"=>30,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "messageBoxWidth",
																				"field_id"=> "flashvars.messageBoxWidth",
																				'tooltip_copy' => "messageBoxWidth",
																				"default_value"=>220,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "messageBoxTop",
																				"field_id"=> "flashvars.messageBoxTop",
																				'tooltip_copy' => "messageBoxTop",
																				"default_value"=>90,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "messageBoxLeft",
																				"field_id"=> "flashvars.messageBoxLeft",
																				'tooltip_copy' => "messageBoxLeft",
																				"default_value"=>70,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "messageBoxTextColor",
																				"field_id"=> "flashvars.messageBoxTextColor",
																				'tooltip_copy' => "messageBoxTextColor",
																				"default_value"=>"#ffffff",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "messageBoxTextSize",
																				"field_id"=> "flashvars.messageBoxTextSize",
																				'tooltip_copy' => "messageBoxTextSize",
																				"default_value"=>15,
																				"is_hidden"=>""
																				)
																			)
													)
													
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'VIDEO',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isVideo",
														"field_id"=> "flashvars.isVideo",
														"tooltip_copy"=> "isVideo",
														"default_value"=>false,
														"is_hidden"=>""
														),
													array(
														'section_type' => 'block',
														'section_title' => 'Video Settings',
														'ui_elements' => array(
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "videoPlayer",
																				"field_id"=> "flashvars.videoPlayer",
																				"tooltip_copy"=> "videojs, youtube, jwplayer",
																				"default_value"=>"youtube",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "videoURL",
																				"field_id"=> "flashvars.videoURL",
																				"tooltip_copy"=> "videoURL",
																				"default_value"=>"",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "videoSlideNum",
																				"field_id"=> "flashvars.videoSlideNum",
																				"tooltip_copy"=> "videoSlideNum",
																				"default_value"=>2,
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "videoID",
																				"field_id"=> "flashvars.videoID",
																				"tooltip_copy"=> "videoID",
																				"default_value"=>"",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "videoPoster",
																				"field_id"=> "flashvars.videoPoster",
																				"tooltip_copy"=> "videoPoster",
																				"default_value"=>"",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "videoMP4",
																				"field_id"=> "flashvars.videoMP4",
																				"tooltip_copy"=> "videoMP4",
																				"default_value"=>"http://content.bitsontherun.com/videos/BwAv0fYF-0Kr0ekT4.mp4",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "videoWEBM",
																				"field_id"=> "flashvars.videoWEBM",
																				"tooltip_copy"=> "videoWEBM",
																				"default_value"=>"https://0267a94cab6d385b9024-b73241aae133d24e20527933c2ff6c10.ssl.cf1.rackcdn.com/ram_trucks_drama.webm",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "videoOGV",
																				"field_id"=> "flashvars.videoOGV",
																				"tooltip_copy"=> "videoOGV",
																				"default_value"=>"https://0267a94cab6d385b9024-b73241aae133d24e20527933c2ff6c10.ssl.cf1.rackcdn.com/ram_trucks_drama.ogv",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"boolean",
																				"field_label"=> "isAutoLoadVideo",
																				"field_id"=> "flashvars.isAutoLoadVideo",
																				"tooltip_copy"=> "isAutoLoadVideo",
																				"default_value"=>"false",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"boolean",
																				"field_label"=> "autoPlay",
																				"field_id"=> "flashvars.autoPlay",
																				"tooltip_copy"=> "autoPlay",
																				"default_value"=>"false",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"boolean",
																				"field_label"=> "isPlayMuted",
																				"field_id"=> "flashvars.isPlayMuted",
																				"tooltip_copy"=> "isPlayMuted",
																				"default_value"=>"false",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"boolean",
																				"field_label"=> "isHideAdUntilLoadedBugPatch",
																				"field_id"=> "flashvars.isHideAdUntilLoadedBugPatch",
																				"tooltip_copy"=> "Use CSS to hide the ad until youtube is done loading so if user clicks too soon, slider does not break (buggy patch)",
																				"default_value"=>true,
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "numAutoPlaySeconds",
																				"field_id"=> "flashvars.numAutoPlaySeconds",
																				"tooltip_copy"=> "numAutoPlaySeconds",
																				"default_value"=>"5",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"boolean",
																				"field_label"=> "scaleToFillFullscreenBG",
																				"field_id"=> "flashvars.scaleToFillFullscreenBG",
																				"tooltip_copy"=> "scaleToFillFullscreenBG",
																				"default_value"=>"false",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "fullscreenImageURL",
																				"field_id"=> "flashvars.fullscreenImageURL",
																				"tooltip_copy"=> "fullscreenImageURL",
																				"default_value"=>"",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "fullscreenImageURLSSL",
																				"field_id"=> "flashvars.fullscreenImageURLSSL",
																				"tooltip_copy"=> "fullscreenImageURLSSL",
																				"default_value"=>"",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "playButtonImageURL",
																				"field_id"=> "flashvars.playButtonImageURL",
																				"tooltip_copy"=> "playButtonImageURL",
																				"default_value"=>"http://ad.vantagelocal.com/buttons/white_20x20_play.png",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "playButtonImageURLSSL",
																				"field_id"=> "flashvars.playButtonImageURLSSL",
																				"tooltip_copy"=> "playButtonImageURLSSL",
																				"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/white_20x20_play.png",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "mobilePlayButtonImageURL",
																				"field_id"=> "flashvars.mobilePlayButtonImageURL",
																				"tooltip_copy"=> "mobilePlayButtonImageURL",
																				"default_value"=>"http://ad.vantagelocal.com/buttons/play_btn_white_mobile.png",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "mobilePlayButtonImageURLSSL",
																				"field_id"=> "flashvars.mobilePlayButtonImageURLSSL",
																				"tooltip_copy"=> "mobilePlayButtonImageURLSSL",
																				"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/play_btn_white_mobile.png",
																				"is_hidden"=>""
																			)
																		)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Play Button Positions',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton_x",
																				"field_id"=> "flashvars.playButton_x",
																				'tooltip_copy' => "playButton_x",
																				"default_value"=>"150",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton_y",
																				"field_id"=> "flashvars.playButton_y",
																				'tooltip_copy' => "playButton_y",
																				"default_value"=>"125",
																				"is_hidden"=>""
																				)
																		)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Video Position',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video_x",
																				"field_id"=> "flashvars.video_x",
																				'tooltip_copy' => "video_x",
																				"default_value"=>"150",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video_y",
																				"field_id"=> "flashvars.video_y",
																				'tooltip_copy' => "video_y",
																				"default_value"=>"40",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Video Size',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoWidth",
																				"field_id"=> "flashvars.videoWidth",
																				'tooltip_copy' => "videoWidth",
																				"default_value"=>"298",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoHeight",
																				"field_id"=> "flashvars.videoHeight",
																				'tooltip_copy' => "videoHeight",
																				"default_value"=>"168",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Fullscreen',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "fullscreenBackgroundColor",
																				"field_id"=> "flashvars.fullscreenBackgroundColor",
																				'tooltip_copy' => "fullscreenBackgroundColor",
																				"default_value"=>"252525",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "forceFullscreenVideoWidth",
																				"field_id"=> "flashvars.forceFullscreenVideoWidth",
																				'tooltip_copy' => "forceFullscreenVideoWidth",
																				"default_value"=>"800",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "forceFullscreenVideoHeight",
																				"field_id"=> "flashvars.forceFullscreenVideoHeight",
																				'tooltip_copy' => "forceFullscreenVideoHeight",
																				"default_value"=>"450",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Player Colors',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timelineDownloadColor",
																				"field_id"=> "flashvars.timelineDownloadColor",
																				'tooltip_copy' => "timelineDownloadColor",
																				"default_value"=>"168DFF",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timelineBackgroundColor",
																				"field_id"=> "flashvars.timelineBackgroundColor",
																				'tooltip_copy' => "timelineBackgroundColor",
																				"default_value"=>"004993",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsColor",
																				"field_id"=> "flashvars.controlsColor",
																				'tooltip_copy' => "controlsColor",
																				"default_value"=>"888888",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsHighlightColor",
																				"field_id"=> "flashvars.controlsHighlightColor",
																				'tooltip_copy' => "controlsHighlightColor",
																				"default_value"=>"BBBBBB",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playerBackgroundColor",
																				"field_id"=> "flashvars.playerBackgroundColor",
																				'tooltip_copy' => "playerBackgroundColor",
																				"default_value"=>"000000",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Video Controls',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useTimeline",
																				"field_id"=> "flashvars.useTimeline",
																				'tooltip_copy' => "useTimeline",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useTimeDisplay",
																				"field_id"=> "flashvars.useTimeDisplay",
																				'tooltip_copy' => "useTimeDisplay",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useSpeaker",
																				"field_id"=> "flashvars.useSpeaker",
																				'tooltip_copy' => "useSpeaker",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useVolLevel",
																				"field_id"=> "flashvars.useVolLevel",
																				'tooltip_copy' => "useVolLevel",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useFullScreen",
																				"field_id"=> "flashvars.useFullScreen",
																				'tooltip_copy' => "useFullScreen",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useInfoButton",
																				"field_id"=> "flashvars.useInfoButton",
																				'tooltip_copy' => "useInfoButton",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useQualityMenu",
																				"field_id"=> "flashvars.useQualityMenu",
																				'tooltip_copy' => "useQualityMenu",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useTimeTooltip",
																				"field_id"=> "flashvars.useTimeTooltip",
																				'tooltip_copy' => "useTimeTooltip",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "usePlayPause",
																				"field_id"=> "flashvars.usePlayPause",
																				'tooltip_copy' => "usePlayPause",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Custom Video',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "previewImg",
																				"field_id"=> "flashvars.previewImg",
																				'tooltip_copy' => "previewImg",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "initialVol",
																				"field_id"=> "flashvars.initialVol",
																				'tooltip_copy' => "initialVol",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "playPauseOverVideo",
																				"field_id"=> "flashvars.playPauseOverVideo",
																				'tooltip_copy' => "playPauseOverVideo",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "bufferTime",
																				"field_id"=> "flashvars.bufferTime",
																				'tooltip_copy' => "bufferTime",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "belowVideo",
																				"field_id"=> "flashvars.belowVideo",
																				'tooltip_copy' => "belowVideo",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "use3Dstyle",
																				"field_id"=> "flashvars.use3Dstyle",
																				'tooltip_copy' => "use3Dstyle",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsAlpha",
																				"field_id"=> "flashvars.controlsAlpha",
																				'tooltip_copy' => "controlsAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playerBackgroundAlpha",
																				"field_id"=> "flashvars.playerBackgroundAlpha",
																				'tooltip_copy' => "playerBackgroundAlpha",
																				"default_value"=>"80",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useBackgroundGradient",
																				"field_id"=> "flashvars.useBackgroundGradient",
																				'tooltip_copy' => "useBackgroundGradient",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "normalTextColor",
																				"field_id"=> "flashvars.normalTextColor",
																				'tooltip_copy' => "normalTextColor",
																				"default_value"=>"777777",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "highlightTextColor",
																				"field_id"=> "flashvars.highlightTextColor",
																				'tooltip_copy' => "highlightTextColor",
																				"default_value"=>"999999",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "popupBackgroundColor",
																				"field_id"=> "flashvars.popupBackgroundColor",
																				'tooltip_copy' => "popupBackgroundColor",
																				"default_value"=>"777777",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "popupBackgroundColor",
																				"field_id"=> "flashvars.popupBackgroundColor",
																				'tooltip_copy' => "popupBackgroundColor",
																				"default_value"=>"000000",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "popupBackgroundAlpha",
																				"field_id"=> "flashvars.popupBackgroundAlpha",
																				'tooltip_copy' => "popupBackgroundAlpha",
																				"default_value"=>"80",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsSeparation",
																				"field_id"=> "flashvars.controlsSeparation",
																				'tooltip_copy' => "controlsSeparation",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsBarHeight",
																				"field_id"=> "flashvars.controlsBarHeight",
																				'tooltip_copy' => "controlsBarHeight",
																				"default_value"=>"25",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsScale",
																				"field_id"=> "flashvars.controlsScale",
																				'tooltip_copy' => "controlsScale",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "textControlsScale",
																				"field_id"=> "flashvars.textControlsScale",
																				'tooltip_copy' => "textControlsScale",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timeTooltipScale",
																				"field_id"=> "flashvars.timeTooltipScale",
																				'tooltip_copy' => "timeTooltipScale",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useControlsGradient",
																				"field_id"=> "flashvars.useControlsGradient",
																				'tooltip_copy' => "useControlsGradient",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useLoadingRing",
																				"field_id"=> "flashvars.useLoadingRing",
																				'tooltip_copy' => "useLoadingRing",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timelineDownloadAlpha",
																				"field_id"=> "flashvars.timelineDownloadAlpha",
																				'tooltip_copy' => "timelineDownloadAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timelineBackgroundAlpha",
																				"field_id"=> "flashvars.timelineBackgroundAlpha",
																				'tooltip_copy' => "timelineBackgroundAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "volBarColor",
																				"field_id"=> "flashvars.volBarColor",
																				'tooltip_copy' => "volBarColor",
																				"default_value"=>"168DFF",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "volBarAlpha",
																				"field_id"=> "flashvars.volBarAlpha",
																				'tooltip_copy' => "volBarAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "volBackgroundColor",
																				"field_id"=> "flashvars.volBackgroundColor",
																				'tooltip_copy' => "volBackgroundColor",
																				"default_value"=>"004993",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "volBackgroundAlpha",
																				"field_id"=> "flashvars.volBackgroundAlpha",
																				'tooltip_copy' => "volBackgroundAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timeTooltipColor",
																				"field_id"=> "flashvars.timeTooltipColor",
																				'tooltip_copy' => "timeTooltipColor",
																				"default_value"=>"666666",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timeTooltipTextColor",
																				"field_id"=> "flashvars.timeTooltipTextColor",
																				'tooltip_copy' => "timeTooltipTextColor",
																				"default_value"=>"DDDDDD",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "tooltipShadow",
																				"field_id"=> "flashvars.tooltipShadow",
																				'tooltip_copy' => "tooltipShadow",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoPath",
																				"field_id"=> "flashvars.logoPath",
																				'tooltip_copy' => "logoPath",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoURL",
																				"field_id"=> "flashvars.logoURL",
																				'tooltip_copy' => "logoURL",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoWindow",
																				"field_id"=> "flashvars.logoWindow",
																				'tooltip_copy' => "logoWindow",
																				"default_value"=>"_blank",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoPosition",
																				"field_id"=> "flashvars.logoPosition",
																				'tooltip_copy' => "logoPosition",
																				"default_value"=>"top-right",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoXmargin",
																				"field_id"=> "flashvars.logoXmargin",
																				'tooltip_copy' => "logoXmargin",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoYmargin",
																				"field_id"=> "flashvars.logoYmargin",
																				'tooltip_copy' => "logoYmargin",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoAlpha",
																				"field_id"=> "flashvars.logoAlpha",
																				'tooltip_copy' => "logoAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoDisplayTime",
																				"field_id"=> "flashvars.logoDisplayTime",
																				'tooltip_copy' => "logoDisplayTime",
																				"default_value"=>"0",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descWidth",
																				"field_id"=> "flashvars.descWidth",
																				'tooltip_copy' => "descWidth",
																				"default_value"=>"220",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descPosition",
																				"field_id"=> "flashvars.descPosition",
																				'tooltip_copy' => "descPosition",
																				"default_value"=>"top-left",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descTextColor",
																				"field_id"=> "flashvars.descTextColor",
																				'tooltip_copy' => "descTextColor",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descXmargin",
																				"field_id"=> "flashvars.descXmargin",
																				'tooltip_copy' => "descXmargin",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descYmargin",
																				"field_id"=> "flashvars.descYmargin",
																				'tooltip_copy' => "descYmargin",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descTextColor",
																				"field_id"=> "flashvars.descTextColor",
																				'tooltip_copy' => "descTextColor",
																				"default_value"=>"EEEEEE",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descTextSize",
																				"field_id"=> "flashvars.descTextSize",
																				'tooltip_copy' => "descTextSize",
																				"default_value"=>"14",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descBackgroundColor",
																				"field_id"=> "flashvars.descBackgroundColor",
																				'tooltip_copy' => "descBackgroundColor",
																				"default_value"=>"000000",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descBackgroundAlpha",
																				"field_id"=> "flashvars.descBackgroundAlpha",
																				'tooltip_copy' => "descBackgroundAlpha",
																				"default_value"=>"50",
																				"is_hidden"=>""
																				)
																			)
													)
												)
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'MAP',
								'ui_elements' => array(
													array(
														'section_type' => 'field',
														'field_type' => "boolean",
														'field_label' => "isRichMediaMap",
														"field_id"=> "flashvars.isRichMediaMap",
														'tooltip_copy' => "Turns map on if true",
														"default_value"=>"false",
														"is_hidden"=>""
														),
													array(
														'section_type' => 'block',
														'section_title' => 'Load Settings',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapSlideNum",
																				"field_id"=> "flashvars.mapSlideNum",
																				'tooltip_copy' => "Map Slide Number",
																				"default_value"=>2,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButtonImageURL",
																				"field_id"=> "flashvars.mapButtonImageURL",
																				'tooltip_copy' => "mapButtonImageURL",
																				"default_value"=>"http://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.r9.cf1.rackcdn.com/buttons/map_pin_6.png",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButtonImageURLSSL",
																				"field_id"=> "flashvars.mapButtonImageURLSSL",
																				'tooltip_copy' => "mapButtonImageURLSSL",
																				"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/map_pin_6.png",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "isAutoLoadMap",
																				"field_id"=> "flashvars.isAutoLoadMap",
																				'tooltip_copy' => "Load the map automatically",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "markerTitles",
																				"field_id"=> "flashvars.markerTitles",
																				'tooltip_copy' => "Pin Titles (Google Map Only), separated by semicolons",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapPinLatitudes",
																				"field_id"=> "flashvars.mapPinLatitudes",
																				'tooltip_copy' => "Must be in order separated by semicolons",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapPinLongitudes",
																				"field_id"=> "flashvars.mapPinLongitudes",
																				'tooltip_copy' => "Must be in order separated by semicolons",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapPinDescriptions",
																				"field_id"=> "flashvars.mapPinDescriptions",
																				'tooltip_copy' => "Address and Info when click the pin, separated by semicolons",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapDefaultZoomLevel",
																				"field_id"=> "flashvars.mapDefaultZoomLevel",
																				'tooltip_copy' => "Lower number means farther into outerspace",
																				"default_value"=>"6",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Load Map Button',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton_x",
																				"field_id"=> "flashvars.mapButton_x",
																				'tooltip_copy' => "mapButton_x",
																				"default_value"=>"260",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton_y",
																				"field_id"=> "flashvars.mapButton_y",
																				'tooltip_copy' => "mapButton_y",
																				"default_value"=>"197",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Map Position',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map_x",
																				"field_id"=> "flashvars.map_x",
																				'tooltip_copy' => "map_x",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map_y",
																				"field_id"=> "flashvars.map_y",
																				'tooltip_copy' => "map_y",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Map Size',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapWidth",
																				"field_id"=> "flashvars.mapWidth",
																				'tooltip_copy' => "mapWidth",
																				"default_value"=>"298",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapHeight",
																				"field_id"=> "flashvars.mapHeight",
																				'tooltip_copy' => "mapHeight",
																				"default_value"=>"248",
																				"is_hidden"=>""
																				)
																			)
													)
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'SOCIAL',
								'ui_elements' => array(
													array(
														'section_type' => 'field',
														'field_type' => "boolean",
														'field_label' => "useShareButtons",
														"field_id"=> "flashvars.useShareButtons",
														'tooltip_copy' => "Use Social on or off",
														"default_value"=>"false",
														"is_hidden"=>""
														),
													array(
														'section_type' => 'block',
														'section_title' => 'Settings',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "socialSlideNum",
																				"field_id"=> "flashvars.socialSlideNum",
																				'tooltip_copy' => "Social Slide Number",
																				"default_value"=>2,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useFacebook",
																				"field_id"=> "flashvars.useFacebook",
																				'tooltip_copy' => "useFacebook",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useTwitter",
																				"field_id"=> "flashvars.useTwitter",
																				'tooltip_copy' => "useTwitter",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useGPlus",
																				"field_id"=> "flashvars.useGPlus",
																				'tooltip_copy' => "useGPlus",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useLinkedIn",
																				"field_id"=> "flashvars.useLinkedIn",
																				'tooltip_copy' => "useLinkedIn",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useEmail",
																				"field_id"=> "flashvars.useEmail",
																				'tooltip_copy' => "useEmail",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "urlToShare",
																				"field_id"=> "flashvars.urlToShare",
																				'tooltip_copy' => "URL of the page to share - use bit.ly",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "shareTitle",
																				"field_id"=> "flashvars.shareTitle",
																				'tooltip_copy' => "Title to share on LinkedIn",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "shareNotes",
																				"field_id"=> "flashvars.shareNotes",
																				'tooltip_copy' => "This is the Summary on LinkedIn",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "twitterText",
																				"field_id"=> "flashvars.twitterText",
																				'tooltip_copy' => "Text to share on Twitter (urlToShare will be added to end)",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "shareLabels",
																				"field_id"=> "flashvars.shareLabels",
																				'tooltip_copy' => "Google Bookmarks (discontinued)",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "shareSource",
																				"field_id"=> "flashvars.shareSource",
																				'tooltip_copy' => "Source for LinkedIn - Client URL",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mailSubject",
																				"field_id"=> "flashvars.mailSubject",
																				'tooltip_copy' => "Subject of the email message",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mailBody",
																				"field_id"=> "flashvars.mailBody",
																				'tooltip_copy' => "Body of the email message (no HTML)",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "isSocialAtStart",
																				"field_id"=> "flashvars.isSocialAtStart",
																				'tooltip_copy' => "Show the buttons at start of animation",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "socialVideoOnly",
																				"field_id"=> "flashvars.socialVideoOnly",
																				'tooltip_copy' => "Show social only in the video",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "hideSocialOnChildLoad",
																				"field_id"=> "flashvars.hideSocialOnChildLoad",
																				'tooltip_copy' => "Hide social buttons when map or video loads",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Social Buttons',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social_x",
																				"field_id"=> "flashvars.social_x",
																				'tooltip_copy' => "social_x",
																				"default_value"=>"0",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social_y",
																				"field_id"=> "flashvars.social_y",
																				'tooltip_copy' => "social_y",
																				"default_value"=>"0",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "socialWidth",
																				"field_id"=> "flashvars.socialWidth",
																				'tooltip_copy' => "socialWidth",
																				"default_value"=>"130",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "socialColor",
																				"field_id"=> "flashvars.socialColor",
																				'tooltip_copy' => "socialColor",
																				"default_value"=>"#333333",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Facebook Button Image",
																				"field_id"=> "files.facebookButtonImage",
																				'tooltip_copy' => "drag drop button image",
																				"is_hidden"=>"",
																				"default_filename"=>"facebook.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"Pu2mgO6MRSqTvTSgwOux_facebook.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"1247",
																				"default_url"=>"https://www.filepicker.io/api/file/KJytovR0RXSZFfQWsbGR"
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Twitter Button Image",
																				"field_id"=> "files.twitterButtonImage",
																				'tooltip_copy' => "drag drop button image",
																				"is_hidden"=>"",
																				"default_filename"=>"twitter.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"XkNeK3yASYsX0xhc4zua_twitter.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"1374",
																				"default_url"=>"https://www.filepicker.io/api/file/gJTtgLOLRMmTXyNcEMPU"
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "LinkedIn Button Image",
																				"field_id"=> "files.linkedinButtonImage",
																				'tooltip_copy' => "drag drop button image",
																				"is_hidden"=>"",
																				"default_filename"=>"linkedin.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"ujUqQEz5QuGqnyGjb7Go_linkedin.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"1292",
																				"default_url"=>"https://www.filepicker.io/api/file/qJIfVH3qTgGVcvzYIMqg"
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Google Plus Button Image",
																				"field_id"=> "files.googleButtonImage",
																				'tooltip_copy' => "drag drop button image",
																				"is_hidden"=>"",
																				"default_filename"=>"google.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"kuoti3MyQAmmOmLAaCzJ_google.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"1633",
																				"default_url"=>"https://www.filepicker.io/api/file/Eh4SBaPTRcuYOJoTaOjq"
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Email Button Image",
																				"field_id"=> "files.emailButtonImage",
																				'tooltip_copy' => "drag drop button image",
																				"is_hidden"=>"",
																				"default_filename"=>"mail.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"SBYQnWdCTfyqBJfP720T_mail.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"1485",
																				"default_url"=>"https://www.filepicker.io/api/file/HtBxguKYSpWl2TGnAMyI"
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "socialAddress",
																				"field_id"=> "flashvars.socialAddress",
																				'tooltip_copy' => "socialAddress",
																				"default_value"=>"http://ad.vantagelocal.com/children/social_child_136.swf",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "socialAddressSSL",
																				"field_id"=> "flashvars.socialAddressSSL",
																				'tooltip_copy' => "socialAddressSSL",
																				"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/children/social_child_136.swf",
																				"is_hidden"=>""
																				)
																			)
													)
													
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'SECOND CLICK BUTTON',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClickURL",
														"field_id"=> "flashvars.secondClickURL",
														"tooltip_copy"=> "secondClickURL",
														"default_value"=>"",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isSecondClickAlways",
														"field_id"=> "flashvars.isSecondClickAlways",
														"tooltip_copy"=> "isSecondClickAlways",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick300_x",
														"field_id"=> "flashvars.secondClick300_x",
														"tooltip_copy"=> "secondClick300_x",
														"default_value"=>"6",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick300_y",
														"field_id"=> "flashvars.secondClick300_y",
														"tooltip_copy"=> "secondClick300_y",
														"default_value"=>"216",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick336_x",
														"field_id"=> "flashvars.secondClick336_x",
														"tooltip_copy"=> "secondClick336_x",
														"default_value"=>"6",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick336_y",
														"field_id"=> "flashvars.secondClick336_y",
														"tooltip_copy"=> "secondClick336_y",
														"default_value"=>"246",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick728_x",
														"field_id"=> "flashvars.secondClick728_x",
														"tooltip_copy"=> "secondClick728_x",
														"default_value"=>"6",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick728_y",
														"field_id"=> "flashvars.secondClick728_y",
														"tooltip_copy"=> "secondClick728_y",
														"default_value"=>"55",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick160_x",
														"field_id"=> "flashvars.secondClick160_x",
														"tooltip_copy"=> "secondClick160_x",
														"default_value"=>"30",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick160_y",
														"field_id"=> "flashvars.secondClick160_y",
														"tooltip_copy"=> "secondClick160_y",
														"default_value"=>"510",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClickAddress",
														"field_id"=> "flashvars.secondClickAddress",
														"tooltip_copy"=> "secondClickAddress",
														"default_value"=>"http://ad.vantagelocal.com/buttons/facebook_dark.png",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClickAddressSSL",
														"field_id"=> "flashvars.secondClickAddressSSL",
														"tooltip_copy"=> "secondClickAddressSSL",
														"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/facebook_dark.png",
														"is_hidden"=>""
													)
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'CUSTOM',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isDebugMode",
														"field_id"=> "flashvars.isDebugMode",
														"tooltip_copy"=> "isDebugMode",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "forceHTML5",
														"field_id"=> "flashvars.forceHTML5",
														"tooltip_copy"=> "forceHTML5",
														"default_value"=>false,
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isClickToCall",
														"field_id"=> "flashvars.isClickToCall",
														"tooltip_copy"=> "isClickToCall",
														"default_value"=>false,
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "clickToCallNumber",
														"field_id"=> "flashvars.clickToCallNumber",
														"tooltip_copy"=> "Phone Number without dashes (+ ok)",
														"default_value"=>"",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "countdownSpinnerURL",
														"field_id"=> "flashvars.countdownSpinnerURL",
														"tooltip_copy"=> "countdownSpinnerURL",
														"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/countdown.png",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "countdownSpinnerGIF",
														"field_id"=> "flashvars.countdownSpinnerGIF",
														"tooltip_copy"=> "countdownSpinnerGIF",
														"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/preloader-red.gif",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isKillASMBLclickTag",
														"field_id"=> "flashvars.isKillASMBLclickTag",
														"tooltip_copy"=> "isKillASMBLclickTag",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isKillGrandpaClickTag",
														"field_id"=> "flashvars.isKillGrandpaClickTag",
														"tooltip_copy"=> "isKillGrandpaClickTag",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "animationClip",
														"field_id"=> "flashvars.animationClip",
														"tooltip_copy"=> "animationClip",
														"default_value"=>"holder",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "parentClickTagPath",
														"field_id"=> "flashvars.parentClickTagPath",
														"tooltip_copy"=> "parentClickTagPath",
														"default_value"=>"button_holder",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "hoverFolder",
														"field_id"=> "flashvars.hoverFolder",
														"tooltip_copy"=> "hoverFolder",
														"default_value"=>"http://ad.vantagelocal.com/hovers/",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "hoverFolderSSL",
														"field_id"=> "flashvars.hoverFolderSSL",
														"tooltip_copy"=> "hoverFolderSSL",
														"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/hovers/",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "videoChildAddress",
														"field_id"=> "flashvars.videoChildAddress",
														"tooltip_copy"=> "videoChildAddress",
														"default_value"=>"http://ad.vantagelocal.com/children/video_child_218.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "videoChildAddressSSL",
														"field_id"=> "flashvars.videoChildAddressSSL",
														"tooltip_copy"=> "videoChildAddressSSL",
														"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/children/video_child_218.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "mapChildAddress",
														"field_id"=> "flashvars.mapChildAddress",
														"tooltip_copy"=> "mapChildAddress",
														"default_value"=>"http://ad.vantagelocal.com/children/map_child_101.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "mapChildAddressSSL",
														"field_id"=> "flashvars.mapChildAddressSSL",
														"tooltip_copy"=> "mapChildAddressSSL",
														"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/children/map_child_101.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailChildAddress",
														"field_id"=> "flashvars.emailChildAddress",
														"tooltip_copy"=> "emailChildAddress",
														"default_value"=>"http://ad.vantagelocal.com/children/email_child_001.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailChildAddressSSL",
														"field_id"=> "flashvars.emailChildAddressSSL",
														"tooltip_copy"=> "emailChildAddressSSL",
														"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/children/email_child_001.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "trackingPixelAddressSSL",
														"field_id"=> "flashvars.trackingPixelAddressSSL",
														"tooltip_copy"=> "trackingPixelAddressSSL",
														"default_value"=>"https://81e2563e7a7102c7a523-4c272e1e114489a00a7c5f34206ea8d1.ssl.cf1.rackcdn.com/px.gif",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "trackingPixelAddress",
														"field_id"=> "flashvars.trackingPixelAddress",
														"tooltip_copy"=> "trackingPixelAddress",
														"default_value"=>"http://3100e55ffe038d736447-4c272e1e114489a00a7c5f34206ea8d1.r73.cf1.rackcdn.com/px.gif",
														"is_hidden"=>""
													)
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'EMAIL (beta)',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isRichMediaEmail",
														"field_id"=> "flashvars.isRichMediaEmail",
														"tooltip_copy"=> "isRichMediaEmail",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButtonImageURL",
														"field_id"=> "flashvars.emailButtonImageURL",
														"tooltip_copy"=> "emailButtonImageURL",
														"default_value"=>"http://ad.vantagelocal.com/buttons/coupon_btn_1.png",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButtonImageURLSSL",
														"field_id"=> "flashvars.emailButtonImageURLSSL",
														"tooltip_copy"=> "emailButtonImageURLSSL",
														"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/coupon_btn_1.png",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isAutoLoadEmail",
														"field_id"=> "flashvars.isAutoLoadEmail",
														"tooltip_copy"=> "isAutoLoadEmail",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton300_x",
														"field_id"=> "flashvars.emailButton300_x",
														"tooltip_copy"=> "emailButton300_x",
														"default_value"=>"53",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton300_y",
														"field_id"=> "flashvars.emailButton300_y",
														"tooltip_copy"=> "emailButton300_y",
														"default_value"=>"125",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton336_x",
														"field_id"=> "flashvars.emailButton336_x",
														"tooltip_copy"=> "emailButton336_x",
														"default_value"=>"246",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton336_y",
														"field_id"=> "flashvars.emailButton336_y",
														"tooltip_copy"=> "emailButton336_y",
														"default_value"=>"242",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton728_x",
														"field_id"=> "flashvars.emailButton728_x",
														"tooltip_copy"=> "emailButton728_x",
														"default_value"=>"443",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton728_y",
														"field_id"=> "flashvars.emailButton728_y",
														"tooltip_copy"=> "emailButton728_y",
														"default_value"=>"27",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton160_x",
														"field_id"=> "flashvars.emailButton160_x",
														"tooltip_copy"=> "emailButton160_x",
														"default_value"=>"60",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton160_y",
														"field_id"=> "flashvars.emailButton160_y",
														"tooltip_copy"=> "emailButton160_y",
														"default_value"=>"286",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email300_x",
														"field_id"=> "flashvars.email300_x",
														"tooltip_copy"=> "email300_x",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email300_y",
														"field_id"=> "flashvars.email300_y",
														"tooltip_copy"=> "email300_y",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email336_x",
														"field_id"=> "flashvars.email336_x",
														"tooltip_copy"=> "email336_x",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email336_y",
														"field_id"=> "flashvars.email336_y",
														"tooltip_copy"=> "email336_y",
														"default_value"=>"13",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email728_x",
														"field_id"=> "flashvars.email728_x",
														"tooltip_copy"=> "email728_x",
														"default_value"=>"607",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email728_y",
														"field_id"=> "flashvars.email728_y",
														"tooltip_copy"=> "email728_y",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email160_x",
														"field_id"=> "flashvars.email160_x",
														"tooltip_copy"=> "email160_x",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email160_y",
														"field_id"=> "flashvars.email160_y",
														"tooltip_copy"=> "email160_y",
														"default_value"=>"296",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailWidth300",
														"field_id"=> "flashvars.emailWidth300",
														"tooltip_copy"=> "emailWidth300",
														"default_value"=>"300",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailHeight300",
														"field_id"=> "flashvars.emailHeight300",
														"tooltip_copy"=> "emailHeight300",
														"default_value"=>"250",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailWidth336",
														"field_id"=> "flashvars.emailWidth336",
														"tooltip_copy"=> "emailWidth336",
														"default_value"=>"336",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailHeight336",
														"field_id"=> "flashvars.emailHeight336",
														"tooltip_copy"=> "emailHeight336",
														"default_value"=>"254",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailWidth728",
														"field_id"=> "flashvars.emailWidth728",
														"tooltip_copy"=> "emailWidth728",
														"default_value"=>"119",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailHeight728",
														"field_id"=> "flashvars.emailHeight728",
														"tooltip_copy"=> "emailHeight728",
														"default_value"=>"90",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailWidth160",
														"field_id"=> "flashvars.emailWidth160",
														"tooltip_copy"=> "emailWidth160",
														"default_value"=>"160",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailHeight160",
														"field_id"=> "flashvars.emailHeight160",
														"tooltip_copy"=> "emailHeight160",
														"default_value"=>"121",
														"is_hidden"=>""
													)
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'VERSION',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "xmlVersion",
														"field_id"=> "flashvars.xmlVersion",
														"tooltip_copy"=> "xmlVersion",
														"default_value"=>"200",
														"is_hidden"=>1
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "vantage class URL",
														"field_id"=> "flashvars.vantageURL",
														"tooltip_copy"=> "URL of the vantage class",
														"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/js/vantage/vantage.078.js",
														"is_hidden"=>""
													)
												)
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'BUILDER VERSION',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "VL Builder version",
														"field_id"=> "",
														"tooltip_copy"=> "This is the vantage local builder version",
														"default_value"=>$version,
														"is_hidden"=>1
													)
												)
							)
						);

			$json_blob = json_encode($config);
			print '<pre>'; print_r($json_blob); print '</pre>';
			echo "new config rows: ";
			$this->variables_model->insert_variables_config($version,$json_blob);
			echo "<br>new builder 2 blob rows: ";
			$this->variables_model->insert_new_blob_to_builder_version($version, $blob_version, $rich_grandpa_version);
		}else
		{	
			redirect('director'); 
		}
	}
	
public function build_navbar_config_swipe($version, $blob_version=NULL,$rich_grandpa_version=NULL){
		echo $version." and ".$blob_version;
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer','creative_uploader');
			redirect('login');
		}
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		if ($role == 'ops' or $role == 'admin')
		{

			$config = array(
							array(	
								'section_type' => 'block',
								'section_title' => 'SPEC AD (General Settings)',
								'ui_elements' => array(
									array(
											'section_type' => 'field',
											'field_type' => "file_picker",
											'field_label' => "Spec Ad Logo Image",
											"field_id"=> "files.specadimage",
											'tooltip_copy' => "Image at the top of the spec ad",
											"is_hidden"=>"",
											"default_filename"=>"learn.png",
											"default_is_writable"=>"true",
											"default_key"=>"Rex456KUSEy4QdWXjDyW_learn.png",
											"default_mimetype"=>"image/png",
											"default_size"=>"2910",
											"default_url"=>"https://www.filepicker.io/api/file/PQGXb2DtS6bEH9SoGvtQ"
									    ),
									array(
											'section_type' => 'field',
											'field_type' => "normal_text",
											'field_label' => "Logo Max height",
											"field_id"=> "flashvars.speclogoheight",
											'tooltip_copy' => "Height of logo div",
											"default_value"=>"45",
											"is_hidden"=>""
									),
									array(
											'section_type' => 'field',
										        'field_type' => "normal_text",
											'field_label' => "Logo horizontal alignment",
											"field_id"=> "flashvars.speclogoalign",
											'tooltip_copy' => "Alignment of logo along the top part of the ad. Left? right? center?",
											"default_value"=>"center",
											"is_hidden"=>""
									    ),	
									array(
										"section_type"=> "field",
										"field_type"=>"normal_text",
										"field_label"=> "Spec Ad Default Background Color",
										"field_id"=> "flashvars.specadbackgroundcolor",
										"tooltip_copy"=> "Background color for all Spec Ad slides",
										"default_value"=>"#999999",
										"is_hidden"=>""
									),
									array(
										"section_type"=> "field",
										"field_type"=>"normal_text",
										"field_label"=> "Video URL",
										"field_id"=> "flashvars.videoURL",
										"tooltip_copy"=> "videoURL",
										"default_value"=>"http://www.youtube.com/watch?v=K56yppSJRLw",
										"is_hidden"=>""
									),
									array(
										'section_type' => 'field',
										'field_type' => "normal_text",
										'field_label' => "Map Marker Titles",
										"field_id"=> "flashvars.markerTitles",
										'tooltip_copy' => "Pin Titles (Google Map Only), separated by semicolons",
										"default_value"=>"Title",
										"is_hidden"=>""
									),
									array(
										'section_type' => 'field',
										'field_type' => "normal_text",
										'field_label' => "Map Pin Latitudes",
										"field_id"=> "flashvars.mapPinLatitudes",
										'tooltip_copy' => "Must be in order separated by semicolons",
										"default_value"=>"50",
										"is_hidden"=>""
									),
									array(
										'section_type' => 'field',
										'field_type' => "normal_text",
										'field_label' => "Map Pin Longitudes",
										"field_id"=> "flashvars.mapPinLongitudes",
										'tooltip_copy' => "Must be in order separated by semicolons",
										"default_value"=>"50",
										"is_hidden"=>""
									),
									array(
										'section_type' => 'field',
										'field_type' => "normal_text",
										'field_label' => "Map Pin Descriptions",
										"field_id"=> "flashvars.mapPinDescriptions",
										'tooltip_copy' => "Address and Info when click the pin, separated by semicolons",
										"default_value"=>"DESCRIPTION",
										"is_hidden"=>""
									),
									array(
										 'section_type' => 'field',
										 'field_type' => "normal_text",
										 'field_label' => "Social Url To Share",
										 "field_id"=> "flashvars.urlToShare",
										 'tooltip_copy' => "URL of the page to share - use bit.ly",
										 "default_value"=>"",
										 "is_hidden"=>""
									),
									array(
										 'section_type' => 'field',
										 'field_type' => "normal_text",
										 'field_label' => "Click-through Landing Page URL",
										 "field_id"=> "flashvars.specURL",
										 'tooltip_copy' => "URL reached by clicking the background, call to action button , or the logo",
										 "default_value"=>"http://google.com",
										 "is_hidden"=>""
									),
									array(
										 'section_type' => 'field',
										 'field_type' => "normal_text",
										 'field_label' => "Nav Menu: Button Default Color",
										 "field_id"=> "flashvars.slidenavbtncolor",
										 'tooltip_copy' => "Color of button in its default state (not moused over, not current slide)",
										 "default_value"=>"#333333",
										 "is_hidden"=>""
									),
									array(
										'section_type' => 'field',
										'field_type' => "normal_text",
										'field_label' => "Nav Menu: Button Hover Color",
										"field_id"=> "flashvars.slidenavbtnhovercolor",
										'tooltip_copy' => "Color the button changes to when a mouse cursor rolls over it",
										"default_value"=>"#999999",
										"is_hidden"=>""
									),
									array(
										'section_type' => 'field',
										'field_type' => "normal_text",
										'field_label' => "Nav Menu: Button Selected Color",
										"field_id"=> "flashvars.slidenavbtnselectedcolor",
										'tooltip_copy' => "Color of button for the current slide (Ex. The video button on the video slide)",
										"default_value"=>"#888888",
										"is_hidden"=>""
									),
									array(
										'section_type' => 'field',
										'field_type' => "normal_text",
										'field_label' => "Nav Menu: Call To Action Button Color",
										"field_id"=> "flashvars.slidenavbtnctacolor",
										'tooltip_copy' => "Color the CTA button",
										"default_value"=>"#3b559f",
										"is_hidden"=>""
									),
									array(
										'section_type' => 'field',
										'field_type' => "file_picker",
										'field_label' => "Nav Menu: Call To Action Button Image",
										"field_id"=> "files.specadctabtn",
										'tooltip_copy' => "Image for the Call to Action button. we suggest a transparent .png sized 70x23",
										"is_hidden"=>"",
										"default_filename"=>"",
										"default_is_writable"=>"true",
										"default_key"=>"",
										"default_mimetype"=>"image/png",
										"default_size"=>"2910",
										"default_url"=>""
									),
									array(	
										 'section_type' => 'block',
										 'section_title' => 'Slide Images',
										 'ui_elements' => array(
												array(
													 'section_type' => 'field',
													 'field_type' => "file_picker",
													 'field_label' => "Slide Image 1",
													 "field_id"=> "files.slideImage0",
													 'tooltip_copy' => "drag drop slide image",
													 "is_hidden"=>"",
													 "default_filename"=>"",
													 "default_is_writable"=>"true",
													 "default_key"=>"",
													 "default_mimetype"=>"image/jpeg",
													 "default_size"=>"53062",
													 "default_url"=>"",
													 "default_width"=> 300,
													 "default_height"=> 250,
												),
												array(
													 'section_type' => 'field',
													 'field_type' => "file_picker",
													 'field_label' => "Slide Image 2",
													 "field_id"=> "files.slideImage1",
													 'tooltip_copy' => "drag drop slide image",
													 "is_hidden"=>"",
													 "default_filename"=>"",
													 "default_is_writable"=>"true",
													 "default_key"=>"",
													 "default_mimetype"=>"image/jpeg",
													 "default_size"=>"48695",
													 "default_url"=>"",
													 "default_width"=> 300,
													 "default_height"=> 250,
												),
												array(
													 'section_type' => 'field',
													 'field_type' => "file_picker",
													 'field_label' => "Slide Image 3",
													 "field_id"=> "files.slideImage2",
													 'tooltip_copy' => "drag drop slide image",
													 "is_hidden"=>"",
													 "default_filename"=>"social_text_share_us.png",
													 "default_is_writable"=>"true",
													 "default_key"=>"PFVmGSh2QeeNKjvu4pGb_social_text_share_us.png",
													 "default_mimetype"=>"image/jpeg",
													 "default_size"=>"51623",
													 "default_url"=>"https://www.filepicker.io/api/file/aRpDdRmPRsmWZVa0RSGc",
													 "default_width"=> 300,
													 "default_height"=> 250,
												),
												array(
													 'section_type' => 'field',
													 'field_type' => "file_picker",
													 'field_label' => "Slide Image 4",
													 "field_id"=> "files.slideImage3",
													 'tooltip_copy' => "drag drop slide image",
													 "is_hidden"=>"",
													 "default_filename"=>"",
													 "default_is_writable"=>"true",
													 "default_key"=>"",
													 "default_mimetype"=>"image/jpeg",
													 "default_size"=>"38610",
													 "default_url"=>"",
													 "default_width"=> 300,
													 "default_height"=> 250,
											 )
										 )
								 )
							 )
						),
						array(	
							 'section_type' => 'block',
							 'section_title' => 'GENERAL',
							 'ui_elements' => array(
									array(
										 "section_type"=> "field",
										 "field_type"=>"boolean",
										 "field_label"=> "showRichMenuAlways",
										 "field_id"=> "flashvars.showRichMenuAlways",
										 "tooltip_copy"=> "here is some tooltip copy for this variable",
										 "default_value"=>"false",
										 "is_hidden"=>""
									),
									array(
										 "section_type"=> "field",
										 "field_type"=>"normal_text",
										 "field_label"=> "closeButtonPadding",
										 "field_id"=> "flashvars.closeButtonPadding",
										 "tooltip_copy"=> "Change to 10 if Video or Map covers top right corner (for AdChoices)",
										 "default_value"=>"40",
										 "is_hidden"=>""
									),
									array(
										 "section_type"=> "field",
										 "field_type"=>"boolean",
										 "field_label"=> "isHoverToClick",
										 "field_id"=> "flashvars.isHoverToClick",
										 "tooltip_copy"=> "isHoverToClick",
										 "default_value"=>"true",
										 "is_hidden"=>""
									),
									array(
										 "section_type"=> "field",
										 "field_type"=>"boolean",
										 "field_label"=> "isReplay",
										 "field_id"=> "flashvars.isReplay",
										 "tooltip_copy"=> "isReplay",
										 "default_value"=>"false",
										 "is_hidden"=>""
									),
									array(
										 "section_type"=> "field",
										 "field_type"=>"boolean",
										 "field_label"=> "isGrandpaImageAlways",
										 "field_id"=> "flashvars.isGrandpaImageAlways",
										 "tooltip_copy"=> "Image Only Ad",
										 "default_value"=>"false",
										 "is_hidden"=>""
									),
									array(
										 "section_type"=> "field",
										 "field_type"=>"normal_text",
										 "field_label"=> "hoverName",
										 "field_id"=> "flashvars.hoverName",
										 "tooltip_copy"=> "Hover Effect - shine or pagepeel or stroke or none",
										 "default_value"=>"shine",
										 "is_hidden"=>""
									)
							 ),
						),
						array(	
							 'section_type' => 'block',
							 'section_title' => 'SLIDER',
							 'ui_elements' => array(
									array(
										 "section_type"=> "field",
										 "field_type"=>"normal_text",
										 "field_label"=> "numSlides",
										 "field_id"=> "flashvars.numSlides",
										 "tooltip_copy"=> "Number of Slides",
										 "default_value"=>3,
										 "is_hidden"=>""
									),
													array(
														'section_type' => 'block',
														'section_title' => 'Settings',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "continuous",
																				"field_id"=> "flashvars.continuous",
																				'tooltip_copy' => "continuous",
																				"default_value"=>false,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "startSlide",
																				"field_id"=> "flashvars.startSlide",
																				'tooltip_copy' => "start on slide number",
																				"default_value"=>0,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "Auto Play Slider Seconds, or false",
																				"field_id"=> "flashvars.auto",
																				'tooltip_copy' => "auto play slider",
																				"default_value"=>false,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "Stop Autoplay on Slide Number",
																				"field_id"=> "flashvars.stopSlide",
																				'tooltip_copy' => "Stop Auto Play on this Slide Number, or false",
																				"default_value"=>false,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "isDesktopSwipe",
																				"field_id"=> "flashvars.isDesktopSwipe",
																				'tooltip_copy' => "isDesktopSwipe",
																				"default_value"=>false,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "disableScroll",
																				"field_id"=> "flashvars.disableScroll",
																				'tooltip_copy' => "disableScroll",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "stopPropagation",
																				"field_id"=> "flashvars.stopPropagation",
																				'tooltip_copy' => "stopPropagation",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "hoverAction",
																				"field_id"=> "flashvars.hoverAction",
																				'tooltip_copy' => "hoverAction",
																				"default_value"=>"expand",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Expandable',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "isExpandable",
																				"field_id"=> "flashvars.isExpandable",
																				'tooltip_copy' => "isExpandable",
																				"default_value"=>false,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "isCountdown",
																				"field_id"=> "flashvars.isCountdown",
																				'tooltip_copy' => "isCountdown",
																				"default_value"=>false,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "expandSlide",
																				"field_id"=> "flashvars.expandSlide",
																				'tooltip_copy' => "Which slide number expands, starting with 0",
																				"default_value"=>2,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "buttonWrapperLeft",
																				"field_id"=> "flashvars.buttonWrapperLeft",
																				'tooltip_copy' => "buttonWrapperLeft",
																				"default_value"=>10,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "buttonWrapperTop",
																				"field_id"=> "flashvars.buttonWrapperTop",
																				'tooltip_copy' => "buttonWrapperTop",
																				"default_value"=>100,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "showCloseButton",
																				"field_id"=> "flashvars.showCloseButton",
																				'tooltip_copy' => "showCloseButton",
																				"default_value"=>false,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "closeButton",
																				"field_id"=> "files.closeButton",
																				'tooltip_copy' => "drag drop close image",
																				"is_hidden"=>"",
																				"default_filename"=>"close.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"8shg1yQ4T5iGsjiDYNLL_close.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"1522",
																				"default_url"=>"https://www.filepicker.io/api/file/tSrK7lJRAGzWh8fpTeb6"
																				),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "numButtons",
																				"field_id"=> "flashvars.numButtons",
																				"tooltip_copy"=> "Number of Buttons",
																				"default_value"=>4,
																				"is_hidden"=>""
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Button Image 1",
																				"field_id"=> "files.buttonImage0",
																				'tooltip_copy' => "drag drop button image",
																				"is_hidden"=>"",
																				"default_filename"=>"learn.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"Rex456KUSEy4QdWXjDyW_learn.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"2910",
																				"default_url"=>"https://www.filepicker.io/api/file/PQGXb2DtS6bEH9SoGvtQ"
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Button Image 2",
																				"field_id"=> "files.buttonImage1",
																				'tooltip_copy' => "drag drop button image",
																				"is_hidden"=>"",
																				"default_filename"=>"learn.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"Rex456KUSEy4QdWXjDyW_learn.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"2910",
																				"default_url"=>"https://www.filepicker.io/api/file/PQGXb2DtS6bEH9SoGvtQ"
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "secondClick",
																				"field_id"=> "flashvars.secondClick",
																				'tooltip_copy' => "secondClick",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Button Image 3",
																				"field_id"=> "files.buttonImage2",
																				'tooltip_copy' => "drag drop button image",
																				"is_hidden"=>"",
																				"default_filename"=>"learn.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"Rex456KUSEy4QdWXjDyW_learn.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"2910",
																				"default_url"=>"https://www.filepicker.io/api/file/PQGXb2DtS6bEH9SoGvtQ"
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "thirdClick",
																				"field_id"=> "flashvars.thirdClick",
																				'tooltip_copy' => "thirdClick",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Button Image 4",
																				"field_id"=> "files.buttonImage3",
																				'tooltip_copy' => "drag drop slide image",
																				"is_hidden"=>"",
																				"default_filename"=>"learn.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"Rex456KUSEy4QdWXjDyW_learn.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"2910",
																				"default_url"=>"https://www.filepicker.io/api/file/PQGXb2DtS6bEH9SoGvtQ"
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "fourthClick",
																				"field_id"=> "flashvars.fourthClick",
																				'tooltip_copy' => "fourthClick",
																				"default_value"=>"",
																				"is_hidden"=>""
																				)
																		)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Spec Ad NavBar',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useNavbar",
																				"field_id"=> "flashvars.useNavbar",
																				'tooltip_copy' => "useNavbar",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'block',
																				'section_title' => 'Logo Image',
																				'ui_elements' => array(
																							array(
																							    'section_type' => 'field',
																							    'field_type' => "boolean",
																							    'field_label' => "useLogoImage",
																							    "field_id"=> "flashvars.useSpecLogo",
																							    'tooltip_copy' => "Use Logo for spec ad",
																							    "default_value"=>true,
																							    "is_hidden"=>""
																							)																																		
																				)
																			)
															)

													),
													array(
														'section_type' => 'block',
														'section_title' => 'Custom Slider Settings',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "text_area",
																				'field_label' => "customCSS",
																				"field_id"=> "flashvars.customCSS",
																				'tooltip_copy' => "Custom CSS",
																				"default_value"=>".vl-button img:hover,.vl-close img:hover{-webkit-box-shadow: 0px 0px 2px 2px rgba(255, 255, 255, .50);box-shadow: 0px 0px 2px 2px rgba(255, 255, 255, .50);}",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "isCustomButtonCSS",
																				"field_id"=> "flashvars.isCustomButtonCSS",
																				'tooltip_copy' => "isCountdown",
																				"default_value"=>false,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "CSS Button Color 1",
																				"field_id"=> "flashvars.buttonColor1",
																				'tooltip_copy' => "buttonColor1",
																				"default_value"=>"#333333",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "CSS Hover Button Color 1",
																				"field_id"=> "flashvars.hoverButtonColor1",
																				'tooltip_copy' => "hoverButtonColor1",
																				"default_value"=>"#333333",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "CSS Button Color 2",
																				"field_id"=> "flashvars.buttonColor2",
																				'tooltip_copy' => "buttonColor2",
																				"default_value"=>"#333333",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "CSS Hover Button Color 2",
																				"field_id"=> "flashvars.hoverButtonColor2",
																				'tooltip_copy' => "hoverButtonColor2",
																				"default_value"=>"#333333",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "CSS Button Color 3",
																				"field_id"=> "flashvars.buttonColor3",
																				'tooltip_copy' => "buttonColor3",
																				"default_value"=>"#333333",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "CSS Hover Button Color 3",
																				"field_id"=> "flashvars.hoverButtonColor3",
																				'tooltip_copy' => "hoverButtonColor3",
																				"default_value"=>"#333333",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "CSS Button Color 4",
																				"field_id"=> "flashvars.buttonColor4",
																				'tooltip_copy' => "buttonColor4",
																				"default_value"=>"#333333",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "CSS Hover Button Color 4",
																				"field_id"=> "flashvars.hoverButtonColor4",
																				'tooltip_copy' => "hoverButtonColor4",
																				"default_value"=>"#333333",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "hover cursor image",
																				"field_id"=> "files.hoverCursor",
																				'tooltip_copy' => "hover cursor image",
																				"is_hidden"=>"",
																				"default_filename"=>"grab.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"gYm55xIWR5i1bpvM2yEr_grab.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"99",
																				"default_url"=>"https://www.filepicker.io/api/file/fSZeqy7FT2ZZESJ7uLHU"
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "dragging cursor image",
																				"field_id"=> "files.draggingCursor",
																				'tooltip_copy' => "dragging cursor image",
																				"is_hidden"=>"",
																				"default_filename"=>"grabbing.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"SGnmgOioRNWMQB8Zz9fW_grabbing.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"2889",
																				"default_url"=>"https://www.filepicker.io/api/file/3yaVyJjSYK8ii7UUnDc8"
																				)
																		)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Message Box',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "messageBoxText",
																				"field_id"=> "flashvars.messageBoxText",
																				'tooltip_copy' => "messageBoxText",
																				"default_value"=>"ROLLOVER TO PLAY",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "messageMobileText",
																				"field_id"=> "flashvars.messageMobileText",
																				'tooltip_copy' => "messageMobileText",
																				"default_value"=>"SWIPE TO EXPAND",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "messageBoxHeight",
																				"field_id"=> "flashvars.messageBoxHeight",
																				'tooltip_copy' => "messageBoxHeight",
																				"default_value"=>30,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "messageBoxWidth",
																				"field_id"=> "flashvars.messageBoxWidth",
																				'tooltip_copy' => "messageBoxWidth",
																				"default_value"=>220,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "messageBoxTop",
																				"field_id"=> "flashvars.messageBoxTop",
																				'tooltip_copy' => "messageBoxTop",
																				"default_value"=>90,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "messageBoxLeft",
																				"field_id"=> "flashvars.messageBoxLeft",
																				'tooltip_copy' => "messageBoxLeft",
																				"default_value"=>70,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "messageBoxTextColor",
																				"field_id"=> "flashvars.messageBoxTextColor",
																				'tooltip_copy' => "messageBoxTextColor",
																				"default_value"=>"#ffffff",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "messageBoxTextSize",
																				"field_id"=> "flashvars.messageBoxTextSize",
																				'tooltip_copy' => "messageBoxTextSize",
																				"default_value"=>15,
																				"is_hidden"=>""
																				)
																			)
													)
													
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'VIDEO',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isVideo",
														"field_id"=> "flashvars.isVideo",
														"tooltip_copy"=> "isVideo",
														"default_value"=>true,
														"is_hidden"=>""
														),
													array(
														'section_type' => 'block',
														'section_title' => 'Video Settings',
														'ui_elements' => array(
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "videoPlayer",
																				"field_id"=> "flashvars.videoPlayer",
																				"tooltip_copy"=> "videojs, youtube, jwplayer",
																				"default_value"=>"youtube",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "videoSlideNum",
																				"field_id"=> "flashvars.videoSlideNum",
																				"tooltip_copy"=> "videoSlideNum",
																				"default_value"=>1,
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "videoID",
																				"field_id"=> "flashvars.videoID",
																				"tooltip_copy"=> "videoID",
																				"default_value"=>"",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "videoPoster",
																				"field_id"=> "flashvars.videoPoster",
																				"tooltip_copy"=> "videoPoster",
																				"default_value"=>"",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "videoMP4",
																				"field_id"=> "flashvars.videoMP4",
																				"tooltip_copy"=> "videoMP4",
																				"default_value"=>"http://content.bitsontherun.com/videos/BwAv0fYF-0Kr0ekT4.mp4",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "videoWEBM",
																				"field_id"=> "flashvars.videoWEBM",
																				"tooltip_copy"=> "videoWEBM",
																				"default_value"=>"https://0267a94cab6d385b9024-b73241aae133d24e20527933c2ff6c10.ssl.cf1.rackcdn.com/ram_trucks_drama.webm",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "videoOGV",
																				"field_id"=> "flashvars.videoOGV",
																				"tooltip_copy"=> "videoOGV",
																				"default_value"=>"https://0267a94cab6d385b9024-b73241aae133d24e20527933c2ff6c10.ssl.cf1.rackcdn.com/ram_trucks_drama.ogv",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"boolean",
																				"field_label"=> "isAutoLoadVideo",
																				"field_id"=> "flashvars.isAutoLoadVideo",
																				"tooltip_copy"=> "isAutoLoadVideo",
																				"default_value"=>"true",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"boolean",
																				"field_label"=> "autoPlay",
																				"field_id"=> "flashvars.autoPlay",
																				"tooltip_copy"=> "autoPlay",
																				"default_value"=>"false",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"boolean",
																				"field_label"=> "isPlayMuted",
																				"field_id"=> "flashvars.isPlayMuted",
																				"tooltip_copy"=> "isPlayMuted",
																				"default_value"=>"false",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"boolean",
																				"field_label"=> "isHideAdUntilLoadedBugPatch",
																				"field_id"=> "flashvars.isHideAdUntilLoadedBugPatch",
																				"tooltip_copy"=> "Use CSS to hide the ad until youtube is done loading so if user clicks too soon, slider does not break (buggy patch)",
																				"default_value"=>true,
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "numAutoPlaySeconds",
																				"field_id"=> "flashvars.numAutoPlaySeconds",
																				"tooltip_copy"=> "numAutoPlaySeconds",
																				"default_value"=>"5",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"boolean",
																				"field_label"=> "scaleToFillFullscreenBG",
																				"field_id"=> "flashvars.scaleToFillFullscreenBG",
																				"tooltip_copy"=> "scaleToFillFullscreenBG",
																				"default_value"=>"false",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "fullscreenImageURL",
																				"field_id"=> "flashvars.fullscreenImageURL",
																				"tooltip_copy"=> "fullscreenImageURL",
																				"default_value"=>"",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "fullscreenImageURLSSL",
																				"field_id"=> "flashvars.fullscreenImageURLSSL",
																				"tooltip_copy"=> "fullscreenImageURLSSL",
																				"default_value"=>"",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "playButtonImageURL",
																				"field_id"=> "flashvars.playButtonImageURL",
																				"tooltip_copy"=> "playButtonImageURL",
																				"default_value"=>"http://ad.vantagelocal.com/buttons/white_20x20_play.png",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "playButtonImageURLSSL",
																				"field_id"=> "flashvars.playButtonImageURLSSL",
																				"tooltip_copy"=> "playButtonImageURLSSL",
																				"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/white_20x20_play.png",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "mobilePlayButtonImageURL",
																				"field_id"=> "flashvars.mobilePlayButtonImageURL",
																				"tooltip_copy"=> "mobilePlayButtonImageURL",
																				"default_value"=>"http://ad.vantagelocal.com/buttons/play_btn_white_mobile.png",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "mobilePlayButtonImageURLSSL",
																				"field_id"=> "flashvars.mobilePlayButtonImageURLSSL",
																				"tooltip_copy"=> "mobilePlayButtonImageURLSSL",
																				"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/play_btn_white_mobile.png",
																				"is_hidden"=>""
																			)
																		)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Play Button Positions',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton_x",
																				"field_id"=> "flashvars.playButton_x",
																				'tooltip_copy' => "playButton_x",
																				"default_value"=>"150",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton_y",
																				"field_id"=> "flashvars.playButton_y",
																				'tooltip_copy' => "playButton_y",
																				"default_value"=>"125",
																				"is_hidden"=>""
																				)
																		)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Video Position',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video_x",
																				"field_id"=> "flashvars.video_x",
																				'tooltip_copy' => "video_x",
																				"default_value"=>"5",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video_y",
																				"field_id"=> "flashvars.video_y",
																				'tooltip_copy' => "video_y",
																				"default_value"=>"50",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Video Size',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoWidth",
																				"field_id"=> "flashvars.videoWidth",
																				'tooltip_copy' => "videoWidth",
																				"default_value"=>"290",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoHeight",
																				"field_id"=> "flashvars.videoHeight",
																				'tooltip_copy' => "videoHeight",
																				"default_value"=>"170",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Fullscreen',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "fullscreenBackgroundColor",
																				"field_id"=> "flashvars.fullscreenBackgroundColor",
																				'tooltip_copy' => "fullscreenBackgroundColor",
																				"default_value"=>"252525",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "forceFullscreenVideoWidth",
																				"field_id"=> "flashvars.forceFullscreenVideoWidth",
																				'tooltip_copy' => "forceFullscreenVideoWidth",
																				"default_value"=>"800",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "forceFullscreenVideoHeight",
																				"field_id"=> "flashvars.forceFullscreenVideoHeight",
																				'tooltip_copy' => "forceFullscreenVideoHeight",
																				"default_value"=>"450",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Player Colors',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timelineDownloadColor",
																				"field_id"=> "flashvars.timelineDownloadColor",
																				'tooltip_copy' => "timelineDownloadColor",
																				"default_value"=>"168DFF",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timelineBackgroundColor",
																				"field_id"=> "flashvars.timelineBackgroundColor",
																				'tooltip_copy' => "timelineBackgroundColor",
																				"default_value"=>"004993",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsColor",
																				"field_id"=> "flashvars.controlsColor",
																				'tooltip_copy' => "controlsColor",
																				"default_value"=>"888888",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsHighlightColor",
																				"field_id"=> "flashvars.controlsHighlightColor",
																				'tooltip_copy' => "controlsHighlightColor",
																				"default_value"=>"BBBBBB",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playerBackgroundColor",
																				"field_id"=> "flashvars.playerBackgroundColor",
																				'tooltip_copy' => "playerBackgroundColor",
																				"default_value"=>"000000",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Video Controls',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useTimeline",
																				"field_id"=> "flashvars.useTimeline",
																				'tooltip_copy' => "useTimeline",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useTimeDisplay",
																				"field_id"=> "flashvars.useTimeDisplay",
																				'tooltip_copy' => "useTimeDisplay",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useSpeaker",
																				"field_id"=> "flashvars.useSpeaker",
																				'tooltip_copy' => "useSpeaker",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useVolLevel",
																				"field_id"=> "flashvars.useVolLevel",
																				'tooltip_copy' => "useVolLevel",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useFullScreen",
																				"field_id"=> "flashvars.useFullScreen",
																				'tooltip_copy' => "useFullScreen",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useInfoButton",
																				"field_id"=> "flashvars.useInfoButton",
																				'tooltip_copy' => "useInfoButton",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useQualityMenu",
																				"field_id"=> "flashvars.useQualityMenu",
																				'tooltip_copy' => "useQualityMenu",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useTimeTooltip",
																				"field_id"=> "flashvars.useTimeTooltip",
																				'tooltip_copy' => "useTimeTooltip",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "usePlayPause",
																				"field_id"=> "flashvars.usePlayPause",
																				'tooltip_copy' => "usePlayPause",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Custom Video',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "previewImg",
																				"field_id"=> "flashvars.previewImg",
																				'tooltip_copy' => "previewImg",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "initialVol",
																				"field_id"=> "flashvars.initialVol",
																				'tooltip_copy' => "initialVol",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "playPauseOverVideo",
																				"field_id"=> "flashvars.playPauseOverVideo",
																				'tooltip_copy' => "playPauseOverVideo",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "bufferTime",
																				"field_id"=> "flashvars.bufferTime",
																				'tooltip_copy' => "bufferTime",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "belowVideo",
																				"field_id"=> "flashvars.belowVideo",
																				'tooltip_copy' => "belowVideo",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "use3Dstyle",
																				"field_id"=> "flashvars.use3Dstyle",
																				'tooltip_copy' => "use3Dstyle",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsAlpha",
																				"field_id"=> "flashvars.controlsAlpha",
																				'tooltip_copy' => "controlsAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playerBackgroundAlpha",
																				"field_id"=> "flashvars.playerBackgroundAlpha",
																				'tooltip_copy' => "playerBackgroundAlpha",
																				"default_value"=>"80",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useBackgroundGradient",
																				"field_id"=> "flashvars.useBackgroundGradient",
																				'tooltip_copy' => "useBackgroundGradient",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "normalTextColor",
																				"field_id"=> "flashvars.normalTextColor",
																				'tooltip_copy' => "normalTextColor",
																				"default_value"=>"777777",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "highlightTextColor",
																				"field_id"=> "flashvars.highlightTextColor",
																				'tooltip_copy' => "highlightTextColor",
																				"default_value"=>"999999",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "popupBackgroundColor",
																				"field_id"=> "flashvars.popupBackgroundColor",
																				'tooltip_copy' => "popupBackgroundColor",
																				"default_value"=>"777777",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "popupBackgroundColor",
																				"field_id"=> "flashvars.popupBackgroundColor",
																				'tooltip_copy' => "popupBackgroundColor",
																				"default_value"=>"000000",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "popupBackgroundAlpha",
																				"field_id"=> "flashvars.popupBackgroundAlpha",
																				'tooltip_copy' => "popupBackgroundAlpha",
																				"default_value"=>"80",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsSeparation",
																				"field_id"=> "flashvars.controlsSeparation",
																				'tooltip_copy' => "controlsSeparation",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsBarHeight",
																				"field_id"=> "flashvars.controlsBarHeight",
																				'tooltip_copy' => "controlsBarHeight",
																				"default_value"=>"25",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsScale",
																				"field_id"=> "flashvars.controlsScale",
																				'tooltip_copy' => "controlsScale",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "textControlsScale",
																				"field_id"=> "flashvars.textControlsScale",
																				'tooltip_copy' => "textControlsScale",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timeTooltipScale",
																				"field_id"=> "flashvars.timeTooltipScale",
																				'tooltip_copy' => "timeTooltipScale",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useControlsGradient",
																				"field_id"=> "flashvars.useControlsGradient",
																				'tooltip_copy' => "useControlsGradient",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useLoadingRing",
																				"field_id"=> "flashvars.useLoadingRing",
																				'tooltip_copy' => "useLoadingRing",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timelineDownloadAlpha",
																				"field_id"=> "flashvars.timelineDownloadAlpha",
																				'tooltip_copy' => "timelineDownloadAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timelineBackgroundAlpha",
																				"field_id"=> "flashvars.timelineBackgroundAlpha",
																				'tooltip_copy' => "timelineBackgroundAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "volBarColor",
																				"field_id"=> "flashvars.volBarColor",
																				'tooltip_copy' => "volBarColor",
																				"default_value"=>"168DFF",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "volBarAlpha",
																				"field_id"=> "flashvars.volBarAlpha",
																				'tooltip_copy' => "volBarAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "volBackgroundColor",
																				"field_id"=> "flashvars.volBackgroundColor",
																				'tooltip_copy' => "volBackgroundColor",
																				"default_value"=>"004993",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "volBackgroundAlpha",
																				"field_id"=> "flashvars.volBackgroundAlpha",
																				'tooltip_copy' => "volBackgroundAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timeTooltipColor",
																				"field_id"=> "flashvars.timeTooltipColor",
																				'tooltip_copy' => "timeTooltipColor",
																				"default_value"=>"666666",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timeTooltipTextColor",
																				"field_id"=> "flashvars.timeTooltipTextColor",
																				'tooltip_copy' => "timeTooltipTextColor",
																				"default_value"=>"DDDDDD",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "tooltipShadow",
																				"field_id"=> "flashvars.tooltipShadow",
																				'tooltip_copy' => "tooltipShadow",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoPath",
																				"field_id"=> "flashvars.logoPath",
																				'tooltip_copy' => "logoPath",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoURL",
																				"field_id"=> "flashvars.logoURL",
																				'tooltip_copy' => "logoURL",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoWindow",
																				"field_id"=> "flashvars.logoWindow",
																				'tooltip_copy' => "logoWindow",
																				"default_value"=>"_blank",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoPosition",
																				"field_id"=> "flashvars.logoPosition",
																				'tooltip_copy' => "logoPosition",
																				"default_value"=>"top-right",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoXmargin",
																				"field_id"=> "flashvars.logoXmargin",
																				'tooltip_copy' => "logoXmargin",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoYmargin",
																				"field_id"=> "flashvars.logoYmargin",
																				'tooltip_copy' => "logoYmargin",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoAlpha",
																				"field_id"=> "flashvars.logoAlpha",
																				'tooltip_copy' => "logoAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoDisplayTime",
																				"field_id"=> "flashvars.logoDisplayTime",
																				'tooltip_copy' => "logoDisplayTime",
																				"default_value"=>"0",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descWidth",
																				"field_id"=> "flashvars.descWidth",
																				'tooltip_copy' => "descWidth",
																				"default_value"=>"220",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descPosition",
																				"field_id"=> "flashvars.descPosition",
																				'tooltip_copy' => "descPosition",
																				"default_value"=>"top-left",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descTextColor",
																				"field_id"=> "flashvars.descTextColor",
																				'tooltip_copy' => "descTextColor",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descXmargin",
																				"field_id"=> "flashvars.descXmargin",
																				'tooltip_copy' => "descXmargin",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descYmargin",
																				"field_id"=> "flashvars.descYmargin",
																				'tooltip_copy' => "descYmargin",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descTextColor",
																				"field_id"=> "flashvars.descTextColor",
																				'tooltip_copy' => "descTextColor",
																				"default_value"=>"EEEEEE",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descTextSize",
																				"field_id"=> "flashvars.descTextSize",
																				'tooltip_copy' => "descTextSize",
																				"default_value"=>"14",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descBackgroundColor",
																				"field_id"=> "flashvars.descBackgroundColor",
																				'tooltip_copy' => "descBackgroundColor",
																				"default_value"=>"000000",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descBackgroundAlpha",
																				"field_id"=> "flashvars.descBackgroundAlpha",
																				'tooltip_copy' => "descBackgroundAlpha",
																				"default_value"=>"50",
																				"is_hidden"=>""
																				)
																			)
													)
												)
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'MAP',
								'ui_elements' => array(
													array(
														'section_type' => 'field',
														'field_type' => "boolean",
														'field_label' => "isRichMediaMap",
														"field_id"=> "flashvars.isRichMediaMap",
														'tooltip_copy' => "Turns map on if true",
														"default_value"=>"true",
														"is_hidden"=>""
														),
													array(
														'section_type' => 'block',
														'section_title' => 'Load Settings',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapSlideNum",
																				"field_id"=> "flashvars.mapSlideNum",
																				'tooltip_copy' => "Map Slide Number",
																				"default_value"=>2,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButtonImageURL",
																				"field_id"=> "flashvars.mapButtonImageURL",
																				'tooltip_copy' => "mapButtonImageURL",
																				"default_value"=>"http://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.r9.cf1.rackcdn.com/buttons/map_pin_6.png",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButtonImageURLSSL",
																				"field_id"=> "flashvars.mapButtonImageURLSSL",
																				'tooltip_copy' => "mapButtonImageURLSSL",
																				"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/map_pin_6.png",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "isAutoLoadMap",
																				"field_id"=> "flashvars.isAutoLoadMap",
																				'tooltip_copy' => "Load the map automatically",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapDefaultZoomLevel",
																				"field_id"=> "flashvars.mapDefaultZoomLevel",
																				'tooltip_copy' => "Lower number means farther into outerspace",
																				"default_value"=>"11",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Load Map Button',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton_x",
																				"field_id"=> "flashvars.mapButton_x",
																				'tooltip_copy' => "mapButton_x",
																				"default_value"=>"260",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton_y",
																				"field_id"=> "flashvars.mapButton_y",
																				'tooltip_copy' => "mapButton_y",
																				"default_value"=>"197",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Map Position',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map_x",
																				"field_id"=> "flashvars.map_x",
																				'tooltip_copy' => "map_x",
																				"default_value"=>"5",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map_y",
																				"field_id"=> "flashvars.map_y",
																				'tooltip_copy' => "map_y",
																				"default_value"=>"50",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Map Size',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapWidth",
																				"field_id"=> "flashvars.mapWidth",
																				'tooltip_copy' => "mapWidth",
																				"default_value"=>"290",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapHeight",
																				"field_id"=> "flashvars.mapHeight",
																				'tooltip_copy' => "mapHeight",
																				"default_value"=>"170",
																				"is_hidden"=>""
																				)
																			)
													)
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'SOCIAL',
								'ui_elements' => array(
													array(
														'section_type' => 'field',
														'field_type' => "boolean",
														'field_label' => "useShareButtons",
														"field_id"=> "flashvars.useShareButtons",
														'tooltip_copy' => "Use Social on or off",
														"default_value"=>"true",
														"is_hidden"=>""
														),
													array(
														'section_type' => 'block',
														'section_title' => 'Settings',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "socialSlideNum",
																				"field_id"=> "flashvars.socialSlideNum",
																				'tooltip_copy' => "Social Slide Number",
																				"default_value"=>3,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useFacebook",
																				"field_id"=> "flashvars.useFacebook",
																				'tooltip_copy' => "useFacebook",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useTwitter",
																				"field_id"=> "flashvars.useTwitter",
																				'tooltip_copy' => "useTwitter",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useGPlus",
																				"field_id"=> "flashvars.useGPlus",
																				'tooltip_copy' => "useGPlus",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useLinkedIn",
																				"field_id"=> "flashvars.useLinkedIn",
																				'tooltip_copy' => "useLinkedIn",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "useEmail",
																				"field_id"=> "flashvars.useEmail",
																				'tooltip_copy' => "useEmail",
																				"default_value"=>true,
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "shareTitle",
																				"field_id"=> "flashvars.shareTitle",
																				'tooltip_copy' => "Title to share on LinkedIn",
																				"default_value"=>"Share your Message",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "shareNotes",
																				"field_id"=> "flashvars.shareNotes",
																				'tooltip_copy' => "This is the Summary on LinkedIn",
																				"default_value"=>"Share your custom message here!",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "twitterText",
																				"field_id"=> "flashvars.twitterText",
																				'tooltip_copy' => "Text to share on Twitter (urlToShare will be added to end)",
																				"default_value"=>"Share your custom message here!",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "shareLabels",
																				"field_id"=> "flashvars.shareLabels",
																				'tooltip_copy' => "Google Bookmarks (discontinued)",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "shareSource",
																				"field_id"=> "flashvars.shareSource",
																				'tooltip_copy' => "Source for LinkedIn - Client URL",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mailSubject",
																				"field_id"=> "flashvars.mailSubject",
																				'tooltip_copy' => "Subject of the email message",
																				"default_value"=>"Shere Your Message",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mailBody",
																				"field_id"=> "flashvars.mailBody",
																				'tooltip_copy' => "Body of the email message (no HTML)",
																				"default_value"=>"Share your custom message here!",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "isSocialAtStart",
																				"field_id"=> "flashvars.isSocialAtStart",
																				'tooltip_copy' => "Show the buttons at start of animation",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "socialVideoOnly",
																				"field_id"=> "flashvars.socialVideoOnly",
																				'tooltip_copy' => "Show social only in the video",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "boolean",
																				'field_label' => "hideSocialOnChildLoad",
																				"field_id"=> "flashvars.hideSocialOnChildLoad",
																				'tooltip_copy' => "Hide social buttons when map or video loads",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Social Buttons',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social_x",
																				"field_id"=> "flashvars.social_x",
																				'tooltip_copy' => "social_x",
																				"default_value"=>"90",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social_y",
																				"field_id"=> "flashvars.social_y",
																				'tooltip_copy' => "social_y",
																				"default_value"=>"110",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "socialWidth",
																				"field_id"=> "flashvars.socialWidth",
																				'tooltip_copy' => "socialWidth",
																				"default_value"=>"130",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "socialColor",
																				"field_id"=> "flashvars.socialColor",
																				'tooltip_copy' => "socialColor",
																				"default_value"=>"#333333",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Facebook Button Image",
																				"field_id"=> "files.facebookButtonImage",
																				'tooltip_copy' => "drag drop button image",
																				"is_hidden"=>"",
																				"default_filename"=>"facebook.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"Pu2mgO6MRSqTvTSgwOux_facebook.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"1247",
																				"default_url"=>"https://www.filepicker.io/api/file/KJytovR0RXSZFfQWsbGR"
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Twitter Button Image",
																				"field_id"=> "files.twitterButtonImage",
																				'tooltip_copy' => "drag drop button image",
																				"is_hidden"=>"",
																				"default_filename"=>"twitter.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"XkNeK3yASYsX0xhc4zua_twitter.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"1374",
																				"default_url"=>"https://www.filepicker.io/api/file/gJTtgLOLRMmTXyNcEMPU"
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "LinkedIn Button Image",
																				"field_id"=> "files.linkedinButtonImage",
																				'tooltip_copy' => "drag drop button image",
																				"is_hidden"=>"",
																				"default_filename"=>"linkedin.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"ujUqQEz5QuGqnyGjb7Go_linkedin.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"1292",
																				"default_url"=>"https://www.filepicker.io/api/file/qJIfVH3qTgGVcvzYIMqg"
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Google Plus Button Image",
																				"field_id"=> "files.googleButtonImage",
																				'tooltip_copy' => "drag drop button image",
																				"is_hidden"=>"",
																				"default_filename"=>"google.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"kuoti3MyQAmmOmLAaCzJ_google.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"1633",
																				"default_url"=>"https://www.filepicker.io/api/file/Eh4SBaPTRcuYOJoTaOjq"
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "file_picker",
																				'field_label' => "Email Button Image",
																				"field_id"=> "files.emailButtonImage",
																				'tooltip_copy' => "drag drop button image",
																				"is_hidden"=>"",
																				"default_filename"=>"mail.png",
																				"default_is_writable"=>"true",
																				"default_key"=>"SBYQnWdCTfyqBJfP720T_mail.png",
																				"default_mimetype"=>"image/png",
																				"default_size"=>"1485",
																				"default_url"=>"https://www.filepicker.io/api/file/HtBxguKYSpWl2TGnAMyI"
																			),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "socialAddress",
																				"field_id"=> "flashvars.socialAddress",
																				'tooltip_copy' => "socialAddress",
																				"default_value"=>"http://ad.vantagelocal.com/children/social_child_136.swf",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "socialAddressSSL",
																				"field_id"=> "flashvars.socialAddressSSL",
																				'tooltip_copy' => "socialAddressSSL",
																				"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/children/social_child_136.swf",
																				"is_hidden"=>""
																				)
																			)
													)
													
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'SECOND CLICK BUTTON',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClickURL",
														"field_id"=> "flashvars.secondClickURL",
														"tooltip_copy"=> "secondClickURL",
														"default_value"=>"",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isSecondClickAlways",
														"field_id"=> "flashvars.isSecondClickAlways",
														"tooltip_copy"=> "isSecondClickAlways",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick300_x",
														"field_id"=> "flashvars.secondClick300_x",
														"tooltip_copy"=> "secondClick300_x",
														"default_value"=>"6",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick300_y",
														"field_id"=> "flashvars.secondClick300_y",
														"tooltip_copy"=> "secondClick300_y",
														"default_value"=>"216",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick336_x",
														"field_id"=> "flashvars.secondClick336_x",
														"tooltip_copy"=> "secondClick336_x",
														"default_value"=>"6",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick336_y",
														"field_id"=> "flashvars.secondClick336_y",
														"tooltip_copy"=> "secondClick336_y",
														"default_value"=>"246",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick728_x",
														"field_id"=> "flashvars.secondClick728_x",
														"tooltip_copy"=> "secondClick728_x",
														"default_value"=>"6",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick728_y",
														"field_id"=> "flashvars.secondClick728_y",
														"tooltip_copy"=> "secondClick728_y",
														"default_value"=>"55",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick160_x",
														"field_id"=> "flashvars.secondClick160_x",
														"tooltip_copy"=> "secondClick160_x",
														"default_value"=>"30",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick160_y",
														"field_id"=> "flashvars.secondClick160_y",
														"tooltip_copy"=> "secondClick160_y",
														"default_value"=>"510",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClickAddress",
														"field_id"=> "flashvars.secondClickAddress",
														"tooltip_copy"=> "secondClickAddress",
														"default_value"=>"http://ad.vantagelocal.com/buttons/facebook_dark.png",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClickAddressSSL",
														"field_id"=> "flashvars.secondClickAddressSSL",
														"tooltip_copy"=> "secondClickAddressSSL",
														"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/facebook_dark.png",
														"is_hidden"=>""
													)
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'CUSTOM',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isDebugMode",
														"field_id"=> "flashvars.isDebugMode",
														"tooltip_copy"=> "isDebugMode",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "forceHTML5",
														"field_id"=> "flashvars.forceHTML5",
														"tooltip_copy"=> "forceHTML5",
														"default_value"=>false,
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isClickToCall",
														"field_id"=> "flashvars.isClickToCall",
														"tooltip_copy"=> "isClickToCall",
														"default_value"=>false,
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "clickToCallNumber",
														"field_id"=> "flashvars.clickToCallNumber",
														"tooltip_copy"=> "Phone Number without dashes (+ ok)",
														"default_value"=>"",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "countdownSpinnerURL",
														"field_id"=> "flashvars.countdownSpinnerURL",
														"tooltip_copy"=> "countdownSpinnerURL",
														"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/countdown.png",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "countdownSpinnerGIF",
														"field_id"=> "flashvars.countdownSpinnerGIF",
														"tooltip_copy"=> "countdownSpinnerGIF",
														"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/preloader-red.gif",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isKillASMBLclickTag",
														"field_id"=> "flashvars.isKillASMBLclickTag",
														"tooltip_copy"=> "isKillASMBLclickTag",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isKillGrandpaClickTag",
														"field_id"=> "flashvars.isKillGrandpaClickTag",
														"tooltip_copy"=> "isKillGrandpaClickTag",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "animationClip",
														"field_id"=> "flashvars.animationClip",
														"tooltip_copy"=> "animationClip",
														"default_value"=>"holder",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "parentClickTagPath",
														"field_id"=> "flashvars.parentClickTagPath",
														"tooltip_copy"=> "parentClickTagPath",
														"default_value"=>"button_holder",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "hoverFolder",
														"field_id"=> "flashvars.hoverFolder",
														"tooltip_copy"=> "hoverFolder",
														"default_value"=>"http://ad.vantagelocal.com/hovers/",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "hoverFolderSSL",
														"field_id"=> "flashvars.hoverFolderSSL",
														"tooltip_copy"=> "hoverFolderSSL",
														"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/hovers/",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "videoChildAddress",
														"field_id"=> "flashvars.videoChildAddress",
														"tooltip_copy"=> "videoChildAddress",
														"default_value"=>"http://ad.vantagelocal.com/children/video_child_218.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "videoChildAddressSSL",
														"field_id"=> "flashvars.videoChildAddressSSL",
														"tooltip_copy"=> "videoChildAddressSSL",
														"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/children/video_child_218.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "mapChildAddress",
														"field_id"=> "flashvars.mapChildAddress",
														"tooltip_copy"=> "mapChildAddress",
														"default_value"=>"http://ad.vantagelocal.com/children/map_child_101.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "mapChildAddressSSL",
														"field_id"=> "flashvars.mapChildAddressSSL",
														"tooltip_copy"=> "mapChildAddressSSL",
														"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/children/map_child_101.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailChildAddress",
														"field_id"=> "flashvars.emailChildAddress",
														"tooltip_copy"=> "emailChildAddress",
														"default_value"=>"http://ad.vantagelocal.com/children/email_child_001.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailChildAddressSSL",
														"field_id"=> "flashvars.emailChildAddressSSL",
														"tooltip_copy"=> "emailChildAddressSSL",
														"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/children/email_child_001.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "trackingPixelAddressSSL",
														"field_id"=> "flashvars.trackingPixelAddressSSL",
														"tooltip_copy"=> "trackingPixelAddressSSL",
														"default_value"=>"https://81e2563e7a7102c7a523-4c272e1e114489a00a7c5f34206ea8d1.ssl.cf1.rackcdn.com/px.gif",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "trackingPixelAddress",
														"field_id"=> "flashvars.trackingPixelAddress",
														"tooltip_copy"=> "trackingPixelAddress",
														"default_value"=>"http://3100e55ffe038d736447-4c272e1e114489a00a7c5f34206ea8d1.r73.cf1.rackcdn.com/px.gif",
														"is_hidden"=>""
													)
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'EMAIL (beta)',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isRichMediaEmail",
														"field_id"=> "flashvars.isRichMediaEmail",
														"tooltip_copy"=> "isRichMediaEmail",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButtonImageURL",
														"field_id"=> "flashvars.emailButtonImageURL",
														"tooltip_copy"=> "emailButtonImageURL",
														"default_value"=>"http://ad.vantagelocal.com/buttons/coupon_btn_1.png",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButtonImageURLSSL",
														"field_id"=> "flashvars.emailButtonImageURLSSL",
														"tooltip_copy"=> "emailButtonImageURLSSL",
														"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/coupon_btn_1.png",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"boolean",
														"field_label"=> "isAutoLoadEmail",
														"field_id"=> "flashvars.isAutoLoadEmail",
														"tooltip_copy"=> "isAutoLoadEmail",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton300_x",
														"field_id"=> "flashvars.emailButton300_x",
														"tooltip_copy"=> "emailButton300_x",
														"default_value"=>"53",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton300_y",
														"field_id"=> "flashvars.emailButton300_y",
														"tooltip_copy"=> "emailButton300_y",
														"default_value"=>"125",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton336_x",
														"field_id"=> "flashvars.emailButton336_x",
														"tooltip_copy"=> "emailButton336_x",
														"default_value"=>"246",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton336_y",
														"field_id"=> "flashvars.emailButton336_y",
														"tooltip_copy"=> "emailButton336_y",
														"default_value"=>"242",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton728_x",
														"field_id"=> "flashvars.emailButton728_x",
														"tooltip_copy"=> "emailButton728_x",
														"default_value"=>"443",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton728_y",
														"field_id"=> "flashvars.emailButton728_y",
														"tooltip_copy"=> "emailButton728_y",
														"default_value"=>"27",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton160_x",
														"field_id"=> "flashvars.emailButton160_x",
														"tooltip_copy"=> "emailButton160_x",
														"default_value"=>"60",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton160_y",
														"field_id"=> "flashvars.emailButton160_y",
														"tooltip_copy"=> "emailButton160_y",
														"default_value"=>"286",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email300_x",
														"field_id"=> "flashvars.email300_x",
														"tooltip_copy"=> "email300_x",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email300_y",
														"field_id"=> "flashvars.email300_y",
														"tooltip_copy"=> "email300_y",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email336_x",
														"field_id"=> "flashvars.email336_x",
														"tooltip_copy"=> "email336_x",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email336_y",
														"field_id"=> "flashvars.email336_y",
														"tooltip_copy"=> "email336_y",
														"default_value"=>"13",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email728_x",
														"field_id"=> "flashvars.email728_x",
														"tooltip_copy"=> "email728_x",
														"default_value"=>"607",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email728_y",
														"field_id"=> "flashvars.email728_y",
														"tooltip_copy"=> "email728_y",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email160_x",
														"field_id"=> "flashvars.email160_x",
														"tooltip_copy"=> "email160_x",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email160_y",
														"field_id"=> "flashvars.email160_y",
														"tooltip_copy"=> "email160_y",
														"default_value"=>"296",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailWidth300",
														"field_id"=> "flashvars.emailWidth300",
														"tooltip_copy"=> "emailWidth300",
														"default_value"=>"300",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailHeight300",
														"field_id"=> "flashvars.emailHeight300",
														"tooltip_copy"=> "emailHeight300",
														"default_value"=>"250",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailWidth336",
														"field_id"=> "flashvars.emailWidth336",
														"tooltip_copy"=> "emailWidth336",
														"default_value"=>"336",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailHeight336",
														"field_id"=> "flashvars.emailHeight336",
														"tooltip_copy"=> "emailHeight336",
														"default_value"=>"254",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailWidth728",
														"field_id"=> "flashvars.emailWidth728",
														"tooltip_copy"=> "emailWidth728",
														"default_value"=>"119",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailHeight728",
														"field_id"=> "flashvars.emailHeight728",
														"tooltip_copy"=> "emailHeight728",
														"default_value"=>"90",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailWidth160",
														"field_id"=> "flashvars.emailWidth160",
														"tooltip_copy"=> "emailWidth160",
														"default_value"=>"160",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailHeight160",
														"field_id"=> "flashvars.emailHeight160",
														"tooltip_copy"=> "emailHeight160",
														"default_value"=>"121",
														"is_hidden"=>""
													)
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'VERSION',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "xmlVersion",
														"field_id"=> "flashvars.xmlVersion",
														"tooltip_copy"=> "xmlVersion",
														"default_value"=>"200",
														"is_hidden"=>1
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "vantage class URL",
														"field_id"=> "flashvars.vantageURL",
														"tooltip_copy"=> "URL of the vantage class",
														"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/js/vantage/vantage.088.js",
														"is_hidden"=>""
													)
												)
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'BUILDER VERSION',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "VL Builder version",
														"field_id"=> "",
														"tooltip_copy"=> "This is the vantage local builder version",
														"default_value"=>$version,
														"is_hidden"=>1
													)
												)
							)
						);

			$json_blob = json_encode($config);
			print '<pre>'; print_r($json_blob); print '</pre>';
			echo "new config rows: ";
			$this->variables_model->insert_variables_config($version,$json_blob);
			echo "<br>new builder 2 blob rows: ";
			$this->variables_model->insert_new_blob_to_builder_version($version, $blob_version, $rich_grandpa_version);
		}else
		{	
			redirect('director'); 
		}
	}	

/*

	public function build_config_old($version){
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer','creative_uploader');
			redirect('login');
		}
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		if ($role == 'ops' or $role == 'admin')
		{

			$config = array(
							array(	
								'section_type' => 'block',
								'section_title' => 'GENERAL',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "showRichMenuAlways",
														"field_id"=> "flashvars.showRichMenuAlways",
														"tooltip_copy"=> "here is some tooltip copy for this variable",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "closeButtonPadding",
														"field_id"=> "flashvars.closeButtonPadding",
														"tooltip_copy"=> "Change to 10 if Video or Map covers top right corner (for AdChoices)",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "isHoverToClick",
														"field_id"=> "flashvars.isHoverToClick",
														"tooltip_copy"=> "isHoverToClick",
														"default_value"=>"true",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "isReplay",
														"field_id"=> "flashvars.isReplay",
														"tooltip_copy"=> "isReplay",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "isGrandpaImageAlways",
														"field_id"=> "flashvars.isGrandpaImageAlways",
														"tooltip_copy"=> "Image Only Ad",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "hoverName",
														"field_id"=> "flashvars.hoverName",
														"tooltip_copy"=> "Hover Effect - shine or pagepeel or stroke or none",
														"default_value"=>"shine",
														"is_hidden"=>""
													)
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'VIDEO',
								'ui_elements' => array(
													array(
														'section_type' => 'block',
														'section_title' => 'Video Settings',
														'ui_elements' => array(
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "videoURL",
																				"field_id"=> "flashvars.videoURL",
																				"tooltip_copy"=> "videoURL",
																				"default_value"=>"http://www.youtube.com/watch?v=K56yppSJRLw",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "isAutoLoadVideo",
																				"field_id"=> "flashvars.isAutoLoadVideo",
																				"tooltip_copy"=> "isAutoLoadVideo",
																				"default_value"=>"true",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "autoPlay",
																				"field_id"=> "flashvars.autoPlay",
																				"tooltip_copy"=> "autoPlay",
																				"default_value"=>"false",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "isPlayMuted",
																				"field_id"=> "flashvars.isPlayMuted",
																				"tooltip_copy"=> "isPlayMuted",
																				"default_value"=>"false",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "numAutoPlaySeconds",
																				"field_id"=> "flashvars.numAutoPlaySeconds",
																				"tooltip_copy"=> "numAutoPlaySeconds",
																				"default_value"=>"5",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "scaleToFillFullscreenBG",
																				"field_id"=> "flashvars.scaleToFillFullscreenBG",
																				"tooltip_copy"=> "scaleToFillFullscreenBG",
																				"default_value"=>"false",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "fullscreenImageURL",
																				"field_id"=> "flashvars.fullscreenImageURL",
																				"tooltip_copy"=> "fullscreenImageURL",
																				"default_value"=>"",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "fullscreenImageURLSSL",
																				"field_id"=> "flashvars.fullscreenImageURLSSL",
																				"tooltip_copy"=> "fullscreenImageURLSSL",
																				"default_value"=>"",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "playButtonImageURL",
																				"field_id"=> "flashvars.playButtonImageURL",
																				"tooltip_copy"=> "playButtonImageURL",
																				"default_value"=>"http://ad.vantagelocal.com/buttons/play_btn_1.png",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "playButtonImageURLSSL",
																				"field_id"=> "flashvars.playButtonImageURLSSL",
																				"tooltip_copy"=> "playButtonImageURLSSL",
																				"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/play_btn_1.png",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "mobilePlayButtonImageURL",
																				"field_id"=> "flashvars.mobilePlayButtonImageURL",
																				"tooltip_copy"=> "mobilePlayButtonImageURL",
																				"default_value"=>"http://ad.vantagelocal.com/buttons/play_btn_white_mobile.png",
																				"is_hidden"=>""
																			),
																			array(
																				"section_type"=> "field",
																				"field_type"=>"normal_text",
																				"field_label"=> "mobilePlayButtonImageURLSSL",
																				"field_id"=> "flashvars.mobilePlayButtonImageURLSSL",
																				"tooltip_copy"=> "mobilePlayButtonImageURLSSL",
																				"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/play_btn_white_mobile.png",
																				"is_hidden"=>""
																			)
																		)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Play Button Positions',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton300_x",
																				"field_id"=> "flashvars.playButton300_x",
																				'tooltip_copy' => "playButton300_x",
																				"default_value"=>"150",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton300_y",
																				"field_id"=> "flashvars.playButton300_y",
																				'tooltip_copy' => "playButton300_y",
																				"default_value"=>"125",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton320_x",
																				"field_id"=> "flashvars.playButton320_x",
																				'tooltip_copy' => "playButton320_x",
																				"default_value"=>"298",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton320_y",
																				"field_id"=> "flashvars.playButton320_y",
																				'tooltip_copy' => "playButton320_y",
																				"default_value"=>"25",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton336_x",
																				"field_id"=> "flashvars.playButton336_x",
																				'tooltip_copy' => "playButton336_x",
																				"default_value"=>"168",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton336_y",
																				"field_id"=> "flashvars.playButton336_y",
																				'tooltip_copy' => "playButton336_y",
																				"default_value"=>"140",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton728_x",
																				"field_id"=> "flashvars.playButton728_x",
																				'tooltip_copy' => "playButton728_x",
																				"default_value"=>"650",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton728_y",
																				"field_id"=> "flashvars.playButton728_y",
																				'tooltip_copy' => "playButton728_y",
																				"default_value"=>"45",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton160_x",
																				"field_id"=> "flashvars.playButton160_x",
																				'tooltip_copy' => "playButton160_x",
																				"default_value"=>"80",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playButton160_y",
																				"field_id"=> "flashvars.playButton160_y",
																				'tooltip_copy' => "playButton160_y",
																				"default_value"=>"555",
																				"is_hidden"=>""
																			)
																		)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Video Position',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video300_x",
																				"field_id"=> "flashvars.video300_x",
																				'tooltip_copy' => "video300_x",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video300_y",
																				"field_id"=> "flashvars.video300_y",
																				'tooltip_copy' => "video300_y",
																				"default_value"=>"23",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video336_x",
																				"field_id"=> "flashvars.video336_x",
																				'tooltip_copy' => "video336_x",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video336_y",
																				"field_id"=> "flashvars.video336_y",
																				'tooltip_copy' => "video336_y",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video728_x",
																				"field_id"=> "flashvars.video728_x",
																				'tooltip_copy' => "video728_x",
																				"default_value"=>"570",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video728_y",
																				"field_id"=> "flashvars.video728_y",
																				'tooltip_copy' => "video728_y",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoWidth160",
																				"field_id"=> "flashvars.video160_x",
																				'tooltip_copy' => "video160_x",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "video160_y",
																				"field_id"=> "flashvars.video160_y",
																				'tooltip_copy' => "video160_y",
																				"default_value"=>"514",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Video Size',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoWidth300",
																				"field_id"=> "flashvars.videoWidth300",
																				'tooltip_copy' => "videoWidth300",
																				"default_value"=>"298",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoHeight300",
																				"field_id"=> "flashvars.videoHeight300",
																				'tooltip_copy' => "videoHeight300",
																				"default_value"=>"168",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoWidth336",
																				"field_id"=> "flashvars.videoWidth336",
																				'tooltip_copy' => "videoWidth336",
																				"default_value"=>"334",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoHeight336",
																				"field_id"=> "flashvars.videoHeight336",
																				'tooltip_copy' => "videoHeight336",
																				"default_value"=>"188",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoWidth728",
																				"field_id"=> "flashvars.videoWidth728",
																				'tooltip_copy' => "videoWidth728",
																				"default_value"=>"158",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoHeight728",
																				"field_id"=> "flashvars.videoHeight728",
																				'tooltip_copy' => "videoHeight728",
																				"default_value"=>"88",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoWidth160",
																				"field_id"=> "flashvars.videoWidth160",
																				'tooltip_copy' => "videoWidth160",
																				"default_value"=>"158",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "videoHeight160",
																				"field_id"=> "flashvars.videoHeight160",
																				'tooltip_copy' => "videoHeight160",
																				"default_value"=>"88",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Fullscreen',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "fullscreenBackgroundColor",
																				"field_id"=> "flashvars.fullscreenBackgroundColor",
																				'tooltip_copy' => "fullscreenBackgroundColor",
																				"default_value"=>"252525",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "forceFullscreenVideoWidth",
																				"field_id"=> "flashvars.forceFullscreenVideoWidth",
																				'tooltip_copy' => "forceFullscreenVideoWidth",
																				"default_value"=>"800",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "forceFullscreenVideoHeight",
																				"field_id"=> "flashvars.forceFullscreenVideoHeight",
																				'tooltip_copy' => "forceFullscreenVideoHeight",
																				"default_value"=>"450",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Player Colors',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timelineDownloadColor",
																				"field_id"=> "flashvars.timelineDownloadColor",
																				'tooltip_copy' => "timelineDownloadColor",
																				"default_value"=>"168DFF",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timelineBackgroundColor",
																				"field_id"=> "flashvars.timelineBackgroundColor",
																				'tooltip_copy' => "timelineBackgroundColor",
																				"default_value"=>"004993",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsColor",
																				"field_id"=> "flashvars.controlsColor",
																				'tooltip_copy' => "controlsColor",
																				"default_value"=>"888888",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsHighlightColor",
																				"field_id"=> "flashvars.controlsHighlightColor",
																				'tooltip_copy' => "controlsHighlightColor",
																				"default_value"=>"BBBBBB",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playerBackgroundColor",
																				"field_id"=> "flashvars.playerBackgroundColor",
																				'tooltip_copy' => "playerBackgroundColor",
																				"default_value"=>"000000",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Video Controls',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "useTimeline",
																				"field_id"=> "flashvars.useTimeline",
																				'tooltip_copy' => "useTimeline",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "useTimeDisplay",
																				"field_id"=> "flashvars.useTimeDisplay",
																				'tooltip_copy' => "useTimeDisplay",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "useSpeaker",
																				"field_id"=> "flashvars.useSpeaker",
																				'tooltip_copy' => "useSpeaker",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "useVolLevel",
																				"field_id"=> "flashvars.useVolLevel",
																				'tooltip_copy' => "useVolLevel",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "useFullScreen",
																				"field_id"=> "flashvars.useFullScreen",
																				'tooltip_copy' => "useFullScreen",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "useInfoButton",
																				"field_id"=> "flashvars.useInfoButton",
																				'tooltip_copy' => "useInfoButton",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "useQualityMenu",
																				"field_id"=> "flashvars.useQualityMenu",
																				'tooltip_copy' => "useQualityMenu",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "useTimeTooltip",
																				"field_id"=> "flashvars.useTimeTooltip",
																				'tooltip_copy' => "useTimeTooltip",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "usePlayPause",
																				"field_id"=> "flashvars.usePlayPause",
																				'tooltip_copy' => "usePlayPause",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Custom Video',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "previewImg",
																				"field_id"=> "flashvars.previewImg",
																				'tooltip_copy' => "previewImg",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "initialVol",
																				"field_id"=> "flashvars.initialVol",
																				'tooltip_copy' => "initialVol",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playPauseOverVideo",
																				"field_id"=> "flashvars.playPauseOverVideo",
																				'tooltip_copy' => "playPauseOverVideo",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "bufferTime",
																				"field_id"=> "flashvars.bufferTime",
																				'tooltip_copy' => "bufferTime",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "belowVideo",
																				"field_id"=> "flashvars.belowVideo",
																				'tooltip_copy' => "belowVideo",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "use3Dstyle",
																				"field_id"=> "flashvars.use3Dstyle",
																				'tooltip_copy' => "use3Dstyle",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsAlpha",
																				"field_id"=> "flashvars.controlsAlpha",
																				'tooltip_copy' => "controlsAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "playerBackgroundAlpha",
																				"field_id"=> "flashvars.playerBackgroundAlpha",
																				'tooltip_copy' => "playerBackgroundAlpha",
																				"default_value"=>"80",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "useBackgroundGradient",
																				"field_id"=> "flashvars.useBackgroundGradient",
																				'tooltip_copy' => "useBackgroundGradient",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "normalTextColor",
																				"field_id"=> "flashvars.normalTextColor",
																				'tooltip_copy' => "normalTextColor",
																				"default_value"=>"777777",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "highlightTextColor",
																				"field_id"=> "flashvars.highlightTextColor",
																				'tooltip_copy' => "highlightTextColor",
																				"default_value"=>"999999",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "popupBackgroundColor",
																				"field_id"=> "flashvars.popupBackgroundColor",
																				'tooltip_copy' => "popupBackgroundColor",
																				"default_value"=>"777777",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "popupBackgroundColor",
																				"field_id"=> "flashvars.popupBackgroundColor",
																				'tooltip_copy' => "popupBackgroundColor",
																				"default_value"=>"000000",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "popupBackgroundAlpha",
																				"field_id"=> "flashvars.popupBackgroundAlpha",
																				'tooltip_copy' => "popupBackgroundAlpha",
																				"default_value"=>"80",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsSeparation",
																				"field_id"=> "flashvars.controlsSeparation",
																				'tooltip_copy' => "controlsSeparation",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsBarHeight",
																				"field_id"=> "flashvars.controlsBarHeight",
																				'tooltip_copy' => "controlsBarHeight",
																				"default_value"=>"32",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "controlsScale",
																				"field_id"=> "flashvars.controlsScale",
																				'tooltip_copy' => "controlsScale",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "textControlsScale",
																				"field_id"=> "flashvars.textControlsScale",
																				'tooltip_copy' => "textControlsScale",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timeTooltipScale",
																				"field_id"=> "flashvars.timeTooltipScale",
																				'tooltip_copy' => "timeTooltipScale",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "useControlsGradient",
																				"field_id"=> "flashvars.useControlsGradient",
																				'tooltip_copy' => "useControlsGradient",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "useLoadingRing",
																				"field_id"=> "flashvars.useLoadingRing",
																				'tooltip_copy' => "useLoadingRing",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timelineDownloadAlpha",
																				"field_id"=> "flashvars.timelineDownloadAlpha",
																				'tooltip_copy' => "timelineDownloadAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timelineBackgroundAlpha",
																				"field_id"=> "flashvars.timelineBackgroundAlpha",
																				'tooltip_copy' => "timelineBackgroundAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "volBarColor",
																				"field_id"=> "flashvars.volBarColor",
																				'tooltip_copy' => "volBarColor",
																				"default_value"=>"168DFF",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "volBarAlpha",
																				"field_id"=> "flashvars.volBarAlpha",
																				'tooltip_copy' => "volBarAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "volBackgroundColor",
																				"field_id"=> "flashvars.volBackgroundColor",
																				'tooltip_copy' => "volBackgroundColor",
																				"default_value"=>"004993",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "volBackgroundAlpha",
																				"field_id"=> "flashvars.volBackgroundAlpha",
																				'tooltip_copy' => "volBackgroundAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timeTooltipColor",
																				"field_id"=> "flashvars.timeTooltipColor",
																				'tooltip_copy' => "timeTooltipColor",
																				"default_value"=>"666666",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "timeTooltipTextColor",
																				"field_id"=> "flashvars.timeTooltipTextColor",
																				'tooltip_copy' => "timeTooltipTextColor",
																				"default_value"=>"DDDDDD",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "tooltipShadow",
																				"field_id"=> "flashvars.tooltipShadow",
																				'tooltip_copy' => "tooltipShadow",
																				"default_value"=>"true",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoPath",
																				"field_id"=> "flashvars.logoPath",
																				'tooltip_copy' => "logoPath",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoURL",
																				"field_id"=> "flashvars.logoURL",
																				'tooltip_copy' => "logoURL",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoWindow",
																				"field_id"=> "flashvars.logoWindow",
																				'tooltip_copy' => "logoWindow",
																				"default_value"=>"_blank",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoPosition",
																				"field_id"=> "flashvars.logoPosition",
																				'tooltip_copy' => "logoPosition",
																				"default_value"=>"top-right",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoXmargin",
																				"field_id"=> "flashvars.logoXmargin",
																				'tooltip_copy' => "logoXmargin",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoYmargin",
																				"field_id"=> "flashvars.logoYmargin",
																				'tooltip_copy' => "logoYmargin",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoAlpha",
																				"field_id"=> "flashvars.logoAlpha",
																				'tooltip_copy' => "logoAlpha",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "logoDisplayTime",
																				"field_id"=> "flashvars.logoDisplayTime",
																				'tooltip_copy' => "logoDisplayTime",
																				"default_value"=>"0",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descWidth",
																				"field_id"=> "flashvars.descWidth",
																				'tooltip_copy' => "descWidth",
																				"default_value"=>"220",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descPosition",
																				"field_id"=> "flashvars.descPosition",
																				'tooltip_copy' => "descPosition",
																				"default_value"=>"top-left",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descTextColor",
																				"field_id"=> "flashvars.descTextColor",
																				'tooltip_copy' => "descTextColor",
																				"default_value"=>"100",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descXmargin",
																				"field_id"=> "flashvars.descXmargin",
																				'tooltip_copy' => "descXmargin",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descYmargin",
																				"field_id"=> "flashvars.descYmargin",
																				'tooltip_copy' => "descYmargin",
																				"default_value"=>"12",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descTextColor",
																				"field_id"=> "flashvars.descTextColor",
																				'tooltip_copy' => "descTextColor",
																				"default_value"=>"EEEEEE",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descTextSize",
																				"field_id"=> "flashvars.descTextSize",
																				'tooltip_copy' => "descTextSize",
																				"default_value"=>"14",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descBackgroundColor",
																				"field_id"=> "flashvars.descBackgroundColor",
																				'tooltip_copy' => "descBackgroundColor",
																				"default_value"=>"000000",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "descBackgroundAlpha",
																				"field_id"=> "flashvars.descBackgroundAlpha",
																				'tooltip_copy' => "descBackgroundAlpha",
																				"default_value"=>"50",
																				"is_hidden"=>""
																				)
																			)
													)
												)
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'MAP',
								'ui_elements' => array(
													array(
														'section_type' => 'block',
														'section_title' => 'Load Settings',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "isRichMediaMap",
																				"field_id"=> "flashvars.isRichMediaMap",
																				'tooltip_copy' => "Turns map on if true",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButtonImageURL",
																				"field_id"=> "flashvars.mapButtonImageURL",
																				'tooltip_copy' => "mapButtonImageURL",
																				"default_value"=>"http://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.r9.cf1.rackcdn.com/buttons/map_pin_6.png",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButtonImageURLSSL",
																				"field_id"=> "flashvars.mapButtonImageURLSSL",
																				'tooltip_copy' => "mapButtonImageURLSSL",
																				"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/map_pin_6.png",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "isAutoLoadMap",
																				"field_id"=> "flashvars.isAutoLoadMap",
																				'tooltip_copy' => "Load the map automatically",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "markerTitles",
																				"field_id"=> "flashvars.markerTitles",
																				'tooltip_copy' => "Pin Titles (Google Map Only), separated by semicolons",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapPinLatitudes",
																				"field_id"=> "flashvars.mapPinLatitudes",
																				'tooltip_copy' => "Must be in order separated by semicolons",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapPinLongitudes",
																				"field_id"=> "flashvars.mapPinLongitudes",
																				'tooltip_copy' => "Must be in order separated by semicolons",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapPinDescriptions",
																				"field_id"=> "flashvars.mapPinDescriptions",
																				'tooltip_copy' => "Address and Info when click the pin, separated by semicolons",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapDefaultZoomLevel",
																				"field_id"=> "flashvars.mapDefaultZoomLevel",
																				'tooltip_copy' => "Lower number means farther into outerspace",
																				"default_value"=>"6",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Load Map Button Position',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton300_x",
																				"field_id"=> "flashvars.mapButton300_x",
																				'tooltip_copy' => "mapButton300_x",
																				"default_value"=>"260",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton300_y",
																				"field_id"=> "flashvars.mapButton300_y",
																				'tooltip_copy' => "mapButton300_y",
																				"default_value"=>"197",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton336_x",
																				"field_id"=> "flashvars.mapButton336_x",
																				'tooltip_copy' => "mapButton336_x",
																				"default_value"=>"304",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton336_y",
																				"field_id"=> "flashvars.mapButton336_y",
																				'tooltip_copy' => "mapButton336_y",
																				"default_value"=>"212",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton728_x",
																				"field_id"=> "flashvars.mapButton728_x",
																				'tooltip_copy' => "mapButton728_x",
																				"default_value"=>"560",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton728_y",
																				"field_id"=> "flashvars.mapButton728_y",
																				'tooltip_copy' => "mapButton728_y",
																				"default_value"=>"28",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton160_x",
																				"field_id"=> "flashvars.mapButton160_x",
																				'tooltip_copy' => "mapButton160_x",
																				"default_value"=>"80",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapButton160_y",
																				"field_id"=> "flashvars.mapButton160_y",
																				'tooltip_copy' => "mapButton160_y",
																				"default_value"=>"483",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Map Position',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map300_x",
																				"field_id"=> "flashvars.map300_x",
																				'tooltip_copy' => "map300_x",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map300_y",
																				"field_id"=> "flashvars.map300_y",
																				'tooltip_copy' => "map300_y",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map336_x",
																				"field_id"=> "flashvars.map336_x",
																				'tooltip_copy' => "map336_x",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map336_y",
																				"field_id"=> "flashvars.map336_y",
																				'tooltip_copy' => "map336_y",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map728_x",
																				"field_id"=> "flashvars.map728_x",
																				'tooltip_copy' => "map728_x",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map728_y",
																				"field_id"=> "flashvars.map728_y",
																				'tooltip_copy' => "map728_y",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map160_x",
																				"field_id"=> "flashvars.map160_x",
																				'tooltip_copy' => "map160_x",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "map160_y",
																				"field_id"=> "flashvars.map160_y",
																				'tooltip_copy' => "map160_y",
																				"default_value"=>"1",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Map Size',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapWidth300",
																				"field_id"=> "flashvars.mapWidth300",
																				'tooltip_copy' => "mapWidth300",
																				"default_value"=>"298",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapHeight300",
																				"field_id"=> "flashvars.mapHeight300",
																				'tooltip_copy' => "mapHeight300",
																				"default_value"=>"248",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapWidth336",
																				"field_id"=> "flashvars.mapWidth336",
																				'tooltip_copy' => "mapWidth336",
																				"default_value"=>"334",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapHeight336",
																				"field_id"=> "flashvars.mapHeight336",
																				'tooltip_copy' => "mapHeight336",
																				"default_value"=>"278",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapWidth728",
																				"field_id"=> "flashvars.mapWidth728",
																				'tooltip_copy' => "mapWidth728",
																				"default_value"=>"726",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapHeight728",
																				"field_id"=> "flashvars.mapHeight728",
																				'tooltip_copy' => "mapHeight728",
																				"default_value"=>"88",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapWidth160",
																				"field_id"=> "flashvars.mapWidth160",
																				'tooltip_copy' => "mapWidth160",
																				"default_value"=>"158",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mapHeight160",
																				"field_id"=> "flashvars.mapHeight160",
																				'tooltip_copy' => "mapHeight160",
																				"default_value"=>"598",
																				"is_hidden"=>""
																				)
																			)
													)
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'SOCIAL',
								'ui_elements' => array(
													array(
														'section_type' => 'block',
														'section_title' => 'Settings',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "useShareButtons",
																				"field_id"=> "flashvars.useShareButtons",
																				'tooltip_copy' => "Use Social on or off",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "urlToShare",
																				"field_id"=> "flashvars.urlToShare",
																				'tooltip_copy' => "URL of the page to share - use bit.ly",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "shareTitle",
																				"field_id"=> "flashvars.shareTitle",
																				'tooltip_copy' => "Title to share on LinkedIn",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "shareNotes",
																				"field_id"=> "flashvars.shareNotes",
																				'tooltip_copy' => "This is the Summary on LinkedIn",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "twitterText",
																				"field_id"=> "flashvars.twitterText",
																				'tooltip_copy' => "Text to share on Twitter (urlToShare will be added to end)",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "shareLabels",
																				"field_id"=> "flashvars.shareLabels",
																				'tooltip_copy' => "Google Bookmarks (discontinued)",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "shareSource",
																				"field_id"=> "flashvars.shareSource",
																				'tooltip_copy' => "Source for LinkedIn - Client URL",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mailSubject",
																				"field_id"=> "flashvars.mailSubject",
																				'tooltip_copy' => "Subject of the email message",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "mailBody",
																				"field_id"=> "flashvars.mailBody",
																				'tooltip_copy' => "Body of the email message (no HTML)",
																				"default_value"=>"",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "isSocialAtStart",
																				"field_id"=> "flashvars.isSocialAtStart",
																				'tooltip_copy' => "Show the buttons at start of animation",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "socialVideoOnly",
																				"field_id"=> "flashvars.socialVideoOnly",
																				'tooltip_copy' => "Show social only in the video",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "hideSocialOnChildLoad",
																				"field_id"=> "flashvars.hideSocialOnChildLoad",
																				'tooltip_copy' => "Hide social buttons when map or video loads",
																				"default_value"=>"false",
																				"is_hidden"=>""
																				)
																			)
													),
													array(
														'section_type' => 'block',
														'section_title' => 'Social Button Position',
														'ui_elements' => array(
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social300_x",
																				"field_id"=> "flashvars.social300_x",
																				'tooltip_copy' => "social300_x",
																				"default_value"=>"0",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social300_y",
																				"field_id"=> "flashvars.social300_y",
																				'tooltip_copy' => "social300_y",
																				"default_value"=>"0",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social336_x",
																				"field_id"=> "flashvars.social336_x",
																				'tooltip_copy' => "social336_x",
																				"default_value"=>"0",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social336_y",
																				"field_id"=> "flashvars.social336_y",
																				'tooltip_copy' => "social336_y",
																				"default_value"=>"0",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social728_x",
																				"field_id"=> "flashvars.social728_x",
																				'tooltip_copy' => "social728_x",
																				"default_value"=>"0",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social728_y",
																				"field_id"=> "flashvars.social728_y",
																				'tooltip_copy' => "social728_y",
																				"default_value"=>"0",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social160_x",
																				"field_id"=> "flashvars.social160_x",
																				'tooltip_copy' => "social160_x",
																				"default_value"=>"15",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "social160_y",
																				"field_id"=> "flashvars.social160_y",
																				'tooltip_copy' => "social160_y",
																				"default_value"=>"510",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "socialAddress",
																				"field_id"=> "flashvars.socialAddress",
																				'tooltip_copy' => "socialAddress",
																				"default_value"=>"http://ad.vantagelocal.com/children/social_child_136.swf",
																				"is_hidden"=>""
																				),
																			array(
																				'section_type' => 'field',
																				'field_type' => "normal_text",
																				'field_label' => "socialAddressSSL",
																				"field_id"=> "flashvars.socialAddressSSL",
																				'tooltip_copy' => "socialAddressSSL",
																				"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/children/social_child_136.swf",
																				"is_hidden"=>""
																				)
																			)
													)
													
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'SECOND CLICK BUTTON',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClickURL",
														"field_id"=> "flashvars.secondClickURL",
														"tooltip_copy"=> "secondClickURL",
														"default_value"=>"",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "isSecondClickAlways",
														"field_id"=> "flashvars.isSecondClickAlways",
														"tooltip_copy"=> "isSecondClickAlways",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick300_x",
														"field_id"=> "flashvars.secondClick300_x",
														"tooltip_copy"=> "secondClick300_x",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick300_y",
														"field_id"=> "flashvars.secondClick300_y",
														"tooltip_copy"=> "secondClick300_y",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick336_x",
														"field_id"=> "flashvars.secondClick336_x",
														"tooltip_copy"=> "secondClick336_x",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick336_y",
														"field_id"=> "flashvars.secondClick336_y",
														"tooltip_copy"=> "secondClick336_y",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick728_x",
														"field_id"=> "flashvars.secondClick728_x",
														"tooltip_copy"=> "secondClick728_x",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick728_y",
														"field_id"=> "flashvars.secondClick728_y",
														"tooltip_copy"=> "secondClick728_y",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick160_x",
														"field_id"=> "flashvars.secondClick160_x",
														"tooltip_copy"=> "secondClick160_x",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClick160_y",
														"field_id"=> "flashvars.secondClick160_y",
														"tooltip_copy"=> "secondClick160_y",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClickAddress",
														"field_id"=> "flashvars.secondClickAddress",
														"tooltip_copy"=> "secondClickAddress",
														"default_value"=>"http://ad.vantagelocal.com/buttons/coupon_btn_1.png",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "secondClickAddressSSL",
														"field_id"=> "flashvars.secondClickAddressSSL",
														"tooltip_copy"=> "secondClickAddressSSL",
														"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/coupon_btn_1.png",
														"is_hidden"=>""
													)
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'CUSTOM',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "isKillASMBLclickTag",
														"field_id"=> "flashvars.isKillASMBLclickTag",
														"tooltip_copy"=> "isKillASMBLclickTag",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "isKillGrandpaClickTag",
														"field_id"=> "flashvars.isKillGrandpaClickTag",
														"tooltip_copy"=> "isKillGrandpaClickTag",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "animationClip",
														"field_id"=> "flashvars.animationClip",
														"tooltip_copy"=> "animationClip",
														"default_value"=>"holder",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "parentClickTagPath",
														"field_id"=> "flashvars.parentClickTagPath",
														"tooltip_copy"=> "parentClickTagPath",
														"default_value"=>"button_holder",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "hoverFolder",
														"field_id"=> "flashvars.hoverFolder",
														"tooltip_copy"=> "hoverFolder",
														"default_value"=>"http://ad.vantagelocal.com/hovers/",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "hoverFolderSSL",
														"field_id"=> "flashvars.hoverFolderSSL",
														"tooltip_copy"=> "hoverFolderSSL",
														"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/hovers/",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "videoChildAddress",
														"field_id"=> "flashvars.videoChildAddress",
														"tooltip_copy"=> "videoChildAddress",
														"default_value"=>"http://ad.vantagelocal.com/children/video_child_218.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "videoChildAddressSSL",
														"field_id"=> "flashvars.videoChildAddressSSL",
														"tooltip_copy"=> "videoChildAddressSSL",
														"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/children/video_child_218.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "mapChildAddress",
														"field_id"=> "flashvars.mapChildAddress",
														"tooltip_copy"=> "mapChildAddress",
														"default_value"=>"http://ad.vantagelocal.com/children/map_child_101.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "mapChildAddressSSL",
														"field_id"=> "flashvars.mapChildAddressSSL",
														"tooltip_copy"=> "mapChildAddressSSL",
														"default_value"=>"https://ed18a41e851d21bd3d3b-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/children/map_child_101.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailChildAddress",
														"field_id"=> "flashvars.emailChildAddress",
														"tooltip_copy"=> "emailChildAddress",
														"default_value"=>"http://ad.vantagelocal.com/children/email_child_001.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailChildAddressSSL",
														"field_id"=> "flashvars.emailChildAddressSSL",
														"tooltip_copy"=> "emailChildAddressSSL",
														"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/children/email_child_001.swf",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "trackingPixelAddressSSL",
														"field_id"=> "flashvars.trackingPixelAddressSSL",
														"tooltip_copy"=> "trackingPixelAddressSSL",
														"default_value"=>"https://81e2563e7a7102c7a523-4c272e1e114489a00a7c5f34206ea8d1.ssl.cf1.rackcdn.com/px.gif",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "trackingPixelAddress",
														"field_id"=> "flashvars.trackingPixelAddress",
														"tooltip_copy"=> "trackingPixelAddress",
														"default_value"=>"http://3100e55ffe038d736447-4c272e1e114489a00a7c5f34206ea8d1.r73.cf1.rackcdn.com/px.gif",
														"is_hidden"=>""
													)
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'EMAIL (beta)',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "isRichMediaEmail",
														"field_id"=> "flashvars.isRichMediaEmail",
														"tooltip_copy"=> "isRichMediaEmail",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButtonImageURL",
														"field_id"=> "flashvars.emailButtonImageURL",
														"tooltip_copy"=> "emailButtonImageURL",
														"default_value"=>"http://ad.vantagelocal.com/buttons/coupon_btn_1.png",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButtonImageURLSSL",
														"field_id"=> "flashvars.emailButtonImageURLSSL",
														"tooltip_copy"=> "emailButtonImageURLSSL",
														"default_value"=>"https://7629e0040b7aa73eaefb-2105a7941d639194e8ba5253efec10c9.ssl.cf1.rackcdn.com/buttons/coupon_btn_1.png",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "isAutoLoadEmail",
														"field_id"=> "flashvars.isAutoLoadEmail",
														"tooltip_copy"=> "isAutoLoadEmail",
														"default_value"=>"false",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton300_x",
														"field_id"=> "flashvars.emailButton300_x",
														"tooltip_copy"=> "emailButton300_x",
														"default_value"=>"53",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton300_y",
														"field_id"=> "flashvars.emailButton300_y",
														"tooltip_copy"=> "emailButton300_y",
														"default_value"=>"125",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton336_x",
														"field_id"=> "flashvars.emailButton336_x",
														"tooltip_copy"=> "emailButton336_x",
														"default_value"=>"246",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton336_y",
														"field_id"=> "flashvars.emailButton336_y",
														"tooltip_copy"=> "emailButton336_y",
														"default_value"=>"242",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton728_x",
														"field_id"=> "flashvars.emailButton728_x",
														"tooltip_copy"=> "emailButton728_x",
														"default_value"=>"443",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton728_y",
														"field_id"=> "flashvars.emailButton728_y",
														"tooltip_copy"=> "emailButton728_y",
														"default_value"=>"27",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton160_x",
														"field_id"=> "flashvars.emailButton160_x",
														"tooltip_copy"=> "emailButton160_x",
														"default_value"=>"60",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailButton160_y",
														"field_id"=> "flashvars.emailButton160_y",
														"tooltip_copy"=> "emailButton160_y",
														"default_value"=>"286",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email300_x",
														"field_id"=> "flashvars.email300_x",
														"tooltip_copy"=> "email300_x",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email300_y",
														"field_id"=> "flashvars.email300_y",
														"tooltip_copy"=> "email300_y",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email336_x",
														"field_id"=> "flashvars.email336_x",
														"tooltip_copy"=> "email336_x",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email336_y",
														"field_id"=> "flashvars.email336_y",
														"tooltip_copy"=> "email336_y",
														"default_value"=>"13",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email728_x",
														"field_id"=> "flashvars.email728_x",
														"tooltip_copy"=> "email728_x",
														"default_value"=>"607",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email728_y",
														"field_id"=> "flashvars.email728_y",
														"tooltip_copy"=> "email728_y",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email160_x",
														"field_id"=> "flashvars.email160_x",
														"tooltip_copy"=> "email160_x",
														"default_value"=>"0",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "email160_y",
														"field_id"=> "flashvars.email160_y",
														"tooltip_copy"=> "email160_y",
														"default_value"=>"296",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailWidth300",
														"field_id"=> "flashvars.emailWidth300",
														"tooltip_copy"=> "emailWidth300",
														"default_value"=>"300",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailHeight300",
														"field_id"=> "flashvars.emailHeight300",
														"tooltip_copy"=> "emailHeight300",
														"default_value"=>"250",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailWidth336",
														"field_id"=> "flashvars.emailWidth336",
														"tooltip_copy"=> "emailWidth336",
														"default_value"=>"336",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailHeight336",
														"field_id"=> "flashvars.emailHeight336",
														"tooltip_copy"=> "emailHeight336",
														"default_value"=>"254",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailWidth728",
														"field_id"=> "flashvars.emailWidth728",
														"tooltip_copy"=> "emailWidth728",
														"default_value"=>"119",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailHeight728",
														"field_id"=> "flashvars.emailHeight728",
														"tooltip_copy"=> "emailHeight728",
														"default_value"=>"90",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailWidth160",
														"field_id"=> "flashvars.emailWidth160",
														"tooltip_copy"=> "emailWidth160",
														"default_value"=>"160",
														"is_hidden"=>""
													),
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "emailHeight160",
														"field_id"=> "flashvars.emailHeight160",
														"tooltip_copy"=> "emailHeight160",
														"default_value"=>"121",
														"is_hidden"=>""
													)
												),
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'VERSION',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "xmlVersion",
														"field_id"=> "flashvars.xmlVersion",
														"tooltip_copy"=> "xmlVersion",
														"default_value"=>"200",
														"is_hidden"=>1
													)
												)
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'BUILDER VERSION',
								'ui_elements' => array(
													array(
														"section_type"=> "field",
														"field_type"=>"normal_text",
														"field_label"=> "VL Builder version",
														"field_id"=> "",
														"tooltip_copy"=> "This is the vantage local builder version",
														"default_value"=>$version,
														"is_hidden"=>1
													)
												)
							),
							array(	
								'section_type' => 'block',
								'section_title' => 'FILES',
								'ui_elements' => array(
													array(
														'section_type' => 'field',
														'field_type' => "file_picker",
														'field_label' => "Special File",
														"field_id"=> "files.special_file",
														'tooltip_copy' => "drag drop asset",
														"is_hidden"=>""
													),
													array(
														'section_type' => 'field',
														'field_type' => "file_picker",
														'field_label' => "A different File",
														"field_id"=> "files.different_file",
														'tooltip_copy' => "drag drop asset",
														"is_hidden"=>""
													)
												)
							)
						);

			$json_blob = json_encode($config);
			print '<pre>'; print_r($json_blob); print '</pre>';
			$this->variables_model->insert_variables_config($version,$json_blob);
		}else
		{	
			redirect('director'); 
		}
	}
		*/
}
