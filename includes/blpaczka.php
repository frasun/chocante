<?php
/**
 * BL Paczka API integration
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\BLPaczka;

defined( 'ABSPATH' ) || exit;

use WC_Countries;
use BLPaczka_Shipping;

use function Chocante\Woo\has_postcode_validation;

const API_URL     = 'https://api.blpaczka.com/api/';
const CACHE_GROUP = 'chocante_blpaczka_api';
const CACHE_TTL   = 300;
const API_TIMEOUT = 10;

// Get POD data.
add_action( 'rest_api_init', __NAMESPACE__ . '\register_endpoint_get_devivery_points' );

/**
 * Get package price
 *
 * @param string $country Country code.
 * @param float  $weight Package weight in kg.
 * @param float  $side_x Package dimension x in cm.
 * @param float  $side_y Pacakge dimension y in cm.
 * @param float  $side_z Package dimension z in cm.
 * @param float  $uptake Order value in country currency.
 * @param bool   $pod Point of delivery.
 * @return array|false
 */
function get_valuation( $country, $weight = 1, $side_x = 10, $side_y = 10, $side_z = 10, $uptake = 0, $pod = false ) {
	$key     = md5( "{$country}-{$weight}-{$side_x}-{$side_y}-{$side_z}-{$uptake}" );
	$results = wp_cache_get( $key, CACHE_GROUP );

	if ( false === $results ) {
		$results = fetch_valuation( $country, $weight, $side_x, $side_y, $side_z, $uptake );

		if ( empty( $results ) ) {
			return false;
		}

		wp_cache_set( $key, $results, CACHE_GROUP, CACHE_TTL );
	}

	return pick_offer( $results, $pod );
}

/**
 * Fetch valuation from the API
 *
 * @param string $country Country code.
 * @param float  $weight Package weight in kg.
 * @param float  $side_x Package dimension x in cm.
 * @param float  $side_y Pacakge dimension y in cm.
 * @param float  $side_z Package dimension z in cm.
 * @param float  $uptake Order value in country currency.
 * @return array|null
 */
