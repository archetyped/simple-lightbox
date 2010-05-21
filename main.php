<?php 
/* 
Plugin Name: Simple Lightbox
Plugin URI:
Description: Customizable Lightbox for Wordpress
Version: 0.5
Author: Archetyped
Author URI: http://archetyped.com
*/

require_once 'model.php';

$slb =& new SLB_Lightbox();

function slb_enabled() {
	global $slb;
	return $slb->is_enabled();
}

?>