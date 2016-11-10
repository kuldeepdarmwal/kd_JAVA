<?php
class vl_platform_model extends CI_Model
{
	public function __construct()
	{
		$this->load->database();
	}

	public function is_feature_accessible($user_id, $feature_html_id)
	{
		$is_accessible = false;
		$bindings = array();
		$query = $this->get_master_feature_query($bindings, $user_id, $feature_html_id);
		$response = $this->db->query($query, $bindings);
		if($response)
		{
			if($response->num_rows() > 0)
			{
				$row = $response->row_array(0);
				if($row['has_access'])
				{
					$is_accessible = true;
				}
			}
		}

		return $is_accessible;
	}

	public function get_accessable_features($user_id)
	{
		$bindings = [];
		$result = $this->get_features_for_user($user_id);
		if($result->num_rows() > 0)
		{
			return $result->result_array();
		}
		return array();
	}

	//	create the sql query without duplicating the code
	//	PARAMETER: $feature_html_id determines whether we are getting access permission or getting the data set
	//		- if empty then we are getting the access permission
	//		- if string then we are getting the feature data set
	//
	//	Note: there are queries for debugging the relationships between tables in functions starting with 'debug_'
	private function get_master_feature_query(array &$bindings, $user_id, $feature_html_id = '')
	{
		$bindings[] = $user_id;
		$where_is_accessible_sql = '';
		$where_get_accessible_set_sql = '';
		if(empty($feature_html_id))
		{
			// getting feature data set
			$where_get_accessible_set_sql = '
				WHERE
					pf.ha = 1
			';
		}
		else
		{
			// getting access permission
			$bindings[] = $feature_html_id;
			$where_is_accessible_sql = '
				WHERE
					fe.html_id = ?
			';
		}

		$query = '
			SELECT
				fe.html_id AS html_id,
				fe.name AS name,
				fe.url AS url,
				fe.icon AS icon,
				fe.`order` AS `order`,
        fe.is_in_header AS in_header,
				pf.ha AS has_access
			FROM
				pl_features AS fe JOIN
				(
					SELECT
						fp.permission_group_id AS pgi,
						fp.feature_id AS fi,
						fp.has_access AS ha
					FROM
						pl_feature_permissions AS fp JOIN
						(
							SELECT
								pg.id AS id,
								pg.role AS role,
								pg.is_group_super AS is_group_super,
								pg.is_global_super AS is_global_super,
								pg.partner_id AS partner_id,
								pg.user_id AS user_id
							FROM
								pl_permission_groups AS pg JOIN
								(
									SELECT
										u.role AS role,
										u.isGroupSuper AS is_group_super,
										u.isGlobalSuper AS is_global_super,
										IF(
											u.role = \'BUSINESS\',
											(SELECT u_sales.partner_id AS partner_id
											FROM users u_inner
												JOIN Advertisers a ON (u_inner.advertiser_id = a.id)
												JOIN users u_sales ON (a.sales_person = u_sales.id)
											WHERE u_inner.id = u.id)
											,
											u.partner_id
										) AS partner_id,
										u.id AS user_id
									FROM users AS u
									WHERE u.id = ?
								) AS ud
								ON (
									(pg.role = ud.role COLLATE utf8_general_ci OR ISNULL(pg.role)) AND
									(pg.is_group_super = ud.is_group_super OR ISNULL(pg.is_group_super)) AND
									(pg.is_global_super = pg.is_global_super OR ISNULL(pg.is_global_super)) AND
									(pg.partner_id = ud.partner_id OR ISNULL(pg.partner_id)) AND
									(pg.user_id = ud.user_id OR ISNULL(pg.user_id))
								)
							WHERE
								1
							ORDER BY
								user_id DESC,
								partner_id DESC,
								is_group_super DESC,
								is_global_super DESC,
								role DESC
						) AS pg_o
						ON (
							fp.permission_group_id = pg_o.id
						)
					GROUP BY
						fi
				) AS pf
				ON (
					pf.fi = fe.id
				)
				'.
				$where_is_accessible_sql.
				$where_get_accessible_set_sql
				.'
			ORDER BY
				`order` ASC
		';

		return $query;
	}

	public function get_features_for_user($user_id)
	{
		$bindings = array();
		$query = $this->get_master_feature_query($bindings, $user_id);
		$response = $this->db->query($query, $bindings);
		return $response;
	}

