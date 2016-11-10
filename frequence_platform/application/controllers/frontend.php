<?php
class frontend extends CI_Controller {
  public function __construct(){
    parent::__construct() ;
    $this->load->library('tank_auth');
    $this->load->library('session');                
    $this->load->model('Frontend_model','frontend',TRUE);
    $this->load->helper('url');
    $this->load->helper('uniqurl');
  }
        
  public function index($page = FALSE)
  {              
    if ( ! file_exists('application/views/frontend/pages/'.$page.'.php'))
      {
	// Whoops, we don't have a page for that!
	show_404();
      }
    $data['title'] = ucfirst($page); // Capitalize the first letter
	
    $this->load->view('frontend/templates/header', $data);
    $this->load->view('frontend/pages/'.$page, $data);
    $this->load->view('frontend/templates/footer', $data);
  }        
  public function error($error){            
    $this->session->sess_destroy();
    echo '<div class="error" style="padding:20px; margin:100px auto; text-align:center; border:1px solid #a00; font-size:22px; width:300px; text-transform:capitalize; color:#d00">';
    echo $error;
    echo '</div>';
  }
        
  /**
   * Check usecode available or not.
   * @param string $id {coupon id} 
   * @param string $num_of_coupon { max number of coupon }
   * @return true|false.
   */
  private function is_coupon_available($id,$num_of_coupon){
    $total_uses = $this->frontend->get_total_use_code_count_by_id($id);
    if($num_of_coupon > $total_uses ) return true;
    return false;
  }
  //                                           COUPON STARTS HERE
  public function coupon($encoded_id='')
  {   
    // parse to a unique id
    $error->status = false;
            
    $id = parse_uniq_url($encoded_id);
    $this->load->model('Metric_model','metric',false) ;            
    $share = false ; // << Coupon is not shared yet !
            
    if(!$campaign = $this->campaign->get_coupon_by_id($id)){
                
      exit($this->error($this->config->item('coupon_notfound')));
    }
    if($this->metric->stat_valid_upto($id) < time()){
      // coupon is expired 
      exit($this->error($this->config->item('coupon_expired')));
    }
            
    // check access mode
    if( isset($_GET['mode'])  && $_GET['mode'] == 'test' ){
      //if (!$this->tank_auth->is_logged_in()) redirect('/auth/login/');
      $this->session->set_userdata('mode','test');    // save coupon mode in session .
      $mode = $_GET['mode'];
    }else{                  
      $this->session->set_userdata('mode','live');
      $mode = 'live';
      if(!$campaign->in_run || !$campaign->visible || !$this->is_coupon_available($id,$campaign->num_of_coupon)){
	exit($this->error($this->config->item('coupon_notfound')));
      } 
    }
    $campaign->encoded_id = $encoded_id ;
            
    // get theme switch
    $campaign->background = 'default';
    $campaign->theme = 'default' ;
    $theme_available = $this->config->item('coupon_theme') ;
    if(isset($_GET['theme'])){
      if(in_array($_GET['theme'],$theme_available)){
	if($_GET['theme'] == 'background')
	  {$campaign->background = 'on';}
	else
	  {$campaign->theme = $_GET['theme'] ;}
      }else{
	exit($this->error($this->config->item('theme_notfound')));
      }
    }else{
      // nothing
    }
           
            
    $this->session->set_userdata('coupon_id',$id);    // save coupon id in session
            
    $matric = $this->session->userdata('matric');
    // inset data in matric table if mode not equal test
    if( !isset($matric[$id]) || ( $matric[$id]['matric_id'] == 'dump' && $mode != 'test' ) ){
                
      if( $mode == 'test' ){
	$this->session->set_userdata('matric',array( $id => array('matric_id' => 'dump')));
                    
      }else{
	$matric_id = $this->frontend->update_matric($id);
	$this->session->set_userdata('matric',array( $id => array('matric_id' => $matric_id)));
      }
                
      $campaign->sharepopup = false;
      $campaign->show_coupon_use_code = false;
    }
            
    // get coupon action type if coupon type is QPO
    //123=6,124=7,12=3,13=4,23=5
            
    if($campaign->coupon_type==1){
      $actions = $this->campaign->get_actions($id);                    
      foreach($actions as $val){        
	$campaign->actions[] =  $val['action_id'];
      }                    
                                        
      $action_str = implode('',$campaign->actions) ;
                    
      // find permutations of actions and create message parameter 
      $msg = $this->config->item('c_msg');
                    
      $campaign->share_msg = $msg[$action_str] ;
    }
            
    if($campaign->coupon_type==1 && !isset($_POST['email'])){           // process coupon if coupon code is QPO
                    
      $fb_data = $this->session->userdata('fb_data');                    
      $gmail_data = $this->session->userdata('google_auth');
      $yahoo_data = $this->session->unset_userdata('yahoo_auth');
                    
      if(isset($fb_data['me']) && $fb_data['me']){               // check fb authendication         

	if( $this->frontend->check_claim($fb_data['me']['email']) && $mode != 'test' ){   // return error if mode != test and duplicate claim
                                    
	  $error->status = true;
	  $error->msg = $this->config->item('duplicate_claim');
	  $campaign->success = 'error';
	}else{ 
	  $this->session->set_userdata('claimer_email',$fb_data['me']['email']);
	  $share = new stdClass() ; // << share object
	  $share->by = $fb_data['me']['email'] ; // << claimer's mail
	  $share->id = $id ;
	  $campaign->claimer->used_code_id = $this->frontend->process_claim($id,'1',$mode);
	  $this->session->unset_userdata('fb_data');                                    
	  session_start(); session_destroy();
	  $campaign = $this->frontend->show_coupon($campaign);  
	  $share->usecode = $campaign->coupon_usecode ; // << Coupon's usecode
	  $campaign->sharepopup = true;
	}
      }elseif(isset($gmail_data) && $gmail_data){                 // check google authendication
	$campaign->claimer->used_code_id = $this->frontend->process_claim($id,'2',$mode);  
	$share = new stdClass() ; // << share object
	$share->by = $gmail_data['claimer_email'] ; // << claimer's mail
	$share->id = $id ;
	$this->session->unset_userdata('google_auth');
	$campaign = $this->frontend->show_coupon($campaign);
	$share->usecode = $campaign->coupon_usecode ; // << Coupon's usecode
                                    
      }elseif(isset($yahoo_data)&&$yahoo_data){                   // check yahoo authendication
	$campaign->claimer->used_code_id = $this->frontend->process_claim($id,'2',$mode);
	$share = new stdClass() ; // << share object
	$share->by = $this->session->userdata('claimer_email') ; // << claimer's mail
	$share->id = $id ;
	$this->session->unset_userdata('yahoo_auth');
	$campaign = $this->frontend->show_coupon($campaign);   
	$share->usecode = $campaign->coupon_usecode ; // << Coupon's usecode
      }
    }
    else{
                
      if( isset($_POST['email']) && (  $mode == 'test' || !$this->frontend->check_claim($_POST['email']) ) ){
                    
	$this->session->set_userdata('claimer_email',$_POST['email']);
	$share = new stdClass() ; // << share object
	$share->by = $_POST['email'] ; // << claimer's mail
	$share->id = $id ;
	$campaign->claimer->used_code_id = $this->frontend->process_claim($id,'3',$mode);
	$campaign = $this->frontend->show_coupon($campaign); 
	$share->usecode = $campaign->coupon_usecode ; // << Coupon's usecode
                            
      }elseif( isset($_POST['email']) && $this->frontend->check_claim($_POST['email'])){
                            
	$error->status =true;
	$error->msg=$this->config->item('duplicate_claim');
      }
    }
            
    if($error->status){ 
      $campaign->error = $error;
      $this->session->sess_destroy();
      session_start(); session_destroy();
    }else{
      $campaign->error = $this->session->userdata('error');                        
      if(!$campaign->error){
	$campaign->error->status = false;
      }else{
	$this->session->sess_destroy();
	session_start(); session_destroy();                            
      }
    }            
    if($mode == 'test'){ 
      $campaign->testmodeactive = true;
    }else {
      $campaign->testmodeactive = false;
    }
                
    $campaign->coupon_id = $id; 
    if($share){  // mail is still being sent so as admin will know that claiming is working fine
      $this->send_claim_email($share);  
    } 
                    
    $this->load->view('frontend/templates/coupon', $campaign);
  }
       
