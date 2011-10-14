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
	
	var $attr = null;
	
	/**
	 * Legacy attribute (for backwards compatibility)
	 * @var string
	 */
	var $attr_legacy = 'lightbox';

	/**
	 * Properties for media attachments in current request
	 * Key (int) Attachment ID
	 * Value (assoc-array) Attachment properties (url, etc.)
	 * > source: Source URL
	 * @var array
	 */
	var $media_attachments = array();
	
	/* Instance members */
	
	/**
	 * Options instance
	 * @var SLB_Options
	 */
	var $options = null;
	
	/**
	 * Base field definitions
	 * @var SLB_Fields
	 */
	var $fields = null;
	
	/*-** Init **-*/
	
	function SLB_Lightbox() {
		$this->__construct();
	}
	
	function __construct() {
		parent::__construct();
		load_plugin_textdomain($this->get_prefix(), false,	$this->util->get_plugin_file_path('lang', array(false, false)));
		$this->init_options();
		$this->init();

		//Init objects
		$this->attr = $this->get_prefix();
		$this->fields =& new SLB_Fields();
	}
	
	function init_options() {
		//Setup options
		$p = $this->get_prefix();
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
				'enabled_compat'			=> array('title' => __('Enable backwards-compatibility with legacy lightbox links', $p), 'default' => false, 'group' => 'activation'),
				'activate_attachments'		=> array('title' => __('Activate image attachment links', $p), 'default' => true, 'group' => 'activation'),
				'validate_links'			=> array('title' => __('Validate links', $p), 'default' => false, 'group' => 'activation'),
				'group_links'				=> array('title' => __('Group image links (for displaying as a slideshow)', $p), 'default' => true, 'group' => 'grouping'),
				'group_post'				=> array('title' => __('Group image links by Post (e.g. on pages with multiple posts)', $p), 'default' => true, 'group' => 'grouping'),
				'group_gallery'				=> array('title' => __('Group gallery links separately', $p), 'default' => false, 'group' => 'grouping'),
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
		
		$this->options =& new SLB_Options('options', $options_config);
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
		add_action('wp_footer', $this->m('client_footer'), 99);
		if ( $this->options->get_bool('group_gallery') ) {
			add_filter('the_content', $this->m('gallery_wrap'), 1);
			add_filter('the_content', $this->m('gallery_unwrap'), 100);
		}
		add_filter('the_content', $this->m('activate_links'), 99);
		
		/* Themes */
		$this->util->add_action('init_themes', $this->m('init_default_themes'));
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
				$ret = ( $this->options->get_value($opt) ) ? true : false;
			}
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
		/*
		$w = $this->group_get_wrapper();
		if ( strpos($content, $w->open) !== false ) {
			$content = str_replace($w->open, '', $content);
			$content = str_replace($w->close, '', $content);
		}
		*/
		return $content;
	}
	
	/**
	 * Scans post content for image links and activates them
	 * 
	 * Lightbox will not be activated for feeds
	 * @param $content
	 */
	function activate_links($content) {
		//Activate links only if enabled
		if ( $this->is_enabled() ) {
			$groups = array();
			$w = $this->group_get_wrapper();
			$g_ph_f = '[%s]';

			//Strip gallery links (if necessary)
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
		global $wpdb;
		$links = $this->get_links($content, true);
		//Process links
		if ( count($links) > 0 ) {
			global $post;
			$types = (object) array('img' => 'image', 'att' => 'attachment');
			$img_types = array('jpg', 'jpeg', 'gif', 'png');
			
			//Format Group
			$g = ( is_null($group) || 0 == strlen(trim($group)) ) ? '' : '_g_' . $group;
			if ( $this->options->get_bool('group_links') ) {
				$g = ( ( $this->options->get_bool('group_post') ) ? $this->add_prefix($post->ID) : $this->get_prefix() ) . $g;
			}
			$lb_base = $lb = $this->attr;
			if ( !empty($g) ) {
				$lb .= '[' . $g . ']';
			}
			
			//Iterate through links & add lightbox if necessary
			foreach ( $links as $link ) {
				//Init vars
				$m_props = array();
				$pid = 0;
				$link_new = $link;
				
				//Parse link attributes
				$attr = $this->util->parse_attribute_string($link_new, array('rel' => '', 'href' => ''));
				$h =& $attr['href'];
				$r =& $attr['rel'];
				
				//Stop processing link if lightbox attribute has already been set
				if ( empty($h) || '#' == $h || ( !empty($r) && ( strpos($r, $lb_base) !== false || strpos($r, $this->add_prefix('off')) !== false || strpos($r, $this->attr_legacy) !== false ) ) )
					continue;
				//Determine link type
				$type = false;
				$domain = str_replace(array('http://', 'https://'), '', get_bloginfo('url'));
				if ( $this->util->has_file_extension($h, $img_types) ) {
					$type = $types->img;
					//Check if item links to internal media (attachment)
					if ( strpos($h, $domain) !== false ) {
						$pid_temp = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE `meta_key` = %s AND `meta_value` = %s LIMIT 1", '_wp_attached_file', basename($h)));
						if ( is_numeric($pid_temp) )
							$pid = intval($pid_temp);
					}
				}
				elseif ( strpos($h, $domain) !== false && is_local_attachment($h) && ( $pid = url_to_postid($h) ) && wp_attachment_is_image($pid) ) 
					$type = $types->att;
				if ( !$type ) {
					continue;
				}
				
				if ( $type == $types->att && !$this->options->get_bool('activate_attachments') )
					continue;
					
				//Process rel attribute
				if ( empty($r) )
					$r = array();
				else
					$r = explode(' ', trim($r));
				
				$r[] = $lb;
				
				//Load properties for attachments
				if ( !!$pid ) {
					if ( !isset($this->media_attachments[$pid]) ) {
						switch ($type) {
							case $types->img:
								$m_props['source'] = $h;
								break;
								
							case $types->att:
								//Source URL
								$m_props['source'] = wp_get_attachment_url($pid);
								break;
						}
														
						//Retrieve attachment data
						if ( $this->options->get_bool('enabled_desc') ) {
							$m_props['p'] = get_post($pid);
							//Description
							$m_props['desc'] = $m_props['p']->post_content;
							//Clear attachment data
							unset($m_props['p']);
						}
						
						//Add attachment properties
						if ( !empty($m_props['source']) )
							$this->media_attachments[$pid] = $m_props;
					}
					
					//Check again if attachment ID exists (in case it was just added to array)
					if ( isset($this->media_attachments[$pid]) )
						$r[] = $this->add_prefix('id[' . $pid . ']');
				}
				
				
				//Convert rel attribute to string
				$r = implode(' ', $r);
				
				//Update link in content
				$link_new = '<a ' . $this->util->build_attribute_string($attr) . '>';
				$content = str_replace($link, $link_new, $content);
				unset($h, $r);
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
		
		$lib = 'js/' . ( ( WP_DEBUG ) ? 'dev/lib.dev.js' : 'lib.js' );
		wp_enqueue_script($this->add_prefix('lib'), $this->util->get_file_url($lib), array('jquery'), $this->util->get_plugin_version());
		wp_enqueue_style($this->add_prefix('style'), $this->get_theme_style(), array(), $this->util->get_plugin_version());
	}
	
	/**
	 * Build client (JS) object name
	 * @return string Name of JS object
	 */
	function get_client_obj() {
		static $obj = null;
		if ( is_null($obj) )
			$obj = strtoupper($this->get_prefix(''));
		return $obj;
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
		$out['script_start'] = '(function($){$(document).ready(function(){';
		$out['script_end'] = '})})(jQuery);';
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
			'altsrc'			=> $this->add_prefix('src'),
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
		$js_code[] = $this->get_client_obj() . '.initialize(' . json_encode($options) . ');';
		$js_out = $out['script_start'] . implode('', $js_code) . $out['script_end'];
		echo $this->util->build_script_element($js_out, $this->add_prefix('init'));
	}
	
	/**
	 * Output code in footer
	 * > Media attachment URLs
	 */
	function client_footer() {
		if ( !$this->is_enabled() )
			return;
		//Media attachments
		if ( !empty($this->media_attachments) ) {
			$atch_out = $this->get_client_obj() . '.media = ' . json_encode($this->media_attachments) . ';';
			echo $this->util->build_script_element($atch_out, $this->add_prefix('media'));
		}
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
