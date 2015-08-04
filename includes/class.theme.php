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
	 * @param bool $sort_topdown (optional) Ancestor sorting (Default: Nearest to Farthest)
	 * @return array Theme's ancestors (sorted by nearest to most distant ancestor)
	 */
	public function get_ancestors($sort_topdown = false) {
		$ret = array();
		/**
		 * @var SLB_Theme
		 */
		$thm = $this;
		while ( $thm->has_parent() ) {
			$par = $thm->get_parent();
			// Add ancestor
			if ( $par->is_valid() && !in_array($par, $ret, true) ) {
				$ret[] = $par;
			}
			// Get next ancestor
			$thm = $par;
		}
		// Sorting
		if ( $sort_topdown ) {
			$ret = array_reverse($ret);
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