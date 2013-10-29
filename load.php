<?php
/**
 * Class loading handler
 * @param string $classname Class to load
 */
function slb_autoload($classname) {
	$prefix = 'slb_';
	$cls = strtolower($classname);
	//Remove prefix
	if ( 0 !== strpos($cls, $prefix) ) {
		return false;
	}
	//Format class for filename
	$fn = 'class.' . substr($cls, strlen($prefix)) . '.php';
	//Build path
	$path = dirname(__FILE__) . '/' . "includes/" . $fn;
	//Load file
	if ( is_readable($path) ) {
		require $path;
	}
}

spl_autoload_register('slb_autoload');