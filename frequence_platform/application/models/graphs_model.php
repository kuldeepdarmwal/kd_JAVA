<?php
//The functions below are all labeled with the corresponding graph numbers.  Takes some variables and returns a query response in the form of a codeigniter object.
class Graphs_model extends CI_Model 
{
	public function __construct()
	{
		$this->load->database();
	}

	public function get_campaigns($business_name)
	{
		$campaignQuery = 'SELECT Name FROM Campaigns WHERE Business = \''.$business_name.'\'';
		$campaignResponse = $this->db->query($campaignQuery);
		return $campaignResponse;
	}
	//101
	public function get_impressions_clicks_column_101($businessName, $startDate, $endDate, $campaignName)
	{
		$query = '	SELECT
		b.Date AS daterange,
		SUM(b.Impressions) AS TotalImpressions,
		SUM(b.Clicks) AS TotalClicks

		FROM
		AdGroups a LEFT JOIN CityRecords b ON (a.ID = b.AdGroupID)

		WHERE
		a.BusinessName =\''.$businessName.'\' AND
		b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
		a.CampaignName RLIKE \''.$campaignName.'\' 

		GROUP BY daterange
		ORDER BY daterange ASC
		';
		$response = $this->db->query($query);
		return $response;
	}
	public function get_impressions_clicks_total_101($businessName, $startDate, $endDate, $campaignName)
	{
		$query = '	SELECT
		SUM(b.Impressions) AS TotalImpressions,
		SUM(b.Clicks) AS TotalClicks

		FROM
		AdGroups a LEFT JOIN CityRecords b ON (a.ID = b.AdGroupID)

		WHERE
		a.BusinessName =\''.$businessName.'\' AND
		b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
		a.CampaignName RLIKE \''.$campaignName.'\' 
		';
		$response = $this->db->query($query);
		return $response;
	}
	//102
	public function graph_rows_query_102($businessName, $startDate, $endDate, $campaignName, $rankLimit)
	{
		$query = 'SELECT
		b.Date AS One,
		SUM(b.Impressions) AS Two,
		a.IsRetargeting AS Three

		FROM
		AdGroups a LEFT JOIN CityRecords b ON (a.ID = b.AdGroupID)

		WHERE
		a.BusinessName =\''.$businessName.'\' AND
		b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
		a.CampaignName RLIKE \''.$campaignName.'\'

		GROUP BY Three, One
		ORDER BY One ASC, Three ASC
		LIMIT '.$rankLimit.'
		';
		$response = $this->db->query($query);
		return $response;
	}
	//103
	public function graph_rows_query_103($businessName, $startDate, $endDate, $campaignName, $rankLimit)
	{
		$query = 'SELECT
		b.Date AS One,
		SUM(b.Clicks) AS Two,
		a.IsRetargeting AS Three

		FROM
		AdGroups a LEFT JOIN CityRecords b ON (a.ID = b.AdGroupID)

		WHERE
		a.BusinessName =\''.$businessName.'\' AND
		b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
		a.CampaignName RLIKE \''.$campaignName.'\'

		GROUP BY Three, One
		ORDER BY One ASC, Three ASC
		LIMIT '.$rankLimit.'
		';
		$response = $this->db->query($query);
		return $response;
	}
	//104 and 105
	public function graph_rows_query_104_105($businessName, $startDate, $endDate, $campaignName, $rankLimit)
	{
		$query = 'SELECT
		b.Base_Site AS One,
		SUM(b.Impressions) AS Two,
		b.Base_Site AS Three,
		SUM(b.Impressions) AS Four

		FROM
		AdGroups a LEFT JOIN SiteRecords b ON (a.ID = b.AdGroupID)

		WHERE
		a.BusinessName =\''.$businessName.'\' AND
		b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
		a.CampaignName RLIKE \''.$campaignName.'\'

		GROUP BY One
		ORDER BY Two DESC
		LIMIT '.$rankLimit.';
		';
		$response = $this->db->query($query);
		return $response;
	}
	public function partial_total_graph_query_104_105($businessName, $startDate, $endDate, $campaignName, $rankLimit)
	{
		$query = '
		SELECT SUM(sumTable.TotalImpressions) AS One
		FROM (
			SELECT
			b.Base_Site AS URL,
			SUM(b.Impressions) AS TotalImpressions

			FROM
			AdGroups a LEFT JOIN SiteRecords b ON (a.ID = b.AdGroupID)

			WHERE
			a.BusinessName =\''.$businessName.'\' AND
			b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
			a.CampaignName RLIKE \''.$campaignName.'\'

			GROUP BY URL
			ORDER BY TotalImpressions DESC
			LIMIT '.$rankLimit.'
		) sumTable
		';
		$response = $this->db->query($query);
		return $response;
	}
	public function total_graph_query_104_105($businessName, $startDate, $endDate, $campaignName)
	{
		$query = '
			SELECT
			SUM(b.Impressions) AS One

			FROM
			AdGroups a LEFT JOIN CityRecords b ON (a.ID = b.AdGroupID)

			WHERE
			a.BusinessName =\''.$businessName.'\' AND
			b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
			a.CampaignName RLIKE \''.$campaignName.'\'
		';
		$response = $this->db->query($query);
		return $response;
	}	
	
