<?php
/**
 * Plugin Name: Chocante Crawler
 * Description: Cache crawler with cookie/UA rules, cron integration and purge hook support
 *
 * Constants (define in wp-config.php):
 *   CHOCANTE_CRAWLER_CONFIG    — absolute path to config.php
 *   CHOCANTE_CRAWLER_LOG       — set true to always save logs to disk
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Crawler;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'LSCWP_V' ) ) {
	return;
}

use LiteSpeed\Tag;
use LiteSpeed\Base;
use LiteSpeed\Thirdparty\WooCommerce;

use function Chocante\Translations\add_translated_urls;
use function Chocante\Cache\find_tag_id;

use const Chocante\Cache\TAG_CURRENCY;
use const Chocante\Cache\TAG_POST_RATING;
use const Chocante\Cache\TAG_PRODUCT_STOCK;
use const Chocante\Cache\TAG_PRODUCT_FEATURED;
use const Chocante\Currency\CURRENCY_COOKIE;
use const Chocante\Location\VAT_EXEMPT_COOKIE;
use const Chocante\Location\VAT_EXEMPT_COOKIES;

define( 'CHOCANTE_CRAWLER_DIR', __DIR__ );

require_once __DIR__ . '/crawler/Engine.php';
require_once __DIR__ . '/crawler/Queue.php';

if ( defined( 'WP_CLI' ) && \WP_CLI ) {
	require_once __DIR__ . '/crawler/CLI.php';
}

add_action( 'chocante_crawler_enqueue', array( 'Chocante_Crawler_Queue', 'enqueue' ) );
add_action( 'litespeed_task_crawler', array( 'Chocante_Crawler_Queue', 'cron_run' ) );

add_filter( 'litespeed_purge_tags', __NAMESPACE__ . '\get_urls_from_tags' );
add_action( 'litespeed_purged_link', __NAMESPACE__ . '\crawl_url' );
add_action( 'litespeed_purged_all', __NAMESPACE__ . '\crawl_full_site' );
add_action( 'litespeed_purged_all_lscache', __NAMESPACE__ . '\crawl_full_site' );

add_filter( 'chocante_crawler_config_urls', __NAMESPACE__ . '\get_shop_pages' );
add_filter( 'chocante_crawler_config_urls', __NAMESPACE__ . '\get_delivery_info' );

// Full site crawl.
// phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
add_filter( 'cron_schedules', __NAMESPACE__ . '\add_crawler_schedule' );
add_action( 'init', __NAMESPACE__ . '\schedule_full_crawl' );
add_action( 'chocante_crawler_run', __NAMESPACE__ . '\fresh_crawl' );

/**
 * Build crawler sitemap based on purge tags
 *
 * @param array $tags Tags to purge.
 * @return array
 */
