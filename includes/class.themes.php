<?php

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
		$this->util->add_action('footer', $this->m('client_output'), 1, 0, false);
		$this->util->add_filter('footer_script', $this->m('client_output_script'), $this->util->priority('client_footer_output'), 1, false);
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
		$js_path = 'js/';
		$js_path .= ( SLB_DEV ) ? 'dev' : 'prod';
		$scheme = is_ssl() ? 'https' : 'http';
		$baseline = $this->add_prefix('baseline');
		$src_base = $this->util->get_file_url('themes', true);
		$defaults = array (
			$baseline					=> array (
				'name'			=> __('Baseline', 'simple-lightbox'),
				'public'		=> false,
				'layout'		=> "$src_base/baseline/layout.html",
				'scripts'		=> array (
					array ( 'base', $src_base . "/baseline/$js_path/client.js" ),
				),
				'styles'		=> array (
					array ( 'base', "$src_base/baseline/css/style.css" ),
				),
			),
			$this->get_default_id()		=> array (
				'name'			=> __('Default (Light)', 'simple-lightbox'),
				'parent'		=> $baseline,
				'scripts'		=> array (
					array ( 'base', $src_base . "/default/$js_path/client.js" ),
				),
				'styles'		=> array (
					array ( 'font', "$scheme://fonts.googleapis.com/css?family=Yanone+Kaffeesatz" ),
					array ( 'base', "$src_base/default/css/style.css" ),
				),
			),
			$this->add_prefix('black')	=> array (
				'name'			=> __('Default (Dark)', 'simple-lightbox'),
				'parent'		=> $this->get_default_id(),
				'styles'		=> array (
					array ( 'base', "$src_base/black/css/style.css" )
				)
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
			$items = $this->get(array('include_private' => true));
			if ( isset($items[$pid]) ) {
				$props['parent'] = $items[$pid];
			}
		}
		$o = ( is_string($id) ) ? new $this->item_type($id, $props) : $id;
		//Add to collection
		return parent::add($o);
	}
	
	/**
	 * Get themes
	 * @param array $args (optional) Arguments
	 * @return array Themes
	 */
	public function get($args = null) {
		//Normalize arguments
		$args_default = array(
			'include_public'	=> true,
			'include_private'	=> false,
		);
		$r = wp_parse_args($args, $args_default);
		$r['include_public'] = !!$r['include_public'];
		$r['include_private'] = !!$r['include_private'];
		
		$items = parent::get($args);
		
		if ( empty($items) )
			return $items;
		
		/* Process custom arguments */

		//Filter
		$items_exclude = array();
		//Identify excluded themes
		$filter_props = array('include_public' => true, 'include_private' => false);
		foreach ( $filter_props as $filter_prop => $filter_value ) {
			if ( !$r[ $filter_prop ] ) {
				foreach ( $items as $id => $item ) {
					if ( $item->get_public() == $filter_value ) {
						$items_exclude[] = $id;
					}
				}
			}
		}
		//Filter themes from collection
		$items = array_diff_key($items, array_fill_keys($items_exclude, null));
		return $items;
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
	 * Build client output
	 */
	public function client_output() {
		//Process active theme
		$thm = $this->get_selected();
		
		//Get theme ancestors
		$thms = $thm->get_ancestors(true);
		$thms[] = $thm;
		
		foreach ( $thms as $thm ) {
			//Load files
			$thm->enqueue_scripts();
		}
	}
	
	/**
	 * Client output script
	 * 
	 * @param array $commands Client script commands
	 * @return array Modified script commands
	 */
	public function client_output_script($commands) {
		//Theme
		$thm = $this->get_selected();

		//Process theme ancestors
		$thms = $thm->get_ancestors(true);
		$thms[] = $thm;
		
		$out = array('/* THM */');
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
				'styles'		=> array_values($thm->get_styles(array('uri_format'=>'full'))),
			);
			/* Optional properties */
			//Layout
			$layout = $thm->get_layout('contents');
			if ( !empty($layout) ) {
				//Format
				$layout = str_replace(array("\n", "\r", "\t"), '', $layout);
				//Save
				$thm_props['layout_raw'] = $layout;
			}
			//Add properties to parameters
			$params[] = json_encode($thm_props);
			
			//Add theme to client
			$code[] = $this->util->call_client_method('View.extend_theme', $params, false);
		}

		if ( !empty($code) ) {
			$out[] = implode('', $code);
			$commands[] = implode(PHP_EOL, $out);
		}
		return $commands;
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