<?php
class comscore_integration_model extends CI_Model 
{
	public $db_to_comscore_array;
	private $COMSCORE_US_GEO_ID=840;
	
	public function __construct()
	{
		$this->load->database();
		
		$this->db_to_comscore_array=array();
		$this->db_to_comscore_array['comscore_geo_main_id']='comscore_geo_main_id';
		$this->db_to_comscore_array['comscore_site_id']='comscore_site_id';
		$this->db_to_comscore_array['total_audience']='Total Audience';
		$this->db_to_comscore_array['male_all']=  'All Males';
		$this->db_to_comscore_array['male_over6']='Males: 6+';
		$this->db_to_comscore_array['male_over15']='Males: 15+';
		$this->db_to_comscore_array['male_over18']='Males: 18+';
		$this->db_to_comscore_array['male_over21']='Males: 21+';
		$this->db_to_comscore_array['male_over35']='Males: 35+';
		$this->db_to_comscore_array['male_over50']='Males: 50+';
		$this->db_to_comscore_array['male_over55']='Males: 55+';
		$this->db_to_comscore_array['male_over65']='Males: 65+';
		$this->db_to_comscore_array['male_2_12']='Males: 2-12';
		$this->db_to_comscore_array['male_2_14']='Males: 2-14';
		$this->db_to_comscore_array['male_2_17']='Males: 2-17';
		$this->db_to_comscore_array['male_6_12']='Males: 6-12';
		$this->db_to_comscore_array['male_6_14']='Males: 6-14';
		$this->db_to_comscore_array['male_9_14']='Males: 9-14';
		$this->db_to_comscore_array['male_13_17']='Males: 13-17';
		$this->db_to_comscore_array['male_13_24']='Males: 13-24';
		$this->db_to_comscore_array['male_13_34']='Males: 13-34';
		$this->db_to_comscore_array['male_13_49']='Males: 13-49';
		$this->db_to_comscore_array['male_18_24']='Males: 18-24';
		$this->db_to_comscore_array['male_18_34']='Males: 18-34';
		$this->db_to_comscore_array['male_18_49']='Males: 18-49';
		$this->db_to_comscore_array['male_21_34']='Males: 21-34';
		$this->db_to_comscore_array['male_21_49']='Males: 21-49';
		$this->db_to_comscore_array['male_25_34']='Males: 25-34';
		$this->db_to_comscore_array['male_25_49']='Males: 25-49';
		$this->db_to_comscore_array['male_25_54']='Males: 25-54';
		$this->db_to_comscore_array['male_35_44']='Males: 35-44';
		$this->db_to_comscore_array['male_35_49']='Males: 35-49';
		$this->db_to_comscore_array['male_35_54']='Males: 35-54';
		$this->db_to_comscore_array['male_35_64']='Males: 35-64';
		$this->db_to_comscore_array['male_45_54']='Males: 45-54';
		$this->db_to_comscore_array['male_45_64']='Males: 45-64';
		$this->db_to_comscore_array['male_55_64']='Males: 55-64';
		$this->db_to_comscore_array['female_all']=  'All Females';
		$this->db_to_comscore_array['female_over6']='Females: 6+';
		$this->db_to_comscore_array['female_over15']='Females: 15+';
		$this->db_to_comscore_array['female_over18']='Females: 18+';
		$this->db_to_comscore_array['female_over21']='Females: 21+';
		$this->db_to_comscore_array['female_over35']='Females: 35+';
		$this->db_to_comscore_array['female_over50']='Females: 50+';
		$this->db_to_comscore_array['female_over55']='Females: 55+';
		$this->db_to_comscore_array['female_over65']='Females: 65+';
		$this->db_to_comscore_array['female_2_12']='Females: 2-12';
		$this->db_to_comscore_array['female_2_14']='Females: 2-14';
		$this->db_to_comscore_array['female_2_17']='Females: 2-17';
		$this->db_to_comscore_array['female_6_12']='Females: 6-12';
		$this->db_to_comscore_array['female_6_14']='Females: 6-14';
		$this->db_to_comscore_array['female_9_14']='Females: 9-14';
		$this->db_to_comscore_array['female_13_17']='Females: 13-17';
		$this->db_to_comscore_array['female_13_24']='Females: 13-24';
		$this->db_to_comscore_array['female_13_34']='Females: 13-34';
		$this->db_to_comscore_array['female_13_49']='Females: 13-49';
		$this->db_to_comscore_array['female_18_24']='Females: 18-24';
		$this->db_to_comscore_array['female_18_34']='Females: 18-34';
		$this->db_to_comscore_array['female_18_49']='Females: 18-49';
		$this->db_to_comscore_array['female_21_34']='Females: 21-34';
		$this->db_to_comscore_array['female_21_49']='Females: 21-49';
		$this->db_to_comscore_array['female_25_34']='Females: 25-34';
		$this->db_to_comscore_array['female_25_49']='Females: 25-49';
		$this->db_to_comscore_array['female_25_54']='Females: 25-54';
		$this->db_to_comscore_array['female_35_44']='Females: 35-44';
		$this->db_to_comscore_array['female_35_49']='Females: 35-49';
		$this->db_to_comscore_array['female_35_54']='Females: 35-54';
		$this->db_to_comscore_array['female_35_64']='Females: 35-64';
		$this->db_to_comscore_array['female_45_54']='Females: 45-54';
		$this->db_to_comscore_array['female_45_64']='Females: 45-64';
		$this->db_to_comscore_array['female_55_64']='Females: 55-64';
		$this->db_to_comscore_array['hh_income_under25k']='HHI USD: Under $25K';
		$this->db_to_comscore_array['hh_income_over60k']='HHI USD: $60K+';
		$this->db_to_comscore_array['hh_income_over75k']='HHI USD: $75K+';
		$this->db_to_comscore_array['hh_income_over100k']='HHI USD: $100K+';
		$this->db_to_comscore_array['hh_income_25k_40k']='HHI USD: 25,000 - 39,999';
		$this->db_to_comscore_array['hh_income_40k_60k']='HHI USD: 40,000 - 59,999';
		$this->db_to_comscore_array['hh_income_60k_75k']='HHI USD: 60,000 - 74,999';
		$this->db_to_comscore_array['hh_income_75k_100k']='HHI USD: 75,000 - 99,999';
		$this->db_to_comscore_array['hh_income_100k_150k']='HHI USD: 100,000 - 149,999';
		$this->db_to_comscore_array['hh_income_150k_200k']='HHI USD: 150,000 - 199,999';
		$this->db_to_comscore_array['hh_income_over200k']='HHI USD: 200,000 or more';
		$this->db_to_comscore_array['region_wn_central']='Region US:West North Central';
		$this->db_to_comscore_array['region_mountain']='Region US:Mountain';
		$this->db_to_comscore_array['region_pacific']='Region US:Pacific';
		$this->db_to_comscore_array['region_new_england']='Region US:New England';
		$this->db_to_comscore_array['region_mid_atlantic']='Region US:Mid Atlantic';
		$this->db_to_comscore_array['region_s_atlantic']='Region US:South Atlantic';
		$this->db_to_comscore_array['region_es_central']='Region US:East South Central';
		$this->db_to_comscore_array['region_ws_central']='Region US:West South Central';
		$this->db_to_comscore_array['region_en_central']='Region US:East North Central';
		$this->db_to_comscore_array['children_no']='Children:No';
		$this->db_to_comscore_array['children_yes']='Children:Yes';
		$this->db_to_comscore_array['hh_size_1']='HH Size: 1';
		$this->db_to_comscore_array['hh_size_2']='HH Size: 2';
		$this->db_to_comscore_array['hh_size_3']='HH Size: 3';
		$this->db_to_comscore_array['hh_size_4']='HH Size: 4';
		$this->db_to_comscore_array['hh_size_over5']='HH Size: 5+';
		$this->db_to_comscore_array['hh_size_1_2']='HH Size: 1-2';
		$this->db_to_comscore_array['hh_size_over3']='HH Size: 3+';
		$this->db_to_comscore_array['race_black']='Race:Black';
		$this->db_to_comscore_array['race_other']='Race: Other';
		$this->db_to_comscore_array['ethnicity_non_hispanic']='Non-Hispanic';
		$this->db_to_comscore_array['ethnicity_hispanic_all']='Hispanic All';
		$this->db_to_comscore_array['hispanic_bilinugal']='Bilingual';
		$this->db_to_comscore_array['hispanic_english_primary']='English Primary';
		$this->db_to_comscore_array['hispanic_spanish_primary']='Spanish Primary';
	}
	