	private function debug_get_features_by_permission_groups_query()
	{
		/* created (3/29/2013)
		// if sql in get_master_feature_query() changes this sql will probably need to change too
		$query = '
			SELECT
				fe.id AS feature_id,
				fe.html_id AS html_id,
				fe.name AS name,
				pg.id AS permission_group_id,
				pg.role AS role,
				pg.is_group_super AS is_group_super,
				pg.is_global_super AS is_global_super,
				pg.partner_id AS partner_id,
				pg.user_id AS user_id,
				fp.has_access AS has_access
			FROM
				pl_features AS fe JOIN
				pl_feature_permissions AS fp ON (
					fe.id = fp.feature_id
				) JOIN
				pl_permission_groups AS pg ON (
					fp.permission_group_id = pg.id
				)
			ORDER BY
				fe.id ASC,
				pg.id ASC
		';

		return $query;
		*/
	}

	private function debug_get_features_by_permission_groups_for_all_users_query()
	{
		/* created (3/29/2013)
		// if sql in get_master_feature_query() changes this sql will probably need to change too
		$query = '
      SELECT
        pf.u_user_id AS u_user_id,
        pf.u_username AS u_username,
        pf.u_firstname AS u_firstname,
        pf.u_lastname AS u_lastname,
        fe.id AS feature_id,
        fe.html_id AS f_html_id,
        fe.name AS f_name,
        pf.pgi AS permission_group_id,
        pf.ha AS p_has_access,
        pf.role AS p_role,
        pf.is_group_super AS p_is_group_super,
        pf.is_global_super AS p_is_global_super,
        pf.partner_id AS p_partner_id,
        pf.p_user_id AS p_user_id,
        pf.role AS u_role,
        pf.u_is_group_super AS u_is_group_super,
        pf.u_is_global_super AS u_is_global_super,
        pf.u_partner_id AS u_partner_id,
        fe.`order` AS `order`
      FROM
        pl_features AS fe JOIN
        (
          SELECT
            fp.permission_group_id AS pgi,
            fp.feature_id AS fi,
            fp.has_access AS ha,
            pg_o.role AS role,
            pg_o.is_group_super AS is_group_super,
            pg_o.is_global_super AS is_global_super,
            pg_o.partner_id AS partner_id,
            pg_o.p_user_id AS p_user_id,
            pg_o.u_role AS u_role,
            pg_o.u_is_group_super AS u_is_group_super,
            pg_o.u_is_global_super AS u_is_global_super,
            pg_o.u_partner_id AS u_partner_id,
            pg_o.u_user_id AS u_user_id,
            pg_o.u_username AS u_username,
            pg_o.u_firstname AS u_firstname,
            pg_o.u_lastname AS u_lastname
          FROM
            pl_feature_permissions AS fp JOIN
            (
              SELECT
                pg.id AS id,
                pg.role AS role,
                pg.is_group_super AS is_group_super,
                pg.is_global_super AS is_global_super,
                pg.partner_id AS partner_id,
                pg.user_id AS p_user_id,
                ud.role AS u_role,
                ud.is_group_super AS u_is_group_super,
                ud.is_global_super AS u_is_global_super,
                ud.partner_id AS u_partner_id,
                ud.user_id AS u_user_id,
                ud.username AS u_username,
                ud.firstname AS u_firstname,
                ud.lastname AS u_lastname
              FROM
                pl_permission_groups AS pg JOIN
                (
                  SELECT
                    u.role AS role,
                    u.isGroupSuper AS is_group_super,
                    u.isGlobalSuper AS is_global_super,
                    IF(

                      u.role = \'BUSINESS\',
                      (SELECT u_sales.partner_id AS partner_id
                      FROM users u_inner
                        JOIN Advertisers a ON (u_inner.business_name = a.Name)
                        JOIN users u_sales ON (a.sales_person = u_sales.id)
                      WHERE u_inner.id = u.id)
                      ,
                      u.partner_id
                    ) AS partner_id,
                    u.id AS user_id,
                    u.username AS username,
                    u.firstname AS firstname,
                    u.lastname AS lastname
                  FROM users AS u
                ) AS ud
                ON (
                  (pg.role = ud.role COLLATE utf8_general_ci OR ISNULL(pg.role)) AND
                  (pg.is_group_super = ud.is_group_super OR ISNULL(pg.is_group_super)) AND
                  (pg.is_global_super = pg.is_global_super OR ISNULL(pg.is_global_super)) AND
                  (pg.partner_id = ud.partner_id OR ISNULL(pg.partner_id)) AND
                  (pg.user_id = ud.user_id OR ISNULL(pg.user_id))
                )
              WHERE
                1
              ORDER BY
                p_user_id DESC,
                partner_id DESC,
                is_group_super DESC,
                is_global_super DESC,
                role DESC
            ) AS pg_o
            ON (
              fp.permission_group_id = pg_o.id
            )
        ) AS pf
        ON (
          pf.fi = fe.id
        )
      ORDER BY
        u_user_id DESC,
        feature_id ASC
		';

		return $query;
		*/
	}

