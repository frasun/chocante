<?php
/**
 * Caching
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Cache;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'LSCWP_V' ) ) {
	return;
}

use LiteSpeed\Thirdparty\WooCommerce;
use LiteSpeed\ESI;
use LiteSpeed\Tag;

use function Chocante\Layout\Product\print_product_variations;
use function Chocante\Woo\set_price_display_modify;
use function Chocante\Layout\Common\output_mobile_menu;
use function Chocante\Layout\Common\output_product_search;
use function Chocante\Layout\Blog\display_post_rating;
use function Chocante\Currency\get_currency;

const TAG_POST_RATING      = 'RATING.';
const TAG_CURRENCY         = 'FX.';
const TAG_PRODUCT_STOCK    = 'STOCK.';
const TAG_PRODUCT_FEATURED = 'FEATURED';

define( 'ESI_COMMENTS', 'production' === wp_get_environment_type() );

/**
 * Reset global vary cookie, use .htaccess
 */
add_filter( 'litespeed_vary', __NAMESPACE__ . '\reset_login_vary' );
add_filter( 'litespeed_vary_cookies', __NAMESPACE__ . '\reset_cookie_vary' );
add_filter( 'litespeed_vary_curr_cookies', __NAMESPACE__ . '\reset_cookie_vary' );

/**
 * Global
 */
add_action( 'litespeed_control_finalize', __NAMESPACE__ . '\set_control_global', );
add_action( 'litespeed_control_finalize', __NAMESPACE__ . '\set_no_cache_translatepress', 20 );
add_action( 'litespeed_purge_finalize', __NAMESPACE__ . '\set_final_tags' );
add_filter( 'litespeed_purge_tags', __NAMESPACE__ . '\finalize_purge_tags', 5 );

/**
 * ESI settings
 */
add_action( 'init', __NAMESPACE__ . '\esi_ref_reset', 4 );
add_action( 'init', __NAMESPACE__ . '\esi_ref_fix', 6 );
add_action( 'init', __NAMESPACE__ . '\set_esi_status' );
add_action( 'init', __NAMESPACE__ . '\set_esi_translate' );
add_action( 'litespeed_tag_finalize', __NAMESPACE__ . '\tag_esi', 5 );

/**
 * Layout
 */
add_action( 'chocante_header', __NAMESPACE__ . '\esi_include_header', 5 );
add_action( 'litespeed_esi_load-header', 'Chocante\Layout\Common\display_header' );
add_action( 'chocante_footer', __NAMESPACE__ . '\esi_include_footer', 5 );
add_action( 'litespeed_esi_load-footer', 'Chocante\Layout\Common\display_footer' );
add_action( 'chocante_footer', __NAMESPACE__ . '\esi_include_overlays', 15 );
add_action( 'litespeed_esi_load-overlays', __NAMESPACE__ . '\esi_overlays' );
add_action( 'wp_create_nav_menu', __NAMESPACE__ . '\purge_layout' );
add_action( 'wp_update_nav_menu', __NAMESPACE__ . '\purge_layout' );
add_action( 'wp_delete_nav_menu', __NAMESPACE__ . '\purge_layout' );
add_action( 'rest_after_save_widget', __NAMESPACE__ . '\purge_layout' );

/**
 * Currency
 */
add_action( 'admin_init', __NAMESPACE__ . '\purge_currency_switcher', 20 );
add_action( 'chocante_currency_changed', __NAMESPACE__ . '\purge_currency' );

/**
 * Product
 */
