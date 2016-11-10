
$('.budget_option_title').blur(function(){
    var element = $(this);
    element.hide();
    var sibling = element.siblings('.budget_option_text_container');
    sibling.find('.budget_option_text').text(element.val());
    sibling.show();
    smart_option_naming = false;
});

$('.budget_option_text').dblclick(function(){
    $(this).parent().hide();
    var sibling = $(this).parent().siblings('.budget_option_title');
    sibling.show();
    sibling.focus();
    sibling[0].select();
});

$('#discount_display_input').focus(function(){
    this.select();
});

$('#discount_display_input').blur(function(){
    var element = $(this);
    var display_text = element.val();
    if(element.val() == "")
    {
        display_text = "Discount";
    }
    $('#discount_display_text').text(display_text);
});

$('.product_display_input').focus(function(){
    this.select();
});

$('.product_display_input').change(function(){
    var element = $(this);
    var product_id = element.attr('data-product-id');
    var display_text = element.val();
    if(element.val() == "" && typeof element.attr('data-original-value') !== "undefined")
    {
        display_text = element.attr('data-original-value');
    }
    $('#product_display_text_'+product_id).text(display_text);
});

var slider_container_id = "rfp_budget_slider_container_col";
var slider_id = "budget_slider";
var budget_multiplier_class = "budget_slider_multiplier";
var impression_dollars_dropdown_class = "io_period_dropdown";

function Slider(range_min, range_max)
{
    this.initialize(range_min, range_max);
}

(function() {
    var range_min;
    var range_max;

    var on_input_to_remove_is_set = true;

    this.initialize = function(new_range_min, new_range_max)
    {
        range_min = new_range_min;
        range_max = new_range_max;

        $("." + impression_dollars_dropdown_class).on('change', delete_slider);
    }

    this.set_values_and_calculate = function(value)
    {
        var multiplier = get_multiplier(value);
        $("." + budget_multiplier_class).each(function(){
            var current_value = $(this).data('default-value');
            current_value = Math.round((current_value * multiplier) / 100) * 100;
            $(this).val(current_value);
            $(this).trigger('change');
        });
        var slider_dropdown_value = Math.max(value-25, 1);

        $(".budget_multiplier_discrete_unit_dropdown:not(div)").each(function(key, value){
            var dropdown_length = $(this).children("option").length;
            var dropdown_multiplier = 75/dropdown_length;
            var selected_option = Math.ceil(slider_dropdown_value/dropdown_multiplier);
            $(this).children("option").eq(selected_option-1).prop('selected', true);
            $(this).material_select();
            cost_per_discrete_unit_calc($(this).data('optionId'), $(this).data('productId'), false, false);
        });
    }

    this.turn_on_slider_remove_function = function()
    {
        if(!on_input_to_remove_is_set)
        {
            $(".product_input, .no_option_product_input").on('input', delete_slider);
            on_input_to_remove_is_set = true;
        }
    }

    this.turn_off_slider_remove_function = function()
    {
        if(on_input_to_remove_is_set)
        {
            $(".product_input, .no_option_product_input").off('input', delete_slider);
            on_input_to_remove_is_set = false;
        }
    }

    this.initialize_slider_removal = function()
    {
        $(".product_input, .no_option_product_input").on('input', delete_slider);
        on_input_to_remove_is_set = true;
    }

    function get_multiplier(position)
    {
        return (Math.pow(range_max, (0.01 * (position - 50))));
    }

    function delete_slider()
    {
        $("#" + slider_container_id).remove();
    }

}).call(Slider.prototype);

function init_rooftops_data()
{
    $('#rooftops_multiselect').placecomplete({
        placeholder: "Select your physical locations...",
        minimumInputLength: 3,
        multiple: true,
        allowClear: true,
        requestParams: {
            types: ["geocode", "establishment"],
            componentRestrictions: {country : "us"}
        }
    });

    $('#rooftops_multiselect').on('change', function(e) {
        recalculate_totals();
    });
}

function init_tvzones_data()
{
    $('#tvzones_multiselect').select2({
        placeholder: "Start typing to find zones...",
        multiple: true,
        ajax: {
            url: "/mpq_v2/get_tv_zones/",
            type: 'POST',
            dataType: 'json',
            data: function (term, page) {
                term = (typeof term === "undefined" || term == "") ? "%" : term;
                return {
                    q: term,
                    page_limit: 50,
                    page: page,
                    mpq_id: mpq_id
                };
            },
            results: function (data) {
                return {results: data.result, more: data.more};
            }
        },
        allowClear: true
    });

    $('#tvzones_multiselect').on('change', function(e) {
        updateTVPricingByZones();
    });
}

function set_end_of_content_editable(element)
{
    var range
	var selection;
    if(document.createRange)
    {
        range = document.createRange();
        range.selectNodeContents(element);
        range.collapse(false);
        selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
    }
}

function create_keywords_contents(object, text_array)
{
	if(object !== false)
	{
		$('.keywords_remove_pill').remove();
		$('.keywords_placeholder_text').remove();
		var temporary_table_array = [];
		$('#keywords_tags > table td').each(function(key, value) {
			temporary_table_array.push($(value).text());
		});
		$('#keywords_tags > table').remove();

		$('#keywords_tags > div:not(.keywords_pill):not(.keywords_last_element)').each(function(key, value) {
			var temp_text = $(value).text();
			$.each(temp_text.split(/[,|\n|\t]/), function(split_key, split_value) {
				temporary_table_array.push(split_value);
			});
		});
		$('#keywords_tags > div:not(.keywords_pill):not(.keywords_last_element').remove();
		var text = $(object).text();
		$("#keywords_tags").empty();
		var text_array = text.split(/[,|\n|\t]/);
		$.merge(text_array, temporary_table_array);
		text_array = $.grep(text_array, function(value) {
			return $.trim(value) != "";
		});

	}

	var pill_contents = "";
	$.each(text_array, function(key, value) {
		pill_contents += '<div contentEditable="false" class="keywords_pill"><span class="keywords_remove_pill" contentEditable="false">×</span><span class="keywords_search_term" contentEditable="false">\t'+value+'\t</span></div>\t';
	});
	if(pill_contents == "")
	{
		pill_contents += '<div class="keywords_placeholder_text" contentEditable="false">Paste Keywords Here...</div>';
	}
	pill_contents += '<div class="keywords_last_element" contentEditable="true"> </div>';

	$('#keywords_tags').html(pill_contents);
}

