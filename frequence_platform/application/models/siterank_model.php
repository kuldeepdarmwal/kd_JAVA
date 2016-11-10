<?php
class Siterank_model extends CI_Model {

	public function __construct()
	{
		$this->load->database();
		$this->load->model('lap_lite_model');
	}
         
        public function save_iab_categories_to_session($session_id, $iab_categories)
		{
			$sql = "SELECT lap_id FROM media_plan_sessions WHERE session_id = ?";
			$query = $this->db->query($sql, $session_id);
			if($query->num_rows() > 0)
			{
				$lap_id = $query->row()->lap_id;
				$sql = "DELETE FROM media_plan_sessions_iab_categories_join WHERE media_plan_lap_id = ?";
				$query = $this->db->query($sql, $lap_id);
				foreach($iab_categories as $v)
				{
					$sql = "INSERT INTO media_plan_sessions_iab_categories_join (media_plan_lap_id, iab_category_id) VALUES(?, ?)";
					$query = $this->db->query($sql, array($lap_id, $v));
				}
			}
			else
			{
				return false;
			}
        }
        public function save_channels_to_session_table($session_id, $channels){
						$bindings = array($channels, $session_id);
            $the_query = "UPDATE `media_plan_sessions` 
                            SET `selected_channels` = ?
                            WHERE `session_id` = ?";
            $this->db->query($the_query, $bindings);
        }
        public function load_session_sites($session_id,$query_result){
            $json_sites = json_encode($query_result->result_array());
            $the_query = "UPDATE `media_plan_sessions` 
                            SET `site_array` = '".$json_sites."'
                            WHERE `session_id` = '".$session_id."'";
            $this->db->query($the_query);
        }
        
        public function load_demographic_settings_to_session($session_id,$demos){
            $the_query = "UPDATE `media_plan_sessions` 
                            SET `demographic_data` = '".$demos."'
                            WHERE `session_id` = '".$session_id."'";
            $this->db->query($the_query);
        }
        public function save_impressions_to_session_table($demo_info, $session_id) {
            $nIMPRESSIONS = 0;
            $nGEO_COV = 1;
            $nGAMMA = 2;
            $nIP_ACC = 3;
            $nDEMO_COV_OVERRIDE = 4;
            $nRETARGETING = 5;

            $demo = explode("|",urldecode($demo_info));
            $impressions = $demo[$nIMPRESSIONS];
             $the_query = "UPDATE `media_plan_sessions` 
                            SET `recommended_impressions` = '".$impressions."'
                            WHERE `session_id` = '".$session_id."'";
            $this->db->query($the_query);
        }
        public function get_demographic_settings_from_session($session_id){
            $the_query = "SELECT `demographic_data` FROM `media_plan_sessions`
                            WHERE `session_id` = '".$session_id."'";
						$response = $this->db->query($the_query);
						return $response->row(0)->demographic_data;
        }

		public function save_demographics_and_sites_to_session_table($demos_selected, $sessionId)
		{
			$demos_selected = urldecode($demos_selected);
			$this->load_demographic_settings_to_session($sessionId, $demos_selected);

			$isSuccess = false;
			$geo_sums = $this->lap_lite_model->get_geo_sums($sessionId);
			if($geo_sums['success'] == true)
			{

				$query_result = $this->get_siterank_results($demos_selected, $geo_sums);
				if($query_result != false)
				{
					$this->load_session_sites($this->session->userdata('session_id'),$query_result);
					$isSuccess = true;
				}
			}

			return $isSuccess;
		}

        public function get_siterank_results($demos_selected, $geo_sums)
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
            $demo_table_name = 'smb_algo_sites';//'DemographicRecords_TEST';//'DemographicRecords_01_20_2012'
            
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
                                                WHERE AdGroupID = '' AND
                                                Domain != '') derived ";
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
                $the_query = "SELECT * FROM
                            ((SELECT Domain,
                                    Reach,
                                    round(".$sumproduct_string." ,5) as Targeting_Efficacy,
                                    round(".$sumproduct_string."*Reach/".$geo_sums['internet_average'].",5) as Demo_Coverage,
                                    round(((".$sumproduct_string."-".$normalizing_array[0]['ave_demo'].")/".$normalizing_array[0]['stdev_demo'].")*".($demo_include_array[23]/100)."+((".$sumproduct_string."*Reach-".$normalizing_array[0]['ave_aud_reach'].")/".$normalizing_array[0]['stdev_aud_reach'].")*".((100-$demo_include_array[23])/100).",5) as score
                                    FROM ".$demo_table_name."
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
         }
            return $this->db->query($the_query);
        }
        
}
