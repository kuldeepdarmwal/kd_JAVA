   //<![CDATA[

/** Here are the global variables **/
var ceditor; //This is for our CKEditor editor
var divcontent=""; //This will save the contents of our div (Not really necessary, just for illustration purposes)
 
//I wanted it to execute after the DOM is ready.
$(document).ready(function(){
    //Handle the doubleclick event for the div
    $(".editable").dblclick(function(){
	
        //Destroy first our editor if it exists
        if(ceditor)
        {
	    for(var i in CKEDITOR.instances)
		CKEDITOR.instances[i].destroy();
	    $('textarea').replaceWith(function(){return $(this).val();});
	}
 
        divcontent = $(this).html(); //Save the content of our div (Stored it in a variable just for clarity)
 
        //Insert the textarea inside the div with the contents of our div as it's value
        $(this).html("<textarea name='txtArea'>"+divcontent+"");
 
        //Time to replace the textarea to a CKEditor editor
        //Notice that it's not using the jQuery adapter's method since it doesn't modify the textarea's value upon submission of the form
        //It's better to use the native CKEditor in this case
        ceditor =  CKEDITOR.replace($(this).children("textarea").get(0));
    });
});

   // Uncomment the following code to test the "Timeout Loading Method".
   // CKEDITOR.loadFullCoreTimeout = 5;
  /*
   window.onload = function()
{
  // Listen to the double click event.
  if ( window.addEventListener )
    document.body.addEventListener( 'dblclick', onDoubleClick, false );
  else if ( window.attachEvent )
    document.body.attachEvent( 'ondblclick', onDoubleClick );

};

function onDoubleClick( ev )
{
  // Get the element which fired the event. This is not necessarily the
  // element to which the event has been attached.
  var element = ev.target || ev.srcElement;

  // Find out the div that holds this element.
  var name;
  do
    {
      element = element.parentNode;
    }
  while ( element && ( name = element.nodeName.toLowerCase() ) && ( name != 'div' || element.className.indexOf( 'editable' ) == -1 ) && name != 'body' )


    if ( name == 'div' && element.className.indexOf( 'editable' ) != -1 )
      replaceDiv( element );
}

var editor;

function replaceDiv( div )
{
  if ( editor )
    editor.destroy();
  editor = CKEDITOR.replace( div );
}
  */
//]]>