<div class="container-fluid banner_intake_body">
	<div class="tabbable">
		<ul id="banner_tabs" class="nav nav-pills" data-tabs="tabs">
			<li class="active"><a href="/banner_intake/<?php echo $email; ?>" data-toggle="tab" >New Adset Request</a></li>
			<?php echo $authorized_menu; ?>
		</ul>
		<?php
			if (isset($submission_failed))
			{
				echo "<div class=\"alert alert-error\"><b>Oops!</b> Your submission could not be completed. Please try again.</div>";
			}

			if (validation_errors())
			{
				echo '<div class="alert alert-error"><b>Oops!</b> Some required fields were empty. Please fill out the fields indicated below and re-submit.</div>';
			}
		?>
		<div class="tab-content">
			<div class="tab-pane active" id="new_adset">
				<div class="row-fluid">
					<div class="span12">
						<form class="form-horizontal" id="banner_intake_form"   method="post" accept-charset="utf-8" enctype="multipart/form-data" novalidate>
							<input type="hidden" name="requester_email" value="<?php echo $email;?>">
							<input type="hidden" name="requester_id" value="<?php echo $user_id;?>">
							<legend class="muted">Flight Details</legend>
							<div class="control-group " id="advertiser_name_control_group">
								<label class="control-label" for="advertiser_name" >Advertiser</label>
								<div class="controls">
									<input class="input span4" type="text" id="advertiser_name" placeholder="Johnny's Diner" name="advertiser_name" value="<?php echo set_value('advertiser_name'); ?>">
									<?php echo form_error('advertiser_name'); ?>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="advertiser_website">Advertiser Website</label>
								<div class="controls">
									<input class="input span4" type="text" id="advertiser_website" placeholder="http://johnnys.com" name="advertiser_website" value="<?php echo set_value('advertiser_website'); ?>">
									<?php echo form_error('advertiser_website'); ?>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="campaign_landing_page">Landing Page</label>
								<div class="controls">
									<input class="input span4" type="text" id="campaign_landing_page" placeholder="http://johnnys.com/burgers" name="landing_page" value="<?php echo set_value('landing_page'); ?>">
									<?php echo form_error('landing_page'); ?>
								</div>
							</div>
							<div class="control-group " id="advertiser_name_control_group">
								<label class="control-label" for="advertiser_name" >Your Email Address</label>
								<div class="controls">
									<input class="input span4" type="text" id="advertiser_email" placeholder="your.email@address.com" name="advertiser_email" value="<?php echo set_value('advertiser_email') ?: $email;?>">
									<?php echo form_error('advertiser_email'); ?>
								</div>
							</div>
							<legend class="muted">Creative Assets</legend>
							<span class="btn btn-primary fileinput-button">
						        <i class="icon-file icon-white"></i>
						        <span>Add files...</span>
						        <!-- The file input field used as target for the file upload widget -->
						        <input id="file_upload" type="file" name="files[]" multiple />
						    </span> <?php echo form_error('creative_files'); ?>
						    <br />
						    <!-- The container for the uploaded files -->
						    <div id="files" class="files">
						    	<div class="alert" id="main_files_alert">Drag and Drop your files anywhere on the page, or click the 'Add files...' button to get started.</div>
						    	<?php
						    		if ($this->input->post('creative_files', true))
									{
										foreach($this->input->post('creative_files', true) as $count => $file)
										{
											if ($count > 0) 
											{
												$count++;
												echo '<div class="file row" data-file="'. $file['url'] .'"/>
														<div><b>'. substr($file['name'], 7) .'</b> <button class="btn btn-danger btn-mini delete_file" data-bucket="'. $file['bucket'] .'" data-name="'. $file['name'] .'"><span class="icon icon-remove"></span></button></div>
										        		<input type="hidden" name="creative_files['. $count .'][url]" value="'. $file['url'] .'"/>
										        		<input type="hidden" name="creative_files['. $count .'][name]" value="'. $file['name'] .'"/>
										        		<input type="hidden" name="creative_files['. $count .'][bucket]" value="'. $file['bucket'] .'"/>
										        		<input type="hidden" name="creative_files['. $count .'][type]" value="'. $file['type'] .'"/>
										        		</div>';
											}
										}
									}
						    	?>
						    </div>					
							<legend class="muted">Storyboarding</legend>

							<div class="control-group" id="scene_1_control_group">
								<label class="control-label" for="scene_1_input">Scene 1</label>
								<div class="controls">
									<textarea class="input span4" type="text" id="scene_1_input" placeholder="an image of a burger and the words 'america's #1 diner'" required name="scenes[]"><?php echo set_value('scenes[0]'); ?></textarea>
								</div>
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
											echo "<div class=\"control-group\" id=\"scene_". $count ."_control_group\">
													<label class=\"control-label\" for=\"scene_". $count ."_input\">Scene ". $count ."</label>
													<div class=\"controls\">
														<textarea class=\"span4\" type=\"text\" id=\"scene_". $count ."_input\" placeholder=\"an image of a burger and the words 'america's #1 diner'\" required name=\"scenes[]\" >". $scene ."</textarea>";

											if ($count == count($this->input->post('scenes')))
											{
												echo " <a id=\"remove_scene_". $count ."_button\" onclick=\"delete_scene()\" class=\"btn btn-danger btn-mini\"><span class=\"icon icon-remove\"></span></a>";
											}

											echo "</div>
											</div>";
										}
									}
								}
							?>

							<div class="control-group" id="add_scene_cg">
								<label class="control-label"></label>
								<div class="controls">
									<a class="btn btn-mini " onclick="add_scene()"><i class="icon-plus" ></i> add scene</a>
									<?php echo form_error('scenes[0]'); ?>
								</div>
							</div>

							<div class="control-group">
								<label class="control-label" for="cta">Call-to-action</label>
								<div class="controls">
									<select class="span4" id="cta_select" name="cta">
										<option value="Click for Details" <?php echo set_select('cta', 'Click for Details', TRUE); ?> >"Click for Details"</option>
										<option value="Learn More" <?php echo set_select('cta', 'Learn More'); ?> >"Learn More"</option>
										<option value="Visit Us" <?php echo set_select('cta', 'Visit Us'); ?> >"Visit Us"</option>
										<option value="Buy Now" <?php echo set_select('cta', 'Buy Now'); ?> >"Buy Now"</option>
										<option value="Contact Us" <?php echo set_select('cta', 'Contact Us'); ?> >"Contact Us"</option>
										<option value="Choose for Me" <?php echo set_select('cta', 'Choose for Me'); ?> >Don't know, please decide for me</option>
										<option value="other" <?php echo set_select('cta', 'other'); ?> >Other</option>
									</select>
									<input class="input-large" type="text" id="other_cta_text" placeholder="" style="<?php if ($this->input->post('cta') !== 'other') echo 'display:none;"'; ?>" name="cta_other" value="<?php echo set_value('cta_other'); ?>">
									<?php echo form_error('cta_other'); ?>
								</div>
							</div>
							<legend class="muted">Features</legend>
							<div class="control-group">
								<div class="controls">
									<label class="checkbox">
										<?php
											// Not using set_checked() here because it only works for checkboxes with non-Boolean values
										?>
										<input type="checkbox" id="is_video" name="is_video" <?php if ($this->input->post('is_video')) echo 'checked="checked"'; ?>> <b>Video</b>
									</label>
									<div id="video_details" style="<?php if (!$this->input->post('is_video')) echo 'display:none;'; ?>">
										<div class="control-group">
											<label class="control-label" for="youtube_url">Youtube URL</label>

											<div class="controls">
												<input class="input-xlarge" type="text" name="features_video_youtube_url" placeholder="http://youtu.be/dQw4w9WgXcQ" value="<?php echo set_value('features_video_youtube_url'); ?>">
												<?php echo form_error('features_video_youtube_url'); ?>
											</div>
											
										</div>
										<div  class="control-group">
											<span class="indent-box">Video plays when: </span>
											<?php echo form_error('features_video_video_play'); ?>
											<label class="radio indent-box" >
												<input type="radio" name="features_video_video_play" id="optionsRadios1" value="hover-to-play_anytime" <?php echo set_radio('features_video_video_play', 'hover-to-play_anytime'); ?>>
												Hover-to-play anytime
											</label>
											<label class="radio indent-box" >
												<input type="radio" name="features_video_video_play" id="optionsRadios2" value="hover-to-play_after_animation" <?php echo set_radio('features_video_video_play', 'hover-to-play_after_animation'); ?>>
												Hover-to-play after animation
											</label>
											<label class="radio indent-box" >
												<input type="radio" name="features_video_video_play" id="optionsRadios3" value="click_after_animation" <?php echo set_radio('features_video_video_play', 'click_after_animation'); ?>>
												Click to play after animation completes
											</label>
											<label class="radio indent-box">
												<input type="radio" name="features_video_video_play" id="optionsRadios4" value="auto_after_animation" <?php echo set_radio('features_video_video_play', 'auto_after_animation'); ?>>
												Autoplay after animation completes
											</label>
											<label class="radio indent-box">
												<input type="radio" name="features_video_video_play" id="optionsRadios5" value="auto_with_animation" <?php echo set_radio('features_video_video_play', 'auto_with_animation'); ?>>
												Video and animation play simultaneously
											</label>
										</div>
										<span class="indent-box">Mobile ad unit (320x50) clicks through to: </span>
										<?php echo form_error('features_video_mobile_clickthrough_to'); ?>
										<label class="radio indent-box">
											<input type="radio" name="features_video_mobile_clickthrough_to" id="mobile_clickthrough_radio_lp" value="landing_page" <?php echo set_radio('features_video_mobile_clickthrough_to', 'landing_page'); ?>>
											Landing page
										</label>
										<label class="radio indent-box">
											<input type="radio" name="features_video_mobile_clickthrough_to" id="mobile_clickthrough_radio_vid" value="video" <?php echo set_radio('features_video_mobile_clickthrough_to', 'video'); ?>>
											Video
										</label>
									</div>
								</div>

								<div class="controls">
									<label class="checkbox">
										<input type="checkbox" id="is_map" name="is_map" <?php if ($this->input->post('is_map')) echo 'checked="checked"'; ?>> <b>Map</b>
									</label>
									<div id="map_details" style="<?php if (!$this->input->post('is_map')) echo 'display:none;"'; ?>">
									<div class="control-group">
												<label class="control-label" for="map_locations">Map locations (one per line)</label>
												<div class="controls">
													<textarea class="span6" id="map_locations" name="features_map_locations" placeholder="1600 Pennsylvania Ave NW, Washington, DC 20500"><?php echo $this->input->post('features_map_locations', true); ?></textarea>
													<?php echo form_error('features_map_locations'); ?>
												</div>
											</div>
										</div>
								</div>

								<div class="controls">
									<label class="checkbox">
										<input type="checkbox" id="is_social" name="is_social" <?php if ($this->input->post('is_social')) echo 'checked="checked"'; ?>> <b>Social</b>
									</label>
									<div id="social_details" style="<?php if (!$this->input->post('is_social')) echo 'display:none;'; ?>">
										<div class="control-group">
											<label class="control-label" for="twitter_text">Twitter text</label>
											<div class="controls">
												<input class="span6" type="text" id="twitter_text" name="features_social_twitter_text" placeholder="Check it out: Johnny's Diner - 2 for 1 Thursday!"  value="<?php echo set_value('features_social_twitter_text'); ?>">
												<?php echo form_error('features_social_twitter_text'); ?>
											</div>
										</div>
										<div class="control-group">
											<label class="control-label" for="email_subject">Email subject title</label>
											<div class="controls">
												<input class="span6" type="text" id="email_subject" name="features_social_email_subject" placeholder="Johnny's Diner - 2 for 1 Thursday! johnnys.com/burger"  value="<?php echo set_value('features_social_email_subject'); ?>">
												<?php echo form_error('features_social_email_subject'); ?>
											</div>
										</div>
										<div class="control-group">
											<label class="control-label" for="email_message">Email message</label>
											<div class="controls">
												<textarea class="span6" id="email_message" name="features_social_email_message" placeholder="Hi there, Meet you at Johnny's on Thursday? Burgers are delicious and it's 2 for 1!"><?php echo set_value('features_social_email_message'); ?></textarea>
												<?php echo form_error('features_social_email_message'); ?>
											</div>
										</div>
										<div class="control-group">
											<label class="control-label" for="li_subject">Linkedin subject title</label>
											<div class="controls">
												<input class="span6" type="text" id="li_subject" name="features_social_linkedin_subject" placeholder="Check it out: Johnny's Diner - 2 for 1 Thursday!"  value="<?php echo set_value('features_social_linkedin_subject'); ?>">
												<?php echo form_error('features_social_linkedin_subject'); ?>
											</div>
										</div>
										<div class="control-group">
											<label class="control-label" for="li_message">Linkedin message</label>
											<div class="controls">
												<textarea class="span6" id="li_message" name="features_social_linkedin_message" placeholder="I'll be at Johnny's on Thursday. Burgers are delicious and it's 2 for 1!"><?php echo set_value('features_social_linkedin_message'); ?></textarea>
												<?php echo form_error('features_social_linkedin_message'); ?>
											</div>
										</div>
									</div>
								</div>
							
								
							</div>

							<legend class="muted">Other Comments</legend>
							<div class="controls">
								<textarea class="input-xxlarge" id="other_comments" name="other_comments" rows="5" placeholder="We want to get you exactly what you want and more, fast. If there is anything else we should know, tell us now."><?php echo set_value('other_comments'); ?></textarea>
							</div>

							<hr>
							<div class="controls">
								<input class="btn btn-large btn-primary span3" type="submit" value="Submit" />
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>


</div>
