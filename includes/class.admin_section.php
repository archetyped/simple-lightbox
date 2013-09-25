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
		//Default
		parent::__construct($id, $labels, $callback, $capability);
		//Class specific
		$this->set_parent($parent);
		return $this;
	}
	
	/* Getters/Setters */
	
	public function get_uri() {
		$file = 'options-' . $this->get_parent() . '.php';
		return parent::get_uri($file, '%1$s#%2$s');
	}

	/**
	 * Retrieve formatted title for section
	 * Wraps title text in element with anchor so that it can be linked to
	 * @return string Title
	 */
	public function get_title() {
		return '<div id="' . $this->get_id() . '" class="' . $this->add_prefix('section_head') . '">' . $this->get_label('title') . '</div>';
	}
}