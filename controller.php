<?php
/**
 * Controller
 * @package Simple Lightbox
 * @author Archetyped
 */
class SLB_Lightbox extends SLB_Base {

	/*-** Properties **-*/

	protected $model = true;

	/**
	 * Fields
	 * @var SLB_Fields
	 */
	public $fields = null;

	/**
	 * Themes collection
	 * @var SLB_Themes
	 */
	public $themes = null;

	/**
	 * Content types
	 * @var SLB_Content_Handlers
	 */
	public $handlers = null;

	/**
	 * Template tags
	 * @var SLB_Template_Tags
	 */
	public $template_tags = null;

	/**
	 * Media item template.
	 *
	 * @var array {
	 *     Media item properties.
	 *
	 *     @type int $id WP post ID. Default null.
	 *     @type string $type Item type. Default null.
	 *     @type string $source Source URI.
	 *     @type bool $internal Internal resource. Default false.
	 *     @type string $title Item title.
	 *     @type string $caption Item caption.
	 *     @type string $description Item description.
	 *     @type array $media {
	 *         Media properties (indexed by size name).
	 *         Original size = 'full'.
	 *
	 *         @type string $file File URI.
	 *         @type int $width File width in pixels.
	 *         @type int $height File height in pixels.
	 *     }
	 *     @type array $meta {
	 *         Item metadata.
	 *     }
	 * }
	 */
	private $media_item_template = [
		'id'       => null,
		'type'     => null,
		'internal' => false,
		'source'   => '',
	];

	/**
	 * Processed media items for output to client.
	 *
	 * @var object[string] {
	 *     Media item properties.
	 *
	 *     @index string Unique ID (system-generated).
	 *
	 *     @see $media_item_template.
	 * }
	 */
	private $media_items = [];

	/**
	 * Collection of unprocessed media items.
	 *
	 * @var array {
	 *     @type object[string] $props {
	 *         Media item properties.
	 *
	 *         @index string Unique ID (system-generated).
	 *
	 *         @see $media_item_template
	 *     }
	 *     @type string[string] $uri {
	 *         Cached URIs.
	 *
	 *         @index string URI.
	 *
	 *         @type string Item ID (points to item in `props` array)
	 *     }
	 * }
	 */
	private $media_items_raw = [
		'props' => [],
		'uri'   => [],
	];

	/**
	 * Manage excluded content
	 * @var object
	 */
	private $exclude = null;

	private $groups = array(
		'auto'   => 0,
		'manual' => array(),
	);

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
	 * Used to track if widget is currently being processed or not
	 * Set to Widget ID currently being processed
	 * @var bool|string
	 */
	private $widget_processing = false;

	/**
	 * Parameters for widget being processed
	 * @param array
	 */
	private $widget_processing_params = null;

