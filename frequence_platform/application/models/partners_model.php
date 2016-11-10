<?php

class Partners_model extends CI_Model
{
        public function __construct()
        {
                $this->load->database();
        }

        public function get_partner_parent_and_other_data($partner_id)
        {
                $query = '
                        SELECT                 	
                                pd.cname AS cname,
                                pd.is_demo_partner AS is_demo_partner,
                                pd2.partner_name AS parent_partner_name
                        FROM
                                wl_partner_details AS pd	
                                LEFT JOIN wl_partner_hierarchy AS ph
                                        ON (ph.descendant_id = pd.id AND pd.id != ph.ancestor_id AND path_length = 1)
                                LEFT JOIN wl_partner_details AS pd2
                                        ON (ph.ancestor_id = pd2.id)
                        WHERE
                                pd.id = ?';
                $response = $this->db->query($query, $partner_id);
                if($response)
                {
                        $raw_partner_data = $response->row_array();
                        return $raw_partner_data;
                }
                return false;
        }       

        public function validate_cname_for_partner($cname, $parent_partner_id, $partner_id = null)
        {	    
		$sql_bindings = array();
                $sql_bindings = array(
			$cname, 
			$parent_partner_id
		);		
                $query = "
                    SELECT                 	
                            id
                    FROM
                            wl_partner_details
                    WHERE
                            cname = ?
                            AND id = ?";		
		
                $result = $this->db->query($query, $sql_bindings);
		if($result->num_rows() == 0)
		{	
			$all_partner_descendants = $this->tank_auth->get_all_descendant_partner_ids($partner_id); 
			foreach ($all_partner_descendants as $key => $value)
			{
				$all_partner_descendants[$key] = $value['descendant_id'];
			}
			$all_partner_descendants[] = $parent_partner_id;

			if (!empty($partner_id))
			{
				$all_partner_descendants[] = $partner_id;
			}
			$sql_bindings_match = array_merge(
				array($cname),
				$all_partner_descendants
			);
			$in_string = rtrim(str_repeat('?,', count($all_partner_descendants)), ',');
			$query_check = "
			    SELECT                 	
				    id
			    FROM
				    wl_partner_details
			    WHERE
				    cname = ?
				    AND id NOT IN ({$in_string})";		

			$result_match = $this->db->query($query_check, $sql_bindings_match);
			if($result_match->num_rows() > 0)
			{
				return false;
			}
			else
			{
				return true;
			}		    
		}
		else
		{
			return true;	
		}
        }
        
        public function validate_unique_partner($partner_name, $cname, $partner_id = null)
        {
                $sql_bindings = array();
                $sql_bindings = array(
                        $partner_name,
                        $cname
                );

                // check at the time of edit
                $in_string = '';
                if (!empty($partner_id))
                {
                        $sql_bindings[] = $partner_id;
                        $in_string = ' AND id != ?';
                }

                $query = "
                    SELECT                 	
                            id
                    FROM
                            wl_partner_details
                    WHERE
                            partner_name = ?
                            AND cname = ?
                            {$in_string}";
                $result = $this->db->query($query, $sql_bindings);
                return $result->num_rows() > 0;
        }

