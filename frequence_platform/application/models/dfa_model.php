<?php

class Dfa_model extends CI_Model {

	private $max_tries = 3;
	
	public function __construct(){
                
	}
	
	private function fix_soap_client_options_for_dfa($options)
	{
		$local_options = $options;
		$local_options['stream_context'] = stream_context_create(
			array(
				'http' => array(
					'protocol_version' => 1.0,
					'header' => 'Connection: Close'
				)
			)
		);
		return $local_options;
	}
        
	//Unused function. Not being maintained.
        public function get_placement_details($placementWsdl,$options,$headers,$placement_id){
            /*
	    $placementService = new SoapClient($placementWsdl, $this->fix_soap_client_options_for_dfa($options));
                $placementService->__setSoapHeaders($headers);
                
		$result['is_success'] = FALSE;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;
		
                try {
                    // Fetch placement types.
                    $result['dfa_result'] = $placementService->getPlacement($placement_id);
		    $result['is_success'] = TRUE;
                } catch (Exception $e) {
                    $result['err_msg'] = $e->getMessage();
                }
                return $result;
            */
        }
        
        
        
        public function get_dfa_tags($placementWsdl,$options,$headers,$campaign_id,$placement_id){
            
            
//            [id] => 1
//            [name] => Standard
//            [id] => 2
//            [name] => Iframe/JavaScript
//            [id] => 4
//            [name] => Internal Redirect
//            [id] => 5
//            [name] => JavaScript/Standard
//            [id] => 9
//            [name] => Click Tracker
//            If you change this value you must also change the get_tags function in the publisher controller where it returns a specific tag value.
            $tag_options = array(5);
            
            
            $placementService = new SoapClient($placementWsdl, $this->fix_soap_client_options_for_dfa($options));
            $placementService->__setSoapHeaders($headers);
            
            $placementTagCriterias = array(array('id'=> $placement_id,
                                            'tagOptionIds'=> $tag_options));

	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;
	    $result['error_counter'] = 0;

	    while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
	    {
		try {
		    $result['dfa_result'] = $placementService->getPlacementTagData($campaign_id,$placementTagCriterias);
		    $result['is_success'] = TRUE;
		} catch (Exception $e) {
		    $result['err_msg'] = $e->getMessage();
		    $result['error_counter'] += 1;
		}
	    }
//            echo 'Request : <br/><xmp>', 
//                ($placementService->__getLastRequest()), 
//                '</xmp><br/>';
            return $result;
        }
        
        public function get_tag_options($placementWsdl, $options,$headers){
                $placementService = new SoapClient($placementWsdl, $this->fix_soap_client_options_for_dfa($options));
                $placementService->__setSoapHeaders($headers);
               

		$result['is_success'] = FALSE;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;
		$result['error_counter'] = 0;

		while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
		{
		    try {
			// Fetch placement types.
			$result['dfa_result'] = $placementService->getRegularPlacementTagOptions();
			$result['is_success'] = TRUE;
		    } catch (Exception $e) {
			$result['err_msg'] =  $e->getMessage();
			$result['error_counter'] += 1;
		    }
		}
                return $result;
        }
        
        public function associate_creatives_to_placements($creativeWsdl, $options,$headers,$creativeId,$placementId){
            $creativeService = new SoapClient($creativeWsdl, $this->fix_soap_client_options_for_dfa($options));           
            $creativeService->__setSoapHeaders($headers);

            
            // Provide which creatives to assign to which placements.
            $creativeIds = array((float)strval($creativeId));
            $placementIds = array((float)strval($placementId));

            // Create creative placement assignment array.
            $creativePlacementAssignments = array();

            for($i = 0; $i < count($creativeIds); $i++) {
                $creativePlacementAssignment = array(
                    'creativeId' => $creativeIds[$i],
                    'placementId' => $placementIds[0],
                    'placementIds' => $placementIds);

                $creativePlacementAssignments[] = $creativePlacementAssignment;
            }



//            // Create creative placement assignment array.
//            $creativePlacementAssignment = array('adName' => $ad_name,
//                                                 'creativeId' => (float)$creativeId,
//                                                 'placementId' => (float)$placementId,
//                                                 'placementIds' => array($placementId));
//
//            $creativePlacementAssignments = array($creativePlacementAssignment);
	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;
	    $result['error_counter'] = 0;
	    
	    while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
	    {
		try {
		    // Assign creatives to placements.
		    $result['dfa_result'] = $creativeService->assignCreativesToPlacements($creativePlacementAssignments);
		    if(count($result['dfa_result']) > 0)
		    {
			$result['is_success'] = TRUE;
		    }
		    else
		    {
			$result['err_msg'] = "9211 - Blank Return from DFA";
			$result['error_counter'] += 1;
		    }
		} catch (Exception $e) {
		    $result['err_msg'] = $e->getMessage();
		    $result['error_counter'] += 1;

		}
	    }
            return $result;
            // Display new ads that resulted from the assignment.
//            foreach($results as $ad) {
//                print 'Ad with name "' . $ad->adName . '" and ID "'. $ad->adId . '" was created.<br> error?: '.$ad->errorMessage.'<br>';
//            }
        }
        
        public function build_asset_filename_array($assets){
            $i = 0;
            foreach($assets as $value){
                $return_array[$i] = array('assetFilename'=>$value);
                $i++;
            }
            return $return_array;
        }
        
