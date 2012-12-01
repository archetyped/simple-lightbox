(function($) {
SLB.View.update_theme('slb_default', {
	/**
	 * State transition handlers
	 */
	'transition': {
		/**
		 * Open event
		 * @param View.Viewer v Viewer instance
		 * @param jQuery.Deferred dfr Deferred instance to be resolved when animation is complete
		 * @return jQuery.Promise Resolved when transition is complete
		 */
		'open': function(v, dfr) {
			console.groupCollapsed('Theme.transition.open()');
			var d = v.dom_get(),
				l = v.get_layout().hide(),
				o = v.get_overlay().hide();
			//Clean UI
			d.find('.slb_content').css({width: '', height: ''}).find('.slb_template_tag').hide();
			d.find('.slb_details').height(0);
			//Show viewer DOM
			d.show(function() {
				//Center vertically
				var pos = { top: ( $(document).scrollTop() + $(window).height() / 2 ) - ( l.height() / 2 ) };
				o.fadeIn(function() {
					l.css(pos);
					dfr.resolve();
				});
			});
			console.groupEnd();
			return dfr.promise();
		},
		/**
		 * Close event
		 * @param View.Viewer v Viewer instance
		 * @param jQuery.Deferred dfr Deferred instance to be resolved when animation is complete
		 * @return jQuery.Promise Resolved when transition is complete
		 */
		'close': function(v, dfr) {
			console.groupCollapsed('Theme.transition.close()');
			var l = v.get_layout(),
				c = l.find('.slb_content');
			var reset = function() {
				//Reset state
				c.width('').height('');
				l.css('opacity', '');
				dfr.resolve();
			}
			if ( v.animation_enabled() ) {
				var lanim = {opacity: 0},
					canim = {};
				if ( $(window).width() > 480 ) {
					lanim.top = $(document).scrollTop() + ( $(window).height() / 2 );
					canim = {width: 0, height: 0};
				}
				//Shrink & fade out viewer
				var pos = l.animate(lanim).promise();
				var size = ( $.isEmptyObject(canim) ) ? true : c.animate(canim).promise();
				$.when(pos, size).done(function() {
					//Fade out overlay
					v.get_overlay().fadeOut(function() {
						reset();
					});
				});
			} else {
				l.css('opacity', 0);
				v.get_overlay().hide();
				reset();
			}
			console.groupEnd();
			return dfr.promise();
		},
		/**
		 * Item loading event
		 * @param View.Viewer v Viewer instance
		 * @param jQuery.Deferred dfr Deferred instance to be resolved when animation is complete
		 * @return jQuery.Promise Resolved when transition is complete
		 */
		'load': function(v, dfr) {
			console.groupCollapsed('Theme.transition.load()');
			v.get_layout().find('.slb_loading').show();
			console.groupEnd();
			return v.get_layout().fadeIn().promise();
		},
		/**
		 * Item unloaded event
		 * @param View.Viewer v Viewer instance
		 * @param jQuery.Deferred dfr Deferred instance to be resolved when animation is complete
		 * @return jQuery.Promise Resolved when transition is complete
		 */
		'unload': function(v, dfr) {
			console.groupCollapsed('Theme.transition.unload()');
			var l = v.get_layout();
			//Hide details
			var det = l.find('.slb_details').animate({height: 0}, 'slow');
			//Hide content
			var cont = l.find('.slb_content .slb_template_tag').fadeOut();
			$.when(det.promise(), cont.promise()).done(function() {
				dfr.resolve();
			});
			console.groupEnd();
			return dfr.promise();
		},
		/**
		 * Item loading completed event
		 * @param View.Viewer v Viewer instance
		 * @param jQuery.Deferred dfr Deferred instance to be resolved when animation is complete
		 * @return jQuery.Promise Resolved when transition is complete
		 */
		'complete': function(v, dfr) {
			console.group('Theme.transition.complete()');
			var l = v.get_layout();
			var det = l.find('.slb_details');
			var c = l.find('.slb_content');
			//Resize viewer to fit item
			var dims = this.get_item_dimensions();
			//Show detail tags (container still hidden)
			det.find('.slb_template_tag').show();
			var pos = { 'top': $(document).scrollTop() + ( $(window).height() / 2 ) - ( this.get_dimensions().height / 2 ) };
			console.info('Pos (Top): %o \nScrollTop: %o \nWindow Height: %o \nLayout Height: %o', pos.top, $(document).scrollTop(), $(window).height(), this.get_dimensions().height);
			pos.top = pos.top || 0;
			//Resize container
			pos = l.animate(pos).promise();
			dims = c.animate(dims).promise();
			$.when(pos, dims).done(function() {
				//Hide loading indicator
				l.find('.slb_loading').fadeOut('fast', function() {
					//Display content
					l.find('.slb_content .slb_template_tag_item_content').fadeIn(function() {
						//Show UI
						l.find('.slb_content .slb_template_tag').show();
						//Show details
						var data = det.find('.slb_data');
						det.animate({height: data.outerHeight()}, 'slow').promise().done(function() {
							det.height('');
							dfr.resolve();
						});
					});
				});
			});
			console.groupEnd();
			return dfr.promise();
		}
	},
	/**
	 * Theme offsets
	 * Reports additional space required for theme UI
	 */
	'offset': function() {
		var dims = {'width': 0, 'height': 0};
		if ( $(window).width() > 480 ) {
			var d = this.get_viewer().get_layout().find('.slb_details');
			d.find('.slb_template_tag').show();
			dims.height = d.find('.slb_data').outerHeight();
		}
		return dims;
	},
	/**
	 * Theme margins
	 * Reports additional margins used for positioning viewer
	 */
	'margin': function() {
		var m = {'height': 0, 'width': 0};
		if ( $(window).width() > 480 ) {
			m.height = 50;
		}
		return m;
	}
});
})(jQuery);