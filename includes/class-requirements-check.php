<?php
/**
 * Requirements Validation
 *
 * Used to ensure environment meets plugin requirements.
 *
 * @package Simple Lightbox
 * @since 2.7.0
 */

/**
 * Plugin Requirements Validation class
 *
 * @since 2.7.0
 */
class SLB_Requirements_Check {
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	private $name = '';

	/**
	 * Plugin file
	 *
	 * @var string
	 */
	private $file = '';

	/**
	 * Plugin dependencies
	 *
	 * @var array
	 */
	private $deps = array(
		'php' => '5.4',
	);

	/**
	 * Dependency failures log
	 *
	 * @var array
	 */
	private $fail = array();

	/**
	 * URIs for notices, etc.
	 *
	 * @var array
	 */
	private $uri = array();

	/**
	 * Constructor
	 *
	 * @param array $args Requirements data.
	 * @return void
	 */
	public function __construct( $args ) {
		$args = (array) $args;
		// Set properties.
		foreach ( array_keys( get_class_vars( get_class( $this ) ) ) as $prop ) {
			if ( ! isset( $args[ $prop ] ) ) {
				continue;
			}
			// Merge array properties.
			if ( is_array( $this->$prop ) && is_array( $args[ $prop ] ) ) {
				$this->$prop = array_merge( $this->$prop, $args[ $prop ] );
				continue;
			}

			// Set string properties.
			if ( is_string( $this->$prop ) && is_scalar( $args[ $prop ] ) ) {
				$this->$prop = (string) $args[ $prop ];
				continue;
			}
		}
	}

	/**
	 * Check if plugin passes all requirements
	 *
	 * @return bool Requirements check result.
	 */
	public function passes() {
		$result = true;
		foreach ( $this->deps as $dep => $req ) {
			$m = $dep . '_passes';
			if ( ! method_exists( $this, $m ) ) {
				continue;
			}
			$passes = $this->$m();
			if ( ! $passes ) {
				// Requirements do not pass.
				$result = $passes;
				// Log dependency failures.
				$this->fail[] = $dep;
			}
		}
		// Handle requirements failure.
		if ( ! $result ) {
			add_action( 'load-plugins.php', array( $this, 'handle_failure' ) );
		}
		return $result;
	}

	/**
	 * Handle requirements failure
	 *
	 * @return void
	 */
	public function handle_failure() {
		// Handle each failed dependency.
		foreach ( $this->fail as $dep ) {
			$m = $dep . '_handle_failure';
			if ( method_exists( $this, $m ) ) {
				$this->$m();
			}
		}
		// Deactivate plugin.
		deactivate_plugins( plugin_basename( $this->file ) );
	}

	/**
	 * Validates PHP version.
	 *
	 * @return bool PHP requirement passes.
	 */
	private function php_passes() {
		return version_compare( PHP_VERSION, $this->deps['php'], '>=' );
	}

	/**
	 * Handle PHP requirement failure
	 *
	 * @return void
	 */
	private function php_handle_failure() {
		// Clear activation query variable from request (stop UI notices).
		unset( $_GET['activate'] );
		// Display notice to user.
		add_action( 'admin_notices', array( $this, 'php_notice' ) );
	}

	/**
	 * Display requirements failure notice and deactivate plugin.
	 *
	 * @return void
	 */
	public function php_notice() {
		global $slb_requirements;
		// Display message to user.
		$link = (object) array(
			/* translators: 1: Plugin name */
			'title' => sprintf( __( 'Learn more about %1$s\'s requirements', 'simple-lightbox' ), $this->name ),
			/* translators: Plugin requirements link text. */
			'text'  => __( 'Learn More', 'simple-lightbox' ),
		);
		// Full link.
		$link = sprintf( '<a target="_blank" href="%1$s" title="%2$s">%3$s</a>', $this->uri['reference'], esc_attr( $link->title ), esc_html( $link->text ) );
		/* translators: 1: Plugin name. 2: PHP version requirement. 3: Plugin requirements link. */
		$err_msg = sprintf( __( '%1$s requires PHP %2$s or higher.  Please have your hosting provider update PHP to enable Simple Lightbox. (%3$s)', 'simple-lightbox' ), $this->name, $this->deps['php'], $link );
		?>
		<div class="error"><p><?php echo $err_msg; ?></p></div>
		<?php
	}
}
