<?php
require_once 'class.base.php';

/**
 * Theme instance
 * @package Simple Lightbox
 * @subpackage Themes
 * @author Archetyped
 */
class SLB_Theme extends SLB_Base {
	/*-** Properties **-*/
	
	/**
	 * @var string Unique ID
	 */
	private $_id = '';
	
	/**
	 * @var string Pretty name
	 */
	private $_name = '';
	
	/**
	 * @var SLB_Theme Parent theme
	 */
	private $_parent = null;
	
	/**
	 * @var string Layout file
	 */
	private $_layout_path = '';
	
	/**
	 * @var array Attached files
	 * > scripts	array JS scripts
	 * > styles		array Stylesheets
	 */
	private $_files = array(
		'scripts'	=> array(),
		'styles'	=> array()
	);
	
	/**
	 * @var array Properties that can be inherited from parent
	 */
	private $_uses_parent = array();
	
	/**
	 * @var string Class mode
	 * @see SLB_Base::$mode
	 */
	var $mode = 'object';
	
	/*-** Methods **-*/
	
	/**
	 * Constructor
	 */
	public function __construct($id, $name) {
		parent::__construct();
		$this->set_id($id);
		$this->set_name($name);
	}
	
	public function is_valid($full = false) {
		$ret = ( strlen($this->get_id()) ) ? true : false;
		if ( $ret && !!$full ) {
			$ret = ( strlen($this->get_name()) ) ? true : false;
		}
		return $ret;
	}
	
	/*-** Getters/Setters **-*/
	
	/**
	 * Get ID
	 * @return string ID
	 */
	public function get_id() {
		return $this->_id;
	}
	
	/**
	 * Set ID
	 * @param string $id Theme ID
	 * @return SLB_Theme Current theme instance
	 */
	private function set_id($id) {
		if ( is_string($id) ) {
			$this->_id = trim($id);
		}
		return $this;
	}
	
	/**
	 * Get name
	 * @return string Name
	 */
	public function get_name() {
		return $this->_name;
	}
	
	/**
	 * Set theme's name
	 * @param string $name Theme name
	 * @return SLB_Theme Current theme instance
	 */
	public function set_name($name) {
		if ( is_string($name) ) {
			$this->_name = trim($name);
		}
		return $this;
	}
	
	/**
	 * Get theme parent
	 * @return SLB_Theme Parent theme instance
	 */
	public function get_parent($use_default = false) {
		$ret = $this->_parent;
		if ( empty($ret) && !!$use_default ) {
			$ret = new SLB_Theme('', '');
		}
		return $ret;
	}
	
	/**
	 * Set theme's parent
	 * @param SLB_Theme $parent Parent theme ID or instance
	 * @return SLB_Theme Current theme instance
	 */
	public function set_parent($parent) {
		$this->_parent = ( $parent instanceof $this ) ? $parent : null;
		return $this;
	}

	/**
	 * Check if theme has a parent
	 * @return bool TRUE if theme has a parent, FALSE otherwise
	 */
	public function has_parent() {
		return ( $this->_parent instanceof $this ) ? true : false;
	}
	
	/**
	 * Retrieve all theme ancestors
	 * @return array Theme ancestors
	 */
	public function get_ancestors() {
		$ret = array();
		/**
		 * @var SLB_Theme
		 */
		$thm = $this;
		while ( $thm->has_parent() ) {
			$par = $thm->get_parent();
			//Add ancestor
			if ( $par->is_valid() && !in_array($par, $ret, true) ) {
				$ret[] = $par;
			}
			//Get next ancestor
			$thm = $par;
		}
		return $ret;
	}
	
	/* Layout */
	
	/**
	 * Set layout file
	 * @param string $src Path to the layout from WP's plugins directory. Example: 'plugin-name/theme/layout.html'
	 * @return SLB_Theme Current theme instance
	 */
	public function set_layout($src) {
		if ( !is_string($src) || !file_exists($this->util->normalize_path(WP_PLUGIN_DIR, $src)) ) {
			$src = '';
		}
		$this->_layout_path = $src;
		return $this;
	}
	
	/**
	 * Retrieve layout file
	 * @param string $format (optional) Layout format
	 * > default	Original value
	 * > path		File Path (Relative to WordPress root)
	 * > uri		File URI
	 * @return string File path
	 */
	public function get_layout($format = null) {
		$ret = ( is_string($this->_layout_path) ) ? $this->_layout_path : '';
		if ( !empty($ret) && !empty($format) ) {
			switch ( $format ) {
				case 'uri' :
					$ret = $this->util->normalize_path(WP_PLUGIN_URL, $ret);
					break;
				case 'path' :
					$ret = $this->util->normalize_path(WP_PLUGIN_DIR, $ret);
					break;
			}
		}
		return $ret;
	}
	
	/* Client files */
	
