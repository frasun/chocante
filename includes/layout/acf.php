<?php
/**
 * Layout hooks - ACF fields
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Layout\ACF;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ACF' ) ) {
	return;
}

const ACF_PRODUCT_TITLE                = 'tekst_przed_tytulem';
const ACF_PRODUCT_TYPE                 = 'tekst_po_tytule';
const ACF_PRODUCT_DETAILS              = 'szczegoly_produktu';
const ACF_PRODUCT_DETAILS_LABEL        = 'nazwa_parametru';
const ACF_PRODUCT_DETAILS_VALUE        = 'wartosc_parametru';
const ACF_PRODUCT_NUTRITION_DATA       = 'tabela_odzywcza';
const ACF_PRODUCT_NUTRITION_DATA_LABEL = 'parametry_';
const ACF_PRODUCT_NUTRITION_DATA_VALUE = 'wartosc_parametru';
const ACF_PRODUCT_FEATURED_THUMBNAIL   = 'zdjecie_do_slidera';
const ACF_CATEGORY_DESCRIPTION         = 'dlugi_opis_kategorii';

// Cart, mini-cart, checkout.
add_filter( 'woocommerce_cart_item_name', __NAMESPACE__ . '\get_custom_product_title', 10, 2 );

// Product loop item.
add_action( 'woocommerce_after_shop_loop_item_title', __NAMESPACE__ . '\display_loop_item_type', 3 );
remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
add_action( 'woocommerce_shop_loop_item_title', __NAMESPACE__ . '\modify_loop_item_title' );

// Product page.
add_action( 'woocommerce_before_single_product_summary', __NAMESPACE__ . '\modify_product_breadcrumb_title', 6 );
add_action( 'woocommerce_before_single_product_summary', __NAMESPACE__ . '\modify_product_page_title', 15 );
add_filter( 'woocommerce_display_product_attributes', __NAMESPACE__ . '\add_product_attributes', 20, 2 );
add_action( 'woocommerce_single_product_summary', __NAMESPACE__ . '\display_nutritional_data', 36 );

// Product category page.
add_action( 'woocommerce_after_main_content', __NAMESPACE__ . '\display_category_description', 20 );

// View order.
add_filter( 'woocommerce_order_item_name', __NAMESPACE__ . '\modify_order_item_name', 10, 2 );

// Featured products slider.
add_filter( 'chocante_featured_products_category', __NAMESPACE__ . '\get_featured_category', 10, 2 );
add_filter( 'chocante_featured_products_title', __NAMESPACE__ . '\get_featured_title', 10, 2 );
add_filter( 'chocante_featured_products_thumbnail', __NAMESPACE__ . '\get_featured_thumbnail', 10, 2 );

/**
 * Modify product title using ACF fields
 *
 * @param string $product_name Product title.
 * @param array  $cart_item Product in the cart.
 * @return string
 */
function get_custom_product_title( $product_name, $cart_item ) {
	$product_id = $cart_item['product_id'];
	$product    = $cart_item['data'];

	if ( 'product' === get_post_type( $product_id ) ) {
		$product_short_name = get_field( ACF_PRODUCT_TITLE, $product_id );
		$product_type       = get_field( ACF_PRODUCT_TYPE, $product_id );

		if ( $product_short_name ) {
			$product_name = $product_short_name;

			if ( $product_type ) {
				$product_name .= '<small>' . $product_type . '</small>';
			}

			return sprintf( '<a href="%s">%s</a>', esc_url( $product->get_permalink() ), wp_kses_post( $product_name ) );
		}
	}

	return $product_name;
}

/**
 * Display product type ACF field in the loop item.
 */
function display_loop_item_type() {
	global $product;

	if ( ! isset( $product ) ) {
		return;
	}

	$product_type = get_field( ACF_PRODUCT_TYPE, $product->get_id() );

	if ( $product_type ) {
		echo "<span class='woocommerce-loop-product__type'>" . esc_html( $product_type ) . '</span>';
	}
}

/**
 * Display ACF field instead of default product title in the loop item.
 */
