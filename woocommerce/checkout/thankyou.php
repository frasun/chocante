<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.1.0
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;

if ( ! $order ) {
	get_template_part( 'template-parts/404' );
	return;
}

$order_id     = $order->get_id();
$order_number = $order->get_order_number();
$order_link   = $order->get_view_order_url();

$page_title = _x( 'Thank you for shopping', 'thankyou', 'chocante' );
// translators: thank you text.
$message = _x( 'You will be informed about further steps by separate emails. You can also keep up to date with the current status at this address:', 'thankyou', 'chocante' );
// translators: processing order.
$processing_message = sprintf( _x( 'Your order no. %s has been accepted for processing.', 'thankyou', 'chocante' ), '<strong>' . $order_number . '</strong>' );

// @todo: Chocante - temp fix.
$parts = parse_url( $_SERVER['REQUEST_URI'] ); // @codingStandardsIgnoreLine.
parse_str( $parts['query'], $query );

if ( isset( $query['error'] ) ) {
	$order->update_status( 'failed' );
}
// END TODO.
?>

<div class="woocommerce-order">
	<?php do_action( 'woocommerce_before_thankyou', $order_id ); ?>
	<?php if ( $order->has_status( 'failed' ) ) : ?>
		<p><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?></p>
	<?php else : ?>
		<h1 class="page-title"><?php echo esc_html( $page_title ); ?></h1>
		<p><?php echo wp_kses_post( $processing_message ); ?></p>
		<p><?php echo wp_kses_post( $message ); ?><br /><a href="<?php echo esc_url( $order_link ); ?>" data-no-translation><?php echo esc_url( $order_link ); ?></a></p>
		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order_id ); ?>
	<?php endif; ?>
	<a href="<?php echo esc_url( home_url() ); ?>" class="button"><?php echo esc_html_x( 'Go to homepage', 'thankyou', 'chocante' ); ?></a>
	<?php do_action( 'woocommerce_thankyou', $order_id ); ?>
</div>