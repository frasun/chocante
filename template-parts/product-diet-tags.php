<?php
/**
 * Product Tags
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $args['product_tags'] ) || empty( $args['product_tags'] ) ) {
	return;
}

foreach ( $args['product_tags'] as $ptag ) {
	$thumbnail = Chocante_Product_Tags::get_tag_thumbnail_url( $ptag->term_id );

	if ( $thumbnail ) {
		// Handle different width - inconsistency in uploaded icons.
		printf( "<img src='%s' alt='%s' class='diet-info__tag-thumbnail' height='%s' loading='lazy' />", esc_url( $thumbnail ), esc_attr( $ptag->name ), esc_attr( 56 ) );
	}
}
