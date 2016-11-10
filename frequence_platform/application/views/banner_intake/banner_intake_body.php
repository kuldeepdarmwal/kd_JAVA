<div class="container">
	<form class="form-horizontal" id="banner_intake_form"   method="post" accept-charset="utf-8" enctype="multipart/form-data">
		<input type="hidden" name="requester_email" value="<?php echo $email;?>">
		<input type="hidden" name="requester_id" value="<?php echo $user_id;?>">
		<input type="hidden" name="io_redirect_id" value="<?php echo $mpq_id;?>">
		<input type="hidden" id="source_table" name="source_table" value="<?php echo set_value('source_table'); ?>"/>
		<input type="hidden" id="num_variations" name="num_variations" value="2"/>
		<div class="row">
			<div class="col s12">
				<div class="card">
					<div class="card-content grey-text text-darken-1">
						<div class="section">
							<h5>Creative Request</h5>
						</div>
						<div class="divider"></div>
						<div class="row">
							<div class="input-field col s12 m4 l4">
								<select name="product" id="product" data-oldval="Display">
									<option value="Display">Display</option>
									<option value="Preroll">Preroll</option>
								</select>
								<label for="product">Product</label>
							</div>
							<div class="input-field col s12 m4 l4 offset-m2 offset-l2">
								<input id="creative_name" name="creative_name" type="text" length="255" value="<?php echo set_value('creative_name'); ?>" placeholder="" >
								<label for="creative_name">Creative Name</label>
							</div>
						</div>
						<div class="row">
							<div id="requesttypeparent" class="input-field col s12 m4 l4">
								<select name="request_type" id="requesttype" data-oldval="Custom Banner Design">
									<option value="Custom Banner Design">Custom Banner Design</option>
									<option value="Ad Tags">Ad Tags (Beta)</option>
									<option value="Upload Own">Upload Your Own</option>
								</select>
								<label for="requesttype">Type</label>
							</div>
							<div id="advertiserparent" class="input-field col s12 m4 l4 offset-m2 offset-l2">
								<input id="advertiser" name="advertiser_id" type="hidden" />
								<label class="banner_intake_select_label" for="s2id_advertiser_id">Advertiser</label>
							</div>
						</div>
						<div class="row">
							<div id="landing_page_parent" class="input-field col s12 m4 l4">
								<input placeholder="" id="landing_page" name="landing_page" type="text" length="255" value="<?php echo set_value('landing_page'); ?>">
								<label for="landing_page">Landing Page</label>
								<?php echo form_error('landing_page'); ?>
							</div>
							<div id="newadvertiserparent" class="col s12 m4 l4 offset-m2 offset-l2">
								<div class="input-field">
									<input placeholder="" id="new_advertiser" name="advertiser_name" type="text" length="255" value="<?php echo set_value('advertiser_name');  ?>">
									<label for="new_advertiser" style="left:0rem;">New Advertiser Name</label>
									<span id="newadvertiser_error" class="error label-important"></span>
								</div>
							</div>							
						</div>
						<div class="row">
							<div id="creative_request_owner_parent" class="input-field col s12 m4 l4">
								<input type="hidden" name="creative_request_owner_id" id="creative_request_owner"/>
								<label class="banner_intake_select_label" for="s2id_creative_request_owner">Ticket Owner <span class="tooltipped" data-position="top" data-delay="50" data-tooltip="This user will be on all email communications" style="cursor:pointer;"><i class="icon-question-sign"></i></span></label>
							</div>
							<div id="cc_on_ticket_parent" class="input-field col s12 m4 l4 offset-m2 offset-l2">
								<input type="hidden" name="cc_on_ticket" id="cc_on_ticket"/>
								<label class="banner_intake_select_label" for="s2id_cc_on_ticket">CC On Ticket</label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div id="banner_intake" class="row request_card">
			<div class="col s12">
				<div class="card">
					<div class="card-content grey-text text-darken-1">
						<div class="section">
							<h5>Custom Banner Design</h5>
						</div>
						<div class="divider"></div>
						<div class="row">
							<div class="input-field col s12 m4 l4">
								<input placeholder="" name="advertiser_website" id="advertiser_website" type="text" length="255" value="<?php echo set_value('advertiser_website');  ?>" >
								<label for="advertiser_website">Advertiser Website</label>
								<?php echo form_error('advertiser_website'); ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div id="ad_tags" class="row request_card">
			<div class="col s12">
				<div class="card">
					<div class="card-content grey-text text-darken-1">
						<div class="section">
							<h5 id="ad_tag_header">Ad Tags</h5>
						</div>
						<div class="divider"></div>
						<div class="row">
							<div class="col s12 m3 l3">
								<div class="row">
									<div class="input-field col s12">
										<textarea class="materialize-textarea ad_tags_tag" id="tag_320x50" name="tag_320x50" placeholder=""><?php echo set_value('tag_320x50'); ?></textarea>
										<label for="tag_320x50">320x50</label>
									</div>
								</div>
								<div class="input-field row">
									<div class="input-field col s12">
										<textarea class="materialize-textarea ad_tags_tag" id="tag_728x90" name="tag_728x90" placeholder=""><?php echo set_value('tag_728x90'); ?></textarea>
										<label for="tag_728x90">728x90</label>
									</div>
								</div>
								<div class="input-field row">
									<div class="input-field col s12">
										<textarea class="materialize-textarea ad_tags_tag" id="tag_160x600" name="tag_160x600" placeholder=""><?php echo set_value('tag_160x600'); ?></textarea>
										<label for="tag_160x600">160x600</label>
									</div>
								</div>
								<div class="input-field row">
									<div class="input-field col s12">
										<textarea class="materialize-textarea ad_tags_tag" id="tag_336x280" name="tag_336x280" placeholder=""><?php echo set_value('tag_336x280'); ?></textarea>
										<label for="tag_336x280">336x280</label>
									</div>
								</div>
								<div class="input-field row">
									<div class="input-field col s12">
										<textarea class="materialize-textarea ad_tags_tag" id="tag_300x250" name="tag_300x250" placeholder=""><?php echo set_value('tag_300x250'); ?></textarea>
										<label for="tag_300x250">300x250</label>
									</div>
								</div>
								<div class="input-field row">
									<div class="input-field col s12">
										<textarea class="materialize-textarea ad_tags_tag" id="tag_custom" name="tag_custom" placeholder=""><?php echo set_value('tag_custom'); ?></textarea>
										<label for="tag_custom">Custom</label>
									</div>
								</div>
							</div>
							<div id="tags_preview" class="input-field col s12 m9 l9 center">
								<div class="row">
									<div class="col s12">
										<div id="tag_320x50_preview"><img src="/images/banner_intake/320x50_placeholder.png"/></div>
									</div>
								</div>
								<div class="row">
									<div class="col s12"> 
										<div id="tag_728x90_preview"><img src="/images/banner_intake/728x90_placeholder.png"/></div>
									</div>
								</div>
								<div class="row" >
									<div class="col s12 m3 l3">
										<div id="tag_160x600_preview"><img src="/images/banner_intake/160x600_placeholder.png"/></div>
									</div>
									<div class="col s12 m8 l8 offset-l1 offset-m1">
										<div class="row">
											<div class="col s12">
												<div id="tag_336x280_preview"><img src="/images/banner_intake/336x280_placeholder.png"/></div>
											</div>
										</div>
										<div class="row">
											<div class="col s12">
												<div id="tag_300x250_preview" ><img src="/images/banner_intake/300x250_placeholder.png"/></div>
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col s12">
										<div id="tag_custom_preview"></div> 
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div id="upload_own" class="row">
			<div class="col s12">
				<div class="card">
					<div id="upload_own_container" class="card-content grey-text text-darken-1">
						<div class="section">
							<h5 id="upload_your_own_card_title">Images and Assets</h5>
						</div>
						<div class="divider"></div>
						<div id="file_upload_error" class="row">
							<div class="col s12">
								<span class="label-important"></span>
							</div>
						</div>
						<div id="video_url" class="row">
							<div class="col s12">
								<div class="row">
									<div class="col s12">
										<input name="video_url" type="radio" id="video_url_url" class="preroll_video_url"/>
										<label for="video_url_url"><i class="material-icons">video_library</i>Provide Video URL</label>
									</div>
								</div>
								<div id="video_url_url_container" class="row video_url_containers">
									<div class="col s10" style="margin-left:35px;">
										<input id="preroll_video_url" class="input-large" type="text" placeholder="http://youtu.be/dQw4w9WgXcQ" name="preroll_video_url" value="">
										<label for="preroll_video_url" class="active" style="left: 0px;">Video URL</label>
									</div>
								</div>
								<div class="row">
									<div class="col s12">
										<input name="video_url" type="radio" id="video_url_upload_own" class="preroll_video_url"/>
										<label for="video_url_upload_own"><i class="material-icons">cloud_upload</i>Upload Your Own Files</label>
									</div>
								</div>
								<div id="video_url_upload_own_container" class="row video_url_containers" style="margin-top:0px;margin-bottom: 0px;">
									<div class="col s11">
										<div class="upload_files_accordion">&nbsp;</div>
									</div>
								</div>
							</div>
						</div>
						<div id="upload_file_container" class="row">
							<div class="col s12">
								<div class="btn fileinput-button">
									<i class="icon-file icon-white"></i>
									<span>UPLOAD FILES...</span>
									<input id="file_upload" type="file" name="files[]" multiple />
								</div>
							</div>
							<div class="col s12">
								<div class="alert" id="main_files_alert">
									Upload your logo, image files, and any other brand assets required
								</div>
							</div>
							<div class="col s12">
								<div class="row" id="files" class="files">
