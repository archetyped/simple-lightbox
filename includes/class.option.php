<?php
/**
 * Option object
 * @package Simple Lightbox
 * @subpackage Options
 * @author Archetyped
 */
class SLB_Option extends SLB_Field {

	/* Properties */

	public $hook_prefix = 'option';

	/**
	 * Determines whether option will be sent to client
	 * @var bool
	 */
	public $in_client = false;

	/**
	 * Child mapping
	 * @see SLB_Field_Base::map
	 * @var array
	 */
	public $map = array(
		'default' => 'data',
		'attr'    => 'properties',
	);

	public $property_priority = array( 'id', 'data', 'parent' );

	/* Init */

	/**
	 * @see SLB_Field::__construct()
	 * @uses parent::__construct() to initialize instance
	 * @param $id
	 * @param $title
	 * @param $default
	 */
	function __construct( $id, $title = '', $default = '' ) {
		// Normalize properties
		$args     = func_get_args();
		$defaults = array(
			'title'   => '',
			'default' => '',
		);
		$props    = $this->make_properties( $args, $defaults );
		// Validate
		if ( is_scalar( $id ) ) {
			$props['id'] = $id;
		}
		if ( ! is_string( $props['title'] ) ) {
			$props['title'] = '';
		}
		// Send to parent constructor
		parent::__construct( $props );
	}

	/* Getters/Setters */

	/**
	 * Retrieve default value for option
	 * @return mixed Default option value
	 */
	function get_default( $context = '' ) {
		return $this->get_data( $context, false );
	}

	/**
	 * Sets parent based on default value
	 */
	function set_parent( $parent = null ) {
		$p = $this->get_parent();
		if ( empty( $parent ) && empty( $p ) ) {
			$parent = 'text';
			$d      = $this->get_default();
			if ( is_bool( $d ) ) {
				$parent = 'checkbox';
			}
			$parent = 'option_' . $parent;
		} elseif ( ! empty( $p ) && ! is_object( $p ) ) {
			$parent =& $p;
		}
		parent::set_parent( $parent );
	}

	/**
	 * Set in_client property
	 * @uses this::in_client
	 * @param bool Whether or not option should be included in client output (Default: false)
	 * @return void
	 */
	function set_in_client( $in_client = false ) {
		$this->in_client = ! ! $in_client;
	}

	/**
	 * Determines whether option should be included in client output
	 * @uses this::in_client
	 * @return bool TRUE if option is included in client output
	 */
	function get_in_client() {
		return $this->in_client;
	}

	/* Formatting */

	/**
	 * Format data as string for browser output
	 * @see SLB_Field_Base::format()
	 * @param mixed $value Data to format
	 * @param string $context (optional) Current context
	 * @return string Formatted value
	 */
	function format_display( $value, $context = '' ) {
		if ( ! is_string( $value ) ) {
			if ( is_bool( $value ) ) {
				$value = ( $value ) ? __( 'Enabled', 'simple-lightbox' ) : __( 'Disabled', 'simple-lightbox' );
			} elseif ( is_null( $value ) ) {
				$value = '';
			} else {
				$value = strval( $value );
			}
		} elseif ( empty( $value ) ) {
			$value = 'empty';
		}
		return htmlentities( $value );
	}

	/**
	 * Format data using same format as default value
	 * @see SLB_Field_Base::format()
	 * @param mixed $value Data to format
	 * @param string $context (optional) Current context
	 * @return mixed Formatted option value
	 */
	function format_default( $value, $context = '' ) {
		// Get default value
		$d = $this->get_default();
		if ( empty( $d ) ) {
			return $value;
		}
		if ( is_bool( $d ) ) {
			$value = $this->format_bool( $value );
		} elseif ( is_string( $d ) ) {
			$value = $this->format_string( $value );
		}
		return $value;
	}

	/**
	 * Format data as boolean (true/false)
	 * @see SLB_Field_Base::format()
	 * @param mixed $value Data to format
	 * @param string $context (optional) Current context
	 * @return bool Option value
	 */
	function format_bool( $value, $context = '' ) {
		if ( ! is_bool( $value ) ) {
			$value = ! ! $value;
		}
		return $value;
	}

	/**
	 * Format data as string
	 * @see SLB_Field_Base::format()
	 * @param mixed $value Data to format
	 * @param string $context (optional) Current context
	 * @return string Option string value
	 */
	function format_string( $value, $context = '' ) {
		if ( is_bool( $value ) ) {
			$value = ( $value ) ? 'true' : 'false';
		} elseif ( is_object( $value ) ) {
			$value = get_class( $value );
		} elseif ( is_array( $value ) ) {
			$value = implode( ' ', $value );
		} else {
			$value = strval( $value );
		}
		return $value;
	}
}
