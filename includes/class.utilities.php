<?php

/**
 * Utility methods
 * 
 * @package Simple Lightbox
 * @subpackage Utilities
 * @author Archetyped
 *
 */
class SLB_Utilities {
	
	/* Properties */
	
	/**
	 * Instance parent
	 * @var object
	 */
	private $_parent = null;
	
	/**
	 * Plugin Base
	 * @var string
	 */
	private $_plugin = array(
		'base'		=> null,
		'file'		=> null,
		'name'		=> null,
		'data'		=> null,
		'uri'		=> null,
		'headers'	=> array (
			'Name'			=> 'Plugin Name',
			'PluginURI'		=> 'Plugin URI',
			'SupportURI'	=> 'Support URI',
			'Version'		=> 'Version',
			'Description'	=> 'Description',
			'Author'		=> 'Author',
			'AuthorURI'		=> 'Author URI',
		)
	);
	
	/**
	 * Plugin base path
	 * @var string
	 */
	private $_path_base = null;
	
	/**
	 * Standard hook priorities
	 * @var array
	 */
	private $_priorities = array (
		'high'							=> 1,
		'low'							=> 99,
		'safe'							=> 15,
		'client_footer_output'			=> 25,
	);
	
	/* Constructors */
	
	function __construct($obj) {
		if ( is_object($obj) ) {
			$this->_parent = $obj;
		}
	}
	
	/**
	 * Returns callback array to instance method
	 * @param object $obj Instance object
	 * @param string $method Name of method
	 * @return array Callback array
	 */
	function m($obj, $method = '') {
		if ( is_string($obj) ) {
			$method = $obj;
			$obj = null;
		}
		if ( !is_object($obj) && isset($this) ) {
			$obj = $this;
		}
		$cb = array($obj, $method);
		return $cb;
	}
	
	/* Helper Functions */
	
	/*-** Prefix **-*/
	
	/**
	 * Get valid separator
	 * @param string $sep (optional) Separator supplied
	 * @return string Separator
	 */
	function get_sep($sep = false) {
		if ( is_null($sep) )
			$sep = '';
		return ( is_string($sep) ) ? $sep : '_';
	}
	
	/**
	 * Retrieve class prefix (with separator if set)
	 * @param bool|string $sep Separator to append to class prefix (Default: no separator)
	 * @return string Class prefix
	 */
	function get_prefix($sep = null) {
		$sep = $this->get_sep($sep);
		$prefix = ( !empty($this->_parent->prefix) ) ? $this->_parent->prefix . $sep : '';
		return $prefix;
	}
	
	/**
	 * Check if a string is prefixed
	 * @param string|array $text Text to check for prefix
	 * @param string $sep (optional) Separator used
	 */
	function has_prefix($text, $sep = null) {
		if ( empty($text) )
			return false;
		if ( !is_array($text) )
			$text = array($text);
		$text = array_values($text);
		$text = $text[0];
		return ( !empty($text) && stripos($text, $this->get_prefix($sep)) === 0 );
	}
	
	/**
	 * Prepend plugin prefix to some text
	 * @param string|array $text Text to add to prefix
	 * @param string $sep (optional) Text used to separate prefix and text
	 * @param bool $once (optional) Whether to add prefix to text that already contains a prefix or not
	 * @return string Text with prefix prepended
	 */
	function add_prefix($text, $sep = '_', $once = true) {
		// Normalize data type (array)
		if ( empty($text) )
			$text = array('');
		if ( !is_array($text) )
			$text = array($text);
		// Add prefix (if necessary)
		if ( !$once || ( $once && !$this->has_prefix($text, $sep) ) )
			array_unshift($text, $this->get_prefix());
		return implode($sep, $text);
	}
	
	/**
	 * Prepend uppercased plugin prefix to some text
	 * @param string|array $text Text to add to prefix
	 * @param string $sep (optional) Text used to separate prefix and text
	 * @param bool $once (optional) Whether to add prefix to text that already contains a prefix or not
	 * @return string Text with prefix prepended
	 */
	function add_prefix_uc($text, $sep = '_', $once = true) {
		$args = func_get_args();
		$var = call_user_func_array($this->m($this, 'add_prefix'), $args);
		$pre = $this->get_prefix();
		return str_replace($pre . $sep, strtoupper($pre) . $sep, $var);
	}
	
	/**
	 * Add prefix to variable reference
	 * Updates actual variable rather than return value
	 * @uses add_prefix() to add prefix to variable
	 * @param string $var Variable to add prefix to
	 * @param string $sep (optional) Separator text
	 * @param bool $once (optional) Add prefix only once
	 * @return void
	 */
	function add_prefix_ref(&$var, $sep = null, $once = true) {
		$args = func_get_args();
		$var = call_user_func_array($this->m($this, 'add_prefix'), $args);
	}
	
	/**
	 * Remove prefix from specified string
	 * @param string $text String to remove prefix from
	 * @param string $sep (optional) Separator used with prefix
	 */
	function remove_prefix($text, $sep = '_') {
		if ( $this->has_prefix($text,$sep) )
			$text = substr($text, strlen($this->get_prefix($sep)));
		return $text;
	}
	
	/**
	 * Returns Database prefix for plugin-related DB Tables
	 * @return string Database prefix
	 */
	function get_db_prefix() {
		global $wpdb;
		return $wpdb->prefix . $this->get_prefix('_');
	}
	
	/*-** Priority **-*/
	
	/**
	 * Retrieve standard priority
	 * @var string $id Priority ID to retrieve
	 * @return int Priority
	 */
	public function priority($id = null) {
		$pri = 10;
		if ( !is_null($id) && array_key_exists($id, $this->_priorities) ) {
			$pri = $this->_priorities[$id];
		}
		return $pri;
	}
	
	/* Wrapped Values */
	
	/**
	 * Create wrapper object
	 * Properties
	 *   > start
	 *   > end
	 * @param string|array $start Start text (Can also be array defining start & end values)
	 * @param string $end (optional) End text
	 * If $end not defined, then $start is used
	 * @return obj Wrapper
	 */
	function get_wrapper($start = null, $end = null) {
		// Validate existing wrapper
		if ( is_object($start) && isset($start->start) && isset($start->end) )
			return $start;
		
		// Initialize wrapper
		$w = array (
			'start'		=> '[',
			'end'		=> ']',
		);
		
		if ( !empty($start) ) {
			if ( is_string($start) ) {
				$w['start'] = $start;
			} elseif ( is_array($start) ) {
				$start = array_values($start);
				if ( is_string($start) ) {
					$w['start'] = $start[0];
				}
				if ( isset($start[1]) && is_string($start[1]) ) {
					$w['end'] = $start[1];
				}
			}
		}
		if ( is_string($end) ) {
			$w['end'] = $end;
		}
		
		return (object) $w;
	}
	
