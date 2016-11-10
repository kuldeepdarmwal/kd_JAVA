<!DOCTYPE html>
<html>
<head>
	<title>Register User</title>
<link rel="shortcut icon" type="image/png" href="http://adage.vantagelocal.com/wp-content/uploads/2013/04/favicon-1.ico">
	<link rel="shortcut icon" href="/images/favicon_blue.png">
	<link rel="stylesheet" href="/bootstrap/assets/css/bootstrap.css">
	<link rel="stylesheet" href="/bootstrap/assets/css/bootstrap-responsive.css">
	<link href="/libraries/external/select2/select2.css" rel="stylesheet"/>
	
	

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
<script src="/libraries/external/select2/select2.js"></script>
<script type="text/javascript">
$(document).ready(function(){
				//INITIALIZE TO BUSINESS
				
				$("#isGlobalSuper").slideUp(0);
				$("#isGlobalSuper_label").slideUp(0);
				$("#isGlobalSuper").prop("checked", false);
				
				$("#s2id_advertiser_select").slideDown(0);//originally called 'advertiser_select' - the Select2 widget prepends it with 's2id'
				$("#advertiser_select_label").slideDown(0);
				
				$("#partner").slideUp(0);
				$("#partner_label").slideUp(0);
				
				$("#isGroupSuper").slideUp(0);
				$("#isGroupSuper_label").slideUp(0);
	
	function clear_all_err_boxes(){
		$(".err-box").slideUp(0);
	}


	function did_change_role() {
		var value = $("#role").val();
		clear_all_err_boxes();

		switch (value){
			case "ADMIN":
				$("#isGlobalSuper").slideUp(0);
				$("#isGlobalSuper_label").slideUp(0);
				$("#isGlobalSuper").prop("checked", false);
				

				$("#s2id_advertiser_select").slideUp(0);//originally called 'advertiser_select' - the Select2 widget prepends it with 's2id'
				$("#advertiser_select_label").slideUp(0);
				
				$("#advertiser_select").val('');
				
				
				$("#partner").slideUp(0);
				$("#partner_label").slideUp(0);
				$("#partner").val("1");
				
				$("#isGroupSuper").slideUp(0);
				$("#isGroupSuper_label").slideUp(0);
				break;
			case "BUSINESS":
				$("#isGlobalSuper").slideUp(0);
				$("#isGlobalSuper_label").slideUp(0);
				$("#isGlobalSuper").prop("checked", false);
				$("#s2id_advertiser_select").slideDown(0);//originally called 'advertiser_select' - the Select2 widget prepends it with 's2id'
				$("#advertiser_select_label").slideDown(0);

				
				$("#partner").slideUp(0);
				$("#partner_label").slideUp(0);
				$("#partner").val("1");
				
				$("#isGroupSuper").slideUp(0);
				$("#isGroupSuper_label").slideUp(0);
				$("#isGroupSuper").prop("checked", false);
				
				break;
			case "CREATIVE":
				$("#isGlobalSuper").slideDown(0);
				$("#isGlobalSuper_label").slideDown(0);
				
				$("#s2id_advertiser_select").slideUp(0);//originally called 'advertiser_select' - the Select2 widget prepends it with 's2id'
				$("#advertiser_select_label").slideUp(0);
				$("#advertiser_select").val('');
				
				$("#partner").slideUp(0);
				$("#partner_label").slideUp(0);
				$("#partner").val("1");
				
				$("#isGroupSuper").slideUp(0);
				$("#isGroupSuper_label").slideUp(0);
				$("#isGroupSuper").prop("checked", false);
			
				break;
			case "SALES":
				$("#isGlobalSuper").slideUp(0);
				$("#isGlobalSuper_label").slideUp(0);
				$("#isGlobalSuper").prop("checked", false);
				
				$("#s2id_advertiser_select").slideUp(0);//originally called 'advertiser_select' - the Select2 widget prepends it with 's2id'
				$("#advertiser_select_label").slideUp(0);
				$("#advertiser_select").val('');
				
				$("#partner").slideDown(0);
				$("#partner_label").slideDown(0);

				
				$("#isGroupSuper").slideDown(0);
				$("#isGroupSuper_label").slideDown(0);
			
				break;
			case "OPS":
				$("#isGlobalSuper").slideDown(0);
				$("#isGlobalSuper_label").slideDown(0);
				
				$("#s2id_advertiser_select").slideUp(0);//originally called 'advertiser_select' - the Select2 widget prepends it with 's2id'
				$("#advertiser_select_label").slideUp(0);
				$("#advertiser_select").val('');
				
				$("#partner").slideUp(0);
				$("#partner_label").slideUp(0);
				$("#partner").val("1");
				
				$("#isGroupSuper").slideUp(0);
				$("#isGroupSuper_label").slideUp(0);
				$("#isGroupSuper").prop("checked", false);
			
				break;
			default:
				break;
		}
	}
	
	$("#role").change(did_change_role);

	$("#advertiser_select").select2({placeholder: "Select an Advertiser"}); ///this code renames the select to 's2id_advertiser_select'


});

