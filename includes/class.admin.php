<?php

require_once 'class.base.php';

/**
 * Admin functionality
 * @package Simple Lightbox
 * @subpackage Admin
 * @author Archetyped
 */
class SLB_Admin extends SLB_Base {
	/* Properties */
	
	/**
	 * Parent object
	 * Set on initialization
	 * @var obj
	 */
	var $parent = null;
	
	/**
	 * Messages
	 * @var array
	 */
	var $messages = array(
		'reset'			=> 'The settings have been reset',
		'beta'			=> '<strong class="%1$s">Notice:</strong> This update is a <strong class="%1$s">Beta version</strong>. It is highly recommended that you test the update on a test server before updating the plugin on a production server.',
		'access_denied'	=> 'You do not have sufficient permissions'
	);
	
	/**
	 * Custom admin top-level menus
	 * Associative Array
	 *  > Key: Menu ID
	 *  > Val: Menu properties
	 * @var array
	 */
	var $menus = array();
	
	/**
	 * Custom admin pages
	 * Associative Array
	 *  > Key: Page ID
	 *  > Val: Page properties
	 * @var array
	 */
	var $pages = array();
	
	/**
	 * Custom admin sections
	 * Associative Array
	 *  > Key: Section ID
	 *  > Val: Section properties 
	 * @var array
	 */
	var $sections = array();
	
	/**
	 * Default menu capability
	 * @var string
	 */
	var $menu_cap_default = 'manage_options';
	
	/**
	 * Section delimeter
	 * @var string
	 */
	var $delim_section = '_sec_';
	
	var $styles = array(
		'admin'		=> array (
			'file'		=> 'css/admin.css',
			'context'	=> array('admin')
		)
	);
	
	/* Constants */
	
	const TYPE_PAGE = 'page';
	const TYPE_MENU = 'menu';
	const TYPE_SECTION = 'section';
	
	/* Constructor */
	
	function __construct(&$parent) {
		parent::__construct();
		//Set parent
		if ( is_object($parent) )
			$this->parent =& $parent;
	}
	
	/* Init */
	
	function register_hooks() {
		parent::register_hooks();
		//Init
		add_action('admin_menu', $this->m('init_menus'), 11);
		
		//Reset Settings
		add_action('admin_action_' . $this->add_prefix('reset'), $this->m('reset_settings'));
		
		//Notices
		add_action('admin_notices', $this->m('show_notices'));
		
		//Plugin listing
		add_filter('plugin_action_links_' . $this->util->get_plugin_base_name(), $this->m('plugin_action_links'), 10, 4);
		add_action('in_plugin_update_message-' . $this->util->get_plugin_base_name(), $this->m('plugin_update_message'), 10, 2);
		add_filter('site_transient_update_plugins', $this->m('plugin_update_transient'));
	}
	
	function test_globals() {
		global $compat;
		global $parent_file;
		global $menu;
		global $submenu;
		global $pagenow;
		global $typenow;
		global $plugin_page;
		global $_wp_real_parent_file;
		global $_wp_menu_nopriv;
		global $_wp_submenu_nopriv;
		global $dbg;
		global $admin_page_hooks;
	}
	
	/* Parsers */
	
	/**
	 * Retrieve properties for admin menu, page, or section
	 * @uses current_filter() to determine current item being processed
	 * @uses this->menus
	 * @uses this->pages
	 * @uses this->sections
	 * 
	 * @param string $type (optional) Item type to retrieve (Singular form of collection)
	 * @param string $hook (optional) Hook to process (Default: current_filter())
	 * @return object Item properties (Default: NULL)
	 */
	function &parse_item($type = 'page', $hook = null) {
		global $dbg;
		$ret = null;
		//Determine page being displayed
		if ( !is_string($hook) || empty($hook) )
			$hook = current_filter();
		$pos = strpos($hook, $this->get_prefix());
		$o = ( $pos !== false ) ? substr($hook, $pos) : $hook;
		$o = $this->remove_prefix($o);
		if ( !empty($o) ) {
			$types = array('page' => 'pages', 'menu' => 'menus', 'section' => 'sections');
			if ( isset($this->{$types[$type]}[$o]) )
				$o =& $this->{$types[$type]}[$o];
			if ( is_object($o) )
				$ret =& $o;
		}
		
		return $ret;
	}
	