// Product tile.
add_filter( 'wc_get_template_part', __NAMESPACE__ . '\esi_include_product_tile', 10, 3 );
add_action( 'litespeed_esi_load-product_tile', __NAMESPACE__ . '\esi_product_tile' );
// Product variations JSON.
add_action( 'chocante_product_variations_json', __NAMESPACE__ . '\esi_include_product_variations', 5 );
add_action( 'litespeed_esi_load-product_variations', __NAMESPACE__ . '\esi_product_variations' );
add_filter( 'trp_translate_encoded_html_as_html', __NAMESPACE__ . '\esi_product_variations_trp_skip_json' );
// Simple product price.
add_action( 'woocommerce_single_product_summary', __NAMESPACE__ . '\esi_include_product_price', 21 );
add_action( 'litespeed_esi_load-product_price', __NAMESPACE__ . '\esi_product_price' );
// Simple product stock.
add_action( 'chocante_product_stock', __NAMESPACE__ . '\esi_include_product_stock', 5 );
add_action( 'litespeed_esi_load-product_stock', __NAMESPACE__ . '\esi_product_stock' );
// Purge on stock quantity changes.
add_action( 'woocommerce_variation_set_stock', __NAMESPACE__ . '\purge_product_stock_variable' );
add_action( 'woocommerce_product_set_stock', __NAMESPACE__ . '\purge_product_stock' );

/**
 * Product section
 */
add_action( 'chocante_product_section_ajax_get', __NAMESPACE__ . '\set_control_get_product_section' );
add_action( 'chocante_product_section_get_products', __NAMESPACE__ . '\tag_get_product_section', 10, 2 );

/**
 * Products
 */
add_action( 'woocommerce_ajax_save_product_variations', __NAMESPACE__ . '\purge_product' );
add_action( 'woocommerce_before_product_object_save', __NAMESPACE__ . '\purge_featured_products' );
add_action( 'wp_ajax_woocommerce_feature_product', __NAMESPACE__ . '\purge_featured_products_ajax', 5 );
add_action( 'litespeed_tag_finalize', __NAMESPACE__ . '\tag_product_taxonomy' );

/**
 * Free shipping
 */
add_action( 'chocante_delivery_info', __NAMESPACE__ . '\esi_include_delivery_info', 5 );
add_action( 'litespeed_esi_load-delivery_info', 'Chocante\Layout\Common\display_free_delivery_info' );

/**
 * Post
 */
add_action( 'chocante_post_header', __NAMESPACE__ . '\esi_include_post_rating', 5 );
add_action( 'litespeed_esi_load-post_rating', __NAMESPACE__ . '\esi_post_rating' );
add_action( 'rmp_after_vote', __NAMESPACE__ . '\purge_post_rating' );

/**
 * Disable admin bar ESI - page uncached when is_admin_bar_showing()
 */
if ( class_exists( 'LiteSpeed\ESI' ) ) {
	if ( apply_filters( 'litespeed_esi_status', false ) ) {
		add_action(
			'wp_footer',
			function () {
				remove_action( 'wp_footer', array( ESI::cls(), 'sub_admin_bar_block' ), 1000 );
			},
			99
		);
		add_action( 'wp_body_open', 'wp_admin_bar_render', 1 );
		add_action( 'wp_footer', 'wp_admin_bar_render', 1001 );
	}
}

/**
 * Reset _lscache_vary
 *
 * @param array $vary Default login vary.
 */
function reset_login_vary( $vary ) {
	unset( $vary['role'] );
	unset( $vary['logged-in'] );
	unset( $vary['admin_bar'] );

	if ( is_admin_bar_showing() ) {
		$vary['admin_bar'] = 1;
	}

	return $vary;
}

/**
 * Reset vary cookies and use .htaccess
 */
function reset_cookie_vary() {
	return array();
}

/**
 * Set cache for AJAX get product section. No cache when admin bar is shown.
 */
function set_control_get_product_section() {
	if ( is_user_logged_in() && apply_filters( 'show_admin_bar', true ) ) {
		$show_admin_bar = _get_admin_bar_pref();
	} else {
		$show_admin_bar = false;
	}

	if ( $show_admin_bar ) {
		do_action( 'litespeed_control_set_nocache', 'chocante - product section - admin' );
	} else {
		do_action( 'litespeed_control_force_public', 'chocante - product section' );
	}
}

