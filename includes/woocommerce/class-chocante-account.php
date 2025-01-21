<?php
/**
 * Chocante WooCommerce account page
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chocante_Account class.
 */
class Chocante_Account {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

		// Page header.
		add_action( 'woocommerce_account_navigation', array( __CLASS__, 'display_page_header' ), 5 );

		// Account menu.
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'hide_account_pages' ) );

		// Account content.
		add_action( 'woocommerce_account_content', array( __CLASS__, 'display_mobile_header' ), 4 );

		// Dashboard.
		remove_action( 'woocommerce_account_content', 'woocommerce_account_content' );
		add_action( 'woocommerce_account_content', array( __CLASS__, 'display_dashboard' ) );

		// Orders.
		add_filter( 'woocommerce_account_orders_columns', array( __CLASS__, 'manage_orders_table_cols' ) );
		add_action( 'woocommerce_my_account_my_orders_column_order-status-number', array( __CLASS__, 'display_order_status_number' ) );
		add_action( 'woocommerce_my_account_my_orders_column_order-total-value', array( __CLASS__, 'display_order_total' ) );
		add_filter( 'woocommerce_order_item_quantity_html', array( __CLASS__, 'modify_order_item_quantity' ), 10, 2 );
		add_filter( 'woocommerce_display_item_meta', '__return_false' );

		// Login.
		add_action( 'woocommerce_before_customer_login_form', array( __CLASS__, 'display_login_page_title' ), 5 );
	}

	/**
	 * Enqueue scripts & styles
	 */
	public static function enqueue_scripts() {
		$account_js = include get_theme_file_path() . '/build/account-scripts.asset.php';

		wp_enqueue_script(
			'chocante-account-js',
			get_theme_file_uri() . '/build/account-scripts.js',
			array_merge( $account_js['dependencies'], array() ),
			$account_js['version'],
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		$account_css = include get_theme_file_path() . '/build/account.asset.php';

		wp_enqueue_style(
			'chocante-account-css',
			get_theme_file_uri() . '/build/account.css',
			$account_css['dependencies'],
			$account_css['version'],
		);
	}

	/**
	 * Hide unused account pages
	 *
	 * @param array $items Account menu items.
	 * @return array
	 */
	public static function hide_account_pages( $items ) {
		unset( $items['downloads'] );
		unset( $items['payment-methods'] );

		return $items;
	}

	/**
	 * Get menu item icon
	 *
	 * @param string $endpoint Menu item endpoint.
	 */
	public static function get_menu_icon( $endpoint ) {
		switch ( $endpoint ) {
			case 'edit-address':
				$icon = 'address';
				break;
			case 'edit-account':
				$icon = 'settings';
				break;
			case 'customer-logout':
				$icon = 'logout';
				break;
			default:
				$icon = $endpoint;
		}

		ob_start();
		Chocante::icon( $icon );
		echo ob_get_clean(); // @codingStandardsIgnoreLine.
	}

	/**
	 * Display mobile header
	 */
	public static function display_mobile_header() {
		global $wp;

		$page_titles = array(
			'dashboard'       => __( 'Dashboard', 'woocommerce' ),
			'orders'          => __( 'Orders', 'woocommerce' ),
			'view-order'      => __( 'Orders', 'woocommerce' ),
			'downloads'       => __( 'Downloads', 'woocommerce' ),
			'edit-address'    => _n( 'Address', 'Addresses', ( 1 + (int) wc_shipping_enabled() ), 'woocommerce' ),
			'payment-methods' => __( 'Payment methods', 'woocommerce' ),
			'edit-account'    => __( 'Account details', 'woocommerce' ),
			'customer-logout' => __( 'Log out', 'woocommerce' ),
		);

		$current_page_title = $page_titles['dashboard'];

		if ( ! empty( $wp->query_vars ) ) {
			foreach ( $wp->query_vars as $key => $value ) {
				if ( array_key_exists( $key, $page_titles ) ) {
					$current_page_title = $page_titles[ $key ];
					break;
				}
			}
		}

		get_template_part(
			'template-parts/account',
			'mobile-header',
			array(
				'page_title' => $current_page_title,
			)
		);
	}

	/**
	 * Display page header
	 */
	public static function display_page_header() {
		get_template_part( 'template-parts/page', 'header' );
	}

	/**
	 * Modify orders table columns
	 *
	 * @return array
	 */
	public static function manage_orders_table_cols() {
		return array(
			'order-status-number' => __( 'Status', 'woocommerce' ) . ' / ' . __( 'Order', 'woocommerce' ),
			'order-date'          => __( 'Date', 'woocommerce' ),
			'order-total-value'   => __( 'Total', 'woocommerce' ),
			'order-actions'       => __( 'Actions', 'woocommerce' ),
		);
	}

	/**
	 * Display order status and number
	 *
	 * @param WC_Order|WC_Order_Refund $order Order.
	 */
	public static function display_order_status_number( $order ) {
		echo '<span class="order-status order-status--' . esc_attr( $order->get_status() ) . '">' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</span>';
		echo '<span class="order-number">' . esc_html( _x( '#', 'hash before order number', 'woocommerce' ) . $order->get_order_number() ) . '</span>';
	}

	/**
	 * Display order total
	 *
	 * @param WC_Order|WC_Order_Refund $order Order.
	 */
	public static function display_order_total( $order ) {
		echo wp_kses_post( $order->get_formatted_order_total() );
	}

	/**
	 * Display account dashboard
	 */
	public static function display_dashboard() {
		global $wp;

		if ( ! empty( $wp->query_vars ) ) {
			foreach ( $wp->query_vars as $key => $value ) {
				// Ignore pagename param.
				if ( 'pagename' === $key ) {
					continue;
				}

				if ( has_action( 'woocommerce_account_' . $key . '_endpoint' ) ) {
					do_action( 'woocommerce_account_' . $key . '_endpoint', $value );
					return;
				}
			}
		}

		$last_order = wc_get_orders(
			array(
				'customer' => get_current_user_id(),
				'limit'    => 1,
			)
		);

		$shipping_address = wc_get_account_formatted_address( 'shipping' );

		get_template_part(
			'template-parts/account',
			'dashboard',
			array(
				'order'   => $last_order,
				'address' => $shipping_address,
			)
		);
	}

	/**
	 * Display page title on login page
	 */
	public static function display_login_page_title() {
		echo '<h1 class="page-title">' . esc_html_x( 'Already have an account?', 'login page', 'chocante' ) . '</h1>';
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
}
