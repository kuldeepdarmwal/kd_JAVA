<?php
class Video_ad_model extends CI_Model
{

	static $THIRD_PARTY_SOURCE_ID = '3';

	public function __construct()
	{
		$this->load->database();
	}

	/*
	 * @param Array $advertiser_frq_ids
	 */
	public function filter_accounts_with_spots_from_advertisers_frq_id($advertiser_frq_ids)
	{
		$advertiser_frq_ids = array_map(function($id) {
			return intval($id);
		}, $advertiser_frq_ids);

		$advertiser_frq_id_binding_marks = implode(',', array_fill(0, count($advertiser_frq_ids), '?'));

		$select_spectrum_tv_accounts = "
			SELECT
				tajtpa.frq_third_party_account_id,
				tsta.name
			FROM
				tp_spectrum_tv_accounts AS tsta
			JOIN tp_advertisers_join_third_party_account AS tajtpa
				ON (tsta.frq_id = tajtpa.frq_third_party_account_id AND tajtpa.third_party_source = " . self::$THIRD_PARTY_SOURCE_ID . ")
			JOIN tp_video_creatives AS tvc
				ON (tajtpa.frq_third_party_account_id = tvc.frq_third_party_account_id AND tajtpa.third_party_source = tvc.third_party_source_id)
			WHERE
				tajtpa.frq_advertiser_id IN ($advertiser_frq_id_binding_marks)
			GROUP BY tajtpa.frq_third_party_account_id
			ORDER BY
				tsta.name ASC";

		$select_spectrum_tv_accounts_result = $this->db->query($select_spectrum_tv_accounts, $advertiser_frq_ids);

		return $select_spectrum_tv_accounts_result->result_array();
	}

	/*
	 * @param Array $frq_third_party_account_ids
	 * @param int $offset
	 * @param int $count
	 */
	public function get_creatives_by_accounts($frq_third_party_account_ids, $offset = 0, $count = 120)
	{
		$bindings = [];

		if(empty($frq_third_party_account_ids))
		{
			return [];
		}

		$account_condition_markers = implode(',', array_fill(0, count($frq_third_party_account_ids), '?'));
		$bindings = array_merge($bindings, $frq_third_party_account_ids);

		$bindings[] = $offset;
		$bindings[] = $count;

		$select_video_creatives = "
			SELECT
				tvc.id AS video_creative_id,
				tvc.name,
				tvc.link_mp4,
				tvc.link_webm,
				tvc.link_thumb
			FROM
				tp_video_creatives AS tvc
			WHERE
				status='complete'
			AND
				tvc.frq_third_party_account_id IN ($account_condition_markers)
			ORDER BY
				tvc.last_active_date DESC
			LIMIT
				?,?;
			";

		$select_video_creatives_result = $this->db->query($select_video_creatives, $bindings);

		return $select_video_creatives_result->result_array();
	}

	/*
	 * @param string $video_id
	 * @param integer $user_id
	 * @param string $user_role
	 * @param boolean $user_is_group_super
	 */
	public function get_creative($video_id, $user_id, $user_role, $user_is_group_super)
	{
		$hierarchy_from_sql;
		$hierarchy_where_sql;
		$hierarchy_bindings;

		$this->get_advertisers_relationship_sql(
			$hierarchy_from_sql,
			$hierarchy_where_sql,
			$hierarchy_bindings,
			$user_id,
			$user_role,
			$user_is_group_super
		);

		$select_video_creative = "
			/* Get Video */
			SELECT
				tvc.id,
				adv.name AS advertiser_name,
				tvc.frq_third_party_account_id,
				tvc.link_mp4,
				tvc.link_webm,
				tvc.link_thumb
			FROM
				$hierarchy_from_sql
				JOIN tp_advertisers_join_third_party_account AS tajtpa ON (adv.id = tajtpa.frq_advertiser_id AND tajtpa.third_party_source = " . self::$THIRD_PARTY_SOURCE_ID . ")
				JOIN tp_video_creatives AS tvc ON (tvc.id = ? AND tajtpa.frq_third_party_account_id = tvc.frq_third_party_account_id AND tajtpa.third_party_source = tvc.third_party_source_id)
			WHERE
				$hierarchy_where_sql
			GROUP BY
				adv.id
		";

		$bindings = [$video_id];
		$bindings = array_merge($bindings, $hierarchy_bindings);

		$select_video_creatives_result = $this->db->query($select_video_creative, $bindings);

		$result = $select_video_creatives_result->result_array();
		if(!empty($result))
		{
			return $result[0];
		}
		return NULL;
	}

