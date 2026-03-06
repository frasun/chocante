<?php
/**
 * Crawler config: wp-content/crawler/config.php
 *
 * @package Claude crawler (hey Claude)
 */

namespace Chocante\Crawler\Config;

use function Chocante\Currency\get_currencies;
use function Chocante\Crawler\get_shop_urls;
use function Chocante\Location\get_delivery_countries;
use function Chocante\Translations\add_translated_urls;

use const Chocante\Currency\CURRENCY_COOKIE;
use const Chocante\Location\COUNTRY_COOKIE;
use const Chocante\Location\VAT_EXEMPT_COOKIE;
use const Chocante\Location\VAT_EXEMPT_COOKIES;

$full_ua = 'Mozilla/5.0';

// Home.
$home_url = array( home_url() );
add_translated_urls( $home_url );
$home_pattern = '/(' . implode( '|', array_map( fn( $u ) => preg_quote( $u, '/' ), $home_url ) ) . ')$/';

// Currencies.
$currencies = array_merge( array( '_null' ), get_currencies() );

// Shop links.
$shop_slugs = get_shop_urls();
add_translated_urls( $shop_slugs );

foreach ( $shop_slugs as &$slug ) {
	$slug = basename( $slug );
}

$shop_pattern = '/\/(' . implode( '|', array_map( 'preg_quote', $shop_slugs ) ) . ')\//';

// Country / currency.
$countries = get_delivery_countries();

return array(

	'sitemaps' => array(
		home_url( apply_filters( 'rank_math/sitemap/index/slug', '/sitemap_index' ) . '.xml' ),
	),

	'urls'     => function () {
		return apply_filters( 'chocante_crawler_config_urls', array() );
	},

	/**
	 * Rules — first match wins.
	 * cookies: cookie_name => ['_null', 'val1', 'val2']
	 *   '_null'  = one variant with NO cookie set
	 *   'val' = one variant with cookie=val
	 * ua: null = use default_ua, string = override
	 */
	'rules'    => array(

		// Product pages.
		array(
			'match'   => '/\/p\//',
			'cookies' => array(
				CURRENCY_COOKIE   => $currencies,
				VAT_EXEMPT_COOKIE => VAT_EXEMPT_COOKIES,
			),
			'ua'      => $full_ua,
		),

		// Shop pages.
		array(
			'match'   => $shop_pattern,
			'cookies' => array(
				CURRENCY_COOKIE   => $currencies,
				VAT_EXEMPT_COOKIE => VAT_EXEMPT_COOKIES,
			),
			'ua'      => $full_ua,
		),

		// Product tile.
		array(
			'match'   => 'lsesi=product_tile',
			'cookies' => array(
				CURRENCY_COOKIE   => $currencies,
				VAT_EXEMPT_COOKIE => VAT_EXEMPT_COOKIES,
			),
		),

		// Header / footer / overlays.
		array(
			'match'   => $home_pattern,
			'cookies' => array(
				CURRENCY_COOKIE => $currencies,
			),
			'ua'      => $full_ua,
		),

		// Delivery info.
		array(
			'match'   => 'lsesi=delivery_info',
			'cookies' => array(
				COUNTRY_COOKIE  => $countries,
				CURRENCY_COOKIE => array( 'PLN', 'EUR' ), // @todo: use curcy settings.
			),
			// 'combos'  => array(
			// array(
			// COUNTRY_COOKIE  => 'PL',
			// CURRENCY_COOKIE => 'PLN',
			// ),
			// array(
			// COUNTRY_COOKIE  => 'DE',
			// CURRENCY_COOKIE => 'EUR',
			// ),
			// array(
			// COUNTRY_COOKIE  => 'FR',
			// CURRENCY_COOKIE => 'EUR',
			// ),
			// array(
			// COUNTRY_COOKIE  => 'ES',
			// CURRENCY_COOKIE => 'EUR',
			// ),
			// array(
			// COUNTRY_COOKIE  => 'GB',
			// CURRENCY_COOKIE => 'GBP',
			// ),
			// array(
			// COUNTRY_COOKIE  => 'GB',
			// CURRENCY_COOKIE => 'GBP',
			// ),
			// array(
			// COUNTRY_COOKIE  => 'US',
			// CURRENCY_COOKIE => 'USD',
			// ),
			// array(
			// COUNTRY_COOKIE  => 'NO',
			// CURRENCY_COOKIE => 'NOK',
			// ),
			// array(
			// COUNTRY_COOKIE  => 'SE',
			// CURRENCY_COOKIE => 'SEK',
			// ),
			// array(
			// COUNTRY_COOKIE  => 'DK',
			// CURRENCY_COOKIE => 'DKK',
			// ),
			// array(
			// COUNTRY_COOKIE  => 'CH',
			// CURRENCY_COOKIE => 'CHF',
			// ),
			// ),
		),

	),

);
