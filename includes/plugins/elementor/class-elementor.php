<?php
/**
 * Elementor Class
 *
 * Elementor Theme Builder compatibility:
 * ======================================
 * This is quite a lot more complicated integration than with Beaver Themer!
 *
 * Though I understand Elementor is trying to make Theme Builder compatible
 * out of the box with ANY theme, it is not a good idea in my opinion, as
 * every theme is different and the whole concept of Theme Builder might
 * be too complicated for basic WP user as you need to know something about
 * WP theme hierarchy!
 *
 * Besides, I've encountered numerous (logical) issues even when using native
 * TwentySeventeen theme (and those does not occur with Beaver Themer's logic),
 * such as not displaying any (even the theme's default) header when a custom
 * header is set to display on some, not all pages, for example.
 *
 * The code below should be future proof enough, unless something is changed
 * in Elementor's Theme Builder code, of course.
 *
 * Hope Elementor is not next Visual Composer...
 *
 * @subpackage  Plugins
 * @subpackage  Page Builder
 * @subpackage  Theme Builder
 *
 * @package    Reykjavik
 * @copyright  WebMan Design, Oliver Juhas
 *
 * @since    1.1.0
 * @version  1.1.0
 *
 * Contents:
 *
 *   0) Init
 *  10) Upgrade
 *  20) Setup
 *  30) Display
 *  40) Getters
 * 100) Others
 */
class Reykjavik_Elementor {





	/**
	 * 0) Init
	 */

		private static $instance;



		/**
		 * Constructor
		 *
		 * @subpackage  Theme Builder
		 *
		 * @since    1.1.0
		 * @version  1.1.0
		 */
		private function __construct() {

			// Helper variables

				$theme_builder = self::get_theme_builder( false );


			// Processing

				// Hooks

					// Actions

						if ( $theme_builder ) {
							add_action( 'elementor/theme/register_locations', __CLASS__ . '::register_locations' );
							/**
							 * The `get_header` is the first action hook where `elementor_location_exits()`
							 * function is working and we still have time to dequeue assets and set up custom
							 * theme builder sections display.
							 */
							add_action( 'get_header', __CLASS__ . '::display_setup', -10 );
						}

					// Filters

						add_filter( 'wp_parse_str', __CLASS__ . '::upgrade_link' );

						if ( $theme_builder ) {
							add_filter( 'get_post_metadata', __CLASS__ . '::content_layout', 10, 3 );
						}

		} // /__construct



		/**
		 * Initialization (get instance)
		 *
		 * @since    1.1.0
		 * @version  1.1.0
		 */
		public static function init() {

			// Processing

				if ( null === self::$instance ) {
					self::$instance = new self;
				}


			// Output

				return self::$instance;

		} // /init





	/**
	 * 10) Upgrade
	 */

		/**
		 * Upgrade link
		 *
		 * By defining the `ELEMENTOR_PARTNER_ID` constant, Elementor's `Utils::get_pro_link()`
		 * method produces URL with incorrect `partner_id` argument. Should be `ref` instead.
		 *
		 * This is a temporary fix for the issue hooking onto `wp_parse_str` that is being used
		 * within `add_query_arg()` function. And hoping to hit Elementor's upgrade URL only
		 * by targeting URLs with `utm_campaign=gopro` argument...
		 *
		 * Waiting for Elementor to fix the `partner_id` URL argument.
		 *
		 * @since    1.1.0
		 * @version  1.1.0
		 *
		 * @param  array $url_args  The array populated with variables.
		 */
		public static function upgrade_link( $url_args = array() ) {

			// Processing

				if (
					defined( 'ELEMENTOR_PARTNER_ID' )
					&& isset( $url_args['utm_campaign'] )
					&& 'gopro' === $url_args['utm_campaign']
					&& ! isset( $url_args['ref'] )
				) {
					$url_args['ref'] = ELEMENTOR_PARTNER_ID;
				}


			// Output

				return $url_args;

		} // /upgrade_link





	/**
	 * 20) Setup
	 */

		/**
		 * Register locations
		 *
		 * @subpackage  Theme Builder
		 *
		 * @since    1.1.0
		 * @version  1.1.0
		 *
		 * @param  object $manager
		 */
		public static function register_locations( $manager ) {

			// Processing

				$manager->register_core_location( 'header' );
				$manager->register_core_location( 'footer' );

		} // /register_locations



