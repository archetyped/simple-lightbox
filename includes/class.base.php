<?php

/**
 * @package Simple Lightbox
 * @subpackage Base
 * @author Archetyped
 *
 */
class SLB_Base {
	/* Configuration */
	
	/**
	 * Class type
	 * Controls initialization, etc.
	 * > full - Fully-functional class
	 * > sub - Sub-class (attached to an instance)
	 * > object - Simple object class (no hooks, etc.)
	 * @var string
	 */
	protected $mode = 'full';
	
	/**
	 * Indicates that instance is model (main controller)
	 * @var bool
	 */
	protected $model = false;
	
	/* Properties */
			
	/**
	 * Variable name of base object in global scope
	 * @var string
	 */
	protected $base = 'slb';
	
	/**
	 * Prefix for plugin-related data (attributes, DB tables, etc.)
	 * @var string
	 */
	public $prefix = 'slb';
	
	/**
	 * Prefix to be added when creating internal hook (action/filter) tags
	 * Used by Utilities
	 * @var string
	 */
	public $hook_prefix = '';
	
	/**
	 * Global data
	 * Facilitates sharing between decoupled objects
	 * @var array
	 */
	private static $globals = array();
	
	protected $shared = array('options', 'admin');
	
	/**
	 * Capabilities
	 * @var array
	 */
	protected $caps = null;
	
	protected $_init = false;
	
