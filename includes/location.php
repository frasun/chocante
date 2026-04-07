<?php
/**
 * Location settings
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Location;

defined( 'ABSPATH' ) || exit;

use const Chocante\Currency\CURRENCY_COOKIE;

use function Chocante\Currency\get_currency_by_country;

const COUNTRY_COOKIE     = 'chocante_country';
const SHIPPING_COOKIE    = 'chocante_shipping_country';
const VAT_EXEMPT_COOKIE  = 'chocante_vat_exempt';
const VAT_EXEMPT_COOKIES = array( '_null', 1 );
const SERVER_COOKIE      = '_sc';

// Handle server cookie.
add_action( 'init', __NAMESPACE__ . '\set_cookie_domain' );

// Get location from cookie.
add_filter( 'woocommerce_customer_default_location_array', __NAMESPACE__ . '\get_default_location_from_cookie' );
add_filter( 'woocommerce_customer_get_billing_country', __NAMESPACE__ . '\get_customer_country_from_cookie' );
add_filter( 'woocommerce_customer_get_shipping_country', __NAMESPACE__ . '\get_customer_shipping_country_from_cookie' );

// Set location cookie value.
add_action( 'woocommerce_calculated_shipping', __NAMESPACE__ . '\set_cookies_in_cart' );
add_action( 'woocommerce_checkout_update_order_review', __NAMESPACE__ . '\set_cookies_in_checkout' );
add_action( 'wp_login', __NAMESPACE__ . '\set_cookies_on_login', 10, 2 );
add_action( 'wp_logout', __NAMESPACE__ . '\unset_shipping_country_cookie' );
if ( ! is_admin() ) {
	add_action( 'woocommerce_customer_save_address', __NAMESPACE__ . '\set_cookies_on_address_change', 10, 4 );
}

// Set variation prices based on a cookie.
add_filter( 'woocommerce_get_variation_prices_hash', __NAMESPACE__ . '\set_variations_price_hash', 10, 3 );

/**
 * Set default location based on cookie
 *
 * @param array $location Customer default location.
 * @return array
 */
function get_default_location_from_cookie( $location ) {
	$shipping_country_cookie = get_country_from_cookie();

	if ( $shipping_country_cookie ) {
		$location['country'] = $shipping_country_cookie;
	}

	return $location;
}

/**
 * Use cookie value for customer billing country
 *
 * @param string $value    Customer billing_country value.
 * @return string
 */
function get_customer_country_from_cookie( $value ) {
	if ( use_customer_settings() ) {
		return $value;
	}

	$country_cookie = get_country_from_cookie( 'billing' );

	return $country_cookie ?? $value;
}

/**
 * Use cookie value for customer shipping country
 *
 * @param string $value    Customer shipping_country value.
 * @return string
 */
function get_customer_shipping_country_from_cookie( $value ) {
	if ( use_customer_settings() ) {
		return $value;
	}

	$country_cookie = get_country_from_cookie();

	return $country_cookie ?? $value;
}

/**
 * Determine if use cookie country or application settings
 */
