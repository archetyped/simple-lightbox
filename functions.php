<?php
/**
 * Functions
 * Provides global access to specific functionality
 * @package Simple Lightbox
 * @author Archetyped
 */

/* Template Tags */
 
/**
 * Activate links in user-defined content
 * @param string $content
 * @return string Updated content with activated links
 */
function slb_activate($content, $group = null) {
	// Validate
	if ( empty($content) ) {
		return $content;
	}
	// Activate links
	$content = $GLOBALS['slb']->activate_links($content, $group);
	return $content;
}
