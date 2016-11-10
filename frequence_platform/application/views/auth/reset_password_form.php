  <body class="reset_password"> 
    <div class="container">
      <div id="logo_container">
        <img src="<?php echo $logo_path; ?>" id="logo" alt="<?php echo $partner_name; ?> Logo"/>
      </div>

      <p class="description">
      	Please enter a new password.
      </p>

      <div id="form_container">
        <form action="" method="post">
            <div class="input_group new_password <?php echo empty(form_error('new_password')) ? : 'error'; ?>">
	          <label for="login">New Password</label>
	          <input name="new_password" type="password" placeholder="New Password" required/>
                  <?php echo form_error('new_password', '<span class="form_error">', '</span>'); ?>
            </div>
            <div class="input_group confirm_new_password">
	          <label for="password">Confirm Password</label>
	          <input name="confirm_new_password" type="password" placeholder="Confirm Password" required/>
            </div>
            <input name="submit" type="submit" value="Reset Password" />
        </form>

      </div>

    </div>