<?php								
									if ($this->input->post('creative_files', true))
									{
										foreach($this->input->post('creative_files', true) as $count => $file)
										{
											if ($count > 0) 
											{
												$count++;
												echo	'<div class="file col s12 m3 l3" data-file="'. $file['url'] .'">
														<div class="row uploaded-file">
															<div class="col s12"><label class="control-label" style="font-size:0.85em;">'.substr($file['name'], 7).'...</label></div>
															<div class="col s6"><label class="control-label" style="font-size:0.85em;">File Size - <b>'.$file['size'].'KB</b></label></div>
															<div class="col s3 offset-s3"><button class="btn-floating waves-effect waves-light red delete_file" data-bucket="'. $file['bucket'] .'" data-name="'. $file['name'] .'"><i class="tiny material-icons right">delete</i></button></div>    
															<input type="hidden" name="creative_files['. $count .'][url]" value="'. $file['url'] .'"/>
															<input type="hidden" name="creative_files['. $count .'][name]" value="'. $file['name'] .'"/>
															<input type="hidden" name="creative_files['. $count .'][bucket]" value="'. $file['bucket'] .'"/>
															<input type="hidden" name="creative_files['. $count .'][type]" value="'. $file['type'] .'"/>
															<input type="hidden" name="creative_files['. $count .'][size]" value="'. $file['size'] .'"/>	
														</div>
													</div>';
											}
										}
									}
