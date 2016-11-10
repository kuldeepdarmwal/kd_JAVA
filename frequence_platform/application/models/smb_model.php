<?php

class smb_model extends CI_Model 
{
	public function __construct()
	{
		$this->load->database();                
	}

	public function getDemoSitePack($sitePackName)
	{
		$bindings = array($sitePackName);
		$query = "SELECT domain, local_reach, channel, site_score FROM smb_site_packs WHERE site_pack_name LIKE ? AND type = 'Demo'";
		$response = $this->db->query($query, $bindings);
		return $response;
	}

	public function get_iab_categories_from_session_table($session_id)
	{
		$sql = "SELECT a.id as id, a.tag_copy as tag_copy FROM iab_categories a JOIN media_plan_sessions_iab_categories_join b ON a.id = b.iab_category_id JOIN media_plan_sessions c ON b.media_plan_lap_id = c.lap_id WHERE c.session_id = ? ORDER BY a.tag_copy";
		$query = $this->db->query($sql, $session_id);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return false;
	}

	public function get_channels_from_session_table($session_id)
	{
		$query = "SELECT selected_channels FROM media_plan_sessions  WHERE `session_id` = '".$session_id."'";
$response = $this->db->query($query);
		$raw_channels = $response->row(0)->selected_channels;
		$channels = array();
		if(!empty($raw_channels))
		{
			$channels = explode("|", $raw_channels);
		}
		return $channels;
	}

	public function getChannelSitePack($sitePackName)
	{
		$bindings = array($sitePackName);
		$query = "SELECT domain, local_reach, channel, site_score FROM smb_site_packs WHERE site_pack_name LIKE ? AND type = 'Channel'";
		$response = $this->db->query($query, $bindings);
		return $response;
	}

	public function getSitePackCoverage($sitePackName)
	{
		$bindings = array($sitePackName);
		$query = "SELECT demo, geo_reach, demo_reach FROM smb_site_pack_coverage WHERE site_pack_name LIKE ?";
		$response = $this->db->query($query, $bindings);
		return $response;
	}

	public function get_population($session_id)
	{
		$the_query = 
		"	SELECT 
				`population` 
			FROM 
				`media_plan_sessions`
			WHERE 
				`session_id` = ?
		";
		$query = $this->db->query($the_query, $session_id);
		$response = $query->row();

		return $response->population;
	}

	public function getMediaTargetingSitePackCategories()
	{
		$query = "SELECT DISTINCT(site_pack_name) as sitePackName FROM smb_site_packs";
		$response = $this->db->query($query);
		return $response;
	}

	public function getInternetMean()
	{
		$query = "
			SELECT 
				SUM(Reach) as totalReach,
				SUM(Reach * Gender_Male) AS gm,
				SUM(Reach * Gender_Female) AS gf,
				SUM(Reach * Age_Under18) AS au18,
				SUM(Reach * Age_18_24) AS a1824,
				SUM(Reach * Age_25_34) AS a2534,
				SUM(Reach * Age_35_44) AS a3544,
				SUM(Reach * Age_45_54) AS a4554,
				SUM(Reach * Age_55_64) AS a5564,
				SUM(Reach * Age_65) AS a65,
				SUM(Reach * Race_Cauc) AS rc,
				SUM(Reach * Race_Afr_Am) AS raa,
				SUM(Reach * Race_Asian) AS ra,
				SUM(Reach * Race_Hisp) AS rh,
				SUM(Reach * Race_Other) AS ro,
				SUM(Reach * Kids_No) AS kn,
				SUM(Reach * Kids_Yes) AS ky,
				SUM(Reach * Income_0_50) AS i050,
				SUM(Reach * Income_50_100) AS i50100,
				SUM(Reach * Income_100_150) AS i100150,
				SUM(Reach * Income_150) AS i150,
				SUM(Reach * College_No) AS cn,
				SUM(Reach * College_Under) AS cu,
				SUM(Reach * College_Grad) AS cg
			FROM smb_all_sites AS dr 
			";
		$response = $this->db->query($query);
		return $response;
	}

