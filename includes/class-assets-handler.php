<?php
/**
 * Theme assets
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante;

defined( 'ABSPATH' ) || exit;

/**
 * Asset import handler.
 */
class Assets_Handler {
	/**
	 * Assets.
	 *
	 * @var array;
	 */
	private static $assets = array();

	/**
	 * Assets helper.
	 *
	 * @param string $key Name of the asset.
	 * @return array
	 */
	public static function include( $key ) {
		if ( ! isset( self::$assets[ $key ] ) ) {
			self::$assets[ $key ] = include get_theme_file_path( "build/{$key}.asset.php" );
		}

		return self::$assets[ $key ];
	}
}
