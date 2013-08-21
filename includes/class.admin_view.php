<?php

/**
 * Admin View Base
 * Core functionality Admin UI components 
 * @package Simple Lightbox
 * @subpackage Admin
 * @author Archetyped
 */
class SLB_Admin_View extends SLB_Base_Object {
	/* Properties */
	
	/**
	 * Labels
	 * @var array (Associative)
	 */
	protected $labels = array();
	
	/**
	 * Options object to use
	 * @var SLB_Options
	 */
	protected $options = null;
	
	/**
	 * Option groups to use
	 * If empty, use entire options object
	 * @var array
	 */
	protected $option_groups = array();
	
	/**
	 * Option building arguments
	 * @var array
	 */
	protected $option_args = array();
	
	/**
	 * Function to handle building UI
	 * @var callback
	 */
	protected $callback = null;
	
	/**
	 * Capability for access control
	 * @var string 
	 */
	protected $capability = 'manage_options';
	
	/**
	 * Icon to use
	 * @var string
	 */
	protected $icon = null;
	
	/**
	 * View parent ID/Slug
	 * @var string
	 */
	protected $parent = null;
	
	/**
	 * Whether parent is a custom view or a default WP one
	 * @var bool
	 */
	protected $parent_custom = true;
		
	/**
	 * If view requires a parent
	 * @var bool
	 */
	protected $parent_required = false;
	
	/**
	 * WP-Generated hook name for view
	 * @var string
	 */
	protected $hookname = null;
	
	/**
	 * Raw content parameters
	 * Stores pre-rendered content parameters
	 * Items stored by ID (key)
	 * @var array
	 */
	protected $content_raw = array();
	
	/**
	 * Parsed content parameters
	 * @var array
	 */
	protected $content = array();
	
	/**
	 * Messages to be displayed
	 * Indexed Array
	 * @var array
	 */
	protected $messages = array();
	
	/**
	 * Required properties
	 * Associative array
	 * > Key: Property name
	 * > Value: Required data type
	 * @var array
	 */
	protected $required = array();
	
	/**
	 * Default required properties
	 * Merged into $required array with this->init_required()
	 * @see this->required for more information
	 * @var array
	 */
	private $_required = array ( 'id' => 'string', 'labels' => 'array' );
	
	/* Init */
	
	/**
	 * Constructor
	 * @return obj Current instance
	 */
	public function __construct($id, $labels, $options = null, $callback = null, $capability = null, $icon = null) {
		$props = array(
			'labels'		=> $labels,
			'options'		=> $options,
			'callback'		=> $callback,
			'capability'	=> $capability,
			'icon'			=> $icon,
		);
		parent::__construct($id, $props);
		$this->init_required();
		return $this;
	}
	
	protected function init_required() {
		$this->required = array_merge($this->_required, $this->required);
		//Check for parent requirement
		if ( $this->parent_required )
			$this->required['parent'] = 'string';
	}
	
	/* Property Methods */
	
	/**
	 * Retrieve ID (Formatted by default)
	 * @param bool $formatted (optional) Whether ID should be formatted for external use or not
	 * @return string ID
	 */
	public function get_id($formatted = true) {
		$id = parent::get_id();
		if ( $formatted )
			$this->add_prefix_ref($id);
		return $id;
	}
	
	/**
	 * Retrieve raw ID
	 * @return string Raw ID
	 */
	public function get_id_raw() {
		return $this->get_id(false);
	}
	
	/**
	 * Retrieve label
	 * Uses first label (or default if defined) if specified type does not exist
	 * @param string $type Label type to retrieve
	 * @param string $default (optional) Default value if label type does not exist
	 * @return string Label text
	 */
	public function get_label($type, $default = null) {
		//Retrieve existing label type
		if ( $this->has_label($type) )
			return $this->labels[$type];
		//Use default label if type is not set
		if ( empty($default) && !empty($this->labels) ) {
			reset($this->labels);
			$default = current($this->labels);
		}
		
		return ( empty($default) ) ? '' : $default;
	}
	
	/**
	 * Set text labels
	 * @param array|string $labels
	 * @return obj Current instance
	 */
	public function set_labels($labels) {
		if ( empty($labels) )
			return this;
		//Single string
		if ( is_string($labels) ) {
			$labels = array ( $labels );
		} 
		
		//Array
		if ( is_array($labels) ) {
			//Merge with existing labels
			if ( empty($this->labels) || !is_array($this->labels) ) {
				$this->labels = array();
			}
			$this->labels = array_merge($this->labels, $labels);
		}
		return $this;
	}
	
