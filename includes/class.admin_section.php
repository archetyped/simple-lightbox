<?php

/**
 * Admin Section
 * Sections are part of a Page
 * @package Simple Lightbox
 * @subpackage Admin
 * @author Archetyped
 */
class SLB_Admin_Section extends SLB_Admin_View {
	/* Properties */
	
	protected $parent_required = true;
	protected $parent_custom = false;
	
	/* Init */
	
	public function __construct($id, $parent, $labels, $callback = null, $capability = null) {
		// Default
		parent::__construct($id, $labels, $callback, $capability);
		// Class specific
		$this->set_parent($parent);
		return $this;
	}
	
	/* Getters/Setters */
	
	/**
	 * Retrieve URI
	 * @uses Admin_View::get_uri()
	 * @param string $file (optional) Base file name
	 * @param string $format (optional) String format
	 * @return string Section URI
	 */
	public function get_uri($file = null, $format = null) {
		if ( !is_string($file) )
			$file = 'options-' . $this->get_parent() . '.php';
		if ( !is_string($format) )
			$format = '%1$s#%2$s';
		return parent::get_uri($file, $format);
	}

	/**
	 * Retrieve formatted title for section
	 * Wraps title text in element with anchor so that it can be linked to
	 * @return string Title
	 */
	public function get_title() {
		return sprintf('<div id="%1$s" class="%2$s">%3$s</div>', $this->get_id(), $this->add_prefix('section_head'), $this->get_label('title'));
	}
}