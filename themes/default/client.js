{
	'animate': {
		'open': function(v, dfr) {
			console.groupCollapsed('Theme.animate.open()');
			var d = v.dom_get(),
				l = v.get_layout().hide(),
				o = v.get_overlay().hide();
			var top = $(document).scrollTop() + 20;
			//Show viewer DOM
			d.show(function() {
				o.fadeIn(function() {
					l.css({'top': top});
					dfr.resolve();				});
			});
			console.groupEnd();
			return dfr.promise();
		},
		'close': function(v, dfr) {
			console.groupCollapsed('Theme.animate.close()');
			v.get_layout().fadeOut(function() {
				v.get_overlay().fadeOut(function() {
					var l = v.get_layout();
					l.find('.slb_content').width('').height('');
					dfr.resolve();
				});
			});
			console.groupEnd();
			return dfr.promise();
		},
		'load': function(v, dfr) {
			console.groupCollapsed('Theme.animate.load()');
			v.get_layout().find('.slb_loading').fadeIn();
			console.groupEnd();
			return v.get_layout().fadeIn().promise();
		},
		'unload': function(v, dfr) {
			console.groupCollapsed('Theme.animate.unload()');
			console.info('Resolved: %o', dfr.isResolved());
			var l = v.get_layout();
			var det = l.find('.slb_details').slideUp();
			var cont = l.find('.slb_content .slb_template_tag').fadeOut();
			/*
			$.when(det.promise(), cont.promise()).done(function() {
				dfr.resolve();
			});
			*/
			console.groupEnd();
			return dfr.promise();
		},
		'complete': function(v, dfr) {
			console.groupCollapsed('Theme.animate.complete()');
			//Resize viewer to fit item
			var dims = v.get_item().get_dimensions();
			var l = v.get_layout();
			//Resize container
			l.find('.slb_content').animate(dims, function() {
				l.find('.slb_loading').fadeOut();
				//Display content
				$(this).find('.slb_template_tag').fadeIn(function() {
					//Display UI
					l.find('.slb_details').hide().promise().done(function() {
						v.unset_loading().promise().done(function() {
							l.find('.slb_details').slideDown(function() {
								dfr.resolve();
							});
						});
					});
				});
			});
			console.groupEnd();
			return dfr.promise();
		},
		'nav': function(v, dfr) {
			console.groupCollapsed('Theme.animate.nav()');
			console.groupEnd();
		},
	} 
}