function init_keywords_data()
{
	$('#keywords_tags').on('blur', function() {
		create_keywords_contents(this, false);
	}).on('keydown', function(e) {
		if(e.keyCode == 13 || (e.keyCode == 188 && e.shiftKey == false))
		{
			create_keywords_contents(this, false);
			set_end_of_content_editable(document.getElementById('keywords_tags'));
		}
	}).on('focus', function() {
		$('.keywords_placeholder_text').remove();
	});

	$('#keywords_tags').bind('paste', function(e){
		window.setTimeout(function(){
			$('#keywords_tags').blur();
		}, 30);
		return;		
	});
	
	$(document).on('click', '.keywords_remove_pill', function(e){
		var parent = $(this).parent();
		parent.remove();
	});


	$('#keywords_clicks_amount').change(function(e) {
		var object = this;
		var keywords_clicks_value = $(object).val().replace(/[^0-9.]/g, "");
		if(keywords_clicks_value == "")
		{
			keywords_clicks_value = 0;
		}
		$('.keywords_clicks_dependent').each(function(key, value) {
			var clicks_object_val = $(value).val().replace(/[^0-9.]/g, "");
			if(clicks_object_val === "" || ($.isNumeric(keywords_clicks_value) && $.isNumeric(clicks_object_val) && clicks_object_val > keywords_clicks_value))
			{
				$(value).addClass('invalid');
			}
			else
			{
				$(value).removeClass('invalid');
			}
		});
	});
}

function updateTVPricingByZones(packs, updateElement){
    var tvZones = $('#tvzones_multiselect').select2('data');
    var zoneIds = _.pluck(tvZones, 'id');
    var packs  = !packs ? getAllSelectedPacks() : packs;

    for (var i = 0; i < packs.length; i++) {
        if (packs[i] === "custom") packs[i] = $('input[name="custom_pack_type"]').val();
    }

    if(zoneIds.length != 0 && packs.length != 0){
        $.ajax({
            type: "POST",
            url: '/mpq_v2/get_prices_by_zones_and_packs_for_tv_request',
            async: true,
            data:
            {
                zones : zoneIds,
                packs : packs
            },
            dataType: 'json',
            success: function(response, textStatus, jqXHR){
                if(response.is_success) {
                    updatePricesForTv(response.data, updateElement);
                    recalculate_totals();
                } else{
                    Materialize.toast('Ooops!'+ response.errors, 16000, 'toast_top');
                }
            },
            error: function(jqXHR, textStatus, error){
                Materialize.toast('Ooops! We had a problem retrieving pricing for zones. Please try after some time.', 16000, 'toast_top');
                send_debug_email(error.toString());
            }
        });
    }
    else{
        updatePricesForTv([]);
    }
}

function updatePricesForTv(pricingObj, updateElement){

    if(!updateElement){
        $(".product_budget_tv_zone_dependent").each(function(){
            var _self = $(this);
            var selectedPack = $("#unit_option_"+_self.data('option-id')+"_"+_self.data('product-id')).val();
            if (selectedPack === "custom") {
                custom_tv_package_calc(_self.data('option-id'), _self.data("product-id"), $('input[name="custom_tv_package_'+_self.data('option-id')+'_price'));
            } else {
                var price = (pricingObj.length != 0 ? _.findWhere(pricingObj, {pack_name : selectedPack}).price : 0);
                _self.html('$'+number_with_commas(price));
            }
        });
    }else{
        var selectedPack = updateElement.val();
        if (selectedPack === "custom") {
            custom_tv_package_calc(updateElement.data('option-id'), updateElement.data("product-id"), $('input[name="custom_tv_package_'+updateElement.data('option-id')+'_price'));
        } else {
            var price = _.findWhere(pricingObj, {pack_name : selectedPack}).price;
            $("#product_subtotal_option_"+updateElement.data('option-id')+"_"+updateElement.data("product-id")).html('$'+number_with_commas(price));
        }
    }
}

function getAllSelectedPacks(){
    var selectedPacks = [];
    $(".product_budget_tv_zone_dependent ").each(function(){
        var _self = $(this);
        var selectedPack = $("#unit_option_"+_self.data('option-id')+"_"+_self.data('product-id')).val();
        selectedPacks.push(selectedPack);
    });
    return _.uniq(selectedPacks);
}

function encode_rooftops_data()
{
    return JSON.stringify($('#rooftops_multiselect').select2('data'));
}

function encode_tv_zones_data()
{
    return $('#tvzones_multiselect').select2('data');
}

function encode_keywords_data()
{
    var search_terms = [];
    $('#keywords_tags .keywords_search_term').each(function(key, value) {
        search_terms.push($.trim($(value).text()));
    });
    return {
        clicks: $('#keywords_clicks_amount').val().replace(/[^0-9.]/g, ""),
        search_terms: search_terms
    };
}

$(document).ready(function(){

    $('.duration_term_dropdown, .duration_dropdown').material_select();

    init_rooftops_data();
    init_tvzones_data();
    init_keywords_data();


    if(rfp_is_preload == 1)
    {
        if(!$.isEmptyObject(rfp_options_data))
        {
            preload_options_data(rfp_options_data);
        }

        $("#" + slider_container_id).hide();

        if(!$.isEmptyObject(rfp_rooftops_data))
        {
            $('#rooftops_multiselect').select2('data', rfp_rooftops_data);
        }

        if(!$.isEmptyObject(rfp_tv_zones_data))
        {
            $('#tvzones_multiselect').select2('data', rfp_tv_zones_data);
        }

		if(!$.isEmptyObject(rfp_keywords_data))
		{
			create_keywords_contents(false, rfp_keywords_data);
		}
		$('#keywords_clicks_amount').val(rfp_keywords_clicks);
		

        disableIfOneDigitalProductSelected();
        updateTVPricingByZones();
        recalculate_totals();
    }
    else
    {
        var slider = new Slider(25, 100);

        $("#" + slider_id).rangeslider({
            polyfill: false,
            onInit: function()
            {
                slider.initialize_slider_removal();
                slider.set_values_and_calculate(50);
            },
            onSlideEnd: function(){
                slider.turn_on_slider_remove_function();
            }
        });

        $("#" + slider_id).on("input", function(){
            slider.turn_off_slider_remove_function();
            slider.set_values_and_calculate($(this).data('plugin_rangeslider').value);
        });
    }
});