</script>
</head>
	
<?php
	if ($use_username) 
	{
		$username = array(
			'name'	=> 'username',
			'id'	=> 'username',
			'value' => set_value('username'),
			'maxlength'	=> $this->config->item('username_max_length', 'tank_auth')
			);
	}
	$email = array(
			'name'	=> 'email',
			'id'	=> 'email',
			'value'	=> set_value('email'),
			'maxlength'	=> 80,
			'size'	=> 30,
			);
	$password = array(
			'name'	=> 'password',
			'id'	=> 'password',
			'value' => set_value('password'),
			'maxlength'	=> $this->config->item('password_max_length', 'tank_auth'),
			'size'	=> 30,
			);
	$confirm_password = array(
			'name'	=> 'confirm_password',
			'id'	=> 'confirm_password',
			'value' => set_value('confirm_password'),
			'maxlength'	=> $this->config->item('password_max_length', 'tank_auth'),
			'size'	=> 30,
			);
	$role = array(
			'name' 	=> 'role',
			'id' 	=> 'role',
			'value' => set_value('role'),
			'maxlength' => 40,
			'size' 	=> 30,
			);
	$role_options = array(
			'ADMIN' => 'Admin',
			'BUSINESS' => 'Advertiser',
			'CREATIVE' => 'Creative',
			'OPS' => 'Ops',
			'SALES' => 'Sales',
			);
	
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
			'size' 	=> 30,
			);
	$lastname = array(
			'name' 	=> 'lastname',
			'id' 	=> 'lastname',
			'value' => set_value('lastname'),
			'maxlength' => 200,
			'size' 	=> 30,
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
	$option = "style='width: 202px'";
?>
<?php
	$business_name_option_array = array(''=>"");
	foreach($business_name_options as $value)
	{
		settype($value['id'], "int");
		$business_name_option_array[$value['id']] = $value['Name'];
	}

	foreach($partner_name_options as $value)
	{
		settype($value['id'], "int");
		$partner_name_option_array[$value['id']] = $value['partner_name'];
	}

?>

<body>
<div class="container">
	<h1>Register Here <small> way better than the DMV</small></h1>
	<form action="/register" method="post" accept-charset="utf-8" class="form-horizontal form">
		<fieldset>
			<div class="row">
				<legend > </legend>
				<div class = "span6">
					<!-- USERNAME -->
					<?php if ($use_username) { ?>
					<div class="control-group">
						<?php echo form_label('Username', $username['id'], array('class' => 'control-label')); ?>
						<div class="controls">
						<?php echo form_input($username); ?>
						<?php echo '<span class="err-box">'.form_error($username['name']).'</span>'; ?><?php echo '<span class="err-box">'.(isset($errors[$username['name']])?$errors[$username['name']]:'').'</span>'; ?>
						</div>
					</div>
					<?php } ?>
					<!-- EMAIL -->
					<div class="control-group">
						<?php echo form_label('Email Address', $email['id'], array('class' => 'control-label')); ?>
						<div class="controls">
						<?php echo form_input($email); ?><?php echo '<span class="err-box">'.form_error($email['name']).'</span>'; ?><?php echo '<span class="err-box">'.(isset($errors[$email['name']])?$errors[$email['name']]:'').'</span>'; ?>
						</div>
					</div>
					<!-- PASSWORD -->
					<div class="control-group">
						<?php echo form_label('Password', $password['id'], array('class' => 'control-label')); ?>
						<div class="controls">
						<?php echo form_password($password); ?><?php echo '<span class="err-box">'.form_error($password['name']).'</span>'; ?>
						</div>
					</div>
					<!-- CONFIRM PASSWORD -->
					<div class="control-group">
						<?php echo form_label('Confirm Password', $confirm_password['id'], array('class' => 'control-label')); ?>
						<div class="controls">
						<?php echo form_password($confirm_password); ?><?php echo '<span class="err-box">'.form_error($confirm_password['name']).'</span>'; ?>
						</div>
					</div>
					<!-- FIRST NAME -->
					<div class="control-group">
						<?php echo form_label('First Name', $firstname['id'], array('class' => 'control-label')); ?>
						<div class="controls">
						<?php echo form_input($firstname); ?><?php echo '<span class="err-box">'.form_error($firstname['name']).'</span>'; ?>
						</div>
					</div>
					<!-- LAST NAME -->
					<div class="control-group">
						<?php echo form_label('Last Name', $lastname['id'], array('class' => 'control-label')); ?>
						<div class="controls">
						<?php echo form_input($lastname); ?><?php echo '<span class="err-box">'.form_error($lastname['name']).'</span>'; ?>
						</div>
					</div>
				</div>
				<div class = "span6">
					<!-- ROLE -->
					<div class="control-group">
						<?php echo form_label('Role', $role['id'], array('class' => 'control-label')); ?>
						<div class="controls">
							<?php $dropdown_js = 'id="role"' ?>
							<?php echo form_dropdown('role',$role_options, 'BUSINESS',$option." ".$dropdown_js); ?>
							<?php echo form_checkbox('isGlobalSuper','isGlobalSuper', 0, "id='isGlobalSuper'"); ?>
							<?php echo " <span id='isGlobalSuper_label'>is super?</span>" ?>
							<?php echo '<span class="err-box">'.form_error($role['name']).'</span>'; ?>
						</div>
					</div>
					<!-- ADVERTISER NAME -->
					<div class="control-group">
						<?php echo form_label('Advertiser Name', $business_name['id'], array('class' => 'control-label','id'=> 'advertiser_select_label')); ?>
						<div class="controls">
							<?php echo form_dropdown('advertiser_name',$business_name_option_array,'',$option."id='advertiser_select'"); ?>
							
							<?php echo '<span class="err-box">'.form_error($business_name['name']).'</span>'; ?>
						</div>
					</div>
					<!-- PARTNER NAME -->
					<div class="control-group">
						<?php echo form_label('Partner', $partner['id'], array('class' => 'control-label','id'=> 'partner_label')); ?>
						<div class="controls">
							<?php echo form_dropdown('partner',$partner_name_option_array,'', $option."id='partner'"); ?>
							<?php echo form_checkbox('isGroupSuper','isGroupSuper', 0, "id='isGroupSuper'"); ?>
							<?php echo "<span id='isGroupSuper_label'>is super?</span>"; ?>
							<?php echo '<span class="err-box">'.form_error($partner['name']).'</span>'; ?>
						</div>
					</div>


					

					<div class="control-group">
						<div class="controls">
							<label class="checkbox">
								<input type="checkbox" name="planner_viewable" value="planner_viewable" id="is_planner"> <span id='is_planner_label'>Can view old planner?</span>
							</label>
						</div>
					</div>

					<div class="control-group">
						<div class="controls">
							<label class="checkbox">
								<input type="checkbox" name="placements_viewable" value="placements_viewable" id="is_placements" checked="checked"> <span id='is_planner_label'>Can view placements in reports and healthcheck?</span>
							</label>
						</div>
					</div>

					<div class="control-group">
						<div class="controls">
							<label class="checkbox">
								<input type="checkbox" name="screenshots_viewable" value="screenshots_viewable" id="is_screenshots" checked="checked"> <span id='is_screenshots_label'>Can view screenshots in reports?</span>
							</label>
						</div>
					</div>

					<div class="control-group">
						<div class="controls">
							<label class="checkbox">
								<input type="checkbox" name="engagements_viewable" value="engagements_viewable" id="is_engagements" checked="checked"> <span id='is_engagements_label'>Can view engagements in reports?</span>
							</label>
						</div>
					</div>

					<div class="control-group">
						<div class="controls">
							<label class="checkbox">
								<input type="checkbox" name="ad_sizes_viewable" value="ad_sizes_viewable" id="is_engagements" > <span id='is_engagements_label'>Can view ad sizes in reports?</span>
							</label>
						</div>
					</div>
					
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
	<td style="color: red;"><?php echo form_error('recaptcha_response_field'); ?></td>
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
	<td style="color: red;"><?php echo form_error($captcha['name']); ?></td>
  </tr>
  </table>
  <?php }
	} ?>

			<div class="row-fluid">
			<div class="span4">
				</div>
			<div class="span4 well well-large">
				<?php echo form_submit('register', 'Register', "class='btn btn-large btn-block btn-primary'"); ?>
			</div>
		</div>
		</fieldset>
	</form>


</body>

</html>



