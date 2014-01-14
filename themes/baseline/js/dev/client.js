if ( typeof jQuery !== 'undefined' && typeof SLB !== 'undefined' && SLB.View && SLB.View.extend_theme ) {(function($) {
$(document).ready(function() {
SLB.View.extend_theme('slb_baseline', {
	/**
	 * Theme offsets
	 * Reports additional space required for theme UI
	 * @return obj Offset width/height values
	 */
	'offset': function() {
		var o;
		if ( document.documentElement.clientWidth > 480 ) {
			o = {'width': 32, 'height': 55};
		} else {
			o = {'width': 0, 'height': 0};
		}
		return o;
	},
	/**
	 * Theme margins
	 * Reports additional margins used for positioning viewer
	 * @return obj Margin width/height values
	 */
	'margin': function() {
		var m;
		if ( document.documentElement.clientWidth > 480 ) {
			m = {'height': 50, 'width': 20};
		} else {
			m = {'height': 0, 'width': 0};
		}
		return m;
	}
});
});
})(jQuery);
}