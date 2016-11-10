    <!DOCTYPE html>
    <html id="the_code"><!--class="custom_style">-->
    <head>
    <title>Creative Uploader</title>
    <link rel="shortcut icon" href="/images/icon/creative_upload.ico">
    <meta name="viewport" content="width=device-width">    
    
    <!-- Bootstrap CSS Toolkit styles -->
    <!--<link rel="stylesheet" href="http://blueimp.github.com/cdn/css/bootstrap.min.css">-->
    
    <!-- Generic page styles -->
    <link rel="stylesheet" href="/assets/ad_link_3000/css/uploader-style.css">
    <!-- Bootstrap styles for responsive website layout, supporting different screen sizes -->
    <link rel="stylesheet" href="/css/creative_uploader/bootstrap-responsive.min.css">
    <!-- Bootstrap CSS fixes for IE6 -->
    <!--[if lt IE 7]><link rel="stylesheet" href="/css/creative_uploader/bootstrap-ie6.min.css"><![endif]-->
    <!-- Bootstrap Image Gallery styles -->
    <link rel="stylesheet" href="/css/creative_uploader/bootstrap-image-gallery.min.css">
    <!--m@-->
    <!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
    <link rel="stylesheet" href="/assets/ad_link_3000/css/jquery.fileupload-ui.css">




    
    <!--<link href="/assets/ad_link_3000/css/ajaxfileupload.css?'v='.<?php echo rand(); ?>" type="text/css" rel="stylesheet">-->
    <script type="text/javascript" src="/assets/ad_link_3000/js/jquery.js"></script>
    <script type="text/javascript" src="/assets/ad_link_3000/js/ajaxfileupload.js"></script>
    <link rel="stylesheet" href="/assets/ad_link_3000/css/bootstrap.min.css">
    <link href="/css/creative_upload.css" type="text/css" rel="stylesheet">
    <script type="text/javascript"> 

window.onbeforeunload = function() {
    while(true)
    {
	jQuery.ajax({
	    async:false,
	    type: "GET",
	    url: "<?php echo base_url();?>creative_uploader/unlink_key",
	    success: function(data, textStatus, jqXHR) {
		//alert('success!');
	    },
	    error: function(data, textStatus, jqXHR) {
		alert("Error 8893: Folder unlink failure on window close");
	    }
	});
	return;
    }
};
//$(document).ready(function() {
    //$('a[rel!=ext]').click(function() { window.onbeforeunload = null; });
    //$('form').submit(function() { window.onbeforeunload = null; });
