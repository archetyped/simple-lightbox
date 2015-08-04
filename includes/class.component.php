<?php

/**
 * Component
 * @package Simple Lightbox
 * @subpackage Base
 * @author Archetyped
 */
class SLB_Component extends SLB_Base_Object {
	/* Properties */
	
	/**
	 * Pretty name
	 * @var string
	 */
	protected $name = '';
	
	protected $props_required = array();
	
	private $props_required_base = array('id');
	
	/* Get/Set */
	
	/**
	 * Set name
	 * @param string $name Name
	 * @return Current instance
	 */
	public function set_name($name) {
		if ( is_string($name) ) {
			$name = trim($name);
			if ( !empty($name) ) {
				$this->name = $name;
			}
		}
		return $this;
	}
	
	public function get_name() {
		return $this->name;
	}
	
	public function set_scripts($scripts) {
		$this->add_files('scripts', $scripts);
	}
	
	public function set_styles($styles) {
		$this->add_files('styles', $styles);
	}
	
	/* Assets */
	
	/**
	 * Get formatted handle for file
	 * @param string $base_handle Base handle to format
	 * @return string Formatted handle
	 */
	public function get_handle($base_handle) {
		return $this->add_prefix( array('asset', $this->get_id(), $base_handle), '-');
	}
	
	/**
	 * Enqueue files in client
	 * @param string $type (optional) Type of file to load (singular) (Default: All client file types)
	 */
	public function enqueue_client_files($type = null) {
		if ( empty($type) ) {
			$type = array ( 'script', 'style');
		}
		if ( !is_array($type) ) {
			$type = array ( $type );
		}
		foreach ( $type as $t ) {
			$m = (object) array (
				'get'		=> $this->m('get_' . $t . 's'),
				'enqueue'	=> 'wp_enqueue_' . $t,
			);
			$v = $this->util->get_plugin_version();
			$files = call_user_func($m->get);
			$param_final = ( 'script' == $t ) ? true : 'all';
			foreach ( $files as $f ) {
				$f = (object) $f;
				// Format handle
				$handle = $this->get_handle($f->handle);
				
				// Format dependencies
				$deps = array();
				foreach ( $f->deps as $dep ) {
					if ( $this->util->has_wrapper($dep) ) {
						$dep = $this->get_handle( $this->util->remove_wrapper($dep) );
					}
					$deps[] = $dep;
				}
				call_user_func($m->enqueue, $handle, $f->uri, $deps, $v, $param_final);
			}
			unset($files, $f, $param_final, $handle, $deps, $dep);
		}
	}
	
	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts() {
		$this->enqueue_client_files('script');
	}

	/**
	 * Enqueue styles
	 */
	public function enqueue_styles() {
		$this->enqueue_client_files('style');
	}
	
	/* Helpers */
	
	/**
	 * Validate instance
	 * @see `Base_Object::is_valid()`
	 * @return bool Valid (TRUE) / Invalid (FALSE)
	 */
	public function is_valid() {
		$ret = parent::is_valid();
		if ( $ret ) {
			// Check required component properties
			$props = array_merge($this->props_required_base, $this->props_required);
			foreach ( $props as $prop ) {
				if ( !isset($this->{$prop}) || empty($this->{$prop}) ) {
					$ret = false;
					break;
				}
			}
		}
		return $ret;
	}
}