$('#mpq_product_info_section img.targeted_image').click(function(e){
    var img = $(this);
    var checkbox = img.parent().siblings('input[type=checkbox]');
    var _self = $(checkbox);
    var checkbox_checked = true;

    if(!checkbox.prop("disabled")){
        if($(checkbox).prop('checked') == true)
        {
            checkbox_checked = false;
        }
        $(checkbox).prop('checked', checkbox_checked);
        if(checkIfDisplayOrPreRollSelected(_self)){
            var checkedProduct = _self.siblings(".product-display-text").text().replace(/\r?\n|\r/, '');
            if(checkedProduct == productConstants.DISPLAY){enableCheckBox(productConstants.PREROLL)}else if(checkedProduct == productConstants.PREROLL){enableCheckBox(productConstants.DISPLAY)}
        }else{
            var checkedProduct = _self.siblings(".product-display-text").text().replace(/\r?\n|\r/, '');
            if(checkedProduct == productConstants.DISPLAY){disableCheckBox(productConstants.PREROLL)}else if(checkedProduct == productConstants.PREROLL){disableCheckBox(productConstants.DISPLAY)}
        }

        $('#'+$(checkbox).attr('data-budget-section')).toggle($(checkbox).prop('checked'));
        img.attr('src', img.attr($(checkbox).prop('checked') ? 'data-enabled-img' : 'data-disabled-img'));
        recalculate_totals();
    }
});


$('#mpq_product_info_section input[type=checkbox]').click(function(e){
    var checkbox = e.delegateTarget;
    var _self =$(this)

    var img = $(checkbox).siblings(".product_header_img").find('img');

    if(checkIfDisplayOrPreRollSelected(_self)){
        var checkedProduct = _self.siblings(".product-display-text").text().replace(/\r?\n|\r/, '');
        if(checkedProduct == productConstants.DISPLAY){enableCheckBox(productConstants.PREROLL)}else if(checkedProduct == productConstants.PREROLL){enableCheckBox(productConstants.DISPLAY)}
    }else{
        var checkedProduct = _self.siblings(".product-display-text").text().replace(/\r?\n|\r/, '');
        if(checkedProduct == productConstants.DISPLAY){disableCheckBox(productConstants.PREROLL)}else if(checkedProduct == productConstants.PREROLL){disableCheckBox(productConstants.DISPLAY)}
    }

    $('#'+$(checkbox).attr('data-budget-section')).toggle(checkbox.checked);
    img.attr('src', img.attr(checkbox.checked ? 'data-enabled-img' : 'data-disabled-img'));
    recalculate_totals();
});

$('.budget_cost_button').click(function(e){
    var button = e.delegateTarget;
    var row = $(button).siblings("div.row");
    if(row.is(":visible"))
    {
        row.slideUp(400);
    }
    else
    {
        row.slideDown(400);
    }
});

function format_discount_and_calc(object)
{
    var value = $(object).val().replace(/[^0-9.]/g, "");
    if(value == "")
    {
        value = 0;
    }
    else
    {
        value = Math.round(value);
        if(value > 100)
        {
            value = 100;
        }
        else if(value < 0)
        {
            value = 0;
        }
    }

    $(object).val(value);
    recalculate_totals();
}


function cost_per_unit_calc(option_id, product_id, object)
{
    var unit_multiplier = 1000;
    if(typeof $('#unit_multiplier_'+option_id+'_'+product_id) !== "undefined")
    {
        unit_multiplier = $('#unit_multiplier_'+option_id+'_'+product_id).val();
    }

    var type = $('#type_option_'+option_id+'_'+product_id).val();
    var unit_object = $('#unit_option_'+option_id+'_'+product_id);
    var unit = unit_object.val().replace(/[^0-9.]/g, "");
    var cpm = $('#cpm_option_'+option_id+'_'+product_id).val().replace(/[^0-9.]/g, "");

    if($.trim(unit) == "")
    {
        unit = 0;
        $('#unit_option_'+option_id+'_'+product_id).val(unit);
    }
    if($.trim(cpm) == "")
    {
        cpm = 0;
        $('#cpm_option_'+option_id+'_'+product_id).val(cpm);
    }

    if(object !== false)
    {
        object.value = number_with_commas(object.value.replace(/[^0-9.]/g, ""));
    }
    else
    {
        if(unit_object.data("previousCalcValue") !== type)
        {
            if(type == 'impressions')
            {
                unit_object.data("previousCalcValue", "impressions");
                if(cpm == 0)
                {
                    unit = 0;
                }
                else
                {
                    unit = Math.round(unit*unit_multiplier/cpm);
                }
            }
            else if(type == 'dollars')
            {
                unit_object.data("previousCalcValue", "dollars");
                unit = Math.round(cpm*unit/unit_multiplier);
            }
        }
        $('#unit_option_'+option_id+'_'+product_id).val(number_with_commas((unit+"").replace(/,/gi, "")));
    }

    var type = $('#type_option_'+option_id+'_'+product_id).val();

    var unit = $('#unit_option_'+option_id+'_'+product_id).val().replace(/[^0-9.]/g, "");
    var cpm = $('#cpm_option_'+option_id+'_'+product_id).val().replace(/[^0-9.]/g, "");
    var sub_total = 0;

    if(type == 'dollars')
    {
        if(cpm == 0)
        {
            sub_total = 0;
        }
        else
        {

            sub_total = Math.round(unit);
        }
    }
    else if(type == 'impressions')
    {
        sub_total = Math.round(cpm * unit / unit_multiplier);
    }

    $('#product_subtotal_option_'+option_id+'_'+product_id).html('$'+number_with_commas(sub_total));
    $('#product_subtotal_option_'+option_id+'_'+product_id).data('base-price', sub_total);


    recalculate_totals();
}

