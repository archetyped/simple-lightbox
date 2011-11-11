<?php 

require_once 'includes/class.base.php';
require_once 'includes/class.options.php';

/**
 * Lightbox functionality class
 * @package Simple Lightbox
 * @author Archetyped
 */
class SLB_Lightbox extends SLB_Base {
	
	/*-** Properties **-*/
	
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
	 * Value to identify activated links
	 * Formatted on initialization
	 * @var string
	 */
	var $attr = null;
	
	/**
	 * Legacy attribute (for backwards compatibility)
	 * @var string
	 */
	var $attr_legacy = 'lightbox';

	/**
	 * Properties for media attachments in current request
	 * > Key (string) Attachment URI
	 * > Value (assoc-array) Attachment properties (url, etc.)
	 *   > source: Source URL
	 * @var array
	 */
	var $media_attachments = array();
	
	/**
	 * Raw media items
	 * Used for populating media object on client-side
	 * > Key: Item URI
	 * > Value: Associative array of media properties
	 * 	 > type: Item type (Default: null)
	 * 	 > id: Item ID (Default: null)
	 * @var array
	 */
	var $media_items_raw = array();

	/**
	 * Media types
	 * @var array
	 */
	var $media_types = array('img' => 'image', 'att' => 'attachment');
	
	/* Widget properties */
	
	/**
	 * Widget callback key
	 * @var string
	 */
	var $widget_callback = 'callback';
	
	/**
	 * Key to use to store original callback
	 * @var string
	 */
	var $widget_callback_orig = 'callback_orig';
	
	/* Instance members */
	
	/**
	 * Options instance
	 * @var SLB_Options
	 */
	var $options = null;
	
	/**
	 * Base field definitions
	 * Stores system/user-defined field definitions
	 * @var SLB_Fields
	 */
	var $fields = null;
	
	var $h_temp = array();
	
	/* Constructor */
	
	function SLB_Lightbox() {
		$this->__construct();
	}
	
	function __construct() {
		parent::__construct();
		$this->init();

		//Init objects
		$this->attr = $this->get_prefix();
		$this->fields =& new SLB_Fields();
	}
	
	/* Init */
	
	function init_env() {
		//Localization
		$ldir = 'l10n';
		$lpath = $this->util->get_plugin_file_path($ldir, array(false, false));
		$lpath_abs = $this->util->get_file_path($ldir);
		if ( is_dir($lpath_abs) ) {
			load_plugin_textdomain($this->get_prefix(), false,	$lpath);
		}
		//Options
		$func_opts = 'init_options';
		if ( isset($this) && method_exists($this, $func_opts) ) {
			call_user_func($this->m($func_opts));
		}
		
		//Context
		$func_context = $this->m('set_client_context');
		$hook_context = ( is_admin() ) ? 'admin_head' : 'wp_head';
		add_action($hook_context, $func_context);
	}
	
