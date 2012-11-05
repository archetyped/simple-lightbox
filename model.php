<?php 

require_once 'includes/class.base.php';
require_once 'includes/class.admin.php';
require_once 'includes/class.options.php';
require_once 'includes/class.themes.php';

/**
 * Lightbox functionality class
 * @package Simple Lightbox
 * @author Archetyped
 */
class SLB_Lightbox extends SLB_Base {
	
	/*-** Properties **-*/
	
	/* Files */
	
	var $scripts = array (
		'core'			=> array (
			'file'		=> 'js/lib.core.js',
			'deps'		=> 'jquery',
		),
		'view'			=> array (
			'file'		=> 'js/lib.view.js',
			'deps'		=> array('jquery', '[core]'),
			'context'	=> array( array('public', '[is_enabled]') ),
		),
		'test'			=> array (
			'file'		=> 'js/lib.test.js',
			'deps'		=> array('jquery', '[core]'),
			'context'	=> array( array('public', '[xvv]') ),
		),
	);
	
	/**
	 * Themes collection
	 * @var SLB_Themes
	 */
	var $themes = null;
	
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
	var $media_items = array();
	
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

	/**
	 * Used to track if widget is currently being processed or not
	 * @var bool
	 */
	var $widget_processing = false;

	/* Instance members */
	
	/**
	 * Base field definitions
	 * Stores system/user-defined field definitions
	 * @var SLB_Fields
	 */
	var $fields = null;
	
	/* Constructor */
	
	function __construct() {
		parent::__construct();
		//Init properties
		$this->attr = $this->get_prefix();
		
		//Init objects
		$this->admin = new SLB_Admin($this);
		$this->fields = new SLB_Fields();
		$this->themes = new SLB_Themes($this);
		
		//Init instance
		$this->init();
	}
	
	/* Init */
	
	/**
	 * Initialize client files
	 * Overrides parent method
	 * @see parent::init_client_files()
	 * @uses parent::init_client_files()
	 * @return void
	 */
	function init_client_files() {
		//Load appropriate file for request
		/*
		if ( ( defined('WP_DEBUG') && WP_DEBUG ) || isset($_REQUEST[$this->add_prefix('debug')]) )
			$this->scripts['lightbox']['file'] = 'js/dev/lib.lightbox.dev.js';
		*/
		//Default init
		parent::init_client_files();
	}
	
	/**
	 * Initialize environment
	 * Overrides parent method
	 * @see parent::init_env
	 * @return void
	 */
	function init_env() {
		//Localization
		$ldir = 'l10n';
		$lpath = $this->util->get_plugin_file_path($ldir, array(false, false));
		$lpath_abs = $this->util->get_file_path($ldir);
		if ( is_dir($lpath_abs) ) {
			load_plugin_textdomain($this->util->get_plugin_textdomain(), false,	$lpath);
		}
		
		//Context
		add_action(( is_admin() ) ? 'admin_head' : 'wp_head', $this->m('set_client_context'));
	}
	
