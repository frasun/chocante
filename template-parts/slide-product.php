<?php
/**
 * Post slide
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

$product_category  = apply_filters( 'chocante_featured_products_category', null, get_the_ID() );
$product_name      = apply_filters( 'chocante_featured_products_title', get_the_title(), get_the_ID() );
$product_thumbnail = apply_filters( 'chocante_featured_products_thumbnail', get_the_post_thumbnail( get_the_ID(), array( 570, 700 ) ), get_the_ID() );
$product_info      = apply_filters( 'chocante_featured_products_diet_icons', array(), get_the_ID() );

?>
<div class="post">
	<?php if ( isset( $product_category ) ) : ?>
		<h5 class="post__heading"><?php echo esc_html( $product_category ); ?></h5>
	<?php endif; ?>
	<h2 class="post__title">
		<a href="<?php echo esc_url( get_permalink( get_the_ID() ) ); ?>"><?php echo esc_html( $product_name ); ?></a>
	</h2>
	<div class="post__cta">
		<a href="<?php echo esc_url( get_permalink( get_the_ID() ) ); ?>"><?php echo esc_html_x( 'Buy now', 'product loop', 'chocante' ); ?></a>
		<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="shop-link"><?php esc_html_e( 'Go to shop', 'woocommerce' ); ?></a>
	</div>
	<?php if ( ! empty( $product_info ) ) : ?>
	<div class="post__info">
		<?php Chocante_Product_Tags::display_diet_icons( $product_info ); ?>
	</div>
	<?php endif; ?>
	<?php if ( isset( $product_thumbnail ) ) : ?>
		<a class="post__thumbnail" href="<?php echo esc_url( get_permalink( get_the_ID() ) ); ?>">
			<figure>
					<?php woocommerce_show_product_sale_flash(); ?>
					<?php echo wp_kses_post( $product_thumbnail ); ?>
			</figure>
		</a>
	<?php endif; ?>
</div>