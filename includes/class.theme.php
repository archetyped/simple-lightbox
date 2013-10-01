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
	
	/* Assets */
	
	protected function add_assets($type, $assets) {
		$m = $this->m('add_' . $type);
		if ( !is_array($assets) || empty($assets) )
			return false;
		foreach ( $assets as $asset ) {
			if ( !is_array($asset) || empty($asset) ) {
				continue;
			}
			call_user_func_array($m, $asset);
		}
	}
	
	public function set_scripts($scripts) {
		$this->add_assets('script', $scripts);
	}
	
	public function set_styles($styles) {
		$this->add_assets('style', $styles);
	}
	
	/**
	 * Get Theme style path
	 * @see `get_style()`
	 */
	public function get_client_style($format = null) {
		return $this->get_style('client', $format);
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