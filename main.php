<?php
/**
 * Plugin entrypoint
 *
 * @package Simple Lightbox
 * @author Archetyped <support@archetyped.com>
 * @copyright 2018 Archetyped
 */

/*
Plugin Name: Simple Lightbox
Plugin URI: http://archetyped.com/tools/simple-lightbox/
Description: The highly customizable lightbox for WordPress
Version: 2.7.1
Text Domain: simple-lightbox
Domain Path: /l10n
Author: Archetyped
Author URI: http://archetyped.com
Support URI: https://github.com/archetyped/simple-lightbox/wiki/Feedback-&-Support
*/

require_once dirname( __FILE__ ) . '/includes/class-requirements-check.php';

/* @var array Plugin Requirements */
$slb_requirements = new SLB_Requirements_Check(
	array(
		'name' => __( 'Simple Lightbox', 'simple-lightbox' ),
		'file' => __FILE__,
		'uri'  => array(
			'reference' => 'https://github.com/archetyped/simple-lightbox/wiki/Requirements',
		),
	)
);

// Check requirements before initializing plugin.
if ( $slb_requirements->passes() ) {
	/**
	 * Initialize SLB
	 *
	 * @return void
	 */
	function slb_init() {
		require_once dirname( __FILE__ ) . '/load.php';
	}
	add_action( 'init', 'slb_init', 1 );
}

unset( $slb_requirements );