	// this method does following steps
	//1. loop the list of sites
	//2. create insert/update queries
	//3. returns the number of records 
	public function persist_comscore_site_data($list_of_sites)
	{
		$number_records=0;
		$query_size=10000;
		
		$query_start=
		"INSERT INTO freq_sites_main
			(url, comscore_site_id, date_added, is_manually_added_flag)
		VALUES ";
		
		$query_end=" ON DUPLICATE KEY UPDATE comscore_site_id=VALUES(comscore_site_id);";
		
		$query_middle=array();
		$bindings=array();
		
		foreach($list_of_sites as $aggregate_row)
		{
			$bindings[]=$aggregate_row[0];
			$bindings[]=$aggregate_row[1];
			$query_middle[]='(?,?,CURRENT_TIMESTAMP,0)';
		
			if (count($query_middle)==$query_size)
			{
				$query=$query_start . implode(',', $query_middle) . $query_end;
				if ($this->db->query($query, $bindings))
				{
					$number_records +=$query_size;
				}
				else
				{
					var_dump($this->db);
				}
				$query_middle=array();
				$bindings=array();
			}
		}
		
		if (count($query_middle) > 0)
		{
			$query=$query_start . implode(',', $query_middle) . $query_end;
			if ($this->db->query($query, $bindings))
			{
				$number_records+=count($query_middle);
			}
			else
			{
				var_dump($this->db);
			}
			$query_middle=array();
			$bindings=array();
		}
		return $number_records;
	}
	