	/**
	 * Retrieve menu properties
	 * @see this->parse_item()
	 * @uses this->parse_item()
	 * @param string $hook (optional) Hook to process
	 * @return object menu properties
	 */
	function &parse_menu($hook = null) {
		return $this->parse_item('menu', $hook);
	}
	
	/**
	 * Retrieve page properties
	 * @see this->parse_item()
	 * @uses this->parse_item()
	 * @param string $hook (optional) Hook to process
	 * @return object Page properties
	 */
	function &parse_page($hook = null) {
		return $this->parse_item('page', $hook);
	}
	
	function &parse_section($hook = null) {
		return $this->parse_item('section', $hook);
	}
	
	/* Handlers */
	
	function handle_section() {
		
	}

	/**
	 * Output admin menu
	 * TODO: Build out functionality
	 */
	function handle_menu() {
		global $dbg;
		$dbg->print_message('Menu', $this->parse_menu());
	}
	
	function get_title($obj, $type = '', $safe = true) {
		$ret = '';
		$safe = !!$safe;
		if ( isset($obj->title) ) {
			$t = $obj->title;
			if ( is_array($t) && !empty($t) ) {
				if ( isset($t[$type]) ) {
					$t = $t[$type];
				} elseif ( $safe ) {
					//Use first element in array as fallback
					$t = array_shift($t);
				}
			} elseif ( $safe && is_string($obj->title) ) {
				//Use string value as fallback
				$t = $obj->title;
			}
			
			if ( is_string($t) )
				$ret = $t;
		}
		
		return $ret;
	}
	
	/*-** START: Refactor **-*/
	
	/**
	 * Adds settings section for plugin functionality
	 * Section is added to specified admin section/menu
	 * @uses `admin_init` hook
	 */
	function init_menus() {
		global $dbg;
		//Add top level menus (when necessary)
		/**
		 * @var SLB_Admin_Menu
		 */
		$menu;
		foreach ( $this->menus as $menu ) {
			//Register menu
			$hook = add_menu_page($menu->get_label('title'), $menu->get_label('menu'), $menu->get_capability(), $menu->get_id(), $menu->get_callback());
			//Add hook to menu object
			$menu->set_hookname($hook);
			$this->menus[$menu->get_id_raw()] =& $menu;
		}
		
		/**
		 * @var SLB_Admin_Page
		 */
		$page;
		//Add subpages
		foreach ( $this->pages as $page ) {
			//Build Arguments
			$args = array ( $page->get_label('header'), $page->get_label('menu'), $page->get_capability(), $page->get_id(), $page->get_callback() );
			$f = null;
			//Handle pages for default WP menus
			if ( $page->is_parent_wp() ) {
				$f = 'add_' . $page->get_parent() . '_page';
			}
			
			//Handle pages for custom menus
			if ( ! function_exists($f) ) {
				array_unshift( $args, $page->get_parent() );
				$f = 'add_submenu_page';
			}
			
			//Add admin page
			$hook = call_user_func_array($f, $args);
			//Save hook to page properties
			$page->set_hookname($hook);
			$this->pages[$page->get_id_raw()] =& $page;
		}
		
		//Add sections
		/**
		 * @var SLB_Admin_Section
		 */
		$section;
		foreach ( $this->sections as $section ) {
			add_settings_section($section->get_id(), $section->get_title(), $section->get_callback(), $section->get_parent());
			if ( $section->is_options_valid() )
				register_setting($section->get_parent(), $section->get_id(), $section->get_options()->m('validate'));
		}
 	}


	/**
	 * Checks if parent menu of specified page is a default WP page
	 * @param obj $page Custom Page to check
	 * @return bool TRUE if parent is default WP page, FALSE otherwise
	 */
	function parent_is_default($page) {
		$ret = false;
		$wrapper = array('[', ']');
		if ( is_object($page) && isset($page->parent) )
			$ret = $this->util->has_wrapper($page->parent, $wrapper);
		return $ret;
	}
	
	function get_parent_wrapper() {
		static $wrapper = null;
		if ( empty($wrapper) )
			$wrapper = array('[', ']');
		return $wrapper;
	}

	/* Methods */
	
	/*-** Menus **-*/
	
