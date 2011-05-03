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
	 * Version number
	 * @var string
	 */
	var $version = '1.5.4b4';
	
	/**
	 * Themes
	 * @var array
	 */
	var $themes = array();
	
	var $theme_default = 'default';
	
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
		'enabled_post'				=> array(true, 'Enable on Posts'),
		'enabled_page'				=> array(true, 'Enable on Pages'),				
		'enabled_archive'			=> array(true, 'Enable on Archive Pages (tags, categories, etc.)'),
		'activate_links'			=> array(true, 'Activate all image links in item content'),
		'validate_links'			=> array(true, 'Validate links'),
		'header_activation'			=> 'Grouping',
		'group_links'				=> array(true, 'Group automatically activated links (for displaying as a slideshow)'),
		'group_post'				=> array(true, 'Group image links by Post (e.g. on pages with multiple posts)'),
		'header_ui'					=> 'UI',
		'theme'						=> array('default', 'Theme'),
		'animate'					=> array(true, 'Animate lightbox resizing'),
		'autostart'					=> array(true, 'Automatically Start Slideshow'),
		'duration'					=> array(6, 'Slide Duration (Seconds)', array('size' => 3, 'maxlength' => 3)),
		'loop'						=> array(true, 'Loop through images'),
		'overlay_opacity'			=> array(0.8, 'Overlay Opacity (0 - 1)', array('size' => 3, 'maxlength' => 3)),
		'enabled_caption'			=> array(true, 'Enable caption'),
		'caption_src'				=> array(true, 'Use image URI as caption when link title not set'),
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
		
		//Setup variables
		$this->theme_default = $this->add_prefix($this->theme_default);
		$this->options_default['theme'][0] = $this->theme_default;
	}
	
	function register_hooks() {
		parent::register_hooks();
		
		/* Admin */

		//Init lightbox admin
		add_action('admin_init', $this->m('admin_settings'));
		//Enqueue header files (CSS/JS)
		add_action('admin_enqueue_scripts', $this->m('admin_enqueue_files'));
		//Reset Settings
		add_action('admin_action_' . $this->add_prefix('reset'), $this->m('admin_reset'));
		add_action('admin_notices', $this->m('admin_notices'));
		//Plugin listing
		add_filter('plugin_action_links_' . $this->util->get_plugin_base_name(), $this->m('admin_plugin_action_links'), 10, 4);
		
		/* Client-side */
		
		//Init lightbox
		add_action('wp_enqueue_scripts', $this->m('enqueue_files'));
		add_action('wp_head', $this->m('client_init'));
		add_filter('the_content', $this->m('activate_post_links'), 99);
		
		/* Themes */
		$this->add_action('init_themes', $this->m('init_default_themes'));
	}
	
	function activate() {
		//Set default options (if not yet set)
		$this->reset_options(false);
		//Options migration
		$opt = 'enabled_single';
		if ( $this->option_isset($opt) ) {
			$val = $this->get_option_value($opt);
			$this->update_option('enabled_post', $val);
			$this->update_option('enabled_page', $val);
			$this->delete_option($opt);
		}
	}
	
	/**
	 * Resets option values to their default values
	 * @param bool $hard Reset all options if TRUE (default), Reset only unset options if FALSE
	 */
	function reset_options($hard = true) {
		foreach ( $this->options_default as $id => $data ) {
			$opt = $this->get_option_id($id);
			if ( !is_array($data) || ( !$hard && !is_null(get_option($opt, null)) ) ) {
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
		if ( $ret && $check_request ) {
			$opt = '';
			//Determine option to check
			if ( is_home() )
				$opt = 'home';
			elseif ( is_single() ) {
				if ( is_page() )
					$opt = 'page';
				else
					$opt = 'post';
			}
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
	 * Generate option ID for saving in DB
	 * Prefixes option name with plugin prefix
	 * @param string $option Option to generate ID for
	 * @return string Option ID
	 */
	function get_option_id($option) {
		return $this->add_prefix($option);
	}
	
	function option_isset($option) {
		return !is_null(get_option($this->get_option_id($option), null));
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
		$ret->id = $this->get_option_id($option);
		$ret->value = get_option($ret->id, $this->get_default_value($option, false));
		$ret->value_default = $this->get_default_value($option, false);
		$ret->value_default_formatted = $this->get_default_value($option);
		$ret->attr = $this->get_default_attr($option);
		return $ret;
	}
	
	/**
	 * Delete plugin-specific option
	 * @uses delete_option() to perform DB operations
	 * @param string $option Option name
	 * @return bool TRUE if option deleted from DB
	 */
	function delete_option($option) {
		return delete_option($this->get_option_id($option));
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
	
	function update_option($option, $newvalue) {
		update_option($this->get_option_id($option), $newvalue);
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
	
	/*-** Theme **-*/
	
	/**
	 * Retrieve themes
	 * @uses do_action() Calls 'slb_init_themes' hook to allow plugins to register themes
	 * @uses $themes to return registered themes
	 * @return array Retrieved themes
	 */
	function get_themes() {
		static $fetched = false;
		if ( !$fetched ) {
			$this->themes = array();
			$this->do_action('init_themes');
			$fetched = true;
		}
		
		return $this->themes;
	}
	
	/**
	 * Retrieve theme
	 * @param string $name Name of theme to retrieve
	 * @uses theme_exists() to check for existence of theme
	 * @return array Theme data
	 */
	function get_theme($name = '') {
		$name = strval($name);
		//Default: Get current theme if no theme specified
		if ( empty($name) ) {
			$name = $this->get_option_value('theme');
		}
		if ( !$this->theme_exists($name) )
			$name = $this->theme_default;
		return $this->themes[$name];
	}
	
	/**
	 * Retrieve specific of theme data
	 * @uses get_theme() to retrieve theme data
	 * @param string $name Theme name
	 * @param string $field Theme field to retrieve
	 * @return mixed Field data
	 */
	function get_theme_data($name = '', $field) {
		$theme = $this->get_theme($name);
		return ( isset($theme[$field]) ) ? $theme[$field] : '';
	}
	
	/**
	 * Retrieve theme stylesheet URL
	 * @param string $name Theme name
	 * @uses get_theme_data() to retrieve theme data
	 * @return string Stylesheet URL
	 */
	function get_theme_style($name = '') {
		return $this->get_theme_data($name, 'stylesheet_url');
	}
	
	/**
	 * Retrieve theme layout
	 * @uses get_theme_data() to retrieve theme data
	 * @param string $name Theme name
	 * @param bool $filter (optional) Filter layout based on user preferences 
	 * @return string Theme layout HTML
	 */
	function get_theme_layout($name = '', $filter = true) {
		$l = $this->get_theme_data($name, 'layout');
		//Filter
		if ( !$this->get_option_value('enabled_caption') )
			$l = str_replace($this->get_theme_placeholder('dataCaption'), '', $l);
		return $l;
	}
	
	/**
	 * Check whether a theme exists
	 * @param string $name Theme to look for
	 * @uses get_themes() to intialize themes if not already performed
	 * @return bool TRUE if theme exists, FALSE otherwise
	 */
	function theme_exists($name) {
		$this->get_themes();
		return ( isset($this->themes[trim(strval($name))]) );
	}
	
	/**
	 * Register lightbox theme
	 * @param string $name Unique theme name
	 * @param string $title Display name for theme
	 * @param string $stylesheet_url URL to stylesheet
	 * @param string $layout Layout HTML
	 * @uses $themes to store the registered theme
	 */
	function register_theme($name, $title, $stylesheet_url, $layout) {
		if ( !is_array($this->themes) ) {
			$this->themes = array();
		}
		
		//Validate parameters
		$name = trim(strval($name));
		$title = trim(strval($title));
		$stylesheet_url = trim(strval($stylesheet_url));
		$layout = $this->format_theme_layout($layout);
		
		$defaults = array(
			'name'				=> '',
			'title'				=> '',
			'stylesheet_url' 	=> '',
			'layout'			=> ''
		);
		
		//Add theme to array
		$this->themes[$name] = wp_parse_args(compact(array_keys($defaults), $defaults)); 
	}
	
	/**
	 * Build theme placeholder
	 * @param string $name Placeholder name
	 * @return string Placeholder
	 */
	function get_theme_placeholder($name) {
		return '{' . $name . '}';
	}
	
	/**
	 * Formats layout for usage in JS
	 * @param string $layout Layout to format
	 * @return string Formatted layout
	 */
	function format_theme_layout($layout = '') {
		//Remove line breaks
		$layout = str_replace(array("\r\n", "\n", "\r", "\t"), '', $layout);
		
		//Escape quotes
		$layout = str_replace("'", "\'", $layout);
		
		//Return
		return "'" . $layout . "'";
	}
	
	/**
	 * Add default themes
	 * @uses register_theme() to register the theme(s)
	 */
	function init_default_themes() {
		$name = $this->theme_default;
		$title = 'Default';
		$stylesheet_url = $this->util->get_file_url('css/lightbox.css');
		$layout = file_get_contents($this->util->normalize_path($this->util->get_path_base(), 'templates', 'default', 'layout.html'));
		$this->register_theme($name, $title, $stylesheet_url, $layout);
		//Testing: Additional themes
		$this->register_theme('black', 'Black', $this->util->get_file_url('css/lb_black.css'), $layout);
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
		if ( ! is_feed() && $this->is_enabled() && $this->get_option_value('activate_links') ) {
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
						$rel = $rel[1];
					}
					
					if ( strpos($rel, 'lightbox') !== false || strpos($rel, $this->add_prefix('off')) )
						continue;
					
					$lb = '';
					
					if ( !empty($rel) )
						$lb .= ' ';
					
					//Add rel attribute to link
					$lb .= 'lightbox';
					$group = '';
					//Check if links should be grouped
					if ( $this->get_option_value('group_links') ) {
						$group = $this->get_prefix();
						//Check if groups should be separated by post
						if ( $this->get_option_value('group_post') )
							$group = $this->add_prefix($post->ID);
					}
					if ( !empty($group) )
						$lb .= '[' . $group . ']';
					$rel .= $lb;
					$link_new = '<a rel="' . $rel . '"' . substr($link_new,2);
					//Insert modified link
					$content = str_replace($link, $link_new, $content);
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
		wp_enqueue_script($this->add_prefix('lib'), $this->util->get_file_url('js/lib.js'), array('jquery'), $this->version);
		wp_enqueue_style($this->add_prefix('style'), $this->get_theme_style(), array(), $this->version);
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
		$out['script_start'] = '<script type="text/javascript">(function($){$(document).ready(function(){';
		$out['script_end'] = '})})(jQuery);</script>';
		$js_code = array();
		//Activate links on page
		if ( $this->get_option_value('activate_links') ) {
			$rel = ( $this->get_option_value('group_links') ) ? 'lightbox[' . $this->get_prefix() . ']' : 'lightbox';
			ob_start();
			?>
			$('a[href$=".jpg"]:not([rel~="lightbox"])','a[href$=".jpeg"]:not([rel~="lightbox"])','a[href$=".gif"]:not([rel~="lightbox"])','a[href$=".png"]:not([rel~="lightbox"])').each(function(i, el){if (! /(^|\b)lightbox\[.+\]($|\b)/i.test($(el).attr('rel'))){var rel=($(el).attr('rel').length > 0) ? $(el).attr('rel') + ' ' : '';$(el).attr('rel', =rel + '<?php echo $rel; ?>');}});
			<?php
			$test = ob_get_clean();
		}
		//Get options
		$options = array(
			'validateLinks'		=> $this->get_option_value('validate_links'),
			'autoPlay'			=> $this->get_option_value('autostart'),
			'slideTime'			=> $this->get_option_value('duration'),
			'loop'				=> $this->get_option_value('loop'),
			'overlayOpacity'	=> $this->get_option_value('overlay_opacity'),
			'animate'			=> $this->get_option_value('animate'),
			'captionEnabled'	=> $this->get_option_value('enabled_caption'),
			'captionSrc'		=> $this->get_option_value('caption_src'),
			'layout'			=> $this->get_theme_layout()
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
		$js_code[] = 'SLB.initialize({' . implode(',', $lb_obj) . '});';
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
	
	/* Custom fields */
	
	/**
	 * Builds field for theme selection
	 * @param array $args Arguments set in admin_settings
	 */
	function admin_field_theme($args = array()) {
		//Get option data
		$option = $this->get_option($args['opt']);

		//Get themes
		$themes = $this->get_themes();
		
		//Get current theme
		$theme = $this->get_theme();
		
		//Build field
		$start = sprintf('<select id="%1$s" name="%1$s">', esc_attr($option->id));
		$end = '</select>';
		$option_format = '<option value="%1$s"%3$s>%2$s</option>';
		
		//Pop out default theme
		$theme_default = $themes[$this->theme_default];
		unset($themes[$this->theme_default]);
		
		//Sort themes by title
		uasort($themes, create_function('$a,$b', 'return strcmp($a[\'title\'], $b[\'title\']);'));
		
		//Insert default theme at top of array
		$themes = array($this->theme_default => $theme_default) + $themes;
		
		//Build options
		$options = array();
		foreach ( $themes as $name => $props ) {
			//Check if current them and set as selected if so
			$attr = ( $theme['name'] == $name ) ? ' selected="selected"' : '';
			$options[] = sprintf($option_format, $name, $props['title'], $attr);
		}
		
		//Output field
		echo $start . join('', $options) . $end;
	}
}

?>