        public function save_partner_details($data, $partner_id = '')
        {
                if (empty($partner_id))
                {
			if ($data['is_demo_partner'] == 1)
			{			
			    $cname_demo = strtolower($data['cname'])."-demo";
			}
			else
			{
			    $cname_demo = strtolower($data['cname']);
			}
                        $ad_choices_tag = '<span id="te-clearads-js-brandcdn01cont||"><script type="text/javascript" src="//choices.truste.com/ca?pid=brandcdn01&aid=brandcdn01&cid=0701&c=brandcdn01cont';
                        $sql_bindings = array(            
                                $cname_demo,            
                                $ad_choices_tag,
                                $data['is_demo_partner'],
                                $this->tank_auth->get_user_id(),
                                date("Y-m-d H:i:s")
                        );
                }

                $sql_bindings[] = $data['partner_name'];
                $sql_bindings[] = $data['home_url'];
				$sql_bindings[] = $data['partner_palette_id'];

                if (!empty($partner_id))
                {
                        $sql_bindings[] = $partner_id;
                        $query = "
                                UPDATE 
                                        wl_partner_details 
                                SET
                                        partner_name = ?,
                                        home_url = ?,
										proposal_palettes_id = ?
                                WHERE
                                        id = ?";
                }
                else
                {
                    $query = "
                            INSERT INTO 
                                    wl_partner_details 
                                            (
                                                    cname,
                                                    ad_choices_tag,
                                                    is_demo_partner,
                                                    created_by,
                                                    created_date,
                                                    partner_name,
                                                    home_url,
													proposal_palettes_id
                                            )
                            VALUES
                                    (?, ?, ?, ?, ?, ?, ?, ?)";
                }
                $result = $this->db->query($query, $sql_bindings);

                if (empty($partner_id))
                {
                        $partner_id = $this->db->insert_id();
                        if ($data['is_demo_partner'] == 1 && !empty($partner_id))
                        {
                                // Demo partner start here
			    //demo sales and client creation				                 
			    $this->demo_partner_model->create_demo_user($partner_id,$cname_demo, $data['partner_user_email'] ,$data['advertiser_email']);
							//insert tv zone relationship for demo
							$this->create_tv_zone_relationship_for_demo_partner($partner_id);
    
                        }
                }
                return $partner_id;
        }

        public function update_partners_image_paths($data, $partner_id)
        {        
                $sql_bindings = array();
                if (!empty($data['login']))
                {
                        $in_string_array[] = 'partner_report_logo_filepath = ?';
                        $sql_bindings[] = $data['login'];
                }
                if (!empty($data['header']))
                {
                        $in_string_array[] = 'header_img = ?';
                        $sql_bindings[] = $data['header'];
                }
                if (!empty($data['favicon']))
                {
                        $in_string_array[] = 'favicon_path = ?';
                        $sql_bindings[] = $data['favicon'];
                }

                $sql_bindings[] = $partner_id;

                $in_string = implode(',', $in_string_array);

                $query = "
                        UPDATE
                                wl_partner_details 
                        SET
                                {$in_string}                    
                        WHERE
                                id = ?";
                $result = $this->db->query($query, $sql_bindings);
                return false;
        }

        public function save_partner_hierarchy($parent_partner_id, $new_partner_id)
        {
                $query = '
                        SELECT                 	
                                ancestor_id,
                                path_length
                        FROM
                                wl_partner_hierarchy
                        WHERE
                                descendant_id = ?';
                $response = $this->db->query($query, $parent_partner_id);
                if($response)
                {
                        $raw_data = $response->result_array();

                        $data_count = count($raw_data);
                        // For self
                        $raw_data[$data_count]['ancestor_id'] = $new_partner_id;           
                        $raw_data[$data_count]['path_length'] = -1;

                        foreach ($raw_data as $hierarchy_data)
                        {                
                                $sql_hierarchy_bindings = array(
                                        $hierarchy_data['ancestor_id'],
                                        $new_partner_id,
                                        $hierarchy_data['path_length']+1
                                );

                                // In wl_partner_hierarchy table
                                $query_hierarchy = "
                                        INSERT INTO 
                                                wl_partner_hierarchy  
                                                        (
                                                                ancestor_id,
                                                                descendant_id,
                                                                path_length
                                                        )
                                        VALUES
                                                (?, ?, ?)";
                                $result = $this->db->query($query_hierarchy, $sql_hierarchy_bindings);
                        }
                }
                return false;
        }

