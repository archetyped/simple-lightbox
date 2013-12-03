<?php 
/**
 * Controller
 * @package Simple Lightbox
 * @author Archetyped
 */
class SLB_Lightbox extends SLB_Base {
	
	/*-** Properties **-*/
	
	protected $model = true;
		
	/* Files */
	
	var $scripts = array (
		'core'			=> array (
			'file'		=> 'client/js/dev/lib.core.js',
			'deps'		=> 'jquery',
			'enqueue'	=> false,
			'in_footer'	=> true,
		),
		'view'			=> array (
			'file'		=> 'client/js/dev/lib.view.js',
			'deps'		=> array('jquery', '[core]'),
			'context'	=> array( array('public', '[is_request_valid]') ),
			'in_footer'	=> true,
		),
	);
	
	var $styles = array (
		'core'			=> array (
			'file'		=> 'client/css/app.css',
			'context'	=> array('public'),
		)
	);

	/**
	 * Fields
	 * @var SLB_Fields
	 */
	public $fields = null;
	
	/**
	 * Themes collection
	 * @var SLB_Themes
	 */
	var $themes = null;
	
	/**
	 * Content types
	 * @var SLB_Content_Handlers
	 */
	var $handlers = null;
	
	/**
	 * Template tags
	 * @var SLB_Template_Tags
	 */
	var $template_tags = null;

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
	private $media_items_raw = array();
	
	/**
	 * Validated URIs
	 * Caches validation of parsed URIs
	 * > Key: URI
	 * > Value: (bool) TRUE if valid
	 * @var array
	 */
	private $validated_uris = array();
	
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

	/**
	 * Constructor
	 */	
	function __construct() {
		parent::__construct();
		//Init instances
		$this->fields = new SLB_Fields();
		$this->themes = new SLB_Themes($this);
		if ( !is_admin() ) {
			$this->template_tags = new SLB_Template_Tags($this);
		}
	}
	
	/* Init */
	
	public function _init() {
		parent::_init();
		$this->util->do_action('init');
	}
	
	/**
	 * Register hooks
	 * @uses parent::_hooks()
	 */
	protected function _hooks() {
		parent::_hooks();

		/* Admin */
		add_action('admin_menu', $this->m('admin_menus'));
		
		/* Init */
		add_action('init', $this->m('_hooks_init'));
	}
	
	/**
	 * Init Hooks
	 */
	public function _hooks_init() {
		if ( $this->is_enabled() ) {
			$priority = $this->util->priority('low');
			
			//Init lightbox
			add_action('wp_footer', $this->m('client_footer'));
			$this->util->add_action('footer_script', $this->m('client_init'), 1);
			$this->util->add_filter('footer_script', $this->m('client_script_media'), 2);
			
			//Link activation
			add_filter('the_content', $this->m('activate_links'), $priority);
			add_filter('get_post_galleries', $this->m('activate_galleries'), $priority);
			$this->util->add_filter('validate_uri_regex', $this->m('validate_uri_regex_default'), 1);
			
			//Gallery wrapping
			add_filter('the_content', $this->m('gallery_wrap'), 1);
			add_filter('the_content', $this->m('gallery_unwrap'), $priority + 1);
			
			//Widgets
			add_filter('sidebars_widgets', $this->m('sidebars_widgets'));
		}
	}
	
