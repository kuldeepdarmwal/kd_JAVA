<?php
$vl_campaign_dropdown_string = '<select id="vl_campaign_dropdown" onChange="vl_campaign_select_script();" ><option value="nothing">Please Select</option>';
 foreach($vl_campaigns as $campaign) {
                $vl_campaign_dropdown_string = $vl_campaign_dropdown_string.'<option value="'.$campaign['c_id'].'">'.$campaign['full_campaign'].'</option>';
            }
$vl_campaign_dropdown_string = $vl_campaign_dropdown_string.'</select>';


echo $vl_campaign_dropdown_string;

?>