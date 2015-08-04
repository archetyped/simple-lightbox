<?php
/**
 * Managed collection of fields
 * @package Simple Lightbox
 * @subpackage Fields
 * @author Archetyped
 */
class SLB_Field_Collection extends SLB_Field_Base {

	/* Configuration */
	
	protected $mode = 'sub';
	
	/* Properties */
	
	/**
	 * Item type
	 * @var string
	 */
	var $item_type = 'SLB_Field';
	
	/**
	 * Indexed array of items in collection
	 * @var array
	 */
	var $items = array();
	
	var $id_formats = array (
		'formatted' => array(
			'wrap'		=> array ( 'open' => '_' ),
			'recursive'	=> false,
			'prefix'	=> array('get_prefix')
		)
	);
	
	var $build_vars_default = array ( 
		'groups'		=> array(),
		'context'		=> '',
		'layout'		=> 'form',
		'build'			=> true,
		'build_groups'	=> true,
	);
	
	/**
	 * Associative array of groups in collection
	 * Key: Group ID
	 * Value: object of group properties
	 *  > id
	 *  > title
	 *  > description string Group description
	 *  > items array Items in group
	 * @var array
	 */
	var $groups = array();
	
	protected $properties_init = null;
	
	/* Constructors */

	/**
	 * Class constructor
	 * @uses parent::__construct()
	 * @uses self::make_properties()
	 * @uses self::init()
	 * @uses self::add_groups()
	 * @uses self::add_items()
	 * @param string $id Collection ID
	 * @param array $properties (optional) Properties to set for collection (Default: none)
	 */
	public function __construct($id, $properties = null) {
		$args = func_get_args();
		$properties = $this->make_properties($args);
		// Parent constructor
		parent::__construct($properties);
		
		// Save initial properties
		$this->properties_init = $properties;
	}
	
	public function _init() {
		parent::_init();
		$this->load($this->properties_init, false);
	}
	
	/*-** Getters/Setters **-*/
	
	/* Setup */
	
	/**
	 * Load collection with specified properties
	 * Updates existing properties
	 * @param array $properties Properties to load
	 * @param bool $update (optional) Update (TRUE) or overwrite (FALSE) items/groups (Default: TRUE)
	 * @return object Current instance
	 */
	public function load($properties, $update = true) {
		$args = func_get_args();
		$properties = $this->make_properties($args);
		if ( !empty($properties) ) {
			// Groups
			if ( isset($properties['groups']) ) {
				$this->add_groups($properties['groups'], $update);
			}
			// Items
			if ( isset($properties['items']) ) {
				$this->add_items($properties['items'], $update);
			}
		}
		return $this;
	}
	
	/* Data */
	
	/**
	 * Retrieve external data for items in collection
	 * Retrieved data is saved to the collection's $data property
	 * Uses class properties to determine how data is retrieved
	 * Examples:
	 *  > DB
	 *  > XML
	 *  > JSON
	 * @return void
	 */
	function load_data() {
		$this->data_loaded = true;
	}
	
	/**
	 * Set data for an item
	 * @param mixed $item Field to set data for
	 *  > string	Field ID
	 *  > object	Field Reference
	 *  > array		Data for multiple items (associative array [field ID => data])
	 * @param mixed $value Data to set
	 * @param bool $save (optional) Whether or not data should be saved to DB (Default: Yes)
	 */
	function set_data($item, $value = '', $save = true, $force_set = false) {
		// Set data for entire collection
		if ( is_array($item) ) {
			$this->data = wp_parse_args($item, $this->data);
			// Update save option
			$args = func_get_args();
			if ( 2 == count($args) && is_bool($args[1]) ) {
				$save = $args[1];
			}
		}
		// Get $item's ID
		elseif ( is_object($item) && method_exists($item, 'get_id') )
			$item = $item->get_id();
		// Set data
		if ( is_string($item) && !empty($item) && ( isset($this->items[$item]) || !!$force_set ) )
			$this->data[$item] = $value;
		if ( !!$save )
			$this->save();
	}

	/* Item */
	
