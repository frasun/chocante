<?php
/**
 * Layout hooks - blog
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Layout\Blog;

use function Chocante\Layout\ProductSection\display_product_section;

defined( 'ABSPATH' ) || exit;

add_action( 'pre_get_posts', __NAMESPACE__ . '\exclude_sticky_posts' );
add_filter( 'excerpt_more', __NAMESPACE__ . '\set_excerpt_more' );
add_action( 'chocante_after_main', __NAMESPACE__ . '\display_featured_products_on_blog_page' );
add_filter( 'the_content', __NAMESPACE__ . '\display_post_header' );
add_action( 'init', __NAMESPACE__ . '\add_shortcodes' );

/**
 * Exclude sticky posts from main blog query
 *
 * @param WP_Query $query Query.
 */
function exclude_sticky_posts( $query ) {
	if ( $query->is_home() && $query->is_main_query() ) {
		$query->set( 'post__not_in', get_option( 'sticky_posts' ) );
	}
}

/**
 * Change the excerpt more string
 */
function set_excerpt_more() {
	return '&hellip;';
}

/**
 * Display featured products on blog home page
 */
function display_featured_products_on_blog_page() {
	if ( class_exists( 'WooCommerce' ) && is_home() ) {
		display_product_section(
			array(
				'heading'    => _x( 'Featured products', 'product slider', 'chocante' ),
				'subheading' => _x( 'Learn more about our offer', 'product slider', 'chocante' ),
				'cta_link'   => wc_get_page_permalink( 'shop' ),
			)
		);
	}
}

/**
 * Display single post content header
 *
 * @param string $content Post content.
 * @return string
 */
function display_post_header( $content ) {
	if ( is_singular( 'post' ) && in_the_loop() && is_main_query() ) {
		ob_start();
		get_template_part( 'template-parts/post-header', '', array( 'content' => $content ) );
		return ob_get_clean() . $content;
	}

	return $content;
}

/**
 * Add shortcodes for template parts
 */
function add_shortcodes() {
	// [chocante_post_slider] shortcode.
	add_shortcode(
		'chocante_post_slider',
		function () {
			ob_start();
			get_template_part( 'template-parts/post-slider' );
			return ob_get_clean();
		}
	);
}

/**
 * Return estimated content reading time
 *
 * @param string $content Text content.
 * @param int    $words_per_minute Number of words per minute.
 * @return float
 */
function get_reading_time( $content, $words_per_minute = 200 ) {
	$total_words = count( preg_split( '~[^\p{L}\p{N}\']+~u', wp_strip_all_tags( $content ) ) );

	return floor( $total_words / $words_per_minute );
}
