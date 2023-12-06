=== The Events Calendar Extension: List Venues/Organizers Shortcodes ===
Contributors: ModernTribe
Donate link: http://m.tri.be/29
Tags: events, calendar
Requires at least: 4.5
Tested up to: 4.9.8
Requires PHP: 5.6
Stable tag: 2.1.3
License: GPL version 3 or any later version
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Adds the `[list_venues]` and `[list_organizers]` shortcodes to list Venues and Organizers. Custom linked post types (https://theeventscalendar.com/knowledgebase/linked-post-types/) can be used as well, such as `[tec_list_linked_posts post_type="tribe_ext_instructor"]`.

== Description ==

Adds the `[list_venues]` and `[list_organizers]` shortcodes to list Venues and Organizers.

Custom linked post types (https://theeventscalendar.com/knowledgebase/linked-post-types/) can be used as well, such as `[tec_list_linked_posts post_type="tribe_ext_instructor"]`.

== Installation ==

Install and activate like any other plugin!

* You can upload the plugin zip file via the *Plugins ‣ Add New* screen
* You can unzip the plugin and then upload to your plugin directory (typically _wp-content/plugins)_ via FTP
* Once it has been installed or uploaded, simply visit the main plugin list and activate it

== Frequently Asked Questions ==

= Where can I find more extensions? =

Please visit our [extension library](https://theeventscalendar.com/extensions/) to learn about our complete range of extensions for The Events Calendar and its associated plugins.

= What if I experience problems? =

We're always interested in your feedback and our [premium forums](https://theeventscalendar.com/support-forums/) are the best place to flag any issues. Do note, however, that the degree of support we provide for extensions like this one tends to be very limited.

== Changelog ==

= [2.1.3] 2019-07-09 =

* Fix - The shortcode's "count" argument is now correct, no longer limited by the default `'posts_per_page'` from `tribe_get_events()` [119631]

= [2.1.2] 2018-11-20 =

* Fix - Added a check to make sure the right type is passed when creating shortcode

= [2.1.1] 2018-08-27 =

* Fix - Use our native functions to get the Organizer and Venue names and links so those get filtered and display consistent with the rest of the site
* Tweak - Custom linked post types (other than Organizer and Venue) now link the post title, even without Events Calendar PRO
* Tweak - Added the `tribe_venue_organizer_shortcode_link` filter to allow customizing any link of any post type
* Tweak - Display the excerpt instead of the full post content as the Item Details for all custom linked post types other than Organizer and Venue
* Tweak - Now requires PHP 5.6+, as do all of our plugins and extensions.

= [2.1.0] 2018-03-23 =

* Feature - Registers a new `[tec_list_linked_posts]` shortcode that can have any linked post type passed to its "post_type" argument, which means this shortcode now supports custom linked post types (see code comments for limitations), such as from https://theeventscalendar.com/knowledgebase/linked-post-types/
* Tweak - Reworked some filters to fix some not being applied and to pass the `$post_id` to filters within the loop (for each linked post type post)

= [2.0.0] 2018-03-19 =

* Fully rewritten in our Extensions Framework
* Fix - Listing Organizers now works
* Fix - No longer links to single Venue or Organizer pages if PRO is not active
* Feature - Optionally limit the quantity of Venues or Organizers output
* Feature - Optionally list Venue or Organizer by ID(s)
* Feature - Optionally exclude Venues or Organizers by ID(s)
* Feature - Optionally output Venue or Organizer featured image
* Feature - Optionally output Venue or Organizer count of upcoming events
* Feature - Optionally hide Venues or Organizers without upcoming events
* Feature - Optionally sort Venues or Organizers by WP_Query's `order` and `orderby` parameters
* Feature - Optionally display Venues or Organizers by author

= [1.0.0] 2015-04-01 =

* Initial release as a Knowledgebase plugin