	public function get_advertiser_id_from_video_id($video_id)
	{
		$select_advertiser_id = "
			SELECT
				tajtpa.frq_advertiser_id AS advertiser_id
			FROM
				tp_advertisers_join_third_party_account AS tajtpa
			JOIN tp_video_creatives AS tvc
				ON (tajtpa.frq_third_party_account_id = tvc.frq_third_party_account_id AND tvc.third_party_source_id = " . self::$THIRD_PARTY_SOURCE_ID . ")
			WHERE
				tvc.id = ?
			";

		$select_advertiser_id = $this->db->query($select_advertiser_id, $video_id);

		$result_row = $select_advertiser_id->row_array();
		if(!empty($result_row))
		{
			return $result_row['advertiser_id'];
		}
		return NULL;
	}

	/*
	 * @param integer $video_ad_id (NULL if using $preview_key)
	 * @param string $preview_key (optional)
	 * @returns array $video_ad_data (or NULL in case of failure)
	 */
	public function get_video_ad_data($video_ad_id, $preview_key = NULL)
	{
		if($video_ad_id)
		{
			$condition = 'id = ?';
			$bindings = [$video_ad_id];
		}
		else if($preview_key)
		{
			$condition = 'preview_key = ?';
			$bindings = [$preview_key];
		}
		else
		{
			return NULL;
		}

		$select_video_ad_config_query = "
			SELECT * FROM
				video_ads
			WHERE
				$condition;
			";

		$video_ad_data = NULL;
		if($select_video_ad_config_result = $this->db->query($select_video_ad_config_query, $bindings))
		{
			$rows = $select_video_ad_config_result->result_array();
			if(!empty($rows))
			{
				$video_ad_data = $rows[0];
			}
		}

		return $video_ad_data;
	}

	/*
	 * @param array $video_ad_data_to_save
	 * @returns integer $video_ad_id (or NULL in case of failure)
	 */
	public function save_video_ad_data($video_ad_data_to_save)
	{
		if(!isset($video_ad_data_to_save['preview_url']))
		{
			$video_ad_data_to_save['preview_url'] = NULL;
		}

		$bindings = [
			$video_ad_data_to_save['user_id'],
			$video_ad_data_to_save['config_json'],
			$video_ad_data_to_save['template_id'],
			$video_ad_data_to_save['advertiser_id'],
			$video_ad_data_to_save['video_source_id'],
			$video_ad_data_to_save['demo_title'],
			$video_ad_data_to_save['preview_key'],
			$video_ad_data_to_save['preview_url']
		];

		$binding_placeholders = implode(',', array_fill(0, count($bindings), '?'));

		$insert_video_ad_query = "
			INSERT INTO
				video_ads
				(
					user_id,
					config_json,
					template_id,
					advertiser_id,
					video_source_id,
					demo_title,
					preview_key,
					preview_url
				)
			VALUES
				($binding_placeholders)
			ON DUPLICATE KEY UPDATE
				config_json = VALUES(config_json),
				template_id = VALUES(template_id),
				demo_title = VALUES(demo_title)
				;
			";

		$video_ad_id = NULL;
		if($insert_video_ad_result = $this->db->query($insert_video_ad_query, $bindings))
		{
			$video_ad_id = $this->db->insert_id();
			if(!$video_ad_id)
			{
				$video_ad = $this->get_video_ad_data(NULL, $video_ad_data_to_save['preview_key']);
				$video_ad_id = $video_ad['id'];
			}
		}

		return $video_ad_id;
	}

