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
	 * Messages
	 * @var array
	 */
	var $messages = array(
		'reset'			=> 'The settings have been reset',
		'beta'			=> '<strong class="%1$s">Notice:</strong> This update is a <strong class="%1$s">Beta version</strong>. It is highly recommended that you test the update on a test server before updating the plugin on a production server.',
		'access_denied'	=> 'You do not have sufficient permissions to manage plugins for this blog.'
	);
	
	/**
	 * Custom admin sections
	 * @var array
	 */
	var $sections = array ();
	
	/**
	 * Section delimeter
	 * @var string
	 */
	var $delim_section = '_sec_';
	
	var $styles = array(
		'admin'		=> array (
			'file'		=> 'css/admin.css',
			'context'	=> array('admin_page_plugins', 'admin_page_options-media')
		)
	);

	/* Constructor */
	
	function SLB_Admin() {
		$args = func_get_args();
		call_user_func_array(array(&$this, '__construct'), $args);
	}
	
	function __construct() {
		parent::__construct();
	}
	
	/* Init */
	
	function register_hooks() {
		parent::register_hooks();
		
		//Init
		add_action('admin_init', $this->m('init_menus'));
		
		//Reset Settings
		// add_action('admin_action_' . $this->add_prefix('reset'), $this->m('admin_reset'));
		add_action('admin_notices', $this->m('show_notices'));
		
		//Plugin listing
		add_filter('plugin_action_links_' . $this->util->get_plugin_base_name(), $this->m('plugin_action_links'), 10, 4);
		// add_action('in_plugin_update_message-' . $this->util->get_plugin_base_name(), $this->m('plugin_update_message'), 10, 2);
		// add_filter('site_transient_update_plugins', $this->m('plugin_update_transient'));
	}
	
	/* Methods */
	
	/**
	 * Get page name elements of WP admin pages
	 * @return obj Page name elements
	 */
	function get_page_elements() {
		static $elements = null;
		if ( empty($elements) ) {
			$elements = new stdClass();
			$elements->start = 'options-';
			$elements->extension = 'php';
		}
		return $elements;
	}
	
	/**
	 * Checks if specified page is a default WP admin page
	 * @param string $page Page name
	 * @return bool TRUE if specified page is WP default
	 */
	function is_default_page($page)	{
		$e = $this->get_page_elements();
		return ( strpos($page, $e->start) === 0
			&& $this->util->has_file_extension($page, $e->extension)
			&& strlen($page) > strlen($e->start . '.' . $e->extension) 
		) ? true : false;
	}
	
	/**
	 * Parses specified page & returns internal WP page name (if possible)
	 * @param string $page Page
	 * @return string Page name
	 */
	function parse_page($page) {
		if ( $this->is_default_page($page) ) {
			$e = $this->get_page_elements();
			$page = substr($page, strlen($e->start));
			$page = substr($page, 0, strpos($page, '.' . $e->extension));
		}
		return $page;
	}
	
	function build_page($page) {
		$e = $this->get_page_elements();
		return $e->start . $page . '.' . $e->extension;
	}
	
	/**
	 * Adds settings section for plugin functionality
	 * Section is added to specified admin section/menu
	 */
	function init_menus() {
		//Iterate through pages
		foreach ( $this->sections as $page => $section ) {
			//Iterate through settings added to page
			foreach ( $section as $props ) {
				$curr = basename($_SERVER['SCRIPT_NAME']);
				$pages = array($page);
				if ( $this->is_default_page($page) )
					$pages[] = 'options.php';
				if ( !in_array($curr, $pages) ) {
					return;
				}
				
				$page = $this->parse_page($page);
				$id = $this->build_section_id($page, $props);
				//Section
				add_settings_section($id, '<div id="' . $id . '">' . $props['title'] . '</div>', $this->m('build_section'), $page);
				//Register settings container
				if ( $this->util->is_a($props['options'], 'Options') )
					register_setting($page, $this->add_prefix($props['id']), $props['options']->m('validate'));
			}
		}
 	}
	
	/**
	 * Construct section ID
	 * @param string $page Page section is on
	 * @param array $props Section properties
	 * @
	 */
	function build_section_id($page, $props) {
		return $page . $this->delim_section . $this->add_prefix($props['id']);
	}
	
	/**
	 * Separate section ID into its component parts
	 * @param string $id Section ID
	 * @return object Section ID components
	 *  > page
	 *  > id
	 */
	function parse_section_id($id) {
		$parts = array('page' => '', 'id' => '');
		list($parts['page'], $parts['id']) = explode($this->delim_section, $id);
		$parts['page'] = $this->build_page($parts['page']);
		$parts['id'] = $this->remove_prefix($parts['id']);
		return (object) $parts;
	}
	
	/**
	 * Add section
	 * @param string $id Unique section ID
	 * @param string $title Section title
	 * @param object $options Reference to $options instance to build section with
	 * @param array $groups (Optional) Option groups to include in section
	 */
	function add_section($id, $title, $page, &$options, $groups = array()) {
		if ( !$this->util->is_a($options, 'Options') )
			return false;
		$props = array (
			'id'		=> $id,
			'title'		=> $title,
			'options'	=> $options,
			'groups'	=> $groups,
			''
		);
		//Add Section
		if ( !isset($this->sections[$page]) || !is_array($this->sections[$page]) )
			$this->sections[$page] = array();
		$this->sections[$page][$id] =& $props;
	}
	
	/**
	 * Build section output
	 * @see do_settings_sections() (calling function)
	 * @param array $section Section data
	 */
	function build_section($section) {
		//Retrieve specified section properties
		$parts = $this->parse_section_id($section['id']);
		if ( !isset($this->sections[$parts->page]) || !is_array($this->sections[$parts->page]) || !isset($this->sections[$parts->page][$parts->id]) || !is_array($this->sections[$parts->page][$parts->id]) )
			return false;
		$props =& $this->sections[$parts->page][$parts->id];
		$options =& $props['options'];
		if ( $this->util->is_a($options, 'Options') ) {
			$options->build($props['groups']);
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
	 * Reset plugin settings
	 * Redirects to referring page upon completion
	 */
	function reset_settings() {
		//Validate user
		if ( ! current_user_can('activate_plugins') || ! check_admin_referer($this->add_prefix('reset')) )
			wp_die(__($this->get_message('access_denied'), $this->util->get_plugin_textdomain()));
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
	function show_notices() {
		if ( is_admin() && isset($_REQUEST[$this->add_prefix('action')]) ) {
			$action = $_REQUEST[$this->add_prefix('action')];
			$msg = null;
			if ( $action ) {
				$msg = $this->get_message($action);
				if ( ! empty($msg) ) {
					?>
					<div id="message" class="updated fade"><p><?php echo $msg; ?></p></div>
					<?php 
				}
			}
		}
	}
	
	/**
	 * Plugin Actions
	 * @var array
	 */
	var $plugin_actions = array ();
	
	function add_plugin_action($action, $props) {
		if ( !is_string($action) || !is_array($props) || empty($props) )
			return false;
		$required = array ( 'title', 'callback' );
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
			//Get actions 
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
	 * Adds additional message for plugin updates
	 * @uses `in_plugin_update_message-$plugin-name` Action hook 
	 * @var array $plugin_data Current plugin data
	 * @var object $r Update response data
	 */
	function plugin_update_message($plugin_data, $r) {
		if ( !isset($r->new_version) )
			return false;
		if ( stripos($r->new_version, 'beta') !== false ) {
			$cls_notice = $this->add_prefix('notice');
			echo '<br />' . $this->admin_plugin_update_get_message($r);
		}
	}
	
	/**
	 * Modify update plugins response data if necessary
	 * @uses `site_transient_update_plugins` Filter hook
	 * @param obj $transient Transient data
	 * @return obj Modified transient data
	 */
	function plugin_update_transient($transient) {
		$n = $this->util->get_plugin_base_name();
		if ( isset($transient->response) && isset($transient->response[$n]) && is_object($transient->response[$n]) && !isset($transient->response[$n]->upgrade_notice) ) {
			$r =& $transient->response[$n];
			$r->upgrade_notice = $this->admin_plugin_update_get_message($r);
		}
		return $transient;
	}
	
	/**
	 * Retrieve custom update message
	 * @param obj $r Response data from plugin update API
	 * @return string Message (Default: empty string)
	 */
	function admin_plugin_update_get_message($r) {
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
	 * @param string $msg Message ID
	 * @return string Message text
	 */
	function get_message($msg) {
		$msg = '';
		if ( is_string($msg) && !empty($msg) && isset($this->messages[$msg]) )
			$msg = $this->messages[$msg];
		return $msg;
	}
	
	/**
	 * Set message text
	 * @param string $id Message ID
	 * @param string $text Message text
	 */
	function set_message($id, $text) {
		$this->messages[trim($id)] = $text;
	}
	
}