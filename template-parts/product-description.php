<?php
/**
 * Additional description on product page
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

$post_content = get_the_content();

if ( empty( $post_content ) ) {
	return;
}

$heading = apply_filters( 'woocommerce_product_description_heading', __( 'Description', 'woocommerce' ) );
?>

<section class="product__description">
	<?php if ( $heading ) : ?>
		<h2><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>
	<?php echo wp_kses_post( apply_filters( 'the_content', $post_content ) ); ?>
</section>