<?php
/**
 * Chocante Theme
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chocante class.
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
			add_filter( 'nav_menu_item_title', array( self::class, 'social_media_icons' ), 10, 3 );
		}

		// Footer.
		add_action( 'chocante_before_footer', array( self::class, 'display_join_group' ) );

		// Images.
		if ( ! is_admin() ) {
			add_filter( 'wp_get_attachment_image_attributes', array( self::class, 'set_image_lazy_loading' ) );
		}

		// WooCommerce.
		if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) ) {
			require_once __DIR__ . '/class-chocante-woocommerce.php';
			Chocante_WooCommerce::init();
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
				'chocante_menu_main'       => _x( 'Main menu', 'menu', 'chocante' ),
				'chocante_footer_chocante' => _x( 'Footer - Chocante', 'menu', 'chocante' ),
				'chocante_footer_products' => _x( 'Footer - Products', 'menu', 'chocante' ),
				'chocante_footer_shop'     => _x( 'Footer - Shop', 'menu', 'chocante' ),
				'chocante_footer_social'   => _x( 'Footer - Social media', 'menu', 'chocante' ),
				'chocante_footer_terms'    => _x( 'Footer - Terms', 'menu', 'chocante' ),
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
		$styles = include get_stylesheet_directory() . '/build/chocante.asset.php';

		wp_enqueue_style(
			'chocante-css',
			get_stylesheet_directory_uri() . '/build/chocante.css',
			$styles['dependencies'],
			$styles['version'],
		);

		$scripts = include get_stylesheet_directory() . '/build/chocante-scripts.asset.php';

		wp_enqueue_script(
			'chocante-js',
			get_stylesheet_directory_uri() . '/build/chocante-scripts.js',
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

		register_sidebar(
			array(
				'name'           => __( 'Footer - bottom left', 'chocante' ),
				'id'             => 'footer-bottom-left',
				'description'    => __( 'Widgets in this area will be shown in the bottom left part of footer.', 'chocante' ),
				'before_widget'  => '',
				'after_widget'   => '',
				'before_sidebar' => '<div class="site-footer__bottom-left">',
				'after_sidebar'  => '</div>',
			)
		);

		register_sidebar(
			array(
				'name'           => __( 'Footer - bottom mobile', 'chocante' ),
				'id'             => 'footer-bottom-mobile',
				'description'    => __( 'Widgets in this area will be shown in the bottom right part of footer.', 'chocante' ),
				'before_widget'  => '',
				'after_widget'   => '',
				'before_sidebar' => '<div class="site-footer__bottom-mobile">',
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
		$file = get_stylesheet_directory() . "/icons/icon-{$filename}.svg";

		if ( file_exists( $file ) ) {
			include $file;
		}
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

	/**
	 * Add lazy loading to images.
	 *
	 * @param array $atts Image attributes.
	 * @return array
	 */
	public static function set_image_lazy_loading( $atts ) {
		// @todo: Chocante - Bricks hack.
		if ( class_exists( 'Chocante_WooCommerce' ) && Chocante_WooCommerce::bricks_disabled() ) {
			$atts['_brx_disable_lazy_loading'] = true;
		}
		// END TODO.

		$atts['loading'] = 'lazy';

		return $atts;
	}

	/**
	 * Display join Facebook group section
	 */
	public static function display_join_group() {
		get_template_part( 'template-parts/join', 'group' );
	}

	/**
	 * Display social media icons instead of title
	 *
	 * @param string   $title     The menu item's title.
	 * @param WP_Post  $menu_item The current menu item object.
	 * @param stdClass $args      An object of wp_nav_menu() arguments.
	 * @return string
	 */
	public static function social_media_icons( $title, $menu_item, $args ) {
		if ( 'chocante_footer_social' === $args->theme_location ) {
			ob_start();
			self::icon( $menu_item->post_name );
			$icon = ob_get_clean();

			if ( ! empty( $icon ) ) {
				return $icon;
			}
		}

		return $title;
	}
}
