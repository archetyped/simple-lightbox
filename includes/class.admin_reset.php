<?php

/**
 * Reset functionality
 * Used for adding options reset links to plugin listing, etc.
 * @package Simple Lightbox
 * @subpackage Admin
 * @author Archetyped
 */
class SLB_Admin_Reset extends SLB_Admin_View {
	/* Properties */
	
	protected $parent_required = false;
	
	public $hook_prefix = 'admin_reset';
	
	/* Init */
	
	/**
	 * Init
	 * @param string $id ID
	 * @param array $labels Labels
	 * @param obj $options Options instance
	 */
	function __construct($id, $labels, $options) {
		parent::__construct($id, $labels);
		//Default options instance
		$this->add_content('options', $options);
		return $this;
	}
	
	/* Handlers */
	
	/**
	 * Default handler
	 * Resets plugin settings
	 * @return string Status message (success, fail, etc.)
	 */
	public function handle() {
		//Validate user
		if ( ! current_user_can('activate_plugins') || ! check_admin_referer($this->get_id()) )
			wp_die(__('Access Denied', 'simple-lightbox'));
		
		//Get data
		$content = $this->get_content();
		
		$success = true;
		
		//Iterate through data
		foreach ( $content as $c ) {
			//Trigger reset
			$res = $this->util->apply_filters('trigger', $success, $c->data, $this);
			//Set result
			if ( !!$success ) {
				$success = $res;
			}
		}
		
		//Set Status Message
		$lbl = ( $success ) ? 'success' : 'failure';
		$this->set_message($this->get_label($lbl));
		
		/*
		//Redirect user
		$uri = remove_query_arg(array('_wpnonce', 'action'), add_query_arg(array($this->add_prefix('action') => $action), $_SERVER['REQUEST_URI']));
		wp_redirect($uri);
		exit;
		*/
	}
	
	public function get_uri() {
		return wp_nonce_url(add_query_arg($this->get_query_args(), remove_query_arg($this->get_query_args_remove(), $_SERVER['REQUEST_URI'])), $this->get_id());
	}
	
	protected function get_query_args() {
		return array (
			'action'					=> $this->add_prefix('admin'),
			$this->add_prefix('type')	=> 'view',
			$this->add_prefix('group')	=> 'reset',
			$this->add_prefix('obj')	=> $this->get_id_raw()
		);
	}
	
	protected function get_query_args_remove() {
		$args_r = array (
			'_wpnonce',
			$this->add_prefix('action')
		);
		
		return array_unique( array_merge( array_keys( $this->get_query_args() ), $args_r ) );
	}
	
	public function get_link_attr() {
		return array (
			'class' 	=> 'delete',
			'onclick'	=> "return confirm('" . $this->get_label('confirm') . "')"
		);
	}
	
	/* Content */
	
	/**
	 * Save options
	 */
	public function add_content($id, $data) {
		return parent::add_content($id, array('data' => $data));
	}
}