//    });
function initialize()
{
    build_adset();
}
function build_adset()
{
    xmlhttp1 = new XMLHttpRequest();
    xmlhttp1.open("POST", "<?php echo base_url();?>creative_uploader/build_adsets/", false);
    xmlhttp1.send();
    document.getElementById("adset_dropdown").innerHTML = $.trim(xmlhttp1.responseText);
}
function dropdown_check(value)
{
    if(value == "select_new_dropdown_adset_insert")
    {
	document.getElementById("new_adset_input").style.visibility="visible";
	//document.getElementById("new_adset_input").innerHTML = '<h2>Create New Adset</h2><input id="new_adset_box" type="text"></input><br><input type="button" value="Submit" name="Submit" onclick="insert_adset()">';
    }
    else if(value == "none")
    {
	document.getElementById("new_adset_input").style.visibility="hidden";
    }
    else
    {
	document.getElementById("new_adset_input").style.visibility="hidden";
	$("#output_zone").html("<img id=\"loading_gif\"src=\"<?php echo base_url('images/creative_uploader/loading.gif'); ?>\" />");
	//output zone with database data
	var adset = document.getElementById("adset_select").options[document.getElementById("adset_select").selectedIndex].text;
	jQuery.ajax({
	    async:true,
	    type: "POST",
	    url: "<?php echo base_url();?>creative_uploader/populate_from_existing_adset/"+value+"",
	    dataType: "html",
	    success: function(data, textStatus, jqXHR) {
		$("#output_zone").html(data);
		document.getElementById("output_adset_name").innerHTML = adset;
		jQuery.ajax({
		    async:true,
		    type: "POST",
		    url: "<?php echo base_url();?>creative_uploader/get_versions/"+value+"",
		    dataType: "html",
		    success: function(data){
			$('#adset_version_select').html(data);
		    },
		    error: function(data) {
			alert("Error 1887: Failed to get adset versions");
		    }
		});
	    },
	    error: function(data, textStatus, jqXHR) {
		alert("Error 1888: Failed to populate from existing data");
	    }
	});
    }
}
function setSelectedIndex(s, v)
{
    for( var i = 0; i < s.options.length; i++) {
	if( s.options[i].value == v) {
	    s.options[i].selected = true;
	    return;
	}
    }
}
function insert_adset()
{
    var text = document.getElementById('new_adset_box').value;
    if(text == "" || text == undefined || text == null)
    {
	//do nothing
    }
    else
    {
	var params="text="+JSON.stringify(text);
	xmlhttp = new XMLHttpRequest();
	xmlhttp.open("POST", "<?php echo base_url();?>creative_uploader/insert_new_adset/", false);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlhttp.setRequestHeader("Content-length", params.length);
	xmlhttp.setRequestHeader("Connection", "close");
	xmlhttp.send(params);
	if(xmlhttp.responseText != "0")
	{
	    build_adset();
	    setSelectedIndex(document.getElementById("adset_select"), $.trim(xmlhttp.responseText));
	    document.getElementById("new_adset_input").style.visibility="hidden";
	    $("#output_zone").html("<img id=\"loading_gif\"src=\"<?php echo base_url('images/creative_uploader/loading.gif'); ?>\" />");
	    //output zone with database data
	    var adset = document.getElementById("adset_select").options[document.getElementById("adset_select").selectedIndex].text;
	    var value = document.getElementById("adset_select").options[document.getElementById("adset_select").selectedIndex].value;
	    jQuery.ajax({
		async:true,
			type: "POST",
			url: "<?php echo base_url();?>creative_uploader/populate_from_existing_adset/"+value+"",
			dataType: "html",
			success: function(data, textStatus, jqXHR) {
			$("#output_zone").html(data);
			document.getElementById("output_adset_name").innerHTML = adset;
		    },
			error: function(data, textStatus, jqXHR) {
			alert("Error 1889: Failed to populate from existing data");
		    }
		});
	    
	}
	else
	{
	    alert("Warning 1216 Duplicate Entry: '"+text+"'");
	}
    }
}
function perform_action()
{
    //document.getElementById("output_zone").innerHTML = "";
    var alert_string = '';
    //document.getElementById("error_message").innerHTML="";
    //document.getElementById("success_message").innerHTML="";
    var select_box = document.getElementById("adset_select").value;
    if(select_box == "select_new_dropdown_adset_insert" || select_box == "none")
    {
	var alert_string = 'Warning 1552: <br> No adset selected';
	document.getElementById("output_zone").innerHTML = alert_string;
    }
    else
    {
	var adset = document.getElementById("adset_select").options[document.getElementById("adset_select").selectedIndex].text;
	var adset_id = document.getElementById("adset_select").options[document.getElementById("adset_select").selectedIndex].value;
	jQuery.ajax({
	    async:true,
	    type: "POST",
	    //url: "<?php echo base_url();?>creative_uploader/find_variables/"+adset_id+"",
	    url: "<?php echo base_url();?>creative_uploader/check_exists/"+adset_id+"",
	    dataType: "html",
	    success: function(data, textStatus, jqXHR) {
		if(data == 0)
		{
		    insert_replace();  
		}
		else
		{
		    $('#modal_confirm_box').modal();
		    $("#confirm_box_body_h").html(data);
		    document.getElementById("output_adset_name").innerHTML = adset;
		}
	    },
	    error: function(data, textStatus, jqXHR) {
		alert("Error 0399: Data comparison time out");
	    }
	});
    }
}
function insert_replace()
{
    $("#output_zone").html("<img id=\"loading_gif\"src=\"<?php echo base_url('images/creative_uploader/loading.gif'); ?>\" />");
    var alert_string = "";
    var adset = document.getElementById("adset_select").options[document.getElementById("adset_select").selectedIndex].text;
    var adset_id = document.getElementById("adset_select").options[document.getElementById("adset_select").selectedIndex].value;
    jQuery.ajax({
	async:true,
	type: "POST",
	url: "<?php echo base_url();?>creative_uploader/find_variables/"+adset_id+"",
	dataType: "html",
	success: function(data, textStatus, jqXHR) {
	    $("#output_zone").html(data);
	    document.getElementById("output_adset_name").innerHTML = adset;
	},
	error: function(data, textStatus, jqXHR) {
	    alert("Critical Error 0474: Data insertion time out");
	}
    });
}
</script>
    </head>
    <body onload="initialize()">  
    <form id="fileupload" action="/creative_uploader/multi_upload/" method="POST" enctype="multipart/form-data" class="styled_file_upload_form">
    <div class="adset_select"><h3>Adsets</h3>
    <div id="adset_dropdown"></div>
    <div id="new_adset_input" style="visibility:hidden;"><h3>Create New Adset</h3><input id="new_adset_box" type="text"></input><br><input type="button" class="btn" value="Submit" name="Submit" onclick="insert_adset()"></div>
    </div>
     
