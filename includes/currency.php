<?php
/**
 * Multi-currency settings
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Currency;

defined( 'ABSPATH' ) || exit;

use const Chocante\Location\SERVER_COOKIE;

const CURRENCY_COOKIE = 'wmc_current_currency';

add_filter( 'tgpc_wc_gift_wrapper_cost', __NAMESPACE__ . '\convert_gift_wrapper_fee' );

// Currency switcher.
add_action( 'init', __NAMESPACE__ . '\add_currency_switcher_shortcode' );
add_action( 'chocante_currency_switcher', __NAMESPACE__ . '\display_currency_switcher' );

add_action( 'woocommerce_checkout_init', __NAMESPACE__ . '\reset_server_currency' );
add_filter( 'wmc_update_exchange_rate_new_rates', __NAMESPACE__ . '\manage_exchange_rates', 10, 2 );

add_action( 'admin_init', __NAMESPACE__ . '\update_settings', 20 );

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
 * Add currency swticher shortcode
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
 * Display currency switcher
 */
function display_currency_switcher() {
	$curcy = get_curcy();

	if ( ! $curcy ) {
		return;
	}

	$current = $curcy->get_current_currency();
	$items   = array_filter( $curcy->get_list_currencies(), fn( $c ) => ! $c['hide'] );

	unset( $items[ $current ] );

	ob_start();
	get_template_part(
		'template-parts/switcher',
		'currency',
		array(
			'current' => $current,
			'items'   => array_keys( $items ),
			'is_js'   => class_exists( 'WOOMULTI_CURRENCY_Data' ) ? $curcy->enable_switch_currency_by_js() : false,
		)
	);

	echo wp_kses_post( ob_get_clean() );
}

/**
 * Reset currency cookie added by server
 */
function reset_server_currency() {
	if ( ! empty( $_COOKIE[ SERVER_COOKIE ] ) ) {
		// phpcs:ignore
		setcookie( SERVER_COOKIE, 0, time() - DAY_IN_SECONDS, '/', COOKIE_DOMAIN, true, false );
		// phpcs:ignore
		setcookie( CURRENCY_COOKIE, 0, time() - DAY_IN_SECONDS, '/', COOKIE_DOMAIN, true, false );
	}
}

/**
 * Manage new exchange rates. Change rates when difference is above 1%.
 *
 * @param array $rates New exchange rates.
 * @param array $settings Plugin settings.
 * @return array
 */
function manage_exchange_rates( $rates, $settings ) {
	$changed = array();

	foreach ( $settings['currency_rate'] as $currency => $current_rate ) {
		$new_rate   = $rates[ $currency ];
		$difference = abs( (float) $current_rate - (float) $new_rate );

		if ( $difference > $current_rate * 0.05 ) {
			$changed[] = $settings['currency'][ $currency ];
		} else {
			$rates[ $currency ] = $current_rate;
		}
	}

	if ( ! empty( $changed ) ) {
		do_action( 'chocante_currency_changed', $changed );
	}

	return $rates;
}

/**
 * Get the list of used currencies
 */
function get_currencies() {
	$curcy      = get_curcy();
	$currencies = array();

	if ( $curcy ) {
		$curcy_currencies = array_filter( $curcy->get_list_currencies(), fn( $c ) => ! $c['hide'] );
		$currencies       = array_merge( $currencies, array_keys( $curcy_currencies ) );
	}

	return $currencies;
}

/**
 * Trigger action on currency settings update
 *
 * @see: WOOMULTI_CURRENCY_Admin_Settings::save_settings
 */
function update_settings() {
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

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	do_action( 'chocante_currency_settings_saved', $_POST['woo_multi_currency_params'] );
}

/**
 * Get country-currency map
 *
 * @param array $settings Curcy settings.
 */
function get_currency_by_country( $settings = array() ) {
	if ( empty( $settings ) ) {
		$curcy = get_curcy();
		if ( ! $curcy ) {
			return array();
		}

		$settings = $curcy->get_params();
	}

	$country_currency = array();

	foreach ( $settings['currency'] as $key => $currency ) {
		if ( false !== (bool) $settings['currency_hidden'][ $key ] ) {
			continue;
		}

		if ( ! empty( $settings[ $currency . '_by_country' ] ) ) {
			foreach ( $settings[ $currency . '_by_country' ] as $country ) {
				$country_currency[ $country ] = $currency;
			}
		}
	}

	return $country_currency;
}
