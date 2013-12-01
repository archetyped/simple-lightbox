/**
 * Admin
 * @package Simple Lightbox
 * @subpackage Admin
 * @author Archetyped
 */

/* global SLB, postboxes, pagenow */

if ( jQuery ){(function ($) {

if ( !SLB || !SLB.attach ) {
	return false;
}

var Admin = {
	/**
	 * Initialization routines 
	 */
	init: function() {
		if ( postboxes ) {
			postboxes.add_postbox_toggles(pagenow);
		}
	},
};

SLB.attach('Admin', Admin);

$(document).ready(function() {
	SLB.Admin.init();
});

})(jQuery);}