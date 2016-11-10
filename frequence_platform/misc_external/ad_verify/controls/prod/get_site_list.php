
echo "SELECT d.url FROM page_data d JOIN page_categories c ON (d.id = c.page_id) WHERE c.category_id IN (SELECT tag_id FROM campaign_categories WHERE campaign_id = ".$_POST['id'].") AND d.base_url IN (SELECT b.Base_site AS Site                                                                       
FROM                                                                                                                  
AdGroups a LEFT JOIN SiteRecords b ON (a.ID = b.AdGroupID)                                                            
                                                                                                                      
WHERE                                                                                                                 
b.Base_site != 'All other sites' AND                                                                                                                                                   
a.campaign_id = ".$_POST['id']." AND                                                                    
b.Date > '2013-01-01'                                                                                                 
GROUP BY Site ORDER BY SUM(b.Impressions) DESC) LIMIT 20";