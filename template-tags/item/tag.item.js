(function($) {
return {
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
}
})(jQuery);