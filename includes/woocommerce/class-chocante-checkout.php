<?php
/**
 * Chocante WooCommerce checkout
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chocante_Checkout class.
 */
class Chocante_Checkout {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'woocommerce_before_checkout_form', array( __CLASS__, 'display_page_title' ), 1 );
		add_filter( 'woocommerce_checkout_cart_item_quantity', array( __CLASS__, 'modify_item_quantity' ), 10, 2 );
		add_action( 'woocommerce_review_order_before_payment', array( __CLASS__, 'display_payment_title' ) );
		add_action( 'woocommerce_checkout_after_customer_details', array( __CLASS__, 'show_back_to_cart' ) );

		// Thank you.
		remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );

		// Order pay.
		add_filter( 'woocommerce_order_item_quantity_html', array( __CLASS__, 'modify_order_item_quantity' ), 10, 2 );
	}

	/**
	 * Enqueue scripts & styles
	 */
	public static function enqueue_scripts() {
		$checkout_css = include get_theme_file_path( 'build/checkout.asset.php' );

		wp_enqueue_style(
			'chocante-checkout-css',
			get_theme_file_uri( 'build/checkout.css' ),
			$checkout_css['dependencies'],
			$checkout_css['version'],
		);
	}

	/**
	 * Display page title
	 */
	public static function display_page_title() {
		get_template_part( 'template-parts/page', 'header' );
	}

	/**
	 * Modify order item quantity in order details
	 *
	 * @param string $quantity_html Order line item quantity HTML.
	 * @param array  $cart_item Order line item.
	 * @return string
	 */
	public static function modify_item_quantity( $quantity_html, $cart_item ) {
		$product = $cart_item['data'];

		if ( $product instanceof WC_Product_Variation ) {
			$weight = $product->get_attribute( 'pa_waga' );

			return "<span class='product-variation-quantity'>{$weight}{$quantity_html}</div>";
		}

		return $quantity_html;
	}

	/**
	 * Modify order item quantity in order details
	 *
	 * @param string        $quantity_html Order line item quantity HTML.
	 * @param WC_Order_Item $item Order line item.
	 * @return string
	 */
	public static function modify_order_item_quantity( $quantity_html, $item ) {
		$attribute   = 'pa_waga';
		$weight      = '';
		$weight_slug = $item->get_meta( $attribute );

		if ( $weight_slug ) {
			$term = get_term_by( 'slug', $item->get_meta( $attribute ), $attribute );

			if ( ! is_wp_error( $term ) ) {
				$weight = $term->name;
			}

			if ( ! empty( $weight ) ) {
				return "<span class='product-variation-quantity'>{$weight}{$quantity_html}</div>";
			}
		}

		return $quantity_html;
	}

	/**
	 * Display payment section title
	 */
	public static function display_payment_title() {
		echo '<h6 class="payment__heading">' . esc_html__( 'Payment methods', 'woocommerce' ) . '</h6>';
	}

	/**
	 * Display back to cart button
	 */
	public static function show_back_to_cart() {
		echo '<a href="' . esc_url( wc_get_cart_url() ) . '" class="back-to-cart">' . esc_html_x( 'Edit previous step', 'checkout', 'chocante' ) . '</a>';
	}
}