	/*
	 * @param string $name
	 * @param string $template
	 * @param string $thumbnail_url
	 * @returns integer $template_id (or NULL in case of failure)
	 */
	public function save_template($name, $template, $thumbnail_url)
	{
		$bindings = [$name, $template, $thumbnail_url];

		$insert_video_ad_template_query = "
			INSERT INTO
				video_ad_templates
				(name, template, thumbnail_url)
			VALUES
				(?, ?, ?);
			";

		$template_id = NULL;
		if($insert_video_ad_template_result = $this->db->query($insert_video_ad_template_query, $bindings))
		{
			$template_id = $this->db->insert_id();
		}

		return $template_id;
	}

	/*
	 * @param string $id
	 * @returns array $template_data (or NULL if no match)
	 */
	public function get_template($id)
	{
		$bindings = [$id];

		$select_video_ad_template_query = "
			SELECT * FROM
				video_ad_templates
			WHERE
				id = ?;
			";

		$template_data = NULL;
		if($select_video_ad_template_result = $this->db->query($select_video_ad_template_query, $bindings))
		{
			$rows = $select_video_ad_template_result->result_array();
			if(!empty($rows))
			{
				$template_data = $rows[0];
			}
		}

		return $template_data;
	}

	/*
	 * @returns array of all $template_data
	 */
	public function get_all_templates()
	{
		$select_all_video_ad_templates_query = "
			SELECT * FROM
				video_ad_templates;
			";

		$all_template_data = NULL;
		$select_video_ad_template_result = $this->db->query($select_all_video_ad_templates_query);
		if($select_video_ad_template_result->num_rows())
		{
			$all_template_data = $select_video_ad_template_result->result_array();
		}

		return $all_template_data;
	}

	/*
	 * @returns array of all $template_data
	 */
	public function get_active_presets_with_templates()
	{
		$select_all_video_ad_presets_query = "
			SELECT vap.*, vat.template FROM
				video_ad_presets AS vap
			JOIN
				video_ad_templates AS vat
			ON
				vat.id = vap.template_id
			WHERE
				vat.active = 1
			AND
				vap.active = 1
			ORDER BY
				vat.id DESC;
			";

		$all_preset_data = NULL;
		$select_video_ad_preset_result = $this->db->query($select_all_video_ad_presets_query);
		if($select_video_ad_preset_result->num_rows())
		{
			$all_preset_data = $select_video_ad_preset_result->result_array();
		}

		return $all_preset_data;
	}

	public function get_advertisers_relationship_sql(
		&$from_sql,
		&$where_sql,
		&$bindings,
		$user_id,
		$user_role,
		$is_group_super
	)
	{
		$lower_role = strtolower($user_role);

		if($lower_role == 'sales' && $is_group_super)
		{
			$from_sql = "
				users AS hierarchy_current_user
				LEFT JOIN wl_partner_owners AS hierarchy_po
					ON hierarchy_current_user.id = hierarchy_po.user_id
				JOIN wl_partner_hierarchy AS hierarchy_ph
					ON (hierarchy_current_user.partner_id = hierarchy_ph.ancestor_id OR hierarchy_po.partner_id = hierarchy_ph.ancestor_id)
				JOIN wl_partner_details AS hierarchy_pd
					ON hierarchy_ph.descendant_id = hierarchy_pd.id
				JOIN users AS hierarchy_sales_person
					ON hierarchy_pd.id = hierarchy_sales_person.partner_id
				JOIN Advertisers AS adv
					ON hierarchy_sales_person.id = adv.sales_person
			";

			$where_sql = "
				hierarchy_current_user.id = ?
			";

			$bindings = array($user_id);
		}
		elseif($lower_role == 'sales' && !$is_group_super)
		{
			$from_sql = "
				users AS hierarchy_current_user
				JOIN wl_partner_owners AS hierarchy_po
					ON hierarchy_current_user.id = hierarchy_po.user_id
				JOIN wl_partner_hierarchy AS hierarchy_ph
					ON hierarchy_po.partner_id = hierarchy_ph.ancestor_id
				JOIN wl_partner_details AS hierarchy_pd
					ON hierarchy_ph.descendant_id = hierarchy_pd.id
				JOIN users AS hierarchy_sales_person
					ON hierarchy_pd.id = hierarchy_sales_person.partner_id
				RIGHT JOIN Advertisers AS adv
					ON hierarchy_sales_person.id = adv.sales_person
			";
			$where_sql = "
					(hierarchy_current_user.id = ? OR adv.sales_person = ?)
			";
			$bindings = array($user_id, $user_id);
		}
		elseif($lower_role == 'admin' || $lower_role == 'ops' || $lower_role == 'creative')
		{
			$from_sql = "
				Advertisers AS adv
			";
			$where_sql = "
				1
			";
			$bindings = array();
		}
		elseif($lower_role == 'business')
		{
			$from_sql = "
				Advertisers AS adv
			";
			$where_sql = "
				adv.sales_person = ?
			";
			$bindings = array($user_id);
		}
		elseif($lower_role == 'client' || $lower_role == 'agency')
		{
			$from_sql = "
				clients_to_advertisers AS hierarchy_cta
				JOIN Advertisers AS adv
					ON adv.id = hierarchy_cta.advertiser_id
			";
			$where_sql = "
				hierarchy_cta.user_id = ?
			";
			$bindings = array($user_id);
		}
		else
		{
			throw new exception("Unknown role: $user_role");
		}
	}