        public function create_dfa_creative($creativeWsdl, $options,$headers,$namespace,$creative_name,$advertiserId,$campaignId,$assets,$sizeId,$creative_type_id,$html,$creativeID = 0){
            if(!empty($assets)){
              $creative_assets_array = $this->build_asset_filename_array($assets);
            }else{
              $creative_assets_array = array();
            }
            $version = 1;
            $creativeService = new SoapClient($creativeWsdl, $this->fix_soap_client_options_for_dfa($options));
            $creativeService->__setSoapHeaders($headers);

            $creative = array(
                'active' => TRUE,
                'advertiserId' => $advertiserId,
                'archived' => FALSE,
                'id' => $creativeID,
                'name' => $creative_name,
                'sizeId' => $sizeId,
                'typeId' => $creative_type_id, // NOT Hard-coded to type 'Image Creative'.
                'version' => $version,
                'creativeAssets'=> $creative_assets_array,//array(array('assetFilename' => $assetFilename[0]),array('assetFilename' => $assetFilename[1]),array('assetFilename' => $assetFilename[2])),
                'HTMLCode'=> $html);

            // Creatives implement an abstract type, CreativeBase. Because of this, an
            // xsi:type is required in the SOAP message to specify which implementation is
            // being sent. This SoapVar wrapper will say that this is an ImageCreative. OR HTMLCreative
            $creative = new SoapVar($creative, SOAP_ENC_OBJECT, 'HTMLCreative',$namespace);

	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;
	    $result['error_counter'] = 0;
	    
	    while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
	    {
		try
		{
		    // Save the creative.
		    $result['dfa_result'] = $creativeService->saveCreative($creative, $campaignId);
		    $result['is_success'] = TRUE;
		}
		catch (Exception $e)
		{
		    $result['error_counter'] += 1;
		    $result['err_msg'] = $e->getMessage();
		}
	    }
            return $result;
        }
        
        
        public function get_dfa_html($vl_creative_type,$dfa_creative_object,$advertiser_id,$dfa_size_id,$creative_width,$creative_height,$size_string,$vl_campaign_id){
            //echo $vl_campaign_id.'<br>';
            switch($vl_creative_type){
            case 'vl_hd_w_fullscreen_image': 
                              
                
                $data['creative_width'] = $creative_width;
                $data['creative_height'] = $creative_height;
                
                $data['backup_image']= '%h/'.$advertiser_id.'/'.$dfa_creative_object[$dfa_size_id]['dfa_assets']['backup_'.$size_string.'.jpg'];
                $data['open_backup_image']= '%h/'.$advertiser_id.'/'.$dfa_creative_object[$dfa_size_id]['dfa_assets']['backup_'.$size_string.'.jpg'];
                $data['ssl_backup_image']= '%h/'.$advertiser_id.'/'.$dfa_creative_object[$dfa_size_id]['dfa_assets']['backup_'.$size_string.'.jpg']; // FIXME: HTTPS please -CL
                
                $data['open_swf_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets'][$size_string.'.swf']['open_uri'];
                $data['ssl_swf_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets'][$size_string.'.swf']['ssl_uri'];
                $data['open_fullscreen_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['fullscreen.jpg']['open_uri'];
                $data['ssl_fullscreen_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['fullscreen.jpg']['ssl_uri'];
                $data['open_xml_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['variables.xml']['open_uri'];
                $data['ssl_xml_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['variables.xml']['ssl_uri'];
                $data['open_gpa_image_file'] = null;
                $data['ssl_gpa_image_file'] = null;

                if(constant('ENVIRONMENT') !== 'production')
                {
                  $this->cup_model->get_dev_tracking_pixel_addresses($data);
                }

                $data['unique_id']= 'VL_'.$advertiser_id.'_'.rand(); 
                $data['is_hd']= true;
                $data['is_gpa'] = false;

                $data['vl_campaign_id'] = $vl_campaign_id;
                
                return $this->load->view('dfa/vl_hd_view',$data,true);
                break;
            
            case 'vl_sd_wo_fullscreen_image': 
                              
                
                $data['creative_width'] = $creative_width;
                $data['creative_height'] = $creative_height;
                
                $data['backup_image']= '%h/'.$advertiser_id.'/'.$dfa_creative_object[$dfa_size_id]['dfa_assets']['backup_'.$size_string.'.jpg'];;
                
                $data['open_swf_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets'][$size_string.'.swf']['open_uri'];
                $data['ssl_swf_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets'][$size_string.'.swf']['ssl_uri'];
                $data['open_fullscreen_file'] = null;
                $data['ssl_fullscreen_file'] = null;
                $data['open_xml_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['variables.xml']['open_uri'];
                $data['ssl_xml_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['variables.xml']['ssl_uri'];
                $data['open_gpa_image_file'] = null;
                $data['ssl_gpa_image_file'] = null;
                
                $data['unique_id']= 'VL_'.$advertiser_id.'_'.rand();
                $data['is_hd']= false;
                $data['is_gpa'] = false;

                $data['vl_campaign_id'] = $vl_campaign_id;
                
                return $this->load->view('dfa/vl_hd_view',$data,true);
                break;
            
            
            case 'vl_hd_wo_fullscreen_image': 
                              
                
                $data['creative_width'] = $creative_width;
                $data['creative_height'] = $creative_height;
                
                $data['backup_image']= '%h/'.$advertiser_id.'/'.$dfa_creative_object[$dfa_size_id]['dfa_assets']['backup_'.$size_string.'.jpg'];;
                
                $data['open_swf_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets'][$size_string.'.swf']['open_uri'];
                $data['ssl_swf_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets'][$size_string.'.swf']['ssl_uri'];
                $data['open_fullscreen_file'] = null;
                $data['ssl_fullscreen_file'] = null;
                $data['open_xml_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['variables.xml']['open_uri'];
                $data['ssl_xml_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['variables.xml']['ssl_uri'];
                $data['open_gpa_image_file'] = null;
                $data['ssl_gpa_image_file'] = null;
                
                $data['unique_id']= 'VL_'.$advertiser_id.'_'.rand();
                $data['is_hd']= true;
                $data['is_gpa'] = false;

                $data['vl_campaign_id'] = $vl_campaign_id;
                
                return $this->load->view('dfa/vl_hd_view',$data,true);
                break;
            
            
            
            case 'vl_sd_w_fullscreen_image': 
                              
                
                $data['creative_width'] = $creative_width;
                $data['creative_height'] = $creative_height;
                
                $data['backup_image']= '%h/'.$advertiser_id.'/'.$dfa_creative_object[$dfa_size_id]['dfa_assets']['backup_'.$size_string.'.jpg'];;
                
                $data['open_swf_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets'][$size_string.'.swf']['open_uri'];
                $data['ssl_swf_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets'][$size_string.'.swf']['ssl_uri'];
                $data['open_fullscreen_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['fullscreen.jpg']['open_uri'];
                $data['ssl_fullscreen_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['fullscreen.jpg']['ssl_uri'];
                $data['open_xml_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['variables.xml']['open_uri'];
                $data['ssl_xml_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['variables.xml']['ssl_uri'];
                $data['open_gpa_image_file'] = null;
                $data['ssl_gpa_image_file'] = null;
                
                $data['unique_id']= 'VL_'.$advertiser_id.'_'.rand();
                $data['is_hd']= false;
                $data['is_gpa'] = false;

                $data['vl_campaign_id'] = $vl_campaign_id;
                
                return $this->load->view('dfa/vl_hd_view',$data,true);
                break;
            
            
            
            
            
            case 'vl_hd_gpa_w_fullscreen_image': 
                              
                
                $data['creative_width'] = $creative_width;
                $data['creative_height'] = $creative_height;
                
                $data['backup_image']= '%h/'.$advertiser_id.'/'.$dfa_creative_object[$dfa_size_id]['dfa_assets']['backup_'.$size_string.'.jpg'];;
                
                $data['open_swf_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets'][$size_string.'.swf']['open_uri'];
                $data['ssl_swf_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets'][$size_string.'.swf']['ssl_uri'];
                $data['open_fullscreen_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['fullscreen.jpg']['open_uri'];
                $data['ssl_fullscreen_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['fullscreen.jpg']['ssl_uri'];
                $data['open_xml_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['variables.xml']['open_uri'];
                $data['ssl_xml_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['variables.xml']['ssl_uri'];
                $data['open_gpa_image_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['grandpa_'.$size_string.'.jpg']['open_uri'];
                $data['ssl_gpa_image_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['grandpa_'.$size_string.'.jpg']['ssl_uri'];
                
                $data['unique_id']= 'VL_'.$advertiser_id.'_'.rand();
                $data['is_hd']= true;
                $data['is_gpa'] = true;

                $data['vl_campaign_id'] = $vl_campaign_id;
                
                return $this->load->view('dfa/vl_hd_view',$data,true);
                break;
            
            
            
            case 'vl_hd_gpa_wo_fullscreen_image': 
                              
                
                $data['creative_width'] = $creative_width;
                $data['creative_height'] = $creative_height;
                
                $data['backup_image']= '%h/'.$advertiser_id.'/'.$dfa_creative_object[$dfa_size_id]['dfa_assets']['backup_'.$size_string.'.jpg'];;
                
                $data['open_swf_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets'][$size_string.'.swf']['open_uri'];
                $data['ssl_swf_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets'][$size_string.'.swf']['ssl_uri'];
                $data['open_fullscreen_file'] = null;
                $data['ssl_fullscreen_file'] = null;
                $data['open_xml_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['variables.xml']['open_uri'];
                $data['ssl_xml_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['variables.xml']['ssl_uri'];
                $data['open_gpa_image_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['grandpa_'.$size_string.'.jpg']['open_uri'];
                $data['ssl_gpa_image_file'] = $dfa_creative_object[$dfa_size_id]['cdn_assets']['grandpa_'.$size_string.'.jpg']['ssl_uri'];
                
                $data['unique_id']= 'VL_'.$advertiser_id.'_'.rand();
                $data['is_hd']= true;
                $data['is_gpa'] = true;

                $data['vl_campaign_id'] = $vl_campaign_id;
                
                return $this->load->view('dfa/vl_hd_view',$data,true);
                break;
            

            }
       
        }
        
        public function inspect_file_names($vl_creative_type,$full_filepath_directory){
            $path_parts = pathinfo($full_filepath_directory);

            switch($vl_creative_type){
               
               case 'vl_hd_w_fullscreen_image':
                   $is_html_creative = TRUE;
                   $dfa_creative_set['2321'] = array('dimensions'=>array('width'=>160,'height'=>600),
                                                     'dfa_assets'=>array('backup_160x600.jpg'=>''),
                                                     'cdn_assets'=>array('160x600.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                         'fullscreen.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                         'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                     'dfa_backup_asset'=>array('backup_160x600.jpg'=>''),
                                                     'is_there'=>true,
                                                     'dfa_creative_html'=>'arbitrary html',
                                                     'placement_id'=>'',
                                                     'creative_id'=>''
                                                     );
                   $dfa_creative_set['3454'] = array('dimensions'=>array('width'=>728,'height'=>90),
                                                     'dfa_assets'=>array('backup_728x90.jpg'=>''),
                                                     'cdn_assets'=>array('728x90.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                         'fullscreen.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                         'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                     'dfa_backup_asset'=>array('backup_728x90.jpg'=>''),
                                                     'is_there'=>true,
                                                     'dfa_creative_html'=>'arbitrary html',
                                                     'placement_id'=>'',
                                                     'creative_id'=>''
                                                     );
                   $dfa_creative_set['4307'] = array('dimensions'=>array('width'=>300,'height'=>250),
                                                     'dfa_assets'=>array('backup_300x250.jpg'=>''),
                                                     'cdn_assets'=>array('300x250.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                         'fullscreen.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                         'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                     'dfa_backup_asset'=>array('backup_300x250.jpg'=>''),
                                                     'is_there'=>true,
                                                     'dfa_creative_html'=>'arbitrary html',
                                                     'placement_id'=>'',
                                                     'creative_id'=>''
                                                     );
                   $dfa_creative_set['4252'] = array('dimensions'=>array('width'=>336,'height'=>280),
                                                     'dfa_assets'=>array('backup_336x280.jpg'=>''),
                                                     'cdn_assets'=>array('336x280.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                         'fullscreen.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                         'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                     'dfa_backup_asset'=>array('backup_336x280.jpg'=>''),
                                                     'is_there'=>true,
                                                     'dfa_creative_html'=>'arbitrary html',
                                                     'placement_id'=>'',
                                                     'creative_id'=>''
                                                     );
                   break;
                   
               case 'vl_sd_wo_fullscreen_image':
                    $is_html_creative = TRUE;
                    $dfa_creative_set['2321'] = array('dimensions'=>array('width'=>160,'height'=>600),
                                                        'dfa_assets'=>array('backup_160x600.jpg'=>''),
                                                        'cdn_assets'=>array('160x600.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_160x600.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    $dfa_creative_set['3454'] = array('dimensions'=>array('width'=>728,'height'=>90),
                                                        'dfa_assets'=>array('backup_728x90.jpg'=>''),
                                                        'cdn_assets'=>array('728x90.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_728x90.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    $dfa_creative_set['4307'] = array('dimensions'=>array('width'=>300,'height'=>250),
                                                        'dfa_assets'=>array('backup_300x250.jpg'=>''),
                                                        'cdn_assets'=>array('300x250.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_300x250.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    $dfa_creative_set['4252'] = array('dimensions'=>array('width'=>336,'height'=>280),
                                                        'dfa_assets'=>array('backup_336x280.jpg'=>''),
                                                        'cdn_assets'=>array('336x280.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_336x280.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    break;
                   
                case 'vl_hd_wo_fullscreen_image':
                    $is_html_creative = TRUE;
                    $dfa_creative_set['2321'] = array('dimensions'=>array('width'=>160,'height'=>600),
                                                        'dfa_assets'=>array('backup_160x600.jpg'=>''),
                                                        'cdn_assets'=>array('160x600.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_160x600.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    $dfa_creative_set['3454'] = array('dimensions'=>array('width'=>728,'height'=>90),
                                                        'dfa_assets'=>array('backup_728x90.jpg'=>''),
                                                        'cdn_assets'=>array('728x90.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_728x90.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    $dfa_creative_set['4307'] = array('dimensions'=>array('width'=>300,'height'=>250),
                                                        'dfa_assets'=>array('backup_300x250.jpg'=>''),
                                                        'cdn_assets'=>array('300x250.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_300x250.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    $dfa_creative_set['4252'] = array('dimensions'=>array('width'=>336,'height'=>280),
                                                        'dfa_assets'=>array('backup_336x280.jpg'=>''),
                                                        'cdn_assets'=>array('336x280.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_336x280.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    break;
                 
                case 'vl_sd_w_fullscreen_image':
                    $is_html_creative = TRUE;
                    $dfa_creative_set['2321'] = array('dimensions'=>array('width'=>160,'height'=>600),
                                                        'dfa_assets'=>array('backup_160x600.jpg'=>''),
                                                        'cdn_assets'=>array('160x600.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'fullscreen.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_160x600.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    $dfa_creative_set['3454'] = array('dimensions'=>array('width'=>728,'height'=>90),
                                                        'dfa_assets'=>array('backup_728x90.jpg'=>''),
                                                        'cdn_assets'=>array('728x90.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'fullscreen.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_728x60.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    $dfa_creative_set['4307'] = array('dimensions'=>array('width'=>300,'height'=>250),
                                                        'dfa_assets'=>array('backup_300x250.jpg'=>''),
                                                        'cdn_assets'=>array('300x250.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'fullscreen.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_300x250.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    $dfa_creative_set['4252'] = array('dimensions'=>array('width'=>336,'height'=>280),
                                                        'dfa_assets'=>array('backup_336x280.jpg'=>''),
                                                        'cdn_assets'=>array('336x280.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'fullscreen.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_336x280.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    break;

                
                case 'vl_hd_gpa_w_fullscreen_image':
                    $is_html_creative = TRUE;
                    $dfa_creative_set['2321'] = array('dimensions'=>array('width'=>160,'height'=>600),
                                                        'dfa_assets'=>array('backup_160x600.jpg'=>''),
                                                        'cdn_assets'=>array('160x600.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'fullscreen.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'grandpa_160x600.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_160x600.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    $dfa_creative_set['3454'] = array('dimensions'=>array('width'=>728,'height'=>90),
                                                        'dfa_assets'=>array('backup_728x90.jpg'=>''),
                                                        'cdn_assets'=>array('728x90.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'fullscreen.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'grandpa_728x90.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_728x90.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    $dfa_creative_set['4307'] = array('dimensions'=>array('width'=>300,'height'=>250),
                                                        'dfa_assets'=>array('backup_300x250.jpg'=>''),
                                                        'cdn_assets'=>array('300x250.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'fullscreen.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'grandpa_300x250.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_300x250.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    $dfa_creative_set['4252'] = array('dimensions'=>array('width'=>336,'height'=>280),
                                                        'dfa_assets'=>array('backup_336x280.jpg'=>''),
                                                        'cdn_assets'=>array('336x280.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'fullscreen.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'grandpa_336x280.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_336x280.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    break;
                  
                   
                case 'vl_hd_gpa_wo_fullscreen_image':
                    $is_html_creative = TRUE;
                    $dfa_creative_set['2321'] = array('dimensions'=>array('width'=>160,'height'=>600),
                                                        'dfa_assets'=>array('backup_160x600.jpg'=>''),
                                                        'cdn_assets'=>array('160x600.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'grandpa_160x600.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_160x600.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    $dfa_creative_set['3454'] = array('dimensions'=>array('width'=>728,'height'=>90),
                                                        'dfa_assets'=>array('backup_728x90.jpg'=>''),
                                                        'cdn_assets'=>array('728x90.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'grandpa_728x90.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_728x90.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    $dfa_creative_set['4307'] = array('dimensions'=>array('width'=>300,'height'=>250),
                                                        'dfa_assets'=>array('backup_300x250.jpg'=>''),
                                                        'cdn_assets'=>array('300x250.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'grandpa_300x250.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_300x250.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    $dfa_creative_set['4252'] = array('dimensions'=>array('width'=>336,'height'=>280),
                                                        'dfa_assets'=>array('backup_336x280.jpg'=>''),
                                                        'cdn_assets'=>array('336x280.swf'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'grandpa_336x280.jpg'=>array('open_uri'=>'','ssl_uri'=>''),
                                                                            'variables.xml'=>array('open_uri'=>'','ssl_uri'=>'')),
                                                        'dfa_backup_asset'=>array('backup_336x280.jpg'=>''),
                                                        'is_there'=>true,
                                                        'dfa_creative_html'=>'arbitrary html',
                                                        'placement_id'=>'',
                                                        'creative_id'=>''
                                                        );
                    break;
               
               
               
               
                   
            }
                   
                   $any_complete_filesets = false;
                   foreach($dfa_creative_set as $dfa_size_id=>$creative_set_details){
                       foreach($creative_set_details['dfa_assets'] as $filename=>$dfa_asset_id){
                           $dfa_creative_set[$dfa_size_id]['is_there'] = $dfa_creative_set[$dfa_size_id]['is_there']&&$this->is_file_there($path_parts['dirname'].'/*'.$filename);
                       }
                       foreach($creative_set_details['cdn_assets'] as $filename=>$cdn_asset_link){
                           $dfa_creative_set[$dfa_size_id]['is_there'] = $dfa_creative_set[$dfa_size_id]['is_there']&&$this->is_file_there($path_parts['dirname'].'/*'.$filename);
                       }
                       foreach($creative_set_details['dfa_backup_asset'] as $filename=>$dfa_asset_link){
                           $dfa_creative_set[$dfa_size_id]['is_there'] = $dfa_creative_set[$dfa_size_id]['is_there']&&$this->is_file_there($path_parts['dirname'].'/*'.$filename);
                       }
                       $any_complete_filesets = $any_complete_filesets || $dfa_creative_set[$dfa_size_id]['is_there'];
                   }
                   
                   return array('success'=>$any_complete_filesets,'file_details'=>$dfa_creative_set,'is_html_creative'=>$is_html_creative);
                   
            
        }
        
        
        
        public function is_file_there($file_string){//* are wildcards
            $it = iterator_to_array(new GlobIterator($file_string, GlobIterator::CURRENT_AS_PATHNAME) );
            return  count($it) == 0 ? false : true ;
        }
        
        public function get_first_file_match($file_string){
            $it = iterator_to_array(new GlobIterator($file_string, GlobIterator::CURRENT_AS_PATHNAME) );
            return reset($it);
        }
        
        
        
        
        public function is_vl_creative_type_supported($vl_creative_type){
           $success = false;
            switch ($vl_creative_type) {
                case 'vl_hd_w_fullscreen_image':
                    $success = true;
                    break;
                case 'vl_sd_wo_fullscreen_image':
                    $success = true;
                    break;
                case 'vl_hd_wo_fullscreen_image':
                    $success = true;
                    break;
                case 'vl_sd_w_fullscreen_image':
                    $success = true;
                    break;
                case 'vl_hd_gpa_w_fullscreen_image':
                    $success = true;
                    break;
                case 'vl_hd_gpa_wo_fullscreen_image':
                    $success = true;
                    break;
                
                default:
            }
            return $success;
        }
        
       
        
     
        
        public function create_dfa_image_asset($creativeWsdl,$options,$headers,$advertiserId,$filename){
           
            $creativeService = new SoapClient($creativeWsdl, $this->fix_soap_client_options_for_dfa($options));

            $creativeService->__setSoapHeaders($headers);
            //$filename = 'http://code.google.com/images/code_logo.gif';
            //$filename = 'http://localhost/dfa_creatives/channing_300x250.gif';
            // Create creative asset structure.
            $creativeAsset = array(
                'name' => 'Asset ' . uniqid().'.gif',
                'advertiserId' => $advertiserId,
                'content' => file_get_contents($filename),
                'forHTMLCreatives' => FALSE);

	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;
	    $result['error_counter'] = 0;
	    
	    while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
	    {	    
		try
		{
		    // Save the creative asset.
		    $result['dfa_result'] = $creativeService->saveCreativeAsset($creativeAsset);
		    $result['is_success'] = TRUE;
		}
		catch (Exception $e)
		{
		    $result['err_msg'] = $e->getMessage();
		    $result['error_counter'] += 1;
		}
	    }
            return $result;
            // Display the filename of the newly created creative asset.
//            print "Creative asset with filename of \"" . $result->savedFilename
//                . "\" was created.";
        }
        
         public function create_dfa_asset($creativeWsdl,$options,$headers,$advertiserId,$filename,$for_html_creatives){
            $creativeService = new SoapClient($creativeWsdl, $this->fix_soap_client_options_for_dfa($options));

            $creativeService->__setSoapHeaders($headers);
            //$filename = 'http://code.google.com/images/code_logo.gif';
            //$filename = 'http://localhost/dfa_creatives/channing_300x250.gif';
            // Create creative asset structure.
           
            $path_parts = pathinfo($filename);
            
            $creativeAsset = array(
                'name' => uniqid().'.'.$path_parts['extension'],
                'advertiserId' => $advertiserId,
                'content' => file_get_contents($filename),
                'forHTMLCreatives' => $for_html_creatives);
	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;
	    $result['error_counter'] = 0;
	    
	    while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
	    {	    
		try
		{
		    // Save the creative asset.
		    $result['dfa_result'] = $creativeService->saveCreativeAsset($creativeAsset);
		    $result['is_success'] = TRUE;
		} 
		catch (Exception $e)
		{
		    $result['err_msg'] = $e->getMessage();
		    $result['error_counter'] += 1;
		}
	    }
            return $result;
        }
        
        
        
        public function create_dfa_asset_for_html_creative($creativeWsdl,$options,$headers,$advertiserId,$filename){
           
            $creativeService = new SoapClient($creativeWsdl, $this->fix_soap_client_options_for_dfa($options));

            $creativeService->__setSoapHeaders($headers);
            //$filename = 'http://code.google.com/images/code_logo.gif';
            //$filename = 'http://localhost/dfa_creatives/channing_300x250.gif';
            // Create creative asset structure.
            
            
            //$for_html_creatives = ($creative_type_id == 1 ? false : true);
            $for_html_creatives = TRUE;
            $path_parts = pathinfo($filename);
            
            $creativeAsset = array(
                'name' => uniqid().'.'.$path_parts['extension'],
                'advertiserId' => $advertiserId,
                'content' => file_get_contents($filename),
                'forHTMLCreatives' => $for_html_creatives);
	    
	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;
	    $result['error_counter'] = 0;
	    
	    while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
	    {	    
		try
		{
		    // Save the creative asset.
		    $result['dfa_result'] = $creativeService->saveCreativeAsset($creativeAsset);
		    $result['is_success'] = TRUE;
		}
		catch (Exception $e)
		{
		    $result['err_msg'] =  $e->getMessage();
		    $result['error_counter'] += 1;
		}
	    }
//            // Display the filename of the newly created creative asset.
//            print "Creative asset with filename of \"" . $result->savedFilename
//                . "\" was created.<br>";
            return $result;
        }
        
        
         //////new function
        public function upload_creative_to_dfa($creativeWsdl, $options,$headers,$namespace,$creative_name,$advertiserId,$campaignId,$assetFilename,$sizeId,$creative_type_id,$creativeID = 0){
            $version = 1;
           echo 'asset filename: '.$assetFilename .'<br>';
            
            
            
            $creativeService = new SoapClient($creativeWsdl, $this->fix_soap_client_options_for_dfa($options));
            $creativeService->__setSoapHeaders($headers);
//            $creative_type = ($creative_type_id == 1 ? 'ImageCreative' : 'HTMLCreative');
//            $creative_type = 'HTMLCreative';
            // Create creative structure.
            
            $arbitrary_html = '<html>
                                <head>
                                    <title>Arbitrary '.$version.'</title>
                                </head>
                                <body>
                                    <h1>HTML Code</h1>
                                </body>
                                </html>';
            
            $creative = array(
                'active' => TRUE,
                'advertiserId' => $advertiserId,
                'archived' => FALSE,
                'id' => $creativeID,
                'name' => $creative_name,
                'sizeId' => $sizeId,
                'typeId' => $creative_type_id, // NOT Hard-coded to type 'Image Creative'.
                'version' => $version,
                'creativeAssets'=> array(array('assetFilename' => $assetFilename[0]),array('assetFilename' => $assetFilename[1]),array('assetFilename' => $assetFilename[2])),
                'HTMLCode'=> $arbitrary_html);

            // Creatives implement an abstract type, CreativeBase. Because of this, an
            // xsi:type is required in the SOAP message to specify which implementation is
            // being sent. This SoapVar wrapper will say that this is an ImageCreative. OR HTMLCreative
            $creative = new SoapVar($creative, SOAP_ENC_OBJECT, 'HTMLCreative',$namespace);

            try {
                // Save the creative.
                $result = $creativeService->saveCreative($creative, $campaignId);
            } catch (Exception $e) {
                print $e->getMessage();
                
                echo 'Request : <br/><xmp>', 
                ($creativeService->__getLastRequest()), 
                '</xmp><br/>';
                
                //echo "REQUEST:<br>" . htmlentities($creativeService->__getLastRequest()) . "<br>";
                exit(1);
            }
            //
            echo 'Request : <br/><xmp>', 
                ($creativeService->__getLastRequest()), 
                '</xmp><br/>';
            // Display the ID of the newly created creative.
            //print "Creative with ID \"" . $result->id . "\" was created.";
            return $result;
            
        }
        
     
        
        
        
	//Unused function. Not being maintained.
        public function delete_dfa_creative($creativeWsdl,$options,$headers,$id){
	    /*
            $creativeService = new SoapClient($creativeWsdl, $this->fix_soap_client_options_for_dfa($options));
            $creativeService->__setSoapHeaders($headers);
            
	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;
	    
	    try {
                // Save the creative asset.
                $result['dfa_result'] = $creativeService->deleteCreative($id);
		$result['is_success'] = TRUE;
            } catch (Exception $e) {
               $result['err_msg'] = $e->getMessage();
            }
            return $result;
	     * 
	     */
        }
        
	//Unused function. Not being maintained.
        public function delete_dfa_placement($placementWsdl,$options,$headers,$id){
            /*
	    $placementService = new SoapClient($placementWsdl, $this->fix_soap_client_options_for_dfa($options));
            $placementService->__setSoapHeaders($headers);
	    
	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;
	    
	    try {
                // Save the creative asset.
                $result['dfa_result'] = $placementService->deletePlacement($id);
		$result['is_success'] = TRUE;
            } catch (Exception $e) {
               $result['err_msg'] = $e->getMessage();
            }
            return $result;
	     */
        }
        
	//Unused function. Not being maintained.
        public function delete_dfa_campaign($campaignWsdl,$options,$headers,$id){
	    /*
            $campaignService = new SoapClient($campaignWsdl, $this->fix_soap_client_options_for_dfa($options));
            $campaignService->__setSoapHeaders($headers);
	   
	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;
	    
	    try {
                // Save the creative asset.
                $result['dfa_result'] = $campaignService->deleteCampaign($id);
		$result['is_success'] = TRUE;
            } catch (Exception $e) {
               $result['err_msg'] = $e->getMessage();
            }
            return $result;
	    */
        }
        
        
        public function upload_image_creative_to_dfa($creativeWsdl, $options,$headers,$namespace,$creative_name,$advertiserId,$campaignId,$assetFilename,$sizeId){
            $creativeService = new SoapClient($creativeWsdl, $this->fix_soap_client_options_for_dfa($options));
            $creativeService->__setSoapHeaders($headers);

            // Create creative structure.
            $creative = array(
                'id' => 0,
                'name' => $creative_name,
                'advertiserId' => $advertiserId,
                'assetFilename' => $assetFilename,
                'sizeId' => $sizeId,
                'typeId' => 1, // Hard-coded to type 'Image Creative'.
                'active' => TRUE,
                'archived' => FALSE,
                'version' => 1);

            

            // Creatives implement an abstract type, CreativeBase. Because of this, an
            // xsi:type is required in the SOAP message to specify which implementation is
            // being sent. This SoapVar wrapper will say that this is an ImageCreative.
            $creative = new SoapVar($creative, SOAP_ENC_OBJECT, 'ImageCreative',$namespace);

	    
	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;
	    $result['error_counter'] = 0;
	    
	    while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
	    {	    
		try
		{
		    // Save the creative.
		    $result['dfa_result'] = $creativeService->saveCreative($creative, $campaignId);
		    $result['is_success'] = TRUE;
		}
		catch (Exception $e)
		{
		    $result['err_msg'] = $e->getMessage();
		    $result['error_counter'] += 1;
		}
	    }
            // Display the ID of the newly created creative.
            //print "Creative with ID \"" . $result->id . "\" was created.";
            return $result;
            
	}

	private function get_dfa_advertisers_page($advertiserService, $searchCriteria, &$result)
	{
		$is_success = false;
		$err_msg = "";
		$error_counter = 0;

		while($error_counter < $this->max_tries && $is_success != true)
		{
			try
			{
				// Fetch the advertisers.
				$result['dfa_result'] = $advertiserService->getAdvertisers($searchCriteria);
				$is_success = true;
			}
			catch (Exception $e)
			{
				$error_counter += 1;
				$err_msg = $e->getMessage();
			}
		}

		$result['is_success'] = $is_success && $result['is_success'];
		$result['err_msg'] = $err_msg;
		$result['error_counter'] = $error_counter;
	}
        
	public function get_all_advertisers($advertiserWsdl,$options,$headers)
	{
		// Get AdvertiserService.
		$advertiserService = new SoapClient($advertiserWsdl, $this->fix_soap_client_options_for_dfa($options));
		$advertiserService->__setSoapHeaders($headers);

		// Create advertiser search criteria structure.
		$advertiserSearchCriteria = array(
			'pageNumber' => 0,
			'pageSize' => 1000,
			'searchString' => '*',
			'includeAdvertisersWithOutGroupOnly' => FALSE,
			'includeInventoryAdvertisersOnly' => FALSE,
			'subnetworkId' => 0
		);

		$result['is_success'] = true;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;
		$result['dfa_advertiser_records'] = NULL;
		$result['error_counter'] = 0;

		$advertiser_records = array();

		$dfa_result = null;
		$current_page = 1;

		do
		{
			$advertiserSearchCriteria['pageNumber'] = $current_page;
			$this->get_dfa_advertisers_page($advertiserService, $advertiserSearchCriteria, $result);
			if($result['is_success'] === true)
			{
				$dfa_result = $result['dfa_result'];
				$advertiser_records = array_merge($advertiser_records, $dfa_result->records);
			}
			else
			{
				break;
			}

			$current_page++;

		} while($current_page <= $dfa_result->totalNumberOfPages);

		unset($result['dfa_result']);
		$result['dfa_advertiser_records'] = $advertiser_records;

		return $result;
	}
        
	public function fetch_placement_types($placementWsdl,$options,$headers){
                $placementService = new SoapClient($placementWsdl, $this->fix_soap_client_options_for_dfa($options));
                $placementService->__setSoapHeaders($headers);
               
		$result['is_success'] = FALSE;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;
		$result['error_counter'] = 0;
	    
		while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
		{
		    try
		    {
			// Fetch placement types.
			$result['dfa_result'] = $placementService->getPlacementTypes();
			$result['is_success'] = TRUE;
		    }
		    catch (Exception $e)
		    {
			$result['error_counter'] += 1;
			$result['err_msg'] = $e->getMessage();
		    }
		}
		    return $result;
        }
        
        
        public function fetch_creative_types($creativeWsdl,$options,$headers){
                $creativeService = new SoapClient($creativeWsdl, $this->fix_soap_client_options_for_dfa($options));
                $creativeService->__setSoapHeaders($headers);
		
		$result['is_success'] = FALSE;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;		
		$result['error_counter'] = 0;
	    
		while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
		{		
		    try
		    {
			// Fetch placement types.
			$result['dfa_result'] = $creativeService->getCreativeTypes();
			$result['is_success'] = TRUE;
		    }
		    catch (Exception $e)
		    {
			$result['error_counter'] += 1;
			$result['err_msg'] = $e->getMessage();
		    }
		}
                return $result;
        }
        
        public function fetch_all_sites($siteWsdl, $options,$headers,$networkId){
                $siteService = new SoapClient($siteWsdl, $this->fix_soap_client_options_for_dfa($options));
                $siteService->__setSoapHeaders($headers);
                // Create site search criteria structure.
                $siteSearchCriteria = array(
                    'pageNumber' => 0,
                    'pageSize' => 10,
                    'searchString' => '*',
                    'excludeSitesMappedToSiteDirectory' => FALSE,
                    'networkId' => $networkId,
                    'subnetworkId' => 0);

		$result['is_success'] = FALSE;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;		
		$result['error_counter'] = 0;
	    
		while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
		{		
		    try
		    {
			// Fetch placement types.
			$result['dfa_result'] = $siteService->getDfaSites($siteSearchCriteria);
			$result['is_success'] = TRUE;
		    }
		    catch (Exception $e)
		    {
			$result['error_counter'] += 1;
			$result['err_msg'] = $e->getMessage();
		    }
		}
                return $result;
        }
        
        
        public function fetch_pricing_types($placementWsdl, $options,$headers){
            $placementService = new SoapClient($placementWsdl, $this->fix_soap_client_options_for_dfa($options));
            $placementService->__setSoapHeaders($headers);
            
	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;	
	    $result['error_counter'] = 0;
	    
	    while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
	    {
		try
		{
		    // Fetch pricing types.
		    $result['dfa_result'] = $placementService->getPricingTypes();
		    $result['is_success'] = TRUE;
		}
		catch (Exception $e)
		{
		    $result['error_counter'] += 1;
		    $result['err_msg'] = $e->getMessage();
		}
	    }
            return $result;
        }
        
        
        public function fetch_ad_types($adWsdl, $options, $headers){
            $adService = new SoapClient($adWsdl, $this->fix_soap_client_options_for_dfa($options));
            $adService->__setSoapHeaders($headers);

	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;	    
	    $result['error_counter'] = 0;
	    
	    while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
	    {	    
		try
		{
		    // Fetch ad types.
		    $result['dfa_result'] = $adService->getAdTypes();
		    $result['is_success'] = TRUE;
		}
		catch (Exception $e)
		{
		    $result['error_counter'] += 1;
		    $result['err_msg'] = $e->getMessage();
		}
	    }
            return $result;
        }
        
        public function fetch_sizes($sizeWsdl, $options,$headers){
            $sizeService = new SoapClient($sizeWsdl, $this->fix_soap_client_options_for_dfa($options));
            $sizeService->__setSoapHeaders($headers);
            $ids = array(4307,3454,2321);

            $sizeSearchCriteria = array(
                'width' => -1,
                'height' => -1,
                'ids'=> $ids);

	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;
	    $result['error_counter'] = 0;
	    
	    while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
	    {	    
		try
		{
		    // Fetch the size.
		    $result['dfa_result'] = $sizeService->getSizes($sizeSearchCriteria);
		    $result['is_success'] = TRUE;
		}
		catch (Exception $e)
		{
		    $result['error_counter'] += 1;
		    $result['err_msg'] = $e;
		}
	    }
            return $result;
        }
        
        public function dfa_authenticate_me($username,$password,$applicationName, $loginWsdl, $namespace, $options, $header){
            // Get LoginService.
            $loginService = new SoapClient($loginWsdl, $this->fix_soap_client_options_for_dfa($options));      
            $loginService->__setSoapHeaders($header);
            
	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;
	    $result['error_counter'] = 0;
	    
	    while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
	    {	    
		try
		{
		    $result['dfa_result'] = $loginService->authenticate($username, $password);
		    $result['is_success'] = TRUE;
		}
		catch (Exception $e)
		{
		    $result['error_counter'] += 1;
		    $result['err_msg'] = $e->getMessage();
		}
	    }
            return $result;
        }
        
        public function fetch_campaign_ids_from_advertiser_id($campaignWsdl,$options,$headers, $advertiser_id){
            $campaignService = new SoapClient($campaignWsdl, $this->fix_soap_client_options_for_dfa($options));
            $campaignService->__setSoapHeaders($headers);

            // Create campaign search criteria structure.
            $campaignSearchCriteria = array(
                'pageNumber' => 0,
                'pageSize' => 1000,
                'advertiserIds'=> array($advertiser_id));
	    
	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;
	    $result['error_counter'] = 0;
	    
	    while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
	    {	    
		try
		{
		    // Fetch the campaigns.
		    $result['dfa_result'] = $campaignService->getCampaignsByCriteria($campaignSearchCriteria);
		    $result['is_success'] = TRUE;
		}
		catch (Exception $e)
		{
		    $result['error_counter'] += 1;
		    $result['err_msg'] = $e->getMessage();
		}
	    }
            return $result;
        
        }
        
	public function fetch_campaign_from_advertiser_with_name($campaignWsdl,$options,$headers, $advertiser_id, $name){
            $campaignService = new SoapClient($campaignWsdl, $this->fix_soap_client_options_for_dfa($options));
            $campaignService->__setSoapHeaders($headers);

            // Create campaign search criteria structure.
            $campaignSearchCriteria = array(
                'pageNumber' => 0,
                'pageSize' => 1000,
		'searchString' => $name,
                'advertiserIds'=> array($advertiser_id));

	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;
	    $result['error_counter'] = 0;
	    
	    while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
	    {	    
		try
		{
		    // Fetch the campaigns.
		    $result['dfa_result'] = $campaignService->getCampaignsByCriteria($campaignSearchCriteria);
		    $result['is_success'] = TRUE;
		}
		catch (Exception $e)
		{
		    $result['error_counter'] += 1;
		    $result['err_msg'] = $e->getMessage();
		}
	    }
            return $result;
        }
	
        public function fetch_placement_ids_from_campaign_id($placementWsdl,$options,$headers,$campaign_id){
             $placementService = new SoapClient($placementWsdl, $this->fix_soap_client_options_for_dfa($options));
            $placementService->__setSoapHeaders($headers);

            $placementSearchCriteria = array(
                'pageNumber' => 0,
                'pageSize' => 1000,
                'searchString' => '*',
                'placementFilter' => 0,
                'campaignIds'=>array($campaign_id));

	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;	    
	    $result['error_counter'] = 0;
	    
	    while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
	    {	    
		try
		{
		// Fetch the placements.
		    $result['dfa_result'] = $placementService->getPlacementsByCriteria($placementSearchCriteria);
		    $result['is_success'] = TRUE;
		}
		catch (Exception $e)
		{
		    $result['error_counter'] += 1;
		    $result['err_msg'] = $e->getMessage();
		}
	    }
            return $result;
        }
        
        
        
        public function insert_new_advertiser_name($advertiser_name,$advertiserWsdl,$options,$headers,$networkId){
       
            // Get AdvertiserService.
            $advertiserService = new SoapClient($advertiserWsdl, $this->fix_soap_client_options_for_dfa($options));
            $advertiserService->__setSoapHeaders($headers);

            // Create advertiser structure.
            $advertiser = array(
                'id' => 0,
                'name' => htmlentities($advertiser_name),
                'networkId' => $networkId,
                'approved' => TRUE,
                'advertiserGroupId' => 0,
                'hidden' => FALSE,
                'impressionExchangeEnabled' => FALSE,
                'inventoryAdvertiser' => FALSE,
                'spotId' => 0,
                'subnetworkId' => 0);

	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;		    
	    $result['error_counter'] = 0;
	    
	    while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
	    {	    
		try
		{
		    // Save the advertiser.
		    $result['dfa_result'] = $advertiserService->saveAdvertiser($advertiser);
		    $result['is_success'] = TRUE;
		}
		catch (Exception $e)
		{
		    $result['error_counter'] += 1;
		    $result['err_msg'] = $e->getMessage();
		}
	    }
           return $result;

        }
        public function get_advertiser_name_from_id($advertiserWsdl, $options,$headers, $id)
	{
               
                $advertiserService = new SoapClient($advertiserWsdl, $this->fix_soap_client_options_for_dfa($options));
                $advertiserService->__setSoapHeaders($headers);

                // Create advertiser search criteria structure.
                $advertiserSearchCriteria = array(
                    'pageNumber' => 0,
                    'pageSize' => 1,
                    'includeAdvertisersWithOutGroupOnly' => FALSE,
                    'includeInventoryAdvertisersOnly' => FALSE,
                    'subnetworkId' => 0,
                    'ids' => array($id));
		
		$result['is_success'] = FALSE;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;	
		$result['error_counter'] = 0;
	    
		while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
		{		
		    try
		    {
			// Fetch the advertisers.
			$result['dfa_result'] = $advertiserService->getAdvertisers($advertiserSearchCriteria);
			$result['is_success'] = TRUE;
		    }
		    catch (Exception $e)
		    {
			$result['error_counter'] += 1;
			$result['err_msg'] = $e->getMessage();
		    }
		}
                return $result;
	}
	    
	    public function get_advertiser_with_name($advertiserWsdl, $options,$headers, $search_name)
	    {
               
                $advertiserService = new SoapClient($advertiserWsdl, $this->fix_soap_client_options_for_dfa($options));
                $advertiserService->__setSoapHeaders($headers);

                // Create advertiser search criteria structure.
                $advertiserSearchCriteria = array(
                    'pageNumber' => 0,
                    'pageSize' => 1,
                    'includeAdvertisersWithOutGroupOnly' => FALSE,
                    'includeInventoryAdvertisersOnly' => FALSE,
                    'subnetworkId' => 0,
                    'searchString' => $search_name);
		
		$result['is_success'] = FALSE;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;
		$result['error_counter'] = 0;
	    
		while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
		{		
		    try
		    {
			// Fetch the advertisers.
			$result['dfa_result'] = $advertiserService->getAdvertisers($advertiserSearchCriteria);
			$result['is_success'] = TRUE;
		    }
		    catch (Exception $e)
		    {
			$result['error_counter'] += 1;
			$result['err_msg'] = $e->getMessage();
		    }
		}
		return $result;

            }
	    
            
            public function get_dimensions_from_size_id($sizeWsdl, $options,$headers,$id){
                $sizeService = new SoapClient($sizeWsdl, $this->fix_soap_client_options_for_dfa($options));
                $sizeService->__setSoapHeaders($headers);

                // Create size search criteria structure.
                $sizeSearchCriteria = array(
                    'width' => -1,
                    'height' => -1,
                    'ids'=> array($id));
//wtf
		$result['is_success'] = FALSE;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;
		$result['error_counter'] = 0;
	    
		while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
		{		
		    try
		    {
			// Fetch the size.
			$result['dfa_result'] = $sizeService->getSizes($sizeSearchCriteria);
			$result['is_success'] = TRUE;
		    }
		    catch (Exception $e)
		    {
			$result['error_counter'] += 1;
			$result['err_msg'] = $e->getMessage();
		    }
		}
                return $result;
            }
            
        public function get_dimensions_array_from_size_id($sizeWsdl, $options,$headers,$id){
                $sizeService = new SoapClient($sizeWsdl, $this->fix_soap_client_options_for_dfa($options));
                $sizeService->__setSoapHeaders($headers);

                // Create size search criteria structure.
                $sizeSearchCriteria = array(
                    'width' => -1,
                    'height' => -1,
                    'ids'=> array($id));

                try {
                    // Fetch the size.
                    $result = $sizeService->getSizes($sizeSearchCriteria);
                } catch (Exception $e) {
                    print_r($e);
                    exit(1);
                }
                return array('width'=>$result->records[0]->width,'height'=>$result->records[0]->height);
            }    
        
        public function insert_placement($placementWsdl, $options,$headers,$placement_name,$campaignId,$placementType,$dfaSiteId,$sizeId,$pricingType){
            $placementService = new SoapClient($placementWsdl, $this->fix_soap_client_options_for_dfa($options));
            $placementService->__setSoapHeaders($headers);

            // Create placement structure.
            $placement = array(
                'id' => 0,
                'name' => 'Placement ' . $placement_name,
                'campaignId' => $campaignId,
                'placementType' => $placementType,
                'dfaSiteId' => $dfaSiteId,
                'sizeId' => $sizeId,
                'pricingSchedule' => array(
                    'startDate' => strtotime('-1 day'),
                    'endDate' => strtotime('+1 year'),
                    'pricingType' => $pricingType,
                    'capCostOption' => 0,
                    'flighted' => FALSE),
                'archived' => FALSE,
                'contentCategoryId' => 0,
                'placementGroupId' => 0,
                'placementStrategyId' => 0,
                'paymentAccepted'=>TRUE,
                'siteId' => 0);

            // Set the placement tag settings by retrieving all of the regular placement tag
            // options and using them.
	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;
	    $result['error_counter'] = 0;
	    
	    $placement_success = FALSE;
	    while($result['error_counter'] < $this->max_tries && $placement_success != TRUE)
	    {	    
		try 
		{
		    // Fetch the tag options.
		    $placementTagOptions = $placementService->getRegularPlacementTagOptions();
		    $placement_success = TRUE;
		} catch (Exception $e) {
		    $result['err_msg'] = $e->getMessage();
		    $result['error_counter'] += 1;
		}
	    }
	    if($result['error_counter'] == $this->max_tries)
	    {
		return $result;
	    }
	    
	    $result['error_counter'] = 0;

            // Place the tag options in a tag settings configuration and add it to the
            // placement.
            $tagSettings = array(
                'includeClickTrackingStringInTags' => FALSE,
                'keywordHandlingOption' => 0);
            $tagTypes = array();
            foreach($placementTagOptions as $tag)
	    {
		$tagTypes[] = $tag->id;
            }
            $tagSettings['tagTypes'] = $tagTypes;
            $placement['tagSettings'] = $tagSettings;
	    
	    while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
	    {
		try 
		{
		// Save the placement.
		    $result['dfa_result'] = $placementService->savePlacement($placement);
		    $result['is_success'] = TRUE;
		}
		catch (Exception $e)
		{
		    $result['error_counter'] += 1;
		    $result['err_msg'] = $e->getMessage();
		}
	    }

            // Display the ID of the newly created placement.
            //print "Placement with ID \"" . $result->id . "\" was created.";
            return $result;
        }
            
        public function insert_campaign(
				 	$campaignWsdl,
					$options,
					$headers,
					$campaign_name,
					$landing_page_url,
					$advertiser_id
				)
				{
            $campaignService = new SoapClient($campaignWsdl, $this->fix_soap_client_options_for_dfa($options));

           
            $campaignService->__setSoapHeaders($headers);

            // Create campaign structure.
            $campaign = array(
                'id' => 0,
                'name' => htmlentities($campaign_name),
                'advertiserId' => $advertiser_id,
                'startDate' => strtotime('-1 day'),
                'endDate' => strtotime('+4 year'),
                'archived' => FALSE);

            // Create the default landing page for the campaign.
            $landingPage = array(
                'id' => 0,
                'name' => 'Default Landing Page Name',
                'url' => $landing_page_url);

	    $result['is_success'] = FALSE;
	    $result['err_msg'] = "";
	    $result['dfa_result'] = NULL;	    
	    $result['error_counter'] = 0;
	    
	    $lp_success = FALSE;
	    while($result['error_counter'] < $this->max_tries && $lp_success != TRUE)
	    {		    
		try
		{
		// Save the landing page.
		    $landingPageResult = $campaignService->saveLandingPage($landingPage);
		    $lp_success = TRUE;
		}
		catch (Exception $e)
		{
		    $result['error_counter'] += 1;
		    $result['err_msg'] = $e->getMessage();
		}
	    }
	    
	    if($result['error_counter'] == $this->max_tries)
	    {
		return $result;
	    }
	    $result['error_counter'] = 0;
	    
            // Add landing page to the campaign.
            $campaign['defaultLandingPageId'] = $landingPageResult->id;
			$campaign['landingPageIds'] = array($landingPageResult->id);
			
	    
	    while($result['error_counter'] < $this->max_tries && $result['is_success'] != TRUE)
	    {				
		try
		{
		    // Save the campaign.
		    $result['dfa_result'] = $campaignService->saveCampaign($campaign);
		    $result['is_success'] = TRUE;
		}
		catch (Exception $e)
		{
		   $result['error_counter'] += 1; 
		   $result['err_msg'] = $e->getMessage();
		}
	    }
            return $result;
        }
        
        
        public function build_security_header($username,$token,$namespace, $applicationName){
            require_once $_SERVER["DOCUMENT_ROOT"].'/dfa_files/DfaHeadersUtil.php';
            return array(DfaHeadersUtil::createWsseHeader($username, $token),$this->build_login_header($namespace, $applicationName));
        }
        
        public function build_login_header($namespace, $applicationName){
            require_once $_SERVER["DOCUMENT_ROOT"].'/dfa_files/DfaHeadersUtil.php';
            return DfaHeadersUtil::createRequestHeader($namespace, $applicationName);
        }
        
	public function load_dfa_token()
	{
	     //////////////////////////////////////////////////  SHOULD ONLY HAVE TO DO THIS ONCE      
	    $username = $this->config->item('dfa_username');
	    $password = $this->config->item('dfa_password');
	    $applicationName = $this->config->item('dfa_application_name');
	    $dfa_site = $this->config->item('dfa_site');
	    
	    $namespace = 'http://www.doubleclick.net/dfa-api/v1.19';
	    $options = array('encoding' => 'utf-8', 'trace'=>1);
	    $login_headers = $this->dfa_model->build_login_header($namespace, $applicationName);
	    $dfa_auth_object = $this->dfa_model->dfa_authenticate_me($username,$password,$applicationName, $this->get_login_wsdl(), $namespace, $options,$login_headers);
	    if($dfa_auth_object['is_success'] == FALSE)
	    {
		return FALSE;
	    }
	    $security_header = $this->dfa_model->build_security_header($username,$dfa_auth_object['dfa_result']->token,$namespace, $applicationName);


	    $this->session->set_userdata('dfa_username',$username);
	    $this->session->set_userdata('dfa_password',$password);
	    $this->session->set_userdata('dfa_app_name',$applicationName);
	    $this->session->set_userdata('dfa_options',$options);
	    $this->session->set_userdata('dfa_namespace',$namespace);
	    $this->session->set_userdata('dfa_token',$dfa_auth_object['dfa_result']->token);
	    $this->session->set_userdata('dfa_network_name',$dfa_auth_object['dfa_result']->networkName);
	    $this->session->set_userdata('dfa_network_id',$dfa_auth_object['dfa_result']->networkId);
	    //$this->session->set_userdata('is_prod',$isProd);
	    $this->session->set_userdata('dfa_site_id',$dfa_site);
	    return TRUE;
	}
	
	
	private function get_base_wsdl()
	{
	    return $this->config->item('dfa_base_wsdl_url');
	}

	public function get_advertiser_wsdl()
	{
	    return $this->get_base_wsdl().'dfa-api/advertiser?wsdl';
	}

	public function get_campaign_wsdl()
	{
	    return $this->get_base_wsdl().'dfa-api/campaign?wsdl';
	}

	public function get_placement_wsdl()
	{
	    return $this->get_base_wsdl().'dfa-api/placement?wsdl';
	}

	public function get_login_wsdl()
	{
	    return $this->get_base_wsdl().'dfa-api/login?wsdl';
	}

	public function get_site_wsdl()
	{
	    return $this->get_base_wsdl().'dfa-api/site?wsdl';
	}

	public function get_ad_wsdl()
	{
	    return $this->get_base_wsdl().'dfa-api/ad?wsdl';
	}

	public function get_size_wsdl()
	{
	    return $this->get_base_wsdl().'dfa-api/size?wsdl';
	}

	public function get_creative_wsdl()
	{
	    return $this->get_base_wsdl().'dfa-api/creative?wsdl';
	}

	public function secure_dfa_tag($tag)
	{
		return str_replace("http://", "https://", $tag);
	}
	public function get_tags($placement_id, $campaign_id)
	{
	    $authToken = $this->session->userdata('dfa_token');
	    $applicationName = $this->session->userdata('dfa_app_name');
	    $namespace = $this->session->userdata('dfa_namespace');
	    $username = $this->session->userdata('dfa_username');
	    $options = $this->session->userdata('dfa_options');
	    $networkId = $this->session->userdata('dfa_network_id');
	    $headers = $this->build_security_header($username,$authToken,$namespace, $applicationName);
	    $tags = $this->get_dfa_tags($this->get_placement_wsdl(),$options,$headers,$campaign_id,$placement_id);
	    if($tags['is_success'] == FALSE)
	    {
		return FALSE;
	    }
	    return $tags['dfa_result']->placementTagInfos[0]->javaScriptTag;
	}
	
}


?>
