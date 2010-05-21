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
		//Init lightbox admin
		add_action('admin_init', $this->m('admin_settings'));
		
		//Init lightbox (client-side)
		add_action('wp_enqueue_scripts', $this->m('enqueue_files'));
		add_action('wp_head', $this->m('client_init'));
		
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
	 * Retrieve default value for specified option
	 * @param string $option Option name
	 * @param bool $formatted Whether to return formatted value (e.g. for use in admin UI)
	 * @return mixed Option default value
	 */
	function get_default_value($option, $formatted = true) {
		$ret = '';
		if ( isset($this->options_default[$option]) ) {
			$ret = $this->options_default[$option];
			//Format value (if required)
			if ( $formatted ) {
				if ( is_bool($ret) )
					$ret = ( $ret ) ? 'Enabled' : 'Disabled';
				if ( is_numeric($ret) )
					$ret = strval($ret);
			}
		}
		return $ret;
	}

	/*-** Frontend **-*/
	
	function enqueue_files() {
		if ( ! $this->is_enabled() || is_admin() )
			return;
		wp_enqueue_script($this->add_prefix('prototype'), $this->util->get_file_url('js/prototype.js'));
		wp_enqueue_script($this->add_prefix('scriptaculous'), $this->util->get_file_url('js/scriptaculous.js?load=effects'), array($this->add_prefix('prototype')));
		wp_enqueue_script($this->add_prefix('lightbox'), $this->util->get_file_url('js/lightbox.js'), array($this->add_prefix('prototype'), $this->add_prefix('scriptaculous')));
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
	 * Lightbox setting - Enabled/Disabled
	 * @return void
	 */
	function admin_enabled() {
		$opt = 'enabled';
		$type = 'checkbox';
		$format = '<input %4$s %1$s id="%2$s" name="%2$s" class="code" /> (Default: %3$s)';
		$this->admin_the_field($opt, $format, $type);
	}
	
	/**
	 * Lightbox setting - Slideshow autostart
	 * @return void
	 */
	function admin_autostart() {
		$opt = 'autostart';
		$type = 'checkbox';
		$format = '<input %4$s %1$s id="%2$s" name="%2$s" class="code" /> (Default: %3$s)';
		$this->admin_the_field($opt, $format, $type);
	}
	
	/**
	 * Lightbox setting - Slide duration
	 * @return void
	 */
	function admin_duration() {
		$opt = 'duration';
		$format = '<input %4$s size="3" maxlength="3" value="%1$s" id="%2$s" name="%2$s" class="code" /> (Default: %3$s)';
		$this->admin_the_field($opt, $format);
	}
	
	/**
	 * Lightbox setting - Looping
	 * @return void
	 */
	function admin_loop() {
		$opt = 'loop';
		$type = 'checkbox';
		$format = '<input %4$s %1$s id="%2$s" name="%2$s" class="code" /> (Default: %3$s)';
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
	
	function admin_the_field($option, $format, $type = 'text') {
		$opt = $this->get_option($option);
		//Adjust type and value formatting based on type
		if ( ! empty($type) && is_string($type) ) {
			switch ( $type ) {
				case 'checkbox' :
					if ( $ret->value )
						$type .= '" checked="checked';
					break;
			}
			
			$type = 'type="' . $type . '"';
		} else {
			$type = '';
		}
		echo sprintf($format, $opt->id, $opt->value, $opt->value_default, $type);
	}
	
	function get_option($option) {
		$ret = new stdClass();
		$ret->id = $this->add_prefix($option);
		$ret->value = get_option($ret->id, $this->get_default_value($option, false));
		$ret->value_default = $this->get_default_value($option);
		return $ret;
	}
}

?>