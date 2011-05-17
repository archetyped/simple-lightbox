<?php
require_once 'class.base.php';

class SLB_Field {}
class SLB_Field_Collection {}

/**
 * Option object
 * @package Simple Lightbox
 * @subpackage Options
 * @author SM
 */
class SLB_Option extends SLB_Field {
	
	/* Init */
	
	function SLB_Option() {
		$this->__construct();
	}
	
	function __construct() {
		parent::__construct();
	}
}

/**
 * Options collection
 * @package Simple Lightbox
 * @subpackage Options
 * @author SM
 * @uses SLB_Field_Collection
 * @todo Create parent class
 */
class SLB_Options extends SLB_Field_Collection {
	
	/* Properties */
	
	var $groups = array();
	
	var $options = array();

	/* Collection */
	
	function add_option($props, $group = '') {
		
	}
	
	/* Group */
	
	function add_group($id, $properties) {
		
	}
	
	function add_to_group($option, $group) {
		
	}
	
	function get_groups() {
		
	}
	
	
}

/**
 * Plugin options management class
 * 
 * @package Simple Lightbox
 * @subpackage Options
 * @author Archetyped
 *
 */
class SLB_Options_OLD extends SLB_Base {
	/**
	 * Name of option in DB
	 * Prefixed during construction
	 * @var string
	 */
	var $name = 'options';
	
	/**
	 * Groups for organizing options
	 * @var unknown_type
	 */
	var $groups = array();
	
	/**
	 * Key used to store configuration options
	 * @var string
	 */
	var $key_config = 'config';
	
	/**
	 * Key for option value property
	 * @var string
	 */
	var $config_value = 'value';
	
	/**
	 * Key for option default value property
	 * @var string
	 */
	var $config_default = 'default';
	
	/**
	 * Key for option label property
	 * @var string
	 */
	var $config_label = 'label';
	
	/**
	 * Key for option description property
	 * @var string
	 */
	var $config_desc = 'desc';
	
	/**
	 * Default config data (Set at initialization)
	 * @var array
	 */
	var $config_data_default = array();
	
	/*-** Init **-*/

	/**
	 * Legacy constructor
	 * @param $config @see __construct
	 */
	function SLB_Options($config = null) {
		$this->__construct($config);
	}
	
	/**
	 * Constructor
	 * @param array $config Default configuration options
	 */
	function __construct($config = null) {
		parent::__construct();
		$this->name = $this->add_prefix($this->name);
//		$this->set_config(array());
		//Set default config data
		if ( is_array($config) ) {
			//Set up groups
			if ( isset($config['groups']) ) {
				$this->add_groups($config['groups']);
			}
			if ( isset($config['options']) )
				$this->add_options($config['options']);
			$this->config_data_default = $config;
		}
	}
	
	/*-** Methods **-*/
	
	/* Getters/Setters */
	
	/**
	 * Retrieve configuration option
	 * @param string Option name
	 * @param mixed Default value
	 * @return mixed Specified option's data
	 */
	function get($key = null, $default = null) {
		if ( $this->exists($key) ) {
			if ( $this->has_value($key) ) {
				return $this->get_config_item($key, $this->config_value);
			}
			return $this->get_default($key);
		}
		return $default;
	}
	
	/**
	 * Retrieve default option value
	 * @param $key Option name
	 * @return mixed Default value or FALSE if option does not exist
	 */
	function get_default($key) {
		if ( $this->exists($key) ) {
			return $this->get_config_item($key, $this->config_default);
		}
		return false;
	}
	
	/**
	 * Set configuration option data
	 * @param $key Option to set data for
	 * @param $data Option data
	 */
	function set($key, $data = null, $label = null, $desc = null) {
		$prop = ( $this->exists($key) ) ? $this->config_value : $this->config_default;
		$data = array($prop => $data, $this->config_label => $label, $this->config_desc => $desc);
		$this->set_config_item($key, $data); 
	}
	
	/**
	 * Checks if option has a saved value
	 * @param string $key Option name
	 * @return bool TRUE if option has a saved value
	 */
	function has_value($key) {
		return $this->exists($key, $this->config_value);
	}
	
	/**
	 * Check if config option exists
	 * @param $key Option name
	 * @return bool TRUE if option exists, FALSE otherwise
	 */
	function exists($key, $prop = false) {
		$ret = false;
		$config = $this->get_config();
		$key = strval($key);
		$ret = ( isset($config[$key]) );
		if ( $ret && is_string($prop) && !isset($config[$key][$prop]) )
			$ret = false;
		return $ret;
	}
	
	/* Groups */
	
