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

	// Use tag ID as key
	protected $key_prop = 'get_id';

	// Call $key_prop is a method to be called
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
		$this->util->add_action( 'init', $this->m( 'init_defaults' ) );
		$this->util->add_action( 'footer', $this->m( 'client_output' ), 1, 0, false );
		$this->util->add_filter( 'footer_script', $this->m( 'client_output_script' ), $this->util->priority( 'client_footer_output' ), 1, false );
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
	public function add( $id, $props = array() ) {
		$o = ( is_string( $id ) ) ? new $this->item_type( $id, $props ) : $id;
		// Add to collection
		return parent::add( $o );
	}

	/* Defaults */

	/**
	 * Initialize default template tags
	 * @param SLB_Template_Tags $tags Tags controller
	 */
	public function init_defaults( $tags ) {
		$js_path  = 'js/';
		$js_path .= ( SLB_DEV ) ? 'dev' : 'prod';
		$src_base = $this->util->get_file_url( 'template-tags', true );
		$defaults = array(
			'item' => array(
				'scripts' => array(
					array( 'base', "$src_base/item/$js_path/tag.item.js" ),
				),
			),
			'ui'   => array(
				'scripts' => array(
					array( 'base', "$src_base/ui/$js_path/tag.ui.js" ),
				),
			),
		);
		foreach ( $defaults as $id => $props ) {
			$tags->add( $id, $props );
		}
	}

	/* Output */

	/**
	 * Build client output
	 */
	public function client_output() {
		// Load matched handlers
		foreach ( $this->get() as $tag ) {
			$tag->enqueue_scripts();
		}
	}

	/**
	 * Client output script
	 * @param array $commands Client script commands
	 * @return array Modified script commands
	 */
	public function client_output_script( $commands ) {
		$out  = array( '/* TPLT */' );
		$code = array();

		foreach ( $this->get() as $tag ) {
			$styles = $tag->get_styles( array( 'uri_format' => 'full' ) );
			if ( empty( $styles ) ) {
				continue;
			}
			// Setup client parameters
			$params   = array(
				sprintf( "'%s'", $tag->get_id() ),
			);
			$params[] = wp_json_encode( array( 'styles' => array_values( $styles ) ) );
			// Extend handler in client
			$code[] = $this->util->call_client_method( 'View.extend_template_tag_handler', $params, false );
		}
		if ( ! empty( $code ) ) {
			$out[]      = implode( '', $code );
			$commands[] = implode( PHP_EOL, $out );
		}
		return $commands;
	}
}
