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

	/* Files */
	
	protected $scripts = array (
		'admin'	=> array (
			'file'		=> 'client/js/lib.admin.js',
			'deps'		=> array('[core]'),
			'context'	=> array( 'admin_page_slb_options' ),
			'in_footer'	=> true,
		),
	);
	
	protected $styles = array (
		'admin'	=> array (
			'file'		=> 'client/css/admin.css',
			'context'	=> array( 'admin_page_slb_options', 'admin_page_plugins' )
		)
	);

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
	 * Reset options
	 * Indexed Array
	 * @var array
	 */
	protected $resets = array();

	/* Constructor */
	
	public function __construct(&$parent) {
		parent::__construct();
		//Set parent
		if ( is_object($parent) )
			$this->parent =& $parent;
	}
	
	/* Init */
	
	protected function _hooks() {
		parent::_hooks();
		//Init
		add_action('admin_menu', $this->m('init_menus'), 11);
		
		//Reset Settings
		add_action('admin_action_' . $this->add_prefix('admin'), $this->m('handle_action'));
		
		//Notices
		add_action('admin_notices', $this->m('handle_notices'));
		
		//Plugin listing
		add_filter('plugin_action_links_' . $this->util->get_plugin_base_name(), $this->m('plugin_action_links'), 10, 4);
		add_action('in_plugin_update_message-' . $this->util->get_plugin_base_name(), $this->m('plugin_update_message'), 10, 2);
		add_filter('site_transient_update_plugins', $this->m('plugin_update_transient'));
	}
	
	/* Handlers */
	
	/**
	 * Handle routing of internal action to appropriate handler
	 */
	public function handle_action() {
		//Parse action
		$t = 'type';
		$g = 'group';
		$o = 'obj';
		$this->add_prefix_ref($t);
		$this->add_prefix_ref($g);
		$this->add_prefix_ref($o);
		$r =& $_REQUEST;
		
		//Retrieve view that initiated the action
		if ( isset($r[$t]) && 'view' == $r[$t] ) {
			if ( isset($r[$g]) && ( $prop = $r[$g] . 's' ) && property_exists($this, $prop) && is_array($this->{$prop}) && isset($r[$o]) && isset($this->{$prop}[$r[$o]]) ) {
				$view =& $this->{$prop}[$r[$o]];
				//Pass request to view
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
			//Filter out empty messages
			if ( empty($msg) )
				continue;
			//Build and display message
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
	
	/**
	 * Displays notices for admin operations
	 * @deprecated
	 */
	function show_notices() {
		if ( is_admin() && isset($_REQUEST[$this->add_prefix('action')]) ) {
			$action = $_REQUEST[$this->add_prefix('action')];
			$msg = null;
			if ( $action ) {
				$msg = $this->get_message($action);
				if ( ! empty($msg) ) {
					
				}
			}
		}
	}
	/* Views */
	
	/**
	 * Adds settings section for plugin functionality
	 * Section is added to specified admin section/menu
	 * @uses `admin_init` hook
	 */
	public function init_menus() {
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


	/* Methods */
	
	/**
	 * Add a new view
	 * @param string $type View type
	 * @param string $id Unique view ID
	 * @param array $args Arguments to pass to view constructor
	 * @return int|bool View ID (FALSE if view was not properly initialized)
	 */
	protected function add_view($type, $id, $args) {
		//Validate request
		$class = $this->add_prefix('admin_' . $type);
		$collection = $type . 's';
		if ( !class_exists($class) || !property_exists($this, $collection) || !is_array($this->{$collection}) )
			return false;
		//Create new instance
		$r = new ReflectionClass($class);
		$view =& $r->newInstanceArgs($args);
		if ( $view->is_valid() )
			$this->{$collection}[$id] =& $view;
		else 
			$id = false;
		unset($view, $r);
		return $id;
	}
	
	/**
	 * Add reset option to plugin action links
	 * @param string $id Unique ID
	 * @param array $labels Text for reset instance
	 * > title - Link text (also title attribute value)
	 * > confirm - Confirmation message
	 * > success - Success message
	 * > failure - Failure message
	 * @param SLB_Options|array $options Options instance (or instance + specific groups)
	 */
	public function add_reset($id, $labels, $options) {
		$args = func_get_args();
		return $this->add_view('reset', $id, $args);
	}
	
	/*-** Menus **-*/
	
	/**
	 * Adds custom admin panel
	 * @param string $id Menu ID
	 * @param string|array $labels Text labels
	 * @param int $pos (optional) Menu position in navigation (index order)
	 * @return string Menu ID
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
	public function add_page($id, $parent, $labels, $options = null, $callback = null, $capability = null) {
		$args = func_get_args();
		wp_enqueue_script('postbox');
		return $this->add_view('page', $id, $args);
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
	public function add_wp_page($id, $parent, $labels, $options = null, $callback = null, $capability = null) {
		//Add page
		$pid = $this->add_page($id, $parent, $labels, $options, $callback, $capability);
		//Set parent as WP
		if ( $pid ) {
			$this->pages[$pid]->set_parent_wp();
		}
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
	public function add_dashboard_page($id, $labels, $options = null, $callback = null, $capability = null) {
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
	public function add_comments_page($id, $labels, $options = null, $callback = null, $capability = null) {
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
	public function add_links_page($id, $labels, $options = null, $callback = null, $capability = null) {
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
	public function add_posts_page($id, $labels, $options = null, $callback = null, $capability = null) {
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
	public function add_pages_page($id, $labels, $options = null, $callback = null, $capability = null) {
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
	public function add_media_page($id, $labels, $options = null, $callback = null, $capability = null) {
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
	public function add_theme_page($id, $labels, $options = null, $callback = null, $capability = null) {
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
	public function add_plugins_page($id, $labels, $options = null, $callback = null, $capability = null) {
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
	public function add_options_page($id, $labels, $options = null, $callback = null, $capability = null) {
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
	public function add_management_page($id, $labels, $options = null, $callback = null, $capability = null) {
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
	public function add_users_page($id, $labels, $options = null, $callback = null, $capability = null) {
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
	public function add_section($id, $parent, $labels, $options = null, $callback = null) {
		$section = new SLB_Admin_Section($id, $parent, $labels, $options, $callback);
		
		//Add Section
		if ( $section->is_valid() )
			$this->sections[$id] =& $section;
		else
			$id = false;
		return $id;
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
		//Add link to settings (only if active)
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
			
			/* Get reset links */
			$type = 'title';
			foreach ( $this->resets as $reset )	 {
				if ( !$reset->has_label($type) )
					continue;
				$id = 'reset_' . $reset->get_id();
				$acts[] = (object) array (
					'id'	=> $id,
					'label'	=> $reset->get_label($type),
					'uri'	=> $reset->get_uri(),
					'attributes'	=> $reset->get_link_attr()
				);
			}
			
			//Add links
			$links = array();
			foreach ( $acts as $act ) {
				$links[$act->id] = $this->util->build_html_link($act->uri, $act->label, $act->attributes);
			}
			
			//Add links
			$actions = array_merge($links, $actions);
		}
		return $actions;
	}
	
	/*-** START: Refactor **-*/
	
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
			//Initialize messages if necessary
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
	/*-** END: Refactor **-*/

}

/**
 * Admin View Base functionality
 * Core functionality for Menus/Pages/Sections 
 * @package Simple Lightbox
 * @subpackage Admin
 * @author Archetyped
 */
class SLB_Admin_View extends SLB_Base_Object {
	/* Properties */
	
	/**
	 * Labels
	 * @var array (Associative)
	 */
	protected $labels = array();
	
	/**
	 * Options object to use
	 * @var SLB_Options
	 */
	protected $options = null;
	
	/**
	 * Option groups to use
	 * If empty, use entire options object
	 * @var array
	 */
	protected $option_groups = array();
	
	/**
	 * Option building arguments
	 * @var array
	 */
	protected $option_args = array();
	
	/**
	 * Function to handle building UI
	 * @var callback
	 */
	protected $callback = null;
	
	/**
	 * Capability for access control
	 * @var string 
	 */
	protected $capability = 'manage_options';
	
	/**
	 * Icon to use
	 * @var string
	 */
	protected $icon = null;
	
	/**
	 * View parent ID/Slug
	 * @var string
	 */
	protected $parent = null;
	
	/**
	 * Whether parent is a custom view or a default WP one
	 * @var bool
	 */
	protected $parent_custom = true;
		
	/**
	 * If view requires a parent
	 * @var bool
	 */
	protected $parent_required = false;
	
	/**
	 * WP-Generated hook name for view
	 * @var string
	 */
	protected $hookname = null;
	
	/**
	 * Messages to be displayed
	 * Indexed Array
	 * @var array
	 */
	protected $messages = array();
	
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
	private $_required = array ( 'id' => 'string', 'labels' => 'array' );
	
	/* Init */
	
	public function __construct($id, $labels, $options = null, $callback = null, $capability = null, $icon = null) {
		$props = array(
			'labels'		=> $labels,
			'options'		=> $options,
			'callback'		=> $callback,
			'capability'	=> $capability,
			'icon'			=> $icon,
		);
		parent::__construct($id, $props);
		$this->init_required();
	}
	
	protected function init_required() {
		$this->required = array_merge($this->_required, $this->required);
		//Check for parent requirement
		if ( $this->parent_required )
			$this->required['parent'] = 'string';
	}
	
	/* Property Methods */
	
	/**
	 * Retrieve ID (Formatted by default)
	 * @param bool $formatted (optional) Whether ID should be formatted for external use or not
	 * @return string ID
	 */
	public function get_id($formatted = true) {
		$id = parent::get_id();
		if ( $formatted )
			$this->add_prefix_ref($id);
		return $id;
	}
	
	/**
	 * Retrieve raw ID
	 * @return string Raw ID
	 */
	public function get_id_raw() {
		return $this->get_id(false);
	}
	
	/**
	 * Retrieve label
	 * Uses first label (or default if defined) if specified type does not exist
	 * @param string $type Label type to retrieve
	 * @param string $default (optional) Default value if label type does not exist
	 * @return string Label text
	 */
	public function get_label($type, $default = null) {
		//Retrieve existing label type
		if ( $this->has_label($type) )
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
	 * @return obj Current instance
	 */
	public function set_labels($labels) {
		if ( empty($labels) )
			return this;
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
		return $this;
	}
	
	/**
	 * Set single text label
	 * @uses this->set_labels()
	 * @param string $type Label type to set
	 * @param string $value Label value
	 * @return obj Current instance
	 */
	public function set_label($type, $value) {
		if ( is_string($type) && is_string($value) ) {
			$label = array( $type => $value );
			$this->set_labels($label);
		}
		return $this;
	}
	
	/**
	 * Checks if specified label is set on view
	 * @param string $type Label type
	 * @return bool TRUE if label exists, FALSE otherwise
	 */
	public function has_label($type) {
		return ( isset($this->labels[$type]) );
	}
	
	/**
	 * Retrieve instance options
	 * @return SLB_Options Options instance
	 */
	public function &get_options() {
		return $this->options;
	}
	
	/**
	 * Set options object
	 * @param obj|array $options Options instance
	 *  > If array, Options instance and specific groups are specified
	 *   > 0: Options instance
	 * 	 > 1: Group(s)
	 * @return obj Current instance
	 */
	public function set_options($options) {
		if ( empty($options) )
			return $this;
		
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
		return $this;
	}
	
	/**
	 * Set option groups
	 * @param string|array $groups Specified group(s)
	 * @return obj Current instance
	 */
	public function set_option_groups($groups) {
		if ( empty($groups) )
			return $this;
		
		//Validate data
		if ( !is_array($groups) ) {
			if ( is_scalar($groups) ) {
				$groups = array(strval($groups));
			}
		}
		
		if ( is_array($groups) ) {
			$this->option_groups = $groups;
		}
		return $this;
	}
	
	/**
	 * Retrieve view messages
	 * @return array Messages
	 */
	protected function &get_messages() {
		if ( !is_array($this->messages) )
			$this->messages = array();
		return $this->messages;
	}
	
	/**
	 * Save message
	 * @param string $text Message text
	 * @return obj Current instance
	 */
	public function set_message($text) {
		$msgs =& $this->get_messages();
		$text = trim($text);
		if ( empty($msgs) && !empty($text) )
			$this->util->add_filter('admin_messages', $this->m('do_messages'));
		$msgs[] = $text;
		return $this;
	}
	
	/**
	 * Add messages to array
	 * Called by internal `admin_messages` filter hook
	 * @param array $msgs Aggregated messages
	 * @return array Merged messages array
	 */
	public function do_messages($msgs = array()) {
		$m =& $this->get_messages();
		if ( !empty($m) )
			$msgs = array_merge($msgs, $m);
		return $msgs;
	}
	
	/**
	 * Retrieve view callback
	 * @return callback Callback (Default: standard handler method)
	 */
	public function get_callback() {
		return ( $this->has_callback() ) ? $this->callback : $this->m('handle');
	}
	
	/**
	 * Set callback function for building item
	 * @param callback $callback Callback function to use
	 * @return obj Current instance
	 */
	public function set_callback($callback) {
		$this->callback = ( is_callable($callback) ) ? $callback : null;
		return $this;
	}
	
	/**
	 * Check if callback set
	 * @return bool TRUE if callback is set
	 */
	protected function has_callback() {
		return ( !empty($this->callback) ) ? true : false;
	}
	
	/**
	 * Run callback
	 */
	public function do_callback() {
		call_user_func($this->get_callback());
	}
	
	/**
	 * Retrieve capability
	 * @return string Capability
	 */
	public function get_capability() {
		return $this->capability;
	}
	
	/**
	 * Set capability for access control
	 * @param string $capability Capability
	 * @return obj Current instance
	 */
	public function set_capability($capability) {
		if ( is_string($capability) && !empty($capability) )
			$this->capability = $capability;
		return $this;
	}
	
	/**
	 * Set icon
	 * @param string $icon Icon URI
	 * @return obj Current instance
	 */
	public function set_icon($icon) {
		if ( !empty($icon) && is_string($icon) )
			$this->icon = $icon;
		return $this;
	}
	
	protected function get_hookname() {
		return ( empty($this->hookname) ) ? '' : $this->hookname;
	}
	
	/**
	 * Set hookname
	 * @param string $hookname Hookname value
	 * @return obj Current instance
	 */
	public function set_hookname($hookname) {
		if ( !empty($hookname) && is_string($hookname) )
			$this->hookname = $hookname;
		return $this;
	}
	
	/**
	 * Retrieve parent
	 * Formats parent ID for custom parents
	 * @uses parent::get_parent()
	 * @return string Parent ID
	 */
	public function get_parent() {
		$parent = parent::get_parent();
		return ( $this->is_parent_custom() ) ? $this->add_prefix($parent) : $parent;
	}
	
	/**
	 * Set parent for view
	 * @param string $parent Parent ID
	 * @return obj Current instance
	 */
	public function set_parent($parent) {
		if ( $this->parent_required ) {
			if ( !empty($parent) && is_string($parent) )
			$this->parent = $parent;
		} else {
			$this->parent = null;
		}
		return $this;
	}
		
	/**
	 * Specify whether parent is a custom view or a WP view
	 * @param bool $custom (optional) TRUE if custom, FALSE if WP
	 * @return obj Current instance
	 */
	protected function set_parent_custom($custom = true) {
		if ( $this->parent_required ) {
			$this->parent_custom = !!$custom;
		}
		return $this;
	}
	
	/**
	 * Set parent as WP view
	 * @uses this->set_parent_custom()
	 * @return obj Current instance
	 */
	public function set_parent_wp() {
		return $this->set_parent_custom(false);
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
	public function get_uri($file = null, $format = null) {
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
		
		if ( empty($format) ) {
			$delim = ( strpos($file, '?') === false ) ? '?' : '&amp;';
			$format = '%1$s' . $delim . 'page=%2$s';
		}
		$uri = sprintf($format, $file, $this->get_id());

		return $uri;
	}
	
	/* Handlers */
	
	/**
	 * Default View handler
	 * Used as callback when none set
	 */
	public function handle() {}
	
	/* Validation */
	
	/**
	 * Check if instance is valid based on required properties/data types
	 * @return bool TRUE if valid, FALSE if not valid 
	 */
	public function is_valid() {
		$valid = true;
		foreach ( $this->required as $prop => $type ) {
			if ( empty($this->{$prop} )
				|| ( !empty($type) && is_string($type) && ( $f = 'is_' . $type ) && function_exists($f) && !$f($this->{$prop}) ) ) {
				$valid = false;
				break;
			}
		}
		return $valid;
	}
	
	protected function is_child() {
		return $this->parent_required;
	}
	
	protected function is_parent_custom() {
		return ( $this->is_child() && $this->parent_custom ) ? true : false;
	}
	
	public function is_parent_wp() {
		return ( $this->is_child() && !$this->parent_custom ) ? true : false;
	}
	
	public function is_options_valid() {
		$opts = $this->get_options();
		return ( is_object($opts) && $this->util->is_a($opts, $this->util->get_class('Options')) ) ? true : false;
	}
	
	/* Options */
	
	/**
	 * Parse options build vars
	 * @uses `options_parse_build_vars` filter hook
	 */
	public function options_parse_build_vars($vars, $opts) {
		//Handle form submission
		if ( isset($_REQUEST[$opts->get_id('formatted')]) ) {
			$vars['validate_pre'] = $vars['save_pre'] = true;
		}
		return $vars;
	}
	
	/**
	 * Actions to perform before building options
	 */
	public function options_build_pre(&$opts) {
		//Build form output
		$form_id = $this->add_prefix('admin_form_' . $this->get_id_raw());
		?>
		<form id="<?php esc_attr_e($form_id); ?>" name="<?php esc_attr_e($form_id); ?>" action="" method="post">
		<?php
	}
	
	/**
	 * Actions to perform after building options
	 */
	public function options_build_post(&$opts)	{
		submit_button();
		?>
		</form>
		<?php
	}
	
	/**
	 * Builds option groups output
	 * @param SLB_Options $options Options instance
	 * @param array $groups Groups to build
	 */
	public function options_build_groups($options, $groups) {
		//Add meta box for each group
		$screen = get_current_screen();
		foreach ( $groups as $gid ) {
			$g = $options->get_group($gid);
			if ( !count($options->get_items($gid)) ) {
				continue;
			}
			add_meta_box($gid, $g->title, $this->m('options_build_group'), $screen, 'normal', 'default', array('options' => $options, 'group' => $gid));
		}
		//Build options
		do_meta_boxes($screen, 'normal', null);
	}
	
	public function options_build_group($obj, $args) {
		$args = $args['args'];
		$group = $args['group'];
		$opts = $args['options'];
		$opts->build_group($group);
	}
	
	protected function show_options($show_submit = true) {
		//Build options output
		if ( !$this->is_options_valid() ) {
			return false;
		}
		/**
		 * @var SLB_Options
		 */
		$opts =& $this->get_options();
		$hooks = array (
			'filter'	=> array (
				'parse_build_vars'		=> array( $this->m('options_parse_build_vars'), 10, 2 )
			),
			'action'	=> array (
				'build_pre'				=> array( $this->m('options_build_pre') ),
				'build_post'			=> array ( $this->m('options_build_post') ),
			)
		);
		//Add hooks
		foreach ( $hooks as $type => $hook ) {
			$m = 'add_' . $type;
			foreach ( $hook as $tag => $args ) {
				array_unshift($args, $tag);
				call_user_func_array($opts->util->m($m), $args);
			}
		}
		?>
		<div class="metabox-holder">
		<?php
		//Build output
		$opts->build(array('build_groups' => $this->m('options_build_groups')));
		?>
		</div>
		<?php
		//Remove hooks
		foreach ( $hooks as $type => $hook ) {
			$m = 'remove_' . $type;
			foreach ( $hook as $tag => $args ) {
				call_user_func($opts->util->m($m), $tag, $args[0]);
			}
		}
	}

	/* UI Elements */
	
	/**
	 * Build submit button element
	 * @param string $text (optional) Button text
	 * @param string $id (optional) Button ID (prefixed on output)
	 * @param object $parent (optional) Page/Section object that contains button
	 * @return object Button properties (id, output)
	 */
	protected function get_button_submit($text = null, $id = null, $parent = null) {
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
	protected function button_submit($text = null, $id = null, $parent = null) {
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
	protected $position = null;
	
	/* Init */
	
	public function __construct($id, $labels, $options = null, $callback = null, $capability = null, $icon = null, $position = null) {
		//Default
		parent::__construct($id, $labels, $options, $callback, $capability, $icon);
		//Class specific
		$this->set_position($position);
	}
	
	/* Getters/Setters */
	
	/**
	 * Set menu position
	 * @return obj Current instance
	 */
	public function set_position($position) {
		if ( is_int($position) )
			$this->position = $position;
		return $this;
	}
	
	/* Handlers */
	
	public function handle() {
		if ( !current_user_can($this->get_capability()) )
			wp_die(__('Access Denied', 'simple-lightbox'));
		?>
		<div class="wrap">
			<h2><?php esc_html_e( $this->get_label('header') ); ?></h2>
			<?php
			$this->show_options();
			?>
		</div>
		<?php
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
	
	protected $parent_required = true;
	
	/* Init */
	
	public function __construct($id, $parent, $labels, $options = null, $callback = null, $capability = null, $icon = null) {
		//Default
		parent::__construct($id, $labels, $options, $callback, $capability, $icon);
		//Class specific
		$this->set_parent($parent);
	}
	
	/* Operations */
	
	protected function show_icon() {
		echo screen_icon();
	}
	
	/* Handlers */
	
	/**
	 * Default Page handler
	 * Builds options form UI for page
	 * @see this->init_menus() Set as callback for custom admin pages
	 * @uses current_user_can() to check if user has access to current page
	 * @uses wp_die() to end execution when user does not have permission to access page
	 */
	public function handle() {
		if ( !current_user_can($this->get_capability()) )
			wp_die(__('Access Denied', 'simple-lightbox'));
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
	
	protected $parent_required = true;
	protected $parent_custom = false;
	
	/* Init */
	
	public function __construct($id, $parent, $labels, $options = null, $callback = null, $capability = null) {
		//Default
		parent::__construct($id, $labels, $options, $callback, $capability);
		//Class specific
		$this->set_parent($parent);
	}
	
	/* Getters/Setters */
	
	public function get_uri() {
		$file = 'options-' . $this->get_parent() . '.php';
		return parent::get_uri($file, '%1$s#%2$s');
	}

	/**
	 * Retrieve formatted title for section
	 * Wraps title text in element with anchor so that it can be linked to
	 * @return string Title
	 */
	public function get_title() {
		return '<div id="' . $this->get_id() . '" class="' . $this->add_prefix('section_head') . '">' . $this->get_label('title') . '</div>';
	}
	
	/* Handlers */
	
	public function handle() {
		$this->show_options(false);
	}
	
	public function options_parse_build_vars($vars, $opts) {
		return $vars;
	}
	
	public function options_build_pre() {}
	
	public function options_build_post() {}
}

class SLB_Admin_Reset extends SLB_Admin_View {
	/* Properties */
	
	protected $required = array ( 'options' => 'object' );
	
	protected $parent_required = false;
	
	/* Init */
	
	function __construct($id, $labels, $options) {
		parent::__construct($id, $labels, $options);
	}
	
	/* Handlers */
	
	/**
	 * Default handler
	 * Resets plugin settings
	 * @return string Status message (success, fail, etc.)
	 */
	public function handle() {
		//Validate user
		if ( ! current_user_can('activate_plugins') || ! check_admin_referer($this->get_id()) )
			wp_die(__('Access Denied', 'simple-lightbox'));

		//Reset settings
		if ( $this->is_options_valid() )
			$this->get_options()->reset(true);
		
		//Set Status Message
		$this->set_message($this->get_label('success'));
		
		/*
		//Redirect user
		$uri = remove_query_arg(array('_wpnonce', 'action'), add_query_arg(array($this->add_prefix('action') => $action), $_SERVER['REQUEST_URI']));
		wp_redirect($uri);
		exit;
		*/
	}
	
	public function get_uri() {
		return wp_nonce_url(add_query_arg($this->get_query_args(), remove_query_arg($this->get_query_args_remove(), $_SERVER['REQUEST_URI'])), $this->get_id());
	}
	
	protected function get_query_args() {
		return array (
			'action'					=> $this->add_prefix('admin'),
			$this->add_prefix('type')	=> 'view',
			$this->add_prefix('group')	=> 'reset',
			$this->add_prefix('obj')	=> $this->get_id_raw()
		);
	}
	
	protected function get_query_args_remove() {
		$args_r = array (
			'_wpnonce',
			$this->add_prefix('action')
		);
		
		return array_unique( array_merge( array_keys( $this->get_query_args() ), $args_r ) );
	}
	
	public function get_link_attr() {
		return array (
			'class' 	=> 'delete',
			'onclick'	=> "return confirm('" . $this->get_label('confirm') . "')"
		);
	}
	
}
