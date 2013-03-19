<?php
require_once 'class.collection_controller.php';
require_once 'class.template_tag.php';

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
		
		add_action('wp_footer', $this->m('client_output'), 11);
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
		if ( is_string($id) ) {
			//Initialize new handler
			$tag = new $this->item_type($id, $props);
		}
		//Add to collection
		return parent::add($tag);
	}
	
	/* Defaults */
	
	/**
	 * Initialize default template tags
	 * @param SLB_Template_Tags $tags Tags controller
	 */
	public function init_defaults($tags) {
		$defaults = array (
			'item'		=> array (
				'client_script'	=> $this->util->get_plugin_file_path('template-tags/item/tag.item.js'),
			),
			'ui'		=> array (
				'client_script'	=> $this->util->get_plugin_file_path('template-tags/ui/tag.ui.js'),
			),
		);
		foreach ( $defaults as $id => $props ) {
			$tags->add($id, $props);
		}
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
		$out[] = '<!-- SLB-TPTG -->' . PHP_EOL;
		$code = array();
		//Load matched handlers
		foreach ( $this->get() as $id => $tag ) {
			//Define
			$params = array(
				sprintf("'%s'", $id),
				sprintf("'%s'", $tag->get_client_script('uri')),
			);
			$code[] = $this->util->call_client_method('View.add_template_tag_handler',  $params, false);
		}
		$out[] = $this->util->build_script_element(implode('', $code), 'add_template_tags');
		$out[] = '<!-- /SLB-TPTG -->' . PHP_EOL;
		echo implode('', $out);
	}
}