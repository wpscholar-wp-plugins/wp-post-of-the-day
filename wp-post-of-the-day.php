<?php

/*
 * Plugin Name:  WP Post of the Day
 * Plugin URI:   https://wordpress.org/plugins/wp-post-of-the-day/
 * Description:  Displays a new post every day.
 * Version:      1.0
 * Author:       Micah Wood
 * Author URI:   https://wpscholar.com
 * License:      GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  wp-post-of-the-day
 * Domain Path:  /languages
 */

if ( ! class_exists( 'WpPostOfTheDay' ) ) {

	/**
	 * Class WpPostOfTheDay
	 */
	class WpPostOfTheDay {

		/**
		 * The shortcode tag.
		 */
		const SHORTCODE = 'wp_post_of_the_day';

		/**
		 * Initialize the plugin.
		 */
		public static function initialize() {
			load_plugin_textdomain( 'wp-post-of-the-day', false, __DIR__ . '/languages' );
			add_filter( 'widget_text', 'do_shortcode' );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'wp_enqueue_scripts' ) );
			add_shortcode( self::SHORTCODE, array( __CLASS__, 'shortcode' ) );
		}

		/**
		 * Register our stylesheet.
		 */
		public static function wp_enqueue_scripts() {
			wp_register_style( self::SHORTCODE, plugins_url( '/assets/wp-post-of-the-day.css', __FILE__ ) );
		}

		/**
		 * Shortcode handler
		 *
		 * @param array $atts
		 *
		 * @return bool|string
		 */
		public static function shortcode( $atts ) {

			$atts = shortcode_atts(
				array(
					'author'    => '',
					'not'       => '',
					'post_type' => 'post',
					'search'    => '',
					'taxonomy'  => '',
					'terms'     => '',
					'class'     => '',
					'size'      => 'large',
					'show'      => 'title, image, excerpt',
				),
				array_change_key_case( array_filter( (array) $atts ), CASE_LOWER ),
				self::SHORTCODE
			);

			wp_enqueue_style( self::SHORTCODE );

			try {

				$post = self::get_post();

				if ( ! $post ) {
					$post = self::find_post( $atts );
				}

				return self::render_post( $post, $atts );

			} catch ( Exception $exception ) {

				// If user can edit the post containing the shortcode, show helpful error messages
				if ( current_user_can( 'edit_posts', get_the_ID() ) ) {
					return $exception->getMessage();
				}

				return '';
			}
		}

		/**
		 * Render the post.
		 *
		 * @param WP_Post $post
		 * @param array $atts
		 *
		 * @return string
		 */
		public static function render_post( WP_Post $post, array $atts ) {

			$image_size = $atts['size'];

			$groups = array_filter( array_map( function ( $group ) {
				return self::list_to_array( $group );
			}, self::list_to_array( $atts['show'], '|' ) ) );

			$can_show = array( 'title', 'image', 'excerpt', 'content' );
			$show = array_merge( ...$groups );

			$show_title = in_array( 'title', $show, true );
			$show_image = in_array( 'image', $show, true );
			$show_excerpt = in_array( 'excerpt', $show, true );
			$show_content = in_array( 'content', $show, true );

			$display = array();

			foreach ( $groups as $items ) {
				if ( count( $groups ) > 1 ) {
					$display[] = '<span class="wp-post-of-the-day__group">';
				}
				foreach ( $items as $item ) {
					if ( in_array( $item, $can_show, true ) ) {
						switch ( $item ) {
							case 'title':
								$display['title'] = $show_title ? sprintf( '<span class="wp-post-of-the-day__title">%s</span>', esc_html( get_the_title( $post ) ) ) : '';
								break;
							case 'image':
								$display['image'] = $show_image ? sprintf( '<span class="wp-post-of-the-day__image">%s</span>', get_the_post_thumbnail( $post, $image_size ) ) : '';
								break;
							case 'excerpt':
								$display['excerpt'] = $show_excerpt ? sprintf( '<span class="wp-post-of-the-day__excerpt">%s</span>', get_the_excerpt( $post ) ) : '';
								break;
							case 'content':
								$display['content'] = $show_content ? sprintf( '<span class="wp-post-of-the-day__content">%s</span>', apply_filters( 'the_content', wp_kses_post( $post->post_content ) ) ) : '';
								break;
						}
					}
				}
				if ( count( $groups ) > 1 ) {
					$display[] = '</span>';
				}
			}

			return sprintf(
				'<div class="wp-post-of-the-day %s"><a href="%s">%s</a></div>',
				esc_attr( implode( ' ', array_filter( array(
					count( $groups ) > 1 ? '--has-groups' : '',
					$atts['class']
				) ) ) ),
				esc_url( get_the_permalink( $post ) ),
				implode( '', array_filter( $display ) )
			);

		}

		/**
		 * @param $atts
		 *
		 * @throws InvalidArgumentException
		 *
		 * @return WP_Post|null
		 */
		public static function find_post( $atts ) {

			$post = null;

			$used_post_ids = self::get_used_post_ids();

			$show_image = strpos( $atts['show'], 'image' ) !== false;

			// Check for featured image support
			if ( $show_image && ! current_theme_supports( 'post-thumbnails' ) ) {
				throw new InvalidArgumentException(
					self::setup_error_message(
						__( 'Sorry, your theme does not support featured images. Update the "show" attribute to exclude the "image" option.', 'wp-post-of-the-day' ),
						'[' . self::SHORTCODE . ' show="title, excerpt"]'
					)
				);
			}

			// Taxonomy validation
			if ( ! empty( $atts['taxonomy'] ) && ! taxonomy_exists( $atts['taxonomy'] ) ) {
				throw new InvalidArgumentException(
					self::setup_error_message(
						sprintf( __( 'Sorry, taxonomy "%s" is invalid. Valid options are: %s. Please check your shortcode implementation.', 'wp-post-of-the-day' ),
							$atts['taxonomy'],
							implode( ', ', get_taxonomies() )
						),
						'[' . self::SHORTCODE . ' taxonomy="' . $atts['taxonomy'] . '"]'
					)
				);
			}

			// Taxonomy/term attribute validation
			if ( ! empty( $atts['terms'] ) && empty( $atts['taxonomy'] ) ) {
				throw new InvalidArgumentException(
					self::setup_error_message(
						sprintf( __( 'Sorry, you cannot use the terms attribute without the taxonomy attribute. Please check your shortcode implementation.', 'wp-post-of-the-day' ) ),
						'[' . self::SHORTCODE . ' terms="' . $atts['terms'] . '"]'
					)
				);
			}

			if ( empty( $atts['terms'] ) && ! empty( $atts['taxonomy'] ) ) {
				throw new InvalidArgumentException(
					self::setup_error_message(
						sprintf( __( 'Sorry, you cannot use the taxonomy attribute without the terms attribute. Please check your shortcode implementation.', 'wp-post-of-the-day' ) ),
						'[' . self::SHORTCODE . ' taxonomy="' . $atts['taxonomy'] . '"]'
					)
				);
			}

			// Post type validation
			$post_types = array_filter( array_map( 'trim', explode( ',', $atts['post_type'] ) ) );

			foreach ( $post_types as $post_type ) {
				if ( ! post_type_exists( $post_type ) ) {
					throw new InvalidArgumentException(
						self::setup_error_message(
							sprintf(
								__( 'Sorry, post type "%s" is invalid. Valid options are: %s. Please check your shortcode implementation.', 'wp-post-of-the-day' ),
								$post_type,
								implode( ', ', get_post_types( array( 'public' => true ) ) )
							),
							'[' . self::SHORTCODE . ' post_type="' . $atts['post_type'] . '"]'
						)
					);
				}
			}

			$post_types = array_filter( array_map( 'trim', explode( ',', $atts['post_type'] ) ) );

			$query_args = array(
				'post_type'      => $post_types,
				'posts_per_page' => 100,
			);

			if ( ! empty( $atts['author'] ) ) {
				$query_args['author__in'] = self::parse_id_list( $atts['author'] );
			}

			$query_args['post__not_in'] = array_merge( self::parse_id_list( $atts['not'] ), $used_post_ids );

			if ( ! empty( $atts['search'] ) ) {
				$query_args['s'] = self::parse_id_list( $atts['search'] );
			}

			if ( ! empty( $atts['taxonomy'] ) && ! empty( $atts['terms'] ) ) {
				$terms = self::parse_id_list( $atts['terms'] );
				if ( 'category' === $atts['taxonomy'] ) {
					$query_args['category__in'] = $terms;
				} else if ( 'post_tag' === $atts['taxonomy'] ) {
					$query_args['tag__in'] = $terms;
				} else {
					$query_args['tax_query'] = array(
						'taxonomy' => $atts['taxonomy'],
						'terms'    => self::parse_id_list( $atts['terms'] ),
					);
				}
			}

			if ( $show_image ) {
				$query_args['meta_query'] = array( array( 'key' => '_thumbnail_id' ) );
			}

			$query = new WP_Query( $query_args );

			if ( ! $query->have_posts() ) {

				// If nothing was found because we used all the IDs, then reset and check again.
				if ( count( self::get_used_post_ids() ) > 0 ) {
					self::set_used_post_ids( array() );

					return self::find_post( $atts );
				}

				throw new InvalidArgumentException(
					self::setup_error_message(
						__( 'Sorry, no matching posts were found. Your query may be too restrictive. Please check your shortcode implementation.', 'wp-post-of-the-day' )
					)
				);
			}

			$posts = $query->posts;

			/**
			 * @var WP_Post $post
			 */
			$post = $posts[ array_rand( $posts ) ];

			if ( $show_image && ! has_post_thumbnail( $post ) ) {
				throw new InvalidArgumentException(
					self::setup_error_message(
						__( 'Sorry, the selected post does not have a featured image.', 'wp-post-of-the-day' )
					)
				);
			}

			// Setup the new post so it is featured for the rest of the day.
			self::set_active_post_id( $post->ID );
			self::set_post_expiration( self::get_next_expiration() );
			self::set_used_post_ids( array_merge( $used_post_ids, array( $post->ID ) ) );

			return $post;

		}

		/**
		 * Get the active post, if available.
		 *
		 * @return WP_Post|null
		 */
		public static function get_post() {
			$post = null;
			$post_id = self::get_active_post_id();
			if ( $post_id ) {
				$post = get_post( $post_id );
				if ( self::has_post_expired() ) {
					$post = null;
				}
			}

			return $post ?: null;
		}

		/**
		 * Check if the post has expired.
		 *
		 * @return bool
		 */
		public static function has_post_expired() {
			return current_time( 'timestamp' ) >= self::get_post_expiration();
		}

		/**
		 * Get the active post ID.
		 *
		 * @return int
		 */
		public static function get_active_post_id() {
			return intval( get_option( self::SHORTCODE, 0 ) );
		}

		/**
		 * Set the active post ID.
		 *
		 * @param int $id
		 */
		public static function set_active_post_id( $id ) {
			update_option( self::SHORTCODE, absint( $id ), true );
		}

		/**
		 * Get the post expiration timestamp.
		 *
		 * @return int
		 */
		public static function get_post_expiration() {
			return intval( get_option( self::SHORTCODE . '_expiration', 0 ) );
		}

		/**
		 * Set the post expiration timestamp.
		 *
		 * @param int $expiration
		 */
		public static function set_post_expiration( $expiration ) {
			update_option( self::SHORTCODE . '_expiration', absint( $expiration ), true );
		}

		/**
		 * Get post IDs that have already been featured so we don't repeat ourselves.
		 *
		 * @return array
		 */
		public static function get_used_post_ids() {
			return array_map( 'absint', (array) get_option( self::SHORTCODE . '_used_ids', array() ) );
		}

		/**
		 * Store post IDs that have already been featured so we don't repeat ourselves.
		 *
		 * @param array $ids
		 */
		public static function set_used_post_ids( array $ids ) {
			update_option( self::SHORTCODE . '_used_ids', array_filter( $ids ), false );
		}

		/**
		 * Get the timestamp representing the new post expiration.
		 *
		 * @return int
		 */
		public static function get_next_expiration() {
			$dateTime = self::get_date_time( current_time( 'timestamp' ) );
			$dateTime->setTime( 23, 59, 59 );

			return $dateTime->getTimestamp();
		}

		/**
		 * Get a DateTime object in the current time zone.
		 *
		 * @param int $timestamp
		 *
		 * @return DateTime
		 */
		public static function get_date_time( $timestamp ) {
			$dt = new DateTime( 'now', self::get_date_time_zone() );
			$dt->setTimestamp( $timestamp );

			return $dt;
		}

		/**
		 * Get a DateTimeZone object that reflects WordPress' timezone setting.
		 *
		 * @return DateTimeZone
		 */
		public static function get_date_time_zone() {
			$timezone_string = get_option( 'timezone_string' );
			if ( ! empty( $timezone_string ) ) {
				return new DateTimeZone( $timezone_string );
			}
			$offset = get_option( 'gmt_offset' );
			$hours = (int) $offset;
			$minutes = abs( ( $offset - (int) $offset ) * 60 );
			$offset = sprintf( '%+03d:%02d', $hours, $minutes );

			return new DateTimeZone( $offset );
		}

		/**
		 * Setup error message.
		 *
		 * @param string $message
		 *
		 * @param string $example
		 *
		 * @return string
		 */
		public static function setup_error_message( $message, $example = '' ) {
			return sprintf(
				'<div class="wp-post-of-the-day-error"><p>%s</p>%s<p>%s</p></div>',
				esc_html( $message ),
				empty( $example ) ? '' : '<p>' . esc_html( $example ) . '</p>',
				esc_html( 'Note: This helpful notification is only visible to logged in users who can edit this shortcode.' )
			);
		}

		/**
		 * Parse an ID list into an array.
		 *
		 * @param string $list
		 *
		 * @return int[]
		 */
		public static function parse_id_list( $list ) {
			$ids = array();
			if ( ! empty( $list ) ) {
				$ids = array_filter( array_map( 'absint', explode( ',', preg_replace( '#[^0-9,]#', '', $list ) ) ) );
			}

			return $ids;
		}

		/**
		 * Convert a list (string) to an array
		 *
		 * @param string $list
		 * @param string $delimiter
		 *
		 * @return array
		 */
		public static function list_to_array( $list, $delimiter = ',' ) {
			return array_filter( array_map( 'trim', explode( $delimiter, $list ) ) );
		}

	}

	WpPostOfTheDay::initialize();

}
