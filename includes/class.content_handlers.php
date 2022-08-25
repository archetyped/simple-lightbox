<?php

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
		$this->util->add_action( 'init', $this->m( 'init_defaults' ), 5 );
		$this->util->add_action( 'footer', $this->m( 'client_output' ), 1, 0, false );
		$this->util->add_filter( 'footer_script', $this->m( 'client_output_script' ), $this->util->priority( 'client_footer_output' ), 1, false );
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
	public function add( $id, $props = array(), $priority = 10 ) {
		$this->clear_cache();
		if ( is_string( $id ) ) {
			// Initialize new handler
			$handler = new $this->item_type( $id, $props );
		} else {
			// Remap parameters
			$handler = func_get_arg( 0 );
			if ( func_num_args() === 2 ) {
				$priority = func_get_arg( 1 );
			}
		}
		if ( ! is_int( $priority ) ) {
			$priority = 10;
		}
		// Add to collection
		return parent::add( $handler, array( 'priority' => $priority ) );
	}

	/**
	 * Remove item
	 * @uses clear_cache()
	 * @see parent::remove()
	 * @return object Current instance
	 */
	public function remove( $item ) {
		$this->clear_cache();
		return parent::remove( $item );
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
	 * @param mixed $args Unused
	 * @return array Handlers
	 */
	public function get( $args = null ) {
		$items = $this->get_cache();
		if ( empty( $items ) ) {
			// Retrieve items
			$items = parent::get( array( 'orderby' => array( 'meta' => 'priority' ) ) );
			$this->update_cache( $items );
		}
		return $items;
	}

	/**
	 * Get matching handler for URI
	 * @param string $uri URI to find match for
	 * @return object Handler package (FALSE if no match found)
	 * Package members
	 * > handler (Content_Handler) Matching handler instance (Default: NULL)
	 * > props (array) Properties returned from matching handler (May be empty depending on handler)
	 */
	public function match( $uri ) {
		$ret = (object) array(
			'handler' => null,
			'props'   => array(),
		);
		foreach ( $this->get() as $handler ) {
			$props = $handler->match( $uri, $this );
			if ( ! ! $props ) {
				$ret->handler = $handler;
				// Add handler props
				if ( is_array( $props ) ) {
					$ret->props = $props;
				}
				// Save match
				$hid = $handler->get_id();
				if ( ! isset( $this->request_matches[ $hid ] ) ) {
					$this->request_matches[ $hid ] = $handler;
				}
				break;
			}
		}
		return $ret;
	}

	/* Cache */

	/**
	 * Retrieve cached items
	 * @uses get_cache_props()
	 * @uses wp_cache_get()
	 * @return array Cached items (Default: empty array)
	 */
	protected function get_cache() {
		$cprops = $this->get_cache_props();
		$items  = wp_cache_get( $cprops->key, $cprops->group );
		return ( is_array( $items ) ) ? $items : array();
	}

	/**
	 * Update cached items
	 * Cache is cleared if no items specified
	 * @uses get_cache_props()
	 * @uses wp_cache_get()
	 * @param array $data Item data to cache
	 */
	protected function update_cache( $data = null ) {
		$props = $this->get_cache_props();
		wp_cache_set( $props->key, $data, $props->group );
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
		if ( ! is_object( $this->cache_props ) ) {
			$this->cache_props = (object) array(
				'key'   => $this->hook_prefix . '_items',
				'group' => $this->get_prefix(),
			);
		}
		return $this->cache_props;
	}

	/* Handlers */

	/**
	 * Initialize default handlers
	 * @param SLB_Content_Handlers $handlers Handlers controller
	 */
	public function init_defaults( $handlers ) {
		$src_base = $this->util->get_file_url( 'content-handlers', true );
		$js_path  = 'js/';
		$js_path .= ( SLB_DEV ) ? 'dev' : 'prod';
		$defaults = array(
			'image' => array(
				'match'   => $this->m( 'match_image' ),
				'scripts' => array(
					array( 'base', "$src_base/image/$js_path/handler.image.js" ),
				),
			),
		);
		foreach ( $defaults as $id => $props ) {
			$handlers->add( $id, $props );
		}
	}

	/**
	 * Matches image URIs
	 * @param string $uri URI to match
	 * @return bool|array TRUE if URI is image (array is used if extra data needs to be sent)
	 */
	public function match_image( $uri, $handlers ) {
		// Basic matching
		$match = ( $this->util->has_file_extension( $uri, array( 'avif', 'jpg', 'jpeg', 'jpe', 'jfif', 'jif', 'gif', 'png', 'webp' ) ) ) ? true : false;

		// Filter result
		$extra = new stdClass();
		$match = $this->util->apply_filters( 'image_match', $match, $uri, $extra );

		// Handle extra data passed from filters
		// Currently only `uri` supported
		if ( $match && isset( $extra->uri ) && is_string( $extra->uri ) ) {
			$match = array( 'uri' => $extra->uri );
		}

		return $match;
	}

	/* Output */

	/**
	 * Build client output
	 * Load handler files in client
	 */
	public function client_output() {
		// Get handlers for current request
		foreach ( $this->request_matches as $handler ) {
			$handler->enqueue_scripts();
		}
	}

	/**
	 * Client output script
	 * @param array $commands Client script commands
	 * @return array Modified script commands
	 */
	public function client_output_script( $commands ) {
		$out  = array( '/* CHDL */' );
		$code = array();

		foreach ( $this->request_matches as $handler ) {
			// Attributes
			$attrs = $handler->get_attributes();
			// Styles
			$styles = $handler->get_styles( array( 'uri_format' => 'full' ) );
			if ( ! empty( $styles ) ) {
				$attrs['styles'] = array_values( $styles );
			}
			if ( empty( $attrs ) ) {
				continue;
			}
			// Setup client parameters
			$params = array(
				sprintf( "'%s'", $handler->get_id() ),
				wp_json_encode( $attrs ),
			);
			// Extend handler in client
			$code[] = $this->util->call_client_method( 'View.extend_content_handler', $params, false );
		}
		if ( ! empty( $code ) ) {
			$out[]      = implode( '', $code );
			$commands[] = implode( PHP_EOL, $out );
		}
		return $commands;
	}
}