	public function increment_view_count($sample_video_ad_id)
	{
		$increment_view_count_sql = "
			UPDATE
				video_ads
			SET
				view_count = view_count + 1
			WHERE
				id = ?
		";

		return $this->db->query($increment_view_count_sql, $sample_video_ad_id);
	}

	public function get_advertiser_and_sales_info_for_email($advertiser_id)
	{
		$get_sales_person_email_sql = "
			SELECT
				sales_user.firstname,
				sales_user.lastname,
				sales_user.email,
				adv.name AS advertiser_name,
				sales_partner.cname AS sales_partner_cname
			FROM
				users AS sales_user
				JOIN Advertisers AS adv ON (sales_user.id = adv.sales_person)
				JOIN wl_partner_details AS sales_partner ON (sales_user.partner_id = sales_partner.id)
			WHERE
				adv.id = ?
		";

		if($get_sales_person_email_result = $this->db->query($get_sales_person_email_sql, $advertiser_id))
		{
			if($get_sales_person_email_result->num_rows())
			{
				return $get_sales_person_email_result->result_array()[0];
			}
		}

		return FALSE;
	}

	public function get_user_info($user_id)
	{
		$sql = "
			SELECT
				id,
				role,
				isGroupSuper
			FROM users
			WHERE users.id = ?
		";
		$response = $this->db->query($sql, $user_id);
		return $response->row();
	}