	/**
	 * Adds custom admin panel
	 * @param string $id Menu ID
	 * @param string|array $labels Text labels
	 * @param int $pos (optional) Menu position in navigation (index order)
	 * @return string Menu ID
	 */
	function add_menu($id, $labels, $position = null) {
		//Create menu instance
		$menu =& new SLB_Admin_Menu($id, $labels, null, null, null, $position);
		//Add to collection
		if ( $menu->is_valid() )
			$this->menus[$id] =& $menu;
		else
			$id = false;
		
		return $id;
	}
	
	/* Page */
	
	/**
	 * Add admin page
	 * @uses this->pages
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * 	> menu: Menu title
	 * 	> header: Page header
	 * @param string $menu Menu ID to add page to
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	function add_page($id, $parent, $labels, $options = null, $callback = null, $capability = null) {
		//Init
		$page =& new SLB_Admin_Page($id, $parent, $labels, $options, $callback, $capability);
		global $dbg;
		//Add to collection
		if ( $page->is_valid() )
			$this->pages[$id] =& $page;
		else
			$id = false;
		return $id;
	}
	
	/* WP Pages */
	
	/**
	 * Add admin page to a standard WP menu
	 * @uses this->add_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * 	> menu: Menu title
	 * 	> header: Page header
	 * @param string $menu Name of WP menu to add page to
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	function add_wp_page($id, $parent, $labels, $options = null, $callback = null, $capability = null) {
		//Add page
		$pid = $this->add_page($id, $parent, $labels, $options, $callback, $capability);
		//Set parent as WP
		global $dbg;
		if ( $pid )
			$this->pages[$pid]->set_parent_wp();
		return $pid;
	}
	
	/**
	 * Add admin page to Dashboard menu
	 * @see add_dashboard_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	function add_dashboard_page($id, $labels, $options = null, $callback = null, $capability = null) {
		$id = $this->add_wp_page($id, 'dashboard', $labels, $options, $callback, $capability);
		return $id;
	}

	/**
	 * Add admin page to Comments menu
	 * @see add_comments_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	function add_comments_page($id, $labels, $options = null, $callback = null, $capability = null) {
		$id = $this->add_wp_page($id, 'comments', $labels, $options, $callback, $capability);
		return $id;
	}
	
	/**
	 * Add admin page to Links menu
	 * @see add_links_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	function add_links_page($id, $labels, $options = null, $callback = null, $capability = null) {
		$id = $this->add_wp_page($id, 'links', $labels, $options, $callback, $capability);
		return $id;
	}

	
	/**
	 * Add admin page to Posts menu
	 * @see add_posts_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	function add_posts_page($id, $labels, $options = null, $callback = null, $capability = null) {
		$id = $this->add_wp_page($id, 'posts', $labels, $options, $callback, $capability);
		return $id;
	}
	
	/**
	 * Add admin page to Pages menu
	 * @see add_pages_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	function add_pages_page($id, $labels, $options = null, $callback = null, $capability = null) {
		$id = $this->add_wp_page($id, 'pages', $labels, $options, $callback, $capability);
		return $id;
	}
	
	/**
	 * Add admin page to Media menu
	 * @see add_media_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	function add_media_page($id, $labels, $options = null, $callback = null, $capability = null) {
		$id = $this->add_wp_page($id, 'media', $labels, $options, $callback, $capability);
		return $id;
	}
	
	/**
	 * Add admin page to Themes menu
	 * @see add_theme_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	function add_theme_page($id, $labels, $options = null, $callback = null, $capability = null) {
		$id = $this->add_wp_page($id, 'theme', $labels, $options, $callback, $capability);
		return $id;
	}
	
	/**
	 * Add admin page to Plugins menu
	 * @see add_plugins_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	function add_plugins_page($id, $labels, $options = null, $callback = null, $capability = null) {
		$id = $this->add_wp_page($id, 'plugins', $labels, $options, $callback, $capability);
		return $id;
	}
	
	/**
	 * Add admin page to Options menu
	 * @see add_options_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	function add_options_page($id, $labels, $options = null, $callback = null, $capability = null) {
		$id = $this->add_wp_page($id, 'options', $labels, $options, $callback, $capability);
		return $id;
	}
	
	/**
	 * Add admin page to Tools menu
	 * @see add_management_page()
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	function add_management_page($id, $labels, $options = null, $callback = null, $capability = null) {
		$id = $this->add_wp_page($id, 'management', $labels, $options, $callback, $capability);
		return $id;
	}
	
	/**
	 * Add admin page to Users menu
	 * @uses this->add_wp_page()
	 * @param string $id Page ID (unique)
	 * @param string|array $labels Text labels (Associative array for multiple labels)
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param string $capability (optional) Custom capability for accessing page
	 * @return string Page ID
	 */
	function add_users_page($id, $labels, $options = null, $callback = null, $capability = null) {
		$id = $this->add_wp_page($id, 'users', $labels, $options, $callback, $capability);
		return $id;
	}
	
