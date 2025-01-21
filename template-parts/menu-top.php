<?php
/**
 * Main menu top aside
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

if ( is_active_sidebar( 'header-top' ) ) {
	dynamic_sidebar( 'header-top' );
}
