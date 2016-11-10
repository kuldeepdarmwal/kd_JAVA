  <body class="forgot_password"> 
    <div class="container">
      <div id="logo_container">
        <img src="<?php echo $logo_path; ?>" id="logo" alt="<?php echo $partner_name; ?> Logo"/>
      </div>
      <p class="description">
      	Please enter your username or email address to reset your password.
      </p>
      <div id="form_container">
        <form action="" method="post">
          <div class="input_group username <?php echo empty($errors['login']) ? : 'error'; ?>">
	          <label for="login">Username or Email</label>
	          <input name="login" type="text" placeholder="Username or Email" required />
		  </div>
          <input name="submit" type="submit" value="Reset Password" />
        </form>
      </div>
    </div>
