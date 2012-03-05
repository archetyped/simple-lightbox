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
	 * Prefix to be added when creating internal hook (action/filter) tags
	 * Used by Utilities
	 * @var string
	 */
	var $hook_prefix = '';
	
	/**
	 * Class type
	 * Controls initialization, etc.
	 * > full - Fully-functional class
	 * > object - Simple object class (no hooks, etc.)
	 * @var string
	 */
	var $mode = 'full';
	
	/* Client */

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
	
	/*-** Instances **-*/
	
	/**
	 * Utilities
	 * @var SLB_Utilities
	 */
	var $util = null;
	
	/**
	 * Options
	 * @var SLB_Options
	 */
	var $options = null;
	
	/**
	 * Admin instance
	 * @var SLB_Admin
	 */
	var $admin = null;
	
	/*-** Init **-*/
	
	/**
	 * Constructor
	 */
	function __construct() {
		$this->util =& new SLB_Utilities($this);
	}
	
	/**
	 * Default initialization method
	 * To be overridden by child classes
	 * @uses this::init_options()
	 * @uses this::init_client_files()
	 * @uses this::register_hooks()
	 * @uses this::init_env()
	 * @uses add_action()
	 */
	function init() {
		if ( !isset($this) )
			return false;
		
		switch ( $this->mode ) {
			case 'object' :
				break;
			default :
				//Options
				$this->init_options();
				add_action('admin_init', $this->m('init_options_text'));
				
				//Admin
				$this->init_admin();
				
				/* Client files */
				$this->init_client_files();
				
				/* Hooks */
				$this->register_hooks();
				
				/* Environment */
				add_action('init', $this->m('init_env'), 1);
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
	
	/**
	 * Checks if options are valid
	 * @param array $data Data to be used on options
	 * @return bool TRUE if options are valid, FALSE otherwise
	 */
	function is_options_valid($data, $check_var = true) {
		$class = $this->get_options_class();
		$ret = ( empty($data) || !is_array($data) || !class_exists($class) ) ? false : true;
		if ( $ret && $check_var && !is_a($this->options, $class) )
			$ret = false;
		return $ret;
	}
	
	/**
	 * Retrieves options class name
	 * @return string
	 */
	function get_options_class() {
		return $this->add_prefix_uc('Options');
	}
	
	/**
	 * Initializes admin functionality
	 * To be overridden by child class
	 */
	function init_admin() {
		if ( !empty($this->admin) && $this->util->is_a($this->admin, 'Admin') )
			$this->admin->init();
	}
	
	/**
	 * Initialize options
	 * To be called by child class
	 */
	function init_options($options_config = null) {
		if ( !$this->is_options_valid($options_config, false) )
			return false;
		$class = $this->get_options_class();
		$this->options =& new $class($options_config);
	}
	
	/**
	 * Initialize options text
	 * Must be called separately from standard options init because textdomain is not available until later
	 * To be called by method in child class
	 * @param array $opts_text Options passed by method in child class
	 * @return void
	 */
	function init_options_text($options_text = null) {
		if ( !$this->is_options_valid($options_text) )
			return false;
		
		//Groups
		if ( isset($options_text['groups']) ) {
			foreach ( $options_text['groups'] as $id => $title) {
				$g_temp =& $this->options->get_group($id);
				$g_temp->title = $title;
			}
		}
		
		//Options
		if ( isset($options_text['items']) ) {
			foreach ( $options_text['items'] as $opt => $title ) {
				$option_temp =& $this->options->get($opt);
				if ( $option_temp->get_id() ) {
					$option_temp->set_title($title);
				}
			}
		}
	}
	
	/**
	 * Initialize environment (Localization, etc.)
	 * To be overriden by child class
	 * @uses `init` Action hook as trigger
	 */
	function init_env() {}
	
	function init_client_files() {
		foreach ( $this->client_files as $key => $val ) {
			if ( empty($val) && isset($this->{$key}) )
				$this->client_files[$key] =& $this->{$key};
			$g =& $this->client_files[$key];
			if ( is_array($g) && !empty($g) ) {
				$g = $this->util->parse_client_files($g, $key);
			}
			//Remove empty file groups
			if ( empty($g) )
				unset($this->client_files[$key]);
		}

		//Register
		add_action('init', $this->m('register_client_files'));
		
		//Enqueue
		$hook_enqueue = ( ( is_admin() ) ? 'admin' : 'wp' ) . '_enqueue_scripts' ;
		add_action($hook_enqueue, $this->m('enqueue_client_files'));
	}
	
	/**
	 * Register client files
	 * @see self::enqueue_client_files() for actual loading of files based on context
	 * @uses `init` Action hook for execution
	 * @return void
	 */
	function register_client_files() {
		$v = $this->util->get_plugin_version();
		foreach ( $this->client_files as $type => $files ) {
			$func = $this->get_client_files_handler($type, 'register');
			if ( !$func )
				continue;
			foreach ( $files as $f ) {
				//Get file URI
				$f->file = ( !$this->util->is_file($f->file) && is_callable($f->file) ) ? call_user_func($f->file) : $this->util->get_file_url($f->file);
				$params = array($f->id, $f->file, $f->deps, $v);
				//Set additional parameters based on file type (script, style, etc.)
				switch ( $type ) {
					case 'scripts':
						$params[] = $f->in_footer;
						break;
					case 'styles':
						$params[] = $f->media;
						break;
				}
				//Register file
				call_user_func_array($func, $params);
			}
		}
	}
	
	/**
	 * Enqueues files for client output (scripts/styles) based on context
	 * @uses `admin_enqueue_scripts` Action hook depending on context
	 * @uses `wp_enqueue_scripts` Action hook depending on context
	 * @return void
	 */
	function enqueue_client_files() {
		//Enqueue files
		foreach ( $this->client_files as $type => $files ) {
			$func = $this->get_client_files_handler($type, 'enqueue');
			if ( !$func )
				continue;
			foreach ( $files as $f ) {
				$load = true;
				//Global Callback
				if ( is_callable($f->callback) && !call_user_func($f->callback) )
					$load = false;
				//Context
				if ( $load && !empty($f->context) ) {
					//Reset $load before evaluating context
					$load = false;
					//Iterate through contexts
					foreach ( $f->context as $ctx ) {
						//Context + Callback
						if ( is_array($ctx) ) {
							//Stop checking context if callback is invalid
							if ( !is_callable($ctx[1]) || !call_user_func($ctx[1]) )
								continue;
							$ctx = $ctx[0];
						}
						//Stop checking context if valid context found
						if ( $this->util->is_context($ctx) ) {
							$load = true;
							break;
						}
					}
				}
				
				//Load valid file
				if ( $load ) {
					$func($f->id);
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
	 * Prepend uppercased plugin prefix to some text
	 * @param string $text Text to add to prefix
	 * @param string $sep (optional) Text used to separate prefix and text
	 * @param bool $once (optional) Whether to add prefix to text that already contains a prefix or not
	 * @return string Text with prefix prepended
	 */
	function add_prefix_uc($text, $sep = null, $once = true) {
		$args = func_get_args();
		return call_user_func_array($this->util->m($this->util, 'add_prefix_uc'), $args);
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