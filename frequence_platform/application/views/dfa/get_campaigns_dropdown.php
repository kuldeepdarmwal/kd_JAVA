<?php
$campaign_dropdown_string = '<select id="campaign_dropdown" onChange="campaign_select_script();"><option value="nothing">Please Select</option><option value="new">*New*</option>';
 foreach($result as $campaign) {
                $campaign_dropdown_string = $campaign_dropdown_string.'<option value="'.$campaign->id.'">'.$campaign->name.'</option>';
            }
$campaign_dropdown_string = $campaign_dropdown_string.'</select>';


echo $campaign_dropdown_string;

?>