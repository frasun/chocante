<?php
/**
 * Layout hooks - product
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Layout\Product;

use function Chocante\Layout\ProductSection\display_product_section;
use function Chocante\Woo\get_variation_name;

defined( 'ABSPATH' ) || exit;

remove_action( 'woocommerce_before_single_product', 'woocommerce_output_all_notices' );
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
add_action( 'woocommerce_before_single_product_summary', 'woocommerce_output_all_notices', 5 );
add_action( 'woocommerce_before_single_product_summary', 'Chocante\Woo\display_shop_breadcrumbs', 7 );
add_action( 'woocommerce_before_single_product_summary', __NAMESPACE__ . '\open_product_info_section', 9 );
add_action( 'woocommerce_before_single_product_summary', __NAMESPACE__ . '\open_product_header', 13 );
add_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 14 );
add_action( 'woocommerce_before_single_product_summary', 'woocommerce_template_single_title', 16 );
add_action( 'woocommerce_before_single_product_summary', __NAMESPACE__ . '\close_product_header', 18 );
add_action( 'woocommerce_before_single_product_summary', '\Chocante\ProductTags\display_diet_icons_product_page', 25 );

remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 25 );
add_action( 'woocommerce_single_product_summary', __NAMESPACE__ . '\display_product_info', 30 );
add_action( 'woocommerce_single_product_summary', __NAMESPACE__ . '\display_product_attributes', 35 );

remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
add_action( 'woocommerce_after_single_product_summary', __NAMESPACE__ . '\close_product_info_section', 30 );

add_action( 'woocommerce_after_single_product', __NAMESPACE__ . '\display_related_products', 10 );
add_action( 'woocommerce_after_single_product', __NAMESPACE__ . '\output_product_description', 20 );

// Product variation.
add_action( 'woocommerce_after_variations_table', 'woocommerce_single_variation', 10 );
add_action( 'woocommerce_after_variations_table', 'woocommerce_single_variation_add_to_cart_button', 20 );
add_filter( 'woocommerce_show_variation_price', '__return_true' );
add_filter( 'woocommerce_reset_variations_link', '__return_false' );
add_filter( 'woocommerce_dropdown_variation_attribute_options_args', __NAMESPACE__ . '\hide_dropdown_placeholder' );
add_filter( 'woocommerce_dropdown_variation_attribute_options_args', __NAMESPACE__ . '\select_variation_from_url' );
add_filter( 'woocommerce_available_variation', __NAMESPACE__ . '\filter_variation_data' );
add_action( 'chocante_product_variations_json', __NAMESPACE__ . '\print_product_variations' );

// Product attributes.
add_filter( 'woocommerce_display_product_attributes', __NAMESPACE__ . '\display_weight_in_attributes', 10 );
add_filter( 'woocommerce_format_weight', __NAMESPACE__ . '\format_weight_dimension', 10, 2 );

// Product gallery.
add_filter( 'woocommerce_gallery_image_html_attachment_image_params', __NAMESPACE__ . '\add_atts_to_main_image', 10, 4 );

// Product stock.
add_filter( 'woocommerce_get_availability_text', __NAMESPACE__ . '\get_stock_text', 10, 2 );
add_filter( 'woocommerce_get_availability_text', __NAMESPACE__ . '\display_low_amount_info', 20, 2 );
add_action( 'chocante_product_stock', __NAMESPACE__ . '\get_product_stock' );

// AJAX add to cart.
add_action( 'woocommerce_ajax_added_to_cart', __NAMESPACE__ . '\make_success_notice_on_add_to_cart' );
add_filter( 'woocommerce_cart_redirect_after_error', __NAMESPACE__ . '\make_error_notice_on_add_to_cart' );
add_filter( 'woocommerce_add_to_cart_fragments', __NAMESPACE__ . '\add_fragments_with_add_to_cart_notices' );

/**
 * Open product info section
 */
function open_product_info_section() {
	echo '<section class="product__summary">';
}

/**
 * Open product page header
 */
function open_product_header() {
	echo '<header class="product__header">';
}

/**
 * Close product info section
 */
function close_product_info_section() {
	echo '</section>';
}

/**
 * Close product page header
 */
function close_product_header() {
	echo '</header>';
}

/**
 * Display additional information on product page
 */
function display_product_info() {
	get_template_part( 'template-parts/info', 'product' );
}

/**
 * Display product attributes on product page
 */
function display_product_attributes() {
	get_template_part( 'template-parts/product', 'details' );
}

/**
 * Display related products
 */
