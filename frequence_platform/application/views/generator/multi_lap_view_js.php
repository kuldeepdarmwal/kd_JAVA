<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
<?php echo $maps_js; ?>

<script type="text/javascript" src="/libraries/external/select2/select2.js"></script>

<script type='text/javascript'>

	var loading_bar, loader, ajax_queue, zips_container;
	
	function Geos_Loaded(num_geos){
		this.num_geos_needed = num_geos;
		this.num_geos_loaded = 0;
		this.promises = []
	}

	Geos_Loaded.prototype.create_promises = function()
	{
		this.promises = [];
		for (var i = 0; i < this.num_geos_needed; i++)
		{
			this.promises.push($.Deferred());
		}
		return this.promises;
	}

	Geos_Loaded.prototype.resolve_promises = function()
	{
		if (this.num_geos_loaded == this.num_geos_needed) return false;
		this.promises[this.num_geos_loaded].resolve();
		this.promises[this.num_geos_loaded] = this.promises[this.num_geos_loaded].promise();
		this.num_geos_loaded++;
	}

	function geo_line_ready()
	{
		loader.resolve_promises();
		ajax_queue.dequeue();
		loading_bar.increment();
		if (loading_bar.is_fully_loaded()) loading_bar.timeout_visibility(1000);
	}

	$(document).ready(function(){

		var cumulative_maps;
		var cumulative_uris;
		var cumulative_centers;

		$('#sel_id').select2({
			placeholder: 'Enter info in the textbox above to get options',
			allowClear: false,
			width: 500
		});

		$('#sel_id').on('change', function(e){
			var uri_code = $(this).val();
			var title = $('#' + $(this).attr('id') + " option[value='" + uri_code + "']").html();
			populate_map_and_demos(uri_code, title);
		});

		function populate_map_and_demos(uri_code, title)
		{
			show_map_loading();
			$.ajax({
				url: '/maps/ajax_get_data_for_maps_and_demos_with_unique_id/',
				data: {
					unique_id : uri_code
				},
				dataType: 'json',
				async: true,
				type: 'POST',
				success: function(data) 
				{
					if(vl_is_ajax_call_success(data))
					{
						$('#title_container').empty();
						$('#title_container').append("<p class='lead' id='title_p'>" + title + "</p>");

						zips_container = null;
						var zips = (data.regions.blobs != null) ? JSON.parse(data.regions.blobs).zcta : cumulative_maps;
						zips_container = append_affected_zips_to_dom(zips, $('#title_container'), 0);
						
						calculate_and_post_demographics(data.demos.stats_data);
						load_map_with_data(data.maps)
					}
					else
					{
						vl_show_ajax_response_data_errors(data, 'Something went wrong: ');
						unblock_page();
					}
				},
				error: function(xhr, textStatus, error) 
				{
					vl_show_jquery_ajax_error(xhr, textStatus, error);
				}
			});
		}

		$('#laps').keydown(function(e){
			if (e.keyCode === 13 && e.shiftKey)
			{
				$('#submit_laps').click();
				e.preventDefault();
			}
		})

		$('#submit_laps').click(function(){
			var laps = $('#laps').val();
			var map_div = $('#maps');
			var select = $('#sel_id');

			laps = parse_textarea_for_laps(laps);
			lap_array = remove_empty_lines(laps);
			
			if (laps == '' || laps == false) alert('Please enter an address and either a radius or a min population');
			else 
			{
				$('#submit_laps').attr('disabled', 'disabled');
				prevent_refresh(true);

				select.empty().append('<option></option>')
				select.attr('data-placeholder', 'Loading your requested geos now');
				select.select2({allowClear: false, width: 500});

				cumulative_uris = [];
				cumulative_maps = [];
				cumulative_centers = [];
				
				
				submit_multi_laps(lap_array);
				loader.create_promises()

				$.when.apply(null, loader.promises).done(function() {
					loading_bar.increment('Loading aggregate map');
					if (loading_bar.is_fully_loaded()) loading_bar.timeout_visibility(1000);
					append_cumulative_option_to_select(cumulative_centers, cumulative_uris.join(','), map_div, lap_array.length + 1);
					select.attr('data-placeholder', 'Select an option below to see your data');
					select.select2({allowClear: false, width: 500});
					loading_bar.increment();
					if (loading_bar.is_fully_loaded()) loading_bar.timeout_visibility(1000);
					prevent_refresh(false);
					$('#submit_laps').removeAttr('disabled');
				});
			}
		});

		function round_excessive_decimal_places(decimal, num_places_to_round_to, use_tilda)
		{
			if (('' + decimal).indexOf('.') == -1) return decimal;
			var num_places = ('' + decimal).length - ('' + decimal).indexOf('.') - 1;
			use_tilda = use_tilda || false;
			if (num_places > num_places_to_round_to)
			{
				decimal = Number(decimal).toFixed(num_places_to_round_to);
				decimal = ((use_tilda ? '~' : '') + decimal);
			}
			return decimal;
		}

		function parse_textarea_for_laps(laps)
		{
			lap_array = laps.split('\n');
			return lap_array;
		}

		function submit_multi_laps(laps)
		{
			var length = laps.length, batch_size = 1;
			ajax_queue = $({});
			loader = new Geos_Loaded(length);

			loading_bar = new Loading_Bar('loading_bar', (length + 1) * 2, 1, 'progress_span'); 
			loading_bar.toggle_visibility();

			$.ajax_queue = function(ajax_opts)
			{
				var old_complete = ajax_opts.complete;
				var calls_length = length / batch_size;
				var num_calls = 0;
				ajax_queue.queue(function(next){
					ajax_opts.complete = function()
					{
						if (old_complete) old_complete.apply(this, arguments);
					};
					var line = ajax_opts.data.laps[0];
					var index = (line.indexOf(';') > -1) ? line.indexOf(';') : line.indexOf('\t');
					loading_bar.increment('Loading ' + line.substring(0, index));
					if (loading_bar.is_fully_loaded()) loading_bar.timeout_visibility(1000);
					$.ajax(ajax_opts);
				});
			};

			for (var i = 0; i < length; i += batch_size)
			{
				$.ajax_queue({
					url: '/proposal_builder/ajax_multi_geo_grabber_submit/' + i,
					data: {
						laps : laps.slice(i, i + batch_size),
						id_append : i
					},
					dataType: 'json',
					type: 'POST',
					success: function(data) 
					{
						if(vl_is_ajax_call_success(data)) add_successful_lap_info_to_page(data);
						else
						{
							vl_show_ajax_response_data_errors(data, 'Something went wrong: ');
							unblock_page();
						}
					},
					error: function(xhr, textStatus, error) 
					{
						vl_show_jquery_ajax_error(xhr, textStatus, error);
					}
				});		
			}
		}

		function remove_empty_lines(laps)
		{
			var good_arr = [];
			for (var i = 0; i < laps.length; i++)
			{
				if (laps[i] != '' && laps[i] != null) good_arr.push(laps[i]);
			}
			return (good_arr.length > 0) ? good_arr : false;
		}

		function add_successful_lap_info_to_page(ajax_data)
		{
			var maps = ajax_data['maps'];
			var map_uris = ajax_data['map_uris'];
			var demos = ajax_data.demographics;
			var radii = ajax_data.max_radius;
			var id_append = ajax_data.id_append;
			var map_html;
			var select_dropdown = $('#sel_id');
			var centers = ajax_data['center_coordinates'];

			for (var i = 0; i < maps.length; i++)
			{
				select_dropdown.append($('<option value="' + map_uris[i] + '">' + ajax_data.center_names[i] + ', ' + round_excessive_decimal_places(radii[i], 4, true) + ' mile radius' + '</option>'));
				if (typeof cumulative_maps !== 'undefined') cumulative_maps = cumulative_maps.concat(maps[i]);

				cumulative_centers.push(centers[i]);
				cumulative_uris.push(map_uris[i]);
				geo_line_ready();
				if (select_dropdown.children('option').length == 2)
				{
					var val = $(select_dropdown.children('option')[1]).val();
					select_dropdown.select2('val', val);
					select_dropdown.change();
				}
			}
		}

		function append_cumulative_option_to_select(centers, uris, object_to_be_appended_to, id_append)
		{
			var region_object = new Object();
			region_object.points = new Object();
			region_object.points.lat_long_points = centers;
			region_object.uris = uris;

			get_uri_for_map_of_given_region_types_with_ajax(region_object, object_to_be_appended_to, id_append);
		}

		function Zips_Container(zips, object_to_be_appended_to, id_append)
		{
			this.id_append = id_append;
			var container = $('<div>', {id: 'toggle_container_' + id_append, class: 'toggle_containers container'});
			var row1 = $('<div>', {class: 'outer_toggle row-fluid'});
			var row2 = $('<div>', {class: 'row-fluid'});
			var toggle = $('<div>', {id: 'toggler_container_' + id_append, class: 'togglers span1 offset11'}).html('<i class="icon-plus-sign" title="Click to see affected zip codes" id="toggler_' + id_append + '"></i>');
			var zips_div = $('<div>', {id: 'container_' + id_append, class: 'zips_containers span12'});
			var class_object = this;

			zips_div.html('<small><p id="zips_text">' + zips.join(', ') + '</p></small>');
			zips_div.hide();
			row1.append(toggle);
			row2.append(zips_div);
			container.append(row1);
			container.append(row2);
			object_to_be_appended_to.append(container);

			$('#zips_text').on('click', function() {
				select_text('zips_text');
			});

			$('#toggler_' + id_append).click(function(){
				Zips_Container.prototype.toggle.call(class_object);
			});

		}

		Zips_Container.prototype.toggle = function()
		{
			$('#container_' + this.id_append).slideToggle();
			$('#toggler_' + this.id_append).toggleClass('icon-minus-sign icon-plus-sign');
			$('#container_' + this.id_append).toggleClass('well well-small');
		}

		function append_affected_zips_to_dom(zips, object_to_be_appended_to, id_append)
		{
			return new Zips_Container(zips, object_to_be_appended_to, id_append);
		}

		function Loading_Bar(container_id, num_elements, step, progress_span_id)
		{
			this.is_visible = false;
			this.id = container_id;
			this.progress_id = progress_span_id;
			this.num_elements_total = num_elements;
			this.num_elements_loaded = 0.0;
			this.step = step;

			$('#' + this.id + ' > div').css('width', '0%');
			$('#' + this.progress_span_id).text('Preparing to load regions');
		}

		Loading_Bar.prototype.timeout_visibility = function(timeout)
		{
			window.setTimeout(this.toggle_visibility.bind(this), timeout);
		}

		Loading_Bar.prototype.toggle_visibility = function() 
		{
			$('#' + this.id).toggle();
			$('#' + this.progress_id).toggle();
		}

		Loading_Bar.prototype.increment = function(text_for_progress_span)
		{
			if (typeof text_for_progress_span != undefined)
			{
				$('#' + this.progress_id).text(text_for_progress_span);
			}
			this.num_elements_loaded += this.step;
			$('#' + this.id + ' > div').css('width', 100 * (this.num_elements_loaded / this.num_elements_total) + '%');
		}

		Loading_Bar.prototype.is_fully_loaded = function()
		{
			return (this.num_elements_loaded / this.num_elements_total) == 1;
		}

		function prevent_refresh(do_prevent)
		{
			if (do_prevent)
			{
				$(window).bind('beforeunload', function(){
					return 'Navigating away from the page may cause the page to break. Is this okay?';
				});
			}
			else
			{
				$(window).unbind('beforeunload');
			}
		}

		function select_text(element) {
			var doc = document, text = doc.getElementById(element), range, selection;    
			if (doc.body.createTextRange)
			{
				range = document.body.createTextRange();
				range.moveToElementText(text);
				range.select();
			}
			else if (window.getSelection)
			{
				selection = window.getSelection();
				range = document.createRange();
				range.selectNodeContents(text);
				selection.removeAllRanges();
				selection.addRange(range);
			}
		}

		function unblock_page()
		{
			prevent_refresh(false);
			$('#submit_laps').removeAttr('disabled');
		}

		$('#laps').on('change input paste keyup', function(){
			$(this).val($(this).val().replace(/\t/gi, ';'));
		})

		$("textarea").keydown(function(e) {
			if(e.keyCode === 9)
			{ 
				// get caret position/selection
				var start = this.selectionStart;
				var end = this.selectionEnd;

				var $this = $(this);
				var value = $this.val();

				// set textarea value to: text before caret + tab + text after caret
				$this.val(value.substring(0, start) + "\t" + value.substring(end));

				// put caret at right position again (add one for the tab)
				this.selectionStart = this.selectionEnd = start + 1;

				// prevent the focus lose
				e.preventDefault();
			}
		});
	});
	
</script>