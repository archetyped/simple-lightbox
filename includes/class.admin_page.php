<?php

/**
 * Admin Page
 * Pages are part of a Menu
 * @package Simple Lightbox
 * @subpackage Admin
 * @author Archetyped
 */
class SLB_Admin_Page extends SLB_Admin_View {
	/* Properties */
	
	protected $parent_required = true;
	
	/* Init */
	
	public function __construct($id, $parent, $labels, $options = null, $callback = null, $capability = null, $icon = null) {
		//Default
		parent::__construct($id, $labels, $options, $callback, $capability, $icon);
		//Class specific
		$this->set_parent($parent);
	}
	
	/* Operations */
	
	protected function show_icon() {
		echo screen_icon();
	}
	
	/**
	 * Add content to page
	 * @uses parent::add_content()
	 * @return object Page instance reference
	 */
	public function add_content($id, $title, $callback, $context = 'primary', $priority = 'default', $callback_args = null) {
		return parent::add_content($id, $title, $callback, $context, $priority, $callback_args);
	}
	
	/* Handlers */
	
	/**
	 * Default Page handler
	 * Builds options form UI for page
	 * @see this->init_menus() Set as callback for custom admin pages
	 * @uses current_user_can() to check if user has access to current page
	 * @uses wp_die() to end execution when user does not have permission to access page
	 */
	public function handle() {
		if ( !current_user_can($this->get_capability()) )
			wp_die(__('Access Denied', 'simple-lightbox'));
		?>
		<div class="wrap">
			<?php $this->show_icon(); ?>
			<h2><?php esc_html_e( $this->get_label('header') ); ?></h2>
			<?php
			$this->show_options();
			?>
		</div>
		<?php
	}
}