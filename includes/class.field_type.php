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
	public $elements = array();

	/**
	 * @var array Field type layouts
	 */
	public $layout = array();

	/**
	 * @var SLB_Field_Type Parent field type (reference)
	 */
	public $parent = null;

	/**
	 * Object that field is in
	 * @var SLB_Field|SLB_Field_Type|SLB_Field_Collection
	 */
	public $container = null;

	/**
	 * Object that called field
	 * Used to determine field hierarchy/nesting
	 * @var SLB_Field|SLB_Field_Type|SLB_Field_Collection
	 */
	public $caller = null;

	function __construct( $id = '', $parent = null ) {
		$args     = func_get_args();
		$defaults = $this->integrate_id( $id );
		if ( ! is_array( $parent ) ) {
			$defaults['parent'] = $parent;
		}

		$props = $this->make_properties( $args, $defaults );
		parent::__construct( $props );
	}

	/* Getters/Setters */

	/**
	 * Search for specified member value in field's container object (if exists)
	 * @param string $member Name of object member to search (e.g. properties, layout, etc.)
	 * @param string $name Value to retrieve from member
	 * @return mixed Member value if found (Default: empty string)
	 */
	function get_container_value( $member, $name = '', $default = '' ) {
		$container =& $this->get_container();
		return $this->get_object_value( $container, $member, $name, $default, 'container' );
	}

	/**
	 * Search for specified member value in field's container object (if exists)
	 * @param string $member Name of object member to search (e.g. properties, layout, etc.)
	 * @param string $name Value to retrieve from member
	 * @return mixed Member value if found (Default: empty string)
	 */
	function get_caller_value( $member, $name = '', $default = '' ) {
		$caller =& $this->get_caller();
		return $this->get_object_value( $caller, $member, $name, $default, 'caller' );
	}

	/**
	 * Sets reference to container object of current field
	 * Reference is cleared if no valid object is passed to method
	 * @param object $container
	 */
	function set_container( &$container ) {
		if ( ! empty( $container ) && is_object( $container ) ) {
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
		if ( $this->has_container() ) {
			$ret =& $this->container;
		}
		return $ret;
	}

	/**
	 * Checks if field has a container reference
	 * @return bool TRUE if field is contained, FALSE otherwise
	 */
	function has_container() {
		return ! empty( $this->container );
	}

	/**
	 * Sets reference to calling object of current field
	 * Any existing reference is cleared if no valid object is passed to method
	 * @param object $caller Calling object
	 */
	function set_caller( &$caller ) {
		if ( ! empty( $caller ) && is_object( $caller ) ) {
			$this->caller =& $caller;
		} else {
			$this->clear_caller();
		}
	}

	/**
	 * Clears reference to calling object of current field
	 */
	function clear_caller() {
		unset( $this->caller );
	}

	/**
	 * Retrieves reference to caller object of current field
	 * @return object Reference to caller object
	 */
	function &get_caller() {
		$ret = null;
		if ( $this->has_caller() ) {
			$ret =& $this->caller;
		}
		return $ret;
	}

	/**
	 * Checks if field has a caller reference
	 * @return bool TRUE if field is called by another field, FALSE otherwise
	 */
	function has_caller() {
		return ! empty( $this->caller );
	}

	/**
	 * Sets an element for the field type
	 * @param string $name Name of element
	 * @param SLB_Field_Type $type Reference of field type to use for element
	 * @param array $properties Properties for element (passed as keyed associative array)
	 * @param string $id_prop Name of property to set $name to (e.g. ID, etc.)
	 */
	function set_element( $name, $type, $properties = array(), $id_prop = 'id' ) {
		$name = trim( strval( $name ) );
		if ( empty( $name ) ) {
			return false;
		}
		// Create new field for element
		$el = new SLB_Field( $name, $type );
		// Set container to current field instance
		$el->set_container( $this );
		// Add properties to element
		$el->set_properties( $properties );
		// Save element to current instance
		$this->elements[ $name ] =& $el;
	}

	/**
	 * Add a layout to the field
	 * @param string $name Name of layout
	 * @param string $value Layout text
	 */
	function set_layout( $name, $value = '' ) {
		if ( ! is_string( $name ) ) {
			return false;
		}
		$name                  = trim( $name );
		$this->layout[ $name ] = $value;
		return true;
	}

	/**
	 * Retrieve specified layout
	 * @param string $name Layout name
	 * @param bool $parse_nested (optional) Whether nested layouts should be expanded in retreived layout or not (Default: TRUE)
	 * @return string Specified layout text
	 */
	function get_layout( $name = 'form', $parse_nested = true ) {
		// Retrieve specified layout (use $name value if no layout by that name exists)
		if ( empty( $name ) ) {
			$name = $this->get_container_value( 'build_vars', 'layout', 'form' );
		}
		$layout = $this->get_member_value( 'layout', $name, $name );

		// Find all nested layouts in current layout
		if ( ! empty( $layout ) && ! ! $parse_nested ) {
			$ph = $this->get_placeholder_defaults();
			// Check layout for placeholders.
			$ph->match = $this->parse_layout( $layout, $ph->pattern_layout );
			while ( ! empty( $ph->match ) ) {
				// Iterate through the different types of layout placeholders
				foreach ( $ph->match as $tag => $instances ) {
					// Iterate through instances of a specific type of layout placeholder
					foreach ( $instances as $instance ) {
						// Get nested layout
						$nested_layout = $this->get_member_value( $instance );

						if ( empty( $nested_layout ) ) {
							continue;
						}

						// Replace layout placeholder with retrieved item data.
						$layout = str_replace( $ph->start . $instance['match'] . $ph->end, $nested_layout, $layout );
					}
				}
				// Check layout for placeholders.
				$ph->match = $this->parse_layout( $layout, $ph->pattern_layout );
			}
		}

		return $layout;
	}

	/**
	 * Checks if specified layout exists.
	 *
	 * Finds layout if it exists in current object or any of its parents.
	 *
	 * @param string $layout Name of layout to check for.
	 * @return bool True if layout exists, False otherwise.
	 */
	function has_layout( $layout ) {
		if ( is_string( $layout ) && ! empty( trim( $layout ) ) ) {
			return false;
		}
		$layout = $this->get_member_value( 'layout', trim( $layout ), false );
		return ( false !== $layout );
	}

	/**
	 * Checks if layout content is valid
	 * Layouts need to have placeholders to be valid
	 * @param string $layout_content Layout content (markup)
	 * @return bool TRUE if layout is valid, FALSE otherwise
	 */
	function is_valid_layout( $layout_content ) {
		$ph = $this->get_placeholder_defaults();
		return preg_match( $ph->pattern_general, $layout_content );
	}

	/**
	 * Parse field layout with a regular expression
	 * @param string $layout Layout data
	 * @param string $search Regular expression pattern to search layout for
	 * @return array Associative array containing all of the regular expression matches in the layout data
	 *  Array Structure:
	 *      root => placeholder tags
	 *              => Tag instances (array)
	 *                  'tag'           => (string) tag name
	 *                  'match'         => (string) placeholder match
	 *                  'attributes'    => (array) attributes
	 */
	function parse_layout( $layout, $search ) {
		$parse_match = '';
		$result      = [];

		// Find all nested layouts in layout.
		$match_value = preg_match_all( $search, $layout, $parse_match, PREG_PATTERN_ORDER );

		// Stop if no matches found.
		if ( ! $match_value ) {
			return $result;
		}

		/* Process matches */

		$ph_xml        = '';
		$ph_root_tag   = 'ph_root_element';
		$ph_start_xml  = '<';
		$ph_end_xml    = ' />';
		$ph_wrap_start = '<' . $ph_root_tag . '>';
		$ph_wrap_end   = '</' . $ph_root_tag . '>';
		$parse_result  = [];

		// Get all matched elements.
		$parse_match = $parse_match[1];

		// Build XML string from placeholders.
		foreach ( $parse_match as $ph ) {
			$ph_xml .= $ph_start_xml . $ph . $ph_end_xml . ' ';
		}
		$ph_xml = $ph_wrap_start . $ph_xml . $ph_wrap_end;
		// Parse XML data.
		$ph_prs = xml_parser_create();
		xml_parser_set_option( $ph_prs, XML_OPTION_SKIP_WHITE, 1 );
		xml_parser_set_option( $ph_prs, XML_OPTION_CASE_FOLDING, 0 );
		$ph_parsed = xml_parse_into_struct( $ph_prs, $ph_xml, $parse_result['values'], $parse_result['index'] );
		xml_parser_free( $ph_prs );

		// Stop if placeholder parsing failed.
		if ( ! $ph_parsed ) {
			return $result;
		}

		unset( $parse_result['index'][ $ph_root_tag ] );

		// Build structured array with all parsed data.
		$ph_default = [
			'tag'        => '',
			'match'      => '',
			'attributes' => [],
		];

		// Build structured array.
		foreach ( $parse_result['index'] as $tag => $instances ) {
			// Create container for instances of current placeholder.
			$result[ $tag ] = [];
			// Process placeholder instances.
			foreach ( $instances as $instance ) {
				// Skip instance if it doesn't exist in parse results.
				if ( ! isset( $parse_result['values'][ $instance ] ) ) {
					continue;
				}
				// Stop processing instance if a previously-saved instance with the same options already exists.
				foreach ( $result[ $tag ] as $tag_match ) {
					if ( $tag_match['match'] === $parse_match[ $instance - 1 ] ) {
						continue 2;
					}
				}
				$instance_parsed = $parse_result['values'][ $instance ];
				// Init instance data array.
				$instance_data = $ph_default;

				// Set tag.
				$instance_data['tag'] = $instance_parsed['tag'];

				// Set attributes.
				if ( isset( $instance_parsed['attributes'] ) && is_array( $instance_parsed['attributes'] ) ) {
					$instance_data['attributes'] = $instance_parsed['attributes'];
				}

				// Add match to array.
				$instance_data['match'] = $parse_match[ $instance - 1 ];

				// Add to result array.
				$result[ $tag ][] = $instance_data;
			}
		}

		return $result;
	}

	/**
	 * Retrieves default properties to use when evaluating layout placeholders
	 * @return object Object with properties for evaluating layout placeholders
	 */
	function get_placeholder_defaults() {
		$ph                  = new stdClass();
		$ph->start           = '{';
		$ph->end             = '}';
		$ph->reserved        = array( 'ref' => 'ref_base' );
		$ph->pattern_general = '/' . $ph->start . '([a-zA-Z0-9_].*?)' . $ph->end . '/i';
		$ph->pattern_layout  = '/' . $ph->start . '([a-zA-Z0-9].*?\s+' . $ph->reserved['ref'] . '="layout.*?".*?)' . $ph->end . '/i';
		return $ph;
	}

	/**
	 * Build item output
	 * @param string $layout (optional) Layout to build
	 * @param string $data Data to pass to layout
	 */
	function build( $layout = null, $data = null ) {
		$this->util->do_action_ref_array( 'build_pre', array( $this ) );
		echo $this->build_layout( $layout, $data );
		$this->util->do_action_ref_array( 'build_post', array( $this ) );
	}

	/**
	 * Builds HTML for a field based on its properties
	 * @param string $layout (optional) Name of layout to build
	 * @param array $data Additional data for current item
	 */
	function build_layout( $layout = 'form', $data = null ) {
		$out_default = '';
		// Get base layout
		$out = $this->get_layout( $layout );
		// Only parse valid layouts
		if ( $this->is_valid_layout( $out ) ) {
			$out = $this->process_placeholders( $out, $layout, $data );
		} else {
			$out = $out_default;
		}
		/* Return generated value */
		$out = $this->format_final( $out );
		return $out;
	}

	/**
	 * Processes placeholders in a string.
	 *
	 * Finds and replaces placeholders in a string to their full values.
	 *
	 * @since 2.8.0
	 *
	 * @param string  $str String with placeholders to replace.
	 * @param string  $layout Optional. Name of layout being built.
	 * @param array   $data Optional. Additional data for current item.
	 * @return string Original text with placeholders converted to full values.
	 */
	public function process_placeholders( $str, $layout = 'form', $data = null ) {
		// Parse Layout.
		$ph = $this->get_placeholder_defaults();

		// Check layout for placeholders.
		$ph->match = $this->parse_layout( $str, $ph->pattern_general );

		// Parse placeholders in layout.
		while ( ! empty( $ph->match ) ) {
			// Iterate through placeholders (tag, id, etc.)
			foreach ( $ph->match as $tag => $instances ) {
				// Iterate through instances of current placeholder
				foreach ( $instances as $instance ) {
					// Process value based on placeholder name.
					$target_property = $this->util->apply_filters_ref_array( "process_placeholder_${tag}", [ '', $this, &$instance, $layout, $data ], false );
					// Process value using default processors (if necessary).
					if ( '' === $target_property ) {
						$target_property = $this->util->apply_filters_ref_array( 'process_placeholder', [ $target_property, $this, &$instance, $layout, $data ], false );
					}
					// Format output.
					if ( ! is_null( $target_property ) ) {
						$context = ( isset( $instance['attributes']['context'] ) ) ? $instance['attributes']['context'] : '';
						// Handle special characters.
						$target_property = $this->preserve_special_chars( $target_property, $context );
						// Context-specific formatting.
						$target_property = $this->format( $target_property, $context );
					}

					// Clear value if value not a string
					if ( ! is_scalar( $target_property ) ) {
						$target_property = '';
					}
					// Replace layout placeholder with retrieved item data
					$str = str_replace( $ph->start . $instance['match'] . $ph->end, $target_property, $str );
				}
			}
			// Check layout for placeholders.
			$ph->match = $this->parse_layout( $str, $ph->pattern_general );
		}
		return $str;
	}
}
