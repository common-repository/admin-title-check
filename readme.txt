=== Admin Title Check ===
Contributors: divspark
Tags: admin, post, title, duplicate, similar, check, autocomplete, matching, suggest, backend
Requires at least: 4.4
Tested up to: 5.8
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Check whether the title matches other posts while adding or editing a post, page or custom post type in classic editor.


== Description ==

Admin Title Check adds additional title checking functionality to the title field in the admin's classic editor add and edit post pages. Typing 4 or more characters into the title field will trigger the title check. Any published (including privately) posts, pages and custom post type titles with exact or similar titles will be shown.

= Prevent Duplicate Titles =

Showing whether the title has been used by other posts and pages prevents duplicate titles (therefore may include duplicate slugs) from being added.

= Find Similar Titles =

Use existing posts' titles to help craft a new one.

= Simple =

The title check is simple to use: just start typing in a title into the title field while adding or editing a post. Exact and similar titles will be shown.


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the `Plugins` screen in WordPress.
3. The plugin can be triggered by typing 4 or more characters into the title field in the admin's add /edit post pages.


== Frequently Asked Questions ==

= Is there support for Gutenberg block editor? =

Currently, the title check will only happen in the classic editor.

= What post types does this work for? (pages, custom post types?) =

Post, pages and custom post types are supported. The title check will happen while adding or editing these post types. These post types titles' will also be matched against and shown.

= Which titles are checked =

The title is checked against all published (public and private) post types. This includes other post types.

= How are titles matched =

Exact and similar. Relevancy (how similar) is not a factor.

= Performance with larger sites =

This plugin sends requests to the server and database to find matching post titles. Several techniques are used to reduce the number of requests such as waiting for 4 characters before initiating the first search and delaying new searches. 

This has been tested with a site with 1000+ posts which was on optimized hosting for WordPress. It performed with no issue. However, the performance of much larger sites is unknown and it would be helpful if comments could be left about anyone's experiences (good and bad please).


== Screenshots ==

1. Showing exact and similar titles while adding a post
2. Showing just similar titles while adding a page
3. Hovering over one of the similar titles


== Changelog ==

= 1.0.1 =
* Improvements and fixes

= 1.0.0 =
* Release
