/**
 * View (Lightbox) functionality
 * @package Simple Lightbox
 * @subpackage View
 * @author Archetyped
 */

(function ($) {

if ( !SLB || !SLB.attach )
	return false; 

/*-** Controller **-*/

var View = {
	
	/* Properties */
	features: { active: '', disabled: 'off', group: 'group', internal: 'internal' },
	items: null,
	item_current: null,
	group: null,
	slideshow_active: true,
	layout: false,
	
	/* Collections */
	
	viewers: {},
	assets: {},
	content_types: {},
	groups: {},
	themes: {},
	theme_tags: {},
	
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
		// this.init_features();
		
		//Items
		// this.init_items();
		
		//UI
		this.init_theme();
		console.groupEnd();
	},
	
	/* Properties */
	
	/**
	 * Init link feature (activated, grouping, etc.) identifiers
	 * @TODO Refactor
	 */
	init_features: function() {
		console.groupCollapsed('Features');
		for ( f in this.features ) {
			this.features[f] = ( '' == this.features[f] ) ? this.util.get_prefix() : this.util.add_prefix(this.features[f]);
		}
		console.dir(this.features);
		console.groupEnd();
	},
	
	/**
	 * Retrieve feature value
	 * @TODO Refactor
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
	
	/* Theme */
	
	/**
	 * Initialize default theme
	 */
	init_theme: function() {
		console.groupCollapsed('Theme');
		//Initialize default theme
		this.add_theme(this.util.add_prefix('default'), this.options.layout);
		console.dir(this.themes);
		console.groupEnd();
	},
	
	/**
	 * Add theme
	 * @param string name Theme name
	 * @param string layout Layout HTML
	 * @param obj options Theme options
	 * @return obj New Theme instance
	 */
	add_theme: function(id, layout, options) {
		//Validate params
		if ( !this.util.is_valid(id, 'string', true) )
			id = this.util.add_prefix('default');
		//Create theme
		var thm = new this.Theme(id, layout, options);
		//DEBUG: Set Parent
		thm._set_parent(this);
		//Add to collection
		this.themes[thm.get_id()] = thm;
		if ( thm instanceof this.Theme )
			console.log('Added theme: %o', thm);
		//Return
		return thm;
	},
	
	add_theme_tag: function(id, build, attrs, dynamic) {
		//Validate ID
		if ( this.util.id_valid(id) ) {
			//Create new instance
			var tag = new this.Theme_Tag(id, build, attrs, dynamic);
			//Add to collection
			this.theme_tags[tag.get_id()] = tag;
		}
	}
};

/* Components */
var Component_Proto = {
	/* Properties */
	
	/**
	 * Component ID
	 * @var string
	 */
	id: '',
	
	/**
	 * Options
	 * @var obj
	 */
	options: {},
	
	/* Init */
	
	_c: function() {},
	
	_set_parent: function() {
		this._parent = View;
		this.util._parent = this;
	},
	
	/* Methods */
	
	get_id: function() {
		return this.id;
	},
	
	set_id: function(id) {
		this.id = id.toString();
	},
	
	get_options: function() {
		return this.options;
	},
	
	get_option: function(option) {
		if ( typeof option == 'string' && option.length > 0 && option in this.options )
			return this.options[option];
		return null;
	},
	
	set_options: function(options) {
		if ( $.isPlainObject(options) )
			$.extend(this.options, options);
	},
	
	set_option: function(option, value) {
		if ( this.util.is_valid(option, 'string') && this.util.is_valid(value, null, false) ) {
			this.options[option] = value;
		}
	},
};

var Component = SLB.Class.extend(Component_Proto);

/**
 * Content viewer
 * @param obj options Init options
 */
var Viewer = {
	_c: function() {
		console.log('Viewer');
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

View.Viewer = Component.extend(Viewer);

/**
 * Content group
 * @param obj options Init options
 */
var Group = {
	_c: function() {
		console.log('Group');
	},
	
	/**
	 * Setup group
	 */
	setup: function(item) {
		
	},
	
	/**
	 * Retrieve group items
	 */
	get_items: function() {
		
	}
};

View.Group = Component.extend(Group);

/**
 * Content type
 * @param obj options Init options
 */
var Content_Type = {
	_c: function() {
		console.log('Content Type');
	}
};

View.Content_Type = Component.extend(Content_Type);

/**
 * Content Item
 * @param obj options Init options
 */
var Content_Item = {
	/* Properties */
	
	attributes: {},
	
	/* Init */
	
	_c: function(item, attributes) {
		console.log('New Content Item');
		//Validate item
		
		//Set attributes
		
	},
	
	/* Methods */
	
	/**
	 * Retrieve item's group
	 * @param obj item Item to get group from
	 * @return string Item group name (Empty string if no group)
	 */
	get_group: function() {
		var g = this.get_attribute('group');
		return ( g != null ) ? g : '';
	},
	
	/**
	 * Retrieve value of specified attribute for value
	 * @param obj item Content item
	 * @param string attr Attribute to get value of
	 * @return mixed Attribute value (NULL if attribute is not set)
	 */
	get_attribute: function(attr) {
		var attrs = this.get_attributes();
		if ( attr in attrs )
			return attrs[attr];
		return null;
	},
	
	/**
	 * Retrieve item attributes
	 * @param obj item Item to get attributes for
	 * @return object Item attributes
	 */
	get_attributes: function() {
		//Debug: Temp item
		var item = '';
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
};

View.Content_Item = Component.extend(Content_Item);

/**
 * Theme
 * @param obj options Init options
 */
var Theme = {
	
	/* Properties */
	
	/**
	 * Raw layout
	 * @var string
	 */	
	_layout: '',
	
	/**
	 * Parsed layout
	 * Placeholders processed
	 * @var string
	 */
	layout: '',
	
	/* Init */
	
	/**
	 * Constructor
	 * @param string id Theme ID
	 * @param string layout Theme HTML
	 * @param obj options Theme options
	 * @return obj Theme instance
	 */
	_c: function(id, layout, options) {
		console.groupCollapsed('New Theme');
		this.set_id(id);
		this.set_layout(layout);
		this.set_options(options);
		console.log('Name: %o \nLayout: %o \nOptions: %o', this.get_id(), this.get_layout(), this.get_options());
		console.groupEnd();
	},
	
	/* Methods */
	
	/**
	 * Retrieve layout
	 * @uses layout
	 * @return string Layout HTML
	 */
	get_layout: function() {
		return this.layout;
	},
	
	/**
	 * Set layout
	 * @uses _layout to set raw layout
	 * @uses layout to set parsed layout
	 * @uses parse_layout to pare raw layout
	 * @param string layout Layout HTML
	 */
	set_layout: function(layout) {
		console.log('Setting Layout: %o', layout);
		//Validate layout
		if ( !this.util.is_valid(layout, 'string') ) {
			console.warn('Layout invalid');
			//Clear layout
			layout = '';
		}
		//Save raw layout
		this._layout = layout;
		//Parse layout
		this.layout = this.parse_layout();
	},
	
	/**
	 * Parse raw layout
	 * @uses _layout to retrieve raw layout HTML
	 * @return string Parsed layout
	 */
	parse_layout: function() {
		//Parse raw layout
		return this._layout;
	},
};

View.Theme = Component.extend(Theme);

/**
 * Theme tag handler
 */
var Theme_Tag = {
	/* Properties */
	
	/**
	 * Indicates if tag is dynamic or not
	 * @param bool
	 */
	dynamic: false,
	
	/**
	 * Default tag attributes/values
	 * @param obj
	 */
	attrs: {},
	
	/* Init */
	
	/**
	 * Constructor
	 * @param string id Unique ID
	 * @param obj attrs (optional) Default attributes
	 * @param bool dynamic (optional) Whether or not tag is dynamically built
	 */
	_c: function(id, build, attrs, dynamic) {
		this.set_id(id);
		this.set_build(build);
		this.set_attrs(attrs);
		this.set_dynamic(dynamic);
	},
	
	/* Methods */
	
	/**
	 * Parse tag and return output
	 * @param obj theme Theme instance
	 * @param obj tag Parsed tag properties
	 * @return string Tag output
	 */
	parse: function(theme, tag) {
		return this.build(theme, tag);
	},
	
	/**
	 * Build tag output
	 * @param obj theme Theme instance
	 * @param obj tag Parsed tag properties
	 * @return string Tag output
	 */
	build: function(theme, tag) {
		return 'build';
	},
	
	/**
	 * Set instance build method
	 * @param function build Build method
	 */
	set_build: function(build) {
		if ( $.isFunction(build) )
			this.build = build;
	},
	
	/**
	 * Set default attributes for tag
 	 * @param obj attrs Default attributes
	 */
	set_attrs: function(attrs) {
		if ( $.isPlainObject(attrs) )
			this.attrs = attrs;
	},
	
	/**
	 * Set tag as dynamic or not
	 * @param bool dynamic (Default: No)
	 */
	set_dynamic: function(dynamic) {
		this.dynamic = ( this.util.is_valid(dynamic, 'bool', true) ) ? dynamic : false;
	}
};

View.Theme_Tag = Component.extend(Theme_Tag);

//Attach to global object
SLB.attach('View', View);

})(jQuery);