	/* Section */
	
	/**
	 * Add section
	 * @uses this->sections
	 * @param string $id Unique section ID
	 * @param string $page Page ID
	 * @param string $labels Label text
	 * @param obj|array $options Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom building
	 * @return string Section ID
	 */
	function add_section($id, $parent, $labels, $options = null, $callback = null) {
		$section =& new SLB_Admin_Section($id, $parent, $labels, $options, $callback);
		
		//Add Section
		if ( $section->is_valid() )
			$this->sections[$id] =& $section;
		else
			$id = false;
		return $id;
	}
	
	function get_section_title($id) {
		$ret = '';
		$s =& $this->parse_section($id);
		if ( isset($s->title) && !empty($s->title) )
			$ret = sprintf('<div id="%s">%s</div>', $s->id, esc_html($s->title));
		return $ret;
	}
	
	/* Operations */
	
	/**
	 * Reset plugin settings
	 * Redirects to referring page upon completion
	 */
	function reset_settings() {
		//Validate user
		if ( ! current_user_can('activate_plugins') || ! check_admin_referer($this->add_prefix('reset')) )
			wp_die(__($this->get_message('access_denied'), $this->util->get_plugin_textdomain()));
		//Check action
		$action = 'reset';
		if ( isset($_REQUEST['action']) && $this->add_prefix($action) == $_REQUEST['action'] ) {
			//Reset settings
			if ( isset($this->parent->options) && $this->util->is_a($this->parent->options, 'Options') )
				$this->parent->options->reset(true);
			//Redirect user
			$uri = remove_query_arg(array('_wpnonce', 'action'), add_query_arg(array($this->add_prefix('action') => $action), $_SERVER['REQUEST_URI']));
			wp_redirect($uri);
			exit;
		}
	}
	
	/**
	 * Displays notices for admin operations
	 */
	function show_notices() {
		if ( is_admin() && isset($_REQUEST[$this->add_prefix('action')]) ) {
			$action = $_REQUEST[$this->add_prefix('action')];
			$msg = null;
			if ( $action ) {
				$msg = $this->get_message($action);
				if ( ! empty($msg) ) {
					?>
					<div id="message" class="updated fade">
						<p>
							<?php echo $msg;?>
						</p>
					</div>
					<?php
				}
			}
		}
	}
	
	/**
	* Adds custom links below plugin on plugin listing page
	* @uses `plugin_action_links_$plugin-name` Filter hook
	* @param $actions
	* @param $plugin_file
	* @param $plugin_data
	* @param $context
	*/
	function plugin_action_links($actions, $plugin_file, $plugin_data, $context) {
		global $dbg;
		global $admin_page_hooks;
		//Add link to settings (only if active)
		if ( is_plugin_active($this->util->get_plugin_base_name()) ) {
			/* Get Actions */
			
			$acts = array();
			$type = 'plugin_action';
			
			/* Get view links */
			foreach ( array('menus', 'pages', 'sections') as $views ) {
				foreach ( $this->{$views} as $view ) {
					$label = $view->get_label($type);
					if ( empty($label) )
						continue;
					$acts[] = (object) array(
						'id'	=> $views . '_' . $view->get_id(),
						'label'	=> $label,
						'uri'	=> $view->get_uri()
					);
				}
			}
			
			//Add links
			$links = array();
			foreach ( $acts as $act ) {
				$links[$act->id] = $this->util->build_html_link($act->uri, $act->label);
			}
			
			//Reset
			/*
			$reset = __('Reset', $this->util->get_plugin_textdomain());
			$reset_confirm = "'" . __('Are you sure you want to reset your settings?', $this->util->get_plugin_textdomain()) . "'";
			$action = $this->add_prefix('reset');
			$reset_uri = wp_nonce_url(add_query_arg('action', $action, remove_query_arg(array($this->add_prefix('action'), 'action'), $_SERVER['REQUEST_URI'])), $action);
			$reset_link = $this->util->build_html_link($reset_uri, $reset, array('id' => $this->add_prefix('reset'), 'onclick' => 'return confirm(' . $reset_confirm . ')'));
			*/
			
			//Add links
			$actions = array_merge($links, $actions);
			//array_splice($actions, 1, 0, $reset_link);
		}
		return $actions;
	}
	
