<?php

/**
 * Fields - Base class
 * Core properties/methods for fields
 * @package Simple Lightbox
 * @subpackage Fields
 * @author Archetyped
 */
class SLB_Field_Base extends SLB_Base {
	/*-** Config **-*/
	protected $mode = 'object';
	protected $shared = false;
	
	/*-** Properties **-*/
	
	/**
	 * @var string Unique name
	 */
	var $id = '';
	
	/**
	 * ID formatting options
	 * Merged with defaults during initialization
	 * @see $id_formats_default
	 * @var array
	 */
	var $id_formats = null;
	
	/**
	 * Default ID Formatting options
	 * Structure:
	 * > Key (string): Format name
	 * > Val (array): Options
	 * @var array
	 */
	var $id_formats_default = array(
		'attr_id' => array(
			'wrap'			=> array('open' => '_', 'segment_open' => '_'),
			'prefix'		=> array('get_container', 'get_id', 'add_prefix'),
			'recursive'		=> true
		),
		'attr_name' => array(
			'wrap'		=> array('open' => '[', 'close' => ']', 'segment_open' => '[', 'segment_close' => ']'),
			'recursive'	=> true,
			'prefix'	=> array('get_container', 'get_id', 'add_prefix')
		)
	);
	
	/**
	 * Special characters/phrases
	 * Used for preserving special characters during formatting
	 * Merged with $special_chars_default
	 * Array Structure
	 * > Key: Special character/phrase
	 * > Value: Placeholder for special character
	 * @var array
	 */
	var $special_chars = null;
	
	var $special_chars_default = array(
		'{'		=> '%SQB_L%',
		'}'		=> '%SQB_R%',
	);

	/**
	 * Reference to parent object that current instance inherits from
	 * @var object
	 */
	var $parent = null;

	/**
	 * Title
	 * @var string
	 */
	var $title = '';

	/**
	 * @var string Short description
	 */
	var $description = '';

	/**
	 * @var array Object Properties
	 */
	var $properties = array();
	
	/**
	 * Initialization properties
	 * @var array
	 */
	protected $properties_init = null;
	
	/**
	 * Structure: Property names stored as keys in group
	 * Root
	 *  -> Group Name
	 *    -> Property Name => Null
	 * Reason: Faster searching over large arrays
	 * @var array Groupings of Properties
	 */
	var $property_groups = array();
	
	/**
	 * Keys to filter out of properties array before setting properties
	 * @var array
	 */
	var $property_filter = array('group');
	
	/**
	 * Define order of properties
	 * Useful when processing order is important (e.g. one property depends on another)
	 * @var array
	 */
	var $property_priority = array();
	
	/**
	 * Data for object
	 * May also contain data for nested objects
	 * @var mixed
	 */
	var $data = null;
	
	/**
	 * Whether data has been fetched or not
	 * @var bool
	 */
	var $data_loaded = false;
	
	/**
	 * @var array Script resources to include for object
	 */
	var $scripts = array();

	/**
	 * @var array CSS style resources to include for object
	 */
	var $styles = array();

	/**
	 * Hooks (Filters/Actions) for object
	 * @var array
	 */
	var $hooks = array();
	
	/**
	 * Mapping of child properties to parent members
	 * Allows more flexibility when creating new instances of child objects using property arrays
	 * Associative array structure:
	 *  > Key: Child property to map FROM
	 *  > Val: Parent property to map TO
	 * @var array
	 */
	var $map = null;
	
	/**
	 * Options used when building collection (callbacks, etc.)
	 * Associative array
	 * > Key: Option name
	 * > Value: Option value
	 * @var array
	 */
	var $build_vars = array();
	
	var $build_vars_default = array();
	
	/**
	 * Constructor
	 */
	function __construct($id = '', $properties = null) {
		parent::__construct();
		// Normalize Properties
		$args = func_get_args();
		$defaults = $this->integrate_id($id);
		$properties = $this->make_properties($args, $defaults);
		// Save init properties
		$this->properties_init = $properties;
		// Set Properties
		$this->set_properties($properties);
	}

	/* Getters/Setters */
	
