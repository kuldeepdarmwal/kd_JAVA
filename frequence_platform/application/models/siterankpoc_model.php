<?php
class siterankpoc_model extends CI_Model
{
	public function __construct()
	{
		$this->load->database();
		$this->load->library('map');
	}
	private $US_POPULATION=350000000;
	private $TOTAL_SITELIST_PAGES=3;
	private $PAGE_ROWS=31;
	private $IAB_NEWS_ID=586;
	// method 1: get a mpq id and fetch all the iab categories and demos.
	// from the demos, fetch stereotypes
	// fetch all sites tagged to context, context n and stereotypes
	// add the scores, assign extra scores for industry
	// return map of sites and their tags
	/*
	List of parameters:
	$mpq_id: mpq id of an mpq
	$iab_weight: weight for iab tags
	$iab_n_weight:  weight for iab neighbour tags
	$reach_weight: weight for reach
	$stereo_weight:  weight for stereotypes
	$industry_multi:  weight factor for industry
	$industry_n_multi:  weight factor for industry neighbour
	$iab_multi: weight factor for iab multiplier 
	$debug_flag:  debug flag is used to display tags for each site. used in the site tag admin to debug
	$one_site_list_flag:  if local sites need to be added to the nonlocal sites. 
	$product_type_flag : preroll_only = 'P', display only or display & preroll = 'ALL', if neither display or preroll = '-1'
	*/
	public function get_sites_for_mpq($mpq_id, $iab_weight, $iab_n_weight, $reach_weight, $stereo_weight, 
		$industry_multi, $industry_n_multi, $iab_multi, $debug_flag, $one_site_list_flag)
	{
		$product_type_flag=$this->get_product_type_for_mpq($mpq_id);
		if ($industry_multi==null)
			$industry_multi=500;
		
		if ($industry_n_multi==null)
			$industry_n_multi=250;
		
		if ($iab_multi==null)
			$iab_multi=25;
		
		if ($iab_weight==null)
			$iab_weight=50;
		
		if ($iab_n_weight==null)
			$iab_n_weight=5;
		
		if ($reach_weight==null)
			$reach_weight=25;
		
		if ($stereo_weight==null)
			$stereo_weight=200;
			
			// step 1: get contextuals and sterotypes for all 3
		$mpq_demo_iab_industry=$this->get_mpq_demo_iab_industry($mpq_id); // pass mpq and get back an array with 3 elements. each has c. tags
		if ($mpq_demo_iab_industry['industry_id'] == "")
		{
			return;
		}
			
		$iab_tags_string=implode(", ", $mpq_demo_iab_industry['iab_tags']);
		$industry_context_tags_string=implode(", ", $mpq_demo_iab_industry['industry_context_tags']);
		$merged_iab_array=array_merge($mpq_demo_iab_industry['iab_tags'], $mpq_demo_iab_industry['industry_context_tags']);
		$merged_iab_string=implode(", ", $merged_iab_array);
		
		// pass all the iab tags, and also all the steretype ids. also pass all the weights passed from frontend
		// this method will return all the data needed for sitelist. it returns url and also the demo data for sites.
		// is also calculates score for the sites. it also limits to top 200 based on score. it returns a normal array with 200 elements
		$sites_score=$this->fetch_sites_for_tags($merged_iab_string, $iab_tags_string, $industry_context_tags_string, 
				$mpq_demo_iab_industry['stereo_tags'], $iab_weight, $iab_n_weight, $reach_weight, $stereo_weight, false, 0, $product_type_flag);
		
		$sites_badges_array=null;
		if ($debug_flag==true)
			$sites_badges_array=$this->fetch_sites_badges($merged_iab_string, $iab_tags_string, $industry_context_tags_string, 
					$mpq_demo_iab_industry['stereo_tags'], $iab_weight, $iab_n_weight, $reach_weight, $stereo_weight);
			
		// now fetch all mapped iab tags for the 200 sites from above. it returns a mapping of iab tag name + site id. it does take care of substring of tag names
		// $iab_categories_sites_id=$this->fetch_headers_for_sites($sites_score, $mpq_demo_iab_industry['iab_tags']);
		$iab_categories_sites_id=$this->fetch_headers_for_sites($sites_score, $mpq_demo_iab_industry['iab_tags'], $mpq_demo_iab_industry['industry_context_tags']);
		// $iab_categories_sites_id=$this->fetch_headers_for_sites($sites_score);
		
		$mpq_data=$this->get_mpq_data_header($mpq_id, $one_site_list_flag);
		$zips_list=$mpq_data['zip_id'];
		// takes the sites array, the tagname + siteid mapping and industry
		return $this->group_sort_headers($mpq_demo_iab_industry['industry_id'], $sites_score, $iab_categories_sites_id, 
				$sites_badges_array, $zips_list, $industry_multi, $industry_n_multi, $iab_multi, $debug_flag, $one_site_list_flag, $mpq_id);
	}
	public function get_sites_for_mpq_ttd($iab_weight, $iab_n_weight, $reach_weight, $stereo_weight, $industry_multi, $industry_n_multi, $iab_multi, 
			$industry_select, $iab_contextual_multiselect_string, $zips, 
			$gender_male_demographic, $gender_female_demographic, 
			$age_under_18_demographic, $age_18_to_24_demographic, $age_25_to_34_demographic, $age_35_to_44_demographic, 
			$age_45_to_54_demographic, $age_55_to_64_demographic, $age_over_65_demographic, 
			$income_under_50k_demographic, $income_50k_to_100k_demographic, $income_100k_to_150k_demographic, $income_over_150k_demographic, 
			$parent_no_kids_demographic, $parent_has_kids_demographic, 
			$education_no_college_demographic, $education_college_demographic, $education_grad_school_demographic, $type_of_sites)
	{
		// step 1: create the demo string from passed variables : new
		$demo_string=$this->create_demo_string($gender_male_demographic, $gender_female_demographic, 
				$age_under_18_demographic, $age_18_to_24_demographic, $age_25_to_34_demographic, $age_35_to_44_demographic, 
				$age_45_to_54_demographic, $age_55_to_64_demographic, $age_over_65_demographic, 
				$income_under_50k_demographic, $income_50k_to_100k_demographic, $income_100k_to_150k_demographic, $income_over_150k_demographic, 
				$parent_no_kids_demographic, $parent_has_kids_demographic, 
				$education_no_college_demographic, $education_college_demographic, $education_grad_school_demographic);
		
		// step 2: get list of stereotypes from demo string, this is returned as a string : reuse
		$stereotypes_string=$this->fetch_stereotypes_for_demos($demo_string);
		// step 3: get iab tags for the industry : reuse
		$industry_iab_array=$this->fetch_tags_for_industry_id($industry_select);
		$industry_context_tags_string=implode(", ", $industry_iab_array);
		
		// step 4: get iab string
		$iab_tags_array=array();
		if ($iab_contextual_multiselect_string!="")
		{
			$iab_tags_array=explode(", ", $iab_contextual_multiselect_string);
		}
			
		// step 5: get merged string
		$merged_iab_array=array();
		if (count($industry_iab_array)>0&&count($iab_tags_array)>0)
			$merged_iab_array=array_merge($industry_iab_array, $iab_tags_array);
		else if (count($industry_iab_array)>0)
			$merged_iab_array=$industry_iab_array;
		else if (count($iab_tags_array)>0)
			$merged_iab_array=$iab_tags_array;
		
		$merged_iab_string=rtrim(implode(",", $merged_iab_array), ',');
		
		// pass all the iab tags, and also all the steretype ids. also pass all the weights passed from frontend
		// this method will return all the data needed for sitelist. it returns url and also the demo data for sites.
		// is also calculates score for the sites. it also limits to top 200 based on score. it returns a normal array with 200 elements
		$main_sites_array=$this->fetch_sites_for_tags($merged_iab_string, $iab_contextual_multiselect_string, $industry_context_tags_string, 
			$stereotypes_string, $iab_weight, $iab_n_weight, $reach_weight, $stereo_weight, true, $type_of_sites,null);
		
		if ($zips!=null)
		{
			$local_sites_array=$this->get_local_sites(null, $zips, true, true);
			$main_sites_array=array_merge($local_sites_array, $main_sites_array);
		}
		
		return $main_sites_array;
	}
	private function create_demo_string($gender_male_demographic, $gender_female_demographic, $age_under_18_demographic, $age_18_to_24_demographic, $age_25_to_34_demographic, $age_35_to_44_demographic, $age_45_to_54_demographic, $age_55_to_64_demographic, $age_over_65_demographic, $income_under_50k_demographic, $income_50k_to_100k_demographic, $income_100k_to_150k_demographic, $income_over_150k_demographic, $parent_no_kids_demographic, $parent_has_kids_demographic, $education_no_college_demographic, $education_college_demographic, $education_grad_school_demographic)
	{
		$demo_string="";
		// old string not used anymore: 0 - male_population, 1 - female_population
		// 2 - age_under_18, 3 age_18_24, 4 age_25_34, 5 age_35_44, 6 age_45_54, 7 age_55_64, 8 age_65
		// 9 white_population,10 black_population,11 asian_population,12 hispanic_population,13 race_other
		// 14 kids_no, 15 kids_yes
		// 16 income_0_50,17 income_50_100,18 income_100_150, 19 income_150
		// 20 college_no, 21 college_under, 22 college_grad

		//gender 		0 - male_population, 1 - female_population
		//1_0_
		if ($gender_male_demographic=='true') // 0
			$demo_string.="1_";
		else
			$demo_string.="0_";
		
		if ($gender_female_demographic=='true') // 1
			$demo_string.="1_";
		else
			$demo_string.="0_";
		
		//age 		2 - age_under_18, 3 age_18_24, 4 age_25_34, 5 age_35_44, 6 age_45_54, 7 age_55_64, 8 age_65
		//0_1_0_0_1_0_0_
		if ($age_under_18_demographic=='true') // 2
			$demo_string.="1_";
		else
			$demo_string.="0_";
		
		if ($age_18_to_24_demographic=='true') // 3
			$demo_string.="1_";
		else
			$demo_string.="0_";
		
		if ($age_25_to_34_demographic=='true') // 4
			$demo_string.="1_";
		else
			$demo_string.="0_";
		
		if ($age_35_to_44_demographic=='true') // 5
			$demo_string.="1_";
		else
			$demo_string.="0_";
		
		if ($age_45_to_54_demographic=='true') // 6
			$demo_string.="1_";
		else
			$demo_string.="0_";
		
		if ($age_55_to_64_demographic=='true') // 7
			$demo_string.="1_";
		else
			$demo_string.="0_";
		
		if ($age_over_65_demographic=='true') // 8
			$demo_string.="1_";
		else
			$demo_string.="0_";
		
		//income     9 income_0_50,   10 income_50_100, 11 income_100_150, 12 income_150
		//1_0_0_0_
		if ($income_under_50k_demographic=='true') // 9
			$demo_string.="1_";
		else
			$demo_string.="0_";
		
		if ($income_50k_to_100k_demographic=='true') // 10
			$demo_string.="1_";
		else
			$demo_string.="0_";
		
		if ($income_100k_to_150k_demographic=='true') // 11
			$demo_string.="1_";
		else
			$demo_string.="0_";
		
		if ($income_over_150k_demographic=='true') // 12
			$demo_string.="1_";
		else
			$demo_string.="0_";

		//edu        13 college_no, 14 college_under, 15 college_grad
		//1_0_0_
		if ($education_no_college_demographic=='true') // 13
			$demo_string.="1_";
		else
			$demo_string.="0_";
		
		if ($education_college_demographic=='true') // 14
			$demo_string.="1_";
		else
			$demo_string.="0_";
		
		if ($education_grad_school_demographic=='true') // 15
			$demo_string.="1_";
		else
			$demo_string.="0_";

		//parenting  16 kids_no, 17 kids_yes
		//1_0_		
		if ($parent_no_kids_demographic=='true') // 16
			$demo_string.="1_";
		else
			$demo_string.="0_";
		
		if ($parent_has_kids_demographic=='true') // 17
			$demo_string.="1_";
		else
			$demo_string.="0_";
		
		return $demo_string;
	}
	private function get_mpq_demo_iab_industry($mpq_id)
	{
		// step 1
		$bindings=array();
		$bindings[]=$mpq_id;
		$sql="
		SELECT
			c.demographic_data AS demo,
			COALESCE(a.id,".$this->IAB_NEWS_ID.") AS iabid,
			industry_id
		FROM
			mpq_sessions_and_submissions c
			LEFT JOIN mpq_iab_categories_join b ON c.id=b.mpq_id
			LEFT JOIN iab_categories a ON a.id = b.iab_category_id 
 		WHERE
			c.id=?";
		
		$response=$this->db->query($sql, $bindings);
		$return_array=array();
		$demo_string="";
		$industry_id="";
		$iab_array=array();
		foreach ($response->result_array() as $row_data)
		{
			$demo_string=$row_data['demo'];
			$iab_array[]=$row_data['iabid'];
			$industry_id=$row_data['industry_id'];
		}
		
		$return_array['industry_id']=$industry_id;
		if ($industry_id!="")
		{
			$return_array['iab_tags']=$iab_array;
			$return_array['stereo_tags']=$this->fetch_stereotypes_for_demos($demo_string);
			$return_array['industry_context_tags']=$this->fetch_tags_for_industry_id($industry_id);
		}
		return $return_array;
	}
	
