<?php
/**
 * Plugin name:       The Events Calendar Extension: List Venues/Organizers Shortcodes
 * Plugin URI:        https://theeventscalendar.com/extensions/list-venues-and-organizers-shortcodes/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-list-venues-organizers-shortcodes/
 * Description:       Adds the `[list_venues]` and `[list_organizers]` shortcodes to list Venues and Organizers. Custom linked post types (https://theeventscalendar.com/knowledgebase/linked-post-types/) can be used as well, such as `[tec_list_linked_posts post_type="tribe_ext_instructor"]`.
 * Version:           2.1.3
 * Extension Class:   Tribe__Extension__VenueOrganizer_List
 * Author:            Modern Tribe, Inc
 * Author URI:        http://theeventscalendar.com
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       tribe-ext-list-venues-organizers-shortcodes
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 */

// Do not load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if (
	class_exists( 'Tribe__Extension' )
	&& ! class_exists( 'Tribe__Extension__VenueOrganizer_List' )
) {
	class Tribe__Extension__VenueOrganizer_List extends Tribe__Extension {
		protected $base_shortcode_tag = 'tec_list_linked_posts';
		protected $atts = [];
		protected $query;
		protected $output = '';

		public function construct() {
			$this->add_required_plugin( 'Tribe__Events__Main', '4.3' );
		}

		/**
		 * Extension initialization and hooks.
		 */
		public function init() {
			// Load plugin textdomain
			load_plugin_textdomain( 'tribe-ext-list-venues-organizers-shortcodes', false, basename( dirname( __FILE__ ) ) . '/languages/' );

			/**
			 * All extensions require PHP 5.6+, following along with https://theeventscalendar.com/knowledgebase/php-version-requirement-changes/
			 */
			$php_required_version = '5.6';

			if ( version_compare( PHP_VERSION, $php_required_version, '<' ) ) {
				if (
					is_admin()
					&& current_user_can( 'activate_plugins' )
				) {
					$message = '<p>';

					$message .= sprintf( __( '%s requires PHP version %s or newer to work. Please contact your website host and inquire about updating PHP.', 'match-the-plugin-directory-name' ), $this->get_name(), $php_required_version );

					$message .= sprintf( ' <a href="%1$s">%1$s</a>', 'https://wordpress.org/about/requirements/' );

					$message .= '</p>';

					tribe_notice( $this->get_name(), $message, 'type=error' );
				}

				return;
			}

			add_shortcode( $this->base_shortcode_tag, [ $this, 'do_base_shortcode' ] );
			add_shortcode( 'list_venues', [ $this, 'do_venue_shortcode' ] );
			add_shortcode( 'list_organizers', [ $this, 'do_organizer_shortcode' ] );

			add_action( 'wp_enqueue_scripts', [ $this, 'load_styles' ] );
		}

		/**
		 * Load needed stylesheets
		 */
		public function load_styles() {
			wp_register_style( 'tribe-list-venues-organizers-shortcodes', plugins_url( 'tribe-ext-list-venues-organizers-shortcodes/src/resources/css/tribe-list-venues-organizers-shortcodes.css' ) );
			wp_enqueue_style( 'tribe-list-venues-organizers-shortcodes' );
		}

		/**
		 * Creating the queries
		 */
		protected function query() {
			// remove all whitespaces
			$exclude = preg_replace( '/\s+/', '', $this->atts['exclude'] );
			$include = preg_replace( '/\s+/', '', $this->atts['include'] );
			$author  = preg_replace( '/\s+/', '', $this->atts['author'] );
			if ( 'current_user' == $author ) {
				$author = get_current_user_id();
			}
			$exclude = explode( ',', $exclude );
			$include = explode( ',', $include );

			$args = [
				'post_type'      => $this->atts['post_type'],
				'posts_per_page' => $this->atts['limit'],
				'order'          => $this->atts['order'],
				'orderby'        => $this->atts['orderby'],
				'post__not_in'   => $exclude, // must be an array
			];

			if ( $this->atts['include'] ) {
				$args['post__in'] = $include;
			}
			if ( $this->atts['author'] ) {
				$args['author'] = $author;
			}

			$this->query = new WP_Query( apply_filters( 'tribe_ext_list_venues_organizers_shortcodes_args', $args, $this->atts ) );
		}

		/**
		 * Creating and formatting the output
		 */
		protected function format() {
			$opening_tag  = '<ul class="tribe-venues-organizers-shortcode list ' . esc_attr( $this->atts['post_type'] ) . '">';
			$this->output = apply_filters( 'tribe_ext_list_venues_organizers_shortcodes_list_open', $opening_tag, $this->atts );

			// remove all whitespaces
			$thumb      = preg_replace( '/\s+/', '', $this->atts['thumb'] );
			$details    = preg_replace( '/\s+/', '', $this->atts['details'] );
			$count      = preg_replace( '/\s+/', '', $this->atts['count'] );
			$hide_empty = preg_replace( '/\s+/', '', $this->atts['hide_empty'] );

			while ( $this->query->have_posts() ) {
				$this->query->the_post();
				$post_id = get_the_ID();

				// count upcoming events
				$args = [
					'start_date' => date( 'Y-m-d H:i:s' ),
				];

				if ( Tribe__Events__Organizer::POSTTYPE == $this->atts['post_type'] ) {
					$args['organizer'] = $post_id;
				} elseif ( Tribe__Events__Venue::POSTTYPE == $this->atts['post_type'] ) {
					$args['venue'] = $post_id;
				}

				$args = apply_filters( 'tribe_ext_list_venues_organizers_shortcodes_get_events_per_post_args', $args, $post_id, $this->atts );

				/** @var WP_Query $events */
				$events = tribe_get_events( $args, true );

				$events_count = $events->found_posts;

				// generate HTML
				if (
					'yes' != $hide_empty
					|| (
						'yes' == $hide_empty
						&& $events_count > 0
					)
				) :
					$item       = sprintf( '<li id="%s" class="tec list %s">', esc_attr( $this->atts['post_type'] . '-' . $post_id ), esc_attr( $this->atts['post_type'] ) );
					$item_thumb = '';

					// get the post thumbnail (if there is one)
					if ( 'yes' == $thumb ) {
						if ( get_the_post_thumbnail( $post_id ) ) {
							$image      = '<div class="tribe-venues-organizers-image">' . get_the_post_thumbnail( $post_id, 'thumbnail' ) . '</div>';
							$item_thumb = $image;
						}
					}

					// reset at the start of processing each post
					$link = '';

					// get the title - if TEC PRO, link to Linked Post Type's single page, else just output title without link
					if ( Tribe__Dependency::instance()->is_plugin_active( 'Tribe__Events__Pro__Main' ) ) {
						if ( Tribe__Events__Organizer::POSTTYPE == $this->atts['post_type'] ) {
							$link = tribe_get_organizer_link( $post_id );
						} else if ( Tribe__Events__Venue::POSTTYPE == $this->atts['post_type'] ) {
							$link = tribe_get_venue_link( $post_id );
						}
					} else {
						if (
							Tribe__Events__Organizer::POSTTYPE == $this->atts['post_type']
							|| Tribe__Events__Venue::POSTTYPE == $this->atts['post_type']
						) {
							$link = get_the_title();
						}
					}

					/**
					 * Customize the link within the List Linked Post Types shortcode.
					 *
					 * Useful to disable linking (href) the post title if the custom
					 * linked post type posts do not have publicly-visible get_the_permalink()
					 * pages, or if you want to add some additional HTML.
					 *
					 * @since 2.1.1
					 *
					 * @param string $link      The full link (which may be empty or just a post title).
					 * @param string $post_type The post type key.
					 * @param int    $post_id   The Post ID.
					 *
					 * @return string
					 */
					$link = (string) apply_filters( 'tribe_venue_organizer_shortcode_link', $link, $this->atts['post_type'], $post_id );

					if ( empty( $link ) ) {
						$link = sprintf( '<a href="%s">%s</a>', get_the_permalink(), get_the_title() );
					}

					$items['link'] = $link;

					// get the upcoming event count string
					if (
						! empty( $this->atts['post_type'] ) // to avoid accidental querying for 'post' post type
						&& 'yes' == $count
					) {
						if ( 0 === $events_count ) {
							$count_string = __( 'No upcoming events', 'tribe-ext-list-venues-organizers-shortcodes' );
						} else {
							$count_string = sprintf( _n( '%s upcoming event', '%s upcoming events', $events_count, 'tribe-ext-list-venues-organizers-shortcodes' ), $events_count );
						}

						$count_string = '<span class="tribe-venues-organizers-event-count">' . $count_string . '</span>';
						apply_filters( 'tribe_venue_organizer_shortcode_count', $events_count, $count_string, $post_id );
						$items['count_string'] = $count_string;
					}

					// get the details
					if ( 'yes' == $details ) {
						$details_class = sprintf( 'tribe-%s-details', esc_attr( $this->atts['post_type'] ) );
						$details_class = str_replace( ' ', '-', $details_class );

						if ( Tribe__Events__Organizer::POSTTYPE == $this->atts['post_type'] ) {
							$item_details = tribe_get_organizer_details();
						} elseif ( Tribe__Events__Venue::POSTTYPE == $this->atts['post_type'] ) {
							$venue_details = tribe_get_venue_details();
							$item_details  = $venue_details['address'];
						} else {
							$item_details = get_the_excerpt();
						}

						$item_details = apply_filters( 'tribe_ext_list_venues_organizers_shortcodes_item_details', $item_details, $post_id );

						if ( ! empty( $item_details ) ) {
							// We wrap with `<div>` because `<address>` (from Organizer contact information) is not allowed within `<p>` and we want to display as block elements.

							$item_details = sprintf( '<div class="tribe-venues-organizers-details %s">%s</div>', $details_class, $item_details );
						}

						$items['details'] = $item_details;
					}

					$item .= $item_thumb ? $item_thumb : '';
					$item .= '<div class="tribe-venues-organizers-info">';

					$item .= implode( '', $items ) . '</div></li>';

					$this->output .= apply_filters( 'tribe_ext_list_venues_organizers_shortcodes_list_item', $item, $post_id, $this->atts );
				endif;
			}

			$this->output .= apply_filters( 'tribe_ext_list_venues_organizers_shortcodes_list_close', '</ul>', $this->atts );
			wp_reset_postdata();
		}

		/**
		 * Creating the shortcode for venues
		 *
		 * @param $atts - an associative array of attributes, or an empty string if no attributes are given
		 *
		 * @return string
		 */
		public function do_venue_shortcode( $atts ) {
			if ( ! is_array( $atts ) ) {
				$atts = [];
			}
			$atts['post_type'] = Tribe__Events__Venue::POSTTYPE;

			return $this->do_base_shortcode( $atts );
		}

		/**
		 * Creating the shortcode for organizers
		 *
		 * @param $atts - an associative array of attributes, or an empty string if no attributes are given
		 *
		 * @return string
		 */
		public function do_organizer_shortcode( $atts ) {
			if ( ! is_array( $atts ) ) {
				$atts = [];
			}
			$atts['post_type'] = Tribe__Events__Organizer::POSTTYPE;

			return $this->do_base_shortcode( $atts );
		}

		/**
		 * Run the shortcode logic.
		 *
		 * Note the `count` argument does not work for custom linked post types
		 * by default (also keep in mind how the `hide_empty` argument might be
		 * affected). To get it to work, you would need to leverage the
		 * `tribe_ext_list_venues_organizers_shortcodes_args` and
		 * `tribe_ext_list_venues_organizers_shortcodes_get_events_per_post_args`
		 * filters to add your necessary custom logic.
		 *
		 * @param $atts
		 *
		 * @return string
		 */
		public function do_base_shortcode( $atts ) {
			$this->atts = shortcode_atts(
				[
					'post_type'  => '', // WP_Query sets to 'post' by default if blank, but we do not allow that within this shortcode's context to avoid unintended consequences.
					'limit'      => -1,
					'order'      => 'ASC',
					'orderby'    => 'post_title',
					'exclude'    => '', // comma-separated list of Linked Post Type post IDs
					'include'    => '', // comma-separated list of Linked Post Type post IDs
					'author'     => '', // int || str Linked Post Type author ID or str "current_user"
					'thumb'      => '', // str "yes" to include Linked Post Type thumbnail
					'details'    => '', // str "yes" to include Linked Post Type details
					'count'      => '', // str "yes" to include count of upcoming events for each Venue or Organizer
					'hide_empty' => '', // string "yes" to exclude Linked Post Type posts without upcoming events
				], $atts, $this->base_shortcode_tag
			);

			if ( ! empty( $this->atts['post_type'] ) ) {
				$this->query();
				$this->format();

				return $this->output;
			} else {
				return '';
			}
		}
	}
}