	function init_options() {
		//Setup options
		$p = $this->util->get_plugin_base(true);
		$options_config = array (
			'groups' 	=> array (
				'activation'	=> __('Activation', $p),
				'grouping'		=> __('Grouping', $p),
				'ui'			=> __('UI', $p),
				'labels'		=> __('Labels', $p)
			),
			'items'	=> array (
				'enabled'					=> array('title' => __('Enable Lightbox Functionality', $p), 'default' => true, 'group' => 'activation'),
				'enabled_home'				=> array('title' => __('Enable on Home page', $p), 'default' => true, 'group' => 'activation'),
				'enabled_post'				=> array('title' => __('Enable on Posts', $p), 'default' => true, 'group' => 'activation'),
				'enabled_page'				=> array('title' => __('Enable on Pages', $p), 'default' => true, 'group' => 'activation'),
				'enabled_archive'			=> array('title' => __('Enable on Archive Pages (tags, categories, etc.)', $p), 'default' => true, 'group' => 'activation'),
				'enabled_widget'			=> array('title' => __('Enable for Widgets', $p), 'default' => false, 'group' => 'activation'),
				'enabled_compat'			=> array('title' => __('Enable backwards-compatibility with legacy lightbox links', $p), 'default' => false, 'group' => 'activation'),
				'activate_attachments'		=> array('title' => __('Activate image attachment links', $p), 'default' => true, 'group' => 'activation'),
				'validate_links'			=> array('title' => __('Validate links', $p), 'default' => false, 'group' => 'activation'),
				'group_links'				=> array('title' => __('Group image links (for displaying as a slideshow)', $p), 'default' => true, 'group' => 'grouping'),
				'group_post'				=> array('title' => __('Group image links by Post (e.g. on pages with multiple posts)', $p), 'default' => true, 'group' => 'grouping'),
				'group_gallery'				=> array('title' => __('Group gallery links separately', $p), 'default' => false, 'group' => 'grouping'),
				'group_widget'				=> array('title' => __('Group widget links separately', $p), 'default' => false, 'group' => 'grouping'),
				'theme'						=> array('title' => __('Theme', $p), 'default' => 'default', 'group' => 'ui', 'parent' => 'option_theme'),
				'animate'					=> array('title' => __('Animate lightbox resizing', $p), 'default' => true, 'group' => 'ui'),
				'autostart'					=> array('title' => __('Automatically Start Slideshow', $p), 'default' => true, 'group' => 'ui'),
				'duration'					=> array('title' => __('Slide Duration (Seconds)', $p), 'default' => '6', 'attr' => array('size' => 3, 'maxlength' => 3), 'group' => 'ui'),
				'loop'						=> array('title' => __('Loop through images', $p), 'default' => true, 'group' => 'ui'),
				'overlay_opacity'			=> array('title' => __('Overlay Opacity (0 - 1)', $p), 'default' => '0.8', 'attr' => array('size' => 3, 'maxlength' => 3), 'group' => 'ui'),
				'enabled_caption'			=> array('title' => __('Enable caption', $p), 'default' => true, 'group' => 'ui'),
				'caption_src'				=> array('title' => __('Use image URI as caption when link title not set', $p), 'default' => true, 'group' => 'ui'),
				'enabled_desc'				=> array('title' => __('Enable description', $p), 'default' => true, 'group' => 'ui'),
				'txt_closeLink'				=> array('title' => __('Close link (for accessibility only, image used for button)', $p), 'default' => 'close', 'group' => 'labels'),
				'txt_loadingMsg'			=> array('title' => __('Loading indicator', $p), 'default' => 'loading', 'group' => 'labels'),
				'txt_nextLink'				=> array('title' => __('Next Image link', $p), 'default' => 'next &raquo;', 'group' => 'labels'),
				'txt_prevLink'				=> array('title' => __('Previous Image link', $p), 'default' => '&laquo; prev', 'group' => 'labels'),
				'txt_startSlideshow'		=> array('title' => __('Start Slideshow link', $p), 'default' => 'start slideshow', 'group' => 'labels'),
				'txt_stopSlideshow'			=> array('title' => __('Stop Slideshow link', $p), 'default' => 'stop slideshow', 'group' => 'labels'),
				'txt_numDisplayPrefix'		=> array('title' => __('Image number prefix (e.g. <strong>Image</strong> x of y)', $p), 'default' => 'Image', 'group' => 'labels'),
				'txt_numDisplaySeparator'	=> array('title' => __('Image number separator (e.g. Image x <strong>of</strong> y)', $p), 'default' => 'of', 'group' => 'labels')
			),
			'legacy' => array (
				'header_activation'	=> null,
				'header_enabled'	=> null,
				'header_strings'	=> null,
				'header_ui'			=> null,
				'enabled_single'	=> array('enabled_post', 'enabled_page')
			)
		);
		$opt_theme =& $options_config['items']['theme'];
		$opt_theme['default'] = $this->theme_default = $this->add_prefix($this->theme_default);
		$opt_theme['options'] = $this->m('get_theme_options');
		
		$this->options =& new SLB_Options($options_config);
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
		$priority = 99;
		add_action('wp_enqueue_scripts', $this->m('enqueue_files'));
		add_action('wp_head', $this->m('client_init'));
		add_action('wp_footer', $this->m('client_footer'), $priority);
		//Link activation
		add_filter('the_content', $this->m('activate_links'), $priority);
		//Gallery wrapping
		add_filter('the_content', $this->m('gallery_wrap'), 1);
		add_filter('the_content', $this->m('gallery_unwrap'), $priority + 1);
		
		
		/* Themes */
		$this->util->add_action('init_themes', $this->m('init_default_themes'));
		
		/* Widgets */
		add_filter('sidebars_widgets', $this->m('sidebars_widgets'));
	}

	/* Methods */
	
	/*-** Request **-*/

	/**
	 * Output current context to client-side
	 * @return void
	 */
	function set_client_context() {
		$ctx = new stdClass();
		$ctx->context = $this->util->get_context();
		echo $this->util->build_script_element($this->util->extend_client_object($ctx), 'context');
	}

	/*-** Helpers **-*/
	
	/**
	 * Checks whether lightbox is currently enabled/disabled
	 * @return bool TRUE if lightbox is currently enabled, FALSE otherwise
	 */
	function is_enabled($check_request = true) {
		$ret = ( $this->options->get_bool('enabled') && !is_feed() ) ? true : false;
		if ( $ret && $check_request ) {
			$opt = '';
			//Determine option to check
			if ( is_home() )
				$opt = 'home';
			elseif ( is_singular() ) {
				$opt = ( is_page() ) ? 'page' : 'post';
			}
			elseif ( is_archive() || is_search() )
				$opt = 'archive';
			//Check option
			if ( !empty($opt) && ( $opt = 'enabled_' . $opt ) && $this->options->has($opt) ) {
				$ret = $this->options->get_bool($opt);
			}
		}
		return $ret;
	}
	
	/*-** Widgets **-*/
	
