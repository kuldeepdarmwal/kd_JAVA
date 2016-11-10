$("#parent_partner_select").select2({
        placeholder: "Select a Parent Partner",
        data: parent_partner_data
});

$('#partner_palette_select').select2({
	multiple: false,
	placeholder: "Select a Palette",
	minimumResultsForSearch: -1,
	allowClear: true,
	dropdownAutoWidth: true,
	ajax: {
		url: "/partners/get_partner_palettes",
		type: "POST",
		dataType: "json",
		data: function (term, page) {
			term = '%';
			return {
				q: term,
				page_limit: 50,
				page: page
			};
		},
		results: function(data) {
			return {results: data.results, more: data.more};
		}
	},
	formatResult: format_partner_palette,
	formatSelection: format_partner_palette
});

if(!$.isEmptyObject(partner_palette_data))
{
	$('#partner_palette_select').select2('data', partner_palette_data);
}

function format_partner_palette(data)
{
	var return_string = format_partner_palette_color_column(data.primary_color);
		return_string += format_partner_palette_color_column(data.secondary_color);
		return_string += format_partner_palette_color_column(data.tertiary_color);
		return_string += format_partner_palette_color_column(data.quaternary_color);
		return_string += format_partner_palette_color_column(data.quinary_color);
		return return_string;
}

function format_partner_palette_color_column(color)
{
	return '<span class="partner_palette_color_container"><span style="background-color:'+color+'" class="partner_palette_color_view"></span><span class="partner_palette_color_text">'+color+'</span></span>';
}

$(document).ready(function(){
    
        $('.tooltip_description').popover();

        // move preview section as per scroll
        var $scrollingDiv = $("#preview-container-section");

        $(window).scroll(function(){			
                $scrollingDiv
                        .stop()
                        .animate({"marginTop": ($(window).scrollTop() + 30) + "px"}, "slow" );			
        });

        $("#partner_name").keyup(function(){
                display_texts(this, "#title_container");
                
                if($('#demo_partner').length && $("#demo_partner").is(':checked') && (partner_create_update == 'new'))
                {
                        $('#partner_user_email').val(replace_special_chars('#partner_name')+default_partner_user_email);
                        $('#advertiser_email').val(replace_special_chars('#partner_name')+default_advertiser_email);
                } else if($('#demo_partner').length && $("#demo_partner").is(':checked'))
                {
                    $('#partner_user_email').val(demo_sales_email);
                    $('#advertiser_email').val(demo_adv_email);
                }
        });

        $("#partner_domain").keyup(function(){
                display_texts(this, "#domain_container");
        });

        function display_texts(obj, id)
        {        
                var suffix_title = '';        
                var title = $(obj).val();
                if ($(obj).val().length > 30)
                {
                        title = $(obj).val().substring(0, 30);
                        suffix_title = '...';
                }

                $(id).text(title + suffix_title);
        }

        
        function replace_special_chars(id)
        {
                return $(id).val().trim().replace(/[^a-z0-9]+/gi, '-');
        }
        
        $("#file_upload_login").change(function (e) {
                if(!validate_image_extension('#file_upload_login', 'regular'))
                {
                        $('#file_upload_login').val('');
                        document.getElementById('logo').src = logo_image_path;
                        display_toast_message('Please browse gif, png, jpg, jpeg images only for Login Image');
                }
                else
                {
                        load_preview_images(this, '#file_upload_login', 'logo');
                }
        });

        $("#file_upload_header").change(function (e) {
                if(!validate_image_extension('#file_upload_header', 'regular'))
                {
                        $('#file_upload_header').val('');
                        document.getElementById('header').src = header_image_path;
                        display_toast_message('Please browse gif, png, jpg, jpeg images only for Header Image');
                }
                else
                {
                        load_preview_images(this, '#file_upload_header', 'header');
                }
        });

        $("#file_upload_favicon").change(function (e) {
                if(!validate_image_extension('#file_upload_favicon', 'favicon'))
                {
                        $('#file_upload_favicon').val('');
                        document.getElementById('favicon').src = favicon_image_path;
                        display_toast_message('Please browse ico images only for Favicon Image');
                }
                else
                {
                        load_preview_images(this, '#file_upload_favicon', 'favicon');
                }
        });

        var _URL = window.URL;
        function load_preview_images(this_image, id, preview_id)
        {
                var file, img, img_width, img_height;
                if ((file = this_image.files[0]))
                {
                        img = new Image();
                        img.onload = function ()
                        {
                                if (this.width > 500 || this.height > 500)
                                {
                                        $(id).val('');
                                        if (preview_id == 'logo')
                                        {
                                                document.getElementById(preview_id).src = logo_image_path;
                                        }
                                        if (preview_id == 'header')
                                        {
                                                document.getElementById(preview_id).src = header_image_path;
                                        }
                                        if (preview_id == 'favicon')
                                        {
                                                document.getElementById(preview_id).src = favicon_image_path;
                                        }
                                        display_toast_message('Please browse Image smaller than 500 X 500');
                                }
                                else
                                {                                        
                                        document.getElementById(preview_id).src = img.src;
                                }
                        };            

                        img.src = _URL.createObjectURL(file);
                }
        }

        // Display error message
        if(partner_form_error_message !== "")
        {
                display_toast_message(partner_form_error_message);
                return false;
        }

        // for cname validations - allow alha-numeric only
        $('#partner_domain').on('keypress paste', function (event) {
             alphanumeric_validations(event);
        });
        
        function alphanumeric_validations(event)
        {
                var regex = new RegExp("^[-a-zA-Z0-9\b]+$");
                var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
                if (!regex.test(key))
                {
                        event.preventDefault();
                        return false;
                }
        }
         
        // For demo checkbox
        $("#demo_partner").change(function() {
               if(this.checked)
                {
                        $('#partner_user_email').val(replace_special_chars('#partner_name')+default_partner_user_email);
                        $('#advertiser_email').val(replace_special_chars('#partner_name')+default_advertiser_email);
                        $('.demo_email').show();
                }
                else
                {
                        $('#partner_user_email').val('');
                        $('#advertiser_email').val('');
                        $('.demo_email').hide();
                }
        });
    });

