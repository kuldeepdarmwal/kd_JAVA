<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin extends CI_Controller {

  public function __construct(){
    parent::__construct() ;
    $this->load->database();
    $this->load->library('tank_auth');
  }

  public function index()
  {
	
    $this->load->helper('url') ;
    // Check if admin have already logged
    if (!$this->tank_auth->is_logged_in()) {
      redirect('/auth/login/');
    }else{
		
      // load helpers & libraries we'll require on page
      $this->load->helper('form') ;
      $this->load->library(array('form_validation','upload','table'));
		
      // configuration for file upload
      $config['upload_path'] = 'assets/img/uploads';
      $config['allowed_types'] = 'gif|jpg|png';
      $config['max_size'] = '100';
      $config['max_width'] = '1024';
      $config['max_height'] = '768';
      $this->upload->initialize($config);

      // load Campaign model class
      $this->load->model('Campaign_model','campaign',TRUE);
                
                
		
      // define validation rules
      $validation_rules = array(
				array(
				      'field' => 'campaign-name',
				      'label' => 'Campaign Name',
				      'rules' => 'trim|required|xss_clean'
				      ),
				array(
				      'field' => 'business-name',
				      'label' => 'Business Name',
				      'rules' => 'trim|required|xss_clean'
				      ),
				array(
				      'field' => 'coupon-title',
				      'label' => 'Coupon title',
				      'rules' => 'trim|required|xss_clean'
				      ),
				array(
				      'field' => 'landing-page',
				      'label' => 'Landing Page',
				      'rules' => 'trim|required|xss_clean'
				      ),
				array(
				      'field' => 'coupon-discount',
				      'label' => 'Coupon Discount',
				      'rules' => 'trim|required|integer|xss_clean'
				      ),
				array(
				      'field' => 'coupon-desc',
				      'label' => 'Coupon description',
				      'rules' => 'trim|required|xss_clean'
				      ),
				array(
				      'field' => 'coupon-validity',
				      'label' => 'Coupon validity',
				      'rules' => 'trim|required|integer|max_length[3]|xss_clean'
				      ),
				array(
				      'field' => 'coupon-max',
				      'label' => 'Maximum no. of Coupon',
				      'rules' => 'trim|required|integer|max_length[3]|xss_clean'
				      ),
				array(
				      'field' => 'coupon-mail-msg',
				      'label' => 'Coupon mail default message',
				      'rules' => 'trim|required|xss_clean'
				      ),
				array(
				      'field' => 'coupon-mail-from',
				      'label' => 'Coupon mail default from mail',
				      'rules' => 'trim|required|xss_clean'
				      ),
				);
      // push already defined rules to validation set
      $this->form_validation->set_rules($validation_rules); 
		
      // define form fields
      $data['form_attributes'] = array(
				       'class'=>'admin-campaign',
				       'id'=>'campaign-create'
				       );
      // ff : Form Fields
      $data['ff_campaign_name'] = array(
					'name' => 'campaign-name',
					'id'   => 'campaign-name',
					'maxlength' => '100' ,
					);
						
    $data['ff_landing_page'] = array(
				     'name' => 'landing-page',
				     'id'   => 'landing-page',
				     'maxlength' => '100',
				     );				
      $options = array() ; // re-usable variable
      $businesses = $this->campaign->get_business_available() ;
      $i = 0;
      foreach($businesses as $business){
	$options[$business->Name] = $business->Name ;
      }
      $data['ff_business_name'] = $options ;
		
      $data['ff_coupon_title'] = array(
				       'name' => 'coupon-title',
				       'id'   => 'coupon-title',
				       'maxlength' => '100' ,
				       );
      $data['ff_coupon_discount'] = array(
					  'name' => 'coupon-discount',
					  'id'   => 'coupon-discount',
					  'maxlength' => '100' ,
					  );
      $data['ff_coupon_desc'] = array(
				      'name' => 'coupon-desc',
				      'id'   => 'coupon-desc',
				      'rows' => '3' ,
				      'cols' => '20'
				      );
      $data['ff_coupon_validity'] = array(
					  'name' => 'coupon-validity',
					  'id'   => 'coupon-validity',
					  'maxlength' => '3' ,
					  );
                        
      $data['ff_coupon_mail_msg'] = array(                    'name' => 'coupon-mail-msg',
							      'id'   => 'coupon-mail-msg',
							      'rows' => '2' ,
							      'cols' => '20'
							      ) ;
      $data['ff_coupon_mail_from'] = array(                   'name' => 'coupon-mail-from',
							      'id'   => 'coupon-mail-from',
							      'maxlength' => '255' ,
							      ) ;
		
      $options = array() ; // re-usable variable
      $coupon_types = $this->campaign->get_coupon_types_available() ;
      foreach($coupon_types as $coupon_type){
	$options[$coupon_type->id] = $coupon_type->name ;
      }
      $data['ff_coupon_types'] = $options ;
																		
      $data['ff_coupon_actions'] = array() ; // re-usable variable
      $data['lbl_coupon_actions'] = array() ;		
      $coupon_actions = $this->campaign->get_actions_available() ;
      foreach($coupon_actions as $coupon_action){
	array_push($data['ff_coupon_actions'],array(
						    'name'=>'coupon-actions[]',
						    'class'=>'action-check',
						    'value'=>$coupon_action->id,
						    'checked'=> ($coupon_action->id == 3 || $coupon_action->id == 4 ) ? FALSE : TRUE ,
						    )
		   ) ;
	array_push($data['lbl_coupon_actions'],$coupon_action->name ) ; 
      }


		
      $data['ff_coupon_max_num'] = array(
					 'name' => 'coupon-max',
					 'id'   => 'coupon-max',
					 'maxlength' => '7'
					 );
                
               
                
      // run page validation and render appropriate view
      $this->load->view('vlc_header');
      // logged-in user's detail
      $data['user_id']	= $this->tank_auth->get_user_id();
      $data['username']	= $this->tank_auth->get_username();
      if ($this->form_validation->run() == FALSE)
	{
	  // re-render page if form haven't validated
	  $this->load->view('admin_index',$data);
	}
      else
	{
	  $post_data = array(
			     'campaign_name' => $this->input->post('campaign-name') ,
			     'business_name' => $this->input->post('business-name') ,
			     'title' => $this->input->post('coupon-title') ,
			     'landing_page' => $this->input->post('landing-page'),
			     'discount' => $this->input->post('coupon-discount') ,
			     'description' => $this->input->post('coupon-desc') ,
			     'validity' => $this->input->post('coupon-validity') ,
			     'coupon_type' => $this->input->post('coupon-types') ,
			     'created' => time(),
			     'num_of_coupon' => $this->input->post('coupon-max') 
			     ) ;
	  // although image is not a required field 
	  // still check for image's availability
                    
	  // if there's an file upload error 
	  if (!$this->upload->do_upload())
	    {
	      $error = array('error' => $this->upload->display_errors());
                                
	    }
	  else
	    {
	      // file uploaded
	      // retrieve uploaded file details 
	      $uploaded_image = $this->upload->data() ;
	      // provide configuration for image resize
	      $config['image_library'] = 'gd2';
	      $config['source_image'] = $uploaded_image['full_path'];
	      $config['create_thumb'] = TRUE;
	      $config['maintain_ratio'] = TRUE;
	      $config['thumb_marker'] = '_campaign_'.time() ;
	      $config['width'] = 200;
	      $config['height'] = 100;
	      // initialize with image configuration
	      $this->load->library('image_lib', $config);
	      // resize .
	      $saved = $this->image_lib->resize();
	      // add image name to final post data 
	      if($saved){
		$post_data['img_filename'] = str_replace('.', $config['thumb_marker'].'.',$uploaded_image['file_name']);
		unlink($uploaded_image['full_path']);    
	      }
                                
	    }
	  // SAVE TO DB ( get Campaign's Id )		
	  $id = $this->campaign->create($post_data) ; 
                                                
	  // assign actions for this campaign
	  if($this->input->post('coupon-types') == 1){
	    $campaign_actions = array() ;
	    foreach($this->input->post('coupon-actions') as $action_id){
	      array_push($campaign_actions,
			 array(
			       'action_id' => $action_id ,
			       'coupon_id' => $id,
			       ) 
			 );
	    } 
	    $this->campaign->set_actions($campaign_actions) ;
	  }
	  // generate coupon codes
	  $this->load->helper('coupon') ;
	  $coupon_codes = coupon_codes($id,'-') ;
	  // prepares coupon data for database insert
	  $coupon_code_data = coupon_codes_prepare($id,$coupon_codes) ; 
	  // save to DB 
	  // load Usecode class
	  $this->load->model('Usecode_model','usecode',TRUE);
	  $this->usecode->create($coupon_code_data) ;
	  // provide a download option
                        
	  $this->load->helper('export');
	  $csv_file = 'campaign_'.$id.'_coupon_codes.csv' ;
	  export_to_csv($coupon_codes,10, $csv_file,true); 
	  // few additional template variables 
	  // as it was breaking next statements force download is commented in helper
	  // very experimental implementation
	  $data['new_campaign'] = 1 ;
	  $data['script_for_download'] = 'var download_win = window.open("'.
	    base_url('assets/exports/'.$csv_file).
	    '","_blank","width=600,height=400,scrollbars=1");'.
	    '' ;
										
						
                        
	  $this->load->view('admin_index',$data);
			
	}
      $this->load->view('vlc_footer');
    }		
  }
        
  /**
   * AJAX Backends --------------------------------------------------------------------
   * for following features :
   *  - coupon_available : enlist/returns all added campaigns(visible) as JSON response
   *  - campaign_toggle : pauses/resumes a running campaign
   *  - campaign_delete : unpublishes a campaign 
   */
        
  public function coupon_available(){
    $this->load->helper('url') ;
    $this->load->helper('uniqurl') ;
    $page_response = new stdClass();
            
    // meant to be accessed from from jQgrid plugin
    $page = $this->input->post('page') ;
    $limit = $this->input->post('rows') ;
    $sidx = $this->input->post('sidx') ;
    $sord = $this->input->post('sord') ;
           
    // load Campaign model class
    $this->load->model('Campaign_model','campaign',TRUE);
            
    $count = $this->campaign->get_coupon_available_count() ;
    if( $count >0 ) { 
      $total_pages = ceil($count/$limit);
    } else { 
      $total_pages = 0; 
                
    } 
    if ($page > $total_pages) $page=$total_pages; 
             
    $start = $limit * $page - $limit; 

            
    $coupons = $this->campaign->get_coupon_available_by($limit,$start,$sidx,$sord) ;
    $page_response->page = $page; 
    $page_response->total = $total_pages; 
    $page_response->records = $count;
    $i=0; 
    foreach($coupons['rows'] as $coupon){
      $page_response->rows[$i]['id']=$coupon->id;
      //$encoded_id = urlencode(base64_encode(urlencode(base64_encode($coupon->id)))) ;
      $page_response->rows[$i]['cell'] = array(
					       $coupon->business_name ,
					       $coupon->campaign_name ,
					       date('m/d/y',$coupon->created),
					       '<ul class="action-buttons">'.
					       '<li>'.
					       anchor('coupon/delete/'.$coupon->id,'Delete',array('title'=>'Delete this campaign','class'=>'btn-delete','id'=>'btn-delete-'.$coupon->id)).
					       '&nbsp;|</li>'.
					       '<li>'.
					       anchor('coupon/pause/'.$coupon->id, $coupon->in_run ? 'Pause' : 'Resume' ,array('title'=>'Pause/Resume this campaign','class'=>'btn-pause','id'=>'btn-pause-'.$coupon->id)).
					       '&nbsp;|</li>'.
					       //                            '<li>'.
					       //                                anchor('admin/view/'.$coupon->id,'View',array('class'=>'btn-view','id'=>'btn-view-'.$coupon->id)).
					       //                                '&nbsp;&nbsp;|</li>'.
					       '<li>'.
					       anchor(normal_url('assets/exports/campaign_'.$coupon->id.'_coupon_codes.csv'),'View',array('title'=>'Download & View campaign usecodes','class'=>'btn-view','id'=>'btn-view-'.$coupon->id)).
					       '&nbsp;|</li>'.
					       '<li>'.
					       anchor(normal_url('coupon/'.uniq_url($coupon->id).'?mode=test'),'Link',array('title'=>'Check/Test campaign operations','class'=>'btn-link','id'=>'btn-link-'.$coupon->id)).
					       '&nbsp;|</li>'.
					       '<li>'.
					       anchor('admin/metrics/'.$coupon->id,'Metrics',array('title'=>'View campaign metric','class'=>'btn-metrics','id'=>'btn-metrics-'.$coupon->id)).
					       '</li>'.
					       '</ul>',
					       ); 
      $i++;
    }
             
    // return 
    echo json_encode($page_response) ;
            
  }
        
  /**
   *
   * Creates a Campaign on AJAX based Submissions 
   *  
   */
        
  public function campaign_create(){
             
             
    $this->load->helper('url');
    $post_data = array(
		       'campaign_name' => $this->input->post('campaign-name') ,
		       'business_name' => $this->input->post('business-name') ,
		       'title' => $this->input->post('coupon-title') ,
		       'landing_page' => $this->input->post('landing-page'),
		       'discount' => $this->input->post('coupon-discount') ,
		       'description' => $this->input->post('coupon-desc') ,
		       'validity' => $this->input->post('coupon-validity') ,
		       'coupon_type' => $this->input->post('coupon-types') ,
		       'created' => time(),
		       'num_of_coupon' => $this->input->post('coupon-max') ,
		       'from_mail' => trim($this->input->post('coupon-mail-from')) ,
		       'share_message' => $this->input->post('coupon-mail-msg'), 
		       ) ;
            
             
    // crop image if a image have arrived
                        
    $uploaded_image = $this->input->post('uploaded_file') ;
    $source_path = './assets/img/uploads/'.$uploaded_image ;
    // provide configuration for image resize
    $config['image_library'] = 'gd2';
    $config['source_image'] = $source_path;
    $config['create_thumb'] = TRUE;
    $config['maintain_ratio'] = FALSE;
    $config['thumb_marker'] = '_icon' ;
    $config['width'] = 146;
    $config['height'] = 146;
    // initialize with image configuration
    $this->load->library('image_lib', $config);
    // resize .
    $saved = $this->image_lib->resize();
    // add image name to final post data 
    if($saved){
      $post_data['img_filename'] = str_replace('.', $config['thumb_marker'].'.',$uploaded_image);
      unlink($source_path);    
    }
    // load Campaign model class
    $this->load->model('Campaign_model','campaign',TRUE);
    // SAVE TO DB ( get Campaign's Id )		
    $id = $this->campaign->create($post_data) ; 
    // assign actions for this campaign
    if($this->input->post('coupon-types') == 1){
      $campaign_actions = array() ;
      foreach($this->input->post('coupon-actions') as $action_id){
	array_push($campaign_actions,
		   array(
			 'action_id' => $action_id ,
			 'coupon_id' => $id,
			 ) 
		   );
      }
                                
      $this->campaign->set_actions($campaign_actions) ;
    }
    // generate coupon codes
    $this->load->helper('coupon') ;
    $coupon_codes = coupon_codes($id,'-') ;
    // prepares coupon data for database insert
    $coupon_code_data = coupon_codes_prepare($id,$coupon_codes) ; 
    // save to DB 
    // load Usecode class
    $this->load->model('Usecode_model','usecode',TRUE);
    $this->usecode->create($coupon_code_data) ;
    // provide a download option
                        
    $this->load->helper('export');
    $csv_file = 'campaign_'.$id.'_coupon_codes.csv' ;
    export_to_csv($coupon_codes,10, $csv_file,true); 
    // as it was breaking next statements force download is commented in helper
    // very experimental implementation
    $post_data['usecode_csv'] = base_url('assets/exports/'.$csv_file) ;
                                
		
			
  
    echo json_encode($post_data) ;
  }
        
  /**
   * Creates a campaign with received input data set
   *  
   */
        
  public function campaign_img_processor(){
    // codeigniter file helper is not running for this kind of uploads
    $this->load->helper('url');
    if(isset($_FILES['file'])){
      $file_name = $_FILES['file']['name'] ;
      $file_name = 'camapign_'.time().'_'.strtolower($file_name) ; // overridden file name
      // overridden filename will be used in next request for 
      // actual file cropping and linking to campaign
      $file_path = './assets/img/uploads';
      $full_path = $file_path .'/'.$file_name ;
      $allowed_ext = 'gif|jpg|png';
      $error = array() ;
      //		$config['max_size'] = '100';
      //		$config['max_width'] = '1024';
      //		$config['max_height'] = '768';
      if (!move_uploaded_file($_FILES['file']['tmp_name'], $full_path)) {
	array_push($error, 'Cannot upload file') ;
	if(!file_exists($file_path)){
	  array_push($error, 'Folder don\'t exists') ;
	}elseif(!is_writable($file_path)){
	  array_push($error, 'Folder is not writable') ;
	}elseif(!is_writable($full_path)){
	  array_push($error, 'File is not writable') ;
	}
      }else{
	$size = round($_FILES['file']['size']/1024,2) ;
                   
	echo json_encode(array(
			       'size' => $size ,
			       'link' => base_url('assets/img/uploads/'.$file_name) ,
			       'file_name' => $file_name 
			       ));
	// other file operation/features
                   
      }

    }else{
      echo "fail";
    }
                
               

  }
        
        
  //---- Campaign's Status Change methods ----
  public function campaign_toggle(){
    if($this->input->is_ajax_request()){
      $response = new stdClass();
      $response->status = 0 ;
      $response->next_op = 'none' ;
      // meant to be accessed from from jQgrid plugin
      $id = $this->input->post('id') ;
           
      // load Campaign model class
      $this->load->model('Campaign_model','campaign',TRUE);
      $campaign = $this->campaign->get_coupon_by_id($id) ;
           
      if($campaign->in_run){
	$this->campaign->pause($id) ;
	$response->status = 1 ;
	$response->next_op = 'resume' ;
      }else{
	$this->campaign->resume($id) ;
	$response->status = 1 ;
	$response->next_op = 'pause'  ;
      }
      echo json_encode($response) ;
    } else {
      echo "<h3>We're Watching !</h3>";
    }
           
  }
       
  public function campaign_delete(){
    if($this->input->is_ajax_request()){
      $response = new stdClass();
      $response->status = 0 ;
      // meant to be accessed from from jQgrid plugin
      $id = $this->input->post('id') ;
           
      // load Campaign model class
      $this->load->model('Campaign_model','campaign',TRUE);
      $this->campaign->delete($id) ;
      $response->status = 1 ;
      echo json_encode($response) ;
           
     
    } else {
      echo "<h3>We're Watching !</h3>";
    }
  }
         
  // actual coupon claiming page 
  function cpn($id,$mode='live'){
    $this->load->helper('url') ;
    $this->load->helper('coupon') ;
    // determine coupon claim mode 
    $do_real = ($mode == 'live') ? true : false ;
    $data['text'] = $do_real ? 'LIVE' : 'TEST' ;
              
    //check if campaign is running or not 
    // load Campaign model class
    $this->load->model('Campaign_model','campaign',TRUE);
    $campaign = $this->campaign->get_coupon_by_id($id) ;
              
    // show error page if campaign is not running
    if(!$campaign->in_run){
      show_404() ;
    }
    $data['coupon'] = coupon_code($id) ;
              
    if($do_real){
      // additional logic for DB updates  
    }
              
    // load Campaign model class
    $this->load->view('vlc_header') ;
    $this->load->view('coupon',$data) ;
    $this->load->view('vlc_footer') ;
  }
         
         
  function view($id){
    // utilises easypaginate.js for pagination 
    $this->load->helper('url') ;
    $this->load->helper('coupon') ;

    $this->load->model('Usecode_model','usecode',TRUE) ;
    $usecodes = $this->usecode->get_by_id($id) ;
    foreach($usecodes  as  $usecode){
      $data['coupons'][] = $usecode->coupon_code ;
    }
            
    $this->load->view('vlc_header') ;
    $this->load->view('coupon_list',$data) ;
    $this->load->view('vlc_footer') ;
             
  }
         
  function metrics($id){
    $this->load->helper('url') ;
    $this->load->view('vlc_header') ;
    $this->load->view('coupon_metrics') ;
    $this->load->view('vlc_footer') ;
  }
	
}