	/**
	 * Checks if the specified path exists in the object
	 * @param array $path Path to check for
	 * @return bool TRUE if path exists in object, FALSE otherwise
	 */
	function path_isset($path = '') {
		// Stop execution if no path is supplied
		if ( empty($path) )
			return false;
		$args = func_get_args();
		$path = $this->util->build_path($args);
		$item =& $this;
		// Iterate over path and check if each level exists before moving on to the next
		for ($x = 0; $x < count($path); $x++) {
			if ( $this->util->property_exists($item, $path[$x]) ) {
				// Set $item as reference to next level in path for next iteration
				$item =& $this->util->get_property($item, $path[$x]);
				// $item =& $item[ $path[$x] ];
			} else {
				return false;
			}
		}
		return true; 
	}

	/**
	 * Retrieves a value from object using a specified path
	 * Checks to make sure path exists in object before retrieving value
	 * @param array $path Path to retrieve value from. Each item in array is a deeper dimension
	 * @return mixed Value at specified path
	 */
	function &get_path_value($path = '') {
		$ret = '';
		$path = $this->util->build_path(func_get_args());
		if ( $this->path_isset($path) ) {
			$ret =& $this;
			for ($x = 0; $x < count($path); $x++) {
				if ( 0 == $x )
					$ret =& $ret->{ $path[$x] };
				else
					$ret =& $ret[ $path[$x] ];
			}
		}
		return $ret;
	}

	/**
	 * Search for specified member value in field type ancestors
	 * @param string $member Name of object member to search (e.g. properties, layout, etc.)
	 * @param string $name Value to retrieve from member
	 * @return mixed Member value if found (Default: empty string)
	 */
	function get_parent_value($member, $name = '', $default = '') {
		$parent = $this->get_parent();
		return $this->get_object_value($parent, $member, $name, $default, 'parent');
	}

	/**
	 * Retrieves specified member value
	 * Handles inherited values
	 * Merging corresponding parents if value is an array (e.g. for property groups)
	 * @param string|array $member Member to search.  May also contain a path to the desired member
	 * @param string $name Value to retrieve from member
	 * @param mixed $default Default value if no value found (Default: empty string)
	 * @param string $dir Direction to move through hierarchy to find value
	 * Possible Values:
	 *  parent (default) 	- Search through field parents
	 *  current				- Do not search through connected objects
	 *  container			- Search through field containers
	 *  caller				- Search through field callers
	 * @return mixed Specified member value
	 * @todo Return reference
	 */
	function &get_member_value($member, $name = '', $default = '', $dir = 'parent') {
		// Check if path to member is supplied
		$path = array();
		if ( is_array($member) && isset($member['tag']) ) {
			if ( isset($member['attributes']['ref_base']) ) {
				if ( 'root' != $member['attributes']['ref_base'] )
					$path[] = $member['attributes']['ref_base'];
			} else {
				$path[] = 'properties';
			}

			$path[] = $member['tag'];
		} else {
			$path = $member;
		}

		$path = $this->util->build_path($path, $name);
		// Set defaults and prepare data
		$val = $default;
		$inherit = false;
		$inherit_tag = '{inherit}';

		/* Determine whether the value must be retrieved from a parent/container object
		 * Conditions:
		 * > Path does not exist in current field
		 * > Path exists and is not an object, but at least one of the following is true:
		 *   > Value at path is an array (e.g. properties, elements, etc. array)
		 *     > Parent/container values should be merged with retrieved array
		 *   > Value at path is a string that inherits from another field
		 *     > Value from other field will be retrieved and will replace inheritance placeholder in retrieved value
		 */

		$deeper = false;

		if ( !$this->path_isset($path) )
			$deeper = true;
		else {
			$val = $this->get_path_value($path);
			if ( !is_object($val) && ( is_array($val) || ($inherit = strpos($val, $inherit_tag)) !== false ) )
				$deeper = true;
			else
				$deeper = false;
		}
		if ( $deeper && 'current' != $dir ) {
			$ex_val = '';
			// Get Parent value (recursive)
			if ( 'parent' == $dir )
				$ex_val = $this->get_parent_value($member, $name, $default);
			elseif ( method_exists($this, 'get_container_value') )
				$ex_val =  $this->get_container_value($member, $name, $default); 
			// Handle inheritance
			if ( is_array($val) ) {
				// Combine Arrays
				if ( is_array($ex_val) )
					$val = array_merge($ex_val, $val);
			} elseif ( $inherit !== false ) {
				// Replace placeholder with inherited string
				$val = str_replace($inherit_tag, $ex_val, $val);
			} else {
				// Default: Set parent value as value
				$val = $ex_val;
			}
		}

		return $val;
	}

