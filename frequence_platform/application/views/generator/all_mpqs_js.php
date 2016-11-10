<script type="text/javascript">
	jQuery(function(){
		$('[data-toggle="tooltip"]').tooltip();

		$('.geos_button').hoverIntent({
			over: function(e)
			{
				$(this).find('div.geos_download').first().show(100);
			},
			out: function(e)
			{
				$(this).find('div.geos_download').first().hide(200);
			},
			timeout: 200
		});

		$('.geos_rfp_button').hoverIntent({
			over: function(e)
			{
				$(this).find('div.geos_rfp_download').first().show(100);
			},
			out: function(e)
			{
				$(this).find('div.geos_rfp_download').first().hide(200);
			},
			timeout: 200
		});

		$('a.disabled, a.disabled *').prop('disabled', true);

		$('#advertiser_select').select2({});
		$.ajax({
			type: "POST",
			url: "/proposal_builder/get_vl_advertisers_dropdown/",
			async: false,
			data: { },
			dataType: 'html',
			error: function(){
				alert("Could not retrieve list of advertisers")
			},
			success: function(msg){ 
				$("#advertiser_select").html(msg);
				$('#advertiser_select').select2({
				});
			}
		});

		$(document).on('change', 'select.campaign_select', function(e){
			e.preventDefault();
			if ($(this).val() == "create_new")
			{
				open_campaign_modal($(this).attr('data-io-id'));
			}
			else
			{
				window.open($(this).val());
			}
			$(this).val('');
		});

		$(document).on('click', 'a.create-campaign', function(e){
			e.preventDefault();
			open_campaign_modal($(this).attr('data-io-id'));
		});

		function open_campaign_modal(insertion_order_id) {
			$('#campaign_modal').modal('show');
			$('#campaign_modal input').val('');
			$('#advertiser_select').select2('data', null);
			$('#campaign_modal input[name="insertion_order_id"]').val(insertion_order_id);
		}

		$(document).on('submit', '#campaign_modal form#campaign_submit', function(e) {
			e.preventDefault();
			e.stopImmediatePropagation();

			if ($('#campaign_submit input[name="campaign_name"]').val() == '' || $('#campaign_submit select[name="business_id"]').val() == '')
			{
				alert("'Campaign Name' and 'Advertiser' must be filled in.");
				return false;
			}

			var insertion_order_id = $('#campaign_modal input[name="insertion_order_id"]').val();

			$.ajax({
				url: "/campaign_setup/create_new_campaign_from_insertion_order",
				method: "POST",
				dataType: 'json',
				data: {
					insertion_order_id: insertion_order_id,
					campaign_name: $('#campaign_submit input[name="campaign_name"]').val(),
					business_id: $('#campaign_submit select[name="business_id"]').val()
				},
				success: function(data) {
					window.open('/campaign_setup/' + data.id);
        			$('#campaign_modal').modal('hide');
					
					if ($('#mpq_list_table a.btn[data-io-id="'+insertion_order_id+'"]').length)
					{
						$('#mpq_list_table [data-io-id="'+insertion_order_id+'"]').parent().html('<select class="campaign_select" data-io-id="'+ insertion_order_id +'"><option></option><option value="create_new">Create New Campaign</option><option value="/campaign_setup/'+ data.id +'">'+ data.name +'</option></select>');
					}
					else if ($('#mpq_list_table select[data-io-id="'+insertion_order_id+'"]').length)
					{
						$('#mpq_list_table [data-io-id="'+insertion_order_id+'"]').append($('<option>', {value: '/campaign_setup/' + data.id, text: data.name}));
					}
				},
				error: function(jqXHR, error, status) {
					alert(jqXHR.responseText);
				}
			});

		});
	});
</script>