        public function get_features($role, $partner_id = null)
        {
                $features_data = array();
                $sub_sql = 'WHERE access_flag != "NONE"';                

                if ($role == 'admin')
                {
                        $sub_sql .= ' AND (access_flag = "ADMIN" OR access_flag = "PARTNERS")';
                }
                else if ($role == 'sales')
                {
                        $sub_sql .= ' AND access_flag = "PARTNERS"';
                }

                $sql = "SELECT 
                                id,
                                html_id,
                                name,
                                feature_description
                        FROM 
                                pl_features {$sub_sql}                
                        ORDER BY
                                name";
                $query = $this->db->query($sql);
                if($query->num_rows() > 0)
                {
                        $features_data = $query->result_array();
                }

                $default_features[] = null;
                if (!empty($partner_id))
                {
                        $default_features[] = $partner_id;
                }

                foreach ($features_data as $feature_key => $feature_value)
                {
                        $features_data[$feature_key]['checked'] = 1;
                        // get default selected
                        foreach ($default_features as $default)
                        {
                                $checked = 1;
                                $is_super = 0;
                                $is_non_super = 0;
                                $partner_features = $this->get_partner_features($default);

                                if (!empty($partner_features))
                                {
                                        foreach ($partner_features as $key => $partner_feature)
                                        {
                                                if ($feature_value['id'] == $partner_feature['feature_id'])
                                                {
                                                        if (!empty($partner_feature['is_super']))
                                                        {
                                                                $is_super = 1;
                                                        }

                                                        if (empty($partner_feature['is_super']))
                                                        {
                                                                $is_non_super = 1;
                                                        }

                                                        if ($partner_feature['has_access'] == 0)
                                                        {
                                                                $is_super = 0;
                                                                $is_non_super = 0;
                                                                $checked = 1;
                                                        }

                                                        // If both or only sales their, check 'all'
                                                        if (($is_super == 1 && $is_non_super == 1) || ($is_super == 0 && $is_non_super == 1))
                                                        {
                                                                $checked = 2;
                                                        }
                                                        else if ($is_super == 1 && $is_non_super == 0)
                                                        {
                                                                $checked = 3;
                                                        }

                                                        $features_data[$feature_key]['checked'] = $checked;
                                                }
                                        }
                                }

                        }
                }        

                return $features_data;
        }

        public function get_partner_features($partner_id = null, $feature_id = null, $is_super = null, $no_access = null)
        {
                $sub_sql = '';
                $sql_bindings = array();
                if ($is_super == 1) // super
                {
                        $sub_sql .= ' AND pg.is_group_super = 1';
                }
                else if ($is_super == 2) // for non-super
                {
                        $sub_sql .= ' AND pg.is_group_super IS NULL';
                }

                if (!empty($partner_id))
                {
                        $sub_sql .= ' AND pg.partner_id = ?';
                        $sql_bindings[] = $partner_id;
                }
                else
                {
                        $sub_sql .= ' AND pg.partner_id IS NULL';
                }

                if (!empty($feature_id))
                {
                        $sub_sql .= ' AND fp.feature_id = ?';
                        $sql_bindings[] = $feature_id;
                }

                $sql = "SELECT
                                fp.feature_id AS feature_id,
                                pg.is_group_super AS is_super,
                                pg.partner_id AS partner_id,
                                fp.has_access AS has_access
                        FROM 
                                pl_feature_permissions  AS fp
                                LEFT JOIN pl_permission_groups AS pg
                                        ON fp.permission_group_id = pg.id
                        WHERE 
                                pg.role = 'SALES'
                                AND user_id IS NULL {$sub_sql}
                        ORDER BY
                                fp.feature_id";
                $query = $this->db->query($sql, $sql_bindings);
                $num_rows = $query->num_rows();
                if (!empty($feature_id))
                {
                        return ($num_rows > 0) ? $num_rows : 0;
                }

                if($query->num_rows() > 0)
                {
                        return $query->result_array();
                }
                return NULL;
        }

        public function save_partner_features_permissions($partner_id, $role)
        {
                $features_listing = $this->get_features($role, $partner_id);
                foreach ($features_listing as $key => $feature)
                {
                        $post_feature_id = $this->input->post('features_'.$feature['id']);
                        if ($post_feature_id != $feature['checked'])
                        {
                                $record_cnt = 1;
                                if ($post_feature_id == 2) //For All
                                {
                                        $record_cnt = 2;
                                        $group_super = array(1, null);
                                }

                                $counter = 1;
                                for ($i = 0; $i < $record_cnt; $i++)
                                {
                                        $is_group_super = 2; // For non-super
                                        $group_super = array(null);

                                        if ($post_feature_id == 3 || $counter == 2) //For super
                                        {
                                                $is_group_super = 1;
                                                $group_super = array(1);
                                        }

                                        $no_access = 1;
                                        if ($post_feature_id == 1) // For none
                                        {
                                                $no_access = 0;
                                                $group_super = array(1, null);
                                        }

                                        $this->insert_partner_features($partner_id, $feature['id'], $group_super, $no_access);

                                        $counter++;
                                }
                        }
                }

                return true;
        }

