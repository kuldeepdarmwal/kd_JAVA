var partners_table;

$(document).ready(function(){
    
        $('#pv_partners_table').show();

        var partners_table_data = format_partners_table_data(pv_table_data);
        var partners_table_columns = [
                {
                        data: "data_partner_name",
                        title: "Partner",
                        render: {
                                _: "formatted",
                                sort: "sort"
                        },
                        type: "string",
                        "class": "pv_custom_column pv_column_padding"
                },
                {
                        data: "data_domain_name",
                        title: "Domain",
                        render: {
                                _: "formatted",
                                sort: "sort"
                        },
                        type: "string",
                        "class": "pv_auto_column"
                },
                {
                        data: "data_parent_partner_name",
                        title: "Parent Partner",
                        render: {
                                _: "formatted",
                                sort: "sort"
                        },
                        type: "string",
                        "class": "pv_custom_column pv_column_padding"
                },
                {
                        data: "actions",
                        title: "Actions",
                        orderable: false,
                        searchable: false,
                        "class": "pv_auto_column",
                        export: false
                }
        ];

        partners_table = $('#pv_partners_table').dataTable({
                "data": partners_table_data,
                "ordering": true,
                "order": [[0, "asc"]],
                "lengthMenu": [
                        [25, 50, 100],
                        [25, 50, 100]
                ],
                "columnDefs": [{}],
                "rowCallback": function(row,data) {
                },
                "initComplete": function(settings) {
                },
                "drawCallback": function(settings) {
                        $('.pv_tooltip').tooltip();
                },
                "columns": partners_table_columns
        });
});

function format_partners_table_data(data)
{
        $.each(data, function(key, value)
        {

                var demo_partner = '';
                if (this.is_demo_partner == 1)
                {
                        demo_partner = '<i><b>(Demo Partner)</b></i>';                    
                }

                this.data_partner_name = {
                        formatted: '<div class="pv_modal_column_container">'+demo_partner+' '+ this.partner_name +'</div>',
                        sort: this.partner_name
                };

                var domain_name_link = '--';
                if (this.cname)
                {
                        var domain_name = this.cname + '.' + domain_without_cname;
                        domain_name_link = '<a href="http://'+ domain_name +'" target="_blank">'+ domain_name +'</a>';
                }

                this.data_domain_name = {
                        formatted: '<div class="pv_modal_column_container">'+ domain_name_link + '</div>',
                        sort: this.cname
                };

                if (!this.parent_partner_name)
                {
                        this.parent_partner_name = '--';                    
                }
                this.data_parent_partner_name = {
                        formatted: '<div class="pv_modal_column_container">'+ this.parent_partner_name + '</div>',
                        sort: this.parent_partner_name
                };

                var ban_partners_title = 'No partner users to ban.';        
                if (this.num_partner_users > 0)
                {
                        ban_partners_title = 'Ban partner users.';
                }

                this.actions = '<div class="pv_action_container">';
                this.actions += '<a id="pv_edit_'+this.partner_id+'" href="/partners/edit/'+this.partner_id+'" class="btn btn-link pv_tooltip" data-trigger="hover" data-placement="top" data-title="edit"><i class="icon-pencil"></i></a>';
                if (user_role == 'admin')
                {
                        this.actions += '<a id="pv_ban_'+this.partner_id+'" href="#" class="btn btn-link pv_tooltip pv_ban_partner_button" data-trigger="hover" data-placement="top" data-title="'+ban_partners_title+'" data-partner-id="'+this.partner_id+'" data-partner-name="'+this.partner_name+'" data-num-partner-users="'+this.num_partner_users+'"><i class="icon-ban-circle"></i></a>';
                }
                this.actions += '</div>';
        });

        return data;
}

$(document).on('click', '.pv_ban_partner_button', function(e){
        e.preventDefault();
        var button_object = this;

        if ($(button_object).attr('data-num-partner-users') == 0)
        {
                Materialize.toast('No partner users to ban.', 20000, 'toast_top');
                return false;
        }

        var partner_id = $(button_object).attr('data-partner-id');
        var partner_name = $(button_object).attr('data-partner-name');

        if (window.confirm('Are you sure you want to ban partner users?'))
        {
                $.ajax({
                        type: "POST",
                        url: '/partners/ban_partner_users',
                        async: true,
                        dataType: 'json',		
                        data: 
                        {
                                partner_id: partner_id
                        },
                        success: function(data, textStatus, jqXHR){
                                if(data.is_success == true)
                                {
                                        $('partner_name_'+partner_id).html();
                                        $(button_object).attr('data-title', 'Partner users are banned.');
                                        $(button_object).attr('data-num-partner-users', 0);
                                        $(button_object).removeClass("intro");
                                        Materialize.toast('All users of '+partner_name+' and their descendants are banned', 20000, 'toast_top');
                                        return false;
                                }
                                else
                                {
                                        Materialize.toast('Ooops! Error - Please try again!', 20000, 'toast_top');
                                        return false;
                                }
                        },
                        error: function(jqXHR, textStatus, error){
                                Materialize.toast('Ooops! Error - Please try again!', 20000, 'toast_top');
                                return false;
                        }
                });
        }
});

if (partner_status != '')
{
        Materialize.toast(partner_status, 20000, 'toast_top', '', 'Status:');
}