<?php
require_once 'class.base_object.php';

/**
 * Content Handler
 * @package Simple Lightbox
 * @subpackage Content Handler
 * @author Archetyped
 */
class SLB_Content_Handler extends SLB_Base_Object {
	/* Properties */
	
	/**
	 * Match handler
	 * @var callback
	 */
	protected $match;
	
	/* Init */
	
	/**
	 * Constructor
	 * @param string $id Unique ID for content type
	 * @param array $props (optional) Type properties (optional because props can be set post-init)
	 */
	public function __construct($id, $props = null) {
		parent::__construct($id);
		$this->set_props($props);
	}
	
	/* Get/Set */
	
	/**
	 * Set type properties
	 * @param array $props Type properties to set
	 */
	public function set_props($props) {
		if ( !empty($props) && is_array($props) ) {
			foreach ( $props as $key => $val ) {
				//Check for setter method
				$m = 'set_' . $key;
				if ( method_exists($this, $m) ) {
					$this->{$m}($val);
				}
			}
		}
		return $this;
	}
	
	/* Matching */
		
	/**
	 * Set matching handler
	 * @param callback $callback Handler callback
	 * @return object Current instance
	 */
	public function set_match($callback) {
		$this->match = ( is_callable($callback) ) ? $callback : null;
		return $this;
	}
	
	/**
	 * Retrieve match handler
	 * @return callback|null Match handler
	 */
	protected function get_match() {
		return $this->match;
	}
	
	/**
	 * Check if valid match set
	 */
	protected function has_match()	{
		return ( is_null($this->match) ) ? false : true;
	}
	
	/**
	 * Match handler against URI
	 * @param string $uri URI to check for match
	 * @return bool TRUE if handler matches URI
	 */
	public function match($uri) {
		$ret = false;
		if ( !!$uri && is_string($uri) && $this->has_match() ) {
			$ret = call_user_func($this->get_match(), $uri);
		}
		return $ret;
	}
	
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
				$ret = $s['path'];
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