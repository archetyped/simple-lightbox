<?php
require_once 'class.base_object.php';

/**
 * Theme
 * @package Simple Lightbox
 * @subpackage Themes
 * @author Archetyped
 */
class SLB_Theme extends SLB_Base_Object {
	/* Properties */
	
	/**
	 * Pretty name
	 * @var string
	 */
	protected $name = '';
	
	/**
	 * Layout file
	 * @var string
	 */
	protected $layout_path = '';
	
	/**
	 * Properties that can be inherited from parent
	 * @var array
	 */
	protected $uses_parent = array();
	
	/* Client */
	
	/**
	 * Set client script file
	 * @see Base_Object::add_script()
	 * @param string $src File path
	 * @param array $deps (optional) File dependencies
	 */
	public function set_client_script($src, $deps = array()) {
		if ( is_array($src) ) {
			list($src, $deps) = func_get_arg(0);
		}
		return $this->add_script('client', $src, $deps);
	}
	
	/**
	 * Retrieve client script
	 * @see Base_Object::get_script()
	 * @param string $format (optional) Data format of return value
	 * @return mixed Client script data (formatted according to $format parameter)
	 */
	public function get_client_script($format = null) {
		$s = $this->get_script('client');
		switch ( $format ) {
			case 'path':
				$ret = $this->util->normalize_path(WP_PLUGIN_DIR, $s['path']);
				break;
			case 'uri':
				$ret = $this->util->normalize_path(WP_PLUGIN_URL, $s['path']);
				break;
			case 'object':
				$ret = (object) $s;
				break;
			default:
				$ret = $s;
		}
		return $ret;
	}
}

/**
 * Theme instance
 * @package Simple Lightbox
 * @subpackage Themes
 * @author Archetyped
 */
class SLB_Theme extends SLB_Base {
	
	/**
	 * @var array Attached files
	 * > scripts	array JS scripts
	 * > styles		array Stylesheets
	 */
	private $_files = array(
		'scripts'	=> array(),
		'styles'	=> array()
	);
	
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
		return $this->name;
	}
	
	/**
	 * Set theme's name
	 * @param string $name Theme name
	 * @return SLB_Theme Current theme instance
	 */
	public function set_name($name) {
		if ( is_string($name) ) {
			$this->name = trim($name);
		}
		return $this;
	}
	
	/**
	 * Get theme parent
	 * @return SLB_Theme Parent theme instance
	 */
	public function get_parent($use_default = false) {
		$ret = $this->parent;
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
		$this->parent = ( $parent instanceof $this ) ? $parent : null;
		return $this;
	}

	/**
	 * Check if theme has a parent
	 * @return bool TRUE if theme has a parent, FALSE otherwise
	 */
	public function has_parent() {
		return ( $this->parent instanceof $this ) ? true : false;
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
		$this->layout_path = $src;
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
		$ret = ( is_string($this->layout_path) ) ? $this->layout_path : '';
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
			if ( !is_array($this->files[$type]) ) {
				$this->files[$type] = array();
			}
			//Add file to group
			$this->files[$type][$handle] = array($handle, $src, $deps); 
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
		$ret = $this->files;
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