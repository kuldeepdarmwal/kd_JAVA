<?php

/*
 * Coupon List
 * Dynamically populated list of coupons that come under a campaign after it's creation
 * pre-alloting rows in table don't seems to be good idea so these numbers may vary 
 * each time 
 */

?>
<div class="wrapper">
    <ul id="coupon-list">
        <?php
            foreach($coupons as $coupon):
        ?> 
        <div class="coupon-item">
            <span><?php  echo $coupon; ?></span>
        </div>
        <?php
            endforeach;
        ?>
    </ul>
   
</div>