	private function debug_get_features_and_all_permission_groups_for_user_query(&$bindings, $user_id)
	{
		/* created (3/29/2013)
		// if sql in get_master_feature_query() changes this sql will probably need to change too
		$bindings[] = $user_id;

		$query = '
      SELECT
        pf.u_user_id AS u_user_id,
        pf.u_username AS u_username,
        pf.u_firstname AS u_firstname,
        pf.u_lastname AS u_lastname,
        fe.id AS feature_id,
        fe.html_id AS f_html_id,
        fe.name AS f_name,
        pf.pgi AS permission_group_id,
        pf.ha AS p_has_access,
        pf.role AS p_role,
        pf.is_group_super AS p_is_group_super,
        pf.is_global_super AS p_is_global_super,
        pf.partner_id AS p_partner_id,
        pf.p_user_id AS p_user_id,
        pf.role AS u_role,
        pf.u_is_group_super AS u_is_group_super,
        pf.u_is_global_super AS u_is_global_super,
        pf.u_partner_id AS u_partner_id,
        fe.`order` AS `order`
      FROM
        pl_features AS fe JOIN
        (
          SELECT
            fp.permission_group_id AS pgi,
            fp.feature_id AS fi,
            fp.has_access AS ha,
            pg_o.role AS role,
            pg_o.is_group_super AS is_group_super,
            pg_o.is_global_super AS is_global_super,
            pg_o.partner_id AS partner_id,
            pg_o.p_user_id AS p_user_id,
            pg_o.u_role AS u_role,
            pg_o.u_is_group_super AS u_is_group_super,
            pg_o.u_is_global_super AS u_is_global_super,
            pg_o.u_partner_id AS u_partner_id,
            pg_o.u_user_id AS u_user_id,
            pg_o.u_username AS u_username,
            pg_o.u_firstname AS u_firstname,
            pg_o.u_lastname AS u_lastname
          FROM
            pl_feature_permissions AS fp JOIN
            (
              SELECT
                pg.id AS id,
                pg.role AS role,
                pg.is_group_super AS is_group_super,
                pg.is_global_super AS is_global_super,
                pg.partner_id AS partner_id,
                pg.user_id AS p_user_id,
                ud.role AS u_role,
                ud.is_group_super AS u_is_group_super,
                ud.is_global_super AS u_is_global_super,
                ud.partner_id AS u_partner_id,
                ud.user_id AS u_user_id,
                ud.username AS u_username,
                ud.firstname AS u_firstname,
                ud.lastname AS u_lastname
              FROM
                pl_permission_groups AS pg JOIN
                (
                  SELECT
                    u.role AS role,
                    u.isGroupSuper AS is_group_super,
                    u.isGlobalSuper AS is_global_super,
                    IF(

                      u.role = \'BUSINESS\',
                      (SELECT u_sales.partner_id AS partner_id
                      FROM users u_inner
                        JOIN Advertisers a ON (u_inner.business_name = a.Name)
                        JOIN users u_sales ON (a.sales_person = u_sales.id)
                      WHERE u_inner.id = u.id)
                      ,
                      u.partner_id
                    ) AS partner_id,
                    u.id AS user_id,
                    u.username AS username,
                    u.firstname AS firstname,
                    u.lastname AS lastname
                  FROM users AS u
                  WHERE u.id = ?
                ) AS ud
                ON (
                  (pg.role = ud.role COLLATE utf8_general_ci OR ISNULL(pg.role)) AND
                  (pg.is_group_super = ud.is_group_super OR ISNULL(pg.is_group_super)) AND
                  (pg.is_global_super = pg.is_global_super OR ISNULL(pg.is_global_super)) AND
                  (pg.partner_id = ud.partner_id OR ISNULL(pg.partner_id)) AND
                  (pg.user_id = ud.user_id OR ISNULL(pg.user_id))
                )
              WHERE
                1
              ORDER BY
                p_user_id DESC,
                partner_id DESC,
                is_group_super DESC,
                is_global_super DESC,
                role DESC
            ) AS pg_o
            ON (
              fp.permission_group_id = pg_o.id
            )
        ) AS pf
        ON (
          pf.fi = fe.id
        )
			ORDER BY
				feature_id ASC,
				p_user_id DESC,
				p_partner_id DESC,
				p_is_group_super DESC,
				p_is_global_super DESC,
				p_role DESC

		';
		return $query;
		*/
	}

