=== Plugin Name ===
Contributors: archetyped
Donate link: http://archetyped.com/tools/simple-lightbox/#donate
Tags: lightbox, gallery, photography, images, theme, template, style
Requires at least: 3.0
Tested up to: 3.1.3
Stable tag: trunk

A simple, themeable, and customizable Lightbox for Wordpress

== Description ==
Simple Lightbox is a very simple and customizable lightbox that is easy to add to your Wordpress website.  It also [supports themes](http://archetyped.com/lab/slb-registering-themes/), so it can be fully integrated with your site's theme.

### BETA NOTES
The current release is a beta version.  Please test and [provide feedback on the beta release page](http://archetyped.com/lab/slb-1-5-5-beta/).
Main changes

#### Beta 3
* Add: Support for image attachment links
* Optimize: Improved compatibility for older versions of PHP
* Optimize: Internal optimizations

#### Beta 2
* Fix: Debug code removed (Fatal Seb)
* Update: Improved URL handling
* Update: Cache-management for enqueued files

#### Beta 1
* Update: Options management overhaul
* Fix: XHTML Validation (Hajo Validation)

#### Customization
Options for customizing the lightbox behavior are located in the **Settings > Media** admin menu in the **Lightbox Settings** section (or just click the **Settings** link below the plugin's name when viewing the list of installed plugins)

* **New: Link Validation** (Optional)
* **New: Keyboard Navigation**
* **New: Show/Hide Lightbox Caption**
* Theme Support
* Customizable UI Text
* Enable/Disable Lightbox Functionality (Default: Enabled)
* Enable Lightbox depending on Page Type (Home, Pages, Archive, etc.)
* Automatically activate lightbox for links to images on page (no need to add `rel="lightbox"` attribute to link)
* Group automatically-activated links (play as a slideshow)
* Group image links by Post (separate slideshow for each Post on page)
* Enable/Disable Lightbox resizing animation
* Automatically Start Slideshow (Default: Enabled)
* Slide Duration (Seconds) (Default: 6)
* Loop through images (Default: Enabled)
* Overlay Opacity (0 - 1) (Default: 0.8)

#### Usage
* The necessary Javascript and CSS files will be automatically added to the page as long as `wp_head()` is called in the theme
* That's it!

== Installation ==

1. Verify that your theme uses the `wp_head()` template tag (this is where the necessary files will be added to your theme output)
1. Let plugin automatically add lightbox functionality for links to images or manually add `rel="lightbox"` to any image links that you want to be displayed in a lightbox

== Upgrade Notice ==

No upgrade notices

== Frequently Asked Questions ==

Post your questions and comments on [Simple Lightbox's beta release page](http://archetyped.com/lab/slb-1-5-5-beta/)

== Screenshots ==

1. Lightbox Customization Options
2. Customized UI Text

== Changelog ==
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
