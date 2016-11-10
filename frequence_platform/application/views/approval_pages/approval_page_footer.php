<script>
	$(function(){
		var adset_version_id = "<?php echo (isset($version_id) ? $version_id : ""); ?>";
		
		$("input[name='show_for_io']").change(function(){			
			var is_checked = $(this).is(":checked");
			var show_for_io = (is_checked ? 1 : 0);

			if (typeof adset_version_id !== 'undefined' && adset_version_id !== '')
			{
				$.ajax({
					async:false,
					type: "GET",
					url: "/creative_uploader/save_show_for_io/",
					data:{'show_for_io':show_for_io, 'adset_version_id':adset_version_id}
				});
			}
		});
		
		function set_show_for_io_value(version_id)
		{
			if (typeof version_id === 'undefined' || version_id === null || version_id === '')
			{
				return;
			}

			$.ajax({
				async:false,
				type: "GET",
				url: "/creative_uploader/get_show_for_io_value/",
				dataType: "html",
				data: {'adset_version_id': version_id},
				success: function(data){
					if(data) // will be empty if user is not logged in
					{
						var ret_obj = $.parseJSON(data);
						var user_name = ret_obj.username;
						var updated_date = ret_obj.updated_date;
						if(ret_obj.data == 1)
						{
							$("input[name='show_for_io']").prop('checked',true);
						}
						else
						{
							$("input[name='show_for_io']").prop('checked',false);
						}
						$("#show_for_io_updated_user_name_date").html("("+ret_obj.updated_date+" by "+user_name+")");
					}
				}
			});
		}
		
		set_show_for_io_value(adset_version_id);
	});
</script>
