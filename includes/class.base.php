<?php

require_once 'class.utilities.php';

/**
 * @package Simple Lightbox
 * @subpackage Base
 * @author Archetyped
 *
 */
class SLB_Base {
	
	/**
	 * Variable name of base object in global scope
	 * @var string
	 */
	var $base = 'slb';
	
	/**
	 * Prefix for Cornerstone-related data (attributes, DB tables, etc.)
	 * @var string
	 */
	var $prefix = 'slb';
	
	/**
	 * Utilities
	 * @var SLB_Utilities
	 */
	var $util = null;
	
	/**
	 * Legacy constructor
	 */
	function SLB_Base() {
		$this->__construct();
	}
	
	/**
	 * Constructor
	 */
	function __construct() {
		$this->util =& new SLB_Utilities($this);
	}
	
	/*-** Init **-*/
	
	/**
	 * Default initialization method
	 * To be overriden by child classes
	 */
	function init() {
		$func = 'register_hooks';
		if ( isset($this) && method_exists($this, $func) ) {
			call_user_method($func, $this);
		}
	}
	
	function register_hooks() {
		//Activation
		$func_activate = 'activate';
		if ( method_exists($this, $func_activate) )
			register_activation_hook($this->util->get_plugin_base_file(), $this->m($func_activate));
		//Deactivation
		$func_deactivate = 'deactivate';
		if ( method_exists($this, $func_deactivate) )
			register_deactivation_hook($this->util->get_plugin_base_file(), $this->m($func_deactivate));
	}
	
	/*-** Reflection **-*/
	
	/**
	 * Retrieve base object
	 * @return object|bool Base object (FALSE if object does not exist)
	 */
	function &get_base() {
		return ( isset($GLOBALS[$this->base]) ) ? $GLOBALS[$this->base] : false;
	}
	
	/*-** Method/Function calling **-*/
	
	/**
	 * Returns callback to instance method
	 * @param string $method Method name
	 * @return array Callback array
	 */
	function &m($method) {
		return $this->util->m($this, $method);
	}
	
	/*-** Prefix **-*/
	
	/**
	 * Retrieve class prefix (with separator if set)
	 * @param bool|string $sep Separator to append to class prefix (Default: no separator)
	 * @return string Class prefix
	 */
	function get_prefix($sep = null) {
		$args = func_get_args();
		return call_user_func_array($this->util->m($this->util, 'get_prefix'), $args);
	}
	
	/**
	 * Check if a string is prefixed
	 * @param string $text Text to check for prefix
	 * @param string $sep (optional) Separator used
	 */
	function has_prefix($text, $sep = null) {
		$args = func_get_args();
		return call_user_func_array($this->util->m($this->util, 'has_prefix'), $args);
	}
	
	/**
	 * Prepend plugin prefix to some text
	 * @param string $text Text to add to prefix
	 * @param string $sep (optional) Text used to separate prefix and text
	 * @param bool $once (optional) Whether to add prefix to text that already contains a prefix or not
	 * @return string Text with prefix prepended
	 */
	function add_prefix($text, $sep = null, $once = true) {
		$args = func_get_args();
		return call_user_func_array($this->util->m($this->util, 'add_prefix'), $args);
	}
	
	/**
	 * Remove prefix from specified string
	 * @param string $text String to remove prefix from
	 * @param string $sep (optional) Separator used with prefix
	 */
	function remove_prefix($text, $sep = null) {
		$args = func_get_args();
		return call_user_func_array($this->util->m($this->util, 'remove_prefix'), $args);
	}
}

?>