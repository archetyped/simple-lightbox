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
	 * @param string $id Handler ID
	 * @param array $props Handler properties
	 * @return object Added handler
	 */
	public function add($id, $props = array(), $priority = 10) {
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
		parent::add($handler, array('priority' => $priority));
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
	
	/* Handlers */
	
	/**
	 * Initialize default handlers
	 * @param SLB_Content_Handlers $controller Handlers controller
	 */
	public function init_defaults($controller) {
		$handlers = array (
			'image'		=> array (
				'match'			=> $this->m('match_image'),
				'client_script'	=> $this->util->get_plugin_file_path('content_handlers/image/handler.image.js'),
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
		$out = array();
		$out[] = '<!-- SLB-HDL -->' . PHP_EOL;
		//Load matched handlers
		foreach ( $this->request_matches as $handler ) {
			//Define
			$out[] = $this->util->build_script_element( $this->util->call_client_method('View.add_content_handler',  $handler->get_id()), sprintf('add_handler_%s', $handler->get_id()) );
			//Load external file
			$out[] = $this->util->build_ext_script_element( $handler->get_client_script('uri') );
		}
		$out[] = '<!-- /SLB-HDL -->' . PHP_EOL;
		echo implode('', $out);
	}
}