        public function insert_partner_features($partner_id, $feature_id, $group_super, $has_access)
        {        
                foreach ($group_super as $key => $super_value)
                {
                        $permission_group_id = $this->vl_platform_model->get_partner_level_permission_group_id($partner_id, 'SALES', $super_value);
                        if (!$permission_group_id)
                        {
                                $sql_group_bindings = array(
                                                                'SALES',
                                                                $super_value,
                                                                $partner_id
                                                            );

                                // In pl_permission_groups table
                                $query_group = "
                                        INSERT INTO 
                                                pl_permission_groups  
                                                        (
                                                                role,
                                                                is_group_super,
                                                                partner_id
                                                        )
                                        VALUES
                                                (?, ?, ?)";
                                $result_group = $this->db->query($query_group, $sql_group_bindings);
                                $permission_group_id = $this->db->insert_id();
                        }

                        $feature_permission = $this->vl_platform_model->get_feature_permission($permission_group_id, $feature_id);
                        if (!isset($feature_permission))
                        {
                                $this->vl_platform_model->create_feature_permission($permission_group_id, $feature_id, $has_access);
                        }
                }

                return true;
        }

        public function delete_partner_details_on_update($role, $partner_id, $parent_partner_id)
        {
                // empty for normal user and 1 for super user section
                $user_type_array = array('', 1);
                foreach ($user_type_array as $type)
                {
                        // Delete groups and features
                        $permission_group_id = $this->vl_platform_model->get_partner_level_permission_group_id($partner_id, 'SALES', $type);

                        if (!empty($permission_group_id))
                        {
                                $features_listing = $this->get_features($role, $partner_id);
                                foreach ($features_listing as $key => $feature)
                                {
                                        $post_feature_id = $this->input->post('features_'.$feature['id']);
                                        if (!empty($post_feature_id))
                                        {
                                                // For features
                                                $query_features = "
                                                        DELETE FROM
                                                                pl_feature_permissions
                                                        WHERE
                                                                permission_group_id = ?
                                                                AND feature_id = ?
                                                        ";
                                                $this->db->query($query_features, array($permission_group_id, $feature['id']));
                                        }
                                }
                        }
                }
 
                // Check if hierarchy is changed or not
                if ($this->partners_model->get_parent_partner_id($partner_id) != $parent_partner_id)
                {
                        // For partner hierarchy
                        $query_group = "
                                DELETE FROM
                                        wl_partner_hierarchy
                                WHERE
                                        descendant_id = ?";
                        $this->db->query($query_group, $partner_id);
                }
                return false;
        }

        public function get_parent_partner_id($partner_id)
        {
                $query = '
                        SELECT                 	
                                ph.ancestor_id AS parent_partner_id
                        FROM
                                wl_partner_hierarchy AS ph
                        WHERE
                                ph.path_length = 1
                                AND ph.descendant_id = ?';
                $response = $this->db->query($query, $partner_id);
                if(!empty($response) && !empty($response->row()->parent_partner_id))
                {
                        return $response->row()->parent_partner_id;
                }
                return false;
        }

        public function validate_images($form_filename, $file_type)
        {
                $allowed_file_ext =  array('jpg', 'jpeg', 'png', 'gif');        
                $filename = $_FILES[$form_filename]['name'];
                $tmp_file_name = $_FILES[$form_filename]['tmp_name'];

                if ($file_type == 1)
                {
                        $image_name = 'Login';
                }
                else if ($file_type == 2)
                {
                        $image_name = 'Header';
                }
                else if ($file_type == 3)
                {
                        $image_name = 'Favicon';
                        $allowed_file_ext =  array('ico');
                }

                $allowed_files_ext_string =  implode(', ', $allowed_file_ext);

                $error_message = '';
                if($filename)
                {               
                        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                        if(!in_array($ext, $allowed_file_ext))
                        {
                                $error_message = 'Please upload only '.$allowed_files_ext_string.' file for '.$image_name.' Image';
                        }
                        else
                        {
                                list($width, $height, $type, $attr) = getimagesize($tmp_file_name); 
                                if($width > 500 || $height > 500) 
                                {
                                        $error_message = 'Please upload '.$image_name.' Image smaller than 500 X 500';
                                }
                        }
                }
                else if ($file_type != 3)
                {
                        $error_message = 'Please upload '.$image_name.' Image';
                }

                return $error_message;
        }

