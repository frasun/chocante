<?php
/**
 * Multi-currency settings
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Currency;

defined( 'ABSPATH' ) || exit;

add_filter( 'chocante_product_section_script_data', __NAMESPACE__ . '\add_currency_to_product_section_script' );
add_filter( 'tgpc_wc_gift_wrapper_cost', __NAMESPACE__ . '\convert_gift_wrapper_fee' );

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
 * Add currency information to product section script
 *
 * @param array $script_data Script localization data.
 * @return array
 */
function add_currency_to_product_section_script( $script_data ) {
	$currency = get_currency();

	if ( $currency ) {
		$script_data['currency'] = $currency;
	}

	return $script_data;
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
