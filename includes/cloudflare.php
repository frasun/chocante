<?php
/**
 * Cloudflare
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Cloudflare;

defined( 'ABSPATH' ) || exit;

use LiteSpeed\Base;
use LiteSpeed\Control;
use LiteSpeed\Tag;

use function Chocante\Cache\find_tag_id;
use function Chocante\Currency\get_currency_by_country;

use const Chocante\Cache\TAG_PRODUCT_STOCK;
use const Chocante\Cache\TAG_CURRENCY;
use const Chocante\Cache\TAG_POST_RATING;

add_action( 'wp_finalized_template_enhancement_output_buffer', __NAMESPACE__ . '\send_headers' );
add_filter( 'litespeed_purge_tags', __NAMESPACE__ . '\purge_tags', 30 );
add_action( 'litespeed_update_confs', __NAMESPACE__ . '\update_worker_drop_qs' );
add_action( 'chocante_currency_settings_saved', __NAMESPACE__ . '\update_worker_currencies' );

/**
 * Add CF headers
 */
function send_headers() {
	if ( defined( 'LSCACHE_IS_ESI' ) ) {
		return;
	}

	foreach ( headers_list() as $header ) {
		if ( stripos( $header, Control::X_HEADER ) !== false ) {
			$control = trim( explode( ':', $header, 2 )[1] );
			header( 'Cache-Control: ' . $control );
		}

		if ( stripos( $header, Tag::X_HEADER ) !== false ) {
			$tags = trim( explode( ':', $header, 2 )[1] );
			header( 'Cache-Tag: ' . $tags );
		}
	}
}

/**
 * Purge CF by tag
 *
 * @param array $tags Purge tags.
 */
function purge_tags( $tags ) {
	global $final_tags;

	if ( ! $final_tags || ! defined( 'LSWCP_TAG_PREFIX' ) ) {
		return $tags;
	}

	$cf_tags = $tags;

	foreach ( $tags as $tag ) {
		if ( find_tag_id( $tag, '_' . TAG_PRODUCT_STOCK, $matches ) || find_tag_id( $tag, '_' . TAG_POST_RATING, $matches ) ) {
			$cf_tags[] = '_' . Tag::TYPE_POST . $matches[1];
			continue;
		}

		if ( find_tag_id( $tag, '_' . TAG_CURRENCY, $matches ) ) {
			$cf_tags[] = '_' . TAG_CURRENCY . $matches[1];
			continue;
		}

		if ( find_tag_id( $tag, '_' . Tag::TYPE_ESI . 'header', $matches ) || '*' === $tag ) {
			$cf_tags = array( '_' );
			break;
		}
	}

	foreach ( $cf_tags as &$tag ) {
		$tag = LSWCP_TAG_PREFIX . $tag;
	}

	purge_cf( $cf_tags );

	return $tags;
}

/**
 * Purge CF
 *
 * @param array $tags Purge tags.
 */
function purge_cf( $tags = array() ) {
	if ( ! defined( 'CF_ZONE_ID' ) || ! defined( 'CF_API_TOKEN' ) ) {
		return;
	}

	if ( empty( $tags ) ) {
		return;
	}

	$api_url = 'https://api.cloudflare.com/client/v4/zones/' . CF_ZONE_ID . '/purge_cache';
	$args    = array(
		'headers' => array(
			'Authorization' => 'Bearer ' . CF_API_TOKEN,
			'Content-Type'  => 'application/json',
		),
		'body'    => wp_json_encode( array( 'tags' => $tags ), JSON_UNESCAPED_SLASHES ),
	);

	$response = wp_remote_post( $api_url, $args );

	if ( is_wp_error( $response ) ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $response->get_error_message() );
	} elseif ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( wp_remote_retrieve_body( $response ) );
	}
}

/**
 * Update droop query string settings in CF worker
 *
 * @param array $conf LSC settings.
 */
function update_worker_drop_qs( $conf ) {
	if ( empty( $conf[ Base::O_CACHE_DROP_QS ] ) ) {
		return;
	}

	$drop_qs = array_map( 'trim', explode( PHP_EOL, $conf[ Base::O_CACHE_DROP_QS ] ) );
	update_cf_env( 'DROP_QS', wp_json_encode( $drop_qs ) );
}

/**
 * Update CF env variable
 *
 * @param string $name Name of the env variable.
 * @param string $value Variable value.
 */
function update_cf_env( $name, $value ) {
	if ( ! defined( 'CF_ACCOUNT_ID' ) || ! defined( 'CF_API_TOKEN' ) || ! defined( 'CF_WORKER' ) ) {
		return;
	}

	$api_url = 'https://api.cloudflare.com/client/v4/accounts/' . CF_ACCOUNT_ID . '/workers/scripts/' . CF_WORKER . '/secrets';
	$args    = array(
		'method'  => 'PUT',
		'headers' => array(
			'Authorization' => 'Bearer ' . CF_API_TOKEN,
			'Content-Type'  => 'application/json',
		),
		'body'    => wp_json_encode(
			array(
				'name' => $name,
				'type' => 'secret_text',
				'text' => $value,
			)
		),
	);

	$response = wp_remote_request( $api_url, $args );

	if ( is_wp_error( $response ) ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $response->get_error_message() );
	} elseif ( ! in_array( wp_remote_retrieve_response_code( $response ), array( 200, 201 ), true ) ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( wp_remote_retrieve_body( $response ) );
	}
}

/**
 * Update default currency by country
 *
 * @param array $settings Currency settings.
 */
function update_worker_currencies( $settings ) {
	$country_currency = get_currency_by_country( $settings );

	update_cf_env( 'COUNTRY_CURRENCY', wp_json_encode( $country_currency ) );
}
