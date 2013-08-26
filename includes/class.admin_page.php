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
		return parent::add_content($id, array(
			'id'			=> $id,
			'title'			=> $title,
			'callback'		=> $callback,
			'context'		=> $context,
			'priority'		=> $priority,
			'callback_args'	=> $callback_args
			)
		);
	}
	
	/**
	 * Parse content by parameters
	 * Sets content value
	 */
	protected function parse_content() {
		//Get raw content
		$raw = $this->get_content(false);
		//Group by context
		$content = array();
		foreach ( $raw as $c ) {
			//Add new context
			if ( !isset($content[$c->context]) ) {
				$content[$c->context] = array();
			}
			//Add item to context
			$content[$c->context][] = $c;
		}
		return $content;
	}
	
	/**
	 * Render content blocks
	 * @param string $context (optional) Context to render
	 */
	protected function render_content($context = 'primary') {
		//Get content
		$content = $this->get_content();
		//Check for context
		if ( !isset($content[$context]) ) {
			return false;
		}
		$content = $content[$context];
		$out = '';
		//Render content
		?>
		<div class="content-wrap">
		<?php
		//Add meta boxes
		$screen = get_current_screen();
		foreach ( $content as $c ) {
			//Callback
			if ( is_callable($c->callback) ) {
				$callback = $c->callback;
				add_meta_box($c->id, $c->title, $c->callback, $screen, $context, $c->priority, $c->callback_args);
			}
		}
		//Output meta boxes
		do_meta_boxes($screen, $context, null);
		?>
		</div>
		<?php
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
		<div class="wrap slb">
			<?php $this->show_icon(); ?>
			<h2><?php esc_html_e( $this->get_label('header') ); ?></h2>
			<div class="metabox-holder columns-2">
				<div class="content-primary postbox-container">
					<?php
					$this->render_content('primary');
					?>
				</div>
				<div class="content-secondary postbox-container">
					<?php
					$this->render_content('secondary');
					?>
				</div>
			</div>
			<br class="clear" />
		</div>
		<?php
	}
}