	/**
	 * Manage nested widget processing
	 * Used to avoid premature widget output
	 * @var int
	 */
	private $widget_processing_level = 0;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		// Init instances
		$this->fields = new SLB_Fields();
		$this->themes = new SLB_Themes( $this );
		if ( ! is_admin() ) {
			$this->template_tags = new SLB_Template_Tags( $this );
		}
	}

	/* Init */

	public function _init() {
		parent::_init();
		$this->util->do_action( 'init' );
	}

	/**
	 * Declare client files (scripts, styles)
	 * @uses parent::_client_files()
	 * @return void
	 */
	protected function _client_files( $files = null ) {
		$js_path  = 'client/js/';
		$js_path .= ( SLB_DEV ) ? 'dev' : 'prod';
		$files    = array(
			'scripts' => array(
				'core' => array(
					'file'      => "$js_path/lib.core.js",
					'deps'      => 'jquery',
					'enqueue'   => false,
					'in_footer' => true,
				),
				'view' => array(
					'file'      => "$js_path/lib.view.js",
					'deps'      => array( '[core]' ),
					'context'   => array( array( 'public', $this->m( 'is_request_valid' ) ) ),
					'in_footer' => true,
				),
			),
			'styles'  => array(
				'core' => array(
					'file'    => 'client/css/app.css',
					'context' => array( 'public' ),
				),
			),
		);
		parent::_client_files( $files );
	}

	/**
	 * Register hooks
	 * @uses parent::_hooks()
	 */
	protected function _hooks() {
		parent::_hooks();

		/* Admin */
		add_action( 'admin_menu', $this->m( 'admin_menus' ) );

		/* Init */
		add_action( 'wp', $this->m( '_hooks_init' ) );
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function _hooks_init() {
		if ( ! $this->is_enabled() ) {
			return;
		}
		// Callback for hooks that need to have method added to end of stack.
		$cb_hooks_add_last = $this->m( 'hooks_add_last' );

		// Init lightbox
		add_action( 'wp_footer', $this->m( 'client_footer' ) );
		$this->util->add_action( 'footer_script', $this->m( 'client_init' ), 1 );
		$this->util->add_filter( 'footer_script', $this->m( 'client_script_media' ), 2 );
		// Link activation
		add_filter( 'the_content', $cb_hooks_add_last );
		add_filter( 'get_post_galleries', $cb_hooks_add_last );
		$this->util->add_filter( 'post_process_links', $this->m( 'activate_groups' ), 11 );
		$this->util->add_filter( 'validate_uri_regex', $this->m( 'validate_uri_regex_default' ), 1 );
		//  Content exclusion
		$this->util->add_filter( 'pre_process_links', $this->m( 'exclude_content' ) );
		$this->util->add_filter( 'pre_exclude_content', $this->m( 'exclude_shortcodes' ) );
		$this->util->add_filter( 'post_process_links', $this->m( 'restore_excluded_content' ) );

		// Grouping
		if ( $this->options->get_bool( 'group_post' ) ) {
			$this->util->add_filter( 'get_group_id', $this->m( 'post_group_id' ), 1 );
		}

		// Shortcode grouping
		if ( $this->options->get_bool( 'group_gallery' ) ) {
			add_filter( 'the_content', $this->m( 'group_shortcodes' ), 1 );
		}

		// Widgets
		if ( $this->options->get_bool( 'enabled_widget' ) ) {
			add_action( 'dynamic_sidebar_before', $this->m( 'widget_process_nested' ) );
			add_action( 'dynamic_sidebar', $cb_hooks_add_last );
			add_filter( 'dynamic_sidebar_params', $this->m( 'widget_process_inter' ), 1 );
			add_action( 'dynamic_sidebar_after', $cb_hooks_add_last );
			add_action( 'dynamic_sidebar_after', $cb_hooks_add_last );
		} else {
			add_action( 'dynamic_sidebar_before', $this->m( 'widget_block_start' ) );
			add_action( 'dynamic_sidebar_after', $this->m( 'widget_block_finish' ) );
		}

		// Menus
		if ( $this->options->get_bool( 'enabled_menu' ) ) {
			add_filter( 'wp_nav_menu', $cb_hooks_add_last );
		}
	}

	/**
	 * Adds hook(s) to end of filter/action stack.
	 *
	 * @param string $data Optional. Data being filtered.
	 * @return string Filtered content.
	 */
	public function hooks_add_last( $data = null ) {
		global $wp_filter;

		$tag = current_filter();

		// Stop processing on invalid hook.
		if ( empty( $tag ) || ! is_string( $tag ) ) {
			return $data;
		}

		// Get lowest priority for filter.
		$max_priority = max( array_keys( $wp_filter[ $tag ]->callbacks ) );
		// Add priority offset
		if ( $max_priority < PHP_INT_MAX ) {
			$max_priority += min( 123, PHP_INT_MAX - $max_priority );
		}

		switch ( $tag ) {
			case 'the_content':
				add_filter( $tag, $this->m( 'activate_links' ), $max_priority );
				break;
			case 'get_post_galleries':
				add_filter( $tag, $this->m( 'activate_galleries' ), $max_priority );
				break;
			case 'dynamic_sidebar':
				add_action( $tag, $this->m( 'widget_process_start' ), $max_priority );
				break;
			case 'dynamic_sidebar_after':
				add_action( $tag, $this->m( 'widget_process_finish' ), $max_priority );
				add_action( $tag, $this->m( 'widget_process_nested_finish' ), $max_priority );
				break;
			case 'wp_nav_menu':
				add_filter( $tag, $this->m( 'menu_process' ), $max_priority, 2 );
				break;
		}

		// Remove init hook.
		remove_filter( $tag, $this->m( __FUNCTION__ ) );

		// Return content (for filters).
		return $data;
	}

	/**
	 * Add post ID to link group ID
	 * @uses `SLB::get_group_id` filter
	 * @param array $group_segments Group ID segments
	 * @return array Modified group ID segments
	 */
	public function post_group_id( $group_segments ) {
		if ( in_the_loop() ) {
			// Prepend post ID to group ID
			$post = get_post();
			if ( $post ) {
				array_unshift( $group_segments, $post->ID );
			}
		}
		return $group_segments;
	}

	/**
	 * Init options
	 */
	protected function _options() {
		// Setup options
		$opts = array(
			'groups' => array(
				'activation' => array(
					'title'    => __( 'Activation', 'simple-lightbox' ),
					'priority' => 10,
				),
				'grouping'   => array(
					'title'    => __( 'Grouping', 'simple-lightbox' ),
					'priority' => 20,
				),
				'ui'         => array(
					'title'    => __( 'UI', 'simple-lightbox' ),
					'priority' => 30,
				),
				'labels'     => array(
					'title'    => __( 'Labels', 'simple-lightbox' ),
					'priority' => 40,
				),
			),
			'items'  => array(
				'enabled'             => array(
					'title'   => __( 'Enable Lightbox Functionality', 'simple-lightbox' ),
					'default' => true,
					'group'   => array( 'activation', 10 ),
				),
				'enabled_home'        => array(
					'title'   => __( 'Enable on Home page', 'simple-lightbox' ),
					'default' => true,
					'group'   => array( 'activation', 20 ),
				),
				'enabled_post'        => array(
					'title'   => __( 'Enable on Single Posts', 'simple-lightbox' ),
					'default' => true,
					'group'   => array( 'activation', 30 ),
				),
				'enabled_page'        => array(
					'title'   => __( 'Enable on Pages', 'simple-lightbox' ),
					'default' => true,
					'group'   => array( 'activation', 40 ),
				),
				'enabled_archive'     => array(
					'title'   => __( 'Enable on Archive Pages (tags, categories, etc.)', 'simple-lightbox' ),
					'default' => true,
					'group'   => array( 'activation', 50 ),
				),
				'enabled_widget'      => array(
					'title'   => __( 'Enable for Widgets', 'simple-lightbox' ),
					'default' => false,
					'group'   => array( 'activation', 60 ),
				),
				'enabled_menu'        => array(
					'title'   => __( 'Enable for Menus', 'simple-lightbox' ),
					'default' => false,
					'group'   => array( 'activation', 60 ),
				),
				'group_links'         => array(
					'title'   => __( 'Group items (for displaying as a slideshow)', 'simple-lightbox' ),
					'default' => true,
					'group'   => array( 'grouping', 10 ),
				),
				'group_post'          => array(
					'title'   => __( 'Group items by Post (e.g. on pages with multiple posts)', 'simple-lightbox' ),
					'default' => true,
					'group'   => array( 'grouping', 20 ),
				),
				'group_gallery'       => array(
					'title'   => __( 'Group gallery items separately', 'simple-lightbox' ),
					'default' => false,
					'group'   => array( 'grouping', 30 ),
				),
				'group_widget'        => array(
					'title'   => __( 'Group widget items separately', 'simple-lightbox' ),
					'default' => false,
					'group'   => array( 'grouping', 40 ),
				),
				'group_menu'          => array(
					'title'   => __( 'Group menu items separately', 'simple-lightbox' ),
					'default' => false,
					'group'   => array( 'grouping', 50 ),
				),
				'ui_autofit'          => array(
					'title'     => __( 'Resize lightbox to fit in window', 'simple-lightbox' ),
					'default'   => true,
					'group'     => array( 'ui', 10 ),
					'in_client' => true,
				),
				'ui_animate'          => array(
					'title'     => __( 'Enable animations', 'simple-lightbox' ),
					'default'   => true,
					'group'     => array( 'ui', 20 ),
					'in_client' => true,
				),
				'slideshow_autostart' => array(
					'title'     => __( 'Start Slideshow Automatically', 'simple-lightbox' ),
					'default'   => true,
					'group'     => array( 'ui', 30 ),
					'in_client' => true,
				),
				'slideshow_duration'  => array(
					'title'     => __( 'Slide Duration (Seconds)', 'simple-lightbox' ),
					'default'   => '6',
					'attr'      => array(
						'size'      => 3,
						'maxlength' => 3,
					),
					'group'     => array( 'ui', 40 ),
					'in_client' => true,
				),
				'group_loop'          => array(
					'title'     => __( 'Loop through items', 'simple-lightbox' ),
					'default'   => true,
					'group'     => array( 'ui', 50 ),
					'in_client' => true,
				),
				'ui_overlay_opacity'  => array(
					'title'     => __( 'Overlay Opacity (0 - 1)', 'simple-lightbox' ),
					'default'   => '0.8',
					'attr'      => array(
						'size'      => 3,
						'maxlength' => 3,
					),
					'group'     => array( 'ui', 60 ),
					'in_client' => true,
				),
				'ui_title_default'    => array(
					'title'     => __( 'Enable default title', 'simple-lightbox' ),
					'default'   => false,
					'group'     => array( 'ui', 70 ),
					'in_client' => true,
				),
				'txt_loading'         => array(
					'title'   => __( 'Loading indicator', 'simple-lightbox' ),
					'default' => 'Loading',
					'group'   => array( 'labels', 20 ),
				),
				'txt_close'           => array(
					'title'   => __( 'Close button', 'simple-lightbox' ),
					'default' => 'Close',
					'group'   => array( 'labels', 10 ),
				),
				'txt_nav_next'        => array(
					'title'   => __( 'Next Item button', 'simple-lightbox' ),
					'default' => 'Next',
					'group'   => array( 'labels', 30 ),
				),
				'txt_nav_prev'        => array(
					'title'   => __( 'Previous Item button', 'simple-lightbox' ),
					'default' => 'Previous',
					'group'   => array( 'labels', 40 ),
				),
				'txt_slideshow_start' => array(
					'title'   => __( 'Start Slideshow button', 'simple-lightbox' ),
					'default' => 'Start slideshow',
					'group'   => array( 'labels', 50 ),
				),
				'txt_slideshow_stop'  => array(
					'title'   => __( 'Stop Slideshow button', 'simple-lightbox' ),
					'default' => 'Stop slideshow',
					'group'   => array( 'labels', 60 ),
				),
				'txt_group_status'    => array(
					'title'   => __( 'Slideshow status format', 'simple-lightbox' ),
					'default' => 'Item %current% of %total%',
					'group'   => array( 'labels', 70 ),
				),
			),
			'legacy' => array(
				'header_activation'       => null,
				'header_enabled'          => null,
				'header_strings'          => null,
				'header_ui'               => null,
				'activate_attachments'    => null,
				'validate_links'          => null,
				'enabled_compat'          => null,
				'enabled_single'          => array( 'enabled_post', 'enabled_page' ),
				'enabled_caption'         => null,
				'enabled_desc'            => null,
				'ui_enabled_caption'      => null,
				'ui_caption_src'          => null,
				'ui_enabled_desc'         => null,
				'caption_src'             => null,
				'animate'                 => 'ui_animate',
				'overlay_opacity'         => 'ui_overlay_opacity',
				'loop'                    => 'group_loop',
				'autostart'               => 'slideshow_autostart',
				'duration'                => 'slideshow_duration',
				'txt_numDisplayPrefix'    => null,
				'txt_numDisplaySeparator' => null,
				'txt_closeLink'           => 'txt_link_close',
				'txt_nextLink'            => 'txt_link_next',
				'txt_prevLink'            => 'txt_link_prev',
				'txt_startSlideshow'      => 'txt_slideshow_start',
				'txt_stopSlideshow'       => 'txt_slideshow_stop',
				'txt_loadingMsg'          => 'txt_loading',
				'txt_link_next'           => 'txt_nav_next',
				'txt_link_prev'           => 'txt_nav_prev',
				'txt_link_close'          => 'txt_close',
			),
		);

		parent::_set_options( $opts );
	}

	/* Methods */

	/*-** Admin **-*/

	/**
	 * Add admin menus
	 * @uses this->admin->add_theme_page
	 */
	function admin_menus() {
		// Build options page
		$lbls_opts = array(
			'menu'          => __( 'Lightbox', 'simple-lightbox' ),
			'header'        => __( 'Lightbox Settings', 'simple-lightbox' ),
			'plugin_action' => __( 'Settings', 'simple-lightbox' ),
		);
		$pg_opts   = $this->admin->add_theme_page( 'options', $lbls_opts )
			->require_form()
			->add_content( 'options', 'Options', $this->options );

		// Add Support information
		$support = $this->util->get_plugin_info( 'SupportURI' );
		if ( ! empty( $support ) ) {
			$pg_opts->add_content( 'support', __( 'Feedback & Support', 'simple-lightbox' ), $this->m( 'theme_page_callback_support' ), 'secondary' );
		}

		// Add Actions
		$lbls_reset = array(
			'title'   => __( 'Reset', 'simple-lightbox' ),
			'confirm' => __( 'Are you sure you want to reset Simple Lightbox\'s settings?', 'simple-lightbox' ),
			'success' => __( 'Settings have been reset', 'simple-lightbox' ),
			'failure' => __( 'Settings were not reset', 'simple-lightbox' ),
		);
		$this->admin->add_action( 'reset', $lbls_reset, $this->options );
	}

	/**
	 * Support information
	 */
	public function theme_page_callback_support() {
		// Description
		$desc = __( '<p>Simple Lightbox thrives on your feedback!</p><p>Click the button below to <strong>get help</strong>, <strong>request a feature</strong>, or <strong>provide some feedback</strong>!</p>', 'simple-lightbox' );
		echo $desc;
		// Link
		$lnk_uri = $this->util->get_plugin_info( 'SupportURI' );
		$lnk_txt = __( 'Get Support &amp; Provide Feedback', 'simple-lightbox' );
		echo $this->util->build_html_link(
			$lnk_uri,
			$lnk_txt,
			array(
				'target' => '_blank',
				'class'  => 'button',
			)
		);
	}

	/*-** Functionality **-*/

	/**
	 * Checks whether lightbox is currently enabled/disabled
	 * @return bool TRUE if lightbox is currently enabled, FALSE otherwise
	 */
	function is_enabled() {
		static $ret = null;
		if ( is_null( $ret ) ) {
			$ret = ( ! is_admin() && $this->options->get_bool( 'enabled' ) && ! is_feed() ) ? true : false;
			if ( $ret ) {
				$opt = '';
				// Determine option to check
				if ( is_home() || is_front_page() ) {
					$opt = 'home';
				} elseif ( is_singular() ) {
					$opt = ( is_page() ) ? 'page' : 'post';
				} elseif ( is_archive() || is_search() ) {
					$opt = 'archive';
				}
				// Check sub-option
				if ( ! empty( $opt ) ) {
					// Prefix option name.
					$opt = 'enabled_' . $opt;
					if ( $this->options->has( $opt ) ) {
						$ret = $this->options->get_bool( $opt );
					}
				}
			}
		}
		// Filter return value
		if ( ! is_admin() ) {
			$ret = $this->util->apply_filters( 'is_enabled', $ret );
		}
		// Return value (force boolean)
		return ! ! $ret;
	}

	/**
	 * Make sure content is valid for processing/activation
	 *
	 * @param string $content Content to validate
	 * @return bool TRUE if content is valid (FALSE otherwise)
	 */
	protected function is_content_valid( $content ) {
		// Invalid hooks
		if ( doing_filter( 'get_the_excerpt' ) ) {
			return false;
		}

		// Non-string value
		if ( ! is_string( $content ) ) {
			return false;
		}

		// Empty string
		$content = trim( $content );
		if ( empty( $content ) ) {
			return false;
		}

		// Content is valid
		return $this->util->apply_filters( 'is_content_valid', true, $content );
	}

	/**
	 * Activates galleries extracted from post
	 * @see get_post_galleries()
	 * @param array $galleries A list of galleries in post
	 * @return A list of galleries with links activated
	 */
	function activate_galleries( $galleries ) {
		// Validate
		if ( empty( $galleries ) ) {
			return $galleries;
		}
		// Check galleries for HTML output
		$gallery = reset( $galleries );
		if ( is_array( $gallery ) ) {
			return $galleries;
		}

		// Activate galleries
		$group = ( $this->options->get_bool( 'group_gallery' ) ) ? true : null;
		foreach ( $galleries as $key => $val ) {
			if ( ! is_null( $group ) ) {
				$group = 'gallery_' . $key;
			}
			// Activate links in gallery
			$gallery = $this->process_links( $val, $group );

			// Save modified gallery
			$galleries[ $key ] = $gallery;
		}

		return $galleries;
	}

	/**
	 * Scans post content for image links and activates them
	 *
	 * Lightbox will not be activated for feeds
	 * @param string $content Content to activate
	 * @param string (optonal) $group Group ID for content
	 * @return string Post content
	 */
	public function activate_links( $content, $group = null ) {
		// Validate content
		if ( ! $this->is_content_valid( $content ) ) {
			return $content;
		}
		// Filter content before processing links
		$content = $this->util->apply_filters( 'pre_process_links', $content );

		// Process links
		$content = $this->process_links( $content, $group );

		// Filter content after processing links
		$content = $this->util->apply_filters( 'post_process_links', $content );

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
	protected function process_links( $content, $group = null ) {
		// Extract links
		$links = $this->get_links( $content, true );
		// Do not process content without links
		if ( empty( $links ) ) {
			return $content;
		}
		// Process links
		static $protocol   = array( 'http://', 'https://' );
		static $qv_att     = 'attachment_id';
		static $uri_origin = null;
		if ( ! is_array( $uri_origin ) ) {
			$uri_parts  = array_fill_keys( array( 'scheme', 'host', 'path' ), '' );
			$uri_origin = wp_parse_args( wp_parse_url( strtolower( home_url() ) ), $uri_parts );
		}
		static $uri_proto = null;
		if ( empty( $uri_proto ) ) {
			$uri_proto = (object) array(
				'raw'    => '',
				'source' => '',
				'parts'  => '',
			);
		}
		$uri_parts_required = array( 'host' => '' );

		// Setup group properties
		$g_props = (object) array(
			'enabled'       => $this->options->get_bool( 'group_links' ),
			'attr'          => 'group',
			'base'          => '',
			'legacy_prefix' => 'lightbox[',
			'legacy_suffix' => ']',
		);
		if ( $g_props->enabled ) {
			$g_props->base = ( is_scalar( $group ) ) ? trim( strval( $group ) ) : '';
		}

		// Initialize content handlers
		if ( ! ( $this->handlers instanceof SLB_Content_Handlers ) ) {
			$this->handlers = new SLB_Content_Handlers( $this );
		}

		// Iterate through and activate supported links

		foreach ( $links as $link ) {
			// Init vars
			$pid         = 0;
			$link_new    = $link;
			$uri         = clone $uri_proto;
			$type        = false;
			$props_extra = array();
			$key         = null;
			$internal    = false;

			// Parse link attributes
			$attrs = $this->util->parse_attribute_string( $link_new, array( 'href' => '' ) );
			// Get URI
			$uri->raw = $attrs['href'];

			// Stop processing invalid links
			if ( ! $this->validate_uri( $uri->raw )
				|| $this->has_attribute( $attrs, 'active' ) // Previously-processed.
				) {
				continue;
			}

			// Normalize URI (make absolute)
			$uri->source = WP_HTTP::make_absolute_url( $uri->raw, $uri_origin['scheme'] . '://' . $uri_origin['host'] );

			// URI cached?
			$key = $this->get_media_item_id( $uri->source );

			// Internal URI? (e.g. attachments)
			if ( ! $key ) {
				$uri->parts = array_merge( $uri_parts_required, (array) wp_parse_url( $uri->source ) );
				$internal   = ( $uri->parts['host'] === $uri_origin['host'] ) ? true : false;

				// Attachment?
				if ( $internal && is_local_attachment( $uri->source ) ) {
					$pid = url_to_postid( $uri->source );
					$src = wp_get_attachment_url( $pid );
					if ( ! ! $src ) {
						$uri->source       = $src;
						$props_extra['id'] = $pid;
						// Check cache for attachment source URI
						$key = $this->get_media_item_id( $uri->source );
					}
					unset( $src );
				}
			}

			// Determine content type
			if ( ! $key ) {
				// Get handler match
				$hdl_result = $this->handlers->match( $uri->source );
				if ( ! ! $hdl_result->handler ) {
					$type        = $hdl_result->handler->get_id();
					$props_extra = $hdl_result->props;
					// Updated source URI
					if ( isset( $props_extra['uri'] ) ) {
						$uri->source = $props_extra['uri'];
						unset( $props_extra['uri'] );
					}
				}

				// Cache valid item
				if ( ! ! $type ) {
					$key = $this->cache_media_item( $uri, $type, $internal, $props_extra );
				}
			}

			// Stop processing invalid links
			if ( ! $key ) {
				// Cache invalid URI
				$this->validated_uris[ $uri->source ] = false;
				if ( $uri->raw !== $uri->source ) {
					$this->validated_uris[ $uri->raw ] = false;
				}
				continue;
			}

			// Activate link
			$this->set_attribute( $attrs, 'active' );
			$this->set_attribute( $attrs, 'asset', $key );
			// Mark internal links
			if ( $internal ) {
				$this->set_attribute( $attrs, 'internal', $pid );
			}

			// Set group (if enabled)
			if ( $g_props->enabled ) {
				$group = array();
				// Get preset group attribute
				$g = ( $this->has_attribute( $attrs, $g_props->attr ) ) ? $this->get_attribute( $attrs, $g_props->attr ) : '';
				if ( ! empty( $g ) ) {
						$group[] = $g;
				} elseif ( ! empty( $g_props->base ) ) {
					$group[] = $g_props->base;
				}

				/**
				 * Filter group ID components
				 *
				 * @see process_links()
				 *
				 * @param array $group Components used to build group ID
				 */
				$group = $this->util->apply_filters( 'get_group_id', $group );

				// Default group
				if ( empty( $group ) || ! is_array( $group ) ) {
					$group = $this->get_prefix();
				} else {
					$group = implode( '_', $group );
				}

				// Set group attribute
				$this->set_attribute( $attrs, $g_props->attr, $group );
				unset( $g );
			}

			// Filter attributes
			$attrs = $this->util->apply_filters( 'process_link_attributes', $attrs );

			// Update link in content
			$link_new = '<a ' . $this->util->build_attribute_string( $attrs ) . '>';
			$content  = str_replace( $link, $link_new, $content );
		}

		// Handle widget content
		if ( ! ! $this->widget_processing && 'the_content' === current_filter() ) {
			$content = $this->exclude_wrap( $content );
		}

		return $content;
	}

	/**
	 * Retrieve HTML links in content
	 * @param string $content Content to get links from
	 * @param bool (optional) $unique Remove duplicates from returned links (Default: FALSE)
	 * @return array Links in content
	 */
	function get_links( $content, $unique = false ) {
		$rgx   = "/\<a\b(?:(?!\shref=|\>).)*\shref=[^\>\<]++\>/i";
		$links = [];
		preg_match_all( $rgx, $content, $links );
		$links = $links[0];
		if ( $unique ) {
			$links = array_unique( $links );
		}
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
	protected function validate_uri( $uri ) {
		static $patterns = null;
		// Previously-validated URI
		if ( isset( $this->validated_uris[ $uri ] ) ) {
			return $this->validated_uris[ $uri ];
		}

		$valid = true;
		// Boilerplate validation
		if ( empty( $uri ) // Empty
			|| 0 === strpos( $uri, '#' ) // Anchor
			) {
			$valid = false;
		}

		// Regex matching
		if ( $valid ) {
			// Get patterns
			if ( is_null( $patterns ) ) {
				$patterns = $this->util->apply_filters( 'validate_uri_regex', array() );
			}
			// Iterate through patterns until match found
			foreach ( $patterns as $pattern ) {
				if ( 1 === preg_match( $pattern, $uri ) ) {
					$valid = false;
					break;
				}
			}
		}

		// Cache
		$this->validated_uris[ $uri ] = $valid;
		return $valid;
	}

	/**
	 * Add URI validation regex pattern
	 * @param
	 */
	public function validate_uri_regex_default( $patterns ) {
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
	function client_init( $client_script ) {
		// Get options
		$options = $this->options->build_client_output();

		// Load UI Strings
		$labels = $this->build_labels();
		if ( ! empty( $labels ) ) {
			$options['ui_labels'] = $labels;
		}

		// Build client output
		$client_script[] = $this->util->call_client_method( 'View.init', $options );
		return $client_script;
	}

	/**
	 * Output code in footer
	 * > Media attachment URLs
	 * @uses `_wp_attached_file` to match attachment ID to URI
	 * @uses `_wp_attachment_metadata` to retrieve attachment metadata
	 */
	function client_footer() {
		if ( ! $this->has_cached_media_items() ) {
			return false;
		}

		// Set up hooks
		add_action( 'wp_print_footer_scripts', $this->m( 'client_footer_script' ) );

		// Build client output
		$this->util->do_action( 'footer' );
	}

	/**
	 * Output client footer scripts
	 */
	function client_footer_script() {
		$client_script = $this->util->apply_filters( 'footer_script', array() );
		if ( ! empty( $client_script ) ) {
			echo $this->util->build_script_element( $client_script, 'footer', true, true );
		}
	}

	/**
	 * Add media information to client output
	 *
	 * @param array $client_script Client script commands.
	 * @return array Modified script commands.
	 * TODO Refactor
	 */
	public function client_script_media( $client_script ) {
		global $wpdb;

		// Init.
		$this->media_items = $this->get_cached_media_items();

		// Extract internal links for additional processing.
		$m_internals = [];
		foreach ( $this->media_items as $key => $p ) {
			if ( $p->internal ) {
				$m_internals[ $key ] =& $this->media_items[ $key ];
			}
		}
		// Cleanup.
		unset( $key, $p );

		// Process internal links.
		if ( ! empty( $m_internals ) ) {
			$uris_base  = [];
			$uri_prefix = wp_upload_dir();
			$uri_prefix = $this->util->normalize_path( $uri_prefix['baseurl'], true );
			foreach ( $m_internals as $key => $p ) {
				// Prepare internal links.
				// Create relative URIs for attachment data retrieval.
				if ( ! $p->id && strpos( $p->source, $uri_prefix ) === 0 ) {
					$uris_base[ str_replace( $uri_prefix, '', $p->source ) ] = $key;
				}
			}
			// Cleanup.
			unset( $key, $p );

			// Retrieve attachment IDs.
			$uris_flat = "('" . implode( "','", array_keys( $uris_base ) ) . "')";
			$q         = $wpdb->prepare( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE `meta_key` = %s AND LOWER(`meta_value`) IN $uris_flat LIMIT %d", '_wp_attached_file', count( $uris_base ) );
			$pids      = $wpdb->get_results( $q );
			// Match IDs to URIs.
			if ( $pids ) {
				foreach ( $pids as $pd ) {
					$file =& $pd->meta_value;
					if ( isset( $uris_base[ $file ] ) ) {
						$m_internals[ $uris_base[ $file ] ]->id = absint( $pd->post_id );
					}
				}
			}
			// Cleanup.
			unset( $uris_base, $uris_flat, $q, $pids, $pd, $file );
		}

		// Process items with attachment IDs.
		$pids = [];
		foreach ( $this->media_items as $key => $p ) {
			// Add post ID to query.
			if ( ! ! $p->id ) {
				// Create array for ID (support multiple URIs per ID).
				if ( ! isset( $pids[ $p->id ] ) ) {
					$pids[ $p->id ] = [];
				}
				// Add URI to ID.
				$pids[ $p->id ][] = $key;
			}
		}
		// Cleanup.
		unset( $key, $p );

		// Retrieve attachment properties.
		if ( ! empty( $pids ) ) {
			$pids_flat = array_keys( $pids );
			// Retrieve attachment post data.
			$atts = get_posts(
				array(
					'post_type' => 'attachment',
					'include'   => $pids_flat,
				)
			);

			// Process attachments.
			if ( $atts ) {
				$props_post_map = [
					'title'       => 'post_title',
					'caption'     => 'post_excerpt',
					'description' => 'post_content',
				];

				foreach ( $atts as $att ) {
					$data = [];
					// Remap post data to metadata.
					foreach ( $props_post_map as $props_post_key => $props_post_source ) {
						$data[ $props_post_key ] = $att->{$props_post_source};
					}
					// Cleanup.
					unset( $props_post_key, $props_post_source );

					// Save data to corresponding media item(s).
					if ( isset( $pids[ $att->ID ] ) ) {
						foreach ( $pids[ $att->ID ] as $key ) {
							$this->media_items[ $key ] = (object) array_merge( (array) $this->media_items[ $key ], $data );
						}
					}
				}
				// Cleanup.
				unset( $att, $data );
			}
			// Cleanup.
			unset( $atts, $atts_meta, $m, $a, $uri, $pids, $pids_flat );
		}

		// Filter media items.
		$this->media_items = $this->util->apply_filters( 'media_items', $this->media_items );

		// Build client output.
		$obj             = 'View.assets';
		$client_script[] = $this->util->extend_client_object( $obj, $this->media_items );
		return $client_script;
	}

	/*-** Media **-*/

	/**
	 * Cache media properties for later processing
	 * @uses array self::$media_items_raw Stores media items for output
	 * @param object $uri URI to cache
	 * Members
	 * > raw: Raw Link URI
	 * > source: Source URI (e.g. for attachment URIs)
	 * @param string $type Media type (image, attachment, etc.)
	 * @param bool $internal TRUE if media is internal (e.g. attachment)
	 * @param array $props (optional) Properties to store for item (Default: NULL)
	 * @return string Unique ID for cached media item
	 */
	private function cache_media_item( $uri, $type, $internal, $props = null ) {
		// Validate.
		if ( ! is_object( $uri ) || ! is_string( $type ) ) {
			return false;
		}
		// Check if URI already cached.
		$key = $this->get_media_item_id( $uri->source );
		// Cache new item.
		if ( null === $key ) {
			// Generate Unique ID.
			do {
				$key = mt_rand();
			} while ( isset( $this->media_items_raw['props'][ $key ] ) );
			// Build properties object.
			$i = $this->media_item_template;
			if ( is_array( $props ) && ! empty( $props ) ) {
				$i = array_merge( $i, $props );
			}
			$i = array_merge(
				$i,
				[
					'type'     => $type,
					'source'   => $uri->source,
					'internal' => $internal,
				]
			);
			// Cache item properties.
			$this->media_items_raw['props'][ $key ] = (object) $i;
			// Cache Source URI (point to properties object).
			$this->media_items_raw['uri'][ $uri->source ] = $key;
		}
		return $key;
	}

	/**
	 * Retrieve ID for media item
	 * @uses self::$media_items_raw
	 * @param string $uri Media item URI
	 * @return string|null Media item ID (Default: NULL if URI doesn't exist in collection)
	 */
	private function get_media_item_id( $uri ) {
		if ( $this->media_item_cached( $uri ) ) {
			return $this->media_items_raw['uri'][ $uri ];
		}
		return null;
	}

	/**
	 * Checks if media item has already been cached
	 * @param string $uri URI of media item
	 * @return boolean Whether media item has been cached
	 */
	private function media_item_cached( $uri ) {
		return ( is_string( $uri ) && ! empty( $uri ) && isset( $this->media_items_raw['uri'][ $uri ] ) ) ? true : false;
	}

	/**
	 * Retrieve cached media item
	 * @param string $uri Media item URI
	 * @return object|null Media item properties (NULL if not set)
	 */
	private function get_cached_media_item( $uri ) {
		$key = $this->get_media_item_id( $uri );
		if ( null !== $key ) {
			return $this->media_items_raw['props'][ $key ];
		}
		return null;
	}

	/**
	 * Retrieve cached media items (properties)
	 * @uses self::$media_items_raw
	 * @return array Cached media items (objects)
	 */
	private function &get_cached_media_items() {
		return $this->media_items_raw['props'];
	}

	/**
	 * Check if media items have been cached
	 * @return boolean
	 */
	private function has_cached_media_items() {
		return ( empty( $this->media_items_raw['props'] ) ) ? false : true;
	}

	/*-** Exclusion **-*/

	/**
	 * Retrieve exclude object
	 * Initialize object properties if necessary
	 * @return object Exclude properties
	 */
	private function get_exclude() {
		// Initialize exclude data
		if ( ! is_object( $this->exclude ) ) {
			$this->exclude = (object) array(
				'tags'          => $this->get_exclude_tags(),
				'ph'            => $this->get_exclude_placeholder(),
				'group_default' => 'default',
				'cache'         => array(),
			);
		}
		return $this->exclude;
	}

	/**
	 * Get exclusion tags (open/close)
	 * Example: open => [slb_exclude], close => [/slb_exclude]
	 *
	 * @return object Exclusion tags
	 */
	private function get_exclude_tags() {
		static $tags = null;
		if ( null === $tags ) {
			/* Init tag elements */
			$tags = (object) [
				// Tag base.
				'base' => $this->add_prefix( 'exclude' ),
			];
			// Opening tag.
			$tags->open = $this->util->add_wrapper( $tags->base );
			// Closing tag.
			$tags->close = $this->util->add_wrapper( $tags->base, '[/', ']' );

			/* Build tag search pattern */

			// Pattern delimeter.
			$dlm = '#';
			// Pattern flags.
			$flags = 's';
			// Tag search pattern.
			$tags->search = $dlm . preg_quote( $tags->open, $dlm ) . '(.*?)' . preg_quote( $tags->close, $dlm ) . $dlm . $flags;
		}
		return $tags;
	}


	/**
	 * Get exclusion tag ("[slb_exclude]")
	 * @uses `get_exclude_tags()` to retrieve tag
	 *
	 * @param string $type (optional) Tag to retrieve (open or close)
	 * @return string Exclusion tag
	 */
	private function get_exclude_tag( $type = 'open' ) {
		// Validate
		$tags = $this->get_exclude_tags();
		if ( ! isset( $tags->{$type} ) ) {
			$type = 'open';
		}
		return $tags->{$type};
	}

	/**
	 * Build exclude placeholder
	 * @return object Exclude placeholder properties
	 */
	private function get_exclude_placeholder() {
		static $ph;
		if ( ! is_object( $ph ) ) {
			$ph = (object) array(
				'base'  => $this->add_prefix( 'exclude_temp' ),
				'open'  => '{{',
				'close' => '}}',
				'attrs' => array(
					'group' => '',
					'key'   => '',
				),
			);
			// Search Patterns
			$sub              = '(.+?)';
			$dlm              = '#';
			$flags            = 's';
			$ph->search       = $dlm . preg_quote( $ph->open, $dlm ) . $ph->base . '\s+' . $sub . preg_quote( $ph->close, $dlm ) . $dlm . $flags;
			$ph->search_group = str_replace( $sub, '(group="%s"\s+.?)', $ph->search );
			// Templates
			$attr_string = '';
			foreach ( $ph->attrs as $attr => $val ) {
				$attr_string .= ' ' . $attr . '="%s"';
			}
			$ph->template = $ph->open . $ph->base . $attr_string . $ph->close;
		}
		return $ph;
	}

	/**
	 * Wrap content in exclusion tags
	 * @uses `get_exclude_tag()` to wrap content with exclusion tag
	 * @param string $content Content to exclude
	 * @return string Content wrapped in exclusion tags
	 */
	private function exclude_wrap( $content ) {
		// Validate
		if ( ! is_string( $content ) ) {
			$content = '';
		}
		// Wrap
		$tags = $this->get_exclude_tags();
		return $tags->open . $content . $tags->close;
	}

	/**
	 * Remove excluded content
	 * Caches content for restoring later
	 * @param string $content Content to remove excluded content from
	 * @return string Updated content
	 */
	public function exclude_content( $content, $group = null ) {
		$ex = $this->get_exclude();
		// Setup cache
		if ( ! is_string( $group ) || empty( $group ) ) {
			$group = $ex->group_default;
		}
		if ( ! isset( $ex->cache[ $group ] ) ) {
			$ex->cache[ $group ] = array();
		}
		$cache =& $ex->cache[ $group ];

		$content = $this->util->apply_filters( 'pre_exclude_content', $content );

		// Search content
		$matches = null;
		if ( false !== strpos( $content, $ex->tags->open ) && preg_match_all( $ex->tags->search, $content, $matches ) ) {
			// Determine index
			$idx = ( ! ! end( $cache ) ) ? key( $cache ) : -1;
			$ph  = array();
			foreach ( $matches[1] as $midx => $match ) {
				// Update index
				$idx++;
				// Cache content
				$cache[ $idx ] = $match;
				// Build placeholder
				$ph[] = sprintf( $ex->ph->template, $group, $idx );
			}
			unset( $midx, $match );
			// Replace content with placeholder
			$content = str_replace( $matches[0], $ph, $content );

			// Cleanup
			unset( $matches, $ph );
		}

		return $content;
	}

	/**
	 * Exclude shortcodes from link activation
	 * @param string $content Content to exclude shortcodes from
	 * @return string Content with shortcodes excluded
	 */
	public function exclude_shortcodes( $content ) {
		// Get shortcodes to exclude
		$shortcodes = $this->util->apply_filters( 'exclude_shortcodes', array( $this->add_prefix( 'group' ) ) );
		// Set callback
		$shortcodes = array_fill_keys( $shortcodes, $this->m( 'exclude_shortcodes_handler' ) );
		return $this->util->do_shortcode( $content, $shortcodes );
	}

	/**
	 * Wrap shortcode in exclude tags
	 * @uses Util->make_shortcode() to rebuild original shortcode
	 *
	 * @param array $attr Shortcode attributes
	 * @param string $content Content enclosed in shortcode
	 * @param string $tag Shortcode name
	 * @return string Excluded shortcode
	 */
	public function exclude_shortcodes_handler( $attr, $content, $tag ) {
		$code = $this->util->make_shortcode( $tag, $attr, $content );
		// Exclude shortcode
		return $this->exclude_wrap( $code );
	}

	/**
	 * Restore excluded content
	 * @param string $content Content to restore excluded content to
	 * @return string Content with excluded content restored
	 */
	public function restore_excluded_content( $content, $group = null ) {
		$ex = $this->get_exclude();
		// Setup cache
		if ( ! is_string( $group ) || empty( $group ) ) {
			$group = $ex->group_default;
		}
		// Nothing to restore if cache group doesn't exist
		if ( ! isset( $ex->cache[ $group ] ) ) {
			return $content;
		}
		$cache =& $ex->cache[ $group ];

		// Search content for placeholders
		$matches = null;
		if ( false !== strpos( $content, $ex->ph->open . $ex->ph->base ) && preg_match_all( $ex->ph->search, $content, $matches ) ) {
			// Restore placeholders
			foreach ( $matches[1] as $idx => $ph ) {
				// Parse placeholder attributes
				$attrs = $this->util->parse_attribute_string( $ph, $ex->ph->attrs );
				// Validate
				if ( $attrs['group'] !== $group ) {
					continue;
				}
				// Restore content
				$attrs['key'] = intval( $attrs['key'] );
				$key          = $attrs['key'];

				if ( isset( $cache[ $key ] ) ) {
					$content = str_replace( $matches[0][ $idx ], $cache[ $key ], $content );
				}
			}
			// Cleanup
			unset( $idx, $ph, $matches, $key );
		}

		return $content;
	}

	/*-** Grouping **-*/

	/**
	 * Builds wrapper for grouping
	 * @return string Format for wrapping content in group
	 */
	function group_get_wrapper() {
		static $fmt = null;
		if ( is_null( $fmt ) ) {
			$fmt = $this->util->make_shortcode( $this->add_prefix( 'group' ), null, '%s' );
		}
		return $fmt;
	}

	/**
	 * Wraps shortcodes for automatic grouping
	 * @uses `the_content` Filter hook
	 * @uses group_shortcodes_handler to Wrap shortcodes for grouping
	 * @param string $content Post content
	 * @return string Modified post content
	 */
	function group_shortcodes( $content ) {
		if ( ! $this->is_content_valid( $content ) ) {
			return $content;
		}
		// Setup shortcodes to wrap
		$shortcodes = $this->util->apply_filters( 'group_shortcodes', array( 'gallery', 'nggallery' ) );
		// Set custom callback
		$shortcodes = array_fill_keys( $shortcodes, $this->m( 'group_shortcodes_handler' ) );
		// Process gallery shortcodes
		return $this->util->do_shortcode( $content, $shortcodes );
	}

	/**
	 * Groups shortcodes for later processing
	 * @param array $attr Shortcode attributes
	 * @param string $content Content enclosed in shortcode
	 * @param string $tag Shortcode name
	 * @return string Grouped shortcode
	 */
	function group_shortcodes_handler( $attr, $content, $tag ) {
		$code = $this->util->make_shortcode( $tag, $attr, $content );
		// Wrap shortcode
		return sprintf( $this->group_get_wrapper(), $code );
	}

	/**
	 * Activate groups in content
	 * @param string $content Content to activate
	 * @return string Updated content
	 */
	public function activate_groups( $content ) {
		return $this->util->do_shortcode( $content, array( $this->add_prefix( 'group' ) => $this->m( 'activate_groups_handler' ) ) );
	}

	/**
	 * Groups shortcodes for later processing
	 * @param array $attr Shortcode attributes
	 * @param string $content Content enclosed in shortcode
	 * @param string $tag Shortcode name
	 * @return string Grouped shortcode
	 */
	function activate_groups_handler( $attr, $content, $tag ) {
		// Get Group ID
		//  Custom group
		if ( isset( $attr['id'] ) ) {
			$group = $attr['id'];
			trim( $group );
		}
		//  Automatically-generated group
		if ( empty( $group ) ) {
			$group = 'auto_' . ( ++$this->groups['auto'] );
		}
		return $this->process_links( $content, $group );
	}

	/*-** Widgets **-*/

	/**
	 * Set widget up for processing/activation
	 * Buffers widget output for further processing
	 * @param array $widget_args Widget arguments
	 * @return void
	 */
	public function widget_process_start( $widget_args ) {
		// Do not continue if a widget is currently being processed (avoid nested processing)
		if ( 0 < $this->widget_processing_level ) {
			return;
		}
		// Start widget processing
		$this->widget_processing        = true;
		$this->widget_processing_params = $widget_args;
		// Enable widget grouping
		if ( $this->options->get_bool( 'group_widget' ) ) {
			$this->util->add_filter( 'get_group_id', $this->m( 'widget_group_id' ) );
		}
		// Begin output buffer
		ob_start();
	}

	/**
	 * Handles inter-widget processing
	 * After widget output generated, Before next widget starts
	 * @param array $params New widget parameters
	 */
	public function widget_process_inter( $params ) {
		$this->widget_process_finish();
		return $params;
	}

	/**
	 * Complete widget processing
	 * Activate widget output
	 * @uses $widget_processing
	 * @uses $widget_processing_level
	 * @uses $widget_processing_params
	 * @return void
	 */
	public function widget_process_finish() {
		/**
		 * Stop processing on conditions:
		 * - No widget is being processed
		 * - Processing a nested widget
		 */
		if ( ! $this->widget_processing || 0 < $this->widget_processing_level ) {
			return;
		}
		// Activate widget output
		$out = $this->activate_links( ob_get_clean() );

		// Clear grouping callback
		if ( $this->options->get_bool( 'group_widget' ) ) {
			$this->util->remove_filter( 'get_group_id', $this->m( 'widget_group_id' ) );
		}
		// End widget processing
		$this->widget_processing        = false;
		$this->widget_processing_params = null;
		// Output widget
		echo $out;
	}

	/**
	 * Add widget ID to link group ID
	 * Widget ID precedes all other group segments
	 * @uses `SLB::get_group_id` filter
	 * @param array $group_segments Group ID segments
	 * @return array Modified group ID segments
	 */
	public function widget_group_id( $group_segments ) {
		// Add current widget ID to group ID
		if ( isset( $this->widget_processing_params['id'] ) ) {
			array_unshift( $group_segments, $this->widget_processing_params['id'] );
		}
		return $group_segments;
	}

	/**
	 * Handles nested activation in widgets
	 * @uses widget_processing
	 * @uses $widget_processing_level
	 * @return void
	 */
	public function widget_process_nested() {
		// Stop if no widget is being processed
		if ( ! $this->widget_processing ) {
			return;
		}

		// Increment nesting level
		$this->widget_processing_level++;
	}

	/**
	 * Mark the end of a nested widget
	 * @uses $widget_processing_level
	 */
	public function widget_process_nested_finish() {
		// Decrement nesting level
		if ( 0 < $this->widget_processing_level ) {
			$this->widget_processing_level--;
		}
	}

	/**
	 * Begin blocking widget activation
	 * @return void
	 */
	public function widget_block_start() {
		$this->util->add_filter( 'is_content_valid', $this->m( 'widget_block_handle' ) );
	}

	/**
	 * Stop blocking widget activation
	 * @return void
	 */
	public function widget_block_finish() {
		$this->util->remove_filter( 'is_content_valid', $this->m( 'widget_block_handle' ) );
	}

	/**
	 * Handle widget activation blocking
	 */
	public function widget_block_handle( $is_content_valid ) {
		return false;
	}

	/*-** Menus **-*/

	/**
	 * Process navigation menu links
	 *
	 * @see wp_nav_menu()/filter: wp_nav_menu
	 *
	 * @param string $nav_menu HTML content for navigation menu.
	 * @param object $args     Navigation menu's arguments.
	 */
	public function menu_process( $nav_menu, $args ) {
		// Grouping
		if ( $this->options->get_bool( 'group_menu' ) ) {
			// Generate group ID for menu
			$group = 'menu';
			$sep   = '_';
			if ( ! empty( $args->menu_id ) ) {
				$group .= $sep . $args->menu_id;
			} elseif ( ! empty( $args->menu ) ) {
				$group .= $sep . ( ( is_object( $args->menu ) ) ? $args->menu->slug : $args->menu );
			}
			$group = $this->group_id_unique( $group );
		} else {
			$group = null;
		}

		// Process menu
		$nav_menu = $this->activate_links( $nav_menu, $group );

		return $nav_menu;
	}

	/**
	 * Generate unique group ID
	 *
	 * @param string $group Group ID to check
	 * @return string Unique group ID
	 */
	public function group_id_unique( $group ) {
		static $groups = array();
		while ( in_array( $group, $groups, true ) ) {
			$patt = '#-(\d+)$#';
			if ( preg_match( $patt, $group, $matches ) ) {
				$group = preg_replace( $patt, '-' . ( ++$matches[1] ), $group );
			} else {
				$group = $group . '-1';
			}
		}
		// Add final group ID to array
		$groups[] = $group;
		return $group;
	}

	/*-** Helpers **-*/

	/**
	 * Build attribute name
	 * Makes sure name is only prefixed once
	 * @param string $name (optional) Attribute base name
	 * @return string Formatted attribute name
	 */
	function make_attribute_name( $name = '' ) {
		// Validate
		if ( ! is_string( $name ) ) {
			$name = '';
		} else {
			$name = trim( $name );
		}
		// Setup
		$sep = '-';
		$top = 'data';
		// Generate valid name
		if ( strpos( $name, $top . $sep . $this->get_prefix() ) !== 0 ) {
			$name = $top . $sep . $this->add_prefix( $name, $sep );
		}
		return $name;
	}

	/**
	 * Sets attribute value.
	 *
	 * Attribute is added to array if it does not already exist.
	 *
	 * @param string|array $attrs Array to set attribute on (Passed by reference).
	 * @param string $name Name of attribute to set.
	 * @param scalar $value Optional. Attribute value.
	 * @return array Updated attributes.
	 */
	function set_attribute( &$attrs, $name, $value = true ) {
		// Validate
		$attrs = $this->get_attributes( $attrs, false );
		// Stop if attribute name or value is invalid.
		if ( ! is_string( $name ) || empty( trim( $name ) ) || ! is_scalar( $value ) ) {
			return $attrs;
		}
		// Set attribute value.
		$attrs = array_merge( $attrs, array( $this->make_attribute_name( $name ) => $value ) );

		return $attrs;
	}

	/**
	 * Converts attribute string into array.
	 *
	 * @param string|array $attrs Attribute string to convert. Associative array also accepted.
	 * @param bool $internal Optional. Return only internal attributes. Default True.
	 * @return array Associative array of attributes (`attribute-name => attribute-value`).
	 */
	function get_attributes( $attrs, $internal = true ) {
		// Parse attribute string.
		$attrs = $this->util->parse_attribute_string( $attrs );
		// Include only internal attributes (if necessary).
		if ( ! ! $internal && ! empty( $attrs ) ) {
			$prefix = $this->make_attribute_name();
			$attrs  = array_filter(
				$attrs,
				function( $key ) use ( $prefix ) {
					return ( strpos( $key, $prefix ) === 0 );
				},
				ARRAY_FILTER_USE_KEY
			);
		}

		return $attrs;
	}

	/**
	 * Retrieves an attributes value from attribute string or array.
	 *
	 * @param string|array $attrs Attribute string to retrieve attribute value from.
	 *                            Associative array also accepted.
	 * @param string $attr Attribute to retrieve value for.
	 * @param bool $internal Optional. Retrieve internal attribute. Default true.
	 * @return scalar|null Attribute value. Null if attribute is invalid or does not exist.
	 */
	function get_attribute( $attrs, $attr, $internal = true ) {
		// Validate.
		$invalid = null;
		if ( ! is_string( $attr ) || empty( trim( $attr ) ) ) {
			return $invalid;
		}
		$attrs = $this->get_attributes( $attrs, $internal );
		if ( empty( $attrs ) ) {
			return $invalid;
		}
		// Format attribute name.
		$attr = ( ! ! $internal ) ? $this->make_attribute_name( $attr ) : trim( $attr );
		// Stop if attribute does not exist or value is invalid.
		if ( ! isset( $attrs[ $attr ] ) || ! is_scalar( $attrs[ $attr ] ) ) {
			return $invalid;
		}
		// Retreive value.
		$ret = $attrs[ $attr ];
		// Validate value (type-specific).
		if ( is_string( $ret ) ) {
			$ret = trim( $ret );
			if ( '' === $ret ) {
				return $invalid;
			}
		}

		return $ret;
	}

	/**
	 * Checks if attribute exists.
	 *
	 * @param string|array $attrs Attribute string to retrieve attribute value from.
	 *                            Associative array also accepted.
	 * @param string $attr Attribute to retrieve value for.
	 * @param bool $internal Optional. Retrieve internal attribute. Default true.
	 * @return bool True if attribute exists. False if attribute does not exist.
	 */
	function has_attribute( $attrs, $attr, $internal = true ) {
		return ( null !== $this->get_attribute( $attrs, $attr, $internal ) );
	}

	/**
	 * Build JS object of UI strings when initializing lightbox
	 * @return array UI strings
	 */
	private function build_labels() {
		$ret = array();
		/* Get all UI options */
		$prefix      = 'txt_';
		$opt_strings = array_filter(
			array_keys( $this->options->get_items() ),
			function ( $opt ) use ( $prefix ) {
				return ( strpos( $opt, $prefix ) === 0 );
			}
		);
		if ( count( $opt_strings ) ) {
			/* Build array of UI options */
			foreach ( $opt_strings as $key ) {
				$name         = substr( $key, strlen( $prefix ) );
				$ret[ $name ] = $this->options->get_value( $key );
			}
		}
		return $ret;
	}
}
