/**
 * Core
 * @package SLB
 * @author Archetyped
 */

(function($) {

/**
 * Extendible class
 * Adapted from John Resig
 * @link http://ejohn.org/blog/simple-javascript-inheritance/ 
 */
var c_init = false;
var Class = function() {};

Class.extend = function(members) {
	var _super = this.prototype;
	
	//Copy instance to prototype
	c_init = true;
	var proto = new this();
	c_init = false;
	
	var val; 
	//Scrub prototype objects (Decouple from super class)
	for ( var name in proto ) {
		if ( $.isPlainObject(proto[name]) ) {
			val = $.extend({}, proto[name]);
			proto[name] = val;
		}
	}
	
	//Copy members
	for ( var name in members ) {
		//Evaluate function members (if overwriting super class method)
		if ( 'function' == typeof members[name] && 'function' == typeof _super[name] ) {
			proto[name] = (function(name, fn) {
				return function() {
					//Cache super variable
					var tmp = this._super;
					//Set variable to super class method
					this._super = _super[name];
					//Call method
					var ret = fn.apply(this, arguments);
					//Restore super variable
					this._super = tmp;
					//Return value
					return ret;
				}
			})(name, members[name]);
		} else {
			val = members[name];
			if ( $.isPlainObject(members[name]) ) {
				val = $.extend({}, members[name]);
			}
			proto[name] = val;
		}
	}
	
	//Constructor
	function Class() {
		if ( !c_init ) {
			//Private init
			if ( this._init ) {
				this._init.apply(this, arguments);
			}
			//Main Constructor
			if ( this._c ) {
				this._c.apply(this, arguments);
			}
		}
	}
	
	
	//Populate new prototype
	Class.prototype = proto;
	
	//Set constructor
	Class.prototype.constructor = Class;
	
	Class.extend = arguments.callee;
	
	//Return function
	return Class;
};

/* Base */
var Base = {
	/* Properties */
	
	base: false,
	_parent: null,
	prefix: 'slb',
	
	/* Methods */
	
	/**
	 * Constructor
	 */
	_init: function() {
		this._set_parent();
	},
	
	_set_parent: function(p) {
		if ( typeof p != 'undefined' )
			this._parent = p;
		this.util._parent = this;
	},
	
	/**
	 * Attach member to object
	 * @param string name Member name
	 * @param mixed Member data
	 * > obj: Member inherits from base object
	 * > other: Simple data object
	 */
	attach: function(member, data, simple) {
		simple = ( typeof simple == undefined ) ? false : !!simple;
		if ( $.type(member) == 'string' && $.isPlainObject(data) ) {
			//Add initial member
			var obj = {};
			if ( simple ) {
				//Simple object
				obj[member] = $.extend({}, data);
				$.extend(this, obj);
			} else {
				//Add new instance object
				data['_parent'] = this;
				var c = this.Class.extend(data);
				this[member] = new c();
			}
		}
	},
	
	/**
	 * Get parent object
	 * @return obj Parent object
	 */
	get_parent: function() {
		return this._parent;
	},
	
	/**
	 * Utility methods
	 */
	util: {
		/* Properties  */
		
		_base: null,
		_parent: null,
		
		/* Constants */
		
		string:	'string',
		bool:	'boolean',
		array:	'array',
		obj:	'object',
		func:	'function',
		num:	'number',
		
		/* Methods */
		
		get_base: function() {
			if ( !this._base ) {
				var p = this.get_parent();
				var p_last = null;
				//Iterate through parents
				while ( !p.base && p_last != p && p._parent ) {
					p_last = p;
					p = p._parent;
				}
				//Set base
				this._base = p;
			}
			return this._base;
		},
		
		get_parent: function() {
			return this._parent;
		},
		
		/**
		 * Retrieve valid separator
		 * If supplied argument is not a valid separator, use default separator
		 * @param string (optional) sep Separator text
		 * @return string Separator text
		 */
		get_sep: function(sep) {
			return ( this.is_string(sep, false) ) ? sep : '_';
		},
		
		/**
		 * Retrieve prefix
		 * @return string Prefix
		 */
		get_prefix: function() {
			return ( this.is_string(this.get_parent().prefix) ) ? this.get_parent().prefix : '';
		},
		
		/**
		 * Check if string is prefixed
		 */
		has_prefix: function(val, sep) {
			return ( this.is_string(val) && val.indexOf(this.get_prefix() + this.get_sep(sep)) === 0 );
		},
		
		/**
		 * Add Prefix to a string
		 * @param string val Value to add prefix to
		 * @param string sep (optional) Separator (Default: `_`)
		 * @param bool (optional) once If text should only be prefixed once (Default: TRUE)
		 */
		add_prefix: function(val, sep, once) {
			//Validate
			if ( !this.is_string(val) ) {
				//Return prefix if value to add prefix to is empty
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
		 */
		remove_prefix: function(val, sep, once) {
			//Validate parameters
			if ( !this.is_string(val, true) ) {
				return val;
			}
			//Default values
			sep = this.get_sep(sep);
			if ( !this.is_bool(once) ) {
				once = true;
			}
			//Check if string is prefixed
			if ( this.has_prefix(val, sep) ) {
				//Remove prefix
				var prfx = this.get_prefix() + sep;
				do {
					val = val.substr(prfx.length);
				} while ( !once && this.has_prefix(val, sep) );
			}
			return val;
		},
		
		/*
		 * Get attribute name
		 * @param string val Attribute's base name
		 */
		get_attribute: function(val) {
			//Setup
			var sep = '-';
			var top = 'data';
			//Validate
			var pre = [top, this.get_prefix()].join(sep);
			if ( !this.is_string(val, false) ) {
				return pre; 
			}
			//Process
			if ( val.indexOf(pre + sep) == -1 ) {
				val = [pre, val].join(sep);
			}
			return val;
		},
		
		/* Request */
		
		/**
		 * Retrieve valid context
		 * @return array Context
		 */
		get_context: function() {
			//Valid context
			var b = this.get_base();
			if ( !$.isArray(b.context) )
				b.context = [];
			//Return context
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
			var ret = false;
			//Validate context
			if ( typeof ctx == 'string' )
				ctx = [ctx];
			if ( $.isArray(ctx) && this.arr_intersect(this.get_context(), ctx).length ) {
				ret = true;
			}
			return ret;
		},
		
		/* Helpers */

		is_set: function(value) {
			return ( $.type(value) != 'undefined' ) ? true : false;
		},
		
		is_type: function(value, type, nonempty) {
			var ret = false;
			if ( this.is_set(value) && null != value && this.is_set(type) ) {
				switch ( $.type(type) ) {
					case this.func:
						ret = ( value instanceof type ) ? true : false;
						break;
					case this.string:
						ret = ( $.type(value) == type ) ? true : false;
						break;
					default:
						ret = false;
						break;
				}
			}
			
			//Validate empty values
			if ( ret && ( $.type(nonempty) != this.bool || nonempty ) ) {
				ret = !this.is_empty(value);
			}
			return ret;
		},
		
		is_string: function(value, nonempty) {
			return this.is_type(value, this.string, nonempty);
		},
		
		is_array: function(value, nonempty) {
			return ( this.is_type(value, this.array, nonempty) );
		},
		
		is_bool: function(value) {
			return this.is_type(value, this.bool, false);
		},
		
		is_obj: function(value, nonempty) {
			return this.is_type(value, this.obj, nonempty);
		},
		
		is_func: function(value) {
			return this.is_type(value, this.func, false);
		},
		
		/**
		 * Checks if an object has a method
		 * @param obj Object to check
		 * @param string|array Names of methods to check for
		 * @return bool TRUE if method(s) exist, FALSE otherwise
		 */
		is_method: function(obj, value) {
			var ret = false;
			if ( this.is_string(value) ) {
				value = [value];
			}
			if ( this.in_obj(obj, value) ) {
				var t = this;
				$.each(value, function(idx, val) {
					ret = ( t.is_func(obj[val]) ) ? true : false;
					return ret;
				});
			}
			return ret;
		},
		
		is_num: function(value, nonempty) {
			return ( this.is_type(value, this.num, nonempty) && !isNaN(value) );
		},
		
		is_int: function(value, nonempty) {
			return ( this.is_num(value, nonempty) && Math.floor(value) === value );
		},
		
		is_scalar: function(value, nonempty) {
			return ( this.is_num(value, nonempty) || this.is_string(value, nonempty) || this.is_bool(value, nonempty) );
		},
		
		/**
		 * Checks if value is empty
		 * @param mixed value Value to check
		 * @param string type (optional) Data type
		 * @return bool TRUE if value is empty, FALSE if not empty
		 */
		is_empty: function(value, type) {
			var ret = false;
			//Initial check for empty value
			if ( !this.is_set(value) || null === value || false === value ) {
				ret = true;
			} else {
				//Validate type
				if ( !this.is_set(type) ) {
					type = $.type(value);
				}
				//Type-based check
				if ( this.is_type(value, type, false) ) {
					switch ( type ) {
						case this.string:
						case this.array:
							if ( value.length == 0 ) {
								ret = true;
							}
							break;
						case this.obj:
							//Only evaluate literal objects
							ret = ( $.isPlainObject(value) && !$.map(value, function(v, key) { return key; }).length );
							break;
						case this.num:
							ret = ( value === 0 );
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
		 * @return bool TRUE if object is Promise/Deferred, FALSE otherwise
		 */
		is_promise: function(obj) {
			return ( this.is_obj(obj) && this.is_method(obj, ['then', 'done', 'always', 'fail', 'pipe']) )
		},
		
		/**
		 * Check if object is a jQuery.Deferred instance
		 */
		is_deferred: function(obj) {
			return ( this.is_promise(obj) && this.is_method(obj, ['resolve', 'reject', 'promise']));
		},
		
		/**
		 * Validate specified value's data type and return default value if necessary
		 * Data type of default value is used to determine data type
		 * @param mixed val Value to check
		 * @param mixed def Default value
		 * @return mixed Valid value 
		 */
		validate: function(val, def) {
			return ( this.is_type(val, def, true) ) ? val : def;
		},
		
		/**
		 * Return formatted string
		 */
		format: function(fmt, val) {
			if ( !this.is_string(fmt) ) {
				return '';
			}
			var params = [],
				ph = '%s';
			//Stop processing if no replacement values specified or format string contains no placeholders
			if ( arguments.length < 2 || fmt.indexOf(ph) == -1 ) {
				return fmt;
			}
			//Get replacement values
			params = Array.prototype.slice.call(arguments, 1);
			
			//Replace placeholders in string with parameters
			
			//Replace all placeholders at once if single parameter set
			if ( params.length == 1 ) {
				fmt = fmt.replace(ph, params[0].toString());
			} else {
				var idx = 0,
					len = params.length,
					pos = 0;
				while ( ( pos = fmt.indexOf(ph) ) && idx < len ) {
					fmt = fmt.substr(0, pos) + params[idx].toString() + fmt.substr(pos + ph.length);
					idx++;
				}
				//Remove any remaining placeholders
				fmt = fmt.replace(ph, '');
			}
			return fmt;
		},
		
		/**
		 * Checks if key(s) exist in an object
		 * @param object obj Object to check
		 * @param string|array key Key(s) to check for in object
		 * @return bool TRUE if key(s) exist in object, FALSE otherwise
		 */
		in_obj: function(obj, key, all) {
			//Validate
			if ( !this.is_bool(all) ) {
				all = true;
			}
			if ( this.is_string(key) ) {
				key = [key];
			}
			var ret = false;
			if ( this.is_obj(obj) && this.is_array(key) ) {
				var val;
				for ( var x = 0; x < key.length; x++ ) {
					val = key[x];
					ret = ( this.is_string(val) && ( val in obj ) ) ? true : false;
					//Stop processing if conditions have been met
					if ( ( !all && ret ) || ( all && !ret ) ) {
						break;
					}
				}
			}
			return ret;
		},
		
		/**
		 * Find common elements of 2 arrays
		 * @param array arr1 First array
		 * @param array arr2 Second array
		 * @return array Elements common to both arrays
		 */
		arr_intersect: function(arr1, arr2) {
			var ret = [];
			if ( arr1 == arr2 ) {
				return arr2;
			}
			if ( !$.isArray(arr2) || !arr2.length || !arr1.length ) {
				return ret;
			}
			//Compare elements in arrays
			var a1;
			var a2;
			var val;
			if ( arr1.length < arr2.length ) {
				a1 = arr1;
				a2 = arr2;
			} else {
				a1 = arr2;
				a2 = arr1;
			}
	
			for ( var x = 0; x < a1.length; x++ ) {
				//Add mutual elements into intersection array
				val = a1[x];
				if ( a2.indexOf(val) != -1 && ret.indexOf(val) == -1 )
					ret.push(val);
			}
			
			//Return intersection results
			return ret;
		}
	}
};
var SLB_Base = Class.extend(Base);

//Init global object
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
	 * Setup client
	 * Set variables, DOM, etc.
	 */
	setup_client: function() {
		/* Quick Hide */
		$('html').addClass(this.util.get_prefix());
	}
};
var SLB_Core = SLB_Base.extend(Core);

this.SLB = new SLB_Core();

SLB.setup_client();

})(jQuery);