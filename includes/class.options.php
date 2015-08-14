<?php
/**
 * Options collection
 * @package Simple Lightbox
 * @subpackage Options
 * @author Archetyped
 * @uses SLB_Field_Collection
 */
class SLB_Options extends SLB_Field_Collection {
	
	/* Properties */
	
	public $hook_prefix = 'options';

	var $item_type = 'SLB_Option';

	/**
	 * Key for saving version to DB
	 * @var string
	 */
	private $version_key = 'version';
	
	/**
	 * Whether version has been checked
	 * @var bool
	 */
	var $version_checked = false;
	
	var $items_migrated = false;
		
	var $build_vars = array (
		'validate_pre'	=> false,
		'validate_post'	=> false,
		'save_pre'		=> false,
		'save_post'		=> false
	);
	
	/* Init */
	
	function __construct($id = '', $props = array()) {
		// Validate arguments
		$args = func_get_args();
		// Set default ID
		if ( !$this->validate_id($id) ) {
			$id = 'options';
		}
		$defaults = $this->integrate_id($id);
		$props = $this->make_properties($args, $defaults);
		parent::__construct($props);
		$this->add_prefix_ref($this->version_key);
	}
	
	protected function _hooks() {
		parent::_hooks();
		// Register fields
		$this->util->add_action('register_fields', $this->m('register_fields'), 10, 1, false);
		// Set option parents
		$this->util->add_action('fields_registered', $this->m('set_parents'), 10, 1, false);
		// Building
		$this->util->add_action('build_init', $this->m('build_init'));
		// Admin
		$this->util->add_action('admin_page_render_content', $this->m('admin_page_render_content'), 10, 3, false);
		$this->util->add_filter('admin_action_reset', $this->m('admin_action_reset'), 10, 3, false);
	}
	
	/* Legacy/Migration */
	
	/**
	 * Checks whether new version has been installed and migrates necessary settings
	 * @uses $version_key as option name
	 * @uses get_option() to retrieve saved version number
	 * @uses SLB_Utilities::get_plugin_version() to retrieve current version
	 * @return bool TRUE if version has been changed
	 */
	function check_update() {
		if ( !$this->version_checked ) {
			$this->version_checked = true;
			$version_changed = false;
			// Get version from DB
			$vo = $this->get_version();
			// Get current version
			$vn = $this->util->get_plugin_version();
			// Compare versions
			if ( $vo != $vn ) {
				// Update saved version
				$this->set_version($vn);
				// Migrate old version to new version
				if ( strcasecmp($vo, $vn) < 0 ) {
					// Force full migration
					$version_changed = true;
				}
			}
			// Migrate
			$this->migrate($version_changed);
		}
		
		return $this->version_checked;
	}
	
	/**
	 * Save plugin version to DB
	 * If no version supplied, will fetch plugin data to determine version
	 * @uses $version_key as option name
	 * @uses update_option() to save version to options table
	 * @param string $ver (optional) Plugin version
	 */
	function set_version($ver = null) {
		if ( empty($ver) ) {
			$ver = $this->util->get_plugin_version();
		}
		return update_option($this->version_key, $ver);
	}
	
	/**
	 * Retrieve saved version data
	 * @return string Saved version
	 */
	function get_version() {
		return get_option($this->version_key, '');
	}
	
	/**
	 * Migrate options from old versions to current version
	 * @uses self::items_migrated to determine if simple migration has been performed in current request or not
	 * @uses self::save() to save data after migration
	 * @param bool $full Whether to perform a full migration or not (Default: No)
	 */
	function migrate($full = false) {
		if ( !$full && $this->items_migrated )
			return false;
		
		// Legacy options
		$d = null;
		$this->load_data();
		
		$items = $this->get_items();
		
		// Migrate separate options to unified option
		if ( $full ) {
			foreach ( $items as $opt => $props ) {
				$oid = $this->add_prefix($opt);
				$o = get_option($oid, $d);
				if ( $o !== $d ) {
					// Migrate value to data array
					$this->set_data($opt, $o, false);
					// Delete legacy option
					delete_option($oid);
				}
			}
		}
		
		// Migrate legacy items
		if ( is_array($this->properties_init) && isset($this->properties_init['legacy']) && is_array($this->properties_init['legacy']) ) {
			$l =& $this->properties_init['legacy'];
			// Normalize legacy map
			foreach ( $l as $opt => $dest ) {
				if ( !is_array($dest) ) {
					if ( is_string($dest) )
						$l[$opt] = array($dest);
					else
						unset($l[$opt]);
				}
			}
			
			/* Separate options */
			if ( $full ) {
				foreach ( $l as $opt => $dest ) {
					$oid = $this->add_prefix($opt);
					$o = get_option($oid, $d);
					// Only migrate valid values
					if ( $o !== $d ) {
						// Process destinations
						foreach ( $dest as $id ) {
							$this->set_data($id, $o, false, true);
						}
					}
					// Remove legacy option
					delete_option($oid);
				}
			}
			
			/* Simple Migration (Internal options only) */
			
			// Get existing items that are also legacy items
			$opts = array_intersect_key($this->get_data(), $l);
			foreach ( $opts as $opt => $val ) {
				$d = $this->get_data($opt);
				// Migrate data from old option to new option
				$dest = $l[$opt];
				// Validate new options to send data to
				foreach ( $dest as $id ) {
					$this->set_data($id, $d, false, true);
				}
				// Remove legacy option
				$this->remove($opt, false);
			}
		}
		// Save changes
		$this->save();
		// Set flag
		$this->items_migrated = true;
	}
	
