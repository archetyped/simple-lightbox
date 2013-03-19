<?php
require_once 'class.collection_controller.php';
require_once 'class.content_handler.php';

/**
 * Content Handler Collection
 * @package Simple Lightbox
 * @subpackage Content Handler
 * @author Archetyped
 */
class SLB_Content_Handlers extends SLB_Collection_Controller {
	/* Configuration */
	
	protected $item_type = 'SLB_Content_Handler';
	
	public $hook_prefix = 'content_handlers';
	
	protected $key_prop = 'get_id';
	
	protected $key_call = true;
	
	/* Properties */
	
	protected $request_matches = array();
	
	/**
	 * Cache properties (key, group)
	 * @var object
	 */
	protected $cache_props = null;
	
	/* Initialization */
	
	protected function _hooks() {
		parent::_hooks();
		$this->util->add_action('init', $this->m('init_defaults'));
		
		add_action('wp_footer', $this->m('client_output'), 11);
	}
	
	/* Collection Management */
	
	/**
	 * Add content type handler
	 * Accepts properties to create new handler OR previously-initialized handler instance
	 * @uses clear_cache()
	 * @see parent::add()
	 * @param string $id Handler ID
	 * @param array $props Handler properties
	 * @return object Current instance
	 */
	public function add($id, $props = array(), $priority = 10) {
		$this->clear_cache();
		if ( is_string($id) ) {
			//Initialize new handler
			$handler = new $this->item_type($id, $props);
		} else {
			//Remap parameters
			$handler = func_get_arg(0);
			if ( func_num_args() == 2 ) {
				$priority = func_get_arg(1);
			}
		}
		if ( !is_int($priority) ) {
			$priority = 10;
		}
		//Add to collection
		return parent::add($handler, array('priority' => $priority));
	}
	
	/**
	 * Remove item
	 * @uses clear_cache()
	 * @see parent::remove()
	 * @return object Current instance
	 */
	public function remove($item) {
		$this->clear_cache();
		return parent::remove($item);
	}
	
	/**
	 * Clear collection
	 * @uses clear_cache()
	 * @see parent::clear()
	 * @return object Current instance
	 */
	public function clear() {
		$this->clear_cache();
		return parent::clear();
	}
	
	/**
	 * Retrieves handlers sorted by priority
	 * @see parent::get()
	 * @uses get_cache()
	 * @return array Handlers
	 */
	public function get() {
		$items = $this->get_cache();
		if ( empty($items) ) {
			//Retrieve items
			$items = parent::get( array( 'orderby' => array('meta' => 'priority') ) );
			$this->update_cache($items);
		}
		return $items;
	}
	
	/**
	 * Get matching handler for URI
	 * @param string $uri URI to find match for
	 * @return SLB_Content_Handler Matching handler (NULL if no handler matched)
	 */
	public function match($uri) {
		foreach ( $this->get() as $handler ) {
			if ( $handler->match($uri) ) {
				//Save match
				$hid = $handler->get_id();
				if ( !isset($this->request_matches[$hid]) ) {
					$this->request_matches[$hid] = $handler;
				}
				return $handler;
			}
		}
		return null;
	}
	
	/* Cache */
	
	/**
	 * Retrieve cached items
	 * @uses get_cache_props()
	 * @uses wp_cache_get()
	 * @return array Cached items (Default: empty array)
	 */
	protected function get_cache() {
		$cprops= $this->get_cache_props();
		$items = wp_cache_get($cprops->key, $cprops->group);
		return ( is_array($items) ) ? $items : array();
	}
	
	/**
	 * Update cached items
	 * Cache is cleared if no items specified
	 * @uses get_cache_props()
	 * @uses wp_cache_get()
	 * @param array $data Item data to cache
	 */
	protected function update_cache($data = null) {
		$props = $this->get_cache_props();
		wp_cache_set($props->key, $data, $props->group);
	}
	
	/**
	 * Clear cache
	 * @uses update_cache()
	 */
	protected function clear_cache() {
		$this->update_cache();
	}
	
	/**
	 * Retrieve cache properites (key, group)
	 * @return object Cache properties
	 */
	protected function get_cache_props() {
		if ( !is_object($this->cache_props) ) {
			$this->cache_props = (object) array (
				'key' => $this->hook_prefix . '_items',
				'group' => $this->get_prefix(),
			);
		}
		return $this->cache_props; 
	}
	
	/* Handlers */
	
	/**
	 * Initialize default handlers
	 * @param SLB_Content_Handlers $controller Handlers controller
	 */
	public function init_defaults($controller) {
		$handlers = array (
			'image'		=> array (
				'match'			=> $this->m('match_image'),
				'client_script'	=> $this->util->get_plugin_file_path('content-handlers/image/handler.image.js'),
			),
		);
		foreach ( $handlers as $id => $props ) {
			$controller->add($id, $props);
		}
	}
	
	/**
	 * Matches image URIs
	 * @param string $uri URI to match
	 * @return bool TRUE if URI is image
	 */
	public function match_image($uri) {
		return ( $this->util->has_file_extension($uri, array('jpg', 'jpeg', 'jpe', 'jfif', 'jif', 'gif', 'png')) ) ? true : false;
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
		$id_fmt = 'add_handler_%s';
		$out = array();
		$out[] = '<!-- SLB-HDL -->' . PHP_EOL;
		$code = array();
		//Load matched handlers
		foreach ( $this->request_matches as $handler ) {
			//Define
			$params = array(
				sprintf("'%s'", $handler->get_id()),
				sprintf("'%s'", $handler->get_client_script('uri')),
			);
			$code[] = $this->util->call_client_method('View.add_content_handler',  $params, false);
		}
		$out[] = $this->util->build_script_element(implode('', $code), 'add_content_handlers');
		$out[] = '<!-- /SLB-HDL -->' . PHP_EOL;
		echo implode('', $out);
	}
}