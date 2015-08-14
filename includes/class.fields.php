<?php
/**
 * Collection of default system-wide fields
 * @package Simple Lightbox
 * @subpackage Fields
 * @author Archetyped
 *
 */
class SLB_Fields extends SLB_Field_Collection {
	
	var $item_type = 'SLB_Field_Type';
	
	/**
	 * Placeholder handlers
	 * @var array
	 */
	var $placholders = null;
	
	/* Constructor */
	
	function __construct() {
		parent::__construct('fields');
	}
	
	protected function _hooks() {
		parent::_hooks();
		// Init fields
		add_action('init', $this->m('register_types'));
		// Init placeholders
		add_action('init', $this->m('register_placeholders'));
	}
	
	/* Field Types */
	
	/**
	 * Initialize fields
	 */
	function register_types() {
		/* Field Types */

		// Base
		$base = new SLB_Field_Type('base');
		$base->set_description(__('Default Element', 'simple-lightbox'));
		$base->set_property('tag', 'span');
		$base->set_property('class', '', 'attr');
		$base->set_layout('form_attr', '{tag} name="{field_name}" id="{field_id}" {properties ref_base="root" group="attr"}');
		$base->set_layout('form', '<{form_attr ref_base="layout"} />');
		$base->set_layout('label', '<label for="{field_id}">{label}</label>');
		$base->set_layout('display', '{data context="display"}');
		$this->add($base);

		// Base closed
		$base_closed = new SLB_Field_Type('base_closed');
		$base_closed->set_parent('base');
		$base_closed->set_description(__('Default Element (Closed Tag)', 'simple-lightbox'));
		$base_closed->set_layout('form_start', '<{tag} id="{field_id}" name="{field_name}" {properties ref_base="root" group="attr"}>');
		$base_closed->set_layout('form_end', '</{tag}>');
		$base_closed->set_layout('form', '{form_start ref_base="layout"}{data}{form_end ref_base="layout"}');
		$this->add($base_closed);

		// Input
		$input = new SLB_Field_Type('input', 'base');
		$input->set_description(__('Default Input Element', 'simple-lightbox'));
		$input->set_property('tag', 'input');
		$input->set_property('type', 'text', 'attr');
		$input->set_property('value', '{data}', 'attr');
		$this->add($input);

		// Text input
		$text = new SLB_Field_Type('text', 'input');
		$text->set_description(__('Text Box', 'simple-lightbox'));
		$text->set_property('size', 15, 'attr');
		$text->set_property('label');
		$text->set_layout('form', '{label ref_base="layout"} {inherit}');
		$this->add($text);
		
		// Checkbox
		$cb = new SLB_Field_Type('checkbox', 'input');
		$cb->set_property('type', 'checkbox');
		$cb->set_property('value', null);
		$cb->set_layout('form_attr', '{inherit} {checked}');
		$cb->set_layout('form', '{label ref_base="layout"} <{form_attr ref_base="layout"} />');
		$this->add($cb);

		// Textarea
		$ta = new SLB_Field_Type('textarea', 'base_closed');
		$ta->set_property('tag', 'textarea');
		$ta->set_property('cols', 40, 'attr');
		$ta->set_property('rows', 3, 'attr');
		$this->add($ta);
		
		// Rich Text
		$rt = new SLB_Field_Type('richtext', 'textarea');
		$rt->set_property('class', 'theEditor {inherit}');
		$rt->set_layout('form', '<div class="rt_container">{inherit}</div>');
		$rt->add_action('admin_print_footer_scripts', 'wp_tiny_mce', 25);
		$this->add($rt);

		// Hidden
		$hidden = new SLB_Field_Type('hidden');
		$hidden->set_parent('input');
		$hidden->set_description(__('Hidden Field', 'simple-lightbox'));
		$hidden->set_property('type', 'hidden');
		$this->add($hidden);

		// Select
		$select = new SLB_Field_Type('select', 'base_closed');
		$select->set_description(__('Select tag', 'simple-lightbox'));
		$select->set_property('tag', 'select');
		$select->set_property('tag_option', 'option');
		$select->set_property('options', array());
		$select->set_layout('form', '{label ref_base="layout"} {form_start ref_base="layout"}{option_loop ref_base="layout"}{form_end ref_base="layout"}');
		$select->set_layout('option_loop', '{loop data="properties.options" layout="option" layout_data="option_data"}');
		$select->set_layout('option', '<{tag_option} value="{data_ext id="option_value"}">{data_ext id="option_text"}</{tag_option}>');
		$select->set_layout('option_data', '<{tag_option} value="{data_ext id="option_value"}" selected="selected">{data_ext id="option_text"}</{tag_option}>');		
		$this->add($select);
		
		// Span
		$span = new SLB_Field_Type('span', 'base_closed');
		$span->set_description(__('Inline wrapper', 'simple-lightbox'));
		$span->set_property('tag', 'span');
		$span->set_property('value', 'Hello there!');
		$this->add($span);
		
		// Enable plugins to modify (add, remove, etc.) field types
		$this->util->do_action_ref_array('register_fields', array($this), false);
		
		// Signal completion of field registration
		$this->util->do_action_ref_array('fields_registered', array($this), false);
	}
	