	public function getInternetStandardDeviation()
	{
		$query = "
			SELECT 
				STDDEV_POP(Gender_Male) AS gm,
				STDDEV_POP(Gender_Female) AS gf,
				STDDEV_POP(Age_Under18) AS au18,
				STDDEV_POP(Age_18_24) AS a1824,
				STDDEV_POP(Age_25_34) AS a2534,
				STDDEV_POP(Age_35_44) AS a3544,
				STDDEV_POP(Age_45_54) AS a4554,
				STDDEV_POP(Age_55_64) AS a5564,
				STDDEV_POP(Age_65) AS a65,
				STDDEV_POP(Race_Cauc) AS rc,
				STDDEV_POP(Race_Afr_Am) AS raa,
				STDDEV_POP(Race_Asian) AS ra,
				STDDEV_POP(Race_Hisp) AS rh,
				STDDEV_POP(Race_Other) AS ro,
				STDDEV_POP(Kids_No) AS kn,
				STDDEV_POP(Kids_Yes) AS ky,
				STDDEV_POP(Income_0_50) AS i050,
				STDDEV_POP(Income_50_100) AS i50100,
				STDDEV_POP(Income_100_150) AS i100150,
				STDDEV_POP(Income_150) AS i150,
				STDDEV_POP(College_No) AS cn,
				STDDEV_POP(College_Under) AS cu,
				STDDEV_POP(College_Grad) AS cg
			FROM smb_all_sites AS dr 
			";
		$response = $this->db->query($query);
		return $response;
	}

        
         
