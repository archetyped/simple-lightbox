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
	
	protected $required = array ( 'options' => 'object' );
	
	protected $parent_required = false;
	
	/* Init */
	
	function __construct($id, $labels, $options) {
		parent::__construct($id, $labels, $options);
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

		//Reset settings
		if ( $this->is_options_valid() )
			$this->get_options()->reset(true);
		
		//Set Status Message
		$this->set_message($this->get_label('success'));
		
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
	
}