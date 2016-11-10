Hi<?php if (strlen($username) > 0) { ?> <?php echo $username; ?><?php } ?>,

Forgot your password, huh? No big deal.
We've made a new one for you: <?php echo $new_password ?>

<?php echo $login_url; ?>


You received this email, because it was requested by a <?php echo $partner_name; ?> user. This is part of the procedure to create a new password on the system. If you DID NOT request a new password then please ignore this email and your password will remain the same.


Thank you,
The <?php echo $partner_name; ?> Team