	/* Placeholder handlers */
	
	function register_placeholders() {
		// Default placeholder handlers
		$this->register_placeholder('all', $this->m('process_placeholder_default'), 11);
		$this->register_placeholder('field_id', $this->m('process_placeholder_id'));
		$this->register_placeholder('field_name', $this->m('process_placeholder_name'));
		$this->register_placeholder('data', $this->m('process_placeholder_data'));
		$this->register_placeholder('data_ext',$this->m('process_placeholder_data_ext'));
		$this->register_placeholder('loop', $this->m('process_placeholder_loop'));
		$this->register_placeholder('label', $this->m('process_placeholder_label'));
		$this->register_placeholder('checked', $this->m('process_placeholder_checked'));
		
		// Allow other code to register placeholders
		$this->util->do_action_ref_array('register_field_placeholders', array($this), false);
		
		// Signal completion of field placeholder registration
		$this->util->do_action_ref_array('field_placeholders_registered', array($this), false);
	}
	
	/**
	 * Register a function to handle a placeholder
	 * Multiple handlers may be registered for a single placeholder
	 * Adds filter hook to WP for handling specified placeholder
	 * Placeholders are in layouts and are replaced with data at runtime
	 * @uses add_filter()
	 * @param string $placeholder Name of placeholder to add handler for (Using 'all' will set the function as a handler for all placeholders
	 * @param callback $callback Function to set as a handler
	 * @param int $priority (optional) Priority of handler
	 * @return void
	 */
	function register_placeholder($placeholder, $callback, $priority = 10) {
		if ( 'all' == $placeholder )
			$placeholder = '';
		else
			$placeholder = '_' . $placeholder;
		$hook = $this->add_prefix('process_placeholder' . $placeholder);
		add_filter($hook, $callback, $priority, 5);
	}
	