	/**
	 * Init options
	 * 
	 */
	function init_options() {
		//Setup options
		$options_config = array (
			'groups' 	=> array (
				'activation'	=> array ( 'priority' => 10),
				'grouping'		=> array ( 'priority' => 20),
				'ui'			=> array ( 'priority' => 30),
				'labels'		=> array ( 'priority' => 40),
			),
			'items'	=> array (
				'enabled'					=> array('default' => true, 'group' => array('activation', 10)),
				'enabled_home'				=> array('default' => true, 'group' => array('activation', 20)),
				'enabled_post'				=> array('default' => true, 'group' => array('activation', 30)),
				'enabled_page'				=> array('default' => true, 'group' => array('activation', 40)),
				'enabled_archive'			=> array('default' => true, 'group' => array('activation', 50)),
				'enabled_widget'			=> array('default' => false, 'group' => array('activation', 60)),
				'activate_attachments'		=> array('default' => true, 'group' => array('activation', 70)),
				'validate_links'			=> array('default' => false, 'group' => array('activation', 80), 'in_client' => true),
				'group_links'				=> array('default' => true, 'group' => array('grouping', 10)),
				'group_post'				=> array('default' => true, 'group' => array('grouping', 20)),
				'group_gallery'				=> array('default' => false, 'group' => array('grouping', 30)),
				'group_widget'				=> array('default' => false, 'group' => array('grouping', 40)),
				'ui_autofit'				=> array('default' => true, 'group' => array('ui', 10), 'in_client' => true),
				'ui_animate'				=> array('default' => true, 'group' => array('ui', 20), 'in_client' => true),
				'slideshow_autostart'		=> array('default' => true, 'group' => array('ui', 30), 'in_client' => true),
				'slideshow_duration'		=> array('default' => '6', 'attr' => array('size' => 3, 'maxlength' => 3), 'group' => array('ui', 40), 'in_client' => true),
				'group_loop'				=> array('default' => true, 'group' => array('ui', 50), 'in_client' => true),
				'ui_overlay_opacity'		=> array('default' => '0.8', 'attr' => array('size' => 3, 'maxlength' => 3), 'group' => array('ui', 60), 'in_client' => true),
				'ui_enabled_caption'		=> array('default' => true, 'group' => array('ui', 70), 'in_client' => true),
				'ui_caption_src'			=> array('default' => true, 'group' => array('ui', 80), 'in_client' => true),
				'ui_enabled_desc'			=> array('default' => true, 'group' => array('ui', 90), 'in_client' => true),
				'txt_link_close'			=> array('default' => 'close', 'group' => array('labels', 10)),
				'txt_loading'				=> array('default' => 'loading', 'group' => array('labels', 20)),
				'txt_link_next'				=> array('default' => 'next &raquo;', 'group' => array('labels', 30)),
				'txt_link_prev'				=> array('default' => '&laquo; prev', 'group' => array('labels', 40)),
				'txt_slideshow_start'		=> array('default' => 'start slideshow', 'group' => array('labels', 50)),
				'txt_slideshow_stop'		=> array('default' => 'stop slideshow', 'group' => array('labels', 60)),
				'txt_group_status'			=> array('default' => 'Image %current% of %total%', 'group' => array('labels', 70))		
			),
			'legacy' => array (
				'header_activation'			=> null,
				'header_enabled'			=> null,
				'header_strings'			=> null,
				'header_ui'					=> null,
				'txt_numDisplayPrefix' 		=> null,
				'txt_numDisplaySeparator'	=> null,
				'enabled_compat'			=> null,
				'enabled_single'			=> array('enabled_post', 'enabled_page'),
				'caption_src'				=> 'ui_caption_src',
				'animate'					=> 'ui_animate',
				'overlay_opacity'			=> 'ui_overlay_opacity',
				'enabled_caption'			=> 'ui_enabled_caption',
				'enabled_desc'				=> 'ui_enabled_desc',
				'txt_closeLink'				=> 'txt_link_close',
				'txt_nextLink'				=> 'txt_link_next',
				'txt_prevLink'				=> 'txt_link_prev',
				'txt_startSlideshow'		=> 'txt_slideshow_start',	
				'txt_stopSlideshow'			=> 'txt_slideshow_stop',
				'loop'						=> 'group_loop',
				'autostart'					=> 'slideshow_autostart',
				'duration'					=> 'slideshow_duration',
				'txt_loadingMsg'			=> 'txt_loading'
			)
		);
		
		parent::init_options($options_config);
	}

