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
	
	public function __construct($id, $labels, $options = null, $callback = null, $capability = null, $icon = null, $position = null) {
		//Default
		parent::__construct($id, $labels, $options, $callback, $capability, $icon);
		//Class specific
		$this->set_position($position);
	}
	
	/* Getters/Setters */
	
	/**
	 * Set menu position
	 * @return obj Current instance
	 */
	public function set_position($position) {
		if ( is_int($position) )
			$this->position = $position;
		return $this;
	}
	
	/* Handlers */
	
	public function handle() {
		if ( !current_user_can($this->get_capability()) )
			wp_die(__('Access Denied', 'simple-lightbox'));
		?>
		<div class="wrap">
			<h2><?php esc_html_e( $this->get_label('header') ); ?></h2>
			<?php
			$this->show_options();
			?>
		</div>
		<?php
	}
}