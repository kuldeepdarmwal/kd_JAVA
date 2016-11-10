var g_iab_select2_expanded = false;
var g_timeout_id;

$(window).load(function(){
    initialize_table();
	init_tags_select2();
});

$(document).ready(function(){
	$("#iab_select2_expand").click(set_iab_width);

	$(window).keydown(function(e){
		if (e.keyCode == 46)
		{
			delete_selected();
		}
	});

});

function initialize_table()
{
    var fixer = function(e, ui) { //helper function that doesn't work right now.  Kept code here for future reference.
		//this is supposed to change the width of all the elements that are being dragged, but not all the elements get dragged anyways so its useless and was causing slowdown.
		$(".ui-selected.ui-sortable-helper").each(function(){
			/*console.log($(this));
			  if($(this).attr("id") == 'break' || $(this).attr("id") == 'header')
			  {
			  $(this).children().eq(0).width($("#headers").children().eq(0).width());
			  $(this).children().eq(1).width($("#headers").children().eq(1).width());
			  }
			  else
			  {
			  $(this).children().each(function(index){
			  if(index > 2)
			  {
			  $(this).width($("#headers").children().eq(index).width());
			  }
			  });
			  $(this).children().eq(0).width($("#headers").children().eq(0).width());//38);
			  $(this).children().eq(1).width($("#headers").children().eq(1).width());//200);
			  $(this).children().eq(2).width($("#headers").children().eq(2).width());//40);
			  
			  }*/
		});
        return ui;
    };
    var first_rows = {};
    $("table#uber tbody").selectable({
		filter : 'tr',
		cancel : 'td.sort'
    }).sortable({
		delay : 100,
		axis : 'y',
		placeholder : 'ui-state-highlight',
		handle : 'td.sort',
		helper : fixer,
        sort : function(event, ui) {
			var $helper = $('.ui-sortable-helper'),
            hTop = $helper.offset().top, 
            hStyle = $helper.attr('style'), 
            hId = $helper.attr('id');
			$helper.children().each(function(index){
				if(index > 2)
				{
					$(this).width($("#headers").children().eq(index).width());
				}
			});
			$helper.children().eq(0).width($("#headers").children().eq(0).width());//38);
			$helper.children().eq(1).width($("#headers").children().eq(1).width());//200);
			$helper.children().eq(2).width($("#headers").children().eq(2).width());//40);
            if (first_rows.length > 1) {
				$.each(first_rows, function(i, item) {
                    if (hId != item.id) {
						var _top = hTop + (21 * i);
						$('#' + item.id).addClass('ui-sortable-helper').attr('style', hStyle).css('top', _top);
                    }
				});
            }
		},
		start : function(event, ui) {

            if (ui.item.hasClass('ui-selected') && $('.ui-selected').length > 1) {
				first_rows = $('.ui-selected').map(function(i, e) {
					var $tr = $(e);
					return {
						tr : $tr.clone(true),
						id : $tr.attr('id')
					};
				}).get();
				$('.ui-selected').addClass('cloned');
            }
            ui.placeholder.html('<td colspan="99" style="width:1198px;">&nbsp;</td>');
		},
		update : function(event, ui) {
            //document.body.style.cursor = 'wait';
            //var arraied = $('table#uber tbody').sortable('toArray');
            //$('#info').html('sort order: ');
            //$.each(arraied, function(key, value) {
			//	$('#info').append(value);
            //   });
			
            // document.body.style.cursor = 'default';
		},
		stop : function(event, ui) {
            if (first_rows.length > 1) {
				$.each(first_rows, function(i, item) {
                    $(item.tr).removeAttr('style').insertBefore(ui.item);
				});
				$('.cloned').remove();
				first_rows = {};
            }
            $("#uber tr:even").removeClass("odd even").addClass("even");
            $("#uber tr:odd").removeClass("odd even").addClass("odd");
             highlight_page_breaks();
		}
    }).disableSelection();

    $("li:even > div, tr:even").removeClass("odd even").addClass("even");
    $("li:odd > div, tr:odd").removeClass("odd even").addClass("odd");

    highlight_page_breaks();

}
function reset_table_list()
{
    $("table#uber tbody").selectable("destroy");
    $("table#uber tbody").sortable("destroy");
    initialize_table();
}
function check_array(item)
{
    if($('#header_checkbox').prop('checked') == false)
    {
		return false;
    }
    else
    {
		var tf = false;
		var mybody = $('#uber tbody');
		mybody.children().each(function(i, elem){
			var mytr = $(elem).children().eq(1);
			if(mytr.hasClass('a_site'))
			{
				if(item == mytr.children().eq(0).html())
				{
					tf = true;
					return false;
				}
			}
		});
		return tf;
    }
    
}
function build_pack_list(pack_id)
{
    if(pack_id == 'none')
    {
		return;
    }
    $.ajax({
		type: "POST",
		url: "/proposal_builder/get_pack_sites/"+pack_id+"",
		async: true
    }).done(function(data) {
		if(data == 'FAIL')
		{
			set_message_timeout_and_show("Error 2101: Failed to create site pack list", "alert alert-error", 5000);
		}
		else
		{
			$('#site_pack').html(data);
		}
    }).fail(function() {
		set_message_timeout_and_show("Error 2103: Construct error retrieving site pack data", "alert alert-error", 5000);
    });
    

}
function save_sites(prop_id)
{
    var errors = "";
    var multi_array = new Array();
    var rows = $('#uber tbody');//.children();
    rows.children().each(function(i){
		multi_array[i] = new Array();
		if(rows.children().eq(i).children().eq(1).hasClass('a_site'))
		{
			multi_array[i][0] = rows.children().eq(i).children().eq(1).children().eq(0).html();
			for(var j = 2; j < rows.children().eq(i).children().length; j++)
			{
				multi_array[i][j -1] = rows.children().eq(i).children().eq(j).html();
			}
		}
		else if(rows.children().eq(i).children().eq(1).hasClass('a_break'))
		{
			multi_array[i][0] = 'break_tag';
			multi_array[i][1] = '(page break)';
		}
		else if(rows.children().eq(i).children().eq(1).hasClass('a_header'))
		{
			multi_array[i][0] = 'header_tag';
			multi_array[i][1] = rows.children().eq(i).children().eq(1).text();
		}
		else //no class
		{
			
			errors += "Error 0419: wrong class on row at "+i+"\n";
		}
		
    });
    if(errors == "")
    {
		$.ajax({
			async:true,
			type: "POST",
			url: "/proposal_builder/insert_proposal_sites/"+prop_id,
			data:{sites: JSON.stringify(multi_array)},
			dataType: "json",
			success: function(data){
				if(data.is_success == true)
				{
					if(data.updated == true)
					{
						set_message_timeout_and_show("Sitelist saved successfully", "alert alert-success", 5000);
					}
					else
					{
						set_message_timeout_and_show("No sitelist update required", "alert alert-info", 5000);
					}
				}
				else
				{
					set_message_timeout_and_show("Error updating sites: "+data.errors, "alert alert-error", 5000);
				}
			},
			error: function(data){
				set_message_timeout_and_show("Error 22013078: Unable to save sitelist.", "alert alert-error", 5000);
			}
		});
    }
    else
    {
		set_message_timeout_and_show(errors, "alert alert-error", 5000);
    }
    
}
function set_message_timeout_and_show(message, selected_class, timeout)
{
	window.clearTimeout(g_timeout_id);
	$('#sitelist_msg_box_content').append(message+"<br>");
	$('#sitelist_msg_box').prop('class', selected_class);
	$('#sitelist_msg_box').show();
	g_timeout_id = window.setTimeout(function(){
		$('#sitelist_msg_box').hide();
		$('#sitelist_msg_box_content').html('');
	}, timeout);
}
//function divided into three sections.  One for manual input text box, one for all selectbox, one for only selected text box
//manual input: divides input by line, then by white space and makes sure theres the desired amount of elements.
function move_pack_sites(id, type)
{
    var error = "";
    var box = $("#"+id+"");
    if(type == 1)
    {
		var box_options = box.prop('options');
		var i = 0;
		for(i = 0; i < box_options.length; i++)
		{
			if(box_options[i].value == 'header_tag')
			{
				var apnd_str = '<tr class="ui-state-default ui-selectee">'+
					'<td class="sort" style="width:38px;"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span></td>'+
					'<td class="a_header" style="width:200px;font-weight:bold;">'+$(box_options[i]).text().toUpperCase()+'</td> <td colspan="25" style="width:950px;"></td></tr>';
				$('#uber tbody').append(apnd_str);
			}
			else if(box_options[i].value == 'break_tag')
			{
				var apnd_str = '<tr class="ui-state-default ui-selectee">'+
					'<td class="sort" style="width:38px;"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span></td>'+
					'<td class="a_break" style="width:200px;">(break tag)</td> <td colspan="25" style="width:950px;"></td></tr>';
				$('#uber tbody').append(apnd_str);
			}
			else if(box_options[i].value == 'ignore_tag')
			{
				//do nothing
			}
			else //a site
			{
				var split1 = box_options[i].value.split("|||");
				if(!check_array(split1[0]))
				{
					var split2 = split1[1].split("|");
					var apnd_str = '<tr class="togl ui-state-default ui-selectee">'+
						'<td class="sort" style="width:38px;"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span></td>'+
						'<td class="a_site" style="width:200px;word-wrap:break-word;"><div style="width:187px;word-wrap:break-word;">'+split1[0]+'</div></td>';
					for(var j = 0; j < split2.length - 1; j++)
					{
						if($('#uber thead').children().eq(0).children().eq(j + 2).css('display') == 'none')
						{
							apnd_str += '<td style="display:none;">'+split2[j]+'</td>';
						}
						else
						{
							apnd_str += '<td>'+split2[j]+'</td>';
						}
					}
					apnd_str += '</tr>';
					$('#uber tbody').append(apnd_str);
				}
			}
		}
    }
    else if (type == 0)
    {
		var box_options = box.prop('options');
		var i = 0;
		for(i = 0; i < box_options.length; i++)
		{
			if(box_options[i].selected==1)
			{
				if(box_options[i].value == 'header_tag')
				{
					var apnd_str = '<tr class="ui-state-default ui-selectee">'+
						'<td class="sort" style="width:38px;" style="width:38px;"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span></td>'+
						'<td class="a_header" style="width:200px;font-weight:bold;">'+$(box_options[i]).text().toUpperCase()+'</td> <td colspan="25" style="width:950px;"></td></tr>';
					$('#uber tbody').append(apnd_str);
				}
				else if(box_options[i].value == 'break_tag')
				{
					var apnd_str = '<tr class="ui-state-default ui-selectee">'+
						'<td class="sort" style="width:38px;"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span></td>'+
						'<td class="a_break" style="width:200px;">(break tag)</td> <td colspan="25" style="width:950px;"></td></tr>';
					$('#uber tbody').append(apnd_str);
				}
				else if(box_options[i].value == 'ignore_tag')
				{
					//do nothing
				}
				else
				{
					var split1 = box_options[i].value.split("|||");
					if(!check_array(split1[0]))
					{
						var split2 = split1[1].split("|");
						var apnd_str = '<tr class="togl ui-state-default ui-selectee">'+
							'<td class="sort" style="width:38px;"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span></td>'+
							'<td class="a_site" style="width:200px;word-wrap:break-word;"><div style="width:187px;word-wrap:break-word;">'+split1[0]+'</div></td>';
						for(var j = 0; j < split2.length - 1; j++)
						{
							if($('#uber thead').children().eq(0).children().eq(j + 2).css('display') == 'none')
							{
								apnd_str += '<td style="display:none;">'+split2[j]+'</td>';
							}
							else
							{
								apnd_str += '<td>'+split2[j]+'</td>';
							}
						}
						apnd_str += '</tr>';
						$('#uber tbody').append(apnd_str);	
					}
				}
			}
		}
    }
    reset_table_list();
}
function move_sites(id, type)
{
    var error = "";
    var box = $("#"+id+"");
    if(type == 2)
    {
		var i=0;
		var site_array = $.trim(box.val()).split("\n");
		for(i=0; i < site_array.length; i++)
		{
			var demo_array = site_array[i].split(/\s+/);

			if(demo_array.length > 26 || demo_array.length < 25)
			{
				error += "Incorrect custom site input at line: "+(i+1)+"\n";
			}
			else
			{
				if(!check_array(demo_array[0]))
				{
					if(demo_array.length == 26)
					{
						demo_array.pop();		
					}
					for(var j = 2; j < demo_array.length; j++)
					{
						demo_array[j] = parseFloat(demo_array[j]).toFixed(2).toString();
					}
					demo_array[1] = parseFloat(demo_array[1]).toFixed(4).toString();
					var apnd_str = '<tr class="togl ui-state-default ui-selectee">'+
						'<td class="sort" style="width:38px;"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span></td>'+
						'<td class="a_site" style="width:200px;word-wrap:break-word;"><div style="width:187px;word-wrap:break-word;">'+demo_array[0]+'</div></td>';
					for(var j = 1; j < demo_array.length; j++)
					{
						if($('#uber thead').children().eq(0).children().eq(j + 1).css('display') == 'none')
						{
							apnd_str += '<td style="display:none;">'+demo_array[j]+'</td>';
						}
						else
						{
							apnd_str += '<td>'+demo_array[j]+'</td>';
						}
					}
					apnd_str += '</tr>';
					$('#uber tbody').append(apnd_str);
				}
			}
		}
    }
    else if(type == 1)
    {
		var box_options = box.prop('options');
		var i = 0;
		for(i = 0; i < box_options.length; i++)
		{
			var split1 = box_options[i].value.split("|||");
			if(!check_array(split1[0]))
			{
				var split2 = split1[1].split("|");
				var apnd_str = '<tr class="togl ui-state-default ui-selectee">'+
					'<td class="sort" style="width:38px;"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span></td>'+
					'<td class="a_site" style="width:200px;word-wrap:break-word;"><div style="width:187px;word-wrap:break-word;">'+split1[0]+'</div></td>';
				for(var j = 0; j < split2.length - 1; j++)
				{
					if($('#uber thead').children().eq(0).children().eq(j + 2).css('display') == 'none')
					{
						apnd_str += '<td style="display:none;">'+split2[j]+'</td>';
					}
					else
					{
						apnd_str += '<td>'+split2[j]+'</td>';
					}
				}
				apnd_str += '</tr>';
				$('#uber tbody').append(apnd_str);
			}
		}
    }
    else if(type == 0)
    {
		var box_options = box.prop('options');
		var i = 0;
		for(i = 0; i < box_options.length; i++)
		{
			if(box_options[i].selected==1)
			{
				var split1 = box_options[i].value.split("|||");
				if(!check_array(split1[0]))
				{
					var split2 = split1[1].split("|");
					var apnd_str = '<tr class="togl ui-state-default ui-selectee">'+
						'<td class="sort" style="width:38px;"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span></td>'+
						'<td class="a_site" style="width:200px;word-wrap:break-word;"><div style="width:187px;word-wrap:break-word;">'+split1[0]+'</div></td>';
					for(var j = 0; j < split2.length - 1; j++)
					{
						if($('#uber thead').children().eq(0).children().eq(j + 2).css('display') == 'none')
						{
							apnd_str += '<td style="display:none;">'+split2[j]+'</td>';
						}
						else
						{
							apnd_str += '<td>'+split2[j]+'</td>';
						}
					}
					apnd_str += '</tr>';
					$('#uber tbody').append(apnd_str);
				}
			}
		}
    }
    if(error != "")
    {
		set_message_timeout_and_show(error, "alert alert-error", 5000)
    }
    reset_table_list();
}
function insert_header()
{
    var texty_box = document.getElementById("header_text");
    if(texty_box.value.length > 0 && texty_box.value.length < 80)
    {
		var apnd_str = '<tr class="ui-state-default ui-selectee">'+
			'<td class="sort" style="width:38px;"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span></td>'+
			'<td class="a_header" style="width:200px;font-weight:bold;">'+texty_box.value+'</td> <td colspan="25" style="width:950px;"></td></tr>';
		$('#uber tbody').append(apnd_str);
		reset_table_list();
    }
    else
    {
		set_message_timeout_and_show("invalid header length", "alert", 5000);
    }
}
function insert_page_break()
{
    var apnd_str = '<tr class="ui-state-default ui-selectee">'+
		'<td class="sort" style="width:38px;"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span></td>'+
		'<td class="a_break" style="width:200px;">(break tag)</td> <td colspan="25" style="width:950px;"></td></tr>';
	if ($('#uber').find('tr.ui-selected').length)
	{
	    $(apnd_str).insertAfter($('#uber tr.ui-selected').last());
    }
    else
    {
    	 $('#uber tbody').append(apnd_str);
    }
    reset_table_list();
}
function get_pack_buttons()
{
    $('#pack_button_1').hide(150, function(){
		$('#pack_button_replace').show(150, function(){
			$('#pack_button_new').show(100, function(){
			});
		});
    });
}
function pack_button_replace()
{
    $('#pack_button_replace').hide(150, function(){
		$('#pack_button_new').hide(100, function(){
			$('#pack_button_replace_save').show(50, function() {
				$('#site_pack_drop_replace').show(40, function() {
					$('#pack_button_back').show(30);
				});
			});
		});
    });
}
function pack_button_new()
{
    $('#pack_button_replace').hide(150, function(){
		$('#pack_button_new').hide(100, function(){
			$('#pack_button_new_save').show(50, function() {
				$('#site_pack_text_new').show(40, function() {
					$('#pack_button_back').show(30);
				});
			});
		});
    });
}
function pack_button_back()
{
    $('#pack_button_new_save').hide(50);
    $('#pack_button_replace_save').hide(49);
    $('#site_pack_text_new').hide(48);
    $('#site_pack_drop_replace').hide(47);
    $('#pack_button_back').hide(46, function() {
		$('#pack_button_replace').show(150, function(){
			$('#pack_button_new').show(100, function(){
			});
		});
    });
}
function save_pack_list(type)
{
    var errors = "";
    var multi_array = new Array();
    var rows = $('#uber tbody');//.children();
    rows.children().each(function(i){
		multi_array[i] = new Array();
		if(rows.children().eq(i).children().eq(1).hasClass('a_site'))
		{
			multi_array[i][0] = rows.children().eq(i).children().eq(1).children().eq(0).html();
			for(var j = 2; j < rows.children().eq(i).children().length; j++)
			{
				multi_array[i][j -1] = rows.children().eq(i).children().eq(j).html();
			}
		}
		else if(rows.children().eq(i).children().eq(1).hasClass('a_break'))
		{
			multi_array[i][0] = 'break_tag';
			multi_array[i][1] = '(page break)';
		}
		else if(rows.children().eq(i).children().eq(1).hasClass('a_header'))
		{
			multi_array[i][0] = 'header_tag';
			multi_array[i][1] = rows.children().eq(i).children().eq(1).html();
		}
		else //no class
		{
			
			errors += "Error 0419: wrong class on row at "+i+"\n";
		}
		
    });
    if(errors == "")
    {
		if(type == 'replace')
		{
			var pack_name = "";
			if($.isNumeric( $('#site_pack_drop_replace :selected').val() ))
			{
				var urla = '/proposal_builder/update_create_site_pack/'+$('#site_pack_drop_replace :selected').val();
			}
			else
			{
				set_message_timeout_and_show("Warning 1508: No Pack Selected", "alert", 5000);
			}
		}
		else if(type == 'new')
		{
			var urla = '/proposal_builder/update_create_site_pack/';
			var pack_name = $('#site_pack_text_new').val();
		}
		else
		{
			return;
		}
		$.ajax({
			type: "POST",
			url: urla,
			data: {arrjson: JSON.stringify(multi_array), packname: pack_name},
			async: true
		}).done(function(data) {
			if(data == "false")
			{
				set_message_timeout_and_show("Error 3104: Insert conflict when creating site pack", "alert alert-error", 5000);
			}
			else if(data == "true")
			{
				$('#site_pack').html("");
				pack_button_back();
			}
			else
			{
				$('#site_pack').html("");
				pack_button_back();
				var opt_insert = new Option(pack_name, data);
				var opt_insert2 = new Option(pack_name, data);
				$('#site_pack_drop').append(opt_insert);
				$('#site_pack_drop_replace').append(opt_insert2);
			}
		}).fail(function() {
			set_message_timeout_and_show("Error 3103: Construct error inserting site pack data", "alert alert-error", 5000);
		});
    }
    else
    {
		set_message_timeout_and_show(errors, "alert alert-error", 5000);
    }
}
function delete_selected()
{
    $('tr.ui-selected').remove();
    reset_table_list();
}

