<?php

/**
 * Collection Controller
 * @package Simple Lightbox
 * @subpackage Collection
 * @author Archetyped
 */
class SLB_Collection_Controller extends SLB_Base_Collection {
	/* Configuration */

	protected $mode = 'full';

	protected $unique = true;

	/* Properties */

	protected $parent = null;

	/* Methods */

	public function __construct( $parent = null ) {
		$this->set_parent( $parent );
		parent::__construct();
	}

	/* Initialization */

	/* Parent */

	/**
	 * Set parent instance
	 * @param SLB_Base $parent (optional) Parent instance
	 * @return obj Current instance
	 */
	protected function set_parent( $parent = null ) {
		$this->parent = ( $parent instanceof SLB_Base ) ? $parent : null;
		return $this;
	}

	/**
	 * Check if parent set
	 * @return bool TRUE if parent set
	 */
	protected function has_parent() {
		return ( is_object( $this->get_parent() ) ) ? true : false;
	}

	/**
	 * Retrieve parent
	 * @uses $parent
	 * @return null|obj Parent instance (NULL if no parent set)
	 */
	protected function get_parent() {
		return $this->parent;
	}
}