	public function get_sample_ad_manager_table_data($user_id, $user_role, $user_is_group_super, $user_has_edit_permission)
	{
		$hierarchy_from_sql;
		$hierarchy_where_sql;
		$hierarchy_bindings;

		$this->get_advertisers_relationship_sql(
			$hierarchy_from_sql,
			$hierarchy_where_sql,
			$hierarchy_bindings,
			$user_id,
			$user_role,
			$user_is_group_super
		);

		$has_sample_ads_where_sql = '';
		if(!$user_has_edit_permission)
		{
			$has_sample_ads_where_sql = ' AND video_ads.id IS NOT NULL';
		}

		$sql = "
			/* Video Ad Manager */
			SELECT
				adv.Name AS advertiser_name,
				adv.id AS advertiser_id,
				tajtpa.frq_third_party_account_id,
				sales_user.email AS sales_user_email,
				sales_partner.partner_name AS partner_name,
				sa.eclipse_id AS client_id, /* Needs to be processed to remove 10X prefix and derive Traffic System */
				cmp.id IS NULL AS needs_digital,
				video_ads.id AS sample_ad_id,
				video_ads.video_source_id AS video_id,
				video_ads.demo_title,
				DATE_FORMAT(video_ads.created, '%c/%e/%Y') AS created_date,
				UNIX_TIMESTAMP(video_ads.created) AS created_timestamp,
				DATE_FORMAT(video_ads.latest_change_date, '%c/%e/%Y') AS modified_date,
				UNIX_TIMESTAMP(video_ads.latest_change_date) AS modified_timestamp,
				author_user.email AS author_email,
				video_ads.preview_key,
				video_ads.preview_url,
				sales_partner.cname AS sales_partner_cname,
				video_ads.view_count,
				CONCAT_WS(',', business_emails.emails, client_emails.emails) AS advertiser_emails
			FROM
				$hierarchy_from_sql
				JOIN tp_advertisers_join_third_party_account AS tajtpa ON (adv.id = tajtpa.frq_advertiser_id AND tajtpa.third_party_source = " . self::$THIRD_PARTY_SOURCE_ID . ")
				JOIN users AS sales_user ON (adv.sales_person = sales_user.id)
				JOIN wl_partner_details AS sales_partner ON (sales_user.partner_id = sales_partner.id)
				JOIN tp_spectrum_accounts AS sa ON (adv.id = sa.advertiser_id)
				JOIN tp_video_creatives AS tvc ON (tajtpa.frq_third_party_account_id = tvc.frq_third_party_account_id AND tajtpa.third_party_source = tvc.third_party_source_id)
				LEFT JOIN video_ads ON (adv.id = video_ads.advertiser_id)
				LEFT JOIN users AS author_user ON (video_ads.user_id = author_user.id)
				LEFT JOIN ( /* Only need one campaign per advertiser */
					SELECT
						cmp_inner.id,
						cmp_inner.business_id
					FROM
						Campaigns AS cmp_inner
					JOIN AdGroups AS ag_inner ON (cmp_inner.id = ag_inner.campaign_id)
					WHERE DATEDIFF(CURDATE(), ag_inner.latest_city_record) > 180
					GROUP BY cmp_inner.business_id
				) AS cmp ON (adv.id = cmp.business_id)
				LEFT JOIN (
					SELECT
						GROUP_CONCAT(advertiser_user.email) as emails,
						advertiser_user.advertiser_id
					FROM
						users AS advertiser_user
					WHERE
						advertiser_user.role = 'BUSINESS'
					AND
						advertiser_user.banned = 0
					GROUP BY advertiser_user.advertiser_id
				) AS business_emails ON (adv.id = business_emails.advertiser_id)
				LEFT JOIN (
					SELECT
						GROUP_CONCAT(client_user.email) as emails,
						cta.advertiser_id
					FROM
						users AS client_user
					JOIN clients_to_advertisers AS cta ON (client_user.id = cta.user_id)
					WHERE
						client_user.role = 'CLIENT'
					AND
						client_user.banned = 0
					GROUP BY cta.advertiser_id
				) AS client_emails ON (adv.id = client_emails.advertiser_id)
			WHERE
				$hierarchy_where_sql
				$has_sample_ads_where_sql
			GROUP BY
				adv.id,
				video_ads.id
			ORDER BY
				adv.id ASC, video_ads.created DESC
		";

		$bindings = $hierarchy_bindings;
		$response = $this->db->query($sql, $bindings);

		$query_results = $response->result();

		$advertisers = array();

		$current_advertiser_id = -1;
		$current_advertiser = array();
		$traffic_system_code_string_length = 3;
		foreach($query_results as $sample_ad)
		{
			$traffic_system_number = substr($sample_ad->client_id, 0, $traffic_system_code_string_length);
			$traffic_system_name = '';
			switch($traffic_system_number)
			{
				default:
				case 100:
					$traffic_system_name = 'Unknown';
					break;
				case 101:
					$traffic_system_name = 'Central Pacific';
					break;
				case 102:
					$traffic_system_name = 'Mid North';
					break;
				case 103:
					$traffic_system_name = 'Southeast';
					break;

			}

			if($sample_ad->advertiser_id !== $current_advertiser_id)
			{
				$current_advertiser_id = $sample_ad->advertiser_id;
				unset($current_advertiser);
				$current_advertiser = array(
					'advertiser_name'            => $sample_ad->advertiser_name,
					'frq_third_party_account_id' => $sample_ad->frq_third_party_account_id,
					'advertiser_emails'          => $sample_ad->advertiser_emails,
					'sales_email'                => $sample_ad->sales_user_email,
					'partner_name'               => $sample_ad->partner_name,
					'client_id'                  => substr($sample_ad->client_id, $traffic_system_code_string_length),
					'traffic_system'             => $traffic_system_name,
					'needs_digital'              => $sample_ad->needs_digital,
					'spec_ads'                   => array(),
					);

				 $advertisers[] = &$current_advertiser;
			}

			if(!empty($sample_ad->sample_ad_id))
			{
				$current_advertiser['spec_ads'][] = array(
					'id' => $sample_ad->sample_ad_id,
					'title' => $sample_ad->demo_title,
					'timestamp' => $sample_ad->created_timestamp,
					'created_date' => $sample_ad->created_date,
					'created_timestamp' => $sample_ad->created_timestamp,
					'modified_timestamp' => $sample_ad->modified_timestamp,
					'modified_date' => $sample_ad->modified_date,
					'author_email' => $sample_ad->author_email,
					'preview_key' => $sample_ad->preview_key,
					'video_id' => $sample_ad->video_id,
					'demo_page_url' => 'http://' . $sample_ad->sales_partner_cname . '.' . g_second_level_domain . $sample_ad->preview_url . '/', // TODO: HTTPS if possible
					'view_count' => $sample_ad->view_count
				);
			}
		}

		return $advertisers;
	}