	// this reads all the comscore site ids and returns an array 
	public function read_sites_table() 
	{
		$query=" SELECT 
						comscore_site_id, 
						url 
					FROM
						freq_sites_main 
					WHERE 	
						comscore_site_id 
					IS NOT NULL";
		
		$response=$this->db->query($query);
		return $response->result_array();
	}
	
	// this reads all the comscore site ids and returns an array
	public function read_sites_table_for_blank_comscore_ids()
	{
		$query= "SELECT
						url
					FROM
						freq_sites_main
					WHERE
						comscore_site_id
					IS NULL";
	
		$response=$this->db->query($query);
		$result_data=$response->result_array();
		$return_array=array();
		foreach ($result_data as $row_data)
		{
			$return_array[]=$row_data['url'];
		}
		return $return_array;
	}
	
	// get count of all the sites that are related to comscore
	public function count_sites_table()
	{
		$query=  "SELECT
						COUNT(*) count
					FROM
						freq_sites_main
					WHERE
						comscore_site_id
					IS NOT NULL";
	
		$response=$this->db->query($query);
		$result_data=$response->result_array();
		$count=0;
		foreach ($result_data as $row_data) 
		{
			$count=$row_data['count'];
			break;
		}
		return $count;
	}
	
	// get count of all the sites that are related to comscore
	public function count_sites_table_blank_comscore_id()
	{
		$query=  "SELECT
						COUNT(*) count
					FROM
						freq_sites_main
					WHERE
						comscore_site_id
					IS NULL";
	
		$response=$this->db->query($query);
		$result_data=$response->result_array();
		$count=0;
		foreach ($result_data as $row_data)
		{
			$count=$row_data['count'];
			break;
		}
		echo $count;
		return $count;
	}
	
	// get count of all the sites that are tagged to a city
	public function count_local_tagged_sites()
	{
		$query=  "SELECT 
						COUNT(*) count
					FROM
						freq_site_tagging_link,
					 	geo_place_map,
						freq_sites_main
					WHERE
						tag_id=id_auto AND
						freq_site_tagging_link.freq_sites_main_id=freq_sites_main.freq_sites_main_id AND
						tag_type_id=2";
	
		$response=$this->db->query($query);
		$result_data=$response->result_array();
		$count=0;
		foreach ($result_data as $row_data)
		{
			$count=$row_data['count'];
			break;
		}
		return $count;
	}
	