/**
 * Add purge tags to AJAX get product section
 *
 * @param string[]     $categories List of product categories.
 * @param string|false $featured If includes featured products.
 */
function tag_get_product_section( $categories, $featured ) {
	if ( ! defined( 'LSCWP_V' ) ) {
		return;
	}

	foreach ( $categories as $category_id ) {
		do_action( 'litespeed_tag_add', WooCommerce::CACHETAG_TERM . $category_id );
	}

	if ( $featured ) {
		do_action( 'litespeed_tag_add', TAG_PRODUCT_FEATURED );
	}

	// Skip currency tags for pages.
	if ( wp_doing_ajax() ) {
		$currency = get_currency();
		if ( $currency ) {
			do_action( 'litespeed_tag_add', TAG_CURRENCY . $currency );
		}
	}
}

/**
 * Do not cache TranslatePress editor
 */
function set_no_cache_translatepress() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( class_exists( 'TRP_Translate_Press' ) && isset( $_REQUEST['trp-edit-translation'] ) ) {
		do_action( 'litespeed_control_set_nocache', 'TranslatePress editor' );
		add_filter( 'litespeed_esi_status', '__return_false' );
	}
}

/**
 * Purge product by ID
 *
 * @param int $product_id Product ID.
 */
function purge_product( $product_id ) {
	do_action( 'litespeed_purge_post', $product_id );
}

/**
 * Purge frontpage when admin changes featured status of product
 *
 * @param \WC_Product $product Product object.
 */
function purge_featured_products( $product ) {
	$changes = $product->get_changes();

	if ( isset( $changes['featured'] ) ) {
		do_action( 'litespeed_purge', TAG_PRODUCT_FEATURED );
	}
}

/**
 * Purge featured after admin stars product
 */
function purge_featured_products_ajax() {
	do_action( 'litespeed_purge', TAG_PRODUCT_FEATURED );
}

/**
 * Define global cache control rules
 *
 * @param string|false $esi_block ESI block id or false if no ESI.
 */
function set_control_global( $esi_block ) {
	if ( $esi_block ) {
		return;
	}

	$not_public = is_search() || is_cart() || is_checkout() || is_account_page() || is_404() || wp_is_rest_endpoint();

	if ( class_exists( 'TRP_Translate_Press' ) && isset( $_REQUEST['trp-edit-translation'] ) ) {
		$not_public = true;
	}

	if ( is_admin_bar_showing() ) {
		do_action( 'litespeed_control_set_nocache', 'chocante - admin' );
	} elseif ( ! $not_public ) {
		do_action( 'litespeed_control_force_public', 'chocante - public' );
	}
}

/**
 * Purge currency tags on Curcy settings change
 *
 * @see: WOOMULTI_CURRENCY_Admin_Settings::save_settings
 */