	/**
	 * Adds item to collection
	 * @param string|obj $id Unique name for item or item instance
	 * @param array $properties (optional) Item properties
	 * @param bool $update (optional) Update or overwrite existing item (Default: FALSE)
	 * @return object Newly-added item
	 */
	function add($id, $properties = array(), $update = false) {
		$item;
		$args = func_get_args();
		// Properties
		foreach ( array_reverse($args) as $arg ) {
			if ( is_array($arg) ) {
				$properties = $arg;
				break;
			}
		}
		if ( !is_array($properties) ) {
			$properties = array();
		}
		
		// Handle item instance
		if ( $id instanceof $this->item_type ) {
			$item = $id;
			$item->set_properties($properties);
		} elseif ( class_exists($this->item_type) ) {
			$defaults = array (
				'parent'		=> null,
				'group'			=> null
			);
			$properties = array_merge($defaults, $properties);
			if ( is_string($id) ) {
				$properties['id'] = $id;
			}
			if ( !!$update && $this->has($properties['id']) ) {
				// Update existing item
				$item = $this->get($properties['id']);
				$item->set_properties($properties);
			} else {
				// Init item
				$type = $this->item_type;
				$item = new $type($properties);
			}
		}
		
		if ( empty($item) || 0 == strlen($item->get_id()) ) {
			return false;
		}
		
		// Set container
		$item->set_container($this);

		// Add item to collection
		$this->items[$item->get_id()] = $item;
		
		if ( isset($properties['group']) ) {
			$this->add_to_group($properties['group'], $item->get_id());
		}
		
		return $item;
	}

	/**
	 * Removes item from collection
	 * @param string|object $item Object or item ID to remove
	 * @param bool $save (optional) Whether to save the collection after removing item (Default: YES)
	 */
	function remove($item, $save = true) {
		// Remove item
		if ( $this->has($item) ) {
			$item = $this->get($item);
			$item = $item->get_id();
			// Remove from items array
			unset($this->items[$item]);
			// Remove item from groups
			$this->remove_from_group($item);
		}
		// Remove item data from collection
		$this->remove_data($item, false);
		
		if ( !!$save )
			$this->save();
	}
	
	/**
	 * Remove item data from collection
	 * @param string|object $item Object or item ID to remove
	 * @param bool $save (optional) Whether to save the collection after removing item (Default: YES)
	 */
	function remove_data($item, $save = true) {
		// Get item ID from object
		if ( $this->has($item) ) {
			$item = $this->get($item);
			$item = $item->get_id();
		}
		
		// Remove data from data member
		if ( is_string($item) && is_array($this->data) ) {
			unset($this->data[$item]);
			if ( !!$save )
				$this->save();
		}
	}

	/**
	 * Checks if item exists in the collection
	 * @param string $item Item ID
	 * @return bool TRUE if item exists, FALSE otherwise
	 */
	function has($item) {
		return ( !is_string($item) || empty($item) || is_null($this->get_member_value('items', $item, null)) ) ? false : true;
	}
	
	/**
	 * Retrieve specified item in collection
	 * @param string|object $item Item object or ID to retrieve
	 * @return SLB_Field Specified item
	 */
	function get($item, $safe_mode = false) {
		if ( $this->has($item) ) {
			if ( !is_object($item) || !($item instanceof $this->item_type) ) {
				if ( is_string($item) ) {
					$item = trim($item);
					$item =& $this->items[$item];
				}
				else {
					$item = false;
				}
			}
		} else {
			$item = false;
		}
		
		if ( !!$safe_mode && !is_object($item) ) {
			// Fallback: Return empty item if no item exists
			$type = $this->item_type;
			$item = new $type('');
		}
		return $item;
	}
	
	/**
	 * Retrieve item data
	 * @param $item Item to get data for
	 * @param $context (optional) Context
	 * @param $top (optional) Iterate through ancestors to get data (Default: Yes)
	 * @return mixed Item data
	 */
	function get_data($item = null, $context = '', $top = true) {
		$this->load_data();
		$ret = null;
		if ( $this->has($item) ) {
			$item = $this->get($item);
			$ret = $item->get_data($context, $top);
		} else {
			$ret = parent::get_data($context, $top);
		}
		
		if ( is_string($item) && is_array($ret) && isset($ret[$item]) )
			$ret = $ret[$item];
		return $ret;
	}

