<?php
/**
 * Menu settings
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Menu;

use function Chocante\Assets\icon;

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', __NAMESPACE__ . '\register_menus' );

if ( ! is_admin() ) {
	add_action( 'wp_footer', __NAMESPACE__ . '\output_mobile_menu', 20 );
	add_filter( 'nav_menu_item_title', __NAMESPACE__ . '\social_media_icons', 10, 3 );
	add_filter( 'nav_menu_link_attributes', __NAMESPACE__ . '\social_media_aria', 10, 3 );
}

/**
 * Register menu locations
 */
function register_menus() {
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
 * Output mobile menu
 */
function output_mobile_menu() {
	get_template_part( 'template-parts/mobile-menu' );
}

/**
 * Display social media icons instead of title
 *
 * @param string   $title     The menu item's title.
 * @param WP_Post  $menu_item The current menu item object.
 * @param stdClass $args      An object of wp_nav_menu() arguments.
 * @return string
 */
function social_media_icons( $title, $menu_item, $args ) {
	if ( 'chocante_footer_social' === $args->theme_location ) {
		ob_start();
		icon( $menu_item->post_name );
		$icon = ob_get_clean();

		if ( ! empty( $icon ) ) {
			return $icon;
		}
	}

	return $title;
}

	/**
	 * Display aria labels in social media links
	 *
	 * @param array    $atts The HTML attributes applied to the menu item’s <a> element, empty strings are ignored.
	 * @param WP_Post  $menu_item The current menu item object.
	 * @param stdClass $args      An object of wp_nav_menu() arguments.
	 * @return array
	 */
function social_media_aria( $atts, $menu_item, $args ) {
	if ( 'chocante_footer_social' === $args->theme_location ) {
		$atts['aria-label'] = esc_attr( $menu_item->title );
	}

	return $atts;
}