	/**
	 * Search for specified member value in an object
	 * @param object $object Reference to object to retrieve value from
	 * @param string $member Name of object member to search (e.g. properties, layout, etc.)
	 * @param string $name (optional) Value to retrieve from member
	 * @param mixed $default (optional) Default value to use if no value found (Default: empty string)
	 * @param string $dir Direction to move through hierarchy to find value @see SLB_Field_Type::get_member_value() for possible values
	 * @return mixed Member value if found (Default: $default)
	 */
	function get_object_value(&$object, $member, $name = '', $default = '', $dir = 'parent') {
		$ret = $default;
		if ( is_object($object) && method_exists($object, 'get_member_value') )
			$ret = $object->get_member_value($member, $name, $default, $dir);
		return $ret;
	}
	
	/**
	 * Set item ID
	 * @param string $id Unique item ID
	 */
	function set_id($id) {
		if ( empty($id) || !is_string($id) )
			return false;
		$this->id = trim($id);
	}
	
	/**
	 * Retrieves field ID
	 * @param array|string $options (optional) Options or ID of format to use
	 * @return string item ID
	 */
	function get_id($options = array()) {
		$item_id = trim($this->id);
		$formats = $this->get_id_formats();
		
		// Setup options
		$wrap_default = array('open' => '', 'close' => '', 'segment_open' => '', 'segment_close' => '');
		
		$options_default = array(
			'format'			=> null,
			'wrap'				=> array(),
			'segments_pre'		=> null,
			'prefix'			=> '',
			'recursive'			=> false
		);
		
		// Load options based on format
		if ( !is_array($options) )
			$options = array('format' => $options);
		if ( isset($options['format']) && is_string($options['format']) && isset($formats[$options['format']]) )
			$options_default = wp_parse_args($formats[$options['format']], $options_default);
		else
			unset($options['format']);
		$options = wp_parse_args($options, $options_default);
		// Import options into function
		extract($options);

		// Validate options
		$wrap = wp_parse_args($wrap, $wrap_default);
		
		if ( !is_array($segments_pre) )
			$segments_pre = array($segments_pre);
		$segments_pre = array_reverse($segments_pre);
		
		// Format ID based on options
		$item_id = array($item_id);

		// Add parent objects to ID 
		if ( !!$recursive ) {
			// Create array of ID components
			$m = 'get_caller';
			$c = ( method_exists($this, $m) ) ? $this->{$m}() : null;
			while ( !!$c ) {
				// Add ID of current caller to array
				if ( method_exists($c, 'get_id') && ( $itemp = $c->get_id() ) && !empty($itemp) )
					$item_id = $itemp;
				// Get parent object
				$c = ( method_exists($c, $m) ) ? $c->{$m}() : null;
				$itemp = '';
			}
			unset($c);
		}
		
		// Additional segments (Pre)
		foreach ( $segments_pre as $seg ) {
			if ( is_null($seg) )
				continue;
			if ( is_object($seg) )
				$seg = (array)$seg;
			if ( is_array($seg) )
				$item_id = array_merge($item_id, array_reverse($seg));
			elseif ( '' != strval($seg) )
				$item_id[] = strval($seg);
		}
		
		// Prefix
		if ( is_array($prefix) ) {
			// Array is sequence of instance methods to call on object
			// Last array member can be an array of parameters to pass to methods
			$count = count($prefix);
			$args = ( $count > 1 && is_array($prefix[$count - 1]) ) ? array_pop($prefix) : array();
			$p = $this;
			$val = '';
			// Iterate through methods
			foreach ( $prefix as $m ) {
				if ( !method_exists($p, $m) )
					continue;
				// Build callback
				$m = $this->util->m($p, $m);
				// Call callback 
				$val = call_user_func_array($m, $args);
				// Returned value may be an instance object
				if ( is_object($val) )
					$p = $val; // Use returned object in next round
				else
					array_unshift($args, $val); // Pass returned value as parameter to next method on using current object
			}
			$prefix = $val;
			unset($p, $val);
		}
		if ( is_numeric($prefix) )
			$prefix = strval($prefix);
		if ( empty($prefix) || !is_string($prefix) )
			$prefix = ''; 

		// Convert array to string
		$item_id = $prefix . $wrap['open'] . implode($wrap['segment_close'] . $wrap['segment_open'], array_reverse($item_id)) . $wrap['close'];
		return $item_id;
	}
	