	/**
	 * Add multiple groups
	 * @uses add_group()
	 * @param array $groups Groups to add
	 * @return void
	 */
	function add_groups($groups) {
		if ( is_array($groups) ) {
			foreach ( $groups as $id => $props ) {
				$this->add_group($id, $props);
			}
		}
	}
	
	/**
	 * Add group to object
	 * @uses $groups
	 * @param string $id Unique group ID
	 * @param array $props Group properties
	 * @return void
	 */
	function add_group($id, $props) {
		if ( !is_array($this->groups) )
			$this->groups = array();
		$this->groups[$id] = $props;
	}
	
	/* Configuration Options */
	
	/**
	 * Add multiple option at once
	 * @uses add_option()
	 * @param array $options Options to add
	 * @return void
	 */
	function add_options($options) {
		if ( is_array($options) ) {
			foreach ( $options as $id => $props )
				$this->add_option($id, $props);
		}
	}
	
	/**
	 * Add option to object
	 * @param string $id Unique option ID
	 * @param array $props Option properties
	 * @return void
	 */
	function add_option($id, $props = array()) {
		
	}
	
	/**
	 * Create configuration option item
	 * Associative array with keys for option properties
	 * default: Default value
	 * label: Option label
	 * desc: Description
	 * @param $args
	 * @return array Normalized option item
	 */
	function make_config_item($args = null) {
		$default_args = array($this->config_default => null, $this->config_label => '', $this->config_desc => '');
		$args = wp_parse_args($args, $default_args);
		return $args;
	}
	
	/**
	 * Retrieve option item
	 * @see make_config_item
	 * @param $key Option name
	 * @return mixed Option item or value of specified property
	 */
	function get_config_item($key, $prop = false) {
		$config = $this->get_config();
		$ret = null;
		
		if ( is_string($key) && ( isset($config[$key]) || isset($this->config_data_default[$key]) ) ) {
			$val = ( isset($config[$key]) ) ? $config[$key] : array();
			$def = ( isset($this->config_data_default[$key]) ) ? $this->config_data_default[$key] : array();
			$ret = wp_parse_args($val, $def);
		}
		
		$ret = $this->make_config_item($ret);
		
		//Retrieve option property (if specified and available)
		if ( !empty($prop) && isset($ret[$prop]) ) {
			$ret = $ret[$prop];
		}
		
		return $ret;
	}
	
	/**
	 * Save option item to DB
	 * @param string $key Option name
	 * @param array $args Option data
	 */
	function set_config_item($key, $args) {
		//Validate config data (must contain value)
		if ( !isset($args[$this->config_value]) )
			return false;
		//Remove default option properties
		if ( count($args) > 1 )
			$args = $this->remove_config_default($args);
		//Save config data
		$config = $this->get_config_saved();
		$config[$key] = $args;
		$this->set_config($config, false);
	}
	
	/**
	 * Remove default properties from option item 
	 * Useful for stripping default data before saving option data
	 * @param array $item Option data
	 * @return array Associative array of user-defined option data
	 */
	function remove_config_default($item) {
		$props_save = array_diff(array_keys($item), array_keys($this->make_config_item()));
		$ret = array();
		foreach ( $props_save as $prop ) {
			$ret[$prop] = $item[$prop];
		}
		return $ret;
	}
	
	/**
	 * Retrieve configuration options
	 * @return array Associative array of configuration options
	 */
	function get_config() {
			return $this->util->array_merge_recursive_distinct($this->config_data_default, $this->get_config_saved());
	}
	
	/**
	 * Retrieve saved option data
	 * Values only
	 * @return array Saved option data
	 */
	function get_config_saved() {
		return $this->get_data($this->key_config, array());
	}
	
	/**
	 * Save configuration options to DB
	 * Default mode is direct overwrite 
	 * @param array $config Configuration options
	 * @param bool $clean (optional) Whether to strip all options of default properties
	 */
	function set_config($config = array(), $clean = true) {
		//Validate
		if ( !is_array($config) )
			$config = array();
		//Strip default properties from array items
		if ( !empty($config) && $clean ) {
			$config = array_map($this->m('remove_config_default'), $config);
		} 
		//Skip if data is unchanged
		if ( $config == $this->get_config_saved() )
			return false;
		//Save to DB
		$this->set_data($this->key_config, $config);
	}
	
	/* Low-level Data Handling */
	
	/**
	 * Retrieve arbitrary data saved to options instance
	 * @param string $key Data key
	 * @param mixed $default Default value to return if key does not exist
	 * @return mixed Specified data
	 */
	function get_data($key = null, $default = null) {
		$opts = get_option($this->name, false);
		if ( is_string($key) && is_array($opts) ) {
			return ( isset($opts[$key]) ) ? $opts[$key] : $default;
		}
		if ( !is_array($opts) ) {
			$opts = array();
			update_option($this->name, $opts);
		}
		return $opts;
	}
	
