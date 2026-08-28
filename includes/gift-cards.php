<?php
/**
 * Coupon settings
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\GiftCards;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Utilities\NumberUtil;
use Automattic\WooCommerce\Enums\OrderStatus;

const GIFT_CARD_META          = '_gift_card';
const GIFT_CARD_COUPON        = 'gift_card';
const GIFT_CARD_COUPON_LENGTH = 10;
const GIFT_CARD_ACTION_HOOK   = 'chocante_send_gift_card_email';

add_action( 'woocommerce_product_options_general_product_data', __NAMESPACE__ . '\display_gift_card_option' );
add_action( 'woocommerce_process_product_meta', __NAMESPACE__ . '\set_gift_card_option' );
add_filter( 'woocommerce_coupon_discount_types', __NAMESPACE__ . '\add_gift_card_coupon_type' );
add_filter( 'woocommerce_cart_coupon_types', __NAMESPACE__ . '\add_gift_card_coupon_to_cart' );
add_filter( 'woocommerce_coupon_get_discount_amount', __NAMESPACE__ . '\add_gift_card_coupon_discount', 10, 5 );
add_filter( 'woocommerce_order_item_display_meta_key', __NAMESPACE__ . '\set_display_name_for_gift_card_meta' );
add_action( 'woocommerce_order_item_meta_end', __NAMESPACE__ . '\display_gift_card_in_customer_order', 10, 2 );
add_filter( 'woocommerce_email_classes', __NAMESPACE__ . '\load_gift_card_email' );
add_action( GIFT_CARD_ACTION_HOOK, __NAMESPACE__ . '\send_gift_card_email' );
add_action( 'woocommerce_order_status_completed', __NAMESPACE__ . '\generate_gift_card_for_order', 10, 2 );
add_action( 'woocommerce_order_status_processing', __NAMESPACE__ . '\generate_gift_card_for_order', 10, 2 );
add_action( 'woocommerce_order_status_changed', __NAMESPACE__ . '\handle_order_cancellation', 10, 3 );
add_action( 'woocommerce_trash_order', __NAMESPACE__ . '\remove_gift_cards' );
add_action( 'woocommerce_before_delete_order', __NAMESPACE__ . '\remove_gift_cards' );

/**
 * Display option for generating a coupon (gift card) in product settings
 */
function display_gift_card_option() {
	global $product_object;
	?>
	<div class="options_group">
		<?php
		woocommerce_wp_checkbox(
			array(
				'id'          => GIFT_CARD_META,
				'value'       => $product_object->get_meta( GIFT_CARD_META ),
				'label'       => __( 'Generate gift card', 'chocante' ),
				'desc_tip'    => true,
				'description' => __( 'Controls generating a gift card copupon based on the product price.', 'chocante' ),
			)
		);
		?>
	</div>
	<?php
}

/**
 * Save gift card option
 *
 * @param int $post_id Product ID.
 *
 * @phpcs:disable WordPress.Security.NonceVerification.Missing
 */
function set_gift_card_option( $post_id ) {
	if ( isset( $_POST[ GIFT_CARD_META ] ) ) {
		$product = wc_get_product( intval( $post_id ) );
		$product->update_meta_data( GIFT_CARD_META, sanitize_text_field( wp_unslash( $_POST[ GIFT_CARD_META ] ) ) );
		$product->save_meta_data();
	}
}
// @phpcs: enable

/**
 * Generate gift card coupon
 *
 * @param int       $order_id Order ID.
 * @param \WC_Order $order Order object.
 */
function generate_gift_card_for_order( $order_id, $order ) {
	$order_items = $order->get_items();
	$send_email  = false;

	foreach ( $order_items as $item ) {
		if ( ! $item instanceof \WC_Order_Item_Product ) {
			continue;
		}

		$product        = $item->get_product();
		$parent_product = $item->get_variation_id() ? wc_get_product( $product->get_parent_id() ) : $product;

		if ( 'yes' === $parent_product->get_meta( GIFT_CARD_META ) ) {
			if ( $item->get_meta( GIFT_CARD_COUPON ) ) {
				continue;
			}

			$send_email = true;
			$i          = 0;

			while ( $i < $item->get_quantity() ) {
				$value = $product->get_price();
				$code  = generate_gift_card( $order_id, $value );

				// translators: Gift card order note.
				$order->add_order_note( sprintf( __( 'Generated gift card code: %1$s for %2$s', 'chocante' ), $code, wc_price( $value ) ) );
				$item->add_meta_data( GIFT_CARD_COUPON, $code );
				$item->save_meta_data();

				++$i;
			}
		}
	}

	if ( $send_email ) {
		schedule_gift_card_email( $order_id );
	}
}

/**
 * Generate gift card coupon
 *
 * @param int   $order_id Order ID.
 * @param float $amount Gift card value.
 * @return string
 */
function generate_gift_card( $order_id, $amount ) {
	$coupon_code = wc_format_coupon_code( wp_generate_password( GIFT_CARD_COUPON_LENGTH, false, false ) );
	$gift_card   = new \WC_Coupon();

	$gift_card->set_code( $coupon_code );
	$gift_card->set_amount( $amount );
	$gift_card->set_discount_type( GIFT_CARD_COUPON );
	// translators: Gift card coupon decription.
	$gift_card->set_description( sprintf( __( 'Gift card generated for Order #%s', 'chocante' ), $order_id ) );
	$gift_card->set_usage_limit( 1 );
	$gift_card->save();

	return $coupon_code;
}

/**
 * Add gift card to coupon types
 *
 * @param array $coupons Coupon types.
 * @return array
 */
