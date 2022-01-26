=== Simple Lightbox ===
Contributors: Archetyped
Donate link: http://gum.co/slb-donate
License: GPLv2
Tags: lightbox, gallery, photography, images, theme, template, style
Requires at least: 5.3
Tested up to: 5.9
Requires PHP: 5.6.20
Stable tag: trunk

The highly customizable lightbox for WordPress

== Description ==
Simple Lightbox is a very simple and customizable lightbox that is easy to add to your WordPress website.

#### Features

Options for customizing the lightbox behavior are located in the **Appearance > Lightbox** admin menu (or just click the **Settings** link below the plugin's name when viewing the list of installed plugins)

* Automatically activate links (no manual coding required)
* Automatically resize lightbox to fit in window
* Customize lightbox with **themes**
* Mobile-optimized responsive themes included
* Customizable lightbox animations
* Infinitely customizable with **add-ons**
* Supports WordPress **image attachment** links
* Supports links in **widgets**
* Keyboard Navigation
* Display media metadata (caption, description, etc.) in lightbox
* Enable Lightbox depending on Page Type (Home, Pages, Archive, etc.)
* Group image links (play as a slideshow)
* Group image links by Post (separate slideshow for each post on page)

#### Usage

1.  Insert links to images/image attachments into your posts/pages

**That's it! The image will be displayed in a lightbox automatically.**

* For more usage tips, go to [Simple Lightbox's official page](http://archetyped.com/tools/simple-lightbox/)

== Installation ==

1.  Install and activate SLB
1.  Verify that your site's theme uses the `wp_head()`, `wp_footer()`, & `the_content()` template tags (standard in any professional theme)

== Upgrade Notice ==

= 2.8.0 =
Faster link processing & other optimizations (WordPress 5.3+ & PHP 7.2+ required).

= 2.7.0 =
Fixes & improvements. PHP 5.4+ Required.

== Frequently Asked Questions ==

Get more information on [Simple Lightbox's official page](http://archetyped.com/tools/simple-lightbox/)

== Screenshots ==

1.  Lightbox Customization Options
2.  Light Theme
3.  Dark Theme

== Changelog ==

= 2.8.1 =

* Update: PHP 5.6 Compatibility
* Add: PHPCS configuration
* Add: GitHub Issue templates

= 2.8.0 =

* Update: WordPress 5.3+ required.
* Update: PHP 7.2+ required.
* Optimize: Link detection up to 2x faster.
* Optimize: Options data handling.
* Optimize: Default title filtering.
* Optimize: Standardize media item data structure to avoid conflicts with third-party data.
* Optimize: Load only necessary media item properties in browser.
* Optimize: Filter all media items (instead of each individual item).
    * Filter Removed: `media_item_properties` (single item).
    * Filter Added: `media_items` (all items).
* Fix: `area` elements included in link detection (This is Jim's Area).

[See full changelog](https://github.com/archetyped/simple-lightbox/releases)