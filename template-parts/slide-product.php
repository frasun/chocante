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
		<span><?php echo esc_html( $product_name ); ?></span>
	</h2>
	<div class="post__cta">
		<a href="<?php echo esc_url( get_permalink( get_the_ID() ) ); ?>"><?php echo esc_html_x( 'Buy now', 'product loop', 'chocante' ); ?></a>
		<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="shop-link"><?php esc_html_e( 'Go to shop', 'woocommerce' ); ?></a>
	</div>
	<?php if ( ! empty( $product_info ) ) : ?>
	<div class="post__info">
		<?php
		foreach ( $product_info as $icon ) {
			echo wp_get_attachment_image( $icon['id'], 'full' );
		}
		?>
	</div>
	<?php endif; ?>
	<?php if ( isset( $product_thumbnail ) ) : ?>
		<figure class="post__thumbnail">
			<?php
			if ( $args['onsale'] ) {
				echo wp_kses_post( '<span class="onsale">' . esc_html__( 'Sale!', 'woocommerce' ) . '</span>' );
			}
			?>
			<?php echo wp_kses_post( $product_thumbnail ); ?>
		</figure>
	<?php endif; ?>
</div>