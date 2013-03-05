<?php
require_once 'class.base_collection.php';
require_once 'class.content_handler.php';

/**
 * Content Handler Collection
 * @package Simple Lightbox
 * @subpackage Content
 * @author Archetyped
 */
class SLB_Content_Handlers extends SLB_Base_Collection {
	/* Configuration */
	
	protected $item_type = SLB_Content_Type;
	
	public $hook_prefix = 'content_handler';
	
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
}