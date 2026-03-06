<?php
/**
 * Multi-currency settings
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Currency;

defined( 'ABSPATH' ) || exit;

const CURRENCY_COOKIE = 'wmc_current_currency';

add_action( 'template_redirect', __NAMESPACE__ . '\set_currency_query_param', 1 );
add_filter( 'tgpc_wc_gift_wrapper_cost', __NAMESPACE__ . '\convert_gift_wrapper_fee' );

// Currency switcher.
add_action( 'init', __NAMESPACE__ . '\add_currency_switcher_shortcode' );
add_action( 'chocante_currency_switcher', __NAMESPACE__ . '\display_currency_switcher' );

/**
 * Get Curcy instance.
 */
function get_curcy() {
	// Curcy premium version.
	if ( class_exists( 'WOOMULTI_CURRENCY_Data' ) ) {
		return \WOOMULTI_CURRENCY_Data::get_ins();
	}

	// Curcy free version.
	if ( class_exists( 'WOOMULTI_CURRENCY_F_Data' ) ) {
		return \WOOMULTI_CURRENCY_F_Data::get_ins();
	}

	return null;
}

/**
 * Get currecny
 */
function get_currency() {
	$curcy = get_curcy();

	if ( $curcy ) {
		return $curcy->get_current_currency();
	}

	return null;
}

/**
 * Convert gift wrapper fee to selected currency
 *
 * @param float $fee Gift wrapper fee in base currency.
 * @return float
 */
function convert_gift_wrapper_fee( $fee ) {
	$currency = get_currency();

	if ( $currency ) {
		return wmc_get_price( $fee, $currency, true );
	}

	return $fee;
}

/**
 * Set cookie and redirect when accessed via wmc query string
 */
function set_currency_query_param() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['wmc-currency'] ) ) {
			return;
	}

	$curcy = get_curcy();

	if ( ! $curcy ) {
		return;
	}

	$all_currencies   = $curcy->get_list_currencies();
	$valid_currencies = array_keys(
		array_filter(
			$all_currencies,
			function ( $currency ) {
				return '1' !== $currency['hide'];
			}
		)
	);

	if ( empty( $valid_currencies ) ) {
		return;
	}

	get_template_part( 'template-parts/currency', 'redirect', array( 'currencies' => $valid_currencies ) );

	do_action( 'chocante_currency_query_param' );
	exit;
}

/**
 * Display langauge swticher
 */
function add_currency_switcher_shortcode() {
	// [chocante_currency_switcher] shortcode.
	add_shortcode(
		'chocante_currency_switcher',
		function () {
			do_action( 'chocante_currency_switcher' );
		}
	);
}

/**
 * Display Curcy currency switcher
 */
function display_currency_switcher() {
	if ( shortcode_exists( 'woo_multi_currency_plain_vertical' ) ) {
		echo do_shortcode( '[woo_multi_currency_plain_vertical]' );
	}
}