	private function debug_get_filtered_features_by_permission_groups_for_user_query(&$bindings, $user_id)
	{
		/* created (3/29/2013)
		// if sql in get_master_feature_query() changes this sql will probably need to change too
		$bindings[] = $user_id;

		$query = '
      SELECT
        fe.id AS feature_id,
        fe.html_id AS f_html_id,
        fe.name AS f_name,
        fe.`order` AS `order`,
        pf.pgi AS permission_group_id,
        pf.ha AS p_has_access,
        pf.role AS p_role,
        pf.is_group_super AS p_is_group_super,
        pf.is_global_super AS p_is_global_super,
        pf.partner_id AS p_partner_id,
        pf.p_user_id AS p_user_id,
        pf.role AS u_role,
        pf.u_is_group_super AS u_is_group_super,
        pf.u_is_global_super AS u_is_global_super,
        pf.u_partner_id AS u_partner_id,
        pf.u_user_id AS u_user_id,
        pf.u_username AS u_username,
        pf.u_firstname AS u_firstname,
        pf.u_lastname AS u_lastname
      FROM
        pl_features AS fe JOIN
        (
          SELECT
            fp.permission_group_id AS pgi,
            fp.feature_id AS fi,
            fp.has_access AS ha,
            pg_o.role AS role,
            pg_o.is_group_super AS is_group_super,
            pg_o.is_global_super AS is_global_super,
            pg_o.partner_id AS partner_id,
            pg_o.p_user_id AS p_user_id,
            pg_o.u_role AS u_role,
            pg_o.u_is_group_super AS u_is_group_super,
            pg_o.u_is_global_super AS u_is_global_super,
            pg_o.u_partner_id AS u_partner_id,
            pg_o.u_user_id AS u_user_id,
            pg_o.u_username AS u_username,
            pg_o.u_firstname AS u_firstname,
            pg_o.u_lastname AS u_lastname
          FROM
            pl_feature_permissions AS fp JOIN
            (
              SELECT
                pg.id AS id,
                pg.role AS role,
                pg.is_group_super AS is_group_super,
                pg.is_global_super AS is_global_super,
                pg.partner_id AS partner_id,
                pg.user_id AS p_user_id,
                ud.role AS u_role,
                ud.is_group_super AS u_is_group_super,
                ud.is_global_super AS u_is_global_super,
                ud.partner_id AS u_partner_id,
                ud.user_id AS u_user_id,
                ud.username AS u_username,
                ud.firstname AS u_firstname,
                ud.lastname AS u_lastname
              FROM
                pl_permission_groups AS pg JOIN
                (
                  SELECT
                    u.role AS role,
                    u.isGroupSuper AS is_group_super,
                    u.isGlobalSuper AS is_global_super,
                    IF(

                      u.role = \'BUSINESS\',
                      (SELECT u_sales.partner_id AS partner_id
                      FROM users u_inner
                        JOIN Advertisers a ON (u_inner.business_name = a.Name)
                        JOIN users u_sales ON (a.sales_person = u_sales.id)
                      WHERE u_inner.id = u.id)
                      ,
                      u.partner_id
                    ) AS partner_id,
                    u.id AS user_id,
                    u.username AS username,
                    u.firstname AS firstname,
                    u.lastname AS lastname
                  FROM users AS u
                  WHERE u.id = ?
                ) AS ud
                ON (
                  (pg.role = ud.role COLLATE utf8_general_ci OR ISNULL(pg.role)) AND
                  (pg.is_group_super = ud.is_group_super OR ISNULL(pg.is_group_super)) AND
                  (pg.is_global_super = pg.is_global_super OR ISNULL(pg.is_global_super)) AND
                  (pg.partner_id = ud.partner_id OR ISNULL(pg.partner_id)) AND
                  (pg.user_id = ud.user_id OR ISNULL(pg.user_id))
                )
              WHERE
                1
              ORDER BY
                p_user_id DESC,
                partner_id DESC,
                is_group_super DESC,
                is_global_super DESC,
                role DESC
            ) AS pg_o
            ON (
              fp.permission_group_id = pg_o.id
            )
					GROUP BY
						fi
        ) AS pf
        ON (
          pf.fi = fe.id
        )
      ORDER BY
				`order` ASC

		';
		return $query;
		*/
	}

