<?php
/**
 * Chocante Theme
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

require_once get_stylesheet_directory() . '/includes/class-chocante.php';
Chocante::init();

// ACF.
if ( class_exists( 'ACF' ) ) {
	require_once get_stylesheet_directory() . '/includes/class-chocante-acf.php';
	Chocante_ACF::init();
}
