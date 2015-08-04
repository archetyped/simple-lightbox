<?php

/**
 * Admin functionality
 * @package Simple Lightbox
 * @subpackage Admin
 * @author Archetyped
 */
class SLB_Admin extends SLB_Base {
	/* Configuration */
	
	protected $mode = 'sub';

	/* Properties */
	
	/**
	 * Parent object
	 * Set on initialization
	 * @var obj
	 */
	protected $parent = null;
	
	/**
	 * Messages
	 * @var array
	 */
	protected $messages = array(
		'reset'			=> 'The settings have been reset',
		'beta'			=> '<strong class="%1$s">Notice:</strong> This update is a <strong class="%1$s">Beta version</strong>. It is highly recommended that you test the update on a test server before updating the plugin on a production server.',
		'access_denied'	=> 'You do not have sufficient permissions'
	);
	
	/* Views */
	
	/**
	 * Custom admin top-level menus
	 * Associative Array
	 *  > Key: Menu ID
	 *  > Val: Menu properties
	 * @var array
	 */
	protected $menus = array();
	
	/**
	 * Custom admin pages
	 * Associative Array
	 *  > Key: Page ID
	 *  > Val: Page properties
	 * @var array
	 */
	protected $pages = array();
	
	/**
	 * Custom admin sections
	 * Associative Array
	 *  > Key: Section ID
	 *  > Val: Section properties 
	 * @var array
	 */
	protected $sections = array();
	
	/**
	 * Actions
	 * Index Array
	 * @var array
	 */
	protected $actions = array();

	/* Constructor */
	
	public function __construct(&$parent) {
		parent::__construct();
		// Set parent
		if ( is_object($parent) )
			$this->parent = $parent;
	}
	
	/* Init */
	
	protected function _hooks() {
		parent::_hooks();
		// Init
		add_action('admin_menu', $this->m('init_menus'), 11);
		
		// Plugin actions
		add_action('admin_action_' . $this->add_prefix('admin'), $this->m('handle_action'));
		
		// Notices
		add_action('admin_notices', $this->m('handle_notices'));
		
		// Plugin listing
		add_filter('plugin_action_links_' . $this->util->get_plugin_base_name(), $this->m('plugin_action_links'), 10, 4);
		add_filter('plugin_row_meta', $this->m('plugin_row_meta'), 10, 4);
		add_action('in_plugin_update_message-' . $this->util->get_plugin_base_name(), $this->m('plugin_update_message'), 10, 2);
		add_filter('site_transient_update_plugins', $this->m('plugin_update_transient'));
	}
	
	/**
	 * Declare client files (scripts, styles)
	 * @uses parent::_client_files()
	 * @return void
	 */
	protected function _client_files($files = null) {
		$js_path = 'client/js/';
		$js_path .= ( SLB_DEV ) ? 'dev' : 'prod';
		$pfx = $this->get_prefix();
		$files = array (
			'scripts' => array (
				'admin'	=> array (
					'file'		=> "$js_path/lib.admin.js",
					'deps'		=> array('[core]'),
					'context'	=> array( "admin_page_$pfx" ),
					'in_footer'	=> true,
				),
			),
			'styles' => array (
				'admin'	=> array (
					'file'		=> 'client/css/admin.css',
					'context'	=> array( "admin_page_$pfx", 'admin_page_plugins' )
				)
			)
		);
		parent::_client_files($files);
	}
	
	/* Handlers */
	
	/**
	 * Handle routing of internal action to appropriate handler
	 */
	public function handle_action() {
		// Parse action
		$t = 'type';
		$g = 'group';
		$o = 'obj';
		$this->add_prefix_ref($t);
		$this->add_prefix_ref($g);
		$this->add_prefix_ref($o);
		$r =& $_REQUEST;
		
		// Retrieve view that initiated the action
		if ( isset($r[$t]) && 'view' == $r[$t] ) {
			if ( isset($r[$g]) && ( $prop = $r[$g] . 's' ) && property_exists($this, $prop) && is_array($this->{$prop}) && isset($r[$o]) && isset($this->{$prop}[$r[$o]]) ) {
				$view =& $this->{$prop}[$r[$o]];
				// Pass request to view
				$view->do_callback();
			}
		}
	}
	