function display_related_products() {
	global $product;

	$product_categories = get_the_terms( $product->get_id(), 'product_cat' );

	if ( is_wp_error( $product_categories ) ) {
		return;
	}

	$heading  = join(
		', ',
		array_map(
			function ( $cat ) {
				return sprintf( '<span>%s</span>', $cat->name );
			},
			$product_categories
		)
	);
	$cta_link = esc_url( get_permalink( wc_get_page_id( 'shop' ) ) . '?filter_product_cat=' . join( ',', wp_list_pluck( $product_categories, 'slug' ) ) );

	display_product_section(
		array(
			'heading'    => $heading,
			'subheading' => _x( 'Products from category', 'product slider', 'chocante' ),
			'cta_link'   => $cta_link,
			'category'   => wp_list_pluck( $product_categories, 'term_id' ),
		)
	);
}

/**
 * Display additional description on product page
 */
function output_product_description() {
	get_template_part( 'template-parts/product', 'description' );
}

/**
 * Replace product weight attribute with product weight dimension
 * Account for missing weight attribute and preserve the ordering - after the pa_brand
 *
 * @todo Revisit when sorting product attributes and use variation attribute only if weight not available.
 *
 * @param array $product_attributes Product attributes.
 * @return array
 */
function display_weight_in_attributes( $product_attributes ) {
	$product_weight_att = 'attribute_pa_waga';
	$product_brand_att  = 'attribute_pa_brand';
	$weight_att         = 'weight';

	if ( ! isset( $product_attributes[ $weight_att ] ) ) {
		return $product_attributes;
	}

	$weight                      = $product_attributes[ $weight_att ];
	$original_product_attributes = $product_attributes;

	unset( $product_attributes[ $weight_att ] );

	$index = array_search( $product_weight_att, array_keys( $product_attributes ), true );
	if ( false !== $index ) {
		unset( $product_attributes[ $product_weight_att ] );
	} else {
		$index = array_search( $product_brand_att, array_keys( $product_attributes ), true );
		if ( false === $index ) {
			return $original_product_attributes;
		} else {
			++$index;
		}
	}

	$before             = array_slice( $product_attributes, 0, $index, true );
	$after              = array_slice( $product_attributes, $index, null, true );
	$product_attributes = $before + array( $weight_att => $weight ) + $after;

	return $product_attributes;
}

/**
 * Format weight dimension to show grams when vale is below 1 kg
 *
 * @param string $weight_string Weight dimension string.
 * @param float  $weight Weight.
 *
 * @return string
 */
function format_weight_dimension( $weight_string, $weight ) {
	$w = floatval( $weight );

	if ( $w > 0 && $w < 1 ) {
		return $w * 1000 . ' g';
	}

	return $weight_string;
}

/**
 * Add LCP attributes to main product image
 *
 * @param array  $image_attributes Attributes for the image markup.
 * @param int    $attachment_id Attachment ID.
 * @param string $image_size Image size.
 * @param bool   $main_image Is this the main image or a thumbnail?.
 * @return array
 */
function add_atts_to_main_image( $image_attributes, $attachment_id, $image_size, $main_image ) {
	if ( $main_image ) {
		$image_attributes['fetchpriority'] = 'high';
	} else {
		$image_attributes['loading'] = 'lazy';
	}

	return $image_attributes;
}

/**
 * Modify quantity text by adding variation info
 *
 * @param string     $availability Availability text.
 * @param WC_Product $product Product object.
 */
function get_stock_text( $availability, $product ) {
	/**
	 * Return any content so that the .stock element gets printed and can be used in JS for replacing with selected variation data.
	 *
	 * @see: wc_get_stock_html()
	 */
	if ( $product instanceof \WC_Product_Variable && empty( $availability ) ) {
		return ' ';
	}

	if ( $product->managing_stock() && $product->is_in_stock() && ! $product->is_on_backorder( 1 ) ) {
		// translators: Number of pieces.
		$stock_amount   = sprintf( __( '%s pcs', 'chocante' ), $product->get_stock_quantity() );
		$variation_name = get_variation_name( $product );

		if ( ! $variation_name ) {
			return $stock_amount;
		}

		return apply_filters( 'chocante_get_stock_text', "{$variation_name} &times; {$stock_amount}" );
	}

	return $availability;
}

/**
 * Display message about low amount left in stock
 *
 * @param string      $availability Availability text.
 * @param \WC_Product $product Product object.
 */
function display_low_amount_info( $availability, $product ) {
	if ( ! empty( trim( $availability ) ) && $product->managing_stock() && $product->is_in_stock() && ! $product->is_on_backorder( 1 ) ) {
		$stock_amount = $product->get_stock_quantity();

		if ( $stock_amount <= wc_get_low_stock_amount( $product ) ) {
			$availability .= '<br /><strong class="low-amount">' . __( 'Last items in stock!', 'chocante' ) . '</strong>';
		}
	}

	return $availability;
}

/**
 * Hide variations dropdown placeholder
 *
 * @param array $args Variation attribute options.
 * @return array
 */
function hide_dropdown_placeholder( $args ) {
	$args['show_option_none'] = false;

	return $args;
}

