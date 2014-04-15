if ( !!window.SLB && SLB.has_child('View.extend_content_handler') ) {(function($) {
SLB.View.extend_content_handler('image', {
	/**
	 * Render images
	 * @param obj item Content Item
	 * @param obj dfr Promise for rendering process
	 * @return obj Promise for rendering process (Resolved when content is loaded)
	 */
	render: function(item, dfr) {
		// Create image object
		var img = new Image();
		// Set load event
		var handler = function() {
			// Save Data
			item.set_data(img);
			// Set attributes
			item.set_attribute('dimensions', {'width': img.width, 'height': img.height});
			// Build output
			var out = $('<img />', {'src': item.get_uri()});
			// Resolve deferred
			dfr.resolve(out);
		};
		
		// Attach event handler
		$(img).on('load', function(e) { handler(e); });
		// Load image
		img.src = item.get_uri();
		// Return promise
		return dfr.promise();
	}
});
})(jQuery);
}