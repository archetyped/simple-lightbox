<?php
require_once 'class.base.php';

/**
 * Theme instance
 * @package Simple Lightbox
 * @subpackage Themes
 * @author Archetyped
 */
class SLB_Theme extends SLB_Base {
	/*-** Properties **-*/
	
	/**
	 * @var string Unique ID
	 */
	private $id = '';
	
	/**
	 * @var string Pretty name
	 */
	private $name = '';
	
	/**
	 * @var string Raw template
	 */
	private $template_data = '';
	
	/**
	 * @var string Template URI (Relative or absolute path)
	 */
	private $template_uri = '';
	
	/**
	 * @var string Stylesheet URI (Relative or absolute path)
	 */
	private $stylesheet_uri = '';
	
	/**
	 * @var string Client attributes (Relative or absolute path)
	 */
	private $client_attributes = '';
	
	/*-** Methods **-*/
	
	/**
	 * Constructor
	 */
	function __construct( $props = array() ) {
		parent::__construct();
		//Normalize properties
		if ( !is_array($props) ) {
			$props = array();
		}
		$defaults = array (
			'id'				=> '',
			'name'				=> '',
			'template_uri'		=> '',
			'template_data'		=> '',
			'stylesheet_uri'	=> '',
			'client_attributes'	=> '',
		);
		
		$props = array_merge($defaults, $props);
		
		//Set properties
		$vars = array_keys( get_class_vars(__CLASS__) );
		
		foreach ( $vars as $var ) {
			if ( isset($props[$var]) ) {
				$this->{$var} = $props[$var];	
			}
		}
		
		//Return instance
		return $this;
	}
	
	/*-** Getters/Setters **-*/
	
	
}

/**
 * Theme collection management
 * @package Simple Lightbox
 * @subpackage Themes
 * @author Archetyped
 */
class SLB_Themes extends SLB_Base {
	
}