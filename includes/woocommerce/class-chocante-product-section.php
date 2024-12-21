<?php
/**
 * Chocante WooCommerce product section with slider
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chocante_Product_Section class.
 */
class Chocante_Product_Section {
	/**
	 * Display product section
	 *
	 * @param array $args Product section arguments.
	 */
	public static function display_product_section( $args = array() ) {
		$product_section = include get_stylesheet_directory() . '/build/product-section.asset.php';

		wp_enqueue_script(
			'product-section',
			get_stylesheet_directory_uri() . '/build/product-section.js',
			array_merge( $product_section['dependencies'] ),
			$product_section['version'],
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_localize_script(
			'product-section',
			'chocante',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'chocante' ),
				'lang'    => apply_filters( 'wpml_current_language', null ),
			)
		);

		get_template_part(
			'template-parts/product',
			'section',
			$args
		);
	}

	/**
	 * Fetch products
	 *
	 * @param array   $category Product category IDs.
	 * @param boolean $featured Whether to include featured products.
	 * @param boolean $onsale Include only products that are on sale.
	 * @param boolean $latest Get newly added products.
	 * @param array   $exclude Product IDs to exclude.
	 */
	public static function get_products( $category = array(), $featured = false, $onsale = false, $latest = false, $exclude = array() ) {
		$limit     = 12;
		$orderby   = $latest ? 'id' : 'rand';
		$order     = 'desc';
		$cache_key = 'chocante_products';

		$args = array(
			'limit'      => $limit,
			'orderby'    => $orderby,
			'order'      => $order,
			'visibility' => 'visibile',
			'status'     => 'publish',
		);

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$args['stock_status'] = 'instock';
		}

		if ( ! empty( $category ) ) {
			$args['product_category_id'] = $category;
			$cache_key                  .= '_category-' . implode( '-', $category );
		}

		if ( $featured ) {
			$args['featured'] = $featured;
			$cache_key       .= '_featured';
		}

		if ( $onsale ) {
			$args['include'] = wc_get_product_ids_on_sale();
			$cache_key      .= '_onsale';
		}

		if ( $latest ) {
			$cache_key .= '_latest';
		}

		if ( ! empty( $exclude ) ) {
			$args['exclude'] = $exclude;
			$cache_key       = '_exclude-' . implode( '-', $exclude );
		}

		$products = wp_cache_get( $cache_key, 'chocante_products', false, $products_found );

		if ( false === $products_found ) {
			$products = wc_get_products( $args );
		}

		wp_cache_set( $cache_key, $products, 'chocante_products' );

		$products = wc_products_array_orderby( $products, $orderby, $order );

		add_filter( 'woocommerce_post_class', array( __CLASS__, 'slider_item_class' ) );

		get_template_part(
			'template-parts/product',
			'slider',
			array(
				'products' => $products,
				'labels'   => self::get_slider_labels(),
			)
		);
	}

	/**
	 * Return aria labels for product slider
	 */
	private static function get_slider_labels() {
		return array(
			'prev'       => _x( 'Previous', 'product slider', 'chocante' ),
			'next'       => _x( 'Next', 'product slider', 'chocante' ),
			'first'      => _x( 'Go to first', 'product slider', 'chocante' ),
			'last'       => _x( 'Go to last', 'product slider', 'chocante' ),
			// translators: Go to product number.
			'slideX'     => _x( 'Go to %s', 'product slider', 'chocante' ),
			// translators: Go to page number.
			'pageX'      => _x( 'Go to page %s', 'product slider', 'chocante' ),
			'play'       => _x( 'Start autoplay', 'product slider', 'chocante' ),
			'pause'      => _x( 'Pause autoplay', 'product slider', 'chocante' ),
			'carousel'   => _x( 'carousel', 'product slider', 'chocante' ),
			'select'     => _x( 'Select product to show', 'product slider', 'chocante' ),
			'slide'      => _x( 'product', 'product slider', 'chocante' ),
			'slideLabel' => '%s / %s',
		);
	}

	/**
	 * Return featured products HTML using AJAX
	 */
	public static function ajax_get_products() {
		if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'chocante' ) ) {
			wp_die();
		}

		if ( has_action( 'wpml_switch_language' ) && isset( $_GET['lang'] ) ) {
			do_action( 'wpml_switch_language', sanitize_text_field( wp_unslash( $_GET['lang'] ) ) );
		}

		$category = isset( $_GET['category'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_GET['category'] ) ) ) : array();
		$featured = isset( $_GET['featured'] ) ? sanitize_text_field( wp_unslash( $_GET['featured'] ) ) : false;
		$onsale   = isset( $_GET['onsale'] ) ? sanitize_text_field( wp_unslash( $_GET['onsale'] ) ) : false;
		$latest   = isset( $_GET['latest'] ) ? sanitize_text_field( wp_unslash( $_GET['latest'] ) ) : false;
		$exclude  = isset( $_GET['exclude'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_GET['exclude'] ) ) ) : array();

		ob_start();
		self::get_products( $category, $featured, $onsale, $latest, $exclude );
		echo ob_get_clean(); // @codingStandardsIgnoreLine.

		wp_die();
	}

	/**
	 * Clear cached products used in sliders
	 */
	public static function clear_cached_products() {
		if ( ! wp_cache_supports( 'chocante_products' ) ) {
			wp_cache_flush_group( 'chocante_products' );
		} else {
			wp_cache_flush();
		}
	}

	/**
	 * Add slider class to loop items
	 *
	 * @param string[] $classes Loop item classes.
	 * @return string[]
	 */
	public static function slider_item_class( $classes ) {
		return array_merge( $classes, array( 'splide__slide' ) );
	}

	/**
	 * Output data attribute to product section element
	 *
	 * @param array $args Product query attributes.
	 */
	public static function output_product_section_atts( $args ) {
		unset( $args['heading'] );
		unset( $args['subheading'] );
		unset( $args['cta_link'] );

		$data_atts = '';

		foreach ( $args as $key => $att ) {
			if ( 'boolean' === gettype( $att ) ) {
				$value = $att ? 'true' : 'false';
			}

			if ( 'array' === gettype( $att ) ) {
				$value = implode( ',', $att );
			}

			$data_atts .= " data-{$key}={$value}";
		}

		echo esc_attr( $data_atts );
	}

	/**
	 * Include WCML in AJAX requests
	 *
	 * @param array $ajax_actions AJAX actions.
	 * @return array
	 */
	public static function use_wcml_in_ajax_actions( $ajax_actions ) {
		$ajax_actions[] = 'get_products';

		return $ajax_actions;
	}
}
