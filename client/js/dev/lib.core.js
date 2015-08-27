/**
 * Core
 * @package SLB
 * @author Archetyped
 */
if ( window.jQuery ){(function($) {
'use strict';

/**
 * Extendible class
 * Adapted from John Resig
 * @link http://ejohn.org/blog/simple-javascript-inheritance/ 
 */
var c_init = false;
var Class = function() {};

/**
 * Create class that extends another class
 * @param object members Child class' properties
 * @return function New class
 */
Class.extend = function(members) {
	var _super = this.prototype;
	
	// Copy instance to prototype
	c_init = true;
	var proto = new this();
	c_init = false;
	
	var val, name; 
	// Scrub prototype objects (Decouple from super class)
	for ( name in proto ) {
		if ( $.isPlainObject(proto[name]) ) {
			val = $.extend({}, proto[name]);
			proto[name] = val;
		}
	}
	
	/**
	 * Create class method with access to super class method
	 * @param string nm Method name
	 * @param function fn Class method
	 * @return function Class method with access to super class method
	 */
	var make_handler = function(nm, fn) {
		return function() {
			// Cache super variable
			var tmp = this._super;
			// Set variable to super class method
			this._super = _super[nm];
			// Call method
			var ret = fn.apply(this, arguments);
			// Restore super variable
			this._super = tmp;
			// Return value
			return ret;
		};
	};
	// Copy properties to Class
	for ( name in members ) {
		// Add access to super class method to methods
		if ( 'function' === typeof members[name] && 'function' === typeof _super[name] ) {
			proto[name] = make_handler(name, members[name]);
		} else {
			// Transfer properties
			// Objects are copied, not referenced
			proto[name] = ( $.isPlainObject(members[name]) ) ? $.extend({}, members[name]) : members[name];
		}
	}
	
	/**
	 * Class constructor
	 * Supports pre-construction initilization (`Class._init()`)
	 * Supports passing constructor for new classes (`Class._c()`)
	 */
	function Class() {
		if ( !c_init ) {
			// Private initialization
			if ( 'function' === typeof this._init ) {
				this._init.apply(this, arguments);
			}
			// Main Constructor
			if ( 'function' === typeof this._c ) {
				this._c.apply(this, arguments);
			}
		}
	}
	
	
	// Populate new prototype
	Class.prototype = proto;
	
	// Set constructor
	Class.prototype.constructor = Class;
	
	// Set extender
	Class.extend = this.extend;
	
	// Return function
	return Class;
};

/**
 * Base Class
 */
var Base = {
	/* Properties */
	
	/**
	 * Base object flag
	 * @var bool
	 */
	base: false,
	/**
	 * Instance parent
	 * @var object
	 */
	_parent: null,
	/**
	 * Class prefix
	 * @var string
	 */
	prefix: 'slb',
	
	/* Methods */
	
	/**
	 * Constructor
	 * Sets instance parent
	 */
	_init: function() {
		this._set_parent();
	},
	
	/**
	 * Set instance parent
	 * Set utilities parent to current instance
	 * @param obj p Parent instance
	 */
	_set_parent: function(p) {
		if ( this.util.is_set(p) ) {
			this._parent = p;
		}
		this.util._parent = this;
	},
	
	/**
	 * Attach new member to instance
	 * Member can be property (value) or method
	 * @param string name Member name
	 * @param object data Member data
	 * @param bool simple (optional) Save new member as data object or new class instance (Default: new instance)
	 * @return obj Attached object
	 */
	attach: function(member, data, simple) {
		var ret = data;
		// Validate
		simple = ( typeof simple === 'undefined' ) ? false : !!simple;
		// Add member to instance
		if ( 'string' === $.type(member) ) {
			// Prepare member value
			if ( $.isPlainObject(data) && !simple ) {
				// Set parent reference for attached instance
				data['_parent'] = this;
				// Define new class
				data = this.Class.extend(data);
			}
			// Save member to current instance
			// Initialize new instance if data is a class
			this[member] = ( 'function' === $.type(data) ) ? new data() : data;
			ret = this[member];
		}
		return ret;
	},
	
	/**
	 * Check for child object
	 * Child object can be multi-level (e.g. Child.Level2child.Level3child)
	 * 
	 * @param string child Name of child object
	 */
	has_child: function(child) {
		// Validate
		if ( !this.util.is_string(child) ) {
			return false;
		}
		
		var children = child.split('.');
		child = null;
		var o = this;
		var x;
		for ( x = 0; x < children.length; x++ ) {
			child = children[x];
			if ( "" === child ) {
				continue;
			}
			if ( this.util.is_obj(o) && o[child] ) {
				o = o[child];
			} else {
				return false;
			}
		}
		return true;
	},
	
	/**
	 * Check if instance is set as a base
	 * @uses base
	 * @return bool TRUE if object is set as a base
	 */
	is_base: function() {
		return !!this.base;
	},
	
	/**
	 * Get parent instance
	 * @uses `Base._parent` property
	 * @return obj Parent instance
	 */
	get_parent: function() {
		var p = this._parent;
		// Validate
		if ( !p ) {
			this._parent = {};
		}
		return this._parent;
	}
};

/**
 * Utility methods
 */
var Utilities =  {
	/* Properties  */
	
	_base: null,
	_parent: null,
	
	/* Methods */
	
	/* Connections */
	
	/**
	 * Get base ancestor
	 * @return obj Base ancestor
	 */
	get_base: function() {
		if ( !this._base ) {
			var p = this.get_parent();
			var p_prev = null;
			var methods = ['is_base', 'get_parent'];
			// Find base ancestor
			// Either oldest ancestor or object explicitly set as a base
			while ( ( p_prev !== p ) && this.is_method(p, methods) && !p.is_base() ) {
				// Save previous parent
				p_prev = p;
				// Get new parent
				p = p.get_parent();
			}
			// Set base
			this._base = p;
		}
		return this._base;
	},
	
	/**
	 * Get parent object or parent property value
	 * @param string prop (optional) Property to retrieve
	 * @return obj Parent object or property value
	 */
	get_parent: function(prop) {
		var ret = this._parent;
		// Validate
		if ( !ret ) {
			// Set default parent value
			ret = this._parent = {};
		}
		// Get parent property
		if ( this.is_string(prop) ) {
			ret = ( this.in_obj(ret, prop) ) ? ret[prop] : null;
		}
		return ret;
	},
	
	/* Prefix */
	
	/**
	 * Retrieve valid separator
	 * If supplied argument is not a valid separator, use default separator
	 * @param string (optional) sep Separator text
	 * @return string Separator text
	 */
	get_sep: function(sep) {
		var sep_default = '_';
		return ( this.is_string(sep, false) ) ? sep : sep_default;
	},
	
	/**
	 * Retrieve prefix
	 * @return string Prefix
	 */
	get_prefix: function() {
		var p = this.get_parent('prefix');
		return ( this.is_string(p, false) ) ? p : '';
	},
	
	/**
	 * Check if string is prefixed
	 */
	has_prefix: function(val, sep) {
		return ( this.is_string(val) && 0 === val.indexOf(this.get_prefix() + this.get_sep(sep)) );
	},
	
	/**
	 * Add Prefix to a string
	 * @param string val Value to add prefix to
	 * @param string sep (optional) Separator (Default: `_`)
	 * @param bool (optional) once If text should only be prefixed once (Default: TRUE)
	 */
	add_prefix: function(val, sep, once) {
		// Validate
		if ( !this.is_string(val) ) {
			// Return prefix if value to add prefix to is empty
			return this.get_prefix();
		}
		sep = this.get_sep(sep);
		if ( !this.is_bool(once) ) {
			once = true;
		}
		
		return ( once && this.has_prefix(val, sep) ) ? val : [this.get_prefix(), val].join(sep);
	},
	
	/**
	 * Remove Prefix from a string
	 * @param string val Value to add prefix to
	 * @param string sep (optional) Separator (Default: `_`)
	 * @param bool (optional) once If text should only be prefixed once (Default: true)
	 * @return string Original value with prefix removed
	 */
	remove_prefix: function(val, sep, once) {
		// Validate parameters
		if ( !this.is_string(val, true) ) {
			return '';
		}
		// Default values
		sep = this.get_sep(sep);
		if ( !this.is_bool(once) ) {
			once = true;
		}
		// Check if string is prefixed
		if ( this.has_prefix(val, sep) ) {
			// Remove prefix
			var prfx = this.get_prefix() + sep;
			do {
				val = val.substr(prfx.length);
			} while ( !once && this.has_prefix(val, sep) );
		}
		return val;
	},
	
	/* Attributes */
	
	/*
	 * Get attribute name
	 * @param string attr_base Attribute's base name
	 * @return string Fully-formed attribute name
	 */
	get_attribute: function(attr_base) {
		// Setup
		var sep = '-';
		var top = 'data';
		// Validate
		var attr = [top, this.get_prefix()].join(sep);
		// Process
		if ( this.is_string(attr_base) && 0 !== attr_base.indexOf(attr + sep) ) {
			attr = [attr, attr_base].join(sep);
		}
		return attr;
	},
	
	/* Request */
	
	/**
	 * Retrieve valid context
	 * @return array Context
	 */
	get_context: function() {
		// Validate
		var b = this.get_base();
		if ( !$.isArray(b.context) ) {
			b.context = [];
		}
		// Return context
		return b.context;
	},
			
	/**
	 * Check if a context exists in current request
	 * If multiple contexts are supplied, result will be TRUE if at least ONE context exists
	 * 
	 * @param string|array ctx Context to check for
	 * @return bool TRUE if context exists, FALSE otherwise
	 */
	is_context: function(ctx) {
		// Validate context
		if ( this.is_string(ctx) ) {
			ctx = [ctx];
		}
		return ( this.is_array(ctx) && this.arr_intersect(this.get_context(), ctx).length > 0 );
	},
	
	/* Helpers */
	
	/**
	 * Check if value is set/defined
	 * @param mixed val Value to check
	 * @return bool TRUE if value is defined
	 */
	is_set: function(val) {
		return ( typeof val !== 'undefined' );
	},
	
	/**
	 * Validate data type
	 * @param mixed val Value to validate
	 * @param mixed type Data type to compare with (function gets for instance, string checks data type)
	 * @param bool nonempty (optional) Check for empty value? (Default: TRUE)
	 * @return bool TRUE if Value matches specified data type
	 */
	is_type: function(val, type, nonempty) {
		var ret = false;
		if ( this.is_set(val) && null !== val && this.is_set(type) ) {
			switch ( $.type(type) ) {
				case 'function':
					ret = ( val instanceof type ) ? true : false;
					break;
				case 'string':
					ret = ( $.type(val) === type ) ? true : false;
					break;
				default:
					ret = false;
					break;
			}
		}
		
		// Validate empty values
		if ( ret && ( !this.is_set(nonempty) || !!nonempty ) ) {
			ret = !this.is_empty(val);
		}
		return ret;
	},
	
	/**
	 * Check if value is a string
	 * @uses is_type()
	 * @param mixed value Value to check
	 * @param bool nonempty (optional) Check for empty value? (Default: TRUE)
	 * @return bool TRUE if value is a valid string
	 */
	is_string: function(value, nonempty) {
		return this.is_type(value, 'string', nonempty);
	},
	
	/**
	 * Check if value is an array
	 * @uses is_type()
	 * @param mixed value Value to check
	 * @param bool nonempty (optional) Check for empty value? (Default: TRUE)
	 * @return bool TRUE if value is a valid array
	 */
	is_array: function(value, nonempty) {
		return ( this.is_type(value, 'array', nonempty) );
	},
	
	/**
	 * Check if value is a boolean
	 * @uses is_type()
	 * @param mixed value Value to check
	 * @return bool TRUE if value is a valid boolean
	 */
	is_bool: function(value) {
		return this.is_type(value, 'boolean', false);
	},
	
	/**
	 * Check if value is an object
	 * @uses is_type()
	 * @param mixed value Value to check
	 * @param bool nonempty (optional) Check for empty value? (Default: TRUE)
	 * @return bool TRUE if value is a valid object
	 */
	is_obj: function(value, nonempty) {
		return this.is_type(value, 'object', nonempty);
	},
	
	/**
	 * Check if value is a function
	 * @uses is_type()
	 * @param mixed value Value to check
	 * @return bool TRUE if value is a valid function
	 */
	is_func: function(value) {
		return this.is_type(value, 'function', false);
	},
	
	/**
	 * Checks if an object has a method
	 * @param obj obj Object to check
	 * @param string|array key Name(s) of methods to check for
	 * @return bool TRUE if method(s) exist, FALSE otherwise
	 */
	is_method: function(obj, key) {
		var ret = false;
		if ( this.is_string(key) ) {
			key = [key];
		}
		if ( this.in_obj(obj, key) ) {
			ret = true;
			var x = 0;
			while ( ret && x < key.length ) {
				ret = this.is_func(obj[key[x]]);
				x++;
			}
		}
		return ret;
	},
	
	/**
	 * Check if object is instance of a class
	 * @param obj obj Instance object
	 * @param obj parent Class to compare with
	 * @return bool TRUE if object is instance of class
	 */
	is_instance: function(obj, parent) {
		if ( !this.is_func(parent) ) {
			return false;
		}
		return ( this.is_obj(obj) && ( obj instanceof parent ) );
	},
	
	/**
	 * Check if object is class
	 * Optionally check if class is sub-class of another class
	 * @param func cls Class to check
	 * @param func parent (optional) parent class
	 * @return bool TRUE if object is valid class (and sub-class if parent is specified)
	 */
	is_class: function(cls, parent) {
		// Validate class
		var ret = ( this.is_func(cls) && ( 'prototype' in cls ) );
		// Check parent class
		if ( ret && this.is_set(parent) ) {
			ret = this.is_instance(cls.prototype, parent);
		}
		return ret;
	},
	
	/**
	 * Check if value is a number
	 * @uses is_type()
	 * @param mixed value Value to check
	 * @param bool nonempty (optional) Check for empty value? (Default: TRUE)
	 * @return bool TRUE if value is a valid number
	 */
	is_num: function(value, nonempty) {
		var f = {
			'nan': ( Number.isNaN ) ? Number.isNaN : isNaN,
			'finite': ( Number.isFinite ) ? Number.isFinite : isFinite
		};
		return ( this.is_type(value, 'number', nonempty) && !f.nan(value) && f.finite(value) );
	},
	
	/**
	 * Check if value is a integer
	 * @uses is_type()
	 * @param mixed value Value to check
	 * @param bool nonempty (optional) Check for empty value? (Default: TRUE)
	 * @return bool TRUE if value is a valid integer
	 */
	is_int: function(value, nonempty) {
		return ( this.is_num(value, nonempty) && Math.floor(value) === value );
	},
	
	/**
	 * Check if value is scalar (string, number, boolean)
	 * @uses is_type()
	 * @param mixed value Value to check
	 * @param bool nonempty (optional) Check for empty value? (Default: TRUE)
	 * @return bool TRUE if value is scalar
	 */
	is_scalar: function(value, nonempty) {
		return ( this.is_num(value, nonempty) || this.is_string(value, nonempty) || this.is_bool(value) );
	},
	
	/**
	 * Checks if value is empty
	 * @param mixed value Value to check
	 * @param string type (optional) Data type
	 * @return bool TRUE if value is empty
	 */
	is_empty: function(value, type) {
		var ret = false;
		// Check Undefined
		if ( !this.is_set(value) ) {
			ret = true;
		} else {
			// Check standard values
			var empties = [null, "", false, 0];
			var x = 0;
			while ( !ret && x < empties.length ) {
				ret = ( empties[x] === value );
				x++;
			}
		}
		
		// Advanced check
		if ( !ret ) {
			// Validate type
			if ( !this.is_set(type) ) {
				type = $.type(value);
			}
			// Type-based check
			if ( this.is_type(value, type, false) ) {
				switch ( type ) {
					case 'string':
					case 'array':
						ret = ( value.length === 0 );
						break;
					case 'number':
						ret = ( value == 0 ); // jshint ignore:line
						break;
					case 'object':
						if ( !$.isPlainObject(value) ) {
							// Custom object. Unable to evaluate emptiness further
							ret = false;
						} else {
							// Evaluate plain object
							if ( Object.getOwnPropertyNames ) {
								// Modern browser check
								ret = ( Object.getOwnPropertyNames(value).length === 0 ); 
							} else if ( value.hasOwnProperty ) {
								// Legacy browser check
								ret = true;
								for ( var key in value ) {
									if ( value.hasOwnProperty(key) ) {
										ret = false;
										break;
									}
								}
							}
						}
						break;
				}
			} else {
				ret = true;
			}
		}
		return ret;
	},
	
	/**
	 * Check if object is a jQuery.Promise instance
	 * Will also match (but not guarantee) jQuery.Deferred instances
	 * @uses is_method()
	 * @param obj obj Object to check 
	 * @return bool TRUE if object is Promise/Deferred
	 */
	is_promise: function(obj) {
		return ( this.is_method(obj, ['then', 'done', 'always', 'fail', 'pipe']) );
	},
	
	/**
	 * Return formatted string
	 * @param string fmt Format template
	 * @param string val Replacement value (Multiple parameters may be set)
	 * @return string Formatted string
	 */
	format: function(fmt, val) {
		// Validate format
		if ( !this.is_string(fmt) ) {
			return '';
		}
		var params = [];
		var ph = '%s';
		/**
		 * Clean string (remove placeholders)
		 */
		var strip = function(txt) {
			return ( txt.indexOf(ph) !== -1 ) ? txt.replace(ph, '') : txt;
		};
		// Stop processing if no replacement values specified or format string contains no placeholders
		if ( arguments.length < 2 || fmt.indexOf(ph) === -1 ) {
			return strip(fmt);
		}
		// Get replacement values
		params = Array.prototype.slice.call(arguments, 1);
		val = null;
		// Clean parameters
		for ( var x = 0; x < params.length; x++ ) {
			if ( !this.is_scalar(params[x], false) ) {
				params[x] = '';
			}
		}
		
		// Replace all placeholders at once if single parameter set
		if ( params.length === 1 ) {
			fmt = fmt.replace(ph, params[0].toString());
		} else {
			var idx = 0; // Current replacement index
			var len = params.length; // Number of replacements
			var rlen = ph.length; // Placeholder length
			var pos = 0; // Current placeholder position (in format template)
			while ( ( pos = fmt.indexOf(ph) ) && pos !== -1 && idx < len ) {
				// Replace current placeholder with respective parameter
				fmt = fmt.substr(0, pos) + params[idx].toString() + fmt.substr(pos + rlen);
				idx++;
			}
			// Remove any remaining placeholders
			fmt = strip(fmt);
		}
		return fmt;
	},
	
	/**
	 * Checks if key(s) exist in an object
	 * @param object obj Object to check
	 * @param string|array key Key(s) to check for in object
	 * @param bool all (optional) All keys must exist in object? (Default: TRUE) 
	 * @return bool TRUE if key(s) exist in object
	 */
	in_obj: function(obj, key, all) {
		// Validate
		if ( !this.is_bool(all) ) {
			all = true;
		}
		if ( this.is_string(key) ) {
			key = [key];
		}
		// Check for keys
		var ret = false;
		if ( this.is_obj(obj) && this.is_array(key) ) {
			var val;
			for ( var x = 0; x < key.length; x++ ) {
				val = key[x];
				ret = ( this.is_string(val) && ( val in obj ) ) ? true : false;
				// Stop processing if conditions have been met
				if ( ( !all && ret ) || ( all && !ret ) ) {
					break;
				}
			}
		}
		return ret;
	},
	
	/**
	 * Retrieve an object's keys
	 * @param obj Object to parse
	 * @return array List of object's keys
	 */
	obj_keys: function(obj) {
		var keys = [];
		// Validation
		if ( !this.is_obj(obj) ) {
			return keys;
		}
		if ( Object.keys ) {
			keys = Object.keys(obj);
		} else {
			var prop;
			for ( prop in obj ) {
				if ( obj.hasOwnProperty(prop) ) {
					keys.push(prop);
				}
			}
		}
		return keys;
	},
	
	/**
	 * Find common elements of 2 or more arrays
	 * @param array arr1 First array
	 * @param array arr2 Second array (additional arrays can be passed as well)
	 * @return array Elements common to all
	 */
	arr_intersect: function(arr1, arr2) {
		var ret = [];
		// Get arrays
		var params = Array.prototype.slice.call(arguments);
		// Clean arrays
		var arrs = [];
		var x;
		for ( x = 0; x < params.length; x++ ) {
			if ( this.is_array(params[x], false) ) {
				arrs.push(params[x]);
			}
		}
		// Stop processing if no valid arrays to compare
		if ( arrs.length < 2 ) {
			return ret;
		}
		params = arr1 = arr2 = null;
		// Find common elements in arrays
		var base = arrs.shift();
		var add;
		var sub;
		for ( x = 0; x < base.length; x++ ) {
			add = true;
			// Check other arrays for element match
			for ( sub = 0; sub < arrs.length; sub++ ) {
				if ( arrs[sub].indexOf(base[x]) === -1 ) {
					add = false;
					break;
				}
			}
			if ( add ) {
				ret.push(base[x]);
			}
		}
		// Return intersection results
		return ret;
	},
	
	/**
	 * Generates a GUID string.
	 * @returns string The generated GUID.
	 * @example af8a8416-6e18-a307-bd9c-f2c947bbb3aa
	 * @author Slavik Meltser (slavik@meltser.info).
	 * @link http://slavik.meltser.info/?p=142
	 */
	guid: function() {
		function _p8(s) {
			var p = (Math.random().toString(16)+"000000000").substr(2,8);
			return s ? "-" + p.substr(0,4) + "-" + p.substr(4,4) : p ;
		}
		return _p8() + _p8(true) + _p8(true) + _p8();
	},
	
	/**
	 * Parse URI
	 * @param string uri URI to parse
	 * @return obj URI components (DOM anchor element)
	 */
	parse_uri: function(uri) {
		return $('<a href="' + uri + '"/>').get(0);
	},
	/**
	 * Parse URI query string
	 * @param string uri URI with query string to parse
	 * @return obj Query variables and values (empty if no query string)
	 */
	parse_query: function(uri) {
		var delim = {
			'vars': '&',
			'val': '='
		};
		var query = {
			'raw': [],
			'parsed': {},
			'string': ''
		};
		uri = this.parse_uri(uri);
		if ( 0 === uri.search.indexOf('?') ) {
			// Extract query string
			query.raw = uri.search.substr(1).split(delim.vars);
			var i, temp, key, val;
			// Build query object
			for ( i = 0; i < query.raw.length; i++ ) {
				// Split var and value
				temp = query.raw[i].split(delim.val);
				key = temp.shift();
				val = ( temp.length > 0 ) ? temp.join(delim.val) : null;
				query.parsed[key] = val;
			}
		}
		return query.parsed;
	},
	/**
	 * Build query string from object
	 * @param obj query Query data
	 * @return string Query data formatted as HTTP query string
	 */
	build_query: function(query) {
		var q = [];
		var delim = {
			'vars': '&',
			'val': '='
		};
		var val;
		for ( var key in query ) {
			val = ( null !== query[key] ) ? delim.val + query[key] : '';
			q.push(key + val);
		}
		return q.join(delim.vars);
	}
};

// Attach Utilities
Base.attach('util', Utilities, true);

/**
 * SLB Base Class
 */
var SLB_Base = Class.extend(Base);

/**
 * Core
 */
var Core = {
	/* Properties */
	
	base: true,
	context: [],
	
	/**
	 * New object initializer
	 * @var obj
	 */
	Class: SLB_Base,
	
	/* Methods */
	
	/**
	 * Init
	 * Set variables, DOM, etc.
	 */
	_init: function() {
		this._super();
		$('html').addClass(this.util.get_prefix());
	}
};
var SLB_Core = SLB_Base.extend(Core);
window.SLB = new SLB_Core();
})(jQuery);}