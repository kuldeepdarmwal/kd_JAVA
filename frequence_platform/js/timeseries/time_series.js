var time_series_data_array= new Array();
	function create_initial_time_series()
	{
		var start_date =$("#initial_start_date").val();
		var end_date = $("#initial_end_date").val();
		var initial_series_type = $("#initial_series_type").val();
		var initial_target_impressions = $("#initial_target_impressions").val();
 
		var data_url = "/campaign_setup/get_initial_timeseries_start_date_array/";
		 $.ajax({
					type: "POST",
					url: data_url,
					async: true,
					data: { start_date: start_date, end_date: end_date, initial_series_type: initial_series_type},
					dataType: 'html',
					error: function(){
					},
					success: function(msg){
						var returned_data = jQuery.parseJSON(msg);
						var list_of_start_dates = returned_data.result;		
						time_series_data_array= new Array();

						for (var i=0; i < list_of_start_dates.length-1; i++)
						{
							time_series_data_array[i]=new Array();
							time_series_data_array[i]['start_date']=list_of_start_dates[i];
							time_series_data_array[i]['impressions']=initial_target_impressions;
						}
						
						// after loop, hardcode the last date to 0. this is the array that will be persisted to database
						time_series_data_array[list_of_start_dates.length-1]=new Array();
						time_series_data_array[list_of_start_dates.length-1]['start_date']=list_of_start_dates[list_of_start_dates.length-1];	
						time_series_data_array[list_of_start_dates.length-1]['impressions']=0;	

						refresh_time_series_div();
					}
				});
	}

	function populate_default_timeseries_wizard()
	{
		time_series_data_array= new Array();
		var init_start_d = new Date();
		var init_start_date=init_start_d.date_timeseries_format();
		init_start_d.setDate(init_start_d.getDate()+90);
		var init_end_date=init_start_d.date_timeseries_format();

		var dynamic_time_series_html = '<div style="display:flex; flex-direction:row;">'+
		'<div id="initial_start_date_div"  class="input-append date"><label for="initial_start_date" class="control-label">Start</label><input type="text" data-format="MM/dd/yyyy"  value="'+
		init_start_date+'" class="input-small" id="initial_start_date" style="font-size:12px"/> <span class="add-on"><i data-time-icon="icon-time" data-date-icon="icon-calendar" class="icon-calendar"></i></span> </div>&nbsp;'+
		
		'<div id="initial_end_date_div"  class="input-append date"> <label for="initial_end_date" class="control-label">End</label> <input type="text" value="'+
		init_end_date+'" data-format="MM/dd/yyyy" class="input-small" id="initial_end_date" style="font-size:12px"/> <span class="add-on"><i data-time-icon="icon-time" data-date-icon="icon-calendar" class="icon-calendar"></i></span> </div>&nbsp;'+
		
		'<div><label for="initial_target_impressions" class="control-label">Target Impr</label> <input type="text" class="input-mini" id="initial_target_impressions" placeholder="100,000"  value="100,000" onkeyup="format_impressions_set_data(this)" style="font-size:12px"/> </div>&nbsp;'+
		

		'<div><label for="initial_series_type" class="control-label">Type</label> <select id="initial_series_type" name="initial_series_type" class="input-small" > <option value="MONTH_END">Monthly</option> <option value="BROADCAST_MONTHLY">B\'cast</option> <option value="FIXED">Fixed</option> </select> </div>&nbsp;'+
		
		'<div><label for="name" class="control-label"><br></label>'+
		'<button type="button" title="Initial Flights" id="initial_timeseries_btn" class="btn btn-success" onclick="create_initial_time_series();" ><span>go</span></button>'+
		' </div> </div>'; 

		try
		{
			document.getElementById('timeseries_pro').value="";
			document.getElementById('time_series_div').innerHTML=dynamic_time_series_html;
		}
		catch(err) 
		{
		}
		$('#initial_start_date_div').datetimepicker({pickTime: false, maskInput: false});
    	$('#initial_end_date_div').datetimepicker({pickTime: false, maskInput: false});
	}

	Date.prototype.date_timeseries_format = function() 
	{
		   var yyyy = this.getFullYear().toString();
		   var mm = (this.getMonth()+1).toString(); // getMonth() is zero-based
		   var dd  = this.getDate().toString();
		   return (mm[1]?mm:"0"+mm[0]) +"/" +(dd[1]?dd:"0"+dd[0]) + "/" + yyyy ; // padding
	};

	Date.prototype.date_timeseries_format_yyyymmdd = function() 
	{
		   var yyyy = this.getFullYear().toString();
		   var mm = (this.getMonth()+1).toString(); // getMonth() is zero-based
		   var dd  = this.getDate().toString();
		   return yyyy + "-" + (mm[1]?mm:"0"+mm[0]) +"-" +(dd[1]?dd:"0"+dd[0]); // padding
	};

	Date.prototype.date_timeseries_format_escape = function() 
	{
		   var yyyy = this.getFullYear().toString();
		   var mm = (this.getMonth()+1).toString(); // getMonth() is zero-based
		   var dd  = this.getDate().toString();
		   return (mm[1]?mm:"0"+mm[0]) +"\/" +(dd[1]?dd:"0"+dd[0]) + "\/" + yyyy ; // padding
	};

	function populate_time_series_collection(time_series_data)
	{
		for (var i=0; i < time_series_data.length; i++)
		{
			if (parseInt(time_series_data[i].impressions) === 0)
			{
				var display_class = 'hidden';
				end_date_string = '';
			}
			else
			{
				var end_date = new Date(time_series_data[i+1].start_date);
				end_date.setDate(end_date.getDate()-1);
				end_date_string=end_date.date_timeseries_format();
				var display_class = '';
			}

			var li = $('<li class="collection-item row '+display_class+'"></li>');
			$(li).append('<span class="io-single-flight-dates col s4"><span class="start_date">'+time_series_data[i].start_date+'</span> &nbsp;&mdash;&nbsp; <span class="end_date">'+end_date_string+'</span></span>');
			$(li).append('<input type="text" class="input-small io-single-flight-impressions col s2 offset-s1" value="'+number_with_commas(time_series_data[i].impressions)+'"/>');
			$(li).append('<div class="col s2 offset-s2"><button class="btn io-remove-flight-button"><i data-time-icon="icon-remove" class="icon-remove icon-white"></i></button></div>');
			if (time_series_data[i].new)
			{
				$(li).append('<span class="new badge">&#8203;</span>');
			}
			$('#io_flights_modal #flights-collection').append(li);
		}

		update_total_impressions();

		$('#io_flights_modal #flights-collection').show(0);
	}

	$('#io_flights_modal #flights-collection').delegate('button.io-remove-flight-button', 'click', function(e){
		e.preventDefault();
		$(this).parent().parent().addClass('hidden');
		$(this).parent().siblings('.io-single-flight-impressions').val(0);
		update_total_impressions()
	});

	function update_total_impressions()
	{
		var total_impressions = 0;
		var region_id = $('#io_flights_region_id').val() !== "";

		$('#io_flights_modal ul#flights-collection li.collection-item:not(.hidden)').each(function()
		{
			total_impressions += parseInt(clean_format_impressions($(this).find('input.io-single-flight-impressions').val()), 10);
		});

		if (!region_id)
		{
			total_impressions *= location_collection.locations.length;
		}

		var summary_string = number_with_commas(total_impressions) + ' Total Impressions';

		if (location_collection.locations.length > 1 && !region_id)
		{
			summary_string += ' ('+ location_collection.locations.length.toString() +' Regions)';
		}
		
		$('#io_flights_modal .total-impressions').text(summary_string);
	}

	// this redraws time series div from the array
	function refresh_time_series_div()
	{
		var dynamic_time_series_html_header='<span id="total_impr_div"></span><span><button onclick="refresh_timeseries_data(1, null, null, null, null, this)" title="Add flight" class="btn  btn-link intro-chardin" type="button">'+
 		' <span class="label label-success">'+
 		'<i class="icon-plus icon-white"></i> Add flight</span></button></span>';

 		var dynamic_time_series_html='<div class="form-hover">';

		var total_impressions=0;
		
		for (var i=0; i < time_series_data_array.length-1; i++)
		{
			if (time_series_data_array[i+1] != undefined)
			{
				if (time_series_data_array[i]['impressions'] == '0' && time_series_data_array[i+1]['impressions'] == '0')
				{
					time_series_data_array.splice(i+1, 1);
				}
			}
			if (time_series_data_array[i+1] != undefined)
			{
				if (time_series_data_array[i]['impressions'] == '0' && time_series_data_array[i+1]['impressions'] == '0')
				{
					time_series_data_array.splice(i+1, 1);
				}
			}

			if (time_series_data_array[i]['impressions'] == '0')
			{
				for (var j=0; j < time_series_data_array.length-1; j++)
				{
					if (time_series_data_array[i]['start_date'] == time_series_data_array[j]['start_date'] && i != j && time_series_data_array[j]['impressions'] != '0')
					{
						time_series_data_array.splice(i, 1);
						break;
					}
				
				}

				for (var j=0; j < time_series_data_array.length-1; j++)
				{
					if (new Date(time_series_data_array[i]['start_date']) >= new Date(time_series_data_array[j]['start_date']) && i < j && time_series_data_array[j]['impressions'] != '0')
					{
						time_series_data_array.splice(i, 1);
					}
				
				}
			}
		}
		for (var i=0; i < time_series_data_array.length-1; i++)
		{
			var end_date_derived = new Date(time_series_data_array[i+1]['start_date']);
			initial_target_impressions=time_series_data_array[i]['impressions'];
			end_date_derived.setDate(end_date_derived.getDate()-1);
			end_date_string=end_date_derived.date_timeseries_format();
			dynamic_time_series_html+=create_timeseries_row(time_series_data_array[i]['start_date'], end_date_string, initial_target_impressions, i);
			total_impressions+=parseInt(clean_format_impressions(initial_target_impressions));

		}
		dynamic_time_series_html+='</div>';
		try 
		{
			document.getElementById('total_impr_div_header').innerHTML=dynamic_time_series_html_header;
			document.getElementById('time_series_div').innerHTML=dynamic_time_series_html;
			document.getElementById('total_impr_div').innerHTML="("+format_impressions(total_impressions)+" impressions)";

		}
		catch(err) 
		{
		}

		$(".datepicker_recurring_start" ).datetimepicker({pickTime: false, maskInput: false}).on('changeDate', function (ev) {
			$(this).datetimepicker('hide') ;
			$(this).find('input[type=text]').focus();
		});


		for (var i=0; i < time_series_data_array.length-1; i++)
		{
			var end_date_div = '#initial_end_date'+i+'_div';
			var start_date_input = 'initial_start_date'+i;
			if (document.getElementById(start_date_input) != undefined)
			{
				var start_date = document.getElementById(start_date_input).value;
				var start_date_new = new Date(start_date);
				start_date_new.setDate(start_date_new.getDate()+1);
				$(end_date_div).datetimepicker('setStartDate', start_date_new);
			}
		}

		//refresh text area
		var final_str="";
		for (var i=0; i < time_series_data_array.length-1; i++)
		{
			var end_date = new Date(time_series_data_array[i+1]['start_date']);
			end_date.setDate(end_date.getDate()-1);
			end_date=end_date.date_timeseries_format();
			if (time_series_data_array[i]['impressions'] != "0") {
				var impr=""+time_series_data_array[i]['impressions'];
				impr=clean_format_impressions(impr);
				impr=""+(parseInt(impr));
				final_str +=time_series_data_array[i]['start_date']+"\t"+end_date+"\t"+format_impressions(impr);
				if (i < time_series_data_array.length-1)
					final_str +="\n";
			}
		}

		document.getElementById('timeseries_pro').value=final_str;
		$('[data-toggle="tooltip"]').tooltip();
	}

	function refresh_time_series_div_from_timeseries_pro()
	{
		var timeseries_pro_data_array=document.getElementById('timeseries_pro').value.trim().split("\n");
		time_series_data_array=new Array();
		var previous_end_date="-1";
		for (var i=0; i < timeseries_pro_data_array.length; i++)
		{
			var sub_row=timeseries_pro_data_array[i];
			var sub_row_array="";
			
			if (sub_row.indexOf("\t") != -1)
			 	sub_row_array=sub_row.split("\t");
			else if (sub_row.indexOf("-") != -1)
			 	sub_row_array=sub_row.split("-");
			
			var total_size=time_series_data_array.length;
	 		var end_date = new Date(sub_row_array[1]);
			end_date.setDate(end_date.getDate()+1);
			end_date=end_date.date_timeseries_format();
			
			if (sub_row_array[0] == "" || sub_row_array[0] == undefined) 
			{
				show_ts_error("Please remove the last blank line and try again");
				return;
			}
			var start_date = new Date(sub_row_array[0]).date_timeseries_format();
			if (i==0) 
			{
				time_series_data_array[0]=new Array();
				time_series_data_array[0]['start_date']=start_date;
				time_series_data_array[0]['impressions']=clean_format_impressions(sub_row_array[2]);
				total_size++;
			}
			if (i > 0 && previous_end_date != "-1" && previous_end_date != start_date)
			{
				time_series_data_array[total_size]=new Array();
				time_series_data_array[total_size]['start_date']=previous_end_date;
				time_series_data_array[total_size]['impressions']=0;
				total_size++;
			}  
			if (i > 0)
			{
				time_series_data_array[total_size]=new Array();
				time_series_data_array[total_size]['start_date']=start_date;
				time_series_data_array[total_size]['impressions']=clean_format_impressions(sub_row_array[2]);
				total_size++;
			}
			if (i == timeseries_pro_data_array.length-1) 
			{
				time_series_data_array[total_size]=new Array();
				time_series_data_array[total_size]['start_date']=end_date;
				time_series_data_array[total_size]['impressions']=0;
				break;
			}
			previous_end_date=end_date;
		}
		refresh_time_series_div();
	}


	//operation_flag=1 is add, 2=delete, 3 mouse out on update
	function refresh_timeseries_data(operation_flag, row_counter, impressions, start_date, end_date, me)
	{
		show_ts_error("");
		me.style.backgroundColor = "";
		var error_flag="";

		if (operation_flag==1)//add
		{
			var count = time_series_data_array.length;
			var end_date_string="";
			var temp_impressions="10,000";
			if (count > 0 )
			{
				var temp_start_date=time_series_data_array[time_series_data_array.length-1]['start_date'];
				var end_date_derived = new Date(temp_start_date);
				end_date_derived.setDate(end_date_derived.getDate()+30);
				end_date_string=end_date_derived.date_timeseries_format();
				time_series_data_array[count]=new Array();
				time_series_data_array[count]['start_date']=end_date_string;	
				time_series_data_array[count-1]['impressions']="10,000";	
				time_series_data_array[count]['impressions']=0;	
			} 
		} 
		else if (operation_flag==2)//delete
		{
			time_series_data_array[row_counter]['impressions']=0;
		} 
		else if (operation_flag==3)//update
		{
			if (start_date == "")
			{
				error_flag = "Please enter a valid date in mm/dd/yyyy format";
				show_ts_error(error_flag, me);
			}
			if (error_flag == "" && end_date == "")
			{
				error_flag = "Please enter a valid date in mm/dd/yyyy format";
				show_ts_error(error_flag, me);
			}
			if (error_flag == "" && impressions == "")
			{
				error_flag = "Impressions should be a number";
				show_ts_error(error_flag, me);
			}
			if (impressions != undefined && error_flag == "") {
				time_series_data_array[row_counter]['impressions']=clean_format_impressions(impressions);
			}
			else if (start_date !=undefined)
			{	
				//validation
				if (new Date(start_date) >= new Date(time_series_data_array[row_counter+1]['start_date']))
				{
					error_flag = "Start Date should be before End Date";
					show_ts_error(error_flag, me);
				}
				for (var f=0; f <= row_counter; f++)
				{
					if (f < row_counter && (time_series_data_array[f]['impressions'] != '0') && (new Date(start_date) < new Date(time_series_data_array[f]['start_date'])))
					{
						error_flag = "Please change dates so this start date is after previous flights";
						show_ts_error(error_flag, me);
					}
					if (row_counter > 0 && f == row_counter && (time_series_data_array[f-1]['impressions'] != '0') && (new Date(start_date) < new Date(time_series_data_array[f]['start_date'])))
					{
						error_flag = "Please change dates so this start date is after previous flights";
						show_ts_error(error_flag, me);
					}
					else if (f < row_counter && (time_series_data_array[f]['impressions'] == '0') && (new Date(start_date) < new Date(time_series_data_array[f]['start_date'])))
					{
						error_flag = "Please change dates so this start date is after previous flights";
						show_ts_error(error_flag, me);
					}
				}				
				
				if (error_flag == "")
				{
					var original_start_date = time_series_data_array[row_counter]['start_date'];
					if (row_counter > 0 && new Date(original_start_date) != new Date(start_date))
					{
						var previous_impressions = time_series_data_array[row_counter-1]['impressions'];
						var previous_date = time_series_data_array[row_counter-1]['start_date'];
						if (previous_impressions == '0' && new Date(previous_date) == new Date(start_date))
						{
							time_series_data_array[row_counter]['start_date']=start_date;
						} 
						else
						{
							var original_impressions = time_series_data_array[row_counter]['impressions'];
							time_series_data_array[row_counter]['impressions']='0';
							
							var start_date_new = new Date(start_date);
							start_date_new_string=start_date_new.date_timeseries_format();
							
							var new_element=new Array();
							new_element['start_date']=start_date_new_string;
							new_element['impressions']=original_impressions;
							time_series_data_array.splice(row_counter+1, 0, new_element); 
						}
					}
					else
					{
						time_series_data_array[row_counter]['start_date']=start_date;
					}
					
				}
			}
				
			else if (end_date !=undefined)
			{
				var next_non_zero_start_date="";
				for (var k=row_counter+1; k < time_series_data_array.length; k++)
				{
					if (time_series_data_array[k]['impressions'] != "0") 
					{
						next_non_zero_start_date = time_series_data_array[k]['start_date'];
						break;
					}
				}
				//validation
				if (next_non_zero_start_date != "" && new Date(end_date) >= new Date(next_non_zero_start_date))
				{
					error_flag = "Please change dates so this end date doesn't overlap with later flights";
					show_ts_error(error_flag, me);
				}
				var row_start_date = "initial_start_date"+row_counter;
				row_start_date = document.getElementById(row_start_date).value;
				if (row_start_date != undefined && new Date(end_date) <= new Date(row_start_date))
				{
					error_flag = "End Date should be after start date";
					show_ts_error(error_flag, me);
				}

				if (error_flag == "")
				{
					var original_end_date = time_series_data_array[row_counter+1]['start_date'];
					var end_date_new = new Date(end_date);
					end_date_new.setDate(end_date_new.getDate()+1);
					end_date_new_string=end_date_new.date_timeseries_format();
					if (original_end_date != end_date_new_string)
					{
						var new_element=new Array();
						new_element['start_date']=end_date_new_string;
						new_element['impressions']='0';
						time_series_data_array.splice(row_counter+1, 0, new_element); 
					}
				}
			}
		} 

		if (error_flag == "")
		{
			refresh_time_series_div();
			show_ts_error('Flights changed successfully', undefined, 0);
		}
		
	}

	function create_timeseries_row(start_date, end_date, initial_target_impressions, row_counter)
	{
		var date_picker_class="datepicker_recurring_start";
		initial_target_impressions=format_impressions(initial_target_impressions);
		var paused_status = "";
		if (initial_target_impressions == '0')
		{
			paused_status = '<span class="label label-warning">Off</span>';
		}
		
		return '<div  style="display:flex; flex-direction:row;"><div id="initial_start_date'+row_counter+'_div"  class="ts_io_input input-append date '+date_picker_class+'">'+
	    	'<input type="text" value="'+start_date+'" class="input-small" id="initial_start_date'+row_counter+
	    	'" onblur="refresh_timeseries_data(3, '+row_counter+', null, this.value, null, this)"  style="font-size:12px"/>'+
	    	'<span class="add-on"><i data-time-icon="icon-time" data-date-icon="icon-calendar" class="icon-calendar"></i></span>'+
	    '</div>&nbsp;'+
	    '<div id="initial_end_date'+row_counter+'_div"  class="ts_io_input input-append date '+date_picker_class+'">'+
	    	'<input type="text" value="'+end_date+'" data-format="MM/dd/yyyy"  class="input-small datepicker_input" id="initial_end_date'+row_counter+
	    	'" '+
	    	'onblur="refresh_timeseries_data(3, '+row_counter+', null, null, this.value, this)" style="font-size:12px"/>'+
	    	'<span class="add-on"><i data-time-icon="icon-time" data-date-icon="icon-calendar" class="icon-calendar"></i></span>'+
	    '</div>&nbsp;'+
	    '<div class="ts_io_input">'+
	    	'<input type="text" class="input-small" id="initial_target_impressions+row_counter+" value="'+initial_target_impressions+
	    	'" onkeyup="format_impressions_set_data(this)" onblur="refresh_timeseries_data(3, '+row_counter+', this.value, null, null, this)" style="font-size:12px"/>&nbsp;'+
	    	'<button id="option_remove_button" class="btn  btn-link intro-chardin" type="button" title="Remove flight" '+
	    	' onclick="refresh_timeseries_data(2, '+row_counter+', null, null, null, this)">'+
	    	'<span class="label label-important">'+
	    	'<i data-time-icon="icon-remove" class="icon-remove icon-white" ></i></span></button>'+paused_status+
		'</div></div>';
 	}

	function format_impressions_set_data(me)
	{
		var data=me.value;
		me.value=format_impressions(data);
		return;
	}
	
	function format_impressions(data)
	{
		if (data == undefined || data == "" || isNaN(data))
			return data;
		data=""+data;
		return data.replace(/,/gi, "").split(/(?=(?:\d{3})+$)/).join(",");
	}

	function clean_format_impressions(data)
	{
		if (data != undefined && data != "") 
		{
			data=""+data;
			data=data.replace(/,/gi, "");
		}
		return data;
	}


	function populate_time_series_array_for_existing_campaign(returned_data_array)
	{
		time_series_data_array=new Array();
		if (returned_data_array != undefined && returned_data_array.length > 0)
		{
			for (var i=0 ; i < returned_data_array.length; i++)
			{
				time_series_data_array[i]=new Array();
				time_series_data_array[i]['start_date']=returned_data_array[i]['series_date'];
				time_series_data_array[i]['impressions']=returned_data_array[i]['impressions'];
			}
			refresh_time_series_div();
		}
		else // if data not found for an existing campaign, show the intial time series div
		{
			populate_default_timeseries_wizard();
		}
	}

	function get_timeseries_string_from_array()
	{
		time_series_string="";
		for (var i=0; i < time_series_data_array.length; i++)
		{
			if (i < time_series_data_array.length-1 && (time_series_data_array[i]['start_date'] == "" || time_series_data_array[i]['impressions'].length == 0))
			{
				return "ERROR:- Date and Impressions are required fields for flights";
			}
			if (i < time_series_data_array.length-1 && 
				(new Date(time_series_data_array[i]['start_date']).getTime()) >= (new Date(time_series_data_array[i+1]['start_date']).getTime()))
			{
				return "ERROR:- Dates overlap. Check "+ time_series_data_array[i]['start_date'];
			}

			if (time_series_data_array[i]['start_date'].length < 8 || time_series_data_array[i]['start_date'].length > 10)
			{
				return "ERROR:- Date format should be mm/dd/yyyy. Check " + time_series_data_array[i]['start_date'];
			}
			var temp_impressions=clean_format_impressions(time_series_data_array[i]['impressions']);

			if (isNaN(parseInt(temp_impressions)))
			{
				return "ERROR:- Impressions should be a number";
			}
			time_series_string+= time_series_data_array[i]['start_date'] + "*" + temp_impressions + "^";
		}

		return time_series_string;
	}

	function get_timeseries_from_collection()
	{
		var time_series_data = [];

		$('#io_flights_modal #flights-collection li.collection-item').each(function(i, val){
			time_series_data.push({
				'start_date': $(this).find('.start_date').text(),
				'impressions': clean_format_impressions($(this).find('input.io-single-flight-impressions').val())
			});
		});

		return filter_empty_flights(time_series_data);
	}

	function filter_empty_flights(time_series_data)
	{
		return time_series_data.filter(function(flight, i, time_series){
			if (i === 0)
			{
				return parseInt(flight.impressions) !== 0;
			}
			else if (time_series[i+1] !== undefined)
			{
				return parseInt(flight.impressions) !== 0 || parseInt(time_series[i-1].impressions) !== 0;
			} else
			{
				return parseInt(flight.impressions) === 0 && parseInt(time_series[i-1].impressions) !== 0;
			}
		});
	}

function show_ts_error(error_msg, me, status_flag)
{	
	if (me != undefined) {
		me.focus();
		me.style.backgroundColor = "#F5CBC6";
	}
	if (status_flag != undefined && status_flag==0)
	{
		error_msg="<span style='color:#334D03'><b>"+error_msg+"</b></span>";
	}	
	else
	{
		error_msg="<span style='color:#b94a48'><b>"+error_msg+"</b></span>";
	}
	
	$("#tags_alert_div").html(error_msg);
	$("#tags_alert_div").show();

}