	/**
	 * Set single text label
	 * @uses this->set_labels()
	 * @param string $type Label type to set
	 * @param string $value Label value
	 * @return obj Current instance
	 */
	public function set_label($type, $value) {
		if ( is_string($type) && is_string($value) ) {
			$label = array( $type => $value );
			$this->set_labels($label);
		}
		return $this;
	}
	
	/**
	 * Checks if specified label is set on view
	 * @param string $type Label type
	 * @return bool TRUE if label exists, FALSE otherwise
	 */
	public function has_label($type) {
		return ( isset($this->labels[$type]) );
	}
	
	/* Content */
	
	/**
	 * Add content block to view
	 * Child classes define method functionality
	 * @param string $id Content block ID
	 * @param array $args Content arguments (Defined by child class), converted to an object
	 * @return obj Current View instance
	 */
	public function add_content($id, $args) {
		//Save parameters
		$this->content_raw[$id] = (object) $args;
		//Clear parsed content
		$this->content = array();
		//Return instance reference
		return $this;
	}
	
	/**
	 * Retrieve content
	 */
	protected function get_content($parsed = true) {
		$content = $this->content_raw;
		if ( $parsed ) {
			//Return previously parsed content
			if ( !empty($this->content) ) {
				$content = $this->content;
			}
			elseif ( !empty($this->content_raw) ) {
				//Parse content before returning
				$content = $this->content = $this->parse_content();
			}
		}
		return $content;
	}
	
	/**
	 * Parse content
	 * Child classes define functionality
	 * @return array Parsed content
	 */
	protected function parse_content() {
		return $this->get_content(false);
	}
	
	/**
	 * Check if content has been added to view
	 * @return bool TRUE if content added
	 */
	protected function has_content() {
		$raw = $this->get_content(false);
		return !empty($raw);
	}
	
	/**
	 * Render content
	 */
	protected function render_content($context = 'default') {
	}
	
	/* Options */
	
	/**
	 * Retrieve instance options
	 * @return SLB_Options Options instance
	 */
	public function &get_options() {
		return $this->options;
	}
	
	/**
	 * Set options object
	 * @param obj|array $options Options instance
	 *  > If array, Options instance and specific groups are specified
	 *   > 0: Options instance
	 * 	 > 1: Group(s)
	 * @return obj Current instance
	 */
	public function set_options($options) {
		if ( empty($options) )
			return $this;
		
		$groups = null;
		
		if ( is_array($options) ) {
			$options = array_values($options);
			//Set option groups
			if ( isset($options[1]) ) {
				$groups = $options[1];
			}
			//Set options object
			$options =& $options[0];
		}
		
		if ( $this->util->is_a($options, 'Options') ) {
			//Save options
			$this->options =& $options;
			
			//Save option groups for valid options
			$this->set_option_groups($groups);
		}
		return $this;
	}
	
	/**
	 * Set option groups
	 * @param string|array $groups Specified group(s)
	 * @return obj Current instance
	 */
	public function set_option_groups($groups) {
		if ( empty($groups) )
			return $this;
		
		//Validate data
		if ( !is_array($groups) ) {
			if ( is_scalar($groups) ) {
				$groups = array(strval($groups));
			}
		}
		
		if ( is_array($groups) ) {
			$this->option_groups = $groups;
		}
		return $this;
	}
	
	/**
	 * Retrieve view messages
	 * @return array Messages
	 */
	protected function &get_messages() {
		if ( !is_array($this->messages) )
			$this->messages = array();
		return $this->messages;
	}
	
	/**
	 * Save message
	 * @param string $text Message text
	 * @return obj Current instance
	 */
	public function set_message($text) {
		$msgs =& $this->get_messages();
		$text = trim($text);
		if ( empty($msgs) && !empty($text) )
			$this->util->add_filter('admin_messages', $this->m('do_messages'));
		$msgs[] = $text;
		return $this;
	}
	
	/**
	 * Add messages to array
	 * Called by internal `admin_messages` filter hook
	 * @param array $msgs Aggregated messages
	 * @return array Merged messages array
	 */
	public function do_messages($msgs = array()) {
		$m =& $this->get_messages();
		if ( !empty($m) )
			$msgs = array_merge($msgs, $m);
		return $msgs;
	}
	
	/**
	 * Retrieve view callback
	 * @return callback Callback (Default: standard handler method)
	 */
	public function get_callback() {
		return ( $this->has_callback() ) ? $this->callback : $this->m('handle');
	}
	
	/**
	 * Set callback function for building item
	 * @param callback $callback Callback function to use
	 * @return obj Current instance
	 */
	public function set_callback($callback) {
		$this->callback = ( is_callable($callback) ) ? $callback : null;
		return $this;
	}
	
