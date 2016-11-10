<?php

class Strategies_model extends CI_Model
{
	public function __construct()
	{
		$this->load->database();
	}

	public function get_strategy_info($partner_id, $industry_id = false)
	{
		$bindings = array($partner_id);

		$sql = 
		"	SELECT cps.*
			FROM cp_strategies cps
			JOIN cp_strategies_join_wl_partner_details cpj
					ON cpj.cp_strategies_id = cps.id
			JOIN (
				SELECT 
					wph.ancestor_id AS id
				FROM wl_partner_hierarchy wph 
				JOIN cp_strategies_join_wl_partner_details cpj
					ON wph.ancestor_id = cpj.wl_partner_details_id
				WHERE 
					wph.descendant_id = ?
				GROUP BY 
					wph.ancestor_id
				ORDER BY 
					path_length ASC
                LIMIT 1
			) AS partner
				ON partner.id = cpj.wl_partner_details_id";
		$order_sql = "\nORDER BY cps.display_order";

		// If there's an industry tag passed, use it as a filter
		$industry_sql = "";
		if ($industry_id)
		{
			$industry_sql = 
			"	WHERE
					cps.id NOT IN (
						SELECT DISTINCT cp_strategies_id
						FROM cp_strategies_join_freq_industry_tags
						WHERE cp_strategies_id NOT IN (
							SELECT cp_strategies_id
							FROM cp_strategies_join_freq_industry_tags
							WHERE freq_industry_tags_id = ?
						)
					)
			";
			$bindings[] = $industry_id;
		}

		$result = $this->db->query($sql.$industry_sql.$order_sql, $bindings);
		$strategies = $result->result_array();

		foreach ($strategies as &$strategy)
		{
			$strategy['cost_per_unit_required'] = $strategy['cost_per_unit_required'] === "1";
			$strategy['products'] = $this->get_products_by_strategy_id($strategy['id']);
			foreach($strategy['products'] as &$product)
			{
				$product['definition'] = json_decode($product['definition']);
			}
		}

		return $strategies;
	}

	public function get_industry_info_by_strategy($strategy_id)
	{
		$sql = 
		"	SELECT freq_industry_tags_id
			FROM cp_strategies_join_freq_industry_tags
			WHERE cp_strategies_id = ?
		";
		$result = $this->db->query($sql, $strategy_id);
		return array_reduce($result->result_array(), function($ids, $industry){
			$ids[] = $industry['freq_industry_tags_id'];
			return $ids;
		}, []);
	}

	public function get_products_by_strategy_id($strategy_id)
	{
		$query = 
		"	SELECT
				cpp.*
			FROM
				cp_products AS cpp
				JOIN cp_strategies_join_cp_products AS cpj
					ON cpp.id = cpj.cp_products_id
			WHERE
				cpj.cp_strategies_id = ?
			ORDER BY
				cpp.display_order ASC";

		$response = $this->db->query($query, $strategy_id);
		return $response->result_array();
	}
}