	/**
	 * Default placeholder processing
	 * To be executed when current placeholder has not been handled by another handler
	 * @param string $output Value to be used in place of placeholder
	 * @param SLB_Field $item Field containing placeholder
	 * @param array $placeholder Current placeholder
	 * @see SLB_Field::parse_layout for structure of $placeholder array
	 * @param string $layout Layout to build
	 * @param array $data Extended data for item
	 * @return string Value to use in place of current placeholder
	 */
	function process_placeholder_default($output, $item, $placeholder, $layout, $data) {
		// Validate parameters before processing
		if ( empty($output) && ($item instanceof SLB_Field_Type) && is_array($placeholder) ) {
			// Build path to replacement data
			$output = $item->get_member_value($placeholder);

			// Check if value is group (properties, etc.)
			// All groups must have additional attributes (beyond reserved attributes) that define how items in group are used
			if (is_array($output)
				&& !empty($placeholder['attributes'])
				&& is_array($placeholder['attributes'])
				&& ($ph = $item->get_placeholder_defaults())
				&& $attribs = array_diff(array_keys($placeholder['attributes']), array_values($ph->reserved))
			) {
				/* Targeted property is an array, but the placeholder contains additional options on how property is to be used */

				// Find items matching criteria in $output
				// Check for group criteria
				if ( 'properties' == $placeholder['tag'] && ($prop_group = $item->get_group($placeholder['attributes']['group'])) && !empty($prop_group) ) {
					/* Process group */
					$group_out = array();
					// Iterate through properties in group and build string
					foreach ( array_keys($prop_group) as $prop_key ) {
						$prop_val = $item->get_property($prop_key);
						if ( !is_null($prop_val) )
							$group_out[] = $prop_key . '="' . $prop_val . '"';
					}
					$output = implode(' ', $group_out);
				}
			} elseif ( is_object($output) && ($output instanceof $item->base_class) ) {
				/* Targeted property is actually a nested item */
				// Set caller to current item
				$output->set_caller($item);
				// Build layout for nested element
				$output = $output->build_layout($layout);
			}
		}

		return $output;
	}

	/**
	 * Build Field ID attribute
	 * @see SLB_Field_Type::process_placeholder_default for parameter descriptions
	 * @return string Placeholder output
	 */
	function process_placeholder_id($output, $item, $placeholder, $layout, $data) {
		// Get attributes
		$args = wp_parse_args($placeholder['attributes'], array('format' => 'attr_id')); 
		return $item->get_id($args);
	}
	
	/**
	 * Build Field name attribute
	 * Name is formatted as an associative array for processing by PHP after submission
	 * @see SLB_Field_Type::process_placeholder_default for parameter descriptions
	 * @return string Placeholder output
	 */
	function process_placeholder_name($output, $item, $placeholder, $layout, $data) {
		// Get attributes
		$args = wp_parse_args($placeholder['attributes'], array('format' => 'attr_name')); 
		return $item->get_id($args);
	}
	
	/**
	 * Build item label
	 * @see SLB_Fields::process_placeholder_default for parameter descriptions
	 * @return string Field label
	 */
	function process_placeholder_label($output, $item, $placeholder, $layout, $data) {
		// Check if item has label property (e.g. sub-elements)
		$out = $item->get_property('label');
		// If property not set, use item title
		if ( empty($out) )
			$out = $item->get_title();
		return $out;
	}
	
	/**
	 * Retrieve data for item
	 * @see SLB_Field_Type::process_placeholder_default for parameter descriptions
	 * @return string Placeholder output
	 */
	function process_placeholder_data($output, $item, $placeholder, $layout) {
		$attr_default = array (
			'context'	=> '',
		);
		$opts = wp_parse_args($placeholder['attributes'], $attr_default);
		// Save context to separate variable
		$context = $opts['context'];
		unset($opts['context']);
		// Get data
		$out = $item->get_data($opts);
		if ( !is_null($out) ) {
			// Get specific member in value (e.g. value from a specific item element)
			if ( isset($opts['element']) && is_array($out) && ( $el = $opts['element'] ) && isset($out[$el]) )
				$out = $out[$el];
		}
		
		// Format data based on context
		$out = $item->preserve_special_chars($out, $context);
		$out = $item->format($out, $context);
		// Return data
		return $out;
	}
	
	/**
	 * Set checked attribute on item
	 * Evaluates item's data to see if item should be checked or not
	 * @see SLB_Fields::process_placeholder_default for parameter descriptions
	 * @return string Appropriate checkbox attribute
	 */
	function process_placeholder_checked($output, $item, $placeholder, $layout, $data) {
		$out = '';
		$c = $item->get_container();
		$d = ( isset($c->data[$item->get_id()]) ) ? $c->data[$item->get_id()] : null;
		$item->set_property('d', true);
		if ( $item->get_data() )
			$out = 'checked="checked"';
		$item->set_property('d', false);
		return $out;
	}

