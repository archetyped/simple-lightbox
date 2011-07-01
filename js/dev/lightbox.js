// -----------------------------------------------------------------------------------
// 
// Simple Lightbox
// by Archetyped - http://archetyped.com/tools/simple-lightbox/
// Updated: 2011-01-27
//
//	Originally based on Lightbox Slideshow v1.1
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
//var SLB = null;
(function($) {
SLB = {
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
	refTags : ['a'],
	relAttribute : null,
	resizeDuration : null,
	slideShowTimer : null,
	startImage : null,
	prefix : '',
	checkedUrls : {},
	
	/**
	 * Initialize lightbox instance
	 * @param object options Instance options
	 */
	initialize: function(options) {
		this.options = $.extend(true, {
			animate : true, // resizing animations
			validateLinks : false, //Validate links before adding them to lightbox
			captionEnabled: true, //Display caption
			captionSrc : true, //Use image source URI if title not set
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
			relAttribute : null, // specifies the rel attribute value that triggers lightbox
			resizeSpeed : 400, // controls the speed of the image resizing (milliseconds)
			showGroupName : false, // show group name of images in image details
			slideTime : 4, // time to display images during slideshow
			altsrc : 'src',
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
		
		
		//Validate options
		if ( 'prefix' in this.options )
			this.prefix = this.options.prefix;
		  //Activation Attribute
		if ( null == this.options.relAttribute )
			 this.options.relAttribute = [this.prefix];
		else if ( !$.isArray(this.options.relAttribute) )
			this.options.relAttribute = [this.options.relAttribute.toString()];
		this.relAttribute = this.options.relAttribute;
		
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
		
		//Set UI
		this.setUI();
		
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
	 * Set localized values for UI elements
	 */
	setUI: function() {
		var s = this.options.strings;
		this.get('slbClose').html(s.closeLink);
		this.get('navNext').html(s.nextLink);
		this.get('navPrev').html(s.prevLink);
		this.get('navSlideControl').html(((this.playSlides) ? s.stopSlideshow : s.startSlideshow));
	},
	
	/**
	 * Add events to various UI elements
	 */
	setEvents: function() {
		var t = this, delay = 500;
		this.get('container,details').click(function(ev) {
			ev.stopPropagation();
		});
		
		var clickP = function() {
			t.get('navPrev').unbind('click').click(false);
			setTimeout(function() {t.get('navPrev').click(clickP)}, delay);
			t.showPrev();
			return false;
		};
		this.get('navPrev').click(function(){
			return clickP();
		});
		
		var clickN = function() {
			t.get('navNext').unbind('click').click(false);
			setTimeout(function() {t.get('navNext').click(clickN)}, delay);
			t.showNext();
			return false;
		};
		this.get('navNext').click(function() {
			return clickN();
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
		var el, els, rel, ph = '{relattr}', t = this;
		var sel = [], selBase = '[href][rel*="' + ph + '"]:not([rel~="' + this.addPrefix('off') + '"])';
		
		//Define event handler
		var handler = function() {
			//Check if element is valid for lightbox
			t.start(this);
			return false;
		};
		
		//Build selector
		for (var i = 0; i < this.refTags.length; i++) {
			for (var x = 0; x < this.relAttribute.length; x++) {
				sel.push(this.refTags[i] + selBase.replace(ph, this.relAttribute[x]));
			}
		}
		sel = sel.join(',');
		//Add event handler to links
		$(sel, $(this.container)).live('click', handler);
	},
	
	/**
	 * Build caption for displayed caption
	 * @param {Object} imageLink
	 */
	getCaption: function(imageLink) {
			imageLink = $(imageLink);
			var caption = '';
			if (this.options.captionEnabled) {
				caption = imageLink.attr('title') || '';
				if (caption == '') {
					var inner = $(imageLink).find('img').first();
					if ($(inner).length) 
						caption = $(inner).attr('title') || $(inner).attr('alt');
					if (!caption) {
						if (imageLink.text().length) 
							caption = imageLink.text();
						else 
							if (this.options.captionSrc) 
								caption = imageLink.attr('href');
					}
					if (!caption) 
						caption = '';
				}
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

		this.imageArray = [];
		this.groupName = this.getGroup(imageLink);
		
		var rel = $(imageLink).attr('rel') || '';
		var imageTitle = '';
		var t = this;
		var groupTemp = {};
		this.fileExists(this.getSourceFile(imageLink),
		function() { //File exists
			// Stretch overlay to fill page and fade in
			t.get('overlay')
				.height($(document).height())
				.fadeTo(t.overlayDuration, t.overlayOpacity);
			
			// Add image to array closure
			var addLink = function(el, idx) {
				groupTemp[idx] = el;
				return groupTemp.length;
			};
			
			//Build final image array & launch lightbox
			var proceed = function() {
				t.startImage = 0;
				//Sort links by document order
				var order = [], el;
				for (var x in groupTemp) {
					order.push(x);
				}
				order.sort(function(a, b) { return (a - b); });
				for (x = 0; x < order.length; x++) {
					el = groupTemp[order[x]];
					//Check if link being evaluated is the same as the clicked link
					if ($(el).get(0) == $(imageLink).get(0)) {
						t.startImage = x;
					}
					t.imageArray.push({'link':t.getSourceFile($(el)), 'title':t.getCaption(el)});
				}
				// Calculate top offset for the lightbox and display 
				var lightboxTop = $(document).scrollTop() + ($(window).height() / 15);
		
				t.get('lightbox').css('top', lightboxTop + 'px').show();
				t.changeImage(t.startImage);
			}
			
			// If image is NOT part of a group..
			if (null == t.groupName) {
				// Add single image to imageArray
				addLink(imageLink, 0);			
				t.startImage = 0;
				proceed();
			} else {
				// If image is part of a group
				var els = $(t.container).find($(imageLink).get(0).tagName.toLowerCase());
				// Loop through links on page & find other images in group
				var grpLinks = [];
				var i, el;
				for (i = 0; i < els.length; i++) {
					el = $(els[i]);
					if (t.getSourceFile(el) && (t.getGroup(el) == t.groupName)) {
						//Add links in same group to temp array
						grpLinks.push(el);
					}
				}
				
				//Loop through group links, validate, and add to imageArray
				var processed = 0;
				for (i = 0; i < grpLinks.length; i++) {
					el = grpLinks[i];
					t.fileExists(t.getSourceFile($(el)),
						function(args) { //File exists
							var el = args.els[args.idx];
							var il = addLink(el, args.idx);
							processed++;
							if (processed == args.els.length)
								proceed();
						},
						function(args) { //File does not exist
							processed++;
							if (args.idx == args.els.length)
								proceed(); 
						},
						{'idx': i, 'els': grpLinks});
				}
			}	
		},
		function() { //File does not exist
			t.end();
		});
	},
	
	/**
	 * Retrieve source URI in link
	 * @param {Object} el
	 * @return string Source file URI
	 */
	getSourceFile: function(el) {
		var src = $(el).attr('href');
		var rel = $(el).attr('rel') || '';
		var reSrc = new RegExp('\\b' + this.options.altsrc + '\\[(.+?)\\](?:\\b|$)');
		if ( reSrc.test(rel) ) {
			src = reSrc.exec(rel)[1];
		}
		return src;
	},
	
	/**
	 * Extract group name from 
	 * @param obj el Element to extract group name from
	 * @return string Group name
	 */
	getGroup: function(el) {
		//Get full attribute value
		var g = null;
		var rel = $(el).attr('rel') || '';
		if (rel != '') {
			var gTmp = '',
				gSt = '[',
				gEnd = ']',
				search = '',
				idx,
				prefix = ' ';
			//Iterate through attributes to find group
			for (var i = 0; i < this.relAttribute.length; i++) {
				search = this.relAttribute[i];
				idx = rel.indexOf(search);
				//Prefix with space to find whole word
				if (prefix != search.charAt(0) && idx > 0) {
					search = prefix + search;
					idx = rel.indexOf(search);
				}
				//Stop processing if value is not found
				if (idx == -1)
					continue;
				gTmp = $.trim(rel.substring(idx).replace(search, ''));
				//Check if group defined
				if (gTmp.length && gSt == gTmp.charAt(0) && gTmp.indexOf(gEnd) != -1) {
					//Extract group name
					g = gTmp.substring(1, gTmp.indexOf(gEnd));
					continue;
				}
			}
		}
		return g;
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
				t.startSlideShow();
		});
		
		//Load image
		imgPreloader.src = this.imageArray[this.activeImage].link;
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
		if (this.options.captionEnabled) {
			this.get('dataCaption').text(this.imageArray[this.activeImage].title);
			this.get('dataCaption').show();
		} else {
			this.get('dataCaption').hide();
		}
		
		// if image is part of set display 'Image x of y' 
		if (this.hasImages()) {
			var num_display = this.options.strings.numDisplayPrefix + ' ' + (this.activeImage + 1) + ' ' + this.options.strings.numDisplaySeparator + ' ' + this.imageArray.length;
			if (this.options.showGroupName && this.groupName != '') {
				num_display += ' ' + this.options.strings.numDisplaySeparator + ' ' + this.groupName;
			}
			this.get('dataNumber')
				.text(num_display)
				.show();
		}
	
		this.get('details').width(this.get('slbContent').width() + (this.options.borderSize * 2));
		this.updateNav();
		var t = this;
		this.get('details').animate({height: 'show', opacity: 'show'}, 650);
	},
	
	/**
	 * Display appropriate previous and next hover navigation.
	 */
	updateNav: function() {
		if (this.hasImages()) {
			this.get('navPrev').show();
			this.get('navNext').show();
			if (this.enableSlideshow) {
				this.get('navSlideControl').show();
				if (this.playSlides) {
					this.startSlideShow();
				} else {
					this.stopSlideShow();
				}
			} else {
				this.get('navSlideControl').hide();
			}
		} else {
			// Hide navigation controls when only one image exists
			this.get('dataNumber').hide();
			this.get('navPrev').hide();
			this.get('navNext').hide();
			this.get('navSlideControl').hide();
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
		clearInterval(this.slideShowTimer);
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
		var t = this;
		$(document).keydown(function(e) {
			t.keyboardAction(e);
		});
	},

	/**
	 * Disable image navigation via the keyboard
	 */
	disableKeyboardNav: function() {
		$(document).unbind('keydown');
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

		if (keycode == 27 || key == 'x' || key == 'o' || key == 'c') { // close lightbox
			this.end();
		} else if (key == 'p' || key == '%') { // display previous image
			this.showPrev();
		} else if (key == 'n' || key =='\'') { // display next image
			this.showNext();
		} else if (key == 'f') { // display first image
			this.showFirst();
		} else if (key == 'l') { // display last image
			this.showLast();
		} else if (key == 's') { // toggle slideshow
			if (this.hasImage() && this.options.enableSlideshow) {
				this.toggleSlideShow();
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
	 * Generate separator text
	 * @param string sep Separator text
	 * @return string Separator text
	 */
	getSep: function(sep) {
		return ( typeof sep == 'undefined' ) ? '_' : sep;
	},
	
	/**
	 * Retrieve prefix
	 * @return string Object prefix
	 */
	getPrefix: function() {
		return this.prefix;
	},

	/**
	 * Add prefix to text
	 * @param string txt Text to add prefix to
	 * @param string sep (optional) Separator text
	 * @return string Prefixed text
	 */
	addPrefix: function(txt, sep) {
		return this.getPrefix() + this.getSep(sep) + txt;
	},
	
	hasPrefix: function(txt) {
		return ( txt.indexOf(this.addPrefix('')) == 0 ) ? true : false;
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
	},
	
	/**
	 * Checks if file exists using AJAX request
	 * @param string url File URL
	 * @param callback success Callback to run if file exists
	 * @param callback failure Callback to run if file does not exist
	 * @param obj args Arguments for callback
	 */
	fileExists: function(url, success, failure, args) {
		if (!this.options.validateLinks)
			return success(args);
		var statusFail = 400;
		var stateCheck = 4;
		var t = this;
		var proceed = function(res) {
			if (res.status < statusFail) {
				if ($.isFunction(success)) 
					success(args);
			} else {
				if ($.isFunction(failure)) 
					failure(args);
			}
		};
		
		//Check if URL already processed
		if (url in this.checkedUrls) {
			proceed(this.checkedUrls[url]);
		} else {
			var req = new XMLHttpRequest();
			req.open('HEAD', url, true);
			req.onreadystatechange = function() {
				if (stateCheck == this.readyState) {
					t.addUrl(url, this);
					proceed(this);
				}
			};
			req.send();
		}
	},
	
	addUrl: function(url, res) {
		if (!(url in this.checkedUrls))
			this.checkedUrls[url] = res;
	}
}
})(jQuery);