	/* Option setup */
	
	/**
	 * Get elements for creating fields
	 * @return obj
	 */
	function get_field_elements() {
		static $o = null;
		if ( empty($o) ) {
			$o = new stdClass();
			/* Layout */
			$layout = new stdClass();
			$layout->label = '<label for="{field_id}" class="title block">{label}</label>';
			$layout->label_ref = '{label ref_base="layout"}';
			$layout->field_pre = '<div class="input block">';
			$layout->field_post = '</div>';
			$layout->opt_pre = '<div class="' . $this->add_prefix('option_item') . '">';
			$layout->opt_post = '</div>';
			$layout->form = '<{form_attr ref_base="layout"} /> <span class="description">(' . __('Default', 'simple-lightbox') . ': {data context="display" top="0"})</span>';
			/* Combine */
			$o->layout =& $layout;
		}
		return $o;
	}
	
	/**
	 * Register option-specific fields
	 * @param SLB_Fields $fields Reference to global fields object
	 * @return void
	 */
	function register_fields($fields) {
		// Layouts
		$o = $this->get_field_elements();
		$l =& $o->layout;
		
		$form = implode('', array (
			$l->opt_pre,
			$l->label_ref,
			$l->field_pre,
			$l->form,
			$l->field_post,
			$l->opt_post
		));
		
		// Text input
		$otxt = new SLB_Field_Type('option_text', 'text');
		$otxt->set_property('class', '{inherit} code');
		$otxt->set_property('size', null);
		$otxt->set_property('value', '{data context="form"}');
		$otxt->set_layout('label', $l->label);
		$otxt->set_layout('form', $form);
		$fields->add($otxt);
		
		// Checkbox
		$ocb = new SLB_Field_Type('option_checkbox', 'checkbox');
		$ocb->set_layout('label', $l->label);
		$ocb->set_layout('form', $form);
		$fields->add($ocb);
		
		// Select
		$othm = new SLB_Field_Type('option_select', 'select');
		$othm->set_layout('label', $l->label);
		$othm->set_layout('form_start', $l->field_pre . '{inherit}');
		$othm->set_layout('form_end', '{inherit}' . $l->field_post);
		$othm->set_layout('form', $l->opt_pre . '{inherit}' . $l->opt_post);
		$fields->add($othm);
	}
	
	/**
	 * Set parent field types for options
	 * Parent only set for Admin pages
	 * @uses SLB_Option::set_parent() to set parent field for each option item
	 * @uses is_admin() to determine if current request is admin page
	 * @param object $fields Collection of default field types
	 * @return void
	 */
	function set_parents($fields) {
		if ( !is_admin() )
			return false;
		$items = &$this->get_items();
		foreach ( array_keys($items) as $opt ) {
			$items[$opt]->set_parent();
		}
		foreach ( $this->items as $opt ) {
			$p = $opt->parent;
			if ( is_object($p) )
				$p = 'o:' . $p->id;
		}
	}
	
	/* Processing */
	
