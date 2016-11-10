
// bulk paste notes start
function paste_bulk_notes (elem, e, notes_type) 
{
    var savedcontent = elem.innerHTML;
    if (e && e.clipboardData && e.clipboardData.getData) 
    {// Webkit - get data from clipboard, put into editdiv, cleanup, then cancel event
        if (/text\/html/.test(e.clipboardData.types)) 
        {
            elem.innerHTML = e.clipboardData.getData('text/html');
        }
        else if (/text\/plain/.test(e.clipboardData.types)) 
        {
            elem.innerHTML = e.clipboardData.getData('text/plain');
        }
        else 
        {
            elem.innerHTML = "";
        }
        waitforpastedata(elem, savedcontent, notes_type);
        if (e.preventDefault) {
                e.stopPropagation();
                e.preventDefault();
        }
        return false;
    }
    else 
    {// Everything else - empty editdiv and allow browser to paste content into it, then cleanup
        elem.innerHTML = "";
        waitforpastedata(elem, savedcontent, notes_type);
        return false;
    }
}

function waitforpastedata(elem, savedcontent, notes_type) 
{
    if (elem.childNodes && elem.childNodes.length > 0) 
    {
        processpaste(elem, savedcontent, notes_type);
    }
    else {
        that = {
            e: elem,
            s: savedcontent
        }
        that.callself = function () {
            waitforpastedata(that.e, that.s, notes_type)
        }
        setTimeout(that.callself,20);
    }
}
//remove 'Opt', add 'Budget $','Budget IMP', in place of budget
var notes_columns_array_campaign=['Code', 'Flight', 'IO', 'BI', 'MPQ', 'URL', 'Ad ID', 'Geo', 'Pop', 'Demo', 'Context', 'Budget $','Budget IMP', 'Cal', 'Note'];
var notes_columns_array_adv=['Account', 'Tracking Tags', 'Notes'];
function processpaste(elem, savedcontent, notes_type) {
    
    var notes_add_array=new Array();
    var notes_columns_array;
    if (notes_type == 1)
    {
        notes_columns_array=notes_columns_array_campaign;
    }
    else if (notes_type == 2)
    {
        notes_columns_array=notes_columns_array_adv;
    }
    var pasteddata = elem.innerHTML;
    var alerted = localStorage.getItem('alerted') || '';
       
    var re =  /[^A-Za-z0-9~`\!@#\$%\^&\*\(\)\-_\+\=\{\[\}\]\|\\\:·–;\'\"<\,>\.\?\/\n\r\s\t]/g;	
	if(re.test(pasteddata) === true)
	{	    
	    alert('Notes contains illegal characters! Please correct and paste again.');	    
	    if(alerted != 'yes') 
	    {
		localStorage.setItem('alerted','yes');
	    }
	    return false;
	}
	var j=0;
 	new_notes_string="";
 	var all_cols_blank_check_flag=true;
    skip_legacy_columns_flag=false;
    if (elem.getElementsByTagName('td').length % 18 == 0)
    {
        skip_legacy_columns_flag=true;
    }
    var k=0;
    var legacy_date="";
  	for (var i=0; i < elem.getElementsByTagName('td').length; )
	{
        var cell_data = elem.getElementsByTagName('td')[i].innerHTML;
        
        if (skip_legacy_columns_flag && (k==1 || k ==2 || k ==3))
        {
            if (k==2)
            {
                legacy_date=cell_data;
            }
            
            // To get time and append it to the date
            if (k==3)
            {
                legacy_date=legacy_date+' '+cell_data;
            }

            i++;
            k++;
            continue;
        }	
        
        if (cell_data.indexOf(' href="') != -1)
        {   var ori_cell_data = cell_data;
            var start = cell_data.indexOf(' href="')+7;
            var end = cell_data.indexOf('"', start);
            cell_data = cell_data.substring(start, end);
            
        }
		new_notes_string += notes_columns_array[j] + ":: " + cell_data + "^^\n";
		if (cell_data != undefined && cell_data != "")
		{
			all_cols_blank_check_flag=false;
		}
        
		if ((j+1) >= notes_columns_array.length) {
			if (!all_cols_blank_check_flag)
	    	{
                //notes_add_array[notes_add_array.length]=new_notes_string;
                document.getElementById("new_notes").value=new_notes_string;
                add_new_note(notes_type, legacy_date);
                sleep(1000);
                k=0;
            }
	    	new_notes_string="";
	    	var all_cols_blank_check_flag=true;
	    	j=-1;
            k=-1;
		}
		j++;
        i++;
        k++;
	}

    /*for (var i=0; i < notes_add_array.length; i++)
    {
        document.getElementById("new_notes").value=notes_add_array[i];
        add_new_note(notes_type);
        sleep(1000);
    }*/
}

function sleep(milliseconds) 
{
  var start = new Date().getTime();
  for (var i = 0; i < 1e7; i++) {
    if ((new Date().getTime() - start) > milliseconds){
      break;
    }
  }
}

    // bulk paste notes end
    var before_notes_text_campaign="Code:: ^^\nFlight:: ^^\nIO::^^\nBI:: ^^\nMPQ:: ^^\nURL:: ^^\nAd ID:: ^^\nGeo:: ^^\nPop:: ^^\nDemo:: ^^\nContext:: ^^\nBudget $:: ^^\nBudget IMP:: ^^\nCal:: ^^\nNote::";
    var before_notes_text_adv="Account:: ^^\Tracking Tags:: ^^\nNotes::";
    var after_notes_text=" ^^\n";
    var before_notes_text=before_notes_text_campaign;
    function add_new_note(object_type_id, legacy_date)
    {
        
        if (document.getElementById("new_notes") == undefined)
            return;
        var new_note = document.getElementById("new_notes").value;
        
        var before_notes_text="";
        if (object_type_id == 1)
        {
            before_notes_text=before_notes_text_campaign;
        }
        else if (object_type_id == 2)
        {
            before_notes_text=before_notes_text_adv;
        }

        if (new_note.indexOf("^^") == -1)
        {
            new_note = before_notes_text + new_note + after_notes_text;
        }
        new_note = new_note.replace(/"/g, "'");

        var object_id="";
        if (object_type_id == "1")
        {
            if (document.getElementById("campaign_select") == undefined)
                return;
        
            object_id = document.getElementById("campaign_select").value;
        }
        else if (object_type_id == "2")
        {
            if (document.getElementById("advertiser_select") == undefined)
                return;
        
            object_id = document.getElementById("advertiser_select").value;
	}
	var data_url = "/campaign_setup/add_new_note";
            $.ajax({
            type: "POST",
            url: data_url,
            async: true,
            data: {object_id: object_id, new_note_text: new_note, object_type_id: object_type_id, legacy_date: legacy_date},
            dataType: 'html',
            error: function(temp){
                document.getElementById('adgroup_modal_detail_body').innerHTML = temp;
                return 'error';
            },
            success: function(msg){ 
                var returned_data = jQuery.parseJSON(msg);
                if (returned_data.is_success)
                {
                    fetch_notes_for_campaign(object_type_id);
                    document.getElementById('alert_notes_span').innerHTML="<small>New Note added</small>";
                }
                else
                {
                    document.getElementById('alert_notes_span').innerHTML="<small>Couldn't add a new Note. Try again!</small>";
                }
            }
        });
    }

    function fetch_notes_for_campaign(user)
    {
	var table_string = '&nbsp;';
	if(user == undefined)
	{
	    user == '2';
	}
	if (document.getElementById("campaign_select") == undefined)
            return;
        var campaign_id = document.getElementById("campaign_select").value;
        if (document.getElementById("advertiser_select") == undefined)
            return;
        var advertiser_select = document.getElementById("advertiser_select").value;
        if ((campaign_id != '' &&  campaign_id != 'new') || (user == '1' && user != 'new_cam' && user != 'new'))
	{
	    document.getElementById("notes_tt_div").style.display= "block";	    
	}
	else if ((campaign_id == '' ||  campaign_id == 'new') && (user != '1' || user == 'new_cam'))
	{
	    document.getElementById("notes_tt_div").style.display= "none";	    
	}
         var data_url = "/campaign_setup/get_notes_for_campaign";
            $.ajax({
            type: "POST",
            url: data_url,
            async: true,
            data: {cid: campaign_id, advertiser_id: advertiser_select},
            dataType: 'html',
            error: function(temp) {
                document.getElementById('adgroup_modal_detail_body').innerHTML = temp;
                return 'error';
            },
            success: function(msg) {
                
                var returned_data = jQuery.parseJSON(msg);
                if (returned_data.is_success)
                {
		    if ((campaign_id != '' &&  campaign_id != 'new') || (user == '1' && user != 'new_cam'))
		    {
			table_string += "&nbsp;&nbsp;&nbsp;Campaign Notes&nbsp;"+
                    "<div id='div' style='background-color:#FAFA7D' contenteditable='true' onpaste='paste_bulk_notes(this, event, 1)'>Click here and Paste (ctrl v) to Add Notes in Bulk to this Campaign</div>"+
                    "<br><table style='width:1950px;font-size:9px' class='table table-bordered table-condensed' id='campaign_notes_table'><thead><tr><th><button type='button' class='btn btn-mini btn-success' onclick='create_excel(\"campaign_notes_table\", \"notes_campaign\")' ><span><i class='icon-remove icon-download'></i></span></button></th><th>Code</th><th>User</th><th>Date</th><th>Time</th><th width='300'>Flight</th>"+
                    "<th>IO</th><th>BI</th><th>RFP</th><th>URL</th><th>Ad ID</th><th width='300'>Geo</th>" + 
                    "<th>Pop</th><th>Demo</th><th>Context</th><th>Bdgt $</th><th>Bdgt Imp</th><th>Cal</th><th width='300'>Note</th></tr></thead><tbody>";

                    for(var i=0; i < returned_data["notes_data"]["notes_data_campaign"].length; i++)
                    {
                        var notes_id=returned_data["notes_data"]["notes_data_campaign"][i]["id"];
                        var notes_text=returned_data["notes_data"]["notes_data_campaign"][i]["notes_text"];
                        var created_date = returned_data["notes_data"]["notes_data_campaign"][i]["created_date"];
			var created_date_arr = created_date.split(" ");
			var created_date_val = created_date_arr[0];
			var created_time = created_date_arr[1].split(":");
                        var created_time_sec = created_time[0]+":"+created_time[1];
                        var username = returned_data["notes_data"]["notes_data_campaign"][i]["username"];
                        var is_imp_flag = returned_data["notes_data"]["notes_data_campaign"][i]["is_important_flag"];
                        
                        var bad_button = '<button type="button" class="btn btn-mini btn-danger" onclick="update_bad_flag('+notes_id+' , 1);"><span><i class="icon-remove icon-white"></i></span></button>';
                        var imp_button = '<button type="button" class="btn btn-mini btn-warning" onclick="update_imp_flag('+notes_id+', 1);"><span><i class="icon-check icon-white"></i></span></button>';
                        var imp_class="";
                        if (is_imp_flag == '1')
                        {
                           imp_class='style="background-color:#03FC03"'; 
                        }

                        var sub_array = notes_text.split("^^\n");
 			var code_val = sub_array[0].split("::");
 			table_string += "<tr><td "+imp_class+">"+imp_button+"<br><br>"+bad_button+"</td><td>"+code_val[1]+"</td><td>"+username+"</td><td>"+created_date_val+"</td><td>"+created_time_sec+"</td>";
                        for (var j=1; j < 15; j++)
                        {
                            var each_cell=sub_array[j];
                            if (each_cell != undefined)
                            {
                                var each_row_array=each_cell.split("::");
                                width="75";
                                if (j==1 || j== 7 || j == 14) //flight, geo, note
                                {
				    width="300";
                                }
				if (j == 8) // for POP values format
				{
				    each_row_array[1] = each_row_array[1].replace(/\B(?=(\d{3})+(?!\d))/g, ",");			   
				}
				if (j==1) // for Flight dates/impressions format
				{
				    var concat_val = "";
				    var tot_imp = 0;
				    var concat_flight = "";
				    var each_val_split = each_row_array[1].split("\n");
				    var count = each_val_split.length; 
				    for(var t=0; t < count; t++)
				    {
					var each_val_split_tab = each_val_split[t].split("\t");
					var add_format_val = each_val_split_tab[0].concat(" - ",each_val_split_tab[1],": " );
					if(each_val_split_tab[2] !== undefined) 
					{
					    var add_format_val_concat = each_val_split_tab[2].concat(" impressions " ,"\n");
					    concat_val += add_format_val.concat(add_format_val_concat);
					    var format_number = each_val_split_tab[2].replace(",","");
					    tot_imp += parseFloat(format_number);					    
					}					
				    }
				    var total_string = "Total Impressions: ";
				    tot_imp =  tot_imp.toString();
				    var tot_imp_format = tot_imp.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
				    var add_format_tot = total_string.concat(tot_imp_format,"\n\n");
				    concat_flight += add_format_tot.concat(concat_val);				    
				    if(tot_imp !== '0')
				    {
				        each_row_array[1] = concat_flight;
				    }
				}
				table_string+="<td style='max-width:"+width+"px;word-wrap:break-word;'><div class='flexcroll'>"+each_row_array[1].replace(/\n/mg, "<br />")+"</div></td>";
			    }
			}
                        table_string+="</tr>";
                    }
                    table_string+="</tbody></table>";		   
		}   
		if (user == '2' || user == '1' || user != 'new')
		{
		    table_string += "<br>&nbsp;&nbsp;&nbsp;&nbsp;Advertiser Notes:&nbsp;"+
                    "<div id='div' style='background-color:#FAFA7D' contenteditable='true' onpaste='paste_bulk_notes(this, event, 2)'>Click here and Paste (ctrl v) to Add Notes in Bulk to the Advertiser</div>"+
                    "<br><table style='width:100%;font-size:9px' class='table table-bordered table-condensed' id='adv_notes_table'><thead><tr><th><button type='button' class='btn btn-mini btn-success' onclick='create_excel(\"adv_notes_table\", \"notes_adv\")' ><span><i class='icon-remove icon-download'></i></span></button></th><th>User</th><th>Date</th><th width='28%'>Account</th><th width='28%'>Tracking Tags</th><th width='28%'>Notes</th></tr></thead><tbody>";

                    for(var i=0; i < returned_data["notes_data"]["notes_data_adv"].length; i++)
                    {
                        var notes_id=returned_data["notes_data"]["notes_data_adv"][i]["id"];
                        var notes_text=returned_data["notes_data"]["notes_data_adv"][i]["notes_text"];
                        var created_date = returned_data["notes_data"]["notes_data_adv"][i]["created_date"];
                        var created_date_arr = created_date.split(":");			
                        var created_date_sec = created_date_arr[0]+":"+created_date_arr[1];
                        
                        var username = returned_data["notes_data"]["notes_data_adv"][i]["username"];
                        var is_imp_flag = returned_data["notes_data"]["notes_data_adv"][i]["is_important_flag"];
                        
                        var bad_button = '<button type="button" class="btn btn-mini btn-danger" onclick="update_bad_flag('+notes_id+' , 2);"><span><i class="icon-remove icon-white"></i></span></button>';
                        var imp_button = '<button type="button" class="btn btn-mini btn-warning" onclick="update_imp_flag('+notes_id+', 2);"><span><i class="icon-check icon-white"></i></span></button>';
                        var imp_class="";
                        if (is_imp_flag == '1')
                        {
                           imp_class='style="background-color:#03FC03"'; 
                        }

                        table_string += "<tr ><td "+imp_class+">"+imp_button+"<br><br>"+bad_button+"</td><td>"+username+"</td><td>"+created_date_sec+"</td>";
                        var sub_array = notes_text.split("^^\n");
                        for (var j=0; j < 3; j++)
                        {
                            var each_cell=sub_array[j];
                            if (each_cell != undefined)
                            {
                                var each_row_array=each_cell.split("::");
                                table_string+="<td><div class='flexcroll'>"+each_row_array[1]+"</div></td>";
                            }
                        }
                        table_string+="</tr>";
                    }
                    table_string+="</tbody></table>";
                   }
                    document.getElementById('notes_table_div').innerHTML = table_string;
                }
                else
                {
                    document.getElementById('alert_notes_span').innerHTML="<small>No notes found</small>";
                }
            }
        });
    }

    function reset_notes_textarea()
    {       
        document.getElementById('new_notes').value = before_notes_text+after_notes_text;
        document.getElementById('alert_notes_span').innerHTML="<small>Notes reset</small>";
    }

    var timer;//this is used for the async loading bar
    var loader_off_secret = null;
    var td_advertiser = 0;
    var adgroups = [];

    function update_bad_flag(notes_id , notes_type)
    {
        if (notes_id == undefined)
            return;

        if(confirm("Please confirm that you want to delete this Note?")) 
        {   
             var data_url = "/campaign_setup/update_note_bad_flag";
                $.ajax({
                type: "POST",
                url: data_url,
                async: true,
                data: {notes_id: notes_id},
                dataType: 'html',
                error: function(){
                    return 'error';
                },
                success: function(msg){
                    var returned_data = jQuery.parseJSON(msg);
                    if (returned_data.is_success)
                    {
                        fetch_notes_for_campaign(notes_type);
                        document.getElementById('alert_notes_span').innerHTML="<small>Note deleted</small>";
                    }
                    else
                    {
                        document.getElementById('alert_notes_span').innerHTML="<small>Couldn't delete Note. Try again!</small>";
                    }
                }
            });
        }
    }

    function update_imp_flag(notes_id ,notes_type)
    {
        if (notes_id == undefined)
            return;

        if(confirm("Please confirm that you want to Toggle Important flag for this Note?")) 
        {   
             var data_url = "/campaign_setup/update_imp_flag";
                $.ajax({
                type: "POST",
                url: data_url,
                async: true,
                data: {notes_id: notes_id},
                dataType: 'html',
                error: function(){
                    return 'error';
                },
                success: function(msg){
                    var returned_data = jQuery.parseJSON(msg);
                    if (returned_data.is_success)
                    {
                        fetch_notes_for_campaign(notes_type);
                        document.getElementById('alert_notes_span').innerHTML="<small>Important flag changed</small>";
                    }
                    else
                    {
                        document.getElementById('alert_notes_span').innerHTML="<small>Couldn't change Note. Try again!</small>";
                    }
                }
            });
        }
    }

 

 $('[data-toggle=popover]').popover({
  trigger:"click"
});

$('[data-toggle=popover]').on('click', function (e) {
   $('[data-toggle=popover]').not(this).popover('hide');
});


//standard
function create_excel(table_name, table_display)
{   
    $table=$("#"+table_name);
    var csv = $table.table2CSV({
            delivery: 'value'
    });
    console.log(csv);
    download(csv, table_display +".csv", "text/csv");
} 

function download(strData, strFileName, strMimeType) {
    var D = document,
        a = D.createElement("a");
        strMimeType= strMimeType || "application/octet-stream";

console.log(1);
    if (navigator.msSaveBlob) { // IE10
        return navigator.msSaveBlob(new Blob([strData], {type: strMimeType}), strFileName);
    } /* end if(navigator.msSaveBlob) */

    console.log(2);
    if ('download' in a) { //html5 A[download]
        a.href = "data:" + strMimeType + "," + encodeURIComponent(strData);
        a.setAttribute("download", strFileName);
        a.innerHTML = "downloading...";
        D.body.appendChild(a);
        setTimeout(function() {
            a.click();
            D.body.removeChild(a);
        }, 66);
        return true;
    } /* end if('download' in a) */

console.log(3);
    //do iframe dataURL download (old ch+FF):
    var f = D.createElement("iframe");
    D.body.appendChild(f);
    console.log(f);
    f.src = "data:" +  strMimeType   + "," + encodeURIComponent(strData);

    setTimeout(function() {
        D.body.removeChild(f);
    }, 333);
    return true;
} /* end download() */

//table2csv

jQuery.fn.table2CSV = function(options) {
    var options = jQuery.extend({
        separator: ',',
        header: [],
        delivery: 'popup' // popup, value
    },
    options);

    var csvData = [];
    var headerArr = [];
    var el = this;

    //header
    var numCols = options.header.length;
    var tmpRow = []; // construct header avalible array

    if (numCols > 0) {
        for (var i = 0; i < numCols; i++) {
            tmpRow[tmpRow.length] = formatData(options.header[i]);
        }
    } else {
        $(el).filter(':visible').find('th').each(function() {
            if ($(this).css('display') != 'none') tmpRow[tmpRow.length] = formatData($(this).html());
        });
    }

    row2CSV(tmpRow);

    // actual data
    $(el).find('tr').each(function() {
        var tmpRow = [];
        $(this).filter(':visible').find('td').each(function() {
            if ($(this).css('display') != 'none') tmpRow[tmpRow.length] = formatData($(this).html());
        });
        row2CSV(tmpRow);
    });
    if (options.delivery == 'popup') {
        var mydata = csvData.join('\n');
        return popup(mydata);
    } else {
        var mydata = csvData.join('\n');
        return mydata;
    }

    function row2CSV(tmpRow) {
        var tmp = tmpRow.join('') // to remove any blank rows
        // alert(tmp);
        if (tmpRow.length > 0 && tmp != '') {
            var mystr = tmpRow.join(options.separator);
            csvData[csvData.length] = mystr;
        }
    }
    function formatData(input) {
        // replace " with â€œ
        var regexp = new RegExp(/["]/g);
        var output = input.replace(regexp, "â€œ");
        //HTML
        var regexp = new RegExp(/\<[^\<]+\>/g);
        var output = output.replace(regexp, "");
        if (output == "") return '';
        return '"' + output + '"';
    }
    function popup(data) {
        var generator = window.open('', 'csv', 'height=400,width=600');
        generator.document.write('<html><head><title>CSV</title>');
        generator.document.write('</head><body >');
        generator.document.write('<textArea cols=70 rows=15 wrap="off" >');
        generator.document.write(data);
        generator.document.write('</textArea>');
        generator.document.write('</body></html>');
        generator.document.close();
        return true;
    }
};
//table2csv encodeURIComponent

// Automated notes generation
function generate_campaign_notes()
{
    var campaign_id = $("#campaign_select").val();
    var flights_data = $("#timeseries_pro").val();
    var geo_data = $("#zip_list_edit").val();
    var landing_page_url = $("#campaign_landing_page_url").val();
    var campaign_name = $("#campaign_select > option[value='"+campaign_id+"']").html();
    var campaign_region_product = campaign_name.split(' - ');
    var campaign_region = campaign_region_product[0];
    var campaign_product = campaign_region_product[1];

    var data_url = "/campaign_setup/generate_campaign_notes";
    $.ajax({
        type: "POST",
        url: data_url,
        async: true,
        data: {
            campaign_id: campaign_id, 
            geo_data: geo_data, 
            campaign_region:campaign_region, 
            campaign_product:campaign_product
        },
        dataType: 'json',
        error: function(jqXHR, textStatus, errorThrown)
        {
            document.getElementById('alert_notes_span').innerHTML="<small>Couldn't generate Note.</small>";
            return 'error';
        },
        success: function(returned_data)
        {
            var io_id = (returned_data.io_id != '') ? returned_data.io_id : '';
            var population = (returned_data.population != '') ? returned_data.population : '';
            var context = (returned_data.context != '') ? returned_data.context : '';
            var ad_version_id = (returned_data.ad_version_id != '') ? returned_data.ad_version_id.replace(/<br\s*\/?>/mg, "\n") : '';
            var mpq_id = (returned_data.mpq_id != '') ? returned_data.mpq_id : '';
            var bi_id = (returned_data.bi_id != '') ? returned_data.bi_id : '';
            var demo = (returned_data.demo != '') ? returned_data.demo.replace(/<br\s*\/?>/mg, "\n") : '';
            
            if (returned_data.is_success)
            {
                var generated_notes_data = "Code:: Launch ^^\nFlight:: "+flights_data+
                    "^^\nIO:: "+io_id+"^^\nBI:: "+bi_id+
                    "^^\nMPQ:: "+mpq_id+"^^\nURL:: "+landing_page_url+
                    "^^\nAd Version IDs:: "+ad_version_id+"^^\nGeo:: "+geo_data+
                    "^^\nPop:: "+population+"^^\nDemo:: "+demo+
                    "^^\nContext:: "+context+
                    "^^\nBudget $:: ^^\nBudget IMP:: ^^\nCal:: ^^\nNote:: ";    
                document.getElementById("new_notes").value = generated_notes_data;
            }
            else
            {
                document.getElementById('alert_notes_span').innerHTML="<small>Couldn't generate Note.</small>";
            }
        }
    });
    
}
