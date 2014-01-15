if ( typeof jQuery !== 'undefined' && typeof SLB !== 'undefined' && SLB.View && SLB.View.extend_theme ) {(function($) {
$(document).ready(function() {
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
			//Reset layout and overlay state on open
			var l = v.get_layout().hide(),
				o = v.get_overlay().hide();
			
			//Clean UI
			var thm = this;
			var d = v.dom_get();
			d.find('.slb_content').css({width: '', height: ''}).find(this.get_tag_selector()).hide();
			d.find('.slb_details').height(0);
			//Show viewer DOM
			d.show({'always': function() {
				var pos = {'top_base': $(document).scrollTop()};
				if ( document.documentElement.clientWidth > thm.get_breakpoint('small') ) {
					/* Standard screen */
					//Center vertically
					pos.top = ( pos.top_base + $(window).height() / 2 ) - ( l.height() / 2 );
					if ( pos.top < pos.top_base ) {
						pos.top = pos.top_base;
					}
				} else {
					/* Small screen */
					//Position at top
					pos.top = pos.top_base;
				}
				//Show overlay
				o.fadeIn({'always': function() {
					//Position lightbox
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
			var l = v.get_layout(),
				c = l.find('.slb_content'),
				spd = 'fast';
			var reset = function() {
				//Reset state
				c.width('').height('');
				l.css('opacity', '');
				dfr.resolve();
			};
			if ( v.animation_enabled() && document.documentElement.clientWidth > this.get_breakpoint('small') ) { /* Standard */
				var lanim = {opacity: 0, top: $(document).scrollTop() + ( $(window).height() / 2 )},
					canim = {width: 0, height: 0};
				//Shrink & fade out viewer
				var pos = l.animate(lanim, spd).promise();
				var size = ( $.isEmptyObject(canim) ) ? true : c.animate(canim, spd).promise();
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
			if ( document.documentElement.clientWidth > this.get_breakpoint('small') ) {
				return v.get_layout().fadeIn().promise();
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
			if ( document.documentElement.clientWidth > this.get_breakpoint('small') ) {
				//Hide details
				det.css(props);
				//Hide content
				cont.hide();
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
				c_tag = c.find('.slb_template_tag');
			//Transition
			if ( document.documentElement.clientWidth > this.get_breakpoint('small') ) {
				var spd = 'fast';
				//Resize viewer to fit item
				var dims_item = this.get_item_dimensions();
				var dims_det = {'height': 0, 'width': 0};
				//Determine details height
				det.width(dims_item.width);
				dims_det.height = det_data.outerHeight();
				det.width('');
				//Determine vertical positioning (centered)
				var top_scr = $(document).scrollTop();
				var pos = { 'top': top_scr + ( $(window).height() / 2 ) - ( ( dims_det.height + dims_item.height ) / 2 ) };
				if ( pos.top < top_scr ) {
					pos.top = top_scr;
				}
				pos.top = pos.top || 0;
				//Resize viewer
				pos = l.animate(pos, spd).promise();
				dims_item = c.animate(dims_item, spd).promise();
				//Display elements
				$.when(pos, dims_item).done(function() {
					var c_tag_cont = c.find('.slb_template_tag_item_content');
					//Hide loading indicator
					loader.fadeOut(spd, function() {
						//Display content
						c_tag_cont.fadeIn(function() {
							//Show UI
							c_tag.show();
							//Show details
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
});
})(jQuery);
}