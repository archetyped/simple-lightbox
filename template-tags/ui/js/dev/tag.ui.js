if ( typeof jQuery !== 'undefined' && typeof SLB !== 'undefined' && SLB.View && SLB.View.extend_template_tag_handler ) {(function($) {
$(document).ready(function() {
SLB.View.extend_template_tag_handler('ui', {
	_hooks : function() {
		this.on('dom_init', function(ev) {
			this.call_attribute('dom_init', ev);
		});
	},
	dom_init: function(ev) {
		var v = ev.data.template.get_theme().get_viewer();
		//Add event handlers
		v.on('events-complete', function(ev, v) {
			//Register event handlers

			/* Close */
			
			var close = function() {
				return v.close();
			};
			//Close button
			v.get_theme().dom_get_tag('ui', 'close').click(close);
			
			/* Navigation */
			
			var nav_next = function() {
				v.item_next();
			};
			
			var nav_prev = function() {
				v.item_prev();
			};
			
			v.get_theme().dom_get_tag('ui', 'nav_next').click(nav_next);
			v.get_theme().dom_get_tag('ui', 'nav_prev').click(nav_prev);
			
			/* Slideshow */
			
			var slideshow_control = function() {
				v.slideshow_toggle();
			};
			
			v.get_theme().dom_get_tag('ui', 'slideshow_control').click(slideshow_control);
		});
		
		v.on('slideshow-toggle', function(ev, v) {
			//Update slideshow control tag
			var tags = v.get_theme().get_tags('ui', 'slideshow_control');
			var render_tag = function(tag) {
				tag.render(v.get_item()).done(function(r) {
					r.tag.dom_get().html(r.output);
				});
			};
			if ( tags.length ) {
				for ( var x = 0; x < tags.length; x++ ) {
					render_tag(tags[x]);
				}
			}
		});
	},
	render: function(item, tag, dfr) {
		//Process content
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
			//Get slideshow status
			var prop = ( item.get_viewer().slideshow_active() ) ? 'slideshow_stop' : 'slideshow_start';
			return item.get_viewer().get_label(prop);
		},
		'group_status': function(item) {
			//Handle single items
			if ( item.get_group().is_single() ) {
				return '';
			}
			//Handle groups with multiple items
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
			//Parse placeholders
			for ( key in handlers ) {
				ph = delim + key + delim;
				if ( out.indexOf(ph) !== -1 ) {
					out = out.replace(ph, handlers[key]());
				}
			}
			return out;
		}
	}
});
});
})(jQuery);
}