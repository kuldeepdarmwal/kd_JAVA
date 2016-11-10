<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
<script src="/libraries/external/select2/select2.js"></script>
<script type="text/javascript">
	$(document).ready(function()
	{
		var business_dropdown_value = false;

		<?php if (!$is_sales_account) { ?>
			
			function did_change_role() {
				var value = $("#role").val();
				business_dropdown_value = !business_dropdown_value ? $("#advertiser_select").val() : business_dropdown_value;

				switch(value)
				{
					case "ADMIN":
						$("#s2id_advertiser_select").slideUp(0);//originally called 'advertiser_select' - the Select2 widget prepends it with 's2id'
						$("#advertiser_select_label").slideUp(0);
						$("#advertiser_select_container").hide();
						$("#advertiser_select").val('');
						
						
						$("#partner").slideUp(0);
						$("#partner_label").slideUp(0);
						$("#partner_container").hide();
						$("#partner").val("1");
						
						$("#isGroupSuper").slideUp(0);
						$("#isGroupSuper_label").slideUp(0);

						$("#sales_contact_info").slideUp(0);
						
						break;
					case "BUSINESS":
						$("#advertiser_select_container").show();
						$("#s2id_advertiser_select").slideDown(0);//originally called 'advertiser_select' - the Select2 widget prepends it with 's2id'
						$("#advertiser_select_label").slideDown(0);

						
						$("#partner").slideUp(0);
						$("#partner_label").slideUp(0);
						$("#partner").val("1");
						$("#partner_container").hide();
						
						$("#isGroupSuper").slideUp(0);
						$("#isGroupSuper_label").slideUp(0);
						$("#isGroupSuper").prop("checked", false);

						$("#sales_contact_info").slideUp(0);

						if(business_dropdown_value)
						{
							$("#advertiser_select").val(business_dropdown_value);
						}
						
						break;
					case "CREATIVE":
						$("#s2id_advertiser_select").slideUp(0);//originally called 'advertiser_select' - the Select2 widget prepends it with 's2id'
						$("#advertiser_select_label").slideUp(0);
						$("#advertiser_select_container").hide();
						$("#advertiser_select").val('');
						
						$("#partner").slideUp(0);
						$("#partner_label").slideUp(0);
						$("#partner").val("1");
						$("#partner_container").hide();
						
						$("#isGroupSuper").slideUp(0);
						$("#isGroupSuper_label").slideUp(0);
						$("#isGroupSuper").prop("checked", false);

						$("#sales_contact_info").slideUp(0);
					
						break;
					case "SALES":
						$("#s2id_advertiser_select").slideUp(0);//originally called 'advertiser_select' - the Select2 widget prepends it with 's2id'
						$("#advertiser_select_label").slideUp(0);
						$("#advertiser_select_container").hide();
						$("#advertiser_select").val('');
						
						$("#partner").slideDown(0);
						$("#partner_label").slideDown(0);
						$("#partner_container").show();

						
						$("#isGroupSuper").slideDown(0);
						$("#isGroupSuper_label").slideDown(0);
						$("#sales_contact_info").slideDown(0);
					
						break;
					case "OPS":
						$("#s2id_advertiser_select").slideUp(0);//originally called 'advertiser_select' - the Select2 widget prepends it with 's2id'
						$("#advertiser_select_label").slideUp(0);
						$("#advertiser_select").val('');
						$("#advertiser_select_container").hide();
						
						$("#partner").slideUp(0);
						$("#partner_label").slideUp(0);
						$("#partner").val("1");
						$("#partner_container").hide();
						
						$("#isGroupSuper").slideUp(0);
						$("#isGroupSuper_label").slideUp(0);
						$("#isGroupSuper").prop("checked", false);

						$("#sales_contact_info").slideUp(0);
					
						break;
					default:
						break;
				}
			}

			$("#role").change(did_change_role);			
		<?php } ?>

		function clear_all_err_boxes(){
			$(".err-box").slideUp(0); 
		}

		function advertiser_dropdown_format(advertiser){
			var markup = '<small style="font-size:8pt;">' + advertiser.partner + '</small> <b>' + advertiser.advertiserElem + '</b>';
			return markup;
		}

		function advertiser_selected_format(advertiser){
			var markup = '<small style="font-size:8pt;">' + advertiser.partner + '</small> <b>' + advertiser.advertiserElem + '</b>';
			return markup;
		}

		$('#email').bind('input change blur', function(){
			$('#email_username').text($(this).val());
		});

		$('#password').bind('input change blur', function(){
			$('#email_password').text($(this).val());
		});

		$('#firstname').bind('input change blur', function(){
			$('#email_first_name').text($(this).val());
		});

		$('#lastname').bind('input change blur', function(){
			$('#email_last_name').text($(this).val());
		});

		$('#advertiser_select').select2({
			placeholder: "Select an advertiser",
			minimumInputLength: 0,
			width: '450',
			formatSelection: function(obj)
			{
				return obj.text;
			},
			ajax: {
				url: "/report_legacy/ajax_get_advertisers",
				type: 'POST',
				dataType: 'json',
				data: function (term, page) {
					term = (typeof term === "undefined" || term == "") ? "%" : term;
					return {
						q: term,
						page_limit: 100,
						page: page
					};
				},
				results: function (data) {
					return {results: data.results, more: data.more};
				}
			},
			allowClear: false
		});	
		$("#advertiser_select").prop("selectedIndex", 0);

		$("#partner_select").select2({
			placeholder: "Select a Partner",
			width: '<?php echo $width; ?>',
			data: <?php echo $partner_name_option_array; ?>
		}); ///this code renames the select to 's2id_advertiser_select'

		var cname_ajax = false;
		$("#advertiser_select").change(function(){
			if(cname_ajax) cname_ajax.abort();
			if($(this).val() > 0)
			{
				get_cname_by_ajax('BUSINESS', $(this).val());
			}
		});

		$("#partner_select").change(function(){
			if(cname_ajax) cname_ajax.abort();
			if($(this).val() > 0)
			{
				get_cname_by_ajax('SALES', $(this).val());
			}
		});

		function get_cname_by_ajax(role, id)
		{
			if(cname_ajax) cname_ajax.abort();
			cname_ajax = $.ajax({
				url: '/auth/get_cname_for_user_by_ajax',
				dataType: 'json',
				async: true,
				type: "POST", 
				data: {
					role : role,
					role_id : id
				},
				success: function(data) 
				{
					if(data.is_success) 
					{					
						$('.email_cname').text(data.cname);
					}
					else
					{
						$('.email_cname').text('{Partner Name}');
					}
					cname_ajax = false;
				},
				error: function(xhr, textStatus, error) 
				{
					cname_ajax = false;
					console.log(error);
				}
			});
		}

		$('#role').change(function(){
			var role = $(this).val()
			if(role != 'SALES' && role != 'BUSINESS')
			{
				$('.email_cname').text('secure');
			}
			else
			{
				var role_id = (role == 'SALES') ? $("#partner_select").val() : $("#advertiser_select").val();
				get_cname_by_ajax(role, role_id);
			}
		});

	});

</script>