	//106
	public function graph_rows_query_106($businessName, $startDate, $endDate, $campaignName, $rankLimit)
	{
		$query = 'SELECT 
		b.City AS One, 
		SUM( b.Impressions ) AS Two, 
		a.IsRetargeting AS Three, 
		z.TotalImpressions AS Four

		FROM AdGroups a
		LEFT JOIN CityRecords b ON ( a.ID = b.AdGroupID )
		LEFT JOIN (

			SELECT 
			x.BusinessName AS bizname, 
			y.City AS cityName, 
			SUM( y.Impressions ) AS TotalImpressions

			FROM AdGroups x
			LEFT JOIN CityRecords y ON ( x.ID = y.AdGroupID )

			WHERE 
			x.BusinessName =\''.$businessName.'\' AND
			y.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
			x.CampaignName RLIKE \''.$campaignName.'\'
			
			GROUP BY cityName
			) AS z ON ( z.cityName = b.City )

		WHERE 
		a.BusinessName =\''.$businessName.'\' AND
		b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
		a.CampaignName RLIKE \''.$campaignName.'\'

		GROUP BY Three, One
		ORDER BY Four DESC, Three ASC

		LIMIT '.$rankLimit.'
		';
		$response = $this->db->query($query);
		return $response;
	}
	public function partial_total_graph_query_106($businessName, $startDate, $endDate, $campaignName, $rankLimit)
	{
		$query = 'SELECT 
		SUM(sumTable.TotalImpressions) One,
		sumTable.isRetarget Two
		FROM (
			SELECT 
			b.City AS cityName, 
			SUM( b.Impressions ) AS TotalImpressions, 
			a.IsRetargeting AS isRetarget, 
			z.TotalImpressions AS TotalCampaign

			FROM AdGroups a
			LEFT JOIN CityRecords b ON ( a.ID = b.AdGroupID )
			LEFT JOIN (

				SELECT 
				x.BusinessName AS bizname, 
				y.City AS cityName, 
				SUM( y.Impressions ) AS TotalImpressions

				FROM AdGroups x
				LEFT JOIN CityRecords y ON ( x.ID = y.AdGroupID )

				WHERE 
				x.BusinessName =\''.$businessName.'\' AND
				y.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
				x.CampaignName RLIKE \''.$campaignName.'\'
				
				GROUP BY cityName
				) AS z ON ( z.cityName = b.City )

			WHERE 
			a.BusinessName =\''.$businessName.'\' AND
			b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
			a.CampaignName RLIKE \''.$campaignName.'\'

			GROUP BY isRetarget, cityName
			ORDER BY TotalCampaign DESC, isRetarget ASC

			LIMIT '.$rankLimit.'
		) sumTable
		GROUP BY Two
		ORDER BY Two ASC
		';
		$response = $this->db->query($query);
		return $response;
	}
	public function total_graph_query_106($businessName, $startDate, $endDate, $campaignName)
	{
		$query = 'SELECT 
		SUM(sumTable.TotalImpressions) One,
		sumTable.isRetarget Two
		FROM (
			SELECT 
			b.City AS cityName, 
			SUM( b.Impressions ) AS TotalImpressions, 
			a.IsRetargeting AS isRetarget, 
			z.TotalImpressions AS TotalCampaign

			FROM AdGroups a
			LEFT JOIN CityRecords b ON ( a.ID = b.AdGroupID )
			LEFT JOIN (

				SELECT 
				x.BusinessName AS bizname, 
				y.City AS cityName, 
				SUM( y.Impressions ) AS TotalImpressions

				FROM AdGroups x
				LEFT JOIN CityRecords y ON ( x.ID = y.AdGroupID )

				WHERE 
				x.BusinessName =\''.$businessName.'\' AND
				y.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
				x.CampaignName RLIKE \''.$campaignName.'\'
				
				GROUP BY cityName
				) AS z ON ( z.cityName = b.City )

			WHERE 
			a.BusinessName =\''.$businessName.'\' AND
			b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
			a.CampaignName RLIKE \''.$campaignName.'\'

			GROUP BY isRetarget, cityName
			ORDER BY TotalCampaign DESC, isRetarget ASC
		) sumTable
		GROUP BY Two
		ORDER BY Two ASC
		';
		$response = $this->db->query($query);
		return $response;
	}
	//107 and 108
	public function graph_rows_query_107_108($businessName, $startDate, $endDate, $campaignName, $rankLimit)
	{
		$query = 'SELECT 
		b.City as One, 
		SUM(b.Impressions) as Two,
		b.City as Three, 
		SUM(b.Impressions) as Four

		FROM 
		AdGroups a LEFT JOIN CityRecords b ON (a.ID = b.AdGroupID)

		WHERE
		a.BusinessName =\''.$businessName.'\' AND
		b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
		a.CampaignName RLIKE \''.$campaignName.'\'

		GROUP BY One
		ORDER BY Two DESC
		LIMIT '.$rankLimit.'
		';
		$response = $this->db->query($query);
		return $response;
	}
        
