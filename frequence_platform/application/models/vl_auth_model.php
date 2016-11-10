<?php

class Vl_auth_model extends CI_Model 
{
	public function __construct()
	{
		$this->load->database();
	}

	public function get_cname_for_user($user_id)
	{
		$cname = null;

    $sql = ' 
      SELECT 
        pd.cname AS cname
      FROM
        wl_partner_details AS pd
        JOIN (
          SELECT
            u.id AS user_id,
            IF(
              u.role = \'BUSINESS\',
              (
                SELECT 
                  u_sales.partner_id AS partner_id
                FROM users u_inner
                  JOIN Advertisers AS a ON (u_inner.advertiser_id = a.id)
                  JOIN users AS u_sales ON (a.sales_person = u_sales.id)
                WHERE u_inner.id = u.id
              ),
              u.partner_id
            ) AS partner_id
          FROM
            users AS u
        ) AS u_outer ON (u_outer.partner_id = pd.id)
      WHERE
        u_outer.user_id = ?
    ';

		$bindings = array($user_id);
		$response = $this->db->query($sql, $bindings);

		if($response->num_rows() > 0)
		{
			$row = $response->row();
			$cname = $row->cname;
		}

		return $cname;
	}

	public function does_host_match_partner($host, $user_id)
	{
		$does_host_match = false;

		$split_host = explode('.', $host);
		$first_element = $split_host[0];
		
		$cname = $this->get_cname_for_user($user_id);

		if(!empty($cname) && $cname == $first_element)
		{
			$does_host_match = true;
		}
		else
		{
			$does_host_match = false;
		}

		return $does_host_match;
	}

	function get_user_by_email($email)
	{
		$sql = "
			SELECT * FROM users 
			WHERE LOWER(email) = ?
		";
		$bindings = array($email);
		$query = $this->db->query($sql, $bindings);

		if ($query->num_rows() > 0) return $query->row();
		return NULL;
	}

	function get_user_by_id($id)
	{
		$sql = "
			SELECT * FROM users 
			WHERE id = ?
		";
		$bindings = array($id);
		$query = $this->db->query($sql, $bindings);
		if ($query->num_rows() > 0) return $query->row();
		return NULL;
	}

	public function get_email_html_for_partner($partner_id)
	{
		$query = 
		"	SELECT 
				wec.html_file_path
			FROM 
				wl_welcome_email_creatives AS wec
			INNER JOIN 
				wl_partner_details AS pd
				ON 
					pd.email_html_id = wec.id
			WHERE 
				pd.id = ?
			LIMIT 1
		";
		$result = $this->db->query($query, $partner_id);
		if($result->num_rows() > 0)
		{
			return $result->row_array()['html_file_path'];
		}
		return null;
	}

}