	/**
	 * Check if callback set
	 * @return bool TRUE if callback is set
	 */
	protected function has_callback() {
		return ( !empty($this->callback) ) ? true : false;
	}
	
	/**
	 * Run callback
	 */
	public function do_callback() {
		call_user_func($this->get_callback());
	}
	
	/**
	 * Retrieve capability
	 * @return string Capability
	 */
	public function get_capability() {
		return $this->capability;
	}
	
	/**
	 * Set capability for access control
	 * @param string $capability Capability
	 * @return obj Current instance
	 */
	public function set_capability($capability) {
		if ( is_string($capability) && !empty($capability) )
			$this->capability = $capability;
		return $this;
	}
	
	/**
	 * Set icon
	 * @param string $icon Icon URI
	 * @return obj Current instance
	 */
	public function set_icon($icon) {
		if ( !empty($icon) && is_string($icon) )
			$this->icon = $icon;
		return $this;
	}
	
	protected function get_hookname() {
		return ( empty($this->hookname) ) ? '' : $this->hookname;
	}
	
	/**
	 * Set hookname
	 * @param string $hookname Hookname value
	 * @return obj Current instance
	 */
	public function set_hookname($hookname) {
		if ( !empty($hookname) && is_string($hookname) )
			$this->hookname = $hookname;
		return $this;
	}
	
	/**
	 * Retrieve parent
	 * Formats parent ID for custom parents
	 * @uses parent::get_parent()
	 * @return string Parent ID
	 */
	public function get_parent() {
		$parent = parent::get_parent();
		return ( $this->is_parent_custom() ) ? $this->add_prefix($parent) : $parent;
	}
	
	/**
	 * Set parent for view
	 * @param string $parent Parent ID
	 * @return obj Current instance
	 */
	public function set_parent($parent) {
		if ( $this->parent_required ) {
			if ( !empty($parent) && is_string($parent) )
			$this->parent = $parent;
		} else {
			$this->parent = null;
		}
		return $this;
	}
		
	/**
	 * Specify whether parent is a custom view or a WP view
	 * @param bool $custom (optional) TRUE if custom, FALSE if WP
	 * @return obj Current instance
	 */
	protected function set_parent_custom($custom = true) {
		if ( $this->parent_required ) {
			$this->parent_custom = !!$custom;
		}
		return $this;
	}
	
	/**
	 * Set parent as WP view
	 * @uses this->set_parent_custom()
	 * @return obj Current instance
	 */
	public function set_parent_wp() {
		return $this->set_parent_custom(false);
	}
	
	/**
	 * Get view URI
	 * URI Structures:
	 *  > Top Level Menus: admin.php?page={menu_id}
	 *  > Pages: [parent_page_file.php|admin.php]?page={page_id}
	 * 	> Section: [parent_menu_uri]#{section_id}
	 * 
	 * @uses $admin_page_hooks to determine if page is child of default WP page
	 * @return string Object URI 
	 */
	public function get_uri($file = null, $format = null) {
		static $page_hooks = null;
		$uri = '';
		if ( empty($file) )
			$file = 'admin.php';
		if ( $this->is_child() ) {
			$parent = str_replace('_page_' . $this->get_id(), '', $this->get_hookname());
			if ( is_null($page_hooks) ) {
				$page_hooks = array_flip($GLOBALS['admin_page_hooks']);
			}
			if ( isset($page_hooks[$parent]) )
				$file = $page_hooks[$parent];
		}
		
		if ( empty($format) ) {
			$delim = ( strpos($file, '?') === false ) ? '?' : '&amp;';
			$format = '%1$s' . $delim . 'page=%2$s';
		}
		$uri = sprintf($format, $file, $this->get_id());

		return $uri;
	}
	
	/* Handlers */
	
	/**
	 * Default View handler
	 * Used as callback when none set
	 */
	public function handle() {}
	
	/* Validation */
	
	/**
	 * Check if instance is valid based on required properties/data types
	 * @return bool TRUE if valid, FALSE if not valid 
	 */
	public function is_valid() {
		$valid = true;
		foreach ( $this->required as $prop => $type ) {
			if ( empty($this->{$prop} )
				|| ( !empty($type) && is_string($type) && ( $f = 'is_' . $type ) && function_exists($f) && !$f($this->{$prop}) ) ) {
				$valid = false;
				break;
			}
		}
		return $valid;
	}
	
	protected function is_child() {
		return $this->parent_required;
	}
	
	protected function is_parent_custom() {
		return ( $this->is_child() && $this->parent_custom ) ? true : false;
	}
	
	public function is_parent_wp() {
		return ( $this->is_child() && !$this->parent_custom ) ? true : false;
	}
	
