<?php
/**
 * Gift card email - plain text
 *
 * @package WordPress
 * @subpackage Chocante
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $user_display_name ) );
echo "\n\n";
esc_html_e( 'Thanks for shopping with us. Here are your gift cards:', 'chocante' );
echo "\n\n";

foreach ( $gift_cards as $coupon ) {
	echo esc_html( $coupon->value );
	echo "\n";
}

echo "\n\n";
esc_html_e( 'Apply the coupon code in cart or checkout to get a discount for your order.', 'chocante' );
echo "\n\n";
esc_html_e( 'You can also find the gift card coupon codes in your account under order details.', 'chocante' );
echo "\n\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