        public function get_sites($demos_selected, $geo_sums)
        {
						if($geo_sums==false || $geo_sums['success']==false)
						{
							return false;
						}

            $this->load->library('table');
            $demo_array = explode("||",urldecode($demos_selected));            
            $demo_include_array = explode("_",urldecode($demo_array[0]));
            $query_array="";
            if(!empty($demo_array[1])){
            $demo_site_array = explode("|",urldecode($demo_array[1]));
            
            $query_array="";
            foreach ($demo_site_array as $val){
            $site_name = explode("SITES_",urldecode($val));
            $query_array=$query_array.",'".$site_name[1]."'";
            }
            $query_array=trim($query_array,",");
            }
            if ($demo_include_array[0] === $demo_include_array[1]){
                $demo_include_array[0] = 1;
                $demo_include_array[1] = 1;
            }
            
            if (($demo_include_array[2]=== $demo_include_array[3])
              &&($demo_include_array[3]=== $demo_include_array[4])
              &&($demo_include_array[4]=== $demo_include_array[5])
              &&($demo_include_array[5]=== $demo_include_array[6])
              &&($demo_include_array[6]=== $demo_include_array[7])
              &&($demo_include_array[7]=== $demo_include_array[8]))
             {
                    $demo_include_array[2] = 1;
                    $demo_include_array[3] = 1;
                    $demo_include_array[4] = 1;
                    $demo_include_array[5] = 1;
                    $demo_include_array[6] = 1;
                    $demo_include_array[7] = 1;
                    $demo_include_array[8] = 1;
            }
            
             if (($demo_include_array[9]=== $demo_include_array[10])
              &&($demo_include_array[10]=== $demo_include_array[11])
              &&($demo_include_array[11]=== $demo_include_array[12]))
             {
                    $demo_include_array[9] = 1;
                    $demo_include_array[10] = 1;
                    $demo_include_array[11] = 1;
                    $demo_include_array[12] = 1;
            }
            
             if (($demo_include_array[13]=== $demo_include_array[14])
              &&($demo_include_array[14]=== $demo_include_array[15]))
             {
                    $demo_include_array[13] = 1;
                    $demo_include_array[14] = 1;
                    $demo_include_array[15] = 1;
            }
            
              if (($demo_include_array[16]=== $demo_include_array[17]))
             {
                    $demo_include_array[16] = 1;
                    $demo_include_array[17] = 1;
            }
            
            
             if (($demo_include_array[18]=== $demo_include_array[19])
              &&($demo_include_array[19]=== $demo_include_array[20])
              &&($demo_include_array[20]=== $demo_include_array[21])
              &&($demo_include_array[21]=== $demo_include_array[22]))
             {
                    $demo_include_array[18] = 1;
                    $demo_include_array[19] = 1;
                    $demo_include_array[20] = 1;
                    $demo_include_array[21] = 1;
                    $demo_include_array[22] = 1;
            }
            
            //$demo_include_array[23] slider
            //$demo_include_array[24] category
            $site_category_preference = $demo_include_array[24];
            //25   and up are sites
            $site_include_string = "Domain = 'thissitedoesntexist'";
            for ($i = 25; $i < count($demo_include_array); $i++){
                $site_include_string .= " OR Domain = '".$demo_include_array[$i]."'";
            }
            
            if ($site_category_preference=='All'){
                $site_category_preference = '%';
            }
            if ($site_category_preference=='None Specified'){
                $site_category_preference = 'null';
            }
//            echo $site_include_string."<br>";
//            $site_include_string = "";
            $demo_table_name = 'smb_all_sites';//'DemographicRecords_TEST';//'DemographicRecords_01_20_2012'
            
//***FLAG*** contains order information for demographic choice flags in the session table. #choosedemo 
            $sumproduct_string = "((".$demo_include_array[0]."*Gender_Male+
                                            ".$demo_include_array[1]."*Gender_Female)*
                                     (".$demo_include_array[2]."*Age_Under18 + 
                                            ".$demo_include_array[3]."*Age_18_24 +
                                            ".$demo_include_array[4]."*Age_25_34 + 
                                            ".$demo_include_array[5]."*Age_35_44 +
                                            ".$demo_include_array[6]."*Age_45_54 +
                                            ".$demo_include_array[7]."*Age_55_64 +
                                            ".$demo_include_array[8]."*Age_65)*
                                    (".$demo_include_array[9]."*Income_0_50 +
                                        ".$demo_include_array[10]."*Income_50_100 +
                                        ".$demo_include_array[11]."*Income_100_150 +
                                        ".$demo_include_array[12]."*Income_150)*
                                    (".$demo_include_array[13]."*College_No +
                                        ".$demo_include_array[14]."*College_Under +
                                        ".$demo_include_array[15]."*College_Grad)*
                                    (".$demo_include_array[16]."*Kids_No +
                                        ".$demo_include_array[17]."*Kids_Yes)*
                                    (".$demo_include_array[18]."*Race_Cauc +
                                        ".$demo_include_array[19]."*Race_Afr_Am +
                                        ".$demo_include_array[20]."*Race_Asian +
                                        ".$demo_include_array[21]."*Race_Hisp +
                                        ".$demo_include_array[22]."*Race_Other))";
            
            
            ////get statics to scale the colums for ranking
            $the_statics_query = "SELECT AVG(Demo) as ave_demo, STD(Demo) as stdev_demo, AVG(Aud_Reach) as ave_aud_reach, STD(Aud_Reach) as stdev_aud_reach FROM
                                            (SELECT".$sumproduct_string." as Demo,".$sumproduct_string."*Reach as Aud_Reach
                                                FROM ".$demo_table_name."
                                                WHERE is_algo='TRUE') derived ";
            //$this->db->query($the_statics_query);
            
            
            $normalizing_array = $this->db->query($the_statics_query)-> result_array();
            //print '<pre>'; print_r($normalizing_array); print '</pre>';
            ///get standard dev of Demo
            
            ////get proxy for average Aud_Reach
            ///get standard dev of Aud_Reach
            
            
            //echo "slider ".$demo_include_array[23]."<br>";
            
            //print '<pre>'; print_r($geo_sums); print '</pre>';
            if($query_array!=""){
            $the_query = "SELECT * FROM
                            ((SELECT Domain as domain,
                                    round(".$sumproduct_string." ,5) as Targeting_Efficacy, 
                                    round(".$sumproduct_string."*Reach/".$geo_sums['internet_average'].",5) as Demo_Coverage,       
                                    round(((".$sumproduct_string."-".$normalizing_array[0]['ave_demo'].")/".$normalizing_array[0]['stdev_demo'].")*".($demo_include_array[23]/100)."+((".$sumproduct_string."*Reach-".$normalizing_array[0]['ave_aud_reach'].")/".$normalizing_array[0]['stdev_aud_reach'].")*".((100-$demo_include_array[23])/100).",5) as score
                                    FROM ".$demo_table_name."
                                    WHERE ((AdGroupID = '' OR AdGroupID = 'default') AND
                                    Domain IN (".$query_array.")) 
                                    ORDER BY score 
                                    DESC LIMIT 30) 
                              UNION
                              (SELECT Domain,
                                    Reach, 
                                    round(".$sumproduct_string." ,5) as Targeting_Efficacy, 
                                    round(".$sumproduct_string."*Reach/".$geo_sums['internet_average'].",5) as Demo_Coverage,       
                                    round(((".$sumproduct_string."-".$normalizing_array[0]['ave_demo'].")/".$normalizing_array[0]['stdev_demo'].")*".($demo_include_array[23]/100)."+((".$sumproduct_string."*Reach-".$normalizing_array[0]['ave_aud_reach'].")/".$normalizing_array[0]['stdev_aud_reach'].")*".((100-$demo_include_array[23])/100).",5) as score2
                                    FROM ".$demo_table_name."
                                    WHERE ((AdGroupID = '' OR AdGroupID = 'default') AND
                                    (".$site_include_string.")) 
                                    ORDER BY score2 
                                    DESC LIMIT 30) ) winners
                          ORDER BY winners.Reach DESC";
         }else{
                $the_query = "(SELECT Site as domain,
                                    Reach as local_reach,
                                    Category as channel,
                                    round(((".$sumproduct_string."-".$normalizing_array[0]['ave_demo'].")/".$normalizing_array[0]['stdev_demo'].")*".($demo_include_array[23]/100)."+((".$sumproduct_string."*Reach-".$normalizing_array[0]['ave_aud_reach'].")/".$normalizing_array[0]['stdev_aud_reach'].")*".((100-$demo_include_array[23])/100).",5)+100 as site_score
                                    FROM ".$demo_table_name."
                                    WHERE is_algo='TRUE'
                                    ORDER BY site_score
                                    DESC LIMIT 30)";
                              
         }
            return $this->db->query($the_query);
        }
        


        
	public function getRealizedValue($sitePackName)
	{
		$bindings = array($sitePackName);
		$query = "
			SELECT 
				Reach as totalReach,
				Reach * Gender_Male AS gm,
				Reach * Gender_Female AS gf,
				Reach * Age_Under18 AS au18,
				Reach * Age_18_24 AS a1824,
				Reach * Age_25_34 AS a2534,
				Reach * Age_35_44 AS a3544,
				Reach * Age_45_54 AS a4554,
				Reach * Age_55_64 AS a5564,
				Reach * Age_65 AS a65,
				Reach * Race_Cauc AS rc,
				Reach * Race_Afr_Am AS raa,
				Reach * Race_Asian AS ra,
				Reach * Race_Hisp AS rh,
				Reach * Race_Other AS ro,
				Reach * Kids_No AS kn,
				Reach * Kids_Yes AS ky,
				Reach * Income_0_50 AS i050,
				Reach * Income_50_100 AS i50100,
				Reach * Income_100_150 AS i100150,
				Reach * Income_150 AS i150,
				Reach * College_No AS cn,
				Reach * College_Under AS cu,
				Reach * College_Grad AS cg
			FROM smb_all_sites
			WHERE Site LIKE ?";
		$response = $this->db->query($query, $bindings);
		return $response;
	}
}
?>
