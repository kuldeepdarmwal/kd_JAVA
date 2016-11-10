 <div class="container">
        <h2>Ad Publisher <small> Drag and Drop - then to DFA</small></h2>
          <form id="fileupload" action="/publisher/multi_upload/" method="POST" enctype="multipart/form-data" class="form-horizontal">
            
            <div class="span12">
                <h4>Creative Settings</h4>
                
                    <div class="control-group">
                        <label class="control-label" for="the_chosen_vl_ad_type">VL Adset Type</label>
                        <div class="controls">
                            <select id="the_chosen_vl_ad_type" onChange="">
                                <option value="vl_hd_w_fullscreen_image">HD (w/ fullscreen image)</option>
                                <option value="vl_sd_wo_fullscreen_image">Standard Def (w/o fullscreen image)</option>
                                <option value="vl_hd_wo_fullscreen_image">HD (w/o fullscreen image)</option>
                                <option value="vl_sd_w_fullscreen_image">Standard Def (w/ fullscreen image)</option>
                                <option value="vl_hd_gpa_w_fullscreen_image">HD GRANDPA (w/ fullscreen image)</option>
                                <option value="vl_hd_gpa_wo_fullscreen_image">HD GRANDPA (w/o fullscreen image)</option>
                            </select>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="adv_id">DFA Advertiser</label>
                        <div class="controls" id="">
                            <div class="form-inline">
                                <select id="advertiser_dropdown" onChange="advertiser_select_script();">
                                    <option value="nothing">--</option>
                                </select>
                                <span>
                                    <span id="new_advertiser_input"> </span> <span id="load_advertiser_status"> </span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="adv_id">DFA Campaign</label>
                        <div class="controls" id="">
                            <div class="form-inline">
                                <select id="campaign_dropdown" onChange="campaign_select_script();">
                                    <option value="nothing">--</option>
                                </select>
                                <span>
                                    <span id="new_campaign_input"> </span> <span id="load_campaign_status"> </span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="adv_id">VL Campaign</label>
                        <div class="controls" id="">
                            <div class="form-inline">
                                <select id="vl_campaign_dropdown" onChange="vl_campaign_select_script();" class="span5">
                                    <option value="nothing">--</option>
                                </select>
                                <span>
                                    <span id="new_vl_campaign_input"> </span> <span id="load_vl_campaign_status"> </span>
                                </span>
                            </div>
                        </div>
                    </div>
                



                

                <div class="row-fluid">
                
               <!--  <div id = "output_zone_progress_bar" class="progress progress-success progress-striped active span6 pull-right">
                    <div class="bar" style="width: 100%"></div>
                </div> -->
                
                <!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
               <h4>Stage Creative Assets</h4>
                <div class="row-fluid fileupload-buttonbar well">
                    <div class="row-fluid span12">
                        <div class="span6">
                        <!-- The fileinput-button span is used to style the file input field as button -->
                        <span class="btn btn-inverse fileinput-button">
                            <i class="icon-plus icon-white"></i>
                            <span>Browse</span>
                            <input type="file" name="files[]" multiple>
                        </span>
                        <button type="submit" class="btn btn-primary start">
                            <i class="icon-upload icon-white"></i>
                            <span>Stage</span>
                        </button>
                        <button type="reset" class="btn btn-warning cancel">
                            <i class="icon-ban-circle icon-white"></i>
                            <span>Cancel</span>
                        </button>
                        <button type="button" class="btn btn-danger delete">
                            <i class="icon-trash icon-white"></i>
                            <span></span>
                        </button>
                        <input type="checkbox" class="toggle">
                        <button type="button" class="btn btn-success pull-right span2" onclick="perform_action();">
                            <i class="icon-circle-arrow-right icon-white"></i>
                            <span></span>
                        </button>
                        <div class="fileupload-progress fade">
                            <!-- The global progress bar -->
                            <div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                                <div class="bar" style="width:0%;"></div>
                            </div>
                            <!-- The extended global progress information -->
                            <div class="progress-extended">&nbsp;</div>
                        </div>
                        <!-- The loading indicator is shown during file processing -->
                        <div class="fileupload-loading"></div>
                        <div >

                        <!-- The table listing the files available for upload/download -->
                            <table role="presentation" class="table table-striped"><tbody class="files" data-toggle="modal-gallery" data-target="#modal-gallery"></tbody></table>
                        </div>
                    </div>
                    <div id="output_panel" class="span6 alert alert-info" style="height:inherit;">
                        
                        <div id="output_zone">
                            <h1>Instructions</h1>
                            <ol>
                                <li>Drag assets onto the main screen</li>
                                <li>Click the blue "Stage<i class="icon-upload"></i>" button</li>
                                <li>Click the green <i class="icon-circle-arrow-right"></i> button</li>
                                <li>Copy your tags</li> 

                            </ol>
                            <div id="success_message"></div>
                            <div id="error_message"></div>
                        </div>
                    </div>
                    </div>
                   

                    
                </div>
            </div>
        </div>
        </form>
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
   