	// method 2: fetch_stereotypes_for_demos
	private function fetch_stereotypes_for_demos($demo_string)
	{
		$demo_array=explode("_", $demo_string);
		
		$gender_female="";
		$gender_male="";
		$age_kids=""; // Under 18
		$age_young=""; // 18-24
		$age_middle=""; // 35-44,45-54
		$age_old=""; // 55-64, 65+
		$parenting="";
		$income_high=""; // $100-150k,$150k
		$income_low=""; // $0-50k
		$edu_no=""; // No College
		$edu_grad=""; // Grad. Sch
		              
		// old mapping, not used now, kept here for reference. 
		  // 0 - male_population, 1 - female_population
	      // 2 - age_under_18, 3 age_18_24, 4 age_25_34, 5 age_35_44, 6 age_45_54, 7 age_55_64, 8 age_65
	      // 9 white_population,10 black_population,11 asian_population,12 hispanic_population,13 race_other
	      // 14 kids_no, 15 kids_yes
	      // 16 income_0_50,17 income_50_100,18 income_100_150, 19 income_150
	      // 20 college_no, 21 college_under, 22 college_grad

		//section 1: pick demographic_data string from mpq_sessions_and_submissions table. For selected demos by user, pick the mapped stereotypes.
		
		//gender 		0 - male_population, 1 - female_population
		//1_0_
		if (!($demo_array[0]=="1"&&$demo_array[1]=="1")) // gender: to check if all are not checked together
		{
			if ($demo_array[0]=="1") // male_population
				$gender_male="M";
			
			if ($demo_array[1]=="1") // female_population
				$gender_female="F";
		}

		//age 		2 - age_under_18, 3 age_18_24, 4 age_25_34, 5 age_35_44, 6 age_45_54, 7 age_55_64, 8 age_65
		//0_1_0_0_1_0_0_
		if (!($demo_array[2]=="1"&&$demo_array[3]=="1"&&$demo_array[4]=="1"&&$demo_array[5]=="1"&&$demo_array[6]=="1"&&$demo_array[7]=="1"&&$demo_array[8]=="1")) // age
		{
			if ($demo_array[2]=="1") // age_under_18
				$age_kids="KIDS";
			
			if ($demo_array[3]=="1") // age_18_24
				$age_young="YOUNG";
			
			if ($demo_array[5]=="1"||$demo_array[6]=="1") // age_35_44 age_45_54
				$age_middle="MIDDLE";
			
			if ($demo_array[7]=="1"||$demo_array[8]=="1") // age_55_64 age_65
				$age_old="OLD";
		}
		
		//income     9 income_0_50,   10 income_50_100, 11 income_100_150, 12 income_150
		//1_0_0_0_
		if (!($demo_array[9]=="1"&&$demo_array[10]=="1"&&$demo_array[11]=="1"&&$demo_array[12]=="1")) // income
		{
			if ($demo_array[11]=="1"||$demo_array[12]=="1") // income_100_150 income_150
				$income_high="HIGH";
			
			if ($demo_array[9]=="1") // income_0_50
				$income_low="LOW";
		}
		
		//edu        13 college_no, 14 college_under, 15 college_grad
		//1_0_0_
		if (!($demo_array[13]=="1"&&$demo_array[14]=="1"&&$demo_array[15]=="1")) // edu
		{
			if ($demo_array[13]=="1") // college_no
				$edu_no="NO";
			
			if ($demo_array[15]=="1") // college_grad
				$edu_grad="GRAD";
		}

		//parenting  16 kids_no, 17 kids_yes
		//1_0_
		if (!($demo_array[16]=="1"&&$demo_array[17]=="1")) // kids
		{
			if ($demo_array[17]=="1") // kids_yes
				$parenting="YES";
		}

		// Section 2: for selected stereotypes, create the stereotypes array
		
		$stereotypes_array=array();
		if ($parenting=="YES")
			$stereotypes_array[]=1;
		if ($gender_female=="F"&&$parenting=="YES")
			$stereotypes_array[]=2;
		if ($gender_male=="F"&&$parenting=="YES")
			$stereotypes_array[]=3;
		if ($gender_male=="M")
			$stereotypes_array[]=4;
		if ($gender_female=="F")
			$stereotypes_array[]=5;
		if ($gender_male=="M"&&$age_young=="YOUNG")
			$stereotypes_array[]=6;
		if ($gender_male=="M"&&$age_middle=="MIDDLE")
			$stereotypes_array[]=7;
		if ($gender_male=="M"&&$age_old=="OLD")
			$stereotypes_array[]=8;
		if ($gender_female=="F"&&$age_young=="YOUNG")
			$stereotypes_array[]=9;
		if ($gender_female=="F"&&$age_middle=="MIDDLE")
			$stereotypes_array[]=10;
		if ($gender_female=="F"&&$age_old=="OLD")
			$stereotypes_array[]=11;
		if ($gender_male=="M"&&$income_high=="HIGH")
			$stereotypes_array[]=12;
		if ($gender_female=="F"&&$income_high=="HIGH")
			$stereotypes_array[]=13;
		if ($age_kids=="KIDS")
			$stereotypes_array[]=14;
		
		if ($age_young=="YOUNG")
			$stereotypes_array[]=15;
		if ($age_middle=="MIDDLE")
			$stereotypes_array[]=16;
		if ($age_old=="OLD")
			$stereotypes_array[]=17;
		if ($income_high=="HIGH")
			$stereotypes_array[]=18;
		if ($income_low=="LOW")
			$stereotypes_array[]=19;
		if ($gender_male=="M"&&$income_low=="LOW")
			$stereotypes_array[]=20;
		if ($gender_female=="F"&&$income_low=="LOW")
			$stereotypes_array[]=21;
		if ($edu_no=="NO")
			$stereotypes_array[]=22;
		if ($edu_grad=="GRAD")
			$stereotypes_array[]=23;
		
		$stereotypes_array[]=24;
		return implode(", ", $stereotypes_array);
	}
	private function fetch_tags_for_industry_id($industry_id)
	{
		$sql="SELECT
				iab_categories_id,
				tag_copy
			FROM
				freq_industry_tags_iab_link, 
				iab_categories 
			WHERE 
				freq_industry_tags_id = ? AND
				id=iab_categories_id";
		$response=$this->db->query($sql, $industry_id);
		$iab_array=array();
		foreach ($response->result_array() as $row_data)
		{
			$iab_array[$row_data['iab_categories_id']]=$row_data['iab_categories_id'];
		}
		return $iab_array;
	}
	private function fetch_child_neighbour_tag_names_for_industry_id($industry_id)
	{
		$sql=" SELECT DISTINCT
				d.tag_friendly_name AS tag_copy
			FROM
				freq_industry_tags_iab_link a,
				iab_categories b,
				iab_heirarchy c,
				iab_categories d
			WHERE a.freq_industry_tags_id = ? AND
				b.id=a.iab_categories_id AND
				c.ancestor_id=b.id AND
				c.path_length > 0 AND
				c.descendant_id=d.id";
		
		$response=$this->db->query($sql, $industry_id);
		$iab_neighbour_array=array();
		foreach ($response->result_array() as $row_data)
		{
			$iab_neighbour_array[$row_data['tag_copy']]=$row_data['tag_copy'];
		}
		return $iab_neighbour_array;
	}
	private function fetch_parent_neighbour_tag_names_for_industry_id($industry_id)
	{
		$sql=" SELECT DISTINCT 
				d.tag_friendly_name AS tag_copy
			FROM
				freq_industry_tags_iab_link a,
				iab_categories b,
				iab_heirarchy c,
				iab_categories d
			WHERE a.freq_industry_tags_id = ? AND
				b.id=a.iab_categories_id AND
				c.descendant_id=b.id AND
				c.path_length = 1 AND
				c.ancestor_id=d.id";
		
		$response=$this->db->query($sql, $industry_id);
		$iab_neighbour_array=array();
		foreach ($response->result_array() as $row_data)
		{
			$iab_neighbour_array[$row_data['tag_copy']]=$row_data['tag_copy'];
		}
		return $iab_neighbour_array;
	}
	private function fetch_top_level_tags_for_industry_id($industry_id)
	{
		$sql="SELECT DISTINCT
				SUBSTRING_INDEX(tag_copy, ' > ', 1) as tag_copy
			FROM
				freq_industry_tags_iab_link,
				iab_categories
			WHERE
				freq_industry_tags_id = ? AND
				id=iab_categories_id";
		$response=$this->db->query($sql, $industry_id);
		$iab_array=array();
		foreach ($response->result_array() as $row_data)
		{
			$iab_array[$row_data['tag_copy']]=$row_data['tag_copy'];
		}
		return $iab_array;
	}
	private function fetch_sites_for_tags($merged_iab_string, $iab_tags_string, $industry_context_tags_string, $stereotype_string, 
		$iab_weight, $iab_n_weight, $reach_weight, $stereo_weight, $ttd_flag, $type_of_sites, $product_type_flag='ALL')
	{
		$sql=" 
			SELECT distinct 
			   ovr.site_id AS site_id, 
			   ovr.url, 
			   ROUND(SUM(ovr.weight)+(".$reach_weight."*(IFNULL(c.total_audience,0)/".$this->US_POPULATION.")*100), 0) AS score,
			   IFNULL(ROUND(( total_audience / ".$this->US_POPULATION." ) * 1, 2),'')                     			AS reach, 
		       IFNULL(ROUND(male_all * 1/total_audience, 2),'')                                     AS gender_male, 
		       IFNULL(ROUND(female_all * 1/total_audience, 2),'')                                   AS gender_female, 
		       IFNULL(ROUND(( male_2_17 + female_2_17 ) * 1/total_audience, 2),'')                  AS Age_Under18, 
		       IFNULL(ROUND(( male_18_24 + female_18_24 ) * 1/total_audience, 2),'')                AS Age_18_24, 
		       IFNULL(ROUND(( male_25_34 + female_25_34 ) * 1/total_audience, 2),'')                AS Age_25_34, 
		       IFNULL(ROUND(( male_35_44 + female_35_44 ) * 1/total_audience, 2),'')                AS Age_35_44, 
		       IFNULL(ROUND(( male_45_54 + female_45_54 ) * 1/total_audience, 2),'')                AS Age_45_54, 
		       IFNULL(ROUND(( male_55_64 + female_55_64 ) * 1/total_audience, 2),'')                AS Age_55_64, 
		       IFNULL(ROUND(( male_over65 + female_over65 ) * 1/total_audience, 2),'')              AS Age_65, 
		       IFNULL(ROUND(children_no * 1/total_audience, 2),'')                                  AS Kids_No, 
		       IFNULL(ROUND(children_yes * 1/total_audience, 2),'')                                 AS Kids_Yes, 
		       IFNULL(ROUND((hh_income_under25k + hh_income_25k_40k + hh_income_40k_60k ) * 1/total_audience, 2),'')    AS Income_0_50, 
		       IFNULL(ROUND(( hh_income_60k_75k + hh_income_75k_100k ) * 1/total_audience, 2),'')   AS Income_50_100, 
		       IFNULL(ROUND(hh_income_100k_150k * 1/total_audience, 2),'')                          AS Income_100_150, 
		       IFNULL(ROUND(( hh_income_150k_200k + hh_income_over200k ) * 1/total_audience, 2),'') AS Income_150 		
			FROM (";
		if ($ttd_flag=="true")
		{
			$sql="
			SELECT distinct
			   ovr.url,
			   ROUND(SUM(ovr.weight)+(".$reach_weight."*(IFNULL(c.total_audience,0)/".$this->US_POPULATION.")*100), 0) AS score,
			   IFNULL(ROUND(( total_audience / ".$this->US_POPULATION." ) * 1, 2),'')                     			AS reach,
		       IFNULL(ROUND(male_all * 1/total_audience, 2),'')                                     AS gender_male,
		       IFNULL(ROUND(female_all * 1/total_audience, 2),'')                                   AS gender_female,
		       IFNULL(ROUND(( male_2_17 + female_2_17 ) * 1/total_audience, 2),'')                  AS Age_Under18,
		       IFNULL(ROUND(( male_18_24 + female_18_24 ) * 1/total_audience, 2),'')                AS Age_18_24,
		       IFNULL(ROUND(( male_25_34 + female_25_34 ) * 1/total_audience, 2),'')                AS Age_25_34,
		       IFNULL(ROUND(( male_35_44 + female_35_44 ) * 1/total_audience, 2),'')                AS Age_35_44,
		       IFNULL(ROUND(( male_45_54 + female_45_54 ) * 1/total_audience, 2),'')                AS Age_45_54,
		       IFNULL(ROUND(( male_55_64 + female_55_64 ) * 1/total_audience, 2),'')                AS Age_55_64,
		       IFNULL(ROUND(( male_over65 + female_over65 ) * 1/total_audience, 2),'')              AS Age_65,
		       IFNULL(ROUND(children_no * 1/total_audience, 2),'')                                  AS Kids_No,
		       IFNULL(ROUND(children_yes * 1/total_audience, 2),'')                                 AS Kids_Yes,
		       IFNULL(ROUND((hh_income_under25k + hh_income_25k_40k + hh_income_40k_60k ) * 1/total_audience, 2),'')    AS Income_0_50,
		       IFNULL(ROUND(( hh_income_60k_75k + hh_income_75k_100k ) * 1/total_audience, 2),'')   AS Income_50_100,
		       IFNULL(ROUND(hh_income_100k_150k * 1/total_audience, 2),'')                          AS Income_100_150,
		       IFNULL(ROUND(( hh_income_150k_200k + hh_income_over200k ) * 1/total_audience, 2),'') AS Income_150
			FROM (";
		}
		
		$inner_query_array=array();

		if ($industry_context_tags_string!="")
		{
				$industry_string=" 
							SELECT a.freq_sites_main_id AS site_id,
							       m.url AS url,
							       a.tag_id,
							       'iab' AS source,
							       b.tag_copy AS tagname,
							       100*".$iab_weight." AS weight
							FROM   freq_site_tagging_link a,
							       iab_categories b,
							       freq_sites_main m
							WHERE  a.tag_id IN (".$industry_context_tags_string.")
							       AND a.tag_type_id = 1
							       AND m.is_rejected_flag IS NULL ";

				if ($ttd_flag!="true" || $type_of_sites=='PREMIUM')
				{				       
					$industry_string.=" AND m.is_bidder_only_flag IS NULL ";
				}
				if ($product_type_flag=='P')
				{				       
					$industry_string.=" AND m.preroll_site_flag = 1 ";
				}
				
				$industry_string.=" AND a.tag_id = b.id
							       AND a.freq_sites_main_id = m.freq_sites_main_id ";

				$inner_query_array[]=$industry_string;			       
		}
		
		
		if ($iab_tags_string!="")
		{
				$iab_string_temp=" 
						SELECT a.freq_sites_main_id AS site_id,
						       m.url AS url,
						       a.tag_id,
						       'iab' AS source,
						       b.tag_copy AS tagname,
						       ".$iab_weight." AS weight
						FROM   freq_site_tagging_link a,
						       iab_categories b,
						       freq_sites_main m
						WHERE  a.tag_id IN (".$iab_tags_string.")
						       AND a.tag_type_id = 1
						       AND m.is_rejected_flag IS NULL";

				if ($ttd_flag!="true" || $type_of_sites=='PREMIUM')
				{				       
					$iab_string_temp.=" AND m.is_bidder_only_flag IS NULL ";
				}
				if ($product_type_flag=='P')
				{				       
					$iab_string_temp.=" AND m.preroll_site_flag = 1 ";
				}			       
				$iab_string_temp.=" AND a.tag_id = b.id
						       AND a.freq_sites_main_id = m.freq_sites_main_id ";

				$inner_query_array[]=$iab_string_temp;
		}
		
		if ($merged_iab_string!="")
		{
				$merged_iab_string_temp=" 
						SELECT a.freq_sites_main_id  AS site_id,
						       m.url AS url,
						       a.tag_id,
						       'iab_neighbour',
						       b.tag_copy,
						       ".$iab_n_weight."  AS weight
						FROM   freq_site_tagging_link a,
						       iab_categories b,
						       freq_sites_main m
						WHERE  tag_type_id = 1
							   AND m.is_rejected_flag IS NULL";

				if ($ttd_flag!="true" || $type_of_sites=='PREMIUM')
				{			   	
					$merged_iab_string_temp.= " AND m.is_bidder_only_flag IS NULL ";
				}
				if ($product_type_flag=='P')
				{				       
					$merged_iab_string_temp.=" AND m.preroll_site_flag = 1 ";
				}

				$merged_iab_string_temp.=  " AND a.freq_sites_main_id = m.freq_sites_main_id
						       AND a.tag_id = b.id
						       AND a.tag_id IN (
			       					SELECT ancestor_id
			                        FROM   iab_heirarchy
			                        WHERE  ( 
			                        		descendant_id IN (".$merged_iab_string.")
			                        		)
			                               	AND path_length > 0
			                        UNION
			                        SELECT descendant_id
			                        FROM   iab_heirarchy
			                        WHERE  
			                        	( 
			                        	ancestor_id IN (".$merged_iab_string."))
			                            AND path_length > 0
			                            ) ";

				$inner_query_array[]=$merged_iab_string_temp;
		}
		
		if ($stereotype_string!="")
		{
				$stereo_string_temp=" 
						SELECT b.freq_sites_main_id  AS site_id,
						       m.url AS url,
						       b.tag_id,
						       'stereo',
						       a.name,
						       ".$stereo_weight."*a.weight AS weight
						FROM   freq_stereotypes a,
						       freq_site_tagging_link b,
						       freq_site_tagging_link c,
						       freq_sites_main m
						WHERE  a.freq_stereotypes_id IN ( ".$stereotype_string." )
								AND b.tag_id = a.freq_stereotypes_id
								AND b.tag_type_id = 4
								AND m.is_rejected_flag IS NULL ";

				if ($ttd_flag!="true" || $type_of_sites=='PREMIUM')
				{				
					$stereo_string_temp .= " AND m.is_bidder_only_flag IS NULL ";
				}
				if ($product_type_flag=='P')
				{				       
					$stereo_string_temp.=" AND m.preroll_site_flag = 1 ";
				}
				$stereo_string_temp .= " AND b.freq_sites_main_id = m.freq_sites_main_id 
								AND b.freq_sites_main_id = c.freq_sites_main_id
								AND c.tag_type_id = 4 ";
				$inner_query_array[]=$stereo_string_temp;				
		}
		
		$sql.=implode(" UNION ", $inner_query_array);
		
		$sql.=" ) 
			AS ovr
	       LEFT JOIN freq_sites_main f ON 
	       		ovr.site_id=f.freq_sites_main_id AND 
	       		f.is_rejected_flag IS NULL  ";

       	if ($ttd_flag!="true" || $type_of_sites=='PREMIUM')
		{		
       		$sql.= " AND f.is_bidder_only_flag IS NULL ";
       	}
	   	
	   	if ($product_type_flag=='P')
		{		
       		$sql.= " AND f.preroll_site_flag = 1 ";
       	}  	 			 
       
       	$sql.=" LEFT JOIN comscore_demo_data c ON 
       		c.comscore_site_id=f.comscore_site_id AND 
       		c.comscore_geo_main_id=840
       	GROUP BY 
       		ovr.url
       	ORDER BY 
       		score DESC
	   	LIMIT ";

		if ($ttd_flag=="true")
		{
			$sql.="2000";
		}
		else
		{
			$sql.="500";
		}
		
		return $this->db->query($sql)->result_array();
	}
	private function fetch_sites_badges($merged_iab_string, $iab_tags_string, $industry_context_tags_string, $stereotype_string, $iab_weight, $iab_n_weight, $reach_weight, $stereo_weight)
	{
		$sql="
				SELECT     m.url AS url,
					       'INDUSTRY' AS source,
					       b.tag_copy AS tagname,
					       round(100*".$iab_weight.") AS weight
					FROM   freq_site_tagging_link a,
					       iab_categories b,
					       freq_sites_main m
					WHERE  a.tag_id IN (".$industry_context_tags_string.")
					       AND a.tag_type_id = 1
					       AND a.tag_id = b.id
					       AND a.freq_sites_main_id = m.freq_sites_main_id
					UNION
					SELECT 
					       m.url AS url,
					       'IAB' AS source,
					       b.tag_copy AS tagname,
					       round(".$iab_weight.") AS weight
					FROM   freq_site_tagging_link a,
					       iab_categories b,
					       freq_sites_main m
					WHERE  a.tag_id IN (".$iab_tags_string.")
					       	AND a.tag_type_id = 1
					       	AND a.tag_id = b.id
					       	AND a.freq_sites_main_id = m.freq_sites_main_id
							AND (m.url, b.tag_copy) 
							NOT IN (
									SELECT 
					      		 		m.url, 
										b.tag_copy
									FROM   
										freq_site_tagging_link a,
					      		 		iab_categories b,
					       				freq_sites_main m
									WHERE  a.tag_id IN (".$industry_context_tags_string.")
					       				AND a.tag_type_id = 1
					       				AND a.tag_id = b.id
					       				AND a.freq_sites_main_id = m.freq_sites_main_id)     
					UNION
					SELECT 
					       m.url AS url,
					       'IAB_N',
					       b.tag_copy,
					       round(".$iab_n_weight.")
					FROM   freq_site_tagging_link a,
					       iab_categories b,
					       freq_sites_main m
					WHERE  tag_type_id = 1
					       AND a.freq_sites_main_id = m.freq_sites_main_id
					       AND a.tag_id = b.id
					       AND a.tag_id IN (
					       					SELECT ancestor_id
					                        FROM   iab_heirarchy
					                        WHERE  ( 
					       								descendant_id IN (".$merged_iab_string.")
					                        		)
					                               AND path_length > 0
					                        UNION
					                        SELECT descendant_id
					                        FROM   iab_heirarchy
					                        WHERE  ( 
					                        			ancestor_id IN (".$merged_iab_string.")
					                        		)
					                               AND 
					                        			path_length > 0)
					       AND (m.url, b.tag_copy) 
					       NOT IN 
					       (
					       			SELECT 
					      		 		m.url,
					                	b.tag_copy
									FROM
					                    freq_site_tagging_link a,
					      		 		iab_categories b,
				      			 		freq_sites_main m
									WHERE  
					                    a.tag_id IN (".$iab_tags_string.")
					       				AND a.tag_type_id = 1
							       		AND a.tag_id = b.id
							       		AND a.freq_sites_main_id = m.freq_sites_main_id
					        )     
							AND (m.url, b.tag_copy) 
							NOT IN (
		                    			SELECT 
		      		 						m.url, 
		                    				b.tag_copy
										FROM freq_site_tagging_link a,
								      		 iab_categories b,
								       		freq_sites_main m
										WHERE a.tag_id IN (".$industry_context_tags_string.")
								       		AND a.tag_type_id = 1
								       		AND a.tag_id = b.id
								       		AND a.freq_sites_main_id = m.freq_sites_main_id
							)     	            		
					UNION
					SELECT 
					       m.url AS url,
					       'STEREO',
					       a.name,
					       round(".$stereo_weight." * a.weight)
					FROM   freq_stereotypes a,
					       freq_site_tagging_link b,
					       freq_site_tagging_link c,
					       freq_sites_main m
					WHERE  a.freq_stereotypes_id IN (".$stereotype_string.")
							AND b.tag_id = a.freq_stereotypes_id
							AND b.tag_type_id = 4
							AND b.freq_sites_main_id = m.freq_sites_main_id
							AND b.freq_sites_main_id = c.freq_sites_main_id
							AND c.tag_type_id = 1
					";
		$result_array=$this->db->query($sql)->result_array();
		$return_data=array();
		foreach ($result_array as $row_data)
		{
			$url=$row_data['url'];
			if (!array_key_exists($url, $return_data))
			{
				$return_site_data=array(
						"INDUSTRY"=>"",
						"IAB"=>"",
						"IAB_N"=>"",
						"STEREO"=>""
				);
				$return_data[$url]=$return_site_data;
			}
			$return_site_data=$return_data[$url];
			$source=$row_data['source'];
			$data_sub=$row_data['tagname']."-".$row_data['weight']."<br>";
			if ($source=="INDUSTRY")
			{
				$data=$return_site_data["INDUSTRY"];
				$data.=$data_sub;
				$return_site_data["INDUSTRY"]=$data;
			}
			else if ($source=="IAB")
			{
				$data=$return_site_data["IAB"];
				$data.=$data_sub;
				$return_site_data["IAB"]=$data;
			}
			else if ($source=="IAB_N")
			{
				$data=$return_site_data["IAB_N"];
				$data.=$data_sub;
				$return_site_data["IAB_N"]=$data;
			}
			else if ($source=="STEREO")
			{
				$data=$return_site_data["STEREO"];
				$data.=$data_sub;
				$return_site_data["STEREO"]=$data;
			}
			$return_data[$url]=$return_site_data;
		}
		return $return_data;
	}
	private function fetch_headers_for_sites($sites_array, $iab_tags, $industry_iab_tags)
	{
		if ($sites_array==null||count($sites_array)==0)
			return;
		$iab_tags_string=implode(", ", $iab_tags);
		$industry_iab_tags_string=implode(", ", $industry_iab_tags);
		$sites_array_new=array();
		foreach ($sites_array as $row_data)
		{
			$sites_array_new[]=$row_data['site_id'];
		}
		$sites_id_string=implode(", ", $sites_array_new);
		
		$sql="
			SELECT 
				CONCAT('I-',a.tag_friendly_name) AS tag_copy,
				b.freq_sites_main_id AS site_id
			FROM
				iab_categories a,
				freq_site_tagging_link b
			WHERE
				b.freq_sites_main_id IN (".$sites_id_string.") AND
				b.tag_id IN (".$industry_iab_tags_string.") AND
				a.id=b.tag_id AND
				b.tag_type_id=1
			UNION
			SELECT
				CONCAT('C-',a.tag_friendly_name) AS tag_copy,
				b.freq_sites_main_id AS site_id
			FROM
				iab_categories a,
				freq_site_tagging_link b
			WHERE
				b.freq_sites_main_id IN (".$sites_id_string.") AND
				b.tag_id IN (".$iab_tags_string.") AND
				a.id=b.tag_id AND
				b.tag_type_id=1 AND 
						(a.tag_friendly_name, b.freq_sites_main_id)
						NOT IN (
							SELECT
								a.tag_friendly_name,
								b.freq_sites_main_id
							FROM
								iab_categories a,
								freq_site_tagging_link b
							WHERE
								b.freq_sites_main_id IN (".$sites_id_string.") AND
								b.tag_id IN (".$industry_iab_tags_string.") AND
								a.id=b.tag_id AND
								b.tag_type_id=1
						)						
			UNION
			SELECT
				CONCAT('N-',SUBSTRING_INDEX(a.tag_copy, ' > ', 1)),
				b.freq_sites_main_id
			FROM
				iab_categories a,
				freq_site_tagging_link b
			WHERE
				b.freq_sites_main_id IN (".$sites_id_string.") AND
				a.id=b.tag_id AND
				b.tag_type_id=1";
		
		$response=$this->db->query($sql);
		return $response->result_array();
	}
	private function group_sort_headers($industry_id, $sites_score, $iab_categories_sites_id, $sites_badges_array, 
		$zips_list, $industry_multi, $industry_n_multi, $iab_multi, $debug_flag, $one_site_list_flag, $mpq_id=0)
	{
		$final_headers_arry=array();
		$used_sites=array(); // if a site is added to the main list, it will be part of this array
		$used_headers=array(); // used headers already accounted for.
		$final_counter=0;
		$page_counter=0;
		$display_sites_ctr=0;
		$overall_counter=0;
		$total_pages_counter=0;
		$average_array=$this->fetch_internet_average_default();
		$industry_child_neighbours_array=$this->fetch_child_neighbour_tag_names_for_industry_id($industry_id);
		$industry_parent_neighbours_array=$this->fetch_parent_neighbour_tag_names_for_industry_id($industry_id);
		$industry_top_level_array=$this->fetch_top_level_tags_for_industry_id($industry_id);
		$debug_sites_array=null;
		if ($debug_flag==true)
		{
			$debug_sites_array=array();
		}
		// local section
		// in local section, if page counter is 10 or less, we will fit in the local sites on this page by showing less local sites
		// if page counter is over 10. we will put a page break to mvoe local sites to next page and show 30 local sites
		
		if ($one_site_list_flag == true)
		{
			$local_sites_array=$this->get_local_sites($mpq_id, $zips_list, false, $one_site_list_flag);
			if (count($local_sites_array)>0)
			{
				// local break check
				if ($page_counter>10)
				{
					if ($debug_flag==true)
					{
						$final_headers_arry[$final_counter]=array(
								"break_tag type 4 : geo:- page counter is ".$page_counter.". As it is > 10. Move local sites to new page"
						);
					}
					else
						$final_headers_arry[$final_counter]=array(
								"break_tag",
								"(page break)"
						);
					
					$total_pages_counter++;
					$page_counter=0;
					$final_counter++;
				}
				
				$sites_counter=1;
				
				// local header
				if ($debug_flag==true)
				{
					$final_headers_arry[$final_counter]=array(
							"header_tag",
							"LOCAL MEDIA (Score: Hardcoded)"
					);
				}
				else
				{
					$final_headers_arry[$final_counter]=array(
							"header_tag",
							"LOCAL MEDIA"
					);
				}
				$final_counter++;
				$page_counter++;
				
				// local loop
				foreach ($local_sites_array as $site)
				{
					if ($page_counter>10)
						break;
					
					if ($debug_flag==true)
					{
						$display_sites_ctr++;
						if ($site["reach"]==""||$site["gender_male"]=="")
						{
							$final_headers_arry[$final_counter]=$this->populate_demo_row($display_sites_ctr, $site["url"], $site["distance1"], $sites_counter, $average_array, null, $debug_flag);
						}
						else
						{
							$final_headers_arry[$final_counter]=$this->populate_demo_row($display_sites_ctr, $site["url"], $site["distance1"], $sites_counter, $site, null, $debug_flag);
						}
					}
					else
					{
						if ($site["reach"]==""||$site["gender_male"]=="")
						{
							$final_headers_arry[$final_counter]=$this->populate_demo_row($display_sites_ctr, $site["url"], $site["distance1"], $sites_counter, $average_array, null, $debug_flag);
						}
						else
						{
							$final_headers_arry[$final_counter]=$this->populate_demo_row($display_sites_ctr, $site["url"], $site["distance1"], $sites_counter, $site, null, $debug_flag);
						}
					}
					
					$sites_counter++;
					$final_counter++;
					$page_counter++;
				}
			}
		}
		// local section ends
		
		//used_sites has list of site that is accounted for, to be shown in the site list
		//sites_score has actual list of around upto 500 sites that are pickedup based on the tags
		while (count($used_sites) < 200 && count($used_sites) < count($sites_score))
		{
			$headers_array=array();

			// all the iab tags that are linked to these sites picked above
			foreach ($iab_categories_sites_id as $row)
			{
				$tag_copy=$row['tag_copy'];
				$tag_copy_detailed=$tag_copy;
				$tag_copy=substr($tag_copy, 2);
				$site_id=$row['site_id'];
				$used_flag=false;

				foreach ($used_headers as $used_header_name)
				{
					if ($used_header_name==$tag_copy)
					{
						$used_flag=true;
						break;
					}
				}
				
				foreach ($used_sites as $used_site_id)
				{
					if ($used_site_id==$site_id)
					{
						$used_flag=true;
						break;
					}
				}
				
				// continue with this loop if the header of the site is already processed earlier.
				if ($used_flag==true) 
				{// this header or site already processed
					continue;
				}

				if (!array_key_exists($tag_copy, $headers_array))
				{
					$headers_array[$tag_copy]=array();
					$headers_array[$tag_copy][0]=0; // initialize total score for the header
					$headers_array[$tag_copy][1]=array(); // initialize the list of sites for the header
					$headers_array[$tag_copy][2]=array(); // entire site row
					$headers_array[$tag_copy][3]=array(); // site ids
				}
				
				foreach ($sites_score as $row_site) //we are within iab loop, now for each iab tag, loop thru all the sites
				{
					$sub_site_id=$row_site['site_id'];
				
					$score=$row_site['score'];
					// if condition: check if the iab and sites array intersect and also if this site is not already accounted for , for this tag copy. tag copy is also changed above to move to primary or secondary levels
					//if ($sub_site_id==$site_id&&count($headers_array[$tag_copy][1])<10&&!array_key_exists($site_id, $headers_array[$tag_copy][3])) // important break; this makes sure each header gets no more than 31 sites
					
					if ($sub_site_id==$site_id && !array_key_exists($site_id, $headers_array[$tag_copy][3]))
					{
						// if iab tag was due to industry, multiply the site score with the industry multiplier. this score goes to the headers array, 0th element, that has total score for a given header tag
						if (strpos($tag_copy_detailed, "I-") !== false)
						{
							$headers_array[$tag_copy][0]+=($score * $industry_multi); // add scores for all the sites mapped to this tag
						}
						else if (strpos($tag_copy_detailed, "C-") !== false)
						{
							//industry child
							if (array_key_exists($tag_copy, $industry_child_neighbours_array))
							{
								$headers_array[$tag_copy][0] += ($score * $industry_n_multi); // add scores for all the sites mapped to this tag
								$sites_badges_array[$row_site['url']]['INDUSTRY_CHILD_N']=$tag_copy;
							}
							// industry parent
							else if (array_key_exists($tag_copy, $industry_parent_neighbours_array))
							{
								$headers_array[$tag_copy][0] += ($score * ($industry_n_multi/2)); // add scores for all the sites mapped to this tag
								$sites_badges_array[$row_site['url']]['INDUSTRY_PARENT_N']=$tag_copy;
							}
							//industry grandfather
							else if (array_key_exists($tag_copy, $industry_top_level_array))
							{
								$headers_array[$tag_copy][0] += ($score * ($industry_n_multi/5)); // add scores for all the sites mapped to this tag
								$sites_badges_array[$row_site['url']]['INDUSTRY_TOPLEVEL_N']=$tag_copy;
							}
							else
							{
								$headers_array[$tag_copy][0]+=($score * $iab_multi); // add scores for all the sites mapped to this tag
							}
						}
						else if (array_key_exists($tag_copy, $industry_top_level_array))
						{
							if (array_key_exists($tag_copy, $industry_child_neighbours_array))
							{
								$headers_array[$tag_copy][0]+=($score * $industry_n_multi); // add scores for all the sites mapped to this tag
								$sites_badges_array[$row_site['url']]['INDUSTRY_CHILD_N']=$tag_copy;
							}
							else if (array_key_exists($tag_copy, $industry_parent_neighbours_array))
							{
								$headers_array[$tag_copy][0]+=($score * ($industry_n_multi/2)); // add scores for all the sites mapped to this tag
								$sites_badges_array[$row_site['url']]['INDUSTRY_PARENT_N']=$tag_copy;
							}
							else if (array_key_exists($tag_copy, $industry_top_level_array))
							{
								$headers_array[$tag_copy][0]+=($score * ($industry_n_multi/5)); // add scores for all the sites mapped to this tag
								$sites_badges_array[$row_site['url']]['INDUSTRY_TOPLEVEL_N']=$tag_copy;
							}
							else
							{
								$headers_array[$tag_copy][0]+=($score * ($industry_n_multi/10)); // add scores for all the sites mapped to this tag
							}
						}
						else 
						{
							if (array_key_exists($tag_copy, $industry_child_neighbours_array))
							{
								$headers_array[$tag_copy][0] += ($score * $industry_n_multi); // add scores for all the sites mapped to this tag
								$sites_badges_array[$row_site['url']]['INDUSTRY_CHILD_N']=$tag_copy;
							}
							else if (array_key_exists($tag_copy, $industry_parent_neighbours_array))
							{
								$headers_array[$tag_copy][0] += ($score * ($industry_n_multi/2)); // add scores for all the sites mapped to this tag
								$sites_badges_array[$row_site['url']]['INDUSTRY_PARENT_N']=$tag_copy;
							}
							else if (array_key_exists($tag_copy, $industry_top_level_array))
							{
								$headers_array[$tag_copy][0] += ($score * ($industry_n_multi/5)); // add scores for all the sites mapped to this tag
								$sites_badges_array[$row_site['url']]['INDUSTRY_TOPLEVEL_N']=$tag_copy;
							}
							else
							{
								$headers_array[$tag_copy][0] += $score; // add scores for all the sites mapped to this tag
							}
						}
						$headers_array[$tag_copy][1][]=$site_id; // only add the id to be used later
						$headers_array[$tag_copy][2][]=$row_site; // add the entire row in 2nd element to be passed to the final array
						$headers_array[$tag_copy][3][$site_id]=$site_id;
						break;
					} // if ends here
				} // site for loop ends here
			} //  iab tag ends here. only one loop remaining now, which is done until we reach 200 used sites

			// at this point all the data is in the headers_array:

			// if there is not even one tag in header
			if (count($headers_array)==0)
			{
				break;	
			}						
			
			$highest_score=0;
			$header_selected="";

			foreach ($headers_array as $key=>$value)
			{
				if ($header_selected == "")
				{
					$header_selected = $key;
				}
				$count_of_sites=count($value[1]);
				if ($count_of_sites == 0)
				{
					$count_of_sites = 1;
				}
				$avg_score=$value[0] / $count_of_sites;
				if ($avg_score > $highest_score)
				{
					$header_selected=$key;
					$highest_score=$avg_score;
				}
			}

			// at this point we picked the iab header with highest score
			$used_headers[]=$header_selected; // add here for a check above, so this is again not taken into account
			
			//skip the header that has less than 3 sites for it
			if (count($headers_array[$header_selected][2]) < 3)
			{
				continue;
			}
				
			if ($debug_flag==true)
			{
				$sites_total_count=count($headers_array[$header_selected][2]);
				$final_headers_arry[$final_counter]=array(
						"header_tag",
						strtoupper($header_selected)." (Score:".$highest_score.") "
				);
			}
			else
			{
				$final_headers_arry[$final_counter]=array(
						"header_tag",
						strtoupper($header_selected)
				);
			}
			$final_counter++;
			$page_counter++;
			
			//sort sites within the selected header
			uasort($headers_array[$header_selected][2], function ($a, $b)
			{
				return $b["score"] - $a["score"];
			});
			
			// take all the sites for chosen header and add them in the used sites 
			/*foreach ($headers_array[$header_selected][1] as $site)
			{
				$used_sites[]=$site; // add here for a check above, so this is again not taken into account
			}*/

			$sites_counter=1;
			// loop thru the selected header and add sites in the main site list
			foreach ($headers_array[$header_selected][2] as $site)
			{	
				if ($debug_flag==true)
				{
					$sites_total_count=count($headers_array[$header_selected][2]);
					$display_sites_ctr++;
					$num_to_show=$display_sites_ctr;
					$badges_data=$sites_badges_array[$site["url"]];

					if ($site["reach"] == "" || $site["gender_male"] == "")
					{
						$final_headers_arry[$final_counter]=$this->populate_demo_row($num_to_show, $site["url"], $site["score"], $sites_counter, $average_array, $badges_data, $debug_flag);
					}
					else
					{
						$final_headers_arry[$final_counter]=$this->populate_demo_row($num_to_show, $site["url"], $site["score"], $sites_counter, $site, $badges_data, $debug_flag);
					}
					$final_counter++;
					$page_counter++;
				}
				else
				{
					if ($site["reach"] == "" || $site["gender_male"] == "")
					{
						$final_headers_arry[$final_counter]=$this->populate_demo_row(null, $site["url"], $site["score"], $sites_counter, $average_array, null, $debug_flag);
					}
					else
					{
						$final_headers_arry[$final_counter]=$this->populate_demo_row(null, $site["url"], $site["score"], $sites_counter, $site, null, $debug_flag);
					}
					// add this only when site is included
					$final_counter++;
					$page_counter++;
				}

				$used_sites[]=$site['site_id']; // this makes sure this site is not duplicated again on the site list. make sure this line is kept above the page break logic.

				// page break logic
				if ($page_counter >= $this->PAGE_ROWS)
				{
					
					if ($total_pages_counter < $this->TOTAL_SITELIST_PAGES)
					{
						// add a page break
						if ($debug_flag==true)
						{
							$final_headers_arry[$final_counter]=array(
									"break_tag type 1 : Show ".$sites_counter." of ".count($headers_array[$header_selected][2])."; truncating remaining sites - ".$header_selected." as they are less than 10",
									"(page break)"
							);
						}
						else
							$final_headers_arry[$final_counter]=array(
									"break_tag",
									"(page break)"
							);
					}
					break; // this break is important. it breaks the for loop for the given header, this ends up throwing all the sites that are remaining for this given header
	
					$total_pages_counter++;
					$page_counter=0;
					$final_counter++;
				}
				$sites_counter++;

				//10 sites per header is moved here + used sites array population
				
				if ($sites_counter >= 11)
				{
					break;// this header got 10 sites, now break
				}
			} // site for loop selected header ends here

			// this if makes sure to add a page break after a header
			if ($page_counter>26)
			{
				if ($total_pages_counter>=$this->TOTAL_SITELIST_PAGES)
					break;
				else
				{
					if ($debug_flag==true)
					{
						$final_headers_arry[$final_counter]=array(
								"break_tag type 3 : Forcing a page break and moving the new header to next page as we are close to ending of this page. Rows ".$page_counter,
								"(page break)"
						);
					}
					else
						$final_headers_arry[$final_counter]=array(
								"break_tag",
								"(page break)"
						);
				}
				$total_pages_counter++;
				$page_counter=0;
				$final_counter++;
			}

			$overall_counter++;
			if ($overall_counter > 500) 
			{ // this is important, this is brute force to make sure the overall while loop doesnt go infinite
				break;
			}

			if ($total_pages_counter>$this->TOTAL_SITELIST_PAGES)
			{
				break;
			}
		}

		//check size of last page
		// if last page has less than 20 rows, delete all rows on last page upto the last break and resize the array
		if ($page_counter < 20)
		{
			$length_final=count($final_headers_arry);
			for ($counter=$length_final-1; $counter > 0; $counter--)
			{
				$tag_name=$final_headers_arry[$counter][0];
				unset($final_headers_arry[$counter]);
				if (!(strpos($tag_name, "break_tag") === false))
				{
					break;
				}	
			}
			$final_headers_arry = array_values($final_headers_arry);
		}
		// check size of last page ends
		return $final_headers_arry;
	}

	private function populate_demo_row($num_to_show, $url, $score, $sites_counter, $site, $badges_data, $debug_flag)
	{
		if ($badges_data==null)
			$badges_data=array();
		
		if (!array_key_exists("INDUSTRY", $badges_data))
		{
			$badges_data["INDUSTRY"]="";
		}
		if (!array_key_exists("IAB", $badges_data))
		{
			$badges_data["IAB"]="";
		}
		if (!array_key_exists("IAB_N", $badges_data))
		{
			$badges_data["IAB_N"]="";
		}
		if (!array_key_exists("STEREO", $badges_data))
		{
			$badges_data["STEREO"]="";
		}
		if (!array_key_exists("INDUSTRY_CHILD_N", $badges_data))
		{
			$badges_data["INDUSTRY_CHILD_N"]="";
		}
		if (!array_key_exists("INDUSTRY_PARENT_N", $badges_data))
		{
			$badges_data["INDUSTRY_PARENT_N"]="";
		}
		if (!array_key_exists("INDUSTRY_TOPLEVEL_N", $badges_data))
		{
			$badges_data["INDUSTRY_TOPLEVEL_N"]="";
		}
		
		if ($debug_flag==true)
		{
			$row=array(
					"(".$num_to_show."-".$sites_counter.") ".$url,
					$score,
					$badges_data['INDUSTRY'],
					$badges_data['IAB'],
					$badges_data['IAB_N'],
					$badges_data['STEREO'],
					$site["reach"],
					$site["gender_male"],
					$site["gender_female"],
					$site["Age_Under18"],
					$site["Age_18_24"],
					$site["Age_25_34"],
					$site["Age_35_44"],
					$site["Age_45_54"],
					$site["Age_55_64"],
					$site["Age_65"],
					$site["Kids_Yes"],
					$site["Kids_No"],
					$site["Income_0_50"],
					$site["Income_50_100"],
					$site["Income_100_150"],
					$site["Income_150"],
					$badges_data['INDUSTRY_CHILD_N'],
					$badges_data['INDUSTRY_PARENT_N'],
					$badges_data['INDUSTRY_TOPLEVEL_N']					  
			);
		}
		else
		{
			$row=array(
					$url,
					$site["reach"],
					$site["gender_male"],
					$site["gender_female"],
					$site["Age_Under18"],
					$site["Age_18_24"],
					$site["Age_25_34"],
					$site["Age_35_44"],
					$site["Age_45_54"],
					$site["Age_55_64"],
					$site["Age_65"],
					"",
					"",
					"",
					"",
					"",
					$site["Kids_Yes"],
					$site["Kids_No"],
					$site["Income_0_50"],
					$site["Income_50_100"],
					$site["Income_100_150"],
					$site["Income_150"],
					"",
					"",
					""
			);
		}
		
		return $row;
	}
	
	// this data is the comscore internet average data for the sites that dont have any demo data
	private function fetch_internet_average_default()
	{
		return array(
				"reach"=>"0",
				"gender_male"=>"0.49",
				"gender_female"=>"0.51",
				"Age_Under18"=>"0.19",
				"Age_18_24"=>"0.11",
				"Age_25_34"=>"0.15",
				"Age_35_44"=>"0.16",
				"Age_45_54"=>"0.15",
				"Age_55_64"=>"0.13",
				"Age_65"=>"0.11",
				"Kids_Yes"=>"0.50",
				"Kids_No"=>"0.50",
				"Income_0_50"=>"0.40",
				"Income_50_100"=>"0.27",
				"Income_100_150"=>"0.18",
				"Income_150"=>"0.15"
		);
	}
	public function get_mpq_data_header($mpq_id, $one_site_list_flag)
	{
		if ($mpq_id=="")
			return;
		
		$bindings=array();
		$bindings[]=$mpq_id;
		$sql="
			SELECT
				c.demographic_data AS demo,
				a.tag_copy,
				COALESCE(a.id,".$this->IAB_NEWS_ID.") AS iabid,
				tag_copy AS iabname,
				region_data
			FROM
				mpq_sessions_and_submissions c
				left join mpq_iab_categories_join b on c.id=b.mpq_id
				left join iab_categories a on a.id = b.iab_category_id 
 				WHERE
				c.id = ?  
			";
		
		$response=$this->db->query($sql, $bindings);
		$return_array=array();
		$demo_string="";
		$return_array_final=array();
		$counter=0;
		$iab_string="";
		$iab_array=array();
		
		foreach ($response->result_array() as $row_data)
		{
			$return_array['demofirst'][$counter]['demo']=$row_data['demo'];
			$return_array['demofirst'][$counter]['iabid']=$row_data['iabid'];
			$return_array['demofirst'][$counter]['region_data']=$row_data['region_data'];
			$iab_array[$counter]=$row_data['iabid'];
			$checked_flag="";
			
			$iab_string.="  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>".($counter+1).".</b> ".$row_data['iabname'];
			if (($counter+1)%2==0)
				$iab_string.="<br> ";
			$counter++;
		}
		
		$sql_industry="
				SELECT
					CONCAT('<b>Industry: </b>', i.name,'<br><br><b>Industry Contextuals:</b><br>', GROUP_CONCAT(c.tag_copy SEPARATOR '<br><br>')) AS industry
			FROM
				freq_industry_tags i,
 				freq_industry_tags_iab_link b,
				iab_categories c,
				mpq_sessions_and_submissions m
			WHERE
				m.id = ? AND
				m.industry_id=i.freq_industry_tags_id AND
				i.freq_industry_tags_id=b.freq_industry_tags_id AND
				b.iab_categories_id=c.id";
		
		$response_industry=$this->db->query($sql_industry, $bindings);
		foreach ($response_industry->result_array() as $row_data)
		{
			$return_array_final['industry_id']=$row_data['industry'];
			break;
		}
		if ($return_array_final['industry_id'] == null)
			return;
		$return_array_final['context_id']=$iab_string; // #2
		$return_array_final['demo_id']=$this->get_demo_string($return_array['demofirst'][0]['demo']); // #3
																									  // region
		if ($one_site_list_flag == true)
		{
			$region_data_array=json_decode($return_array['demofirst'][0]['region_data'], true);
			$this->map->convert_old_flexigrid_format($region_data_array);
			if (array_key_exists('0', $region_data_array))
				$ids = $region_data_array[0]['ids'];
			else
				$ids = $region_data_array['ids'];

			$zip_array = $ids['zcta'];

			$zip_array_var = implode(", ", $zip_array);
			$return_array_final['zip_id'] = $zip_array_var; // #4
		} 
		else
		{
			$return_array_final['zip_id']=array();
		}

		$return_array_final['product_type_flag']=$this->get_product_type_for_mpq($mpq_id);
		return $return_array_final;
	}

	private function get_demo_string($demo_array_string)
	{
		$demo_string="";
		$demo_array=explode("_", $demo_array_string);
		if (!($demo_array[0]=="1"&&$demo_array[1]=="1"))
		{
			if ("1"==$demo_array[0])
				$demo_string.='Gender: Male'."<br>";
			if ("1"==$demo_array[1])
				$demo_string.='Gender: Female'."<br>";
		}
		if (!($demo_array[2]=="1"&&$demo_array[3]=="1"&&$demo_array[4]=="1"&&$demo_array[5]=="1"&&$demo_array[6]=="1"&&$demo_array[7]=="1"&&$demo_array[8]=="1"))
		{
			if ("1"==$demo_array[2])
				$demo_string.='Age: under 18'."<br>";
			if ("1"==$demo_array[3])
				$demo_string.='Age: 18 to 24'."<br>";
			if ("1"==$demo_array[4])
				$demo_string.='Age: 25 to 34'."<br>";
			if ("1"==$demo_array[5])
				$demo_string.='Age: 35 to 44'."<br>";
			if ("1"==$demo_array[6])
				$demo_string.='Age: 45 to 54'."<br>";
			if ("1"==$demo_array[7])
				$demo_string.='Age: 55 to 64'."<br>";
			if ("1"==$demo_array[8])
				$demo_string.='Age: 65 and older'."<br>";
		}
		
		if (!($demo_array[9]=="1"&&$demo_array[10]=="1"&&$demo_array[11]=="1"&&$demo_array[12]=="1"))
		{
			if ("1"==$demo_array[9])
				$demo_string.='Income: under 50k'."<br>";
			if ("1"==$demo_array[10])
				$demo_string.='Income: 50k to 100k'."<br>";
			if ("1"==$demo_array[11])
				$demo_string.='Income: 100k to 150k'."<br>";
			if ("1"==$demo_array[12])
				$demo_string.='Income: over 150k'."<br>";
		}
		
		if (!($demo_array[13]=="1"&&$demo_array[14]=="1"&&$demo_array[15]=="1"))
		{
			if ("1"==$demo_array[13])
				$demo_string.='Education: No college'."<br>";
			if ("1"==$demo_array[14])
				$demo_string.='Education: College'."<br>";
			if ("1"==$demo_array[15])
				$demo_string.='Education: Grad school'."<br>";
		}
		
		if (!($demo_array[16]=="1"&&$demo_array[17]=="1"))
		{
			if ("1"==$demo_array[16])
				$demo_string.='Parenting: No'."<br>";
			if ("1"==$demo_array[17])
				$demo_string.='Parenting: Yes'."<br>";
		}
		
		return $demo_string;
	}
	
	private function get_local_sites($mpq_id, $zips_list, $ttd_flag, $one_site_list_flag)
	{
		$product_type_flag=$this->get_product_type_for_mpq($mpq_id);
		$return_array=array();
		$bindings=array();
		// Non-numeric zips need to be converted to strings before the query
		$zips_list = implode(', ', array_map(function($zip){
			return (is_numeric($zip)) ? $zip : "'{$zip}'";
		}, explode(', ', $zips_list)));
		$bindings[]=$zips_list;
		$sites_url_array=array();
		
		$preroll_sql=" ";
		if ($product_type_flag == 'P')
		{
			$preroll_sql=" AND b.preroll_site_flag = 1  ";
		}		 

		$query_local_reach="
			SELECT   
				   url AS url, 
		           freq_sites_main_id AS id, 
		           ROUND(( total_audience / 350000000 ) * 100, 2) AS reach, 
		           distance1, 
		           '' AS regionname,
		           ROUND(male_all * 1/total_audience, 2)                                     AS gender_male,
			       ROUND(female_all * 1/total_audience, 2)                                   AS gender_female,
			       ROUND(0.2*(( male_18_24 + female_18_24 ) * 1/total_audience), 2) 		AS Age_Under18,
				   ROUND(0.8*(( male_18_24 + female_18_24 ) * 1/total_audience), 2)          AS Age_18_24,
			       ROUND(( male_25_34 + female_25_34 ) * 1/total_audience, 2)                AS Age_25_34,
			       ROUND(( male_35_44 + female_35_44 ) * 1/total_audience, 2)                AS Age_35_44,
			       ROUND(( male_45_54 + female_45_54 ) * 1/total_audience, 2)                AS Age_45_54,
			       ROUND(( male_55_64 + female_55_64 ) * 1/total_audience, 2)                AS Age_55_64,
			       ROUND(( male_over65 + female_over65 ) * 1/total_audience, 2)              AS Age_65,
			       ROUND(children_no * 1/total_audience, 2)                                  AS Kids_No,
			       ROUND(children_yes * 1/total_audience, 2)                                 AS Kids_Yes,
			       ROUND((hh_income_under25k + hh_income_25k_40k + hh_income_40k_60k ) * 1/total_audience, 2)    AS Income_0_50,
			       ROUND(( hh_income_60k_75k + hh_income_75k_100k ) * 1/total_audience, 2)   AS Income_50_100,
			       ROUND((1-((hh_income_under25k + hh_income_25k_40k + hh_income_40k_60k + 
						hh_income_60k_75k + hh_income_75k_100k)/total_audience))* 0.6, 2) AS Income_100_150,
			       ROUND((1-((hh_income_under25k + hh_income_25k_40k + hh_income_40k_60k + 
						hh_income_60k_75k + hh_income_75k_100k)/total_audience))* 0.4, 2) AS Income_150
			FROM     ( 
	                   SELECT    
								 main.url , 
	                             main.distance1, 
	                             d.* , 
	                             main.freq_sites_main_id 
	                   FROM      
								( 
	                                      SELECT   url, 
	                                               a.freq_sites_main_id, 
	                                               a.tag_id, 
	                                               b.comscore_site_id, 
	                                               c.intptlat10, 
	                                               center.lat, 
	                                               c.intptlon10, 
	                                               center.lng, 
	                                               ( ROUND(69 * DEGREES(ACOS(COS(Radians(c.intptlat10)) * 
													COS(RADIANS(center.lat)) * COS(RADIANS(c.intptlon10 - center.lng)) +
													SIN(RADIANS(c.intptlat10)) * SIN(RADIANS(center.lat)))), 2) ) AS distance1
	                                      FROM     
													freq_site_tagging_link a 
	                                      JOIN     	freq_sites_main b ON 
	                                      				a.freq_sites_main_id=b.freq_sites_main_id AND
	                                      				b.is_rejected_flag IS NULL AND 
	                                      				b.is_bidder_only_flag IS NULL
	                                      JOIN     	geo_place_map c ON a.tag_id=c.id_auto 
	                                      JOIN 
	                                               ( 
	                                                      SELECT AVG(Y(center_point)) AS lat, 
	                                                             AVG(X(center_point)) AS lng 
	                                                      FROM   
															geo_polygons 
	                                                      WHERE  
															local_id IN (".$zips_list.")) center 
	                                      WHERE    
												a.tag_type_id=2 
												$preroll_sql
	                                      HAVING   
												distance1 < 500 
	                                      ORDER BY 
												distance1 LIMIT "; 
		
		if ($ttd_flag==true)
		{
			$query_local_reach.=" 100 ";
		}
		else
		{
			$query_local_reach.=" 15 ";
		}	
							
		$query_local_reach.=		" ) 
									AS main 
	                   LEFT JOIN 
							comscore_demo_data d ON main.comscore_site_id=d.comscore_site_id 
	                   LEFT JOIN
							comscore_geo_main e ON e.comscore_geo_id=d.comscore_geo_main_id 
	                   ORDER BY
							main.distance1, 
	                        d.total_audience DESC 
			) AS otr 
			GROUP BY
				 otr.url 
			ORDER BY
				 otr.distance1, 
			     otr.url
		";
		
		$response=$this->db->query($query_local_reach, $bindings);
		if ($one_site_list_flag == true)
		{
			return $response->result_array();
		}
		else
		{
			$data_array=array();
			$data_array_counter=0;
			$final_headers_arry=array();
			$average_array=$this->fetch_internet_average_default();

			foreach ($response->result_array() as $row_data)
			{
				$sites_url_array[]=$row_data["url"];	

				if ($row_data["reach"]==""||$row_data["gender_male"]=="")
				{
					$final_headers_arry[$data_array_counter]=$this->populate_demo_row(null, $row_data["url"], null, null, $average_array, null, false);
				}
				else
				{
					$final_headers_arry[$data_array_counter]=$this->populate_demo_row(null, $row_data["url"], null, null, $row_data, null, false);
				}
				$data_array_counter++;	
			}		
			return $final_headers_arry;
		}
	}

	function generate_save_local_sites_proposal($mpq_id)
	{
		//step 1: get all records from prop_gen_Sessions and loop.
		$local_query="
			SELECT lap_id,
				region_data
			FROM
				prop_gen_sessions
			WHERE 
				source_mpq=?
				";

		$response=$this->db->query($local_query, $mpq_id);
		
		foreach ($response->result_array() as $row_data)
		{
			$region_data_array_raw=$row_data['region_data'];
			$lap_id=$row_data['lap_id'];
			$region_data_array=json_decode($region_data_array_raw, true);
			$this->map->convert_old_flexigrid_format($region_data_array);
			$zip_array = $region_data_array['ids']['zcta'];

			$zips_list = implode(", ", $zip_array);
			$local_sites_array = $this->get_local_sites($mpq_id, $zips_list, false, false);

			if ($local_sites_array != "" && count($local_sites_array) > 0)
			{
				$sites_string=json_encode($local_sites_array);
				$local_query="
					UPDATE 
						prop_gen_sessions
					SET 
						site_array=?
					WHERE 
						lap_id=?
					";
				$binding_array=array();
				$binding_array[]=$sites_string;
				$binding_array[]=$lap_id;
				$this->db->query($local_query, $binding_array);
			}
		}
	}


	public function get_site_rankings_proposal($mpq_id)
	{
		$result['context_array']=$this->get_sites_for_mpq($mpq_id, null, null, null, null, null, null, null, true, false);
		$main_array=array();
		$header="-1";
		foreach ($result['context_array'] as $row)
		{
			if ($row[0] == "header_tag")
			{
				$header = $row[1];
				$header_score = strstr(substr($header, strpos($header, ' (Score:')+8, strpos($header, ')')), ')', true);
				$header = strstr($header, ' (Score:', true);
				$main_array[$header]=array();
				$main_array[$header]['header_score']=$header_score;
				$main_array[$header]['sites']=array();
			}
			else if (strpos($row[0],'break_tag') !== false) {
			   // just ignore the break tags...
			}
			else 
			{
				$site_name=trim(substr($row[0], strpos($row[0], ')')+1));
				$main_array[$header]['sites'][$site_name]=array();
				$main_array[$header]['sites'][$site_name]['site_score']=$row[1];
				
				if ($row[2] != "")
				{
					$industry_array=explode("<br>", $row[2]);
					$main_array[$header]['sites'][$site_name]['industries']=array();
					foreach ($industry_array as $industry_row)
					{
						if ($industry_row != "")
						{
							$industry_name = substr($industry_row, 0, strrpos($industry_row,'-') );
							$industry_score = str_replace("<br>", "", (substr($industry_row, strrpos($industry_row, '-')+1)));
							$industry_details=array();
							$industry_details['name']=$industry_name;
							$industry_details['score']=$industry_score;
							$main_array[$header]['sites'][$site_name]['industries'][]=$industry_details;
						}
					} 
				}
				
				if ($row[3] != "")
				{	
					$iab_array=explode("<br>", $row[3]);
					$main_array[$header]['sites'][$site_name]['iab_categories']=array();
					foreach ($iab_array as $iab_row)
					{
						if ($iab_row != "")
						{
							$iab_name = substr($iab_row, 0, strrpos($iab_row,'-') );
							$iab_score = str_replace("<br>", "", (substr($iab_row, strrpos($iab_row, '-')+1)));
							$iab_details=array();
							$iab_details['name']=$iab_name;
							$iab_details['score']=$iab_score;
							$main_array[$header]['sites'][$site_name]['iab_categories'][]=$iab_details;
						}
					} 
				}

				if ($row[4] != "")
				{
					$iab_n_array=explode("<br>", $row[4]);
					$main_array[$header]['sites'][$site_name]['iab_n_categories']=array();
					foreach ($iab_n_array as $iab_n_row)
					{
						if ($iab_n_row != "")
						{
							$iab_n_name = substr($iab_n_row, 0, strrpos($iab_n_row,'-') );
							$iab_n_score = str_replace("<br>", "", (substr($iab_n_row, strrpos($iab_n_row, '-')+1)));
							$iab_n_details=array();
							$iab_n_details['name']=$iab_n_name;
							$iab_n_details['score']=$iab_n_score;
							$main_array[$header]['sites'][$site_name]['iab_n_categories'][]=$iab_n_details;
						}
					} 					
				}

				if ($row[5] != "")
				{
					$stereotype_array=explode("<br>", $row[5]);
					$main_array[$header]['sites'][$site_name]['stereotypes']=array();
					foreach ($stereotype_array as $stereotype_row)
					{
						if ($stereotype_row != "")
						{
							$stereotype_name = substr($stereotype_row, 0, strrpos($stereotype_row,'-') );
							$stereotype_score = str_replace("<br>", "", (substr($stereotype_row, strrpos($stereotype_row, '-')+1)));
							$stereotype_details=array();
							$stereotype_details['name']=$stereotype_name;
							$stereotype_details['score']=$stereotype_score;
							$main_array[$header]['sites'][$site_name]['stereotypes'][]=$stereotype_details;
						}
					} 				
				}

				if ($row[22] != "")
				{
					$main_array[$header]['sites'][$site_name]['INDUSTRY_CHILD_NEIGH']=$row[22];				
				}

				if ($row[23] != "")
				{
					$main_array[$header]['sites'][$site_name]['INDUSTRY_PARENT_NEIGH']=$row[23];	
				}

				if ($row[24] != "")
				{
					$main_array[$header]['sites'][$site_name]['INDUSTRY_TOPLEVEL_NEIGH']=$row[24];
				}
				
				$main_array[$header]['sites'][$site_name]['reach-score']=$row[6];
				
				$demo_array=array();

				$demo_array['male-demo']=$row[7];
				$demo_array['female-demo']=$row[8];

				$demo_array['age-under-18-demo']=$row[9];
				$demo_array['age-18-24-demo']=$row[10];
				$demo_array['age-25-34-demo']=$row[11];
				$demo_array['age-35-44-demo']=$row[12];
				$demo_array['age-45-54-demo']=$row[13];
				$demo_array['age-55-64-demo']=$row[14];
				$demo_array['age-65-demo']=$row[15];

				$demo_array['kids-demo']=$row[16];
				$demo_array['no-kids-demo']=$row[17];

				$demo_array['income-0-50-demo']=$row[18];
				$demo_array['income-50-100-demo']=$row[19];
				$demo_array['income-100-150-demo']=$row[20];
				$demo_array['income-over-150-demo']=$row[21];

				$main_array[$header]['sites'][$site_name]['demographics']=$demo_array;
			}			
		}
		$main_array_final=array();
		$main_array_final['site_results']=$main_array;

		$sql = '
            UPDATE prop_gen_prop_data
            SET site_list_widget = ?
            WHERE source_mpq = ?
        ';
        $this->db->query($sql, array(json_encode($main_array_final), $mpq_id));
		if($this->db->affected_rows() > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	private function get_product_type_for_mpq($mpq_id)
	{
		$product_type = "ALL";
		if ($mpq_id == null || $mpq_id == "")
		{
			return $product_type;
		}			

		$sql = "
			SELECT 
				DISTINCT banner_intake_id 
			FROM 
				cp_products p, 
				cp_submitted_products sp 
			WHERE 
				sp.product_id=p.id AND
				sp.mpq_id=?
		";

		$response=$this->db->query($sql, $mpq_id);
		$preroll_flag = false;
		$display_flag = false;
		foreach ($response->result_array() as $row_data)
		{
			$product_type = $row_data['banner_intake_id'];
			if ($product_type == 'Preroll')
			{
				$preroll_flag = true;
			} 
			else if ($product_type == 'Display')
			{
				$display_flag = true;
			} 
		}

		if ($display_flag)
		{
			$product_type = "ALL";
		}
		else if (!$display_flag && $preroll_flag)
		{
			$product_type = "P";
		}
		
		return $product_type;
	}
}