function init_tags_select2()
{
	$('#media_targeting_tags_select2').select2({
		placeholder: "Select contextual categories",
		minimumInputLength: 0,
		multiple: true,
		ajax: {
			url: "/media_targeting_tags/ajax_media_targeting_tags/",
			type: 'POST',
			dataType: 'json',
			data: function (term, page) 
			{
				term = (typeof term === "undefined" || term == "") ? "%" : term;
				return {
					q: term,
					page_limit: 10,
					page: page
				};
			},
			results: function (data) {
				return {results: data.result, more: data.more};
			}
		},
		allowClear: true
	});
	$('#media_targeting_tags_select2').on("select2-loaded", function(){
		$("#media_targeting_tags_select2").select2("container").find("ul.select2-choices").sortable({
			containment: 'parent',
			start: function() { $("#media_targeting_tags_select2").select2("onSortStart"); },
			update: function() { $("#media_targeting_tags_select2").select2("onSortEnd"); }
		});
	});
	
	initialize_media_targeting_tags_selection();
	populate_site_multiselect_from_tags();
}
function set_iab_width()
{
	if(g_iab_select2_expanded == false) //expand
	{
		var select2_box = $("#s2id_media_targeting_tags_select2");
		select2_box.css('position', 'absolute');
		select2_box.css('z-index', '10');
		var html_width = $("html").css("width");
		var html_width = html_width.substring(0, html_width.length - 2);
		var new_width = html_width / 1.5;
		select2_box.css('width', new_width + "px");
		$("#iab_select2_container").css('height', select2_box.css('height'));
		$("#iab_select2_expand > i").removeClass('icon-plus');
		$("#iab_select2_expand > i").addClass('icon-minus');
		g_iab_select2_expanded = true;
	}
	else  //contract
	{
		var select2_box = $("#s2id_media_targeting_tags_select2");
		select2_box.css('position', 'relative');
		select2_box.css('z-index', 'auto');
		select2_box.css('width', '320px');
		$("#iab_select2_container").css('height', 'auto');
		$("#iab_select2_expand > i").removeClass('icon-minus');
		$("#iab_select2_expand > i").addClass('icon-plus');
		g_iab_select2_expanded = false;
	}
}
function populate_site_multiselect_from_tags()
{
	var tag_data = $("#media_targeting_tags_select2").select2("data");
	$.ajax({
		async:true,
		type: "POST",
		url: "/proposal_builder/get_sitelist_by_tags",
		data:{tag_data: JSON.stringify(tag_data)},
		dataType: "json",
		success: function(data){
			if(data.is_success == true)
			{
				if(data.data != false)
				{
					var options_string = "";
					$.each(data.data, function(k, v){
						options_string += '<option value="'+v.value+'">'+v.name+'</option>';
					});
					$('#tags_sites').html(options_string);
				}
				else
				{
					$('#tags_sites').html('');
				}
			}
			else
			{
				set_message_timeout_and_show("Error 42241993: Trying to get site tags with Error: "+data.errors, "alert alert-error", 5000);
			}
		},
		error: function(data){
			set_message_timeout_and_show("Error 42257671: Server failed to retrieve tag sites.", "alert alert-error", 5000);
		}
	});
}
function highlight_page_breaks()
{
	var i = 1;
	$('table#uber tbody tr')
		.removeClass('page-start')
		.removeClass('page-end');
	$('table#uber tbody tr').each(function(index, el)
	{
		if (i == 1)
		{
			if ($(el).find('.a_break').length)
			{
				return true;
			}
			$(el).addClass('page-start');
		}
		else if (i == 32 || $(el).find('.a_break').length)
		{
			$(el).addClass('page-end');
			i = 0;
		}
		i++;
	});
}