	/**
	 * Display notices
	 * Messages are localized upon display
	 * @uses `admin_notices` action hook to display messages
	 */
	public function handle_notices() {
		$msgs = $this->util->apply_filters('admin_messages', array());
		foreach ( $msgs as $mid => $msg ) {
			// Filter out empty messages
			if ( empty($msg) )
				continue;
			// Build and display message
			$mid = $this->add_prefix('msg_' . $mid);
			?>
			<div id="<?php echo esc_attr($mid); ?>" class="updated fade">
				<p>
					<?php echo esc_html($msg);?>
				</p>
			</div>
			<?php
		}
	}
	
	/* Views */
	
	/**
	 * Adds settings section for plugin functionality
	 * Section is added to specified admin section/menu
	 * @uses `admin_init` hook
	 */
	public function init_menus() {
		// Add top level menus (when necessary)
		$menu;
		foreach ( $this->menus as $menu ) {
			// Register menu
			$hook = add_menu_page($menu->get_label('title'), $menu->get_label('menu'), $menu->get_capability(), $menu->get_id(), $menu->get_callback());
			// Add hook to menu object
			$menu->set_hookname($hook);
			$this->menus[$menu->get_id_raw()] =& $menu;
		}
		
		$page;
		// Add subpages
		foreach ( $this->pages as $page ) {
			// Build Arguments
			$args = array ( $page->get_label('header'), $page->get_label('menu'), $page->get_capability(), $page->get_id(), $page->get_callback() );
			$f = null;
			// Handle pages for default WP menus
			if ( $page->is_parent_wp() ) {
				$f = 'add_' . $page->get_parent() . '_page';
			}
			
			// Handle pages for custom menus
			if ( ! function_exists($f) ) {
				array_unshift( $args, $page->get_parent() );
				$f = 'add_submenu_page';
			}
			
			// Add admin page
			$hook = call_user_func_array($f, $args);
			// Save hook to page properties
			$page->set_hookname($hook);
			$this->pages[$page->get_id_raw()] =& $page;
		}
		
		// Add sections
		$section;
		foreach ( $this->sections as $section ) {
			add_settings_section($section->get_id(), $section->get_title(), $section->get_callback(), $section->get_parent());
		}
 	}


	/* Methods */
	
	/**
	 * Add a new view
	 * @param string $type View type
	 * @param string $id Unique view ID
	 * @param array $args Arguments to pass to view constructor
	 * @return Admin_View|bool View instance (FALSE if view was not properly initialized)
	 */
	protected function add_view($type, $id, $args) {
		// Validate request
		$class = $this->add_prefix('admin_' . $type);
		$collection = $type . 's';
		if ( !class_exists($class) ) {
			$class = $this->add_prefix('admin_view');
			$collection = null;
		}
		// Create new instance
		$r = new ReflectionClass($class);
		$view = $r->newInstanceArgs($args);
		if ( $view->is_valid() && !empty($collection) && property_exists($this, $collection) && is_array($this->{$collection}) )
			$this->{$collection}[$id] =& $view;
		unset($r);
		return $view;
	}
	
	/**
	 * Add plugin action link
	 * @uses `add_view()` to init/attach action instance
	 * @param string $id Action ID
	 * @param array $labels Text for action
	 * > title 		- Link text (also title attribute value)
	 * > confirm 	- Confirmation message
	 * > success 	- Success message
	 * > failure 	- Failure message
	 * @param array $data Additional data for action
	 * @return obj Action instance
	 */
	public function add_action($id, $labels, $data = null) {
		$args = func_get_args();
		return $this->add_view('action', $id, $args);
	}
	
	/*-** Menus **-*/
	
	/**
	 * Adds custom admin panel
	 * @param string $id Menu ID
	 * @param string|array $labels Text labels
	 * @param int $pos (optional) Menu position in navigation (index order)
	 * @return Admin_Menu Menu instance
	 */
	public function add_menu($id, $labels, $position = null) {
		$args = array ( $id, $labels, null, null, null, $position );
		return $this->add_view('menu', $id, $args);
	}
	
	/* Page */
	
	/**
	 * Add admin page
	 * @uses this->pages
	 * @param string $id Page ID (unique)
	 * @param string $parent Menu ID to add page to
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * 	> menu: Menu title
	 * 	> header: Page header
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return Admin_Page Page instance
	 */
	public function add_page($id, $parent, $labels, $callback = null, $capability = null) {
		$args = func_get_args();
		return $this->add_view('page', $id, $args);
	}
	
	/* WP Pages */
	