<!-- Le javascript



    <!-- Placed at the end of the document so the pages load faster -->
    <!--<script src="../bootstrap/assets/js/jquery.js"></script>-->


    <script src="/bootstrap/assets/js/jquery.js"></script>
    <script src="/bootstrap/assets/js/bootstrap-transition.js"></script>
    <script src="/bootstrap/assets/js/bootstrap-alert.js"></script>
    <script src="/bootstrap/assets/js/bootstrap-modal.js"></script>
    <script src="/bootstrap/assets/js/bootstrap-dropdown.js"></script>
    <script src="/bootstrap/assets/js/bootstrap-scrollspy.js"></script>
    <script src="/bootstrap/assets/js/bootstrap-tab.js"></script>
    <script src="/bootstrap/assets/js/bootstrap-tooltip.js"></script>
    <script src="/bootstrap/assets/js/bootstrap-popover.js"></script>
    <script src="/bootstrap/assets/js/bootstrap-button.js"></script>
    <script src="/bootstrap/assets/js/bootstrap-collapse.js"></script>
    <script src="/bootstrap/assets/js/bootstrap-carousel.js"></script>
    <script src="/bootstrap/assets/js/bootstrap-typeahead.js"></script>


    <!-- The template to display files available for upload -->
<script id="template-upload" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-upload fade">
        <td class="preview"><span class="fade"></span></td>
        <td class="name" style="font-size:10px; word-wrap: break-word;"><span>{%=file.name%}</span></td>
        <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>
        {% if (file.error) { %}
            <td class="error" colspan="2"><span class="label label-important">{%=locale.fileupload.error%}</span> {%=locale.fileupload.errors[file.error] || file.error%}</td>
        {% } else if (o.files.valid && !i) { %}

            <td class="start">{% if (!o.options.autoUpload) { %}
                <button class="btn btn-primary">
                    <i class="icon-upload icon-white"></i>
                </button>
            {% } %}</td>
        {% } else { %}
            <td colspan="2"></td>
        {% } %}
        <td class="cancel">{% if (!i) { %}
            <button class="btn btn-warning">
                <i class="icon-ban-circle icon-white"></i>
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
            <td class="preview"><a href="{%=file.url%}" title="{%=file.name%}" rel="gallery" download="{%=file.name%}"><i class="icon-picture"></i></a></td> 
            <td class="name" style="font-size:10px; word-wrap: break-word;">
                <a href="{%=file.url%}" title="{%=file.name%}" rel="{%=file.thumbnail_url&&'gallery'%}" download="{%=file.name%}">{%=file.name%}</a>
            </td>
            <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>
            <td colspan="2"></td>
        {% } %}
        <td class="delete">
            <button class="btn btn-danger" data-type="{%=file.delete_type%}" data-url="{%=file.delete_url%}">
                <i class="icon-trash icon-white"></i>
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
<script src="/blueimp/js/tmpl.min.js"></script>
<!-- The Load Image plugin is included for the preview images and image resizing functionality -->
<script src="/blueimp/js/load-image.min.js"></script>
<!-- The Canvas to Blob plugin is included for image resizing functionality -->
<script src="/blueimp/js/canvas-to-blob.min.js"></script>
<!-- Bootstrap JS and Bootstrap Image Gallery are not required, but included for the demo -->
<script src="/blueimp/js/bootstrap.min.js"></script>
<script src="/blueimp/js/bootstrap-image-gallery.min.js"></script>
<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
<script src="/blueimp/js/uploader-js/jquery.iframe-transport.js"></script>
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
<!--[if gte IE 8]><script src="js/cors/jquery.xdr-transport.js"></script><![endif]-->



