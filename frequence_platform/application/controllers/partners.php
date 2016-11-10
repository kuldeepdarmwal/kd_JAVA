<?php defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH.'/vendor/autoload.php';

class Partners extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		
		$this->load->model('partners_model');
		$this->load->model('demo_partner_model');
		$this->load->library('vl_aws_services');
		$this->load->library('tank_auth');
		$this->load->library('vl_platform');        
		$this->load->helper('encrypt_decrypt');        
		$this->load->helper('vl_ajax_helper');
		$this->load->helper('select2_helper');
	}
	
	public function index()
	{       
		$data = array();
		
		$redirect_string = $this->vl_platform->get_access_permission_redirect('partners');
		if($redirect_string == 'login')
		{
			$this->session->set_userdata('referer', '/partners');
			redirect($redirect_string);
		}
                else if($redirect_string !== '')
                {
                        redirect($redirect_string);
                }

                $user_id = $this->tank_auth->get_user_id();
                $username = $this->tank_auth->get_username();
                $is_super = $this->tank_auth->get_isGroupSuper($username);
                $role = $this->tank_auth->get_role($username);

                if($role == 'admin')
                {
                        $data['partners'] = $this->tank_auth->get_partner_array();
                }
                else if($role == 'sales')
                {
                        $data['partners'] = $this->tank_auth->get_partner_hierarchy_by_sales_person($user_id, $is_super);
                }

                foreach ($data['partners'] as $key => $partner)
                {
                        $data['partners'][$key]['partner_id'] = encrypt_id($partner['id']);
                        $partner_parent_and_other = $this->partners_model->get_partner_parent_and_other_data($partner['id']);
                        $data['partners'][$key]['parent_partner_name'] = $partner_parent_and_other['parent_partner_name'];
                        $data['partners'][$key]['cname'] = $partner_parent_and_other['cname'];
                        $data['partners'][$key]['is_demo_partner'] = $partner_parent_and_other['is_demo_partner'];
                        $data['partners'][$key]['num_partner_users'] = $this->partners_model->get_partner_users($partner['id']);
                }

                $data['title'] = 'Partners';
                $data['domain'] = $this->tank_auth->get_domain_without_cname();
                $data['user_role'] = $role;
                $data['partner_status'] = '';
                if (!empty($this->session->flashdata('partner_status')))
                {
                        $data['partner_status'] = $this->session->flashdata('partner_status');
                }

                $this->vl_platform->show_views(
                        $this,
                        $data,
                        'partners',
                        'partners/partners_view_html.php',
                        'partners/partners_view_header.php',
                        NULL,
                        'partners/partners_view_footer.php',
                        NULL
                );
        }

        public function add_edit_partner($encrypted_partner_id = null)	
        {
                // Login stuff
                $this->vl_platform->has_permission_to_view_page_otherwise_redirect('partners', 'partners/create');

                $edit_partner_id = '';
                if (!empty($encrypted_partner_id))
                {
                        $edit_partner_id = decrypt_id($encrypted_partner_id);
                        if (!$edit_partner_id || !$this->tank_auth->is_valid_partner_id($edit_partner_id))
                        {
                                redirect('/partners/create');
                        }
                }

                $user_id = $this->tank_auth->get_user_id();
                $username = $this->tank_auth->get_username();
                $is_super = $this->tank_auth->get_isGroupSuper($username);
                $role = $this->tank_auth->get_role($username);

                $data = array();
                $form_data = array();
                $data['partner_form_error_message'] = '';
				$data['partner_palette_info'] = '[]';

                $this->load->helper(array('form', 'url'));
                $this->load->library('form_validation');

                if ($this->input->post('source'))
                {            
                        $validation_rules = array(
                                array(
                                        'field'   => 'partner_name',
                                        'label'   => 'Partner Name',
                                        'rules'   => 'trim|required'
                                    ),
                                array(
                                        'field'   => 'partner_domain',
                                        'label'   => 'Partner Domain',
                                        'rules'   => 'trim|required'
                                    ),
                                array(
                                        'field'   => 'partner_homepage',
                                        'label'   => 'Partner Homepage',
                                        'rules'   => 'trim|required'
                                    )
                        );

                        if (empty($this->input->post('partner_id')))
                        {
                                $validation_rules[] = array(                
                                        'field'   => 'parent_partner',
                                        'label'   => 'Parent Partner',
                                        'rules'   => 'trim|required'
                                );
                        }

                        $this->form_validation->set_rules($validation_rules);          
                        if ($this->form_validation->run() == false)
                        {               
                                foreach ($validation_rules as $row)
                                {
                                        $error = form_error($row['field']);
                                        if ($error)
                                        {
                                                $data['partner_form_error_message'] .= '<br />'.strip_tags($error);
                                        }
                                }
                        }

                        $form_data['preview_title'] = $form_data['partner_name'] = $this->input->post('partner_name');
                        $form_data['preview_url'] = $form_data['cname'] = $this->input->post('partner_domain');
                        $form_data['home_url'] = $this->input->post('partner_homepage');
                        $form_data['parent_partner'] = $this->input->post('parent_partner');
			$form_data['partner_user_email'] = $this->input->post('partner_user_email');
			$form_data['advertiser_email'] = $this->input->post('advertiser_email');
						$partner_palette_id = $this->input->post('partner_palette_id');
						if(!is_numeric($partner_palette_id))
						{
							$partner_palette_id = null;
						}
						$form_data['partner_palette_id'] = $partner_palette_id;

                        $form_data['is_demo_partner'] = 0;
                        if (!empty($this->input->post('demo_partner')))
                        {
                                $form_data['is_demo_partner'] = 1;
                        }
                        $post_partner_id = '';
                        if (!empty($this->input->post('partner_id')))
                        {
                                $post_partner_id = decrypt_id($this->input->post('partner_id'));
                        }

                        // check for unique partner
                        if (!empty($form_data['partner_name']) && !empty($form_data['cname']))
                        {
                                // validation for partner name and cname
                                if($this->partners_model->validate_unique_partner($form_data['partner_name'], $form_data['cname'], $post_partner_id))
                                {
                                        $data['partner_form_error_message'] .= '<br /> Please provide other partner name or cname, as this is already in use';
                                }
                        }

                        // check for cname
                        if (!empty($form_data['cname']) && !empty($form_data['parent_partner']))
                        {
                                // validation for cname as per parent partner
                                if(!$this->partners_model->validate_cname_for_partner($form_data['cname'], $form_data['parent_partner'], $post_partner_id))
                                {
                                        $data['partner_form_error_message'] .= '<br /> Please provide other cname or parent partner, as this is already in use';
                                }
                        }
			// check for unique demo sales/adv emails
                        if (empty($encrypted_partner_id) && ($form_data['is_demo_partner'] == 1) && (!empty($form_data['partner_user_email'])) && (!empty($form_data['advertiser_email'])))
                        {
                                // validation for demo partner sales/adv emails
                                if($this->demo_partner_model->validate_unique_demo_emails($form_data['partner_user_email'], $form_data['advertiser_email']))
                                {
                                        $data['partner_form_error_message'] .= '<br /> Please provide other demo sales or demo advertiser emails, this is already in use';
                                } else if($form_data['partner_user_email'] == $form_data['advertiser_email'])
				{
					$data['partner_form_error_message'] .= '<br /> Please provide different demo sales and demo advertiser emails!';
				}
                        }
                        // For image validations
                        $file_names_array = array('file_login', 'file_header', 'file_favicon');
                        $new_file_names_array = array('login', 'header', 'favicon');
                        $file_count = 1;
                        foreach ($file_names_array as $file_name)
                        {
                                if ((empty($post_partner_id)) || (!empty($post_partner_id) && $_FILES[$file_name]['name']))
                                {
                                        // 1 for login, 2 for header and 3 for favicon image
                                        $error_message = $this->partners_model->validate_images($file_name, $file_count);

                                        if ($error_message)
                                        {
                                                $data['partner_form_error_message'] .= '<br /> '.$error_message;                           
                                        }
                                }
                                $file_count++;
                        }

			
                        if (empty($data['partner_form_error_message']))
                        {
                                //Insert into wl_partners_details and permisisons tables
                                $partner_id = $this->partners_model->save_partner_details($form_data, $post_partner_id);			
                                
				$decrypted_partner_id = encrypt_id($partner_id);

                                // Delete all permissions, hierarchy (if changed) on update
                                if (!empty($post_partner_id))
                                {
                                        $this->partners_model->delete_partner_details_on_update($role, $partner_id, $form_data['parent_partner']);
                                }

                                $this->partners_model->save_partner_features_permissions($partner_id, $role);                                

                                // set partner hierarchy
                                if ((empty($post_partner_id) && !empty($form_data['parent_partner'])) || (!empty($post_partner_id) && $this->partners_model->get_parent_partner_id($partner_id) != $form_data['parent_partner'] && !empty($form_data['parent_partner'])))
                                {
                                        $this->partners_model->save_partner_hierarchy($form_data['parent_partner'], $partner_id);
                                }

                                // Save in partner owners for non-super sales user
                                if ($role == 'sales' && !$is_super && empty($post_partner_id))
                                {
                                        $this->tank_auth->save_partner_owner($user_id, $partner_id);
                                }

                                // Upload Images                
                                $partners_directory = 'partners/'. preg_replace('/\s+/', '_', strtolower($form_data['partner_name'])).'/'.$decrypted_partner_id.'/';    
                                foreach ($file_names_array as $key => $file_name)
                                {
                                        if ($_FILES[$file_name]['name'])
                                        {
                                                $ext = pathinfo($_FILES[$file_name]['name'], PATHINFO_EXTENSION);
                                                $new_file_name = $new_file_names_array[$key].'.'.$ext;

                                                $file_result = $this->partners_model->upload_partner_images($file_name, $new_file_name, $partners_directory);            
                                                if ($file_result['is_success'])
                                                {
                                                        $image_data[$new_file_names_array[$key]] = S3_ASSETS_PATH.'/'.$partners_directory.$file_result['filename'];
                                                }
                                                else
                                                {
                                                        $form_data[$file_name] = $_FILES[$file_name]['name'];
                                                        $data['partner_form_error_message'] .= '<br /> Error in '.ucfirst($new_file_names_array[$key]).' Image upload, please try again! ('.$file_result['error'].')';
                                                }
                                        }
                                }

                                if (!$data['partner_form_error_message'])
                                {
                                        // Update Image paths in DB
                                        if (!empty($image_data))
                                        {
                                                $this->partners_model->update_partners_image_paths($image_data, $partner_id);
                                        }

                                        $status_message = empty($edit_partner_id) ? 'created' : 'edited';
                                        $this->session->set_flashdata('partner_status', 'Partner "'.$form_data['partner_name'].'" '.$status_message);

                                        // redirect to all partners
                                        redirect("/partners");
                                }
                                else
                                {
                                        $this->session->set_flashdata('partner_status', 'Error with image upload, please try again!');
                                        redirect("/partners/edit/".encrypt_id($partner_id));
                                }

                        }

                }
                else if (!empty($edit_partner_id) && !$this->input->post('source'))
                {
                        // Get partner details
                        $form_data = $this->tank_auth->get_partner_info($edit_partner_id);
			//Get Demo users details
			$form_data_demo = $this->tank_auth->get_demo_users_by_partner_id($edit_partner_id);
			$form_data['demo_sales_email'] = isset($form_data_demo[0]) ? $form_data_demo[0] : '';
                        $form_data['demo_adv_email'] = isset($form_data_demo[1]) ? $form_data_demo[1] : '';
			
                        $form_data['preview_title'] = $form_data['partner_name'];
                        $form_data['preview_url'] = $form_data['cname'];

                        // Get parent Partner id
                        $form_data['parent_partner'] = $this->partners_model->get_parent_partner_id($edit_partner_id);
						if(is_numeric($form_data['proposal_palettes_id']))
						{
							$partner_palette_info = $this->partners_model->get_select2_partner_palette_data(false, false, false, $form_data['proposal_palettes_id']);
						}
						else
						{
							$partner_palette_info = array();
						}
						$data['partner_palette_info'] = json_encode($partner_palette_info);
                }

                // Get Features
                $data['features_listing'] = $this->partners_model->get_features($role, $edit_partner_id);

                // Default data for preview section
                $form_data['default_partner_report_logo_filepath'] = '/assets/img/partner_preview/partner_login.png';
                $form_data['default_header_img'] = '/assets/img/partner_preview/partner_header.png';
                $form_data['default_favicon_path'] = '/assets/img/partner_preview/partner_favicon.png';
                $form_data['default_preview_url'] = 'domain';
                $form_data['default_preview_title'] = 'BrandCDN';

		// For demo checkbox fields
                $form_data['default_partner_user_email'] = '-partner-demo-sales@brandcdn.com';
                $form_data['default_advertiser_email'] = '-partner-demo-advertiser@brandcdn.com';
//		
                $data['form_data'] = $form_data;
                $data['create_edit_lable'] = empty($edit_partner_id) ? 'Create' : 'Edit';
                $data['source'] = empty($edit_partner_id) ? 'new' : 'update';
                $data['create_edit_button'] = empty($edit_partner_id) ? 'Create Partner' : 'Save Partner';
                $data['title'] = $data['create_edit_lable'].' Partner';
                $data['domain'] = $this->tank_auth->get_domain_without_cname();
                $data['partner_id'] = $encrypted_partner_id;
                $data['partner_status'] = '';
                if (!empty($this->session->flashdata('partner_status')))
                {
                        $data['partner_status'] = $this->session->flashdata('partner_status');
                }

                // To get parent partners
                if($role == 'admin')
                {
                        $data['parent_partner_name_options'] = $this->tank_auth->get_all_partners_except_self();
                }
                else if($role == 'sales')
                {
                        $data['parent_partner_name_options'] = $this->tank_auth->get_partner_hierarchy_by_sales_person($user_id, $is_super);
                }

                $data['parent_partner_name_option_array'] = array();
                if ($data['parent_partner_name_options'])
                {
                        // dis-allow all descendant partners and self
                        $all_partner_descendants = array();
                        if (!empty($edit_partner_id))
                        {
                                $all_partner_descendants = $this->tank_auth->get_all_descendant_partner_ids($edit_partner_id);
                                $all_partner_descendants[] = $edit_partner_id;
                        }

                        foreach($data['parent_partner_name_options'] as $value)
                        {
                                settype($value['id'], "int");
                                if (!in_array($value['id'], $all_partner_descendants))
                                {
                                        $data['parent_partner_name_option_array'][] = array(
                                            'id' => $value['id'],
                                            'text' => $value['partner_name']
                                        );
                                }
                        }
                }
                $data['parent_partner_name_option_array'] = json_encode($data['parent_partner_name_option_array']);

                // Demo and real partner features
                $data['is_demo_partner_accessible'] = 0;
                $data['is_real_partner_accessible'] = 0;
                if($role == 'admin')
                {
                        $data['is_demo_partner_accessible'] = 1;
                        $data['is_real_partner_accessible'] = 1;
                }
                else if($role == 'sales')
                {                        
                        $data['is_demo_partner_accessible'] = $this->vl_platform_model->is_feature_accessible($this->tank_auth->get_user_id(), 'demo_partner') ? 1 : 0;
                        $data['is_real_partner_accessible'] = $this->vl_platform_model->is_feature_accessible($this->tank_auth->get_user_id(), 'real_partner') ? 1 : 0;
                }

                $this->vl_platform->show_views(
                        $this,
                        $data,
                        'partners',
                        'partners/partner_form_html',
                        'partners/partner_form_header',
                        NULL,
                        'partners/partner_form_footer',
                        NULL
                );
        }

        public function check_cname_sales_adv_partner_unique()
        {
                $return_array = array('is_success' => false, 'errors' => "", 'data' => array());
                $allowed_roles = array('sales', 'admin');
                $response = vl_verify_ajax_call($allowed_roles);

                if($response['is_success'])
                {
                        $cname = $this->input->post('cname');
                        $parent_partner = $this->input->post('parent_partner');                        
                        $partner_name = $this->input->post('partner_name');
			$sales_email = $this->input->post('sales_email');
                        $adv_email = $this->input->post('adv_email');
			$partner_id = '';
                        if (!empty($this->input->post('partner_id')))
                        {
			    $partner_id = decrypt_id($this->input->post('partner_id'));
                        }
                        // check for unique partner
                        if (!empty($cname) && !empty($partner_name))
                        {
                                if(!$this->partners_model->validate_unique_partner($partner_name, $cname, $partner_id))
                                {
                                        $return_array['is_success'] = true;
                                }
                                else
                                {
                                        $return_array['errors'] = 'Please provide other partner name or cname, as this is already in use';
                                }
                        }
                        
                        // check for cname
                        if (!empty($cname) && !empty($parent_partner) && $return_array['is_success'] == true)
                        {
                                if(!$this->partners_model->validate_cname_for_partner($cname, $parent_partner, $partner_id))
                                {
                                        $return_array['is_success'] = false;
                                        $return_array['errors'] = 'Please provide other cname or parent partner, as this is already in use';
                                }
                                else
                                {
                                        $return_array['is_success'] = true;                                        
                                }
                        }
			// check for sales adv email
                        if (!empty($adv_email) && !empty($sales_email) && $return_array['is_success'] == true)
                        {
                                if(!$this->demo_partner_model->validate_unique_demo_emails($sales_email, $adv_email))
                                {
                                        $return_array['is_success'] = true;
                                }
                                else
                                {
                                        $return_array['is_success'] = false;
                                        $return_array['errors'] = 'Please provide other demo sales email or advertiser email, as this is already in use';
                                }
                        }
                }

                echo json_encode($return_array);
        }
	

        public function ban_partner_users()
        {
                $return_array = array('is_success' => false, 'errors' => "", 'data' => array());
                $allowed_roles = array('admin');
                $response = vl_verify_ajax_call($allowed_roles);

                if($response['is_success'])
                {
                        $partner_id = $this->input->post('partner_id');

                        if (!empty($partner_id))
                        {
                                $partner_id = decrypt_id($partner_id);
                                if ($partner_id && $this->partners_model->ban_partner_users($partner_id))
                                {
                                        $return_array['is_success'] = true;
                                }
                        }
                }

                echo json_encode($return_array);
        }
	
	/*
	 * Updating Dates through the Cron
	 */
	public function update_date_for_demo_data()
	{
		$result = $this->demo_partner_model->update_date_for_demo_data();
		if($result)
		{
			echo "Dates Updated Successfully";
		}
		else
		{
			echo "Dates Not Updated Successfully";
		}
		
		// Update the cache lifetime impressions
		$caching_response = shell_exec("php index.php campaigns_main cache_lifetime_impressions");
		echo '<br/> ---- Cache Lifetime Impression Update --- <br/>';
		echo $caching_response;
	}

		public function get_partner_palettes()
		{
			if($_SERVER['REQUEST_METHOD'] == 'POST')
			{
				$return_array = array();
				$allowed_roles = array('sales', 'admin', 'ops');
				$post_variables = array('q', 'page_limit', 'page');
				$response = vl_verify_ajax_call($allowed_roles, $post_variables);

				if($response['is_success'])
				{
					$parameters = array();
					$add_args = array();
					$parameters['q'] = $this->input->post('q');
					$parameters['page_limit'] = $this->input->post('page_limit');
					$parameters['page'] = $this->input->post('page');
					$return_array = select2_helper($this->partners_model, 'get_select2_partner_palette_data', $parameters, $add_args);
					
				}
				else
				{
					$return_array['errors'] = "Not authorized = #903881";
				}
				echo json_encode($return_array);
			}
			else
			{
				show_404();
			}
		}

}