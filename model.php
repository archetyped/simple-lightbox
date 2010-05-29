<?php 

require_once 'includes/class.base.php';

/**
 * Lightbox functionality class
 * @package Simple Lightbox
 * @author Archetyped
 */
class SLB_Lightbox extends SLB_Base {
	
	/*-** Properties **-*/
	
	/**
	 * Default options
	 * 0: Value
	 * 1: Label
	 * @var array
	 */
	var $options_default = array (
		'enabled'			=>	array(true, 'Enable Lightbox Functionality'),
		'autostart'			=>	array(true, 'Automatically Start Slideshow'),
		'duration'			=>	array(6, 'Slide Duration (Seconds)'),
		'loop'				=>	array(true, 'Loop through images'),
		'overlay_opacity'	=>	array(0.8, 'Overlay Opacity (0 - 1)'),
	);
	
	/*-** Init **-*/
	
	function SLB_Lightbox() {
		$this->__construct();
	}
	
	function __construct() {
		parent::__construct();
		$this->init();
	}
	
	function init() {
		$this->register_hooks();
	}
	
	function register_hooks() {
		register_activation_hook($this->util->get_plugin_base_file(), $this->m('activate'));
		//Init lightbox admin
		add_action('admin_init', $this->m('admin_settings'));
		
		//Init lightbox (client-side)
		add_action('wp_enqueue_scripts', $this->m('enqueue_files'));
		add_action('wp_head', $this->m('client_init'));
		
	}
	
	function activate() {
		//Set default options (if not yet set)
		$this->reset_options(false);
	}
	
	/**
	 * Resets option values to their default values
	 * @param bool $hard Reset all options if TRUE (default), Reset only unset options if FALSE
	 */
	function reset_options($hard = true) {
		foreach ( $this->options_default as $id => $data ) {
			$opt = $this->add_prefix($id);
			if ( !$hard && !is_null(get_option($opt, null)) ) {
				continue;
			}
			update_option($opt, $data[0]);
		}
	}
	
	/*-** Helpers **-*/
	
	/**
	 * Checks whether lightbox is currently enabled/disabled
	 * @return bool TRUE if lightbox is currently enabled, FALSE otherwise
	 */
	function is_enabled() {
		return ( get_option($this->add_prefix('enabled')) ) ? true : false;
	}
	
	/**
	 * Builds object of option data
	 * Properties:
	 * > id: Option ID
	 * > value: Option's value (uses default value if option not yet set)
	 * > value_default: Option's default value (formatted)
	 * 
	 * @param string $option Option name
	 * @return object Option data
	 */
	function get_option($option) {
		$ret = new stdClass();
		$ret->id = $this->add_prefix($option);
		$ret->value = get_option($ret->id, $this->get_default_value($option, false));
		$ret->value_default = $this->get_default_value($option);
		return $ret;
	}
	
	/**
	 * Retrieve default value for specified option
	 * @param string $option Option name
	 * @param bool $formatted Whether to return formatted value (e.g. for use in admin UI)
	 * @return mixed Option default value
	 */
	function get_default_value($option, $formatted = true) {
		$ret = '';
		if ( isset($this->options_default[$option][0]) ) {
			$ret = $this->options_default[$option][0];
			//Format value (if required)
			if ( $formatted ) {
				if ( is_bool($ret) || ( is_string($ret) && 'on' == $ret ) )
					$ret = ( $ret ) ? 'Enabled' : 'Disabled';
				if ( is_numeric($ret) )
					$ret = strval($ret);
			}
		}
		return $ret;
	}

	/*-** Frontend **-*/
	
	/**
	 * Enqueue files in template head
	 */
	function enqueue_files() {
		if ( ! $this->is_enabled() )
			return;
		wp_enqueue_script($this->add_prefix('lib'), $this->util->get_file_url('js/lib.js'));
		wp_enqueue_style($this->add_prefix('lightbox_css'), $this->util->get_file_url('css/lightbox.css'));
	}
	