	// get count of all the geo data from comscore_geo_main table. $demo_flag is true for local !=840. and false for US
	public function count_demo_table($local_flag)
	{
		$this->load->database();
		$equals_variable='=';
		if ($local_flag) 
			$equals_variable='!=';
		
		$query=" SELECT
						COUNT(*) count
					FROM
						comscore_demo_data
					WHERE
						comscore_geo_main_id ".$equals_variable.$this->COMSCORE_US_GEO_ID;
	
		$response=$this->db->query($query);
		$result_data=$response->result_array();
		$count=0;
		foreach ($result_data as $row_data)
		{
			$count=$row_data['count'];
			break;
		}
		return $count;
	}
	
	// this method returns back an array with each row containing comscore_site_id, url and dma_id
	public function fetch_sites_city_data_for_comscore() 
	{
		
		$query=" 
		SELECT 
				m.comscore_site_id AS 'comscore_site_id', 
				r.INTPTLAT10 AS 'lat', 
				r.INTPTLON10 AS 'lng'
		FROM 
				freq_site_tagging_link lk
		JOIN 
				freq_sites_main m ON 
					m.freq_sites_main_id=lk.freq_sites_main_id
		JOIN 
				geo_place_map r ON 
					r.id_auto=lk.tag_id
		WHERE 
				lk.tag_type_id=2";
		
		$response=$this->db->query($query);
		$result_data=$response->result_array();
		$return_data_array=array();
		$counter=0;// this is important variable, dont initialize within 2nd for loop
		
		foreach ($result_data as $site_details) {
			//	$dma_query="
			// 			SELECT
			// 				comscore_geo_id AS 'comscore_geo_id', 
			// 				 (
			// 				    3959 * ACOS 
			// 					(
			// 				      COS ( RADIANS(".$site_details['lat'].") )
			// 				      * COS( RADIANS( LATITUDE ) )
			// 				      * COS( RADIANS( LONGITUDE ) - RADIANS(".$site_details['lng'].") )
			// 				      + SIN ( RADIANS(".$site_details['lat'].") )
			// 				      * SIN( RADIANS( LATITUDE ) )
			// 				    )
			// 				  ) AS DISTANCE
			// 			FROM 
			// 				comscore_geo_main 
			// 			WHERE 
			// 				comscore_geo_id !=''
			// 			ORDER BY 
			// 				DISTANCE 
			// 			";
			
			$dma_query="
			SELECT
				comscore_geo_id AS 'comscore_geo_id',
				 (
				    3959 * ACOS
					(
				      COS ( RADIANS(".$site_details['lat'].") )
				      * COS( RADIANS( LATITUDE ) )
				      * COS( RADIANS( LONGITUDE ) - RADIANS(".$site_details['lng'].") )
				      + SIN ( RADIANS(".$site_details['lat'].") )
				      * SIN( RADIANS( LATITUDE ) )
				    )
				  ) AS DISTANCE
			FROM
				comscore_geo_main
			WHERE
				comscore_geo_id !=''
			HAVING
				DISTANCE < 500
			ORDER BY
				DISTANCE
			LIMIT
				10";
			
			$response_dma=$this->db->query($dma_query);
			$result_sub_data=$response_dma->result_array();
			
			foreach ($result_sub_data as $dma_details) 
			{
				$dma_id=$dma_details['comscore_geo_id'];
				$return_data_array[$counter]['dma_id']=$dma_id;
				$return_data_array[$counter]['comscore_site_id']=$site_details['comscore_site_id'];
				$return_data_array[$counter]['url']='';
				$counter++;
			}
		}
		return $return_data_array;
	}
	
