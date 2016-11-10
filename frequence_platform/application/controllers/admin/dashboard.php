<?php/* if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dashboard extends CI_Controller {

  public function __construct(){
    parent::__construct() ;
    $this->load->database();
  }

  public function index()
  {
		
		
    // load helpers & libraries we'll require on page
    $this->load->helper(array('form','url')) ;
    $this->load->library(array('form_validation','upload','table'));
		
    // configs
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
				    'field' => 'landing-page',
				    'label' => 'Landing Page',
				    'rules' => 'trim|required|xss_clean'
				    ),
			      array(
				    'field' => 'coupon-title',
				    'label' => 'Coupon title',
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
			      );
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
				      'size' => '50'
				      );
    $data['ff_landing_page'] = array(
				     'name' => 'landing-page',
				     'id'   => 'landing-page',
				     'maxlength' => '100',
				     'size' => '50'
				     );
    $options = array() ; // re-usable variable
    $i = 0;
    $businesses = $this->campaign->get_business_available() ;
    foreach($businesses as $business){
      $options[$business->Name] = $business->Name ;
    }
    $data['ff_business_name'] = $options ;
		
    $data['ff_coupon_title'] = array(
				     'name' => 'coupon-title',
				     'id'   => 'coupon-title',
				     'maxlength' => '100' ,
				     'size' => '50'
				     );
    $data['ff_coupon_discount'] = array(
					'name' => 'coupon-discount',
					'id'   => 'coupon-discount',
					'maxlength' => '2' ,
					'size' => '50'
					);
    $data['ff_coupon_desc'] = array(
				    'name' => 'coupon-desc',
				    'id'   => 'coupon-desc',
				    'rows' => '4' ,
				    'cols' => '80'
				    );
    $data['ff_coupon_validity'] = array(
					'name' => 'coupon-validity',
					'id'   => 'coupon-validity',
					'maxlength' => '3' ,
					'size' => '50'
					);
		
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
						  'checked'=> ($coupon_action->id == 3 ) ? FALSE : TRUE ,
						  )
		 ) ;
      array_push($data['lbl_coupon_actions'],$coupon_action->name ) ; 
    }


		
    $data['ff_coupon_max_num'] = array(
				       'name' => 'coupon-max',
				       'id'   => 'coupon-max',
				       'maxlength' => '7' ,
				       'size' => '50'
				       );
                
    // data set for table
    $offset=0;$limit=5;
    $data['campaign_list'] = array() ;
    $coupons = $this->campaign->all($limit,$offset) ;
    foreach($coupons['rows'] as $coupon){
      $data['campaign_list'][] = array(
				       $coupon->business_name ,
				       $coupon->campaign_name ,
				       date('d/m/y',$coupon->created),
				       '<ul class="action-buttons">'.
				       '<li>'.anchor('coupon/delte/'.$coupon->id,'Delete',array('id'=>'btn-delete')).'</li>'.
				       '<li>'.anchor('coupon/pause/'.$coupon->id,'Pause',array('id'=>'btn-pause')).'</li>'.
				       '<li>'.anchor('coupon/view/'.$coupon->id,'View',array('id'=>'btn-view')).'</li>'.
				       '<li>'.anchor(normal_url('coupon/link/'.$coupon->id),'Link',array('id'=>'btn-link')).'</li>'.
				       '<li>'.anchor('coupon/metrics/'.$coupon->id,'Metrics',array('id'=>'btn-metrics')).'</li>'.
				       '</ul>',
				       ); 
    }
    $data['campaign_list_num'] = $coupons['count'] ;
    // pagination
    $this->load->library('Jquery_pagination') ;
    $config = array(
                    'base_url'=>site_url('admin'),
                    'total_rows'=>$coupons['count'],
                    'per_page'=>$limit,
                    'uri_segment'=>3 
		    );
    $this->jquery_pagination->initialize($config) ;
    $data['campaign_pagination_links'] = $this->jquery_pagination->create_links() ;
                
                
    // run page validation and render appropriate view
    $this->load->view('vlc_header');
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
	// create campaign			
	$this->campaign->create($post_data) ;
	$this->load->view('admin_index',$data);
	//$this->load->view('admin_success');
			
      }
    $this->load->view('vlc_footer');	
  }
	
}
     */