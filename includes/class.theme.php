<?php

/**
 * Theme
 * @package Simple Lightbox
 * @subpackage Themes
 * @author Archetyped
 */
class SLB_Theme extends SLB_Component {
	/* Properties */
	
	protected $props_required = array('name');
	
	/**
	 * Public flag
	 * @var bool
	 */
	protected $public = true;
	
	/* Get/Set */
	
	/**
	 * Retrieve theme's ancestors
	 * @return array Theme's ancestors (sorted by nearest to most distant ancestor)
	 */
	public function get_ancestors() {
		$ret = array();
		/**
		 * @var SLB_Theme
		 */
		$thm = $this;
		while ( $thm->has_parent() ) {
			$par = $thm->get_parent();
			//Add ancestor
			if ( $par->is_valid() && !in_array($par, $ret, true) ) {
				$ret[] = $par;
			}
			//Get next ancestor
			$thm = $par;
		}
		return $ret;
	}
	
	/**
	 * Set public flag
	 * @param bool $public
	 */
	public function set_public($public) {
		$this->public = !!$public;
	}
	
	/**
	 * Get privacy state
	 * @return bool
	 */
	public function get_public() {
		return !!$this->public;
	}
	
	/* Assets */
	
	public function set_scripts($scripts) {
		$this->add_files('scripts', $scripts);
	}
	
	public function set_styles($styles) {
		$this->add_files('styles', $styles);
	}
	
	/**
	 * Get Theme style path
	 * @see `get_style()`
	 */
	public function get_client_style($format = null) {
		return $this->get_style('client', $format);
	}
	
	/**
	 * Get formatted handle for file
	 * @param string $base_handle Base handle to format
	 * @return string Formatted handle
	 */
	public function get_handle($base_handle) {
		return $this->add_prefix( array('theme', $this->get_id(), $base_handle), '-');
	}
	
	/**
	 * Enqueue files in client
	 * 
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
				//Format handle
				$handle = $this->get_handle($f->handle);
				
				//Format dependencies
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
	
	/* Templates */
	
	/**
	 * Add template file
	 * @see `add_file()`
	 * @param string $handle Template handle
	 * @param string $src Template URI
	 * @return obj Current instance
	 */
	protected function add_template($handle, $src) {
		return $this->add_file('template', $handle, $src);
	}
	
	/**
	 * Retrieve template file
	 * @see `get_file()`
	 * @param string $handle Template handle
	 * @param string $format (optional) Return value format
	 * @return mixed Template file (Default: array of file properties @see `Base_Object::add_file()`)
	 */
	protected function get_template($handle, $format = null) {
		return $this->get_file('template', $handle, $format);
	}
	
	/* Layout */
	
	/**
	 * Set theme layout
	 * @uses `add_template()`
	 * @param string $src Layout file URI
	 * @return Current instance
	 */
	public function set_layout($src) {
		return $this->add_template('layout', $src);
	}
	
	/**
	 * Get layout
	 * @param string $format (optional) Layout data format
	 * @return mixed Theme layout
	 */
	public function get_layout($format = null) {
		return $this->get_template('layout', $format);
	}
}