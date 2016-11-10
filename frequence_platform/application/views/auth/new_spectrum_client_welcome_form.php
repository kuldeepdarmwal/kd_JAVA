  <body> 
    <div class="container" style='height:700px'>
      <div id="logo_container">
        <img src="<?php echo $logo_path; ?>" id="logo" alt="<?php echo $partner_name; ?> Logo"/>
      </div>
       <div class='row'>
      <?php if (!$success_flag && $email != "")
     {
     ?>
      <p class="description">
         <p>
          <h2>
            Welcome
          </h2>
        </p>
        
        <p>
          You've been invited to view <?php echo $partner_url; ?> marketing dashboard.<br> 
        </p>
      </p>
       
      
        <div class='row'>
        <p>
          Please create your login credentials to join:
        </p>
      </div>
        <div id="form_container" style='float:left'>
          <form action="" method="post" name='client_invite_form'>
            
            <div class="input_group email">
              <label for="login">Email</label>
              <input name="email" type="text" value='<?php echo $email; ?>' READONLY  />
            </div>

            <div class="input_group first_name">
              <label for="login">First Name</label>
              <input name="first_name" id='first_name' type="text" placeholder="First Name" value="<?php echo $first_name ?>" required/>
            </div>

            <div class="input_group last_name">
              <label for="login">Last Name</label>
              <input name="last_name" id='last_name' type="text" placeholder="Last Name" value="<?php echo $last_name ?>" required/>
            </div>

            <div class="input_group new_password">
              <label for="login">Password</label>
              <input id='new_password' name="new_password" type="password" placeholder="Password" required/>
            </div>

            <div class="input_group confirm_new_password">
              <label for="password">Confirm Password</label>
              <input id='confirm_new_password' name="confirm_new_password" type="password" placeholder="Confirm Password" required/>
            </div>
            <input name="button" type="button" value="CREATE" onclick='return validate()'/>
           </form>
        <?php
        }
        ?>
        <div class='description'><b>
          <span id='error_div'>
        <?php echo validation_errors(); ?>
        <?php echo $message; ?>
       </b>
     </span>
      </div>
    </div>
    </div>
  </body> 

  <script type="text/javascript">
function validate()
{

  document.getElementById('error_div').innerHTML="";

  if ($('input[name="first_name"]').val()  == 'First Name' || $('input[name="first_name"]').val()  == '')
  {
    document.getElementById('error_div').innerHTML += "First Name required<br>";
  }
  if ($('input[name="last_name"]').val()  == 'Last Name' || $('input[name="last_name"]').val()  == '')          
  {
    document.getElementById('error_div').innerHTML += "Last Name required<br>";
  }
  if ($('input[name="new_password"]').val().length < 4)
  {
    document.getElementById('error_div').innerHTML += "Password must be at least 4 characters in length<br>";
  }
  else if ($('input[name="new_password"]').val() != $('input[name="confirm_new_password"]').val())
  {
    document.getElementById('error_div').innerHTML += "Passwords don't match<br>";
  }

  if (document.getElementById('error_div').innerHTML.length == 0)
  {
    document.forms['client_invite_form'].submit();
    return true;
  }

}
  
  </script>