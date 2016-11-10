$(document).ready(function() {

  // get the action filter option item on page load
  var $filterType = $('#filterOptions li.active a').attr('class');
	
  // get and assign the ourHolder element to the
	// $holder varible for use later
  var $holder = $('ul.ourHolder');

  // clone all items within the pre-assigned $holder element
  var $data = $holder.clone();

	$('#test_box').change(function() {
		alert("change traced!");
		// reset the active class on all the buttons
		var id = $(this).find("option:selected").attr("id");
		alert(id);
		// assign the class of the clicked filter option
		// element to our $filterType variable
		var $filterType = id;
		
		if ($filterType == 'all') {
			// assign all li items to the $filteredData var when
			// the 'All' filter option is clicked
			var $filteredData = $data.find('li');
		} 
		else {
			// find all li elements that have our required $filterType
			// values for the data-type element
			var $filteredData = $data.find('li[data-type=' + $filterType + ']');
			alert($filteredData);
		}
		// call quicksand and assign transition parameters
		$holder.quicksand($filteredData, {
			duration: 800,
			easing: 'easeInOutQuad'
		});
		alert("hello");
		return false;
	});
});