	/**
	 * Init options
	 */
	protected function _options() {
		//Setup options
		$opts = array (
			'groups' 	=> array (
				'activation'	=> array ( 'title' => __('Activation', 'simple-lightbox'), 'priority' => 10),
				'grouping'		=> array ( 'title' => __('Grouping', 'simple-lightbox'), 'priority' => 20),
				'ui'			=> array ( 'title' => __('UI', 'simple-lightbox'), 'priority' => 30),
				'labels'		=> array ( 'title' => __('Labels', 'simple-lightbox'), 'priority' => 40),
			),
			'items'	=> array (
				'enabled'					=> array('title' => __('Enable Lightbox Functionality', 'simple-lightbox'), 'default' => true, 'group' => array('activation', 10)),
				'enabled_home'				=> array('title' => __('Enable on Home page', 'simple-lightbox'), 'default' => true, 'group' => array('activation', 20)),
				'enabled_post'				=> array('title' => __('Enable on Posts', 'simple-lightbox'), 'default' => true, 'group' => array('activation', 30)),
				'enabled_page'				=> array('title' => __('Enable on Pages', 'simple-lightbox'), 'default' => true, 'group' => array('activation', 40)),
				'enabled_archive'			=> array('title' => __('Enable on Archive Pages (tags, categories, etc.)', 'simple-lightbox'), 'default' => true, 'group' => array('activation', 50)),
				'enabled_widget'			=> array('title' => __('Enable for Widgets', 'simple-lightbox'), 'default' => false, 'group' => array('activation', 60)),
				'group_links'				=> array('title' => __('Group items (for displaying as a slideshow)', 'simple-lightbox'), 'default' => true, 'group' => array('grouping', 10)),
				'group_post'				=> array('title' => __('Group items by Post (e.g. on pages with multiple posts)', 'simple-lightbox'), 'default' => true, 'group' => array('grouping', 20)),
				'group_gallery'				=> array('title' => __('Group gallery items separately', 'simple-lightbox'), 'default' => false, 'group' => array('grouping', 30)),
				'group_widget'				=> array('title' => __('Group widget items separately', 'simple-lightbox'), 'default' => false, 'group' => array('grouping', 40)),
				'ui_autofit'				=> array('title' => __('Resize lightbox to fit in window', 'simple-lightbox'), 'default' => true, 'group' => array('ui', 10), 'in_client' => true),
				'ui_animate'				=> array('title' => __('Enable animations', 'simple-lightbox'), 'default' => true, 'group' => array('ui', 20), 'in_client' => true),
				'slideshow_autostart'		=> array('title' => __('Start Slideshow Automatically', 'simple-lightbox'), 'default' => true, 'group' => array('ui', 30), 'in_client' => true),
				'slideshow_duration'		=> array('title' => __('Slide Duration (Seconds)', 'simple-lightbox'), 'default' => '6', 'attr' => array('size' => 3, 'maxlength' => 3), 'group' => array('ui', 40), 'in_client' => true),
				'group_loop'				=> array('title' => __('Loop through items', 'simple-lightbox'),'default' => true, 'group' => array('ui', 50), 'in_client' => true),
				'ui_overlay_opacity'		=> array('title' => __('Overlay Opacity (0 - 1)', 'simple-lightbox'), 'default' => '0.8', 'attr' => array('size' => 3, 'maxlength' => 3), 'group' => array('ui', 60), 'in_client' => true),
				'txt_loading'				=> array('title' => __('Loading indicator', 'simple-lightbox'), 'default' => 'Loading', 'group' => array('labels', 20)),
				'txt_close'					=> array('title' => __('Close button', 'simple-lightbox'), 'default' => 'Close', 'group' => array('labels', 10)),
				'txt_nav_next'				=> array('title' => __('Next Item button', 'simple-lightbox'), 'default' => 'Next', 'group' => array('labels', 30)),
				'txt_nav_prev'				=> array('title' => __('Previous Item button', 'simple-lightbox'), 'default' => 'Previous', 'group' => array('labels', 40)),
				'txt_slideshow_start'		=> array('title' => __('Start Slideshow button', 'simple-lightbox'), 'default' => 'Start slideshow', 'group' => array('labels', 50)),
				'txt_slideshow_stop'		=> array('title' => __('Stop Slideshow button', 'simple-lightbox'),'default' => 'Stop slideshow', 'group' => array('labels', 60)),
				'txt_group_status'			=> array('title' => __('Slideshow status format', 'simple-lightbox'), 'default' => 'Item %current% of %total%', 'group' => array('labels', 70))		
			),
			'legacy' => array (
				'header_activation'			=> null,
				'header_enabled'			=> null,
				'header_strings'			=> null,
				'header_ui'					=> null,
				'activate_attachments'		=> null,
				'validate_links'			=> null,
				'enabled_compat'			=> null,
				'enabled_single'			=> array('enabled_post', 'enabled_page'),
				'enabled_caption'			=> null,
				'enabled_desc'				=> null,
				'ui_enabled_caption'		=> null,
				'ui_caption_src'			=> null,
				'ui_enabled_desc'			=> null,
				'caption_src'				=> null,
				'animate'					=> 'ui_animate',
				'overlay_opacity'			=> 'ui_overlay_opacity',
				'loop'						=> 'group_loop',
				'autostart'					=> 'slideshow_autostart',
				'duration'					=> 'slideshow_duration',
				'txt_numDisplayPrefix' 		=> null,
				'txt_numDisplaySeparator'	=> null,
				'txt_closeLink'				=> 'txt_link_close',
				'txt_nextLink'				=> 'txt_link_next',
				'txt_prevLink'				=> 'txt_link_prev',
				'txt_startSlideshow'		=> 'txt_slideshow_start',	
				'txt_stopSlideshow'			=> 'txt_slideshow_stop',
				'txt_loadingMsg'			=> 'txt_loading',
				'txt_link_next'				=> 'txt_nav_next',
				'txt_link_prev'				=> 'txt_nav_prev',
				'txt_link_close'			=> 'txt_close',
			)
		);
		
		parent::_options($opts);
	}