	/*-** END: Refactor **-*/
	
	/**
	* Adds additional message for plugin updates
	* @uses `in_plugin_update_message-$plugin-name` Action hook
	* @uses this->plugin_update_get_message()
	* @var array $plugin_data Current plugin data
	* @var object $r Update response data
	*/
	function plugin_update_message($plugin_data, $r) {
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
	function plugin_update_transient($transient) {
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
	function plugin_update_get_message($r) {
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
	* @uses this->messages
	* @param string $msg_id Message ID
	* @return string Message text
	*/
	function get_message($msg_id) {
		$msg = '';
		if ( is_string($msg_id) && !empty($msg_id) && isset($this->messages[$msg_id]) )
			$msg = $this->messages[$msg_id];
		return $msg;
	}
	
	/**
	* Set message text
	* @uses this->messages
	* @param string $id Message ID
	* @param string $text Message text
	*/
	function set_message($id, $text) {
		$this->messages[trim($id)] = $text;
	}

}

/**
 * Admin View Base functionality
 * Core functionality for Menus/Pages/Sections 
 * @package Simple Lightbox
 * @subpackage Admin
 * @author Archetyped
 */
class SLB_Admin_View extends SLB_Base {
	/* Properties */
	
	/**
	 * Unique ID
	 * @var string
	 */
	var $id = null;
	
	/**
	 * Labels
	 * @var array (Associative)
	 */
	var $labels = array();
	
	/**
	 * Options object to use
	 * @var SLB_Options
	 */
	var $options = null;
	
	/**
	 * Option groups to use
	 * If empty, use entire options object
	 * @var array
	 */
	var $option_groups = array();
	
	/**
	 * Function to handle building UI
	 * @var callback
	 */
	var $callback = null;
	
	/**
	 * Capability for access control
	 * @var string 
	 */
	var $capability = 'manage_options';
	
	/**
	 * Icon to use
	 * @var string
	 */
	var $icon = null;
	
	/**
	 * View parent ID/Slug
	 * @var string
	 */
	var $parent = null;
	
	/**
	 * Whether parent is a custom view or a default WP one
	 * @var bool
	 */
	var $parent_custom = true;
		
	/**
	 * If view requires a parent
	 * @var bool
	 */
	var $parent_required = false;
	
	/**
	 * WP-Generated hook name for view
	 * @var string
	 */
	var $hookname = null;
	
	/**
	 * Required properties
	 * Associative array
	 * > Key: Property name
	 * > Value: Required data type
	 * @var array
	 */
	protected $required = array();
	
	/**
	 * Default required properties
	 * Merged into $required array with this->init_required()
	 * @see this->required for more information
	 * @var array
	 */
	protected $_required = array ( 'id' => 'string', 'labels' => 'array' );
	
	/* Init */
	
	function __construct($id, $labels, $options = null, $callback = null, $capability = null, $icon = null) {
		parent::__construct();
		
		$this->init_required();
		$this->set_id($id);
		$this->set_labels($labels);
		$this->set_options($options);
		$this->set_callback($callback);
		$this->set_capability($capability);
		$this->set_icon($icon);
	}
	
	function init_required() {
		$this->required = array_merge($this->_required, $this->required);
	}
	
	/* Getters/Setters */
	
	/**
	 * Retrieve ID (Formatted by default)
	 * @param bool $formatted (optional) Whether ID should be formatted for external use or not
	 * @return string ID
	 */
	function get_id($formatted = true) {
		$id = $this->id;
		if ( $formatted )
			$this->add_prefix_ref($id);
		return $id;
	}
	
	/**
	 * Retrieve raw ID
	 * @return string Raw ID
	 */
	function get_id_raw() {
		return $this->get_id(false);
	}
	
	/**
	 * Set ID
	 * @param string ID
	 */
	function set_id($id) {
		if ( is_scalar($id) ) {
			$this->id = trim(strval($id));
			return true;
		}
		return false;
	}
	
	/**
	 * Retrieve label
	 * Uses first label (or default if defined) if specified type does not exist
	 * @param string $type Label type to retrieve
	 * @param string $default (optional) Default value if label type does not exist
	 * @return string Label text
	 */
	function get_label($type, $default = null) {
		//Retrieve existing label type
		if ( isset($this->labels[$type]) )
			return $this->labels[$type];
		//Use default label if type is not set
		if ( empty($default) && !empty($this->labels) ) {
			reset($this->labels);
			$default = current($this->labels);
		}
		
		return ( empty($default) ) ? '' : $default;
	}
	
	/**
	 * Set text labels
	 * @param array|string $labels
	 */
	function set_labels($labels) {
		if ( empty($labels) )
			return false;
		//Single string
		if ( is_string($labels) ) {
			$labels = array ( $labels );
		} 
		
		//Array
		if ( is_array($labels) ) {
			//Merge with existing labels
			if ( empty($this->labels) || !is_array($this->labels) ) {
				$this->labels = array();
			}
			$this->labels = array_merge($this->labels, $labels);
		}
	}
	
	/**
	 * Set single text label
	 * @uses this->set_labels()
	 * @param string $type Label type to set
	 * @param string $value Label value
	 */
	function set_label($type, $value) {
		if ( is_string($type) && is_string($value) ) {
			$label = array( $type => $value );
			$this->set_labels($label);
		}
	}
	
	/**
	 * Retrieve instance options
	 * @return SLB_Options Options instance
	 */
	function &get_options() {
		return $this->options;
	}
	
	/**
	 * Set options object
	 * @param obj|array $options Options instance
	 *  > If array, Options instance and specific groups are specified
	 *   > 0: Options instance
	 * 	 > 1: Group(s)
	 */
	function set_options($options) {
		if ( empty($options) )
			return false;
		
		$groups = null;
		
		if ( is_array($options) ) {
			$options = array_values($options);
			//Set option groups
			if ( isset($options[1]) ) {
				$groups = $options[1];
			}
			//Set options object
			$options =& $options[0];
		}
		
		if ( $this->util->is_a($options, 'Options') ) {
			//Save options
			$this->options =& $options;
			
			//Save option groups for valid options
			$this->set_option_groups($groups);
		}
	}
	
	/**
	 * Set option groups
	 * @param string|array $groups Specified group(s)
	 */
	function set_option_groups($groups) {
		if ( empty($groups) )
			return false;
		
		//Validate data
		if ( !is_array($groups) ) {
			if ( is_scalar($groups) ) {
				$groups = array(strval($groups));
			}
		}
		
		if ( is_array($groups) ) {
			$this->option_groups = $groups;
		}
	}
	
	/**
	 * Retrieve view callback
	 * @return callback Callback
	 */
	function get_callback() {
		return ( empty($this->callback) ) ? $this->m('handle') : $this->callback;
	}
	
	/**
	 * Set callback function for building item
	 * @param callback $callback Callback function to use
	 */
	function set_callback($callback) {
		if ( is_callable($callback) )
			$this->callback = $callback;
	}
	
	/**
	 * Retrieve capability
	 * @return string Capability
	 */
	function get_capability() {
		return $this->capability;
	}
	
	/**
	 * Set capability for access control
	 * @param string $capability Capability
	 */
	function set_capability($capability) {
		if ( is_string($capability) && !empty($capability) )
			$this->capability = $capability;
	}
	
	/**
	 * Set icon
	 * @param string $icon Icon URI
	 */
	function set_icon($icon) {
		if ( !empty($icon) && is_string($icon) )
			$this->icon = $icon;
	}
	
	function get_hookname() {
		return ( empty($this->hookname) ) ? '' : $this->hookname;
	}
	
	/**
	 * Set hookname
	 * @param string $hookname Hookname value
	 */
	function set_hookname($hookname) {
		if ( !empty($hookname) && is_string($hookname) )
			$this->hookname = $hookname;
	}
	
	/**
	 * Retrieve parent
	 * Formats parent ID for custom parents
	 * @return string Parent ID
	 */
	function get_parent() {
		$parent = $this->parent;
		return ( $this->is_parent_custom() ) ? add_prefix($parent) : $parent;
	}
	
	/**
	 * Set parent for view
	 * @param string $parent Parent ID
	 */
	function set_parent($parent) {
		global $dbg;
		if ( $this->parent_required ) {
			if ( !empty($parent) && is_string($parent) )
			$this->parent = $parent;
		} else {
			$this->parent = null;
		}
	}
		
	/**
	 * Specify whether parent is a custom view or a WP view
	 * @param bool $custom (optional) TRUE if custom, FALSE if WP
	 */
	function set_parent_custom($custom = true) {
		if ( $this->parent_required ) {
			$this->parent_custom = !!$custom;
		}
	}
	
	/**
	 * Set parent as WP view
	 * @uses this->set_parent_custom()
	 */
	function set_parent_wp() {
		$this->set_parent_custom(false);
	}
	
	/**
	 * Get view URI
	 * URI Structures:
	 *  > Top Level Menus: admin.php?page={menu_id}
	 *  > Pages: [parent_page_file.php|admin.php]?page={page_id}
	 * 	> Section: [parent_menu_uri]#{section_id}
	 * 
	 * @uses $admin_page_hooks to determine if page is child of default WP page
	 * @return string Object URI 
	 */
	function get_uri($file = null, $format = null) {
		global $dbg;
		static $page_hooks = null;
		$uri = '';
		if ( empty($file) )
			$file = 'admin.php';
		if ( $this->is_child() ) {
			$parent = str_replace('_page_' . $this->get_id(), '', $this->get_hookname());
			if ( is_null($page_hooks) ) {
				$page_hooks = array_flip($GLOBALS['admin_page_hooks']);
			}
			if ( isset($page_hooks[$parent]) )
				$file = $page_hooks[$parent];
		}
		
		if ( empty($format) )
			$format = '%1$s?page=%2$s';
		$uri = sprintf($format, $file, $this->get_id());

		return $uri;
	}
	
	/* Handlers */
	
	/**
	 * Default View handler
	 * Used as callback when none set
	 */
	function handle() {
		global $dbg;
		$dbg->print_message('Default view handler');
		
	}
	
	/* Validation */
	
	/**
	 * Check if instance is valid based on required properties/data types
	 * @return bool TRUE if valid, FALSE if not valid 
	 */
	function is_valid() {
		$valid = true;
		global $dbg;
		foreach ( $this->required as $prop => $type ) {
			if ( empty($this->{$prop} )
				|| ( !empty($type) && is_string($type) && ( $f = 'is_' . $type ) && function_exists($f) && !$f($this->{$prop}) ) ) {
				$valid = false;
				break;
			}
		}
		return $valid;
	}
	
	function has_callback() {
		return ( !empty($this->callback) ) ? true : false;
	}
	
	function do_callback() {
		if ( $this->has_callback() ) {
			call_user_func($this->get_callback(), $this);
		}
	}
		
	function is_child() {
		return $this->parent_required;
	}
	
	function is_parent_custom() {
		return ( $this->is_child() && $this->parent_custom ) ? true : false;
	}
	
	function is_parent_wp() {
		return ( $this->is_child() && !$this->parent_custom ) ? true : false;
	}
	
	function is_options_valid() {
		return ( is_object($this->get_options()) && $this->util->is_a($this->get_options(), $this->get_options_class()) ) ? true : false;
	}
	
	/* UI Elements */
	
	function show_options($show_submit = true) {
		//Build options form
		if ( $this->is_options_valid() ) {
			if ( $show_submit ) {
				$submit = $this->get_button_submit();
				//Handle form submission
				//TODO: Move submission processing to options class
				if ( isset($_REQUEST[$submit->id]) ) {
					//Validate
					$values = $this->get_options()->validate();
					//Set option data
					
					$this->get_options()->set_data($values);
				}
			
				//Build form output
				$form_id = $this->add_prefix('admin_form_' . $this->get_id_raw());
				?>
				<form id="<?php esc_attr_e($form_id); ?>" name="<?php esc_attr_e($form_id); ?>" action="" method="post">
			<?php 
			}
			//Build options output
			$this->get_options()->build();
			if ( $show_submit ) {
				echo $submit->output;
				?>
				</form>
				<?php
			}
		}
	}
	
	/**
	 * Build submit button element
	 * @param string $text (optional) Button text
	 * @param string $id (optional) Button ID (prefixed on output)
	 * @param object $parent (optional) Page/Section object that contains button
	 * @return object Button properties (id, output)
	 */
	function get_button_submit($text = null, $id = null, $parent = null) {
		//Format values
		if ( !is_string($text) || empty($text) )
			$text = __('Save Changes');
		if ( is_object($parent) && isset($parent->id) )
			$parent = $parent->id . '_';
		else
			$parent = '';
		if ( !is_string($id) || empty($id) )
			$id = 'submit';
		$id = $this->add_prefix($parent . $id);
		//Build HTML
		$out = $this->util->build_html_element(array(
			'tag'			=> 'input',
			'wrap'			=> false,
			'attributes'	=> array(
				'type'	=> 'submit',
				'class'	=> 'button-primary',
				'id'	=> $id,
				'name'	=> $id,
				'value'	=> $text
			)
		));
		$out = '<p class="submit">' . $out . '</p>';
		$ret = new stdClass;
		$ret->id = $id;
		$ret->output = $out;
		return $ret;
	}
	
	/**
	 * Output submit button element
	 * @param string $text (optional) Button text
	 * @param string $id (optional) Button ID (prefixed on output)
	 * @param object $parent (optional) Page/Section object that contains button
	 * @return object Button properties (id, output)
	 */
	function button_submit($text = null, $id = null, $parent = null) {
		$btn = $this->get_button_submit($text, $id, $parent);
		echo $btn->output;
		return $btn;
	}
}

/**
 * Admin Menu functionality
 * @package Simple Lightbox
 * @subpackage Admin
 * @author Archetyped
 */
class SLB_Admin_Menu extends SLB_Admin_View {
	/* Properties */
	
	/**
	 * Menu position
	 * @var int
	 */
	var $position = null;
	
	/* Init */
	
	function __construct($id, $labels, $options = null, $callback = null, $capability = null, $icon = null, $position = null) {
		//Default
		parent::__construct($id, $labels, $options, $callback, $capability, $icon);
		//Class specific
		$this->set_position($position);
	}
	
	/* Getters/Setters */
	
	function set_position($position) {
		if ( is_int($position) )
			$this->position = $position;
	}
	
	/* Handlers */
	
	function handle() {
		global $dbg;
		$dbg->print_message('Menu Handler');
	}
}

/**
 * Admin Page functionality
 * @package Simple Lightbox
 * @subpackage Admin
 * @author Archetyped
 */
class SLB_Admin_Page extends SLB_Admin_View {
	/* Properties */
	
	protected $required = array ( 'parent' => 'string' );
	
	var $parent_required = true;
	
	/* Init */
	
	function __construct($id, $parent, $labels, $options = null, $callback = null, $capability = null, $icon = null) {
		//Default
		parent::__construct($id, $labels, $options, $callback, $capability, $icon);
		//Class specific
		$this->set_parent($parent);
	}
	
	/* Operations */
	
	function show_icon() {
		echo screen_icon();
	}
	
	/* Handlers */
	
	/**
	 * Default Page handler
	 * Builds options form UI for page
	 * @see this->init_menus() Set as callback for custom admin pages
	 * @uses current_user_can() to check if user has access to current page
	 * @uses wp_die() to end execution when user does not have permission to access page
	 * @uses this->menu_cap_default Default capability for page access
	 * @uses this->parse_page
	 */
	function handle() {
		global $dbg;
		if ( !current_user_can($this->get_capability()) )
			wp_die('Access Denied');
		?>
		<div class="wrap">
			<?php $this->show_icon(); ?>
			<h2><?php esc_html_e( $this->get_label('header') ); ?></h2>
			<?php
			$this->show_options();
			?>
		</div>
		<?php
	}
}

/**
 * Admin Section functionality
 * @package Simple Lightbox
 * @subpackage Admin
 * @author Archetyped
 */
class SLB_Admin_Section extends SLB_Admin_View {
	/* Properties */
	
	protected $required = array ( 'parent' => 'string' );
	
	var $parent_required = true;
	var $parent_custom = false;
	
	/* Init */
	
	function __construct($id, $parent, $labels, $options = null, $callback = null, $capability = null) {
		//Default
		parent::__construct($id, $labels, $options, $callback, $capability);
		//Class specific
		$this->set_parent($parent);
	}
	
	/* Getters/Setters */
	
	function get_uri() {
		$file = 'options-' . $this->get_parent() . '.php';
		return parent::get_uri($file, '%1$s#%2$s');
	}

	/**
	 * Retrieve formatted title for section
	 * Wraps title text in element with anchor so that it can be linked to
	 * @return string Title
	 */
	function get_title() {
		return '<span id="' . $this->get_id() . '">' . $this->get_label('title') . '</span>';
	}
	
	/* Handlers */
	
	function handle() {
		$this->show_options(false);
	}
}