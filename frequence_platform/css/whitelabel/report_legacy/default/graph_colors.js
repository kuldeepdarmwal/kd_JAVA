impression_color = 'rgb(0,136,204)';     
impression_fade_color_0 = 'rgb(0,136,204)';     
impression_fade_color_1 = 'rgb(0,136,204)';

retargeting_color = 'rgb(255,86,25)';     
retargeting_fade_color_0 = 'rgb(255,86,25)';     
retargeting_fade_color_1 = 'rgb(255,86,25)';

var clickscolor = 'rgb(80,201,181)';     
var retargetingclickscolor = 'rgb(210, 210, 210)';     
var clicksborder_color = 'grey';

var legendbackgroundcolor = 'rgb(192, 193, 194)';     
var legendhiddencolor = 'rgb(165, 165, 165)';

var engagements_color = 'rgb(255, 0, 0)';

function apply_alpha_if_available()
{
	function contains(str, substr)
	{
		return !!~('' + str).indexOf(substr);
	}

	var test_rgba_element = document.createElement('test_rgba');
	var test_rgba_style = test_rgba_element.style;
	test_rgba_style.cssText = 'background-color:rgba(150, 255, 150, 0.5)';
	if(contains(test_rgba_style.backgroundColor, 'rgba'))
	{
		impression_color = 'rgba(0, 97, 156, 1)';     
		impression_fade_color_0 = 'rgba(0, 97, 156, 1)';     
		impression_fade_color_1 = 'rgba(0, 97, 156, 1)';

		retargeting_color = 'rgba(255, 189, 76, 1)';     
		retargeting_fade_color_0 = 'rgba(255, 189, 76, 1)';     
		retargeting_fade_color_1 = 'rgba(255, 189, 76, 1)';

		var clickscolor = 'rgb(80,201,181)';     
		var retargetingclickscolor = 'rgb(210, 210, 210)';     
		var clicksborder_color = 'grey';

		var legendbackgroundcolor = 'rgba(192, 193, 194, 0)';     
		var legendhiddencolor = 'rgba(165, 165, 165, 0)';

		var engagements_color = 'rgb(255, 0, 0)';
	}

}