	/**
	 * Check if text is wrapped by specified character(s)
	 * @uses this->get_wrapper() to Validate wrapper text
	 * @param string $text Text to check
	 * @param string|array $start (optional) Start text (Array defines both start/end text)
	 * @param string $end (optional) End text
	 */
	function has_wrapper($text, $start = null, $end = null) {
		if ( !is_string($text) || empty($text) )
			return false;
		// Validate wrapper
		$w = $this->get_wrapper($start, $end);
		
		// Check for wrapper
		return ( substr($text, 0, strlen($w->start)) == $w->start && substr($text, -1, strlen($w->end)) == $w->end ) ? true : false;
	}
	
	/**
	 * Remove wrapper from specified text
	 * @uses this->has_wrapper() to check if text is wrapped
	 * @uses this->get_wrapper() to retrieve wrapper object
	 * @param string $text Text to check
	 * @param string|array $start (optional) Start text (Array defines both start/end text)
	 * @param string $end (optional) End text
	 * @return string Unwrapped text
	 */
	function remove_wrapper($text, $start = null, $end = null) {
		if ( $this->has_wrapper($text, $start, $end) ) {
			$w = $this->get_wrapper($start, $end);
			$text = substr($text, strlen($w->start), strlen($text) - strlen($w->start) - strlen($w->end) );
		}
		
		return $text;
	}
	
	/**
	 * Add wrapper to specified text
	 * @uses Utilities::get_wrapper() to retrieve wrapper object
	 * @param string $text Text to wrap
	 * @param string|array $start (optional) Start text (Array defines both start/end text)
	 * @param string $end (optional) End text
	 * @param bool $once (optional) Whether to wrap text only once (Default: TRUE)
	 * @return string Wrapped text
	 */
	function add_wrapper($text, $start = null, $end = null, $once = true) {
		$w = $this->get_wrapper($start, $end);
		if ( !$once || !$this->has_wrapper($text, $w) )
			$text = $w->start . $text . $w->end;
		return $text;
	}
	
	/*-** Client **-*/
	
	/**
	 * Parses client files array
	 * > Adds ID property (prefixed file key)
	 * > Parses and validates internal dependencies
	 * > Converts properties array to object
	 * Properties
	 * > file (string|array): File name (string) or callback (array) to retrieve file name
	 * > deps (array) [optional]: Dependencies
	 *   > Values wrapped in square brackets (`[` & `]`) are internal files
	 * > callback (string|array) [optional]: Global callback to determine whether file should be loaded
	 *   > Values wrapped in square brackets (`[` & `]`) are internal methods (of parent object) 
	 * > context (array) [optional]: Context(s) in which to load the file
	 *   Acceptable values
	 *   > string: Context name
	 *   > array: Context name + callback (both must return TRUE to load file) 
	 *     > Callback follows same pattern as `callback` member
	 * @param array $files Files array
	 * @return object Client files
	 */
	function parse_client_files($files, $type = 'scripts') {
		if ( is_array($files) && !empty($files) ) {
			// Defaults
			$defaults = array(
				'file'		=> null,
				'deps' 		=> array(),
				'callback'	=> null,
				'context'	=> array(),
				'enqueue'	=> true,
				'enqueued'  => false
			);
			switch ( $type ) {
				case 'styles':
					$defaults['media'] = 'all';
					break;
				default:
					$defaults['in_footer'] = false;
			}
			// Iterate through files
			/**
			 * $h (string) handle
			 * $p (array) properties
			 */
			foreach ( $files as $h => $p ) {
				unset($file, $cb, $ctxs, $ctx);
				// Set ID
				$p['id'] = $this->add_prefix($h);
				// Type Validation
				/**
				 * $m (string) property name
				 * $d (mixed) default value
				 */
				foreach ( $defaults as $m => $d ) {
					// Check if value requires validation
					if ( !is_array($d) || !isset($p[$m]) || is_array($p[$m]) )
						continue;
					// Wrap value in array or destroy it
					if ( is_scalar($p[$m]) )
						$p[$m] = array($p[$m]);
					else
						unset($p[$m]);
				}
				
				// Normalize file properties
				$p = array_merge($defaults, $p);
				
				/* File name */
				
				// Validate file
				$file =& $p['file'];
				
				// Determine if filename or callback
				if ( !$this->is_file($file) )
					$file = ( is_callable($file) ) ? $file : null;
				// Remove invalid file and move on to next
				if ( empty($file) ) {
					unset($files[$h]);
					continue;
				}
				
				/* Dependencies */
				
				// Format internal dependencies
				foreach ( $p['deps'] as $idx => $dep ) {
					if ( $this->has_wrapper($dep) ) {
						$dep = $this->remove_wrapper($dep);
						$p['deps'][$idx] = $this->add_prefix($dep);
					}
				}
				
				/* Context */
				
				// Validate callback
				$cb =& $p['callback'];
				if ( !is_null($cb) && !is_callable($cb) ) {
					// Remove files with invalid callbacks (will never be loaded)
					unset($files[$h]);
					continue;
				}
				
				// Validate contexts
				$ctxs =& $p['context'];
				$ctxs = array_unique($ctxs);
				$has_contexts = ( count($ctxs) > 0 ) ? true : false;
				foreach ( $ctxs as $idx => $ctx ) {
					// Convert to array
					$ctx = array_values( array_slice( (array) $ctx, 0, 2 ) );
					switch ( count($ctx) ) {
						case 1 :
							// Simple context
							$ctx = $ctx[0];
							break;
						case 2 :
							// Context + Callback
							if ( is_callable($ctx[1]) ) {
								break;
							}
							// Continue to default case if callback is invalid
						default :
							// Context is invalid
							$ctx = false;
							break;
					}
					
					// Remove invalid contexts
					if ( empty($ctx) ) {
						unset($ctxs[$idx]);
					} else {
						$ctxs[$idx] = $ctx;
					}
				}
				// Remove file if all specified contexts invalid (no context is OK)
				if ( $has_contexts && empty($ctxs) ) {
					unset($files[$h]);
					continue;
				}
				$ctxs = array_values($ctxs);
				
				/* Finalize Properties */
				
				// Convert properties to object
				$files[$h] = (object) $p;
			}
		}
		// Cast to object before returning
		$files = (object) $files;
		return $files;
	}

	/**
	 * Build JS client object
	 * @param string (optional) $path Additional object path
	 * @return string Client object
	 */
	function get_client_object($path = null) {
		$obj = strtoupper($this->get_prefix());
		if ( !empty($path) && is_string($path) ) {
			if ( 0 !== strpos($path, '[') )
				$obj .= '.';
			$obj .= $path;
		}
		return $obj;
	}
	
