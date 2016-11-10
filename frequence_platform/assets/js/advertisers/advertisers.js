(function($){

	$('input[name="sales_person"]').select2({
		width: '100%',
		placeholder: "Select a Sales User",
		minimumInputLength: 0,
		multiple: false,
		allowClear: false,
		ajax: {
			url: "/advertisers/get_allowed_advertiser_owners/",
			type: 'POST',
			dataType: 'json',
			data: function (term, page) {
				term = (typeof term === "undefined" || term == "") ? "%" : term;
				return {
					q: term,
					page_limit: 50,
					page: page
				};
			},
			results: function (data) {
				return {results: data.results, more: data.more};
			}
		},
		formatResult: function(data) {
			return data.text+' <small class="grey-text">'+data.email+'</small>';
		}
	});

	$('input[name="sales_person"]').select2('data', { id: $('input[name="sales_person"]').val(), text: $('input#sales_person_text').val() });

	$('#advertiser_edit_submit').on('click', function(e){
		e.preventDefault();

		if (validate_advertiser_edit_form()) {
			$('form#edit_advertiser').submit();
		}

		return false;
	});

	function validate_advertiser_edit_form()
	{
		var is_valid = true;
		$.each([$('input[name="advertiser_name"]'), $('input[name="sales_person"]')], function(){
			console.log($(this).val().length === 0);
			if ($(this).val().length === 0)
			{
				is_valid = false;
			}
			$(this).toggleClass('invalid', $(this).val().length === 0);
		});
		return is_valid;
	}

})(jQuery);