<body class="login"> 
    <div class="container">
      <div id="logo_container">
        <img src="<?php echo $logo_path; ?>" id="logo" alt="<?php echo $partner_name; ?> Logo"/>
      </div>

      <div id="form_container">
        <form action="" method="post">
          <span class="hide error_message"><?php echo $error_message; ?></span>
          <div class="input_group username <?php echo empty($errors['login']) ? : 'error'; ?>">
	          <label for="login">Username or Email</label>
	          <input name="login" id="login" type="text" placeholder="Username or Email" required value="<?php echo empty($errors['login']) ? $username : ''; ?>"/>
		      </div>

          <div class="input_group password <?php echo empty($errors['password']) ? : 'error'; ?>">
	          <label for="password">Password</label>
	          <input name="password" id="password" type="password" placeholder="Password" required/>
		      </div>
			<input type="hidden" name="referer" value="<?php echo $referer; ?>" />
          <input name="submit" id="submit" type="submit" value="Sign In" />
        </form>

        <div id="forgot_password">
          <a href="/forgot_password">Forgot your password?</a>
        </div>
      </div>

    </div>
