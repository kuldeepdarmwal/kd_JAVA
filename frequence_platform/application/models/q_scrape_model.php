<?php
class Q_scrape_model extends CI_Model 
{
	public function __construct()
	{
		$this->load->database();
	}
	public function get_missing_sites()
	{
	  //$sql = 'SELECT DISTINCT Base_SiteFROM SiteRecords WHERE Base_Site NOT IN (SELECT DISTINCT Domain FROM DemographicRecords_01_20_2012) ORDER BY Base_Site LIMIT 200';
	  //$sql = "SELECT DISTINCT b.Base_Site FROM DemographicRecords_01_20_2012 a RIGHT JOIN SiteRecords b ON a.Domain = b.Base_Site WHERE a.Domain IS NULL LIMIT 200";
	  $sql = "SELECT DISTINCT a.Base_Site FROM SiteRecords a LEFT JOIN DemographicRecords_01_20_2012 b ON a.Base_Site = b.Domain WHERE b.Domain IS NULL ORDER BY Base_Site LIMIT 50";
	  $query = $this->db->query($sql);
	  if($query->num_rows() > 0)
	    {
	      return $query;
	    }
	  return NULL;
	}
	public function insert_demographic($demos)
	{
	 $sql = 'Replace INTO DemographicRecords_01_20_2012 (Domain, Reach, Gender_Male, Gender_Female, Age_Under18, Age_18_24, Age_25_34, Age_35_44, Age_45_54, Age_55_64, Age_65, Race_Cauc, Race_Afr_Am, Race_Asian, Race_Hisp, Race_Other, Kids_No, Kids_Yes, Income_0_50, Income_50_100, Income_100_150, Income_150, College_No, College_Under, College_Grad, Average_Flag)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
	 $query = $this->db->query($sql, $demos);
	 if($this->db->affected_rows() > 0)
	   {
	     return TRUE;
	   }
	 return FALSE;
	}
	
	
}