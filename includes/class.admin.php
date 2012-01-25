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
	 * @var array
	 */
	var $menus = array();
	
	/**
	 * Custom admin pages
	 * @var array
	 */
	var $pages = array();
	
	/**
	 * Custom admin sections
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
	
	/**
	 * Output admin page
	 * Passes request to page's callback or options building method
	 * @see this->init_menus() Set as callback for custom admin pages
	 * @uses current_user_can() to check if user has access to current page
	 * @uses wp_die() to end execution when user does not have permission to access page
	 * @uses this->menu_cap_default Default capability for page access
	 * @uses this->parse_page
	 */
	function handle_page() {
		if ( !current_user_can($this->menu_cap_default) )
			wp_die('no permissions');
		global $dbg;
		//Retrieve page being displayed
		$page =& $this->parse_page();
		if ( !$page )
			return false;
		?>
		<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php echo esc_html( $this->get_title($page, 'header') ); ?></h2>
		<?php
		//Process page output
		if ( is_callable($page->callback) )
			$page->callback($page);
		elseif ( $this->util->is_a($page->options, 'Options') ) {
			//TODO: Process form submission
			$submit_id = $this->add_prefix('save_options');
			if ( isset($_REQUEST[$submit_id]) ) {
				$dbg->print_message('Processing submission');
				$values = $page->options->validate();
				$dbg->print_message($values);
				$page->options->save();
			}
			
			//Build form output
			//TODO: Move form tag to options class
			$form_id = $this->add_prefix('admin_page');
			?>
			<form id="<?php echo esc_attr($form_id); ?>" name="<?php echo esc_attr($form_id); ?>" action="" method="post"> 
			<?php
			$page->options->build();
			?>
			<p class="submit">
				<input type="submit" class="button-primary" id="<?php echo esc_attr($submit_id); ?>" name="<?php echo esc_attr($submit_id); ?>" value="<?php _e('Save Changes'); ?>" />
			</p>
			</form>
			<?php
		}
		?>
		</div>
		<?php
	}
	
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
	
	function get_title($obj, $type = '') {
		$ret = '';
		if ( isset($obj->title) ) {
			$t = $obj->title;
			if ( is_array($t) && isset($t[$type]) )
				$t = $t[$type];
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
		//Add top level menus (when necessary)
		$pre = 'menu';
		foreach ( $this->menus as $menu ) {
			$menu = $menu;
			$hook = add_menu_page($menu->title, $menu->title_menu, $this->menu_cap_default, $this->add_prefix($menu->id), $this->m('handle_menu'));
		}
		
		//Add subpages
		$wrapper = array('[', ']');
		foreach ( $this->pages as $page ) {
			//Handle pages for default WP menus
			if ( $this->util->has_wrapper($page->parent, $wrapper) ) {
				$f = 'add_' . $this->util->remove_wrapper($page->parent, $wrapper) . '_page';
				if ( function_exists($f) )
					$hook = $f($this->get_title($page, 'header'), $this->get_title($page, 'menu'), $this->menu_cap_default, $this->add_prefix($page->id), $this->m('handle_page'));
			} else {
				//Handle pages for custom menus
				$this->add_prefix_ref($page->parent);
				$hook = add_submenu_page($page->parent, $page->title, $page->title, $this->menu_cap_default, $this->add_prefix($page->id), $this->m('handle_page'));
			}
		}
		
		//Add sections
		foreach ( $this->sections as $s ) {
			$this->add_prefix_ref($s->id);
			add_settings_section($s->id, $this->get_section_title($s->id), $this->m('handle_section'), $s->parent);
			if ( $this->util->is_a($s->options, 'Options') )
				register_setting($s->parent, $s->id, $s->options->m('validate'));
		}
 	}

	/* Methods */
	
	/*-** Menus **-*/
	
	/**
	 * Adds custom admin panel
	 * @param string $id Menu ID
	 * @param string $title Page title
	 * @param string $title_menu (optional) Nav title
	 * @param int $pos (optional) Menu position in navigation (index order)
	 * @return string Menu ID
	 */
	function add_menu($id, $title, $title_menu = '', $pos = null) {
		//Init args
		$args = func_get_args();
		if ( count($args) == 1 && is_array($args[0]) )
			list($id, $title, $title_menu, $pos) = array_pad($args[0], 4, null);
		if ( empty($title_menu) || !is_string($title_menu) )
			$title_menu = $title;
		//Add Menu
		$this->menus[$id] = (object) compact('id', 'title', 'title_menu', 'pos');
		return $id;
	}
	
	/* Page */
	
	/**
	 * Add admin page
	 * @uses this->pages
	 * @uses this->add_menu() to define menus on-demand
	 * @param string $id Page ID (unique)
	 * @param string|array Title value(s) (Associative array for multiple title values)
	 * 	> menu: Menu title
	 * 	> header: Page header
	 * @param string|array $menu Menu to add page to (Use array to create new menus on-demand)
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param int $pos (optional) Position in menu (index order)
	 * @return string Page ID
	 */
	function add_page($id, $title, $menu, $options = null, $callback = null, $pos = null, $capability = null) {
		//Init args
		$args = func_get_args();
		if ( count($args) == 1 && is_array($args[0]) )
			list($id, $title, $menu, $options, $callback, $pos) = array_pad($args[0], 6, null);
		 
		//Init menu
		if ( is_array($menu) )
			$menu = $this->add_menu($menu);
		if ( !is_string($menu) )
			return false;
		
		//Init page
		$parent = $menu;
		$this->parse_options($options);
		$props = compact('id', 'title', 'parent', 'options', 'callback', 'pos', 'capability');
		
		$this->pages[$id] = (object) $props;
		return $id;
	}
	
	/* WP Menus */
	
	/**
	 * Add admin page to Dashboard menu
	 * @see add_dashboard_page()
	 * @uses this->add_page()
	 * @param string $id Page ID (unique)
	 * @param string|array Title value(s) (Associative array for multiple title values)
	 * 	> nav: Navigation title
	 * 	> header: Page header
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param int $pos (optional) Position in menu (index order)
	 * @return string Page ID
	 */
	function add_dashboard_page($id, $title, $options = null, $callback = null, $pos = null) {
		$menu = 'dashboard';
		$id = $this->add_page($id, $title, $this->util->add_wrapper($menu, '[', ']'), $options, $callback, $pos);
		return $id;
	}

	/**
	 * Add admin page to Comments menu
	 * @see add_comments_page()
	 * @uses this->add_page()
	 * @param string $id Page ID (unique)
	 * @param string|array Title value(s) (Associative array for multiple title values)
	 * 	> nav: Navigation title
	 * 	> header: Page header
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param int $pos (optional) Position in menu (index order)
	 * @return string Page ID
	 */
	function add_comments_page($id, $title, $options = null, $callback = null, $pos = null) {
		$menu = 'comments';
		$id = $this->add_page($id, $title, $this->util->add_wrapper($menu, '[', ']'), $options, $callback, $pos);
		return $id;
	}
	
	/**
	 * Add admin page to Links menu
	 * @see add_links_page()
	 * @uses this->add_page()
	 * @param string $id Page ID (unique)
	 * @param string|array Title value(s) (Associative array for multiple title values)
	 * 	> nav: Navigation title
	 * 	> header: Page header
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param int $pos (optional) Position in menu (index order)
	 * @return string Page ID
	 */
	function add_links_page($id, $title, $options = null, $callback = null, $pos = null) {
		$menu = 'links';
		$id = $this->add_page($id, $title, $this->util->add_wrapper($menu, '[', ']'), $options, $callback, $pos);
		return $id;
	}

	
	/**
	 * Add admin page to Posts menu
	 * @see add_posts_page()
	 * @uses this->add_page()
	 * @param string $id Page ID (unique)
	 * @param string|array Title value(s) (Associative array for multiple title values)
	 * 	> nav: Navigation title
	 * 	> header: Page header
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param int $pos (optional) Position in menu (index order)
	 * @return string Page ID
	 */
	function add_posts_page($id, $title, $options = null, $callback = null, $pos = null) {
		$menu = 'posts';
		$id = $this->add_page($id, $title, $this->util->add_wrapper($menu, '[', ']'), $options, $callback, $pos);
		return $id;
	}
	
	/**
	 * Add admin page to Pages menu
	 * @see add_pages_page()
	 * @uses this->add_page()
	 * @param string $id Page ID (unique)
	 * @param string|array Title value(s) (Associative array for multiple title values)
	 * 	> nav: Navigation title
	 * 	> header: Page header
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param int $pos (optional) Position in menu (index order)
	 * @return string Page ID
	 */
	function add_pages_page($id, $title, $options = null, $callback = null, $pos = null) {
		$menu = 'pages';
		$id = $this->add_page($id, $title, $this->util->add_wrapper($menu, '[', ']'), $options, $callback, $pos);
		return $id;
	}
	
	/**
	 * Add admin page to Media menu
	 * @see add_media_page()
	 * @uses this->add_page()
	 * @param string $id Page ID (unique)
	 * @param string|array Title value(s) (Associative array for multiple title values)
	 * 	> nav: Navigation title
	 * 	> header: Page header
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param int $pos (optional) Position in menu (index order)
	 * @return string Page ID
	 */
	function add_media_page($id, $title, $options = null, $callback = null, $pos = null) {
		$menu = 'media';
		$id = $this->add_page($id, $title, $this->util->add_wrapper($menu, '[', ']'), $options, $callback, $pos);
		return $id;
	}
	
	/**
	 * Add admin page to Themes menu
	 * @see add_theme_page()
	 * @uses this->add_page()
	 * @param string $id Page ID (unique)
	 * @param string|array Title value(s) (Associative array for multiple title values)
	 * 	> nav: Navigation title
	 * 	> header: Page header
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param int $pos (optional) Position in menu (index order)
	 * @return string Page ID
	 */
	function add_theme_page($id, $title, $options = null, $callback = null, $pos = null) {
		$menu = 'theme';
		$id = $this->add_page($id, $title, $this->util->add_wrapper($menu, '[', ']'), $options, $callback, $pos);
		return $id;
	}
	
	/**
	 * Add admin page to Plugins menu
	 * @see add_plugins_page()
	 * @uses this->add_page()
	 * @param string $id Page ID (unique)
	 * @param string|array Title value(s) (Associative array for multiple title values)
	 * 	> nav: Navigation title
	 * 	> header: Page header
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param int $pos (optional) Position in menu (index order)
	 * @return string Page ID
	 */
	function add_plugins_page($id, $title, $options = null, $callback = null, $pos = null) {
		$menu = 'plugins';
		$id = $this->add_page($id, $title, $this->util->add_wrapper($menu, '[', ']'), $options, $callback, $pos);
		return $id;
	}
	
	/**
	 * Add admin page to Options menu
	 * @see add_options_page()
	 * @uses this->add_page()
	 * @param string $id Page ID (unique)
	 * @param string|array Title value(s) (Associative array for multiple title values)
	 * 	> nav: Navigation title
	 * 	> header: Page header
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param int $pos (optional) Position in menu (index order)
	 * @return string Page ID
	 */
	function add_options_page($id, $title, $options = null, $callback = null, $pos = null) {
		$menu = 'options';
		$id = $this->add_page($id, $title, $this->util->add_wrapper($menu, '[', ']'), $options, $callback, $pos);
		return $id;
	}
	
	/**
	 * Add admin page to Tools menu
	 * @see add_management_page()
	 * @uses this->add_page()
	 * @param string $id Page ID (unique)
	 * @param string|array Title value(s) (Associative array for multiple title values)
	 * 	> nav: Navigation title
	 * 	> header: Page header
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param int $pos (optional) Position in menu (index order)
	 * @return string Page ID
	 */
	function add_management_page($id, $title, $options = null, $callback = null, $pos = null) {
		$menu = 'management';
		$id = $this->add_page($id, $title, $this->util->add_wrapper($menu, '[', ']'), $options, $callback, $pos);
		return $id;
	}
	
	/**
	 * Add admin page to Users menu
	 * @uses this->add_page()
	 * @param string $id Page ID (unique)
	 * @param string|array Title value(s) (Associative array for multiple title values)
	 * 	> nav: Navigation title
	 * 	> header: Page header
	 * @param obj|array $options (optional) Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @param int $pos (optional) Position in menu (index order)
	 * @return string Page ID
	 */
	function add_users_page($id, $title, $options = null, $callback = null, $pos = null) {
		$menu = 'users';
		$id = $this->add_page($id, $title, $this->util->add_wrapper($menu, '[', ']'), $options, $callback, $pos);
		return $id;
	}
	
	/* Section */
	
	/**
	 * Add section
	 * @uses this->sections
	 * @param string $id Unique section ID
	 * @param string $title Section title
	 * @param string|array Page ID (Use array to define page on-demand)
	 * @param obj|array $options Options object (Use array to define options object & specific group(s))
	 * 	> Array Example: array($options, 'group_1') or array($options, array('group_1', 'group_3'))
	 * @param callback $callback (optional) Callback for custom page building
	 * @return string Section ID
	 */
	function add_section($id, $title, $page, $options = null, $callback = null) {
		//Init page
		if ( is_array($page) )
			$page = $this->add_page($page);
		
		//Validate args
		if ( !is_string($page) )
			return false;
		if ( !$options && !$callback )
			return false;
		
		$parent = $page;
		$this->parse_options($options);
		$props = compact('id', 'title', 'parent', 'options', 'callback');
		
		//Add Section
		$this->sections[$id] = (object) $props;
		return $id;
	}
	
	function parse_options(&$options) {
		if ( is_array($options) && $this->util->is_a($options[0], 'Options') )
			$options = $options[0];
		if ( !$this->util->is_a($options, 'Options') )
			$options = null;
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
		//Add link to settings (only if active)
		if ( is_plugin_active($this->util->get_plugin_base_name()) ) {
			/* Get Actions */
			
			//Settings
			$settings = __('Settings', $this->util->get_plugin_textdomain());
			//TODO: Build method for retrieving page/section URI
			$settings_uri = 'options-media.php';
			$settings_link = $this->util->build_html_link($settings_uri, $settings, array('class' => 'delete'));
			
			//Reset
			$reset = __('Reset', $this->util->get_plugin_textdomain());
			$reset_confirm = "'" . __('Are you sure you want to reset your settings?', $this->util->get_plugin_textdomain()) . "'";
			$action = $this->add_prefix('reset');
			$reset_uri = wp_nonce_url(add_query_arg('action', $action, remove_query_arg(array($this->add_prefix('action'), 'action'), $_SERVER['REQUEST_URI'])), $action);
			$reset_link = $this->util->build_html_link($reset_uri, $reset, array('id' => $this->add_prefix('reset'), 'onclick' => 'return confirm(' . $reset_confirm . ')'));
			
			//Add links
			array_unshift($actions, $settings_link);
			array_splice($actions, 1, 0, $reset_link);
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
