<?php
/**
 * Layout hooks - product slider section
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Layout\ProductSection;

use Chocante\Assets_Handler;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

const CACHE_GROUP = 'chocante_products';

add_action( 'wp_ajax_get_product_section', __NAMESPACE__ . '\ajax_get_product_section' );
add_action( 'wp_ajax_nopriv_get_product_section', __NAMESPACE__ . '\ajax_get_product_section' );
add_action( 'init', __NAMESPACE__ . '\add_shortcodes' );
add_action( 'chocante_featured_products_content', '\Chocante\ProductTags\display_diet_icons', 10 );

// Cache flush.
add_action( 'woocommerce_product_object_updated_props', __NAMESPACE__ . '\clear_cached_products_on_props_change', 10, 2 );
add_action( 'before_delete_post', __NAMESPACE__ . '\clear_cached_products_on_delete' );
add_action( 'wc_after_products_starting_sales', __NAMESPACE__ . '\clear_cached_products' );
add_action( 'wc_after_products_ending_sales', __NAMESPACE__ . '\clear_cached_products' );

/**
 * Display product section
 *
 * @param array  $args Product section arguments.
 * @param string $content Product section description text.
 */
function display_product_section( $args = array(), $content = '' ) {
	$product_section = Assets_Handler::include( 'product-section' );

	wp_enqueue_script(
		'product-section',
		get_theme_file_uri( 'build/product-section.js' ),
		array_merge( $product_section['dependencies'] ),
		$product_section['version'],
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	$script_data = array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
	);

	if ( is_product() ) {
		$script_data['productId'] = get_the_ID();
	}

	/**
	 * Filter for adding data.
	 */
	$script_data = apply_filters( 'chocante_product_section_script_data', $script_data );

	wp_localize_script(
		'product-section',
		'chocante',
		$script_data
	);

	get_template_part(
		'template-parts/product',
		'section',
		array(
			'heading'       => isset( $args['heading'] ) ? $args['heading'] : __( 'Related products', 'woocommerce' ),
			'subheading'    => isset( $args['subheading'] ) ? $args['subheading'] : null,
			'cta_link'      => isset( $args['cta_link'] ) ? $args['cta_link'] : get_permalink( wc_get_page_id( 'shop' ) ),
			'cta_text'      => isset( $args['cta_text'] ) ? $args['cta_text'] : __( 'View products', 'woocommerce' ),
			'content'       => shortcode_unautop( $content ),
			'filters'       => get_product_section_atts(
				array(
					'category' => isset( $args['category'] ) ? $args['category'] : null,
					'featured' => isset( $args['featured'] ) ? $args['featured'] : null,
					'onsale'   => isset( $args['onsale'] ) ? $args['onsale'] : null,
					'latest'   => isset( $args['latest'] ) ? $args['latest'] : null,
					'exclude'  => isset( $args['exclude'] ) ? $args['exclude'] : null,
				)
			),
			'section_id'    => isset( $args['section_id'] ) ? $args['section_id'] : null,
			'section_class' => isset( $args['section_class'] ) ? " {$args['section_class']}" : '',
		)
	);
}

/**
 * Fetch products and render template
 *
 * @param array   $category Product category IDs.
 * @param boolean $featured Whether to include featured products.
 * @param boolean $onsale Include only products that are on sale.
 * @param boolean $latest Get newly added products.
 * @param array   $exclude Product IDs to exclude.
 * @return string
 */
function get_product_section( $category = array(), $featured = false, $onsale = false, $latest = false, $exclude = array() ) {
	/**
	 * Hook before getting products to display.
	 */
	do_action( 'chocante_product_section_ajax_get' );

	$products = get_products( $category, $featured, $onsale, $latest, $exclude );

	add_filter( 'woocommerce_post_class', __NAMESPACE__ . '\slider_item_class' );

	ob_start();
	get_template_part(
		'template-parts/product',
		'slider',
		array(
			'products' => $products,
			'labels'   => get_slider_labels(),
		)
	);

	remove_filter( 'woocommerce_post_class', __NAMESPACE__ . '\slider_item_class' );

	return ob_get_clean();
}

/**
 * Fetch products from database
 *
 * @param array   $category Product category IDs.
 * @param boolean $featured Whether to include featured products.
 * @param boolean $onsale Include only products that are on sale.
 * @param boolean $latest Get newly added products.
 * @param array   $exclude Product IDs to exclude.
 * @return array
 */
function get_products( $category = array(), $featured = false, $onsale = false, $latest = false, $exclude = array() ) {
	$limit   = 12;
	$orderby = $latest ? 'id' : 'rand';
	$order   = 'desc';

	$args = array(
		'limit'      => $limit,
		'orderby'    => $orderby,
		'order'      => $order,
		'visibility' => 'visibile',
		'status'     => 'publish',
	);

	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
		$args['stock_status'] = 'instock';
	}

	if ( ! empty( $category ) ) {
		$args['product_category_id'] = $category;
	}

	if ( $featured ) {
		$args['featured'] = $featured;
	}

	if ( $onsale ) {
		$args['include'] = wc_get_product_ids_on_sale();
	}

	if ( ! empty( $exclude ) ) {
		$args['exclude'] = $exclude;
	}

	// WPML support.
	$lang = apply_filters( 'wpml_current_language', null );

	$cache_key = md5( wp_json_encode( array( ...func_get_args(), $lang ) ) );
	$products  = wp_cache_get( $cache_key, CACHE_GROUP );

	if ( false === $products ) {
		$products = wc_get_products( $args );
		wp_cache_set( $cache_key, $products, CACHE_GROUP );
	}

	return $products;
}