	/**
	 * Reroute widget display handlers to internal method
	 * @param array $sidebar_widgets List of sidebars & their widgets
	 * @uses WP Hook `sidebars_widgets` to intercept widget list
	 * @global $wp_registered_widgets to reroute display callback
	 * @return array Sidebars and widgets (unmodified)
	 */
	function sidebars_widgets($sidebars_widgets) {
		global $wp_registered_widgets;
		static $widgets_processed = false;
		if ( is_admin() || empty($wp_registered_widgets) || $widgets_processed || !$this->options->get_bool('enabled_widget') )
			return $sidebars_widgets; 
		$widgets_processed = true;
		//Fetch active widgets from all sidebars
		foreach ( $sidebars_widgets as $sb => $ws ) {
			//Skip inactive widgets and empty sidebars
			if ( 'wp_inactive_widgets' == $sb || empty($ws) || !is_array($ws) )
				continue;
			foreach ( $ws as $w ) {
				if ( isset($wp_registered_widgets[$w]) && isset($wp_registered_widgets[$w][$this->widget_callback]) ) {
					$wref =& $wp_registered_widgets[$w];
					//Backup original callback
					$wref[$this->widget_callback_orig] = $wref[$this->widget_callback];
					//Reroute callback
					$wref[$this->widget_callback] = $this->m('widget_callback');
					unset($wref);
				}
			}
		}

		return $sidebars_widgets;
	}
	
	/**
	 * Widget display handler
	 * Widget output is rerouted to this method by sidebar_widgets()
	 * @param array $args Widget instance properties
	 * @param int (optional) $widget_args Additional widget args (usually the widget's instance number)
	 * @see WP_Widget::display_callback() for more information
	 * @global $wp_registered_widgets
	 * @return void
	 */
	function widget_callback($args, $widget_args = 1) {
		global $wp_registered_widgets;
		$wid = ( isset($args['widget_id']) ) ? $args['widget_id'] : false;
		//Stop processing if widget data invalid
		if ( !$wid || !isset($wp_registered_widgets[$wid]) || !($w =& $wp_registered_widgets[$wid]) || !isset($w['id']) || $wid != $w['id'] )
			return false;
		//Get original callback
		if ( !isset($w[$this->widget_callback_orig]) || !($cb = $w[$this->widget_callback_orig]) || !is_callable($cb) )
			return false;
		$params = func_get_args();
		//Start output buffer
		ob_start();
		//Call original callback
		call_user_func_array($cb, $params);
		//Flush output buffer
		echo $this->widget_process_links(ob_get_clean(), $wid);
	}
	
