=== Plugin Name ===
Contributors: archetyped
Tags: lightbox, gallery, photography, images
Requires at least: 2.9.2
Tested up to: 2.9.2
Stable tag: trunk

A simple and customizable Lightbox for Wordpress

== Description ==

Simple Lightbox is a very simple and customizable lightbox that is easy to add to your Wordpress website

#### Customization
Options for customizing the lightbox behavior are located in the **Settings > Media** admin menu in the **Lightbox Settings** section (or just click the **Settings** link below the plugin's name when viewing the list of installed plugins)

* Enable/Disable Lightbox Functionality (Default: Enabled)
* Enable Lightbox depending on Page Type (Home, Pages, Archive, etc.)
* Automatically activate lightbox for links to images on page (no need to add `rel="lightbox"` attribute to link)
* Group automatically-activated links (play as a slideshow)
* Automatically Start Slideshow (Default: Enabled)
* Slide Duration (Seconds) (Default: 6)
* Loop through images (Default: Enabled)
* Overlay Opacity (0 - 1) (Default: 0.8)

#### Usage
* The necessary Javascript and CSS files will be automatically added to the page as long as `wp_head()` is called in the theme
* That's it!

== Installation ==

1. Upload `simple-lightbox` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Verify that your theme uses the `wp_head()` template tag (this is where the necessary files will be added to your theme output)
1. Let plugin automatically add lightbox functionality for links to images or manually add `rel="lightbox"` to any image links that you want to be displayed in a lightbox

== Upgrade Notice ==

No upgrade notices

== Frequently Asked Questions ==

Send your questions to wp@archetyped.com

== Screenshots ==

1. Lightbox Options

== Changelog ==
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