	private static $_init_passed = false;
	
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
	private $client_files = array (
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
	protected $options = null;
	
	/**
	 * Admin
	 * @var SLB_Admin
	 */
	var $admin = null;
	
	/*-** Initialization **-*/
	
	/**
	 * Constructor
	 */
	function __construct() {
		$this->util = new SLB_Utilities($this);
		if ( $this->can('init') ) {
			$hook = 'init';
			if ( did_action($hook) || self::$_init_passed ) {
				$this->_init();
			} else {
				add_action($hook, $this->m('_init'), 1);
			}
		}
	}
	
	/**
	 * Default initialization method
	 * @uses _init_passed
	 * @uses _env()
	 * @uses _options()
	 * @uses _admin()
	 * @uses _hooks()
	 * @uses _client_files()
	 */
	public function _init() {
		self::$_init_passed = true;
		if ( $this->_init || !isset($this) || !$this->can('init') )
			return false;
		$this->_init = true;
		// Environment
		$this->_env();

		if ( $this->can('control') ) {
			// Options
			$this->_options();
			
			// Admin
			if ( is_admin() )
				$this->_admin();
		}

		// Hooks
		$this->_hooks();
		
		// Client files
		$this->_client_files();
	}
	
	/**
	 * Initialize environment (Localization, etc.)
	 */
	private function _env() {
		if ( !$this->can('singleton') ) {
			return false;
		}
		// Localization
		$ldir = 'l10n';
		$lpath = $this->util->get_plugin_file_path($ldir, array(false, false));
		$lpath_abs = $this->util->get_file_path($ldir);
		if ( is_dir($lpath_abs) ) {
			load_plugin_textdomain('simple-lightbox', false, $lpath);
		}
		
		// Context
		add_action( ( is_admin() ) ? 'admin_print_footer_scripts' : 'wp_footer', $this->util->m('set_client_context'), $this->util->priority('client_footer_output') );
	}
	
	/**
	 * Initialize options
	 * To be implemented in child classes
	 */
	protected function _options() {}
	
	/**
	 * Initialize options
	 * To be called by child class
	 */
	protected function _set_options($options_config = null) {
		$class = $this->util->get_class('Options');
		$key = 'options';
		if ( $this->shares($key) ) {
			$opts = $this->gvar($key);
			// Setup options instance
			if ( !($opts instanceof $class) ) {
				$opts = $this->gvar($key, new $class());
			}
		} else {
			$opts = new $class();
		}
		// Load options
		if ( $this->is_options_valid($options_config, false) ) {
			$opts->load($options_config);
		}
		// Set instance property
		$this->options = $opts;
	}
	
	/**
	 * Initialize admin
	 * To be called by child class
	 */
	private function _admin() {
		if ( !is_admin() ) {
			return false;
		}
		$class = $this->util->get_class('Admin');
		$key = 'admin';
		if ( $this->shares($key) ) {
			$adm = $this->gvar($key);
			// Setup options instance
			if ( !($adm instanceof $class) ) {
				$adm = $this->gvar($key, new $class($this));
			}
		} else {
			$adm = new $class($this);
		}
		// Set instance property
		$this->admin = $adm;
	}
	
	/**
	 * Register default hooks
	 */
	protected function _hooks() {
		$base = $this->util->get_plugin_base_file();
		// Activation
		$func_activate = '_activate';
		if ( method_exists($this, $func_activate) )
			register_activation_hook($base, $this->m($func_activate));
		
		// Deactivation
		$func_deactivate = '_deactivate';
		if ( method_exists($this, $func_deactivate) )
			register_deactivation_hook($base, $this->m($func_deactivate));
	}
	
	/**
	 * Initialize client files
	 */
	protected function _client_files($files = null) {
		// Validation
		if ( !is_array($files) || empty($files) ) {
			return false;
		} 
		foreach ( $this->client_files as $key => $val ) {
			if ( isset($files[$key]) && is_array($files[$key]) || !empty($files[$key]) ) {
				$this->client_files[$key] = $this->util->parse_client_files($files[$key], $key);
			}
			// Remove empty file groups
			if ( empty($this->client_files[$key]) ) {
				unset($this->client_files[$key]);
			}
		}
		
		
		// Stop if no files are set for registration
		if ( empty($this->client_files) ) {
			return false;
		}
		
		// Register
		add_action('init', $this->m('register_client_files'));
		
		// Enqueue
		$hk_prfx = ( ( is_admin() ) ? 'admin' : 'wp' );
		$hk_enqueue = $hk_prfx . '_enqueue_scripts' ;
		$hk_enqueue_ft = $hk_prfx . '_footer';
		add_action($hk_enqueue, $this->m('enqueue_client_files'), 10, 0);
		add_action($hk_enqueue_ft, $this->m('enqueue_client_files_footer'), 1);
	}
	
	/**
	 * Register client files
	 * @see enqueue_client_files() for actual loading of files based on context
	 * @uses `init` Action hook for execution
	 * @return void
	 */
	public function register_client_files() {
		$v = $this->util->get_plugin_version();
		foreach ( $this->client_files as $type => $files ) {
			$func = $this->get_client_files_handler($type, 'register');
			if ( !$func )
				continue;
			foreach ( $files as $f ) {
				// Get file URI
				$f->file = ( !$this->util->is_file($f->file) && is_callable($f->file) ) ? call_user_func($f->file) : $this->util->get_file_url($f->file, true);
				$params = array($f->id, $f->file, $f->deps, $v);
				// Set additional parameters based on file type (script, style, etc.)
				switch ( $type ) {
					case 'scripts':
						$params[] = $f->in_footer;
						break;
					case 'styles':
						$params[] = $f->media;
						break;
				}
				// Register file
				call_user_func_array($func, $params);
			}
		}
	}
	
	/**
	 * Enqueues files for client output (scripts/styles) based on context
	 * @uses `admin_enqueue_scripts` Action hook depending on context
	 * @uses `wp_enqueue_scripts` Action hook depending on context
	 * @param bool $footer (optional) Whether to enqueue footer files (Default: No)
	 * @return void
	 */
	function enqueue_client_files($footer = false) {
		// Validate
		if ( !is_bool($footer) ) {
			$footer = false;
		}
		// Enqueue files
		foreach ( $this->client_files as $type => $files ) {
			$func = $this->get_client_files_handler($type, 'enqueue');
			if ( !$func ) {
				continue;
			}
			foreach ( $files as $fkey => $f ) {
				// Skip previously-enqueued files and shadow files
				if ( $f->enqueued || !$f->enqueue ) {
					continue;
				}
				// Enqueue files only for current location (header/footer)
				if ( isset($f->in_footer) ) {
					if ( $f->in_footer != $footer ) {
						continue;
					}
				} elseif ( $footer ) {
					continue;
				}
				$load = true;
				// Global Callback
				if ( is_callable($f->callback) && !call_user_func($f->callback) ) {
					$load = false;
				}
				// Context
				if ( $load && !empty($f->context) ) {
					// Reset $load before evaluating context
					$load = false;
					// Iterate through contexts
					foreach ( $f->context as $ctx ) {
						// Context + Callback
						if ( is_array($ctx) ) {
							// Stop checking context if callback is invalid
							if ( !is_callable($ctx[1]) || !call_user_func($ctx[1]) )
								continue;
							$ctx = $ctx[0];
						}
						// Stop checking context if valid context found
						if ( $this->util->is_context($ctx) ) {
							$load = true;
							break;
						}
					}
				}
				// Load valid file
				if ( $load ) {
					// Mark file as enqueued
					$this->client_files[$type]->{$fkey}->enqueued = true;
					$func($f->id);
				}
			}
		}
	}

	/**
	 * Enqueue client files in the footer
	 */
	public function enqueue_client_files_footer() {
		$this->enqueue_client_files(true);
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
	function m($method) {
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
	
	/*-** Capabilities **-*/
	
	protected function can($cap) {
		if ( is_null($this->caps) ) {
			// Build capabilities based on instance properties
			$this->caps = array(
				'init'			=> ( 'object' != $this->mode ) ? true : false,
				'singleton'		=> ( !!$this->model ) ? true : false,
				'control'		=> ( 'sub' == $this->mode || 'object' == $this->mode ) ? false : true,
			);
		}
		return ( isset($this->caps[$cap]) ) ? $this->caps[$cap] : false;
	}
	
	/*-** Globals **-*/
	
	/**
	 * Get/Set (internal) global variables
	 * @uses $globals to get/set global variables
	 * @param string $name Variable name - If no name is specified, entire globals array is returned
	 * @param mixed $val (optional) Set the value of a variable (Returns variable value if omitted)
	 * @return mixed Variable value
	 */
	private function gvar($name = null, $val = null) {
		$g =& self::$globals;
		if ( !is_array($g) ) {
			$g = array();
		}
		if ( !is_string($name) || empty($name) ) {
			return $g;
		}
		$ret = $val;
		if ( null !== $val ) {
			// Set Value
			$g[$name] = $val;
		} elseif ( isset($g[$name]) ) {
			// Retrieve variable
			$ret = $g[$name];
		}
		return $ret;
	}
	
	private function shares($name) {
		return ( !empty($this->shared) && in_array($name, $this->shared) ) ? true : false;
	}
	
	/*-** Options **-*/
	
	/**
	 * Checks if options are valid
	 * @param array $data Data to be used on options
	 * @return bool TRUE if options are valid, FALSE otherwise
	 */
	function is_options_valid($data, $check_var = true) {
		$class = $this->util->get_class('Options');
		$ret = ( empty($data) || !is_array($data) || !class_exists($class) ) ? false : true;
		if ( $ret && $check_var && !($this->options instanceof $class) )
			$ret = false;
		return $ret;
	}
}

?>