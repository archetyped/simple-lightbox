<?php

/**
 * Content Handler
 * @package Simple Lightbox
 * @subpackage Content Handlers
 * @author Archetyped
 */
class SLB_Content_Handler extends SLB_Component {
	/* Properties */
	
	/**
	 * Match handler
	 * @var callback
	 */
	protected $match;
	
	/**
	 * Custom attributes
	 * @var callback
	 */
	protected $attributes;
	
	/* Matching */
		
	/**
	 * Set matching handler
	 * @param callback $callback Handler callback
	 * @return object Current instance
	 */
	public function set_match($callback) {
		$this->match = ( is_callable($callback) ) ? $callback : null;
		return $this;
	}
	
	/**
	 * Retrieve match handler
	 * @return callback|null Match handler
	 */
	protected function get_match() {
		return $this->match;
	}
	
	/**
	 * Check if valid match set
	 */
	protected function has_match()	{
		return ( is_null($this->match) ) ? false : true;
	}
	
	/**
	 * Match handler against URI
	 * @param string $uri URI to check for match
	 * @return bool TRUE if handler matches URI
	 */
	public function match($uri, $uri_raw = null) {
		$ret = false;
		if ( !!$uri && is_string($uri) && $this->has_match() ) {
			$ret = call_user_func($this->get_match(), $uri, $uri_raw);
		}
		return $ret;
	}
	
	/* Attributes */
	
	public function set_attributes($callback) {
		$this->attributes = ( is_callable($callback) ) ? $callback : null;
		return $this;
	}
	
	public function get_attributes() {
		$ret = array();
		// Callback
		if ( !is_null($this->attributes) ) {
			$ret = call_user_func($this->attributes);
		}
		// Filter
		$hook = sprintf('content_handler_%s_attributes', $this->get_id());
		$ret = $this->util->apply_filters($hook, $ret);
		return ( is_array($ret) ) ? $ret : array();
	}
}