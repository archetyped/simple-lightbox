(function($) {
$(document).ready(function() {
//Validation
if ( typeof SLB === 'undefined' || typeof SLB.View === 'undefined' || typeof SLB.View.extend_theme !== 'function' ) {
	return false;
}
//Extend theme
SLB.View.extend_theme('slb_baseline', {
	/**
	 * Theme offsets
	 * Reports additional space required for theme UI
	 */
	'offset': function() {
		var o = {'width': 0, 'height': 0};
		if ( document.documentElement.clientWidth > 480 ) {
			$.extend(o, {'width': 32, 'height': 55});
		}
		return o;
	},
	/**
	 * Theme margins
	 * Reports additional margins used for positioning viewer
	 */
	'margin': function() {
		var m = {'height': 0, 'width': 0};
		if ( document.documentElement.clientWidth > 480 ) {
			$.extend(m, {'height': 50, 'width': 20});
		}
		return m;
	}
});
});
})(jQuery);