<script type="text/javascript">
var G_advertiser_id;
var G_campaign_id;
var G_vl_campaign_id;

function init_function()
{

    build_advertiser_select_dropdown();
    advertiser_select_script();
    build_vl_campaign_select_dropdown(); 

}

function build_advertiser_select_dropdown()
{
       var xmlhttp = new XMLHttpRequest();
       var advertiser_dropdown_url = "/publisher/get_all_advertisers/";
       xmlhttp.open("GET", advertiser_dropdown_url, false);
       xmlhttp.send();
       document.getElementById("advertiser_dropdown").innerHTML=xmlhttp.responseText;
}

function advertiser_select_script()
{
    set_output_zone_class(null); 
    //first handle if it's a new advertiser'
    document.getElementById("campaign_dropdown").innerHTML='';//clears out in case a new advertiser was created after an old one was picked
    document.getElementById("new_campaign_input").innerHTML='';
    document.getElementById("new_advertiser_input").innerHTML='';
    set_advertiser_status(null);
    set_campaign_status(null);
    G_advertiser_id=document.getElementById("advertiser_dropdown").value;
    if (G_advertiser_id == "new"){
       show_new_advertiser_input_box(1); 
    }else if(G_advertiser_id != "nothing"){
       build_campaign_select_dropdown(G_advertiser_id);
    }

}

function campaign_select_script()
{
    set_output_zone_class(null) ;
    document.getElementById("new_campaign_input").innerHTML='';
    set_campaign_status(null);
    G_campaign_id=document.getElementById("campaign_dropdown").value;
    if (G_campaign_id == "new"){
        show_new_campaign_input_box(1);
    }else{

    }
}

function vl_campaign_select_script()
{
    set_output_zone_class(null) ;
    document.getElementById("new_vl_campaign_input").innerHTML='';
    set_campaign_status(null);
    G_vl_campaign_id=document.getElementById("vl_campaign_dropdown").value;
    // if (G_campaign_id == "new"){
    //     show_new_campaign_input_box(1);
    // }else{

    // }
    //alert(G_vl_campaign_id);
}

function show_new_advertiser_input_box(is_new)
{
    if(is_new){
        document.getElementById("new_advertiser_input").innerHTML='<input class="span2"  type="text" placeholder="Name new advertiser" id="new_advertiser_name"> <button type="button"  id="advertiser_load_button" class="btn btn-success " onclick="load_new_advertiser_name_script();" data-loading-text="Loading..."><i class="icon-plus icon-white"></i> <span>Add</span></button>';
        document.getElementById("load_advertiser_status").innerHTML='';
        document.getElementById("load_campaign_status").innerHTML='';
     }else{
        document.getElementById("new_advertiser_input").innerHTML='';
        document.getElementById("load_advertiser_status").innerHTML='';
        document.getElementById("load_campaign_status").innerHTML='';
    }
}

function show_new_campaign_input_box(is_new)
{
    if(is_new){
       document.getElementById("new_campaign_input").innerHTML='<input   type="text" placeholder="Name new campaign" id="new_campaign_name"> <input  id="new_landing_page" type="url" value="http://www." id="new_landing_page"> <button  type="button" id="campaign_load_button" class="btn btn-success" onclick="load_new_campaign_name_script();"" data-loading-text="Loading...> <i class="icon-plus icon-white"></i> <span>Add</span></button> ';
    }else{
        document.getElementById("new_campaign_input").innerHTML='';
    }
}  

