<?php
class ring_model extends CI_Model
{
	public function __construct()
	{
		$this->load->database();
	}
	
	public function get_index_name()
	{

	}
        public function epcm($d2,$d1,$name){
             $rtg=0;
             if($name!=""){
             $query = $this->db->query("Select SUM(cost) as c from CityRecords where AdGroupID IN ($name) AND DATE BETWEEN '$d1' AND '$d2'");
             $row = $query->row();
             $rtg =$row->c;}
             return(round($rtg,1));
        }
        public function actualimpression($d2,$d1,$name){
             $rtg=0;
             if($name!=""){
             $query = $this->db->query("Select SUM(Impressions) as c from CityRecords where AdGroupID IN ($name) AND DATE BETWEEN '$d1' AND '$d2'");
             $row = $query->row();
             $rtg =($row->c)/1000;}
             return(round($rtg,1));
        }
         public function enddate_impression($d2,$name){
             $rtg=0;
             if($name!=""){
             $query = $this->db->query("Select SUM(Impressions) as c from CityRecords where AdGroupID IN ($name) AND DATE = '$d2'");
             $row = $query->row();
             $rtg =($row->c)/1000;
             }
             return(round($rtg,1));
        }
        public function details($str)
        {
                $param = explode("|",$str);
                $d1 = $param[0] ;
                $d2 = $param[1] ;
                $cc = $param[2];
                $id = explode("::",$cc);
                $c = $id[1];
		//ROUND((sum(cr.Impressions)/sum(cr.Clicks)*100),1) as ctr
        	$query = "select ad.ID as id,ROUND((sum(cr.Impressions)/1000),1) as ki, (sum(cr.Clicks)) as cl,ROUND((sum(cr.Clicks))*100 / sum(cr.Impressions),4) as ctr, ROUND((sum(cost)),1) as cost,ROUND((sum(cost)/(sum(cr.Impressions)/1000)),2) as cpm,ROUND(sum(cost)/sum(cr.Clicks),1) as cpc from AdGroups ad join CityRecords as cr on ad.ID=cr.AdGroupId where ad.CampaignName='$c' AND cr.Date BETWEEN '$d1' AND '$d2' group by cr.AdGroupId";
               return($this->db->query($query));
        }

        public function highchart($str)
        {
                $param = explode("|",$str);
                $d1 = $param[0] ;
                $d2 = $param[1] ;
                $cc = $param[2];
                $id = explode("::",$cc);
                $c = $id[1];
                $output="";
                $query = "
                        SELECT
                        TargetImpressions as ti
                        FROM Campaigns where Name='$c'";
                $query = $this->db->query($query);

                $row = $query->row();
				
                $target= round(($row->ti)/28,1)."|".$cc;

                $query = $this->db->query("select ad.ID as id from AdGroups ad join CityRecords as cr on ad.ID=cr.AdGroupId where ad.CampaignName='$c' AND cr.Date BETWEEN '$d1' AND '$d2' group by cr.AdGroupId");
   
                foreach ($query->result() as $val)
                {
                        $output.= "||".$val->id;
                        $query1 = $this->db->query("select sum(Impressions) as Impressions,Date,AdGroupId from CityRecords where AdGroupId= '$val->id' and Date BETWEEN '$d1' AND '$d2' group BY Date");
                     foreach ($query1->result_array() as $row)
                        {
                                $imp = $row['Impressions'];
                                $imp = round($imp/1000,1);
                                $output.="|".$imp.",".$row['Date'];
                        }
                }
                $output=trim($output,"||");
                $output = $d1."|".$d2."$$".$target."$$".$output;
               return $output;
//			print $output;
//			exit;
        }

        public function top5($str)
        {
                $param = explode("|",$str);
                $d1 = $param[0] ;
                $d2 = $param[1] ;
                $cc = $param[2];
                $id = explode("::",$cc);
                $id_string="";
                $output="";
                $c = $id[1];
                $query = "select ad.ID as id  from AdGroups ad join CityRecords as cr on ad.ID=cr.AdGroupID where ad.CampaignName='$c' AND cr.Date BETWEEN '$d1' AND '$d2' group by cr.AdGroupID";
                $query = $this->db->query($query);

                foreach ($query->result() as $val)
                {
                        $id_string.= "'".$val->id."',";
                }
                $id_string = trim($id_string,",");
		if($id_string == '') {
		  $output = '';
		} else {
                $query = $this->db->query("select Base_Site as name,SUM(Impressions) as imp from SiteRecords where AdGroupID IN ($id_string) AND Date BETWEEN '$d1' AND '$d2' group by Base_Site order by imp DESC LIMIT 5");
                foreach ($query->result() as $val)
                {
                	$output.= "||".$val->name."|".$val->imp;
                }
                $output=trim($output,"||");
		}
                return $output;
        }
       public function top5cities($str)
        {
                $param = explode("|",$str);
                $d1 = $param[0] ;
                $d2 = $param[1] ;
                $c = $param[2];
                $cc = $param[2];
                $id = explode("::",$cc);
                $c = $id[1];
                $output="";
                $id_string="";
                $query = "select ad.ID as id  from AdGroups ad join CityRecords as cr on ad.ID=cr.AdGroupID where ad.CampaignName='$c' AND cr.Date BETWEEN '$d1' AND '$d2' group by cr.AdGroupID";
                $query = $this->db->query($query);

                foreach ($query->result() as $val)
                {
                        $id_string.= "'".$val->id."',";
                }
                $id_string = trim($id_string,",");
		if($id_string == '') {
		  $output = '';
		} else {
                $query = $this->db->query("select City as name,SUM(Impressions) as imp from CityRecords where AdGroupId IN ($id_string) AND Date BETWEEN '$d1' AND '$d2' group by City order by imp DESC LIMIT 5");
                foreach ($query->result() as $val)
                {
			if($val->name == '') {
				$val->name = "No City Name";
			}
                	$output.= "||".$val->name."|".$val->imp;
                }
                $output=trim($output,"||");
		}
                return $output;
        }


