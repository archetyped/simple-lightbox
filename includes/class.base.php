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
	 * Prefix for plugin-related data (attributes, DB tables, etc.)
	 * @var string
	 */
	var $prefix = 'slb';

	/**
	 * Client files
	 * @var array
	 * Structure
	 * > Key: unique file ID
	 * > Properties
	 *   > file (string) File path (Relative to plugin base)
	 *   > deps (array) Script dependencies
	 * 		> Internal dependencies are wrapped in square brackets ([])
	 *   > context (string|array)
	 * 		> Context in which the script should be included
	 *   > in_footer (bool) optional [Default: FALSE]
	 * 		> If TRUE, file will be included in footer of page, otherwise it will be included in the header
	 * 
	 * Array is processed and converted to an object on init
	 */
	var $client_files = array(
		'scripts'	=> array(),
		'styles'	=> array()
	);
	
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
	 * To be overridden by child classes
	 */
	function init() {
		if ( !isset($this) )
			return false;
		
		/* Client files */
		$this->init_client_files();
		
		/* Hook */
		$this->register_hooks();
		
		/* Environment */
		$env = 'init_env';
		if ( method_exists($this, $env) )
			add_action('init', $this->m($env));
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
	
	function init_client_files() {
		foreach ( $this->client_files as $key => $val ) {
			if ( empty($val) && isset($this->{$key}) )
				$this->client_files[$key] =& $this->{$key};
			$g =& $this->client_files[$key];
			if ( is_array($g) && !empty($g) ) {
				$g = $this->util->parse_client_files($g, $key);
			}
		}

		//Register
		add_action('init', $this->m('register_client_files'));
		
		//Enqueue
		$hook_enqueue = ( ( is_admin() ) ? 'admin' : 'wp' ) . '_enqueue_scripts' ;
		add_action($hook_enqueue, $this->m('enqueue_client_files'));
	}
	
	function register_client_files() {
		//Scripts
		foreach ( $this->client_files as $type => $files ) {
			if ( !empty($files) ) {
				$func = $this->get_client_files_handler($type, 'register');
				if ( !$func )
					continue;
				foreach ( $files as $f ) {
					$params = array($f->id, $this->util->get_file_url($f->file), $f->deps, $this->util->get_plugin_version());
					switch ( $type ) {
						case 'scripts':
							$params[] = $f->in_footer;
							break;
						case 'styles':
							$params[] = $f->media;
							break;
					}
					call_user_func_array($func, $params);
				}
			}
		}
	}
	
	/**
	 * Enqueues files for client output (scripts/styles)
	 * Called by appropriate `enqueue_scripts` hook depending on context (admin or frontend)
	 * @return void
	 */
	function enqueue_client_files() {
		//Enqueue files
		foreach ( $this->client_files as $type => $files ) {
			if ( !empty($files) ) {
				$func = $this->get_client_files_handler($type, 'enqueue');
				if ( !$func )
					continue;
				foreach ( $files as $f ) {
					if ( empty($f->context) || $this->util->is_context($f->context) ) {
						$func($f->id);
					}
				}
			}
		}
	}
	
	/**
	 * Build function name for handling client operations
	 */
	function get_client_files_handler($type, $action) {
		$func = 'wp_' . $action . '_' . substr($type, 0, -1);
		if ( !function_exists($func) )
			$func = false;
		return $func;
	}
	
	/*-** Reflection **-*/
	
	/**
	 * Retrieve base object
	 * @return object|bool Base object (FALSE if object does not exist)
	 */
	function &get_base() {
		$base = false;
		if ( isset($GLOBALS[$this->base]) )
			$base =& $GLOBALS[$this->base]; 
		return $base;
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
	 * Add prefix to variable reference
	 * Updates actual variable rather than return value
	 * @uses SLB_Utilities::add_prefix_ref();
	 * @param string $var Variable to add prefix to
	 * @param string $sep (optional) Separator text
	 * @param bool $once (optional) Add prefix only once
	 * @return void
	 */
	function add_prefix_ref(&$var, $sep = null, $once = true) {
		$args = func_get_args();
		$args[0] =& $var;
		call_user_func_array($this->util->m($this->util, 'add_prefix_ref'), $args);
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