	// take the comscore data for five sites and persist in the comscore_demo_data table
	public function persist_demo_data_for_sites($comscore_demo_data) {
		$this->db->reconnect();
		$number_records=0;
		$query_size=10000;
		
		$query_start="
			INSERT INTO comscore_demo_data
			(	comscore_geo_main_id,
				comscore_site_id,
				total_audience, 
				male_all,
				male_over6,
				male_over15,
				male_over18,
				male_over21,
				male_over35,
				male_over50,
				male_over55,
				male_over65,
				male_2_12,
				male_2_14,
				male_2_17,
				male_6_12,
				male_6_14,
				male_9_14,
				male_13_17,
				male_13_24,
				male_13_34,
				male_13_49,
				male_18_24,
				male_18_34,
				male_18_49,
				male_21_34,
				male_21_49,
				male_25_34,
				male_25_49,
				male_25_54,
				male_35_44,
				male_35_49,
				male_35_54,
				male_35_64,
				male_45_54,
				male_45_64,
				male_55_64,
				female_all,
				female_over6,
				female_over15,
				female_over18,
				female_over21,
				female_over35,
				female_over50,
				female_over55,
				female_over65,
				female_2_12,
				female_2_14,
				female_2_17,
				female_6_12,
				female_6_14,
				female_9_14,
				female_13_17,
				female_13_24,
				female_13_34,
				female_13_49,
				female_18_24,
				female_18_34,
				female_18_49,
				female_21_34,
				female_21_49,
				female_25_34,
				female_25_49,
				female_25_54,
				female_35_44,
				female_35_49,
				female_35_54,
				female_35_64,
				female_45_54,
				female_45_64,
				female_55_64,
				hh_income_under25k,
				hh_income_over60k,
				hh_income_over75k,
				hh_income_over100k,
				hh_income_25k_40k,
				hh_income_40k_60k,
				hh_income_60k_75k,
				hh_income_75k_100k,
				hh_income_100k_150k,
				hh_income_150k_200k,
				hh_income_over200k,
				region_wn_central,
				region_mountain,
				region_pacific,
				region_new_england,
				region_mid_atlantic,
				region_s_atlantic,
				region_es_central,
				region_ws_central,
				region_en_central,
				children_no,
				children_yes,
				hh_size_1,
				hh_size_2,
				hh_size_3,
				hh_size_4,
				hh_size_over5,
				hh_size_1_2,
				hh_size_over3,
				race_black,
				race_other,
				ethnicity_non_hispanic,
				ethnicity_hispanic_all,
				hispanic_bilinugal,
				hispanic_english_primary,
				hispanic_spanish_primary,
				date_added)
			VALUES ";
		
		$query_end="
			ON DUPLICATE KEY UPDATE
				comscore_geo_main_id=VALUES(comscore_geo_main_id),
				comscore_site_id=VALUES(comscore_site_id),
				total_audience=VALUES(total_audience),
				male_all=VALUES(male_all),
				male_over6=VALUES(male_over6),
				male_over15=VALUES(male_over15),
				male_over18=VALUES(male_over18),
				male_over21=VALUES(male_over21),
				male_over35=VALUES(male_over35),
				male_over50=VALUES(male_over50),
				male_over55=VALUES(male_over55),
				male_over65=VALUES(male_over65),
				male_2_12=VALUES(male_2_12),
				male_2_14=VALUES(male_2_14),
				male_2_17=VALUES(male_2_17),
				male_6_12=VALUES(male_6_12),
				male_6_14=VALUES(male_6_14),
				male_9_14=VALUES(male_9_14),
				male_13_17=VALUES(male_13_17),
				male_13_24=VALUES(male_13_24),
				male_13_34=VALUES(male_13_34),
				male_13_49=VALUES(male_13_49),
				male_18_24=VALUES(male_18_24),
				male_18_34=VALUES(male_18_34),
				male_18_49=VALUES(male_18_49),
				male_21_34=VALUES(male_21_34),
				male_21_49=VALUES(male_21_49),
				male_25_34=VALUES(male_25_34),
				male_25_49=VALUES(male_25_49),
				male_25_54=VALUES(male_25_54),
				male_35_44=VALUES(male_35_44),
				male_35_49=VALUES(male_35_49),
				male_35_54=VALUES(male_35_54),
				male_35_64=VALUES(male_35_64),
				male_45_54=VALUES(male_45_54),
				male_45_64=VALUES(male_45_64),
				male_55_64=VALUES(male_55_64),
				female_all=VALUES(female_all),
				female_over6=VALUES(female_over6),
				female_over15=VALUES(female_over15),
				female_over18=VALUES(female_over18),
				female_over21=VALUES(female_over21),
				female_over35=VALUES(female_over35),
				female_over50=VALUES(female_over50),
				female_over55=VALUES(female_over55),
				female_over65=VALUES(female_over65),
				female_2_12=VALUES(female_2_12),
				female_2_14=VALUES(female_2_14),
				female_2_17=VALUES(female_2_17),
				female_6_12=VALUES(female_6_12),
				female_6_14=VALUES(female_6_14),
				female_9_14=VALUES(female_9_14),
				female_13_17=VALUES(female_13_17),
				female_13_24=VALUES(female_13_24),
				female_13_34=VALUES(female_13_34),
				female_13_49=VALUES(female_13_49),
				female_18_24=VALUES(female_18_24),
				female_18_34=VALUES(female_18_34),
				female_18_49=VALUES(female_18_49),
				female_21_34=VALUES(female_21_34),
				female_21_49=VALUES(female_21_49),
				female_25_34=VALUES(female_25_34),
				female_25_49=VALUES(female_25_49),
				female_25_54=VALUES(female_25_54),
				female_35_44=VALUES(female_35_44),
				female_35_49=VALUES(female_35_49),
				female_35_54=VALUES(female_35_54),
				female_35_64=VALUES(female_35_64),
				female_45_54=VALUES(female_45_54),
				female_45_64=VALUES(female_45_64),
				female_55_64=VALUES(female_55_64),
				hh_income_under25k=VALUES(hh_income_under25k),
				hh_income_over60k=VALUES(hh_income_over60k),
				hh_income_over75k=VALUES(hh_income_over75k),
				hh_income_over100k=VALUES(hh_income_over100k),
				hh_income_25k_40k=VALUES(hh_income_25k_40k),
				hh_income_40k_60k=VALUES(hh_income_40k_60k),
				hh_income_60k_75k=VALUES(hh_income_60k_75k),
				hh_income_75k_100k=VALUES(hh_income_75k_100k),
				hh_income_100k_150k=VALUES(hh_income_100k_150k),
				hh_income_150k_200k=VALUES(hh_income_150k_200k),
				hh_income_over200k=VALUES(hh_income_over200k),
				region_wn_central=VALUES(region_wn_central),
				region_mountain=VALUES(region_mountain),
				region_pacific=VALUES(region_pacific),
				region_new_england=VALUES(region_new_england),
				region_mid_atlantic=VALUES(region_mid_atlantic),
				region_s_atlantic=VALUES(region_s_atlantic),
				region_es_central=VALUES(region_es_central),
				region_ws_central=VALUES(region_ws_central),
				region_en_central=VALUES(region_en_central),
				children_no=VALUES(children_no),
				children_yes=VALUES(children_yes),
				hh_size_1=VALUES(hh_size_1),
				hh_size_2=VALUES(hh_size_2),
				hh_size_3=VALUES(hh_size_3),
				hh_size_4=VALUES(hh_size_4),
				hh_size_over5=VALUES(hh_size_over5),
				hh_size_1_2=VALUES(hh_size_1_2),
				hh_size_over3=VALUES(hh_size_over3),
				race_black=VALUES(race_black),
				race_other=VALUES(race_other),
				ethnicity_non_hispanic=VALUES(ethnicity_non_hispanic),
				ethnicity_hispanic_all=VALUES(ethnicity_hispanic_all),
				hispanic_bilinugal=VALUES(hispanic_bilinugal),
				hispanic_english_primary=VALUES(hispanic_english_primary),
				hispanic_spanish_primary=VALUES(hispanic_spanish_primary);
			";
		
			$query_middle=array();
			$bindings=array();		
			
			foreach($comscore_demo_data as $aggregate_row)
			{
				$site_data=$aggregate_row[1];
				foreach($this->db_to_comscore_array as $data_column=>$comscore_column)
				{
					if (array_key_exists($data_column,$site_data) && is_numeric($site_data[$data_column]) ) 
					{
						$bindings[]=$site_data[$data_column];
					} else 
					{
						$bindings[]="0";
					}
				}
				$query_middle[]='
								(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
								,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,
								?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
								,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
								,current_timestamp)';
	
				if (count($query_middle)==$query_size)
				{
					$query=$query_start . implode(',', $query_middle) . $query_end;
					if ($this->db->query($query, $bindings))
					{
						$number_records+=$query_size;
					}
					else
					{
						var_dump($this->db);
					}
					$query_middle=array();
					$bindings=array();
				}
			}
			
			if (count($query_middle) > 0)
			{
				$query=$query_start . implode(',', $query_middle) . $query_end;
				if ($this->db->query($query, $bindings))
				{
					$number_records+=count($query_middle);
				}
				else
				{
					var_dump($this->db);
				}
				$query_middle=array();
				$bindings=array();
			}
			return $number_records;
	}
}
	?>