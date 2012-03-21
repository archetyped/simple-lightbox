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
	
	/**
	 * Component types that can have default instances
	 * @var array
	 */
	component_defaults: [],
	
	/* Component Collections */
	
	viewers: {},
	items: [],
	content_types: {},
	groups: {},
	themes: {},
	
	/**
	 * Collection/Data type mapping
	 * > Key: Collection name
	 * > Value: Data type
	 * @var object
	 */
	collection_types: {},
	
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
		console.groupCollapsed('Init');
		//Set options
		$.extend(true, this.options, options);
		console.groupCollapsed('Options');
		console.dir(this.options);
		console.groupEnd();
		
		/* Set defaults */
		
		this.init_components();
		
		//Features
		this.init_features();
		
		//Theme
		this.init_theme();
		
		//Viewer
		this.init_viewers();
		
		//Items
		this.init_items();
		console.info('Init complete');
		console.groupEnd();
	},
	
	init_components: function() {
		this.collection_types = {
			'viewers':	 		this.Viewer,
			'items': 			this.Content_Item,
			'content_types': 	this.Content_Type,
			'groups': 			this.Group,
			'themes': 			this.Theme
		};
		
		this.component_defaults = [
			this.Viewer,
			this.Theme
		];
	
	},
	
	/* Components */
	
	component_make_default: function(type) {
		console.group('View.component_make_default');
		var ret = false;
		console.dir(this.component_defaults);
		for ( var x = 0; x < this.component_defaults.length; x++ ) {
			console.log(x);
			console.log('Checking: %o \nMatch: %o', this.component_defaults[x].prototype._slug);
			if ( type == this.component_defaults[x] ) {
				ret = true;
				break;
			}
		}
		console.log('Type: %o \nDefaults: %o', type.prototype._slug, ret);
		console.groupEnd();
		return ret;
	},
	
	/**
	 * Retrieve collection of components of specified type
	 * @param function type Component type
	 * @return object|array|null Component collection (NULL if invalid)
	 */
	get_components: function(type) {
		var ret = null;
		if ( this.util.is_func(type) ) {
			//Determine collection
			for ( var coll in this.collection_types ) {
				if ( type == this.collection_types[coll] && coll in this ) {
					ret = this[coll];
					break;
				}
			}
		}
		return ret;
	},
	
	/**
	 * Retrieve component from specific collection
	 * @param function type Component type
	 * @param string id Component ID
	 * @return object|null Component reference (NULL if invalid)
	 */
	get_component: function(type, id) {
		console.group('View.get_component');
		console.log('Type: %o \nID: %o', type, id);
		var ret = null;
		//Validate parameters
		if ( !this.util.is_func(type) ) {
			console.warn('Data type is invalid');
			console.groupEnd();
			return ret;
		}
		console.log('Component type is valid');

		//Sanitize id
		if ( !this.util.is_string(id) || this.util.is_empty(id) ) {
			console.log('ID is invalid, unsetting');
			id = null;
		}
		
		//Get component from collection
		var coll = this.get_components(type);
		console.log('Components: %o', coll);
		console.log('ID: %o', id);
		if ( this.util.is_obj(coll) ) {
			var tid = ( this.util.is_string(id) ) ? id : this.util.add_prefix('default');
			console.log('Checking for component: %o', tid);
			if ( tid in coll ) {
				console.log('Component found, retrieving');
				ret = coll[tid];
			}
		}
		
		//Default: Create default component
		if ( this.util.is_empty(ret) ) {
			console.log('Component does not exist\nID: %o \nType: %o \nReturn: %o', id, type.prototype._slug, ret);
			if ( !this.util.is_empty(id) || this.component_make_default(type) ) {
				console.log('Creating new component instance');
				ret = this.add_component(type, id);
			}
		}

		console.groupEnd();
		//Return component
		return ret;
	},
	
	/**
	 * Create new component instance and save to appropriate collection
	 * @param function type Component type to create
	 * @param string id ID of component
	 * @param object options Component initialization options (Default options used if default component is allowed)
	 * @return object|null New component (NULL if invalid)
	 */
	add_component: function(type, id, options) {
		//Validate type
		if ( !this.util.is_func(type) ) {
			return false;
		}
		//Validate request
		if ( ( this.util.is_empty(id) || !this.util.is_obj(options) || this.util.is_empty(options) ) && !this.component_make_default(type) ) {
			return false;
		}
		//Defaults
		var ret = null;
		if ( this.util.is_empty(id) ) {
			id = this.util.add_prefix('default');
		}
		if ( !this.util.is_obj(options) ) {
			options = {};
		}
		//Check if specialized method exists for component type
		var m = ( 'component' != type.prototype._slug ) ? 'add_' + type.prototype._slug : null;
		if ( !this.util.is_empty(m) && ( m in this ) && this.util.is_func(this[m]) ) {
			ret = this[m](id, options);
		}
		//Default process
		else {
			ret = new type(id, options);
		}
		
		//Add new component to collection
		if ( this.util.is_type(ret, type) ) {
			//Get collection
			var coll = this.get_components(type);
			//Add to collection
			switch ( $.type(coll) ) {
				case 'object' :
					coll[id] = ret;
					break;
				case 'array' :
					coll.push(ret);
					break;
			}
		} else {
			ret = null;
		}
		
		//Return new component
		return ret;
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
	
	/* Viewers */
	
	init_viewers: function() {
		//Reset viewers
		this.viewers = {};
		//Add default viewer
		this.add_viewer(this.util.add_prefix('default'));
	},
	
	add_viewer: function(v) {
		if ( !this.util.is_valid(v, this.util.string) )
			return false;
		//Create viewer
		var v = new this.Viewer(v);
		//Add to collection
		this.viewers[v.get_id()] = v;
	},
	
	get_viewers: function() {
		return this.viewers;
	},
	
	has_viewer: function(v) {
		return ( this.util.is_valid(v, this.util.string) && v in this.get_viewers() ) ? true : false;
	},
	
	get_viewer: function(v) {
		//Retrieve default viewer if specified viewer not set
		if ( !this.has_viewer(v) )
			v = this.util.add_prefix('default');
		return this.get_viewers()[v];
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
			t.show_item(this);
			return false;
		};
		
		//Get activated links
		var sel = 'a[href][rel~="' + this.get_feature('active') + '"]:not([rel~="' + this.get_feature('disabled') + '"])';
		console.log('Selector: %o', sel);
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
	 * Display item in viewer
	 */
	show_item: function(item) {
		console.groupCollapsed('View.show_item');
		//Parse item
		if ( ! this.util.is_type(item, this.Content_Item) ) {
			//Create new instance
			var item = new this.Content_Item(item);
			item.show();
		}
		console.groupEnd();
	},
	
	/* Group */
	
	/**
	 * Add new group
	 * @param string g Group ID
	 *  > If group with same ID already set, new group replaces existing one
	 * @param object attrs (optional) Group attributes
	 */
	add_group: function(g, attrs) {
		console.group('View.add_group');
		//Create new group
		g = new this.Group(g, attrs);
		console.log('New group: %o', g.get_id());
		//Add group to collection
		if ( this.util.is_valid(g.get_id(), this.util.string) ) {
			this.groups[g.get_id()] = g;
			console.log('Add group to collection');
		}
		console.groupEnd();
	},
	
	/**
	 * Retrieve groups
	 * @uses groups property
	 * @return object Registered groups
	 */
	get_groups: function() {
		return this.groups;
	},
	
	/**
	 * Retrieve specified group
	 * @param string g Group ID
	 * @return object|null Group instance (NULL if group does not exist)
	 */
	get_group: function(g) {
		console.group('View.get_group');
		if ( this.util.is_string(g) ) {
			if ( !this.has_group(g) ) {
				//Add new group (if necessary)
				this.add_group(g);
			}
			//Retrieve group
			g = this.get_groups()[g];
		}
		console.groupEnd();
		return ( this.util.is_type(g, this.Group) ) ? g : null;
	},
	
	/**
	 * Checks if group is registered
	 * @uses get_groups() to retrieve registered groups
	 * @return bool TRUE if group exists, FALSE otherwise
	 */
	has_group: function(g) {
		return ( this.util.is_valid(g, 'string') && ( g in this.get_groups() ) ) ? true : false;
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
		if ( !this.util.is_valid(id, this.util.string) )
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
	
	/**
	 * Add Theme Tag Handler to Theme prototype
	 * @param string id Unique ID
	 * @param function (optional) build Tag parser/builder
	 * @param obj attrs (optional) Default tag attributes/values
	 * @param bool dynamic (optional) Whether tag is dynamically rendered (per item) or not
	 */
	add_theme_tag: function(id, build, attrs, dynamic) {
		//Validate ID
		if ( this.util.is_valid(id, 'string', true) ) {
			//Create new instance
			var tag = new this.Theme_Tag(id, build, attrs, dynamic);
			//Add to Theme prototype
			this.Theme.prototype.tags[tag.get_id()] = tag;
		}
	}
};

/* Components */
var Component = {
	/*-** Properties **-*/
	
	/* Internal/Configuration */
	
	/**
	 * Base name of component type
	 */
	
	_slug: 'component',
	
	/**
	 * Valid component references for current component
	 * > Key (string): Property name that stores reference
	 * > Value (function): Data type of component
	 * @var object
	 */
	_refs: {},
	
	/**
	 * Components that may contain current object
	 * Used for retrieving data from a parent object
	 * Example: An Item may be contained by a Group
	 * > Value (strong): Property name of container component
	 * @var array
	 */
	_containers: [],
	
	_reciprocal: false,
	
	_el: null,
	
	_el_attr: null,
		
	_attributes: {},
	
	/* Public */
	
	attributes: false,
	
	/**
	 * Component ID
	 * @var string
	 */
	id: '',
	
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
	
	/* Components */
	
	/**
	 * Retrieve component containers
	 * @uses _container property
	 * @return array Component containers
	 */
	get_containers: function() {
		//Sanitize property
		if ( !this.util.is_array(this._containers) ) {
			this._containers = [];
		}
		//Return value
		return this._containers;
	},
	
	/**
	 * Check if current object has potential container objects
	 * @return bool TRUE if containers exist, FALSE otherwise
	 */
	has_containers: function() {
		return ( this.get_containers().length > 0 );
	},
	
	/**
	 * Retrieve object references
	 * @uses _refs
	 * @return obj References object

	 */
	get_references: function() {
		return this._refs;
	},
	
	/**
	 * Check if reference exists in object
	 * @param string ref Reference ID
	 * @return bool TRUE if reference exists, FALSE otherwise
	 */
	has_reference: function(ref) {
		return ( this.util.is_string(ref) && ( ref in this ) && ( ref in this.get_references() ) ) ? true : false;
	},
	
	/**
	 * Retrieve reference data type
	 * @param string ref Reference ID
	 * @return function Reference data type (NULL if invalid)
	 */
	get_reference: function(ref) {
		return ( this.has_reference(ref) ) ? this._refs[ref] : null;
	},
	
	/**
	 * Retrieve component reference from current object
	 * > Procedure:
	 *   > Check if property already set
	 *   > Check attributes
	 *   > Check container object(s)
	 * 	 > Check parent object (controller)
	 * @uses _containers to check potential container components for references
	 * @param string cname Component name
	 * @param bool get_default (optional) Whether or not to retrieve default object from controller if none exists in current instance (Default: TRUE) 
	 * @return object Component reference (FALSE if no component found)
	 */
	get_component: function(cname, get_default) {
		console.group('Component.get_component');
		console.log('Property: %o \nType: %o \nGet Default: %o', cname, ctype, get_default);
		var c = null;
		//Validate request
		if ( !this.util.is_string(cname) || this.util.is_empty(cname) || !( cname in this ) || !this.has_reference(cname) ) {
			console.warn('Request is invalid, quitting');
			console.groupEnd();
			return c;
		}
		
		//Normalize parameters
		if ( !this.util.is_bool(get_default) ) {
			get_default = true;
		}
		var ctype = this._refs[cname];

		//Phase 1: Check if component reference previously set
		console.info('Check for property');
		if ( this.util.is_type(this[cname], ctype) ) {
			console.log('Component is set returning immediately: %o', this[cname]);
			console.groupEnd();
			return this[cname];
		}
		
		//If reference not set, iterate through component hierarchy until reference is found
		c = this[cname] = null;
				
		//Phase 2: Check attributes
		console.info('Check for component in attributes');
		c = this.get_attribute(cname);
		console.log('Attribute value: %o', c);
		//Save object-specific component reference
		if ( !this.util.is_empty(c) ) {
			console.log('Saving component');
			c = this.set_component(cname, c);
		}

		//Phase 3: Check Container(s)
		if ( this.util.is_empty(c) && this.has_containers() ) {
			console.info('Checking object container(s)');
			var containers = this.get_containers();
			console.log('Containers: %o', containers);
			var con = null;
			for ( var i = 0; i < containers.length; i++ ) {
				con = containers[i];
				console.groupCollapsed('Container %d : %s', i, con);
				//Validate container
				if ( con == cname ) {
					console.warn('Container is current component, skipping');
					console.groupEnd();
					continue;
				}
				//Retrieve container
				con = this.get_component(con, false);
				console.log('Container: %o', con);
				if ( this.util.is_empty(con) ) {
					console.warn('Container could not be found, skipping');
					console.groupEnd();
					continue;
				}
				console.log('Check for component in container: %o', con);
				//Attempt to retrieve component from container
				c = con.get_component(cname);
				console.info('Component: %o', c);
				//Stop iterating if valid component found
				if ( !this.util.is_empty(c) ) {
					console.groupEnd();
					break;
				}
				console.groupEnd();
			}
		}
		
		//Phase 4: From controller (optional)
		if ( get_default && this.util.is_empty(c) ) {
			console.info('Get default component (from controller)');
			c = this.get_parent().get_component(ctype);
		}
		console.log('Component: %o', c);
		console.groupEnd();		
		return c;
	},
	
	/**
	 * Sets component reference on current object
	 *  > Component property reset (set to NULL) if invalid component supplied
	 * @param string name Name of property to set component on object
	 * @param string|object ref Component or Component ID (to be retrieved from controller)
	 * @param function type Component type
	 * @return object Component (NULL if invalid)
	 */
	set_component: function(name, ref) {
		console.groupCollapsed('Component.set_component');
		console.log('Component: %o \nObject: %o', name, ref);
		//Make sure component property exists
		if ( !this.has_reference(name) )
			return null;
		var ctype = this.get_reference(name); 
		
		//Get component from controller if ID supplied
		if ( this.util.is_string(ref) ) {
			c = this.get_parent().get_component(ctype, ref);
		}
		
		//Set (or clear) component reference
		this[name] = ( this.util.is_type(ref, ctype) ) ? ref : null;
		console.groupEnd();
		//Return value for confirmation
		return this[name];
	},

	/* Attributes */
	
	parse_attributes: function(attributes) {
		console.groupCollapsed('Item.parse_attributes');
		//Reset attributes
		this.set_attributes(attributes, true);
		
		el = this.get_element();
		if ( !this.util.is_empty(el) ) {
			//Get attributes from element
			var opts = $(el).attr(this._el_attr);
			if ( opts ) {
				opts = opts.split(' ');
				var wrap = {
					open: '[',
					close: ']' 
				};
				var attrs = {};
				var attr = key = val = open = null;
				var prefix = this.util.add_prefix('');
				
				for ( var x = 0; x < opts.length; x++ ) {
					attr = opts[x];
					//Process options
					if ( attr.indexOf(prefix) === 0 ) {
						//Set attributes
						if ( attr.indexOf(wrap.close) === ( attr.length - 1 ) ) {
							//Strip prefix
							open = attr.indexOf(wrap.open);
							key = attr.substring(prefix.length, open);
							val = attr.substring(open + 1, attr.length - 1);
							//Set attribute
							this.set_attribute(key, val);
							continue;
						}
						//Set flags
						this.set_attribute(attr.substr(prefix.length), true);
					}
				}
			}
		}
		console.groupEnd();
	},
	
	get_attributes: function() {
		console.groupCollapsed('Item.get_attributes()');
		//Parse attributes on first access
		if ( this.util.is_bool(this.attributes) ) {
			console.log('Attibutes need to be initialized');
			this.parse_attributes();
		}
		console.log('Attributes retrieved: %o', this.attributes);
		console.groupEnd();
		return this.attributes;
	},
	
	/**
	 * Retrieve value of specified attribute for value
	 * @param string key Attribute to retrieve
	 * @param mixed def (optional) Default value if attribute is not set
	 * @return mixed Attribute value (NULL if attribute is not set)
	 */
	get_attribute: function(key, def) {
		console.log('Getting attribute: %o', key);
		if ( !this.util.is_set(def) )
			def = null;
		var a = this.get_attributes();
		return ( key in this.get_attributes() ) ? this.attributes[key] : def;
	},
	
	set_attributes: function(attributes, full) {
		if ( !this.util.is_bool(full) )
			full = false;
		//Reset attributes
		if ( full || this.util.is_empty(this.attributes) ) {
			this.attributes = $.extend({}, this._attributes);
		}
		
		//Merge new/existing attributes
		if ( $.isPlainObject(attributes) && !this.util.is_empty(attributes) ) {
			$.extend(this.attributes, attributes);
		}
	},
	
	set_attribute: function(key, val) {
		if ( this.util.is_valid(key, 'string') && this.util.is_set(val, true) ) {
			this.get_attributes()[key] = val;
		}
	},
	
	/* DOM Element */
	
	get_element: function() {
		return $(this._el);
	},
	
	/**
	 * Set reference of instance on DOM element
	 */
	set_ref: function(el) {
		var key = this.util.add_prefix(this._slug);
		$(el).data(key, this);
		if ( this._reciprocal )
			this._el = $(el);
	},
};

Component = SLB.Class.extend(Component);

/**
 * Content viewer
 * @param obj options Init options
 */
var Viewer = {
	
	/* Properties */
	_slug: 'viewer',
	
	/* References */
	
	item: null,
	
	theme: null,
	
	/* Status */
	
	loading: false,
	
	_c: function(id, attributes) {
		//Set ID
		this.set_id(id);
		//Set attributes
		this.set_attributes(attributes);
	},
	
		
	/* Setup */
	
	set_item: function(item) {
		console.groupCollapsed('Viewer.set_item');
		if ( this.util.is_type(item, View.Content_Item) ) {
			console.log('Item set: %o', item);
			this.item = item;
			return true;
		} else {
			this.item = null;
		}
		console.groupEnd();
		return false;
	},
	
	/**
	 * Sets loading mode
	 * @param bool mode (optional) Set (TRUE) or unset (FALSE) loading mode (Default: TRUE)
	 */
	set_loading: function(mode) {
		if ( !this.util.is_bool(mode) )
			loading = true;
		this.loading = loading;
	},
	
	/**
	 * Retrieve loading status
	 * @return bool Loading status (Default: FALSE)
	 */
	get_loading: function() {
		return ( this.util.is_bool(this.loading) ) ? this.loading : false;
	},
	
	/**
	 * Check if viewer is currently loading content
	 * @return bool Loading status (Default: FALSE)
	 */
	is_loading: function() {
		return this.get_loading(); 
	},
	
	/* Display */
	
	/**
	 * Display content in lightbox
	 */
	show: function(item) {
		console.groupCollapsed('Viewer.show');
		console.log('Add item reference');
		//Add item reference to viewer
		i = this.set_item(item);
		//Make sure item was properly set
		if ( !i ) {
			this.exit();
			return false;
		}
		console.log('Set loading flag');
		this.set_loading();
		console.groupEnd();
	},
	
	exit: function() {
		this.reset();
	},
	
	reset: function() {
		this.set_item(false);
		this.set_loading(false);
	},
	
	/* Theme */
	
	/**
	 * Retrieve theme reference
	 * @return object Theme reference
	 */
	get_theme: function() {
		return this.get_component('theme', View.Theme);
	},
	
	/**
	 * Set viewer's theme
	 * @param object theme Theme object
	 */
	set_theme: function(theme) {
		//Get theme using ID
		if ( this.util.is_string(theme) ) {
			this.get_parent().get_theme(theme);
		}
		//Set theme for viewer
		if ( this.util.is_type(theme, View.Theme) ) {
			this.theme = theme;
		}
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
	
	/* Properties */
	_slug: 'group',
	
	items: [],
	
	viewer: null,
	
	_c: function(id, attributes) {
		console.log('New Group');
		this.set_id(id);
		this.set_attributes(attributes);
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
		
	},
	
	get_viewer: function() {
		return false;
	}
};

View.Group = Component.extend(Group);

/**
 * Content type
 * @param obj options Init options
 */
var Content_Type = {
	
	/* Properties */
	
	_slug: 'content_type',
	
	item: null,
		
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
	
	_slug: 'content_item',
	_reciprocal: true,
	_refs: {
		'viewer': View.Viewer,
		'group': View.Group
	},
	_containers: ['group'],
	
	group: null,
	viewer: null,
	content_type: null,
	
	_el_attr: 'rel',
		
	_attributes: {
		src: null,
		permalink: null,
		title: '',
		group: null,
		internal: false
	},
	
	/* Init */
	
	_c: function(el) {
		console.log('New Content Item');
		//Save element to instance
		this.set_ref(el);
	},
	
	/* Methods */
	
	/*-** Instances **-*/
	
	get_viewer: function() {
		return this.get_component('viewer');
		// return v;
	},
	
	/**
	 * Sets item's viewer property
	 * @uses View.get_viewer() to retrieve global viewer
	 * @uses this.viewer to save item's viewer
	 * @param string|View.Viewer v Viewer to set for item
	 *  > Item's viewer is reset if invalid viewer provided
	 */
	set_viewer: function(v) {
		if ( this.util.is_valid(v, this.util.string) && this.get_parent().has_viewer(v) ) {
			v = this.get_parent().get_viewer(v);
		}
		
		//Set or clear viewer property
		this.viewer = ( this.util.is_type(v, View.Viewer) ) ? v : false;
		
		//Return value for confirmation
		return this.viewer;
	},

	has_group: function() {
		return ( this.util.is_set(this.get_group()) ) ? true : false;
	},

	/**
	 * Retrieve item's group
	 * @param obj item Item to get group from
	 * @return View.Group|bool Group reference item belongs to (FALSE if no group)
	 */
	get_group: function() {
		console.groupCollapsed('Item.get_group');
		var g = this.get_component('group', View.Group);
		/*
		//Check if group already set
		if ( !this.util.is_type(this.group, View.Group) && !this.util.is_bool(this.group) ) {
			//If group not set, check attributes
			var g = this.get_attribute('group');
			console.log('Group attribute: %o', g);
			this.set_group(g);
		}
		*/
		console.groupEnd();
		return this.group;
	},
	
	/**
	 * Sets item's group property
	 * @uses View.get_group() to retrieve global group
	 * @uses this.group to set item's group
	 * @param string|View.Group g Group to set for item
	 *  > Item's group is reset if invalid group provided
	 */
	set_group: function(g) {
		console.groupCollapsed('Item.set_group');
		console.log('Group: %o', g);
		//If group ID set, get object reference
		if ( this.util.is_string(g) ) {
			g = this.get_parent().get_group(g);
		}
		
		//Set (or clear) group property
		this.group = ( this.util.is_type(g, View.Group) ) ? g : false;
		console.groupEnd();
	},
	
	get_type: function() {
		
	},
	
	set_type: function(type) {
		
	},
	
	/* Actions */
	
	show: function() {
		console.groupCollapsed('Item.show');
		//Retrieve viewer
		var v = this.get_viewer();
		console.log('Viewer retrieved: %o', v);
		// v.show(item);
		console.groupEnd();
	}
};

View.Content_Item = Component.extend(Content_Item);

/**
 * Theme
 * @param obj options Init options
 */
var Theme = {
	
	/* Properties */
	
	_slug: 'theme',
	
	viewer: null,
	group: null,
	item: null,
	
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
	
	/**
	 * Layout tags
	 * Processes tag
	 * @var Theme_Tag
	 */
	tags: {},
	
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
		console.log('Name: %o \nLayout: %o', this.get_id(), this.get_layout());
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
	
	/**
	 * Render Theme output
	 * @return string Theme output
	 */
	render: function() {
		//Parse layout
		return this.parse_layout();
	},
};

View.Theme = Component.extend(Theme);

/**
 * Theme tag handler
 */
var Theme_Tag = {
	/* Properties */
	
	_slug: 'theme_tag',
	
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
		this.dynamic = ( this.util.is_valid(dynamic, 'boolean', true) ) ? dynamic : false;
	}
};

View.Theme_Tag = Component.extend(Theme_Tag);

//Attach to global object
SLB.attach('View', View);
View = SLB.View;

})(jQuery);