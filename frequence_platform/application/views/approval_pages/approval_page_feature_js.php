<script type="text/javascript">
	$('#approval_page_refresh').click(function() {
		$(".approval_iframe").each(function(index) {
			$(this).attr("src", $(this).attr("src"));
		});
	});

	//Load iframes only for mobile ad
	$('#mobile_ad_units_tab').click(function() {
		$(".mobile_ad_iframe").each(function(index) {
			$(this).attr("src", $(this).attr("src"));
		});
	});

	//Load iframes related to standard ad unit
	$('#standard_ad_units_tab').click(function() {
		$(".standard_ad_iframe").each(function(index) {
			$(this).attr("src", $(this).attr("src"));
		});
	});
</script>