	/**
	 * Validate option values
	 * Used for validating options (e.g. admin form submission) prior to saving options to DB
	 * Reformats values based on options' default values (i.e. bool, int, string, etc.)
	 * Adds option items not included in original submission 
	 * @param array $values (optional) Option values
	 * @return array Full options data
	 */
	function validate($values = null) {
		$qvar = $this->get_id('formatted');
		if ( empty($values) && isset($_REQUEST[$qvar]) ) {
			$values = $_REQUEST[$qvar];
		}
		if ( is_array($values) ) {
			// Format data based on option type (bool, string, etc.)
			foreach ( $values as $id => $val ) {
				// Get default
				$d = $this->get_default($id);
				if ( is_bool($d) && !empty($val) )
					$values[$id] = true;
			}
			
			// Merge in additional options that are not in post data
			// Missing options (e.g. disabled checkboxes, empty fields, etc.)
			
			// Get groups that were output in request
			$qvar_groups = $qvar . '_groups';
			if ( isset($_REQUEST[$qvar_groups]) ) {
				$groups = explode( ',', implode(',', $_REQUEST[$qvar_groups]) );

				// Get group items				
				$items = array();
				$items_temp = null;
				foreach ( $groups as $gid ) {
					$items_temp = $this->get_group_items($gid);
					$items = array_merge($items, $items_temp);
				}
				unset($items_temp);
				$items = call_user_func_array('array_merge', $items);
				foreach ( $items as $id => $opt ) {
					// Add options that were not included in form submission
					if ( !array_key_exists($id, $values) ) {
						if ( is_bool($opt->get_default()) )
							$values[$id] = false;
						else
							$values[$id] = $opt->get_default();
					}
				}
			}
		}
		
		// Return value
		return $values;
	}
	
	/* Data */
	
	/**
	 * Retrieve options from database
	 * @uses get_option to retrieve option data
	 * @return array Options data
	 */
	function fetch_data($sanitize = true) {
		// Get data
		$data = get_option($this->get_key(), null);
		if ( $sanitize && is_array($data) ) {
			// Sanitize loaded data based on default values
			foreach ( $data as $id => $val ) {
				if ( $this->has($id) ) {
					$opt = $this->get($id);
					if ( is_bool($opt->get_default()) )
						$data[$id] = !!$val;
				}
			}
		}
		return $data;
	}
	
	/**
	 * Retrieves option data for collection
	 * @see SLB_Field_Collection::load_data()
	 */
	function load_data() {
		if ( !$this->data_loaded ) {
			// Retrieve data
			$this->data = $this->fetch_data();
			$this->data_loaded = true;
			// Check update
			$this->check_update();
		}
	}
	
	/**
	 * Resets option values to their default values
	 * @param bool $hard Reset all options if TRUE (default), Reset only unset options if FALSE
	 */
	function reset($hard = true) {
		$this->load_data();
		// Reset data
		if ( $hard ) {
			$this->data = null;
		}
		// Save
		$this->save();
	}
	
	/**
	 * Save options data to database
	 */
	function save() {
		$this->normalize_data();
		update_option($this->get_key(), $this->data);
	}
	
	/**
	 * Normalize data
	 * Assures that data in collection match items
	 * @uses self::data to reset and save collection data after normalization
	 */
	function normalize_data() {
		$data = array();
		foreach ( $this->get_items() as $id => $opt ) {
			$data[$id] = $opt->get_data();
		}
		$this->data =& $data;
		return $data;
	}

	/* Collection */
	
	/**
	 * Build key for saving/retrieving data to options table
	 * @return string Key
	 */
	function get_key() {
		return $this->add_prefix($this->get_id());
	}
	
	/**
	 * Add option to collection
	 * @uses SLB_Field_Collection::add() to add item
	 * @param string $id Unique item ID
	 * @param array $properties Item properties
	 * @param bool $update (optional) Should item be updated or overwritten (Default: FALSE)
	 * @return SLB_Option Option instance
	 */
	function &add($id, $properties = array(), $update = false) {
		// Create item
		$args = func_get_args();
		$ret = call_user_func_array(array('parent', 'add'), $args); 
		return $ret;
	}
	
	/**
	 * Retrieve option value
	 * @uses get_data() to retrieve option data
	 * @param string $option Option ID to retrieve value for
	 * @param string $context (optional) Context for formatting data
	 * @return mixed Option value
	 */
	function get_value($option, $context = '') {
		return $this->get_data($option, $context);
	}
	
	/**
	 * Retrieve option value as boolean (true/false)
	 * @uses get_data() to retrieve option data
	 * @param string $option Option ID to retrieve value for
	 * @return bool Option value
	 */
	function get_bool($option) {
		return $this->get_value($option, 'bool');
	}
	
	function get_string($option) {
		return $this->get_value($option, 'string');
	}
	
	/**
	 * Retrieve option's default value
	 * @uses get_data() to retrieve option data
	 * @param string $option Option ID to retrieve value for
	 * @param string $context (optional) Context for formatting data
	 * @return mixed Option's default value
	 */
	function get_default($option, $context = '') {
		return $this->get_data($option, $context, false);
	}
	
