<?php
require_once 'class.component.php';

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
	
	/* Templates */
	
	/**
	 * Add template file
	 * @see `add_file()`
	 * @param string $handle Template handle
	 * @param string $src Template path
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
	 * @return mixed Template file
	 */
	protected function get_template($handle, $format = null) {
		return $this->get_file('template', $handle, $format);
	}
	
	/* Layout */
	
	/**
	 * Set theme layout
	 * @uses `add_template()`
	 * @param string $src File path
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