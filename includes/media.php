<?php
/**
 * Media settings
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Media;

defined( 'ABSPATH' ) || exit;

const IMAGE_QUALITY = 70;

add_filter( 'image_editor_output_format', __NAMESPACE__ . '\set_image_format' );
add_filter( 'wp_editor_set_quality', __NAMESPACE__ . '\set_image_quality' );
add_filter( 'wp_image_editors', __NAMESPACE__ . '\set_image_editor' );
add_filter( 'wp_kses_allowed_html', __NAMESPACE__ . '\escpae_svg', 10, 2 );
add_filter( 'get_custom_logo_image_attributes', __NAMESPACE__ . '\set_custom_logo_size', 10, 2 );
add_filter( 'woocommerce_get_image_size_gallery_thumbnail', __NAMESPACE__ . '\set_product_gallery_thumbnail_size' );

/**
 * Use web format for uploaded images.
 *
 * @param string[] $formats An array of mime type mappings. Maps a source mime type to a new destination mime type. Default empty array.
 * @return string[]
 */
function set_image_format( $formats ) {
	$formats['image/jpg']           = 'image/webp';
	$formats['image/jpeg']          = 'image/webp';
	$formats['image/png']           = 'image/webp';
	$formats['image/heic']          = 'image/webp';
	$formats['image/heif']          = 'image/webp';
	$formats['image/heic-sequence'] = 'image/webp';
	$formats['image/heif-sequence'] = 'image/webp';

	return $formats;
}

/**
 * Set quality of uploaded images
 */
function set_images_quality() {
	return IMAGE_QUALITY;
}

/**
 * Use WP GD for avif support
 */
function set_image_editor() {
	return array( 'WP_Image_Editor_GD' );
}

/**
 * Add SVG tags to wp_kses_post.
 *
 * @param array[] $html Allowed HTML tags.
 * @param string  $context Context name.
 * @return array[]
 */
function escpae_svg( $html, $context ) {
	if ( 'post' !== $context ) {
		return $html;
	}

	$html['svg'] = array(
		'xmlns'           => true,
		'fill'            => true,
		'viewbox'         => true,
		'role'            => true,
		'aria-hidden'     => true,
		'aria-labelledby' => true,
		'class'           => true,
		'width'           => true,
		'height'          => true,
	);

	$html['path'] = array(
		'd'               => true,
		'fill'            => true,
		'stroke'          => true,
		'stroke-width'    => true,
		'stroke-linecap'  => true,
		'stroke-linejoin' => true,
	);

	$html['g'] = array(
		'fill'      => true,
		'transform' => true,
	);

	$html['circle'] = array(
		'cx'   => true,
		'cy'   => true,
		'r'    => true,
		'fill' => true,
	);

	return $html;
}

/**
 * Modify custom logo size attributes
 *
 * @param array $logo_atts Custom logo attributes.
 * @param array $image_id Logo image ID.
 * @return array
 */
function set_custom_logo_size( $logo_atts, $image_id ) {
	$logo = wp_get_attachment_metadata( $image_id );

	if ( $logo ) {
		$logo_atts['width']  = $logo['width'];
		$logo_atts['height'] = $logo['height'];
	}

	return $logo_atts;
}

/**
 * Set woocommerce_gallery_thumbnail image size.
 */
function set_product_gallery_thumbnail_size() {
	return array(
		'width'  => 150,
		'height' => 150,
		'crop'   => 1,
	);
}