	/**
	 * Set option titles
	 */
	function init_options_text() {
		$p = $this->util->get_plugin_textdomain();
		$options_config = array (
			'groups' 	=> array (
				'activation'	=> __('Activation', $p),
				'grouping'		=> __('Grouping', $p),
				'ui'			=> __('UI', $p),
				'labels'		=> __('Labels', $p)
			),
			'items'		=> array (
				'enabled'					=> __('Enable Lightbox Functionality', $p),
				'enabled_home'				=> __('Enable on Home page', $p),
				'enabled_post'				=> __('Enable on Posts', $p),
				'enabled_page'				=> __('Enable on Pages', $p),
				'enabled_archive'			=> __('Enable on Archive Pages (tags, categories, etc.)', $p),
				'enabled_widget'			=> __('Enable for Widgets', $p),
				'activate_attachments'		=> __('Activate image attachment links', $p),
				'validate_links'			=> __('Validate links', $p),
				'group_links'				=> __('Group image links (for displaying as a slideshow)', $p),
				'group_post'				=> __('Group image links by Post (e.g. on pages with multiple posts)', $p),
				'group_gallery'				=> __('Group gallery links separately', $p),
				'group_widget'				=> __('Group widget links separately', $p),
				'theme_default'				=> __('Theme', $p),
				'ui_autofit'				=> __('Automatically fit lightbox in browser', $p),
				'ui_animate'				=> __('Animate lightbox resizing', $p),
				'slideshow_autostart'		=> __('Automatically Start Slideshow', $p),
				'slideshow_duration'		=> __('Slide Duration (Seconds)', $p),
				'group_loop'				=> __('Loop through images', $p),
				'ui_overlay_opacity'		=> __('Overlay Opacity (0 - 1)', $p),
				'ui_enabled_caption'		=> __('Enable caption', $p),
				'ui_enabled_desc'			=> __('Enable description', $p),
				'ui_caption_src'			=> __('Set file name as caption when link title not set', $p),
				'txt_link_close'			=> __('Close link (for accessibility only, image used for button)', $p),
				'txt_loading'				=> __('Loading indicator', $p),
				'txt_link_next'				=> __('Next Image link', $p),
				'txt_link_prev'				=> __('Previous Image link', $p),
				'txt_slideshow_start'		=> __('Start Slideshow link', $p),
				'txt_slideshow_stop'		=> __('Stop Slideshow link', $p),
				'txt_group_status'			=> __('Slideshow status format', $p),
			)
		);
		
		parent::init_options_text($options_config);
	}
	
	function register_hooks() {
		parent::register_hooks();

		/* Admin */
		add_action('admin_menu', $this->m('admin_menus'));
		//Init lightbox admin
		// add_action('admin_init', $this->m('admin_settings'));
		// //Reset Settings
		// add_action('admin_action_' . $this->add_prefix('reset'), $this->m('admin_reset'));
		// add_action('admin_notices', $this->m('admin_notices'));
		// //Plugin listing
		// add_filter('plugin_action_links_' . $this->util->get_plugin_base_name(), $this->m('admin_plugin_action_links'), 10, 4);
		// add_action('in_plugin_update_message-' . $this->util->get_plugin_base_name(), $this->m('admin_plugin_update_message'), 10, 2);
		// add_filter('site_transient_update_plugins', $this->m('admin_plugin_update_transient'));
		
		/* Client-side */
		
		//Init lightbox
		$priority = 99;
		add_action('wp_footer', $this->m('client_init'), 11);
		add_action('wp_footer', $this->m('client_footer'), $priority);
		//Link activation
		add_filter('the_content', $this->m('activate_links'), $priority);
		//Gallery wrapping
		add_filter('the_content', $this->m('gallery_wrap'), 1);
		add_filter('the_content', $this->m('gallery_unwrap'), $priority + 1);
		
		/* Widgets */
		add_filter('sidebars_widgets', $this->m('sidebars_widgets'));
	}

	/* Methods */
	
	/*-** Admin **-*/

	/**
	 * Add admin menus
	 * @uses this->admin->add_theme_page
	 * @uses this->util->get_plugin_textdomain()
	 */
	function admin_menus() {
		$dom = $this->util->get_plugin_textdomain();
		$options_labels = array(
			'menu'			=> __('Lightbox', $dom),
			'header'		=> __('Lightbox Settings', $dom),
			'plugin_action'	=> __('Settings', $dom)
		);
		/*
		$menu_labels = $options_labels;
		$menu_labels['plugin_action'] = 'Menu Link';
		$section_labels = $options_labels;
		$section_labels['plugin_action'] = 'Section Link';
		$this->admin->add_menu('menu', $menu_labels);
		*/
		$labels_reset = array (
			'title'			=> __('Reset', $dom),
			'confirm'		=> __('Are you sure you want to reset settings?', $dom),
			'success'		=> __('Setting Reset', $dom),
			'failure'		=> __('Settings were not reset', $dom)
		);
		
		$this->admin->add_theme_page('options', $options_labels, $this->options);
		// $this->admin->add_section('section', 'media', $section_labels, $this->options);
		$this->admin->add_reset('reset', $labels_reset, $this->options);
	}
	
