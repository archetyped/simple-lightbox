<?php
require_once 'class.fields.php';

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
	var $in_client = false;
	
	/**
	 * Child mapping
	 * @see SLB_Field_Base::map
	 * @var array
	 */
	var $map = array (
		'default'	=> 'data',
		'attr'		=> 'properties'
	);
	
	var $property_priority = array ('id', 'data', 'parent');
	
	/* Init */
	
	/**
	 * @see SLB_Field::__construct()
	 * @uses parent::__construct() to initialize instance
	 * @param $id
	 * @param $title
	 * @param $default
	 */
	function __construct($id, $title = '', $default = '') {
		//Normalize properties
		$args = func_get_args();
		$defaults = array ('title' => '', 'default' => '');
		$props = $this->make_properties($args, $defaults);
		//Validate
		if ( is_scalar($id) )
			$props['id'] = $id;
		if ( !is_string($props['title']) )
			$props['title'] = '';
		//Send to parent constructor
		parent::__construct($props);
	}
	
	/* Getters/Setters */
	
	/**
	 * Retrieve default value for option
	 * @return mixed Default option value
	 */
	function get_default($context = '') {
		return $this->get_data($context, false);	
	}
			
	/**
	 * Sets parent based on default value
	 */
	function set_parent($parent = null) {
		$p =& $this->get_parent();
		if ( empty($parent) && empty($p) ) {
			$parent = 'text';
			$d = $this->get_default();
			if ( is_bool($d) )
				$parent = 'checkbox';
			$parent = 'option_' . $parent;
		} elseif ( !empty($p) && !is_object($p) ) {
			$parent =& $p;
		}
		parent::set_parent($parent);
	}
	
	/**
	 * Set in_client property
	 * @uses this::in_client
	 * @param bool Whether or not option should be included in client output (Default: false)
	 * @return void
	 */
	function set_in_client($in_client = false) {
		$this->in_client = !!$in_client;
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
	function format_display($value, $context = '') {
		if ( !is_string($value) ) {
			if ( is_bool($value) ) {
				$value = ( $value ) ? __('Enabled', 'simple-lightbox') : __('Disabled', 'simple-lightbox');
			}
			elseif ( is_null($value) )
				$value = '';
			else
				$value = strval($value);
		} elseif ( empty($value) ) {
			$value = 'empty';
		}
		return htmlentities($value);
	}
	
	/**
	 * Format data using same format as default value
	 * @see SLB_Field_Base::format()
	 * @param mixed $value Data to format
	 * @param string $context (optional) Current context
	 * @return mixed Formatted option value 
	 */
	function format_default($value, $context = '') {
		//Get default value
		$d = $this->get_default();
		if ( empty($d) )
			return $value;
		if ( is_bool($d) )
			$value = $this->format_bool($value);
		elseif ( is_string($d) )
			$value = $this->format_string($value);
		return $value;
	}
	
	/**
	 * Format data as boolean (true/false)
	 * @see SLB_Field_Base::format()
	 * @param mixed $value Data to format
	 * @param string $context (optional) Current context
	 * @return bool Option value
	 */
	function format_bool($value, $context = '') {
		if ( !is_bool($value) )
			$value = !!$value;
		return $value;
	}
	
	/**
	 * Format data as string
	 * @see SLB_Field_Base::format()
	 * @param mixed $value Data to format
	 * @param string $context (optional) Current context
	 * @return string Option string value
	 */
	function format_string($value, $context = '') {
		if ( is_bool($value) ) {
			$value = ( $value ) ? 'true' : 'false';
		} 
		elseif ( is_object($value) ) {
			$value = get_class($value);
		}
		elseif ( is_array($value) ) {
			$value = implode(' ', $value);
		} 
		else {
			$value = strval($value);
		}
		return $value;
	}
}

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
		//Validate arguments
		$args = func_get_args();
		//Set default ID
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
		//Register fields
		add_action($this->add_prefix('register_fields'), $this->m('register_fields'));
		//Set option parents
		add_action($this->add_prefix('fields_registered'), $this->m('set_parents'));
		//Building
		$this->util->add_action('build_init', $this->m('build_init'));
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
			//Get version from DB
			$vo = $this->get_version();
			//Get current version
			$vn = $this->util->get_plugin_version();
			//Compare versions
			if ( $vo != $vn ) {
				//Update saved version
				$this->set_version($vn);
				//Migrate old version to new version
				if ( strcasecmp($vo, $vn) < 0 ) {
					//Force full migration
					$version_changed = true;
				}
			}
			//Migrate
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
		
		//Legacy options
		$d = null;
		$this->load_data();
		
		$items =& $this->get_items();
		
		//Migrate separate options to unified option
		if ( $full ) {
			foreach ( $items as $opt => $props ) {
				$oid = $this->add_prefix($opt);
				$o = get_option($oid, $d);
				if ( $o !== $d ) {
					//Migrate value to data array
					$this->set_data($opt, $o, false);
					//Delete legacy option
					delete_option($oid);
				}
			}
		}
		
		//Migrate legacy items
		if ( is_array($this->properties_init) && isset($this->properties_init['legacy']) && is_array($this->properties_init['legacy']) ) {
			$l =& $this->properties_init['legacy'];
			//Normalize legacy map
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
					//Only migrate valid values
					if ( $o !== $d ) {
						//Process destinations
						foreach ( $dest as $id ) {
							$this->set_data($id, $o, false, true);
						}
					}
					//Remove legacy option
					delete_option($oid);
				}
			}
			
			/* Simple Migration (Internal options only) */
			
			//Get existing items that are also legacy items
			$opts = array_intersect_key($this->get_data(), $l);
			foreach ( $opts as $opt => $val ) {
				$d = $this->get_data($opt);
				//Migrate data from old option to new option
				$dest = $l[$opt];
				//Validate new options to send data to
				foreach ( $dest as $id ) {
					$this->set_data($id, $d, false, true);
				}
				//Remove legacy option
				$this->remove($opt, false);
			}
		}
		//Save changes
		$this->save();
		//Set flag
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
	function register_fields(&$fields) {
		//Layouts
		$o = $this->get_field_elements();
		
		$form = $o->layout->opt_pre . $o->layout->label_ref . $o->layout->field_pre . $o->layout->form . $o->layout->field_post . $o->layout->opt_post;
		
		//Text input
		$otxt = new SLB_Field_Type('option_text', 'text');
		$otxt->set_property('class', '{inherit} code');
		$otxt->set_property('size', null);
		$otxt->set_property('value', '{data context="form"}');
		$otxt->set_layout('label', $o->layout->label);
		$otxt->set_layout('form', $form);
		$fields->add($otxt);
		
		//Checkbox
		$ocb = new SLB_Field_Type('option_checkbox', 'checkbox');
		$ocb->set_layout('label', $o->layout->label);
		$ocb->set_layout('form', $form);
		$fields->add($ocb);
		
		//Select
		$othm = new SLB_Field_Type('option_select', 'select');
		$othm->set_layout('label', $o->layout->label);
		$othm->set_layout('form_start', $o->layout->field_pre . '{inherit}');
		$othm->set_layout('form_end', '{inherit}' . $o->layout->field_post);
		$othm->set_layout('form', $o->layout->opt_pre . '{inherit}' . $o->layout->opt_post);
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
	function set_parents(&$fields) {
		if ( !is_admin() )
			return false;
		$items =& $this->get_items();
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
	function validate($values = null, $force_save = false) {
		if ( empty($values) && isset($_REQUEST[$this->add_prefix('options')]) ) {
			$values_orig = $values;
			if ( is_string($values_orig) ) 
				$force_save = true;
			$values = $_REQUEST[$this->add_prefix('options')];
		}
		if ( is_array($values) ) {
			//Format data based on option type (bool, string, etc.)
			foreach ( $values as $id => $val ) {
				//Get default
				$d = $this->get_default($id);
				if ( is_bool($d) && !empty($val) )
					$values[$id] = true;
			}
			//Merge in additional options that are not in post data
			//Missing options (e.g. disabled checkboxes) & defaults
			$items =& $this->get_items();
			foreach ( $items as $id => $opt ) {
				//Add options that were not included in form submission
				if ( !array_key_exists($id, $values) ) {
					if ( is_bool($opt->get_default()) )
						$values[$id] = false;
					else
						$values[$id] = $opt->get_default();
				}
			}
		}
		
		if ( $force_save ) {
			$this->set_data($values);
			$values = $values_orig;
		}
		
		//Return value
		return $values;
	}
	
	/* Data */
	
	/**
	 * Retrieve options from database
	 * @uses get_option to retrieve option data
	 * @return array Options data
	 */
	function fetch_data($sanitize = true) {
		//Get data
		$data = get_option($this->get_key(), null);
		if ( $sanitize && is_array($data) ) {
			//Sanitize loaded data based on default values
			foreach ( $data as $id => $val ) {
				if ( $this->has($id) ) {
					$opt = $this->get($id);
					if ( is_bool($opt->get_default()) )
						$data[$id] = !!$val;
				}/* else {
					//Remove data that has no matching item
					unset($data[$id]);
				}*/
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
			//Retrieve data
			$this->data = $this->fetch_data();
			$this->data_loaded = true;
			//Check update
			$this->check_update();
		}
	}
	
	/**
	 * Resets option values to their default values
	 * @param bool $hard Reset all options if TRUE (default), Reset only unset options if FALSE
	 */
	function reset($hard = true) {
		$this->load_data();
		//Reset data
		if ( $hard ) {
			$this->data = null;
		}
		//Save
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
		//Create item
		$args = func_get_args();
		$ret =& call_user_func_array(array('parent', 'add'), $args); 
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
		$items =& $this->get_items();
		$out = array();
		foreach ( $items as $option ) {
			if ( !$option->get_in_client() )
				continue;
			$out[$option->get_id()] = $option->get_data('default');
		}
		return $out;
	}
}