<?php
/**
 * Caching
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Cache;

defined( 'ABSPATH' ) || exit;

use const Chocante\Currency\CURRENCY_COOKIE;
use function Chocante\Currency\display_currency_switcher;

/**
 * Vary
 */
add_filter( 'litespeed_vary', __NAMESPACE__ . '\set_default_vary' );
add_filter( 'litespeed_vary_cookies', __NAMESPACE__ . '\set_global_cookie_vary' );
add_filter( 'litespeed_vary_curr_cookies', __NAMESPACE__ . '\set_global_currency_cookie_vary' );
add_action( 'chocante_product_section_ajax_get', __NAMESPACE__ . '\set_vary_currency' );

/**
 * Control
 */
add_action( 'litespeed_control_finalize', __NAMESPACE__ . '\set_public' );
add_action( 'chocante_product_section_ajax_get', __NAMESPACE__ . '\set_public_get_product_section' );
add_action( 'litespeed_control_finalize', __NAMESPACE__ . '\set_no_cache_translatepress' );

/**
 * ESI
 */
add_action( 'chocante_currency_switcher', __NAMESPACE__ . '\esi_url_currency_swticher', 5 );
add_action( 'litespeed_esi_load-currency_switcher', __NAMESPACE__ . '\esi_include_currency_swticher' );

/**
 * Tag
 */

/**
 * Purge
 */
add_action( 'woocommerce_ajax_save_product_variations', __NAMESPACE__ . '\purge_product' );
/**
 * Purge frontpage when admin marks product as featured
 * Used for purging featured products slider
 *
 * @todo: add cache tag to featured product section.
 */
add_action( 'woocommerce_before_product_object_save', __NAMESPACE__ . '\purge_featured_products' );
/**
 * Purge all product sections
 *
 * @todo: switch to tagging.x
 */
add_action( 'chocante_product_section_cache_flush', __NAMESPACE__ . '\purge_get_product_section' );

/**
 * Set public cache for AJAX get product section
 */
function set_public_get_product_section() {
	do_action( 'litespeed_control_force_public', 'AJAX product section' );
}

/**
 * Do not cache TranslatePress editor
 */
function set_no_cache_translatepress() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( class_exists( 'TRP_Translate_Press' ) && isset( $_REQUEST['trp-edit-translation'] ) ) {
		do_action( 'litespeed_control_set_nocache', 'TranslatePress editor' );
	}
}

/**
 * Purge AJAX get product section
 */
function purge_get_product_section() {
	do_action( 'litespeed_purge', 'AJAX.get_product_section' );
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
		do_action( 'litespeed_purge_post', get_option( 'page_on_front' ) );
	}
}

/**
 * Skip vary for logged-in customers
 *
 * @param array $vary Array or vary cookies.
 * @return array
 */
function set_default_vary( $vary ) {
	// Vary not used.
	unset( $vary['logged-in'] );

	/**
	 * Rework LSC approach. Determine based on global variable instead of just the user setting.
	 *
	 * @see LiteSpeed\Vary::finalize_default_vary
	 * @see Chocante\Layout\Common\hide_admin_bar
	 */
	if ( is_admin_bar_showing() ) {
		$vary['admin_bar'] = 1;
	} else {
		unset( $vary['admin_bar'] );
	}

	return $vary;
}

/**
 * Set public page cache for logged-in users regardless of ESI setting
 * Default LSC setting with ESI off is either private or no-cache
 */
function set_public() {
	if ( class_exists( 'WooCommerce' ) && ( is_cart() || is_checkout() || is_account_page() || is_wc_endpoint_url() ) ) {
		return;
	}

	do_action( 'litespeed_control_force_public', 'chocante public' );
}

/**
 * Set LSCACHE_VARY_COOKIE
 *
 * @see WOOMULTI_CURRENCY_Plugin_Litespeed_Cache::__construct
 *
 * @param array $cookies List of site-wide cookie varies.
 * @return array
 */
function set_global_cookie_vary( $cookies ) {
	$excluded = array( 'wmc_current_currency', 'wmc_current_currency_old', 'wmc_ip_info' );
	$cookies  = array_filter( $cookies, fn( $cookie ) => ! in_array( $cookie, $excluded, true ) );

	return $cookies;
}

/**
 * Set currency vary cookies - leave only single cookie 'wmc_current_currency'
 *
 * @see WOOMULTI_CURRENCY_Plugin_Litespeed_Cache::__construct
 *
 * @param array $cookies List of site-wide cookie varies.
 * @return array
 */
function set_global_currency_cookie_vary( $cookies ) {
	$excluded = array( 'wmc_current_currency_old', 'wmc_ip_info' );
	$cookies  = array_filter( $cookies, fn( $cookie ) => ! in_array( $cookie, $excluded, true ) );

	return $cookies;
}

/**
 * Set currency vary cookie
 */
function set_vary_currency() {
	add_filter(
		'litespeed_vary_curr_cookies',
		/**
		* Add currency to vary cookies
		*
		* @param array $cookies List of current vary cookies.
		* @return array
		*/
		function ( $cookies ) {
			$cookies[] = CURRENCY_COOKIE;

			return $cookies;
		}
	);
}

/**
 * Display currency switcher ESI block
 */
function esi_url_currency_swticher() {
	if ( ! apply_filters( 'litespeed_esi_status', false ) ) {
		return;
	}

	remove_action( 'chocante_currency_switcher', 'Chocante\Currency\display_currency_switcher' );

	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	echo apply_filters( 'litespeed_esi_url', 'currency_switcher', 'currency switcher', array(), 'public' );
}

/**
 * Include currency switcher ESI block
 */
function esi_include_currency_swticher() {
	set_vary_currency();
	display_currency_switcher();
}
