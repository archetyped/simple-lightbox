(function($) {
return {
	/**
	 * Animation handlers
	 */
	'animate': {
		/**
		 * Open event
		 * @param View.Viewer Viewer instance
		 * @param jQuery.Deferred Deferred instance to be resolved when animation is complete
		 * @return jQuery.Promise Resolved when animation is complete
		 */
		'open': function(v, dfr) {
			console.groupCollapsed('Theme.animate.open()');
			var d = v.dom_get(),
				l = v.get_layout().hide(),
				o = v.get_overlay().hide();
			//Show viewer DOM
			d.show(function() {
				var pos = { top: ( $(document).scrollTop() + $(window).height() / 2 ) - ( l.height() / 2 ) };
				o.fadeIn(function() {
					l.css(pos);
					dfr.resolve();				});
			});
			console.groupEnd();
			return dfr.promise();
		},
		/**
		 * Close event
		 * @param View.Viewer Viewer instance
		 * @param jQuery.Deferred Deferred instance to be resolved when animation is complete
		 * @return jQuery.Promise Resolved when animation is complete
		 */
		'close': function(v, dfr) {
			console.groupCollapsed('Theme.animate.close()');
			var l = v.get_layout();
			var pos = l.animate({top: $(document).scrollTop() + ( $(window).height() / 2 ), opacity: 0}).promise();
			var size = l.find('.slb_content').animate({width: 0, height: 0}).promise();
			$.when(pos, size).done(function() {
				v.get_overlay().fadeOut(function() {
					l.find('.slb_content').width('').height('');
					l.css('opacity', '');
					dfr.resolve();
				});
			});
			console.groupEnd();
			return dfr.promise();
		},
		/**
		 * Item loading event
		 * @param View.Viewer Viewer instance
		 * @param jQuery.Deferred Deferred instance to be resolved when animation is complete
		 * @return jQuery.Promise Resolved when animation is complete
		 */
		'load': function(v, dfr) {
			console.groupCollapsed('Theme.animate.load()');
			v.get_layout().find('.slb_loading').show();
			console.groupEnd();
			return v.get_layout().fadeIn().promise();
		},
		/**
		 * Item unloaded event
		 * @param View.Viewer Viewer instance
		 * @param jQuery.Deferred Deferred instance to be resolved when animation is complete
		 * @return jQuery.Promise Resolved when animation is complete
		 */
		'unload': function(v, dfr) {
			console.groupCollapsed('Theme.animate.unload()');
			console.info('Resolved: %o', dfr.isResolved());
			var l = v.get_layout();
			var det = l.find('.slb_details').slideUp();
			var cont = l.find('.slb_content .slb_template_tag').fadeOut();
			$.when(det.promise(), cont.promise()).done(function() {
				dfr.resolve();
			});
			console.groupEnd();
			return dfr.promise();
		},
		/**
		 * Item loading completed event
		 * @param View.Viewer Viewer instance
		 * @param jQuery.Deferred Deferred instance to be resolved when animation is complete
		 * @return jQuery.Promise Resolved when animation is complete
		 */
		'complete': function(v, dfr) {
			console.group('Theme.animate.complete()');
			//Resize viewer to fit item
			var dims = this.get_item_dimensions();
			var l = v.get_layout();
			l.find('.slb_details .slb_template_tag').show();
			var pos = { 'top': $(document).scrollTop() + ( $(window).height() / 2 ) - ( this.get_dimensions().height / 2 ) };
			console.info('Pos (Top): %o \nScrollTop: %o \nWindow Height: %o \nLayout Height: %o', pos.top, $(document).scrollTop(), $(window).height(), this.get_dimensions().height);
			pos.top = pos.top || 0;
			var det = l.find('.slb_details');
			//Resize container
			pos = l.animate(pos).promise();
			dims = l.find('.slb_content').animate(dims).promise();
			$.when(pos, dims).done(function() {
				l.find('.slb_loading').fadeOut('fast', function() {
					//Display content
					l.find('.slb_content .slb_template_tag').fadeIn(function() {
						//Display UI
						det.hide().promise().done(function() {
							det.slideDown(function() {
								dfr.resolve();
							});
						});
					});
				});
			});
			console.groupEnd();
			return dfr.promise();
		},
	},
	/**
	 * Theme-specific margins 
	 */
	'margin': function() {
		var dims = {'width': 0, 'height': 0};
		var d = this.get_viewer().get_layout().find('.slb_details');
		d.find('.slb_template_tag').show();
		dims.height = d.height();
		return dims;
	}
};
})(jQuery);