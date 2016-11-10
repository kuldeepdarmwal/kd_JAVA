<?php
class Rtg_model extends CI_Model 
{
  public function __construct()
  {
    $this->load->database();
  }
  public function get_adset()
  {
    $sql = "SELECT * FROM rtg_adsets WHERE hidden = 0";
    $query = $this->db->query($sql);
    if($query->num_rows() > 0)
      {
	return $query->result_array();
      }
    return NULL;
  }
  public function get_sites()
  {
    $sql = "SELECT * FROM rtg_sites WHERE hidden = 0";
    $query = $this->db->query($sql);
    if($query->num_rows() > 0)
      {
	return $query->result_array();
      }
    return NULL;
  }
}