function get_urls_from_tags( $tags ) {
	global $final_tags;

	if ( ! $final_tags ) {
		return $tags;
	}

	// Skip purge all on menu updates.
	$esi_header = '_' . Tag::TYPE_ESI . 'header';
	if ( in_array( $esi_header, $tags, true ) ) {
		$tags = array( $esi_header );
	}

	// Skip AJAX.get_product_section purge - handle via term ids.
	remove_product_section_tag( $tags );

	$urls = array();

	foreach ( $tags as $tag ) {
		$tag = ltrim( $tag, '_' );

		if ( find_tag_id( $tag, Tag::TYPE_POST, $matches ) ) {
			$posttype = get_post_type( $matches[1] );

			if ( ! in_array( $posttype, array( 'post', 'page', 'product' ), true ) ) {
				continue;
			}

			// Handle post removal.
			if ( 'publish' !== get_post_status( $matches[1] ) ) {
				continue;
			}

			if ( 'post' === $posttype ) {
				$urls[] = get_permalink( get_option( 'page_for_posts' ) );
			}

			if ( 'product' === $posttype ) {
				$product = wc_get_product( $matches[1] );

				// Product tile.
				$is_visible = $product->is_visible();

				if ( $is_visible ) {
					$urls[] = get_esi_url( 'product_tile', 'public', array( 'id' => (int) $matches[1] ) );
				}

				// Product attributes.
				$attributes         = $product->get_attributes();
				$product_taxonomies = array();

				foreach ( $attributes as $attribute ) {
					if ( $attribute->is_taxonomy() && $attribute->get_visible() ) {
						$taxonomy = $attribute->get_taxonomy_object();
						if ( $taxonomy->attribute_public ) {
							foreach ( $attribute->get_options() as $term_id ) {
								$product_taxonomies[] = $term_id;
							}
						}
					}
				}

				foreach ( $product_taxonomies as $pa ) {
					$tags[]  = '_' . Tag::TYPE_ARCHIVE_TERM . $pa;
					$pa_term = get_term( $pa );
					$urls[]  = get_term_link( $pa_term );
				}

				// Featured.
				if ( $product->is_featured() ) {
					$tags[] = '_' . TAG_PRODUCT_FEATURED;
					$urls[] = get_permalink( get_option( 'page_on_front' ) );
				}
			}

			$urls[] = get_permalink( $matches[1] );
			continue;
		}

		if ( Tag::TYPE_HOME === $tag ) {
			$urls[] = get_permalink( get_option( 'page_for_posts' ) );
			continue;
		}

		if ( Tag::TYPE_FRONTPAGE === $tag ) {
			$urls[] = get_permalink( get_option( 'page_on_front' ) );
			continue;
		}

		if ( find_tag_id( $tag, Tag::TYPE_ARCHIVE_POSTTYPE, $matches ) ) {
			$urls[] = get_post_type_archive_link( get_post_type( $matches[1] ) );
			continue;
		}

		if ( find_tag_id( $tag, Tag::TYPE_ARCHIVE_TERM, $matches ) ) {
			$term   = get_term( $matches[1] );
			$urls[] = get_term_link( $term );
			continue;
		}

		if ( find_tag_id( $tag, Tag::TYPE_ESI, $matches ) ) {
			$site_wide_esi = array( 'header', 'footer', 'overlays' );

			if ( in_array( $matches[1], $site_wide_esi, true ) ) {
				$urls[] = home_url();
			}
			continue;
		}

		if ( WooCommerce::CACHETAG_SHOP === $tag ) {
			$urls = array_merge( $urls, get_shop_urls( true ) );
			continue;
		}

		if ( find_tag_id( $tag, WooCommerce::CACHETAG_TERM, $matches ) ) {
			$term   = get_term( $matches[1] );
			$urls[] = get_term_link( $term );
			continue;
		}

		if ( find_tag_id( $tag, TAG_POST_RATING, $matches ) ) {
			$urls[] = get_permalink( $matches[1] );
			continue;
		}

		if ( find_tag_id( $tag, TAG_PRODUCT_STOCK, $matches ) ) {
			$urls[] = get_permalink( $matches[1] );
			continue;
		}

		if ( find_tag_id( $tag, TAG_CURRENCY, $matches ) ) {
			crawl_currency( $matches[1] );
			continue;
		}
	}

	if ( ! empty( $urls ) ) {
		crawl_urls( $urls );
	}

	return $tags;
}

/**
 * Get main catalog urls
 *
 * @param bool $on_sale Show sales page.
 * @param bool $paged Include all paged views.
 * @return string[]
 */
function get_shop_urls( $on_sale = false, $paged = false ) {
	$shop_urls = array();
	$shop_url  = get_permalink( wc_get_page_id( 'shop' ) );

	$shop_urls[] = $shop_url;

	if ( $on_sale ) {
		$shop_urls[] = $shop_url . '?filter_on_sale=1';
	}

	if ( $paged ) {
		$product_query_args = array(
			'status'     => 'publish',
			'visibility' => 'catalog',
			'limit'      => 1,
			'paginate'   => true,
		);
		$hide_out_of_stock  = ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) );

		if ( $hide_out_of_stock ) {
			$product_query_args['stock_status'] = 'instock';
		}

		$product_query = wc_get_products( $product_query_args );

		$total_products = $product_query->total;
		$per_page       = apply_filters( 'loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page() );
		$total_pages    = ceil( $total_products / $per_page );
		$paged_base     = $GLOBALS['wp_rewrite']->pagination_base;

		for ( $i = 2; $i <= $total_pages; $i++ ) {
			$shop_urls[] = trailingslashit( $shop_url ) . $paged_base . '/' . $i . '/';
		}
	}

	return $shop_urls;
}

/**
 * Add urls to crawler queue
 *
 * @param array $urls URLs to crawl.
 */
