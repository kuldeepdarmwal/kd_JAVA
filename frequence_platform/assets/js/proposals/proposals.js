jQuery(function($){

    $('input').placeholder();

    $('#proposal').fadeTo(0, 0);

    $('select').material_select();
    $('.modal-trigger').leanModal();
    $(".button-collapse").sideNav();

    var proposal_data;
    var proposal_id = $('input#proposal_id').val();
    var iframe_src_base = '/proposals/' + proposal_id;
    load_rendered_proposal($("#template option:first").val());

    $(document).on('change', '#template', function(e){
        e.stopPropagation();
        load_rendered_proposal(value = $(this).val());
    });

    function load_rendered_proposal(id)
    {
        $('#proposal').fadeTo(200, 0);
        $('#proposal').height(0);

        var template_query = id === 'null' ? '' : '?template='+id;
        $('#proposal').attr('src', iframe_src_base + '/html/' + template_query);
        $('#proposal').load(function(e){
            e.stopImmediatePropagation();

            scale_proposal();
            $('main, #proposal').height($('#proposal').contents().outerHeight());
            $('#proposal').fadeTo(200, 1);
        });
    }

    function scale_proposal()
    {
        var container_width = $('main').width();
        var proposal_width = $('#proposal').contents().find('.page').outerWidth();

        var ratio = container_width / (proposal_width + 50);
        if (ratio >= 1){
            $('#proposal').contents().find('body').css({
                'transform': 'none',
                'width': '100%'
            });
            return true;
        }

        $('#proposal').contents().find('body').width(proposal_width);
        $('#proposal').contents().find('body').css('transform', 'scale('+ratio+')');
        $('#proposal').contents().find('body').css('-webkit-transform-origin', '0 0');

        var scaled_padding = (container_width - ($('#proposal').contents().find('body').width() * ratio)) / 2;
        $('#proposal').contents().find('html').css('padding-left', scaled_padding);
    }

    $(window).resize(function(e){
        scale_proposal();
    });

    $('#save_proposal_html').on('click', function(e){
        e.preventDefault();

        $.ajax({
            url: '/proposals/'+proposal_id+'/save_html',
            method: 'POST',
            dataType: 'json',
            data: {
                'html': $('#proposal').contents().find('html').html(),
                'proposal_id': proposal_id,
                'base_template': $('#template').val()
            },
            success: function(data) {
                $('#template').val($("#template option:first").val());
                Materialize.toast("Template Saved", 4000);
            },
            error: function(err) {
                Materialize.toast(err.responseText, 5000);
            }
        });
    });

    $('#template_upload').submit(function(e){
        e.preventDefault();

        $.ajax({
            url: $(this).attr('action'),
            type: $(this).attr('method'),
            data: new FormData(this),
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(data){
                load_rendered_proposal(data.id);
                if ($('#template optgroup option[value="'+data.id+'"]').length == 0)
                {
                    $('#template optgroup')
                        .append($("<option></option>")
                        .attr("value", data.id)
                        .text(data.filename.replace('templates/', '')));
                }
                $('#template').val(data.id);
                $('#modal1').closeModal();
                $('#template_upload').trigger('reset');
            },
            error: function(err){
                Materialize.toast(err.responseText, 5000);
                $('input.file-path').addClass('invalid');
            }
        });
    });
});