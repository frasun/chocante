<?php
/**
 * Translation settings
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Translations;

defined( 'ABSPATH' ) || exit;

add_filter( 'chocante_product_section_script_data', __NAMESPACE__ . '\add_language_to_product_section_script' );
add_action( 'chocante_product_section_ajax_get', __NAMESPACE__ . '\switch_product_section_language' );

/**
 * Add current language information to product section script
 *
 * @param array $script_data Script localization data.
 * @return array
 */
function add_language_to_product_section_script( $script_data ) {
	$script_data['lang'] = apply_filters( 'wpml_current_language', null );

	return $script_data;
}

/**
 * Switch language when getting products to display in product section.
 */
function switch_product_section_language() {
  // phpcs:disable WordPress.Security.NonceVerification.Recommended
	$lang         = isset( $_GET['lang'] ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : null;
	$current_lang = apply_filters( 'wpml_current_language', null );

	if ( $lang !== $current_lang ) {
		do_action( 'wpml_switch_language', $lang );
	}
}