        public function hchk()
        {
		date_default_timezone_set('UTC');

		$monthAndTwoDaysAgoDate = date("Y-m-d", strtotime('-1 month -2 days'));
                //$query = 'SELECT DISTINCT Name,Business,TargetImpressions FROM Campaigns order by Name desc';
		$query ="SELECT DISTINCT a.Name, a.Business, a.TargetImpressions 
			FROM Campaigns a LEFT JOIN AdGroups b ON (a.Name = b.CampaignName) LEFT JOIN CityRecords c ON (b.ID = c.AdGroupID) 
			WHERE c.Date > '$monthAndTwoDaysAgoDate' 
			ORDER BY a.Business ASC";

                $query = $this->db->query($query);

                foreach ($query->result_array() as $row)
                {
                         $name= $row['Name'];
                         $query_rtg = $this->db->query("Select count(id) as c from AdGroups where CampaignName = '$name' AND IsRetargeting = 1");
                         $val = $query_rtg->row();
                         $rtg =$val->c;
                         if($rtg==0)
                         {
                         	$rtg="False";$threshhold=0.87;
                         }
                         else
                         {
                         	$rtg="True";
                         	$threshhold=1.59;
                         }
                          $id_string="";
                         $query = "select ad.ID as id  from AdGroups ad join CityRecords as cr on ad.ID=cr.AdGroupId where ad.CampaignName='$name'  group by cr.AdGroupId";
                        $query = $this->db->query($query);
                        foreach ($query->result() as $val)
                        {
                        $id_string.= "'".$val->id."',";
                        }
                        $id_string = trim($id_string,",");
 
                         $d1="";$d2="";
                         $date = date("Y-m-d",strtotime('-2 day'));
                         $oneWeekAgo = date("Y-m-d",strtotime ( '-1 week' , strtotime ( $date ) )) ; 
                         $oneMonthAgo = date("Y-m-d",strtotime ( '-1 month' , strtotime ( $date ) )) ;
                         $oneforthnightAgo = date("Y-m-d",strtotime ( '-14 day' , strtotime ( $date ) )) ; 
                         $actual_monthly = $this->actualimpression($date,$oneMonthAgo,$id_string);
                         $actual_forthnightly = $this->actualimpression($date,$oneforthnightAgo,$id_string);
                         $actual_weekly = $this->actualimpression($date,$oneWeekAgo,$id_string);
                         $enddateimprssion = $this->enddate_impression($date,$id_string);
                         $epcm_monthly =round($this->epcm($date,$oneMonthAgo,$id_string) / ($actual_monthly > 0 ? $actual_monthly : 1),2);
                         $epcm_forthnightly =round($this->epcm($date,$oneforthnightAgo,$id_string) / ($actual_forthnightly > 0 ? $actual_forthnightly : 1),2);
                         $epcm_weekly =round($this->epcm($date,$oneWeekAgo,$id_string) / ($actual_weekly > 0 ? $actual_weekly : 1),2);
                         $epcm_daily =round($this->epcm($date,$date,$id_string) / ($enddateimprssion > 0 ? $enddateimprssion : 1),2);
                         if($epcm_monthly>$threshhold){
                         $epcm_m_class="red";}
                         else{
                          $epcm_m_class="";}
                          if($epcm_forthnightly>$threshhold){
                         $epcm_f_class="red";}
                         else{
                          $epcm_f_class="";}
                          if($epcm_weekly>$threshhold){
                         $epcm_w_class="red";}
                          else{
                          $epcm_w_class="";}
                          if($epcm_daily>$threshhold){
                         $epcm_d_class="red";}
                          else{
                          $epcm_d_class="";}
                         
                        $data['rows'][] = array(
                                'cell' => array(
                                        $row['Name'],
                                        $row['Business'],
                                        $rtg,
                                        $enddateimprssion
                                ),
                                'monthly' => array(
                                        round($row['TargetImpressions'],1),
                                        $actual_monthly,
                                        $epcm_monthly,
                                        $epcm_m_class
                                 ),
                                  'forthnightly' => array(
                                        round($row['TargetImpressions']/2,1),
                                        $actual_forthnightly,
                                        $epcm_forthnightly,
                                        $epcm_f_class
                                 ),
                                  'weekly' => array(
                                        round($row['TargetImpressions']/4,1),
                                        $actual_weekly,
                                        $epcm_weekly,
                                        $epcm_w_class
                                 ),
                                  'daily' => array(
                                        round($row['TargetImpressions']/28,1),
                                        $enddateimprssion,
                                        $epcm_daily,
                                        $epcm_d_class
                                 )


                        );
                }


                return ($data);

        }
}
?>
