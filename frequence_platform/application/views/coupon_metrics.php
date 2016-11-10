<div class="metric-page">
<?php

/* Coupon Metrics 
 * template to display metric for a selected coupon/campaign id
 */

if($metric_status){
?>
<strong class="table_title"><?php  echo $campaign_name ;  ?> summary</strong>
<?php
$tmpl = array ( 'table_open'  => '<table width="100%" border="0" cellpadding="5" cellspacing="1" class="metric stat" id="admin_table">' );
$this->table->set_template($tmpl); 
$this->table->set_heading('Displayed Coupons', 'Codes Created', 'Claimed','Facebook Shares','Email Shares','Self Emails');
echo $this->table->generate($stat_set);
$this->table->clear();
?>
<strong class="table_title">coupons claimed</strong>
<div class="scroll-cnt">
    <div class="scroll-area">
<?php
$tmpl = array ( 'table_open'  => '<table width="100%" border="0" cellpadding="5" cellspacing="1" class="metric stat-claims scrollable" id="admin_table">' );
$this->table->set_template($tmpl); 
$this->table->set_heading('Coupons Claimed', 'Timestamp', 'Coupon Code','Redeem Action');
if(count($claims) == 0){
    $this->table->add_row('No Coupon have been claimed yet !');
    echo $this->table->generate();
}else{
    echo $this->table->generate($claims);
}
$this->table->clear();
?></div></div>
<strong class="table_title">shared contacts</strong>

<div class="scroll-cnt">
    <div class="scroll-area">
<?php
$tmpl = array ( 'table_open'  => '<table width="100%" border="0" cellpadding="5" cellspacing="1" class="metric stat-shares scrollable" id="admin_table">' );
$this->table->set_template($tmpl);
$this->table->set_heading('Shared Email','Referred id/email','Time Stamp');
if(count($shares) == 0){
    $this->table->add_row('Not being shared yet !');
    echo $this->table->generate();
}else{
    echo $this->table->generate($shares);
}
$this->table->clear();
?>
</div>
</div>
</div>
<?php
}else{
    echo "No Sufficient metric data available" ;
}


?>


