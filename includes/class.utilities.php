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
	
	function SLB_Utilities() {
		$this->__construct();
	}
	
	function __construct() {
		
	}
	
	/**
	 * Returns callback array to instance method
	 * @param object $obj Instance object
	 * @param string $method Name of method
	 * @return array Callback array
	 */
	function &m(&$obj, $method = '') {
		if ( $obj == null && isset($this) )
			$obj =& $this;
		$arr = array(&$obj, $method);
		return $arr;
	}
	
	/* Helper Functions */
	
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
	
	/*-** Request **-*/
	
	/**
	 * Checks if the currently executing file matches specified file name
	 * @param string $filename Filename to check for
	 * @return bool TRUE if current page matches specified filename, FALSE otherwise
	 */
	function is_file( $filename ) {
		return ( $filename == basename( $_SERVER['SCRIPT_NAME'] ) );
	}
	
	/**
	 * Checks whether the current page is a management page
	 * @return bool TRUE if current page is a management page, FALSE otherwise
	 */
	function is_admin_management_page() {
		return ( is_admin()
				 && ( $this->is_file('edit.php')
				 	|| ( $this->is_file('admin.php')
				 		&& isset($_GET['page'])
				 		&& strpos($_GET['page'], 'cnr') === 0 )
				 	)
				 );
	}
	
	/**
	 * Joins and normalizes the slashes in the paths passed to method
	 * All forward/back slashes are converted to forward slashes
	 * Multiple path segments can be passed as additional argments
	 * @param string $path Path to normalize
	 * @param bool $trailing_slash (optional) Whether or not normalized path should have a trailing slash or not (Default: FALSE)
	 *  If multiple path segments are passed, $trailing_slash will be the LAST parameter (default value used if omitted)
	 */
	function normalize_path($path, $trailing_slash = false) {
		$sl_f = '/';
		$sl_b = '\\';
		$parts = func_get_args();
		if ( func_num_args() > 1 ) {
			if ( is_bool(($tr = $parts[count($parts) - 1])) ) {
				$trailing_slash = $tr;
				//Remove from args array
				array_pop($parts);
			} else {
				$trailing_slash = false;
			}
			$first = true;
			//Trim trailing slashes from path parts
			foreach ( $parts as $key => $part ) {
				$part = trim($part);
				//Special Trim
				$parts[$key] = trim($part, $sl_f . $sl_b);
				//Verify path still contains value
				if ( empty($parts[$key]) ) {
					unset($parts[$key]);
					continue;
				}
				//Only continue processing the first valid path segment
				if ( $first )
					$first = !$first;
				else
					continue;
				//Add back leading slash if necessary
				if ( $part[0] == $sl_f || $part[0] == $sl_b )
					$parts[$key] = $sl_f . $parts[$key];
				
			}
		}
		//Join path parts together
		$parts = implode($sl_b, $parts);
		$parts = str_replace($sl_b, $sl_f, $parts);
		//Add trailing slash (if necessary)
		if ( $trailing_slash )
			$parts .= $sl_f;
		return $parts;
	}
	
	/**
	 * Returns URL of file (assumes that it is in plugin directory)
	 * @param string $file name of file get URL
	 * @return string File path
	 */
	function get_file_url($file) {
		if ( is_string($file) && '' != trim($file) ) {
			$file = $this->normalize_path($this->get_url_base(), $file);
		}
		return $file;
	}
	
	/**
	 * Retrieves file extension
	 * @param string $file file name/path
	 * @return string File's extension
	 */
	function get_file_extension($file) {
		$ret = '';
		$sep = '.';
		if ( is_string($icon) && ( $rpos = strrpos($file, $sep) ) !== false ) 
			$ret = substr($file, $rpos + 1);
		return $ret;
	}
	
	/**
	 * Checks if file has specified extension
	 * @param string $file File name/path
	 * @param string $extension File ending to check $file for
	 * @return bool TRUE if file has extension
	 */
	function has_file_extension($file, $extension) {
		return ( $this->get_file_extension($file) == $extension ) ? true : false;
	}
	
	/**
	 * Retrieve base URL for plugin-specific files
	 * @return string Base URL
	 */
	function get_url_base() {
		static $url_base = '';
		if ( '' == $url_base ) {
			$url_base = $this->normalize_path(plugins_url(), $this->get_plugin_base());
		}
		return $url_base;
	}
	
	function get_path_base() {
		static $path_base = '';
		if ( '' == $path_base ) {
			$path_base = $this->normalize_path(WP_PLUGIN_DIR, $this->get_plugin_base());
		}
		return $path_base;
	}
	
	function get_plugin_base() {
		static $plugin_dir = '';
		if ( '' == $plugin_dir ) {
			$plugin_dir = str_replace($this->normalize_path(WP_PLUGIN_DIR), '', $this->normalize_path(dirname(dirname(__FILE__))));
		}
		return $plugin_dir;
	}
	
	function get_plugin_base_file() {
		$file = 'main.php';
		return $this->get_path_base() . '/' . $file;
	}
	
	function get_plugin_base_name() {
		$file = $this->get_plugin_base_file();
		return plugin_basename($file);
	}
	
	/**
	 * Retrieve current action based on URL query variables
	 * @param mixed $default (optional) Default action if no action exists
	 * @return string Current action
	 */
	function get_action($default = null) {
		$action = '';
		
		//Check if action is set in URL
		if ( isset($_GET['action']) )
			$action = $_GET['action'];
		//Otherwise, Determine action based on plugin plugin admin page suffix
		elseif ( isset($_GET['page']) && ($pos = strrpos($_GET['page'], '-')) && $pos !== false && ( $pos != count($_GET['page']) - 1 ) )
			$action = trim(substr($_GET['page'], $pos + 1), '-_');

		//Determine action for core admin pages
		if ( ! isset($_GET['page']) || empty($action) ) {
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
	 * (Compatibility method for PHP 4
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
		//Object
		if ( is_object($obj) )
			return $obj->{$property};
		//Array
		if ( is_array($obj) )
			return $obj[$property];
		//Class
		if ( is_string($obj) && class_exists($obj) ) {
			$cvars = get_class_vars($obj);
			if ( isset($cvars[$property]) )
				return $cvars[$property];
		}
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
		//Get all arrays passed to function
		$args = func_get_args();
		if ( empty($args) )
			return false;
		//Return empty array if first parameter is not an array
		if ( !is_array($args[0]) )
			return array();
		//Set first array as base array
		$merged = $args[0];
		//Iterate through arrays to merge
		$arg_length = count($args);
		for ( $x = 1; $x < $arg_length; $x++ ) {
			//Skip if argument is not an array (only merge arrays)
			if ( !is_array($args[$x]) )
				continue;
			//Iterate through argument items
			foreach ( $args[$x] as $key => $val ) {
				//Generate key for numeric indexes
				if ( is_int($key) ) {
					//Add new item to merged array
					$merged[] = null;
					//Get key of new item
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
			//Skip element if key does not exist in the replacement array
			if (!isset($arr_replace[$key]))
				continue;
			//If element values for both arrays are strings, replace text
			if (is_string($val) && strpos($val, $search) !== false && is_string($arr_replace[$key]))
				$arr_subject[$key] = str_replace($search, $arr_replace[$key], $val);
			//If value in both arrays are arrays, recursively replace text
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
	 * Returns value of item at specified path in array
	 * @param array $arr Array to get item from
	 * @param array $path Array of segments that form path to array (each array item is a deeper dimension in the array)
	 * @return mixed Value of item in array (Default: empty string)
	 */
	function &get_array_item(&$arr, &$path) {
		$item = '';
		if ($this->array_item_isset($arr, $path)) {
			eval('$item =& $arr' . $this->get_array_path($path) . ';');
		}
		return $item;
	}
	
	function get_array_path($attribute = '', $format = null) {
		//Formatted value
		$fmtd = '';
		if (!empty($attribute)) {
			//Make sure attribute is array
			if (!is_array($attribute)) {
				$attribute = array($attribute);
			}
			//Format attribute
			$format = strtolower($format);
			switch ($format) {
				case 'id':
					$fmtd = array_shift($attribute) . '[' . implode('][', $attribute) . ']';
					break;
				case 'metadata':
				case 'attribute':
					//Join segments
					$delim = '_';
					$fmtd = implode($delim, $attribute);
					//Replace white space and repeating delimiters
					$fmtd = str_replace(' ', $delim, $fmtd);
					while (strpos($fmtd, $delim.$delim) !== false)
						$fmtd = str_replace($delim.$delim, $delim, $fmtd);
					//Prefix formatted value with delimeter for metadata keys
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
		
		//Iterate through parameters and build path
		foreach ( $args as $arg ) {
			if ( empty($arg) )
				continue;
				
			if (is_array($arg)) {
				//Recurse through array items to pull out any more arrays
				foreach ($arg as $key => $val) {
					$path = array_merge($path, $this->build_path($val));
				}
				//$path = array_merge($path, array_values($arg));
			} elseif ( is_scalar($arg) ) {
				$path[] = $arg;
			}
		}
		
		return $path;
	}
	
	/**
	 * Builds attribute string for HTML element
	 * @param array $attr Attributes
	 * @return string Formatted attribute string
	 */
	function build_attribute_string($attr) {
		$ret = '';
		if ( is_object($attr) )
			$attr = (array) $attr;
		if ( is_array($attr) ) {
			array_map('esc_attr', $attr);
			$attr_str = array();
			foreach ( $attr as $key => $val ) {
				$attr_str[] = $key . '="' . $val . '"';
			}
			$ret = implode(' ', $attr_str);
		}
		return $ret;
	}
	
	/**
	 * Generate external stylesheet element
	 * @param $url Stylesheet URL
	 * @return string Stylesheet element
	 */
	function build_stylesheet_element($url = '') {
		$attributes = array('href' => $url, 'type' => 'text/css', 'rel' => 'stylesheet');
		return $this->build_html_element(array('tag' => 'link', 'wrap' => false, 'attributes' => $attributes));
	}
	
	/**
	 * Generate external script element
	 * @param $url Script URL
	 * @return string Script element
	 */
	function build_ext_script_element($url = '') {
		$attributes = array('src' => $url, 'type' => 'text/javascript');
		return $this->build_html_element(array('tag' => 'script', 'attributes' => $attributes));
	}
	
	/**
	 * Generate HTML element based on values
	 * @param $args Element arguments
	 * @return string Generated HTML element
	 */
	function build_html_element($args) {
		$defaults = array(
						'tag'			=> 'span',
						'wrap'			=> true,
						'content'		=> '',
						'attributes'	=> array()
						);
		$el_start = '<';
		$el_end = '>';
		$el_close = '/';
		extract(wp_parse_args($args, $defaults), EXTR_SKIP);
		$content = trim($content);
		
		if ( !$wrap && strlen($content) > 0 )
			$wrap = true;
		
		$attributes = $this->build_attribute_string($attributes);
		if ( strlen($attributes) > 0 )
			$attributes = ' ' . $attributes;
			
		$ret = $el_start . $tag . $attributes;
		
		if ( $wrap )
			$ret .= $el_end . $content . $el_start . $el_close . $tag;
		else
			$ret .= ' ' . $el_close;

		$ret .= $el_end;
		return $ret;	
	}
	
	/*-** Admin **-*/
	
	/**
	 * Add submenu page in the admin menu
	 * Adds ability to set the position of the page in the menu
	 * @see add_submenu_page (Wraps functionality)
	 * 
	 * @param $parent
	 * @param $page_title
	 * @param $menu_title
	 * @param $access_level
	 * @param $file
	 * @param $function
	 * @param int $pos Index position of menu page
	 * 
	 * @global array $submenu Admin page submenus
	 */
	function add_submenu_page($parent, $page_title, $menu_title, $capability, $file, $function = '', $pos = false) {
		//Add submenu page as usual
		$args = func_get_args();
		$hookname = call_user_func_array('add_submenu_page', $args);
		if ( is_int($pos) ) {
			global $submenu;
			//Get last submenu added
			$parent = $this->get_submenu_parent_file($parent);
			if ( isset($submenu[$parent]) ) {
			$subs =& $submenu[$parent];

			//Make sure menu isn't already in the desired position
			if ( $pos <= ( count($subs) - 1 ) ) {
				//Get submenu that was just added
				$sub = array_pop($subs);
				//Insert into desired position
				if ( 0 == $pos ) {
					array_unshift($subs, $sub);
				} else {
					$top = array_slice($subs, 0, $pos);
					$bottom = array_slice($subs, $pos);
					array_push($top, $sub);
					$subs = array_merge($top, $bottom);
				}
			}
		}
		}
		
		return $hookname;
	}
	
	/**
	 * Remove admin submenu
	 * @param string $parent Submenu parent file
	 * @param string $file Submenu file name
	 * @return int|null Index of removed submenu (NULL if submenu not found)
	 * 
	 * @global array $submenu
	 * @global array $_registered_pages
	 */
	function remove_submenu_page($parent, $file) {
		global $submenu, $_registered_pages;
		$ret = null;
		
		$parent = $this->get_submenu_parent_file($parent);
		$file = plugin_basename($file);
		$file_index = 2;
		
		//Find submenu
		if ( isset($submenu[$parent]) ) {
			$subs =& $submenu[$parent];
			for ($x = 0; $x < count($subs); $x++) {
				if ( $subs[$x][$file_index] == $file ) {
					//Remove matching submenu
					$hookname = get_plugin_page_hookname($file, $parent);
					remove_all_actions($hookname);
					unset($_registered_pages[$hookname]);
					unset($subs[$x]);
					$subs = array_values($subs);
					//Set index and stop processing
					$ret = $x;
					break;
				}
			}
		}
		
		return $ret;
	}
	
	/**
	 * Replace a submenu page
	 * Adds a submenu page in the place of an existing submenu page that has the same $file value
	 * 
	 * @param $parent
	 * @param $page_title
	 * @param $menu_title
	 * @param $access_level
	 * @param $file
	 * @param $function
	 * @return string Hookname
	 * 
	 * @global array $submenu
	 */
	function replace_submenu_page($parent, $page_title, $menu_title, $access_level, $file, $function = '') {
		global $submenu;
		//Remove matching submenu (if exists)
		$pos = $this->remove_submenu_page($parent, $file);
		//Insert submenu page
		$hookname = $this->add_submenu_page($parent, $page_title, $menu_title, $access_level, $file, $function, $pos);
		return $hookname;
	}
	
	/**
	 * Retrieves parent file for submenu
	 * @param string $parent Parent file
	 * @return string Formatted parent file name
	 * 
	 * @global array $_wp_real_parent_file;
	 */
	function get_submenu_parent_file($parent) {
		global $_wp_real_parent_file;
		$parent = plugin_basename($parent);
		if ( isset($_wp_real_parent_file[$parent]) )
			$parent = $_wp_real_parent_file[$parent];
		return $parent;
	}
}