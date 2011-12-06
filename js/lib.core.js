/**
 * Core
 * @package SLB
 * @author Archetyped
 */

var SLB = {};

(function($){
/* Classes */

SLB = {
	prefix: 'slb',
	base: this,
	context: 	[],	//Context
	options:	{	//Options
	},
	
	extend: function(member, data) {
		if ( $.type(member) == 'string' && $.isPlainObject(data) ) {
			//Add initial member
			var obj = {};
			obj[member] = $.extend({}, data);
			$.extend(this, obj);
			
			if ( member in this ) {
				//Add additional objects
				var args = ( arguments.length > 2 ) ? [].slice.apply(arguments).slice(2) : [];
				args.unshift(this[member]);
				//Add base properties
				args.push({
					base: SLB,
					parent: this,
					extend: this.extend
				});
				$.extend.apply($, args);
			}
		}
	},
	
	/* Prefix */
	
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
		return ( this.prefix && this.prefix.length ) ? this.prefix + this.get_sep(sep) : '';
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
	}
}

/* Utilities */
SLB.extend('util', {
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
		if ( !$.isArray(this.base.context) )
			this.base.context = [];
		//Return context
		return this.base.context;
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
});

})(jQuery);