	/**
	 * Retrieve ID formatting options for class
	 * Format options arrays are merged together and saved to $id_formats
	 * @uses $id_formats
	 * @uses $id_formats_default
	 * @return array ID Formatting options
	 */
	function &get_id_formats() {
		if ( is_null($this->id_formats) ) {
			$this->id_formats = wp_parse_args($this->id_formats, $this->id_formats_default);
		}
		return $this->id_formats;
	}

	/**
	 * Retrieve value from data member
	 * @param string $context Context to format data for
	 * @param bool $top (optional) Whether to traverse through the field hierarchy to get data for field (Default: TRUE)
	 * @return mixed Value at specified path
	 */
	function get_data($context = '', $top = true) {
		$opt_d = array('context' => '', 'top' => true);
		$args = func_get_args();
		$a = false;
		if ( count($args) == 1 && is_array($args[0]) && !empty($args[0]) ) {
			$a = true;
			$args = wp_parse_args($args[0], $opt_d);
			extract($args);
		}
		
		if ( is_string($top) ) {
			if ( 'false' == $top )
				$top = false;
			elseif ( 'true' == $top )
				$top = true;
			elseif ( is_numeric($top) )
				$top = intval($top);
		}
		$top = !!$top;
		$obj =& $this;
		$obj_path = array($this);
		$path = array();
		if ( $top ) {
			// Iterate through hiearchy to get top-most object
			while ( !empty($obj) ) {
				$new = null;
				// Try to get caller first
				if ( method_exists($obj, 'get_caller') ) {
					$checked = true;
					$new =& $obj->get_caller();
				}
				// Try to get container if no caller found
				if ( empty($new) && method_exists($obj, 'get_container') ) {
					$checked = true;
					$new =& $obj->get_container();
					// Load data
					if ( method_exists($new, 'load_data') ) {
						$new->load_data();
					}
				}
	
				$obj =& $new;
				unset($new);
				// Stop iteration
				if ( !empty($obj) ) {
					// Add object to path if it is valid
					$obj_path[] =& $obj;
				}
			}
			unset($obj);
		}

		// Check each object (starting with top-most) for matching data for current field

		// Reverse array
		$obj_path = array_reverse($obj_path);
		// Build path for data location
		foreach ( $obj_path as $obj ) {
			if ( method_exists($obj, 'get_id') )
				$path[] = $obj->get_id();
		}
		// Iterate through objects
		while ( !empty($obj_path) ) {
			// Get next object
			$obj = array_shift($obj_path);
			// Shorten path
			array_shift($path);
			// Check for value in object and stop iteration if matching data found
			$val = $this->get_object_value($obj, 'data', $path, null, 'current');
			if ( !is_null($val) ) {
				break;
			}
		}
		return $this->format($val, $context);
	}

	/**
	 * Sets value in data member
	 * Sets value to data member itself by default
	 * @param mixed $value Value to set
	 * @param string|array $name Name of value to set (Can also be path to value)
	 */
	function set_data($value, $name = '') {
		$ref =& $this->get_path_value('data', $name);
		$ref = $value;
	}
	
	/**
	 * Sets parent object of current instance
	 * Parent objects must be the same object type as current instance
	 * @uses SLB to get field type definition
	 * @uses SLB_Fields::has() to check if field type exists
	 * @uses SLB_Fields::get() to retrieve field type object reference
	 * @param string|object $parent Parent ID or reference
	 */
	function set_parent($parent = null) {
		// Stop processing if parent empty
		if ( empty($parent) && !is_string($this->parent) )
			return false;
		// Parent passed as object reference wrapped in array
		if ( is_array($parent) && isset($parent[0]) && is_object($parent[0]) )
			$parent = $parent[0];
		
		// No parent set but parent ID (previously) set in object
		if ( empty($parent) && is_string($this->parent) )
			$parent = $this->parent;
		
		// Retrieve reference object if ID was supplied
		if ( is_string($parent) ) {
			$parent = trim($parent);
			// Get parent object reference
			/**
			 * @var SLB
			 */
			$b = $this->get_base();
			if ( !!$b && isset($b->fields) && $b->fields->has($parent) ) {
				$parent = $b->fields->get($parent);
			}
		}
		
		// Set parent value on object
		if ( is_string($parent) || is_object($parent) )
			$this->parent = $parent;
	}

