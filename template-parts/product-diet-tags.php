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

$image_size = wc_get_image_size( 'shop_catalog' );

foreach ( $args['product_tags'] as $ptag ) {
	$thumbnail = Chocante_Product_Tags::get_tag_thumbnail_url( $ptag->term_id );

	if ( $thumbnail ) {
		printf( "<img src='%s' alt='%s' class='diet-info__tag-thumbnail' width='%s' />", esc_url( $thumbnail ), esc_attr( $ptag->name ), esc_attr( $image_size['width'] ) );
	}
}
