<?php
/* 
Plugin Name: Simple Lightbox
Plugin URI: http://archetyped.com/tools/simple-lightbox/
Description: The highly customizable lightbox for WordPress
Version: 2.3.1
Author: Archetyped
Author URI: http://archetyped.com
Support URI: https://github.com/archetyped/simple-lightbox/wiki/Feedback-&-Support
*/
/*
Copyright 2014 Sol Marchessault (sol@archetyped.com)
*/
$slb = null;
/**
 * Initialize SLB
 */
function slb_init() {
	$path = dirname(__FILE__) . '/';
	require_once $path . 'load.php';
}

add_action('init', 'slb_init', 1);