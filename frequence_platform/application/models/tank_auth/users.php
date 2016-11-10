<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Users
 *
 * This model represents user authentication data. It operates the following tables:
 * - user account data,
 * - user profiles
 *
 * @package	Tank_auth
 * @author	Ilya Konyukhov (http://konyukhov.com/soft/)
 */
class Users extends CI_Model
{
	private $table_name			= 'users';			// user accounts
	private $profile_table_name	= 'user_profiles';	// user profiles

	function __construct()
	{
		parent::__construct();

		$ci =& get_instance();
		$this->table_name			= $ci->config->item('db_table_prefix', 'tank_auth').$this->table_name;
		$this->profile_table_name	= $ci->config->item('db_table_prefix', 'tank_auth').$this->profile_table_name;
	}

/*
Determines the orphan sites that do not have demographic data from siteRecords and inserts them into DemographicRecords_01_20_2012
when this function is called it should be passed an array that includes all the attributes of the demographic table.
Most of the time there will be no AdGroupID but the attribute cannot be NULL in the database, leave it as a single space.
Domain is generated here from the siterecords table so that variable does not need to have any relevant data either.
*/
	function insert_demos_from_sites($array)			
	{
		$demo_array = $array;
		$insert_count = 0;
		$missing_sites = "
		SELECT DISTINCT 
		CASE 
		WHEN Site LIKE '%:%' THEN SUBSTRING(Site,1,INSTR(Site,':')-1)
		WHEN Site LIKE '%/%' THEN SUBSTRING(Site,1,INSTR(Site,'/')-1)
		ELSE Site
		END
		AS site
		FROM SiteRecords
		WHERE site NOT IN (SELECT DISTINCT TRIM(Domain) FROM DemographicRecords_01_20_2012)
		ORDER BY site";
		
		$insert_demos = "
		INSERT INTO DemographicRecords_01_20_2012 
		(Domain, Reach, Gender_Male, Gender_Female, 
		Age_Under18, Age_18_24, Age_25_34, Age_35_44, Age_45_54, Age_55_64, Age_65, 
		Race_Cauc, Race_Afr_Am, Race_Asian, Race_Hisp, Race_Other, 
		Kids_No, Kids_Yes, Income_0_50, Income_50_100, Income_100_150, Income_150, 
		College_No, College_Under, College_Grad, AdGroupID)
		VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		
		$sites_result = $this->db->query($missing_sites);
		
		$row_count = $sites_result->num_rows();
		
		for($i = 0; $i < $row_count; $i++)
		{
		$insert_count++;
		$demo_array['Domain'] = $sites_result->row($i)->site;
		
		$demo_result = $this->db->query($insert_demos, $demo_array);
		}
		return $insert_count;
		
	}
	function get_bad_demo_percentages()
	{
		$query = "
			SELECT Domain, SUM(Gender_Male, Gender_Female) Gender, 
			SUM(Age_Under18, Age_18_24, Age_25_34, Age_35_44, Age_45_54, Age_55_64, Age_65) Age, 
			SUM(Race_Cauc, Race_Afr_Am, Race_Asian, Race_Hisp, Race_Other) Race, 
			SUM(Kids_No, Kids_Yes) Kids, 
			SUM(Income_0_50, Income_50_100, Income_100_150, Income_150) Income, 
			SUMCollege_No, College_Under, College_Grad) College
			FROM DemographicRecords_01_20_2012
			ORDER BY Domain";
		$result = $this->db->query($query);
		
		return $result;
	}
	
	
		/**
	 * Get user record by Id
	 *
	 * @param	int
	 * @param	bool
	 * @return	object
	 */
	function get_planner_viewable($user_id)
	{
	  $sql = 'SELECT planner_viewable FROM users where id = ?';
	  $query = $this->db->query($sql, $user_id);
	  if($query->num_rows() > 0)
	    {
	      $temp = $query->row_array();
	      return $temp['planner_viewable'];
	    }
	  return 0;
	}
	function get_partner_details($partner_id)
	{
		$sql = 'SELECT * FROM wl_partner_details WHERE id = ?';
		$query = $this->db->query($sql, $partner_id);
		if($query->num_rows() > 0)
		{
		  return $query->row_array();
		}
	 	return NULL;
	}
	function get_partner_info_by_role_and_ids($role, $user_id, $advertiser_id = null)
	{
		$result = null;

		if(0 === strcasecmp($role, 'business'))
		{
			$sql = '
				SELECT 
					wpd.*
				FROM
					wl_partner_details AS wpd
					JOIN users AS us_sales 
						ON (wpd.id = us_sales.partner_id)
					JOIN Advertisers AS adv
						ON (us_sales.id = adv.sales_person)
				WHERE
					adv.id = ?
			';

			$response = $this->db->query($sql, $advertiser_id);
			if($response->num_rows() > 0)
			{
				$result = $response->row_array();
			}
		}
		else
		{
			$sql = '
				SELECT 
					wpd.*
				FROM
					wl_partner_details AS wpd
					JOIN users AS us
						ON (wpd.id = us.partner_id)
				WHERE
					us.id = ?
			';

			$response = $this->db->query($sql, $user_id);
			if($response->num_rows() > 0)
			{
				$result = $response->row_array();
			}
		}

		return $result;
	}

	function get_partner_id_by_business_id($business_id)
	{
	  $sql = 'SELECT a.partner_id FROM users a JOIN Advertisers b ON a.id = b.sales_person JOIN wl_partner_details c ON a.partner_id = c.id WHERE b.id = ?';
	  $query = $this->db->query($sql, $business_id);
	  if($query->num_rows() > 0)
	    {
	      $temp = $query->row_array();
	      return $temp['partner_id'];
	    }
	  return NULL;
	}
	function get_partner_id_by_business_name($business_name)
	{
	  $sql = 'SELECT a.partner_id FROM users a JOIN Advertisers b ON a.id = b.sales_person JOIN wl_partner_details c ON a.partner_id = c.id WHERE b.Name = ?';
	  $query = $this->db->query($sql, $business_name);
	  if($query->num_rows() > 0)
	    {
	      $temp = $query->row_array();
	      return $temp['partner_id'];
	    }
	  return NULL;
	}
	function get_partner_id_by_user_id($user_id)
	{
	  $sql = 'SELECT partner_id FROM users WHERE id = ?';
	  $query = $this->db->query($sql, $user_id);
	  if($query->num_rows() > 0)
	    {
	      $temp = $query->row_array();
	      return $temp['partner_id'];
	    }
	  return NULL;
	}
	function get_businesses_by_salesperson($user_id)
	{
	  //$sql = 'SELECT DISTINCT a.Name FROM Advertisers a JOIN users b ON a.sales_person = b.id WHERE b.id = ?';
	  $sql = 'SELECT Name, id FROM Advertisers WHERE sales_person = ? ORDER BY Name ASC';
	  $query = $this->db->query($sql, $user_id);
	  if($query->num_rows() > 0)
	    {
	      return $query->result_array();
	    }
	  return NULL;
	}
	function get_salesperson_by_business_id($business_id)
	{
	  $sql = 'SELECT sales_person FROM Advertisers WHERE id = ? LIMIT 1';
	  $query = $this->db->query($sql, $business_id);
	  if($query->num_rows() > 0)
	    {
	      $temp = $query->row_array();
	      return $temp['sales_person'];
	    }
	  return NULL;
	}
	function get_salesperson_by_business_name($business)
	{
	  $sql = 'SELECT sales_person FROM Advertisers WHERE Name = ? LIMIT 1';
	  $query = $this->db->query($sql, $business);
	  if($query->num_rows() > 0)
	    {
	      $temp = $query->row_array();
	      return $temp['sales_person'];
	    }
	  return NULL;
	}
	function get_all_sales_users()
	{
	  $sql = 'SELECT id, CONCAT(firstname, " ", lastname) as username FROM users WHERE role = "SALES" ORDER BY username ASC';
	  $query = $this->db->query($sql);
	  if($query->num_rows() > 0)
	    {
	      return $query->result_array();
	    }
	  return NULL;
	}
	function get_all_partners()
	{
	  $sql = 'SELECT id, partner_name FROM wl_partner_details WHERE 1';
	  $query = $this->db->query($sql);
	  if($query->num_rows() > 0)
	    {
	      return $query->result_array();
	    }
	  return NULL;
	}
	function get_partner_id_by_unique_id($partner_unique_id)
	{
	  $sql = 'SELECT id FROM wl_partner_details WHERE unique_id = ?';
	  $query = $this->db->query($sql, $partner_unique_id);
	  if($query->num_rows() > 0)
	    {
	      $temp = $query->row_array();
	      return $temp['id'];
	    }
	  return NULL;
	}
	function get_businesses_by_partner($partner_id)
	{
	  $sql = 'SELECT a.Name AS Name, a.id AS id FROM Advertisers a JOIN users b ON a.sales_person = b.id WHERE b.partner_id = ? ORDER BY Name ASC';
	  $query = $this->db->query($sql, $partner_id);
	  if($query->num_rows() > 0)
	    {
	      return $query->result_array();
	    }
	  return NULL;
	}
	function get_user_by_id($user_id, $activated)
	{
		$this->db->where('id', $user_id);
		$this->db->where('activated', $activated ? 1 : 0);

		$query = $this->db->get($this->table_name);
		if ($query->num_rows() == 1) return $query->row();
		return NULL;
	}

	/**
	 * Get user record by login (username or email)
	 *
	 * @param	string
	 * @return	object
	 */
	function get_user_by_login($login)
	{
		$this->db->where('LOWER(username)=', strtolower($login));
		$this->db->or_where('LOWER(email)=', strtolower($login));

		$query = $this->db->get($this->table_name);
		if ($query->num_rows() == 1) return $query->row();
		return NULL;
	}

	/**
	 * Get user record by username
	 *
	 * @param	string
	 * @return	object
	 */
	 
	function get_business_option_array()
	{
		$sql = "SELECT `id`,`Name` FROM `Advertisers` ORDER BY Name ASC";
		$query = $this->db->query($sql);
		if($query->num_rows() >= 1)
		{
			$temp = $query->result_array();	
			return $temp;
		}
		return NULL;
	}
	function get_business_id_by_username($username)  //function by MATT!!!: business_id
	{
		$sql = "SELECT a.id AS id FROM `users` AS u JOIN Advertisers AS a ON (u.advertiser_id = a.id) WHERE u.`username` = ? LIMIT 1";
		$query = $this->db->query($sql, $username);
		if($query->num_rows() == 1)
		{
			$temp = $query->row_array();
			return $temp['id'];
		}
		return NULL;
	}
	function get_business_name_by_username($username)  //function by MATT!!!: business_name
	{
		$sql = "SELECT a.Name AS business_name FROM `users` AS u JOIN Advertisers AS a ON (u.advertiser_id = a.id) WHERE u.`username` = ? LIMIT 1";
		$query = $this->db->query($sql, $username);
		if($query->num_rows() == 1)
			{
			$temp = $query->row_array();
			return $temp['business_name'];
			}
		return NULL;
	}

	function get_role_by_username($username)  //function by WILL: role
	{
		$sql = "SELECT `role` FROM `users` WHERE `username` = ? LIMIT 1";
		$query = $this->db->query($sql, $username);
		if($query->num_rows() == 1)
			{
			$temp = $query->row_array();
			return strtolower($temp['role']);
			}
		return NULL;
	}
	function get_role_by_user_id($user_id)  //function by WILL: role
	{
		$sql = "SELECT `role` FROM `users` WHERE `id` = ? LIMIT 1";
		$query = $this->db->query($sql, $user_id);
		if($query->num_rows() == 1)
			{
			$temp = $query->row_array();
			return strtolower($temp['role']);
			}
		return NULL;
	}
	function get_email_by_username($username)  //function by WILL: email
	{
		$sql = "SELECT `email` FROM `users` WHERE `username` = ? LIMIT 1";
		$query = $this->db->query($sql, $username);
		if($query->num_rows() == 1)
			{
			$temp = $query->row_array();
			return strtolower($temp['email']);
			}
		return NULL;
	}
	function get_email_by_user_id($user_id)  //function by WILL: email
	{
		$sql = "SELECT `email` FROM `users` WHERE `id` = ? LIMIT 1";
		$query = $this->db->query($sql, $user_id);
		
		if ($query->num_rows() == 1)
		{
			$result = $query->row_array();
			return strtolower($result['email']);
		}
		
		return NULL;
	}
	function get_firstname_by_username($username)  //function by WILL: firstname
	{
		$sql = "SELECT `firstname` FROM `users` WHERE `username` = ? LIMIT 1";
		$query = $this->db->query($sql, $username);
		if($query->num_rows() == 1)
			{
			$temp = $query->row_array();
			return strtolower($temp['firstname']);
			}
		return NULL;
	}
	function get_lastname_by_username($username)  //function by WILL: lastname
	{
		$sql = "SELECT `lastname` FROM `users` WHERE `username` = ? LIMIT 1";
		$query = $this->db->query($sql, $username);
		if($query->num_rows() == 1)
			{
			$temp = $query->row_array();
			return strtolower($temp['lastname']);
			}
		return NULL;
	}
	function get_bgroup_by_username($username)  //function by WILL: bgroup
	{
		$sql = "SELECT `bgroup` FROM `users` WHERE `username` = ? LIMIT 1";
		$query = $this->db->query($sql, $username);
		if($query->num_rows() == 1)
			{
			$temp = $query->row_array();
			return strtolower($temp['bgroup']);
			}
		return NULL;
	}
	function get_isGroupSuper_by_username($username)  //function by WILL: isGroupSuper
	{
		$sql = "SELECT `isGroupSuper` FROM `users` WHERE `username` = ? LIMIT 1";
		$query = $this->db->query($sql, $username);
		if($query->num_rows() == 1)
			{
			$temp = $query->row_array();
			return strtolower($temp['isGroupSuper']);
			}
		return NULL;
	}
	function get_isGlobalSuper_by_username($username)  //function by WILL: isGlobalSuper
	{
		$sql = "SELECT `isGlobalSuper` FROM `users` WHERE `username` = ? LIMIT 1";
		$query = $this->db->query($sql, $username);
		if($query->num_rows() == 1)
			{
			$temp = $query->row_array();
			return strtolower($temp['isGlobalSuper']);
			}
		return NULL;
	}
        
	function get_placements_viewable_by_username($username)
	{
	    	$sql = "SELECT `placements_viewable` FROM `users` WHERE `username` = ? LIMIT 1";
		$query = $this->db->query($sql, $username);
		if($query->num_rows() == 1)
		{
			$temp = $query->row_array();
			return $temp['placements_viewable'];
		}
		return 0;  
	}
	
	function get_ad_sizes_viewable_by_id($user_id)
	{
	    	$sql = "SELECT `ad_sizes_viewable` FROM `users` WHERE `id` = ? LIMIT 1";
		$query = $this->db->query($sql, $user_id);
		if($query->num_rows() == 1)
		{
			$temp = $query->row_array();
			return $temp['ad_sizes_viewable'];
		}
		return 0; 
	}
	
	function get_beta_report_engagements_viewable_by_username($username)
	{
		$sql = "SELECT `beta_report_engagements_viewable` FROM `users` WHERE `username` = ? LIMIT 1";
		$query = $this->db->query($sql, $username);
		if($query->num_rows() == 1)
		{
			$temp = $query->row_array();
			return $temp['beta_report_engagements_viewable'];
		}
		return 0;            
	}
        
	function get_screenshots_viewable_by_username($username)
	{
		$sql = "SELECT `screenshots_viewable` FROM `users` WHERE `username` = ? LIMIT 1";
		$query = $this->db->query($sql, $username);
		if($query->num_rows() == 1)
		{
			$temp = $query->row_array();
			return strtolower($temp['screenshots_viewable']);
		}
		return NULL;            
	}
	
	function get_campaigns_by_business_id($business_id)
	{
		$sql = "SELECT `Name`, id FROM `Campaigns` WHERE `business_id` = ? ORDER BY Name ASC";
		$query = $this->db->query($sql, $business_id);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return NULL;
	}
	function get_campaigns_by_campaign_id($c_id)
	{
		$sql = "SELECT `vl_id`, ID FROM `AdGroups` WHERE `campaign_id` = ? ORDER BY id DESC";
		$query = $this->db->query($sql, $c_id);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return NULL;
	}
	function get_campaigns_by_business_name($business_name)
	{
		$sql = "SELECT `Name`, id FROM `Campaigns` WHERE `Business` = ? ORDER BY Name ASC";
		$query = $this->db->query($sql, $business_name);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return NULL;
	}
        
        
        
	function get_campaign_details_by_id($campaign_id)
	{
	  $sql = "SELECT * FROM Campaigns WHERE id = ?";
	  $query = $this->db->query($sql, $campaign_id);
	  if($query->num_rows() > 0)
		{
			return $query->row_array();
		}
	  return NULL;
	}
	function get_campaign_details($campaign_name, $business_id)
	{
		$bindings = array($campaign_name, $business_id);
	  $sql = "SELECT * FROM Campaigns WHERE Name = ? AND business_id = ?";
	  $query = $this->db->query($sql, $bindings);
	  if($query->num_rows() > 0)
		{
			return $query->row_array();
		}
	  return NULL;
	}
	function get_business_by_campaign_name($campaign_name)
	{
	  $sql = "SELECT DISTINCT Business FROM Campaigns WHERE Name = ?";
	  $query = $this->db->query($sql, $campaign_name);
	  if($query->num_rows() > 0)
	    {
	      $temp = $query->row_array();
	      return $temp['Business'];
	    }
	  return NULL;
	}
	function insert_advertiser($insert_array)
	{
	  $sql = "INSERT IGNORE INTO Advertisers (Name, sales_person) VALUES(?, ?)";
	  $query = $this->db->query($sql, $insert_array);
	  return $this->db->affected_rows();
	}
	function insert_campaign($insert_array)
	{
		$business_id = $insert_array[1];
		$insert_array[] = $business_id;
	  $sql = "INSERT IGNORE INTO Campaigns (Name, business_id, LandingPage, TargetImpressions, hard_end_date, Business) SELECT ?, ?, ?, ?, ?, a.Name FROM Advertisers AS a WHERE a.id = ?";
	  $query = $this->db->query($sql, $insert_array);
	  return $this->db->affected_rows();
	}
	function insert_adgroup($insert_array)
	{
	  $sql = "INSERT IGNORE INTO AdGroups (ID, BusinessName, CampaignName, IsRetargeting, IsDerivedSiteDateRequired, City, Region, Source, campaign_id) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)";
	  $query = $this->db->query($sql, $insert_array);
	  return $this->db->affected_rows();
	}
	function get_all_business_names($search_term = '', $start = 0, $limit = 0)
	{
		$sql = "
			SELECT 
				`Name`, id 
			FROM 
				`Advertisers` ";
		$bindings=array();		
		if ($search_term != null && $search_term != "")
		{
			$sql .= " 
				WHERE 
					name LIKE ?";
			$bindings[]=$search_term;
		}
		$sql .= " 
			ORDER BY Name ASC";

		if ($start > 0 || $limit > 0)	
		{
			$bindings[]=$start;
			$bindings[]=$limit;
			$sql .= " LIMIT ?,? ";
		}			

		$query = $this->db->query($sql, $bindings);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return NULL;
	}
	function get_business_id_from_name($business_name)
	{
		$bindings = array($business_name);
		$sql = "SELECT id FROM Advertisers WHERE name = ?";
		$response = $this->db->query($sql, $bindings);
		if($response->num_rows() > 0)
		{
			$rows = $response->result_array();
			$id = $rows[0]['id'];
			return $id;
		}
		return 0;
	}
	function get_business_name_from_id($business_id)
	{
		$bindings = array($business_id);
		$sql = "SELECT `Name` FROM `Advertisers` WHERE id = ?";
		$response = $this->db->query($sql, $bindings);
		if($response->num_rows() > 0)
		{
			$rows = $response->result_array();
			$name = $rows[0]['Name'];
			return $name;
		}
		return "";
	}
	function get_campaign_name_from_id($campaign_id)
	{
		$bindings = array($campaign_id);
		$sql = "SELECT `Name` FROM `Campaigns` WHERE id = ?";
		$response = $this->db->query($sql, $bindings);
		if($response->num_rows() > 0)
		{
			$rows = $response->result_array();
			$name = $rows[0]['Name'];
			return $name;
		}
		return "";
	}
	function get_business_and_campaign_by_id($campaign_id)
	{
		$bindings = array($campaign_id);
		$sql = "SELECT a.Name AS business_name, a.id AS business_id, c.Name AS campaign_name, c.id AS campaign_id FROM `Advertisers` AS a JOIN Campaigns AS c ON (a.id = c.business_id) WHERE c.id = ?";
		$response = $this->db->query($sql, $bindings);
		$data = array();
		if($response->num_rows() > 0)
		{
			$rows = $response->result_array();
			$data = array(
			'business_name' => $rows[0]['business_name'],
			'business_id' => $rows[0]['business_id'],
			'campaign_name' => $rows[0]['campaign_name'],
			'campaign_id' => $rows[0]['campaign_id']
			);
		}
		else
		{
			$data = array(
			'business_name' => "",
			'business_id' => 0,
			'campaign_name' => "",
			'campaign_id' => 0
			);
		}
		return $data;
	}

	function get_advertisers_by_user_id_for_admin_and_ops($user_id)
	{
		$sql = 
			'
			SELECT 
				pd.partner_name, 
				a.id, 
				a.Name,
				u.partner_id
			FROM 
				`wl_partner_details` pd 
				JOIN `users` u 
					ON pd.id = u.`partner_id` 
				JOIN Advertisers a
					ON u.id = a.sales_person 
			ORDER BY a.Name ASC
			';
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return NULL;
	}

	function get_advertisers_by_user_id_and_is_super($user_id, $is_super)
	{
		$non_super_sales_where_sql = "";
		$binding_array = array($user_id);
		if($is_super == 1)
		{
			$wl_partner_hierarchy_join_sql = "(u.partner_id = h.ancestor_id OR po.partner_id = h.ancestor_id)";
		}
		else
		{
			$wl_partner_hierarchy_join_sql = " po.partner_id = h.ancestor_id";
			
			//May use this later
			$non_super_sales_where_sql = "OR a.sales_person = ?";
			$binding_array[] = $user_id;
		}
		$sql = 
			'
			SELECT DISTINCT
				pd.id AS partner_id,
				pd.partner_name,
				a.id,
				a.Name
			FROM
				users u ';
		if ($is_super == '1') $sql .= 'LEFT ';
		$sql .=	'JOIN wl_partner_owners po
					ON u.id = po.user_id
				JOIN wl_partner_hierarchy h
					ON '.$wl_partner_hierarchy_join_sql.'
				JOIN wl_partner_details pd
					ON h.descendant_id = pd.id
				JOIN users u2 
					ON pd.id = u2.partner_id
				RIGHT JOIN Advertisers a
					ON u2.id = a.sales_person
			WHERE 
				u.id = ? '.$non_super_sales_where_sql.'
			ORDER BY a.Name ASC
			';
		$query = $this->db->query($sql, $binding_array);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return false;
	}

	function is_partner_owner($user_id)
	{
		$sql =
			'
			SELECT DISTINCT 
				* 
			FROM 
				wl_partner_owners po 
			WHERE 
				po.user_id = ' . (string)$user_id;
		$query = $this->db->query($sql);
		if($query->num_rows() > 0) return TRUE;
		return FALSE;
	}

	function get_user_by_username($username)
	{
		$this->db->where('LOWER(username)=', strtolower($username));

		$query = $this->db->get($this->table_name);
		if ($query->num_rows() == 1) return $query->row();
		return NULL;
	}

	function get_user_by_new_password_key_client_agency($new_password_key)
	{
		$new_password_key=strtolower($new_password_key);
		$sql =
			"
				SELECT DISTINCT 
					id, 
					email,
					firstname,
					lastname
				FROM 
					users 
				WHERE 
					LOWER(new_password_key)=? AND
					new_password_requested > DATE_ADD(CURDATE(), INTERVAL -14 DAY) AND
					role in ('CLIENT', 'AGENCY')
			";
			
		$query = $this->db->query($sql, $new_password_key);
		if($query->num_rows() > 0)
		{
			$array_data = $query->result_array();
			return $array_data[0];
		}
		return FALSE;
	}

	/**
	 * Get session by mpq_sessions_and_submissions id
	 */
	function get_session_by_mpq_session_id($mpq_session_id, $session_id)
	{
		// delete previous MPQ sessions if exists
		$sql = 
		'	DELETE IGNORE from mpq_sessions_and_submissions 
			WHERE session_id = ?
			AND NOT id = ?
		';
		$bindings = array($session_id, $mpq_session_id);
		$this->db->query($sql, $bindings);

		// sets existing authorized MPQ session to have PHP session_id of user
		$this->db->where('id =', $mpq_session_id);
		$this->db->update('mpq_sessions_and_submissions', array('session_id' => $session_id));

		$this->db->where('id =', $mpq_session_id);
		$mss_query = $this->db->get('mpq_sessions_and_submissions');
		if ($mss_query->num_rows() > 0)
		{
			return $mss_query->row();
		}
		return false;
	}

	/**
	 * Get user record by email
	 *
	 * @param	string
	 * @return	object
	 */
	function get_user_by_email($email)
	{
		$this->db->where('LOWER(email)=', strtolower($email));

		$query = $this->db->get($this->table_name);
		if ($query->num_rows() == 1) return $query->row();
		return NULL;
	}

	/**
	 * Check if username available for registering
	 *
	 * @param	string
	 * @return	bool
	 */
	function is_username_available($username)
	{
		$this->db->select('1', FALSE);
		$this->db->where('LOWER(username)=', strtolower($username));

		$query = $this->db->get($this->table_name);
		return $query->num_rows() == 0;
	}

	/**
	 * Check if email available for registering
	 *
	 * @param	string
	 * @return	bool
	 */
	function is_email_available($email)
	{
		$this->db->select('1', FALSE);
		$this->db->where('LOWER(email)=', strtolower($email));
		$this->db->or_where('LOWER(new_email)=', strtolower($email));

		$query = $this->db->get($this->table_name);
		return $query->num_rows() == 0;
	}

	/**
	 * Create new user record
	 *
	 * @param	array
	 * @param	bool
	 * @return	array
	 */
	function create_user($data, $activated = TRUE)  //called from Tank_auth->create_user with data array
	{
		$data['created'] = date('Y-m-d H:i:s');
		$data['archived'] = date('Y-m-d H:i:s', strtotime("+6 month"));
		$data['activated'] = $activated ? 1 : 0;

		if ($this->db->insert($this->table_name, $data)) {
			$user_id = $this->db->insert_id();
			if ($activated)	$this->create_profile($user_id, $data);  //call to the create_profile function in this model with the data array
			return array('user_id' => $user_id);
		}
		return NULL;
	}

	/**
	 * Activate user if activation key is valid.
	 * Can be called for not activated users only.
	 *
	 * @param	int
	 * @param	string
	 * @param	bool
	 * @return	bool
	 */
	function activate_user($user_id, $activation_key, $activate_by_email)
	{
		$this->db->select('1', FALSE);
		$this->db->where('id', $user_id);
		if ($activate_by_email) {
			$this->db->where('new_email_key', $activation_key);
		} else {
			$this->db->where('new_password_key', $activation_key);
		}
		$this->db->where('activated', 0);
		$query = $this->db->get($this->table_name);

		if ($query->num_rows() == 1) {

			$this->db->set('activated', 1);
			$this->db->set('new_email_key', NULL);
			$this->db->where('id', $user_id);
			$this->db->update($this->table_name);

			$this->create_profile($user_id);
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Purge table of non-activated users
	 *
	 * @param	int
	 * @return	void
	 */
	function purge_na($expire_period = 172800)
	{
		$this->db->where('activated', 0);
		$this->db->where('UNIX_TIMESTAMP(created) <', time() - $expire_period);
		$this->db->delete($this->table_name);
	}

	/**
	 * Delete user record
	 *
	 * @param	int
	 * @return	bool
	 */
	function delete_user($user_id)
	{
		$this->db->where('id', $user_id);
		$this->db->delete($this->table_name);
		if ($this->db->affected_rows() > 0) {
			$this->delete_profile($user_id);
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Set new password key for user.
	 * This key can be used for authentication when resetting user's password.
	 *
	 * @param	int
	 * @param	string
	 * @return	bool
	 */
	function set_password_key($user_id, $new_pass_key)
	{
		$this->db->set('new_password_key', $new_pass_key);
		$this->db->set('new_password_requested', date('Y-m-d H:i:s'));
		$this->db->where('id', $user_id);

		$this->db->update($this->table_name);
		return $this->db->affected_rows() > 0;
	}

	/**
	 * Check if given password key is valid and user is authenticated.
	 *
	 * @param	int
	 * @param	string
	 * @param	int
	 * @return	void
	 */
	function can_reset_password($user_id, $new_pass_key, $expire_period = 900)
	{
		$this->db->select('1', FALSE);
		$this->db->where('id', $user_id);
		$this->db->where('new_password_key', $new_pass_key);
		$this->db->where('UNIX_TIMESTAMP(new_password_requested) >', time() - $expire_period);

		$query = $this->db->get($this->table_name);
		return $query->num_rows() == 1;
	}

	/**
	 * Change user password if password key is valid and user is authenticated.
	 *
	 * @param	int
	 * @param	string
	 * @param	string
	 * @param	int
         * @param	int
	 * @return	bool
	 */
	function reset_password($user_id, $new_pass, $new_pass_key, $expire_period = 900, $is_registration = 0)
	{
		$this->db->set('password', $new_pass);
		$this->db->set('new_password_key', NULL);
		$this->db->set('new_password_requested', NULL);
                // Set registration date for new user
                if ($is_registration == 1)
                {
                        $this->db->set('registration_date', date('Y-m-d H:i:s'));
                }
		$this->db->where('id', $user_id);
		$this->db->where('new_password_key', $new_pass_key);
		$this->db->where('UNIX_TIMESTAMP(new_password_requested) >=', time() - $expire_period);

		$this->db->update($this->table_name);
		return $this->db->affected_rows() > 0;
	}

	/**
	 * Change user password
	 *
	 * @param	int
	 * @param	string
	 * @return	bool
	 */
	function change_password($user_id, $new_pass)
	{
		$this->db->set('password', $new_pass);
		$this->db->where('id', $user_id);

		$this->db->update($this->table_name);
		return $this->db->affected_rows() > 0;
	}

	/**
	 * Set new email for user (may be activated or not).
	 * The new email cannot be used for login or notification before it is activated.
	 *
	 * @param	int
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @return	bool
	 */
	function set_new_email($user_id, $new_email, $new_email_key, $activated)
	{
		$this->db->set($activated ? 'new_email' : 'email', $new_email);
		$this->db->set('new_email_key', $new_email_key);
		$this->db->where('id', $user_id);
		$this->db->where('activated', $activated ? 1 : 0);

		$this->db->update($this->table_name);
		return $this->db->affected_rows() > 0;
	}

	/**
	 * Activate new email (replace old email with new one) if activation key is valid.
	 *
	 * @param	int
	 * @param	string
	 * @return	bool
	 */
	function activate_new_email($user_id, $new_email_key)
	{
		$this->db->set('email', 'new_email', FALSE);
		$this->db->set('new_email', NULL);
		$this->db->set('new_email_key', NULL);
		$this->db->where('id', $user_id);
		$this->db->where('new_email_key', $new_email_key);

		$this->db->update($this->table_name);
		return $this->db->affected_rows() > 0;
	}

	/**
	 * Update user login info, such as IP-address or login time, and
	 * clear previously generated (but not activated) passwords.
	 *
	 * @param	int
	 * @param	bool
	 * @param	bool
	 * @return	void
	 */
	
	function update_login_info($user_id, $record_ip, $record_time)
	{
		$this->db->set('new_password_key', NULL);
		$this->db->set('new_password_requested', NULL);

		if ($record_ip)		$this->db->set('last_ip', $this->input->ip_address());
		$last_login_date = new DateTime;
		$last_login_date->setTimezone(new DateTimeZone('Etc/UTC'));
		if ($record_time)	$this->db->set('last_login', $last_login_date->format('Y-m-d H:i:s'));

		$this->db->where('id', $user_id);
		$this->db->update($this->table_name);
	}

	function update_first_name_last_name($user_id, $first_name, $last_name)
	{
		$this->db->set('firstname', $first_name);
		$this->db->set('lastname', $last_name);

		$this->db->where('id', $user_id);
		$this->db->update($this->table_name);
	}

	/**
	 * Ban user
	 *
	 * @param	int
	 * @param	string
	 * @return	void
	 */
	function ban_user($user_id, $reason = NULL)
	{
		$this->db->where('id', $user_id);
		$this->db->update($this->table_name, array(
			'banned'		=> 1,
			'ban_reason'	=> $reason,
		));
	}

	/**
	 * Unban user
	 *
	 * @param	int
	 * @return	void
	 */
	function unban_user($user_id)
	{
		$this->db->where('id', $user_id);
		$this->db->update($this->table_name, array(
			'banned'		=> 0,
			'ban_reason'	=> NULL,
		));
	}

	/**
	 * Create an empty profile for a new user
	 *
	 * @param	int
	 * @return	bool
	 */
	private function create_profile($user_id, $data) //called from this->create_user with the data array 
	{
	  //$role = 'BUSINESS';
		$this->db->set('user_id', $user_id);
		//$this->db->set('role', $data['role']);
		//$this->db->set('isGlobalSuper', $data['isGlobalSuper']);
		//$this->db->set('bgroup', $data['bgroup']);
		//$this->db->set('isGroupSuper', $data['isGroupSuper']);
		//$this->db->set('business_name', $data['business_name']);
		//$this->db->set('firstname', $data['firstname']);
		//$this->db->set('lastname', $data['lastname']);
		return $this->db->insert($this->profile_table_name);
	}

	/**
	 * Delete user profile
	 *
	 * @param	int
	 * @return	void
	 */
	private function delete_profile($user_id)
	{
		$this->db->where('user_id', $user_id);
		$this->db->delete($this->profile_table_name);
	}
	function get_partner_details_by_cname($partner_cname)
	{
		$sql = 'SELECT * FROM wl_partner_details WHERE cname = ? ORDER BY id LIMIT 1';
		$query = $this->db->query($sql, $partner_cname);
		if($query->num_rows() > 0)
		{
			$temp = $query->row_array();
			return $temp;
		}
		return false;
	}
	function get_partner_details_by_unique_id($unique_id)
	{
		$sql = 'SELECT * FROM wl_partner_details WHERE unique_id = ?';
		$query = $this->db->query($sql, $unique_id);
		if($query->num_rows() > 0)
		{
			$temp = $query->row_array();
			return $temp;
		}
		return false;
	}
	function get_partner_details_by_id($id)
	{
		$sql = 'SELECT * FROM wl_partner_details WHERE id = ?';
		$query = $this->db->query($sql, $id);
		if($query->num_rows() > 0)
		{
			if ($query->num_rows() == 1) return $query->row();
		}
		return false;
	}
	function get_sales_id_and_name_by_partner_id($p_id)
	{
		$sql = 'SELECT id, CONCAT(firstname, " ", lastname) as username FROM users WHERE role = "SALES" AND partner_id = ? ORDER BY username ASC';
		$query = $this->db->query($sql, $p_id);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return false;
	}
	function get_super_sales_by_partner_id($p_id)
	{
		$sql = 'SELECT * FROM users WHERE partner_id = ? AND role = "SALES" AND isGroupSuper = 1';
		$query = $this->db->query($sql, $p_id);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return false;
	}
	function get_advertisers_by_sales_person_partner_hierarchy($user_id, $is_super, $search_term=null, $mysql_page_number=0, $page_limit=0)
	{
		if($is_super == 1)
		{
			$binding_array = array($user_id);
			$sql = 
				'
				SELECT DISTINCT 
					a.*
				FROM
					users u
					LEFT JOIN wl_partner_owners po
						ON u.id = po.user_id
					JOIN wl_partner_hierarchy h
						ON (u.partner_id = h.ancestor_id OR po.partner_id = h.ancestor_id)
					JOIN wl_partner_details pd
						ON h.descendant_id = pd.id
					JOIN users u2 
						ON pd.id = u2.partner_id
					JOIN Advertisers a
						ON u2.id = a.sales_person
				WHERE 
					u.id = ? ';

			if ($search_term != null)
			{
				$sql .= " 
					AND 
						a.name LIKE ?";
				$binding_array[]=$search_term;
			}	
			$sql.=' ORDER BY a.Name ASC
			';
			if ($mysql_page_number > 0 || $page_limit > 0)	
			{
				$binding_array[]=$mysql_page_number;
				$binding_array[]=$page_limit;
				$sql .= " LIMIT ?,? ";
			}			
			
		}
		else
		{
			$binding_array = array($user_id, $user_id);
			$sql = 
				'
				SELECT DISTINCT
					a.*
				FROM
					users u
					JOIN wl_partner_owners po
						ON u.id = po.user_id
					JOIN wl_partner_hierarchy h
						ON po.partner_id = h.ancestor_id
					JOIN wl_partner_details pd
						ON h.descendant_id = pd.id
					JOIN users u2 
						ON pd.id = u2.partner_id
					RIGHT JOIN Advertisers a
						ON u2.id = a.sales_person
				WHERE 
					(u.id = ? OR a.sales_person = ?)';

			if ($search_term != null)
			{
				$sql .= " 
					AND 
						a.name LIKE ?";
				$binding_array[]=$search_term;
			}	

			$sql .= ' ORDER BY a.Name ASC
			';
			if ($mysql_page_number > 0 || $page_limit > 0)	
			{
				$binding_array[]=$mysql_page_number;
				$binding_array[]=$page_limit;
				$sql .= " LIMIT ?,? ";
			}			
			
		}
		$query = $this->db->query($sql, $binding_array);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return NULL;
	}

	function legacy_get_advertisers_by_sales_person_partner_hierarchy($search_term=null, $mysql_page_number=0, $page_limit=0, $user_id, $is_super)
	{
		error_log('$search_term is '. $search_term);
		if($is_super == 1)
		{
			$binding_array = array($user_id);
			$sql = 
				'
				SELECT DISTINCT 
					a.*
				FROM
					users u
					LEFT JOIN wl_partner_owners po
						ON u.id = po.user_id
					JOIN wl_partner_hierarchy h
						ON (u.partner_id = h.ancestor_id OR po.partner_id = h.ancestor_id)
					JOIN wl_partner_details pd
						ON h.descendant_id = pd.id
					JOIN users u2 
						ON pd.id = u2.partner_id
					JOIN Advertisers a
						ON u2.id = a.sales_person
				WHERE 
					u.id = ? ';

			if ($search_term != null)
			{
				$sql .= " 
					AND 
						a.name LIKE ?";
				$binding_array[]=$search_term;
			}	
			$sql.=' ORDER BY a.Name ASC
			';
			if ($mysql_page_number > 0 || $page_limit > 0)	
			{
				$binding_array[]=$mysql_page_number;
				$binding_array[]=$page_limit;
				$sql .= " LIMIT ?,? ";
			}			
			
		}
		else
		{
			$binding_array = array($user_id, $user_id);
			$sql = 
				'
				SELECT DISTINCT
					a.*
				FROM
					users u
					JOIN wl_partner_owners po
						ON u.id = po.user_id
					JOIN wl_partner_hierarchy h
						ON po.partner_id = h.ancestor_id
					JOIN wl_partner_details pd
						ON h.descendant_id = pd.id
					JOIN users u2 
						ON pd.id = u2.partner_id
					RIGHT JOIN Advertisers a
						ON u2.id = a.sales_person
				WHERE 
					(u.id = ? OR a.sales_person = ?)';

			if ($search_term != null)
			{
				$sql .= " 
					AND 
						a.name LIKE ?";
				$binding_array[]=$search_term;
			}	

			$sql .= ' ORDER BY a.Name ASC
			';
			if ($mysql_page_number > 0 || $page_limit > 0)	
			{
				$binding_array[]=$mysql_page_number;
				$binding_array[]=$page_limit;
				$sql .= " LIMIT ?,? ";
			}			
			
		}
		$query = $this->db->query($sql, $binding_array);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return NULL;
	}

	function get_advertisers_for_client_or_agency($user_id, $search_term=null, $mysql_page_number=0, $page_limit=0)
	{
		$binding_array = array($user_id);
		$sql = "
			SELECT
				adv.*
			FROM
				clients_to_advertisers AS cta
				JOIN Advertisers AS adv
					ON cta.advertiser_id = adv.id
			WHERE
				cta.user_id = ?";

		if ($search_term != null && $search_term != "")
		{
			$sql .= " 
				AND 
					adv.name LIKE ?";
			$binding_array[]=$search_term;
		}			
				
		$sql .= "ORDER BY adv.Name ASC
		";

		if ($mysql_page_number > 0 || $page_limit > 0)	
		{
			$binding_array[]=$mysql_page_number;
			$binding_array[]=$page_limit;
			$sql .= " LIMIT ?,? ";
		}	

		$result = $this->db->query($sql, $binding_array);
		if($result->num_rows() > 0)
		{
			return $result->result_array();
		}

		return null;
	}
	public function get_sales_users_by_partner_hierarchy($user_id, $is_super)
	{
		$result_array = array();
		$hierarchy_on_sql = "";
		$bindings = array($user_id, $user_id);
				
		if ($is_super == 1)
		{
			$hierarchy_on_sql .= 
						"
							UNION
							SELECT
								DISTINCT u2.id AS id,
								CONCAT(u2.firstname, \" \", u2.lastname) as username
							FROM
								users AS u
								LEFT JOIN wl_partner_owners po
									ON u.id = po.user_id
								LEFT JOIN wl_partner_hierarchy AS h
									ON (u.partner_id = h.ancestor_id)
								LEFT JOIN wl_partner_details AS pd
									ON h.descendant_id = pd.id
								RIGHT JOIN users AS u2 
									ON pd.id = u2.partner_id
							WHERE
								(u.id = ? OR
								u2.id = ?) AND
								u2.role = 'SALES'
						";
			$bindings[] = $user_id;
			$bindings[] = $user_id;
		}
		
		$sql = "
				SELECT
					id,
					username
				FROM
				(
					SELECT
						DISTINCT u2.id AS id,
						CONCAT(u2.firstname, \" \", u2.lastname) as username
					FROM
						users AS u
						LEFT JOIN wl_partner_owners po
							ON u.id = po.user_id
						LEFT JOIN wl_partner_hierarchy AS h
							ON (po.partner_id = h.ancestor_id)
						LEFT JOIN wl_partner_details AS pd
							ON h.descendant_id = pd.id
						RIGHT JOIN users AS u2 
							ON pd.id = u2.partner_id
					WHERE
						(u.id = ? OR
						u2.id = ?) AND
						u2.role = 'SALES'
					$hierarchy_on_sql	
				) AS user_info					
				ORDER BY 
					user_info.username ASC
			";
		$query = $this->db->query($sql, $bindings);
		
		if ($query->num_rows() > 0)
		{
			$result_array = $query->result_array();
		}
		
		return $result_array;
	}
	public function get_parent_and_sales_owner_emails_by_partner($partner_id)
	{
		$sql = "
			SELECT DISTINCT 
				us.id, us.email 
			FROM 
				wl_partner_hierarchy AS ph
				JOIN wl_partner_details AS pd
					ON ph.ancestor_id = pd.id
				LEFT JOIN wl_partner_owners po
					ON pd.id = po.partner_id
				JOIN users AS us
					ON (pd.id = us.partner_id OR po.user_id = us.id)
			WHERE
				(us.isGroupSuper = '1' OR (po.user_id = us.id)) AND
				ph.descendant_id = ? AND
				us.role = 'SALES' AND
				us.banned = 0 AND
				us.is_insertion_order_cc = 1
			";
		$query = $this->db->query($sql, $partner_id);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return array();
	}
	public function get_partner_hierarchy_by_sales_person($user_id, $is_super)
	{
		$result = array();
		$sales_super_hierarchy_sql = "";
		if($is_super == 1)
		{
			$sales_super_hierarchy_sql = " OR us.partner_id = ph.ancestor_id";
		}
		$sql = "
			SELECT DISTINCT
				pd.id AS id,
				pd.partner_name AS partner_name
			FROM
				users AS us
				LEFT JOIN wl_partner_owners po
					ON us.id = po.user_id
				LEFT JOIN wl_partner_hierarchy AS ph
					ON (po.partner_id = ph.ancestor_id".$sales_super_hierarchy_sql.")
				RIGHT JOIN wl_partner_details AS pd
					ON (ph.descendant_id = pd.id OR us.partner_id = pd.id)
			WHERE
				us.id = ?
			ORDER BY
				partner_name";
		$query = $this->db->query($sql, $user_id);
		if($query->num_rows() > 0)
		{
			$result = $query->result_array();
		}
		return $result;
	}
	public function get_sales_people_by_partners_allowed_by_user($user_id, $is_super, $selected_partner = false)
	{
		$bindings = array($user_id);
		if($is_super == "1")
		{
			$sql = "
				SELECT DISTINCT
					us2.id AS id,
					CONCAT(us2.firstname, ' ', us2.lastname) AS username,
					pd.partner_name AS partner,
					MIN(ag.earliest_city_record) AS earliest_impression,
					MAX(ag.latest_city_record) AS latest_impression
				FROM
					users AS us
					LEFT JOIN wl_partner_owners po
						ON us.id = po.user_id
					JOIN wl_partner_hierarchy AS ph
						ON (po.partner_id = ph.ancestor_id OR us.partner_id = ph.ancestor_id)
					RIGHT JOIN wl_partner_details AS pd
						ON (ph.descendant_id = pd.id OR us.partner_id = pd.id)
					JOIN users AS us2
						ON pd.id = us2.partner_id
					LEFT JOIN Advertisers ad
						ON us2.id = ad.sales_person
					LEFT JOIN Campaigns ca
						ON ad.id = ca.business_id
					LEFT JOIN AdGroups ag
						ON ca.id = ag.campaign_id	
				WHERE
					us.id = ? AND
					us2.role = 'SALES'
				GROUP BY
					us2.id
				ORDER BY
					username";
		}
		else //non super sales
		{
			$partner_selection_sql = "";
			$union_selection_sql = "";
			if($selected_partner !== false)
			{
				$bindings[] = $selected_partner;
				$bindings[] = $user_id;
				$bindings[] = $selected_partner;
				$partner_selection_sql = " AND us2.partner_id = ?";
				$union_selection_sql = "AND us3.partner_id = ?";
			}
			else
			{
				$bindings[] = $user_id;
			}
			$sql = "
				SELECT DISTINCT 
					alias.* 
				FROM 
				(
					SELECT DISTINCT 
						us2.id as id, 
						CONCAT(us2.firstname, ' ', us2.lastname) as username,
						MIN(ag.earliest_city_record) AS earliest_impression,
						MAX(ag.latest_city_record) AS latest_impression
					FROM
						users us
						JOIN wl_partner_owners po
							ON us.id = po.user_id
						JOIN wl_partner_hierarchy as ph
							ON (po.partner_id = ph.ancestor_id)
						JOIN users us2
							ON ph.descendant_id = us2.partner_id
						LEFT JOIN Advertisers ad
							ON us2.id = ad.sales_person
						LEFT JOIN Campaigns ca
							ON ad.id = ca.business_id
						LEFT JOIN AdGroups ag
							ON ca.id = ag.campaign_id
					WHERE
						us.id = ? AND
						us2.role = 'SALES'
						".$partner_selection_sql."
					GROUP BY 
						us2.id
					UNION
					SELECT 
						us3.id, 
						CONCAT(us3.firstname, ' ', us3.lastname) as username,
						MIN(ag.earliest_city_record) AS earliest_impression,
						MAX(ag.latest_city_record) AS latest_impression
					FROM 
						users us3
						LEFT JOIN Advertisers ad
							ON us3.id = ad.sales_person
						LEFT JOIN Campaigns ca
							ON ad.id = ca.business_id
						LEFT JOIN AdGroups ag
							ON ca.id = ag.campaign_id
					WHERE 
						us3.id = ?
						".$union_selection_sql."
					GROUP BY us3.id
				) alias
				WHERE 1
				ORDER BY 
					alias.username";
		}
		$query = $this->db->query($sql, $bindings);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return array();
	}
        
        public function is_valid_partner_id($partner_id)
        {
                $sql = '
                        SELECT 
                                id
                        FROM 
                                wl_partner_details
                        WHERE 
                                id = ?
                ';
                $result = $this->db->query($sql, $partner_id);
                return $result->num_rows() > 0;
        }

        public function get_all_partners_except_self($current_partner_id = null)
        {
                $sub_sql = 'WHERE 1';
                $bindings = array();
                if ($current_partner_id)
                {
                        $bindings[] = $current_partner_id;
                        $sub_sql = 'WHERE id != ?';
                }
                $sql = '
                        SELECT 
                                id,
                                partner_name 
                        FROM 
                                wl_partner_details '.$sub_sql;
                $query = $this->db->query($sql, $bindings);
                if($query->num_rows() > 0)
                {
                        return $query->result_array();
                }
                return NULL;
        }

        public function get_all_descendant_partner_ids($partner_id)
        {
            $query = '
                    SELECT                 	
                            descendant_id
                    FROM
                            wl_partner_hierarchy
                    WHERE
                            ancestor_id = ?';
            $response = $this->db->query($query, $partner_id);
            if($response)
            {
                    $raw_descendant_data = $response->result_array();
                    return $raw_descendant_data;
            }
            return false;
        }
        
        public function save_partner_owner($user_id, $partner_id)
        {
            $sql_bindings = array(           
                    $user_id,
                    $partner_id
            );

            // In wl_partner_owners table
            $query = "
                    INSERT INTO 
                            wl_partner_owners  
                                    (
                                            user_id,
                                            partner_id
                                    )
                            VALUES
                                    (?, ?)";
            $result = $this->db->query($query, $sql_bindings);

            return true;
        }
        
	function get_demo_users_by_partner_id($p_id)
	{
		$sql = 'SELECT 
			* 
			FROM 
				users 
			WHERE 
				partner_id = ?';
		$query = $this->db->query($sql, $p_id);
		if($query->num_rows() > 0)
		{
		    foreach($query->result_array() as $demo_arr)
		    {
			$demo_data[] = $demo_arr['email'];			
		    }		    
		    return $demo_data;
		}
		return false;
	}
}

/* End of file users.php */
/* Location: ./application/models/auth/users.php */
