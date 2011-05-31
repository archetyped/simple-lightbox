<?php 

require_once 'includes/class.base.php';
require_once 'includes/class.fields.php';
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
	 * Options Configuration
	 * @var array
	 */
	var $options_config = array (
		'groups' 	=> array (
			'activation'	=> 'Activation',
			'grouping'		=> 'Grouping',
			'ui'			=> 'UI',
			'labels'		=> 'Labels'
		),
		'items'	=> array (
			'enabled'					=> array('title' => 'Enable Lightbox Functionality', 'default' => true, 'group' => 'activation'),
			'enabled_home'				=> array('title' => 'Enable on Home page', 'default' => true, 'group' => 'activation'),
			'enabled_post'				=> array('title' => 'Enable on Posts', 'default' => true, 'group' => 'activation'),
			'enabled_page'				=> array('title' => 'Enable on Pages', 'default' => true, 'group' => 'activation'),
			'enabled_archive'			=> array('title' => 'Enable on Archive Pages (tags, categories, etc.)', 'default' => true, 'group' => 'activation'),
			'activate_links'			=> array('title' => 'Activate all image links in item content', 'default' => true, 'group' => 'activation'),
			'validate_links'			=> array('title' => 'Validate links', 'default' => false, 'group' => 'activation'),
			'group_links'				=> array('title' => 'Group automatically activated links (for displaying as a slideshow)', 'default' => true, 'group' => 'grouping'),
			'group_post'				=> array('title' => 'Group image links by Post (e.g. on pages with multiple posts)', 'default' => true, 'group' => 'grouping'),
			'theme'						=> array('title' => 'Theme', 'default' => 'default', 'group' => 'ui', 'parent' => 'option_theme'),
			'animate'					=> array('title' => 'Animate lightbox resizing', 'default' => true, 'group' => 'ui'),
			'autostart'					=> array('title' => 'Automatically Start Slideshow', 'default' => true, 'group' => 'ui'),
			'duration'					=> array('title' => 'Slide Duration (Seconds)', 'default' => '6', 'attr' => array('size' => 3, 'maxlength' => 3), 'group' => 'ui'),
			'loop'						=> array('title' => 'Loop through images', 'default' => true, 'group' => 'ui'),
			'overlay_opacity'			=> array('title' => 'Overlay Opacity (0 - 1)', 'default' => '0.8', 'attr' => array('size' => 3, 'maxlength' => 3), 'group' => 'ui'),
			'enabled_caption'			=> array('title' => 'Enable caption', 'default' => true, 'group' => 'ui'),
			'caption_src'				=> array('title' => 'Use image URI as caption when link title not set', 'default' => true, 'group' => 'ui'),
			'txt_closeLink'				=> array('title' => 'Close link (for accessibility only, image used for button)', 'default' => 'close', 'group' => 'labels'),
			'txt_loadingMsg'			=> array('title' => 'Loading indicator', 'default' => 'loading', 'group' => 'labels'),
			'txt_nextLink'				=> array('title' => 'Next Image link', 'default' => 'next &raquo;', 'group' => 'labels'),
			'txt_prevLink'				=> array('title' => 'Previous Image link', 'default' => '&laquo; prev', 'group' => 'labels'),
			'txt_startSlideshow'		=> array('title' => 'Start Slideshow link', 'default' => 'start slideshow', 'group' => 'labels'),
			'txt_stopSlideshow'			=> array('title' => 'Stop Slideshow link', 'default' => 'stop slideshow', 'group' => 'labels'),
			'txt_numDisplayPrefix'		=> array('title' => 'Image number prefix (e.g. <strong>Image</strong> x of y)', 'default' => 'Image', 'group' => 'labels'),
			'txt_numDisplaySeparator'	=> array('title' => 'Image number separator (e.g. Image x <strong>of</strong> y)', 'default' => 'of', 'group' => 'labels')
		),
		'legacy' => array (
			'header_activation',
			'header_enabled',
			'header_strings',
			'header_ui',
			'enabled_single'
		)
	);
	
	/* Instance members */
	
	/**
	 * Options instance
	 * @var SLB_Options
	 */
	var $options = null;
	
	/**
	 * Fields
	 * @var SLB_Fields
	 */
	var $fields = null;
	
	/*-** Init **-*/
	
	function SLB_Lightbox() {
		$this->__construct();
	}
	
	function __construct() {
		parent::__construct();
		$this->init();

		//Setup options
		$opt_theme =& $this->options_config['items']['theme'];
		$opt_theme['default'] = $this->theme_default = $this->add_prefix($this->theme_default);
		$opt_theme['options'] = $this->m('get_theme_options');
		
		//Init objects
		$this->fields =& new SLB_Fields();
		$this->options =& new SLB_Options('options', $this->options_config);
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
		$this->util->add_action('init_themes', $this->m('init_default_themes'));
	}
	
	function activate() {
		//Set default options (if not yet set)
		$this->options->reset(false);
		$this->options->migrate();
	}
	
	/*-** Helpers **-*/
	
	/**
	 * Checks whether lightbox is currently enabled/disabled
	 * @return bool TRUE if lightbox is currently enabled, FALSE otherwise
	 */
	function is_enabled($check_request = true) {
		$ret = ( $this->options->get_value('enabled') ) ? true : false;
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
		if ( !$this->options->get_value('enabled_caption') )
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
		if ( ! is_feed() && $this->is_enabled() && $this->options->get_value('activate_links') ) {
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
					if ( $this->options->get_value('group_links') ) {
						$group = $this->get_prefix();
						//Check if groups should be separated by post
						if ( $this->options->get_value('group_post') )
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
		wp_enqueue_script($this->add_prefix('lib'), $this->util->get_file_url('js/lib.js'), array('jquery'), $this->util->get_plugin_version());
		wp_enqueue_style($this->add_prefix('style'), $this->get_theme_style(), array(), $this->util->get_plugin_version());
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
		$out['script_start'] = '<script type="text/javascript">/* <![CDATA[ */(function($){$(document).ready(function(){';
		$out['script_end'] = '})})(jQuery);/* ]]> */</script>';
		$js_code = array();
		//Activate links on page
		if ( $this->options->get_value('activate_links') ) {
			$rel = ( $this->options->get_value('group_links') ) ? 'lightbox[' . $this->get_prefix() . ']' : 'lightbox';
			ob_start();
			?>
			$('a[href$=".jpg"]:not([rel~="lightbox"])','a[href$=".jpeg"]:not([rel~="lightbox"])','a[href$=".gif"]:not([rel~="lightbox"])','a[href$=".png"]:not([rel~="lightbox"])').each(function(i, el){if (! /(^|\b)lightbox\[.+\]($|\b)/i.test($(el).attr('rel'))){var rel=($(el).attr('rel').length > 0) ? $(el).attr('rel') + ' ' : '';$(el).attr('rel', =rel + '<?php echo $rel; ?>');}});
			<?php
			$test = ob_get_clean();
		}
		//Get options
		$options = array(
			'validateLinks'		=> $this->options->get_value('validate_links'),
			'autoPlay'			=> $this->options->get_value('autostart'),
			'slideTime'			=> $this->options->get_value('duration'),
			'loop'				=> $this->options->get_value('loop'),
			'overlayOpacity'	=> $this->options->get_value('overlay_opacity'),
			'animate'			=> $this->options->get_value('animate'),
			'captionEnabled'	=> $this->options->get_value('enabled_caption'),
			'captionSrc'		=> $this->options->get_value('caption_src'),
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
		$opt_strings = array_filter(array_keys($this->options->get_items()), create_function('$opt', 'return ( strpos($opt, "' . $prefix . '") === 0 );'));
		if ( $opt_strings ) {
			$strings = array();
			foreach ( $opt_strings as $key ) {
				$name = substr($key, strlen($prefix));
				$strings[] = "'" . $name . "':'" . $this->options->get_value($key) . "'";
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
		add_settings_section($section, '<span id="' . $this->admin_get_settings_section() . '">' . __('Lightbox Settings') . '</span>', $this->m('admin_section'), $page);
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