	/*
	 * @param string $spod_id
	 * @param integer $time (optional)
	 * @return string $video_ad_key
	 */
	public function create_video_ad_key($video_id, $time = NULL)
	{
		if($time === NULL)
		{
			$time = time();
		}
		$data = array($video_id, $time);
		return $this->video_ad_model->obfuscate(implode(',', $data));
	}

	public function obfuscate($data)
	{
		$base64_data = base64_encode($data);
		$random_byte_position = 19;
		$extra_3_bytes = 'MDY'; // use the same 3 extra bytes, so that it can be reversed
		$obfuscated_data = substr_replace($base64_data, $extra_3_bytes, $random_byte_position, 0); // add 3 random bytes
		$obfuscated_data = str_replace('/', '-', $obfuscated_data); // make it not look like a directory
		return $obfuscated_data;
	}

	public function de_obfuscate($obfuscated_data)
	{
		$random_byte_position = 19;
		$random_byte_length = 3;
		$base64_data = substr_replace($obfuscated_data, '', $random_byte_position, $random_byte_length); // remove 3 random bytes
		$base64_data = str_replace('-', '/', $base64_data); // restore any slashes
		$data = base64_decode($base64_data);
		return $data;
	}

	/*
	 * @param string $video_ad_key
	 * @return array[video_source_id, time]
	 */
	public function parse_video_ad_key($video_ad_key)
	{
		$plain_text = $this->video_ad_model->de_obfuscate($video_ad_key);
		$data = explode(',', $plain_text);

		return array(
			'video_source_id' => $data[0],
			'time' => $data[1],
		);
	}