	/**
	 * Add file
	 * @param string $type Group to add file to
	 * @param string $handle Name of the stylesheet
	 * @param string $src Path to the file from WP's plugins directory. Example: 'plugin-name/theme/style.css'
	 * @return SLB_Theme Current theme
	 */
	private function add_file($type, $handle, $src, $deps = array()) {
		if ( is_string($type) && !empty($type)
			&& is_string($handle) && !empty($handle)
			&& is_string($src) && !empty($src) ) {
			//Validate dependencies
			if ( !is_array($deps) ) {
				$deps = array();
			}
			//Init file group
			if ( !is_array($this->_files[$type]) ) {
				$this->_files[$type] = array();
			}
			//Add file to group
			$this->_files[$type][$handle] = array($handle, $src, $deps); 
		}
		return $this;
	}

	/**
	 * Retrieve files
	 * All files or a specific group of files can be retrieved
	 * @param string $type (optional) File group to retrieve
	 * @return array Files
	 */
	private function get_files($type = null) {
		$ret = $this->_files;
		if ( is_string($type) ) {
			$ret = ( isset($ret[$type]) ) ? $ret[$type] : array();
		}
		if ( !is_array($ret) ) {
			$ret = array();
		}
		return $ret;
	}
	
	/**
	 * Retrieve file
	 * @param string $type Group to retrieve file from
	 * @param string $handle
	 * @return array|null File properties (Default: NULL)
	 */
	private function get_file($type, $handle) {
		$files = $this->get_files($type);
		return ( is_string($type) && isset($files[$handle]) ) ? $files[$handle] : null;
	}
	
	/**
	 * Add stylesheet
	 * @param string $handle Name of the stylesheet
	 * @param string $src Path to stylesheet from WP's plugins directory. Example: 'plugin-name/theme/style.css'
	 * @return SLB_Theme Current theme
	 */
	public function add_style($handle, $src, $deps = array()) {
		return $this->add_file('styles', $handle, $src, $deps);
	}
	
	/**
	 * Retrieve stylesheet files
	 * @return array Stylesheet files
	 */
	public function get_styles() {
		return $this->get_files('styles');
	}
	
	/**
	 * Retrieve stylesheet file
	 * @param string $handle Name of stylesheet
	 * @return array|null File properties (Default: NULL)
	 */
	public function get_style($handle) {
		return $this->get_file('styles', $handle);
	}
	
	/**
	 * Add script
	 * @param string $handle Name of the script
	 * @param string $src Path to script from WP's plugins directory. Example: 'plugin-name/theme/client.js'
	 * @return SLB_Theme Current theme
	 */
	public function add_script($handle, $src, $deps = array()) {
		return $this->add_file('scripts', $handle, $src, $deps);
	}
	
	/**
	 * Retrieve script files
	 * @return array Script files
	 */
	public function get_scripts() {
		return $this->get_files('scripts');
	}
	
	/**
	 * Retrieve script file
	 * @param string $handle Name of script
	 * @return array|null File properties (Default: NULL)
	 */
	public function get_script($handle) {
		return $this->get_file('scripts', $handle);
	}
	
}

/**
 * Theme collection management
 * @package Simple Lightbox
 * @subpackage Themes
 * @author Archetyped
 */
class SLB_Themes extends SLB_Base {
	/* Properties */
	
	private $_parent = null;
	
	/**
	 * @var string Default item
	 */
	private $_id_default = 'default';
	
	/**
	 * @var array Items collection
	 * Associative array
	 * > key: Theme ID
	 * > value: Theme instance
	 */
	private $_items = array();
	
	/**
	 * @var bool Flag to determine if items have been initialized yet
	 */
	private $_items_init = false;
		
	/* Methods */

	function __construct($parent = null) {
		$this->set_parent($parent);
		parent::__construct();
		$this->init();
	}
	
	/* Initialization */
	
	function register_hooks() {
		parent::register_hooks();
		//Register themes
		$this->util->add_action('init_themes', $this->m('init_defaults'), 1);
		
		//Client output
		add_action('wp_footer', $this->m('client_output'), 11);
	}
	
	function init_options() {
		$options_config = array (
			'items'	=> array (
				'theme_default'		=> array (
					'default' 	=> $this->get_default_id(),
					'group' 	=> array('ui', 0),
					'parent' 	=> 'option_select',
					'options' 	=> $this->m('get_field_values'),
					'in_client'	=> true
				),
			)
		);
		
		parent::init_options($options_config);
	}
	
	/**
	 * Add default themes
	 * @uses register_theme() to register the theme(s)
	 */
	function init_defaults($themes) {
		$path_base = $this->util->get_plugin_file_path('themes/default', true);
		//Default
		$def = $this->add_item($this->get_default_id(), 'Default')
				 		->set_layout($path_base . 'layout.html')
				 		->add_style('main', $path_base . 'style.css')
				 		->add_script('main', $path_base . 'client.js');
		//Dark
		$path_base = $this->util->get_plugin_file_path('themes/black', true);
		$dark = $this->add_item($this->add_prefix('black'), 'Dark')
						 ->add_style('main', $path_base . 'style.css')
						 ->set_parent($def);
	}
	
	/* Parent */
	
