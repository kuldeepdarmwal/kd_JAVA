<?php

/**
 * Wrapper for Google Visualisation API
 * Visualisation type: Barchart
 *
 */
class QBarchartGoogleGraph extends QVizualisationGoogleGraph {

	/**
	 * visualisation type holder
	 *
	 * @var string
	 */
	protected $vizualisationType = "BarChart";
	//protected $vizualisationType = "CoreChart";

	/**
	 * holder for google api package name
	 *
	 * @var string
	 */
	protected $drawProperties = array("width"=>400,"height"=>240,"legend"=>"bottom");

	/**
	 * holder for default properties
	 *
	 * @var array
	 */
	protected $package = "";

	/**
	 * holder for registered api methods
	 *
	 * @var array
	 */
	protected $configuration = array(
		"axisColor" => array("datatype" => "string,object"),
		"axisBackgroundColor" => array("datatype" => "string,object"),
		"axisFontSize"=>array("datatype" => "string"), 
		"backgroundColor" => array("datatype" => "string,object"),
		"is3D" => array("datatype" => "bool"),
		"borderColor" => array("datatype" => "string,object"),
		"colors" => array("datatype" => "array"),
		"focusBorderColor" => array("datatype" => "string,object"),
		"height" => array("datatype" => "integer"),
		"isStacked" => array("datatype" => "bool"),
		"legend" => array(
			"values" => array("right", "left", "top", "bottom", "none"), 
			"datatype" => "string"
		),  
		"legendBackgroundColor" => array("datatype" => "string,object"),
		"legendTextColor" => array("datatype" => "string,object"),
		"lineSize" => array("datatype" => "integer"), 
		"max" => array("datatype" => "string"), 
		"min" => array("datatype" => "string"), 
		"pointSize" => array("datatype" => "integer"),
		"reverseAxis" => array("datatype" => "bool"),
		"title" => array("datatype" => "string"), 
		"titleX" => array("datatype" => "string"),
		"titleY" => array("datatype" => "string"),
		"titleColor" => array("datatype" => "string,object"),
		"width" => array("datatype" => "integer"),
	);

}
