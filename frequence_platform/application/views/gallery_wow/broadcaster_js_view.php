 <script src="/libraries/external/select2/select2.js"></script>
<script>
	

	function form_valid(){
		return $("#adset_version_select").select2('val')!='' && $("#adset_friendly_name").val()!='';
	}

	function adset_dropdown_format(adset){
		var published_badge = '';
		if(adset.partners !== null){
			published_badge = '<i class="icon-thumbs-up icon-2x" style="color:green"></i>';
		}
		var markup = '<table><tr><td><img src="'+adset.thumb+'" height="50" width="60"></td><td><h5>'+adset.text+'</h5></td><td> '+published_badge+'</td></tr></table>';
		return markup;
	}

	function adset_selected_format(adset){
		var published_badge = '';
		if(adset.partners !== null){
			published_badge = '<i class="icon-thumbs-up" style="color:green"></i>';
		}
		var markup = '<table><tr><td>'+adset.text+'</td><td> '+published_badge+'</td></tr></table>';
		return markup;
	}


		$(document).ready( function () {

			$("#preview_iframe").attr("src",'http://25.media.tumblr.com/tumblr_mdljorZPaC1rkmcsko1_500.gif');


			$('#adset_version_select').select2({
				placeholder: "Select adset",
				minimumInputLength: 0,
				multiple: false,
				ajax: {
					url: "/gallery/adset_feed/",
					type: 'POST',
					dataType: 'json',
					data: function (term, page) {
						term = (typeof term === "undefined" || term == "") ? "%" : term;
						return {
							q: term,
							page_limit: 20,
							page: page
						};
					},
					results: function (data) {
						return {results: data.result, more: data.more};
					}
				},
				formatResult: adset_dropdown_format, 
				formatSelection: adset_selected_format,  
				dropdownCssClass: "bigdrop", // apply css that makes the dropdown taller
				escapeMarkup: function (m) { return m; },
				allowClear: true
			});



			$("#partners_multiselect").select2({
				placeholder: "Select partners",
				multiple: true,
				data:<?php echo $all_partners_select;?>
			}).on("change",function(e){///need to do some checking if the special case of all has been selected
					if(e.added != undefined){
						if(e.added.id == 0){ //if all was added remove all other partners
							$(this).select2('data',e.added);
						}else{ //if something other than all was added check to see if all is there 
							var splice_index = -1;//initialize the splice index out of range of the array
							var data_obj = $(this).select2('data');
							for(var i = 0; i < data_obj.length; i++)
							{
								if(data_obj[i].id == 0){//if ALL is there splice that element from the data array
									splice_index = i;
								}
							}

							if(splice_index > -1){ //if a valid splice index was set we remove the ALL element from the data obj
								data_obj.splice(splice_index,1);
							}
							$(this).select2('data',data_obj);//then reload the data object
						}
					}
					
				}
			);

			$("#adset_features").select2({
				placeholder: "Select features",
				multiple: true,
				data:<?php echo $all_features_select;?>
			});

			$("#adset_version_select").on('change', function(e) {
				//load iframe
				$("#preview_iframe").attr("src",$(this).select2('data').preview_link);
				//update thumbnail
				$("#thumbnail_img").attr("src",$(this).select2('data').thumb);
				
				//prefill based on whether or not this adset has been saved in the past
				if($(this).select2('data').saved_adset_name===null){//if not saved
					$("#adset_friendly_name").val($(this).select2('data').friendly_name);///suggest a name
					$("#partners_multiselect").select2('data',[{id:0,text:'ALL'}]); //set partners to all
				}else{//otherwise if saved - prefill features and partners
					$("#adset_friendly_name").val($(this).select2('data').saved_adset_name);
					$("#partners_multiselect").select2('data',eval("("+$(this).select2('data').partners+")"));
				}
				$("#adset_features").select2('data',eval("("+$(this).select2('data').features+")"));
				if($(this).select2('data').partners !== null){
					$('#published_label').html('Published');
				}else{
					$('#published_label').html('');
				}

			});


 			$("#save_adset_to_gallery_btn").on('click',function(e){
 				if(form_valid()){
 					$('#published_label').html('');//turn off published label
 					//alert("please insert adset id: "+$(adset_version_select).select2('val')+" adset name: "+$("#adset_friendly_name").val()+" partners: "+$("#partners_multiselect").val()+" features: "+$("#adset_features").val());
 					$.ajax({
							type: "POST",
							url: '/gallery/save_adset_to_gallery/',
							async: true,
							data: { as_v_id: $(adset_version_select).select2('val'), as_name: $("#adset_friendly_name").val(), prtnrs: $("#partners_multiselect").val(), features: $("#adset_features").val()},
							dataType: 'html',
							error: function(){
								alert('error saving adset to gallery');
							},
							success: function(msg){ 
								//alert(msg);
								var result = $.parseJSON(msg);
								if(result.success==true)
								{
									if($("#partners_multiselect").val() !== ''){
										$('#published_label').html('Published');
									}else{
										$('#published_label').html('');
									}
								}
								else
								{
									$('#published_label').html('');
									alert(result.message);
								}								
								
							}
						});
 				}else{
 					alert('you\'re missing something like adset or adset name');//form validation
 				}
 				
 			});

			
 		});
</script>