	/**
	 * Add admin page to a standard WP menu
	 * @uses this->add_page()
	 * @param string $id Page ID (unique)
	 * @param string $parent Name of WP menu to add page to
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * 	> menu: Menu title
	 * 	> header: Page header
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return Admin_Page Page instance
	 */
	public function add_wp_page($id, $parent, $labels, $callback = null, $capability = null) {
		// Add page
		$pg = $this->add_page($id, $parent, $labels, $capability);
		// Set parent as WP
		if ( $pg ) {
			$pg->set_parent_wp();
		}
		return $pg;
	}
	
	/**
	 * Add admin page to Dashboard menu
	 * @see add_dashboard_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return Admin_Page Page instance
	 */
	public function add_dashboard_page($id, $labels, $callback = null, $capability = null) {
		return $this->add_wp_page($id, 'dashboard', $labels, $capability);
	}

	/**
	 * Add admin page to Comments menu
	 * @see add_comments_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	public function add_comments_page($id, $labels, $callback = null, $capability = null) {
		return $this->add_wp_page($id, 'comments', $labels, $capability);
	}
	
	/**
	 * Add admin page to Links menu
	 * @see add_links_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	public function add_links_page($id, $labels, $callback = null, $capability = null) {
		return $this->add_wp_page($id, 'links', $labels, $capability);
	}

	
	/**
	 * Add admin page to Posts menu
	 * @see add_posts_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	public function add_posts_page($id, $labels, $callback = null, $capability = null) {
		return $this->add_wp_page($id, 'posts', $labels, $capability);
	}
	
	/**
	 * Add admin page to Pages menu
	 * @see add_pages_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	public function add_pages_page($id, $labels, $callback = null, $capability = null) {
		return $this->add_wp_page($id, 'pages', $labels, $capability);
	}
	
	/**
	 * Add admin page to Media menu
	 * @see add_media_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	public function add_media_page($id, $labels, $callback = null, $capability = null) {
		return $this->add_wp_page($id, 'media', $labels, $capability);
	}
	
	/**
	 * Add admin page to Themes menu
	 * @see add_theme_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	public function add_theme_page($id, $labels, $callback = null, $capability = null) {
		return $this->add_wp_page($id, 'theme', $labels, $capability);
	}
	
	/**
	 * Add admin page to Plugins menu
	 * @see add_plugins_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	public function add_plugins_page($id, $labels, $callback = null, $capability = null) {
		return $this->add_wp_page($id, 'plugins', $labels, $capability);
	}
	
	/**
	 * Add admin page to Options menu
	 * @see add_options_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	public function add_options_page($id, $labels, $callback = null, $capability = null) {
		return $this->add_wp_page($id, 'options', $labels, $capability);
	}
	
	/**
	 * Add admin page to Tools menu
	 * @see add_management_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	public function add_management_page($id, $labels, $callback = null, $capability = null) {
		return $this->add_wp_page($id, 'management', $labels, $capability);
	}
	
	/**
	 * Add admin page to Users menu
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	public function add_users_page($id, $labels, $callback = null, $capability = null) {
		return $this->add_wp_page($id, 'users', $labels, $capability);
	}
	
	/* Section */
	
	/**
	 * Add section
	 * @uses this->sections
	 * @param string $id Unique section ID
	 * @param string $page Page ID
	 * @param string $labels Label text
	 * @return obj Section instance
	 */
	public function add_section($id, $parent, $labels) {
		$args = func_get_args();
		$section = $this->add_view('section', $id, $args);
		
		// Add Section
		if ( $section->is_valid() )
			$this->sections[$id] = $section;
		
		return $section;
	}
	
	/* Operations */
	
	/**
	* Adds custom links below plugin on plugin listing page
	* @uses `plugin_action_links_$plugin-name` Filter hook
	* @param $actions
	* @param $plugin_file
	* @param $plugin_data
	* @param $context
	*/
	public function plugin_action_links($actions, $plugin_file, $plugin_data, $context) {
		global $admin_page_hooks;
		// Add link to settings (only if active)
		if ( is_plugin_active($this->util->get_plugin_base_name()) ) {
			/* Get Actions */
			
			$acts = array();
			$type = 'plugin_action';
			
			/* Get view links */
			foreach ( array('menus', 'pages', 'sections') as $views ) {
				foreach ( $this->{$views} as $view ) {
					if ( !$view->has_label($type) )
						continue;
					$acts[] = (object) array (
						'id'	=> $views . '_' . $view->get_id(),
						'label'	=> $view->get_label($type),
						'uri'	=> $view->get_uri(),
						'attributes'	=> array()
					);
				}
			}
			
			/* Get action links */
			$type = 'title';
			foreach ( $this->actions as $a ) {
				if ( !$a->has_label($type) )
					continue;
				$id = 'action_' . $a->get_id();
				$acts[] = (object) array (
					'id'			=> $id,
					'label'			=> $a->get_label($type),
					'uri'			=> $a->get_uri(),
					'attributes'	=> $a->get_link_attr()
				);
			}
			unset($a);
			
			// Add links
			$links = array();
			foreach ( $acts as $act ) {
				$links[$act->id] = $this->util->build_html_link($act->uri, $act->label, $act->attributes);
			}
			
			// Add links
			$actions = array_merge($links, $actions);
		}
		return $actions;
	}

