// -----------------------------------------------------------------------------------
// 
// Simple Lightbox
// by Archetyped - http://archetyped.com/tools/simple-lightbox/
// Updated: 2011-01-27
//
//	Largely based on Lightbox Slideshow v1.1
//	by Justin Barkhuff - http://www.justinbarkhuff.com/lab/lightbox_slideshow/
//  2007/08/15
//
//	Largely based on Lightbox v2.02
//	by Lokesh Dhakar - http://huddletogether.com/projects/lightbox2/
//	2006/03/31
//
//	Licensed under the Creative Commons Attribution 2.5 License - http://creativecommons.org/licenses/by/2.5/
//
//	The code inserts HTML at the bottom of the page for displaying content in a non-modal dialog
//
// -----------------------------------------------------------------------------------

/**
 * Lightbox object
 */
var Lightbox = null;
(function($) {
Lightbox = {
	activeImage : null,
	badObjects : ['select','object','embed'],
	container : null,
	enableSlideshow : null,
	groupName : null,
	imageArray : [],
	options : null,
	overlayDuration : null,
	overlayOpacity : null,
	playSlides : null,
	refTags : ['a','area'],
	relAttribute : null,
	resizeDuration : null,
	slideShowTimer : null,
	startImage : null,
	prefix : 'slb',
	
	/**
	 * Initialize lightbox instance
	 * @param object options Instance options
	 */
	initialize: function(options) {
		this.options = $.extend(true, {
			animate : true, // resizing animations
			autoPlay : true, // should slideshow start automatically
			borderSize : 10, // if you adjust the padding in the CSS, you will need to update this variable
			containerID : document, // lightbox container object
			enableSlideshow : true, // enable slideshow feature
			googleAnalytics : false, // track individual image views using Google Analytics
			imageDataLocation : 'south', // location of image caption information
			initImage : '', // ID of image link to automatically launch when upon script initialization
			loop : true, // whether to continuously loop slideshow images
			overlayDuration : .2, // time to fade in shadow overlay
			overlayOpacity : .8, // transparency of shadow overlay
			relAttribute : 'lightbox', // specifies the rel attribute value that triggers lightbox
			resizeSpeed : 400, // controls the speed of the image resizing (milliseconds)
			showGroupName : false, // show group name of images in image details
			slideTime : 4, // time to display images during slideshow
			strings : { // allows for localization
				closeLink : 'close',
				loadingMsg : 'loading',
				nextLink : 'next &raquo;',
				prevLink : '&laquo; prev',
				startSlideshow : 'start slideshow',
				stopSlideshow : 'stop slideshow',
				numDisplayPrefix : 'Image',
				numDisplaySeparator : 'of'
			},
			placeholders : {
				slbContent: '<img id="slb_slbContent" />',
				slbLoading: '<span id="slb_slbLoading">loading</span>',
				slbClose: '<a class="slb_slbClose" href="#">close</a>',
				navPrev: '<a class="slb_navPrev slb_nav" href="#">&laquo; prev</a>',
				navNext: '<a class="slb_navNext slb_nav" href="#">&raquo; next</a>',
				navSlideControl: '<a class="slb_navSlideControl" href="#">Stop</a>',
				dataCaption: '<span class="slb_dataCaption"></span>',
				dataNumber: '<span class="slb_dataNumber"></span>'
			},
			layout : null
        }, options);
		
		//Stop if no layout is defined
		if (!this.options.layout || this.options.layout.toString().length == 0)
			this.end();
		
		if ( this.options.animate ) {
			this.overlayDuration = Math.max(this.options.overlayDuration,0);
			this.resizeDuration = this.options.resizeSpeed;
		} else {
			this.overlayDuration = 0;
			this.resizeDuration = 0;
		}
		
		this.enableSlideshow = this.options.enableSlideshow;
		this.overlayOpacity = Math.max(Math.min(this.options.overlayOpacity,1),0);
		this.playSlides = this.options.autoPlay;
		this.container = $(this.options.containerID);
		this.relAttribute = this.options.relAttribute;
		this.updateImageList();
		var t = this;
		var objBody = $(this.container).get(0) != document ? this.container : $('body');
		
		var objOverlay = $('<div/>', {
			'id': this.getID('overlay'),
			'css': {'display': 'none'}
		}).appendTo(objBody)
		  .click(function() {t.end()});
		
		var objLightbox = $('<div/>', {
			'id': this.getID('lightbox'),
			'css': {'display': 'none'}
		}).appendTo(objBody)
		  .click(function() {t.end()});
		
		//Build layout from template
		var layout = this.getLayout();
		
		//Append to container
		$(layout).appendTo(objLightbox);
		
		//Add events
		this.setEvents();
		
		if (this.options.initImage != '') {
			this.start($(this.options.initImage));
		}
	},
	
	/**
	 * Build layout from template
	 * @uses options.layout
	 * @return string Layout markup (HTML)
	 */
	getLayout: function() {
		var l = this.options.layout;
		
		//Expand placeholders
		var ph, phs, phr;
		for (ph in this.options.placeholders) {
			phs = '{' + ph + '}';
			//Continue to next placeholder if current one is not in layout
			if (l.indexOf(phs) == -1)
				continue;
			phr = new RegExp(phs, "g");
			l = l.replace(phr, this.options.placeholders[ph]);
		}
		
		//Return final layout
		return l;
		
	},
	
	/**
	 * Add events to various UI elements
	 */
	setEvents: function() {
		var t = this;
		this.get('container,details').click(function(ev) {
			ev.stopPropagation();
		});
		this.get('navPrev').click(function() {
			t.showPrev();
			return false;
		});
		this.get('navNext').click(function() {
			t.showNext();
			return false;
		});
		this.get('navSlideControl').click(function() {
			t.toggleSlideShow();
			return false;
		});
		this.get('slbClose').click(function() {
			t.end();
			return false;
		});
	},
	
	/**
	 * Finds all compatible image links on page
	 */
	updateImageList: function() {
		var el, els, rel, t = this;
		for(var i=0; i < this.refTags.length; i++) {
			els = $(this.container).find(this.refTags[i]);
			for(var j=0; j < els.length; j++) {
				el = els[j];
				rel = $(el).attr('rel');
				if ($(el).attr('href') && (rel.toLowerCase().match(this.relAttribute))) {
					$(el).click(function() {
						t.start(this);
						return false;
					});
				}
			}
		}
	},
	
	/**
	 * Build caption for displayed caption
	 * @param {Object} imageLink
	 */
	getCaption: function(imageLink) {
			imageLink = $(imageLink);
			var caption = imageLink.attr('title') || '';
			if ( caption == '' ) {
				var inner = $(imageLink).find('img').first();
				if ( $(inner).length )
					caption = $(inner).attr('title') || $(inner).attr('alt');
				if ( !caption )
					caption = imageLink.text() || imageLink.attr('href') || '';
			}
			return caption;
	},

	/**
	 * Display overlay and lightbox. If image is part of a set, add siblings to imageArray.
	 * @param node imageLink Link element containing image URL
	 */
	start: function(imageLink) {
		imageLink = $(imageLink);
		this.hideBadObjects();

		// stretch overlay to fill page and fade in
		this.get('overlay')
			.height($(document).height())
			.fadeTo(this.overlayDuration, this.overlayOpacity);
		this.imageArray = [];
		this.groupName = null;
		
		var rel = $(imageLink).attr('rel');
		var imageTitle = '';
		
		// if image is NOT part of a group..
		if (rel == this.relAttribute) {
			// add single image to imageArray
			imageTitle = this.getCaption(imageLink);
			this.imageArray.push({'link':$(imageLink).attr('href'), 'title':imageTitle});			
			this.startImage = 0;
		} else {
			// if image is part of a group..
			
			var els = $(this.container).find($(imageLink).get(0).tagName.toLowerCase());
			// loop through anchors, find other images in group, and add them to imageArray
			for (var i=0; i < els.length; i++) {
				var el = $(els[i]);
				if (el.attr('href') && (el.attr('rel') == rel)) {
					imageTitle = this.getCaption(el);
					this.imageArray.push({'link':el.attr('href'),'title':imageTitle});
					if ($(el).get(0) == $(imageLink).get(0)) {
						this.startImage = this.imageArray.length - 1;
					}
				}
			}
			// get group name
			this.groupName = rel.substring(this.relAttribute.length + 1, rel.length - 1);
		}

		// calculate top offset for the lightbox and display 
		var lightboxTop = $(document).scrollTop() + ($(window).height() / 15);

		this.get('lightbox').css('top', lightboxTop + 'px').show();
		this.changeImage(this.startImage);
	},

	/**
	 * Preload requested image prior to displaying it in lightbox 
	 * @param int imageNum Index of image in imageArray property
	 * @uses imageArray to retrieve index at specified image
	 * @uses resizeImageContainer() to resize lightbox after image has loaded
	 */
	changeImage: function(imageNum) {
		this.activeImage = imageNum;

		this.disableKeyboardNav();
		this.pauseSlideShow();

		// hide elements during transition
		this.get('slbLoading').show();
		this.get('slbContent').hide();
		this.get('details').hide();
		var imgPreloader = new Image();
		var t = this;
		// once image is preloaded, resize image container
		$(imgPreloader).bind('load', function() {
			t.get('slbContent').attr('src', imgPreloader.src);
			t.resizeImageContainer(imgPreloader.width, imgPreloader.height);
			//Restart slideshow if active
			if ( t.isSlideShowActive() )
				t.startSlideshow();
		});

		imgPreloader.src = this.imageArray[this.activeImage].link;
		
		if (this.options.googleAnalytics) {
			urchinTracker(this.imageArray[this.activeImage].link);
		}
	},

	/**
	 * Resizes lightbox to fit image
	 * @param int imgWidth Image width in pixels
	 * @param int imgHeight Image height in pixels
	 */
	resizeImageContainer: function(imgWidth, imgHeight) {
		// get current height and width
		var el = this.get('container');
		var borderSize = this.options.borderSize * 2;
		
		this.get('container').animate({width: imgWidth + borderSize, height: imgHeight + borderSize}, this.resizeDuration)

		this.showImage();
	},
	
	/**
	 * Display image and begin preloading neighbors.
	 */	
	showImage: function() {
		this.get('slbLoading').hide();
		var t = this;
		this.get('slbContent').fadeIn(500, function() { t.updateDetails(); });
		this.preloadNeighborImages();
	},

	/**
	 * Display caption, image number, and bottom nav
	 */
	updateDetails: function() {
		this.get('dataCaption').text(this.imageArray[this.activeImage].title);
		this.get('dataCaption').show();
		
		// if image is part of set display 'Image x of y' 
		if (this.hasImages()) {
			var num_display = this.options.strings.numDisplayPrefix + ' ' + eval(this.activeImage + 1) + ' ' + this.options.strings.numDisplaySeparator + ' ' + this.imageArray.length;
			if (this.options.showGroupName && this.groupName != '') {
				num_display += ' ' + this.options.strings.numDisplaySeparator + ' ' + this.groupName;
			}
			this.get('dataNumber')
				.text(num_display)
				.show();
			if (!this.enableSlideshow) {
				this.get('navSlideControl').hide();
			}
			this.get('detailsNav').show();
		}
	
		this.get('details').width(this.get('slbContent').width() + (this.options.borderSize * 2));
		
		var t = this;
		this.get('details').animate({height: 'show', opacity: 'show'}, 650, function() {t.updateNav();});
	},
	
	/**
	 * Display appropriate previous and next hover navigation.
	 */
	updateNav: function() {
		if (this.hasImages()) {
			if (this.enableSlideshow) {
				if (this.playSlides) {
					this.startSlideShow();
				} else {
					this.stopSlideShow();
				}
			}
		}
		this.enableKeyboardNav();
	},
	
	/**
	 * Checks if slideshow is currently activated
	 * @return bool TRUE if slideshow is active, FALSE otherwise
	 * @uses playSlides to check slideshow activation status
	 */
	isSlideShowActive: function() {
		return this.playSlides;
	},
	
	/**
	 * Start the slideshow
	 */
	startSlideShow: function() {
		this.playSlides = true;
		var t = this;
		this.slideShowTimer = setInterval(function() { t.showNext(); t.pauseSlideShow(); }, this.options.slideTime * 1000);
		this.get('navSlideControl').text(this.options.strings.stopSlideshow);
	},
	
	/**
	 * Stop the slideshow
	 */
	stopSlideShow: function() {
		this.playSlides = false;
		if (this.slideShowTimer) {
			clearInterval(this.slideShowTimer);
		}
		this.get('navSlideControl').text(this.options.strings.startSlideshow);
	},

	/**
	 * Toggles the slideshow status
	 */
	toggleSlideShow: function() {
		if (this.playSlides) {
			this.stopSlideShow();
		}else{
			this.startSlideShow();
		}
	},

	/**
	 * Pauses the slideshow
	 * Stops the slideshow but does not change the slideshow's activation status
	 */
	pauseSlideShow: function() {
		if (this.slideShowTimer) {
			clearInterval(this.slideShowTimer);
		}
	},
	
	/**
	 * Check if there is at least one image to display in the lightbox
	 * @return bool TRUE if at least one image is found
	 * @uses imageArray to check for images
	 */
	hasImage: function() {
		return ( this.imageArray.length > 0 );
	},
	
	/**
	 * Check if there are multiple images to display in the lightbox
	 * @return bool TRUE if there are multiple images
	 * @uses imageArray to determine the number of images
	 */
	hasImages: function() {
		return ( this.imageArray.length > 1 );
	},
	
	/**
	 * Check if the current image is the first image in the list
	 * @return bool TRUE if image is first
	 * @uses activeImage to check index of current image
	 */
	isFirstImage: function() {
		return ( this.activeImage == 0 );
	},
	
	/**
	 * Check if the current image is the last image in the list
	 * @return bool TRUE if image is last
	 * @uses activeImage to check index of current image
	 * @uses imageArray to compare current image to total number of images
	 */
	isLastImage: function() {
		return ( this.activeImage == this.imageArray.length - 1 );
	},
	
	/**
	 * Show the next image in the list
	 */
	showNext : function() {
		if (this.hasImages()) {
			if ( !this.options.loop && this.isLastImage() ) {
				return this.end();
			}
			if ( this.isLastImage() ) {
				this.showFirst();
			} else {
				this.changeImage(this.activeImage + 1);
			}
		}
	},

	/**
	 * Show the previous image in the list
	 */
	showPrev : function() {
		if (this.hasImages()) {
			if ( !this.options.loop && this.isFirstImage() )
				return this.end();
			if (this.activeImage == 0) {
				this.showLast();
			} else {
				this.changeImage(this.activeImage - 1);
			}
		}
	},
	
	/**
	 * Show the first image in the list
	 */
	showFirst : function() {
		if (this.hasImages()) {
			this.changeImage(0);
		}
	},

	/**
	 * Show the last image in the list
	 */
	showLast : function() {
		if (this.hasImages()) {
			this.changeImage(this.imageArray.length - 1);
		}
	},

	/**
	 * Enable image navigation via the keyboard
	 */
	enableKeyboardNav: function() {
		document.onkeydown = this.keyboardAction; 
	},

	/**
	 * Disable image navigation via the keyboard
	 */
	disableKeyboardNav: function() {
		document.onkeydown = '';
	},

	/**
	 * Handler for keyboard events
	 * @param event e Keyboard event data
	 */
	keyboardAction: function(e) {
		if (e == null) { // ie
			keycode = event.keyCode;
		} else { // mozilla
			keycode = e.which;
		}

		key = String.fromCharCode(keycode).toLowerCase();
		var t = this;
		
		if (key == 'x' || key == 'o' || key == 'c') { // close lightbox
			t.end();
		} else if (key == 'p' || key == '%') { // display previous image
			t.showPrev();
		} else if (key == 'n' || key =='\'') { // display next image
			t.showNext();
		} else if (key == 'f') { // display first image
			t.showFirst();
		} else if (key == 'l') { // display last image
			t.showLast();
		} else if (key == 's') { // toggle slideshow
			if (t.hasImage() && t.options.enableSlideshow) {
				t.toggleSlideShow();
			}
		}
	},

	/**
	 * Preloads images before/after current image
	 */
	preloadNeighborImages: function() {
		var nextImageID = this.imageArray.length - 1 == this.activeImage ? 0 : this.activeImage + 1;
		nextImage = new Image();
		nextImage.src = this.imageArray[nextImageID].link;

		var prevImageID = this.activeImage == 0 ? this.imageArray.length - 1 : this.activeImage - 1;
		prevImage = new Image();
		prevImage.src = this.imageArray[prevImageID].link;
	},

	/**
	 * Close the lightbox
	 */
	end: function() {
		this.disableKeyboardNav();
		this.pauseSlideShow();
		this.get('lightbox').hide();
		this.get('overlay').fadeOut(this.overlayDuration);
		this.showBadObjects();
	},
	
	/**
	 * Displays objects that may conflict with the lightbox
	 * @param bool show (optional) Whether or not to show objects (Default: TRUE)
	 */
	showBadObjects: function (show) {
		show = ( typeof(show) == 'undefined' ) ? true : !!show;
		var vis = (show) ? 'visible' : 'hidden';
		$(this.badObjects.join(',')).css('visibility', vis);
	},
	
	/**
	 * Hides objects that may conflict with the lightbox
	 * @uses showBadObjects() to hide objects
	 */
	hideBadObjects: function () {
		this.showBadObjects(false);
	},

	/**
	 * Add prefix to text
	 * @param string txt Text to add prefix to
	 * @return string Prefixed text
	 */
	addPrefix: function(txt) {
		return this.prefix + '_' + txt;
	},
	
	/**
	 * Generate formatted ID for lightbox-specific elements
	 * @param string id Base ID of element
	 * @return string Formatted ID
	 */
	getID: function(id) {
		return this.addPrefix(id);
	},
	
	/**
	 * Generate formatted selector for lightbox-specific elements
	 * Compares specified ID to placeholders first, then named elements
	 * Multiple selectors can be included and separated by commas (',')
	 * @param string id Base ID of element
	 * @uses options.placeholders to compare id to placeholder names
	 * @return string Formatted selector
	 */
	getSel: function(id) {
		//Process multiple selectors
		var delim = ',', prefix = '#', sel;
		if (id.toString().indexOf(delim) != -1) {
			//Split
			var sels = id.toString().split(delim);
			//Build selector
			for (var x = 0; x < sels.length; x++) {
				sels[x] = this.getSel($.trim(sels[x]));
			}
			//Join
			sel = sels.join(delim);
		} else {
			//Single selector
			if (id in this.options.placeholders) {
				var ph = $(this.options.placeholders[id]);
				if (!ph.attr('id')) {
					//Class selector
					prefix = '.';
				}
			}
			sel = prefix + this.getID(id);
		}
		
		return sel;
	},
	
	/**
	 * Retrieve lightbox-specific element
	 * @param string id Base ID of element
	 * @uses getSel() to generate formatted selector for element
	 * @return object jQuery object of selected element(s)
	 */
	get: function(id) {
		return $(this.getSel(id));
	}
}
})(jQuery);