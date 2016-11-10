(function($){
  
    $(document).ready(function(){
        // get the form
        var _allowed = ['gif','png','jpg','jpeg'] ;
        var _sttext = '(.gif,.png,.jpg)' ;
        if($('form#campaign-create input:file').length != 0){ 
        
            // substitute form/field for input file
            var _form = '<form id="file_upload_form" method="post"  accept-charset="utf-8" enctype="multipart/form-data" '+
                            'action="'+CI.base_url +'admin/campaign_img_processor'+'" target="upload_target">'+
                                '<input name="file" id="file"  type="file" />'+
                                '<div id="progress" style="width:145px;height:3px;background:grey"/></div>'+
                                '<div id="file-error" style="color:grey;font-size:9px;font-style:italic;text-transform:none"/></div>'+
                                '<input type="submit" name="action" value="Upload" style="display:none;" />'+
                                '<iframe id="upload_target" name="upload_target" src="" style="display:none"></iframe>'+
                        '</form>';
            var _hiddenElm = '<input type="hidden" name="uploaded_file" value="" />' ;
            $('input[name="userfile"]').after(_form).after(_hiddenElm);
            $('input[name="userfile"]').hide();
        }
        
        
        // take iframe's id and input file's names
        
        // find a file field for form that'll trigger upload
       
        // iframe upload command
       $('input#file').change(function(){
         // determine upload file type
         var ext = $(this).val().split('.').pop().toLowerCase();
            if($.inArray(ext, _allowed) == -1) {
                $('#file-error').html('Invalid File Type').css("color","red");
                setTimeout(function(){$('input:file').val('');$('#file-error').html('');},3000);
                
            }else{
			     _sttext = 'Wait while the file is in upload..' ;
                 $('#file-error').html(_sttext).css("color","grey");
				 $('form#campaign-create input:submit').attr('disabled','disabled') ;
                 $(this).parent().submit() ;
            }  
       });
       
       // iframe data extractor
       $("#upload_target").load(function (){
           
            // we can hook for a progress indicator too
            // do something once the iframe is loaded
            var data = $("#upload_target").contents().text() ;
            if(data != null){
                data = JSON.parse(data) ;
				 _sttext = 'File uploaded successfully!' ;
				 $('#file-error').html(_sttext).css("color","green");
                 $('input[name="uploaded_file"]').val(data.file_name) ;
                 $('form#file_upload_form #progress').css("background-color","green");
				  $('form#campaign-create input:submit').removeAttr('disabled') ;
            }
         });
        
    });  
})(jQuery);
