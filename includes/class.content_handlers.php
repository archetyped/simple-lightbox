<?php
require_once 'class.collection_controller.php';
require_once 'class.content_handler.php';

/**
 * Content Handler Collection
 * @package Simple Lightbox
 * @subpackage Content
 * @author Archetyped
 */
class SLB_Content_Handlers extends SLB_Collection_Controller {
	/* Configuration */
	
	protected $item_type = 'SLB_Content_Handler';
	
	public $hook_prefix = 'content_handlers';
	
	/* Initialization */
	
	protected function _hooks() {
		parent::_hooks();
		$this->util->add_action('init', $this->m('init_defaults'));
	}
	
	/* Collection Management */
	
	/**
	 * Add content type handler
	 * Accepts properties to create new handler OR previously-initialized handler instance
	 * @param string $id Handler ID
	 * @param array $props (optional) Handler properties
	 */
	public function add($id, $props = array()) {
		$handler = ( is_string($id) ) ? new $this->item_type($id, $props) : $id;
		if ( $handler instanceof $this->item_type ) {
			//Add handler to collection
			parent::add($handler);
		}
	}
	
	/**
	 * Get matching handler for URI
	 * @param string $uri URI to find match for
	 * @return SLB_Content_Handler Matching handler (NULL if no handler matched)
	 */
	public function get_match($uri) {
		foreach ( $this->get() as $handler ) {
			if ( $handler->match($uri) ) {
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
				'client_script'	=> $this->util->get_plugin_file_path('client/js/handler.image.js'),
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
}