	/* Output */
	
	function build_init() {
		if ( $this->build_vars['validate_pre'] ) {
			$values = $this->validate();
			if ( $this->build_vars['save_pre'] ) {
				$this->set_data($values);
			}
		}
	}
	
	/**
	 * Build array of option values for client output
	 * @return array Associative array of options
	 */
	function build_client_output() {
		$items = $this->get_items();
		$out = array();
		foreach ( $items as $option ) {
			if ( !$option->get_in_client() )
				continue;
			$out[$option->get_id()] = $option->get_data('default');
		}
		return $out;
	}
	
	/* Admin */
	
	/**
	 * Handles output building for options on admin pages
	 * @param obj|array $opts Options instance or Array of options instance and groups to build
	 * @param obj $page Admin Page instance
	 * @param obj $state Admin Page state properties
	 */
	public function admin_page_render_content($opts, $page, $state) {
		$groups = null;
		if ( is_array($opts) && count($opts) == 2 ) {
			$groups = $opts[1];
			$opts = $opts[0];
		}
		if ( $opts === $this ) {
			// Set build variables and callbacks
			$this->set_build_var('admin_page', $page);
			$this->set_build_var('admin_state', $state);
			if ( !empty($groups) ) {
				$this->set_build_var('groups', $groups);
			}
			$hooks = array (
				'filter'	=> array (
					'parse_build_vars'		=> array( $this->m('admin_parse_build_vars'), 10, 2 )
				)
			);
			
			// Add hooks
			foreach ( $hooks as $type => $hook ) {
				$m = 'add_' . $type;
				foreach ( $hook as $tag => $args ) {
					array_unshift($args, $tag);
					call_user_func_array($this->util->m($m), $args);
				}
			}
			
			// Build output
			$this->build(array('build_groups' => $this->m('admin_build_groups')));
			
			// Remove hooks
			foreach ( $hooks as $type => $hook ) {
				$m = 'remove_' . $type;
				foreach ( $hook as $tag => $args ) {
					call_user_func($this->util->m($m), $tag, $args[0]);
				}
			}
			// Clear custom build vars
			$this->delete_build_var('admin_page');
			$this->delete_build_var('admin_state');
		}
	}

	/**
	 * Builds option groups output
	 */
	public function admin_build_groups() {
		$page = $this->get_build_var('admin_page');
		$state = $this->get_build_var('admin_state');
		$groups = $this->get_build_var('groups');
		
		// Get all groups
		$groups_all = $this->get_groups();
		$groups_built = array();
		if ( empty($groups) ) {
			$groups = array_keys($groups_all);
		}
		// Iterate through groups
		foreach ( $groups as $gid ) {
			// Validate
			if ( !isset($groups_all[$gid]) || !count($this->get_items($gid)) ) {
				continue;
			}
			// Add meta box for each group
			$g = $groups_all[$gid];
			add_meta_box($g->id, $g->title, $this->m('admin_build_group'), $state->screen, $state->context, $state->priority, array('group' => $g->id, 'page' => $page));
			$groups_built[] = $gid;
		}
		
		// Define groups built
		if ( !empty($groups_built) ) {
			echo $this->util->build_html_element(array(
				'tag'			=> 'input',
				'attributes'	=> array (
					'type'	=> 'hidden',
					'value'	=> implode(',', $groups_built),
					'name'	=> $this->get_id('formatted') . '_groups[]'
				),
			));
		}
	}
	
	/**
	 * Group output handler for admin pages
	 * @param obj $obj Object passed by `do_meta_boxes()` call (Default: NULL)
	 * @param array $box Meta box properties
	 */
	public function admin_build_group($obj, $box) {
		$a = $box['args'];
		$group = $a['group'];
		$this->build_group($group);
	}
	
	/**
	 * Parse build vars
	 * @uses `options_parse_build_vars` filter hook
	 */
	public function admin_parse_build_vars($vars, $opts) {
		// Handle form submission
		if ( isset($_REQUEST[$opts->get_id('formatted')]) ) {
			$vars['validate_pre'] = $vars['save_pre'] = true;
		}
		return $vars;
	}

	/**
	 * Admin reset handler
	 * @param bool $res Current result
	 * @param obj $opts Options instance
	 * @param obj $reset Admin Reset instance
	 */
	public function admin_action_reset($res, $opts, $reset) {
		// Only process matching options instance
		if ( $opts === $this ) {
			// Reset options
			$this->reset();
			// Set result
			$res = true;
		}
		return $res;
	}
}