function modify_loop_item_title() {
	global $product;

	if ( ! isset( $product ) ) {
		return;
	}

	$product_name  = get_field( ACF_PRODUCT_TITLE, $product->get_id() );
	$product_title = $product_name ? $product_name : get_the_title();

	echo '<h2 class="' . esc_attr( apply_filters( 'woocommerce_product_loop_title_classes', 'woocommerce-loop-product__title' ) ) . '">' . $product_title . '</h2>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}


/**
 * Display ACF short name in breadcrumbs
 */
function modify_product_breadcrumb_title() {
	add_filter( 'the_title', __NAMESPACE__ . '\get_product_page_short_title', 10, 2 );
}

/**
 * Replace default product name with ACF fields
 *
 * @param string $title Product name.
 * @param int    $id Product ID.
 * @return string
 */
function get_product_page_short_title( $title, $id ) {
	$product_short_name = get_field( ACF_PRODUCT_TITLE, $id );

	if ( $product_short_name ) {
		return $product_short_name;
	}

	return $title;
}

/**
 * Display ACF fields in product page title
 */
function modify_product_page_title() {
	add_filter( 'the_title', __NAMESPACE__ . '\get_product_page_title', 10, 2 );
}

/**
 * Replace default product name with ACF fields
 *
 * @param string $title Product name.
 * @param int    $id Product ID.
 * @return string
 */
function get_product_page_title( $title, $id ) {
	$product_short_name = get_field( ACF_PRODUCT_TITLE, $id );
	$product_type       = get_field( ACF_PRODUCT_TYPE, $id );

	if ( $product_short_name ) {
		$product_title = $product_short_name;

		if ( $product_type ) {
			$product_title .= " {$product_type}";
		}

		return $product_title;
	}

	return $title;
}

/**
 * Add ACF product attributes
 *
 * @param array      $product_attributes Product attributes.
 * @param WC_Product $product Product object.
 * @return array
 */
function add_product_attributes( $product_attributes, $product ) {
	$field_name = ACF_PRODUCT_DETAILS;
	$attributes = get_field( $field_name, $product->get_id() );

	if ( $attributes ) {
		$index = 0;
		foreach ( $attributes as $attribute ) {
			$product_attributes[ $field_name . '_' . $index ] = array(
				'label' => $attribute[ ACF_PRODUCT_DETAILS_LABEL ],
				'value' => $attribute[ ACF_PRODUCT_DETAILS_VALUE ],
			);
			++$index;
		}
	}

	return $product_attributes;
}

/**
 * Display nutritional data table
 */
function display_nutritional_data() {
	global $product;

	$data_field = get_field( ACF_PRODUCT_NUTRITION_DATA, $product->get_id() );

	if ( ! $data_field || ! is_array( $data_field ) ) {
		return;
	}

	$data = array();

	foreach ( $data_field as $field ) {
		if ( empty( $field ) ) {
			continue;
		}

		array_push(
			$data,
			array(
				'label' => $field[ ACF_PRODUCT_NUTRITION_DATA_LABEL ],
				'value' => $field[ ACF_PRODUCT_NUTRITION_DATA_VALUE ],
			)
		);
	}

	get_template_part(
		'template-parts/product',
		'nutritional-data',
		array(
			'data' => $data,
		)
	);
}

/**
 * Display diet information
 */
function display_category_description() {
	if ( ! is_product_category() ) {
		return;
	}

	$queried_object       = get_queried_object();
	$taxonomy             = $queried_object->taxonomy;
	$term_id              = $queried_object->term_id;
	$category_description = get_field( ACF_CATEGORY_DESCRIPTION, $taxonomy . '_' . $term_id );

	if ( $category_description ) {
		echo '<div class="page-description">' . wp_kses_post( $category_description ) . '</div>';
	}
}

/**
 * Replace default order item name with ACF fields
 *
 * @param string        $item_name Order line item name HTML.
 * @param WC_Order_Item $item Order line item.
 * @return string
 */
function modify_order_item_name( $item_name, $item ) {
	$product_id         = $item->get_data()['product_id'];
	$product_short_name = get_field( ACF_PRODUCT_TITLE, $product_id );
	$product_type       = get_field( ACF_PRODUCT_TYPE, $product_id );

	if ( $product_short_name ) {
		$product_title = '<strong>' . $product_short_name . '</strong>';

		if ( $product_type ) {
			$product_title .= '<small>' . $product_type . '</small>';
		}

		return $product_title;
	}

	return '<strong>' . $item->get_name() . '</strong>';
}

/**
 * Get featured product category
 *
 * @param null $category Empty category.
 * @param int  $product_id Product ID.
 * @return string|null
 */
function get_featured_category( $category, $product_id ) {
	$product_short_name = get_field( ACF_PRODUCT_TYPE, $product_id );

	return $product_short_name;
}

/**
 * Get featured product title
 *
 * @param string $title Product name.
 * @param int    $product_id Product ID.
 * @return string
 */
function get_featured_title( $title, $product_id ) {
	$product_name = get_field( ACF_PRODUCT_TITLE, $product_id );

	if ( $product_name ) {
		return $product_name;
	}

	return $title;
}

/**
 * Get featured product thumbnail
 *
 * @param int $image The post thumbnail ID.
 * @param int $product_id Product ID.
 * @return string|null
 */
function get_featured_thumbnail( $image, $product_id ) {
	$thumbnail = get_field( ACF_PRODUCT_FEATURED_THUMBNAIL, $product_id );

	if ( $thumbnail ) {
		if ( is_array( $thumbnail ) ) {
			return $image;
		}

		return $thumbnail;
	}

	return $image;
}
