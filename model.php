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
		'header_enabled'			=> 'Activation',
		'enabled'					=> array(true, 'Enable Lightbox Functionality'),
		'enabled_home'				=> array(true, 'Enable on Home page'),
		'enabled_single'			=> array(true, 'Enable on Single Posts/Pages'),
		'enabled_archive'			=> array(true, 'Enable on Archive Pages (tags, categories, etc.)'),
		'activate_links'			=> array(true, 'Activate all image links on page'),
		'header_activation'			=> 'Grouping',
		'group_links'				=> array(true, 'Group automatically activated links (for displaying as a slideshow)'),
		'group_post'				=> array(true, 'Group image links by Post (e.g. on pages with multiple posts)'),
		'header_ui'					=> 'UI',
		'autostart'					=> array(true, 'Automatically Start Slideshow'),
		'duration'					=> array(6, 'Slide Duration (Seconds)', array('size' => 3, 'maxlength' => 3)),
		'loop'						=> array(true, 'Loop through images'),
		'overlay_opacity'			=> array(0.8, 'Overlay Opacity (0 - 1)', array('size' => 3, 'maxlength' => 3)),
		'header_strings'			=> 'Labels',
		'txt_closeLink'				=> array('close', 'Close link (for accessibility only, image used for button)'),
		'txt_loadingMsg'			=> array('loading', 'Loading indicator'),
		'txt_nextLink'				=> array('next &raquo;', 'Next Image link'),
		'txt_prevLink'				=> array('&laquo; prev', 'Previous Image link'),
		'txt_startSlideshow'		=> array('start slideshow', 'Start Slideshow link'),
		'txt_stopSlideshow'			=> array('stop slideshow', 'Stop Slideshow link'),
		'txt_numDisplayPrefix'		=> array('Image', 'Image number prefix (e.g. <strong>Image</strong> x of y)'),
		'txt_numDisplaySeparator'	=> array('of', 'Image number separator (e.g. Image x <strong>of</strong> y)')
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
		/* Admin */
		//Init lightbox admin
		add_action('admin_init', $this->m('admin_settings'));
		//Enqueue header files (CSS/JS)
		add_action('admin_enqueue_scripts', $this->m('admin_enqueue_files'));
		//Reset Settings
		add_action('admin_action_' . $this->add_prefix('reset'), $this->m('admin_reset'));
		add_action('admin_notices', $this->m('admin_notices'));
		
		/* Client-side */
		//Init lightbox (client-side)
		add_action('wp_enqueue_scripts', $this->m('enqueue_files'));
		add_action('wp_head', $this->m('client_init'));
		add_filter('plugin_action_links_' . $this->util->get_plugin_base_name(), $this->m('admin_plugin_action_links'), 10, 4);
		add_filter('the_content', $this->m('activate_post_links'));
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
				$ret = htmlentities($ret);
			}
		} elseif ( ! is_array($this->options_default[$option]) ) {
			$ret = $this->options_default[$option];
		}
		return $ret;
	}

	/*-** Frontend **-*/
	
	/**
	 * Scans post content for image links and activates them
	 * 
	 * Lightbox will not be activated for feeds
	 * @param $content
	 */
	function activate_post_links($content) {
		//Check option
		if ( ! is_feed() && $this->is_enabled() && $this->get_option_value('activate_links') && $this->get_option_value('group_links') && $this->get_option_value('group_post') ) {
			//Scan for links
			$matches = array();
			if ( preg_match_all("/\<a[^\>]*href=[^\s]+\.(?:jp[e]*g|gif|png).*?\>/i", $content, $matches) ) {
				global $post;
				//Iterate through links & add lightbox if necessary
				foreach ($matches[0] as $link) {
					//Check if rel attribute exists
					$link_new = $link;
					$rel = '';
					if ( strpos(strtolower($link_new), ' rel=') !== false && preg_match("/\s+rel=(?:\"|')(.*?)(?:\"|')(\s|\>)/i", $link_new, $rel) ) {
						//Check if lightbox is already set in rel attribute
						$link_new = str_replace($rel[0], $rel[2], $link_new);
						$rel = $rel[1];
					}
					
					if ( strpos($rel, 'lightbox') === false) {
						//Add rel attribute to link
						$rel .= ' lightbox[' . $this->add_prefix($post->ID) . ']';
						$link_new = '<a rel="' . $rel . '"' . substr($link_new,2);
						//Insert modified link
						$content = str_replace($link, $link_new, $content);
					}
				}
			}
		}
		return $content; 
	}
	
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
		//Load UI Strings
		if ( ($strings = $this->build_strings()) && !empty($strings) )
			$lb_obj[] = $strings;
		$js_code[] = 'Lightbox.initialize({' . implode(',', $lb_obj) . '});';
		echo $out['script_start'] . implode('', $js_code) . $out['script_end'];
	}
	
	/**
	 * Build JS object of UI strings when initializing lightbox
	 * @return string JS object of UI strings
	 */
	function build_strings() {
		$ret = '';
		$prefix = 'txt_';
		$opt_strings = array_filter(array_keys($this->options_default), create_function('$opt', 'return ( strpos($opt, "' . $prefix . '") === 0 );'));
		if ( $opt_strings ) {
			$strings = array();
			foreach ( $opt_strings as $key ) {
				$name = substr($key, strlen($prefix));
				$strings[] = "'" . $name . "':'" . $this->get_option_value($key) . "'";
			}
			$ret = "'strings':{" . implode(',', $strings) . "}";
		}
		return $ret;
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
			$settings = __('Settings');
			$reset = __('Reset');
			$reset_confirm = "'" . __('Are you sure you want to reset your settings?') . "'";
			$action = $this->add_prefix('reset');
			$reset_link = wp_nonce_url(add_query_arg('action', $action, remove_query_arg(array($this->add_prefix('action'), 'action'), $_SERVER['REQUEST_URI'])), $action);
			array_unshift($actions, '<a class="delete" href="options-media.php#' . $this->admin_get_settings_section() . '" title="' . $settings . '">' . $settings . '</a>');
			array_splice($actions, 1, 0, '<a id="' . $this->add_prefix('reset') . '" href="' . $reset_link . '" onclick="return confirm(' . $reset_confirm . ');" title="' . $reset . '">' . $reset . '</a>');
		}
		return $actions;
	}
	
	/**
	 * Reset plugin settings
	 * Redirects to referring page upon completion
	 */
	function admin_reset() {
		//Validate user
		if ( ! current_user_can('activate_plugins') || ! check_admin_referer($this->add_prefix('reset')) )
			wp_die(__('You do not have sufficient permissions to manage plugins for this blog.'));
		$action = 'reset';
		if ( isset($_REQUEST['action']) && $this->add_prefix($action) == $_REQUEST['action'] ) {
			//Reset settings
			$this->reset_options(true);
			$uri = remove_query_arg(array('_wpnonce', 'action'), add_query_arg(array($this->add_prefix('action') => $action), $_SERVER['REQUEST_URI']));
			//Redirect user
			wp_redirect($uri);
			exit;
		}
	}
	
	/**
	 * Displays notices for admin operations
	 */
	function admin_notices() {
		if ( is_admin() && isset($_REQUEST[$this->add_prefix('action')]) ) {
			$action = $_REQUEST[$this->add_prefix('action')];
			$msg = null;
			if ( $action ) {
				switch ( $action ) {
					case 'reset':
						$msg = "Simple Lightbox's settings have been <strong>reset</strong>";
						break; 
				}
				if ( ! is_null($msg) ) {
					?>
					<div id="message" class="updated fade"><p><?php _e($msg); ?></p></div>
					<?php 
				}
			}
		}
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
			$label = ( isset($defaults[1]) ) ? $defaults[1] : '';
			$callback = ( method_exists($this, $func) ) ? $this->m($func) : $this->m('admin_field_default');
			$args = array('opt' => $key);
			//Check if option is a section header
			if ( ! is_array($defaults) ) {
				$label = '<h4 class="subhead">' . $defaults . '</h4>';
				$callback = $this->m('admin_field_header');
			} elseif ( is_null(get_option($id, null)) ) {
				//Add option to DB if not yet set
				$args['label_for'] = $id;
				update_option($id, htmlentities2($defaults[0]));
			}
			add_settings_field($id, __($label), $callback, $page, $section, $args);
			register_setting($page, $id);
		}
	}
	
	function admin_enqueue_files() {
		if ( is_admin() && basename($_SERVER['SCRIPT_NAME']) == $this->options_admin_page ) {
			wp_enqueue_style($this->add_prefix('admin_styles'), $this->util->get_file_url('css/admin.css'));
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
		if ( empty($format) && $format !== false )
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

		echo sprintf($format, $opt->id, htmlentities($opt->value), $opt->value_default_formatted, $attr);
	}
	
	/**
	 * Builds header for settings subsection
	 * @param array $args Arguments set in admin_settings
	 */
	function admin_field_header($args) {
		$opt = ( isset($args['opt']) ) ? $args['opt'] : '';
		$this->admin_the_field($opt, false, 'header');
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