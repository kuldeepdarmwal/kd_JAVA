<?php 
	$email = array(
			'name'	=> 'email',
			'id'	=> 'email',
			'value'	=> set_value('email'),
			'maxlength'	=> 80,
			'size'	=> 30,
			'required' => 'required',
			);
	$password = array(
			'name'	=> 'password',
			'id'	=> 'password',
			'value' => set_value('password'),
			'maxlength'	=> $this->config->item('password_max_length', 'tank_auth'),
			'size'	=> 30,
			'required' => 'required',
			);
	$confirm_password = array(
			'name'	=> 'confirm_password',
			'id'	=> 'confirm_password',
			'value' => set_value('confirm_password'),
			'maxlength'	=> $this->config->item('password_max_length', 'tank_auth'),
			'size'	=> 30,
			'required' => 'required',
			);
	
		$role = array(
				'name' 	=> 'role',
				'id' 	=> 'role',
				'value' => set_value('role'),
				'maxlength' => 40,
				'size' 	=> 30,
				);
	if (!$is_sales_account) 
	{
		$role_options = array(
				'ADMIN' => 'Admin',
				'BUSINESS' => 'Advertiser',
				'CREATIVE' => 'Creative',
				'OPS' => 'Ops',
				'SALES' => 'Sales',
				);
	} else
	{
		$role_options = array(
				'BUSINESS' => 'Advertiser',
				);
	}
	$business_name = array(
			'name' 	=> 'advertiser_name',
			'id' 	=> 'advertiser_id_select',
			'value' => set_value('advertiser_select'),
			'maxlength' => 200,
			'size' 	=> 30,
			);
	$firstname = array(
			'name' 	=> 'firstname',
			'id' 	=> 'firstname',
			'value' => set_value('firstname'),
			'maxlength' => 45,
			'size' 	=> 24,
			'required' => 'required',
			);
	$lastname = array(
			'name' 	=> 'lastname',
			'id' 	=> 'lastname',
			'value' => set_value('lastname'),
			'maxlength' => 200,
			'size' 	=> 24,
			'required' => 'required',
			);
	$address_1 = array(
			'name' 	=> 'address 1',
			'id' 	=> 'address_1',
			'value' => set_value('address_1'),
			'maxlength' => 200,
			'size' 	=> 24
			);
	$address_2 = array(
			'name' 	=> 'address 2',
			'id' 	=> 'address_2',
			'value' => set_value('address_2'),
			'maxlength' => 200,
			'size' 	=> 24
			);
	$city = array(
			'name' 	=> 'city',
			'id' 	=> 'city',
			'value' => set_value('city'),
			'maxlength' => 200,
			'size' 	=> 24
			);
	$state = array(
			'name' 	=> 'state',
			'id' 	=> 'state',
			'value' => set_value('state'),
			'maxlength' => 2,
			'size' 	=> 2
			);
	$zip = array(
			'name' 	=> 'zip',
			'id' 	=> 'zip',
			'value' => set_value('zip'),
			'maxlength' => 5,
			'size' 	=> 5
			);
	$phone_number = array(
			'name' 	=> 'phone_number',
			'id' 	=> 'phone_number',
			'value' => set_value('phone_number'),
			'maxlength' => 200,
			'size' 	=> 24
			);
	$fax_number = array(
			'name' 	=> 'fax_number',
			'id' 	=> 'fax_number',
			'value' => set_value('fax_number'),
			'maxlength' => 200,
			'size' 	=> 24
			);
	$captcha = array(
			'name'	=> 'captcha',
			'id'	=> 'captcha',
			'maxlength'	=> 8,
			);
	$partner = array(
			'name'  => 'partner',
			'id'    => 'partner',
			'value' => set_value('partner'),
			'maxlength' => 200,
			'size'  => 30,
			);

	$option = "style='width:$width'";
?>
<?php
	
	if (!isset($selected_advertiser))
	{
		$selected_advertiser = "";
	} 

	if (!isset($selected_partner))
	{
		$selected_partner = "";
	}

?>

