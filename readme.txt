=== Simple Lightbox ===
Contributors: Archetyped
Donate link: http://mycharitywater.org/archetyped-2012-fall
Tags: lightbox, gallery, photography, images, theme, template, style
Requires at least: 3.4
Tested up to: 3.5
License: GPLv2 or later
Stable tag: trunk

A simple, themeable, and highly customizable Lightbox for Wordpress

== Description ==

#### Simple Lightbox 2.0 Beta!
The next generation of Simple Lightbox is here and ready for brave beta testers.
[Sign up for SLB 2.0 beta now](http://archetyped.com/lab/simple-lightbox-2-0-beta/) while there's still space available! 

Simple Lightbox is a very simple and highly customizable lightbox that is easy to add to your Wordpress website.  It also [supports skins](http://archetyped.com/lab/slb-registering-themes/), so it can be fully integrated with your site's theme.

#### Features
Options for customizing the lightbox behavior are located in the **Settings > Media** admin menu in the **Lightbox Settings** section (or just click the **Settings** link below the plugin's name when viewing the list of installed plugins)

* **Supports links in Widgets (New!)**
* **Supports Image Attachment links**
* **Display image metadata (caption, description, etc.) in lightbox**
* Automatically activate links (no manual coding required)
* Keyboard Navigation
* Link Validation (optional)
* User-customizable skin (template) support
* Enable Lightbox depending on Page Type (Home, Pages, Archive, etc.)
* Group image links (play as a slideshow)
* Group image links by Post (e.g. separate slideshow for each post on home page)
* UI/Animation Customization
* Slideshow Customization
* Backwards-compatibility with legacy lightbox links

#### Usage
1. Insert links to images/image attachments into your posts/pages

**That's it! The image will be displayed in a lightbox automatically.**

* For more usage tips, go to [Simple Lightbox's official page](http://archetyped.com/tools/simple-lightbox/)

== Installation ==

1. Install and activate SLB
1. Verify that your site's theme uses the `wp_head()`, `wp_footer()`, & `the_content()` template tags (standard in any professional theme)

== Upgrade Notice ==

No upgrade notices

== Frequently Asked Questions ==

Post your questions and comments on [Simple Lightbox's official page](http://archetyped.com/tools/simple-lightbox/)

== Screenshots ==

1. Lightbox Customization Options
2. Customized UI Text

== Changelog ==

= 1.6.3.1 =
* Fix: Backward compatibility restored (Backward Genius)

= 1.6.3 =
* Optimize: Improved lightbox overlay display

= 1.6.2 =
* Update: Rebuild JS code
* Optimize: Improved compatibility with PHP 5+
* Optimize: Improved utility code
* Fix: Some elements can overlap lightbox
* Fix: Slideshow not automatically starting (Laurence of Autoplay)

= 1.6.1 =
* Optimize: Handle repeated initializations of lightbox on client-side
* Fix: Localization not properly loaded (Mustafa Lingo)

= 1.6 =
* Add: Widget support
* Add: WordPress 3.3 support
* Add: Localization support
* Add: Option to group gallery links separately (supports WordPress & NextGen galleries)
* Add: Upgrade notice
* Optimize: WP 3.3 compatibility
* Optimize: Improved compatibility with URI case-sensitivity
* Optimize: Activation processing
* Optimize: Image grouping
* Optimize: Image metadata loading performance
* Optimize: File loading
* Optimize: Improved safeguards against interference by bugs in other plugins
* Optimize: Link processing performance
* Optimize: Lightbox styling isolated from site styles
* Optimize: Improved link processing performance
* Optimize: Improved image metadata support
* Optimize: Improved support for HTTP/HTTPS requests
* Fix: SLB is not defined in JS (Jezz Hands)
* Fix: Boolean case-sensitivity (78 Truths)
* Fix: YouTube embed using iFrame overlaps lightbox (Elena in Hiding)
* Fix: Issue when scanning links without valid URLs (McCloskey Iteration)
* Fix: Image activation is case-sensitive (Sensitive Tanya)
* Fix: Visible lightbox overlay edges when image larger than browser window (Chibi Overlay) 
* Fix: Options availability for some users
* Fix: Inconsistent loading of image metadata
* Fix: Links not fully processed when group is set manually

= 1.5.6 =
* Add: Display image description in lightbox (with HTML support)
* Add: Support for W3 Total Cache plugin
* Add: Initial support for NextGEN galleries
* Update: **Important:** [System Requirements](http://wordpress.org/about/requirements/) aligned with WP 3.2.1
* Optimize: Improved support for small images in default template
* Optimize: Support for non-English text in user options
* Optimize: Improved IE compatibility
* Optimize: Improved data handling
* Optimize: Skin loading performance
* Optimize: Skin CSS Cleanup
* Optimize: Caption support for galleries
* Optimize: Options code cleanup (Juga Sweep)
* Fix: User-defined UI text not used (Ivan gets Even (cooler))
* Fix: Options reset after update (KRazy Donna)

= 1.5.5.1 =
* Fix: Disabled links not being disabled (Disabling Sascha)

= 1.5.5 =
* Add: Distinct link activation (will not affect other lightboxes)
* Add: Backwards compatibility with legacy lightbox links (optional)
* Add: Support for WordPress 3.2
* Add: Support for links added after page load (e.g. via AJAX, etc.)
* Add: Admin option to enable/disable attachment links
* Add: Support for image attachment links
* Update: Options management overhaul
* Update: Additional WordPress 3.2 support (Gallery)
* Update: Cache-management for enqueued files
* Update: Improved UI consistency
* Update: Improved compatibility for older versions of PHP
* Update: Internal optimizations
* Update: Improved URL handling
* Fix: Improved options migration from old versions (Hutchison Migration)
* Fix: XHTML Validation (Hajo Validation)

= 1.5.4 =
* Add: Optional Link validation
* Add: Keyboard Navigation
* Add: Option to enable/disable image caption
* Add: `rel` attribute supported again
* Add: Use `slb_off` in link's `rel` attribute to disable automatic activation for link
* Fix: HTTPS compatibility (J&uuml;rgen Protocol)
* Fix: Enabling SLB on Pages issue
* Fix: Zmanu is_single
* Fix: Image order is sometimes incorrect
* Optimize: Filter double clicks
* Optimize: Separate options to enable/disable SLB on Posts and Pages
* Optimize: Better grouping support

= 1.5.3 =
* Fix: Caption may not display under certain circumstances (Caption Erin)
* Fix: Images not grouped when "separate by post" option is activated (Logical Ross)
* Update: Lightbox will not be activated for links that already have `rel` attribute set

= 1.5.2 =
* Fix: Slideshow loops out of control (Mirage of Wallentin)
* Fix: Lightbox fails when group by posts disabled (Lange Find)
* Add: Option to use the image's URI as caption when link title not set (Under UI options)

= 1.5.1 =
* Add: WP Gallery support
* Fix: Navigation hidden when only one image
* Fix: Use user-defined UI text

= 1.5 =
* Add: Theme support
* Optimize: Javascript cleanup and file size reductions
* Optimize: CSS cleanup

= 1.4 =
* Update: Integrated with jQuery
* Optimize: Javascript filesize 9x smaller
* Add: Close lightbox by clicking to left/right outside of image (an oft-requested feature)

= 1.3.2 =
* Add: Option to enable/disable lightbox resizing animation (thanks Maria!)

= 1.3.1 =
* Update: Utilities code (internal)

= 1.3 =
* Add: Customizable UI label text (close, next, and prev button images can be replaced in `images` directory)
* Add: Group image links by Post (separate slideshow for each post)
* Add: Reset settings link on plugin listings page
* Optimize: Organized settings page

= 1.2.1 =
* Fixed: Image title given higher precedence than Image alt (more compatible w/WP workflow)

= 1.2 =
* Added: Option to group automatically activated links
* Optimized: Lightbox caption retrieval

= 1.1 =
* Added: Enable/disable lightbox functionality by page type (Home, Pages/Posts, Archive, etc.)
* Added: Automatically activate lightbox functionality for image links
* Added: Link to settings menu on plugin listing page
* Optimized: Options menu field building
* Optimized: Loading of default values for plugin options
* Optimized: General code optimizations

= 1.0 =
* Initial release
