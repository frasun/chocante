<?php
/**
 * Chocante Theme
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Chocante class.
 */
class Chocante {
	/**
	 * Class constructor.
	 */
	public static function init() {
		// Setup.
		add_action( 'after_setup_theme', array( self::class, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_scripts' ) );

		// WooCommerce.
		add_filter( 'woocommerce_pagination_args', array( self::class, 'set_pagination_args' ) );
		add_filter( 'woocommerce_catalog_orderby', array( self::class, 'set_caralog_orderby' ) );
	}

	/**
	 * Load textdomain
	 */
	public static function load_textdomain() {
		load_theme_textdomain( 'chocante', get_stylesheet_directory() . '/languages' );
	}

	/**
	 * Enqueue scripts & styles
	 */
	public static function enqueue_scripts() {
		if ( ! bricks_is_builder_main() ) {
			$styles = include get_stylesheet_directory() . '/build/styles.asset.php';

			wp_enqueue_style(
				'chocante',
				get_stylesheet_directory_uri() . '/build/styles.css',
				$styles['dependencies'],
				$styles['version'],
			);

				$scripts = include get_stylesheet_directory() . '/build/scripts.asset.php';

				wp_enqueue_script(
					'chocante',
					get_stylesheet_directory_uri() . '/build/scripts.js',
					$scripts['dependencies'],
					$scripts['version'],
					array(
						'in_footer' => true,
						'strategy'  => 'defer',
					)
				);
		}
	}

	/**
	 * Set shop pagination arguments
	 *
	 * @param array $pagination Pagination args.
	 * @return array
	 */
	public static function set_pagination_args( $pagination ) {
		$pagination['prev_text'] = '';
		$pagination['next_text'] = '';

		return $pagination;
	}

	/**
	 * Set orderby options
	 */
	public static function set_caralog_orderby() {
		return array(
			'menu_order' => _x( 'Default', 'sorting option', 'chocante' ),
			'popularity' => _x( 'Popularity', 'sorting option', 'chocante' ),
			'rating'     => _x( 'Average rating', 'sorting option', 'chocante' ),
			'date'       => _x( 'Latest', 'sorting option', 'chocante' ),
			'price'      => _x( 'Lowest price', 'sorting option', 'chocante' ),
			'price-desc' => _x( 'Highest price', 'sorting option', 'chocante' ),
		);
	}
}
