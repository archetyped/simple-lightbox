/**
 * Lightbox functionality
 * @package Simple Lightbox
 * @subpackage Lightbox
 * @author Archetyped
 */

(function ($) {

if ( !SLB || !SLB.extend )
	return false; 

var viewer = {
	
	/* Properties */
	features: { active: '', disabled: 'off', group: 'group', internal: 'internal' },
	assets: {},
	content_handlers: {},
	items: null,
	item_current: null,
	group: null,
	slideshow_active: true,
	layout: false,
	
	
	/* Template */
	template: {
		/* Skin Placeholders, etc. */
	},
	
	/* Options */
	options: {
		validate_links: false,
		ui_animate: true,
		ui_overlay_opacity: '0.8',
		ui_enabled_desc: true,
		ui_enabled_caption: true,
		ui_caption_src: true,
		ui_labels: {
			link_close: 'close',
			link_next: 'next &raquo;',
			link_prev: '&laquo; prev',
			slideshow_start: 'start slideshow',
			slideshow_stop: 'stop slideshow',
			slideshow_status: '',
			loading: 'loading'
		},
		group_loop: true,
		slideshow_autostart: true,
		slideshow_duration: '6',
	},
	
	/* Methods */
	
	/* Init */
	
	/**
	 * Initialization
	 */
	init: function(options) {
		console.group('Init');
		//Set options
		$.extend(true, this.options, options);
		console.groupCollapsed('Options');
		console.dir(this.options);
		console.groupEnd();
		
		//Set properties
		this.slideshow_active = this.options.slideshow_autostart;
		
		//Features
		this.init_features();
		
		//Items
		this.init_items();
		
		//UI
		this.layout_init();
		console.groupEnd();
	},
	
	/* Properties */
	
	/**
	 * Init link feature (activated, grouping, etc.) identifiers
	 */
	init_features: function() {
		console.groupCollapsed('Features');
		for ( f in this.features ) {
			this.features[f] = ( '' == this.features[f] ) ? this.base.get_prefix() : this.base.add_prefix(this.features[f]);
		}
		console.dir(this.features);
		console.groupEnd();
	},
	
	/**
	 * Retrieve feature value
	 */
	get_feature: function(f) {
		if ( f in this.features )
			return this.features[f];
		return '';
	},
	
	/* Items */
	
		/**
	 * Set event handlers
	 */
	init_items: function() {
		console.groupCollapsed('Items');
		//Define handler
		var t = this;
		var handler = function() {
			t.show(this);
			return false;
		};
		
		//Get activated links
		var sel = 'a[href][rel~="' + this.get_feature('active') + '"]:not([rel~="' + this.get_feature('disabled') + '"])';
		console.log('Selector: ' + sel);
		console.dir($(sel));
		//Add event handler
		$(sel).live('click', handler);
		console.groupEnd();
	},
	
	/**
	 * Retrieve items
	 * @param string group (optional) Group to retrieve items for
	 * @return array Items
	 */
	get_items: function(group) {
		
	},
	
	/**
	 * Retrieve item's group
	 * @param obj item Item to get group from
	 * @return string Item group name (Empty string if no group)
	 */
	get_item_group: function(item) {
		var g = this.get_item_attribute(item, this.get_feature('group'));
		return ( g != null ) ? g : '';
	},
	
	/**
	 * Retrieve value of specified attribute for value
	 * @param obj item Content item
	 * @param string attr Attribute to get value of
	 * @return mixed Attribute value (NULL if attribute is not set)
	 */
	get_item_attribute: function(item, attr) {
		var attrs = this.get_item_attributes(item);
		if ( attr in attrs )
			return attrs[attr];
		return null;
	},
	
	/**
	 * Retrieve item attributes
	 * @param obj item Item to get attributes for
	 * @return object Item attributes
	 */
	get_item_attributes: function(item) {
		console.groupCollapsed('Get Attributes');
		//Get attribute values
		var attrs = $(item).attr('rel');
		attrs = attrs.split(' ');
		var o = {};
		var wrap = {start: '[', end: ']'};
		var istart = -1, iend = -1;
		var val;
		//Format attributes
		for ( var a = 0; a < attrs.length; a++ ) {
			attr = attrs[a];
			istart = attr.indexOf(wrap.start);
			iend = attr.indexOf(wrap.end);
			if ( istart > 0 && iend == ( attr.length - 1 ) ) {
				//Extract attribute value
				val = attr.substring(istart + 1, iend);
				attr = attr.substr(0, istart);
			} else {
				val = true;
			}
			//Set attribute
			o[attr] = val;
		}
		console.dir(o);
		console.groupEnd();
		return o;
	},
	
	/* Display */
	
	/**
	 * Display content in lightbox
	 */
	show: function(item) {
		console.groupCollapsed('Show Item');
		console.log(item);
		this.get_item_attributes(item);
		var group = this.get_item_group(item);
		console.groupEnd();
	},
	
	/**
	 * Resize lightbox to fit content
	 */
	resize: function() {
		
	},
	
	/**
	 * Prepare DOM
	 * Hide overlapping DOM elements, etc.
	 */
	dom_prepare: function() {
		
	},
	
	/**
	 * Restore DOM
	 * Show overlapping DOM elements, etc.
	 */
	dom_restore: function() {
		
	},
	
	/* Template */
	
	/**
	 * Initialize layout
	 */
	layout_init: function() {
		console.groupCollapsed('Layout');
		//Build layout
		var layout = this.layout_build();
		console.log('Layout\n %o', layout);
		//Add to DOM
		if ( '' != layout ) {
			console.info('Adding layout to DOM');
			$(document).append(layout);
			this.layout = $(layout);
		
			//Set event handlers
			this.layout_events();		
		}
		console.groupEnd();
	},
	
	/**
	 * Build layout
	 */
	layout_build: function() {
		return '';
	},
	
	/**
	 * Retrieve layout element
	 * @param string el Element ID
	 * @return jQuery object Specified element
	 * If element is not specified, then entire layout is returned
	 */
	layout_get: function(el) {
		return $(this.layout);
	},
	
	/**
	 * Setup event handlers for layout
	 * navigation, close, overlay, etc.
	 */
	layout_events: function() {
		
	},
	
	/* Data */
	
	/**
	 * Load content
	 */
	content_fetch: function(item) {
		
	},
	
	/**
	 * Get content type
	 */
	
	/**
	 * Setup group
	 */
	group_setup: function(item) {
		
	},
	
	/**
	 * Setup content array
	 */
	group_get_items: function() {
		
	},
	
	/* Interactivity */
	
	/**
	 * Start Slideshow
	 */
	slideshow_start: function() {
		
	},
	
	/**
	 * Stop Slideshow
	 */
	slideshow_stop: function() {
		
	},
	
	/**
	 * Next item
	 */
	item_next: function() {
		
	},
	
	/**
	 * Previous item
	 */
	item_prev: function() {
		
	},
	
	/**
	 * Close lightbox
	 */
	close: function() {
		
	}
	
};

SLB.extend('viewer', viewer);

})(jQuery);