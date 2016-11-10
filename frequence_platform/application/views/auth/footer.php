    <script type="text/javascript" src="/libraries/external/jquery-1.10.2/jquery-1.10.2.min.js"></script>
    <script type="text/javascript" src="/libraries/external/js/jquery-placeholder/jquery.placeholder.js"></script>
    <script type="text/javascript">
        jQuery(function($){
            $('input').placeholder();
            
            // Reset password form validation
            $(document).on('submit', '.reset_password form', function(e){
        	$('.new_password').removeClass("error");
                $(".form_error").remove();
                if ($('input[name="new_password"]').val() !== $('input[name="confirm_new_password"]').val()){
                    $('.input_group.confirm_new_password').addClass('error');
                    return false;
        	}
        	else
        	{
                    return true;
 	       	}
            });
        });
    </script>
  </body>
</html>