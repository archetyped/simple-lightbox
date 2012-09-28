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
	 * @var string Raw template
	 */
	private $_template_data = '';
	
	/**
	 * @var string Template URI (Relative or absolute path)
	 */
	private $_template_uri = '';
	
	/**
	 * @var string Stylesheet URI (Relative or absolute path)
	 */
	private $_stylesheet_uri = '';
	
	/**
	 * @var string Client attributes URI (Relative or absolute path)
	 */
	private $_client_attributes_uri = '';
	
	/**
	 * @var string Client attributes data
	 */
	private $_client_attributes_data = '';
	
	/**
	 * @var string Class mode
	 * @see SLB_Base::$mode
	 */
	var $mode = 'object';
	
	/*-** Methods **-*/
	
	/**
	 * Constructor
	 */
	function __construct( $props = array() ) {
		parent::__construct();
		//Normalize properties
		if ( !is_array($props) ) {
			$props = array();
		}
		$defaults = array (
			'id'				=> '',
			'name'				=> '',
			'parent'			=> null,
			'template_uri'		=> '',
			'template_data'		=> '',
			'stylesheet_uri'	=> '',
			'client_attributes'	=> '',
		);
		
		$props = array_merge($defaults, $props);
		
		extract($props);
		
		$this->set_id($id)
			 ->set_name($name)
			 ->set_parent($parent)
			 ->set_template($template_data)
			 ->set_template_uri($template_uri)
			 ->set_stylesheet($stylesheet_uri)
			 ->set_client_attrs($client_attributes);
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
	public function set_id($id) {
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
	public function get_parent() {
		return $this->_parent;
	}
	
	/**
	 * Set theme's parent
	 * @param SLB_Theme $parent Parent theme instance
	 * @return SLB_Theme Current theme instance
	 */
	public function set_parent($parent) {
		if ( $parent instanceof $this ) {
			$this->_parent = $parent;
		}
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
	 * Get template data
	 * @return string Template
	 */
	public function get_template($format = true) {
		if ( !is_bool($format) ) {
			$format = true;
		}
		//Build template
		$tpl = null;
		if ( empty($this->_template_data) && !empty($this->_template_uri) ) {
			//Retrieve template from URI
			$tpl = file_get_contents($this->util->normalize_path($this->util->get_path_base(), $this->_template_uri));
			
			//Save template data
			if ( !empty($tpl) && is_string($tpl) ) {
				$this->_template_data = $tpl;
			}
		}
		//Return
		return ( $format ) ? $this->format_template( $this->_template_data ) : $this->_template_data;
	}
	
	/**
	 * Formats layout for usage in JS
	 * @param string $tpl Template data to format
	 * @return string Formatted template
	 */
	protected function format_template($tpl = '') {
		//Validate
		if ( !is_string($tpl) ) {
			$tpl = '';
		}
		if ( !empty($tpl) ) {
			//Remove line breaks
			$tpl = str_replace(array("\r\n", "\n", "\r", "\t"), '', $tpl);
			
			//Escape quotes
			$tpl = str_replace("'", "\'", $tpl);
		}
		//Return
		return "'" . $tpl . "'";
	}
	
	/**
	 * Set template data
	 * @param string $data Template data (URI or Raw template)
	 * @return SLB_Theme Current theme instance
	 */
	public function set_template($data) {
		if ( is_string($data) ) {
			$this->_template_data = trim($data);
		}
		return $this;
	}
	
	/**
	 * Set template URI
	 * @param string $uri Template URI
	 * @return SLB_Theme Current theme instance
	 */
	public function set_template_uri($uri) {
		if ( is_string($uri) ) {
			$this->_template_uri = trim($uri);
		}
		return $this;
	}
	
	/**
	 * Get stylesheet URI
	 * @return string Fully-formed stylesheet URI
	 */
	public function get_stylesheet($full = true) {
		if ( !is_bool($full) ) {
			$full = true;
		}
		$ret = $this->_stylesheet_uri;
		if ( !empty($ret) && $full ) {
			//Build full URI
			$ret = $this->util->get_file_url($ret);
		}
		return $ret;
	}
	
	/**
	 * Set stylesheet URI
	 * @param string $id URI to stylesheet file
	 * @return SLB_Theme Current theme instance
	 */
	public function set_stylesheet($uri) {
		if ( is_string($uri) ) {
			$this->_stylesheet_uri = trim($uri);
		}
		return $this;
	}
	
	/**
	 * Get client attributes
	 * @return string Client attributes
	 */
	public function get_client_attrs() {
		$data = null;
		if ( empty($this->_client_attributes_data) && !empty($this->_client_attributes_uri) && ( $file = $this->util->normalize_path($this->util->get_path_base(), $this->_client_attributes_uri) ) && file_exists($file) ) {
			//Retrieve file contents
			$data = file_get_contents($file);
			//Format
			$data = trim($data, " ;,\n\r\t\0\x0B");
			//Validate first/last characters
			if ( !empty($data) && substr($data, 0, 1) == '{' && substr($data, -1) == '}' ) {
				//Save contents
				$this->_client_attributes_data = $data;
			}
		}
		//Return
		return $this->_client_attributes_data;
	}
	
	/**
	 * Set client attributes URI
	 * @param string $uri URI to file containing client attributes
	 * @return SLB_Theme Current theme instance
	 */
	public function set_client_attrs($uri) {
		if ( is_string($uri) ) {
			$uri = trim($uri);
			//Clear cached data when URI changed
			if ( $uri != $this->_client_attributes_uri ) {
				$this->_client_attributes_data = '';
			}
			$this->_client_attributes_uri = $uri;
		}
		return $this;
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
		
	/**
	 * @var string Default item ID (namespaced upon init)
	 */
	public $id_default = 'default';
	
	/* Methods */

	function __construct() {
		parent::__construct();
		$this->add_prefix_ref($this->id_default);
		$this->init();
	}
	
	/* Initialization */
	
	function register_hooks() {
		$this->util->add_action('init_themes', $this->m('init_defaults'));
	}
	
	/**
	 * Add default themes
	 * @uses register_theme() to register the theme(s)
	 */
	function init_defaults() {
		$path_base = 'themes/default/';
		$props = array (
			'id'				=> $this->id_default,
			'name'				=> 'Default',
			'template_uri'		=> $path_base . 'layout.html',
			'stylesheet_uri'	=> $path_base . 'style.css',
			'client_attributes'	=> $path_base . 'client.js',
		);
		$this->add_item($props);

		//Testing: Additional themes
		$props_black = array_merge($props, array (
			'id' 				=> $this->add_prefix('black'),
			'name'				=> 'Black',
			'stylesheet_uri'	=> 'themes/black/style.css',
			'parent'			=> $props['id'],
		)); 
		$this->add_item($props_black);
	}
		
	/**
	 * Add theme to collection
	 * @param mixed $data Theme data
	 * > array - Theme properties
	 * > SLB_Theme - Theme instance
	 * @return SLB_Theme Theme instance
	 */
	public function add_item($data) {
		global $dbg;
		if ( !( $data instanceof SLB_Theme ) ) {
			//Validate
			if ( is_array($data) ) {
				//Theme parent
				if ( isset($data['parent']) && $this->has_item($data['parent']) ) {
					//Get parent instance
					$data['parent'] = $this->get_item($data['parent']);
				} else {
					//Clear invalid parent
					unset($data['parent']);
				}
			}
			//Create new theme instance
			$data = new SLB_Theme($data);
		}
		if ( ( $id = $data->get_id() ) && !empty($id) ) {
			$this->_items[$id] = $data;
		}
		return $data;
	}
	
	/**
	 * Get all items in collection
	 * @return array Items
	 */
	public function get_items() {
		if ( !$this->_items_init ) {
			$this->_items_init = true;
			$this->util->do_action('init_themes');
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
	public function get_item($id) {
		$item = null;
		if ( $this->has_item($id) ) {
			$items = $this->get_items();
			$item = $items[$id];
		} else {
			$item = new SLB_Theme(array('id' => $id));
		}
		return $item; 
	}
	
	
	
}