  public function google_auth(){
    //setting parameters
    $coupon_id = $this->session->userdata('coupon_id'); 
    $data->encoded_id = uniq_url($coupon_id);
    if(isset($_GET['error'])){
      $this->session->sess_destroy();
      $data->success=$_GET['error'];
    }
    else{
      $authcode= $_GET["code"];                    
      $clientid=$this->config->item('gmailapp_clientID');
      $clientsecret=$this->config->item('googel_secret');
      $redirecturi=normal_url('frontend/google_auth');
      $fields=array(
		    'code'=>  urlencode($authcode),
		    'client_id'=>  urlencode($clientid),
		    'client_secret'=>  urlencode($clientsecret),
		    'redirect_uri'=>  urlencode($redirecturi),
		    'grant_type'=>  urlencode('authorization_code')
                    );
      //url-ify the data for the POST
      $fields_string='';
      foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
      $fields_string=rtrim($fields_string,'&');
      //open connection
      $ch = curl_init();
      //set the url, number of POST vars, POST data
      curl_setopt($ch,CURLOPT_URL,'https://accounts.google.com/o/oauth2/token');
      curl_setopt($ch,CURLOPT_POST,5);
      curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
      // Set so curl_exec returns the result instead of outputting it.
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      //to trust any ssl certificates
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      //execute post
      $result = curl_exec($ch);
      //close connection
      curl_close($ch);
      //var_dump($result);
      //echo "<br><br>";
      //extracting access_token from response string
      $response=  json_decode($result);
      //var_dump($response);
      $accesstoken= $response->access_token;
      //passing accesstoken to obtain contact details
      $xmlresponse=  file_get_contents('https://www.google.com/m8/feeds/contacts/default/full?max-results=99999&oauth_token='.$accesstoken);
      //reading xml using SimpleXML   
      $xml=  new SimpleXMLElement($xmlresponse);
      $xml->registerXPathNamespace('gd', 'http://schemas.google.com/g/2005');
      $email = (array)$xml->id;

      $result = $xml->xpath('//gd:email');
                        
      $data->result = $result;
      $data->step = 1;
                        
      if($this->session->userdata('mode') == 'test' || !$this->frontend->check_claim($email['0'])){
                            
	$this->session->set_userdata('claimer_email',$email['0']);
                                
      }else if($this->frontend->check_claim($email['0'])){                            
	$error->status = true;
	$error->msg = $this->config->item('duplicate_claim');
	$this->session->set_userdata('error',$error);
	$data->success = 'error';
      }
    }
    $data->campaign = $this->campaign->get_coupon_by_id($coupon_id);
    $this->load->view('frontend/templates/referrer',$data);                    
  }
        
