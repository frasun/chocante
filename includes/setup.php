<?php
/**
 * Theme setup
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Setup;

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', __NAMESPACE__ . '\load_textdomain' );
add_action( 'after_setup_theme', __NAMESPACE__ . '\add_feature_support' );

/**
 * Load textdomain
 */
function load_textdomain() {
	load_theme_textdomain( 'chocante', get_theme_file_path( 'languages' ) );
}

/**
 * WP features support
 */
function add_feature_support() {
	// Theme.
	add_theme_support( 'title-tag' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'post-formats', array( 'aside', 'gallery', 'link', 'image', 'quote', 'status', 'video', 'audio', 'chat' ) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'block-template-parts' );
	add_theme_support( 'custom-line-height' );
	add_theme_support( 'custom-logo' );
	add_theme_support( 'post-thumbnails' );
	remove_theme_support( 'core-block-patterns' );

	// Posts.
	add_post_type_support( 'page', 'excerpt' );

	// WooCommerce.
	add_theme_support( 'woocommerce' );
	remove_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-slider' );
	add_theme_support( 'wc-product-gallery-lightbox' );

	// RankMath SEO.
	add_theme_support( 'rank-math-breadcrumbs' );
}