	public function is_options_valid() {
		$opts = $this->get_options();
		return ( is_object($opts) && $this->util->is_a($opts, $this->util->get_class('Options')) ) ? true : false;
	}
	
	/* Options */
	
	/**
	 * Parse options build vars
	 * @uses `options_parse_build_vars` filter hook
	 */
	public function options_parse_build_vars($vars, $opts) {
		//Handle form submission
		if ( isset($_REQUEST[$opts->get_id('formatted')]) ) {
			$vars['validate_pre'] = $vars['save_pre'] = true;
		}
		return $vars;
	}
	
	/**
	 * Actions to perform before building options
	 */
	public function options_build_pre(&$opts) {
		//Build form output
		$form_id = $this->add_prefix('admin_form_' . $this->get_id_raw());
		?>
		<form id="<?php esc_attr_e($form_id); ?>" name="<?php esc_attr_e($form_id); ?>" action="" method="post">
		<?php
	}
	
	/**
	 * Actions to perform after building options
	 */
	public function options_build_post(&$opts)	{
		submit_button();
		?>
		</form>
		<?php
	}
	
	/**
	 * Builds option groups output
	 * @param SLB_Options $options Options instance
	 * @param array $groups Groups to build
	 */
	public function options_build_groups($options, $groups) {
		//Add meta box for each group
		$screen = get_current_screen();
		foreach ( $groups as $gid ) {
			$g = $options->get_group($gid);
			if ( !count($options->get_items($gid)) ) {
				continue;
			}
			add_meta_box($gid, $g->title, $this->m('options_build_group'), $screen, 'normal', 'default', array('options' => $options, 'group' => $gid));
		}
		//Build options
		do_meta_boxes($screen, 'normal', null);
	}
	
	public function options_build_group($obj, $args) {
		$args = $args['args'];
		$group = $args['group'];
		$opts = $args['options'];
		$opts->build_group($group);
	}
	
	protected function show_options($show_submit = true) {
		//Build options output
		if ( !$this->is_options_valid() ) {
			return false;
		}
		/**
		 * @var SLB_Options
		 */
		$opts =& $this->get_options();
		$hooks = array (
			'filter'	=> array (
				'parse_build_vars'		=> array( $this->m('options_parse_build_vars'), 10, 2 )
			),
			'action'	=> array (
				'build_pre'				=> array( $this->m('options_build_pre') ),
				'build_post'			=> array ( $this->m('options_build_post') ),
			)
		);
		//Add hooks
		foreach ( $hooks as $type => $hook ) {
			$m = 'add_' . $type;
			foreach ( $hook as $tag => $args ) {
				array_unshift($args, $tag);
				call_user_func_array($opts->util->m($m), $args);
			}
		}
		?>
		<div class="metabox-holder">
		<?php
		//Build output
		$opts->build(array('build_groups' => $this->m('options_build_groups')));
		?>
		</div>
		<?php
		//Remove hooks
		foreach ( $hooks as $type => $hook ) {
			$m = 'remove_' . $type;
			foreach ( $hook as $tag => $args ) {
				call_user_func($opts->util->m($m), $tag, $args[0]);
			}
		}
	}

	/* UI Elements */
	
	/**
	 * Build submit button element
	 * @param string $text (optional) Button text
	 * @param string $id (optional) Button ID (prefixed on output)
	 * @param object $parent (optional) Page/Section object that contains button
	 * @return object Button properties (id, output)
	 */
	protected function get_button_submit($text = null, $id = null, $parent = null) {
		//Format values
		if ( !is_string($text) || empty($text) )
			$text = __('Save Changes');
		if ( is_object($parent) && isset($parent->id) )
			$parent = $parent->id . '_';
		else
			$parent = '';
		if ( !is_string($id) || empty($id) )
			$id = 'submit';
		$id = $this->add_prefix($parent . $id);
		//Build HTML
		$out = $this->util->build_html_element(array(
			'tag'			=> 'input',
			'wrap'			=> false,
			'attributes'	=> array(
				'type'	=> 'submit',
				'class'	=> 'button-primary',
				'id'	=> $id,
				'name'	=> $id,
				'value'	=> $text
			)
		));
		$out = '<p class="submit">' . $out . '</p>';
		$ret = new stdClass;
		$ret->id = $id;
		$ret->output = $out;
		return $ret;
	}
	
	/**
	 * Output submit button element
	 * @param string $text (optional) Button text
	 * @param string $id (optional) Button ID (prefixed on output)
	 * @param object $parent (optional) Page/Section object that contains button
	 * @return object Button properties (id, output)
	 */
	protected function button_submit($text = null, $id = null, $parent = null) {
		$btn = $this->get_button_submit($text, $id, $parent);
		echo $btn->output;
		return $btn;
	}
}