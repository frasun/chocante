<?php
/**
 * Post slide
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="post">
	<h5 class="post__heading">Kakao ceremonialne</h5>
	<?php the_title( '<h2 class="post__title">', '</h2>' ); ?>
	<div class="post__cta">
		<a href="<?php echo esc_url( get_permalink( get_the_ID() ) ); ?>"><?php echo esc_html_x( 'Buy now', 'product loop', 'chocante' ); ?></a>
		<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="shop-link"><?php esc_html_e( 'Go to shop', 'woocommerce' ); ?></a>
	</div>
	<?php if ( has_post_thumbnail() ) : ?>
		<figure class="post__thumbnail">
		<?php
		if ( $args['onsale'] ) {
			echo wp_kses_post( '<span class="onsale">' . esc_html__( 'Sale!', 'woocommerce' ) . '</span>' );
		}
		?>
			<?php the_post_thumbnail( array( 570, 700 ) ); ?>
		</figure>
	<?php endif; ?>
</div>