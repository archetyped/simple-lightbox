=== Plugin Name ===
Contributors: archetyped
Tags: lightbox, gallery, photography, images
Requires at least: 3.0
Tested up to: 3.0.4
Stable tag: trunk

A simple and customizable Lightbox for Wordpress

== Description ==

#### BETA

The latest release is a beta that includes the following improvements:

* Lightbox now integrated with jQuery
* Javascript file size 9x smaller
* Close lightbox by clicking to left/right outside of image (an oft-requested feature)

Please be aware that beta versions may not be ideal for production environments.  Please test and report any issues by going to the [Simple Lightbox's official page](http://archetyped.com/tools/simple-lightbox/). Your testing is greatly appreciated.


Simple Lightbox is a very simple and customizable lightbox that is easy to add to your Wordpress website

#### Customization
Options for customizing the lightbox behavior are located in the **Settings > Media** admin menu in the **Lightbox Settings** section (or just click the **Settings** link below the plugin's name when viewing the list of installed plugins)

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

Send your questions to wp@archetyped.com or post a comment on [Simple Lightbox's official page](http://archetyped.com/tools/simple-lightbox/)

== Screenshots ==

1. Lightbox Customization Options
2. Customized UI Text

== Changelog ==
= 1.3.2 =
* Added: Option to enable/disable lightbox resizing animation (thanks Maria!)
= 1.3.1 =
* Updated: Utilities code (internal)
= 1.3 =
* Added: Customizable UI label text (close, next, and prev button images can be replaced in `images` directory)
* Added: Group image links by Post (separate slideshow for each post)
* Added: Reset settings link on plugin listings page
* Optimized: Organized settings page

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
