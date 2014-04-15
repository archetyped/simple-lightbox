if ( !!window.SLB && SLB.has_child('View.extend_theme') ) {(function($) {
SLB.View.extend_theme('slb_default', {
	/**
	 * Transition event handlers
	 */
	'transition': {
		/**
		 * Open event
		 * @param View.Viewer v Viewer instance
		 * @param deferred dfr Resolved when transition is complete
		 * @return promise Resolved when transition is complete
		 */
		'open': function(v, dfr) {
			// Reset layout and overlay state on open
			var l = v.get_layout().hide(),
				o = v.get_overlay().hide();
			
			// Clean UI
			var thm = this;
			var d = v.dom_get();
			d.find('.slb_content').css({width: '', height: ''}).find(this.get_tag_selector()).hide();
			d.find('.slb_details').height(0);
			// Show viewer DOM
			d.show({'always': function() {
				var pos = {'top_base': $(document).scrollTop()};
				if ( document.documentElement.clientWidth > thm.get_breakpoint('small') ) {
					/* Standard screen */
					// Center vertically
					pos.top = ( pos.top_base + $(window).height() / 2 ) - ( l.height() / 2 );
					if ( pos.top < pos.top_base ) {
						pos.top = pos.top_base;
					}
				} else {
					/* Small screen */
					// Position at top
					pos.top = pos.top_base;
				}
				// Show overlay
				o.fadeIn({'always': function() {
					// Position lightbox
					l.css(pos);
					dfr.resolve();
				}});
			}});
			return dfr.promise();
		},
		/**
		 * Close event
		 * @param View.Viewer v Viewer instance
		 * @param jQuery.Deferred dfr Deferred instance to be resolved when animation is complete
		 * @return jQuery.Promise Resolved when transition is complete
		 */
		'close': function(v, dfr) {
			// Viewer elements
			var l = v.get_layout(),
				c = l.find('.slb_content');
			// Reset procedure
			var reset = function() {
				// Reset state
				c.width('').height('');
				l.css('opacity', '');
				dfr.resolve();
			};
			if ( v.animation_enabled() && document.documentElement.clientWidth > this.get_breakpoint('small') ) { 
				/* Standard */
				var anims = {
					'layout': { opacity: 0, top: $(document).scrollTop() + ( $(window).height() / 2 ) },
					'content': { width: 0, height: 0 },
					'speed': 'fast'
				};
				// Shrink & fade out viewer
				var pos = l.animate(anims.layout, anims.speed).promise();
				var size = c.animate(anims.content, anims.speed).promise();
				$.when(pos, size).done(function() {
					// Fade out overlay
					v.get_overlay().fadeOut({'always': function() {
						reset();
					}});
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
		 * @param deferred dfr Resolved when animation is complete
		 * @return promise Resolved when transition is complete
		 */
		'load': function(v) {
			v.get_layout().find('.slb_loading').show();
			if ( document.documentElement.clientWidth > this.get_breakpoint('small') ) {
				return v.get_layout().fadeIn().promise();
			} else {
				return v.get_layout().show().promise();
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
				cont = l.find('.slb_content ' + this.get_tag_selector());
			var props = {height: 0};
			// Hide details
			det.css(props);
			// Hide content
			cont.hide();
			// Finish
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
			// Elements
			var l = v.get_layout(),
				loader = l.find('.slb_loading'),
				det = l.find('.slb_details'),
				det_data = det.find('.slb_data'),
				c = l.find('.slb_content'),
				c_tag = c.find( this.get_tag_selector() );
			// Transition
			if ( document.documentElement.clientWidth > this.get_breakpoint('small') ) {
				var spd = 'fast';
				// Resize viewer to fit item
				var dims_item = this.get_item_dimensions();
				// Determine details height
				det.width(dims_item.width);
				var dims_det = {'height': det_data.outerHeight()};
				// Reset width
				det.width('');
				// Determine vertical positioning (centered)
				var pos = { 'top_base': $(document).scrollTop() };
				// Center vertically
				pos.top = pos.top_base + ( $(window).height() / 2 ) - ( ( dims_det.height + dims_item.height ) / 2 );
				if ( pos.top < pos.top_base ) {
					pos.top = pos.top_base;
				}
				
				// Position/Resize viewer
				pos = l.animate(pos, spd).promise();
				dims_item = c.animate(dims_item, spd).promise();
				// Display elements
				var thm = this;
				$.when(pos, dims_item).done(function() {
					// Hide loading indicator
					loader.fadeOut(spd, function() {
						// Display content
						c.find( thm.get_tag_selector('item', 'content') ).fadeIn(function() {
							// Show UI
							c_tag.show();
							// Show details
							det.animate({'height': det_data.outerHeight()}, 'slow').promise().done(function() {
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
	}
});
})(jQuery);
}