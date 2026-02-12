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

// WCML.
add_filter( 'wcml_multi_currency_ajax_actions', __NAMESPACE__ . '\add_product_section_to_wcml_ajax_actions' );
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
 * Add currency information to product section script
 *
 * @param array $script_data Script localization data.
 * @return array
 */
function add_currency_to_product_section_script( $script_data ) {
	$curcy = get_curcy();

	if ( $curcy ) {
		$script_data['currency'] = $curcy->get_current_currency();
	}

	return $script_data;
}

/**
 * Include WCML in AJAX requests
 *
 * @param array $ajax_actions AJAX actions.
 * @return array
 */
function add_product_section_to_wcml_ajax_actions( $ajax_actions ) {
	$ajax_actions[] = 'get_product_section';

	return $ajax_actions;
}

/**
 * Convert gift wrapper fee to selected currency
 *
 * @param float $fee Gift wrapper fee in base currency.
 * @return float
 */
function convert_gift_wrapper_fee( $fee ) {
	return apply_filters( 'wcml_raw_price_amount', $fee );
}
