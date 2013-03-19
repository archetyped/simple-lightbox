(function($) {
return {
	init: function(item, tag, v) {
		console.groupCollapsed('Template_Tag_Handler (UI).init: %o', tag.get_prop());
		//Add event handlers
		v.on('events-complete', function(ev, v) {
			console.info('Event Handler: Template_Tag_Handler(UI).complete');
			//Register event handlers

			/* Close */
			
			var close = function() {
				return v.close();
			};
			//Close button
			v.get_theme().dom_get_tag('ui', 'close').click(close);
			
			/* Navigation */
			
			var nav_next = function() {
				console.info('Viewer.event.nav_next');
				console.groupCollapsed('Tag.UI.nav_next');
				v.item_next();
				console.groupEnd();
			};
			
			var nav_prev = function() {
				console.info('Viewer.event.nav_prev');
				console.groupCollapsed('Tag.UI.nav_prev');
				v.item_prev();
				console.groupEnd();
			};
			
			v.get_theme().dom_get_tag('ui', 'nav_next').click(nav_next);
			v.get_theme().dom_get_tag('ui', 'nav_prev').click(nav_prev);
			
			/* Slideshow */
			
			var slideshow_control = function() {
				console.info('Viewer.event.slideshow_control');
				console.groupCollapsed('Tag.UI.slideshow_control');
				v.slideshow_toggle();
				console.groupEnd();
			};
			
			v.get_theme().dom_get_tag('ui', 'slideshow_control').click(slideshow_control);
		});
		
		v.on('slideshow-toggle', function(ev, v) {
			console.group('Tag.UI.slideshow-toggle');
			//Update slideshow control tag
			var tags = v.get_theme().get_tags('ui', 'slideshow_control');
			if ( tags.length ) {
				for ( var x = 0; x < tags.length; x++ ) {
					tags[x].render(v.get_item()).done(function(r) {
						r.tag.dom_get().html(r.output);
					});
				}
			}
			console.groupEnd();
		});
		console.groupEnd();
	},
	render: function(item, tag) {
		console.groupCollapsed('Template_Tag_Handler (UI).render: %o', tag.get_prop());
		//Initialize event handlers (once per viewer)
		var v = item.get_viewer();
		var st = ['events-init', tag.get_ns(), tag.get_name()].join('_');
		var fmt = function(output) {
			//return '<a href="#" title="%s">%s</a>'.sprintf(output, output);
			return output;
		};
		if ( !v.get_status(st) ) {
			v.set_status(st);
			this.call_attribute('init', item, tag, v);
		}
		//Process content
		var dfr = $.Deferred();
		var ret = this.handle_prop(tag.get_prop(), item, tag);
		if ( this.util.is_promise(ret) ) {
			ret.done(function(output) {
				dfr.resolve(fmt(output));
			});
		} else {
			dfr.resolve(fmt(ret));
		}
		console.groupEnd();
		return dfr.promise();
	},
	props: {
		'slideshow_control': function(item, tag) {
			//Get slideshow status
			var prop = ( item.get_viewer().slideshow_active() ) ? 'slideshow_stop' : 'slideshow_start';
			return item.get_viewer().get_label(prop);
		},
		'group_status': function(item, tag) {
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
				ph = key.wrap(delim);
				if ( out.indexOf(ph) != -1 ) {
					out = out.replace(ph, handlers[key]());
				}
			}
			return out;
		}
	}
}
})(jQuery);