/**
 * Simple Lightbox
 * @author Archetyped (http://archetyped.com/tools/simple-lightbox/)
 * 
 * Inspiration/History
 * > 2010: Originally based on Lightbox Slideshow v1.1
 *   > by Justin Barkhuff (http://www.justinbarkhuff.com/lab/lightbox_slideshow/)
 *   > Largely based on Lightbox v2.02
 *     > by Lokesh Dhakar - http://huddletogether.com/projects/lightbox2/
 * > 2011-01-12: Rebuilt as jQuery-compatible codebase
 * 
 */


(function($) {
SLB = {
	/* Properties */
	
	//Core
	prefix : 'slb',
	options : {},
	//Activation
	trigger : null,
	urls_checked : {},
	//Items
	items : [],
	item_curr : null,
	item_init : null,
	group : null,
	media : {},
	//Content
	content: {
		caption_enabled: true,
		caption_src: true,
		desc_enabled: true,
		labels : {
			closeLink : 'close',
			loadingMsg : 'loading',
			nextLink : 'next &raquo;',
			prevLink : '&laquo; prev',
			startSlideshow : 'start slideshow',
			stopSlideshow : 'stop slideshow',
			numDisplayPrefix : 'Image',
			numDisplaySeparator : 'of'
		}
	},
	//Layout
	container : document,
	masks : ['select','object','embed','iframe'],
	layout: {
		template: '',
		parsed: '',
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
		}
	},
	//Slideshow
	slideshow: {
		active: null,
		enabled: true,
		loop: true,
		duration: 4,
		timer: null
	},
	//Animation
	anim: {
		active: true,
		overlay_duration: 0,
		overlay_opacity: .8,
		resize_duration: 0
	},
	
	/* Initialization */
	
	/**
	 * Initialize lightbox instance
	 * @param object options Instance options
	 */
	init: function(options) {
		//Options
		this.options = $.extend(true, {
			//Core
			prefix: null,
			
			//Activation
			trigger : null,
			validateLinks : false,
			
			//Content
			captionEnabled: true,
			captionSrc : true,
			descEnabled: true,
			labels : {},
			
			//Layout
			layout : null,
			placeholders : {},
			
			//Animation
			animate : true,
			overlayDuration : .2,
			overlayOpacity : .8,
			resizeSpeed : 400,
			
			//Slideshow
			autoPlay : true,
			enableSlideshow : true,
			loop : true,
			slideTime : 4
        }, options);
		
		//Stop if no layout is defined
		if (!this.options.layout || this.options.layout.toString().length == 0)
			this.end();
		
		/* Setup Properties */
		
		//Prefix
		if ( $.type(this.options.prefix) == 'string' && this.options.prefix.length > 0 ) {
			this.prefix = this.options.prefix;
		}
		
		//Activation
		this.trigger = ( $.isArray(this.options.trigger) ) ? this.options.trigger : [this.prefix];
		
		//Content
		$.extend(true, this.content, {
			caption_enabled: !!this.options.captionEnabled,
			caption_src: !!this.options.captionSrc,
			desc_enabled: !!this.options.descEnabled,
			labels: ( $.isPlainObject(this.options.labels) ) ? this.options.labels : {}
		});
		
		//Layout
		$.extend(true, this.layout, {
			template: this.options.layout,
			placeholders: this.options.placeholders
		});
		
		//Animation
		$.extend(this.anim, {
			overlay_opacity: Math.max(Math.min(this.options.overlayOpacity,1),0)
		});
		if ( this.options.animate ) {
			$.extend(this.anim, {
				active: true,
				overlay_duration: Math.max(this.options.overlayDuration,0),
				resize_duration: this.options.resizeSpeed
			});
		} else {
			$.extend(this.anim, {
				active: false,
				overlay_duration: 0,
				resize_duration: 0
			});
		}
		
		//Slideshow
		$.extend(this.slideshow, {
			play: !!this.options.autoPlay,
			active: !!this.options.autoPlay,
			enabled: !!this.options.enableSlideshow,
			loop: ( !!this.options.enableSlideshow && !!this.options.loop ),
			duration: ( $.isNumeric(this.options.slideTime) ) ? parseInt(this.options.slideTime) : 0
		});
		
		/* Init Layout */

		var t = this;
		var body = $('body');

		//Overlay
		$('<div/>', {
			'id': this.getID('overlay'),
			'css': {'display': 'none'}
		}).appendTo(body)
		  .click(function() {t.end();});
		
		//Viewer
		var viewer = $('<div/>', {
			'id': this.getID('viewer'),
			'css': {'display': 'none'}
		}).appendTo(body)
		  .click(function() {t.end();});
		
		//Build layout from template
		this.layout.parsed = this.getLayout();
		
		//Insert layout into viewer
		$(this.layout.parsed).appendTo(viewer);
		
		//Set UI
		this.initUI();
		
		//Add events
		this.initEvents();
	},
	
	/**
	 * Set localized values for UI elements
	 */
	initUI: function() {
		this.get('slbClose').html(this.getLabel('closeLink'));
		this.get('navNext').html(this.getLabel('nextLink'));
		this.get('navPrev').html(this.getLabel('prevLink'));
		this.get('navSlideControl').html( this.getLabel((this.slideshowActive()) ? 'stopSlideshow' : 'startSlideshow') );
	},
	
	/**
	 * Add events to various UI elements
	 */
	initEvents: function() {
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
			setTimeout(function() {t.get('navPrev').click(clickP);}, delay);
			t.navPrev();
			return false;
		};
		this.get('navPrev').click(function(){
			return clickP();
		});
		
		var clickN = function() {
			//Handle double clicks
			t.get('navNext').unbind('click').click(false);
			setTimeout(function() {t.get('navNext').click(clickN);}, delay);
			t.navNext();
			return false;
		};
		this.get('navNext').click(function() {
			return clickN();
		});
		
		this.get('navSlideControl').click(function() {
			t.slideshowToggle();
			return false;
		});
		this.get('slbClose').click(function() {
			t.end();
			return false;
		});
		
		//Handle links on page
		this.initLinks();
	},
	
	/**
	 * Finds all compatible image links on page
	 * @return void
	 */
	initLinks: function() {
		var ph = '{relattr}', t = this;
		var sel = [], selBase = 'a[href][rel*="' + ph + '"]:not([rel~="' + this.addPrefix('off') + '"])';
		
		//Click event handler
		var handler = function() {
			t.view(this);
			return false;
		};
		
		//Build selector
		for (var x = 0; x < this.trigger.length; x++) {
			sel.push(selBase.replace(ph, this.trigger[x]));
		}
		sel = sel.join(',');
		//Add event handler to links
		$(sel, $(this.container)).live('click', handler);
	},
	
	/* Display */
	
	/**
	 * Display viewer
	 * If item is part of a group, add other items in group
	 * @param node item Link element of item to display
	 */
	view: function(item) {
		item = $(item);
		this.mask();

		this.items = [];
		this.setGroup(item);
		
		var t = this;
		var groupTemp = {};
		this.fileExists(this.itemSource(item),
		function() {
			/* File Exists */
			
			/* Handlers */
			
			/**
			 * Add item to group
			 * @param obj el Item to add
			 * @param int idx DOM position index of item
			 * @return int Total number of items in group
			 */
			var addItem = function(el, idx) {
				groupTemp[idx] = el;
				return groupTemp.length;
			};
			
			/**
			 * Build final item array & launch viewer
			 */
			var proceed = function() {
				t.item_init = 0;
				//Sort links by document order
				var order = [], el;
				for (var x in groupTemp) {
					order.push(x);
				}
				order.sort(function(a, b) { return (a - b); });
				for (x = 0; x < order.length; x++) {
					el = groupTemp[order[x]];
					// Check if link being evaluated is the same as the clicked link
					if ($(el).get(0) == $(item).get(0)) {
						t.item_init = x;
					}
					t.items.push({'link':t.itemSource($(el)), 'title':t.getCaption(el), 'desc': t.getDescription(el)});
				}
				// Calculate top offset for the viewer and display 
				var vwrTop = $(document).scrollTop() + ($(window).height() / 15);
				t.get('viewer').css('top', vwrTop + 'px').show();
				t.itemLoad(t.item_init);
			};
			
			/* Display */
			
			//Overlay
			t.get('overlay')
				.height($(document).height())
				.fadeTo(t.anim.overlay_duration, t.options.overlayOpacity);
				
			//Single item (not in group)
			if ( !t.hasGroup() ) {
				//Display item
				t.item_init = 0;
				addItem(item, t.item_init);			
				proceed();
			} else {
				//Item in group
				var links = $(t.container).find('a');
				//Get other items in group
				var grpLinks = [];
				var i, link;
				for ( i = 0; i < links.length; i++ ) {
					link = $(links[i]);
					if ( t.itemSource(link) && t.inGroup(link) ) {
						//Add links in group
						grpLinks.push(link);
					}
				}
				
				//Loop through group links, validate, and add to items array
				var processed = 0;
				for ( i = 0; i < grpLinks.length; i++ ) {
					link = grpLinks[i];
					t.fileExists(t.itemSource($(link)),
						function(args) {
							/* File exists */

							//Add item
							var el = args.items[args.idx];
							addItem(el, args.idx);
							processed++;
							//Display valid items after all items parsed
							if ( processed == args.items.length )
								proceed();
						},
						function(args) {
							/* File does not exist */
							processed++;
							//Display valid items after all items parsed
							if (args.idx == args.items.length)
								proceed(); 
						},
						{'idx': i, 'items': grpLinks});
				}
			}
		},
		function() {
			/* File does not exist */
			t.end();
		});
	},
	
	/**
	 * Close the viewer
	 */
	end: function() {
		this.keyboardDisable();
		this.slideshowPause();
		this.get('viewer').hide();
		this.get('overlay').fadeOut(this.anim.overlay_duration);
		this.unmask();
	},
	
	/**
	 * Resizes viewer to fit image
	 * @param int imgWidth Image width in pixels
	 * @param int imgHeight Image height in pixels
	 */
	viewerResize: function(w, h) {
		var d = this.viewerSize(w, h);
		//Resize container
		this.get('container').animate({width: d.width, height: d.height}, this.anim.resize_duration);
		//Resize overlay
		this.get('overlay').css('min-width', d.width);
		this.itemShow();
	},
	
	/**
	 * Retrieve or build container size
	 * @param int w Container width to set
	 * @param int h Container height to set
	 * @return obj Container width (w)/height (h) values
	 */
	viewerSize: function(w, h) {
		var style = 'padding';
		var hz = ['left', 'right'], vt = ['top', 'bottom'];
		var ph = 0, pv = 0;
		var t = this;
		//Calculate spacing around item
		
		var getVal = function(prop) {
			var unit = 'px';
			prop = style + '-' +  prop;
			var ptemp = t.get('content').css( prop );
			if ( ptemp.indexOf(unit) == -1 ) {
				ptemp = 0;
			} else {
				ptemp = ptemp.replace(unit, '');
			}
			return ( $.isNumeric(ptemp) ) ? parseFloat(ptemp) : 0;
		};
		
		//Horizontal
		for ( var x = 0; x < hz.length; x++) {
			ph += getVal(hz[x]);
		}
		//Vertical
		for ( x = 0; x < vt.length; x++) {
			pv += getVal(vt[x]);
		}
		var c = {
			'width': w + ph,
			'height': h + pv
		};
		return c;
	},
	
	/**
	 * Displays objects that may conflict with the viewer
	 * @param bool show (optional) Whether or not to show objects (Default: TRUE)
	 */
	unmask: function (show) {
		show = ( typeof(show) == 'undefined' ) ? true : !!show;
		var vis = (show) ? 'visible' : 'hidden';
		$(this.masks.join(',')).css('visibility', vis);
	},
	
	/**
	 * Hides objects that may conflict with the viewer
	 * @uses unmask() to hide objects
	 */
	mask: function () {
		this.unmask(false);
	},
	
	/* Item */
	
	/**
	 * Retrieve item property
	 * @uses items to retrieve property from item in array
	 * @param int idx Item position index
	 * @param string prop Item property to retrieve
	 * @return mixed Item property (Default: empty string)
	 */
	itemProp: function(idx, prop) {
		return ( idx < this.items.length && prop in this.items[idx] ) ? this.items[idx][prop] : '';
	},
	
	/**
	 * Preloads requested item prior to displaying it in viewer 
	 * @param int idx Index of item in items property
	 * @uses items to retrieve item at specified index
	 * @uses viewerResize() to resize viewer after item has loaded
	 */
	itemLoad: function(idx) {
		this.item_curr = idx;

		this.keyboardDisable();
		this.slideshowPause();

		//Hide elements during transition
		this.get('slbLoading').show();
		this.get('slbContent').hide();
		this.get('details').hide();
		var preloader = new Image();
		var t = this;
		
		//Event handler: Display item when loaded
		$(preloader).bind('load', function() {
			t.get('slbContent').attr('src', preloader.src);
			t.viewerResize(preloader.width, preloader.height);

			//Restart slideshow if active
			if ( t.slideshowActive() )
				t.slideshowStart();
		});
		
		//Load image
		preloader.src = this.itemProp(this.item_curr, 'link');
	},
	
	/**
	 * Display image and begin preloading neighbors.
	 */	
	itemShow: function() {
		this.get('slbLoading').hide();
		var t = this;
		this.get('slbContent').fadeIn(500, function() { t.contentUpdate(); });
		if ( this.hasItems() ) {
			this.itemPreloadSiblings();
		}
	},
	
	/**
	 * Preloads items surrounding current item
	 */
	itemPreloadSiblings: function() {
		//Prev
		var idxPrev = ( this.itemFirst() ) ? this.items.length - 1 : this.item_curr - 1;
		var itemPrev = new Image();
		itemPrev.src = this.itemProp(idxPrev, 'link');
		
		//Next
		var idxNext = ( this.itemLast() ) ? 0 : this.item_curr + 1;
		if ( idxNext != idxPrev ) {
			var itemNext = new Image();
			itemNext.src = this.itemProp(idxNext, 'link');
		}
	},
	
	/**
	 * Check if there is at least one image to display in the viewer
	 * @return bool TRUE if at least one image is found
	 * @uses items to check for images
	 */
	hasItem: function() {
		return ( this.items.length > 0 );
	},
	
	/**
	 * Check if there are multiple images to display in the viewer
	 * @return bool TRUE if there are multiple images
	 * @uses items to determine the number of images
	 */
	hasItems: function() {
		return ( this.items.length > 1 );
	},
	
	/**
	 * Check if the current image is the first image in the list
	 * @return bool TRUE if image is first
	 * @uses item_curr to check index of current image
	 */
	itemFirst: function() {
		return ( this.item_curr == 0 );
	},
	
	/**
	 * Check if the current image is the last image in the list
	 * @return bool TRUE if image is last
	 * @uses item_curr to check index of current image
	 * @uses items to compare current image to total number of images
	 */
	itemLast: function() {
		return ( this.item_curr == this.items.length - 1 );
	},
	
	/**
	 * Retrieve source URI in link
	 * @param obj el
	 * @return string Source file URI
	 */
	itemSource: function(el) {
		var src = $(el).attr('href');
		var attr = $(el).attr('rel') || '';
		if ( attr.length ) {
			//Attachment source
			var mSrc = this.mediaProp(el, 'source');
			//Set source using extended properties
			if ( $.type(mSrc) === 'string' && mSrc.length )
				src = mSrc;
		}
		return src;
	},
	
	/**
	 * Check if current link is part of a gallery
	 * @param obj item
	 * @param string gType Gallery type to check for
	 * @return bool Whether link is part of a gallery
	 */
	itemGallery: function(item, gType) {
		var ret = false;
		var galls = {
			'wp': '.gallery-icon',
			'ng': '.ngg-gallery-thumbnail'
		};
		
		
		if ( typeof gType == 'undefined' || !(gType in galls) ) {
			gType = 'wp';
		}
		return ( $(item).parent(galls[gType]).length > 0 ) ? true : false ;
	},
	
	/* Media */
	
	/**
	 * Retrieve ID of media item
	 * @param obj el Link element
	 * @return string|bool Media ID (Default: false - No ID)
	 */
	mediaId: function(el) {
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
	mediaProps: function(el) {
		var props = {},
			mId = this.mediaId(el);
		if (mId && mId in this.media) {
			props = this.media[mId];
		}
		return props;
	},
	
	/**
	 * Retrieve single property for media item
	 * @param obj el Image link DOM element
	 * @param string prop Property to retrieve
	 * @return mixed|null Item property (Default: NULL if property does not exist)
	 */
	mediaProp: function(el, prop) {
		var props = this.mediaProps(el);
		return (prop in props) ? props[prop] : null;
	},
	
	/* Content */
	
	/**
	 * Retrieve specified label
	 * @param string id Label ID
	 * @param string def (optional) Default value if specified label is invalid
	 * @return string Label text
	 */
	getLabel: function(id, def) {
		if ( typeof def == 'undefined' ) {
			def = '';
		}
		return ( id in this.content.labels ) ? this.content.labels[id] : def;
	},
	
	/**
	 * Build caption for displayed item
	 * @param obj item DOM link element
	 * @return string Image caption
	 */
	getCaption: function(item) {
		item = $(item);
		var caption = '';
		if ( this.content.caption_enabled ) {
			var sels = {
				'capt': '.wp-caption-text',
				'gIcon': '.gallery-icon'
			};
			var els = {
				'link': item,
				'origin': item,
				'sibs': null,
				'img': null
			};
			//WP Caption
			if ( this.itemGallery(els.link, 'wp') ) {
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
				caption = this.mediaProp(els.link, 'title') || '';
				caption = $.trim(caption);
			}
			
			//Fall back Link Text
			if ( !caption ) {
				var c = '';
				if ( ( c = $.trim($(els.link).text()) ) && c.length) {
					caption = c;
				} else if (this.options.captionSrc) {
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
	 * @param obj item
	 * @return string Item description (Default: empty string)
	 */
	getDescription: function(item) {
		var desc = '';
		if ( this.content.desc_enabled ) {
			//Retrieve description
			if ( this.itemGallery(item, 'ng') ) {
				desc = $(item).attr('title');
			} else { 
				desc = this.mediaProp(item, 'desc');
			}
			
			if (!desc)
				desc = '';
		}
		return desc;
	},
	
	/**
	 * Display item details
	 */
	contentUpdate: function() {
		//Caption
		if (this.content.caption_enabled) {
			this.get('dataCaption').text(this.itemProp(this.item_curr, 'title'));
			this.get('dataCaption').show();
		} else {
			this.get('dataCaption').hide();
		}
		
		//Description
		this.get('dataDescription').html(this.itemProp(this.item_curr, 'desc'));
		
		//Handle grouped items
		if ( this.hasItems() ) {
			var num_display = this.getLabel('numDisplayPrefix') + ' ' + (this.item_curr + 1) + ' ' + this.getLabel('numDisplaySeparator') + ' ' + this.items.length;
			this.get('dataNumber')
				.text(num_display)
				.show();
		}
		
		//Resize content area
		this.get('details').width(this.get('container').width());
		this.navUpdate();
		var t = this;
		this.get('details').animate({height: 'show', opacity: 'show'}, 650);
	},
	
	/* Layout */

	/**
	 * Build layout from template
	 * @uses options.layout
	 * @return string Layout markup (HTML)
	 */
	getLayout: function() {
		var l = this.layout.template;
		
		//Expand placeholders
		var ph, phs, phr;
		for ( ph in this.layout.placeholders ) {
			phs = '{' + ph + '}';
			//Continue to next placeholder if current one is not in layout
			if (l.indexOf(phs) == -1)
				continue;
			phr = new RegExp(phs, "g");
			l = l.replace(phr, this.layout.placeholders[ph]);
		}
		
		//Return final layout
		return l;
		
	},

	/* Grouping */
	
	/**
	 * Sets group based on current item
	 * @param obj item DOM element to get group from
	 */
	setGroup: function(item) {
		this.group = this.getGroup(item);
	},
	
	/**
	 * Extract group name from 
	 * @param obj item Element to extract group name from
	 * @return string Group name
	 */
	getGroup: function(item) {
		//Return global group property if no item specified
		if ( typeof item == 'undefined' || 0 == $(item).length )
			return this.group;
		//Get item's group
		var g = '';
		var attr = $(item).attr('rel') || '';
		if ( attr != '' ) {
			var gTmp = '',
				gSt = '[',
				gEnd = ']',
				search = this.addPrefix('group') + gSt,
				idx,
				prefix = ' ';
			//Find group indicator
			idx = attr.indexOf(search);
			//Prefix with space to find whole word
			if ( prefix != search.charAt(0) && idx > 0 ) {
				search = prefix + search;
				idx = attr.indexOf(search);
			}
			//Continue processing if value is found
			if ( idx != -1 ) {
				//Extract group name
				gTmp = $.trim(attr.substring(idx).replace(search, ''));
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
	 * Check if item is part of current group
	 * @param obj item Item to check
	 * @return bool TRUE if item is in current group, FALSE otherwise
	 */
	inGroup: function(item) {
		return ( this.hasGroup() && ( this.getGroup(item) == this.getGroup() ) ) ? true : false;
	},
	
	/**
	 * Check if group is set
	 * @return bool TRUE if group is set, FALSE otherwise
	 */
	hasGroup: function() {
		return ( $.type(this.group) == 'string' && this.group.length > 0 ) ? true : false;
	},
	
	/* Slideshow */
	
	/**
	 * Checks if slideshow is currently activated
	 * @return bool TRUE if slideshow is active, false otherwise
	 * @uses slideshow.active to check slideshow activation status
	 */
	slideshowActive: function() {
		return this.slideshow.active;
	},
	
	/**
	 * Start the slideshow
	 */
	slideshowStart: function() {
		this.slideshow.active = true;
		var t = this;
		clearInterval(this.slideshow.timer);
		this.slideshow.timer = setInterval(function() { t.navNext(); t.slideshowPause(); }, this.slideshow.duration * 1000);
		this.get('navSlideControl').text(this.getLabel('stopSlideshow'));
	},
	
	/**
	 * Stop the slideshow
	 */
	slideshowStop: function() {
		this.slideshow.active = false;
		if ( this.slideshow.timer ) {
			clearInterval(this.slideshow.timer);
		}
		this.get('navSlideControl').text(this.getLabel('startSlideshow'));
	},

	/**
	 * Toggles the slideshow status
	 */
	slideshowToggle: function() {
		if ( this.slideshowActive() ) {
			this.slideshowStop();
		} else {
			this.slideshowStart();
		}
	},

	/**
	 * Pauses the slideshow
	 * Stops the slideshow but does not change the slideshow's activation status
	 */
	slideshowPause: function() {
		if ( this.slideshow.timer ) {
			clearInterval(this.slideshow.timer);
		}
	},
	
	/* Navigation */
	
	/**
	 * Display appropriate previous and next hover navigation.
	 */
	navUpdate: function() {
		if ( this.hasItems() ) {
			this.get('navPrev').show();
			this.get('navNext').show();
			if ( this.slideshow.enabled ) {
				this.get('navSlideControl').show();
				if ( this.slideshowActive() ) {
					this.slideshowStart();
				} else {
					this.slideshowStop();
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
		this.keyboardEnable();
	},
	
	/**
	 * Show the next image in the list
	 */
	navNext : function() {
		if ( this.hasItems() ) {
			if ( !this.slideshow.loop && this.itemLast() ) {
				return this.end();
			}
			if ( this.itemLast() ) {
				this.navFirst();
			} else {
				this.itemLoad(this.item_curr + 1);
			}
		}
	},

	/**
	 * Show the previous image in the list
	 */
	navPrev : function() {
		if ( this.hasItems() ) {
			if ( !this.slideshow.loop && this.itemFirst() ) {
				return this.end();
			}
			if ( this.itemFirst() ) {
				this.navLast();
			} else {
				this.itemLoad(this.item_curr - 1);
			}
		}
	},
	
	/**
	 * Show the first image in the list
	 */
	navFirst : function() {
		if ( this.hasItems() ) {
			this.itemLoad(0);
		}
	},

	/**
	 * Show the last image in the list
	 */
	navLast : function() {
		if ( this.hasItems() ) {
			this.itemLoad(this.items.length - 1);
		}
	},

	/**
	 * Enable image navigation via the keyboard
	 */
	keyboardEnable: function() {
		var t = this;
		$(document).keydown(function(e) {
			t.keyboardControl(e);
		});
	},

	/**
	 * Disable image navigation via the keyboard
	 */
	keyboardDisable: function() {
		$(document).unbind('keydown');
	},

	/**
	 * Handler for keyboard events
	 * @param event e Keyboard event data
	 */
	keyboardControl: function(e) {
		var code = e.which;
		var key = String.fromCharCode(code).toLowerCase();
		
		if ( code == 27 || key == 'x'  ) {
			//Close
			this.end();
		} else if ( code == 39 || key =='n' ) {
			//Next
			this.navNext();
		} else if ( code == 37 || key == 'p' ) {
			//Previous
			this.navPrev();
		}
	},
	
	/* Helpers */
	
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
		return ( txt.indexOf(this.addPrefix('')) === 0 ) ? true : false;
	},
	
	/**
	 * Generate formatted ID for viewer-specific elements
	 * @param string id Base ID of element
	 * @return string Formatted ID
	 */
	getID: function(id) {
		return this.addPrefix(id);
	},
	
	/**
	 * Generate formatted selector for viewer-specific elements
	 * Compares specified ID to placeholders first, then named elements
	 * Multiple selectors can be included and separated by commas (',')
	 * @param string id Base ID of element
	 * @uses layout.placeholders to compare id to placeholder names
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
			if ( id in this.layout.placeholders ) {
				var ph = $( this.layout.placeholders[id] );
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
	 * Retrieve viewer-specific element
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
		//Validate
		var t = this;
		if ( !$.isPlainObject(args) ) {
			args = null;
		}
		if ( !$.isFunction(failure) ) {
			failure = function() {
				t.end();
			};
		}
		if ( !$.isFunction(success) ) {
			success = failure;
		}
		
		//Immediate success when validation disabled
		if ( !this.options.validateLinks ) {
			return success(args);
		}
		
		//Validate link
		var statusFail = 400;
		var stateCheck = 4;
		var proceed = function(res) {
			if ( res.status < statusFail ) {
				success(args);
			} else {
				failure(args);
			}
		};
		
		//Check if URL already processed
		if ( url in this.urls_checked ) {
			proceed(this.urls_checked[url]);
		} else {
			//Build AJAX request to check new file
			var req = new XMLHttpRequest();
			req.open('HEAD', url, true);
			req.onreadystatechange = function() {
				if ( stateCheck == this.readyState ) {
					t.addUrl(url, this);
					proceed(this);
				}
			};
			req.send();
		}
	},
	
	addUrl: function(url, res) {
		if (!(url in this.urls_checked))
			this.urls_checked[url] = res;
	}
};
})(jQuery);