$('#partner_submit_button').click(function(){

        var validate_response = partner_validate();
        if(validate_response !== "") //empty string means success
        {
                display_toast_message(validate_response);
                return false;
        }
        else if($('#parent_partner_select').val() != '' && $('#partner_domain').val() != '')
        {
                var is_demo_partner = 0;
                if($('#demo_partner').length && $("#demo_partner").is(':checked') && (partner_create_update == 'new'))
                {                    
                    is_demo_partner = 1;
                }
                check_cname_and_unique_partner(is_demo_partner);
                return false;
        }
});

function display_toast_message(error_message)
{
        Materialize.toast('Ooops! It looks like you haven\'t filled out the page completely:<br />'+error_message, 20000, 'toast_top');    
}

function partner_validate()
{
        var text_response = "";

        if($('#partner_name').val() == "")
        {
                text_response += "<br>- Please provide a Partner Name";
        }

        if($('#partner_domain').val() == "" && partner_create_update == 'new')
        {
                text_response += "<br>- Please provide a Partner Domain";
        }

        if($('#partner_homepage').val() == "")
        {
                text_response += "<br>- Please provide a Partner Homepage";
        }

        if($('#parent_partner_select').val() == "" && partner_create_update == 'new')
        {
                text_response += "<br>- Please select a Parent Partner";
        }        
        
        if($('#demo_partner').length && $("#demo_partner").is(':checked') && partner_create_update == 'new')
        {
                if(!validate_email($('#partner_user_email').val()))
                {
                        text_response += "<br>- Please provide a valid Partner User Email";
                }

                if(!validate_email($('#advertiser_email').val()))
                {
                       text_response += "<br>- Please provide a valid Advertiser Email";
                }
        } 

        if($('#file_upload_login').val() == "" && partner_create_update == 'new')
        {
                text_response += "<br>- Please browse Login Image";
        }
        else if($('#file_upload_login').val())
        {
                if(!validate_image_extension('#file_upload_login', 'regular'))
                {
                        text_response += "<br>- Please browse gif, png, jpg, jpeg images only for Login Image";
                }
        }

        if($('#file_upload_header').val() == "" && partner_create_update == 'new')
        {
                text_response += "<br>- Please browse Header Image";
        }
        else if($('#file_upload_header').val())
        {
                if(!validate_image_extension('#file_upload_header', 'regular'))
                {
                        text_response += "<br>- Please browse gif, png, jpg, jpeg images only for Header Image";
                }
        }
    
        if($('#file_upload_favicon').val())
        {
                if(!validate_image_extension('#file_upload_favicon', 'favicon'))
                {
                        text_response += "<br>- Please browse ico images only for Favicon Image";
                }
        }    

        return text_response;
}

//validate the image extension
function validate_image_extension(id, type)
{
        var is_success = false;
        var allowed_files = ['gif','png','jpg','jpeg'];
        if (type == 'favicon')
        {
                allowed_files = ['ico'];
        }
        var ext = $(id).val().split('.').pop().toLowerCase();    
        if($.inArray(ext, allowed_files) != -1)
        {
                is_success = true;
        }

        return is_success;
}

// validate email
function validate_email(email)
{
        var filter = /^[\w\-\.\+]+\@[a-zA-Z0-9\.\-]+\.[a-zA-z0-9]{2,4}$/;
        if (filter.test(email))
        {
                return true;
        }
        return false;
}
// check cname and unique partner
function check_cname_and_unique_partner(is_demo_partner)
{
        $('#partner_submit_button').prop("disabled", true);
        if(is_demo_partner == '1')
        {
            var sales_email = $('#partner_user_email').val();
            var adv_email = $('#advertiser_email').val();
            var cname = $('#partner_domain').val()+'-demo';
        }else
        {
            var sales_email = '';
            var adv_email = '';
            var cname = $('#partner_domain').val();
        }
        $.ajax({
            type: "POST",
            url: '/partners/check_cname_sales_adv_partner_unique',
            async: true,
            dataType: 'json',		
            data:
            {
                    cname: cname,
                    parent_partner: $('#parent_partner_select').val(),
                    partner_id: $('#partner_id').val(),
                    partner_name: $('#partner_name').val(),
                    sales_email: sales_email,
                    adv_email: adv_email,
                    is_demo_partner: is_demo_partner
            },
            success: function(data, textStatus, jqXHR){
                    if(data.is_success == true)
                    {
                            $('#partner_form').submit();
                            $('#partner_submit_button').prop("disabled", true);
                    }
                    else
                    {
                            $('#partner_submit_button').prop("disabled", false);
                            display_toast_message(data.errors);                            
                    }
            },
            error: function(jqXHR, textStatus, error){
                    $('#partner_submit_button').prop("disabled", false);
                    display_toast_message(jqXHR.errors);
            }
        });
}

if (partner_status != '')
{
        Materialize.toast(partner_status, 20000, 'toast_top', '', 'Status:');
}