</div>

    <div class="action_div">
    <input class="btn-large btn-success" type="button" value="Load Assets &raquo;&raquo;"  onclick="perform_action();"/>
    </div>



    <div class="creative_div"><h3>CREATIVE ASSETS</h3>
    <!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
    <div class="row fileupload-buttonbar">
    <div class="span7">
    <!-- The fileinput-button span is used to style the file input field as button -->
    <span class="btn btn-success fileinput-button">
    <i class="icon-plus icon-white"></i>
    <span>Add files...</span>
    <input type="file" name="files[]" multiple>
    </span>
    <button type="submit" class="btn btn-primary start">
    <i class="icon-upload icon-white"></i>
    <span>Start upload</span>
    </button>
    <button type="reset" class="btn btn-warning cancel">
    <i class="icon-ban-circle icon-white"></i>
    <span>Cancel upload</span>
    </button>
    <button type="button" class="btn btn-danger delete">
    <i class="icon-trash icon-white"></i>
    <span>Delete</span>
    </button>
    <input type="checkbox" class="toggle">
    </div>
    <!-- The global progress information -->
    <div class="span5 fileupload-progress fade">
    <!-- The global progress bar -->
    <div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
    <div class="bar" style="width:0%;"></div>
    </div>
    <!-- The extended global progress information -->
    <div class="progress-extended">&nbsp;</div>
    </div>
    </div>
    <!-- The loading indicator is shown during file processing -->
    <div class="fileupload-loading"></div>
    <br>
    <!-- The table listing the files available for upload/download -->
    <table role="presentation" class="table table-striped"><tbody class="files" data-toggle="modal-gallery" data-target="#modal-gallery"></tbody></table>
    </div>
    </form>

    <div id ="output_zone" class="styled_output_zone">
    <font color="red"><div id="error_message"></div></font>
    <font color="white"><div id="success_message"></div></font>
    </div>

    <!-- modal popup for asset overwrite confirmation -->
    <div id="modal_confirm_box" class="modal hide fade">
      <div id="confirm_box_header" class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="false">&times;</button>
	<h3>Overwrite Existing Files?</h3>
      </div>
      <div id="confirm_box_body" class="modal-body">
	<h5 id="confirm_box_body_h">There is nothing here...</h5>
      </div>

      <div id="confirm_box_footer" class="modal-footer">
	<a href="#" id="sub_popup" class="btn btn-primary" data-dismiss="modal" onclick="insert_replace();">Overwrite All</a>
	<a href="#" class="btn" data-dismiss="modal">Let Me Think About That</a>

      </div>
    </div>

    <!-- modal-gallery is the modal dialog used for the image gallery -->
    <div id="modal-gallery" class="modal modal-gallery hide fade" data-filter=":odd">
    <div class="modal-header">
    <a class="close" data-dismiss="modal">&times;</a>
    <h3 class="modal-title"></h3>
    </div>
    <div class="modal-body"><div class="modal-image"></div></div>
    <div class="modal-footer">
    <a class="btn modal-download" target="_blank">
    <i class="icon-download"></i>
    <span>Download</span>
    </a>
    <a class="btn btn-success modal-play modal-slideshow" data-slideshow="5000">
    <i class="icon-play icon-white"></i>
    <span>Slideshow</span>
    </a>
    <a class="btn btn-info modal-prev">
    <i class="icon-arrow-left icon-white"></i>
    <span>Previous</span>
    </a>
    <a class="btn btn-primary modal-next">
    <span>Next</span>
    <i class="icon-arrow-right icon-white"></i>
    </a>
    </div>
    </div>


    <!-- The template to display files available for upload -->
    <script id="template-upload" type="text/x-tmpl">
    {% for (var i=0, file; file=o.files[i]; i++) { %}
     <tr class="template-upload fade">
     <td class="preview"><span class="fade"></span></td>
     <td class="name"><span>{%=file.name%}</span></td>
     <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>
     {% if (file.error) { %}
      <td class="error" colspan="2"><span class="label label-important">{%=locale.fileupload.error%}</span> {%=locale.fileupload.errors[file.error] || file.error%}</td>
      {% } else if (o.files.valid && !i) { %}
      <td>
      <div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="bar" style="width:0%;"></div></div>
      </td>
      <td class="start">{% if (!o.options.autoUpload) { %}
			 <button class="btn btn-primary">
			 <i class="icon-upload icon-white"></i>
			 <span>{%=locale.fileupload.start%}</span>
			 </button>
			 {% } %}</td>
      {% } else { %}
      <td colspan="2"></td>
      {% } %}
     <td class="cancel">{% if (!i) { %}
			 <button class="btn btn-warning">
			 <i class="icon-ban-circle icon-white"></i>
			 <span>{%=locale.fileupload.cancel%}</span>
			 </button>
			 {% } %}</td>
     </tr>
     {% } %}
