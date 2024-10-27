<?php
/**
 * Main menu top aside
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;

if ( is_active_sidebar( 'header-top' ) ) {
	dynamic_sidebar( 'header-top' );
}