	/* Methods */
	
	/*-** Admin **-*/

	/**
	 * Add admin menus
	 * @uses this->admin->add_theme_page
	 */
	function admin_menus() {
		//Build options page
		$lbls_opts = array(
			'menu'			=> __('Lightbox', 'simple-lightbox'),
			'header'		=> __('Lightbox Settings', 'simple-lightbox'),
			'plugin_action'	=> __('Settings', 'simple-lightbox')
		);
		$pg_opts = $this->admin->add_theme_page('options', $lbls_opts)
			->require_form()
			->add_content('options', 'Options', $this->options);
			
		//Add Support information
		$support = $this->util->get_plugin_info('SupportURI');
		if ( !empty($support) ) {
			$pg_opts->add_content('support', __('Support', 'simple-lightbox'), $this->m('theme_page_callback_support'), 'secondary');
		}
		
		//Add Actions
		$lbls_reset = array (
			'title'			=> __('Reset', 'simple-lightbox'),
			'confirm'		=> __('Are you sure you want to reset settings?', 'simple-lightbox'),
			'success'		=> __('Settings have been reset', 'simple-lightbox'),
			'failure'		=> __('Settings were not reset', 'simple-lightbox')
		);
		$this->admin->add_action('reset', $lbls_reset, $this->options);
	}
	
	/**
	 * Support information
	 */
	public function theme_page_callback_support() {
		echo '<p>';
		_e("Getting support is easy!  Whether you're experiencing an issue or have a feature request, click the button below to get support right now!", 'simple-lightbox');
		echo '</p>';
		
		$lnk_txt = __('Get Support', 'simple-lightbox');
		$lnk_uri = $this->util->get_plugin_info('SupportURI');
		printf('<a href="%s" title="%s" target="_blank" class="button">%s</a>', $lnk_uri, esc_attr($lnk_txt), $lnk_txt);
	}

	/*-** Functionality **-*/
	
	/**
	 * Checks whether lightbox is currently enabled/disabled
	 * @return bool TRUE if lightbox is currently enabled, FALSE otherwise
	 */
	function is_enabled() {
		static $ret = null;
		if ( is_null($ret) ) {
			$ret = ( $this->options->get_bool('enabled') && !is_feed() ) ? true : false;
			if ( $ret ) {
				$opt = '';
				//Determine option to check
				if ( is_home() )
					$opt = 'home';
				elseif ( is_singular() ) {
					$opt = ( is_page() ) ? 'page' : 'post';
				}
				elseif ( is_archive() || is_search() )
					$opt = 'archive';
				//Check sub-option
				if ( !empty($opt) && ( $opt = 'enabled_' . $opt ) && $this->options->has($opt) ) {
					$ret = $this->options->get_bool($opt);
				}
			}
		}
		return $ret;
	}
	
	/**
	 * Activates galleries extracted from post
	 * @see get_post_galleries()
	 * @param array $galleries A list of galleries in post
	 * @return A list of galleries with links activated
	 */
	function activate_galleries($galleries) {
		//Validate
		if ( empty($galleries) ) {
			return $galleries;
		}
		//Check galleries for HTML output
		$gallery = reset($galleries);
		if ( is_array($gallery) ) {
			return $galleries;
		}
		
		//Activate galleries
		$group = ( $this->options->get_bool('group_gallery') ) ? true : null;
		foreach ( $galleries as $key => $val ) {
			if ( !is_null($group) ) {
				$group = 'gallery_' . $key;
			}
			//Activate links in gallery
			$gallery = $this->process_links($val, $group);
			
			//Save modified gallery
			$galleries[$key] = $gallery;
		}
		
		return $galleries;
	}
	
