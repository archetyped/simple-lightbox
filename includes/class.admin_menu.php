<?php

/**
 * Admin Menu
 * Menus are top-level views in the Admin UI
 * @package Simple Lightbox
 * @subpackage Admin
 * @author Archetyped
 */
class SLB_Admin_Menu extends SLB_Admin_View {
	/* Properties */

	/**
	 * Menu position
	 * @var int
	 */
	protected $position = null;

	/* Init */

	public function __construct( $id, $labels, $callback = null, $capability = null, $icon = null, $position = null ) {
		// Default
		parent::__construct( $id, $labels, $callback, $capability, $icon );
		// Class specific
		$this->set_position( $position );
		return $this;
	}

	/* Getters/Setters */

	/**
	 * Set menu position
	 * @return obj Current instance
	 */
	public function set_position( $position ) {
		if ( is_int( $position ) ) {
			$this->position = $position;
		}
		return $this;
	}
}