  public function show_popup($encoded_id = FALSE){
    if( !isset( $_POST['referrer']) && !isset($_POST['emails'])){
      if($encoded_id != FALSE){
	$coupon_id = parse_uniq_url($encoded_id);
	if($data->campaign = $this->campaign->get_coupon_by_id($coupon_id)){
	  if($data->campaign->coupon_type !=1 ){
	    redirect('frontend/coupon/'.$encoded_id);
	  }
	  $this->session->set_userdata('coupon_id',$coupon_id);           
	  $data->encoded_id = $encoded_id; 
	  $this->load->view('frontend/templates/referrer',$data);
	}else{
	  redirect('frontend/coupon/'.$encoded_id);
	}
      }else{
	redirect('frontend/coupon/');
      }
    }elseif($encoded_id || $this->campaign->get_coupon_by_id($coupon_id)){
      $post_data = array( $referrer = FALSE, $emails = FALSE, $msg = FALSE );
      if(isset($_POST['referrer'])) $post_data['referrer'] = $_POST['referrer'];
      if(isset($_POST['emails'])) $post_data['emails'] = $_POST['emails'];
      if(isset($_POST['msg'])) $post_data['msg'] = $_POST['msg'];
               
      $this->save_referrer($post_data,$encoded_id);
    }else{
      redirect('frontend/coupon/');
    }           
  }
        