	/* Items (Collection) */
	
	/**
	 * Add multiple items to collection
	 * @param array $items Items to add to collection
	 * Array Structure:
	 *  > Key (string): Item ID
	 *  > Val (array): Item properties
	 * @return void
	 */
	function add_items($items = array(), $update = false) {
		// Validate
		if ( !is_array($items) || empty($items) ) {
			return false;
		}
		// Add items
		foreach ( $items as $id => $props ) {
			$this->add($id, $props, $update);
		}
	}
	
	/**
	 * Retrieve reference to items in collection
	 * @return array Collection items (reference)
	 */
	function &get_items($group = null, $sort = 'priority') {
		$gset = $this->group_exists($group);
		if ( $gset ) {
			$items = $this->get_group_items($group);
		} elseif ( !empty($group) ) {
			$items = array();
		} else {
			$items = $this->items;
		}
		if ( !empty($items) ) {
			// Sort items
			if ( !empty($sort) && is_string($sort) ) {
				if ( 'priority' == $sort ) {
					if ( $gset ) {
						// Sort by priority
						ksort($items, SORT_NUMERIC);
					}
				}
			}
			// Release from buckets
			if ( $gset ) {
				$items = call_user_func_array('array_merge', $items);
			}
		}
		return $items;
	}
	
	/**
	 * Build output for items in specified group
	 * If no group specified, all items in collection are built
	 * @param string|object $group (optional) Group to build items for (ID or instance object)
	 */
	function build_items($group = null) {
		// Get group items
		$items =& $this->get_items($group);
		if ( empty($items) ) {
			return false;
		}
		
		$this->util->do_action_ref_array('build_items_pre', array($this));
		foreach ( $items as $item ) {
			$item->build();
		}
		$this->util->do_action_ref_array('build_items_post', array($this));
	}
	
	/* Group */
	
	/**
	 * Add groups to collection
	 * @param array $groups Associative array of group properties
	 * Array structure:
	 *  > Key (string): group ID
	 *  > Val (string): Group Title
	 */
	function add_groups($groups = array(), $update = false) {
		// Validate
		if ( !is_array($groups) || empty($groups) ) {
			return false;
		}
		// Iterate
		foreach ( $groups as $id => $props ) {
			$this->add_group($id, $props, null, $update);
		}
	}
	
	/**
	 * Adds group to collection
	 * Groups are used to display related items in the UI 
	 * @param string $id Unique name for group
	 * @param string $title Group title
	 * @param string $description Short description of group's purpose
	 * @param array $items (optional) ID's of existing items to add to group
	 * @return object Group object
	 */
	function &add_group($id, $properties = array(), $items = array(), $update = false) {
		// Create new group and set properties
		$default = array (
			'title'			=> '',
			'description'	=> '',
			'priority'		=> 10
		);
		$p = ( is_array($properties) ) ? array_merge($default, $properties) : $default;
		if ( !is_int($p['priority']) || $p['priority'] < 0 ) {
			$p['priority'] = $default['priority'];
		}
		$id = trim($id);
		// Retrieve or init group
		if ( !!$update && $this->group_exists($id) ) {
			$grp = $this->get_group($id);
			$grp->title = $p['title'];
			$grp->description = $p['description'];
			$grp->priority = $p['priority'];
		} else {
			$this->groups[$id] =& $this->create_group($id, $p['title'], $p['description'], $p['priority']);
		}
		// Add items to group (if supplied)
		if ( !empty($items) && is_array($items) ) {
			$this->add_to_group($id, $items);
		}
		return $this->groups[$id];
	}

	/**
	 * Remove specified group from collection
	 * @param string $id Group ID to remove
	 */
	function remove_group($id) {
		$id = trim($id);
		if ( $this->group_exists($id) ) {
			unset($this->groups[$id]);
		}
	}

