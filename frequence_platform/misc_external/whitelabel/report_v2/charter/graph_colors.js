var impression_color = 'rgb(125, 19, 21)';     
var impression_fade_color_0 = 'rgb(125, 19, 21)';     
var impression_fade_color_1 = 'rgb(125, 19, 21)';

var retargeting_color = 'rgb(190, 44, 47)';     
var retargeting_fade_color_0 = 'rgb(190, 44, 47)';     
var retargeting_fade_color_1 = 'rgb(190, 44, 47)';

var clickscolor = 'white';     
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
	// Colors with Alpah channel
	if(contains(test_rgba_style.backgroundColor, 'rgba'))
	{
		impression_color = 'rgba(125, 19, 21, 1)';     
		impression_fade_color_0 = 'rgba(125, 19, 21, 1)';     
		impression_fade_color_1 = 'rgba(125, 19, 21, 1)';

		retargeting_color = 'rgba(234, 195, 196, 1)';     
		retargeting_fade_color_0 = 'rgba(234, 195, 196, 1)';     
		retargeting_fade_color_1 = 'rgba(234, 195, 196, 1)';

		clickscolor = 'white';     
		retargetingclickscolor = 'rgba(210, 210, 210, 1)';     
		clicksborder_color = 'grey';

		legendbackgroundcolor = 'rgba(192, 193, 194, 0.25)';     
		legendhiddencolor = 'rgba(165, 165, 165, 1)';

		engagements_color = 'rgba(255, 0, 0, 1)';
	}
}
