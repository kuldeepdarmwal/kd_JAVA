<?php

function __autoload($className=null) {
	//echo 'autoload BarChart.<br />';

	$classes = array (
        'QConfig.class.php', 
        'QInflector.class.php', 
        'QTool.class.php', 
        'QGoogleGraph.class.php', 
        'QVizualisationGoogleGraph.class.php', 
        'QApikeyGoogleGraph.class.php', 
	);

	foreach($classes as $class) {
    include_once($class);
		//include_once($class);
	}

	//echo 'className: '.$className.'<br />';
  include_once($className.".class.php");
	//include_once($className.".class.php");
}

function myLoadGoogleGraph($className) {
	//echo 'autoload BarChart.<br />';

	$classes = array (
        'QConfig.class.php', 
        'QInflector.class.php', 
        'QTool.class.php', 
        'QGoogleGraph.class.php', 
        'QVizualisationGoogleGraph.class.php',
        'QBarchartGoogleGraph.class.php',
        'QApikeyGoogleGraph.class.php', 
	);

	foreach($classes as $class) {
            include_once($_SERVER['DOCUMENT_ROOT'].'/VISUAL_API/'.$class);
		//include_once($class);
	}

	//echo 'className: '.$className.'<br />';
  include_once($_SERVER['DOCUMENT_ROOT'].'/VISUAL_API/'.$className.".class.php");
	//include_once($className.".class.php");
}

?>