	/**
	 * Update plugin listings metadata
	 * @param array $plugin_meta Plugin metadata
	 * @param string $plugin_file Plugin file
	 * @param array $plugin_data Plugin Data
	 * @param string $status Plugin status
	 * @return array Updated plugin metadata
	 */
	public function plugin_row_meta($plugin_meta, $plugin_file, $plugin_data, $status) {
		$u = ( is_object($this->parent) && isset($this->parent->util) ) ? $this->parent->util : $this->util;
		$hook_base = 'admin_plugin_row_meta_';
		if ( $plugin_file == $u->get_plugin_base_name() ) {
			// Add metadata
			//  Support
			$l = $u->get_plugin_info('SupportURI');
			if ( !empty($l) ) {
				$t = __( $this->util->apply_filters($hook_base . 'support', 'Get Support'), 'simple-lightbox');
				$plugin_meta[] = $u->build_html_link($l, $t);
			}
		}
		return $plugin_meta;
	}
	
	/**
	* Adds additional message for plugin updates
	* @uses `in_plugin_update_message-$plugin-name` Action hook
	* @uses this->plugin_update_get_message()
	* @var array $plugin_data Current plugin data
	* @var object $r Update response data
	*/
	public function plugin_update_message($plugin_data, $r) {
		if ( !isset($r->new_version) )
			return false;
		if ( stripos($r->new_version, 'beta') !== false ) {
			$cls_notice = $this->add_prefix('notice');
			echo '<br />' . $this->plugin_update_get_message($r);
		}
	}
	
	/**
	* Modify update plugins response data if necessary
	* @uses `site_transient_update_plugins` Filter hook
	* @uses this->plugin_update_get_message()
	* @param obj $transient Transient data
	* @return obj Modified transient data
	*/
	public function plugin_update_transient($transient) {
		$n = $this->util->get_plugin_base_name();
		if ( isset($transient->response) && isset($transient->response[$n]) && is_object($transient->response[$n]) && !isset($transient->response[$n]->upgrade_notice) ) {
			$r =& $transient->response[$n];
			$r->upgrade_notice = $this->plugin_update_get_message($r);
		}
		return $transient;
	}
	
	/**
	* Retrieve custom update message
	* @uses this->get_message()
	* @param obj $r Response data from plugin update API
	* @return string Message (Default: empty string)
	*/
	protected function plugin_update_get_message($r) {
		$msg = '';
		$cls_notice = $this->add_prefix('notice');
		if ( !is_object($r) || !isset($r->new_version) )
			return $msg;
		if ( stripos($r->new_version, 'beta') !== false ) {
			$msg = sprintf($this->get_message('beta'), $cls_notice);
		}
		return $msg;
	}
	
	/*-** Messages **-*/
	
	/**
	* Retrieve stored messages
	* @param string $msg_id Message ID
	* @return string Message text
	*/
	public function get_message($msg_id) {
		$msg = '';
		$msgs = $this->get_messages();
		if ( is_string($msg_id) && isset($msgs[$msg_id]) ) {
			$msg = $msgs[$msg_id];
		}
		return $msg;
	}
	
	/**
	 * Retrieve all messages
	 * Initializes messages if necessary
	 * @uses $messages
	 * @return array Messages
	 */
	function get_messages() {
		if ( empty($this->messages) ) {
			// Initialize messages if necessary
			$this->messages = array(
				'reset'			=> __('The settings have been reset', 'simple-lightbox'),
				'beta'			=> __('<strong class="%1$s">Notice:</strong> This update is a <strong class="%1$s">Beta version</strong>. It is highly recommended that you test the update on a test server before updating the plugin on a production server.', 'simple-lightbox'),
				'access_denied'	=> __('Access Denied', 'simple-lightbox'),
			);
		}
		return $this->messages;
	}
	
	/**
	* Set message text
	* @uses this->messages
	* @param string $id Message ID
	* @param string $text Message text
	*/
	public function set_message($id, $text) {
		$this->messages[trim($id)] = $text;
	}
}