function fetch_valuation( $country, $weight = 1, $side_x = 10, $side_y = 10, $side_z = 10, $uptake = 0 ) {
	if ( ! defined( 'BLPACZKA_API_LOGIN' ) || ! defined( 'BLPACZKA_API_KEY' ) ) {
		log_error( 'Missing API credentials' );
		return null;
	}

	$search_args   = array(
		'country_code' => $country,
		'type'         => 'package',
		'weight'       => $weight,
		'side_x'       => $side_x,
		'side_y'       => $side_y,
		'side_z'       => $side_z,
		'uptake'       => $uptake,
		'sortable'     => true,
		'no_pickup'    => false,
		'origin'       => 'woocommerce',
	);
	$shop_postcode = WC()->countries->get_base_postcode();

	if ( ! empty( $shop_postcode ) ) {
		$search_args['postal_sender'] = wc_normalize_postcode( $shop_postcode );
	}

	$api_url = API_URL . 'getValuation.json';
	$args    = array(
		'headers' => array(
			'Content-Type' => 'application/json',
		),
		'body'    => wp_json_encode(
			array(
				'auth'          => get_auth(),
				'CourierSearch' => $search_args,
			)
		),
		'timeout' => API_TIMEOUT,
	);

	$response = wp_remote_post( $api_url, $args );

	if ( is_wp_error( $response ) ) {
		log_error( $response->get_error_message() );
		return null;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( true !== $body['success'] || empty( $body['data']['results'] ) ) {
		$error = ! empty( $body['message'] ) ? $body['message'] : implode( ' ', array_values( $body['data']['errors'] ) );
		log_error( $error );
		return null;
	}

	return $body['data']['results'];
}

/**
 * Get the best quoute from valuation response
 *
 * @param array $offers Courier data.
 * @param bool  $pod Point of delivery.
 * @return array|false
 */
function pick_offer( $offers, $pod = false ) {
	$filtered = array_filter( $offers, fn( $c ) => $c['Courier']['taker_point_required'] === $pod );

	if ( empty( $filtered ) ) {
		return false;
	}

	$selected = array_values( $filtered )[0];

	return array(
		'price'   => $selected['Price']['value'],
		'courier' => $selected['Courier']['name'],
		'code'    => $selected['Courier']['courier_code'],
	);
}

/**
 * Log errors from the API
 *
 * @param string $message Error message.
 */
function log_error( $message ) {
  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( '[BLPACZKA] ' . $message );
}

/**
 * Register API endpoint for delivery points
 */
function register_endpoint_get_devivery_points() {
	register_rest_route(
		'chocante',
		'/pod',
		array(
			'methods'             => 'GET',
			'callback'            => __NAMESPACE__ . '\fetch_delivery_points',
			'permission_callback' => '__return_true',
			'args'                => array(
				'country'  => array(
					'validate_callback' => __NAMESPACE__ . '\validate_country',
					'sanitize_callback' => 'sanitize_text_field',
					'required'          => true,
				),
				'postcode' => array(
					'validate_callback' => __NAMESPACE__ . '\validate_postcode',
					'sanitize_callback' => 'wc_normalize_postcode',
					'required'          => true,
				),
				'courier'  => array(
					'validate_callback' => __NAMESPACE__ . '\validate_courier',
					'sanitize_callback' => 'sanitize_text_field',
					'required'          => false,
				),
			),
		)
	);
}

/**
 * Validate country based on shop settings
 *
 * @param string $country_code Country code.
 * @return bool
 */
function validate_country( $country_code ) {
	if ( ! (bool) preg_match( '/^[A-Z]{2}$/', $country_code ) ) {
		return false;
	}

	$countries          = new WC_Countries();
	$shipping_countries = $countries->get_shipping_countries();

	return isset( $shipping_countries[ $country_code ] );
}

/**
 * Validate postal code
 *
 * @param string           $postcode Postal code.
 * @param \WP_REST_Request $request Request.
 * @return bool
 */
function validate_postcode( $postcode, $request ) {
	$country = $request->get_param( 'country' );

	if ( ! has_postcode_validation( $country ) ) {
		return true;
	}

	return \WC_Validation::is_postcode( wc_format_postcode( $postcode, $country ), $country );
}

/**
 * Validate courier code
 *
 * @param string $courier Courier code.
 * @return bool
 */
function validate_courier( $courier ) {
	return isset( BLPaczka_Shipping::COURIERS[ $courier ] );
}

/**
 * Fetch delivery point data from the API
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response|\WP_Error
 */
function fetch_delivery_points( $request ) {
	if ( ! defined( 'BLPACZKA_API_LOGIN' ) || ! defined( 'BLPACZKA_API_KEY' ) ) {
		log_error( 'Missing API credentials' );
		return null;
	}

	$api_url = API_URL . 'getPudoPoints.json';
	$args    = array(
		'headers' => array(
			'Content-Type' => 'application/json',
		),
		'body'    => wp_json_encode(
			array(
				'auth'      => get_auth(),
				'PudoPoint' => array(
					'country'      => $request->get_param( 'country' ),
					'postal_code'  => $request->get_param( 'postcode' ),
					'courier_code' => $request->get_param( 'courier' ),
				),
			)
		),
		'timeout' => API_TIMEOUT,
	);

	$response = wp_remote_post( $api_url, $args );

	if ( is_wp_error( $response ) ) {
		return new \WP_Error( '[BLPACZKA] Request failed', wp_json_encode( $response, JSON_PRETTY_PRINT ), array( 'status' => 500 ) );
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( true !== $body['success'] ) {
		return new \WP_Error( '[BLPACZKA] Bad request', $body['message'], array( 'status' => 400 ) );
	}

	return new \WP_REST_Response( $body, wp_remote_retrieve_response_code( $response ) );
}

/**
 * Get API authentication
 */
function get_auth() {
	return array(
		'login'   => BLPACZKA_API_LOGIN,
		'api_key' => BLPACZKA_API_KEY,
	);
}
