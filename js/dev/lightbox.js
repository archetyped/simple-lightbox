// -----------------------------------------------------------------------------------
// 
// Simple Lightbox
// by Archetyped - http://archetyped.com/tools/simple-lightbox/
// Updated: 2010-06-11
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
//	The code inserts html at the bottom of the page that looks similar to this:
//
//	<div id="overlay"></div>
//	<div id="lightbox">
//		<div id="outerImageContainer">
//			<div id="imageContainer">
//				<img id="lightboxImage" />
//				<div id="hoverNav">
//					<a href="javascript:void(0);" id="prevLinkImg">&laquo; prev</a>
//					<a href="javascript:void(0);" id="nextLinkImg">next &raquo;</a>
//				</div>
//				<div id="loading">
//					<a href="javascript:void(0);" id="loadingLink">loading</a>
//				</div>
//			</div>
//		</div>
//		<div id="imageDataContainer">
//			<div id="imageData">
//				<div id="imageDetails">
//					<span id="caption"></span>
//					<span id="numberDisplay"></span>
//					<span id="detailsNav">
//						<a id="prevLinkDetails" href="javascript:void(0);">&laquo; prev</a>
//						<a id="nextLinkDetails" href="javascript:void(0);">next &raquo;</a>
//						<a id="slideShowControl" href="javascript:void(0);">stop slideshow</a>
//					</span>
//				</div>
//				<div id="close">
//					<a id="closeLink" href="javascript:void(0);">close</a>
//				</div>
//			</div>
//		</div>
//	</div>
//
// -----------------------------------------------------------------------------------

