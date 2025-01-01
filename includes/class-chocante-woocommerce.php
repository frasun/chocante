<?php
/**
 * Chocante WooCommerce
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;

// Common modules.
require_once __DIR__ . '/woocommerce/class-chocante-product-section.php';

/**
 * Chocante_WooCommerce class.
 */
class Chocante_WooCommerce {
	/**
	 * Init hooks.
	 */
	public static function init() {
		// Load page specific hooks.
		if ( ! is_admin() ) {
			add_action( 'wp', array( __CLASS__, 'load_page_hooks' ) );
		}

		// Page header.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'chocante_header_aside', array( __CLASS__, 'display_header_actions' ) );

		// Catalog settings.
		add_filter( 'woocommerce_pagination_args', array( __CLASS__, 'set_pagination_args' ) );
		add_filter( 'woocommerce_catalog_orderby', array( __CLASS__, 'set_caralog_orderby' ) );

		// Mini-cart.
		add_action( 'woocommerce_before_mini_cart', array( __CLASS__, 'display_mini_cart_title' ) );
		remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_button_view_cart', 10 );
		add_filter( 'woocommerce_widget_cart_item_quantity', array( __CLASS__, 'display_mini_cart_item_total' ), 10, 3 );

		// Cart & mini-cart.
		add_filter( 'woocommerce_cart_item_remove_link', array( __CLASS__, 'use_remove_icon' ) );
		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'get_custom_product_name' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_price', array( __CLASS__, 'add_weight_to_price_in_cart' ), 10, 2 );

		// Cart & product page.
		add_action( 'woocommerce_before_quantity_input_field', array( __CLASS__, 'display_remove_quantity_button' ) );
		add_action( 'woocommerce_after_quantity_input_field', array( __CLASS__, 'display_add_quantity_button' ), 20 );

		// Free shipping.
		if ( class_exists( 'Chocante_Free_Shipping' ) ) {
			add_action( 'chocante_delivery_info', array( __CLASS__, 'display_free_delivery_info' ) );
		}

		// Product search.
		add_action( 'pre_get_product_search_form', array( __CLASS__, 'display_product_search_title' ) );
		add_filter( 'get_product_search_form', array( __CLASS__, 'display_product_search_icon' ) );

		// Product loop.
		// @todo: Chocante - Bricks hack.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'disable_bricks_assets' ), 1000 );
		// END TODO.
		add_filter( 'woocommerce_loop_add_to_cart_link', array( __CLASS__, 'add_to_cart_button' ), 10, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'add_to_cart_text' ), 10, 2 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
		add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 50 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'add_loop_item_info_open' ), 30 );
		add_action( 'woocommerce_after_shop_loop_item_title', array( __CLASS__, 'add_loop_item_info_close' ), 20 );
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
		add_action( 'woocommerce_after_shop_loop_item', array( __CLASS__, 'add_loop_item_info_close' ), 30 );
		// @todo: Chocante - Remove after switching from Bricks.
		add_action(
			'woocommerce_before_shop_loop_item_title',
			function () {
				remove_all_filters( 'woocommerce_sale_flash', 10 );
			},
			1
		);
		// END TODO.

		// Product & archive page.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper' );
		add_action( 'woocommerce_before_main_content', array( __CLASS__, 'open_main_element' ) );
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end' );
		add_action( 'woocommerce_after_main_content', array( __CLASS__, 'close_main_element' ), 60 );

		// Breadcrumbs.
		// @todo: Chocante - remove priority after switching from Bricks.
		add_filter( 'woocommerce_breadcrumb_defaults', array( __CLASS__, 'modify_breadcrumbs' ), 20 );
		// END TODO.

		// Product gallery.
		if ( ! is_admin() ) {
			add_action( 'after_setup_theme', array( __CLASS__, 'disable_product_gallery_zoom' ), 99 );
		}
		add_filter( 'woocommerce_get_image_size_gallery_thumbnail', array( __CLASS__, 'set_gallery_thumbnail_size' ) );

		// Page footer.
		if ( ! is_admin() ) {
			add_action( 'wp_footer', array( __CLASS__, 'output_product_search' ), 30 );
		}

		// Product sliders.
		add_action( 'wp_ajax_get_products', array( Chocante_Product_Section::class, 'ajax_get_products' ) );
		add_action( 'wp_ajax_nopriv_get_products', array( Chocante_Product_Section::class, 'ajax_get_products' ) );
		add_action( 'woocommerce_after_product_object_save', array( Chocante_Product_Section::class, 'clear_cached_products' ) );
		add_filter( 'wcml_multi_currency_ajax_actions', array( Chocante_Product_Section::class, 'use_wcml_in_ajax_actions' ) );

		/**
		 * Fix PHP notice in widgets page
		 *
		 * @link https://github.com/WordPress/gutenberg/issues/33576#issuecomment-883690807
		 */
		remove_filter( 'admin_head', 'wp_check_widget_editor_deps' );

		// Remove WooCommerce styles.
		add_filter( 'woocommerce_enqueue_styles', array( __CLASS__, 'disable_woocommerce_styles' ) );
	}

	/**
	 * Load page specific hooks
	 */
	public static function load_page_hooks() {
		if ( is_shop() || is_product_category() || is_product_taxonomy() || is_product_tag() ) {
			require_once __DIR__ . '/woocommerce/class-chocante-product-archive.php';
			Chocante_Product_Archive::init();
			return;
		}

		if ( is_product() ) {
			require_once __DIR__ . '/woocommerce/class-chocante-product-page.php';
			Chocante_Product_Page::init();
			return;
		}

		if ( is_cart() ) {
			require_once __DIR__ . '/woocommerce/class-chocante-cart.php';
			Chocante_Cart::init();
			return;
		}

		if ( is_account_page() ) {
			require_once __DIR__ . '/woocommerce/class-chocante-account.php';
			Chocante_Account::init();
			return;
		}
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
		ob_start();
		Chocante::icon( 'prev' );
		$prev_icon = ob_get_clean();

		ob_start();
		Chocante::icon( 'next' );
		$next_icon = ob_get_clean();

		$pagination['prev_text'] = is_rtl() ? $next_icon : $prev_icon;
		$pagination['next_text'] = is_rtl() ? $prev_icon : $next_icon;

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
	 * Replace add to cart text when product is out of stock
	 *
	 * @param string     $text Add to cart text.
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	public static function add_to_cart_text( $text, $product ) {
		return $product->is_in_stock() ? _x( 'Buy now', 'product loop', 'chocante' ) : __( 'Read more', 'woocommerce' );
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
			wp_deregister_style( 'bricks-frontend' );
			wp_dequeue_style( 'bricks-frontend' );
			wp_dequeue_style( 'bricks-frontend-rtl' );
			wp_dequeue_style( 'bricks-default-content' );
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
	 * Scope changes to new template
	 *
	 * @todo: Chocante - Bricks.
	 */
	public static function bricks_disabled() {
		return is_cart() || is_product() || is_shop() || is_product_category() || is_product_taxonomy() || is_product_tag() || is_account_page() || is_home();
	}

	/**
	 * Open <main> element
	 */
	public static function open_main_element() {
		echo '<main role="main">';
	}

	/**
	 * Close <main> element
	 */
	public static function close_main_element() {
		echo '</main>';
	}

	/**
	 * Disable zoom in product gallery
	 */
	public static function disable_product_gallery_zoom() {
		remove_theme_support( 'wc-product-gallery-zoom' );
	}

	/**
	 * Modify breadcrumbs arguments
	 *
	 * @param array $args Breadcrumbs args.
	 * @return array
	 */
	public static function modify_breadcrumbs( $args ) {
		$args['delimiter'] = '&nbsp;&#8208;&nbsp;';
		return $args;
	}

	/**
	 * Product gallery thumbnail size
	 */
	public static function set_gallery_thumbnail_size() {
		return array(
			'width'  => 150,
			'height' => 150,
			'crop'   => 1,
		);
	}
}