function use_customer_settings() {
	// phpcs:ignore
	$nonce_value        = wc_get_var( $_REQUEST['woocommerce-shipping-calculator-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) );
	$calculate_shipping  = ( ! empty( $_POST['calc_shipping'] ) && ( wp_verify_nonce( $nonce_value, 'woocommerce-shipping-calculator' ) || wp_verify_nonce( $nonce_value, 'woocommerce-cart' ) ) );
	$update_order_review = isset( $_REQUEST['wc-ajax'] ) && 'update_order_review' === $_REQUEST['wc-ajax'];
	$save_address        = ! empty( $_POST['action'] ) && 'edit_address' === $_POST['action'];

	return $calculate_shipping || $update_order_review || $save_address;
}

/**
 * Set location cookies based on customer data
 */
function set_cookies_in_cart() {
	$customer = WC()->customer;
	manage_location_cookies( $customer );
}

/**
 * Set shipping country after user logs in
 *
 * @param string     $user_login User login.
 * @param int|object $user       User.
 */
function set_cookies_on_login( $user_login, $user ) {
	$customer = new \WC_Customer( $user->ID, true );

	manage_location_cookies( $customer );
}

/**
 * Sync cookie with customer address
 *
 * @param int         $user_id User ID being saved.
 * @param string      $address_type Type of address; 'billing' or 'shipping'.
 * @param array       $address The address fields. Since 9.8.0.
 * @param WC_Customer $customer The customer object being saved. Since 9.8.0.
 */
function set_cookies_on_address_change( $user_id, $address_type, $address, $customer ) {
	manage_location_cookies( $customer );
}

/**
 * Change shipping location in checkout
 *
 * @param string $query Checkout form fields query params.
 */
function set_cookies_in_checkout( $query ) {
	if ( ! isset( $query ) || headers_sent() ) {
		return;
	}

	parse_str( $query, $data );

	$country = isset( $data['billing_country'] ) ? wc_clean( wp_unslash( $data['billing_country'] ) ) : null;

	if ( $country ) {
		set_country_cookie( COUNTRY_COOKIE, $country );
	}

	if ( isset( $data['ship_to_different_address'] ) ) {
		$shipping_country = isset( $data['shipping_country'] ) ? wc_clean( wp_unslash( $data['shipping_country'] ) ) : null;

		if ( $shipping_country && WC()->customer->get_billing_country( 'edit' ) !== $shipping_country ) {
			set_country_cookie( SHIPPING_COOKIE, $shipping_country );
		}
	} else {
		unset_shipping_country_cookie();
	}
}

/**
 * Manage location cookie setting
 *
 * @param \WC_Customer $customer Customer object.
 */
function manage_location_cookies( $customer ) {
	$billing_country  = $customer->get_billing_country( 'edit' );
	$shipping_country = $customer->get_shipping_country( 'edit' );

	set_country_cookie( COUNTRY_COOKIE, $billing_country );

	if ( $shipping_country !== $billing_country ) {
		set_country_cookie( SHIPPING_COOKIE, $shipping_country );
	} else {
		unset_shipping_country_cookie();
	}
}

/**
 * Set country cookie
 *
 * @param string $cookie_name Cookie name.
 * @param string $country_code Country code.
 */
function set_country_cookie( $cookie_name, $country_code ) {
	if ( headers_sent() ) {
		return;
	}

	if ( ! empty( $_COOKIE[ $cookie_name ] ) && sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ) === $country_code ) {
		return;
	}

	$default_expiration_seconds = intval( apply_filters( 'wc_session_expiration', is_user_logged_in() ? WEEK_IN_SECONDS : 2 * DAY_IN_SECONDS ) );
  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	setcookie( $cookie_name, $country_code, time() + $default_expiration_seconds, '/', COOKIE_DOMAIN, true, false );
}

/**
 * Unet shipping cookie
 */
function unset_shipping_country_cookie() {
	if ( headers_sent() || empty( $_COOKIE[ SHIPPING_COOKIE ] ) ) {
		return;
	}

  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	setcookie( SHIPPING_COOKIE, 0, time() - DAY_IN_SECONDS, '/', COOKIE_DOMAIN, true, false );
}

/**
 * Get country from cookie
 *
 * @param string $context Type of country: 'billing' or 'shipping' or 'any'.
 * @return string|null;
 */
function get_country_from_cookie( $context = 'any' ) {
	$countries = new \WC_Countries();

	if ( ! empty( $_COOKIE[ SHIPPING_COOKIE ] ) && 'billing' !== $context ) {
		$shipping_country_cookie = sanitize_text_field( wp_unslash( $_COOKIE[ SHIPPING_COOKIE ] ) );

		if ( in_array( $shipping_country_cookie, array_keys( $countries->get_shipping_countries() ), true ) ) {
			return $shipping_country_cookie;
		}
	}

	if ( ! empty( $_COOKIE[ COUNTRY_COOKIE ] ) && 'shipping' !== $context ) {
		$country_cookie = sanitize_text_field( wp_unslash( $_COOKIE[ COUNTRY_COOKIE ] ) );

		if ( in_array( $country_cookie, array_keys( $countries->get_allowed_countries() ), true ) ) {
			return $country_cookie;
		}
	}

	return null;
}

/**
 * Get countries with defined shipping methods
 */
function get_delivery_countries() {
	$zones     = \WC_Shipping_Zones::get_zones();
	$countries = array();

	foreach ( $zones as $zone ) {
		$shipping_zone = \WC_Shipping_Zones::get_zone( $zone['zone_id'] );
		$locations     = $shipping_zone->get_zone_locations();

		foreach ( $locations as $country ) {
			$countries[] = $country->code;
		}
	}

	return $countries;
}

/**
 * Use location cookies to generate variation price cache hash
 *
 * @param array       $price_hash Transient hash.
 * @param \WC_Product $product Product object.
 * @param bool        $for_display Price context.
 * @return string;
 */
function set_variations_price_hash( $price_hash, $product, $for_display ) {
	if ( ! $for_display ) {
		return $price_hash;
	}

	$country    = sanitize_key( $_COOKIE[ COUNTRY_COOKIE ] ?? null );
	$vat_exempt = sanitize_key( $_COOKIE[ VAT_EXEMPT_COOKIE ] ?? null );

	if ( $country ) {
		$price_hash[] = $country;
	}

	if ( $vat_exempt ) {
		$price_hash[] = $vat_exempt;
	}

	return $price_hash;
}

/**
 * Set cookie domain if origin server sets default location cookies
 */
function set_cookie_domain() {
	if ( ! empty( $_COOKIE[ SERVER_COOKIE ] ) ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		define( 'COOKIE_DOMAIN', $_SERVER['HTTP_HOST'] );
	}
}

/**
 * Get a list of cookies and currencies for delivery info.
 */
function get_delivery_cookies() {
	$countries        = get_delivery_countries();
	$country_currency = get_currency_by_country();
	$delivery_cookies = array();

	foreach ( $countries as $country ) {
		$delivery_cookies[] = array(
			COUNTRY_COOKIE  => $country,
			CURRENCY_COOKIE => isset( $country_currency[ $country ] ) ? $country_currency[ $country ] : 'USD',
		);
	}

	return $delivery_cookies;
}
