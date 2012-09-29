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

	/**
	 * Media item properties
	 * > Item key: Link URI
	 * > Base properties
	 *   > id: WP Attachment ID
	 *   > source: Source URI
	 *   > title: Media title (generally WP attachment title)
	 *   > desc: Media description (generally WP Attachment content)
	 *   > type: Asset type (attachment, image, etc.)
	 */
	assets: {},
	
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
	template_tags: {},
	
	/**
	 * Collection/Data type mapping
	 * > Key: Collection name
	 * > Value: Data type
	 * @var object
	 */
	collections: {},
	
	/**
	 * Temporary component instances
	 * For use by controller when no component instance is available
	 * > Key: Component slug
	 * > Value: Component instance 
	 */
	component_temps: {},
	
	/* Options */
	options: {
		validate_links: false,
		ui_animate: true,
		ui_enabled_desc: true,
		ui_enabled_caption: true,
		ui_caption_src: true,
		slideshow_enabled: true,
		slideshow_autostart: false,
		slideshow_duration: '6',
	},
	
	/* Methods */
	
	/* Init */
	
	update_refs: function() {
		console.groupCollapsed('Updating component references');
		var c;
		var r;
		var ref;
		for ( var p in this ) {
			if ( !this.util.is_func(this[p]) || !( '_refs' in this[p].prototype ) ) {
				continue;
			}
			console.groupCollapsed('Processing component: %o', p);
			//Set component
			c = this[p];
			if ( !this.util.is_empty(c.prototype._refs) ) {
				for ( r in c.prototype._refs ) {
					ref = c.prototype._refs[r];
					if ( this.util.is_func(ref) ) {
						continue;
					}
					if ( this.util.is_string(ref) && ref in this ) {
						ref = c.prototype._refs[r] = this[ref];
					}
					if ( !this.util.is_func(ref) ) {
						delete c.prototype_refs[r];
					}
				}
			}
			console.groupEnd();
		}
		
		/* Initialize components */
		console.log('Initializing components');
		this.init_components();
		
		console.groupEnd();
	},
	
	/**
	 * Initialization
	 */
	init: function(options) {
		console.groupCollapsed('View.init');
		//Set options
		$.extend(true, this.options, options);
		console.groupCollapsed('Options');
		console.dir(this.options);
		console.groupEnd();
		
		/* Set defaults */
		
		//Items
		this.init_items();
		console.info('Init complete');
		console.groupEnd();
	},
	
	init_components: function() {
		this.collections = {
			'viewers':	 		this.Viewer,
			'items': 			this.Content_Item,
			'content_types': 	this.Content_Type,
			'groups': 			this.Group,
			'themes': 			this.Theme,
			'template_tags': 	this.Template_Tag
		};
		
		this.component_defaults = [
			this.Viewer,
		];
	
	},
	
	/* Components */

	component_make_default: function(type) {
		console.groupCollapsed('View.component_make_default');
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
	 * Validates component type
	 * @param function comp Component type to check
	 * @return bool TRUE if param is valid component, FALSE otherwise
	 */
	check_component: function(comp) {
		//Validate component type
		return ( this.util.is_func(comp) && ('_slug' in comp.prototype ) && ( comp.prototype instanceof (this.Component) ) ) ? true : false;
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
			for ( var coll in this.collections ) {
				if ( type == this.collections[coll] && coll in this ) {
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
		console.groupCollapsed('View.get_component');
		console.log('Type: %o \nSlug: %o \nID: %o', type, type.prototype._slug, id);
		var ret = null;
		//Validate parameters
		if ( !this.util.is_func(type) ) {
			console.warn('Data type is invalid');
			console.groupEnd();
			return ret;
		}
		console.info('Component type is valid');

		//Sanitize id
		if ( !this.util.is_string(id) ) {
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
			console.warn('Component does not exist\nID: %o \nType: %o \nReturn: %o', id, type.prototype._slug, ret);
			if ( this.util.is_string(id) || this.component_make_default(type) ) {
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
		console.group('View.add_component');
		console.log('Type: %o \nID: %o \nOptions: %o', type, id, options);
		//Validate type
		if ( !this.util.is_func(type) ) {
			console.warn('Invalid type');
			console.groupEnd();
			return false;
		}
		//Validate request
		if ( this.util.is_empty(id) && !this.component_make_default(type) ) {
			console.warn('Invalid request');
			console.groupEnd();
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
			console.log('Passing to specialized constructor');
			ret = this[m](id, options);
		}
		//Default process
		else {
			console.log('Calling constructor directly');
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
		console.groupEnd();
		//Return new component
		return ret;
	},
	
	/**
	 * Create new temporary component instance
	 * @param function type Component type
	 * @return New temporary instance
	 */
	add_component_temp: function(type) {
		var ret = null;
		if ( this.check_component(type) ) {
			//Create new instance
			ret = new type('');
			//Save to collection
			this.component_temps[ret._slug] = ret;
		}
		return ret;
	},
	
	/**
	 * Retrieve temporary component instance
	 * Creates new temp component instance for type if not previously created
	 * @param function type Component type to retrieve temp instance for
	 * @return obj Temporary component instance
	 */
	get_component_temp: function(type) {
		console.info('Get default component: %o', type.prototype._slug);
		return ( this.has_component_temp(type) ) ? this.component_temps[type.prototype._slug] : this.add_component_temp(type);
	},
	
	/**
	 * Check if temporary component instance exists
	 * @param function type Component type to check for
	 * @return bool TRUE if temp instance exists, FALSE otherwise 
	 */
	has_component_temp: function(type) {
		return ( this.check_component(type) && ( type.prototype._slug in this.component_temps ) ) ? true : false;
	},
	
	/* Properties */
	
	/**
	 * Retrieve specified options
	 * @param array opts Array of option names
	 * @return object Specified options (Default: empty object)
	 */
	get_options: function(opts) {
		console.groupCollapsed('View.get_options');
		console.info('Options to get: %o', opts);
		var ret = {};
		//Validate
		if ( this.util.is_string(opts) ) {
			opts = [opts];
		}
		if ( !this.util.is_array(opts) ) {
			console.warn('No options specified');
			console.groupEnd();
			return ret;
		}
		//Get specified options
		for ( var x = 0; x < opts.length; x++ ) {
			//Skip if option not set
			if ( !( opts[x] in this.options ) ) {
				continue;
			}
			ret[ opts[x] ] = this.options[ opts[x] ]; 
		}
		console.info('Options retrieved: %o', ret);
		console.groupEnd();
		return ret;
	},
	
	/**
	 * Retrieve option
	 * @uses View.options
	 * @param string opt Option to retrieve
	 * @param mixed def (optional) Default value if option does not exist (Default: NULL)
	 * @return mixed Option value
	 */
	get_option: function(opt, def) {
		var ret = this.get_options(opt);
		if ( this.util.is_obj(ret) && ( opt in ret ) ) {
			ret = ret[opt];
		} else {
			ret = ( this.util.is_set(def) ) ? def : null;
		}
		return ret;
	},
	
	/* Viewers */
	
	/**
	 * Add viewer instance to collection
	 * @param string id Viewer ID
	 * @param obj options Viewer options
	 */
	add_viewer: function(id, options) {
		console.groupCollapsed('View.add_viewer');
		console.log('ID: %o \nOptions: %o', id, options);
		//Validate
		if ( !this.util.is_string(id) ) {
			console.groupEnd();
			return false;
		}
		if ( !this.util.is_obj(options, false) ) {
			options = {};
		}
		//Create viewer
		var v = new this.Viewer(id, options);
		//Add to collection
		this.viewers[v.get_id()] = v;
		console.groupEnd();
		//Return viewer
		return v;
	},
	
	/**
	 * Retrieve all viewer instances
	 * @return obj Viewer instances
	 */
	get_viewers: function() {
		return this.viewers;
	},
	
	/**
	 * Check if viewer exists
	 * @param string v Viewer ID
	 * @return bool TRUE if viewer exists, FALSE otherwise
	 */
	has_viewer: function(v) {
		return ( this.util.is_string(v) && v in this.get_viewers() ) ? true : false;
	},
	
	/**
	 * Retrieve Viewer instance
	 * Default viewer retrieved if specified viewer does not exist
	 * > Default viewer created if necessary
	 * @param string v Viewer ID to retrieve
	 * @return Viewer Viewer instance
	 */
	get_viewer: function(v) {
		//Retrieve default viewer if specified viewer not set
		if ( !this.has_viewer(v) ) {
			v = this.util.add_prefix('default');
			//Create default viewer if necessary
			if ( !this.has_viewer(v) ) {
				this.add_viewer(v);
			}
		}
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
		var sel = 'a[href][%s="%s"]'.sprintf(this.util.get_attribute('active'), 1);
		console.log('Selector: %o \nItems: %o', sel, $(sel));
		//Add event handler
		$(sel).click(handler);
		console.groupEnd();
	},
	
	/**
	 * Retrieve specific Content_Item instance
	 * @param obj el DOM node to get item instance for
	 * @return Content_Item Item instance for DOM node
	 */
	get_item: function(el) {
		console.groupCollapsed('View.get_item');
		console.log('Element: %o', el);
		//Check if item instance attached to element
		var key = this.get_component_temp(this.Content_Item).get_data_key();
		console.log('Data Key: %o', key);
		var item = $(el).data(key);
		if ( this.util.is_empty(item) ) {
			console.log('Creating new content item');
			item = this.add_item(el);		}
		console.groupEnd();
		return item;
	},
	
	/**
	 * Create new item instance
	 * @param obj el DOM element representing item
	 * @return Content_Item New item instance
	 */
	add_item: function(el) {
		console.groupCollapsed('View.add_item');
		var item = new this.Content_Item(el);
		console.log('Item: %o \nInstance: %o', el, item);
		console.log('Item ID: %o', item.get_attribute('id'));
		console.groupEnd();
		return item;
	},
	
	/**
	 * Display item in viewer
	 * @param obj el DOM element representing item
	 */
	show_item: function(el) {
		console.group('View.show_item');
		this.get_item(el).show();
		console.groupEnd();
	},
	
	/* Content Type */
	
	get_content_types: function() {
		return this.get_components(this.Content_Type);
	},
	
	/**
	 * Find matching content type for item
	 * @param Content_Item item Item to find type for
	 * @return Content_Type|null Matching content type (NULL if no matching type found) 
	 */
	get_content_type: function(item) {
		console.groupCollapsed('View.get_content_type');
		//Check for source URI
		var types = this.get_content_types();
		//Iterate through types until match found
		var pri = Object.keys(types).sort(function(a, b){return a - b;});
		console.log('Type Priorities: %o', pri);
		var g, type, match;
		console.log('Iterating by priority');
		for ( var p = 0; p < pri.length; p++ ) {
			console.log('Priority: %o', pri[p]);
			g = types[pri[p]];
			console.log('Items in group: %o', g.length);
			console.dir(g);
			for ( var x = 0; x < g.length; x++ ) {
				console.groupCollapsed('Checking Type: %o', g[x]);
				type = g[x];
				console.log('Checking Type Match: %o', type);
				if ( type.match(item) ) {
					console.info('Matching type found: %o', type.get_id());
					console.groupEnd();
					console.groupEnd();
					return type;
				}
				console.groupEnd();
			}
		}
		console.groupEnd();
		return null;
	},
	
	add_content_type: function(id, attributes, priority) {
		console.groupCollapsed('View.add_content_type');
		//Validate
		if ( !this.util.is_string(id) ) {
			console.error('ID not set');
			console.groupEnd();
			return false;
		}
		if ( !this.util.is_obj(attributes, false) ) {
			attributes = {};
		}
		if ( !this.util.is_int(priority) ) {
			priority = 10;
		}
		console.log('Default values set\nID: %o \nAttr: %o \nPriority: %o', id, attributes, priority);
		console.info('Saving content type properties');
		//Save
		var types = this.get_components(this.Content_Type);
		console.log(types);
		if ( !this.util.is_obj(types, false) ) {
			console.log('Init types');
			types = {};
		}
		if ( !(priority in types) ) {
			types[priority] = [];
		}
		types[priority].push(new this.Content_Type(id, attributes));
		console.log('Types: %o \nCollection: %o', types, this.get_components(this.Content_Type));
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
		console.groupCollapsed('View.add_group');
		//Create new group
		g = new this.Group(g, attrs);
		console.log('New group: %o', g.get_id());
		//Add group to collection
		if ( this.util.is_string(g.get_id()) ) {
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
		console.groupCollapsed('View.get_group');
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
		return ( this.util.is_string(g) && ( g in this.get_groups() ) ) ? true : false;
	},
	
	/* Theme */
	
	/**
	 * Initialize themes
	 */
	init_themes: function() {
		console.groupCollapsed('View.init_themes');
		//Validate models property
		if ( !this.util.is_obj(this.Theme.prototype._models, false) ) {
			this.Theme.prototype._models = {};
		}
		console.groupEnd();
	},
	
	/**
	 * Add theme
	 * @param string name Theme name
	 * @param obj attr Theme options
	 * > Multiple attribute parameters are merged
	 * @return void
	 */
	add_theme: function(id, attr) {
		console.groupCollapsed('View.add_theme');
		console.log('ID: %o \nAttributes: %o', id, attr);
		//Validate
		if ( !this.util.is_string(id) ) {
			console.groupEnd();
			return false;
		}
		
		//Build attributes
		var attrs = [{'layout_raw': '', 'layout_parsed': ''}];
		if ( arguments.length >= 2 ) {
			var args = Array.prototype.slice.call(arguments, 1);
			var t = this;
			$.each(args, function(idx, arg) {
				if ( t.util.is_obj(arg, false) ) {
					attrs.push(arg);
				}
			});
		}
		
		//Create theme model
		var model = $.extend.apply(null, attrs);
		
		//Initialize models object
		if ( this.util.is_obj(model, false) ) {
			this.init_themes();
			
			//Add theme model
			this.Theme.prototype._models[id] = model;
		}
		console.groupEnd();
	},
	
	/**
	 * Retrieve theme models
	 * @return obj Theme models
	 */
	get_theme_models: function() {
		//Check prototype for theme models
		if ( !this.util.is_obj(this.Theme.prototype._models) ) {
			//Initialize theme models
			this.init_themes();
		}
		//Retrieve matching theme model
		return this.Theme.prototype._models;
	},
	
	/**
	 * Add Theme Tag Handler to Theme prototype
	 * @param string id Unique ID
	 * @param obj attrs (optional) Default tag attributes/values
	 * @return Template_Tag_Handler Tag Handler instance
	 */
	add_template_tag_handler: function(id, attrs, add) {
		//Validate
		if ( !this.util.is_bool(add) ) {
			add = true;
		}
		if ( !this.util.is_string(id) ) {
			add = false;
		}
		//Create new instance
		var handler = new this.Template_Tag_Handler(id, attrs);
		if ( add ) {
			//Add to collection in Template_Tag prototype
			this.get_template_tag_handlers()[handler.get_id()] = handler;
		}
		//Return instance
		return handler;
	},
	
	/**
	 * Retrieve Template Tag Handler collection
	 * @return obj Template_Tag_Handler objects
	 */
	get_template_tag_handlers: function() {
		return this.Template_Tag.prototype.handlers;
	},
	
	/**
	 * Retrieve template tag handler
	 * @param string id ID of tag handler to retrieve
	 * @return Template_Tag_Handler Tag Handler instance (new instance for invalid ID)
	 */
	get_template_tag_handler: function(id) {
		return ( this.util.is_string(id) && ( id in this.get_template_tag_handlers() ) ) ? this.get_template_tag_handlers()[id] : this.add_template_tag_handler(id, {}, false);
	},
};

/* Components */
var Component = {
	/*-** Properties **-*/
	
	/* Internal/Configuration */
	
	/**
	 * Base name of component type
	 * @var string
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
	
	/**
	 * Whether DOM element and component are connected in 1:1 relationship
	 * Some components will be assigned to different DOM elements depending on usage
	 * @var bool
	 */
	_reciprocal: false,
	
	/**
	 * DOM Element tied to component
	 * @var DOM Element 
	 */
	_dom: null,

	/**
	 * Default attributes
	 * @var object
	 */
	_attr_default: {},
	
	/**
	 * Attributes passed to constructor
	 * @var obj
	 */
	_attr_init: null,
	
	/**
	 * Attributes to retrieve from parent (controller)
	 * @var array
	 */
	_attr_parent: [],
	
	/**
	 * Defines how parent properties should be remapped to component properties
	 * @var object
	 */
	_attr_map: {},
	
	/**
	 * Event handlers
	 * @var object
	 * > Key: string Event type
	 * > Value: obj Handlers
	 *   > Key: string Context
	 *   > Value: array Handlers
	 */
	_events: {},
	
	/* Public */
	
	attributes: false,
	
	/**
	 * Component ID
	 * @var string
	 */
	id: '',
	
	/* Init */
	
	_c: function(id, attributes) {
		console.groupCollapsed('Component.Constructor: %o', this._slug);
		//Set ID
		this.set_id(id);
		console.info('ID set: %o', id);
		
		//Save init attributes
		console.info('Setting attributes: %o', attributes);
		this._attr_init = attributes;
		console.groupEnd();
	},
	
	_set_parent: function() {
		this._parent = View;
		this.util._parent = this;
	},
	
	/* Methods */
	
	/**
	 * Retrieve instance ID
	 * @uses id as ID base
	 * @uses _slug to add namespace (if necessary)
	 * @param bool ns (optional) Whether or not to namespace ID (Default: FALSE)
	 * @return string Instance ID
	 */
	get_id: function(ns) {
		//Validate
		if ( !this.check_id() ) {
			this.id = '';
		}
		var id = this.id;
		//Namespace ID
		if ( this.util.is_bool(ns) && ns ) {
			id = this.add_ns(id);
		}
		
		return id;
	},
	
	/**
	 * Set instance ID
	 * @param string id Unique ID
	 */
	set_id: function(id) {
		this.id = ( this.check_id(id) ) ? id : '';
	},
	
	/**
	 * Validate ID value
	 * @param string id (optional) ID value (Default: Component ID)
	 * @param bool nonempty (optional) TRUE if it should also check for empty strings, FALSE otherwise (Default: FALSE)
	 * @return bool TRUE if ID is valid, FALSE otherwise
	 */
	check_id: function(id, nonempty) {
		//Validate
		if ( arguments.length == 1 && this.util.is_bool(arguments[0]) ) {
			nonempty = arguments[0];
			id = null;
		}
		if ( !this.util.is_set(id) ) {
			id = this.id;
		}
		if ( !this.util.is_bool(nonempty) ) {
			nonempty = false;
		}
		return ( this.util.is_string(id, nonempty) ) ? true : false;
	},
	
	/**
	 * Get namespace
	 * @uses _slug for namespace segment
	 * @uses Util.add_prefix() to prefix slug
	 * @return string Component namespace
	 */
	get_ns: function() {
		return this.util.add_prefix(this._slug);
	},
	
	/**
	 * Add namespace to value
	 * @param string val Value to namespace
	 * @return string Namespaced value (Empty string if invalid value provided)
	 */
	add_ns: function(val) {
		return ( this.util.is_string(val) ) ? this.get_ns() + '_' + val : '';
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
	 * Check if reference exists in object
	 * @param string ref Reference ID
	 * @return bool TRUE if reference exists, FALSE otherwise
	 */
	has_reference: function(ref) {
		return ( this.util.is_string(ref) && ( ref in this ) && ( ref in this.get_references() ) ) ? true : false;
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
	 * Retrieve reference data type
	 * @param string ref Reference ID
	 * @return function Reference data type (NULL if invalid)
	 */
	get_reference: function(ref) {
		return ( this.has_reference(ref) ) ? this._refs[ref] : null;
	},
	
	/**
	 * Checks if component is valid
	 * @param obj|string Component instance or ID
	 *  > If ID is specified then it will check for component on current instance
	 * @param function|string ctype Component type
	 *  > If component is an object, then ctype is required
	 *  > If component is string ID, then ctype is optional (Default: reference type)
	 *  > If ctype is a function, then it is compared to component directly
	 *  > If ctype is a string, then the component reference type is retrieved
	 * @uses get_reference()
	 * @return bool TRUE if component is valid, FALSE otherwise
	 */
	check_component: function(comp, ctype) {
		//Validate
		if ( this.util.is_empty(comp)
			|| ( this.util.is_obj(comp) && !this.util.is_func(ctype) )
			|| ( this.util.is_string(comp) && !this.has_reference(comp) )
			|| ( this.util.is_empty(ctype) && !this.util.is_string(comp) )
			|| ( !this.util.is_obj(comp) && !this.util.is_string(comp) )
		) {
			return false;
		}
		//Get component type
		if ( !this.util.is_func(ctype) ) {
			//Component is a string ID
			ctype = this.get_reference(comp);
		}
		//Get component instance
		if ( this.util.is_string(comp) ) {
			comp = this.get_component(comp, false);
		}
		return this.util.is_type(comp, ctype);
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
	 * @param bool check_attr (optional) Whether or not to check instance attributes for component (Default: TRUE)
	 * @param bool get_default (optional) Whether or not to retrieve default object from controller if none exists in current instance (Default: TRUE)
	 * @param bool recursive (optional) Whether or not to check containers for specified component reference (Default: TRUE) 
	 * @return object|null Component reference (NULL if no component found)
	 */
	get_component: function(cname, check_attr, get_default, recursive) {
		console.groupCollapsed('Component.get_component(): %o', cname);
		console.log('Property: %o \nGet Default: %o \nRecursive: %o', cname, get_default, recursive);
		var c = null;
		//Validate request
		if ( !this.util.is_string(cname) || !( cname in this ) || !this.has_reference(cname) ) {
			console.warn('Request is invalid, quitting\nName: %o \nValid Property: %o \nHas Reference: %o \nReferences: %o', cname, (cname in this), this.has_reference(cname), this._refs);
			console.groupEnd();
			return c;
		}
		
		//Normalize parameters
		if ( !this.util.is_bool(check_attr) ) {
			check_attr = true;
		}
		if ( !this.util.is_bool(get_default) ) {
			get_default = true;
		}
		if ( !this.util.is_bool(recursive) ) {
			recursive = true;
		}
		var ctype = this._refs[cname];
		
		console.log('Validated Parameters\nProperty: %o \nType: %o \nGet Default: %o \nRecursive: %o', cname, ctype.prototype._slug, get_default, recursive);

		//Phase 1: Check if component reference previously set
		console.info('Check for property');
		if ( this.util.is_type(this[cname], ctype) ) {
			console.log('Component is set returning immediately: %o', this[cname]);
			console.groupEnd();
			return this[cname];
		}
		console.warn('First-class property not set');
		//If reference not set, iterate through component hierarchy until reference is found
		c = this[cname] = null;
				
		//Phase 2: Check attributes
		if ( check_attr ) {
			console.info('Check for component in attributes');
			console.log('Attributes: %o', this.get_attributes());
			c = this.get_attribute(cname);
			console.log('Attribute value: %o', c);
			//Save object-specific component reference
			if ( !this.util.is_empty(c) ) {
				console.log('Saving component');
				c = this.set_component(cname, c);
			}
		}

		//Phase 3: Check Container(s)
		if ( recursive && this.util.is_empty(c) && this.has_containers() ) {
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
				con = this.get_component(con, true, false);
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
	 * @param function validate (optional) Additional validation to be performed (Must return bool: TRUE (Valid)/FALSE (Invalid))
	 * @return object Component (NULL if invalid)
	 */
	set_component: function(name, ref, validate) {
		console.groupCollapsed('Component.set_component: %o', name);
		console.log('Component: %o \nObject: %o', name, ref);
		var clear = null;
		//Make sure component property exists
		if ( !this.has_reference(name) ) {
			console.groupEnd();
			return clear;
		}
		//Normalize reference
		if ( this.util.is_empty(ref) ) {
			ref = clear;
		}
		var ctype = this.get_reference(name); 
		
		//Get component from controller if ID supplied
		if ( this.util.is_string(ref) ) {
			ref = this.get_parent().get_component(ctype, ref);
		}
		
		if ( !this.util.is_type(ref, ctype) ) {
			ref = clear;
		}

		//Additional validation
		if ( !this.util.is_empty(ref) && this.util.is_func(validate) && !validate.call(this, ref) ) {
			ref = clear;
		}
		//Set (or clear) component reference
		this[name] = ref;
		
		console.groupEnd();
		//Return value for confirmation
		return this[name];
	},

	/* Attributes */
	
	/**
	 * Initializes attributes
	 */
	init_attributes: function(force) {
		if ( !this.util.is_bool(force) ) {
			force = false;
		}
		if ( force || !this.util.is_obj(this.attributes) ) {
			console.groupCollapsed('Component.init_attributes');
			console.info('Initializing attributes');
			this.attributes = {};
			//Build attribute groups
			var attrs = [{}];
			attrs.push(this.init_default_attributes());
			if ( this.dom_has() ) {
				attrs.push(this.get_dom_attributes());
			}
			if ( this.util.is_obj(this._attr_init) ) {
				attrs.push(this._attr_init);
			}
			console.log('Attribute objects: %o', attrs);
			//Merge attributes
			this.attributes = $.extend.apply(null, attrs);
			console.groupEnd()
		}
	},
	
	/**
	 * Generate default attributes for component
	 * @uses _attr_parent to determine options to retrieve from controller
	 * @uses View.get_options() to get values from controller
	 * @uses _attr_map to Remap controller attributes to instance attributes
	 * @uses _attr_default to Store default attributes
	 */
	init_default_attributes: function() {
		console.groupCollapsed('Component.init_default_attributes');
		console.log('Default attributes: %o', this._attr_default);
		console.log('Get parent options: %o', this._attr_parent);
		//Get parent options
		var opts = this.get_parent().get_options(this._attr_parent);
		console.log('Parent Options: %o \nEmpty: %o', opts, this.util.is_empty(opts));
		if ( this.util.is_obj(opts) ) {
			console.log('Remap options: %o \nMap: %o', opts, this._attr_map);
			//Remap
			for ( var opt in this._attr_map ) {
				if ( opt in opts ) {
					//Move value to new property
					opts[this._attr_map[opt]] = opts[opt];
					//Delete old property
					delete opts[opt];
				}
			}
			console.info('Options remapped: %o', opts);
			//Merge with default attributes
			$.extend(true, this._attr_default, opts);
			console.log('Options merged with defaults: %o', this._attr_default);
		}
		console.groupEnd();
		return this._attr_default;
	},
	
	/**
	 * Retrieve DOM attributes
	 */
	get_dom_attributes: function() {
		console.groupCollapsed('Component.get_dom_attributes');
		var attrs = {};
		var el = this.dom_get();
		if ( el.length ) {
			console.info('Checking DOM element for attributes');
			//Get attributes from element
			var opts = $(el).get(0).attributes;
			if ( this.util.is_obj(opts) ) {
				console.group('Processing DOM Attributes: %o', opts);
				var attr_prefix = this.util.get_attribute();
				$.each(opts, function(idx, opt) {
					if ( opt.name.indexOf( attr_prefix ) == -1 ) {
						return true;
					}
					console.log('Index: %o \nOption: %o', idx, opt.name);
					//Process custom attributes
					//Strip prefix
					var key = opt.name.substr(attr_prefix.length + 1);
					console.log('Attribute: %o \nValue: %o', key, opt.value);
					attrs[key] = opt.value;
				});
				console.groupEnd();
			}
		}
		console.log('DOM Attribute: %o', attrs);
		console.groupEnd();
		return attrs;
	},
	
	/**
	 * Retrieve all instance attributes
	 * @uses parse_attributes() to initialize attributes (if necessary)
	 * @uses attributes
	 * @return obj Attributes
	 */
	get_attributes: function() {
		//Initilize attributes
		this.init_attributes();
		//Return attributes
		return this.attributes;
	},
	
	/**
	 * Retrieve value of specified attribute for value
	 * @param string key Attribute to retrieve
	 * @param mixed def (optional) Default value if attribute is not set
	 * @param bool enforce_type (optional) Whether data type should match default value (Default: TRUE)
	 * > If possible, attribute value will be converted to match default data type
	 * > If attribute value cannot match default data type, default value will be used
	 * @return mixed Attribute value (NULL if attribute is not set)
	 */
	get_attribute: function(key, def, enforce_type) {
		//Validate
		if ( !this.util.is_set(def) ) {
			def = null;
		}
		if ( !this.util.is_string(key) ) {
			return def;
		}
		if ( !this.util.is_bool(enforce_type) ) {
			enforce_type = true;
		}
		//Get attribute value
		var ret = ( this.has_attribute(key) ) ? this.get_attributes()[key] : def;
		//Validate type
		if ( enforce_type && ret !== def && null !== def && !this.util.is_type(ret, $.type(def), false) ) {
			//Convert type
			//Scalar default
			if ( this.util.is_scalar(def, false) ) {
				//Non-scalar attribute
				if ( !this.util.is_scalar(ret, false) ) {
					ret = def;
				} else if ( this.util.is_string(def, false) ) {
					ret = ret.toString();
				} else if ( this.util.is_num(def, false) && !this.util.is_num(ret, false) ) {
					ret = ( this.util.is_int(def, false) ) ? parseInt(ret) : parseFloat(ret);
					if ( !this.util.is_num(ret, false) ) {
						ret = def;
					}
				} else if ( this.util.is_bool(def, false) ) {
					ret = ( this.util.is_string(ret) || ( this.util.is_num(ret) ) );
				} else {
					ret = def;
				}
			}
			//Non-scalar default
			else {
				ret = def;
			}
		}
		return ret;
	},
	
	/**
	 * Call attribute as method
	 * @param string attr Attribute to call
	 * @param arguments (optional) Additional arguments to pass to method
	 */
	call_attribute: function(attr) {
		console.group('Component.call_attribute');
		attr = this.get_attribute(attr, function() {});
		console.info('Passing to attribute (method)');
		//Get arguments
		var args = Array.prototype.slice.call(arguments, 1);
		//Pass arguments to user-defined method
		console.groupEnd();
		return attr.apply(this, args);
	},
	
	/**
	 * Check if attribute exists
	 * @param string key Attribute name
	 * @return bool TRUE if exists, FALSE otherwise
	 */
	has_attribute: function(key) {
		return ( key in this.get_attributes() );
	},
	
	/**
	 * Set component attributes
	 * @param obj attributes Attributes to set
	 * @param bool full (optional) Whether to fully replace or merge component's attributes with new values (Default: Merge)  
	 */
	set_attributes: function(attributes, full) {
		console.groupCollapsed('Component.set_attributes');
		console.log('Instance Attributes: %o \nAttributes Passed: %o \nFull Reset: %o', this.attributes, attributes, full);
		if ( !this.util.is_bool(full) ) {
			full = false;
		}

		//Initialize attributes
		this.init_attributes(full);
		
		//Merge new/existing attributes
		if ( this.util.is_obj(attributes) ) {
			$.extend(this.attributes, attributes);
		}
		
		console.groupEnd();
	},
	
	/**
	 * Set value for a component attribute
	 * @uses get_attributes() to retrieve attributes
	 * @param string key Attribute to set
	 * @param mixed val Attribute value
	 */
	set_attribute: function(key, val) {
		if ( this.util.is_string(key) && this.util.is_set(val) ) {
			this.get_attributes()[key] = val;
		}
	},
	
	/* DOM */

	/**
	 * Generate selector for retrieving child element
	 * @param string element Class name of child element
	 * @return string Element selector
	 */
	dom_get_selector: function(element) {
		return ( this.util.is_string(element) ) ? '.' + this.add_ns(element) : '';
	},
	
	dom_get_attribute: function() {
		return this.util.get_attribute(this._slug);
	},

	/**
	 * Set reference of instance on DOM element
	 * @uses _reciprocal to determine if DOM element should also be attached to instance
	 * @param string|obj (jQuery) el DOM element to attach instance to
	 */
	dom_set: function(el) {
		console.groupCollapsed('Component.dom_set');
		console.log('Element: %o', el);
		//Save instance to DOM object
		$(el).data(this.get_data_key(), this);
		//Save DOM object to instance
		if ( this._reciprocal ) {
			this._dom = $(el);
		}
		console.groupEnd();
	},

	/**
	 * Check if DOM element is set for instance
	 * @uses _dom to check for stored value
	 * @param string element Child element to check for
	 * @return bool TRUE if DOM element set, FALSE otherwise
	 */
	dom_has: function(element) {
		var ret = !this.util.is_empty(this._dom);
		//Check for child element
		if ( ret && this.util.is_string(element) ) {
			ret = !!($(this._dom).has(this.dom_get_selector(element))).length;
		}
		return ret;
	},
	
	/**
	 * Retrieve attached DOM element
	 * @uses _dom to retrieve attached DOM element
	 * @param string element Child element to retrieve
	 * @param bool put (optional) Whether to insert element if it does not exist (Default: FALSE)
	 * @param obj options (optional) Options for creating new object
	 * @uses dom_put() to insert child element
	 * @return obj jQuery DOM element
	 */
	dom_get: function(element, put, options) {
		console.groupCollapsed('Component.dom_get: %o', this._slug);
		//Init Component DOM
		this.dom_init();
		//Check for main DOM element
		var ret = ( this.dom_has() ) ? this._dom : null;
		if ( !this.util.is_empty(ret) && this.util.is_string(element) ) {
			//Check for child element
			if ( this.dom_has(element) ) {
				ret = $(this.dom_get_selector(element), this._dom);
			} else if ( this.util.is_bool(put) && put ) {
				//Insert child element
				ret = this.dom_put(element, options);
			}
		}
		console.groupEnd();
		
		return $(ret);
	},
	
	/**
	 * Initialize DOM element
	 * To be overridden by child classes
	 */
	dom_init: function() {},
	
	/**
	 * Wrap output in DOM element
	 * Wrapper element created and added to main DOM element if not yet created
	 * @param string element ID for DOM element (Used as class name for wrapper)
	 * @param string|jQuery|obj content Content to add to DOM (Object contains element properties)
	 * 	> tag 		: Element tag name
	 * 	> content	: Element content
	 */
	dom_put: function(element, content) {
		console.groupCollapsed('Component.dom_put: %o', element);
		var r = null;
		//Stop processing if main DOM element not set or element is not valid
		if ( !this.dom_has() || !this.util.is_string(element) ) {
			console.warn('Invalid parameters');
			console.groupEnd();
			return r;
		}
		//Setup options
		console.log('Setup options');
		var strip = ['tag', 'content'];
		var options = {
			'tag': 'div',
			'content': '',
			'class': this.add_ns(element)
		}
		console.log('Setup content');
		//Setup content
		if ( !this.util.is_empty(content) && !this.util.is_obj(content) && !this.util.is_type(content, jQuery) ) {
			$.extend(options, content);
		} else {
			options.content = content;
		}
		attrs = $.extend({}, options);
		console.log('Element Attributes');
		console.dir(attrs);
		for ( var x = 0; x < strip.length; x++ ) {
			delete attrs[strip[x]];
		}
		console.log('Element options');
		console.dir(options);
		//Retrieve existing element
		var d = this.dom_get();
		r = $(this.dom_get_selector(element), d);
		//Create element (if necessary)
		if ( !r.length ) {
			r = $('<%s />'.sprintf(options.tag), attrs).appendTo(d);
			console.log('Adding element: %o \nDOM: %o \nElement: %o', options, d, r);
		}
		//Set content
		console.log('Append content');
		$(r).append(options.content);
		console.groupEnd();
		return $(r);
	},
	
	/* Data */
	
	/**
	 * Retrieve key used to store data in DOM element
	 * @return string Data key 
	 */
	get_data_key: function() {
		return this.get_ns();
	},
	
	/**
	 * Retrieve data for component stored in DOM element
	 */
	get_data: function() {
		var ret = null;
		if ( this.dom_has() ) {
			$(this.dom_get()).data(this.get_data_key());
		}
	},
	
	/**
	 * Set data for component in DOM element
	 */
	set_data: function(data) {
		if ( this.dom_has() ) {
			$(this.dom_get()).data(this.get_data_key(), data);
		}
	},
	
	/* Events */
	
	/**
	 * Register event handler for custom event
	 * @param string event Custom event to register handler for
	 * @param function func Event handler
	 * @param obj options (optional) Configuration options for registering event handler
	 * > overwrite	(bool)	Whether or not to overwrite existing handler in same context
	 * @param string|Component context (optional) Specific context for event handler, allows handlers to only be registered once
	 * @return obj Component instance (allows chaining) 
	 */
	on: function(event, func, options, context) {
		console.groupCollapsed('Component.on: %o', this._slug + '.' + event);
		console.log('Event: %o \nFunc: %o \nOptions: %o \nContext: %o', event, func, options, context);
		//Add event handlers via array
		if ( this.util.is_array(event) ) {
			var t = this;
			$.each(event, function(idx, val) {
				t.on(val, func, options, context);
			});
			console.groupEnd();
			return this;
		}
		//Add event handlers via map object
		if ( this.util.is_obj(event) ) {
			for ( var key in event ) {
				this.on(key, event[key], func, options);
			}
			console.groupEnd();
			return this;
		}
		
		/* Validate */
		//Request
		if ( !this.util.is_string(event) || !this.util.is_func(func) ) {
			console.warn('Invalid event handler');
			console.groupEnd();
			return this;
		}
		//Options
		//Check if options omitted
		if ( this.util.is_string(options) || this.util.is_type(options, View.Component) ) {
			//Set context from options parameter
			console.info('Setting context');
			context = arguments[2];
			options = null;
		}
		if ( !this.util.is_obj(options, false) ) {
			//Reset options
			options = {};
		}
		//Merge options with defaults
		var options_def = {
			overwrite: false
		};
		options = $.extend({}, options_def, options);
		
		//Context
		var context_def = '0';
		if ( !this.util.is_string(context) ) {
			console.info('Normalizing context: %o', context);
			if ( this.util.is_num(context) ) {
				//Convert number to string context
				context = context.toString();
			} else if ( this.util.is_type(context, View.Component) ) {
				//Build context from component instance
				var id = context.get_id(true);
				context = ( this.util.is_string(id) ) ? id : context_def;
				//Force unique handler
				options.overwrite = false;
			}
		}
		if ( !this.util.is_string(context) ) {
			context = context_def;
		}
		console.info('Event: %o \nContext: %o', event, context);
		var es = this._events;
		//Setup event
		if ( !( event in es ) || !this.util.is_obj(es[event], false) ) {
			console.info('Initializing event: %o', event);
			es[event] = {};
		}
		var e = es[event];
		//Check for duplicate handler
		if ( !options.overwrite && ( context != context_def ) && ( context in e ) ) {
			console.warn('Handler already registered');
			console.groupEnd();
			return this;
		}
		//Add context to event
		if ( !( context in e ) ) {
			e[context] = [];
		}
		//Add event handler
		e[context].push(func);
		console.groupEnd();
		return this;
	},
	
	/**
	 * Trigger custom event
	 * Event handlers are passed several parameters
	 * > component	(obj)	Reference to current component instance
	 * > ev			(obj)	Event object
	 * 	> name	(string)	Event name
	 * 	> data	(mixed)		Data to pass to handlers (if supplied)
	 * @param string event Custom event to trigger
	 * @param mixed data (optional) Data to pass to event handlers
	 */
	trigger: function(event, data) {
		console.groupCollapsed('Component.trigger: %o', this._slug + '.' + event);
		if ( this.util.is_array(event) ) {
			var t = this;
			$.each(event, function(idx, val) {
				t.trigger(val, data);
			});
			console.groupEnd();
			return true;
		}
		//Validate
		if ( !this.util.is_string(event) || !( event in this._events ) ) {
			console.warn('Invalid event');
			console.groupEnd();
			return false;
		}
		//Create event object
		var ev = { 'type': event, 'data': null };
		//Add data to event object
		if ( this.util.is_set(data) ) {
			ev.data = data;
		}
		//Call handlers for event
		var es = this._events;
		var ec;
		//Iterate through context buckets for event
		for ( context in es[event] ) {
			ec = es[event][context];
			console.info('Bucket: %o (%o)', context, ec.length);
			//Iterate though handlers in current context bucket
			for ( var x = 0; x < ec.length; x++ ) {
				//Call handler, passing component instance & event object
				ec[x](this, ev);
			}
		}
		console.groupEnd();
	},
};

View.Component = Component = SLB.Class.extend(Component);

/**
 * Content viewer
 * @param obj options Init options
 */
var Viewer = {
	
	/* Configuration */
	
	_slug: 'viewer',
	
	_refs: {
		item: 'Content_Item',
		theme: 'Theme'
	},
	
	_reciprocal: true,
	
	_attr_default: {
		loop: true,
		animate: true,
		overlay_enabled: true,
		overlay_opacity: '0.8',
		container: null,
		slideshow_enabled: true,
		slideshow_autostart: true,
		slideshow_duration: 2,
		slideshow_active: false,
		slideshow_timer: null,
		labels: {
			close: 'close',
			nav_prev: '&laquo; prev',
			nav_next: 'next &raquo;',
			slideshow_start: 'start slideshow',
			slideshow_stop: 'stop slideshow',
			group_status: 'Image %current% of %total%',
			loading: 'loading',
		}
	},
	
	_attr_parent: [
		'theme', 
		'group_loop', 
		'ui_animate', 'ui_overlay_opacity', 'ui_labels',
		'slideshow_enabled', 'slideshow_autostart', 'slideshow_duration'],
	
	_attr_map: {
		'group_loop': 'loop',
		'ui_animate': 'animate',
		'ui_overlay_opacity': 'overlay_opacity',
		'ui_labels': 'labels'
	},
	
	/* References */
	
	/**
	 * Item currently loaded in viewer
	 * @var object Content_Item
	 */
	item: null,
	
	/**
	 * Theme used by viewer
	 * @var object Theme
	 */
	theme: null,
	
	/* Properties */
	
	init: false,
	loading: false,
	
	/* Methods */
	
	/* References */
	
	/**
	 * Set item reference
	 * Validates item before setting
	 * @param obj item Content_Item instance
	 * @return bool TRUE if valid item set, FALSE otherwise
	 */
	set_item: function(item) {
		console.groupCollapsed('Viewer.set_item');
		var i = this.set_component('item', item, function(item) {
			return ( item.has_type() );
		});
		console.groupEnd();
		return ( !this.util.is_empty(i) );
	},
	
	/**
	 * Retrieve item instance current attached to viewer
	 * @return Content_Item|NULL Current item instance
	 */
	get_item: function() {
		return this.get_component('item');
	},
	
	/**
	 * Retrieve theme reference
	 * @return object Theme reference
	 */
	get_theme: function() {
		console.groupCollapsed('Viewer.get_theme');
		//Get saved theme
		var ret = this.get_component('theme', true, false, false);
		if ( this.util.is_empty(ret) ) {
			//Theme needs to be initialized
			ret = this.set_component('theme', new View.Theme(this));
		}
		console.log('Theme: %o', ret);
		console.groupEnd();
		return ret;
	},
	
	/**
	 * Set viewer's theme
	 * @param object theme Theme object
	 */
	set_theme: function(theme) {
		this.set_component('theme', theme);
	},
	
	/* Properties */
	
	/**
	 * Sets loading mode
	 * @param bool mode (optional) Set (TRUE) or unset (FALSE) loading mode (Default: TRUE)
	 */
	set_loading: function(mode) {
		console.groupCollapsed('Viewer.set_loading: %o', mode);
		if ( !this.util.is_bool(mode) ) {
			mode = true;
		}
		this.loading = mode;
		console.info('Loading: %o', mode);
		//Pause/Resume slideshow
		if ( this.slideshow_active() ) {
			this.slideshow_pause(mode);
		}
		//Set CSS class on DOM element
		var m = ( mode ) ? 'addClass' : 'removeClass';
		console.info('Loading method: %o', m);
		$(this.dom_get())[m]('loading');
		//Loading animation
		if ( mode ) {
			this.get_theme().animate('load');
		}
		console.groupEnd();
	},
	
	unset_loading: function() {
		this.set_loading(false);
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
		console.info('Set current item');
		//Validate request
		if ( !this.set_item(item) || !this.get_theme() ) {
			console.warn('Invalid request');
			console.groupEnd();
			this.exit();
		}
		//Set loading flag
		console.info('Set loading flag');
		this.set_loading();
		//Display
		this.render();
		console.groupEnd();
	},
	
	/**
	 * Check if viewer is currently open
	 * Checks if node is actually visible in DOM 
	 * @return bool TRUE if viewer is open, FALSE otherwise 
	 */
	is_open: function() {
		return ( this.dom_get().css('display') == 'none' ) ? false : true;
	},
	
	/**
	 * Load output into DOM
	 */
	render: function() {
		console.group('Viewer.render');
		//Get theme output
		console.info('Rendering Theme layout');
		var v = this;
		var th = this.get_theme();
		var dfr = $.Deferred();
		//Theme event handlers
		th
			.on({
				//Loading
				'render-loading': function(theme, ev) {
					console.group('Viewer.render.loading (Callback)');
					if ( v.is_open() ) {
						v.set_loading();
						dfr.resolve();
					} else {
						th.animate('open').done(function() {
							//Fallback open
							v.get_overlay().show();
							v.dom_get().show();
							//Set loading flag
							v.set_loading();
							dfr.resolve();
						});
					}
					console.groupEnd();
				},
				//Complete
				'render-complete': function(theme, ev) {
					dfr.done(function() {
						console.groupCollapsed('Viewer.render.complete (Callback)');
						console.log('Completed output: %o', ev.data);
						console.info('Theme loaded');
						//Set classes
						var d = v.dom_get();
						var classes = ['item_single', 'item_multi'];
						var ms = ['addClass', 'removeClass']; 
						if ( !v.get_item().get_group().is_single() ) {
							ms.reverse();
						}
						$.each(ms, function(idx, val) {
							d[val](classes[idx]);
						});
						//Bind events
						v.events_init();
						//Animate
						th.animate('complete').done(function() {
							//Unset loading flag
							v.unset_loading();
						});
						//Trigger event
						v.trigger('render-complete');
						//Set viewer as initialized
						v.init = true;
						console.groupEnd();
					});
				}
			}, this)
			//Render
			.render(this.get_item());
		console.groupEnd();
	},
	
	/**
	 * Retrieve container element
	 * Creates default container element if not yet created
	 * @return jQuery Container element
	 */
	dom_get_container: function() {
		var sel = this.get_attribute('container');
		//Set default container
		if ( this.util.is_empty(sel) ) {
			sel = '#' + this.add_ns('wrap');
		}
		//Add default container to DOM if not yet present
		c = $(sel);
		if ( !c.length ) {
			//Prepare ID
			id = ( sel.indexOf('#') === 0 ) ? sel.substr(1) : sel;
			//Add element
			c = $('<div />', {'id': id}).appendTo('body');
		}
		return c;
	},
	
	/**
	 * Custom Viewer DOM initialization
	 */
	dom_init: function() {
		console.group('Viewer.dom_init');
		//Check if DOM element already set
		if ( !this.dom_has() ) {
			console.info('DOM element needs to be created');
			//Create element & add to DOM
			//Save element to instance
			this.dom_set($('<div/>', {
				'id':  this.get_id(true),
				'class': [
					this.get_ns(),
					this.get_theme().get_classes(' '),
				].join(' ')
			}).appendTo(this.dom_get_container()).hide());
			console.log('Theme ID: %o', this.get_theme().get_id(true));
			console.log('DOM element added');
			//Add theme layout (basic)
			var v = this;
			console.info('Rendering basic theme layout');
			var thm = this.get_theme();
			thm.on('render-init', function(theme, ev) {
				console.groupCollapsed('Viewer.dom_init.init (callback)');
				console.info('Basic layout: %o', ev.data);
				//Add rendered theme layout to viewer DOM
				v.dom_put('layout', ev.data);
				console.groupEnd();
			}, this).render();
		}
		console.groupEnd();
	},
	
	/**
	 * Restore DOM
	 * Show overlapping DOM elements, etc.
	 * @TODO Build functionality
	 */
	dom_restore: function() {},
	
	/* Layout */
	
	get_layout: function() {
		var el = 'layout';
		return ( this.dom_has(el) ) ? this.dom_get(el) : this.dom_put(el).hide();
	},
	
	/* Overlay */
	
	/**
	 * Determine if overlay is enabled for viewer
	 * @return bool TRUE if overlay is enabled, FALSE otherwise
	 */
	overlay_enabled: function() {
		var ov = this.get_attribute('overlay_enabled');
		return ( this.util.is_bool(ov) ) ?  ov : false;
	},
	
	/**
	 * Retrieve overlay DOM element
	 * @return jQuery Overlay element (NULL if no overlay set for viewer)
	 */
	get_overlay: function() {
		console.groupCollapsed('Viewer.get_overlay');
		var o = null;
		var el = 'overlay';
		console.log('Enabled: %o', this.overlay_enabled());
		if ( this.dom_has(el) ) {
			console.info('Overlay exists');
			o = this.dom_get(el);
		} else if ( this.overlay_enabled() ) {
			console.info('Creating overlay');
			o = this.dom_put(el).hide();
		} else {
			console.warn('Problem with overlay');
		}
		console.groupEnd();
		return $(o);
	},

	/**
	 * Exit Viewer
	 */
	exit: function() {
		console.groupCollapsed('Viewer.exit');
		this.reset();
		console.groupEnd();
	},
	
	/**
	 * Reset viewer
	 */
	reset: function() {
		this.set_item(false);
		this.set_loading(false);
		this.slideshow_stop();
	},
	
	/* Content */
	
	get_labels: function() {
		return this.get_attribute('labels', {});
	},
	
	get_label: function(name) {
		var lbls = this.get_labels();
		return ( name in lbls ) ? lbls[name] : '';
	},
	
	/* Interactivity */
	
	/**
	 * Initialize event handlers for UI elements
	 */
	events_init: function() {
		console.groupCollapsed('Viewer.events_init');
		if ( this.init ) {
			console.warn('Event handlers previously set');
			console.groupEnd();
			return false;
		}
		
		//Control event bubbling
		var l = this.get_layout();
		l.children().click(function(ev) {
			ev.stopPropagation();
		});
		
		/* Close */
		
		var v = this;
		var close = function(e) {
			return v.close(e);
		};
		//Container
		l.click(close);
		//Overlay
		this.get_overlay().click(close);
		
		//Fire event
		this.trigger('events-init');
		console.groupEnd();
	},
		
	/**
	 * Check if slideshow functionality is enabled
	 * @return bool TRUE if slideshow is enabled, FALSE otherwise
	 */
	slideshow_enabled: function() {
		var o = this.get_attribute('slideshow_enabled');
		return ( this.util.is_bool(o) && o && this.get_item() && !this.get_item().get_group().is_single() ) ? true : false; 
	},
		
	/**
	 * Checks if slideshow is currently active
	 * @return bool TRUE if slideshow is active, FALSE otherwise
	 */
	slideshow_active: function() {
		return ( this.slideshow_enabled() && ( this.get_attribute('slideshow_active') || ( !this.init && this.get_attribute('slideshow_autostart') ) ) ) ? true : false;
	},
	
	/**
	 * Clear slideshow timer
	 */
	slideshow_clear_timer: function() {
		clearInterval(this.get_attribute('slideshow_timer'));
	},
	
	/**
	 * Start slideshow timer
	 * @param function callback Callback function
	 */
	slideshow_set_timer: function(callback) {
		this.set_attribute('slideshow_timer', setInterval(callback, this.get_attribute('slideshow_duration') * 1000));
	},
	
	/**
	 * Start Slideshow
	 */
	slideshow_start: function() {
		if ( !this.slideshow_enabled() ) {
			return false;
		}
		this.set_attribute('slideshow_active', true);
		//Clear residual timers
		this.slideshow_clear_timer();
		//Start timer
		var v = this;
		this.slideshow_set_timer(function() {
			//Pause slideshow until next item fully loaded
			v.slideshow_pause();
			
			//Show next item
			v.item_next();
		});
		this.trigger('slideshow-start');
	},
	
	/**
	 * Stop Slideshow
	 * @param bool full (optional) Full stop (TRUE) or pause (FALSE) (Default: TRUE)
	 */
	slideshow_stop: function(full) {
		if ( !this.util.is_bool(full) ) {
			full = true;
		}
		if ( full ) {
			this.set_attribute('slideshow_active', false);
		}
		//Kill timers
		this.slideshow_clear_timer();
		this.trigger('slideshow-stop');
	},
	
	slideshow_toggle: function() {
		console.groupCollapsed('Viewer.slideshow_toggle');
		if ( !this.slideshow_enabled() ) {
			console.warn('Slideshow not enabled');
			console.groupEnd();
			return false;
		}
		if ( this.slideshow_active() ) {
			this.slideshow_stop();
		} else {
			this.slideshow_start();
		}
		this.trigger('slideshow-toggle');
		console.groupEnd();
	},
	
	/**
	 * Pause Slideshow
	 * @param bool mode (optional) Pause (TRUE) or Resume (FALSE) slideshow (default: TRUE)
	 */
	slideshow_pause: function(mode) {
		//Validate
		if ( !this.util.is_bool(mode) ) {
			mode = true;
		}
		//Set viewer slideshow properties
		if ( this.slideshow_active() ) {
			if ( !mode ) {
				//Slideshow resumed
				this.slideshow_start();
			} else {
				//Slideshow paused
				this.slideshow_stop(false);
			}
		}
		this.trigger('slideshow-pause');
	},
	
	/**
	 * Resume slideshow
	 */
	slideshow_resume: function() {
		this.slideshow_pause(false);
	},
	
	/**
	 * Next item
	 */
	item_next: function() {
		var g = this.get_item().get_group(true);
		var v = this;
		g.on('item-next', function(g) {
			v.trigger(['item-next', 'item-change']);
		});
		g.show_next();
	},
	
	/**
	 * Previous item
	 */
	item_prev: function() {
		var g = this.get_item().get_group(true);
		var v = this;
		g.on('item-prev', function(g) {
			v.trigger(['item-prev','item-change']);
		});
		g.show_prev();
	},
	
	/**
	 * Close viewer
	 */
	close: function(e) {
		console.groupCollapsed('Viewer.close');
		var v = this;
		this.get_theme().animate('close').done(function() {
			//Fallback viewer hide
			v.dom_get().hide();
			
			//Restore DOM
			v.dom_restore();
			
			//End processes
			v.exit();
			
			v.trigger('close');
		});
		console.groupEnd();
		return false;
	}
};

View.Viewer = Component.extend(Viewer);

/**
 * Content group
 * @param obj options Init options
 */
var Group = {
	/* Configuration */
	
	_slug: 'group',
	_reciprocal: true,
	_refs: {
		'current': 'Content_Item'
	},
	
	/* References */
	
	current: null,
	
	/* Properties */
	
	/**
	 * Selector for getting group items
	 * @var string 
	 */
	selector: null,
	
	/**
	 * Retrieve selector for group items
	 * @return string Group items selector 
	 */
	get_selector: function() {
		console.groupCollapsed('Group.get_selector');
		if ( this.util.is_empty(this.selector) ) {
			//Build selector
			this.selector = 'a[%s="%s"]'.sprintf(this.dom_get_attribute(), this.get_id());
			console.info('Selector: %o', this.selector);
		}
		console.groupEnd();
		return this.selector;
	},
	
	/**
	 * Retrieve group items
	 */
	get_items: function() {
		console.groupCollapsed('Group.get_items');
		var items = ( !this.util.is_empty(this.get_id()) ) ? $(this.get_selector()) : this.get_current().dom_get();
		console.log('Items (%o): %o', items.length, items);
		console.groupEnd();
		return items;
	},
	
	/**
	 * Retrieve item at specified index
	 * If no index specified, first item is returned
	 * @param int idx Index of item to return
	 * @return Content_Item Item
	 */
	get_item: function(idx) {
		console.group('Group.get_item: %o', idx);
		//Validation
		if ( !this.util.is_int(idx) ) {
			idx = 0;
		}
		//Retrieve all items
		var items = this.get_items();
		//Validate index
		var max = this.get_size() - 1;
		if ( idx > max ) {
			idx = max;
		}
		//Return specified item
		console.groupEnd();
		return items.get(idx);
	},
	
	/**
	 * Retrieve (zero-based) position of specified item in group
	 * @param Content_Item item Item to locate in group
	 * @return int Index position of item in group (-1 if item not in group)
	 */
	get_pos: function(item) {
		if ( this.util.is_empty(item) ) {
			//Get current item
			item = this.get_current();
		}
		return ( this.util.is_type(item, View.Content_Item) ) ? this.get_items().index(item.dom_get()) : -1;
	},
	
	/**
	 * Retrieve current item
	 * @return Content_Item Current item
	 */
	get_current: function() {
		//Sanitize
		if ( !this.util.is_empty(this.current) && !this.util.is_type(this.current, View.Content_Item) ) {
			console.warn('Resetting current item: %o', this.current);
			this.current = null;
		}
		console.log('Current: %o', this.current);
		return this.current;
	},
	
	/**
	 * Sets current group item
	 * @param Content_Item item Item to set as current
	 */
	set_current: function(item) {
		console.groupCollapsed('Group.set_current');
		console.log('Item: %o', item);
		//Validate
		if ( this.util.is_type(item, View.Content_Item) ) {
			//Set current item
			console.log('Setting current item');
			this.current = item;
		}
		console.groupEnd();
	},
		
	get_next: function(item) {
		console.group('Group.get_next');
		//Validate
		if ( !this.util.is_type(item, View.Content_Item) ) {
			console.log('Retrieving current item');
			item = this.get_current();
		}
		if ( this.get_size() == 1 ) {
			console.warn('Single item in group');
			console.groupEnd();
			return item;
		}
		var next = null;
		var pos = this.get_pos(item);
		if ( pos != -1 ) {
			pos = ( pos + 1 < this.get_size() ) ? pos + 1 : 0;
			next = this.get_item(pos);
		}
		console.log('Position in Group: %o \nItem: %o', pos, next);
		console.groupEnd();
		return next;
	},
	
	get_prev: function(item) {
		console.groupCollapsed('Group.get_prev');
		//Validate
		if ( !this.util.is_type(item, View.Content_Item) ) {
			console.log('Retrieving current item');
			item = this.get_current();
		}
		if ( this.get_size() == 1 ) {
			console.warn('Single item in group');
			console.groupEnd();
			return item;
		}
		var prev = null;
		var pos = this.get_pos(item);
		if ( pos != -1 ) {
			if ( pos == 0 ) {
				pos = this.get_size();
			}
			pos -= 1;
			prev = this.get_item(pos);
		}
		console.log('Position in Group: %o \nItem: %o', pos, prev);
		console.groupEnd();
		return prev;
	},
	
	show_next: function(item) {
		console.groupCollapsed('Group.show_next');
		if ( this.get_size() > 1 ) {
			//Retrieve item
			var i = this.get_parent().get_item(this.get_next(item));
			//Update current item
			this.set_current(i);
			//Show item
			i.show();
		}
		console.groupEnd();
	},
	
	show_prev: function(item) {
		console.group('Group.show_prev');
		if ( this.get_size() > 1 ) {
			//Retrieve item
			var i = this.get_parent().get_item(this.get_prev(item));
			//Update current item
			this.set_current(i);
			//Show item
			i.show();
		}
		console.groupEnd();
	},
	
	/**
	 * Retrieve total number of items in group
	 * @return int Number of items in group 
	 */
	get_size: function() {
		return this.get_items().length;
	},
	
	is_single: function() {
		return ( this.get_size() == 1 );
	}
};

View.Group = Component.extend(Group);

/**
 * Content type
 * @param obj options Init options
 */
var Content_Type = {
	
	/* Configuration */
	
	_slug: 'content_type',
	_refs: {
		'item': 'Content_Item',
	},
	
	/* References */
	
	item: null,
	
	/* Properties */
	
	/**
	 * Raw layout template
	 * @var string 
	 */
	template: '',
	
	/* Methods */
	
	/* Item */
	
	/**
	 * Check if item instance set for type
	 * @uses get_item()
	 * @uses clear_item() to remove invalid item values
	 * @return bool TRUE if valid item set, FALSE otherwise
	 */
	has_item: function() {
		return ( this.util.is_empty(this.get_item()) ) ? false : true;
	},
	
	/**
	 * Retrieve item instance set on type
	 * @uses get_component()
	 * @return mixed Content_Item if valid item set, NULL otherwise
	 */
	get_item: function() {
		return this.get_component('item', true, false);
	},
	
	/**
	 * Set item instance for type
	 * Items are only meant to be set/used while item is being processed
	 * @uses set_component()
	 * @param Content_Item item Item instance
	 * @return obj|null Item instance if item successfully set, NULL otherwise
	 */
	set_item: function(item) {
		//Set reference
		var r = this.set_component('item', item);
		return r;
	},
	
	/**
	 * Clear item instance from type
	 * Sets value to NULL
	 */
	clear_item: function() {
		this.item = null;
	},
	
	/* Evaluation */
		
	/**
	 * Check if item matches content type
	 * @param object item Content_Item instance to check for type match
	 * @return bool TRUE if type matches, FALSE otherwise 
	 */
	match: function(item) {
		console.groupCollapsed('Content_Type.match');
		//Validate
		var attr = 'match';
		var m = this.get_attribute(attr);
		//Stop processing types with no matching algorithm
		if ( !this.util.is_empty(m) ) {
			//Process regex patterns
			
			//String-based
			if ( this.util.is_string(m) ) {
				//Create new regexp object
				console.log('Processing string regex');
				m = new RegExp(m, "i");
				this.set_attribute(attr, m);
			}
			//RegExp based
			if ( this.util.is_type(m, RegExp) ) {
				console.info('Checking regex: %o \nMatch: %o', m, m.test(item.get_uri()));
				console.groupEnd();
				return m.test(item.get_uri());
			}
			//Process function
			if ( this.util.is_func(m) ) {
				console.info('Processing match function');
				console.groupEnd();
				return ( m.call(this, item) ) ? true : false;
			}
		}
		//Default
		console.warn('No match algorithm');
		console.groupEnd();
		return false;
	},
	
	/* Processing/Output */
	
	/**
	 * Render output to display item
	 * @param Content_Item item Item to render output for
	 * @return obj jQuery.Promise that is resolved when item is rendered
	 */
	render: function(item) {
		console.groupCollapsed('Content_Type.render');
		var dfr = $.Deferred();
		//Validate
		var ret = this.call_attribute('render', item);
		if ( this.util.is_promise(ret) ) {
			ret.done(function(output) {
				dfr.resolve(output);
			});
		} else {
			//String format
			if ( this.util.is_string(ret) ) {
				console.log('Processing string format');
				ret = ret.sprintf(item.get_uri());
			}
			//Resolve deferred immediately
			dfr.resolve(ret);
		}
		console.groupEnd();
		return dfr.promise();
	}
};

View.Content_Type = Component.extend(Content_Type);

/**
 * Content Item
 * @param obj options Init options
 */
var Content_Item = {
	/* Configuration */
	
	_slug: 'content_item',
	_reciprocal: true,
	_refs: {
		'viewer': 'Viewer',
		'group': 'Group',
		'type': 'Content_Type'
	},
	_containers: ['group'],
	
	_attr_default: {
		source: null,
		permalink: null,
		dimensions: null,
		title: '',
		group: null,
		internal: false,
		output: null
	},
	
	/* References */
	
	group: null,
	viewer: null,
	type: null,
	
	/* Properties */
	
	data: null,
	
	/* Init */
	
	_c: function(el) {
		console.info('New Content Item');
		//Save element to instance
		this.dom_set(el);
		//Default initialization
		this._super();
	},
	
	/* Methods */
	
	/*-** Attributes **-*/
	
	/**
	 * Build default attributes
	 * Populates attributes with asset properties (attachments)
	 * Overrides super class method
	 * @uses Component.init_default_attributes()
	 */
	init_default_attributes: function() {
		console.groupCollapsed('Content_Item.init_default_attributes');
		this._super();
		//Add asset properties
		var key = this.dom_get().attr('href') || null;
		var assets = this.get_parent().assets || null;
		console.log('Key: %o \nAssets: %o', key, assets);
		//Merge asset data with default attributes
		if ( this.util.is_string(key) ) {
			var attrs = [{}, this._attr_default, {'permalink': key}];
			if ( this.util.is_obj(assets) && ( key in assets ) && this.util.is_obj(assets[key]) ) {
				attrs.push(assets[key]);
			}
			this._attr_default = $.extend.apply(this, attrs);
			console.log('Default Attributes Updated: %o', this._attr_default);
		}
		console.groupEnd();
		return this._attr_default;
	},
	
	/*-** Properties **-*/
	
	/**
	 * Retrieve item output
	 * Output generated based on content type if not previously generated
	 * @uses get_attribute() to retrieve cached output
	 * @uses set_attribute() to cache generated output
	 * @uses get_type() to retrieve item type
	 * @uses Content_Type.render() to generate item output
	 * @return obj jQuery.Promise that is resolved when output is retrieved
	 */
	get_output: function() {
		console.groupCollapsed('Item.get_output');
		console.info('Checking for cached output');
		var dfr = $.Deferred();
		//Check for cached output
		var ret = this.get_attribute('output');
		if ( this.util.is_string(ret) ) {
			dfr.resolve(ret);
		} else if ( this.has_type() ) {
			//Render output from scratch (if necessary)
			console.info('Rendering output');
			console.info('Get item type');
			//Get item type
			var type = this.get_type();
			console.log('Item type: %o', type);
			//Render type-based output
			var item = this;
			type.render(this).done(function(output) {
				console.info('Output Retrieved: %o', output);
				console.info('Caching item output');
				//Cache output
				item.set_output(output);
				dfr.resolve(output);
			});
		} else {
			dfr.resolve('');
		}
		console.groupEnd();
		return dfr.promise();
	},
	
	/**
	 * Cache output for future retrieval
	 * @uses set_attribute() to cache output
	 */
	set_output: function(out) {
		console.groupCollapsed('Item.set_output: %o', out);
		if ( this.util.is_string(out, false) ) {
			this.set_attribute('output', out);
		}
		console.groupEnd();
	},
	
	/**
	 * Retrieve item output
	 * Alias for `get_output()`
	 * @return jQuery.Promise Deferred that is resolved when content is retrieved 
	 */
	get_content: function() {
		return this.get_output();
	},
	
	/**
	 * Retrieve item URI
	 * @param string mode (optional) Which URI should be retrieved
	 * > source: Media source
	 * > permalink: Item permalink
	 * @return string Item URI
	 */
	get_uri: function(mode) {
		console.groupCollapsed('Item.get_uri');
		//Validate
		if ( ['source', 'permalink'].indexOf(mode) == -1 ) {
			mode = 'source';
		}
		console.log('Mode: %o', mode);
		//Retrieve URI
		var ret = this.get_attribute(mode);
		if ( !this.util.is_string(ret) ) {
			ret = ( 'source' == mode ) ? this.get_attribute('permalink') : '';
		}
		console.log('Item URI: %o', ret);
		console.groupEnd();
		return ret;
	},
	
	get_title: function() {
		var prop = 'title';
		//Check saved attributes
		var title = this.get_attribute(prop);
		if ( this.util.is_string(title) ) {
			return title;
		}
		
		//Generate title from item metadata
		var dom = this.dom_has();
		
		//Caption
		if ( dom ) {
			var sel = '.wp-caption-text'
			if ( this.in_gallery('wp') ) {
				title = this.dom_get().parent().siblings(sel).html();
			} else {
				title = this.dom_get().siblings(sel).html();
			}
		}
		
		//Attributes
		if ( !title ) {
			title = this.get_attribute(prop);
		}
		
		//DOM attributes
		if ( dom ) {
			//Image title
			if ( !title ) {
				var img = this.dom_get().find('img').first();
				title = $(img).attr('title') || $(img).attr('alt');
			}
			
			//DOM element title
			if ( !title ) {
				title = this.dom_get().attr(prop);
			}
		}
		
		//Return value
		this.set_attribute(prop, title);
		return title;
	},
	
	/**
	 * Retrieve item dimensions
	 * Wraps Content_Type.get_dimensions() for type-specific dimension retrieval
	 * @return obj Item `width` and `height` properties (px) 
	 */
	get_dimensions: function() {
		var dim = this.get_attribute('dimensions', {});
		return $.extend({'width': 0, 'height': 0}, dim);
	},
	
	/**
	 * Save item data to instance
	 * Item data is saved when rendered
	 * @param mixed data Item data (property cleared if NULL)
	 */
	set_data: function(data) {
		this.data = data;
	},
	
	/**
	 * Check if current link is part of a gallery
	 * @param obj item
	 * @param string gType Gallery type to check for
	 * @return bool TRUE if link is part of a gallery (FALSE otherwise)
	 */
	in_gallery: function(gType) {
		var ret = false;
		var galls = {
			'wp': '.gallery-icon',
			'ng': '.ngg-gallery-thumbnail'
		};
		
		if ( typeof gType == 'undefined' || !(gType in galls) ) {
			gType = 'wp';
		}
		return ( this.dom_get().parent(galls[gType]).length > 0 ) ? true : false ;
	},
	
	/*-** Component References **-*/
	
	/* Viewer */
	
	get_viewer: function() {
		return this.get_component('viewer');
	},
	
	/**
	 * Sets item's viewer property
	 * @uses View.get_viewer() to retrieve global viewer
	 * @uses this.viewer to save item's viewer
	 * @param string|View.Viewer v Viewer to set for item
	 *  > Item's viewer is reset if invalid viewer provided
	 */
	set_viewer: function(v) {
		return this.set_component('viewer', v);
	},
	
	/* Group */

	/**
	 * Retrieve item's group
	 * @param bool set_current (optional) Sets item as current item in group (Default: FALSE)
	 * @return View.Group|bool Group reference item belongs to (FALSE if no group)
	 */
	get_group: function(set_current) {
		console.groupCollapsed('Item.get_group');
		var prop = 'group';
		//Check if group reference already set
		var g = this.get_component(prop, true, false, false);
		if ( g ) {
			console.log('Group: %o', g);
		} else {
			console.warn('No group reference: %o', g);
			//Set empty group if no group exists
			g = this.set_component(prop, new View.Group());
			set_current = true;
		}
		if ( this.util.is_bool(set_current) && set_current ) {
			g.set_current(this);
		}
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
	
	/* Content Type */
	
	/**
	 * Retrieve item type
	 * @uses get_component() to retrieve saved reference to Content_Type instance
	 * @uses View.get_content_type() to determine item content type (if necessary)
	 * @return Content_Type|null Content Type of item (NULL no valid type exists)
	 */
	get_type: function() {
		console.groupCollapsed('Item.get_type');
		console.info('Retrieving saved type reference');
		var t = this.get_component('type', false, false, false);
		if ( !t ) {
			console.info('No type reference, getting type from Controller');
			t = this.set_type(this.get_parent().get_content_type(this));
		}
		console.info('Type: %o', t);
		console.groupEnd();
		return t;
	},
	
	/**
	 * Save content type reference
	 * @uses set_component() to save type reference
	 * @return Content_Type|null Saved content type (NULL if invalid)
	 */
	set_type: function(type) {
		return this.set_component('type', type);
	},
	
	/**
	 * Check if content type exists for item
	 * @return bool TRUE if content type exists, FALSE otherwise
	 */
	has_type: function() {
		console.groupCollapsed('Item.has_type');
		var ret = !this.util.is_empty(this.get_type());
		console.groupEnd();
		return ret;
	},
	
	/* Actions */
	
	/**
	 * Display item in viewer
	 * @uses get_viewer() to retrieve viewer instance for item
	 * @uses Viewer.show() to display item in viewer
	 */
	show: function() {
		console.group('Item.show');
		//Validate content type
		if ( !this.has_type() ) {
			return false;
		}
		//Retrieve viewer
		var v = this.get_viewer();
		console.info('Viewer retrieved: %o', v);
		v.show(this);
		console.groupEnd();
	}
};

View.Content_Item = Component.extend(Content_Item);

/**
 * Theme
 */
var Theme = {
	
	/* Configuration */
	
	_slug: 'theme',
	_refs: {
		'viewer': 'Viewer',
		'template': 'Template'
	},
	_models: null,
	
	_containers: ['viewer'],
	
	_attr_default: {
		template: null
	},
	
	/* References */
	
	viewer: null,
	template: null,
	
	/* Methods */
	
	/**
	 * Custom constructor
	 * @see Component._c()
	 */
	_c: function(id, attributes, viewer) {
		console.groupCollapsed('Theme.Constructor');
		//Validate
		if ( arguments.length == 1 && this.util.is_type(arguments[0], View.Viewer) ) {
			viewer = arguments[0];
			id = null;
		}
		//Pass parameters to parent constructor
		this._super(id, attributes);
		
		//Set viewer instance
		this.set_viewer(viewer);
		
		//Set theme model
		this.set_model(id);
		
		console.groupEnd();
	},
	
	/* Viewer */
	
	get_viewer: function() {
		return this.get_component('viewer', false, true, false);
	},
	
	/**
	 * Sets theme's viewer property
	 * @uses View.get_viewer() to retrieve global viewer
	 * @uses this.viewer to save item's viewer
	 * @param string|View.Viewer v Viewer to set for item
	 *  > Theme's viewer is reset if invalid viewer provided
	 */
	set_viewer: function(v) {
		return this.set_component('viewer', v);
	},
	
	/* Template */
	
	/**
	 * Retrieve template instance
	 * @return Template instance
	 */
	get_template: function() {
		console.groupCollapsed('Theme.get_template');
		//Get saved template
		var ret = this.get_component('template', true, false, false);
		//Template needs to be initialized
		if ( this.util.is_empty(ret) ) {
			//Pass model to Template instance
			var attr = { 'model': this.get_model() };
			ret = this.set_component('template', new View.Template(attr));
		}
		console.groupEnd();
		return ret;
	},
	
	/**
	 * Retrieve tags from template
	 * All tags will be retrieved by default
	 * Specific tag/property instances can be retrieved as well
	 * @see Template.get_tags()
	 * @param string name (optional) Name of tags to retrieve
	 * @param string prop (optional) Specific tag property to retrieve
	 * @return array Tags in template
	 */
	get_tags: function(name, prop) {
		return this.get_template().get_tags(name, prop);
	},
	
	/**
	 * Retrieve tag DOM elements
	 * @see Template.dom_get_tag()
	 */
	dom_get_tag: function(tag, prop) {
		return $(this.get_template().dom_get_tag(tag, prop));
	},
	
	/* Model */
	
	/**
	 * Retrieve theme models
	 * @return obj Theme models
	 */
	get_models: function() {
		return this.get_parent().get_theme_models();
	},
	
	/**
	 * Retrieve specified theme model
	 * @param string id (optional) Theme model to retrieve
	 * > Default model retrieved if ID is invalid/not set
	 * @return obj Specified theme model
	 */
	get_model: function(id) {
		var ret = null;
		if ( !this.util.is_set(id) && this.has_attribute('model') ) {
			ret = this.get_attribute('model');
		} else {
			//Retrieve matching theme model
			var models = this.get_models();
			if ( !this.util.is_string(id) ) {
				var id = this.get_parent().get_option('theme_default');
			}
			//Select first theme model if specified model is invalid
			if ( !this.util.is_string(id) || !( id in models ) ) {
				id = Object.keys(models)[0];
			}
			ret = models[id];
		}
		return ret;
	},
	
	/**
	 * Set model for current theme instance
	 * @param string id (optional) Theme ID (Default theme retrieved if ID invalid)
	 */
	set_model: function(id) {
		this.set_attribute('model', this.get_model(id));
		//Set ID using model attributes (if necessary)
		if ( !this.check_id(true) ) {
			var m = this.get_model();
			if ( 'id' in m ) {
				this.set_id(m.id);
			}
		}
	},
	
	/**
	 * Check if instance has model
	 * @return bool TRUE if model is set, FALSE otherwise
	 */
	has_model: function() {
		return ( this.util.is_empty( this.get_model() ) ) ? false : true;
	},
	
	/**
	 * Check if specified attribute exists in model
	 * @param string key Attribute to check for
	 * @return bool TRUE if attribute exists, FALSE otherwise
	 */
	in_model: function(key) {
		return ( this.util.in_obj(this.get_model(), key) ) ? true : false;
	},
	
	/* Properties */
	
	/**
	 * Generate class names for DOM node
	 * @param string rtype (optional) Return data type
	 *  > Default: array
	 *  > If string supplied: Joined classes delimited by parameter
	 * @uses get_class() to generate class names
	 * @uses Array.join() to convert class names array to string
	 * @return array Class names
	 */
	get_classes: function(rtype) {
		//Build array of class names
		var cls = [ this.get_id(true) ];
		//Include theme parent's class name
		var m = this.get_model();
		if ( 'parent' in m && this.util.is_string(m.parent) ) {
			cls.push(this.add_ns(m.parent));
		}
		//Convert class names array to string
		if ( this.util.is_string(rtype) ) {
			cls = cls.join(rtype);
		}
		//Return class names
		return cls;
	},
	
	/* Output */
	
	/**
	 * Render Theme output
	 * @param Content_Item item Item to render theme for
	 */
	render: function(item) {
		console.group('Theme.render');
		/*
		if ( !this.util.is_type(item, View.Content_Item) ) {
			item = this.get_viewer().get_item();
		}
		if ( !item ) {
			console.warn('Invalid item');
			console.groupEnd();
			return false;
		}
		*/
		var thm = this;
		var tpl = this.get_template();
		//Register events
		var events = [
			'render-init',
			'render-loading',
			'render-complete'
		];
		var handler = function(e) {
			return function(tp, ev) {
				thm.trigger(e, ev.data);
			}
		};
		for ( var x = 0; x < events.length; x++ ) {
			tpl.on(events[x], handler(events[x]), this); 
		}
		//Render template
		tpl.render(item);		console.groupEnd();
	},
	
	animate: function(event) {
		console.groupCollapsed('Theme.animate: %o', event);
		var dfr = null;
		if ( this.get_viewer().get_attribute('animate', true) && this.util.is_string(event) ) {
			//Get animation settings
			var anims = ( this.in_model('animate') ) ? this.get_model()['animate'] : null;
			if ( this.util.is_method(anims, event) ) {
				//Pass control to animation event
				var ret = anims[event].call(this);
				//Check for promise usage
				if ( this.util.is_promise(ret) ) {
					dfr = ret.pipe(function(r) {
						return r;
					});
				}
			}
		}
		if ( !this.util.is_promise(dfr) ) {
			dfr = $.Deferred();
			dfr.resolve();
		}
		console.groupEnd();
		return dfr.promise();
	},
};

View.Theme = Component.extend(Theme);

/**
 * Template handler
 * Parses and Builds layout from raw template
 */
var Template = {
	/* Configuration */
	
	_slug: 'template',
	_reciprocal: true,
	
	_attr_default: {
		/**
		 * Raw layout template
		 * @var string
		 */	
		layout_raw: '',
		/**
		 * Parsed layout
		 * Placeholders processed
		 * @var string
		 */
		layout_parsed: '',
		/**
		 * Tags in template
		 * Populated once template has been parsed
		 * @var array
		 */
		tags: null,
		/**
		 * Model to use for properties
		 * Usually reference to an object in other component
		 * @var obj
		 */
		model: null
	},
	
	/* Methods */
	
	_c: function(attributes) {
		console.groupCollapsed('Template.Constructor');
		this._super('', attributes);
		console.groupEnd();
	},
	
	/* Properties */
	
	/**
	 * Retrieve Template model
	 * @return obj Model (Default: Empty object)
	 */
	get_model: function() {
		var m = this.get_attribute('model', null, false);
		if ( !this.util.is_obj(m) ) {
			//Set default value
			m = {};
			this.set_attribute('model', m, false);
		}
		return m;
	},
	
	/**
	 * Check if instance has model
	 * @return bool TRUE if model is set, FALSE otherwise
	 */
	has_model: function() {
		return ( this.util.is_empty( this.get_model() ) ) ? false : true;
	},
	
	/**
	 * Check if specified attribute exists in model
	 * @param string key Attribute to check for
	 * @return bool TRUE if attribute exists, FALSE otherwise
	 */
	in_model: function(key) {
		return ( this.util.in_obj(this.get_model(), key) ) ? true : false;
	},
	
	/**
	 * Retrieve attribute
	 * Gives priority to model values
	 * @see Component.get_attribute()
	 * @param string key Attribute to retrieve
	 * @param mixed def (optional) Default value (Default: NULL)
	 * @param bool check_model (optional) Whether to check model or not (Default: TRUE)
	 * @param bool enforce_type (optional) Whether return value data type should match default value data type (Default: TRUE)
	 * @return mixed Attribute value
	 */
	get_attribute: function(key, def, check_model, enforce_type) {
		//Validate
		if ( !this.util.is_string(key) ) {
			//Invalid requests sent straight to super method
			return this._super(key, def, enforce_type);
		}
		if ( !this.util.is_bool(check_model) ) {
			check_model = true;
		}
		//Check if model is set
		var ret = null;
		if ( check_model && this.in_model(key) ) {
			ret = this.get_model()[key];
		} else {
			ret = this._super(key, def, enforce_type);
		}
		return ret;
	},
	
	/**
	 * Set attribute value
	 * Gives priority to model values
	 * @see Component.set_attribute()
	 * @param string key Attribute to set
	 * @param mixed val Value to set for attribute
	 * @param bool check_model (optional) Whether to check model or not (Default: TRUE)
	 * @return mixed Attribute value
	 */
	set_attribute: function(key, val, check_model) {
		console.groupCollapsed('Template.set_attribute');
		console.log('Key: %o \nValue: %o', key, val);
		//Validate
		if ( !this.util.is_string(key) || !this.util.is_set(val) ) {
			console.warn('Invalid request');
			console.groupEnd();
			return false;
		}
		if ( !this.util.is_bool(check_model) ) {
			check_model = true;
		}
		//Determine where to set attribute
		if ( check_model && this.in_model(key) ) {
			console.info('Setting model attribute: %o', this.get_model());
			//Set attribute in model
			this.get_model()[key] = val;
		} else {
			//Standard attributes
			this._super(key, val);
		}
		console.groupEnd();
	},
	
	/* Output */
	
	/**
	 * Render output
	 * @param Content_Item item Item to render template for
	 *  > loading: DOM elements created and item content about to be loaded
	 *  > success: Item content loaded, ready for display
	 */
	render: function(item) {
		console.group('Template.render');
		//Populate layout
		if ( this.util.is_type(item, View.Content_Item) ) {
			console.info('Rendering Item');
			//Iterate through tags and populate layout
			if ( this.has_tags() ) {
				this.trigger('render-loading');
				var tpl = this;
				var tags = this.get_tags(),
					tag_promises = [];
				console.info('Tags exist: %o', tags);
				//Render Tag output
				console.groupCollapsed('Processing Tags');
				$.each(tags, function(idx, tag) {
					console.log('Tag DOM: %o', tag.dom_get().get(0));
					console.groupCollapsed('Processing Tag: %o', [tag.get_name(), tag.get_prop()].join('.'));
					tag_promises.push(tag.render(item).done(function(r) {
						console.log('Tag rendered: %o', [r.tag.get_name(), r.tag.get_prop()].join('.'));
						console.group('Tag Processing Callback');
						console.info('Tag Output: %o', r.output);
						r.tag.dom_get().html(r.output);
						console.groupEnd();
					}));
					console.groupEnd();
				});
				console.groupEnd();
				//Fire event when all tags rendered
				$.when.apply($, tag_promises).done(function() {
					tpl.trigger('render-complete');
				});
			}
		} else {
			console.info('Building basic layout');
			//Get Layout (basic)
			this.trigger('render-init', this.dom_get());
		}
		console.groupEnd();
	},
	
	/*-** Layout **-*/
	
	/**
	 * Retrieve layout
	 * @param bool parsed (optional) TRUE retrieves parsed layout, FALSE retrieves raw layout (Default: TRUE)
	 * @return string Layout (HTML)
	 */
	get_layout: function(parsed) {
		console.groupCollapsed('Template.get_layout: %o', parsed);
		//Validate
		if ( !this.util.is_bool(parsed) ) {
			parsed = true;
		}
		//Determine which layout to retrieve (raw/parsed)
		var l = ( parsed ) ? this.parse_layout() : this.get_attribute('layout_raw', '');
		console.log('Layout: %o', l);
		console.groupEnd();
		return l;
	},
	
	/**
	 * Parse layout
	 * Converts template tags to HTML elements
	 * > Template tag properties saved to HTML elements for future initialization
	 * Returns saved layout if previously parsed
	 * @return string Parsed layout
	 */
	parse_layout: function() {
		//Check for previously-parsed layout
		var a = 'layout_parsed';
		var ret = this.get_attribute(a);
		//Return cached layout immediately
		if ( this.util.is_string(ret) ) {
			return ret;
		}
		//Parse raw layout
		ret = this.sanitize_layout( this.get_layout(false) );
		ret = this.parse_tags(ret);
		//Save parsed layout
		this.set_attribute(a, ret);
		
		//Return parsed layout
		return ret;
	},
	
	/**
	 * Set layout value
	 * @param string layout Parsed layout
	 */
	set_layout: function(layout) {
		console.group('Template.set_layout');
		if ( this.util.is_string(layout) && this.has_tags() ) {
			//Create DOM object
			var o = $(layout);
			//Attach tags to placeholders
			var tags = this.get_tags();
			var nodes = $(tags[0].get_selector(), o);
			console.info('Layout: %o \nDOM Tree: %o \nTags: %o \nNodes: %o \nSelector: %o', layout, o, tags.length, nodes.length, tags[0].get_selector());
			//Connect DOM elements with Tag instances
			nodes.each(function(idx) {
				//Make sure tag instance exists for node
				if ( idx >= tags.length ) {
					return false;
				}
				//Get corresponding tag instance
				var tag = tags[idx];
				//Attach DOM node to Tag instance
				tag.dom_set(this);
			});
		}
		console.log('Layout: %o', o);
		//Save attribute
		this.set_attribute('layout', o);
		console.groupEnd();
	},
	
	/**
	 * Sanitize layout
	 * @param obj|string l Layout string or jQuery object
	 * @return obj|string Sanitized layout (Same data type that was passed to method)
	 */
	sanitize_layout: function(l) {
		console.groupCollapsed('Template.sanitize_layout');
		console.log('Pre sanitize: %o', l);
		//Stop processing if invalid value
		if ( this.util.is_empty(l) ) {
			console.warn('Layout is empty, nothing to sanitize');
			console.groupEnd();
			return l;
		}
		//Set return type
		var rtype = ( this.util.is_string(l) ) ? 'string' : null;
		/* Quarantine hard-coded tags */
			
		//Create DOM structure from raw template
		var dom = $(l);
		//Find hard-coded tag nodes
		var tag_temp = new View.Template_Tag();
		var cls = tag_temp.get_class();
		var cls_new = ['x', cls].join('_');
		$(tag_temp.get_selector(), dom).each(function(idx) {
			//Replace matching class name with blocking class
			$(this).removeClass(cls).addClass(cls_new);
		});
		//Format return value
		switch ( rtype ) {
			case 'string' :
				dom = dom.wrap('<div />').parent().html();
				console.info('Converting DOM tree to string: %o', dom);
			default :
				l = dom;
		}
		console.groupEnd();
		return l;
	},
	
	/*-** Tags **-*/
	
	/**
	 * Extract tags from template
	 * Tags are replaced with DOM element placeholders
	 * Extracted tags are saved as element attribute values (for future use)
	 * @param string l Raw layout to parse
	 * @return string Parsed layout
	 */
	parse_tags: function(l) {
		console.group('Template.parse_tags');
		//Validate
		if ( !this.util.is_string(l) ) {
			return '';
		}
		//Parse tags in layout
		
		console.groupCollapsed('Find tags');
		//Tag regex
		var re = /\{{2}\s*(\w.*?)\s*\}{2}/gim;
		//Tag match results
		var match;
		//Iterate through template and find tags
		while ( match = re.exec(l) ) {
			//Replace tag in layout with DOM container
			l = l.substring(0, match.index) + this.get_tag_container(match[1]) + l.substring(match.index + match[0].length);
		}
		console.groupEnd();
		console.log('Parsed Layout: %o', l);
		console.groupEnd();
		return l;
	},
	
	/**
	 * Create DOM element container for tag
	 * @param string Tag ID (will be prefixed)
	 * @return string DOM element
	 */
	get_tag_container: function(tag) {
		//Build element
		console.log('Tag: %o', tag);
		var attr = this.get_tag_attribute();
		console.log('Attribute: %o', attr);
		return '<span %s="%s"></span>'.sprintf(attr, escape(tag)); 
	},
	
	get_tag_attribute: function() {
		return this.get_parent().get_component_temp(View.Template_Tag).dom_get_attribute();
	},
	
	/**
	 * Retrieve Template_Tag instance at specified index
	 * @param int idx (optional) Index to retrieve tag from
	 * @return Template_Tag Tag instance
	 */
	get_tag: function(idx) {
		var ret = null;
		if ( this.has_tags() ) {
			var tags = this.get_tags();
			if ( !this.util.is_int(idx) || 0 > idx || idx >= tags.length ) {
				idx = 0;
			}
			ret = tags[idx];
		}
		return ret;
	},
	
	/**
	 * Retrieve tags from template
	 * Subset of tags may be retrieved based on parameter values
	 * Template is parsed if tags not set
	 * @param string name (optional) Tag type to retrieve instances of
	 * @param string prop (optional) Tag property to retrieve instances of
	 * @return array Template_Tag instances
	 */
	get_tags: function(name, prop) {
		console.groupCollapsed('Template.get_tags');
		var a = 'tags';
		var tags = this.get_attribute(a);
		//Initialize tags
		if ( !this.util.is_array(tags) ) {
			tags = [];
			console.groupCollapsed('Retrieving tags');
			//Retrieve layout DOM tree
			var d = this.dom_get();
			//Select tag nodes
			var attr = this.get_tag_attribute();
			var nodes = $(d).find('[' + attr + ']');
			console.log('Nodes: %o', nodes);
			//Build tag instances from nodes
			$(nodes).each(function(idx) {
				//Get tag placeholder
				var el = $(this);
				var tag = new View.Template_Tag(unescape(el.attr(attr)));
				console.log('Node: %o \nTag: %o', el, tag);
				//Populate valid tags
				if ( tag.has_handler() ) {
					console.info('Tag has handler');
					//Add tag to array
					tags.push(tag);
					//Connect tag to DOM node
					tag.dom_set(el);
					//Set classes
					el.addClass(tag.get_classes(' '));
				}
				//Clear data attribute
				el.removeAttr(attr);
			});
			//Save tags
			this.set_attribute(a, tags);
			console.log('Saved tags: %o', tags);
			console.groupEnd();
		}
		tags = this.get_attribute(a, []);
		//Filter tags by parameters
		if ( !this.util.is_empty(tags) && this.util.is_string(name) ) {
			//Normalize
			if ( !this.util.is_string(prop) ) {
				prop = false;
			}
			var tags_filtered = [];
			var tc = null;
			for ( var x = 0; x < tags.length; x++ ) {
				tc = tags[x];
				if ( name == tc.get_name() ) {
					//Check tag property
					if ( !prop || prop == tc.get_prop() ) {
						tags_filtered.push(tc);
					}
				}
			}
			tags = tags_filtered;
		}
		console.log('Return value: %o', tags);
		console.groupEnd();
		return ( this.util.is_array(tags, false) ) ? tags : [];
	},
	
	/**
	 * Check if template contains tags
	 * @return bool TRUE if tags exist, FALSE otherwise
	 */
	has_tags: function() {
		return ( this.get_tags().length > 0 ) ? true : false;
	},
	
	/*-** DOM **-*/
	
	/**
	 * Custom DOM initialization 
	 */
	dom_init: function() {
		console.group('Template.dom_init');
		if ( !this.dom_has() ) {
			console.info('Layout needs to be parsed');
			//Create DOM object from parsed layout
			this.dom_set(this.get_layout());
		}
		console.groupEnd();
	},
	
	/**
	 * Retrieve DOM element(s) for specified tag
	 * @param string tag Name of tag to retrieve
	 * @param string prop (optional) Specific tag property to retrieve
	 * @return array DOM elements for tag
	 */
	dom_get_tag: function(tag, prop) {
		console.groupCollapsed('Template.dom_get_tag()');
		var ret = $();
		var tags = this.get_tags(tag, prop);
		if ( tags.length ) {
			//Build selector
			var level = null;
			if ( this.util.is_string(tag) ) {
				level = ( this.util.is_string(prop) ) ? 'full' : 'tag';
			}
			var sel = '.' + tags[0].get_class(level);
			console.log('Selector')
			ret = this.dom_get().find(sel);
		}
		console.log('Tag elements: %o', ret);
		console.groupEnd();
		return ret;
	},
};

View.Template = Component.extend(Template);

/**
 * Template tag 
 */
var Template_Tag = {
	/* Configuration */
	_slug: 'template_tag',
	_reciprocal: true,
	/* Properties */
	_attr_default: {
		name: null,
		prop: null,
		match: null
	},
	/**
	 * Tag Handlers
	 * Collection of Template_Tag_Handler instances
	 * @var obj 
	 */
	handlers: {},
	/* Methods */
	
	/**
	 * Constructor
	 * @param 
	 */
	_c: function(tag_match) {
		console.groupCollapsed('Template_Tag.Constructor');
		console.info('Parse tag instance');
		this.parse(tag_match);		
		console.groupEnd();
	},
	
	/**
	 * Set instance attributes using tag extracted from template
	 * @param string tag_match Extracted tag match
	 */
	parse: function(tag_match) {
		console.groupCollapsed('Template_Tag.parse');
		//Return default value for invalid instances
		if ( !this.util.is_string(tag_match) ) {
			console.groupEnd();
			return false;
		}
		//Parse instance options
		var parts = tag_match.split('|'),
			part;
		if ( !parts.length ) {
			console.groupEnd();
			return null;
		}
		var attrs = {
			name: null,
			prop: null,
			match: tag_match
		};
		//Get tag ID
		attrs.name = parts[0];
		//Get main property
		if ( attrs.name.indexOf('.') != -1 ) {
			attrs.name = attrs.name.split('.', 2);
			attrs.prop = attrs.name[1];
			attrs.name = attrs.name[0];
		}
		//Get other attributes
		for ( var x = 1; x < parts.length; x++ ) {
			part = parts[x].split(':', 1);
			if ( part.length > 1 &&  !( part[0] in attrs ) ) {
				//Add key/value pair to attributes
				attrs[part[0]] = part[1];
			}
		}
		//Save to instance
		this.set_attributes(attrs, true);
		console.groupEnd();
	},
	
	/**
	 * Render tag output
	 * @param Content_Item item
	 * @return obj jQuery.Promise object that is resolved when tag is rendered
	 * Parameters passed to callbacks
	 * > tag 	obj		Current tag instance
	 * > output	string	Tag output
	 */
	render: function(item) {
		var tag = this;
		return tag.get_handler().render(item, tag).pipe(function(output) {
			return {'tag': tag, 'output': output};
		});
	},
	
	/**
	 * Retrieve tag name
	 * @return string Tag name (DEFAULT: NULL)
	 */
	get_name: function() {
		return this.get_attribute('name');
	},
	
	/**
	 * Retrieve tag property
	 */
	get_prop: function() {
		return this.get_attribute('prop');
	},
	
	/**
	 * Retrieve tag handler
	 * @return Template_Tag_Handler Handler instance (Empty instance if handler does not exist)
	 */
	get_handler: function() {
		return ( this.has_handler() ) ? this.handlers[this.get_name()] : new View.Template_Tag_Handler('');
	},
	
	/**
	 * Check if handler exists for tag
	 * @return bool TRUE if handler exists, FALSE otherwise
	 */
	has_handler: function() {
		return ( this.get_name() in this.handlers );
	},
	
	/**
	 * Generate class names for DOM node
	 * @param string rtype (optional) Return data type
	 *  > Default: array
	 *  > If string supplied: Joined classes delimited by parameter
	 * @uses get_class() to generate class names
	 * @uses Array.join() to convert class names array to string
	 * @return array Class names
	 */
	get_classes: function(rtype) {
		//Build array of class names
		var cls = [
			//General tag class
			this.get_class(),
			//Tag name
			this.get_class('tag'),
			//Tag name + property
			this.get_class('full')
		];
		//Convert class names array to string
		if ( this.util.is_string(rtype) ) {
			cls = cls.join(rtype);
		}
		//Return class names
		return cls;
	},
	
	/**
	 * Generate DOM-compatible class name based with varied levels of specificity
	 * @param int level (optional) Class name specificity
	 *  > Default: General tag class (common to all tag elements)
	 *  > tag: Tag Name
	 *  > full: Tag Name + Property
	 * @return string Class name
	 */
	get_class: function(level) {
		var cls = '';
		switch ( level ) {
			case 'tag' :
				//Tag name
				cls = this.add_ns(this.get_name());
				break;
			case 'full' :
				//Tag name + property
				cls = this.add_ns([this.get_name(), this.get_prop()].join('_'));
				break;
			default :
				//General
				cls = this.get_ns(); 
				break;
		}
		return cls;
	},
	
	/**
	 * Generate tag selector based on specified class name level
	 * @param string level (optional) Class name specificity (@see get_class() for parameter values)
	 * @return string Tag selector
	 */
	get_selector: function(level) {
		return '.' + this.get_class(level);
	},
};

View.Template_Tag = Component.extend(Template_Tag);

/**
 * Theme tag handler
 */
var Template_Tag_Handler = {
	/* Configuration */
	_slug: 'template_tag_handler',
	/* Properties */
	_attr_default: {
		supports_modifiers: false,
		dynamic: false,
		props: {}
	},
	
	/* Methods */
	
	/**
	 * Render tag output
	 * @param Content_Item item Item currently being displayed
	 * @param Template_Tag Tag instance (from template)
	 * @return obj jQuery.Promise linked to rendering process
	 */
	render: function(item, instance) {
		console.group('Template_Tag_Handler.render');
		var dfr = $.Deferred();
		//Pass to attribute method
		var ret = this.call_attribute('render', item, instance);
		//Check for promise
		if ( this.util.is_promise(ret) ) {
			ret.done(function(output) {
				dfr.resolve(output);
			});
		} else {
			//Resolve non-promises immediately
			dfr.resolve(ret);
		}
		//Return promise
		console.groupEnd();
		return dfr.promise();
	},
	
	add_prop: function(prop, func) {
		//Get attribute
		var a = 'props';
		var props = this.get_attribute(a);
		//Validate
		if ( !this.util.is_string(prop) || !this.util.is_func(func) ) {
			return false;
		}
		if ( !this.util.is_obj(props, false) ) {
			props = {};
		}
		//Add property
		props[prop] = func;
		//Save attribute
		this.set_attribute(a, props);
	},
	
	handle_prop: function(prop, item, instance) {
		//Locate property
		var props = this.get_attribute('props');
		var out = '';
		if ( this.util.is_obj(props) && prop in props && this.util.is_func(props[prop]) ) {
			out = props[prop].call(this, item, instance);
		} else {
			out = item.get_viewer().get_label(prop);
		}
		return out;
	},
};

View.Template_Tag_Handler = Component.extend(Template_Tag_Handler);

console.groupCollapsed('Pre Init');
/* Update References */

//Attach to global object
SLB.attach('View', View);
View = SLB.View;
console.info('Updating references');
View.update_refs();

/*-** Registration **-*/

/* Content Types */
console.info('Adding default content types');
View.add_content_type('image', {
	match: /^.+\.(jpg|png|gif)$/i,
	render: function(item) {
		var dfr = $.Deferred();
		//Create image object
		var img = new Image();
		var type = this;
		//Set load event
		$(img).bind('load', function() {
			console.groupCollapsed('Content_Type.image.load (Callback)');
			//Save Data
			item.set_data(this);
			//Set attributes
			var attrs = {
				'dimensions': {'width': this.width, 'height': this.height},
			};
			item.set_attributes(attrs);
			//Build output
			var out = $('<img />', {'src': item.get_uri()});
			//Resolve deferred
			dfr.resolve(out);
			console.groupEnd();
		});
		//Load image
		img.src = item.get_uri();
		//Return promise
		return dfr.promise();
	}
});

/* Template Tags */
console.info('Adding template tag handlers');
/**
 * Item data tag
 */
View.add_template_tag_handler('item', {
	render: function(item, tag) {
		console.groupCollapsed('Template_Tag_Handler (Item).render: %o', tag.get_prop());
		console.log('Property: %o', item.get_attributes());
		var dfr = $.Deferred();
		var m = 'get_' + tag.get_prop();
		var ret = ( this.util.is_method(item, m) ) ? item[m]() : item.get_attribute(tag.get_prop(), '');
		if ( this.util.is_promise(ret) ) {
			ret.done(function(output) {
				dfr.resolve(output);
			});
		} else {
			dfr.resolve(ret);
		}
		console.groupEnd();
		return dfr.promise();
	}
});

/**
 * UI tag
 */
View.add_template_tag_handler('ui', {
	init: function(item, tag) {
		console.groupCollapsed('Template_Tag_Handler (UI).init: %o', tag.get_prop());
		var v = item.get_viewer();
		
		//Run only once for viewer
		var vid = v.get_id();
		var cl = arguments.callee;
		if ( !this.util.is_set(cl.viewers) ) {
			cl.viewers = [];
		}
		if ( cl.viewers.indexOf(vid) != -1 ) {
			console.warn('Events already initilized for viewer');
			console.groupEnd();
			return false;
		}
		cl.viewers.push(vid);
		
		//Add event handlers
		v.on('events-init', function(v) {
			console.info('Event Handler: Template_Tag_Handler(UI).complete');
			//Register event handlers

			/* Close */
			
			var close = function(e) {
				return v.close(e);
			};
			//Close button
			v.get_theme().dom_get_tag('ui', 'close').click(close);
			
			/* Navigation */
			
			var nav_next = function(e) {
				console.info('Viewer.event.nav_next');
				console.groupCollapsed('Tag.UI.nav_next');
				v.item_next();
				console.groupEnd();
			};
			
			var nav_prev = function(e) {
				console.info('Viewer.event.nav_prev');
				console.groupCollapsed('Tag.UI.nav_prev');
				v.item_prev();
				console.groupEnd();
			};
			
			v.get_theme().dom_get_tag('ui', 'nav_next').click(nav_next);
			v.get_theme().dom_get_tag('ui', 'nav_prev').click(nav_prev);
			
			/* Slideshow */
			
			var slideshow_control = function(e) {
				console.info('Viewer.event.slideshow_control');
				console.groupCollapsed('Tag.UI.slideshow_control');
				v.slideshow_toggle();
				console.groupEnd();
			};
			
			v.get_theme().dom_get_tag('ui', 'slideshow_control').click(slideshow_control);
		}, this);
		
		v.on('slideshow-toggle', function(v) {
			console.group('Tag.UI.slideshow-toggle');
			//Update slideshow control tag
			var tags = v.get_theme().get_tags('ui', 'slideshow_control');
			if ( tags.length ) {
				for ( var x = 0; x < tags.length; x++ ) {
					tags[x].render(v.get_item()).done(function(r) {
						r.tag.dom_get().html(r.output);
					});
				}
			}
			console.groupEnd();
		}, this);
		console.groupEnd();
	},
	render: function(item, tag) {
		console.groupCollapsed('Template_Tag_Handler (UI).render: %o', tag.get_prop());
		//Initialize event handlers
		this.call_attribute('init', item, tag);
		//Process content
		var dfr = $.Deferred();
		var ret = this.handle_prop(tag.get_prop(), item, tag);
		if ( this.util.is_promise(ret) ) {
			ret.done(function(output) {
				dfr.resolve(output);
			});
		} else {
			dfr.resolve(ret);
		}
		console.groupEnd();
		return dfr.promise();
	},
	props: {
		'slideshow_control': function(item, tag) {
			//Get slideshow status
			prop = ( item.get_viewer().slideshow_active() ) ? 'slideshow_stop' : 'slideshow_start';
			return item.get_viewer().get_label(prop);
		},
		'group_status': function(item, tag) {
			//Handle single items
			if ( item.get_group().is_single() ) {
				return '';
			}
			//Handle groups with multiple items
			out = item.get_viewer().get_label('group_status');
			var key,
				ph,
				delim = '%',
				handlers = {
				current: function() {
					return item.get_group(true).get_pos() + 1;
				},
				total: function() {
					return item.get_group().get_size();
				}
			};
			//Parse placeholders
			for ( key in handlers ) {
				ph = key.wrap(delim);
				if ( out.indexOf(ph) != -1 ) {
					out = out.replace(ph, handlers[key]());
				}
			}
			return out;
		}
	}
});
console.groupEnd();
})(jQuery);