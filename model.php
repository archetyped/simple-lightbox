<?php 

require_once 'includes/class.base.php';

/**
 * Lightbox functionality class
 * @package Simple Lightbox
 * @author Archetyped
 */
class SLB_Lightbox extends SLB_Base {
	
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
		$fields = array(
						'enabled'			=>	__( 'Enable Lightbox Functionality' ),
						'autostart'			=>	__( 'Automatically Start Slideshow' ),
						'duration'			=>	__( 'Slide Duration (Seconds)' ),
						'loop'				=>	__( 'Loop through images' ),
						'overlay_opacity'	=>	__( 'Overlay Opacity (0 - 1)' )
						);
		foreach ($fields as $key => $title) {
			$id = $section . '_' . $key;
			$callback = $this->m('admin_' . $key);
			add_settings_field($id, $title, $callback, $page, $section, array('label_for' => $id));
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
		$checked = '';
		$id = $this->add_prefix('enabled');
		if (get_option($id))
			$checked = ' checked="checked" ';
		$format = '<input type="checkbox" %1$s id="%2$s" name="%2$s" class="code" /> (Default: Yes)';
		echo sprintf($format, $checked, $id);
	}
	
	/**
	 * Lightbox setting - Slideshow autostart
	 * @return void
	 */
	function admin_autostart() {
		$checked = '';
		$id = $this->add_prefix('autostart');
		if (get_option($id))
			$checked = ' checked="checked" ';
		$format = '<input type="checkbox" %1$s id="%2$s" name="%2$s" class="code" /> (Default: Yes)';
		echo sprintf($format, $checked, $id);
	}
	
	/**
	 * Lightbox setting - Slide duration
	 * @return void
	 */
	function admin_duration() {
		$val = 6;
		$id = $this->add_prefix('duration');
		$opt = get_option($id); 
		if ($opt) $val = $opt;
		$format = '<input type="text" size="3" maxlength="3" value="%1$s" id="%2$s" name="%2$s" class="code" /> (Default: 6)';
		echo sprintf($format, $val, $id);
	}
	
	/**
	 * Lightbox setting - Looping
	 * @return void
	 */
	function admin_loop() {
		$checked = '';
		$id = $this->add_prefix('loop');
		if (get_option($id))
			$checked = ' checked="checked" ';
		$format = '<input type="checkbox" %1$s id="%2$s" name="%2$s" class="code" /> (Default: Yes)';
		echo sprintf($format, $checked, $id);
	}
	
	/**
	 * Lightbox setting - Overlay Opacity
	 * @return void
	 */
	function admin_overlay_opacity() {
		$val = 0.8;
		$id = $this->add_prefix('overlay_opacity');
		$opt = get_option($id); 
		if ($opt) $val = $opt;
		$format = '<input type="text" size="3" maxlength="5" value="%1$s" id="%2$s" name="%2$s" class="code" /> (Default: 0.8)';
		echo sprintf($format, $val, $id);
	}
}

?>