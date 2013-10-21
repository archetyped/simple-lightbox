<?php

/**
 * Content Handler Collection
 * @package Simple Lightbox
 * @subpackage Content Handler
 * @author Archetyped
 */
class SLB_Template_Tags extends SLB_Collection_Controller {
	/* Configuration */
	
	protected $item_type = 'SLB_Template_Tag';
	
	public $hook_prefix = 'template_tags';
	
	//Use tag ID as key
	protected $key_prop = 'get_id';
	
	//Call $key_prop is a method to be called
	protected $key_call = true;
	
	/* Properties */
	
	/**
	 * Cache properties (key, group)
	 * @var object
	 */
	protected $cache_props = null;
	
	/* Initialization */
	
	protected function _hooks() {
		parent::_hooks();
		$this->util->add_action('init', $this->m('init_defaults'));
		$this->util->add_action('footer', $this->m('client_output'), 1, 0, false);
	}
	
	/* Collection Management */
	
	/**
	 * Add template tag
	 * Accepts properties to create new template tag OR previously-initialized tag instance
	 * @see parent::add()
	 * @param string $id Tag ID
	 * @param array $props Tag properties
	 * @return object Current instance
	 */
	public function add($id, $props = array()) {
		$o = ( is_string($id) ) ? new $this->item_type($id, $props) : $id;
		//Add to collection
		return parent::add($o);
	}
	
	/* Defaults */
	
	/**
	 * Initialize default template tags
	 * @param SLB_Template_Tags $tags Tags controller
	 */
	public function init_defaults($tags) {
		$src_base = $this->util->get_file_url('template-tags', true);
		$defaults = array (
			'item'		=> array (
				'scripts'		=> array (
					array ( 'base', $src_base . '/item/tag.item.js' ),
				)
			),
			'ui'		=> array (
				'scripts'		=> array (
					array ( 'base', $src_base . '/ui/tag.ui.js' ),
				)
			),
		);
		foreach ( $defaults as $id => $props ) {
			$tags->add($id, $props);
		}
	}
	
	/* Output */
	
	/**
	 * Build client output
	 */
	public function client_output() {
		//Load matched handlers
		foreach ( $this->get() as $tag ) {
			$tag->enqueue_client_files();
		}
	}
}