        public function upload_partner_images($form_filename, $new_filename, $path)
        {
                $error_message = '';        

                $file_object = array(
                        'name'	=>	$path.$new_filename,
                        'tmp'	=>	$_FILES[$form_filename]['tmp_name'],
                        'size'	=>	$_FILES[$form_filename]['size'],
                        'type'	=>	$_FILES[$form_filename]['type']
                );

                try
                {
                        $this->vl_aws_services->upload_file($file_object, S3_PARTNERS_BUCKET, true);
                        $result['is_success'] = true;
                        $result['filename'] = $new_filename;
                }
                catch (Exception $e)
                {
                        $result['is_success'] = false;
                        $result['error'] = $e->getMessage();
                }        

                return $result;
        }

        public function ban_partner_users($partner_id)
        {
                $all_partner_descendants = $this->tank_auth->get_all_descendant_partner_ids($partner_id);

                foreach ($all_partner_descendants as $key => $value)
                {
                        $all_partner_descendants[$key] = $value['descendant_id'];
                }      
                $all_partner_descendants[] = $partner_id;

                $sql_bindings = array();
                $sql_bindings = $all_partner_descendants;

                $in_string = rtrim(str_repeat('?,', count($all_partner_descendants)), ',');

                $query = "
                        UPDATE 
                                users
                        SET 
                                banned = 1 
                        WHERE
                                (role = 'SALES'
                                OR role = 'BUSINESS' 
                                OR role = 'CLIENT'
                                OR role = 'AGENCY')
                                AND partner_id IN ({$in_string})";
                $result = $this->db->query($query, $sql_bindings);

                return true;
        }
        
        public function get_partner_users($partner_id)
        {
                $all_partner_descendants = $this->tank_auth->get_all_descendant_partner_ids($partner_id);

                foreach ($all_partner_descendants as $key => $value)
                {
                        $all_partner_descendants[$key] = $value['descendant_id'];
                }      
                $all_partner_descendants[] = $partner_id;

                $sql_bindings = array();
                $sql_bindings = $all_partner_descendants;

                $in_string = rtrim(str_repeat('?,', count($all_partner_descendants)), ',');

                $query = "
                        SELECT 
                                id
                        FROM 
                                users
                        WHERE
                                (role = 'SALES'
                                OR role = 'BUSINESS' 
                                OR role = 'CLIENT'
                                OR role = 'AGENCY')
                                AND banned = 0
                                AND partner_id IN ({$in_string})";
                $result = $this->db->query($query, $sql_bindings);

                return ($result->num_rows() > 0) ? $result->num_rows() : 0;
        }
		
		public function get_select2_partner_palette_data($term, $start, $limit, $id = false)
		{
			if($id !== false)
			{
				$tail_sql = "WHERE id = ?";
				$bindings = array($id);
			}
			else
			{
				$tail_sql = "
					WHERE
						1
					LIMIT ?, ?";
				$bindings = array($start, $limit);
			}
			$query = '
				SELECT
					*,
				    CONCAT("Palette ", id) AS text
				FROM
					proposal_palettes
			    '.$tail_sql;
			$response = $this->db->query($query, $bindings);
			if($response->num_rows() > 0)
			{
				if($id !== false)
				{
					return $response->row_array();
				}
				else
				{
					return $response->result_array();
				}
			}
			return array();
		}

		public function create_tv_zone_relationship_for_demo_partner($partner_id)
		{
			$query = "
				INSERT INTO 
					geo_regions_collections_partner
					(partner_id, geo_regions_collection_id, regions_collection_type)
				SELECT 
					?, 
					geo_regions_collection_id, 
					regions_collection_type
				FROM
					geo_regions_collections_partner
				WHERE
					partner_id = 5 AND
					regions_collection_type = 1";
			$response = $this->db->query($query, $partner_id);
			if($response && $this->db->affected_rows() > 0)
			{
				return true;
			}
			return false;
		}

}