=== WP Post of the Day ===
Contributors: wpscholar
Donate link: https://www.paypal.me/wpscholar
Tags: post rotation, daily post, different post, post of the day
Requires PHP: 5.3
Requires at least: 4.5
Tested up to: 5.7
Stable tag: 1.0
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Shows a new post every day.

== Description ==

The **WP Post of the Day** plugin allows you to display a new post once a day.

Using this plugin is simple:

1. Install the plugin
2. Activate the plugin
3. On the page or post where you want to have a post display, add the `[wp_post_of_the_day]` shortcode where you want your post to appear.
5. Save your changes.

= Features =

* Works with custom post types
* No settings page, just an easy way for you to show a daily post on your site
* Clean, well written code that won't bog down your site

== Installation ==

= Prerequisites =
If you don't meet the below requirements, I highly recommend you upgrade your WordPress install or move to a web host
that supports a more recent version of PHP.

* Requires WordPress version 4.5 or greater
* Requires PHP version 5.3 or greater

= The Easy Way =

1. In your WordPress admin, go to 'Plugins' and then click on 'Add New'.
2. In the search box, type in 'WP Post of the Day' and hit enter.  This plugin should be the first and likely the only result.
3. Click on the 'Install' link.
4. Once installed, click the 'Activate this plugin' link.

= The Hard Way =

1. Download the .zip file containing the plugin.
2. Upload the file into your `/wp-content/plugins/` directory and unzip
3. Find the plugin in the WordPress admin on the 'Plugins' page and click 'Activate'

== Frequently Asked Questions ==

The `[wp_post_of_the_day]` shortcode supports a few attributes to give you more control over the results:

* **author** - Provide an author ID or a comma-separated list of author IDs if you want to limit the selected posts to one of those authors. Example: `[wp-post-of-the-day author="1, 11, 14"]`

* **not** - Provide a comma-separated list of post IDs to exclude. Example: `[wp-post-of-the-day not="3, 456, 876"]`

* **post_type** - Provide a post type or a comma-separated list of post types to pull from. You must use the internal post type name. Default is `post`. Example: `[wp-post-of-the-day post_type="page"]`

* **search** - Provide a custom search term to limit the selected posts returned.  Example: `[wp-post-of-the-day search="relativity"]`

* **taxonomy** - Provide a custom taxonomy to pull from. Requires the `terms` attribute to be set as well. Example: `[wp-post-of-the-day taxonomy="post_tag" terms="2,4"]`

* **terms** - Provide a term ID or comma-separated list of term IDs to limit the selected posts returned. Requires the `taxonomy` attribute to be set as well. Example: `[wp-post-of-the-day taxonomy="post_tag" terms="2,4"]`

* **class** - Provide a custom class name for the wrapping HTML div. Example: `[wp-post-of-the-day class="my-custom-class"]`

* **size** - Provide a registered image size to use for image display (optional, only takes effect if images are being shown). Example: `[wp-post-of-the-day size="thumbnail"]`

* **show** - Provide a comma-separated list of post elements to display. You can also use a vertical pipe `|` character instead of a comma to separate items into columns. Options are: title, image, excerpt, content. Defaults to `title, image, excerpt`. Items will show in the order you provide. Note: If images are shown, only posts with featured images will be returned. Example: `[wp-post-of-the-day show="title, image"]`

Keep in mind that any of these attributes can be combined as needed.  Example: `[wp-post-of-the-day author="19" size="full" not="2310"]`.  Also, keep in mind that the shortcode can be used in text widgets.

== Changelog ==

= 1.0 =

* Tested in WordPress version 5.2

== Upgrade Notice ==
