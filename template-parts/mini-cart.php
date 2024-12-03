<?php
/**
 * Mini Cart
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="mini-cart">
	<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="mini-cart__button" title="<?php esc_attr_e( 'Cart', 'woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'woocommerce' ); ?>">
		<?php Chocante::icon( 'cart' ); ?>
		<span class="mini-cart__count" data-count="<?php echo esc_attr( WC()->cart->get_cart_contents_count() ); ?>"></span>
	</a>
	<aside class="mini-cart__content">
		<div class="widget_shopping_cart_content">
			<?php woocommerce_mini_cart(); ?>
		</div>
	</aside>
</div>