/**
 * Select product variation on page load
 *
 * Preselect variation:
 * 1. Variation param set in the url and available
 * 2. Default variation is defined and available
 * 3. Take first available variation
 *
 * @param array $args Variation attribute options.
 * @return array
 */
function select_variation_from_url( $args ) {
	$product       = $args['product'];
	$attribute_key = 'attribute_' . $args['attribute'];
	$children      = $product->get_visible_children();

	if ( empty( $children ) ) {
		return $args;
	}

	$args['options'] = array();

	foreach ( $children as $variation_id ) {
		$variation_option = get_post_meta( $variation_id, $attribute_key, true );

		if ( $variation_option ) {
			$args['options'][] = $variation_option;
		}
	}

	// 1. Variation param set in the url and available.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( isset( $_REQUEST[ $attribute_key ] ) && in_array( wc_clean( wp_unslash( $_REQUEST[ $attribute_key ] ) ), $args['options'], true ) ) {
		return $args;
	}

	// 2. Default variation is defined and available
	$default_attribute = $product->get_variation_default_attribute( $args['attribute'] );
	if ( in_array( $default_attribute, $args['options'], true ) ) {
		$args['selected'] = $default_attribute;
		return $args;
	}

	// 3. Take first available variation.
	$args['selected'] = reset( $args['options'] );

	return $args;
}

/**
 * Filter variation data used in add to cart on product page.
 *
 * @param array $variation_data Array of variation data.
 * @return array
 */
function filter_variation_data( $variation_data ) {
	unset(
		$variation_data['dimensions'],
		$variation_data['dimensions_html'],
		$variation_data['image'],
		$variation_data['image_id'],
		$variation_data['is_downloadable'],
		$variation_data['is_sold_individually'],
		$variation_data['is_virtual'],
	);

	return $variation_data;
}

/**
 * Output product variations json
 */
function print_product_variations() {
	global $product;

	if ( ! $product instanceof \WC_Product_Variable ) {
		return;
	}

	$get_variations       = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
	$available_variations = $get_variations ? $product->get_available_variations() : false;
	$variations_json      = wp_json_encode( $available_variations );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
}

/**
 * Display simple product stock amount info
 */
function get_product_stock() {
	global $product;
	echo wp_kses_post( wc_get_stock_html( $product ) );
}

/**
 * Add notice on successful add to cart.
 *
 * @param int $product_id ID of added product.
 */
function make_success_notice_on_add_to_cart( $product_id ) {
  // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$quantity = empty( $_POST['quantity'] ) ? 1 : wc_stock_amount( wp_unslash( $_POST['quantity'] ) );

	wc_add_to_cart_message( array( $product_id => $quantity ), true );
}

/**
 * Add notice on error add to cart.
 */
function make_error_notice_on_add_to_cart() {
	\WC_AJAX::get_refreshed_fragments();

	return false;
}

/**
 * Include add to cart notices in cart fragments
 *
 * @param array $fragments WC fragments.
 * @return array
 */
function add_fragments_with_add_to_cart_notices( $fragments ) {
	if ( wc_notice_count() > 0 ) {
		$notices_html = wc_print_notices( true );

		if ( ! empty( $notices_html ) ) {
			$fragments['add-to-cart'] = $notices_html;
		}
	}

	return $fragments;
}

/**
 * Get product images
 *
 * @param WC_Product $product Product object.
 */
function get_product_image_ids( $product ) {
	$images            = array();
	$post_thumbnail_id = $product->get_image_id();

	if ( $post_thumbnail_id ) {
		$images[] = $post_thumbnail_id;
	}

	$gallery_image_ids = $product->get_gallery_image_ids();

	if ( ! empty( $gallery_image_ids ) ) {
		$images = array_merge( $images, $gallery_image_ids );
	}

	return $images;
}

/**
 * Get product gallery image html
 *
 * @param int    $attachment_id Product gallery images.
 * @param string $alt_text Image alt attribute.
 * @param bool   $is_main Main product image.
 */
function display_product_gallery_image( $attachment_id, $alt_text, $is_main ) {
	$image_size   = apply_filters( 'woocommerce_gallery_image_size', 'woocommerce_single' );
	$full_size    = apply_filters( 'woocommerce_gallery_full_size', apply_filters( 'woocommerce_product_thumbnails_large_size', 'full' ) );
	$image_params = apply_filters(
		'woocommerce_gallery_image_html_attachment_image_params',
		array(
			'alt' => esc_attr( $alt_text ),
		),
		$attachment_id,
		$image_size,
		$is_main
	);
	$full_image   = wp_get_attachment_image_src( $attachment_id, $full_size );

	printf( '<a href="%s" data-pswp-width="%s" data-pswp-height="%s">', esc_url( $full_image[0] ), esc_attr( $full_image[1] ), esc_attr( $full_image[2] ) );
	echo wp_get_attachment_image(
		$attachment_id,
		$image_size,
		false,
		$image_params
	);
	echo '</a>';
}
