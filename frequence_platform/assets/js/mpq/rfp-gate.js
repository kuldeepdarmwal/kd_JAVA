$('#account_executive_select, #industry_select').on("change", function(e) {
	var update = get_filtered_strategy_info().done(function(data){
		if (data.strategies)
		{
			populate_strategies(data.strategies);
		}
		validate_rfp_gate();
	});
});

$('#mpq_org_name, #mpq_website_name, input[name="strategy_id"]').on('change', function(e) {
	validate_rfp_gate();
});

$(document).on('click', '#strategies .strategy:not(.selected)', function(e){
	e.preventDefault;
	$('#strategies .strategy.selected').removeClass('selected');
	$(this).addClass('selected');
	$('input[name="strategy_id"]').val($(this).data('strategy-id'));

	var update = get_filtered_strategy_info().done(function(data){
		if (data.strategies)
		{
			populate_strategies(data.strategies);
		}
		validate_rfp_gate();
	});
});

function get_filtered_strategy_info()
{
	return $.ajax({
		url: '/mpq_v2/get_filtered_strategy_info',
		method: 'POST',
		dataType: 'json',
		data: get_strategy_selection_data(),
		error: function() {
			Materialize.toast('Ooops! Something went wrong. Please refresh the pag and try again.', 10000, 'toast_top');
		}
	});
}

function get_strategy_selection_data()
{
	return {
		owner_id: $('#account_executive_select').select2('data').id,
		industry_id: $('#industry_select').val(),
		strategy_id: $('input[name="strategy_id"]').val()
	};
}

function populate_strategies(strategies)
{
	var current_strategy = $('input[name="strategy_id"]').val();

	$('#strategies').empty();
	$('input[name="strategy_id"]').val('');
	var is_single = strategies.length === 1;
	$('.select-notice').toggle(!is_single);
	strategies.map(function(strategy){
		var selected = (strategy.id == current_strategy) || is_single;
		strategy.selected = selected ? "selected" : "";
        if (is_single)
        {
            strategy.selected += " single";
        }

		if (selected) {
			$('input[name="strategy_id"]').val(strategy.id);
		}

		strategy.products.map(function(product) {
			product.included = product.product_type !== 'discount' && !product.definition.after_discount; // don't include discounts or setup fees
		});

		$('#strategies').append(Mustache.render(strategyTemplate, strategy));
	});
}

function validate_rfp_gate()
{
	$('#rfp_gate_submit').toggleClass('disabled', (
		$('#account_executive_select').select2('data') === null ||
		$('#mpq_org_name').val() === '' ||
		$('#mpq_website_name').val() === '' ||
		$('#industry_select').select2('data') === null ||
		$('input[name="strategy_id"]').val() === ''
	));
}

var strategyTemplate = 	''+
	'<div class="strategy card {{selected}}" data-strategy-id="{{id}}">'+
	'	<div class="title">'+
	'		<i class="strategy_checked icon-ok"></i>'+
	'		<h4>{{name}}</h4>'+
	'	</div>'+
	'		<img class="preview col s4" src="{{preview_image}}" />'+
	'		<p class="description col s4">{{description}}</p>'+
	'		<ul class="products col s4">'+
	'			{{#products}}{{#included}}<li><img src="{{definition.proposal_icon}}" /></li>{{/included}}{{/products}}'+
	'		</ul>'+
	'</div>';

jQuery(function($){
	var update = get_filtered_strategy_info().done(function(data){
		if (data.strategies)
		{
			populate_strategies(data.strategies);
		}
		validate_rfp_gate();
	});
});