<?php
require_once 'class.base.php';

/**
 * Theme collection management
 * @package Simple Lightbox
 * @subpackage Themes
 * @author Archetyped
 */
class SLB_Themes extends SLB_Base {
	/* Configuration */
	
	protected $mode = 'full';
	
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

	public function __construct($parent = null) {
		$this->set_parent($parent);
		parent::__construct();
	}
	
	/* Initialization */
	
	protected function _hooks() {
		parent::_hooks();
		//Register themes
		$this->util->add_action('init_themes', $this->m('init_defaults'), 1);
		
		//Client output
		add_action('wp_footer', $this->m('client_output'), 11);
	}
	
	protected function _options() {
		$opts = array (
			'items'	=> array (
				'theme_default'		=> array (
					'title'		=> __('Theme', 'simple-lightbox'),
					'default' 	=> $this->get_default_id(),
					'group' 	=> array('ui', 0),
					'parent' 	=> 'option_select',
					'options' 	=> $this->m('get_field_values'),
					'in_client'	=> true
				),
			)
		);
		
		parent::_options($opts);
	}
	
	/**
	 * Add default themes
	 * @uses register_theme() to register the theme(s)
	 */
	function init_defaults($themes) {
		$path_base = $this->util->get_plugin_file_path('themes/default', true);
		//Default
		$def = $this->add_item($this->get_default_id(), 'Default (Light)')
				 		->set_layout($path_base . 'layout.html')
				 		->add_style('main', $path_base . 'style.css')
				 		->add_script('main', $path_base . 'client.js');
		//Dark
		$path_base = $this->util->get_plugin_file_path('themes/black', true);
		$dark = $this->add_item($this->add_prefix('black'), 'Default (Dark)')
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
		//Stop if not enabled
		if ( !$this->has_parent() || !$this->get_parent()->is_enabled() ) {
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
				'name'			=> $thm->get_name(),
				'parent'		=> ( $thm->has_parent() ) ? $thm->get_parent()->get_id() : '',
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