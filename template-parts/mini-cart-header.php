<?php
/**
 * Mini Cart header
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>
<h4 class="mini-cart__content-title">
	<a href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Cart', 'woocommerce' ); ?></a>
	<?php $count = WC()->cart->get_cart_contents_count(); ?>
	<?php if ( $count > 0 ) : ?>
	<span class="count">
		<?php echo esc_html( WC()->cart->get_cart_contents_count() ); ?>
	</span>
	<?php endif; ?>
</h4>