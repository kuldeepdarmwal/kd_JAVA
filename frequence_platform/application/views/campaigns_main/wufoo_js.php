<script src="../js/campaign_health/jquery.stickytableheader.js" type="text/javascript"></script> 
<script type="text/javascript">
$(document).ready(function () {
	tables = document.getElementsByTagName('table');
                        for (var t = 0; t < tables.length; t++) {
                                element = tables[t];

                                        /* Here is dynamically created a form */
                                        var form = document.createElement('form');
                                        form.setAttribute('class', 'filter');
                                        // For ie...
                                        form.attributes['class'].value = 'filter';
                                        var input = document.createElement('input');
                                        input.onkeyup = function() {
                                                filterTable(input, element);
                                        }
                                        form.appendChild(input);
                                        element.parentNode.insertBefore(form, element);

                        }
	$("table").stickyTableHeaders();
	});

</script>
<script type="text/javascript" src="/js/campaign_health/filter_table.js"></script>