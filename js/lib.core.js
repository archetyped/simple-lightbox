/**
 * Core
 * @package SLB
 * @author Archetyped
 */


(function($){

/* Prototypes */

//Object

if (!Object.keys) {
  Object.keys = (function () {
    var hasOwnProperty = Object.prototype.hasOwnProperty,
        hasDontEnumBug = !({toString: null}).propertyIsEnumerable('toString'),
        dontEnums = [
          'toString',
          'toLocaleString',
          'valueOf',
          'hasOwnProperty',
          'isPrototypeOf',
          'propertyIsEnumerable',
          'constructor'
        ],
        dontEnumsLength = dontEnums.length

    return function (obj) {
      if (typeof obj !== 'object' && typeof obj !== 'function' || obj === null) throw new TypeError('Object.keys called on non-object')

      var result = []

      for (var prop in obj) {
        if (hasOwnProperty.call(obj, prop)) result.push(prop)
      }

      if (hasDontEnumBug) {
        for (var i=0; i < dontEnumsLength; i++) {
          if (hasOwnProperty.call(obj, dontEnums[i])) result.push(dontEnums[i])
        }
      }
      return result
    }
  })()
};

//Array

if ( !Array.compare ) {
	/**
	 * Compares another array with this array
	 * @param array arr Array to compare this array with
	 * @return bool Whether arrays are equal or not
	 */
	Array.prototype.compare = function(arr) {
		if (typeof arr == 'object' && this.length == arr.length) {
			for (var x = 0; x < this.length; x++) {
				//Nested array check
				if (this[x].compare && !this.compare(arr[x])) {
					return false;
				}
				if (this[x] !== arr[x])
					return false;
			}
			return true;
		}
		return false;
	};
}

if ( !Array.intersect ) {
	/**
	 * Find common elements of 2 arrays
	 * @param array arr1 First array to compare
	 * @param array arr2 Second array to compare
	 * @return array Elements common to both arrays
	 */
	Array.prototype.intersect = function(arr) {
		var ret = [];
		if ( !$.isArray(arr) || !arr.length || !this.length )
			return ret;
		//Compare elements in arrays
		var a1;
		var a2;
		var val;
		if ( this.length < arr.length ) {
			a1 = this;
			a2 = arr;
		} else {
			a1 = arr;
			a2 = this;
		}

		for ( var x = 0; x < a1.length; x++ ) {
			//Add mutual elements into intersection array
			val = a1[x];
			if ( a2.indexOf(val) != -1 && ret.indexOf(val) == -1 )
				ret.push(val);
		}
		
		//Return intersection results
		return ret;
	};
}

//String

if ( !String.trim ) {
	/**
	 * Trim whitespace from head/tail of string
	 * @return string Trimmed string
	 */
	String.prototype.trim = function() {
		return this.replace(/^\s+|\s+$/g,"");
	};
}
if ( !String.ltrim ) {
	/**
	 * Trim whitespace from head of string
	 * @return string Trimmed string
	 */
	String.prototype.ltrim = function() {
		return this.replace(/^\s+/,"");
	};
}
if ( !String.rtrim ) {
	/**
	 * Trim whitespace from tail of string
	 * @return string Trimmed string
	 */
	String.prototype.rtrim = function() {
		return this.replace(/\s+$/,"");
	};
}

if ( !String.sprintf ) {
	/**
	 * Return formatted string
	 */
	String.prototype.sprintf = function() {
		var params = [],
			ph = '%s';
		if (arguments.length < 1) {
			return this;
		}
		format = this.toString();
		if ( arguments.length >= 1 ) {
			params = Array.prototype.slice.call(arguments);
		}
		//Replace placeholders in string with parameters
		if ( format.indexOf(ph) != -1 ) {
			//Replace all placeholders at once if single parameter set
			if ( params.length == 1 ) {
				format = format.replace(ph, params[0].toString());
			} else {
				var idx = 0,
					pos = 0;
				while ( ( pos = format.indexOf(ph) ) && idx < params.length ) {
					format = format.substr(0, pos) + params[idx].toString() + format.substr(pos + ph.length);
					idx++;
				}
				//Remove any remaining placeholders
				format = format.replace(ph, '');
			}
		}
		return format;
	}
}

if ( !String.wrap ) {
	/**
	 * Wrap string with another string
	 */
	String.prototype.wrap = function(val) {
		var t = typeof val;
		if ( ['undefined','object','array'].indexOf(t) != -1 ) {
			return this;
		}
		val = val.toString();
		if ( !val.length ) {
			return this;
		}
		return [val, this, val].join('');
	}
}

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
		
		string: 'string',
		bool: 'boolean',
		array: 'array',
		obj: 'object',
		func: 'function',
		num: 'number',
		
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
			if ( typeof sep == 'undefined' || sep == null )
				sep = '';
			
			return ( $.type(sep) == 'string' ) ? sep : '_';
		},
		
		/**
		 * Retrieve prefix
		 * @param string (optional) sep Separator text
		 * @return string Prefix (with separator if specified)
		 */
		get_prefix: function(sep) {
			return ( this.get_parent().prefix && this.get_parent().prefix.length ) ? this.get_parent().prefix + this.get_sep(sep) : '';
		},
		
		/**
		 * Check if string is prefixed
		 */
		has_prefix: function(val, sep) {
			return ( $.type(val) == 'string' && val.length && val.indexOf(this.get_prefix(sep)) === 0 );
		},
		
		/**
		 * Add Prefix to a string
		 * @param string val Value to add prefix to
		 * @param string sep (optional) Separator (Default: `_`)
		 * @param bool (optional) once If text should only be prefixed once (Default: true)
		 */
		add_prefix: function(val, sep, once) {
			if ( typeof sep == 'undefined' )
				sep = '_';
			once = ( typeof once == 'undefined' ) ? true : !!once;
			if ( once && this.has_prefix(val, sep) )
				return val;	
			return this.get_prefix(sep) + val;
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
			if ( !this.is_string(sep, true) ) {
				sep = '_';
			}
			if ( !this.is_bool(once) ) {
				once = true;
			}
			//Check if string is prefixed
			if ( this.has_prefix(val, sep) ) {
				//Remove prefix
				var re = new RegExp('^(%s)+(.*)$'.sprintf(this.get_prefix(sep)), 'g');
				val = val.replace(re, '$2');
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
			if ( $.isArray(ctx) && this.get_context().intersect(ctx).length )
				ret = true;
			return ret;
		},
		
		/* Helpers */

		is_set: function(value) {
			return ( typeof value != 'undefined' ) ? true : false;
		},
		
		is_type: function(value, type, nonempty) {
			var ret = false;
			if ( this.is_set(value) && null != value && this.is_set(type) ) {
				switch ( typeof type ) {
					case this.func:
						ret = ( value instanceof type ) ? true : false;
						break;
					case this.string:
						ret = ( typeof value == type ) ? true : false;
						break;
					default:
						ret = false;
						break;
				}
			}
			
			//Validate empty values
			if ( ret && ( typeof nonempty != this.bool || nonempty ) ) {
				ret = !this.is_empty(value);
			}
			return ret;
		},
		
		is_string: function(value, nonempty) {
			return this.is_type(value, this.string, nonempty);
		},
		
		is_array: function(value, nonempty) {
			return ( this.is_type(value, this.array, nonempty) || ( this.is_obj(value, nonempty) && value.length ) );
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
		
		is_method: function(obj, value) {
			return ( this.is_obj(obj) && this.is_string(value) && ( value in obj ) && this.is_func(obj[value]) ) ? true : false;
		},
		
		is_num: function(value, nonempty) {
			return this.is_type(value, this.num, nonempty);
		},
		
		is_int: function(value, nonempty) {
			return ( this.is_num(value, nonempty) && Math.floor(value) === value );
		},
		
		/**
		 * Checks if value is empty
		 * @param mixed value Value to check
		 * @param string type (optional) Data type
		 * @return bool TRUE if value is empty, FALSE if not empty
		 */
		is_empty: function(value, type) {
			ret = false;
			//Initial check for empty value
			if ( !this.is_set(value) || null === value || false === value ) {
				ret = true;
			} else {
				//Validate type
				if ( !this.is_set(type) ) {
					type = typeof value;
				}
				//Type-based check
				if ( this.is_type(value, type, false) ) {
					switch ( type ) {
						case this.string:
						case this.array:
							if ( value.length == 0 )
								ret = true;
							break;
						case this.object:
							ret = true;
							for ( var p in value ) {
								ret = false;
								break;
							}
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
		 * Validate specified value's data type and return default value if necessary
		 * Data type of default value is used to determine data type
		 * @param mixed val Value to check
		 * @param mixed def Default value
		 * @return mixed Valid value 
		 */
		validate: function(val, def) {
			return ( this.is_type(val, def, true) ) ? val : def;
		},
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
	},
};
var SLB_Core = SLB_Base.extend(Core);

this.SLB = new SLB_Core();

SLB.setup_client();

})(jQuery);