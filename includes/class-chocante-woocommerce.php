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
	 * Init hooks.
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
		add_action( 'woocommerce_after_cart_table', array( self::class, 'display_cart_info' ) );
		add_action( 'woocommerce_cart_totals_before_order_total', array( self::class, 'display_coupon_form_in_cart' ) );
		add_action( 'woocommerce_before_quantity_input_field', array( self::class, 'display_remove_quantity_button' ) );
		add_action( 'woocommerce_after_quantity_input_field', array( self::class, 'display_add_quantity_button' ), 20 );
		add_filter( 'woocommerce_cart_item_permalink', array( self::class, 'return_empty_permalink' ) );

		if ( class_exists( 'Chocante_Free_Shipping' ) ) {
			add_action( 'chocante_delivery_info', array( self::class, 'display_free_delivery_info' ) );
		}

		add_action( 'chocante_after_cart', array( self::class, 'display_featured_products_in_cart' ) );

		// Cart empty.
		add_action( 'woocommerce_before_cart', array( self::class, 'display_cart_title' ), 1 );
		add_filter( 'body_class', array( self::class, 'modify_empty_cart_body_class' ) );
		remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );

		// Product search.
		add_action( 'pre_get_product_search_form', array( self::class, 'display_product_search_title' ) );
		add_filter( 'get_product_search_form', array( self::class, 'display_product_search_icon' ) );

		// Product loop.
		// @todo: Chocante - Bricks hack.
		add_action( 'wp_enqueue_scripts', array( self::class, 'disable_bricks_assets' ), 1000 );
		// END TODO.

		// Product sliders.
		add_action( 'wp_ajax_get_products', array( self::class, 'ajax_get_products' ) );
		add_action( 'wp_ajax_nopriv_get_products', array( self::class, 'ajax_get_products' ) );
		add_action( 'woocommerce_after_product_object_save', array( self::class, 'clear_cached_products' ) );

		// Product page.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper' );
		add_action( 'woocommerce_before_main_content', array( self::class, 'open_main_element' ) );
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		remove_action( 'woocommerce_before_single_product', 'woocommerce_output_all_notices' );
		// @todo: Chocante - Bricks hack.
		add_action(
			'woocommerce_before_single_product_summary',
			function () {
				remove_all_filters( 'woocommerce_sale_flash', 10 );
			},
			1
		);
		// END TODO.
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
		add_action( 'woocommerce_before_single_product_summary', 'woocommerce_output_all_notices', 5 );
		add_action( 'woocommerce_before_single_product_summary', 'woocommerce_breadcrumb', 7 );
		add_action( 'woocommerce_before_single_product_summary', array( self::class, 'open_product_info_section' ), 9 );
		add_action( 'woocommerce_before_single_product_summary', array( self::class, 'open_product_header' ), 13 );
		add_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 14 );
		add_action( 'woocommerce_before_single_product_summary', 'woocommerce_template_single_title', 16 );
		add_action( 'woocommerce_before_single_product_summary', array( self::class, 'close_product_header' ), 18 );

		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
		add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 20 );
		add_action( 'woocommerce_single_product_summary', array( self::class, 'display_product_info' ), 30 );
		add_action( 'woocommerce_single_product_summary', array( self::class, 'display_product_attributes' ), 35 );

		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
		add_action( 'woocommerce_after_single_product_summary', array( self::class, 'close_product_info_section' ), 30 );

		add_action( 'woocommerce_after_single_product', array( self::class, 'display_related_products' ), 10 );
		add_action( 'woocommerce_after_single_product', array( self::class, 'output_product_description' ), 20 );

		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end' );
		add_action( 'woocommerce_after_main_content', array( self::class, 'close_main_element' ) );

		// Product variation.
		add_action( 'woocommerce_after_variations_table', 'woocommerce_single_variation', 10 );
		add_action( 'woocommerce_after_variations_table', 'woocommerce_single_variation_add_to_cart_button', 20 );
		add_filter( 'woocommerce_show_variation_price', '__return_true' );

		// Product attributes.
		add_filter( 'woocommerce_display_product_attributes', array( self::class, 'filter_product_attributes' ), 10, 2 );
		add_filter( 'woocommerce_format_weight', array( self::class, 'format_weight_dimension' ), 10, 2 );

		// Product gallery.
		add_action( 'after_setup_theme', array( self::class, 'disable_product_gallery_zoom' ), 99 );

		// Breadcrumbs.
		// @todo: Chocante - remove priority after switching from Bricks.
		add_filter( 'woocommerce_breadcrumb_defaults', array( self::class, 'modify_breadcrumbs' ), 20 );
		// END TODO.

		// Product page footer.
		add_action( 'template_redirect', array( self::class, 'display_join_group' ) );

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
			$cart = include get_stylesheet_directory() . '/build/cart.asset.php';

			wp_enqueue_script(
				'chocante-cart',
				get_stylesheet_directory_uri() . '/build/cart.js',
				array_merge( $cart['dependencies'], array( 'jquery' ) ),
				$cart['version'],
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}

		if ( is_product() ) {
			$product = include get_stylesheet_directory() . '/build/single-product.asset.php';

			wp_enqueue_script(
				'chocante-single-product',
				get_stylesheet_directory_uri() . '/build/single-product.js',
				array_merge( $product['dependencies'], array() ),
				$product['version'],
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
	public static function display_cart_info() {
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
		self::class::display_product_section(
			array(
				'heading'  => __( 'Featured products', 'chocante' ),
				'featured' => true,
			)
		);
	}

	/**
	 * Display related products
	 */
	public static function display_related_products() {
		global $product;

		$product_categories = get_the_terms( $product->get_id(), 'product_cat' );

		if ( is_wp_error( $product_categories ) ) {
			return;
		}

		$heading  = join( ', ', wp_list_pluck( $product_categories, 'name' ) );
		$cta_link = esc_url( get_permalink( wc_get_page_id( 'shop' ) ) . '?filter_product_cat=' . join( ',', wp_list_pluck( $product_categories, 'slug' ) ) );

		self::class::display_product_section(
			array(
				'heading'    => $heading,
				'subheading' => __( 'Products from category', 'chocante' ),
				'cta_link'   => $cta_link,
				'category'   => wp_list_pluck( $product_categories, 'term_id' ),
				'exclude'    => array( $product->get_id() ),
			)
		);
	}

	/**
	 * Display featured products
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
			'limit'        => $limit,
			'orderby'      => $orderby,
			'order'        => $order,
			'visibility'   => 'visibile',
			'status'       => 'publish',
			'stock_status' => 'instock',
		);

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

		// @todo: Chocante - move to global loop.
		add_filter( 'woocommerce_post_class', array( self::class, 'slider_item_class' ) );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( self::class, 'add_to_cart_button' ), 10, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( self::class, 'add_to_cart_text' ), 10, 2 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
		add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 50 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( self::class, 'add_loop_item_info_open' ), 30 );
		add_action( 'woocommerce_after_shop_loop_item_title', array( self::class, 'add_loop_item_info_close' ), 20 );
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
		add_action( 'woocommerce_after_shop_loop_item', array( self::class, 'add_loop_item_info_close' ), 30 );
		// @todo: Chocante - Remove after switching from Bricks.
		remove_all_filters( 'woocommerce_sale_flash', 10 );
		// END TODO.
		// END TODO.

		get_template_part(
			'template-parts/product',
			'slider',
			array(
				'products' => $products,
				'labels'   => self::class::get_slider_labels(),
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
		self::class::get_products( $category, $featured, $onsale, $latest, $exclude );
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
		return is_cart() || is_product();
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

	/**
	 * Return # as a link to product in cart that chnaged status to out of stock in order to remain HTML structure
	 *
	 * @param string $link Product permalink.
	 * @return string
	 */
	public static function return_empty_permalink( $link ) {
		return empty( $link ) ? '#' : $link;
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
	 * Open product info section
	 */
	public static function open_product_info_section() {
		echo '<section class="product__summary">';
	}

	/**
	 * Close product info section
	 */
	public static function close_product_info_section() {
		echo '</section>';
	}

	/**
	 * Open product page header
	 */
	public static function open_product_header() {
		echo '<header class="product__header">';
	}

	/**
	 * Close product page header
	 */
	public static function close_product_header() {
		echo '</header>';
	}

	/**
	 * Display product attributes on product page
	 */
	public static function display_product_attributes() {
		get_template_part( 'template-parts/product', 'details' );
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
	 * Display additional description on product page
	 */
	public static function output_product_description() {
		get_template_part( 'template-parts/product', 'description' );
	}

	/**
	 * Disable zoom in product gallery
	 */
	public static function disable_product_gallery_zoom() {
		remove_theme_support( 'wc-product-gallery-zoom' );
	}

	/**
	 * Display additional information on product page
	 */
	public static function display_product_info() {
		get_template_part( 'template-parts/info', 'product' );
	}

	/**
	 * Filter product attributes
	 * Show weight dimension for variable products in order to switch to chosen variation
	 * Show weight attribute for simple attribute
	 *
	 * @param array      $product_attributes Product attributes.
	 * @param WC_Product $product Product object.
	 * @return array
	 */
	public static function filter_product_attributes( $product_attributes, $product ) {
		if ( is_a( $product, 'WC_Product_Simple' ) ) {
			unset( $product_attributes['weight'] );
		} elseif ( is_a( $product, 'WC_Product_Variable' ) ) {
			if ( isset( $product_attributes['weight'] ) ) {
				$weight = $product_attributes['weight'];
				unset( $product_attributes['weight'] );
				$keys  = array_keys( $product_attributes );
				$index = array_search( 'attribute_pa_waga', $keys, true );

				if ( false !== $index ) {
					$before             = array_slice( $product_attributes, 0, $index + 1, true );
					$after              = array_slice( $product_attributes, $index + 1, null, true );
					$product_attributes = $before + array( 'weight' => $weight ) + $after;
				}
			}

			unset( $product_attributes['attribute_pa_waga'] );
		}

		return $product_attributes;
	}

	/**
	 * Format weight dimension to show grams when vale is below 1 kg
	 *
	 * @param string $weight_string Weight dimension string.
	 * @param float  $weight Weight.
	 *
	 * @return string
	 */
	public static function format_weight_dimension( $weight_string, $weight ) {
		$w = floatval( $weight );

		if ( $w > 0 && $w < 1 ) {
			return $w * 1000 . ' g';
		}

		return $weight_string;
	}

	/**
	 * Change display of join group section on product page
	 */
	public static function display_join_group() {
		if ( is_product() ) {
			remove_action( 'chocante_before_footer', array( Chocante::class, 'display_join_group' ) );
			add_action( 'woocommerce_after_main_content', array( Chocante::class, 'display_join_group' ), 5 );
		}
	}
}
