<?php
/**
 * Mini Cart
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

use function Chocante\Assets\icon;
?>

<div class="mini-cart">
	<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="mini-cart__button" title="<?php esc_attr_e( 'Cart', 'woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'woocommerce' ); ?>">
		<?php icon( 'cart' ); ?>
		<span class="mini-cart__count"></span>
	</a>
	<?php if ( ! is_checkout() ) : ?>
	<aside class="mini-cart__content">
		<div class="widget_shopping_cart_content">
			<?php woocommerce_mini_cart(); ?>
		</div>
	</aside>
	<?php endif; ?>
</div>