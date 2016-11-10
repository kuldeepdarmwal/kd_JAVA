Hi <?php echo $firstname;?> <?php echo $lastname;?> (Login Email: <?php echo $email; ?>),    

Forgot your password, huh? No big deal. Copy the following link to your browser address bar:

<?php echo 'http://' . $partner_url . '/reset_password/'.$user_id.'/'.$new_pass_key; ?>

You are receiving this email because a password reset request was sent on your behalf. This is part of the procedure to create a new password on the system. If you do not wish for your password to be reset, then please ignore this email and your password will remain the same.

Thank you,
The <?php echo $partner_name; ?> Team