	/**
	 * Standardized method to create a new item group
	 * @param string $title Group title (used in meta boxes, etc.)
	 * @param string $description Short description of group's purpose
	 * @param int $priority (optional) Group priority (e.g. used to sort groups during output)
	 * @return object Group object
	 */
	function &create_group($id = '', $title = '', $description = '', $priority = 10) {
		// Create new group object
		$group = new stdClass();
		/* Set group properties */
		// Set ID
		$id = ( is_scalar($id) ) ? trim($id) : '';
		$group->id = $id;
		// Set Title
		$title = ( is_scalar($title) ) ? trim($title) : '';
		$group->title = $title;
		// Set Description
		$description = ( is_scalar($description) ) ? trim($description) : '';
		$group->description = $description;
		// Priority
		$group->priority = ( is_int($priority) ) ? $priority : 10;
		// Create array to hold items
		$group->items = array();
		return $group;
	}
	
	/**
	 * Checks if group exists in collection
	 * @param string $id Group name
	 * @return bool TRUE if group exists, FALSE otherwise
	 */
	function group_exists($group) {
		$ret = false;
		if ( is_object($group) ) {
			$ret = true;
		} elseif ( is_string($group) && ($group = trim($group)) && strlen($group) > 0 ) {
			$group = trim($group);
			// Check if group exists
			$ret = !is_null($this->get_member_value('groups', $group, null));
		}
		return $ret;
	}
	
	/**
	 * Adds item to a group in the collection
	 * Group is created if it does not already exist
	 * @param string|array $group ID of group (or group parameters if new group) to add item to
	 * @param string|array $items Name or array of item(s) to add to group
	 */
	function add_to_group($group, $items, $priority = 10) {
		// Validate
		if ( empty($items) || empty($group) || ( !is_string($group) && !is_array($group) ) ) {
			return false;
		}
		
		// Get group ID
		if ( is_string($group) ) {
			$group = array($group, $priority);
		}
		list($gid, $priority) = $group;
		$gid = trim(sanitize_title_with_dashes($gid));
		if ( empty($gid) ) {
			return false;
		}
		// Item priority
		if ( !is_int($priority) ) {
			$priority = 10;
		}
		
		// Prepare group
		if ( !$this->group_exists($gid) ) {
			// TODO Follow
			call_user_func($this->m('add_group'), $gid, $group);
		}
		// Prepare items
		if ( !is_array($items) ) {
			$items = array($items);
		}
		// Add Items
		foreach ( $items as $item ) {
			// Skip if not in current collection
			$itm_ref = $this->get($item);
			if ( !$itm_ref ) {
				continue;
			}
			$itm_id = $itm_ref->get_id();
			// Remove item from any other group it's in (items can only be in one group)
			foreach ( $this->get_groups() as $group ) {
				foreach ( $group->items as $tmp_pri => $tmp_items ) {
					if ( isset($group->items[$tmp_pri][$itm_id]) ) {
						unset($group->items[$tmp_pri][$itm_id]);
					}
				}
			}
			// Add reference to item in group
			$items =& $this->get_group($gid)->items;
			if ( !isset($items[$priority]) ) {
				$items[$priority] = array();
			}
			$items[$priority][$itm_id] = $itm_ref;
		}
		unset($itm_ref);
	}

