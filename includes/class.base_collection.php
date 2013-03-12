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
	
	/**
	 * Property to use for item key
	 * Example: A property or method of the item
	 * @var string
	 */
	protected $key_prop = null;
	
	/**
	 * Should $key_prop be called or retrieved?
	 * Default: Retrieved (FALSE)
	 * @var bool
	 */
	protected $key_call = false;
	
	/**
	 * Items in collection unique?
	 * Default: FALSE
	 * @var bool
	 */
	protected $unique = false;
	
	/* Properties */

	/**
	 * Indexed array of items in collection
	 * @var array
	 */
	protected $items = null;
	
	/**
	 * Item metadata
	 * Indexed by item key
	 * @var array
	 */
	protected $items_meta = array();
	
	/* Item Management */
	
	/**
	 * Initialize collections
	 * Calls `init` action if collection has a hook prefix
	 */
	private function init() {
		//Initialize
		if ( is_null($this->items) ) {
			$this->items = array();
			if ( !empty($this->hook_prefix) ) {
				$this->util->do_action('init', $this);
			}
		}
	}
	
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
		$this->init();
		return ( ( ( is_string($key) && !empty($key) ) || is_int($key) ) && isset($this->items[$key]) ) ? true : false;
	}
	
	/**
	 * Add item(s) to collection
	 * @param mixed $items Single item or array of items to add
	 * @return Current instance
	 */
	public function add($items) {
		$this->init();
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
		$this->init();
		$ret = array();
		if ( func_num_args() > 0 ) {
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
	
	/* Metadata */
	
	/**
	 * Add metadata for item
	 * @param string|int $item Item key
	 * @param string|array $meta_key Meta key to set (or array of metadata)
	 * @param mixed $meta_value (optional) Metadata value (if key set)
	 * @param bool $reset (optional) Whether to remove existing metadata first (Default: FALSE)
	 * @return object Current instance
	 */
	protected function add_meta($item, $meta_key, $meta_value = null, $reset = false) {
		//Validate
		if ( $this->key_valid($item) && ( is_array($meta_key) || is_string($meta_key) ) ) {
			//Prepare metadata
			$meta = ( is_string($meta_key) ) ? array($meta_key => $meta_value) : $meta_key;
			//Reset existing meta (if necessary)
			if ( is_array($meta_key) ) {
				$reset = func_get_arg(2);
			}
			if ( !!$reset ) {
				unset($this->items_meta[$item]);
			}
			//Add metadata
			if ( !isset($this->items_meta[$item]) ) {
				$this->items_meta[$item] = array();
			}
			$this->items_meta[$item] = array_merge($this->items_meta[$item], $meta);
		}
		return $this;
	}
	
	/**
	 * Remove item metadata
	 * @param string $item Item key
	 * @return object Current instance
	 */
	protected function remove_meta($item, $meta_key = null) {
		if ( $this->key_valid($item) && isset($this->items_meta[$item]) ) {
			if ( is_string($meta_key) ) {
				//Remove specific meta value
				unset($this->items_meta[$item][$meta_key]);
			} else {
				//Remove all metadata
				unset($this->items_meta[$item]);
			}
		}
		return $this;
	}
	
	/**
	 * Retrieve metadata
	 * @param string $item Item key
	 * @param string $meta_key (optional) Meta key (All metadata retrieved if no key specified)
	 * @return mixed|null Metadata value
	 */
	protected function get_meta($item, $meta_key = null) {
		$ret = null;
		if ( $this->key_valid($item) && isset($this->items_meta[$item]) ) {
			if ( is_null($meta_key) ) {
				$ret = $this->items_meta[$item];
			} elseif ( is_string($meta_key) && isset($this->items_meta[$item][$meta_key]) ) {
				$ret = $this->items_meta[$item][$meta_key];
			}
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