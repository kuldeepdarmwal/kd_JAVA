<!doctype html>
<html>
	<body>
	<div class="partner-container"> 
		<div class="row">
			<div class="col s5 partner-form">
				<h4><?php echo $create_edit_lable; ?> Partner</h4>
				
				<form id="partner_form" method="POST" action="" enctype="multipart/form-data">
					<div id="partner_info_section">
						<div class="card-content">   
                                                        <div class="row">
								<div class="input-field col s12" style="height:61px;">
									<input type="text" id="partner_name" name="partner_name" class="grey-text text-darken-1" length="255" value="<?php echo isset($form_data['partner_name']) ? $form_data['partner_name'] : ''; ?>">
									<label for="partner_name" data-error="Please fill in the Partner Name.">Partner Name</label>
								</div>
							</div>							
                                                   
							<div class="row">
								<div class="input-field col s8">
									<input type="text" id="partner_domain" name="partner_domain" class="grey-text text-darken-1" length="255" value="<?php echo isset($form_data['cname']) ? $form_data['cname'] : ''; ?>" <?php echo isset($partner_id) ? 'readonly' : ''; ?>>
									<label for="partner_domain" data-error="Please fill in the Partner Domain.">Domain</label>
								</div>
                                                                <div class="input-field col s4 display_domain">
									<?php echo '.'.$domain; ?>									
								</div>
							</div>
                                                     
							<div class="row">
								<div class="input-field col s12">
									<input type="text" id="partner_homepage" name="partner_homepage" class="grey-text text-darken-1" length="255" value="<?php echo isset($form_data['home_url']) ? $form_data['home_url'] : ''; ?>">
									<label for="partner_homepage" data-error="Please fill in the Partner Homepage.">Partner Homepage</label>
								</div>
							</div>
                                                    
                                                        <div class="row">
								<div class="input-field col s12 row_parent_partner">									
                                                                        <input class="" name='parent_partner' id="parent_partner_select" type="hidden" value="<?php echo isset($form_data['parent_partner']) ? $form_data['parent_partner'] : ''; ?>" >
									<label id="parent_partner_select_label" data-error="Please select Parent Partner" for="s2id_parent_partner_select">Parent Partner</label>
								</div>
							</div>
                                                    
                                                    <?php                                                        
                                                            if ($is_demo_partner_accessible == 1)
                                                            {
                                                                    $demo_disabled = '';
                                                                    $demo_checked = '';                                                                    
                                                                    if ($is_real_partner_accessible == 0 || isset($partner_id))
                                                                    {
                                                                            $demo_disabled = 'onclick="return false"';                                                                            
                                                                    }
                                                                    $demo_email_class = 'demo_email';
                                                                    $demo_email_readonly = '';
                                                                    if (($is_real_partner_accessible == 0 && empty($partner_id)) || (isset($form_data['is_demo_partner']) && $form_data['is_demo_partner'] == 1))
                                                                    {
                                                                            $demo_checked = 'checked';                                                                         
									    $demo_email_class = '';                                                                            
                                                                    }

                                                                    if (!empty($partner_id))
                                                                    {
                                                                            $demo_email_readonly = 'readonly';
                                                                    }
                                                    ?>
                                                        <div class="row">
								<div class="input-field col s12 demo_partner">
									<input type="checkbox" id="demo_partner" name="demo_partner" class="filled-in" value="1" <?php echo $demo_checked; ?> <?php echo $demo_disabled; ?> />
									<label for="demo_partner">Is Demo Partner</label>
								</div>
							</div>
                                                    
                                                        <div class="row <?php echo $demo_email_class; ?>">
								<div class="input-field col s12">
									<input type="text" id="partner_user_email" name="partner_user_email" class="grey-text text-darken-1" length="255" value="<?php if(isset($form_data['demo_sales_email'])) { echo $form_data['demo_sales_email']; } elseif(isset($form_data['partner_user_email'])) { echo $form_data['partner_user_email']; }else { echo $form_data['default_partner_user_email']; } ?>" <?php echo $demo_email_readonly; ?>
									<label for="partner_user_email" data-error="Please fill in the Sales Email.">Partner User Email</label>
								</div>
							</div>
                                                        <div class="row <?php echo $demo_email_class; ?>">
								<div class="input-field col s12">
									<input type="text" id="advertiser_email" name="advertiser_email" class="grey-text text-darken-1" length="255" value="<?php if(isset($form_data['demo_adv_email'])) { echo $form_data['demo_adv_email']; }  elseif(isset($form_data['advertiser_email'])) { echo $form_data['advertiser_email']; }else { echo $form_data['default_advertiser_email']; } ?>" <?php echo $demo_email_readonly; ?>
									<label for="advertiser_email" data-error="Please fill in the Advertiser Email.">Advertiser Email</label>
								</div>
							</div>
                                                    <?php
                                                            }
                                                    ?>
                                                    
								<div class="row" style="padding-top: 30px;">
									<div class="col s12 input-field">
										<input type="hidden" style="width:100%;" id="partner_palette_select" name="partner_palette_id" value="">
										<label id="partner_palette_select_label" for="s2id_partner_palette_select">Partner Palette</label>
									</div>
								</div>

                                                        <div class="row feature_header">
                                                            <div class="col s3">Features</div>
                                                            <div class="col s9">Partner User Access</div>
                                                        </div>                                        
                                                                        
                                                                <?php                                                                        
                                                                        foreach ($features_listing as $key => $features)
                                                                        {
                                                                                $none_checked = '';
                                                                                $all_checked = '';
                                                                                $super_checked = '';
                                                                                if ($features['checked'] == 1)
                                                                                {
                                                                                        $none_checked = 'checked';
                                                                                }
                                                                                else if ($features['checked'] == 2)
                                                                                {
                                                                                        $all_checked = 'checked';
                                                                                }
                                                                                else if ($features['checked'] == 3)
                                                                                {
                                                                                        $super_checked = 'checked';
                                                                                }                                                                            

                                                                                $feature_description = '';
                                                                                if(!empty($features['feature_description']))
                                                                                {
                                                                                        $feature_description = 'description';
                                                                                }
                                                                ?>
                                                                            <div class="row highlight features_listing">
                                                                                    <div class="col s3 feature">
                                                                                            <label for="features_<?php echo $features['id']; ?>" class="tooltip_<?php echo $feature_description;?> tool_poppers" data-toggle="popover" data-trigger="hover" data-placement="right" data-original-title="Description" data-content="<?php echo htmlspecialchars($features['feature_description']); ?>">
                                                                                                    <?php echo $features['name']; ?>
                                                                                            </label>
                                                                                    </div>
                                                                                    <div class="col s3">
                                                                                            <input type="radio" name="features_<?php echo $features['id']; ?>" id="features_none_<?php echo $features['id']; ?>" class="filled-in" value="1" <?php echo $none_checked; ?> />
                                                                                            <label for="features_none_<?php echo $features['id']; ?>">None</label>
                                                                                    </div>
                                                                                    <div class="col s3">
                                                                                            <input type="radio" name="features_<?php echo $features['id']; ?>" id="features_all_<?php echo $features['id']; ?>" class="filled-in" value="2" <?php echo $all_checked; ?> />
                                                                                            <label for="features_all_<?php echo $features['id']; ?>">All</label>
                                                                                    </div>
                                                                                    <div class="col s3">
                                                                                            <input type="radio" name="features_<?php echo $features['id']; ?>" id="features_super_<?php echo $features['id']; ?>" class="filled-in" value="3" <?php echo $super_checked; ?> />
                                                                                            <label for="features_super_<?php echo $features['id']; ?>">Supers Only</label>
                                                                                    </div>
                                                                            </div>
                                                                <?php
                                                                        }
                                                                ?>                                                    
                                                    
                                                        <div class="row">
								<div class="input-field col s12">
									<div class="btn fileinput-button">
									<i class="icon-file icon-white"></i>
									<span>Login Image</span>
									<input id="file_upload_login" type="file" name="file_login" value="<?php echo isset($form_data['file_login']) ? $form_data['file_login'] : ''; ?>" />
                                                                        </div>
								</div>
							</div>
                                                    
                                                        <div class="row">
								<div class="input-field col s12">
									<div class="btn fileinput-button">
									<i class="icon-file icon-white"></i>
									<span>Header Image</span>
									<input id="file_upload_header" type="file" name="file_header" value="<?php echo isset($form_data['file_header']) ? $form_data['file_header'] : ''; ?>" />
                                                                        </div>
								</div>
							</div>
                                                    
                                                        <div class="row">
								<div class="input-field col s12">
									<div class="btn fileinput-button">
									<i class="icon-file icon-white"></i>
									<span>Favicon Image</span>
									<input id="file_upload_favicon" type="file" name="file_favicon" value="<?php echo isset($form_data['file_favicon']) ? $form_data['file_favicon'] : ''; ?>" />
                                                                        </div>
								</div>
							</div>                                            
                                                     
							<input type="hidden" name="partner_id" id="partner_id" value="<?php echo isset($partner_id) ? $partner_id : ''; ?>" />
                                                        <input type="hidden" name="source" value="<?php echo $source; ?>" />                                                        
							<button type="button" id="partner_submit_button" class="btn btn-save"><?php echo $create_edit_button; ?></button>
						</div>
					</div>
				</form>
			</div>
                    
			<div class="col s6 offset-s1" id="preview-container-section">
				<div class="preview-container">
                                        <div class="preview_title_container row">
                                                <div class="preview_buttons col s10" id="favicon_container">
                                                        <img src="<?php echo !empty($form_data['favicon_path']) ? $form_data['favicon_path'] : $form_data['default_favicon_path']; ?>" id="favicon" alt=""/>
                                                        <div id="title_container">
                                                          <?php echo !empty($form_data['preview_title']) ? $form_data['preview_title'] : $form_data['default_preview_title']; ?>
                                                        </div>
                                                </div>
                                                <div class="preview_title_end col s2"></div>
                                        </div>
                                        <div class="remove_space row">                                        
                                                <div class="preview_url col s11" id="url_container">
                                                        <span id="domain_container"><?php echo !empty($form_data['preview_url']) ? $form_data['preview_url'] : $form_data['default_preview_url']; ?></span>
                                                        <span><?php echo '.'.$domain; ?></span>
                                                </div>
                                                <div class="preview_url_end col s1"></div>
                                        </div>
                                        <div class="remove_space preview_head_container row">
                                                <div class="preview_top col s12">
                                                        <div id="header_container">
                                                                <img src="<?php echo !empty($form_data['header_img']) ? $form_data['header_img'] : $form_data['default_header_img']; ?>" id="header" alt=""/>
                                                        </div>
                                                </div>
                                        </div>
                                        <div class="remove_space preview_body_container row">
                                                <div class="preview_body col s12">
                                                        <div class="center_section">
                                                                <div id="logo_container">
                                                                        <img src="<?php echo !empty($form_data['partner_report_logo_filepath']) ? $form_data['partner_report_logo_filepath'] : $form_data['default_partner_report_logo_filepath']; ?>" id="logo" alt=""/>
                                                                </div>
                                                                <div id="form_container">
                                                                        <form action="" method="post">
                                                                                <div class="input_group username input-field">
                                                                                        <label for="login">Username or Email</label>
                                                                                        <input name="login" id="login" type="text" placeholder="Username or Email" required value="" class="grey-text text-darken-1" readonly />
                                                                                </div>
                                                                                <div class="input_group password input-field">
                                                                                        <label for="password">Password</label>
                                                                                        <input name="password" id="password" type="password" placeholder="Password" class="grey-text text-darken-1" required readonly />
                                                                                </div>
                                                                                <input name="submit" id="submit" type="button" value="Sign In" />
                                                                        </form>
                                                                </div>
                                                        </div>
                                                </div>
                                        </div>
                                        <div class="remove_space row">
                                                <div class="preview_bottom col s12"></div>
                                        </div>
                              </div>
			</div>
                    
		</div>
	</div>
	</body>
</html>