        public function all_region_data($businessName, $startDate, $endDate, $campaignName){
                $query = 'SELECT 
		b.City as City, 
                b.Region as Region,
		SUM(b.Impressions) as Impressions

		FROM 
		AdGroups a LEFT JOIN CityRecords b ON (a.ID = b.AdGroupID)

		WHERE
		a.BusinessName =\''.$businessName.'\' AND
		b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
                b.Impressions != 0 AND b.City !=\'All other cities\' AND
		a.CampaignName RLIKE \''.$campaignName.'\'
                    

		GROUP BY City, Region
		ORDER BY Impressions DESC
		';
		$response = $this->db->query($query);
		return $response;
        }
        
	public function partial_total_graph_query_107_108($businessName, $startDate, $endDate, $campaignName, $rankLimit)
	{
		$query = 'SELECT SUM(sumTable.impr) AS One
		FROM (
			SELECT
			b.City as cityName, 
			SUM(b.Impressions) as impr

			FROM
			AdGroups a LEFT JOIN CityRecords b ON (a.ID = b.AdGroupID)

			WHERE
			a.BusinessName =\''.$businessName.'\' AND
			b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
			a.CampaignName RLIKE \''.$campaignName.'\'

			GROUP BY cityName
			ORDER BY impr DESC
			LIMIT '.$rankLimit.'
		) sumTable
			';
		$response = $this->db->query($query);
		return $response;
	}
	public function total_graph_query_107_108($businessName, $startDate, $endDate, $campaignName)
	{
		$query = 'SELECT SUM(sumTable.impr) AS One
		FROM (
			SELECT
			b.City as cityName, 
			SUM(b.Impressions) as impr

			FROM
			AdGroups a LEFT JOIN CityRecords b ON (a.ID = b.AdGroupID)

			WHERE
			a.BusinessName =\''.$businessName.'\' AND
			b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
			a.CampaignName RLIKE \''.$campaignName.'\'

			GROUP BY cityName
			ORDER BY impr DESC
		) sumTable
			';
		$response = $this->db->query($query);
		return $response;
	}
	