<div class="container">
	<h1><i class='icon-user'> </i>Register User</h1>
	<?php if ($this->session->flashdata('message')) { ?>
		<div class="alert alert-success">
			<a class="close" data-dismiss="alert" href="#">&times;</a>
			<?php echo $this->session->flashdata('message'); ?>
		</div>
	<?php } ?>
	<?php if (!$business_name_options) { ?>
		<div class="alert alert-error">
			Oops, it looks like you don't have any Advertiser Organizations that you can create users for. <a style='cursor:pointer;' onclick='history.go(-1)'>Click here to go back</a>.
		</div>
	<?php } else { ?>
	<?php if ($check_email_status) { ?>
		<div class="alert alert-error">
			We’re sorry. This login email already exists…
		</div>
	<?php } ?>
	<form action="/register" method="post" accept-charset="utf-8" class="form-horizontal form">
		<fieldset>
			<div class="row">
				<legend > </legend>
				<div class = "span5">

					<!-- REQUIRED FIELDS -->
					<!-- EMAIL -->
					<div class="control-group">
						<?php echo form_label('Email Address', $email['id'], array('class' => 'control-label')); ?>
						<div class="controls">
						<?php echo form_input($email); ?><?php echo form_error($email['name'], '<span class="err-box label label-important">', '</span>'); ?><?php echo '<span class="err-box label label-important">'.(isset($errors[$email['name']])?$errors[$email['name']]:'').'</span>'; ?>
						</div>
					</div>
					<!-- PASSWORD -->
					<div class="control-group">
						<?php echo form_label('Password', $password['id'], array('class' => 'control-label')); ?>
						<div class="controls">
						<?php echo form_password($password); ?><?php echo form_error($password['name'], '<span class="err-box label label-important">', '</span>'); ?>
						</div>
					</div>
					<!-- CONFIRM PASSWORD -->
					<div class="control-group">
						<?php echo form_label('Confirm Password', $confirm_password['id'], array('class' => 'control-label')); ?>
						<div class="controls">
						<?php echo form_password($confirm_password); ?><?php echo form_error($confirm_password['name'], '<span class="err-box label label-important">', '</span>'); ?>
						</div>
					</div>
					<!-- FIRST NAME -->
					<div class="control-group">
						<?php echo form_label('First Name', $firstname['id'], array('class' => 'control-label')); ?>
						<div class="controls">
						<?php echo form_input($firstname); ?><?php echo form_error($firstname['name'], '<span class="err-box label label-important">', '</span>'); ?>
						</div>
					</div>
					<!-- LAST NAME -->
					<div class="control-group">
						<?php echo form_label('Last Name', $lastname['id'], array('class' => 'control-label')); ?>
						<div class="controls">
						<?php echo form_input($lastname); ?><?php echo form_error($lastname['name'], '<span class="err-box label label-important">', '</span>'); ?>
						</div>
					</div>

					<?php if (!$is_sales_account) { ?>
						<div id="sales_contact_info" style="display:none">
							<legend>Proposal Contact Info <small>optional</small></legend>
							<!-- ADDRESS 1 -->
							<div class="control-group">
								<?php echo form_label('Address', $address_1['id'], array('class' => 'control-label')); ?>
								<div class="controls">
								<?php echo form_input($address_1); ?><?php echo '<span class="err-box label label-important">'.form_error($address_1['name']).'</span>'; ?>
								</div>
							</div>
							<!-- ADDRESS 2 -->
							<div class="control-group">
								<?php echo form_label('Address (Line 2)', $address_2['id'], array('class' => 'control-label')); ?>
								<div class="controls">
								<?php echo form_input($address_2); ?><?php echo '<span class="err-box label label-important">'.form_error($address_2['name']).'</span>'; ?>
								</div>
							</div>
							<!-- CITY -->
							<div class="control-group">
								<?php echo form_label('City', $city['id'], array('class' => 'control-label')); ?>
								<div class="controls">
								<?php echo form_input($city); ?><?php echo '<span class="err-box label label-important">'.form_error($city['name']).'</span>'; ?>
								</div>
							</div>
							<!-- STATE -->
							<div class="control-group">
								<?php echo form_label('State / Province', $state['id'], array('class' => 'control-label')); ?>
								<div class="controls">
								<?php echo form_input($state); ?><?php echo '<span class="err-box label label-important">'.form_error($state['name']).'</span>'; ?>
								</div>
							</div>
							<!-- ZIP -->
							<div class="control-group">
								<?php echo form_label('Zip / Postal Code', $zip['id'], array('class' => 'control-label')); ?>
								<div class="controls">
								<?php echo form_input($zip); ?><?php echo '<span class="err-box label label-important">'.form_error($zip['name']).'</span>'; ?>
								</div>
							</div>
							<!-- PHONE NUMBER -->
							<div class="control-group">
								<?php echo form_label('Phone Number', $phone_number['id'], array('class' => 'control-label')); ?>
								<div class="controls">
								<?php echo form_input($phone_number); ?><?php echo form_error($phone_number['name'], '<span class="err-box label label-important">', '</span>'); ?>
								</div>
							</div>
						</div>
					<?php } ?>
				</div>
				<div class = "span7">
					<!-- ROLE -->
					<div class="control-group">
					<?php if (!$is_sales_account) { ?>
						<?php echo form_label('<i class="icon-user"></i> User Role', $role['id'], array('class' => 'control-label')); ?>
						<div class="controls">
							<?php $dropdown_js = 'id="role"' ?>
							<?php echo form_dropdown('role',$role_options, $selected_role, $option . " " . $dropdown_js); ?>
							<?php //echo form_checkbox('isGlobalSuper','isGlobalSuper', 0, "id='isGlobalSuper'"); ?>
							<?php //echo " <span id='isGlobalSuper_label'>is super?</span>" ?>
							<?php echo form_error($role['name'], '<span class="err-box label label-important">', '</span>'); ?>
						</div>
					<?php } else { ?>
						<?php echo form_label('<i class="icon-user"></i> User Role', $role['id'], array('class' => 'control-label')); ?>
						<div class="controls">
							<?php echo "<span class='control-label' style='text-align:left;'><b>" . $role_options['BUSINESS'] . "</b></span>"; ?>
						</div>
					<?php } ?>	
					</div>
					<!-- ADVERTISER NAME -->
					<div class="control-group" id='advertiser_select_container' <?php if ($selected_role != 'BUSINESS') echo 'style="display:none;"'; ?>>
						<?php echo form_label('<i class="icon-building"></i> Advertiser Org.', $business_name['id'], array('class' => 'control-label','id'=> 'advertiser_select_label')); ?>
						<div class="controls">
							<input class="" name='advertiser_name' id="advertiser_select" type="hidden" value="<?php echo form_prep($selected_advertiser); ?>" >
							<?php echo form_error($business_name['name'], '<span class="err-box label label-important">', '</span>'); ?>
						</div>
					</div>
					<?php if (!$is_sales_account) { ?>
					<!-- PARTNER NAME -->
					<div class="control-group"  id='partner_container' <?php if ($selected_role != 'SALES') echo 'style="display:none;"'; ?> >
						<?php echo form_label('<i class="icon-group"></i> Partner', $partner['id'], array('class' => 'control-label','id'=> 'partner_label')); ?>
						<div class="controls">
							
							<input class="" name='partner' id="partner_select" type="hidden" value="<?php echo form_prep($selected_partner); ?>" ><br>
							<?php echo form_error($partner['name'], '<span class="err-box label label-important">', '</span>'); ?><br>
							<label class="checkbox">
								<?php echo form_checkbox('isGroupSuper','isGroupSuper', 0, "id='isGroupSuper' " . ($is_group_super_checked ? 'checked="checked"' : "")); ?> <span id='isGroupSuper_label'>is super?</span>
							</label>
							
						</div>
					</div>
					<?php } ?>
					<?php if ((!$is_sales_account) AND $user_permissions['can_view_planner']) { ?>
						<div class="control-group">
							<div class="controls">
								<label class="checkbox">
									<input type="checkbox" name="planner_viewable" value="planner_viewable" id="is_planner" <?php echo ($is_planner_viewable_checked ? 'checked="checked"' : ''); ?> > <span id='is_planner_label'>Can view old planner?</span>
								</label>
							</div>
						</div>
					<?php } ?>

					<?php if($user_permissions['can_view_placements']) { ?>
					<div class="control-group">
						<div class="controls">
							<label class="checkbox">
								<input type="checkbox" name="placements_viewable" value="placements_viewable" id="is_placements" <?php echo ($is_placements_viewable_checked ? 'checked="checked"' : ''); ?> > <span id='is_planner_label'>Can view placements in reports?</span>
							</label>
						</div>
					</div>
					<?php } ?>

					<?php if($user_permissions['can_view_screenshots']) { ?>
					<div class="control-group">
						<div class="controls">
							<label class="checkbox">
								<input type="checkbox" name="screenshots_viewable" value="screenshots_viewable" id="is_screenshots" <?php echo ($is_screenshots_viewable_checked ? 'checked="checked"' : ''); ?> > <span id='is_screenshots_label'>Can view screenshots in reports?</span>
							</label>
						</div>
					</div>
					<?php } ?>

					<?php if($user_permissions['can_view_engagements']) { ?>
					<div class="control-group">
						<div class="controls">
							<label class="checkbox">
								<input type="checkbox" name="engagements_viewable" value="engagements_viewable" id="is_engagements" <?php echo ($is_engagements_viewable_checked ? 'checked="checked"' : ''); ?> > <span id='is_engagements_label'>Can view engagements in reports?</span>
							</label>
						</div>
					</div>
					<?php } ?>
					<hr>
					<div class="control-group">
						<div class="controls">
							<label class="radio">
								<input type="radio" name="send_registration_welcome_email" value="false" id="copy_paste_credentials" <?php echo (!($is_send_registration_welcome_email_checked) ? 'checked="checked"' : ''); ?> > <span id='copy_paste_credentials_span'>Copy paste credentials</span>
							</label>
							<div class="well">
								<?php echo nl2br($email_contents); ?>
							</div>
							<label class="radio">
								<input type="radio" name="send_registration_welcome_email" value="true" id="automatically_send_email" <?php echo ($is_send_registration_welcome_email_checked ? 'checked="checked"' : ''); ?>> <span id='automatically_send_email_span'>Automatically send welcome email?</span>
							</label>
						</div>
					</div>
			<?php if($this->session->userdata('is_demo_partner') != 1) { ?>
					<div class="controls" id="submit_button">
						<?php echo form_submit('register', 'Register', "class='btn btn-large btn-primary span5'"); ?>
					</div>
			<?php } else { ?>
					<div class="controls" id="submit_button">
					    <button name="register" type="button" class="btn btn-large span5" style="cursor:default;outline:none; color: #A4A4A4">Register</button>
					</div>
			<?php } ?>
				</div>
			</div>
			


  <?php if ($captcha_registration) { //this variable is in the tankauth config file and can be set to true in order for it to be used
	if ($use_recaptcha) { ?>
  <table><tr>
	<td colspan="2">
	  <div id="recaptcha_image"></div>
	</td>
	<td>
	  <a href="javascript:Recaptcha.reload()">Get another CAPTCHA</a>
	  <div class="recaptcha_only_if_image"><a href="javascript:Recaptcha.switch_type('audio')">Get an audio CAPTCHA</a></div>
	  <div class="recaptcha_only_if_audio"><a href="javascript:Recaptcha.switch_type('image')">Get an image CAPTCHA</a></div>
	</td>
  </tr>
  <tr>
	<td>
	  <div class="recaptcha_only_if_image">Enter the words above</div>
	  <div class="recaptcha_only_if_audio">Enter the numbers you hear</div>
	</td>
	<td><input type="text" id="recaptcha_response_field" name="recaptcha_response_field" /></td>
	<td><?php echo form_error('recaptcha_response_field', '<span class="err-box label label-important">', '</span>'); ?></td>
	<?php echo $recaptcha_html; ?>
  </tr>
  <?php } else { ?>
  <tr>
	<td colspan="3">
	  <p>Enter the code exactly as it appears:</p>
	  <?php echo $captcha_html; ?>
	</td>
  </tr>
  <tr>
	<td><?php echo form_label('Confirmation Code', $captcha['id']); ?></td>
	<td><?php echo form_input($captcha); ?></td>
	<td><?php echo form_error($captcha['name'], '<span class="err-box label label-important">', '</span>'); ?></td>
  </tr>
  </table>
  <?php }
	} ?>

		<div class="row-fluid">
		</div>
		</fieldset>
	</form>
<?php } ?>