function add_gift_card_coupon_type( $coupons ) {
	$coupons[ GIFT_CARD_COUPON ] = __( 'Gift card', 'chocante' );

	return $coupons;
}

/**
 * Add discount for gift card coupon
 *
 * @see WC_Coupon::get_discount_amount
 *
 * @param float      $discount Amount this coupon has discounted.
 * @param  float      $discounting_amount Amount the coupon is being applied to.
 * @param  array|null $cart_item          Cart item being discounted if applicable.
 * @param  boolean    $single             True if discounting a single qty item, false if its the line.
 * @param \WC_Coupon $coupon Coupon object.
 * @return float
 */
function add_gift_card_coupon_discount( $discount, $discounting_amount, $cart_item, $single, $coupon ) {
	if ( $coupon->is_type( GIFT_CARD_COUPON ) ) {
		if ( ! is_null( $cart_item ) && WC()->cart->subtotal_ex_tax ) {
			$cart_item_qty = is_null( $cart_item ) ? 1 : $cart_item['quantity'];

			if ( wc_prices_include_tax() ) {
				$discount_percent = ( wc_get_price_including_tax( $cart_item['data'] ) * $cart_item_qty ) / WC()->cart->subtotal;
			} else {
				$discount_percent = ( wc_get_price_excluding_tax( $cart_item['data'] ) * $cart_item_qty ) / WC()->cart->subtotal_ex_tax;
			}

			$discount = ( (float) $coupon->get_amount() * $discount_percent ) / $cart_item_qty;
		}

		return NumberUtil::round( min( $discount, $discounting_amount ), wc_get_rounding_precision() );
	}

	return $discount;
}

/**
 * Add gift card coupon type to cart
 *
 * @param array $coupons Cart coupons.
 * @return array
 */
function add_gift_card_coupon_to_cart( $coupons ) {
	$coupons[] = GIFT_CARD_COUPON;
	return $coupons;
}

/**
 * Set display name for gift card order meta
 *
 * @param string $key Order meta key.
 * @return string
 */
function set_display_name_for_gift_card_meta( $key ) {
	if ( GIFT_CARD_COUPON === $key ) {
		return __( 'Gift card code', 'chocante' );
	}

	return $key;
}

/**
 * Display gift card code in order details for customer
 *
 * @param int                    $item_id    The item ID.
 * @param \WC_Order_Item_Product $item       The item object.
 */
function display_gift_card_in_customer_order( $item_id, $item ) {
	$gift_cards = $item->get_meta( GIFT_CARD_COUPON, false );

	if ( empty( $gift_cards ) ) {
		return;
	}
	?>
	<div class="gift-cards">
		<?php foreach ( $gift_cards as $gift_card ) : ?>
		<p><?php esc_html_e( 'Gift card code', 'chocante' ); ?>: <strong><?php echo esc_html( $gift_card->value ); ?></strong></p>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Schedule sending email with the gift card coupon to customer
 *
 * @param int $order_id Order ID.
 */
function schedule_gift_card_email( $order_id ) {
	if ( ! as_has_scheduled_action( GIFT_CARD_ACTION_HOOK, array( $order_id ), 'chocante-gift-cards' ) ) {
		as_schedule_single_action( time() + 60, GIFT_CARD_ACTION_HOOK, array( $order_id ), 'chocante-gift-cards' );
	}
}

/**
 * Load gift card email controller
 *
 * @param array $emails Email classes.
 * @return array
 */
function load_gift_card_email( $emails ) {
	$emails['Chocante_Gift_Card_Email'] = include __DIR__ . '/class-chocante-gift-card-email.php';
	return $emails;
}

/**
 * Send gift card email
 *
 * @param int $order_id Order ID.
 */
function send_gift_card_email( $order_id ) {
	WC()->mailer();
	do_action( 'chocante_gift_card_notification', $order_id );
}

/**
 * Handle order cancellation
 *
 * @param int    $order_id   Order ID.
 * @param string $old_status Previous status (sans `wc-` prefix).
 * @param string $new_status New status (sans `wc-` prefix).
 */
function handle_order_cancellation( $order_id, $old_status, $new_status ) {
	$eligible_statuses = array( OrderStatus::COMPLETED, OrderStatus::PROCESSING );

	$was_eligible = in_array( $old_status, $eligible_statuses, true );
	$is_eligible  = in_array( $new_status, $eligible_statuses, true );

	if ( $was_eligible && ! $is_eligible ) {
		remove_gift_cards( $order_id );
	}
}

/**
 * Remove coupon on order cancellation
 *
 *  @param int $order_id The affected order ID.
 */
function remove_gift_cards( $order_id ) {
	as_unschedule_action( GIFT_CARD_ACTION_HOOK, array( $order_id ) );

	$order = wc_get_order( $order_id );

	if ( ! $order instanceof \WC_Order ) {
		return;
	}

	$order_items = $order->get_items();

	foreach ( $order_items as $item ) {
		$coupon_codes = $item->get_meta( GIFT_CARD_COUPON, false );

		foreach ( $coupon_codes as $coupon_code ) {
			$coupon_id = wc_get_coupon_id_by_code( $coupon_code->value );

			if ( $coupon_id ) {
				$coupon = new \WC_Coupon( $coupon_id );
				$coupon->delete( true );
			}
		}

		$item->delete_meta_data( GIFT_CARD_COUPON );
		$item->save();
	}

	$order->add_order_note( __( 'Gift card codes removed from order', 'chocante' ) );
	$order->save();
}