	/**
	 * Loops over data to build item output
	 * Options:
	 *  data		- Dot-delimited path in item that contains data to loop through
	 *  layout		- Name of layout to use for each data item in loop
	 *  layout_data	- Name of layout to use for data item that matches previously-saved item data
	 * @see SLB_Field_Type::process_placeholder_default for parameter descriptions
	 * @return string Placeholder output
	 */
	function process_placeholder_loop($output, $item, $placeholder, $layout, $data) {
		// Setup loop options
		$attr_defaults = array (
								'layout'		=> '',
								'layout_data'	=> null,
								'data'			=> ''
								);
		$attr = wp_parse_args($placeholder['attributes'], $attr_defaults);
		if ( is_null($attr['layout_data']) )
			$attr['layout_data'] =& $attr['layout'];
		// Get data for loop
		$path = explode('.', $attr['data']);
		$loop_data = $item->get_member_value($path);
		
		// Check if data is callback
		if ( is_callable($loop_data) )
			$loop_data = call_user_func($loop_data);
		
		// Get item data
		$data = $item->get_data();

		// Iterate over data and build output
		$out = array();
		if ( is_array($loop_data) && !empty($loop_data) ) {
			foreach ( $loop_data as $value => $label ) {
				// Load appropriate layout based on item value
				$layout = ( ($data === 0 && $value === $data) xor $data == $value ) ? $attr['layout_data'] : $attr['layout'];
				// Stop processing if no valid layout is returned
				if ( empty($layout) )
					continue;
				// Prep extended item data
				$data_ext = array('option_value' => $value, 'option_text' => $label);
				$out[] = $item->build_layout($layout, $data_ext);
			}
		}

		// Return output
		return implode($out);
	}
	
	/**
	 * Returns specified value from extended data array for item
	 * @see SLB_Field_Type::process_placeholder_default for parameter descriptions
	 * @return string Placeholder output
	 */
	function process_placeholder_data_ext($output, $item, $placeholder, $layout, $data) {
		if ( isset($placeholder['attributes']['id']) && ($key = $placeholder['attributes']['id']) && isset($data[$key]) ) {
			$output = strval($data[$key]);
		}
		
		return $output;
	}
	
	/* Build */
	
	/**
	 * Output items in a group
	 * @param string $group ID of Group to output
	 * @return string Group output
	 * TODO Make compatible with parent::build_group()
	 */
	function build_group($group) {
		$out = array();
		$classnames = (object) array(
			'multi'		=> 'multi_field',
			'single'	=> 'single_field',
			'elements'	=> 'has_elements'
		);

		// Stop execution if group does not exist
		if ( $this->group_exists($group) && $group =& $this->get_group($group) ) {
			$group_items = ( count($group->items) > 1 ) ? $classnames->multi : $classnames->single . ( ( ( $fs = array_keys($group->items) ) && ( $f =& $group->items[$fs[0]] ) && ( $els = $f->get_member_value('elements', '', null) ) && !empty($els) ) ? '_' . $classnames->elements : '' );
			$classname = array($this->add_prefix('attributes_wrap'), $group_items);
			$out[] = '<div class="' . implode(' ', $classname) . '">'; // Wrap all items in group

			// Build layout for each item in group
			foreach ( array_keys($group->items) as $item_id ) {
				$item =& $group->items[$item_id];
				$item->set_caller($this);
				// Start item output
				$id = $this->add_prefix('field_' . $item->get_id());
				$out[] = '<div id="' . $id . '_wrap" class=' . $this->add_prefix('attribute_wrap') . '>';
				// Build item layout
				$out[] = $item->build_layout();
				// end item output
				$out[] = '</div>';
				$item->clear_caller();
			}
			$out[] = '</div>'; // Close items container
			// Add description if exists
			if ( !empty($group->description) )
				$out[] = '<p class=' . $this->add_prefix('group_description') . '>' . $group->description . '</p>';
		}

		// Return group output
		return implode($out);
	}
}