	/**
	 * Scans post content for image links and activates them
	 * 
	 * Lightbox will not be activated for feeds
	 * @param $content
	 * @return string Post content
	 */
	function activate_links($content) {
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
		$uri_home = strtolower(home_url());
		$domain = str_replace($protocol, '', $uri_home);
		$qv_att = 'attachment_id';
		
		//Setup group properties
		$g_props = (object) array(
			'enabled'			=> $this->options->get_bool('group_links'),
			'attr'				=> 'group',
			'base'				=> '',
			'legacy_prefix'		=> 'lightbox[',
			'legacy_suffix'		=> ']'
		);
		if ( $g_props->enabled ) {
			$g_props->base = ( is_scalar($group) ) ? trim(strval($group)) : '';
		}
		
		//Initialize content handlers
		if ( !( $this->handlers instanceof SLB_Content_Handlers ) ) {
			$this->handlers = new SLB_Content_Handlers($this);
		}
		
		//Iterate through and activate supported links
		$uri_proto = array('raw' => '', 'source' => '');
		
		foreach ( $links as $link ) {
			//Init vars
			$pid = 0;
			$link_new = $link;
			$internal = false;
			$q = null;
			$uri = (object) $uri_proto;
			$type = false;
			$props_extra = array();
			
			//Parse link attributes
			$attrs = $this->util->parse_attribute_string($link_new, array('href' => ''));
			$attrs_legacy = ( isset($attrs['rel']) && !empty($attrs['rel']) ) ? explode(' ', trim($attrs['rel'])) : array();
			//Get URI
			$uri->raw = $uri->source = $attrs['href'];
			
			//Stop processing invalid links
			if ( !$this->validate_uri($uri->raw)
				|| $this->has_attribute($attrs, 'active', false) //Previously-processed
				|| in_array($this->add_prefix('off'), $attrs_legacy) //Disabled (legacy)
				) {
				continue;
			}
			
			//Process legacy attributes
			if ( !empty($attrs_legacy) ) {
				//Group
				if ( $g_props->enabled ) {
					foreach ( $attrs_legacy as $attr ) {
						if ( 0 === strpos($attr, $g_props->legacy_prefix) && substr($attr, -1) == $g_props->legacy_suffix ) {
							$this->set_attribute($attrs, $g_props->attr, substr($attr, strlen($g_props->legacy_prefix), -1));
							break;
						}
					}
					unset($attr);
				}
			}
			
			//Check if item links to internal media (attachment)
			if ( 0 === strpos($uri->raw, '/') ) {
				//Relative URIs are always internal
				$internal = true;
				$uri->source = $uri_home . $uri->raw;
			} else {
				//Absolute URI
				$uri_dom = str_replace($protocol, '', strtolower($uri->raw));
				if ( strpos($uri_dom, $domain) === 0 ) {
					$internal = true;
				}
				unset($uri_dom);
			}
			
			//Get source URI (e.g. attachments)
			if ( $internal && is_local_attachment($uri->source) ) {
				$pid = url_to_postid($uri->source);
				$src = wp_get_attachment_url($pid);
				if ( !!$src ) {
					$uri->source = $src;
					$props_extra['id'] = $pid;
				}
				unset($src);
			}
			
			/* Determine content type */
			
			//Check if URI has already been processed
			if ( $this->media_item_cached($uri->source) ) {
				$i = $this->get_cached_media_item($uri->source);
				$type = $i->type;
			} else {
				//Get handler match
				$hdl_result = $this->handlers->match($uri->source);
				if ( !!$hdl_result->handler ) {
					$type = $hdl_result->handler->get_id();
					$props_extra = $hdl_result->props;
					//Updated source URI
					if ( isset($props_extra['uri']) ) {
						$uri->source = $props_extra['uri'];
						unset($props_extra['uri']);
					}
				}
			}
			
			//Stop processing if link type not valid
			if ( !$type ) {
				//Cache
				$this->validated_uris[$uri->raw] = false;
				continue;
			}
			
			//Set group (if necessary)
			if ( $g_props->enabled ) {
				//Get preset group attribute
				$g = ( $this->has_attribute($attrs, $g_props->attr) ) ? $this->get_attribute($attrs, $g_props->attr) : ''; 
				if ( is_string($g) && ($g = trim($g)) && !empty($g) ) {
					$group = $g;
				} else {
					$group = $g_props->base;
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
				$this->set_attribute($attrs, $g_props->attr, $group);
				unset($g);
			}
			
			//Activate link
			$this->set_attribute($attrs, 'active');
			
			//Process internal links
			if ( $internal ) {
				//Mark as internal
				$this->set_attribute($attrs, 'internal', $pid);
			}
			
			//Cache item attributes
			$this->cache_media_item($uri, $type, $internal, $props_extra);
			
			//Update link in content
			$link_new = '<a ' . $this->util->build_attribute_string($attrs) . '>';
			$content = str_replace($link, $link_new, $content);
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
	 * Validate URI
	 * Matches specified URI against internal & external regex patterns
	 * URI is **invalid** if it matches a regex
	 * 
	 * @param string $uri URI to validate
	 * @return bool TRUE if URI is valid
	 */
	protected function validate_uri($uri) {
		static $patterns = null;
		//Previously-validated URI
		if ( isset($this->validated_uris[$uri]) )
			return $this->validated_uris[$uri];

		$valid = true;
		//Boilerplate validation
		if ( empty($uri) //Empty
			|| 0 === strpos($uri, '#') //Anchor
			)
			$valid = false;

		//Regex matching
		if ( $valid ) {
			//Get patterns
			if ( is_null($patterns) ) {
				$patterns = $this->util->apply_filters('validate_uri_regex', array());
			}	
			//Iterate through patterns until match found
			foreach ( $patterns as $pattern ) {
				if ( 1 === preg_match($pattern, $uri) ) {
					$valid = false;
					break;
				}
			}
		}
		
		//Cache
		$this->validated_uris[$uri] = $valid;
		return $valid;
	}
	
	/**
	 * Add URI validation regex pattern
	 * @param 
	 */
	public function validate_uri_regex_default($patterns) {
		$patterns[] = '@^https?://[^/]*(wikipedia|wikimedia)\.org/wiki/file:.*$@i';
		return $patterns;
	} 
	
	/* Client */
	
	/**
	 * Checks if output should be loaded in current request
	 * @uses `is_enabled()`
	 * @uses `has_cached_media_items()`
	 * @return bool TRUE if output is being loaded into client
	 */
	public function is_request_valid() {
		return ( $this->is_enabled() && $this->has_cached_media_items() ) ? true : false;
	}
	
	/**
	 * Sets options/settings to initialize lightbox functionality on page load
	 * @return void
	 */
	function client_init($client_script) {
		//Get options
		$options = $this->options->build_client_output();
		
		//Load UI Strings
		if ( ($labels = $this->build_labels()) && !empty($labels) ) {
			$options['ui_labels'] = $labels;
		}
		
		//Build client output
		$client_script[] = $this->util->call_client_method('View.init', $options);
		return $client_script;
	}
	
	/**
	 * Output code in footer
	 * > Media attachment URLs
	 * @uses `_wp_attached_file` to match attachment ID to URI
	 * @uses `_wp_attachment_metadata` to retrieve attachment metadata
	 */
	function client_footer() {
		if ( !$this->has_cached_media_items() )
			return false;
		
		//Set up hooks
		add_action('wp_print_footer_scripts', $this->m('client_footer_script'));
		
		//Build client output
		$this->util->do_action('footer');
	}
	
	/**
	 * Output client footer scripts
	 */
	function client_footer_script() {
		$client_script = $this->util->apply_filters('footer_script', array());
		if ( !empty($client_script) ) {
			echo $this->util->build_script_element($client_script, 'footer', true, true);
		}
	}
	
	/**
	 * Add media information to client output
	 * 
	 * @param array $commands Client script commands
	 * @return array Modified script commands
	 */
	function client_script_media($client_script) {
		/* Load cached media */
		global $wpdb;
		
		$this->media_items = array();
		$props = array('id', 'type', 'description', 'title', 'source', 'caption');
		$props = (object) array_combine($props, $props);
		$props_map = array('description' => 'post_content', 'title' => 'post_title', 'caption' => 'post_excerpt');

		//Separate media into buckets by type
		//$m_bucket = array();
		$m_internals = array();
		$type = $id = null;
		
		$m_items = $this->media_items = $this->get_cached_media_items();
		foreach ( $m_items as $uri => $p ) {
			/*
			$type = $p->{$props->type};
			//Initialize bucket (if necessary)
			if ( !isset($m_bucket[$type]) ) {
				$m_bucket[$type] = array();
			}
			//Add item to bucket
			$m_bucket[$type][$uri] =& $m_items[$uri];
			*/
			//Set aside internal links for additional processing
			if ( $p->internal && !isset($m_internals[$uri]) ) {
				$m_internals[$uri] =& $m_items[$uri];
			}
		}
		unset($uri, $p);
		
		//Process internal links
		if ( !empty($m_internals) ) {
			$uris_base = array();
			$uri_prefix = wp_upload_dir();
			$uri_prefix = $this->util->normalize_path($uri_prefix['baseurl'], true);
			foreach ( $m_internals as $uri => $p ) {
				//Prepare internal links
				if ( !$p->id && strpos($p->source, $uri_prefix) === 0 ) {
					$uris_base[str_replace($uri_prefix, '', $p->source)] = $uri;
				}
			}
			unset($uri, $p);
			
			//Retrieve attachment IDs
			$uris_flat = "('" . implode("','", array_keys($uris_base)) . "')";
			$q = $wpdb->prepare("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE `meta_key` = %s AND LOWER(`meta_value`) IN $uris_flat LIMIT %d", '_wp_attached_file', count($uris_base));
			$pids = $wpdb->get_results($q);
			//Match IDs to URIs
			if ( $pids ) {
				foreach ( $pids as $pd ) {
					$file =& $pd->meta_value;
					if ( isset($uris_base[$file]) ) {
						$m_internals[ $uris_base[$file] ]->{$props->id} = absint($pd->post_id);
					}
				}
			}
			//Destroy worker vars
			unset($uris_base, $uris_flat, $q, $pids, $pd);
		}
		
		//Process items with attachment IDs
		$pids = array();
		foreach ( $m_items as $uri => $p ) {
			//Add post ID to query
			if ( !!$p->id ) {
				//Create array for ID (support multiple URIs per ID)
				if ( !isset($pids[$p->id]) ) {
					$pids[$p->id] = array();
				}
				//Add URI to ID
				$pids[$p->id][] = $uri;
			}
		}
		unset($uri, $p);
		
		//Retrieve attachment properties
		if ( !empty($pids) ) {
			$pids_flat = array_keys($pids);
			//Retrieve attachment post data
			$atts = get_posts(array('post_type' => 'attachment', 'include' => $pids_flat));
			
			//Process attachments
			if ( $atts ) {
				//Retrieve attachment metadata
				$pids_flat = "('" . implode("','", $pids_flat) . "')";
				$atts_meta = $wpdb->get_results($wpdb->prepare("SELECT `post_id`,`meta_value` FROM $wpdb->postmeta WHERE `post_id` IN $pids_flat AND `meta_key` = %s LIMIT %d", '_wp_attachment_metadata', count($atts)));
				//Restructure metadata array by post ID
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
				$props_size = array('file', 'width', 'height');
				$props_exclude = array('hwstring_small');
				foreach ( $atts as $att ) {
					//Set post data
					$m = array();
					//Remap post data to properties
					foreach ( $props_map as $prop_key => $prop_source ) {
						$m[$props->{$prop_key}] = $att->{$prop_source};
					}
					unset($prop_key, $prop_source);
					
					//Add metadata
					if ( isset($atts_meta[$att->ID]) && ($a = unserialize($atts_meta[$att->ID])) && is_array($a) ) {
						//Move original size into `sizes` array
						foreach ( $props_size as $d ) {
							if ( !isset($a[$d]) ) {
								continue;
							}
							$a['sizes']['original'][$d] = $a[$d];
							unset($a[$d]);
						}

						//Strip extraneous metadata
						foreach ( $props_exclude as $d ) {
							if ( isset($a[$d]) ) {
								unset($a[$d]);
							}
						}
						
						//Merge post data & meta data
						$m = array_merge($a, $m);
						//Destroy worker vars
						unset($a, $d);
					}
					
					//Save attachment data (post & meta) to original object(s)
					foreach ( $pids[$att->ID] as $uri ) {
						$this->media_items[$uri] = (object) array_merge( (array) $m_items[$uri], $m);
					}
				}
			}
			unset($atts, $atts_meta, $m, $a, $uri, $pids, $pids_flat);
		}

		//Expand URI variants
		foreach ( $m_items as $uri => $p ) {
			if ( empty($p->_entries) ) {
				continue;
			}
			foreach ( $p->_entries as $uri_variant ) {
				if ( isset($this->media_items[$uri_variant]) ) {
					continue;
				}
				$this->media_items[$uri_variant] = array('_parent' => $uri);
			}
		}
		unset($uri, $p, $uri_variant);
		
		//Build client output
		$obj = 'View.assets';
		$client_script[] = $this->util->extend_client_object($obj, $this->media_items);
		return $client_script;
	}

	/*-** Media **-*/
	
	/**
	 * Cache media properties for later processing
	 * @param object $uri URI to cache
	 * Members
	 * > raw: Raw Link URI
	 * > source: Source URI (e.g. for attachment URIs)
	 * @param string $type Media type (image, attachment, etc.)
	 * @param bool $internal TRUE if media is internal (e.g. attachment)
	 * @param array $props (optional) Properties to store for item (Default: NULL)
	 */
	private function cache_media_item($uri, $type, $internal, $props = null) {
		//Validate
		if ( !is_object($uri) || !is_string($type) ) {
			return false;
		}
		if ( !$this->media_item_cached($uri->source) ) {
			//Set properties
			$i = array('id' => null, '_entries' => array());
			if ( is_array($props) && !empty($props) ) {
				$i = array_merge($i, $props);
			}
			$i = array_merge($i, array('type' => $type, 'source' => $uri->source, 'internal' => $internal));
			//Cache media item
			$this->media_items_raw[$uri->source] = (object) $i;
		}
		//Add URI variants
		if ( $uri->raw != $uri->source && !in_array($uri->raw, $this->media_items_raw[$uri->source]->_entries) ) {
			$this->media_items_raw[$uri->source]->_entries[] = $uri->raw;
		}
	}
	
	/**
	 * Checks if media item has already been cached
	 * @param string $uri URI of media item
	 * @return boolean Whether media item has been cached
	 */
	private function media_item_cached($uri) {
		return ( is_string($uri) && isset($this->media_items_raw[$uri]) ) ? true : false;
	}
	
	/**
	 * Retrieve cached media item
	 * @param string $uri Media item URI
	 * @return object|null Media item properties (NULL if not set)
	 */
	private function get_cached_media_item($uri) {
		return ( $this->media_item_cached($uri) ) ? $this->media_items_raw[$uri] : null;
	}
	
	/**
	 * Retrieve cached media items
	 * @return array Cached media items
	 */
	private function &get_cached_media_items() {
		return $this->media_items_raw;
	}
	
	/**
	 * Check if media items have been cached
	 * @return boolean
	 */
	private function has_cached_media_items() {
		return ( empty($this->media_items_raw) ) ? false : true; 
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
		if ( is_admin() || empty($wp_registered_widgets) || $widgets_processed || !is_object($this->options) || !$this->options->get_bool('enabled_widget') )
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
	 * @param array $attrs Array to add attribute to (Passed by reference)
	 * @param string $name Name of attribute to add
	 * @param string (optional) $value Attribute value
	 * @return array Updated attribute array
	 */
	function set_attribute(&$attrs, $name, $value = true) {
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
	 * @see get_attribute()
	 * @return bool Whether or not attribute (with matching value if specified) exists
	 */
	function has_attribute($attrs, $attr, $value = null, $internal = true) {
		$a = $this->get_attribute($attrs, $attr, $internal);
		$ret = false;
		if ( $a !== false ) {
			$ret = true;
			//Check value
			if ( !is_null($value) ) {
				if ( is_string($value) ) {
					$ret = ( $a == strval($value) ) ? true : false;
				} elseif ( is_bool($value) ) {
					$ret = ( !!$a == $value ) ? true : false;
				} else {
					$ret = false;
				}
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