<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
    <link rel="shortcut icon" href="<?php echo base_url('images/favicon.png');?>" />
    <script type="text/javascript" src="/js/jquery-1.7.1.min.js"></script>
    <script language="JavaScript">
    function frame_resize(id)
{
    var newheight;
    var newwidth;

    if(document.getElementById)
    {
	newheight=document.getElementById(id).contentWindow.document.body.scrollHeight;
	newheight += 20;
	newwidth=document.getElementById(id).contentWindow.document.body.scrollWidth;
	newwidth += 30;
    }

    document.getElementById(id).height= (newheight) + "px";
    document.getElementById(id).width= (newwidth) + "px";
}
function div_resize(id)
{
    var newwidth;
    if(document.getElementbyId)
    {
	newwidth=document.getElementById("control_frame").contentWindow.document.body.scrollWidth;
    }
    document.getElementById(id).width=(newwidth) + "px";
}
function before_save()
{
  document.getElementById("error_content").innerHTMl = "Save in progress...";
  save_proposal();
}
function save_proposal()
{
  //  document.getElementById("error_content").innerHTML = "Save in Progress...";
  var iframe_CKEDITOR = document.getElementById("control_frame").contentWindow.CKEDITOR;
    for(var i in iframe_CKEDITOR.instances)
    {
	iframe_CKEDITOR.instances[i].destroy();
	$('#control_frame').contents().find('textarea').replaceWith(function(){return $(this).val();});
    }
    var frame11 = document.getElementById("control_frame").contentWindow.document;
    var params = "obj="+encodeURIComponent(JSON.stringify($('#control_frame').contents().find('html').get(0).outerHTML))+"";
    //console.log(params);
    xmlhttp1 = new XMLHttpRequest();
    xmlhttp1.open("POST", "<?php echo base_url('proposal_builder/save_proposal_html/'.$prop_id);?>", false);
    xmlhttp1.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    //xmlhttp1.setRequestHeader("Content-length", params.length);
    //xmlhttp1.setRequestHeader("Connection", "close");
    xmlhttp1.send(params);
    if(xmlhttp1.responseText.length < 300)
    {
	document.getElementById("error_content").innerHTML = xmlhttp1.responseText;
	document.getElementById("pdf_button").disabled = false;
    }
    else
    {
      document.getElementById("error_content").innerHTML = xmlhttp1.responseText;
      //document.getElementById("error_content").innerHTML = 'Error Saving File';
      //console.log(xmlhttp1.responseText);
      }
}
function close_editors()
{
    var iframe_CKEDITOR = document.getElementById("control_frame").contentWindow.CKEDITOR;

    for(var i in iframe_CKEDITOR.instances)
    {
	iframe_CKEDITOR.instances[i].destroy();
	$('#control_frame').contents().find('textarea').replaceWith(function(){return $(this).val();});
    }
}
function create_pdf()
{
    xmlhttp = new XMLHttpRequest()
    xmlhttp.open("POST", "<?php echo base_url('proposal_builder/create_pdf/'.$prop_id.'/yes'); ?>", false);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.send();
    document.getElementById("error_content").innerHTML = xmlhttp.responseText;
}
function templatize(val)
{
    if(val == "none")
    {
	document.getElementById('control_frame').src = "<?php echo base_url('proposal_builder/load_proposal_display/'.$prop_id);?>";
    }
    else
    {
	document.getElementById('control_frame').src = "<?php echo base_url('proposal_builder/load_proposal_display/'.$prop_id);?>"+"?template="+val+"";
    }
}

    </script>
  </head>
  <body style="background:#ccc;">
    <div id="toolbar" style="background:#fafafa;box-shadow: 0 5px 10px rgba(0,0,0,0.3);height:35px;text-align:center;position:fixed;width: 100%;top: 0px;left: 0px;padding: 20px;"onLoad="div_resize('toolbar');">
      <button id="save_button" style="height:35px;" type="button" onclick="before_save();">Save Proposal Content</button>
      <button id="pdf_button" style="height:35px;position:relative;margin-left:30px;" type="button" onclick="create_pdf();" disabled="disabled">Create PDF</button>
      <button id="close_button" style="height:35px; position: relative; margin-left:30px;" onclick="close_editors();" type="button">Close Editor</button>
      <div id="error_content" style="display:inline-block;margin-left:30px;"></div>
  <span id="html_select" style="position:relative;margin-left:30px;" >Use Existing Template:&nbsp;<select onchange="templatize(this.value);" style="height:35px;width:120px;">
  <option value="none">None</option>
  <?php 
  foreach($prop_list as $v)
    {
      if($v != '.' AND $v != '..')
	{
	  echo '<option value="'.$v.'">'.$v.'</option>';
	}
    }
?>
  </select></span>
    </div>
    <iframe id="control_frame" src="<?php echo base_url('proposal_builder/load_proposal_display/'.$prop_id); ?>" scrolling="no" seamless="seamless" frameborder=0 onload="frame_resize('control_frame');" style="min-height:1000px;width:1240px;margin:50px auto 50px auto;display:block;"></iframe>

  </body>
</html>
