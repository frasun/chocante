<?php
/**
 * Chocante WooCommerce
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Chocante_WooCommerce class.
 */
class Chocante_WooCommerce {
	const PRODUCT_WEIGHT_TAXONOMY = 'pa_waga';

	/**
	 * Class constructor.
	 */
	public static function init() {
		// Page header.
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_scripts' ) );
		add_action( 'chocante_header_aside', array( self::class, 'display_header_actions' ) );

		if ( ! is_admin() ) {
			add_action( 'wp_footer', array( self::class, 'output_product_search' ) );
		}

		// Catalog settings.
		add_filter( 'woocommerce_pagination_args', array( self::class, 'set_pagination_args' ) );
		add_filter( 'woocommerce_catalog_orderby', array( self::class, 'set_caralog_orderby' ) );

		// Mini-cart.
		add_action( 'woocommerce_before_mini_cart', array( self::class, 'display_mini_cart_title' ) );
		remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_button_view_cart', 10 );
		add_filter( 'woocommerce_widget_cart_item_quantity', array( self::class, 'display_mini_cart_item_total' ), 10, 3 );

		// Cart & mini-cart.
		add_filter( 'woocommerce_cart_item_remove_link', array( self::class, 'use_remove_icon' ) );
		add_filter( 'woocommerce_cart_item_name', array( self::class, 'get_custom_product_name' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_price', array( self::class, 'add_weight_to_price_in_cart' ), 10, 2 );

		// Product search.
		add_action( 'pre_get_product_search_form', array( self::class, 'display_product_search_title' ) );
		add_filter( 'get_product_search_form', array( self::class, 'display_product_search_icon' ) );

		/**
		 * Fix PHP notice in widgets page
		 *
		 * @link https://github.com/WordPress/gutenberg/issues/33576#issuecomment-883690807
		 */
		remove_filter( 'admin_head', 'wp_check_widget_editor_deps' );
	}

	/**
	 * Enqueue scripts & styles
	 */
	public static function enqueue_scripts() {
		wp_enqueue_script( 'wc-cart-fragments' );
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

	/**
	 * Display account link, mini-cart & product search actions in header
	 *
	 * @param string $container_id Container CSS class.
	 */
	public static function display_header_actions( $container_id ) {
		if ( $container_id ) {
			echo '<aside class="' . esc_attr( $container_id ) . '">';
		}

		get_template_part( 'template-parts/mini-cart' );
		get_template_part( 'template-parts/customer-account-link' );
		get_template_part( 'template-parts/product-search' );

		if ( $container_id ) {
			echo '</aside>';
		}
	}

	/**
	 * Output product search form
	 */
	public static function output_product_search() {
		get_template_part( 'template-parts/product-search', 'form' );
	}


	/**
	 * Display title in mini cart
	 */
	public static function display_mini_cart_title() {
		get_template_part( 'template-parts/mini-cart', 'header' );
	}

	/**
	 * Display item total in mini cart
	 *
	 * @param string $quantity Quantity HTML.
	 * @param array  $cart_item Cart line item.
	 * @return string
	 */
	public static function display_mini_cart_item_total( $quantity, $cart_item ) {
		$include_tax = 'incl' === get_option( 'woocommerce_tax_display_shop' );
		$subtotal    = $include_tax ? $cart_item['line_subtotal_tax'] : $cart_item['line_subtotal'];

		return '<footer>' . $quantity . '<strong>' . wc_price( $subtotal ) . '</strong></footer>';
	}

	/**
	 * Use remove icon in cart
	 *
	 * @param string $remove_link Remove link HTML.
	 * @return string
	 */
	public static function use_remove_icon( $remove_link ) {
		ob_start();
		Chocante::icon( 'close' );

		return str_replace( '&times;</a>', ob_get_clean() . '</a>', $remove_link );
	}

	/**
	 * Add title to product search
	 */
	public static function display_product_search_title() {
		echo '<h4 class="search-products__title">' . esc_html__( 'Search products', 'woocommerce' ) . '</h4>';
	}

	/**
	 * Add title to product search
	 *
	 * @param string $form Search form HTML.
	 * @return string
	 */
	public static function display_product_search_icon( $form ) {
		ob_start();
		echo '<div class="search-products__icon">';
		Chocante::icon( 'search' );
		echo '</div>';

		return str_replace( '</form>', ob_get_clean() . '</form>', $form );
	}

	/**
	 * Display weight suffix for product variations next to price in cart & mini-cart.
	 *
	 * @param string $price Search form HTML.
	 * @param arrat  $cart_item Product item in cart.
	 * @return string
	 */
	public static function add_weight_to_price_in_cart( $price, $cart_item ) {
		$product = $cart_item['data'];

		if ( $product instanceof WC_Product_Variation ) {
			$weight = $cart_item['data']->get_attribute( 'pa_waga' );

			return "{$price} <span class='woocommerce-price-suffix'>/ {$weight}</span>";
		}

		return $price;
	}

	/**
	 * Display product name without variation data in cart
	 *
	 * @param string $product_name Product title.
	 * @param array  $cart_item Product in the cart.
	 * @return string
	 */
	public static function get_custom_product_name( $product_name, $cart_item ) {
		$product = $cart_item['data'];

		if ( $product instanceof WC_Product_Variation ) {
			$parent_id = $product->get_parent_id();
			$parent    = wc_get_product( $parent_id );

			return $parent->get_name();
		}

		return $product_name;
	}
}
