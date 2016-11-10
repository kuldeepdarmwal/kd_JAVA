#!/usr/bin/env php
<?php

define('BASEPATH', '/dev/null');
require_once(__DIR__ . '/../../application/libraries/swiffy.php');

$input = file_get_contents('php://stdin');
if(empty($input))
{
	exit('Please pipe in Swiffy HTML.');
}

$swiffy = new swiffy();

if(isset($argv[1]))
{
	switch($argv[1])
	{
	case 'hover':
		$id_prefix = 'hover_';
		$options = ['transparent' => TRUE];
		break;
	default:
		echo "Unknown Swiffy config {$argv[1]}. Leave blank, or use 'hover'.";
	}
}

echo $swiffy->get_js_from_swiffy_html($input, $id_prefix, $options);
