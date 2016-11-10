<script type="text/javascript" >
	$(function() {	
	// uploadButton  ( Add file )
		$('#uploadButton').hover(function(){
			$('#upload_b').addClass('hover');
		},function(){
			$('#upload_b').removeClass('hover');
		});		
						   
	// mutiupload
	  $('.uploadFilepics').live('click',function(){
		  $('#uploadify').uploadifyUpload(); 	
		   showWarning('Uploading...');
	  })	
	  
	  var scriptData = {'albumid':'<?=$_GET[albumid]?>'};
	  
	  $('#uploadify').uploadify({
	  'uploader'  : 'components/uploadify/uploadify.swf',
	  'script'    : 'uploadpics.php',
	  'cancelImg' : 'components/uploadify/cancel.png',
	  'folder'    : 'uploads',
	  'method'	: 'GET',
	  'scriptData': scriptData,
	  'multi': true, 'auto': false,'fileExt': '*.jpg;*.gif;*.png','fileDesc': 'Image Files (.JPG, .GIF, .PNG)',
	  'queueID'        : 'custom-queue',
	  'wmode'		: 'transparent',
	  'hideButton': true,
  'queueSizeLimit' : 5, 
  'simUploadLimit' : 1,
	  'width': 92,'height': 26,
	  'sizeLimit'	: parseInt($('#maxUploadFileSize').text()),
		  'onClearQueue' : function(event) {
			  $('#upload_c').removeClass('special').addClass('disable');
			  $('#uploadFile').removeClass('uploadFilepics confirm').addClass('disable');	 
			  $('#status-message').html(' ');
			},
		'onSelectOnce'   : function(event,data) {
			  $('#upload_c').removeClass('disable').addClass('special');
			  $('#uploadButtondisable').css({'display':'none'});
			  $('#uploadFile').removeClass('disable').addClass('uploadFilepics confirm');	 
			  $('#status-message').html('Ready');
			},
		  'onAllComplete'  : function(event,data,response) {

		  if(data.errors){
						$('#status-message').html('Complete '+ data.filesUploaded + ' file , <font color=red>and ' + data.errors + ' file donot Complete </font>.');
						showError('uploadComplete'+ data.filesUploaded + 'file , <font color=red>and ' + data.errors + ' file donot Complete </font>.',7000);
						
				  }else{
						  $('#status-message').html('Complete '+ data.filesUploaded + ' file');
											  $('#albumsLoad').fadeOut(500,function(){
															  
															  $('#imageLoad').load('gallery/loadpics.php?albumid=<?=$_GET[albumid]?>',function(){ imgRow(); }).fadeIn();				
																					
													}).load('gallery/loadAlbum.php').fadeIn();			
						  showSuccess('uploadComplete '+ data.filesUploaded  +' File',7000);
						  setTimeout('$.fancybox.close()',500);  // uploadmodal with close  ;
				 }
			}
		});	
		});	
</script>
      
<div class="modal_dialog">
  <div class="header"><span>UPLOAD_DIALOG</span><div class="close_me"><a  id="close_windows"  class="butAcc"  ><img src="images/icon/closeme.png" /> </a></div> </div>
  <div class="clear"></div>
  <div class="content">
    <form  action="">
        <div>
            <div class="demo-box">
            <div style="border:#f4f4f4 20px solid; border-bottom:13px solid #f4f4f4">
              <div id="custom-queue" class="custom-queue dialog"></div>
            </div>
            </div>
                   <div id="uploadButton"><div id="uploadify"></div></div>
                   <div class="upload-group">
                  <a class="uibutton  icon add "  id="upload_b">add file</a>
                  <a href="javascript:$('#uploadify').uploadifyClearQueue();" class="uibutton disable" id="upload_c">clear file</a>     
                  <a class="uibutton   disable" id="uploadFile">Upload</a> or <a id="close_windows"   class="butAcc">cancel</a>
                  <span id="status-message"></span>
                  <span id="maxUploadFileSize" style="display:none"><? $config['max_upload_size']?></span>
        </div>
    </form>
  </div>
</div>