	/**
	 * Retrieve field type parent
	 * @return SLB_Field_Type Parent field
	 */
	function get_parent() {
		return $this->parent;
	}

	/**
	 * Set object title
	 * @param string $title Title for object
	 * @param string $plural Plural form of title
	 */
	function set_title($title = '') {
		if ( is_scalar($title) )
			$this->title = strip_tags(trim($title));
	}

	/**
	 * Retrieve object title
	 */
	function get_title() {
		return $this->get_member_value('title', '','', 'current');
	}

	/**
	 * Set object description
	 * @param string $description Description for object
	 */
	function set_description($description = '') {
		$this->description = strip_tags(trim($description));
	}

	/**
	 * Retrieve object description
	 * @return string Object description
	 */
	function get_description() {
		$dir = 'current';
		return $this->get_member_value('description', '','', $dir);
		return $desc;
	}
	
	/**
	 * Sets multiple properties on field type at once
	 * @param array $properties Properties. Each element is an array containing the arguments to set a new property
	 * @return boolean TRUE if successful, FALSE otherwise
	 */
	function set_properties($properties) {
		if ( !is_array($properties) ) {
			return false;
		}
		// Normalize properties
		$properties = $this->remap_properties($properties);
		$properties = $this->sort_properties($properties);
		// Set Member properties
		foreach ( $properties as $prop => $val ) {
			if ( ( $m = 'set_' . $prop ) && method_exists($this, $m) ) {
				$this->{$m}($val);
				// Remove member property from array
				unset($properties[$prop]);
			}
		}
		
		// Filter properties
		$properties = $this->filter_properties($properties);
		// Set additional instance properties
		foreach ( $properties as $name => $val) {
			$this->set_property($name, $val);
		}
	}
	
	/**
	 * Remap properties based on $map
	 * @uses $map For determine how child properties should map to parent properties
	 * @uses SLB_Utlities::array_remap() to perform array remapping
	 * @param array $properties Associative array of properties
	 * @return array Remapped properties
	 */
	function remap_properties($properties) {
		// Return remapped properties
		return $this->util->array_remap($properties, $this->map);
	}
	
	/**
	 * Sort properties based on priority
	 * @uses this::property_priority
	 * @return array Sorted priorities
	 */
	function sort_properties($properties) {
		// Stop if sorting not necessary
		if ( empty($properties) || !is_array($properties) || empty($this->property_priority) || !is_array($this->property_priority) )
			return $properties;
		$props = array();
		foreach ( $this->property_priority as $prop ) {
			if ( !array_key_exists($prop, $properties) )
				continue;
			// Add to new array
			$props[$prop] = $properties[$prop];
			// Remove from old array
			unset($properties[$prop]);
		}
		// Append any remaining properties
		$props = array_merge($props, $properties);
		return $props;
	}
	
	/**
	 * Build properties array
	 * @param array $props Instance properties
	 * @param array $signature (optional) Default properties
	 * @return array Normalized properties
	 */
	function make_properties($props, $signature = array()) {
		$p = array();
		if ( is_array($props) ) {
			foreach ( $props as $prop ) {
				if ( is_array($prop) ) {
					$p = array_merge($prop, $p);
				}
			}
		}
		$props = $p;
		if ( is_array($signature) ) {
			$props = array_merge($signature, $props);
		}
		return $props;
	}
	
	function validate_id($id) {
		return ( is_scalar($id) && !empty($id) ) ? true : false;
	}
	
	function integrate_id($id) {
		return ( $this->validate_id($id) ) ? array('id' => $id) : array();
	}
	
	/**
	 * Filter property members
	 * @uses $property_filter to remove define members to remove from $properties
	 * @param array $props Properties
	 * @return array Filtered properties
	 */
	function filter_properties($props = array()) {
		return $this->util->array_filter_keys($props, $this->property_filter);
	}
	
