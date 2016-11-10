<?php
class Facebook_model extends CI_Model {
	
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
                $this->load->helper('url');
		$config = array(
						'appId'  => $this->config->item('facebookAppId'), //'296712543741535',
						'secret' => $this->config->item('facebookAppSecret'), //'2da378e0f70d9daa66b04216656584e6',
						'fileUpload' => true, // Indicates if the CURL based @ syntax for file uploads is enabled.
						);
		
		$this->load->library('Facebook', $config);
		
		$user = $this->facebook->getUser();

		// We may or may not have this data based on whether the user is logged in.
		//
		// If we have a $user id here, it means we know the user is logged into
		// Facebook, but we don't know if the access token is valid. An access
		// token is invalid if the user logged out of Facebook.
		$profile = null;
		if($user)
		{
			try {
			    // Proceed knowing you have a logged in user who's authenticated.
				$profile = $this->facebook->api('/me?fields=id,name,link,email');
                                
			} catch (FacebookApiException $e) {
                         //   print_r($e); exit;
				error_log($e);
			    $user = null;
			}		
		}
		
		$fb_data = array(
						'me' => $profile,
						'uid' => $user,
						'loginUrl' => $this->facebook->getLoginUrl(
							array(
								'scope' => 'email,user_birthday,publish_stream', // app permissions
								'redirect_uri' => normal_url('/fb/fb_connect') // URL where you want to redirect your users after a successful login
							)
						),
						'logoutUrl' => $this->facebook->getLogoutUrl(),
		);
                
		$this->session->set_userdata('fb_data', $fb_data);
	}
}