	private function debug_get_permission_groups_for_user_query(&$bindings, $user_id)
	{
		/* created (3/29/2013)
		// if sql in get_master_feature_query() changes this sql will probably need to change too
		$bindings[] = $user_id;
		$query = '
              SELECT
                pg.id AS id,
                pg.role AS role,
                pg.is_group_super AS is_group_super,
                pg.is_global_super AS is_global_super,
                pg.partner_id AS partner_id,
                pg.user_id AS p_user_id,
                ud.role AS u_role,
                ud.is_group_super AS u_is_group_super,
                ud.is_global_super AS u_is_global_super,
                ud.partner_id AS u_partner_id,
                ud.user_id AS u_user_id,
                ud.username AS u_username,
                ud.firstname AS u_firstname,
                ud.lastname AS u_lastname
              FROM
                pl_permission_groups AS pg JOIN
                (
                  SELECT
                    u.role AS role,
                    u.isGroupSuper AS is_group_super,
                    u.isGlobalSuper AS is_global_super,
                    IF(
                      u.role = \'BUSINESS\',
                      (SELECT u_sales.partner_id AS partner_id
                      FROM users u_inner
                        JOIN Advertisers a ON (u_inner.business_name = a.Name)
                        JOIN users u_sales ON (a.sales_person = u_sales.id)
                      WHERE u_inner.id = u.id)
                      ,
                      u.partner_id
                    ) AS partner_id,
                    u.id AS user_id,
                    u.username AS username,
                    u.firstname AS firstname,
                    u.lastname AS lastname
                  FROM users AS u
                  WHERE u.id = ?
                ) AS ud
                ON (
                  (pg.role = ud.role COLLATE utf8_general_ci OR ISNULL(pg.role)) AND
                  (pg.is_group_super = ud.is_group_super OR ISNULL(pg.is_group_super)) AND
                  (pg.is_global_super = pg.is_global_super OR ISNULL(pg.is_global_super)) AND
                  (pg.partner_id = ud.partner_id OR ISNULL(pg.partner_id)) AND
                  (pg.user_id = ud.user_id OR ISNULL(pg.user_id))
                )
              WHERE
                1
              ORDER BY
                p_user_id DESC,
                partner_id DESC,
                is_group_super DESC,
                is_global_super DESC,
                role DESC
		';

		return $query;
		*/
	}

	public function get_user_profile_details($user_id)
	{
		///m@ this function needs to be changed to only have the required details needed for the header and footer accross all VL platform pages

		$user_sql = '
			(SELECT
				CONCAT(u.firstname," ",u.lastname) AS user_full_name,
				u.email AS user_email,
				u.role AS user_role,
        u.isGroupSuper AS is_group_super,
				IF(u.role = \'BUSINESS\',
					(
						SELECT
							u_sales.partner_id AS partner_id
						FROM
							users u_inner
							JOIN Advertisers a ON (u_inner.advertiser_id = a.id)
							JOIN users u_sales ON (a.sales_person = u_sales.id)
						WHERE u_inner.id = u.id
					),
					u.partner_id
				) AS partner_id,
				u.advertiser_id as user_advertiser_id,
				IF(u.role = \'BUSINESS\',
					(
						SELECT
							adv.Name as adver_name
						FROM
							Advertisers adv
						WHERE adv.id = u.advertiser_id
					),
					NULL
				) as advertiser_name,
				u.id AS user_id
			FROM users AS u
			WHERE
				u.id = ?
			LIMIT 1
		) user_profile
		';

		$user_partner_sql = '
		(
			SELECT
				user_profile.*,
				p.partner_name as partner_org,
				p.home_url as partner_home_page,
				p.contact_number as partner_phone
			FROM'.
				$user_sql
				.'
				JOIN wl_partner_details p ON (user_profile.partner_id = p.id)
		) user_partner_join';

		$sql =   '
			SELECT
				user_partner_join.*,
				IF(user_partner_join.user_role = \'BUSINESS\',
				c.LandingPage, NULL) as recent_landing_page
			FROM'.
				$user_partner_sql
				.'
				LEFT JOIN Campaigns c ON (user_partner_join.user_advertiser_id = c.business_id)
		';

		$query = $this->db->query($sql, $user_id);
		if($query->num_rows() > 0)
		{
			return $query->row_array();
		}
		else
		{
			return false;
		}
	}

