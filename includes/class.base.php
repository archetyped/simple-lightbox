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
	function init() {}
	
	/**
	 * Returns callback to instance method
	 * @param string $method Method name
	 * @return array Callback array
	 */
	function &m($method) {
		return $this->util->m($this, $method);
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
	 * Retrieve class prefix (with separator if set)
	 * @param bool|string $sep Separator to append to class prefix (Default: no separator)
	 * @return string Class prefix
	 */
	function get_prefix($sep = false) {
		$sep = ( is_string($sep) ) ? $sep : '';
		$prefix = ( !empty($this->prefix) ) ? $this->prefix . $sep : '';
		return $prefix;
	}
	
	/**
	 * Prepend plugin prefix to some text
	 * @param string $text Text to add to prefix
	 * @param string $sep Text used to separate prefix and text
	 * @return string Text with prefix prepended
	 */
	function add_prefix($text = '', $sep = '_') {
		return $this->get_prefix($sep) . $text;
	}
	
	function remove_prefix($text = '', $sep = '_') {
		if ( !empty($text) && strpos($text, ( $p = $this->get_prefix($sep) )) === 0 )
			$text = substr($text, strlen($p));
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