	/**
	 * Add/Set a property on the field definition
	 * @param string $name Name of property
	 * @param mixed $value Default value for property
	 * @param string|array $group Group(s) property belongs to
	 * @return boolean TRUE if property is successfully added to field type, FALSE otherwise
	 */
	function set_property($name, $value = '', $group = null) {
		// Do not add if property name is not a string
		if ( !is_string($name) )
			return false;
		// Create property array
		$prop_arr = array();
		$prop_arr['value'] = $value;
		// Add to properties array
		$this->properties[$name] = $value;
		// Add property to specified groups
		if ( !empty($group) ) {
			$this->set_group_property($group, $name);
		}
		return true;
	}

	/**
	 * Retreives property from field type
	 * @param string $name Name of property to retrieve
	 * @return mixed Specified Property if exists (Default: Empty string)
	 */
	function get_property($name) {
		$val = $this->get_member_value('properties', $name);
		return $val;
	}
	
	/**
	 * Removes a property from item
	 * @param string $name Property ID
	 */
	function remove_property($name) {
		// Remove property
		if ( isset($this->properties[$name]) )
			unset($this->properties[$name]);
		// Remove from group
		foreach ( array_keys($this->property_groups) as $g ) {
			if ( isset($this->property_groups[$g][$name]) ) {
				unset($this->property_groups[$g][$name]);
				break;
			}
		}
	}

	/**
	 * Adds Specified Property to a Group
	 * @param string|array $group Group(s) to add property to
	 * @param string $property Property to add to group
	 */
	function set_group_property($group, $property) {
		if ( is_string($group) && isset($this->property_groups[$group][$property]) )
			return;
		if ( !is_array($group) ) {
			$group = array($group);
		}

		foreach ($group as $g) {
			$g = trim($g);
			// Initialize group if it doesn't already exist
			if ( !isset($this->property_groups[$g]) )
				$this->property_groups[$g] = array();

			// Add property to group
			$this->property_groups[$g][$property] = null;
		}
	}

	/**
	 * Retrieve property group
	 * @param string $group Group to retrieve
	 * @return array Array of properties in specified group
	 */
	function get_group($group) {
		return $this->get_member_value('property_groups', $group, array());
	}
	
	/**
	 * Save field data
	 * Child classes will define their own
	 * functionality for this method
	 * @return bool TRUE if save was successful (FALSE otherwise)
	 */
	function save() {
		return true;
	}
	
	/*-** Hooks **-*/
	
	/**
	 * Retrieve hooks added to object
	 * @return array Hooks
	 */
	function get_hooks() {
		return $this->get_member_value('hooks', '', array());
	}
	
	/**
	 * Add hook for object
	 * @see add_filter() for parameter defaults
	 * @param $tag
	 * @param $function_to_add
	 * @param $priority
	 * @param $accepted_args
	 */
	function add_hook($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
		// Create new array for tag (if not already set)
		if ( !isset($this->hooks[$tag]) )
			$this->hooks[$tag] = array();
		// Build Unique ID
		if ( is_string($function_to_add) )
			$id = $function_to_add;
		elseif ( is_array($function_to_add) && !empty($function_to_add) )
			$id = strval($function_to_add[count($function_to_add) - 1]);
		else
			$id = 'function_' . ( count($this->hooks[$tag]) + 1 ); 
		// Add hook
		$this->hooks[$tag][$id] = func_get_args();
	}
	
