<?php

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
	 * @param string $id Unique ID for content type
	 * @param array $props (optional) Type properties (optional because props can be set post-init)
	 */
	public function __construct($id, $props = null) {
		parent::__construct();
		$this
			->set_id($id)
			->set_props($props);
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
	 * Set type properties
	 * @param array $props Type properties to set
	 */
	protected function set_props($props) {
		if ( is_array($props) && !empty($props) ) {
			foreach ( $props as $key => $val ) {
				//Check for setter method
				$m = 'set_' . $key;
				if ( method_exists($this, $m) ) {
					$this->{$m}($val);
				}
			}
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
	 * @param string $src File URI
	 * @return object Current instance
	 */
	protected function add_file($type, $handle, $src, $deps = array()) {
		if ( is_string($type) && is_string($handle) && is_string($src) ) {
			//Validate dependencies
			if ( !is_array($deps) ) {
				$deps = array();
			}
			//Init file group
			if ( !isset($this->files[$type]) || !is_array($this->files[$type]) ) {
				$this->files[$type] = array();
			}
			//Add file to group
			$this->files[$type][$handle] = array('handle' => $handle, 'uri' => $src, 'deps' => $deps); 
		}
		return $this;
	}
	
	/**
	 * Add multiple files
	 * @param string $type Group to add files to
	 * @param array $files Files to add
	 * @see add_file() for file parameters
	 * @return object Current instance
	 */
	protected function add_files($type, $files) {
		if ( !is_array($files) || empty($files) )
			return false;
		$m = $this->m('add_file');
		foreach ( $files as $file ) {
			if ( !is_array($file) || empty($file) ) {
				continue;
			}
			array_unshift($file, $type);
			call_user_func_array($m, $file);
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
	 * @param string $format (optional) Format of return value (Default: array)
	 * @return array|null File properties (Default: NULL)
	 */
	protected function get_file($type, $handle, $format = null) {
		//Get files
		$files = $this->get_files($type);
		//Get specified file
		$ret = ( is_string($type) && isset($files[$handle]) ) ? $files[$handle] : null;
		//Format return value
		if ( !empty($ret) && !!$format ) {
			switch ( $format ) {
				case 'uri':
					$ret = $ret['uri'];
					//Normalize URI
					if ( !$this->util->is_uri($ret) ) {
						$ret = $this->util->normalize_path(site_url(), $ret);
					}
					break;
				case 'path':
					$ret = $ret['uri'];
					//Normalize path
					if ( !$this->util->is_uri($ret) ) {
						$ret = $this->util->get_relative_path($ret);
						$ret = $this->util->normalize_path(ABSPATH, $ret);
					}
					break;
				case 'object':
					$ret = (object) $ret;
					break;
				case 'contents':
					$ret = $ret['uri'];
					if ( !$this->util->is_uri($ret) ) {
						$ret = $this->util->normalize_path(site_url(), $ret);
					}
					$get = wp_safe_remote_get($ret);
					$ret = ( !is_wp_error($get) && 200 == $get['response']['code'] ) ? $get['body'] : '';
					break;
			}
		}
		return $ret;
	}
	
	/**
	 * Add stylesheet
	 * @param string $handle Name of the stylesheet
	 * @param string $src Stylesheet URI
	 * @return object Current instance
	 */
	public function add_style($handle, $src, $deps = array()) {
		return $this->add_file('styles', $handle, $src, $deps);
	}
	
	/**
	 * Retrieve stylesheet files
	 * @return array Stylesheet files
	 */
	public function get_styles($opts = null) {
		$files = $this->get_files('styles');
		if ( is_array($opts) ) {
			$opts = (object) $opts;
		}
		if ( is_object($opts) && !empty($opts) ) {
			//Parse options
			//URI Format
			if ( isset($opts->uri_format) ) {
				foreach ( $files as $hdl => $props ) {
					switch ( $opts->uri_format ) {
						case 'full':
							if ( !$this->util->is_uri($props['uri']) ) {
								$files[$hdl]['uri'] = $this->util->normalize_path(site_url(), $props['uri']);
							}
							break;
					}
				}
			}
		}
		return $files;
	}
	
	/**
	 * Retrieve stylesheet file
	 * @param string $handle Name of stylesheet
	 * @param string $format (optional) Format of return value (@see `get_file()`)
	 * @return array|null File properties (Default: NULL)
	 */
	public function get_style($handle, $format = null) {
		return $this->get_file('styles', $handle, $format);
	}
	
	/**
	 * Add script
	 * @param string $handle Name of the script
	 * @param string $src Script URI
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
	 * @param string $format (optional) Format of return value (@see `get_file()`)
	 * @return array|null File properties (Default: NULL)
	 */
	public function get_script($handle, $format = null) {
		return $this->get_file('scripts', $handle, $format);
	}
	
}