function cost_per_inventory_unit_calc(option_id, product_id, object, is_dollars)
{
    var raw_inventory = $('#inventory_option_'+product_id).val();

    if(object !== false)
    {
        object.value = number_with_commas(object.value.replace(/,/gi, ""));
    }

    if(option_id === false)
    {
        if(is_dollars === false)
        {
            $('#base_dollars_option_'+product_id).val(number_with_commas(raw_inventory));
        }

        cost_per_inventory_unit_calc(0, product_id, false, false);
        cost_per_inventory_unit_calc(1, product_id, false, false);
        cost_per_inventory_unit_calc(2, product_id, false, false);
        return;
    }

    var inventory = $('#base_dollars_option_'+product_id).val().replace(/[^0-9.]/g, "");
    var unit = $('#unit_option_'+option_id+'_'+product_id).val();
    var cpm = $('#cpm_option_'+option_id+'_'+product_id).val().replace(/[^0-9.]/g, "");

    if($.trim(cpm) == "")
    {
        cpm = 0;
        $('#cpm_option_'+option_id+'_'+product_id).val(cpm);
    }

    var sub_total = Math.round(+inventory + (cpm * unit / 1000));

    $('#product_subtotal_option_'+option_id+'_'+product_id).data('base-price', sub_total);
    $('#product_subtotal_option_'+option_id+'_'+product_id).html('$'+number_with_commas(sub_total));

    recalculate_totals();
}

function cost_per_discrete_unit_calc(option_id, product_id, object, is_dollars)
{
    var cpc_object = $('#cpm_option_'+option_id+'_'+product_id);
    var unit_object = $('#unit_option_'+option_id+'_'+product_id);
    var dollar_object = $('#dollar_option_'+option_id+'_'+product_id);
    var unit = unit_object.val().replace(/[^0-9.]/g, "");

    sub_total = 0;
    if(is_dollars)
    {
        var dollar_value = dollar_object.val().replace(/[^0-9.]/g, "");
        sub_total = dollar_value;
        cpc_object.val(number_with_commas(dollar_value/unit));
    }
    else
    {
        var original_cpc = cpc_object.data('default-cpc');
        if(typeof original_cpc !== "undefined")
        {
            cpc_object.val(original_cpc);
        }

        var cpc = cpc_object.val().replace(/[^0-9.]/g, "");

        sub_total = Math.round(cpc * unit);
        dollar_object.val(number_with_commas(sub_total));
    }
    if(object !== false)
    {
        object.value = number_with_commas(object.value.replace(/,/gi, ""));
    }



    $('#product_subtotal_option_'+option_id+'_'+product_id).data('base-price', sub_total);
    $('#product_subtotal_option_'+option_id+'_'+product_id).html('$'+number_with_commas(sub_total));
    recalculate_totals();
}

function input_box_calc(option_id, product_id, object)
{
    object.value = number_with_commas(object.value.replace(/,/gi, ""));

    var unit = $('#unit_option_'+option_id+'_'+product_id).val().replace(/[^0-9.]/g, "");

    if($.trim(unit) == "")
    {
        unit = 0;
        $('#unit_option_'+option_id+'_'+product_id).val(unit);
    }

    var sub_total = Math.round(unit);

    $('#product_subtotal_option_'+option_id+'_'+product_id).data('base-price', sub_total);
    $('#product_subtotal_option_'+option_id+'_'+product_id).html('$'+number_with_commas(sub_total));

    recalculate_totals();
}

function cost_per_static_unit_calc(option_id, product_id, object)
{
    object.value = number_with_commas(object.value.replace(/,/gi, ""));
    var unit = $('#content_option_'+product_id).val().replace(/[^0-9.]/g, "");

    if($.trim(unit) == "")
    {
        unit = 0;
        $('#content_option_'+product_id).val(unit);
    }

    if(option_id == false)
    {
        var price_0 = $('#price_option_0_'+product_id).val().replace(/[^0-9.]/g, "");
        var price_1 = $('#price_option_1_'+product_id).val().replace(/[^0-9.]/g, "");
        var price_2 = $('#price_option_2_'+product_id).val().replace(/[^0-9.]/g, "");

        var sub_total_0 = Math.round(price_0 * unit);
        var sub_total_1 = Math.round(price_1 * unit);
        var sub_total_2 = Math.round(price_2 * unit);

        $('#product_subtotal_option_0_'+product_id).html('$'+number_with_commas(sub_total_0));
        $('#product_subtotal_option_1_'+product_id).html('$'+number_with_commas(sub_total_1));
        $('#product_subtotal_option_2_'+product_id).html('$'+number_with_commas(sub_total_2));

        $('#product_subtotal_option_0_'+product_id).data('base-price', sub_total_0);
        $('#product_subtotal_option_1_'+product_id).data('base-price', sub_total_1);
        $('#product_subtotal_option_2_'+product_id).data('base-price', sub_total_2);
    }
    else
    {
        var price = $('#price_option_'+option_id+'_'+product_id).val().replace(/[^0-9.]/g, "");

        if($.trim(price) == "")
        {
            price = 0;
            $('#price_option_'+option_id+'_'+product_id).val(price);
        }

        var sub_total = Math.round(price * unit);

        $('#product_subtotal_option_'+option_id+'_'+product_id).data('base-price', sub_total);
        $('#product_subtotal_option_'+option_id+'_'+product_id).html('$'+number_with_commas(sub_total));
    }

    recalculate_totals();
}

function cost_per_tv_package_calc(option_id, product_id, object)
{
    var pack = $(object).val();
    var option_id = $(object).data('option-id');

    if (pack === "custom")
    {
        $('#custom_tv_package_'+option_id).show(0);
        $('input[name="custom_tv_package_'+option_id+'_spots"]').attr('disabled', false);
        $('input[name="custom_tv_package_'+option_id+'_price"]').attr('disabled', false);
    }
    else
    {
        $('#custom_tv_package_'+option_id).hide(0);
        $('input[name="custom_tv_package_'+option_id+'_spots"]').attr('disabled', true);
        $('input[name="custom_tv_package_'+option_id+'_price"]').attr('disabled', true);
        updateTVPricingByZones([pack], $(object));
    }

    recalculate_totals();
}

