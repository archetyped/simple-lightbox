<?php
require_once 'class.base.php';

/**
 * Managed collection
 * @package Simple Lightbox
 * @subpackage Base
 * @author Archetyped
 */
class SLB_Base_Collection extends SLB_Base {
	/* Configuration */
	
	/**
	 * Set object mode
	 * @var string
	 */
	protected $mode = 'object';

	/**
	 * Item type
	 * @var string
	 */
	protected $item_type = null;
	
	/* Properties */

	/**
	 * Indexed array of items in collection
	 * @var array
	 */
	var $items = null;
	
	/* Constructors */

	/**
	 * Class constructor
	 * @uses parent::__construct()
	 * @uses self::init()
	 */
	public function __construct() {
		//Parent constructor
		parent::__construct();
	}
	
	/* Item Management */
	
	/**
	 * Normalize/Validate item(s)
	 * TODO: If no items are specified, then collection is normalized
	 * Single items are wrapped in an array
	 * @param array|object $items Item(s) to validate
	 * @return array Validated items
	 */
	protected function normalize($items) {
		if ( !is_array($items) ) {
			$items = array($items);
		}
		//Validate item type
		if ( !is_null($this->item_type) ) {
			foreach ( $items as $idx => $item ) {
				//Remove invalid items
				if ( !( $item instanceof $this->item_type ) ) {
					unset($items[$idx]);
				}
			}
		}
		if ( !empty($items) ) {
			$items = array_values($items);
		}
		return $items;
	}
	
	/**
	 * Validate item key
	 * Checks collection for existence of key as well
	 * @param string|int $key Key to check collection for
	 * @return bool TRUE if key is valid
	 */
	protected function key_valid($key) {
		return ( ( ( is_string($key) && !empty($key) ) || is_int($key) ) && isset($this->items[$key]) ) ? true : false;
	}
	
	/**
	 * Add item(s) to collection
	 * @param mixed $items Single item or array of items to add
	 * @return Current instance
	 */
	public function add($items) {
		//Validate
		$items = $this->normalize($items);
		//Add items to collection
		if ( !empty($items) ) {
			$this->items = array_merge($this->items, $items);
		}
		return $this;
	}
	
	/**
	 * Remove item(s) from collection
	 * @param int|string $item Key of item to remove
	 * @return Current instance 
	 */
	public function remove($item) {
		if ( $this->key_valid($item) ) {
			unset($this->items[$item]);
		}
		return $this;
	}
	
	/**
	 * Clear collection
	 * @return Current instance
	 */
	public function clear() {
		$this->items = array();
		return $this;
	}
	
	/**
	 * Checks if item exists in the collection
	 * @param mixed $item Item(s) to check for
	 * @return bool TRUE if item(s) in collection
	 */
	public function has($items) {
		//Attempt to locate item
		return false;
	}
	
	/**
	 * Retrieve item(s) from collection
	 * If no items specified, entire collection returned
	 * @param string|int $item (optional) ID of item to retrieve
	 * @return object|array Specified item(s)
	 */
	public function get($item = null) {
		//Initialize
		if ( is_null($this->items) ) {
			$this->items = array();
			$this->util->do_action('init', $this);
		}
		$ret = array();
		if ( 0 != func_num_args() ) {
			$single = false;
			if ( !is_array($item) ) {
				$single = true;
				$item = array($item);
			}
			foreach ( $item as $i ) {
				if ( $this->key_valid($i) ) {
					$ret[] = $this->items[$i];
				}
			}
			//Unwrap single item
			if ( $single ) {
				$ret = ( 1 == count($ret) ) ? $ret[0] : null;
			}
		} else {
			$ret = $this->items;
		}
		return $ret;
	}
	
	/* Collection */
	
	/**
	 * Build entire collection of items
	 * Prints output
	 */
	function build($build_vars = array()) {
		//Parse vars
		$this->parse_build_vars($build_vars);
		$this->util->do_action_ref_array('build_init', array(&$this));
		//Pre-build output
		$this->util->do_action_ref_array('build_pre', array(&$this));
		//Build groups
		$this->build_groups();
		//Post-build output
		$this->util->do_action_ref_array('build_post', array(&$this));
	}
	
	/**
	 * Parses build variables prior to use
	 * @uses this->reset_build_vars() to reset build variables for each request
	 * @param array $build_vars Variables to use for current request
	 */
	function parse_build_vars($build_vars = array()) {
		$this->reset_build_vars();
		$this->build_vars = $this->util->apply_filters('parse_build_vars', wp_parse_args($build_vars, $this->build_vars), $this);		
	}
	
	/**
	 * Reset build variables to defaults
	 * Default Variables
	 * > groups		- array - Names of groups to build
	 * > context	- string - Context of current request
	 * > layout		- string - Name of default layout to use
	 */
	function reset_build_vars() {
		$this->build_vars = wp_parse_args($this->build_vars, $this->build_vars_default);
	}
}