	/**
	 * Remove item from a group
	 * If no group is specified, then item is removed from all groups
	 * @param string|object $item Object or ID of item to remove from group
	 * @param string $group (optional) Group ID to remove item from
	 */
	function remove_from_group($item, $group = '') {
		// Get ID of item to remove or stop execution if item invalid
		$item = $this->get($item);
		$item = $item->get_id();
		if ( !$item )
			return false;

		// Remove item from group
		if ( !empty($group) ) {
			// Remove item from single group
			if ( ($group =& $this->get_group($group)) && isset($group->items[$item]) ) {
				unset($group->items[$item]);
			}
		} else {
			// Remove item from all groups
			foreach ( array_keys($this->groups) as $group ) {
				if ( ($group =& $this->get_group($group)) && isset($group->items[$item]) ) {
					unset($group->items[$item]);
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
		if ( is_object($group) ) {
			return $group;
		}
		if ( is_string($group) ) {
			$group = trim($group);
		}
		// Create group if it doesn't already exist
		if ( ! $this->group_exists($group) ) {
			$this->add_group($group);
		}
		return $this->get_member_value('groups', $group);
	}
	
	/**
	 * Retrieve a group's items
	 * @uses SLB_Field_Collection::get_group() to retrieve group object
	 * @param object|string $group Group object or group ID
	 * @return array Group's items
	 */
	function &get_group_items($group) {
		$group =& $this->get_group($group);
		return $group->items;
	}

	/**
	 * Retrieve all groups in collection
	 * @return array Reference to group objects
	 */
	function &get_groups($opts = array()) {
		$groups =& $this->get_member_value('groups');
		if ( is_array($opts) && !empty($opts) ) {
			extract($opts, EXTR_SKIP);
			if ( !empty($groups) && !empty($sort) && is_string($sort) ) {
				if ( property_exists(current($groups), $sort) ) {
					// Sort groups by property
					$sfunc = create_function('$a,$b', '$ap = $a->' . $sort . '; $bp = $b->' . $sort . '; if ( $ap == $bp ) return 0; return ( $ap > $bp ) ? 1 : -1;');
					uasort($groups, $sfunc);
				}
			}
		}
		return $groups;
	}
	
	/**
	 * Output groups
	 * @uses self::build_vars to determine groups to build
	 */
	function build_groups() {
		$this->util->do_action_ref_array('build_groups_pre', array($this));
		
		// Get groups to build
		$groups = ( !empty($this->build_vars['groups']) ) ? $this->build_vars['groups'] : array_keys($this->get_groups(array('sort' => 'priority')));
		// Check options
		if ( is_callable($this->build_vars['build_groups']) ) {
			// Pass groups to callback to build output
			call_user_func_array($this->build_vars['build_groups'], array($this, $groups));
		} elseif ( !!$this->build_vars['build_groups'] ) {
			// Build groups
			foreach ( $groups as $group ) {
				$this->build_group($group);
			}
		}
		
		$this->util->do_action_ref_array('build_groups_post', array($this));
	}

	/**
	 * Build group
	 */
	function build_group($group) {
		if ( !$this->group_exists($group) ) {
			return false;
		}
		$group =& $this->get_group($group);
		// Stop processing if group contains no items
		if ( !count($this->get_items($group)) ) {
			return false;
		}
		
		// Pre action
		$this->util->do_action_ref_array('build_group_pre', array($this, $group));
		
		// Build items
		$this->build_items($group);
		
		// Post action
		$this->util->do_action_ref_array('build_group_post', array($this, $group));
	}

	/* Collection */
	
	/**
	 * Build entire collection of items
	 * Prints output
	 */
	function build($build_vars = array()) {
		// Parse vars
		$this->parse_build_vars($build_vars);
		$this->util->do_action_ref_array('build_init', array($this));
		// Pre-build output
		$this->util->do_action_ref_array('build_pre', array($this));
		// Build groups
		$this->build_groups();
		// Post-build output
		$this->util->do_action_ref_array('build_post', array($this));
	}
	
	/**
	 * Set build variable
	 * @param string $key Variable name
	 * @param mixed $val Variable value 
	 */
	function set_build_var($key, $val) {
		$this->build_vars[$key] = $val;
	}
	
	/**
	 * Retrieve build variable
	 * @param string $key Variable name
	 * @param mixed $default Value if variable is not set
	 * @return mixed Variable value
	 */
	function get_build_var($key, $default = null) {
		return ( array_key_exists($key, $this->build_vars) ) ? $this->build_vars[$key] : $default;
	}
	
	/**
	 * Delete build variable
	 * @param string $key Variable name to delete
	 */
	function delete_build_var($key) {
		if ( array_key_exists($key, $this->build_vars) ) {
			unset($this->build_vars[$key]);
		}
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