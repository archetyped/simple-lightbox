<?php
require_once 'class.base_object.php';

/**
 * Component
 * @package Simple Lightbox
 * @subpackage Base
 * @author Archetyped
 */
class SLB_Component extends SLB_Base_Object {
	/* Properties */
	
	/**
	 * Pretty name
	 * @var string
	 */
	protected $name = '';
	
	protected $props_required = array();
	
	private $props_required_base = array('id');
	
	/* Get/Set */
	
	/**
	 * Set name
	 * @param string $name Name
	 * @return Current instance
	 */
	public function set_name($name) {
		if ( is_string($name) ) {
			$name = trim($name);
			if ( !empty($name) ) {
				$this->name = $name;
			}
		}
		return $this;
	}
	
	public function get_name() {
		return $this->name;
	}
	
	/* Helpers */
	
	/**
	 * Validate instance
	 * @see `Base_Object::is_valid()`
	 * @return bool Valid (TRUE) / Invalid (FALSE)
	 */
	public function is_valid() {
		$ret = parent::is_valid();
		if ( $ret ) {
			//Check required component properties
			$props = array_merge($this->props_required_base, $this->props_required);
			foreach ( $props as $prop ) {
				if ( !isset($this->{$prop}) || empty($this->{$prop}) ) {
					$ret = false;
					break;
				}
			}
		}
		return $ret;
	}
	
	/* Client */
	
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
		return $this->get_script('client', $format);
	}
}