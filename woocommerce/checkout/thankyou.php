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

$page_title = _x( 'Thank you for shopping', 'thankyou', 'chocante' );
$message    = apply_filters(
	'chocante_thankyou_order_received_text',
	// translators: thank you text.
	sprintf( esc_html_x( 'You will be informed about further steps by separate emails. You can also keep up to date with the current status at %1$sthis address%2$s.', 'thankyou', 'chocante' ), '<a href="' . esc_url( wc_get_account_endpoint_url( 'orders' ) ) . '">', '</a>' )
);
?>

<div class="woocommerce-order">
	<?php
	if ( $order ) {
		do_action( 'woocommerce_before_thankyou', $order->get_id() );
	}
	?>

	<?php if ( $order && ! $order->has_status( 'failed' ) ) : ?>
		<h1 class="page-title"><?php echo esc_html( $page_title ); ?></h1>
	<?php endif; ?>
	<?php if ( $order && ! $order->has_status( 'failed' ) ) : ?>
		<?php
		if ( $order ) {
			echo '<p>';
			// translators: processing order.
			printf( esc_html_x( 'Your order no. %s has been accepted for processing.', 'thankyou', 'chocante' ), '<strong>' . esc_html( $order->get_order_number() ) . '</strong>' );
			echo '</p>';
		}
		?>
		<p><?php echo wp_kses_post( $message ); ?></p>
	<?php elseif ( $order ) : ?>
		<p><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?></p>
		<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Pay', 'woocommerce' ); ?></a>
	<?php endif; ?>

	<?php
	if ( $order ) {
		do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
		do_action( 'woocommerce_thankyou', $order->get_id() );
	}
	?>

	<a href="<?php echo esc_url( home_url() ); ?>" class="button"><?php echo esc_html_x( 'Go to homepage', 'thankyou', 'chocante' ); ?></a>
</div>