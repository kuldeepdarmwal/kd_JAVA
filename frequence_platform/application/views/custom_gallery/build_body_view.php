
<div class="curate_body container-fluid">
	<div class="row-fluid ">
		<div class="span6">
			<ul class="nav nav-pills">
			<li class="dropdown">
				<a class="dropdown-toggle" data-toggle="dropdown" href="#">
					<i class="icon-folder-open"></i> Open<b class="caret"></b>
				</a>
				<ul class="dropdown-menu">
					<li><a id="open_new_menu_option"><i class="icon-certificate"></i> New</a></li>
					<li id="open_existing_li"><a id="open_existing_menu_option"><i class="icon-edit"></i> Existing</a></li>
				</ul>
			</li>
			<li id="save_pill_li" class="disabled"><a  id = "save_as_gallery_button" href="#" data-toggle="modal"><i class="icon-save"></i> Save</a></li>
			</ul> 
		</div>
		
	</div>

	<div class="row-fluid" style="min-height:78px">
		<div class="span4">
			<h3 id="">
				<span class="" id="gallery_title">New Gallery</span> <i id="refresh_icon" class="icon-refresh icon-spin" style="visibility:hidden"></i> 
				<span id="delete_button_span" style="visibility:hidden">
					<small ><a href="#delete_gallery_modal" data-toggle="modal"><i class="icon-trash"></i></a> </small>
				</span>
			</h3>
		</div>

		<div id="share_alert_box" class="span7 alert alert-info" style="visibility:hidden">
			<div class="row-fluid">
				<div class="span12">
					<h5 >
						<span class="muted">
							<i class="icon-group"></i> share link: </span>
							<span id="share_link_span">
							</span>
						</span>
						
					</h5>
					<span class=" muted">
							<h5><i class="icon-envelope"></i> email me when link is viewed  <input id="is_tracked" type="checkbox" value=""></h5>
						</span>
				</div>
				
			</div>
		</div>
		
	</div>

	<div class="row-fluid" style="padding-bottom:500px">
		<div class="span12">
			<input class="span11" id="adset_select" type="hidden" >
		</div>
	</div>
</div>



<div id="save_as_modal" class="modal hide fade"  role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="myModalLabel"><i class="icon-save"></i> Save gallery:</h3>
	</div>
	<div class="modal-body">
		<?php if($can_user_see_editor_dropdown){ ?>
		<div style="">
			<div class="row-fluid">
				<div class="span2">Assign to:</div>
				<div class="span10">
					<?php } ?>
					<div id="the_gallery_owner_container">
					<input id="the_gallery_owner" class= "span12" type="hidden">
					</div>
					<?php if($can_user_see_editor_dropdown){ ?>
				</div>
			</div>
			<div class="row-fluid">
				<hr>
			</div>
		</div>
		<?php } ?>
		<div class="row-fluid">
			<ul class="nav nav-pills ">
				<li id="save_as_new_modal_pill" class="active">
					<a id="save_as_new_modal_anchor" href="#save_as_new_pane" data-toggle="tab">as new</a>
				</li>
				<li id="save_as_existing_modal_pill" >
					<a id="save_as_existing_modal_anchor" href="#save_as_existing_pane" data-toggle="tab">as existing</a>
				</li>
			</ul>
			<div class="tab-content">
				<div class="tab-pane active" id="save_as_new_pane">
					<div class="well" style="height:75px"><input id="new_gallery_name" class= "span12" type="text" placeholder="name gallery here"></div>
					<div class="pull-right">
						<button class="btn btn-danger" data-dismiss="modal" aria-hidden="true"><i class="icon-remove-circle"></i> Cancel</button>
						<button id="save_as_new_modal_go" class="btn btn-primary"><i class="icon-save"></i> Save</button>
					</div>
				</div>
				<div class= "tab-pane" id="save_as_existing_pane">
					<div class="well " style="height:75px"><input id="the_selected_save_as_gallery" class= "span12" type="hidden"></div>
					<div class="pull-right">
						<button class="btn btn-danger" data-dismiss="modal" aria-hidden="true"><i class="icon-remove-circle"></i> Cancel</button>
						<button id="save_as_existing_modal_go"class="btn btn-primary"><i class="icon-save"></i> Save</button>
					</div>
				</div>
			</div>
		</div>	
	</div>
</div>

<div id="open_gallery_modal" class="modal hide fade"  role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="myModalLabel"><i class="icon-folder-open"></i> Open gallery:</h3>
	</div>
	<div class="modal-body">

				<div class="well " style="height:75px"><input id="open_gallery_select" class= "span5" type="hidden"></div>
				<div class="pull-right">
					<button class="btn btn-danger" data-dismiss="modal" aria-hidden="true"><i class="icon-remove-circle"></i> Cancel</button>
					<button id="open_gallery_modal_go"class="btn btn-primary"><i class="icon-folder-open"></i> Open</button>
				</div>


	</div>
</div>


<div id="delete_gallery_modal" class="modal hide fade"  role="dialog"  aria-hidden="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3><i class="icon-trash"></i> Delete gallery</h3>
	</div>
	<div class="modal-body">
		<div><h4>Are you sure?</h4>
			When you delete a gallery, all previously shared links will not work and you won't be able to see the gallery anymore.
		</div>
				



	</div>
	<div class="modal-footer">
					<div class="pull-right">
					<button class="btn btn-danger" data-dismiss="modal" aria-hidden="true"><i class="icon-remove-circle"></i> Cancel</button>
					<button id="delete_gallery" class="btn btn-primary"><i class="icon-trash"></i> I'm sure, delete gallery</button>
				</div>
			</div>
</div>