function build_campaign_select_dropdown(advertiser_id)
{
       //alert(advertiser_id);
       var xmlhttp = new XMLHttpRequest();
       var campaign_dropdown_url = "/publisher/get_campaigns_from_advertiser/"+advertiser_id;
       xmlhttp.open("GET", campaign_dropdown_url, false);
       xmlhttp.send();
       document.getElementById("campaign_dropdown").innerHTML=xmlhttp.responseText;
}

function build_vl_campaign_select_dropdown()
{
       //alert(advertiser_id);
       var xmlhttp = new XMLHttpRequest();
       var vl_campaign_dropdown_url = "/publisher/get_vl_campaign_dropdown";
       xmlhttp.open("GET", vl_campaign_dropdown_url, false);
       xmlhttp.send();
       document.getElementById("vl_campaign_dropdown").innerHTML=xmlhttp.responseText;
}



function load_new_advertiser_name_script()
{
    set_advertiser_status(null);

     if (document.getElementById("new_advertiser_name").value != "")
     { 
        var load_new_advertiser_url = "/publisher/insert_new_advertiser/"+document.getElementById("new_advertiser_name").value;
        var new_advertiser_load_result = load_new_advertiser_ajax(load_new_advertiser_url);
        if(new_advertiser_load_result.success)
        {
            G_advertiser_id = new_advertiser_load_result.advertiser_id;
            build_advertiser_select_dropdown();
            build_campaign_select_dropdown(G_advertiser_id);
            document.getElementById("advertiser_dropdown").value=G_advertiser_id;
            document.getElementById("new_advertiser_input").innerHTML='';
            set_advertiser_status('success','new advertiser loaded');
            document.getElementById("new_campaign_input").innerHTML='';
        }else
        {
            set_advertiser_status('important','error');
        }
     }else
     {
            alert("please name new advertiser");
     }     
 }
//if label is set to null, then it will clear the div
function set_advertiser_status(label, copy)
{   
    if(label === null)
    {
        document.getElementById("load_advertiser_status").innerHTML ='';
    }else
    {
        document.getElementById("load_advertiser_status").innerHTML = '<span class="label label-'+label+'">'+copy+'</span>';
    } 
}

function set_campaign_status(label, copy)
{   
    if(label === null)
    {
        document.getElementById("load_campaign_status").innerHTML ='';
    }else
    {
        document.getElementById("load_campaign_status").innerHTML = '<span class="label label-'+label+'">'+copy+'</span>';
    } 
}






function load_new_advertiser_ajax(the_url)
{   
    $('#advertiser_load_button').button('loading');
    var return_data;
    $.ajax({
        type: "GET",
        url: the_url,
        async: false,
        data: {  },
        dataType: 'html',
        error: function()
        {
            return 'error';
        },
        success: function(msg)
        {
            return_data = msg;
        }
    });
    $('#advertiser_load_button').button('reset')
    return eval('(' +return_data+')' );
}

function load_new_campaign_ajax(the_url)
{     
    $('#campaign_load_button').button('loading');
    var return_data;
    $.ajax(
    {
        type: "GET",
        url: the_url,
        async: false,
        data: {  },
        dataType: 'html',
        error: function()
        {
            return 'error';
        },
        success: function(msg)
        {
            return_data = msg;
        }
    });
    $('#campaign_load_button').button('reset')
    return eval('(' +return_data+')' );
}
     

