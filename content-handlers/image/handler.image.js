(function($) {
return {
	render: function(item) {
		var dfr = $.Deferred();
		//Create image object
		var img = new Image();
		var type = this;
		//Set load event
		var handler = function(e) {
			console.groupCollapsed('Content_Type.image.load (Callback)');
			//Save Data
			item.set_data(img);
			//Set attributes
			var dim = {'width': img.width, 'height': img.height};
			console.info('Setting dimensions', dim);
			item.set_attribute('dimensions', dim);
			//Build output
			var out = $('<img />', {'src': item.get_uri()});
			//Resolve deferred
			dfr.resolve(out);
			console.groupEnd();
		};
		
		//Attach event handler
		$(img).on('load', function(e) { handler(e); });
		//Load image
		img.src = item.get_uri();
		//Return promise
		return dfr.promise();
	}
}
})(jQuery);