<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Connect extends CI_Controller{
    public function __construct(){
		parent::__construct() ;
                $this->load->library('session'); 

    
    }
    public function index(){


    }
	
	public function yahoo(){
		// Yahoo PHP-SDK config file
		require_once('./application/libraries/common.inc.php');
		$this->load->model('Campaign_model','campaign',false ) ;
		$this->load->model('Frontend_model','frontend',false ) ;
                $this->load->helper('uniqurl');
		// check if there's an action parameter
		$action = null;
		if ( isset($_GET['action']) ) {
			$action = $_GET['action'];
		}
		
		// return callback
		$callback = $this->config->item('yahooapp_callback');
		$key = $this->config->item('yahooapp_key');
		$secret = $this->config->item('yahooapp_secret');
		$app_id = $this->config->item('yahooapp_id');
		$domain = $this->config->item('yahooapp_domain');
		
		// Instantiate our application
		$app = new YahooOAuthApplication( $key, $secret, $app_id, $domain ); 
		
		if ( !$action || $action == 'request_token' ) {
			$request_token = $app->getRequestToken($callback );
			$_SESSION['request_token_key'] = $request_token->key;
			$_SESSION['request_token_secret'] = $request_token->secret;
			$redirect_url = $app->getAuthorizationUrl( $request_token );
			// redirect for authentication
			Header( "Location: $redirect_url");

		} else if ( $action == "authorized" ) {

			$request_token = new OAuthConsumer($_SESSION['request_token_key'], $_SESSION['request_token_secret']);
			$response = $app->getAccessToken($request_token, $_GET['oauth_verifier'] );
			parse_str($response, $params);
   
			$access_token = $params['oauth_token'];
			$access_token_secret = $params['oauth_token_secret'];
			$yahoo_guid = $params['xoauth_yahoo_guid'] ;
			$_SESSION['ACCESS_TOKEN'] = $access_token;
			$_SESSION['ACCESS_TOKEN_SECRET'] = $access_token_secret;
			$_SESSION['YAHOO_GUID'] = $yahoo_guid;

			$token =  new YahooOAuthAccessToken($_SESSION['ACCESS_TOKEN'], $_SESSION['ACCESS_TOKEN_SECRET'], null, null, null, $_SESSION['YAHOO_GUID']);
   
			$app->token = $token;
			$profile  = $app->getProfile() ;
                     
                        if(is_array($profile->profile->emails)){
                            $data['user_email'] = $profile->profile->emails[1]->handle ;     
                        }else{
                            $data['user_email'] = $profile->profile->emails->handle ;  
                        }
			 $contacts = $app->getContacts();                       
                        
			$mode = $this->session->userdata('mode');
			if( $mode !='test' && $this->frontend->check_claim($data['user_email']) ){
                               $error->status = true;
                               $error->msg = $this->config->item('duplicate_claim');
                               $this->session->set_userdata('error',$error);
                               $data['success'] = 'error';
                        }elseif(!$contacts){
                               $error->status = true;
                               $error->msg = $this->config->item('no_contact_found');
                               $this->session->set_userdata('error',$error);
                               $data['success'] = 'error';
                        }else{ 
				$this->session->set_userdata('claimer_email',$data['user_email']);
				$data['step'] = 2 ;			
				                               
				$data['user_contacts'] = array() ;                               
                                if(is_array($contacts->contact)){
                                    foreach($contacts->contact as $contact){                                        
                                            $ct = new StdClass() ;
                                            if(is_object($contact->fields)){
                                                if(isset($contact->fields->value))
                                                    $ct->email = $contact->fields->value;
                                            }elseif(is_array($contact->fields)){
                                                if($contact->fields[0]->type == 'email'){
                                                    $ct->email =  $contact->fields[0]->value ;
                                                }elseif($contact->fields[1]->type == 'email'){
                                                    $ct->email =  $contact->fields[1]->value ;
                                                }                                                
                                            }
                                            if(isset($ct)) array_push($data['user_contacts'], $ct) ;                                            
                                    }
                                }else{
                                    if(is_object($contacts->contact->fields)){
                                        if(isset($contacts->contact->fields->value))
                                            $ct->email = $contacts->contact->fields->value;
                                    }elseif(is_array($contacts->contact->fields)){
                                        if(isset($contacts->contact->fields[0]->value))
                                            $ct->email = $contacts->contact->fields[0]->value; 
                                    }
                                    if(isset($ct))  $data['user_contacts'][] = $ct;                                  
                                }                            
			}        
                        
			$data['campaign'] = $this->campaign->get_coupon_by_id($this->session->userdata('coupon_id')); 
                        $data['encoded_id'] = uniq_url($this->session->userdata('coupon_id'));
                        if( isset($data['user_contacts']) && empty($data['user_contacts']) && !isset($data['success'])){
                               $error->status = true;
                               $error->msg = $this->config->item('contact_list_error');
                               $this->session->set_userdata('error',$error);
                               $data['success'] = 'error';
                        }
                        
			$this->load->view('frontend/templates/referrer',$data);
                        
		}
	    
	
	}

}
?>