	/**
	 * Sets options/settings to initialize lightbox functionality on page load
	 * @return void
	 */
	function client_init() {
		if ( ! $this->is_enabled() )
			return;
			
		$options = array();
		$out = array();
		$out['script_start'] = '<script type="text/javascript">Event.observe(window,"load",function(){ Lightbox.initialize(';
		$out['script_end'] = '); });</script>';
		//Get options
		$options['autoPlay'] = get_option($this->add_prefix('lb_autostart'));
		$options['slideTime'] = get_option($this->add_prefix('lb_duration'));
		$options['loop'] = get_option($this->add_prefix('lb_loop'));
		$options['overlayOpacity'] = get_option($this->add_prefix('lb_overlay_opacity'));
		$obj = '{';
		foreach ($options as $option => $val) {
			if ($val === TRUE || $val == 'on')
				$val = 'true';
			elseif ($val === FALSE || empty($val))
				$val = 'false';
			$obj .= "'{$option}': {$val},";
		}
		$obj = rtrim($obj, ',');
		$obj .= '}';
		echo $out['script_start'] . $obj . $out['script_end'];
	}
	
	
	/*-** Admin **-*/
	
	/**
	 * Adds settings section for Lightbox functionality
	 * Section is added to Settings > Media Admin menu
	 */
	function admin_settings() {
		$page = 'options-media.php';
		$form = 'options.php';
		$curr = basename($_SERVER['SCRIPT_NAME']);	
		if ( $curr != $page && $curr != $form ) {
			return;
		}
		
		$page = 'media';
		$section = $this->get_prefix();
		//Section
		add_settings_section($section, 'Lightbox Settings', $this->m('admin_section'), $page);
		//Fields
		foreach ($this->options_default as $key => $defaults) {
			$id = $section . '_' . $key;
			$callback = $this->m('admin_' . $key);
			add_settings_field($id, __($defaults[1]), $callback, $page, $section, array('label_for' => $id));
			register_setting($page, $id);
		}
	}
	
	/**
	 * Placeholder function for lightbox admin settings
	 * Required because setting init function requires a callback
	 */
	function admin_section() { }
	
	/**
	 * General field builder
	 * @param string $option Option name to build field for
	 * @param string $format Field markup (using sprintf specifiers)
	 * @param string $type (optional) Type of field being build (e.g. checkbox, text, etc.)
	 * Specifiers:
	 * 1. Field ID
	 * 2. Field Value
	 * 3. Field Default Value (formatted)
	 * 4. Field Type
	 */
	function admin_the_field($option, $format, $type = 'text') {
		$opt = $this->get_option($option);
		//Adjust type and value formatting based on type
		if ( ! empty($type) && is_string($type) ) {
			switch ( $type ) {
				case 'checkbox' :
					if ( $opt->value )
						$type .= '" checked="checked';
					break;
			}
			
			$type = 'type="' . $type . '"';
		} else {
			$type = '';
		}
		echo sprintf($format, $opt->id, $opt->value, $opt->value_default, $type);
	}
	
	/**
	 * Lightbox setting - Enabled/Disabled
	 * @return void
	 */
	function admin_enabled() {
		$opt = 'enabled';
		$type = 'checkbox';
		$format = '<input %4$s id="%1$s" name="%1$s" class="code" /> (Default: %3$s)';
		$this->admin_the_field($opt, $format, $type);
	}
	
	/**
	 * Lightbox setting - Slideshow autostart
	 * @return void
	 */
	function admin_autostart() {
		$opt = 'autostart';
		$type = 'checkbox';
		$format = '<input %4$s id="%1$s" name="%1$s" class="code" /> (Default: %3$s)';
		$this->admin_the_field($opt, $format, $type);
	}
	
	/**
	 * Lightbox setting - Slide duration
	 * @return void
	 */
	function admin_duration() {
		$opt = 'duration';
		$format = '<input %4$s size="3" maxlength="3" value="%2$s" id="%1$s" name="%1$s" class="code" /> (Default: %3$s)';
		$this->admin_the_field($opt, $format);
	}
	
	/**
	 * Lightbox setting - Looping
	 * @return void
	 */
	function admin_loop() {
		$opt = 'loop';
		$type = 'checkbox';
		$format = '<input %4$s id="%1$s" name="%1$s" class="code" /> (Default: %3$s)';
		$this->admin_the_field($opt, $format, $type);
	}
	
	/**
	 * Lightbox setting - Overlay Opacity
	 * @return void
	 */
	function admin_overlay_opacity() {
		$opt = 'overlay_opacity';
		$format = '<input %4$s size="3" maxlength="5" value="%2$s" id="%1$s" name="%1$s" class="code" /> (Default: %3$s)';
		$this->admin_the_field($opt, $format);
	}
}

?>