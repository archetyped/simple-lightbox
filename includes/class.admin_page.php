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

	public $hook_prefix = 'admin_page';

	/**
	 * Required features/elements
	 */
	private $_required = array();

	/* Init */

	public function __construct( $id, $parent, $labels, $callback = null, $capability = null ) {
		// Default
		parent::__construct( $id, $labels, $callback, $capability );
		// Class specific
		$this->set_parent( $parent );
		return $this;
	}

	/* Operations */

	/**
	 * Add content to page
	 * @uses parent::add_content()
	 * @param string $id Module ID
	 * @param string $title Module title
	 * @param mixed $callback Callback method or other data for building module UI
	 * @param string $context (optional) Context to add module to (Default: primary)
	 * @param string $priority (optional) Controls module ordering (Default: default)
	 * @param array $callback_args (optional) Additional data to pass callback (Default: NULL)
	 * @return object Page instance reference
	 */
	public function add_content( $id, $title, $callback = null, $context = 'primary', $priority = 'default', $callback_args = null ) {
		$opts = [
			'id'            => $id,
			'title'         => $title,
			'callback'      => $callback,
			'context'       => $context,
			'priority'      => $priority,
			'callback_args' => $callback_args,
		];
		return parent::add_content( $id, $opts );
	}

	/**
	 * Parse content by parameters
	 * Sets content value
	 */
	protected function parse_content() {
		// Get raw content
		$raw = $this->get_content( false );
		// Group by context
		$content = array();
		foreach ( $raw as $c ) {
			// Add new context
			if ( ! isset( $content[ $c->context ] ) ) {
				$content[ $c->context ] = array();
			}
			// Add item to context
			$content[ $c->context ][] = $c;
		}
		return $content;
	}

	/**
	 * Render content blocks
	 * @param string $context (optional) Context to render
	 */
	protected function render_content( $context = 'primary' ) {
		// Get content
		$content = $this->get_content();
		// Check for context
		if ( ! isset( $content[ $context ] ) ) {
			return false;
		}
		$content = $content[ $context ];
		$out     = '';
		// Render content
		?>
		<div class="content-wrap">
		<?php
			// Add meta boxes
			$screen = get_current_screen();
		foreach ( $content as $c ) {
			$c->screen = $screen;
			// Callback
			if ( is_callable( $c->callback ) ) {
				$callback = $c->callback;
				add_meta_box( $c->id, $c->title, $c->callback, $c->screen, $c->context, $c->priority, $c->callback_args );
			} else {
				// Let handlers build output
				$this->util->do_action( 'render_content', $c->callback, $this, $c );
			}
		}
			// Output meta boxes
			do_meta_boxes( $screen, $context, null );
		?>
		</div>
		<?php
	}

	/**
	 * Require form submission support
	 * @return obj Page instance
	 */
	public function require_form() {
		$this->_require( 'form_submit' );
		return $this;
	}

	/**
	 * Check if form submission is required
	 * @return bool TRUE if form submission required
	 */
	private function is_required_form() {
		return $this->_is_required( 'form_submit' );
	}

	/* Handlers */

	/**
	 * Default Page handler
	 * Builds content blocks
	 * @see this->init_menus() Set as callback for custom admin pages
	 * @uses current_user_can() to check if user has access to current page
	 * @uses wp_die() to end execution when user does not have permission to access page
	 */
	public function handle() {
		if ( ! current_user_can( $this->get_capability() ) ) {
			wp_die( __( 'Access Denied', 'simple-lightbox' ) );
		}
		wp_enqueue_script( 'postbox' );
		?>
		<div class="wrap slb">
			<h2><?php echo esc_html( $this->get_label( 'header' ) ); ?></h2>
			<?php
				// Form submission support
			if ( $this->is_required_form() ) {
				// Build form output
				$form_id = $this->add_prefix( 'admin_form_' . $this->get_id_raw() );
				$nonce   = (object) [
					'action' => $this->get_id(),
					'name'   => $this->get_id() . '_nonce',
				];
				?>
					<form id="<?php echo esc_attr( $form_id ); ?>" name="<?php echo esc_attr( $form_id ); ?>" action="" method="post">
					<?php
					wp_nonce_field( $nonce->action, $nonce->name );
			}
			?>
			<div class="metabox-holder columns-2">
				<div class="content-primary postbox-container">
					<?php
					$this->render_content( 'primary' );
					?>
				</div>
				<div class="content-secondary postbox-container">
					<?php
					$this->render_content( 'secondary' );
					?>
				</div>
			</div>
			<br class="clear" />
			<?php
				// Form submission support
			if ( $this->is_required_form() ) {
				submit_button();
				?>
					</form>
				<?php
			}
			?>
		</div>
		<?php
	}
}