  /**
   * Validate referrer data and save
   * @param type $post_data
   * @param type $encoded_id 
   */
  private function save_referrer($post_data = array(), $encoded_id = FALSE){
    $data = array();
            
    $data['encoded_id'] = uniq_url($this->session->userdata('coupon_id'));           
                        
    if($post_data['referrer'])  $_referrer = $_POST['referrer'];
            
    if($post_data['emails']){ $emails = explode(',', $post_data['emails']); }
            
    $msg = $post_data['msg'];            
            
    $email_data = array();
    foreach($emails as $val){
      if($val !='')  $email_data[] = array('email'=>$val);
    }
    if(is_array($_referrer)){
      foreach($_referrer as $val){
	$email_data[] = array('email'=>$val);
      }
    }
            
    if($this->session->userdata('mode') == 'test'){                
      $data['testmodeactive'] = true;                
    }else{
      if(is_array($_referrer)){
	try{
	  $this->send_ref_email($_referrer,$msg);
	}catch(Exception $e){
	  // do nothing
	}                    
      }
      $this->send_ref_email($emails,$msg);
      $data['testmodeactive'] = false;                
    }

    $this->session->set_userdata('insert_referrer',$email_data);
            
    $this->session->set_userdata('google_auth',array('auth'=>'success','claimer_email'=>$this->session->userdata('claimer_email')));
    $data['success'] = true;
    $data['campaign']->id = $this->session->userdata('coupon_id');
    $this->load->view('frontend/templates/referrer',$data);
  }
        
  public function send_ref_email($_referrer,$msg){
    $this->load->library('email'); // load codeingntor email library
            
    // get campaign data by coupon id
    $campaign = $this->campaign->get_coupon_by_id($this->session->userdata('coupon_id'));
            
    if($campaign->img_filename) {
      $campaign->img_filename = base_url('assets/img/uploads').'/'.$campaign->img_filename;
    }else{
      $campaign->img_filename = base_url('resource/img/coupon_icon.jpg'); 
    }
    $template = '';
    $template .= '<p>'.$msg.'</p>';
    $template .= '<table border="0">
                    <tr border="0" height="146px">
                    <td border="0" width="146px" height="146px">
                        <div class="coupon_icon">
                            <img src="'.$campaign->img_filename.'" width="146" height="146">            
                        </div>
                    </td>
                    <td border="0" width="310px" bgcolor="#894A9C" height="136px" align="center" style="font-size:30px;color:#FFD200">
                                                   '.$campaign->title.'
                   </td>
                    </tr>
                    <tr border="0">
                        <td border="0" colspan="2" bgcolor="#682F79" height="40px" align="center" style="font-size:20px;color:#fff">
                            <div style=style="margin: 0px 8px 0px 8px; color:#fff; font-family:Amaranth, sans-serif;font-size: 20px;padding: 5px 0px">
                                '.$campaign->description.'
                            </div>
                        </td>
                    </tr>
                    <tr border="0">
                    <td border="0" colspan="2" bgcolor="#5A236B" align="center" style="font-size:20px;">
                        <div style="margin: 0px 8px 0px 8px; color: white; font-family:Amaranth, sans-serif; font-size: 20px; padding: 5px 0px; margin-top: 2px">
                            Discount '.$campaign->discount.'%
                         </div></td>
                    </tr>
                    </table>';
                
    $subject = $campaign->title ? $campaign->title : $this->config->item('campaign_email_subject') ;
    $from = explode('<',$campaign->from_mail) ;
    $from_name = trim($from[0]) ; $from_mail = trim($from[1],'>') ;
    foreach($_referrer as $val){
      $this->email->from($from_mail,$from_name);
      $this->email->to($val);
      $this->email->subject($subject);
      $this->email->message($template);
      $this->email->send();
      $this->email->clear();                
    }
  }
        
