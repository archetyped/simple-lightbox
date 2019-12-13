if ( !!window.SLB && SLB.has_child('View.extend_template_tag_handler') ) {(function() {
SLB.View.extend_template_tag_handler('item', {
	/**
	 * Render Item tag
	 * @param obj item Content Item
	 * @param obj tag Tag instance
	 * @param obj dfr Promise to be resolved when tag is rendered
	 */
	render: function(item, tag, dfr) {
		// Build method name
		var m = 'get_' + tag.get_prop();
		// Get data
		var ret = ( this.util.is_method(item, m) ) ? item[m]() : item.get_attribute(tag.get_prop(), '');
		// Handle response
		if ( this.util.is_promise(ret) ) {
			ret.done(function(output) {
				dfr.resolve(output);
			});
		} else {
			dfr.resolve(ret);
		}
		return dfr.promise();
	}
});
})();
}