function cost_per_sem_unit_calc(option_id, product_id)
{
    var cpm_object = $('#cpm_option_'+option_id+'_'+product_id);
    var unit_object = $('#unit_option_'+option_id+'_'+product_id);

    var clicks_object_val = cpm_object.val().replace(/[^0-9.]/g, "");
    var keywords_clicks_val = $('#keywords_clicks_amount').val().replace(/[^0-9.]/g, "");
    var unit_object_val = unit_object.val().replace(/[^0-9.]/g, "");

    if(clicks_object_val === "" || keywords_clicks_val === "" || ($.isNumeric(keywords_clicks_val) && $.isNumeric(clicks_object_val) && clicks_object_val > keywords_clicks_val))
    {
        cpm_object.addClass('invalid');
    }
    else
    {
        cpm_object.removeClass('invalid');
    }

    if(unit_object_val === "")
    {
        unit_object_val = 0;
        unit_object.val(0);
    }

    $('#product_subtotal_option_'+option_id+'_'+product_id).html('$'+number_with_commas(unit_object_val));
    recalculate_totals();
}

function custom_tv_package_calc(option_id, product_id, object)
{
    var price = $(object).val() || 0;
    if (price === 0) $(object).val(0);
    $('#product_subtotal_option_'+option_id+'_'+product_id).html('$'+number_with_commas(price));
    recalculate_totals();
}

function custom_tv_spots_calc(option_id, product_id, object)
{
    var spots = $(object).val() || 0;
    if (spots === 0) $(object).val(0);
}
var is_form_submitted_flag=false;
$('#cp_submit_button').click(function(){

    if (is_form_submitted_flag)
        return;

    var validate_response = rfp_validate();
    if(validate_response !== "") //empty string means success
    {
        Materialize.toast('Ooops! It looks like you haven\'t filled out the page completely:'+validate_response, 20000, 'toast_top');
        return;
    }
    var product_object = {};
    $('input.product_input, select.product_input').each(function(){
        var product = $(this);
        var input_type = product.attr('data-input-type');
        var option_id = product.attr('data-option-id');
        var product_id = product.attr('data-product-id');
        var uses_cpm_switch = product.attr('data-cpm-switch');
        var cpc_reverse_calc = product.attr('data-cpc-reverse-calc');
        var value = product.val().replace(/[,]/g, "");
        if(value == "")
        {
            value = 0;
        }

        if((($('#product_'+product_id).prop('checked') && $('#product_'+product_id).is(':visible')) || product.hasClass('after_discount')) && $('#option_'+option_id+'_indicator').is(':visible'))
        {
            if(typeof uses_cpm_switch !== "undefined")
            {
                var temp_cpm = $('#cpm_option_'+option_id+'_'+product_id).val();
                var temp_type = $('#type_option_'+option_id+'_'+product_id).val();
                var raw_unit_multiplier = $('#unit_multiplier_'+option_id+'_'+product_id).val();
                var unit_multiplier = 1000;
                if(typeof raw_unit_multiplier !== "undefined")
                {
                    unit_multiplier = raw_unit_multiplier;
                }
                if(temp_type == "dollars")
                {
                    if(temp_cpm == 0)
                    {
                        value = 0;
                    }
                    else
                    {
                        value = value*unit_multiplier/temp_cpm;
                    }
                }
            }
            else if(typeof cpc_reverse_calc !== "undefined")
            {
                var temp_cpc = $('#cpm_option_'+option_id+'_'+product_id).val();
                if(temp_cpc == 0)
                {
                    value = 0;
                }
                else
                {
                    value = value/temp_cpc;
                }
            }

            if(!(product_id in product_object))
            {
                product_object[product_id] = {};
            }
            if(!(option_id in product_object[product_id]))
            {
                product_object[product_id][option_id] = {};
            }
            if (input_type === "tv") {
                value_obj = {
                    unit: value
                };
                if (value === "custom") {
                    value_obj.spots = $('input[name="custom_tv_package_'+option_id+'_spots"]').val();
                    value_obj.price = $('input[name="custom_tv_package_'+option_id+'_price"]').val();
                }
                product_object[product_id][option_id] = value_obj;
            } else {
                product_object[product_id][option_id][input_type] = value;
            }
        }
    });
    $('input.no_option_product_input, select.no_option_product_input').each(function(){
        var product = $(this);
        var input_type = product.attr('data-input-type');
        var product_id = product.attr('data-product-id');
        if($('#product_'+product_id).prop('checked') && $('#product_'+product_id).is(':visible'))
        {
            var value = product.val().replace(/[,]/g, "");
            if(value == "")
            {
                value = 0;
            }
            if(!(product_id in product_object))
            {
                product_object[product_id] = {};
            }
            if($('#option_0_indicator').is(':visible'))
            {
                if(!(0 in product_object[product_id]))
                {
                    product_object[product_id][0] = {};
                }
                product_object[product_id][0][input_type] = value;
            }
            if($('#option_1_indicator').is(':visible'))
            {
                if(!(1 in product_object[product_id]))
                {
                    product_object[product_id][1] = {};
                }
                product_object[product_id][1][input_type] = value;
            }
            if($('#option_2_indicator').is(':visible'))
            {
                if(!(2 in product_object[product_id]))
                {
                    product_object[product_id][2] = {};
                }
                product_object[product_id][2][input_type] = value;
            }
        }
    });

    $('.product_budget_allocation:checked').each(function(){
        var input = $(this);
        var product_id = input.attr('data-product-id');
        var value = input.val();
        if($('#product_'+product_id).prop('checked') && $('#product_'+product_id).is(':visible'))
        {
            if(!(product_id in product_object))
            {
                product_object[product_id] = {};
            }
            if($('#option_0_indicator').is(':visible'))
            {
                if(!(0 in product_object[product_id]))
                {
                    product_object[product_id][0] = {};
                }
                product_object[product_id][0]['budget_allocation'] = value;
            }
            if($('#option_1_indicator').is(':visible'))
            {
                if(!(1 in product_object[product_id]))
                {
                    product_object[product_id][1] = {};
                }
                product_object[product_id][1]['budget_allocation'] = value;
            }
            if($('#option_2_indicator').is(':visible'))
            {
                if(!(2 in product_object[product_id]))
                {
                    product_object[product_id][2] = {};
                }
                product_object[product_id][2]['budget_allocation'] = value;
            }
        }
    });

    $('.product_display_input').each(function(){
        var input = $(this);
        var value = input.val();
        var product_id = input.attr('data-product-id');
        if(value !== "")
        {
            $.each(product_object[product_id], function(option_id, option){
                option['custom_name'] = value;
            });
        }
    });

    var option_data = [];
    if($('#option_0_indicator').is(':visible'))
    {
        option_data.push({
            "name": $('#option_0_title').val(),
            "discount": $('#discount_percent_option_0').val(),
            "term": $('#option_0_term').val(),
            "duration": $('#option_0_duration').val(),
			"grand_total": $('#grand_total_option_0').text().replace(/[$,]/g, '')
        });
    }
    if($('#option_1_indicator').is(':visible'))
    {
        option_data.push({
            "name": $('#option_1_title').val(),
            "discount": $('#discount_percent_option_1').val(),
            "term": $('#option_1_term').val(),
            "duration": $('#option_1_duration').val(),
			"grand_total": $('#grand_total_option_1').text().replace(/[$,]/g, '')
        });
    }
    if($('#option_2_indicator').is(':visible'))
    {
        option_data.push({
            "name": $('#option_2_title').val(),
            "discount": $('#discount_percent_option_2').val(),
            "term": $('#option_2_term').val(),
            "duration": $('#option_2_duration').val(),
			"grand_total": $('#grand_total_option_2').text().replace(/[$,]/g, '')
        });
    }

    var cc_owner = $('#cc_owner').is(":checked") ? 1 : 0;
    var iab_categories = encode_iab_category_data();
    var political_segments = encode_political_data();
    var demographics = encode_demographic_data();
    var rooftops = encode_rooftops_data();
    var tv_zones = encode_tv_zones_data();
    var keywords_data = encode_keywords_data();
    var options = JSON.stringify(option_data);
    var discount_text = $('#discount_display_input').val();
    if(discount_text == "")
    {
        discount_text = "Discount";
    }

    is_form_submitted_flag=true;
    $('#cp_submit_button').attr("disabled", true);
    $('#cp_submit_button').text("Processing...");

    $.ajax({
        type: "POST",
        url: '/proposal_builder/create_mpq_proposal_v2',
        async: true,
        data:
        {
            product_object: product_object,
            cc_owner: cc_owner,
            iab_categories: iab_categories,
            political_segments: political_segments,
            demographics: demographics,
            rooftops: rooftops,
            tv_zones: tv_zones,
            keywords_data: keywords_data,
            options: options,
            discount_text: discount_text,
            rfp_status: rfp_status,
            mpq_id: mpq_id

        },
        dataType: 'json',
        success: function(data, textStatus, jqXHR){
            if(typeof data.session_expired !== "undefined" && data.session_expired === true)
            {
                Materialize.toast('Notice: The form has expired.  Please refresh to continue creating your proposal.', 80000, 'toast_top');
            }
            else if(data.is_success === true)
            {
                var successForm = $('#take_me_to_success');
                successForm.find('[name="id"]').val(data.prop_id);
                successForm.submit();
            }
            else
            {
                Materialize.toast('Ooops! We had a problem receiving your proposal. Please refresh the page and fill a new proposal', 16000, 'toast_top');
                send_debug_email(data.toString());
            }

        },
        error: function(jqXHR, textStatus, error){
            Materialize.toast('Ooops! We had a problem receiving your proposal. Please refresh the page and fill a new proposal', 16000, 'toast_top');
            send_debug_email(JSON.stringify(jqXHR) + " " + textStatus.toString() + " " + JSON.stringify(error));
        }
    });
});