	/**
	 * Set arbitrary data in options instance
	 * @param string $key Data key
	 * @param mixed $data Data to set
	 */
	function set_data($key = null, $data = null) {
		$opts = $this->get_data();
		//Set data
		if ( func_num_args() == 1 && is_array($key) ) {
			$opts = $key;
		}
		elseif ( func_num_args() > 1 && is_string($key) ) {
			$opts[$key] = $data;
		}
		//Save to db
		update_option($this->name, $opts);
	}
	
	/* Output */
	
	/**
	 * Output form for configuration options
	 */
	function form() {
		$opts = array();
		//Build form elements for valid config options
		foreach ( $this->get_config() as $key => $data ) {
			if ( !$this->is_form_field($key) )
				continue;
			$id = $this->get_form_id($key);
			$opts[] = '<tr valign="top"><th scope="row"><label for="' . $id . '">' . $data[$this->config_label] . '</label></th><td>' . $this->get_form_element($key, $data) . '</td></tr>';
		}
		//Build form 
		if ( !empty($opts) ) {
			array_unshift($opts, '<form method="post" action="' . esc_attr($_SERVER['REQUEST_URI']) . '">', '<table class="form-table">');
			$opts[] = '</table>';
			$opts[] = '<p class="submit"><input class="button-primary" type="submit" value="' . __('Save Changes') . '" name="' . $this->add_prefix('submit') . '" /></p>';
			$opts[] = '</form>';
		}
		echo implode('', $opts);
	}
	
	/**
	 * Handle form submission
	 */
	function handle_form() {
		if ( isset($_POST[$this->add_prefix('submit')]) ) {
			$postdata = ( isset($_POST[$this->add_prefix($this->key_config)]) ) ? $_POST[$this->add_prefix($this->key_config)] : array();
			//Iterate through fields and set option values
			$config = $this->get_config();
			foreach ( $config as $key => $data ) {
				//Skip non form items
				if ( !$this->is_form_field($key) )
					continue;
				//Handle different option types (based on default values)
				$val_default = $this->get_default($key);
				//Boolean
				if ( is_bool($val_default) ) {
					$config[$key][$this->config_value] = ( isset($postdata[$key]) ) ? true : false;
				}
			 	//Default: Set form value
			 	else {
			 		if ( isset($postdata[$key]) )
			 			$config[$key][$this->config_value] = $postdata[$key];
			 	}
			}
			$this->set_config($config);
		}
	}
	
	/**
	 * Check whether config option is intended to be manipulated via a form
	 * @param $key Option name
	 * @return bool TRUE if option is intended for forms
	 */
	function is_form_field($key) {
		$data = $this->get_config_item($key);
		return ( !empty($data[$this->config_label]) );
	}
	
	/**
	 * Generate valid form ID for option 
	 * @param string $key Option name
	 * @return string form ID for option
	 */
	function get_form_id($key) {
		static $lkey = '';
		static $id = '';
		if ( $key != $lkey ) {
			$id = $this->add_prefix(array($this->key_config, $key));	
		}
		return $id;
	}
	
	/**
	 * Generate valid form name for option
	 * ID will be part of 'config' Post data array when submitted
	 * @param $key Option name
	 * @return string Form name
	 */
	function get_form_name($key) {
		return $this->add_prefix($this->key_config) . '[' . $key . ']';
	}
	
	/**
	 * Field builder for config options
	 * @param string $key Option name
	 * @param array $data(optional) Option data
	 * @return string Form element (HTML)
	 */
	function get_form_element($key, $data = null) {
		$ret = 'Element';
		if ( !is_array($data) || empty($data) )
			$data = $this->get_config_item($key);
		$attr = array('value' => $this->get($key), 'name' => $this->get_form_name($key));
		$attr['id'] = $this->get_form_id($key);
		
		//Determine field type
		$type_default = 'text';
		if ( is_bool($attr['value']) ) {
			$attr['type'] = 'checkbox';
		} else {
			$attr['type'] =  $type_default;
		}
		
		//Adjust type and value formatting based on type
		switch ( $attr['type'] ) {
			case 'checkbox' :
				if ( $attr['value'] )
					$attr['checked'] = 'checked';
				break;
		}
		$ret = $this->util->build_html_element(array('tag' => 'input', 'wrap' => false, 'attributes' => $attr));
		return $ret;
	}
}