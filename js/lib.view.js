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
		console.groupCollapsed('Init');
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
		//Check if item instance attached to element
		var key = this.get_component_temp(this.Content_Item).get_data_key();
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
			for ( var x = 0; x < g.length; x++ ) {
				console.groupCollapsed('Checking Type: %o', g[x]);
				type = g[x];
				//Init type if necessary
				if ( this.util.is_array(type, false) ) {
					console.log('Initializing type');
					type = g[x] = new this.Content_Type(type[0], type[1]);
				}
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
		types[priority].push([id, attributes]);
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
		console.group('View.init_themes');
		//Populate theme models
		var models = this.get_option('themes');
		console.log('Models: %o', models);
		if ( !this.util.is_obj(models) ) {
			models = {};
		}
		var id;
		for ( id in models ) {
			this.add_theme(id, models[id]);
		}
		console.groupEnd();
	},
	
	/**
	 * Add theme
	 * @param string name Theme name
	 * @param obj attr Theme options
	 * @return void
	 */
	add_theme: function(id, attr) {
		console.group('View.add_theme');
		console.log('ID: %o \nAttributes: %o', id, attr);
		//Validate
		if ( !this.util.is_string(id) ) {
			console.groupEnd();
			return false;
		}
		//Remap layout attribute
		if ( 'layout' in attr ) {
			attr['layout_raw'] = attr.layout;
			delete attr.layout;
		}
		//Create theme model
		var model = $.extend({'layout_raw': '', 'layout_parsed': ''}, attr);
		
		//Validate models property
		if ( !this.util.is_obj(this.Theme.prototype._models, false) ) {
			this.Theme.prototype._models = {};
		}
		
		//Add to Theme prototype
		this.Theme.prototype._models[id] = model;
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
		handler = new this.Template_Tag_Handler(id, attrs);
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
	 * DOM element attribute that stores component attributes
	 * @var string
	 */
	_dom_attr: null,
	
	/**
	 * Default attributes
	 * @var object
	 */
	_attr_default: {},
	
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
		
		//Set attributes
		console.info('Setting attributes: %o', attributes);
		this.set_attributes(attributes);
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
	 * @param bool get_default (optional) Whether or not to retrieve default object from controller if none exists in current instance (Default: TRUE)
	 * @param bool recursive (optional) Whether or not to check containers for specified component reference (Default: TRUE) 
	 * @return object|null Component reference (NULL if no component found)
	 */
	get_component: function(cname, get_default, recursive) {
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
		if ( !this.util.is_bool(get_default) ) {
			get_default = true;
		}
		if ( !this.util.is_bool(recursive) ) {
			recursive = true;
		}
		var ctype = this._refs[cname];
		
		console.log('Validated Parameters\nProperty: %o \nType: %o \nGet Default: %o \nRecursive: %o', cname, ctype._slug, get_default, recursive);

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
		console.info('Check for component in attributes');
		console.log('Attributes: %o', this.get_attributes());
		c = this.get_attribute(cname);
		console.log('Attribute value: %o', c);
		//Save object-specific component reference
		if ( !this.util.is_empty(c) ) {
			console.log('Saving component');
			c = this.set_component(cname, c);
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
		
		ref = ( this.util.is_type(ref, ctype) ) ? ref : clear;

		//Additional validation
		if ( !this.util.is_empty(ref) && this.util.is_func(validate) && !validate.apply(this, [ref]) ) {
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
	 * Initialize attributes
	 * @uses set_attributes() to Reset attributes
	 * @uses dom_get() to retrieve DOM element
	 * @uses _dom_attr to retrieve attributes from DOM element
	 * @param obj attributes (optional) Attributes to set
	 */
	parse_attributes: function(attributes) {
		console.groupCollapsed('Item.parse_attributes');
		//Reset attributes
		this.set_attributes(attributes, true);
		
		el = this.dom_get();
		if ( !this.util.is_empty(el) ) {
			//Get attributes from element
			console.info('Checking DOM element for attributes');
			var opts = $(el).get(0).attributes;
			if ( this.util.is_obj(opts) ) {
				console.group('Processing DOM Attributes: %o', opts);
				var opt, key;
				var attr_prefix = this.util.get_attribute();
				for ( var x = 0; x < opts.length; x++ ) {
					opt = opts[x];
					if ( opt.name.indexOf( attr_prefix ) == -1 ) {
						continue;
					}
					//Process internal attributes
					//Strip prefix
					key = opt.name.substr(attr_prefix.length);
					console.log('Attribute: %o \nValue: %o', key, opt.value);
					this.set_attribute(key, opt.value);
				}
				console.groupEnd();
			}
		}
		console.groupEnd();
	},
	
	/**
	 * Retrieve all instance attributes
	 * @uses parse_attributes() to initialize attributes (if necessary)
	 * @uses attributes
	 * @return obj Attributes
	 */
	get_attributes: function() {
		//Parse attributes on first access
		if ( this.util.is_bool(this.attributes) ) {
			this.parse_attributes();
		}
		return this.attributes;
	},
	
	/**
	 * Retrieve value of specified attribute for value
	 * @param string key Attribute to retrieve
	 * @param mixed def (optional) Default value if attribute is not set
	 * @return mixed Attribute value (NULL if attribute is not set)
	 */
	get_attribute: function(key, def) {
		if ( !this.util.is_set(def) ) {
			def = null;
		}
		return ( this.has_attribute(key) ) ? this.get_attributes()[key] : def;
	},
	
	/**
	 * Call attribute as method
	 * @param string attr Attribute to call
	 * @param arguments (optional) Additional arguments to pass to method
	 */
	call_attribute: function(attr) {
		console.group('Component.call_attribute');
		attr = this.get_attribute(attr, null);
		if ( this.util.is_func(attr) ) {
			console.info('Passing to attribute (method)');
			//Get arguments
			var args = Array.prototype.slice.call(arguments, 1);
			//Pass arguments to user-defined method
			attr.apply(this, args);
		}
		console.groupEnd();
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
	 * Generate default attributes for component
	 * @uses _attr_parent to determine options to retrieve from controller
	 * @uses View.get_options() to get values from controller
	 * @uses _attr_map to Remap controller attributes to instance attributes
	 * @uses _attr_default to Store default attributes
	 */
	build_default_attributes: function() {
		console.groupCollapsed('Component.build_default_attributes');
		console.log('Get parent options: %o', this._attr_parent);
		//Get parent options
		var opts = this.get_parent().get_options(this._attr_parent);
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
		console.groupEnd();
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

		//Reset attributes
		this.build_default_attributes();
		if ( full || this.util.is_empty(this.attributes) ) {
			this.attributes = $.extend({}, this._attr_default);
		}
		
		//Merge new/existing attributes
		if ( $.isPlainObject(attributes) && !this.util.is_empty(attributes) ) {
			$.extend(this.attributes, attributes);
		}
		console.dir(this.attributes);
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
		//Save instance to DOM object
		$(el).data(this.get_data_key(), this);
		//Save DOM object to instance
		if ( this._reciprocal ) {
			this._dom = $(el);
		}
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
	 * Execute callback function
	 * Validates callback and passes data to it
	 * @param function|obj callback Callback to validate/execute
	 *  > If callback is obj, next parameter is key containing actual callback
	 * @param mixed data Data to pass to callback function (Variable number of parameters may be passed to callback)
	 * @return mixed Original data 
	 */
	do_callback: function(callback, data) {
		var i = 1;
		//Parse request
		if ( this.util.is_obj(callback) && this.util.is_string(arguments[i]) && ( arguments[i] in callback ) ) {
			callback = callback[arguments[i]];
			i++;
		}
		//Build callback parameters
		data = ( arguments.length > i ) ? Array.prototype.slice.call(arguments, i) : [];
		//Validate
		if ( this.util.is_func(callback) ) {
			//Execute callback
			callback.apply(this, data);
		}
		//Return first data parameter
		return ( this.util.is_array(data) && data.length > 0 ) ? data[0] : data;
	},
	
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
	
	on: function(event, context, func, options) {
		console.group('Component.on: %o', event);
		/* Validate */
		//Context
		if ( this.util.is_func(context) && !this.util.is_func(func) ) {
			//Shift parameters
			options = func;
			func = context;
		}
		//Request
		if ( !this.util.is_string(event) || !this.util.is_func(func) ) {
			console.groupEnd();
			return false;
		}
		//Options
		if ( !this.util.is_obj(options) ) {
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
			if ( this.util.is_num(context) ) {
				//Convert number to string context
				context = context.toString();
			} else if ( this.util.is_type(context, View.Component) ) {
				//Build context from component instance
				context = context.get_id(true);
				//Force unique handler
				options.overwrite = false;
			} else {
				//Default context
				context = context_def;
			}
		}
		console.info('Event: %o \nContext: %o', event, context);
		var es = this._events;
		//Setup event
		if ( !( event in es ) || !this.util.is_obj(es[event]) ) {
			e = es[event] = {};
		}
		//Check for duplicate handler
		if ( !options.overwrite && context != context_def && ( context in e ) ) {
			console.groupEnd();
			return false;
		}
		//Add context to event
		if ( !( context in e ) ) {
			e[context] = [];
		}
		//Add event handler
		e[context].push(func);
		console.groupEnd();
	},
		
	trigger: function(event) {
		console.groupCollapsed('Component.trigger: %o', event);
		//Validate
		if ( !this.util.is_string(event) || !( event in this._events ) ) {
			console.groupEnd();
			return false;
		}
		//Call handlers for event
		var es = this._events;
		var ec;
		for ( ev in this._events[event] ) {
			ec = this._events[event][ev];
			for ( var x = 0; x < ec.length; x++ ) {
				//Call handler, passing component instance
				ec[x](this);
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
		var i = this.set_component('item', item, function(instance) {
			return ( !this.util.is_empty(instance.get_type()) );
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
		var ret = this.get_component('theme', false, false);
		if ( this.util.is_empty(ret) ) {
			//Theme needs to be initialized
			ret = this.set_component('theme', new View.Theme());
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
		console.group('Viewer.set_loading: %o', mode);
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
		console.group('Viewer.show');
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
		this.dom_build();
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
	 * Load output into DOM
	 */
	dom_build: function() {
		console.group('Viewer.dom_build');
		//Get theme output
		console.info('Rendering Theme layout');
		var v = this;
		this.get_theme().render(this.get_item(), {
			'loading': function(output) {
				console.groupCollapsed('Viewer.dom_build.loading (Callback)');
				//Set loading flag
				v.set_loading();
				//Display overlay
				v.overlay_show();
				//Display viewer
				var top = $(document).scrollTop() + Math.min($(window).height() / 15, 20);
				v.dom_get('layout').css('top', top + 'px').show();
				console.groupEnd();
			},
			'complete': function(output) {
				console.groupCollapsed('Viewer.dom_build.complete (Callback)');
				console.log('Completed output: %o', output);
				//Resize viewer to fit item
				var dim = v.get_item().get_dimensions();
				var content = v.dom_get_tag('item', 'content');
				//Display item media
				content.width(dim.width).height(dim.height + 20).show();
				//Unset loading flag
				v.unset_loading();
				console.info('Theme loaded');
				//Set classes
				var d = v.dom_get();
				var classes = ['item_single', 'item_multi'];
				var ms = ['addClass', 'removeClass']; 
				if ( !v.get_item().get_group().is_single() ) {
					ms.reverse();
				}
				var m;
				for ( var x = 0; x < ms.length; x++ ) {
					d[ms[x]](classes[x]);
				}
				//Bind events
				v.events_init();
				//Trigger event
				v.trigger('complete');
				//Set viewer as initialized
				v.init = true;
				console.groupEnd();
			}
		});
		console.groupEnd();
	},
	
	/**
	 * Retrieve DOM element tag 
	 */
	dom_get_tag: function(tag, prop) {
		return $(this.get_theme().get_template().dom_get_tag(this, tag, prop));
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
					this.get_theme().get_id(true)
				].join(' ')
			}).appendTo(this.dom_get_container()).hide());
			console.log('Theme ID: %o', this.get_theme().get_id(true));
			console.log('DOM element added');
			//Add theme layout (basic)
			var t = this;
			console.info('Rendering basic theme layout');
			this.get_theme().render(null, {
				'init': function(data) {
					console.groupCollapsed('Viewer.dom_init.init (callback)');
					console.info('Basic layout: %o', data);
					t.dom_put('layout', data);
					console.groupEnd();
				}
			});
		}
		console.groupEnd();
	},
	
	/**
	 * Restore DOM
	 * Show overlapping DOM elements, etc.
	 * @TODO Build functionality
	 */
	dom_restore: function() {},
	
	/* Overlay */
	
	/**
	 * Determine if overlay is enabled for viewer
	 * @return bool TRUE if overlay is enabled, FALSE otherwise
	 */
	overlay_enabled: function() {
		var ov = this.get_attribute('overlay_enabled');
		return ( this.util.is_bool(ov) && ov );
	},
	
	/**
	 * Retrieve overlay DOM element
	 * @return jQuery Overlay element (NULL if no overlay set for viewer)
	 */
	get_overlay: function() {
		var o = null;
		var el = 'overlay';
		if ( this.dom_has(el) ) {
			o = this.dom_get(el);
		} else if ( this.overlay_enabled() ) {
			o = this.dom_put(el).hide();
		}
		return $(o);
	},
	
	/**
	 * Display overlay
	 */
	overlay_show: function() {
		this.dom_get().show();
		this.get_overlay().fadeIn();
	},
	
	/**
	 * Hide overlay
	 */
	overlay_hide: function() {
		this.get_overlay().fadeOut();
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
		var l = this.dom_get('layout');
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
		this.trigger('events_init');
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
		this.trigger('slideshow_start');
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
		this.trigger('slideshow_stop');
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
		this.trigger('slideshow_toggle');
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
		this.trigger('slideshow_pause');
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
		g.on('item_next', function(g) {
			v.trigger('item_next');
		});
		g.show_next();
	},
	
	/**
	 * Previous item
	 */
	item_prev: function() {
		var g = this.get_item().get_group(true);
		var v = this;
		g.on('item_prev', function(g) {
			v.trigger('item_prev');
		});
		g.show_prev();
	},
	
	/**
	 * Close viewer
	 */
	close: function(e) {
		console.group('Viewer.close');
		console.log('Init: %o \nItem: %o', this.init, this.get_item().dom_get());
		
		//Close viewer
		this.dom_get_container().find('.slb_viewer_layout').hide();
		
		//Hide overlay
		this.overlay_hide();
		
		//Restore DOM
		this.dom_restore();
		
		//End processes
		this.exit();
		
		this.trigger('close');
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
	
	_attr_default: {
		
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
		console.group('Group.get_prev');
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
		'item': 'Content_Item'
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
		return this.get_component('item', false);
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
		var m = this.get_attribute(attr, null);
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
				return ( m.apply(this, [item]) ) ? true : false;
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
	 * Passes retrieved output to callback function
	 * @param Content_Item item Item to render output for
	 * @param function callback Function to execute once output rendered
	 */
	render: function(item, callback) {
		console.groupCollapsed('Content_Type.render');
		//Validate
		var a = this.get_attribute('render', null);
		//String format
		if ( this.util.is_string(a) ) {
			console.log('Processing string format');
			this.do_callback(callback, a.sprintf(item.get_uri()));
		} else {
			//Pass item and callback to method
			console.info('Processing render function');
			this.call_attribute('render', item, callback);
		}
		console.groupEnd();
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
	
	_dom_attr: 'rel',
		
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
	},
	
	/* Methods */
	
	/*-** Attributes **-*/
	
	/**
	 * Build default attributes
	 * Populates attributes with asset properties (attachments)
	 * Overrides super class method
	 * @uses Component.build_default_attributes()
	 */
	build_default_attributes: function() {
		console.groupCollapsed('Content_Item.build_default_attributes');
		this._super();
		//Add asset properties
		var key = this.dom_get().attr('href') || null;
		var assets = this.get_parent().assets || null;
		console.log('Key: %o \nAssets: %o \nDefault Attributes: %o', key, assets, this._attr_default['id']);
		//Merge asset data with default attributes
		if ( !this.util.is_empty(key) && $.isPlainObject(assets) && ( key in assets ) && $.isPlainObject(assets[key]) ) {
			this._attr_default = $.extend({}, this._attr_default, assets[key]);
			console.log('Default Attributes Updated: %o', this._attr_default);
		}
		console.groupEnd();
	},
	
	/*-** Properties **-*/
	
	/**
	 * Retrieve item URI
	 * @param string mode (optional) Which URI should be retrieved
	 * > source: Media source
	 * > permalink: Item permalink
	 * @return string Item URI
	 */
	get_uri: function(mode) {
		console.groupCollapsed('Item.get_uri');
		if ( ['source', 'permalink'].indexOf(mode) == -1 ) {
			mode = 'source';
		}
		console.log('Mode: %o', mode);
		var ret = null;
		//Source URI
		if ( 'source' == mode ) {
			ret = this.get_attribute('source');
		}
		
		//Permalink URI
		if ( !ret ) {
			ret = this.dom_get().attr('href');
		}
		
		console.log('Item URI: %o', ret);
		console.groupEnd();
		return ret;
	},
	
	get_title: function(callback) {
		var prop = 'title';
		var title = '';
		//Metadata
		
		//Caption
		if ( !title ) {
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
		
		//Image title
		if ( !title ) {
			var img = this.dom_get().find('img').first();
			title = $(img).attr('title') || $(img).attr('alt');
		}
		
		//Link title
		if ( !title ) {
			title = this.dom_get().attr(prop);
		}
		
		//Return value
		this.set_attribute(prop, title)
		return this.do_callback(callback, title);
	},
	
	get_content: function(callback) {
		this.get_output(callback);
	},
	
	/**
	 * Retrieve item dimensions
	 * Wraps Content_Type.get_dimensions() for type-specific dimension retrieval
	 * @return obj Item `width` and `height` properties (px) 
	 */
	get_dimensions: function() {
		var dim = this.get_attribute('dimensions');
		if ( !$.isPlainObject(dim) ) {
			dim = {};
		}
		dim = $.extend({'width': 0, 'height': 0}, dim);
		return dim;
	},
	
	/**
	 * Retrieve item output
	 * Output generated based on content type if not previously generated
	 * @uses get_attribute() to retrieve cached output
	 * @uses set_attribute() to cache generated output
	 * @uses get_type() to retrieve item type
	 * @uses Content_Type.render() to generate item output
	 */
	get_output: function(callback) {
		console.groupCollapsed('Item.get_output');
		console.info('Checking for cached output');
		//Check for cached output
		var ret = this.get_attribute('output');
		if ( this.util.is_string(ret) ) {
			this.do_callback(callback, ret);
		} else {
			//Render output from scratch (if necessary)
			console.info('Rendering output');
			console.info('Get item type');
			//Get item type
			var type = this.get_type();
			console.log('Item type: %o', type);
			//Render type-based output
			if ( !!type ) {
				var instance = this;
				type.render(this, function(output) {
					console.info('Output Retrieved: %o', output);
					console.info('Caching item output');
					//Cache output
					instance.set_output(output);
					instance.do_callback(callback, output);
				});
			}
		}
		console.groupEnd();
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
		if ( this.util.is_string(v) && this.get_parent().has_viewer(v) ) {
			v = this.get_parent().get_viewer(v);
		}
		
		//Set or clear viewer property
		this.viewer = ( this.util.is_type(v, View.Viewer) ) ? v : false;
		
		//Return value for confirmation
		return this.viewer;
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
		var g = this.get_component(prop, false, false);
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
	 * @uses set_component() to save reference to retrieved Content_Type instance
	 * @uses View.get_content_type() to determine item content type (if necessary)
	 * @return Content_Type|null Content Type of item (NULL no valid type exists)
	 */
	get_type: function() {
		console.groupCollapsed('Item.get_type');
		console.info('Retrieving saved type reference');
		var t = this.get_component('type', false, false);
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
	
	/* Actions */
	
	/**
	 * Display item in viewer
	 * @uses get_viewer() to retrieve viewer instance for item
	 * @uses Viewer.show() to display item in viewer
	 * @TODO Debug execution (hanging)
	 */
	show: function() {
		console.groupCollapsed('Item.show');
		//Validate content type
		if ( !this.get_type() ) {
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
	 * @uses Component._c()
	 */
	_c: function(id, attributes) {
		console.groupCollapsed('Theme.Constructor');
		//Pass parameters to parent constructor
		this._super(id, attributes);
		//Set theme model
		this.set_model(id);

		console.groupEnd();
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
				var id = this.util.add_prefix('default');
			}
			console.log('Models: %o', models);
			//Select first theme model if specified model is invalid
			if ( ! ( id in models ) ) {
				id = Object.keys(models.keys)[0];
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
			if ( 'name' in m ) {
				this.set_id(m.name);
			}
		}
	},
	
	/* Template */
	
	/**
	 * Retrieve template instance
	 * @return Template instance
	 */
	get_template: function() {
		console.groupCollapsed('Theme.get_template');
		//Get saved template
		var ret = this.get_component('template', false, false);
		//Template needs to be initialized
		if ( this.util.is_empty(ret) ) {
			//Pass model to Template instance
			var attr = { 'model': this.get_model() };
			ret = this.set_component('template', new View.Template(attr));
		}
		console.groupEnd();
		return ret;
	},
	
	/* Output */
	
	/**
	 * Render Theme output
	 * Output passed to callback function
	 * @param Content_Item item Item to render theme for
	 * @param obj callbacks Functions to execute with rendered output (@see Template.render for reference)
	 */
	render: function(item, callbacks) {
		console.group('Theme.render');
		//Retrieve layout
		this.get_template().render(item, callbacks);		console.groupEnd();
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
	
	has_model: function() {
		return ( this.util.is_empty( this.get_model() ) ) ? false : true;
	},
	
	/**
	 * Check if specified attribute exists in model
	 * @param string key Attribute to check for
	 * @return bool TRUE if attribute exists, FALSE otherwise
	 */
	in_model: function(key) {
		return ( key in this.get_model() ) ? true : false;
	},
	
	/**
	 * Retrieve attribute
	 * Gives priority to model values
	 * @see Component.get_attribute()
	 * @param string key Attribute to retrieve
	 * @param mixed def (optional) Default value (Default: NULL)
	 * @param bool check_model (optional) Whether to check model or not (Default: TRUE)
	 * @return mixed Attribute value
	 */
	get_attribute: function(key, def, check_model) {
		//Validate
		if ( !this.util.is_string(key) ) {
			//Invalid requests sent straight to super method
			return this._super(key, def);
		}
		if ( !this.util.is_bool(check_model) ) {
			check_model = true;
		}
		//Check if model is set
		var ret = null;
		if ( check_model && this.in_model(key) ) {
			ret = this.get_model()[key];
		} else {
			ret = this._super(key, def);
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
	 * Output passed to callback function
	 * @param Content_Item item Item to render template for
	 * @param obj callbacks Functions to execute with rendered output
	 *  > loading: DOM elements created and item content about to be loaded
	 *  > success: Item content loaded, ready for display
	 */
	render: function(item, callbacks) {
		console.group('Template.render');
		//Populate layout
		if ( this.util.is_type(item, View.Content_Item) ) {
			console.info('Rendering Item');
			var v = item.get_viewer();
			//Initialize Viewer in DOM
			var d = v.dom_get();
			//Iterate through tags (DOM placeholders) and populate layout
			if ( this.has_tags() ) {
				this.do_callback(callbacks, 'loading', d);
				var instance = this;
				var tags = this.get_tags(),
					tag,
					tag_count = 0;
				console.info('Tags exist: %o', tags);
				//Render Tag output
				for ( var x = 0; x < tags.length; x++ ) {
					tag = tags[x];
					console.log('Tag DOM: %o', tag.dom_get().get(0));
					console.groupCollapsed('Processing Tag: %o', [tag.get_name(), tag.get_prop()].join('.'));
					tag.render(item, function(output) {
						console.log('Tag rendered: %o', [this.get_name(), this.get_prop()].join('.'));
						console.group('Tag Processing Callback');
						console.info('Tag Output: %o', output);
						this.dom_get().html(output);
						tag_count++;
						console.log('Parsed tags: %o / %o', tag_count, tags.length);
						//Execute callback once all tags have been rendered
						if ( tag_count == tags.length ) {
							instance.do_callback(callbacks, 'complete', d);
						}
						console.groupEnd();
					});
					console.groupEnd();
				}
			}
		} else {
			//Get Layout (basic)
			console.groupEnd();
			return this.do_callback(callbacks, 'init', this.dom_get());
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
		var ret = this.get_attribute(a, null);
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
		if ( this.util.is_array(tags) && this.util.is_string(name) ) {
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
		return ( this.util.is_array(tags) ) ? tags : [];
	},

	/**
	 * Save tags extracted from template
	 * @param array tags Tags to save
	 */
	set_tags: function(tags) {
		if ( !this.util.is_array(tags) ) {
			tags = [];
		}
		this.set_attribute('tags', tags);
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
	 * @param obj component Component instance to find tag in
	 * @param string tag Name of tag to retrieve
	 * @param string prop (optional) Specific tag property to retrieve
	 * @return array DOM elements for tag
	 */
	dom_get_tag: function(component, tag, prop) {
		console.groupCollapsed('Template.dom_get_tag()');
		if ( !this.util.is_type(component, Component) )
			return null;
		//Build selector
		var sel = null;
		var delim = '_';
		if ( this.util.is_type(tag, this.get_parent().Template_Tag) ) {
			//ID selector
			sel = '#' + tag.get_id(true);
		} else if ( this.util.is_string(tag) ) {
			//Create temporary tag instance
			var tag_temp = new View.Template_Tag();
			//Class selector
			sel = [tag_temp.get_ns(), tag];
			if ( this.util.is_string(prop) ) {
				sel.push(prop);
			}
			sel = '.' + sel.join(delim);
		}
		console.info('Selector: %o', sel);
		console.groupEnd();
		//Return DOM elements
		return $(sel, component.dom_get());
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
	 * @param function callback Callback function to pass output to
	 */
	render: function(item, callback) {
		return ( this.has_handler() ) ? this.get_handler().render(item, this, callback.bind(this)) : '';
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
	 * @return Template_Tag_Handler Handler instance (NULL if handler does not exist)
	 */
	get_handler: function() {
		return ( this.has_handler() ) ? this.handlers[this.get_name()] : null;
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
	 * Rendered output passed to callback function
	 * @param Content_Item item Item currently being displayed
	 * @param Template_Tag instance Tag instance (from template)
	 * @param function callback Callback function to pass output to
	 */
	render: function(item, instance, callback) {
		console.group('Template_Tag_Handler.render');
		this.call_attribute('render', item, instance, callback);
		console.groupEnd();
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
	render: function(item, callback) {
		//Create image object
		var img = new Image();
		var instance = this;
		//Set load event (with callback)
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
			//Do callback
			instance.do_callback(callback, out);
			console.groupEnd();
		});
		
		//Load image
		img.src = item.get_uri();
	}
});

/* Template Tags */
console.info('Adding template tag handlers');
/**
 * Item data tag
 */
View.add_template_tag_handler('item', {
	render: function(item, tag, callback) {
		console.groupCollapsed('Template_Tag_Handler (Item).render: %o', tag.get_prop());
		console.log('Property: %o', item.get_attributes());
		var m = 'get_' + tag.get_prop();
		if ( this.util.is_method(item, m) ) {
			item[m](callback);
		} else {
			this.do_callback(callback, item.get_attribute(tag.get_prop(), ''));	
		}
		console.groupEnd();
	}
});

/**
 * UI tag
 */
View.add_template_tag_handler('ui', {
	init: function(item, tag, callback) {
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
		v.on('events_init', this, function(v) {
			console.info('Event Handler: Template_Tag_Handler(UI).complete');
			//Register event handlers

			/* Close */
			
			var close = function(e) {
				return v.close(e);
			};
			//Close button
			v.dom_get_tag('ui', 'close').click(close);
			
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
			
			v.dom_get_tag('ui', 'nav_next').click(nav_next);
			v.dom_get_tag('ui', 'nav_prev').click(nav_prev);
			
			/* Slideshow */
			
			var slideshow_control = function(e) {
				console.info('Viewer.event.slideshow_control');
				console.groupCollapsed('Tag.UI.slideshow_control');
				v.slideshow_toggle();
				console.groupEnd();
			};
			
			v.dom_get_tag('ui', 'slideshow_control').click(slideshow_control);
		});
		
		v.on('slideshow_toggle', this, function(v) {
			console.group('Tag.UI.slideshow_toggle');
			//Render slideshow control tag
			var nodes = v.dom_get_tag('ui', 'slideshow_control');
			console.log('Nodes: %o', nodes.length);
			if ( nodes.length ) {
				var tag_temp = View.get_component_temp(View.Template_Tag);
				nodes.each(function(idx) {
					var el = $(this);
					//Get tag
					var tag = $(this).data(tag_temp.get_data_key());
					if ( v.util.is_type(tag, View.Template_Tag) ) {
						tag.render(v.get_item(), function(output) {
							el.html(output);
						});
					}
				});
			}
			console.groupEnd();
		});
		console.groupEnd();
	},
	render: function(item, tag, callback) {
		console.groupCollapsed('Template_Tag_Handler (UI).render: %o', tag.get_prop());
		//Initialize event handlers
		this.call_attribute('init', item, tag, callback);
		//Process content
		var out = this.handle_prop(tag.get_prop(), item, tag);
		console.groupEnd();
		this.do_callback( callback, out );
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