	/**
	 * Convenience method for adding an action for object
	 * @see add_filter() for parameter defaults
	 * @param $tag
	 * @param $function_to_add
	 * @param $priority
	 * @param $accepted_args
	 */
	function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
		$this->add_hook($tag, $function_to_add, $priority, $accepted_args);
	}
	
	/**
	 * Convenience method for adding a filter for object
	 * @see add_filter() for parameter defaults
	 * @param $tag
	 * @param $function_to_add
	 * @param $priority
	 * @param $accepted_args
	 */
	function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
		$this->add_hook($tag, $function_to_add, $priority, $accepted_args);
	}
	
	/*-** Dependencies **-*/
	
	/**
	 * Adds dependency to object
	 * @param string $type Type of dependency to add (script, style)
	 * @param array|string $context When dependency will be added (@see SLB_Utilities::get_action() for possible contexts)
	 * @see wp_enqueue_script for the following of the parameters
	 * @param $handle
	 * @param $src
	 * @param $deps
	 * @param $ver
	 * @param $ex
	 */
	function add_dependency($type, $context, $handle, $src = false, $deps = array(), $ver = false, $ex = false) {
		$args = func_get_args();
		// Remove type/context from arguments
		$args = array_slice($args, 2);

		// Set context
		if ( !is_array($context) ) {
			// Wrap single contexts in an array
			if ( is_string($context) )
				$context = array($context);
			else 
				$context = array();
		}
		// Add file to instance property
		if ( isset($this->{$type}) && is_array($this->{$type}) )
			$this->{$type}[$handle] = array('context' => $context, 'params' => $args);
	}
	
	/**
	 * Add script to object to be added in specified contexts
	 * @param array|string $context Array of contexts to add script to page
	 * @see wp_enqueue_script for the following of the parameters
	 * @param $handle
	 * @param $src
	 * @param $deps
	 * @param $ver
	 * @param $in_footer
	 */
	function add_script( $context, $handle, $src = false, $deps = array(), $ver = false, $in_footer = false ) {
		$args = func_get_args();
		// Add file type to front of arguments array
		array_unshift($args, 'scripts');
		call_user_func_array($this->m('add_dependency'), $args);
	}

	/**
	 * Retrieve script dependencies for object
	 * @return array Script dependencies
	 */
	function get_scripts() {
		return $this->get_member_value('scripts', '', array());
	}
	
	/**
	 * Add style to object to be added in specified contexts
	 * @param array|string $context Array of contexts to add style to page
	 * @see wp_enqueue_style for the following of the parameters
	 * @param $handle
	 * @param $src
	 * @param $deps
	 * @param $ver
	 * @param $in_footer
	 */
	function add_style( $handle, $src = false, $deps = array(), $ver = false, $media = false ) {
		$args = func_get_args();
		array_unshift($args, 'styles');
		call_user_func_array($this->m('add_dependency'), $args);
	}

	/**
	 * Retrieve Style dependencies for object
	 * @return array Style dependencies
	 */
	function get_styles() {
		return $this->get_member_value('styles', '', array());
	}
	
	/* Helpers */
	
	/**
	 * Format value based on specified context
	 * @param mixed $value Value to format
	 * @param string $context Current context
	 * @return mixed Formatted value
	 */
	function format($value, $context = '') {
		if ( is_scalar($context) && !empty($context) ) {
			$handler = 'format_' . trim(strval($context));
			// Only process if context is valid and has a handler
			if ( !empty($context) && method_exists($this, $handler) ) {
				// Pass value to handler
				$value = $this->{$handler}($value, $context);
			}
		}
		// Return formatted value
		return $value;
	}
	
	/**
	 * Format value for output in form field
	 * @param mixed $value Value to format
	 * @return mixed Formatted value
	 */
	function format_form($value) {
		if ( is_string($value) )
			$value = htmlspecialchars($value);
		return $value;
	}
	
	/**
	 * Final formatting before output
	 * Restores special characters, etc.
	 * @uses $special_chars
	 * @uses $special_chars_default
	 * @param mixed $value Pre-final field output
	 * @param string $context (Optional) Formatting context
	 * @return mixed Formatted value
	 */
	function format_final($value, $context = '') {
		if ( !is_string($value) )
			return $value;
		
		// Restore special chars
		return $this->restore_special_chars($value, $context);
	}
	
	function preserve_special_chars($value, $context = '') {
		if ( !is_string($value) )
			return $value;
		$specials = $this->get_special_chars();
		return str_replace(array_keys($specials), $specials, $value);
	}
	
	function restore_special_chars($value, $context = '') {
		if ( !is_string($value) )
			return $value;
		$specials = $this->get_special_chars();
		return str_replace($specials, array_keys($specials), $value);
	}
	
	/**
	 * Retrieve special characters/placeholders
	 * Merges defaults with class-specific characters
	 * @uses $special_chars
	 * @uses $special_chars_default
	 * @return array Special characters/placeholders
	 */
	function get_special_chars() {
		return wp_parse_args($this->special_chars, $this->special_chars_default);
	}
}