		/**
		 * Setup display of custom sections
		 *
		 * @subpackage  Theme Builder
		 * @subpackage  Customize Options
		 *
		 * @since    1.1.0
		 * @version  1.1.0
		 */
		public static function display_setup() {

			// Helper variables

				$locations = self::get_locations();


			// Requirements check

				if ( empty( $locations ) ) {
					return;
				}


			// Processing

				foreach ( $locations as $location => $location_args ) {
					if ( self::is_location_active( $location ) ) {
						switch ( $location ) {

							/**
							 * Site header
							 *
							 * Removing theme header, displaying the theme builder one, dequeuing header
							 * related assets, and disabling theme's sticky header.
							 */
							case 'header':
								remove_all_actions( 'tha_header_top' );
								remove_all_actions( 'tha_header_bottom' );

								add_action( 'tha_header_top', __CLASS__ . '::header' );

								add_action( 'wp_enqueue_scripts', __CLASS__ . '::dequeue_header_scripts', 110 );
								break;

							/**
							 * Site footer
							 *
							 * Removing theme footer, displaying the theme builder one.
							 */
							case 'footer':
								remove_all_actions( 'tha_footer_top' );
								remove_all_actions( 'tha_footer_bottom' );

								add_action( 'tha_footer_top', __CLASS__ . '::footer' );
								break;

							/**
							 * Site content area (singulars and archives)
							 *
							 * For all the locations that are editable by Elementor in content area we need
							 * to remove theme content wrappers and all sections and elements hooked into
							 * THA content wrapper action hooks. This will also effectively remove a sidebar
							 * from such locations.
							 *
							 * As we do not register support for such locations (see `register_locations()`
							 * above), Elementor Theme Builder will take over the whole theme content area
							 * and display content as needed.
							 */
							default:
								if ( $location_args['edit_in_content'] ) {
									remove_all_actions( 'tha_content_before' );
									remove_all_actions( 'tha_content_top' );
									remove_all_actions( 'tha_content_bottom' );
									remove_all_actions( 'tha_content_after' );

									// We still need to disable sidebar for correct body class.
									add_filter( 'wmhook_reykjavik_sidebar_disable', '__return_true' );
								}
								break;

						}
					}
				}

		} // /display_setup





	/**
	 * 30) Display
	 */

		/**
		 * Display header
		 *
		 * @subpackage  Theme Builder
		 *
		 * @since    1.1.0
		 * @version  1.1.0
		 */
		public static function header() {

			// Processing

				elementor_theme_do_location( 'header' );

		} // /header



		/**
		 * Display footer
		 *
		 * @subpackage  Theme Builder
		 *
		 * @since    1.1.0
		 * @version  1.1.0
		 */
		public static function footer() {

			// Processing

				elementor_theme_do_location( 'footer' );

		} // /footer





	/**
	 * 40) Getters
	 */

		/**
		 * Get theme builder instance or check if it's loaded
		 *
		 * @subpackage  Theme Builder
		 *
		 * @since    1.1.0
		 * @version  1.1.0
		 *
		 * @param  boolean $get_instance  Get theme builder instance or just check if it's loaded?
		 */
		public static function get_theme_builder( $get_instance = true ) {

			// Output

				if ( $get_instance ) {
					return ElementorPro\Modules\ThemeBuilder\Module::instance();
				} else {
					return is_callable( 'ElementorPro\Modules\ThemeBuilder\Module::instance' );
				}

		} // /get_theme_builder



		/**
		 * Get locations
		 *
		 * @subpackage  Theme Builder
		 *
		 * @since    1.1.0
		 * @version  1.1.0
		 */
		public static function get_locations() {

			// Output

				return self::get_theme_builder()->get_locations_manager()->get_locations();

		} // /get_locations



		/**
		 * Get documents for specific location
		 *
		 * @subpackage  Theme Builder
		 *
		 * @since    1.1.0
		 * @version  1.1.0
		 *
		 * @param  string $location
		 */
		public static function get_documents_for_location( $location = '' ) {

			// Output

				return self::get_theme_builder()->get_conditions_manager()->get_documents_for_location( $location );

		} // /get_documents_for_location



		/**
		 * Does the location have any documents, is it active?
		 *
		 * @subpackage  Theme Builder
		 *
		 * @since    1.1.0
		 * @version  1.1.0
		 *
		 * @param  string $location
		 */
		public static function is_location_active( $location = '' ) {

			// Requirements check

				if (
					function_exists( 'elementor_location_exits' )
					&& ! elementor_location_exits( $location )
				) {
					return;
				}


			// Helper variables

				$documents = self::get_documents_for_location( $location );


			// Output

				return ! empty( $documents );

		} // /is_location_active





	/**
	 * 100) Others
	 */

		/**
		 * Dequeue theme header scripts
		 *
		 * @subpackage  Theme Builder
		 *
		 * @since    1.1.0
		 * @version  1.1.0
		 */
		public static function dequeue_header_scripts() {

			// Processing

				wp_dequeue_script( 'reykjavik-scripts-nav-a11y' );
				wp_dequeue_script( 'reykjavik-scripts-nav-mobile' );

		} // /dequeue_header_scripts



		/**
		 * Page builder content layout setup for Elementor templates
		 *
		 * @subpackage  Theme Builder
		 * @subpackage  Custom Fields
		 *
		 * @since    1.1.0
		 * @version  1.1.0
		 *
		 * @param  null|array|string $value
		 * @param  absint            $post_id
		 * @param  string            $meta_key
		 */
		public static function content_layout( $value = null, $post_id = 0, $meta_key = '' ) {

			// Processing

				if (
					'content_layout' === $meta_key
					&& 'elementor_library' === get_post_type( $post_id )
				) {
					return 'stretched';
				}


			// Output

				return $value;

		} // /content_layout





} // /Reykjavik_Elementor

add_action( 'after_setup_theme', 'Reykjavik_Elementor::init' );