	public function get_cname_from_advertiser_id($adv_id)
	{
		$query =
		"	SELECT
				pd.cname
			FROM
				wl_partner_details AS pd
			INNER JOIN
				users AS u
				ON u.partner_id = pd.id
			INNER JOIN
				Advertisers AS a
				ON a.sales_person = u.id
			WHERE
				a.id = ?
			LIMIT 1
		";
		$result = $this->db->query($query, $adv_id);
		if($result->num_rows() > 0)
		{
			return $result->row_array()['cname'];
		}
		return null;
	}

	public function get_cname_from_sales_partner_id($partner_id)
	{
		$query =
		"	SELECT
				pd.cname
			FROM
				wl_partner_details AS pd
			WHERE
				pd.id = ?
		";
		$result = $this->db->query($query, $partner_id);
		if($result->num_rows() > 0)
		{
			return $result->row_array()['cname'];
		}
		return null;
	}

	public function get_feature_id_by_html_id($feature_html_id)
	{
		if (!isset($feature_html_id))
		{
			return null;
		}

		$query =
			"SELECT
				features.id AS feature_id
			FROM
				pl_features AS features
			WHERE
				features.html_id = ?
			";

		$result = $this->db->query($query,$this->db->escape(array($feature_html_id)));

		if ($result->num_rows() > 0)
		{
			return $result->row()->feature_id;
		}

		return null;
	}

	public function get_user_level_permission_group_id($user_id)
	{
		if (!isset($user_id))
		{
			return null;
		}

		$query =
			"SELECT
				pg.id AS permission_group_id
			FROM
				pl_permission_groups AS pg
			WHERE
				pg.role IS NULL
			AND
				pg.is_group_super IS NULL
			AND
				pg.is_global_super IS NULL
			AND
				pg.user_id = ?
			";

		$result = $this->db->query($query,array($user_id));

		if ($result->num_rows() > 0)
		{
			return $result->row()->permission_group_id;
		}

		return null;
	}

	public function create_user_level_permission_group_id($user_id)
	{
		if (!isset($user_id))
		{
			return null;
		}

		$query =
			"INSERT IGNORE INTO
				pl_permission_groups (user_id)
			VALUES
				(?)
			";

		$this->db->query($query,array($user_id));

		if ($this->db->insert_id())
		{
			return $this->db->insert_id();
		}
		else
		{
			return null;
		}
	}

	public function get_feature_permission($permission_group_id,$feature_id)
	{
		if (!isset($permission_group_id) || !isset($feature_id))
		{
			return null;
		}

		$query =
			"SELECT
				fp.has_access
			FROM
				pl_feature_permissions AS fp
			WHERE
				fp.permission_group_id = ?
			AND
				fp.feature_id = ?
			";

		$result = $this->db->query($query,array($permission_group_id,$feature_id));

		if ($result->num_rows() > 0)
		{
			return $result->row();
		}

		return null;
	}

	public function create_feature_permission($permission_group_id, $feature_id, $has_access)
	{
		if (!isset($permission_group_id) || !isset($feature_id) || !isset($has_access))
		{
			return null;
		}

		$sql =
			"INSERT IGNORE INTO
				pl_feature_permissions (permission_group_id,feature_id,has_access)
			VALUES
				(?,?,?)
			";

		$this->db->query($sql,array($permission_group_id,$feature_id,$has_access));
		return $this->db->affected_rows();
	}

