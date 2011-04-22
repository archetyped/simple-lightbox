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
		$this->util =& new SLB_Utilities();
	}
	
	/**
	 * Default initialization method
	 * To be overriden by child classes
	 */
	function init() {
		$func = 'register_hooks';
		if ( isset($this) ) {
			if ( method_exists($this, $func) )
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
	
	/*-** Method/Function calling **-*/
	
	/**
	 * Returns callback to instance method
	 * @param string $method Method name
	 * @return array Callback array
	 */
	function &m($method) {
		return $this->util->m($this, $method);
	}
	
	/*-** Hooks **-*/
	
	function do_action($tag, $arg = '') {
		do_action($this->add_prefix($tag), $arg);
	}
	
	function apply_filters($tag, $value) {
		apply_filters($this->add_prefix($tag), $value);
	}
	
	function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
		return add_action($this->add_prefix($tag), $function_to_add, $priority, $accepted_args);
	}
	
	function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
		return add_filter($this->add_prefix(tag), $function_to_add, $priority, $accepted_args);
	}
	
	/**
	 * Retrieves post metadata for internal methods
	 * Metadata set internally is wrapped in an array so it is unwrapped before returned the retrieved value
	 * @see get_post_meta()
	 * @param int $post_id Post ID
	 * @param string $key Name of metadata to retrieve
	 * @param boolean $single Whether or not to retrieve single value or not
	 * @return mixed Retrieved post metadata
	 */
	function post_meta_get($post_id, $key, $single = false) {
		$meta_value = get_post_meta($post_id, $this->post_meta_get_key($key), $single);
		if (is_array($meta_value) && count($meta_value) == 1)
			$meta_value = $meta_value[0];
		return $meta_value;
	}
	
	/**
	 * Wraps metadata in array for storage in database
	 * @param mixed $meta_value Value to be set as metadata
	 * @return array Wrapped metadata value
	 */
	function post_meta_prepare_value($meta_value) {
		return array($meta_value);
	}
	
	/**
	 * Adds Metadata for a post to database
	 * For internal methods
	 * @see add_post_meta
	 * @param $post_id
	 * @param $meta_key
	 * @param $meta_value
	 * @param $unique
	 * @return boolean Result of operation
	 */
	function post_meta_add($post_id, $meta_key, $meta_value, $unique = false) {
		$meta_value = $this->post_meta_value_prepare($meta_value);
		return add_post_meta($post_id, $meta_key, $meta_value, $unique);
	}
	
	/**
	 * Updates post metadata for internal data/methods
	 * @see update_post_meta()
	 * @param $post_id
	 * @param $meta_key
	 * @param $meta_value
	 * @param $prev_value
	 * @return boolean Result of operation
	 */
	function post_meta_update($post_id, $meta_key, $meta_value, $prev_value = '') {
		$meta_value = $this->post_meta_prepare_value($meta_value);
		return update_post_meta($post_id, $meta_key, $meta_value, $prev_value);
	}
	
	/**
	 * Builds postmeta key for custom data set by plugin
	 * @param string $key Base key name 
	 * @return string Formatted postmeta key
	 */
	function post_meta_get_key($key) {
		$sep = '_';
		if ( strpos($key, $sep . $this->prefix) !== 0 ) {
			$key_base = func_get_args();
			if ( !empty($key_base) ) {
				$key = array_merge((array)$this->prefix, $key_base);
				return $sep . implode($sep, $key);
			}
		}
		
		return $key;
	}
	
	/**
	 * Get valid separator
	 * @param string $sep (optional) Separator supplied
	 * @return string Separator
	 */
	function get_sep($sep = null) {
		if ( !is_string($sep) )
			$default = '_';
		return ( is_string($sep) ) ? $sep : $default;
	}
	
	/**
	 * Retrieve class prefix (with separator if set)
	 * @param bool|string $sep Separator to append to class prefix (Default: no separator)
	 * @return string Class prefix
	 */
	function get_prefix($sep = null) {
		$sep = $this->get_sep($sep);
		$prefix = ( !empty($this->prefix) ) ? $this->prefix . $sep : '';
		return $prefix;
	}
	
	/**
	 * Check if a string is prefixed
	 * @param string $text Text to check for prefix
	 * @param string $sep (optional) Separator used
	 */
	function has_prefix($text, $sep = null) {
		return ( !empty($text) && strpos($text, $this->get_prefix($sep)) === 0 );
	}
	
	/**
	 * Prepend plugin prefix to some text
	 * @param string $text Text to add to prefix
	 * @param string $sep (optional) Text used to separate prefix and text
	 * @param bool $once (optional) Whether to add prefix to text that already contains a prefix or not
	 * @return string Text with prefix prepended
	 */
	function add_prefix($text, $sep = null, $once = true) {
		if ( $this->has_prefix($text, $sep) )
			return $text;
		return $this->get_prefix($sep) . $text;
	}
	
	/**
	 * Remove prefix from specified string
	 * @param string $text String to remove prefix from
	 * @param string $sep (optional) Separator used with prefix
	 */
	function remove_prefix($text, $sep = null) {
		if ( $this->has_prefix($text,$sep) )
			$text = substr($text, strlen($this->get_prefix($sep)));
		return $text;
	}
	
	/**
	 * Creates a meta key for storing post meta data
	 * Prefixes standard prefixed text with underscore to hide meta data on post edit forms
	 * @param string $text Text to use as base of meta key
	 * @return string Formatted meta key
	 */
	function make_meta_key($text = '') {
		return '_' . $this->add_prefix($text);
	}
	
	/**
	 * Returns Database prefix for Cornerstone-related DB Tables
	 * @return string Database prefix
	 */
	function get_db_prefix() {
		global $wpdb;
		return $wpdb->prefix . $this->get_prefix('_');
	}
}

?>