	/**
	 * Build jQuery JS expression to add data to specified client object
	 * @param string|obj $obj Name of client object (Set to root object if not a valid name)
	 * @param mixed $data Data to add to client object
	 * @param bool (optional) $out Whether or not to output code (Default: false)
	 * @return string JS expression to extend client object
	 */
	function extend_client_object($obj, $data = null, $out = false) {
		// Validate parameters
		$args = func_get_args();
		switch ( count($args) ) {
			case 2:
				if ( !is_scalar($args[0]) ) {
					if ( is_bool($args[1]) )
						$out = $args[1];
				} else {
					break;
				}
			case 1:
				$data = $args[0];
				$obj = null;
				break;
		}
		// Default client object
		if ( !is_string($obj) || empty($obj) )
			$obj = null;
		// Default data
		if ( is_array($data) ) {
			$data = (object)$data;
		}
		// Build expression
		if ( empty($data) || ( empty($obj) && is_scalar($data) ) ) {
			$ret = '';
		} else {
			$c_obj = $this->get_client_object($obj);
			$ret = $this->validate_client_object($obj, sprintf('{$.extend(%1$s, %2$s);}', $c_obj, json_encode($data)) );
			if ( $out )
				echo $this->build_script_element($ret, 'context', true, true);
		}
		return $ret;
	}

	/**
	 * Validate client object $obj before running command $cmd
	 * 
	 * @param string $obj Full object name
	 * @param string $cmd (optional) Command to wrap in validation
	 * @return string Command wrapped in validation block
	 * If no command is specified the validation conditions are returned
	 */
	public function validate_client_object($obj, $cmd = null) {
		// Get base object
		$base = $this->get_client_object();
		
		// Build condition
		$sep = '.';
		$obj = trim($obj, $sep);
		//  Strip base object
		if ( 0 === strpos($obj, $base . $sep) ) {
			$obj = substr($obj, strlen($base . $sep));
		}
		$fmt = '!!window.%1$s';
		if ( !empty($obj) ) {
			$fmt .= ' && %1$s.has_child(\'%2$s\')';
		}
		$condition = sprintf($fmt, $base, $obj);
		
		// Wrap command in validation
		if ( !empty($cmd) && is_string($cmd) ) {
			$condition = sprintf('if ( %1$s ) { %2$s }', $condition, $cmd);
		}
		return $condition;
	}
	
	/**
	 * Build client method call
	 * @uses get_client_object() to generate the body of the method call
	 * @param string $method Method name
	 * @param array|string $params (optional) Parameters to pass to method
	 * @param bool $encode (optional) JSON-encode parameters? (Default: TRUE)
	 * @param bool $validate (optional) Validate method before calling it?
	 * @return string Method call
	 */
	function call_client_method($method, $params = null, $encode = true, $validate = true) {
		$ret = '';
		if ( !is_string($method) || empty($method) ) {
			return $ret;
		}
		$encode = !!$encode;
		$validate = !!$validate;
		
		// Build parameters
		if ( !is_null($params) ) {
			if ( $encode ) {
				$params = json_encode($params);	
			} elseif ( is_array($params) ) {
				$params = implode(',', $params); 
			}
		}
		if ( !is_string($params) ) {
			$params = '';	
		}
		$ret = sprintf('%s(%s);', $this->get_client_object($method), $params);
		if ( $validate ) {
			$ret = $this->validate_client_object($method, $ret);
		}
		return $ret;
	}
	
	/*-** WP **-*/
	
	/**
	 * Checks if $post is a valid Post object
	 * If $post is not valid, assigns global post object to $post (if available)
	 * @return bool TRUE if $post is valid object by end of function processing
	 * @param object $post Post object to evaluate
	 */
	function check_post(&$post) {
		if (empty($post)) {
			if (isset($GLOBALS['post'])) {
				$post = $GLOBALS['post'];
				$GLOBALS['post'] =& $post;
			}
			else
				return false;
		}
		if (is_array($post))
			$post = (object) $post;
		elseif (is_numeric($post))
			$post = get_post($post);
		if (!is_object($post))
			return false;
		return true;
	}
	
	
	/**
	 * Retrieve parent object
	 * @return obj|bool Parent object (FALSE if no valid parent set)
	 */
	function get_parent() {
		if ( is_object($this->_parent) ) {
			return $this->_parent;
		} else {
			return false;
		} 
	}
	
	/**
	 * Retrieve parent property value
	 * @uses self::get_parent()
	 * @param string $prop Property name
	 * @param mixed $default Default value
	 * @return mixed Parent property value
	 */
	function get_parent_property($prop, $default = '') {
		$p = $this->get_parent();
		return ( !!$p && property_exists($p, $prop) ) ? $p->{$prop} : $default; 
	}
	
	/* Hooks */
	
	/**
	 * Retrieve formatted name for internal hooks
	 * Prefixes with parent prefix and hook prefix
	 * @uses self::get_parent_property() to retrieve hook prefix
	 * @uses self::add_prefix()
	 * @param string $tag Base tag
	 * @param bool|string $hook_prefix (optional) Secondary prefix to use for hook (Default: Use predefined hook name, FALSE: no secondary hook)
	 * @return string Formatted hook
	 */
	function get_hook($tag, $hook_prefix = true) {
		// Hook prefix
		$hook = '';
		if ( is_bool($hook_prefix) && $hook_prefix ) {
			$hook = $this->get_parent_property('hook_prefix', '');
		} elseif ( is_string($hook_prefix) ) {
			$hook = $hook_prefix;
		}
		if ( !empty($hook) )
			$hook .= '_';
		// Prefix
		return $this->add_prefix($hook . $tag);
	}
	
	/**
	 * Run internal action
	 * Namespaces $tag
	 * @uses self::get_hook()
	 * @see do_action()
	 * @param string|array $tag Action hook. If array, get hook prefix
	 */
	function do_action($tag, $arg = '') {
		// Handle hook prefix
		$hook_prefix = true;
		if ( is_array($tag) ) {
			$hook_prefix = $tag[1];
			$tag = $tag[0];
		}
		$args = func_get_args();
		$args[0] = $this->get_hook($tag, $hook_prefix);
		return call_user_func_array('do_action', $args);
	}
	
	/**
	 * Run internal action passing arguments in array
	 * @uses do_action_ref_array()
	 * @param bool|string $hook_prefix (optional) Secondary prefix to use for hook (Default: Use predefined hook name, FALSE: no secondary hook)
	 */
	function do_action_ref_array($tag, $args, $hook_prefix = true) {
		return do_action_ref_array($this->get_hook($tag, $hook_prefix), $args);
	}
	
	/**
	 * Run internal filter
	 * Namespaces $tag
	 * @uses self::get_hook()
	 * @see apply_filters()
	 * @param string|array $tag Action hook. If array, get hook prefix
	 */
	function apply_filters($tag, $value) {
		// Handle hook prefix
		$hook_prefix = true;
		if ( is_array($tag) ) {
			$hook_prefix = $tag[1];
			$tag = $tag[0];
		}
		$args = func_get_args();
		$args[0] = $this->get_hook($tag, $hook_prefix);
		return call_user_func_array('apply_filters', $args);
	}
	
	/**
	 * Run internal filter passing arguments in array
	 * @uses apply_filters_ref_array()
	 * @param bool|string $hook_prefix (optional) Secondary prefix to use for hook (Default: Use predefined hook name, FALSE: no secondary hook)
	 */
	function apply_filters_ref_array($tag, $args, $hook_prefix = true) {
		return apply_filters_ref_array($this->get_hook($tag, $hook_prefix), $args);	
	}
	
