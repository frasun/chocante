<?php
/**
 * Gift card email - html
 *
 * @package WordPress
 * @subpackage Chocante
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<?php /* translators: %s: Customer billing first name */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $user_display_name ) ); ?></p>
<p><?php esc_html_e( 'Thanks for shopping with us. Here are your gift cards:', 'chocante' ); ?></p>
<?php if ( $email_improvements_enabled ) : ?>
	<div class="hr hr-top"></div>
	<?php foreach ( $gift_cards as $coupon ) : ?>
	<p><b><?php echo esc_html( $coupon->value ); ?></b></p>
	<?php endforeach; ?>
	<div class="hr hr-bottom"></div>
<?php else : ?>
	<?php foreach ( $gift_cards as $coupon ) : ?>
	<p><b><?php echo esc_html( $coupon->value ); ?></b></p>
	<?php endforeach; ?>
<?php endif; ?>
<p><?php esc_html_e( 'Apply the coupon code in cart or checkout to get a discount for your order.', 'chocante' ); ?></p>
<p><small><?php esc_html_e( 'You can also find the gift card coupon codes in your account under order details.', 'chocante' ); ?></small></p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content email-additional-content-aligned">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

do_action( 'woocommerce_email_footer', $email );
