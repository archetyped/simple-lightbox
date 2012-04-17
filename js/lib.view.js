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
	template_tags: {},
	
	/**
	 * Collection/Data type mapping
	 * > Key: Collection name
	 * > Value: Data type
	 * @var object
	 */
	collections: {},
	
	/* Options */
	options: {
		validate_links: false,
		ui_enabled_desc: true,
		ui_enabled_caption: true,
		ui_caption_src: true,
		slideshow_autostart: true,
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
			this.Theme
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
		if ( ( this.util.is_empty(id) || !this.util.is_obj(options) ) && !this.component_make_default(type) ) {
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
	 * Retrieve specified options
	 * @param array opts Array of option names
	 * @return object Specified options (Default: empty object)
	 */
	get_options: function(opts) {
		console.groupCollapsed('View.get_options');
		console.info('Options to get: %o', opts);
		var ret = {};
		//Validate
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
	
	/* Viewers */
	
	init_viewers: function() {
		console.groupCollapsed('View.init_viewers');
		//Reset viewers
		this.viewers = {};
		//Add default viewer
		
		this.add_viewer(this.util.add_prefix('default'));
		console.groupEnd();
	},
	
	add_viewer: function(v, options) {
		//Validate
		if ( !this.util.is_string(v) ) {
			return false;
		}
		if ( !this.util.is_obj(options, false) ) {
			options = {};
		}
		//Create viewer
		var v = new this.Viewer(v, options);
		//Add to collection
		this.viewers[v.get_id()] = v;
	},
	
	get_viewers: function() {
		return this.viewers;
	},
	
	has_viewer: function(v) {
		return ( this.util.is_string(v) && v in this.get_viewers() ) ? true : false;
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
		var sel = 'a[href][rel~="' + this.util.get_prefix() + '"]:not([rel~="' + this.util.add_prefix('off') + '"])';
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
		console.group('View.show_item');
		//Parse item
		if ( ! this.util.is_type(item, this.Content_Item) ) {
			//Create new instance
			var item = new this.Content_Item(item);
			item.show();
		}
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
	 * Initialize default theme
	 */
	init_theme: function() {
		console.groupCollapsed('View.init_theme');
		//Initialize default theme
		this.add_theme(this.util.add_prefix('default'));
		console.dir(this.themes);
		console.groupEnd();
	},
	
	/**
	 * Add theme
	 * @param string name Theme name
	 * @param obj attr Theme options
	 * @return obj New Theme instance
	 */
	add_theme: function(id, attr) {
		//Validate
		if ( !this.util.is_string(id) ) {
			id = this.util.add_prefix('default');
		}
		//Create theme
		var thm = new this.Theme(id, attr);
		//Add to collection
		this.themes[thm.get_id()] = thm;
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
	add_template_tag: function(id, attrs) {
		//Validate ID
		if ( this.util.is_string(id) ) {
			//Create new instance
			var tag = new this.Template_Tag(id, attrs);
			//Add to collection
			this.template_tags[tag.get_id()] = tag;
			//Return instance
			return tag;
		}
	}
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
	_el: null,
	
	/**
	 * DOM element attribute that stores component attributes
	 * @var string
	 */
	_el_attr: null,
	
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
		console.log('Property: %o \nGet Default: %o', cname, get_default);
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
	 * @param function type Component type
	 * @return object Component (NULL if invalid)
	 */
	set_component: function(name, ref) {
		console.groupCollapsed('Component.set_component: %o', name);
		console.log('Component: %o \nObject: %o', name, ref);
		var clear = null;
		//Make sure component property exists
		if ( !this.has_reference(name) ) {
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
		
		//Set (or clear) component reference
		this[name] = ( this.util.is_type(ref, ctype) ) ? ref : clear;
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
		console.groupCollapsed('Component.get_attribute(): %o', key);
		if ( !this.util.is_set(def) ) {
			def = null;
		}
		var a = this.get_attributes();
		console.groupEnd();
		return ( key in this.get_attributes() ) ? this.attributes[key] : def;
	},
	
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
	
	set_attributes: function(attributes, full) {
		if ( !this.util.is_bool(full) )
			full = false;
			
		//Reset attributes
		this.build_default_attributes();
		if ( full || this.util.is_empty(this.attributes) ) {
			this.attributes = $.extend({}, this._attr_default);
		}
		
		//Merge new/existing attributes
		if ( $.isPlainObject(attributes) && !this.util.is_empty(attributes) ) {
			$.extend(true, this.attributes, attributes);
		}
	},
	
	set_attribute: function(key, val) {
		if ( this.util.is_string(key) && this.util.is_set(val) ) {
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
	
	/* Configuration */
	
	_slug: 'viewer',
	
	_refs: {
		item: 'Content_Item',
		theme: 'Theme'
	},
	
	_attr_parent: ['theme', 'group_loop', 'ui_animate', 'ui_overlay_opacity', 'ui_labels'],
	
	_attr_map: {
		'group_loop': 'loop',
		'ui_animate': 'animate',
		'ui_overlay_opacity': 'overlay_opacity',
		'ui_labels': 'labels'
	},
	
	_attr_default: {
		loop: true,
		animate: true,
		overlay_opacity: '0.8',
		labels: {
			link_close: 'close',
			link_next: 'next &raquo;',
			link_prev: '&laquo; prev',
			slideshow_start: 'start slideshow',
			slideshow_stop: 'stop slideshow',
			slideshow_status: '',
			loading: 'loading'
		}
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
	
	loading: false,
	
	/* Methods */
	
	/* Setup */
	
	set_item: function(item) {
		console.groupCollapsed('Viewer.set_item');
		if ( this.util.is_type(item, View.Content_Item) ) {
			console.log('Item set: %o', item);
			this.item = item;
			console.groupEnd();
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
		if ( !this.util.is_bool(mode) ) {
			mode = true;
		}
		this.loading = mode;
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
		//Add item reference to viewer
		var i = this.set_item(item);
		//Load theme
		console.info('Getting theme');
		var t = this.get_theme();
		//Validate request
		if ( !i || !t ) {
			console.warn('Invalid request');
			console.groupEnd();
			this.exit();
		}
		//Set loading flag
		console.info('Set loading flag');
		this.set_loading();
		//Get theme output
		console.info('Rendering Theme layout');
		var l = t.render(item);
		console.log('Theme layout: %o', l);
		//Load into DOM
		
		//Display
		console.groupEnd();
	},
	
	exit: function() {
		console.groupCollapsed('Viewer.exit');
		this.reset();
		console.groupEnd();
	},
	
	reset: function() {
		this.set_item(false);
		this.set_loading(false);
	},
	
	/* Content */
	
	get_labels: function() {
		return this.get_attribute('labels', {});
	},
	
	get_label: function(name) {
		var lbls = this.get_labels();
		return ( name in lbls ) ? lbls[name] : '';
	},
	
	/* Theme */
	
	/**
	 * Retrieve theme reference
	 * @return object Theme reference
	 */
	get_theme: function() {
		return this.get_component('theme');
	},
	
	/**
	 * Set viewer's theme
	 * @param object theme Theme object
	 */
	set_theme: function(theme) {
		this.set_component('theme', theme);
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
	 * @uses template for raw HTML
	 * @uses item for item properties to populate template
	 * @return string Generated item output
	 */
	render: function(item) {
		console.groupCollapsed('Content_Type.render');
		//Validate
		var attr = 'render';
		var a = this.get_attribute(attr, null);
		var ret = '';
		//Stop processing types with no rendering functionality
		if ( !this.util.is_empty(a) ) {
			//Process regex patterns
			console.info('Rendering item');
			//String format
			if ( this.util.is_string(a) ) {
				console.log('Processing string format');
				ret = this.util.sprintf(a, item.get_uri());
			} 
			else if ( this.util.is_func(a) ) {
				console.info('Processing render function');
				ret =  a.apply(this, [item]);
			}
		}
		//Default
		console.info('Rendered Output: %o', ret);
		console.groupEnd();
		return ret;
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
	
	_el_attr: 'rel',
		
	_attr_default: {
		src: null,
		permalink: null,
		title: '',
		group: null,
		internal: false,
		output: null
	},
	
	/* References */
	
	group: null,
	viewer: null,
	type: null,
	
	/* Init */
	
	_c: function(el) {
		console.log('New Content Item');
		//Save element to instance
		this.set_ref(el);
	},
	
	/* Methods */
	
	/*-** Properties **-*/
	
	/**
	 * Retrieve item URI
	 */
	get_uri: function() {
		console.groupCollapsed('Item.get_uri');
		var ret = null;
		var e = this.get_element();
		if ( e ) {
			ret = $(e).attr('href');	
		}
		console.log('Item URI: %o', ret);
		console.groupEnd();
		return ret;
	},
	
	/**
	 * Retrieve item output
	 * Output generated based on content type if not previously generated
	 * @uses get_attribute() to retrieve cached output
	 * @uses set_attribute() to cache generated output
	 * @uses get_type() to retrieve item type
	 * @uses Content_Type.render() to generate item output
	 * @return string Generated output;
	 */
	get_output: function() {
		console.groupCollapsed('Item.get_output');
		console.info('Checking for cached output');
		var ret = this.get_attribute('output');
		if ( !this.util.is_string(ret) ) {
			console.info('Rendering output');
			console.info('Get item type');
			//Get item type
			var type = this.get_type();
			console.log('Item type: %o', type);
			//Render type-based output
			if ( !!type ) {
				ret = type.render(this);
			}
			console.info('Output Retrieved: %o', ret);
			console.info('Caching item output');
			//Cache output
			this.set_output(ret);
		}
		console.groupEnd();
		return ( this.util.is_empty(ret) ) ? '' : ret.toString();
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
	 */
	show: function() {
		console.group('Item.show');
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
	_reciprocal: true,
	_refs: {
		'viewer': 'Viewer',
		'template': 'Template'
	},
	_containers: ['viewer'],
	
	_attr_default: {
		template: null
	},
	
	/* References */
	
	viewer: null,
	template: null,
	
	/* Methods */
	
	/**
	 * Retrieve template instance
	 * @return Template instance
	 */
	get_template: function() {
		//Get saved template
		var ret = this.get_component('template', false, false);
		//Template needs to be initialized
		if ( this.util.is_empty(ret) ) {
			var attr = {},
				t = this.get_attribute('template');
			if ( this.util.is_string(t) ) {
				attr['template'] =  t;
			}
			ret = this.set_component('template', new View.Template(attr));
		}
		return ret;
	},
	
	/**
	 * Render Theme output
	 * @return string Theme output
	 */
	render: function(item) {
		console.group('Theme.render');
		//Retrieve layout
		var l = this.get_template().render();
		console.groupEnd();
		return l;
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
	
	_attr_default: {
		/**
		 * Raw layout template
		 * @var string
		 */	
		template: '',
		/**
		 * Parsed layout
		 * Placeholders processed
		 * @var string
		 */
		layout: null
	},
	
	_attr_parent: ['template'],
	
	/* Methods */
	
	_c: function(attributes) {
		console.groupCollapsed('Template.Constructor');
		this.set_attributes(attributes);
		console.groupEnd();
	},
	
	/**
	 * Parse layout from raw template
	 * Saves parsed layout for future requests
	 * @return obj Parsed layout (jQuery object)
	 */
	parse: function() {
		console.group('Template.parse');
		//Get raw template
		var l = this.get_attribute('template');
		if ( !this.util.is_empty(l) ) {
			//Get tags
			this.get_tags(l);
			//Convert tags to placeholder DOM elements
			
			//Save tag data to placeholders
			
		}
		this.set_layout(l);
		console.groupEnd();
		return l;
	},
	
	/**
	 * Render output
	 * @return string Layout HTML
	 */
	render: function() {
		console.group('Template.render');
		var l = this.get_layout();
		console.groupEnd();
		return l;
	},
	
	/*-** Layout **-*/
	
	/**
	 * Retrieve layout
	 * @return string Layout HTML
	 */
	get_layout: function() {
		console.group('Template.get_layout');
		var l = this.get_attribute('layout');
		if ( this.util.is_empty(l) ) {
			l = this.parse();
		}
		console.groupEnd();
		return l;
	},
	
	/**
	 * Set layout value
	 * @param string layout Parsed layout
	 */
	set_layout: function(layout) {
		this.set_attribute('layout', layout);
	},
	
	/*-** Tags **-*/
	
	/**
	 * Extract tags from template
	 * @param string t Template
	 * @return obj Tag instances grouped by tag 
	 */
	get_tags: function(t) {
		console.group('Template.get_tags');
		var re = /\{(\w.*?)\}/gim,
			tags = {};
		if ( this.util.is_string(t) ) {
			var match,
				tag;
			console.group('Find tags');
			while ( match = re.exec(t) ) {
				tag = this.parse_tag(match);
				if ( !!tag ) {
					//Add Tag to collection
					if ( !( tag.tag in tags ) ) {
						tags[tag.tag] = [];
					}
					tags[tag.tag].push(tag);
				}
			}
			console.groupEnd();
			console.info('Tags: %o', tags);
		}
		console.groupEnd();
		return tags;
	},
	
	/**
	 * Parse tag extracted from template
	 * @param array tag Extracted tag match
	 * @see RegExp.exec() for properties
	 * @return object Parsed tag properties
	 */
	parse_tag: function(tag) {
		console.groupCollapsed('Template.parse_tag');
		//Return default value for invalid instances
		if ( !this.util.is_array(tag) || tag.length < 2 || !this.util.is_string(tag[0]) ) {
			return null;
		}
		var instance = tag[1];
		//Parse instance options
		var parts = instance.split('|'),
			part;
		if ( !parts.length ) {
			console.groupEnd();
			return null;
		}
		var d = {
			tag: null,
			prop: null,
			match: null
		}
		var attrs = $.extend({}, d);
		//Get tag ID
		attrs.tag = parts[0];
		//Get main property
		var prop = attrs.tag.split('.', 2);
		if ( prop.length == 2 ) {
			attrs.tag = prop[0];
			attrs.prop = prop[1];
		}
		//Get other attributes
		for ( var x = 1; x < parts.length; x++ ) {
			part = parts[x].split(':', 1);
			if ( part.length > 1 &&  !( part[0] in attrs ) ) {
				//Add key/value pair to attributes
				attrs[part[0]] = part[1];
			}
		}
		//Save match attributes
		attrs.match = tag;
		console.groupEnd();
		return attrs;
	},
};

View.Template = Component.extend(Template);

/**
 * Theme tag handler
 */
var Template_Tag = {
	/* Configuration */
	
	_slug: 'template_tag',
	
	/* Properties */
	
	_attr_default: {
		supports_modifiers: false,
		dynamic: false
	},
	
	/* Methods */
	
	/**
	 * Parse tag instance
	 * @param string Tag instance
	 * @return obj Tag instance attributes
	 */
	parse: function(instance) {
		//Stop processing if instance already parsed
		if ( this.util.is_obj(instance) ) {
			return instance;
		}
		var d = {
			tag: this.get_id(),
			prop: ''
		}
		//Return default value for invalid instances
		if ( !this.util.is_string(instance) ) {
			return d;
		}
		//Parse instance options
		var parts = instance.split('|'),
			part;
		var attrs = $.extend({}, d);
		if ( parts.length > 0 ) {
			attrs.prop = parts[0];
			for ( var x = 1; x < parts.length; x++ ) {
				part = parts[x].split(':', 1);
				if ( part.length > 1 &&  !( part[0] in attrs ) ) {
					//Add key/value pair to attributes
					attrs[part[0]] = part[1];
				}
			}
		}
		return attrs;
	},
	
	/**
	 * Render tag output
	 * @param Content_Item item Item currently being displayed
	 * @param string instance Tag instance (from template)
	 * @return string Tag output
	 */
	render: function(item, instance) {
		var a = this.get_attribute('render', null);
		var out = '';
		if ( this.util.is_func(a) && this.util.is_type(item, View.Content_Item) ) {
			//Parse tag instance
			var attr = this.parse(instance);
			//Pass arguments to user-defined method
			out = a.apply(this, [item, attr]);
		}
		return out;
	}
};

View.Template_Tag = Component.extend(Template_Tag);

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
	render: '<img src="%s" />'
});

/* Template Tags */
console.info('Adding template tags');
/**
 * Item data tag
 */
View.add_template_tag('item', {
	render: function(item, attrs) {
		var m = 'get_' + attrs.prop;
		var o = ( this.util.is_method(item, m) ) ? this[m]() : this.get_attribute(attrs.prop); 
	}
});

/**
 * UI tag
 */
View.add_template_tag('ui', {
	render: function(item, attrs) {
		return item.get_viewer().get_label(attrs.prop);
	}
});
console.groupEnd();
})(jQuery);