</script>
    <!-- The template to display files available for download -->
    <script id="template-download" type="text/x-tmpl">
    {% for (var i=0, file; file=o.files[i]; i++) { %}
     <tr class="template-download fade" >
     {% if (file.error) { %}
      <td></td>
      <td class="name"><span>{%=file.name%}</span></td>
      <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>
      <td class="error" colspan="2"><span class="label label-important">{%=locale.fileupload.error%}</span> {%=locale.fileupload.errors[file.error] || file.error%}</td>
      {% } else { %}
      <td class="preview">{% if (file.thumbnail_url) { %}
			   <a href="{%=file.url%}" title="{%=file.name%}" rel="gallery" download="{%=file.name%}"><img src="{%=file.thumbnail_url%}"></a>
			   {% } %}</td>
      <td class="name">
      <a href="{%=file.url%}" title="{%=file.name%}" rel="{%=file.thumbnail_url&&'gallery'%}" download="{%=file.name%}">{%=file.name%}</a>
      </td>
      <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>
      <td colspan="2"></td>
      {% } %}
     <td class="delete">
     <button class="btn btn-danger" data-type="{%=file.delete_type%}" data-url="{%=file.delete_url%}">
     <i class="icon-trash icon-white"></i>
     <span>{%=locale.fileupload.destroy%}</span>
     </button>
     
     <input type="checkbox" name="delete" value="1">
     </td>
     </tr>
     {% } %}
</script>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
    <!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
    <script src="/js/FILE_UPLOADER/uploader-js/vendor/jquery.ui.widget.js"></script>
    <!-- The Templates plugin is included to render the upload/download listings -->
    <script src="http://blueimp.github.com/JavaScript-Templates/tmpl.min.js"></script>
    <!-- The Load Image plugin is included for the preview images and image resizing functionality -->
    <script src="http://blueimp.github.com/JavaScript-Load-Image/load-image.min.js"></script>
    <!-- The Canvas to Blob plugin is included for image resizing functionality -->
    <script src="http://blueimp.github.com/JavaScript-Canvas-to-Blob/canvas-to-blob.min.js"></script>
    <!-- Bootstrap JS and Bootstrap Image Gallery are not required, but included for the demo -->
    <script src="http://blueimp.github.com/cdn/js/bootstrap.min.js"></script>
    <script src="http://blueimp.github.com/Bootstrap-Image-Gallery/js/bootstrap-image-gallery.min.js"></script>
    <!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
    <script src="/js/FILE_UPLOADER/uploader-js/jquery.iframe-transport.js"></script>
    <!-- The basic File Upload plugin -->
    <script src="/js/FILE_UPLOADER/uploader-js/jquery.fileupload.js"></script>
    <!-- The File Upload file processing plugin -->
    <script src="/js/FILE_UPLOADER/uploader-js/jquery.fileupload-fp.js"></script>
    <!-- The File Upload user interface plugin -->
    <script src="/js/FILE_UPLOADER/uploader-js/jquery.fileupload-ui.js"></script>
    <!-- The localization script -->
    <script src="/js/FILE_UPLOADER/uploader-js/locale.js"></script>
    <!-- The main application script -->
    <script src="/js/FILE_UPLOADER/uploader-js/main.js"></script>
    <!-- The XDomainRequest Transport is included for cross-domain file deletion for IE8+ -->
    <!--[if gte IE 8]><script src="/js/cors/jquery.xdr-transport.js"></script><![endif]-->



</body>
    </html>
