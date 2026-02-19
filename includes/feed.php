<?php
/**
 * Product feed settings
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Feed;

defined( 'ABSPATH' ) || exit;

add_filter( 'adt_product_feed_platform_allowes_html_fields', __NAMESPACE__ . '\strip_all_tags' );

/**
 * Strip all HTML tags from content.
 *
 * @return array
 */
function strip_all_tags() {
	return array();
}
