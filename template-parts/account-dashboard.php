<?php
/**
 * Account dashboard
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="dashboard">
	<div class="box">
		<h3 class="box__heading"><?php esc_html_e( 'Last order', 'woocommerce' ); ?></h3>
		<?php if ( count( $args['order'] ) ) : ?>
			<?php $last_order = wc_get_order( $args['order'][0] ); ?>
			<div class="box__content">
				<div class="order-status-number">
					<span class="order-status order-status--<?php echo esc_attr( $last_order->get_status() ); ?>"><?php echo esc_html( wc_get_order_status_name( $last_order->get_status() ) ); ?></span>
					<strong class="order-number"><?php echo esc_html( _x( '#', 'hash before order number', 'woocommerce' ) . $last_order->get_order_number() ); ?></strong>
				</div>
				<div class="box__item">
					<span class="box__item-label"><?php esc_html_e( 'Total', 'woocommerce' ); ?></span>
					<strong class="box__item-value"><?php echo wp_kses_post( $last_order->get_formatted_order_total() ); ?></strong>
				</div>
				<div class="box__item">
					<span class="box__item-label"><?php esc_html_e( 'Date', 'woocommerce' ); ?></span>
					<time class="box__item-value"><?php echo esc_html( wc_format_datetime( $last_order->get_date_created() ) ); ?></time>
				</div>
			</div>
			<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="box__link"><?php esc_html_e( 'Orders', 'woocommerce' ); ?></a>
			<?php else : ?>
				<p class="box__empty"><?php echo esc_html__( 'No order has been made yet.', 'woocommerce' ); ?></p>
				<a class="box__link" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>"><?php echo esc_html__( 'Browse products', 'woocommerce' ); ?></a>
			<?php endif; ?>
	</div>

	<div class="box">
		<h3 class="box__heading"><?php esc_html_e( 'Shipping address', 'woocommerce' ); ?></h3>
		<?php if ( ! empty( $args['address'] ) ) : ?>
		<address><?php echo wp_kses_post( $args['address'] ); ?></address>
		<?php else : ?>
			<?php esc_html_e( 'You have not set up this type of address yet.', 'woocommerce' ); ?>
		<?php endif; ?>
		<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>" class="box__link"><?php echo esc_html( _n( 'Address', 'Addresses', ( 1 + (int) wc_shipping_enabled() ), 'woocommerce' ) ); ?></a>
	</div>
</div>