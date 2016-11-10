<?php

class Dfp_model extends CI_Model 
{
	public function __construct()
	{
		$this->load->database();
	}

	public function save_dfp_order_as_template($order, $name)
	{
		$order->id = 0;
		$order->name = "TEMPLATE - ".$name;

		$encoded = json_encode($order);

		$create_template_query =
		"INSERT INTO
			dfp_object_templates
				(name, object_blob, type)
		VALUES
			(?, ?, 0)
		";
		return $this->db->query($create_template_query, array($name, $encoded));
	}
	
	public function save_dfp_line_item_as_template($line_item, $name)
	{
		$line_item->id = 0;
		$line_item->name = "TEMPLATE - ".$name;

		$encoded = json_encode($line_item);

		$create_template_query =
		"INSERT INTO
			dfp_object_templates
				(name, object_blob, type)
		VALUES
			(?, ?, 1)
		";
		return $this->db->query($create_template_query, array($name, $encoded));
	}

}


?>
