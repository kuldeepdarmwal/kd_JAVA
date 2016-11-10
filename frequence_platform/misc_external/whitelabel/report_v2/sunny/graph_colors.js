var impression_color = 'rgb(91, 202, 233)';     
var impression_fade_color_0 = 'rgb(91, 202, 233)';     
var impression_fade_color_1 = 'rgb(91, 202, 233)';

var retargeting_color = 'rgb(9, 105, 164)';     
var retargeting_fade_color_0 = 'rgb(9, 105, 164)';     
var retargeting_fade_color_1 = 'rgb(9, 105, 164)';

var clickscolor = 'white';     
var retargetingclickscolor = 'rgb(210, 210, 210)';     
var clicksborder_color = 'grey';

var legendbackgroundcolor = 'rgb(192, 193, 194)';     
var legendhiddencolor = legendbackgroundcolor;

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
		impression_color = 'rgba(91, 202, 233, 1)';     
		impression_fade_color_0 = 'rgba(91, 202, 233, 0.8)';     
		impression_fade_color_1 = 'rgba(91, 202, 233, 0.3)';

		retargeting_color = 'rgba(9, 105, 164, 1)';     
		retargeting_fade_color_0 = 'rgba(9, 105, 164, 0.6)';     
		retargeting_fade_color_1 = 'rgba(9, 105, 164, 0.6)';

		clickscolor = 'white';     
		retargetingclickscolor = 'rgba(210, 210, 210,1)';     
		clicksborder_color = 'grey';

		legendbackgroundcolor = 'rgba(192, 193, 194, 0.25)';     
		legendhiddencolor = legendbackgroundcolor;

		engagements_color = 'rgba(255, 0, 0, 1)';
	}
}