	public function initial_sample_ad_data_fix()
	{
		$get_sample_ads_sql = "
			SELECT
				*
			FROM
				video_ads
		";

		$sample_ads_response = $this->db->query($get_sample_ads_sql);
		$sample_ads_count = $sample_ads_response->num_rows();
		$sample_ads = $sample_ads_response->result();

		$spot_id_set = array();
		foreach($sample_ads as &$sample_ad)
		{
			$ad_data = $this->video_ad_model->parse_video_ad_key($sample_ad->preview_key);
			// TODO: handle now inconsistent first segment (was tvc.third_party_video_id, now tvc.id)
			$spot_id = (int)$ad_data['video_source_id'];
			$spot_id_set[] = $spot_id;
			$sample_ad->video_source_id = $spot_id;
			$sample_ad->latest_change_date = $sample_ad->created;

			$config = json_decode($sample_ad->config_json);
			if(!empty($config))
			{
				if(property_exists($config, 'advertiser_name'))
				{
					$sample_ad->demo_title = $config->advertiser_name;
				}
			}
		}

		$spot_ids_placeholder = rtrim(str_repeat("?,", $sample_ads_count), ",");

		$resolve_sample_ad_data_sql = "
			SELECT
				advertiser_id,
				my_spot_id AS spot_id
			FROM (
				SELECT
					sa.advertiser_id,
					sts.spot_id AS my_spot_id
				FROM
					tp_spectrum_tv_schedule AS sts
					JOIN tp_spectrum_accounts AS sa ON (sts.account_ul_id = sa.ul_id)
				WHERE
					sts.spot_id IN ($spot_ids_placeholder)
				UNION ALL
				SELECT
					sa.advertiser_id,
					sts.bookend_top_id AS my_spot_id
				FROM
					tp_spectrum_tv_schedule AS sts
					JOIN tp_spectrum_accounts AS sa ON (sts.account_ul_id = sa.ul_id)
				WHERE
					sts.bookend_top_id IN ($spot_ids_placeholder)
				UNION ALL
				SELECT
					sa.advertiser_id,
					sts.bookend_bottom_id AS my_spot_id
				FROM
					tp_spectrum_tv_schedule AS sts
					JOIN tp_spectrum_accounts AS sa ON (sts.account_ul_id = sa.ul_id)
				WHERE
					sts.bookend_bottom_id IN ($spot_ids_placeholder)
			) AS all_spot_ids
			GROUP BY my_spot_id
			ORDER BY my_spot_id
		";

		$resolve_sample_ad_data_bindings = array_merge(
			$spot_id_set,
			$spot_id_set,
			$spot_id_set
		);

		$resolve_sample_ad_data_response = $this->db->query($resolve_sample_ad_data_sql, $resolve_sample_ad_data_bindings);
		$resolve_sample_ad_data_result = $resolve_sample_ad_data_response->result();

		$map_spot_id_to_advertiser = array();
		foreach($resolve_sample_ad_data_result as $row)
		{
			$map_spot_id_to_advertiser[$row->spot_id] = $row->advertiser_id;
		}

		$map_count = count($map_spot_id_to_advertiser);

		$num_values_per_row = 5;  // Must match $update_video_ads_sql
		$video_ad_value_set_placeholder = '('.rtrim(str_repeat('?,', $num_values_per_row), ',').'),';

		$update_video_ads_values_sql = "";
		$update_video_ads_values_bindings = array();
		foreach($sample_ads as &$sample_ad)
		{
			if(array_key_exists($sample_ad->video_source_id, $map_spot_id_to_advertiser))
			{
				$sample_ad->advertiser_id = $map_spot_id_to_advertiser[$sample_ad->video_source_id];
			}
			else
			{
				$sample_ad->advertiser_id = NULL;
				echo "Unable to resolve spot_id: {$sample_ad->video_source_id} for video_ads.id: {$sample_ad->id} , demo_title: {$sample_ad->demo_title}\n";
			}

			$update_video_ads_values_sql .= $video_ad_value_set_placeholder;

			$update_video_ads_values_bindings[] = $sample_ad->id;
			$update_video_ads_values_bindings[] = $sample_ad->advertiser_id;
			$update_video_ads_values_bindings[] = $sample_ad->video_source_id;
			$update_video_ads_values_bindings[] = $sample_ad->latest_change_date;
			$update_video_ads_values_bindings[] = $sample_ad->demo_title;
		}

		$update_video_ads_values_sql = rtrim($update_video_ads_values_sql, ',');

		$update_video_ads_sql = "
			INSERT INTO video_ads (id, advertiser_id, video_source_id, latest_change_date, demo_title)
			VALUES
			$update_video_ads_values_sql
			ON DUPLICATE KEY UPDATE
				advertiser_id = VALUES(advertiser_id),
				video_source_id = VALUES(video_source_id),
				latest_change_date = VALUES(latest_change_date),
				demo_title = VALUES(demo_title)
		";

		$is_success = $this->db->query($update_video_ads_sql, $update_video_ads_values_bindings);
		$mysql_affected = $this->db->affected_rows();
		$num_affected = $mysql_affected / 2;

		echo "num affected: $num_affected, num expected: $sample_ads_count\n";
	}

	public function get_advertiser_emails($advertiser_id)
	{
		$advertiser_email_query = "
			SELECT
				users.email
			FROM
				users,
				JOIN Advertisers as adv ON (users.id = adv.user_id)
			WHERE
				adv.id = ?
		";

		$advertiser_email_result = $this->db->query($advertiser_email_query, $advertiser_id);

		return $advertiser_email_result->result_array();
	}

}
