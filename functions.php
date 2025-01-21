<?php
/**
 * Chocante Theme
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

require_once get_theme_file_path() . '/includes/class-chocante.php';
Chocante::init();

// ACF.
if ( class_exists( 'ACF' ) ) {
	require_once get_theme_file_path() . '/includes/class-chocante-acf.php';
	Chocante_ACF::init();
}
