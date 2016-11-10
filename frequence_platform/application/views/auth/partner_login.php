<!doctype html>
<html class="no-js">
  <head>
    <meta charset="utf-8">
    <link rel="shortcut icon" href="<?php echo $favicon_path; ?>">
    <title><?php echo $partner_name; ?> | Login</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width">
    <!--<link href="<?php echo $css_path; ?>" rel="stylesheet" type="text/css" />-->
    <link href="/css/whitelabel/spectrum_login_style.css" rel="stylesheet" type="text/css" />
  </head>
  <body>
    <div id="header">
    </div>

    <div class="container">

      <div id="logo_container">
        <img src="<?php echo $logo_path; ?>" id="logo" />
      </div>

      <div id="callout">
        <h1>Video Media Analytics</h1>
        <h3>Get targeted. Go farther.</h3>
      </div>

      <div id="login_form">
        <form action="" method="post">
          <label for="login">Username</label>
          <input name="login" type="text" placeholder="Username" />

          <label for="password">Password</label>
          <input name="password" type="password" placeholder="Password" />

          <input name="submit" type="submit" value="Sign In" />
        </form>

        <div id="forgot_password">
          <a href="/forgot_password">Forgot your password?</a>
        </div>
      </div>

    </div>

    <script type="text/javascript" src="/libraries/external/jquery-1.10.2/jquery-1.10.2.min.js"></script>
    <script type="text/javascript" src="/libraries/external/js/jquery-placeholder/jquery.placeholder.js"></script>
    <script type="text/javascript">
      jQuery(function($){
        $('input').placeholder();
      });
    </script>
  </body>
</html>