function send_debug_email(data_string)
{
    $.ajax({
        type: "POST",
        url: '/proposal_builder/send_debug_email',
        async: true,
        data:
        {
            data_string: data_string
        },
        dataType: 'json',
        success: function(data, textStatus, jqXHR){
        },
        error: function(jqXHR, textStatus, error){
        }
    });
}

$('.proposal_option_close_button').click(function(e){
    var option_id = $(this).attr('data-option-id');
    $('.option_'+option_id+'_item').fadeOut(200, rename_visible_options);
    rfp_status.is_option_display[option_id] = false;
    recalculate_totals();
});

$('#add_new_option_button').click(function(e){
    for(var i = 0; i < rfp_status.is_option_display.length; i++)
    {
        if(rfp_status.is_option_display[i] == false)
        {
            rfp_status.is_option_display[i] = true;
            $('.option_'+i+'_item').fadeIn();
            break;
        }
    }

    rename_visible_options();
    recalculate_totals();
});

function rename_visible_options()
{
    if(smart_option_naming)
    {
        var count = 1;
        if($('#option_0_indicator').is(':visible'))
        {
            $('#option_0_text').text('Budget '+count);
            $('#option_0_title').val('Budget '+count);
            count++;
        }
        if($('#option_1_indicator').is(':visible'))
        {
            $('#option_1_text').text('Budget '+count);
            $('#option_1_title').val('Budget '+count);
            count++;
        }
        if($('#option_2_indicator').is(':visible'))
        {
            $('#option_2_text').text('Budget '+count);
            $('#option_2_title').val('Budget '+count);
        }
    }
}

$('.duration_dropdown').change(function(e){
    var object = $(this);
    if(object.val() !== "")
    {
        $('.input_cpm.cpm_disabled').each(function(key, value) {
            if(typeof $(value).data('cpmPeriods') !== 'undefined' && $(value).data('optionId') == object.data('optionId'))
            {
                var cpm_object = $(this);
                var cpm_periods = $(value).data('cpmPeriods');
                $.each(cpm_periods, function(period, cpm_amount) {
                    if((object.val() * 1) <= (period * 1))
                    {
                        cpm_object.val(cpm_amount);
                        return false;
                    }
                });
            }
        });

        updateBudgetIfImpressionsSelected(object);

        recalculate_totals();
    }
});

function updateBudgetIfImpressionsSelected(object){

    $('.product_impressions_dollar_budget').each(function(key, value){
        var _self = $(this);
        if($(value).attr('data-option-id') == object.data('optionId')){
            var optionId = object.data('optionId');
            $(".budget_impressions_dependent[data-option-id="+optionId+"]").each(function(key, value){
                var _child = $(this);
                var grandParent  = _child.parent().parent();
                if($(grandParent).find('select')[0].value == "impressions"){
                    var unitMultiplier = $(grandParent).parent().find('input:hidden')[0].value;
                    var unit = $('#unit_option_'+optionId+'_'+_child.data('product-id')).val().replace(/[^0-9.]/g, "");
                    var cpm  = $(grandParent).parent().find('input:disabled')[0].value;
                    var subTotal = Math.round(cpm * unit / unitMultiplier);
                    $("#product_subtotal_option_"+optionId+'_'+_child.data('product-id')).html('$'+number_with_commas(subTotal));
                    $("#product_subtotal_option_"+optionId+'_'+_child.data('product-id')).data("base-price", subTotal)
                }
            });
        }
    });
}