function purge_currency_switcher() {
	if ( ! isset( $_POST['_woo_multi_currency_nonce'], $_POST['woo_multi_currency_params'] ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( ! wp_verify_nonce( $_POST['_woo_multi_currency_nonce'], 'woo_multi_currency_settings' ) ) {
		return;
	}
	// phpcs:ignore WordPress.WP.Capabilities.Unknown
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	purge_layout();
}

/**
 * Purge currency on rate change
 *
 * @param array $currencies Currencies to purge.
 */
function purge_currency( $currencies ) {
	foreach ( $currencies as $currency ) {
		do_action( 'litespeed_purge', TAG_CURRENCY . $currency );
	}
}

/**
 * Set purge tags to currency switcher
 */
function tag_esi() {
	if ( ! defined( 'LSCACHE_IS_ESI' ) ) {
		return;
	}

	// Remove Woo tags.
	remove_action( 'litespeed_tag_finalize', array( WooCommerce::cls(), 'set_tag' ) );

	// Post tag.
	$post_id = get_the_ID();

	if ( 'product_tile' === LSCACHE_IS_ESI ) {
		do_action( 'litespeed_tag_add', Tag::TYPE_POST . $post_id );
	}

	// Currency tag.
	$currency = get_currency();

	if ( $currency ) {
		$tag_by_currency = array( 'product_tile', 'product_price', 'product_variations', 'delivery_info' );

		if ( in_array( LSCACHE_IS_ESI, $tag_by_currency, true ) ) {
			do_action( 'litespeed_tag_add', TAG_CURRENCY . $currency );
		}
	}
}

/**
 * ESI url - product variations json
 *
 * @param int $product_id Product ID.
 */
function esi_include_product_variations( $product_id ) {
	if ( ! apply_filters( 'litespeed_esi_status', false ) ) {
		return;
	}

	remove_action( 'chocante_product_variations_json', 'Chocante\Layout\Product\print_product_variations' );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo apply_filters( 'litespeed_esi_url', 'product_variations', 'chocante - product variations json', array( 'id' => $product_id ), 'public', true, true );
}

/**
 * ESI block - product variations json
 *
 * @param array $params Block params.
 */
function esi_product_variations( $params ) {
	global $product;
	$product_id = $params['id'];
	$product    = wc_get_product( $product_id );

	do_action( 'litespeed_tag_add', Tag::TYPE_POST . $product_id );
	do_action( 'litespeed_tag_add', TAG_PRODUCT_STOCK . $product_id );

	print_product_variations();
}


/**
 * Prevent TranslatePress from processing product variations JSON
 *
 * @param bool $filter Run TRP filters.
 */
function esi_product_variations_trp_skip_json( $filter ) {
	if ( isset( $_GET['lsesi'] ) && 'product_variations' === $_GET['lsesi'] ) {
			return false;
	}

	return $filter;
}

/**
 * ESI block url - simple product price
 */
function esi_include_product_price() {
	if ( ! apply_filters( 'litespeed_esi_status', false ) ) {
		return;
	}

	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 25 );

	global $product;

	if ( ! $product instanceof \WC_Product_Simple ) {
		return;
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo apply_filters( 'litespeed_esi_url', 'product_price', 'chocante - simple product price', array( 'id' => $product->get_id() ), 'public', ESI_COMMENTS );
}

/**
 * ESI block - simple product price
 *
 * @param array $params Block params.
 */
function esi_product_price( $params ) {
	global $product;
	$product_id = $params['id'];
	$product    = wc_get_product( $product_id );

	do_action( 'litespeed_tag_add', Tag::TYPE_POST . $product_id );

	wc_get_template( 'single-product/price.php' );
}

/**
 * ESI url - product tile
 *
 * @param string $template WC template url.
 * @param string $slug WC template slug.
 * @param string $name WC template name.
 * @return string
 */
function esi_include_product_tile( $template, $slug, $name ) {
	if ( ! apply_filters( 'litespeed_esi_status', false ) ) {
		return $template;
	}

	if ( 'content' === $slug && 'product' === $name ) {
		return get_theme_file_path( 'template-parts/esi-product-tile.php' );
	}

	return $template;
}

/**
 * ESI block - product tile
 *
 * @param array $params Block params.
 */
function esi_product_tile( $params ) {
	global $product;
	global $post;

	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$post = get_post( $params['id'] );
	setup_postdata( $post );

	$product = wc_get_product( $params['id'] );

	set_price_display_modify();

	remove_filter( 'wc_get_template_part', __NAMESPACE__ . '\esi_include_product_tile', 10, 3 );
	wc_get_template_part( 'content', 'product' );
}

/**
 * Set ESI status
 */
function set_esi_status() {
	if ( ( class_exists( 'TRP_Translate_Press' ) && isset( $_REQUEST['trp-edit-translation'] ) ) || is_admin_bar_showing() || wp_doing_ajax() ) {
		add_filter( 'litespeed_esi_status', '__return_false' );
	}
}

/**
 * Set gettext translations in ESI
 */
function set_esi_translate() {
	if ( ! class_exists( 'TRP_Translate_Press' ) || ! defined( 'LSCACHE_IS_ESI' ) ) {
		return;
	}

	$trp             = \TRP_Translate_Press::get_trp_instance();
	$gettext_manager = $trp->get_component( 'gettext_manager' );

	$gettext_manager->create_gettext_translated_global();
	$gettext_manager->call_gettext_filters();
}

/**
 * ESI block url - site header
 */
function esi_include_header() {
	if ( ! apply_filters( 'litespeed_esi_status', false ) ) {
		return;
	}

	remove_action( 'chocante_header', 'Chocante\Layout\Common\display_header' );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo apply_filters( 'litespeed_esi_url', 'header', 'chocante - header', array(), 'public', ESI_COMMENTS );
}

/**
 * ESI block url - site footer
 */
function esi_include_footer() {
	if ( ! apply_filters( 'litespeed_esi_status', false ) ) {
		return;
	}

	remove_action( 'chocante_footer', 'Chocante\Layout\Common\display_footer' );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo apply_filters( 'litespeed_esi_url', 'footer', 'chocante - footer', array(), 'public', ESI_COMMENTS );
}
/**
 * ESI block url - overlays
 */
function esi_include_overlays() {
	if ( ! apply_filters( 'litespeed_esi_status', false ) ) {
		return;
	}

	remove_action( 'chocante_footer', 'Chocante\Layout\Common\output_mobile_menu', 20 );
	remove_action( 'chocante_footer', 'Chocante\Layout\Common\output_product_search', 30 );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo apply_filters( 'litespeed_esi_url', 'overlays', 'chocante - mobile menu & search', array(), 'public', ESI_COMMENTS );
}
/**
 * ESI block - mobile menu & search
 */
function esi_overlays() {
	output_mobile_menu();
	output_product_search();
}

/**
 * ESI block url - product stock
 */
function esi_include_product_stock() {
	if ( ! apply_filters( 'litespeed_esi_status', false ) ) {
		return;
	}

	global $product;

	if ( ! $product instanceof \WC_Product_Simple ) {
		return;
	}

	remove_action( 'chocante_product_stock', 'Chocante\Layout\Product\get_product_stock' );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo apply_filters( 'litespeed_esi_url', 'product_stock', 'chocante - simple product stock', array( 'id' => $product->get_id() ), 'public', ESI_COMMENTS );
}

/**
 * ESI block - product stock
 *
 * @param array $params Block params.
 */
function esi_product_stock( $params ) {
	$product_id = $params['id'];
	$product    = wc_get_product( $product_id );

	do_action( 'litespeed_tag_add', Tag::TYPE_POST . $product_id );
	do_action( 'litespeed_tag_add', TAG_PRODUCT_STOCK . $product_id );

	echo wp_kses_post( wc_get_stock_html( $product ) );
}

/**
 * ESI block url - post rating
 */
function esi_include_post_rating() {
	if ( ! apply_filters( 'litespeed_esi_status', false ) ) {
		return;
	}

	remove_action( 'chocante_post_header', 'Chocante\Layout\Blog\display_post_rating' );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo apply_filters( 'litespeed_esi_url', 'post_rating', 'chocante - post rating result', array( 'id' => get_the_id() ), 'public', ESI_COMMENTS );
}

/**
 * ESI block - post rating
 *
 * @param array $params Block params.
 */
function esi_post_rating( $params ) {
	do_action( 'litespeed_tag_add', TAG_POST_RATING . $params['id'] );

	display_post_rating( $params['id'] );
}

/**
 * Purge post rating after vote
 *
 * @param int $post_id Post ID.
 */
function purge_post_rating( $post_id ) {
	do_action( 'litespeed_purge', TAG_POST_RATING . $post_id );
}

/**
 * ESI block url - post rating
 */
function esi_include_delivery_info() {
	if ( ! apply_filters( 'litespeed_esi_status', false ) ) {
		return;
	}

	remove_action( 'chocante_delivery_info', 'Chocante\Layout\Common\display_free_delivery_info' );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo apply_filters( 'litespeed_esi_url', 'delivery_info', 'chocante - delivery info', array(), 'public', ESI_COMMENTS );
}

/**
 * Purge variable product stock info
 *
 * @param \WC_Product_Variation $variation Product variation object.
 */
function purge_product_stock_variable( $variation ) {
	do_action( 'litespeed_purge', TAG_PRODUCT_STOCK . $variation->get_parent_id() );
}

/**
 * Purge simple product stock info
 *
 * @param \WC_Product_Simple $product Product object.
 */
function purge_product_stock( $product ) {
	do_action( 'litespeed_purge', TAG_PRODUCT_STOCK . $product->get_id() );
}

/**
 * Remove ESI referer due to bug in LSC
 *
 * @see: LiteSpeed\ESI::_register_esi_actions
 */
function esi_ref_reset() {
	if ( empty( $_SERVER['ESI_REFERER'] ) ) {
		return;
	}

	// phpcs:ignore
	$_SERVER['ESI_REFERER_BACKUP'] = $_SERVER['ESI_REFERER'];
	unset( $_SERVER['ESI_REFERER'] );
}

/**
 * Revert ESI referer due to bug in LSC
 *
 * @see: LiteSpeed\ESI::_register_esi_actions
 */
function esi_ref_fix() {
	if ( ! empty( $_SERVER['ESI_REFERER_BACKUP'] ) ) {
		// phpcs:ignore
		$_SERVER['ESI_REFERER'] = $_SERVER['ESI_REFERER_BACKUP'];
		unset( $_SERVER['ESI_REFERER_BACKUP'] );
	}
}

/**
 * Mark end of purge tag collection
 */
function set_final_tags() {
	global $final_tags;
	$final_tags = true;
}

/**
 * Extract IDs from purge tags
 *
 * @param string   $tag Tag to look in.
 * @param string   $prefix ID prefix.
 * @param string[] $matches Matched IDs.
 * @return int|false
 */
function find_tag_id( $tag, $prefix, &$matches ) {
	return preg_match( '/^' . preg_quote( $prefix, '/' ) . '(.+)/', $tag, $matches );
}

/**
 * Add archive pages when purging posts
 *
 * @param array $tags Final purge tags.
 */
function finalize_purge_tags( $tags ) {
	foreach ( $tags as $key => $tag ) {
		if ( find_tag_id( $tag, Tag::TYPE_URL, $matches ) ) {
			unset( $tags[ $key ] );
		}
	}

	global $final_tags;

	if ( ! $final_tags ) {
		return $tags;
	}

	$final_tags = $tags;

	foreach ( $tags as $tag ) {
		$tag = ltrim( $tag, '_' );

		if ( find_tag_id( $tag, Tag::TYPE_POST, $matches ) ) {
			$posttype = get_post_type( $matches[1] );

			if ( 'adt_product_feed' === $posttype ) {
				return array( '_nothing' );
			}

			if ( 'post' === $posttype ) {
				$final_tags[] = '_' . Tag::TYPE_HOME;
				$final_tags[] = '_' . Tag::TYPE_FRONTPAGE;
				break;
			}

			if ( 'product' === $posttype ) {
				$final_tags[] = '_' . WooCommerce::CACHETAG_SHOP;
				break;
			}
		}
	}

	return $final_tags;
}

/**
 * Purge layout elements
 */
function purge_layout() {
	do_action( 'litespeed_purge', Tag::TYPE_ESI . 'header' );
	do_action( 'litespeed_purge', Tag::TYPE_ESI . 'footer' );
	do_action( 'litespeed_purge', Tag::TYPE_ESI . 'overlays' );
}

/**
 * Add tags to Woo product taxonomies
 */
function tag_product_taxonomy() {
	if ( is_product_taxonomy() ) {
		$term = get_queried_object();

		if ( $term && ! is_wp_error( $term ) ) {
			do_action( 'litespeed_tag_add', WooCommerce::CACHETAG_TERM . $term->term_id );
		}
	}
}
