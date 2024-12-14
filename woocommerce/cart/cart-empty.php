<?php
/**
 * Empty cart page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-empty.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_cart_is_empty' );
?>

<div class="wc-empty-cart-message empty-screen">
	<figure>
		<?php Chocante::icon( 'shopping-cart' ); ?>
	</figure>
	<h1 class="page-title">
		<?php echo esc_html_x( 'Cart is empty', 'cart', 'chocante' ); ?>
	</h1>
	<p>
		<?php echo esc_html_x( 'You have not added anything to your shopping cart yet. Go to our shop to select the products you are interested in.', 'cart', 'chocante' ); ?>
	</p>

	<?php if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
		<a class="button wc-backward<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
			<?php
				/**
				 * Filter "Return To Shop" text.
				 *
				 * @since 4.6.0
				 * @param string $default_text Default text.
				 */
				echo esc_html( apply_filters( 'woocommerce_return_to_shop_text', __( 'Return to shop', 'woocommerce' ) ) );
			?>
		</a>
	<?php endif; ?>
</div>