/**
 * Return aria labels for product slider
 */
function get_slider_labels() {
	return array(
		'prev'       => _x( 'Previous', 'product slider', 'chocante' ),
		'next'       => _x( 'Next', 'product slider', 'chocante' ),
		'first'      => _x( 'Go to first', 'product slider', 'chocante' ),
		'last'       => _x( 'Go to last', 'product slider', 'chocante' ),
		// translators: Go to product number.
		'slideX'     => _x( 'Go to %s', 'product slider', 'chocante' ),
		// translators: Go to page number.
		'pageX'      => _x( 'Go to page %s', 'product slider', 'chocante' ),
		'play'       => _x( 'Start autoplay', 'product slider', 'chocante' ),
		'pause'      => _x( 'Pause autoplay', 'product slider', 'chocante' ),
		'carousel'   => _x( 'carousel', 'product slider', 'chocante' ),
		'select'     => _x( 'Select product to show', 'product slider', 'chocante' ),
		'slide'      => _x( 'product', 'product slider', 'chocante' ),
		'slideLabel' => '%s / %s',
	);
}

/**
 * Return products HTML using AJAX
 *
 * @phpcs:disable WordPress.Security.NonceVerification.Recommended
 */
function ajax_get_product_section() {
	$category = isset( $_GET['category'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_GET['category'] ) ) ) : array();
	$featured = isset( $_GET['featured'] ) ? sanitize_text_field( wp_unslash( $_GET['featured'] ) ) : false;
	$onsale   = isset( $_GET['onsale'] ) ? sanitize_text_field( wp_unslash( $_GET['onsale'] ) ) : false;
	$latest   = isset( $_GET['latest'] ) ? sanitize_text_field( wp_unslash( $_GET['latest'] ) ) : false;
	$exclude  = isset( $_GET['exclude'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_GET['exclude'] ) ) ) : array();

	echo wp_kses_post( get_product_section( $category, $featured, $onsale, $latest, $exclude ) );

	wp_die();
}

/**
 * Clear cached products used in sliders
 */
function clear_cached_products() {
	if ( wp_cache_supports( 'flush_group' ) ) {
		wp_cache_flush_group( CACHE_GROUP );
	} else {
		wp_cache_flush();
	}

	/**
		* Hook after clearing product data from cache.
		*/
	do_action( 'chocante_product_section_cache_flush' );
}

/**
 * Clear cached products used in sliders on admin changes or when stock status changes
 *
 * @param WC_Product $product Product object.
 * @param array      $props Updated props.
 */
function clear_cached_products_on_props_change( $product, $props ) {
	static $purged = false;

	if ( $purged ) {
		return;
	}

	if ( is_admin() || ( in_array( 'stock_status', $props, true ) ) ) {
		clear_cached_products();
		$purged = true;
	}
}

/**
 * Clear cached products used in sliders on admin changes or when stock status changes
 *
 * @param int $post_id Post ID.
 */
function clear_cached_products_on_delete( $post_id ) {
	if ( get_post_type( $post_id ) === 'product' ) {
		clear_cached_products();
	}
}

/**
 * Add slider class to loop items
 *
 * @param string[] $classes Loop item classes.
 * @return string[]
 */
function slider_item_class( $classes ) {
	return array_merge( $classes, array( 'splide__slide' ) );
}

/**
 * Output data attribute to product section element
 *
 * @param array $args Product query attributes.
 * @return string
 */
function get_product_section_atts( $args ) {
	$data_atts = '';

	foreach ( $args as $key => $att ) {
		if ( ! isset( $att ) ) {
			continue;
		}

		switch ( gettype( $att ) ) {
			case 'boolean':
				$value = $att ? 'true' : 'false';
				break;
			case 'array':
				$value = implode( ',', $att );
				break;
			default:
				$value = $att;
		}

		$data_atts .= " data-{$key}={$value}";
	}

	return $data_atts;
}

/**
 * Add shortcodes for template parts
 */
function add_shortcodes() {
	// [chocante_product_section] shortcode.
	add_shortcode(
		'chocante_product_section',
		function ( $atts, $content ) {
			ob_start();
			display_product_section( $atts, $content );
			return ob_get_clean();
		}
	);

	// [chocante_featured_products] shortcode.
	add_shortcode(
		'chocante_featured_products',
		function () {
			ob_start();
			get_template_part( 'template-parts/slider', 'featured-products' );
			return ob_get_clean();
		}
	);
}
