<?php
require_once 'class.collection_controller.php';
require_once 'class.theme.php';

/**
 * Themes Collection
 * @package Simple Lightbox
 * @subpackage Themes
 * @author Archetyped
 */
class SLB_Themes extends SLB_Collection_Controller {
	/* Configuration */
	
	protected $item_type = 'SLB_Theme';
	
	public $hook_prefix = 'themes';
	
	protected $key_prop = 'get_id';
	
	protected $key_call = true;
	
	/* Properties */
	
	protected $id_default = null;
	
	/* Initialization */
	
	protected function _hooks() {
		parent::_hooks();
		//Register themes
		$this->util->add_action('init', $this->m('init_defaults'), 1);
		
		//Client output
		add_action('wp_footer', $this->m('client_output'), $this->util->priority('client_footer_output'));
	}
	
	protected function _options() {
		$opts = array (
			'items'	=> array (
				'theme_default'		=> array (
					'title'		=> __('Theme', 'simple-lightbox'),
					'default' 	=> $this->get_default_id(),
					'group' 	=> array('ui', 0),
					'parent' 	=> 'option_select',
					'options' 	=> $this->m('opt_get_field_values'),
					'in_client'	=> true
				),
			)
		);
		
		parent::_options($opts);
	}
	
	/**
	 * Add default themes
	 * @param SLB_Themes $themes Themes controller
	 */
	function init_defaults($themes) {
		$defaults = array (
			$this->get_default_id()		=> array (
				'name'			=> __('Default (Light)', 'simple-lightbox'),
				'layout'		=> $this->util->get_file_url('themes/default/layout.html'),
				'client_script'	=> $this->util->get_file_url('themes/default/client.js'),
				'client_style'	=> $this->util->get_file_url('themes/default/css/style.css'),
			),
			$this->add_prefix('black')	=> array (
				'name'			=> __('Default (Dark)', 'simple-lightbox'),
				'parent'		=> $this->get_default_id(),
				'client_style'	=> $this->util->get_file_url('themes/black/css/style.css'),
			),
		);
		
		foreach ( $defaults as $id => $props ) {
			$themes->add($id, $props);
		}
	}

	/* Collection management */
	
	/**
	 * Add theme
	 * Accepts properties to create new theme or previously-created theme instance
	 * @uses parent::add()
	 * @param string|object $id Theme ID (or Theme object)
	 * @param array $props Theme properties
	 * @return object Current instance
	 */
	public function add($id, $props = array()) {
		//Prepare parent
		if ( isset($props['parent']) && !($props['parent'] instanceof $this->item_type) ) {
			$pid = $props['parent'];
			$items = $this->get();
			if ( isset($items[$pid]) ) {
				$props['parent'] = $items[$pid];
			}
		}
		$o = ( is_string($id) ) ? new $this->item_type($id, $props) : $id;
		//Add to collection
		return parent::add($o);
	}

	/* Helpers */
	
	/**
	 * Retrieve default theme ID
	 * @uses `$id_default`
	 * @return string Default theme ID
	 */
	public function get_default_id() {
		if ( !$this->id_default ) {
			$this->id_default = $this->add_prefix('default');
		}
		return $this->id_default;
	}
	
	/**
	 * Retrieve currently-selected theme
	 * @return SLB_Theme Selected theme
	 */
	protected function get_selected() {
		//Get themes
		$thms = $this->get();
		//Retrieve currently-selected theme
		$id = $this->options->get_value('theme_default');
		if ( !isset($thms[$id]) ) {
			$id = $this->get_default_id();
		}
		return $thms[$id];
	}
	
	/* Output */
	
	/**
	 * Client output
	 */
	public function client_output() {
		//Stop if not enabled
		if ( !$this->has_parent() || !$this->get_parent()->is_enabled() ) {
			return;
		}
		
		//Theme
		/**
		 * @var SLB_Theme
		 */
		$thm = $this->get_selected();

		//Process theme ancestors
		$thms = array_reverse($thm->get_ancestors());
		$thms[] = $thm;
		
		$id_fmt = 'add_theme_%s';
		$out = array();
		$out[] = '<!-- SLB-THM -->' . PHP_EOL;
		$code = array();
		
		//Build output for each theme
		foreach ( $thms as $thm ) {
			//Setup client parameters
			$params = array(
				sprintf("'%s'", $thm->get_id()),
			);
			//Theme properties
			$thm_props = array(
				'name'			=> $thm->get_name(),
				'parent'		=> ( $thm->has_parent() ) ? $thm->get_parent()->get_id() : '',
			);
			/* Optional properties */
			//Layout
			$uri = $thm->get_layout('uri');
			if ( !empty($uri) ) {
				$thm_props['layout_uri'] = $uri;
			}
			//Script
			$script = $thm->get_client_script('uri');
			if ( !empty($script) ) {
				$thm_props['client_script'] = $script;
			}
			//Style
			$style = $thm->get_client_style('uri');
			if ( !empty($style) ) {
				$thm_props['client_style'] = $style;
			}
			//Add properties to parameters
			$params[] = json_encode($thm_props);
			
			//Add theme to client
			$code[] = $this->util->call_client_method('View.add_theme', $params, false);
		}

		$out[] = $this->util->build_script_element(implode('', $code), 'add_themes', true, true);
		$out[] = '<!-- /SLB-THM -->' . PHP_EOL;
		echo implode('', $out);
	}
	
	/* Options */
	
	/**
	 * Retrieve themes for use in option field
	 * @uses self::theme_default
	 * @return array Theme options
	 */
	public function opt_get_field_values() {
		//Get themes
		$items = $this->get();
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
}