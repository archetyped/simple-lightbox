{
	'animate': {
		'open': function() {
			console.groupCollapsed('Theme.animate.open()');
			var dfr = $.Deferred();
			var v = this.get_viewer(),
				d = v.dom_get(),
				l = v.get_layout().hide(),
				o = v.get_overlay().hide();
			//Set layout position
			var top = $(document).scrollTop() + ( $(window).height() / 2 );
			//Show viewer DOM
			d.show().promise().done(function() {
				o.fadeIn().promise().done(function() {
					l.css({'top': top + 'px', 'margin-top': ( ( v.get_layout().height() / 2 ) * -1 ) + 'px'});
					dfr.resolve();
				});
			});
			console.groupEnd();
			return dfr.promise();
		},
		'close': function() {
			console.groupCollapsed('Theme.animate.close()');
			var dfr = $.Deferred();
			var v = this.get_viewer();
			v.get_layout().fadeOut().promise().done(function() {
				v.get_overlay().fadeOut().promise().done(function(r) {
					dfr.resolve(r);
				});
			});
			console.groupEnd();
			return dfr.promise();
			
		},
		'load': function() {
			console.groupCollapsed('Theme.animate.load()');
			console.groupEnd();
			return this.get_viewer().get_layout().fadeIn().promise();
		},
		'unload': function() {
			console.groupCollapsed('Theme.animate.unload()');
			console.groupEnd();
		},
		'complete': function() {
			console.groupCollapsed('Theme.animate.complete()');
			var dfr = $.Deferred();
			var v = this.get_viewer();
			//Resize viewer to fit item
			var dims = v.get_item().get_dimensions();
			var l = v.get_layout();
			//Set layout's position
			l.animate({
				'top': $(document).scrollTop() + ( $(window).height() / 2 ),
				'margin-top': ( dims.height * -1 ) / 2,
			}).promise().done(function() {
				//Resize container
				l.find('.slb_content').animate(dims).promise().done(function() {
					l.find('.slb_loading').fadeOut();
					//Display content
					this.find('.slb_template_tag').fadeIn();
					//Display UI
					l.find('.slb_details').hide().promise().done(function() {
						v.unset_loading();
						this.slideDown();
					});
				});
			});
			//var content = v.get_theme().dom_get_tag('item', 'content');
			//Display item media
			console.groupEnd();
			return dfr.promise();
		},
		'nav': function() {
			console.groupCollapsed('Theme.animate.nav()');
			console.groupEnd();
		},
	} 
}