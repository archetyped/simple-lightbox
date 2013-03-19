(function($) {
SLB.View.update_theme('slb_default', {
	/* Helpers */
	'zoom_set': function() {
		//Set viewport properties
		var vp = $('meta[name=viewport]');
		if ( !vp.length ) {
			vp = $('<meta name="viewport" />').appendTo('head');
		}
		var att = 'content';
		this.set_attribute('vp_' + att, vp.attr(att), false);
		vp.attr(att, 'width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;');
	},
	
	'zoom_unset': function() {
		var att = 'content';
		var att_val = this.get_attribute('vp_' + att, '', false);
		var vp = $('meta[name=viewport]');
		vp.attr(att, att_val);
	},
	
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
			var t = this;
			var d = v.dom_get(),
				l = v.get_layout().hide(),
				o = v.get_overlay().hide();
			var pos = {'top' : ''};
			var final = function() {
				t.call_attribute('zoom_set');
				l.css(pos);
				dfr.resolve();
			};
			//Clean UI
			d.find('.slb_content').css({width: '', height: ''}).find('.slb_template_tag').hide();
			d.find('.slb_details').height(0);
			//Show viewer DOM
			d.show(function() {
				if ( window.outerWidth > 480 ) {
					//Center vertically
					pos.top = ( $(document).scrollTop() + $(window).height() / 2 ) - ( l.height() / 2 );
					o.fadeIn(function() {
						final();
					});
				} else {
					final();
				}
			});
			return dfr.promise();
		},
		/**
		 * Close event
		 * @param View.Viewer v Viewer instance
		 * @param jQuery.Deferred dfr Deferred instance to be resolved when animation is complete
		 * @return jQuery.Promise Resolved when transition is complete
		 */
		'close': function(v, dfr) {
			var l = v.get_layout(),
				c = l.find('.slb_content');
			var t = this;
			var reset = function() {
				//Reset state
				t.call_attribute('zoom_unset');
				
				c.width('').height('');
				l.css('opacity', '');
				dfr.resolve();
			}
			if ( v.animation_enabled() && window.outerWidth > 480 ) {
				var lanim = {opacity: 0, top: $(document).scrollTop() + ( $(window).height() / 2 )},
					canim = {width: 0, height: 0};
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
				reset();
			}
			return dfr.promise();
		},
		/**
		 * Item loading event
		 * @param View.Viewer v Viewer instance
		 * @param jQuery.Deferred dfr Deferred instance to be resolved when animation is complete
		 * @return jQuery.Promise Resolved when transition is complete
		 */
		'load': function(v, dfr) {
			v.get_layout().find('.slb_loading').show();
			if ( window.outerWidth > 480 ) {
				return v.get_layout().fadeIn().promise()
			} else {
				v.get_layout().show();
				dfr.resolve();
				return dfr;
			}
		},
		/**
		 * Item unloaded event
		 * @param View.Viewer v Viewer instance
		 * @param jQuery.Deferred dfr Deferred instance to be resolved when animation is complete
		 * @return jQuery.Promise Resolved when transition is complete
		 */
		'unload': function(v, dfr) {
			var l = v.get_layout(),
				det = l.find('.slb_details'),
				cont = l.find('.slb_content .slb_template_tag');
			var props = {height: 0};
			if ( window.outerWidth > 480 ) {
				//Hide details
				det.animate(props, 'slow');
				//Hide content
				cont.fadeOut();
			} else {
				det.css(props);
				cont.hide();
			}
			$.when(det.promise(), cont.promise()).done(function() {
				dfr.resolve();
			});
			return dfr.promise();
		},
		/**
		 * Item loading completed event
		 * @param View.Viewer v Viewer instance
		 * @param jQuery.Deferred dfr Deferred instance to be resolved when animation is complete
		 * @return jQuery.Promise Resolved when transition is complete
		 */
		'complete': function(v, dfr) {
			//Elements
			var l = v.get_layout(),
				loader = l.find('.slb_loading'),
				det = l.find('.slb_details'),
				det_data = det.find('.slb_data'),
				c = l.find('.slb_content'),
				c_tag = c.find('.slb_template_tag'),
				c_tag_cont = c.find('.slb_template_tag_item_content');
			//Transition
			if ( window.outerWidth > 480 ) {
				//Resize viewer to fit item
				var dims = this.get_item_dimensions();
				//Show detail tags (container still hidden)
				det.find('.slb_template_tag').show();
				var pos = { 'top': $(document).scrollTop() + ( $(window).height() / 2 ) - ( this.get_dimensions().height / 2 ) };
				pos.top = pos.top || 0;
				//Resize container
				pos = l.animate(pos).promise();
				dims = c.animate(dims).promise();
				$.when(pos, dims).done(function() {
					//Hide loading indicator
					loader.fadeOut('fast', function() {
						//Display content
						c_tag_cont.fadeIn(function() {
							//Show UI
							c_tag.show();
							//Show details
							det.animate({height: det_data.outerHeight()}, 'slow').promise().done(function() {
								det.height('');
								dfr.resolve();
							});
						});
					});
				});
			} else {
				loader.hide();
				c_tag.show();
				det.height('');
				dfr.resolve();
			}
			return dfr.promise();
		}
	},
	/**
	 * Theme offsets
	 * Reports additional space required for theme UI
	 */
	'offset': function() {
		var dims = {'width': 0, 'height': 0};
		if ( window.outerWidth > 480 ) {
			var d = this.get_viewer().get_layout().find('.slb_details');
			d.find('.slb_template_tag').show();
			$.extend(dims, {'width': 32, 'height': d.find('.slb_data').outerHeight()})
		}
		return dims;
	},
	/**
	 * Theme margins
	 * Reports additional margins used for positioning viewer
	 */
	'margin': function() {
		var m = {'height': 0, 'width': 0};
		if ( window.outerWidth > 480 ) {
			$.extend(m, {'height': 50, 'width': 20});
		}
		return m;
	}
});
})(jQuery);