$('.duration_term_dropdown').change(function(e){
    var object = $(this);
    var option_id = object.attr('data-option-id');
    var term_text = "per month";
    if(object.val() == "weekly")
    {
        term_text = "per week";
    }
    else if(object.val() == "daily")
    {
        term_text = "per day";
    }
    $('.cp_term_text_'+option_id).html(term_text);
});

$('.budget_option_title').change(function(e){
    var object = $(this);
    if(object.val().length > 50 || object.val() == "")
    {
        object.addClass('invalid');
    }
    else
    {
        object.removeClass('invalid');
    }
});

//validate that at least one product is checked
function validate_product_section()
{
    var is_success = false;
    $('#mpq_product_info_section input[type=checkbox]').each(function(key, value){
        if($(value).prop('checked') == true || $(value).prop('checked') == 'checked')
        {
            is_success = true;
        }
    });

    return is_success;
}

function validateIfDigitalSelectedForTV(){
    var is_success = true;
    if(rfp_status.num_tv_zone_products > 0){
        if(!rfp_status.has_geo_products)
            is_success = false;
    }
    return is_success;
}

function validate_options_and_products()
{
    var return_string = "";
    $('.budget_option_title').each(function(key, value){
        if($(value).val() == "" || $(value).val().length > 40)
        {
            return_string += "<br>- Budget name is too long";
        }
    });

    var is_success = true;
    $('.option_term_duration').each(function(key, value){
        if($(value).val() == "")
        {
            is_success = false;
        }
    });

    $('.product_input:not(div)').each(function(key, value){
        if($(value).val() == "" && $(value).attr('disabled') === false)
        {
            is_success = false;
        }
    });

	if($('input.enable_keywords_section:visible:checked').length > 0)
	{

		var keywords_clicks = $('#keywords_clicks_amount').val();
		var numeric_string = "";
		var budget_percentage_string = "";
		var visible_keywords_clicks = [];

		$('.product_input.keywords_clicks_dependent').each(function(i, input) {
			$(input).removeClass('invalid');
			var value = parseInt($(input).val());
			var sem_success = true;
			var option_id = $(input).data('optionId');

			if($('#option_'+option_id+'_indicator').is(':visible'))
			{
				visible_keywords_clicks.push(value);

				var clicks_input = $(value).find('.sem_clicks_amount').val();
				var price_input = $(value).find('.sem_price_amount').val();

				if (isNaN(value) || value === "") {
					sem_success = false;
					numeric_string = "<br>- SEM clicks per month must be a number";
				}
				
				if (value > keywords_clicks) {
					sem_success = false;
					budget_percentage_string = "<br>- Your clicks per month must be fewer than the click inventory specified above ("+keywords_clicks+")";
				}

				if ( (clicks_input == 0 && price_input != 0) || (price_input == 0 && clicks_input != 0) ) {
					sem_success = false;

				}

				if (sem_success === false) {
					$(input).addClass('invalid');
					is_success = false;
				}
			}
		});

		var all_keywords_zero = true;
		$.each(visible_keywords_clicks, function(key, click) {
			if(click > 0)
			{
				all_keywords_zero = false;
			}
		});

		if((visible_keywords_clicks.length == 0) || all_keywords_zero)
		{
			return_string += "<br>- You must have at least one budget with SEM clicks greater than 0";
		}

		var sem_budget_success = true;
		$('.sem_budget_item').each(function(i, budget) {
			var clicks_input = $(budget).find('.sem_clicks_amount').val();
			var price_input = $(budget).find('.sem_price_amount').val();
			var option_id = $(budget).data('option-id');

			if($('#option_'+option_id+'_indicator').is(':visible'))
			{
				if ( (clicks_input == 0 && price_input != 0) || (price_input == 0 && clicks_input != 0) ) {
					sem_budget_success = false;
					$(budget).find('.sem_clicks_amount').addClass('invalid');
					$(budget).find('.sem_price_amount').addClass('invalid');
				}
			}
		});
		if (sem_budget_success === false) {
			return_string += "<br>- You must either set an amount for both SEM clicks and Budget or set both to zero";
			is_success = false;
		}
	}

    var tv_budget_success = true;
    $('.tv_budget_item').each(function(i, budget) {
        $(budget).find('.custom_spots_input').removeClass('invalid');
        $(budget).find('.custom_price_input').removeClass('invalid');

        var option_id = $(budget).data('option-id');
        var product_id = $(budget).data('product-id');
        var pack_input = $('#unit_option_'+option_id+'_'+product_id).val();

        if (pack_input === "custom" && $('#option_'+option_id+'_indicator').is(':visible')) {
            var spots_input = $(budget).find('.custom_spots_input').val();
            var price_input = $(budget).find('.custom_price_input').val();

            if ( (spots_input == 0 && price_input != 0) || (price_input == 0 && spots_input != 0) ) {
                tv_budget_success = false;
                $(budget).find('.custom_spots_input').addClass('invalid');
                $(budget).find('.custom_price_input').addClass('invalid');
            }
        }
    });
    if (tv_budget_success === false) {
        return_string += "<br>- You must either set an amount for both TV Spots and Price or set both to zero";
        is_success = false;
    }

    var sem_string = numeric_string + budget_percentage_string;
    if (sem_string.length > 0) {
        return_string += sem_string;
    }

    $options_exist_flag=false;
    $('.product_budget_calc').each(function(key, value){
        if($(value).is(':visible'))
        {
            $options_exist_flag=true;
        }
    });

    if (!$options_exist_flag)
    {
        is_success = false;
    }

    if(is_success === false)
    {
        return_string += "<br>- Please complete the budget section";
    }

    return return_string;
}

function validate_discount_text()
{
    if($('#discount_display_input').val() == "")
    {
        return false;
    }
    return true;
}

