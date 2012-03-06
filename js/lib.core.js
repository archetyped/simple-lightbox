/**
 * Core
 * @package SLB
 * @author Archetyped
 */


(function($){

/* Prototypes */

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

/* Extendible Class */
var c_init = false;
var Class = function() {};

Class.extend = function(members) {
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
		val = members[name];
		if ( $.isPlainObject(members[name]) ) {
			val = $.extend({}, members[name]);
		}
		proto[name] = val;
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

var SLB_Base = Class.extend({
	/* Properties */
	
	base: false,
	_parent: null,
	prefix: 'slb',
	
	/* Methods */
	
	/**
	 * Constructor
	 */
	_init: function() {
		this.util['_parent'] = this;
	},
	
	set_parent: function(p) {
		this._parent = p;
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
	 * Utility methods
	 */
	util: {
		/* Properties  */
		
		_base: null,
		_parent: null,
		
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
		 * Add Prefix to value
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
		 * Return formatted string
		 */
		sprintf: function() {
			var format = '',
				params = [];
			if (arguments.length < 1)
				return format;
			if (arguments.length == 1) {
				format = arguments[0];
				return format;
			}
			params = arguments.slice(1);
			return format;
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
		
		/**
		 * Checks if value is valid based on type
		 * All undefined values are immediately invalid
		 * @var mixed value Value to check
		 * @var string type (optional) Required data type (string, array, etc. - Default: Anything)
		 * @var bool nonempty (optional) Whether value must not be empty (Default: Yes)
		 * @return bool Whether value is valid (TRUE if valid, FALSE otherwise)
		 */
		is_valid: function(value, type, nonempty) {
			var ret = false;
			var tvalue = typeof value;
			console.groupCollapsed('Validity check');
			console.log('Value: %o \nValue Type: %o \nType: %o \nNonempty: %s', value, tvalue, type, nonempty);
			if ( tvalue != 'undefined' ) {
				ret = true;	
				console.log('Value is set, continuing');
				//Check data type (only if set)
				if ( typeof type == 'string' && tvalue != type ) {
					console.warn('%o is not equal to %o', tvalue, type);
					ret = false;
				}
				
				//Check if data is empty
				nonempty = ( typeof nonempty == 'undefined' ) ? true : !!nonempty; 
				if ( ret && nonempty ) {
					console.log('Checking nonempty');
					switch ( type ) {
						case 'string':
						case 'array':
						case 'object':
							if ( value.length == 0 )
								ret = false;
							break;
					}
				}
			}
			console.log('Return value: %o', ret);
			console.groupEnd();
			return ret;
		},
	}
});

//Init global object
var SLB_Core = SLB_Base.extend({
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
});

this.SLB = new SLB_Core();

SLB.setup_client();

})(jQuery);