	/**
	 * Add internal action
	 * Namespaces $tag
	 * @uses self::get_hook()
	 * @see add_action()
	 * @param bool|string $hook_prefix (optional) Secondary prefix to use for hook (Default: Use predefined hook name, FALSE: no secondary hook)
	 */
	function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1, $hook_prefix = true) {
		return add_action($this->get_hook($tag, $hook_prefix), $function_to_add, $priority, $accepted_args);
	}
	
	/**
	 * Add internal filter
	 * Namespaces $tag
	 * @uses self::get_hook()
	 * @see add_filter()
	 * @param bool|string $hook_prefix (optional) Secondary prefix to use for hook (Default: Use predefined hook name, FALSE: no secondary hook)
	 */
	function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1, $hook_prefix = true) {
		return add_filter($this->get_hook($tag, $hook_prefix), $function_to_add, $priority, $accepted_args);
	}
	
	/**
	 * Remove internal action
	 * Namespaces $tag
	 * @uses self::get_hook()
	 * @uses remove_action()
	 * @param bool|string $hook_prefix (optional) Secondary prefix to use for hook (Default: Use predefined hook name, FALSE: no secondary hook)
	 */
	function remove_action($tag, $function_to_remove, $priority = 10, $accepted_args = 1, $hook_prefix = true) {
		return remove_action($this->get_hook($tag, $hook_prefix), $function_to_remove, $priority, $accepted_args);	
	}
	
	/**
	 * Remove internal filter
	 * Namespaces $tag
	 * @uses self::get_hook()
	 * @uses remove_filter()
	 * @param bool|string $hook_prefix (optional) Secondary prefix to use for hook (Default: Use predefined hook name, FALSE: no secondary hook)
	 */
	function remove_filter($tag, $function_to_remove, $priority = 10, $accepted_args = 1, $hook_prefix = true) {
		return remove_filter($this->get_hook($tag, $hook_prefix), $function_to_remove, $priority, $accepted_args);
	}
	
	/* Shortcode */
	
	/**
	 * Process specific shortcode(s) in content
	 * Default: Process all existing shortcodes
	 * @uses $shortcode_tags - array tag => callback
	 * @uses do_shortcode()
	 * 
	 * @param string $content Content to process for shortcodes
	 * @param string|array $shortcode Single tag or array of tags to process
	 *  > Associative array sets temporary callbacks for shortcodes (`tag => callback`)
	 */
	public function do_shortcode($content, $shortcode = null) {
		global $shortcode_tags;
		
		// Process custom shortcodes
		if ( !is_null($shortcode) ) {
			// Cast to array
			$shortcode = (array) $shortcode;
			// Backup and reset shortcode handlers
			$tags_temp = $shortcode_tags;
			$shortcode_tags = array();
			// Register specified tags
			foreach ( $shortcode as $key => $val ) {
				if ( is_string($key) && is_callable($val) ) {
					// Tag w/custom callback
					$shortcode_tags[$key] = $val;
				} elseif ( is_int($key) && is_string($val) && isset($tags_temp[$val]) ) {
					// Tag with default callback
					$shortcode_tags[$val] = $tags_temp[$val];
				}
			}
		}
		
		// Process shortcodes in content
		$content = do_shortcode($content);
		
		// Restore default shortcode handlers
		if ( isset($tags_temp) ) {
			$shortcode_tags = $tags_temp;
			unset($tags_temp);
		}
		
		return $content;
	}
	
	/**
	 * Build shortcode tag
	 * @param string $tag Shortcode base
	 * @param array (optional) $attr Shortcode attributes
	 * @param string (optional) $content Shortcode content
	 * @return string Shortcode tag
	 */
	public function make_shortcode($tag, $attr = null, $content = null) {
		return $this->build_element(array (
			'tag'			=> $tag,
			'attributes'	=> $attr,
			'content'		=> $content,
		));
	}

	/* Meta */
	
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
	 * Creates a meta key for storing post meta data
	 * Prefixes standard prefixed text with underscore to hide meta data on post edit forms
	 * @param string $text Text to use as base of meta key
	 * @return string Formatted meta key
	 */
	function make_meta_key($text = '') {
		return '_' . $this->add_prefix($text);
	}
	
	/* Class */
	
	/**
	 * Retrieve name of internal class
	 * @param string $class Base name of class
	 * @return string Full name of internal class
	 */
	function get_class($class) {
		return $this->add_prefix_uc($class);
	} 
	
	/* Context */
	
	/**
	 * Retrieve context for current request
	 * @return array Context
	 */
	function get_context() {
		// Context
		static $ctx = null;
		if ( !is_array($ctx) ) {
			// Standard
			$ctx = array($this->build_context());
			// Action
			$action = $this->get_action();
			if ( !empty($action) ) {
				$ctx[] = $this->build_context('action', $action);
			}
			// Post type
			$post_type = $this->get_post_type();
			if ( !empty($action) ) {
				$ctx[] = $this->build_context('post-type', $post_type);
			}
			// Admin page
			if ( is_admin() ) {
				global $pagenow;
				$pg = $this->strip_file_extension($pagenow);
				$ctx[] = $this->build_context('page', $pg);
				// Query String
				if ( isset($_SERVER['QUERY_STRING']) ) {
					parse_str($_SERVER['QUERY_STRING'], $qv);
					if ( isset($qv['page']) ) {
						$ctx[] = $this->build_context('page', $qv['page']);
						if ( stripos($qv['page'], $this->get_prefix()) === 0 ) {
							$ctx[] = $this->build_context('page', $this->get_prefix());
						}
					}
				}
				// Action
				if ( !empty($action) ) {
					$ctx[] = $this->build_context('page', $pg, 'action', $action);
					$ctx[] = $this->build_context('post-type', $post_type, 'action', $action);
				}
			}
			// User
			$u = wp_get_current_user();
			$ctx[] = $this->build_context('user', ( $u->ID ) ? 'registered' : 'guest', false);
		}
		
		return $ctx;
	}
	
	/**
	 * Builds context from multiple components
	 * Usage:
	 * > $prefix can be omitted and context strings can be added as needed
	 * > Multiple context strings may be passed to be joined together
	 * 
	 * @param string (optional) $context Variable number of components to add to context
	 * @param bool (optional) $prefix Whether or not to prefix context with request type (public or admin) [Default: TRUE] 
	 * @return string Context
	 */
	function build_context($context = null, $prefix = true) {
		$args = func_get_args();
		// Get prefix option
		if ( !empty($args) ) {
			$prefix = ( is_bool($args[count($args) - 1]) ) ? array_pop($args) : true;
		}
		
		// Validate 
		$context = array_filter($args, 'is_string');
		$sep = '_';

		// Context Prefix
		if ( $prefix )
			array_unshift($context, ( is_admin() ) ? 'admin' : 'public' );
		return implode($sep, $context);
	}
	
	/**
	 * Check if context exists in current request
	 * @param string $context Context to check for
	 * @return bool TRUE if context exists FALSE otherwise
	 */
	function is_context($context) {
		$ret = false;
		if ( is_scalar($context) )
			$context = array($context);
		if ( is_array($context) && !empty($context) ) {
			$ictx = array_intersect($this->get_context(), $context);
			if ( !empty($ictx) )
				$ret = true;
		}
		return $ret;
	}

	/**
	 * Output current context to client-side
	 * @uses `wp_head` action hook
	 * @uses `admin_head` action hook
	 * @return void
	 */
	function set_client_context() {
		$ctx = new stdClass();
		$ctx->context = $this->get_context();
		$this->extend_client_object($ctx, true);
	}
	
	/* Path */
	
	/**
	 * Joins and normalizes the slashes in the paths passed to method
	 * All forward/back slashes are converted to forward slashes
	 * Multiple path segments can be passed as additional argments
	 * @param string $path Path to normalize
	 * @param bool|array $trailing_slash (optional) Whether or not normalized path should have a trailing slash or not (Default: FALSE)
	 *  If array is passed, first index is trailing, second is leading slash
	 * If multiple path segments are passed, $trailing_slash will be the LAST parameter (default value used if omitted)
	 */
	function normalize_path($path, $trailing_slash = false) {
		$sl_f = '/';
		$sl_b = '\\';
		$parts = func_get_args();
		// Slash defaults (trailing, leading);
		$slashes = array(false, true);
		if ( func_num_args() > 1 ) {
			// Get last argument
			$arg_last = $parts[count($parts) - 1];
			if ( is_bool($arg_last) ) {
				$arg_last = array($arg_last);
			}
			
			if ( is_array($arg_last) && count($arg_last) > 0 && is_bool($arg_last[0]) ) {
				// Remove slash paramter from args array
				array_pop($parts);
				// Normalize slashes options
				if ( isset($arg_last[0]) )
					$slashes[0] = $arg_last[0];
				if ( isset($arg_last[1]) )
					$slashes[1] = $arg_last[1];
			}
		}
		// Extract to slash options local variables
		list($trailing_slash, $leading_slash) = $slashes;
		
		// Clean path segments
		foreach ( $parts as $key => $part ) {
			// Trim slashes/spaces
			$parts[$key] = trim($part, " " . $sl_f . $sl_b);
			
			// Verify path segment still contains value
			if ( empty($parts[$key]) ) {
				unset($parts[$key]);
				continue;
			}
		}
		
		// Join path parts together
		$parts = implode($sl_b, $parts);
		$parts = str_replace($sl_b, $sl_f, $parts);
		// Add trailing slash (if necessary)
		if ( $trailing_slash )
			$parts .= $sl_f;
		// Add leading slash (if necessary)
		$regex = '#^.+:[\\/]#';
		if ( $leading_slash && !preg_match($regex, $parts) ) {
			$parts = $sl_f . $parts;
		}
		return $parts;
	}
	
	/**
	 * Returns URL of file (assumes that it is in plugin directory)
	 * @param string $file name of file get URL
	 * @param string|bool $relative Path that URI should be relative to (Default: full path)
	 * @return string File URL
	 */
	function get_file_url($file, $relative = null) {
		if ( is_string($file) && '' != trim($file) ) {
			$file = str_replace(' ', '%20', $this->normalize_path($this->get_url_base(false, $relative), $file));
		}
		return $file;
	}
	
	/**
	 * Returns path to plugin file
	 * @param string $file file name
	 * @return string File path
	 */
	function get_file_path($file, $relative = null) {
		// Build path
		if ( is_string($file) && '' != trim($file) ) {
			$file = $this->normalize_path($this->get_path_base($relative), $file);
		}
		return $file;
	}
	
	function get_plugin_file_path($file, $trailing_slash = false) {
		if ( is_string($file) && '' != trim($file) )
			$file = $this->normalize_path($this->get_plugin_base(), $file, $trailing_slash);
		return $file;
	}
	
	/**
	 * Checks if value is valid file name
	 * @param string $filename File name to check
	 * @return bool TRUE if valid file name, FALSE otherwise
	 */
	function is_file($filename) {
		$ext = $this->get_file_extension($filename);
		return ( empty($ext) ) ? false : true;
	}
	
	/**
	 * Check if string is valid URI
	 * @param string $uri String to check
	 * @return bool TRUE if string is valid URI
	 */
	function is_uri($uri) {
		return ( preg_match('|^(https?:)?//|', $uri) ) ? true : false;
	}
	
	/**
	 * Retrieves file extension
	 * @param string $file file name/path
	 * @param bool (optional) $lowercase Whether lowercase extension should be returned (Default: TRUE)
	 * @return string File's extension
	 */
	function get_file_extension($file, $lowercase = true) {
		$ret = '';
		$sep = '.';
		// Validate
		if ( !is_string($file) )
			return $ret;
		// Strip query string (if necessary)
		if ( ( $qpos = strpos($file, '?') ) && $qpos !== false ) {
			$file = substr($file, 0, $qpos);
		}
		if ( ( $rpos = strrpos($file, $sep) ) > 0 ) 
			$ret = substr($file, $rpos + 1);
		if ( !!$lowercase )
			$ret = strtolower($ret);
		return $ret;
	}
	
	/**
	 * Checks if file has specified extension
	 * @uses get_file_extension()
	 * @param string $file File name/path
	 * @param string|array $extension File ending(s) to check $file for
	 * @param bool (optional) Whether check should be case senstive or not (Default: FALSE)
	 * @return bool TRUE if file has extension
	 */
	function has_file_extension($file, $extension, $case_sensitive = false) {
		if ( !is_array($extension) )
			$extension = array(strval($extension));
		if ( !$case_sensitive ) {
			// Normalize extensions
			$extension = array_map('strtolower', $extension);
		} 
		return ( in_array($this->get_file_extension($file, !$case_sensitive), $extension) ) ? true : false;
	}
	
	/**
	 * Removes file extension from file name
	 * The extension is the text following the last period ('.') in the file name
	 * @uses get_file_extension()
	 * @param string $file File name
	 * @return string File name without extension
	 */
	function strip_file_extension($file) {
		$ext = $this->get_file_extension($file);
		if ( !empty($ext) ) {
			$file = substr($file, 0, (strlen($ext) + 1) * -1);
		}
		return $file;
	}
	
	/**
	 * Retrieve base URL for plugin-specific files
	 * @uses get_plugin_base()
	 * @uses normalize_path()
	 * @return string Base URL
	 */
	function get_url_base($trailing_slash = false, $relative = null) {
		$ret = $this->_plugin['uri'];
		if ( empty($ret) ) {
			$ret = $this->normalize_path(plugins_url(), $this->get_plugin_base());
		}
		// Trailing slash
		if ( !!$trailing_slash ) {
			$ret .= '/';
		}
		// Relative
		if ( !empty($relative) ) {
			// Default
			if ( is_bool($relative) ) {
				$relative = site_url();
			}
			// Custom
			if ( is_string($relative) ) {
				$ret = $this->get_relative_path($ret, $relative);
			}
		}
		
		return $ret;
	}
	
	/**
	 * Retrieve plugin's base path
	 * @uses WP_PLUGIN_DIR
	 * @uses get_plugin_base()
	 * @uses normalize_path()
	 * @return string Base path
	 */
	function get_path_base($relative = null) {
		$ret = $this->_path_base;
		if ( empty($ret) ) {
			// Get base directory of parent object
			if ( $this->get_parent() ) {
				$r = new ReflectionClass(get_class($this->get_parent()));
				$base = $r->getFileName();
				unset($r);
			} else {
				$base = __FILE__;
			}
			// Extract base path
			$base = $this->normalize_path($base);
			if ( 0 === strpos($base, $this->normalize_path(WP_PLUGIN_DIR)) ) {
				$end = strpos($base, '/', strlen(WP_PLUGIN_DIR) + 1);
				$base = substr($base, 0, $end);
			}
			$ret = $this->_path_base = $base;
		}
		// Make relative path
		if ( !empty($relative) ) {
			// Default
			if ( is_bool($relative) ) {
				$relative = ABSPATH;
			}
			// Custom
			if ( is_string($relative) ) {
				$ret = $this->get_relative_path($ret, $relative);
			}
		}
		return $ret;
	}
	
	/**
	 * Retrieve relative path for absolute paths
	 * @param string $path Path to modify
	 * @param string $relative (optional) Base path to make $path relative to (Default: Site's base path)
	 * @return string Relative path
	 */
	function get_relative_path($path, $relative = true) {
		// Default base path
		if ( !is_string($relative) ) {
			$relative = ABSPATH;
		}
		if ( !empty($relative) && !empty($path) ) {
			$relative = $this->normalize_path($relative);
			$path = $this->normalize_path($path);
			// Strip base path
			if ( strpos($path, $relative) === 0 ) {
				$path = substr($path, strlen($relative));
			}
		}
		return $path;
	}
	
	/**
	 * Retrieve plugin's base directory
	 * @uses WP_PLUGIN_DIR
	 * @uses Utilities::get_path_base() to retrieve plugin base path
	 * @uses Utilities::_plugin_base to save plugin base
	 * @return string Base directory
	 */
	function get_plugin_base() {
		$ret = $this->_plugin['base'];
		if ( empty($ret) ) {
			$ret = $this->_plugin['base'] = basename($this->get_path_base());
		}
		return $ret;
	}
	
	/**
	 * Retrieve plugin's base file path
	 * @uses get_path_base()
	 * @uses get_file_path()
	 * @return string Base file path
	 */
	function get_plugin_base_file() {
		$ret = $this->_plugin['file'];
		if ( empty($ret) ) {
			$dir = @opendir($this->get_path_base());
			if ( $dir ) {
				while ( ($ftemp = readdir($dir)) !== false ) {
					// Only process PHP files
					$ftemp = $this->get_file_path($ftemp);
					if ( !$this->has_file_extension($ftemp, 'php') || !is_readable($ftemp) )
						continue;
					// Check for data
					$data = get_file_data($ftemp, $this->_plugin['headers']);
					if ( !empty($data['Name']) ) {
						// Set base file
						$file = $ftemp;
						// Save plugin data
						$this->set_plugin_info($data);
						break;
					}
				}
			}
			@closedir($dir);
		}
		// Return
		return $file;
	}
	
	/**
	 * Retrieve plugin's internal name
	 * Internal name is used by WP core
	 * @uses get_plugin_base_file()
	 * @uses plugin_basename()
	 * @return string Internal plugin name
	 */
	function get_plugin_base_name() {
		$ret = $this->_plugin['name'];
		if ( empty($ret) ) {
			$ret = $this->_plugin['name'] = plugin_basename( $this->get_plugin_base_file() );
		}
		return $ret;
	}
	
	private function set_plugin_info($data) {
		if ( is_array($data) ) {
			$this->_plugin['data'] = $data;
		}
	}
	
	/**
	 * Retrieve plugin info
	 * Parses info comment in main plugin file
	 * @uses get_plugin_base_file() to retrieve plugin info
	 * @return array|string Plugin info (specific value if field set)
	 */
	function get_plugin_info($field = null) {
		$ret = $this->_plugin['data'];
		// Get plugin data
		if ( empty($ret) ) {
			$this->get_plugin_base_file(); 
			$ret = $this->_plugin['data'];
		}
		// Return specified field
		if ( !empty($field) ) {
			$ret = ( is_array($ret) && isset($ret[$field]) ) ? $ret[$field] : '';
		}
		return $ret;
	}
	
	/**
	 * Retrieve plugin version
	 * @uses get_plugin_info()
	 * @param bool $strip_desc Strip any additional version text
	 * @return string Plugin version
	 */
	function get_plugin_version($strip_desc = true) {
		// Retrieve version
		$ret = $this->get_plugin_info('Version');
		// Format
		if ( !empty($ret) && $strip_desc ) {
			$ret = explode(' ', $ret, 2);
			$ret = $ret[0];
		}
		// Return
		return $ret;
	}
	
	/**
	 * Retrieve current post type based on URL query variables
	 * @return string|null Current post type
	 */
	public function get_post_type() {
		if ( isset($_GET['post_type']) && !empty($_GET['post_type']) ) {
			return $_GET['post_type'];
		}
		$pt = null;
		if ( isset($_GET['post']) && is_numeric($_GET['post']) ) {
			$pt = get_post_type($_GET['post']);
		}
		return $pt;
	}
	
	/**
	 * Retrieve current action based on URL query variables
	 * @param mixed $default (optional) Default action if no action exists
	 * @return string Current action
	 */
	function get_action($default = null) {
		$action = '';
		
		// Check if action is set in URL
		if ( isset($_GET['action']) )
			$action = $_GET['action'];
		// Otherwise, Determine action based on plugin plugin admin page suffix
		elseif ( isset($_GET['page']) && ($pos = strrpos($_GET['page'], '-')) && $pos !== false && ( $pos != count($_GET['page']) - 1 ) )
			$action = trim(substr($_GET['page'], $pos + 1), '-_');

		// Determine action for core admin pages
		if ( ( !isset($_GET['page']) || empty($action) ) && isset($_SERVER['SCRIPT_NAME']) ) {
			$actions = array(
				'add'			=> array('page-new', 'post-new'),
				'edit-item'		=> array('page', 'post'),
				'edit'			=> array('edit', 'edit-pages')
			);
			$page = basename($_SERVER['SCRIPT_NAME'], '.php');
			
			foreach ( $actions as $act => $pages ) {
				if ( in_array($page, $pages) ) {
					$action = $act;
					break;
				}
			}
		}
		if ( empty($action) )
			$action = $default;
		return $action;
	}
	
	/*-** General **-*/
	
	/**
	 * Checks if a property exists in a class or object
	 * Compatibility method for PHP 4
	 * @param mixed $class Class or object to check 
	 * @param string $property Name of property to look for in $class
	 */
	function property_exists($class, $property) {
		if ( !is_object($class) && !is_array($class) )
			return false;
		if ( function_exists('property_exists') && is_object($class) ) {
			return property_exists($class, $property);
		} else {
			return array_key_exists($property, $class);
		}
	}
	
	/**
	 * Retrieve specified property from object or array
	 * @param object|array $obj Object or array to get property from
	 * @param string $property Property name to retrieve
	 * @return mixed Property value
	 */
	function &get_property(&$obj, $property) {
		$property = trim($property);
		// Object
		if ( is_object($obj) )
			return $obj->{$property};
		// Array
		if ( is_array($obj) )
			return $obj[$property];
		// Class
		if ( is_string($obj) && class_exists($obj) ) {
			$cvars = get_class_vars($obj);
			if ( isset($cvars[$property]) )
				return $cvars[$property];
		}
	}
	
	/**
	 * Remap array members based on a
	 * mapping of source/destination keys
	 * @param array $properties Associative array of properties
	 * @param array $map Source/Destination mapping
	 * > Key: Source member
	 * > Val: Destination member
	 * @param bool $overwrite If TRUE, source value will be set in destination regardless of whether member already exists or not
	 * @return array Remapped properties
	 */
	function array_remap($arr, $map = array(), $overwrite = false) {
		if ( !empty($map) && is_array($map) ) {
			// Iterate through mappings
			foreach ( $map as $from => $to ) {
				if ( !array_key_exists($from, $arr) )
					continue;
				$move = $overwrite;
				// Only remap if parent property doesn't already exist in array
				if ( !array_key_exists($to, $arr) )
					$move = true;
				if ( $move ) {
					// Move member value to new key
					$arr[$to] = $arr[$from];
					// Remove source member
					unset($arr[$from]);
				}
			}
		}
		// Return remapped properties
		return $arr;
	}
	
	function array_filter_keys($arr, $keys) {
		if ( is_array($arr) && !empty($arr) && is_array($keys) && !empty($keys) ) {
			foreach ( $keys as $rem ) {
				if ( array_key_exists($rem, $arr) )
					unset($arr[$rem]);
			}
		}

		return $arr;
	}
	
	/**
	 * Insert an item into an array at the specified position
	 * @param mixed $item Item to insert into array 
	 * @param int $pos Index position to insert item into array
	 * @return array Modified array
	 */
	function array_insert($array, $item, $pos = null) {
		array_splice($array, $pos, 0, $item);
		return $array;
	}
	
	/**
	 * Merges 1 or more arrays together
	 * Methodology
	 * - Set first parameter as base array
	 *   - All other parameters will be merged into base array
	 * - Iterate through other parameters (arrays)
	 *   - Skip all non-array parameters
	 *   - Iterate though key/value pairs of current array
	 *     - Merge item in base array with current item based on key name
	 *     - If the current item's value AND the corresponding item in the base array are BOTH arrays, recursively merge the the arrays
	 *     - If the current item's value OR the corresponding item in the base array is NOT an array, current item overwrites base item
	 * @param array Variable number of arrays
	 * @param array $arr1 Default array
	 * @return array Merged array
	 */
	function array_merge_recursive_distinct($arr1) {
		// Get all arrays passed to function
		$args = func_get_args();
		if ( empty($args) )
			return false;
		// Return empty array if first parameter is not an array
		if ( !is_array($args[0]) )
			return array();
		// Set first array as base array
		$merged = $args[0];
		// Iterate through arrays to merge
		$arg_length = count($args);
		for ( $x = 1; $x < $arg_length; $x++ ) {
			// Skip if argument is not an array (only merge arrays)
			if ( !is_array($args[$x]) )
				continue;
			// Iterate through argument items
			foreach ( $args[$x] as $key => $val ) {
				// Generate key for numeric indexes
				if ( is_int($key) ) {
					// Add new item to merged array
					$merged[] = null;
					// Get key of new item
					$key = array_pop(array_keys($merged));
				}
				if ( !isset($merged[$key]) || !is_array($merged[$key]) || !is_array($val) ) {
					$merged[$key] = $val;
				} elseif ( is_array($merged[$key]) && is_array($val) ) {
					$merged[$key] = $this->array_merge_recursive_distinct($merged[$key], $val);
				}
			}
		}
		return $merged;
	}
	
	/**
	 * Replaces string value in one array with the value of the matching element in a another array
	 * 
	 * @param string $search Text to search for in array
	 * @param array $arr_replace Array to use for replacing values
	 * @param array $arr_subject Array to search for specified value
	 * @return array Searched array with replacements made
	 */
	function array_replace_recursive($search, $arr_replace, $arr_subject) {
		foreach ($arr_subject as $key => $val) {
			// Skip element if key does not exist in the replacement array
			if (!isset($arr_replace[$key]))
				continue;
			// If element values for both arrays are strings, replace text
			if (is_string($val) && strpos($val, $search) !== false && is_string($arr_replace[$key]))
				$arr_subject[$key] = str_replace($search, $arr_replace[$key], $val);
			// If value in both arrays are arrays, recursively replace text
			if (is_array($val) && is_array($arr_replace[$key]))
				$arr_subject[$key] = $this->array_replace_recursive($search, $arr_replace[$key], $val);
		}
		
		return $arr_subject;
	}
	
	/**
	 * Checks if item at specified path in array is set
	 * @param array $arr Array to check for item
	 * @param array $path Array of segments that form path to array (each array item is a deeper dimension in the array)
	 * @return boolean TRUE if item is set in array, FALSE otherwise
	 */
	function array_item_isset(&$arr, &$path) {
		$f_path = $this->get_array_path($path);
		return eval('return isset($arr' . $f_path . ');');
	}
	
	/**
	 * Build formatted string based on array values
	 * Array values in formatted string will be ordered by index order
	 * @param array $attribute Values to build string with
	 * @param string $format (optional) Format name (Default: Multidimensional array representation > ['value1']['value2']['value3'], etc.)
	 * @return string Formatted string based on array values
	 */
	function get_array_path($attribute = '', $format = null) {
		// Formatted value
		$fmtd = '';
		if (!empty($attribute)) {
			// Make sure attribute is array
			if (!is_array($attribute)) {
				$attribute = array($attribute);
			}
			// Format attribute
			$format = strtolower($format);
			switch ($format) {
				case 'id':
					$fmtd = array_shift($attribute) . '[' . implode('][', $attribute) . ']';
					break;
				case 'metadata':
				case 'attribute':
					// Join segments
					$delim = '_';
					$fmtd = implode($delim, $attribute);
					// Replace white space and repeating delimiters
					$fmtd = str_replace(' ', $delim, $fmtd);
					while (strpos($fmtd, $delim.$delim) !== false)
						$fmtd = str_replace($delim.$delim, $delim, $fmtd);
					// Prefix formatted value with delimeter for metadata keys
					if ('metadata' == $format)
						$fmtd = $delim . $fmtd;
					break;
				case 'path':
				case 'post':
				default:
					$fmtd = '["' . implode('"]["', $attribute) . '"]';
			}
		}
		return $fmtd;
	}
	
	/**
	 * Builds array of path elements based on arguments
	 * Each item in path array represents a deeper level in structure path is for (object, array, filesystem, etc.)
	 * @param array|string Value to add to the path
	 * @return array 1-dimensional array of path elements
	 */
	function build_path() {
		$path = array();
		$args = func_get_args();
		
		// Iterate through parameters and build path
		foreach ( $args as $arg ) {
			if ( empty($arg) )
				continue;
				
			if (is_array($arg)) {
				// Recurse through array items to pull out any more arrays
				foreach ($arg as $key => $val) {
					$path = array_merge($path, $this->build_path($val));
				}
				// $path = array_merge($path, array_values($arg));
			} elseif ( is_scalar($arg) ) {
				$path[] = $arg;
			}
		}
		
		return $path;
	}
	
	/**
	 * Build generic element
	 * @param array $args
	 * @return string Element output
	 */
	public function build_element($args = array()) {
		$ret = '';
		$args_default = array(
			'tag'			=> '',
			'wrap'			=> false,
			'content'		=> '',
			'attributes'	=> array(),
			'format'		=> array(),
		);
		$format_default = array(
			'open'	=> '[%s]',
			'close'	=> '[/%s]',
		);
		$args = wp_parse_args($args, $args_default);
		$args['format'] = wp_parse_args($args['format'], $format_default);
		
		// Validate
		if ( !is_string($args['tag']) || empty($args['tag']) ) {
			return $ret;
		}
		
		$args = (object) $args;
		
		$args->attributes = $this->build_attribute_string($args->attributes);
		if ( strlen($args->attributes) > 0 ) {
			$args->attributes = ' ' . $args->attributes;
		}
		
		// Build output
		$args->format = (object) $args->format;
		$ret = sprintf( $args->format->open, $args->tag . $args->attributes);
		
		// Wrap content if necessary
		if ( $args->wrap || ( is_string($args->content) && !empty($args->content) ) ) {
			$ret .= $args->content . sprintf( $args->format->close, $args->tag);
		}

		return $ret;
	}
	
	/**
	 * Parse string of attributes into array
	 * For XML/XHTML tag attributes
	 * @param string $txt Attribute text (Can be full tag or just attributes)
	 * @return array Attributes as associative array
	 */
	function parse_attribute_string($txt, $defaults = array()) {
		$txt = trim($txt, ' >');
		$matches = $attr = array();
		// Strip tag
		if ( $txt[0] == '<' && ($s = strpos($txt, ' ')) && $s !== false ) {
			$txt = trim(substr($txt, $s + 1));
		}
		// Parse attributes
		$rgx = "/\b(\w+.*?)=([\"'])(.*?)\\2(?:\s|$)/i";
		preg_match_all($rgx, $txt, $matches);
		if ( count($matches) > 3 ) {
			foreach ( $matches[1] as $sub_idx => $val ) {
				if ( isset($matches[3][$sub_idx]) )
					$attr[trim($val)] = trim($matches[3][$sub_idx]);
			}
		}
		// Destroy parsing vars
		unset($txt, $matches);

		return array_merge($defaults, $attr);
	}
	
	/**
	 * Builds attribute string for HTML element
	 * @param array $attr Attributes
	 * @return string Formatted attribute string
	 */
	function build_attribute_string($attr) {
		$ret = '';
		if ( is_object($attr) ) {
			$attr = (array) $attr;
		}
		if ( is_array($attr) && !empty($attr) ) {
			array_map('esc_attr', $attr);
			$attr_str = array();
			foreach ( $attr as $key => $val ) {
				$attr_str[] = $key . '="' . $val . '"';
			}
			$ret = implode(' ', $attr_str);
		}
		return $ret;
	}
	
	/* HTML */	

	/**
	 * Generate HTML element based on values
	 * @param $args Element arguments
	 * @return string Generated HTML element
	 */
	public function build_html_element($args) {
		$args_default = array(
			'tag'			=> 'span',
			'content'		=> '',
			'attributes'	=> array()
		);
		$args = wp_parse_args($args, $args_default);
		$args['format'] = array(
			'open'		=> '<%s>',
			'close'		=> '</%s>',
		);
		// Build element
		return $this->build_element($args);
	}
	
	/**
	 * Build HTML link element
	 * @uses build_html_element() to build link output
	 * @param string $uri Link URI
	 * @param string $content Link content
	 * @param $array (optional) $attributes Additional link attributes
	 * @return string HTML link element
	 */
	function build_html_link($uri, $content, $attributes = array()) {
		$attributes = array_merge(array('href' => $uri, 'title' => $content), $attributes);
		return $this->build_html_element(array('tag' => 'a', 'wrap' => true, 'content' => $content, 'attributes' => $attributes));
	}
	
	/**
	 * Generate external stylesheet element
	 * @param $url Stylesheet URL
	 * @return string Stylesheet element
	 */
	function build_stylesheet_element($url) {
		$attributes = array('href' => $url, 'type' => 'text/css', 'rel' => 'stylesheet');
		return $this->build_html_element(array('tag' => 'link', 'wrap' => false, 'attributes' => $attributes));
	}
	
	/**
	 * Build client-side script element
	 * 
	 * @param string $content Script content
	 * @param string $id (optional) Element ID
	 * @param bool $wrap_jquery (optional) Wrap commands in jQuery? (Default: Yes)
	 * @param bool $wait_doc_ready (optional) Wait until document is fully loaded before executing commands? (Default: No)
	 */
	function build_script_element($content = '', $id = '', $wrap_jquery = true, $wait_doc_ready = false) {
		// Stop processing invalid content
		if ( is_array($content) && !empty($content) ) {
			$content = implode(PHP_EOL, $content);
		}
		if ( empty($content) || !is_string($content) ) {
			return '';
		}
		$attributes = array('type' => 'text/javascript');
		$start = array('/* <![CDATA[ */');
		$end = array('/* ]]> */');
		if ( $wrap_jquery ) {
			$start[] = 'if ( !!window.jQuery ) {(function($){';
			$end[] = '})(jQuery);}';
			
			// Add event handler (if necessary)
			if ( $wait_doc_ready ) {
				$start[] = '$(document).ready(function(){';
				$end[] = '})';
			}
		}
		
		// Reverse order of end values
		$end = array_reverse($end);
		$content = implode('', array_merge($start, array($content), $end));
		if ( is_string($id) && !empty($id) ) {
			$attributes['id'] = $this->add_prefix($id);
		}
		return $this->build_html_element(array('tag' => 'script', 'content' => $content, 'wrap' => true, 'attributes' => $attributes)) . PHP_EOL;
	}
}