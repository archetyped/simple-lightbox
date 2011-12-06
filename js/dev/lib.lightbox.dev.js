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
// -----------------------------------------------------------------------------------
/**
 * Lightbox
 * @package SLB
 * @subpackage Lightbox
 * @author Archetyped
 */
(function($) {
SLB = {
	active_image : null,
	bad_objects : ['select','object','embed','iframe'],
	container : null,
	enabled_slideshow : null,
	group_name : null,
	image_array : [],
	options : null,
	overlay_duration : null,
	overlay_opacity : null,
	play_slides : null,
	ref_tags : ['a'],
	identifier : null,
	resize_duration : null,
	slideshow_timer : null,
	start_image : null,
	prefix : '',
	checked_urls : {},
	media : {},
	
	/**
	 * Initialize lightbox instance
	 * @param object options Instance options
	 */
	initialize: function(options) {
		this.options = $.extend(true, {
			animate : true, // resizing animations
			validate_links : false, //Validate links before adding them to lightbox
			enabled_caption: true, //Display caption
			caption_src : true, //Use image source URI if title not set
			enabled_desc: true, //Display description
			autostart : true, // should slideshow start automatically
			border_size : 10, // if you adjust the padding in the CSS, you will need to update this variable
			container_id : document, // lightbox container object
			enabled_slideshow : true, // enable slideshow feature
			init_image : '', // ID of image link to automatically launch when upon script initialization
			loop : true, // whether to continuously loop slideshow images
			overlay_duration : .2, // time to fade in shadow overlay
			overlay_opacity : .8, // transparency of shadow overlay
			identifier : null, // specifies the rel attribute value that triggers lightbox
			resize_speed : 400, // controls the speed of the image resizing (milliseconds)
			show_group_name : false, // show group name of images in image details
			duration : 4, // time to display images during slideshow
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
				dataDescription: '<span class="slb_dataDescription"></span>',
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
		if ( null == this.options.identifier )
			 this.options.identifier = [this.prefix];
		else if ( !$.isArray(this.options.identifier) )
			this.options.identifier = [this.options.identifier.toString()];
		this.identifier = this.options.identifier;
		
		if ( this.options.animate ) {
			this.overlay_duration = Math.max(this.options.overlay_duration,0);
			this.resize_duration = this.options.resize_speed;
		} else {
			this.overlay_duration = 0;
			this.resize_duration = 0;
		}
		this.enabled_slideshow = this.options.enabled_slideshow;
		this.overlay_opacity = Math.max(Math.min(this.options.overlay_opacity,1),0);
		this.play_slides = this.options.autostart;
		this.container = $(this.options.container_id);
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
		
		if (this.options.init_image != '') {
			this.start($(this.options.init_image));
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
		this.get('navSlideControl').html(((this.play_slides) ? s.stopSlideshow : s.startSlideshow));
	},
	
	/**
	 * Add events to various UI elements
	 */
	setEvents: function() {
		var t = this, delay = 500;
		//Remove all previous handlers
		this.get('container,details,navPrev,navNext,navSlideControl,slbClose').unbind('click');
		
		//Set event handlers
		this.get('container,details').click(function(ev) {
			ev.stopPropagation();
		});
		
		var clickP = function() {
			//Handle double clicks
			t.get('navPrev').unbind('click').click(false);
			setTimeout(function() {t.get('navPrev').click(clickP)}, delay);
			t.showPrev();
			return false;
		};
		this.get('navPrev').click(function(){
			return clickP();
		});
		
		var clickN = function() {
			//Handle double clicks
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
	 * @return void
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
		for (var i = 0; i < this.ref_tags.length; i++) {
			for (var x = 0; x < this.identifier.length; x++) {
				sel.push(this.ref_tags[i] + selBase.replace(ph, this.identifier[x]));
			}
		}
		sel = sel.join(',');
		//Add event handler to links
		$(sel, $(this.container)).live('click', handler);
	},
	
	/**
	 * Display overlay and lightbox. If image is part of a set, add siblings to image_array.
	 * @param node imageLink Link element containing image URL
	 */
	start: function(imageLink) {
		imageLink = $(imageLink);
		this.hideBadObjects();

		this.image_array = [];
		this.group_name = this.getGroup(imageLink);
		
		var rel = $(imageLink).attr('rel') || '';
		var imageTitle = '';
		var t = this;
		var groupTemp = {};
		this.fileExists(this.getSourceFile(imageLink),
		function() { //File exists
			// Stretch overlay to fill page and fade in
			t.get('overlay')
				.height($(document).height())
				.fadeTo(t.overlay_duration, t.overlay_opacity);
			
			// Add image to array closure
			var addLink = function(el, idx) {
				groupTemp[idx] = el;
				return groupTemp.length;
			};
			
			//Build final image array & launch lightbox
			var proceed = function() {
				t.start_image = 0;
				//Sort links by document order
				var order = [], el;
				for (var x in groupTemp) {
					order.push(x);
				}
				order.sort(function(a, b) { return (a - b); });
				for (x = 0; x < order.length; x++) {
					el = groupTemp[order[x]];
					// Check if link being evaluated is the same as the clicked link
					if ($(el).get(0) == $(imageLink).get(0)) {
						t.start_image = x;
					}
					t.image_array.push({'link':t.getSourceFile($(el)), 'title':t.getCaption(el), 'desc': t.getDescription(el)});
				}
				// Calculate top offset for the lightbox and display 
				var lightboxTop = $(document).scrollTop() + ($(window).height() / 15);
		
				t.get('lightbox').css('top', lightboxTop + 'px').show();
				t.changeImage(t.start_image);
			}
			// If image is NOT part of a group..
			if (null == t.group_name) {
				// Add single image to array
				t.start_image = 0;
				addLink(imageLink, t.start_image);			
				proceed();
			} else {
				// Image is part of a group
				var els = $(t.container).find(t.ref_tags.join(',').toLowerCase());
				// Loop through links on page & find other images in group
				var grpLinks = [];
				var i, el;
				for (i = 0; i < els.length; i++) {
					el = $(els[i]);
					if (t.getSourceFile(el) && (t.getGroup(el) == t.group_name)) {
						//Add links in same group to temp array
						grpLinks.push(el);
					}
				}
				
				//Loop through group links, validate, and add to image_array
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
	 * Retrieve ID of media item
	 * @param obj el Link element
	 * @return string|bool Media ID (Default: false - No ID)
	 */
	getMediaId: function(el) {
		var h = $(el).attr('href');
		if ($.type(h) !== 'string') 
			h = false; 
		return h;
	},
	
	/**
	 * Retrieve Media properties
	 * @param obj el Link element
	 * @return obj Properties for Media item (Default: Empty)
	 */
	getMediaProperties: function(el) {
		var props = {},
			m_id = this.getMediaId(el);
		if (m_id && m_id in this.media) {
			props = this.media[m_id];
		}
		return props;
	},
	
	/**
	 * Retrieve single property for media item
	 * @param obj el Image link DOM element
	 * @param string prop Property to retrieve
	 * @return mixed|null Item property (Default: NULL if property does not exist)
	 */
	getMediaProperty: function(el, prop) {
		var props = this.getMediaProperties(el);
		return (prop in props) ? props[prop] : null;
	},
	
	/**
	 * Build caption for displayed item
	 * @param obj imageLink Image link DOM element
	 * @return string Image caption
	 */
	getCaption: function(imageLink) {
		imageLink = $(imageLink);
		var caption = '';
		if (this.options.enabled_caption) {
			var sels = {
				'capt': '.wp-caption-text',
				'gIcon': '.gallery-icon'
			};
			var els = {
				'link': imageLink,
				'origin': imageLink,
				'sibs': null,
				'img': null
			}
			//WP Caption
			if ( $(els.link).parent(sels.gIcon).length > 0 ) {
				els.origin = $(els.link).parent();
			}
			if ( (els.sibs = $(els.origin).siblings(sels.capt)) && $(els.sibs).length > 0 ) {
				caption = $.trim($(els.sibs).first().text());
			}
			
			//Fall back to image properties
			if ( !caption ) {
				els.img = $(els.link).find('img').first();
				if ( $(els.img).length ) {
					//Image title / alt
					caption = $(els.img).attr('title') || $(els.img).attr('alt');
					caption = $.trim(caption);
				}
			}
			
			//Media properties
			if ( !caption ) {
				caption = this.getMediaProperty(els.link, 'title') || '';
				caption = $.trim(caption);
			}
			
			//Fall back Link Text
			if ( !caption ) {
				var c = '';
				if ( ( c = $.trim($(els.link).text()) ) && c.length) {
					caption = c;
				} else if (this.options.caption_src) {
					//Fall back to Link href
					caption = $(els.link).attr('href');
					var trimChars = ['/', '#', '.'];
					//Trim invalid characters
					while ( caption.length && $.inArray(caption.charAt(0), trimChars) != -1 )
						caption = caption.substr(1);
					while ( caption.length && $.inArray(caption.charAt(caption.length - 1), trimChars) != -1 )
						caption = caption.substr(0, caption.length - 1);
				
					//Strip to base file name
					var idx = caption.lastIndexOf('/');
					if ( -1 != idx )
						caption = caption.substr(idx + 1);
					//Strip extension
					idx = caption.lastIndexOf('.');
					if ( -1 != idx ) {
						caption = caption.slice(0, idx);
					}
				}
				caption = $.trim(caption);
			}
		}
		return caption;
	},
	
	/**
	 * Retrieve item description
	 * @param obj imageLink
	 * @return string Item description (Default: empty string)
	 */
	getDescription: function(imageLink) {
		var desc = '';
		if (this.options.enabled_desc) {
			//Retrieve description
			if (this.inGallery(imageLink, 'ng')) {
				desc = $(imageLink).attr('title');
			}
			else 
				desc = this.getMediaProperty(imageLink, 'desc');
			
			if (!desc)
				desc = '';
		}
		return desc;
	},
	
	/**
	 * Check if current link is part of a gallery
	 * @param obj imageLink
	 * @param string gType Gallery type to check for
	 * @return bool Whether link is part of a gallery
	 */
	inGallery: function(imageLink, gType) {
		var ret = false;
		var galls = {
			'wp': '.gallery-icon',
			'ng': '.ngg-gallery-thumbnail'
		};
		
		
		if ( typeof gType == 'undefined' || !(gType in galls) ) {
			gType = 'wp';
		}
		return ( ( $(imageLink).parent(galls[gType]).length > 0 ) ? true : false );
	},
	
	/**
	 * Retrieve source URI in link
	 * @param obj el
	 * @return string Source file URI
	 */
	getSourceFile: function(el) {
		var src = $(el).attr('href');
		var rel = $(el).attr('rel') || '';
		if (rel.length) {
			//Attachment source
			relSrc = this.getMediaProperty(el, 'source');
			//Set source using rel-derived value
			if ( $.type(relSrc) === 'string' && relSrc.length )
				src = relSrc;
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
				search = this.addPrefix('group') + gSt,
				idx,
				prefix = ' ';
			//Find group indicator
			idx = rel.indexOf(search);
			//Prefix with space to find whole word
			if (prefix != search.charAt(0) && idx > 0) {
				search = prefix + search;
				idx = rel.indexOf(search);
			}
			//Continue processing if value is found
			if (idx != -1) {
				//Extract group name
				gTmp = $.trim(rel.substring(idx).replace(search, ''));
				//Check if group defined
				if (gTmp.length > 1 && gTmp.indexOf(gEnd) > 0) {
					//Extract group name
					g = gTmp.substring(0, gTmp.indexOf(gEnd));
				}
			}
		}
		return g;
	},

	/**
	 * Preload requested image prior to displaying it in lightbox 
	 * @param int imageNum Index of image in image_array property
	 * @uses image_array to retrieve index at specified image
	 * @uses resizeImageContainer() to resize lightbox after image has loaded
	 */
	changeImage: function(imageNum) {
		this.active_image = imageNum;

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
		imgPreloader.src = this.image_array[this.active_image].link;
	},

	/**
	 * Resizes lightbox to fit image
	 * @param int imgWidth Image width in pixels
	 * @param int imgHeight Image height in pixels
	 */
	resizeImageContainer: function(w, h) {
		var d = this.getContainerSize(w, h);
		//Resize container
		this.get('container').animate({width: d.width, height: d.height}, this.resize_duration);
		//Resize overlay
		this.get('overlay').css('min-width', d.width);
		this.showImage();
	},
	
	/**
	 * Retrieve or build container size
	 * @param int w Container width to set
	 * @param int h Container height to set
	 * @return obj Container width (w)/height (h) values
	 */
	getContainerSize: function(w, h) {
		var b = this.options.border_size * 2;
		var c = {
			'width': w + b,
			'height': h + b
		}
		return c;
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
		//Caption
		if (this.options.enabled_caption) {
			this.get('dataCaption').text(this.image_array[this.active_image].title);
			this.get('dataCaption').show();
		} else {
			this.get('dataCaption').hide();
		}
		
		//Description
		this.get('dataDescription').html(this.image_array[this.active_image].desc);
		
		// if image is part of set display 'Image x of y' 
		if (this.hasImages()) {
			var num_display = this.options.strings.numDisplayPrefix + ' ' + (this.active_image + 1) + ' ' + this.options.strings.numDisplaySeparator + ' ' + this.image_array.length;
			if (this.options.show_group_name && this.group_name != '') {
				num_display += ' ' + this.options.strings.numDisplaySeparator + ' ' + this.group_name;
			}
			this.get('dataNumber')
				.text(num_display)
				.show();
		}
	
		this.get('details').width(this.get('slbContent').width() + (this.options.border_size * 2));
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
			if (this.enabled_slideshow) {
				this.get('navSlideControl').show();
				if (this.play_slides) {
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
	 * @return bool TRUE if slideshow is active, false otherwise
	 * @uses play_slides to check slideshow activation status
	 */
	isSlideShowActive: function() {
		return this.play_slides;
	},
	
	/**
	 * Start the slideshow
	 */
	startSlideShow: function() {
		this.play_slides = true;
		var t = this;
		clearInterval(this.slideshow_timer);
		this.slideshow_timer = setInterval(function() { t.showNext(); t.pauseSlideShow(); }, this.options.duration * 1000);
		this.get('navSlideControl').text(this.options.strings.stopSlideshow);
	},
	
	/**
	 * Stop the slideshow
	 */
	stopSlideShow: function() {
		this.play_slides = false;
		if (this.slideshow_timer) {
			clearInterval(this.slideshow_timer);
		}
		this.get('navSlideControl').text(this.options.strings.startSlideshow);
	},

	/**
	 * Toggles the slideshow status
	 */
	toggleSlideShow: function() {
		if (this.play_slides) {
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
		if (this.slideshow_timer) {
			clearInterval(this.slideshow_timer);
		}
	},
	
	/**
	 * Check if there is at least one image to display in the lightbox
	 * @return bool TRUE if at least one image is found
	 * @uses image_array to check for images
	 */
	hasImage: function() {
		return ( this.image_array.length > 0 );
	},
	
	/**
	 * Check if there are multiple images to display in the lightbox
	 * @return bool TRUE if there are multiple images
	 * @uses image_array to determine the number of images
	 */
	hasImages: function() {
		return ( this.image_array.length > 1 );
	},
	
	/**
	 * Check if the current image is the first image in the list
	 * @return bool TRUE if image is first
	 * @uses active_image to check index of current image
	 */
	isFirstImage: function() {
		return ( this.active_image == 0 );
	},
	
	/**
	 * Check if the current image is the last image in the list
	 * @return bool TRUE if image is last
	 * @uses active_image to check index of current image
	 * @uses image_array to compare current image to total number of images
	 */
	isLastImage: function() {
		return ( this.active_image == this.image_array.length - 1 );
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
				this.changeImage(this.active_image + 1);
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
			if (this.active_image == 0) {
				this.showLast();
			} else {
				this.changeImage(this.active_image - 1);
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
			this.changeImage(this.image_array.length - 1);
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
			if (this.hasImage() && this.options.enabled_slideshow) {
				this.toggleSlideShow();
			}
		}
	},

	/**
	 * Preloads images before/after current image
	 */
	preloadNeighborImages: function() {
		var nextImageID = this.image_array.length - 1 == this.active_image ? 0 : this.active_image + 1;
		nextImage = new Image();
		nextImage.src = this.image_array[nextImageID].link;

		var prevImageID = this.active_image == 0 ? this.image_array.length - 1 : this.active_image - 1;
		prevImage = new Image();
		prevImage.src = this.image_array[prevImageID].link;
	},

	/**
	 * Close the lightbox
	 */
	end: function() {
		this.disableKeyboardNav();
		this.pauseSlideShow();
		this.get('lightbox').hide();
		this.get('overlay').fadeOut(this.overlay_duration);
		this.showBadObjects();
	},
	
	/**
	 * Displays objects that may conflict with the lightbox
	 * @param bool show (optional) Whether or not to show objects (Default: TRUE)
	 */
	showBadObjects: function (show) {
		show = ( typeof(show) == 'undefined' ) ? true : !!show;
		var vis = (show) ? 'visible' : 'hidden';
		$(this.bad_objects.join(',')).css('visibility', vis);
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
		if (!this.options.validate_links)
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
		if (url in this.checked_urls) {
			proceed(this.checked_urls[url]);
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
		if (!(url in this.checked_urls))
			this.checked_urls[url] = res;
	}
}
})(jQuery);