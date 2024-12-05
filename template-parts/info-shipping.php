<?php
/**
 * Fast shipping infp
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fast shipping
 */
// translators: Fast shipping heading.
$heading = __( 'Fast shipping', 'chocante' );
// translators: Fast shipping content.
$content = __( 'Order before 11 a.m. and we will ship it the same day.', 'chocante' );
get_template_part(
	'template-parts/info',
	'section',
	array(
		'icon'    => 'clock',
		'heading' => $heading,
		'content' => $content,
	)
);
