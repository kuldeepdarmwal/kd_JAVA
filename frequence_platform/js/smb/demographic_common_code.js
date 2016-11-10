var customColor = '#5bcae9';

function sparkline(length, percent, id, final_length) {
	var maxInputLength = 200;
	var finalLength = final_length || 106;
	var indexValue = length*finalLength / maxInputLength;
	var fullBar = finalLength;
	if(indexValue > fullBar)
	{
		indexValue = fullBar;
	}

	var halfBar = fullBar / 2;
	var backgroundColor = '#cacaca';
	var barColor = '#414142';
	if(indexValue > halfBar)
	{
		barColor = customColor;
	}
	var targetValue = halfBar;

	$(id).sparkline(
		[targetValue,indexValue,fullBar,indexValue,indexValue], 
		{
			type: 'bullet',
			width: finalLength,
			targetWidth: 1,
			height: '12',
			targetColor: '#f6f6f6',
			performanceColor: '#',
			rangeColors: [backgroundColor, barColor, barColor],
			tooltipFormatter: function(sparkline, options, fields){
				return percent + "%";
			}
		}
	);
}
