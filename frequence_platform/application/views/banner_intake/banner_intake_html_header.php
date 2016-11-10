<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="">
<meta name="author" content="">

<link rel="stylesheet" href="/libraries/external/js/jquery-file-upload-9.5.7/css/jquery.fileupload.css"/>
<link rel="stylesheet" href="/libraries/external/js/jquery-file-upload-9.5.7/css/jquery.fileupload-ui.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.96.1/css/materialize.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<link href="/libraries/external/select2/select2.css" rel="stylesheet"/>
<link href="/assets/css/banner_intake/banner_intake.css" rel="stylesheet"/>

<script>
	/**
	 * Environment such PhantomJS 1.8.* does not provides the bind method on Function prototype.
	 * This code will ensure that acceptance test for banner intake would not break.
	 */
	if (!Function.prototype.bind) {
		Function.prototype.bind = function(scope) {
			var self = this;
			return function() {
				return self.apply(scope, arguments);
			};
		};
	}
</script>

<!--[if IE 9]><link rel="stylesheet" href="/assets/css/mpq/rfp_ie9.css" type="text/css"><![endif]-->
<style type="text/css">
.banner_intake_body {
	padding-left:5%;
	padding-right:5%;
}

.indent-box{
	padding-left:5em;
}

.ticket_icon { 
  cursor:pointer;
}

#ticket_icon:hover {
  color:blue;
}

#asset_upload_well {
	background-color: #F6F6F6;
	border: 1px dashed #666;
	min-height: 100px;
}

input, textarea { color: #000; }
.placeholder,
textarea.placeholder,
input[type="text"].placeholder 
{ color: #aaa; }


#add_scene_button, .remove_scene_button {
	color: #666;
	text-decoration: none;
	cursor: pointer;
	-webkit-transition: all 0.1s ease-in-out;
	position: relative;
}
#add_scene_button:hover {
	color: #777;
	-webkit-transition: all 0.1s ease-in-out;
}
span.center {
	display: block;
	margin: 5px 0 0 0;
	text-align: center;
}

div.file.row {
	margin-left: 0;
}

.fileinput-button {
	margin-bottom: 15px;
}
.bar {
	line-height: 30px;
}
button.delete_file {
	margin-bottom: 15px;
	vertical-align: 0%;
}
#file_upload_error
{
	display:none;
}
.remove_variation_btn
{

}
</style>