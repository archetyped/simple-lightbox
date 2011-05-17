<?php

require_once 'class.base.php';

/* Init */
$slb_content_utilities = new SLB_Content_Utilities();
//$slb_content_utilities->init();

/* Functions */

/**
 * Register handler for a placeholder in a content type template
 * Placeholders allow templates to be populated with dynamic content at runtime
 * Multiple handlers can be registered for a placeholder,
 * thus allowing custom handlers to override default processing, etc.
 * @uses SLB_Field_Type::register_placeholder_handler() to register placeholder
 * @param string $placeholder Placeholder identifier
 * @param callback $handler Callback function to use as handler for placeholder
 * @param int $priority (optional) Priority of registered handler (Default: 10)
 */
function slb_register_placeholder_handler($placeholder, $handler, $priority = 10) {
	SLB_Field_Type::register_placeholder_handler($placeholder, $handler, $priority);
}

/* Hooks */

//Default placeholder handlers
slb_register_placeholder_handler('all', array('SLB_Field_Type', 'process_placeholder_default'), 11);
slb_register_placeholder_handler('field_id', array('SLB_Field_Type', 'process_placeholder_id'));
slb_register_placeholder_handler('field_name', array('SLB_Field_Type', 'process_placeholder_name'));
slb_register_placeholder_handler('data', array('SLB_Field_Type', 'process_placeholder_data'));
slb_register_placeholder_handler('loop', array('SLB_Field_Type', 'process_placeholder_loop'));
slb_register_placeholder_handler('data_ext', array('SLB_Field_Type', 'process_placeholder_data_ext'));

/**
 * Fields - Base class
 * Core properties/methods for Content Type derivative classes
 * @package Simple Lightbox
 * @subpackage Fields
 * @author SM
 */
class SLB_Field_Base extends SLB_Base {

	/**
	 * Base class name
	 * @var string
	 */
	var $base_class = 'content_base';

	/**
	 * @var string Unique name
	 */
	var $id = '';

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
	 * Plural Title
	 * @var string
	 */
	var $title_plural = '';

	/**
	 * @var string Short description
	 */
	var $description = '';

	/**
	 * @var array Object Properties
	 */
	var $properties = array();

	/**
	 * Data for object
	 * May also contain data for nested objects
	 * @var mixed
	 */
	var $data = null;

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
	 * Legacy Constructor
	 */
	function SLB_Field_Base($id = '', $parent = null) {
		$this->__construct($id, $parent);
	}

	/**
	 * Constructor
	 */
	function __construct($id = '', $parent = null) {
		parent::__construct();
		$this->base_class = $this->add_prefix($this->base_class);
		$id = trim($id);
		$this->id = $id;
		if ( is_bool($parent) && $parent )
			$parent = $id;
		$this->set_parent($parent);
	}

	/* Getters/Setters */
	
