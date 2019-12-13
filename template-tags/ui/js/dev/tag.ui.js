if ( !!window.SLB && SLB.has_child('View.extend_template_tag_handler') ) {(function() {
SLB.View.extend_template_tag_handler('ui', {
	_hooks : function() {
		this.on('dom_init', function(ev) {
			this.call_attribute('events_init', ev);
		});
	},
	events_init: function(ev) {
		var v = ev.data.template.get_theme().get_viewer();
		var thm = v.get_theme();
		// Add event handlers
		v.on('events-complete', function(ev, v) {
			// Register event handlers

			/* Close */

			// Close button
			thm.dom_get_tag('ui', 'close').click(function() {
				return v.close();
			});

			/* Navigation */

			thm.dom_get_tag('ui', 'nav_next').click(function() {
				v.item_next();
			});
			thm.dom_get_tag('ui', 'nav_prev').click(function() {
				v.item_prev();
			});

			/* Slideshow */

			thm.dom_get_tag('ui', 'slideshow_control').click(function() {
				v.slideshow_toggle();
			});
		});

		v.on('slideshow-toggle', function(ev, v) {
			// Update slideshow control tag
			var tags = thm.get_tags('ui', 'slideshow_control');
			if ( tags.length ) {
				// Renderer
				var render_tag = function(tag) {
					tag.render(v.get_item()).done(function(r) {
						r.tag.dom_get().html(r.output);
					});
				};
				// Process tags
				for ( var x = 0; x < tags.length; x++ ) {
					render_tag(tags[x]);
				}
			}
		});
	},
	render: function(item, tag, dfr) {
		// Process content
		var ret = this.handle_prop(tag.get_prop(), item, tag);
		if ( this.util.is_promise(ret) ) {
			ret.done(function(output) {
				dfr.resolve(output);
			});
		} else {
			dfr.resolve(ret);
		}
		return dfr.promise();
	},
	props: {
		'slideshow_control': function(item) {
			// Get slideshow status
			var v = item.get_viewer();
			var prop = ( v.slideshow_active() ) ? 'slideshow_stop' : 'slideshow_start';
			return v.get_label(prop);
		},
		'group_status': function(item) {
			// Handle single items
			if ( item.get_group().is_single() ) {
				return '';
			}
			// Handle groups with multiple items
			var out = item.get_viewer().get_label('group_status');
			var key,
				ph,
				delim = '%',
				handlers = {
					current: function() {
						return item.get_group(true).get_pos() + 1;
					},
					total: function() {
						return item.get_group().get_size();
					}
				};
			// Parse placeholders
			for ( key in handlers ) {
				// Build placeholder
				ph = delim + key + delim;
				// Replace placeholder
				if ( -1 !== out.indexOf(ph) ) {
					out = out.replace(new RegExp(ph, 'ig'), handlers[key]());
				}
			}
			return out;
		}
	}
});
})();}