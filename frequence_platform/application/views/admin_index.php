<?php
	if($username):?>
	   <div id="user-detail">Hi, <strong><?php echo $username; ?></strong>! (<?php echo anchor('/auth/logout/', 'Logout'); ?>)</div>
	<?php
	endif;
?>
<div class="wrapper">	
	<div class="page_left">
            <?php

                //  Removed in favor of jQgrid based campaign listing
                // display a table based listing of all available campaigns
                // $this->table->set_heading('Business Name', 'Campaign Name', 'Creation date','Actions');
                // echo $this->table->generate($campaign_list);
                // echo $campaign_pagination_links ;
                // 

	        // form render callbacks 
		echo validation_errors('<div class="error">', '</div>') ;
	
		echo form_open_multipart('admin',$form_attributes) ;
                
                //echo form_fieldset('Coupon Details :');
                echo '<br/><strong class="form-sep">Coupon Details </strong><br/><br/>' ;
                echo form_label('Business Name');
		echo form_dropdown('business-name',$ff_business_name).'<br/>' ;
		
		echo form_label('Campaign Name');
		echo form_input($ff_campaign_name).'<br/>' ;
		
		echo form_label('Coupon Title');
		echo form_input($ff_coupon_title).'<br/>' ;
		
		echo form_label('Offer Discount');
		echo form_input($ff_coupon_discount).'<br/>' ;
		
		echo form_label('Valid Days');
		echo form_input($ff_coupon_validity).'<br/>' ;            
                		
		echo form_label('Coupon Description');
		echo form_textarea($ff_coupon_desc).'<br/>' ;
		
		echo form_label('Coupon Type');
		echo form_dropdown('coupon-types',$ff_coupon_types).'<br/>' ;
		?>
		<div class="field-group" id="cnt-coupon-actions"><?php
		echo form_label('Claim methods allowed').'<br/>';
		echo form_checkbox($ff_coupon_actions[0]).form_label($lbl_coupon_actions[0]).'<br/>' ;
		echo form_checkbox($ff_coupon_actions[1]).form_label($lbl_coupon_actions[1]).'<br/>' ;
		echo form_checkbox($ff_coupon_actions[2]).form_label($lbl_coupon_actions[2]).'<br/>' ;
        echo form_checkbox($ff_coupon_actions[3]).form_label($lbl_coupon_actions[3]).'<br/>' ;
		?></div><?php
		
		echo form_label('Number of coupons');
		echo form_input($ff_coupon_max_num).'<br/>' ;

                echo form_label('Business Landing Page');
                echo form_input($ff_landing_page).'<br/>' ;

		echo form_label('Upload an image');
		echo '<input type="file" name="userfile" size="20" /><br/>';
                //echo form_fieldset_close() ;
                
                //echo form_fieldset('Coupon Mail settings :');
                
                echo '<strong class="form-sep">Coupon mail settings </strong><br/><br/>' ;
                echo form_label('From address') ;
                echo form_input($ff_coupon_mail_from).'<br/>' ;
                
                
                echo form_label('Default Message ') ;
                echo form_textarea($ff_coupon_mail_msg).'<br/>' ;

               // echo form_fieldset_close() ;

		echo form_submit('create-campaign','ADD') ;
		
		
		echo form_close() ;
	?>
        
        </div>
        <div class="page_right">
            <!-- provide ground for jQgrid based Campaign listing -->
            <table id="campaign-list"><tr><td/></tr></table>
            <div id="pager"></div>
        </div>
        <div class="table_holder" align="right"></div>
	<div class="shadow"><img src="<?php echo base_url('assets/img/shadow.png');  ?>" width="943" height="87" /></div>
</div>
<?php
// extra code to check if it's a new campaign and provide a option for CSV download
 if(isset($new_campaign)):
                    ?>
            <script>
                function open_download_csv(){
                  <?php echo $script_for_download; ?>
                }
                open_download_csv();
            </script>
            
            <?php
 endif;
?>

