<?php
require_once 'class.base_object.php';

/**
 * Content Handler
 * @package Simple Lightbox
 * @subpackage Content Handler
 * @author Archetyped
 */
class SLB_Template_Tag extends SLB_Base_Object {
	/* Properties */
	
	/* Client handler */
	
	/**
	 * Set client script file
	 * @see Base_Object::add_script()
	 * @param string $src File path
	 * @param array $deps (optional) File dependencies
	 */
	public function set_client_script($src, $deps = array()) {
		if ( is_array($src) ) {
			list($src, $deps) = func_get_arg(0);
		}
		return $this->add_script('client', $src, $deps);
	}
	
	/**
	 * Retrieve client script
	 * @see Base_Object::get_script()
	 * @param string $format (optional) Data format of return value
	 * @return mixed Client script data (formatted according to $format parameter)
	 */
	public function get_client_script($format = null) {
		$s = $this->get_script('client');
		switch ( $format ) {
			case 'path':
				$ret = $this->util->normalize_path(WP_PLUGIN_DIR, $s['path']);
				break;
			case 'uri':
				$ret = $this->util->normalize_path(WP_PLUGIN_URL, $s['path']);
				break;
			case 'object':
				$ret = (object) $s;
				break;
			default:
				$ret = $s;
		}
		return $ret;
	}
}