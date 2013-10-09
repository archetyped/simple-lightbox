(function($) {
$(document).ready(function() {
if ( typeof SLB == 'undefined' || typeof SLB.View == 'undefined' || typeof SLB.View.extend_theme == 'undefined' )
	return false;
SLB.View.extend_theme('slb_baseline', {
	/**
	 * Theme offsets
	 * Reports additional space required for theme UI
	 */
	'offset': function() {
		var dims = {'width': 0, 'height': 0};
		if ( document.documentElement.clientWidth > 480 ) {
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
		if ( document.documentElement.clientWidth > 480 ) {
			$.extend(m, {'height': 50, 'width': 20});
		}
		return m;
	}
});
});
})(jQuery);