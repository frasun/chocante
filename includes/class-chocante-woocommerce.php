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
			add_action( 'wp_footer', array( self::class, 'output_product_search' ), 30 );
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

		// Cart.
		add_action( 'woocommerce_after_cart_table', array( self::class, 'display_delivery_info' ) );
		add_action( 'woocommerce_cart_totals_before_order_total', array( self::class, 'display_coupon_form_in_cart' ) );
		add_action( 'woocommerce_before_quantity_input_field', array( self::class, 'display_remove_quantity_button' ) );
		add_action( 'woocommerce_after_quantity_input_field', array( self::class, 'display_add_quantity_button' ), 20 );

		if ( class_exists( 'Chocante_Free_Shipping' ) ) {
			add_action( 'chocante_delivery_info', array( self::class, 'display_free_delivery_info' ) );
		}

		add_action( 'chocante_after_main', array( self::class, 'display_featured_products_in_cart' ) );

		// Cart empty.
		add_filter( 'woocommerce_before_cart', array( self::class, 'display_cart_title' ), 1 );
		add_filter( 'body_class', array( self::class, 'modify_empty_cart_body_class' ) );
		remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );

		// Product search.
		add_action( 'pre_get_product_search_form', array( self::class, 'display_product_search_title' ) );
		add_filter( 'get_product_search_form', array( self::class, 'display_product_search_icon' ) );

		// Product loop.
		add_action( 'wp_enqueue_scripts', array( self::class, 'disable_bricks_assets' ), 1000 );
		add_filter( 'wp_get_attachment_image_attributes', array( self::class, 'disable_bricks_set_image_attributes' ) );
		// END TODO.

		// Product sliders.
		add_action( 'wp_ajax_get_products', array( self::class, 'ajax_get_products' ) );
		add_action( 'wp_ajax_nopriv_get_products', array( self::class, 'ajax_get_products' ) );
		add_action( 'woocommerce_after_product_object_save', array( self::class, 'clear_cached_products' ) );

		add_action(
			'init',
			function () {
				remove_all_filters( 'woocommerce_cart_item_permalink', 10 );
			}
		);

		/**
		 * Fix PHP notice in widgets page
		 *
		 * @link https://github.com/WordPress/gutenberg/issues/33576#issuecomment-883690807
		 */
		remove_filter( 'admin_head', 'wp_check_widget_editor_deps' );

		// Remove WooCommerce styles.
		add_filter( 'woocommerce_enqueue_styles', array( self::class, 'disable_woocommerce_styles' ) );

		// WCML Ajax requests.
		add_filter( 'wcml_multi_currency_ajax_actions', array( self::class, 'use_wcml_in_ajax_actions' ) );
	}

	/**
	 * Enqueue scripts & styles
	 */
	public static function enqueue_scripts() {
		wp_enqueue_script( 'wc-cart-fragments' );

		if ( is_cart() ) {
			$scripts = include get_stylesheet_directory() . '/build/cart.asset.php';

			wp_enqueue_script(
				'chocante-cart',
				get_stylesheet_directory_uri() . '/build/cart.js',
				array_merge( $scripts['dependencies'], array( 'jquery' ) ),
				$scripts['version'],
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);

			wp_localize_script(
				'chocante-cart',
				'chocante',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'chocante' ),
					'lang'    => apply_filters( 'wpml_current_language', null ),
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
		Chocante::icon( 'remove' );

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

			return "<div>{$price} <span class='woocommerce-price-suffix'>/ {$weight}</span></div>";
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

			return sprintf( '<a href="%s">%s</a>', esc_url( $product->get_permalink() ), $parent->get_name() );
		}

		return $product_name;
	}

	/**
	 * Display additional information about delivery
	 */
	public static function display_delivery_info() {
		get_template_part( 'template-parts/info', 'cart' );
	}

	/**
	 * Display quantity buttons in cart
	 */
	public static function display_add_quantity_button() {
		if ( self::bricks_disabled() ) {
			get_template_part( 'template-parts/quantity', 'plus' );
		}
	}

	/**
	 * Display quantity buttons in cart
	 */
	public static function display_remove_quantity_button() {
		if ( self::bricks_disabled() ) {
			get_template_part( 'template-parts/quantity', 'minus' );
		}
	}

	/**
	 * Display free delivery infor
	 */
	public static function display_free_delivery_info() {
		get_template_part(
			'template-parts/info',
			'section',
			array(
				'icon'    => 'shipping',
				'content' => Chocante_Free_Shipping::instance()->display_free_shipping_info( true ),
			)
		);
	}

	/**
	 * Display cart title
	 */
	public static function display_cart_title() {
		if ( ! WC()->cart->is_empty() ) {
			echo wp_kses_post( '<h1 class="page-title">' . get_the_title() . '</h1>' );
		}
	}

	/**
	 * Add class to body when cart is empty
	 *
	 * @param string[] $body_class Body CSS classes.
	 * @return string[]
	 */
	public static function modify_empty_cart_body_class( $body_class ) {
		if ( is_cart() && WC()->cart->is_empty() ) {
			$body_class[] = 'cart-empty';
		}

		return $body_class;
	}

	/**
	 * Display featured products in cart
	 */
	public static function display_featured_products_in_cart() {
		if ( is_cart() ) {
			add_filter( 'chocante_product_section_heading', array( self::class, 'featured_products_section_title' ) );

			get_template_part(
				'template-parts/product',
				'section',
				array(
					'type' => 'featured',
				)
			);
		}
	}

	/**
	 * Modify product section title for featured
	 *
	 * @return string
	 */
	public static function featured_products_section_title() {
		return __( 'Featured products', 'chocante' );
	}

	/**
	 * Get featured products
	 */
	public static function get_featured_products() {
		$limit    = 12;
		$orderby  = 'rand';
		$order    = 'desc';
		$products = wp_cache_get( 'chocante_featured_products', 'chocante', false, $products_found );

		if ( false === $products_found ) {
			// Handle the legacy filter which controlled posts per page etc.
			$args = array(
				'posts_per_page' => $limit,
				'orderby'        => $orderby,
				'order'          => $order,
				'featured'       => true,
			);

			$products = wc_get_products( $args );
		}

		wp_cache_set( 'chocante_featured_products', $products, 'chocante' );

		wc_set_loop_prop( 'name', 'featured' );

		// Get visible upsells then sort them at random, then limit result set.
		$featured = wc_products_array_orderby( array_filter( $products, 'wc_products_array_filter_visible' ), $orderby, $order );
		$featured = $limit > 0 ? array_slice( $featured, 0, $limit ) : $featured;

		add_filter( 'woocommerce_post_class', array( self::class, 'slider_item_class' ) );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( self::class, 'add_to_cart_button' ), 10, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( self::class, 'add_to_cart_text' ), 10, 2 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
		add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 50 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( self::class, 'add_loop_item_info_open' ), 30 );
		add_action( 'woocommerce_after_shop_loop_item_title', array( self::class, 'add_loop_item_info_close' ), 20 );
		add_action( 'woocommerce_after_shop_loop_item', array( self::class, 'add_loop_item_info_close' ), 30 );
		// @todo: Chocante - Remove after switching from Bricks.
		remove_all_filters( 'woocommerce_sale_flash', 10 );
		// END TODO.

		$aria = array(
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

		get_template_part(
			'template-parts/product',
			'slider',
			array(
				'products' => $featured,
				'aria'     => $aria,
			)
		);
	}

	/**
	 * Return featured products HTML using AJAX
	 */
	public static function ajax_get_products() {
		if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'chocante' ) ) {
			wp_die();
		}

		$type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';

		if ( has_action( 'wpml_switch_language' ) && isset( $_GET['lang'] ) ) {
			do_action( 'wpml_switch_language', sanitize_text_field( wp_unslash( $_GET['lang'] ) ) );
		}

		if ( 'featured' === $type ) {
			ob_start();
			self::class::get_featured_products();
			echo ob_get_clean(); // @codingStandardsIgnoreLine.		
		}

		wp_die();
	}

	/**
	 * Clear cached products used in sliders
	 */
	public static function clear_cached_products() {
		wp_cache_delete( 'chocante_featured_products' );
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
	 * Output product loop item content wrapper open
	 */
	public static function add_loop_item_info_open() {
		echo '<div class="woocommerce-loop-product__info-wrapper">';
		echo '<div class="woocommerce-loop-product__info">';
	}

	/**
	 * Output product loop item content wrapper close
	 */
	public static function add_loop_item_info_close() {
		echo '</div>';
	}

	/**
	 * Replace add to cart link with button
	 *
	 * @param string     $link Add to cart url.
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	public static function add_to_cart_button( $link, $product ) {
		return '<button class="button">' . esc_html( $product->add_to_cart_text() ) . '</button>';
	}

	/**
	 * Replace add to cart link with button
	 *
	 * @param string     $text Add to cart text.
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	public static function add_to_cart_text( $text, $product ) {
		$is_purchasable = $product instanceof WC_Product_Simple ? $product->is_purchasable() && $product->is_in_stock() : $product->is_purchasable();

		return $is_purchasable ? __( 'Buy now', 'chocante' ) : __( 'Read more', 'woocommerce' );
	}

	/**
	 * Dequeue Bricks styles.
	 */
	public static function disable_bricks_assets() {
		if ( self::bricks_disabled() ) {
			wp_dequeue_script( 'bricks-woocommerce' );
			wp_dequeue_style( 'bricks-woocommerce' );
			wp_dequeue_style( 'bricks-woocommerce-rtl' );
			wp_dequeue_script( 'bricks-scripts' );
			wp_dequeue_script( 'bricks-filters' );
			// wp_deregister_style( 'bricks-frontend' );
			// wp_dequeue_style( 'bricks-frontend' );
			// wp_dequeue_style( 'bricks-frontend-rtl' );
			// wp_dequeue_style( 'bricks-default-content' );
		}
	}

	/**
	 * Disable default WooCommerce styles.
	 */
	public static function disable_woocommerce_styles() {
		if ( self::bricks_disabled() ) {
			return false;
		}
	}

	/**
	 * Disable Bricks lazy loading.
	 *
	 * @param array $atts Image attributes.
	 * @return array
	 */
	public static function disable_bricks_set_image_attributes( $atts ) {
		if ( self::bricks_disabled() ) {
			$atts['_brx_disable_lazy_loading'] = true;
		}

		return $atts;
	}

	/**
	 * Scope changes to new template
	 *
	 * @todo: Chocante - Bricks.
	 */
	public static function bricks_disabled() {
		return is_cart();
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

	/**
	 * Display coupon form in cart
	 */
	public static function display_coupon_form_in_cart() {
		get_template_part( 'template-parts/cart', 'coupon' );
	}
}
