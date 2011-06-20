<?php 
/* 
Plugin Name: Simple Lightbox
Plugin URI: http://archetyped.com/tools/simple-lightbox/
Description: Customizable Lightbox for Wordpress
Version: 1.5.5
Author: Archetyped
Author URI: http://archetyped.com
*/
/* 
Copyright 2010 Solomon Marchessault (contact@archetyped.com)

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

require_once 'model.php';

$slb =& new SLB_Lightbox();

function slb_enabled() {
	global $slb;
	return $slb->is_enabled();
}

function slb_register_theme($name, $title, $stylesheet_url, $layout) {
	global $slb;
	$slb->register_theme($name, $title, $stylesheet_url, $layout);
}

?>