	private function set_parent($parent = null) {
		if ( $parent instanceof SLB_Base ) {
			$this->_parent = $parent;
		}
	}
	
	private function has_parent() {
		return ( !empty($this->_parent) ) ? true : false;
	}
	
	private function get_parent() {
		return $this->_parent;
	}
	
	/* Collection management */
	
	/**
	 * Add theme to collection
	 * @param string|SLB_Theme $id Theme ID or instance
	 * @param string $name (Optional) Name of theme to add (Not used if $id is a theme instance)
	 * @return SLB_Theme Added theme instance
	 */
	public function add_item($id, $name = null) {
		$thm = ( $id instanceof SLB_Theme ) ? $id : new SLB_Theme($id, $name);
		if ( strlen($thm->get_id()) && strlen($thm->get_name()) ) {
			//Add theme to collection
			$this->_items[$thm->get_id()] = $thm;
		}
		return $thm;
	}
	
	/**
	 * Get all items in collection
	 * @return array Items
	 */
	public function get_items() {
		if ( !$this->_items_init ) {
			$this->_items_init = true;
			$this->util->do_action('init_themes', $this);
		}
		return $this->_items;
	}
	
	public function has_item($id) {
		return ( is_string($id) && !empty($id) && ( $items = $this->get_items() ) && isset($items[$id]) ) ? true : false;
	}
	
	/**
	 * Retrieve item from collection
	 * If item with matching ID does not exist, a new theme instance is returned
	 * @param string $id ID of theme to retrieve
	 * @return SLB_Theme Specified theme
	 */
	public function get_item($id = null) {
		if ( !is_string($id) ) {
			//User-selected item
			$id = $this->options->get_value('theme_default');
			//Fallback item
			if ( !$this->has_item($id) ) {
				$id = $this->get_default_id();
			}
		}
		if ( $this->has_item($id) ) {
			$items = $this->get_items();
			$item = $items[$id];
		} else {
			$item = new SLB_Theme($id, $id);
		}
		return $item;
	}
	
	public function get_default_id() {
		static $id = null;
		if ( empty($id) ) {
			$id = $this->add_prefix($this->_id_default);
		}
		return $id;
	}
	
	/* Output */
	
	/**
	 * Output code in footer
	 */
	function client_output() {
		echo '<!-- X-THM -->';
		if ( !$this->has_parent() ) {
			return;
		}
		$parent = $this->get_parent();
		//Stop if not enabled
		if ( !$parent->is_enabled() ) {
			return;
		}
		echo '<!-- SLB-THM -->' . PHP_EOL;
		
		$client_out = array();
		
		/* Load theme */
		
		//Theme
		/**
		 * @var SLB_Theme
		 */
		$thm = $this->get_item();
		if ( empty($thm) || !$thm->is_valid() ) {
			return;
		}
		
		//Process theme ancestors
		$thms = array_reverse($thm->get_ancestors());
		$thms[] = $thm;
		
		//Build output for each theme
		foreach ( $thms as $thm ) {
			//Theme properties
			$thm_props = array(
				'id'			=> $thm->get_id(),
				'name'			=> $thm->get_name(),
				'parent'		=> $thm->get_parent(true)->get_id()
			);
			//Optional properties
			$uri = $thm->get_layout('uri');
			if ( !empty($uri) ) {
				$thm_props['layout_uri'] = $uri;
			}
			//Add theme to client
			$client_out[] = $this->util->build_script_element( $this->util->call_client_method('View.add_theme', array( sprintf("'%s'", $thm->get_id()), json_encode($thm_props) ), false), sprintf('add_theme_%s', $thm->get_id()) );
			
			//Load external files
			foreach ( array('styles' => 'build_stylesheet_element', 'scripts' => 'build_ext_script_element') as $key => $build ) {
				foreach ( $thm->{'get_' . $key}() as $handle => $props ) {
					$uri = $props[1];
					if ( !empty($uri) ) {
						$uri = $this->util->normalize_path(WP_PLUGIN_URL, $uri);
					}
					$client_out[] = $this->util->{$build}($uri);
				}
			}
		}
		
		//Output
		echo implode('', $client_out);
		
		echo PHP_EOL . '<!-- /SLB-THM -->' . PHP_EOL;
	}
	
	/* Options */
	
	/**
	 * Retrieve themes for use in option field
	 * @uses self::theme_default
	 * @return array Theme options
	 */
	public function get_field_values() {
		//Get themes
		$items = $this->get_items();
		$d = $this->get_default_id();
		//Pop out default theme
		if ( isset($items[$d]) ) {
			$itm_d = $items[$d];
			unset($items[$d]);
		}
		
		//Sort themes by name
		uasort($items, create_function('$a,$b', 'return strcmp($a->get_name(), $b->get_name());'));
		
		//Insert default theme at top of array
		if ( isset($itm_d) ) {
			$items = array( $d => $itm_d ) + $items;
		}
		
		//Build options
		foreach ( $items as $item ) {
			$items[$item->get_id()] = $item->get_name();
		}
		return $items;
	}
	
	public function get_item_selected() {
		
	}
}