//
//	Lightbox Object
//
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
	
	//
	// initialize()
	// Constructor sets class properties and configuration options and
	// inserts html at the bottom of the page which is used to display the shadow 
	// overlay and the image container.
	//
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
			prefix : '', // ID prefix for all dynamically created html elements
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
			}
        }, options);
		
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
		
		var objImageDataContainer = $('<div/>', {
			'id': this.getID('imageDataContainer'),
			'class': this.getID('clearfix')
		}).click(function(ev) {ev.stopPropagation();});

		var objImageData = $('<div/>', {
			'id': this.getID('imageData')
		}).appendTo(objImageDataContainer);
	
		var objImageDetails = $('<div/>', {
			'id': this.getID('imageDetails')
		}).appendTo(objImageData);
	
		var objCaption = $('<span/>', {
			'id': this.getID('caption')
		}).appendTo(objImageDetails);
	
		var objNumberDisplay = $('<span/>', {
			'id': this.getID('numberDisplay')
		}).appendTo(objImageDetails);

		var objDetailsNav = $('<span/>', {
			'id': this.getID('detailsNav')
		}).appendTo(objImageDetails);

		var objPrevLink = $('<a/>', {
			'id': this.getID('prevLinkDetails'),
			'href': 'javascript:void(0);',
			'html': this.options.strings.prevLink
		}).appendTo(objDetailsNav)
		  .click(function() {t.showPrev()});
		
		var objNextLink = $('<a/>', {
			'id': this.getID('nextLinkDetails'),
			'href': 'javascript:void(0);',
			'html': this.options.strings.nextLink
		}).appendTo(objDetailsNav)
		  .click(function() {t.showNext()});

		var objSlideShowControl = $('<a/>', {
			'id': this.getID('slideShowControl'),
			'href': 'javascript:void(0);'
		}).appendTo(objDetailsNav)
		  .click(function() {t.toggleSlideShow()});

		var objClose = $('<div/>', {
			'id': this.getID('close')
		}).appendTo(objImageData);
	
		var objCloseLink = $('<a/>', {
			'id': this.getID('closeLink'),
			'href': 'javascript:void(0);',
			'html': this.options.strings.closeLink
		}).appendTo(objClose)
		  .click(function() {t.end()});

		if (this.options.imageDataLocation == 'north') {
			$(objLightbox).append(objImageDataContainer);
		}
	
		var objOuterImageContainer = $('<div/>', {
			'id': this.getID('outerImageContainer')
		}).appendTo(objLightbox)
		  .click(function(ev) {ev.stopPropagation();});

		var objImageContainer = $('<div/>', {
			'id': this.getID('imageContainer')
		}).appendTo(objOuterImageContainer);
	
		var objLightboxImage = $('<img/>', {
			'id': this.getID('lightboxImage')
		}).appendTo(objImageContainer);
	
		var objHoverNav = $('<div/>', {
			'id': this.getID('hoverNav')
		}).appendTo(objImageContainer);
	
		var objPrevLinkImg = $('<a/>', {
			'id': this.getID('prevLinkImg'),
			'href': 'javascript:void(0);'
		}).appendTo(objHoverNav)
		  .click(function() {t.showPrev()});
		
		var objNextLinkImg = $('<a/>', {
			'id': this.getID('nextLinkImg'),
			'href': 'javascript:void(0);'
		}).appendTo(objHoverNav)
		  .click(function() {t.showNext()});
	
		var objLoading = $('<div/>', {
			'id': this.getID('loading')
		}).appendTo(objImageContainer);
	
		var objLoadingLink = $('<a/>', {
			'id': this.getID('loadingLink'),
			'href': 'javascript:void(0);',
			'html': this.options.strings.loadingMsg
		}).appendTo(objLoading)
		  .click(function() {t.end()});
		
		if (this.options.imageDataLocation != 'north') {
			$(objLightbox).append(objImageDataContainer);
		}
		
		if (this.options.initImage != '') {
			this.start($(this.options.initImage));
		}
	},
	
	//
	//	updateImageList()
	//	Loops through specific tags within 'container' looking for 
	// 'lightbox' references and applies onclick events to them.
	//
	updateImageList: function() {
		var el, els, rel;
		var t = this;
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

	//
	//	start()
	//	Display overlay and lightbox. If image is part of a set, add siblings to imageArray.
	//
	start: function(imageLink) {
		imageLink = $(imageLink);
		this.hideBadObjects();

		// stretch overlay to fill page and fade in
		var pageSize = this.getPageSize();
		this.getEl('overlay')
			.height(pageSize.pageHeight)
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
		var pageScroll = this.getPageScroll();
		var lightboxTop = pageScroll.y + (pageSize.winHeight / 15);

		this.getEl('lightbox').css('top', lightboxTop + 'px').show();
		this.changeImage(this.startImage);
	},

	//
	//	changeImage()
	//	Hide most elements and preload image in preparation for resizing image container.
	//
	changeImage: function(imageNum) {
		this.activeImage = imageNum;

		this.disableKeyboardNav();
		this.pauseSlideShow();

		// hide elements during transition
		this.getEl('loading').show();
		this.getEl('lightboxImage').hide();
		this.getEl('hoverNav').hide();
		this.getEl('imageDataContainer').hide();
		this.getEl('numberDisplay').hide();
		this.getEl('detailsNav').hide();
		var imgPreloader = new Image();
		var t = this;
		// once image is preloaded, resize image container
		$(imgPreloader).bind('load', function() {
			t.getEl('lightboxImage').attr('src', imgPreloader.src);
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

	//
	//	resizeImageContainer()
	//
	resizeImageContainer: function(imgWidth, imgHeight) {
		// get current height and width
		var el = this.getEl('outerImageContainer');
		var borderSize = this.options.borderSize * 2;
		
		this.getEl('outerImageContainer').animate({width: imgWidth + borderSize, height: imgHeight + borderSize}, this.resizeDuration)

		this.getEl('prevLinkImg').height(imgHeight);
		this.getEl('nextLinkImg').height(imgHeight);
		this.getEl('imageDataContainer').width(imgWidth + borderSize)

		this.showImage();
	},
	
	//
	//	showImage()
	//	Display image and begin preloading neighbors.
	//
	showImage: function() {
		this.getEl('loading').hide();
		var t = this;
		this.getEl('lightboxImage').fadeIn(500, function() { t.updateDetails(); });
		this.preloadNeighborImages();
	},

	//
	//	updateDetails()
	//	Display caption, image number, and bottom nav.
	//
	updateDetails: function() {
		this.getEl('caption').text(this.imageArray[this.activeImage].title);
		this.getEl('caption').show();
		
		// if image is part of set display 'Image x of y' 
		if (this.hasImages()) {
			var num_display = this.options.strings.numDisplayPrefix + ' ' + eval(this.activeImage + 1) + ' ' + this.options.strings.numDisplaySeparator + ' ' + this.imageArray.length;
			if (this.options.showGroupName && this.groupName != '') {
				num_display += ' ' + this.options.strings.numDisplaySeparator + ' ' + this.groupName;
			}
			this.getEl('numberDisplay')
				.text(num_display)
				.show();
			if (!this.enableSlideshow) {
				this.getEl('slideShowControl').hide();
			}
			this.getEl('detailsNav').show();
		}
		
		var t = this;
		this.getEl('imageDataContainer').animate({height: 'toggle', opacity: 'toggle'}, 650, function() {t.updateNav();});
	},
	
	//
	//	updateNav()
	//	Display appropriate previous and next hover navigation.
	//
	updateNav: function() {
		if (this.hasImages()) {
			this.getEl('hoverNav').show();
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
	
	isSlideShowActive: function() {
		return this.playSlides;
	},
	
	//
	//	startSlideShow()
	//	Starts the slide show
	//
	startSlideShow: function() {
		this.playSlides = true;
		var t = this;
		this.slideShowTimer = setInterval(function() { t.showNext(); t.pauseSlideShow(); }, this.options.slideTime * 1000);
		this.getEl('slideShowControl').text(this.options.strings.stopSlideshow);
	},
	
	//
	//	stopSlideShow()
	//	Stops the slide show
	//
	stopSlideShow: function() {
		this.playSlides = false;
		if (this.slideShowTimer) {
			clearInterval(this.slideShowTimer);
		}
		this.getEl('slideShowControl').text(this.options.strings.startSlideshow);
	},

	//
	//	stopSlideShow()
	//	Stops the slide show
	//
	toggleSlideShow: function() {
		if (this.playSlides) {
			this.stopSlideShow();
		}else{
			this.startSlideShow();
		}
	},

	//
	//	pauseSlideShow()
	//	Pauses the slide show (doesn't change the value of this.playSlides)
	//
	pauseSlideShow: function() {
		if (this.slideShowTimer) {
			clearInterval(this.slideShowTimer);
		}
	},
	
	hasImage: function() {
		return ( this.imageArray.length > 0 );
	},
	
	hasImages: function() {
		return ( this.imageArray.length > 1 );
	},
	
	isFirstImage: function() {
		return ( this.activeImage == 0 );
	},
	
	isLastImage: function() {
		return ( this.activeImage == this.imageArray.length - 1 );
	},
	
	//
	//	showNext()
	//	Display the next image in a group
	//
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

	//
	//	showPrev()
	//	Display the next image in a group
	//
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
	
	//
	//	showFirst()
	//	Display the first image in a group
	//
	showFirst : function() {
		if (this.hasImages()) {
			this.changeImage(0);
		}
	},

	//
	//	showFirst()
	//	Display the first image in a group
	//
	showLast : function() {
		if (this.hasImages()) {
			this.changeImage(this.imageArray.length - 1);
		}
	},

	//
	//	enableKeyboardNav()
	//
	enableKeyboardNav: function() {
		document.onkeydown = this.keyboardAction; 
	},

	//
	//	disableKeyboardNav()
	//
	disableKeyboardNav: function() {
		document.onkeydown = '';
	},

	//
	//	keyboardAction()
	//
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

	//
	//	preloadNeighborImages()
	//	Preload previous and next images.
	//
	preloadNeighborImages: function() {
		var nextImageID = this.imageArray.length - 1 == this.activeImage ? 0 : this.activeImage + 1;
		nextImage = new Image();
		nextImage.src = this.imageArray[nextImageID].link;

		var prevImageID = this.activeImage == 0 ? this.imageArray.length - 1 : this.activeImage - 1;
		prevImage = new Image();
		prevImage.src = this.imageArray[prevImageID].link;
	},

	//
	//	end()
	//
	end: function() {
		this.disableKeyboardNav();
		this.pauseSlideShow();
		this.getEl('lightbox').hide();
		this.getEl('overlay').fadeOut(this.overlayDuration);
		this.showBadObjects();
	},
	
	//
	//	showBadObjects()
	//
	showBadObjects: function (show) {
		show = ( typeof(show) == 'undefined' ) ? true : !!show;
		var vis = (show) ? 'visible' : 'hidden';
		$(this.badObjects.join(',')).css('visibility', vis);
	},
	
	//
	//	hideBadObjects()
	//
	hideBadObjects: function () {
		this.showBadObjects(false);
	},
		
	//
	// pause(numberMillis)
	// Pauses code execution for specified time. Uses busy code, not good.
	// Code from http://www.faqts.com/knowledge_base/view.phtml/aid/1602
	//
	pause: function(numberMillis) {
		var now = new Date();
		var exitTime = now.getTime() + numberMillis;
		while(true) {
			now = new Date();
			if (now.getTime() > exitTime)
				return;
		}
	},

	//
	// getPageScroll()
	// Returns array with x,y page scroll values.
	// Core code from - quirksmode.org
	//
	getPageScroll: function() {
		var x,y;
		if (self.pageYOffset) {
			x = self.pageXOffset;
			y = self.pageYOffset;
		} else if (document.documentElement && document.documentElement.scrollTop) {	 // Explorer 6 Strict
			x = document.documentElement.scrollLeft;
			y = document.documentElement.scrollTop;
		} else if (document.body) {// all other Explorers
			x = document.body.scrollLeft;
			y = document.body.scrollTop;
		}
		return {x:x,y:y};
	},

	//
	// getPageSize()
	// Returns array with page width, height and window width, height
	// Core code from - quirksmode.org
	// Edit for Firefox by pHaez
	//
	getPageSize: function() {
		var scrollX,scrollY,windowX,windowY,pageX,pageY;
		if (window.innerHeight && window.scrollMaxY) {	
			scrollX = document.body.scrollWidth;
			scrollY = window.innerHeight + window.scrollMaxY;
		} else if (document.body.scrollHeight > document.body.offsetHeight) { // all but Explorer Mac
			scrollX = document.body.scrollWidth;
			scrollY = document.body.scrollHeight;
		} else { // Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari
			scrollX = document.body.offsetWidth;
			scrollY = document.body.offsetHeight;
		}
		
		if (self.innerHeight) {	// all except Explorer
			windowX = self.innerWidth;
			windowY = self.innerHeight;
		} else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
			windowX = document.documentElement.clientWidth;
			windowY = document.documentElement.clientHeight;
		} else if (document.body) { // other Explorers
			windowX = document.body.clientWidth;
			windowY = document.body.clientHeight;
		}	
		
		pageY = (scrollY < windowY) ? windowY : scrollY; // for small pages with total height less then height of the viewport
		pageX = (scrollX < windowX) ? windowX : scrollX; // for small pages with total width less then width of the viewport
	
		return {pageWidth:pageX,pageHeight:pageY,winWidth:windowX,winHeight:windowY};
	},

	//
	// getID()
	// Returns formatted Lightbox element ID
	//
	getID: function(id) {
		return this.options.prefix+id;
	},
	
	getSel: function(id) {
		return '#' + this.getID(id);
	},
	
	getEl: function(id) {
		return $(this.getSel(id));
	}
}
})(jQuery);