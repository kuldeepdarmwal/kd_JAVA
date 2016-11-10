<?php
$advertisers_dropdown_string = '<select id="advertiser_dropdown" onChange="advertiser_select_script();"><option value="nothing">Please Select</option><option value="new">*New*</option>';
 foreach($dfa_advertiser_records as $advertiser) {
                $advertisers_dropdown_string = $advertisers_dropdown_string.'<option value="'.$advertiser->id.'">'.$advertiser->name.'</option>';
            }
$advertisers_dropdown_string = $advertisers_dropdown_string.'</select>';


echo $advertisers_dropdown_string;

?>