  public function send_claim_email($share_obj){
    $this->load->library('email');
            
    $campaign = $this->campaign->get_coupon_by_id($share_obj->id);
    if($campaign->img_filename) { 
      $campaign->img_filename = base_url('assets/img/uploads').'/'.$campaign->img_filename;
    }else{
      $campaign->img_filename = base_url('resource/img/coupon_icon.jpg'); 
    }
    $template = '';
    $template .= '<p>'.$campaign->share_message.'</p>';
    $template .= '<table border="0">
                    <tr border="0" height="146px">
                    <td border="0" width="146px" height="146px">
                        <div class="coupon_icon">
                            <img src="'.$campaign->img_filename.'" width="146" height="146">            
                        </div>
                    </td>
                    <td border="0" width="310px" bgcolor="#894A9C" height="136px" align="center" style="font-size:30px;color:#FFD200">
                                                   '.$campaign->title.'
                   </td>
                    </tr>
                    <tr border="0">
                        <td border="0" colspan="2" bgcolor="#682F79" height="40px" align="center" style="font-size:20px;color:#fff">
                            <div style=style="margin: 0px 8px 0px 8px; color:#fff; font-family:Amaranth, sans-serif;font-size: 20px;padding: 5px 0px">
                                '.$campaign->description.'
                            </div>
                        </td>
                    </tr>
                    <tr border="0">
                    <td border="0" colspan="2" bgcolor="#5A236B" align="center" style="font-size:20px;">
                        <div style="margin: 0px 8px 0px 8px; color: white; font-family:Amaranth, sans-serif; font-size: 20px; padding: 5px 0px; margin-top: 2px">
                             '.$campaign->discount.'
                         </div></td>
                    </tr>
                    <tr border="0">
                    <td border="0" colspan="2" bgcolor="#5A236B" align="center" style="font-size:20px;">
                        <div style="margin: 0px 8px 0px 8px; color: white; font-family:Amaranth, sans-serif; font-size: 20px; padding: 5px 0px; margin-top: 2px">
                             '.$share_obj->usecode.'
                         </div></td>
                    </tr>
                    </table>';
    $subject = $campaign->title ? $campaign->title : $this->config->item('campaign_email_subject') ;
    $from = explode('<',$campaign->from_mail) ;
    $from_name = trim($from[0]) ; $from_mail = trim($from[1],'>') ;

    $this->email->from($from_mail,$from_name);
    $this->email->to($share_obj->by);
    $this->email->subject($subject);
    $this->email->message($template);
    $this->email->send();
    $this->email->clear();                
            
  }
        
  function claim_by_mail(){
    $mail = $this->input->post('mail') ;
    $action = $this->input->post('mode') ;
    $id = $this->input->post('id') ;
    $mode = $this->session->userdata('mode') ;
            
    if($action == 4){
      // save coupon id in session
      //$this->session->set_userdata('coupon_id',$id);    // save coupon id in session
      $metric = $this->session->userdata('matric');
      if(!isset($metric[$id])){
	// metrics are keyed by coupon id
	// create a metric id entry
	$metric_id = $this->frontend->update_matric($id);
	$this->session->set_userdata('matric',array( $id => array('matric_id' => $matric_id))); // save to session
      }else{
	$metric_id = $metric[$id]['matric_id'] ; // get metric id
	if( $mode == 'test'){
	  $this->session->set_userdata('matric',array( $id => array('matric_id' => 'dump')));
                         
	}
      }
                
                 
      $campaign = $this->campaign->get_coupon_by_id($id) ;
      $share = new stdClass();
            
      if( $this->frontend->check_claim($mail) && $mode != 'test' ){   // return error if mode != test and duplicate claim
	$share->msg = $this->config->item('duplicate_claim');
	$share->status = 'error';              
      }else{ 
	$share->status = 'success';
	$this->session->set_userdata('claimer_email',$mail);
	$share->by = $mail ; // << claimer's mail
	$share->id = $id ;
	$campaign->claimer->used_code_id = $this->frontend->process_claim($id,$action,$mode);
	$campaign = $this->frontend->show_coupon($campaign);  
	$share->usecode = $campaign->coupon_usecode ; // << Coupon's usecode
	$share->expire = $campaign->expire ;
                                    
	// send a mail to claimer
	if($this->send_claim_email($share)){
	  $share->mail_sent = true ;
	}
      }
                
    }
    echo json_encode($share) ;
  }
}