function validate_rooftops()
{
    var num_rooftops = $('#rooftops_multiselect').select2('data').length;
    if(num_rooftops > 0)
    {
        return true;
    }
    return false;
}

function validate_tvzones()
{
    var num_tv_zones = $('#tvzones_multiselect').select2('data').length;
    if(num_tv_zones > 0)
    {
        return true;
    }
    return false;
}

function validate_keywords_section()
{
    var keywords_clicks = $('#keywords_clicks_amount').val();
    if($('#keywords_tags .keywords_search_term').length > 0 && keywords_clicks !== "" && $.isNumeric(keywords_clicks))
    {
        return true;
    }
    return false;
}

function rfp_validate()
{
    var text_response = "";
    if(!validate_product_section())
    {
        text_response += "<br>- Please select at least one product.";
    }
    if(!validateIfDigitalSelectedForTV()){
        text_response += "<br>- Please select at least one Digital product with TV.";
    }
    if(rfp_status.num_tv_zone_products > 0 && $('input.enable_tv_zones_section:visible').length > 0)
    {
        if(!validate_tvzones())
        {
            text_response += "<br>- Please complete the TV zones section";
        }
    }
    if(rfp_status.num_geo_products > 0)
    {
        var geo_success = {'is_success': false};
        validate_geography(geo_success);
        if(!geo_success.is_success)
        {
            text_response += "<br>- Please complete the geography section";
        }
    }
    if(rfp_status.num_audience_products > 0)
    {
        if(!validate_demographics())
        {
            text_response += "<br>- Please make sure the demographics you provided are correct";
        }
        if(!validate_iab_categories())
        {
            text_response += "<br>- Please provide at least three contextual channels";
        }
    }
    if(rfp_status.num_rooftops_products > 0 && $('input.enable_rooftops_section:visible').length > 0)
    {
        if(!validate_rooftops())
        {
            text_response += "<br>- Please complete the rooftops section";
        }
    }

    if(rfp_status.num_keywords_products > 0 && $('input.enable_keywords_section:visible').length > 0)
    {
        if(!validate_keywords_section())
        {
            text_response += "<br>- Please complete the keywords section";
        }
    }

    var option_product_response = validate_options_and_products();
    if(option_product_response !== "")
    {
        text_response += option_product_response;
    }
    if(!validate_discount_text())
    {
        text_response += "<br>- Please provide a name for your discount";
    }

    return text_response;
}

function preload_options_data(option_object)
{
    if(option_object.length < 3)
    {
        $('.option_2_item').hide();
        rfp_status.is_option_display[2] = false;
    }
    if(option_object.length < 2)
    {
        $('.option_1_item').hide();
        rfp_status.is_option_display[1] = false;
    }
    $.each(option_object, function(key, value){
        var option_id = value.option_id;
        $('#option_'+option_id+'_text').text(value.option_name);
        $('#option_'+option_id+'_title').val(value.option_name);
        $('#discount_percent_option_'+option_id).val(value.discount);
        $('#option_'+option_id+'_term').val(value.term);
        $('#option_'+option_id+'_term').material_select();
        $('#option_'+option_id+'_duration').val(value.duration);
        $('#option_'+option_id+'_duration').material_select();
    });
    $('#discount_display_input').val(option_object[0].discount_name);
    $('#discount_display_text').text(option_object[0].discount_name);
}

/*
 * Functions to disable one checkbox if either of digital products
 * are unchecked
 */

var productConstants = {
    TV : "TV",
    DISPLAY : "Display",
    PREROLL : "Preroll",
    SEM : "SEM",
    VISITS : "Visits",
    DIRECTORIES : "Directories",
    LEADS : "Leads",
    CONTENT : "Content"
}

/**
 @summary Function to enable the checkbox of the product name
 @param product name
 */
function enableCheckBox(productName){
    var ele;
    $("#product_section_content").find(".product-display-text").each(function(){
        if($(this).html().replace(/\r?\n|\r/, '') == productName){
            ele = $(this);
            ele.siblings("input:checkbox").attr("disabled", false);
        }
    });
}

/**
 @summary Function to disable the checkbox of the product name
 @param product name
 */
function disableCheckBox(productName){
    var ele;
    $("#product_section_content").find(".product-display-text").each(function(){
        if($(this).html().replace(/\r?\n|\r/, '') == productName){
            ele = $(this);
            ele.siblings("input:checkbox").attr("disabled", true);
        }
    });
}

/**
 @summary Function to check if either preroll or display selected
 @param parent object of checkboxes
 @return boolean
 */
function checkIfDisplayOrPreRollSelected(_parent){

    var status = false;
    var productStatusMap = getProductCheckedStatus();
    var checkedProductName = _parent.siblings(".product-display-text").text().replace(/\r?\n|\r/, '');
    var checkedProductStatus = _parent.prop("checked");
    //Temporary Fix to disable "Display or PreRoll" Selection for Spectrum
    // Will be changed in latest refactor
    var required = _.keys(productStatusMap).length == 6;
    if(!checkedProductStatus && !required){
        if(checkedProductName != productConstants.TV){
            productStatusMap[checkedProductName] =checkedProductStatus;
            status = productStatusMap[productConstants.DISPLAY] && productStatusMap[productConstants.PREROLL];
        }else{ status = true;}
    }else{ status = true;}
    return status;
}

/**
 @summary Function to get product checked status; iterates over all the checkboxes
 and creates a status map
 @return Object (example: {Digital: false, Preroll : true})
 */
function getProductCheckedStatus(){
    var productStatus = {};
    $("#product_section_content input:checkbox").each(function(key, value){
        var _self = $(this);
        var productName = _self.siblings(".product-display-text").text().replace(/\r?\n|\r/, '').trim();
        productStatus[productName] = _self.prop("checked");
    });
    return productStatus;
}

/**
 @summary Function to disable checkbox for selection if only one digital product is selected
 */
function disableIfOneDigitalProductSelected(){
    var productStatusMap = getProductCheckedStatus();
    var required = _.keys(productStatusMap).length == 6;
    if(!required){
        if(!(productStatusMap[productConstants.DISPLAY] && productStatusMap[productConstants.PREROLL])){
            for(var key in productStatusMap){
                if(key != productConstants.TV && key != productConstants.SEM && productStatusMap[key]){disableCheckBox(key)}
            }
        }
    }
}

