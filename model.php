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
	 * Page that plugin options are on
	 * @var string
	 */
	var $options_admin_page = 'options-media.php';
	
	/**
	 * Page that processes options
	 * @var string
	 */
	var $options_admin_form = 'options.php';
	
	/**
	 * Default options
	 * 0: Value
	 * 1: Label
	 * @var array
	 */
	var $options_default = array (
		'enabled'			=> array(true, 'Enable Lightbox Functionality'),
		'enabled_home'		=> array(true, 'Enable on Home page'),
		'enabled_single'	=> array(true, 'Enable on Single Posts/Pages'),
		'enabled_archive'	=> array(true, 'Enable on Archive Pages (tags, categories, etc.)'),
		'activate_links'	=> array(true, 'Automatically setup for all links to images on page'),
		'group_links'		=> array(true, 'Group automatically activated links (for displaying as a slideshow)'),
		'autostart'			=> array(true, 'Automatically Start Slideshow'),
		'duration'			=> array(6, 'Slide Duration (Seconds)', array('size' => 3, 'maxlength' => 3)),
		'loop'				=> array(true, 'Loop through images'),
		'overlay_opacity'	=> array(0.8, 'Overlay Opacity (0 - 1)', array('size' => 3, 'maxlength' => 3)),
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
		add_filter('plugin_action_links_' . $this->util->get_plugin_base_name(), $this->m('admin_plugin_action_links'), 10, 4);
		
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
	function is_enabled($check_request = true) {
		$ret = ( get_option($this->add_prefix('enabled')) ) ? true : false;
		if ( $ret && !! $check_request ) {
			$opt = '';
			//Determine option to check
			if ( is_home() )
				$opt = 'home';
			elseif ( is_single() )
				$opt = 'single';
			elseif ( is_archive() || is_search() )
				$opt = 'archive';
			//Check option
			if ( ! empty($opt) && ( $opt = 'enabled_' . $opt ) && isset($this->options_default[$opt]) ) {
				$ret = ( get_option($this->add_prefix($opt)) ) ? true : false;
			}
		}
		return $ret;
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
		$ret->value_default = $this->get_default_value($option, false);
		$ret->value_default_formatted = $this->get_default_value($option);
		$ret->attr = $this->get_default_attr($option);
		return $ret;
	}
	
	/**
	 * Retrieve an option's value
	 * @param string $option Option name
	 * @return mixed Option value
	 */
	function get_option_value($option) {
		$opt = $this->get_option($option);
		return $opt->value;
	}
	
	/**
	 * Retrieve default attributes for an option
	 * @param string $option Option name
	 * @return array Default attributes
	 */
	function get_default_attr($option) {
		$ret = array();
		if ( isset($this->options_default[$option][2]) )
			$ret = $this->options_default[$option][2];
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
		$out['script_start'] = '<script type="text/javascript">Event.observe(window,"load",function(){';
		$out['script_end'] = '});</script>';
		$js_code = array();
		//Activate links on page
		if ( $this->get_option_value('activate_links') ) {
			$rel = ( $this->get_option_value('group_links') ) ? 'lightbox[' . $this->get_prefix() . ']' : 'lightbox';
			ob_start();
			?>
			$$('a[href$=".jpg"]:not([rel~="lightbox"])','a[href$=".jpeg"]:not([rel~="lightbox"])','a[href$=".gif"]:not([rel~="lightbox"])','a[href$=".png"]:not([rel~="lightbox"])').each(function(el){if (! /(^|\b)lightbox\[.+\]($|\b)/i.test(el.rel)){var rel=(el.rel.length > 0) ? el.rel + ' ' : '';el.rel=rel + '<?php echo $rel; ?>';}});
			<?php
			$js_code[] = ob_get_clean();
		}
		//Get options
		$options = array(
			'autoPlay'			=> $this->get_option_value('autostart'),
			'slideTime'			=> $this->get_option_value('duration'),
			'loop'				=> $this->get_option_value('loop'),
			'overlayOpacity'	=> $this->get_option_value('overlay_opacity')
		);
		$lb_obj = array();
		foreach ($options as $option => $val) {
			if ($val === TRUE || $val == 'on')
				$val = 'true';
			elseif ($val === FALSE || empty($val))
				$val = 'false';
			$lb_obj[] = "'{$option}':{$val}";
		}
		$js_code[] = 'Lightbox.initialize({' . implode(',', $lb_obj) . '});';
		echo $out['script_start'] . implode('', $js_code) . $out['script_end'];
	}
	
	
	/*-** Admin **-*/
	
	/**
	 * Adds custom links below plugin on plugin listing page
	 * @param $actions
	 * @param $plugin_file
	 * @param $plugin_data
	 * @param $context
	 */
	function admin_plugin_action_links($actions, $plugin_file, $plugin_data, $context) {
		//Add link to settings (only if active)
		if ( is_plugin_active($this->util->get_plugin_base_name()) ) {
			array_unshift($actions, '<a href="options-media.php#' . $this->admin_get_settings_section() . '" title="' . __('Settings') . '">' . __('Settings') . '</a>');
		}
		return $actions;
	}
	
	/**
	 * Adds settings section for Lightbox functionality
	 * Section is added to Settings > Media Admin menu
	 */
	function admin_settings() {
		$page = $this->options_admin_page;
		$form = $this->options_admin_form;
		$curr = basename($_SERVER['SCRIPT_NAME']);	
		if ( $curr != $page && $curr != $form ) {
			return;
		}
		
		$page = 'media';
		$section = $this->get_prefix();
		//Section
		add_settings_section($section, '<span id="' . $this->admin_get_settings_section() . '">' . __('Lightbox Settings') . '</span>', $this->m('admin_section'), $page);
		//Fields
		foreach ($this->options_default as $key => $defaults) {
			$id = $this->add_prefix($key);
			$func = 'admin_field_' . $key;
			$callback = ( method_exists($this, $func) ) ? $this->m($func) : $this->m('admin_field_default');
			//Add option to DB if not yet set
			if ( is_null(get_option($id, null)) )
				update_option($id, $defaults[0]);
			add_settings_field($id, __($defaults[1]), $callback, $page, $section, array('label_for' => $id, 'opt' => $key));
			register_setting($page, $id);
		}
	}
	
	/**
	 * Get ID of settings section on admin page
	 * @return string ID of settings section
	 */
	function admin_get_settings_section() {
		return $this->add_prefix('settings');
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
	function admin_the_field($option, $format = '', $type = '') {
		$opt = $this->get_option($option);
		$format_default = '<input id="%1$s" name="%1$s" %4$s class="code" /> (Default: %3$s)';
		if ( empty($format) )
			$format = $format_default;
		if ( empty($type) || !is_string($type) ) {
			$type_default = 'text';
			$type = ( is_bool($opt->value_default) ) ? 'checkbox' : $type_default;
		}
		//Adjust type and value formatting based on type
		switch ( $type ) {
			case 'checkbox' :
				if ( $opt->value )
					$opt->attr['checked'] = 'checked';
				break;
			case 'text' :
				if ( $format == $format_default )
					$format = str_replace('%4$s', '%4$s value="%2$s"', $format);
				break;
		}
		$opt->attr['type'] = $type;
		//Build attribute string
		$attr = '';
		if ( ! empty($opt->attr) ) {
			$attr = $this->util->build_attribute_string($opt->attr);
		}

		echo sprintf($format, $opt->id, $opt->value, $opt->value_default_formatted, $attr);
	}
	
	/**
	 * Default field output generator
	 * @param array $args Arguments set in admin_settings
	 */
	function admin_field_default($args = array()) {
		$opt = ( isset($args['opt']) ) ? $args['opt'] : '';
		$this->admin_the_field($opt);
	}
}

?>