	/*-** Request **-*/

	/**
	 * Output current context to client-side
	 * @uses `wp_head` action hook
	 * @uses `admin_head` action hook
	 * @return void
	 */
	function set_client_context() {
		if ( !is_admin() && !$this->is_enabled() )
			return false;
		$ctx = new stdClass();
		$ctx->context = $this->util->get_context();
		$this->util->extend_client_object($ctx, true);
	}

	/*-** Functionality **-*/
	
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
			static $g_idx = 1;
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
			$content = str_replace($g_ph, $w->open . $this->process_links($g_content, 'gallery_' . $group) . $w->close, $content);
		}
		return $content;
	}
	
	/**
	 * Process links in content
	 * @global obj $wpdb DB instance
	 * @global obj $post Current post
	 * @param string $content Text containing links
	 * @param string (optional) $group Group to add links to (Default: none)
	 * @return string Content with processed links 
	 */
	function process_links($content, $group = null) {
		//Validate
		if ( !is_string($content) || empty($content) ) {
			return $content;
		}
		//Extract links
		$links = $this->get_links($content, true);
		//Do not process content without links
		if ( empty($links) ) {
			return $content;
		}
		//Process links
		$protocol = array('http://', 'https://');
		$domain = str_replace($protocol, '', strtolower(get_bloginfo('url')));
		$types = $this->get_media_types();
		
		//Format Group
		if ( $this->options->get_bool('group_links') ) {
			if ( is_scalar($group) ) {
				$group_base = trim(strval($group));
			} else {
				$group_base = '';
			}
		} else {
			$group_base = null;
		}
		
		//Iterate through links & add lightbox if necessary
		foreach ( $links as $link ) {
			//Init vars
			$pid = 0;
			$link_new = $link;
			$internal = false;
			
			//Parse link attributes
			$attrs = $this->util->parse_attribute_string($link_new, array('rel' => '', 'href' => ''));
			$attrs_legacy = explode(' ', $attrs['rel']);
			$h =& $attrs['href'];
			
			//Stop processing invalid, disabled
			if ( empty($h) 
				|| 0 === strpos($h, '#')
				|| $this->has_attribute($attrs, 'active', false) 
				|| in_array($this->add_prefix('off'), $attrs_legacy)
				) {
				continue;
			}
			
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
			
			elseif ( $this->util->has_file_extension($h, array('jpg', 'jpeg', 'gif', 'png')) ) {
				//Direct Image file
				$type = $types->img;
			}
			
			elseif ( $internal && is_local_attachment($h) && ( $pid = url_to_postid($h) ) && wp_attachment_is_image($pid) ) {
				//Attachment URI
				$type = $types->att;
			}
			
			//Stop processing if link type not valid
			if ( !$type || ( $type == $types->att && !$this->options->get_bool('activate_attachments') ) ) {
				continue;
			}
			
			//Set group (if necessary)
			if ( null !== $group_base ) {
				$group = $group_base;
				$g_name = 'group';
				//Get preset group attribute
				$g = ( $this->has_attribute($attrs, $g_name) ) ? $this->get_attribute($attrs, $g_name) : ''; 
				if ( is_string($g) && ($g = trim($g)) && !empty($g) ) {
					$group = $g;
				}
				//Group links by post?
				if ( !$this->widget_processing && $this->options->get_bool('group_post') ) {
					global $post;
					$group = ( !empty($group) ) ? implode('_', array($post->ID, $group)) : $post->ID;
				}
				//Default group
				if ( empty($group) ) {
					$group = $this->get_prefix();
				}
				
				//Set group attribute
				$attrs = $this->set_attribute($attrs, $g_name, $group);
			}
			
			//Activate link
			$attrs = $this->set_attribute($attrs, 'active');
			
			//Process internal links
			if ( $internal ) {
				//Mark as internal
				$attrs = $this->set_attribute($attrs, 'internal', $pid);
				//Add to media items array
				$this->cache_media_item($h, $type, $pid);
			}
			
			//Update link in content
			$link_new = '<a ' . $this->util->build_attribute_string($attrs) . '>';
			$content = str_replace($link, $link_new, $content);
			unset($h);
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
	 * Sets options/settings to initialize lightbox functionality on page load
	 * @return void
	 */
	function client_init() {
		if ( ! $this->is_enabled() ) {
			return;
		}
		echo PHP_EOL . '<!-- SLB -->' . PHP_EOL;
		//Get options
		$options = $this->options->build_client_output();

		//Build client output
		echo $this->util->build_script_element($this->util->call_client_method('View.init', $options), 'init', true, true);
		echo '<!-- /SLB -->' . PHP_EOL;
	}
	
	/**
	 * Output code in footer
	 * > Media attachment URLs
	 * @uses `_wp_attached_file` to match attachment ID to URI
	 * @uses `_wp_attachment_metadata` to retrieve attachment metadata
	 */
	function client_footer() {
		echo '<!-- X-M -->';
		//Stop if not enabled
		if ( !$this->is_enabled() ) {
			return;
		}
		echo '<!-- SLB-M -->' . PHP_EOL;
		
		$client_out = array();
		
		/* Load cached media */
		if ( $this->has_cached_media_items() ) {
			global $wpdb;
			
			$this->media_items = array();
			$props = array('id', 'type', 'desc', 'title', 'source');
			$props = (object) array_combine($props, $props);
	
			//Separate media into buckets by type
			$m_bucket = array();
			$type = $id = null;
			
			$m_items =& $this->get_cached_media_items();
			foreach ( $m_items as $uri => $p ) {
				$type = $p[$props->type];
				if ( empty($type) ) {
					continue;
				}
				if ( !isset($m_bucket[$type]) ) {
					$m_bucket[$type] = array();
				}
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
						$f = $pd->meta_value;
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
							$this->media_items[$uri] = $m;
						}
					}
				}
			}
			
			//Media attachments
			if ( !empty($this->media_items) ) {
				$obj = 'View.assets';
				$client_out[] = $this->util->extend_client_object($obj, $this->media_items);
			}
		}
		if ( !empty($client_out) ) {
			echo $this->util->build_script_element($client_out, 'footer');	
		}
		echo PHP_EOL . '<!-- /SLB-M -->' . PHP_EOL;
	}

	/*-** Media **-*/
	
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
	 * Cache media properties for later processing
	 * @param string $uri URI for internal media (e.g. direct uri, attachment uri, etc.) 
	 * @param string $type Media type (image, attachment, etc.)
	 * @param int (optional) $id ID of media item (if available) (Default: NULL)
	 */
	function cache_media_item($uri, $type, $id = null) {
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
		return ( isset($this->media_items_raw[$uri]) ) ? true : false;
	}
	
	/**
	 * Retrieve cached media item
	 * @param string $uri Media item URI
	 * @return array|null Media item properties (NULL if not set)
	 */
	function get_cached_media_item($uri) {
		$ret = null;
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
		return ( !empty($this->media_items_raw) ) ? true : false; 
	}
	
	/*-** Theme **-*/
	
	/**
	 * Retrieve theme
	 * @param string $id ID of theme to retrieve
	 * @return SLB_Theme Theme instance
	 * @TODO Refactor
	 */
	function get_theme($id = '') {
		//Default: Get current theme if no theme specified
		if ( !$this->themes->has_item($id) ) {
			$id = $this->options->get_value('theme');
			if ( !$this->themes->has_item($id) ) {
				$id = $this->themes->get_default_id();
			}
		}
		return $this->themes->get_item($id);
	}

	/*-** Grouping **-*/
	
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
	 * @uses `the_content` Filter hook
	 * @uses gallery_wrap_callback to Wrap shortcodes for grouping
	 * @param string $content Post content
	 * @return string Modified post content
	 */
	function gallery_wrap($content) {
		if ( !$this->is_enabled() )
			return $content;
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
	 * @uses `the_content` filter hook
	 * @param $content Post content
	 * @return string Modified post content
	 */
	function gallery_unwrap($content) {
		if ( !$this->is_enabled() )
			return $content;
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
		if ( is_admin() || empty($wp_registered_widgets) || $widgets_processed || !is_object($this->options) || !$this->is_enabled() || !$this->options->get_bool('enabled_widget') )
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
	 * Original widget display handler is called inside of an output buffer & links in output are processed before sending to browser 
	 * @param array $args Widget instance properties
	 * @param int (optional) $widget_args Additional widget args (usually the widget's instance number)
	 * @see WP_Widget::display_callback() for more information
	 * @see sidebars_widgets() for callback modification
	 * @global $wp_registered_widgets
	 * @uses widget_process_links() to Process links in widget content
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
		$this->widget_processing = true;
		//Start output buffer
		ob_start();
		//Call original callback
		call_user_func_array($cb, $params);
		//Flush output buffer
		echo $this->widget_process_links(ob_get_clean(), $wid);
		$this->widget_processing = false;
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
	
	/*-** Helpers **-*/

	/**
	 * Build attribute name
	 * Makes sure name is only prefixed once
	 * @param string $name (optional) Attribute base name
	 * @return string Formatted attribute name
	 */
	function make_attribute_name($name = '') {
		//Validate
		if ( !is_string($name) ) {
			$name = '';
		} else {
			$name = trim($name);
		}
		//Setup
		$sep = '-';
		$top = 'data';
		//Generate valid name
		if ( strpos($name, $top . $sep . $this->get_prefix()) !== 0 ) {
			$name = $top . $sep . $this->add_prefix($name, $sep);
		}
		return $name;
	}

	/**
	 * Set attribute to array
	 * Attribute is added to array if it does not exist
	 * @param array $attrs Current attribute array
	 * @param string $name Name of attribute to add
	 * @param string (optional) $value Attribute value
	 * @return array Updated attribute array
	 */
	function set_attribute($attrs, $name, $value = true) {
		//Validate
		$attrs = $this->get_attributes($attrs, false);
		if ( !is_string($name) || empty($name) ) {
			return $attrs;
		}
		if ( !is_scalar($value) ) {
			$value = true;
		}
		//Add attribute
		$attrs = array_merge($attrs, array( $this->make_attribute_name($name) => strval($value) ));
		
		return $attrs;
	}
	
	/**
	 * Convert attribute string into array
	 * @param string $attr_string Attribute string
	 * @param bool (optional) $internal Whether only internal attributes should be evaluated (Default: TRUE)
	 * @return array Attributes as associative array
	 */
	function get_attributes($attr_string, $internal = true) {
		if ( is_string($attr_string) ) {
			$attr_string = $this->util->parse_attribute_string($attr_string);
		}
		$ret = ( is_array($attr_string) ) ? $attr_string : array();
		//Filter out external attributes
		if ( !empty($ret) && is_bool($internal) && $internal ) {
			$ret_f = array();
			foreach ( $ret as $key => $val ) {
				if ( strpos($key, $this->make_attribute_name()) == 0 ) {
					$ret_f[$key] = $val;
				}
			}
			if ( !empty($ret_f) ) {
				$ret = $ret_f;
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
		//Validate
		$attrs = $this->get_attributes($attrs, $internal);
		if ( $internal ) {
			$attr = $this->make_attribute_name($attr);
		}
		if ( isset($attrs[$attr]) ) {
			$ret = $attrs[$attr];
		}
		return $ret;
	}
	
	/**
	 * Checks if attribute exists
	 * If supplied, the attribute's value is also validated
	 * @param string|array $attrs Attributes to retrieve attribute value from
	 * @param string $attr Attribute name to retrieve
	 * @param mixed $value (optional) Attribute value to check for
	 * @param bool $internal (optional) Whether to check only internal attributes (Default: TRUE)
	 * @return bool Whether or not attribute (with matching value if specified) exists
	 */
	function has_attribute($attrs, $attr, $value = null, $internal = true) {
		$a = $this->get_attribute($attrs, $attr, $internal);
		$ret = false;
		if ( $a !== false ) {
			$ret = true;
			//Check value
			if ( null != $value && is_scalar($value) ) {
				$ret = ( $a == strval($value) ) ? true : false;
			}
		}
		return $ret;
	}
	
	/**
	 * Build JS object of UI strings when initializing lightbox
	 * @return array UI strings
	 */
	function build_labels() {
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
}

?>