	/**
	 * Process links in widget content
	 * @param string $content Widget content
	 * @return string Processed widget content
	 * @uses process_links() to process links
	 */
	function widget_process_links($content, $id) {
		$id = ( $this->options->get_bool('group_widget') ) ? "widget_$id" : null;
		return $this->process_links($content, $id);
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
			$this->util->do_action('init_themes');
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
			$name = $this->options->get_value('theme');
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
		if ( !$this->options->get_bool('enabled_caption') )
			$l = str_replace($this->get_theme_placeholder('dataCaption'), '', $l);
		if ( !$this->options->get_bool('enabled_desc') )
			$l = str_replace($this->get_theme_placeholder('dataDescription'), '', $l);
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
	 * Builds wrapper for grouping
	 * @return object Wrapper properties
	 *  > open
	 *  > close
	 */
	function group_get_wrapper() {
		static $wrapper = null;
		if (  is_null($wrapper) ) {
			$start = '<';
			$end = '>';
			$terminate = '/';
			$val = $this->add_prefix('group');
			//Build properties
			$wrapper = array(
				'open' => $start . $val . $end,
				'close' => $start . $terminate . $val . $end
			);
			//Convert to object
			$wrapper = (object) $wrapper;
		}
		return $wrapper;
	}
	
	/**
	 * Wraps galleries for grouping
	 * @param string $content Post content
	 * @return string Modified post content
	 */
	function gallery_wrap($content) {
		//Stop processing if option not enabled
		if ( !$this->options->get_bool('group_gallery') )
			return $content;
		global $shortcode_tags;
		//Save default shortcode handlers to temp variable
		$sc_temp = $shortcode_tags;
		//Find gallery shortcodes
		$shortcodes = array('gallery', 'nggallery');
		$m = $this->m('gallery_wrap_callback');
		$shortcode_tags = array();
		foreach ( $shortcodes as $tag ) {
			$shortcode_tags[$tag] = $m;
		}
		//Wrap gallery shortcodes
		$content = do_shortcode($content);
		//Restore default shortcode handlers
		$shortcode_tags = $sc_temp;
		
		return $content;
	}
	
	/**
	 * Wraps gallery shortcodes for later processing
	 * @param array $attr Shortcode attributes
	 * @param string $content Content enclosed in shortcode
	 * @param string $tag Shortcode name
	 * @return string Wrapped gallery shortcode
	 */
	function gallery_wrap_callback($attr, $content = null, $tag) {
		//Rebuild shortcode
		$sc = '[' . $tag . ' ' . $this->util->build_attribute_string($attr) . ']';
		if ( !empty($content) )
			$sc .= $content . '[/' . $tag .']';
		//Wrap shortcode
		$w = $this->group_get_wrapper();
		$sc = $w->open . $sc . $w->close;
		return $sc;
	}
	
	/**
	 * Removes wrapping from galleries
	 * @param $content Post content
	 * @return string Modified post content
	 */
	function gallery_unwrap($content) {
		//Stop processing if option not enabled
		if ( !$this->options->get_bool('group_gallery') )
			return $content;
		$w = $this->group_get_wrapper();
		if ( strpos($content, $w->open) !== false ) {
			$content = str_replace($w->open, '', $content);
			$content = str_replace($w->close, '', $content);
		}
		return $content;
	}
	
	/**
	 * Retrieve supported media types
	 * @return object Supported media types
	 */
	function get_media_types() {
		static $t = null;
		if ( is_null($t) )
			$t = (object) $this->media_types;
		return $t;
	}
	
	/**
	 * Check if media type is supported
	 * @param string $type Media type
	 * @return bool If media type is supported
	 */
	function is_media_type_supported($type) {
		$ret = false;
		$t = $this->get_media_types();
		foreach ( $t as $n => $v ) {
			if ( $type == $v ) {
				$ret = true;
				break;
			}
		}
		return $ret;
	}
	
	/**
	 * Scans post content for image links and activates them
	 * 
	 * Lightbox will not be activated for feeds
	 * @param $content
	 * @return string Post content
	 */
	function activate_links($content) {
		//Activate links only if enabled
		if ( !$this->is_enabled() ) {
			return $content;
		}
		
		$groups = array();
		$w = $this->group_get_wrapper();
		$g_ph_f = '[%s]';

		//Strip groups
		if ( $this->options->get_bool('group_gallery') ) {
			$groups = array();
			$g_idx = 0;
			$g_end_idx = 0;
			//Iterate through galleries
			while ( ($g_start_idx = strpos($content, $w->open, $g_end_idx)) && $g_start_idx !== false 
					&& ($g_end_idx = strpos($content, $w->close, $g_start_idx)) && $g_end_idx != false ) {
				$g_start_idx += strlen($w->open);
				//Extract gallery content & save for processing
				$g_len = $g_end_idx - $g_start_idx;
				$groups[$g_idx] = substr($content, $g_start_idx, $g_len);
				//Replace content with placeholder
				$g_ph = sprintf($g_ph_f, $g_idx);
				$content = substr_replace($content, $g_ph, $g_start_idx, $g_len);
				//Increment gallery count
				$g_idx++;
				//Update end index
				$g_end_idx = $g_start_idx + strlen($w->open);
			}
		}
		
		//General link processing
		$content = $this->process_links($content);
		
		//Reintegrate Groups
		foreach ( $groups as $group => $g_content ) {
			$g_ph = $w->open . sprintf($g_ph_f, $group) . $w->close;
			//Skip group if placeholder does not exist in content
			if ( strpos($content, $g_ph) === false ) {
				continue;
			}
			//Replace placeholder with processed content
			$content = str_replace($g_ph, $w->open . $this->process_links($g_content, $group) . $w->close, $content);
		}
		return $content;
	}
	
	/**
	 * Retrieve HTML links in content
	 * @param string $content Content to get links from
	 * @param bool (optional) $unique Remove duplicates from returned links (Default: FALSE)
	 * @return array Links in content
	 */
	function get_links($content, $unique = false) {
		$rgx = "/\<a[^\>]+href=.*?\>/i";
		$links = array();
		preg_match_all($rgx, $content, $links);
		$links = $links[0];
		if ( $unique )
			$links = array_unique($links);
		return $links;
	}
	
	/**
	 * Process links in content
	 * @param string $content Text containing links
	 * @param string (optional) $group Group to add links to (Default: none)
	 * @return string Content with processed links 
	 */
	function process_links($content, $group = null) {
		//Validate content before processing
		if ( !is_string($content) || empty($content) )
			return $content;
		$links = $this->get_links($content, true);
		//Process links
		if ( count($links) > 0 ) {
			global $wpdb;
			global $post;
			$types = $this->get_media_types();
			$img_types = array('jpg', 'jpeg', 'gif', 'png');
			$protocol = array('http://', 'https://');
			$domain = str_replace($protocol, '', strtolower(get_bloginfo('url')));
			
			//Format Group
			$group_base = ( !is_scalar($group) ) ? '' : trim(strval($group));
			if ( !$this->options->get_bool('group_links') ) {
				$group_base = null;
			}
			
			//Iterate through links & add lightbox if necessary
			foreach ( $links as $link ) {
				//Init vars
				$pid = 0;
				$link_new = $link;
				$internal = false;
				$group = $group_base;
				
				//Parse link attributes
				$attr = $this->util->parse_attribute_string($link_new, array('rel' => '', 'href' => ''));
				$h =& $attr['href'];
				$r =& $attr['rel'];
				$attrs_all = $this->get_attributes($r, false);
				$attrs = $this->get_attributes($attrs_all);
				
				//Stop processing invalid, disabled, or legacy links
				if ( empty($h) 
					|| 0 === strpos($h, '#') 
					|| $this->has_attribute($attrs, $this->make_attribute_disabled())
					|| $this->has_attribute($attrs_all, $this->attr_legacy, false) 
					)
					continue;
				
				//Check if item links to internal media (attachment)
				$hdom = str_replace($protocol, '', strtolower($h));
				if ( strpos($hdom, $domain) === 0 ) {
					//Save URL for further processing
					$internal = true;
				}
				
				//Determine link type
				$type = false;
				
				//Check if link has already been processed
				if ( $internal && $this->media_item_cached($h) ) {
					$i = $this->get_cached_media_item($h);
					$type = $i['type'];
				}
				
				elseif ( $this->util->has_file_extension($h, $img_types) ) {
					//Direct Image file
					$type = $types->img;
				}
				
				elseif ( $internal && is_local_attachment($h) && ( $pid = url_to_postid($h) ) && wp_attachment_is_image($pid) ) {
					//Attachment URI
					$type = $types->att;
				}
				
				//Stop processing if link type not valid
				if ( !$type || ( $type == $types->att && !$this->options->get_bool('activate_attachments') ) )
					continue;
				
				//Set group (if necessary)
				if ( $this->options->get_bool('group_links') ) {
					//Get preset group attribute
					$g_name = $this->make_attribute_name('group');
					$g = ( $this->has_attribute($attrs, $g_name) ) ? $this->get_attribute($attrs, $g_name) : $this->get_attribute($attrs, $this->attr); 
					
					if ( is_string($g) && ($g = trim($g)) && strlen($g) )
						$group = $g;
					
					//Group links by post?
					if ( $this->options->get_bool('group_post') ) {
						if ( strlen($group) )
							$group = '_' . $group; 
						$group = $this->add_prefix($post->ID . $group);
					}
					//Set group attribute
					if ( is_string($group) && !empty($group) ) {
						$attrs = $this->set_attribute($attrs, $g_name, $group);
					}
				}
				
				//Activate link
				$attrs = $this->set_attribute($attrs, $this->attr);
				
				//Process internal links
				if ( $internal ) {
					//Mark as internal
					$attrs = $this->set_attribute($attrs, 'internal');
					//Add to media items array
					$this->cache_media_item($h, $type, $pid);
				}
				
				//Convert rel attribute to string
				$r = $this->build_attributes(array_merge($attrs_all, $attrs));
				
				//Update link in content
				$link_new = '<a ' . $this->util->build_attribute_string($attr) . '>';
				$content = str_replace($link, $link_new, $content);
				unset($h, $r);
			}
		}
		return $content;
	}
	
	/**
	 * Generates link attributes from array
	 * @param array $attrs Link Attributes
	 * @return string Attribute string
	 */
	function build_attributes($attrs) {
		$a = array();
		//Validate attributes
		$attrs = $this->get_attributes($attrs, false);
		//Iterate through attributes and build output array
		foreach ( $attrs as $key => $val ) {
			//Standard attributes
			if ( is_bool($val) && $val ) {
				$a[] = $key;
			}
			//Attributes with values
			elseif ( is_string($val) ) {
				$a[] = $key . '[' . $val . ']';
			}
		}
		return implode(' ', $a);
	}

	/**
	 * Build attribute name
	 * Makes sure name is only prefixed once
	 * @return string Formatted attribute name 
	 */
	function make_attribute_name($name = '') {
		$sep = '_';
		$name = trim($name);
		//Generate valid name
		if ( $name != $this->attr ) {
			//Use default name
			if ( empty($name) )
				$name = $this->attr;
			//Add prefix if not yet set
			elseif ( strpos($name, $this->attr . $sep) !== 0 )
				$name = $this->attr . $sep . $name;
		}
		return $name;
	}

	/**
	 * Create attribute to disable lightbox for current link
	 * @return string Disabled lightbox attribute
	 */
	function make_attribute_disabled() {
		static $ret = null;
		if ( is_null($ret) ) {
			$ret = $this->make_attribute_name('off');
		}
		return $ret;
	}
	
	/**
	 * Set attribute to array
	 * Attribute is added to array if it does not exist
	 * @param array $attrs Current attribute array
	 * @param string $name Name of attribute to add
	 * @param string (optional) $value Attribute value
	 * @return array Updated attribute array
	 */
	function set_attribute($attrs, $name, $value = null) {
		//Validate attribute array
		$attrs = $this->get_attributes($attrs, false);
		//Build attribute name
		$name = $this->make_attribute_name($name);
		//Set attribute
		$attrs[$name] = true;
		if ( !empty($value) && is_string($value) )
			$attrs[$name] = $value;
		return $attrs;
	}
	
	/**
	 * Convert attribute string into array
	 * @param string $attr_string Attribute string
	 * @param bool (optional) $internal Whether only internal attributes should be evaluated (Default: TRUE)
	 * @return array Attributes as associative array
	 */
	function get_attributes($attr_string, $internal = true) {
		$ret = array();
		//Protect bracketed values prior to parsing attributes string
	 	if ( is_string($attr_string) ) {
	 		$attr_string = trim($attr_string);
	 		$attr_vals = array();
			$attr_keys = array();
			$offset = 0;
	 		while ( ($bo = strpos($attr_string,'[', $offset)) && $bo !== false
	 			&& ($bc = strpos($attr_string,']', $bo)) && $bc !== false
				) {
	 			//Push all preceding attributes into array
	 			$attr_temp = explode(' ', substr($attr_string, $offset, $bo));
				//Get attribute name
				$name = array_pop($attr_temp);
				$attr_keys = array_merge($attr_keys, $attr_temp);
				//Add to values array
				$attr_vals[$name] = substr($attr_string, $bo+1, $bc-$bo-1);
				//Update offset
				$offset = $bc+1;
	 		}
			//Parse remaining attributes
			$attr_keys = array_merge($attr_keys, array_filter(explode(' ', substr($attr_string, $offset))));
			//Set default values for all keys
			$attr_keys = array_fill_keys($attr_keys, TRUE);
			//Merge attributes with values
			$ret = array_merge($attr_keys, $attr_vals);
	 	} elseif ( is_array($attr_string) )
			$ret = $attr_string;
		
		//Filter non-internal attributes if necessary
		if ( $internal && is_array($ret) ) {
			foreach ( array_keys($ret) as $attr ) {
				if ( $attr == $this->attr)
					continue;
				if ( strpos($attr, $this->attr . '_') !== 0 )
					unset($ret[$attr]);
			}
		}
		
		return $ret;
	}
	
	/**
	 * Retrieve attribute value
	 * @param string|array $attrs Attributes to retrieve attribute value from
	 * @param string $attr Attribute name to retrieve
	 * @param bool (optional) $internal Whether only internal attributes should be evaluated (Default: TRUE)
	 * @return string|bool Attribute value (Default: FALSE)
	 */
	function get_attribute($attrs, $attr, $internal = true) {
		$ret = false;
		$attrs = $this->get_attributes($attrs, $internal);
		//Validate attribute name for internal attributes
		if ( $internal )
			$attr = $this->make_attribute_name($attr);
		if ( isset($attrs[$attr]) ) {
			$ret = $attrs[$attr];
		}
		return $ret;
	}
	
	/**
	 * Checks if attribute exists
	 * @param string|array $attrs Attributes to retrieve attribute value from
	 * @param string $attr Attribute name to retrieve
	 * @param bool (optional) $internal Whether only internal attributes should be evaluated (Default: TRUE)
	 * @return bool Whether or not attribute exists
	 */
	function has_attribute($attrs, $attr, $internal = true) {
		return ( $this->get_attribute($attrs, $attr, $internal) !== false ) ? true : false;
	}
	
	/**
	 * Cache media properties for later processing
	 * @param string $uri URI for internal media (e.g. direct uri, attachment uri, etc.) 
	 * @param string $type Media type (image, attachment, etc.)
	 * @param int (optional) $id ID of media item (if available) (Default: NULL)
	 */
	function cache_media_item($uri, $type, $id = null) {
		//Normalize URI
		$uri = strtolower($uri);
		//Cache media item
		if ( $this->is_media_type_supported($type) && !$this->media_item_cached($uri) ) {
			//Set properties
			$i = array('type' => null, 'id' => null, 'source' => null);
			//Type
			$i['type'] = $type;
			$t = $this->get_media_types();
			//Source
			if ( $type == $t->img )
				$i['source'] = $uri;
			//ID
			if ( is_numeric($id) )
				$i['id'] = absint($id);
			
			$this->media_items_raw[$uri] = $i;
		}
	}
	
	/**
	 * Checks if media item has already been cached
	 * @param string $uri URI of media item
	 * @return boolean Whether media item has been cached
	 */
	function media_item_cached($uri) {
		$ret = false;
		if ( !$uri || !is_string($uri) )
			return $ret;
		$uri = strtolower($uri);
		return ( isset($this->media_items_raw[$uri]) ) ? true : false;
	}
	
	/**
	 * Retrieve cached media item
	 * @param string $uri Media item URI
	 * @return array|null Media item properties (NULL if not set)
	 */
	function get_cached_media_item($uri) {
		$ret = null;
		$uri = strtolower($uri);
		if ( $this->media_item_cached($uri) ) {
			$ret = $this->media_items_raw[$uri];
		}
		return $ret;
	}
	
	/**
	 * Retrieve cached media items
	 * @return array Cached media items
	 */
	function &get_cached_media_items() {
		return $this->media_items_raw;
	}
	
	/**
	 * Check if media items have been cached
	 * @return boolean
	 */
	function has_cached_media_items() {
		return ( count($this->media_items_raw) > 0 ) ? true : false; 
	}
	
	/**
	 * Enqueue files in template head
	 */
	function enqueue_files() {
		if ( ! $this->is_enabled() )
			return;
		
		$lib = 'js/' . ( ( ( defined('WP_DEBUG') && WP_DEBUG ) || isset($_REQUEST[$this->add_prefix('debug')]) ) ? 'dev/lib.dev.js' : 'lib.js' );
		wp_enqueue_script($this->add_prefix('lib'), $this->util->get_file_url($lib), array('jquery'), $this->util->get_plugin_version());
		wp_enqueue_style($this->add_prefix('style'), $this->get_theme_style(), array(), $this->util->get_plugin_version());
	}
	
	/**
	 * Sets options/settings to initialize lightbox functionality on page load
	 * @return void
	 */
	function client_init() {
		if ( ! $this->is_enabled() )
			return;
		echo '<!-- SLB -->' . PHP_EOL;
		$options = array();
		$out = array();
		$js_code = array();
		//Get options
		$options = array(
			'validateLinks'		=> $this->options->get_bool('validate_links'),
			'autoPlay'			=> $this->options->get_bool('autostart'),
			'slideTime'			=> $this->options->get_value('duration'),
			'loop'				=> $this->options->get_bool('loop'),
			'overlayOpacity'	=> $this->options->get_value('overlay_opacity'),
			'animate'			=> $this->options->get_bool('animate'),
			'captionEnabled'	=> $this->options->get_bool('enabled_caption'),
			'captionSrc'		=> $this->options->get_bool('caption_src'),
			'descEnabled'		=> $this->options->get_bool('enabled_desc'),
			'relAttribute'		=> array($this->get_prefix()),
			'prefix'			=> $this->get_prefix()
		);
		//Backwards compatibility
		if ( $this->options->get_bool('enabled_compat'))
			$options['relAttribute'][] = $this->attr_legacy;
			
		//Load UI Strings
		if ( ($strings = $this->build_strings()) && !empty($strings) )
			$options['strings'] = $strings;
		//Load Layout
		$options['layout'] = $this->get_theme_layout();

		//Build client output
		echo $this->util->build_script_element($this->util->call_client_method('initialize', $options), 'init', true, true);
		echo PHP_EOL . '<!-- /SLB -->' . PHP_EOL;
	}
	
	/**
	 * Output code in footer
	 * > Media attachment URLs
	 * @uses `_wp_attached_file` to match attachment ID to URI
	 * @uses `_wp_attachment_metadata` to retrieve attachment metadata
	 */
	function client_footer() {
		echo '<!-- X -->';
		//Stop if not enabled or if there are no media items to process
		if ( !$this->is_enabled() || !$this->has_cached_media_items() )
			return;
		echo '<!-- SLB -->' . PHP_EOL;
		
		global $wpdb;
		
		$this->media_attachments = array();
		$props = array('id', 'type', 'desc', 'title', 'source');
		$props = (object) array_combine($props, $props);

		//Separate media into buckets by type
		$m_bucket = array();
		$type = $id = null;
		
		$m_items =& $this->get_cached_media_items();
		foreach ( $m_items as $uri => $p ) {
			$type = $p[$props->type];
			if ( empty($type) )
				continue;
			if ( !isset($m_bucket[$type]) )
				$m_bucket[$type] = array();
			//Add to bucket for type (by reference)
			$m_bucket[$type][$uri] =& $m_items[$uri];
		}
		
		//Process links by type
		$t = $this->get_media_types();

		//Direct image links
		if ( isset($m_bucket[$t->img]) ) {
			$b =& $m_bucket[$t->img];
			$uris_base = array();
			$uri_prefix = wp_upload_dir();
			$uri_prefix = $this->util->normalize_path($uri_prefix['baseurl'], true);
			foreach ( array_keys($b) as $uri ) {
				$uris_base[str_replace($uri_prefix, '', $uri)] = $uri;
			}
			
			//Retrieve attachment IDs
			$uris_flat = "('" . implode("','", array_keys($uris_base)) . "')";
			$q = $wpdb->prepare("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE `meta_key` = %s AND LOWER(`meta_value`) IN $uris_flat LIMIT %d", '_wp_attached_file', count($b));
			$pids_temp = $wpdb->get_results($q);
			//Match IDs with URIs
			if ( $pids_temp ) {
				foreach ( $pids_temp as $pd ) {
					$f = strtolower($pd->meta_value);
					if ( is_numeric($pd->post_id) && isset($uris_base[$f]) ) {
						$b[$uris_base[$f]][$props->id] = absint($pd->post_id);
					}
				}
			}
			//Destroy worker vars
			unset($b, $uri, $uris_base, $uris_flat, $q, $pids_temp, $pd);
		}
		
		//Image attachments
		if ( isset($m_bucket[$t->att]) ) {
			$b =& $m_bucket[$t->att];
			
			//Attachment source URI
			foreach ( $b as $uri => $p ) {
				$s = wp_get_attachment_url($p[$props->id]);
				if ( !!$s )
					$b[$uri][$props->source] = $s;
			}
			//Destroy worker vars
			unset($b, $uri, $p);
		}
		
		//Retrieve attachment IDs
		$ids = array();
		foreach ( $m_items as $uri => $p ) {
			//Add post ID to query
			if ( isset($p[$props->id]) ) {
				$id = $p[$props->id];
				//Create array for ID (support multiple URIs per ID)
				if ( !isset($ids[$id]) ) {
					$ids[$id] = array();
				}
				//Add URI to ID
				$ids[$id][] = $uri;
			}
		}
		
		//Retrieve attachment properties
		if ( !empty($ids) ) {
			$ids_flat = array_keys($ids);
			$atts = get_posts(array('post_type' => 'attachment', 'include' => $ids_flat));
			$ids_flat = "('" . implode("','", $ids_flat) . "')";
			$atts_meta = $wpdb->get_results($wpdb->prepare("SELECT `post_id`,`meta_value` FROM $wpdb->postmeta WHERE `post_id` IN $ids_flat AND `meta_key` = %s LIMIT %d", '_wp_attachment_metadata', count($ids)));
			//Rebuild metadata array
			if ( $atts_meta ) {
				$meta = array();
				foreach ( $atts_meta as $att_meta ) {
					$meta[$att_meta->post_id] = $att_meta->meta_value;
				}
				$atts_meta = $meta;
				unset($meta);
			} else {
				$atts_meta = array();
			}
			
			//Process attachments
			if ( $atts ) {
				foreach ( $atts as $att ) {
					if ( !isset($ids[$att->ID]) )
						continue;
					//Add attachment
					//Set properties
					$m = array(
						$props->title	=> $att->post_title,
						$props->desc	=> $att->post_content,
					);
					//Add metadata
					if ( isset($atts_meta[$att->ID]) && ($a = unserialize($atts_meta[$att->ID])) && is_array($a) ) {
						//Move original size into `sizes` array
						foreach ( array('file', 'width', 'height') as $d ) {
							if ( !isset($a[$d]) )
								continue;
							$a['sizes']['original'][$d] = $a[$d];
							unset($a[$d]);
						}

						//Strip extraneous metadata
						foreach ( array('hwstring_small') as $d ) {
							if ( isset($a[$d]) )
								unset($a[$d]);
						}

						$m = array_merge($a, $m);
						unset($a, $d);
					}
					
					//Save to object
					foreach ( $ids[$att->ID] as $uri ) {
						if ( isset($m_items[$uri]) )
							$m = array_merge($m_items[$uri], $m);
						$this->media_attachments[$uri] = $m;
					}
				}
			}
		}
		
		//Media attachments
		if ( !empty($this->media_attachments) ) {
			$obj = 'media';
			$atch_out = $this->util->extend_client_object($obj, $this->media_attachments);
			echo $this->util->build_script_element($atch_out, $obj);
		}
		
		echo PHP_EOL . '<!-- /SLB -->' . PHP_EOL;
	}
	
	/**
	 * Build JS object of UI strings when initializing lightbox
	 * @return array UI strings
	 */
	function build_strings() {
		$ret = array();
		//Get all UI options
		$prefix = 'txt_';
		$opt_strings = array_filter(array_keys($this->options->get_items()), create_function('$opt', 'return ( strpos($opt, "' . $prefix . '") === 0 );'));
		if ( count($opt_strings) ) {
			//Build array of UI options
			foreach ( $opt_strings as $key ) {
				$name = substr($key, strlen($prefix));
				$ret[$name] = $this->options->get_value($key);
			}
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
			$settings = __('Settings', $this->get_prefix());
			$reset = __('Reset', $this->get_prefix());
			$reset_confirm = "'" . __('Are you sure you want to reset your settings?', $this->get_prefix()) . "'";
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
			wp_die(__('You do not have sufficient permissions to manage plugins for this blog.', $this->get_prefix()));
		$action = 'reset';
		if ( isset($_REQUEST['action']) && $this->add_prefix($action) == $_REQUEST['action'] ) {
			//Reset settings
			$this->options->reset(true);
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
	 * @todo Move appropriate code to options class
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
		add_settings_section($section, '<span id="' . $this->admin_get_settings_section() . '">' . __('Lightbox Settings', $this->get_prefix()) . '</span>', $this->m('admin_section'), $page);
		//Register settings container
		register_setting($page, $this->add_prefix('options'), $this->options->m('validate'));
 	}
	
 	/**
 	 * Enqueues header files for admin pages
 	 * @todo Separate and move options CSS to options class
 	 */
	function admin_enqueue_files() {
		//Enqueue custom CSS for options page
		if ( is_admin() && basename($_SERVER['SCRIPT_NAME']) == $this->options_admin_page ) {
			wp_enqueue_style($this->add_prefix('admin'), $this->util->get_file_url('css/admin.css'), array(), $this->util->get_plugin_version());
		}
	}
	
	/**
	 * Get ID of settings section on admin page
	 * @return string ID of settings section
	 * @todo Eval for moving to options class
	 */
	function admin_get_settings_section() {
		return $this->add_prefix('settings');
	}
	
	/**
	 * Placeholder function for lightbox admin settings
	 * Required because setting init function requires a callback
	 * @todo Evaluate for moving to options class
	 */
	function admin_section() {
		$this->options->build();		
	}
	
	/* Custom fields */
	
	function get_theme_options() {
		//Get themes
		$themes = $this->get_themes();
		
		//Pop out default theme
		$theme_default = $themes[$this->theme_default];
		unset($themes[$this->theme_default]);
		
		//Sort themes by title
		uasort($themes, create_function('$a,$b', 'return strcmp($a[\'title\'], $b[\'title\']);'));
		
		//Insert default theme at top of array
		$themes = array($this->theme_default => $theme_default) + $themes;
		
		//Build options
		foreach ( $themes as $name => $props ) {
			$themes[$name] = $props['title'];
		}
		return $themes;
	}
}

?>
