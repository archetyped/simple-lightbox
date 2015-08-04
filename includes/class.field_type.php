<?php
/**
 * Field Types
 * Stores properties for a specific field
 * @package Simple Lightbox
 * @subpackage Fields
 * @author Archetyped
 */
class SLB_Field_Type extends SLB_Field_Base {
	/* Properties */

	/**
	 * @var array Array of Field types that make up current Field type
	 */
	var $elements = array();

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
	 * @var SLB_Field|SLB_Field_Type|SLB_Field_Collection
	 */
	var $container = null;

	/**
	 * Object that called field
	 * Used to determine field hierarchy/nesting
	 * @var SLB_Field|SLB_Field_Type|SLB_Field_Collection
	 */
	var $caller = null;

	function __construct($id = '', $parent = null) {
		$args = func_get_args();
		$defaults = $this->integrate_id($id);
		if ( !is_array($parent) )
			$defaults['parent'] = $parent;
		
		$props = $this->make_properties($args, $defaults);
		parent::__construct($props);
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
			// Set as param as container for current field
			$this->container =& $container;
		} else {
			// Clear container member if argument is invalid
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
		// Create new field for element
		$el = new SLB_Field($name, $type);
		// Set container to current field instance
		$el->set_container($this);
		// Add properties to element
		$el->set_properties($properties);
		// Save element to current instance
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
		// Retrieve specified layout (use $name value if no layout by that name exists)
		if ( empty($name) )
			$name = $this->get_container_value('build_vars', 'layout', 'form');
		$layout = $this->get_member_value('layout', $name, $name);

		// Find all nested layouts in current layout
		if ( !empty($layout) && !!$parse_nested ) {
			$ph = $this->get_placeholder_defaults();

			while ($ph->match = $this->parse_layout($layout, $ph->pattern_layout)) {
				// Iterate through the different types of layout placeholders
				foreach ($ph->match as $tag => $instances) {
					// Iterate through instances of a specific type of layout placeholder
					foreach ($instances as $instance) {
						// Get nested layout
						$nested_layout = $this->get_member_value($instance);

						// Replace layout placeholder with retrieved item data
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

		// Find all nested layouts in layout
		$match_value = preg_match_all($search, $layout, $parse_match, PREG_PATTERN_ORDER);

		if ($match_value !== false && $match_value > 0) {
			$parse_result = array();
			// Get all matched elements
			$parse_match = $parse_match[1];

			// Build XML string from placeholders
			foreach ($parse_match as $ph) {
				$ph_xml .= $ph_start_xml . $ph . $ph_end_xml . ' ';
			}
			$ph_xml = $ph_wrap_start . $ph_xml . $ph_wrap_end;
			// Parse XML data
			$ph_prs = xml_parser_create();
			xml_parser_set_option($ph_prs, XML_OPTION_SKIP_WHITE, 1);
			xml_parser_set_option($ph_prs, XML_OPTION_CASE_FOLDING, 0);
			$ret = xml_parse_into_struct($ph_prs, $ph_xml, $parse_result['values'], $parse_result['index']);
			xml_parser_free($ph_prs);

			// Build structured array with all parsed data

			unset($parse_result['index'][$ph_root_tag]);

			// Build structured array
			$result = array();
			foreach ($parse_result['index'] as $tag => $instances) {
				$result[$tag] = array();
				// Instances
				foreach ($instances as $instance) {
					// Skip instance if it doesn't exist in parse results
					if (!isset($parse_result['values'][$instance]))
						continue;

					// Stop processing instance if a previously-saved instance with the same options already exists
					foreach ($result[$tag] as $tag_match) {
						if ($tag_match['match'] == $parse_match[$instance - 1])
							continue 2;
					}

					// Init instance data array
					$inst_data = array();

					// Add Tag to array
					$inst_data['tag'] = $parse_result['values'][$instance]['tag'];

					// Add instance data to array
					$inst_data['attributes'] = (isset($parse_result['values'][$instance]['attributes'])) ? $inst_data['attributes'] = $parse_result['values'][$instance]['attributes'] : '';

					// Add match to array
					$inst_data['match'] = $parse_match[$instance - 1];

					// Add to result array
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
	 * Build item output
	 * @param string $layout (optional) Layout to build
	 * @param string $data Data to pass to layout
	 */
	function build($layout = null, $data = null) {
		$this->util->do_action_ref_array('build_pre', array($this));
		echo $this->build_layout($layout, $data);
		$this->util->do_action_ref_array('build_post', array($this));
	}
	
	/**
	 * Builds HTML for a field based on its properties
	 * @param string $layout (optional) Name of layout to build
	 * @param array $data Additional data for current item
	 */
	function build_layout($layout = 'form', $data = null) {
		$out_default = '';
		// Get base layout
		$out = $this->get_layout($layout);
		// Only parse valid layouts
		if ( $this->is_valid_layout($out) ) {
			// Parse Layout
			$ph = $this->get_placeholder_defaults();

			// Search layout for placeholders
			while ( $ph->match = $this->parse_layout($out, $ph->pattern_general) ) {
				// Iterate through placeholders (tag, id, etc.)
				foreach ( $ph->match as $tag => $instances ) {
					// Iterate through instances of current placeholder
					foreach ( $instances as $instance ) {
						// Process value based on placeholder name
						$target_property = $this->util->apply_filters(array('process_placeholder_' . $tag, false), '', $this, $instance, $layout, $data);
						// Process value using default processors (if necessary)
						if ( '' == $target_property ) {
							$target_property = $this->util->apply_filters(array('process_placeholder', false), $target_property, $this, $instance, $layout, $data);
						}

						// Clear value if value not a string
						if ( !is_scalar($target_property) ) {
							$target_property = '';
						}
						
						// Replace layout placeholder with retrieved item data
						$out = str_replace($ph->start . $instance['match'] . $ph->end, $target_property, $out);
					}
				}
			}
		} else {
			$out = $out_default;
		}
		/* Return generated value */
		$out = $this->format_final($out);
		return $out;
	}
}