	//109
	public function graph_rows_query_109($businessName, $startDate, $endDate, $campaignName)
	{
		$query = 'SELECT
		SUM(t.under18) as two,
		SUM(t.18to24) as three,
		SUM(t.25to34) as four,
		SUM(t.35to44) as five,
		SUM(t.45to54) as six,
		SUM(t.55to64) as seven,
		SUM(t.65over) as eight,
		SUM(impressions) AS imp
		
		FROM (

			SELECT
			a.BusinessName AS bizname,
			SUM(b.Impressions) AS impressions,
			b.Base_Site AS URL,
			c.AdGroupID AS AdID,
			c.Age_Under18*SUM(b.Impressions) AS under18,
			c.Age_18_24*SUM(b.Impressions) AS 18to24,
			c.Age_25_34*SUM(b.Impressions) AS 25to34,
			c.Age_35_44*SUM(b.Impressions) AS 35to44,
			c.Age_45_54*SUM(b.Impressions) AS 45to54,
			c.Age_55_64*SUM(b.Impressions) AS 55to64,
			c.Age_65*SUM(b.Impressions) AS 65over

			FROM
			AdGroups a LEFT JOIN SiteRecords b ON (a.ID = b.AdGroupID) INNER JOIN DemographicRecords_TEST c ON (c.Domain = b.Base_Site)

			WHERE 
			a.BusinessName =\''.$businessName.'\' AND
			b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
			a.CampaignName RLIKE \''.$campaignName.'\' AND
			c.AdGroupID = b.AdGroupID

			GROUP BY URL

			UNION

			SELECT
			a.BusinessName AS bizname,
			SUM(b.Impressions) AS impressions,
			b.Base_Site AS URL,
			c.AdGroupID AS AdID,
			c.Age_Under18*SUM(b.Impressions) AS under18,
			c.Age_18_24*SUM(b.Impressions) AS 18to24,
			c.Age_25_34*SUM(b.Impressions) AS 25to34,
			c.Age_35_44*SUM(b.Impressions) AS 35to44,
			c.Age_45_54*SUM(b.Impressions) AS 45to54,
			c.Age_55_64*SUM(b.Impressions) AS 55to64,
			c.Age_65*SUM(b.Impressions) AS 65over

			FROM
			AdGroups a LEFT JOIN SiteRecords b ON (a.ID = b.AdGroupID) INNER JOIN DemographicRecords_TEST c ON (c.Domain = b.Base_Site)

			WHERE
			a.BusinessName =\''.$businessName.'\' AND
			b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
			a.CampaignName RLIKE \''.$campaignName.'\' AND
			c.AdGroupID = \'\' 

			GROUP BY URL
		) AS t
		';
		$response = $this->db->query($query);
		return $response;
	}
	//110
	public function graph_rows_query_110($businessName, $startDate, $endDate, $campaignName)
	{
		$query = 'SELECT
		SUM(under50) AS two,
		SUM(50to100) AS three,
		SUM(100to150) AS four,
		SUM(150up) AS five,
		SUM(impressions) AS imp

		FROM (

			SELECT
			a.BusinessName AS bizname,
			SUM(b.Impressions) AS Impressions,
			b.Base_Site AS URL,
			c.AdGroupID AS AdID,
			c.Income_0_50*SUM(b.Impressions) AS under50,
			c.Income_50_100*SUM(b.Impressions) AS 50to100,
			c.Income_100_150*SUM(b.Impressions) AS 100to150,
			c.Income_150*SUM(b.Impressions) AS 150up

			FROM
			AdGroups a LEFT JOIN SiteRecords b ON (a.ID = b.AdGroupID) INNER JOIN DemographicRecords_TEST c ON (c.Domain = b.Base_Site)

			WHERE 
			a.BusinessName =\''.$businessName.'\' AND
			b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
			a.CampaignName RLIKE \''.$campaignName.'\' AND
			c.AdGroupID = b.AdGroupID

			GROUP BY URL

			UNION

			SELECT
			a.BusinessName AS bizname,
			SUM(b.Impressions) AS Impressions,
			b.Base_Site AS URL,
			c.AdGroupID AS AdID,
			c.Income_0_50*SUM(b.Impressions) AS under50,
			c.Income_50_100*SUM(b.Impressions) AS 50to100,
			c.Income_100_150*SUM(b.Impressions) AS 100to150,
			c.Income_150*SUM(b.Impressions) AS 150up

			FROM
			AdGroups a LEFT JOIN SiteRecords b ON (a.ID = b.AdGroupID) INNER JOIN DemographicRecords_TEST c ON (c.Domain = b.Base_Site)

			WHERE
			a.BusinessName =\''.$businessName.'\' AND
			b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
			a.CampaignName RLIKE \''.$campaignName.'\' AND
			c.AdGroupID = \'\' 


			GROUP BY URL
		) AS t
		';
		$response = $this->db->query($query);
		return $response;
	}
	//111
	public function graph_rows_query_111($businessName, $startDate, $endDate, $campaignName)
	{
		$query = 'SELECT
		SUM(male) AS two,
		SUM(female) AS three,
		SUM(impressions) AS imp

		FROM (

			SELECT
			a.BusinessName AS bizname,
			SUM(b.Impressions) AS impressions,
			b.Base_Site AS URL,
			c.AdGroupID AS AdID,
			c.Gender_Male*SUM(b.Impressions) AS male,
			c.Gender_Female*SUM(b.Impressions) AS female

			FROM
			AdGroups a LEFT JOIN SiteRecords b ON (a.ID = b.AdGroupID) INNER JOIN DemographicRecords_TEST c ON (c.Domain = b.Base_Site)

			WHERE 
			a.BusinessName =\''.$businessName.'\' AND
			b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
			a.CampaignName RLIKE \''.$campaignName.'\' AND
			c.AdGroupID = b.AdGroupID

			GROUP BY URL

			UNION

			SELECT
			a.BusinessName AS bizname,
			SUM(b.Impressions) AS impressions,
			b.Base_Site AS URL,
			c.AdGroupID AS AdID,
			c.Gender_Male*SUM(b.Impressions) AS male,
			c.Gender_Female*SUM(b.Impressions) AS female

			FROM
			AdGroups a LEFT JOIN SiteRecords b ON (a.ID = b.AdGroupID) INNER JOIN DemographicRecords_TEST c ON (c.Domain = b.Base_Site)

			WHERE
			a.BusinessName =\''.$businessName.'\' AND
			b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
			a.CampaignName RLIKE \''.$campaignName.'\' AND
			c.AdGroupID = \'\'


			GROUP BY URL
		) AS t
			';
		$response = $this->db->query($query);
		return $response;
	}
	public function graph_rows_query_112($businessName, $startDate, $endDate, $campaignName)
	{
		$query = 'SELECT
        SUM(NoCollege) AS two,
        SUM(CollegeUnder) AS three,
        SUM(CollegeGrad) AS four,
		SUM(impressions) AS imp

        FROM (

            SELECT
            a.BusinessName AS bizname,
            SUM(b.Impressions) AS impressions,
            b.Base_Site AS URL,
            c.AdGroupID AS AdID,           
            c.College_No*SUM(b.Impressions) AS NoCollege,
            c.College_Under*SUM(b.Impressions) AS CollegeUnder,
            c.College_Grad*SUM(b.Impressions) AS CollegeGrad

            FROM
            AdGroups a LEFT JOIN SiteRecords b ON (a.ID = b.AdGroupID) INNER JOIN DemographicRecords_TEST c ON (c.Domain = b.Base_Site)

            WHERE
            a.BusinessName =\''.$businessName.'\' AND
            b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
            a.CampaignName RLIKE \''.$campaignName.'\' AND
            c.AdGroupID = b.AdGroupID

            GROUP BY URL

            UNION

            SELECT
            a.BusinessName AS bizname,
            SUM(b.Impressions) AS impressions,
            b.Base_Site AS URL,
            c.AdGroupID AS AdID,
            c.College_No*SUM(b.Impressions) AS NoCollege,
            c.College_Under*SUM(b.Impressions) AS CollegeUnder,
            c.College_Grad*SUM(b.Impressions) AS CollegeGrad


            FROM
            AdGroups a LEFT JOIN SiteRecords b ON (a.ID = b.AdGroupID) INNER JOIN DemographicRecords_TEST c ON (c.Domain = b.Base_Site)

            WHERE
            a.BusinessName =\''.$businessName.'\' AND
            b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
            a.CampaignName RLIKE \''.$campaignName.'\' AND
            c.AdGroupID = \'\'

            GROUP BY URL
        ) AS t
		';
		$response = $this->db->query($query);
		return $response;
	}
	
	public function partial_total_demos_query($businessName, $startDate, $endDate, $campaignName)
	{
	$query = 'SELECT
			SUM(b.Impressions) AS ptotals

			FROM
			AdGroups a LEFT JOIN SiteRecords b ON (a.ID = b.AdGroupID)

			WHERE
			a.BusinessName =\''.$businessName.'\' AND
			b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
			a.CampaignName RLIKE \''.$campaignName.'\'
	';
	
	
	$response = $this->db->query($query);
	return $response;
	}
	
	public function total_demos_query($businessName, $startDate, $endDate, $campaignName)
	{
	$query = 'SELECT
			SUM(b.Impressions) AS totals

			FROM
			AdGroups a LEFT JOIN CityRecords b ON (a.ID = b.AdGroupID)

			WHERE
			a.BusinessName =\''.$businessName.'\' AND
			b.Date BETWEEN \''.$startDate.'\' AND \''.$endDate.'\' AND
			a.CampaignName RLIKE \''.$campaignName.'\'
	';
	
	$response = $this->db->query($query);
	return $response;
	}
}
?>