function crawl_urls( $urls ) {
	if ( empty( $urls ) ) {
		return;
	}

	$urls = array_unique( $urls );
	add_translated_urls( $urls );

	do_action( 'chocante_crawler_enqueue', array( 'urls' => $urls ) );
}

/**
 * Add urls with currency tags
 *
 * @param string $currency Currency code.
 */
function crawl_currency( $currency ) {
	$cookies = array(
		CURRENCY_COOKIE   => array( $currency ),
		VAT_EXEMPT_COOKIE => VAT_EXEMPT_COOKIES,
	);

	do_action(
		'chocante_crawler_enqueue',
		array(
			'sitemap' => home_url( '/product-sitemap.xml' ),
			'cookies' => $cookies,
		)
	);

	$urls = get_shop_urls( true, true );
	add_translated_urls( $urls );

	do_action(
		'chocante_crawler_enqueue',
		array(
			'urls'    => $urls,
			'cookies' => $cookies,
		)
	);
}

/**
 * Add paged shop urls to crawler config
 *
 * @param string[] $urls A set of urls to pass to crawler config.
 */
function get_shop_pages( $urls ) {
	$shop_links = get_shop_urls( true, true );
	add_translated_urls( $shop_links );

	return array_merge( $urls, $shop_links );
}

/**
 * Get porudct tile ESI block url
 *
 * @see Litespeed\ESI::_gen_esi_md5, LiteSpeed\ESI::sub_esi_block
 *
 * @param string $block_id ESI block id.
 * @param string $control ESI control.
 * @param array  $params ESI block params.
 * @return string
 */
function get_esi_url( $block_id, $control, $params = array() ) {
	$appended_params = array(
		'lsesi'    => $block_id,
		'_control' => $control,
	);

	if ( defined( 'ESI_COMMENTS' ) && ESI_COMMENTS ) {
		$params['_ls_silence'] = true;
	}

	if ( ! empty( $params ) ) {
		$appended_params['esi'] = base64_encode( wp_json_encode( $params ) );
	}

	$hash = apply_filters( 'litespeed_conf', Base::HASH );
	foreach ( $appended_params as $param ) {
		$hash .= $param;
	}

	$appended_params['_hash'] = md5( $hash );

	return add_query_arg( array_map( 'urlencode', $appended_params ), trailingslashit( home_url() ) );
}

/**
 * Remove AJAX product setion from purge tags and use category tags for purging
 *
 * @param array $tags Array of purge tags.
 */
function remove_product_section_tag( &$tags ) {
	$tags = array_values( array_diff( $tags, array( '_' . Tag::TYPE_AJAX . 'get_product_section' ) ) );
}

/**
 * Get delivery info ESI block
 *
 * @param string[] $urls Links for crawler.
 * @return string[]
 */
function get_delivery_info( $urls ) {
	$urls[] = get_esi_url( 'delivery_info', 'public' );
	add_translated_urls( $urls );

	return $urls;
}

/**
 * Add cron schedule based on LSC conf
 *
 * @param array $schedules An array of non-default cron schedules keyed by the schedule name. Default empty array.
 * @return array
 */
function add_crawler_schedule( $schedules ) {
	$interval                      = (int) apply_filters( 'litespeed_conf', Base::O_CRAWLER_CRAWL_INTERVAL );
	$schedules['chocante_crawler'] = array(
		'interval' => $interval ? $interval : WEEK_IN_SECONDS,
		'display'  => 'Chocante Crawler',
	);

	return $schedules;
}

/**
 * Crawl the full site
 */
function crawl_full_site() {
	global $crawl_full_site;

	if ( $crawl_full_site ) {
		return;
	}

	$crawl_full_site = true;

	do_action( 'chocante_crawler_enqueue' );
}

/**
 * Purge all and crawl
 */
function fresh_crawl() {
	do_action( 'litespeed_purge_all' );
}

/**
 * Schedule full site crawl
 */
function schedule_full_crawl() {
	if ( ! wp_next_scheduled( 'chocante_crawler_run' ) ) {
		wp_schedule_event( time(), 'chocante_crawler', 'chocante_crawler_run' );
	}
}

/**
 * Crawl single url
 *
 * @param string $url URL to crwl.
 */
function crawl_url( $url ) {
	$urls = array( $url );
	add_translated_urls( $urls, false );

	do_action( 'chocante_crawler_enqueue', array( 'urls' => $urls ) );
}