?>								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div id="storyboard" class="row">
			<div class="col s12">
				<div class="card">
					<div class="card-content grey-text text-darken-1">
						<div class="section">
							<h5>Storyboarding</h5>
						</div>
						<div class="divider"></div>
						<div class="row" id="scene_1_control_group">
							<div class="input-field col s12 m4 l4">
								<textarea id="scene_1_input" class="materialize-textarea" placeholder="an image of a burger and the words 'america's #1 diner'" required name="scenes[]"><?php echo set_value('scenes[0]'); ?></textarea>
								<label for="scene_1_input" class="active">Scene 1</label>
							</div>
<?php
							// Sticky form if validation fails
							// Drawback fo this code: you can leave empty scenes after scene 1. Needs to be revisited.
							if ($this->input->post('scenes', true))
							{
								foreach($this->input->post('scenes', true) as $count => $scene)
								{
									if ($count > 0) 
									{
										$count++;
										echo	"<div id=\"scene_". $count ."_control_group\" class=\"input-field col s12 m4 l4\">
													<textarea class=\"materialize-textarea\" id=\"scene_". $count ."_input\" placeholder=\"an image of a burger and the words 'america's #1 diner'\" required name=\"scenes[]\" >". $scene ."</textarea>
												";
												if ($count == count($this->input->post('scenes')))
												{
													echo " <a id=\"remove_scene_". $count ."_button\" onclick=\"delete_scene()\" class=\"btn-floating waves-effect waves-light red\"><i class=\"material-icons\">delete</i></a>";
												}
										echo		"
													<label for=\"scene_". $count ."_input\">Scene ". $count ."</label>
											</div>";
									}
								}
							}