function load_new_campaign_name_script()
{
    set_campaign_status(null);
    if (document.getElementById("new_campaign_name").value != "" && document.getElementById("new_landing_page").value != "" && document.getElementById("new_landing_page").value != "http://www.")
    { 
        var load_new_campaign_url = "/publisher/insert_new_campaign/?advertiser_id="+G_advertiser_id+"&campaign_name="+document.getElementById("new_campaign_name").value+"&landing_page="+escape(document.getElementById("new_landing_page").value);
        var new_campaign_load_result = load_new_campaign_ajax(load_new_campaign_url);
        if(new_campaign_load_result.success)
        {
            G_campaign_id = new_campaign_load_result.campaign_id;
            build_campaign_select_dropdown(G_advertiser_id);
            document.getElementById("campaign_dropdown").value=G_campaign_id;
            set_campaign_status('success','new campaign loaded');
            document.getElementById("new_campaign_input").innerHTML='';
        }else
        {
            set_campaign_status('important','error');
        }
    }else
    {
        alert("please make sure campaign name and landing page are correct");
    }     
}



function perform_action(){
    
     var output_string = "";
     var upload_creative_url;
     var alert_string ='<h1>Error</h1>';
     

     //check if advertiser has been picked
     if (G_advertiser_id == undefined || G_advertiser_id == "new" || G_advertiser_id == "nothing"){
         alert_string = alert_string+' missing DFA advertiser,<br>';
     }
     //check if campaign has been picked
     if (G_campaign_id == undefined || G_campaign_id == "new" || G_campaign_id == "nothing"){
         alert_string = alert_string+' missing DFA campaign,<br>';
     }
     //check if vl campaign has been picked
     if (document.getElementById("vl_campaign_dropdown").value == "nothing"){
        alert_string = alert_string+' missing VL campaign,<br>';
     }
     if (alert_string == '<h1>Error</h1>'){
        var xmlhttp_multi = new XMLHttpRequest();
        var multi_upload_url = "/publisher/multi_upload/";
        xmlhttp_multi.open("GET", multi_upload_url, false);
        xmlhttp_multi.send();
        if(xmlhttp_multi.responseText!='[]'){
            var files_array = eval('(' + xmlhttp_multi.responseText + ')');
            var xmlhttp_file_check = new XMLHttpRequest();
            var file_check_url = "/publisher/file_check/?vl_creative_type="+document.getElementById("the_chosen_vl_ad_type").value+'&asset_filepath='+escape(files_array[0].full_filepath)+'&advertiser_id='+G_advertiser_id+"&campaign_id="+G_campaign_id+'&vl_c_id='+G_vl_campaign_id;
            //alert(file_check_url);
            xmlhttp_file_check.open("GET", file_check_url, false);
            xmlhttp_file_check.send();
            //var file_check_result = xmlhttp_file_check.responseText;
            var file_check_result = eval('(' + xmlhttp_file_check.responseText + ')');
            if(file_check_result.success){
                set_output_zone_class('success');
                document.getElementById("output_zone").innerHTML = file_check_result.file_check_message;
            }else{
                set_output_zone_class('error')
                document.getElementById("output_zone").innerHTML = file_check_result.file_check_message;
            }
        }else{
            set_output_zone_class('error');
            document.getElementById("output_zone").innerHTML='<h2>Error</h2>Please stage files before loading<br>';
        }
     }else{
        set_output_zone_class('error');
        document.getElementById("output_zone").innerHTML=alert_string; 
     }
}

function set_output_zone_class(type){
    switch(type)
    {
        case 'error':
          document.getElementById("output_panel").className = "span6 alert alert-error";
          break;
        case 'success':
          document.getElementById("output_panel").className = "span6 alert alert-success";
          break;
        default:
          document.getElementById("output_panel").className = "span6 alert alert-info";
          document.getElementById("output_zone").innerHTML='<h1>Instructions</h1><ol><li>Drag assets onto the main screen</li><li>Click the blue "Stage<i class="icon-upload"></i>" button</li><li>Click the green <i class="icon-circle-arrow-right"></i> button</li><li>Copy your tags</li> </ol>';
     
    }
    
}


</script>

  </body>
  <script>
    window.onload = init_function();
</script>
</html>