	/**
	 * Checks if the specified path exists in the object
	 * @param array $path Path to check for
	 * @return bool TRUE if path exists in object, FALSE otherwise
	 */
	function path_isset($path = '') {
		//Stop execution if no path is supplied
		if ( empty($path) )
			return false;
		$args = func_get_args();
		$path = $this->util->build_path($args);
		$item =& $this;
		//Iterate over path and check if each level exists before moving on to the next
		for ($x = 0; $x < count($path); $x++) {
			if ( $this->util->property_exists($item, $path[$x]) ) {
				//Set $item as reference to next level in path for next iteration
				$item =& $this->util->get_property($item, $path[$x]);
				//$item =& $item[ $path[$x] ];
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
		$parent =& $this->get_parent();
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
	 */
	function get_member_value($member, $name = '', $default = '', $dir = 'parent') {
		//Check if path to member is supplied
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
		//Set defaults and prepare data
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
				//Get Parent value (recursive)
				$ex_val = ( 'parent' != $dir ) ? $this->get_container_value($member, $name, $default) : $this->get_parent_value($member, $name, $default);
				//Handle inheritance
				if ( is_array($val) ) {
					//Combine Arrays
					if ( is_array($ex_val) )
						$val = array_merge($ex_val, $val);
				} elseif ( $inherit !== false ) {
					//Replace placeholder with inherited string
					$val = str_replace($inherit_tag, $ex_val, $val);
				} else {
					//Default: Set parent value as value
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
	 * Retrieve value from data member
	 * @param bool $top (optional) Whether to traverse through the field hierarchy to get data for field (Default: TRUE)
	 * @return mixed Value at specified path
	 */
	function get_data($top = true) {
		$top = !!$top;
		$obj = $this;
		$obj_path = array($this);
		$path = array();
		//Iterate through hiearchy to get top-most object
		while ( !empty($obj) ) {
			$new = null;
			//Try to get caller first
			if ( method_exists($obj, 'get_caller') ) {
				$checked = true;
				$new = $obj->get_caller();
			}
			//Try to get container if no caller found
			if ( empty($new) && method_exists($obj, 'get_container') ) {
				$checked = true;
				$new = $obj->get_container();
			}

			$obj = $new;

			//Stop iteration
			if ( !empty($obj) ) {
				//Add object to path if it is valid
				$obj_path[] = $obj;
			}
		}

		//Check each object (starting with top-most) for matching data for current field

		//Reverse array
		$obj_path = array_reverse($obj_path);
		//Build path for data location
		foreach ( $obj_path as $obj ) {
			if ( $this->util->property_exists($obj, 'id') )
				$path[] = $obj->id;
		}

		//Iterate through objects
		while ( !empty($obj_path) ) {
			//Get next object
			$obj = array_shift($obj_path);
			//Shorten path
			array_shift($path);
			//Check for value in object and stop iteration if matching data found
			if ( ($val = $this->get_object_value($obj, 'data', $path, null, 'current')) && !is_null($val) ) {
				break;
			}
		}

		return $val;
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
	 * Retrieve base_class property
	 * @return string base_class property of current class/instance object
	 */
	function get_base_class() {
		$ret = '';
		if ( isset($this) )
			$ret = $this->base_class;
		else {
			$ret = SLB_Utilities::get_property(__CLASS__, 'base_class');
		}
		
		return $ret;
	}
	
	/**
	 * Sets parent object of current instance
	 * Parent objects must be the same object type as current instance
	 * @param string|object $parent Parent ID or reference
	 */
	function set_parent($parent) {
		if ( !empty($parent) ) {
			//Validate parent object
			if ( is_array($parent) )
				$parent =& $parent[0];
	
			//Retrieve reference object if ID was supplied
			if ( is_string($parent) ) {
				$parent = trim($parent);
				//Check for existence of parent
				$lookup = $this->base_class . 's';
				if ( isset($GLOBALS[$lookup][$parent]) ) {
					//Get reference to parent
					$parent =& $GLOBALS[$lookup][$parent];
				}
			}
			
			//Set reference to parent field type
			if ( is_a($parent, $this->base_class) ) {
				$this->parent =& $parent;
			}
		}
	}

	/**
	 * Retrieve field type parent
	 * @return SLB_Field_Type Reference to parent field
	 */
	function &get_parent() {
		return $this->parent;
	}

	/**
	 * Retrieves field ID
	 * @param string|SLB_Field|array $field (optional) Field object or ID of field or options array
	 * @return string|bool Field ID, FALSE if $field is invalid
	 */
	function get_id($field = null) {
		$ret = false;
		if ( ( !is_object($field) || !is_a($field, 'slb_field_type') ) && isset($this) ) {
			$field =& $this;
		}

		if ( is_a($field, SLB_Field_Type::get_base_class()) )
			$id = $field->id;

		if ( is_string($id) )
			$ret = trim($id);
		
		//Setup options
		$options_def = array('format' => null);
		 //Get options array
		$num_args = func_num_args();
		$options = ( $num_args > 0 && ( $last_arg = func_get_arg($num_args - 1) ) && is_array($last_arg) ) ? $last_arg : array();
		$options = wp_parse_args($options, $options_def); 
		//Check if field should be formatted
		if ( is_string($ret) && !empty($options['format']) ) {
			//Clear format option if it is an invalid value
			if ( is_bool($options['format']) || is_int($options['format']) )
				$options['format'] = null;
			//Setup values
			$wrap = array('open' => '[', 'close' => ']');
			if ( isset($options['wrap']) && is_array($options['wrap']) )
				$wrap = wp_parse_args($options['wrap'], $wrap);
			$wrap_trailing = ( isset($options['wrap_trailing']) ) ? !!$options['wrap_trailing'] : true;
			switch ( $options['format'] ) {
				case 'attr_id' :
					$wrap = (array('open' => '_', 'close' => '_'));
					$wrap_trailing = false;
					break;
			}
			$c = $field->get_caller();
			$field_id = array($ret);
			while ( !!$c ) {
				//Add ID of current field to array
				if ( isset($c->id) && is_a($c, $this->base_class) )
					$field_id[] = $c->id;
				$c = ( method_exists($c, 'get_caller') ) ? $c->get_caller() : null;
			}

			//Add prefix to ID value
			$field_id[] = 'attributes';

			//Convert array to string
			return $field->prefix . $wrap['open'] . implode($wrap['close'] . $wrap['open'], array_reverse($field_id)) . ( $wrap_trailing ? $wrap['close'] : '');
		}
		return $ret;
	}

	/**
	 * Set object title
	 * @param string $title Title for object
	 * @param string $plural Plural form of title
	 */
	function set_title($title = '', $plural = '') {
		$this->title = strip_tags(trim($title));
		if ( isset($plural) )
			$this->title_plural = strip_tags(trim($plural));
	}

	/**
	 * Retrieve object title
	 * @param bool $plural TRUE if plural title should be retrieved, FALSE otherwise (Default: FALSE)
	 */
	function get_title($plural = false) {
		$dir = 'current';
		//Singular
		if ( !$plural )
			return $this->get_member_value('title', '','', $dir);
		//Plural
		$title = $this->get_member_value('title_plural', '', '', $dir);
		if ( empty($title) ) {
			//Use singular title for plural base
			$title = $this->get_member_value('title', '', '', $dir);
			//Determine technique for making title plural
			//Get last letter
			if ( !empty($title) ) {
				$tail = substr($title, -1);
				switch ( $tail ) {
					case 's' :
						$title .= 'es';
						break;
					case 'y' :
						$title = substr($title, 0, -1) . 'ies';
						break;
					default :
						$title .= 's';
				}
			}
		}
		return $title;
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
		//Create new array for tag (if not already set)
		if ( !isset($this->hooks[$tag]) )
			$this->hooks[$tag] = array();
		//Build Unique ID
		if ( is_string($function_to_add) )
			$id = $function_to_add;
		elseif ( is_array($function_to_add) && !empty($function_to_add) )
			$id = strval($function_to_add[count($function_to_add) - 1]);
		else
			$id = 'function_' . ( count($this->hooks[$tag]) + 1 ); 
		//Add hook
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
		//Remove type/context from arguments
		$args = array_slice($args, 2);

		//Set context
		if ( !is_array($context) ) {
			//Wrap single contexts in an array
			if ( is_string($context) )
				$context = array($context);
			else 
				$context = array();
		}
		//Add file to instance property
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
		//Add file type to front of arguments array
		array_unshift($args, 'scripts');
		call_user_func_array(array(&$this, 'add_dependency'), $args);
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
		call_user_method_array('add_dependency', $this, $args);
	}

	/**
	 * Retrieve Style dependencies for object
	 * @return array Style dependencies
	 */
	function get_styles() {
		return $this->get_member_value('styles', '', array());
	}
}

/**
 * Content Type - Field Types
 * Stores properties for a specific field
 * @package Simple Lightbox
 * @subpackage Fields
 * @author SM
 */
class SLB_Field_Type extends SLB_Field_Base {
	/* Properties */

	/**
	 * Base class name
	 * @var string
	 */
	var $base_class = 'slb_field_type';

	/**
	 * @var array Array of Field types that make up current Field type
	 */
	var $elements = array();

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
	 * @var array Field type layouts
	 */
	var $layout = array();

	/**
	 * @var SLB_Field_Type Parent field type (reference)
	 */
	var $parent = null;

	/**
	 * Object that field is in
	 * @var SLB_Field|SLB_Field_Type|SLB_Content_Type
	 */
	var $container = null;

	/**
	 * Object that called field
	 * Used to determine field hierarchy/nesting
	 * @var SLB_Field|SLB_Field_Type|SLB_Content_Type
	 */
	var $caller = null;

	/**
	 * Legacy Constructor
	 */
	function SLB_Field_Type($id = '', $parent = null) {
		$this->__construct($id, $parent);
	}

	/**
	 * Constructor
	 */
	function __construct($id = '', $parent = null) {
		parent::__construct($id);

		$this->id = $id;
		$this->set_parent($parent);
	}

	/* Getters/Setters */

	/**
	 * Search for specified member value in field's container object (if exists)
	 * @param string $member Name of object member to search (e.g. properties, layout, etc.)
	 * @param string $name Value to retrieve from member
	 * @return mixed Member value if found (Default: empty string)
	 */
	function get_container_value($member, $name = '', $default = '') {
		$container =& $this->get_container();
		return $this->get_object_value($container, $member, $name, $default, 'container');
	}

	/**
	 * Search for specified member value in field's container object (if exists)
	 * @param string $member Name of object member to search (e.g. properties, layout, etc.)
	 * @param string $name Value to retrieve from member
	 * @return mixed Member value if found (Default: empty string)
	 */
	function get_caller_value($member, $name = '', $default = '') {
		$caller =& $this->get_caller();
		return $this->get_object_value($caller, $member, $name, $default, 'caller');
	}

	/**
	 * Sets reference to container object of current field
	 * Reference is cleared if no valid object is passed to method
	 * @param object $container
	 */
	function set_container(&$container) {
		if ( !empty($container) && is_object($container) ) {
			//Set as param as container for current field
			$this->container =& $container;
		} else {
			//Clear container member if argument is invalid
			$this->clear_container();
		}
	}

	/**
	 * Clears reference to container object of current field
	 */
	function clear_container() {
		$this->container = null;
	}

	/**
	 * Retrieves reference to container object of current field
	 * @return object Reference to container object
	 */
	function &get_container() {
		$ret = null;
		if ( $this->has_container() )
			$ret =& $this->container;
		return $ret;
	}

	/**
	 * Checks if field has a container reference
	 * @return bool TRUE if field is contained, FALSE otherwise
	 */
	function has_container() {
		return !empty($this->container);
	}

	/**
	 * Sets reference to calling object of current field
	 * Any existing reference is cleared if no valid object is passed to method
	 * @param object $caller Calling object
	 */
	function set_caller(&$caller) {
		if ( !empty($caller) && is_object($caller) )
			$this->caller =& $caller;
		else
			$this->clear_caller();
	}

	/**
	 * Clears reference to calling object of current field
	 */
	function clear_caller() {
		unset($this->caller);
	}

	/**
	 * Retrieves reference to caller object of current field
	 * @return object Reference to caller object
	 */
	function &get_caller() {
		$ret = null;
		if ( $this->has_caller() )
			$ret =& $this->caller;
		return $ret;
	}

	/**
	 * Checks if field has a caller reference
	 * @return bool TRUE if field is called by another field, FALSE otherwise
	 */
	function has_caller() {
		return !empty($this->caller);
	}

	/**
	 * Add/Set a property on the field definition
	 * @param string $name Name of property
	 * @param mixed $value Default value for property
	 * @param string|array $group Group(s) property belongs to
	 * @param boolean $uses_data Whether or not property uses data from the content item
	 * @return boolean TRUE if property is successfully added to field type, FALSE otherwise
	 */
	function set_property($name, $value = '', $group = null) {
		//Do not add if property name is not a string
		if ( !is_string($name) )
			return false;
		//Create property array
		$prop_arr = array();
		$prop_arr['value'] = $value;
		//Add to properties array
		$this->properties[$name] = $value;
		//Add property to specified groups
		if ( !empty($group) ) {
			$this->set_group_property($group, $name);
		}
		return true;
	}

	/**
	 * Sets multiple properties on field type at once
	 * @param array $properties Properties. Each element is an array containing the arguments to set a new property
	 * @return boolean TRUE if successful, FALSE otherwise 
	 */
	function set_properties($properties) {
		if ( !is_array($properties) )
			return false;
		foreach ( $properties as $name => $val) {
			$this->set_property($name, $val);
		}
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
			//Initialize group if it doesn't already exist
			if ( !isset($this->property_groups[$g]) )
				$this->property_groups[$g] = array();

			//Add property to group
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
	 * Sets an element for the field type
	 * @param string $name Name of element
	 * @param SLB_Field_Type $type Reference of field type to use for element
	 * @param array $properties Properties for element (passed as keyed associative array)
	 * @param string $id_prop Name of property to set $name to (e.g. ID, etc.)
	 */
	function set_element($name, $type, $properties = array(), $id_prop = 'id') {
		$name = trim(strval($name));
		if ( empty($name) )
			return false;
		//Create new field for element
		$el = new SLB_Field($name, $type);
		//Set container to current field instance
		$el->set_container($this);
		//Add properties to element
		$el->set_properties($properties);
		//Save element to current instance
		$this->elements[$name] =& $el;
	}

	/**
	 * Add a layout to the field
	 * @param string $name Name of layout
	 * @param string $value Layout text
	 */
	function set_layout($name, $value = '') {
		if ( !is_string($name) )
			return false;
		$name = trim($name);
		$this->layout[$name] = $value;
		return true;
	}

	/**
	 * Retrieve specified layout
	 * @param string $name Layout name
	 * @param bool $parse_nested (optional) Whether nested layouts should be expanded in retreived layout or not (Default: TRUE)
	 * @return string Specified layout text
	 */
	function get_layout($name = 'form', $parse_nested = true) {
		//Retrieve specified layout (use $name value if no layout by that name exists)
		$layout = $this->get_member_value('layout', $name, $name);

		//Find all nested layouts in current layout
		if ( !empty($layout) && !!$parse_nested ) {
			$ph = $this->get_placeholder_defaults();

			while ($ph->match = $this->parse_layout($layout, $ph->pattern_layout)) {
				//Iterate through the different types of layout placeholders
				foreach ($ph->match as $tag => $instances) {
					//Iterate through instances of a specific type of layout placeholder
					foreach ($instances as $instance) {
						//Get nested layout
						$nested_layout = $this->get_member_value($instance);

						//Replace layout placeholder with retrieved item data
						if ( !empty($nested_layout) )
							$layout = str_replace($ph->start . $instance['match'] . $ph->end, $nested_layout, $layout);
					}
				}
			}
		}

		return $layout;
	}

	/**
	 * Checks if specified layout exists
	 * Finds layout if it exists in current object or any of its parents
	 * @param string $layout Name of layout to check for
	 * @return bool TRUE if layout exists, FALSE otherwise
	 */
	function has_layout($layout) {
		$ret = false;
		if ( is_string($layout) && ($layout = trim($layout)) && !empty($layout) ) {
			$layout = $this->get_member_value('layout', $layout, false);
			if ( $layout !== false )
				$ret = true;
		}

		return $ret;
	}

	/**
	 * Checks if layout content is valid
	 * Layouts need to have placeholders to be valid
	 * @param string $layout_content Layout content (markup)
	 * @return bool TRUE if layout is valid, FALSE otherwise
	 */
	function is_valid_layout($layout_content) {
		$ph = $this->get_placeholder_defaults();
		return preg_match($ph->pattern_general, $layout_content);
	}

	/**
	 * Parse field layout with a regular expression
	 * @param string $layout Layout data
	 * @param string $search Regular expression pattern to search layout for
	 * @return array Associative array containing all of the regular expression matches in the layout data
	 * 	Array Structure:
	 *		root => placeholder tags
	 *				=> Tag instances (array)
	 *					'tag'			=> (string) tag name
	 *					'match' 		=> (string) placeholder match
	 *					'attributes' 	=> (array) attributes
	 */
	function parse_layout($layout, $search) {
		$ph_xml = '';
		$parse_match = '';
		$ph_root_tag = 'ph_root_element';
		$ph_start_xml = '<';
		$ph_end_xml = ' />';
		$ph_wrap_start = '<' . $ph_root_tag . '>';
		$ph_wrap_end = '</' . $ph_root_tag . '>';
		$parse_result = false;

		//Find all nested layouts in layout
		$match_value = preg_match_all($search, $layout, $parse_match, PREG_PATTERN_ORDER);

		if ($match_value !== false && $match_value > 0) {
			$parse_result = array();
			//Get all matched elements
			$parse_match = $parse_match[1];

			//Build XML string from placeholders
			foreach ($parse_match as $ph) {
				$ph_xml .= $ph_start_xml . $ph . $ph_end_xml . ' ';
			}
			$ph_xml = $ph_wrap_start . $ph_xml . $ph_wrap_end;
			//Parse XML data
			$ph_prs = xml_parser_create();
			xml_parser_set_option($ph_prs, XML_OPTION_SKIP_WHITE, 1);
			xml_parser_set_option($ph_prs, XML_OPTION_CASE_FOLDING, 0);
			$ret = xml_parse_into_struct($ph_prs, $ph_xml, $parse_result['values'], $parse_result['index']);
			xml_parser_free($ph_prs);

			//Build structured array with all parsed data

			unset($parse_result['index'][$ph_root_tag]);

			//Build structured array
			$result = array();
			foreach ($parse_result['index'] as $tag => $instances) {
				$result[$tag] = array();
				//Instances
				foreach ($instances as $instance) {
					//Skip instance if it doesn't exist in parse results
					if (!isset($parse_result['values'][$instance]))
						continue;

					//Stop processing instance if a previously-saved instance with the same options already exists
					foreach ($result[$tag] as $tag_match) {
						if ($tag_match['match'] == $parse_match[$instance - 1])
							continue 2;
					}

					//Init instance data array
					$inst_data = array();

					//Add Tag to array
					$inst_data['tag'] = $parse_result['values'][$instance]['tag'];

					//Add instance data to array
					$inst_data['attributes'] = (isset($parse_result['values'][$instance]['attributes'])) ? $inst_data['attributes'] = $parse_result['values'][$instance]['attributes'] : '';

					//Add match to array
					$inst_data['match'] = $parse_match[$instance - 1];

					//Add to result array
					$result[$tag][] = $inst_data;
				}
			}
			$parse_result = $result;
		}

		return $parse_result;
	}

	/**
	 * Retrieves default properties to use when evaluating layout placeholders
	 * @return object Object with properties for evaluating layout placeholders
	 */
	function get_placeholder_defaults() {
		$ph = new stdClass();
		$ph->start = '{';
		$ph->end = '}';
		$ph->reserved = array('ref' => 'ref_base');
		$ph->pattern_general = '/' . $ph->start . '([a-zA-Z0-9_].*?)' . $ph->end . '/i';
		$ph->pattern_layout = '/' . $ph->start . '([a-zA-Z0-9].*?\s+' . $ph->reserved['ref'] . '="layout.*?".*?)' . $ph->end . '/i';
		return $ph;
	}

	/**
	 * Builds HTML for a field based on its properties
	 * @param array $field Field properties (id, field, etc.)
	 * @param array data Additional data for current field
	 */
	function build_layout($layout = 'form', $data = null) {
		$out_default = '';

		/* Layout */

		//Get base layout
		$out = $this->get_layout($layout);

		//Only parse valid layouts
		if ( $this->is_valid_layout($out) ) {
			//Parse Layout
			$ph = $this->get_placeholder_defaults();

			//Search layout for placeholders
			while ( $ph->match = $this->parse_layout($out, $ph->pattern_general) ) {
				//Iterate through placeholders (tag, id, etc.)
				foreach ( $ph->match as $tag => $instances ) {
					//Iterate through instances of current placeholder
					foreach ( $instances as $instance ) {
						//Process value based on placeholder name
						$target_property = apply_filters('slb_process_placeholder_' . $tag, '', $this, $instance, $layout, $data);

						//Process value using default processors (if necessary)
						if ( '' == $target_property ) {
							$target_property = apply_filters('slb_process_placeholder', $target_property, $this, $instance, $layout, $data);
						}

						//Clear value if value not a string
						if ( !is_scalar($target_property) ) {
							$target_property = '';
						}
						//Replace layout placeholder with retrieved item data
						$out = str_replace($ph->start . $instance['match'] . $ph->end, $target_property, $out);
					}
				}
			}
		} else {
			$out = $out_default;
		}

		/* Return generated value */

		return $out;
	}

	/*-** Static Methods **-*/

	/**
	 * Returns indacator to use field data (in layouts, property values, etc.)
	 */
	function uses_data() {
		return '{data}';
	}

	/**
	 * Register a function to handle a placeholder
	 * Multiple handlers may be registered for a single placeholder
	 * Basically a wrapper function to facilitate adding hooks for placeholder processing
	 * @uses add_filter()
	 * @param string $placeholder Name of placeholder to add handler for (Using 'all' will set the function as a handler for all placeholders
	 * @param callback $handler Function to set as a handler
	 * @param int $priority (optional) Priority of handler
	 */
	function register_placeholder_handler($placeholder, $handler, $priority = 10) {
		if ( 'all' == $placeholder )
			$placeholder = '';
		else
			$placeholder = '_' . $placeholder;

		add_filter('slb_process_placeholder' . $placeholder, $handler, $priority, 5);
	}

	/**
	 * Default placeholder processing
	 * To be executed when current placeholder has not been handled by another handler
	 * @param string $ph_output Value to be used in place of placeholder
	 * @param SLB_Field $field Field containing placeholder
	 * @param array $placeholder Current placeholder
	 * @see SLB_Field::parse_layout for structure of $placeholder array
	 * @param string $layout Layout to build
	 * @param array $data Extended data for field
	 * @return string Value to use in place of current placeholder
	 */
	function process_placeholder_default($ph_output, $field, $placeholder, $layout, $data) {
		//Validate parameters before processing
		if ( empty($ph_output) && is_a($field, 'SLB_Field_Type') && is_array($placeholder) ) {
			//Build path to replacement data
			$ph_output = $field->get_member_value($placeholder);

			//Check if value is group (properties, etc.)
			//All groups must have additional attributes (beyond reserved attributes) that define how items in group are used
			if (is_array($ph_output)
				&& !empty($placeholder['attributes'])
				&& is_array($placeholder['attributes'])
				&& ($ph = $field->get_placeholder_defaults())
				&& $attribs = array_diff(array_keys($placeholder['attributes']), array_values($ph->reserved))
			) {
				/* Targeted property is an array, but the placeholder contains additional options on how property is to be used */

				//Find items matching criteria in $ph_output
				//Check for group criteria
				//TODO: Implement more robust/flexible criteria handling (2010-03-11: Currently only processes property groups)
				if ( 'properties' == $placeholder['tag'] && ($prop_group = $field->get_group($placeholder['attributes']['group'])) && !empty($prop_group) ) {
					/* Process group */
					$group_out = array();
					//Iterate through properties in group and build string
					foreach ( $prop_group as $prop_key => $prop_val ) {
						$group_out[] = $prop_key . '="' . $field->get_property($prop_key) . '"'; 
					}
					$ph_output = implode(' ', $group_out);
				}
			} elseif ( is_object($ph_output) && is_a($ph_output, $field->base_class) ) {
				/* Targeted property is actually a nested field */
				//Set caller to current field
				$ph_output->set_caller($field);
				//Build layout for nested element
				$ph_output = $ph_output->build_layout($layout);
			}
		}

		return $ph_output;
	}

	/**
	 * Build Field ID attribute
	 * @see SLB_Field_Type::process_placeholder_default for parameter descriptions
	 * @return string Placeholder output
	 */
	function process_placeholder_id($ph_output, $field, $placeholder, $layout, $data) {
		//Get attributes
		$args = wp_parse_args($placeholder['attributes'], array('format' => 'attr_id')); 
		return $field->get_id($args);
	}
	
	/**
	 * Build Field name attribute
	 * Name is formatted as an associative array for processing by PHP after submission
	 * @see SLB_Field_Type::process_placeholder_default for parameter descriptions
	 * @return string Placeholder output
	 */
	function process_placeholder_name($ph_output, $field, $placeholder, $layout, $data) {
		//Get attributes
		$args = wp_parse_args($placeholder['attributes'], array('format' => 'default')); 
		return $field->get_id($args);
	}

	/**
	 * Retrieve data for field
	 * @see SLB_Field_Type::process_placeholder_default for parameter descriptions
	 * @return string Placeholder output
	 */
	function process_placeholder_data($ph_output, $field, $placeholder, $layout) {
		$val = $field->get_data();
		if ( !is_null($val) ) {
			$ph_output = $val;
			$attr =& $placeholder['attributes'];
			//Get specific member in value (e.g. value from a specific field element)
			if ( isset($attr['element']) && is_array($ph_output) && ( $el = $attr['element'] ) && isset($ph_output[$el]) )
				$ph_output = $ph_output[$el];
			if ( isset($attr['format']) && 'display' == $attr['format'] )
				$ph_output = nl2br($ph_output);
		}

		//Return data
		return $ph_output;
	}

	/**
	 * Loops over data to build field output
	 * Options:
	 *  data		- Dot-delimited path in field that contains data to loop through
	 *  layout		- Name of layout to use for each data item in loop
	 *  layout_data	- Name of layout to use for data item that matches previously-saved field data
	 * @see SLB_Field_Type::process_placeholder_default for parameter descriptions
	 * @return string Placeholder output
	 */
	function process_placeholder_loop($ph_output, $field, $placeholder, $layout, $data) {
		//Setup loop options
		$attr_defaults = array (
								'layout'		=> '',
								'layout_data'	=> null,
								'data'			=> ''
								);

		$attr = wp_parse_args($placeholder['attributes'], $attr_defaults);

		if ( is_null($attr['layout_data']) ) {
			$attr['layout_data'] =& $attr['layout'];
		}

		//Get data for loop
		$path = explode('.', $attr['data']);
		$loop_data = $field->get_member_value($path);
		/*if ( isset($loop_data['value']) )
			$loop_data = $loop_data['value'];
		*/
		$out = array();

		//Get field data
		$data = $field->get_data();

		//Iterate over data and build output
		if ( is_array($loop_data) && !empty($loop_data) ) {
			foreach ( $loop_data as $value => $label ) {
				//Load appropriate layout based on field value
				$layout = ( ($data === 0 && $value === $data) xor $data == $value ) ? $attr['layout_data'] : $attr['layout'];
				//Stop processing if no valid layout is returned
				if ( empty($layout) )
					continue;
				//Prep extended field data
				$data_ext = array('option_value' => $value, 'option_text' => $label);
				$out[] = $field->build_layout($layout, $data_ext);
			}
		}

		//Return output
		return implode($out);
	}

	/**
	 * Returns specified value from extended data array for field
	 * @see SLB_Field_Type::process_placeholder_default for parameter descriptions
	 * @return string Placeholder output
	 */
	function process_placeholder_data_ext($ph_output, $field, $placeholder, $layout, $data) {
		if ( isset($placeholder['attributes']['id']) && ($key = $placeholder['attributes']['id']) && isset($data[$key]) ) {
			$ph_output = strval($data[$key]);
		}

		return $ph_output;
	}

}

class SLB_Field extends SLB_Field_Type {

}

class SLB_Content_Type extends SLB_Field_Base {

	/**
	 * Base class for instance objects
	 * @var string
	 */
	var $base_class = 'slb_content_type';

	/**
	 * Indexed array of fields in content type
	 * @var array
	 */
	var $fields = array();

	/**
	 * Associative array of groups in content type
	 * Key: Group name
	 * Value: object of group properties
	 *  > description string Group description
	 *  > location string Location of group on edit form
	 *  > fields array Fields in group
	 * @var array
	 */
	var $groups = array();

	/* Constructors */

	/**
	 * Legacy constructor
	 * @param string $id Content type ID
	 */
	function SLB_Content_Type($id, $parent = false, $properties = null) {
		$args = func_get_args();
		call_user_func_array(array(&$this, '__construct'), $args);
	}

	/**
	 * Class constructor
	 * @param string $id Content type ID
	 * @param string|bool $parent (optional) Parent to inherit properties from (Default: none)
	 * @param array $properties (optional) Properties to set for content type (Default: none)
	 */
	function __construct($id = '', $parent = null, $properties = null) {
		parent::__construct($id, $parent);
		
		//Set properties
		//TODO Iterate through additional arguments and set instance properties
	}
	
	/* Registration */
	
	/**
	 * Registers current content type w/CNR
	 */
	function register() {
		global $slb_content_utilities;
		$slb_content_utilities->register_content_type($this);
	}

	/* Getters/Setters */

	/**
	 * Adds group to content type
	 * Groups are used to display related fields in the UI 
	 * @param string $id Unique name for group
	 * @param string $title Group title
	 * @param string $description Short description of group's purpose
	 * @param string $location Where group will be displayed on post edit form (Default: main)
	 * @param array $fields (optional) ID's of existing fields to add to group
	 * @return object Group object
	 */
	function &add_group($id, $title = '', $description = '', $location = 'normal', $fields = array()) {
		//Create new group and set properties
		$id = trim($id);
		$this->groups[$id] =& $this->create_group($title, $description, $location);
		//Add fields to group (if supplied)
		if ( !empty($fields) && is_array($fields) )
			$this->add_to_group($id, $fields);
		return $this->groups[$id];
	}

	/**
	 * Remove specified group from content type
	 * @param string $id Group ID to remove
	 */
	function remove_group($id) {
		$id = trim($id);
		if ( $this->group_exists($id) ) {
			unset($this->groups[$id]);
		}
	}

	/**
	 * Standardized method to create a new field group
	 * @param string $title Group title (used in meta boxes, etc.)
	 * @param string $description Short description of group's purpose
	 * @param string $location Where group will be displayed on post edit form (Default: main)
	 * @return object Group object
	 */
	function &create_group($title = '', $description = '', $location = 'normal') {
		$group = new stdClass();
		$title = ( is_scalar($title) ) ? trim($title) : '';
		$group->title = $title;
		$description = ( is_scalar($description) ) ? trim($description) : '';
		$group->description = $description;
		$location = ( is_scalar($location) ) ? trim($location) : 'normal';
		$group->location = $location;
		$group->fields = array();
		return $group;
	}

	/**
	 * Checks if group exists
	 * @param string $id Group name
	 * @return bool TRUE if group exists, FALSE otherwise
	 */
	function group_exists($id) {
		$id = trim($id);
		//Check if group exists in content type
		return ( !is_null($this->get_member_value('groups', $id, null)) );
	}

	/**
	 * Adds field to content type
	 * @param string $id Unique name for field
	 * @param SLB_Field_Type|string $parent Field type that this field is based on
	 * @param array $properties (optional) Field properties
	 * @param string $group (optional) Group ID to add field to
	 * @return SLB_Field Reference to new field
	 */
	function &add_field($id, $parent, $properties = array(), $group = null) {
		//Create new field
		$id = trim(strval($id));
		$field = new SLB_Field($id);
		$field->set_parent($parent);
		$field->set_container($this);
		$field->set_properties($properties);

		//Add field to content type
		$this->fields[$id] =& $field;
		//Add field to group
		$this->add_to_group($group, $field->id);
		return $field;
	}

	/**
	 * Removes field from content type
	 * @param string|SLB_Field $field Object or Field ID to remove 
	 */
	function remove_field($field) {
		$field = SLB_Field_Type::get_id($field);
		if ( !$field )
			return false;

		//Remove from fields array
		//$this->fields[$field] = null;
		unset($this->fields[$field]);

		//Remove field from groups
		$this->remove_from_group($field);
	}

	/**
	 * Retrieve specified field in Content Type
	 * @param string $field Field ID
	 * @return SLB_Field Specified field
	 */
	function &get_field($field) {
		if ( $this->has_field($field) ) {
			$field = trim($field);
			$field = $this->get_member_value('fields', $field);
		} else {
			//Return empty field if no field exists
			$field =& new SLB_Field('');
		}
		return $field;
	}

	/**
	 * Checks if field exists in the content type
	 * @param string $field Field ID
	 * @return bool TRUE if field exists, FALSE otherwise
	 */
	function has_field($field) {
		return ( !is_string($field) || empty($field) || is_null($this->get_member_value('fields', $field, null)) ) ? false : true;
	}

	/**
	 * Adds field to a group in the content type
	 * Group is created if it does not already exist
	 * @param string|array $group ID of group (or group parameters if new group) to add field to
	 * @param string|array $fields Name or array of field(s) to add to group
	 */
	function add_to_group($group, $fields) {
		//Validate parameters
		$group_id = '';
		if ( !empty($group) ) {
			if ( !is_array($group) ) {
				$group = array($group, $group);
			}
			
			$group[0] = $group_id = trim(sanitize_title_with_dashes($group[0]));
		}
		if ( empty($group_id) || empty($fields) )
			return false;
		//Create group if it doesn't exist
		if ( !$this->group_exists($group_id) ) {
			call_user_func_array($this->m('add_group'), $group);
		}
		if ( ! is_array($fields) )
			$fields = array($fields);
		foreach ( $fields as $field ) {
			unset($fref);
			if ( ! $this->has_field($field) )
				continue;
			$fref =& $this->get_field($field);
			//Remove field from any other group it's in (fields can only be in one group)
			foreach ( array_keys($this->groups) as $group_name ) {
				if ( isset($this->groups[$group_name]->fields[$fref->id]) )
					unset($this->groups[$group_name]->fields[$fref->id]);
			}
			//Add reference to field in group
			$this->groups[$group_id]->fields[$fref->id] =& $fref;
		}
	}

	/**
	 * Remove field from a group
	 * If no group is specified, then field is removed from all groups
	 * @param string|SLB_Field $field Field object or ID of field to remove from group
	 * @param string $group (optional) Group ID to remove field from
	 */
	function remove_from_group($field, $group = '') {
		//Get ID of field to remove or stop execution if field invalid
		$field = SLB_Field_Type::get_id($field);
		if ( !$field )
			return false;

		//Remove field from group
		if ( !empty($group) ) {
			//Remove field from single group
			if ( ($group =& $this->get_group($group)) && isset($group->fields[$field]) ) {
				unset($group->fields[$field]);
			}
		} else {
			//Remove field from all groups
			foreach ( array_keys($this->groups) as $group ) {
				if ( ($group =& $this->get_group($group)) && isset($group->fields[$field]) ) {
					unset($group->fields[$field]);
				}
			}
		}
	}

	/**
	 * Retrieve specified group
	 * @param string $group ID of group to retrieve
	 * @return object Reference to specified group
	 */
	function &get_group($group) {
		$group = trim($group);
		//Create group if it doesn't already exist
		if ( ! $this->group_exists($group) )
			$this->add_group($group);

		return $this->get_member_value('groups', $group);
	}

	/**
	 * Retrieve all groups in content type
	 * @return array Reference to group objects
	 */
	function &get_groups() {
		return $this->get_member_value('groups');
	}

	/**
	 * Output fields in a group
	 * @param string $group ID of Group to output
	 * @return string Group output
	 */
	function build_group($group) {
		$out = array();
		$classnames = (object) array(
			'multi'		=> 'multi_field',
			'single'	=> 'single_field',
			'elements'	=> 'has_elements'
		);

		//Stop execution if group does not exist
		if ( $this->group_exists($group) && $group =& $this->get_group($group) ) {
			$group_fields = ( count($group->fields) > 1 ) ? $classnames->multi : $classnames->single . ( ( ( $fs = array_keys($group->fields) ) && ( $f =& $group->fields[$fs[0]] ) && ( $els = $f->get_member_value('elements', '', null) ) && !empty($els) ) ? '_' . $classnames->elements : '' );
			$classname = array('slb_attributes_wrap', $group_fields);
			$out[] = '<div class="' . implode(' ', $classname) . '">'; //Wrap all fields in group

			//Build layout for each field in group
			foreach ( array_keys($group->fields) as $field_id ) {
				$field =& $group->fields[$field_id];
				$field->set_caller($this);
				//Start field output
				$id = 'slb_field_' . $field->get_id();
				$out[] = '<div id="' . $id . '_wrap" class="slb_attribute_wrap">';
				//Build field layout
				$out[] = $field->build_layout();
				//end field output
				$out[] = '</div>';
				$field->clear_caller();
			}
			$out[] = '</div>'; //Close fields container
			//Add description if exists
			if ( !empty($group->description) )
				$out[] = '<p class="slb_group_description">' . $group->description . '</p>';
		}

		//Return group output
		return implode($out);
	}

	/**
	 * Set data for a field
	 * @param string|SLB_Field $field Reference or ID of Field to set data for
	 * @param mixed $value Data to set
	 */
	function set_data($field, $value = '') {
		if ( 1 == func_num_args() && is_array($field) )
			$this->data = $field;
		else {
			$field = SLB_Field_Type::get_id($field);
			if ( empty($field) )
				return false;
			$this->data[$field] = $value;
		}
	}

	/*-** Admin **-*/

	/**
	 * Adds meta boxes for post's content type
	 * Each group in content type is a separate meta box
	 * @param string $type Type of item meta boxes are being build for (post, page, link)
	 * @param string $context Location of meta box (normal, advanced, side)
	 * @param object $post Post object
	 */
	function admin_do_meta_boxes($type, $context, $post) {
		//Add post data to content type
		global $slb_content_utilities;
		$this->set_data($slb_content_utilities->get_item_data($post));

		//Get Groups
		$groups = array_keys($this->get_groups());
		$priority = 'default';
		//Iterate through groups and add meta box if it fits the context (location)
		foreach ( $groups as $group_id ) {
			$group =& $this->get_group($group_id);
			if ( $context == $group->location && count($group->fields) ) {
				//Format ID for meta box
				$meta_box_id = $this->prefix . '_group_' . $group_id;
				$group_args = array( 'group' => $group_id );
				add_meta_box($meta_box_id, $group->title, $this->m('admin_build_meta_box'), $type, $context, $priority, $group_args);
			}
		}
	}

	/**
	 * Outputs group fields for a meta box 
	 * @param object $post Post object
	 * @param array $box Meta box properties
	 */
	function admin_build_meta_box($post, $box) {
		//Stop execution if group not specified
		if ( !isset($box['args']['group']) )
			return false;

		//Get ID of group to output
		$group_id =& $box['args']['group'];

		$output = array();
		$output[] = '<div class="slb_group_wrap">';
		$output[] = $this->build_group($group_id);
		$output[] = '</div>';

		//Output group content to screen
		echo implode($output);
	}

	/**
	 * Retrieves type ID formatted as a meta value
	 * @return string
	 */
	function get_meta_value() {
		return serialize(array($this->id));
	}

}

/**
 * Utilities for Content Type functionality
 * @package Simple Lightbox
 * @subpackage Fields
 * @author SM
 */
class SLB_Content_Utilities extends SLB_Base {

	/**
	 * Array of hooks called
	 * @var array
	 */
	var $hooks_processed = array();
	
	/**
	 * Initialize content type functionality
	 */
	function init() {
		$this->register_hooks();
	}

	/**
	 * Registers hooks for content types
	 * @todo 2010-07-30: Check hooks for 3.0 compatibility
	 */
	function register_hooks() {
		//Register types
		add_action('init', $this->m('register_types'));
		add_action('init', $this->m('add_hooks'), 11);
		
		//Enqueue scripts for fields in current post type
		add_action('admin_enqueue_scripts', $this->m('enqueue_files'));
		
		//Add menus
		//add_action('admin_menu', $this->m('admin_menu'));

		//Build UI on post edit form
		add_action('do_meta_boxes', $this->m('admin_do_meta_boxes'), 10, 3);

		//Get edit link for items
		//add_filter('get_edit_post_link', $this->m('get_edit_item_url'), 10, 3);

		//add_action('edit_form_advanced', $this->m('admin_page_edit_form'));

		//Save Field data/Content type
		add_action('save_post', $this->m('save_item_data'), 10, 2);

		//Modify post query for content type compatibility
		add_action('pre_get_posts', $this->m('pre_get_posts'), 20);
	}

	/**
	 * Initialize fields and content types
	 */
	function register_types() {
		//Global variables
		global $slb_field_types, $slb_content_types;

		/* Field Types */

		//Base
		$base = new SLB_Field_Type('base');
		$base->set_description('Default Element');
		$base->set_property('tag', 'span');
		$base->set_property('class', '', 'attr');
		$base->set_layout('form', '<{tag} name="{field_name}" id="{field_id}" {properties ref_base="root" group="attr"} />');
		$base->set_layout('label', '<label for="{field_id}">{label}</label>');
		$base->set_layout('display', '{data format="display"}');
		$this->register_field($base);

		//Base closed
		$base_closed = new SLB_Field_Type('base_closed');
		$base_closed->set_parent('base');
		$base_closed->set_description('Default Element (Closed Tag)');
		//$base_closed->set_property('value');
		$base_closed->set_layout('form_start', '<{tag} id="{field_id}" name="{field_name}" {properties ref_base="root" group="attr"}>');
		$base_closed->set_layout('form_end', '</{tag}>');
		$base_closed->set_layout('form', '{form_start ref_base="layout"}{data}{form_end ref_base="layout"}');
		$this->register_field($base_closed);

		//Input
		$input = new SLB_Field_Type('input');
		$input->set_parent('base');
		$input->set_description('Default Input Element');
		$input->set_property('tag', 'input');
		$input->set_property('type', 'text', 'attr');
		$input->set_property('value', SLB_Field::uses_data(), 'attr');
		$this->register_field($input);

		//Text input
		$text = new SLB_Field_Type('text', 'input');
		$text->set_description('Text Box');
		$text->set_property('size', 15, 'attr');
		$text->set_property('label');
		$text->set_layout('form', '{label ref_base="layout"} {inherit}');
		$this->register_field($text);

		//Textarea
		$ta = new SLB_Field_Type('textarea', 'base_closed');
		$ta->set_property('tag', 'textarea');
		$ta->set_property('cols', 40, 'attr');
		$ta->set_property('rows', 3, 'attr');
		$this->register_field($ta);
		
		//Rich Text
		$rt = new SLB_Field_Type('richtext', 'textarea');
		$rt->set_property('class', 'theEditor {inherit}');
		$rt->set_layout('form', '<div class="rt_container">{inherit}</div>');
		$rt->add_action('admin_print_footer_scripts', 'wp_tiny_mce', 25);
		$this->register_field($rt);

		//Location
		$location = new SLB_Field_Type('location');
		$location->set_description('Geographic Coordinates');
		$location->set_element('latitude', 'text', array( 'size' => 3, 'label' => 'Latitude' ));
		$location->set_element('longitude', 'text', array( 'size' => 3, 'label' => 'Longitude' ));
		$location->set_layout('form', '<span>{latitude ref_base="elements"}</span>, <span>{longitude ref_base="elements"}</span>');
		$this->register_field($location);

		//Phone
		$phone = new SLB_Field_Type('phone');
		$phone->set_description('Phone Number');
		$phone->set_element('area', 'text', array( 'size' => 3 ));
		$phone->set_element('prefix', 'text', array( 'size' => 3 ));
		$phone->set_element('suffix', 'text', array( 'size' => 4 ));
		$phone->set_layout('form', '({area ref_base="elements"}) {prefix ref_base="elements"} - {suffix ref_base="elements"}');
		$this->register_field($phone);

		//Hidden
		$hidden = new SLB_Field_Type('hidden');
		$hidden->set_parent('input');
		$hidden->set_description('Hidden Field');
		$hidden->set_property('type', 'hidden');
		$this->register_field($hidden);

		//Span
		$span = new SLB_Field_Type('span');
		$span->set_description('Inline wrapper');
		$span->set_parent('base_closed');
		$span->set_property('tag', 'span');
		$span->set_property('value', 'Hello there!');
		$this->register_field($span);

		//Select
		$select = new SLB_Field_Type('select');
		$select->set_description('Select tag');
		$select->set_parent('base_closed');
		$select->set_property('tag', 'select');
		$select->set_property('tag_option', 'option');
		$select->set_property('options', array());
		$select->set_layout('form', '{label ref_base="layout"} {form_start ref_base="layout"}{loop data="properties.options" layout="option" layout_data="option_data"}{form_end ref_base="layout"}');
		$select->set_layout('option', '<{tag_option} value="{data_ext id="option_value"}">{data_ext id="option_text"}</{tag_option}>');
		$select->set_layout('option_data', '<{tag_option} value="{data_ext id="option_value"}" selected="selected">{data_ext id="option_text"}</{tag_option}>');		
		$this->register_field($select);

		//Enable plugins to modify (add, remove, etc.) field types
		do_action_ref_array('slb_register_field_types', array(&$slb_field_types));

		//Content Types
		
		//Enable plugins to add/remove content types
		do_action_ref_array('slb_register_content_types', array(&$slb_content_types));

		//Enable plugins to modify content types after they have all been registered
		do_action_ref_array('slb_content_types_registered', array(&$slb_content_types));
	}

	/**
	 * Add content type to global array of content types
	 * @param SLB_Content_Type $ct Content type to register
	 * 
	 * @global array $slb_content_types Content types array
	 */
	function register_content_type(&$ct) {
		//Add content type to CNR array
		if ( $this->is_content_type($ct) && !empty($ct->id) ) {
			global $slb_content_types;
			$slb_content_types[$ct->id] =& $ct;
		}
		//WP Post Type Registration
		global $wp_post_types;
		if ( !empty($ct->id) && !isset($wp_post_types[$ct->id]) )
			register_post_type($ct->id, $this->build_post_type_args($ct));
	}
	
	/**
	 * Generates arguments array for WP Post Type Registration
	 * @param SLB_Content_Type $ct Content type being registered
	 * @return array Arguments array
	 * @todo Enable custom taxonomies
	 */
	function build_post_type_args(&$ct) {
		//Setup labels
		
		//Build labels
		$labels = array (
			'name'				=> _( $ct->get_title(true) ),
			'singular_name'		=> _( $ct->get_title(false) ),
		);
		
		//Action labels
		$item_actions = array(
			'add_new'	=> 'Add new %s',
			'edit'		=> 'Edit %s',
			'new'		=> 'New %s',
			'view'		=> 'View %s',
			'search'	=> array('Search %s', true),
			'not_found'	=> array('No %s found', true, false),
			'not_found_in_trash'	=> array('No %s found in Trash', true, false)	
		);

		foreach ( $item_actions as $key => $val ) {
			$excluded = false;
			$plural = false;
			if ( is_array($val) ) {
				if ( count($val) > 1 && true == $val[1] ) {
					$plural = true;
				}
				if ( count($val) > 2 && false == $val[2] )
					$excluded = true;
				$val = $val[0];
			}
			$title = ( $plural ) ? $labels['name'] : $labels['singular_name'];
			if ( $excluded )
				$item = $key;
			else {
				$item = $key . '_item' . ( ( $plural ) ? 's' : '' );
			}
			$labels[$item] = sprintf($val, $title);
		}
		
		//Setup args
		$args = array (
			'labels'				=> $labels,
			'description'			=> $ct->get_description(),
			'public'				=> true,
			'capability_type'		=> 'post',
			'hierarchical'			=> false,
			'menu_position'			=> 5,
			'supports'				=> array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks', 'custom-fields', 'comments', 'revisions'),
			'taxonomies'			=> get_object_taxonomies('post')
		);
		
		return $args;
	}

	/**
	 * Add field type to global array of field types
	 * @param SLB_Field_Type $field Field to register
	 * 
	 * @global array $slb_field_types Field types array
	 */
	function register_field(&$field) {
		if ( $this->is_field($field) && !empty($field->id) ) {
			global $slb_field_types;
			$slb_field_types[$field->id] =& $field;
		}
	}

	/*-** Helpers **-*/

	/**
	 * Checks whether an object is a valid content type instance
	 * @param obj $ct Object to evaluate
	 * @return bool TRUE if object is a valid content type instance, FALSE otherwise
	 */
	function is_content_type(&$ct) {
		return is_a($ct, 'slb_content_type');
	}

	/**
	 * Checks whether an object is a valid field instance
	 * @param obj $field Object to evaluate
	 * @return bool TRUE if object is a valid field instance, FALSE otherwise
	 */
	function is_field(&$field) {
		return is_a($field, 'slb_field_type');
	}

	/*-** Handlers **-*/

	/**
	 * Modifies query parameters to include custom content types
	 * Adds custom content types to default post query so these items are retrieved as well
	 * @param WP_Query $q Reference to WP_Query object being used to perform posts query
	 * @see WP_Query for reference
	 */
	function pre_get_posts($q) {
		$pt =& $q->query_vars['post_type'];
		/* Do not continue processing if:
		 * > In admin section
		 * > Single object requested
		 * > More than one post type is already specified
		 * > Post type other than 'post' is supplied
		 */
		if ( is_admin()
		|| $q->is_singular
		|| ( is_array($pt)
			&& ( count($pt) > 1 
				|| 'post' != $pt[0] )
			)
		|| !in_array($pt, array('post', null))
		) {
			return false;
		}
		
		$default_types = $this->get_default_post_types();
		$custom_types = array_diff(array_keys($this->get_types()), $default_types);
		if ( !count($custom_types) )
			return false;
		//Wrap post type in array
		if ( empty($pt) || is_null($pt) )
			$pt = array('post');
		if ( !is_array($pt) )
			$pt = array($pt);
		//Add custom types to query
		foreach ( $custom_types as $type ) {
			$pt[] = $type;
		}
	}
	
	/**
	 * Retrieves current context (content type, action)
	 * @return array Content Type and Action of current request
	 */
	function get_context() {
		$post = false;
		if ( isset($GLOBALS['post']) && !is_null($GLOBALS['post']) )
			$post = $GLOBALS['post'];
		elseif ( isset($_REQUEST['post_id']) )
			$post = $_REQUEST['post_id'];
		elseif ( isset($_REQUEST['post']) )
			$post = $_REQUEST['post'];
		elseif ( isset($_REQUEST['post_type']) )
			$post = $_REQUEST['post_type'];
		//Get action
		$action = $this->util->get_action();
		if ( empty($post) )
			$post = $this->get_page_type();
		//Get post's content type
		$ct =& $this->get_type($post);
		
		return array(&$ct, $action);
	}
	
	/**
	 * Enqueues files for fields in current content type
	 * @param string $page Current context
	 */
	function enqueue_files($page = null) {
		list($ct, $action) = $this->get_context();
		$file_types = array('scripts' => 'script', 'styles' => 'style');
		//Get content type fields
		foreach ( $ct->fields as $field ) {
			//Enqueue scripts/styles for each field
			foreach ( $file_types as $type => $func_base ) {
				$deps = $field->{"get_$type"}();
				foreach ( $deps as $handle => $args ) {
					//Confirm context
					if ( in_array('all', $args['context']) || in_array($page, $args['context']) || in_array($action, $args['context']) ) {
						$this->enqueue_file($func_base, $args['params']);
					}
				}
			}
		}
	}
	
	/**
	 * Add plugin hooks for fields used in current request
	 */
	function add_hooks() {
		list($ct, $action) = $this->get_context();
		//Iterate through content type fields and add hooks from fields
		foreach ( $ct->fields as $field ) {
			//Iterate through hooks added to field
			$hooks = $field->get_hooks(); 
			foreach ( $hooks as $tag => $callback ) {
				//Iterate through function callbacks added to tag
				foreach ( $callback as $id => $args ) {
					//Check if hook/function was already processed
					if ( isset($this->hooks_processed[$tag][$id]) )
						continue;
					//Add hook/function to list of processed hooks 
					if ( !is_array($this->hooks_processed[$tag]) )
						$this->hooks_processed[$tag] = array($id => true);
					//Add hook to WP
					call_user_func_array('add_filter', $args);
				}
			}
		}
	}

	/**
	 * Enqueues files
	 * @param string $type Type of file to enqueue (script or style)
	 * @param array $args (optional) Arguments to pass to enqueue function
	 */
	function enqueue_file($type = 'script', $args = array()) {
		$func = 'wp_enqueue_' . $type;
		if ( function_exists($func) ) {
			call_user_func_array($func, $args);
		}
	}

	/**
	 * Add admin menus for content types
	 * @deprecated Not needed for 3.0+
	 */
	function admin_menu() {
		global $slb_content_types;

		$pos = 21;
		foreach ( $slb_content_types as $id => $type ) {
			if ( $this->is_default_post_type($id) )
				continue;
			$page = $this->get_admin_page_file($id);
			$callback = $this->m('admin_page');
			$access = 8;
			$pos += 1;
			$title = $type->get_title(true);
			if ( !empty($title) ) {
				//Main menu
				add_menu_page($type->get_title(true), $type->get_title(true), $access, $page, $callback, '', $pos);
				//Edit
				add_submenu_page($page, __('Edit ' . $type->get_title(true)), __('Edit'), $access, $page, $callback);
				$hook = get_plugin_page_hookname($page, $page);
				add_action('load-' . $hook, $this->m('admin_menu_load_plugin'));
				//Add
				$page_add = $this->get_admin_page_file($id, 'add');
				add_submenu_page($page, __('Add New ' . $type->get_title()), __('Add New'), $access, $page_add, $callback);
				$hook = get_plugin_page_hook($page_add, $page);
				add_action('load-' . $hook, $this->m('admin_menu_load_plugin'));
				//Hook for additional menus
				$menu_hook = 'slb_admin_menu_type';
				//Type specific
				do_action_ref_array($menu_hook . '_' . $id, array(&$type));
				//General
				do_action_ref_array($menu_hook, array(&$type));
			}
		}
	}

	/**
	 * Load data for plugin admin page prior to admin-header.php is loaded
	 * Useful for enqueueing scripts/styles, etc.
	 */
	function admin_menu_load_plugin() {
		//Get Action
		global $editing, $post, $post_ID, $p;
		$action = $this->util->get_action();
		if ( isset($_GET['delete_all']) )
			$action = 'delete_all';
		if ( isset($_GET['action']) && 'edit' == $_GET['action'] && ! isset($_GET['bulk_edit']))
			$action = 'manage';
		switch ( $action ) {
			case 'delete_all' :
			case 'edit' :
				//Handle bulk actions
				//Redirect to edit.php for processing

				//Build query string
				$qs = $_GET;
				unset($qs['page']);
				$edit_uri = admin_url('edit.php') . '?' . build_query($qs);
				wp_redirect($edit_uri);
				break;
			case 'edit-item' :
				wp_enqueue_script('admin_comments');
				enqueue_comment_hotkeys_js();
				//Get post being edited
				if ( empty($_GET['post']) ) {
					wp_redirect("post.php"); //TODO redict to appropriate manage page
					exit();
				}
				$post_ID = $p = (int) $_GET['post'];
				$post = get_post($post_ID);
				if ( !current_user_can('edit_post', $post_ID) )
					wp_die( __('You are not allowed to edit this item') );

				if ( $last = wp_check_post_lock($post->ID) ) {
					add_action('admin_notices', '_admin_notice_post_locked');
				} else {
					wp_set_post_lock($post->ID);
					$locked = true;
				}
				//Continue on to add case
			case 'add'	:
				$editing = true;
				wp_enqueue_script('autosave');
				wp_enqueue_script('post');
				if ( user_can_richedit() )
					wp_enqueue_script('editor');
				add_thickbox();
				wp_enqueue_script('media-upload');
				wp_enqueue_script('word-count');
				add_action( 'admin_print_footer_scripts', 'wp_tiny_mce', 25 );
				wp_enqueue_script('quicktags');
				wp_enqueue_script($this->add_prefix('edit_form'), $this->util->get_file_url('js/admin_edit_form.js'), array('jquery', 'postbox'), false, true);
				break;
			default		:
				wp_enqueue_script( $this->add_prefix('inline-edit-post') );
		}
	}

	/**
	 * Build admin page file name for the specified post type
	 * @param string|SLB_Content_Type $type Content type ID or object
	 * @param string $action Action to build file name for
	 * @param bool $sep_action Whether action should be a separate query variable (Default: false)
	 * @return string Admin page file name
	 */
	function get_admin_page_file($type, $action = '', $sep_action = false) {
		if ( isset($type->id) )
			$type = $type->id;
		$page = $this->add_prefix('post_type_' . $type);
		if ( !empty($action) ) {
			if ( $sep_action )
				$page .= '&action=';
			else
				$page .= '-';

			$page .= $action;
		}
		return $page;
	}

	/**
	 * Determine content type based on URL query variables
	 * Uses $_GET['page'] variable to determine content type
	 * @return string Content type of page (NULL if no type defined by page)
	 */
	function get_page_type() {
		$type = null;
		//Extract type from query variable
		if ( isset($_GET['page']) ) {
			$type = $_GET['page'];
			$prefix = $this->add_prefix('post_type_');
			//Remove plugin page prefix
			if ( ($pos = strpos($type, $prefix)) === 0 )
				$type = substr($type, strlen($prefix));
			//Remove action (if present)
			if ( ($pos = strrpos($type, '-')) && $pos !== false )
				$type = substr($type, 0, $pos);
		}
		return $type;
	}

	/**
	 * Populate administration page for content type
	 */
	function admin_page() {
		$prefix = $this->add_prefix('post_type_');
		if ( strpos($_GET['page'], $prefix) !== 0 )
			return false;

		//Get action
		$action = $this->util->get_action('manage');
		//Get content type
		$type =& $this->get_type($this->get_page_type());
		global $title, $parent_file, $submenu_file;
		$title = $type->get_title(true);
		//$parent_file = $prefix . $type->id;
		//$submenu_file = $parent_file;

		switch ( $action ) {
			case 'edit-item' :
			case 'add' :
				$this->admin_page_edit($type, $action);
				break;
			default :
				$this->admin_page_manage($type, $action);
		}
	}

	/**
	 * Queries content items for admin management pages
	 * Also retrieves available post status for specified content type
	 * @see wp_edit_posts_query
	 * @param SLB_Content_Type|string $type Content type instance or ID
	 * @return array All item statuses and Available item status
	 */
	function admin_manage_query($type = 'post') {
		global $wp_query;
		$q = array();
		//Get post type
		if ( ! is_a($type, 'SLB_Content_Type') ) {
			$type = $this->get_type($type);
		}
		$q = array('post_type' => $type->id);
		$g = $_GET;
		//Date
		$q['m']   = isset($g['m']) ? (int) $g['m'] : 0;
		//Category
		$q['cat'] = isset($g['cat']) ? (int) $g['cat'] : 0;
		$post_stati  = array(	//	array( adj, noun )
					'publish' => array(_x('Published', 'post'), __('Published posts'), _n_noop('Published <span class="count">(%s)</span>', 'Published <span class="count">(%s)</span>')),
					'future' => array(_x('Scheduled', 'post'), __('Scheduled posts'), _n_noop('Scheduled <span class="count">(%s)</span>', 'Scheduled <span class="count">(%s)</span>')),
					'pending' => array(_x('Pending Review', 'post'), __('Pending posts'), _n_noop('Pending Review <span class="count">(%s)</span>', 'Pending Review <span class="count">(%s)</span>')),
					'draft' => array(_x('Draft', 'post'), _x('Drafts', 'manage posts header'), _n_noop('Draft <span class="count">(%s)</span>', 'Drafts <span class="count">(%s)</span>')),
					'private' => array(_x('Private', 'post'), __('Private posts'), _n_noop('Private <span class="count">(%s)</span>', 'Private <span class="count">(%s)</span>')),
					'trash' => array(_x('Trash', 'post'), __('Trash posts'), _n_noop('Trash <span class="count">(%s)</span>', 'Trash <span class="count">(%s)</span>')),
				);

		$post_stati = apply_filters('post_stati', $post_stati);

		$avail_post_stati = get_available_post_statuses('post');

		//Status
		if ( isset($g['post_status']) && in_array( $g['post_status'], array_keys($post_stati) ) ) {
			$q['post_status'] = $g['post_status'];
			$q['perm'] = 'readable';
		} else {
			unset($q['post_status']);
		}

		//Order
		if ( isset($q['post_status']) && 'pending' === $q['post_status'] ) {
			$q['order'] = 'ASC';
			$q['orderby'] = 'modified';
		} elseif ( isset($q['post_status']) && 'draft' === $q['post_status'] ) {
			$q['order'] = 'DESC';
			$q['orderby'] = 'modified';
		} else {
			$q['order'] = 'DESC';
			$q['orderby'] = 'date';
		}

		//Pagination
		$posts_per_page = (int) get_user_option( 'edit_per_page', 0, false );
		if ( empty( $posts_per_page ) || $posts_per_page < 1 )
			$posts_per_page = 15;
		if ( isset($g['paged']) && (int) $g['paged'] > 1 )
			$q['paged'] = (int) $g['paged'];
		$q['posts_per_page'] = apply_filters( 'edit_posts_per_page', $posts_per_page );
		//Search
		$q[s] = ( isset($g['s']) ) ? $g[s] : '';
		$wp_query->query($q);

		return array($post_stati, $avail_post_stati);
	}

	/**
	 * Counts the number of items in the specified content type
	 * @see wp_count_posts
	 * @param SLB_Content_Type|string $type Content Type instance or ID
	 * @param string $perm Permission level for items (e.g. readable)
	 * @return array Associative array of item counts by post status (published, draft, etc.)
	 */
	function count_posts( $type, $perm = '' ) {
		global $wpdb;

		$user = wp_get_current_user();

		if ( !is_a($type, 'SLB_Content_Type') )
			$type = $this->get_type($type);
		$type_val = $type->get_meta_value();
		$type = $type->id;
		$cache_key = $type;

		//$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s";
		$query = "SELECT p.post_status, COUNT( * ) as num_posts FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.id WHERE m.meta_key = '" . $this->get_type_meta_key() . "' AND m.meta_value = '$type_val'";
		if ( 'readable' == $perm && is_user_logged_in() ) {
			//TODO enable check for custom post types "read_private_{$type}s"
			if ( !current_user_can("read_private_posts") ) {
				$cache_key .= '_' . $perm . '_' . $user->ID;
				$query .= " AND (p.post_status != 'private' OR ( p.post_author = '$user->ID' AND p.post_status = 'private' ))";
			}
		}
		$query .= ' GROUP BY p.post_status';

		$count = wp_cache_get($cache_key, 'counts');
		if ( false !== $count )
			return $count;

		$count = $wpdb->get_results( $wpdb->prepare( $query, $type ), ARRAY_A );

		$stats = array( 'publish' => 0, 'private' => 0, 'draft' => 0, 'pending' => 0, 'future' => 0, 'trash' => 0 );
		foreach( (array) $count as $row_num => $row ) {
			$stats[$row['post_status']] = $row['num_posts'];
		}

		$stats = (object) $stats;
		wp_cache_set($cache_key, $stats, 'counts');

		return $stats;
	}

	/**
	 * Builds management page for items of a specific custom content type
	 * @param SLB_Content_Type $type Content Type to manage
	 * @param string $action Current action
	 * 
	 * @global string $title
	 * @global string $parent_file
	 * @global string $plugin_page
	 * @global string $page_hook
	 * @global WP_User $current_user
	 * @global WP_Query $wp_query
	 * @global wpdb $wpdb
	 * @global WP_Locale $wp_locale
	 */
	function admin_page_manage($type, $action) {
		if ( !current_user_can('edit_posts') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		global $title, $parent_file, $plugin_page, $page_hook, $current_user, $wp_query, $wpdb, $wp_locale;
		$title = __('Edit ' . $type->get_title(true));
		$admin_path = ABSPATH . 'wp-admin/'; 

		//Pagination
		if ( ! isset($_GET['paged']) )
			$_GET['paged'] = 1;

		$add_url = $this->get_admin_page_url($type->id, 'add');
		$is_trash = isset($_GET['post_status']) && $_GET['post_status'] == 'trash';
		//User posts
		$user_posts = false;
		if ( !current_user_can('edit_others_posts') ) {
			$user_posts_count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(1) FROM $wpdb->posts p JOIN $wpdb->postmeta m ON m.post_id = p.id WHERE m.meta_key = '_slb_post_type' AND m.meta_value = %s AND p.post_status != 'trash' AND p.post_author = %d", $type->get_meta_value(), $current_user->ID) );
			$user_posts = true;
			if ( $user_posts_count && empty($_GET['post_status']) && empty($_GET['all_posts']) && empty($_GET['author']) )
				$_GET['author'] = $current_user->ID;
		}
		//Get content type items
		list($post_stati, $avail_post_stati) = $this->admin_manage_query($type->id);
		?>
		<div class="wrap">
		<?php screen_icon('edit'); ?>
		<h2><?php echo esc_html( $title ); ?> <a href="<?php echo $add_url; ?>" class="button add-new-h2"><?php echo esc_html_x('Add New', 'post'); ?></a> <?php
		if ( isset($_GET['s']) && $_GET['s'] )
			printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', esc_html( get_search_query() ) ); ?>
		</h2>
		<?php /* Action messages here: saved, trashed, etc. */ ?>
		<form id="posts-filter" action="<?php echo admin_url('admin.php'); ?>" method="get">
		<?php if ( isset($_GET['page']) ) { ?>
		<input type="hidden" name="page" id="page" value="<?php esc_attr_e($_GET['page']); ?>" />
		<?php } ?>
		<ul class="subsubsub">
		<?php 
		/* Status links */
		if ( empty($locked_post_status) ) :
			$status_links = array();
			$num_posts = $this->count_posts($type, 'readable');
			$class = '';
			$allposts = '';
			$curr_page = $_SERVER['PHP_SELF'] . '?page=' . $_GET['page'];
			if ( $user_posts ) {
				if ( isset( $_GET['author'] ) && ( $_GET['author'] == $current_user->ID ) )
					$class = ' class="current"';
				$status_links[] = "<li><a href='$curr_page&author=$current_user->ID'$class>" . sprintf( _nx( 'My Posts <span class="count">(%s)</span>', 'My Posts <span class="count">(%s)</span>', $user_posts_count, 'posts' ), number_format_i18n( $user_posts_count ) ) . '</a>';
				$allposts = '?all_posts=1';
			}

			$total_posts = array_sum( (array) $num_posts ) - $num_posts->trash;
			$class = empty($class) && empty($_GET['post_status']) ? ' class="current"' : '';
			$status_links[] = "<li><a href='$curr_page{$allposts}'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_posts, 'posts' ), number_format_i18n( $total_posts ) ) . '</a>';

			foreach ( $post_stati as $status => $label ) {
				$class = '';

				if ( !in_array( $status, $avail_post_stati ) )
					continue;

				if ( empty( $num_posts->$status ) )
					continue;

				if ( isset($_GET['post_status']) && $status == $_GET['post_status'] )
					$class = ' class="current"';

				$status_links[] = "<li><a href='$curr_page&post_status=$status'$class>" . sprintf( _n( $label[2][0], $label[2][1], $num_posts->$status ), number_format_i18n( $num_posts->$status ) ) . '</a>';
			}
			echo implode( " |</li>\n", $status_links ) . '</li>';
			unset( $status_links );
		endif;
		?>
		</ul>
		<p class="search-box">
			<label class="screen-reader-text" for="post-search-input"><?php _e( 'Search Posts' ); ?>:</label>
			<input type="text" id="post-search-input" name="s" value="<?php the_search_query(); ?>" />
			<input type="submit" value="<?php esc_attr_e( 'Search Posts' ); ?>" class="button" />
		</p>
		<?php 
		if ( have_posts() ) {
		?>
		<div class="tablenav">
		<?php 
		$page_links = paginate_links( array(
			'base'		=> add_query_arg( 'paged', '%#%' ),
			'format'	=> '',
			'prev_text'	=> __('&laquo;'),
			'next_text'	=> __('&raquo;'),
			'total'		=> $wp_query->max_num_pages,
			'current'	=> $_GET['paged']
		));
		?>
		<div class="alignleft actions">
		<select name="action">
			<option value="-1" selected="selected"><?php _e('Bulk Actions'); ?></option>
			<?php if ( $is_trash ) { ?>
			<option value="untrash"><?php _e('Restore'); ?></option>
			<?php } else { ?>
			<option value="edit"><?php _e('Edit'); ?></option>
			<?php } if ( $is_trash || !EMPTY_TRASH_DAYS ) { ?>
			<option value="delete"><?php _e('Delete Permanently'); ?></option>
			<?php } else { ?>
			<option value="trash"><?php _e('Move to Trash'); ?></option>
			<?php } ?>
		</select>
		<input type="submit" value="<?php esc_attr_e('Apply'); ?>" name="doaction" id="doaction" class="button-secondary action" />
		<?php wp_nonce_field('bulk-posts'); ?>

		<?php // view filters
		if ( !is_singular() ) {
		$arc_query = "SELECT DISTINCT YEAR(post_date) AS yyear, MONTH(post_date) AS mmonth FROM $wpdb->posts p JOIN $wpdb->postmeta m ON m.post_id = p.ID WHERE m.meta_key = '" . $this->get_type_meta_key() . "' AND m.meta_value = '" . $type->get_meta_value() . "' ORDER BY post_date DESC";

		$arc_result = $wpdb->get_results( $arc_query );

		$month_count = count($arc_result);

		if ( $month_count && !( 1 == $month_count && 0 == $arc_result[0]->mmonth ) ) {
		$m = isset($_GET['m']) ? (int)$_GET['m'] : 0;
		?>
		<select name='m'>
		<option<?php selected( $m, 0 ); ?> value='0'><?php _e('Show all dates'); ?></option>
		<?php
		foreach ($arc_result as $arc_row) {
			if ( $arc_row->yyear == 0 )
				continue;
			$arc_row->mmonth = zeroise( $arc_row->mmonth, 2 );

			if ( $arc_row->yyear . $arc_row->mmonth == $m )
				$default = ' selected="selected"';
			else
				$default = '';

			echo "<option$default value='" . esc_attr("$arc_row->yyear$arc_row->mmonth") . "'>";
			echo $wp_locale->get_month($arc_row->mmonth) . " $arc_row->yyear";
			echo "</option>\n";
		}
		?>
		</select>
		<?php } 

		$dropdown_options = array('show_option_all' => __('View all categories'), 'hide_empty' => 0, 'hierarchical' => 1,
			'show_count' => 0, 'orderby' => 'name', 'selected' => $cat);
		wp_dropdown_categories($dropdown_options);
		do_action('restrict_manage_posts');
		?>
		<input type="submit" id="post-query-submit" value="<?php esc_attr_e('Filter'); ?>" class="button-secondary" />
		<?php } 

		if ( $is_trash && current_user_can('edit_others_posts') ) { ?>
		<input type="submit" name="delete_all" id="delete_all" value="<?php esc_attr_e('Empty Trash'); ?>" class="button-secondary apply" />
		<?php } ?>
		</div>

		<?php if ( $page_links ) { ?>
		<div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s', 
			number_format_i18n( ( $_GET['paged'] - 1 ) * $wp_query->query_vars['posts_per_page'] + 1 ),
			number_format_i18n( min( $_GET['paged'] * $wp_query->query_vars['posts_per_page'], $wp_query->found_posts ) ),
			number_format_i18n( $wp_query->found_posts ),
			$page_links
		); echo $page_links_text; ?></div>
		<?php } //page links ?>
		<div class="clear"></div>
		</div>
		<?php
			include ($admin_path . 'edit-post-rows.php');
		} else { //have_posts() ?>
		<div class="clear"></div>
		<p><?php
		if ( $is_trash )
			_e('No posts found in the trash');
		else
			_e('No posts found');
		?></p>
		<?php } ?>
		</form>
		<?php inline_edit_row('post'); ?>
		<div id="ajax-response"></div>
		<br class="clear" />
		</div>
		<?php
	}

	/**
	 * Build admin edit page for custom type item
	 * @param SLB_Content_Type $type Content type being edited
	 * @param string $action Current action (add, edit, manage, etc.)
	 */
	function admin_page_edit($type, $action) {
		global $title, $hook_suffix, $parent_file, $screen_layout_columns, $post, $post_ID, $p;
		$screen_layout_columns = 2;
		//TODO Add default icon for content type
		$parent_file = 'edit.php'; //Makes screen_icon() use edit icon on post edit form
		switch ( $action ) {
			case 'edit-item' :
				$title = 'Edit';
				$post = get_post_to_edit($post_ID);
				break;
			default :
				$title = 'Add New';
				$post = get_default_post_to_edit();
				break;	
		}
		$title = __($title . ' ' . $type->get_title());
		$admin_path = ABSPATH . 'wp-admin/';
		include ($admin_path . 'edit-form-advanced.php');
	}

	/**
	 * Adds hidden field declaring content type on post edit form
	 * @deprecated no longer needed for WP 3.0+
	 */
	function admin_page_edit_form() {
		global $post, $plugin_page;
		if ( empty($post) || !$post->ID ) {
			$type = $this->get_type($post);
			if ( ! empty($type) && ! empty($type->id) ) {
			?>
			<input type="hidden" name="cnr[content_type]" id="cnr[content_type]" value="<?php echo $type->id; ?>" />
			<?php
			}
		}
	}

	/**
	 * Adds meta boxes for post's content type
	 * Each group in content type is a separate meta box
	 * @param string $type Type of item meta boxes are being build for (post, page, link)
	 * @param string $context Location of meta box (normal, advanced, side)
	 * @param object $post Post object
	 */
	function admin_do_meta_boxes($type, $context, $post) {
		//Validate $type. Should be 'post','page', or a custom post type for our purposes
		if ( in_array($type, array_merge(array_keys($this->get_types()), array('post', 'page'))) ) {
			//Get content type definition
			$ct =& $this->get_type($post);
			//Pass processing to content type instance
			$ct->admin_do_meta_boxes($type, $context, $post);
		}
	}

	/**
	 * Saves field data submitted for current post
	 * @param int $post_id ID of current post
	 * @param object $post Post object
	 */
	function save_item_data($post_id, $post) {
		if ( empty($post_id) || empty($post) )
			return false;
		//Save field data
		if ( isset($_POST['cnr']['attributes']) ) {  
			$prev_data = $this->get_item_data($post_id);

			//Get current field data
			$curr_data = $_POST['cnr']['attributes'];

			//Merge arrays together (new data overwrites old data)
			if ( is_array($prev_data) && is_array($curr_data) ) {
				$curr_data = array_merge($prev_data, $curr_data);
			}

			//Save to database
			update_post_meta($post_id, $this->get_fields_meta_key(), $curr_data);
		}

		//Save content type
		if ( isset($_POST['cnr']['content_type']) ) {
			$type = $_POST['cnr']['content_type'];
			$saved_type = get_post_meta($post_id, $this->get_type_meta_key(), true);
			if ( is_array($saved_type) )
				$saved_type = implode($saved_type);
			if ( $type != $saved_type ) {
				//Continue processing if submitted content type is different from previously-saved content type (or no type was previously set)
				update_post_meta($post_id, $this->get_type_meta_key(), array($type));
			}
		}
	}

	/*-** Helpers **-*/

	/**
	 * Get array of default post types
	 * @return array Default post types
	 */
	function get_default_post_types() {
		return array('post', 'page', 'attachment', 'revision', 'nav_menu');
	}

	/**
	 * Checks if post's post type is a standard WP post type
	 * @param mixed $post_type Post type (default) or post ID/object to evaluate
	 * @see SLB_Content_Utilities::get_type() for possible parameter values
	 * @return bool TRUE if post is default type, FALSE if it is a custom type
	 */
	function is_default_post_type($post_type) {
		if ( !is_string($post_type) ) {
			$post_type = $this->get_type($post_type);
			$post_type = $post_type->id;
		}
		return in_array($post_type, $this->get_default_post_types());
	}

	/**
	 * Checks if specified content type has been defined
	 * @param string|SLB_Content_Type $type Content type ID or object
	 * @return bool TRUE if content type exists, FALSE otherwise
	 * 
	 * @uses array $slb_content_types
	 */
	function type_exists($type) {
		global $slb_content_types;
		if ( ! is_scalar($type) ) {
			if ( is_a($type, 'SLB_Content_Type') )
				$type = $type->id;
			else
				$type = null;
		}
		return ( isset($slb_content_types[$type]) );
	}

	/**
	 * Retrieves content type definition for specified content item (post, page, etc.)
	 * If content type does not exist, a new instance object will be created and returned
	 * > New content types are automatically registered (since we are looking for registered types when using this method)
	 * @param string|object $item Post object, or item type (string)
	 * @return SLB_Content_Type Reference to matching content type, empty content type if no matching type exists
	 * 
	 * @uses array $slb_content_types
	 */
	function &get_type($item) {
		//Return immediately if $item is a content type instance
		if ( is_a($item, 'SLB_Content_Type') )
			return $item;

		$type = null;

		if ( is_string($item) )
			$type = $item;

		if ( !$this->type_exists($type) ) {
			$post = $item;

			//Check if $item is a post (object or ID)
			if ( $this->util->check_post($post) && isset($post->post_type) ) {
				$type = $post->post_type;
			}
		}
		global $slb_content_types;
		if ( $this->type_exists($type) ) {
			//Retrieve content type from global array
			$type =& $slb_content_types[$type];
		} else {
			//Create new empty content type if it does not already exist
			$type =& new SLB_Content_Type($type);
			//Automatically register newly initialized content type if it extends an existing WP post type
			if ( $this->is_default_post_type($type->id) )
				$type->register();
		}

		return $type;
	}
	
	/**
	 * Retrieve content types
	 * @return Reference to content types array
	 */
	function &get_types() {
		return $GLOBALS['slb_content_types'];
	}

	/**
	 * Retrieve meta key for post fields
	 * @return string Fields meta key
	 */
	function get_fields_meta_key() {
		return $this->make_meta_key('fields');
	}

	/**
	 * Retrieve meta key for post type
	 * @return string Post type meta key
	 */
	function get_type_meta_key() {
		return $this->make_meta_key('post_type');
	}

	/**
	 * Checks if post contains specified field data
	 * @param Object $post (optional) Post to check data for
	 * @param string $field (optional) Field ID to check for
	 * @return bool TRUE if data exists, FALSE otherwise
	 */
	function has_item_data($item = null, $field = null) {
		$ret = $this->get_item_data($item, $field, 'raw', null);
		if ( is_scalar($ret) )
			return ( !empty($ret) || $ret === 0 );
		if ( is_array($ret) ) {
			foreach ( $ret as $key => $val ) {
				if ( !empty($val) || $ret === 0 )
					return true;
			}
		}
		
		return false;
	}

	/**
	 * Retrieve specified field data from content item (e.g. post)
	 * Usage Examples:
	 * get_item_data($post_id, 'field_id')
	 *  - Retrieves field_id data from global $post object
	 *  - Field data is formatted using 'display' layout of field
	 *  
	 * get_item_data($post_id, 'field_id', 'raw')
	 *  - Retrieves field_id data from global $post object
	 *  - Raw field data is returned (no formatting)
	 *  
	 * get_item_data($post_id, 'field_id', 'display', $post_id)
	 *  - Retrieves field_id data from post matching $post_id
	 *  - Field data is formatted using 'display' layout of field
	 *  
	 * get_item_data($post_id, 'field_id', null)
	 *  - Retrieves field_id data from post matching $post_id
	 *  - Field data is formatted using 'display' layout of field
	 *    - The default layout is used when no valid layout is specified
	 *
	 * get_item_data($post_id)
	 *  - Retrieves full data array from post matching $post_id
	 *  
	 * @param int|object $item(optional) Content item to retrieve field from (Default: null - global $post object will be used)
	 * @param string $field ID of field to retrieve
	 * @param string $layout(optional) Layout to use when returning field data (Default: display)
	 * @param array $attr (optional) Additional attributes to pass along to field object (e.g. for building layout, etc.)
	 * @see SLB_Field_Type::build_layout for more information on attribute usage
	 * @return mixed Specified field data 
	 */
	function get_item_data($item = null, $field = null, $layout = null, $default = '', $attr = null) {
		$ret = $default;

		//Get item
		$item = get_post($item);

		if ( !isset($item->ID) )
			return $ret;

		//Get item data
		$data = get_post_meta($item->ID, $this->get_fields_meta_key(), true);

		//Get field data

		//Set return value to data if no field specified
		if ( empty($field) || !is_string($field) )
			$ret = $data;
		//Stop if no valid field specified
		if ( !isset($data[$field]) ) {
			//TODO Check $item object to see if specified field exists (e.g. title, post_status, etc.)
			return $ret;
		}

		$ret = $data[$field];

		//Initialize layout value
		$layout_def = 'display';

		if ( !is_scalar($layout) || empty($layout) )
			$layout = $layout_def;

		$layout = strtolower($layout);

		//Check if raw data requested
		if ( 'raw' == $layout )
			return $ret;

		/* Build specified layout */

		//Get item's content type
		$ct =& $this->get_type($item);
		$ct->set_data($data);

		//Get field definition
		$fdef =& $ct->get_field($field);

		//Validate layout
		if ( !$fdef->has_layout($layout) )
			$layout = $layout_def;
		
		//Build layout
		$fdef->set_caller($ct);
		$ret = $fdef->build_layout($layout, $attr);
		$fdef->clear_caller();

		//Return formatted value
		return $ret;
	}

	/**
	 * Prints an item's field data
	 * @see SLB_Content_Utilities::get_item_data() for more information
	 * @param int|object $item(optional) Content item to retrieve field from (Default: null - global $post object will be used)
	 * @param string $field ID of field to retrieve
	 * @param string $layout(optional) Layout to use when returning field data (Default: display)
	 * @param mixed $default (optional) Default value to return in case of errors, etc.
	 * @param array $attr Additional attributes to pass to field
	 */
	function the_item_data($item = null, $field = null, $layout = null, $default = '', $attr = null) {
		echo apply_filters('slb_the_item_data', $this->get_item_data($item, $field, $layout, $default, $attr), $item, $field, $layout, $default, $attr);
	}

	/**
	 * Build Admin URL for specified post type
	 * @param string|SLB_Content_Type $type Content type ID or object
	 * @param string $action Action to build URL for
	 * @param bool $sep_action Whether action should be a separate query variable (Default: false)
	 * @return string Admin page URL
	 */
	function get_admin_page_url($type, $action = '', $sep_action = false) {
		$url = admin_url('admin.php');
		$url .= '?page=' . $this->get_admin_page_file($type, $action, $sep_action);
		return $url; 
	}

	function get_edit_item_url($edit_url, $item_id, $context) {
		//Get post type
		$type = $this->get_type($item_id);
		if (  ! $this->is_default_post_type($type->id) && $this->type_exists($type) ) {
			$edit_url = $this->get_admin_page_url($type, 'edit-item', true) . '&post=' . $item_id;
		}

		return $edit_url;
	}
}
?>
