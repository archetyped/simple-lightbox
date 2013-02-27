<?php
require_once 'class.base.php';

/**
 * Base Object
 * @package Simple Lightbox
 * @subpackage Base
 * @author Archetyped
 */
class SLB_Base_Object extends SLB_Base {
	/* Configuration */
	
	/**
	 * @var string
	 * @see Base::$mode
	 */
	protected $mode = 'object';
	
	/*-** Properties **-*/
	
	/**
	 * Unique ID
	 * @var string
	 */
	protected $id = '';
	
	/**
	 * Parent object
	 * @var Base_Object
	 */
	protected $parent = null;
	
	/**
	 * Attached files
	 * @var array
	 * > scripts	array JS scripts
	 * > styles		array Stylesheets
	 */
	protected $files = array(
		'scripts'	=> array(),
		'styles'	=> array()
	);
	
	/**
	 * Properties that can be inherited from parent
	 * @var array
	 */
	protected $parent_props = array();
	
	/*-** Methods **-*/
	
	/**
	 * Constructor
	 */
	public function __construct($id) {
		parent::__construct();
		$this->set_id($id);
	}
	
	/**
	 * Checks if object is valid
	 * To be overriden by child classes
	 */
	public function is_valid() {
		return true;
	}
	
	/*-** Getters/Setters **-*/
		
	/**
	 * Get ID
	 * @return string ID
	 */
	public function get_id() {
		return $this->id;
	}
	
	/**
	 * Set ID
	 * @param string $id ID
	 * @return object Current instance
	 */
	public function set_id($id) {
		$id = ( is_string($id) ) ? trim($id) : '';
		if ( !empty($id) ) {
			$this->id = $id;
		}
		return $this;
	}
	
	/**
	 * Get parent
	 * @return object|null Parent
	 */
	public function get_parent() {
		return $this->parent;
	}
	
	/**
	 * Set parent
	 * @param object $parent Parent object
	 * @return object Current instance
	 */
	public function set_parent($parent) {
		$this->parent = ( $parent instanceof $this ) ? $parent : null;
		return $this;
	}

	/**
	 * Check if parent is set
	 * @return bool TRUE if parent is set
	 */
	public function has_parent() {
		return ( is_null($this->parent) ) ? false : true;
	}
	
	/**
	 * Retrieve all ancestors
	 * @return array Ancestors
	 */
	public function get_ancestors() {
		$ret = array();
		$curr = $this;
		while ( $curr->has_parent() ) {
			//Add ancestor
			$ret[] = $par = $curr->get_parent();
			//Get next ancestor
			$curr = $par;
		}
		return $ret;
	}
	
	/* Files */
	
	/**
	 * Add file
	 * @param string $type Group to add file to
	 * @param string $handle Name for resource
	 * @param string $src Path to the file from WP's plugins directory. Example: 'plugin-name/css/style.css'
	 * @return object Current instance
	 */
	protected function add_file($type, $handle, $src, $deps = array()) {
		if ( !is_string($type) && !is_string($handle) && !is_string($src) ) {
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
	protected function get_files($type = null) {
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
	protected function get_file($type, $handle) {
		$files = $this->get_files($type);
		return ( is_string($type) && isset($files[$handle]) ) ? $files[$handle] : null;
	}
	
	/**
	 * Add stylesheet
	 * @param string $handle Name of the stylesheet
	 * @param string $src Path to stylesheet from WP's plugins directory. Example: 'plugin-name/css/style.css'
	 * @return object Current instance
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
	 * @param string $src Path to script from WP's plugins directory. Example: 'plugin-name/js/client.js'
	 * @return object Current instance
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