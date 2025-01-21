<?php
/**
 * Fast shipping infp
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fast shipping
 */
// translators: Fast shipping heading.
$heading = _x( 'Fast shipping', 'infobox', 'chocante' );
// translators: Fast shipping content.
$content = _x( 'Order before 11 a.m. and we will ship it the same day.', 'infobox', 'chocante' );
get_template_part(
	'template-parts/info',
	'section',
	array(
		'icon'    => 'clock',
		'heading' => $heading,
		'content' => $content,
	)
);