?>						</div>
						<div class="row" id="add_scene_cg">
							<div class="input-field col s12 m4 l4">
								<a class="waves-effect waves-light btn" onclick="add_scene()"><i class="material-icons left">add</i>Add Scene</a>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s12 m4 l4">
								<select id="cta_select" name="cta">
									<option value="">Select Call to Action</option>
									<option value="Click for Details" <?php echo set_select('cta', 'Click for Details'); ?>>Click for Details</option>
									<option value="Learn More" <?php echo set_select('cta', 'Learn More'); ?> >Learn More</option>
									<option value="Visit Us" <?php echo set_select('cta', 'Visit Us'); ?> >Visit Us</option>
									<option value="Buy Now" <?php echo set_select('cta', 'Buy Now'); ?> >Buy Now</option>
									<option value="Contact Us" <?php echo set_select('cta', 'Contact Us'); ?> >Contact Us</option>
									<option value="Choose for Me" <?php echo set_select('cta', 'Choose for Me'); ?> >Don't know, please decide for me</option>
									<option value="other" <?php echo set_select('cta', 'other'); ?> >Other</option>
								</select>
								<label for="cta_select">Call-to-action</label>
							</div>
							<div id="other_cta_text" class="input-field col s12 m4 l4" >
								<input id="cta_other" class="input-large" type="text" placeholder="" name="cta_other" value="<?php echo set_value('cta_other'); ?>">
								<label for="cta_other">Other Call-to-action</label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div id="features" class="row">
			<div class="col s12">
				<div class="card">
					<div class="card-content grey-text text-darken-1">
						<div class="section">
							<h5>Features</h5>
						</div>
						<div class="divider"></div>
						<div class="row">
							<div class="input-field col s12">
								<?php
									// Not using set_checked() here because it only works for checkboxes with non-Boolean values
								?>
								<input type="checkbox" id="is_video" name="is_video" <?php if ($this->input->post('is_video')) echo 'checked="checked"'; ?> />
								<label for="is_video">Video</label>
							</div>
							<div id="video_details" style="<?php if (!$this->input->post('is_video')) echo 'display:none;'; ?>" class="col s12">
								<div class="row">
									<div class="input-field col s12 m4 l4 offset-m1 offset-l1" >
										<input id="features_video_youtube_url" class="input-large is_video_field" type="text" placeholder="http://youtu.be/dQw4w9WgXcQ" name="features_video_youtube_url" value="<?php echo set_value('features_video_youtube_url'); ?>">
										<label for="youtube_url">Youtube URL</label>
										<?php echo form_error('features_video_youtube_url'); ?>
									</div>
									<div class="col s10 offset-m1 offset-l1">
										<div id="video_details_subheader" class="col s12 video_details_subheader" >
											<span id="features_video_video_play_span">Video plays when: </span>
											<?php echo form_error('features_video_video_play'); ?>
										</div>
										<div class="col s12" >
											<input type="radio" name="features_video_video_play" id="optionsRadios1" value="hover-to-play_anytime" <?php echo set_radio('features_video_video_play', 'hover-to-play_anytime'); ?> class="with-gap" />
											<label for="optionsRadios1">Hover-to-play anytime</label>
										</div>
										<div class="col s12" >
											<input type="radio" name="features_video_video_play" id="optionsRadios2" value="hover-to-play_after_animation" <?php echo set_radio('features_video_video_play', 'hover-to-play_after_animation'); ?> class="with-gap" />
											<label for="optionsRadios2">Hover-to-play after animation</label>
										</div>
										<div class="col s12" >
											<input type="radio" name="features_video_video_play" id="optionsRadios3" value="click_after_animation" <?php echo set_radio('features_video_video_play', 'click_after_animation'); ?> class="with-gap" />
											<label for="optionsRadios3">Click to play after animation completes</label>
										</div>
										<div class="col s12" >
											<input type="radio" name="features_video_video_play" id="optionsRadios4" value="auto_after_animation" <?php echo set_radio('features_video_video_play', 'auto_after_animation'); ?> class="with-gap" />
											<label for="optionsRadios4">Autoplay after animation completes</label>
										</div>
										<div class="col s12" >
											<input type="radio" name="features_video_video_play" id="optionsRadios5" value="auto_with_animation" <?php echo set_radio('features_video_video_play', 'auto_with_animation'); ?> class="with-gap" />
											<label for="optionsRadios5">Video and animation play simultaneously</label>
										</div>
									</div>
									<div style="clear:both;">&nbsp;</div>
									<div class="col s10 offset-m1 offset-l1">
										<div class="col s12 video_details_subheader" >
											<span id="features_video_mobile_clickthrough_to_span">Mobile ad unit (320x50) clicks through to: </span>
											<?php echo form_error('features_video_mobile_clickthrough_to'); ?>
										</div>
										<div class="col s12" >
											<input type="radio" name="features_video_mobile_clickthrough_to" id="mobile_clickthrough_radio_lp" value="landing_page" <?php echo set_radio('features_video_mobile_clickthrough_to', 'landing_page'); ?> class="with-gap" />
											<label for="mobile_clickthrough_radio_lp">Landing page</label>
										</div>
										<div class="col s12" >
											<input type="radio" name="features_video_mobile_clickthrough_to" id="mobile_clickthrough_radio_vid" value="video" <?php echo set_radio('features_video_mobile_clickthrough_to', 'video'); ?> class="with-gap" />
											<label for="mobile_clickthrough_radio_vid">Video</label>
										</div>
									</div>
								</div>
							</div>
							<div class="input-field col s12">
								<input type="checkbox" id="is_map" name="is_map" <?php if ($this->input->post('is_map')) echo 'checked="checked"'; ?> />
								<label for="is_map">Map</label>
							</div>	
							<div id="map_details" style="<?php if (!$this->input->post('is_map')) echo 'display:none;"'; ?>" class="col s12">
								<div class="row">
									<div class="input-field col s12 m4 l4 offset-m1 offset-l1">
										<textarea class="materialize-textarea" name="features_map_locations" id="features_map_locations" placeholder="1600 Pennsylvania Ave NW, Washington, DC 20500"><?php echo $this->input->post('features_map_locations', true); ?></textarea>
										<label for="features_map_locations" class="active">Map locations (one per line)</label>
									</div>
								</div>
							</div>
							<div class="input-field col s12">
								<input type="checkbox" id="is_social" name="is_social" <?php if ($this->input->post('is_social')) echo 'checked="checked"'; ?> />
								<label for="is_social">Social</label>
							</div>
							<div id="social_details" style="<?php if (!$this->input->post('is_social')) echo 'display:none;'; ?>" class="col s12">
								<div class="row">
									<div class="input-field col s10 m4 l4 offset-m1 offset-l1">
										<input type="text" id="li_subject" name="features_social_linkedin_subject" placeholder="Check it out: Johnny's Diner - 2 for 1 Thursday!"  value="<?php echo set_value('features_social_linkedin_subject'); ?>">
										<label class="active" for="li_subject">Linkedin subject title</label>
									</div>
									<div class="input-field col s10 m5 l5 offset-m1 offset-l1">
										<textarea class="materialize-textarea" id="li_message" name="features_social_linkedin_message" placeholder="I'll be at Johnny's on Thursday. Burgers are delicious and it's 2 for 1!"><?php echo set_value('features_social_linkedin_message'); ?></textarea>
										<label class="active" for="li_message">Linkedin message</label>
									</div>
								</div>
								<div class="row">
									<div class="input-field col s10  m4 l4 offset-m1 offset-l1">
										<input type="text" id="email_subject" name="features_social_email_subject" placeholder="Johnny's Diner - 2 for 1 Thursday! johnnys.com/burger"  value="<?php echo set_value('features_social_email_subject'); ?>">
										<label class="active" for="email_subject">Email subject title</label>
									</div>
									<div class="input-field col s10 m5 l5 offset-m1 offset-l1">
										<textarea class="materialize-textarea" id="email_message" name="features_social_email_message" placeholder="Hi there, Meet you at Johnny's on Thursday? Burgers are delicious and it's 2 for 1!"><?php echo set_value('features_social_email_message'); ?></textarea>
										<label class="active" for="email_message">Email message</label>
									</div>
								</div>
								<div class="row">
									<div class="input-field col s10 m4 l4 offset-m1 offset-l1">
										<input type="text" id="twitter_text" name="features_social_twitter_text" placeholder="Check it out: Johnny's Diner - 2 for 1 Thursday!"  value="<?php echo set_value('features_social_twitter_text'); ?>">
										<label class="active" for="twitter_text">Twitter text</label>
									</div>
								</div>
							</div>
							<div class="input-field col s12">
								<input type="checkbox" id="has_variations" name="has_variations" <?php if ($this->input->post('is_social')) echo 'checked="checked"'; ?> />
								<label for="has_variations">Location Variations <span class="tooltipped" data-position="right" data-delay="50" data-tooltip="Add additional information for multiple store locations, like additional phone numbers, addresses, or cities."><i class="icon-question-sign"></i></span></label>
							</div>
							<div id="variations_details" style="<?php if (!$this->input->post('has_variations')) echo 'display:none;'; ?>" class="col s12">
								<div class="row">
									<div class="input-field col s10 m4 l6 offset-m1 offset-l1">
											<input type="text" id="variation_spec" name="variation_spec" placeholder="" value="" length="100">
											<label class="active" for="variation_1_name">Describe how you plan on varying the text between ads</label>
									</div>
								</div>
								<div class="row">

									<div class="input-field col s10 m4 l2 offset-m1 offset-l1">
										<input type="text" id="variation_1_name" name="variation_names[]" placeholder="Chicago" value="" length="20">
										<label class="active" for="variation_1_name">Name of the location variation</label>
									</div>
									<div class="input-field col s10 m4 l4 offset-m1 offset-l1">
										<input type="text" id="variation_1_details" name="variation_details[]" placeholder="Located at 150 Main St. Chicago, IL" value="" length="100">
										<label class="active" for="variation_1_details">Text copy for your variation</label>
									</div>
								</div>
								<div class="row">

									<div class="input-field col s10 m4 l2 offset-m1 offset-l1">
										<input type="text" id="variation_2_name" name="variation_names[]" placeholder="Another city" value="" length="20">
									</div>
									<div class="input-field col s10 m4 l4 offset-m1 offset-l1">
										<input type="text" id="variation_2_details" name="variation_details[]" placeholder="Another text variation" value="" length="100">
									</div>
									<br>
								</div>
								<div class="row" id="add_variation_row">
									<div class="input-field col s12 m4 l4 offset-m1 offset-l1">
										<a class="waves-effect waves-light btn" onclick="add_variation(this)"><i class="material-icons left">add</i>Add Text Variation</a>
									</div>
								</div>								
							</div>						
						</div>
					</div>
				</div>
			</div>
		</div>    
		<div class="row">
			<div class="col s12">
				<div class="card">
					<div class="card-content grey-text text-darken-1">
						<div class="section">
							<h5>Other Comments</h5>
						</div>
						<div class="divider"></div>
						<div class="row">
							<div class="col s12">
								<textarea class="materialize-textarea" id="other_comments" name="other_comments" rows="5" placeholder="We want to deliver your perfect creative - and fast. Anything else we should know?"><?php echo set_value('other_comments'); ?></textarea>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<button id="submit_request" class="btn-large waves-effect waves-light right" type="button" name="action">Submit Request
			<i class="material-icons right">send</i>
		</button>
	</form>
</div>
