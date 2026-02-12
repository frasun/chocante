<?php
/**
 * Theme blocks
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Blocks;

defined( 'ABSPATH' ) || exit;

add_action( 'init', __NAMESPACE__ . '\init_blocks' );

/**
 * Register theme blocks.
 */
function init_blocks() {
	register_block_type( dirname( __DIR__ ) . '/build/infobar' );
	wp_set_script_translations( 'chocante-infobar-editor-script', 'chocante', get_theme_file_path( 'languages' ) );
}
