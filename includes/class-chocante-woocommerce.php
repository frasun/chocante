<?php
/**
 * Chocante WooCommerce common
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

// Common modules.
require_once __DIR__ . '/woocommerce/class-chocante-product-archive.php';
require_once __DIR__ . '/woocommerce/class-chocante-product-page.php';
require_once __DIR__ . '/woocommerce/class-chocante-cart.php';
require_once __DIR__ . '/woocommerce/class-chocante-checkout.php';
require_once __DIR__ . '/woocommerce/class-chocante-account.php';
require_once __DIR__ . '/woocommerce/class-chocante-product-section.php';
require_once __DIR__ . '/woocommerce/class-chocante-product-tags.php';
require_once __DIR__ . '/woocommerce/class-globkurier-shipping.php';

/**
 * Chocante_WooCommerce class.
 */
class Chocante_WooCommerce {
	/**
	 * Bank account order needed to display account currency
	 *
	 * @var int $backs_order Order of queried bank account.
	 */
	private static $bacs_order = 0;

	/**
	 * Init hooks.
	 */
	public static function init() {
		// Setup.
		add_action( 'after_setup_theme', array( __CLASS__, 'support_woocommerce' ) );

		// Load page specific hooks.
		if ( ! is_admin() ) {
			add_action( 'wp', array( __CLASS__, 'load_page_hooks' ) );
		}

		// Page header.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'chocante_header_aside', array( __CLASS__, 'display_header_actions' ) );

		// Catalog settings.
		add_filter( 'woocommerce_pagination_args', array( __CLASS__, 'set_pagination_args' ) );

		// Mini-cart.
		add_action( 'woocommerce_before_mini_cart', array( __CLASS__, 'display_mini_cart_title' ) );
		remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_button_view_cart', 10 );
		add_filter( 'woocommerce_widget_cart_item_quantity', array( __CLASS__, 'display_mini_cart_item_total' ), 10, 3 );
		add_filter( 'woocommerce_add_to_cart_fragments', array( __CLASS__, 'update_mini_cart_count' ) );

		// Cart & mini-cart.
		add_filter( 'woocommerce_cart_item_remove_link', array( __CLASS__, 'use_remove_icon' ) );
		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'get_custom_product_name' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_price', array( __CLASS__, 'add_weight_to_price_in_cart' ), 10, 2 );

		// Cart & product page.
		add_action( 'woocommerce_before_quantity_input_field', array( __CLASS__, 'display_remove_quantity_button' ) );
		add_action( 'woocommerce_after_quantity_input_field', array( __CLASS__, 'display_add_quantity_button' ), 20 );
		add_filter( 'woocommerce_quantity_input_type', array( __CLASS__, 'set_quantity_input_type' ) );

		// Checkout.
		add_action( 'wp_ajax_validate_postcode', array( Chocante_Checkout::class, 'validate_postcode' ) );
		add_action( 'wp_ajax_nopriv_validate_postcode', array( Chocante_Checkout::class, 'validate_postcode' ) );

		// Free shipping.
		if ( class_exists( 'Chocante_Free_Shipping' ) ) {
			add_action( 'chocante_delivery_info', array( __CLASS__, 'display_free_delivery_info' ) );
		}

		// Product search.
		add_action( 'pre_get_product_search_form', array( __CLASS__, 'display_product_search_title' ) );
		add_filter( 'get_product_search_form', array( __CLASS__, 'display_product_search_icon' ) );

		// Product loop.
		add_filter( 'woocommerce_loop_add_to_cart_link', array( __CLASS__, 'add_to_cart_button' ), 10, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'add_to_cart_text' ), 10, 2 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
		add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 50 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'add_loop_item_info_open' ), 30 );
		add_action( 'woocommerce_after_shop_loop_item_title', array( __CLASS__, 'add_loop_item_info_close' ), 20 );
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
		add_action( 'woocommerce_after_shop_loop_item', array( __CLASS__, 'add_loop_item_info_close' ), 30 );

		// Product & archive page.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper' );
		add_action( 'woocommerce_before_main_content', array( __CLASS__, 'open_main_element' ) );
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end' );
		add_action( 'woocommerce_after_main_content', array( __CLASS__, 'close_main_element' ), 60 );

		// Breadcrumbs.
		add_filter( 'woocommerce_breadcrumb_defaults', array( __CLASS__, 'modify_breadcrumbs' ) );

		// Product gallery.
		if ( ! is_admin() ) {
			add_action( 'after_setup_theme', array( __CLASS__, 'setup_product_gallery' ), 99 );
		}
		add_filter( 'woocommerce_get_image_size_gallery_thumbnail', array( __CLASS__, 'disable_gallery_thumbnail_size' ) );

		// Page footer.
		if ( ! is_admin() ) {
			add_action( 'wp_footer', array( __CLASS__, 'output_product_search' ), 30 );
		}

		// Product sliders.
		add_action( 'wp_ajax_get_product_section', array( Chocante_Product_Section::class, 'ajax_get_product_section' ) );
		add_action( 'wp_ajax_nopriv_get_product_section', array( Chocante_Product_Section::class, 'ajax_get_product_section' ) );
		add_action( 'woocommerce_product_object_updated_props', array( Chocante_Product_Section::class, 'clear_cached_products_on_props_change' ), 10, 2 );
		add_action( 'before_delete_post', array( Chocante_Product_Section::class, 'clear_cached_products_on_delete' ) );
		add_action( 'wc_after_products_starting_sales', array( Chocante_Product_Section::class, 'clear_cached_products' ) );
		add_action( 'wc_after_products_ending_sales', array( Chocante_Product_Section::class, 'clear_cached_products' ) );

		// WCML.
		add_filter( 'wcml_multi_currency_ajax_actions', array( Chocante_Product_Section::class, 'use_wcml_in_ajax_actions' ) );

		/**
		 * Fix PHP notice in widgets page
		 *
		 * @link https://github.com/WordPress/gutenberg/issues/33576#issuecomment-883690807
		 */
		remove_filter( 'admin_head', 'wp_check_widget_editor_deps' );

		// Thankyou / order emails - add currency from WCML.
		if ( class_exists( 'WCML_Multi_Currency' ) ) {
			add_filter( 'woocommerce_bacs_account_fields', array( __CLASS__, 'add_currency_to_bank_details' ) );
		}

		// Shortcodes.
		add_action( 'init', array( __CLASS__, 'add_shortcodes' ) );

		// Gift wrapper.
		if ( class_exists( 'Tgpc_Wc_Gift_Wrap' ) ) {
			add_filter( 'tgpc_wc_gift_wrapper_icon_html', array( __CLASS__, 'disable_gift_wrapper_icon_in_admin' ) );
			add_filter( 'tgpc_wc_gift_wrapper_checkout_label', array( __CLASS__, 'display_gift_wrapper_label' ), 10, 3 );
			add_filter( 'tgpc_wc_gift_wrapper_cost', array( __CLASS__, 'convert_gift_wrapper_fee' ) );
		}

		// Product Tags.
		Chocante_Product_Tags::init();
		add_action( 'woocommerce_before_single_product_summary', array( Chocante_Product_Tags::class, 'display_diet_icons_product_page' ), 25 );
		add_filter( 'chocante_featured_products_diet_icons', array( Chocante_Product_Tags::class, 'get_product_tags' ), 10, 2 );

		// Globkurier.
		add_filter( 'woocommerce_shipping_methods', array( __CLASS__, 'add_globkurier_shipping_method' ) );

		// Performance.
		add_filter( 'woocommerce_enqueue_styles', '__return_false' );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'manage_woo_scripts' ), 1000 );
	}

	/**
	 * Add WooCommerce support
	 */
	public static function support_woocommerce() {
		add_theme_support( 'woocommerce' );
	}

	/**
	 * Load page specific hooks
	 */
	public static function load_page_hooks() {
		if ( is_shop() || is_product_category() || is_product_taxonomy() || is_product_tag() ) {
			Chocante_Product_Archive::init();
			return;
		}

		if ( is_product() ) {
			Chocante_Product_Page::init();
			return;
		}

		if ( is_cart() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			Chocante_Cart::init();
			return;
		}

		if ( is_checkout() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			Chocante_Checkout::init();
			return;
		}

		if ( is_account_page() ) {
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
		echo '<span class="screen-reader-text">';
		esc_html_e( 'Previous page', 'woocommerce' );
		echo '</span>';
		Chocante::icon( 'prev' );
		$prev_icon = ob_get_clean();

		ob_start();
		echo '<span class="screen-reader-text">';
		esc_html_e( 'Next page', 'woocommerce' );
		echo '</span>';
		Chocante::icon( 'next' );
		$next_icon = ob_get_clean();

		$pagination['prev_text'] = is_rtl() ? $next_icon : $prev_icon;
		$pagination['next_text'] = is_rtl() ? $prev_icon : $next_icon;

		return $pagination;
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
		return '<footer>' . $quantity . '<strong>' . wc_price( wc_get_price_to_display( $cart_item['data'], array( 'qty' => $cart_item['quantity'] ) ) ) . '</strong></footer>';
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
			$weight = $product->get_attribute( 'pa_waga' );

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
		get_template_part( 'template-parts/quantity', 'plus' );
	}

	/**
	 * Display quantity buttons in cart
	 */
	public static function display_remove_quantity_button() {
		get_template_part( 'template-parts/quantity', 'minus' );
	}

	/**
	 * Always set quantity input to type="number"
	 */
	public static function set_quantity_input_type() {
		return 'number';
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
	 * Setup product gallery
	 */
	public static function setup_product_gallery() {
		remove_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-slider' );
		add_theme_support( 'wc-product-gallery-lightbox' );
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
	 * Add currency info to bank account details displayed in thankyou page and emails
	 *
	 * @param array $account_details Account details.
	 * @return array
	 */
	public static function add_currency_to_bank_details( $account_details ) {
		$account_currencies = get_option( 'wcml_bacs_accounts_currencies' );

		if ( false === $account_currencies ) {
			return $account_details;
		}

		$account_details['currency'] = array(
			'label' => __( 'Currency', 'woocommerce' ),
			'value' => $account_currencies[ self::$bacs_order ],
		);

		++self::$bacs_order;

		return $account_details;
	}

	/**
	 * Add shortcodes for template parts
	 */
	public static function add_shortcodes() {
		// [chocante_product_section] shortcode.
		add_shortcode(
			'chocante_product_section',
			function ( $atts, $content ) {
				ob_start();
				Chocante_Product_Section::display_product_section( $atts, $content );
				return ob_get_clean();
			}
		);

		// [chocante_featured_products] shortcode.
		add_shortcode(
			'chocante_featured_products',
			function () {
				ob_start();
				get_template_part( 'template-parts/slider', 'featured-products' );
				return ob_get_clean();
			}
		);
	}

	/**
	 * Add cart count to wc-fragments
	 *
	 * @param array $fragments WC fragments.
	 * @return array
	 */
	public static function update_mini_cart_count( $fragments ) {
		if ( ! is_object( WC()->cart ) ) {
			return;
		}

		$fragments['cart-count'] = WC()->cart->get_cart_contents_count();

		return $fragments;
	}

	/**
	 * Disable gift wrapping icon in admin
	 *
	 * @return string
	 */
	public static function disable_gift_wrapper_icon_in_admin() {
		return '';
	}

	/**
	 * Modify gift wrapper checkbox label
	 *
	 * @param string $label The input label as html.
	 * @param string $label_icon The html of the icon.
	 * @param string $label_text The escaped text of the label.
	 * @return string
	 */
	public static function display_gift_wrapper_label( $label, $label_icon, $label_text ) {
		return $label_text;
	}

	/**
	 * Convert gift wrapper fee to selected currency
	 *
	 * @param float $fee Gift wrapper fee in base currency.
	 * @return float
	 */
	public static function convert_gift_wrapper_fee( $fee ) {
		if ( has_filter( 'wcml_raw_price_amount' ) ) {
			return apply_filters( 'wcml_raw_price_amount', $fee );
		}

		return $fee;
	}

	/**
	 * Add Globkurier to shipping methods
	 *
	 * @param array $shipping_methods Shipping methods.
	 * @return array
	 */
	public static function add_globkurier_shipping_method( $shipping_methods ) {
		$shipping_methods['globkurier'] = 'Globkurier_Shipping';
		return $shipping_methods;
	}

	/**
	 * Disable woocommerce_gallery_thumbnail image size.
	 */
	public static function disable_gallery_thumbnail_size() {
		return array(
			'width'  => 150,
			'height' => 150,
			'crop'   => 1,
		);
	}

	/**
	 * Load Woo non-critical scripts in footer
	 */
	public static function manage_woo_scripts() {
		global $wp_scripts;

		$footer_scripts = array(
			'js-cookie',
			'woocommerce',
			'wc-single-product',
		);

		if ( is_product() ) {
			$footer_scripts[] = 'flexslider';
			$footer_scripts[] = 'photoswipe';
			$footer_scripts[] = 'photoswipe-ui-default';
		}

		foreach ( $footer_scripts as $handle ) {
			if ( isset( $wp_scripts->registered[ $handle ] ) ) {
				$script = $wp_scripts->registered[ $handle ];

				wp_dequeue_script( $handle );
				wp_enqueue_script(
					$handle,
					$script->src,
					$script->deps,
					$script->ver,
					array(
						'in_footer' => true,
						'strategy'  => 'defer',
					)
				);
			}
		}

		global $wp_styles;

		$async_styles = array(
			'photoswipe',
			'photoswipe-default-skin',
		);

		foreach ( $async_styles as $handle ) {
			if ( isset( $wp_styles->registered[ $handle ] ) ) {
				$style = $wp_styles->registered[ $handle ];

				wp_deregister_style( $handle );

				if ( is_product() ) {
					wp_enqueue_style(
						$handle,
						$style->src,
						$style->deps,
						$style->ver,
						'print'
					);

					add_filter(
						'style_loader_tag',
						function ( $html, $h ) use ( $handle ) {
							if ( $h === $handle ) {
								$html = str_replace( "media='print'", "media='print' onload=\"this.media='all'\"", $html );
							}
							return $html;
						},
						10,
						2
					);
				}
			}
		}
	}
}
