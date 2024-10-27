<?php
/**
 * Chocante Theme
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Chocante class.
 */
class Chocante {
	/**
	 * Class constructor.
	 */
	public static function init() {
		// Setup.
		add_action( 'after_setup_theme', array( self::class, 'load_textdomain' ) );
		add_action( 'after_setup_theme', array( self::class, 'register_nav_menus' ) );
		add_action( 'widgets_init', array( self::class, 'register_sidebars' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_scripts' ), 20 );

		// Custom logo.
		add_action( 'after_setup_theme', array( self::class, 'support_custom_logo' ) );
		add_filter( 'get_custom_logo_image_attributes', array( self::class, 'set_custom_logo_attributes' ), 10, 2 );

		// Menu.
		// add_filter( 'nav_menu_css_class', array( self::class, 'set_menu_item_class' ), 10, 2 );?
		// add_filter( 'nav_menu_submenu_css_class', array( self::class, 'set_submenu_class' ) );?
		if ( ! is_admin() ) {
			add_action( 'wp_footer', array( self::class, 'output_mobile_menu' ), 20 );
		}

		// WooCommerce.
		if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) ) {
			require plugin_dir_path( __FILE__ ) . 'class-chocante-woocommerce.php';
			Chocante_WooCommerce::init();

			// WooCommerce ACF.
			if ( class_exists( 'ACF' ) ) {
				require plugin_dir_path( __FILE__ ) . 'class-chocante-woocommerce-acf.php';
				Chocante_WooCommerce_ACF::init();
			}
		}
	}

	/**
	 * Load textdomain
	 */
	public static function load_textdomain() {
		load_theme_textdomain( 'chocante', get_stylesheet_directory() . '/languages' );
	}

	/**
	 * Log values to wp-content/debug.log
	 *
	 * @param mixed $data Value to log.
	 */
	public static function debug_log( $data ) {
		/* phpcs:disable */
		if ( true === WP_DEBUG ) {
			if ( is_array( $data ) || is_object( $data ) ) {
				error_log( print_r( $data, true ) );
			} else {
				error_log( $data );
			}
		}
		/* phpcs:enable */
	}

	/**
	 * Register menu locations
	 */
	public static function register_nav_menus() {
		register_nav_menus(
			array(
				'chocante_menu_main' => __( 'Main menu', 'chocante' ),
			)
		);
	}

	/**
	 * Add custom logo
	 */
	public static function support_custom_logo() {
		add_theme_support( 'custom-logo' );
	}

	/**
	 * Modify custom logo
	 *
	 * @param array $logo_atts Custom logo attributes.
	 * @param array $image_id Logo image ID.
	 * @return array
	 */
	public static function set_custom_logo_attributes( $logo_atts, $image_id ) {
		$logo = wp_get_attachment_metadata( $image_id );

		if ( $logo ) {
			$logo_atts['width']  = $logo['width'];
			$logo_atts['height'] = $logo['height'];
		}

		return $logo_atts;
	}

	/**
	 * Enqueue scripts & styles
	 */
	public static function enqueue_scripts() {
		$styles = include get_stylesheet_directory() . '/build/styles.asset.php';

		wp_enqueue_style(
			'chocante',
			get_stylesheet_directory_uri() . '/build/styles.css',
			$styles['dependencies'],
			$styles['version'],
		);

		$scripts = include get_stylesheet_directory() . '/build/scripts.asset.php';

		wp_enqueue_script(
			'chocante',
			get_stylesheet_directory_uri() . '/build/scripts.js',
			array_merge( $scripts['dependencies'], array( 'wc-cart-fragments' ) ),
			$scripts['version'],
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Register widget areas
	 */
	public static function register_sidebars() {
		// Header Top Nav.
		register_sidebar(
			array(
				'name'           => __( 'Header - top', 'chocante' ),
				'id'             => 'header-top',
				'description'    => __( 'Widgets in this area will be shown in the top part of header.', 'chocante' ),
				'before_widget'  => '',
				'after_widget'   => '',
				'before_sidebar' => '<aside class="site-header__top">',
				'after_sidebar'  => '</aside>',
			)
		);

		register_sidebar(
			array(
				'name'           => __( 'Mobile menu - after main menu', 'chocante' ),
				'id'             => 'mobile-menu-after-nav',
				'description'    => __( 'Widgets in this area will be shown in mobile menu after main navigation menu.', 'chocante' ),
				'before_widget'  => '',
				'after_widget'   => '',
				'before_sidebar' => '<aside class="mobile-menu__after-nav">',
				'after_sidebar'  => '</aside>',
			)
		);

		register_sidebar(
			array(
				'name'           => __( 'Mobile menu - top', 'chocante' ),
				'id'             => 'mobile-menu-top',
				'description'    => __( 'Widgets in this area will be shown in the top part of mobile menu.', 'chocante' ),
				'before_widget'  => '',
				'after_widget'   => '',
				'before_sidebar' => '<div class="mobile-menu__top-sidebar">',
				'after_sidebar'  => '</div>',
			)
		);
	}

	/**
	 * Insert SVG icon
	 *
	 * @param string $filename Filename from /icons directory.
	 */
	public static function icon( $filename ) {
		require get_stylesheet_directory() . "/icons/icon-{$filename}.svg";
	}

	/**
	 * Insert spinner
	 */
	public static function spinner() {
		echo '<img src="' . esc_url( get_stylesheet_directory_uri() . '/images/spinner-2x.gif' ) . '" alt="' . esc_attr__( 'Loading', 'chocante' ) . '" class="spinner">';
	}

	/**
	 * Clean menu list item classes
	 *
	 * @param array $classes List of menu item classes.
	 * @return array
	 */
	public static function set_menu_item_class( $classes ) {
		return array_intersect( array( 'menu-item-has-children' ), $classes );
	}

	/**
	 * Clean sub-menu classes
	 *
	 * @param array $classes List of sub-menu classes.
	 * @return array
	 */
	public static function set_submenu_class( $classes ) {
		$classes = array();

		return $classes;
	}

	/**
	 * Output mobile menu
	 */
	public static function output_mobile_menu() {
		get_template_part( 'template-parts/mobile-menu' );
	}

	/**
	 * Display page title
	 */
	public static function get_page_title() {
		$title = '<h1>' . get_the_title() . '</h1>';

		echo wp_kses_post( apply_filters( 'chocante_page_title', $title, get_the_title() ) );
	}
}
