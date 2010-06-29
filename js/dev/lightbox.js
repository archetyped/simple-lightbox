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

var Lightbox = {	
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
		if (!document.getElementsByTagName){ return; }
		
		this.options = $H({
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
			resizeSpeed : 7, // controls the speed of the image resizing (1=slowest and 10=fastest)
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
        }).merge(options);
		
		if(this.options.animate){
			this.overlayDuration = Math.max(this.options.overlayDuration,0);
			this.options.resizeSpeed = Math.max(Math.min(this.options.resizeSpeed,10),1);
			this.resizeDuration = (11 - this.options.resizeSpeed) * 0.15;
		}else{
			this.overlayDuration = 0;
			this.resizeDuration = 0;
		}
		
		this.enableSlideshow = this.options.enableSlideshow;
		this.overlayOpacity = Math.max(Math.min(this.options.overlayOpacity,1),0);
		this.playSlides = this.options.autoPlay;
		this.container = $(this.options.containerID);
		this.relAttribute = this.options.relAttribute;
		this.updateImageList();
		
		var objBody = this.container != document ? this.container : document.getElementsByTagName('body').item(0);
		
		var objOverlay = document.createElement('div');
		objOverlay.setAttribute('id',this.getID('overlay'));
		objOverlay.style.display = 'none';
		objBody.appendChild(objOverlay);
		Event.observe(objOverlay,'click',this.end.bindAsEventListener(this));
		
		var objLightbox = document.createElement('div');
		objLightbox.setAttribute('id',this.getID('lightbox'));
		objLightbox.style.display = 'none';
		objBody.appendChild(objLightbox);
		
		var objImageDataContainer = document.createElement('div');
		objImageDataContainer.setAttribute('id',this.getID('imageDataContainer'));
		objImageDataContainer.className = this.getID('clearfix');

		var objImageData = document.createElement('div');
		objImageData.setAttribute('id',this.getID('imageData'));
		objImageDataContainer.appendChild(objImageData);
	
		var objImageDetails = document.createElement('div');
		objImageDetails.setAttribute('id',this.getID('imageDetails'));
		objImageData.appendChild(objImageDetails);
	
		var objCaption = document.createElement('span');
		objCaption.setAttribute('id',this.getID('caption'));
		objImageDetails.appendChild(objCaption);
	
		var objNumberDisplay = document.createElement('span');
		objNumberDisplay.setAttribute('id',this.getID('numberDisplay'));
		objImageDetails.appendChild(objNumberDisplay);

		var objDetailsNav = document.createElement('span');
		objDetailsNav.setAttribute('id',this.getID('detailsNav'));
		objImageDetails.appendChild(objDetailsNav);

		var objPrevLink = document.createElement('a');
		objPrevLink.setAttribute('id',this.getID('prevLinkDetails'));
		objPrevLink.setAttribute('href','javascript:void(0);');
		objPrevLink.innerHTML = this.options.strings.prevLink;
		objDetailsNav.appendChild(objPrevLink);
		Event.observe(objPrevLink,'click',this.showPrev.bindAsEventListener(this));
		
		var objNextLink = document.createElement('a');
		objNextLink.setAttribute('id',this.getID('nextLinkDetails'));
		objNextLink.setAttribute('href','javascript:void(0);');
		objNextLink.innerHTML = this.options.strings.nextLink;
		objDetailsNav.appendChild(objNextLink);
		Event.observe(objNextLink,'click',this.showNext.bindAsEventListener(this));

		var objSlideShowControl = document.createElement('a');
		objSlideShowControl.setAttribute('id',this.getID('slideShowControl'));
		objSlideShowControl.setAttribute('href','javascript:void(0);');
		objDetailsNav.appendChild(objSlideShowControl);
		Event.observe(objSlideShowControl,'click',this.toggleSlideShow.bindAsEventListener(this));

		var objClose = document.createElement('div');
		objClose.setAttribute('id',this.getID('close'));
		objImageData.appendChild(objClose);
	
		var objCloseLink = document.createElement('a');
		objCloseLink.setAttribute('id',this.getID('closeLink'));
		objCloseLink.setAttribute('href','javascript:void(0);');
		objCloseLink.innerHTML = this.options.strings.closeLink;
		objClose.appendChild(objCloseLink);	
		Event.observe(objCloseLink,'click',this.end.bindAsEventListener(this));

		if(this.options.imageDataLocation == 'north'){
			objLightbox.appendChild(objImageDataContainer);
		}
	
		var objOuterImageContainer = document.createElement('div');
		objOuterImageContainer.setAttribute('id',this.getID('outerImageContainer'));
		objLightbox.appendChild(objOuterImageContainer);

		var objImageContainer = document.createElement('div');
		objImageContainer.setAttribute('id',this.getID('imageContainer'));
		objOuterImageContainer.appendChild(objImageContainer);
	
		var objLightboxImage = document.createElement('img');
		objLightboxImage.setAttribute('id',this.getID('lightboxImage'));
		objImageContainer.appendChild(objLightboxImage);
	
		var objHoverNav = document.createElement('div');
		objHoverNav.setAttribute('id',this.getID('hoverNav'));
		objImageContainer.appendChild(objHoverNav);
	
		var objPrevLinkImg = document.createElement('a');
		objPrevLinkImg.setAttribute('id',this.getID('prevLinkImg'));
		objPrevLinkImg.setAttribute('href','javascript:void(0);');
		objHoverNav.appendChild(objPrevLinkImg);
		Event.observe(objPrevLinkImg,'click',this.showPrev.bindAsEventListener(this));
		
		var objNextLinkImg = document.createElement('a');
		objNextLinkImg.setAttribute('id',this.getID('nextLinkImg'));
		objNextLinkImg.setAttribute('href','javascript:void(0);');
		objHoverNav.appendChild(objNextLinkImg);
		Event.observe(objNextLinkImg,'click',this.showNext.bindAsEventListener(this));
	
		var objLoading = document.createElement('div');
		objLoading.setAttribute('id',this.getID('loading'));
		objImageContainer.appendChild(objLoading);
	
		var objLoadingLink = document.createElement('a');
		objLoadingLink.setAttribute('id',this.getID('loadingLink'));
		objLoadingLink.setAttribute('href','javascript:void(0);');
		objLoadingLink.innerHTML = this.options.strings.loadingMsg;
		objLoading.appendChild(objLoadingLink);
		Event.observe(objLoadingLink,'click',this.end.bindAsEventListener(this));
		
		if(this.options.imageDataLocation != 'north'){
			objLightbox.appendChild(objImageDataContainer);
		}
		
		if(this.options.initImage != ''){
			this.start($(this.options.initImage));
		}
	},
	
	//
	//	updateImageList()
	//	Loops through specific tags within 'container' looking for 
	// 'lightbox' references and applies onclick events to them.
	//
	updateImageList: function(){
		var el, els, rel;
		for(var i=0; i < this.refTags.length; i++){
			els = this.container.getElementsByTagName(this.refTags[i]);
			for(var j=0; j < els.length; j++){
				el = els[j];
				rel = String(el.getAttribute('rel'));
				if (el.getAttribute('href') && (rel.toLowerCase().match(this.relAttribute))){
					el.onclick = function(){Lightbox.start(this); return false;}
				}
			}
		}
	},
	
	getCaption: function(imageLink) {
			var caption = imageLink.title || '';
			if ( caption == '' ) {
				var inner = $(imageLink).getElementsBySelector('img').first();
				if ( inner )
					caption = inner.getAttribute('title') || inner.getAttribute('alt');
				if ( !caption )
					caption = imageLink.innerHTML.stripTags() || imageLink.href || '';
			}
			return caption;
	},

	//
	//	start()
	//	Display overlay and lightbox. If image is part of a set, add siblings to imageArray.
	//
	start: function(imageLink) {	

		this.hideBadObjects();

		// stretch overlay to fill page and fade in
		var pageSize = this.getPageSize();
		$(this.getID('overlay')).setStyle({height:pageSize.pageHeight+'px'});
		new Effect.Appear(this.getID('overlay'), { duration: this.overlayDuration, from: 0, to: this.overlayOpacity });

		this.imageArray = [];
		this.groupName = null;
		
		var rel = imageLink.getAttribute('rel');
		var imageTitle = '';
		
		// if image is NOT part of a group..
		if(rel == this.relAttribute){
			// add single image to imageArray
			imageTitle = this.getCaption(imageLink);
			this.imageArray.push({'link':imageLink.getAttribute('href'), 'title':imageTitle});			
			this.startImage = 0;
		} else {
			// if image is part of a group..
			var els = this.container.getElementsByTagName(imageLink.tagName);
			// loop through anchors, find other images in group, and add them to imageArray
			for (var i=0; i<els.length; i++){
				var el = els[i];
				if (el.getAttribute('href') && (el.getAttribute('rel') == rel)){
					imageTitle = this.getCaption(el);
					this.imageArray.push({'link':el.getAttribute('href'),'title':imageTitle});
					if(el == imageLink){
						this.startImage = this.imageArray.length-1;
					}
				}
			}
			// get group name
			this.groupName = rel.substring(this.relAttribute.length+1,rel.length-1);
		}

		// calculate top offset for the lightbox and display 
		var pageScroll = this.getPageScroll();
		var lightboxTop = pageScroll.y + (pageSize.winHeight / 15);

		$(this.getID('lightbox')).setStyle({top:lightboxTop+'px'}).show();
		this.changeImage(this.startImage);
	},

	//
	//	changeImage()
	//	Hide most elements and preload image in preparation for resizing image container.
	//
	changeImage: function(imageNum){	
		this.activeImage = imageNum;

		this.disableKeyboardNav();
		this.pauseSlideShow();

		// hide elements during transition
		$(this.getID('loading')).show();
		$(this.getID('lightboxImage')).hide();
		$(this.getID('hoverNav')).hide();
		$(this.getID('imageDataContainer')).hide();
		$(this.getID('numberDisplay')).hide();
		$(this.getID('detailsNav')).hide();
		
		var imgPreloader = new Image();
		
		// once image is preloaded, resize image container
		imgPreloader.onload=function(){
			$(Lightbox.getID('lightboxImage')).src = imgPreloader.src;
			Lightbox.resizeImageContainer(imgPreloader.width,imgPreloader.height);
		}
		imgPreloader.src = this.imageArray[this.activeImage].link;
		
		if(this.options.googleAnalytics){
			urchinTracker(this.imageArray[this.activeImage].link);
		}
	},

	//
	//	resizeImageContainer()
	//
	resizeImageContainer: function(imgWidth,imgHeight) {
		// get current height and width
		var cDims = $(this.getID('outerImageContainer')).getDimensions();

		// scalars based on change from old to new
		var xScale = ((imgWidth  + (this.options.borderSize * 2)) / cDims.width) * 100;
		var yScale = ((imgHeight  + (this.options.borderSize * 2)) / cDims.height) * 100;

		// calculate size difference between new and old image, and resize if necessary
		var wDiff = (cDims.width - this.options.borderSize * 2) - imgWidth;
		var hDiff = (cDims.height - this.options.borderSize * 2) - imgHeight;

		if(!( hDiff == 0)){ new Effect.Scale(this.getID('outerImageContainer'), yScale, {scaleX: false, duration: this.resizeDuration, queue: 'front'}); }
		if(!( wDiff == 0)){ new Effect.Scale(this.getID('outerImageContainer'), xScale, {scaleY: false, delay: this.resizeDuration, duration: this.resizeDuration}); }

		// if new and old image are same size and no scaling transition is necessary, 
		// do a quick pause to prevent image flicker.
		if((hDiff == 0) && (wDiff == 0)){
			if(navigator.appVersion.indexOf('MSIE')!=-1){ this.pause(250); } else { this.pause(100);} 
		}

		$(this.getID('prevLinkImg')).setStyle({height:imgHeight+'px'});
		$(this.getID('nextLinkImg')).setStyle({height:imgHeight+'px'});
		$(this.getID('imageDataContainer')).setStyle({width:(imgWidth+(this.options.borderSize * 2))+'px'});

		this.showImage();
	},
	
	//
	//	showImage()
	//	Display image and begin preloading neighbors.
	//
	showImage: function(){
		$(this.getID('loading')).hide();
		new Effect.Appear(this.getID('lightboxImage'), { duration: 0.5, queue: 'end', afterFinish: function(){	Lightbox.updateDetails(); } });
		this.preloadNeighborImages();
	},

	//
	//	updateDetails()
	//	Display caption, image number, and bottom nav.
	//
	updateDetails: function() {
		$(this.getID('caption')).show();
		$(this.getID('caption')).update(this.imageArray[this.activeImage].title);
		
		// if image is part of set display 'Image x of y' 
		if(this.imageArray.length > 1){
			var num_display = this.options.strings.numDisplayPrefix + ' ' + eval(this.activeImage + 1) + ' ' + this.options.strings.numDisplaySeparator + ' ' + this.imageArray.length;
			if(this.options.showGroupName && this.groupName != ''){
				num_display += ' '+this.options.strings.numDisplaySeparator+' '+this.groupName;
			}
			$(this.getID('numberDisplay')).update(num_display).show();
			if(!this.enableSlideshow){
				$(this.getID('slideShowControl')).hide();
			}
			$(this.getID('detailsNav')).show();
		}
		
		new Effect.Parallel(
			[ new Effect.SlideDown( this.getID('imageDataContainer'), { sync: true }), 
			  new Effect.Appear(this.getID('imageDataContainer'), { sync: true }) ], 
			{ duration:.65, afterFinish: function() { Lightbox.updateNav();} } 
		);
	},
	
	//
	//	updateNav()
	//	Display appropriate previous and next hover navigation.
	//
	updateNav: function() {
		if(this.imageArray.length > 1){
			$(this.getID('hoverNav')).show();
			if(this.enableSlideshow){
				if(this.playSlides){
					this.startSlideShow();
				} else {
					this.stopSlideShow();
				}
			}
		}
		this.enableKeyboardNav();
	},
	//
	//	startSlideShow()
	//	Starts the slide show
	//
	startSlideShow: function(){
		this.playSlides = true;
		this.slideShowTimer = new PeriodicalExecuter(function(pe){ Lightbox.showNext(); pe.stop(); },this.options.slideTime);
		$(this.getID('slideShowControl')).update(this.options.strings.stopSlideshow);
	},
	
	//
	//	stopSlideShow()
	//	Stops the slide show
	//
	stopSlideShow: function(){
		this.playSlides = false;
		if(this.slideShowTimer){
			this.slideShowTimer.stop();
		}
		$(this.getID('slideShowControl')).update(this.options.strings.startSlideshow);
	},

	//
	//	stopSlideShow()
	//	Stops the slide show
	//
	toggleSlideShow: function(){
		if(this.playSlides){
			this.stopSlideShow();
		}else{
			this.startSlideShow();
		}
	},

	//
	//	pauseSlideShow()
	//	Pauses the slide show (doesn't change the value of this.playSlides)
	//
	pauseSlideShow: function(){
		if(this.slideShowTimer){
			this.slideShowTimer.stop();
		}
	},
	
	//
	//	showNext()
	//	Display the next image in a group
	//
	showNext : function(){
		if(this.imageArray.length > 1){
			if(!this.options.loop && ((this.activeImage == this.imageArray.length - 1 && this.startImage == 0) || (this.activeImage+1 == this.startImage))){
				return this.end();
			}
			if(this.activeImage == this.imageArray.length - 1){
				this.changeImage(0);
			}else{
				this.changeImage(this.activeImage+1);
			}
		}
	},

	//
	//	showPrev()
	//	Display the next image in a group
	//
	showPrev : function(){
		if(this.imageArray.length > 1){
			if(this.activeImage == 0){
				this.changeImage(this.imageArray.length - 1);
			}else{
				this.changeImage(this.activeImage-1);
			}
		}
	},
	
	//
	//	showFirst()
	//	Display the first image in a group
	//
	showFirst : function(){
		if(this.imageArray.length > 1){
			this.changeImage(0);
		}
	},

	//
	//	showFirst()
	//	Display the first image in a group
	//
	showLast : function(){
		if(this.imageArray.length > 1){
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
		
		if(key == 'x' || key == 'o' || key == 'c'){ // close lightbox
			Lightbox.end();
		} else if(key == 'p' || key == '%'){ // display previous image
			Lightbox.showPrev();
		} else if(key == 'n' || key =='\''){ // display next image
			Lightbox.showNext();
		} else if(key == 'f'){ // display first image
			Lightbox.showFirst();
		} else if(key == 'l'){ // display last image
			Lightbox.showLast();
		} else if(key == 's'){ // toggle slideshow
			if(Lightbox.imageArray.length > 0 && Lightbox.options.enableSlideshow){
				Lightbox.toggleSlideShow();
			}
		}
	},

	//
	//	preloadNeighborImages()
	//	Preload previous and next images.
	//
	preloadNeighborImages: function(){
		var nextImageID = this.imageArray.length - 1 == this.activeImage ? 0 : this.activeImage + 1;
		nextImage = new Image();
		nextImage.src = this.imageArray[nextImageID].link

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
		$(this.getID('lightbox')).hide();
		new Effect.Fade(this.getID('overlay'), { duration:this.overlayDuration });
		this.showBadObjects();
	},
	
	//
	//	showBadObjects()
	//
	showBadObjects: function (){
		var els;
		var tags = Lightbox.badObjects;
		for(var i=0; i<tags.length; i++){
			els = document.getElementsByTagName(tags[i]);
			for(var j=0; j<els.length; j++){
				$(els[j]).setStyle({visibility:'visible'});
			}
		}
	},
	
	//
	//	hideBadObjects()
	//
	hideBadObjects: function (){
		var els;
		var tags = Lightbox.badObjects;
		for(var i=0; i<tags.length; i++){
			els = document.getElementsByTagName(tags[i]);
			for(var j=0; j<els.length; j++){
				$(els[j]).setStyle({visibility:'hidden'});
			}
		}
	},
		
	//
	// pause(numberMillis)
	// Pauses code execution for specified time. Uses busy code, not good.
	// Code from http://www.faqts.com/knowledge_base/view.phtml/aid/1602
	//
	pause: function(numberMillis) {
		var now = new Date();
		var exitTime = now.getTime() + numberMillis;
		while(true){
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
	getPageScroll: function(){
		var x,y;
		if (self.pageYOffset) {
			x = self.pageXOffset;
			y = self.pageYOffset;
		} else if (document.documentElement && document.documentElement.scrollTop){	 // Explorer 6 Strict
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
	getPageSize: function(){
		var scrollX,scrollY,windowX,windowY,pageX,pageY;
		if (window.innerHeight && window.scrollMaxY) {	
			scrollX = document.body.scrollWidth;
			scrollY = window.innerHeight + window.scrollMaxY;
		} else if (document.body.scrollHeight > document.body.offsetHeight){ // all but Explorer Mac
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
	getID: function(id){
		return this.options.prefix+id;
	}
}

// -----------------------------------------------------------------------------------