	public function update_feature_permission($permission_group_id, $feature_id, $has_access)
	{
		if (!isset($permission_group_id) || !isset($feature_id) || !isset($has_access))
		{
			return null;
		}

		$sql =
			"UPDATE
				pl_feature_permissions
			SET
				has_access = ?
			WHERE
				permission_group_id = ?
			AND
				feature_id = ?
			";

		$this->db->query($sql,array($has_access,$permission_group_id,$feature_id));
		return $this->db->affected_rows();
	}
        
        public function get_partner_level_permission_group_id($partner_id, $role, $is_group_super = null)
	{
		if (!isset($partner_id) && !isset($role))
		{
			return null;
		}
                
                $in_string = 'AND pg.is_group_super IS NULL';
                $sql_bindings[] = $role;
                if (!empty($is_group_super))
                {
                        $in_string = 'AND pg.is_group_super = ?';
                        $sql_bindings[] = $is_group_super;
                }
                $sql_bindings[] = $partner_id;

		$query =
			"SELECT
				pg.id AS permission_group_id
			FROM
				pl_permission_groups AS pg
			WHERE
				pg.role = ?
                            AND 
                                pg.user_id IS NULL
                                {$in_string}
                            AND
				pg.partner_id = ?
			";

		$result = $this->db->query($query, $sql_bindings);

		if ($result->num_rows() > 0)
		{
			return $result->row()->permission_group_id;
		}

		return null;
	}
        
        public function get_partner_features_permission($permission_group_id)
	{
		if (!isset($permission_group_id))
		{
			return null;
		}

		$query =
			"SELECT
				fp.feature_id
			FROM
				pl_feature_permissions AS fp
			WHERE
				fp.permission_group_id = ?
			AND
				fp.has_access = 1
			";

		$result = $this->db->query($query,array($permission_group_id));

		if ($result->num_rows() > 0)
		{
			return $result->result_array();
		}

		return null;
	}
	
	public function get_campaigns_with_lift_permission($campaign_ids, $role, $user_id)
	{
//		if (!isset($campaign_ids) || (!isset($user_id) && !isset($role)))
//		{
//			return null;
//		}
//		elseif(isset($role) && (strtolower($role) == 'admin'))
//		{
//			return $campaign_ids;
//		}
//		
//		$binding_array = array();
//		
//		
//		$sub_query = " AND lower(lap.role) = ? ";
//		if (isset($user_id) && $user_id != null)
//		{
//			$sub_query = " AND lap.user_id = ? ";
//			$binding_array[] = $user_id;
//		}
//		else
//		{
//			$binding_array[] = strtolower($role);
//		}
//		
//		$query =
//			"SELECT 
//				cmp.id
//			FROM 
//				Campaigns AS cmp
//			JOIN 
//				pl_lift_advertiser_permission AS lap
//			ON 
//				cmp.business_id = lap.advertiser_id
//			WHERE
//				lap.has_access = 1
//			$sub_query	
//			AND
//				cmp.id IN ($campaign_ids)
//			";
//		
//		$result = $this->db->query($query, $binding_array);
//		
//		error_log("######## 1 #########");
//		
//		
//		if ($result->num_rows() > 0)
//		{
//			error_log("######## 2 #########");
//			$cmp_ids = "";
//			foreach ($result->result_array() as $row)
//			{
//				$cmp_ids = $cmp_ids.$row['id'].",";
//			}			
//			$cmp_ids = rtrim($cmp_ids,",");
//			return $cmp_ids;
//		}
//		elseif (isset($user_id) && $user_id != null)
//		{
//			error_log("######## 3 #########");
//			//If user id or role doesn't have entry in pl_lift_advertiser_permission, they will have access to all advertisers.
//			$binding_array = array();
//			$binding_array[] = $user_id;
//			
//			$query =
//				"SELECT 
//					*
//				FROM 
//					pl_lift_advertiser_permission AS lap
//				WHERE
//					lap.user_id = ? 	
//				";
//			
//			$result = $this->db->query($query, $binding_array);
//			
//			if ($result->num_rows() == 0)
//			{
//				error_log("######## no entry for user #########");
//				return $campaign_ids;
//			}
//			else
//			{
//				error_log("######## 4 #########");
//			}
//		}
//
//		return null;
	}
}


?>
