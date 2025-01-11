<?php
// @todo: Chocante - Bricks - remove.
/**
 * Register/enqueue custom scripts and styles
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( class_exists( 'Chocante_WooCommerce' ) ) {
			if ( Chocante_WooCommerce::bricks_disabled() ) {
				wp_enqueue_style( 'bricks-child', get_stylesheet_uri(), array(), filemtime( get_stylesheet_directory() . '/style.css' ) );
			} elseif ( ! bricks_is_builder_main() ) {
				wp_enqueue_style( 'bricks-child', get_stylesheet_uri(), array(), filemtime( get_stylesheet_directory() . '/style.css' ) );
			}
		} elseif ( ! bricks_is_builder_main() ) {
			wp_enqueue_style( 'bricks-child', get_stylesheet_uri(), array( 'bricks-frontend' ), filemtime( get_stylesheet_directory() . '/style.css' ) );
		}
	}
);

/**
 * Register custom elements
 */
add_action(
	'init',
	function () {
		$element_files = array(
			__DIR__ . '/elements/title.php',
		);

		foreach ( $element_files as $file ) {
			\Bricks\Elements::register_element( $file );
		}
	},
	11
);

/**
 * Add text strings to builder
 */
add_filter(
	'bricks/builder/i18n',
	function ( $i18n ) {
		// For element category 'custom'.
		$i18n['custom'] = esc_html__( 'Custom', 'bricks' );

		return $i18n;
	}
);
// END TODO.

/**
 * Add media sizes for slider images
 * Remove unused default media sizes
 */
add_action(
	'init',
	function () {
		remove_image_size( '1536x1536' );
		remove_image_size( '2048x2048' );
		add_image_size( 'slider', 600, 700, true );
		add_image_size( 'slider_mobile', 460, 460, true );
	}
);

/**
 * Chocante Theme
 *
 * @package Chocante_Theme
 */
require_once get_stylesheet_directory() . '/includes/class-chocante.php';
Chocante::init();

// ACF.
if ( class_exists( 'ACF' ) ) {
	require_once get_stylesheet_directory() . '/includes/class-chocante-acf.php';
	Chocante_ACF::init();
}
